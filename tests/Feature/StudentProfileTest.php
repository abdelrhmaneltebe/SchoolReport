<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\School;
use App\Models\User;
use App\Models\SchoolGradeLevel;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\MasterSubject;
use App\Models\ClassSubjectAssignment;
use App\Models\GradeRecord;
use App\Models\AttendanceRecord;
use App\Models\SchoolGradingSystem;

class StudentProfileTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $admin;
    protected SchoolGradeLevel $gradeLevel;
    protected SchoolClass $class;
    protected Student $student;
    protected MasterSubject $subject;
    protected ClassSubjectAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'school_id' => 'SCH-PROFILE-TEST',
            'school_name' => 'Profile Test Academy',
            'school_code' => 'PTA',
            'active_scale_model' => 'LETTER_GRADE_GPA',
        ]);
        $this->school->ensureReportDirectoriesExist();

        $this->admin = User::create([
            'name' => 'Admin Tester',
            'email' => 'admin_profile_test@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->gradeLevel = SchoolGradeLevel::create([
            'school_id' => $this->school->school_id,
            'grade_name' => 'Grade 10',
            'grade_code' => 'G10',
            'display_order' => 1,
        ]);

        $this->class = SchoolClass::create([
            'school_id' => $this->school->school_id,
            'grade_id' => $this->gradeLevel->id,
            'class_name' => 'Class 10-A',
            'max_capacity' => 30,
        ]);

        $this->student = Student::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class->id,
            'admission_number' => 'ADM-2026-001',
            'full_name' => 'Alexander Hamilton',
            'gender' => 'Boy',
            'status' => 'ACTIVE',
        ]);

        $this->subject = MasterSubject::create([
            'school_id' => $this->school->school_id,
            'subject_name' => 'Advanced Physics',
            'subject_code' => 'PHYS-101',
            'default_credits' => 3.0,
        ]);

        $this->assignment = ClassSubjectAssignment::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'assigned_teacher' => 'Dr. Robert Oppenheimer',
        ]);

        // Add a grade record
        GradeRecord::create([
            'school_id' => $this->school->school_id,
            'student_id' => $this->student->id,
            'class_subject_assignment_id' => $this->assignment->id,
            'total_score' => 92.5,
            'teacher_remarks' => 'Outstanding analytical mastery.',
        ]);

        // Add attendance records
        AttendanceRecord::create([
            'school_id' => $this->school->school_id,
            'student_id' => $this->student->id,
            'attendance_date' => '2026-11-01',
            'status' => 'PRESENT',
            'session_type' => 'FULL_DAY',
        ]);

        AttendanceRecord::create([
            'school_id' => $this->school->school_id,
            'student_id' => $this->student->id,
            'attendance_date' => '2026-11-02',
            'status' => 'TARDY',
            'session_type' => 'FULL_DAY',
            'remarks' => 'Delayed by traffic',
        ]);
    }

    public function test_student_profile_page_loads_successfully_with_all_metrics(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.students.profile', $this->student->id));

        $response->assertStatus(200);
        $response->assertSee('Alexander Hamilton');
        $response->assertSee('ADM-2026-001');
        $response->assertSee('Class 10-A');
        $response->assertSee('Grade 10');
        $response->assertSee('Profile Test Academy');

        // Check tabs and KPIs
        $response->assertSee('Cumulative Performance');
        $response->assertSee('Attendance Rate');
        $response->assertSee('Course Enrolments');
        $response->assertSee('Advanced Physics');
        $response->assertSee('Dr. Robert Oppenheimer');
    }

    public function test_student_profile_short_url_alias_works(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('students.profile', $this->student->id));

        $response->assertStatus(200);
        $response->assertSee('Alexander Hamilton');
    }

    public function test_student_profile_displays_attendance_history(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.students.profile', $this->student->id));

        $response->assertStatus(200);
        $response->assertSee('PRESENT');
        $response->assertSee('TARDY');
        $response->assertSee('Delayed by traffic');
    }

    public function test_student_profile_enforces_tenant_isolation(): void
    {
        $otherSchool = School::create([
            'school_id' => 'SCH-OTHER',
            'school_name' => 'Foreign Academy',
            'school_code' => 'FA',
            'active_scale_model' => 'PERCENTAGE_100',
        ]);

        $foreignStudent = Student::create([
            'school_id' => $otherSchool->school_id,
            'admission_number' => 'ADM-FOREIGN',
            'full_name' => 'Foreign Student',
        ]);

        // Attempting to access foreign student while active school is SCH-PROFILE-TEST
        $response = $this->actingAs($this->admin)
            ->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.students.profile', $foreignStudent->id));

        $response->assertStatus(404);
    }

    public function test_unassigned_student_profile_renders_gracefully(): void
    {
        $unassigned = Student::create([
            'school_id' => $this->school->school_id,
            'admission_number' => 'ADM-UNASSIGNED',
            'full_name' => 'Unassigned Pupil',
            'gender' => 'Girl',
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('config.students.profile', $unassigned->id));

        $response->assertStatus(200);
        $response->assertSee('Unassigned Pupil');
        $response->assertSee('ADM-UNASSIGNED');
        $response->assertSee('Unassigned to any class section');
    }

    public function test_student_details_can_be_updated_from_profile_modal(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('config.students.update', $this->student->id), [
                'admission_number' => 'ADM-2026-001',
                'full_name' => 'Alexander Hamilton Jr.',
                'class_id' => $this->class->id,
                'gender' => 'Boy',
                'status' => 'ACTIVE',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->student->refresh();
        $this->assertEquals('Alexander Hamilton Jr.', $this->student->full_name);
    }
}
