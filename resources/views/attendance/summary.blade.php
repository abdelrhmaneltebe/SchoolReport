@extends('layouts.app')

@section('title', 'Class Attendance Summary & Sheets')

@section('content')
<div class="container-fluid px-0">

    <!-- Header Navigation Tabs -->
    <div class="card border rounded-3 p-3 mb-4 bg-white shadow-xs">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="p-2 bg-info-subtle text-info rounded-3 fs-3">
                    📊
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-primary text-white px-2 py-1 small">Pillar 1 Summary</span>
                        <span class="badge bg-light text-secondary border font-monospace">{{ $activeSchool->school_code }}</span>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark">Class Attendance Summary &amp; Broadsheet</h4>
                    <p class="text-muted small mb-0">
                        Aggregated attendance records, tardy conversions, and statutory truancy tracking for <strong>{{ $activeSchool->school_name }}</strong>.
                    </p>
                </div>
            </div>

            <!-- Top Mode Tabs -->
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-outline-secondary fw-semibold">
                    📋 Daily Roll Call
                </a>
                <a href="{{ route('attendance.summary') }}" class="btn btn-sm btn-primary fw-bold shadow-2xs">
                    📊 Sheet &amp; Summaries
                </a>
                <a href="{{ route('config.attendance') }}" class="btn btn-sm btn-outline-secondary fw-semibold">
                    ⚙️ Policy Settings
                </a>
            </div>
        </div>
    </div>

    <!-- Filter & Range Form -->
    <div class="card border rounded-3 p-3 mb-4 bg-white shadow-xs">
        <form method="GET" action="{{ route('attendance.summary') }}" id="summaryFilterForm">
            <div class="row g-3 align-items-end">
                <div class="col-md-4 col-sm-6">
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

                <div class="col-md-5 col-sm-6">
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

                <div class="col-md-3 col-sm-12 text-md-end text-start">
                    @if($selectedClass)
                        <a href="{{ route('attendance.export', ['class_id' => $selectedClass->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-sm btn-outline-success fw-bold w-100 w-md-auto shadow-2xs">
                            📥 Export CSV Report
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Attendance Summary Table -->
    @if($selectedClass)
        <div class="card border rounded-3 bg-white shadow-xs overflow-hidden">
            <div class="card-header bg-light border-bottom p-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark">
                    📊 Cumulative Attendance Broadsheet: {{ $selectedClass->class_name }}
                </span>
                <div class="small text-muted">
                    Policy Cutoff: <strong class="text-danger">{{ $policy->min_attendance_rate }}%</strong> • Tardy Rule: <strong>{{ $policy->tardies_per_absence }} Tardies = 1 Absence</strong>
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
                                <th>Logged Days</th>
                                <th>🟢 Present</th>
                                <th>🔴 Unexcused</th>
                                <th>🟠 Excused</th>
                                <th>🔵 Tardy</th>
                                <th>Converted</th>
                                <th>Net Absences</th>
                                <th>Attendance %</th>
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
