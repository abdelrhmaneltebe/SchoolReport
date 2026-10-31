@extends('layouts.app')

@section('title', 'Report Card - ' . $card['student']['name'])

@section('styles')
<style>
    @media print {
        body {
            background-color: #ffffff !important;
            color: #000000 !important;
            padding: 0 !important;
        }
        .no-print, header, nav, .btn, .breadcrumb {
            display: none !important;
        }
        .report-card-container {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
            max-width: 100% !important;
        }
        .table {
            border-color: #000000 !important;
        }
        .page-break {
            page-break-after: always;
        }
    }
    .report-card-container {
        max-width: 950px;
        margin: 0 auto;
        background-color: #ffffff;
    }
    .watermark-logo {
        width: 65px;
        height: 65px;
        border-radius: 12px;
        background: linear-gradient(135deg, #1e3a8a, #3b82f6);
        color: #ffffff;
        font-weight: 800;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .sig-line {
        border-top: 1.5px solid #334155;
        width: 200px;
        margin: 40px auto 5px auto;
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-0">
    <!-- Action Controls (Hidden in Print) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 no-print">
        <a href="{{ route('reports.generator') }}" class="btn btn-outline-secondary btn-sm fw-semibold">
            ← Back to Report Studio
        </a>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm fw-bold px-3 shadow-xs" onclick="window.print()">
                🖨️ Print / Save as PDF
            </button>
            <a href="{{ route('reports.export.jasper', $card['student']['id']) }}" class="btn btn-outline-indigo btn-sm fw-semibold">
                ⚡ Export via JasperReports
            </a>
        </div>
    </div>

    <!-- OFFICIAL REPORT CARD A4 SHEET -->
    <div class="card border rounded-3 p-4 p-md-5 report-card-container shadow-xs">
        <!-- 1. Institutional General Header -->
        <div class="d-flex justify-content-between align-items-center pb-3 border-bottom border-2 border-dark mb-4">
            <div class="d-flex align-items-center gap-3">
                @if(!empty($card['school']['logoBase64']))
                    <img src="{{ $card['school']['logoBase64'] }}" alt="{{ $card['school']['name'] }}" class="rounded-3 border bg-white p-1 shadow-2xs" style="height: 65px; width: auto; max-width: 130px; object-fit: contain;">
                @elseif(!empty($card['school']['logoUrl']))
                    <img src="{{ $card['school']['logoUrl'] }}" alt="{{ $card['school']['name'] }}" class="rounded-3 border bg-white p-1 shadow-2xs" style="height: 65px; width: auto; max-width: 130px; object-fit: contain;">
                @else
                    <div class="watermark-logo shadow-sm">
                        {{ substr($card['school']['name'], 0, 2) }}
                    </div>
                @endif
                <div>
                    <h3 class="fw-bold mb-0 text-dark font-serif" style="letter-spacing: -0.5px;">{{ $card['school']['name'] }}</h3>
                    <div class="text-muted small fw-semibold">
                        School Code: <span class="text-dark font-monospace">{{ $card['school']['code'] }}</span> • Accreditation & Reporting Division
                    </div>
                    <div class="text-muted small">
                        Official Academic Achievement & Progress Report • Academic Year: <strong>{{ $card['school']['academicYear'] }}</strong>
                    </div>
                </div>
            </div>
            <div class="text-end d-none d-sm-block">
                <div class="badge bg-dark text-white px-3 py-2 fw-bold text-uppercase" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                    {{ $card['school']['term'] }}
                </div>
                <div class="text-muted small mt-1">Issue Date: {{ date('M d, Y') }}</div>
            </div>
        </div>

        <!-- 2. Student Profile Grid -->
        <div class="bg-light rounded-3 p-3 mb-4 border border-slate-300">
            <div class="row g-2 small">
                <div class="col-sm-6 col-md-3">
                    <span class="text-muted d-block" style="font-size: 0.75rem;">STUDENT FULL NAME:</span>
                    <strong class="text-dark fs-6">{{ $card['student']['name'] }}</strong>
                </div>
                <div class="col-sm-6 col-md-3">
                    <span class="text-muted d-block" style="font-size: 0.75rem;">ADMISSION NUMBER:</span>
                    <strong class="text-dark font-monospace fs-6">{{ $card['student']['admissionNumber'] }}</strong>
                </div>
                <div class="col-sm-6 col-md-3">
                    <span class="text-muted d-block" style="font-size: 0.75rem;">GRADE LEVEL / STAGE:</span>
                    <strong class="text-dark">{{ $card['student']['levelName'] }} ({{ $card['student']['divisionName'] }})</strong>
                </div>
                <div class="col-sm-6 col-md-3">
                    <span class="text-muted d-block" style="font-size: 0.75rem;">CLASS / BATCH:</span>
                    <strong class="text-dark">{{ $card['student']['className'] }}</strong>
                </div>
            </div>
        </div>

        <!-- 3. Subject-Wise Marks & Grades Table -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-sm align-middle text-center mb-0" style="font-size: 0.85rem;">
                <thead class="table-dark text-white">
                    <tr>
                        <th class="text-start ps-3" style="width: 25%;">Subject Curriculum</th>
                        <th style="width: 15%;">Assigned Teacher</th>
                        <th style="width: 8%;">Credits</th>
                        <th style="width: 14%;">Assessment Breakdown</th>
                        <th style="width: 10%;">Score / 100</th>
                        <th style="width: 8%;">Grade</th>
                        <th style="width: 8%;">Status</th>
                        <th class="text-start ps-2" style="width: 12%;">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($card['subjectRows'] as $sub)
                        <tr>
                            <td class="text-start ps-3 py-2">
                                <div class="fw-bold text-dark">{{ $sub['subjectName'] }}</div>
                                <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.65rem;">
                                    {{ $sub['subjectCode'] }}
                                </span>
                            </td>
                            <td class="text-muted small">
                                {{ $sub['teacherName'] }}
                            </td>
                            <td class="fw-semibold">
                                {{ number_format($sub['credits'], 1) }}
                            </td>
                            <td class="small text-start px-2">
                                @if(!empty($sub['subScores']))
                                    @foreach($sub['subScores'] as $sc)
                                        <div class="d-flex justify-content-between" style="font-size: 0.72rem;">
                                            <span class="text-muted">{{ $sc['categoryName'] }}:</span>
                                            <strong class="text-dark">{{ $sc['rawScore'] }} / {{ $sc['maxScore'] }}</strong>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-muted fst-italic">Standard Mark</span>
                                @endif
                            </td>
                            <td class="fw-bold fs-6 {{ $sub['isPassed'] ? 'text-dark' : 'text-danger' }}">
                                {{ $sub['rawScore'] !== null ? number_format($sub['rawScore'], 1) : '-' }}
                            </td>
                            <td>
                                <span class="badge {{ in_array($sub['letterGrade'], ['A+', 'A', 'B']) ? 'bg-success text-white' : ($sub['isPassed'] ? 'bg-primary text-white' : 'bg-danger text-white') }} px-2 py-1 fw-bold">
                                    {{ $sub['letterGrade'] }}
                                </span>
                            </td>
                            <td>
                                @if($sub['rawScore'] === null)
                                    <span class="badge bg-secondary text-white">Pending</span>
                                @elseif($sub['isPassed'])
                                    <span class="badge bg-success text-white">Passed</span>
                                @else
                                    <span class="badge bg-danger text-white">Retake</span>
                                @endif
                            </td>
                            <td class="text-start ps-2 text-muted small" style="font-size: 0.75rem;">
                                {{ $sub['teacherRemarks'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                No course marks recorded for this student.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 4. Academic Summary & Attendance Profiles (Side-by-Side) -->
        <div class="row g-3 mb-4">
            <!-- Academic Summary Box -->
            <div class="col-md-7">
                <div class="border border-slate-300 rounded-3 p-3 bg-light h-100">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-2" style="font-size: 0.85rem;">
                        🏆 CUMULATIVE ACADEMIC PROFILE
                    </h6>
                    <div class="row g-2 text-center">
                        <div class="col-4 border-end">
                            <span class="text-muted d-block small" style="font-size: 0.7rem;">OVERALL AVERAGE</span>
                            <strong class="fs-4 text-primary">{{ number_format($card['metrics']['overallPercentage'], 1) }}%</strong>
                        </div>
                        <div class="col-4 border-end">
                            <span class="text-muted d-block small" style="font-size: 0.7rem;">CUMULATIVE GPA</span>
                            <strong class="fs-4 text-dark">{{ $card['metrics']['cumulativeGpa'] }} / 4.0</strong>
                        </div>
                        <div class="col-4">
                            <span class="text-muted d-block small" style="font-size: 0.7rem;">CLASS RANK</span>
                            <strong class="fs-4 text-dark">{{ $card['metrics']['rankInClass'] }}</strong>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center small">
                        <span class="text-muted">Honors & Academic Standing:</span>
                        <span class="badge bg-dark text-white fw-bold px-2 py-1">
                            {{ $card['metrics']['honorStatus'] }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Attendance Profile Box -->
            <div class="col-md-5">
                <div class="border border-slate-300 rounded-3 p-3 bg-light h-100">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-2" style="font-size: 0.85rem;">
                        📅 ATTENDANCE SUMMARY
                    </h6>
                    <div class="d-flex justify-content-between align-items-center mb-1 small">
                        <span class="text-muted">Enrolled Days:</span>
                        <strong class="text-dark">{{ $card['attendance']['enrolledDays'] }} Days</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1 small">
                        <span class="text-muted">Days Present:</span>
                        <strong class="text-success">{{ $card['attendance']['presentDays'] }} Days</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2 small">
                        <span class="text-muted">Days Absent / Excused:</span>
                        <strong class="text-danger">{{ $card['attendance']['absentDays'] }} Days</strong>
                    </div>
                    <div class="pt-2 border-top d-flex justify-content-between align-items-center">
                        <span class="small text-muted">Attendance Rate:</span>
                        <strong class="fs-5 text-success">{{ $card['attendance']['attendanceRate'] }}%</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Grading Legend & Signature Blocks -->
        <div class="row g-3 align-items-end mt-2 pt-2 border-top">
            <!-- Compact Grading Legend -->
            <div class="col-md-6">
                <span class="fw-bold text-dark d-block mb-1" style="font-size: 0.75rem;">GRADING SCALE REFERENCE LEGEND:</span>
                <div class="d-flex flex-wrap gap-1" style="font-size: 0.7rem;">
                    @foreach($card['school']['cutoffs'] as $c)
                        <span class="badge bg-light text-secondary border px-1 py-1">
                            <strong>{{ $c['grade'] }}</strong>: {{ $c['min'] }}-{{ $c['max'] }}% ({{ $c['gpa'] ?? '0' }})
                        </span>
                    @endforeach
                </div>
            </div>

            <!-- Official Signatures -->
            <div class="col-md-6">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="sig-line"></div>
                        <span class="small fw-semibold text-dark">Class Teacher</span>
                    </div>
                    <div class="col-6">
                        <div class="sig-line"></div>
                        <span class="small fw-semibold text-dark">Principal / Head of School</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($isPrintMode)
<script>
window.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => { window.print(); }, 400);
});
</script>
@endif
@endsection
