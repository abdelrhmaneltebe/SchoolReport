@extends('layouts.app')

@section('title', 'Class Performance & Analytics - ' . $class->class_name)

@section('content')
<div class="container-fluid px-0">
    <!-- Breadcrumb & Navigation -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('reports.generator') }}" class="text-decoration-none">Report Studio</a></li>
            <li class="breadcrumb-item active" aria-current="page">Class Analytics</li>
        </ol>
    </nav>

    <!-- Header & Batch Selector -->
    <div class="card border rounded-3 p-4 mb-4 bg-white shadow-xs">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <img src="{{ $activeSchool->getLogoUrl() }}" alt="{{ $activeSchool->school_name }}" class="rounded-3 border bg-white p-1 shadow-2xs" style="height: 56px; width: auto; max-width: 110px; object-fit: contain;">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-primary text-white px-2 py-1 small">Performance Analytics</span>
                        <span class="badge bg-light text-secondary border small">{{ $class->gradeLevel->grade_name ?? 'Level' }}</span>
                        <span class="badge bg-secondary-subtle text-secondary small">{{ $activeSchool->school_code }}</span>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark">📈 Performance &amp; Grade Analytics: {{ $class->class_name }}</h4>
                    <p class="text-muted small mb-0">
                        {{ $activeSchool->school_name }} • Multi-dimensional analysis of student scores, passing rates, and grade distributions.
                    </p>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <!-- Jump to other class -->
                <select class="form-select form-select-sm fw-semibold" style="width: auto;" onchange="window.location.href='/reports/analytics/' + this.value">
                    @foreach($allClasses as $c)
                        <option value="{{ $c->id }}" {{ $class->id == $c->id ? 'selected' : '' }}>
                            {{ $c->class_name }} ({{ $c->gradeLevel->grade_code ?? 'CLS' }})
                        </option>
                    @endforeach
                </select>

                <a href="{{ route('reports.card.batch', ['class' => $class->id]) }}" class="btn btn-outline-primary btn-sm fw-bold">
                    🖨️ Batch Report Cards
                </a>
                <a href="{{ route('reports.broadsheet', ['class_id' => $class->id]) }}" class="btn btn-outline-dark btn-sm fw-semibold">
                    📋 Broadsheet
                </a>
                <a href="{{ route('reports.export.csv', $class->id) }}" class="btn btn-outline-success btn-sm fw-semibold">
                    📊 Export CSV
                </a>
            </div>
        </div>
    </div>

    <!-- 1. KPI SUMMARY CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border rounded-3 p-3 bg-white h-100 shadow-2xs">
                <span class="text-muted small fw-semibold text-uppercase">Total Enrolled</span>
                <h3 class="fw-bold text-dark mb-0 mt-1">{{ $analytics['totalStudents'] }}</h3>
                <small class="text-muted">Students registered in batch</small>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border rounded-3 p-3 bg-white h-100 shadow-2xs">
                <span class="text-muted small fw-semibold text-uppercase">Class Average</span>
                <h3 class="fw-bold text-primary mb-0 mt-1">{{ number_format($analytics['averagePercentage'], 1) }}%</h3>
                <small class="text-muted">High: <strong>{{ number_format($analytics['highestScore'], 1) }}%</strong> • Low: <strong>{{ number_format($analytics['lowestScore'], 1) }}%</strong></small>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border rounded-3 p-3 bg-white h-100 shadow-2xs">
                <span class="text-muted small fw-semibold text-uppercase">Overall Pass Rate</span>
                <h3 class="fw-bold {{ $analytics['passRate'] >= 75 ? 'text-success' : 'text-danger' }} mb-0 mt-1">
                    {{ number_format($analytics['passRate'], 1) }}%
                </h3>
                <small class="text-muted">Meeting 60.0% benchmark</small>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border rounded-3 p-3 bg-white h-100 shadow-2xs">
                <span class="text-muted small fw-semibold text-uppercase">At-Risk Students</span>
                <h3 class="fw-bold {{ count($analytics['atRiskStudents']) > 0 ? 'text-danger' : 'text-success' }} mb-0 mt-1">
                    {{ count($analytics['atRiskStudents']) }}
                </h3>
                <small class="text-muted">Require remedial attention</small>
            </div>
        </div>
    </div>

    <!-- 2. GRADE DISTRIBUTION HISTOGRAM -->
    <div class="card border rounded-3 p-4 mb-4 bg-white shadow-xs">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="fw-bold text-dark mb-0">📊 Grade Distribution Curve</h6>
                <small class="text-muted">Aggregated performance across all subjects in {{ $class->class_name }}</small>
            </div>
        </div>

        <div class="row g-2 text-center">
            @php
                $distColors = [
                    'A+' => ['bg' => 'bg-success', 'border' => 'border-success', 'text' => 'text-success'],
                    'A'  => ['bg' => 'bg-success', 'border' => 'border-success', 'text' => 'text-success'],
                    'B'  => ['bg' => 'bg-primary', 'border' => 'border-primary', 'text' => 'text-primary'],
                    'C'  => ['bg' => 'bg-info',    'border' => 'border-info',    'text' => 'text-info'],
                    'D'  => ['bg' => 'bg-warning', 'border' => 'border-warning', 'text' => 'text-warning'],
                    'F'  => ['bg' => 'bg-danger',  'border' => 'border-danger',  'text' => 'text-danger'],
                ];
                $maxDist = max(array_values($analytics['gradeDistribution'])) ?: 1;
            @endphp

            @foreach($analytics['gradeDistribution'] as $grade => $count)
                @php
                    $pctOfTotal = $analytics['totalStudents'] > 0 ? round(($count / $analytics['totalStudents']) * 100, 1) : 0;
                    $barHeight = round(($count / $maxDist) * 100);
                @endphp
                <div class="col">
                    <div class="border rounded-3 p-2 bg-light h-100 d-flex flex-column justify-content-between">
                        <div>
                            <span class="badge {{ $grade === 'F' ? 'bg-danger' : ($grade === 'D' ? 'bg-warning text-dark' : 'bg-primary') }} text-white px-2 py-1 fw-bold fs-7">
                                {{ $grade }}
                            </span>
                            <div class="fs-4 fw-bold text-dark mt-2 mb-0">{{ $count }}</div>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">{{ $pctOfTotal }}%</small>
                        </div>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar {{ $distColors[$grade]['bg'] }}" style="width: {{ $barHeight }}%;"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- 3. TABS: SUBJECT COMPARISON vs AT-RISK ROSTER vs TOP PERFORMERS -->
    <div class="card border rounded-3 p-0 mb-4 bg-white shadow-xs overflow-hidden">
        <div class="border-bottom px-4 pt-3 bg-light">
            <ul class="nav nav-tabs card-header-tabs" id="analyticsTabs">
                <li class="nav-item">
                    <button class="nav-link fw-semibold active text-dark" data-bs-toggle="tab" data-bs-target="#tabSubjectComparison">
                        📚 Subject Performance Matrix ({{ count($analytics['subjectAnalytics']) }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold text-danger" data-bs-toggle="tab" data-bs-target="#tabAtRisk">
                        ⚠️ At-Risk Intervention List ({{ count($analytics['atRiskStudents']) }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold text-success" data-bs-toggle="tab" data-bs-target="#tabTopPerformers">
                        🏆 Honor Roll Leaders ({{ count($analytics['topPerformers']) }})
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content p-4">
            <!-- TAB 1: SUBJECT COMPARISON TABLE -->
            <div class="tab-pane fade show active" id="tabSubjectComparison">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start ps-3">Subject</th>
                                <th class="text-start">Teacher</th>
                                <th>Pass Mark</th>
                                <th>Enrolled</th>
                                <th>Average Score</th>
                                <th>Highest</th>
                                <th>Lowest</th>
                                <th>Pass Rate</th>
                                <th class="text-end pe-3">Grade Distribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($analytics['subjectAnalytics'] as $sub)
                                <tr>
                                    <td class="text-start ps-3 fw-bold text-dark">
                                        {{ $sub['name'] }}
                                        <small class="d-block text-muted font-monospace">{{ $sub['code'] }}</small>
                                    </td>
                                    <td class="text-start text-dark fw-semibold">
                                        👨‍🏫 {{ $sub['teacher'] }}
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border">{{ $sub['passMark'] }}%</span>
                                    </td>
                                    <td class="fw-semibold">{{ $sub['enrolled'] }}</td>
                                    <td class="fw-bold fs-6 text-primary">
                                        {{ number_format($sub['average'], 1) }}%
                                    </td>
                                    <td class="text-success fw-bold">{{ number_format($sub['highest'], 1) }}</td>
                                    <td class="text-danger fw-bold">{{ number_format($sub['lowest'], 1) }}</td>
                                    <td>
                                        <span class="badge {{ $sub['passRate'] >= 75 ? 'bg-success text-white' : 'bg-danger text-white' }} px-2 py-1">
                                            {{ number_format($sub['passRate'], 1) }}%
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-inline-flex gap-1">
                                            @foreach($sub['grades'] as $g => $c)
                                                @if($c > 0)
                                                    <span class="badge bg-light text-dark border px-1" title="{{ $c }} student(s) got {{ $g }}">
                                                        {{ $g }}:<strong>{{ $c }}</strong>
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        No subjects assigned to this class.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: AT-RISK STUDENTS LIST -->
            <div class="tab-pane fade" id="tabAtRisk">
                @if(empty($analytics['atRiskStudents']))
                    <div class="text-center py-5">
                        <div class="display-6 mb-2">🎉</div>
                        <h6 class="fw-bold text-success mb-1">Excellent Performance!</h6>
                        <p class="text-muted small">No students in {{ $class->class_name }} are currently below the academic pass limit.</p>
                    </div>
                @else
                    <div class="alert alert-warning small py-2 px-3 mb-3 border-warning rounded-3">
                        <strong>⚠️ Academic Intervention Notice:</strong> The following students have overall averages below 60.0% or have failed multiple courses. Target for remedial support.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small text-center">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-start ps-3">Admission #</th>
                                    <th class="text-start">Student Name</th>
                                    <th>Overall Score</th>
                                    <th>Failed Courses</th>
                                    <th class="text-start">Intervention Reason</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($analytics['atRiskStudents'] as $risk)
                                    <tr>
                                        <td class="text-start ps-3 font-monospace fw-bold">
                                            {{ $risk['student']['admissionNumber'] }}
                                        </td>
                                        <td class="text-start fw-bold text-dark">
                                            {{ $risk['student']['name'] }}
                                        </td>
                                        <td class="fw-bold text-danger fs-6">
                                            {{ number_format($risk['percentage'], 1) }}%
                                        </td>
                                        <td>
                                            <span class="badge bg-danger text-white px-2 py-1">
                                                {{ $risk['failedSubjectsCount'] ?? $risk['failedCount'] ?? 0 }} Course(s)
                                            </span>
                                        </td>
                                        <td class="text-start text-muted">
                                            {{ $risk['reason'] ?? 'Targeted Remediation Required' }}
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="{{ route('reports.card.single', $risk['student']['id']) }}" class="btn btn-outline-primary btn-sm py-0 px-2 fw-semibold">
                                                View Card →
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- TAB 3: TOP PERFORMERS / HONOR ROLL -->
            <div class="tab-pane fade" id="tabTopPerformers">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small text-center">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 10%;">Rank</th>
                                <th class="text-start ps-3">Student Name</th>
                                <th>Admission #</th>
                                <th>Average Score</th>
                                <th>Cumulative GPA</th>
                                <th>Overall Grade</th>
                                <th>Honors Standing</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($analytics['topPerformers'] as $idx => $performer)
                                <tr>
                                    <td>
                                        <span class="badge {{ $idx === 0 ? 'bg-warning text-dark' : ($idx === 1 ? 'bg-secondary text-white' : 'bg-light text-dark border') }} fw-bold px-2 py-1">
                                            {{ $performer['metrics']['rankInClass'] }}
                                        </span>
                                    </td>
                                    <td class="text-start ps-3 fw-bold text-dark">
                                        {{ $performer['student']['name'] }}
                                    </td>
                                    <td class="font-monospace text-muted">
                                        {{ $performer['student']['admissionNumber'] }}
                                    </td>
                                    <td class="fw-bold fs-6 text-primary">
                                        {{ number_format($performer['metrics']['overallPercentage'], 1) }}%
                                    </td>
                                    <td class="fw-bold text-dark">
                                        {{ $performer['metrics']['cumulativeGpa'] }} / 4.0
                                    </td>
                                    <td>
                                        <span class="badge bg-success text-white fw-bold">
                                            {{ $performer['metrics']['letterGrade'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark text-white fw-bold px-2 py-1">
                                            {{ $performer['metrics']['honorStatus'] }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('reports.card.single', $performer['student']['id']) }}" class="btn btn-outline-primary btn-sm py-0 px-2 fw-semibold">
                                            View Report Card →
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        No top performers data available.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
