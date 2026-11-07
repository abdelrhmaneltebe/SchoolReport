<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\School;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\Hash;

class PlanManagementTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $superAdmin;
    protected User $schoolAdmin;
    protected Plan $basicPlan;
    protected Plan $proPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basicPlan = Plan::create([
            'name' => 'Starter / Basic Plan',
            'code' => 'basic-test',
            'description' => 'Starter tier',
            'price' => 49.00,
            'billing_cycle' => 'monthly',
            'max_students' => 250,
            'features' => [
                'homeroom_attendance',
                'batch_gradebook',
                'student_profiles',
            ],
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->proPlan = Plan::create([
            'name' => 'Professional / Standard Plan',
            'code' => 'pro-test',
            'description' => 'Complete institutional suite',
            'price' => 149.00,
            'billing_cycle' => 'monthly',
            'max_students' => 1000,
            'features' => [
                'homeroom_attendance',
                'slot_attendance',
                'batch_gradebook',
                'jasper_report_cards',
                'broadsheet_reports',
                'custom_branding',
            ],
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->school = School::create([
            'school_id' => 'SCH-PLAN-TEST',
            'school_name' => 'Oakridge Academy',
            'school_code' => 'OAK',
            'active_scale_model' => 'PERCENTAGE_100',
            'plan_id' => $this->basicPlan->id,
            'subscription_status' => 'ACTIVE',
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => Hash::make('secret123'),
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->schoolAdmin = User::create([
            'name' => 'School Admin',
            'email' => 'admin@oakridge.test',
            'password' => Hash::make('secret123'),
            'role' => 'SCHOOL_ADMIN',
            'school_id' => $this->school->school_id,
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Test 1: Super admin can view plans management dashboard.
     */
    public function test_super_admin_can_view_plans_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('admin.plans.index'));

        $response->assertStatus(200);
        $response->assertSee('Starter / Basic Plan');
        $response->assertSee('Professional / Standard Plan');
        $response->assertSee('Oakridge Academy');
        $response->assertSee('Homeroom Daily Attendance');
    }

    /**
     * Test 2: Super admin can create a new plan with specific features.
     */
    public function test_super_admin_can_create_new_plan(): void
    {
        $payload = [
            'name' => 'Enterprise Custom Plan',
            'code' => 'enterprise-custom',
            'description' => 'Custom enterprise tier with all features',
            'price' => 399.00,
            'billing_cycle' => 'yearly',
            'max_students' => 5000,
            'max_classes' => 200,
            'max_templates' => 10,
            'features' => [
                'homeroom_attendance',
                'slot_attendance',
                'batch_gradebook',
                'jasper_report_cards',
                'report_studio_customizer',
            ],
            'is_active' => '1',
            'is_default' => '0',
            'display_order' => '10',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.plans.store'), $payload);

        $response->assertRedirect(route('admin.plans.index'));
        $this->assertDatabaseHas('plans', [
            'name' => 'Enterprise Custom Plan',
            'code' => 'enterprise-custom',
            'price' => 399.00,
            'billing_cycle' => 'yearly',
            'max_students' => 5000,
        ]);

        $created = Plan::where('code', 'enterprise-custom')->first();
        $this->assertEquals(10, $created->max_templates);
        $this->assertTrue($created->hasFeature('report_studio_customizer'));
        $this->assertEquals('10 Report Studio & Template Customizer', $created->getFeatureDisplayName('report_studio_customizer'));
        $this->assertFalse($created->hasFeature('custom_branding'));
    }

    /**
     * Test 3: Super admin can update plan features and pricing.
     */
    public function test_super_admin_can_update_plan(): void
    {
        $payload = [
            'name' => 'Starter / Basic Plan Updated',
            'code' => 'basic-test',
            'price' => 59.00,
            'billing_cycle' => 'monthly',
            'max_students' => 300,
            'features' => [
                'homeroom_attendance',
                'batch_gradebook',
                'custom_branding', // added feature
            ],
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.plans.update', $this->basicPlan->id), $payload);

        $response->assertRedirect(route('admin.plans.index'));
        $this->assertDatabaseHas('plans', [
            'id' => $this->basicPlan->id,
            'name' => 'Starter / Basic Plan Updated',
            'price' => 59.00,
            'max_students' => 300,
        ]);

        $this->basicPlan->refresh();
        $this->assertTrue($this->basicPlan->hasFeature('custom_branding'));
    }

    /**
     * Test 4: Super admin can assign a school to a different plan.
     */
    public function test_super_admin_can_assign_school_to_plan(): void
    {
        $payload = [
            'school_id' => $this->school->school_id,
            'plan_id' => $this->proPlan->id,
            'subscription_status' => 'ACTIVE',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.plans.assign_school'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('schools', [
            'school_id' => $this->school->school_id,
            'plan_id' => $this->proPlan->id,
            'subscription_status' => 'ACTIVE',
        ]);

        $this->school->refresh();
        $this->assertEquals($this->proPlan->id, $this->school->plan_id);
    }

    /**
     * Test 5: School feature check reflects the assigned plan.
     */
    public function test_school_has_feature_evaluates_plan_features(): void
    {
        // Oakridge is on Basic Plan (has homeroom_attendance, NO slot_attendance)
        $this->actingAs($this->schoolAdmin);

        $this->assertTrue($this->school->hasFeature('homeroom_attendance'));
        $this->assertFalse($this->school->hasFeature('slot_attendance'));
        $this->assertFalse($this->school->hasFeature('jasper_report_cards'));

        // Switch to Pro Plan (has slot_attendance and jasper_report_cards)
        $this->school->update(['plan_id' => $this->proPlan->id]);
        $this->school->refresh();

        $this->assertTrue($this->school->hasFeature('slot_attendance'));
        $this->assertTrue($this->school->hasFeature('jasper_report_cards'));
    }

    /**
     * Test 6: Custom school feature override grants access even if plan lacks it.
     */
    public function test_custom_features_override_plan_restrictions(): void
    {
        $this->actingAs($this->schoolAdmin);

        // School is on Basic Plan (lacks jasper_report_cards)
        $this->school->update([
            'plan_id' => $this->basicPlan->id,
            'custom_features' => ['jasper_report_cards'],
        ]);
        $this->school->refresh();

        $this->assertTrue($this->school->hasFeature('jasper_report_cards'));
        $this->assertFalse($this->school->hasFeature('slot_attendance'));
    }

    /**
     * Test 7: Super admin bypasses all feature restrictions.
     */
    public function test_super_admin_bypasses_all_feature_restrictions(): void
    {
        $this->actingAs($this->superAdmin);

        // Even though school is on basic plan, super admin has full access
        $this->assertTrue($this->school->hasFeature('homeroom_attendance'));
        $this->assertTrue($this->school->hasFeature('slot_attendance'));
        $this->assertTrue($this->school->hasFeature('report_studio_customizer'));
    }

    /**
     * Test 8: Cannot delete plan with assigned schools.
     */
    public function test_cannot_delete_plan_with_assigned_schools(): void
    {
        // Basic plan is assigned to Oakridge
        $response = $this->actingAs($this->superAdmin)
            ->delete(route('admin.plans.destroy', $this->basicPlan->id));

        $response->assertRedirect(route('admin.plans.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('plans', ['id' => $this->basicPlan->id]);
    }

    /**
     * Test 9: Can delete unused plan.
     */
    public function test_can_delete_unused_plan(): void
    {
        $unusedPlan = Plan::create([
            'name' => 'Unused Tier',
            'code' => 'unused-tier',
            'price' => 10.00,
            'billing_cycle' => 'monthly',
            'is_active' => true,
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('admin.plans.destroy', $unusedPlan->id));

        $response->assertRedirect(route('admin.plans.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('plans', ['id' => $unusedPlan->id]);
    }

    /**
     * Test 10: Plan displays customized number for Report Studio & Template Customizer.
     */
    public function test_plan_displays_custom_number_for_report_studio_customizer(): void
    {
        $plan3 = Plan::create([
            'name' => 'Gold 3 Tier',
            'code' => 'gold-3',
            'price' => 199.00,
            'billing_cycle' => 'monthly',
            'max_templates' => 3,
            'features' => ['report_studio_customizer'],
            'is_active' => true,
        ]);

        $plan10 = Plan::create([
            'name' => 'Diamond 10 Tier',
            'code' => 'diamond-10',
            'price' => 299.00,
            'billing_cycle' => 'monthly',
            'max_templates' => 10,
            'features' => ['report_studio_customizer'],
            'is_active' => true,
        ]);

        $this->assertEquals('3 Report Studio & Template Customizer', $plan3->getFeatureDisplayName('report_studio_customizer'));
        $this->assertEquals('10 Report Studio & Template Customizer', $plan10->getFeatureDisplayName('report_studio_customizer'));

        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('admin.plans.index'));

        $response->assertStatus(200);
        $response->assertSee('3 Report Studio & Template Customizer');
        $response->assertSee('10 Report Studio & Template Customizer');
    }
}
