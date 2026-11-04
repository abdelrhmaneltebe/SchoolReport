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
                    <div class="input-group input-group-sm shadow-2xs">
                        <button type="button" class="btn btn-outline-secondary fw-bold" onclick="jumpDate(-1)" title="Previous Day">
                            ◀ Prev
                        </button>
                        <input type="date" 
                               name="date" 
                               id="dateInput" 
                               class="form-control text-center fw-bold" 
                               value="{{ $date }}" 
                               onclick="try{this.showPicker()}catch(e){}" 
                               onchange="document.getElementById('filterForm').submit()"
                               style="cursor: pointer;"
                               title="Click to open calendar">
                        <button type="button" class="btn btn-outline-secondary fw-bold" onclick="jumpDate(1)" title="Next Day">
                            Next ▶
                        </button>
                        <button type="button" class="btn btn-light border fw-semibold" onclick="setToday()">
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
                                @foreach($students as $index => $student)
                                    @php
                                        $rec = $existingRecords->get($student->id);
                                        $currentStatus = $rec ? $rec->status : 'PRESENT';
                                        $currentRemarks = $rec ? $rec->remarks : '';
                                    @endphp
                                    <tr id="row-{{ $student->id }}" class="transition">
                                        <td class="text-center text-muted fw-bold small">
                                            {{ $index + 1 }}
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-placeholder rounded-circle bg-light text-primary fw-bold d-inline-flex justify-content-center align-items-center border" style="width: 34px; height: 34px; font-size: 0.8rem;">
                                                    {{ strtoupper(substr($student->full_name, 0, 2)) }}
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark small lh-sm">{{ $student->full_name }}</div>
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

<!-- JavaScript for Dynamic Interactions & Live Auto-Save -->
<script>
    const currentMode = "{{ $mode }}";
    const currentAssignmentId = "{{ $selectedAssignment?->id ?? '' }}";
    const currentSlotName = "{{ $slotName ?? '' }}";

    document.addEventListener('DOMContentLoaded', function() {
        const dateInput = document.getElementById('dateInput');
        if (dateInput) {
            dateInput.addEventListener('click', function() {
                try {
                    if (typeof this.showPicker === 'function') {
                        this.showPicker();
                    }
                } catch (e) {}
            });
        }
    });

    function jumpDate(days) {
        const dateInput = document.getElementById('dateInput');
        if (!dateInput.value) return;
        const current = new Date(dateInput.value);
        current.setDate(current.getDate() + days);
        dateInput.value = current.toISOString().split('T')[0];
        document.getElementById('filterForm').submit();
    }

    function setToday() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('dateInput').value = today;
        document.getElementById('filterForm').submit();
    }

    function markAllPresent() {
        const presentRadios = document.querySelectorAll('input[type="radio"][value="PRESENT"]');
        presentRadios.forEach(radio => {
            radio.checked = true;
        });
        showStatusNotice('Marked all as Present. Click Save All or let auto-save persist.');
        recalcStatsLocally();
    }

    function quickSaveStudent(studentId, status) {
        const remarks = document.getElementById('remarks-' + studentId)?.value || '';
        const date = document.getElementById('dateInput').value;

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
        if (elRate) elRate.textContent = stats.rate + '%';
    }

    function recalcStatsLocally() {
        const present = document.querySelectorAll('input[type="radio"][value="PRESENT"]:checked').length;
        const unexcused = document.querySelectorAll('input[type="radio"][value="UNEXCUSED"]:checked').length;
        const excused = document.querySelectorAll('input[type="radio"][value="EXCUSED"]:checked').length;
        const tardy = document.querySelectorAll('input[type="radio"][value="TARDY"]:checked').length;
        const total = present + unexcused + excused + tardy;

        const stats = {
            present: present,
            unexcused: unexcused,
            excused: excused,
            tardy: tardy,
            rate: total > 0 ? Math.round(((present + tardy) / total) * 1000) / 10 : 0
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
