<?php

// กำหนด namespace ของคอนโทรลเลอร์ให้อยู่ใน App\Http\Controllers
namespace App\Http\Controllers;

// นำเข้าโมเดลหมวดหมู่งานซ่อม (Category Model)
use App\Models\Category;
// นำเข้าโมเดลประวัติการบันทึกสถานะงานแจ้งซ่อม (RepairLog Model สำหรับบันทึก Timeline)
use App\Models\RepairLog;
// นำเข้าโมเดลหลักของคำขอแจ้งซ่อม (RepairRequest Model)
use App\Models\RepairRequest;
// นำเข้าโมเดลบันทึกการประเมินดาวและความพึงพอใจ (Review Model)
use App\Models\Review;
// นำเข้าโมเดลผู้ใช้งาน (User Model สำหรับดึงข้อมูลผู้แจ้งและช่างซ่อม)
use App\Models\User;
// นำเข้าคลาส Request เพื่อใช้รับส่งข้อมูล HTTP Request
use Illuminate\Http\Request;
// นำเข้า Auth Facade เพื่อดึงข้อมูลผู้ใช้งานปัจจุบันที่เข้าสู่ระบบ
use Illuminate\Support\Facades\Auth;
// นำเข้า Storage Facade เพื่อจัดการพื้นที่จัดเก็บไฟล์รูปภาพ
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
        // เริ่มต้นสร้าง Query ดึงข้อมูลจากตาราง repair_requests เฉพาะที่เป็นของ User คนปัจจุบัน
        // กรองด้วยเงื่อนไข where('user_id', Auth::id()) เพื่อให้ผู้ใช้มองเห็นเฉพาะงานของตนเอง
        // ใช้งาน Eager Loading ด้วย with(['category', 'technician']) เพื่อป้องกันปัญหา N+1 Query
        $query = RepairRequest::where('user_id', Auth::id())
            ->with(['category', 'technician']);

        // ตรวจสอบว่ามีพารามิเตอร์ 'status' ส่งมาใน Request หรือไม่
        if ($request->filled('status')) {
            // หากมีค่า ให้เพิ่มเงื่อนไข WHERE กรองตามสถานะงาน เช่น pending, in_progress, completed
            $query->where('status', $request->status);
        }

        // ตรวจสอบว่ามีพารามิเตอร์ 'category_id' ส่งมาใน Request หรือไม่
        if ($request->filled('category_id')) {
            // หากมีค่า ให้เพิ่มเงื่อนไข WHERE กรองตามรหัสหมวดหมู่งานซ่อม
            $query->where('category_id', $request->category_id);
        }

        // ดึงข้อมูลโดยเรียงลำดับจากล่าสุด (latest()) และแบ่งหน้าละ 10 รายการ (paginate(10))
        // พ่วง withQueryString() เพื่อให้ลิงก์หน้าถัดไปคงพารามิเตอร์ Filter เดิมไว้ ไม่หลุดหาย
        $tickets = $query->latest()->paginate(10)->withQueryString();
        
        // ดึงรายการหมวดหมู่ทั้งหมดจากฐานข้อมูล เพื่อส่งไปสร้างตัวเลือกใน Dropdown คัดกรองหน้า View
        $categories = Category::all();

        // ส่งตัวแปร $tickets และ $categories ไปเรนเดอร์ใน View resources/views/tickets/index.blade.php
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
        // ดึงหมวดหมู่งานซ่อมทั้งหมด (เช่น คอมพิวเตอร์, ไฟฟ้า, แอร์, ประปา) เพื่อนำไปแสดงใน Select Option
        $categories = Category::all();
        // ส่งตัวแปร $categories ไปยังไฟล์ Blade View resources/views/tickets/create.blade.php
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
        // 1. ตรวจสอบความถูกต้องของข้อมูลฟอร์มแจ้งซ่อม (Form Request Validation)
        $validated = $request->validate([
            // category_id ต้องไม่ว่าง และต้องมี id อยู่จริงในตาราง categories
            'category_id' => ['required', 'exists:categories,id'],
            // title ต้องไม่ว่าง เป็นข้อความ ไม่เกิน 255 ตัวอักษร
            'title' => ['required', 'string', 'max:255'],
            // location ต้องไม่ว่าง เป็นข้อความ ระบุห้องหรือสถานที่ ไม่เกิน 255 ตัวอักษร
            'location' => ['required', 'string', 'max:255'],
            // urgency ระดับความเร่งด่วน ต้องเป็นค่าใดค่าหนึ่งใน low, normal, high, urgent เท่านั้น
            'urgency' => ['required', 'in:low,normal,high,urgent'],
            // description รายละเอียดปัญหา ต้องไม่ว่าง และเป็นข้อความ
            'description' => ['required', 'string'],
            // image_before รูปภาพก่อนซ่อม อนุญาตให้ว่างได้ แต่ถ้ามีต้องเป็นไฟล์รูปนามสกุลที่กำหนด ขนาดไม่เกิน 5MB
            'image_before' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            // ข้อความแจ้งเตือนภาษาไทยเมื่อไม่ได้เลือกหมวดหมู่
            'category_id.required' => 'กรุณาเลือกหมวดหมู่งานซ่อม',
            // ข้อความแจ้งเตือนภาษาไทยเมื่อไม่ได้ระบุหัวข้อปัญหา
            'title.required' => 'กรุณาระบุหัวข้อปัญหา',
            // ข้อความแจ้งเตือนภาษาไทยเมื่อไม่ได้ระบุสถานที่
            'location.required' => 'กรุณาระบุสถานที่/ห้อง',
            // ข้อความแจ้งเตือนภาษาไทยเมื่อไม่ได้เลือกระดับความเร่งด่วน
            'urgency.required' => 'กรุณาเลือกระดับความเร่งด่วน',
            // ข้อความแจ้งเตือนภาษาไทยเมื่อไม่ได้กรอกรายละเอียด
            'description.required' => 'กรุณากรอกรายละเอียดอาการ',
            // ข้อความแจ้งเตือนภาษาไทยเมื่อไฟล์ที่อัปโหลดไม่ใช่รูปภาพ
            'image_before.image' => 'ไฟล์ต้องเป็นรูปภาพเท่านั้น',
            // ข้อความแจ้งเตือนภาษาไทยเมื่อไฟล์รูปภาพใหญ่เกิน 5MB
            'image_before.max' => 'ขนาดรูปภาพต้องไม่เกิน 5MB',
        ]);

        // กำหนดค่าเริ่มต้นของตัวแปรที่เก็บ Path รูปภาพให้เป็น null
        $imagePath = null;
        // ตรวจสอบว่าผู้ใช้งานได้แนบไฟล์ในช่อง 'image_before' มาหรือไม่
        if ($request->hasFile('image_before')) {
            // บันทึกไฟล์รูปภาพลงโฟลเดอร์ 'repairs' ในดิสก์ 'public' (storage/app/public/repairs)
            // คืนค่าเป็น Path สัมพัทธ์ เช่น repairs/abc123xyz.jpg เพื่อนำไปเก็บในฐานข้อมูล
            $imagePath = $request->file('image_before')->store('repairs', 'public');
        }

        // 3. เริ่มต้นอัลกอริทึมสร้างรหัสใบแจ้งซ่อม (Ticket Number Generator)
        // ดึงปีและเดือนปัจจุบันในรูปแบบ ค.ศ. เช่น 202610
        $yearMonth = date('Ym');
        // นับจำนวนงานแจ้งซ่อมที่มีการสร้างในเดือนและปีปัจจุบัน แล้วบวก 1 เพื่อเป็นลำดับถัดไป
        $count = RepairRequest::whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->count() + 1;
        // ประกอบสตริงรหัส Ticket เช่น REP-202610-0001 โดยเติมเลขศูนย์ข้างหน้าให้ครบ 4 หลักด้วย sprintf
        $ticketNumber = 'REP-' . $yearMonth . '-' . sprintf('%04d', $count);

        // วนลูปตรวจสอบความปลอดภัย หากรหัสที่สร้างขึ้นไปซ้ำกับฐานข้อมูลที่มีอยู่
        while (RepairRequest::where('ticket_number', $ticketNumber)->exists()) {
            // เพิ่มตัวนับขึ้นทีละ 1 จนกว่าจะได้รหัสที่ไม่ซ้ำ
            $count++;
            // สร้างรหัส Ticket ใหม่
            $ticketNumber = 'REP-' . $yearMonth . '-' . sprintf('%04d', $count);
        }

        // 4. บันทึกข้อมูลใบแจ้งซ่อมใหม่ลงในตาราง repair_requests ผ่าน Eloquent create()
        $ticket = RepairRequest::create([
            // บันทึกรหัส Ticket Number ที่สร้างได้
            'ticket_number' => $ticketNumber,
            // บันทึก ID ของผู้ใช้งานที่กำลังล็อกอินอยู่เป็นเจ้าของเรื่อง
            'user_id' => Auth::id(),
            // บันทึกหมวดหมู่ที่เลือก
            'category_id' => $validated['category_id'],
            // บันทึกหัวข้อเรื่อง
            'title' => $validated['title'],
            // บันทึกสถานที่
            'location' => $validated['location'],
            // บันทึกระดับความเร่งด่วน
            'urgency' => $validated['urgency'],
            // บันทึกรายละเอียดอาการเสีย
            'description' => $validated['description'],
            // กำหนดสถานะเริ่มต้นของงานแจ้งซ่อมเป็น 'pending' (รอดำเนินการ)
            'status' => 'pending',
            // บันทึก Path ไฟล์รูปภาพก่อนซ่อม (หากไม่มีจะเป็น null)
            'image_before' => $imagePath,
        ]);

        // 5. บันทึกประวัติ Timeline จุดเริ่มต้นของระบบลงในตาราง repair_logs
        RepairLog::create([
            // เชื่อมโยงกับ Ticket ที่เพิ่งสร้างขึ้นใหม่
            'repair_request_id' => $ticket->id,
            // บันทึก ID ของผู้กระทำการ (User ที่ส่งเรื่อง)
            'user_id' => Auth::id(),
            // สถานะเดิมยังไม่มี (เพิ่งสร้างใหม่) จึงให้เป็น null
            'status_from' => null,
            // สถานะใหม่เปลี่ยนเป็น 'pending'
            'status_to' => 'pending',
            // บันทึกคำอธิบายขั้นตอนแรก
            'comment' => 'ผู้ใช้งานส่งเรื่องแจ้งซ่อมเข้ามาในระบบ',
        ]);

        // ส่ง Redirect ผู้ใช้ไปยังหน้ารายละเอียดของใบแจ้งซ่อมนั้นทันที
        // พร้อมส่ง Flash Message 'success' แจ้งว่าบันทึกสำเร็จพร้อมระบุเลขรหัสคำขอ
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
        // 1. ตรวจสอบสิทธิ์การเข้าถึงข้อมูล (Authorization Access Control)
        // ดึงออบเจ็กต์ของผู้ใช้งานที่ล็อกอินอยู่ปัจจุบัน
        $user = Auth::user();
        // หากผู้ใช้งานมีบทบาทเป็น 'user' (ไม่ใช่แอดมินหรือช่าง) และไม่ใช่เจ้าของ Ticket นี้
        if ($user->role === 'user' && $ticket->user_id !== $user->id) {
            // ปฏิเสธการเข้าถึงและส่งรหัส HTTP Status 403 Forbidden ทันที
            abort(403, 'คุณไม่มีสิทธิ์ดูรายการแจ้งซ่อมของผู้อื่น');
        }

        // 2. โหลดความสัมพันธ์ (Eloquent Relationships) ที่จำเป็นทั้งหมดด้วย Lazy Eager Loading
        // โหลดข้อมูล user (ผู้แจ้ง), category (หมวดหมู่), technician (ช่าง), logs.user (คนบันทึก log), review (รีวิว)
        $ticket->load(['user', 'category', 'technician', 'logs.user', 'review']);
        
        // 3. ดึงรายชื่อช่างซ่อมทั้งหมดในระบบ (role = technician)
        // เพื่อใช้สร้างตัวเลือก Dropdown ในกรณีที่ผู้เปิดดูเป็น Admin และต้องการกดมอบหมายงานจากหน้านี้
        $technicians = User::where('role', 'technician')->get();

        // เรนเดอร์ไฟล์ Blade View resources/views/tickets/show.blade.php พร้อมส่งตัวแปร $ticket และ $technicians
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
        // 1. ป้องกันความปลอดภัย: ตรวจสอบว่าผู้ที่กดปุ่มยกเลิก ใช่เจ้าของงานคนเดิมหรือไม่
        if ($ticket->user_id !== Auth::id()) {
            // หากไม่ใช่เจ้าของ ส่งรหัส HTTP Status 403 Forbidden ทันที
            abort(403, 'คุณไม่มีสิทธิ์ยกเลิกคำขอนี้');
        }

        // 2. ตรวจสอบเงื่อนไขทางธุรกิจ (Business Logic): ยกเลิกได้เฉพาะตอนสถานะยังเป็น 'pending' เท่านั้น
        if ($ticket->status !== 'pending') {
            // หากสถานะเปลี่ยนไปแล้ว (เช่น แอดมินจ่ายงาน หรือช่างรับงานไปแล้ว) ให้ส่งแจ้งเตือนกลับ
            return back()->with('error', 'ไม่สามารถยกเลิกได้ เนื่องจากเรื่องนี้ได้รับการมอบหมายหรือดำเนินการไปแล้ว');
        }

        // 3. อัปเดตสถานะของตั๋วงานซ่อมในฐานข้อมูลให้กลายเป็น 'cancelled'
        $ticket->update(['status' => 'cancelled']);

        // 4. บันทึกข้อมูลประวัติการเปลี่ยนแปลงลงตาราง repair_logs สำหรับแสดงใน Timeline
        RepairLog::create([
            // รหัสอ้างอิงตั๋วแจ้งซ่อม
            'repair_request_id' => $ticket->id,
            // รหัสผู้ใช้ที่เป็นคนกดยกเลิก
            'user_id' => Auth::id(),
            // สถานะก่อนหน้าคือ 'pending'
            'status_from' => 'pending',
            // สถานะใหม่คือ 'cancelled'
            'status_to' => 'cancelled',
            // บันทึกคำอธิบายเหตุการณ์
            'comment' => 'ผู้ใช้งานขอยกเลิกคำขอแจ้งซ่อม',
        ]);

        // ส่งผู้ใช้กลับไปยังหน้าเดิม พร้อมแสดง Flash Message แจ้งความสำเร็จ
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
        // 1. ตรวจสอบสิทธิ์ว่าผู้ประเมินคือเจ้าของงานแจ้งซ่อมนี้จริงหรือไม่
        if ($ticket->user_id !== Auth::id()) {
            // หากไม่ใช่เจ้าของ ส่ง 403 Forbidden ทันที
            abort(403, 'คุณไม่มีสิทธิ์ประเมินงานนี้');
        }

        // 2. ตรวจสอบว่าช่างได้ดำเนินการซ่อมเสร็จสิ้น (สถานะ completed) แล้วหรือยัง
        if ($ticket->status !== 'completed') {
            // หากยังซ่อมไม่เสร็จ ห้ามประเมิน ให้ส่งข้อความ error กลับไป
            return back()->with('error', 'สามารถประเมินได้เฉพาะงานที่สถานะ "ซ่อมเสร็จสิ้น" เท่านั้น');
        }

        // 3. ป้องกันการกดประเมินซ้ำ โดยเช็คว่างานนี้เคยมีข้อมูลในตาราง reviews แล้วหรือยัง
        if ($ticket->review()->exists()) {
            // หากมีแล้ว ให้ส่งข้อความ error แจ้งว่าประเมินไปแล้ว
            return back()->with('error', 'งานซ่อมนี้ได้รับการประเมินความพึงพอใจไปแล้ว');
        }

        // 4. ตรวจสอบความถูกต้องของข้อมูลที่ส่งมาจากการประเมิน
        $validated = $request->validate([
            // คะแนนดาว ต้องมีค่า เป็นจำนวนเต็ม และต้องอยู่ระหว่าง 1 ถึง 5 ดาว
            'rating' => ['required', 'integer', 'between:1,5'],
            // ข้อคิดเห็นเพิ่มเติม อนุญาตให้ว่างได้ แต่ถ้ามีต้องไม่เกิน 1,000 ตัวอักษร
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            // ข้อความแจ้งเตือนภาษาไทยเมื่อไม่ได้ระบุคะแนนดาว
            'rating.required' => 'กรุณาให้คะแนนความพึงพอใจ 1-5 ดาว',
            // ข้อความแจ้งเตือนภาษาไทยเมื่อคะแนนไม่อยู่ในช่วง 1-5
            'rating.between' => 'คะแนนต้องอยู่ระหว่าง 1 ถึง 5 ดาว',
        ]);

        // 5. บันทึกข้อมูลการประเมินลงในตาราง reviews ด้วย Eloquent create()
        Review::create([
            // รหัสอ้างอิงตั๋วแจ้งซ่อม
            'repair_request_id' => $ticket->id,
            // รหัสผู้ใช้ที่เป็นคนประเมิน
            'user_id' => Auth::id(),
            // บันทึกคะแนนดาวที่ได้รับ
            'rating' => $validated['rating'],
            // บันทึกข้อคิดเห็น (หากไม่มีให้เป็น null)
            'comment' => $validated['comment'] ?? null,
        ]);

        // 6. อัปเดตสถานะของ Ticket ให้เป็น 'closed' เพื่อเป็นอันสิ้นสุดกระบวนการทำงานอย่างสมบูรณ์
        $ticket->update(['status' => 'closed']);

        // 7. บันทึกประวัติ Timeline การปิดงานลงตาราง repair_logs
        RepairLog::create([
            // รหัสอ้างอิงตั๋วแจ้งซ่อม
            'repair_request_id' => $ticket->id,
            // รหัสผู้ใช้ที่ทำการปิดงาน
            'user_id' => Auth::id(),
            // สถานะก่อนหน้าคือ 'completed'
            'status_from' => 'completed',
            // สถานะใหม่คือ 'closed'
            'status_to' => 'closed',
            // บันทึกข้อความพร้อมระบุคะแนนดาวที่ได้รับ
            'comment' => "ผู้ใช้ตรวจรับงานและประเมินความพึงพอใจ: {$validated['rating']} ดาว",
        ]);

        // ส่งผู้ใช้กลับหน้าเดิมพร้อม Flash Message ขอบคุณการประเมิน
        return back()->with('success', 'บันทึกการประเมินความพึงพอใจเรียบร้อยแล้ว ขอบคุณสำหรับความคิดเห็นครับ');
    }
}
