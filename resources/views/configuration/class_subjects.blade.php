@extends('layouts.app')

@section('title', 'Master Subjects & Curriculum Studio')

@section('content')
<div class="card card-custom p-4 mb-4">
    <!-- Header & Top Actions -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom pb-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary px-2 py-1 small">Curriculum Studio</span>
                <h4 class="fw-bold mb-0">Master Subjects & Curriculum Allocation (Pillar 3)</h4>
            </div>
            <p class="text-muted small mb-0">
                School: <strong>{{ $activeSchool->school_name }}</strong> • Create master subjects and allocate them across school levels and class batches.
            </p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-outline-info fw-semibold btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#modalApplyGradingSystem">
                🎓 Apply Grading System
            </button>
            <button type="button" class="btn btn-outline-primary fw-semibold btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#modalAssignToLevel">
                🎓 Assign to Level(s)
            </button>
            <button type="button" class="btn btn-outline-success fw-semibold btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#modalAssignToClass">
                🏫 Assign to Batch(es)
            </button>
            <button type="button" class="btn btn-primary fw-bold px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCreateSubject">
                ＋ Create New Subject
            </button>
        </div>
    </div>

    <!-- Quick Stats Overview -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-xs">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Master Subjects</div>
                        <h3 class="fw-bold text-primary mb-0 mt-1">{{ $stats['total_subjects'] }}</h3>
                    </div>
                    <span class="fs-2">📚</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-xs">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">School Levels</div>
                        <h3 class="fw-bold text-indigo mb-0 mt-1">{{ $stats['total_levels'] }}</h3>
                    </div>
                    <span class="fs-2">🎓</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-xs">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Classes / Batches</div>
                        <h3 class="fw-bold text-success mb-0 mt-1">{{ $stats['total_classes'] }}</h3>
                    </div>
                    <span class="fs-2">🏫</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-xs">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Total Allocations</div>
                        <h3 class="fw-bold text-dark mb-0 mt-1">{{ $stats['total_assignments'] }}</h3>
                    </div>
                    <span class="fs-2">📋</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs fw-semibold small mb-4" id="subjectStudioTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabCatalog">
                📚 Master Subject Catalog ({{ $masterSubjects->count() }})
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link text-secondary" data-bs-toggle="tab" data-bs-target="#tabLevelMatrix">
                🎓 Level-Wise Curriculum Matrix ({{ $gradeLevels->count() }} Levels)
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link text-secondary" data-bs-toggle="tab" data-bs-target="#tabClassInspector">
                🏫 Batch / Class Marksheet Inspector
            </button>
        </li>
    </ul>

    <div class="tab-content" id="subjectStudioTabContent">
        <!-- ============================================================== -->
        <!-- TAB 1: MASTER SUBJECT CATALOG                                  -->
        <!-- ============================================================== -->
        <div class="tab-pane fade show active" id="tabCatalog">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0">Master Subject Catalog</h6>
                <button type="button" class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalCreateSubject">
                    ＋ Create Subject
                </button>
            </div>

            @if($masterSubjects->isEmpty())
                <div class="text-center py-5 bg-light rounded-3 border border-dashed my-3">
                    <span class="fs-1 d-block mb-2">📚</span>
                    <h5 class="fw-bold text-dark mb-1">No Subjects Created in Master Catalog Yet</h5>
                    <p class="text-muted small mb-4">
                        Add subjects such as Mathematics, English, Physics, or Biology and assign them to your school levels and batches.
                    </p>
                    <button type="button" class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalCreateSubject">
                        ＋ Create Your First Subject
                    </button>
                </div>
            @else
                <div class="table-responsive bg-white rounded-3 border shadow-xs">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 100px;">Code</th>
                                <th>Subject Name</th>
                                <th>Department</th>
                                <th>Grading System</th>
                                <th class="text-center">Credits</th>
                                <th class="text-center">Max Score</th>
                                <th class="text-center">Allocated Batches</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($masterSubjects as $subj)
                                <tr>
                                    <td>
                                        <span class="badge bg-primary px-2 py-1 fw-bold">{{ $subj->subject_code }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('config.subjects.profile', $subj->id) }}" class="text-decoration-none fw-bold text-dark fs-6 hover-primary d-inline-flex align-items-center gap-1">
                                            {{ $subj->subject_name }}
                                            <span class="text-primary small" style="font-size: 0.75rem;">↗</span>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border fw-semibold">
                                            {{ $subj->department ?? 'General' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($subj->gradingRule)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">
                                                ✓ {{ $subj->gradingRule->rule_name }}
                                            </span>
                                            <div class="small text-muted" style="font-size: 0.7rem;">
                                                Pass: {{ $subj->pass_limit ?? $subj->gradingRule->pass_limit }} • Total: {{ $subj->gradingRule->total_grade }}
                                            </div>
                                        @else
                                            <span class="badge bg-secondary-subtle text-muted">
                                                ● School Default
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold text-primary">{{ number_format($subj->default_credits, 2) }}</td>
                                    <td class="text-center fw-bold">{{ number_format($subj->max_score, 0) }}</td>
                                    <td class="text-center">
                                        @if($subj->assignments_count > 0)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">
                                                ✓ Assigned to {{ $subj->assignments_count }} Class Batch{{ $subj->assignments_count === 1 ? '' : 'es' }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-muted">Unassigned</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('config.subjects.profile', $subj->id) }}" class="btn btn-outline-dark py-0 px-2 fw-semibold" title="View Subject Profile">
                                                👁️ Profile
                                            </a>
                                            <button type="button" class="btn btn-outline-info py-0 px-2 fw-semibold" title="Apply Grading System"
                                                    onclick="openApplyGradingModalForSubject({{ $subj->id }}, '{{ addslashes($subj->subject_name) }}', {{ $subj->grading_rule_id ?? 0 }})">
                                                🎓 Grading
                                            </button>
                                            <button type="button" class="btn btn-outline-primary py-0 px-2 fw-semibold" title="Assign to Level"
                                                    onclick="openAssignToLevelModal({{ $subj->id }}, '{{ addslashes($subj->subject_name) }}', {{ $subj->default_credits }})">
                                                🎓 Level
                                            </button>
                                            <button type="button" class="btn btn-outline-success py-0 px-2 fw-semibold" title="Assign to Class Batch"
                                                    onclick="openAssignToClassModal({{ $subj->id }}, '{{ addslashes($subj->subject_name) }}', {{ $subj->default_credits }})">
                                                🏫 Batch
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary py-0 px-2" title="Edit Subject"
                                                    onclick="openEditSubjectModal({{ $subj->id }}, '{{ addslashes($subj->subject_name) }}', '{{ $subj->subject_code }}', '{{ addslashes($subj->department ?? 'General') }}', {{ $subj->default_credits }}, {{ $subj->max_score }}, {{ $subj->grading_rule_id ?? 'null' }}, {{ $subj->pass_limit ?? 'null' }})">
                                                ✏️
                                            </button>
                                            <button type="button" class="btn btn-outline-danger py-0 px-2" title="Delete Subject"
                                                    onclick="confirmDeleteSubject({{ $subj->id }}, '{{ addslashes($subj->subject_name) }}')">
                                                🗑️
                                            </button>
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
        <!-- TAB 2: LEVEL-WISE CURRICULUM MATRIX                             -->
        <!-- ============================================================== -->
        <div class="tab-pane fade" id="tabLevelMatrix">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold text-dark mb-0">Curriculum Matrix: Allocated Subjects by School Level</h6>
                    <small class="text-muted">Shows subjects assigned across all batches in each level.</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalAssignToLevel">
                    🎓 Assign Subject to Level(s)
                </button>
            </div>

            @if($gradeLevels->isEmpty())
                <div class="text-center py-5 bg-light rounded-3 border border-dashed my-3">
                    <p class="text-muted mb-2">No school levels configured yet.</p>
                    <a href="{{ route('config.levels') }}" class="btn btn-primary btn-sm fw-bold">Go to School Levels Studio →</a>
                </div>
            @else
                <div class="row g-3">
                    @foreach($gradeLevels as $level)
                        @php
                            // Gather unique subjects across all classes in this level
                            $levelSubjects = $level->classes->flatMap->subjectAssignments->map->subject->unique('id');
                        @endphp
                        <div class="col-lg-6">
                            <div class="card h-100 border border-slate-200 rounded-3 shadow-xs">
                                <div class="card-header bg-light p-3 d-flex justify-content-between align-items-center border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary fw-bold">{{ $level->grade_code }}</span>
                                        <h6 class="fw-bold mb-0 text-dark">{{ $level->grade_name }}</h6>
                                        <span class="badge bg-white text-muted border small">
                                            {{ $level->classes->count() }} Batch{{ $level->classes->count() === 1 ? '' : 'es' }}
                                        </span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary py-0 px-2 fw-semibold"
                                            onclick="openAssignLevelSpecific({{ $level->id }}, '{{ addslashes($level->grade_name) }}')">
                                        ＋ Assign Subject
                                    </button>
                                </div>
                                <div class="card-body p-3">
                                    <div class="small fw-semibold text-muted mb-2">Active Batches in Level:</div>
                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        @forelse($level->classes as $cls)
                                            <a href="{{ route('config.classes.profile', $cls->id) }}" class="badge bg-light text-dark border text-decoration-none hover-shadow" title="View {{ $cls->class_name }} Profile">
                                                {{ $cls->class_name }} ({{ $cls->students->count() }} std) ↗
                                            </a>
                                        @empty
                                            <span class="text-muted small">No batches created in this level.</span>
                                        @endforelse
                                    </div>

                                    <div class="small fw-semibold text-muted mb-2">Subjects Assigned (Curriculum):</div>
                                    @if($levelSubjects->isEmpty())
                                        <div class="p-2 text-center bg-light rounded text-muted small border border-dashed">
                                            No subjects assigned to this level yet. 
                                            <a href="javascript:void(0)" class="fw-bold text-primary" onclick="openAssignLevelSpecific({{ $level->id }}, '{{ addslashes($level->grade_name) }}')">
                                                ＋ Assign Subject
                                            </a>
                                        </div>
                                    @else
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($levelSubjects as $lSubj)
                                                <a href="{{ route('config.subjects.profile', $lSubj->id) }}" class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2 text-decoration-none hover-shadow" title="View {{ $lSubj->subject_name }} Profile">
                                                    {{ $lSubj->subject_name }} ({{ $lSubj->subject_code }}) ↗
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- ============================================================== -->
        <!-- TAB 3: BATCH / CLASS INSPECTOR                                 -->
        <!-- ============================================================== -->
        <div class="tab-pane fade" id="tabClassInspector">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 p-3 bg-light rounded-3 border">
                <div>
                    <h6 class="fw-bold text-dark mb-0">Select Target Batch / Class to Inspect</h6>
                    <small class="text-muted">View and modify exact subject allocations, weights, and teachers for this section.</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0 fw-semibold small text-muted">Batch:</label>
                    <select class="form-select form-select-sm fw-bold" style="width: 250px;" onchange="window.location.search = '?class_id=' + this.value">
                        @foreach($allClasses as $c)
                            <option value="{{ $c->id }}" {{ $selectedClass && $selectedClass->id === $c->id ? 'selected' : '' }}>
                                {{ $c->class_name }} ({{ $c->gradeLevel->grade_name ?? 'Level' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if($selectedClass)
                <div class="row g-4">
                    <!-- LEFT: Quick Assign from Catalog -->
                    <div class="col-lg-4">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="fw-bold text-uppercase text-muted mb-0" style="font-size: 0.8rem;">Available Catalog</h6>
                                    <small class="text-muted">1-Click assign to {{ $selectedClass->class_name }}</small>
                                </div>
                            </div>

                            <div class="list-group gap-2">
                                @forelse($masterSubjects as $subj)
                                    @php
                                        $alreadyAssigned = $selectedClass->subjectAssignments->contains('subject_id', $subj->id);
                                    @endphp
                                    <div class="list-group-item rounded-3 border d-flex justify-content-between align-items-center p-2.5">
                                        <div>
                                            <a href="{{ route('config.subjects.profile', $subj->id) }}" class="fw-bold small text-dark text-decoration-none hover-primary">
                                                {{ $subj->subject_name }} ↗
                                            </a>
                                            <small class="text-muted font-monospace d-block">{{ $subj->subject_code }} • {{ $subj->default_credits }} Cr</small>
                                        </div>
                                        @if(!$alreadyAssigned)
                                            <form method="POST" action="{{ route('config.class_subjects.assign') }}">
                                                @csrf
                                                <input type="hidden" name="class_id" value="{{ $selectedClass->id }}">
                                                <input type="hidden" name="subject_id" value="{{ $subj->id }}">
                                                <input type="hidden" name="credit_hours" value="{{ $subj->default_credits }}">
                                                <input type="hidden" name="cw_weight" value="40">
                                                <input type="hidden" name="exam_weight" value="60">
                                                <button type="submit" class="btn btn-outline-primary btn-sm py-1 px-2 fw-bold">+ Add</button>
                                            </form>
                                        @else
                                            <span class="badge bg-success-subtle text-success border border-success-subtle py-1">✓ Active</span>
                                        @endif
                                    </div>
                                @empty
                                    <div class="text-center text-muted small py-3">No subjects created yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Assigned Subjects for Selected Batch -->
                    <div class="col-lg-8">
                        <div class="p-3 bg-white rounded-3 border h-100 shadow-xs">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <div>
                                    <h6 class="fw-bold text-uppercase text-primary mb-0" style="font-size: 0.8rem;">
                                        Assigned Subjects for: 
                                        <a href="{{ route('config.classes.profile', $selectedClass->id) }}" class="text-decoration-none text-primary fw-bold" title="Open Class Profile">
                                            {{ $selectedClass->class_name }} ↗
                                        </a>
                                    </h6>
                                    <small class="text-muted">Defines columns on report card and Excel marksheet</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="{{ route('config.classes.profile', $selectedClass->id) }}" class="btn btn-sm btn-outline-primary py-0 px-2 fw-semibold">
                                        🏫 View {{ $selectedClass->class_name }} Profile
                                    </a>
                                    <span class="badge bg-primary-subtle text-primary fw-bold">
                                        {{ $selectedClass->subjectAssignments->count() }} Subjects
                                    </span>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle small text-center mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 45px;">#</th>
                                            <th class="text-start">Subject</th>
                                            <th>Credits</th>
                                            <th>CW / Exam</th>
                                            <th>Passing</th>
                                            <th>Teacher</th>
                                            <th style="width: 50px;">Remove</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($selectedClass->subjectAssignments as $assign)
                                            <tr>
                                                <td class="fw-bold text-primary">{{ $assign->display_order }}</td>
                                                <td class="text-start">
                                                    <div>
                                                        <a href="{{ route('config.subjects.profile', $assign->subject->id) }}" class="fw-bold text-dark text-decoration-none hover-primary">
                                                            {{ $assign->subject->subject_name }} ↗
                                                        </a>
                                                    </div>
                                                    <small class="text-muted font-monospace">{{ $assign->subject->subject_code }}</small>
                                                </td>
                                                <td class="fw-bold">{{ $assign->credit_hours }}</td>
                                                <td class="text-muted">{{ $assign->cw_weight }}% / {{ $assign->exam_weight }}%</td>
                                                <td class="fw-semibold text-danger">{{ $assign->passing_mark ?? 60 }}</td>
                                                <td>{{ $assign->assigned_teacher ?? 'Unassigned' }}</td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="{{ route('config.class_subjects.profile', $assign->id) }}" class="btn btn-sm btn-outline-primary py-0 px-1" title="View Course-Class Profile">
                                                            👁️
                                                        </a>
                                                        <form method="POST" action="{{ route('config.class_subjects.delete', $assign->id) }}" class="d-inline" onsubmit="return confirm('Remove {{ addslashes($assign->subject->subject_name) }} from this batch?');">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Unassign">
                                                                🗑️
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-4">
                                                    No subjects assigned to this batch yet.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- ==================================================================== -->
<!-- MODALS                                                                -->
<!-- ==================================================================== -->

<!-- 1. MODAL: CREATE NEW MASTER SUBJECT -->
<div class="modal fade" id="modalCreateSubject" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.subjects.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">＋ Create Master Subject</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="subject_name" class="form-control" placeholder="e.g. Mathematics, English Literature, Biology, Physics" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Subject Code <span class="text-danger">*</span></label>
                            <input type="text" name="subject_code" class="form-control text-uppercase" placeholder="e.g. MATH, ENG, BIO" required maxlength="20">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Department</label>
                            <input type="text" name="department" class="form-control" placeholder="e.g. Sciences, Languages" value="General">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Default Credits</label>
                            <input type="number" step="0.5" name="default_credits" class="form-control" value="1.0" min="0.5" max="10" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Maximum Score</label>
                            <input type="number" step="1" name="max_score" class="form-control" value="100" min="1" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Grading System / Template</label>
                        <select name="grading_rule_id" class="form-select form-select-sm">
                            <option value="">-- Active School Default Template --</option>
                            @foreach($gradingTemplates as $gt)
                                <option value="{{ $gt->id }}">
                                    {{ $gt->rule_name }} (Scale: {{ $gt->total_grade }}, Pass: {{ $gt->pass_limit }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted" style="font-size: 0.7rem;">Determines calculation hierarchy, passing marks, and scale.</small>
                    </div>

                    <hr class="my-3">
                    <label class="form-label small fw-bold text-dark mb-1">Also allocate immediately to these Levels:</label>
                    <div class="p-2 bg-light rounded border" style="max-height: 120px; overflow-y: auto;">
                        @forelse($gradeLevels as $gl)
                            <div class="form-check small">
                                <input class="form-check-input" type="checkbox" name="assign_grade_ids[]" value="{{ $gl->id }}" id="createSubjLevel{{ $gl->id }}">
                                <label class="form-check-label" for="createSubjLevel{{ $gl->id }}">
                                    {{ $gl->grade_name }} ({{ $gl->classes->count() }} batches)
                                </label>
                            </div>
                        @empty
                            <small class="text-muted">No levels created yet.</small>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Create Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. MODAL: ASSIGN SUBJECT TO LEVEL(S) -->
<div class="modal fade" id="modalAssignToLevel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.class_subjects.assign_level') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">🎓 Assign Subject to School Level(s)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Allocating a subject to a level automatically assigns it to <strong>all batches/sections</strong> in that level.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Select Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" id="assignLevelSubjectSelect" class="form-select" required>
                            @foreach($masterSubjects as $s)
                                <option value="{{ $s->id }}" data-credits="{{ $s->default_credits }}">
                                    {{ $s->subject_name }} ({{ $s->subject_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-dark mb-0">Select Target Level(s) <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-link btn-xs text-primary p-0 text-decoration-none small" onclick="toggleCheckboxes('levelCheckItem')">Select / Deselect All</button>
                        </div>
                        <div class="p-2.5 bg-light rounded-3 border" style="max-height: 160px; overflow-y: auto;">
                            @forelse($gradeLevels as $gl)
                                <div class="form-check small mb-1">
                                    <input class="form-check-input levelCheckItem" type="checkbox" name="grade_ids[]" value="{{ $gl->id }}" id="assignGl{{ $gl->id }}" checked>
                                    <label class="form-check-label fw-semibold text-dark" for="assignGl{{ $gl->id }}">
                                        {{ $gl->grade_name }} <span class="text-muted">({{ $gl->classes->count() }} batches)</span>
                                    </label>
                                </div>
                            @empty
                                <div class="text-muted small">No levels found.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">Credit Hours</label>
                            <input type="number" step="0.5" name="credit_hours" class="form-control form-control-sm" value="1.0" min="0.5">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">CW Weight %</label>
                            <input type="number" step="1" name="cw_weight" class="form-control form-control-sm" value="40" min="0" max="100">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">Exam Weight %</label>
                            <input type="number" step="1" name="exam_weight" class="form-control form-control-sm" value="60" min="0" max="100">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold text-dark">Assigned Teacher (Optional)</label>
                        <input type="text" name="assigned_teacher" class="form-control form-control-sm" placeholder="e.g. Department Head, Dr. Evans">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Allocate to Selected Levels</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. MODAL: ASSIGN SUBJECT TO SPECIFIC BATCH(ES) -->
<div class="modal fade" id="modalAssignToClass" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.class_subjects.assign') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">🏫 Assign Subject to Specific Batch(es)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Select Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" id="assignBatchSubjectSelect" class="form-select" required>
                            @foreach($masterSubjects as $s)
                                <option value="{{ $s->id }}">{{ $s->subject_name }} ({{ $s->subject_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-dark mb-0">Select Target Batch(es) <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-link btn-xs text-primary p-0 text-decoration-none small" onclick="toggleCheckboxes('classCheckItem')">Select / Deselect All</button>
                        </div>
                        <div class="p-2.5 bg-light rounded-3 border" style="max-height: 180px; overflow-y: auto;">
                            @forelse($gradeLevels as $gl)
                                <div class="small fw-bold text-muted text-uppercase mt-1 mb-1" style="font-size: 0.7rem;">
                                    {{ $gl->grade_name }}:
                                </div>
                                @foreach($gl->classes as $c)
                                    <div class="form-check small ps-4 mb-1">
                                        <input class="form-check-input classCheckItem" type="checkbox" name="class_ids[]" value="{{ $c->id }}" id="assignCls{{ $c->id }}">
                                        <label class="form-check-label fw-semibold" for="assignCls{{ $c->id }}">
                                            {{ $c->class_name }} <span class="text-muted">({{ $c->students->count() }} std)</span>
                                        </label>
                                    </div>
                                @endforeach
                            @empty
                                <div class="text-muted small">No classes/batches found.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">Credits</label>
                            <input type="number" step="0.5" name="credit_hours" class="form-control form-control-sm" value="1.0" min="0.5">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">CW %</label>
                            <input type="number" step="1" name="cw_weight" class="form-control form-control-sm" value="40" min="0" max="100">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">Exam %</label>
                            <input type="number" step="1" name="exam_weight" class="form-control form-control-sm" value="60" min="0" max="100">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold text-dark">Assigned Teacher</label>
                        <input type="text" name="assigned_teacher" class="form-control form-control-sm" placeholder="e.g. Mrs. Claire Adams">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">Assign to Selected Batches</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 4. MODAL: EDIT SUBJECT -->
<div class="modal fade" id="modalEditSubject" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="editSubjectForm" method="POST" action="">
                @csrf
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title fw-bold">✏️ Edit Master Subject</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="subject_name" id="editSubjectName" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Subject Code <span class="text-danger">*</span></label>
                            <input type="text" name="subject_code" id="editSubjectCode" class="form-control text-uppercase" required maxlength="20">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Department</label>
                            <input type="text" name="department" id="editSubjectDepartment" class="form-control">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Default Credits</label>
                            <input type="number" step="0.5" name="default_credits" id="editSubjectCredits" class="form-control" required min="0.5">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Maximum Score</label>
                            <input type="number" step="1" name="max_score" id="editSubjectMaxScore" class="form-control" required min="1">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label class="form-label small fw-bold text-dark">Grading System / Template</label>
                            <select name="grading_rule_id" id="editSubjectGradingRuleId" class="form-select form-select-sm">
                                <option value="">-- Active School Default Template --</option>
                                @foreach($gradingTemplates as $gt)
                                    <option value="{{ $gt->id }}">
                                        {{ $gt->rule_name }} (Scale: {{ $gt->total_grade }}, Pass: {{ $gt->pass_limit }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">Pass Limit</label>
                            <input type="number" step="0.5" name="pass_limit" id="editSubjectPassLimit" class="form-control form-control-sm" placeholder="e.g. 60.00">
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

<!-- 5. REUSABLE MODAL: CONFIRM DELETION -->
<div class="modal fade" id="modalConfirmDeleteSubject" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="deleteSubjectForm" method="POST" action="">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">⚠️ Confirm Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-2">Are you sure you want to permanently delete this subject from the master catalog?</p>
                    <div class="p-3 bg-danger-subtle text-danger border border-danger-subtle rounded-3 mb-3">
                        <strong id="deleteSubjectItemName" class="fs-6 d-block">-</strong>
                        <span class="small">Master Subject Catalog</span>
                    </div>
                    <p class="small text-muted mb-0">
                        ⚡ <strong>Warning:</strong> Deleting this subject will also unassign it from all levels and batches.
                    </p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold">Yes, Delete Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 6. MODAL: APPLY GRADING SYSTEM TO SUBJECTS -->
<div class="modal fade" id="modalApplyGradingSystem" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.grading.apply_to_subjects') }}" method="POST">
                @csrf
                <div class="modal-header bg-info text-dark">
                    <h5 class="modal-title fw-bold">🎓 Apply Grading System into Subjects</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Bind a grading system template to your subjects. This links the hierarchical calculation periods, category weights, scale, and pass threshold.
                    </p>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-dark">Grading System Template <span class="text-danger">*</span></label>
                            <select name="rule_id" class="form-select" id="applyGradingRuleSelect" required>
                                @foreach($gradingTemplates as $gt)
                                    <option value="{{ $gt->id }}">
                                        {{ $gt->rule_name }} (Scale: {{ $gt->total_grade }}, Pass: {{ $gt->pass_limit }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Pass Limit Override</label>
                            <div class="input-group">
                                <input type="number" step="0.5" name="pass_limit" id="applyPassLimitClassSubj" class="form-control text-center" placeholder="e.g. 60.00">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted" style="font-size: 0.7rem;">Leave empty to inherit template pass limit.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark d-block">Target Subjects:</label>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="all_subjects" id="targetAllClassSubj" value="1" checked onchange="toggleSubjectListClassSubj(false)">
                                <label class="form-check-label small fw-bold text-dark" for="targetAllClassSubj">
                                    All Master Subjects ({{ $masterSubjects->count() }} Subjects)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="all_subjects" id="targetSpecificClassSubj" value="0" onchange="toggleSubjectListClassSubj(true)">
                                <label class="form-check-label small fw-bold text-dark" for="targetSpecificClassSubj">
                                    Select Specific Subjects
                                </label>
                            </div>
                        </div>

                        <div id="subjectCheckboxesClassSubjContainer" class="border rounded-2 p-3 bg-light d-none" style="max-height: 220px; overflow-y: auto;">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                <span class="small text-muted">Select subjects:</span>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="selectAllClassSubj(true)">All</button>
                                    <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="selectAllClassSubj(false)">None</button>
                                </div>
                            </div>
                            <div class="row g-2">
                                @foreach($masterSubjects as $s)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" name="subject_ids[]" value="{{ $s->id }}" class="form-check-input class-subj-apply-cb" id="class-subj-cb-{{ $s->id }}">
                                            <label class="form-check-label small text-dark" for="class-subj-cb-{{ $s->id }}">
                                                <strong>{{ $s->subject_code }}</strong> - {{ $s->subject_name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="form-check p-2 bg-light rounded border">
                        <input class="form-check-input ms-1" type="checkbox" name="cascade_to_classes" value="1" id="cascadeToClassesSubj" checked>
                        <label class="form-check-label small fw-semibold text-dark ms-2" for="cascadeToClassesSubj">
                            ✓ Also cascade this grading system to all existing Class/Batch allocations for these subjects
                        </label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-dark fw-bold">✓ Apply Grading System</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openAssignToLevelModal(subjectId, subjectName, defaultCredits) {
        const select = document.getElementById('assignLevelSubjectSelect');
        if (select) select.value = subjectId;
        const modal = new bootstrap.Modal(document.getElementById('modalAssignToLevel'));
        modal.show();
    }

    function openAssignToClassModal(subjectId, subjectName, defaultCredits) {
        const select = document.getElementById('assignBatchSubjectSelect');
        if (select) select.value = subjectId;
        const modal = new bootstrap.Modal(document.getElementById('modalAssignToClass'));
        modal.show();
    }

    function openAssignLevelSpecific(levelId, levelName) {
        document.querySelectorAll('.levelCheckItem').forEach(cb => {
            cb.checked = (cb.value == levelId);
        });
        const modal = new bootstrap.Modal(document.getElementById('modalAssignToLevel'));
        modal.show();
    }

    function openEditSubjectModal(id, name, code, dept, credits, max, gradingRuleId, passLimit) {
        document.getElementById('editSubjectForm').action = `/configuration/subjects/${id}/update`;
        document.getElementById('editSubjectName').value = name;
        document.getElementById('editSubjectCode').value = code;
        document.getElementById('editSubjectDepartment').value = dept;
        document.getElementById('editSubjectCredits').value = credits;
        document.getElementById('editSubjectMaxScore').value = max;
        const ruleSelect = document.getElementById('editSubjectGradingRuleId');
        if (ruleSelect) ruleSelect.value = (gradingRuleId !== null && gradingRuleId !== undefined) ? gradingRuleId : '';
        const passInput = document.getElementById('editSubjectPassLimit');
        if (passInput) passInput.value = (passLimit !== null && passLimit !== undefined) ? passLimit : '';
        const modal = new bootstrap.Modal(document.getElementById('modalEditSubject'));
        modal.show();
    }

    function openApplyGradingModalForSubject(subjectId, subjectName, currentRuleId) {
        const radioSpecific = document.getElementById('targetSpecificClassSubj');
        if (radioSpecific) radioSpecific.checked = true;
        toggleSubjectListClassSubj(true);
        selectAllClassSubj(false);
        const targetCb = document.getElementById(`class-subj-cb-${subjectId}`);
        if (targetCb) targetCb.checked = true;
        const ruleSelect = document.getElementById('applyGradingRuleSelect');
        if (ruleSelect && currentRuleId) ruleSelect.value = currentRuleId;
        const modal = new bootstrap.Modal(document.getElementById('modalApplyGradingSystem'));
        modal.show();
    }

    function toggleSubjectListClassSubj(show) {
        const container = document.getElementById('subjectCheckboxesClassSubjContainer');
        if (container) {
            if (show) {
                container.classList.remove('d-none');
            } else {
                container.classList.add('d-none');
            }
        }
    }

    function selectAllClassSubj(checked) {
        document.querySelectorAll('.class-subj-apply-cb').forEach(cb => cb.checked = checked);
    }

    function confirmDeleteSubject(id, name) {
        document.getElementById('deleteSubjectForm').action = `/configuration/subjects/${id}/delete`;
        document.getElementById('deleteSubjectItemName').textContent = name;
        const modal = new bootstrap.Modal(document.getElementById('modalConfirmDeleteSubject'));
        modal.show();
    }

    function toggleCheckboxes(className) {
        const items = document.querySelectorAll('.' + className);
        if (items.length === 0) return;
        const anyUnchecked = Array.from(items).some(i => !i.checked);
        items.forEach(i => i.checked = anyUnchecked);
    }
</script>
@endsection
