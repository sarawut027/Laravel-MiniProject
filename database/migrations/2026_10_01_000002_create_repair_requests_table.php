<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: ตาราง repair_requests
 * ตารางหลักสำหรับเก็บข้อมูลคำขอแจ้งซ่อมทั้งหมดในระบบ
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('repair_requests', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->string('ticket_number')->unique(); // รหัสอ้างอิงคำขอ ไม่ซ้ำกัน เช่น REP-202610-0001
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // ผู้แจ้งเรื่อง (FK users)
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete(); // หมวดหมู่งานซ่อม (FK categories)
            $table->string('title'); // หัวข้อปัญหา / ชื่ออุปกรณ์ที่ชำรุด
            $table->string('location'); // สถานที่ / อาคาร / ชั้น / ห้อง
            $table->enum('urgency', ['low', 'normal', 'high', 'urgent'])->default('normal'); // ระดับความเร่งด่วน
            $table->text('description'); // รายละเอียดอาการเสีย
            $table->enum('status', [ // สถานะงาน 7 ขั้นตอน
                'pending',       // รอดำเนินการ
                'assigned',      // มอบหมายช่างแล้ว
                'in_progress',   // กำลังดำเนินการ
                'waiting_parts', // รออะไหล่
                'completed',     // ซ่อมเสร็จสิ้น
                'closed',        // ปิดงานเรียบร้อย
                'cancelled'      // ยกเลิกคำขอ
            ])->default('pending');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // ช่างที่รับผิดชอบ (FK users)
            $table->dateTime('assigned_at')->nullable(); // วันเวลาที่มอบหมายงาน
            $table->dateTime('completed_at')->nullable(); // วันเวลาที่ช่างซ่อมเสร็จ
            $table->text('technician_notes')->nullable(); // บันทึกข้อความและสิ่งที่ซ่อมจากช่าง
            $table->decimal('repair_cost', 10, 2)->default(0); // ค่าใช้จ่าย / ค่าอะไหล่ (บาท)
            $table->string('image_before')->nullable(); // Path ไฟล์รูปภาพก่อนซ่อม
            $table->string('image_after')->nullable(); // Path ไฟล์รูปภาพหลังซ่อมเสร็จ
            $table->timestamps(); // created_at และ updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_requests');
    }
};
