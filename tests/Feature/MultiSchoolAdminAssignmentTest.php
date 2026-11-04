<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MultiSchoolAdminAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected School $schoolA;
    protected School $schoolB;
    protected School $schoolC;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create 3 distinct test schools
        $this->schoolA = School::create([
            'school_id' => 'SCH-ALPHA',
            'school_name' => 'Alpha International Academy',
            'school_code' => 'ALPHA',
            'active_scale_model' => 'PERCENTAGE_100',
        ]);

        $this->schoolB = School::create([
            'school_id' => 'SCH-BETA',
            'school_name' => 'Beta British College',
            'school_code' => 'BETA',
            'active_scale_model' => 'PERCENTAGE_100',
        ]);

        $this->schoolC = School::create([
            'school_id' => 'SCH-GAMMA',
            'school_name' => 'Gamma Science Institute',
            'school_code' => 'GAMMA',
            'active_scale_model' => 'PERCENTAGE_100',
        ]);

        // Global Super Admin
        $this->superAdmin = User::create([
            'name' => 'Global Super Admin',
            'email' => 'super@system.test',
            'password' => Hash::make('password123'),
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_super_admin_has_access_to_all_schools(): void
    {
        $this->assertTrue($this->superAdmin->isSuperAdmin());
        $this->assertTrue($this->superAdmin->hasAccessToSchool($this->schoolA));
        $this->assertTrue($this->superAdmin->hasAccessToSchool($this->schoolB));
        $this->assertTrue($this->superAdmin->hasAccessToSchool($this->schoolC));

        // accessibleSchools() returns all schools
        $accessible = $this->superAdmin->accessibleSchools();
        $this->assertCount(3, $accessible);
        $this->assertTrue($accessible->pluck('school_id')->contains('SCH-ALPHA'));
        $this->assertTrue($accessible->pluck('school_id')->contains('SCH-BETA'));
        $this->assertTrue($accessible->pluck('school_id')->contains('SCH-GAMMA'));
    }

    public function test_super_admin_can_switch_to_any_school(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.schools.switch', 'SCH-GAMMA'));

        $response->assertStatus(302);
        $this->assertEquals('SCH-GAMMA', session('active_school_id'));
    }

    public function test_school_admin_assigned_to_multiple_schools_can_access_only_assigned_schools(): void
    {
        // Multi-school admin assigned to School A and School B, but NOT School C
        $multiAdmin = User::create([
            'name' => 'Multi-Campus Principal',
            'email' => 'principal@multicampus.test',
            'password' => Hash::make('password123'),
            'role' => 'SCHOOL_ADMIN',
            'school_id' => 'SCH-ALPHA',
            'status' => 'ACTIVE',
        ]);
        $multiAdmin->schools()->sync(['SCH-ALPHA', 'SCH-BETA']);

        $this->assertTrue($multiAdmin->isSchoolAdmin());
        $this->assertFalse($multiAdmin->isSuperAdmin());

        // Check access
        $this->assertTrue($multiAdmin->hasAccessToSchool('SCH-ALPHA'));
        $this->assertTrue($multiAdmin->hasAccessToSchool($this->schoolA));
        $this->assertTrue($multiAdmin->hasAccessToSchool('SCH-BETA'));
        $this->assertTrue($multiAdmin->hasAccessToSchool($this->schoolB));

        // School C is NOT accessible
        $this->assertFalse($multiAdmin->hasAccessToSchool('SCH-GAMMA'));
        $this->assertFalse($multiAdmin->hasAccessToSchool($this->schoolC));

        $accessible = $multiAdmin->accessibleSchools();
        $this->assertCount(2, $accessible);
        $this->assertTrue($accessible->pluck('school_id')->contains('SCH-ALPHA'));
        $this->assertTrue($accessible->pluck('school_id')->contains('SCH-BETA'));
        $this->assertFalse($accessible->pluck('school_id')->contains('SCH-GAMMA'));
    }

    public function test_school_admin_can_switch_between_assigned_schools(): void
    {
        $multiAdmin = User::create([
            'name' => 'Campus Director',
            'email' => 'director@campuses.test',
            'password' => Hash::make('password123'),
            'role' => 'SCHOOL_ADMIN',
            'status' => 'ACTIVE',
        ]);
        $multiAdmin->schools()->sync(['SCH-ALPHA', 'SCH-BETA']);

        // Switch to School A
        $responseA = $this->actingAs($multiAdmin)
            ->get(route('admin.schools.switch', 'SCH-ALPHA'));
        $responseA->assertStatus(302);
        $this->assertEquals('SCH-ALPHA', session('active_school_id'));

        // Switch to School B
        $responseB = $this->actingAs($multiAdmin)
            ->get(route('admin.schools.switch', 'SCH-BETA'));
        $responseB->assertStatus(302);
        $this->assertEquals('SCH-BETA', session('active_school_id'));
    }

    public function test_school_admin_cannot_switch_to_unassigned_school_returns_403(): void
    {
        $singleAdmin = User::create([
            'name' => 'Alpha Only Admin',
            'email' => 'admin@alpha-only.test',
            'password' => Hash::make('password123'),
            'role' => 'SCHOOL_ADMIN',
            'status' => 'ACTIVE',
        ]);
        $singleAdmin->schools()->sync(['SCH-ALPHA']);

        // Attempting to switch to unassigned School C
        $response = $this->actingAs($singleAdmin)
            ->get(route('admin.schools.switch', 'SCH-GAMMA'));

        $response->assertStatus(403);
        // Session active_school_id should NOT be set to SCH-GAMMA
        $this->assertNotEquals('SCH-GAMMA', session('active_school_id'));
    }

    public function test_super_admin_can_create_new_school_admin_with_multiple_school_assignments(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.users.store'), [
                'name' => 'New Regional Director',
                'email' => 'regional@directors.test',
                'password' => 'secret12345',
                'role' => 'SCHOOL_ADMIN',
                'school_ids' => ['SCH-ALPHA', 'SCH-BETA'],
                'phone' => '+1 555 123 4567',
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $user = User::where('email', 'regional@directors.test')->first();
        $this->assertNotNull($user);
        $this->assertEquals('SCHOOL_ADMIN', $user->role);
        $this->assertEquals('SCH-ALPHA', $user->school_id); // Primary fallback

        // Check pivot table
        $this->assertDatabaseHas('school_user', [
            'user_id' => $user->id,
            'school_id' => 'SCH-ALPHA',
        ]);
        $this->assertDatabaseHas('school_user', [
            'user_id' => $user->id,
            'school_id' => 'SCH-BETA',
        ]);
        $this->assertDatabaseMissing('school_user', [
            'user_id' => $user->id,
            'school_id' => 'SCH-GAMMA',
        ]);

        $this->assertCount(2, $user->schools);
    }

    public function test_super_admin_can_update_existing_admin_school_assignments(): void
    {
        $admin = User::create([
            'name' => 'Editable Admin',
            'email' => 'editable@admin.test',
            'password' => Hash::make('password123'),
            'role' => 'SCHOOL_ADMIN',
            'status' => 'ACTIVE',
        ]);
        $admin->schools()->sync(['SCH-ALPHA']);

        $this->assertCount(1, $admin->schools);

        // Update to assign to School B and School C instead
        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.users.update', $admin->id), [
                'name' => 'Updated Director',
                'email' => 'editable@admin.test',
                'role' => 'SCHOOL_ADMIN',
                'school_ids' => ['SCH-BETA', 'SCH-GAMMA'],
                'status' => 'ACTIVE',
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $admin->refresh();
        $this->assertEquals('Updated Director', $admin->name);
        $this->assertEquals('SCH-BETA', $admin->school_id);

        $this->assertDatabaseMissing('school_user', [
            'user_id' => $admin->id,
            'school_id' => 'SCH-ALPHA',
        ]);
        $this->assertDatabaseHas('school_user', [
            'user_id' => $admin->id,
            'school_id' => 'SCH-BETA',
        ]);
        $this->assertDatabaseHas('school_user', [
            'user_id' => $admin->id,
            'school_id' => 'SCH-GAMMA',
        ]);
        $this->assertCount(2, $admin->schools);
    }

    public function test_super_admin_user_creation_does_not_require_schools(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.users.store'), [
                'name' => 'Co-Super Admin',
                'email' => 'cosuper@system.test',
                'password' => 'secret12345',
                'role' => 'SUPER_ADMIN',
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $newSuper = User::where('email', 'cosuper@system.test')->first();
        $this->assertNotNull($newSuper);
        $this->assertEquals('SUPER_ADMIN', $newSuper->role);
        $this->assertNull($newSuper->school_id);
        $this->assertTrue($newSuper->hasAccessToSchool('SCH-GAMMA'));
    }

    public function test_topbar_accessible_schools_rendered_for_multi_school_admin(): void
    {
        $multiAdmin = User::create([
            'name' => 'Dual Admin',
            'email' => 'dual@admin.test',
            'password' => Hash::make('password123'),
            'role' => 'SCHOOL_ADMIN',
            'status' => 'ACTIVE',
        ]);
        $multiAdmin->schools()->sync(['SCH-ALPHA', 'SCH-BETA']);

        $response = $this->actingAs($multiAdmin)->get(route('dashboard'));
        $response->assertStatus(200);

        // Must display school switcher options for Alpha and Beta, but NOT Gamma
        $response->assertSee('SCH-ALPHA');
        $response->assertSee('SCH-BETA');
        $response->assertDontSee('SCH-GAMMA');
    }
}
