<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\School;
use App\Models\SchoolGradeLevel;
use App\Models\SchoolClass;
use App\Models\MasterSubject;
use App\Models\ClassSubjectAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubjectAndCurriculumAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected SchoolGradeLevel $level1;
    protected SchoolGradeLevel $level2;
    protected SchoolClass $class1A;
    protected SchoolClass $class1B;
    protected SchoolClass $class2A;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'school_id' => 'SCH-TEST-SUBJ',
            'school_name' => 'Test Subject Academy',
            'school_code' => 'TSA'
        ]);

        $this->level1 = SchoolGradeLevel::create([
            'school_id' => $this->school->school_id,
            'grade_name' => 'Grade 9',
            'grade_code' => 'G9',
            'display_order' => 1
        ]);

        $this->level2 = SchoolGradeLevel::create([
            'school_id' => $this->school->school_id,
            'grade_name' => 'Grade 10',
            'grade_code' => 'G10',
            'display_order' => 2
        ]);

        $this->class1A = SchoolClass::create([
            'school_id' => $this->school->school_id,
            'grade_id' => $this->level1->id,
            'class_name' => 'G9-A'
        ]);

        $this->class1B = SchoolClass::create([
            'school_id' => $this->school->school_id,
            'grade_id' => $this->level1->id,
            'class_name' => 'G9-B'
        ]);

        $this->class2A = SchoolClass::create([
            'school_id' => $this->school->school_id,
            'grade_id' => $this->level2->id,
            'class_name' => 'G10-A'
        ]);
    }

    public function test_can_view_subjects_and_curriculum_page(): void
    {
        $response = $this->get(route('config.class_subjects'));
        $response->assertStatus(200);
        $response->assertSee('Master Subject Catalog', false);
        $response->assertSee('Level-Wise Curriculum Matrix', false);
        $response->assertSee('Batch / Class Marksheet Inspector', false);
    }

    public function test_can_create_master_subject_without_assignment(): void
    {
        $response = $this->post(route('config.subjects.store'), [
            'subject_name' => 'Advanced Physics',
            'subject_code' => 'PHY-301',
            'department' => 'Sciences',
            'default_credits' => 1.5,
            'max_score' => 100,
        ]);

        $response->assertRedirect(route('config.class_subjects'));
        $this->assertDatabaseHas('master_subjects', [
            'school_id' => $this->school->school_id,
            'subject_name' => 'Advanced Physics',
            'subject_code' => 'PHY-301',
            'department' => 'Sciences'
        ]);

        // Verify no assignments were created
        $this->assertEquals(0, ClassSubjectAssignment::count());
    }

    public function test_can_create_master_subject_with_instant_level_assignment(): void
    {
        $response = $this->post(route('config.subjects.store'), [
            'subject_name' => 'World History',
            'subject_code' => 'HIST-101',
            'department' => 'Humanities',
            'default_credits' => 1.0,
            'max_score' => 100,
            'cw_weight' => 50,
            'exam_weight' => 50,
            'passing_mark' => 55,
            'assign_grade_ids' => [$this->level1->id]
        ]);

        $response->assertRedirect(route('config.class_subjects'));

        $subject = MasterSubject::where('subject_code', 'HIST-101')->firstOrFail();

        // Level 1 has 2 classes (G9-A and G9-B), so both must have assignments
        $this->assertDatabaseHas('class_subject_assignments', [
            'school_id' => $this->school->school_id,
            'class_id' => $this->class1A->id,
            'subject_id' => $subject->id,
            'cw_weight' => 50,
            'exam_weight' => 50
        ]);

        $this->assertDatabaseHas('class_subject_assignments', [
            'school_id' => $this->school->school_id,
            'class_id' => $this->class1B->id,
            'subject_id' => $subject->id
        ]);

        // Level 2 class should NOT have an assignment
        $this->assertDatabaseMissing('class_subject_assignments', [
            'class_id' => $this->class2A->id,
            'subject_id' => $subject->id
        ]);
    }

    public function test_can_update_master_subject(): void
    {
        $subject = MasterSubject::create([
            'school_id' => $this->school->school_id,
            'subject_name' => 'Old Chemistry',
            'subject_code' => 'CHEM-OLD',
            'department' => 'Science',
            'default_credits' => 1.0,
            'max_score' => 100
        ]);

        $response = $this->post(route('config.subjects.update', $subject->id), [
            'subject_name' => 'Organic Chemistry',
            'subject_code' => 'CHEM-201',
            'department' => 'Physical Sciences',
            'default_credits' => 2.0,
            'max_score' => 100
        ]);

        $response->assertRedirect(route('config.class_subjects'));
        $this->assertDatabaseHas('master_subjects', [
            'id' => $subject->id,
            'subject_name' => 'Organic Chemistry',
            'subject_code' => 'CHEM-201',
            'department' => 'Physical Sciences'
        ]);
    }

    public function test_can_assign_subject_to_entire_level(): void
    {
        $subject = MasterSubject::create([
            'school_id' => $this->school->school_id,
            'subject_name' => 'Mathematics',
            'subject_code' => 'MATH-10',
            'department' => 'Mathematics',
            'default_credits' => 1.0,
            'max_score' => 100
        ]);

        $response = $this->post(route('config.class_subjects.assign_level'), [
            'subject_id' => $subject->id,
            'grade_ids' => [$this->level1->id, $this->level2->id],
            'credit_hours' => 1.5,
            'cw_weight' => 45,
            'exam_weight' => 55,
            'passing_mark' => 60,
            'assigned_teacher' => 'Dr. Alan Turing'
        ]);

        $response->assertRedirect(route('config.class_subjects'));

        // All 3 classes in Level 1 & Level 2 should now have the assignment
        $this->assertEquals(3, ClassSubjectAssignment::where('subject_id', $subject->id)->count());

        $this->assertDatabaseHas('class_subject_assignments', [
            'class_id' => $this->class1A->id,
            'subject_id' => $subject->id,
            'credit_hours' => 1.5,
            'assigned_teacher' => 'Dr. Alan Turing'
        ]);
    }

    public function test_can_assign_subject_to_specific_classes_batches(): void
    {
        $subject = MasterSubject::create([
            'school_id' => $this->school->school_id,
            'subject_name' => 'Computer Science Lab',
            'subject_code' => 'CS-LAB',
            'department' => 'Technology',
            'default_credits' => 1.0,
            'max_score' => 100
        ]);

        $response = $this->post(route('config.class_subjects.assign'), [
            'subject_id' => $subject->id,
            'class_ids' => [$this->class1A->id],
            'credit_hours' => 1.0,
            'cw_weight' => 60,
            'exam_weight' => 40,
            'passing_mark' => 70,
            'assigned_teacher' => 'Grace Hopper'
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('class_subject_assignments', [
            'class_id' => $this->class1A->id,
            'subject_id' => $subject->id,
            'assigned_teacher' => 'Grace Hopper'
        ]);

        $this->assertDatabaseMissing('class_subject_assignments', [
            'class_id' => $this->class1B->id,
            'subject_id' => $subject->id
        ]);
    }

    public function test_can_unassign_subject_from_class(): void
    {
        $subject = MasterSubject::create([
            'school_id' => $this->school->school_id,
            'subject_name' => 'Art',
            'subject_code' => 'ART-01',
            'department' => 'Arts'
        ]);

        $assignment = ClassSubjectAssignment::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class1A->id,
            'subject_id' => $subject->id,
            'credit_hours' => 1.0
        ]);

        $response = $this->get(route('config.class_subjects.delete', $assignment->id));
        $response->assertStatus(302);

        $this->assertDatabaseMissing('class_subject_assignments', [
            'id' => $assignment->id
        ]);
    }

    public function test_can_delete_master_subject_and_cascade_assignments(): void
    {
        $subject = MasterSubject::create([
            'school_id' => $this->school->school_id,
            'subject_name' => 'Drama',
            'subject_code' => 'DRM-01',
            'department' => 'Arts'
        ]);

        ClassSubjectAssignment::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class1A->id,
            'subject_id' => $subject->id,
            'credit_hours' => 1.0
        ]);

        $response = $this->get(route('config.subjects.delete', $subject->id));
        $response->assertRedirect(route('config.class_subjects'));

        $this->assertDatabaseMissing('master_subjects', [
            'id' => $subject->id
        ]);
    }

    public function test_gracefully_handles_deleting_nonexistent_subject_or_assignment(): void
    {
        $responseSubject = $this->get(route('config.subjects.delete', 999999));
        $responseSubject->assertRedirect(route('config.class_subjects'));
        $responseSubject->assertSessionHas('error');

        $responseAssignment = $this->get(route('config.class_subjects.delete', 999999));
        $responseAssignment->assertStatus(302);
        $responseAssignment->assertSessionHas('error');
    }
}
