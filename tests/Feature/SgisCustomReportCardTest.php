<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\School;
use App\Models\Student;
use App\Models\ReportTemplate;
use App\Services\ReportAnalyticsService;
use Database\Seeders\SchoolReportingSeeder;
use Database\Seeders\SgisCustomReportSeeder;

class SgisCustomReportCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SchoolReportingSeeder::class);
        $this->seed(SgisCustomReportSeeder::class);
    }

    public function test_sgis_report_template_exists_in_database()
    {
        $template = ReportTemplate::where('school_id', 'SCH-SGIS-GAM')
            ->where('report_code', 'sgis_trimester_card')
            ->first();

        $this->assertNotNull($template);
        $this->assertEquals('SGIS Official Report Card (Trimester Model)', $template->title);
        $this->assertEquals('sgis_report_card_a4.jrxml', $template->template_file);
        $this->assertTrue($template->is_active);
    }

    public function test_sgis_trimester_data_calculation()
    {
        $student = Student::where('admission_number', 'STD-SGIS-G10-001')->first();
        if (!$student) {
            $student = Student::where('school_id', 'SCH-SGIS-GAM')->first();
        }

        $this->assertNotNull($student);

        $analytics = app(ReportAnalyticsService::class);
        $data = $analytics->buildSgisTrimesterCardData($student);

        $this->assertStringContainsString('SUN OF GLORY', $data['school']['name']);
        $this->assertNotEmpty($data['subjectRows']);
        $this->assertArrayHasKey('totalCredits', $data['metrics']);
        $this->assertArrayHasKey('gpa', $data['metrics']);
        $this->assertArrayHasKey('summerCourses', $data['metrics']);
        $this->assertArrayHasKey('t1', $data['attendance']);
    }

    public function test_sgis_single_card_view_route()
    {
        $student = Student::where('admission_number', 'STD-SGIS-G10-001')->first();
        if (!$student) {
            $student = Student::where('school_id', 'SCH-SGIS-GAM')->first();
        }

        $this->assertNotNull($student);

        $response = $this->get(route('reports.card.single', ['student' => $student->id]));
        $response->assertStatus(200);
        $response->assertViewIs('reports.sgis_card');
        $response->assertSee('SUN OF GLORY');
        $response->assertSee('Report Card 2025/2026');
        $response->assertSee('Summer Course');
        $response->assertSee('Legal Representative');
        $response->assertSee('School General Director');
    }
}
