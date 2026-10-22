<?php

// กำหนด namespace ของคลาสให้อยู่ในกลุ่ม App\Http\Controllers ตามโครงสร้างมาตรฐานของ Laravel
namespace App\Http\Controllers;

// นำเข้าโมเดลประวัติงานซ่อม (RepairLog Model) สำหรับบันทึกขั้นตอนการดำเนินการของช่างลง Timeline
use App\Models\RepairLog;
// นำเข้าโมเดลคำขอแจ้งซ่อม (RepairRequest Model) เพื่อค้นหาและอัปเดตสถานะงานซ่อม
use App\Models\RepairRequest;
// นำเข้าคลาส Request เพื่อใช้รับส่งข้อมูล HTTP Request
use Illuminate\Http\Request;
// นำเข้า Auth Facade เพื่อดึงข้อมูลและตรวจสอบสิทธิ์ของช่างที่กำลังล็อกอินอยู่
use Illuminate\Support\Facades\Auth;

/**
 * Class TechnicianController
 * คอนโทรลเลอร์สำหรับช่างซ่อม (Technician)
 * หน้าที่หลัก:
 * 1. แสดงรายการงานที่ได้รับมอบหมาย (My Tasks)
 * 2. อัปเดตสถานะงาน (กำลังซ่อม / รออะไหล่ / ซ่อมเสร็จสิ้น)
 * 3. บันทึกรายละเอียดการซ่อม อะไหล่ที่ใช้ ค่าใช้จ่าย และแนบรูปถ่ายหลังซ่อม (Before & After)
 */
