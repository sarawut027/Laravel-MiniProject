<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\RepairLog;
use App\Models\RepairRequest;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function dashboard()
    {
        $total = RepairRequest::count();
        $pending = RepairRequest::where('status', 'pending')->count();
        $inProgress = RepairRequest::whereIn('status', ['assigned', 'in_progress', 'waiting_parts'])->count();
        $completed = RepairRequest::whereIn('status', ['completed', 'closed'])->count();
        $avgRating = round(Review::avg('rating') ?: 0, 1);

        $stats = [
            'total' => $total,
            'pending' => $pending,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'average_rating' => $avgRating,
        ];

        $recentTickets = RepairRequest::with(['category', 'user', 'technician'])
            ->latest()
            ->take(5)
            ->get();

        // Chart Data: Grouped by Category
        $categories = Category::withCount('tickets')->get();
        $chartData = [
            'category_labels' => $categories->pluck('name')->toArray(),
            'category_counts' => $categories->pluck('tickets_count')->toArray(),
            'status_counts' => [
                'pending' => $pending,
                'assigned' => RepairRequest::where('status', 'assigned')->count(),
                'in_progress' => RepairRequest::where('status', 'in_progress')->count(),
                'waiting_parts' => RepairRequest::where('status', 'waiting_parts')->count(),
                'completed' => RepairRequest::where('status', 'completed')->count(),
                'closed' => RepairRequest::where('status', 'closed')->count(),
                'cancelled' => RepairRequest::where('status', 'cancelled')->count(),
            ],
        ];

        return view('admin.dashboard', compact('stats', 'recentTickets', 'chartData'));
    }

    public function tickets(Request $request)
    {
        $query = RepairRequest::with(['category', 'user', 'technician']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('urgency')) {
            $query->where('urgency', $request->urgency);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $tickets = $query->latest()->paginate(15)->withQueryString();
        $categories = Category::all();
        $technicians = User::where('role', 'technician')->get();

        return view('admin.tickets', compact('tickets', 'categories', 'technicians'));
    }

    public function assign(Request $request, RepairRequest $ticket)
    {
        $validated = $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
            'comment' => ['nullable', 'string', 'max:500'],
        ], [
            'assigned_to.required' => 'กรุณาเลือกช่างซ่อมที่ต้องการมอบหมาย',
            'assigned_to.exists' => 'ไม่พบข้อมูลช่างซ่อมที่เลือก',
        ]);

        $tech = User::findOrFail($validated['assigned_to']);
        if ($tech->role !== 'technician') {
            return back()->with('error', 'ผู้ใช้ที่เลือกไม่ใช่ช่างซ่อม');
        }

        $statusFrom = $ticket->status;
        $statusTo = ($ticket->status === 'pending') ? 'assigned' : $ticket->status;

        $ticket->update([
            'assigned_to' => $tech->id,
            'assigned_at' => now(),
            'status' => $statusTo,
        ]);

        $logComment = "แอดมินมอบหมายงานให้ช่าง: {$tech->name}";
        if (!empty($validated['comment'])) {
            $logComment .= " (ข้อความสั่งการ: {$validated['comment']})";
        }

        RepairLog::create([
            'repair_request_id' => $ticket->id,
            'user_id' => Auth::id(),
            'status_from' => $statusFrom,
            'status_to' => $statusTo,
            'comment' => $logComment,
        ]);

        return back()->with('success', "มอบหมายงานให้ช่าง \"{$tech->name}\" เรียบร้อยแล้ว");
    }
}
