<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\School;
use App\Models\SchoolGradeLevel;
use App\Models\SchoolClass;
use App\Models\MasterSubject;
use App\Models\ClassSubjectAssignment;
use App\Models\GradingStructureRule;
use App\Models\AcademicGradingPeriod;
use App\Models\GradingCategory;
use App\Models\SchoolGradingSystem;
use App\Models\Student;
use App\Models\GradeRecord;
use App\Models\StudentSubScore;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BatchGradebookTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected School $otherSchool;
    protected SchoolGradeLevel $level10;
    protected SchoolClass $class10A;
    protected MasterSubject $mathSubject;
    protected ClassSubjectAssignment $mathAssignment;
    protected GradingStructureRule $gradingRule;
    protected AcademicGradingPeriod $sem1;
    protected GradingCategory $catHomework;
    protected GradingCategory $catExam;
    protected Student $student1;
    protected Student $student2;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. School setup
        $this->school = School::create([
            'school_id' => 'SCH-GB-TEST',
            'school_name' => 'Gradebook Academy',
            'school_code' => 'GBA'
        ]);

        $this->otherSchool = School::create([
            'school_id' => 'SCH-GB-OTHER',
            'school_name' => 'Other Academy',
            'school_code' => 'OA'
        ]);

        // 2. Grading Scale
        SchoolGradingSystem::create([
            'school_id' => $this->school->school_id,
            'grading_model' => 'LETTER_GRADE_GPA',
            'passing_score' => 60.0,
            'cutoffs_json' => [
                ['grade' => 'A+', 'min' => 95, 'max' => 100, 'gpa' => 4.0, 'label' => 'Distinction'],
                ['grade' => 'A',  'min' => 90, 'max' => 94.99, 'gpa' => 3.8, 'label' => 'Excellent'],
                ['grade' => 'B',  'min' => 80, 'max' => 89.99, 'gpa' => 3.0, 'label' => 'Good'],
                ['grade' => 'C',  'min' => 70, 'max' => 79.99, 'gpa' => 2.0, 'label' => 'Satisfactory'],
                ['grade' => 'D',  'min' => 60, 'max' => 69.99, 'gpa' => 1.0, 'label' => 'Pass'],
                ['grade' => 'F',  'min' => 0,  'max' => 59.99, 'gpa' => 0.0, 'label' => 'Fail'],
            ]
        ]);

        // 3. Level & Class
        $this->level10 = SchoolGradeLevel::create([
            'school_id' => $this->school->school_id,
            'grade_name' => 'Grade 10',
            'grade_code' => 'G10',
            'display_order' => 1
        ]);

        $this->class10A = SchoolClass::create([
            'school_id' => $this->school->school_id,
            'grade_id' => $this->level10->id,
            'class_name' => 'Class 10-A',
            'max_capacity' => 30
        ]);

        // 4. Grading Blueprint with Categories
        $this->gradingRule = GradingStructureRule::create([
            'school_id' => $this->school->school_id,
            'rule_name' => 'Secondary STEM 2-Tier Rule',
            'scenario_depth' => '2_TIER_MAIN_CAT',
            'pass_limit' => 60.0,
            'total_grade' => 100.0,
            'credit_hours' => 3.0,
            'is_template' => true,
            'is_active' => true
        ]);

        $this->sem1 = AcademicGradingPeriod::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->gradingRule->id,
            'period_name' => 'Semester 1',
            'period_type' => 'SEMESTER',
            'weight' => 50.0,
            'display_order' => 1
        ]);

        $this->catHomework = GradingCategory::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->gradingRule->id,
            'grading_period_id' => $this->sem1->id,
            'category_name' => 'Homework & Quizzes',
            'category_type' => 'CONTINUOUS',
            'weight' => 40.0, // 40%
            'max_score' => 100.0,
            'display_order' => 1
        ]);

        $this->catExam = GradingCategory::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->gradingRule->id,
            'grading_period_id' => $this->sem1->id,
            'category_name' => 'Terminal Exam',
            'category_type' => 'EXAM',
            'weight' => 60.0, // 60%
            'max_score' => 100.0,
            'display_order' => 2
        ]);

        // 5. Subject & Assignment
        $this->mathSubject = MasterSubject::create([
            'school_id' => $this->school->school_id,
            'subject_name' => 'Pure Mathematics',
            'subject_code' => 'MATH-10',
            'default_credits' => 3.0,
            'grading_rule_id' => $this->gradingRule->id,
            'is_active' => true
        ]);

        $this->mathAssignment = ClassSubjectAssignment::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class10A->id,
            'subject_id' => $this->mathSubject->id,
            'grading_rule_id' => $this->gradingRule->id,
            'passing_mark' => 60.0,
            'assigned_teacher' => 'Dr. Archimedes'
        ]);

        // 6. Enrolled Students
        $this->student1 = Student::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class10A->id,
            'admission_number' => 'STD-GB-01',
            'full_name' => 'Isaac Newton',
            'gender' => 'Male',
            'status' => 'ACTIVE'
        ]);

        $this->student2 = Student::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class10A->id,
            'admission_number' => 'STD-GB-02',
            'full_name' => 'Ada Lovelace',
            'gender' => 'Female',
            'status' => 'ACTIVE'
        ]);
    }

    public function test_loads_batch_gradebook_page_successfully()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.classes.gradebook', $this->class10A->id));

        $response->assertStatus(200);
        $response->assertSee('Batch Gradebook: Class 10-A');
        $response->assertSee('Pure Mathematics');
        $response->assertSee('Semester 1');
        $response->assertSee('Homework & Quizzes');
        $response->assertSee('Terminal Exam');
        $response->assertSee('Isaac Newton');
        $response->assertSee('Ada Lovelace');
    }

    public function test_loads_batch_gradebook_broadsheet_mode()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.classes.gradebook', [
                'id' => $this->class10A->id,
                'view_mode' => 'broadsheet'
            ]));

        $response->assertStatus(200);
        $response->assertSee('Batch Multi-Subject Broadsheet Matrix');
        $response->assertSee('Isaac Newton');
        $response->assertSee('Pure Mathematics');
    }

    public function test_can_batch_save_grades_with_hierarchical_categories()
    {
        // Newton: HW 90/100 (weighted 36), Exam 95/100 (weighted 57) => total 93 (Grade A)
        // Lovelace: HW 100/100 (weighted 40), Exam 98/100 (weighted 58.8) => total 98.8 (Grade A+)
        $payload = [
            'assignment_id' => $this->mathAssignment->id,
            'period_id' => $this->sem1->id,
            'score_entry_mode' => 'percent',
            'grades' => [
                $this->student1->id => [
                    'categories' => [
                        $this->catHomework->id => 90.0,
                        $this->catExam->id => 95.0,
                    ],
                    'teacher_remarks' => 'Outstanding calculus insights'
                ],
                $this->student2->id => [
                    'categories' => [
                        $this->catHomework->id => 100.0,
                        $this->catExam->id => 98.0,
                    ],
                    'teacher_remarks' => 'Exceptional algorithmic thinking'
                ]
            ]
        ];

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.classes.gradebook.save', $this->class10A->id), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check StudentSubScore persisted
        $this->assertDatabaseHas('student_sub_scores', [
            'school_id' => $this->school->school_id,
            'student_id' => $this->student1->id,
            'grading_category_id' => $this->catHomework->id,
            'raw_score' => 90.0
        ]);

        $this->assertDatabaseHas('student_sub_scores', [
            'school_id' => $this->school->school_id,
            'student_id' => $this->student2->id,
            'grading_category_id' => $this->catExam->id,
            'raw_score' => 98.0
        ]);

        // Check overall GradeRecord computed
        $this->assertDatabaseHas('grade_records', [
            'school_id' => $this->school->school_id,
            'student_id' => $this->student1->id,
            'class_subject_assignment_id' => $this->mathAssignment->id,
            'total_score' => 93.0,
            'letter_grade' => 'A'
        ]);

        $this->assertDatabaseHas('grade_records', [
            'school_id' => $this->school->school_id,
            'student_id' => $this->student2->id,
            'class_subject_assignment_id' => $this->mathAssignment->id,
            'total_score' => 98.8,
            'letter_grade' => 'A+'
        ]);
    }

    public function test_can_ajax_save_single_student_grade()
    {
        $payload = [
            'assignment_id' => $this->mathAssignment->id,
            'period_id' => $this->sem1->id,
            'student_id' => $this->student1->id,
            'score_entry_mode' => 'percent',
            'categories' => [
                $this->catHomework->id => 85.0,
                $this->catExam->id => 80.0,
            ],
            'teacher_remarks' => 'Good progress'
        ];

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->postJson(route('config.classes.gradebook.save_single', $this->class10A->id), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'student_id' => $this->student1->id,
            'total_score' => 82.0, // (85*0.4) + (80*0.6) = 34 + 48 = 82
            'letter_grade' => 'B',
            'is_passed' => true
        ]);

        $this->assertDatabaseHas('grade_records', [
            'student_id' => $this->student1->id,
            'total_score' => 82.0,
            'letter_grade' => 'B'
        ]);
    }

    public function test_enforces_tenant_isolation_on_batch_gradebook()
    {
        $response = $this->withSession(['active_school_id' => $this->otherSchool->school_id])
            ->get(route('config.classes.gradebook', $this->class10A->id));

        $response->assertStatus(404);
    }

    public function test_gradebook_tab_present_in_class_profile()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.classes.profile', $this->class10A->id));

        $response->assertStatus(200);
        $response->assertSee('📖 Batch Gradebook');
        $response->assertSee('tabClassGradebook');
    }

    public function test_can_update_course_enrollments_selectively()
    {
        // Initially both students in class10A are eligible
        // Selectively enroll only student1 in Math
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.class_subjects.enrollments.update', $this->mathAssignment->id), [
                'student_ids' => [$this->student1->id]
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check course_enrollments
        $this->assertDatabaseHas('course_enrollments', [
            'class_subject_assignment_id' => $this->mathAssignment->id,
            'student_id' => $this->student1->id,
            'enrollment_status' => 'ENROLLED'
        ]);

        $this->assertDatabaseMissing('course_enrollments', [
            'class_subject_assignment_id' => $this->mathAssignment->id,
            'student_id' => $this->student2->id,
        ]);
    }

    public function test_course_marksheet_shows_only_enrolled_students()
    {
        // Enroll only student1
        \App\Models\CourseEnrollment::create([
            'school_id' => $this->school->school_id,
            'class_subject_assignment_id' => $this->mathAssignment->id,
            'student_id' => $this->student1->id,
            'enrollment_status' => 'ENROLLED',
            'enrolled_at' => now(),
        ]);

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.classes.gradebook', [
                'id' => $this->class10A->id,
                'assignment_id' => $this->mathAssignment->id
            ]));

        $response->assertStatus(200);
        $response->assertSee($this->student1->full_name);
        // Student 1 has editable input in marksheet table
        $response->assertSee("grades[{$this->student1->id}]");
        // Student 2 is NOT in the marksheet table inputs
        $response->assertDontSee("grades[{$this->student2->id}]");
    }

    public function test_subject_profile_deep_links_directly_to_batch_gradebook()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.subjects.profile', $this->mathSubject->id));

        $response->assertStatus(200);
        $expectedUrl = route('config.classes.gradebook', [
            'id' => $this->class10A->id,
            'assignment_id' => $this->mathAssignment->id
        ]);
        $response->assertSee($expectedUrl);
    }

    public function test_can_ajax_toggle_single_student_course_enrollment()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->postJson(route('config.class_subjects.enrollment.toggle', $this->mathAssignment->id), [
                'student_id' => $this->student2->id,
                'action' => 'enroll'
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'ENROLLED'
        ]);

        $this->assertDatabaseHas('course_enrollments', [
            'class_subject_assignment_id' => $this->mathAssignment->id,
            'student_id' => $this->student2->id,
            'enrollment_status' => 'ENROLLED'
        ]);

        // Unenroll
        $responseUnenroll = $this->withSession(['active_school_id' => $this->school->school_id])
            ->postJson(route('config.class_subjects.enrollment.toggle', $this->mathAssignment->id), [
                'student_id' => $this->student2->id,
                'action' => 'unenroll'
            ]);

        $responseUnenroll->assertStatus(200);
        $responseUnenroll->assertJson([
            'success' => true,
            'status' => 'UNENROLLED'
        ]);

        $this->assertDatabaseMissing('course_enrollments', [
            'class_subject_assignment_id' => $this->mathAssignment->id,
            'student_id' => $this->student2->id,
        ]);
    }

    public function test_can_batch_save_grades_using_percent_scale_mode()
    {
        // Set homework max to 20, weight to 20%; Exam max to 80, weight to 80%
        $this->catHomework->update(['max_score' => 20.0, 'weight' => 20.0]);
        $this->catExam->update(['max_score' => 80.0, 'weight' => 80.0]);

        // Student 1 enters marks on a 0-100 scale: 90 on HW (worth 20%), 85 on Exam (worth 80%)
        // Weighted HW = (90/100) * 20 = 18.0
        // Weighted Exam = (85/100) * 80 = 68.0
        // Total = 18.0 + 68.0 = 86.0
        $payload = [
            'assignment_id' => $this->mathAssignment->id,
            'period_id' => $this->sem1->id,
            'score_entry_mode' => 'percent',
            'grades' => [
                $this->student1->id => [
                    'categories' => [
                        $this->catHomework->id => 90.0,
                        $this->catExam->id => 85.0,
                    ],
                    'teacher_remarks' => 'Scored on percentage scale'
                ]
            ]
        ];

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.classes.gradebook.save', $this->class10A->id), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check sub-scores saved with max 100 and proportional weighted scores
        $this->assertDatabaseHas('student_sub_scores', [
            'school_id' => $this->school->school_id,
            'student_id' => $this->student1->id,
            'grading_category_id' => $this->catHomework->id,
            'raw_score' => 90.0,
            'max_score' => 100.0,
            'weighted_score' => 18.0
        ]);

        $this->assertDatabaseHas('student_sub_scores', [
            'school_id' => $this->school->school_id,
            'student_id' => $this->student1->id,
            'grading_category_id' => $this->catExam->id,
            'raw_score' => 85.0,
            'max_score' => 100.0,
            'weighted_score' => 68.0
        ]);

        // Check total score in grade_records
        $this->assertDatabaseHas('grade_records', [
            'school_id' => $this->school->school_id,
            'student_id' => $this->student1->id,
            'class_subject_assignment_id' => $this->mathAssignment->id,
            'total_score' => 86.0,
        ]);
    }

    public function test_can_ajax_save_single_student_grade_using_percent_scale_mode()
    {
        $this->catHomework->update(['max_score' => 25.0, 'weight' => 25.0]);

        $payload = [
            'assignment_id' => $this->mathAssignment->id,
            'period_id' => $this->sem1->id,
            'student_id' => $this->student1->id,
            'score_entry_mode' => 'percent',
            'categories' => [
                $this->catHomework->id => 80.0 // 80% of 25 = 20.0
            ],
            'teacher_remarks' => 'Quick single-student percentage save'
        ];

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->postJson("/configuration/classes/{$this->class10A->id}/gradebook/save-single", $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('student_sub_scores', [
            'student_id' => $this->student1->id,
            'grading_category_id' => $this->catHomework->id,
            'raw_score' => 80.0,
            'max_score' => 100.0,
            'weighted_score' => 20.0
        ]);
    }

    public function test_can_batch_save_grades_using_weight_scale_mode()
    {
        // Homework weight is 20%, Exam weight is 80%
        $this->catHomework->update(['max_score' => 20.0, 'weight' => 20.0]);
        $this->catExam->update(['max_score' => 80.0, 'weight' => 80.0]);

        // In weight mode: student scores are entered directly out of category weight
        // e.g. 18 out of 20 for HW, 72 out of 80 for Exam => Total = 18 + 72 = 90
        $payload = [
            'assignment_id' => $this->mathAssignment->id,
            'period_id' => $this->sem1->id,
            'score_entry_mode' => 'weight',
            'grades' => [
                $this->student1->id => [
                    'categories' => [
                        $this->catHomework->id => 18.0,
                        $this->catExam->id => 72.0,
                    ],
                    'teacher_remarks' => 'Scored directly out of weight'
                ]
            ]
        ];

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.classes.gradebook.save', $this->class10A->id), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check sub-scores saved with max = weight and direct weighted score
        $this->assertDatabaseHas('student_sub_scores', [
            'school_id' => $this->school->school_id,
            'student_id' => $this->student1->id,
            'grading_category_id' => $this->catHomework->id,
            'raw_score' => 18.0,
            'max_score' => 20.0,
            'weighted_score' => 18.0
        ]);

        $this->assertDatabaseHas('student_sub_scores', [
            'school_id' => $this->school->school_id,
            'student_id' => $this->student1->id,
            'grading_category_id' => $this->catExam->id,
            'raw_score' => 72.0,
            'max_score' => 80.0,
            'weighted_score' => 72.0
        ]);

        // Check total score in grade_records equals sum of weighted scores (90.0)
        $this->assertDatabaseHas('grade_records', [
            'school_id' => $this->school->school_id,
            'student_id' => $this->student1->id,
            'class_subject_assignment_id' => $this->mathAssignment->id,
            'total_score' => 90.0,
            'letter_grade' => 'A'
        ]);
    }

    public function test_gradebook_page_renders_score_scale_toggle()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.classes.gradebook', [
                'id' => $this->class10A->id,
                'assignment_id' => $this->mathAssignment->id
            ]));

        $response->assertStatus(200);
        $response->assertSee('Score Scale:');
        $response->assertSee('semWeight');
        $response->assertSee('semPercent');
        $response->assertSee('Out of 100');
    }
}
