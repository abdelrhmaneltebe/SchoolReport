<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolGradeLevel extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function division()
    {
        return $this->belongsTo(SchoolDivision::class, 'division_id');
    }

    public function classes()
    {
        return $this->hasMany(SchoolClass::class, 'grade_id')->orderBy('class_name');
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id', 'school_id');
    }

    public function students()
    {
        return $this->hasManyThrough(Student::class, SchoolClass::class, 'grade_id', 'class_id');
    }
}
