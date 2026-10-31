@extends('layouts.app')

@section('title', 'School-Wide Attendance Policy — School Reporting Hub')

@section('content')
<div class="card-custom p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom pb-3 mb-4">
        <div>
            <h5 class="fw-bold mb-1">Pillar 1: School Attendance System Configuration</h5>
            <p class="text-muted small mb-0">Universal policy for <strong>{{ $activeSchool->school_name }}</strong> (One single system for the entire school).</p>
        </div>
        <button type="submit" form="attendanceForm" class="btn btn-primary btn-sm fw-bold px-4">
            💾 Save Attendance Policy
        </button>
    </div>

    <form id="attendanceForm" method="POST" action="{{ route('config.attendance.update') }}">
        @csrf
        <input type="hidden" name="school_id" value="{{ $activeSchool->school_id }}">

        <div class="row g-4">
            <!-- Left Column: Mode & Thresholds -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded-3 border">
                    <h6 class="fw-bold text-uppercase text-muted mb-3" style="font-size: 0.8rem;">1. Roll Call Mode & Thresholds</h6>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Roll Call Tracking Mode</label>
                        <div class="form-check p-2 bg-white rounded border mb-2">
                            <input class="form-check-input ms-1" type="radio" name="tracking_mode" id="mode1" value="DAILY_HOMEROOM" {{ $policy->tracking_mode === 'DAILY_HOMEROOM' ? 'checked' : '' }}>
                            <label class="form-check-label ms-2 small fw-bold" for="mode1">
                                Daily Homeroom Roll Call (Morning session)
                                <div class="text-muted fw-normal">Class homeroom teacher records attendance once daily.</div>
                            </label>
                        </div>
                        <div class="form-check p-2 bg-white rounded border">
                            <input class="form-check-input ms-1" type="radio" name="tracking_mode" id="mode2" value="PERIOD_BASED" {{ $policy->tracking_mode === 'PERIOD_BASED' ? 'checked' : '' }}>
                            <label class="form-check-label ms-2 small fw-bold" for="mode2">
                                Period-by-Period Roll Call (Lesson-by-lesson)
                                <div class="text-muted fw-normal">Attendance is recorded for each subject period independently.</div>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Minimum Attendance Policy Required (%)</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.5" name="min_attendance_rate" value="{{ $policy->min_attendance_rate }}" class="form-control fw-bold" required>
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted">Students falling below this statutory cutoff are flagged on report cards as with low attendance.</small>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Chronic Truancy Consecutive Days Alert</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="chronic_truancy_days" value="{{ $policy->chronic_truancy_days }}" class="form-control fw-bold" required>
                            <span class="input-group-text">Days</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Lateness Conversion -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded-3 border">
                    <h6 class="fw-bold text-uppercase text-muted mb-3" style="font-size: 0.8rem;">2. Lateness Formulas & Report Integration</h6>

                    <div class="mb-3 p-3 bg-white rounded border">
                        <label class="form-label fw-semibold small">Lateness / Tardy Conversion Rule</label>
                        <div class="d-flex align-items-center gap-2 small">
                            <span>Every</span>
                            <input type="number" name="tardies_per_absence" value="{{ $policy->tardies_per_absence }}" class="form-control form-control-sm text-center fw-bold" style="width: 70px;">
                            <span>Tardies automatically count as <strong>1 Unexcused Absence</strong>.</span>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold small">JasperReports Report Card Elements</label>
                        <div class="form-check small mb-1">
                            <input class="form-check-input" type="checkbox" checked disabled>
                            <label class="form-check-label">Show Total School Days Enrolled in Term</label>
                        </div>
                        <div class="form-check small mb-1">
                            <input class="form-check-input" type="checkbox" checked disabled>
                            <label class="form-check-label">Show Days Present & Days Absent Breakdown</label>
                        </div>
                        <div class="form-check small mb-1">
                            <input class="form-check-input" type="checkbox" checked disabled>
                            <label class="form-check-label">Show Official Attendance Rate Percentage</label>
                        </div>
                        <div class="form-check small">
                            <input class="form-check-input" type="checkbox" checked disabled>
                            <label class="form-check-label">Print "Statutory Attendance Met" Verification Badge</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
