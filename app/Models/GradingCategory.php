<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradingCategory extends Model
{
    protected $fillable = [
        'school_id',
        'rule_id',
        'grading_period_id',
        'parent_category_id',
        'template_category_id',
        'category_name',
        'category_type',
        'weight',
        'max_score',
        'display_order'
    ];

    protected $casts = [
        'weight' => 'float',
        'max_score' => 'float',
        'display_order' => 'integer'
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(GradingStructureRule::class, 'rule_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AcademicGradingPeriod::class, 'grading_period_id');
    }

    public function parentCategory(): BelongsTo
    {
        return $this->belongsTo(GradingCategory::class, 'parent_category_id');
    }

    public function templateCategory(): BelongsTo
    {
        return $this->belongsTo(GradingTemplateCategory::class, 'template_category_id');
    }

    public function subCategories(): HasMany
    {
        return $this->hasMany(GradingCategory::class, 'parent_category_id')->orderBy('display_order');
    }

    public function subScores(): HasMany
    {
        return $this->hasMany(StudentSubScore::class, 'grading_category_id');
    }
}
