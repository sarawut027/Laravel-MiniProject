<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: ตาราง reviews
 * บันทึกคะแนนประเมินความพึงพอใจการซ่อม (1-5 ดาว) และข้อคิดเห็นจากผู้ใช้งาน
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_request_id')->unique()->constrained('repair_requests')->cascadeOnDelete(); // 1 Ticket รีวิวได้ 1 ครั้ง (unique)
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // ผู้ให้คะแนน
            $table->unsignedTinyInteger('rating'); // คะแนนดาว (1 - 5)
            $table->text('comment')->nullable(); // ข้อคิดเห็นหรือข้อเสนอแนะเพิ่มเติม
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
