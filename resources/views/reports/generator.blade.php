@extends('layouts.app')

@section('title', 'Reports & Analytics Studio - ' . $activeSchool->school_name)

@section('content')
<div class="container-fluid px-0">
    <!-- Breadcrumb & Header -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Reports &amp; Analytics Studio</li>
        </ol>
    </nav>

    <!-- Success & Error Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-xs mb-3 py-2 px-3 small d-flex align-items-center justify-content-between" role="alert">
            <div class="d-flex align-items-center gap-2">
                <span class="fs-6">✅</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-xs mb-3 py-2 px-3 small d-flex align-items-center justify-content-between" role="alert">
            <div class="d-flex align-items-center gap-2">
                <span class="fs-6">⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-xs mb-3 py-2 px-3 small">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Studio Header Banner -->
    <div class="card border rounded-3 p-4 mb-4 bg-white shadow-xs">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('school.profile') }}" title="Click to manage School Profile & Logo">
                    <img src="{{ $activeSchool->getLogoUrl() }}" alt="{{ $activeSchool->school_name }}" class="rounded-3 border bg-white p-1 shadow-2xs" style="height: 56px; width: auto; max-width: 110px; object-fit: contain;">
                </a>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-primary text-white px-2 py-1 small" style="color: #ffffff !important;">Universal Reporting Studio</span>
                        <span class="badge bg-light text-secondary border small">Active School: <strong>{{ $activeSchool->school_name }}</strong></span>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark">📊 Academic Reports &amp; Analytics Generator</h4>
                    <p class="text-muted small mb-0">
                        Switch between <strong>General Institutional Reports</strong> and <strong>School-Customized Reports</strong>. Deploy complete Jasper bundles with subreports and images.
                    </p>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary btn-sm fw-bold px-3 shadow-2xs" data-bs-toggle="modal" data-bs-target="#modalAddReport">
                    ＋ Add New Report
                </button>
                <a href="{{ route('reports.broadsheet') }}" class="btn btn-outline-secondary btn-sm fw-semibold">
                    📋 Master Broadsheet →
                </a>
            </div>
        </div>
    </div>

    <!-- MAIN REPORT GENERATOR PARAMETER STUDIO -->
    <div class="card border rounded-3 p-4 mb-4 bg-white shadow-xs">
        <form action="{{ route('reports.generator.dispatch') }}" method="POST" id="reportGeneratorForm">
            @csrf

            <!-- 1. DYNAMIC REPORT SELECTOR DROPDOWN -->
            <div class="mb-4 pb-3 border-bottom">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <label class="form-label small fw-bold text-dark mb-0">
                        1. Select Report from Catalog <span class="text-danger">*</span>
                    </label>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold py-1 px-2" data-bs-toggle="modal" data-bs-target="#modalAddReport" title="Create a new report in this catalog">
                            ＋ Add Report
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold py-1 px-2" onclick="openEditReportModal()" title="Edit title, scope or parameters">
                            ✏️ Edit Selected
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-indigo fw-semibold py-1 px-2" onclick="openDeployModal()" title="Deploy Jasper template, subreports, and image assets">
                            🚀 Deploy Assets &amp; Subreports
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2" onclick="confirmDeleteReport()" title="Remove selected report">
                            🗑️ Remove
                        </button>
                    </div>
                </div>

                <!-- Prominent Dropdown Selection -->
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-light text-muted border-primary-subtle fs-5">📊</span>
                    <select name="report_template_id" id="reportSelectDropdown" class="form-select form-select-lg fw-bold text-dark border-primary-subtle shadow-2xs" onchange="onReportDropdownChange(this)">
                        @foreach($groupedTemplates as $groupTitle => $templates)
                            <optgroup label="── {{ $groupTitle }} ──">
                                @foreach($templates as $tmpl)
                                    @php
                                        $icon = match($tmpl->base_type) {
                                            'card_single' => '🎓',
                                            'card_batch' => '🖨️',
                                            'analytics' => '📈',
                                            'broadsheet' => '📋',
                                            'at_risk' => '⚠️',
                                            'honor_roll' => '🏆',
                                            default => '📄'
                                        };
                                        $scopeTag = $tmpl->is_global ? '[General - All Schools]' : '[School Custom]';
                                    @endphp
                                    <option value="{{ $tmpl->id }}"
                                            data-id="{{ $tmpl->id }}"
                                            data-title="{{ $tmpl->title }}"
                                            data-category="{{ $tmpl->category }}"
                                            data-description="{{ $tmpl->description }}"
                                            data-base-type="{{ $tmpl->base_type }}"
                                            data-is-global="{{ $tmpl->is_global ? '1' : '0' }}"
                                            data-school-id="{{ $tmpl->school_id }}"
                                            data-template-file="{{ $tmpl->template_file }}"
                                            data-parameters="{{ json_encode($tmpl->parameters ?? []) }}"
                                            data-format="{{ $tmpl->default_format }}"
                                            data-is-system="{{ $tmpl->is_system ? '1' : '0' }}"
                                            {{ $selectedReportTemplate && $selectedReportTemplate->id == $tmpl->id ? 'selected' : '' }}>
                                        {{ $icon }} {{ $tmpl->title }} &nbsp;{{ $scopeTag }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <!-- Report Context & Description Details -->
                <div class="mt-2 p-3 bg-light rounded-3 border d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-bold text-dark small" id="selectedReportTitleDisplay">
                                {{ $selectedReportTemplate->title ?? 'Selected Report' }}
                            </span>
                            <span id="selectedReportScopeBadge" class="badge bg-primary text-white" style="font-size: 0.7rem; color: #ffffff !important;">
                                {{ $selectedReportTemplate && $selectedReportTemplate->is_global ? '🌐 General Report (All Schools)' : '🏫 School Customized Report' }}
                            </span>
                        </div>
                        <p class="text-muted small mb-0" id="selectedReportDesc">
                            {{ $selectedReportTemplate->description ?? 'Generates official report documentation based on configured parameters.' }}
                        </p>
                    </div>
                    <div class="border-start ps-3 d-none d-md-block">
                        <span class="small fw-semibold text-muted d-block" style="font-size: 0.72rem;">ACTIVE PARAMETERS:</span>
                        <div id="activeParametersPills" class="d-flex flex-wrap gap-1 mt-1">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. DYNAMIC PARAMETERS SECTION (Fields show/hide based on report's selected parameters) -->
            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label small fw-bold text-dark mb-0">
                        2. Academic Parameters &amp; Filtering
                    </label>
                    <small class="text-muted fst-italic" id="paramVisibilityHint">
                        Only relevant parameters for the selected report are displayed.
                    </small>
                </div>

                <div class="row g-3">
                    <!-- Parameter: School Level / Stage -->
                    <div class="col-md-6 col-lg-4" id="paramLevelWrapper">
                        <label class="form-label small text-muted d-flex align-items-center gap-1">
                            <span>🏛️ School Level / Stage</span>
                        </label>
                        <select class="form-select" id="generatorLevelSelect" name="level_id" onchange="handleLevelChange(this.value)">
                            <option value="">-- All Levels / Stages --</option>
                            @foreach($levels as $lvl)
                                <option value="{{ $lvl->id }}" {{ $selectedLevelId == $lvl->id ? 'selected' : '' }}>
                                    {{ $lvl->grade_name }} ({{ $lvl->grade_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Parameter: Class / Batch -->
                    <div class="col-md-6 col-lg-4" id="paramClassWrapper">
                        <label class="form-label small text-muted d-flex align-items-center gap-1">
                            <span>🏫 Class / Batch <span class="text-danger">*</span></span>
                        </label>
                        <select class="form-select fw-semibold" name="class_id" id="generatorClassSelect" required onchange="handleClassChange(this.value)">
                            @foreach($allClasses as $cls)
                                <option value="{{ $cls->id }}" data-level="{{ $cls->grade_id }}" {{ $selectedClassId == $cls->id ? 'selected' : '' }}>
                                    {{ $cls->class_name }} ({{ $cls->gradeLevel->grade_code ?? 'CLS' }}) - {{ $cls->students->count() }} std
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Parameter: Individual Student -->
                    <div class="col-md-6 col-lg-4" id="paramStudentWrapper">
                        <label class="form-label small text-muted d-flex align-items-center gap-1">
                            <span>🎓 Student (for Individual Card)</span>
                        </label>
                        <select class="form-select fw-semibold" name="student_id" id="generatorStudentSelect">
                            @if(count($studentsInClass) === 0)
                                <option value="">-- No students in this class --</option>
                            @else
                                @foreach($studentsInClass as $std)
                                    <option value="{{ $std->id }}" {{ $selectedStudentId == $std->id ? 'selected' : '' }}>
                                        {{ $std->full_name }} ({{ $std->admission_number }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Parameter: Academic Period / Term -->
                    <div class="col-md-6 col-lg-4" id="paramPeriodWrapper">
                        <label class="form-label small text-muted d-flex align-items-center gap-1">
                            <span>📅 Academic Period / Term</span>
                        </label>
                        <select class="form-select" name="period_id" id="generatorPeriodSelect">
                            <option value="">-- Full Year / Cumulative --</option>
                            @foreach($periods as $prd)
                                <option value="{{ $prd->id }}" {{ $selectedPeriodId == $prd->id ? 'selected' : '' }}>
                                    {{ $prd->period_name }} (Weight: {{ $prd->weight }}%)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Parameter: Subject / Course -->
                    <div class="col-md-6 col-lg-4" id="paramSubjectWrapper">
                        <label class="form-label small text-muted d-flex align-items-center gap-1">
                            <span>📚 Subject / Course</span>
                        </label>
                        <select class="form-select" name="subject_id" id="generatorSubjectSelect">
                            <option value="">-- All Enrolled Subjects --</option>
                            @foreach($subjects as $subj)
                                <option value="{{ $subj->id }}" {{ $selectedSubjectId == $subj->id ? 'selected' : '' }}>
                                    {{ $subj->subject_name }} ({{ $subj->subject_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Parameter: Passing Mark Threshold -->
                    <div class="col-md-6 col-lg-4" id="paramPassMarkWrapper">
                        <label class="form-label small text-muted d-flex align-items-center gap-1">
                            <span>⚖️ Passing Mark Threshold (%)</span>
                        </label>
                        <div class="input-group">
                            <input type="number" step="0.5" min="0" max="100" class="form-control" name="pass_mark" id="generatorPassMarkInput" value="60.0" placeholder="60.0">
                            <span class="input-group-text bg-light text-muted small">%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. OUTPUT FORMAT & ACTION DISPATCH BUTTONS -->
            <div class="pt-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-3" id="paramFormatWrapper">
                <div class="d-flex align-items-center gap-2">
                    <span class="small fw-bold text-muted">Select Output Action:</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" name="format" value="view" class="btn btn-primary px-4 fw-bold shadow-2xs" id="btnActionView">
                        👁️ Generate &amp; Preview
                    </button>
                    <button type="submit" name="format" value="print" class="btn btn-outline-dark px-3 fw-semibold" id="btnActionPrint">
                        🖨️ Direct Print / PDF
                    </button>
                    <button type="submit" name="format" value="csv" class="btn btn-outline-success px-3 fw-semibold" id="btnActionCsv">
                        📊 Export CSV (Excel)
                    </button>
                    <button type="submit" name="format" value="jasper" class="btn btn-outline-indigo px-3 fw-semibold" id="btnActionJasper">
                        ⚡ JasperReports PDF
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- QUICK PRESET SHORTCUT CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border rounded-3 p-3 bg-white h-100 shadow-2xs">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold mb-0 text-dark">🖨️ Batch Report Cards</h6>
                    <span class="badge bg-primary text-white" style="color: #ffffff !important;">Full Class</span>
                </div>
                <p class="text-muted small mb-3">Print all student report cards for a chosen class in one multi-page document with clean page breaks.</p>
                <a href="{{ route('reports.card.batch', ['class' => $selectedClassId ?? $allClasses->first()->id ?? 1]) }}" class="btn btn-outline-primary btn-sm fw-bold w-100">
                    Open Batch Print Studio →
                </a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border rounded-3 p-3 bg-white h-100 shadow-2xs">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold mb-0 text-dark">📈 Performance Analytics</h6>
                    <span class="badge bg-success text-white" style="color: #ffffff !important;">Visual</span>
                </div>
                <p class="text-muted small mb-3">View class score distributions, pass rates, grade histograms (A-F), and top performers.</p>
                <a href="{{ route('reports.analytics.class', ['class' => $selectedClassId ?? $allClasses->first()->id ?? 1]) }}" class="btn btn-outline-success btn-sm fw-bold w-100">
                    View Class Analytics →
                </a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border rounded-3 p-3 bg-white h-100 shadow-2xs">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold mb-0 text-dark">📋 Master Broadsheet</h6>
                    <span class="badge bg-dark text-white" style="color: #ffffff !important;">Matrix</span>
                </div>
                <p class="text-muted small mb-3">Unified grid of all students across all courses with ranking, attendance, and Excel download.</p>
                <a href="{{ route('reports.broadsheet', ['class_id' => $selectedClassId ?? $allClasses->first()->id ?? 1]) }}" class="btn btn-outline-dark btn-sm fw-bold w-100">
                    Open Master Broadsheet →
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 1: ADD NEW REPORT TO CATALOG        -->
<!-- ========================================== -->
<div class="modal fade" id="modalAddReport" tabindex="-1" aria-labelledby="modalAddReportLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('reports.templates.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="class_id" value="{{ $selectedClassId }}">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalAddReportLabel" style="color: #ffffff !important;">
                        ＋ Add New Report to Catalog
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Scope Selection: General vs School-Specific -->
                    <div class="p-3 mb-3 rounded-3 border bg-light">
                        <label class="form-label small fw-bold text-dark d-block mb-1">
                            🌐 Report Scope &amp; School Visibility <span class="text-danger">*</span>
                        </label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-check p-2 bg-white rounded border">
                                    <input class="form-check-input ms-1" type="radio" name="is_global" id="scope_global" value="1" checked>
                                    <label class="form-check-label ps-2 cursor-pointer w-100" for="scope_global">
                                        <strong class="text-dark d-block">🌐 General Report (All Schools)</strong>
                                        <small class="text-muted">Available across all schools in the system</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check p-2 bg-white rounded border">
                                    <input class="form-check-input ms-1" type="radio" name="is_global" id="scope_school" value="0">
                                    <label class="form-check-label ps-2 cursor-pointer w-100" for="scope_school">
                                        <strong class="text-dark d-block">🏫 School Customized Only</strong>
                                        <small class="text-muted">Shows only in <em>{{ $activeSchool->school_name }}</em></small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Title -->
                        <div class="col-md-7">
                            <label class="form-label small fw-bold text-dark">Report Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control fw-semibold" name="title" required placeholder="e.g. Midterm Progress Report, Senior Graduation Audit">
                        </div>

                        <!-- Category -->
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-dark">Category / Group</label>
                            <input type="text" class="form-control" name="category" list="reportCategoriesList" placeholder="e.g. Student Report Cards, Analytics">
                            <datalist id="reportCategoriesList">
                                <option value="Student Report Cards">
                                <option value="Performance & Analytics">
                                <option value="Administrative Lists">
                                <option value="Custom Reports">
                                <option value="End-of-Term Records">
                            </datalist>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Base Engine / Layout -->
                        <div class="col-md-7">
                            <label class="form-label small fw-bold text-dark">Base Report Engine / Layout <span class="text-danger">*</span></label>
                            <select class="form-select fw-semibold" name="base_type" required onchange="presetParametersForBaseType(this.value, 'add')">
                                <option value="card_single">🎓 Single Student Report Card (Official A4)</option>
                                <option value="card_batch">🖨️ Bulk Class Report Cards (Multi-page Print)</option>
                                <option value="analytics">📈 Class Performance &amp; Grade Analytics Dashboard</option>
                                <option value="broadsheet">📋 Master Class Broadsheet Matrix</option>
                                <option value="at_risk">⚠️ At-Risk / Remedial Alert List</option>
                                <option value="honor_roll">🏆 Honor Roll &amp; Top Performers</option>
                                <option value="custom">📄 Custom JasperReports / External Document</option>
                            </select>
                        </div>

                        <!-- Default Format -->
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-dark">Default Output Action</label>
                            <select class="form-select" name="default_format">
                                <option value="view">👁️ Screen Preview</option>
                                <option value="print">🖨️ Instant Print / PDF</option>
                                <option value="csv">📊 CSV Spreadsheet</option>
                                <option value="jasper">⚡ JasperReports PDF</option>
                            </select>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-dark">Description / Purpose</label>
                        <textarea class="form-control small" name="description" rows="2" placeholder="Brief summary of what this report presents and who it is for..."></textarea>
                    </div>

                    <!-- PARAMETERS SELECTION CHECKBOXES -->
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label small fw-bold text-dark mb-0">
                                ⚙️ Choose Parameters to Use for this Report:
                            </label>
                            <div class="d-flex gap-2">
                                <a href="javascript:void(0)" class="small text-primary fw-semibold text-decoration-none" onclick="toggleAllParams('add', true)">Select All</a>
                                <span class="text-muted small">•</span>
                                <a href="javascript:void(0)" class="small text-muted text-decoration-none" onclick="toggleAllParams('add', false)">Clear All</a>
                            </div>
                        </div>
                        <p class="text-muted small mb-3">
                            Check the input controls that should be shown to users when generating this report:
                        </p>

                        <div class="row g-2" id="addParamCheckboxesContainer">
                            @foreach($availableParams as $key => $param)
                                <div class="col-md-6">
                                    <div class="form-check p-2 bg-white rounded border shadow-2xs">
                                        <input class="form-check-input ms-1 param-check-add" type="checkbox" name="parameters[]" value="{{ $key }}" id="param_add_{{ $key }}" {{ in_array($key, ['level', 'class', 'format']) ? 'checked' : '' }}>
                                        <label class="form-check-label ps-2 cursor-pointer w-100" for="param_add_{{ $key }}">
                                            <div class="fw-bold text-dark small">{{ $param['icon'] }} {{ $param['label'] }}</div>
                                            <small class="text-muted d-block" style="font-size: 0.73rem;">{{ $param['description'] }}</small>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        💾 Save &amp; Add Report to Catalog
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 2: EDIT EXISTING REPORT             -->
<!-- ========================================== -->
<div class="modal fade" id="modalEditReport" tabindex="-1" aria-labelledby="modalEditReportLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="editReportForm" method="POST" action="">
                @csrf
                @method('PUT')
                <input type="hidden" name="class_id" value="{{ $selectedClassId }}">

                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="modalEditReportLabel" style="color: #ffffff !important;">
                        ✏️ Edit Report Definition
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Scope Selection -->
                    <div class="p-3 mb-3 rounded-3 border bg-light">
                        <label class="form-label small fw-bold text-dark d-block mb-1">
                            🌐 Report Scope &amp; School Visibility <span class="text-danger">*</span>
                        </label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-check p-2 bg-white rounded border">
                                    <input class="form-check-input ms-1" type="radio" name="is_global" id="edit_scope_global" value="1">
                                    <label class="form-check-label ps-2 cursor-pointer w-100" for="edit_scope_global">
                                        <strong class="text-dark d-block">🌐 General Report (All Schools)</strong>
                                        <small class="text-muted">Available to all schools</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check p-2 bg-white rounded border">
                                    <input class="form-check-input ms-1" type="radio" name="is_global" id="edit_scope_school" value="0">
                                    <label class="form-check-label ps-2 cursor-pointer w-100" for="edit_scope_school">
                                        <strong class="text-dark d-block">🏫 School Customized Only</strong>
                                        <small class="text-muted">Shows only in this school</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Title -->
                        <div class="col-md-7">
                            <label class="form-label small fw-bold text-dark">Report Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control fw-semibold" name="title" id="editReportTitle" required>
                        </div>

                        <!-- Category -->
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-dark">Category / Group</label>
                            <input type="text" class="form-control" name="category" id="editReportCategory" list="reportCategoriesList">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Base Engine / Layout -->
                        <div class="col-md-7">
                            <label class="form-label small fw-bold text-dark">Base Report Engine / Layout <span class="text-danger">*</span></label>
                            <select class="form-select fw-semibold" name="base_type" id="editReportBaseType" required>
                                <option value="card_single">🎓 Single Student Report Card (Official A4)</option>
                                <option value="card_batch">🖨️ Bulk Class Report Cards (Multi-page Print)</option>
                                <option value="analytics">📈 Class Performance &amp; Grade Analytics Dashboard</option>
                                <option value="broadsheet">📋 Master Class Broadsheet Matrix</option>
                                <option value="at_risk">⚠️ At-Risk / Remedial Alert List</option>
                                <option value="honor_roll">🏆 Honor Roll &amp; Top Performers</option>
                                <option value="custom">📄 Custom JasperReports / External Document</option>
                            </select>
                        </div>

                        <!-- Default Format -->
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-dark">Default Output Action</label>
                            <select class="form-select" name="default_format" id="editReportFormat">
                                <option value="view">👁️ Screen Preview</option>
                                <option value="print">🖨️ Instant Print / PDF</option>
                                <option value="csv">📊 CSV Spreadsheet</option>
                                <option value="jasper">⚡ JasperReports PDF</option>
                            </select>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-dark">Description / Purpose</label>
                        <textarea class="form-control small" name="description" id="editReportDesc" rows="2"></textarea>
                    </div>

                    <!-- PARAMETERS SELECTION CHECKBOXES -->
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label small fw-bold text-dark mb-0">
                                ⚙️ Choose Parameters to Use for this Report:
                            </label>
                            <div class="d-flex gap-2">
                                <a href="javascript:void(0)" class="small text-primary fw-semibold text-decoration-none" onclick="toggleAllParams('edit', true)">Select All</a>
                                <span class="text-muted small">•</span>
                                <a href="javascript:void(0)" class="small text-muted text-decoration-none" onclick="toggleAllParams('edit', false)">Clear All</a>
                            </div>
                        </div>
                        <p class="text-muted small mb-3">
                            Check the parameters required for this report:
                        </p>

                        <div class="row g-2" id="editParamCheckboxesContainer">
                            @foreach($availableParams as $key => $param)
                                <div class="col-md-6">
                                    <div class="form-check p-2 bg-white rounded border shadow-2xs">
                                        <input class="form-check-input ms-1 param-check-edit" type="checkbox" name="parameters[]" value="{{ $key }}" id="param_edit_{{ $key }}">
                                        <label class="form-check-label ps-2 cursor-pointer w-100" for="param_edit_{{ $key }}">
                                            <div class="fw-bold text-dark small">{{ $param['icon'] }} {{ $param['label'] }}</div>
                                            <small class="text-muted d-block" style="font-size: 0.73rem;">{{ $param['description'] }}</small>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark fw-bold px-4" style="color: #ffffff !important; background-color: #0f172a !important;">
                        💾 Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 3: DEPLOY REPORT PACKAGE (JRXML, SUBREPORTS & IMAGES)   -->
<!-- ============================================================== -->
<div class="modal fade" id="modalDeployReport" tabindex="-1" aria-labelledby="modalDeployReportLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-indigo text-white" style="background-color: #4338ca !important;">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-5">🚀</span>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalDeployReportLabel">
                            Deploy Report Package &amp; Media Assets
                        </h5>
                        <small class="text-indigo-100" id="deployReportSubtitle">Upload JRXML files, subreports, school logos, and signature stamps.</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- Action & Status Ribbon -->
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 bg-light rounded-3 border mb-4">
                    <div>
                        <div class="fw-bold text-dark small">Target Report: <span id="deployReportTitle">...</span></div>
                        <div class="small text-muted font-monospace" style="font-size: 0.75rem;" id="deployReportDirectory">...</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-dark fw-semibold" onclick="recompileCurrentPackage()">
                            ⚡ Recompile All Templates
                        </button>
                        <a href="#" id="btnDownloadBundleZip" class="btn btn-sm btn-outline-primary fw-semibold">
                            📥 Download Package (ZIP)
                        </a>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- LEFT COLUMN: EXISTING DEPLOYED ASSETS & PREVIEWS -->
                    <div class="col-lg-7 border-end">
                        <h6 class="fw-bold text-dark mb-3 d-flex align-items-center justify-content-between">
                            <span>📦 Active Deployed Files</span>
                            <span class="badge bg-secondary text-white" id="deployTotalFilesBadge" style="color: #ffffff !important;">0 files</span>
                        </h6>

                        <!-- Main Template Card -->
                        <div class="p-3 rounded border mb-3 bg-white shadow-2xs">
                            <span class="small fw-bold text-muted text-uppercase d-block mb-1" style="font-size: 0.7rem;">Primary Report Template (.jrxml)</span>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fs-5">📄</span>
                                    <div>
                                        <strong class="text-dark d-block" id="deployMainFileName">None deployed</strong>
                                        <small class="text-muted" id="deployMainFileSize">Upload a .jrxml file</small>
                                    </div>
                                </div>
                                <span class="badge bg-success text-white" id="deployMainStatusBadge" style="color: #ffffff !important;">Compiled ✅</span>
                            </div>
                        </div>

                        <!-- Subreports List -->
                        <div class="mb-3">
                            <span class="small fw-bold text-muted text-uppercase d-block mb-2" style="font-size: 0.7rem;">Deployed Subreports</span>
                            <div id="deploySubreportsContainer" class="d-flex flex-column gap-2">
                                <div class="text-muted small fst-italic py-2 px-3 border rounded bg-light">No subreports uploaded yet.</div>
                            </div>
                        </div>

                        <!-- Images & Media Gallery with Live Previews -->
                        <div>
                            <span class="small fw-bold text-muted text-uppercase d-block mb-2" style="font-size: 0.7rem;">Image Assets &amp; Signatures Gallery</span>
                            <div id="deployImagesGallery" class="row g-2">
                                <div class="col-12 text-muted small fst-italic py-2 px-3 border rounded bg-light">No image assets uploaded yet.</div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: DEPLOYMENT UPLOAD FORMS -->
                    <div class="col-lg-5">
                        <h6 class="fw-bold text-dark mb-3">📤 Upload &amp; Deploy Files</h6>

                        <form id="deployUploadForm" method="POST" action="" enctype="multipart/form-data">
                            @csrf

                            <!-- Option A: ZIP Archive -->
                            <div class="p-3 rounded border bg-light mb-3">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    📦 Option 1: Full Package ZIP Archive
                                </label>
                                <p class="text-muted small mb-2" style="font-size: 0.75rem;">
                                    Upload a single .zip containing your main .jrxml, subreports, and images folder. The system auto-unpacks and compiles.
                                </p>
                                <input type="file" name="zip_package" class="form-control form-control-sm" accept=".zip">
                            </div>

                            <div class="text-center text-muted small fw-bold my-2">── OR UPLOAD INDIVIDUAL FILES ──</div>

                            <!-- Option B: Direct Files -->
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark mb-1">📄 Main Template File (.jrxml)</label>
                                <input type="file" name="template_file" class="form-control form-control-sm" accept=".jrxml,.jasper">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark mb-1">📑 Subreport Files (.jrxml, multiple)</label>
                                <input type="file" name="subreport_files[]" multiple class="form-control form-control-sm" accept=".jrxml,.jasper">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark mb-1">🖼️ Media &amp; Images (.png, .jpg, .svg, multiple)</label>
                                <input type="file" name="asset_files[]" multiple class="form-control form-control-sm" accept=".png,.jpg,.jpeg,.gif,.svg">
                                <div class="form-text small" style="font-size: 0.72rem;">Include school crest logo, principal signature scan, official stamp, etc.</div>
                            </div>

                            <button type="submit" class="btn btn-primary fw-bold w-100 shadow-2xs mt-2" id="btnSubmitDeployment">
                                🚀 Deploy &amp; Compile Files Now
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Delete Form -->
<form id="deleteReportForm" method="POST" action="" style="display: none;">
    @csrf
    @method('DELETE')
    <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
</form>

<script>
const allClassesData = @json($allClasses);
const availableParamsMeta = @json($availableParams);

/**
 * Handle report selection change from Dropdown
 */
function onReportDropdownChange(selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    if (!selectedOption) return;

    const title = selectedOption.dataset.title || selectedOption.textContent.trim();
    const description = selectedOption.dataset.description || '';
    const format = selectedOption.dataset.format || 'view';
    const isGlobal = selectedOption.dataset.isGlobal === '1';
    const baseType = selectedOption.dataset.baseType || 'card_single';

    let params = [];
    try {
        params = JSON.parse(selectedOption.dataset.parameters || '[]');
    } catch(e) {
        params = ['level', 'class', 'format'];
    }

    if (baseType === 'card_single') {
        if (!params.includes('student')) params.push('student');
        if (!params.includes('class')) params.push('class');
    }

    // Update info header
    const titleEl = document.getElementById('selectedReportTitleDisplay');
    if (titleEl) titleEl.textContent = title;

    const scopeBadge = document.getElementById('selectedReportScopeBadge');
    if (scopeBadge) {
        if (isGlobal) {
            scopeBadge.className = 'badge bg-primary text-white';
            scopeBadge.style.color = '#ffffff !important';
            scopeBadge.textContent = '🌐 General Report (All Schools)';
        } else {
            scopeBadge.className = 'badge bg-indigo text-white';
            scopeBadge.style.color = '#ffffff !important';
            scopeBadge.textContent = '🏫 School Customized Report';
        }
    }

    const descEl = document.getElementById('selectedReportDesc');
    if (descEl) descEl.textContent = description || 'Standard report generated based on selected parameters.';

    // Update active parameters pills
    const pillsContainer = document.getElementById('activeParametersPills');
    if (pillsContainer) {
        pillsContainer.innerHTML = '';
        params.forEach(pKey => {
            const meta = availableParamsMeta[pKey];
            if (meta) {
                const pill = document.createElement('span');
                pill.className = 'badge bg-white text-dark border shadow-2xs py-1 px-2';
                pill.style.fontSize = '0.72rem';
                pill.textContent = `${meta.icon} ${meta.label}`;
                pillsContainer.appendChild(pill);
            }
        });
    }

    // Dynamic visibility of parameter input fields
    applyParameterVisibility(params);
}

/**
 * Show/Hide form parameter fields based on selected report's parameters
 */
function applyParameterVisibility(parameters) {
    const paramWrappers = {
        'level': document.getElementById('paramLevelWrapper'),
        'class': document.getElementById('paramClassWrapper'),
        'student': document.getElementById('paramStudentWrapper'),
        'subject': document.getElementById('paramSubjectWrapper'),
        'period': document.getElementById('paramPeriodWrapper'),
        'pass_mark': document.getElementById('paramPassMarkWrapper'),
        'format': document.getElementById('paramFormatWrapper')
    };

    Object.keys(paramWrappers).forEach(key => {
        const wrapper = paramWrappers[key];
        if (!wrapper) return;

        if (parameters.includes(key)) {
            wrapper.classList.remove('d-none');
        } else {
            wrapper.classList.add('d-none');
        }
    });
}

/**
 * Open and populate Edit Report modal for selected dropdown item
 */
function openEditReportModal() {
    const select = document.getElementById('reportSelectDropdown');
    const opt = select.options[select.selectedIndex];
    if (!opt) return;

    const id = opt.dataset.id;
    const title = opt.dataset.title;
    const category = opt.dataset.category;
    const desc = opt.dataset.description;
    const baseType = opt.dataset.baseType;
    const isGlobal = opt.dataset.isGlobal === '1';
    const format = opt.dataset.format;
    let params = [];
    try {
        params = JSON.parse(opt.dataset.parameters || '[]');
    } catch(e) {
        params = [];
    }

    document.getElementById('editReportForm').action = `/reports/templates/${id}`;
    document.getElementById('editReportTitle').value = title;
    document.getElementById('editReportCategory').value = category || '';
    document.getElementById('editReportDesc').value = desc || '';
    document.getElementById('editReportBaseType').value = baseType || 'card_single';
    document.getElementById('editReportFormat').value = format || 'view';

    document.getElementById('edit_scope_global').checked = isGlobal;
    document.getElementById('edit_scope_school').checked = !isGlobal;

    // Set checkboxes
    document.querySelectorAll('.param-check-edit').forEach(cb => {
        cb.checked = params.includes(cb.value);
    });

    const modal = new bootstrap.Modal(document.getElementById('modalEditReport'));
    modal.show();
}

/**
 * Open Deployment & Asset Management Studio for selected report
 */
function openDeployModal() {
    const select = document.getElementById('reportSelectDropdown');
    const opt = select.options[select.selectedIndex];
    if (!opt) return;

    const templateId = opt.dataset.id;
    const title = opt.dataset.title;

    document.getElementById('deployReportTitle').textContent = title;
    document.getElementById('deployUploadForm').action = `/reports/templates/${templateId}/deploy`;
    document.getElementById('btnDownloadBundleZip').href = `/reports/templates/${templateId}/download-bundle`;

    // Fetch deployment details via AJAX
    fetch(`/reports/templates/${templateId}/deployment`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            const dep = data.deployment;

            document.getElementById('deployReportDirectory').textContent = dep.directory;
            document.getElementById('deployTotalFilesBadge').textContent = `${dep.total_files} active files`;

            // Main file
            if (dep.main) {
                document.getElementById('deployMainFileName').textContent = dep.main.filename;
                document.getElementById('deployMainFileSize').textContent = `${dep.main.filesize} • Updated ${dep.main.updated_at}`;
                const badge = document.getElementById('deployMainStatusBadge');
                if (dep.main.is_compiled) {
                    badge.className = 'badge bg-success text-white';
                    badge.textContent = 'Compiled .jasper ✅';
                } else {
                    badge.className = 'badge bg-warning text-dark';
                    badge.textContent = 'Uncompiled .jrxml ⏳';
                }
            } else {
                document.getElementById('deployMainFileName').textContent = 'No primary template file';
                document.getElementById('deployMainFileSize').textContent = 'Upload a .jrxml report';
                document.getElementById('deployMainStatusBadge').className = 'badge bg-secondary text-white';
                document.getElementById('deployMainStatusBadge').textContent = 'Missing ⚠️';
            }

            // Subreports
            const subContainer = document.getElementById('deploySubreportsContainer');
            subContainer.innerHTML = '';
            if (dep.subreports && dep.subreports.length > 0) {
                dep.subreports.forEach(s => {
                    const div = document.createElement('div');
                    div.className = 'p-2 rounded border bg-white d-flex align-items-center justify-content-between shadow-2xs';
                    div.innerHTML = `
                        <div class="d-flex align-items-center gap-2">
                            <span>📑</span>
                            <div>
                                <strong class="small text-dark d-block font-monospace">${s.filename}</strong>
                                <small class="text-muted" style="font-size: 0.7rem;">${s.filesize} • ${s.updated_at}</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge ${s.is_compiled ? 'bg-success text-white' : 'bg-warning text-dark'} small" style="font-size: 0.65rem;">
                                ${s.is_compiled ? 'Compiled ✅' : 'Pending ⏳'}
                            </span>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none" onclick="deleteReportAsset(${templateId}, '${s.filename}', 'subreports')">🗑️</button>
                        </div>
                    `;
                    subContainer.appendChild(div);
                });
            } else {
                subContainer.innerHTML = '<div class="text-muted small fst-italic py-2 px-3 border rounded bg-light">No subreports deployed.</div>';
            }

            // Images & Media Assets
            const imgContainer = document.getElementById('deployImagesGallery');
            imgContainer.innerHTML = '';
            if (dep.images && dep.images.length > 0) {
                dep.images.forEach(img => {
                    const col = document.createElement('div');
                    col.className = 'col-md-6';
                    col.innerHTML = `
                        <div class="card p-2 border rounded shadow-2xs h-100 bg-white">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div style="width: 48px; height: 48px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    ${img.data_uri ? `<img src="${img.data_uri}" style="max-width: 100%; max-height: 100%; object-fit: contain;">` : '🖼️'}
                                </div>
                                <div class="overflow-hidden flex-grow-1">
                                    <div class="fw-bold small text-truncate text-dark" title="${img.filename}">${img.filename}</div>
                                    <small class="text-muted d-block" style="font-size: 0.7rem;">${img.dimensions || ''} • ${img.filesize}</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none" onclick="deleteReportAsset(${templateId}, '${img.filename}', 'images')" title="Delete asset">🗑️</button>
                            </div>
                            <div class="bg-light p-1 px-2 rounded border small font-monospace text-secondary" style="font-size: 0.68rem;" title="Jasper parameter mapping">
                                🔑 ${img.param_hint}
                            </div>
                        </div>
                    `;
                    imgContainer.appendChild(col);
                });
            } else {
                imgContainer.innerHTML = '<div class="col-12 text-muted small fst-italic py-2 px-3 border rounded bg-light">No image assets uploaded yet.</div>';
            }

            const modal = new bootstrap.Modal(document.getElementById('modalDeployReport'));
            modal.show();
        })
        .catch(err => alert('Could not load deployment metadata: ' + err));
}

/**
 * Recompile current package templates and subreports
 */
function recompileCurrentPackage() {
    const select = document.getElementById('reportSelectDropdown');
    const opt = select.options[select.selectedIndex];
    if (!opt) return;

    const templateId = opt.dataset.id;
    fetch(`/reports/templates/${templateId}/compile`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        openDeployModal(); // refresh view
    })
    .catch(err => alert('Compilation error: ' + err));
}

/**
 * Delete asset from package
 */
function deleteReportAsset(templateId, filename, category) {
    if (!confirm(`Are you sure you want to remove file "${filename}"?`)) return;

    fetch(`/reports/templates/${templateId}/assets`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ filename, category })
    })
    .then(r => r.json())
    .then(data => {
        openDeployModal();
    });
}

/**
 * Delete selected report from catalog
 */
function confirmDeleteReport() {
    const select = document.getElementById('reportSelectDropdown');
    const opt = select.options[select.selectedIndex];
    if (!opt) return;

    const id = opt.dataset.id;
    const title = opt.dataset.title || 'this report';

    if (confirm(`Are you sure you want to remove "${title}" from your reports catalog?`)) {
        const form = document.getElementById('deleteReportForm');
        form.action = `/reports/templates/${id}`;
        form.submit();
    }
}

/**
 * Toggle all checkboxes helper
 */
function toggleAllParams(type, check) {
    const selector = type === 'add' ? '.param-check-add' : '.param-check-edit';
    document.querySelectorAll(selector).forEach(cb => {
        cb.checked = check;
    });
}

/**
 * Preset parameter recommendations based on chosen base type
 */
function presetParametersForBaseType(baseType, formType) {
    let recommended = ['level', 'class', 'format'];
    switch (baseType) {
        case 'card_single':
            recommended = ['level', 'class', 'student', 'period', 'format'];
            break;
        case 'card_batch':
            recommended = ['level', 'class', 'period', 'format'];
            break;
        case 'analytics':
        case 'at_risk':
            recommended = ['level', 'class', 'period', 'pass_mark', 'format'];
            break;
        case 'broadsheet':
            recommended = ['level', 'class', 'period', 'subject', 'format'];
            break;
        case 'honor_roll':
            recommended = ['level', 'class', 'period', 'format'];
            break;
        case 'custom':
            recommended = ['level', 'class', 'student', 'subject', 'period', 'pass_mark', 'format'];
            break;
    }

    const selector = formType === 'add' ? '.param-check-add' : '.param-check-edit';
    document.querySelectorAll(selector).forEach(cb => {
        cb.checked = recommended.includes(cb.value);
    });
}

/**
 * Level change handler - updates dependent classes
 */
function handleLevelChange(levelId) {
    const classSelect = document.getElementById('generatorClassSelect');
    classSelect.innerHTML = '';

    const matching = levelId 
        ? allClassesData.filter(c => String(c.grade_id) === String(levelId))
        : allClassesData;

    if (matching.length === 0) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = '-- No Classes in this Level --';
        classSelect.appendChild(opt);
        return;
    }

    matching.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = `${c.class_name} (${c.grade_level ? c.grade_level.grade_code : 'CLS'}) - ${c.students ? c.students.length : 0} std`;
        classSelect.appendChild(opt);
    });

    handleClassChange(classSelect.value);
}

/**
 * Class change handler - updates dependent students
 */
function handleClassChange(classId) {
    if (!classId) return;
    const stdSelect = document.getElementById('generatorStudentSelect');
    stdSelect.innerHTML = '';

    const cls = allClassesData.find(c => String(c.id) === String(classId));
    if (cls && cls.students && cls.students.length > 0) {
        cls.students.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = `${s.full_name} (${s.admission_number})`;
            stdSelect.appendChild(opt);
        });
    } else {
        stdSelect.innerHTML = '<option value="">-- No students enrolled --</option>';
    }
}

// Initial initialization on DOM Load
document.addEventListener('DOMContentLoaded', function() {
    const reportSelect = document.getElementById('reportSelectDropdown');
    if (reportSelect) {
        onReportDropdownChange(reportSelect);
    }
});
</script>
@endsection
