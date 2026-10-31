<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolGradeLevel;
use App\Models\Student;
use App\Services\StudentEnrollmentService;

class StudentEnrollmentController extends Controller
{
    protected StudentEnrollmentService $enrollmentService;

    public function __construct(StudentEnrollmentService $enrollmentService)
    {
        $this->enrollmentService = $enrollmentService;
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
     * Display Student Directory & Enrollment Studio.
     */
    public function index(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $search = $request->get('search');
        $gradeId = $request->get('grade_id');
        $classId = $request->get('class_id');
        $status = $request->get('status', 'ALL');

        // Query students
        $query = Student::where('school_id', $activeSchool->school_id)
            ->with(['schoolClass.gradeLevel.division', 'attendanceRecords', 'gradeRecords']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('admission_number', 'like', "%{$search}%");
            });
        }

        if ($classId === 'unassigned' || $gradeId === 'unassigned') {
            $query->whereNull('class_id');
        } elseif (!empty($classId)) {
            $query->where('class_id', $classId);
            if (!empty($gradeId)) {
                $query->whereHas('schoolClass', function ($q) use ($gradeId) {
                    $q->where('grade_id', $gradeId);
                });
            }
        } elseif (!empty($gradeId)) {
            $query->whereHas('schoolClass', function ($q) use ($gradeId) {
                $q->where('grade_id', $gradeId);
            });
        }

        if ($status !== 'ALL' && !empty($status)) {
            $query->where('status', $status);
        }

        $students = $query->orderBy('admission_number')->paginate(50)->withQueryString();

        // Reference lists
        $allLevels = SchoolGradeLevel::where('school_id', $activeSchool->school_id)
            ->orderBy('display_order')
            ->get();

        $allClasses = SchoolClass::where('school_id', $activeSchool->school_id)
            ->with(['gradeLevel', 'students'])
            ->orderBy('class_name')
            ->get();

        // Global metrics
        $totalStudents = Student::where('school_id', $activeSchool->school_id)->count();
        $activeStudents = Student::where('school_id', $activeSchool->school_id)->where('status', 'ACTIVE')->count();
        $unassignedStudents = Student::where('school_id', $activeSchool->school_id)->whereNull('class_id')->count();
        $totalClasses = $allClasses->count();

        $classesData = $allClasses->map(function ($c) {
            return [
                'id' => $c->id,
                'grade_id' => $c->grade_id,
                'class_name' => $c->class_name,
                'code' => $c->gradeLevel->grade_code ?? 'CLS',
                'level_name' => $c->gradeLevel->grade_name ?? 'Level',
                'student_count' => $c->students->count(),
                'max_capacity' => $c->max_capacity,
            ];
        });

