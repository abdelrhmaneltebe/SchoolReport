<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\AttendanceRecord;
use App\Models\SchoolAttendancePolicy;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    /**
     * Resolve the active school tenant context.
     */
    protected function resolveActiveSchool(Request $request): School
    {
        if (session()->has('active_school_id')) {
            $school = School::where('school_id', session('active_school_id'))->first();
            if ($school) {
                return $school;
            }
        }

        if (auth()->check() && auth()->user()->school_id) {
            $school = School::where('school_id', auth()->user()->school_id)->first();
            if ($school) {
                return $school;
            }
        }

        return School::first() ?? new School(['school_id' => 'SCH-DEFAULT', 'school_name' => 'Default School']);
    }

    /**
     * Display Daily Attendance Register / Roll Call.
     */
    public function index(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $classes = SchoolClass::where('school_id', $activeSchool->school_id)
            ->with(['gradeLevel', 'students'])
            ->withCount('students')
            ->orderBy('class_name')
            ->get();

        $selectedClassId = $request->input('class_id') ?? ($classes->first()?->id);
        $selectedClass = $classes->firstWhere('id', $selectedClassId);

        $dateInput = $request->input('date') ?? Carbon::today()->format('Y-m-d');
        try {
            $date = Carbon::parse($dateInput)->format('Y-m-d');
        } catch (\Exception $e) {
            $date = Carbon::today()->format('Y-m-d');
        }

        $sessionType = $request->input('session_type', 'FULL_DAY');

        $students = $selectedClass
            ? $selectedClass->students()->orderBy('full_name')->get()
            : collect();

        // Load existing attendance records for the students on this date
        $existingRecords = AttendanceRecord::where('school_id', $activeSchool->school_id)
            ->whereIn('student_id', $students->pluck('id'))
            ->where('attendance_date', $date)
            ->where('session_type', $sessionType)
            ->get()
            ->keyBy('student_id');

        // Load attendance policy
        $policy = SchoolAttendancePolicy::firstOrCreate(
            ['school_id' => $activeSchool->school_id],
            [
                'tracking_mode' => 'DAILY_HOMEROOM',
                'min_attendance_rate' => 85.00,
                'chronic_truancy_days' => 3,
                'tardies_per_absence' => 3
            ]
        );

        // Compute daily summary stats
        $totalStudents = $students->count();
        $presentCount = 0;
        $unexcusedCount = 0;
        $excusedCount = 0;
        $tardyCount = 0;
        $recordedCount = $existingRecords->count();

        foreach ($students as $student) {
            $record = $existingRecords->get($student->id);
            $status = $record ? $record->status : null;
            match ($status) {
                'PRESENT' => $presentCount++,
                'UNEXCUSED' => $unexcusedCount++,
                'EXCUSED' => $excusedCount++,
                'TARDY' => $tardyCount++,
                default => null,
            };
        }

        $rateBase = max(1, $totalStudents);
        $attendanceRate = $recordedCount > 0
            ? round((($presentCount + $tardyCount) / $rateBase) * 100, 1)
            : null;

        $stats = [
            'total' => $totalStudents,
            'recorded' => $recordedCount,
            'pending' => max(0, $totalStudents - $recordedCount),
            'present' => $presentCount,
            'unexcused' => $unexcusedCount,
            'excused' => $excusedCount,
            'tardy' => $tardyCount,
            'rate' => $attendanceRate,
            'minRate' => $policy->min_attendance_rate
        ];

        return view('attendance.index', compact(
            'activeSchool',
            'classes',
            'selectedClass',
            'date',
            'sessionType',
            'students',
            'existingRecords',
            'policy',
            'stats'
        ));
    }

    /**
     * Batch save daily attendance records for a class on a specific date.
     */
    public function saveBatch(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'attendance_date' => 'required|date',
            'session_type' => 'nullable|string',
            'attendance' => 'nullable|array',
            'attendance.*.status' => 'required|in:PRESENT,UNEXCUSED,EXCUSED,TARDY',
            'attendance.*.remarks' => 'nullable|string|max:255',
        ]);

        $class = SchoolClass::where('school_id', $activeSchool->school_id)
            ->where('id', $request->input('class_id'))
            ->firstOrFail();

        $date = Carbon::parse($request->input('attendance_date'))->format('Y-m-d');
        $sessionType = $request->input('session_type', 'FULL_DAY');
        $attendanceData = $request->input('attendance', []);

        $studentIds = $class->students()->pluck('id')->toArray();

        foreach ($attendanceData as $studentId => $data) {
            // Verify student belongs to this class
            if (!in_array($studentId, $studentIds)) {
                continue;
            }

            AttendanceRecord::updateOrCreate(
                [
                    'school_id' => $activeSchool->school_id,
                    'student_id' => $studentId,
                    'attendance_date' => $date,
                    'session_type' => $sessionType,
                ],
                [
                    'status' => $data['status'],
                    'remarks' => $data['remarks'] ?? null,
                ]
            );
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Attendance saved successfully.',
                'date' => $date,
            ]);
        }

        return redirect()->route('attendance.index', [
            'class_id' => $class->id,
            'date' => $date,
            'session_type' => $sessionType
        ])->with('success', "Daily attendance for {$class->class_name} on {$date} successfully saved!");
    }

    /**
     * Quick AJAX update for a single student's attendance status.
     */
    public function quickUpdate(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $request->validate([
            'student_id' => 'required|exists:students,id',
            'attendance_date' => 'required|date',
            'status' => 'required|in:PRESENT,UNEXCUSED,EXCUSED,TARDY',
            'session_type' => 'nullable|string',
            'remarks' => 'nullable|string|max:255',
        ]);

        $student = Student::where('school_id', $activeSchool->school_id)
            ->where('id', $request->input('student_id'))
            ->firstOrFail();

        $date = Carbon::parse($request->input('attendance_date'))->format('Y-m-d');
        $sessionType = $request->input('session_type', 'FULL_DAY');

        $record = AttendanceRecord::updateOrCreate(
            [
                'school_id' => $activeSchool->school_id,
                'student_id' => $student->id,
                'attendance_date' => $date,
                'session_type' => $sessionType,
            ],
            [
                'status' => $request->input('status'),
                'remarks' => $request->input('remarks'),
            ]
        );

        // Recalculate class stats
        $class = $student->schoolClass;
        $stats = null;
        if ($class) {
            $students = $class->students;
            $records = AttendanceRecord::where('school_id', $activeSchool->school_id)
                ->whereIn('student_id', $students->pluck('id'))
                ->where('attendance_date', $date)
                ->where('session_type', $sessionType)
                ->get()
                ->keyBy('student_id');

            $present = $records->where('status', 'PRESENT')->count();
            $unexcused = $records->where('status', 'UNEXCUSED')->count();
            $excused = $records->where('status', 'EXCUSED')->count();
            $tardy = $records->where('status', 'TARDY')->count();
            $total = $students->count();

            $stats = [
                'present' => $present,
                'unexcused' => $unexcused,
                'excused' => $excused,
                'tardy' => $tardy,
                'recorded' => $records->count(),
                'rate' => $total > 0 ? round((($present + $tardy) / $total) * 100, 1) : 0,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated',
            'record' => $record,
            'stats' => $stats
        ]);
    }

    /**
     * Mark all students in a class with a single status (e.g. All Present).
     */
    public function markAll(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'attendance_date' => 'required|date',
            'status' => 'required|in:PRESENT,UNEXCUSED,EXCUSED,TARDY',
            'session_type' => 'nullable|string',
        ]);

        $class = SchoolClass::where('school_id', $activeSchool->school_id)
            ->where('id', $request->input('class_id'))
            ->firstOrFail();

        $date = Carbon::parse($request->input('attendance_date'))->format('Y-m-d');
        $sessionType = $request->input('session_type', 'FULL_DAY');
        $status = $request->input('status');

        $students = $class->students;
        foreach ($students as $student) {
            AttendanceRecord::updateOrCreate(
                [
                    'school_id' => $activeSchool->school_id,
                    'student_id' => $student->id,
                    'attendance_date' => $date,
                    'session_type' => $sessionType,
                ],
                [
                    'status' => $status,
                ]
            );
        }

        return redirect()->route('attendance.index', [
            'class_id' => $class->id,
            'date' => $date,
            'session_type' => $sessionType
        ])->with('success', "Marked all {$students->count()} students as {$status} for {$date}.");
    }

    /**
     * Display Class Attendance Sheet & Aggregated Summary.
     */
    public function summary(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $classes = SchoolClass::where('school_id', $activeSchool->school_id)
            ->with(['gradeLevel', 'students'])
            ->orderBy('class_name')
            ->get();

        $selectedClassId = $request->input('class_id') ?? ($classes->first()?->id);
        $selectedClass = $classes->firstWhere('id', $selectedClassId);

        $students = $selectedClass
            ? $selectedClass->students()->orderBy('full_name')->get()
            : collect();

        $startDate = $request->input('start_date') ?? Carbon::today()->startOfMonth()->format('Y-m-d');
        $endDate = $request->input('end_date') ?? Carbon::today()->format('Y-m-d');

        $policy = SchoolAttendancePolicy::firstOrCreate(
            ['school_id' => $activeSchool->school_id],
            [
                'tracking_mode' => 'DAILY_HOMEROOM',
                'min_attendance_rate' => 85.00,
                'chronic_truancy_days' => 3,
                'tardies_per_absence' => 3
            ]
        );

        $studentSummaries = [];
        $tardiesPerAbsence = max(1, $policy->tardies_per_absence ?? 3);

        foreach ($students as $student) {
            $records = AttendanceRecord::where('school_id', $activeSchool->school_id)
                ->where('student_id', $student->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->get();

            $totalLogged = $records->count();
            $present = $records->where('status', 'PRESENT')->count();
            $unexcused = $records->where('status', 'UNEXCUSED')->count();
            $excused = $records->where('status', 'EXCUSED')->count();
            $tardy = $records->where('status', 'TARDY')->count();

            // Compute effective unexcused absences from tardies
            $equivalentFromTardies = (int)floor($tardy / $tardiesPerAbsence);
            $effectiveAbsences = $unexcused + $equivalentFromTardies;

            $rate = $totalLogged > 0
                ? round((($present + $tardy) / $totalLogged) * 100, 1)
                : 100.0;

            $isTruant = $unexcused >= ($policy->chronic_truancy_days ?? 3) || $rate < ($policy->min_attendance_rate ?? 85.0);

            $studentSummaries[] = [
                'student' => $student,
                'total_days' => $totalLogged,
                'present' => $present,
                'unexcused' => $unexcused,
                'excused' => $excused,
                'tardy' => $tardy,
                'equivalent_tardies' => $equivalentFromTardies,
                'effective_absences' => $effectiveAbsences,
                'rate' => $rate,
                'is_truant' => $isTruant,
            ];
        }

        return view('attendance.summary', compact(
            'activeSchool',
            'classes',
            'selectedClass',
            'startDate',
            'endDate',
            'studentSummaries',
            'policy'
        ));
    }

    /**
     * Export attendance summary as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $activeSchool = $this->resolveActiveSchool($request);
        $classId = $request->input('class_id');
        $class = SchoolClass::where('school_id', $activeSchool->school_id)->findOrFail($classId);

        $startDate = $request->input('start_date') ?? Carbon::today()->startOfMonth()->format('Y-m-d');
        $endDate = $request->input('end_date') ?? Carbon::today()->format('Y-m-d');

        $policy = SchoolAttendancePolicy::where('school_id', $activeSchool->school_id)->first();
        $tardiesPerAbsence = max(1, $policy->tardies_per_absence ?? 3);

        $students = $class->students()->orderBy('full_name')->get();

        $filename = "attendance_{$class->class_name}_{$startDate}_to_{$endDate}.csv";

        return response()->streamDownload(function () use ($students, $activeSchool, $startDate, $endDate, $tardiesPerAbsence, $policy) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, [
                'Admission No',
                'Student Name',
                'Class',
                'Enrolled Days',
                'Present Days',
                'Unexcused Absences',
                'Excused Absences',
                'Tardies',
                'Converted Absences (from Tardies)',
                'Net Absences',
                'Attendance Rate (%)',
                'Status'
            ]);

            foreach ($students as $student) {
                $records = AttendanceRecord::where('school_id', $activeSchool->school_id)
                    ->where('student_id', $student->id)
                    ->whereBetween('attendance_date', [$startDate, $endDate])
                    ->get();

                $total = $records->count();
                $present = $records->where('status', 'PRESENT')->count();
                $unexcused = $records->where('status', 'UNEXCUSED')->count();
                $excused = $records->where('status', 'EXCUSED')->count();
                $tardy = $records->where('status', 'TARDY')->count();
                $equivalent = (int)floor($tardy / $tardiesPerAbsence);
                $netAbsences = $unexcused + $equivalent;
                $rate = $total > 0 ? round((($present + $tardy) / $total) * 100, 1) : 100.0;
                $status = ($unexcused >= ($policy->chronic_truancy_days ?? 3) || $rate < ($policy->min_attendance_rate ?? 85.0))
                    ? 'CHRONIC TRUANCY / LOW ATTENDANCE'
                    : 'REGULAR';

                fputcsv($handle, [
                    $student->admission_number,
                    $student->full_name,
                    $student->schoolClass->class_name ?? '',
                    $total,
                    $present,
                    $unexcused,
                    $excused,
                    $tardy,
                    $equivalent,
                    $netAbsences,
                    $rate . '%',
                    $status
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
