@extends('layouts.app')

@section('title', 'Excel Marksheet Ingestion Hub')

@section('content')
<div class="row g-4">
    <!-- Left Column: Upload Marksheet -->
    <div class="col-lg-7">
        <div class="card card-custom p-4">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                <div>
                    <h5 class="fw-bold mb-1">📥 Ingest Class Marksheet Workbook</h5>
                    <p class="text-muted small mb-0">Upload multi-subject spreadsheet with side-by-side attendance records</p>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fw-semibold">
                    PhpSpreadsheet Engine
                </span>
            </div>

            <form action="{{ route('ingestion.preview') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small">1. Select Target Class</label>
                    <select name="class_id" class="form-select border-secondary-subtle" required>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->class_name }} ({{ $c->class_code }}) — {{ $c->students_count }} Students, {{ $c->subject_assignments_count }} Assigned Subjects
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text small">The system will validate columns against this class's assigned subjects and max score limits.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-dark small">2. Choose Excel Marksheet File (.xlsx, .xls)</label>
                    <div class="p-4 border-2 border-dashed rounded-3 bg-light text-center border-secondary-subtle">
                        <input type="file" name="spreadsheet" id="spreadsheet" class="form-control mb-2" accept=".xlsx,.xls" required>
                        <small class="text-muted d-block">Supported formats: Microsoft Excel OpenXML (.xlsx), Excel 97-2003 (.xls)</small>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">
                        🛡️ <strong>Safety Guarantee:</strong> Upload runs in read-only pre-flight audit mode first.
                    </span>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        🔍 Inspect & Pre-flight Audit
                    </button>
                </div>
            </form>
        </div>

        <!-- Pre-Flight Rules Info Box -->
        <div class="card card-custom p-4 mt-4 bg-light border-0">
            <h6 class="fw-bold text-dark mb-2">📋 Automatic Reconciliation Engine Checks</h6>
            <div class="row g-3 small text-muted">
                <div class="col-md-6">
                    <div class="p-3 bg-white rounded-3 border">
                        <strong class="text-primary d-block mb-1">Pillar 1: Attendance Verification</strong>
                        <span>Verifies that <code>Present + Absent == Enrolled Days</code>. Flags statutory attendance rate drops below 85%.</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-white rounded-3 border">
                        <strong class="text-warning text-dark d-block mb-1">Pillars 2 & 3: Score Cap Limits</strong>
                        <span>Reconciles CW and Exam marks against custom class subject weights (e.g. Religion max 50, French max 20).</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Download Dynamic Templates -->
    <div class="col-lg-5">
        <div class="card card-custom p-4">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                <div>
                    <h5 class="fw-bold mb-1">📑 Dynamic Class Templates</h5>
                    <p class="text-muted small mb-0">Pre-generated sheets with class roster & subject headers</p>
                </div>
                <span class="badge bg-secondary-subtle text-secondary px-2 py-1 small">
                    Auto-Formatted
                </span>
            </div>

            <p class="small text-muted mb-3">
                Download the standardized mark entry template pre-loaded with your school's active student admission numbers, student names, and assigned subject formulas:
            </p>

            <div class="list-group list-group-flush border rounded-3 mb-3">
                @foreach($classes as $c)
                    <div class="list-group-item d-flex align-items-center justify-content-between p-3">
                        <div>
                            <div class="fw-bold text-dark">{{ $c->class_name }}</div>
                            <small class="text-muted">{{ $c->students_count }} enrolled students • {{ $c->subject_assignments_count }} subjects</small>
                        </div>
                        <a href="{{ route('ingestion.template', $c->id) }}" class="btn btn-sm btn-outline-primary fw-semibold px-3">
                            ⬇️ Download .XLSX
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="alert alert-info py-2 px-3 small rounded-3 mb-0">
                <strong>💡 Tip:</strong> Teachers can enter marks directly into these downloaded sheets. Total formulas are already embedded in the cells!
            </div>
        </div>
    </div>
</div>
@endsection
