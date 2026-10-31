@extends('layouts.app')

@section('title', 'Grading Templates & Hierarchy Customization')

@section('content')
<!-- Header & Status -->
<div class="card card-custom p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom pb-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary px-2 py-1 small">Grading Template Studio</span>
                <h4 class="fw-bold mb-0">Grading System Templates & Multi-Tier Hierarchy Customization</h4>
            </div>
            <p class="text-muted small mb-0">
                School: <strong>{{ $activeSchool->school_name }}</strong> • Add, remove, rename, and re-weight periods, sub-periods, categories, and sub-categories in real time.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-semibold">
                ✓ Dynamic 4-Tier Node Management & Weight Balance
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-xs mb-4" role="alert">
            <span class="fs-5">✓</span>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-xs mb-4" role="alert">
            <span class="fs-5">⚠️</span>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <h6 class="fw-bold mb-1">Validation Errors:</h6>
            <ul class="mb-0 small ps-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- 1. TEMPLATE SELECTION CAROUSEL / DECK -->
    <h6 class="fw-bold text-dark mb-3">1. Select or Activate a Grading Template</h6>
    <div class="row g-3 mb-4">
        @foreach($templates as $tmpl)
            <div class="col-lg-4">
                <div class="card h-100 rounded-3 border {{ $activeTemplate && $activeTemplate->id === $tmpl->id ? 'border-primary border-2 shadow-sm bg-primary-subtle bg-opacity-10' : 'border-slate-200 bg-white' }}">
                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge {{ $tmpl->is_active ? 'bg-success' : 'bg-secondary-subtle text-secondary' }} px-2 py-1 small">
                                    {{ $tmpl->is_active ? '● ACTIVE SYSTEM' : 'INACTIVE TEMPLATE' }}
                                </span>
                                <span class="badge bg-light text-dark border small">
                                    {{ $tmpl->scenario_depth === '2_TIER_MAIN_CAT' ? '2 Tiers' : ($tmpl->scenario_depth === '3_TIER_SUB_PERIOD_MAIN_CAT' ? '3 Tiers' : '4 Tiers') }}
                                </span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">{{ $tmpl->rule_name }}</h6>
                            <p class="small text-muted mb-3">{{ $tmpl->description }}</p>
                        </div>

                        <div class="border-top pt-2 mt-2 d-flex justify-content-between align-items-center">
                            <a href="{{ route('config.grading', ['template_id' => $tmpl->id]) }}" 
                               class="btn btn-sm {{ $activeTemplate && $activeTemplate->id === $tmpl->id ? 'btn-primary' : 'btn-outline-primary' }} fw-semibold px-3">
                                ✏️ {{ $activeTemplate && $activeTemplate->id === $tmpl->id ? 'Editing Template' : 'Edit Structure' }}
                            </a>

                            @if(!$tmpl->is_active)
                                <form action="{{ route('config.grading.activate_template', $tmpl->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success fw-bold">
                                        ✓ Use as Active
                                    </button>
                                </form>
                            @else
                                <span class="small fw-bold text-success">✓ Active for School</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- 2. ACTIVE TEMPLATE HIERARCHY EDITOR -->
    @if($activeTemplate)
    <div class="card border border-primary border-opacity-25 rounded-3 shadow-sm bg-white p-4 mb-4">
        <form action="{{ route('config.grading.update_hierarchy') }}" method="POST" id="hierarchyForm">
            @csrf
            <input type="hidden" name="rule_id" value="{{ $activeTemplate->id }}">

            <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-3 mb-4 gap-3">
                <div>
                    <span class="text-uppercase tracking-wider text-primary small fw-bold">Live Hierarchy Customizer</span>
                    <h5 class="fw-bold mb-0 text-dark">Customizing: {{ $activeTemplate->rule_name }}</h5>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="button" class="btn btn-outline-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalApplyGradingToSubjects">
                        🎓 Apply to Subjects
                    </button>
                    <button type="button" class="btn btn-success fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddMainPeriod">
                        + Add Main Grading Period
                    </button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                        💾 Save Changes to Names & Weights
                    </button>
                </div>
            </div>

            <!-- Template Header Parameters (Name, Pass Limit, Total Grade, Credits) -->
            <div class="row g-3 p-3 bg-light rounded-3 border mb-4">
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-dark">Template Name</label>
                    <input type="text" name="rule_name" class="form-control form-control-sm fw-bold bg-white" value="{{ $activeTemplate->rule_name }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-dark">Pass Limit</label>
                    <input type="number" step="0.5" name="pass_limit" class="form-control form-control-sm fw-bold bg-white text-center text-danger" value="{{ $activeTemplate->pass_limit }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-dark">Total Grade (Scale Max)</label>
                    <input type="number" step="1" name="total_grade" class="form-control form-control-sm fw-bold bg-white text-center text-primary" value="{{ $activeTemplate->total_grade }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-dark">Credit Hours</label>
                    <input type="number" step="0.5" name="credit_hours" class="form-control form-control-sm fw-bold bg-white text-center" value="{{ $activeTemplate->credit_hours }}" required>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- TEMPLATE CATEGORY BANK / MASTER LIST                           -->
            <!-- ============================================================== -->
            <div class="card border border-info border-opacity-50 rounded-3 mb-4 bg-white shadow-xs">
                <div class="card-header bg-info-subtle border-bottom d-flex flex-wrap justify-content-between align-items-center py-2 px-3 gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-5">📋</span>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Template Category Bank (List of Categories for this Template)</h6>
                            <small class="text-muted">Master pool of categories available to use across all grading periods and quarters in this template.</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modalApplyTemplateCategories">
                            ⚡ Apply List to Periods
                        </button>
                        <button type="button" class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalAddTemplateCategory">
                            ＋ Add to Category List
                        </button>
                    </div>
                </div>
                <div class="card-body p-3">
                    @if($activeTemplate->templateCategories->isEmpty())
                        <div class="text-center py-4 text-muted bg-light rounded-2 border border-dashed">
                            <span class="d-block mb-2">No categories defined in this template's category list yet.</span>
                            <button type="button" class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalAddTemplateCategory">
                                ＋ Add First Category to List
                            </button>
                        </div>
                    @else
                        <div class="row g-2">
                            @foreach($activeTemplate->templateCategories as $tCat)
                                <div class="col-md-6 col-xl-4">
                                    <div class="border rounded-2 p-2 bg-light d-flex justify-content-between align-items-center shadow-2xs">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <strong class="text-dark small">{{ $tCat->category_name }}</strong>
                                                <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">{{ $tCat->category_type }}</span>
                                            </div>
                                            <div class="small text-muted" style="font-size: 0.75rem;">
                                                Default Weight: <span class="fw-bold text-primary">{{ $tCat->default_weight }}%</span> • Max: {{ $tCat->default_max_score }}
                                                @if(!empty($tCat->sub_categories_json))
                                                    <span class="badge bg-info-subtle text-info ms-1">{{ count($tCat->sub_categories_json) }} Subs</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" 
                                                    onclick='openEditTemplateCategoryModal(@json($tCat))' title="Edit">
                                                ✏️
                                            </button>
                                            <a href="{{ route('config.grading.template_categories.delete', $tCat->id) }}" 
                                               class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.75rem;"
                                               onclick="return confirm('Remove category \'{{ addslashes($tCat->category_name) }}\' from template category list?')" title="Delete">
                                                🗑️
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-center border-top pt-2 mt-3 small text-muted">
                            <span>Available in Template: <strong>{{ $activeTemplate->templateCategories->count() }} Categories</strong></span>
                            <span>Combined Default Weight: <strong>{{ $activeTemplate->templateCategories->sum('default_weight') }}%</strong></span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Master Periods Balance Bar -->
            <div class="d-flex flex-wrap justify-content-between align-items-center p-2 mb-3 bg-white rounded border border-slate-200">
                <div class="d-flex align-items-center gap-2">
                    <span class="small fw-bold text-dark">Main Periods Weight Balance:</span>
                    <span id="mainPeriodsSumBadge" class="badge bg-secondary">Calculating...</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 small fw-semibold" onclick="equalizeMainPeriods()">
                        ⚡ Auto-Distribute Main Periods (100%)
                    </button>
                    <button type="button" class="btn btn-sm btn-primary py-1 px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#modalAddMainPeriod">
                        + Add Period
                    </button>
                </div>
            </div>

            <!-- Granular Hierarchy Customization Tree -->
            <div class="mb-4">
                @if($activeTemplate->periods->isEmpty())
                    <div class="text-center py-5 border border-dashed rounded-3 bg-light">
                        <span class="fs-1 d-block mb-2">📂</span>
                        <h6 class="fw-bold text-dark">No Grading Periods Defined Yet</h6>
                        <p class="small text-muted mb-3">Get started by creating your first Main Grading Period (e.g. Semester 1, Term 1, or Trimester 1).</p>
                        <button type="button" class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalAddMainPeriod">
                            + Add First Main Grading Period
                        </button>
                    </div>
                @endif

                @foreach($activeTemplate->periods as $pIdx => $mainPeriod)
                    <div class="border rounded-3 p-3 mb-4 bg-light bg-opacity-50" id="mainPeriodCard-{{ $mainPeriod->id }}">
                        <!-- LEVEL 1: MAIN PERIOD HEADER -->
                        <div class="p-2 bg-white rounded border border-primary border-opacity-50 mb-3 shadow-xs">
                            <div class="row g-2 align-items-center">
                                <div class="col-auto">
                                    <span class="badge bg-primary px-2 py-1">Main Period {{ $pIdx + 1 }}</span>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light fw-semibold small">Period Name</span>
                                        <input type="text" name="periods[{{ $mainPeriod->id }}][name]" 
                                               class="form-control fw-bold" 
                                               value="{{ $mainPeriod->period_name }}" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light fw-semibold small">Weight</span>
                                        <input type="number" step="0.01" name="periods[{{ $mainPeriod->id }}][weight]" 
                                               class="form-control fw-bold text-center text-primary main-period-weight" 
                                               value="{{ $mainPeriod->weight }}" required oninput="recalculateAll()">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-md-auto ms-auto d-flex align-items-center gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-info fw-semibold" 
                                            onclick="openUseCategoryModal({{ $mainPeriod->id }}, '{{ addslashes($mainPeriod->period_name) }}')">
                                        📋 Use from List
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" 
                                            onclick="openAddSubPeriodModal({{ $mainPeriod->id }}, '{{ addslashes($mainPeriod->period_name) }}')">
                                        + Sub-Period
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success fw-semibold" 
                                            onclick="openAddCategoryModal({{ $mainPeriod->id }}, '{{ addslashes($mainPeriod->period_name) }}')">
                                        + Category
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Remove Main Period"
                                            onclick="confirmDelete('period', {{ $mainPeriod->id }}, '{{ addslashes($mainPeriod->period_name) }}', 'Main Period (and all associated Quarters/Categories)')">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- IF HAS SUB-PERIODS (QUARTERS) -->
                        @if($mainPeriod->subPeriods->isNotEmpty())
                            <div class="ps-3 border-start border-3 border-primary border-opacity-25">
                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-white rounded border border-info border-opacity-50">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="small fw-bold text-dark">↳ Sub-Grading Periods under {{ $mainPeriod->period_name }}:</span>
                                        <span id="subPeriodsSumBadge-{{ $mainPeriod->id }}" class="badge bg-secondary">...</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 small" onclick="equalizeSubPeriods({{ $mainPeriod->id }})">
                                            ⚡ Distribute Sub-Periods (100%)
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary py-0 px-2 small fw-bold" onclick="openAddSubPeriodModal({{ $mainPeriod->id }}, '{{ addslashes($mainPeriod->period_name) }}')">
                                            + Add Sub-Period
                                        </button>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    @foreach($mainPeriod->subPeriods as $sIdx => $subPeriod)
                                        <div class="col-lg-6">
                                            <div class="card h-100 border border-slate-200 bg-white rounded-3 shadow-xs">
                                                <!-- LEVEL 2: SUB-GRADING PERIOD HEADER -->
                                                <div class="card-header bg-light p-2 border-bottom">
                                                    <div class="row g-2 align-items-center">
                                                        <div class="col-auto">
                                                            <span class="badge bg-info text-dark small">Sub-Period</span>
                                                        </div>
                                                        <div class="col">
                                                            <input type="text" name="periods[{{ $subPeriod->id }}][name]" 
                                                                   class="form-control form-control-sm fw-bold bg-white" 
                                                                   value="{{ $subPeriod->period_name }}" required>
                                                        </div>
                                                        <div class="col-auto">
                                                            <div class="input-group input-group-sm" style="width: 110px;">
                                                                <input type="number" step="0.01" name="periods[{{ $subPeriod->id }}][weight]" 
                                                                       class="form-control fw-bold text-center sub-period-weight" 
                                                                       data-parent="{{ $mainPeriod->id }}"
                                                                       value="{{ $subPeriod->weight }}" required oninput="recalculateAll()">
                                                                <span class="input-group-text py-0">%</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-auto d-flex align-items-center gap-1">
                                                            <button type="button" class="btn btn-sm btn-outline-info py-0 px-2" title="Use categories from template list"
                                                                    onclick="openUseCategoryModal({{ $subPeriod->id }}, '{{ addslashes($subPeriod->period_name) }}')">
                                                                📋 From List
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" title="Add Category under this Sub-Period"
                                                                    onclick="openAddCategoryModal({{ $subPeriod->id }}, '{{ addslashes($subPeriod->period_name) }}')">
                                                                + Cat
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" title="Remove Sub-Period"
                                                                    onclick="confirmDelete('period', {{ $subPeriod->id }}, '{{ addslashes($subPeriod->period_name) }}', 'Sub-Period and its Categories')">
                                                                🗑️
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- LEVEL 3: CATEGORIES UNDER SUB-PERIOD -->
                                                <div class="card-body p-2">
                                                    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                                                        <div class="d-flex align-items-center gap-1">
                                                            <span class="small fw-semibold text-muted">Categories:</span>
                                                            <span id="categoriesSumBadge-{{ $subPeriod->id }}" class="badge bg-secondary" style="font-size: 0.7rem;">...</span>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-1">
                                                            <button type="button" class="btn btn-outline-secondary py-0 px-1" style="font-size: 0.7rem;" onclick="equalizeCategories({{ $subPeriod->id }})">
                                                                ⚡ Distribute (100%)
                                                            </button>
                                                            <button type="button" class="btn btn-outline-info py-0 px-1" style="font-size: 0.7rem;" onclick="openUseCategoryModal({{ $subPeriod->id }}, '{{ addslashes($subPeriod->period_name) }}')">
                                                                📋 From List
                                                            </button>
                                                            <button type="button" class="btn btn-outline-primary py-0 px-1" style="font-size: 0.7rem;" onclick="openAddCategoryModal({{ $subPeriod->id }}, '{{ addslashes($subPeriod->period_name) }}')">
                                                                + Add Cat
                                                            </button>
                                                        </div>
                                                    </div>

                                                    @if($subPeriod->categories->isEmpty())
                                                        <div class="text-center py-3 bg-light rounded-2 border border-dashed text-muted small">
                                                            No categories yet. 
                                                            <a href="javascript:void(0)" class="fw-bold text-info me-2" onclick="openUseCategoryModal({{ $subPeriod->id }}, '{{ addslashes($subPeriod->period_name) }}')">
                                                                📋 Use from Template List
                                                            </a>
                                                            <a href="javascript:void(0)" class="fw-bold text-primary" onclick="openAddCategoryModal({{ $subPeriod->id }}, '{{ addslashes($subPeriod->period_name) }}')">
                                                                + Add Category
                                                            </a>
                                                        </div>
                                                    @endif
                                                    
                                                    @foreach($subPeriod->categories as $cIdx => $cat)
                                                        <div class="p-2 border rounded-2 mb-2 bg-light bg-opacity-75">
                                                            <div class="row g-1 align-items-center">
                                                                <div class="col">
                                                                    <input type="text" name="categories[{{ $cat->id }}][name]" 
                                                                           class="form-control form-control-sm fw-bold bg-white" 
                                                                           value="{{ $cat->category_name }}" required>
                                                                </div>
                                                                <div class="col-auto">
                                                                    <div class="input-group input-group-sm" style="width: 105px;">
                                                                        <input type="number" step="0.01" name="categories[{{ $cat->id }}][weight]" 
                                                                               class="form-control fw-bold text-center text-primary category-weight" 
                                                                               data-period="{{ $subPeriod->id }}"
                                                                               value="{{ $cat->weight }}" required oninput="recalculateAll()">
                                                                        <span class="input-group-text py-0">%</span>
                                                                    </div>
                                                                </div>
                                                                <div class="col-auto d-flex align-items-center gap-1">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1" title="Add Sub-Category"
                                                                            onclick="openAddSubCategoryModal({{ $cat->id }}, '{{ addslashes($cat->category_name) }}', {{ $subPeriod->id }})">
                                                                        + Sub
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" title="Remove Category"
                                                                            onclick="confirmDelete('category', {{ $cat->id }}, '{{ addslashes($cat->category_name) }}', 'Category')">
                                                                        🗑️
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <!-- LEVEL 4: SUB-CATEGORIES (IF ANY) -->
                                                            @if($cat->subCategories->isNotEmpty())
                                                                <div class="mt-2 ps-3 border-start border-2 border-primary border-opacity-25">
                                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                                        <div class="d-flex align-items-center gap-1">
                                                                            <span class="text-muted small fw-semibold" style="font-size: 0.7rem;">Sub-Categories:</span>
                                                                            <span id="subCategoriesSumBadge-{{ $cat->id }}" class="badge bg-secondary" style="font-size: 0.65rem;">...</span>
                                                                        </div>
                                                                        <button type="button" class="btn btn-outline-secondary py-0 px-1" style="font-size: 0.65rem;" onclick="equalizeSubCategories({{ $cat->id }})">
                                                                            ⚡ Distribute
                                                                        </button>
                                                                    </div>
                                                                    @foreach($cat->subCategories as $subCat)
                                                                        <div class="row g-1 align-items-center mt-1">
                                                                            <div class="col">
                                                                                <input type="text" name="categories[{{ $subCat->id }}][name]" 
                                                                                       class="form-control form-control-sm bg-white small py-0" 
                                                                                       value="{{ $subCat->category_name }}" required>
                                                                            </div>
                                                                            <div class="col-auto">
                                                                                <div class="input-group input-group-sm" style="width: 95px;">
                                                                                    <input type="number" step="0.01" name="categories[{{ $subCat->id }}][weight]" 
                                                                                           class="form-control small py-0 text-center fw-bold text-secondary sub-category-weight" 
                                                                                           data-parent-cat="{{ $cat->id }}"
                                                                                           value="{{ $subCat->weight }}" required oninput="recalculateAll()">
                                                                                    <span class="input-group-text py-0 small">%</span>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-auto">
                                                                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" title="Remove Sub-Category"
                                                                                        onclick="confirmDelete('category', {{ $subCat->id }}, '{{ addslashes($subCat->category_name) }}', 'Sub-Category')">
                                                                                    🗑️
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <!-- LEVEL 3: DIRECT CATEGORIES UNDER MAIN PERIOD (CASE 1) -->
                            <div class="ps-3 border-start border-3 border-primary border-opacity-25">
                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-white rounded border border-success border-opacity-50">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="small fw-bold text-dark">↳ Main Categories under {{ $mainPeriod->period_name }}:</span>
                                        <span id="categoriesSumBadge-{{ $mainPeriod->id }}" class="badge bg-secondary">...</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 small" onclick="equalizeCategories({{ $mainPeriod->id }})">
                                            ⚡ Distribute Categories (100%)
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info py-0 px-2 small fw-semibold" onclick="openUseCategoryModal({{ $mainPeriod->id }}, '{{ addslashes($mainPeriod->period_name) }}')">
                                            📋 Use from List
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 small fw-semibold" onclick="openAddSubPeriodModal({{ $mainPeriod->id }}, '{{ addslashes($mainPeriod->period_name) }}')">
                                            + Add Sub-Period
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary py-0 px-2 small fw-bold" onclick="openAddCategoryModal({{ $mainPeriod->id }}, '{{ addslashes($mainPeriod->period_name) }}')">
                                            + Add Category
                                        </button>
                                    </div>
                                </div>

                                @if($mainPeriod->categories->isEmpty())
                                    <div class="text-center py-4 bg-white rounded-3 border border-dashed text-muted mb-3">
                                        <p class="mb-2">No categories or sub-periods added under {{ $mainPeriod->period_name }}.</p>
                                        <button type="button" class="btn btn-sm btn-outline-info fw-semibold me-2" onclick="openUseCategoryModal({{ $mainPeriod->id }}, '{{ addslashes($mainPeriod->period_name) }}')">
                                            📋 Use from Category List
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary me-2" onclick="openAddCategoryModal({{ $mainPeriod->id }}, '{{ addslashes($mainPeriod->period_name) }}')">
                                            + Add Category
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="openAddSubPeriodModal({{ $mainPeriod->id }}, '{{ addslashes($mainPeriod->period_name) }}')">
                                            + Add Sub-Period (e.g. Quarters)
                                        </button>
                                    </div>
                                @endif

                                <div class="row g-3">
                                    @foreach($mainPeriod->categories as $cIdx => $cat)
                                        <div class="col-md-6">
                                            <div class="p-2 border rounded-2 bg-white shadow-xs">
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="text" name="categories[{{ $cat->id }}][name]" 
                                                           class="form-control form-control-sm fw-bold" 
                                                           value="{{ $cat->category_name }}" required>
                                                    <div class="input-group input-group-sm" style="width: 120px;">
                                                        <input type="number" step="0.01" name="categories[{{ $cat->id }}][weight]" 
                                                               class="form-control fw-bold text-center text-primary category-weight" 
                                                               data-period="{{ $mainPeriod->id }}"
                                                               value="{{ $cat->weight }}" required oninput="recalculateAll()">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1" title="Add Sub-Category"
                                                            onclick="openAddSubCategoryModal({{ $cat->id }}, '{{ addslashes($cat->category_name) }}', {{ $mainPeriod->id }})">
                                                        + Sub
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" title="Remove Category"
                                                            onclick="confirmDelete('category', {{ $cat->id }}, '{{ addslashes($cat->category_name) }}', 'Category')">
                                                        🗑️
                                                    </button>
                                                </div>

                                                <!-- SUB-CATEGORIES IF PRESENT IN FLAT MODE -->
                                                @if($cat->subCategories->isNotEmpty())
                                                    <div class="mt-2 ps-3 border-start border-2 border-primary border-opacity-25">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <div class="d-flex align-items-center gap-1">
                                                                <span class="text-muted small fw-semibold" style="font-size: 0.7rem;">Sub-Categories:</span>
                                                                <span id="subCategoriesSumBadge-{{ $cat->id }}" class="badge bg-secondary" style="font-size: 0.65rem;">...</span>
                                                            </div>
                                                            <button type="button" class="btn btn-outline-secondary py-0 px-1" style="font-size: 0.65rem;" onclick="equalizeSubCategories({{ $cat->id }})">
                                                                ⚡ Distribute
                                                            </button>
                                                        </div>
                                                        @foreach($cat->subCategories as $subCat)
                                                            <div class="row g-1 align-items-center mt-1">
                                                                <div class="col">
                                                                    <input type="text" name="categories[{{ $subCat->id }}][name]" 
                                                                           class="form-control form-control-sm bg-white small py-0" 
                                                                           value="{{ $subCat->category_name }}" required>
                                                                </div>
                                                                <div class="col-auto">
                                                                    <div class="input-group input-group-sm" style="width: 95px;">
                                                                        <input type="number" step="0.01" name="categories[{{ $subCat->id }}][weight]" 
                                                                               class="form-control small py-0 text-center fw-bold text-secondary sub-category-weight" 
                                                                               data-parent-cat="{{ $cat->id }}"
                                                                               value="{{ $subCat->weight }}" required oninput="recalculateAll()">
                                                                        <span class="input-group-text py-0 small">%</span>
                                                                    </div>
                                                                </div>
                                                                <div class="col-auto">
                                                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" title="Remove Sub-Category"
                                                                            onclick="confirmDelete('category', {{ $subCat->id }}, '{{ addslashes($subCat->category_name) }}', 'Sub-Category')">
                                                                        🗑️
                                                                    </button>
                                                                </div>
                                                            </div>
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
                @endforeach
            </div>

            <!-- Sticky Save Bar -->
            <div class="p-3 bg-light rounded-3 border d-flex flex-wrap justify-content-between align-items-center gap-3">
                <span class="small text-muted">
                    🛡️ <strong>Instant Verification:</strong> Saving updates all child weights and ensures mathematical parity across the report card engine.
                </span>
                <button type="submit" class="btn btn-primary px-4 fw-bold">
                    💾 Save Changes to Template Hierarchy & Weights
                </button>
            </div>
        </form>
    </div>
    @endif

    <!-- 3. ADDITIONAL DETAILS TABS (CALENDAR & COURSES) -->
    <div class="mt-4">
        <ul class="nav nav-tabs fw-semibold small mb-3" id="metaTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#metaCalendar">
                    📅 Academic Grading Period Dates
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link text-secondary" data-bs-toggle="tab" data-bs-target="#metaCourses">
                    🎯 Course Specific Thresholds & Credits
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link text-secondary" data-bs-toggle="tab" data-bs-target="#metaCutoffs">
                    📊 Letter Grade Scale & GPA Points
                </button>
            </li>
        </ul>

        <div class="tab-content" id="metaTabsContent">
            <!-- Calendar Tab -->
            <div class="tab-pane fade show active p-3 bg-white border border-top-0 rounded-bottom" id="metaCalendar">
                <h6 class="fw-bold mb-2">Grading Periods & Sub-Periods Calendar Scheduling</h6>
                <table class="table table-sm table-bordered text-center align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start">Main Period</th>
                            <th>Main Date Range</th>
                            <th class="text-start">Sub Grading Period</th>
                            <th>Sub Start Date</th>
                            <th>Sub End Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($calendarPeriods as $mainP)
                            @if($mainP->subPeriods->isNotEmpty())
                                @foreach($mainP->subPeriods as $sIdx => $subP)
                                <tr>
                                    @if($sIdx === 0)
                                        <td rowspan="{{ $mainP->subPeriods->count() }}" class="text-start fw-bold text-dark bg-light align-middle">
                                            {{ $mainP->period_name }} ({{ $mainP->weight }}%)
                                        </td>
                                        <td rowspan="{{ $mainP->subPeriods->count() }}" class="small text-muted bg-light align-middle">
                                            {{ $mainP->start_date ? $mainP->start_date->format('Y-m-d') : '2026-09-01' }} → {{ $mainP->end_date ? $mainP->end_date->format('Y-m-d') : '2027-01-20' }}
                                        </td>
                                    @endif
                                    <td class="text-start fw-semibold text-primary">{{ $subP->period_name }} ({{ $subP->weight }}%)</td>
                                    <td>{{ $subP->start_date ? $subP->start_date->format('Y-m-d') : '2026-09-01' }}</td>
                                    <td>{{ $subP->end_date ? $subP->end_date->format('Y-m-d') : '2026-11-10' }}</td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td class="text-start fw-bold text-dark">{{ $mainP->period_name }} ({{ $mainP->weight }}%)</td>
                                    <td colspan="4">{{ $mainP->start_date ? $mainP->start_date->format('Y-m-d') : '2026-09-01' }} → {{ $mainP->end_date ? $mainP->end_date->format('Y-m-d') : '2027-01-20' }}</td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No periods scheduled for this template.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Courses Tab -->
            <div class="tab-pane fade p-3 bg-white border border-top-0 rounded-bottom" id="metaCourses">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                    <div>
                        <h6 class="fw-bold mb-0">Course / Subject Specific Grading Systems & Thresholds</h6>
                        <small class="text-muted">Apply standard or custom grading templates, pass limits, and credits across catalog subjects.</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <form action="{{ route('config.grading.apply_to_subjects') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="rule_id" value="{{ $activeTemplate->id }}">
                            <input type="hidden" name="all_subjects" value="1">
                            <input type="hidden" name="cascade_to_classes" value="1">
                            <button type="submit" class="btn btn-sm btn-outline-primary fw-semibold" onclick="return confirm('Apply active template \'{{ addslashes($activeTemplate->rule_name) }}\' across ALL subjects?')">
                                ⚡ Apply Active Template to All Subjects
                            </button>
                        </form>
                        <button type="button" class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalApplyGradingToSubjects">
                            🎓 Apply Grading System to Subject(s)
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start" style="width: 100px;">Code</th>
                                <th class="text-start">Course / Subject Name</th>
                                <th>Department</th>
                                <th>Applied Grading System</th>
                                <th>Pass Limit</th>
                                <th>Total Grade Scale</th>
                                <th>Credit Hours</th>
                                <th>Batch Allocations</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($masterSubjects as $subj)
                            <tr>
                                <td class="text-start">
                                    <span class="badge bg-primary px-2 py-1">{{ $subj->subject_code }}</span>
                                </td>
                                <td class="text-start">
                                    <a href="{{ route('config.subjects.profile', $subj->id) }}" class="fw-bold text-dark text-decoration-none hover-primary fs-6">
                                        {{ $subj->subject_name }} ↗
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border">{{ $subj->department ?? 'General' }}</span>
                                </td>
                                <td>
                                    @if($subj->gradingRule)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">
                                            ✓ {{ $subj->gradingRule->rule_name }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-muted">
                                            ● School Default ({{ $activeTemplate->rule_name }})
                                        </span>
                                    @endif
                                </td>
                                <td class="fw-bold text-danger">
                                    {{ number_format($subj->pass_limit ?? ($subj->gradingRule->pass_limit ?? $activeTemplate->pass_limit), 2) }}
                                </td>
                                <td class="fw-bold text-primary">
                                    {{ number_format($subj->max_score ?? ($subj->gradingRule->total_grade ?? $activeTemplate->total_grade), 2) }}
                                </td>
                                <td class="fw-bold">
                                    {{ number_format($subj->default_credits ?? ($subj->gradingRule->credit_hours ?? $activeTemplate->credit_hours), 2) }}
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $subj->assignments->count() }} Batches
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('config.subjects.profile', $subj->id) }}" class="btn btn-outline-dark py-0 px-2 fw-semibold" title="View Subject Profile">
                                            👁️ Profile
                                        </a>
                                        <button type="button" class="btn btn-outline-primary py-0 px-2 fw-semibold" 
                                                onclick="openApplyGradingForSingleSubject({{ $subj->id }}, '{{ addslashes($subj->subject_name) }}', {{ $subj->grading_rule_id ?? $activeTemplate->id }})">
                                            🎓 Apply
                                        </button>
                                        @if($subj->grading_rule_id)
                                            <a href="{{ route('config.grading.subjects.reset', $subj->id) }}" 
                                               class="btn btn-outline-secondary py-0 px-2" title="Reset to School Default"
                                               onclick="return confirm('Reset {{ addslashes($subj->subject_name) }} to school default grading template?')">
                                                ↺
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No master subjects created yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Cutoffs Tab -->
            <div class="tab-pane fade p-3 bg-white border border-top-0 rounded-bottom" id="metaCutoffs">
                <h6 class="fw-bold mb-2">Cutoffs Scale</h6>
                <table class="table table-sm table-striped text-center small align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Grade</th><th>Min %</th><th>Max %</th><th>GPA Points</th><th>Standing</th></tr>
                    </thead>
                    <tbody>
                        @foreach($baseGrading->cutoffs_json ?? [] as $cutoff)
                        <tr>
                            <td class="fw-bold text-primary">{{ $cutoff['grade'] }}</td>
                            <td>{{ $cutoff['min'] }}%</td>
                            <td>{{ $cutoff['max'] }}%</td>
                            <td class="fw-bold">{{ number_format($cutoff['gpa'], 2) }}</td>
                            <td class="text-success fw-semibold">{{ $cutoff['label'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ==================================================================== -->
<!-- MODALS FOR DYNAMIC ADD & REMOVE                                       -->
<!-- ==================================================================== -->

@if($activeTemplate)
<!-- 1. MODAL: ADD MAIN GRADING PERIOD -->
<div class="modal fade" id="modalAddMainPeriod" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.grading.periods.add') }}" method="POST">
                @csrf
                <input type="hidden" name="rule_id" value="{{ $activeTemplate->id }}">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">＋ Add Main Grading Period</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Period Name <span class="text-danger">*</span></label>
                        <input type="text" name="period_name" class="form-control" placeholder="e.g. Semester 3, Trimester 3, Summer Session" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Weight % <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="weight" class="form-control" value="50.00" required min="0" max="100">
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted">Percentage contribution towards the yearly academic average.</small>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Start Date</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="2026-09-01">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">End Date</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="2027-01-20">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Add Main Period</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. MODAL: ADD SUB-GRADING PERIOD -->
<div class="modal fade" id="modalAddSubPeriod" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.grading.periods.add') }}" method="POST">
                @csrf
                <input type="hidden" name="rule_id" value="{{ $activeTemplate->id }}">
                <input type="hidden" name="parent_period_id" id="subPeriodParentPeriodId" value="">
                <div class="modal-header bg-info text-dark">
                    <h5 class="modal-title fw-bold">＋ Add Sub-Grading Period</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border small py-2 mb-3">
                        Parent Period: <strong id="subPeriodParentPeriodName" class="text-primary">-</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Sub-Period Name <span class="text-danger">*</span></label>
                        <input type="text" name="period_name" class="form-control" placeholder="e.g. Quarter 3, Mid-Term 1, Interim Assessment" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Weight % within Parent Period <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="weight" class="form-control" value="50.00" required min="0" max="100">
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted">Weight percentage within the parent main period (Sum must equal 100%).</small>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Start Date</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="2026-09-01">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">End Date</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="2026-11-10">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info fw-bold">Add Sub-Period</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. MODAL: ADD CATEGORY -->
<div class="modal fade" id="modalAddCategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.grading.categories.add') }}" method="POST">
                @csrf
                <input type="hidden" name="rule_id" value="{{ $activeTemplate->id }}">
                <input type="hidden" name="grading_period_id" id="catGradingPeriodId" value="">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">＋ Add Grading Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border small py-2 mb-3">
                        Belongs to Period: <strong id="catGradingPeriodName" class="text-success">-</strong>
                    </div>

                    @if($activeTemplate->templateCategories->isNotEmpty())
                    <div class="mb-3 p-2 bg-light rounded border">
                        <label class="form-label small fw-bold text-dark mb-1">📋 Or Pick from Template Category List:</label>
                        <select class="form-select form-select-sm" id="catQuickSelectTemplate" onchange="fillCategoryFromTemplate(this)">
                            <option value="">-- Select from template list (or enter custom below) --</option>
                            @foreach($activeTemplate->templateCategories as $tCat)
                                <option value="{{ $tCat->id }}" 
                                        data-name="{{ $tCat->category_name }}" 
                                        data-weight="{{ $tCat->default_weight }}" 
                                        data-type="{{ $tCat->category_type }}" 
                                        data-max="{{ $tCat->default_max_score }}">
                                    {{ $tCat->category_name }} ({{ $tCat->default_weight }}%) - {{ $tCat->category_type }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="template_category_id" id="catTemplateCategoryId" value="">
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="category_name" id="modalCatName" class="form-control" placeholder="e.g. Quizzes, Homework, Midterm Exam, Projects, Cat6" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label small fw-bold text-dark">Type / Role</label>
                            <select name="category_type" id="modalCatType" class="form-select">
                                <option value="CLASSWORK">Classwork / Engagement</option>
                                <option value="HOMEWORK">Homework / Assignments</option>
                                <option value="QUIZ">Quizzes / Short Tests</option>
                                <option value="EXAM">Terminal / Midterm Exam</option>
                                <option value="PROJECT">Projects / Practical Labs</option>
                                <option value="PARTICIPATION">Attendance / Participation</option>
                                <option value="CUSTOM" selected>Custom Category</option>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label small fw-bold text-dark">Weight % <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="weight" id="modalCatWeight" class="form-control" value="20.00" required min="0" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-dark">Max Assessment Score</label>
                        <input type="number" step="1" name="max_score" id="modalCatMaxScore" class="form-control" value="100" required min="1">
                        <small class="text-muted">Standard denominator for raw mark conversion (e.g. 100, 50, 20).</small>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 4. MODAL: ADD SUB-CATEGORY -->
<div class="modal fade" id="modalAddSubCategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.grading.categories.add') }}" method="POST">
                @csrf
                <input type="hidden" name="rule_id" value="{{ $activeTemplate->id }}">
                <input type="hidden" name="grading_period_id" id="subCatPeriodId" value="">
                <input type="hidden" name="parent_category_id" id="subCatParentCatId" value="">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">＋ Add Sub-Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border small py-2 mb-3">
                        Parent Category: <strong id="subCatParentCatName" class="text-primary">-</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Sub-Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="category_name" class="form-control" placeholder="e.g. Quiz 1, Pop Quiz, Practical Lab 1, HW 1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Weight % within Parent Category <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="weight" class="form-control" value="25.00" required min="0" max="100">
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted">Weight percentage within the parent category (Sum of sub-categories must equal 100%).</small>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-dark">Max Assessment Score</label>
                        <input type="number" step="1" name="max_score" class="form-control" value="100" required min="1">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark fw-bold">Add Sub-Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 5. REUSABLE MODAL: CONFIRM DELETION -->
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
                        ⚡ <strong>Cascading Warning:</strong> Any sub-periods, child categories, sub-categories, and student marks under this node will also be deleted.
                    </p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold">Yes, Delete Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 6. MODAL: ADD TEMPLATE CATEGORY (TO MASTER LIST) -->
<div class="modal fade" id="modalAddTemplateCategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.grading.template_categories.add') }}" method="POST">
                @csrf
                <input type="hidden" name="rule_id" value="{{ $activeTemplate->id }}">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">＋ Add Category to Template Bank</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="category_name" class="form-control" placeholder="e.g. Quizzes, Homework, Midterm Exam, Projects, Cat6" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label small fw-bold text-dark">Category Type / Role</label>
                            <select name="category_type" class="form-select">
                                <option value="CLASSWORK">Classwork / Engagement</option>
                                <option value="HOMEWORK">Homework / Assignments</option>
                                <option value="QUIZ">Quizzes / Short Tests</option>
                                <option value="EXAM">Terminal / Midterm Exam</option>
                                <option value="PROJECT">Projects / Practical Labs</option>
                                <option value="PARTICIPATION">Attendance / Participation</option>
                                <option value="CUSTOM" selected>Custom Category</option>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label small fw-bold text-dark">Default Weight % <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="default_weight" class="form-control text-center fw-bold" value="20.00" required min="0" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Default Max Score</label>
                        <input type="number" step="1" name="default_max_score" class="form-control" value="100" required min="1">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-dark">Sub-Categories Blueprint (Optional)</label>
                        <textarea name="sub_categories_text" class="form-control small" rows="2" placeholder="e.g. Sub1 (25%), Sub2 (20%), Sub3 (30%), Sub4 (25%)"></textarea>
                        <small class="text-muted">Separate by commas. When used in a period, these sub-categories will be auto-generated.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Add to Category List</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 7. MODAL: EDIT TEMPLATE CATEGORY -->
<div class="modal fade" id="modalEditTemplateCategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="formEditTemplateCategory" method="POST" action="">
                @csrf
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title fw-bold">✏️ Edit Category in Template List</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="category_name" id="editTemplateCatName" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label small fw-bold text-dark">Category Type / Role</label>
                            <select name="category_type" id="editTemplateCatType" class="form-select">
                                <option value="CLASSWORK">Classwork / Engagement</option>
                                <option value="HOMEWORK">Homework / Assignments</option>
                                <option value="QUIZ">Quizzes / Short Tests</option>
                                <option value="EXAM">Terminal / Midterm Exam</option>
                                <option value="PROJECT">Projects / Practical Labs</option>
                                <option value="PARTICIPATION">Attendance / Participation</option>
                                <option value="CUSTOM">Custom Category</option>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label small fw-bold text-dark">Default Weight % <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="default_weight" id="editTemplateCatWeight" class="form-control text-center fw-bold" required min="0" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Default Max Score</label>
                        <input type="number" step="1" name="default_max_score" id="editTemplateCatMaxScore" class="form-control" required min="1">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-dark">Sub-Categories Blueprint (Optional)</label>
                        <textarea name="sub_categories_text" id="editTemplateCatSubs" class="form-control small" rows="2" placeholder="e.g. Sub1 (25%), Sub2 (20%), Sub3 (30%), Sub4 (25%)"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-secondary fw-bold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 8. MODAL: USE CATEGORIES FROM LIST IN PERIOD -->
<div class="modal fade" id="modalUseCategoryInPeriod" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.grading.template_categories.use_in_period') }}" method="POST">
                @csrf
                <input type="hidden" name="rule_id" value="{{ $activeTemplate->id }}">
                <input type="hidden" name="grading_period_id" id="useCatPeriodId" value="">
                <div class="modal-header bg-info text-dark">
                    <div>
                        <h5 class="modal-title fw-bold mb-0">📋 Use Categories from Template List</h5>
                        <small class="text-muted">Target Period: <strong id="useCatPeriodName" class="text-primary">-</strong></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Select one or more categories from the template's master list to immediately add them into this grading period.
                    </p>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-bold text-dark">Available Categories:</span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="selectAllUseCats(true)">Select All</button>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="selectAllUseCats(false)">Deselect All</button>
                        </div>
                    </div>

                    <div class="border rounded-3 p-2 bg-light mb-3" style="max-height: 280px; overflow-y: auto;">
                        @forelse($activeTemplate->templateCategories as $tCat)
                            <div class="card p-2 mb-2 border rounded-2 bg-white shadow-2xs">
                                <label class="d-flex align-items-center justify-content-between cursor-pointer mb-0">
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="checkbox" name="template_category_ids[]" value="{{ $tCat->id }}" class="form-check-input use-cat-checkbox" checked>
                                        <div>
                                            <strong class="text-dark small">{{ $tCat->category_name }}</strong>
                                            <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size: 0.65rem;">{{ $tCat->category_type }}</span>
                                            @if(!empty($tCat->sub_categories_json))
                                                <span class="badge bg-info-subtle text-info ms-1" style="font-size: 0.65rem;">
                                                    ↳ {{ count($tCat->sub_categories_json) }} Sub-Categories
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-primary-subtle text-primary fw-bold" style="font-size: 0.75rem;">{{ $tCat->default_weight }}%</span>
                                        <span class="text-muted small ms-1">(Max {{ $tCat->default_max_score }})</span>
                                    </div>
                                </label>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted">
                                No categories defined in this template's category list yet.
                            </div>
                        @endforelse
                    </div>

                    <div class="p-2 bg-light rounded border small text-muted">
                        💡 <em>Tip: You can rebalance weights or adjust individual categories at any time using the ⚡ Auto-Distribute or input fields.</em>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-dark fw-bold">✓ Add Selected to Grading Period</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 9. MODAL: BATCH APPLY TEMPLATE CATEGORIES ACROSS PERIODS -->
<div class="modal fade" id="modalApplyTemplateCategories" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.grading.template_categories.apply_to_periods') }}" method="POST">
                @csrf
                <input type="hidden" name="rule_id" value="{{ $activeTemplate->id }}">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">⚡ Apply Category List to Periods</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Choose which grading periods (or quarters) should receive all categories from the template's master list.
                    </p>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Target Periods in this Template:</label>
                        <div class="border rounded-2 p-2 bg-light" style="max-height: 200px; overflow-y: auto;">
                            @php
                                $targetPeriods = [];
                                foreach ($activeTemplate->periods as $mp) {
                                    if ($mp->subPeriods->isNotEmpty()) {
                                        foreach ($mp->subPeriods as $sp) {
                                            $targetPeriods[] = ['id' => $sp->id, 'name' => $mp->period_name . ' → ' . $sp->period_name];
                                        }
                                    } else {
                                        $targetPeriods[] = ['id' => $mp->id, 'name' => $mp->period_name];
                                    }
                                }
                            @endphp
                            @foreach($targetPeriods as $tp)
                                <div class="form-check mb-1">
                                    <input type="checkbox" name="period_ids[]" value="{{ $tp['id'] }}" class="form-check-input apply-period-checkbox" checked id="tp-{{ $tp['id'] }}">
                                    <label class="form-check-label small fw-semibold text-dark" for="tp-{{ $tp['id'] }}">
                                        {{ $tp['name'] }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Application Mode:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mode" id="modeAddMissing" value="add_missing" checked>
                            <label class="form-check-label small text-dark fw-semibold" for="modeAddMissing">
                                Add missing categories only (Preserves existing customized categories)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mode" id="modeReplace" value="replace">
                            <label class="form-check-label small text-danger fw-semibold" for="modeReplace">
                                Reset & Replace existing categories with the complete Template List
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Apply Categories to Periods</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 10. MODAL: APPLY GRADING SYSTEM TO SUBJECTS -->
<div class="modal fade" id="modalApplyGradingToSubjects" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('config.grading.apply_to_subjects') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">🎓 Apply Grading System to Subjects</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Choose which grading system template to bind to your school's master subjects. This controls their calculation breakdown, passing mark, and total scale.
                    </p>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-dark">Grading System Template <span class="text-danger">*</span></label>
                            <select name="rule_id" class="form-select" id="applyRuleSelect" required>
                                @foreach($templates as $tmpl)
                                    <option value="{{ $tmpl->id }}" {{ $activeTemplate && $activeTemplate->id === $tmpl->id ? 'selected' : '' }}>
                                        {{ $tmpl->rule_name }} (Scale: {{ $tmpl->total_grade }}, Pass: {{ $tmpl->pass_limit }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Pass Limit Override</label>
                            <div class="input-group">
                                <input type="number" step="0.5" name="pass_limit" id="applyPassLimit" class="form-control text-center" placeholder="e.g. 60.00">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted" style="font-size: 0.7rem;">Leave empty to inherit template pass limit.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark d-block">Target Subjects:</label>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="all_subjects" id="targetAllSubj" value="1" checked onchange="toggleSubjectList(false)">
                                <label class="form-check-label small fw-bold text-dark" for="targetAllSubj">
                                    All Master Subjects ({{ $masterSubjects->count() }} Subjects)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="all_subjects" id="targetSpecificSubj" value="0" onchange="toggleSubjectList(true)">
                                <label class="form-check-label small fw-bold text-dark" for="targetSpecificSubj">
                                    Select Specific Subjects
                                </label>
                            </div>
                        </div>

                        <div id="subjectCheckboxesContainer" class="border rounded-2 p-3 bg-light d-none" style="max-height: 220px; overflow-y: auto;">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                <span class="small text-muted">Select subjects:</span>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="selectAllApplySubjects(true)">All</button>
                                    <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="selectAllApplySubjects(false)">None</button>
                                </div>
                            </div>
                            <div class="row g-2">
                                @foreach($masterSubjects as $s)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" name="subject_ids[]" value="{{ $s->id }}" class="form-check-input apply-subj-checkbox" id="subj-check-{{ $s->id }}">
                                            <label class="form-check-label small text-dark" for="subj-check-{{ $s->id }}">
                                                <strong>{{ $s->subject_code }}</strong> - {{ $s->subject_name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="form-check p-2 bg-light rounded border">
                        <input class="form-check-input ms-1" type="checkbox" name="cascade_to_classes" value="1" id="cascadeToClasses" checked>
                        <label class="form-check-label small fw-semibold text-dark ms-2" for="cascadeToClasses">
                            ✓ Also cascade this grading system to all existing Class/Batch allocations for these subjects
                        </label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">✓ Apply Grading System</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- ==================================================================== -->
<!-- REAL-TIME WEIGHT BALANCE & MODAL CONTROLLERS SCRIPT                   -->
<!-- ==================================================================== -->
<script>
    // 1. Modal Triggers with Context Passing
    function openAddSubPeriodModal(parentPeriodId, parentPeriodName) {
        document.getElementById('subPeriodParentPeriodId').value = parentPeriodId;
        document.getElementById('subPeriodParentPeriodName').textContent = parentPeriodName;
        const modal = new bootstrap.Modal(document.getElementById('modalAddSubPeriod'));
        modal.show();
    }

    function openAddCategoryModal(periodId, periodName) {
        document.getElementById('catGradingPeriodId').value = periodId;
        document.getElementById('catGradingPeriodName').textContent = periodName;
        const quickSelect = document.getElementById('catQuickSelectTemplate');
        if (quickSelect) quickSelect.selectedIndex = 0;
        const hiddenId = document.getElementById('catTemplateCategoryId');
        if (hiddenId) hiddenId.value = '';
        const modal = new bootstrap.Modal(document.getElementById('modalAddCategory'));
        modal.show();
    }

    function openUseCategoryModal(periodId, periodName) {
        document.getElementById('useCatPeriodId').value = periodId;
        document.getElementById('useCatPeriodName').textContent = periodName;
        selectAllUseCats(true);
        const modal = new bootstrap.Modal(document.getElementById('modalUseCategoryInPeriod'));
        modal.show();
    }

    function selectAllUseCats(checked) {
        document.querySelectorAll('.use-cat-checkbox').forEach(cb => cb.checked = checked);
    }

    function openEditTemplateCategoryModal(item) {
        const form = document.getElementById('formEditTemplateCategory');
        form.action = `/configuration/grading/template-categories/${item.id}/update`;
        document.getElementById('editTemplateCatName').value = item.category_name || '';
        document.getElementById('editTemplateCatType').value = item.category_type || 'CUSTOM';
        document.getElementById('editTemplateCatWeight').value = item.default_weight || 20.00;
        document.getElementById('editTemplateCatMaxScore').value = item.default_max_score || 100;
        
        let subsText = '';
        if (item.sub_categories_json && Array.isArray(item.sub_categories_json)) {
            subsText = item.sub_categories_json.map(s => `${s.name} (${s.weight}%)`).join(', ');
        }
        document.getElementById('editTemplateCatSubs').value = subsText;

        const modal = new bootstrap.Modal(document.getElementById('modalEditTemplateCategory'));
        modal.show();
    }

    function fillCategoryFromTemplate(select) {
        const selected = select.options[select.selectedIndex];
        if (!selected || !selected.value) {
            const hiddenId = document.getElementById('catTemplateCategoryId');
            if (hiddenId) hiddenId.value = '';
            return;
        }
        const hiddenId = document.getElementById('catTemplateCategoryId');
        if (hiddenId) hiddenId.value = selected.value;
        const nameInput = document.getElementById('modalCatName');
        if (nameInput) nameInput.value = selected.getAttribute('data-name') || '';
        const typeSelect = document.getElementById('modalCatType');
        if (typeSelect) typeSelect.value = selected.getAttribute('data-type') || 'CUSTOM';
        const weightInput = document.getElementById('modalCatWeight');
        if (weightInput) weightInput.value = selected.getAttribute('data-weight') || '20.00';
        const maxInput = document.getElementById('modalCatMaxScore');
        if (maxInput) maxInput.value = selected.getAttribute('data-max') || '100';
    }

    function toggleSubjectList(show) {
        const container = document.getElementById('subjectCheckboxesContainer');
        if (container) {
            if (show) {
                container.classList.remove('d-none');
            } else {
                container.classList.add('d-none');
            }
        }
    }

    function selectAllApplySubjects(checked) {
        document.querySelectorAll('.apply-subj-checkbox').forEach(cb => cb.checked = checked);
    }

    function openApplyGradingForSingleSubject(subjectId, subjectName, currentRuleId) {
        const radioSpecific = document.getElementById('targetSpecificSubj');
        if (radioSpecific) radioSpecific.checked = true;
        toggleSubjectList(true);
        selectAllApplySubjects(false);
        const targetCb = document.getElementById(`subj-check-${subjectId}`);
        if (targetCb) targetCb.checked = true;
        const ruleSelect = document.getElementById('applyRuleSelect');
        if (ruleSelect && currentRuleId) ruleSelect.value = currentRuleId;
        const modal = new bootstrap.Modal(document.getElementById('modalApplyGradingToSubjects'));
        modal.show();
    }

    function openAddSubCategoryModal(parentCatId, parentCatName, periodId) {
        document.getElementById('subCatParentCatId').value = parentCatId;
        document.getElementById('subCatParentCatName').textContent = parentCatName;
        document.getElementById('subCatPeriodId').value = periodId;
        const modal = new bootstrap.Modal(document.getElementById('modalAddSubCategory'));
        modal.show();
    }

    function confirmDelete(type, id, name, typeLabel) {
        const form = document.getElementById('deleteForm');
        if (type === 'period') {
            form.action = `/configuration/grading/periods/${id}/delete`;
        } else {
            form.action = `/configuration/grading/categories/${id}/delete`;
        }
        document.getElementById('deleteItemName').textContent = name;
        document.getElementById('deleteItemType').textContent = `Type: ${typeLabel}`;
        const modal = new bootstrap.Modal(document.getElementById('modalConfirmDelete'));
        modal.show();
    }

    // 2. Real-Time Balance Meters & Auto-Equalizers
    function updateBadge(badgeElement, sum) {
        if (!badgeElement) return;
        const rounded = Math.round(sum * 100) / 100;
        if (Math.abs(rounded - 100.0) < 0.05) {
            badgeElement.className = 'badge bg-success-subtle text-success border border-success-subtle fw-bold';
            badgeElement.innerHTML = `✓ 100.00% (Balanced)`;
        } else if (rounded < 100.0) {
            const diff = (100.0 - rounded).toFixed(2);
            badgeElement.className = 'badge bg-warning-subtle text-dark border border-warning-subtle fw-bold';
            badgeElement.innerHTML = `⚠️ ${rounded.toFixed(2)}% (Needs +${diff}%)`;
        } else {
            const diff = (rounded - 100.0).toFixed(2);
            badgeElement.className = 'badge bg-danger-subtle text-danger border border-danger-subtle fw-bold';
            badgeElement.innerHTML = `⚠️ ${rounded.toFixed(2)}% (Over by +${diff}%)`;
        }
    }

    function recalculateAll() {
        // A. Main Periods
        const mainPeriodInputs = document.querySelectorAll('.main-period-weight');
        let mainSum = 0;
        mainPeriodInputs.forEach(input => {
            mainSum += parseFloat(input.value) || 0;
        });
        updateBadge(document.getElementById('mainPeriodsSumBadge'), mainSum);

        // B. Sub-Periods grouped by parent
        const subPeriodInputs = document.querySelectorAll('.sub-period-weight');
        const subGroupSums = {};
        subPeriodInputs.forEach(input => {
            const parentId = input.getAttribute('data-parent');
            subGroupSums[parentId] = (subGroupSums[parentId] || 0) + (parseFloat(input.value) || 0);
        });
        for (const [parentId, sum] of Object.entries(subGroupSums)) {
            updateBadge(document.getElementById(`subPeriodsSumBadge-${parentId}`), sum);
        }

        // C. Categories grouped by period
        const catInputs = document.querySelectorAll('.category-weight');
        const catGroupSums = {};
        catInputs.forEach(input => {
            const periodId = input.getAttribute('data-period');
            catGroupSums[periodId] = (catGroupSums[periodId] || 0) + (parseFloat(input.value) || 0);
        });
        for (const [periodId, sum] of Object.entries(catGroupSums)) {
            updateBadge(document.getElementById(`categoriesSumBadge-${periodId}`), sum);
        }

        // D. Sub-Categories grouped by parent category
        const subCatInputs = document.querySelectorAll('.sub-category-weight');
        const subCatGroupSums = {};
        subCatInputs.forEach(input => {
            const parentCatId = input.getAttribute('data-parent-cat');
            subCatGroupSums[parentCatId] = (subCatGroupSums[parentCatId] || 0) + (parseFloat(input.value) || 0);
        });
        for (const [catId, sum] of Object.entries(subCatGroupSums)) {
            updateBadge(document.getElementById(`subCategoriesSumBadge-${catId}`), sum);
        }
    }

    function distributeEqualWeights(elements) {
        if (!elements || elements.length === 0) return;
        const count = elements.length;
        const base = Math.floor((100.0 / count) * 100) / 100;
        let remainder = Math.round((100.0 - (base * count)) * 100) / 100;

        elements.forEach((el, idx) => {
            let val = base;
            if (idx === 0 && remainder !== 0) {
                val = Math.round((val + remainder) * 100) / 100;
            }
            el.value = val.toFixed(2);
        });
        recalculateAll();
    }

    function equalizeMainPeriods() {
        const inputs = Array.from(document.querySelectorAll('.main-period-weight'));
        distributeEqualWeights(inputs);
    }

    function equalizeSubPeriods(parentId) {
        const inputs = Array.from(document.querySelectorAll(`.sub-period-weight[data-parent="${parentId}"]`));
        distributeEqualWeights(inputs);
    }

    function equalizeCategories(periodId) {
        const inputs = Array.from(document.querySelectorAll(`.category-weight[data-period="${periodId}"]`));
        distributeEqualWeights(inputs);
    }

    function equalizeSubCategories(parentCatId) {
        const inputs = Array.from(document.querySelectorAll(`.sub-category-weight[data-parent-cat="${parentCatId}"]`));
        distributeEqualWeights(inputs);
    }

    // Run on initialization
    document.addEventListener('DOMContentLoaded', function() {
        recalculateAll();
    });
</script>
@endsection
