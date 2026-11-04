@extends('layouts.app')

@section('title', "Student Profile — {$student->full_name} ({$student->admission_number})")

@section('content')
<div class="container-fluid px-0">

    <!-- Top Breadcrumb & Navigation -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('config.students.index') }}" class="text-decoration-none">Students Directory</a></li>
                @if($student->schoolClass)
                    <li class="breadcrumb-item">
                        <a href="{{ route('config.classes.profile', $student->schoolClass->id) }}" class="text-decoration-none">
                            {{ $student->schoolClass->class_name }}
                        </a>
                    </li>
                @endif
                <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">{{ $student->full_name }}</li>
            </ol>
        </nav>

        <a href="{{ route('config.students.index') }}" class="btn btn-sm btn-outline-secondary fw-semibold shadow-2xs">
            ← Back to Students Directory
        </a>
    </div>

    <!-- Hero Profile Header Card -->
    <div class="card border rounded-3 p-4 mb-4 bg-white shadow-xs">
        <div class="row align-items-center g-4">
            <!-- Left: Avatar & Identity -->
            <div class="col-lg-8 col-md-7">
                <div class="d-flex align-items-center gap-3 flex-wrap flex-sm-nowrap">
                    <!-- Avatar circle -->
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-xs flex-shrink-0"
                         style="width: 76px; height: 76px; font-size: 1.8rem; background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                        {{ strtoupper(substr($student->full_name, 0, 2)) }}
                    </div>

                    <!-- Details -->
                    <div class="min-w-0">
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <span class="badge {{ $student->status === 'ACTIVE' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary' }} px-2.5 py-1 fw-bold">
                                ● {{ $student->status }}
                            </span>
                            <span class="badge bg-light text-primary border font-monospace px-2.5 py-1 fw-bold">
                                ID: {{ $student->admission_number }}
                            </span>
                            <span class="badge bg-light text-secondary border px-2 py-1">
                                {{ $student->gender ?? 'Other' }}
                            </span>
                            @if($classRank)
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1 fw-bold">
                                    🏆 Rank #{{ $classRank }} of {{ $classTotalStudents }}
                                </span>
                            @endif
                        </div>

                        <h3 class="fw-bold text-dark mb-1 text-truncate">
                            {{ $student->full_name }}
                        </h3>

                        <div class="d-flex align-items-center gap-3 text-muted small flex-wrap">
                            @if($student->schoolClass)
                                <span>
                                    🏫 Class: 
                                    <a href="{{ route('config.classes.profile', $student->schoolClass->id) }}" class="fw-bold text-primary text-decoration-none hover-primary">
                                        {{ $student->schoolClass->class_name }} ↗
                                    </a>
                                </span>
                                @if($student->schoolClass->gradeLevel)
                                    <span>• 🏷️ Level: <strong>{{ $student->schoolClass->gradeLevel->grade_name }}</strong></span>
                                @endif
                                @if($student->schoolClass->gradeLevel?->division)
                                    <span>• 🏛️ Stage: <strong>{{ $student->schoolClass->gradeLevel->division->division_name }}</strong></span>
                                @endif
                            @else
                                <span class="text-warning fst-italic">⚠️ Unassigned to any class section</span>
                            @endif
                            <span>• 🏛️ School: <strong>{{ $activeSchool->school_name }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Action Buttons -->
            <div class="col-lg-4 col-md-5 text-md-end text-start">
                <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                    <a href="{{ route('reports.preview', $student->id) }}" class="btn btn-sm btn-primary fw-bold shadow-2xs">
                        👁️ Preview Report Card
                    </a>
                    <a href="{{ route('reports.export.jasper', $student->id) }}" class="btn btn-sm btn-outline-primary fw-semibold shadow-2xs" title="Download Official Jasper PDF">
                        📄 PDF Report
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold shadow-2xs" 
                            data-bs-toggle="modal" data-bs-target="#modalEditStudentProfile">
                        ✏️ Edit Info
                    </button>
                    @if($student->class_id)
                        <a href="{{ route('attendance.index', ['class_id' => $student->class_id]) }}" class="btn btn-sm btn-outline-success fw-semibold shadow-2xs" title="Take Roll Call for Class">
                            📋 Attendance
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Summary Highlights (4 Cards) -->
    <div class="row g-3 mb-4">
        <!-- 1. Cumulative GPA & Overall Average -->
        <div class="col-xl-3 col-md-6">
            <div class="card border rounded-3 p-3 bg-white shadow-xs h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Cumulative Performance</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small">
                        Grade Point
                    </span>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-1">
                    <h3 class="fw-bold mb-0 text-dark">
                        {{ number_format($reportData['summary']['cumulativeGpa'] ?? 0.0, 2) }}
                    </h3>
                    <span class="text-muted small">GPA</span>
                    <span class="text-muted ms-auto fw-bold fs-6">
                        {{ $reportData['summary']['overallPercentage'] ?? 0 }}%
                    </span>
                </div>
                <div class="progress mb-2" style="height: 6px;">
                    <div class="progress-bar bg-primary" role="progressbar" 
                         style="width: {{ min(100, max(5, $reportData['summary']['overallPercentage'] ?? 0)) }}%;">
                    </div>
                </div>
                <div class="small fw-semibold text-truncate {{ ($reportData['summary']['overallPercentage'] ?? 0) >= 60 ? 'text-success' : 'text-danger' }}">
                    ★ {{ $reportData['summary']['honorStatus'] ?? 'Good Standing' }}
                </div>
            </div>
        </div>

        <!-- 2. Attendance Standing -->
        <div class="col-xl-3 col-md-6">
            <div class="card border rounded-3 p-3 bg-white shadow-xs h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Attendance Rate</span>
                    <span class="badge {{ $attendanceRate >= 85 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' }} px-2 py-1 small">
                        {{ $reportData['attendance']['attendanceStatus'] ?? 'Regular' }}
                    </span>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-1">
                    <h3 class="fw-bold mb-0 {{ $attendanceRate >= 85 ? 'text-success' : 'text-danger' }}">
                        {{ $attendanceRate }}%
                    </h3>
                    <span class="text-muted small">presence</span>
                </div>
                <div class="progress mb-2" style="height: 6px;">
                    <div class="progress-bar {{ $attendanceRate >= 85 ? 'bg-success' : 'bg-warning' }}" role="progressbar" 
                         style="width: {{ min(100, max(5, $attendanceRate)) }}%;">
                    </div>
                </div>
                <div class="small text-muted d-flex justify-content-between">
                    <span><strong>{{ $presentCount }}</strong> Present</span>
                    <span><strong>{{ $tardyCount }}</strong> Tardies</span>
                    <span><strong>{{ $unexcusedCount }}</strong> Unexcused</span>
                </div>
            </div>
        </div>

        <!-- 3. Course Enrollment & Passed Subjects -->
        <div class="col-xl-3 col-md-6">
            <div class="card border rounded-3 p-3 bg-white shadow-xs h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Course Enrolments</span>
                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 small">
                        Pillar 3
                    </span>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-1">
                    <h3 class="fw-bold mb-0 text-dark">
                        {{ $reportData['summary']['passedSubjectsCount'] ?? 0 }} / {{ $reportData['summary']['totalSubjectsCount'] ?? 0 }}
                    </h3>
                    <span class="text-muted small">passed</span>
                </div>
                <div class="progress mb-2" style="height: 6px;">
                    @php
                        $totalSubj = max(1, $reportData['summary']['totalSubjectsCount'] ?? 1);
                        $passedSubj = $reportData['summary']['passedSubjectsCount'] ?? 0;
                        $subjPct = round(($passedSubj / $totalSubj) * 100);
                    @endphp
                    <div class="progress-bar bg-info" role="progressbar" style="width: {{ $subjPct }}%;"></div>
                </div>
                <div class="small text-muted d-flex justify-content-between">
                    <span>Credits: <strong>{{ $reportData['summary']['totalCredits'] ?? count($reportData['subjects'] ?? []) }}</strong></span>
                    <span class="fw-semibold text-dark">
                        {{ ($reportData['summary']['passedSubjectsCount'] ?? 0) === ($reportData['summary']['totalSubjectsCount'] ?? 0) && ($reportData['summary']['totalSubjectsCount'] ?? 0) > 0 ? '✓ All Passed' : 'In Progress' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- 4. Class Standing & Cohort -->
        <div class="col-xl-3 col-md-6">
            <div class="card border rounded-3 p-3 bg-white shadow-xs h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Class Cohort Standing</span>
                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 small">
                        Class Rank
                    </span>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-1">
                    <h3 class="fw-bold mb-0 text-dark">
                        @if($classRank)
                            #{{ $classRank }}
                        @else
                            --
                        @endif
                    </h3>
                    <span class="text-muted small">
                        @if($classTotalStudents > 0)
                            of {{ $classTotalStudents }} students
                        @else
                            in class
                        @endif
                    </span>
                </div>
                <div class="progress mb-2" style="height: 6px;">
                    @php
                        $rankPct = ($classTotalStudents > 0 && $classRank) 
                            ? max(10, round((($classTotalStudents - $classRank + 1) / $classTotalStudents) * 100))
                            : 50;
                    @endphp
                    <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $rankPct }}%;"></div>
                </div>
                <div class="small text-muted text-truncate">
                    Class: <strong>{{ $student->schoolClass->class_name ?? 'Unassigned' }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabbed Detailed Navigation -->
    <div class="card border rounded-3 bg-white shadow-xs mb-4">
        <div class="card-header bg-light border-bottom p-2">
            <ul class="nav nav-pills card-header-pills gap-1" id="studentProfileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold small px-3 py-2" id="tab-academics-btn" data-bs-toggle="pill" data-bs-target="#tab-academics" type="button" role="tab">
                        📚 Academic Gradebook &amp; Scores
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold small px-3 py-2" id="tab-attendance-btn" data-bs-toggle="pill" data-bs-target="#tab-attendance" type="button" role="tab">
                        🗓️ Attendance Register &amp; Log ({{ $totalAttendanceCount }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold small px-3 py-2" id="tab-reports-btn" data-bs-toggle="pill" data-bs-target="#tab-reports" type="button" role="tab">
                        📊 Report Card &amp; Transcripts
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold small px-3 py-2" id="tab-details-btn" data-bs-toggle="pill" data-bs-target="#tab-details" type="button" role="tab">
                        👤 Personal &amp; Enrollment Record
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content" id="studentProfileTabsContent">

                <!-- TAB 1: ACADEMIC GRADEBOOK & SUBJECT PERFORMANCE -->
                <div class="tab-pane fade show active" id="tab-academics" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Enrolled Subjects &amp; Continuous Assessment Marks</h5>
                            <p class="text-muted small mb-0">Grade records, assessment category breakdowns, letter grades, and GPA calculations.</p>
                        </div>
                        @if($student->schoolClass)
                            <a href="{{ route('config.classes.profile', $student->schoolClass->id) }}#gradebook" class="btn btn-sm btn-outline-primary fw-semibold">
                                📋 Open Class Gradebook ↗
                            </a>
                        @endif
                    </div>

                    @if(!empty($reportData['subjects']))
                        <div class="table-responsive border rounded-3 mb-4">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 text-start">Subject / Code</th>
                                        <th>Teacher</th>
                                        <th>Sub-Score Breakdown</th>
                                        <th class="text-center">Score / Max</th>
                                        <th class="text-center">Percentage</th>
                                        <th class="text-center">Grade</th>
                                        <th class="text-center">GPA</th>
                                        <th class="text-center">Status</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData['subjects'] as $subj)
                                        @php
                                            $pct = ($subj['maxScore'] > 0 && $subj['rawScore'] !== null) 
                                                ? round(($subj['rawScore'] / $subj['maxScore']) * 100, 1) 
                                                : null;
                                        @endphp
                                        <tr>
                                            <td class="ps-3 text-start">
                                                <div class="fw-bold text-dark fs-6">{{ $subj['subjectName'] }}</div>
                                                <span class="badge bg-light text-muted border font-monospace">{{ $subj['subjectCode'] }}</span>
                                                <span class="text-muted" style="font-size: 0.72rem;">({{ $subj['credits'] }} credits)</span>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $subj['teacherName'] ?? 'Faculty' }}</span>
                                            </td>
                                            <td style="min-width: 200px;">
                                                @if(!empty($subj['subScores']))
                                                    <div class="d-flex flex-column gap-1">
                                                        @foreach($subj['subScores'] as $sub)
                                                            <div class="d-flex justify-content-between align-items-center" style="font-size: 0.75rem;">
                                                                <span class="text-muted text-truncate" style="max-width: 110px;">{{ $sub['categoryName'] }}:</span>
                                                                <span class="fw-semibold font-monospace">{{ $sub['rawScore'] }}/{{ $sub['maxScore'] }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-muted fst-italic" style="font-size: 0.75rem;">Total raw mark</span>
                                                @endif
                                            </td>
                                            <td class="text-center fw-bold text-dark">
                                                @if($subj['rawScore'] !== null)
                                                    {{ $subj['rawScore'] }} <span class="text-muted fw-normal">/ {{ $subj['maxScore'] }}</span>
                                                @else
                                                    <span class="text-muted">--</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($pct !== null)
                                                    <span class="badge {{ $pct >= 60 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} border fw-bold">
                                                        {{ $pct }}%
                                                    </span>
                                                @else
                                                    <span class="text-muted">--</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary text-white px-2 py-1 fw-bold">
                                                    {{ $subj['letterGrade'] }}
                                                </span>
                                            </td>
                                            <td class="text-center font-monospace fw-bold text-dark">
                                                {{ $subj['gpa'] }}
                                            </td>
                                            <td class="text-center">
                                                @if($subj['isPassed'])
                                                    <span class="badge bg-success text-white px-2 py-1">Passed</span>
                                                @else
                                                    <span class="badge bg-danger text-white px-2 py-1">Needs Improvement</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="text-muted fst-italic" style="font-size: 0.78rem;">
                                                    "{{ $subj['teacherRemarks'] }}"
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="3" class="ps-3 text-start">Cumulative Semester Totals:</td>
                                        <td class="text-center">
                                            {{ $reportData['summary']['totalRawScore'] ?? 0 }} / {{ $reportData['summary']['totalMaxScore'] ?? 0 }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary text-white">
                                                {{ $reportData['summary']['overallPercentage'] ?? 0 }}%
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-dark text-white">
                                                {{ $reportData['summary']['overallGrade'] ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="text-center font-monospace">
                                            {{ number_format($reportData['summary']['cumulativeGpa'] ?? 0.0, 2) }}
                                        </td>
                                        <td colspan="2" class="text-end pe-3">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                {{ $reportData['summary']['honorStatus'] ?? 'Good Standing' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info py-3 px-4">
                            ℹ️ No graded subject courses are enrolled for this student yet. Enroll the student in subjects or assign courses to <strong>{{ $student->schoolClass->class_name ?? 'their class' }}</strong> to start tracking grade records.
                        </div>
                    @endif
                </div>

                <!-- TAB 2: ATTENDANCE REGISTER & HISTORY -->
                <div class="tab-pane fade" id="tab-attendance" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Attendance Register &amp; Daily Roll Call History</h5>
                            <p class="text-muted small mb-0">Full day homeroom presence, lesson slot attendance, tardies, and absences.</p>
                        </div>
                        @if($student->class_id)
                            <a href="{{ route('attendance.index', ['class_id' => $student->class_id]) }}" class="btn btn-sm btn-primary fw-bold shadow-2xs">
                                📋 Launch Class Roll Call Register ↗
                            </a>
                        @endif
                    </div>

                    <!-- Attendance Metrics Summary Grid -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-2 col-4">
                            <div class="p-3 bg-light rounded-3 border text-center">
                                <div class="text-muted small fw-bold">TOTAL DAYS</div>
                                <div class="fs-4 fw-bold text-dark">{{ $totalAttendanceCount }}</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-4">
                            <div class="p-3 bg-success-subtle rounded-3 border border-success-subtle text-center">
                                <div class="text-success small fw-bold">PRESENT</div>
                                <div class="fs-4 fw-bold text-success">{{ $presentCount }}</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-4">
                            <div class="p-3 bg-warning-subtle rounded-3 border border-warning-subtle text-center">
                                <div class="text-warning small fw-bold">TARDY</div>
                                <div class="fs-4 fw-bold text-warning">{{ $tardyCount }}</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-4">
                            <div class="p-3 bg-info-subtle rounded-3 border border-info-subtle text-center">
                                <div class="text-info small fw-bold">EXCUSED</div>
                                <div class="fs-4 fw-bold text-info">{{ $excusedCount }}</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-4">
                            <div class="p-3 bg-danger-subtle rounded-3 border border-danger-subtle text-center">
                                <div class="text-danger small fw-bold">UNEXCUSED</div>
                                <div class="fs-4 fw-bold text-danger">{{ $unexcusedCount }}</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-4">
                            <div class="p-3 bg-primary-subtle rounded-3 border border-primary-subtle text-center">
                                <div class="text-primary small fw-bold">RATE %</div>
                                <div class="fs-4 fw-bold text-primary">{{ $attendanceRate }}%</div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Attendance Records Table -->
                    @if($attendanceRecords->isNotEmpty())
                        <div class="table-responsive border rounded-3">
                            <table class="table table-hover align-middle mb-0 small text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 text-start">Date &amp; Day</th>
                                        <th>Attendance Mode</th>
                                        <th>Subject / Period Slot</th>
                                        <th>Status</th>
                                        <th class="text-start">Teacher Remarks / Excuse</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($attendanceRecords as $att)
                                        <tr>
                                            <td class="ps-3 text-start fw-bold text-dark">
                                                📅 {{ \Carbon\Carbon::parse($att->attendance_date)->format('D, M j, Y') }}
                                            </td>
                                            <td>
                                                @if($att->session_type === 'SLOT')
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">
                                                        ⏱️ Subject Slot
                                                    </span>
                                                @else
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                                        ☀️ Full Day Roll Call
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($att->slot_name)
                                                    <span class="badge bg-light text-dark border px-2 py-1">
                                                        {{ $att->slot_name }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">Homeroom Session</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($att->status === 'PRESENT')
                                                    <span class="badge bg-success text-white px-2.5 py-1 fw-bold">✓ PRESENT</span>
                                                @elseif($att->status === 'TARDY')
                                                    <span class="badge bg-warning text-dark px-2.5 py-1 fw-bold">⏰ TARDY</span>
                                                @elseif($att->status === 'EXCUSED')
                                                    <span class="badge bg-info text-dark px-2.5 py-1 fw-bold">📋 EXCUSED</span>
                                                @else
                                                    <span class="badge bg-danger text-white px-2.5 py-1 fw-bold">✕ UNEXCUSED</span>
                                                @endif
                                            </td>
                                            <td class="text-start text-muted">
                                                {{ $att->remarks ?: '--' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-light border py-3 px-4 text-center">
                            No individual attendance entries logged for this student yet.
                            @if($student->class_id)
                                <div class="mt-2">
                                    <a href="{{ route('attendance.index', ['class_id' => $student->class_id]) }}" class="btn btn-sm btn-primary">
                                        Open Attendance Register for {{ $student->schoolClass->class_name }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- TAB 3: REPORT CARD & TRANSCRIPTS -->
                <div class="tab-pane fade" id="tab-reports" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Official Term Report Card &amp; Academic Transcripts</h5>
                            <p class="text-muted small mb-0">High-resolution institutional report card generation with Jasper PDF engine integration.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('reports.preview', $student->id) }}" target="_blank" class="btn btn-primary btn-sm fw-bold shadow-2xs">
                                👁️ Full Interactive Preview ↗
                            </a>
                            <a href="{{ route('reports.export.jasper', $student->id) }}" class="btn btn-outline-danger btn-sm fw-bold shadow-2xs">
                                📄 Export Jasper PDF
                            </a>
                        </div>
                    </div>

                    <!-- Report Summary Card -->
                    <div class="card p-4 border rounded-3 bg-light mb-4">
                        <div class="row align-items-center g-3">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-dark text-white px-2 py-1 font-monospace">{{ $activeSchool->school_code }}</span>
                                    <h5 class="fw-bold text-dark mb-0">{{ $activeSchool->school_name }} Official Report Card</h5>
                                </div>
                                <p class="text-muted small mb-0">
                                    Includes comprehensive subject continuous assessments, term exam marks, class rank, attendance verification, and principal signature blocks.
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end text-start">
                                <div class="d-inline-block text-start p-2 bg-white rounded border">
                                    <div class="small text-muted">Academic Term: <strong>Annual 2026-2027</strong></div>
                                    <div class="small text-muted">Grading Scale: <strong>{{ $activeSchool->active_scale_model ?? 'Standard' }}</strong></div>
                                    <div class="small text-muted">Status: <strong class="text-success">Validated &amp; Ready</strong></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grading Scale Legend Reference -->
                    <h6 class="fw-bold text-dark mb-2">📊 Institutional Grading Scale Reference</h6>
                    <div class="table-responsive border rounded-3">
                        <table class="table table-sm table-striped mb-0 text-center small">
                            <thead class="table-light">
                                <tr>
                                    <th>Letter Grade</th>
                                    <th>Minimum Percentage</th>
                                    <th>Grade Point (GPA)</th>
                                    <th>Description / Performance Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportData['school']['cutoffs'] ?? [] as $cutoff)
                                    <tr>
                                        <td><span class="badge bg-primary px-2 py-1 fw-bold">{{ $cutoff['grade'] }}</span></td>
                                        <td class="fw-semibold">{{ $cutoff['min'] ?? $cutoff['min_score'] ?? 0 }}%</td>
                                        <td class="font-monospace fw-bold">{{ number_format($cutoff['gpa'] ?? 0, 2) }}</td>
                                        <td class="text-muted">{{ $cutoff['label'] ?? $cutoff['description'] ?? 'Standard' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 4: PERSONAL & ENROLLMENT RECORD -->
                <div class="tab-pane fade" id="tab-details" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Student Institutional Master Record</h5>
                            <p class="text-muted small mb-0">Official enrollment metadata, institutional bindings, and demographic attributes.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalEditStudentProfile">
                            ✏️ Edit Record Details
                        </button>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card border rounded-3 p-3 bg-white h-100">
                                <h6 class="fw-bold text-primary mb-3">👤 Personal Demographic Details</h6>
                                <table class="table table-sm table-borderless mb-0 small">
                                    <tr>
                                        <td class="text-muted" style="width: 40%;">Full Legal Name:</td>
                                        <td class="fw-bold text-dark">{{ $student->full_name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Admission / Student ID:</td>
                                        <td class="fw-bold font-monospace text-primary">{{ $student->admission_number }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Gender:</td>
                                        <td class="fw-semibold">{{ $student->gender ?? 'Other' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Account Status:</td>
                                        <td>
                                            <span class="badge {{ $student->status === 'ACTIVE' ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $student->status }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Registration Date:</td>
                                        <td>{{ $student->created_at ? $student->created_at->format('M j, Y') : 'N/A' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border rounded-3 p-3 bg-white h-100">
                                <h6 class="fw-bold text-primary mb-3">🏫 Institutional Placement</h6>
                                <table class="table table-sm table-borderless mb-0 small">
                                    <tr>
                                        <td class="text-muted" style="width: 40%;">School Tenant:</td>
                                        <td class="fw-bold text-dark">{{ $activeSchool->school_name }} ({{ $activeSchool->school_code }})</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Assigned Class / Section:</td>
                                        <td>
                                            @if($student->schoolClass)
                                                <a href="{{ route('config.classes.profile', $student->schoolClass->id) }}" class="fw-bold text-primary text-decoration-none hover-primary">
                                                    {{ $student->schoolClass->class_name }} ↗
                                                </a>
                                            @else
                                                <span class="text-warning fst-italic">Unassigned</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Educational Stage / Level:</td>
                                        <td>{{ $student->schoolClass->gradeLevel->grade_name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Division:</td>
                                        <td>{{ $student->schoolClass->gradeLevel->division->division_name ?? 'Standard' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Report Card Template:</td>
                                        <td><span class="badge bg-light text-secondary border">Universal Report Engine</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Safety Actions -->
                    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Need to re-assign or unenroll this student from the active roster?</span>
                        <form method="POST" action="{{ route('config.students.delete', $student->id) }}" onsubmit="return confirm('Are you sure you want to delete student {{ addslashes($student->full_name) }}? All linked grade and attendance records will be removed.');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger fw-semibold">
                                🗑️ Delete Student from School
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<!-- Modal: Edit Student Profile -->
<div class="modal fade" id="modalEditStudentProfile" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('config.students.update', $student->id) }}">
                @csrf
                <div class="modal-header bg-primary text-white p-3">
                    <h5 class="modal-title fw-bold">✏️ Edit Student Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Admission / Student ID <span class="text-danger">*</span></label>
                        <input type="text" name="admission_number" class="form-control" value="{{ $student->admission_number }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Student Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" value="{{ $student->full_name }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Assign to Class / Section</label>
                        <select name="class_id" class="form-select">
                            <option value="">-- Unassigned (No Class) --</option>
                            @foreach($allLevels as $lvl)
                                <optgroup label="Level: {{ $lvl->grade_name }}">
                                    @foreach($lvl->classes as $c)
                                        <option value="{{ $c->id }}" {{ $student->class_id == $c->id ? 'selected' : '' }}>
                                            {{ $c->class_name }} ({{ $c->students_count }} students)
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="Boy" {{ $student->gender === 'Boy' ? 'selected' : '' }}>Boy / Male</option>
                                <option value="Girl" {{ $student->gender === 'Girl' ? 'selected' : '' }}>Girl / Female</option>
                                <option value="Other" {{ !in_array($student->gender, ['Boy', 'Girl']) ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Enrollment Status</label>
                            <select name="status" class="form-select">
                                <option value="ACTIVE" {{ $student->status === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                                <option value="INACTIVE" {{ $student->status === 'INACTIVE' ? 'selected' : '' }}>Inactive / Transferred</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">💾 Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
