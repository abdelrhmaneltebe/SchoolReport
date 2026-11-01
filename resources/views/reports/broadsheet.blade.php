@extends('layouts.app')

@section('title', 'Master Broadsheet — School Reporting Hub')

@section('content')
<div class="card-custom p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom pb-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            @php
                $activeSch = $selectedClass ? \App\Models\School::where('school_id', $selectedClass->school_id)->first() : null;
            @endphp
            @if($activeSch)
                <img src="{{ $activeSch->getLogoBase64() ?? $activeSch->getLogoUrl() }}" alt="{{ $activeSch->school_name }}" class="rounded-3 border bg-white p-1 shadow-2xs" style="height: 52px; width: auto; max-width: 100px; object-fit: contain;">
            @endif
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-dark text-white px-2 py-1 small">Unified Broadsheet</span>
                    <a href="{{ route('reports.generator') }}" class="small fw-semibold text-decoration-none">← Back to Reports Studio</a>
                </div>
                <h5 class="fw-bold mb-1">Unified Master Broadsheet: Side-by-Side Review</h5>
                <p class="text-muted small mb-0">{{ $activeSch->school_name ?? 'School' }} • Student Attendance and Class-Specific Subject Grades side-by-side.</p>
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <label class="form-label mb-0 fw-semibold small text-muted">Select Class:</label>
            <select class="form-select form-select-sm fw-bold" style="width: 200px;" onchange="window.location.search = '?class_id=' + this.value">
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ $selectedClass && $selectedClass->id === $c->id ? 'selected' : '' }}>
                        {{ $c->class_name }} ({{ $c->students->count() }} Students)
                    </option>
                @endforeach
            </select>
            @if($selectedClass)
                <a href="{{ route('reports.card.batch', ['class' => $selectedClass->id]) }}" class="btn btn-outline-primary btn-sm fw-semibold">
                    🖨️ Batch Cards
                </a>
                <a href="{{ route('reports.analytics.class', ['class' => $selectedClass->id]) }}" class="btn btn-outline-success btn-sm fw-semibold">
                    📈 Analytics
                </a>
                <a href="{{ route('reports.export.csv', $selectedClass->id) }}" class="btn btn-outline-dark btn-sm fw-semibold">
                    📊 Export CSV
                </a>
            @endif
        </div>
    </div>

    @if($selectedClass)
    <div class="table-responsive border rounded-3 mb-4">
        <table class="table table-sm table-hover align-middle small mb-0 text-center">
            <thead class="table-light">
                <tr class="border-bottom">
                    <th colspan="2" class="p-2 text-start text-uppercase fw-bold">Student Profile</th>
                    <th colspan="3" class="p-2 text-uppercase fw-bold bg-primary-subtle text-primary border-start border-end">
                        📅 School Attendance (90 Days)
                    </th>
                    <th colspan="{{ $selectedClass->subjectAssignments->count() }}" class="p-2 text-uppercase fw-bold bg-info-subtle text-dark border-end">
                        🎓 {{ $selectedClass->class_name }} Assigned Subjects
                    </th>
                    <th class="p-2 text-uppercase fw-bold">Action</th>
                </tr>
                <tr class="text-muted small">
                    <th class="text-start">ID / Name</th>
                    <th>Class</th>
                    <th class="bg-primary-subtle border-start">Pres</th>
                    <th class="bg-primary-subtle">Abs</th>
                    <th class="bg-primary-subtle border-end">Att. %</th>
                    @foreach($selectedClass->subjectAssignments as $assign)
                        <th class="bg-info-subtle border-end">
                            <div>{{ $assign->subject->subject_name }}</div>
                            <small class="text-muted">Max {{ $assign->custom_max_score ?? $assign->subject->max_score }}</small>
                        </th>
                    @endforeach
                    <th>Report Card</th>
                </tr>
            </thead>
            <tbody>
                @forelse($selectedClass->students as $student)
                <tr>
                    <td class="text-start">
                        <div class="fw-bold">{{ $student->full_name }}</div>
                        <small class="text-muted font-monospace">{{ $student->admission_number }}</small>
                    </td>
                    <td>{{ $selectedClass->class_name }}</td>
                    <td class="bg-primary-subtle border-start fw-bold text-success">88</td>
                    <td class="bg-primary-subtle">2</td>
                    <td class="bg-primary-subtle border-end fw-bold text-success">97.8%</td>
                    @foreach($selectedClass->subjectAssignments as $assign)
                        @php
                            $rec = $student->gradeRecords->firstWhere('class_subject_assignment_id', $assign->id);
                        @endphp
                        <td class="bg-info-subtle border-end">
                            @if($rec)
                                <div class="fw-bold">{{ $rec->total_score }} / {{ $assign->custom_max_score ?? $assign->subject->max_score }}</div>
                                <span class="badge bg-success-subtle text-success">{{ $rec->letter_grade ?? 'A' }}</span>
                            @else
                                <span class="text-muted">--</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="text-nowrap">
                        <a href="{{ route('reports.card.single', $student->id) }}" class="btn btn-primary btn-sm py-0 px-2 fw-semibold">
                            🎓 Card
                        </a>
                        <a href="{{ route('reports.export.jasper', $student->id) }}" class="btn btn-outline-indigo btn-sm py-0 px-2 fw-semibold ms-1" title="Download Official JasperReports PDF">
                            ⚡ Jasper
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ 6 + $selectedClass->subjectAssignments->count() }}" class="py-4 text-muted">No students in this class.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
