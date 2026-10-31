<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\School;
use App\Models\SchoolAttendancePolicy;
use App\Models\SchoolGradingSystem;
use App\Models\SchoolClass;
use App\Models\MasterSubject;
use App\Models\ClassSubjectAssignment;
use App\Models\GradingStructureRule;
use App\Models\AcademicGradingPeriod;
use App\Models\GradingCategory;
use App\Models\GradingTemplateCategory;
use App\Models\SchoolGradeLevel;
use App\Models\SchoolDivision;
use App\Models\Student;
use App\Models\GradeRecord;
use App\Models\StudentSubScore;
use App\Models\CourseEnrollment;

class SchoolConfigurationController extends Controller
{
    /**
     * Resolve school tenant based on authentication and session scope.
     */
    protected function resolveActiveSchool(Request $request): School
    {
        $schoolId = auth()->check() && !auth()->user()->isSuperAdmin()
            ? auth()->user()->school_id
            : ($request->get('school_id', session('active_school_id', 'SCH-GV-01')));

        $school = School::where('school_id', $schoolId)->first() ?? School::first();
        session(['active_school_id' => $school->school_id]);
        return $school;
    }

    /**
     * Display Attendance Policy configuration.
     */
    public function attendance(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);
        $policy = SchoolAttendancePolicy::firstOrCreate(
            ['school_id' => $activeSchool->school_id],
            ['tracking_mode' => 'DAILY_HOMEROOM', 'min_attendance_rate' => 85.00, 'chronic_truancy_days' => 3, 'tardies_per_absence' => 3]
        );

