<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\AttendanceRecord;
use App\Models\GradeRecord;
use App\Models\ClassSubjectAssignment;
use App\Services\ExcelIngestionService;
use App\Services\ExcelTemplateGeneratorService;
use App\Services\GradingCalculationService;

class ExcelIngestionController extends Controller
{
    protected ExcelIngestionService $ingestionService;
    protected ExcelTemplateGeneratorService $templateService;
    protected GradingCalculationService $gradingService;

    public function __construct(
        ExcelIngestionService $ingestionService,
        ExcelTemplateGeneratorService $templateService,
        GradingCalculationService $gradingService
    ) {
        $this->ingestionService = $ingestionService;
        $this->templateService = $templateService;
        $this->gradingService = $gradingService;
    }

    /**
     * Show Excel Ingestion Hub.
     */
    public function index(Request $request)
    {
        $schoolId = auth()->check() && !auth()->user()->isSuperAdmin()
            ? auth()->user()->school_id
            : ($request->get('school_id', session('active_school_id', 'SCH-GV-01')));
        $school = School::where('school_id', $schoolId)->first() ?? School::first();
        session(['active_school_id' => $school->school_id]);

        $classes = SchoolClass::where('school_id', $school->school_id)
            ->withCount(['students', 'subjectAssignments'])
            ->get();

        return view('ingestion.index', compact('school', 'classes'));
    }

    /**
     * Download dynamic Excel template pre-configured for a specific class.
     */
    public function downloadTemplate($classId)
    {
        $class = SchoolClass::findOrFail($classId);
        $filePath = $this->templateService->generateTemplate($class);

        return response()->download($filePath, "Marksheet_Template_{$class->class_code}.xlsx")->deleteFileAfterSend(true);
    }

    /**
     * Parse uploaded file and show pre-flight anomaly audit.
     */
    public function previewUpload(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'spreadsheet' => 'required|file|mimes:xlsx,xls'
        ]);

        $class = SchoolClass::with(['gradeLevel', 'subjectAssignments.subject'])->findOrFail($request->class_id);
        $school = School::where('school_id', $class->school_id)->firstOrFail();

        $file = $request->file('spreadsheet');
        $tempPath = $file->storeAs('temp_uploads', uniqid('marksheet_') . '.xlsx');
        $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . $tempPath);

        $parsed = $this->ingestionService->parseClassSpreadsheet($fullPath, $class);

        // Save parsed payload in session for commit
        session([
            'pending_ingestion_' . $class->id => [
                'class_id' => $class->id,
                'temp_path' => $fullPath,
                'parsed_rows' => $parsed['valid_rows'],
                'anomalies' => $parsed['anomalies']
            ]
        ]);

        return view('ingestion.preview', [
            'school' => $school,
            'class' => $class,
            'validRows' => $parsed['valid_rows'],
            'anomalies' => $parsed['anomalies']
        ]);
    }

    /**
     * Commit validated student attendance & subject marks into the database.
     */
    public function commitUpload(Request $request, $classId)
    {
        $sessionKey = 'pending_ingestion_' . $classId;
        $pending = session($sessionKey);

        if (!$pending) {
            return redirect()->route('ingestion.index')->with('error', 'Ingestion session expired. Please re-upload your marksheet.');
        }

        $class = SchoolClass::with('subjectAssignments')->findOrFail($classId);
        $school = School::where('school_id', $class->school_id)->firstOrFail();

        $rows = $pending['parsed_rows'];
        $importedCount = 0;

        foreach ($rows as $row) {
            $student = Student::where('school_id', $school->school_id)
                ->where('admission_number', $row['admission_no'])
                ->first();

            if (!$student) continue;

            // 1. Commit Attendance Record
            AttendanceRecord::updateOrCreate(
                [
                    'school_id' => $school->school_id,
                    'student_id' => $student->id,
                    'academic_term' => 'Term 1 Final',
                    'academic_year' => '2026-2027'
                ],
                [
                    'enrolled_days' => $row['attendance']['enrolled'],
                    'present_days' => $row['attendance']['present'],
                    'absent_days' => $row['attendance']['absent'],
                    'tardy_days' => 0,
                    'attendance_percentage' => $row['attendance']['rate']
                ]
            );

            // 2. Commit Subject Grade Records
            foreach ($row['scores'] as $score) {
                // Determine grade letter
                $pct = $score['max'] > 0 ? ($score['total'] / $score['max']) * 100 : 0;
                $letter = $pct >= 90 ? 'A+' : ($pct >= 80 ? 'A' : ($pct >= 70 ? 'B' : ($pct >= 60 ? 'C' : 'F')));

                GradeRecord::updateOrCreate(
                    [
                        'school_id' => $school->school_id,
                        'student_id' => $student->id,
                        'class_subject_assignment_id' => $score['assignment_id'],
                        'academic_term' => 'Term 1 Final',
                        'academic_year' => '2026-2027'
                    ],
                    [
                        'class_id' => $class->id,
                        'cw_score' => $score['cw'],
                        'exam_score' => $score['exam'],
                        'total_score' => $score['total'],
                        'letter_grade' => $letter,
                        'teacher_remarks' => 'Imported via Excel Ingestion'
                    ]
                );
            }

            $importedCount++;
        }

        // Clean temp file
        if (file_exists($pending['temp_path'])) {
            @unlink($pending['temp_path']);
        }
        session()->forget($sessionKey);

        return redirect()->route('reports.broadsheet', ['class_id' => $class->id])
            ->with('success', "Successfully imported marks and attendance for {$importedCount} students!");
    }
}
