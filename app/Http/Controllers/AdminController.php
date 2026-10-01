<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\RepairLog;
use App\Models\RepairRequest;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class AdminController
 * คอนโทรลเลอร์สำหรับผู้ดูแลระบบ (Admin)
 * หน้าที่หลัก:
 * 1. แดชบอร์ดสรุปสถิติตัวเลข KPI และข้อมูลสำหรับสร้างกราฟ (dashboard)
 * 2. จัดการงานซ่อมทั้งหมด ค้นหา กรองสถานะ และติดตามงาน (tickets)
 * 3. มอบหมายงานและจ่ายงานให้ช่างซ่อม (assign)
 */
class AdminController extends Controller
{
    /**
     * หน้าแดชบอร์ดสรุปสถิติงานซ่อมบำรุง
     * คำนวณตัวเลข KPI 5 ตัว, รายการงานล่าสุด 5 รายการ,
     * และเตรียมชุดข้อมูล Array สำหรับแสดงผลชาร์ตแยกตามหมวดหมู่และสถานะ
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        // คำนวณตัวเลขสถิติหลัก (KPI Cards)
        $total = RepairRequest::count(); // จำนวนงานทั้งหมด
        $pending = RepairRequest::where('status', 'pending')->count(); // งานที่รอดำเนินการ (ยังไม่มอบหมาย)
        $inProgress = RepairRequest::whereIn('status', ['assigned', 'in_progress', 'waiting_parts'])->count(); // งานที่อยู่ระหว่างดำเนินการ
        $completed = RepairRequest::whereIn('status', ['completed', 'closed'])->count(); // งานที่เสร็จแล้วทั้งหมด
        $avgRating = round(Review::avg('rating') ?: 0, 1); // คะแนนประเมินเฉลี่ยจากผู้ใช้ (ทศนิยม 1 ตำแหน่ง)

        $stats = [
            'total' => $total,
            'pending' => $pending,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'average_rating' => $avgRating,
        ];

        // ดึงงานแจ้งซ่อมล่าสุด 5 รายการ พร้อม Eager Loading ข้อมูลที่เกี่ยวข้อง
        $recentTickets = RepairRequest::with(['category', 'user', 'technician'])
            ->latest()
            ->take(5)
            ->get();

        // จัดเตรียมข้อมูลสำหรับแสดงผลกราฟสถิติ (Category Distribution & Status Breakdown)
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

    /**
     * หน้ารายการงานแจ้งซ่อมทั้งหมดสำหรับผู้ดูแลระบบ
     * รองรับการค้นหา (Search) และตัวกรองหลายมิติ (Multi-filter): สถานะ, หมวดหมู่, ความเร่งด่วน, ช่างผู้รับผิดชอบ
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function tickets(Request $request)
    {
        $query = RepairRequest::with(['category', 'user', 'technician']);

        // ค้นหาตามคำค้นหา: รหัสคำขอ, หัวข้อ, สถานที่, หรือชื่อผู้แจ้งเรื่อง
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

        // กรองตามสถานะงาน
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // กรองตามหมวดหมู่งาน
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // กรองตามระดับความเร่งด่วน
        if ($request->filled('urgency')) {
            $query->where('urgency', $request->urgency);
        }

        // กรองตามช่างผู้รับผิดชอบ
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // แบ่งหน้าละ 15 รายการ และรักษา Query String ใน Pagination Link
        $tickets = $query->latest()->paginate(15)->withQueryString();
        
        // ข้อมูลสำหรับ Dropdown ในส่วนตัวกรอง
        $categories = Category::all();
        $technicians = User::where('role', 'technician')->get();

        return view('admin.tickets', compact('tickets', 'categories', 'technicians'));
    }

    /**
     * ฟังก์ชันมอบหมายงานซ่อมให้ช่าง (Dispatch / Assign Technician)
     * บันทึกช่างผู้รับผิดชอบ เวลาที่มอบหมาย และบันทึก Timeline
     *
     * @param Request $request
     * @param RepairRequest $ticket
     * @return \Illuminate\Http\RedirectResponse
     */
    public function assign(Request $request, RepairRequest $ticket)
    {
        // 1. ตรวจสอบความถูกต้องของ Input
        $validated = $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
            'comment' => ['nullable', 'string', 'max:500'],
        ], [
            'assigned_to.required' => 'กรุณาเลือกช่างซ่อมที่ต้องการมอบหมาย',
            'assigned_to.exists' => 'ไม่พบข้อมูลช่างซ่อมที่เลือก',
        ]);

        // 2. ตรวจสอบว่า User ที่เลือกมีบทบาท (role) เป็น technician จริงหรือไม่
        $tech = User::findOrFail($validated['assigned_to']);
        if ($tech->role !== 'technician') {
            return back()->with('error', 'ผู้ใช้ที่เลือกไม่ใช่ช่างซ่อม');
        }

        $statusFrom = $ticket->status;
        // หากสถานะเดิมคือ 'pending' เมื่อมอบหมายงานแล้วให้เปลี่ยนเป็น 'assigned'
        $statusTo = ($ticket->status === 'pending') ? 'assigned' : $ticket->status;

        // 3. บันทึกข้อมูลการมอบหมายลงใน Ticket
        $ticket->update([
            'assigned_to' => $tech->id,
            'assigned_at' => now(),
            'status' => $statusTo,
        ]);

        // 4. สร้างข้อความบันทึก Log Timeline
        $logComment = "แอดมินมอบหมายงานให้ช่าง: {$tech->name}";
        if (!empty($validated['comment'])) {
            $logComment .= " (ข้อความสั่งการ: {$validated['comment']})";
        }

        // บันทึก Timeline ของระบบ
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
