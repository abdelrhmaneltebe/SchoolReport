@extends('layouts.app')

@section('title', 'Dashboard — School Reporting Hub')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card-custom p-3">
            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Total Enrolled Students</span>
            <h3 class="fw-bold my-1 text-primary">{{ $studentsCount }}</h3>
            <small class="text-success fw-semibold">✓ Active across {{ $classesCount }} Classes</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom p-3">
            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">School Levels & Stages</span>
            <h3 class="fw-bold my-1 text-primary">{{ $levelsCount }} Levels</h3>
            <small class="text-muted">Academic Grade Structure</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom p-3">
            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Master Subject Catalog</span>
            <h3 class="fw-bold my-1 text-primary">{{ $subjectsCount }} Subjects</h3>
            <small class="text-muted">School-defined names & codes</small>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Structure & Levels Card -->
    <div class="col-md-6 col-lg-3">
        <div class="card-custom p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fs-4">🏛️</span>
                    <h5 class="fw-bold mb-0">School Levels & Structure</h5>
                </div>
                <p class="text-muted small">Configure educational stages, grade levels (e.g. Levels 1 to 12), and active classes/sections for this school tenant.</p>
            </div>
            <a href="{{ route('config.levels') }}" class="btn btn-outline-primary btn-sm fw-bold mt-2">Manage Levels & Classes →</a>
        </div>
    </div>

    <!-- Pillar 1 Card -->
    <div class="col-md-6 col-lg-3">
        <div class="card-custom p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fs-4">📅</span>
                    <h5 class="fw-bold mb-0">Pillar 1: Attendance</h5>
                </div>
                <p class="text-muted small">Daily homeroom roll call, statutory minimum policy (85%), and 3-tardies-to-1-absence conversion rules.</p>
            </div>
            <a href="{{ route('config.attendance') }}" class="btn btn-primary btn-sm fw-bold mt-2">Configure Attendance</a>
        </div>
    </div>

    <!-- Pillar 2 Card -->
    <div class="col-md-6 col-lg-3">
        <div class="card-custom p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fs-4">🎓</span>
                    <h5 class="fw-bold mb-0">Pillar 2: Grading System</h5>
                </div>
                <p class="text-muted small">Multi-tier hierarchical grading rules (Semesters, Quarters, Categories) with dynamic add/remove and real-time balancing.</p>
            </div>
            <a href="{{ route('config.grading') }}" class="btn btn-primary btn-sm fw-bold mt-2">Configure Grading</a>
        </div>
    </div>

    <!-- Pillar 3 Card -->
    <div class="col-md-6 col-lg-3">
        <div class="card-custom p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fs-4">🏫</span>
                    <h5 class="fw-bold mb-0">Pillar 3: Class Subjects</h5>
                </div>
                <p class="text-muted small">Assign subjects from the catalog separately to each class with credit hours, CW/Exam weights, and teachers.</p>
            </div>
            <a href="{{ route('config.class_subjects') }}" class="btn btn-primary btn-sm fw-bold mt-2">Assign Class Subjects</a>
        </div>
    </div>
</div>

<!-- Operational Workflow Quick Access -->
<div class="card card-custom p-4 mt-4">
    <h5 class="fw-bold mb-1">⚡ Reporting Operations & Delivery</h5>
    <p class="text-muted small mb-3">Core registrar workflow: Ingest marksheet workbooks, reconcile attendance & grades, and export official JasperReports PDFs</p>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="p-3 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="fw-bold mb-1">📥 Excel Marksheet Ingestion</h6>
                    <small class="text-muted">Download dynamic class templates, upload teacher workbooks, and run pre-flight reconciliation audits.</small>
                </div>
                <a href="{{ route('ingestion.index') }}" class="btn btn-outline-primary btn-sm fw-bold text-nowrap ms-3">
                    Open Ingestion Hub →
                </a>
            </div>
        </div>

        <div class="col-md-6">
            <div class="p-3 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="fw-bold mb-1">📋 Master Broadsheet & PDF Cards</h6>
                    <small class="text-muted">View unified broadsheets placing attendance and grades side-by-side; generate official JasperReports A4 PDFs.</small>
                </div>
                <a href="{{ route('reports.broadsheet') }}" class="btn btn-primary btn-sm fw-bold text-nowrap ms-3">
                    View Master Broadsheet →
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
