<?php

namespace App\Services;

use App\Models\SchoolGradingSystem;
use App\Models\GradingStructureRule;
use App\Models\AcademicGradingPeriod;
use App\Models\GradingCategory;

class GradingCalculationService
{
    /**
     * Resolve the effective grading rule for a given subject or assignment.
     */
    public function resolveRuleForSubject(\App\Models\MasterSubject $subject, ?\App\Models\ClassSubjectAssignment $assignment = null): ?GradingStructureRule
    {
        if ($assignment && $assignment->grading_rule_id) {
            $rule = GradingStructureRule::find($assignment->grading_rule_id);
            if ($rule) return $rule;
        }

        if ($subject->grading_rule_id) {
            $rule = GradingStructureRule::find($subject->grading_rule_id);
            if ($rule) return $rule;
        }

        return GradingStructureRule::where('school_id', $subject->school_id)
            ->where('is_template', true)
            ->where('is_active', true)
            ->first() ?? GradingStructureRule::where('school_id', $subject->school_id)->first();
    }

    /**
     * Determine letter grade and GPA points for a given normalized percentage.
     */
    public function calculateGradeAndGpa(float $percentage, SchoolGradingSystem $gradingSystem): array
    {
        $cutoffs = $gradingSystem->cutoffs_json ?? [];

        foreach ($cutoffs as $tier) {
            if ($percentage >= $tier['min'] && $percentage <= $tier['max']) {
                return [
                    'letter_grade' => $tier['grade'],
                    'gpa' => (float) $tier['gpa'],
                    'label' => $tier['label'] ?? ''
                ];
            }
        }

        return [
            'letter_grade' => 'F',
            'gpa' => 0.00,
            'label' => 'Academic Warning'
        ];
    }

    /**
     * Compute multi-tier hierarchical grade roll-up across all 3 scenarios.
     *
     * @param array $subScoreMap [ 'period_id' => [ 'category_id' => score_obtained ] ]
     * @param GradingStructureRule $rule
     * @return array Calculated roll-up breakdown
     */
    public function calculateMultiTierGrade(array $subScoreMap, GradingStructureRule $rule): array
    {
        $periods = AcademicGradingPeriod::where('school_id', $rule->school_id)
            ->where('rule_id', $rule->id)
            ->whereNull('parent_period_id')
            ->with(['subPeriods', 'categories.subCategories'])
            ->orderBy('display_order')
            ->get();

        $mainPeriodResults = [];
        $finalYearlyGrade = 0.0;

        foreach ($periods as $mainPeriod) {
            $mainPeriodWeight = (float) $mainPeriod->weight;
            $mainPeriodScore = 0.0;

            if ($mainPeriod->subPeriods->isNotEmpty()) {
                // Scenario 2 or Scenario 3 (Hierarchical with Sub-Periods e.g. Quarters)
                $subPeriodResults = [];
                $accumulatedSubPeriodScore = 0.0;

                foreach ($mainPeriod->subPeriods as $subPeriod) {
                    $subPeriodWeight = (float) $subPeriod->weight;
                    $subPeriodScore = $this->calculatePeriodCategoryScore($subPeriod->id, $rule, $subScoreMap);
                    
                    $subPeriodResults[] = [
                        'period_id' => $subPeriod->id,
                        'period_name' => $subPeriod->period_name,
                        'weight' => $subPeriodWeight,
                        'score' => round($subPeriodScore, 2),
                        'weighted_contribution' => round(($subPeriodScore * $subPeriodWeight) / 100.0, 2)
                    ];

                    $accumulatedSubPeriodScore += ($subPeriodScore * $subPeriodWeight) / 100.0;
                }

                $mainPeriodScore = $accumulatedSubPeriodScore;
                $mainPeriodResults[] = [
                    'period_id' => $mainPeriod->id,
                    'period_name' => $mainPeriod->period_name,
                    'weight' => $mainPeriodWeight,
                    'score' => round($mainPeriodScore, 2),
                    'sub_periods' => $subPeriodResults,
                    'weighted_contribution' => round(($mainPeriodScore * $mainPeriodWeight) / 100.0, 2)
                ];
            } else {
                // Scenario 1 (Flat Main Periods e.g. Semester 1, Semester 2 directly with Categories)
                $mainPeriodScore = $this->calculatePeriodCategoryScore($mainPeriod->id, $rule, $subScoreMap);
                $mainPeriodResults[] = [
                    'period_id' => $mainPeriod->id,
                    'period_name' => $mainPeriod->period_name,
                    'weight' => $mainPeriodWeight,
                    'score' => round($mainPeriodScore, 2),
                    'sub_periods' => [],
                    'weighted_contribution' => round(($mainPeriodScore * $mainPeriodWeight) / 100.0, 2)
                ];
            }

            $finalYearlyGrade += ($mainPeriodScore * $mainPeriodWeight) / 100.0;
        }

        // Apply Course Total Grade Scale (e.g. 100%, 20 French scale, 50 Religion scale)
        $scaledScore = ($finalYearlyGrade / 100.0) * $rule->total_grade;
        $isPassed = $scaledScore >= $rule->pass_limit;

        return [
            'rule_name' => $rule->rule_name,
            'scenario_depth' => $rule->scenario_depth,
            'course_pass_limit' => $rule->pass_limit,
            'course_total_scale' => $rule->total_grade,
            'credit_hours' => $rule->credit_hours,
            'final_percentage' => round($finalYearlyGrade, 2),
            'scaled_score' => round($scaledScore, 2),
            'is_passed' => $isPassed,
            'periods_breakdown' => $mainPeriodResults
        ];
    }

