@extends('layouts.app')

@section('title', 'Pre-Flight Marksheet Audit')

@section('content')
<div class="card card-custom p-4 mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom pb-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary px-2 py-1 small">Pre-Flight Audit</span>
                <h4 class="fw-bold mb-0">Class Marksheet Validation: {{ $class->class_name }} ({{ $class->class_code }})</h4>
            </div>
            <p class="text-muted small mb-0">
                School: <strong>{{ $school->school_name }}</strong> • Total Parsed Students: <strong>{{ count($validRows) }}</strong>
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('ingestion.index') }}" class="btn btn-outline-secondary px-3 fw-semibold">
                ← Cancel & Re-upload
            </a>
            <form action="{{ route('ingestion.commit', $class->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm" {{ count($anomalies) > 0 ? '' : '' }}>
                    ✓ Commit & Write to Database
                </button>
            </form>
        </div>
    </div>

    <!-- Anomaly Alert Box -->
    @if(count($anomalies) > 0)
        <div class="alert alert-warning border-warning-subtle py-3 px-4 rounded-3 mb-4">
            <h6 class="fw-bold text-dark mb-2">⚠️ Pre-Flight Anomalies Detected ({{ count($anomalies) }})</h6>
            <p class="small text-muted mb-2">The following inconsistencies were flagged during spreadsheet parsing. You may still proceed with commit if these are intentional or overridden by policy:</p>
            <ul class="small mb-0 ps-3">
                @foreach($anomalies as $err)
                    <li class="text-danger fw-semibold">{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @else
        <div class="alert alert-success border-success-subtle py-3 px-4 rounded-3 mb-4 d-flex align-items-center gap-3">
            <div class="fs-4">🛡️</div>
            <div>
                <strong class="text-success d-block">Pre-Flight Audit Passed Cleanly!</strong>
                <span class="small text-muted">All student admission numbers, attendance sums (Present + Absent == Enrolled), and subject mark boundaries match your school's configuration.</span>
            </div>
        </div>
    @endif

    <!-- Side-by-Side Preview Grid -->
    <div class="table-responsive border rounded-3">
        <table class="table table-hover table-bordered align-middle mb-0 text-center small">
            <thead class="table-light">
                <!-- Group Headers -->
                <tr>
                    <th colspan="2" class="text-start bg-secondary-subtle">STUDENT INFORMATION</th>
                    <th colspan="4" class="bg-primary-subtle text-primary">PILLAR 1: ATTENDANCE PROFILE</th>
                    @if(count($validRows) > 0 && isset($validRows[0]['scores']))
                        @foreach($validRows[0]['scores'] as $score)
                            <th colspan="3" class="bg-warning-subtle text-dark">
                                {{ $score['subject_name'] }} [{{ $score['subject_code'] }}] (Max: {{ $score['max'] }})
                            </th>
                        @endforeach
                    @endif
                </tr>
                <!-- Field Headers -->
                <tr>
                    <th class="text-start">Admission No</th>
                    <th class="text-start">Student Name</th>
                    <th>Enrolled</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Rate %</th>
                    @if(count($validRows) > 0 && isset($validRows[0]['scores']))
                        @foreach($validRows[0]['scores'] as $score)
                            <th>CW</th>
                            <th>Exam</th>
                            <th>Total</th>
                        @endforeach
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($validRows as $row)
                    <tr>
                        <td class="text-start fw-bold text-dark font-monospace">{{ $row['admission_no'] }}</td>
                        <td class="text-start fw-semibold">{{ $row['name'] }}</td>
                        <td>{{ $row['attendance']['enrolled'] }}</td>
                        <td class="text-success fw-bold">{{ $row['attendance']['present'] }}</td>
                        <td class="text-danger">{{ $row['attendance']['absent'] }}</td>
                        <td>
                            <span class="badge {{ $row['attendance']['rate'] >= 85 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }}">
                                {{ $row['attendance']['rate'] }}%
                            </span>
                        </td>

                        @foreach($row['scores'] as $sc)
                            <td class="text-muted">{{ $sc['cw'] }}</td>
                            <td class="text-muted">{{ $sc['exam'] }}</td>
                            <td class="fw-bold text-dark bg-light">{{ $sc['total'] }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="15" class="py-4 text-center text-muted">No student rows found in spreadsheet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
        <span class="small text-muted">
            Ready to integrate? Clicking "Commit & Write to Database" will update Student Attendance and Grade records in real-time.
        </span>
        <form action="{{ route('ingestion.commit', $class->id) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-success fw-bold px-4">
                ✓ Confirm & Commit to Master Records
            </button>
        </form>
    </div>
</div>
@endsection
