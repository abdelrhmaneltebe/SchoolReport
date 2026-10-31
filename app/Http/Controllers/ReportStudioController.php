<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\School;
use App\Models\SchoolGradeLevel;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\MasterSubject;
use App\Models\AcademicGradingPeriod;
use App\Models\ReportTemplate;
use App\Services\ReportAnalyticsService;
use App\Services\JasperReportService;
use App\Services\ReportDeploymentService;

class ReportStudioController extends Controller
{
    protected ReportAnalyticsService $analyticsService;
    protected JasperReportService $jasperService;
    protected ReportDeploymentService $deploymentService;

    public function __construct(
        ReportAnalyticsService $analyticsService,
        JasperReportService $jasperService,
        ReportDeploymentService $deploymentService
    ) {
        $this->analyticsService = $analyticsService;
        $this->jasperService = $jasperService;
        $this->deploymentService = $deploymentService;
    }

    /**
     * Resolve active school tenant.
     */
    protected function resolveActiveSchool(Request $request): School
    {
        $schoolId = auth()->check() && !auth()->user()->isSuperAdmin()
            ? auth()->user()->school_id
            : ($request->get('school_id', session('active_school_id', 'SCH-GV-01')));

        $school = School::where('school_id', $schoolId)->first() ?? School::first();
        session(['active_school_id' => $school->school_id]);
        return $school;
    }

    /**
     * Retrieve global general reports AND school-customized reports for active school.
     */
    protected function getAvailableReportTemplates(string $schoolId)
    {
        return ReportTemplate::where(function ($q) use ($schoolId) {
                $q->where('is_global', true)
                  ->orWhereNull('school_id')
                  ->orWhere('school_id', $schoolId);
            })
            ->where('is_active', true)
            ->orderBy('is_global', 'desc')
            ->orderBy('display_order')
            ->orderBy('title')
            ->get();
    }

    /**
     * Display Report Generator Studio.
     */
    public function index(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);
        $allSchools = School::orderBy('school_name')->get();

        $reportTemplates = $this->getAvailableReportTemplates($activeSchool->school_id);

        // Group into Global General vs School-Specific Customized
        $groupedTemplates = [
            '🌐 General Reports (All Schools)' => $reportTemplates->where('is_global', true),
            "🏫 School Customized ({$activeSchool->school_name})" => $reportTemplates->where('is_global', false),
        ];

        // Filter out empty groups
        $groupedTemplates = array_filter($groupedTemplates, fn($g) => $g->isNotEmpty());

        $levels = SchoolGradeLevel::where('school_id', $activeSchool->school_id)
            ->with(['classes.students'])
            ->orderBy('display_order')
            ->get();

        $allClasses = SchoolClass::where('school_id', $activeSchool->school_id)
            ->with(['gradeLevel', 'students'])
            ->orderBy('class_name')
            ->get();

        $subjects = MasterSubject::where('school_id', $activeSchool->school_id)
            ->orderBy('subject_name')
            ->get();

        $periods = AcademicGradingPeriod::where('school_id', $activeSchool->school_id)
            ->orderBy('display_order')
            ->get();

        // Selected filter state
        $selectedTemplateId = $request->get('report_template_id');
        $selectedReportType = $request->get('report_type');

        $selectedReportTemplate = null;
        if ($selectedTemplateId) {
            $selectedReportTemplate = $reportTemplates->firstWhere('id', $selectedTemplateId);
        } elseif ($selectedReportType) {
            $selectedReportTemplate = $reportTemplates->firstWhere('base_type', $selectedReportType)
                ?? $reportTemplates->firstWhere('report_code', $selectedReportType);
        }

        if (!$selectedReportTemplate) {
            $selectedReportTemplate = $reportTemplates->first();
        }

        $selectedReportType = $selectedReportTemplate ? $selectedReportTemplate->base_type : 'card_single';
        $selectedLevelId = $request->get('level_id');
        $selectedClassId = $request->get('class_id', $allClasses->first()->id ?? null);
        $selectedSubjectId = $request->get('subject_id');
        $selectedPeriodId = $request->get('period_id');
        $selectedStudentId = $request->get('student_id');

        // Resolve students for the chosen class
        $studentsInClass = [];
        if ($selectedClassId) {
            $studentsInClass = Student::where('class_id', $selectedClassId)
                ->orderBy('full_name')
                ->get();
            if (!$selectedStudentId && $studentsInClass->isNotEmpty()) {
                $selectedStudentId = $studentsInClass->first()->id;
            }
        }

        // Live preview data generation if parameters exist
        $previewData = null;
        $previewType = $selectedReportType;

