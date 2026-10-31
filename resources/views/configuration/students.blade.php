@extends('layouts.app')

@section('title', 'Students Directory & Enrollment Studio - ' . $activeSchool->school_name)

@section('content')
<div class="card card-custom p-4 mb-4">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('config.levels') }}" class="text-decoration-none">Levels & Classes</a></li>
            <li class="breadcrumb-item active" aria-current="page">Students Directory & Enrollment</li>
        </ol>
    </nav>

    <!-- Hero Header & Action Toolbar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom pb-4 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 bg-gradient text-white p-3 text-center shadow-xs" style="min-width: 85px; background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <span class="fs-4 d-block">👥</span>
                <span class="badge bg-white text-success fw-bold mt-1" style="font-size: 0.65rem;">ROSTER</span>
            </div>
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h3 class="fw-bold mb-0 text-dark">Students Directory & Enrollment Studio</h3>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">
                        ● {{ $activeStudents }} Active Students
                    </span>
                    <span class="badge bg-light text-secondary border px-2 py-1 small">
                        🏛️ {{ $activeSchool->school_name }}
                    </span>
                </div>
                <p class="text-muted small mb-0">
                    Enroll students individually or bulk upload whole school/class rosters using formatted spreadsheets (.xlsx, .csv).
                </p>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-success fw-bold btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#modalEnrollSingleStudent">
                ＋ Enroll Student
            </button>
            <button type="button" class="btn btn-primary fw-bold btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#modalUploadStudentSheet">
                📤 Upload Students Sheet
            </button>
            <a href="{{ route('config.students.template') }}" class="btn btn-outline-secondary fw-semibold btn-sm shadow-xs" title="Download Excel Enrollment Template">
                📥 Download Excel Template
            </a>
            <a href="{{ route('config.levels') }}" class="btn btn-outline-secondary btn-sm fw-semibold">
                🏛️ School Levels
            </a>
        </div>
    </div>

    @if(session('import_warnings'))
        <div class="alert alert-warning alert-dismissible fade show small mb-4" role="alert">
            <h6 class="fw-bold mb-1">⚠️ Spreadsheet Import Notices:</h6>
            <ul class="mb-0 ps-3">
                @foreach(session('import_warnings') as $warn)
                    <li>{{ $warn }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Quick Metrics Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Total Students</div>
                <h3 class="fw-bold text-success mb-0 mt-1">{{ $totalStudents }}</h3>
                <small class="text-muted">Registered in school</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Active Enrollment</div>
                <h3 class="fw-bold text-primary mb-0 mt-1">{{ $activeStudents }}</h3>
                <small class="text-muted">{{ $totalStudents > 0 ? round(($activeStudents / $totalStudents) * 100, 1) : 0 }}% active status</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Classes / Batches</div>
                <h3 class="fw-bold text-indigo mb-0 mt-1">{{ $totalClasses }}</h3>
                <small class="text-muted">Across {{ $allLevels->count() }} levels</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Average Class Size</div>
                <h3 class="fw-bold text-dark mb-0 mt-1">
                    {{ $totalClasses > 0 ? round($totalStudents / $totalClasses, 1) : 0 }}
                </h3>
                <small class="text-muted">Students per section</small>
            </div>
        </div>
    </div>

    <!-- Filter and Search Bar with Dependent Level -> Batch Selection -->
    <form method="GET" action="{{ route('config.students.index') }}" class="p-3 bg-light rounded-3 border mb-4" id="studentFilterForm">
        @if(request()->has('school_id'))
            <input type="hidden" name="school_id" value="{{ request('school_id') }}">
        @endif
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="🔍 Search by name or admission number..." value="{{ $search }}">
            </div>
            <div class="col-md-3">
                <select name="grade_id" id="filterGradeSelect" class="form-select form-select-sm" onchange="handleLevelFilterChange(this.value)">
                    <option value="">-- All School Levels --</option>
                    @if($unassignedStudents > 0)
                        <option value="unassigned" {{ $gradeId === 'unassigned' ? 'selected' : '' }}>-- ⚠️ Unassigned Students ({{ $unassignedStudents }}) --</option>
                    @endif
                    @foreach($allLevels as $lvl)
                        <option value="{{ $lvl->id }}" {{ $gradeId == $lvl->id ? 'selected' : '' }}>
                            {{ $lvl->grade_name }} ({{ $lvl->grade_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="class_id" id="filterClassSelect" class="form-select form-select-sm" {{ empty($gradeId) || $gradeId === 'unassigned' ? 'disabled' : '' }}>
                    <option value="">
                        @if(empty($gradeId))
                            -- Select Level First --
                        @elseif($gradeId === 'unassigned')
                            -- Not Applicable (Unassigned) --
                        @else
                            -- All Classes / Batches in this Level --
                        @endif
                    </option>
                    @if(!empty($gradeId) && $gradeId !== 'unassigned')
                        @foreach($allClasses->where('grade_id', $gradeId) as $c)
                            <option value="{{ $c->id }}" class="filter-class-option" {{ $classId == $c->id ? 'selected' : '' }}>
                                {{ $c->class_name }} ({{ $c->gradeLevel->grade_code ?? 'CLS' }})
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold">Filter</button>
                @if(!empty($search) || !empty($gradeId) || !empty($classId) || $status !== 'ALL')
                    <a href="{{ route('config.students.index', request()->has('school_id') ? ['school_id' => request('school_id')] : []) }}" class="btn btn-sm btn-outline-secondary" title="Reset Filters">↺</a>
                @endif
            </div>
        </div>
    </form>

    <!-- Tabbed Workspace -->
    <ul class="nav nav-tabs fw-semibold small mb-4" id="studentStudioTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabAllStudents">
                👥 Student Directory ({{ $students->total() }})
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link text-secondary" data-bs-toggle="tab" data-bs-target="#tabClassesGrid">
                🏫 Enrollment by Class Batch ({{ $totalClasses }})
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link text-secondary" data-bs-toggle="tab" data-bs-target="#tabBulkUploadGuide">
                📤 Spreadsheet Upload Guide
            </button>
        </li>
    </ul>

    <div class="tab-content" id="studentStudioTabContent">
        <!-- ============================================================== -->
        <!-- TAB 1: ALL STUDENTS DIRECTORY                                  -->
        <!-- ============================================================== -->
        <div class="tab-pane fade show active" id="tabAllStudents">
            @if($students->isEmpty())
                <div class="text-center py-5 bg-light rounded-3 border border-dashed my-3">
                    <span class="fs-1 d-block mb-2">👥</span>
                    <h5 class="fw-bold text-dark mb-1">No Students Found</h5>
                    <p class="text-muted small mb-4">
                        @if(!empty($search) || !empty($classId) || !empty($gradeId))
                            No students match the selected search criteria. Try clearing the filters.
                        @else
                            Start building your school roster by enrolling students or uploading a spreadsheet.
                        @endif
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalEnrollSingleStudent">
                            ＋ Enroll Student
                        </button>
                        <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalUploadStudentSheet">
                            📤 Upload Students Sheet
                        </button>
                    </div>
                </div>
            @else
                <div class="table-responsive bg-white rounded-3 border shadow-2xs mb-3">
                    <table class="table table-hover align-middle mb-0 small text-center">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th class="text-start">Admission No</th>
                                <th class="text-start">Full Name</th>
                                <th class="text-start">Class / Section</th>
                                <th>Level</th>
                                <th>Gender</th>
                                <th>Status</th>
                                <th>Attendance</th>
                                <th>Graded Avg</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $idx => $std)
                                @php
                                    $att = $std->attendanceRecords->first();
                                    $hasGrades = $std->gradeRecords->isNotEmpty();
                                    $avg = $hasGrades ? round($std->gradeRecords->avg('total_score'), 1) : null;
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ $students->firstItem() + $idx }}</td>
                                    <td class="text-start fw-bold text-primary">{{ $std->admission_number }}</td>
                                    <td class="text-start fw-bold text-dark fs-6">{{ $std->full_name }}</td>
                                    <td class="text-start">
                                        @if($std->schoolClass)
                                            <a href="{{ route('config.classes.profile', $std->schoolClass->id) }}" class="fw-semibold text-dark text-decoration-none hover-primary">
                                                {{ $std->schoolClass->class_name }} ↗
                                            </a>
                                        @else
                                            <span class="text-muted fst-italic">Unassigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($std->schoolClass && $std->schoolClass->gradeLevel)
                                            <span class="badge bg-indigo-subtle text-dark border border-indigo-subtle fw-bold">
                                                {{ !empty(trim($std->schoolClass->gradeLevel->grade_code ?? '')) ? $std->schoolClass->gradeLevel->grade_code : ($std->schoolClass->gradeLevel->grade_name ?? 'Level') }}
                                            </span>
                                        @else
                                            <span class="badge bg-light text-muted border fst-italic">Unassigned</span>
                                        @endif
                                    </td>
                                    <td>{{ $std->gender ?? 'Other' }}</td>
                                    <td>
                                        <span class="badge {{ $std->status === 'ACTIVE' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-muted' }} border">
                                            {{ $std->status }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($att)
                                            <span class="fw-semibold {{ $att->attendance_rate >= 85 ? 'text-success' : 'text-warning' }}">
                                                {{ $att->attendance_rate }}%
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($avg !== null)
                                            <span class="badge {{ $avg >= 60 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} border fw-bold">
                                                {{ $avg }}%
                                            </span>
                                        @else
                                            <span class="badge bg-light text-muted border">No marks</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('reports.preview', $std->id) }}" class="btn btn-outline-primary py-0 px-2 fw-semibold" title="Preview Report Card">
                                                👁️
                                            </a>
                                            <a href="{{ route('reports.export.jasper', $std->id) }}" class="btn btn-outline-secondary py-0 px-2" title="Download PDF">
                                                📄
                                            </a>
                                            <button type="button" class="btn btn-outline-secondary py-0 px-2" title="Edit Student"
                                                    onclick="openEditStudentModal({{ $std->id }}, '{{ addslashes($std->admission_number) }}', '{{ addslashes($std->full_name) }}', {{ $std->class_id }}, '{{ $std->gender }}', '{{ $std->status }}')">
                                                ✏️
                                            </button>
                                            <form method="POST" action="{{ route('config.students.delete', $std->id) }}" class="d-inline" onsubmit="return confirm('Remove student {{ addslashes($std->full_name) }}?');">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger py-0 px-2" title="Remove">
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

                <div class="mt-3 pt-3 border-top">
                    {{ $students->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>

        <!-- ============================================================== -->
        <!-- TAB 2: ENROLLMENT BY CLASS / BATCH                             -->
        <!-- ============================================================== -->
        <div class="tab-pane fade" id="tabClassesGrid">
            <div class="row g-3">
                @foreach($allClasses as $c)
                    @php
                        $stdCount = $c->students->count();
                        $capPct = $c->max_capacity > 0 ? round(($stdCount / $c->max_capacity) * 100, 1) : 0;
                    @endphp
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border rounded-3 p-3 shadow-2xs">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="fw-bold text-dark mb-0 fs-6">
                                        <a href="{{ route('config.classes.profile', $c->id) }}" class="text-decoration-none text-dark hover-primary">
                                            {{ $c->class_name }} ↗
                                        </a>
                                    </h6>
                                    <small class="text-muted">{{ $c->gradeLevel->grade_name ?? 'Level' }} • {{ $c->homeroom_teacher ? 'Homeroom: ' . $c->homeroom_teacher : 'No homeroom' }}</small>
                                </div>
                                <span class="badge bg-primary fw-bold">{{ $c->gradeLevel->grade_code ?? 'CLS' }}</span>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center small mb-1">
                                    <span class="fw-semibold">Enrollment</span>
                                    <span class="text-muted">{{ $stdCount }} / {{ $c->max_capacity }} Students ({{ $capPct }}%)</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar {{ $capPct >= 95 ? 'bg-danger' : ($capPct >= 80 ? 'bg-warning' : 'bg-success') }}" style="width: {{ min(100, $capPct) }}%;"></div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-auto">
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-xs btn-outline-success py-1 px-2 fw-semibold" style="font-size: 0.75rem;" onclick="openEnrollForClass({{ $c->id }}, '{{ addslashes($c->class_name) }}')">
                                        ＋ Enroll
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2 fw-semibold" style="font-size: 0.75rem;" onclick="openUploadForClass({{ $c->id }}, '{{ addslashes($c->class_name) }}')">
                                        📤 Upload Sheet
                                    </button>
                                </div>
                                <a href="{{ route('config.classes.profile', $c->id) }}" class="btn btn-xs btn-outline-secondary py-1 px-2 fw-semibold" style="font-size: 0.75rem;">
                                    👁️ Profile
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 3: SPREADSHEET UPLOAD GUIDE                                -->
        <!-- ============================================================== -->
        <div class="tab-pane fade" id="tabBulkUploadGuide">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card border rounded-3 p-4 shadow-2xs h-100">
                        <h6 class="fw-bold text-dark mb-2">Bulk Student Import Instructions</h6>
                        <p class="text-muted small mb-3">
                            You can upload a single Excel (.xlsx) or CSV (.csv) file containing all your school's students. The system will automatically map each student to their assigned class section.
                        </p>

                        <h6 class="small fw-bold text-uppercase text-muted mb-2" style="font-size: 0.75rem;">Expected Column Headers:</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-sm small text-center align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Column Header</th>
                                        <th>Required?</th>
                                        <th>Accepted Variations</th>
                                        <th>Example Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-bold text-start"><code>admission_number</code></td>
                                        <td><span class="badge bg-danger">Required</span></td>
                                        <td class="text-muted">Admission No, Student ID, ID Number</td>
                                        <td class="font-monospace">STD-2026-001</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-start"><code>full_name</code></td>
                                        <td><span class="badge bg-danger">Required</span></td>
                                        <td class="text-muted">Student Name, Full Name, Name</td>
                                        <td>Alexander Hamilton</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-start"><code>class_name</code></td>
                                        <td><span class="badge bg-secondary">Optional</span></td>
                                        <td class="text-muted">Class, Batch, Section (Leave blank for unassigned)</td>
                                        <td>Grade 9-A</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-start"><code>gender</code></td>
                                        <td><span class="badge bg-secondary">Optional</span></td>
                                        <td class="text-muted">Sex (Male, Female, Other)</td>
                                        <td>Male</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-start"><code>status</code></td>
                                        <td><span class="badge bg-secondary">Optional</span></td>
                                        <td class="text-muted">ACTIVE, INACTIVE</td>
                                        <td>ACTIVE</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('config.students.template') }}" class="btn btn-primary fw-bold">
                                📥 Download Pre-Formatted Template (.xlsx)
                            </a>
                            <button type="button" class="btn btn-outline-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalUploadStudentSheet">
                                📤 Upload Your File Now
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border rounded-3 p-4 shadow-2xs h-100 bg-light">
                        <h6 class="fw-bold text-dark mb-2">⚡ Quick Upload Dropzone</h6>
                        <p class="text-muted small mb-3">Directly choose and process your student roster spreadsheet:</p>

                        <form action="{{ route('config.students.upload') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Target Class (Optional)</label>
                                <select name="class_id" class="form-select form-select-sm">
                                    <option value="">-- Multi-Class Sheet (Read from file) --</option>
                                    @foreach($allClasses as $c)
                                        <option value="{{ $c->id }}">Assign all to: {{ $c->class_name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Leave empty if your spreadsheet already has a Class Name column.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Select Spreadsheet (.xlsx, .csv) <span class="text-danger">*</span></label>
                                <input type="file" name="student_sheet" class="form-control" accept=".xlsx,.xls,.csv" required>
                            </div>

                            <button type="submit" class="btn btn-success fw-bold w-100 py-2">
                                📤 Process & Enroll All Students
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: ENROLL SINGLE STUDENT                                   -->
<!-- ============================================================== -->
<div class="modal fade" id="modalEnrollSingleStudent" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.students.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">＋ Enroll Individual Student</h5>
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
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-dark">Filter by Level <span class="text-muted fw-normal">(Optional)</span></label>
                            <select id="enrollModalLevelSelect" class="form-select" onchange="filterModalClasses(this.value, 'enrollModalClassSelect')">
                                <option value="">-- All Levels --</option>
                                @foreach($allLevels as $lvl)
                                    <option value="{{ $lvl->id }}">{{ $lvl->grade_name }} ({{ $lvl->grade_code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-bold text-dark">Assign to Class / Batch <span class="text-muted fw-normal">(Optional)</span></label>
                            <select name="class_id" id="enrollModalClassSelect" class="form-select">
                                <option value="">-- No Class / Unassigned Batch --</option>
                                @foreach($allClasses as $c)
                                    <option value="{{ $c->id }}" data-grade-id="{{ $c->grade_id }}" {{ $classId == $c->id ? 'selected' : '' }}>
                                        {{ $c->class_name }} ({{ $c->gradeLevel->grade_name ?? 'Level' }}) - {{ $c->students->count() }}/{{ $c->max_capacity }} std
                                    </option>
                                @endforeach
                            </select>
                        </div>
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
                            <label class="form-label small fw-bold text-dark">Enrollment Status</label>
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
<!-- MODAL: UPLOAD STUDENTS SHEET                                   -->
<!-- ============================================================== -->
<div class="modal fade" id="modalUploadStudentSheet" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.students.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="uploadModalTitle">📤 Upload Students Spreadsheet</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Upload an Excel (.xlsx, .xls) or CSV (.csv) file containing student records. If you haven't formatted your file yet, download our pre-made template below.
                    </p>

                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-dark">Filter by Level <span class="text-muted fw-normal">(Optional)</span></label>
                            <select id="uploadModalLevelSelect" class="form-select" onchange="filterModalClasses(this.value, 'uploadModalClassSelect', null, true)">
                                <option value="">-- All Levels --</option>
                                @foreach($allLevels as $lvl)
                                    <option value="{{ $lvl->id }}">{{ $lvl->grade_name }} ({{ $lvl->grade_code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-bold text-dark">Target Class (Optional)</label>
                            <select name="class_id" id="uploadModalClassSelect" class="form-select">
                                <option value="">-- Multi-Class Sheet (Read Class from Column) --</option>
                                @foreach($allClasses as $c)
                                    <option value="{{ $c->id }}" data-grade-id="{{ $c->grade_id }}">Assign all to: {{ $c->class_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Choose Spreadsheet File <span class="text-danger">*</span></label>
                        <input type="file" name="student_sheet" class="form-control" accept=".xlsx,.xls,.csv" required>
                    </div>

                    <div class="p-3 bg-light rounded-2 border small">
                        💡 <strong>Need the template?</strong>
                        <a href="{{ route('config.students.template') }}" class="text-decoration-none fw-bold text-primary ms-1">
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

<!-- ============================================================== -->
<!-- MODAL: EDIT STUDENT DETAILS                                    -->
<!-- ============================================================== -->
<div class="modal fade" id="modalEditStudent" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="formEditStudent" method="POST" action="">
                @csrf
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title fw-bold" id="editStudentModalTitle">✏️ Edit Student</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Admission Number <span class="text-danger">*</span></label>
                        <input type="text" name="admission_number" id="editStudentAdm" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Student Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="editStudentName" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-dark">Filter by Level <span class="text-muted fw-normal">(Optional)</span></label>
                            <select id="editModalLevelSelect" class="form-select" onchange="filterModalClasses(this.value, 'editStudentClass')">
                                <option value="">-- All Levels --</option>
                                @foreach($allLevels as $lvl)
                                    <option value="{{ $lvl->id }}">{{ $lvl->grade_name }} ({{ $lvl->grade_code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-bold text-dark">Class / Batch <span class="text-muted fw-normal">(Optional)</span></label>
                            <select name="class_id" id="editStudentClass" class="form-select">
                                <option value="">-- No Class / Unassigned Batch --</option>
                                @foreach($allClasses as $c)
                                    <option value="{{ $c->id }}" data-grade-id="{{ $c->grade_id }}">
                                        {{ $c->class_name }} ({{ $c->gradeLevel->grade_name ?? 'Level' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Gender</label>
                            <select name="gender" id="editStudentGender" class="form-select">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Status</label>
                            <select name="status" id="editStudentStatus" class="form-select">
                                <option value="ACTIVE">ACTIVE</option>
                                <option value="INACTIVE">INACTIVE</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Update Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const filterClassesData = {!! json_encode($classesData ?? []) !!};

function handleLevelFilterChange(gradeId, keepSelectedClassId = null) {
    const classSelect = document.getElementById('filterClassSelect');
    if (!classSelect) return;

    classSelect.innerHTML = '';

    if (!gradeId || gradeId === '') {
        // No level selected: prompt to select level first and disable
        classSelect.disabled = true;
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = '-- Select Level First --';
        classSelect.appendChild(opt);
        return;
    }

    if (gradeId === 'unassigned') {
        classSelect.disabled = true;
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = '-- Not Applicable (Unassigned) --';
        classSelect.appendChild(opt);
        return;
    }

    // A specific level is selected -> enable dropdown & populate matching classes only
    classSelect.disabled = false;
    const matching = filterClassesData.filter(c => String(c.grade_id) === String(gradeId));

    const defaultOpt = document.createElement('option');
    defaultOpt.value = '';
    defaultOpt.textContent = matching.length > 0 
        ? `-- All Classes / Batches in this Level (${matching.length}) --` 
        : '-- No Batches Found in this Level --';
    classSelect.appendChild(defaultOpt);

    matching.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = `${c.class_name} (${c.code})`;
        if (keepSelectedClassId && String(c.id) === String(keepSelectedClassId)) {
            opt.selected = true;
        }
        classSelect.appendChild(opt);
    });
}

function filterModalClasses(gradeId, targetSelectId, selectedClassId = null, isUploadModal = false) {
    const targetSelect = document.getElementById(targetSelectId);
    if (!targetSelect) return;

    targetSelect.innerHTML = '';

    const firstOpt = document.createElement('option');
    firstOpt.value = '';
    firstOpt.textContent = isUploadModal
        ? '-- Multi-Class Sheet (Read Class from Column) --'
        : '-- No Class / Unassigned Batch --';
    targetSelect.appendChild(firstOpt);

    const matching = gradeId 
        ? filterClassesData.filter(c => String(c.grade_id) === String(gradeId))
        : filterClassesData;

    matching.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        if (isUploadModal) {
            opt.textContent = `Assign all to: ${c.class_name} (${c.code})`;
        } else {
            opt.textContent = `${c.class_name} (${c.level_name}) - ${c.student_count}/${c.max_capacity} std`;
        }
        if (selectedClassId && String(c.id) === String(selectedClassId)) {
            opt.selected = true;
        }
        targetSelect.appendChild(opt);
    });
}

function openEnrollForClass(classId, className) {
    const classObj = filterClassesData.find(c => String(c.id) === String(classId));
    const levelSelect = document.getElementById('enrollModalLevelSelect');
    if (classObj && levelSelect) {
        levelSelect.value = classObj.grade_id;
        filterModalClasses(classObj.grade_id, 'enrollModalClassSelect', classId);
    } else {
        if (levelSelect) levelSelect.value = '';
        filterModalClasses('', 'enrollModalClassSelect', classId);
    }
    new bootstrap.Modal(document.getElementById('modalEnrollSingleStudent')).show();
}

function openUploadForClass(classId, className) {
    const classObj = filterClassesData.find(c => String(c.id) === String(classId));
    const levelSelect = document.getElementById('uploadModalLevelSelect');
    if (classObj && levelSelect) {
        levelSelect.value = classObj.grade_id;
        filterModalClasses(classObj.grade_id, 'uploadModalClassSelect', classId, true);
    } else {
        if (levelSelect) levelSelect.value = '';
        filterModalClasses('', 'uploadModalClassSelect', classId, true);
    }
    document.getElementById('uploadModalTitle').innerText = '📤 Upload Sheet for ' + className;
    new bootstrap.Modal(document.getElementById('modalUploadStudentSheet')).show();
}

function openEditStudentModal(id, adm, name, classId, gender, status) {
    document.getElementById('editStudentModalTitle').innerText = '✏️ Edit: ' + name;
    document.getElementById('formEditStudent').action = '/configuration/students/' + id + '/update';
    document.getElementById('editStudentAdm').value = adm;
    document.getElementById('editStudentName').value = name;
    document.getElementById('editStudentGender').value = gender || 'Other';
    document.getElementById('editStudentStatus').value = status || 'ACTIVE';

    const classObj = filterClassesData.find(c => String(c.id) === String(classId));
    const levelSelect = document.getElementById('editModalLevelSelect');
    if (classObj && levelSelect) {
        levelSelect.value = classObj.grade_id;
        filterModalClasses(classObj.grade_id, 'editStudentClass', classId);
    } else {
        if (levelSelect) levelSelect.value = '';
        filterModalClasses('', 'editStudentClass', classId);
    }

    new bootstrap.Modal(document.getElementById('modalEditStudent')).show();
}

document.addEventListener('DOMContentLoaded', function() {
    const currentGrade = "{{ $gradeId ?? '' }}";
    const currentClass = "{{ $classId ?? '' }}";
    if (currentGrade && currentGrade !== '') {
        handleLevelFilterChange(currentGrade, currentClass);
    } else {
        handleLevelFilterChange('', '');
    }
});
</script>
@endsection
