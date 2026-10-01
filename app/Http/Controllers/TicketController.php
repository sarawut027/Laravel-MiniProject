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

/**
 * Class TicketController
 * คอนโทรลเลอร์สำหรับจัดการงานแจ้งซ่อมของผู้ใช้งาน (User / Requester)
 * หน้าที่หลัก:
 * 1. แสดงรายการแจ้งซ่อมของตนเอง (index)
 * 2. แสดงฟอร์มแจ้งซ่อมใหม่ (create)
 * 3. บันทึกข้อมูลการแจ้งซ่อมและอัปโหลดรูปภาพ (store)
 * 4. ดูรายละเอียดงาน Timeline และข้อมูลช่าง (show)
 * 5. ขอยกเลิกคำขอแจ้งซ่อม (cancel)
 * 6. ประเมินความพึงพอใจและปิดงาน (review)
 */
class TicketController extends Controller
{
    /**
     * แสดงรายการแจ้งซ่อมทั้งหมดของผู้ใช้งานที่ล็อกอินอยู่
     * พร้อมรองรับการกรองตามสถานะ (Status) และหมวดหมู่ (Category)
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // ดึงเฉพาะงานแจ้งซ่อมที่เป็นของ User ที่กำลัง Login อยู่เท่านั้น
        // โหลด Eager Loading ความสัมพันธ์ category และ technician เพื่อลด N+1 Query
        $query = RepairRequest::where('user_id', Auth::id())
            ->with(['category', 'technician']);

        // กรองตามสถานะงาน เช่น pending, in_progress, completed
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // กรองตามหมวดหมู่งานซ่อม
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // แบ่งหน้าละ 10 รายการ และรักษา Query String ที่ใช้ในการกรองไว้
        $tickets = $query->latest()->paginate(10)->withQueryString();
        
        // ดึงหมวดหมู่ทั้งหมดเพื่อส่งไปแสดงในตัวเลือก Filter หน้า View
        $categories = Category::all();

        return view('tickets.index', compact('tickets', 'categories'));
    }

    /**
     * แสดงหน้าฟอร์มสร้างใบแจ้งซ่อมใหม่
     * ส่งรายการหมวดหมู่ทั้งหมด (Categories) ไปให้ผู้ใช้เลือก
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $categories = Category::all();
        return view('tickets.create', compact('categories'));
    }

    /**
     * บันทึกข้อมูลใบแจ้งซ่อมใหม่ลงฐานข้อมูล
     * มีการตรวจสอบความถูกต้องของข้อมูล (Validation), บันทึกไฟล์รูปภาพ (File Upload),
     * ออกรหัส Ticket อัตโนมัติ (REP-YYYYMM-XXXX), และบันทึก Timeline เริ่มต้น
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // 1. ตรวจสอบความถูกต้องของข้อมูล (Form Validation)
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'urgency' => ['required', 'in:low,normal,high,urgent'],
            'description' => ['required', 'string'],
            'image_before' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'], // ไม่เกิน 5MB
        ], [
            'category_id.required' => 'กรุณาเลือกหมวดหมู่งานซ่อม',
            'title.required' => 'กรุณาระบุหัวข้อปัญหา',
            'location.required' => 'กรุณาระบุสถานที่/ห้อง',
            'urgency.required' => 'กรุณาเลือกระดับความเร่งด่วน',
            'description.required' => 'กรุณากรอกรายละเอียดอาการ',
            'image_before.image' => 'ไฟล์ต้องเป็นรูปภาพเท่านั้น',
            'image_before.max' => 'ขนาดรูปภาพต้องไม่เกิน 5MB',
        ]);

        // 2. จัดการอัปโหลดไฟล์รูปภาพอุปกรณ์ก่อนซ่อม (ถ้ามี)
        $imagePath = null;
        if ($request->hasFile('image_before')) {
            // บันทึกลงใน storage/app/public/repairs ซึ่งเข้าถึงได้ผ่าน asset('storage/...')
            $imagePath = $request->file('image_before')->store('repairs', 'public');
        }

        // 3. สร้างรหัส Ticket Number อัตโนมัติในรูปแบบ: REP-YYYYMM-XXXX (เช่น REP-202610-0001)
        $yearMonth = date('Ym');
        $count = RepairRequest::whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->count() + 1;
        $ticketNumber = 'REP-' . $yearMonth . '-' . sprintf('%04d', $count);

        // ตรวจสอบความซ้ำซ้อน หากชนให้เพิ่มลำดับจนกว่าจะไม่ซ้ำ
        while (RepairRequest::where('ticket_number', $ticketNumber)->exists()) {
            $count++;
            $ticketNumber = 'REP-' . $yearMonth . '-' . sprintf('%04d', $count);
        }

        // 4. บันทึกข้อมูลใบแจ้งซ่อมลงในตาราง repair_requests
        $ticket = RepairRequest::create([
            'ticket_number' => $ticketNumber,
            'user_id' => Auth::id(),
            'category_id' => $validated['category_id'],
            'title' => $validated['title'],
            'location' => $validated['location'],
            'urgency' => $validated['urgency'],
            'description' => $validated['description'],
            'status' => 'pending', // สถานะเริ่มต้น: รอดำเนินการ
            'image_before' => $imagePath,
        ]);

        // 5. บันทึกประวัติ Timeline เริ่มต้นลงตาราง repair_logs
        RepairLog::create([
            'repair_request_id' => $ticket->id,
            'user_id' => Auth::id(),
            'status_from' => null,
            'status_to' => 'pending',
            'comment' => 'ผู้ใช้งานส่งเรื่องแจ้งซ่อมเข้ามาในระบบ',
        ]);

        // นำผู้ใช้ไปยังหน้ารายละเอียดของ Ticket นั้น พร้อมแสดง Flash Alert ข้อความสำเร็จ
        return redirect()->route('tickets.show', $ticket)
            ->with('success', "ส่งเรื่องแจ้งซ่อมเรียบร้อยแล้ว รหัสคำขอ: {$ticket->ticket_number}");
    }

    /**
     * แสดงหน้ารายละเอียดของใบแจ้งซ่อม รูปภาพ Before/After และประวัติ Timeline
     * มีการตรวจสอบสิทธิ์: เจ้าของเรื่อง, ช่างที่ได้รับมอบหมาย, หรือแอดมินเท่านั้นที่ดูได้
     *
     * @param RepairRequest $ticket
     * @return \Illuminate\View\View
     */
    public function show(RepairRequest $ticket)
    {
        // ตรวจสอบสิทธิ์ (Authorization Check)
        // หากผู้ใช้มี role เป็น user จะต้องเป็นเจ้าของ Ticket นี้เท่านั้น
        $user = Auth::user();
        if ($user->role === 'user' && $ticket->user_id !== $user->id) {
            abort(403, 'คุณไม่มีสิทธิ์ดูรายการแจ้งซ่อมของผู้อื่น');
        }

        // โหลดข้อมูล Relations เพื่อใช้งานใน Blade
        $ticket->load(['user', 'category', 'technician', 'logs.user', 'review']);
        
        // ดึงรายชื่อช่างซ่อม เพื่อให้ Admin ใช้ใน Dropdown สั่งมอบหมายงาน
        $technicians = User::where('role', 'technician')->get();

        return view('tickets.show', compact('ticket', 'technicians'));
    }

