<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\GradeRecord;
use App\Models\AttendanceRecord;
use App\Services\JasperReportService;

class ReportCardController extends Controller
{
    protected JasperReportService $jasperService;

    public function __construct(JasperReportService $jasperService)
    {
        $this->jasperService = $jasperService;
    }

    /**
     * Display Unified Master Broadsheet for a class.
     */
    public function broadsheet(Request $request)
    {
        $schoolId = auth()->check() && !auth()->user()->isSuperAdmin()
            ? auth()->user()->school_id
            : ($request->get('school_id', session('active_school_id', 'SCH-GV-01')));
        $activeSchool = School::where('school_id', $schoolId)->first() ?? School::first();
        session(['active_school_id' => $activeSchool->school_id]);

        $classes = SchoolClass::where('school_id', $activeSchool->school_id)->get();
        
        $selectedClassId = $request->get('class_id', $classes->first()->id ?? null);
        $selectedClass = SchoolClass::with([
            'gradeLevel',
            'subjectAssignments.subject',
            'students.gradeRecords'
        ])->find($selectedClassId);

        $policy = $activeSchool->attendancePolicy;

        return view('reports.broadsheet', compact(
            'activeSchool',
            'classes',
            'selectedClass',
            'policy'
        ));
    }

    /**
     * Preview single report card dataset.
     */
    public function previewCard(Request $request, $studentId)
    {
        $student = Student::with([
            'schoolClass.subjectAssignments.subject',
            'gradeRecords.assignment.subject'
        ])->findOrFail($studentId);

        $school = School::where('school_id', $student->school_id)->with('attendancePolicy')->firstOrFail();

        // Calculate sample metrics
        $subjectRows = [];
        $totalObtained = 0;
        $totalMax = 0;

        foreach ($student->schoolClass->subjectAssignments as $assignment) {
            $record = $student->gradeRecords->firstWhere('class_subject_assignment_id', $assignment->id);
            $rawScore = $record ? (float)$record->total_score : 0.0;
            $maxScore = $assignment->custom_max_score ?? (float)$assignment->subject->max_score;

            $subjectRows[] = [
                'subjectCode' => $assignment->subject_number_override ?? $assignment->subject->subject_code,
                'subjectName' => $assignment->subject->subject_name,
                'rawScore' => $rawScore,
                'maxScore' => $maxScore,
                'letterGrade' => $record->letter_grade ?? 'A',
                'teacherRemarks' => $record->teacher_remarks ?? 'Good engagement.'
            ];

            $totalObtained += $rawScore;
            $totalMax += $maxScore;
        }

        $overallPct = $totalMax > 0 ? round(($totalObtained / $totalMax) * 100, 1) : 0;

        return view('reports.preview', compact('student', 'school', 'subjectRows', 'overallPct'));
    }

    /**
     * Dispatch JasperReports generation.
     */
    public function exportJasperPdf(Request $request, $studentId)
    {
        $student = Student::with([
            'schoolClass.subjectAssignments.subject',
            'gradeRecords.assignment.subject'
        ])->findOrFail($studentId);

        if (!$this->jasperService->isAvailable()) {
            return redirect()->route('reports.card.single', [
                'student' => $student->id,
                'print' => 1
            ])->with('info', 'ℹ️ On shared web hosting without Java runtime, your official report card is generated in High-Resolution Print / PDF format.');
        }

        $school = School::where('school_id', $student->school_id)->firstOrFail();

        if ($school->school_id === 'SCH-SGIS-GAM') {
            return app(ReportStudioController::class)->exportJasper($request, $studentId);
        }

        $subjectRows = [];
        $totalObtained = 0.0;
        $totalMax = 0.0;

        foreach ($student->schoolClass->subjectAssignments as $assignment) {
            $record = $student->gradeRecords->firstWhere('class_subject_assignment_id', $assignment->id);
            $rawScore = $record ? (float)$record->total_score : 0.0;
            $maxScore = (float)($assignment->custom_max_score ?? $assignment->subject->max_score);

            $totalObtained += $rawScore;
            $totalMax += $maxScore;

            $subjectRows[] = [
                'studentName' => $student->full_name,
                'admissionNumber' => $student->admission_number,
                'className' => $student->schoolClass->class_name,
                'gpaOrAverage' => '',
                'classRank' => '1 of 28',
                'honorStatus' => 'Principal\'s Honor List',
                'daysEnrolled' => 90,
                'daysPresent' => 88,
                'daysAbsent' => 2,
                'attendanceRate' => '97.8%',
                'subjectCode' => $assignment->subject_number_override ?? $assignment->subject->subject_code,
                'subjectName' => $assignment->subject->subject_name,
                'rawScore' => $rawScore,
                'maxScore' => $maxScore,
                'letterGrade' => $record->letter_grade ?? 'A',
                'teacherRemarks' => $record->teacher_remarks ?? 'Good performance'
            ];
        }

        $overallPct = $totalMax > 0 ? round(($totalObtained / $totalMax) * 100, 1) : 0;
        $honor = $overallPct >= 90 ? "Principal's Honor List" : ($overallPct >= 80 ? "Academic Distinction" : "Satisfactory");

        foreach ($subjectRows as &$row) {
            $row['gpaOrAverage'] = "{$overallPct}%";
            $row['honorStatus'] = $honor;
        }
        unset($row);

        $logoDiskPath = $school->getLogoDiskPath();
        $parameters = [
            'P_SCHOOL_NAME' => $school->school_name,
            'P_SCHOOL_CODE' => $school->school_code,
            'P_LOGO_PATH' => $logoDiskPath ? str_replace('\\', '/', $logoDiskPath) : '',
            'P_PRINCIPAL_SIGNATURE' => '',
            'P_ACADEMIC_YEAR' => '2026-2027',
            'P_TERM_NAME' => 'Term 1 Final',
            'P_SCALE_NAME' => $school->active_scale_model
        ];

        $outputPath = storage_path("app/schools/{$school->school_id}/exports/{$student->admission_number}.pdf");

        try {
            $this->jasperService->generateReport('official_report_card_a4.jrxml', $parameters, $subjectRows, $outputPath);
            return response()->download($outputPath);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'JasperReports execution error: ' . $e->getMessage());
        }
    }
}
