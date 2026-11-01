<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\School;
use App\Models\SchoolGradeLevel;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\MasterSubject;
use App\Models\ClassSubjectAssignment;
use App\Models\GradeRecord;
use App\Models\SchoolGradingSystem;

class ReportStudioTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected SchoolGradeLevel $level10;
    protected SchoolClass $class10A;
    protected Student $student1;
    protected Student $student2;
    protected MasterSubject $subject;
    protected ClassSubjectAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. School Tenant
        $this->school = School::create([
            'school_id' => 'SCH-RPT-TEST',
            'school_name' => 'Report Testing Academy',
            'school_code' => 'RTA'
        ]);

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

        // 2. Level & Class
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

        // 3. Subject & Assignment
        $this->subject = MasterSubject::create([
            'school_id' => $this->school->school_id,
            'subject_name' => 'Calculus & Algebra',
            'subject_code' => 'MATH-10',
            'default_credits' => 3.0,
            'is_active' => true
        ]);

        $this->assignment = ClassSubjectAssignment::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class10A->id,
            'subject_id' => $this->subject->id,
            'passing_mark' => 60.0,
            'assigned_teacher' => 'Prof. Euler'
        ]);

        // 4. Students
        $this->student1 = Student::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class10A->id,
            'admission_number' => 'STD-RPT-01',
            'full_name' => 'Ada Lovelace',
            'gender' => 'Female',
            'status' => 'ACTIVE'
        ]);

        $this->student2 = Student::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class10A->id,
            'admission_number' => 'STD-RPT-02',
            'full_name' => 'Alan Turing',
            'gender' => 'Male',
            'status' => 'ACTIVE'
        ]);

        // 5. Grade Records
        GradeRecord::create([
            'school_id' => $this->school->school_id,
            'student_id' => $this->student1->id,
            'class_subject_assignment_id' => $this->assignment->id,
            'total_score' => 96.0,
            'letter_grade' => 'A+',
            'gpa_point' => 4.0,
            'teacher_remarks' => 'Exceptional computing insights'
        ]);

        GradeRecord::create([
            'school_id' => $this->school->school_id,
            'student_id' => $this->student2->id,
            'class_subject_assignment_id' => $this->assignment->id,
            'total_score' => 88.0,
            'letter_grade' => 'B',
            'gpa_point' => 3.0,
            'teacher_remarks' => 'Great cryptography logic'
        ]);
    }

    public function test_loads_report_generator_studio_page_successfully()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('reports.generator'));

        $response->assertStatus(200);
        $response->assertSee('Academic Reports & Analytics Generator');
        $response->assertSee('Select Report from Catalog');
        $response->assertSee('Class 10-A');
    }

    public function test_can_generate_and_render_single_student_report_card()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('reports.card.single', $this->student1->id));

        $response->assertStatus(200);
        $response->assertSee('Ada Lovelace');
        $response->assertSee('STD-RPT-01');
        $response->assertSee('Calculus &amp; Algebra', false);
        $response->assertSee('Prof. Euler');
        $response->assertSee('96.0');
        $response->assertSee('A+');
        $response->assertSee("Principal's High Honor List");
    }

    public function test_can_generate_batch_class_report_cards_with_rankings()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('reports.card.batch', $this->class10A->id));

        $response->assertStatus(200);
        $response->assertSee('Bulk Report Cards: Class 10-A');
        $response->assertSee('Ada Lovelace');
        $response->assertSee('Alan Turing');
        $response->assertSee('1 of 2');
        $response->assertSee('2 of 2');
        $response->assertSee('page-break');
    }

    public function test_can_load_class_performance_and_analytics_dashboard()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('reports.analytics.class', $this->class10A->id));

        $response->assertStatus(200);
        $response->assertSee('Performance & Grade Analytics: Class 10-A');
        $response->assertSee('Class Average');
        $response->assertSee('Overall Pass Rate');
        $response->assertSee('Subject Performance Matrix');
        $response->assertSee('Grade Distribution Curve');
    }

    public function test_can_load_class_performance_analytics_for_empty_class_with_no_students()
    {
        $emptyClass = \App\Models\SchoolClass::create([
            'school_id' => $this->school->school_id,
            'grade_id' => $this->level10->id,
            'class_name' => 'Empty Batch',
            'max_capacity' => 30,
        ]);

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('reports.analytics.class', $emptyClass->id));

        $response->assertStatus(200);
        $response->assertSee('Performance & Grade Analytics: Empty Batch');
        $response->assertSee('0.0%');
        $response->assertSee('High:');
        $response->assertSee('Low:');
    }

    public function test_can_export_class_broadsheet_csv()
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('reports.export.csv', $this->class10A->id));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Ada Lovelace', $response->getContent());
        $this->assertStringContainsString('Alan Turing', $response->getContent());
        $this->assertStringContainsString('Calculus & Algebra', $response->getContent());
    }

    public function test_dispatches_generator_form_submission_to_correct_route()
    {
        // Test redirect for single card with explicitly selected student
        $responseSingle = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('reports.generator.dispatch'), [
                'report_type' => 'card_single',
                'class_id' => $this->class10A->id,
                'student_id' => $this->student1->id,
                'format' => 'view'
            ]);

        $responseSingle->assertRedirect(route('reports.card.single', ['student' => $this->student1->id, 'print' => 0]));

        // Test fallback resolution when student_id is omitted or null (auto-picks first student in class)
        $responseFallback = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('reports.generator.dispatch'), [
                'report_type' => 'card_single',
                'class_id' => $this->class10A->id,
                'student_id' => '',
                'format' => 'view'
            ]);

        $responseFallback->assertRedirect(route('reports.card.single', ['student' => $this->student1->id, 'print' => 0]));

        // Test graceful error redirect when school/class has no students at all
        $emptySchool = School::create([
            'school_id' => 'SCH-EMPTY-99',
            'school_name' => 'Empty Academy 99',
            'school_code' => 'EMP99'
        ]);

        $emptyClass = SchoolClass::create([
            'school_id' => $emptySchool->school_id,
            'grade_id' => $this->level10->id,
            'class_name' => 'Empty Class 99'
        ]);

        $responseEmpty = $this->withSession(['active_school_id' => $emptySchool->school_id])
            ->post(route('reports.generator.dispatch'), [
                'report_type' => 'card_single',
                'class_id' => $emptyClass->id,
                'student_id' => '',
                'format' => 'view'
            ]);

        $responseEmpty->assertRedirect(route('reports.generator'));
        $responseEmpty->assertSessionHas('error');

        // Test redirect for batch cards
        $responseBatch = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('reports.generator.dispatch'), [
                'report_type' => 'card_batch',
                'class_id' => $this->class10A->id,
                'format' => 'print'
            ]);

        $responseBatch->assertRedirect(route('reports.card.batch', ['class' => $this->class10A->id, 'print' => 1]));
    }

    public function test_can_add_edit_and_remove_report_template_with_chosen_parameters()
    {
        // 1. Add new custom report with chosen parameters
        $responseAdd = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('reports.templates.store'), [
                'title' => 'Midterm Academic Audit',
                'category' => 'Quality Assurance',
                'description' => 'Targeted audit for mid-semester grading and compliance',
                'base_type' => 'analytics',
                'parameters' => ['level', 'class', 'period', 'pass_mark'],
                'default_format' => 'view',
            ]);

        $responseAdd->assertRedirect();
        $this->assertDatabaseHas('report_templates', [
            'school_id' => $this->school->school_id,
            'title' => 'Midterm Academic Audit',
            'base_type' => 'analytics',
        ]);

        $createdTemplate = \App\Models\ReportTemplate::where('school_id', $this->school->school_id)
            ->where('title', 'Midterm Academic Audit')
            ->first();
        $this->assertNotNull($createdTemplate);
        $this->assertTrue($createdTemplate->hasParameter('pass_mark'));
        $this->assertFalse($createdTemplate->hasParameter('student'));

        // 2. Edit existing report template
        $responseUpdate = $this->withSession(['active_school_id' => $this->school->school_id])
            ->put(route('reports.templates.update', $createdTemplate->id), [
                'title' => 'Midterm Academic Audit (Revised)',
                'category' => 'Academic Quality',
                'description' => 'Updated audit report description',
                'base_type' => 'card_batch',
                'parameters' => ['level', 'class', 'period', 'format'],
                'default_format' => 'print',
            ]);

        $responseUpdate->assertRedirect();
        $this->assertDatabaseHas('report_templates', [
            'id' => $createdTemplate->id,
            'title' => 'Midterm Academic Audit (Revised)',
            'base_type' => 'card_batch',
            'default_format' => 'print',
        ]);

        // 3. Dispatch generator using the report_template_id
        $responseDispatch = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('reports.generator.dispatch'), [
                'report_template_id' => $createdTemplate->id,
                'class_id' => $this->class10A->id,
                'format' => 'print',
            ]);

        $responseDispatch->assertRedirect(route('reports.card.batch', ['class' => $this->class10A->id, 'print' => 1]));

        // 4. Delete report template
        $responseDelete = $this->withSession(['active_school_id' => $this->school->school_id])
            ->delete(route('reports.templates.destroy', $createdTemplate->id));

        $responseDelete->assertRedirect();
        $this->assertDatabaseMissing('report_templates', [
            'id' => $createdTemplate->id,
        ]);
    }

    public function test_global_report_is_visible_across_schools_while_school_custom_report_is_isolated()
    {
        $schoolB = School::create([
            'school_id' => 'SCH-TEST-B',
            'school_name' => 'Secondary Campus Academy',
            'school_code' => 'SCA'
        ]);

        // Create a Global general report
        $globalReport = \App\Models\ReportTemplate::create([
            'title' => 'Global Ministry Annual Report',
            'report_code' => 'global_ministry_annual',
            'is_global' => true,
            'school_id' => null,
            'base_type' => 'broadsheet',
            'category' => 'Ministry Reports',
            'parameters' => ['level', 'class', 'period', 'format'],
            'default_format' => 'view',
            'is_active' => true,
        ]);

        // Create a School-A customized report
        $schoolAReport = \App\Models\ReportTemplate::create([
            'title' => 'Campus A Custom Deficiency List',
            'report_code' => 'campus_a_deficiency',
            'is_global' => false,
            'school_id' => $this->school->school_id,
            'base_type' => 'at_risk',
            'category' => 'Custom',
            'parameters' => ['level', 'class', 'period', 'pass_mark'],
            'default_format' => 'view',
            'is_active' => true,
        ]);

        // When School A visits the generator: sees BOTH Global and School A reports
        $responseA = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('reports.generator'));

        $responseA->assertStatus(200);
        $responseA->assertSee('Global Ministry Annual Report');
        $responseA->assertSee('Campus A Custom Deficiency List');

        // When School B visits the generator: sees Global report, but NEVER sees School A custom report!
        $responseB = $this->withSession(['active_school_id' => $schoolB->school_id])
            ->get(route('reports.generator'));

        $responseB->assertStatus(200);
        $responseB->assertSee('Global Ministry Annual Report');
        $responseB->assertDontSee('Campus A Custom Deficiency List');
    }

    public function test_can_deploy_report_package_with_subreports_and_images_and_compile()
    {
        $template = \App\Models\ReportTemplate::create([
            'title' => 'Institutional Diploma Card',
            'report_code' => 'diploma_card_test',
            'is_global' => true,
            'school_id' => null,
            'base_type' => 'card_single',
            'category' => 'Diplomas',
            'parameters' => ['student', 'period', 'format'],
            'default_format' => 'jasper',
            'is_active' => true,
        ]);

        // Prepare test files
        $mainJrxml = \Illuminate\Http\UploadedFile::fake()->create('diploma.jrxml', 5, 'application/xml');
        $subreport = \Illuminate\Http\UploadedFile::fake()->create('honors_subreport.jrxml', 3, 'application/xml');
        $logo = \Illuminate\Http\UploadedFile::fake()->image('campus_logo.png', 100, 100);
        $sig = \Illuminate\Http\UploadedFile::fake()->image('dean_sign.png', 150, 50);

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('reports.templates.deploy', $template->id), [
                'template_file' => $mainJrxml,
                'subreport_files' => [$subreport],
                'asset_files' => [$logo, $sig],
            ]);

        $response->assertRedirect();

        // Check deployment metadata via AJAX API
        $responseMeta = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('reports.templates.deployment', $template->id));

        $responseMeta->assertStatus(200);
        $json = $responseMeta->json();

        $this->assertTrue($json['success']);
        $this->assertEquals('diploma.jrxml', $json['deployment']['main']['filename']);
        $this->assertCount(1, $json['deployment']['subreports']);
        $this->assertEquals('honors_subreport.jrxml', $json['deployment']['subreports'][0]['filename']);
        $this->assertCount(2, $json['deployment']['images']);

        // Download bundle as ZIP
        $responseZip = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('reports.templates.download-bundle', $template->id));
        $responseZip->assertStatus(200);
        $this->assertStringContainsString('application/zip', $responseZip->headers->get('content-type'));

        // Clean up test deployed files
        $dir = $template->getDeploymentDirectory();
        if (is_dir($dir)) {
            \Illuminate\Support\Facades\File::deleteDirectory($dir);
        }
    }

    public function test_creating_school_automatically_provisions_custom_report_directories()
    {
        $uniqueId = 'SCH-AUTO-' . substr(uniqid(), -4);
        $newSchool = School::create([
            'school_id' => $uniqueId,
            'school_name' => 'Auto Provisioned Prep',
            'school_code' => 'APP'
        ]);

        $reportsDir = $newSchool->getReportsDirectory();
        $imagesDir = $newSchool->getCustomReportsDirectory() . DIRECTORY_SEPARATOR . 'images';
        $subreportsDir = $newSchool->getCustomReportsDirectory() . DIRECTORY_SEPARATOR . 'subreports';
        $exportsDir = $newSchool->getExportsDirectory();

        $this->assertDirectoryExists($reportsDir);
        $this->assertDirectoryExists($imagesDir);
        $this->assertDirectoryExists($subreportsDir);
        $this->assertDirectoryExists($exportsDir);

        // Clean up created test directories
        $baseSchoolDir = dirname($reportsDir);
        if (is_dir($baseSchoolDir)) {
            \Illuminate\Support\Facades\File::deleteDirectory($baseSchoolDir);
        }
    }

    public function test_artisan_command_ensures_folders_for_existing_schools()
    {
        $this->artisan('schools:ensure-report-folders')
            ->expectsOutputToContain('Active & Ready')
            ->assertExitCode(0);

        $this->assertDirectoryExists($this->school->getReportsDirectory());
        $this->assertDirectoryExists($this->school->getCustomReportsDirectory() . DIRECTORY_SEPARATOR . 'subreports');
        $this->assertDirectoryExists($this->school->getCustomReportsDirectory() . DIRECTORY_SEPARATOR . 'images');
        $this->assertDirectoryExists($this->school->getExportsDirectory());
    }
}