class TechnicianController extends Controller
{
    /**
     * แสดงรายการงานซ่อมที่ได้รับมอบหมาย (My Tasks) ของช่างที่กำลังล็อกอิน
     * รองรับการกรองตามสถานะงาน เช่น assigned, in_progress, waiting_parts, completed
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // 1. ดึงเฉพาะงานที่ได้รับมอบหมายให้ช่างคนนี้ โดยเช็คเงื่อนไข assigned_to ตรงกับ Auth::id()
        // โหลดข้อมูลความสัมพันธ์ category (หมวดหมู่) และ user (ผู้แจ้งเรื่อง) ล่วงหน้าด้วย Eager Loading
        $query = RepairRequest::where('assigned_to', Auth::id())
            ->with(['category', 'user']);

        // 2. ตรวจสอบว่าช่างได้เลือกฟิลเตอร์สถานะงานมาหรือไม่
        if ($request->filled('status')) {
            // หากมีการเลือกสถานะ ให้กรองเฉพาะงานในสถานะนั้นๆ เช่น in_progress, waiting_parts
            $query->where('status', $request->status);
        }

        // 3. เรียงลำดับจากงานที่ได้รับล่าสุด (latest()) และแบ่งหน้าละ 10 รายการ (paginate(10))
        // พ่วง withQueryString() เพื่อรักษาเงื่อนไขตัวกรองไว้เมื่อคลิกเปลี่ยนหน้า
        $tasks = $query->latest()->paginate(10)->withQueryString();

        // เรนเดอร์ไฟล์ Blade View resources/views/technician/tasks.blade.php พร้อมส่งตัวแปร $tasks
        return view('technician.tasks', compact('tasks'));
    }

    /**
     * ฟังก์ชันอัปเดตสถานะงานซ่อม และปิดงาน
     * รองรับการแนบภาพถ่ายหลังซ่อม (image_after) และระบุค่าใช้จ่าย (repair_cost)
     *
     * @param Request $request
     * @param RepairRequest $ticket
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, RepairRequest $ticket)
    {
        // 1. ตรวจสอบสิทธิ์ความปลอดภัย (Authorization Check)
        // ตรวจสอบว่าช่างคนที่กดอัปเดต คือช่างที่ได้รับมอบหมายงานนี้จริงหรือไม่ หรือเป็น Admin
        if ($ticket->assigned_to !== Auth::id() && !Auth::user()->isAdmin()) {
            // หากไม่ใช่ผู้รับผิดชอบและไม่ใช่แอดมิน ให้ปฏิเสธและตอบกลับด้วย HTTP 403 Forbidden
            abort(403, 'คุณไม่ได้รับมอบหมายให้ดูแลงานซ่อมนี้');
        }

        // 2. ตรวจสอบความถูกต้องของข้อมูลที่ช่างส่งมา (Form Validation)
        $validated = $request->validate([
            // status ต้องไม่ว่าง และต้องเป็นค่าใดค่าหนึ่งใน: กำลังทำ, รออะไหล่, ซ่อมเสร็จ
            'status' => ['required', 'in:in_progress,waiting_parts,completed'],
            // technician_notes บันทึกของช่าง อนุญาตให้ว่างได้ เป็นข้อความ
            'technician_notes' => ['nullable', 'string'],
            // repair_cost ค่าใช้จ่าย อนุญาตให้ว่างได้ แต่ถ้ามีต้องเป็นตัวเลข และมีค่าไม่ต่ำกว่า 0
            'repair_cost' => ['nullable', 'numeric', 'min:0'],
            // image_after รูปถ่ายหลังซ่อมเสร็จ อนุญาตให้ว่างได้ แต่ถ้ามีต้องเป็นไฟล์รูป ไม่เกิน 5MB
            'image_after' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            // ข้อความแจ้งเตือนภาษาไทยเมื่อไม่ได้เลือกสถานะ
            'status.required' => 'กรุณาเลือกสถานะงาน',
            // ข้อความแจ้งเตือนภาษาไทยเมื่อสถานะไม่อยู่ในตัวเลือกที่กำหนด
            'status.in' => 'สถานะงานไม่ถูกต้อง',
            // ข้อความแจ้งเตือนภาษาไทยเมื่อค่าใช้จ่ายไม่ใช่ตัวเลข
            'repair_cost.numeric' => 'ค่าใช้จ่ายต้องเป็นตัวเลข',
            // ข้อความแจ้งเตือนภาษาไทยเมื่อค่าใช้จ่ายเป็นค่าติดลบ
            'repair_cost.min' => 'ค่าใช้จ่ายต้องไม่ติดลบ',
            // ข้อความแจ้งเตือนภาษาไทยเมื่อไฟล์ไม่ใช่รูปภาพ
            'image_after.image' => 'ไฟล์ต้องเป็นรูปภาพเท่านั้น',
            // ข้อความแจ้งเตือนภาษาไทยเมื่อรูปภาพมีขนาดใหญ่เกิน 5MB
            'image_after.max' => 'ขนาดรูปภาพต้องไม่เกิน 5MB',
        ]);

        // บันทึกสถานะเดิมก่อนการอัปเดตเก็บไว้ในตัวแปร $statusFrom
        $statusFrom = $ticket->status;
        // กำหนดสถานะใหม่ที่ต้องการเปลี่ยนเก็บไว้ในตัวแปร $statusTo
        $statusTo = $validated['status'];

        // 3. จัดเตรียมชุดข้อมูล Array สำหรับนำไปอัปเดตลงตาราง repair_requests
        $updateData = [
            // อัปเดตสถานะงานใหม่
            'status' => $statusTo,
            // อัปเดตบันทึกการซ่อมของช่าง หากไม่ได้กรอกใหม่ให้คงค่าเดิมไว้
            'technician_notes' => $validated['technician_notes'] ?? $ticket->technician_notes,
            // อัปเดตค่าใช้จ่ายในการซ่อม ตรวจสอบว่ามีการส่งค่ามาและไม่เป็นค่าว่างหรือไม่
            'repair_cost' => (isset($validated['repair_cost']) && $validated['repair_cost'] !== null) 
                // หากมีการระบุ ให้ใช้ค่าใหม่ที่ส่งมา
                ? $validated['repair_cost'] 
                // หากไม่ได้ระบุ ให้คงค่าเดิมไว้
                : $ticket->repair_cost,
        ];

        // 4. จัดการอัปโหลดไฟล์รูปภาพหลังซ่อมเสร็จ (Image After Repair)
        if ($request->hasFile('image_after')) {
            // บันทึกไฟล์รูปลง storage/app/public/repairs ในดิสก์ 'public'
            $updateData['image_after'] = $request->file('image_after')->store('repairs', 'public');
        }

        // 5. หากช่างเปลี่ยนสถานะเป็น 'completed' (ซ่อมเสร็จสิ้น)
        if ($statusTo === 'completed') {
            // ให้บันทึกเวลาที่ซ่อมเสร็จสิ้นด้วยเวลาปัจจุบัน (now())
            $updateData['completed_at'] = now();
        }

        // ทำการอัปเดตข้อมูลลงฐานข้อมูลตาราง repair_requests
        $ticket->update($updateData);

        // 6. จัดเตรียมข้อความอธิบายความคืบหน้าสำหรับบันทึกลงใน Timeline
        $comment = !empty($validated['technician_notes']) 
            // หากช่างกรอกบันทึกเพิ่มเติม ให้แสดงข้อความที่ช่างบันทึก
            ? "ช่างบันทึก: {$validated['technician_notes']}"
            // หากไม่ได้กรอก ให้แสดงข้อความสถานะภาษาไทยจาก Accessor status_label
            : "ช่างอัปเดตสถานะเป็น {$ticket->status_label}";

        // ตรวจสอบว่ามีการระบุค่าใช้จ่ายในการซ่อมมากกว่า 0 บาทหรือไม่
        if (isset($validated['repair_cost']) && $validated['repair_cost'] > 0) {
            // หากมี ให้พ่วงข้อมูลค่าใช้จ่ายและจัดฟอร์แมตตัวเลข 2 ตำแหน่งด้วย number_format
            $comment .= " (ระบุค่าใช้จ่าย: " . number_format($validated['repair_cost'], 2) . " บาท)";
        }

        // 7. บันทึกประวัติ Timeline การทำงานของช่างลงในตาราง repair_logs
        RepairLog::create([
            // รหัสอ้างอิงตั๋วงานซ่อม
            'repair_request_id' => $ticket->id,
            // รหัสช่างผู้ทำการอัปเดต
            'user_id' => Auth::id(),
            // สถานะก่อนหน้า
            'status_from' => $statusFrom,
            // สถานะใหม่
            'status_to' => $statusTo,
            // ข้อความอธิบายความคืบหน้า
            'comment' => $comment,
        ]);

        // ส่ง Redirect กลับหน้าเดิมพร้อม Flash Message แจ้งความสำเร็จ
        return back()->with('success', "อัปเดตสถานะงานเป็น \"{$ticket->status_label}\" สำเร็จเรียบร้อยแล้ว");
    }
}
