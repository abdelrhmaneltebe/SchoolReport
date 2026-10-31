<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradingTemplateCategory extends Model
{
    protected $fillable = [
        'school_id',
        'rule_id',
        'category_name',
        'category_type',
        'default_weight',
        'default_max_score',
        'sub_categories_json',
        'display_order'
    ];

    protected $casts = [
        'default_weight' => 'float',
        'default_max_score' => 'float',
        'sub_categories_json' => 'array',
        'display_order' => 'integer'
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(GradingStructureRule::class, 'rule_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id', 'school_id');
    }

    public function assignedInstances(): HasMany
    {
        return $this->hasMany(GradingCategory::class, 'template_category_id');
    }
}
