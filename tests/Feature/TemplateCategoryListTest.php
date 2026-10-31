<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\School;
use App\Models\GradingStructureRule;
use App\Models\AcademicGradingPeriod;
use App\Models\GradingCategory;
use App\Models\GradingTemplateCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TemplateCategoryListTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected GradingStructureRule $rule;
    protected AcademicGradingPeriod $sem1;
    protected AcademicGradingPeriod $sem2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'school_id' => 'SCH-TEST-CAT',
            'school_name' => 'Category Testing Academy',
            'school_code' => 'CTA'
        ]);

        $this->rule = GradingStructureRule::create([
            'school_id' => $this->school->school_id,
            'rule_name' => 'Test Template with Category Pool',
            'is_template' => true,
            'template_key' => 'TEST_CAT_POOL',
            'description' => 'Test template for category list',
            'scope_type' => 'ALL_COURSES',
            'scenario_depth' => '2_TIER_MAIN_CAT',
            'pass_limit' => 60.00,
            'total_grade' => 100.00,
            'credit_hours' => 1.00,
            'is_active' => true
        ]);

        $this->sem1 = AcademicGradingPeriod::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'period_name' => 'Semester 1',
            'period_type' => 'MAIN',
            'weight' => 50.00,
            'display_order' => 1
        ]);

        $this->sem2 = AcademicGradingPeriod::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'period_name' => 'Semester 2',
            'period_type' => 'MAIN',
            'weight' => 50.00,
            'display_order' => 2
        ]);
    }

    public function test_can_view_template_category_bank(): void
    {
        GradingTemplateCategory::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'category_name' => 'Homework & Prep',
            'category_type' => 'HOMEWORK',
            'default_weight' => 20.00,
            'default_max_score' => 100.00,
            'display_order' => 1
        ]);

        $response = $this->get(route('config.grading', ['template_id' => $this->rule->id]));
        $response->assertStatus(200);
        $response->assertSee('Template Category Bank', false);
        $response->assertSee('Homework &amp; Prep', false);
    }

    public function test_can_add_category_to_template_list(): void
    {
        $response = $this->post(route('config.grading.template_categories.add'), [
            'rule_id' => $this->rule->id,
            'category_name' => 'Pop Quizzes',
            'category_type' => 'QUIZ',
            'default_weight' => 25.00,
            'default_max_score' => 50.00,
            'sub_categories_text' => 'Quiz 1 (50%), Quiz 2 (50%)'
        ]);

        $response->assertRedirect(route('config.grading', ['template_id' => $this->rule->id]));
        $this->assertDatabaseHas('grading_template_categories', [
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'category_name' => 'Pop Quizzes',
            'category_type' => 'QUIZ',
            'default_weight' => 25.00
        ]);

        $cat = GradingTemplateCategory::where('category_name', 'Pop Quizzes')->firstOrFail();
        $this->assertCount(2, $cat->sub_categories_json);
        $this->assertEquals('Quiz 1', $cat->sub_categories_json[0]['name']);
    }

    public function test_can_update_category_in_template_list(): void
    {
        $tCat = GradingTemplateCategory::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'category_name' => 'Midterm Old',
            'category_type' => 'EXAM',
            'default_weight' => 30.00,
            'default_max_score' => 100.00,
            'display_order' => 1
        ]);

        $response = $this->post(route('config.grading.template_categories.update', $tCat->id), [
            'category_name' => 'Midterm Assessment (Updated)',
            'category_type' => 'EXAM',
            'default_weight' => 35.00,
            'default_max_score' => 100.00,
            'sub_categories_text' => ''
        ]);

        $response->assertRedirect(route('config.grading', ['template_id' => $this->rule->id]));
        $this->assertDatabaseHas('grading_template_categories', [
            'id' => $tCat->id,
            'category_name' => 'Midterm Assessment (Updated)',
            'default_weight' => 35.00
        ]);
    }

    public function test_can_delete_category_from_template_list(): void
    {
        $tCat = GradingTemplateCategory::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'category_name' => 'Temporary Cat',
            'category_type' => 'CUSTOM',
            'default_weight' => 10.00,
            'default_max_score' => 100.00,
            'display_order' => 1
        ]);

        $response = $this->get(route('config.grading.template_categories.delete', $tCat->id));
        $response->assertRedirect(route('config.grading', ['template_id' => $this->rule->id]));
        $this->assertDatabaseMissing('grading_template_categories', [
            'id' => $tCat->id
        ]);
    }

    public function test_can_use_category_from_list_in_grading_period(): void
    {
        $tCat = GradingTemplateCategory::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'category_name' => 'Science Practical Labs',
            'category_type' => 'PROJECT',
            'default_weight' => 20.00,
            'default_max_score' => 100.00,
            'sub_categories_json' => [
                ['name' => 'Lab 1', 'weight' => 50],
                ['name' => 'Lab 2', 'weight' => 50]
            ],
            'display_order' => 1
        ]);

        $response = $this->post(route('config.grading.template_categories.use_in_period'), [
            'rule_id' => $this->rule->id,
            'grading_period_id' => $this->sem1->id,
            'template_category_ids' => [$tCat->id]
        ]);

        $response->assertRedirect(route('config.grading', ['template_id' => $this->rule->id]));

        // Check parent category was instantiated in Semester 1
        $this->assertDatabaseHas('grading_categories', [
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'grading_period_id' => $this->sem1->id,
            'template_category_id' => $tCat->id,
            'category_name' => 'Science Practical Labs',
            'weight' => 20.00
        ]);

        $parentCat = GradingCategory::where('category_name', 'Science Practical Labs')->firstOrFail();

        // Check sub-categories were instantiated under parent category
        $this->assertDatabaseHas('grading_categories', [
            'parent_category_id' => $parentCat->id,
            'category_name' => 'Lab 1',
            'weight' => 50.00
        ]);
        $this->assertDatabaseHas('grading_categories', [
            'parent_category_id' => $parentCat->id,
            'category_name' => 'Lab 2',
            'weight' => 50.00
        ]);
    }

    public function test_can_batch_apply_template_category_list_to_all_periods(): void
    {
        $c1 = GradingTemplateCategory::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'category_name' => 'Cat1 (Homework)',
            'category_type' => 'HOMEWORK',
            'default_weight' => 40.00,
            'default_max_score' => 100.00,
            'display_order' => 1
        ]);

        $c2 = GradingTemplateCategory::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'category_name' => 'Cat2 (Final Exam)',
            'category_type' => 'EXAM',
            'default_weight' => 60.00,
            'default_max_score' => 100.00,
            'display_order' => 2
        ]);

        $response = $this->post(route('config.grading.template_categories.apply_to_periods'), [
            'rule_id' => $this->rule->id,
            'period_ids' => [$this->sem1->id, $this->sem2->id],
            'mode' => 'replace'
        ]);

        $response->assertRedirect(route('config.grading', ['template_id' => $this->rule->id]));

        // Verify both periods have both categories
        $this->assertEquals(2, GradingCategory::where('grading_period_id', $this->sem1->id)->count());
        $this->assertEquals(2, GradingCategory::where('grading_period_id', $this->sem2->id)->count());

        $this->assertDatabaseHas('grading_categories', [
            'grading_period_id' => $this->sem1->id,
            'category_name' => 'Cat1 (Homework)',
            'weight' => 40.00
        ]);
        $this->assertDatabaseHas('grading_categories', [
            'grading_period_id' => $this->sem2->id,
            'category_name' => 'Cat2 (Final Exam)',
            'weight' => 60.00
        ]);
    }

    public function test_gracefully_handles_deleting_nonexistent_template_category(): void
    {
        $response = $this->get(route('config.grading.template_categories.delete', 999999));
        $response->assertRedirect(route('config.grading'));
        $response->assertSessionHas('error');
    }
}
