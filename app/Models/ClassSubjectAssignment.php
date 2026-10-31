<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSubjectAssignment extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(MasterSubject::class, 'subject_id');
    }

    public function gradeRecords()
    {
        return $this->hasMany(GradeRecord::class, 'class_subject_assignment_id');
    }

    public function gradingRule()
    {
        return $this->belongsTo(GradingStructureRule::class, 'grading_rule_id');
    }

    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class, 'class_subject_assignment_id');
    }

    public function courseEnrollments()
    {
        return $this->hasMany(CourseEnrollment::class, 'class_subject_assignment_id');
    }

    public function enrolledStudents()
    {
        return $this->belongsToMany(Student::class, 'course_enrollments', 'class_subject_assignment_id', 'student_id')
            ->withPivot('enrollment_status', 'enrolled_at')
            ->withTimestamps();
    }

    /**
     * Get the effective students roster for this course-section.
     * If specific enrollments exist, return those enrolled students.
     * Otherwise fallback to all students in the class.
     */
    public function getEffectiveStudentsAttribute()
    {
        if ($this->relationLoaded('enrolledStudents') && $this->enrolledStudents->isNotEmpty()) {
            return $this->enrolledStudents->where('pivot.enrollment_status', 'ENROLLED');
        }

        $enrolled = $this->enrolledStudents()->wherePivot('enrollment_status', 'ENROLLED')->orderBy('admission_number')->get();
        if ($enrolled->isNotEmpty()) {
            return $enrolled;
        }

        return $this->schoolClass ? $this->schoolClass->students()->orderBy('admission_number')->get() : collect();
    }
}
