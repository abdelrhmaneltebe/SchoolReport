@extends('layouts.app')

@section('title', $assignment->subject->subject_name . ' - ' . $assignment->schoolClass->class_name . ' Profile')

@section('content')
<div class="card card-custom p-4 mb-4">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('config.levels') }}" class="text-decoration-none">Levels & Classes</a></li>
            <li class="breadcrumb-item"><a href="{{ route('config.classes.profile', $assignment->class_id) }}" class="text-decoration-none">{{ $assignment->schoolClass->class_name }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('config.subjects.profile', $assignment->subject_id) }}" class="text-decoration-none">{{ $assignment->subject->subject_name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Course-Class Profile</li>
        </ol>
    </nav>

    <!-- Hero Header & Action Toolbar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom pb-4 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 bg-primary text-white p-3 text-center shadow-xs" style="min-width: 85px;">
                <span class="fs-5 fw-bold d-block">{{ $assignment->subject->subject_code }}</span>
                <span class="badge bg-white text-primary fw-bold mt-1" style="font-size: 0.65rem;">{{ $assignment->credit_hours }} CR</span>
            </div>
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h3 class="fw-bold mb-0 text-dark">
                        {{ $assignment->subject->subject_name }}
                        <span class="text-muted fw-normal fs-5">in</span>
                        <a href="{{ route('config.classes.profile', $assignment->class_id) }}" class="text-decoration-none text-primary">
                            {{ $assignment->schoolClass->class_name }}
                        </a>
                    </h3>
                    @if($assignment->schoolClass && $assignment->schoolClass->gradeLevel)
                        <span class="badge bg-indigo-subtle text-dark border border-indigo-subtle px-2 py-1 small fw-bold">
                            🎓 {{ !empty(trim($assignment->schoolClass->gradeLevel->grade_name ?? '')) ? $assignment->schoolClass->gradeLevel->grade_name : ($assignment->schoolClass->gradeLevel->grade_code ?? 'Level') }}
                        </span>
                    @endif
                    <span class="badge bg-light text-dark border px-2 py-1 small">
                        📁 {{ $assignment->subject->department ?? 'General' }}
                    </span>
                    @if($assignment->assigned_teacher)
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">
                            👨‍🏫 {{ $assignment->assigned_teacher }}
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 small">
                            👨‍🏫 No Teacher Assigned
                        </span>
                    @endif
                </div>
                <p class="text-muted small mb-0">
                    School: <strong>{{ $activeSchool->school_name }}</strong> • Assessment Weights: <strong>CW {{ $assignment->cw_weight }}% / Exam {{ $assignment->exam_weight }}%</strong> • Passing Mark: <strong>{{ $passLimit }}%</strong>
                </p>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('config.classes.gradebook', ['id' => $assignment->class_id, 'assignment_id' => $assignment->id]) }}" class="btn btn-success fw-bold btn-sm shadow-xs">
                📖 Open Gradebook
            </a>
            <button type="button" class="btn btn-primary fw-bold btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#modalUploadGradebookSheet">
                📤 Upload Grades Sheet
            </button>
            <a href="{{ route('config.classes.gradebook.template', ['id' => $assignment->class_id, 'assignment_id' => $assignment->id]) }}" class="btn btn-outline-secondary fw-semibold btn-sm shadow-xs" title="Download Excel Template">
                📥 Excel Template
            </a>
            <button type="button" class="btn btn-outline-primary fw-semibold btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#modalManageCourseEnrollment">
                👥 Manage Roster ({{ $students->count() }}/{{ $allClassStudents->count() }})
            </button>
            <button type="button" class="btn btn-secondary fw-semibold btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#modalEditParameters">
                ✏️ Edit Parameters
            </button>
            <button type="button" class="btn btn-outline-info fw-semibold btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#modalChangeGradingBlueprint">
                🎓 Change Grading System
            </button>
            <a href="{{ route('config.classes.profile', $assignment->class_id) }}" class="btn btn-outline-secondary btn-sm fw-semibold">
                ← Back to {{ $assignment->schoolClass->class_name }}
            </a>
            <a href="{{ route('config.subjects.profile', $assignment->subject_id) }}" class="btn btn-outline-secondary btn-sm fw-semibold">
                📚 Master Subject Profile
            </a>
        </div>
    </div>

    <!-- Quick Metrics Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-2">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Enrolled Students</div>
                <h3 class="fw-bold text-primary mb-0 mt-1">{{ $students->count() }}</h3>
                <small class="text-muted">In this section</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Course Average</div>
                <h3 class="fw-bold {{ $avgScore >= 60 ? 'text-success' : 'text-warning' }} mb-0 mt-1">
                    {{ $avgScore !== null ? $avgScore . '%' : 'N/A' }}
                </h3>
                <small class="text-muted">Section Mean</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Highest Score</div>
                <h3 class="fw-bold text-success mb-0 mt-1">
                    {{ $highestScore !== null ? $highestScore . '%' : 'N/A' }}
                </h3>
                <small class="text-muted">Top Student Mark</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Lowest Score</div>
                <h3 class="fw-bold text-danger mb-0 mt-1">
                    {{ $lowestScore !== null ? $lowestScore . '%' : 'N/A' }}
                </h3>
                <small class="text-muted">Minimum Mark</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Pass Rate</div>
                <h3 class="fw-bold {{ $passRate >= 75 ? 'text-success' : 'text-danger' }} mb-0 mt-1">
                    {{ $passRate !== null ? $passRate . '%' : 'N/A' }}
                </h3>
                <small class="text-muted">Pass Threshold: {{ $passLimit }}%</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Grading Blueprint</div>
                <h6 class="fw-bold text-dark text-truncate mb-0 mt-1" title="{{ $effectiveRule->rule_name ?? 'Default' }}">
                    {{ $effectiveRule->rule_name ?? 'School Default' }}
                </h6>
                <small class="text-muted">
                    {{ $assignment->gradingRule ? 'Section Rule' : ($assignment->subject->gradingRule ? 'Subject Rule' : 'Default Rule') }}
                </small>
            </div>
        </div>
    </div>

    <!-- Tabbed Course-Class Workspace -->
    <ul class="nav nav-tabs fw-semibold small mb-4" id="courseClassTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabMarksheet">
                📝 Section Marksheet Roster ({{ $students->count() }})
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link text-secondary" data-bs-toggle="tab" data-bs-target="#tabBlueprint">
                🎓 Applied Grading Blueprint
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link text-secondary" data-bs-toggle="tab" data-bs-target="#tabAnalytics">
                📈 Grade Distribution
            </button>
        </li>
    </ul>

    <div class="tab-content" id="courseClassTabContent">
        <!-- ============================================================== -->
        <!-- TAB 1: SECTION MARKSHEET ROSTER                                -->
        <!-- ============================================================== -->
        <div class="tab-pane fade show active" id="tabMarksheet">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Student Marks for {{ $assignment->subject->subject_name }} in {{ $assignment->schoolClass->class_name }}</h6>
                    <small class="text-muted">Showing {{ $students->count() }} enrolled students out of {{ $allClassStudents->count() }} total in {{ $assignment->schoolClass->class_name }}.</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalManageCourseEnrollment">
                        👥 Manage Roster ({{ $students->count() }}/{{ $allClassStudents->count() }})
                    </button>
                    <a href="{{ route('config.classes.gradebook', ['id' => $assignment->class_id, 'assignment_id' => $assignment->id]) }}" class="btn btn-success btn-sm fw-bold">
                        📖 Open Full Gradebook
                    </a>
                </div>
            </div>

            @if($students->isEmpty())
                <div class="text-center py-5 bg-light rounded-3 border border-dashed my-3">
                    <span class="fs-1 d-block mb-2">👥</span>
                    <h5 class="fw-bold text-dark mb-1">No Students Currently Enrolled in this Subject</h5>
                    <p class="text-muted small mb-4">
                        You can selectively enroll students from {{ $assignment->schoolClass->class_name }} into this subject.
                    </p>
                    <button type="button" class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalManageCourseEnrollment">
                        👥 Enroll Students Now
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
                                <th>Classwork (CW)</th>
                                <th>Exam Score</th>
                                <th>Total Score</th>
                                <th>Grade / GPA</th>
                                <th>Status</th>
                                <th class="text-start">Remarks</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $idx => $std)
                                @php
                                    $record = $gradeRecords->get($std->id);
                                    $hasScore = $record && $record->total_score !== null;
                                    $isPassed = $hasScore && $record->total_score >= $passLimit;
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ $idx + 1 }}</td>
                                    <td class="text-start fw-bold text-primary">{{ $std->admission_number }}</td>
                                    <td class="text-start fw-bold text-dark fs-6">{{ $std->full_name }}</td>
                                    <td>
                                        {{ $record ? number_format($record->cw_score, 1) : '-' }}
                                    </td>
                                    <td>
                                        {{ $record ? number_format($record->exam_score, 1) : '-' }}
                                    </td>
                                    <td class="fw-bold fs-6 {{ $hasScore ? ($isPassed ? 'text-success' : 'text-danger') : 'text-muted' }}">
                                        {{ $hasScore ? number_format($record->total_score, 1) : 'Not Graded' }}
                                    </td>
                                    <td>
                                        @if($hasScore && $record->letter_grade)
                                            <span class="badge bg-light text-dark border fw-bold">
                                                {{ $record->letter_grade }} ({{ number_format($record->gpa_point, 2) }})
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($hasScore)
                                            @if($isPassed)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">Passed</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold">Failed</span>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary-subtle text-muted">Pending</span>
                                        @endif
                                    </td>
                                    <td class="text-start text-muted">
                                        <small>{{ $record->teacher_remarks ?? '-' }}</small>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 fw-semibold"
                                                onclick="openRecordGradeModal({{ $std->id }}, '{{ addslashes($std->full_name) }}', '{{ $std->admission_number }}', {{ $record ? ($record->cw_score ?? 'null') : 'null' }}, {{ $record ? ($record->exam_score ?? 'null') : 'null' }}, {{ $record ? ($record->total_score ?? 'null') : 'null' }}, '{{ addslashes($record->teacher_remarks ?? '') }}')">
                                            ✏️ Enter Mark
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- ============================================================== -->
        <!-- TAB 2: APPLIED GRADING BLUEPRINT                               -->
        <!-- ============================================================== -->
        <div class="tab-pane fade" id="tabBlueprint">
            <div class="p-3 bg-light rounded-3 border mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            Active Blueprint: {{ $effectiveRule->rule_name ?? 'School Default Template' }}
                        </h6>
                        <small class="text-muted">
                            Architecture Depth: <strong>{{ str_replace('_', ' ', $effectiveRule->scenario_depth ?? '2_TIER') }}</strong> • Pass Limit: <strong>{{ $passLimit }}%</strong> • Total Scale: <strong>{{ $effectiveRule->total_grade ?? 100 }} pts</strong>
                        </small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modalChangeGradingBlueprint">
                        🎓 Switch Blueprint
                    </button>
                </div>
            </div>

            @if($effectiveRule && $effectiveRule->periods->isNotEmpty())
                <div class="row g-3">
                    @foreach($effectiveRule->periods as $period)
                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 border shadow-2xs rounded-3">
                                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary fw-bold">{{ $period->display_order }}</span>
                                        <h6 class="fw-bold mb-0 text-dark">{{ $period->period_name }}</h6>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary fw-bold">{{ $period->weight }}% Weight</span>
                                </div>
                                <div class="card-body p-3">
                                    <h6 class="small fw-bold text-muted text-uppercase mb-2" style="font-size: 0.75rem;">Categories & Assessment Tasks:</h6>
                                    @if($period->categories->isEmpty() && $period->subPeriods->isEmpty())
                                        <p class="text-muted small mb-0">No categories defined in this period.</p>
                                    @endif

                                    @if($period->categories->isNotEmpty())
                                        <ul class="list-group list-group-flush small">
                                            @foreach($period->categories as $cat)
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1.5">
                                                    <div>
                                                        <span class="fw-semibold text-dark">{{ $cat->category_name }}</span>
                                                        <small class="text-muted font-monospace d-block">Max {{ $cat->max_score }} pts</small>
                                                    </div>
                                                    <span class="badge bg-light text-secondary border fw-bold">{{ $cat->weight }}%</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if($period->subPeriods->isNotEmpty())
                                        <div class="mt-2 pt-2 border-top">
                                            <h6 class="small fw-bold text-muted text-uppercase mb-2" style="font-size: 0.75rem;">Sub-Periods:</h6>
                                            @foreach($period->subPeriods as $sp)
                                                <div class="p-2 bg-light rounded-2 border mb-2 small">
                                                    <div class="d-flex justify-content-between align-items-center fw-bold">
                                                        <span>{{ $sp->period_name }}</span>
                                                        <span class="badge bg-secondary-subtle text-secondary">{{ $sp->weight }}%</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 bg-light rounded-3 border">
                    <p class="text-muted small mb-0">No grading periods configured under this blueprint.</p>
                </div>
            @endif
        </div>

        <!-- ============================================================== -->
        <!-- TAB 3: GRADE DISTRIBUTION & ANALYTICS                          -->
        <!-- ============================================================== -->
        <div class="tab-pane fade" id="tabAnalytics">
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card border rounded-3 p-3 shadow-2xs h-100">
                        <h6 class="fw-bold text-dark mb-3">Letter Grade Distribution</h6>
                        <div class="d-flex flex-column gap-2">
                            @foreach($distribution as $letter => $count)
                                @php
                                    $totalScored = array_sum($distribution);
                                    $pct = $totalScored > 0 ? round(($count / $totalScored) * 100, 1) : 0;
                                    $barColor = match($letter) {
                                        'A' => 'bg-success',
                                        'B' => 'bg-info',
                                        'C' => 'bg-primary',
                                        'D' => 'bg-warning',
                                        default => 'bg-danger'
                                    };
                                @endphp
                                <div>
                                    <div class="d-flex justify-content-between align-items-center small mb-1">
                                        <span class="fw-bold">Grade {{ $letter }}</span>
                                        <span class="text-muted">{{ $count }} Students ({{ $pct }}%)</span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border rounded-3 p-3 shadow-2xs h-100">
                        <h6 class="fw-bold text-dark mb-3">Performance Highlights</h6>
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Total Enrolled in Section:</span>
                                <span class="fw-bold text-dark">{{ $students->count() }} Students</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Passing Threshold:</span>
                                <span class="fw-bold text-danger">{{ $passLimit }}%</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Students Meeting Pass Mark:</span>
                                <span class="fw-bold text-success">{{ $passRate !== null ? $passRate . '%' : 'N/A' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Highest Student Score:</span>
                                <span class="fw-bold text-primary">{{ $highestScore !== null ? $highestScore . '%' : 'N/A' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Lowest Student Score:</span>
                                <span class="fw-bold text-warning">{{ $lowestScore !== null ? $lowestScore . '%' : 'N/A' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: EDIT COURSE-CLASS PARAMETERS                            -->
<!-- ============================================================== -->
<div class="modal fade" id="modalEditParameters" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.class_subjects.update', $assignment->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title fw-bold">✏️ Edit Section Parameters</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Assigned Teacher</label>
                        <input type="text" name="assigned_teacher" class="form-control" value="{{ $assignment->assigned_teacher }}" placeholder="e.g. Dr. Euler">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">Credit Hours</label>
                            <input type="number" step="0.5" name="credit_hours" class="form-control" value="{{ $assignment->credit_hours }}" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">CW %</label>
                            <input type="number" name="cw_weight" class="form-control" value="{{ $assignment->cw_weight }}" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">Exam %</label>
                            <input type="number" name="exam_weight" class="form-control" value="{{ $assignment->exam_weight }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Passing Mark (%)</label>
                        <input type="number" step="0.5" name="passing_mark" class="form-control" value="{{ $assignment->passing_mark ?? 60.0 }}" required>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Save Parameters</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: CHANGE GRADING BLUEPRINT                                -->
<!-- ============================================================== -->
<div class="modal fade" id="modalChangeGradingBlueprint" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.class_subjects.update', $assignment->id) }}" method="POST">
                @csrf
                <input type="hidden" name="assigned_teacher" value="{{ $assignment->assigned_teacher }}">
                <input type="hidden" name="credit_hours" value="{{ $assignment->credit_hours }}">
                <input type="hidden" name="cw_weight" value="{{ $assignment->cw_weight }}">
                <input type="hidden" name="exam_weight" value="{{ $assignment->exam_weight }}">
                <input type="hidden" name="passing_mark" value="{{ $assignment->passing_mark ?? 60.0 }}">

                <div class="modal-header bg-info text-dark">
                    <h5 class="modal-title fw-bold">🎓 Change Grading System</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Select a specific grading blueprint for this class section, or choose to inherit from the master subject / school default.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Select Blueprint <span class="text-danger">*</span></label>
                        <select name="grading_rule_id" class="form-select">
                            <option value="">-- Inherit from Subject / School Default --</option>
                            @foreach($gradingTemplates as $tmpl)
                                <option value="{{ $tmpl->id }}" {{ $assignment->grading_rule_id == $tmpl->id ? 'selected' : '' }}>
                                    {{ $tmpl->rule_name }} ({{ str_replace('_', ' ', $tmpl->scenario_depth) }}) - Pass: {{ $tmpl->pass_limit }}%
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Apply Blueprint</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: RECORD / UPDATE STUDENT GRADE                           -->
<!-- ============================================================== -->
<div class="modal fade" id="modalRecordGrade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.class_subjects.record_grade', $assignment->id) }}" method="POST">
                @csrf
                <input type="hidden" name="student_id" id="rgStudentId">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="rgTitle">✏️ Enter Student Mark</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-2 bg-light rounded-2 border mb-3 small">
                        Student: <strong id="rgStudentName" class="text-dark"></strong> (<span id="rgAdmissionNo" class="text-muted"></span>)
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Classwork (CW) Score</label>
                            <input type="number" step="0.5" min="0" max="{{ $assignment->cw_weight }}" name="cw_score" id="rgCwScore" class="form-control" placeholder="Max {{ $assignment->cw_weight }}" oninput="calculateTotal()">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Exam Score</label>
                            <input type="number" step="0.5" min="0" max="{{ $assignment->exam_weight }}" name="exam_score" id="rgExamScore" class="form-control" placeholder="Max {{ $assignment->exam_weight }}" oninput="calculateTotal()">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Total Score (0 - 100)</label>
                        <input type="number" step="0.5" min="0" max="100" name="total_score" id="rgTotalScore" class="form-control fw-bold fs-5 text-primary" placeholder="Calculated automatically or custom override">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Teacher Remarks</label>
                        <input type="text" name="teacher_remarks" id="rgRemarks" class="form-control" placeholder="e.g. Excellent progress, active in discussions">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Save Grade</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openRecordGradeModal(studentId, studentName, admissionNo, cw, exam, total, remarks) {
    document.getElementById('rgStudentId').value = studentId;
    document.getElementById('rgStudentName').innerText = studentName;
    document.getElementById('rgAdmissionNo').innerText = admissionNo;
    document.getElementById('rgCwScore').value = cw !== null ? cw : '';
    document.getElementById('rgExamScore').value = exam !== null ? exam : '';
    document.getElementById('rgTotalScore').value = total !== null ? total : '';
    document.getElementById('rgRemarks').value = remarks || '';

    new bootstrap.Modal(document.getElementById('modalRecordGrade')).show();
}

function calculateTotal() {
    const cw = parseFloat(document.getElementById('rgCwScore').value) || 0;
    const exam = parseFloat(document.getElementById('rgExamScore').value) || 0;
    if (document.getElementById('rgCwScore').value !== '' || document.getElementById('rgExamScore').value !== '') {
        document.getElementById('rgTotalScore').value = (cw + exam).toFixed(1);
    }
}

function toggleAllCourseRoster(check) {
    document.querySelectorAll('.course-roster-cb').forEach(cb => cb.checked = check);
}
</script>

<!-- ============================================================== -->
<!-- MODAL: MANAGE COURSE ENROLLMENT ROSTER                         -->
<!-- ============================================================== -->
<div class="modal fade" id="modalManageCourseEnrollment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient text-white p-3" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-4">👥</span>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white">Manage Subject Enrollment Roster</h5>
                        <small class="text-white-50">{{ $assignment->subject->subject_name }} &bull; {{ $assignment->schoolClass->class_name }}</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('config.class_subjects.enrollments.update', $assignment->id) }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small border-0 mb-3">
                        Select which students from <strong>{{ $assignment->schoolClass->class_name }}</strong> take <strong>{{ $assignment->subject->subject_name }}</strong>. You can enroll or exclude individual students to support electives and custom curricula.
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted">Total Batch Students: <strong>{{ $allClassStudents->count() }}</strong></span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleAllCourseRoster(true)">✓ Select All</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleAllCourseRoster(false)">✗ Deselect All</button>
                        </div>
                    </div>

                    <div class="list-group rounded-3 border overflow-auto" style="max-height: 380px;">
                        @forelse($allClassStudents as $std)
                            @php
                                $isEnrolled = $students->contains('id', $std->id);
                            @endphp
                            <label class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-2">
                                <div class="d-flex align-items-center gap-3">
                                    <input class="form-check-input course-roster-cb me-1" type="checkbox" name="student_ids[]" value="{{ $std->id }}" {{ $isEnrolled ? 'checked' : '' }}>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $std->full_name }}</div>
                                        <small class="text-muted">{{ $std->admission_number }} &bull; {{ $std->gender ?? 'Other' }}</small>
                                    </div>
                                </div>
                                <span class="badge {{ $isEnrolled ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-muted' }} border">
                                    {{ $isEnrolled ? 'Enrolled' : 'Not Enrolled' }}
                                </span>
                            </label>
                        @empty
                            <div class="p-3 text-center text-muted">No students registered in {{ $assignment->schoolClass->class_name }} yet.</div>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Save Course Roster</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: UPLOAD GRADES SPREADSHEET (ANY CATEGORY OR ALL)         -->
<!-- ============================================================== -->
<div class="modal fade" id="modalUploadGradebookSheet" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient text-white p-3" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-4">📤</span>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white">Upload Grades Spreadsheet</h5>
                        <small class="text-white-50">{{ $assignment->subject->subject_name }} &bull; {{ $assignment->schoolClass->class_name }}</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('config.classes.gradebook.upload', $assignment->class_id) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="assignment_id" value="{{ $assignment->id }}">

                <div class="modal-body p-4">
                    <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="fs-3">📥</span>
                            <div>
                                <strong class="text-dark d-block">Pre-filled Excel Marksheet Template</strong>
                                <small class="text-muted">Download a spreadsheet pre-populated with student admission numbers and full names.</small>
                            </div>
                        </div>
                        <a href="{{ route('config.classes.gradebook.template', ['id' => $assignment->class_id, 'assignment_id' => $assignment->id]) }}" class="btn btn-sm btn-outline-primary fw-bold text-nowrap">
                            📥 Download Template
                        </a>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark mb-1">Target Grading Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">🌟 Auto-Detect from Sheet Columns (Match all categories automatically)</option>
                            @if(isset($effectiveRule) && $effectiveRule && $effectiveRule->categories->isNotEmpty())
                                <optgroup label="Rule Categories">
                                    @foreach($effectiveRule->categories as $cat)
                                        <option value="{{ $cat->id }}">
                                            📝 {{ $cat->category_name }} [Max: {{ $cat->max_score }}, Weight: {{ $cat->weight }}%]
                                        </option>
                                    @endforeach
                                </optgroup>
                            @else
                                <optgroup label="Assessment Components">
                                    <option value="cw">Classwork (CW) [Max: {{ $assignment->cw_weight }}%]</option>
                                    <option value="exam">Exam Score [Max: {{ $assignment->exam_weight }}%]</option>
                                </optgroup>
                            @endif
                        </select>
                        <small class="text-muted d-block mt-1">
                            Choose <strong>Auto-Detect</strong> to import multiple columns matching your grading periods, or pick a <strong>specific category</strong> to map any single score column directly into that category.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark mb-1">Select Spreadsheet File (.xlsx, .xls, .csv) <span class="text-danger">*</span></label>
                        <input type="file" name="sheet_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <small class="text-muted d-block mt-1">
                            Accepted formats: Excel Workbook (<code>.xlsx</code>, <code>.xls</code>) or Comma-Separated Values (<code>.csv</code>).
                        </small>
                    </div>

                    <div class="alert alert-info py-2 px-3 small border-0 mb-0">
                        <strong>ℹ️ Note:</strong> The system matches students using their <strong>Admission Number</strong> or <strong>Full Name</strong>.
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        📤 Upload & Apply Grades
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
