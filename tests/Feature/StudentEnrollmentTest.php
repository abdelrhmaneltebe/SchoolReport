<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\School;
use App\Models\SchoolGradeLevel;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StudentEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected School $otherSchool;
    protected SchoolGradeLevel $grade9;
    protected SchoolClass $class9A;
    protected SchoolClass $class9B;
    protected Student $student1;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Primary school setup
        $this->school = School::create([
            'school_id' => 'SCH-STD-TEST',
            'school_name' => 'Highland Preparatory School',
            'school_code' => 'HPS'
        ]);

        $this->otherSchool = School::create([
            'school_id' => 'SCH-OTHER-STD',
            'school_name' => 'Other Academy',
            'school_code' => 'OA'
        ]);

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
            'max_capacity' => 30
        ]);

        $this->class9B = SchoolClass::create([
            'school_id' => $this->school->school_id,
            'grade_id' => $this->grade9->id,
            'class_name' => 'Grade 9-B',
            'max_capacity' => 30
        ]);

        $this->student1 = Student::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class9A->id,
            'admission_number' => 'ADM-001',
            'full_name' => 'Marcus Aurelius',
            'gender' => 'Male',
            'status' => 'ACTIVE'
        ]);
    }

    public function test_loads_student_directory_index_page_with_metrics()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.students.index'));

        $response->assertStatus(200);
        $response->assertSee('Students Directory &amp; Enrollment Studio', false);
        $response->assertSee('Marcus Aurelius');
        $response->assertSee('ADM-001');
        $response->assertSee('Grade 9-A');
        $response->assertSee('Highland Preparatory School');
        $response->assertSee(route('config.students.template'));
    }

    public function test_can_enroll_single_student()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.students.store'), [
                'admission_number' => 'ADM-002',
                'full_name' => 'Lucius Verus',
                'class_id' => $this->class9B->id,
                'gender' => 'Male',
                'status' => 'ACTIVE'
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('students', [
            'school_id' => $this->school->school_id,
            'class_id' => $this->class9B->id,
            'admission_number' => 'ADM-002',
            'full_name' => 'Lucius Verus',
            'status' => 'ACTIVE'
        ]);
    }

    public function test_can_enroll_single_student_without_class_as_optional()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.students.store'), [
                'admission_number' => 'ADM-OPT-001',
                'full_name' => 'Plato of Athens',
                'class_id' => null, // Optional
                'gender' => 'Male',
                'status' => 'ACTIVE'
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('students', [
            'school_id' => $this->school->school_id,
            'class_id' => null,
            'admission_number' => 'ADM-OPT-001',
            'full_name' => 'Plato of Athens'
        ]);
    }

    public function test_can_update_student_to_unassign_class()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.students.update', $this->student1->id), [
                'admission_number' => 'ADM-001',
                'full_name' => 'Marcus Aurelius',
                'class_id' => null, // Unassigning
                'gender' => 'Male',
                'status' => 'ACTIVE'
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('students', [
            'id' => $this->student1->id,
            'class_id' => null
        ]);
    }

    public function test_prevents_enrolling_duplicate_admission_number()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.students.store'), [
                'admission_number' => 'ADM-001', // Already exists
                'full_name' => 'Imposter Marcus',
                'class_id' => $this->class9A->id,
                'gender' => 'Male'
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('students', [
            'full_name' => 'Imposter Marcus'
        ]);
    }

    public function test_can_update_student_details()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.students.update', $this->student1->id), [
                'admission_number' => 'ADM-001-EDIT',
                'full_name' => 'Marcus Aurelius Antoninus',
                'class_id' => $this->class9B->id,
                'gender' => 'Male',
                'status' => 'ACTIVE'
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('students', [
            'id' => $this->student1->id,
            'admission_number' => 'ADM-001-EDIT',
            'full_name' => 'Marcus Aurelius Antoninus',
            'class_id' => $this->class9B->id
        ]);
    }

    public function test_can_delete_student()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.students.delete', $this->student1->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('students', [
            'id' => $this->student1->id
        ]);
    }

    public function test_can_download_enrollment_excel_template()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.students.template'));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));
    }

    public function test_can_upload_csv_spreadsheet_with_students_bulk_enrollment()
    {
        $csvContent = implode("\n", [
            'admission_number,full_name,class_name,gender,status',
            'STD-BULK-01,Seneca the Younger,Grade 9-A,Male,ACTIVE',
            'STD-BULK-02,Hypatia of Alexandria,Grade 9-B,Female,ACTIVE',
            'STD-BULK-03,Epictetus,Grade 9-A,Male,ACTIVE',
            'STD-BULK-04,Zeno of Citium,,Male,ACTIVE'
        ]);

        $file = UploadedFile::fake()->createWithContent('students_import.csv', $csvContent);

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.students.upload'), [
                'student_sheet' => $file
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('students', [
            'school_id' => $this->school->school_id,
            'admission_number' => 'STD-BULK-01',
            'full_name' => 'Seneca the Younger',
            'class_id' => $this->class9A->id
        ]);

        $this->assertDatabaseHas('students', [
            'school_id' => $this->school->school_id,
            'admission_number' => 'STD-BULK-02',
            'full_name' => 'Hypatia of Alexandria',
            'class_id' => $this->class9B->id,
            'gender' => 'Female'
        ]);

        $this->assertDatabaseHas('students', [
            'school_id' => $this->school->school_id,
            'admission_number' => 'STD-BULK-03',
            'full_name' => 'Epictetus',
            'class_id' => $this->class9A->id
        ]);

        $this->assertDatabaseHas('students', [
            'school_id' => $this->school->school_id,
            'admission_number' => 'STD-BULK-04',
            'full_name' => 'Zeno of Citium',
            'class_id' => null
        ]);
    }

    public function test_can_upload_spreadsheet_with_target_class_override()
    {
        $csvContent = implode("\n", [
            'admission_number,full_name,class_name,gender,status',
            'STD-CLASS-01,Cleopatra VII,Unknown Class,Female,ACTIVE',
            'STD-CLASS-02,Ptolemy Philadelphus,,Male,ACTIVE'
        ]);

        $file = UploadedFile::fake()->createWithContent('students_class_target.csv', $csvContent);

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.students.upload'), [
                'student_sheet' => $file,
                'class_id' => $this->class9B->id // Forced target class
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('students', [
            'school_id' => $this->school->school_id,
            'admission_number' => 'STD-CLASS-01',
            'class_id' => $this->class9B->id
        ]);

        $this->assertDatabaseHas('students', [
            'school_id' => $this->school->school_id,
            'admission_number' => 'STD-CLASS-02',
            'class_id' => $this->class9B->id
        ]);
    }

    public function test_enforces_tenant_isolation_on_students()
    {
        $foreignClass = SchoolClass::create([
            'school_id' => $this->otherSchool->school_id,
            'grade_id' => $this->grade9->id,
            'class_name' => 'Foreign Class'
        ]);

        $foreignStudent = Student::create([
            'school_id' => $this->otherSchool->school_id,
            'class_id' => $foreignClass->id,
            'admission_number' => 'FOR-999',
            'full_name' => 'Foreign Student'
        ]);

        // Attempting to delete foreign student
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.students.delete', $foreignStudent->id));

        $response->assertStatus(404);
        $this->assertDatabaseHas('students', ['id' => $foreignStudent->id]);
    }

    public function test_filter_displays_disabled_class_select_when_no_level_selected()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.students.index'));

        $response->assertStatus(200);
        $response->assertSee('disabled', false);
        $response->assertSee('-- Select Level First --');
    }

    public function test_filter_populates_only_classes_for_selected_level()
    {
        // Create Grade 10 with Class 10-A
        $grade10 = SchoolGradeLevel::create([
            'school_id' => $this->school->school_id,
            'grade_name' => 'Grade 10',
            'grade_code' => 'G10',
            'display_order' => 2
        ]);

        $class10A = SchoolClass::create([
            'school_id' => $this->school->school_id,
            'grade_id' => $grade10->id,
            'class_name' => 'Grade 10-A',
            'max_capacity' => 30
        ]);

        // Request filtering by Grade 9 only
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.students.index', ['grade_id' => $this->grade9->id]));

        $response->assertStatus(200);
        $response->assertSee('<option value="' . $this->class9A->id . '" class="filter-class-option"', false);
        $response->assertSee('<option value="' . $this->class9B->id . '" class="filter-class-option"', false);
        // Grade 10-A should NOT be an option in the class filter
        $response->assertDontSee('<option value="' . $class10A->id . '" class="filter-class-option"', false);
    }

    public function test_filter_by_level_and_batch_returns_matching_students()
    {
        $std9B = Student::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class9B->id,
            'admission_number' => 'STD-9B-01',
            'full_name' => 'Nine B Student'
        ]);

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.students.index', [
                'grade_id' => $this->grade9->id,
                'class_id' => $this->class9B->id
            ]));

        $response->assertStatus(200);
        $response->assertSee('Nine B Student');
        $response->assertDontSee('Alexander The Great'); // student1 is in class9A
    }

    public function test_filter_by_unassigned_level_displays_unassigned_students()
    {
        $unassigned = Student::create([
            'school_id' => $this->school->school_id,
            'class_id' => null,
            'admission_number' => 'STD-UNASSIGNED',
            'full_name' => 'Orphan Student'
        ]);

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.students.index', ['grade_id' => 'unassigned']));

        $response->assertStatus(200);
        $response->assertSee('Orphan Student');
        $response->assertDontSee('Alexander The Great');
        $response->assertSee('-- Not Applicable (Unassigned) --');
    }

    public function test_custom_bootstrap5_pagination_renders_cleanly_without_svg_errors()
    {
        // Create 55 additional students to trigger multi-page pagination (total > 50)
        for ($i = 1; $i <= 55; $i++) {
            Student::create([
                'school_id' => $this->school->school_id,
                'class_id' => $this->class9A->id,
                'admission_number' => 'STD-PAG-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'full_name' => "Pagination Student {$i}",
                'status' => 'ACTIVE'
            ]);
        }

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.students.index', ['page' => 2]));

        $response->assertStatus(200);
        $response->assertSee('Page navigation');
        $response->assertSee('‹ Previous');
        $response->assertSee('Showing');
        $response->assertSee('results');

        // Verify clean bootstrap-5 pagination structure with NO unstyled svgs or hidden sm conflicting blocks
        $response->assertDontSee('d-sm-none');
        $response->assertDontSee('<svg class="w-5 h-5"', false);
    }
}
