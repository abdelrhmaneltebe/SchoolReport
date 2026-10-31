<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSubScore extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'class_subject_assignment_id',
        'grading_period_id',
        'grading_category_id',
        'raw_score',
        'max_score',
        'weighted_score'
    ];

    protected $casts = [
        'raw_score' => 'float',
        'max_score' => 'float',
        'weighted_score' => 'float'
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ClassSubjectAssignment::class, 'class_subject_assignment_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AcademicGradingPeriod::class, 'grading_period_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GradingCategory::class, 'grading_category_id');
    }
}