        return view('configuration.attendance', compact('activeSchool', 'policy'));
    }

    /**
     * Update Attendance Policy.
     */
    public function updateAttendance(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|string',
            'tracking_mode' => 'required|string',
            'min_attendance_rate' => 'required|numeric|min:0|max:100',
            'chronic_truancy_days' => 'required|integer|min:1',
            'tardies_per_absence' => 'required|integer|min:1'
        ]);

        SchoolAttendancePolicy::updateOrCreate(
            ['school_id' => $validated['school_id']],
            $validated
        );

        return redirect()->back()->with('success', 'School-wide attendance policy updated successfully.');
    }

    /**
     * Display Grading System & Overrides.
     */
    public function grading(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);
        $activeSchool->load(['gradingSystems', 'divisions']);

        $baseGrading = $activeSchool->gradingSystems->where('scope_type', 'SCHOOL_BASE')->first();

        // 1. Fetch System & Custom Templates with Complete Nested Hierarchy
        $templates = \App\Models\GradingStructureRule::where('school_id', $activeSchool->school_id)
            ->where('is_template', true)
            ->with([
                'periods.subPeriods.categories.subCategories',
                'periods.categories.subCategories',
                'templateCategories'
            ])
            ->get();

        $selectedTemplateId = (int) $request->get('template_id', $templates->firstWhere('is_active', true)->id ?? $templates->first()->id ?? 0);
        $activeTemplate = $templates->firstWhere('id', $selectedTemplateId) ?? $templates->first();

        // 2. Fetch Course Specific Overrides
        $courseRules = \App\Models\GradingStructureRule::where('school_id', $activeSchool->school_id)
            ->where('scope_type', 'COURSE')
            ->with('course')
            ->get();

        // 3. Calendar Periods for Date Schedule Tab
        $calendarPeriods = \App\Models\AcademicGradingPeriod::where('school_id', $activeSchool->school_id)
            ->where('rule_id', $activeTemplate->id ?? null)
            ->whereNull('parent_period_id')
            ->with('subPeriods')
            ->orderBy('display_order')
            ->get();

        // 4. Live Simulation
        $calculationService = app(\App\Services\GradingCalculationService::class);
        $simulation = $activeTemplate ? $calculationService->calculateMultiTierGrade([], $activeTemplate) : null;

        // 5. All Master Subjects for Subject-to-Grading Mapping
        $masterSubjects = MasterSubject::where('school_id', $activeSchool->school_id)
            ->with(['gradingRule', 'assignments.schoolClass'])
            ->orderBy('department')
            ->orderBy('subject_name')
            ->get();

        return view('configuration.grading', compact(
            'activeSchool',
            'baseGrading',
            'templates',
            'activeTemplate',
            'courseRules',
            'calendarPeriods',
            'simulation',
            'masterSubjects'
        ));
    }

    /**
     * Activate a template as the school's active grading standard.
     */
    public function activateTemplate(Request $request, $ruleId)
    {
        $rule = \App\Models\GradingStructureRule::findOrFail($ruleId);

        \App\Models\GradingStructureRule::where('school_id', $rule->school_id)
            ->where('scope_type', $rule->scope_type)
            ->update(['is_active' => false]);

        $rule->update(['is_active' => true]);

        return redirect()->route('config.grading', ['template_id' => $rule->id])
            ->with('success', "Activated '{$rule->rule_name}' as the active grading template!");
    }

    /**
     * Update names and weights of periods, sub-periods, categories, and sub-categories in-place.
     */
    public function updateGradingHierarchy(Request $request)
    {
        $validated = $request->validate([
            'rule_id' => 'required|exists:grading_structure_rules,id',
            'rule_name' => 'required|string|max:150',
            'pass_limit' => 'required|numeric|min:0',
            'total_grade' => 'required|numeric|min:1',
            'credit_hours' => 'required|numeric|min:0.5',
            'periods' => 'nullable|array',
            'categories' => 'nullable|array'
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
            $rule = \App\Models\GradingStructureRule::findOrFail($validated['rule_id']);
            $rule->update([
                'rule_name' => $validated['rule_name'],
                'pass_limit' => (float) $validated['pass_limit'],
                'total_grade' => (float) $validated['total_grade'],
                'credit_hours' => (float) $validated['credit_hours']
            ]);

            // Update Periods & Sub-Periods (Names and Weights)
            if (!empty($validated['periods'])) {
                foreach ($validated['periods'] as $periodId => $pData) {
                    \App\Models\AcademicGradingPeriod::where('id', $periodId)->update([
                        'period_name' => $pData['name'],
                        'weight' => (float) $pData['weight']
                    ]);
                }
            }

            // Update Categories & Sub-Categories (Names and Weights)
            if (!empty($validated['categories'])) {
                foreach ($validated['categories'] as $catId => $cData) {
                    \App\Models\GradingCategory::where('id', $catId)->update([
                        'category_name' => $cData['name'],
                        'weight' => (float) $cData['weight']
                    ]);
                }
            }
        });

        return redirect()->route('config.grading', ['template_id' => $validated['rule_id']])
            ->with('success', 'Grading template hierarchy, names, and weights updated successfully!');
    }

    /**
     * Add a Main Grading Period or Sub-Grading Period dynamically.
     */
    public function addPeriod(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $validated = $request->validate([
            'rule_id' => 'required|exists:grading_structure_rules,id',
            'parent_period_id' => 'nullable|exists:academic_grading_periods,id',
            'period_name' => 'required|string|max:100',
            'weight' => 'required|numeric|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        $rule = GradingStructureRule::where('school_id', $activeSchool->school_id)
            ->findOrFail($validated['rule_id']);

        $isSubPeriod = !empty($validated['parent_period_id']);

        $nextOrder = AcademicGradingPeriod::where('rule_id', $rule->id)
            ->when($isSubPeriod, function ($q) use ($validated) {
                return $q->where('parent_period_id', $validated['parent_period_id']);
            }, function ($q) {
                return $q->whereNull('parent_period_id');
            })
            ->max('display_order') + 1;

        $period = AcademicGradingPeriod::create([
            'school_id' => $activeSchool->school_id,
            'rule_id' => $rule->id,
            'parent_period_id' => $validated['parent_period_id'] ?? null,
            'period_name' => trim($validated['period_name']),
            'period_type' => $isSubPeriod ? 'SUB' : 'MAIN',
            'weight' => (float) $validated['weight'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'display_order' => $nextOrder
        ]);

        $this->syncRuleScenarioDepth($rule);

        $typeLabel = $isSubPeriod ? 'Sub-Grading Period' : 'Main Grading Period';

        return redirect()->route('config.grading', ['template_id' => $rule->id])
            ->with('success', "✓ Added {$typeLabel} '{$period->period_name}' ({$period->weight}%) successfully!");
    }

    /**
     * Delete a Grading Period (Main or Sub) with cascading removal.
     */
    public function deletePeriod(Request $request, $id)
    {
        $period = AcademicGradingPeriod::find($id);

        if (!$period) {
            return redirect()->route('config.grading')
                ->with('error', "Grading Period #{$id} was already removed or does not exist.");
        }

        if (auth()->check() && !auth()->user()->isSuperAdmin() && $period->school_id !== auth()->user()->school_id) {
            return redirect()->route('config.grading')
                ->with('error', "You do not have permission to delete this period.");
        }

        $ruleId = $period->rule_id;
        $name = $period->period_name;
        $isSub = !empty($period->parent_period_id);

        $period->delete();

        if ($rule = GradingStructureRule::find($ruleId)) {
            $this->syncRuleScenarioDepth($rule);
        }

        $typeLabel = $isSub ? 'Sub-Grading Period' : 'Main Grading Period';

        return redirect()->route('config.grading', ['template_id' => $ruleId])
            ->with('success', "✓ {$typeLabel} '{$name}' and its child categories were removed successfully.");
    }

    /**
     * Add a Category or Sub-Category dynamically.
     */
    public function addCategory(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $validated = $request->validate([
            'rule_id' => 'required|exists:grading_structure_rules,id',
            'grading_period_id' => 'required|exists:academic_grading_periods,id',
            'parent_category_id' => 'nullable|exists:grading_categories,id',
            'template_category_id' => 'nullable|exists:grading_template_categories,id',
            'category_name' => 'required|string|max:100',
            'category_type' => 'nullable|string|max:50',
            'weight' => 'required|numeric|min:0|max:100',
            'max_score' => 'nullable|numeric|min:1'
        ]);

        $rule = GradingStructureRule::where('school_id', $activeSchool->school_id)
            ->findOrFail($validated['rule_id']);

        $isSubCategory = !empty($validated['parent_category_id']);

        if ($isSubCategory) {
            $parentCat = GradingCategory::findOrFail($validated['parent_category_id']);
            $periodId = $parentCat->grading_period_id;
        } else {
            $periodId = $validated['grading_period_id'];
        }

        $nextOrder = GradingCategory::where('rule_id', $rule->id)
            ->when($isSubCategory, function ($q) use ($validated) {
                return $q->where('parent_category_id', $validated['parent_category_id']);
            }, function ($q) use ($periodId) {
                return $q->where('grading_period_id', $periodId)->whereNull('parent_category_id');
            })
            ->max('display_order') + 1;

        $category = GradingCategory::create([
            'school_id' => $activeSchool->school_id,
            'rule_id' => $rule->id,
            'grading_period_id' => $periodId,
            'parent_category_id' => $validated['parent_category_id'] ?? null,
            'template_category_id' => $validated['template_category_id'] ?? null,
            'category_name' => trim($validated['category_name']),
            'category_type' => $isSubCategory ? 'SUB' : ($validated['category_type'] ?? 'MAIN'),
            'weight' => (float) $validated['weight'],
            'max_score' => (float) ($validated['max_score'] ?? $validated['weight'] ?? 100.00),
            'display_order' => $nextOrder
        ]);

        $this->syncRuleScenarioDepth($rule);

        $typeLabel = $isSubCategory ? 'Sub-Category' : 'Category';

        return redirect()->route('config.grading', ['template_id' => $rule->id])
            ->with('success', "✓ Added {$typeLabel} '{$category->category_name}' ({$category->weight}%) successfully!");
    }

    /**
     * Delete a Category or Sub-Category.
     */
    public function deleteCategory(Request $request, $id)
    {
        $category = GradingCategory::find($id);

        if (!$category) {
            return redirect()->route('config.grading')
                ->with('error', "Category #{$id} was already removed or does not exist.");
        }

        if (auth()->check() && !auth()->user()->isSuperAdmin() && $category->school_id !== auth()->user()->school_id) {
            return redirect()->route('config.grading')
                ->with('error', "You do not have permission to delete this category.");
        }

        $ruleId = $category->rule_id;
        $name = $category->category_name;
        $isSub = !empty($category->parent_category_id);

        $category->delete();

        if ($rule = GradingStructureRule::find($ruleId)) {
            $this->syncRuleScenarioDepth($rule);
        }

        $typeLabel = $isSub ? 'Sub-Category' : 'Category';

        return redirect()->route('config.grading', ['template_id' => $ruleId])
            ->with('success', "✓ {$typeLabel} '{$name}' was removed successfully.");
    }

    /**
     * Synchronize rule scenario_depth to match current structure hierarchy.
     */
    protected function syncRuleScenarioDepth(GradingStructureRule $rule): void
    {
        $hasSubCategories = GradingCategory::where('rule_id', $rule->id)->whereNotNull('parent_category_id')->exists();
        if ($hasSubCategories) {
            $rule->update(['scenario_depth' => '4_TIER_FULL_SUB_CAT']);
            return;
        }

        $hasSubPeriods = AcademicGradingPeriod::where('rule_id', $rule->id)->whereNotNull('parent_period_id')->exists();
        if ($hasSubPeriods) {
            $rule->update(['scenario_depth' => '3_TIER_SUB_PERIOD_MAIN_CAT']);
            return;
        }

        $rule->update(['scenario_depth' => '2_TIER_MAIN_CAT']);
    }

    /**
     * Add a Category to the Template's Master Category List (Category Bank).
     */
    public function addTemplateCategory(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $validated = $request->validate([
            'rule_id' => 'required|exists:grading_structure_rules,id',
            'category_name' => 'required|string|max:100',
            'category_type' => 'nullable|string|max:50',
            'default_weight' => 'required|numeric|min:0|max:100',
            'default_max_score' => 'nullable|numeric|min:1',
            'sub_categories_text' => 'nullable|string'
        ]);

        $rule = GradingStructureRule::where('school_id', $activeSchool->school_id)
            ->findOrFail($validated['rule_id']);

        $subCategories = null;
        if (!empty($validated['sub_categories_text'])) {
            $subCategories = [];
            $lines = preg_split('/[\r\n,]+/', $validated['sub_categories_text']);
            $defaultWeight = count($lines) > 0 ? round(100 / count($lines), 2) : 25;
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (!empty($trimmed)) {
                    if (preg_match('/^(.*?)\s*\((\d+(?:\.\d+)?)\s*%\)$/', $trimmed, $m)) {
                        $subCategories[] = ['name' => trim($m[1]), 'weight' => (float)$m[2]];
                    } else {
                        $subCategories[] = ['name' => $trimmed, 'weight' => $defaultWeight];
                    }
                }
            }
        }

        $nextOrder = GradingTemplateCategory::where('rule_id', $rule->id)->max('display_order') + 1;

        $templateCat = GradingTemplateCategory::create([
            'school_id' => $activeSchool->school_id,
            'rule_id' => $rule->id,
            'category_name' => trim($validated['category_name']),
            'category_type' => $validated['category_type'] ?? 'CUSTOM',
            'default_weight' => (float) $validated['default_weight'],
            'default_max_score' => (float) ($validated['default_max_score'] ?? 100.00),
            'sub_categories_json' => $subCategories,
            'display_order' => $nextOrder
        ]);

        return redirect()->route('config.grading', ['template_id' => $rule->id])
            ->with('success', "✓ Category '{$templateCat->category_name}' added to template category list!");
    }

    /**
     * Update a Category in the Template's Master Category List.
     */
    public function updateTemplateCategory(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $templateCat = GradingTemplateCategory::where('school_id', $activeSchool->school_id)->findOrFail($id);

        $validated = $request->validate([
            'category_name' => 'required|string|max:100',
            'category_type' => 'nullable|string|max:50',
            'default_weight' => 'required|numeric|min:0|max:100',
            'default_max_score' => 'nullable|numeric|min:1',
            'sub_categories_text' => 'nullable|string'
        ]);

        $subCategories = null;
        if (!empty($validated['sub_categories_text'])) {
            $subCategories = [];
            $lines = preg_split('/[\r\n,]+/', $validated['sub_categories_text']);
            $defaultWeight = count($lines) > 0 ? round(100 / count($lines), 2) : 25;
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (!empty($trimmed)) {
                    if (preg_match('/^(.*?)\s*\((\d+(?:\.\d+)?)\s*%\)$/', $trimmed, $m)) {
                        $subCategories[] = ['name' => trim($m[1]), 'weight' => (float)$m[2]];
                    } else {
                        $subCategories[] = ['name' => $trimmed, 'weight' => $defaultWeight];
                    }
                }
            }
        }

        $templateCat->update([
            'category_name' => trim($validated['category_name']),
            'category_type' => $validated['category_type'] ?? 'CUSTOM',
            'default_weight' => (float) $validated['default_weight'],
            'default_max_score' => (float) ($validated['default_max_score'] ?? 100.00),
            'sub_categories_json' => $subCategories
        ]);

        return redirect()->route('config.grading', ['template_id' => $templateCat->rule_id])
            ->with('success', "✓ Template category '{$templateCat->category_name}' updated successfully.");
    }

    /**
     * Delete a Category from the Template's Master Category List.
     */
    public function deleteTemplateCategory(Request $request, $id)
    {
        $templateCat = GradingTemplateCategory::find($id);

        if (!$templateCat) {
            return redirect()->route('config.grading')
                ->with('error', "Template category #{$id} was not found or was already removed.");
        }

        if (auth()->check() && !auth()->user()->isSuperAdmin() && $templateCat->school_id !== auth()->user()->school_id) {
            return redirect()->route('config.grading')
                ->with('error', "You do not have permission to delete this template category.");
        }

        $ruleId = $templateCat->rule_id;
        $name = $templateCat->category_name;
        $templateCat->delete();

        return redirect()->route('config.grading', ['template_id' => $ruleId])
            ->with('success', "✓ Category '{$name}' removed from template category list.");
    }

    /**
     * Use selected Category or Categories from Template List inside a Grading Period.
     */
    public function useCategoryInPeriod(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $validated = $request->validate([
            'rule_id' => 'required|exists:grading_structure_rules,id',
            'grading_period_id' => 'required|exists:academic_grading_periods,id',
            'template_category_ids' => 'nullable|array',
            'template_category_ids.*' => 'exists:grading_template_categories,id',
            'template_category_id' => 'nullable|exists:grading_template_categories,id',
            'custom_weight' => 'nullable|numeric|min:0|max:100'
        ]);

        $rule = GradingStructureRule::where('school_id', $activeSchool->school_id)
            ->findOrFail($validated['rule_id']);

        $period = AcademicGradingPeriod::where('school_id', $activeSchool->school_id)
            ->where('rule_id', $rule->id)
            ->findOrFail($validated['grading_period_id']);

        $targetCategoryIds = [];
        if (!empty($validated['template_category_ids'])) {
            $targetCategoryIds = $validated['template_category_ids'];
        } elseif (!empty($validated['template_category_id'])) {
            $targetCategoryIds = [$validated['template_category_id']];
        }

        if (empty($targetCategoryIds)) {
            return redirect()->route('config.grading', ['template_id' => $rule->id])
                ->with('error', 'Please select at least one category from the template list to use in this period.');
        }

        $templateCategories = GradingTemplateCategory::where('school_id', $activeSchool->school_id)
            ->where('rule_id', $rule->id)
            ->whereIn('id', $targetCategoryIds)
            ->orderBy('display_order')
            ->get();

        $addedCount = 0;
        foreach ($templateCategories as $tc) {
            $maxOrder = GradingCategory::where('grading_period_id', $period->id)
                ->whereNull('parent_category_id')
                ->max('display_order') ?? 0;

            $weight = count($targetCategoryIds) === 1 && isset($validated['custom_weight']) && $validated['custom_weight'] !== ''
                ? (float) $validated['custom_weight']
                : (float) $tc->default_weight;

            $cat = GradingCategory::create([
                'school_id' => $activeSchool->school_id,
                'rule_id' => $rule->id,
                'grading_period_id' => $period->id,
                'template_category_id' => $tc->id,
                'category_name' => $tc->category_name,
                'category_type' => $tc->category_type ?? 'MAIN',
                'weight' => $weight,
                'max_score' => (float) $tc->default_max_score,
                'display_order' => $maxOrder + 1
            ]);

            // Auto-create sub-categories if defined on the template category
            if (!empty($tc->sub_categories_json) && is_array($tc->sub_categories_json)) {
                foreach ($tc->sub_categories_json as $sIdx => $sub) {
                    GradingCategory::create([
                        'school_id' => $activeSchool->school_id,
                        'rule_id' => $rule->id,
                        'grading_period_id' => $period->id,
                        'parent_category_id' => $cat->id,
                        'category_name' => $sub['name'] ?? ('Sub ' . ($sIdx + 1)),
                        'category_type' => 'SUB',
                        'weight' => (float) ($sub['weight'] ?? 25.00),
                        'max_score' => (float) ($tc->default_max_score ?? 100.00),
                        'display_order' => $sIdx + 1
                    ]);
                }
            }

            $addedCount++;
        }

        $this->syncRuleScenarioDepth($rule);

        return redirect()->route('config.grading', ['template_id' => $rule->id])
            ->with('success', "✓ Used {$addedCount} categories from template list in '{$period->period_name}' successfully!");
    }

    /**
     * Batch apply the entire Template Category List across multiple or all Grading Periods.
     */
    public function applyTemplateCategoriesToPeriods(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $validated = $request->validate([
            'rule_id' => 'required|exists:grading_structure_rules,id',
            'period_ids' => 'required|array|min:1',
            'period_ids.*' => 'exists:academic_grading_periods,id',
            'mode' => 'nullable|in:add_missing,replace'
        ]);

        $rule = GradingStructureRule::where('school_id', $activeSchool->school_id)
            ->findOrFail($validated['rule_id']);

        $templateCategories = GradingTemplateCategory::where('school_id', $activeSchool->school_id)
            ->where('rule_id', $rule->id)
            ->orderBy('display_order')
            ->get();

        if ($templateCategories->isEmpty()) {
            return redirect()->route('config.grading', ['template_id' => $rule->id])
                ->with('error', 'The template category list is currently empty. Add categories to the template list first.');
        }

        $mode = $validated['mode'] ?? 'add_missing';
        $appliedPeriodsCount = 0;

        foreach ($validated['period_ids'] as $pId) {
            $period = AcademicGradingPeriod::where('school_id', $activeSchool->school_id)
                ->where('rule_id', $rule->id)
                ->find($pId);

            if (!$period) continue;

            if ($mode === 'replace') {
                GradingCategory::where('grading_period_id', $period->id)->delete();
            }

            foreach ($templateCategories as $tc) {
                if ($mode === 'add_missing') {
                    $exists = GradingCategory::where('grading_period_id', $period->id)
                        ->where(function($q) use ($tc) {
                            $q->where('template_category_id', $tc->id)
                              ->orWhere('category_name', $tc->category_name);
                        })
                        ->exists();
                    if ($exists) continue;
                }

                $maxOrder = GradingCategory::where('grading_period_id', $period->id)
                    ->whereNull('parent_category_id')
                    ->max('display_order') ?? 0;

                $cat = GradingCategory::create([
                    'school_id' => $activeSchool->school_id,
                    'rule_id' => $rule->id,
                    'grading_period_id' => $period->id,
                    'template_category_id' => $tc->id,
                    'category_name' => $tc->category_name,
                    'category_type' => $tc->category_type ?? 'MAIN',
                    'weight' => (float) $tc->default_weight,
                    'max_score' => (float) $tc->default_max_score,
                    'display_order' => $maxOrder + 1
                ]);

                if (!empty($tc->sub_categories_json) && is_array($tc->sub_categories_json)) {
                    foreach ($tc->sub_categories_json as $sIdx => $sub) {
                        GradingCategory::create([
                            'school_id' => $activeSchool->school_id,
                            'rule_id' => $rule->id,
                            'grading_period_id' => $period->id,
                            'parent_category_id' => $cat->id,
                            'category_name' => $sub['name'] ?? ('Sub ' . ($sIdx + 1)),
                            'category_type' => 'SUB',
                            'weight' => (float) ($sub['weight'] ?? 25.00),
                            'max_score' => (float) ($tc->default_max_score ?? 100.00),
                            'display_order' => $sIdx + 1
                        ]);
                    }
                }
            }
            $appliedPeriodsCount++;
        }

        $this->syncRuleScenarioDepth($rule);

        return redirect()->route('config.grading', ['template_id' => $rule->id])
            ->with('success', "✓ Template category list applied across {$appliedPeriodsCount} grading periods successfully!");
    }

    /**
     * Apply a Grading System / Template to one, multiple, or all Subjects.
     */
    public function applyGradingSystemToSubjects(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $validated = $request->validate([
            'rule_id' => 'required|exists:grading_structure_rules,id',
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'exists:master_subjects,id',
            'all_subjects' => 'nullable|boolean',
            'pass_limit' => 'nullable|numeric|min:0|max:100',
            'cascade_to_classes' => 'nullable|boolean'
        ]);

        $rule = GradingStructureRule::where('school_id', $activeSchool->school_id)
            ->findOrFail($validated['rule_id']);

        $passLimit = isset($validated['pass_limit']) && $validated['pass_limit'] !== '' 
            ? (float) $validated['pass_limit'] 
            : (float) $rule->pass_limit;

        $cascade = $request->boolean('cascade_to_classes', true);

        $query = MasterSubject::where('school_id', $activeSchool->school_id);
        if (!$request->boolean('all_subjects') && !empty($validated['subject_ids'])) {
            $query->whereIn('id', $validated['subject_ids']);
        }

        $subjects = $query->get();

        if ($subjects->isEmpty()) {
            return redirect()->back()->with('error', 'Please select at least one subject to apply the grading system to.');
        }

        foreach ($subjects as $subj) {
            $subj->update([
                'grading_rule_id' => $rule->id,
                'pass_limit' => $passLimit,
                'default_credits' => (float) $rule->credit_hours,
                'max_score' => (float) $rule->total_grade
            ]);

            if ($cascade) {
                ClassSubjectAssignment::where('subject_id', $subj->id)->update([
                    'grading_rule_id' => $rule->id,
                    'passing_mark' => $passLimit,
                    'credit_hours' => (float) $rule->credit_hours
                ]);
            }
        }

        $count = $subjects->count();

        return redirect()->back()
            ->with('success', "✓ Successfully applied grading system '{$rule->rule_name}' to {$count} subject(s)!");
    }

    /**
     * Reset a subject's grading system back to school default.
     */
    public function resetSubjectGrading(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $subject = MasterSubject::where('school_id', $activeSchool->school_id)->find($id);

        if (!$subject) {
            return redirect()->back()->with('error', "Subject #{$id} was not found.");
        }

        $subject->update([
            'grading_rule_id' => null,
            'pass_limit' => null
        ]);

        ClassSubjectAssignment::where('subject_id', $subject->id)->update([
            'grading_rule_id' => null
        ]);

        return redirect()->back()
            ->with('success', "✓ Reset '{$subject->subject_name}' to default grading template.");
    }

    /**
     * Display Master Subjects & Curriculum Studio (Levels & Batches).
     */
    public function classSubjects(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $masterSubjects = MasterSubject::where('school_id', $activeSchool->school_id)
            ->with(['gradingRule', 'assignments.schoolClass'])
            ->withCount('assignments')
            ->orderBy('department')
            ->orderBy('subject_name')
            ->get();

        $gradingTemplates = GradingStructureRule::where('school_id', $activeSchool->school_id)
            ->where('is_template', true)
            ->orderBy('rule_name')
            ->get();

        $gradeLevels = SchoolGradeLevel::where('school_id', $activeSchool->school_id)
            ->with(['classes.subjectAssignments.subject', 'classes.subjectAssignments.gradingRule', 'classes.students'])
            ->orderBy('display_order')
            ->get();

        $allClasses = SchoolClass::where('school_id', $activeSchool->school_id)
            ->with(['gradeLevel', 'subjectAssignments.subject', 'subjectAssignments.gradingRule', 'students'])
            ->orderBy('class_name')
            ->get();

        $selectedClassId = $request->get('class_id', $allClasses->first()->id ?? null);
        $selectedClass = SchoolClass::with(['subjectAssignments.subject', 'subjectAssignments.gradingRule', 'students'])->find($selectedClassId);

        $stats = [
            'total_subjects' => $masterSubjects->count(),
            'total_levels' => $gradeLevels->count(),
            'total_classes' => $allClasses->count(),
            'total_assignments' => ClassSubjectAssignment::where('school_id', $activeSchool->school_id)->count()
        ];

        return view('configuration.class_subjects', compact(
            'activeSchool',
            'masterSubjects',
            'gradingTemplates',
            'gradeLevels',
            'allClasses',
            'selectedClass',
            'stats'
        ));
    }

    /**
     * Display a comprehensive Master Subject Profile with analytics, allocations, and grading details.
     */
    public function subjectProfile(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $subject = MasterSubject::where('school_id', $activeSchool->school_id)
            ->with([
                'gradingRule',
                'assignments.schoolClass.gradeLevel',
                'assignments.gradeRecords.student',
                'assignments.schoolClass.students'
            ])
            ->findOrFail($id);

        $gradingService = app(\App\Services\GradingCalculationService::class);
        $effectiveRule = $gradingService->resolveRuleForSubject($subject);
        if ($effectiveRule) {
            $effectiveRule->load([
                'periods.subPeriods.categories.subCategories',
                'periods.categories.subCategories',
                'templateCategories'
            ]);
        }

        // Levels where this subject is taught
        $assignedLevelIds = $subject->assignments->pluck('schoolClass.gradeLevel.id')->unique()->filter()->values();
        $assignedLevels = SchoolGradeLevel::whereIn('id', $assignedLevelIds)
            ->with(['classes.subjectAssignments' => function ($q) use ($subject) {
                $q->where('subject_id', $subject->id);
            }])
            ->get();

        // Enrolled students count
        $classIds = $subject->assignments->pluck('class_id')->unique()->values();
        $totalStudentsCount = Student::whereIn('class_id', $classIds)->count();

        // Enrolled students list (with marks if any)
        $enrolledStudents = Student::whereIn('class_id', $classIds)
            ->with(['schoolClass.gradeLevel', 'gradeRecords' => function ($q) use ($subject) {
                $q->whereIn('class_subject_assignment_id', $subject->assignments->pluck('id'));
            }])
            ->orderBy('full_name')
            ->take(50)
            ->get();

        // Teachers assigned
        $teachers = $subject->assignments->pluck('assigned_teacher')->filter()->unique()->values();

        // Performance analytics from grade records (if any)
        $assignmentIds = $subject->assignments->pluck('id');
        $gradeRecords = GradeRecord::whereIn('class_subject_assignment_id', $assignmentIds)->get();
        $gradedCount = $gradeRecords->count();
        $passLimit = $subject->pass_limit ?? ($effectiveRule->pass_limit ?? 60.0);
        $avgScore = $gradedCount > 0 ? round($gradeRecords->avg('total_score'), 1) : null;
        $highestScore = $gradedCount > 0 ? $gradeRecords->max('total_score') : null;
        $lowestScore = $gradedCount > 0 ? $gradeRecords->min('total_score') : null;
        $passCount = $gradedCount > 0 ? $gradeRecords->filter(fn($r) => $r->total_score >= $passLimit)->count() : 0;
        $passRate = $gradedCount > 0 ? round(($passCount / $gradedCount) * 100, 1) : null;

        // Meta for modals (adding to level, adding to batch, changing grading rule)
        $allGradeLevels = SchoolGradeLevel::where('school_id', $activeSchool->school_id)
            ->with('classes')
            ->orderBy('display_order')
            ->get();

        $allClasses = SchoolClass::where('school_id', $activeSchool->school_id)
            ->with('gradeLevel')
            ->orderBy('class_name')
            ->get();

        $gradingTemplates = GradingStructureRule::where('school_id', $activeSchool->school_id)
            ->where('is_template', true)
            ->orderBy('rule_name')
            ->get();

        return view('configuration.subject_profile', compact(
            'activeSchool',
            'subject',
            'effectiveRule',
            'assignedLevels',
            'totalStudentsCount',
            'enrolledStudents',
            'teachers',
            'gradedCount',
            'avgScore',
            'highestScore',
            'lowestScore',
            'passRate',
            'passCount',
            'allGradeLevels',
            'allClasses',
            'gradingTemplates'
        ));
    }

    /**
     * Create a new Master Subject in catalog with optional instant level assignment.
     */
    public function storeSubject(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $validated = $request->validate([
            'subject_name' => 'required|string|max:150',
            'subject_code' => 'required|string|max:50',
            'department' => 'nullable|string|max:100',
            'default_credits' => 'nullable|numeric|min:0.5',
            'max_score' => 'nullable|numeric|min:1',
            'cw_weight' => 'nullable|numeric|min:0|max:100',
            'exam_weight' => 'nullable|numeric|min:0|max:100',
            'passing_mark' => 'nullable|numeric|min:0|max:100',
            'grading_rule_id' => 'nullable|exists:grading_structure_rules,id',
            'assign_grade_ids' => 'nullable|array',
            'assign_grade_ids.*' => 'exists:school_grade_levels,id'
        ]);

        $subject = MasterSubject::create([
            'school_id' => $activeSchool->school_id,
            'subject_name' => trim($validated['subject_name']),
            'subject_code' => strtoupper(trim($validated['subject_code'])),
            'department' => $validated['department'] ?? 'General',
            'default_credits' => (float) ($validated['default_credits'] ?? 1.00),
            'max_score' => (float) ($validated['max_score'] ?? 100.00),
            'grading_rule_id' => $validated['grading_rule_id'] ?? null,
            'pass_limit' => isset($validated['passing_mark']) ? (float) $validated['passing_mark'] : null,
            'is_active' => true
        ]);

        $assignedClassesCount = 0;
        if (!empty($validated['assign_grade_ids'])) {
            $classes = SchoolClass::where('school_id', $activeSchool->school_id)
                ->whereIn('grade_id', $validated['assign_grade_ids'])
                ->get();

            $credits = (float) ($validated['default_credits'] ?? 1.00);
            $cw = (float) ($validated['cw_weight'] ?? 40.00);
            $exam = (float) ($validated['exam_weight'] ?? 60.00);
            $pass = (float) ($validated['passing_mark'] ?? 60.00);

            foreach ($classes as $cls) {
                $maxOrder = ClassSubjectAssignment::where('class_id', $cls->id)->max('display_order') ?? 0;
                ClassSubjectAssignment::firstOrCreate(
                    [
                        'class_id' => $cls->id,
                        'subject_id' => $subject->id
                    ],
                    [
                        'school_id' => $activeSchool->school_id,
                        'grading_rule_id' => $subject->grading_rule_id,
                        'credit_hours' => $credits,
                        'cw_weight' => $cw,
                        'exam_weight' => $exam,
                        'passing_mark' => $pass,
                        'display_order' => $maxOrder + 1
                    ]
                );
                $assignedClassesCount++;
            }
        }

        $extraMsg = $assignedClassesCount > 0 ? " and assigned to {$assignedClassesCount} classes/batches" : "";

        return redirect()->route('config.class_subjects')
            ->with('success', "✓ Subject '{$subject->subject_name}' ({$subject->subject_code}) created successfully{$extraMsg}!");
    }

    /**
     * Update Master Subject details.
     */
    public function updateSubject(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $subject = MasterSubject::where('school_id', $activeSchool->school_id)->findOrFail($id);

        $validated = $request->validate([
            'subject_name' => 'required|string|max:150',
            'subject_code' => 'required|string|max:50',
            'department' => 'nullable|string|max:100',
            'default_credits' => 'required|numeric|min:0.5',
            'max_score' => 'required|numeric|min:1',
            'grading_rule_id' => 'nullable|exists:grading_structure_rules,id',
            'pass_limit' => 'nullable|numeric|min:0|max:100'
        ]);

        $subject->update([
            'subject_name' => trim($validated['subject_name']),
            'subject_code' => strtoupper(trim($validated['subject_code'])),
            'department' => $validated['department'] ?? 'General',
            'default_credits' => (float) $validated['default_credits'],
            'max_score' => (float) $validated['max_score'],
            'grading_rule_id' => $validated['grading_rule_id'] ?? null,
            'pass_limit' => isset($validated['pass_limit']) ? (float) $validated['pass_limit'] : null
        ]);

        // Cascade grading rule update to class assignments if changed
        if (array_key_exists('grading_rule_id', $validated)) {
            ClassSubjectAssignment::where('subject_id', $subject->id)->update([
                'grading_rule_id' => $validated['grading_rule_id'] ?? null,
                'passing_mark' => isset($validated['pass_limit']) ? (float) $validated['pass_limit'] : 60.00
            ]);
        }

        return redirect()->route('config.class_subjects')
            ->with('success', "✓ Subject '{$subject->subject_name}' updated successfully.");
    }

    /**
     * Delete a Master Subject from catalog.
     */
    public function deleteSubject(Request $request, $id)
    {
        $subject = MasterSubject::find($id);

        if (!$subject) {
            return redirect()->route('config.class_subjects')
                ->with('error', "Subject #{$id} was not found or was already removed.");
        }

        if (auth()->check() && !auth()->user()->isSuperAdmin() && $subject->school_id !== auth()->user()->school_id) {
            return redirect()->route('config.class_subjects')
                ->with('error', "You do not have permission to delete this subject.");
        }

        $name = $subject->subject_name;
        $subject->delete();

        return redirect()->route('config.class_subjects')
            ->with('success', "✓ Subject '{$name}' was removed from master catalog.");
    }

    /**
     * Assign Subject to an entire Level (all classes/batches within that level).
     */
    public function assignSubjectToLevel(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $validated = $request->validate([
            'subject_id' => 'required|exists:master_subjects,id',
            'grade_ids' => 'required|array|min:1',
            'grade_ids.*' => 'exists:school_grade_levels,id',
            'credit_hours' => 'nullable|numeric|min:0.5',
            'cw_weight' => 'nullable|numeric|min:0|max:100',
            'exam_weight' => 'nullable|numeric|min:0|max:100',
            'passing_mark' => 'nullable|numeric|min:0|max:100',
            'assigned_teacher' => 'nullable|string|max:150'
        ]);

        $subject = MasterSubject::where('school_id', $activeSchool->school_id)->findOrFail($validated['subject_id']);
        $credits = (float) ($validated['credit_hours'] ?? $subject->default_credits);
        $cw = (float) ($validated['cw_weight'] ?? 40.00);
        $exam = (float) ($validated['exam_weight'] ?? 60.00);
        $pass = (float) ($validated['passing_mark'] ?? 60.00);
        $teacher = $validated['assigned_teacher'] ?? null;

        $classes = SchoolClass::where('school_id', $activeSchool->school_id)
            ->whereIn('grade_id', $validated['grade_ids'])
            ->get();

        if ($classes->isEmpty()) {
            return redirect()->route('config.class_subjects')
                ->with('error', "The selected level(s) currently have no classes/batches created. Create classes in School Levels first.");
        }

        $assignedCount = 0;
        foreach ($classes as $cls) {
            $maxOrder = ClassSubjectAssignment::where('class_id', $cls->id)->max('display_order') ?? 0;
            ClassSubjectAssignment::updateOrCreate(
                [
                    'class_id' => $cls->id,
                    'subject_id' => $subject->id
                ],
                [
                    'school_id' => $activeSchool->school_id,
                    'grading_rule_id' => $subject->grading_rule_id,
                    'credit_hours' => $credits,
                    'cw_weight' => $cw,
                    'exam_weight' => $exam,
                    'passing_mark' => $pass,
                    'assigned_teacher' => $teacher,
                    'display_order' => $maxOrder + 1
                ]
            );
            $assignedCount++;
        }

        $levelCount = count($validated['grade_ids']);

        return redirect()->route('config.class_subjects')
            ->with('success', "✓ Assigned '{$subject->subject_name}' across {$levelCount} level(s) ({$assignedCount} classes/batches) successfully!");
    }

    /**
     * Assign Subject to specific Class / Batch(es).
     */
    public function assignSubjectToClass(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $validated = $request->validate([
            'subject_id' => 'required|exists:master_subjects,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'class_ids' => 'nullable|array',
            'class_ids.*' => 'exists:school_classes,id',
            'credit_hours' => 'nullable|numeric|min:0.5',
            'cw_weight' => 'nullable|numeric|min:0|max:100',
            'exam_weight' => 'nullable|numeric|min:0|max:100',
            'passing_mark' => 'nullable|numeric|min:0|max:100',
            'assigned_teacher' => 'nullable|string|max:150'
        ]);

        $subject = MasterSubject::where('school_id', $activeSchool->school_id)->findOrFail($validated['subject_id']);
        $credits = (float) ($validated['credit_hours'] ?? $subject->default_credits);
        $cw = (float) ($validated['cw_weight'] ?? 40.00);
        $exam = (float) ($validated['exam_weight'] ?? 60.00);
        $pass = (float) ($validated['passing_mark'] ?? 60.00);
        $teacher = $validated['assigned_teacher'] ?? null;

        // Resolve target class IDs
        $targetClassIds = [];
        if (!empty($validated['class_ids'])) {
            $targetClassIds = $validated['class_ids'];
        } elseif (!empty($validated['class_id'])) {
            $targetClassIds = [$validated['class_id']];
        }

        if (empty($targetClassIds)) {
            return redirect()->back()->with('error', 'Please select at least one target class/batch.');
        }

        $assignedCount = 0;
        foreach ($targetClassIds as $clsId) {
            $maxOrder = ClassSubjectAssignment::where('class_id', $clsId)->max('display_order') ?? 0;
            ClassSubjectAssignment::updateOrCreate(
                [
                    'class_id' => $clsId,
                    'subject_id' => $subject->id
                ],
                [
                    'school_id' => $activeSchool->school_id,
                    'grading_rule_id' => $subject->grading_rule_id,
                    'credit_hours' => $credits,
                    'cw_weight' => $cw,
                    'exam_weight' => $exam,
                    'passing_mark' => $pass,
                    'assigned_teacher' => $teacher,
                    'display_order' => $maxOrder + 1
                ]
            );
            $assignedCount++;
        }

        return redirect()->back()
            ->with('success', "✓ Assigned '{$subject->subject_name}' to {$assignedCount} class/batch(es) successfully.");
    }

    /**
     * Unassign / remove a subject from a class/batch.
     */
    public function unassignSubjectFromClass(Request $request, $id)
    {
        $assignment = ClassSubjectAssignment::with('subject')->find($id);

        if (!$assignment) {
            return redirect()->back()->with('error', 'Assignment not found or already removed.');
        }

        if (auth()->check() && !auth()->user()->isSuperAdmin() && $assignment->school_id !== auth()->user()->school_id) {
            return redirect()->back()->with('error', 'You do not have permission to modify this assignment.');
        }

        $subjName = $assignment->subject->subject_name ?? 'Subject';
        $assignment->delete();

        return redirect()->back()->with('success', "✓ Unassigned '{$subjName}' from class successfully.");
    }

    /**
     * Display School Levels, Educational Stages & Classes Studio.
     */
    public function levels(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $divisions = SchoolDivision::where('school_id', $activeSchool->school_id)
            ->with(['gradeLevels.classes.students'])
            ->orderBy('display_order')
            ->get();

        $unassignedLevels = SchoolGradeLevel::where('school_id', $activeSchool->school_id)
            ->whereNull('division_id')
            ->with(['classes.students'])
            ->orderBy('display_order')
            ->get();

        $allLevels = SchoolGradeLevel::where('school_id', $activeSchool->school_id)
            ->with(['division', 'classes.students'])
            ->orderBy('display_order')
            ->get();

        $allClasses = SchoolClass::where('school_id', $activeSchool->school_id)
            ->with(['gradeLevel', 'students'])
            ->get();

        $totalStudents = Student::where('school_id', $activeSchool->school_id)->count();

        $stats = [
            'total_levels' => $allLevels->count(),
            'total_divisions' => $divisions->count(),
            'total_classes' => $allClasses->count(),
            'total_students' => $totalStudents
        ];

        return view('configuration.levels', compact(
            'activeSchool',
            'divisions',
            'unassignedLevels',
            'allLevels',
            'stats'
        ));
    }

    /**
     * Create a new Grade/School Level.
     */
    public function storeLevel(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $validated = $request->validate([
            'grade_name' => 'required|string|max:100',
            'grade_code' => 'required|string|max:20',
            'division_id' => 'nullable|exists:school_divisions,id',
            'display_order' => 'nullable|integer',
            'create_class' => 'nullable|boolean',
            'class_name' => 'nullable|string|max:50'
        ]);

        $order = $validated['display_order'] ?? (SchoolGradeLevel::where('school_id', $activeSchool->school_id)->max('display_order') + 1);

        $level = SchoolGradeLevel::create([
            'school_id' => $activeSchool->school_id,
            'division_id' => $validated['division_id'] ?? null,
            'grade_name' => trim($validated['grade_name']),
            'grade_code' => strtoupper(trim($validated['grade_code'])),
            'display_order' => $order
        ]);

        if (!empty($validated['create_class']) && !empty($validated['class_name'])) {
            $classNames = array_filter(array_map('trim', explode(',', $validated['class_name'])));
            foreach ($classNames as $cName) {
                SchoolClass::firstOrCreate(
                    [
                        'school_id' => $activeSchool->school_id,
                        'grade_id' => $level->id,
                        'class_name' => $cName
                    ],
                    [
                        'max_capacity' => 30
                    ]
                );
            }
        }

        return redirect()->route('config.levels')
            ->with('success', "✓ Level '{$level->grade_name}' ({$level->grade_code}) created successfully!");
    }

    /**
     * Batch generate consecutive School Levels with customizable classes/sections.
     */
    public function batchCreateLevels(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $validated = $request->validate([
            'pattern' => 'required|string|max:50',
            'code_prefix' => 'required|string|max:10',
            'from_level' => 'required|integer|min:1|max:30',
            'to_level' => 'required|integer|gte:from_level|max:30',
            'division_id' => 'nullable|exists:school_divisions,id',
            'create_default_class' => 'nullable|boolean',
            'create_classes' => 'nullable|boolean',
            'class_sections' => 'nullable|string|max:200',
            'class_format' => 'nullable|string|max:30',
            'max_capacity' => 'nullable|integer|min:1|max:200'
        ]);

        $pattern = $validated['pattern'];
        $prefix = strtoupper($validated['code_prefix']);
        $from = (int) $validated['from_level'];
        $to = (int) $validated['to_level'];
        $divisionId = $validated['division_id'] ?? null;
        $shouldCreateClasses = !empty($validated['create_classes']) || !empty($validated['create_default_class']);
        $rawSections = $validated['class_sections'] ?? ($request->has('create_default_class') && !$request->has('class_sections') ? 'A' : 'A, B');
        $format = $validated['class_format'] ?? 'CODE_DASH_SEC';
        $capacity = (int) ($validated['max_capacity'] ?? 30);

        // Parse section names (comma-separated, e.g. "A, B, C" or "Alpha, Beta")
        $sections = array_filter(array_map('trim', explode(',', $rawSections)));
        if (empty($sections)) {
            $sections = ['A'];
        }

        $createdLevelsCount = 0;
        $createdClassesCount = 0;
        $maxOrder = SchoolGradeLevel::where('school_id', $activeSchool->school_id)->max('display_order') ?? 0;

        for ($i = $from; $i <= $to; $i++) {
            $name = str_replace('{N}', $i, $pattern);
            $code = $prefix . $i;

            $level = SchoolGradeLevel::firstOrCreate(
                [
                    'school_id' => $activeSchool->school_id,
                    'grade_code' => $code
                ],
                [
                    'grade_name' => $name,
                    'division_id' => $divisionId,
                    'display_order' => ++$maxOrder
                ]
            );

            if ($level->wasRecentlyCreated) {
                $createdLevelsCount++;
            }

            if ($shouldCreateClasses) {
                foreach ($sections as $sec) {
                    $className = match ($format) {
                        'NAME_DASH_SEC' => "{$name} - {$sec}",
                        'NAME_SPACE_SEC' => "{$name} {$sec}",
                        'SECTION_ONLY' => $sec,
                        default => "{$code}-{$sec}", // CODE_DASH_SEC e.g. L1-A
                    };

                    $class = SchoolClass::firstOrCreate(
                        [
                            'school_id' => $activeSchool->school_id,
                            'grade_id' => $level->id,
                            'class_name' => $className
                        ],
                        [
                            'max_capacity' => $capacity
                        ]
                    );

                    if ($class->wasRecentlyCreated) {
                        $createdClassesCount++;
                    }
                }
            }
        }

        $classMsg = $shouldCreateClasses ? " and {$createdClassesCount} classes" : "";

        return redirect()->route('config.levels')
            ->with('success', "✓ Batch generator finished: {$createdLevelsCount} levels{$classMsg} created successfully!");
    }

    /**
     * Update an existing School Level.
     */
    public function updateLevel(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $level = SchoolGradeLevel::where('school_id', $activeSchool->school_id)->findOrFail($id);

        $validated = $request->validate([
            'grade_name' => 'required|string|max:100',
            'grade_code' => 'required|string|max:20',
            'division_id' => 'nullable|exists:school_divisions,id',
            'display_order' => 'required|integer'
        ]);

        $level->update([
            'grade_name' => trim($validated['grade_name']),
            'grade_code' => strtoupper(trim($validated['grade_code'])),
            'division_id' => $validated['division_id'] ?? null,
            'display_order' => (int) $validated['display_order']
        ]);

        return redirect()->route('config.levels')
            ->with('success', "✓ Level '{$level->grade_name}' updated successfully.");
    }

    /**
     * Delete a School Level.
     */
    public function deleteLevel(Request $request, $id)
    {
        $level = SchoolGradeLevel::with('classes.students')->find($id);

        if (!$level) {
            return redirect()->route('config.levels')
                ->with('error', "School Level #{$id} was not found or was already removed.");
        }

        if (auth()->check() && !auth()->user()->isSuperAdmin() && $level->school_id !== auth()->user()->school_id) {
            return redirect()->route('config.levels')
                ->with('error', "You do not have permission to delete this level.");
        }

        $studentCount = $level->classes->sum(fn($c) => $c->students->count());

        if ($studentCount > 0 && !$request->has('force')) {
            return redirect()->route('config.levels')
                ->with('error', "Cannot delete '{$level->grade_name}': It has {$studentCount} actively enrolled student(s). Reassign students before removing this level.");
        }

        $name = $level->grade_name;
        $level->delete();

        return redirect()->route('config.levels')
            ->with('success', "✓ Level '{$name}' was removed successfully.");
    }

    /**
     * Store a new Educational Stage / Division.
     */
    public function storeDivision(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $validated = $request->validate([
            'division_name' => 'required|string|max:100',
            'display_order' => 'nullable|integer'
        ]);

        $order = $validated['display_order'] ?? (SchoolDivision::where('school_id', $activeSchool->school_id)->max('display_order') + 1);

        $div = SchoolDivision::create([
            'school_id' => $activeSchool->school_id,
            'division_name' => trim($validated['division_name']),
            'display_order' => $order
        ]);

        return redirect()->route('config.levels')
            ->with('success', "✓ Educational Stage '{$div->division_name}' created successfully.");
    }

    /**
     * Delete an Educational Stage / Division.
     */
    public function deleteDivision(Request $request, $id)
    {
        $div = SchoolDivision::find($id);

        if (!$div) {
            return redirect()->route('config.levels')
                ->with('error', "Educational Stage #{$id} was not found or was already removed.");
        }

        if (auth()->check() && !auth()->user()->isSuperAdmin() && $div->school_id !== auth()->user()->school_id) {
            return redirect()->route('config.levels')
                ->with('error', "You do not have permission to delete this stage.");
        }

        // Move levels inside this division to Unassigned
        SchoolGradeLevel::where('division_id', $div->id)->update(['division_id' => null]);

        $name = $div->division_name;
        $div->delete();

        return redirect()->route('config.levels')
            ->with('success', "✓ Educational Stage '{$name}' was removed. Associated levels moved to General / Unassigned.");
    }

    /**
     * Store a Class / Section under a Level.
     */
    public function storeClass(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $validated = $request->validate([
            'grade_id' => 'required|exists:school_grade_levels,id',
            'class_name' => 'required|string|max:100',
            'homeroom_teacher' => 'nullable|string|max:150',
            'max_capacity' => 'nullable|integer|min:1'
        ]);

        $class = SchoolClass::create([
            'school_id' => $activeSchool->school_id,
            'grade_id' => $validated['grade_id'],
            'class_name' => trim($validated['class_name']),
            'homeroom_teacher' => $validated['homeroom_teacher'] ?? null,
            'max_capacity' => $validated['max_capacity'] ?? 30
        ]);

        return redirect()->route('config.levels')
            ->with('success', "✓ Class '{$class->class_name}' added successfully.");
    }

    /**
     * Delete a Class / Section.
     */
    public function deleteClass(Request $request, $id)
    {
        $class = SchoolClass::with('students')->find($id);

        if (!$class) {
            return redirect()->route('config.levels')
                ->with('error', "Class #{$id} was not found or was already removed.");
        }

        if (auth()->check() && !auth()->user()->isSuperAdmin() && $class->school_id !== auth()->user()->school_id) {
            return redirect()->route('config.levels')
                ->with('error', "You do not have permission to delete this class.");
        }

        if ($class->students->isNotEmpty()) {
            return redirect()->route('config.levels')
                ->with('error', "Cannot delete '{$class->class_name}': It has {$class->students->count()} actively enrolled student(s).");
        }

        $name = $class->class_name;
        $class->delete();

        return redirect()->route('config.levels')
            ->with('success', "✓ Class '{$name}' removed successfully.");
    }

    /**
     * Display Class / Batch Profile with its assigned courses, students, and marksheet summary.
     */
    public function classProfile(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $class = SchoolClass::with([
            'gradeLevel.division',
            'subjectAssignments.subject.gradingRule',
            'subjectAssignments.gradingRule',
            'subjectAssignments.gradeRecords',
            'students.gradeRecords',
            'students.attendanceRecords'
        ])
        ->where('school_id', $activeSchool->school_id)
        ->find($id);

        if (!$class) {
            abort(404, "Class section #{$id} not found or does not belong to active school.");
        }

        $allLevels = SchoolGradeLevel::where('school_id', $activeSchool->school_id)
            ->orderBy('display_order')
            ->get();

        $masterSubjects = MasterSubject::where('school_id', $activeSchool->school_id)
            ->where('is_active', true)
            ->orderBy('subject_name')
            ->get();

        $gradingCalculationService = app(\App\Services\GradingCalculationService::class);

        // Enhance assignments with effective rules
        foreach ($class->subjectAssignments as $assign) {
            $assign->effective_rule = $gradingCalculationService->resolveRuleForSubject($assign->subject, $assign);
        }

        // Metrics
        $totalCourses = $class->subjectAssignments->count();
        $enrolledStudentsCount = $class->students->count();
        $capacityPct = $class->max_capacity > 0 ? round(($enrolledStudentsCount / $class->max_capacity) * 100, 1) : 0;
        $teachers = $class->subjectAssignments->pluck('assigned_teacher')->filter()->unique();

        // Calculate Grade Metrics
        $assignmentIds = $class->subjectAssignments->pluck('id')->toArray();
        $allGradeRecords = GradeRecord::whereIn('class_subject_assignment_id', $assignmentIds)->get();

        $avgScore = $allGradeRecords->isNotEmpty() ? round($allGradeRecords->avg('total_score'), 1) : null;

        $passingRecordsCount = 0;
        foreach ($allGradeRecords as $gr) {
            $assign = $class->subjectAssignments->firstWhere('id', $gr->class_subject_assignment_id);
            $passLimit = $assign ? ($assign->passing_mark ?? 60.0) : 60.0;
            if ($gr->total_score >= $passLimit) {
                $passingRecordsCount++;
            }
        }
        $passRate = $allGradeRecords->isNotEmpty() ? round(($passingRecordsCount / $allGradeRecords->count()) * 100, 1) : null;

        // Build student grade metrics
        $studentMetrics = [];
        foreach ($class->students as $std) {
            $stdRecords = $allGradeRecords->where('student_id', $std->id);
            $stdAvg = $stdRecords->isNotEmpty() ? round($stdRecords->avg('total_score'), 1) : null;
            $attRecord = $std->attendanceRecords->first();
            $attRate = $attRecord ? $attRecord->attendance_rate : null;

            $studentMetrics[$std->id] = [
                'average' => $stdAvg,
                'attendance_rate' => $attRate,
                'records_count' => $stdRecords->count()
            ];
        }

        // Attendance stats for class
        $classAttendance = [
            'total_days' => 0,
            'present' => 0,
            'absent' => 0,
            'excused' => 0,
            'tardy' => 0,
            'avg_rate' => null
        ];
        $allAttRecords = $class->students->flatMap->attendanceRecords;
        if ($allAttRecords->isNotEmpty()) {
            $classAttendance['total_days'] = $allAttRecords->sum('total_days');
            $classAttendance['present'] = $allAttRecords->sum('days_present');
            $classAttendance['absent'] = $allAttRecords->sum('days_absent');
            $classAttendance['excused'] = $allAttRecords->sum('days_excused');
            $classAttendance['tardy'] = $allAttRecords->sum('days_tardy');
            $classAttendance['avg_rate'] = round($allAttRecords->avg('attendance_rate'), 1);
        }

        $gradingTemplates = GradingStructureRule::where('school_id', $activeSchool->school_id)
            ->where('is_template', true)
            ->get();

        $gradebookData = $this->prepareGradebookData($class, $request);

        return view('configuration.class_profile', array_merge(compact(
            'activeSchool',
            'class',
            'allLevels',
            'masterSubjects',
            'gradingTemplates',
            'totalCourses',
            'enrolledStudentsCount',
            'capacityPct',
            'teachers',
            'avgScore',
            'passRate',
            'studentMetrics',
            'classAttendance'
        ), $gradebookData));
    }

    /**
     * Display the Batch Gradebook page.
     */
    public function classGradebook(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $class = SchoolClass::with([
            'gradeLevel.division',
            'subjectAssignments.subject.gradingRule',
            'subjectAssignments.gradingRule',
            'students'
        ])
        ->where('school_id', $activeSchool->school_id)
        ->findOrFail($id);

        $gradebookData = $this->prepareGradebookData($class, $request);

        return view('configuration.class_gradebook', array_merge([
            'activeSchool' => $activeSchool,
            'class' => $class
        ], $gradebookData));
    }

    /**
     * Helper to prepare Gradebook data for a batch/class.
     */
    protected function prepareGradebookData(SchoolClass $class, Request $request): array
    {
        $gradingCalculationService = app(\App\Services\GradingCalculationService::class);

        // Enhance assignments with effective rules
        foreach ($class->subjectAssignments as $assign) {
            $assign->effective_rule = $gradingCalculationService->resolveRuleForSubject($assign->subject, $assign);
        }

        $assignments = $class->subjectAssignments;

        // Determine selected assignment
        $selectedAssignmentId = $request->get('assignment_id');
        $selectedAssignment = null;
        if ($selectedAssignmentId) {
            $selectedAssignment = $assignments->firstWhere('id', $selectedAssignmentId);
        }
        if (!$selectedAssignment && $request->get('subject_id')) {
            $selectedAssignment = $assignments->firstWhere('subject_id', $request->get('subject_id'));
        }
        if (!$selectedAssignment) {
            $selectedAssignment = $assignments->first();
        }

        $effectiveRule = null;
        $periods = collect();
        $selectedPeriod = null;
        $categories = collect();
        $subScores = collect();
        $gradeRecords = collect();

        if ($selectedAssignment) {
            $effectiveRule = $selectedAssignment->effective_rule;
            if ($effectiveRule) {
                $periods = AcademicGradingPeriod::where('rule_id', $effectiveRule->id)
                    ->whereNull('parent_period_id')
                    ->with(['subPeriods.categories', 'categories.subCategories'])
                    ->orderBy('display_order')
                    ->get();
            }

            // Determine selected period
            $selectedPeriodId = $request->get('period_id');
            if ($selectedPeriodId) {
                $selectedPeriod = $periods->firstWhere('id', $selectedPeriodId);
                if (!$selectedPeriod) {
                    foreach ($periods as $p) {
                        $sub = $p->subPeriods->firstWhere('id', $selectedPeriodId);
                        if ($sub) {
                            $selectedPeriod = $sub;
                            break;
                        }
                    }
                }
            }
            if (!$selectedPeriod) {
                $selectedPeriod = $periods->first();
            }

            if ($selectedPeriod) {
                $categories = $selectedPeriod->categories;
                if ($categories->isEmpty() && $selectedPeriod->subPeriods && $selectedPeriod->subPeriods->isNotEmpty()) {
                    $categories = $selectedPeriod->subPeriods->flatMap->categories;
                }
            }

            // Load existing grade records for this assignment
            $gradeRecords = GradeRecord::where('class_subject_assignment_id', $selectedAssignment->id)
                ->get()
                ->keyBy('student_id');

            // Load granular sub scores for this assignment & period
            if ($selectedPeriod) {
                $subScores = StudentSubScore::where('class_subject_assignment_id', $selectedAssignment->id)
                    ->where('grading_period_id', $selectedPeriod->id)
                    ->get()
                    ->groupBy('student_id');
            }
        }

        // All students enrolled in this homeroom batch
        $allClassStudents = $class->students()->orderBy('admission_number')->get();

        // Students specifically enrolled in the selected course/subject
        if ($selectedAssignment) {
            $enrolled = $selectedAssignment->enrolledStudents()
                ->wherePivot('enrollment_status', 'ENROLLED')
                ->orderBy('admission_number')
                ->get();

            if ($enrolled->isEmpty() && $allClassStudents->isNotEmpty()) {
                foreach ($allClassStudents as $std) {
                    CourseEnrollment::firstOrCreate([
                        'school_id' => $class->school_id,
                        'class_subject_assignment_id' => $selectedAssignment->id,
                        'student_id' => $std->id,
                    ], [
                        'enrollment_status' => 'ENROLLED',
                        'enrolled_at' => now(),
                    ]);
                }
                $students = $allClassStudents;
            } else {
                $students = $enrolled;
            }
        } else {
            $students = $allClassStudents;
        }

        // Grading system tiers
        $gradingSystem = SchoolGradingSystem::where('school_id', $class->school_id)->first();
        $gradingCutoffs = $gradingSystem ? ($gradingSystem->cutoffs_json ?? []) : [];

        // All subjects broadsheet matrix (student_id => assignment_id => GradeRecord)
        $allAssignmentIds = $assignments->pluck('id')->toArray();
        $broadsheetRecords = GradeRecord::whereIn('class_subject_assignment_id', $allAssignmentIds)
            ->get()
            ->groupBy('student_id');

        // Stats for selected assignment
        $gradedCount = 0;
        $totalScoresSum = 0;
        $passingCount = 0;
        $passLimit = $selectedAssignment ? ($selectedAssignment->passing_mark ?? ($effectiveRule->pass_limit ?? 60.0)) : 60.0;

        foreach ($students as $std) {
            $gr = $gradeRecords->get($std->id);
            if ($gr && $gr->total_score !== null) {
                $gradedCount++;
                $totalScoresSum += $gr->total_score;
                if ($gr->total_score >= $passLimit) {
                    $passingCount++;
                }
            }
        }

        $courseAvg = $gradedCount > 0 ? round($totalScoresSum / $gradedCount, 1) : null;
        $coursePassRate = $gradedCount > 0 ? round(($passingCount / $gradedCount) * 100, 1) : null;

        return [
            'assignments' => $assignments,
            'selectedAssignment' => $selectedAssignment,
            'effectiveRule' => $effectiveRule,
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'categories' => $categories,
            'subScores' => $subScores,
            'gradeRecords' => $gradeRecords,
            'students' => $students,
            'allClassStudents' => $allClassStudents,
            'gradingSystem' => $gradingSystem,
            'gradingCutoffs' => $gradingCutoffs,
            'broadsheetRecords' => $broadsheetRecords,
            'courseAvg' => $courseAvg,
            'coursePassRate' => $coursePassRate,
            'gradedCount' => $gradedCount,
            'passLimit' => $passLimit,
            'viewMode' => $request->get('view_mode', 'detail')
        ];
    }

    /**
     * Save all grades for a batch in a subject / period.
     */
    public function saveBatchGradebook(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $class = SchoolClass::where('school_id', $activeSchool->school_id)->findOrFail($id);

        $validated = $request->validate([
            'assignment_id' => 'required|exists:class_subject_assignments,id',
            'period_id' => 'nullable|integer',
            'score_entry_mode' => 'nullable|in:weight,percent',
            'grades' => 'required|array',
        ]);

        $entryMode = $request->input('score_entry_mode', 'weight');

        $assignment = ClassSubjectAssignment::where('school_id', $activeSchool->school_id)
            ->where('class_id', $class->id)
            ->findOrFail($validated['assignment_id']);

        $periodId = !empty($validated['period_id']) ? (int) $validated['period_id'] : null;
        $period = $periodId ? AcademicGradingPeriod::find($periodId) : null;

        $gradingService = app(\App\Services\GradingCalculationService::class);
        $gradingSystem = SchoolGradingSystem::where('school_id', $activeSchool->school_id)->first();
        $rule = $gradingService->resolveRuleForSubject($assignment->subject, $assignment);

        $savedCount = 0;

        foreach ($validated['grades'] as $studentId => $data) {
            $student = Student::where('school_id', $activeSchool->school_id)
                ->where('class_id', $class->id)
                ->find($studentId);

            if (!$student) continue;

            $calculatedTotal = 0.0;
            $cwScore = 0.0;
            $examScore = 0.0;
            $hasCategoryScores = false;

            if (!empty($data['categories']) && is_array($data['categories'])) {
                foreach ($data['categories'] as $catId => $rawScore) {
                    if ($rawScore === null || $rawScore === '') continue;
                    $hasCategoryScores = true;
                    $rawScore = (float) $rawScore;
                    $cat = GradingCategory::find($catId);
                    if (!$cat) continue;

                    $catWeight = (float) $cat->weight > 0 ? (float) $cat->weight : (float) ($cat->max_score ?? 100.0);

                    // Weighted score contribution based on entry mode
                    if ($entryMode === 'percent') {
                        $weightedScore = ($rawScore / 100.0) * $catWeight;
                        $storedMax = 100.0;
                    } else {
                        // Category Weight scale: entered directly out of category weight (e.g. out of 30)
                        $clampedScore = min(max($rawScore, 0.0), $catWeight);
                        $weightedScore = $clampedScore;
                        $storedMax = $catWeight;
                    }
                    $calculatedTotal += $weightedScore;

                    if (stripos($cat->category_name, 'exam') !== false || stripos($cat->category_name, 'terminal') !== false) {
                        $examScore += $weightedScore;
                    } else {
                        $cwScore += $weightedScore;
                    }

                    StudentSubScore::updateOrCreate(
                        [
                            'school_id' => $activeSchool->school_id,
                            'student_id' => $student->id,
                            'class_subject_assignment_id' => $assignment->id,
                            'grading_period_id' => $period ? $period->id : 0,
                            'grading_category_id' => $cat->id,
                        ],
                        [
                            'raw_score' => $rawScore,
                            'max_score' => $storedMax,
                            'weighted_score' => round($weightedScore, 2),
                        ]
                    );
                }
            }

            if (!$hasCategoryScores) {
                $cw = isset($data['cw_score']) && $data['cw_score'] !== '' ? (float) $data['cw_score'] : null;
                $exam = isset($data['exam_score']) && $data['exam_score'] !== '' ? (float) $data['exam_score'] : null;
                $cwScore = $cw ?? 0.0;
                $examScore = $exam ?? 0.0;
                $calculatedTotal = $cwScore + $examScore;
            }

            if (isset($data['total_score']) && $data['total_score'] !== '' && !$hasCategoryScores) {
                $calculatedTotal = (float) $data['total_score'];
            }

            $gradeInfo = $gradingSystem 
                ? $gradingService->calculateGradeAndGpa($calculatedTotal, $gradingSystem) 
                : ['letter_grade' => null, 'gpa' => null];

            GradeRecord::updateOrCreate(
                [
                    'school_id' => $activeSchool->school_id,
                    'class_subject_assignment_id' => $assignment->id,
                    'student_id' => $student->id,
                ],
                [
                    'cw_score' => round($cwScore, 2),
                    'exam_score' => round($examScore, 2),
                    'total_score' => round($calculatedTotal, 2),
                    'letter_grade' => $gradeInfo['letter_grade'],
                    'gpa_point' => $gradeInfo['gpa'],
                    'teacher_remarks' => $data['teacher_remarks'] ?? null,
                    'academic_cycle' => '2026-2027',
                    'term_name' => $period ? $period->period_name : 'Term 1',
                ]
            );

            $savedCount++;
        }

        $periodName = $period ? $period->period_name : 'General Assessment';
        return redirect()->back()
            ->with('success', "✓ Successfully saved grades for {$savedCount} students in {$assignment->subject->subject_name} ({$periodName})!");
    }

    /**
     * AJAX quick-save a single student's grade row.
     */
    public function saveSingleStudentGradebook(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $class = SchoolClass::where('school_id', $activeSchool->school_id)->findOrFail($id);

        $validated = $request->validate([
            'assignment_id' => 'required|exists:class_subject_assignments,id',
            'period_id' => 'nullable|integer',
            'student_id' => 'required|exists:students,id',
            'score_entry_mode' => 'nullable|in:weight,percent',
            'categories' => 'nullable|array',
            'cw_score' => 'nullable|numeric|min:0',
            'exam_score' => 'nullable|numeric|min:0',
            'total_score' => 'nullable|numeric|min:0',
            'teacher_remarks' => 'nullable|string|max:255'
        ]);

        $entryMode = $request->input('score_entry_mode', 'weight');

        $assignment = ClassSubjectAssignment::where('school_id', $activeSchool->school_id)
            ->where('class_id', $class->id)
            ->findOrFail($validated['assignment_id']);

        $student = Student::where('school_id', $activeSchool->school_id)
            ->where('class_id', $class->id)
            ->findOrFail($validated['student_id']);

        $periodId = !empty($validated['period_id']) ? (int) $validated['period_id'] : null;
        $period = $periodId ? AcademicGradingPeriod::find($periodId) : null;

        $gradingService = app(\App\Services\GradingCalculationService::class);
        $gradingSystem = SchoolGradingSystem::where('school_id', $activeSchool->school_id)->first();

        $calculatedTotal = 0.0;
        $cwScore = 0.0;
        $examScore = 0.0;
        $hasCategories = false;

        if (!empty($validated['categories']) && is_array($validated['categories'])) {
            foreach ($validated['categories'] as $catId => $rawScore) {
                if ($rawScore === null || $rawScore === '') continue;
                $hasCategories = true;
                $rawScore = (float) $rawScore;
                $cat = GradingCategory::find($catId);
                if (!$cat) continue;

                $catWeight = (float) $cat->weight > 0 ? (float) $cat->weight : (float) ($cat->max_score ?? 100.0);

                if ($entryMode === 'percent') {
                    $weightedScore = ($rawScore / 100.0) * $catWeight;
                    $storedMax = 100.0;
                } else {
                    $clampedScore = min(max($rawScore, 0.0), $catWeight);
                    $weightedScore = $clampedScore;
                    $storedMax = $catWeight;
                }
                $calculatedTotal += $weightedScore;

                if (stripos($cat->category_name, 'exam') !== false || stripos($cat->category_name, 'terminal') !== false) {
                    $examScore += $weightedScore;
                } else {
                    $cwScore += $weightedScore;
                }

                StudentSubScore::updateOrCreate(
                    [
                        'school_id' => $activeSchool->school_id,
                        'student_id' => $student->id,
                        'class_subject_assignment_id' => $assignment->id,
                        'grading_period_id' => $period ? $period->id : 0,
                        'grading_category_id' => $cat->id,
                    ],
                    [
                        'raw_score' => $rawScore,
                        'max_score' => $storedMax,
                        'weighted_score' => round($weightedScore, 2),
                    ]
                );
            }
        }

        if (!$hasCategories) {
            $cwScore = isset($validated['cw_score']) ? (float) $validated['cw_score'] : 0.0;
            $examScore = isset($validated['exam_score']) ? (float) $validated['exam_score'] : 0.0;
            $calculatedTotal = isset($validated['total_score']) ? (float) $validated['total_score'] : ($cwScore + $examScore);
        }

        $gradeInfo = $gradingSystem 
            ? $gradingService->calculateGradeAndGpa($calculatedTotal, $gradingSystem) 
            : ['letter_grade' => null, 'gpa' => null];

        GradeRecord::updateOrCreate(
            [
                'school_id' => $activeSchool->school_id,
                'class_subject_assignment_id' => $assignment->id,
                'student_id' => $student->id,
            ],
            [
                'cw_score' => round($cwScore, 2),
                'exam_score' => round($examScore, 2),
                'total_score' => round($calculatedTotal, 2),
                'letter_grade' => $gradeInfo['letter_grade'],
                'gpa_point' => $gradeInfo['gpa'],
                'teacher_remarks' => $validated['teacher_remarks'] ?? null,
                'academic_cycle' => '2026-2027',
                'term_name' => $period ? $period->period_name : 'Term 1',
            ]
        );

        $passLimit = $assignment->passing_mark ?? 60.0;

        return response()->json([
            'success' => true,
            'student_id' => $student->id,
            'total_score' => round($calculatedTotal, 2),
            'letter_grade' => $gradeInfo['letter_grade'],
            'gpa' => $gradeInfo['gpa'],
            'is_passed' => $calculatedTotal >= $passLimit,
            'message' => 'Grade saved successfully'
        ]);
    }

    /**
     * Download Excel template for Gradebook marksheet pre-filled with students and categories.
     */
    public function downloadGradebookTemplate(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);
        $class = SchoolClass::where('school_id', $activeSchool->school_id)->findOrFail($id);

        $assignmentId = $request->get('assignment_id');
        $assignment = ClassSubjectAssignment::where('school_id', $activeSchool->school_id)
            ->where('class_id', $class->id)
            ->findOrFail($assignmentId);

        $periodId = $request->get('period_id');
        $period = $periodId ? AcademicGradingPeriod::find($periodId) : null;
        $scoreEntryMode = $request->get('score_entry_mode', 'weight');

        $service = app(\App\Services\GradebookSpreadsheetService::class);
        $tempPath = $service->generateTemplate($assignment, $period, $activeSchool, $scoreEntryMode);

        $safeClass = preg_replace('/[^A-Za-z0-9_-]/', '_', $class->class_name);
        $safeSubject = preg_replace('/[^A-Za-z0-9_-]/', '_', $assignment->subject->subject_code ?? 'Subject');
        $safePeriod = $period ? preg_replace('/[^A-Za-z0-9_-]/', '_', $period->period_name) : 'General';
        $safeScale = $scoreEntryMode === 'percent' ? 'Scale100' : 'ScaleWeight';
        $fileName = "Gradebook_{$safeClass}_{$safeSubject}_{$safePeriod}_{$safeScale}.xlsx";

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Upload spreadsheet (.xlsx, .xls, .csv) with grades for any category or all categories.
     */
    public function uploadGradebookSheet(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);
        $class = SchoolClass::where('school_id', $activeSchool->school_id)->findOrFail($id);

        $validated = $request->validate([
            'assignment_id' => 'required|exists:class_subject_assignments,id',
            'period_id' => 'nullable|integer',
            'category_id' => 'nullable',
            'score_entry_mode' => 'nullable|in:weight,percent',
            'sheet_file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        $assignment = ClassSubjectAssignment::where('school_id', $activeSchool->school_id)
            ->where('class_id', $class->id)
            ->findOrFail($validated['assignment_id']);

        $periodId = !empty($validated['period_id']) ? (int) $validated['period_id'] : null;
        $period = $periodId ? AcademicGradingPeriod::find($periodId) : null;
        $targetCategoryId = !empty($validated['category_id']) ? (int) $validated['category_id'] : null;
        $scoreEntryMode = $request->input('score_entry_mode', 'weight');

        $file = $request->file('sheet_file');
        $service = app(\App\Services\GradebookSpreadsheetService::class);

        try {
            $result = $service->importGradesFromSpreadsheet(
                $file->getPathname(),
                $assignment,
                $period,
                $targetCategoryId,
                $activeSchool,
                $scoreEntryMode
            );

            if ($result['imported_students'] > 0) {
                $cats = !empty($result['updated_categories']) ? implode(', ', $result['updated_categories']) : 'Active Categories';
                $periodName = $period ? $period->period_name : 'General Grading';
                $msg = "✓ Successfully imported grades for {$result['imported_students']} students in {$assignment->subject->subject_name} ({$periodName}) into [{$cats}]!";
                if (!empty($result['warnings'])) {
                    $msg .= " (" . count($result['warnings']) . " warnings: check student IDs)";
                }
                return redirect()->back()->with('success', $msg);
            } else {
                $err = !empty($result['warnings']) ? implode(' ', array_slice($result['warnings'], 0, 3)) : "No student marks could be matched from the uploaded file.";
                return redirect()->back()->with('error', "⚠️ Upload failed: {$err}");
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "⚠️ Spreadsheet processing error: " . $e->getMessage());
        }
    }

    /**
     * Update School Class details.
     */
    public function updateClass(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $class = SchoolClass::where('school_id', $activeSchool->school_id)->findOrFail($id);

        $validated = $request->validate([
            'class_name' => 'required|string|max:100',
            'grade_id' => 'required|exists:school_grade_levels,id',
            'homeroom_teacher' => 'nullable|string|max:150',
            'max_capacity' => 'nullable|integer|min:1'
        ]);

        $class->update([
            'class_name' => trim($validated['class_name']),
            'grade_id' => $validated['grade_id'],
            'homeroom_teacher' => $validated['homeroom_teacher'] ?? null,
            'max_capacity' => $validated['max_capacity'] ?? 30
        ]);

        return redirect()->route('config.classes.profile', $class->id)
            ->with('success', "✓ Class '{$class->class_name}' details updated successfully.");
    }

    /**
     * Enroll a new student directly into a class.
     */
    public function enrollStudentInClass(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $class = SchoolClass::where('school_id', $activeSchool->school_id)->findOrFail($id);

        $validated = $request->validate([
            'admission_number' => 'required|string|max:50',
            'full_name' => 'required|string|max:150',
            'gender' => 'nullable|string|max:10',
            'status' => 'nullable|string|max:20'
        ]);

        // Check admission number uniqueness in school
        $existing = Student::where('school_id', $activeSchool->school_id)
            ->where('admission_number', trim($validated['admission_number']))
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', "Student with Admission #{$validated['admission_number']} already exists.");
        }

        $student = Student::create([
            'school_id' => $activeSchool->school_id,
            'class_id' => $class->id,
            'admission_number' => trim($validated['admission_number']),
            'full_name' => trim($validated['full_name']),
            'gender' => $validated['gender'] ?? 'Other',
            'status' => $validated['status'] ?? 'ACTIVE'
        ]);

        return redirect()->route('config.classes.profile', $class->id)
            ->with('success', "✓ Student '{$student->full_name}' ({$student->admission_number}) enrolled in {$class->class_name}.");
    }

    /**
     * Display Course-Class Profile (ClassSubjectAssignment) - specific course in a specific class.
     */
    public function classSubjectProfile(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $assignment = ClassSubjectAssignment::with([
            'schoolClass.gradeLevel.division',
            'schoolClass.students.attendanceRecords',
            'subject.gradingRule',
            'gradingRule',
            'gradeRecords.student'
        ])
        ->where('school_id', $activeSchool->school_id)
        ->find($id);

        if (!$assignment) {
            abort(404, "Course-Class assignment #{$id} not found or does not belong to active school.");
        }

        $gradingCalculationService = app(\App\Services\GradingCalculationService::class);
        $effectiveRule = $gradingCalculationService->resolveRuleForSubject($assignment->subject, $assignment);

        if ($effectiveRule) {
            $effectiveRule->load([
                'periods.categories.subCategories',
                'periods.subPeriods.categories.subCategories'
            ]);
        }

        $allClassStudents = $assignment->schoolClass ? $assignment->schoolClass->students()->orderBy('admission_number')->get() : collect();

        $enrolled = $assignment->enrolledStudents()
            ->wherePivot('enrollment_status', 'ENROLLED')
            ->orderBy('admission_number')
            ->get();

        if ($enrolled->isEmpty() && $allClassStudents->isNotEmpty()) {
            foreach ($allClassStudents as $std) {
                CourseEnrollment::firstOrCreate([
                    'school_id' => $activeSchool->school_id,
                    'class_subject_assignment_id' => $assignment->id,
                    'student_id' => $std->id,
                ], [
                    'enrollment_status' => 'ENROLLED',
                    'enrolled_at' => now(),
                ]);
            }
            $students = $allClassStudents;
        } else {
            $students = $enrolled;
        }

        $gradeRecords = $assignment->gradeRecords->keyBy('student_id');

        // Course metrics
        $recordsWithScores = $assignment->gradeRecords->filter(fn($r) => $r->total_score !== null);
        $avgScore = $recordsWithScores->isNotEmpty() ? round($recordsWithScores->avg('total_score'), 1) : null;
        $highestScore = $recordsWithScores->isNotEmpty() ? round($recordsWithScores->max('total_score'), 1) : null;
        $lowestScore = $recordsWithScores->isNotEmpty() ? round($recordsWithScores->min('total_score'), 1) : null;

        $passLimit = $assignment->passing_mark ?? ($assignment->subject->pass_limit ?? ($effectiveRule->pass_limit ?? 60.0));
        $passingCount = $recordsWithScores->filter(fn($r) => $r->total_score >= $passLimit)->count();
        $passRate = $recordsWithScores->isNotEmpty() ? round(($passingCount / $recordsWithScores->count()) * 100, 1) : null;

        // Grade distribution
        $distribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];
        foreach ($recordsWithScores as $r) {
            $letter = strtoupper(substr($r->letter_grade ?? '', 0, 1));
            if (isset($distribution[$letter])) {
                $distribution[$letter]++;
            } else {
                if ($r->total_score >= 90) $distribution['A']++;
                elseif ($r->total_score >= 80) $distribution['B']++;
                elseif ($r->total_score >= 70) $distribution['C']++;
                elseif ($r->total_score >= 60) $distribution['D']++;
                else $distribution['F']++;
            }
        }

        $gradingTemplates = GradingStructureRule::where('school_id', $activeSchool->school_id)
            ->where('is_template', true)
            ->get();

        return view('configuration.course_class_profile', compact(
            'activeSchool',
            'assignment',
            'effectiveRule',
            'students',
            'allClassStudents',
            'gradeRecords',
            'avgScore',
            'highestScore',
            'lowestScore',
            'passLimit',
            'passRate',
            'distribution',
            'gradingTemplates'
        ));
    }

    /**
     * Update Course-Class assignment parameters.
     */
    public function updateClassSubject(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $assignment = ClassSubjectAssignment::where('school_id', $activeSchool->school_id)->findOrFail($id);

        $validated = $request->validate([
            'assigned_teacher' => 'nullable|string|max:150',
            'credit_hours' => 'required|numeric|min:0.1|max:20',
            'cw_weight' => 'required|numeric|min:0|max:100',
            'exam_weight' => 'required|numeric|min:0|max:100',
            'passing_mark' => 'nullable|numeric|min:0|max:100',
            'grading_rule_id' => 'nullable|exists:grading_structure_rules,id'
        ]);

        $assignment->update([
            'assigned_teacher' => $validated['assigned_teacher'] ?? null,
            'credit_hours' => $validated['credit_hours'],
            'cw_weight' => $validated['cw_weight'],
            'exam_weight' => $validated['exam_weight'],
            'passing_mark' => $validated['passing_mark'] ?? 60.0,
            'grading_rule_id' => array_key_exists('grading_rule_id', $validated) ? ($validated['grading_rule_id'] ?: null) : $assignment->grading_rule_id
        ]);

        return redirect()->back()
            ->with('success', "✓ Course-Class parameters updated successfully.");
    }

    /**
     * Quick Record / Update Grade for a student in this Course-Class.
     */
    public function recordClassSubjectGrade(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $assignment = ClassSubjectAssignment::where('school_id', $activeSchool->school_id)->findOrFail($id);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'cw_score' => 'nullable|numeric|min:0',
            'exam_score' => 'nullable|numeric|min:0',
            'total_score' => 'nullable|numeric|min:0|max:100',
            'teacher_remarks' => 'nullable|string|max:255'
        ]);

        $cw = isset($validated['cw_score']) ? (float)$validated['cw_score'] : null;
        $exam = isset($validated['exam_score']) ? (float)$validated['exam_score'] : null;
        
        $total = isset($validated['total_score']) 
            ? (float)$validated['total_score'] 
            : (($cw !== null || $exam !== null) ? (($cw ?? 0) + ($exam ?? 0)) : 0.0);

        // Grade calculation service for letter & GPA
        $gradingService = app(\App\Services\GradingCalculationService::class);
        $gradingSystem = SchoolGradingSystem::where('school_id', $activeSchool->school_id)->first();
        $gradeInfo = $gradingSystem ? $gradingService->calculateGradeAndGpa($total, $gradingSystem) : ['letter_grade' => null, 'gpa' => null];

        $record = GradeRecord::updateOrCreate(
            [
                'school_id' => $activeSchool->school_id,
                'class_subject_assignment_id' => $assignment->id,
                'student_id' => $validated['student_id'],
            ],
            [
                'cw_score' => $cw ?? 0.0,
                'exam_score' => $exam ?? 0.0,
                'total_score' => $total,
                'letter_grade' => $gradeInfo['letter_grade'],
                'gpa_point' => $gradeInfo['gpa'],
                'teacher_remarks' => $validated['teacher_remarks'] ?? null,
                'academic_cycle' => '2026-2027',
                'term_name' => 'Term 1'
            ]
        );

        return redirect()->back()
            ->with('success', "✓ Mark for student updated successfully (Total: {$total}).");
    }

    /**
     * Update the student roster for a course-class section.
     */
    public function updateCourseEnrollments(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $assignment = ClassSubjectAssignment::where('school_id', $activeSchool->school_id)
            ->with(['schoolClass', 'subject'])
            ->findOrFail($id);

        $validated = $request->validate([
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id'
        ]);

        $selectedIds = collect($validated['student_ids'] ?? [])->map(fn($v) => (int)$v)->all();

        // All eligible students from class
        $eligibleStudents = $assignment->schoolClass ? $assignment->schoolClass->students : collect();

        $enrolledCount = 0;
        foreach ($eligibleStudents as $std) {
            if (in_array($std->id, $selectedIds)) {
                CourseEnrollment::updateOrCreate(
                    [
                        'school_id' => $activeSchool->school_id,
                        'class_subject_assignment_id' => $assignment->id,
                        'student_id' => $std->id,
                    ],
                    [
                        'enrollment_status' => 'ENROLLED',
                        'enrolled_at' => now(),
                    ]
                );
                $enrolledCount++;
            } else {
                CourseEnrollment::where('class_subject_assignment_id', $assignment->id)
                    ->where('student_id', $std->id)
                    ->delete();
            }
        }

        $className = $assignment->schoolClass ? $assignment->schoolClass->class_name : 'Batch';
        return redirect()->back()
            ->with('success', "✓ Updated course roster: {$enrolledCount} students are now enrolled in {$assignment->subject->subject_name} for {$className}!");
    }

    /**
     * AJAX toggle enrollment of a single student in a course.
     */
    public function toggleStudentCourseEnrollment(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $assignment = ClassSubjectAssignment::where('school_id', $activeSchool->school_id)->findOrFail($id);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'action' => 'required|in:enroll,unenroll'
        ]);

        $studentId = (int) $validated['student_id'];

        if ($validated['action'] === 'enroll') {
            CourseEnrollment::updateOrCreate(
                [
                    'school_id' => $activeSchool->school_id,
                    'class_subject_assignment_id' => $assignment->id,
                    'student_id' => $studentId,
                ],
                [
                    'enrollment_status' => 'ENROLLED',
                    'enrolled_at' => now(),
                ]
            );
            $status = 'ENROLLED';
        } else {
            CourseEnrollment::where('class_subject_assignment_id', $assignment->id)
                ->where('student_id', $studentId)
                ->delete();
            $status = 'UNENROLLED';
        }

        return response()->json([
            'success' => true,
            'student_id' => $studentId,
            'status' => $status,
            'enrolled_count' => $assignment->enrolledStudents()->count()
        ]);
    }
}