    /**
     * Compute weighted categories score for a single period (Main or Sub).
     */
    protected function calculatePeriodCategoryScore(int $periodId, GradingStructureRule $rule, array $subScoreMap): float
    {
        $categories = GradingCategory::where('rule_id', $rule->id)
            ->where('grading_period_id', $periodId)
            ->whereNull('parent_category_id')
            ->with('subCategories')
            ->orderBy('display_order')
            ->get();

        if ($categories->isEmpty()) {
            return 0.0;
        }

        $totalPeriodScore = 0.0;

        foreach ($categories as $cat) {
            $catWeight = (float) $cat->weight;
            $catScore = 0.0;

            if ($cat->subCategories->isNotEmpty()) {
                // Scenario 3 (Sub-Categories e.g. Sub1 25%, Sub2 20%, Sub3 30%, Sub4 25%)
                $accumulatedSub = 0.0;
                foreach ($cat->subCategories as $subCat) {
                    $subWeight = (float) $subCat->weight;
                    $scoreValue = (float) ($subScoreMap[$periodId][$subCat->id] ?? 85.0); // fallback sample score
                    $accumulatedSub += ($scoreValue * $subWeight) / 100.0;
                }
                $catScore = $accumulatedSub;
            } else {
                // Direct Main Category score (Scenario 1 or Scenario 2)
                $catScore = (float) ($subScoreMap[$periodId][$cat->id] ?? 85.0);
            }

            $totalPeriodScore += ($catScore * $catWeight) / 100.0;
        }

        return $totalPeriodScore;
    }

    /**
     * Compute weighted GPA across student subject marks.
     */
    public function computeStudentWeightedAverage(array $subjectMarks): array
    {
        $totalQualityPoints = 0.0;
        $totalCredits = 0.0;
        $totalRawObtained = 0.0;
        $totalMaxPoints = 0.0;

        foreach ($subjectMarks as $mark) {
            $credits = (float) ($mark['credit_hours'] ?? 1.0);
            $score = (float) ($mark['total'] ?? 0.0);
            $max = (float) ($mark['max'] ?? 100.0);

            $gpaPoint = $mark['gpa_point'] ?? 0.0;

            $totalQualityPoints += ($gpaPoint * $credits);
            $totalCredits += $credits;
            $totalRawObtained += $score;
            $totalMaxPoints += $max;
        }

        $overallGpa = $totalCredits > 0 ? round($totalQualityPoints / $totalCredits, 2) : 0.00;
        $overallPercentage = $totalMaxPoints > 0 ? round(($totalRawObtained / $totalMaxPoints) * 100.0, 1) : 0.0;

        return [
            'total_credits' => $totalCredits,
            'overall_gpa' => $overallGpa,
            'overall_percentage' => $overallPercentage
        ];
    }
}
