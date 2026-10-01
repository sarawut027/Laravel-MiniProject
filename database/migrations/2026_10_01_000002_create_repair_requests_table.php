<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('repair_requests', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('title');
            $table->string('location');
            $table->enum('urgency', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->text('description');
            $table->enum('status', [
                'pending',
                'assigned',
                'in_progress',
                'waiting_parts',
                'completed',
                'closed',
                'cancelled'
            ])->default('pending');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('technician_notes')->nullable();
            $table->decimal('repair_cost', 10, 2)->default(0);
            $table->string('image_before')->nullable();
            $table->string('image_after')->nullable();
            $table->timestamps();
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
