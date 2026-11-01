<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\School;
use App\Models\User;
use App\Models\DemoRequest;

class HomePageAndDemoRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        School::create([
            'school_id' => 'SCH-GV-01',
            'school_name' => 'Green Valley International Academy',
            'school_code' => 'GV'
        ]);
    }

    public function test_guest_can_view_home_page(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Enterprise School Reporting');
        $response->assertSee('Request Institutional Demo');
        $response->assertSee('Academic Pillars');
        $response->assertSee('Sign In');
    }

    public function test_authenticated_user_can_view_home_page_with_dashboard_access(): void
    {
        $user = User::factory()->create([
            'role' => 'SCHOOL_ADMIN',
            'school_id' => 'SCH-GV-01'
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee('Open Dashboard');
        $response->assertSee('Go to Dashboard');
    }

    public function test_guest_can_submit_demo_request(): void
    {
        $data = [
            'full_name' => 'Dr. William Brown',
            'email' => 'w.brown@modernacademy.edu',
            'school_name' => 'Modern Academy International',
            'phone' => '+1 555 234 5678',
            'role' => 'School Principal / Director',
            'student_count' => '500 - 1,500 Students',
            'notes' => 'Interested in SGIS trimester report card and Cognia template compatibility.'
        ];

        $response = $this->post(route('request_demo'), $data);

        $response->assertRedirect(url('/#demo'));
        $response->assertSessionHas('demo_success');

        $this->assertDatabaseHas('demo_requests', [
            'email' => 'w.brown@modernacademy.edu',
            'school_name' => 'Modern Academy International',
            'full_name' => 'Dr. William Brown'
        ]);
    }

    public function test_demo_request_requires_mandatory_fields(): void
    {
        $response = $this->post(route('request_demo'), []);
        $response->assertSessionHasErrors(['full_name', 'email', 'school_name']);
    }

    public function test_login_page_renders_back_to_home_and_demo_link(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('Back to Home');
        $response->assertSee('Request Demo');
    }
}
