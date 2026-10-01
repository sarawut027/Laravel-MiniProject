<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_request_id',
        'user_id',
        'status_from',
        'status_to',
        'comment',
    ];

    public function ticket()
    {
        return $this->belongsTo(RepairRequest::class, 'repair_request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
