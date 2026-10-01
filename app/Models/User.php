<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Class User
 * โมเดลสำหรับจัดการข้อมูลผู้ใช้งานระบบ (ตาราง `users`)
 * รองรับ 3 บทบาทหลัก (Roles):
 * 1. 'admin' -> ผู้ดูแลระบบ
 * 2. 'technician' -> ช่างซ่อมบำรุง
 * 3. 'user' -> พนักงาน / ผู้ใช้งานทั่วไป
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * รายการฟิลด์ที่อนุญาตให้บันทึกข้อมูลแบบ Mass Assignment
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',       // ชื่อ-นามสกุล
        'email',      // อีเมล (ใช้สำหรับ Login และต้องไม่ซ้ำกัน)
        'password',   // รหัสผ่าน (เข้ารหัส Hash แล้ว)
        'role',       // บทบาท ('admin', 'technician', 'user')
        'phone',      // เบอร์โทรศัพท์ติดต่อ
        'department', // แผนก / สังกัด
    ];

    /**
     * ฟิลด์ที่ต้องซ่อนเมื่อแปลงข้อมูลเป็น Array หรือ JSON (เช่น Password)
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * กำหนดการแปลงชนิดข้อมูลอัตโนมัติ (Casts)
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // แฮชรหัสผ่านอัตโนมัติ
        ];
    }

    // ==========================================
    // Helper Methods สำหรับตรวจสอบ Role ในโค้ด
    // ==========================================

    /**
     * ตรวจสอบว่าผู้ใช้คนนี้เป็น Admin หรือไม่
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * ตรวจสอบว่าผู้ใช้คนนี้เป็น ช่างซ่อม หรือไม่
     */
    public function isTechnician(): bool
    {
        return $this->role === 'technician';
    }

    /**
     * ตรวจสอบว่าผู้ใช้คนนี้เป็น ผู้ใช้งานทั่วไป หรือไม่
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    // ==========================================
    // ความสัมพันธ์ระหว่างตาราง (Relationships)
    // ==========================================

    /**
     * รายการแจ้งซ่อมที่ผู้ใช้คนนี้เป็นผู้แจ้ง (One-to-Many)
     */
    public function repairRequests()
    {
        return $this->hasMany(RepairRequest::class, 'user_id');
    }

    /**
     * รายการงานซ่อมที่ได้รับมอบหมายให้ทำ (สำหรับ Technician) (One-to-Many)
     */
    public function assignedTasks()
    {
        return $this->hasMany(RepairRequest::class, 'assigned_to');
    }

    /**
     * ประวัติการกดบันทึกหรืออัปเดต Timeline ในระบบ (One-to-Many)
     */
    public function repairLogs()
    {
        return $this->hasMany(RepairLog::class, 'user_id');
    }

    /**
     * ประวัติการให้คะแนนดาวรีวิว (One-to-Many)
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id');
    }
}
