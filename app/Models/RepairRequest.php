<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairRequest extends Model
{
    use HasFactory;

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

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
            'repair_cost' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function logs()
    {
        return $this->hasMany(RepairLog::class, 'repair_request_id')->oldest();
    }

    public function review()
    {
        return $this->hasOne(Review::class, 'repair_request_id');
    }

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
