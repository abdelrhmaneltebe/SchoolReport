<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'student_id');
    }

    public function gradeRecords()
    {
        return $this->hasMany(GradeRecord::class, 'student_id');
    }

    public function courseEnrollments()
    {
        return $this->hasMany(CourseEnrollment::class, 'student_id');
    }

    public function enrolledCourses()
    {
        return $this->belongsToMany(ClassSubjectAssignment::class, 'course_enrollments', 'student_id', 'class_subject_assignment_id')
            ->withPivot('enrollment_status', 'enrolled_at')
            ->withTimestamps();
    }

    public function subScores()
    {
        return $this->hasMany(StudentSubScore::class, 'student_id');
    }
}
