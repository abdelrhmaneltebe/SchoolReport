<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School;
use App\Models\MasterSubject;
use App\Models\AcademicGradingPeriod;
use App\Models\GradingStructureRule;
use App\Models\GradingCategory;
use App\Models\GradingTemplateCategory;

class MultiTierGradingSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::first();
        if (!$school) return;

        // Clean previous multi-tier records safely
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        GradingCategory::truncate();
        GradingTemplateCategory::truncate();
        AcademicGradingPeriod::truncate();
        GradingStructureRule::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $catList5 = [
            ['name' => 'Cat1 (Homework & Engagement)', 'weight' => 20.00],
            ['name' => 'Cat2 (Quizzes & Assignments)', 'weight' => 30.00],
            ['name' => 'Cat3 (Midterm Assessment)', 'weight' => 10.00],
            ['name' => 'Cat4 (Projects & Practical)', 'weight' => 25.00],
            ['name' => 'Cat5 (Terminal Exam)', 'weight' => 15.00],
        ];

        $subWeights = [
            ['name' => 'Sub1 (Component A)', 'weight' => 25.00],
            ['name' => 'Sub2 (Component B)', 'weight' => 20.00],
            ['name' => 'Sub3 (Component C)', 'weight' => 30.00],
            ['name' => 'Sub4 (Component D)', 'weight' => 25.00],
        ];

        // ==============================================================
        // 1. TEMPLATE CASE 1: 2-Tier (Main Period -> Main Category)
        // ==============================================================
        $t1 = GradingStructureRule::create([
            'school_id' => $school->school_id,
            'rule_name' => 'Case 1 Template: 2-Tier (Semesters → Main Categories)',
            'is_template' => true,
            'template_key' => 'TEMPLATE_CASE_1',
            'description' => 'Direct category breakdown inside Semesters (no sub-periods). Semester 1 (50%) & Semester 2 (50%) each contain Cat 1..5 summing to 100%.',
            'scope_type' => 'ALL_COURSES',
            'scenario_depth' => '2_TIER_MAIN_CAT',
            'pass_limit' => 60.00,
            'total_grade' => 100.00,
            'credit_hours' => 1.00,
            'is_active' => false
        ]);

        $t1_sem1 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t1->id,
            'period_name' => 'Ex: Semester 1',
            'period_type' => 'MAIN',
            'weight' => 50.00,
            'start_date' => '2026-09-01',
            'end_date' => '2027-01-20',
            'display_order' => 1
        ]);

        $t1_sem2 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t1->id,
            'period_name' => 'Semester 2',
            'period_type' => 'MAIN',
            'weight' => 50.00,
            'start_date' => '2027-02-01',
            'end_date' => '2027-06-25',
            'display_order' => 2
        ]);

        foreach ([$t1_sem1, $t1_sem2] as $sem) {
            foreach ($catList5 as $cIdx => $c) {
                GradingCategory::create([
                    'school_id' => $school->school_id,
                    'rule_id' => $t1->id,
                    'grading_period_id' => $sem->id,
                    'category_name' => $c['name'],
                    'category_type' => 'MAIN',
                    'weight' => $c['weight'],
                    'display_order' => $cIdx + 1
                ]);
            }
        }

        // Seed Master Template Categories for Template 1
        foreach ($catList5 as $cIdx => $c) {
            GradingTemplateCategory::create([
                'school_id' => $school->school_id,
                'rule_id' => $t1->id,
                'category_name' => $c['name'],
                'category_type' => 'CUSTOM',
                'default_weight' => $c['weight'],
                'default_max_score' => 100.00,
                'display_order' => $cIdx + 1
            ]);
        }

        // ==============================================================
        // 2. TEMPLATE CASE 2: 3-Tier (Main -> Sub-Period -> Main Category)
        // ==============================================================
        $t2 = GradingStructureRule::create([
            'school_id' => $school->school_id,
            'rule_name' => 'Case 2 Template: 3-Tier (Semesters → Quarters → Categories)',
            'is_template' => true,
            'template_key' => 'TEMPLATE_CASE_2',
            'description' => 'Semester 1 (50%) is split into Quarter 1 (50%) & Quarter 2 (50%); Semester 2 (50%) into Quarter 3 & 4. Each Quarter contains Cat 1..5 (100%).',
            'scope_type' => 'ALL_COURSES',
            'scenario_depth' => '3_TIER_SUB_PERIOD_MAIN_CAT',
            'pass_limit' => 60.00,
            'total_grade' => 100.00,
            'credit_hours' => 1.00,
            'is_active' => false
        ]);

        $t2_sem1 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t2->id,
            'period_name' => 'Ex: Semester 1',
            'period_type' => 'MAIN',
            'weight' => 50.00,
            'start_date' => '2026-09-01',
            'end_date' => '2027-01-20',
            'display_order' => 1
        ]);

        $t2_sem2 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t2->id,
            'period_name' => 'Semester 2',
            'period_type' => 'MAIN',
            'weight' => 50.00,
            'start_date' => '2027-02-01',
            'end_date' => '2027-06-25',
            'display_order' => 2
        ]);

        $t2_q1 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t2->id,
            'parent_period_id' => $t2_sem1->id,
            'period_name' => 'Quarter 1',
            'period_type' => 'SUB',
            'weight' => 50.00,
            'start_date' => '2026-09-01',
            'end_date' => '2026-11-10',
            'display_order' => 1
        ]);

        $t2_q2 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t2->id,
            'parent_period_id' => $t2_sem1->id,
            'period_name' => 'Quarter 2',
            'period_type' => 'SUB',
            'weight' => 50.00,
            'start_date' => '2026-11-15',
            'end_date' => '2027-01-20',
            'display_order' => 2
        ]);

        $t2_q3 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t2->id,
            'parent_period_id' => $t2_sem2->id,
            'period_name' => 'Quarter 3',
            'period_type' => 'SUB',
            'weight' => 50.00,
            'start_date' => '2027-02-01',
            'end_date' => '2027-04-10',
            'display_order' => 1
        ]);

        $t2_q4 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t2->id,
            'parent_period_id' => $t2_sem2->id,
            'period_name' => 'Quarter 4',
            'period_type' => 'SUB',
            'weight' => 50.00,
            'start_date' => '2027-04-15',
            'end_date' => '2027-06-25',
            'display_order' => 2
        ]);

        foreach ([$t2_q1, $t2_q2, $t2_q3, $t2_q4] as $q) {
            foreach ($catList5 as $cIdx => $c) {
                GradingCategory::create([
                    'school_id' => $school->school_id,
                    'rule_id' => $t2->id,
                    'grading_period_id' => $q->id,
                    'category_name' => $c['name'],
                    'category_type' => 'MAIN',
                    'weight' => $c['weight'],
                    'display_order' => $cIdx + 1
                ]);
            }
        }

        // Seed Master Template Categories for Template 2
        foreach ($catList5 as $cIdx => $c) {
            GradingTemplateCategory::create([
                'school_id' => $school->school_id,
                'rule_id' => $t2->id,
                'category_name' => $c['name'],
                'category_type' => 'CUSTOM',
                'default_weight' => $c['weight'],
                'default_max_score' => 100.00,
                'display_order' => $cIdx + 1
            ]);
        }

        // ==============================================================
        // 3. TEMPLATE CASE 3: 4-Tier (Deep Sub-Categories) [ACTIVE DEFAULT]
        // ==============================================================
        $t3 = GradingStructureRule::create([
            'school_id' => $school->school_id,
            'rule_name' => 'Case 3 Template: 4-Tier (Deep Sub-Categories Hierarchy)',
            'is_template' => true,
            'template_key' => 'TEMPLATE_CASE_3',
            'description' => 'Full hierarchy: Semesters (50%) → Quarters (50%) → Cat1 (20%) [Sub1..4 = 100%] + Cat2 (30%) [Sub1..4 = 100%] + Cat3 (50% Terminal Exam).',
            'scope_type' => 'ALL_COURSES',
            'scenario_depth' => '4_TIER_FULL_SUB_CAT',
            'pass_limit' => 60.00,
            'total_grade' => 100.00,
            'credit_hours' => 1.00,
            'is_active' => true
        ]);

        $t3_sem1 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t3->id,
            'period_name' => 'Ex: Semester 1',
            'period_type' => 'MAIN',
            'weight' => 50.00,
            'start_date' => '2026-09-01',
            'end_date' => '2027-01-20',
            'display_order' => 1
        ]);

        $t3_sem2 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t3->id,
            'period_name' => 'Semester 2',
            'period_type' => 'MAIN',
            'weight' => 50.00,
            'start_date' => '2027-02-01',
            'end_date' => '2027-06-25',
            'display_order' => 2
        ]);

        $t3_q1 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t3->id,
            'parent_period_id' => $t3_sem1->id,
            'period_name' => 'Quarter 1',
            'period_type' => 'SUB',
            'weight' => 50.00,
            'start_date' => '2026-09-01',
            'end_date' => '2026-11-10',
            'display_order' => 1
        ]);

        $t3_q2 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t3->id,
            'parent_period_id' => $t3_sem1->id,
            'period_name' => 'Quarter 2',
            'period_type' => 'SUB',
            'weight' => 50.00,
            'start_date' => '2026-11-15',
            'end_date' => '2027-01-20',
            'display_order' => 2
        ]);

        $t3_q3 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t3->id,
            'parent_period_id' => $t3_sem2->id,
            'period_name' => 'Quarter 3',
            'period_type' => 'SUB',
            'weight' => 50.00,
            'start_date' => '2027-02-01',
            'end_date' => '2027-04-10',
            'display_order' => 1
        ]);

        $t3_q4 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t3->id,
            'parent_period_id' => $t3_sem2->id,
            'period_name' => 'Quarter 4',
            'period_type' => 'SUB',
            'weight' => 50.00,
            'start_date' => '2027-04-15',
            'end_date' => '2027-06-25',
            'display_order' => 2
        ]);

        foreach ([$t3_q1, $t3_q2, $t3_q3, $t3_q4] as $q) {
            // Cat1 (20%)
            $c1 = GradingCategory::create([
                'school_id' => $school->school_id,
                'rule_id' => $t3->id,
                'grading_period_id' => $q->id,
                'category_name' => 'Cat1',
                'category_type' => 'MAIN',
                'weight' => 20.00,
                'display_order' => 1
            ]);
            foreach ($subWeights as $sIdx => $sw) {
                GradingCategory::create([
                    'school_id' => $school->school_id,
                    'rule_id' => $t3->id,
                    'grading_period_id' => $q->id,
                    'parent_category_id' => $c1->id,
                    'category_name' => $sw['name'],
                    'category_type' => 'SUB',
                    'weight' => $sw['weight'],
                    'display_order' => $sIdx + 1
                ]);
            }

            // Cat2 (30%)
            $c2 = GradingCategory::create([
                'school_id' => $school->school_id,
                'rule_id' => $t3->id,
                'grading_period_id' => $q->id,
                'category_name' => 'Cat2',
                'category_type' => 'MAIN',
                'weight' => 30.00,
                'display_order' => 2
            ]);
            foreach ($subWeights as $sIdx => $sw) {
                GradingCategory::create([
                    'school_id' => $school->school_id,
                    'rule_id' => $t3->id,
                    'grading_period_id' => $q->id,
                    'parent_category_id' => $c2->id,
                    'category_name' => $sw['name'],
                    'category_type' => 'SUB',
                    'weight' => $sw['weight'],
                    'display_order' => $sIdx + 1
                ]);
            }

            // Cat3 (50%)
            GradingCategory::create([
                'school_id' => $school->school_id,
                'rule_id' => $t3->id,
                'grading_period_id' => $q->id,
                'category_name' => 'Cat3 (Terminal Assessment)',
                'category_type' => 'MAIN',
                'weight' => 50.00,
                'display_order' => 3
            ]);
        }

        // Seed Master Template Categories for Template 3
        GradingTemplateCategory::create([
            'school_id' => $school->school_id,
            'rule_id' => $t3->id,
            'category_name' => 'Cat1',
            'category_type' => 'CLASSWORK',
            'default_weight' => 20.00,
            'default_max_score' => 100.00,
            'sub_categories_json' => $subWeights,
            'display_order' => 1
        ]);
        GradingTemplateCategory::create([
            'school_id' => $school->school_id,
            'rule_id' => $t3->id,
            'category_name' => 'Cat2',
            'category_type' => 'QUIZ',
            'default_weight' => 30.00,
            'default_max_score' => 100.00,
            'sub_categories_json' => $subWeights,
            'display_order' => 2
        ]);
        GradingTemplateCategory::create([
            'school_id' => $school->school_id,
            'rule_id' => $t3->id,
            'category_name' => 'Cat3 (Terminal Assessment)',
            'category_type' => 'EXAM',
            'default_weight' => 50.00,
            'default_max_score' => 100.00,
            'sub_categories_json' => null,
            'display_order' => 3
        ]);

        // ==============================================================
        // 4. Course Specific Threshold Registry (Course, Pass Limit, Total Grade, Credit)
        // ==============================================================
        $math = MasterSubject::where('subject_code', 'MATH101')->first();
        if ($math) {
            GradingStructureRule::create([
                'school_id' => $school->school_id,
                'rule_name' => 'Advanced Mathematics Standard',
                'is_template' => false,
                'scope_type' => 'COURSE',
                'course_id' => $math->id,
                'scenario_depth' => '4_TIER_FULL_SUB_CAT',
                'pass_limit' => 60.00,
                'total_grade' => 100.00,
                'credit_hours' => 4.00,
                'is_active' => true
            ]);
        }

        $french = MasterSubject::where('subject_code', 'FR101')->first();
        if ($french) {
            GradingStructureRule::create([
                'school_id' => $school->school_id,
                'rule_name' => 'French Language Baccalaureate (0-20 Scale)',
                'is_template' => false,
                'scope_type' => 'COURSE',
                'course_id' => $french->id,
                'scenario_depth' => '3_TIER_SUB_PERIOD_MAIN_CAT',
                'pass_limit' => 10.00,
                'total_grade' => 20.00,
                'credit_hours' => 2.00,
                'is_active' => true
            ]);
        }

        $religion = MasterSubject::where('subject_code', 'ISL101')->first();
        if ($religion) {
            GradingStructureRule::create([
                'school_id' => $school->school_id,
                'rule_name' => 'Islamic & Moral Studies (0-50 Scale)',
                'is_template' => false,
                'scope_type' => 'COURSE',
                'course_id' => $religion->id,
                'scenario_depth' => '2_TIER_MAIN_CAT',
                'pass_limit' => 25.00,
                'total_grade' => 50.00,
                'credit_hours' => 1.00,
                'is_active' => true
            ]);
        }
    }
}
