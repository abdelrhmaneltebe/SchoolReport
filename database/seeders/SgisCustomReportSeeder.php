<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School;
use App\Models\SchoolDivision;
use App\Models\SchoolGradeLevel;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\MasterSubject;
use App\Models\ClassSubjectAssignment;
use App\Models\AcademicGradingPeriod;
use App\Models\GradeRecord;
use App\Models\StudentSubScore;
use App\Models\ReportTemplate;

class SgisCustomReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schoolId = 'SCH-SGIS-GAM';
        $school = School::firstOrCreate(
            ['school_id' => $schoolId],
            [
                'school_name' => 'Sun of Glory',
                'school_code' => 'SGIS',
                'is_active' => true,
            ]
        );

        // 1. Register Report Template & Ensure Storage Files
        $destDir = storage_path('app/schools/SCH-SGIS-GAM/reports/custom');
        $imgDestDir = $destDir . '/images';
        if (!is_dir($imgDestDir)) {
            @mkdir($imgDestDir, 0755, true);
        }

        $srcDir = base_path('reporting-engine/templates');
        $srcImgDir = $srcDir . '/images';

        if (file_exists($srcDir . '/sgis_report_card_a4.jrxml') && !file_exists($destDir . '/sgis_report_card_a4.jrxml')) {
            @copy($srcDir . '/sgis_report_card_a4.jrxml', $destDir . '/sgis_report_card_a4.jrxml');
        }
        if (file_exists($srcDir . '/sgis_report_card_a4.jasper') && !file_exists($destDir . '/sgis_report_card_a4.jasper')) {
            @copy($srcDir . '/sgis_report_card_a4.jasper', $destDir . '/sgis_report_card_a4.jasper');
        }
        foreach (['cognia_seal.png', 'logo.png', 'footer_address.png'] as $img) {
            if (file_exists($srcImgDir . '/' . $img) && !file_exists($imgDestDir . '/' . $img)) {
                @copy($srcImgDir . '/' . $img, $imgDestDir . '/' . $img);
            }
        }

        ReportTemplate::updateOrCreate(
            [
                'school_id' => $schoolId,
                'report_code' => 'sgis_trimester_card',
            ],
            [
                'is_global' => false,
                'title' => 'SGIS Official Report Card (Trimester Model)',
                'category' => 'Sun of Glory Custom Reports',
                'description' => 'Official Cognia-accredited Trimester Report Card with Trimesters 1-3, Cumulative GPA, Summer Course tracking, and attendance.',
                'base_type' => 'card_single',
                'template_file' => 'sgis_report_card_a4.jrxml',
                'deployment_path' => 'schools/SCH-SGIS-GAM/reports/custom',
                'subreports' => [],
                'assets' => ['logo.png', 'cognia_seal.png', 'footer_address.png'],
                'is_compiled' => true,
                'parameters' => ['student', 'class', 'format'],
                'default_format' => 'view',
                'is_system' => false,
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        // 2. Ensure High School / Secondary Division
        $division = SchoolDivision::firstOrCreate(
            [
                'school_id' => $schoolId,
                'division_name' => 'American High School',
            ],
            [
                'display_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 3. Ensure Grade 10 Level
        $gradeLevel = SchoolGradeLevel::firstOrCreate(
            [
                'school_id' => $schoolId,
                'grade_code' => 'G10',
            ],
            [
                'division_id' => $division->id,
                'grade_name' => 'Grade 10',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 4. Ensure Class G10 - C
        $class = SchoolClass::firstOrCreate(
            [
                'school_id' => $schoolId,
                'class_name' => 'G10 - C',
            ],
            [
                'grade_id' => $gradeLevel->id,
                'homeroom_teacher' => 'Faculty Registrar',
                'max_capacity' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 5. Ensure Student: Abdallah Amr Alaa Eldin Hussein Wahba
        $student = Student::updateOrCreate(
            [
                'school_id' => $schoolId,
                'admission_number' => 'STD-SGIS-G10-001',
            ],
            [
                'class_id' => $class->id,
                'full_name' => 'Abdallah Amr Alaa Eldin Hussein Wahba',
                'gender' => 'Male',
                'status' => 'ACTIVE',
            ]
        );

        // 6. Ensure Trimester Periods (T1, T2, T3)
        $t1 = AcademicGradingPeriod::firstOrCreate(
            [
                'school_id' => $schoolId,
                'period_name' => 'Trimester 1',
            ],
            [
                'period_type' => 'MAIN',
                'weight' => 33.33,
                'display_order' => 1,
            ]
        );

        $t2 = AcademicGradingPeriod::firstOrCreate(
            [
                'school_id' => $schoolId,
                'period_name' => 'Trimester 2',
            ],
            [
                'period_type' => 'MAIN',
                'weight' => 33.33,
                'display_order' => 2,
            ]
        );

        $t3 = AcademicGradingPeriod::firstOrCreate(
            [
                'school_id' => $schoolId,
                'period_name' => 'Trimester 3',
            ],
            [
                'period_type' => 'MAIN',
                'weight' => 33.34,
                'display_order' => 3,
            ]
        );

        // 7. High School Subjects & Scores from Official PDF
        $curriculum = [
            ['name' => 'English Language Arts', 'code' => 'ELA-G10', 'credit' => 1.0, 't1' => 90.81],
            ['name' => 'Algebra 2',               'code' => 'ALG2-G10', 'credit' => 1.0, 't1' => 51.49],
            ['name' => 'Biology',                 'code' => 'BIO-G10',  'credit' => 1.0, 't1' => 30.80],
            ['name' => 'World History',           'code' => 'WHIS-G10', 'credit' => 1.0, 't1' => 45.29],
            ['name' => 'German',                  'code' => 'GER-G10',  'credit' => 1.0, 't1' => 52.00],
            ['name' => 'Computer',                'code' => 'COMP-G10', 'credit' => 0.5, 't1' => 90.00],
            ['name' => 'Art and Design',          'code' => 'ART-G10',  'credit' => 0.5, 't1' => 0.00],
            ['name' => 'Physical Education',      'code' => 'PE-G10',   'credit' => 0.5, 't1' => 100.00],
        ];

        foreach ($curriculum as $item) {
            $masterSubject = MasterSubject::firstOrCreate(
                [
                    'school_id' => $schoolId,
                    'subject_name' => $item['name'],
                ],
                [
                    'subject_code' => $item['code'],
                    'max_score' => 100.00,
                    'pass_limit' => 60.00,
                    'default_credits' => $item['credit'],
                ]
            );

            $assignment = ClassSubjectAssignment::firstOrCreate(
                [
                    'school_id' => $schoolId,
                    'class_id' => $class->id,
                    'subject_id' => $masterSubject->id,
                ],
                [
                    'credit_hours' => $item['credit'],
                    'assigned_teacher' => 'Faculty Member',
                ]
            );

            // Grade record for T1
            GradeRecord::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'student_id' => $student->id,
                    'class_subject_assignment_id' => $assignment->id,
                ],
                [
                    'academic_cycle' => '2025/2026',
                    'term_name' => 'Trimester 1',
                    'total_score' => $item['t1'],
                    'letter_grade' => $item['t1'] >= 90 ? 'A-' : ($item['t1'] >= 60 ? 'D' : 'F'),
                    'gpa_point' => 0.0,
                    'is_verified' => true,
                ]
            );

            // Sub score for T1
            if ($t1) {
                $category = \App\Models\GradingCategory::where('school_id', $schoolId)->first();
                if ($category) {
                    StudentSubScore::updateOrCreate(
                        [
                            'school_id' => $schoolId,
                            'student_id' => $student->id,
                            'class_subject_assignment_id' => $assignment->id,
                            'grading_period_id' => $t1->id,
                        ],
                        [
                            'grading_category_id' => $category->id,
                            'raw_score' => $item['t1'],
                            'max_score' => 100.00,
                            'weighted_score' => $item['t1'],
                        ]
                    );
                }
            }
        }
    }
}
