<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\School;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        School::create([
            'school_id' => 'SCH-GV-01',
            'school_name' => 'Green Valley International Academy',
            'school_code' => 'GV'
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
