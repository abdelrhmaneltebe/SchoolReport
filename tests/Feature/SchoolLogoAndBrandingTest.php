<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\School;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\SchoolGradeLevel;
use App\Models\Student;
use App\Services\ReportAnalyticsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class SchoolLogoAndBrandingTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $superAdmin;
    protected User $schoolAdmin;
    protected School $otherSchool;
    protected User $otherSchoolAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::firstOrCreate(
            ['school_id' => 'SCH-TEST-LOGO'],
            [
                'school_name' => 'Logo Test Academy',
                'school_code' => 'LTA',
                'active_scale_model' => 'PERCENTAGE_100',
            ]
        );
        $this->school->ensureReportDirectoriesExist();

        $this->superAdmin = User::firstOrCreate(
            ['email' => 'superadmin_logo_test@example.com'],
            [
                'name' => 'Super Admin Logo Tester',
                'role' => 'SUPER_ADMIN',
                'password' => bcrypt('secret123')
            ]
        );

        $this->schoolAdmin = User::firstOrCreate(
            ['email' => 'schooladmin_logo_test@example.com'],
            [
                'name' => 'School Admin Logo Tester',
                'role' => 'SCHOOL_ADMIN',
                'school_id' => $this->school->school_id,
                'password' => bcrypt('secret123')
            ]
        );

        $this->otherSchool = School::firstOrCreate(
            ['school_id' => 'SCH-OTHER-LOGO'],
            [
                'school_name' => 'Other Logo Academy',
                'school_code' => 'OLA',
                'active_scale_model' => 'PERCENTAGE_100',
            ]
        );
        $this->otherSchool->ensureReportDirectoriesExist();

        $this->otherSchoolAdmin = User::firstOrCreate(
            ['email' => 'otheradmin_logo_test@example.com'],
            [
                'name' => 'Other Admin Tester',
                'role' => 'SCHOOL_ADMIN',
                'school_id' => $this->otherSchool->school_id,
                'password' => bcrypt('secret123')
            ]
        );
    }

    protected function tearDown(): void
    {
        // Clean up uploaded files in test school directories
        $dirs = [
            $this->school->getReportsDirectory(),
            $this->otherSchool->getReportsDirectory()
        ];
        foreach ($dirs as $d) {
            $parent = dirname($d);
            if (is_dir($parent)) {
                File::deleteDirectory($parent);
            }
        }

        parent::tearDown();
    }

    public function test_serves_svg_fallback_crest_when_no_logo_uploaded()
    {
        $this->school->deleteLogo();

        $response = $this->get(route('schools.logo', $this->school->school_id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('LT', $response->getContent()); // Initials for Logo Test
        $this->assertStringContainsString('LTA', $response->getContent()); // Code
    }

    public function test_school_admin_can_access_school_profile_page()
    {
        $response = $this->actingAs($this->schoolAdmin)
            ->get(route('school.profile'));

        $response->assertStatus(200);
        $response->assertSee('Logo Test Academy Profile &amp; Logo', false);
        $response->assertSee('🖼️ Official School Logo');
        $response->assertSee('Select New School Logo Image');
    }

    public function test_school_admin_can_upload_school_logo()
    {
        $fakeLogo = UploadedFile::fake()->image('crest_emblem.png', 200, 200);

        $response = $this->actingAs($this->schoolAdmin)
            ->post(route('school.profile.update'), [
                'school_id' => $this->school->school_id,
                'school_name' => 'Logo Test Academy Updated',
                'principal_name' => 'Prof. Alexander Wright',
                'logo' => $fakeLogo
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->school->refresh();
        $this->assertEquals('Logo Test Academy Updated', $this->school->school_name);
        $this->assertTrue($this->school->hasCustomLogo());
        $this->assertNotNull($this->school->getLogoDiskPath());
        $this->assertFileExists($this->school->getLogoDiskPath());

        // Verify the served logo endpoint serves png
        $logoResponse = $this->get(route('schools.logo', $this->school->school_id));
        $logoResponse->assertStatus(200);
        $this->assertStringContainsString('image/png', $logoResponse->headers->get('Content-Type'));
    }

    public function test_super_admin_can_upload_logo_via_admin_update()
    {
        $fakeLogo = UploadedFile::fake()->image('super_admin_crest.jpg', 300, 300);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.schools.update', $this->school->school_id), [
                'school_name' => 'Logo Test Academy by Super Admin',
                'school_code' => 'LTA',
                'active_scale_model' => 'PERCENTAGE_100',
                'logo' => $fakeLogo
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->school->refresh();
        $this->assertTrue($this->school->hasCustomLogo());
    }

    public function test_report_cards_data_contains_logo_url_and_base64()
    {
        // 1. Upload a logo
        $fakeLogo = UploadedFile::fake()->image('report_card_logo.png', 100, 100);
        $this->school->saveLogo($fakeLogo);
        $this->school->refresh();

        $this->assertNotNull($this->school->getLogoBase64());
        $this->assertStringStartsWith('data:image/png;base64,', $this->school->getLogoBase64());

        // 2. Build class & student
        $level = SchoolGradeLevel::firstOrCreate(
            ['school_id' => $this->school->school_id, 'grade_code' => 'LVL-LG'],
            ['grade_name' => 'Logo Level', 'display_order' => 1]
        );

        $class = SchoolClass::firstOrCreate(
            ['school_id' => $this->school->school_id, 'class_name' => 'Logo Class 1'],
            ['grade_id' => $level->id, 'max_capacity' => 30]
        );

        $student = Student::firstOrCreate(
            ['school_id' => $this->school->school_id, 'admission_number' => 'STD-LG-01'],
            ['full_name' => 'Logo Tester Student', 'class_id' => $class->id]
        );

        $analyticsService = app(ReportAnalyticsService::class);
        $cards = $analyticsService->buildClassReportCardsData($class);

        $this->assertNotEmpty($cards);
        $firstCard = $cards[0];

        $this->assertArrayHasKey('school', $firstCard);
        $this->assertArrayHasKey('logoUrl', $firstCard['school']);
        $this->assertArrayHasKey('logoBase64', $firstCard['school']);
        $this->assertArrayHasKey('hasLogo', $firstCard['school']);

        $this->assertTrue($firstCard['school']['hasLogo']);
        $this->assertNotNull($firstCard['school']['logoBase64']);
        $this->assertStringStartsWith(route('schools.logo', $this->school->school_id), $firstCard['school']['logoUrl']);
    }

    public function test_school_admin_can_remove_custom_logo()
    {
        $fakeLogo = UploadedFile::fake()->image('temp_logo.png', 100, 100);
        $this->school->saveLogo($fakeLogo);
        $this->assertTrue($this->school->hasCustomLogo());

        $response = $this->actingAs($this->schoolAdmin)
            ->post(route('school.profile.remove_logo'), [
                'school_id' => $this->school->school_id
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->school->refresh();
        $this->assertFalse($this->school->hasCustomLogo());
        $this->assertNull($this->school->logo_path);
    }

    public function test_unauthorized_school_admin_cannot_update_other_school_profile()
    {
        $response = $this->actingAs($this->schoolAdmin)
            ->post(route('school.profile.update'), [
                'school_id' => $this->otherSchool->school_id,
                'school_name' => 'Hacked School Name',
            ]);

        $response->assertStatus(403);
    }
}
