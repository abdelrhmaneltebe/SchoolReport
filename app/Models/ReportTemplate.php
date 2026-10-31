<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportTemplate extends Model
{
    protected $table = 'report_templates';

    protected $fillable = [
        'school_id',
        'is_global',
        'title',
        'report_code',
        'category',
        'description',
        'base_type',
        'template_file',
        'subreports',
        'assets',
        'deployment_path',
        'is_compiled',
        'parameters',
        'default_format',
        'is_system',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_global' => 'boolean',
        'is_compiled' => 'boolean',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'parameters' => 'array',
        'subreports' => 'array',
        'assets' => 'array',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id', 'school_id');
    }

    /**
     * Check if a specific parameter is enabled for this report.
     */
    public function hasParameter(string $param): bool
    {
        return in_array($param, $this->parameters ?? []);
    }

    /**
     * Check if report is general (available across all schools).
     */
    public function isGeneral(): bool
    {
        return $this->is_global || empty($this->school_id);
    }

    /**
     * Absolute filesystem directory where this report package is deployed.
     */
    public function getDeploymentDirectory(): string
    {
        if ($this->deployment_path) {
            return storage_path('app' . DIRECTORY_SEPARATOR . $this->deployment_path);
        }

        if ($this->isGeneral()) {
            return storage_path('app' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . 'global' . DIRECTORY_SEPARATOR . $this->report_code);
        }

        return storage_path('app' . DIRECTORY_SEPARATOR . 'schools' . DIRECTORY_SEPARATOR . $this->school_id . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $this->report_code);
    }

    /**
     * Subreports directory.
     */
    public function getSubreportsDirectory(): string
    {
        return $this->getDeploymentDirectory() . DIRECTORY_SEPARATOR . 'subreports';
    }

    /**
     * Images and asset directory.
     */
    public function getAssetsDirectory(): string
    {
        return $this->getDeploymentDirectory() . DIRECTORY_SEPARATOR . 'images';
    }

    /**
     * Available parameter definitions across reports.
     */
    public static function availableParameters(): array
    {
        return [
            'level' => [
                'label' => 'School Level / Stage',
                'description' => 'Filter by educational stage or grade level',
                'icon' => '🏛️',
            ],
            'class' => [
                'label' => 'Class / Batch',
                'description' => 'Select class section or cohort',
                'icon' => '🏫',
            ],
            'student' => [
                'label' => 'Individual Student',
                'description' => 'Select a single student for personal report',
                'icon' => '🎓',
            ],
            'subject' => [
                'label' => 'Subject / Course',
                'description' => 'Filter by specific subject marksheet',
                'icon' => '📚',
            ],
            'period' => [
                'label' => 'Academic Period / Term',
                'description' => 'Filter by term, semester, or full academic year',
                'icon' => '📅',
            ],
            'pass_mark' => [
                'label' => 'Passing Mark Threshold',
                'description' => 'Custom passing score percentage for pass/fail calculation',
                'icon' => '⚖️',
            ],
            'format' => [
                'label' => 'Output Format Selection',
                'description' => 'Allow user to pick View, Print HTML, CSV Export, or Jasper PDF',
                'icon' => '📤',
            ],
        ];
    }
}
