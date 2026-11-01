@extends('layouts.app')

@section('title', 'SGIS Report Card - ' . $data['student']['name'])

@section('styles')
<style>
    .sgis-sheet {
        background: #ffffff;
        width: 100%;
        max-width: 900px;
        margin: 0 auto;
        padding: 24px 30px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border-radius: 8px;
        color: #000000;
        font-family: Arial, Helvetica, sans-serif;
    }

    .sgis-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.82rem;
        margin-bottom: 8px;
    }

    .sgis-table th, .sgis-table td {
        border: 1px solid #111111;
        padding: 4px 6px;
        text-align: center;
        vertical-align: middle;
    }

    .sgis-table th {
        background-color: #f8fafc;
        font-weight: 700;
        color: #0f172a;
    }

    .sgis-table td.subject-name {
        text-align: left;
        padding-left: 10px;
        font-weight: 600;
    }

    .sgis-scale-table {
        border-collapse: collapse;
        font-size: 0.68rem;
    }

    .sgis-scale-table th, .sgis-scale-table td {
        border: 1px solid #222222;
        padding: 2px 5px;
        text-align: center;
    }

    .sgis-absent-table {
        border-collapse: collapse;
        font-size: 0.72rem;
        width: 100%;
    }

    .sgis-absent-table th, .sgis-absent-table td {
        border: 1px solid #222222;
        padding: 3px 6px;
        text-align: center;
    }

    @media print {
        body {
            background: #ffffff !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        header, .nav, .breadcrumb, .no-print, .alert {
            display: none !important;
        }
        .container-fluid {
            padding: 0 !important;
            max-width: 100% !important;
        }
        .sgis-sheet {
            box-shadow: none !important;
            border-radius: 0 !important;
            padding: 10px 18px !important;
            max-width: 100% !important;
            width: 100% !important;
        }
        @page {
            size: A4 portrait;
            margin: 8mm 10mm;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-0">
    <!-- Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="{{ route('reports.generator') }}" class="btn btn-outline-secondary btn-sm fw-semibold">
            ← Back to Reports Studio
        </a>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-primary btn-sm fw-semibold">
                🖨️ Print / Save as PDF
            </button>
            <a href="{{ route('reports.export.jasper', $data['student']['id']) }}" class="btn btn-primary btn-sm fw-bold">
                ⚡ Export JasperReports PDF
            </a>
        </div>
    </div>

    <!-- Official Report Sheet -->
    <div class="sgis-sheet">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div style="width: 110px;">
                <img src="{{ $data['school']['logoBase64'] ?? $data['school']['logoUrl'] }}" alt="School Logo" style="max-height: 80px; max-width: 100px; object-fit: contain;">
            </div>
            <div class="text-center flex-grow-1 px-2">
                <h4 class="fw-bold mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">{{ $data['school']['name'] }}</h4>
                <h5 class="fw-bold text-dark mb-0" style="font-size: 1.05rem;">{{ $data['school']['reportTitle'] }}</h5>
            </div>
            <div style="width: 110px;" class="text-end">
                <img src="{{ $data['school']['cogniaBase64'] ?? asset('reporting-engine/templates/images/cognia_seal.png') }}" 
                     alt="Cognia Accredited" 
                     style="height: 75px; width: 75px; object-fit: contain;">
            </div>
        </div>

        <!-- Student Meta Strip -->
        <div class="row gx-2 mb-2" style="font-size: 0.85rem;">
            <div class="col-8">
                <div><strong>Student Name :</strong> &nbsp; {{ $data['student']['name'] }}</div>
                <div class="mt-1"><strong>{{ $data['student']['className'] }}</strong></div>
            </div>
            <div class="col-4 text-end">
                <div><strong>GPA:</strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $data['metrics']['gpa'] }}</div>
                <div class="mt-1"><strong>Date:</strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $data['student']['date'] }}</div>
            </div>
        </div>

        <!-- Main Multi-Trimester Table -->
        <table class="sgis-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 28%; vertical-align: middle;">Subject</th>
                    <th colspan="2" style="width: 14%;">Trimester 1</th>
                    <th colspan="2" style="width: 14%;">Trimester 2</th>
                    <th colspan="2" style="width: 14%;">Trimester 3</th>
                    <th colspan="3" style="width: 30%;">Total Average</th>
                </tr>
                <tr>
                    <th style="font-size: 0.75rem;">%</th>
                    <th style="font-size: 0.75rem;">Letter Grade</th>
                    <th style="font-size: 0.75rem;">%</th>
                    <th style="font-size: 0.75rem;">Letter Grade</th>
                    <th style="font-size: 0.75rem;">%</th>
                    <th style="font-size: 0.75rem;">Letter Grade</th>
                    <th style="font-size: 0.75rem;">Percentage</th>
                    <th style="font-size: 0.75rem;">Letter Grade</th>
                    <th style="font-size: 0.75rem;">Credit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['subjectRows'] as $row)
                    <tr>
                        <td class="subject-name">{{ $row['subjectName'] }}</td>
                        <td>{{ $row['t1Percent'] }}</td>
                        <td class="fw-bold">{{ $row['t1Grade'] }}</td>
                        <td>{{ $row['t2Percent'] }}</td>
                        <td class="fw-bold">{{ $row['t2Grade'] }}</td>
                        <td>{{ $row['t3Percent'] }}</td>
                        <td class="fw-bold">{{ $row['t3Grade'] }}</td>
                        <td>{{ $row['cumulativePercent'] }}</td>
                        <td class="fw-bold">{{ $row['cumulativeGrade'] }}</td>
                        <td>{{ $row['credit'] }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="7" class="border-0"></td>
                    <td colspan="2" class="fw-bold text-end pe-2">Total Credits</td>
                    <td class="fw-bold">{{ $data['metrics']['totalCredits'] }}</td>
                </tr>
                <tr>
                    <td colspan="7" class="border-0"></td>
                    <td colspan="2" class="fw-bold text-end pe-2">GPA</td>
                    <td class="fw-bold">{{ $data['metrics']['gpa'] }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Summer Course Box -->
        <div class="border border-dark d-flex mb-3" style="font-size: 0.8rem;">
            <div class="p-2 fw-bold text-center border-end border-dark d-flex align-items-center justify-content-center" style="width: 25%; background-color: #fafafa;">
                Summer Course
            </div>
            <div class="p-2 flex-grow-1" style="font-size: 0.76rem; line-height: 1.35;">
                {{ $data['metrics']['summerCourses'] ?: 'None' }}
            </div>
        </div>

        <!-- Grading Scale Legend -->
        <div class="text-center fw-bold mb-3" style="font-size: 0.72rem; word-spacing: 2px;">
            (A+) 97-100 &nbsp;&nbsp; (A) 93-96 &nbsp;&nbsp; (A-) 90-92 &nbsp;&nbsp; (B+) 87-89 &nbsp;&nbsp; (B) 83-86 &nbsp;&nbsp; (B-) 80-82 &nbsp;&nbsp; (C+) 77-79 &nbsp;&nbsp; (C) 73-76 &nbsp;&nbsp; (C-) 70-72 &nbsp;&nbsp; (D+) 67-69 &nbsp;&nbsp; (D) 63-66 &nbsp;&nbsp; (D-) 60-62 &nbsp;&nbsp; (F) 00-59
        </div>

        <!-- Signatures Strip -->
        <div class="row text-center mb-3">
            <div class="col-6">
                <div class="fw-bold" style="font-size: 0.82rem;">Legal Representative</div>
                <div class="fst-italic fw-bold mt-1" style="font-size: 0.95rem; font-family: 'Times New Roman', serif;">
                    {{ $data['school']['legalRep'] }}
                </div>
            </div>
            <div class="col-6">
                <div class="fw-bold" style="font-size: 0.82rem;">School General Director</div>
                <div class="fst-italic fw-bold mt-1" style="font-size: 0.95rem; font-family: 'Times New Roman', serif;">
                    {{ $data['school']['director'] }}
                </div>
            </div>
        </div>

        <!-- GPA Scale & Absent Days Row -->
        <div class="row align-items-end mb-2">
            <!-- GPA Scale Mini-Table -->
            <div class="col-6">
                <div class="fw-bold mb-1" style="font-size: 0.75rem;">GPA Scale</div>
                <table class="sgis-scale-table">
                    <tr>
                        <td class="fw-bold">A+</td><td>4</td>
                        <td class="fw-bold">B+</td><td>3.3</td>
                        <td class="fw-bold">C+</td><td>2.3</td>
                        <td class="fw-bold">D+</td><td>1.3</td>
                        <td class="fw-bold">F</td><td>0</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">A</td><td>4</td>
                        <td class="fw-bold">B</td><td>3</td>
                        <td class="fw-bold">C</td><td>2</td>
                        <td class="fw-bold">D</td><td>1</td>
                        <td></td><td></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">A-</td><td>3.7</td>
                        <td class="fw-bold">B-</td><td>2.7</td>
                        <td class="fw-bold">C-</td><td>1.7</td>
                        <td class="fw-bold">D-</td><td>0.7</td>
                        <td></td><td></td>
                    </tr>
                </table>
            </div>

            <!-- Absent Days Table -->
            <div class="col-6">
                <table class="sgis-absent-table">
                    <thead>
                        <tr>
                            <th rowspan="2" style="vertical-align: middle; width: 40%;">Absent Days</th>
                            <th>Trimester 1</th>
                            <th>Trimester 2</th>
                            <th>Trimester 3</th>
                        </tr>
                        <tr>
                            <td>{{ $data['attendance']['t1'] }}</td>
                            <td>{{ $data['attendance']['t2'] }}</td>
                            <td>{{ $data['attendance']['t3'] }}</td>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <!-- Accreditation Notice -->
        <div class="text-center fst-italic my-2" style="font-size: 0.72rem; color: #1e293b;">
            {{ $data['school']['name'] }} is accredited by NCA CASI, NWAC and SACS CASI, accreditation divisions of Cognia (AdvancED)
        </div>

        <!-- Footer Strip -->
        <div class="border-top pt-2 mt-2 d-flex justify-content-between align-items-center" style="font-size: 0.68rem; color: #64748b;">
            <div style="line-height: 1.35;">
                <div>Area 40 El Markaz El-Raesi (El Malika Farida Street), Behind Carrefour, Maadi, Cairo, Egypt.</div>
                <div>(+20) 24470637/8/9 &nbsp;-&nbsp; Mob: (+20) 102 176 1000 &nbsp;-&nbsp; Fax: (+20) 24470640</div>
            </div>
            <div class="text-end" style="line-height: 1.35;">
                <div>info@sunofgloryinternationalschool.com</div>
                <div>www.sunofgloryinternationalschool.com</div>
                <div class="fst-italic mt-1" style="font-size: 0.62rem;">Powered by Skolera</div>
            </div>
        </div>
    </div>
</div>
@endsection

@if($isPrintMode)
@section('scripts')
<script>
    window.addEventListener('load', function() {
        setTimeout(function() {
            window.print();
        }, 400);
    });
</script>
@endsection
@endif
