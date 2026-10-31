<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\School;
use App\Models\SchoolDivision;
use App\Models\SchoolGradeLevel;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SchoolLevelManagementTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'school_id' => 'SCH-TEST-01',
            'school_name' => 'Test International Academy',
            'school_code' => 'TIA'
        ]);
    }

    public function test_can_view_levels_index_page(): void
    {
        $response = $this->get(route('config.levels'));
        $response->assertStatus(200);
        $response->assertSee('School Levels', false);
    }

    public function test_can_create_new_school_level(): void
    {
        $division = SchoolDivision::create([
            'school_id' => $this->school->school_id,
            'division_name' => 'Middle School',
            'display_order' => 1
        ]);

        $response = $this->post(route('config.levels.store'), [
            'grade_name' => 'Level 7',
            'grade_code' => 'L7',
            'division_id' => $division->id,
            'display_order' => 7,
            'create_class' => 1,
            'class_name' => 'L7-A'
        ]);

        $response->assertRedirect(route('config.levels'));
        $this->assertDatabaseHas('school_grade_levels', [
            'school_id' => $this->school->school_id,
            'grade_name' => 'Level 7',
            'grade_code' => 'L7',
            'division_id' => $division->id
        ]);

        $level = SchoolGradeLevel::where('grade_code', 'L7')->first();
        $this->assertDatabaseHas('school_classes', [
            'school_id' => $this->school->school_id,
            'grade_id' => $level->id,
            'class_name' => 'L7-A'
        ]);
    }

    public function test_can_batch_generate_levels(): void
    {
        $response = $this->post(route('config.levels.batch'), [
            'pattern' => 'Level {N}',
            'code_prefix' => 'L',
            'from_level' => 1,
            'to_level' => 6,
            'create_default_class' => 1
        ]);

        $response->assertRedirect(route('config.levels'));

        for ($i = 1; $i <= 6; $i++) {
            $this->assertDatabaseHas('school_grade_levels', [
                'school_id' => $this->school->school_id,
                'grade_name' => "Level {$i}",
                'grade_code' => "L{$i}"
            ]);

            $level = SchoolGradeLevel::where('school_id', $this->school->school_id)->where('grade_code', "L{$i}")->first();
            $this->assertDatabaseHas('school_classes', [
                'school_id' => $this->school->school_id,
                'grade_id' => $level->id,
                'class_name' => "L{$i}-A"
            ]);
        }
    }

    public function test_can_batch_generate_levels_with_custom_class_names(): void
    {
        $response = $this->post(route('config.levels.batch'), [
            'pattern' => 'Grade {N}',
            'code_prefix' => 'G',
            'from_level' => 10,
            'to_level' => 12,
            'create_classes' => 1,
            'class_sections' => 'Science, Arts, Business',
            'class_format' => 'CODE_DASH_SEC'
        ]);

        $response->assertRedirect(route('config.levels'));

        foreach ([10, 11, 12] as $num) {
            $level = SchoolGradeLevel::where('school_id', $this->school->school_id)->where('grade_code', "G{$num}")->first();
            $this->assertNotNull($level);

            foreach (['Science', 'Arts', 'Business'] as $sec) {
                $this->assertDatabaseHas('school_classes', [
                    'school_id' => $this->school->school_id,
                    'grade_id' => $level->id,
                    'class_name' => "G{$num}-{$sec}"
                ]);
            }
        }
    }

    public function test_can_update_school_level(): void
    {
        $level = SchoolGradeLevel::create([
            'school_id' => $this->school->school_id,
            'grade_name' => 'Old Level',
            'grade_code' => 'OLD',
            'display_order' => 1
        ]);

        $response = $this->post(route('config.levels.update', $level->id), [
            'grade_name' => 'Senior Level 12',
            'grade_code' => 'SL12',
            'display_order' => 12
        ]);

        $response->assertRedirect(route('config.levels'));
        $this->assertDatabaseHas('school_grade_levels', [
            'id' => $level->id,
            'grade_name' => 'Senior Level 12',
            'grade_code' => 'SL12',
            'display_order' => 12
        ]);
    }

    public function test_can_delete_empty_level_safely(): void
    {
        $level = SchoolGradeLevel::create([
            'school_id' => $this->school->school_id,
            'grade_name' => 'To Be Removed',
            'grade_code' => 'TBR',
            'display_order' => 99
        ]);

        $response = $this->post(route('config.levels.delete', $level->id));
        $response->assertRedirect(route('config.levels'));

        $this->assertDatabaseMissing('school_grade_levels', [
            'id' => $level->id
        ]);
    }

    public function test_cannot_delete_level_with_enrolled_students(): void
    {
        $level = SchoolGradeLevel::create([
            'school_id' => $this->school->school_id,
            'grade_name' => 'Grade 10',
            'grade_code' => 'G10',
            'display_order' => 10
        ]);

        $class = SchoolClass::create([
            'school_id' => $this->school->school_id,
            'grade_id' => $level->id,
            'class_name' => '10-A'
        ]);

        Student::create([
            'school_id' => $this->school->school_id,
            'class_id' => $class->id,
            'admission_number' => 'STU-001',
            'full_name' => 'John Doe'
        ]);

        // Attempt deletion without force
        $response = $this->post(route('config.levels.delete', $level->id));
        $response->assertSessionHas('error');

        // Level must still exist
        $this->assertDatabaseHas('school_grade_levels', [
            'id' => $level->id
        ]);
    }

    public function test_can_create_and_delete_educational_stages(): void
    {
        $response = $this->post(route('config.divisions.store'), [
            'division_name' => 'Kindergarten Stage',
            'display_order' => 1
        ]);

        $response->assertRedirect(route('config.levels'));
        $this->assertDatabaseHas('school_divisions', [
            'school_id' => $this->school->school_id,
            'division_name' => 'Kindergarten Stage'
        ]);

        $div = SchoolDivision::where('division_name', 'Kindergarten Stage')->first();

        $delResponse = $this->post(route('config.divisions.delete', $div->id));
        $delResponse->assertRedirect(route('config.levels'));

        $this->assertDatabaseMissing('school_divisions', [
            'id' => $div->id
        ]);
    }

    public function test_delete_division_via_get_or_nonexistent_id_gracefully_redirects(): void
    {
        // Testing GET request to /configuration/divisions/4/delete (exactly what user encountered)
        $response = $this->get('/configuration/divisions/4/delete');
        $response->assertRedirect(route('config.levels'));
        $response->assertSessionHas('error');
    }
}
