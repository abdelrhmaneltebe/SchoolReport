@extends('layouts.app')

@section('title', $mode === 'slot' ? 'Subject & Slot Attendance Summary' : 'Class Attendance Summary & Sheets')

@section('content')
<div class="container-fluid px-0">

    <!-- Header Navigation Tabs -->
    <div class="card border rounded-3 p-3 mb-4 bg-white shadow-xs">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="p-2 {{ $mode === 'slot' ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info' }} rounded-3 fs-3">
                    {{ $mode === 'slot' ? '📚' : '📊' }}
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge {{ $mode === 'slot' ? 'bg-info text-dark' : 'bg-primary text-white' }} px-2 py-1 small">
                            {{ $mode === 'slot' ? 'Pillar 1 • Subject & Slot Summary' : 'Pillar 1 • Full Day Broadsheet' }}
                        </span>
                        <span class="badge bg-light text-secondary border font-monospace">{{ $activeSchool->school_code }}</span>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark">
                        {{ $mode === 'slot' ? 'Subject Period & Timetable Slot Summary' : 'Class Attendance Summary & Broadsheet' }}
                    </h4>
                    <p class="text-muted small mb-0">
                        @if($mode === 'slot')
                            Aggregated lesson attendance records, skipped period tracking, and subject attendance rates for <strong>{{ $activeSchool->school_name }}</strong>.
                        @else
                            Aggregated attendance records, tardy conversions, and statutory truancy tracking for <strong>{{ $activeSchool->school_name }}</strong>.
                        @endif
                    </p>
                </div>
            </div>

            <!-- Top Mode Tabs -->
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('attendance.index', ['mode' => $mode]) }}" class="btn btn-sm btn-outline-secondary fw-semibold">
                    📋 Roll Call Register
                </a>
                <a href="{{ route('attendance.summary', ['mode' => $mode]) }}" class="btn btn-sm btn-primary fw-bold shadow-2xs">
                    📊 Sheet &amp; Summaries
                </a>
                <a href="{{ route('config.attendance') }}" class="btn btn-sm btn-outline-secondary fw-semibold">
                    ⚙️ Policy Settings
                </a>
            </div>
        </div>
    </div>

    <!-- Mode Selector & Filter Form -->
    <div class="card border rounded-3 p-3 mb-4 bg-white shadow-xs">
        <!-- Segmented Mode Switcher -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-3 border-bottom">
            <div class="btn-group btn-group-sm p-1 bg-light rounded-pill border shadow-2xs" role="group">
                <a href="{{ route('attendance.summary', ['mode' => 'full_day', 'class_id' => $selectedClass?->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
                   class="btn rounded-pill px-3 fw-bold {{ $mode === 'full_day' ? 'btn-primary shadow-xs' : 'btn-light text-secondary' }}">
                    ☀️ Full Day Summary (Homeroom)
                </a>
                <a href="{{ route('attendance.summary', ['mode' => 'slot', 'class_id' => $selectedClass?->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
                   class="btn rounded-pill px-3 fw-bold {{ $mode === 'slot' ? 'btn-primary shadow-xs' : 'btn-light text-secondary' }}">
                    📚 Subject &amp; Slot Summary
                </a>
            </div>

            @if($mode === 'slot' && $selectedAssignment)
                <span class="badge bg-primary-subtle text-primary border px-2 py-1 small">
                    Filtering: 📖 {{ $selectedAssignment->subject->subject_name }}
                </span>
            @endif
        </div>

        <form method="GET" action="{{ route('attendance.summary') }}" id="summaryFilterForm">
            <input type="hidden" name="mode" value="{{ $mode }}">

            <div class="row g-3 align-items-end">
                <div class="{{ $mode === 'slot' ? 'col-md-3 col-sm-6' : 'col-md-4 col-sm-6' }}">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-1">
                        🏫 Select Class / Section:
                    </label>
                    <select name="class_id" class="form-select form-select-sm fw-bold border-primary shadow-2xs" onchange="document.getElementById('summaryFilterForm').submit()">
                        @foreach($classes as $cls)
                            <option value="{{ $cls->id }}" {{ ($selectedClass && $selectedClass->id == $cls->id) ? 'selected' : '' }}>
                                {{ $cls->class_name }} ({{ $cls->gradeLevel->grade_name ?? 'Level' }}) • {{ $cls->students->count() }} Students
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($mode === 'slot')
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1">
                            📖 Filter by Subject (Optional):
                        </label>
                        <select name="assignment_id" class="form-select form-select-sm fw-bold border-info shadow-2xs" onchange="document.getElementById('summaryFilterForm').submit()">
                            <option value="">-- All Assigned Subjects --</option>
                            @foreach($assignments as $assign)
                                <option value="{{ $assign->id }}" {{ ($selectedAssignment && $selectedAssignment->id == $assign->id) ? 'selected' : '' }}>
                                    {{ $assign->subject->subject_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="{{ $mode === 'slot' ? 'col-md-4 col-sm-6' : 'col-md-5 col-sm-6' }}">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-1">
                        🗓️ Date Range:
                    </label>
                    <div class="input-group input-group-sm shadow-2xs">
                        <span class="input-group-text bg-light text-muted small">From</span>
                        <input type="date" name="start_date" class="form-control text-center fw-semibold" value="{{ $startDate }}" onchange="document.getElementById('summaryFilterForm').submit()">
                        <span class="input-group-text bg-light text-muted small">To</span>
                        <input type="date" name="end_date" class="form-control text-center fw-semibold" value="{{ $endDate }}" onchange="document.getElementById('summaryFilterForm').submit()">
                    </div>
                </div>

                <div class="{{ $mode === 'slot' ? 'col-md-2 col-sm-12' : 'col-md-3 col-sm-12' }} text-md-end text-start">
                    @if($selectedClass)
                        @php
                            $exportParams = [
                                'class_id' => $selectedClass->id,
                                'start_date' => $startDate,
                                'end_date' => $endDate,
                                'mode' => $mode,
                            ];
                            if ($mode === 'slot' && $selectedAssignment) {
                                $exportParams['assignment_id'] = $selectedAssignment->id;
                            }
                        @endphp
                        <a href="{{ route('attendance.export', $exportParams) }}" class="btn btn-sm btn-outline-success fw-bold w-100 shadow-2xs">
                            📥 Export CSV
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Attendance Summary Table -->
    @if($selectedClass)
        <div class="card border rounded-3 bg-white shadow-xs overflow-hidden">
            <div class="card-header bg-light border-bottom p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-bold text-dark">
                    📊 {{ $mode === 'slot' ? 'Subject & Slot Attendance Summary' : 'Cumulative Homeroom Attendance Broadsheet' }}: {{ $selectedClass->class_name }}
                    @if($mode === 'slot' && $selectedAssignment)
                        • <span class="text-primary">{{ $selectedAssignment->subject->subject_name }}</span>
                    @endif
                </span>
                <div class="small text-muted">
                    Min Required Rate: <strong class="text-danger">{{ $policy->min_attendance_rate }}%</strong> • Tardy Conversion: <strong>{{ $policy->tardies_per_absence }} Tardies = 1 Absence</strong>
                </div>
            </div>

            @if(empty($studentSummaries))
                <div class="p-5 text-center text-muted">
                    <h6>No students enrolled in this class.</h6>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th class="text-start" style="min-width: 200px;">Student</th>
                                <th>{{ $mode === 'slot' ? 'Logged Slots' : 'Logged Days' }}</th>
                                <th>🟢 Present</th>
                                <th>🔴 Unexcused</th>
                                <th>🟠 Excused</th>
                                <th>🔵 Tardy</th>
                                <th>Converted</th>
                                <th>Net Absences</th>
                                <th>{{ $mode === 'slot' ? 'Slot Rate %' : 'Attendance %' }}</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($studentSummaries as $index => $row)
                                <tr>
                                    <td class="text-muted fw-bold small">{{ $index + 1 }}</td>
                                    <td class="text-start">
                                        <div class="fw-bold text-dark small">{{ $row['student']->full_name }}</div>
                                        <small class="text-muted font-monospace">{{ $row['student']->admission_number }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $row['total_days'] }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">{{ $row['present'] }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold {{ $row['unexcused'] > 0 ? 'text-danger' : 'text-muted' }}">
                                            {{ $row['unexcused'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-warning text-dark">{{ $row['excused'] }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-info text-dark">{{ $row['tardy'] }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary small">
                                            +{{ $row['equivalent_tardies'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold {{ $row['effective_absences'] > 0 ? 'text-danger' : 'text-muted' }}">
                                            {{ $row['effective_absences'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold {{ $row['rate'] < $policy->min_attendance_rate ? 'text-danger' : 'text-success' }}">
                                            {{ $row['rate'] }}%
                                        </span>
                                    </td>
                                    <td>
                                        @if($row['is_truant'])
                                            <span class="badge bg-danger text-white px-2 py-1 small fw-bold">
                                                ⚠️ Truancy Warning
                                            </span>
                                        @else
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small fw-bold">
                                                ✓ Regular
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

</div>
@endsection
