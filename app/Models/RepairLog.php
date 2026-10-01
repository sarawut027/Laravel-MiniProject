<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class RepairLog
 * โมเดลสำหรับจัดการตาราง `repair_logs` (ประวัติการดำเนินการ / Timeline Audit Logs)
 * ทำหน้าที่บันทึกทุกความเคลื่อนไหวของงานแจ้งซ่อม เช่น:
 * - การแจ้งซ่อมครั้งแรก
 * - การมอบหมายงานให้ช่าง
 * - ช่างเริ่มงาน หรือรออะไหล่
 * - ช่างซ่อมเสร็จสิ้น
 * - ผู้ใช้งานตรวจรับงาน และปิดงาน
 */
class RepairLog extends Model
{
    use HasFactory;

    /**
     * ฟิลด์ที่อนุญาตให้บันทึกข้อมูลแบบ Mass Assignment
     *
     * @var array<string>
     */
    protected $fillable = [
        'repair_request_id', // รหัสอ้างอิงใบแจ้งซ่อม
        'user_id',           // รหัสผู้ใช้ที่เป็นคนกระทำ (Admin, Tech, หรือ User)
        'status_from',       // สถานะก่อนหน้า (null หากเป็นสร้างใหม่)
        'status_to',         // สถานะใหม่หลังเปลี่ยน
        'comment',           // ข้อความอธิบายเหตุการณ์ เช่น "ช่างเข้าตรวจสอบ", "รอสั่งซื้ออะไหล่"
    ];

    /**
     * ความสัมพันธ์: ประวัตินี้เป็นของ Ticket ใบใด (Many-to-One)
     */
    public function ticket()
    {
        return $this->belongsTo(RepairRequest::class, 'repair_request_id');
    }

    /**
     * ความสัมพันธ์: ผู้ใช้งานที่เป็นคนสร้างประวัตินี้ขึ้นมา (Many-to-One)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
