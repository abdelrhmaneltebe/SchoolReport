<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Reporting Department Hub — Multi-Tenant Academic & Reporting Engine</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --primary-light: #e0e7ff;
            --surface-dark: #0f172a;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1e293b;
            background-color: #ffffff;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        .navbar-brand-badge {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            color: white;
            font-weight: 700;
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
        }

        .home-nav {
            height: 72px;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #f1f5f9;
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .hero-section {
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.12) 0%, rgba(248, 250, 252, 0.8) 40%, #ffffff 80%);
            padding: 5rem 0 4rem 0;
        }

        .badge-pill-accent {
            background-color: #eef2ff;
            color: #4338ca;
            border: 1px solid #c7d2fe;
            padding: 0.4rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .hero-title {
            font-size: 3.1rem;
            font-weight: 800;
            line-height: 1.15;
            color: #0f172a;
            letter-spacing: -0.03em;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.2rem;
            }
        }

        .hero-gradient-text {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn-gradient-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            color: #ffffff;
            border: none;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
            transition: all 0.2s ease-in-out;
        }

        .btn-gradient-primary:hover {
            background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.45);
        }

        .feature-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #ffffff;
            padding: 2rem;
            transition: all 0.25s ease-in-out;
            height: 100%;
        }

        .feature-card:hover {
            border-color: #cbd5e1;
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -10px rgba(15, 23, 42, 0.1);
        }

        .feature-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 1.25rem;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
            text-align: center;
        }

        .demo-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #cbd5e1;
            border-radius: 20px;
            box-shadow: 0 20px 30px -15px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .live-preview-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 20px 40px -15px rgba(79, 70, 229, 0.15);
            overflow: hidden;
        }

        .shadow-2xs {
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .footer {
            background-color: #0f172a;
            color: #94a3b8;
            padding: 3.5rem 0 2rem 0;
            border-top: 1px solid #1e293b;
        }
    </style>
</head>
<body>

    <!-- 1. TOP NAVIGATION -->
    <header class="home-nav d-flex align-items-center">
        <div class="container d-flex align-items-center justify-content-between">
            <a href="{{ route('home') }}" class="d-flex align-items-center text-decoration-none" style="gap: 12px;">
                <span class="navbar-brand-badge flex-shrink-0">SR</span>
                <div class="d-flex flex-column">
                    <span class="fw-bold text-dark lh-sm" style="font-size: 1.05rem; letter-spacing: -0.3px;">School Reporting</span>
                    <span class="text-muted" style="font-size: 0.72rem; font-weight: 600;">Department Hub</span>
                </div>
            </a>

            <!-- Center navigation links -->
            <nav class="d-none d-md-flex align-items-center gap-4">
                <a href="#features" class="text-secondary text-decoration-none fw-semibold small hover-dark">Academic Pillars</a>
                <a href="#jasper" class="text-secondary text-decoration-none fw-semibold small hover-dark">JasperReports Studio</a>
                <a href="#pricing" class="text-secondary text-decoration-none fw-semibold small hover-dark">Plans &amp; Pricing</a>
                <a href="#instant-demo" class="text-secondary text-decoration-none fw-semibold small hover-dark">Live Test Drive</a>
                <a href="#demo" class="text-secondary text-decoration-none fw-semibold small hover-dark">Request Demo</a>
            </nav>

            <!-- Action buttons -->
            <div class="d-flex align-items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-primary px-3 py-1.5 fw-semibold" style="border-radius: 8px;">
                        📊 Open Dashboard
                    </a>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary px-2.5 py-1.5 fw-semibold" style="border-radius: 8px;" title="Sign Out">
                            🚪 Exit
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary px-3 py-1.5 fw-semibold" style="border-radius: 8px;">
                        🔑 Sign In
                    </a>
                    <a href="#demo" class="btn btn-sm btn-gradient-primary px-3 py-1.5 fw-semibold" style="border-radius: 8px;">
                        🚀 Request a Demo
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- 2. HERO SECTION -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center gy-5">
                <div class="col-lg-7">
                    <div class="badge-pill-accent mb-3">
                        <span>✨ Multi-Tenant Academic &amp; Reporting Engine 2.6</span>
                    </div>
                    <h1 class="hero-title mb-3">
                        Enterprise School Reporting, <span class="hero-gradient-text">Cognia Report Cards</span> &amp; Academic Management
                    </h1>
                    <p class="lead text-muted mb-4" style="font-size: 1.15rem; line-height: 1.6;">
                        Streamline your school's three foundational pillars — <strong>Attendance Policies</strong>, 
                        <strong>Dynamic Grading Hierarchies</strong>, and <strong>Class Subjects</strong> — with high-resolution 
                        JasperReports PDF generation, multi-trimester evaluations, and automated Excel ingestion.
                    </p>

                    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-lg btn-gradient-primary px-4 py-2.5 fw-bold" style="border-radius: 10px;">
                                📊 Go to Dashboard →
                            </a>
                            <a href="#demo" class="btn btn-lg btn-light border px-4 py-2.5 fw-bold shadow-2xs" style="border-radius: 10px;">
                                📋 Request Institutional Demo
                            </a>
                        @else
                            <a href="#demo" class="btn btn-lg btn-gradient-primary px-4 py-2.5 fw-bold" style="border-radius: 10px;">
                                📋 Request Institutional Demo
                            </a>
                            <a href="{{ route('login') }}" class="btn btn-lg btn-light border px-4 py-2.5 fw-bold shadow-2xs" style="border-radius: 10px;">
                                Sign In to Portal →
                            </a>
                            <a href="#instant-demo" class="btn btn-link text-decoration-none text-indigo fw-bold ps-1">
                                ⚡ Try 1-Click Test Drive
                            </a>
                        @endauth
                    </div>

                    <!-- Platform stats -->
                    <div class="row g-3 pt-2">
                        <div class="col-sm-4 col-6">
                            <div class="stat-card">
                                <div class="fs-4 fw-bold text-dark">{{ $schoolsCount ?? 2 }} Active</div>
                                <small class="text-muted fw-semibold">School Tenants</small>
                            </div>
                        </div>
                        <div class="col-sm-4 col-6">
                            <div class="stat-card">
                                <div class="fs-4 fw-bold text-primary">{{ $studentsCount ?? 110 }}+</div>
                                <small class="text-muted fw-semibold">Enrolled Students</small>
                            </div>
                        </div>
                        <div class="col-sm-4 col-12">
                            <div class="stat-card">
                                <div class="fs-4 fw-bold text-success">100% Vector</div>
                                <small class="text-muted fw-semibold">JasperReports PDFs</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hero Graphic / Mockup -->
                <div class="col-lg-5">
                    <div class="live-preview-card">
                        <div class="bg-dark text-white p-3 d-flex align-items-center justify-content-between border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-danger rounded-circle p-1"></span>
                                <span class="badge bg-warning rounded-circle p-1"></span>
                                <span class="badge bg-success rounded-circle p-1"></span>
                                <span class="small fw-semibold ms-2 font-monospace" style="font-size: 0.78rem;">SGIS_Report_Card_A4.pdf</span>
                            </div>
                            <span class="badge bg-primary text-white" style="font-size: 0.65rem;">Cognia Accredited</span>
                        </div>
                        <div class="p-4 bg-white">
                            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Smart Generation Int. School</h6>
                                    <small class="text-muted">Grade 12 • Academic Year 2026-2027</small>
                                </div>
                                <span class="badge bg-indigo-subtle text-indigo px-2 py-1 fw-bold">GPA: 3.82</span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-borderless small mb-0">
                                    <thead class="text-muted border-bottom" style="font-size: 0.72rem;">
                                        <tr>
                                            <th>SUBJECT</th>
                                            <th class="text-center">T1</th>
                                            <th class="text-center">T2</th>
                                            <th class="text-center">T3</th>
                                            <th class="text-end">STATUS</th>
                                        </tr>
                                    </thead>
                                    <tbody style="font-size: 0.78rem;">
                                        <tr>
                                            <td class="fw-semibold">Advanced Calculus</td>
                                            <td class="text-center">92%</td>
                                            <td class="text-center">94%</td>
                                            <td class="text-center">95%</td>
                                            <td class="text-end"><span class="badge bg-success-subtle text-success">Pass</span></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold">Physics Honors</td>
                                            <td class="text-center">88%</td>
                                            <td class="text-center">91%</td>
                                            <td class="text-center">89%</td>
                                            <td class="text-end"><span class="badge bg-success-subtle text-success">Pass</span></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold">English Literature</td>
                                            <td class="text-center">95%</td>
                                            <td class="text-center">96%</td>
                                            <td class="text-center">94%</td>
                                            <td class="text-end"><span class="badge bg-success-subtle text-success">Pass</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3 p-2.5 rounded bg-light border d-flex align-items-center justify-content-between">
                                <small class="text-muted fw-semibold">Remedial Summer Alert:</small>
                                <span class="badge bg-success text-white">None Required (Passed All)</span>
                            </div>

                            <div class="mt-3 text-center">
                                <a href="#instant-demo" class="btn btn-sm btn-outline-primary w-100 fw-bold py-1.5">
                                    Inspect Live Interactive Reports →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. THE THREE PILLARS OF ACADEMIC ARCHITECTURE -->
    <section id="features" class="py-5 bg-light border-top border-bottom">
        <div class="container py-4">
            <div class="text-center max-w-700 mx-auto mb-5">
                <span class="badge bg-indigo-subtle text-indigo px-3 py-1 rounded-pill fw-bold small mb-2">SYSTEM FOUNDATION</span>
                <h2 class="fw-bold text-dark mb-3">The 3 Essential Academic Pillars</h2>
                <p class="text-muted">A unified structural architecture engineered to manage complex school regulations, grading standards, and subject enrollments with pinpoint precision.</p>
            </div>

            <div class="row g-4">
                <!-- Pillar 1 -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper bg-primary-subtle text-primary">
                            📅
                        </div>
                        <span class="badge bg-primary text-white mb-2" style="font-size: 0.65rem;">PILLAR 1</span>
                        <h4 class="fw-bold text-dark mb-2">Attendance Policies</h4>
                        <p class="text-muted small mb-3">
                            Configurable school-level absence limits, excused vs. unexcused tracking, automated parent warning triggers, and minimum attendance thresholds required for semester credit completion.
                        </p>
                        <ul class="list-unstyled small text-secondary mb-0">
                            <li class="mb-1.5">✓ Strict unexcused absence ceiling</li>
                            <li class="mb-1.5">✓ Automated credit denial thresholds</li>
                            <li>✓ Daily &amp; period-based status auditing</li>
                        </ul>
                    </div>
                </div>

                <!-- Pillar 2 -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper bg-success-subtle text-success">
                            🎓
                        </div>
                        <span class="badge bg-success text-white mb-2" style="font-size: 0.65rem;">PILLAR 2</span>
                        <h4 class="fw-bold text-dark mb-2">Dynamic Grading Hierarchies</h4>
                        <p class="text-muted small mb-3">
                            Multi-tier structure supporting Terms, Trimesters, Quarters, with weighted sub-categories (Classwork, Homework, Projects, Exams). Seamlessly switch between Percent and Weight scale models.
                        </p>
                        <ul class="list-unstyled small text-secondary mb-0">
                            <li class="mb-1.5">✓ Unlimited nested grading periods</li>
                            <li class="mb-1.5">✓ Custom pass benchmarks (60% standard)</li>
                            <li>✓ GPA &amp; Academic Distinction formulas</li>
                        </ul>
                    </div>
                </div>

                <!-- Pillar 3 -->
                <div class="col-lg-4 col-md-12">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper bg-info-subtle text-info">
                            🏫
                        </div>
                        <span class="badge bg-info text-white mb-2" style="font-size: 0.65rem;">PILLAR 3</span>
                        <h4 class="fw-bold text-dark mb-2">Class Subjects &amp; Curriculum</h4>
                        <p class="text-muted small mb-3">
                            Master subject catalog mapped across grade levels and classrooms. Flexible student course enrollments, elective tracking, and granular teacher assignment permissions.
                        </p>
                        <ul class="list-unstyled small text-secondary mb-0">
                            <li class="mb-1.5">✓ Grade level &amp; section batch mapping</li>
                            <li class="mb-1.5">✓ Selective student course enrollment</li>
                            <li>✓ Direct deep links to interactive marksheets</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. JASPERREPORTS & INGESTION ENGINE -->
    <section id="jasper" class="py-5 bg-white">
        <div class="container py-4">
            <div class="row align-items-center gy-5">
                <div class="col-lg-6">
                    <span class="badge bg-primary text-white px-3 py-1 rounded-pill fw-bold small mb-2">REPORT STUDIO</span>
                    <h2 class="fw-bold text-dark mb-3">Enterprise JasperReports Studio &amp; Excel Ingestion</h2>
                    <p class="text-muted mb-4">
                        Say goodbye to broken HTML print views. Our dedicated reporting engine compiles JRXML templates into vector-crisp PDFs with sub-second execution, automated failover, and bulk spreadsheet ingestion.
                    </p>

                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex align-items-start gap-3">
                            <div class="badge bg-primary text-white p-2 rounded-3 fs-6">📑</div>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">SGIS 3-Trimester Custom Report Cards</h6>
                                <p class="small text-muted mb-0">Includes official Cognia accreditation seal, multi-period breakdown, GPA calculation, and remedial summer course alerts.</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <div class="badge bg-success text-white p-2 rounded-3 fs-6">📥</div>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">Intelligent Excel Marksheet Ingestion</h6>
                                <p class="small text-muted mb-0">Upload school CSV marksheets with automated column mapping, category detection, validation safeguards, and instant gradebook sync.</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <div class="badge bg-indigo text-white p-2 rounded-3 fs-6">🏢</div>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">Strict Multi-Tenant Isolation</h6>
                                <p class="small text-muted mb-0">Complete data partition between schools. Super Admins can manage and switch between tenants, while School Admins stay strictly scoped.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="p-4 p-md-5 rounded-4 bg-dark text-white shadow-lg">
                        <div class="d-flex align-items-center justify-content-between border-bottom border-secondary pb-3 mb-4">
                            <span class="fw-bold text-indigo" style="color: #a5b4fc !important;">⚙️ Live Engine Capabilities</span>
                            <span class="badge bg-success text-white">Online &amp; Ready</span>
                        </div>
                        <div class="font-monospace small mb-3 text-secondary" style="color: #94a3b8 !important;">
                            &gt; Engine: Java 17 + JasperReports 7.0<br>
                            &gt; Fallback: High-Res Print/PDF Web Renderer<br>
                            &gt; Active Scaling: Multi-Tenant Tenant Isolation<br>
                            &gt; Formats: PDF Vector, Excel Ingestion, CSV Analytics
                        </div>
                        <div class="border-top border-secondary pt-3 mt-4 text-center">
                            <a href="#instant-demo" class="btn btn-primary fw-bold px-4 py-2">
                                Test Drive Live Reports Studio →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 4.5. SUBSCRIPTION PLANS & PRICING -->
    <section id="pricing" class="py-5 bg-white border-top">
        <div class="container py-4">
            <div class="text-center max-w-700 mx-auto mb-5">
                <span class="badge bg-indigo-subtle text-indigo px-3 py-1 rounded-pill fw-bold small mb-2">TRANSPARENT INSTITUTIONAL PLANS</span>
                <h2 class="fw-bold text-dark mb-2">Predictable Plans for Modern Schools</h2>
                <p class="text-muted">Choose the ideal tier tailored to your school body size and academic reporting requirements. All plans include dedicated multi-tenant isolation, automatic cloud backups, and Cognia-aligned marksheet engines.</p>
            </div>

            <div class="row g-4 justify-content-center align-items-stretch">
                @forelse($plans as $plan)
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 rounded-4 border {{ $plan->is_default ? 'border-primary border-2 shadow-lg position-relative' : 'border-slate-200 shadow-sm' }} bg-white d-flex flex-column justify-content-between overflow-hidden">
                            
                            @if($plan->is_default)
                                <div class="position-absolute top-0 end-0 bg-primary text-white small fw-bold px-3 py-1 rounded-bottom-start shadow-sm" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                    ★ MOST POPULAR TIER
                                </div>
                            @endif

                            <div class="p-4 p-md-5">
                                <div class="mb-3">
                                    <span class="badge bg-light text-secondary border font-monospace px-2 py-1 small">{{ strtoupper($plan->code) }}</span>
                                </div>
                                <h4 class="fw-bold text-dark mb-2">{{ $plan->name }}</h4>
                                <p class="text-muted small mb-4" style="min-height: 40px;">
                                    {{ $plan->description ?? 'Full academic management and reporting package.' }}
                                </p>

                                <div class="d-flex align-items-baseline gap-1 mb-4 pb-3 border-bottom">
                                    <span class="display-5 fw-bold text-dark">${{ number_format($plan->price, 0) }}</span>
                                    <span class="text-muted fw-semibold">/ {{ $plan->billing_cycle }}</span>
                                </div>

                                <!-- Capacity Highlights -->
                                <div class="mb-4 p-3 bg-light rounded-3 border">
                                    <div class="d-flex align-items-center gap-2 mb-2 small text-dark fw-semibold">
                                        <span>👥</span>
                                        <span>{{ $plan->max_students ? number_format($plan->max_students) . ' Students Capacity' : 'Unlimited Students' }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mb-2 small text-dark fw-semibold">
                                        <span>🏛️</span>
                                        <span>{{ $plan->max_classes ? number_format($plan->max_classes) . ' Classes & Sections' : 'Unlimited Classes' }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 small text-dark fw-semibold">
                                        <span>🛡️</span>
                                        <span>Independent Database Tenant</span>
                                    </div>
                                </div>

                                <!-- Feature List -->
                                <h6 class="fw-bold small text-muted text-uppercase mb-3" style="font-size: 0.72rem; letter-spacing: 0.8px;">
                                    Included Modules ({{ count($plan->features ?? []) }} Features):
                                </h6>
                                <ul class="list-unstyled mb-0 small">
                                    @foreach($availableFeatures as $fKey => $fMeta)
                                        @php
                                            $isIncluded = $plan->hasFeature($fKey);
                                        @endphp
                                        <li class="py-1.5 d-flex align-items-center justify-content-between {{ $isIncluded ? 'text-dark' : 'text-muted opacity-40' }}">
                                            <div class="d-flex align-items-center gap-2">
                                                <span>{{ $fMeta['icon'] }}</span>
                                                <span class="{{ $isIncluded ? 'fw-medium' : 'text-decoration-line-through' }}">{{ $fMeta['name'] }}</span>
                                                @if($fKey === 'student_profiles' && $isIncluded)
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-1.5 py-0" style="font-size: 0.68rem;">
                                                        {{ $plan->max_students ? number_format($plan->max_students) . ' Students Max' : 'Unlimited' }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($isIncluded)
                                                <span class="text-success fw-bold">✓</span>
                                            @else
                                                <span class="text-muted" style="font-size: 0.75rem;">—</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="p-4 bg-light bg-opacity-50 border-top mt-auto">
                                <a href="#demo" class="btn w-100 fw-bold py-2.5 {{ $plan->is_default ? 'btn-gradient-primary shadow-sm' : 'btn-outline-primary' }}" style="border-radius: 10px;" onclick="selectDemoPlan('{{ $plan->name }}')">
                                    Get Started with {{ $plan->name }} →
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center text-muted py-5">
                        <p>No plans currently active. Please configure plans in the Super Admin console.</p>
                    </div>
                @endforelse
            </div>

            <!-- Enterprise banner below cards -->
            <div class="mt-5 p-4 rounded-4 bg-light border text-center max-w-900 mx-auto">
                <div class="row align-items-center justify-content-between g-3">
                    <div class="col-md-8 text-md-start">
                        <h5 class="fw-bold text-dark mb-1">Need a tailored plan for a School District or Multi-Campus Network?</h5>
                        <p class="text-muted small mb-0">We support custom server provisions, tailored Cognia / British IB templates, dedicated Report Studio compiler environments, and on-premise deployments.</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="#demo" class="btn btn-dark fw-bold px-4 py-2" style="border-radius: 8px;">
                            Contact Academic Solutions →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. 1-CLICK INSTANT TEST DRIVE -->
    <section id="instant-demo" class="py-5 bg-light border-top border-bottom">
        <div class="container py-4">
            <div class="text-center max-w-700 mx-auto mb-5">
                <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill fw-bold small mb-2">TRY IT RIGHT NOW</span>
                <h2 class="fw-bold text-dark mb-2">Instant 1-Click Interactive Demo</h2>
                <p class="text-muted">Explore the platform immediately with full access as a School Admin or Super Admin without filling any forms.</p>
            </div>

            <div class="row g-4 justify-content-center">
                <!-- School Admin Demo -->
                <div class="col-md-5">
                    <div class="demo-card p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="fs-2">🏫</span>
                                <span class="badge bg-primary text-white px-2 py-1">TENANT SCOPED</span>
                            </div>
                            <h4 class="fw-bold text-dark mb-1">School Administrator</h4>
                            <p class="text-muted small mb-3">Experience the full school workflow: Attendance policies, grading rules, class profiles, student roster, gradebook, and JasperReports generation.</p>
                            <ul class="list-unstyled small text-secondary mb-4">
                                <li class="mb-1">✓ Live SGIS / Green Valley gradebook</li>
                                <li class="mb-1">✓ Generate official PDF report cards</li>
                                <li class="mb-1">✓ Class &amp; subject management</li>
                            </ul>
                        </div>
                        <a href="{{ route('quick_login', 'SCHOOL_ADMIN') }}" class="btn btn-primary w-100 fw-bold py-2 shadow-2xs">
                            Launch School Admin Demo →
                        </a>
                    </div>
                </div>

                <!-- Super Admin Demo -->
                <div class="col-md-5">
                    <div class="demo-card p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="fs-2">👑</span>
                                <span class="badge bg-dark text-white px-2 py-1">PLATFORM GOVERNANCE</span>
                            </div>
                            <h4 class="fw-bold text-dark mb-1">Super Administrator</h4>
                            <p class="text-muted small mb-3">Explore multi-campus management: Provision new schools, switch between school tenants in real time, and manage administrative user accounts.</p>
                            <ul class="list-unstyled small text-secondary mb-4">
                                <li class="mb-1">✓ Create &amp; configure multiple schools</li>
                                <li class="mb-1">✓ Global school tenant switcher</li>
                                <li class="mb-1">✓ User directory &amp; credential control</li>
                            </ul>
                        </div>
                        <a href="{{ route('quick_login', 'SUPER_ADMIN') }}" class="btn btn-dark w-100 fw-bold py-2 shadow-2xs">
                            Launch Super Admin Demo →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. REQUEST A DEMO SECTION -->
    <section id="demo" class="py-5 bg-white">
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">
                    <div class="card border rounded-4 p-4 p-md-5 shadow-sm">
                        <div class="text-center mb-4">
                            <span class="badge bg-indigo-subtle text-indigo px-3 py-1 rounded-pill fw-bold small mb-2">PERSONALIZED WALKTHROUGH</span>
                            <h2 class="fw-bold text-dark mb-2">Request an Institutional Demo</h2>
                            <p class="text-muted small mb-0">Our academic solutions team will walk your school leadership through custom report card layouts, grading rules, and migration options.</p>
                        </div>

                        <!-- Success Alert -->
                        @if(session('demo_success'))
                            <div class="alert alert-success alert-dismissible fade show p-3 rounded-3 mb-4 shadow-2xs" role="alert">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="fs-5">✓</span>
                                    <strong class="text-success">Demo Request Received!</strong>
                                </div>
                                <div class="small">{{ session('demo_success') }}</div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form action="{{ route('request_demo') }}" method="POST">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" 
                                           placeholder="Dr. Sarah Johnson" value="{{ old('full_name') }}" required>
                                    @error('full_name')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Institutional Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                           placeholder="s.johnson@school.edu" value="{{ old('email') }}" required>
                                    @error('email')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">School / Academy Name <span class="text-danger">*</span></label>
                                    <input type="text" name="school_name" class="form-control @error('school_name') is-invalid @enderror" 
                                           placeholder="Smart Generation International School" value="{{ old('school_name') }}" required>
                                    @error('school_name')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Phone / WhatsApp</label>
                                    <input type="text" name="phone" class="form-control" 
                                           placeholder="+1 (555) 019-2834" value="{{ old('phone') }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Student Body Size</label>
                                    <select name="student_count" class="form-select">
                                        <option value="Under 500 Students">Under 500 Students</option>
                                        <option value="500 - 1,500 Students">500 - 1,500 Students</option>
                                        <option value="1,500 - 3,500 Students">1,500 - 3,500 Students</option>
                                        <option value="3,500+ Students (Multi-Campus)">3,500+ Students (Multi-Campus)</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Preferred Subscription Plan</label>
                                    <select name="interested_plan" id="demoInterestedPlan" class="form-select">
                                        <option value="">-- Let us recommend a tier --</option>
                                        @foreach($plans as $pl)
                                            <option value="{{ $pl->name }}">{{ $pl->name }} (${{ number_format($pl->price, 0) }}/{{ $pl->billing_cycle }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Your Role</label>
                                    <select name="role" class="form-select">
                                        <option value="School Principal / Director">School Principal / Director</option>
                                        <option value="Academic Dean / Department Head">Academic Dean / Department Head</option>
                                        <option value="IT Director / System Coordinator">IT Director / System Coordinator</option>
                                        <option value="Registrar / Administrative Officer">Registrar / Administrative Officer</option>
                                        <option value="Teacher / Academic Counselor">Teacher / Academic Counselor</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-dark">Curriculum &amp; Reporting Requirements (Optional)</label>
                                    <textarea name="notes" class="form-control" rows="3" 
                                              placeholder="Tell us about your grading terms (Trimesters/Semesters), accreditation standards (Cognia, IB, Cambridge), or current marksheet challenges...">{{ old('notes') }}</textarea>
                                </div>

                                <div class="col-12 pt-2">
                                    <button type="submit" class="btn btn-gradient-primary w-100 fw-bold py-2.5 fs-6 shadow-2xs">
                                        Submit Demo Request 🚀
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 7. FOOTER -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4 align-items-center justify-content-between mb-4">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="navbar-brand-badge flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.85rem;">SR</span>
                        <span class="fw-bold text-white fs-6">School Reporting Department Hub</span>
                    </div>
                    <p class="small text-secondary mb-0" style="max-width: 420px; color: #94a3b8 !important;">
                        Multi-tenant academic reporting engine engineered for dynamic grading hierarchies, Cognia-compliant student reports, and JasperReports vector PDF generation.
                    </p>
                </div>
                <div class="col-md-5 text-md-end">
                    <div class="d-flex flex-wrap justify-content-md-end gap-3 small">
                        <a href="{{ route('login') }}" class="text-white text-decoration-none">Portal Sign In</a>
                        <span class="text-secondary">•</span>
                        <a href="#pricing" class="text-white text-decoration-none">Plans &amp; Pricing</a>
                        <span class="text-secondary">•</span>
                        <a href="#instant-demo" class="text-white text-decoration-none">Live Test Drive</a>
                        <span class="text-secondary">•</span>
                        <a href="#demo" class="text-white text-decoration-none">Request Demo</a>
                    </div>
                    <small class="d-block text-secondary mt-2" style="color: #64748b !important;">
                        Version 2.6 Multi-Tenant Edition • All Rights Reserved &copy; {{ date('Y') }}
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectDemoPlan(planName) {
            const selectEl = document.getElementById('demoInterestedPlan');
            if (selectEl) {
                for (let i = 0; i < selectEl.options.length; i++) {
                    if (selectEl.options[i].text.includes(planName)) {
                        selectEl.selectedIndex = i;
                        break;
                    }
                }
            }
        }
    </script>
</body>
</html>