        return view('configuration.students', compact(
            'activeSchool',
            'students',
            'allLevels',
            'allClasses',
            'classesData',
            'totalStudents',
            'activeStudents',
            'unassignedStudents',
            'totalClasses',
            'search',
            'gradeId',
            'classId',
            'status'
        ));
    }

    /**
     * Enroll an individual student.
     */
    public function store(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $validated = $request->validate([
            'admission_number' => 'required|string|max:50',
            'full_name' => 'required|string|max:150',
            'class_id' => 'nullable|exists:school_classes,id',
            'gender' => 'nullable|string|max:10',
            'status' => 'nullable|string|max:20'
        ]);

        // Check uniqueness in school
        $existing = Student::where('school_id', $activeSchool->school_id)
            ->where('admission_number', trim($validated['admission_number']))
            ->first();

        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Student with Admission #{$validated['admission_number']} already exists in this school.");
        }

        // Verify class belongs to active school if provided
        $class = null;
        if (!empty($validated['class_id'])) {
            $class = SchoolClass::where('school_id', $activeSchool->school_id)->findOrFail($validated['class_id']);
        }

        $student = Student::create([
            'school_id' => $activeSchool->school_id,
            'class_id' => $class ? $class->id : null,
            'admission_number' => trim($validated['admission_number']),
            'full_name' => trim($validated['full_name']),
            'gender' => $validated['gender'] ?? 'Other',
            'status' => $validated['status'] ?? 'ACTIVE'
        ]);

        $destMsg = $class ? "in {$class->class_name}" : "as Unassigned (No Class)";
        $routeParams = $class ? ['class_id' => $class->id] : [];

        return redirect()->route('config.students.index', $routeParams)
            ->with('success', "✓ Student '{$student->full_name}' ({$student->admission_number}) successfully enrolled {$destMsg}.");
    }

    /**
     * Update student details.
     */
    public function update(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $student = Student::where('school_id', $activeSchool->school_id)->findOrFail($id);

        $validated = $request->validate([
            'admission_number' => 'required|string|max:50',
            'full_name' => 'required|string|max:150',
            'class_id' => 'nullable|exists:school_classes,id',
            'gender' => 'nullable|string|max:10',
            'status' => 'nullable|string|max:20'
        ]);

        // Check uniqueness
        $existing = Student::where('school_id', $activeSchool->school_id)
            ->where('admission_number', trim($validated['admission_number']))
            ->where('id', '!=', $student->id)
            ->first();

        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Another student with Admission #{$validated['admission_number']} already exists.");
        }

        $classId = !empty($validated['class_id']) ? $validated['class_id'] : null;
        if ($classId) {
            SchoolClass::where('school_id', $activeSchool->school_id)->findOrFail($classId);
        }

        $student->update([
            'admission_number' => trim($validated['admission_number']),
            'full_name' => trim($validated['full_name']),
            'class_id' => $classId,
            'gender' => $validated['gender'] ?? 'Other',
            'status' => $validated['status'] ?? 'ACTIVE'
        ]);

        return redirect()->back()
            ->with('success', "✓ Student '{$student->full_name}' updated successfully.");
    }

    /**
     * Delete student record.
     */
    public function destroy(Request $request, $id)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $student = Student::where('school_id', $activeSchool->school_id)->findOrFail($id);
        $name = $student->full_name;
        $student->delete();

        return redirect()->back()
            ->with('success', "✓ Student '{$name}' was removed from the roster.");
    }

    /**
     * Download Excel template for bulk student enrollment.
     */
    public function downloadTemplate(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $defaultClass = null;
        if ($request->has('class_id')) {
            $defaultClass = SchoolClass::where('school_id', $activeSchool->school_id)->find($request->get('class_id'));
        }

        $filePath = $this->enrollmentService->generateTemplate($activeSchool, $defaultClass);
        $fileName = 'student_enrollment_template_' . strtolower($activeSchool->school_code) . '.xlsx';

        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Upload spreadsheet with students.
     */
    public function uploadSheet(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);

        $request->validate([
            'student_sheet' => 'required|file|max:10240', // 10MB
            'class_id' => 'nullable|exists:school_classes,id'
        ]);

        $defaultClass = null;
        if ($request->filled('class_id')) {
            $defaultClass = SchoolClass::where('school_id', $activeSchool->school_id)->find($request->get('class_id'));
        }

        $file = $request->file('student_sheet');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            return redirect()->back()->with('error', 'Please upload a valid Excel spreadsheet (.xlsx, .xls) or CSV file.');
        }

        $result = $this->enrollmentService->importStudentsFromSpreadsheet(
            $file->getRealPath(),
            $activeSchool,
            $defaultClass
        );

        $msg = "✓ Bulk Enrollment Processed: {$result['created']} new student(s) enrolled, {$result['updated']} existing record(s) updated.";
        if ($result['skipped'] > 0) {
            $msg .= " ({$result['skipped']} row(s) skipped due to missing required fields).";
        }

        if (!empty($result['errors'])) {
            return redirect()->back()
                ->with('success', $msg)
                ->with('import_warnings', array_slice($result['errors'], 0, 8));
        }

        return redirect()->back()->with('success', $msg);
    }
}
