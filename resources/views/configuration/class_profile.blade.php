@extends('layouts.app')

@section('title', $class->class_name . ' (' . ($class->gradeLevel->grade_name ?? 'Level') . ') - Class Profile')

@section('content')
<div class="card card-custom p-4 mb-4">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('config.levels') }}" class="text-decoration-none">Levels & Classes</a></li>
            <li class="breadcrumb-item"><a href="{{ route('config.class_subjects', ['class_id' => $class->id]) }}" class="text-decoration-none">Class Subjects (Pillar 3)</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $class->class_name }}</li>
        </ol>
    </nav>

    <!-- Hero Header & Action Toolbar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom pb-4 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 bg-gradient text-white p-3 text-center shadow-xs" style="min-width: 85px; background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);">
                <span class="fs-5 fw-bold d-block">{{ $class->gradeLevel->grade_code ?? 'CLS' }}</span>
                <span class="badge bg-white text-primary fw-bold mt-1" style="font-size: 0.65rem;">SECTION</span>
            </div>
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h3 class="fw-bold mb-0 text-dark">{{ $class->class_name }}</h3>
                    <span class="badge bg-primary-subtle text-dark border border-primary-subtle px-2 py-1 small fw-bold">
                        🎓 {{ $class->gradeLevel->grade_name ?? 'Level' }}
                    </span>
                    @if($class->gradeLevel && $class->gradeLevel->division)
                        <span class="badge bg-light text-dark border px-2 py-1 small">
                            🏛️ {{ $class->gradeLevel->division->division_name }}
                        </span>
                    @endif
                    @if($class->homeroom_teacher)
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">
                            👨‍🏫 Homeroom: {{ $class->homeroom_teacher }}
                        </span>
                    @endif
                </div>
                <p class="text-muted small mb-0">
                    School: <strong>{{ $activeSchool->school_name }}</strong> • Academic Cycle: <strong>2026-2027</strong> • Capacity: <strong>{{ $class->students->count() }} / {{ $class->max_capacity }} Students</strong>
                </p>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('config.classes.gradebook', $class->id) }}" class="btn btn-primary fw-bold btn-sm shadow-xs">
                📖 Gradebook
            </a>
            <button type="button" class="btn btn-outline-primary fw-bold btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#modalAssignSubjectToClass">
                ＋ Assign Course
            </button>
            <button type="button" class="btn btn-outline-success fw-semibold btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#modalEnrollStudent">
                ＋ Enroll Student
            </button>
            <a href="{{ route('reports.broadsheet', ['class_id' => $class->id]) }}" class="btn btn-outline-indigo fw-semibold btn-sm shadow-xs" style="color: #4f46e5; border-color: #4f46e5;">
                📋 Broadsheet
            </a>
            <a href="{{ route('ingestion.template', $class->id) }}" class="btn btn-outline-secondary fw-semibold btn-sm shadow-xs">
                📥 Excel Template
            </a>
            <button type="button" class="btn btn-secondary fw-semibold btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#modalEditClass">
                ✏️ Edit Class
            </button>
            <a href="{{ route('config.levels') }}" class="btn btn-outline-secondary btn-sm fw-semibold">
                ← Back to Levels
            </a>
        </div>
    </div>

    <!-- Quick Metrics Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-2">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Assigned Courses</div>
                <h3 class="fw-bold text-primary mb-0 mt-1">{{ $totalCourses }}</h3>
                <small class="text-muted">Active Curriculum</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Enrolled Students</div>
                <h3 class="fw-bold text-success mb-0 mt-1">{{ $enrolledStudentsCount }}</h3>
                <small class="text-muted">Class Roster</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Capacity Usage</div>
                <h3 class="fw-bold {{ $capacityPct > 90 ? 'text-danger' : 'text-dark' }} mb-0 mt-1">{{ $capacityPct }}%</h3>
                <small class="text-muted">{{ $enrolledStudentsCount }} of {{ $class->max_capacity }} max</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Class Faculty</div>
                <h3 class="fw-bold text-indigo mb-0 mt-1">{{ $teachers->count() }}</h3>
                <small class="text-muted">Instructors</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Class Average</div>
                <h3 class="fw-bold {{ $avgScore >= 60 ? 'text-success' : 'text-warning' }} mb-0 mt-1">
                    {{ $avgScore !== null ? $avgScore . '%' : 'N/A' }}
                </h3>
                <small class="text-muted">Across all courses</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Pass Rate</div>
                <h3 class="fw-bold {{ $passRate >= 75 ? 'text-success' : 'text-danger' }} mb-0 mt-1">
                    {{ $passRate !== null ? $passRate . '%' : 'N/A' }}
                </h3>
                <small class="text-muted">Grade records total</small>
            </div>
        </div>
    </div>

    <!-- Tabbed Profile Workspace -->
    <ul class="nav nav-tabs fw-semibold small mb-4" id="classProfileTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabClassCourses">
                📚 Assigned Courses ({{ $totalCourses }})
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link text-secondary" data-bs-toggle="tab" data-bs-target="#tabClassStudents">
                👥 Enrolled Students ({{ $enrolledStudentsCount }})
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link text-secondary" data-bs-toggle="tab" data-bs-target="#tabClassGradebook" id="tabBtnGradebook">
                📖 Batch Gradebook
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link text-secondary" data-bs-toggle="tab" data-bs-target="#tabClassAttendance">
                📅 Attendance Summary ({{ $classAttendance['avg_rate'] !== null ? $classAttendance['avg_rate'] . '%' : 'N/A' }})
            </button>
        </li>
    </ul>

    <div class="tab-content" id="classProfileTabContent">
        <!-- ============================================================== -->
        <!-- TAB 1: ASSIGNED COURSES (courses_class)                         -->
        <!-- ============================================================== -->
        <div class="tab-pane fade show active" id="tabClassCourses">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Courses & Curriculum Taught in {{ $class->class_name }}</h6>
                    <small class="text-muted">Manage course parameters, teachers, weights, and inspect individual course-class profiles.</small>
                </div>
                <button type="button" class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalAssignSubjectToClass">
                    ＋ Assign Another Course
                </button>
            </div>

            @if($class->subjectAssignments->isEmpty())
                <div class="text-center py-5 bg-light rounded-3 border border-dashed my-3">
                    <span class="fs-1 d-block mb-2">📚</span>
                    <h5 class="fw-bold text-dark mb-1">No Courses Assigned to this Class Yet</h5>
                    <p class="text-muted small mb-4">
                        Assign subjects from your master catalog (e.g. Mathematics, English, Physics) to start grading students.
                    </p>
                    <button type="button" class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalAssignSubjectToClass">
                        ＋ Assign Course Now
                    </button>
                </div>
            @else
                <div class="table-responsive bg-white rounded-3 border shadow-2xs">
                    <table class="table table-hover align-middle mb-0 small text-center">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 45px;">#</th>
                                <th class="text-start">Course Name & Code</th>
                                <th class="text-start">Assigned Teacher</th>
                                <th>Credit Hours</th>
                                <th>CW / Exam</th>
                                <th>Passing Mark</th>
                                <th>Grading Blueprint</th>
                                <th>Roster</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($class->subjectAssignments as $assign)
                                <tr>
                                    <td class="fw-bold text-primary">{{ $assign->display_order }}</td>
                                    <td class="text-start">
                                        <div class="fw-bold text-dark fs-6">
                                            <a href="{{ route('config.class_subjects.profile', $assign->id) }}" class="text-decoration-none text-dark hover-primary" title="View Course-Class Profile">
                                                {{ $assign->subject->subject_name }}
                                            </a>
                                            <a href="{{ route('config.subjects.profile', $assign->subject->id) }}" class="badge bg-light text-secondary border text-decoration-none ms-1" title="Master Catalog Profile">
                                                {{ $assign->subject->subject_code }} ↗
                                            </a>
                                        </div>
                                        <small class="text-muted">{{ $assign->subject->department ?? 'General Department' }}</small>
                                    </td>
                                    <td class="text-start">
                                        @if($assign->assigned_teacher)
                                            <span class="fw-semibold text-dark">👨‍🏫 {{ $assign->assigned_teacher }}</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Unassigned</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold">{{ number_format($assign->credit_hours, 2) }}</td>
                                    <td class="text-muted">{{ $assign->cw_weight }}% / {{ $assign->exam_weight }}%</td>
                                    <td class="fw-semibold text-danger">{{ number_format($assign->passing_mark ?? 60.0, 1) }}%</td>
                                    <td>
                                        @if($assign->gradingRule)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold" title="Course-Class Specific Rule">
                                                ✓ {{ $assign->gradingRule->rule_name }}
                                            </span>
                                        @elseif($assign->subject->gradingRule)
                                            <span class="badge bg-info-subtle text-info border border-info-subtle fw-semibold" title="Inherited from Subject">
                                                ✓ {{ $assign->subject->gradingRule->rule_name }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-muted" title="Inherited from School Default">
                                                ● School Default
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $courseEnrolled = $assign->enrolledStudents->count();
                                            $batchTotal = $class->students->count();
                                        @endphp
                                        <span class="badge {{ $courseEnrolled < $batchTotal ? 'bg-warning-subtle text-dark border-warning' : 'bg-light text-dark' }} border">
                                            👥 {{ $courseEnrolled }} / {{ $batchTotal }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('config.classes.gradebook', ['id' => $class->id, 'assignment_id' => $assign->id]) }}" class="btn btn-outline-success py-0 px-2 fw-semibold" title="Open Gradebook for this Course">
                                                📖 Gradebook
                                            </a>
                                            <a href="{{ route('config.class_subjects.profile', $assign->id) }}" class="btn btn-outline-primary py-0 px-2 fw-semibold" title="View Course-Class Section Profile">
                                                👁️ Course
                                            </a>
                                            <button type="button" class="btn btn-outline-secondary py-0 px-2" title="Edit Allocation"
                                                    onclick="openEditCourseClassModal({{ $assign->id }}, '{{ addslashes($assign->subject->subject_name) }}', '{{ addslashes($assign->assigned_teacher ?? '') }}', {{ $assign->credit_hours }}, {{ $assign->cw_weight }}, {{ $assign->exam_weight }}, {{ $assign->passing_mark ?? 60 }}, {{ $assign->grading_rule_id ?? 'null' }})">
                                                ✏️
                                            </button>
                                            <form method="POST" action="{{ route('config.class_subjects.delete', $assign->id) }}" class="d-inline" onsubmit="return confirm('Remove {{ addslashes($assign->subject->subject_name) }} from {{ addslashes($class->class_name) }}?');">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger py-0 px-2" title="Unassign">
                                                    🗑️
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- ============================================================== -->
        <!-- TAB 2: ENROLLED STUDENTS                                        -->
        <!-- ============================================================== -->
        <div class="tab-pane fade" id="tabClassStudents">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Enrolled Students in {{ $class->class_name }}</h6>
                    <small class="text-muted">Total {{ $enrolledStudentsCount }} students enrolled in this section.</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalEnrollStudent">
                        ＋ Enroll New Student
                    </button>
                    <button type="button" class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalUploadClassStudentSheet">
                        📤 Upload Students Sheet
                    </button>
                    <a href="{{ route('config.students.template', ['class_id' => $class->id]) }}" class="btn btn-sm btn-outline-secondary fw-semibold">
                        📥 Download Template
                    </a>
                </div>
            </div>

            @if($class->students->isEmpty())
                <div class="text-center py-5 bg-light rounded-3 border border-dashed my-3">
                    <span class="fs-1 d-block mb-2">👥</span>
                    <h5 class="fw-bold text-dark mb-1">No Students Enrolled in this Class Yet</h5>
                    <p class="text-muted small mb-4">
                        Add students to this class or upload an Excel marksheet to enroll the roster automatically.
                    </p>
                    <button type="button" class="btn btn-success fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalEnrollStudent">
                        ＋ Enroll First Student
                    </button>
                </div>
            @else
                <div class="table-responsive bg-white rounded-3 border shadow-2xs">
                    <table class="table table-hover align-middle mb-0 small text-center">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th class="text-start">Admission No</th>
                                <th class="text-start">Student Full Name</th>
                                <th>Gender</th>
                                <th>Status</th>
                                <th>Attendance</th>
                                <th>Average Score</th>
                                <th class="text-end">Report Cards</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($class->students as $idx => $std)
                                @php
                                    $metric = $studentMetrics[$std->id] ?? null;
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ $idx + 1 }}</td>
                                    <td class="text-start fw-bold text-primary">{{ $std->admission_number }}</td>
                                    <td class="text-start fw-bold text-dark fs-6">{{ $std->full_name }}</td>
                                    <td>{{ $std->gender ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge {{ $std->status === 'ACTIVE' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-muted' }} border">
                                            {{ $std->status }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($metric && $metric['attendance_rate'] !== null)
                                            <span class="fw-semibold {{ $metric['attendance_rate'] >= 85 ? 'text-success' : 'text-warning' }}">
                                                {{ $metric['attendance_rate'] }}%
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($metric && $metric['average'] !== null)
                                            <span class="badge {{ $metric['average'] >= 60 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} border fw-bold">
                                                {{ $metric['average'] }}%
                                            </span>
                                        @else
                                            <span class="badge bg-light text-muted border">No grades</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('reports.preview', $std->id) }}" class="btn btn-outline-primary py-0 px-2 fw-semibold" title="Preview Student Report Card">
                                                👁️ Preview
                                            </a>
                                            <a href="{{ route('reports.export.jasper', $std->id) }}" class="btn btn-outline-secondary py-0 px-2" title="Download PDF">
                                                📄 PDF
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- ============================================================== -->
        <!-- TAB 3: ATTENDANCE SUMMARY                                       -->
        <!-- ============================================================== -->
        <div class="tab-pane fade" id="tabClassAttendance">
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="p-3 bg-light rounded-3 border text-center">
                        <small class="text-muted fw-semibold">Class Attendance Rate</small>
                        <h3 class="fw-bold text-success mb-0 mt-1">
                            {{ $classAttendance['avg_rate'] !== null ? $classAttendance['avg_rate'] . '%' : 'N/A' }}
                        </h3>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="p-3 bg-light rounded-3 border text-center">
                        <small class="text-muted fw-semibold">Total Days Tracked</small>
                        <h3 class="fw-bold text-dark mb-0 mt-1">{{ $classAttendance['total_days'] }}</h3>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="p-3 bg-light rounded-3 border text-center">
                        <small class="text-muted fw-semibold">Present Days</small>
                        <h3 class="fw-bold text-primary mb-0 mt-1">{{ $classAttendance['present'] }}</h3>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="p-3 bg-light rounded-3 border text-center">
                        <small class="text-muted fw-semibold">Absences (Unexcused / Excused)</small>
                        <h3 class="fw-bold text-danger mb-0 mt-1">
                            {{ $classAttendance['absent'] }} / {{ $classAttendance['excused'] }}
                        </h3>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0">Student Attendance Standings</h6>
                <a href="{{ route('config.attendance') }}" class="btn btn-sm btn-outline-primary fw-semibold">
                    ⚙️ Attendance Configuration
                </a>
            </div>

            <div class="table-responsive bg-white rounded-3 border shadow-2xs">
                <table class="table table-hover align-middle mb-0 small text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start">Student Name</th>
                            <th>Total Days</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Excused</th>
                            <th>Tardy</th>
                            <th>Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($class->students as $std)
                            @php
                                $att = $std->attendanceRecords->first();
                            @endphp
                            <tr>
                                <td class="text-start fw-bold">{{ $std->full_name }} ({{ $std->admission_number }})</td>
                                <td>{{ $att ? $att->total_days : 'N/A' }}</td>
                                <td class="text-success fw-bold">{{ $att ? $att->days_present : 'N/A' }}</td>
                                <td class="text-danger fw-bold">{{ $att ? $att->days_absent : 'N/A' }}</td>
                                <td>{{ $att ? $att->days_excused : 'N/A' }}</td>
                                <td>{{ $att ? $att->days_tardy : 'N/A' }}</td>
                                <td>
                                    @if($att)
                                        <span class="badge {{ $att->attendance_rate >= 85 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} border fw-bold">
                                            {{ $att->attendance_rate }}%
                                        </span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No student records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 3: BATCH GRADEBOOK & MARKSHEET                             -->
        <!-- ============================================================== -->
        <div class="tab-pane fade" id="tabClassGradebook">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">📖 Batch Gradebook: {{ $class->class_name }}</h6>
                    <small class="text-muted">Enter and manage student grades based on the configured grading systems and period categories.</small>
                </div>
                <a href="{{ route('config.classes.gradebook', ['id' => $class->id, 'assignment_id' => $selectedAssignment ? $selectedAssignment->id : null, 'period_id' => $selectedPeriod ? $selectedPeriod->id : null]) }}" class="btn btn-sm btn-primary fw-bold">
                    ⤢ Open Full-Screen Gradebook Studio ↗
                </a>
            </div>

            @if($assignments->isEmpty())
                <div class="text-center py-5 bg-light rounded-3 border border-dashed my-3">
                    <span class="fs-1 d-block mb-2">📚</span>
                    <h5 class="fw-bold text-dark mb-1">No Courses Assigned to this Batch</h5>
                    <p class="text-muted small mb-3">Assign courses to this class first to start grading.</p>
                    <button type="button" class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalAssignSubjectToClass">
                        ＋ Assign Course
                    </button>
                </div>
            @else
                <!-- Scope Switcher inside Tab -->
                <div class="p-3 bg-light rounded-3 border mb-3">
                    <form method="GET" action="{{ route('config.classes.profile', $class->id) }}">
                        <input type="hidden" name="tab" value="gradebook">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-dark mb-1">Subject / Course</label>
                                <select name="assignment_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                    @foreach($assignments as $assign)
                                        <option value="{{ $assign->id }}" {{ $selectedAssignment && $selectedAssignment->id == $assign->id ? 'selected' : '' }}>
                                            📖 {{ $assign->subject->subject_name }} ({{ $assign->subject->subject_code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-dark mb-1">Grading Period</label>
                                <select name="period_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                    @if($periods->isEmpty())
                                        <option value="">-- General Assessment / Term 1 --</option>
                                    @else
                                        @foreach($periods as $p)
                                            <option value="{{ $p->id }}" {{ $selectedPeriod && $selectedPeriod->id == $p->id ? 'selected' : '' }}>
                                                🗓️ {{ $p->period_name }} (Weight: {{ $p->weight }}%)
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <a href="{{ route('config.classes.gradebook', ['id' => $class->id, 'assignment_id' => $selectedAssignment ? $selectedAssignment->id : null, 'period_id' => $selectedPeriod ? $selectedPeriod->id : null]) }}" class="btn btn-sm btn-outline-primary w-100 fw-bold">
                                    Full Studio ↗
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                @if($selectedAssignment)
                    <!-- Fast Marksheet Form -->
                    <form method="POST" action="{{ route('config.classes.gradebook.save', $class->id) }}">
                        @csrf
                        <input type="hidden" name="assignment_id" value="{{ $selectedAssignment->id }}">
                        <input type="hidden" name="period_id" value="{{ $selectedPeriod ? $selectedPeriod->id : '' }}">
                        <input type="hidden" name="score_entry_mode" id="tabHiddenScoreEntryMode" value="weight">

                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 px-1">
                            <span class="text-muted small">Enter marks directly in cells. Totals and standing update automatically.</span>
                            <!-- Segmented Input Scale Toggle -->
                            <div class="d-flex align-items-center gap-1 bg-light px-2 py-1 rounded border shadow-2xs">
                                <span class="small fw-bold text-dark text-nowrap" style="font-size: 0.75rem;">Score Scale:</span>
                                <div class="btn-group btn-group-sm" role="group">
                                    <input type="radio" class="btn-check" name="tab_score_entry_mode_radio" id="tabSemWeight" value="weight" checked onchange="switchTabScoreEntryMode('weight')">
                                    <label class="btn btn-outline-primary fw-semibold py-0 px-2" for="tabSemWeight" style="font-size: 0.72rem;" title="Enter scores out of Category Weight (e.g. out of 30 for 30% weight)">
                                        ⚖️ Out of Weight (e.g. / 30)
                                    </label>

                                    <input type="radio" class="btn-check" name="tab_score_entry_mode_radio" id="tabSemPercent" value="percent" onchange="switchTabScoreEntryMode('percent')">
                                    <label class="btn btn-outline-primary fw-semibold py-0 px-2" for="tabSemPercent" style="font-size: 0.72rem;" title="Enter scores out of 100 (%)">
                                        💯 Out of 100 (%)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive rounded-3 border mb-3">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light text-secondary text-uppercase" style="font-size: 0.75rem;">
                                    <tr>
                                        <th class="ps-3" style="width: 40px;">#</th>
                                        <th style="min-width: 100px;">Adm #</th>
                                        <th style="min-width: 170px;">Student Name</th>
                                        @if($categories->isNotEmpty())
                                            @foreach($categories as $cat)
                                                @php
                                                    $catWeight = $cat->weight > 0 ? $cat->weight : $cat->max_score;
                                                @endphp
                                                <th class="text-center" style="min-width: 110px;">
                                                    {{ $cat->category_name }}
                                                    <div class="fw-normal text-muted tab-cat-scale-header" data-cat-id="{{ $cat->id }}" data-weight="{{ $catWeight }}" style="font-size: 0.65rem;">
                                                        Max {{ $catWeight }} <span class="fw-bold">({{ $cat->weight }}%)</span>
                                                    </div>
                                                </th>
                                            @endforeach
                                        @else
                                            <th class="text-center" style="min-width: 110px;">Coursework (Max 50)</th>
                                            <th class="text-center" style="min-width: 110px;">Final Exam (Max 50)</th>
                                        @endif
                                        <th class="text-center" style="min-width: 80px;">Total</th>
                                        <th class="text-center" style="min-width: 70px;">Grade</th>
                                        <th class="text-center" style="min-width: 90px;">Status</th>
                                        <th style="min-width: 150px;">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($students as $idx => $std)
                                        @php
                                            $gr = $gradeRecords->get($std->id);
                                            $stdSubScores = $subScores->get($std->id, collect())->keyBy('grading_category_id');
                                            $isPassed = $gr && $gr->total_score !== null && $gr->total_score >= $passLimit;
                                        @endphp
                                        <tr>
                                            <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                                            <td class="font-monospace fw-semibold">{{ $std->admission_number }}</td>
                                            <td class="fw-bold text-dark">{{ $std->full_name }}</td>

                                            @if($categories->isNotEmpty())
                                                @foreach($categories as $cat)
                                                    @php
                                                        $catScore = $stdSubScores->get($cat->id);
                                                        $rawVal = $catScore ? $catScore->raw_score : '';
                                                        $catWeight = $cat->weight > 0 ? $cat->weight : $cat->max_score;
                                                    @endphp
                                                    <td class="text-center">
                                                        <input type="number" 
                                                               step="0.5" 
                                                               min="0" 
                                                               max="{{ $catWeight }}" 
                                                               name="grades[{{ $std->id }}][categories][{{ $cat->id }}]" 
                                                               value="{{ $rawVal }}" 
                                                               class="form-control form-control-sm text-center fw-semibold tab-cat-input"
                                                               data-student-id="{{ $std->id }}"
                                                               data-weight="{{ $catWeight }}"
                                                               data-cat-max="{{ $catWeight }}"
                                                               data-max="{{ $catWeight }}"
                                                               data-active-max="{{ $catWeight }}"
                                                               oninput="calcTabRow({{ $std->id }})"
                                                               placeholder="0-{{ $catWeight }}">
                                                    </td>
                                                @endforeach
                                            @else
                                                <td class="text-center">
                                                    <input type="number" step="0.5" min="0" max="50" name="grades[{{ $std->id }}][cw_score]" value="{{ $gr ? $gr->cw_score : '' }}" class="form-control form-control-sm text-center fw-semibold tab-cw-input" data-student-id="{{ $std->id }}" oninput="calcTabRowFlat({{ $std->id }})">
                                                </td>
                                                <td class="text-center">
                                                    <input type="number" step="0.5" min="0" max="50" name="grades[{{ $std->id }}][exam_score]" value="{{ $gr ? $gr->exam_score : '' }}" class="form-control form-control-sm text-center fw-semibold tab-exam-input" data-student-id="{{ $std->id }}" oninput="calcTabRowFlat({{ $std->id }})">
                                                </td>
                                            @endif

                                            <td class="text-center">
                                                <input type="number" step="0.1" readonly name="grades[{{ $std->id }}][total_score]" id="tab_total_{{ $std->id }}" value="{{ $gr ? $gr->total_score : '' }}" class="form-control form-control-sm text-center fw-bold bg-light">
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold" id="tab_grade_{{ $std->id }}">
                                                    {{ $gr && $gr->letter_grade ? $gr->letter_grade : '-' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge {{ $isPassed ? 'bg-success' : ($gr && $gr->total_score !== null ? 'bg-danger' : 'bg-secondary') }}" id="tab_status_{{ $std->id }}">
                                                    {{ $isPassed ? 'Passed' : ($gr && $gr->total_score !== null ? 'Retake' : 'Pending') }}
                                                </span>
                                            </td>
                                            <td>
                                                <input type="text" name="grades[{{ $std->id }}][teacher_remarks]" value="{{ $gr ? $gr->teacher_remarks : '' }}" class="form-control form-control-sm" placeholder="Remarks...">
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted py-4">No enrolled students in this class.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Auto-calculates totals and grades based on grading blueprint.</span>
                            <button type="submit" class="btn btn-success fw-bold px-4">
                                💾 Save All Grades for {{ $selectedAssignment->subject->subject_name }}
                            </button>
                        </div>
                    </form>
                @endif
            @endif
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: ASSIGN SUBJECT / COURSE TO CLASS                        -->
<!-- ============================================================== -->
<div class="modal fade" id="modalAssignSubjectToClass" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.class_subjects.assign') }}" method="POST">
                @csrf
                <input type="hidden" name="class_id" value="{{ $class->id }}">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">＋ Assign Course to {{ $class->class_name }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Select Course / Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">-- Choose from Master Catalog --</option>
                            @foreach($masterSubjects as $subj)
                                <option value="{{ $subj->id }}">
                                    {{ $subj->subject_name }} ({{ $subj->subject_code }}) - {{ $subj->default_credits }} Cr
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Assigned Teacher</label>
                        <input type="text" name="assigned_teacher" class="form-control" placeholder="e.g. Dr. Euler or Prof. Gauss">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">Credit Hours</label>
                            <input type="number" step="0.5" name="credit_hours" class="form-control" value="1.0" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">CW %</label>
                            <input type="number" name="cw_weight" class="form-control" value="40" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">Exam %</label>
                            <input type="number" name="exam_weight" class="form-control" value="60" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Assign Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: ENROLL STUDENT INTO CLASS                               -->
<!-- ============================================================== -->
<div class="modal fade" id="modalEnrollStudent" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.classes.enroll_student', $class->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">＋ Enroll Student in {{ $class->class_name }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Admission Number <span class="text-danger">*</span></label>
                        <input type="text" name="admission_number" class="form-control" placeholder="e.g. STD-2026-001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Student Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. Johnathan Doe" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Status</label>
                            <select name="status" class="form-select">
                                <option value="ACTIVE" selected>ACTIVE</option>
                                <option value="INACTIVE">INACTIVE</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">Enroll Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: EDIT CLASS DETAILS                                      -->
<!-- ============================================================== -->
<div class="modal fade" id="modalEditClass" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.classes.update', $class->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title fw-bold">✏️ Edit Class: {{ $class->class_name }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Class / Section Name <span class="text-danger">*</span></label>
                        <input type="text" name="class_name" class="form-control" value="{{ $class->class_name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Academic Level <span class="text-danger">*</span></label>
                        <select name="grade_id" class="form-select" required>
                            @foreach($allLevels as $lvl)
                                <option value="{{ $lvl->id }}" {{ $class->grade_id == $lvl->id ? 'selected' : '' }}>
                                    {{ $lvl->grade_name }} ({{ $lvl->grade_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Homeroom Teacher</label>
                        <input type="text" name="homeroom_teacher" class="form-control" value="{{ $class->homeroom_teacher }}" placeholder="e.g. Mrs. Smith">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Maximum Capacity</label>
                        <input type="number" name="max_capacity" class="form-control" value="{{ $class->max_capacity }}" min="1" max="100">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: EDIT COURSE-CLASS PARAMETERS                            -->
<!-- ============================================================== -->
<div class="modal fade" id="modalEditCourseClass" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="formEditCourseClass" method="POST" action="">
                @csrf
                <div class="modal-header bg-info text-dark">
                    <h5 class="modal-title fw-bold" id="editCourseClassTitle">✏️ Edit Course Parameters</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Assigned Teacher</label>
                        <input type="text" name="assigned_teacher" id="editCCTeacher" class="form-control" placeholder="Teacher name">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">Credit Hours</label>
                            <input type="number" step="0.5" name="credit_hours" id="editCCCredits" class="form-control" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">CW %</label>
                            <input type="number" name="cw_weight" id="editCCCw" class="form-control" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">Exam %</label>
                            <input type="number" name="exam_weight" id="editCCExam" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Passing Mark (%)</label>
                        <input type="number" step="0.5" name="passing_mark" id="editCCPassLimit" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Specific Grading Blueprint</label>
                        <select name="grading_rule_id" id="editCCRule" class="form-select">
                            <option value="">-- Inherit from Subject / School Default --</option>
                            @foreach($gradingTemplates as $tmpl)
                                <option value="{{ $tmpl->id }}">{{ $tmpl->rule_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Update Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: UPLOAD CLASS STUDENTS SHEET                             -->
<!-- ============================================================== -->
<div class="modal fade" id="modalUploadClassStudentSheet" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.students.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="class_id" value="{{ $class->id }}">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">📤 Upload Students Sheet for {{ $class->class_name }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Upload an Excel (.xlsx, .xls) or CSV (.csv) file. All students in this file will be enrolled directly into <strong>{{ $class->class_name }}</strong>.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Choose Spreadsheet File <span class="text-danger">*</span></label>
                        <input type="file" name="student_sheet" class="form-control" accept=".xlsx,.xls,.csv" required>
                    </div>
                    <div class="p-3 bg-light rounded-2 border small">
                        💡 <strong>Need the template?</strong>
                        <a href="{{ route('config.students.template', ['class_id' => $class->id]) }}" class="text-decoration-none fw-bold text-primary ms-1">
                            Download Excel Template (.xlsx) ↗
                        </a>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Upload & Enroll</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const tabGradingCutoffs = @json($gradingCutoffs ?? []);
const tabPassLimit = {{ (float) ($passLimit ?? 60.0) }};

function getTabLetterAndGpa(score) {
    if (score === null || isNaN(score) || score === '') return { grade: '-', gpa: 0.0 };
    for (const tier of tabGradingCutoffs) {
        if (score >= tier.min && score <= tier.max) {
            return { grade: tier.grade, gpa: tier.gpa };
        }
    }
    return { grade: score >= 60 ? 'D' : 'F', gpa: score >= 60 ? 1.0 : 0.0 };
}

let tabScoreEntryMode = 'weight';

function switchTabScoreEntryMode(mode, convertExisting = true) {
    const oldMode = tabScoreEntryMode;
    tabScoreEntryMode = mode;

    const hiddenInp = document.getElementById('tabHiddenScoreEntryMode');
    if (hiddenInp) hiddenInp.value = mode;

    const radWeight = document.getElementById('tabSemWeight');
    const radPercent = document.getElementById('tabSemPercent');
    if (radWeight && radPercent) {
        radWeight.checked = (mode === 'weight');
        radPercent.checked = (mode === 'percent');
    }

    try {
        localStorage.setItem('gradebook_score_mode', mode);
    } catch(e) {}

    // Update header labels
    document.querySelectorAll('.tab-cat-scale-header').forEach(header => {
        const weight = header.dataset.weight;
        if (mode === 'percent') {
            header.innerHTML = `Out of 100 <span class="fw-bold">(${weight}%)</span>`;
        } else {
            header.innerHTML = `Max ${weight} <span class="fw-bold">(${weight}%)</span>`;
        }
    });

    // Update inputs
    const catInputs = document.querySelectorAll('input.tab-cat-input');
    const studentIds = new Set();

    catInputs.forEach(inp => {
        const catWeight = parseFloat(inp.dataset.weight || inp.dataset.catMax || 100);
        const activeMax = (mode === 'percent') ? 100 : catWeight;
        inp.dataset.activeMax = activeMax;
        inp.max = activeMax;
        inp.placeholder = (mode === 'percent') ? '0-100' : `0-${catWeight}`;

        if (convertExisting && inp.value !== '') {
            const currentVal = parseFloat(inp.value);
            if (!isNaN(currentVal)) {
                if (oldMode === 'weight' && mode === 'percent') {
                    const converted = (currentVal / catWeight) * 100.0;
                    inp.value = Math.round(converted * 10) / 10;
                } else if (oldMode === 'percent' && mode === 'weight') {
                    const converted = (currentVal / 100.0) * catWeight;
                    inp.value = Math.round(converted * 10) / 10;
                }
            }
        }

        const sid = inp.dataset.studentId;
        if (sid) studentIds.add(sid);
    });

    studentIds.forEach(sid => calcTabRow(sid));
}

function calcTabRow(studentId) {
    const inputs = document.querySelectorAll(`input.tab-cat-input[data-student-id="${studentId}"]`);
    let weightedTotal = 0;
    let anyEntered = false;

    inputs.forEach(inp => {
        const val = parseFloat(inp.value);
        const weight = parseFloat(inp.dataset.weight || 0);
        const isPercent = (tabScoreEntryMode === 'percent');

        if (!isNaN(val)) {
            anyEntered = true;
            if (isPercent) {
                const clampedVal = Math.min(Math.max(val, 0), 100.0);
                weightedTotal += (clampedVal / 100.0) * weight;
            } else {
                const clampedVal = Math.min(Math.max(val, 0), weight);
                weightedTotal += clampedVal;
            }
        }
    });

    const totalInp = document.getElementById(`tab_total_${studentId}`);
    const badgeGrade = document.getElementById(`tab_grade_${studentId}`);
    const badgeStatus = document.getElementById(`tab_status_${studentId}`);

    if (anyEntered) {
        const roundedTotal = Math.round(weightedTotal * 10) / 10;
        totalInp.value = roundedTotal;
        const info = getTabLetterAndGpa(roundedTotal);
        badgeGrade.innerText = `${info.grade} (${info.gpa})`;
        if (roundedTotal >= tabPassLimit) {
            badgeStatus.className = 'badge bg-success';
            badgeStatus.innerText = 'Passed';
        } else {
            badgeStatus.className = 'badge bg-danger';
            badgeStatus.innerText = 'Retake';
        }
    } else {
        totalInp.value = '';
        badgeGrade.innerText = '-';
        badgeStatus.className = 'badge bg-secondary';
        badgeStatus.innerText = 'Pending';
    }
}

function calcTabRowFlat(studentId) {
    const cw = parseFloat(document.querySelector(`input.tab-cw-input[data-student-id="${studentId}"]`)?.value || 0);
    const exam = parseFloat(document.querySelector(`input.tab-exam-input[data-student-id="${studentId}"]`)?.value || 0);
    const total = (isNaN(cw) ? 0 : cw) + (isNaN(exam) ? 0 : exam);
    const totalInp = document.getElementById(`tab_total_${studentId}`);
    const badgeGrade = document.getElementById(`tab_grade_${studentId}`);
    const badgeStatus = document.getElementById(`tab_status_${studentId}`);

    const roundedTotal = Math.round(total * 10) / 10;
    totalInp.value = roundedTotal;
    const info = getTabLetterAndGpa(roundedTotal);
    badgeGrade.innerText = `${info.grade} (${info.gpa})`;
    if (roundedTotal >= tabPassLimit) {
        badgeStatus.className = 'badge bg-success';
        badgeStatus.innerText = 'Passed';
    } else {
        badgeStatus.className = 'badge bg-danger';
        badgeStatus.innerText = 'Retake';
    }
}

function openEditCourseClassModal(assignId, subjName, teacher, credits, cw, exam, passLimit, ruleId) {
    document.getElementById('editCourseClassTitle').innerText = '✏️ Edit: ' + subjName;
    document.getElementById('formEditCourseClass').action = '/configuration/class-subjects/' + assignId + '/update';
    document.getElementById('editCCTeacher').value = teacher;
    document.getElementById('editCCCredits').value = credits;
    document.getElementById('editCCCw').value = cw;
    document.getElementById('editCCExam').value = exam;
    document.getElementById('editCCPassLimit').value = passLimit;
    document.getElementById('editCCRule').value = ruleId || '';

    new bootstrap.Modal(document.getElementById('modalEditCourseClass')).show();
}

document.addEventListener('DOMContentLoaded', function() {
    try {
        const savedMode = localStorage.getItem('gradebook_score_mode');
        if (savedMode === 'percent') {
            switchTabScoreEntryMode('percent', false);
        }
    } catch(e) {}

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tab') === 'gradebook' || window.location.hash === '#tabClassGradebook') {
        const tabEl = document.getElementById('tabBtnGradebook');
        if (tabEl) {
            bootstrap.Tab.getOrCreateInstance(tabEl).show();
        }
    }
});
</script>
@endsection
