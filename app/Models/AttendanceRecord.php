<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function classSubjectAssignment()
    {
        return $this->belongsTo(ClassSubjectAssignment::class, 'class_subject_assignment_id');
    }
}