    /**
     * ฟังก์ชันขอยกเลิกคำขอแจ้งซ่อม (สำหรับผู้ใช้)
     * เงื่อนไข: ต้องเป็นเจ้าของเรื่อง และสถานะต้องเป็น 'pending' (ยังไม่เริ่มดำเนินการ) เท่านั้น
     *
     * @param RepairRequest $ticket
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(RepairRequest $ticket)
    {
        // ป้องกันไม่ให้ผู้ใช้อื่นแอบกดยกเลิก
        if ($ticket->user_id !== Auth::id()) {
            abort(403, 'คุณไม่มีสิทธิ์ยกเลิกคำขอนี้');
        }

        // ตรวจสอบสถานะ หากแอดมินมอบหมายหรือช่างเริ่มทำแล้ว ไม่อนุญาตให้ยกเลิก
        if ($ticket->status !== 'pending') {
            return back()->with('error', 'ไม่สามารถยกเลิกได้ เนื่องจากเรื่องนี้ได้รับการมอบหมายหรือดำเนินการไปแล้ว');
        }

        // อัปเดตสถานะเป็น 'cancelled'
        $ticket->update(['status' => 'cancelled']);

        // บันทึกประวัติ Timeline การยกเลิก
        RepairLog::create([
            'repair_request_id' => $ticket->id,
            'user_id' => Auth::id(),
            'status_from' => 'pending',
            'status_to' => 'cancelled',
            'comment' => 'ผู้ใช้งานขอยกเลิกคำขอแจ้งซ่อม',
        ]);

        return back()->with('success', 'ยกเลิกคำขอแจ้งซ่อมเรียบร้อยแล้ว');
    }

    /**
     * ฟังก์ชันประเมินความพึงพอใจการซ่อม (Rating & Review) และปิดงาน (Closed)
     * เงื่อนไข: ผู้แจ้งต้องเป็นคนประเมิน และสถานะต้องเป็น 'completed' (ช่างซ่อมเสร็จแล้ว)
     *
     * @param Request $request
     * @param RepairRequest $ticket
     * @return \Illuminate\Http\RedirectResponse
     */
    public function review(Request $request, RepairRequest $ticket)
    {
        // ตรวจสอบสิทธิ์ว่าใช่เจ้าของงานหรือไม่
        if ($ticket->user_id !== Auth::id()) {
            abort(403, 'คุณไม่มีสิทธิ์ประเมินงานนี้');
        }

        // ตรวจสอบว่าช่างซ่อมเสร็จหรือยัง
        if ($ticket->status !== 'completed') {
            return back()->with('error', 'สามารถประเมินได้เฉพาะงานที่สถานะ "ซ่อมเสร็จสิ้น" เท่านั้น');
        }

        // ป้องกันการกดประเมินซ้ำ
        if ($ticket->review()->exists()) {
            return back()->with('error', 'งานซ่อมนี้ได้รับการประเมินความพึงพอใจไปแล้ว');
        }

        // ตรวจสอบความถูกต้องของคะแนนดาว (1 ถึง 5)
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'rating.required' => 'กรุณาให้คะแนนความพึงพอใจ 1-5 ดาว',
            'rating.between' => 'คะแนนต้องอยู่ระหว่าง 1 ถึง 5 ดาว',
        ]);

        // บันทึกข้อมูลรีวิวลงตาราง reviews
        Review::create([
            'repair_request_id' => $ticket->id,
            'user_id' => Auth::id(),
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        // อัปเดตสถานะของ Ticket เป็น 'closed' (ปิดงานสมบูรณ์)
        $ticket->update(['status' => 'closed']);

        // บันทึก Timeline ปิดงาน
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
