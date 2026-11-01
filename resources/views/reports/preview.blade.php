@extends('layouts.app')

@section('title', 'Student Report Card Preview — School Reporting Hub')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="{{ route('reports.broadsheet') }}" class="btn btn-outline-secondary btn-sm fw-bold">
                ← Back to Broadsheet
            </a>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-dark btn-sm fw-bold">
                    🖨️ Browser Print
                </button>
                <a href="{{ route('reports.export.jasper', $student->id) }}" class="btn btn-primary btn-sm fw-bold">
                    ⚡ Export JasperReports PDF
                </a>
            </div>
        </div>

        <!-- A4 Printable Card Mockup -->
        <div class="card-custom p-5 bg-white border border-2 shadow-sm rounded-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ $school->getLogoBase64() ?? $school->getLogoUrl() }}" alt="{{ $school->school_name }}" class="rounded-3 border bg-white p-1 shadow-2xs" style="height: 60px; width: auto; max-width: 120px; object-fit: contain;">
                    <div class="text-start">
                        <div class="text-uppercase tracking-widest text-primary fw-bold small">Official Student Progress Report</div>
                        <h3 class="fw-bold my-1 text-dark">{{ $school->school_name }}</h3>
                        <p class="text-muted small mb-0">Academic Year 2026-2027 • Term 1 Evaluation • School Code: <strong class="font-monospace">{{ $school->school_code }}</strong></p>
                    </div>
                </div>
                <div class="badge bg-dark text-white px-3 py-2 fw-bold text-uppercase" style="font-size: 0.8rem;">
                    Official Report
                </div>
            </div>

            <!-- Student Metadata -->
            <div class="row g-2 p-3 bg-light rounded-3 border mb-4 small">
                <div class="col-6"><strong>Student Name:</strong> {{ $student->full_name }}</div>
                <div class="col-6"><strong>Admission Number:</strong> {{ $student->admission_number }}</div>
                <div class="col-6"><strong>Class:</strong> {{ $student->schoolClass->class_name }}</div>
                <div class="col-6"><strong>Homeroom Teacher:</strong> {{ $student->schoolClass->homeroom_teacher }}</div>
            </div>

            <!-- Dual-Pillar Section: Subjects + Attendance Side-by-Side -->
            <div class="row g-3 mb-4">
                <!-- Left: Subject Marks Table (8 Cols) -->
                <div class="col-md-8">
                    <div class="border rounded-3 overflow-hidden">
                        <div class="bg-light p-2 fw-bold text-dark border-bottom small d-flex justify-content-between">
                            <span>Academic Performance</span>
                            <span>{{ count($subjectRows) }} Subjects</span>
                        </div>
                        <table class="table table-sm table-striped mb-0 small text-center align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-start">Subject</th>
                                    <th>Score / Max</th>
                                    <th>Grade</th>
                                    <th class="text-start">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subjectRows as $row)
                                <tr>
                                    <td class="text-start fw-semibold">{{ $row['subjectName'] }}</td>
                                    <td class="fw-bold">{{ $row['rawScore'] }} / {{ $row['maxScore'] }}</td>
                                    <td><span class="badge bg-success-subtle text-success fw-bold">{{ $row['letterGrade'] }}</span></td>
                                    <td class="text-start text-muted fst-italic">{{ $row['teacherRemarks'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right: Attendance Profile Card (4 Cols) -->
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 bg-light h-100 d-flex flex-column justify-content-between small">
                        <div>
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Attendance Profile</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Total Term Days:</span>
                                <strong>90 Days</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Days Present:</span>
                                <strong class="text-success">88 Days</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Absences:</span>
                                <strong>2 Days</strong>
                            </div>
                            <div class="border-top pt-2 d-flex justify-content-between fw-bold">
                                <span>Attendance Rate:</span>
                                <span class="text-success">97.8%</span>
                            </div>
                        </div>
                        <div class="mt-3 p-2 bg-success-subtle text-success text-center rounded fw-bold">
                            ✓ Policy Met (&gt;{{ $school->attendancePolicy->min_attendance_rate ?? 85 }}%)
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overall Standing -->
            <div class="p-3 bg-light rounded-3 border mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0 fw-bold">Overall Average: {{ $overallPct }}%</h6>
                    <small class="text-muted">High Honors eligibility fulfilled (Attendance ≥ 95% and Average ≥ 90%)</small>
                </div>
                <span class="badge bg-success fs-6 py-2 px-3 fw-bold">🏆 HIGH HONORS</span>
            </div>

            <!-- Signatures -->
            <div class="row pt-4 border-top mt-4 text-center small text-muted">
                <div class="col-6">
                    <div class="fw-bold text-dark">{{ $student->schoolClass->homeroom_teacher }}</div>
                    <div>Class Teacher Signature</div>
                </div>
                <div class="col-6">
                    <div class="fw-bold text-dark">Dr. R. Sterling</div>
                    <div>Principal Signature & Official Seal</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
