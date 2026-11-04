<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\AttendanceRecord;
use App\Models\SchoolAttendancePolicy;
use App\Models\ClassSubjectAssignment;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    /**
     * Predefined timetable period slots.
     */
    protected array $standardSlots = [
        'Period 1' => 'Period 1 (08:00 - 08:45)',
        'Period 2' => 'Period 2 (08:45 - 09:30)',
        'Period 3' => 'Period 3 (09:45 - 10:30)',
        'Period 4' => 'Period 4 (10:30 - 11:15)',
        'Period 5' => 'Period 5 (11:30 - 12:15)',
        'Period 6' => 'Period 6 (12:15 - 01:00)',
        'Period 7' => 'Period 7 (01:15 - 02:00)',
        'Period 8' => 'Period 8 (02:00 - 02:45)',
    ];

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
     * Supports both Full-Day (Homeroom) and Subject & Timetable Slot attendance.
     */
    public function index(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $classes = SchoolClass::where('school_id', $activeSchool->school_id)
            ->with([
                'gradeLevel',
                'students',
                'subjectAssignments.subject',
                'subjectAssignments.enrolledStudents'
            ])
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

        $mode = $request->input('mode', 'full_day'); // 'full_day' or 'slot'
        $slots = $this->standardSlots;

        $assignments = $selectedClass ? $selectedClass->subjectAssignments : collect();
        $selectedAssignmentId = $request->input('assignment_id') ?? ($assignments->first()?->id);
        $selectedAssignment = $assignments->firstWhere('id', $selectedAssignmentId);

        $slotName = $request->input('slot_name', 'Period 1');

        if ($mode === 'slot' && $selectedAssignment) {
            $sessionType = 'SUBJECT_SLOT';
            // Use effective students for this specific course (supports elective enrollments)
            $students = $selectedAssignment->effective_students;
            if ($students->isEmpty() && $selectedClass) {
                $students = $selectedClass->students()->orderBy('full_name')->get();
            }

            $existingRecords = AttendanceRecord::where('school_id', $activeSchool->school_id)
                ->whereIn('student_id', $students->pluck('id'))
                ->where('attendance_date', $date)
                ->where('session_type', 'SUBJECT_SLOT')
                ->where('class_subject_assignment_id', $selectedAssignment->id)
                ->where('slot_name', $slotName)
                ->get()
                ->keyBy('student_id');
        } else {
            $mode = 'full_day';
            $sessionType = 'FULL_DAY';
            $selectedAssignment = null;
            $slotName = null;

            $students = $selectedClass
                ? $selectedClass->students()->orderBy('full_name')->get()
                : collect();

            $existingRecords = AttendanceRecord::where('school_id', $activeSchool->school_id)
                ->whereIn('student_id', $students->pluck('id'))
                ->where('attendance_date', $date)
                ->where('session_type', 'FULL_DAY')
                ->whereNull('class_subject_assignment_id')
                ->get()
                ->keyBy('student_id');
        }

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
            'mode',
            'assignments',
            'selectedAssignment',
            'slotName',
            'slots',
            'sessionType',
            'students',
            'existingRecords',
            'policy',
            'stats'
        ));
    }

    /**
     * Batch save daily attendance records for a class or subject slot.
     */
    public function saveBatch(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'mode' => 'nullable|in:full_day,slot',
            'assignment_id' => 'nullable|exists:class_subject_assignments,id',
            'slot_name' => 'nullable|string|max:50',
            'attendance_date' => 'required|date',
            'attendance' => 'nullable|array',
            'attendance.*.status' => 'nullable|in:PRESENT,UNEXCUSED,EXCUSED,TARDY',
            'attendance.*.remarks' => 'nullable|string|max:255',
        ]);

        $class = SchoolClass::where('school_id', $activeSchool->school_id)
            ->where('id', $request->input('class_id'))
            ->firstOrFail();

        $date = Carbon::parse($request->input('attendance_date'))->format('Y-m-d');
        $mode = $request->input('mode', 'full_day');
        $attendanceData = $request->input('attendance', []);

        $assignment = null;
        if ($mode === 'slot' && $request->filled('assignment_id')) {
            $assignment = ClassSubjectAssignment::where('school_id', $activeSchool->school_id)
                ->where('class_id', $class->id)
                ->where('id', $request->input('assignment_id'))
                ->firstOrFail();
            $sessionType = 'SUBJECT_SLOT';
            $assignmentId = $assignment->id;
            $slotName = $request->input('slot_name', 'Period 1');
            $allowedStudentIds = $assignment->effective_students->pluck('id')->toArray();
        } else {
            $mode = 'full_day';
            $sessionType = 'FULL_DAY';
            $assignmentId = null;
            $slotName = null;
            $allowedStudentIds = $class->students()->pluck('id')->toArray();
        }

        foreach ($attendanceData as $studentId => $data) {
            if (!in_array($studentId, $allowedStudentIds)) {
                continue;
            }

            if (empty($data['status'])) {
                continue;
            }

            AttendanceRecord::updateOrCreate(
                [
                    'school_id' => $activeSchool->school_id,
                    'student_id' => $studentId,
                    'attendance_date' => $date,
                    'session_type' => $sessionType,
                    'class_subject_assignment_id' => $assignmentId,
                    'slot_name' => $slotName,
                ],
                [
                    'status' => $data['status'],
                    'remarks' => $data['remarks'] ?? null,
                ]
            );
        }

        $redirectParams = [
            'mode' => $mode,
            'class_id' => $class->id,
            'date' => $date,
        ];
        if ($mode === 'slot') {
            $redirectParams['assignment_id'] = $assignmentId;
            $redirectParams['slot_name'] = $slotName;
        }

        $scopeName = $mode === 'slot'
            ? ($assignment ? "{$assignment->subject->subject_name} ({$slotName})" : "Subject Slot")
            : "Daily Homeroom";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Attendance for {$scopeName} saved successfully.",
                'date' => $date,
            ]);
        }

        return redirect()->route('attendance.index', $redirectParams)
            ->with('success', "Attendance for {$class->class_name} • {$scopeName} on {$date} successfully saved!");
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
            'mode' => 'nullable|in:full_day,slot',
            'assignment_id' => 'nullable|exists:class_subject_assignments,id',
            'slot_name' => 'nullable|string|max:50',
            'remarks' => 'nullable|string|max:255',
        ]);

        $student = Student::where('school_id', $activeSchool->school_id)
            ->where('id', $request->input('student_id'))
            ->firstOrFail();

        $date = Carbon::parse($request->input('attendance_date'))->format('Y-m-d');
        $mode = $request->input('mode', 'full_day');

        if ($mode === 'slot' && $request->filled('assignment_id')) {
            $sessionType = 'SUBJECT_SLOT';
            $assignmentId = $request->input('assignment_id');
            $slotName = $request->input('slot_name', 'Period 1');
        } else {
            $mode = 'full_day';
            $sessionType = 'FULL_DAY';
            $assignmentId = null;
            $slotName = null;
        }

        $record = AttendanceRecord::updateOrCreate(
            [
                'school_id' => $activeSchool->school_id,
                'student_id' => $student->id,
                'attendance_date' => $date,
                'session_type' => $sessionType,
                'class_subject_assignment_id' => $assignmentId,
                'slot_name' => $slotName,
            ],
            [
                'status' => $request->input('status'),
                'remarks' => $request->input('remarks'),
            ]
        );

        // Recalculate stats for this context
        $stats = null;
        $class = $student->schoolClass;
        if ($class) {
            if ($mode === 'slot' && $assignmentId) {
                $assignment = ClassSubjectAssignment::find($assignmentId);
                $roster = $assignment ? $assignment->effective_students : $class->students;
            } else {
                $roster = $class->students;
            }

            $query = AttendanceRecord::where('school_id', $activeSchool->school_id)
                ->whereIn('student_id', $roster->pluck('id'))
                ->where('attendance_date', $date)
                ->where('session_type', $sessionType);

            if ($mode === 'slot') {
                $query->where('class_subject_assignment_id', $assignmentId)
                      ->where('slot_name', $slotName);
            } else {
                $query->whereNull('class_subject_assignment_id');
            }

            $records = $query->get()->keyBy('student_id');

            $present = $records->where('status', 'PRESENT')->count();
            $unexcused = $records->where('status', 'UNEXCUSED')->count();
            $excused = $records->where('status', 'EXCUSED')->count();
            $tardy = $records->where('status', 'TARDY')->count();
            $total = $roster->count();

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
     * Mark all students in a class or subject slot with a single status (e.g. All Present).
     */
    public function markAll(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'attendance_date' => 'required|date',
            'status' => 'required|in:PRESENT,UNEXCUSED,EXCUSED,TARDY',
            'mode' => 'nullable|in:full_day,slot',
            'assignment_id' => 'nullable|exists:class_subject_assignments,id',
            'slot_name' => 'nullable|string|max:50',
        ]);

        $class = SchoolClass::where('school_id', $activeSchool->school_id)
            ->where('id', $request->input('class_id'))
            ->firstOrFail();

        $date = Carbon::parse($request->input('attendance_date'))->format('Y-m-d');
        $status = $request->input('status');
        $mode = $request->input('mode', 'full_day');

        if ($mode === 'slot' && $request->filled('assignment_id')) {
            $assignment = ClassSubjectAssignment::where('school_id', $activeSchool->school_id)
                ->where('class_id', $class->id)
                ->where('id', $request->input('assignment_id'))
                ->firstOrFail();
            $sessionType = 'SUBJECT_SLOT';
            $assignmentId = $assignment->id;
            $slotName = $request->input('slot_name', 'Period 1');
            $students = $assignment->effective_students;
            if ($students->isEmpty()) {
                $students = $class->students;
            }
        } else {
            $mode = 'full_day';
            $sessionType = 'FULL_DAY';
            $assignmentId = null;
            $slotName = null;
            $students = $class->students;
        }

        foreach ($students as $student) {
            AttendanceRecord::updateOrCreate(
                [
                    'school_id' => $activeSchool->school_id,
                    'student_id' => $student->id,
                    'attendance_date' => $date,
                    'session_type' => $sessionType,
                    'class_subject_assignment_id' => $assignmentId,
                    'slot_name' => $slotName,
                ],
                [
                    'status' => $status,
                ]
            );
        }

        $redirectParams = [
            'mode' => $mode,
            'class_id' => $class->id,
            'date' => $date,
        ];
        if ($mode === 'slot') {
            $redirectParams['assignment_id'] = $assignmentId;
            $redirectParams['slot_name'] = $slotName;
        }

        return redirect()->route('attendance.index', $redirectParams)
            ->with('success', "Marked all {$students->count()} students as {$status} for {$date}.");
    }

    /**
     * Display Class Attendance Sheet & Aggregated Summary.
     * Supports both Full-Day Broadsheets and Subject & Slot attendance breakdowns.
     */
    public function summary(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $classes = SchoolClass::where('school_id', $activeSchool->school_id)
            ->with(['gradeLevel', 'students', 'subjectAssignments.subject'])
            ->orderBy('class_name')
            ->get();

        $selectedClassId = $request->input('class_id') ?? ($classes->first()?->id);
        $selectedClass = $classes->firstWhere('id', $selectedClassId);

        $mode = $request->input('mode', 'full_day'); // 'full_day' or 'slot'
        $assignments = $selectedClass ? $selectedClass->subjectAssignments : collect();
        $selectedAssignmentId = $request->input('assignment_id');
        $selectedAssignment = $assignments->firstWhere('id', $selectedAssignmentId);

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

        $students = $selectedClass
            ? $selectedClass->students()->orderBy('full_name')->get()
            : collect();

        $studentSummaries = [];
        $tardiesPerAbsence = max(1, $policy->tardies_per_absence ?? 3);

        foreach ($students as $student) {
            $query = AttendanceRecord::where('school_id', $activeSchool->school_id)
                ->where('student_id', $student->id)
                ->whereBetween('attendance_date', [$startDate, $endDate]);

            if ($mode === 'slot') {
                $query->where('session_type', 'SUBJECT_SLOT');
                if ($selectedAssignment) {
                    $query->where('class_subject_assignment_id', $selectedAssignment->id);
                }
            } else {
                $query->where('session_type', 'FULL_DAY');
            }

            $records = $query->get();

            $totalLogged = $records->count();
            $present = $records->where('status', 'PRESENT')->count();
            $unexcused = $records->where('status', 'UNEXCUSED')->count();
            $excused = $records->where('status', 'EXCUSED')->count();
            $tardy = $records->where('status', 'TARDY')->count();

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
            'mode',
            'assignments',
            'selectedAssignment',
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

        $mode = $request->input('mode', 'full_day');
        $assignmentId = $request->input('assignment_id');
        $assignment = $assignmentId ? ClassSubjectAssignment::find($assignmentId) : null;

        $startDate = $request->input('start_date') ?? Carbon::today()->startOfMonth()->format('Y-m-d');
        $endDate = $request->input('end_date') ?? Carbon::today()->format('Y-m-d');

        $policy = SchoolAttendancePolicy::where('school_id', $activeSchool->school_id)->first();
        $tardiesPerAbsence = max(1, $policy->tardies_per_absence ?? 3);

        $students = $class->students()->orderBy('full_name')->get();

        $prefix = $mode === 'slot' && $assignment
            ? "attendance_slot_{$assignment->subject->subject_name}_{$class->class_name}"
            : "attendance_{$class->class_name}";
        $filename = "{$prefix}_{$startDate}_to_{$endDate}.csv";

        return response()->streamDownload(function () use ($students, $activeSchool, $startDate, $endDate, $tardiesPerAbsence, $policy, $mode, $assignment) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            $headers = [
                'Admission No',
                'Student Name',
                'Class',
            ];
            if ($mode === 'slot') {
                $headers[] = 'Subject';
                $headers[] = 'Enrolled Slots/Periods';
                $headers[] = 'Present Slots';
                $headers[] = 'Unexcused Absences';
                $headers[] = 'Excused Absences';
                $headers[] = 'Tardies';
                $headers[] = 'Converted Absences';
                $headers[] = 'Net Absences';
                $headers[] = 'Slot Attendance Rate (%)';
                $headers[] = 'Status';
            } else {
                $headers[] = 'Enrolled Days';
                $headers[] = 'Present Days';
                $headers[] = 'Unexcused Absences';
                $headers[] = 'Excused Absences';
                $headers[] = 'Tardies';
                $headers[] = 'Converted Absences (from Tardies)';
                $headers[] = 'Net Absences';
                $headers[] = 'Attendance Rate (%)';
                $headers[] = 'Status';
            }

            fputcsv($handle, $headers);

            foreach ($students as $student) {
                $query = AttendanceRecord::where('school_id', $activeSchool->school_id)
                    ->where('student_id', $student->id)
                    ->whereBetween('attendance_date', [$startDate, $endDate]);

                if ($mode === 'slot') {
                    $query->where('session_type', 'SUBJECT_SLOT');
                    if ($assignment) {
                        $query->where('class_subject_assignment_id', $assignment->id);
                    }
                } else {
                    $query->where('session_type', 'FULL_DAY');
                }

                $records = $query->get();

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

                $row = [
                    $student->admission_number,
                    $student->full_name,
                    $student->schoolClass->class_name ?? '',
                ];

                if ($mode === 'slot') {
                    $row[] = $assignment ? $assignment->subject->subject_name : 'All Subjects';
                }

                $row = array_merge($row, [
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

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
