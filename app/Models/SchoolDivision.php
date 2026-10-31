<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolDivision extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id', 'school_id');
    }

    public function gradeLevels()
    {
        return $this->hasMany(SchoolGradeLevel::class, 'division_id')->orderBy('display_order');
    }
}