        if ($request->has('generate') || $request->has('preview')) {
            if ($selectedReportType === 'card_single' && $selectedStudentId) {
                $student = Student::find($selectedStudentId);
                if ($student) {
                    $previewData = $this->analyticsService->buildStudentReportCardData($student);
                }
            } elseif ($selectedReportType === 'card_batch' && $selectedClassId) {
                $class = SchoolClass::find($selectedClassId);
                if ($class) {
                    $previewData = $this->analyticsService->buildClassReportCardsData($class);
                }
            } elseif (in_array($selectedReportType, ['analytics', 'at_risk', 'honor_roll']) && $selectedClassId) {
                $class = SchoolClass::find($selectedClassId);
                if ($class) {
                    $previewData = $this->analyticsService->buildClassAnalytics($class);
                }
            } elseif ($selectedReportType === 'broadsheet' && $selectedClassId) {
                return redirect()->route('reports.broadsheet', ['class_id' => $selectedClassId]);
            }
        }

        $availableParams = ReportTemplate::availableParameters();

        return view('reports.generator', compact(
            'activeSchool',
            'allSchools',
            'reportTemplates',
            'groupedTemplates',
            'selectedReportTemplate',
            'availableParams',
            'levels',
            'allClasses',
            'subjects',
            'periods',
            'studentsInClass',
            'selectedReportType',
            'selectedLevelId',
            'selectedClassId',
            'selectedSubjectId',
            'selectedPeriodId',
            'selectedStudentId',
            'previewData',
            'previewType'
        ));
    }

    /**
     * Dispatch report generation to targeted views or exports.
     */
    public function generate(Request $request)
    {
        $templateId = $request->input('report_template_id');
        $template = $templateId ? ReportTemplate::find($templateId) : null;

        $reportType = $template ? $template->base_type : $request->input('report_type', 'card_single');
        $classId = $request->input('class_id');
        $studentId = $request->input('student_id');
        $format = $request->input('format', $template->default_format ?? 'view');

        if ($format === 'csv' && $classId) {
            return $this->exportCsv($request, $classId);
        }

        if ($format === 'jasper' && $studentId) {
            return $this->exportJasper($request, $studentId, $template);
        }

        switch ($reportType) {
            case 'card_single':
                return redirect()->route('reports.card.single', [
                    'student' => $studentId,
                    'print' => $format === 'print' ? 1 : 0
                ]);

            case 'card_batch':
                return redirect()->route('reports.card.batch', [
                    'class' => $classId,
                    'print' => $format === 'print' ? 1 : 0
                ]);

            case 'analytics':
            case 'at_risk':
            case 'honor_roll':
                return redirect()->route('reports.analytics.class', [
                    'class' => $classId,
                    'tab' => $reportType
                ]);

            case 'broadsheet':
            default:
                return redirect()->route('reports.broadsheet', ['class_id' => $classId]);
        }
    }

    /**
     * Store a new report template in the report catalog (Global or School-Specific).
     */
    public function storeTemplate(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'category' => 'nullable|string|max:80',
            'description' => 'nullable|string|max:500',
            'base_type' => 'required|string|in:card_single,card_batch,analytics,broadsheet,at_risk,honor_roll,custom',
            'is_global' => 'nullable|boolean',
            'parameters' => 'nullable|array',
            'default_format' => 'required|string|in:view,print,csv,jasper',
            'zip_package' => 'nullable|file|mimes:zip|max:51200',
            'template_file' => 'nullable|file|max:20480',
            'subreport_files.*' => 'nullable|file|max:20480',
            'asset_files.*' => 'nullable|file|mimes:png,jpg,jpeg,gif,svg|max:10240',
        ]);

        $isGlobal = $request->boolean('is_global');

        $template = ReportTemplate::create([
            'school_id' => $isGlobal ? null : $activeSchool->school_id,
            'is_global' => $isGlobal,
            'title' => $validated['title'],
            'report_code' => Str::slug($validated['title'], '_') . '_' . substr(uniqid(), -4),
            'category' => $validated['category'] ?: ($isGlobal ? 'General Reports' : 'School Custom Reports'),
            'description' => $validated['description'] ?? null,
            'base_type' => $validated['base_type'],
            'parameters' => $validated['parameters'] ?? ['level', 'class', 'format'],
            'default_format' => $validated['default_format'],
            'is_system' => false,
            'display_order' => (int)ReportTemplate::max('display_order') + 1,
            'is_active' => true,
        ]);

        // Deploy any uploaded assets or zip bundle immediately
        if ($request->hasFile('zip_package') || $request->hasFile('template_file') || $request->hasFile('subreport_files') || $request->hasFile('asset_files')) {
            $this->deploymentService->deployPackage($template, $request->all());
        }

        $scopeMsg = $isGlobal ? "🌐 General Report (available to all schools)" : "🏫 School Customized Report (for {$activeSchool->school_name})";

        return redirect()->route('reports.generator', [
            'report_template_id' => $template->id,
            'class_id' => $request->input('class_id'),
        ])->with('success', "🎉 Report \"{$template->title}\" created as {$scopeMsg}.");
    }

    /**
     * Update an existing report template.
     */
    public function updateTemplate(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);
        $template = ReportTemplate::findOrFail($id);

        // Security check: non-global templates must belong to this school
        if (!$template->is_global && $template->school_id !== $activeSchool->school_id) {
            abort(403, 'Unauthorized access to this school report.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'category' => 'nullable|string|max:80',
            'description' => 'nullable|string|max:500',
            'base_type' => 'required|string|in:card_single,card_batch,analytics,broadsheet,at_risk,honor_roll,custom',
            'is_global' => 'nullable|boolean',
            'parameters' => 'nullable|array',
            'default_format' => 'required|string|in:view,print,csv,jasper',
        ]);

        $isGlobal = $request->boolean('is_global');

        $template->update([
            'title' => $validated['title'],
            'category' => $validated['category'] ?: ($isGlobal ? 'General Reports' : 'School Custom Reports'),
            'description' => $validated['description'] ?? null,
            'base_type' => $validated['base_type'],
            'is_global' => $isGlobal,
            'school_id' => $isGlobal ? null : ($template->school_id ?: $activeSchool->school_id),
            'parameters' => $validated['parameters'] ?? ['level', 'class', 'format'],
            'default_format' => $validated['default_format'],
        ]);

        return redirect()->route('reports.generator', [
            'report_template_id' => $template->id,
            'class_id' => $request->input('class_id'),
        ])->with('success', "✅ Report \"{$template->title}\" updated successfully.");
    }

    /**
     * Remove / delete a report template from the catalog.
     */
    public function destroyTemplate(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);
        $template = ReportTemplate::findOrFail($id);

        if (!$template->is_global && $template->school_id !== $activeSchool->school_id) {
            abort(403, 'Unauthorized access to this school report.');
        }

        $title = $template->title;
        $template->delete();

        return redirect()->route('reports.generator', [
            'class_id' => $request->input('class_id'),
        ])->with('success', "🗑️ Report \"{$title}\" removed from catalog.");
    }

    /**
     * Get deployment metadata (main file, subreports, images with preview URIs).
     */
    public function getDeployment(Request $request, $id)
    {
        $template = ReportTemplate::findOrFail($id);
        $metadata = $this->deploymentService->getDeployedAssetsMetadata($template);

        return response()->json([
            'success' => true,
            'template' => $template,
            'deployment' => $metadata,
        ]);
    }

    /**
     * Deploy report package (ZIP archive, main jrxml, subreports, image assets).
     */
    public function deployPackage(Request $request, $id)
    {
        $template = ReportTemplate::findOrFail($id);

        $request->validate([
            'zip_package' => 'nullable|file|mimes:zip|max:51200',
            'template_file' => 'nullable|file|max:20480',
            'subreport_files.*' => 'nullable|file|max:20480',
            'asset_files.*' => 'nullable|file|mimes:png,jpg,jpeg,gif,svg|max:10240',
        ]);

        $results = $this->deploymentService->deployPackage($template, $request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => '🚀 Report package deployed successfully with all subreports and media assets!',
                'results' => $results,
            ]);
        }

        return redirect()->back()->with('success', '🚀 Report package deployed successfully with all subreports and media assets!');
    }

    /**
     * Recompile all JRXML templates and subreports.
     */
    public function compileTemplate(Request $request, $id)
    {
        $template = ReportTemplate::findOrFail($id);
        $results = $this->deploymentService->compileAll($template);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $results['success'],
                'message' => $results['success'] ? '✅ All report templates and subreports compiled to .jasper successfully!' : '⚠️ Some templates failed compilation.',
                'results' => $results,
            ]);
        }

        return redirect()->back()->with('success', '✅ All templates and subreports compiled successfully!');
    }

    /**
     * Delete an individual asset or subreport file.
     */
    public function deleteAsset(Request $request, $id)
    {
        $template = ReportTemplate::findOrFail($id);
        $fileName = $request->input('filename');
        $category = $request->input('category', 'images');

        $deleted = $this->deploymentService->deleteAsset($template, $fileName, $category);

        return response()->json([
            'success' => $deleted,
            'message' => $deleted ? "🗑️ File \"{$fileName}\" deleted." : "File not found.",
        ]);
    }

    /**
     * Download the full deployed package as a ZIP archive.
     */
    public function downloadBundle(Request $request, $id)
    {
        $template = ReportTemplate::findOrFail($id);
        $zipPath = $this->deploymentService->createBundleZip($template);

        return response()->download($zipPath, basename($zipPath))->deleteFileAfterSend(true);
    }

    /**
     * Display Single Student Official Report Card.
     */
    public function singleCard(Request $request, $studentId)
    {
        $student = Student::with(['schoolClass.gradeLevel.division', 'gradeRecords'])->findOrFail($studentId);
        $card = $this->analyticsService->buildStudentReportCardData($student);
        $isPrintMode = $request->boolean('print');

        return view('reports.card_single', compact('card', 'isPrintMode'));
    }

    /**
     * Display Multi-Page Batch Report Cards for an entire class.
     */
    public function batchCards(Request $request, $classId)
    {
        set_time_limit(180);
        $class = SchoolClass::with(['gradeLevel.division'])->findOrFail($classId);
        $cards = $this->analyticsService->buildClassReportCardsData($class);
        $isPrintMode = $request->boolean('print');

        return view('reports.card_batch', compact('class', 'cards', 'isPrintMode'));
    }

    /**
     * Display Class Performance & Analytics Dashboard.
     */
    public function classAnalytics(Request $request, $classId)
    {
        set_time_limit(180);
        $activeSchool = $this->resolveActiveSchool($request);
        $class = SchoolClass::with(['gradeLevel', 'students'])->findOrFail($classId);
        $allClasses = SchoolClass::where('school_id', $activeSchool->school_id)->orderBy('class_name')->get();
        $analytics = $this->analyticsService->buildClassAnalytics($class);
        $activeTab = $request->get('tab', 'overview');

        return view('reports.analytics', compact('activeSchool', 'class', 'allClasses', 'analytics', 'activeTab'));
    }

    /**
     * Stream CSV export of Master Broadsheet.
     */
    public function exportCsv(Request $request, $classId)
    {
        set_time_limit(180);
        $class = SchoolClass::with(['gradeLevel', 'students'])->findOrFail($classId);
        $csv = $this->analyticsService->generateBroadsheetCsv($class);

        $filename = "Broadsheet_{$class->class_name}_" . date('Ymd_His') . ".csv";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Dispatch JasperReports PDF compilation.
     */
    public function exportJasper(Request $request, $studentId, ?ReportTemplate $template = null)
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
        $cardData = $this->analyticsService->buildStudentReportCardData($student);

        // Resolve template file path
        if (!$template) {
            $template = ReportTemplate::where(function ($q) use ($school) {
                    $q->where('is_global', true)->orWhere('school_id', $school->school_id);
                })
                ->where('base_type', 'card_single')
                ->first();
        }

        $templateFilePath = 'official_report_card_a4.jrxml';
        if ($template && $template->template_file) {
            $customPath = $template->getDeploymentDirectory() . DIRECTORY_SEPARATOR . $template->template_file;
            if (file_exists($customPath)) {
                $templateFilePath = $customPath;
            }
        }

        $parameters = [
            'P_SCHOOL_NAME' => $school->school_name,
            'P_SCHOOL_CODE' => $school->school_code,
            'P_ACADEMIC_YEAR' => '2026-2027',
            'P_TERM_NAME' => 'Annual Term',
            'P_SCALE_NAME' => $school->active_scale_model ?? 'Standard Scale'
        ];

        $subjectRows = [];
        foreach ($cardData['subjectRows'] as $row) {
            $subjectRows[] = [
                'studentName' => $cardData['student']['name'],
                'admissionNumber' => $cardData['student']['admissionNumber'],
                'className' => $cardData['student']['className'],
                'gpaOrAverage' => "{$cardData['metrics']['overallPercentage']}%",
                'classRank' => $cardData['metrics']['rankInClass'],
                'honorStatus' => $cardData['metrics']['honorStatus'],
                'daysEnrolled' => $cardData['attendance']['enrolledDays'],
                'daysPresent' => $cardData['attendance']['presentDays'],
                'daysAbsent' => $cardData['attendance']['absentDays'],
                'attendanceRate' => "{$cardData['attendance']['attendanceRate']}%",
                'subjectCode' => $row['subjectCode'],
                'subjectName' => $row['subjectName'],
                'rawScore' => $row['rawScore'] ?? 0.0,
                'maxScore' => $row['maxScore'] ?? 100.0,
                'letterGrade' => $row['letterGrade'],
                'teacherRemarks' => $row['teacherRemarks']
            ];
        }

        $outputPath = storage_path("app/schools/{$school->school_id}/exports/{$student->admission_number}.pdf");

        try {
            $this->jasperService->generateReport($templateFilePath, $parameters, $subjectRows, $outputPath);
            return response()->download($outputPath);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'JasperReports runner notice: ' . $e->getMessage());
        }
    }
}
