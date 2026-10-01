<?php

namespace App\Http\Controllers;

use App\Models\RepairLog;
use App\Models\RepairRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TechnicianController extends Controller
{
    public function index(Request $request)
    {
        $query = RepairRequest::where('assigned_to', Auth::id())
            ->with(['category', 'user']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tasks = $query->latest()->paginate(10)->withQueryString();

        return view('technician.tasks', compact('tasks'));
    }

    public function updateStatus(Request $request, RepairRequest $ticket)
    {
        // Only assigned technician or admin can update status
        if ($ticket->assigned_to !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'คุณไม่ได้รับมอบหมายให้ดูแลงานซ่อมนี้');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:in_progress,waiting_parts,completed'],
            'technician_notes' => ['nullable', 'string'],
            'repair_cost' => ['nullable', 'numeric', 'min:0'],
            'image_after' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
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

        $updateData = [
            'status' => $statusTo,
            'technician_notes' => $validated['technician_notes'] ?? $ticket->technician_notes,
            'repair_cost' => (isset($validated['repair_cost']) && $validated['repair_cost'] !== null) 
                ? $validated['repair_cost'] 
                : $ticket->repair_cost,
        ];

        if ($request->hasFile('image_after')) {
            $updateData['image_after'] = $request->file('image_after')->store('repairs', 'public');
        }

        if ($statusTo === 'completed') {
            $updateData['completed_at'] = now();
        }

        $ticket->update($updateData);

        // Build friendly log comment
        $comment = !empty($validated['technician_notes']) 
            ? "ช่างบันทึก: {$validated['technician_notes']}"
            : "ช่างอัปเดตสถานะเป็น {$ticket->status_label}";

        if (isset($validated['repair_cost']) && $validated['repair_cost'] > 0) {
            $comment .= " (ระบุค่าใช้จ่าย: " . number_format($validated['repair_cost'], 2) . " บาท)";
        }

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
