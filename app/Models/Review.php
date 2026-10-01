<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Review
 * โมเดลสำหรับจัดการตาราง `reviews` (การประเมินความพึงพอใจการให้บริการ)
 * เก็บข้อมูล:
 * - คะแนนดาว 1 - 5 ดาว (rating)
 * - ข้อเสนอแนะและความคิดเห็นจากผู้ใช้งาน (comment)
 * - เชื่อมโยงกับ Ticket และ User ผู้ประเมิน
 */
class Review extends Model
{
    use HasFactory;

    /**
     * ฟิลด์ที่อนุญาตให้บันทึกข้อมูลแบบ Mass Assignment
     *
     * @var array<string>
     */
    protected $fillable = [
        'repair_request_id', // รหัสอ้างอิงใบแจ้งซ่อม
        'user_id',           // รหัสผู้ใช้ที่เป็นคนให้คะแนน
        'rating',            // คะแนนดาว (1 - 5)
        'comment',           // ข้อคิดเห็นเพิ่มเติม
    ];

    /**
     * แปลงชนิดข้อมูลอัตโนมัติ
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    /**
     * ความสัมพันธ์: รีวิวนี้เป็นของ Ticket ใบใด (Many-to-One)
     */
    public function ticket()
    {
        return $this->belongsTo(RepairRequest::class, 'repair_request_id');
    }

    /**
     * ความสัมพันธ์: ผู้ใช้งานที่เป็นคนประเมิน (Many-to-One)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
