<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\School;
use App\Models\SchoolGradeLevel;
use App\Models\SchoolClass;
use App\Models\MasterSubject;
use App\Models\ClassSubjectAssignment;
use App\Models\GradingStructureRule;
use App\Services\GradingCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApplyGradingSystemToSubjectsTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected GradingStructureRule $rule1;
    protected GradingStructureRule $rule2;
    protected MasterSubject $math;
    protected MasterSubject $physics;
    protected SchoolGradeLevel $grade;
    protected SchoolClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'school_id' => 'SCH-TEST-APPLY',
            'school_name' => 'Apply Grading Academy',
            'school_code' => 'AGA'
        ]);

        $this->rule1 = GradingStructureRule::create([
            'school_id' => $this->school->school_id,
            'rule_name' => 'Case 1: Standard 2-Tier Template',
            'is_template' => true,
            'template_key' => 'TEMPLATE_1',
            'scenario_depth' => '2_TIER_MAIN_CAT',
            'pass_limit' => 60.00,
            'total_grade' => 100.00,
            'credit_hours' => 1.00,
            'is_active' => true
        ]);

        $this->rule2 = GradingStructureRule::create([
            'school_id' => $this->school->school_id,
            'rule_name' => 'Case 3: Advanced 4-Tier Template',
            'is_template' => true,
            'template_key' => 'TEMPLATE_3',
            'scenario_depth' => '4_TIER_FULL_SUB_CAT',
            'pass_limit' => 70.00,
            'total_grade' => 100.00,
            'credit_hours' => 1.50,
            'is_active' => false
        ]);

        $this->math = MasterSubject::create([
            'school_id' => $this->school->school_id,
            'subject_name' => 'Mathematics',
            'subject_code' => 'MATH-101',
            'department' => 'Mathematics',
            'default_credits' => 1.0,
            'max_score' => 100.0
        ]);

        $this->physics = MasterSubject::create([
            'school_id' => $this->school->school_id,
            'subject_name' => 'Physics',
            'subject_code' => 'PHY-101',
            'department' => 'Sciences',
            'default_credits' => 1.0,
            'max_score' => 100.0
        ]);

        $this->grade = SchoolGradeLevel::create([
            'school_id' => $this->school->school_id,
            'grade_name' => 'Level 10',
            'grade_code' => 'L10',
            'display_order' => 1
        ]);

        $this->class = SchoolClass::create([
            'school_id' => $this->school->school_id,
            'grade_id' => $this->grade->id,
            'class_name' => 'L10-A'
        ]);

        ClassSubjectAssignment::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class->id,
            'subject_id' => $this->math->id,
            'credit_hours' => 1.0
        ]);
    }

    public function test_can_apply_grading_system_to_all_subjects(): void
    {
        $response = $this->post(route('config.grading.apply_to_subjects'), [
            'rule_id' => $this->rule2->id,
            'all_subjects' => 1,
            'cascade_to_classes' => 1
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('master_subjects', [
            'id' => $this->math->id,
            'grading_rule_id' => $this->rule2->id,
            'pass_limit' => 70.00
        ]);

        $this->assertDatabaseHas('master_subjects', [
            'id' => $this->physics->id,
            'grading_rule_id' => $this->rule2->id,
            'pass_limit' => 70.00
        ]);

        // Verify assignment also received the grading rule
        $this->assertDatabaseHas('class_subject_assignments', [
            'subject_id' => $this->math->id,
            'grading_rule_id' => $this->rule2->id,
            'passing_mark' => 70.00
        ]);
    }

    public function test_can_apply_grading_system_to_specific_subject_with_pass_limit_override(): void
    {
        $response = $this->post(route('config.grading.apply_to_subjects'), [
            'rule_id' => $this->rule1->id,
            'subject_ids' => [$this->physics->id],
            'pass_limit' => 75.50,
            'cascade_to_classes' => 1
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('master_subjects', [
            'id' => $this->physics->id,
            'grading_rule_id' => $this->rule1->id,
            'pass_limit' => 75.50
        ]);

        // Math should remain unassigned
        $this->assertDatabaseHas('master_subjects', [
            'id' => $this->math->id,
            'grading_rule_id' => null
        ]);
    }

    public function test_can_create_subject_with_grading_system(): void
    {
        $response = $this->post(route('config.subjects.store'), [
            'subject_name' => 'Chemistry Honors',
            'subject_code' => 'CHEM-H',
            'department' => 'Sciences',
            'default_credits' => 1.5,
            'max_score' => 100,
            'grading_rule_id' => $this->rule2->id,
            'passing_mark' => 65.0
        ]);

        $response->assertRedirect(route('config.class_subjects'));

        $this->assertDatabaseHas('master_subjects', [
            'subject_code' => 'CHEM-H',
            'grading_rule_id' => $this->rule2->id,
            'pass_limit' => 65.0
        ]);
    }

    public function test_can_update_subject_grading_system(): void
    {
        $this->math->update(['grading_rule_id' => $this->rule1->id]);

        $response = $this->post(route('config.subjects.update', $this->math->id), [
            'subject_name' => 'Mathematics Advanced',
            'subject_code' => 'MATH-101',
            'department' => 'Mathematics',
            'default_credits' => 2.0,
            'max_score' => 100,
            'grading_rule_id' => $this->rule2->id,
            'pass_limit' => 68.0
        ]);

        $response->assertRedirect(route('config.class_subjects'));

        $this->assertDatabaseHas('master_subjects', [
            'id' => $this->math->id,
            'grading_rule_id' => $this->rule2->id,
            'pass_limit' => 68.0
        ]);
    }

    public function test_can_reset_subject_grading_to_school_default(): void
    {
        $this->math->update(['grading_rule_id' => $this->rule2->id, 'pass_limit' => 70]);

        $response = $this->get(route('config.grading.subjects.reset', $this->math->id));
        $response->assertStatus(302);

        $this->assertDatabaseHas('master_subjects', [
            'id' => $this->math->id,
            'grading_rule_id' => null,
            'pass_limit' => null
        ]);
    }

    public function test_cascades_grading_rule_when_assigned_to_level(): void
    {
        $this->physics->update(['grading_rule_id' => $this->rule2->id]);

        $response = $this->post(route('config.class_subjects.assign_level'), [
            'subject_id' => $this->physics->id,
            'grade_ids' => [$this->grade->id],
            'credit_hours' => 1.0,
            'cw_weight' => 40,
            'exam_weight' => 60,
            'passing_mark' => 60
        ]);

        $response->assertRedirect(route('config.class_subjects'));

        $this->assertDatabaseHas('class_subject_assignments', [
            'class_id' => $this->class->id,
            'subject_id' => $this->physics->id,
            'grading_rule_id' => $this->rule2->id
        ]);
    }

    public function test_grading_calculation_service_resolves_effective_rule(): void
    {
        $service = app(GradingCalculationService::class);

        // 1. Math without explicit rule resolves active default (rule1)
        $resolvedDefault = $service->resolveRuleForSubject($this->math);
        $this->assertEquals($this->rule1->id, $resolvedDefault->id);

        // 2. Physics with explicit rule2 resolves rule2
        $this->physics->update(['grading_rule_id' => $this->rule2->id]);
        $resolvedPhysics = $service->resolveRuleForSubject($this->physics);
        $this->assertEquals($this->rule2->id, $resolvedPhysics->id);
    }
}
