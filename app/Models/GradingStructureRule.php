<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradingStructureRule extends Model
{
    protected $fillable = [
        'school_id',
        'rule_name',
        'is_template',
        'template_key',
        'description',
        'scope_type',
        'course_id',
        'division_id',
        'grade_id',
        'scenario_depth',
        'pass_limit',
        'total_grade',
        'credit_hours',
        'is_active'
    ];

    protected $casts = [
        'is_template' => 'boolean',
        'pass_limit' => 'float',
        'total_grade' => 'float',
        'credit_hours' => 'float',
        'is_active' => 'boolean'
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id', 'school_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(MasterSubject::class, 'course_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(SchoolDivision::class, 'division_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(SchoolGradeLevel::class, 'grade_id');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(AcademicGradingPeriod::class, 'rule_id')
            ->whereNull('parent_period_id')
            ->with(['subPeriods', 'categories.subCategories'])
            ->orderBy('display_order');
    }

    public function allPeriods(): HasMany
    {
        return $this->hasMany(AcademicGradingPeriod::class, 'rule_id')
            ->orderBy('display_order');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(GradingCategory::class, 'rule_id')->orderBy('display_order');
    }

    public function mainCategories(): HasMany
    {
        return $this->hasMany(GradingCategory::class, 'rule_id')
            ->whereNull('parent_category_id')
            ->with('subCategories')
            ->orderBy('display_order');
    }

    public function templateCategories(): HasMany
    {
        return $this->hasMany(GradingTemplateCategory::class, 'rule_id')->orderBy('display_order');
    }

    public function assignedSubjects(): HasMany
    {
        return $this->hasMany(MasterSubject::class, 'grading_rule_id');
    }
}
