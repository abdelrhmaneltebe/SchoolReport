<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicGradingPeriod extends Model
{
    protected $fillable = [
        'school_id',
        'rule_id',
        'parent_period_id',
        'period_name',
        'period_type',
        'weight',
        'start_date',
        'end_date',
        'display_order'
    ];

    protected $casts = [
        'weight' => 'float',
        'start_date' => 'date',
        'end_date' => 'date',
        'display_order' => 'integer'
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id', 'school_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(GradingStructureRule::class, 'rule_id');
    }

    public function parentPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicGradingPeriod::class, 'parent_period_id');
    }

    public function subPeriods(): HasMany
    {
        return $this->hasMany(AcademicGradingPeriod::class, 'parent_period_id')
            ->with('categories.subCategories')
            ->orderBy('display_order');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(GradingCategory::class, 'grading_period_id')
            ->whereNull('parent_category_id')
            ->with('subCategories')
            ->orderBy('display_order');
    }
}
