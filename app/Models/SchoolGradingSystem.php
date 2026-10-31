<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolGradingSystem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'cutoffs_json' => 'array',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id', 'school_id');
    }
}
