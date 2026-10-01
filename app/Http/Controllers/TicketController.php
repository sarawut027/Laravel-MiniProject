<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\RepairLog;
use App\Models\RepairRequest;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = RepairRequest::where('user_id', Auth::id())
            ->with(['category', 'technician']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $tickets = $query->latest()->paginate(10)->withQueryString();
        $categories = Category::all();

        return view('tickets.index', compact('tickets', 'categories'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('tickets.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'urgency' => ['required', 'in:low,normal,high,urgent'],
            'description' => ['required', 'string'],
            'image_before' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            'category_id.required' => 'กรุณาเลือกหมวดหมู่งานซ่อม',
            'title.required' => 'กรุณาระบุหัวข้อปัญหา',
            'location.required' => 'กรุณาระบุสถานที่/ห้อง',
            'urgency.required' => 'กรุณาเลือกระดับความเร่งด่วน',
            'description.required' => 'กรุณากรอกรายละเอียดอาการ',
            'image_before.image' => 'ไฟล์ต้องเป็นรูปภาพเท่านั้น',
            'image_before.max' => 'ขนาดรูปภาพต้องไม่เกิน 5MB',
        ]);

        $imagePath = null;
        if ($request->hasFile('image_before')) {
            $imagePath = $request->file('image_before')->store('repairs', 'public');
        }

        // Generate Ticket Number: REP-YYYYMM-XXXX
        $yearMonth = date('Ym');
        $count = RepairRequest::whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->count() + 1;
        $ticketNumber = 'REP-' . $yearMonth . '-' . sprintf('%04d', $count);

        // Ensure uniqueness if collision occurs
        while (RepairRequest::where('ticket_number', $ticketNumber)->exists()) {
            $count++;
            $ticketNumber = 'REP-' . $yearMonth . '-' . sprintf('%04d', $count);
        }

        $ticket = RepairRequest::create([
            'ticket_number' => $ticketNumber,
            'user_id' => Auth::id(),
            'category_id' => $validated['category_id'],
            'title' => $validated['title'],
            'location' => $validated['location'],
            'urgency' => $validated['urgency'],
            'description' => $validated['description'],
            'status' => 'pending',
            'image_before' => $imagePath,
        ]);

        RepairLog::create([
            'repair_request_id' => $ticket->id,
            'user_id' => Auth::id(),
            'status_from' => null,
            'status_to' => 'pending',
            'comment' => 'ผู้ใช้งานส่งเรื่องแจ้งซ่อมเข้ามาในระบบ',
        ]);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "ส่งเรื่องแจ้งซ่อมเรียบร้อยแล้ว รหัสคำขอ: {$ticket->ticket_number}");
    }

    public function show(RepairRequest $ticket)
    {
        // Check permission: Admin, assigned Technician, or Owner can view
        $user = Auth::user();
        if ($user->role === 'user' && $ticket->user_id !== $user->id) {
            abort(403, 'คุณไม่มีสิทธิ์ดูรายการแจ้งซ่อมของผู้อื่น');
        }

        $ticket->load(['user', 'category', 'technician', 'logs.user', 'review']);
        $technicians = User::where('role', 'technician')->get();

        return view('tickets.show', compact('ticket', 'technicians'));
    }

    public function cancel(RepairRequest $ticket)
    {
        if ($ticket->user_id !== Auth::id()) {
            abort(403, 'คุณไม่มีสิทธิ์ยกเลิกคำขอนี้');
        }

        if ($ticket->status !== 'pending') {
            return back()->with('error', 'ไม่สามารถยกเลิกได้ เนื่องจากเรื่องนี้ได้รับการมอบหมายหรือดำเนินการไปแล้ว');
        }

        $ticket->update(['status' => 'cancelled']);

        RepairLog::create([
            'repair_request_id' => $ticket->id,
            'user_id' => Auth::id(),
            'status_from' => 'pending',
            'status_to' => 'cancelled',
            'comment' => 'ผู้ใช้งานขอยกเลิกคำขอแจ้งซ่อม',
        ]);

        return back()->with('success', 'ยกเลิกคำขอแจ้งซ่อมเรียบร้อยแล้ว');
    }

    public function review(Request $request, RepairRequest $ticket)
    {
        if ($ticket->user_id !== Auth::id()) {
            abort(403, 'คุณไม่มีสิทธิ์ประเมินงานนี้');
        }

        if ($ticket->status !== 'completed') {
            return back()->with('error', 'สามารถประเมินได้เฉพาะงานที่สถานะ "ซ่อมเสร็จสิ้น" เท่านั้น');
        }

        if ($ticket->review()->exists()) {
            return back()->with('error', 'งานซ่อมนี้ได้รับการประเมินความพึงพอใจไปแล้ว');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'rating.required' => 'กรุณาให้คะแนนความพึงพอใจ 1-5 ดาว',
            'rating.between' => 'คะแนนต้องอยู่ระหว่าง 1 ถึง 5 ดาว',
        ]);

        Review::create([
            'repair_request_id' => $ticket->id,
            'user_id' => Auth::id(),
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        $ticket->update(['status' => 'closed']);

        RepairLog::create([
            'repair_request_id' => $ticket->id,
            'user_id' => Auth::id(),
            'status_from' => 'completed',
            'status_to' => 'closed',
            'comment' => "ผู้ใช้ตรวจรับงานและประเมินความพึงพอใจ: {$validated['rating']} ดาว",
        ]);

        return back()->with('success', 'บันทึกการประเมินความพึงพอใจเรียบร้อยแล้ว ขอบคุณสำหรับความคิดเห็นครับ');
    }
}
