<?php

namespace App\Http\Controllers;

use App\Models\RepairLog;
use App\Models\RepairRequest;
use Illuminate\Http\Request;
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
        // ค้นหาเฉพาะงานที่มอบหมายให้ช่างคนนี้ (assigned_to = Auth::id())
        // ดึงความสัมพันธ์ category และ user (ผู้แจ้ง) มาพร้อมกัน
        $query = RepairRequest::where('assigned_to', Auth::id())
            ->with(['category', 'user']);

        // กรองตามสถานะงานหากมีการเลือก Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // เรียงลำดับจากล่าสุด และแบ่งหน้าละ 10 รายการ
        $tasks = $query->latest()->paginate(10)->withQueryString();

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
        // 1. ตรวจสอบสิทธิ์: ต้องเป็นช่างที่ได้รับมอบหมายงานนี้ หรือเป็น Admin เท่านั้น
        if ($ticket->assigned_to !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'คุณไม่ได้รับมอบหมายให้ดูแลงานซ่อมนี้');
        }

        // 2. ตรวจสอบความถูกต้องของข้อมูลที่ส่งมา (Validation)
        $validated = $request->validate([
            'status' => ['required', 'in:in_progress,waiting_parts,completed'],
            'technician_notes' => ['nullable', 'string'],
            'repair_cost' => ['nullable', 'numeric', 'min:0'],
            'image_after' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'], // ไม่เกิน 5MB
        ], [
            'status.required' => 'กรุณาเลือกสถานะงาน',
            'status.in' => 'สถานะงานไม่ถูกต้อง',
            'repair_cost.numeric' => 'ค่าใช้จ่ายต้องเป็นตัวเลข',
            'repair_cost.min' => 'ค่าใช้จ่ายต้องไม่ติดลบ',
            'image_after.image' => 'ไฟล์ต้องเป็นรูปภาพเท่านั้น',
            'image_after.max' => 'ขนาดรูปภาพต้องไม่เกิน 5MB',
        ]);

        $statusFrom = $ticket->status;
        $statusTo = $validated['status'];

        // 3. เตรียมข้อมูลสำหรับอัปเดต
        $updateData = [
            'status' => $statusTo,
            'technician_notes' => $validated['technician_notes'] ?? $ticket->technician_notes,
            'repair_cost' => (isset($validated['repair_cost']) && $validated['repair_cost'] !== null) 
                ? $validated['repair_cost'] 
                : $ticket->repair_cost,
        ];

        // 4. จัดการอัปโหลดรูปภาพหลังซ่อมเสร็จ (Image After)
        if ($request->hasFile('image_after')) {
            $updateData['image_after'] = $request->file('image_after')->store('repairs', 'public');
        }

        // 5. หากเปลี่ยนเป็นสถานะ 'completed' ให้บันทึกเวลาที่ซ่อมเสร็จสิ้น (completed_at)
        if ($statusTo === 'completed') {
            $updateData['completed_at'] = now();
        }

        // ทำการบันทึกลงฐานข้อมูล
        $ticket->update($updateData);

        // 6. สร้างข้อความบันทึก Log Timeline ให้อ่านง่าย
        $comment = !empty($validated['technician_notes']) 
            ? "ช่างบันทึก: {$validated['technician_notes']}"
            : "ช่างอัปเดตสถานะเป็น {$ticket->status_label}";

        if (isset($validated['repair_cost']) && $validated['repair_cost'] > 0) {
            $comment .= " (ระบุค่าใช้จ่าย: " . number_format($validated['repair_cost'], 2) . " บาท)";
        }

        // บันทึกประวัติ Timeline การทำงานของช่าง
        RepairLog::create([
            'repair_request_id' => $ticket->id,
            'user_id' => Auth::id(),
            'status_from' => $statusFrom,
            'status_to' => $statusTo,
            'comment' => $comment,
        ]);

        return back()->with('success', "อัปเดตสถานะงานเป็น \"{$ticket->status_label}\" สำเร็จเรียบร้อยแล้ว");
    }
}
