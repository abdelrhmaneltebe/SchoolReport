@extends('layouts.app')

@section('title', 'School Levels & Educational Structure')

@section('content')
<div class="card card-custom p-4 mb-4">
    <!-- Header & Actions -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom pb-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary text-white px-2 py-1 small">Academic Structure</span>
                <h4 class="fw-bold mb-0">School Levels, Stages & Classes Studio</h4>
            </div>
            <p class="text-muted small mb-0">
                School: <strong>{{ $activeSchool->school_name }}</strong> • Configure educational stages, grade levels, and classes for this school.
            </p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-outline-secondary fw-semibold btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#modalCreateDivision">
                🏛️ + New Stage / Division
            </button>
            <button type="button" class="btn btn-outline-primary fw-semibold btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#modalBatchGenerate">
                ⚡ Batch Generate Levels
            </button>
            <button type="button" class="btn btn-primary fw-bold px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCreateLevel">
                ＋ Create New Level
            </button>
        </div>
    </div>

    <!-- Quick Stats Overview -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-xs">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Total School Levels</div>
                        <h3 class="fw-bold text-primary mb-0 mt-1">{{ $stats['total_levels'] }}</h3>
                    </div>
                    <span class="fs-2">🎓</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-xs">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Classes / Sections</div>
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
                        <div class="text-muted small fw-semibold">Enrolled Students</div>
                        <h3 class="fw-bold text-dark mb-0 mt-1">{{ $stats['total_students'] }}</h3>
                    </div>
                    <span class="fs-2">👥</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 rounded-3 border border-slate-200 bg-white p-3 shadow-xs">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Educational Stages</div>
                        <h3 class="fw-bold text-indigo mb-0 mt-1">{{ $stats['total_divisions'] }}</h3>
                    </div>
                    <span class="fs-2">🏛️</span>
                </div>
            </div>
        </div>
    </div>

    <!-- EMPTY STATE -->
    @if($allLevels->isEmpty())
        <div class="text-center py-5 border border-dashed rounded-3 bg-light my-3">
            <span class="fs-1 d-block mb-2">🎓</span>
            <h5 class="fw-bold text-dark mb-1">No School Levels Configured Yet</h5>
            <p class="text-muted small mb-4">
                Define the academic structure for {{ $activeSchool->school_name }} by creating individual levels or using the 1-click batch generator.
            </p>
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalCreateLevel">
                    ＋ Create First Level
                </button>
                <button type="button" class="btn btn-outline-primary fw-semibold px-3" data-bs-toggle="modal" data-bs-target="#modalBatchGenerate">
                    ⚡ Batch Generate Levels (1-12)
                </button>
            </div>
        </div>
    @endif

    <!-- STRUCTURE GROUPED BY EDUCATIONAL STAGE / DIVISION -->
    @foreach($divisions as $div)
        <div class="border rounded-3 p-3 mb-4 bg-white shadow-xs">
            <!-- Stage / Division Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center pb-2 mb-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-dark text-white px-2 py-1 small" style="color: #ffffff !important; background-color: #0f172a !important;">Stage</span>
                    <h5 class="fw-bold mb-0 text-dark">{{ $div->division_name }}</h5>
                    <span class="badge bg-light text-secondary border small">
                        {{ $div->gradeLevels->count() }} Level{{ $div->gradeLevels->count() === 1 ? '' : 's' }}
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 fw-semibold" 
                            onclick="openCreateLevelWithDivision({{ $div->id }}, '{{ addslashes($div->division_name) }}')">
                        ＋ Add Level to this Stage
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2" title="Remove Stage"
                            onclick="confirmDelete('division', {{ $div->id }}, '{{ addslashes($div->division_name) }}', 'Educational Stage')">
                        🗑️
                    </button>
                </div>
            </div>

            <!-- Levels Grid in this Stage -->
            @if($div->gradeLevels->isEmpty())
                <div class="text-center py-3 bg-light rounded-2 border border-dashed text-muted small">
                    No levels in this stage yet. 
                    <a href="javascript:void(0)" class="fw-bold text-primary" onclick="openCreateLevelWithDivision({{ $div->id }}, '{{ addslashes($div->division_name) }}')">
                        ＋ Add a Level now
                    </a>
                </div>
            @else
                <div class="row g-3">
                    @foreach($div->gradeLevels as $level)
                        <div class="col-lg-6">
                            <div class="card h-100 border border-slate-200 rounded-3 shadow-xs">
                                <!-- Level Header -->
                                <div class="card-header bg-light p-3 d-flex justify-content-between align-items-center border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary text-white px-2 py-1 fw-bold">{{ $level->grade_code }}</span>
                                        <h6 class="fw-bold mb-0 text-dark">{{ $level->grade_name }}</h6>
                                        <span class="badge bg-white text-muted border small" style="font-size: 0.7rem;">
                                            Order #{{ $level->display_order }}
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-success py-0 px-2 fw-semibold" title="Add Class / Section"
                                                onclick="openCreateClassModal({{ $level->id }}, '{{ addslashes($level->grade_name) }}')">
                                            ＋ Class
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Edit Level"
                                                onclick="openEditLevelModal({{ $level->id }}, '{{ addslashes($level->grade_name) }}', '{{ $level->grade_code }}', {{ $level->division_id ?? 'null' }}, {{ $level->display_order }})">
                                            ✏️
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" title="Delete Level"
                                                onclick="confirmDelete('level', {{ $level->id }}, '{{ addslashes($level->grade_name) }}', 'School Level')">
                                            🗑️
                                        </button>
                                    </div>
                                </div>

                                <!-- Level Classes Body -->
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="small fw-semibold text-muted">Classes / Sections in this Level:</span>
                                        <span class="badge bg-secondary-subtle text-secondary small">
                                            {{ $level->classes->count() }} Section{{ $level->classes->count() === 1 ? '' : 's' }} • 
                                            {{ $level->classes->sum(fn($c) => $c->students->count()) }} Students
                                        </span>
                                    </div>

                                    @if($level->classes->isEmpty())
                                        <div class="p-2 text-center bg-light rounded text-muted small border border-dashed">
                                            No classes assigned. 
                                            <a href="javascript:void(0)" class="fw-bold text-success" onclick="openCreateClassModal({{ $level->id }}, '{{ addslashes($level->grade_name) }}')">
                                                ＋ Add Section (e.g. {{ $level->grade_code }}-A)
                                            </a>
                                        </div>
                                    @else
                                        <div class="list-group list-group-flush border rounded-2">
                                            @foreach($level->classes as $cls)
                                                <div class="list-group-item d-flex justify-content-between align-items-center p-2 small">
                                                    <div>
                                                        <a href="{{ route('config.classes.profile', $cls->id) }}" class="fw-bold text-dark text-decoration-none hover-primary">
                                                            {{ $cls->class_name }} ↗
                                                        </a>
                                                        @if($cls->homeroom_teacher)
                                                            <span class="text-muted ms-2">• Homeroom: {{ $cls->homeroom_teacher }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-light text-dark border">
                                                            {{ $cls->students->count() }}/{{ $cls->max_capacity }} Students
                                                        </span>
                                                        <a href="{{ route('config.classes.profile', $cls->id) }}" class="btn btn-sm btn-outline-primary py-0 px-2 fw-semibold" title="View Class Profile">
                                                            👁️ Profile
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" title="Delete Class"
                                                                onclick="confirmDelete('class', {{ $cls->id }}, '{{ addslashes($cls->class_name) }}', 'Class Section')">
                                                            🗑️
                                                        </button>
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
            @endif
        </div>
    @endforeach

    <!-- UNASSIGNED / GENERAL LEVELS (IF ANY) -->
    @if($unassignedLevels->isNotEmpty())
        <div class="border rounded-3 p-3 mb-4 bg-white shadow-xs">
            <div class="d-flex flex-wrap justify-content-between align-items-center pb-2 mb-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary text-white px-2 py-1 small" style="color: #ffffff !important; background-color: #334155 !important;">General</span>
                    <h5 class="fw-bold mb-0 text-dark">General / Unassigned Levels</h5>
                    <span class="badge bg-light text-secondary border small">
                        {{ $unassignedLevels->count() }} Level{{ $unassignedLevels->count() === 1 ? '' : 's' }}
                    </span>
                </div>
            </div>

            <div class="row g-3">
                @foreach($unassignedLevels as $level)
                    <div class="col-lg-6">
                        <div class="card h-100 border border-slate-200 rounded-3 shadow-xs">
                            <div class="card-header bg-light p-3 d-flex justify-content-between align-items-center border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary text-white px-2 py-1 fw-bold">{{ $level->grade_code }}</span>
                                    <h6 class="fw-bold mb-0 text-dark">{{ $level->grade_name }}</h6>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-success py-0 px-2 fw-semibold" 
                                            onclick="openCreateClassModal({{ $level->id }}, '{{ addslashes($level->grade_name) }}')">
                                        ＋ Class
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" 
                                            onclick="openEditLevelModal({{ $level->id }}, '{{ addslashes($level->grade_name) }}', '{{ $level->grade_code }}', null, {{ $level->display_order }})">
                                        ✏️
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" 
                                            onclick="confirmDelete('level', {{ $level->id }}, '{{ addslashes($level->grade_name) }}', 'School Level')">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small fw-semibold text-muted">Classes in Level:</span>
                                    <span class="badge bg-secondary-subtle text-secondary small">
                                        {{ $level->classes->count() }} Section{{ $level->classes->count() === 1 ? '' : 's' }}
                                    </span>
                                </div>
                                @if($level->classes->isEmpty())
                                    <div class="p-2 text-center bg-light rounded text-muted small border border-dashed">
                                        No classes assigned.
                                    </div>
                                @else
                                    <div class="list-group list-group-flush border rounded-2">
                                        @foreach($level->classes as $cls)
                                            <div class="list-group-item d-flex justify-content-between align-items-center p-2 small">
                                                <a href="{{ route('config.classes.profile', $cls->id) }}" class="fw-bold text-dark text-decoration-none hover-primary">
                                                    {{ $cls->class_name }} ↗
                                                </a>
                                                <div class="d-flex align-items-center gap-1">
                                                    <a href="{{ route('config.classes.profile', $cls->id) }}" class="btn btn-sm btn-outline-primary py-0 px-2 fw-semibold" title="View Class Profile">
                                                        👁️ Profile
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" 
                                                            onclick="confirmDelete('class', {{ $cls->id }}, '{{ addslashes($cls->class_name) }}', 'Class Section')">
                                                        🗑️
                                                    </button>
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
        </div>
    @endif
</div>

<!-- ==================================================================== -->
<!-- MODALS                                                                -->
<!-- ==================================================================== -->

<!-- 1. MODAL: CREATE LEVEL -->
<div class="modal fade" id="modalCreateLevel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.levels.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">＋ Create New School Level</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Level / Grade Name <span class="text-danger">*</span></label>
                        <input type="text" name="grade_name" class="form-control" placeholder="e.g. Level 1, Grade 10, Kindergarten 1, Year 7" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Level Code <span class="text-danger">*</span></label>
                            <input type="text" name="grade_code" class="form-control text-uppercase" placeholder="e.g. L1, G10, KG1, Y7" required maxlength="10">
                            <small class="text-muted">Short identifier</small>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Display Order</label>
                            <input type="number" name="display_order" class="form-control" placeholder="e.g. 1, 2, 3">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Educational Stage / Division</label>
                        <select name="division_id" id="createLevelDivisionSelect" class="form-select">
                            <option value="">-- General / None --</option>
                            @foreach($divisions as $div)
                                <option value="{{ $div->id }}">{{ $div->division_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <hr class="my-3">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="create_class" value="1" id="createClassCheck" checked onchange="document.getElementById('defaultClassBox').style.display = this.checked ? 'block' : 'none';">
                        <label class="form-check-label small fw-bold text-dark" for="createClassCheck">
                            Automatically create class(es) / section(s) for this level
                        </label>
                    </div>
                    <div id="defaultClassBox" class="mb-2">
                        <input type="text" name="class_name" class="form-control form-control-sm fw-semibold" placeholder="e.g. Section A, Section B (separate multiple classes with commas)" value="Section A">
                        <small class="text-muted">You can enter multiple sections separated by comma (e.g. <code>10-A, 10-B, 10-C</code>).</small>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Create Level</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. MODAL: EDIT LEVEL -->
<div class="modal fade" id="modalEditLevel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="editLevelForm" method="POST" action="">
                @csrf
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title fw-bold">✏️ Edit School Level</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Level / Grade Name <span class="text-danger">*</span></label>
                        <input type="text" name="grade_name" id="editLevelName" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Level Code <span class="text-danger">*</span></label>
                            <input type="text" name="grade_code" id="editLevelCode" class="form-control text-uppercase" required maxlength="10">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Display Order <span class="text-danger">*</span></label>
                            <input type="number" name="display_order" id="editLevelOrder" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Educational Stage / Division</label>
                        <select name="division_id" id="editLevelDivision" class="form-select">
                            <option value="">-- General / None --</option>
                            @foreach($divisions as $div)
                                <option value="{{ $div->id }}">{{ $div->division_name }}</option>
                            @endforeach
                        </select>
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

<!-- 3. MODAL: BATCH GENERATE LEVELS -->
<div class="modal fade" id="modalBatchGenerate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.levels.batch') }}" method="POST">
                @csrf
                <div class="modal-header bg-info text-dark">
                    <h5 class="modal-title fw-bold">⚡ 1-Click Batch Level Generator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Quickly create a sequence of levels (e.g. Grade 1 to 12, or Level 1 to 6) in a single click.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Naming Pattern <span class="text-danger">*</span></label>
                        <input type="text" name="pattern" class="form-control" value="Level {N}" required placeholder="e.g. Level {N} or Grade {N} or Year {N}">
                        <small class="text-muted">Use <code>{N}</code> as the placeholder for the sequential level number.</small>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">Code Prefix</label>
                            <input type="text" name="code_prefix" id="batchCodePrefix" class="form-control text-uppercase" value="L" required placeholder="e.g. L, G" oninput="updateBatchPreview()">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">From Level</label>
                            <input type="number" name="from_level" id="batchFromLevel" class="form-control" value="1" min="1" max="30" required oninput="updateBatchPreview()">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-dark">To Level</label>
                            <input type="number" name="to_level" id="batchToLevel" class="form-control" value="12" min="1" max="30" required oninput="updateBatchPreview()">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Assign to Educational Stage</label>
                        <select name="division_id" class="form-select">
                            <option value="">-- General / No Division --</option>
                            @foreach($divisions as $div)
                                <option value="{{ $div->id }}">{{ $div->division_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Enhanced Classes / Sections Creation Section -->
                    <hr class="my-3">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="create_classes" value="1" id="batchClassesToggle" checked onchange="toggleBatchClassesConfig(this.checked)">
                        <label class="form-check-label small fw-bold text-dark" for="batchClassesToggle">
                            Create classes / sections for each generated level
                        </label>
                    </div>

                    <div id="batchClassesConfigBox" class="p-3 bg-light rounded-3 border mb-3">
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-dark mb-0">Classes / Sections to Create (comma-separated):</label>
                                <span class="small text-muted" style="font-size: 0.75rem;">Click presets below or type custom</span>
                            </div>
                            <input type="text" name="class_sections" id="batchClassSectionsInput" class="form-control form-control-sm fw-bold" value="A, B" placeholder="e.g. A, B or A, B, C or Blue, Green, Red" oninput="updateBatchPreview()">
                        </div>

                        <!-- Quick Preset Buttons -->
                        <div class="d-flex flex-wrap align-items-center gap-1 mb-3">
                            <span class="small text-muted me-1" style="font-size: 0.75rem;">Presets:</span>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="setBatchSections('A')">Section A</button>
                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 fw-semibold" style="font-size: 0.75rem;" onclick="setBatchSections('A, B')">A, B (2 Sections)</button>
                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 fw-semibold" style="font-size: 0.75rem;" onclick="setBatchSections('A, B, C')">A, B, C (3 Sections)</button>
                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 fw-semibold" style="font-size: 0.75rem;" onclick="setBatchSections('A, B, C, D')">A, B, C, D (4 Sections)</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="setBatchSections('1, 2')">1, 2</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="setBatchSections('Blue, Green, Red')">Colors</button>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-8">
                                <label class="form-label small fw-semibold text-dark mb-1">Class Naming Format:</label>
                                <select name="class_format" id="batchClassFormatSelect" class="form-select form-select-sm" onchange="updateBatchPreview()">
                                    <option value="CODE_DASH_SEC" selected>L{N}-A (e.g. L1-A, L1-B)</option>
                                    <option value="NAME_DASH_SEC">Level {N} - A (e.g. Level 1 - A)</option>
                                    <option value="NAME_SPACE_SEC">Level {N} A (e.g. Level 1 A)</option>
                                    <option value="SECTION_ONLY">Section Only (e.g. A, B)</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-semibold text-dark mb-1">Capacity:</label>
                                <input type="number" name="max_capacity" class="form-control form-control-sm text-center" value="30" min="1" max="100">
                            </div>
                        </div>

                        <!-- Live Preview Box -->
                        <div class="p-2 bg-white rounded border text-muted small mt-2">
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <span class="text-primary fw-bold" style="font-size: 0.75rem;">💡 Dynamic Live Preview:</span>
                                <span id="batchPreviewCountBadge" class="badge bg-primary-subtle text-primary ms-auto" style="font-size: 0.7rem;">...</span>
                            </div>
                            <div id="batchPreviewClasses" class="text-dark fw-semibold font-monospace" style="font-size: 0.75rem; max-height: 70px; overflow-y: auto;">
                                ...
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info fw-bold">Generate All Levels</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 4. MODAL: CREATE STAGE / DIVISION -->
<div class="modal fade" id="modalCreateDivision" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.divisions.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">🏛️ Add Educational Stage / Division</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Stage Name <span class="text-danger">*</span></label>
                        <input type="text" name="division_name" class="form-control" placeholder="e.g. Primary School, Junior High, Senior Secondary" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-dark">Display Order</label>
                        <input type="number" name="display_order" class="form-control" placeholder="e.g. 1, 2, 3">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark fw-bold">Save Stage</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 5. MODAL: CREATE CLASS / SECTION -->
<div class="modal fade" id="modalCreateClass" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.classes.store') }}" method="POST">
                @csrf
                <input type="hidden" name="grade_id" id="createClassGradeId" value="">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">＋ Add Class / Section</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border small py-2 mb-3">
                        Level: <strong id="createClassLevelName" class="text-primary">-</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Class / Section Name <span class="text-danger">*</span></label>
                        <input type="text" name="class_name" class="form-control" placeholder="e.g. 10-A, Section Blue, Alpha" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Homeroom Teacher</label>
                        <input type="text" name="homeroom_teacher" class="form-control" placeholder="e.g. Dr. Sarah Jenkins">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-dark">Maximum Capacity</label>
                        <input type="number" name="max_capacity" class="form-control" value="30" min="1" max="100">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">Add Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 6. REUSABLE MODAL: CONFIRM DELETION -->
<div class="modal fade" id="modalConfirmDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="deleteForm" method="POST" action="">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">⚠️ Confirm Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-2">Are you sure you want to permanently delete this item?</p>
                    <div class="p-3 bg-danger-subtle text-danger border border-danger-subtle rounded-3 mb-3">
                        <strong id="deleteItemName" class="fs-6 d-block">-</strong>
                        <span id="deleteItemType" class="small">-</span>
                    </div>
                    <p class="small text-muted mb-0">
                        ⚡ <strong>Note:</strong> Items with actively enrolled students cannot be deleted until students are reassigned.
                    </p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold">Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openCreateLevelWithDivision(divisionId, divisionName) {
        const select = document.getElementById('createLevelDivisionSelect');
        if (select) {
            select.value = divisionId;
        }
        const modal = new bootstrap.Modal(document.getElementById('modalCreateLevel'));
        modal.show();
    }

    function openEditLevelModal(id, name, code, divisionId, order) {
        document.getElementById('editLevelForm').action = `/configuration/levels/${id}/update`;
        document.getElementById('editLevelName').value = name;
        document.getElementById('editLevelCode').value = code;
        document.getElementById('editLevelOrder').value = order;
        document.getElementById('editLevelDivision').value = divisionId || '';
        const modal = new bootstrap.Modal(document.getElementById('modalEditLevel'));
        modal.show();
    }

    function openCreateClassModal(gradeId, levelName) {
        document.getElementById('createClassGradeId').value = gradeId;
        document.getElementById('createClassLevelName').textContent = levelName;
        const modal = new bootstrap.Modal(document.getElementById('modalCreateClass'));
        modal.show();
    }

    function confirmDelete(type, id, name, typeLabel) {
        const form = document.getElementById('deleteForm');
        if (type === 'level') {
            form.action = `/configuration/levels/${id}/delete`;
        } else if (type === 'division') {
            form.action = `/configuration/divisions/${id}/delete`;
        } else if (type === 'class') {
            form.action = `/configuration/classes/${id}/delete`;
        }
        document.getElementById('deleteItemName').textContent = name;
        document.getElementById('deleteItemType').textContent = `Type: ${typeLabel}`;
        const modal = new bootstrap.Modal(document.getElementById('modalConfirmDelete'));
        modal.show();
    }

    function toggleBatchClassesConfig(checked) {
        const box = document.getElementById('batchClassesConfigBox');
        if (box) box.style.display = checked ? 'block' : 'none';
        updateBatchPreview();
    }

    function setBatchSections(sections) {
        const input = document.getElementById('batchClassSectionsInput');
        if (input) {
            input.value = sections;
            updateBatchPreview();
        }
    }

    function updateBatchPreview() {
        const pattern = document.querySelector('input[name="pattern"]')?.value || 'Level {N}';
        const prefix = document.getElementById('batchCodePrefix')?.value.toUpperCase() || 'L';
        const from = parseInt(document.getElementById('batchFromLevel')?.value) || 1;
        const to = parseInt(document.getElementById('batchToLevel')?.value) || 1;
        const createClasses = document.getElementById('batchClassesToggle')?.checked ?? true;
        const sectionsInput = document.getElementById('batchClassSectionsInput')?.value || 'A';
        const format = document.getElementById('batchClassFormatSelect')?.value || 'CODE_DASH_SEC';

        const previewContainer = document.getElementById('batchPreviewClasses');
        const badge = document.getElementById('batchPreviewCountBadge');

        if (!createClasses) {
            if (previewContainer) previewContainer.textContent = 'Classes disabled — Only levels will be generated.';
            if (badge) badge.textContent = `${Math.max(0, to - from + 1)} Levels (0 Classes)`;
            return;
        }

        const sections = sectionsInput.split(',')
            .map(s => s.trim())
            .filter(s => s.length > 0);

        const levelCount = Math.max(0, to - from + 1);
        const classCount = levelCount * sections.length;

        if (badge) {
            badge.textContent = `${levelCount} Level(s) × ${sections.length} Section(s) = ${classCount} Classes`;
        }

        if (previewContainer) {
            let samples = [];
            const displayLimit = Math.min(3, levelCount);
            for (let i = from; i < from + displayLimit; i++) {
                const name = pattern.replace('{N}', i);
                const code = prefix + i;
                const classNames = sections.map(sec => {
                    if (format === 'NAME_DASH_SEC') return `${name} - ${sec}`;
                    if (format === 'NAME_SPACE_SEC') return `${name} ${sec}`;
                    if (format === 'SECTION_ONLY') return sec;
                    return `${code}-${sec}`;
                });
                samples.push(`${name} → [${classNames.join(', ')}]`);
            }
            if (levelCount > displayLimit) {
                samples.push(`... and ${levelCount - displayLimit} more levels (${classCount} classes total)`);
            }
            previewContainer.innerHTML = samples.join('<br>');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateBatchPreview();
    });
</script>
@endsection
