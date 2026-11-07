<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'price' => 'decimal:2',
            'max_students' => 'integer',
            'max_classes' => 'integer',
            'max_teachers' => 'integer',
            'display_order' => 'integer',
        ];
    }

    /**
     * Canonical Catalog of all System Features that can be assigned to Plans.
     */
    public const AVAILABLE_FEATURES = [
        'homeroom_attendance' => [
            'key' => 'homeroom_attendance',
            'name' => 'Homeroom Daily Attendance',
            'description' => 'Daily homeroom roll call register, excuses, and presence records.',
            'category' => 'Attendance',
            'icon' => '📅',
        ],
        'slot_attendance' => [
            'key' => 'slot_attendance',
            'name' => 'Subject & Slot Attendance',
            'description' => 'Period-by-period timetable lesson attendance and subject cutting tracking.',
            'category' => 'Attendance',
            'icon' => '⏱️',
        ],
        'attendance_truancy_policy' => [
            'key' => 'attendance_truancy_policy',
            'name' => 'Chronic Truancy & Attendance Policies',
            'description' => 'Configurable attendance rate thresholds, tardy-to-absence conversion, and warnings.',
            'category' => 'Attendance',
            'icon' => '⚙️',
        ],
        'batch_gradebook' => [
            'key' => 'batch_gradebook',
            'name' => 'Batch Gradebook & Multi-Scale Grading',
            'description' => 'Hierarchical grade recording, percent/weight/points scale toggles, and live auto-save.',
            'category' => 'Academics',
            'icon' => '📝',
        ],
        'custom_grading_rules' => [
            'key' => 'custom_grading_rules',
            'name' => 'Custom Grading Templates & Overrides',
            'description' => 'School-wide, level-level, and subject-specific passing cutoffs and scale definitions.',
            'category' => 'Academics',
            'icon' => '🎓',
        ],
        'student_profiles' => [
            'key' => 'student_profiles',
            'name' => 'Student 360 Profiles',
            'description' => 'Comprehensive student profile with GPA, attendance stats, and full multi-term marksheet.',
            'category' => 'Students',
            'icon' => '👤',
        ],
        'bulk_excel_ingestion' => [
            'key' => 'bulk_excel_ingestion',
            'name' => 'Excel & CSV Data Ingestion',
            'description' => 'Bulk student enrollment spreadsheets and marksheet spreadsheet batch uploading.',
            'category' => 'Data Management',
            'icon' => '📥',
        ],
        'jasper_report_cards' => [
            'key' => 'jasper_report_cards',
            'name' => 'JasperReports Official Report Cards',
            'description' => 'Custom-designed JasperReports engine with instant A4 PDF export and layout preview.',
            'category' => 'Reporting',
            'icon' => '📄',
        ],
        'broadsheet_reports' => [
            'key' => 'broadsheet_reports',
            'name' => 'Master Broadsheet & Class Summaries',
            'description' => 'Whole-class broadsheet matrix view with rankings, class averages, and term analytics.',
            'category' => 'Reporting',
            'icon' => '📊',
        ],
        'custom_branding' => [
            'key' => 'custom_branding',
            'name' => 'School Branding & Custom Logo',
            'description' => 'Custom school logo watermark, institutional headers, and branding on report cards.',
            'category' => 'Branding',
            'icon' => '🖼️',
        ],
        'report_studio_customizer' => [
            'key' => 'report_studio_customizer',
            'name' => 'Report Studio & Template Customizer',
            'description' => 'Design, compile JRXML packages, manage subreports, and deploy custom templates.',
            'category' => 'Reporting',
            'icon' => '🛠️',
        ],
    ];

    /**
     * Schools subscribed to this plan.
     */
    public function schools(): HasMany
    {
        return $this->hasMany(School::class, 'plan_id');
    }

    /**
     * Check if this plan includes a given feature.
     */
    public function hasFeature(string $featureKey): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $features = $this->features ?? [];
        return in_array($featureKey, $features, true) || in_array('*', $features, true);
    }

    /**
     * Get feature objects for the enabled features on this plan.
     */
    public function getEnabledFeaturesAttribute(): array
    {
        $enabled = [];
        $features = $this->features ?? [];

        foreach ($features as $key) {
            if (isset(self::AVAILABLE_FEATURES[$key])) {
                $enabled[$key] = self::AVAILABLE_FEATURES[$key];
            }
        }

        return $enabled;
    }

    /**
     * Scope for active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordering.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('price');
    }
}
