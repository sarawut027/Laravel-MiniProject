<?php

// กำหนด namespace ของคลาสให้อยู่ในกลุ่ม App\Http\Controllers ตามโครงสร้างมาตรฐานของ Laravel
namespace App\Http\Controllers;

// นำเข้าโมเดลหมวดหมู่ (Category Model) เพื่อใช้ดึงข้อมูลหมวดหมู่และนับจำนวนงานซ่อมในแต่ละหมวด
use App\Models\Category;
// นำเข้าโมเดลประวัติงานซ่อม (RepairLog Model) สำหรับบันทึกขั้นตอนการมอบหมายงานลง Timeline
use App\Models\RepairLog;
// นำเข้าโมเดลหลักของงานแจ้งซ่อม (RepairRequest Model) เพื่อค้นหา กรอง และอัปเดตข้อมูล
use App\Models\Review;
// นำเข้าโมเดลการประเมินความพึงพอใจ (Review Model) เพื่อคำนวณคะแนนดาวเฉลี่ย
use App\Models\RepairRequest;
// นำเข้าโมเดลผู้ใช้งาน (User Model) เพื่อดึงรายชื่อช่างซ่อมและตรวจสอบสิทธิ์
use App\Models\User;
// นำเข้าคลาส Request สำหรับรับส่งข้อมูลจากแบบฟอร์มและการค้นหา
use Illuminate\Http\Request;
// นำเข้า Auth Facade เพื่ออ้างอิงถึงข้อมูลผู้ดูแลระบบ (Admin) ที่กำลังล็อกอินอยู่
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
        // 1. คำนวณตัวเลขสถิติภาพรวมหลักของระบบ (KPI Cards)
        // นับจำนวนคำขอแจ้งซ่อมทั้งหมดที่มีในระบบ
        $total = RepairRequest::count();
        // นับจำนวนงานที่อยู่ในสถานะ 'pending' (งานใหม่ที่ยังรอดำเนินการและยังไม่มอบหมาย)
        $pending = RepairRequest::where('status', 'pending')->count();
        // นับจำนวนงานที่อยู่ระหว่างดำเนินการ โดยรวมสถานะ 'assigned', 'in_progress', 'waiting_parts'
        $inProgress = RepairRequest::whereIn('status', ['assigned', 'in_progress', 'waiting_parts'])->count();
        // นับจำนวนงานที่ปิดสำเร็จแล้ว โดยรวมสถานะ 'completed' (ซ่อมเสร็จ) และ 'closed' (ตรวจรับประเมินแล้ว)
        $completed = RepairRequest::whereIn('status', ['completed', 'closed'])->count();
        // คำนวณคะแนนดาวเฉลี่ยจากตาราง reviews หากไม่มีข้อมูลให้เป็น 0 และปัดเศษทศนิยม 1 ตำแหน่งด้วย round()
        $avgRating = round(Review::avg('rating') ?: 0, 1);

        // รวมค่าสถิติทั้งหมดเก็บลงในตัวแปร Array $stats เพื่อส่งต่อให้หน้า View
        $stats = [
            // จำนวนงานทั้งหมด
            'total' => $total,
            // จำนวนงานรอดำเนินการ
            'pending' => $pending,
            // จำนวนงานกำลังดำเนินการ
            'in_progress' => $inProgress,
            // จำนวนงานที่เสร็จสิ้น
            'completed' => $completed,
            // คะแนนเฉลี่ยความพึงพอใจ
            'average_rating' => $avgRating,
        ];

        // 2. ดึงรายการงานแจ้งซ่อมล่าสุด 5 รายการ
        // Eager Loading ความสัมพันธ์ category, user (ผู้แจ้ง), และ technician (ช่าง) เพื่อลด SQL Queries
        $recentTickets = RepairRequest::with(['category', 'user', 'technician'])
            // เรียงลำดับจากรายการที่สร้างล่าสุด
            ->latest()
            // ดึงมาเพียง 5 แถว
            ->take(5)
            // ประมวลผลดึงข้อมูลเป็น Collection
            ->get();

        // 3. จัดเตรียมชุดข้อมูลสำหรับเรนเดอร์กราฟสถิติ (Chart.js / SVG Charts)
        // ดึงหมวดหมู่ทั้งหมดพร้อมนับจำนวนความสัมพันธ์ของงานซ่อมด้วย withCount('tickets')
        $categories = Category::withCount('tickets')->get();
        
        // ประกอบข้อมูล Array $chartData สำหรับส่งไปพล็อตชาร์ตในหน้าบ้าน
        $chartData = [
            // รายชื่อหมวดหมู่ทั้งหมดสำหรับแกน Label ในกราฟ
            'category_labels' => $categories->pluck('name')->toArray(),
            // จำนวนงานซ่อมในแต่ละหมวดหมู่สำหรับแสดงเป็นแท่งกราฟหรือสัดส่วนวงกลม
            'category_counts' => $categories->pluck('tickets_count')->toArray(),
            // สถิติจำนวนงานแยกตามทุกสถานะของระบบ เพื่อสร้างกราฟวงกลมแสดงสถานะ (Status Breakdown)
            'status_counts' => [
                // จำนวนงานรอดำเนินการ
                'pending' => $pending,
                // จำนวนงานที่มอบหมายแล้ว
                'assigned' => RepairRequest::where('status', 'assigned')->count(),
                // จำนวนงานที่กำลังลงมือซ่อม
                'in_progress' => RepairRequest::where('status', 'in_progress')->count(),
                // จำนวนงานที่รอสั่งอะไหล่
                'waiting_parts' => RepairRequest::where('status', 'waiting_parts')->count(),
                // จำนวนงานที่ช่างซ่อมเสร็จสิ้น
                'completed' => RepairRequest::where('status', 'completed')->count(),
                // จำนวนงานที่ผู้ใช้ตรวจรับและปิดงานแล้ว
                'closed' => RepairRequest::where('status', 'closed')->count(),
                // จำนวนงานที่ถูกยกเลิก
                'cancelled' => RepairRequest::where('status', 'cancelled')->count(),
            ],
        ];

        // เรนเดอร์ไฟล์ View resources/views/admin/dashboard.blade.php พร้อมแนบตัวแปรทั้งสาม
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
        // เริ่มต้นสร้าง Query ดึงข้อมูลจากตาราง repair_requests
        // ทำ Eager Loading ตาราง category, user, และ technician ล่วงหน้าเพื่อประสิทธิภาพสูงสุด
        $query = RepairRequest::with(['category', 'user', 'technician']);

        // 1. ระบบค้นหาด้วยคำค้น (Search Keyword)
        if ($request->filled('search')) {
            // ดึงค่าข้อความที่พิมพ์ค้นหา
            $search = $request->search;
            // สร้างกลุ่มเงื่อนไขซ้อนกันในวงเล็บ WHERE (...)
            $query->where(function ($q) use ($search) {
                // ค้นหาเปรียบเทียบกับรหัสคำขอแจ้งซ่อม (เช่น REP-202610-0001)
                $q->where('ticket_number', 'like', "%{$search}%")
                  // หรือค้นหาจากหัวข้อเรื่อง
                  ->orWhere('title', 'like', "%{$search}%")
                  // หรือค้นหาจากสถานที่/ห้อง
                  ->orWhere('location', 'like', "%{$search}%")
                  // หรือค้นหาข้ามตารางไปยังชื่อของผู้แจ้งเรื่อง (whereHas Relation)
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // 2. ตัวกรองตามสถานะงาน (Filter by Status)
        if ($request->filled('status')) {
            // เพิ่มเงื่อนไข WHERE กรองตามสถานะ เช่น pending, assigned ฯลฯ
            $query->where('status', $request->status);
        }

        // 3. ตัวกรองตามหมวดหมู่งานซ่อม (Filter by Category)
        if ($request->filled('category_id')) {
            // เพิ่มเงื่อนไข WHERE กรองตาม category_id
            $query->where('category_id', $request->category_id);
        }

        // 4. ตัวกรองตามระดับความเร่งด่วน (Filter by Urgency)
        if ($request->filled('urgency')) {
            // เพิ่มเงื่อนไข WHERE กรองตามความเร่งด่วน เช่น high, urgent
            $query->where('urgency', $request->urgency);
        }

        // 5. ตัวกรองตามช่างผู้รับผิดชอบ (Filter by Assigned Technician)
        if ($request->filled('assigned_to')) {
            // เพิ่มเงื่อนไข WHERE กรองตาม id ช่างในฟิลด์ assigned_to
            $query->where('assigned_to', $request->assigned_to);
        }

        // สั่งประมวลผล Query โดยเรียงจากใหม่สุด (latest()) และแบ่งหน้าละ 15 รายการ (paginate(15))
        // รักษาพารามิเตอร์การค้นหาและการกรองไว้ใน Pagination Link ด้วย withQueryString()
        $tickets = $query->latest()->paginate(15)->withQueryString();
        
        // ดึงหมวดหมู่ทั้งหมดสำหรับนำไปสร้างตัวเลือก Select ในส่วน Filter
        $categories = Category::all();
        // ดึงเฉพาะผู้ใช้งานที่มีบทบาทเป็น 'technician' เพื่อนำไปสร้างตัวเลือก Select คัดกรองตามช่าง
        $technicians = User::where('role', 'technician')->get();

        // เรนเดอร์ไฟล์ View resources/views/admin/tickets.blade.php พร้อมส่งตัวแปร $tickets, $categories, $technicians
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
        // 1. ตรวจสอบความถูกต้องของข้อมูลการจ่ายงาน (Validation)
        $validated = $request->validate([
            // assigned_to ต้องมีค่า และต้องเป็น id ที่มีอยู่จริงในตาราง users
            'assigned_to' => ['required', 'exists:users,id'],
            // comment คำสั่งการเพิ่มเติม อนุญาตให้ว่างได้ แต่ถ้ามีต้องไม่เกิน 500 ตัวอักษร
            'comment' => ['nullable', 'string', 'max:500'],
        ], [
            // ข้อความเตือนเมื่อไม่ได้เลือกช่างซ่อม
            'assigned_to.required' => 'กรุณาเลือกช่างซ่อมที่ต้องการมอบหมาย',
            // ข้อความเตือนเมื่อไม่พบข้อมูล id ช่างในฐานข้อมูล
            'assigned_to.exists' => 'ไม่พบข้อมูลช่างซ่อมที่เลือก',
        ]);

        // 2. ตรวจสอบเชิงลึกว่าผู้ใช้ที่ถูกเลือกมีบทบาท (role) เป็น 'technician' จริงหรือไม่
        // ค้นหา User ตาม id ที่ระบุ หากไม่พบจะโยน ModelNotFoundException (404)
        $tech = User::findOrFail($validated['assigned_to']);
        // หากผู้ใช้รายนี้ไม่ได้มี role เป็น 'technician' (เช่น เผลอเลือก User ทั่วไป)
        if ($tech->role !== 'technician') {
            // ปฏิเสธการทำงานและส่งกลับพร้อมข้อความ Error
            return back()->with('error', 'ผู้ใช้ที่เลือกไม่ใช่ช่างซ่อม');
        }

        // บันทึกสถานะก่อนหน้าของงานซ่อมเก็บไว้ในตัวแปร $statusFrom เพื่อนำไปลง Timeline
        $statusFrom = $ticket->status;
        // หากสถานะเดิมคืองานใหม่ 'pending' ให้ปรับเปลี่ยนสถานะเป้าหมายเป็น 'assigned' ทันที
        // แต่หากงานนั้นอยู่ในสถานะอื่นอยู่แล้ว (เช่น ช่างกำลังทำ แล้วแอดมินเปลี่ยนตัวช่าง) ให้คงสถานะเดิมไว้
        $statusTo = ($ticket->status === 'pending') ? 'assigned' : $ticket->status;

        // 3. บันทึกข้อมูลการมอบหมายงานลงในตาราง repair_requests
        $ticket->update([
            // บันทึกรหัสช่างซ่อมผู้รับผิดชอบงาน
            'assigned_to' => $tech->id,
            // บันทึกเวลาที่มีการมอบหมายงาน (ใช้เวลาปัจจุบัน now())
            'assigned_at' => now(),
            // อัปเดตสถานะงานเป็นค่าใหม่ที่คำนวณได้
            'status' => $statusTo,
        ]);

        // 4. สร้างข้อความระบุประวัติการทำงานสำหรับบันทึกลงใน Timeline
        $logComment = "แอดมินมอบหมายงานให้ช่าง: {$tech->name}";
        // ตรวจสอบว่าแอดมินได้ระบุข้อความคำสั่งการเพิ่มเติมมาด้วยหรือไม่
        if (!empty($validated['comment'])) {
            // หากมีข้อความคำสั่ง ให้ต่อท้ายเข้าไปในประวัติ Timeline
            $logComment .= " (ข้อความสั่งการ: {$validated['comment']})";
        }

        // 5. บันทึกประวัติการเปลี่ยนแปลงลงตาราง repair_logs
        RepairLog::create([
            // รหัสอ้างอิงตั๋วงานซ่อม
            'repair_request_id' => $ticket->id,
            // รหัสของผู้กระทำการ ซึ่งก็คือแอดมินที่กำลังล็อกอินอยู่
            'user_id' => Auth::id(),
            // สถานะก่อนหน้า
            'status_from' => $statusFrom,
            // สถานะใหม่หลังมอบหมาย
            'status_to' => $statusTo,
            // ข้อความประวัติการทำงาน
            'comment' => $logComment,
        ]);

        // ส่ง Redirect กลับหน้าเดิมพร้อมแจ้งเตือน Flash Message ความสำเร็จว่ามอบหมายงานให้ช่างเรียบร้อย
        return back()->with('success', "มอบหมายงานให้ช่าง \"{$tech->name}\" เรียบร้อยแล้ว");
    }
}
