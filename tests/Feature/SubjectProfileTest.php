<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\School;
use App\Models\SchoolGradeLevel;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\MasterSubject;
use App\Models\ClassSubjectAssignment;
use App\Models\GradingStructureRule;
use App\Models\AcademicGradingPeriod;
use App\Models\GradingCategory;
use App\Models\GradeRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubjectProfileTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected School $otherSchool;
    protected GradingStructureRule $activeTemplate;
    protected GradingStructureRule $stemTemplate;
    protected SchoolGradeLevel $grade9;
    protected SchoolGradeLevel $grade10;
    protected SchoolClass $class9A;
    protected SchoolClass $class9B;
    protected SchoolClass $class10A;
    protected MasterSubject $math;
    protected MasterSubject $physics;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Primary school setup
        $this->school = School::create([
            'school_id' => 'SCH-TEST-PROF',
            'school_name' => 'Apex High School',
            'school_code' => 'AHS'
        ]);

        $this->otherSchool = School::create([
            'school_id' => 'SCH-OTHER',
            'school_name' => 'Foreign School',
            'school_code' => 'FS'
        ]);

        // 2. Default School Grading Template
        $this->activeTemplate = GradingStructureRule::create([
            'school_id' => $this->school->school_id,
            'rule_name' => 'Standard General Template',
            'is_template' => true,
            'template_key' => 'STD_GEN',
            'scenario_depth' => '2_TIER_MAIN_CAT',
            'pass_limit' => 60.00,
            'total_grade' => 100.00,
            'credit_hours' => 1.00,
            'is_active' => true
        ]);

        $term1 = AcademicGradingPeriod::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->activeTemplate->id,
            'period_name' => 'Term 1',
            'period_type' => 'SEMESTER',
            'weight' => 50.00,
            'display_order' => 1
        ]);

        GradingCategory::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->activeTemplate->id,
            'grading_period_id' => $term1->id,
            'category_name' => 'Classwork',
            'weight' => 40.00,
            'max_score' => 40.00,
            'display_order' => 1
        ]);

        // STEM Specialized Template
        $this->stemTemplate = GradingStructureRule::create([
            'school_id' => $this->school->school_id,
            'rule_name' => 'STEM Advanced Laboratory Blueprint',
            'is_template' => true,
            'template_key' => 'STEM_LAB',
            'scenario_depth' => '2_TIER_MAIN_CAT',
            'pass_limit' => 70.00,
            'total_grade' => 100.00,
            'credit_hours' => 1.50,
            'is_active' => true
        ]);

        // 3. Levels and Classes
        $this->grade9 = SchoolGradeLevel::create([
            'school_id' => $this->school->school_id,
            'grade_name' => 'Grade 9',
            'grade_code' => 'G9',
            'display_order' => 1
        ]);

        $this->grade10 = SchoolGradeLevel::create([
            'school_id' => $this->school->school_id,
            'grade_name' => 'Grade 10',
            'grade_code' => 'G10',
            'display_order' => 2
        ]);

        $this->class9A = SchoolClass::create([
            'school_id' => $this->school->school_id,
            'grade_id' => $this->grade9->id,
            'class_name' => 'G9-A'
        ]);

        $this->class9B = SchoolClass::create([
            'school_id' => $this->school->school_id,
            'grade_id' => $this->grade9->id,
            'class_name' => 'G9-B'
        ]);

        $this->class10A = SchoolClass::create([
            'school_id' => $this->school->school_id,
            'grade_id' => $this->grade10->id,
            'class_name' => 'G10-A'
        ]);

        // 4. Students
        Student::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class9A->id,
            'admission_number' => 'STD-001',
            'full_name' => 'Alice Smith',
            'status' => 'ACTIVE'
        ]);

        Student::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class9A->id,
            'admission_number' => 'STD-002',
            'full_name' => 'Bob Johnson',
            'status' => 'ACTIVE'
        ]);

        Student::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class9B->id,
            'admission_number' => 'STD-003',
            'full_name' => 'Charlie Brown',
            'status' => 'ACTIVE'
        ]);

        // 5. Subjects
        $this->math = MasterSubject::create([
            'school_id' => $this->school->school_id,
            'subject_name' => 'Mathematics Advanced',
            'subject_code' => 'MATH-ADV',
            'department' => 'Mathematics & Computing',
            'default_credits' => 1.5,
            'max_score' => 100,
            'grading_rule_id' => $this->stemTemplate->id,
            'pass_limit' => 70.0
        ]);

        $this->physics = MasterSubject::create([
            'school_id' => $this->school->school_id,
            'subject_name' => 'General Physics',
            'subject_code' => 'PHYS-101',
            'department' => 'Natural Sciences',
            'default_credits' => 1.0,
            'max_score' => 100,
            'grading_rule_id' => null // Uses School Default
        ]);
    }

    public function test_loads_subject_profile_successfully_with_all_metrics()
    {
        // Assign math to G9-A and G9-B
        $assign9A = ClassSubjectAssignment::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class9A->id,
            'subject_id' => $this->math->id,
            'credit_hours' => 1.5,
            'cw_weight' => 40,
            'exam_weight' => 60,
            'passing_mark' => 70,
            'assigned_teacher' => 'Dr. Euler'
        ]);

        $assign9B = ClassSubjectAssignment::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class9B->id,
            'subject_id' => $this->math->id,
            'credit_hours' => 1.5,
            'cw_weight' => 40,
            'exam_weight' => 60,
            'passing_mark' => 70,
            'assigned_teacher' => 'Prof. Gauss'
        ]);

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.subjects.profile', $this->math->id));

        $response->assertStatus(200);
        $response->assertSee('Mathematics Advanced');
        $response->assertSee('MATH-ADV');
        $response->assertSee('Mathematics &amp; Computing', false);
        $response->assertSee('Apex High School');
        $response->assertSee('STEM Advanced Laboratory Blueprint');
        $response->assertSee('Dr. Euler');
        $response->assertSee('Prof. Gauss');
        $response->assertSee('G9-A');
        $response->assertSee('G9-B');
    }

    public function test_calculates_enrolled_students_and_assigned_levels_accurately()
    {
        // Assign math to Grade 9 (G9-A, G9-B: 3 students) and Grade 10 (G10-A: 0 students)
        ClassSubjectAssignment::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class9A->id,
            'subject_id' => $this->math->id,
            'credit_hours' => 1.5,
            'cw_weight' => 40,
            'exam_weight' => 60,
            'assigned_teacher' => 'Dr. Euler'
        ]);

        ClassSubjectAssignment::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class9B->id,
            'subject_id' => $this->math->id,
            'credit_hours' => 1.5,
            'cw_weight' => 40,
            'exam_weight' => 60,
            'assigned_teacher' => 'Prof. Gauss'
        ]);

        ClassSubjectAssignment::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class10A->id,
            'subject_id' => $this->math->id,
            'credit_hours' => 1.5,
            'cw_weight' => 40,
            'exam_weight' => 60,
            'assigned_teacher' => 'Dr. Euler'
        ]);

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.subjects.profile', $this->math->id));

        $response->assertStatus(200);
        // Academic Levels count: 2 (Grade 9, Grade 10)
        // Class Batches count: 3 (G9-A, G9-B, G10-A)
        // Enrolled Students: 3 unique students
        // Faculty: 2 unique teachers (Dr. Euler, Prof. Gauss)
        $response->assertSee('Grade 9');
        $response->assertSee('Grade 10');
        $response->assertSee('Alice');
        $response->assertSee('Smith');
        $response->assertSee('Bob');
        $response->assertSee('Charlie');
    }

    public function test_displays_school_default_template_when_subject_has_no_custom_rule()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.subjects.profile', $this->physics->id));

        $response->assertStatus(200);
        $response->assertSee('General Physics');
        $response->assertSee('Standard General Template');
        $response->assertSee('School Default');
        $response->assertSee('Term 1');
        $response->assertSee('Classwork');
    }

    public function test_aggregates_grade_records_average_score_and_pass_rate()
    {
        $assign = ClassSubjectAssignment::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class9A->id,
            'subject_id' => $this->math->id,
            'credit_hours' => 1.5,
            'cw_weight' => 40,
            'exam_weight' => 60,
            'passing_mark' => 70
        ]);

        $student1 = Student::where('admission_number', 'STD-001')->first();
        $student2 = Student::where('admission_number', 'STD-002')->first();

        // Student 1: 85 (Passed)
        GradeRecord::create([
            'school_id' => $this->school->school_id,
            'student_id' => $student1->id,
            'class_subject_assignment_id' => $assign->id,
            'total_score' => 85.00
        ]);

        // Student 2: 55 (Failed)
        GradeRecord::create([
            'school_id' => $this->school->school_id,
            'student_id' => $student2->id,
            'class_subject_assignment_id' => $assign->id,
            'total_score' => 55.00
        ]);

        // Average = (85 + 55) / 2 = 70.0%
        // Pass rate = 1 of 2 = 50.0%
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.subjects.profile', $this->math->id));

        $response->assertStatus(200);
        $response->assertSee('70%');
        $response->assertSee('50% Pass Rate');
    }

    public function test_enforces_tenant_isolation_and_returns_404_for_other_schools_subject()
    {
        $foreignSubject = MasterSubject::create([
            'school_id' => $this->otherSchool->school_id,
            'subject_name' => 'Foreign Subject',
            'subject_code' => 'FS-101'
        ]);

        // Active school is Apex, trying to access foreign subject
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.subjects.profile', $foreignSubject->id));

        $response->assertStatus(404);
    }

    public function test_returns_404_for_non_existent_subject()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get('/configuration/subjects/999999');

        $response->assertStatus(404);
    }

    public function test_renders_action_modals_for_editing_assigning_and_changing_grading_rule()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.subjects.profile', $this->math->id));

        $response->assertStatus(200);
        $response->assertSee('id="modalEditSubject"', false);
        $response->assertSee('id="modalAssignToLevel"', false);
        $response->assertSee('id="modalAssignToClass"', false);
        $response->assertSee('id="modalApplyGradingSystem"', false);
        $response->assertSee(route('config.subjects.update', $this->math->id));
        $response->assertSee(route('config.class_subjects.assign_level'));
        $response->assertSee(route('config.class_subjects.assign'));
        $response->assertSee(route('config.grading.apply_to_subjects'));
    }
}
