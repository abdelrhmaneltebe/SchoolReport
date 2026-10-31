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
use App\Models\SchoolGradingSystem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CourseClassProfileTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected School $otherSchool;
    protected SchoolGradeLevel $grade9;
    protected SchoolClass $class9A;
    protected MasterSubject $math;
    protected MasterSubject $physics;
    protected ClassSubjectAssignment $mathAssignment;
    protected Student $student1;
    protected Student $student2;
    protected GradingStructureRule $template;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Primary school setup
        $this->school = School::create([
            'school_id' => 'SCH-CC-TEST',
            'school_name' => 'Evergreen Academy',
            'school_code' => 'EA'
        ]);

        $this->otherSchool = School::create([
            'school_id' => 'SCH-OTHER-CC',
            'school_name' => 'Foreign Academy',
            'school_code' => 'FA'
        ]);

        // Grading system
        SchoolGradingSystem::create([
            'school_id' => $this->school->school_id,
            'scope_type' => 'SCHOOL_BASE',
            'grading_model' => 'LETTER_GRADE_GPA',
            'cutoffs_json' => [
                ['grade' => 'A', 'min' => 90, 'max' => 100, 'gpa' => 4.0],
                ['grade' => 'B', 'min' => 80, 'max' => 89.99, 'gpa' => 3.0],
                ['grade' => 'C', 'min' => 70, 'max' => 79.99, 'gpa' => 2.0],
                ['grade' => 'D', 'min' => 60, 'max' => 69.99, 'gpa' => 1.0],
                ['grade' => 'F', 'min' => 0, 'max' => 59.99, 'gpa' => 0.0]
            ]
        ]);

        $this->template = GradingStructureRule::create([
            'school_id' => $this->school->school_id,
            'rule_name' => 'Standard Academic Blueprint',
            'is_template' => true,
            'template_key' => 'STD_ACAD',
            'scenario_depth' => '2_TIER_MAIN_CAT',
            'pass_limit' => 60.00,
            'total_grade' => 100.00,
            'credit_hours' => 1.00,
            'is_active' => true
        ]);

        $term1 = AcademicGradingPeriod::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->template->id,
            'period_name' => 'Semester 1',
            'period_type' => 'SEMESTER',
            'weight' => 50.00,
            'display_order' => 1
        ]);

        GradingCategory::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->template->id,
            'grading_period_id' => $term1->id,
            'category_name' => 'Homework & Quizzes',
            'weight' => 40.00,
            'max_score' => 40.00,
            'display_order' => 1
        ]);

        // 2. Grade level & Class
        $this->grade9 = SchoolGradeLevel::create([
            'school_id' => $this->school->school_id,
            'grade_name' => 'Grade 9',
            'grade_code' => 'G9',
            'display_order' => 1
        ]);

        $this->class9A = SchoolClass::create([
            'school_id' => $this->school->school_id,
            'grade_id' => $this->grade9->id,
            'class_name' => 'Grade 9-A',
            'homeroom_teacher' => 'Mrs. Gable',
            'max_capacity' => 28
        ]);

        // 3. Students
        $this->student1 = Student::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class9A->id,
            'admission_number' => 'ADM-901',
            'full_name' => 'James Wilson',
            'gender' => 'Male',
            'status' => 'ACTIVE'
        ]);

        $this->student2 = Student::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class9A->id,
            'admission_number' => 'ADM-902',
            'full_name' => 'Emma Watson',
            'gender' => 'Female',
            'status' => 'ACTIVE'
        ]);

        // 4. Subjects
        $this->math = MasterSubject::create([
            'school_id' => $this->school->school_id,
            'subject_name' => 'Mathematics Core',
            'subject_code' => 'MATH-101',
            'department' => 'Mathematics',
            'default_credits' => 1.5,
            'max_score' => 100,
            'grading_rule_id' => $this->template->id,
            'pass_limit' => 65.0
        ]);

        $this->physics = MasterSubject::create([
            'school_id' => $this->school->school_id,
            'subject_name' => 'Introductory Physics',
            'subject_code' => 'PHYS-101',
            'department' => 'Science',
            'default_credits' => 1.0,
            'max_score' => 100
        ]);

        // 5. Course-Class Assignment (courses_class)
        $this->mathAssignment = ClassSubjectAssignment::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class9A->id,
            'subject_id' => $this->math->id,
            'credit_hours' => 1.5,
            'cw_weight' => 40.0,
            'exam_weight' => 60.0,
            'passing_mark' => 65.0,
            'assigned_teacher' => 'Dr. Alan Turing'
        ]);
    }

    public function test_loads_class_profile_successfully_with_metrics_and_courses()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.classes.profile', $this->class9A->id));

        $response->assertStatus(200);
        $response->assertSee('Grade 9-A');
        $response->assertSee('Grade 9');
        $response->assertSee('Mrs. Gable');
        $response->assertSee('Evergreen Academy');
        $response->assertSee('Mathematics Core');
        $response->assertSee('Dr. Alan Turing');
        $response->assertSee('James Wilson');
        $response->assertSee('Emma Watson');
        $response->assertSee(route('config.class_subjects.profile', $this->mathAssignment->id));
    }

    public function test_loads_course_class_profile_successfully()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.class_subjects.profile', $this->mathAssignment->id));

        $response->assertStatus(200);
        $response->assertSee('Mathematics Core');
        $response->assertSee('Grade 9-A');
        $response->assertSee('Dr. Alan Turing');
        $response->assertSee('CW 40% / Exam 60%');
        $response->assertSee('James Wilson');
        $response->assertSee('Emma Watson');
        $response->assertSee('Standard Academic Blueprint');

        // Test alias route /configuration/courses-classes/{id}
        $aliasResponse = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.courses_classes.profile', $this->mathAssignment->id));

        $aliasResponse->assertStatus(200);
        $aliasResponse->assertSee('Mathematics Core');
    }

    public function test_can_update_class_details()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.classes.update', $this->class9A->id), [
                'class_name' => 'Grade 9-Advanced (Alpha)',
                'grade_id' => $this->grade9->id,
                'homeroom_teacher' => 'Mr. Charles Babbage',
                'max_capacity' => 32
            ]);

        $response->assertRedirect(route('config.classes.profile', $this->class9A->id));
        $this->assertDatabaseHas('school_classes', [
            'id' => $this->class9A->id,
            'class_name' => 'Grade 9-Advanced (Alpha)',
            'homeroom_teacher' => 'Mr. Charles Babbage',
            'max_capacity' => 32
        ]);
    }

    public function test_can_enroll_student_directly_into_class()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.classes.enroll_student', $this->class9A->id), [
                'admission_number' => 'ADM-903',
                'full_name' => 'Grace Hopper',
                'gender' => 'Female',
                'status' => 'ACTIVE'
            ]);

        $response->assertRedirect(route('config.classes.profile', $this->class9A->id));
        $this->assertDatabaseHas('students', [
            'school_id' => $this->school->school_id,
            'class_id' => $this->class9A->id,
            'admission_number' => 'ADM-903',
            'full_name' => 'Grace Hopper'
        ]);
    }

    public function test_can_update_course_class_parameters()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.class_subjects.update', $this->mathAssignment->id), [
                'assigned_teacher' => 'Prof. Katherine Johnson',
                'credit_hours' => 2.0,
                'cw_weight' => 50.0,
                'exam_weight' => 50.0,
                'passing_mark' => 70.0
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('class_subject_assignments', [
            'id' => $this->mathAssignment->id,
            'assigned_teacher' => 'Prof. Katherine Johnson',
            'credit_hours' => 2.0,
            'cw_weight' => 50.0,
            'exam_weight' => 50.0,
            'passing_mark' => 70.0
        ]);
    }

    public function test_can_record_student_grade_in_course_class()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.class_subjects.record_grade', $this->mathAssignment->id), [
                'student_id' => $this->student1->id,
                'cw_score' => 38.0,
                'exam_score' => 55.0,
                'total_score' => 93.0,
                'teacher_remarks' => 'Outstanding analytical reasoning.'
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('grade_records', [
            'school_id' => $this->school->school_id,
            'class_subject_assignment_id' => $this->mathAssignment->id,
            'student_id' => $this->student1->id,
            'total_score' => 93.0,
            'letter_grade' => 'A',
            'teacher_remarks' => 'Outstanding analytical reasoning.'
        ]);
    }

    public function test_enforces_tenant_isolation_on_class_and_course_class_profiles()
    {
        $foreignClass = SchoolClass::create([
            'school_id' => $this->otherSchool->school_id,
            'grade_id' => $this->grade9->id,
            'class_name' => 'Foreign Section'
        ]);

        $foreignAssignment = ClassSubjectAssignment::create([
            'school_id' => $this->otherSchool->school_id,
            'class_id' => $foreignClass->id,
            'subject_id' => $this->math->id,
            'credit_hours' => 1.0,
            'cw_weight' => 40,
            'exam_weight' => 60
        ]);

        // Attempting to access foreign class
        $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.classes.profile', $foreignClass->id))
            ->assertStatus(404);

        // Attempting to access foreign course-class assignment
        $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.class_subjects.profile', $foreignAssignment->id))
            ->assertStatus(404);
    }

    public function test_returns_404_for_non_existent_class_or_assignment()
    {
        $this->withSession(['active_school_id' => $this->school->school_id])
            ->get('/configuration/classes/999999')
            ->assertStatus(404);

        $this->withSession(['active_school_id' => $this->school->school_id])
            ->get('/configuration/class-subjects/999999')
            ->assertStatus(404);
    }
}
