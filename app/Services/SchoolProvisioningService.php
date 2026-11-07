<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use App\Models\SchoolAttendancePolicy;
use App\Models\SchoolGradingSystem;
use App\Models\SchoolDivision;
use App\Models\SchoolGradeLevel;
use App\Models\AcademicGradingPeriod;
use App\Models\GradingStructureRule;
use App\Models\GradingCategory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SchoolProvisioningService
{
    /**
     * Provision a brand-new individual school with all foundational pillars and an admin account.
     */
    public function provisionSchool(array $schoolData, ?array $adminData = null): School
    {
        return DB::transaction(function () use ($schoolData, $adminData) {
            $code = strtoupper(trim($schoolData['school_code']));
            $schoolId = 'SCH-' . $code . '-' . Str::upper(Str::random(3));

            // 1. Create School Record
            $school = School::create([
                'school_id' => $schoolId,
                'school_name' => $schoolData['school_name'],
                'school_code' => $code,
                'principal_name' => $schoolData['principal_name'] ?? 'School Principal',
                'contact_email' => $schoolData['contact_email'] ?? null,
                'contact_phone' => $schoolData['contact_phone'] ?? null,
                'address' => $schoolData['address'] ?? null,
                'city' => $schoolData['city'] ?? 'City',
                'country' => $schoolData['country'] ?? 'United States',
                'active_scale_model' => $schoolData['active_scale_model'] ?? 'PERCENTAGE_100',
                'plan_id' => $schoolData['plan_id'] ?? null,
                'subscription_status' => $schoolData['subscription_status'] ?? 'ACTIVE',
                'subscription_starts_at' => now(),
                'is_active' => true,
            ]);

            // Provision dedicated reports & customized deployment directories
            $school->ensureReportDirectoriesExist();

            // 2. Provision Pillar 1: School Attendance Policy
            SchoolAttendancePolicy::create([
                'school_id' => $school->school_id,
                'tracking_mode' => 'DAILY_HOMEROOM',
                'min_attendance_rate' => 85.00,
                'chronic_truancy_days' => 3,
                'tardies_per_absence' => 3
            ]);

            // 3. Provision Pillar 2: Base Grading System Cutoffs
            SchoolGradingSystem::create([
                'school_id' => $school->school_id,
                'scope_type' => 'SCHOOL_BASE',
                'grading_model' => 'LETTER_GRADE_GPA',
                'default_cw_weight' => 40.00,
                'default_exam_weight' => 60.00,
                'passing_score' => 60.00,
                'cutoffs_json' => [
                    ['grade' => 'A+', 'min' => 95, 'max' => 100, 'gpa' => 4.00, 'label' => 'High Honors'],
                    ['grade' => 'A',  'min' => 90, 'max' => 94.9, 'gpa' => 3.75, 'label' => 'Dean\'s List'],
                    ['grade' => 'B+', 'min' => 85, 'max' => 89.9, 'gpa' => 3.25, 'label' => 'Distinction'],
                    ['grade' => 'B',  'min' => 80, 'max' => 84.9, 'gpa' => 3.00, 'label' => 'Commendation'],
                    ['grade' => 'C+', 'min' => 75, 'max' => 79.9, 'gpa' => 2.50, 'label' => 'Satisfactory'],
                    ['grade' => 'C',  'min' => 70, 'max' => 74.9, 'gpa' => 2.00, 'label' => 'Average'],
                    ['grade' => 'D',  'min' => 60, 'max' => 69.9, 'gpa' => 1.00, 'label' => 'Minimum Pass'],
                    ['grade' => 'F',  'min' => 0,  'max' => 59.9, 'gpa' => 0.00, 'label' => 'Academic Warning']
                ]
            ]);

            // 4. Provision Pillar 3: Default Division & Grade Levels
            $div = SchoolDivision::create([
                'school_id' => $school->school_id,
                'division_name' => 'High School Division',
                'display_order' => 1
            ]);

            SchoolGradeLevel::create([
                'school_id' => $school->school_id,
                'division_id' => $div->id,
                'grade_name' => 'Grade 10',
                'grade_code' => 'G10',
                'display_order' => 1
            ]);

            SchoolGradeLevel::create([
                'school_id' => $school->school_id,
                'division_id' => $div->id,
                'grade_name' => 'Grade 11',
                'grade_code' => 'G11',
                'display_order' => 2
            ]);

            SchoolGradeLevel::create([
                'school_id' => $school->school_id,
                'division_id' => $div->id,
                'grade_name' => 'Grade 12',
                'grade_code' => 'G12',
                'display_order' => 3
            ]);

            // 5. Provision the 3 Standard Grading Templates for this Individual School
            $this->provisionDefaultGradingTemplates($school);

            // 6. Create Dedicated School Administrator User
            if ($adminData && !empty($adminData['email'])) {
                User::create([
                    'name' => $adminData['name'] ?? ($school->school_name . ' Admin'),
                    'email' => $adminData['email'],
                    'password' => Hash::make($adminData['password'] ?? 'password123'),
                    'role' => 'SCHOOL_ADMIN',
                    'school_id' => $school->school_id,
                    'status' => 'ACTIVE',
                    'phone' => $adminData['phone'] ?? null
                ]);
            }

            return $school;
        });
    }

    /**
     * Provision isolated Case 1, Case 2, and Case 3 templates for a newly created school.
     */
    protected function provisionDefaultGradingTemplates(School $school): void
    {
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

        // Template Case 1 (2-Tier)
        $t1 = GradingStructureRule::create([
            'school_id' => $school->school_id,
            'rule_name' => 'Case 1 Template: 2-Tier (Semesters → Main Categories)',
            'is_template' => true,
            'template_key' => 'TEMPLATE_CASE_1',
            'description' => 'Semester 1 (50%) & Semester 2 (50%) directly containing Cat 1..5 summing to 100%.',
            'scope_type' => 'ALL_COURSES',
            'scenario_depth' => '2_TIER_MAIN_CAT',
            'pass_limit' => 60.00,
            'total_grade' => 100.00,
            'credit_hours' => 1.00,
            'is_active' => false
        ]);

        $t1_s1 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t1->id,
            'period_name' => 'Semester 1',
            'period_type' => 'MAIN',
            'weight' => 50.00,
            'start_date' => date('Y-09-01'),
            'end_date' => date('Y-01-20', strtotime('+1 year')),
            'display_order' => 1
        ]);

        $t1_s2 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t1->id,
            'period_name' => 'Semester 2',
            'period_type' => 'MAIN',
            'weight' => 50.00,
            'start_date' => date('Y-02-01', strtotime('+1 year')),
            'end_date' => date('Y-06-25', strtotime('+1 year')),
            'display_order' => 2
        ]);

        foreach ([$t1_s1, $t1_s2] as $sem) {
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

        // Template Case 2 (3-Tier)
        $t2 = GradingStructureRule::create([
            'school_id' => $school->school_id,
            'rule_name' => 'Case 2 Template: 3-Tier (Semesters → Quarters → Categories)',
            'is_template' => true,
            'template_key' => 'TEMPLATE_CASE_2',
            'description' => 'Semester 1 (50%) [Q1 (50%), Q2 (50%)] & Semester 2 (50%) [Q3, Q4]. Each quarter contains Cat 1..5 (100%).',
            'scope_type' => 'ALL_COURSES',
            'scenario_depth' => '3_TIER_SUB_PERIOD_MAIN_CAT',
            'pass_limit' => 60.00,
            'total_grade' => 100.00,
            'credit_hours' => 1.00,
            'is_active' => false
        ]);

        $t2_s1 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t2->id,
            'period_name' => 'Semester 1',
            'period_type' => 'MAIN',
            'weight' => 50.00,
            'display_order' => 1
        ]);
        $t2_s2 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t2->id,
            'period_name' => 'Semester 2',
            'period_type' => 'MAIN',
            'weight' => 50.00,
            'display_order' => 2
        ]);

        $t2_q1 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t2->id,
            'parent_period_id' => $t2_s1->id,
            'period_name' => 'Quarter 1',
            'period_type' => 'SUB',
            'weight' => 50.00,
            'display_order' => 1
        ]);
        $t2_q2 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t2->id,
            'parent_period_id' => $t2_s1->id,
            'period_name' => 'Quarter 2',
            'period_type' => 'SUB',
            'weight' => 50.00,
            'display_order' => 2
        ]);
        $t2_q3 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t2->id,
            'parent_period_id' => $t2_s2->id,
            'period_name' => 'Quarter 3',
            'period_type' => 'SUB',
            'weight' => 50.00,
            'display_order' => 1
        ]);
        $t2_q4 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t2->id,
            'parent_period_id' => $t2_s2->id,
            'period_name' => 'Quarter 4',
            'period_type' => 'SUB',
            'weight' => 50.00,
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

        // Template Case 3 (4-Tier) - Active default
        $t3 = GradingStructureRule::create([
            'school_id' => $school->school_id,
            'rule_name' => 'Case 3 Template: 4-Tier (Deep Sub-Categories Hierarchy)',
            'is_template' => true,
            'template_key' => 'TEMPLATE_CASE_3',
            'description' => 'Full hierarchy: Semesters (50%) → Quarters (50%) → Cat1 (20%) [Sub1..4] + Cat2 (30%) [Sub1..4] + Cat3 (50%).',
            'scope_type' => 'ALL_COURSES',
            'scenario_depth' => '4_TIER_FULL_SUB_CAT',
            'pass_limit' => 60.00,
            'total_grade' => 100.00,
            'credit_hours' => 1.00,
            'is_active' => true
        ]);

        $t3_s1 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t3->id,
            'period_name' => 'Semester 1',
            'period_type' => 'MAIN',
            'weight' => 50.00,
            'display_order' => 1
        ]);

        $t3_s2 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t3->id,
            'period_name' => 'Semester 2',
            'period_type' => 'MAIN',
            'weight' => 50.00,
            'display_order' => 2
        ]);

        $t3_q1 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t3->id,
            'parent_period_id' => $t3_s1->id,
            'period_name' => 'Quarter 1',
            'period_type' => 'SUB',
            'weight' => 50.00,
            'display_order' => 1
        ]);

        $t3_q2 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t3->id,
            'parent_period_id' => $t3_s1->id,
            'period_name' => 'Quarter 2',
            'period_type' => 'SUB',
            'weight' => 50.00,
            'display_order' => 2
        ]);

        $t3_q3 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t3->id,
            'parent_period_id' => $t3_s2->id,
            'period_name' => 'Quarter 3',
            'period_type' => 'SUB',
            'weight' => 50.00,
            'display_order' => 1
        ]);

        $t3_q4 = AcademicGradingPeriod::create([
            'school_id' => $school->school_id,
            'rule_id' => $t3->id,
            'parent_period_id' => $t3_s2->id,
            'period_name' => 'Quarter 4',
            'period_type' => 'SUB',
            'weight' => 50.00,
            'display_order' => 2
        ]);

        foreach ([$t3_q1, $t3_q2, $t3_q3, $t3_q4] as $q) {
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

            GradingCategory::create([
                'school_id' => $school->school_id,
                'rule_id' => $t3->id,
                'grading_period_id' => $q->id,
                'category_name' => 'Cat3 (Terminal Exam)',
                'category_type' => 'MAIN',
                'weight' => 50.00,
                'display_order' => 3
            ]);
        }
    }
}
