<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\School;
use App\Models\SchoolGradeLevel;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\AttendanceRecord;
use App\Models\SchoolAttendancePolicy;
use App\Services\ReportAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected School $otherSchool;
    protected SchoolGradeLevel $grade10;
    protected SchoolClass $class10A;
    protected Student $student1;
    protected Student $student2;
    protected Student $student3;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Primary School & Academic Structure
        $this->school = School::create([
            'school_id' => 'SCH-ATT-TEST',
            'school_name' => 'Atlantic International School',
            'school_code' => 'AIS',
        ]);

        $this->otherSchool = School::create([
            'school_id' => 'SCH-OTHER-TEST',
            'school_name' => 'Pacific Academy',
            'school_code' => 'PAC',
        ]);

        $this->grade10 = SchoolGradeLevel::create([
            'school_id' => $this->school->school_id,
            'grade_name' => 'Grade 10',
            'grade_code' => 'G10',
            'display_order' => 10,
        ]);

        $this->class10A = SchoolClass::create([
            'school_id' => $this->school->school_id,
            'grade_id' => $this->grade10->id,
            'class_name' => '10-A Diamond',
        ]);

        // 2. Create Students
        $this->student1 = Student::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class10A->id,
            'admission_number' => 'STD-2026-001',
            'full_name' => 'Alice Adams',
            'gender' => 'Female',
            'status' => 'ACTIVE',
        ]);

        $this->student2 = Student::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class10A->id,
            'admission_number' => 'STD-2026-002',
            'full_name' => 'Bob Builder',
            'gender' => 'Male',
            'status' => 'ACTIVE',
        ]);

        $this->student3 = Student::create([
            'school_id' => $this->school->school_id,
            'class_id' => $this->class10A->id,
            'admission_number' => 'STD-2026-003',
            'full_name' => 'Charlie Clark',
            'gender' => 'Male',
            'status' => 'ACTIVE',
        ]);

        // 3. Create Attendance Policy
        SchoolAttendancePolicy::create([
            'school_id' => $this->school->school_id,
            'tracking_mode' => 'DAILY_HOMEROOM',
            'min_attendance_rate' => 85.00,
            'chronic_truancy_days' => 3,
            'tardies_per_absence' => 3,
        ]);
    }

    /**
     * Test 1: Daily Roll Call Register view loads successfully.
     */
    public function test_attendance_register_page_loads_with_class_and_students(): void
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('attendance.index', [
                'class_id' => $this->class10A->id,
                'date' => '2026-11-01',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Class Attendance Register &amp; Daily Roll Call', false);
        $response->assertSee('10-A Diamond');
        $response->assertSee('Alice Adams');
        $response->assertSee('Bob Builder');
        $response->assertSee('Charlie Clark');
        $response->assertSee('Pillar 1 Operational');
    }

    /**
     * Test 2: Batch saving daily attendance for the whole class.
     */
    public function test_batch_saving_attendance_records(): void
    {
        $payload = [
            'class_id' => $this->class10A->id,
            'attendance_date' => '2026-11-02',
            'session_type' => 'FULL_DAY',
            'attendance' => [
                $this->student1->id => [
                    'status' => 'PRESENT',
                    'remarks' => 'On time',
                ],
                $this->student2->id => [
                    'status' => 'UNEXCUSED',
                    'remarks' => 'Skipped class',
                ],
                $this->student3->id => [
                    'status' => 'TARDY',
                    'remarks' => 'Late 15 mins',
                ],
            ],
        ];

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('attendance.batch_save'), $payload);

        $response->assertRedirect(route('attendance.index', [
            'class_id' => $this->class10A->id,
            'date' => '2026-11-02',
            'session_type' => 'FULL_DAY',
        ]));

        $this->assertDatabaseHas('attendance_records', [
            'school_id' => $this->school->school_id,
            'student_id' => $this->student1->id,
            'attendance_date' => '2026-11-02',
            'status' => 'PRESENT',
            'remarks' => 'On time',
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'school_id' => $this->school->school_id,
            'student_id' => $this->student2->id,
            'attendance_date' => '2026-11-02',
            'status' => 'UNEXCUSED',
            'remarks' => 'Skipped class',
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'school_id' => $this->school->school_id,
            'student_id' => $this->student3->id,
            'attendance_date' => '2026-11-02',
            'status' => 'TARDY',
            'remarks' => 'Late 15 mins',
        ]);
    }

    /**
     * Test 3: Quick AJAX update of a single student's status with live stats recalculation.
     */
    public function test_quick_ajax_update_attendance_status(): void
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->postJson(route('attendance.quick_update'), [
                'student_id' => $this->student1->id,
                'attendance_date' => '2026-11-03',
                'status' => 'EXCUSED',
                'remarks' => 'Medical appointment',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Status updated',
            'record' => [
                'student_id' => $this->student1->id,
                'attendance_date' => '2026-11-03',
                'status' => 'EXCUSED',
            ],
            'stats' => [
                'excused' => 1,
                'recorded' => 1,
            ],
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $this->student1->id,
            'attendance_date' => '2026-11-03',
            'status' => 'EXCUSED',
            'remarks' => 'Medical appointment',
        ]);
    }

    /**
     * Test 4: One-click "Mark All Present" batch action.
     */
    public function test_mark_all_students_present_action(): void
    {
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->post(route('attendance.mark_all'), [
                'class_id' => $this->class10A->id,
                'attendance_date' => '2026-11-04',
                'status' => 'PRESENT',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseCount('attendance_records', 3);
        $this->assertEquals(
            3,
            AttendanceRecord::where('attendance_date', '2026-11-04')
                ->where('status', 'PRESENT')
                ->count()
        );
    }

    /**
     * Test 5: Attendance Summary Broadsheet displays correct calculations & tardy conversions.
     */
    public function test_attendance_summary_broadsheet_and_tardy_conversions(): void
    {
        // Give Bob Builder 3 tardies across 3 days -> 3 tardies / 3 = 1 converted absence
        foreach (['2026-11-01', '2026-11-02', '2026-11-03'] as $date) {
            AttendanceRecord::create([
                'school_id' => $this->school->school_id,
                'student_id' => $this->student2->id,
                'attendance_date' => $date,
                'status' => 'TARDY',
                'session_type' => 'FULL_DAY',
            ]);
        }

        // Give Bob 2 unexcused absences -> Total effective absences = 2 + 1 = 3 (triggers chronic truancy)
        foreach (['2026-11-04', '2026-11-05'] as $date) {
            AttendanceRecord::create([
                'school_id' => $this->school->school_id,
                'student_id' => $this->student2->id,
                'attendance_date' => $date,
                'status' => 'UNEXCUSED',
                'session_type' => 'FULL_DAY',
            ]);
        }

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('attendance.summary', [
                'class_id' => $this->class10A->id,
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-05',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Class Attendance Summary &amp; Broadsheet', false);
        $response->assertSee('Bob Builder');
        // Bob has 3 tardies and 2 unexcused
        $response->assertSee('Truancy Warning');
    }

    /**
     * Test 6: CSV Export streams valid CSV data with attendance stats.
     */
    public function test_export_attendance_csv_stream(): void
    {
        AttendanceRecord::create([
            'school_id' => $this->school->school_id,
            'student_id' => $this->student1->id,
            'attendance_date' => '2026-11-01',
            'status' => 'PRESENT',
            'session_type' => 'FULL_DAY',
        ]);

        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->get(route('attendance.export', [
                'class_id' => $this->class10A->id,
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-05',
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Admission No', $content);
        $this->assertStringContainsString('Enrolled Days', $content);
        $this->assertStringContainsString('Alice Adams', $content);
        $this->assertStringContainsString('STD-2026-001', $content);
    }

    /**
     * Test 7: Multi-tenant isolation for attendance records.
     */
    public function test_tenant_isolation_on_attendance(): void
    {
        // Create grade level & student in Other School
        $otherGrade = SchoolGradeLevel::create([
            'school_id' => $this->otherSchool->school_id,
            'grade_name' => 'Other Grade 10',
            'grade_code' => 'OG10',
            'display_order' => 1,
        ]);

        $otherClass = SchoolClass::create([
            'school_id' => $this->otherSchool->school_id,
            'grade_id' => $otherGrade->id,
            'class_name' => 'Other 10-A',
        ]);

        $otherStudent = Student::create([
            'school_id' => $this->otherSchool->school_id,
            'class_id' => $otherClass->id,
            'admission_number' => 'OTH-001',
            'full_name' => 'Foreign Student',
        ]);

        // Attempt to quick update student from other school while logged into primary school
        $response = $this->withSession(['active_school_id' => $this->school->school_id])
            ->postJson(route('attendance.quick_update'), [
                'student_id' => $otherStudent->id,
                'attendance_date' => '2026-11-01',
                'status' => 'PRESENT',
            ]);

        $response->assertStatus(404);
    }

    /**
     * Test 8: ReportAnalyticsService dynamically includes real attendance data in Report Cards.
     */
    public function test_report_card_analytics_uses_real_attendance_records(): void
    {
        // Record 10 attendance days for student1: 9 PRESENT, 1 UNEXCUSED
        for ($i = 1; $i <= 9; $i++) {
            AttendanceRecord::create([
                'school_id' => $this->school->school_id,
                'student_id' => $this->student1->id,
                'attendance_date' => "2026-11-0{$i}",
                'status' => 'PRESENT',
                'session_type' => 'FULL_DAY',
            ]);
        }
        AttendanceRecord::create([
            'school_id' => $this->school->school_id,
            'student_id' => $this->student1->id,
            'attendance_date' => '2026-11-10',
            'status' => 'UNEXCUSED',
            'session_type' => 'FULL_DAY',
        ]);

        $analytics = app(ReportAnalyticsService::class);
        $card = $analytics->buildStudentReportCardData($this->student1);

        $this->assertEquals(10, $card['attendance']['enrolledDays']);
        $this->assertEquals(9, $card['attendance']['presentDays']);
        $this->assertEquals(1, $card['attendance']['absentDays']);
        $this->assertEquals(90.0, $card['attendance']['attendanceRate']);
        $this->assertEquals('Regular', $card['attendance']['status']);
    }
}
