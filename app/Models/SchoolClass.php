<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function gradeLevel()
    {
        return $this->belongsTo(SchoolGradeLevel::class, 'grade_id');
    }

    public function subjectAssignments()
    {
        return $this->hasMany(ClassSubjectAssignment::class, 'class_id')->orderBy('display_order');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id', 'school_id');
    }
}
