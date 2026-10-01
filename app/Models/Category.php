<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Category
 * โมเดลสำหรับจัดการตาราง `categories` (หมวดหมู่งานซ่อมบำรุง)
 * เช่น:
 * - ระบบไฟฟ้าและแสงสว่าง
 * - ระบบประปาและสุขาภิบาล
 * - เครื่องปรับอากาศและความเย็น
 * - อุปกรณ์คอมพิวเตอร์และระบบเครือข่าย
 * - อาคาร สถานที่ และเฟอร์นิเจอร์
 */
class Category extends Model
{
    use HasFactory;

    /**
     * ฟิลด์ที่อนุญาตให้บันทึกข้อมูลแบบ Mass Assignment
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',        // ชื่อหมวดหมู่
        'icon',        // ไอคอนสัญลักษณ์
        'description', // คำอธิบายรายละเอียดอุปกรณ์ที่จัดอยู่ในหมวดนี้
    ];

    /**
     * ความสัมพันธ์: หมวดหมู่นี้มีรายการแจ้งซ่อมใดบ้าง (One-to-Many)
     */
    public function tickets()
    {
        return $this->hasMany(RepairRequest::class, 'category_id');
    }
}
