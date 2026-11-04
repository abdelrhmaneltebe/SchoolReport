@extends('layouts.app')

@section('title', $mode === 'slot' ? 'Subject & Slot Attendance Register' : 'Attendance Register & Daily Roll Call')

@section('content')
<div class="container-fluid px-0">

    <!-- Header Navigation Tabs -->
    <div class="card border rounded-3 p-3 mb-4 bg-white shadow-xs">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="p-2 {{ $mode === 'slot' ? 'bg-info-subtle text-info' : 'bg-primary-subtle text-primary' }} rounded-3 fs-3">
                    {{ $mode === 'slot' ? '📚' : '📅' }}
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge {{ $mode === 'slot' ? 'bg-info text-dark' : 'bg-primary text-white' }} px-2 py-1 small">
                            {{ $mode === 'slot' ? 'Pillar 1 • Subject & Slot Mode' : 'Pillar 1 • Full Day Homeroom' }}
                        </span>
                        <span class="badge bg-light text-secondary border font-monospace">{{ $activeSchool->school_code }}</span>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark">
                        {{ $mode === 'slot' ? 'Subject Period & Timetable Slot Attendance' : 'Class Attendance Register & Daily Roll Call' }}
                    </h4>
                    <p class="text-muted small mb-0">
                        @if($mode === 'slot')
                            Record attendance for specific subject lessons and timetable periods in <strong>{{ $activeSchool->school_name }}</strong>.
                        @else
                            Record daily homeroom presence, unexcused absences, excused sick leaves, and tardies for <strong>{{ $activeSchool->school_name }}</strong>.
                        @endif
                    </p>
                </div>
            </div>

            <!-- Top Mode Tabs -->
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('attendance.index', ['mode' => $mode]) }}" class="btn btn-sm btn-primary fw-bold shadow-2xs">
                    📋 Roll Call Register
                </a>
                <a href="{{ route('attendance.summary', ['mode' => $mode]) }}" class="btn btn-sm btn-outline-secondary fw-semibold">
                    📊 Sheet &amp; Summaries
                </a>
                <a href="{{ route('config.attendance') }}" class="btn btn-sm btn-outline-secondary fw-semibold">
                    ⚙️ Policy Settings
                </a>
            </div>
        </div>
    </div>

    <!-- Mode Selector & Filter Controls Card -->
    <div class="card border rounded-3 p-3 mb-4 bg-white shadow-xs">
        <!-- Segmented Mode Switcher -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-3 border-bottom">
            <div class="btn-group btn-group-sm p-1 bg-light rounded-pill border shadow-2xs" role="group">
                <a href="{{ route('attendance.index', ['mode' => 'full_day', 'class_id' => $selectedClass?->id, 'date' => $date]) }}" 
                   class="btn rounded-pill px-3 fw-bold {{ $mode === 'full_day' ? 'btn-primary shadow-xs' : 'btn-light text-secondary' }}">
                    ☀️ Full Day (Homeroom Roll Call)
                </a>
                <a href="{{ route('attendance.index', ['mode' => 'slot', 'class_id' => $selectedClass?->id, 'date' => $date]) }}" 
                   class="btn rounded-pill px-3 fw-bold {{ $mode === 'slot' ? 'btn-primary shadow-xs' : 'btn-light text-secondary' }}">
                    📚 Subject &amp; Slot Attendance
                </a>
            </div>

            @if($mode === 'slot' && $selectedAssignment)
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small">
                        📖 {{ $selectedAssignment->subject->subject_name }}
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 small">
                        ⏱️ {{ $slotName }}
                    </span>
                    @if($selectedAssignment->assigned_teacher)
                        <span class="badge bg-light text-dark border px-2 py-1 small">
                            👤 Teacher: {{ $selectedAssignment->assigned_teacher }}
                        </span>
                    @endif
                </div>
            @endif
        </div>

        <form method="GET" action="{{ route('attendance.index') }}" id="filterForm">
            <input type="hidden" name="mode" value="{{ $mode }}">

            <div class="row g-3 align-items-end">
                <!-- Class Selection -->
                <div class="{{ $mode === 'slot' ? 'col-md-3 col-sm-6' : 'col-md-4 col-sm-6' }}">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-1">
                        🏫 Select Class / Section:
                    </label>
                    <select name="class_id" class="form-select form-select-sm fw-bold border-primary shadow-2xs" onchange="document.getElementById('filterForm').submit()">
                        @forelse($classes as $cls)
                            <option value="{{ $cls->id }}" {{ ($selectedClass && $selectedClass->id == $cls->id) ? 'selected' : '' }}>
                                {{ $cls->class_name }} ({{ $cls->gradeLevel->grade_name ?? 'Level' }}) • {{ $cls->students_count }} Students
                            </option>
                        @empty
                            <option value="">-- No classes configured --</option>
                        @endforelse
                    </select>
                </div>

                @if($mode === 'slot')
                    <!-- Subject / Course Selection -->
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1">
                            📖 Select Subject:
                        </label>
                        <select name="assignment_id" class="form-select form-select-sm fw-bold border-info shadow-2xs" onchange="document.getElementById('filterForm').submit()">
                            @forelse($assignments as $assign)
                                <option value="{{ $assign->id }}" {{ ($selectedAssignment && $selectedAssignment->id == $assign->id) ? 'selected' : '' }}>
                                    {{ $assign->subject->subject_name }} {{ $assign->assigned_teacher ? "({$assign->assigned_teacher})" : '' }}
                                </option>
                            @empty
                                <option value="">-- No subjects assigned --</option>
                            @endforelse
                        </select>
                    </div>

                    <!-- Timetable Slot / Period Selection -->
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1">
                            ⏱️ Timetable Period / Slot:
                        </label>
                        <select name="slot_name" class="form-select form-select-sm fw-bold border-secondary shadow-2xs" onchange="document.getElementById('filterForm').submit()">
                            @foreach($slots as $slotKey => $slotLabel)
                                <option value="{{ $slotKey }}" {{ $slotName === $slotKey ? 'selected' : '' }}>
                                    {{ $slotLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Date Picker & Quick Day Jumper -->
                <div class="{{ $mode === 'slot' ? 'col-md-3 col-sm-6' : 'col-md-5 col-sm-6' }}">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-1">
                        🗓️ Attendance Date:
                    </label>
                    <div class="input-group input-group-sm shadow-2xs border rounded-3 overflow-hidden bg-white">
                        <button type="button" class="btn btn-light border-0 text-muted fw-bold px-2" onclick="jumpDate(-1)" title="Previous Day">
                            ◀
                        </button>
                        <span class="input-group-text bg-white border-0 text-primary pe-1 ps-2" style="cursor: pointer;" onclick="if(window.fpDate) window.fpDate.open()" title="Click to open calendar">
                            📅
                        </span>
                        <input type="text" 
                               name="date" 
                               id="dateInput" 
                               class="form-control text-center fw-bold border-0 bg-white" 
                               value="{{ $date }}" 
                               readonly
                               style="cursor: pointer; font-size: 0.88rem;"
                               title="Click to open calendar">
                        <button type="button" class="btn btn-light border-0 text-muted fw-bold px-2" onclick="jumpDate(1)" title="Next Day">
                            ▶
                        </button>
                        <button type="button" class="btn btn-primary-subtle text-primary border-0 fw-bold px-3 small" onclick="setToday()">
                            Today
                        </button>
                    </div>
                </div>

                @if($mode === 'full_day')
                    <!-- Quick Action Buttons for Full Day -->
                    <div class="col-md-3 col-sm-12 text-md-end text-start d-flex gap-2 justify-content-md-end">
                        @if($selectedClass && $students->isNotEmpty())
                            <button type="button" class="btn btn-sm btn-outline-success fw-bold flex-grow-1 flex-md-grow-0 shadow-2xs" onclick="markAllPresent()">
                                ✓ All Present
                            </button>
                            <button type="submit" form="attendanceBatchForm" class="btn btn-sm btn-primary fw-bold flex-grow-1 flex-md-grow-0 shadow-2xs">
                                💾 Save All
                            </button>
                        @endif
                    </div>
                @endif
            </div>

            @if($mode === 'slot')
                <!-- Quick Action Buttons for Slot Mode -->
                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top flex-wrap gap-2">
                    <div class="text-muted small">
                        Showing students enrolled in <strong>{{ $selectedAssignment ? $selectedAssignment->subject->subject_name : 'Subject' }}</strong> for <strong>{{ $slotName }}</strong>.
                    </div>
                    @if($selectedClass && $students->isNotEmpty())
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-success fw-bold shadow-2xs" onclick="markAllPresent()">
                                ✓ Mark All Present in this Slot
                            </button>
                            <button type="submit" form="attendanceBatchForm" class="btn btn-sm btn-primary fw-bold shadow-2xs">
                                💾 Save Slot Attendance
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </form>
    </div>

    @if($selectedClass)
        <!-- Daily / Slot Statistics Banner -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-2">
                <div class="p-3 bg-white border rounded-3 shadow-2xs text-center">
                    <div class="text-muted small fw-semibold">
                        {{ $mode === 'slot' ? 'Enrolled in Subject' : 'Total Students' }}
                    </div>
                    <div class="h4 fw-bold text-dark my-1">{{ $stats['total'] }}</div>
                    <small class="text-muted">
                        {{ $mode === 'slot' && $selectedAssignment ? $selectedAssignment->subject->subject_name : $selectedClass->class_name }}
                    </small>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 bg-white border border-success-subtle rounded-3 shadow-2xs text-center bg-success-subtle bg-opacity-25">
                    <div class="text-success small fw-bold">🟢 Present</div>
                    <div class="h4 fw-bold text-success my-1" id="statPresentCount">{{ $stats['present'] }}</div>
                    <small class="text-muted">{{ $mode === 'slot' ? 'In Lesson' : 'In Attendance' }}</small>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 bg-white border border-danger-subtle rounded-3 shadow-2xs text-center bg-danger-subtle bg-opacity-25">
                    <div class="text-danger small fw-bold">🔴 Unexcused</div>
                    <div class="h4 fw-bold text-danger my-1" id="statUnexcusedCount">{{ $stats['unexcused'] }}</div>
                    <small class="text-muted">{{ $mode === 'slot' ? 'Skipped / Cut' : 'Absences' }}</small>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 bg-white border border-warning-subtle rounded-3 shadow-2xs text-center bg-warning-subtle bg-opacity-25">
                    <div class="text-warning small fw-bold">🟠 Excused</div>
                    <div class="h4 fw-bold text-warning my-1" id="statExcusedCount">{{ $stats['excused'] }}</div>
                    <small class="text-muted">Approved Leaves</small>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 bg-white border border-info-subtle rounded-3 shadow-2xs text-center bg-info-subtle bg-opacity-25">
                    <div class="text-info small fw-bold">🔵 Tardy</div>
                    <div class="h4 fw-bold text-info my-1" id="statTardyCount">{{ $stats['tardy'] }}</div>
                    <small class="text-muted">Late to Class</small>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 bg-white border rounded-3 shadow-2xs text-center">
                    <div class="text-muted small fw-semibold">
                        {{ $mode === 'slot' ? 'Lesson Rate' : 'Attendance Rate' }}
                    </div>
                    <div class="h4 fw-bold my-1 {{ ($stats['rate'] !== null && $stats['rate'] < $stats['minRate']) ? 'text-danger' : 'text-primary' }}" id="statRatePercent">
                        {{ $stats['rate'] !== null ? $stats['rate'] . '%' : '--' }}
                    </div>
                    <small class="text-muted">Policy: <strong>{{ $stats['minRate'] }}%</strong></small>
                </div>
            </div>
        </div>

        <!-- Attendance Roster Form -->
        <form method="POST" action="{{ route('attendance.batch_save') }}" id="attendanceBatchForm">
            @csrf
            <input type="hidden" name="class_id" value="{{ $selectedClass->id }}">
            <input type="hidden" name="mode" value="{{ $mode }}">
            <input type="hidden" name="attendance_date" value="{{ $date }}">
            <input type="hidden" name="session_type" value="{{ $sessionType }}">
            @if($mode === 'slot' && $selectedAssignment)
                <input type="hidden" name="assignment_id" value="{{ $selectedAssignment->id }}">
                <input type="hidden" name="slot_name" value="{{ $slotName }}">
            @endif

            <div class="card border rounded-3 bg-white shadow-xs overflow-hidden">
                <div class="card-header bg-light border-bottom p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        @if($mode === 'slot' && $selectedAssignment)
                            <span class="fw-bold text-dark">
                                📚 Lesson Roster: <strong>{{ $selectedAssignment->subject->subject_name }}</strong> • {{ $slotName }}
                            </span>
                        @else
                            <span class="fw-bold text-dark">📋 Roll Call Register: {{ $selectedClass->class_name }}</span>
                        @endif
                        <span class="badge bg-secondary-subtle text-secondary small">{{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span id="ajaxSaveStatus" class="badge bg-success-subtle text-success border border-success-subtle small px-2 py-1 d-none">
                            ✓ Saved live
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-success fw-bold px-3 shadow-2xs" onclick="markAllPresent()" title="Mark all enrolled students as Present">
                            ⚡ Mark All Present
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary fw-bold px-3 shadow-2xs">
                            💾 Save All Attendance
                        </button>
                    </div>
                </div>

                @if($students->isEmpty())
                    <div class="p-5 text-center text-muted">
                        <div class="fs-1 mb-2">👥</div>
                        <h6 class="fw-bold">No Students Enrolled</h6>
                        <p class="small mb-3">
                            @if($mode === 'slot')
                                No students found for {{ $selectedAssignment ? $selectedAssignment->subject->subject_name : 'this subject' }} in {{ $selectedClass->class_name }}.
                            @else
                                Add or import students into {{ $selectedClass->class_name }} to record attendance.
                            @endif
                        </p>
                        <a href="{{ route('config.students.index') }}" class="btn btn-sm btn-primary fw-bold">
                            Go to Students Directory →
                        </a>
                    </div>
                @else
                    <!-- Filter and Search Toolbar -->
                    <div class="bg-white border-bottom p-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <!-- Search Box -->
                            <div class="flex-grow-1" style="min-width: 240px; max-width: 360px;">
                                <div class="input-group input-group-sm border rounded-3 overflow-hidden shadow-2xs">
                                    <span class="input-group-text bg-light border-0 text-muted ps-3 pe-2">
                                        🔍
                                    </span>
                                    <input type="text" 
                                           id="studentSearchInput" 
                                           class="form-control border-0 ps-1 bg-white" 
                                           placeholder="Search student by name or ID..." 
                                           autocomplete="off"
                                           oninput="applyAttendanceFilters()"
                                           aria-label="Search student">
                                    <button class="btn btn-light border-0 text-muted px-2 d-none" 
                                            type="button" 
                                            id="clearSearchBtn" 
                                            onclick="clearSearchInput()"
                                            title="Clear search">✕</button>
                                </div>
                            </div>

                            <!-- Status Filter Pills & Counter -->
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="text-muted small fw-semibold text-uppercase me-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Filter:</span>
                                <div class="btn-group btn-group-sm shadow-2xs" role="group" aria-label="Status filter">
                                    <button type="button" class="btn btn-sm btn-dark text-white fw-bold px-2 py-1 att-filter-btn" id="filterBtnAll" onclick="setStatusFilter('ALL')">
                                        All <span class="badge bg-light text-dark ms-1" id="filterCountAll">{{ $students->count() }}</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success fw-bold px-2 py-1 att-filter-btn" id="filterBtnPresent" onclick="setStatusFilter('PRESENT')">
                                        🟢 Present <span class="badge bg-success-subtle text-success ms-1" id="filterCountPresent">{{ $stats['present'] }}</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold px-2 py-1 att-filter-btn" id="filterBtnUnexcused" onclick="setStatusFilter('UNEXCUSED')">
                                        🔴 Absent <span class="badge bg-danger-subtle text-danger ms-1" id="filterCountUnexcused">{{ $stats['unexcused'] }}</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold px-2 py-1 att-filter-btn" id="filterBtnExcused" onclick="setStatusFilter('EXCUSED')">
                                        🟠 Excused <span class="badge bg-warning-subtle text-dark ms-1" id="filterCountExcused">{{ $stats['excused'] }}</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info text-dark fw-bold px-2 py-1 att-filter-btn" id="filterBtnTardy" onclick="setStatusFilter('TARDY')">
                                        🔵 Tardy <span class="badge bg-info-subtle text-dark ms-1" id="filterCountTardy">{{ $stats['tardy'] }}</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary fw-bold px-2 py-1 att-filter-btn" id="filterBtnUnmarked" onclick="setStatusFilter('UNMARKED')">
                                        ⚪ Unmarked <span class="badge bg-secondary-subtle text-secondary ms-1" id="filterCountUnmarked">{{ $stats['pending'] ?? 0 }}</span>
                                    </button>
                                </div>

                                <div class="badge bg-light text-secondary border px-2 py-1 small fw-normal ms-1">
                                    Showing <strong id="visibleStudentCount" class="text-dark">{{ $students->count() }}</strong> of {{ $students->count() }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th style="width: 50px;" class="text-center">#</th>
                                    <th style="min-width: 220px;">Student Information</th>
                                    <th style="min-width: 320px;" class="text-center">Attendance Status</th>
                                    <th style="min-width: 200px;">Remarks / Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="noResultsRow" style="display: none;">
                                    <td colspan="4" class="text-center py-5 text-muted bg-light-subtle">
                                        <div class="fs-1 mb-2">🔍</div>
                                        <h6 class="fw-bold text-dark mb-1">No Matching Students Found</h6>
                                        <p class="small text-muted mb-3">No student matched your search query or selected attendance status filter.</p>
                                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold px-3 shadow-2xs" onclick="resetAttendanceFilters()">
                                            ✕ Reset Search &amp; Show All
                                        </button>
                                    </td>
                                </tr>
                                @foreach($students as $index => $student)
                                    @php
                                        $rec = $existingRecords->get($student->id);
                                        $currentStatus = $rec ? $rec->status : null;
                                        $currentRemarks = $rec ? $rec->remarks : '';
                                    @endphp
                                    <tr id="row-{{ $student->id }}" 
                                        class="transition att-student-row"
                                        data-student-name="{{ strtolower($student->full_name) }}"
                                        data-admission-number="{{ strtolower($student->admission_number) }}"
                                        data-student-id="{{ $student->id }}">
                                        <td class="text-center text-muted fw-bold small">
                                            {{ $index + 1 }}
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-placeholder rounded-circle bg-light text-primary fw-bold d-inline-flex justify-content-center align-items-center border" style="width: 34px; height: 34px; font-size: 0.8rem;">
                                                    {{ strtoupper(substr($student->full_name, 0, 2)) }}
                                                </div>
                                                <div>
                                                    <a href="{{ route('config.students.profile', $student->id) }}" target="_blank" class="fw-bold text-dark small lh-sm text-decoration-none hover-primary d-inline-flex align-items-center gap-1" title="View Student Profile">
                                                        <span>{{ $student->full_name }}</span>
                                                        <span class="text-muted" style="font-size: 0.65rem;">↗</span>
                                                    </a>
                                                    <div class="text-muted" style="font-size: 0.72rem;">
                                                        ID: <span class="font-monospace fw-semibold">{{ $student->admission_number }}</span>
                                                        @if($student->gender)
                                                            • <span class="text-capitalize">{{ strtolower($student->gender) }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm w-100 shadow-2xs" role="group" aria-label="Status for {{ $student->full_name }}">
                                                <!-- Present Radio -->
                                                <input type="radio" 
                                                       class="btn-check att-radio" 
                                                       name="attendance[{{ $student->id }}][status]" 
                                                       id="status-present-{{ $student->id }}" 
                                                       value="PRESENT" 
                                                       {{ $currentStatus === 'PRESENT' ? 'checked' : '' }}
                                                       onchange="quickSaveStudent({{ $student->id }}, 'PRESENT')">
                                                <label class="btn btn-outline-success fw-bold px-2 py-1" for="status-present-{{ $student->id }}">
                                                    🟢 Present
                                                </label>

                                                <!-- Unexcused Absence Radio -->
                                                <input type="radio" 
                                                       class="btn-check att-radio" 
                                                       name="attendance[{{ $student->id }}][status]" 
                                                       id="status-absent-{{ $student->id }}" 
                                                       value="UNEXCUSED" 
                                                       {{ $currentStatus === 'UNEXCUSED' ? 'checked' : '' }}
                                                       onchange="quickSaveStudent({{ $student->id }}, 'UNEXCUSED')">
                                                <label class="btn btn-outline-danger fw-bold px-2 py-1" for="status-absent-{{ $student->id }}">
                                                    🔴 Absent
                                                </label>

                                                <!-- Excused Absence Radio -->
                                                <input type="radio" 
                                                       class="btn-check att-radio" 
                                                       name="attendance[{{ $student->id }}][status]" 
                                                       id="status-excused-{{ $student->id }}" 
                                                       value="EXCUSED" 
                                                       {{ $currentStatus === 'EXCUSED' ? 'checked' : '' }}
                                                       onchange="quickSaveStudent({{ $student->id }}, 'EXCUSED')">
                                                <label class="btn btn-outline-warning fw-bold px-2 py-1 text-dark" for="status-excused-{{ $student->id }}">
                                                    🟠 Excused
                                                </label>

                                                <!-- Tardy Radio -->
                                                <input type="radio" 
                                                       class="btn-check att-radio" 
                                                       name="attendance[{{ $student->id }}][status]" 
                                                       id="status-tardy-{{ $student->id }}" 
                                                       value="TARDY" 
                                                       {{ $currentStatus === 'TARDY' ? 'checked' : '' }}
                                                       onchange="quickSaveStudent({{ $student->id }}, 'TARDY')">
                                                <label class="btn btn-outline-info fw-bold px-2 py-1 text-dark" for="status-tardy-{{ $student->id }}">
                                                    🔵 Tardy
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   name="attendance[{{ $student->id }}][remarks]" 
                                                   id="remarks-{{ $student->id }}" 
                                                   class="form-control form-control-sm text-muted" 
                                                   placeholder="Notes (optional)..." 
                                                   value="{{ $currentRemarks }}"
                                                   onblur="quickSaveRemarks({{ $student->id }})">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                <div class="card-footer bg-light p-3 text-end">
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-4 shadow-2xs">
                        💾 Save All Attendance Records
                    </button>
                </div>
            </div>
        </form>
    @else
        <div class="alert alert-info">
            Please configure at least one class under <strong>Levels &amp; Classes</strong> to record attendance.
        </div>
    @endif

</div>

<!-- JavaScript for Dynamic Interactions, Filter & Search, & Live Auto-Save -->
<script>
    const currentMode = "{{ $mode }}";
    const currentAssignmentId = "{{ $selectedAssignment?->id ?? '' }}";
    const currentSlotName = "{{ $slotName ?? '' }}";
    let currentFilterStatus = 'ALL';

    document.addEventListener('DOMContentLoaded', function() {
        // Prevent form submission on Enter inside search bar
        const searchInput = document.getElementById('studentSearchInput');
        if (searchInput) {
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
            });
        }

        // Initialize Filter Badges
        updateFilterBadges();

        // Calendar Flatpickr Initialization
        if (typeof flatpickr !== 'undefined') {
            window.fpDate = flatpickr("#dateInput", {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "D, M j, Y",
                altInputClass: "form-control text-center fw-bold border-0 bg-white shadow-none",
                defaultDate: "{{ $date }}",
                disableMobile: true,
                animate: true,
                onChange: function(selectedDates, dateStr, instance) {
                    if (dateStr) {
                        document.getElementById('filterForm').submit();
                    }
                },
                onReady: function(selectedDates, dateStr, instance) {
                    const footer = document.createElement('div');
                    footer.className = 'flatpickr-custom-footer';
                    footer.innerHTML = `
                        <button type="button" class="btn-quick" id="fpBtnYesterday">◀ Yesterday</button>
                        <button type="button" class="btn-quick btn-today" id="fpBtnToday">🎯 Today</button>
                        <button type="button" class="btn-quick" id="fpBtnTomorrow">Tomorrow ▶</button>
                    `;
                    instance.calendarContainer.appendChild(footer);

                    footer.querySelector('#fpBtnYesterday').addEventListener('click', (e) => {
                        e.stopPropagation();
                        jumpDate(-1);
                    });
                    footer.querySelector('#fpBtnToday').addEventListener('click', (e) => {
                        e.stopPropagation();
                        setToday();
                    });
                    footer.querySelector('#fpBtnTomorrow').addEventListener('click', (e) => {
                        e.stopPropagation();
                        jumpDate(1);
                    });
                }
            });
        }
    });

    /* ==========================================================================
       Status Filter & Live Search Functions
       ========================================================================== */
    function setStatusFilter(status) {
        currentFilterStatus = status;

        const btnAll = document.getElementById('filterBtnAll');
        const btnPresent = document.getElementById('filterBtnPresent');
        const btnUnexcused = document.getElementById('filterBtnUnexcused');
        const btnExcused = document.getElementById('filterBtnExcused');
        const btnTardy = document.getElementById('filterBtnTardy');
        const btnUnmarked = document.getElementById('filterBtnUnmarked');

        // Reset all buttons to outline style
        if (btnAll) btnAll.className = 'btn btn-sm btn-outline-secondary fw-bold px-2 py-1 att-filter-btn';
        if (btnPresent) btnPresent.className = 'btn btn-sm btn-outline-success fw-bold px-2 py-1 att-filter-btn';
        if (btnUnexcused) btnUnexcused.className = 'btn btn-sm btn-outline-danger fw-bold px-2 py-1 att-filter-btn';
        if (btnExcused) btnExcused.className = 'btn btn-sm btn-outline-warning text-dark fw-bold px-2 py-1 att-filter-btn';
        if (btnTardy) btnTardy.className = 'btn btn-sm btn-outline-info text-dark fw-bold px-2 py-1 att-filter-btn';
        if (btnUnmarked) btnUnmarked.className = 'btn btn-sm btn-outline-secondary fw-bold px-2 py-1 att-filter-btn';

        // Apply active solid styling
        if (status === 'ALL' && btnAll) {
            btnAll.className = 'btn btn-sm btn-dark text-white fw-bold px-2 py-1 att-filter-btn shadow-xs active';
        } else if (status === 'PRESENT' && btnPresent) {
            btnPresent.className = 'btn btn-sm btn-success text-white fw-bold px-2 py-1 att-filter-btn shadow-xs active';
        } else if (status === 'UNEXCUSED' && btnUnexcused) {
            btnUnexcused.className = 'btn btn-sm btn-danger text-white fw-bold px-2 py-1 att-filter-btn shadow-xs active';
        } else if (status === 'EXCUSED' && btnExcused) {
            btnExcused.className = 'btn btn-sm btn-warning text-dark fw-bold px-2 py-1 att-filter-btn shadow-xs active';
        } else if (status === 'TARDY' && btnTardy) {
            btnTardy.className = 'btn btn-sm btn-info text-dark fw-bold px-2 py-1 att-filter-btn shadow-xs active';
        } else if (status === 'UNMARKED' && btnUnmarked) {
            btnUnmarked.className = 'btn btn-sm btn-secondary text-white fw-bold px-2 py-1 att-filter-btn shadow-xs active';
        }

        applyAttendanceFilters();
    }

    function applyAttendanceFilters() {
        const searchInput = document.getElementById('studentSearchInput');
        const clearBtn = document.getElementById('clearSearchBtn');
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';

        if (clearBtn) {
            clearBtn.classList.toggle('d-none', query.length === 0);
        }

        const rows = document.querySelectorAll('.att-student-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const name = (row.getAttribute('data-student-name') || '').toLowerCase();
            const adm = (row.getAttribute('data-admission-number') || '').toLowerCase();

            const checkedRadio = row.querySelector('input.att-radio:checked');
            const status = checkedRadio ? checkedRadio.value : 'UNMARKED';

            const matchesQuery = !query || name.includes(query) || adm.includes(query);
            const matchesStatus = (currentFilterStatus === 'ALL') || (status === currentFilterStatus);

            if (matchesQuery && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const visibleEl = document.getElementById('visibleStudentCount');
        if (visibleEl) visibleEl.textContent = visibleCount;

        const noResultsRow = document.getElementById('noResultsRow');
        if (noResultsRow) {
            noResultsRow.style.display = (visibleCount === 0) ? '' : 'none';
        }
    }

    function clearSearchInput() {
        const searchInput = document.getElementById('studentSearchInput');
        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
        }
        applyAttendanceFilters();
    }

    function resetAttendanceFilters() {
        const searchInput = document.getElementById('studentSearchInput');
        if (searchInput) {
            searchInput.value = '';
        }
        setStatusFilter('ALL');
    }

    function updateFilterBadges() {
        const present = document.querySelectorAll('input.att-radio[value="PRESENT"]:checked').length;
        const unexcused = document.querySelectorAll('input.att-radio[value="UNEXCUSED"]:checked').length;
        const excused = document.querySelectorAll('input.att-radio[value="EXCUSED"]:checked').length;
        const tardy = document.querySelectorAll('input.att-radio[value="TARDY"]:checked').length;
        const total = document.querySelectorAll('.att-student-row').length;
        const unmarked = Math.max(0, total - (present + unexcused + excused + tardy));

        const elAll = document.getElementById('filterCountAll');
        const elPres = document.getElementById('filterCountPresent');
        const elUnex = document.getElementById('filterCountUnexcused');
        const elExc = document.getElementById('filterCountExcused');
        const elTar = document.getElementById('filterCountTardy');
        const elUnmarked = document.getElementById('filterCountUnmarked');

        if (elAll) elAll.textContent = total;
        if (elPres) elPres.textContent = present;
        if (elUnex) elUnex.textContent = unexcused;
        if (elExc) elExc.textContent = excused;
        if (elTar) elTar.textContent = tardy;
        if (elUnmarked) elUnmarked.textContent = unmarked;
    }

    /* ==========================================================================
       Date Helpers & Quick Actions
       ========================================================================== */
    function jumpDate(days) {
        const dateInput = document.getElementById('dateInput');
        if (!dateInput.value) return;
        const current = new Date(dateInput.value);
        current.setDate(current.getDate() + days);
        const nextVal = current.toISOString().split('T')[0];
        dateInput.value = nextVal;
        if (window.fpDate) {
            window.fpDate.setDate(nextVal, false);
        }
        document.getElementById('filterForm').submit();
    }

    function setToday() {
        const today = new Date().toISOString().split('T')[0];
        const dateInput = document.getElementById('dateInput');
        dateInput.value = today;
        if (window.fpDate) {
            window.fpDate.setDate(today, false);
        }
        document.getElementById('filterForm').submit();
    }

    function markAllPresent() {
        const presentRadios = document.querySelectorAll('input.att-radio[value="PRESENT"]');
        presentRadios.forEach(radio => {
            radio.checked = true;
        });
        showStatusNotice('Marked all as Present. Click Save All or let auto-save persist.');
        recalcStatsLocally();
        updateFilterBadges();
        applyAttendanceFilters();
    }

    function quickSaveStudent(studentId, status) {
        const remarks = document.getElementById('remarks-' + studentId)?.value || '';
        const date = document.getElementById('dateInput').value;

        // Immediately update local badges & filters
        updateFilterBadges();
        applyAttendanceFilters();
        recalcStatsLocally();

        const payload = {
            student_id: studentId,
            attendance_date: date,
            status: status,
            remarks: remarks,
            mode: currentMode,
            assignment_id: currentAssignmentId,
            slot_name: currentSlotName
        };

        fetch("{{ route('attendance.quick_update') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.stats) {
                updateStatsUI(data.stats);
                showStatusNotice('✓ Saved live');
            }
        })
        .catch(err => console.error('Save error:', err));
    }

    function quickSaveRemarks(studentId) {
        const selectedRadio = document.querySelector('input[name="attendance[' + studentId + '][status]"]:checked');
        if (!selectedRadio) return;
        quickSaveStudent(studentId, selectedRadio.value);
    }

    function updateStatsUI(stats) {
        const elPresent = document.getElementById('statPresentCount');
        const elUnexcused = document.getElementById('statUnexcusedCount');
        const elExcused = document.getElementById('statExcusedCount');
        const elTardy = document.getElementById('statTardyCount');
        const elRate = document.getElementById('statRatePercent');

        if (elPresent) elPresent.textContent = stats.present;
        if (elUnexcused) elUnexcused.textContent = stats.unexcused;
        if (elExcused) elExcused.textContent = stats.excused;
        if (elTardy) elTardy.textContent = stats.tardy;
        if (elRate) elRate.textContent = stats.rate !== null && stats.rate !== undefined ? (stats.rate + '%') : '--';
    }

    function recalcStatsLocally() {
        const present = document.querySelectorAll('input.att-radio[value="PRESENT"]:checked').length;
        const unexcused = document.querySelectorAll('input.att-radio[value="UNEXCUSED"]:checked').length;
        const excused = document.querySelectorAll('input.att-radio[value="EXCUSED"]:checked').length;
        const tardy = document.querySelectorAll('input.att-radio[value="TARDY"]:checked').length;
        const totalMarked = present + unexcused + excused + tardy;
        const totalStudents = document.querySelectorAll('.att-student-row').length;
        const rateBase = Math.max(1, totalStudents);

        const stats = {
            present: present,
            unexcused: unexcused,
            excused: excused,
            tardy: tardy,
            rate: totalMarked > 0 ? Math.round(((present + tardy) / rateBase) * 1000) / 10 : null
        };
        updateStatsUI(stats);
    }

    function showStatusNotice(msg) {
        const statusBadge = document.getElementById('ajaxSaveStatus');
        if (statusBadge) {
            statusBadge.textContent = msg;
            statusBadge.classList.remove('d-none');
            setTimeout(() => {
                statusBadge.classList.add('d-none');
            }, 2500);
        }
    }
</script>
@endsection
