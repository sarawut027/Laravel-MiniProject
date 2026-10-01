<?php

namespace App\Http\Controllers;
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class RepairRequest
 * โมเดลหลักสำหรับจัดการตาราง `repair_requests` (คำขอแจ้งซ่อม)
 * เก็บข้อมูล:
 * - รหัสคำขอ (ticket_number)
 * - ผู้แจ้ง (user_id), หมวดหมู่ (category_id), ช่างที่ดูแล (assigned_to)
 * - หัวข้อ, สถานที่, อาการ, ความเร่งด่วน, สถานะ
 * - รูปภาพก่อนซ่อม (image_before) และหลังซ่อม (image_after)
 * - บันทึกจากช่าง (technician_notes) และค่าใช้จ่าย (repair_cost)
 */
class RepairRequest extends Model
{
    use HasFactory;

    /**
     * ฟิลด์ที่อนุญาตให้บันทึกข้อมูลแบบ Mass Assignment ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'ticket_number',
        'user_id',
        'category_id',
        'title',
        'location',
        'urgency',
        'description',
        'status',
        'assigned_to',
        'assigned_at',
        'completed_at',
        'technician_notes',
        'repair_cost',
        'image_before',
        'image_after',
    ];

    /**
     * แปลงชนิดข้อมูล (Type Casting) อัตโนมัติเมื่อดึงจากฐานข้อมูล
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
            'repair_cost' => 'decimal:2',
        ];
    }

    // ==========================================
    // ความสัมพันธ์ระหว่างตาราง (Relationships)
    // ==========================================

    /**
     * ความสัมพันธ์: คำขอนี้สร้างโดยผู้ใช้งานคนใด (Many-to-One)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * ความสัมพันธ์: คำขอนี้อยู่ในหมวดหมู่งานซ่อมใด (Many-to-One)
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * ความสัมพันธ์: ช่างซ่อมที่ได้รับมอบหมายงานนี้ (Many-to-One)
     */
    public function technician()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * ความสัมพันธ์: ประวัติการบันทึก Timeline ของคำขอนี้ (One-to-Many)
     * เรียงตามลำดับเวลาจากเก่าไปใหม่ (oldest)
     */
    public function logs()
    {
        return $this->hasMany(RepairLog::class, 'repair_request_id')->oldest();
    }

    /**
     * ความสัมพันธ์: คะแนนประเมินความพึงพอใจ (One-to-One)
     */
    public function review()
    {
        return $this->hasOne(Review::class, 'repair_request_id');
    }

    // ==========================================
    // ฟังก์ชันเสริมสำหรับแสดงผลใน Blade (Accessors)
    // ==========================================

    /**
     * แปลงรหัสสถานะภาษาอังกฤษให้เป็นข้อความภาษาไทยสวยงาม
     * เรียกใช้ใน Blade: $ticket->status_label
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'รอดำเนินการ',
            'assigned' => 'มอบหมายช่างแล้ว',
            'in_progress' => 'กำลังดำเนินการ',
            'waiting_parts' => 'รออะไหล่',
            'completed' => 'ซ่อมเสร็จสิ้น',
            'closed' => 'ปิดงานเรียบร้อย',
            'cancelled' => 'ยกเลิกคำขอ',
            default => $this->status,
        };
    }

    /**
     * แปลงระดับความเร่งด่วนภาษาอังกฤษให้เป็นข้อความภาษาไทย
     * เรียกใช้ใน Blade: $ticket->urgency_label
     */
    public function getUrgencyLabelAttribute(): string
    {
        return match ($this->urgency) {
            'low' => 'ไม่เร่งด่วน',
            'normal' => 'ปกติ',
            'high' => 'ด่วน',
            'urgent' => 'ด่วนที่สุด',
            default => $this->urgency,
        };
    }

    /**
     * คืนค่า CSS Class ของ Tailwind สำหรับ Badge สถานะงาน
     * เรียกใช้ใน Blade: $ticket->status_badge_class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
            'assigned' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
            'in_progress' => 'bg-blue-100 text-blue-800 border-blue-300',
            'waiting_parts' => 'bg-orange-100 text-orange-800 border-orange-300',
            'completed' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'closed' => 'bg-green-100 text-green-800 border-green-300',
            'cancelled' => 'bg-rose-100 text-rose-800 border-rose-300',
            default => 'bg-gray-100 text-gray-800 border-gray-300',
        };
    }

    /**
     * คืนค่า CSS Class ของ Tailwind สำหรับ Badge ระดับความเร่งด่วน
     * เรียกใช้ใน Blade: $ticket->urgency_badge_class
     */
    public function getUrgencyBadgeClassAttribute(): string
    {
        return match ($this->urgency) {
            'low' => 'bg-slate-100 text-slate-700',
            'normal' => 'bg-blue-100 text-blue-700',
            'high' => 'bg-amber-100 text-amber-700',
            'urgent' => 'bg-rose-100 text-rose-700 font-bold',
            default => 'bg-gray-100 text-gray-700',
        };
    }
}
