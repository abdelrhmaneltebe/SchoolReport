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
use App\Models\CourseEnrollment;
use App\Models\GradeRecord;
use App\Models\StudentSubScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

class GradebookSpreadsheetUploadTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected School $otherSchool;
    protected SchoolGradeLevel $level;
    protected SchoolClass $class;
    protected MasterSubject $subject;
    protected ClassSubjectAssignment $assignment;
    protected GradingStructureRule $rule;
    protected AcademicGradingPeriod $period;
    protected GradingCategory $catHw;
    protected GradingCategory $catExam;
    protected Student $student1;
    protected Student $student2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'school_id' => 'SCH-UPL-TEST',
            'school_name' => 'Upload Test Academy',
            'school_code' => 'UTA'
        ]);

        $this->otherSchool = School::create([
            'school_id' => 'SCH-UPL-OTHER',
            'school_name' => 'Other Academy',
            'school_code' => 'OTA'
        ]);

        SchoolGradingSystem::create([
            'school_id' => $this->school->school_id,
            'grading_model' => 'LETTER_GRADE_GPA',
            'passing_score' => 60.0,
            'cutoffs_json' => [
                ['grade' => 'A',  'min' => 90, 'max' => 100,  'gpa' => 4.0, 'label' => 'Excellent'],
                ['grade' => 'B',  'min' => 80, 'max' => 89.99, 'gpa' => 3.0, 'label' => 'Good'],
                ['grade' => 'C',  'min' => 70, 'max' => 79.99, 'gpa' => 2.0, 'label' => 'Satisfactory'],
                ['grade' => 'D',  'min' => 60, 'max' => 69.99, 'gpa' => 1.0, 'label' => 'Pass'],
                ['grade' => 'F',  'min' => 0,  'max' => 59.99, 'gpa' => 0.0, 'label' => 'Fail'],
            ]
        ]);

        $this->level = SchoolGradeLevel::create([
            'school_id' => $this->school->school_id,
            'grade_name' => 'Grade 8',
            'grade_code' => 'G8',
            'display_order' => 1
        ]);

        $this->class = SchoolClass::create([
            'school_id' => $this->school->school_id,
            'grade_id' => $this->level->id,
            'class_name' => 'Grade 8-A',
            'max_capacity' => 30
        ]);

        $this->student1 = Student::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class->id,
            'admission_number' => 'STD-UPL-01',
            'full_name' => 'Albert Einstein',
            'gender' => 'Male',
            'status' => 'ACTIVE'
        ]);

        $this->student2 = Student::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class->id,
            'admission_number' => 'STD-UPL-02',
            'full_name' => 'Marie Curie',
            'gender' => 'Female',
            'status' => 'ACTIVE'
        ]);

        $this->rule = GradingStructureRule::create([
            'school_id' => $this->school->school_id,
            'rule_name' => 'Middle School Blueprint',
            'calculation_type' => 'PERIOD_BASED',
            'total_grade' => 100.0,
            'pass_limit' => 60.0,
            'is_template' => true,
            'is_active' => true
        ]);

        $this->period = AcademicGradingPeriod::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'period_name' => 'Quarter 1',
            'weight' => 100.0,
            'display_order' => 1
        ]);

        $this->catHw = GradingCategory::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'grading_period_id' => $this->period->id,
            'category_name' => 'Homework & Quizzes',
            'category_type' => 'CONTINUOUS',
            'weight' => 40.0,
            'max_score' => 40.0,
            'display_order' => 1
        ]);

        $this->catExam = GradingCategory::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'grading_period_id' => $this->period->id,
            'category_name' => 'Quarter Exam',
            'category_type' => 'EXAM',
            'weight' => 60.0,
            'max_score' => 60.0,
            'display_order' => 2
        ]);

        $this->subject = MasterSubject::create([
            'school_id' => $this->school->school_id,
            'subject_name' => 'Physical Science',
            'subject_code' => 'SCI-8',
            'default_credits' => 3.0,
            'grading_rule_id' => $this->rule->id,
            'is_active' => true
        ]);

        $this->assignment = ClassSubjectAssignment::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'grading_rule_id' => $this->rule->id,
            'credit_hours' => 3.0,
            'cw_weight' => 40.0,
            'exam_weight' => 60.0,
            'passing_mark' => 60.0
        ]);

        CourseEnrollment::create([
            'school_id' => $this->school->school_id,
            'class_subject_assignment_id' => $this->assignment->id,
            'student_id' => $this->student1->id,
            'enrollment_status' => 'ENROLLED'
        ]);

        CourseEnrollment::create([
            'school_id' => $this->school->school_id,
            'class_subject_assignment_id' => $this->assignment->id,
            'student_id' => $this->student2->id,
            'enrollment_status' => 'ENROLLED'
        ]);
    }

    public function test_can_download_gradebook_excel_template()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.classes.gradebook.template', [
                'id' => $this->class->id,
                'assignment_id' => $this->assignment->id,
                'period_id' => $this->period->id,
            ]));

        $response->assertStatus(200);
        $this->assertTrue($response->headers->has('content-disposition'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_can_upload_csv_with_auto_detected_categories()
    {
        $csvContent = implode("\n", [
            'Admission Number,Student Full Name,Homework & Quizzes,Quarter Exam,Teacher Remarks',
            'STD-UPL-01,Albert Einstein,38,55,Brilliant work',
            'STD-UPL-02,Marie Curie,40,58,Outstanding performance'
        ]);

        $file = UploadedFile::fake()->createWithContent('gradebook_test.csv', $csvContent);

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.classes.gradebook.upload', $this->class->id), [
                'assignment_id' => $this->assignment->id,
                'period_id' => $this->period->id,
                'category_id' => '', // Auto-detect
                'sheet_file' => $file
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check Einstein: (38/40)*40 + (55/60)*60 = 38 + 55 = 93 -> Grade A
        $this->assertDatabaseHas('student_sub_scores', [
            'student_id' => $this->student1->id,
            'grading_category_id' => $this->catHw->id,
            'raw_score' => 38,
            'weighted_score' => 38
        ]);

        $this->assertDatabaseHas('student_sub_scores', [
            'student_id' => $this->student1->id,
            'grading_category_id' => $this->catExam->id,
            'raw_score' => 55,
            'weighted_score' => 55
        ]);

        $this->assertDatabaseHas('grade_records', [
            'student_id' => $this->student1->id,
            'class_subject_assignment_id' => $this->assignment->id,
            'total_score' => 93.0,
            'letter_grade' => 'A',
            'gpa_point' => 4.0,
            'teacher_remarks' => 'Brilliant work'
        ]);

        // Check Curie: 40 + 58 = 98 -> Grade A
        $this->assertDatabaseHas('grade_records', [
            'student_id' => $this->student2->id,
            'class_subject_assignment_id' => $this->assignment->id,
            'total_score' => 98.0,
            'letter_grade' => 'A'
        ]);
    }

    public function test_can_upload_sheet_for_specific_chosen_category()
    {
        // Teacher uploads a sheet specifically for "Homework & Quizzes" only
        $csvContent = implode("\n", [
            'Student ID,Name,Quiz Score',
            'STD-UPL-01,Albert Einstein,35',
            'STD-UPL-02,Marie Curie,39'
        ]);

        $file = UploadedFile::fake()->createWithContent('homework_scores.csv', $csvContent);

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.classes.gradebook.upload', $this->class->id), [
                'assignment_id' => $this->assignment->id,
                'period_id' => $this->period->id,
                'category_id' => $this->catHw->id, // Explicit Category Target!
                'sheet_file' => $file
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('student_sub_scores', [
            'student_id' => $this->student1->id,
            'grading_category_id' => $this->catHw->id,
            'raw_score' => 35,
            'weighted_score' => 35
        ]);

        $this->assertDatabaseHas('student_sub_scores', [
            'student_id' => $this->student2->id,
            'grading_category_id' => $this->catHw->id,
            'raw_score' => 39,
            'weighted_score' => 39
        ]);

        // Total score only has homework so far (35 / 100) -> Grade F
        $this->assertDatabaseHas('grade_records', [
            'student_id' => $this->student1->id,
            'total_score' => 35.0,
            'letter_grade' => 'F'
        ]);
    }

    public function test_enforces_tenant_isolation_on_gradebook_upload()
    {
        $csvContent = "Admission Number,Grade\nSTD-UPL-01,90";
        $file = UploadedFile::fake()->createWithContent('grades.csv', $csvContent);

        $response = $this->withSession(['active_school_id' => $this->otherSchool->school_id])
            ->post(route('config.classes.gradebook.upload', $this->class->id), [
                'assignment_id' => $this->assignment->id,
                'sheet_file' => $file
            ]);

        $response->assertStatus(404);
    }

    public function test_can_upload_sheet_with_percent_score_scale_mode()
    {
        // Category HW: max 40, weight 40%
        // Student enters 90 (out of 100) -> weighted should be (90/100)*40 = 36.0
        $csvContent = "Admission Number,Student Full Name,Homework & Quizzes\n" .
                      "STD-UPL-01,Marie Curie,90\n" .
                      "STD-UPL-02,Nikola Tesla,75\n";

        $file = UploadedFile::fake()->createWithContent('grades_percent.csv', $csvContent);

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.classes.gradebook.upload', $this->class->id), [
                'assignment_id' => $this->assignment->id,
                'period_id' => $this->period->id,
                'score_entry_mode' => 'percent',
                'sheet_file' => $file
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check Curie: 90 / 100 with weight 40% -> 36.0
        $this->assertDatabaseHas('student_sub_scores', [
            'student_id' => $this->student1->id,
            'grading_category_id' => $this->catHw->id,
            'raw_score' => 90.0,
            'max_score' => 100.0,
            'weighted_score' => 36.0
        ]);

        // Check Tesla: 75 / 100 with weight 40% -> 30.0
        $this->assertDatabaseHas('student_sub_scores', [
            'student_id' => $this->student2->id,
            'grading_category_id' => $this->catHw->id,
            'raw_score' => 75.0,
            'max_score' => 100.0,
            'weighted_score' => 30.0
        ]);
    }
}
