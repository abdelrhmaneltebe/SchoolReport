@extends('layouts.app')

@section('title', $subject->subject_name . ' (' . $subject->subject_code . ') - Subject Profile')

@section('content')
<div class="card card-custom p-4 mb-4">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('config.class_subjects') }}" class="text-decoration-none">Class Subjects (Pillar 3)</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $subject->subject_name }}</li>
        </ol>
    </nav>

    <!-- Hero Header & Action Toolbar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom pb-4 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 bg-primary text-white p-3 text-center shadow-xs" style="min-width: 80px;">
                <span class="fs-6 fw-bold d-block text-uppercase">{{ $subject->subject_code }}</span>
                <span class="badge bg-white text-primary fw-bold mt-1" style="font-size: 0.65rem;">{{ $subject->default_credits }} CR</span>
            </div>
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h3 class="fw-bold mb-0 text-dark">{{ $subject->subject_name }}</h3>
                    <span class="badge bg-light text-dark border px-2 py-1 small">
                        📁 {{ $subject->department ?? 'General Department' }}
                    </span>
                    @if($subject->is_active)
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">
                            ● Active in Curriculum
                        </span>
                    @else
                        <span class="badge bg-secondary-subtle text-muted px-2 py-1 small">
                            ● Inactive
                        </span>
                    @endif
                </div>
                <p class="text-muted small mb-0">
                    School: <strong>{{ $activeSchool->school_name }}</strong> • Master catalog subject profiling, curriculum allocation, faculty assignments, and grading metrics.
                </p>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-outline-info fw-semibold btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#modalApplyGradingSystem">
                🎓 Change Grading System
            </button>
            <button type="button" class="btn btn-outline-primary fw-semibold btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#modalAssignToLevel">
                🎓 Assign to Level(s)
            </button>
            <button type="button" class="btn btn-outline-success fw-semibold btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#modalAssignToClass">
                🏫 Assign to Batch(es)
            </button>
            <button type="button" class="btn btn-secondary fw-semibold btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#modalEditSubject">
                ✏️ Edit Details
            </button>
            <a href="{{ route('config.class_subjects') }}" class="btn btn-outline-secondary btn-sm fw-semibold">
                ← Back to Catalog
            </a>
        </div>
    </div>

    <!-- Quick Metrics Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-2">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Academic Levels</div>
                <h3 class="fw-bold text-primary mb-0 mt-1">{{ $assignedLevels->count() }}</h3>
                <small class="text-muted">Enrolled Stages</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Class Batches</div>
                <h3 class="fw-bold text-success mb-0 mt-1">{{ $subject->assignments->count() }}</h3>
                <small class="text-muted">Active Sections</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Enrolled Students</div>
                <h3 class="fw-bold text-indigo mb-0 mt-1">{{ $totalStudentsCount }}</h3>
                <small class="text-muted">Roster Total</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Faculty / Teachers</div>
                <h3 class="fw-bold text-dark mb-0 mt-1">{{ $teachers->count() }}</h3>
                <small class="text-muted">Instructors</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Passing Mark</div>
                <h3 class="fw-bold text-danger mb-0 mt-1">
                    {{ number_format($subject->pass_limit ?? ($effectiveRule->pass_limit ?? 60.0), 1) }}%
                </h3>
                <small class="text-muted">Max: {{ number_format($subject->max_score, 0) }}</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-2xs">
                <div class="text-muted small fw-semibold">Graded Average</div>
                <h3 class="fw-bold {{ $avgScore >= 60 ? 'text-success' : 'text-warning' }} mb-0 mt-1">
                    {{ $avgScore !== null ? $avgScore . '%' : 'N/A' }}
                </h3>
                <small class="text-muted">{{ $passRate !== null ? $passRate . '% Pass Rate' : 'No records yet' }}</small>
            </div>
        </div>
    </div>

    <!-- Tabbed Profile Workspace -->
    <ul class="nav nav-tabs fw-semibold small mb-4" id="profileTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabAllocations">
                🏫 Levels & Batches Allocation ({{ $subject->assignments->count() }})
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link text-secondary" data-bs-toggle="tab" data-bs-target="#tabGrading">
                🎓 Applied Grading System & Blueprint
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link text-secondary" data-bs-toggle="tab" data-bs-target="#tabStudents">
                👥 Enrolled Student Roster ({{ $totalStudentsCount }})
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link text-secondary" data-bs-toggle="tab" data-bs-target="#tabFaculty">
                👨‍🏫 Assigned Faculty ({{ $teachers->count() }})
            </button>
        </li>
    </ul>

    <div class="tab-content" id="profileTabsContent">
        <!-- ============================================================== -->
        <!-- TAB 1: LEVELS & BATCHES ALLOCATION                            -->
        <!-- ============================================================== -->
        <div class="tab-pane fade show active" id="tabAllocations">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Where is {{ $subject->subject_name }} Taught?</h6>
                    <small class="text-muted">Classes, sections, and credit weight distributions across school levels.</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modalAssignToLevel">
                        ＋ Assign to Level(s)
                    </button>
                    <button type="button" class="btn btn-sm btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalAssignToClass">
                        ＋ Assign to Batch(es)
                    </button>
                </div>
            </div>

            @if($subject->assignments->isEmpty())
                <div class="text-center py-5 bg-light rounded-3 border border-dashed my-3">
                    <span class="fs-1 d-block mb-2">🏫</span>
                    <h5 class="fw-bold text-dark mb-1">Subject is Not Assigned to Any Batches Yet</h5>
                    <p class="text-muted small mb-4">
                        Assign this subject to entire levels or individual class sections so teachers and students can be enrolled.
                    </p>
                    <button type="button" class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalAssignToLevel">
                        🎓 Assign to Level(s) Now
                    </button>
                </div>
            @else
                <div class="table-responsive bg-white rounded-3 border shadow-2xs">
                    <table class="table table-hover align-middle mb-0 small text-center">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">Level / Stage</th>
                                <th class="text-start">Class / Batch</th>
                                <th class="text-start">Assigned Teacher</th>
                                <th>Credit Hours</th>
                                <th>CW Weight</th>
                                <th>Exam Weight</th>
                                <th>Pass Mark</th>
                                <th>Enrolled</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subject->assignments as $assign)
                                <tr>
                                    <td class="text-start">
                                        @php
                                            $gradeLvl = $assign->schoolClass?->gradeLevel;
                                            $divName = $gradeLvl?->division?->division_name;
                                            $levelTitle = !empty(trim($gradeLvl->grade_name ?? '')) ? $gradeLvl->grade_name : ($gradeLvl->grade_code ?? 'Level');
                                        @endphp
                                        @if($gradeLvl)
                                            <span class="badge bg-indigo-subtle border border-indigo-subtle fw-bold px-2 py-1" style="color: #1e1b4b !important;">
                                                {{ $levelTitle }}
                                                @if($divName)
                                                    <span class="text-secondary fw-normal">({{ $divName }})</span>
                                                @endif
                                            </span>
                                        @else
                                            <span class="badge bg-light border fst-italic px-2 py-1" style="color: #334155 !important;">All Levels / General</span>
                                        @endif
                                    </td>
                                    <td class="text-start fw-bold text-dark fs-6">
                                        <a href="{{ route('config.classes.gradebook', ['id' => $assign->class_id, 'assignment_id' => $assign->id]) }}" class="text-decoration-none fw-bold text-dark hover-primary" title="Open Gradebook for {{ $assign->schoolClass->class_name }} in {{ $subject->subject_name }}">
                                            {{ $assign->schoolClass->class_name }} ↗
                                        </a>
                                        <small class="d-block text-muted">
                                            <a href="{{ route('config.classes.profile', $assign->class_id) }}" class="text-muted text-decoration-none hover-primary">
                                                Batch Profile ↗
                                            </a>
                                        </small>
                                    </td>
                                    <td class="text-start">
                                        @if($assign->assigned_teacher)
                                            <span class="fw-semibold text-dark">👨‍🏫 {{ $assign->assigned_teacher }}</span>
                                        @else
                                            <span class="text-muted fst-italic">Unassigned</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold text-primary">{{ number_format($assign->credit_hours, 2) }}</td>
                                    <td class="text-muted">{{ number_format($assign->cw_weight, 1) }}%</td>
                                    <td class="text-muted">{{ number_format($assign->exam_weight, 1) }}%</td>
                                    <td class="fw-bold text-danger">{{ number_format($assign->passing_mark, 1) }}%</td>
                                    <td>
                                        @php
                                            $enrolledCount = $assign->enrolledStudents->count();
                                            $totalBatchStd = $assign->schoolClass ? $assign->schoolClass->students->count() : 0;
                                        @endphp
                                        <span class="badge {{ $enrolledCount < $totalBatchStd ? 'bg-warning-subtle text-dark border-warning' : 'bg-light text-dark' }} border">
                                            👥 {{ $enrolledCount }} / {{ $totalBatchStd }} enrolled
                                        </span>
                                        @if($enrolledCount < $totalBatchStd)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-block mt-1" style="font-size: 0.65rem;">
                                                Selective Roster
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('config.classes.gradebook', ['id' => $assign->class_id, 'assignment_id' => $assign->id]) }}" class="btn btn-outline-success py-0 px-2 fw-semibold" title="Open Gradebook with this Course Pre-selected">
                                                📖 Gradebook
                                            </a>
                                            <a href="{{ route('config.class_subjects.profile', $assign->id) }}" class="btn btn-outline-primary py-0 px-2 fw-semibold" title="View Course-Class Section Profile">
                                                👁️ Course
                                            </a>
                                            <form method="POST" action="{{ route('config.class_subjects.delete', $assign->id) }}" class="d-inline"
                                                  onsubmit="return confirm('Remove {{ addslashes($subject->subject_name) }} from {{ addslashes($assign->schoolClass->class_name) }}?');">
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
        <!-- TAB 2: APPLIED GRADING SYSTEM & BLUEPRINT                      -->
        <!-- ============================================================== -->
        <div class="tab-pane fade" id="tabGrading">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Active Grading System Architecture</h6>
                    <small class="text-muted">Hierarchical periods, categories, weights, and scoring scale applied to this subject.</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalApplyGradingSystem">
                        🎓 Change Grading System
                    </button>
                    @if($subject->grading_rule_id)
                        <a href="{{ route('config.grading.subjects.reset', $subject->id) }}" 
                           class="btn btn-sm btn-outline-secondary fw-semibold"
                           onclick="return confirm('Reset {{ addslashes($subject->subject_name) }} to school default grading template?')">
                            ↺ Reset to School Default
                        </a>
                    @endif
                </div>
            </div>

            @if($effectiveRule)
                <div class="card border border-primary border-opacity-25 rounded-3 p-4 mb-4 bg-white shadow-2xs">
                    <div class="d-flex flex-wrap justify-content-between align-items-center pb-3 border-bottom mb-3 gap-2">
                        <div>
                            <span class="badge {{ $subject->grading_rule_id ? 'bg-success' : 'bg-primary' }} px-2 py-1 small">
                                {{ $subject->grading_rule_id ? '✓ Custom Subject Override' : '● Inherited School Default' }}
                            </span>
                            <h5 class="fw-bold text-dark mt-2 mb-1">{{ $effectiveRule->rule_name }}</h5>
                            <p class="small text-muted mb-0">{{ $effectiveRule->description }}</p>
                        </div>
                        <div class="d-flex align-items-center gap-3 bg-light p-2 rounded-2 border">
                            <div class="text-center px-2">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">SCENARIO</small>
                                <strong class="small text-dark">{{ $effectiveRule->scenario_depth === '2_TIER_MAIN_CAT' ? '2-Tier' : ($effectiveRule->scenario_depth === '3_TIER_SUB_PERIOD_MAIN_CAT' ? '3-Tier' : '4-Tier') }}</strong>
                            </div>
                            <div class="text-center border-start ps-3 pe-2">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">PASS LIMIT</small>
                                <strong class="small text-danger">{{ number_format($subject->pass_limit ?? $effectiveRule->pass_limit, 1) }}%</strong>
                            </div>
                            <div class="text-center border-start ps-3 pe-2">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">SCALE TOTAL</small>
                                <strong class="small text-primary">{{ number_format($effectiveRule->total_grade, 0) }}</strong>
                            </div>
                            <div class="text-center border-start ps-3">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">CREDITS</small>
                                <strong class="small">{{ number_format($subject->default_credits, 2) }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Grading Periods & Categories Tree -->
                    <h6 class="fw-bold text-dark mb-3">Hierarchical Breakdown for this Subject:</h6>
                    <div class="row g-3">
                        @foreach($effectiveRule->periods as $pIdx => $period)
                            <div class="col-lg-6">
                                <div class="border rounded-3 p-3 bg-light bg-opacity-50 h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary">{{ $pIdx + 1 }}</span>
                                            <strong class="text-dark">{{ $period->period_name }}</strong>
                                        </div>
                                        <span class="badge bg-primary-subtle text-primary fw-bold">{{ $period->weight }}% Weight</span>
                                    </div>

                                    @if($period->subPeriods->isNotEmpty())
                                        <!-- Sub-Periods (Quarters) -->
                                        @foreach($period->subPeriods as $sp)
                                            <div class="bg-white rounded-2 p-2 border mb-2 shadow-2xs">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-semibold small text-dark">↳ {{ $sp->period_name }}</span>
                                                    <span class="badge bg-info-subtle text-info fw-bold" style="font-size: 0.7rem;">{{ $sp->weight }}% of period</span>
                                                </div>
                                                <div class="ps-2 border-start border-2 border-info border-opacity-50 mt-1">
                                                    @foreach($sp->categories as $sc)
                                                        <div class="d-flex justify-content-between align-items-center small py-1">
                                                            <span class="text-muted">{{ $sc->category_name }}</span>
                                                            <span class="fw-bold text-secondary">{{ $sc->weight }}%</span>
                                                        </div>
                                                        @if($sc->subCategories->isNotEmpty())
                                                            <div class="ps-3 border-start small text-muted">
                                                                @foreach($sc->subCategories as $subCat)
                                                                    <div class="d-flex justify-content-between py-0" style="font-size: 0.7rem;">
                                                                        <span>• {{ $subCat->category_name }}</span>
                                                                        <span>{{ $subCat->weight }}%</span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <!-- Direct Categories -->
                                        <div class="bg-white rounded-2 p-2 border">
                                            @foreach($period->categories as $cat)
                                                <div class="d-flex justify-content-between align-items-center small py-1">
                                                    <span class="fw-semibold text-dark">{{ $cat->category_name }}</span>
                                                    <span class="fw-bold text-primary">{{ $cat->weight }}%</span>
                                                </div>
                                                @if($cat->subCategories->isNotEmpty())
                                                    <div class="ps-3 border-start small text-muted">
                                                        @foreach($cat->subCategories as $subCat)
                                                            <div class="d-flex justify-content-between py-0" style="font-size: 0.7rem;">
                                                                <span>• {{ $subCat->category_name }}</span>
                                                                <span>{{ $subCat->weight }}%</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- ============================================================== -->
        <!-- TAB 3: ENROLLED STUDENT ROSTER                                -->
        <!-- ============================================================== -->
        <div class="tab-pane fade" id="tabStudents">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Enrolled Student Roster ({{ $totalStudentsCount }} Total)</h6>
                    <small class="text-muted">Students registered across active sections taking this subject.</small>
                </div>
                <a href="{{ route('reports.broadsheet') }}" class="btn btn-sm btn-outline-primary fw-semibold">
                    📋 View in Master Broadsheet
                </a>
            </div>

            @if($enrolledStudents->isEmpty())
                <div class="text-center py-5 bg-light rounded-3 border border-dashed my-3">
                    <span class="fs-1 d-block mb-2">👥</span>
                    <h5 class="fw-bold text-dark mb-1">No Students Currently Enrolled</h5>
                    <p class="text-muted small mb-0">Assign this subject to classes with active students to populate the roster.</p>
                </div>
            @else
                <div class="table-responsive bg-white rounded-3 border shadow-2xs">
                    <table class="table table-hover align-middle mb-0 small text-center">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start" style="width: 120px;">Admission No</th>
                                <th class="text-start">Student Full Name</th>
                                <th>Gender</th>
                                <th class="text-start">Level & Class</th>
                                <th>Latest Total Score</th>
                                <th>Letter Grade</th>
                                <th>Academic Standing</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($enrolledStudents as $std)
                                @php
                                    $rec = $std->gradeRecords->first();
                                    $passLimitVal = $subject->pass_limit ?? ($effectiveRule->pass_limit ?? 60.0);
                                @endphp
                                <tr>
                                    <td class="text-start font-monospace fw-bold text-primary">{{ $std->admission_number }}</td>
                                    <td class="text-start fw-bold text-dark fs-6">{{ $std->full_name }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $std->gender ?? 'N/A' }}</span>
                                    </td>
                                    <td class="text-start">
                                        <span class="badge bg-indigo-subtle text-dark border border-indigo-subtle fw-semibold">
                                            {{ $std->schoolClass->gradeLevel->grade_name ?? '' }} - {{ $std->schoolClass->class_name }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($rec)
                                            <strong class="fs-6 {{ $rec->total_score >= $passLimitVal ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($rec->total_score, 1) }}
                                            </strong>
                                            <span class="text-muted small">/ {{ number_format($subject->max_score, 0) }}</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-muted">Pending Ingestion</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($rec && $rec->letter_grade)
                                            <span class="badge {{ in_array($rec->letter_grade, ['A+', 'A', 'B']) ? 'bg-success' : (in_array($rec->letter_grade, ['C', 'D']) ? 'bg-warning text-dark' : 'bg-danger') }} px-2 py-1">
                                                {{ $rec->letter_grade }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($rec)
                                            @if($rec->total_score >= $passLimitVal)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">✓ Passed</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">⚠️ Remedial</span>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- ============================================================== -->
        <!-- TAB 4: FACULTY & TEACHING ASSIGNMENTS                          -->
        <!-- ============================================================== -->
        <div class="tab-pane fade" id="tabFaculty">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Assigned Faculty Members</h6>
                    <small class="text-muted">Instructors designated to teach {{ $subject->subject_name }} across sections.</small>
                </div>
            </div>

            @if($teachers->isEmpty())
                <div class="text-center py-5 bg-light rounded-3 border border-dashed my-3">
                    <span class="fs-1 d-block mb-2">👨‍🏫</span>
                    <h5 class="fw-bold text-dark mb-1">No Teachers Assigned Yet</h5>
                    <p class="text-muted small mb-0">Assign instructors when allocating this subject to classes or edit existing allocations.</p>
                </div>
            @else
                <div class="row g-3">
                    @foreach($teachers as $teacherName)
                        @php
                            $taughtClasses = $subject->assignments->where('assigned_teacher', $teacherName);
                        @endphp
                        <div class="col-md-6 col-lg-4">
                            <div class="card rounded-3 border border-slate-200 bg-white p-3 shadow-2xs h-100">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 fw-bold fs-5" style="width: 50px; height: 50px; display: inline-flex; align-items: center; justify-content: center;">
                                        {{ strtoupper(substr($teacherName, 0, 1)) }}
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">{{ $teacherName }}</h6>
                                        <small class="text-muted">Subject Teacher • {{ $subject->department ?? 'General' }}</small>
                                    </div>
                                </div>
                                <div class="border-top pt-2">
                                    <small class="text-muted d-block mb-1 fw-semibold">Sections Taught ({{ $taughtClasses->count() }}):</small>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($taughtClasses as $tc)
                                            <span class="badge bg-light text-dark border">
                                                {{ $tc->schoolClass->class_name }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<!-- ==================================================================== -->
<!-- MODALS                                                                -->
<!-- ==================================================================== -->

<!-- 1. MODAL: EDIT SUBJECT -->
<div class="modal fade" id="modalEditSubject" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.subjects.update', $subject->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title fw-bold">✏️ Edit {{ $subject->subject_name }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="subject_name" class="form-control" value="{{ $subject->subject_name }}" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Subject Code <span class="text-danger">*</span></label>
                            <input type="text" name="subject_code" class="form-control text-uppercase" value="{{ $subject->subject_code }}" required maxlength="20">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Department</label>
                            <input type="text" name="department" class="form-control" value="{{ $subject->department }}">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Default Credits</label>
                            <input type="number" step="0.5" name="default_credits" class="form-control" value="{{ $subject->default_credits }}" required min="0.5">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Maximum Score</label>
                            <input type="number" step="1" name="max_score" class="form-control" value="{{ $subject->max_score }}" required min="1">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label class="form-label small fw-bold text-dark">Grading System / Template</label>
                            <select name="grading_rule_id" class="form-select form-select-sm">
                                <option value="">-- Active School Default Template --</option>
                                @foreach($gradingTemplates as $gt)
                                    <option value="{{ $gt->id }}" {{ $subject->grading_rule_id == $gt->id ? 'selected' : '' }}>
                                        {{ $gt->rule_name }} (Scale: {{ $gt->total_grade }}, Pass: {{ $gt->pass_limit }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">Pass Limit</label>
                            <input type="number" step="0.5" name="pass_limit" class="form-control form-control-sm" value="{{ $subject->pass_limit }}" placeholder="e.g. 60.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. MODAL: ASSIGN TO LEVEL -->
<div class="modal fade" id="modalAssignToLevel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.class_subjects.assign_level') }}" method="POST">
                @csrf
                <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">🎓 Assign {{ $subject->subject_name }} to Level(s)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Select one or more educational levels. This subject will be automatically assigned to all classes and sections in those levels.
                    </p>
                    <div class="border rounded-2 p-3 bg-light mb-3" style="max-height: 200px; overflow-y: auto;">
                        @foreach($allGradeLevels as $lvl)
                            <div class="form-check mb-1">
                                <input type="checkbox" name="grade_ids[]" value="{{ $lvl->id }}" class="form-check-input" id="lvl-{{ $lvl->id }}"
                                       {{ in_array($lvl->id, $assignedLevels->pluck('id')->toArray()) ? 'checked' : '' }}>
                                <label class="form-check-label small fw-semibold text-dark" for="lvl-{{ $lvl->id }}">
                                    {{ $lvl->grade_name }} ({{ $lvl->classes->count() }} batches)
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Credit Hours</label>
                            <input type="number" step="0.5" name="credit_hours" class="form-control form-control-sm" value="{{ $subject->default_credits }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Passing Mark</label>
                            <input type="number" step="1" name="passing_mark" class="form-control form-control-sm" value="{{ $subject->pass_limit ?? ($effectiveRule->pass_limit ?? 60) }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Assign to Selected Levels</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. MODAL: ASSIGN TO SPECIFIC BATCH -->
<div class="modal fade" id="modalAssignToClass" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.class_subjects.assign') }}" method="POST">
                @csrf
                <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">🏫 Assign to Class Batch(es)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Select Target Class / Section <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-select" required>
                            <option value="">-- Choose Class Section --</option>
                            @foreach($allClasses as $cls)
                                <option value="{{ $cls->id }}">
                                    {{ $cls->gradeLevel->grade_name ?? 'Level' }} - {{ $cls->class_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Assigned Teacher (Optional)</label>
                        <input type="text" name="assigned_teacher" class="form-control" placeholder="e.g. Dr. Alan Turing">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">Credits</label>
                            <input type="number" step="0.5" name="credit_hours" class="form-control form-control-sm" value="{{ $subject->default_credits }}">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">CW %</label>
                            <input type="number" step="1" name="cw_weight" class="form-control form-control-sm" value="40">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">Exam %</label>
                            <input type="number" step="1" name="exam_weight" class="form-control form-control-sm" value="60">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">Assign to Batch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 4. MODAL: APPLY / CHANGE GRADING SYSTEM -->
<div class="modal fade" id="modalApplyGradingSystem" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.grading.apply_to_subjects') }}" method="POST">
                @csrf
                <input type="hidden" name="subject_ids[]" value="{{ $subject->id }}">
                <div class="modal-header bg-info text-dark">
                    <h5 class="modal-title fw-bold">🎓 Change Grading System for {{ $subject->subject_name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Grading System Template <span class="text-danger">*</span></label>
                        <select name="rule_id" class="form-select" required>
                            @foreach($gradingTemplates as $gt)
                                <option value="{{ $gt->id }}" {{ ($subject->grading_rule_id ?? $effectiveRule->id ?? 0) == $gt->id ? 'selected' : '' }}>
                                    {{ $gt->rule_name }} (Scale: {{ $gt->total_grade }}, Pass: {{ $gt->pass_limit }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Pass Limit Override (Optional)</label>
                        <div class="input-group">
                            <input type="number" step="0.5" name="pass_limit" class="form-control text-center" value="{{ $subject->pass_limit }}" placeholder="e.g. 60.00">
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">Leave empty to inherit template default.</small>
                    </div>
                    <div class="form-check p-2 bg-light rounded border">
                        <input class="form-check-input ms-1" type="checkbox" name="cascade_to_classes" value="1" id="cascadeClassesProfile" checked>
                        <label class="form-check-label small fw-semibold text-dark ms-2" for="cascadeClassesProfile">
                            Cascade update to all active batch allocations for this subject
                        </label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-dark fw-bold">✓ Apply Template</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
