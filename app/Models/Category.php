<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'description',
    ];

    public function tickets()
    {
        return $this->hasMany(RepairRequest::class, 'category_id');
    }
}
