<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: ตาราง repair_logs
 * เก็บประวัติ Timeline ความเคลื่อนไหว และการเปลี่ยนสถานะของงานแจ้งซ่อม
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('repair_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_request_id')->constrained('repair_requests')->cascadeOnDelete(); // อ้างอิงตาราง repair_requests
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // ผู้ดำเนินการบันทึก (Admin, ช่าง, หรือผู้ใช้)
            $table->string('status_from')->nullable(); // สถานะก่อนเปลี่ยน (null หากเป็นสร้างใหม่)
            $table->string('status_to'); // สถานะหลังเปลี่ยน
            $table->text('comment')->nullable(); // ข้อความรายละเอียดการกระทำ / หมายเหตุ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_logs');
    }
};
