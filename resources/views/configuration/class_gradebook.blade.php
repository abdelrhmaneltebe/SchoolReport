@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3 max-w-7xl">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('config.levels') }}" class="text-decoration-none">Levels & Classes</a></li>
            <li class="breadcrumb-item"><a href="{{ route('config.classes.profile', $class->id) }}" class="text-decoration-none">{{ $class->class_name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">📖 Batch Gradebook</li>
        </ol>
    </nav>

    <!-- Header Section -->
    <div class="card-custom p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 text-white p-3 text-center shadow-xs" style="min-width: 80px; background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);">
                    <span class="fs-5 fw-bold d-block">{{ $class->gradeLevel->grade_code ?? 'CLS' }}</span>
                    <span class="badge bg-white text-primary fw-bold mt-1" style="font-size: 0.65rem;">GRADEBOOK</span>
                </div>
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h3 class="fw-bold mb-0 text-dark">Batch Gradebook: {{ $class->class_name }}</h3>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small">
                            🎓 {{ $class->gradeLevel->grade_name ?? 'Level' }}
                        </span>
                        @if($class->homeroom_teacher)
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">
                                👨‍🏫 {{ $class->homeroom_teacher }}
                            </span>
                        @endif
                    </div>
                    <p class="text-muted small mb-0">
                        School: <strong>{{ $activeSchool->school_name }}</strong> • Total Students: <strong>{{ $students->count() }}</strong> • Courses Taught: <strong>{{ $assignments->count() }}</strong>
                    </p>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('config.classes.profile', $class->id) }}" class="btn btn-outline-secondary btn-sm fw-semibold">
                    ← Back to Batch Profile
                </a>
                <a href="{{ route('reports.broadsheet', ['class_id' => $class->id]) }}" class="btn btn-outline-primary btn-sm fw-semibold">
                    📋 Broadsheet Report
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-xs border-0 mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($assignments->isEmpty())
        <div class="card-custom p-5 text-center">
            <div class="fs-1 text-muted mb-2">📚</div>
            <h5 class="fw-bold text-dark">No Courses Assigned to this Batch</h5>
            <p class="text-muted small mb-3">Please assign master subjects to this class first before entering grades.</p>
            <a href="{{ route('config.classes.profile', $class->id) }}" class="btn btn-primary btn-sm fw-bold">
                Go to Batch Profile to Assign Courses
            </a>
        </div>
    @else
        <!-- Filter & Scope Selector Bar -->
        <div class="card-custom p-3 mb-4 bg-light">
            <form method="GET" action="{{ route('config.classes.gradebook', $class->id) }}" id="gradebookFilterForm">
                @if(request()->has('school_id'))
                    <input type="hidden" name="school_id" value="{{ request('school_id') }}">
                @endif
                <div class="row g-2 align-items-center">
                    <!-- Course Switcher -->
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-dark mb-1">Subject / Course <span class="text-danger">*</span></label>
                        <select name="assignment_id" class="form-select form-select-sm fw-semibold" onchange="this.form.submit()">
                            @foreach($assignments as $assign)
                                <option value="{{ $assign->id }}" {{ $selectedAssignment && $selectedAssignment->id == $assign->id ? 'selected' : '' }}>
                                    📖 {{ $assign->subject->subject_name }} ({{ $assign->subject->subject_code }})
                                    @if($assign->assigned_teacher) - [{{ $assign->assigned_teacher }}] @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Grading Period Switcher -->
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-dark mb-1">Grading Period / Term</label>
                        <select name="period_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            @if($periods->isEmpty())
                                <option value="">-- General Assessment / Term 1 --</option>
                            @else
                                @foreach($periods as $p)
                                    <option value="{{ $p->id }}" {{ $selectedPeriod && $selectedPeriod->id == $p->id ? 'selected' : '' }}>
                                        🗓️ {{ $p->period_name }} (Weight: {{ $p->weight }}%)
                                    </option>
                                    @if($p->subPeriods->isNotEmpty())
                                        @foreach($p->subPeriods as $sp)
                                            <option value="{{ $sp->id }}" {{ $selectedPeriod && $selectedPeriod->id == $sp->id ? 'selected' : '' }}>
                                                &nbsp;&nbsp;&nbsp;↳ {{ $sp->period_name }} (Weight: {{ $sp->weight }}%)
                                            </option>
                                        @endforeach
                                    @endif
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- View Mode Toggle -->
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-dark mb-1">View Mode</label>
                        <div class="btn-group w-100 btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="view_mode" id="vmDetail" value="detail" {{ $viewMode !== 'broadsheet' ? 'checked' : '' }} onchange="this.form.submit()">
                            <label class="btn btn-outline-primary fw-semibold" for="vmDetail">📝 Detailed Marksheet</label>

                            <input type="radio" class="btn-check" name="view_mode" id="vmBroadsheet" value="broadsheet" {{ $viewMode === 'broadsheet' ? 'checked' : '' }} onchange="this.form.submit()">
                            <label class="btn btn-outline-primary fw-semibold" for="vmBroadsheet">📊 Batch Broadsheet</label>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        @if($viewMode !== 'broadsheet')
            <!-- Course & Grading Blueprint Summary -->
            @if($selectedAssignment)
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card-custom p-3 h-100">
                            <div class="text-muted small fw-semibold">Class Course Average</div>
                            <h3 class="fw-bold {{ ($courseAvg ?? 0) >= $passLimit ? 'text-success' : 'text-danger' }} mb-0 mt-1" id="summaryClassAvg">
                                {{ $courseAvg !== null ? $courseAvg . '%' : 'No Marks' }}
                            </h3>
                            <small class="text-muted">Calculated weighted score</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-custom p-3 h-100">
                            <div class="text-muted small fw-semibold">Passing Rate</div>
                            <h3 class="fw-bold {{ ($coursePassRate ?? 0) >= 75 ? 'text-success' : 'text-warning' }} mb-0 mt-1">
                                {{ $coursePassRate !== null ? $coursePassRate . '%' : 'N/A' }}
                            </h3>
                            <small class="text-muted">Passing threshold: ≥ {{ $passLimit }}%</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-custom p-3 h-100">
                            <div class="text-muted small fw-semibold">Graded Students</div>
                            <h3 class="fw-bold text-dark mb-0 mt-1">
                                {{ $gradedCount }} / {{ $students->count() }}
                            </h3>
                            <small class="text-muted">{{ $students->count() - $gradedCount }} pending completion</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-custom p-3 h-100">
                            <div class="text-muted small fw-semibold">Grading Blueprint</div>
                            <div class="fw-bold text-primary text-truncate mt-1" title="{{ $effectiveRule ? $effectiveRule->rule_name : 'Standard Flat Scale' }}">
                                📐 {{ $effectiveRule ? $effectiveRule->rule_name : 'Standard Scale' }}
                            </div>
                            <small class="text-muted">
                                {{ $categories->count() > 0 ? $categories->count() . ' Categories in period' : 'Continuous & Terminal Scale' }}
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Grading System Categories Banner -->
                @if($categories->isNotEmpty())
                    <div class="alert alert-info border-0 shadow-xs mb-4 py-2 px-3 small">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <strong>Active Grading Structure:</strong>
                                @foreach($categories as $cat)
                                    @php
                                        $displayMax = $cat->weight > 0 ? $cat->weight : $cat->max_score;
                                    @endphp
                                    <span class="badge bg-white text-info-emphasis border border-info-subtle me-1">
                                        {{ $cat->category_name }}: {{ $cat->weight }}% (Max {{ $displayMax }})
                                    </span>
                                @endforeach
                            </div>
                            <span class="text-muted">Weights total: <strong>{{ $categories->sum('weight') }}%</strong></span>
                        </div>
                    </div>
                @endif

                <!-- Interactive Student Marksheet Table -->
                <div class="card-custom overflow-hidden mb-4">
                    <form method="POST" action="{{ route('config.classes.gradebook.save', $class->id) }}" id="batchGradebookForm">
                        @csrf
                        <input type="hidden" name="assignment_id" value="{{ $selectedAssignment->id }}">
                        <input type="hidden" name="period_id" value="{{ $selectedPeriod ? $selectedPeriod->id : '' }}">
                        <input type="hidden" name="score_entry_mode" id="hiddenScoreEntryMode" value="weight">

                        <div class="p-3 bg-white border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">
                                    📝 Student Marksheet: {{ $selectedAssignment->subject->subject_name }}
                                    @if($selectedPeriod)
                                        <span class="text-muted fw-normal">({{ $selectedPeriod->period_name }})</span>
                                    @endif
                                </h6>
                                <small class="text-muted">Enter marks directly in cells. Totals, Letter Grades, and Status update automatically.</small>
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <!-- Segmented Input Scale Toggle -->
                                <div class="d-flex align-items-center gap-1 bg-light px-2 py-1 rounded border shadow-2xs">
                                    <span class="small fw-bold text-dark text-nowrap" style="font-size: 0.78rem;">Score Scale:</span>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <input type="radio" class="btn-check" name="score_entry_mode_radio" id="semWeight" value="weight" checked onchange="switchScoreEntryMode('weight')">
                                        <label class="btn btn-outline-primary fw-semibold py-0 px-2" for="semWeight" style="font-size: 0.75rem;" title="Enter scores out of Category Weight (e.g. out of 30 for 30% weight)">
                                            ⚖️ Out of Weight (e.g. / 30)
                                        </label>

                                        <input type="radio" class="btn-check" name="score_entry_mode_radio" id="semPercent" value="percent" onchange="switchScoreEntryMode('percent')">
                                        <label class="btn btn-outline-primary fw-semibold py-0 px-2" for="semPercent" style="font-size: 0.75rem;" title="Enter scores out of 100 (%)">
                                            💯 Out of 100 (%)
                                        </label>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-sm btn-primary fw-bold shadow-xs" data-bs-toggle="modal" data-bs-target="#modalUploadGradebookSheet">
                                    📤 Upload Grades Sheet
                                </button>
                                <a href="{{ route('config.classes.gradebook.template', ['id' => $class->id, 'assignment_id' => $selectedAssignment->id, 'period_id' => $selectedPeriod ? $selectedPeriod->id : '']) }}" id="btnExcelTemplate" class="btn btn-sm btn-outline-secondary fw-semibold shadow-xs" title="Download Pre-filled Excel Template for this subject">
                                    📥 Excel Template
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modalManageCourseEnrollment">
                                    👥 Manage Roster ({{ $students->count() }}/{{ $allClassStudents->count() }})
                                </button>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        ⚡ Quick Fill
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end small">
                                        <li><a class="dropdown-item" href="#" onclick="quickFillEmpty(0); return false;">Fill empty cells with 0</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="quickFillMax(); return false;">Fill empty cells with Max / 100</a></li>
                                    </ul>
                                </div>
                                <button type="submit" class="btn btn-sm btn-success fw-bold px-3">
                                    💾 Save All Grades
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="gradebookTable">
                                <thead class="table-light text-secondary small text-uppercase">
                                    <tr>
                                        <th class="ps-3" style="width: 40px;">#</th>
                                        <th style="min-width: 110px;">Adm #</th>
                                        <th style="min-width: 180px;">Student Name</th>
                                        @if($categories->isNotEmpty())
                                            @foreach($categories as $cat)
                                                @php
                                                    $catWeight = $cat->weight > 0 ? $cat->weight : $cat->max_score;
                                                @endphp
                                                <th class="text-center" style="min-width: 120px;" title="Weight: {{ $cat->weight }}%">
                                                    {{ $cat->category_name }}
                                                    <div class="fw-normal text-muted cat-scale-header" data-cat-id="{{ $cat->id }}" data-weight="{{ $catWeight }}" data-cat-max="{{ $catWeight }}" style="font-size: 0.7rem; text-transform: none;">
                                                        Max {{ $catWeight }} <span class="fw-bold">({{ $cat->weight }}%)</span>
                                                    </div>
                                                </th>
                                            @endforeach
                                        @else
                                            <th class="text-center" style="min-width: 120px;">
                                                Coursework
                                                <div class="fw-normal text-muted" style="font-size: 0.7rem; text-transform: none;">Max 50.0</div>
                                            </th>
                                            <th class="text-center" style="min-width: 120px;">
                                                Final Exam
                                                <div class="fw-normal text-muted" style="font-size: 0.7rem; text-transform: none;">Max 50.0</div>
                                            </th>
                                        @endif
                                        <th class="text-center" style="min-width: 100px;">Total / 100</th>
                                        <th class="text-center" style="min-width: 90px;">Grade</th>
                                        <th class="text-center" style="min-width: 100px;">Status</th>
                                        <th style="min-width: 160px;">Teacher Remarks</th>
                                        <th class="text-center pe-3" style="width: 60px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    @forelse($students as $idx => $std)
                                        @php
                                            $gr = $gradeRecords->get($std->id);
                                            $stdSubScores = $subScores->get($std->id, collect())->keyBy('grading_category_id');
                                            $isPassed = $gr && $gr->total_score !== null && $gr->total_score >= $passLimit;
                                        @endphp
                                        <tr id="row_std_{{ $std->id }}">
                                            <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                                            <td>
                                                <span class="font-monospace fw-semibold text-dark">{{ $std->admission_number }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                                        {{ strtoupper(substr($std->full_name, 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark">{{ $std->full_name }}</div>
                                                        <small class="text-muted" style="font-size: 0.75rem;">{{ $std->gender }}</small>
                                                    </div>
                                                </div>
                                            </td>

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
                                                               class="form-control form-control-sm text-center fw-semibold cat-score-input"
                                                               data-student-id="{{ $std->id }}"
                                                               data-weight="{{ $catWeight }}"
                                                               data-cat-max="{{ $catWeight }}"
                                                               data-max="{{ $catWeight }}"
                                                               data-active-max="{{ $catWeight }}"
                                                               oninput="calculateStudentRow({{ $std->id }})"
                                                               placeholder="0 - {{ $catWeight }}">
                                                    </td>
                                                @endforeach
                                            @else
                                                <td class="text-center">
                                                    <input type="number" 
                                                           step="0.5" 
                                                           min="0" 
                                                           max="50" 
                                                           name="grades[{{ $std->id }}][cw_score]" 
                                                           value="{{ $gr ? $gr->cw_score : '' }}" 
                                                           class="form-control form-control-sm text-center fw-semibold flat-cw-input"
                                                           data-student-id="{{ $std->id }}"
                                                           oninput="calculateStudentRowFlat({{ $std->id }})"
                                                           placeholder="0 - 50">
                                                </td>
                                                <td class="text-center">
                                                    <input type="number" 
                                                           step="0.5" 
                                                           min="0" 
                                                           max="50" 
                                                           name="grades[{{ $std->id }}][exam_score]" 
                                                           value="{{ $gr ? $gr->exam_score : '' }}" 
                                                           class="form-control form-control-sm text-center fw-semibold flat-exam-input"
                                                           data-student-id="{{ $std->id }}"
                                                           oninput="calculateStudentRowFlat({{ $std->id }})"
                                                           placeholder="0 - 50">
                                                </td>
                                            @endif

                                            <!-- Computed Total -->
                                            <td class="text-center">
                                                <input type="number" 
                                                       step="0.1" 
                                                       readonly 
                                                       name="grades[{{ $std->id }}][total_score]" 
                                                       id="total_{{ $std->id }}" 
                                                       value="{{ $gr ? $gr->total_score : '' }}" 
                                                       class="form-control form-control-sm text-center fw-bold bg-light total-display"
                                                       style="min-width: 75px;">
                                            </td>

                                            <!-- Letter Grade & GPA -->
                                            <td class="text-center">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold fs-7" id="grade_badge_{{ $std->id }}">
                                                    {{ $gr && $gr->letter_grade ? $gr->letter_grade : '-' }}
                                                </span>
                                            </td>

                                            <!-- Pass / Fail Status -->
                                            <td class="text-center">
                                                <span class="badge text-white {{ $isPassed ? 'bg-success' : ($gr && $gr->total_score !== null ? 'bg-danger' : 'bg-secondary') }}" id="status_badge_{{ $std->id }}">
                                                    {{ $isPassed ? '✓ Passed' : ($gr && $gr->total_score !== null ? '✗ Retake' : 'Pending') }}
                                                </span>
                                            </td>

                                            <!-- Teacher Remarks -->
                                            <td>
                                                <input type="text" 
                                                       name="grades[{{ $std->id }}][teacher_remarks]" 
                                                       id="remarks_{{ $std->id }}" 
                                                       value="{{ $gr ? $gr->teacher_remarks : '' }}" 
                                                       class="form-control form-control-sm" 
                                                       placeholder="Remarks...">
                                            </td>

                                            <!-- Quick Row Save -->
                                            <td class="text-center pe-3">
                                                <button type="button" 
                                                        class="btn btn-xs btn-outline-primary py-1 px-2" 
                                                        id="btn_save_{{ $std->id }}" 
                                                        onclick="saveSingleRow({{ $std->id }})" 
                                                        title="Save this student's grade">
                                                    💾
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="p-4 text-center text-muted">
                                                No students enrolled in this class yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
                            <span class="text-muted small">
                                Showing <strong>{{ $students->count() }}</strong> students enrolled in <strong>{{ $class->class_name }}</strong>
                            </span>
                            <button type="submit" class="btn btn-success fw-bold px-4">
                                💾 Save All {{ $students->count() }} Student Grades
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        @else
            <!-- MULTI-SUBJECT BATCH BROADSHEET MATRIX -->
            <div class="card-custom overflow-hidden mb-4">
                <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">📊 Batch Multi-Subject Broadsheet Matrix</h6>
                        <small class="text-muted">Consolidated marksheet of all assigned courses for {{ $class->class_name }}.</small>
                    </div>
                    <a href="{{ route('reports.broadsheet', ['class_id' => $class->id]) }}" class="btn btn-sm btn-outline-primary fw-bold">
                        Print Official Broadsheet ↗
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0 small text-center">
                        <thead class="table-light text-secondary text-uppercase" style="font-size: 0.75rem;">
                            <tr>
                                <th class="text-start ps-3" style="min-width: 50px;">#</th>
                                <th class="text-start" style="min-width: 100px;">Adm #</th>
                                <th class="text-start" style="min-width: 170px;">Student Name</th>
                                @foreach($assignments as $assign)
                                    <th style="min-width: 110px;">
                                        <a href="{{ route('config.classes.gradebook', ['id' => $class->id, 'assignment_id' => $assign->id]) }}" class="text-decoration-none text-dark d-block">
                                            {{ $assign->subject->subject_name }}
                                            <div class="fw-normal text-muted" style="font-size: 0.65rem;">Pass: {{ $assign->passing_mark ?? 60 }}%</div>
                                        </a>
                                    </th>
                                @endforeach
                                <th style="min-width: 90px;" class="bg-primary-subtle text-primary fw-bold">Average</th>
                                <th style="min-width: 80px;" class="bg-primary-subtle text-primary fw-bold">GPA</th>
                                <th style="min-width: 100px;">Outcome</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $idx => $std)
                                @php
                                    $stdRecords = $broadsheetRecords->get($std->id, collect())->keyBy('class_subject_assignment_id');
                                    $totalMarksSum = 0;
                                    $coursesWithMarks = 0;
                                    $failedCount = 0;
                                @endphp
                                <tr>
                                    <td class="text-start ps-3 text-muted">{{ $idx + 1 }}</td>
                                    <td class="text-start font-monospace fw-semibold">{{ $std->admission_number }}</td>
                                    <td class="text-start fw-bold text-dark">{{ $std->full_name }}</td>

                                    @foreach($assignments as $assign)
                                        @php
                                            $gr = $stdRecords->get($assign->id);
                                            $passThreshold = $assign->passing_mark ?? 60.0;
                                            if ($gr && $gr->total_score !== null) {
                                                $totalMarksSum += $gr->total_score;
                                                $coursesWithMarks++;
                                                if ($gr->total_score < $passThreshold) {
                                                    $failedCount++;
                                                }
                                            }
                                        @endphp
                                        <td>
                                            @if($gr && $gr->total_score !== null)
                                                <span class="fw-bold {{ $gr->total_score >= $passThreshold ? 'text-success' : 'text-danger' }}">
                                                    {{ $gr->total_score }}
                                                </span>
                                                <span class="badge bg-light text-secondary border px-1" style="font-size: 0.65rem;">
                                                    {{ $gr->letter_grade ?? '-' }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    @endforeach

                                    @php
                                        $stdAvg = $coursesWithMarks > 0 ? round($totalMarksSum / $coursesWithMarks, 1) : null;
                                        $gradingService = app(\App\Services\GradingCalculationService::class);
                                        $overallGrade = ($stdAvg !== null && $gradingSystem) ? $gradingService->calculateGradeAndGpa($stdAvg, $gradingSystem) : null;
                                    @endphp
                                    <td class="bg-primary-subtle text-primary fw-bold">
                                        {{ $stdAvg !== null ? $stdAvg . '%' : '-' }}
                                    </td>
                                    <td class="bg-primary-subtle text-primary fw-bold">
                                        {{ $overallGrade ? $overallGrade['gpa'] : '-' }}
                                    </td>
                                    <td>
                                        @if($coursesWithMarks === 0)
                                            <span class="badge bg-secondary text-white">No Grades</span>
                                        @elseif($failedCount === 0)
                                            <span class="badge bg-success text-white">Passed All</span>
                                        @else
                                            <span class="badge bg-warning text-dark">{{ $failedCount }} Failed</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $assignments->count() + 6 }}" class="p-4 text-center text-muted">
                                        No enrolled students in this class.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
</div>

<!-- JavaScript for Live Auto-Calculation & Fast Single-Row Save -->
<script>
const gradingCutoffs = @json($gradingCutoffs ?? []);
const passLimit = {{ (float) ($passLimit ?? 60.0) }};
const assignmentId = {{ (int) ($selectedAssignment ? $selectedAssignment->id : 0) }};
const periodId = {{ (int) ($selectedPeriod ? $selectedPeriod->id : 0) }};
const classId = {{ (int) $class->id }};
const csrfToken = "{{ csrf_token() }}";
let currentScoreEntryMode = 'weight';

function switchScoreEntryMode(mode, convertExisting = true) {
    const oldMode = currentScoreEntryMode;
    currentScoreEntryMode = mode;

    // Update hidden input in batch form
    const hiddenInp = document.getElementById('hiddenScoreEntryMode');
    if (hiddenInp) hiddenInp.value = mode;

    // Keep radio inputs in sync
    const radWeight = document.getElementById('semWeight');
    const radPercent = document.getElementById('semPercent');
    if (radWeight && radPercent) {
        radWeight.checked = (mode === 'weight');
        radPercent.checked = (mode === 'percent');
    }

    // Persist user preference
    try {
        localStorage.setItem('gradebook_score_mode', mode);
    } catch(e) {}

    // Update template download links to include mode
    const btnTmpl = document.getElementById('btnExcelTemplate');
    if (btnTmpl) {
        const url = new URL(btnTmpl.href, window.location.origin);
        url.searchParams.set('score_entry_mode', mode);
        btnTmpl.href = url.toString();
    }

    // Update table header sub-labels
    document.querySelectorAll('.cat-scale-header').forEach(header => {
        const weight = header.dataset.weight;
        if (mode === 'percent') {
            header.innerHTML = `Out of 100 <span class="fw-bold">(${weight}%)</span>`;
        } else {
            header.innerHTML = `Max ${weight} <span class="fw-bold">(${weight}%)</span>`;
        }
    });

    // Update category inputs max, placeholder, and optionally convert values
    const catInputs = document.querySelectorAll('input.cat-score-input');
    const studentIds = new Set();

    catInputs.forEach(inp => {
        const catWeight = parseFloat(inp.dataset.weight || inp.dataset.catMax || 100);
        const activeMax = (mode === 'percent') ? 100 : catWeight;
        inp.dataset.activeMax = activeMax;
        inp.max = activeMax;
        inp.placeholder = (mode === 'percent') ? '0 - 100' : `0 - ${catWeight}`;

        if (convertExisting && inp.value !== '') {
            const currentVal = parseFloat(inp.value);
            if (!isNaN(currentVal)) {
                if (oldMode === 'weight' && mode === 'percent') {
                    // Convert from weight scale to 100 (e.g. 27 / 30 -> 90)
                    const converted = (currentVal / catWeight) * 100.0;
                    inp.value = Math.round(converted * 10) / 10;
                } else if (oldMode === 'percent' && mode === 'weight') {
                    // Convert from 100 to weight scale (e.g. 90 / 100 -> 27)
                    const converted = (currentVal / 100.0) * catWeight;
                    inp.value = Math.round(converted * 10) / 10;
                }
            }
        }

        const sid = inp.dataset.studentId;
        if (sid) studentIds.add(sid);
    });

    // Re-calculate totals
    studentIds.forEach(sid => calculateStudentRow(sid));
}

function getLetterAndGpa(score) {
    if (score === null || isNaN(score) || score === '') {
        return { grade: '-', gpa: 0.0 };
    }
    for (const tier of gradingCutoffs) {
        if (score >= tier.min && score <= tier.max) {
            return { grade: tier.grade, gpa: tier.gpa };
        }
    }
    return { grade: score >= 60 ? 'D' : 'F', gpa: score >= 60 ? 1.0 : 0.0 };
}

function calculateStudentRow(studentId) {
    const inputs = document.querySelectorAll(`input.cat-score-input[data-student-id="${studentId}"]`);
    let weightedTotal = 0;
    let anyEntered = false;

    inputs.forEach(inp => {
        const val = parseFloat(inp.value);
        const weight = parseFloat(inp.dataset.weight || 0);
        const isPercent = (currentScoreEntryMode === 'percent');

        if (!isNaN(val)) {
            anyEntered = true;
            if (isPercent) {
                // Out of 100: scale by weight %
                const clampedVal = Math.min(Math.max(val, 0), 100.0);
                weightedTotal += (clampedVal / 100.0) * weight;
            } else {
                // Out of category weight: score is entered directly out of category weight (e.g. out of 30)
                const clampedVal = Math.min(Math.max(val, 0), weight);
                weightedTotal += clampedVal;
            }
        }
    });

    const totalInp = document.getElementById(`total_${studentId}`);
    const badgeGrade = document.getElementById(`grade_badge_${studentId}`);
    const badgeStatus = document.getElementById(`status_badge_${studentId}`);

    if (anyEntered) {
        const roundedTotal = Math.round(weightedTotal * 10) / 10;
        totalInp.value = roundedTotal;
        const info = getLetterAndGpa(roundedTotal);
        badgeGrade.innerText = `${info.grade} (${info.gpa})`;

        if (roundedTotal >= passLimit) {
            badgeStatus.className = 'badge bg-success text-white';
            badgeStatus.innerText = '✓ Passed';
        } else {
            badgeStatus.className = 'badge bg-danger text-white';
            badgeStatus.innerText = '✗ Retake';
        }
    } else {
        totalInp.value = '';
        badgeGrade.innerText = '-';
        badgeStatus.className = 'badge bg-secondary text-white';
        badgeStatus.innerText = 'Pending';
    }

    recalcClassAverage();
}

function calculateStudentRowFlat(studentId) {
    const cwInp = document.querySelector(`input.flat-cw-input[data-student-id="${studentId}"]`);
    const examInp = document.querySelector(`input.flat-exam-input[data-student-id="${studentId}"]`);

    const cw = cwInp ? parseFloat(cwInp.value) : 0;
    const exam = examInp ? parseFloat(examInp.value) : 0;

    const anyEntered = (cwInp && cwInp.value !== '') || (examInp && examInp.value !== '');
    const totalInp = document.getElementById(`total_${studentId}`);
    const badgeGrade = document.getElementById(`grade_badge_${studentId}`);
    const badgeStatus = document.getElementById(`status_badge_${studentId}`);

    if (anyEntered) {
        const total = (isNaN(cw) ? 0 : cw) + (isNaN(exam) ? 0 : exam);
        const roundedTotal = Math.round(total * 10) / 10;
        totalInp.value = roundedTotal;
        const info = getLetterAndGpa(roundedTotal);
        badgeGrade.innerText = `${info.grade} (${info.gpa})`;

        if (roundedTotal >= passLimit) {
            badgeStatus.className = 'badge bg-success text-white';
            badgeStatus.innerText = '✓ Passed';
        } else {
            badgeStatus.className = 'badge bg-danger text-white';
            badgeStatus.innerText = '✗ Retake';
        }
    } else {
        totalInp.value = '';
        badgeGrade.innerText = '-';
        badgeStatus.className = 'badge bg-secondary text-white';
        badgeStatus.innerText = 'Pending';
    }

    recalcClassAverage();
}

function recalcClassAverage() {
    const totals = document.querySelectorAll('input.total-display');
    let sum = 0;
    let count = 0;

    totals.forEach(t => {
        const val = parseFloat(t.value);
        if (!isNaN(val) && t.value !== '') {
            sum += val;
            count++;
        }
    });

    const avgEl = document.getElementById('summaryClassAvg');
    if (avgEl) {
        if (count > 0) {
            const avg = Math.round((sum / count) * 10) / 10;
            avgEl.innerText = `${avg}%`;
            avgEl.className = `fw-bold ${avg >= passLimit ? 'text-success' : 'text-danger'} mb-0 mt-1`;
        } else {
            avgEl.innerText = 'No Marks';
            avgEl.className = 'fw-bold text-dark mb-0 mt-1';
        }
    }
}

function quickFillEmpty(fillVal) {
    const inputs = document.querySelectorAll('input.cat-score-input, input.flat-cw-input, input.flat-exam-input');
    const studentIds = new Set();

    inputs.forEach(inp => {
        if (inp.value === '') {
            inp.value = fillVal;
            const sid = inp.dataset.studentId;
            if (sid) studentIds.add(sid);
        }
    });

    studentIds.forEach(sid => {
        const hasCats = document.querySelector(`input.cat-score-input[data-student-id="${sid}"]`);
        if (hasCats) {
            calculateStudentRow(sid);
        } else {
            calculateStudentRowFlat(sid);
        }
    });
}

function quickFillMax() {
    const inputs = document.querySelectorAll('input.cat-score-input');
    const studentIds = new Set();

    inputs.forEach(inp => {
        if (inp.value === '') {
            const catMax = parseFloat(inp.dataset.catMax || inp.dataset.max || 100);
            inp.value = (currentScoreEntryMode === 'percent') ? 100 : catMax;
            const sid = inp.dataset.studentId;
            if (sid) studentIds.add(sid);
        }
    });

    studentIds.forEach(sid => {
        calculateStudentRow(sid);
    });
}

function saveSingleRow(studentId) {
    const btn = document.getElementById(`btn_save_${studentId}`);
    if (btn) btn.innerHTML = '⏳';

    const catInputs = document.querySelectorAll(`input.cat-score-input[data-student-id="${studentId}"]`);
    const payload = {
        _token: csrfToken,
        assignment_id: assignmentId,
        period_id: periodId,
        student_id: studentId,
        score_entry_mode: currentScoreEntryMode,
        teacher_remarks: document.getElementById(`remarks_${studentId}`)?.value || '',
        total_score: document.getElementById(`total_${studentId}`)?.value || null
    };

    if (catInputs.length > 0) {
        payload.categories = {};
        catInputs.forEach(inp => {
            const catId = inp.name.match(/\[categories\]\[(\d+)\]/)?.[1];
            if (catId) {
                payload.categories[catId] = inp.value;
            }
        });
    } else {
        payload.cw_score = document.querySelector(`input.flat-cw-input[data-student-id="${studentId}"]`)?.value;
        payload.exam_score = document.querySelector(`input.flat-exam-input[data-student-id="${studentId}"]`)?.value;
    }

    fetch(`/configuration/classes/${classId}/gradebook/save-single`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (btn) {
                btn.innerHTML = '✅';
                btn.className = 'btn btn-xs btn-success py-1 px-2';
                setTimeout(() => {
                    btn.innerHTML = '💾';
                    btn.className = 'btn btn-xs btn-outline-primary py-1 px-2';
                }, 2000);
            }
        } else {
            alert('Error saving grade: ' + (data.message || 'Unknown error'));
            if (btn) btn.innerHTML = '💾';
        }
    })
    .catch(err => {
        alert('Network error saving grade');
        if (btn) btn.innerHTML = '💾';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    try {
        const savedMode = localStorage.getItem('gradebook_score_mode');
        if (savedMode === 'percent') {
            switchScoreEntryMode('percent', false);
        }
    } catch(e) {}
});

function toggleAllRosterCheckboxes(check) {
    document.querySelectorAll('.roster-checkbox').forEach(cb => cb.checked = check);
}
</script>

@if($selectedAssignment)
<!-- ============================================================== -->
<!-- MODAL: MANAGE SUBJECT ROSTER / ENROLLMENT                      -->
<!-- ============================================================== -->
<div class="modal fade" id="modalManageCourseEnrollment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient text-white p-3" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-4">👥</span>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white">Manage Subject Roster</h5>
                        <small class="text-white-50">{{ $selectedAssignment->subject->subject_name }} &bull; {{ $class->class_name }}</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('config.class_subjects.enrollments.update', $selectedAssignment->id) }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small border-0 mb-3">
                        Select which students from <strong>{{ $class->class_name }}</strong> are enrolled in <strong>{{ $selectedAssignment->subject->subject_name }}</strong>. Unselected students will not appear on this subject's gradebook.
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted">Total Batch Students: <strong>{{ $allClassStudents->count() }}</strong></span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleAllRosterCheckboxes(true)">✓ Select All</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleAllRosterCheckboxes(false)">✗ Deselect All</button>
                        </div>
                    </div>

                    <div class="list-group rounded-3 border overflow-auto" style="max-height: 380px;">
                        @forelse($allClassStudents as $std)
                            @php
                                $isEnrolled = $students->contains('id', $std->id);
                            @endphp
                            <label class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-2">
                                <div class="d-flex align-items-center gap-3">
                                    <input class="form-check-input roster-checkbox me-1" type="checkbox" name="student_ids[]" value="{{ $std->id }}" {{ $isEnrolled ? 'checked' : '' }}>
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
                            <div class="p-3 text-center text-muted">No students registered in {{ $class->class_name }} yet.</div>
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
                        <small class="text-white-50">
                            {{ $selectedAssignment->subject->subject_name }} &bull; {{ $class->class_name }}
                            @if($selectedPeriod) &bull; {{ $selectedPeriod->period_name }} @endif
                        </small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('config.classes.gradebook.upload', $class->id) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="assignment_id" value="{{ $selectedAssignment->id }}">
                <input type="hidden" name="period_id" value="{{ $selectedPeriod ? $selectedPeriod->id : '' }}">

                <div class="modal-body p-4">
                    <!-- Template Download Suggestion Callout -->
                    <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="fs-3">📥</span>
                            <div>
                                <strong class="text-dark d-block">Pre-filled Excel Marksheet Template</strong>
                                <small class="text-muted">Download a spreadsheet pre-populated with student admission numbers, full names, and all category columns.</small>
                            </div>
                        </div>
                        <a href="{{ route('config.classes.gradebook.template', ['id' => $class->id, 'assignment_id' => $selectedAssignment->id, 'period_id' => $selectedPeriod ? $selectedPeriod->id : '']) }}" class="btn btn-sm btn-outline-primary fw-bold text-nowrap">
                            📥 Download Template
                        </a>
                    </div>

                    <!-- Category Selector: Any Category or Auto-Detect -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark mb-1">Target Grading Category</label>
                        <select name="category_id" id="uploadTargetCategorySelect" class="form-select">
                            <option value="">🌟 Auto-Detect from Sheet Columns (Match all categories automatically)</option>
                            @if($categories->isNotEmpty())
                                <optgroup label="Categories in this Period">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">
                                            📝 {{ $cat->category_name }} [Max: {{ $cat->max_score }}, Weight: {{ $cat->weight }}%]
                                        </option>
                                    @endforeach
                                </optgroup>
                            @else
                                <optgroup label="Assessment Components">
                                    <option value="cw">Classwork (CW) [Max: {{ $selectedAssignment->cw_weight }}%]</option>
                                    <option value="exam">Exam Score [Max: {{ $selectedAssignment->exam_weight }}%]</option>
                                </optgroup>
                            @endif
                        </select>
                        <small class="text-muted d-block mt-1">
                            Choose <strong>Auto-Detect</strong> to import multiple columns matching your grading periods, or pick a <strong>specific category</strong> to map any single score column directly into that category.
                        </small>
                    </div>

                    <!-- Sheet Score Scale Option -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark mb-1">Sheet Score Scale</label>
                        <div class="d-flex flex-wrap gap-3 p-2 bg-light rounded border">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="score_entry_mode" id="modalScaleWeight" value="weight" checked>
                                <label class="form-check-label small fw-semibold text-dark" for="modalScaleWeight">
                                    ⚖️ Category Weight / Max (e.g. 18 / 20)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="score_entry_mode" id="modalScalePercent" value="percent">
                                <label class="form-check-label small fw-semibold text-dark" for="modalScalePercent">
                                    💯 Out of 100 (%) (e.g. 90 / 100)
                                </label>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Choose <strong>Out of 100 (%)</strong> if your spreadsheet grades are recorded on a 0–100% scale. The system will automatically calculate weighted contributions according to your category weights.
                        </small>
                    </div>

                    <!-- Spreadsheet File Input -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark mb-1">Select Spreadsheet File (.xlsx, .xls, .csv) <span class="text-danger">*</span></label>
                        <input type="file" name="sheet_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <small class="text-muted d-block mt-1">
                            Accepted formats: Excel Workbook (<code>.xlsx</code>, <code>.xls</code>) or Comma-Separated Values (<code>.csv</code>). Maximum file size: 10MB.
                        </small>
                    </div>

                    <!-- Information Alert -->
                    <div class="alert alert-info py-2 px-3 small border-0 mb-0">
                        <strong>ℹ️ How it works:</strong> The system matches students using their <strong>Admission Number</strong> or <strong>Full Name</strong>. Scores are clamped between <code>0</code> and <code>Max Score</code>, weighted scores and GPAs update automatically, and existing marks in other categories remain intact.
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
@endif
@endsection
