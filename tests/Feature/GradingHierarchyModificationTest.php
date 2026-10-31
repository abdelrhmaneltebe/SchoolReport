<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\School;
use App\Models\User;
use App\Models\GradingStructureRule;
use App\Models\AcademicGradingPeriod;
use App\Models\GradingCategory;
use App\Services\GradingCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GradingHierarchyModificationTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected GradingStructureRule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::first() ?? School::create([
            'school_id' => 'SCH-TEST-01',
            'school_name' => 'Test Academy',
            'school_code' => 'TA'
        ]);

        $this->rule = GradingStructureRule::create([
            'school_id' => $this->school->school_id,
            'rule_name' => 'Custom Dynamic Template',
            'is_template' => true,
            'template_key' => 'TEST_DYNAMIC',
            'description' => 'Dynamic add/remove verification rule',
            'scope_type' => 'ALL_COURSES',
            'scenario_depth' => '2_TIER_MAIN_CAT',
            'pass_limit' => 60.00,
            'total_grade' => 100.00,
            'credit_hours' => 1.00,
            'is_active' => true
        ]);
    }

    public function test_can_add_and_delete_main_period(): void
    {
        // 1. Add Main Period
        $response = $this->post(route('config.grading.periods.add'), [
            'rule_id' => $this->rule->id,
            'period_name' => 'Semester 3 Test',
            'weight' => 33.33,
            'start_date' => '2027-01-01',
            'end_date' => '2027-05-30'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('academic_grading_periods', [
            'rule_id' => $this->rule->id,
            'period_name' => 'Semester 3 Test',
            'period_type' => 'MAIN',
            'parent_period_id' => null,
            'weight' => 33.33
        ]);

        $period = AcademicGradingPeriod::where('period_name', 'Semester 3 Test')->first();

        // 2. Delete Main Period
        $delResponse = $this->post(route('config.grading.periods.delete', $period->id));
        $delResponse->assertRedirect();

        $this->assertDatabaseMissing('academic_grading_periods', [
            'id' => $period->id
        ]);
    }

    public function test_can_add_sub_period_and_sync_scenario_depth(): void
    {
        // Create parent main period
        $mainPeriod = AcademicGradingPeriod::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'period_name' => 'Semester Alpha',
            'period_type' => 'MAIN',
            'weight' => 50.00
        ]);

        // Add Sub-Period
        $response = $this->post(route('config.grading.periods.add'), [
            'rule_id' => $this->rule->id,
            'parent_period_id' => $mainPeriod->id,
            'period_name' => 'Quarter 1 Alpha',
            'weight' => 50.00
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('academic_grading_periods', [
            'rule_id' => $this->rule->id,
            'parent_period_id' => $mainPeriod->id,
            'period_name' => 'Quarter 1 Alpha',
            'period_type' => 'SUB',
            'weight' => 50.00
        ]);

        // Verify scenario_depth upgraded to 3_TIER_SUB_PERIOD_MAIN_CAT
        $this->assertEquals('3_TIER_SUB_PERIOD_MAIN_CAT', $this->rule->fresh()->scenario_depth);

        $subPeriod = AcademicGradingPeriod::where('period_name', 'Quarter 1 Alpha')->first();

        // Delete Sub-Period
        $delResponse = $this->post(route('config.grading.periods.delete', $subPeriod->id));
        $delResponse->assertRedirect();

        $this->assertDatabaseMissing('academic_grading_periods', ['id' => $subPeriod->id]);
        // After deletion of only sub-period, should revert to 2_TIER_MAIN_CAT
        $this->assertEquals('2_TIER_MAIN_CAT', $this->rule->fresh()->scenario_depth);
    }

    public function test_can_add_category_and_sub_category_with_cascading_delete(): void
    {
        $mainPeriod = AcademicGradingPeriod::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'period_name' => 'Semester Beta',
            'period_type' => 'MAIN',
            'weight' => 100.00
        ]);

        // 1. Add Category
        $response = $this->post(route('config.grading.categories.add'), [
            'rule_id' => $this->rule->id,
            'grading_period_id' => $mainPeriod->id,
            'category_name' => 'Lab Work',
            'category_type' => 'PROJECT',
            'weight' => 40.00,
            'max_score' => 100
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('grading_categories', [
            'rule_id' => $this->rule->id,
            'grading_period_id' => $mainPeriod->id,
            'category_name' => 'Lab Work',
            'parent_category_id' => null,
            'weight' => 40.00
        ]);

        $cat = GradingCategory::where('category_name', 'Lab Work')->first();

        // 2. Add Sub-Category under Category
        $subCatResponse = $this->post(route('config.grading.categories.add'), [
            'rule_id' => $this->rule->id,
            'grading_period_id' => $mainPeriod->id,
            'parent_category_id' => $cat->id,
            'category_name' => 'Titration Practical',
            'weight' => 50.00,
            'max_score' => 50
        ]);

        $subCatResponse->assertRedirect();
        $this->assertDatabaseHas('grading_categories', [
            'rule_id' => $this->rule->id,
            'parent_category_id' => $cat->id,
            'category_name' => 'Titration Practical',
            'category_type' => 'SUB',
            'weight' => 50.00
        ]);

        // Scenario depth should be 4_TIER_FULL_SUB_CAT
        $this->assertEquals('4_TIER_FULL_SUB_CAT', $this->rule->fresh()->scenario_depth);

        // 3. Delete Parent Category -> should cascade and delete Sub-Category
        $delCatResponse = $this->post(route('config.grading.categories.delete', $cat->id));
        $delCatResponse->assertRedirect();

        $this->assertDatabaseMissing('grading_categories', ['id' => $cat->id]);
        $this->assertDatabaseMissing('grading_categories', ['category_name' => 'Titration Practical']);
    }

    public function test_full_cascading_deletion_when_main_period_is_removed(): void
    {
        // Setup: Main Period -> Sub-Period -> Category -> Sub-Category
        $mainPeriod = AcademicGradingPeriod::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'period_name' => 'Yearly Cycle 1',
            'period_type' => 'MAIN',
            'weight' => 100.00
        ]);

        $subPeriod = AcademicGradingPeriod::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'parent_period_id' => $mainPeriod->id,
            'period_name' => 'Quarter A',
            'period_type' => 'SUB',
            'weight' => 100.00
        ]);

        $cat = GradingCategory::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'grading_period_id' => $subPeriod->id,
            'category_name' => 'Exams',
            'category_type' => 'EXAM',
            'weight' => 100.00
        ]);

        $subCat = GradingCategory::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'grading_period_id' => $subPeriod->id,
            'parent_category_id' => $cat->id,
            'category_name' => 'Midterm Paper',
            'category_type' => 'SUB',
            'weight' => 100.00
        ]);

        // Deleting the main period must cascade delete ALL child entities
        $delResponse = $this->post(route('config.grading.periods.delete', $mainPeriod->id));
        $delResponse->assertRedirect();

        $this->assertDatabaseMissing('academic_grading_periods', ['id' => $mainPeriod->id]);
        $this->assertDatabaseMissing('academic_grading_periods', ['id' => $subPeriod->id]);
        $this->assertDatabaseMissing('grading_categories', ['id' => $cat->id]);
        $this->assertDatabaseMissing('grading_categories', ['id' => $subCat->id]);
    }

    public function test_bulk_name_and_weight_update(): void
    {
        $period = AcademicGradingPeriod::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'period_name' => 'Original Name',
            'period_type' => 'MAIN',
            'weight' => 50.00
        ]);

        $cat = GradingCategory::create([
            'school_id' => $this->school->school_id,
            'rule_id' => $this->rule->id,
            'grading_period_id' => $period->id,
            'category_name' => 'Old Cat',
            'category_type' => 'MAIN',
            'weight' => 40.00
        ]);

        $response = $this->post(route('config.grading.update_hierarchy'), [
            'rule_id' => $this->rule->id,
            'rule_name' => 'Renamed Rule Standard',
            'pass_limit' => 65.00,
            'total_grade' => 100.00,
            'credit_hours' => 2.00,
            'periods' => [
                $period->id => ['name' => 'Updated Period Name', 'weight' => 100.00]
            ],
            'categories' => [
                $cat->id => ['name' => 'Updated Cat Name', 'weight' => 100.00]
            ]
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('grading_structure_rules', [
            'id' => $this->rule->id,
            'rule_name' => 'Renamed Rule Standard',
            'pass_limit' => 65.00,
            'credit_hours' => 2.00
        ]);

        $this->assertDatabaseHas('academic_grading_periods', [
            'id' => $period->id,
            'period_name' => 'Updated Period Name',
            'weight' => 100.00
        ]);

        $this->assertDatabaseHas('grading_categories', [
            'id' => $cat->id,
            'category_name' => 'Updated Cat Name',
            'weight' => 100.00
        ]);
    }
}
