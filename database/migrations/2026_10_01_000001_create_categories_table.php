<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: ตาราง categories
 * จัดการหมวดหมู่อุปกรณ์และประเภทงานซ่อมบำรุงในองค์กร
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ชื่อหมวดหมู่ เช่น ระบบไฟฟ้า, ระบบประปา, แอร์
            $table->string('icon')->nullable(); // สัญลักษณ์ไอคอน
            $table->text('description')->nullable(); // คำอธิบายรายละเอียดอุปกรณ์ที่ครอบคลุม
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
