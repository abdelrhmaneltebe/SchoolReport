<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'School Reporting Department Hub')</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bs-primary: #4f46e5;
            --bs-primary-rgb: 79, 70, 229;
        }
        body {
            background-color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1e293b;
        }
        .navbar-brand-badge {
            background-color: #4f46e5;
            color: white;
            font-weight: 700;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .card-custom {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            background-color: #ffffff;
        }
        .nav-pills .nav-link {
            color: #64748b;
            font-weight: 600;
            font-size: 0.875rem;
            border-radius: 8px;
            padding: 8px 16px;
        }
        .nav-pills .nav-link.active {
            background-color: #4f46e5;
            color: white;
        }
        .pagination {
            margin-bottom: 0;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 3px;
        }
        .pagination svg {
            width: 1rem !important;
            height: 1rem !important;
            max-width: 1rem !important;
            max-height: 1rem !important;
            display: inline-block !important;
            vertical-align: middle !important;
        }
        .pagination .page-item .page-link {
            color: #334155;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px !important;
            padding: 0.35rem 0.65rem;
            font-weight: 600;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-decoration: none !important;
            transition: all 0.15s ease-in-out;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }
        .pagination .page-item.active .page-link {
            background-color: #4f46e5 !important;
            border-color: #4f46e5 !important;
            color: #ffffff !important;
            box-shadow: 0 1px 3px rgba(79, 70, 229, 0.3);
        }
        .pagination .page-item.disabled .page-link {
            color: #94a3b8 !important;
            background-color: #f8fafc !important;
            border-color: #e2e8f0 !important;
            cursor: not-allowed;
            box-shadow: none;
        }
        .pagination .page-item:not(.disabled):not(.active) .page-link:hover {
            background-color: #e0e7ff;
            border-color: #a5b4fc;
            color: #3730a3;
        }

        /* Enhanced High-Contrast Text & Badges (Ensures readability across all displays) */
        .text-muted {
            color: #475569 !important; /* Deep slate instead of faint grey */
        }
        .text-secondary {
            color: #334155 !important;
        }
        .text-indigo {
            color: #312e81 !important;
        }
        .bg-indigo-subtle {
            background-color: #e0e7ff !important;
        }
        .border-indigo-subtle {
            border-color: #c7d2fe !important;
        }

        /* High-Contrast Badge & Label Contrast System */
        .badge {
            font-weight: 600;
        }

        /* 1. DARK BACKGROUNDS => LIGHT / WHITE FONT */
        .badge.bg-dark,
        .badge.bg-secondary,
        .badge.bg-primary,
        .badge.bg-success,
        .badge.bg-danger,
        .badge.bg-indigo,
        .badge.text-bg-dark,
        .badge.text-bg-secondary,
        .badge.text-bg-primary,
        .badge.text-bg-success,
        .badge.text-bg-danger,
        .badge.text-bg-indigo,
        .bg-dark.badge,
        .bg-secondary.badge,
        .bg-primary.badge,
        .bg-success.badge,
        .bg-danger.badge,
        [class*="badge"][class*="bg-dark"],
        [class*="badge"][class*="bg-secondary"]:not([class*="-subtle"]),
        [class*="badge"][class*="bg-primary"]:not([class*="-subtle"]),
        [class*="badge"][class*="bg-success"]:not([class*="-subtle"]),
        [class*="badge"][class*="bg-danger"]:not([class*="-subtle"]) {
            color: #ffffff !important;
        }

        /* 2. LIGHT BACKGROUNDS => DEEP DARK FONT */
        .badge.bg-light,
        .badge.bg-white,
        .badge.text-bg-light {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
        }
        .badge.bg-white {
            background-color: #ffffff !important;
        }
        .badge.bg-warning,
        .badge.text-bg-warning {
            background-color: #f59e0b !important;
            color: #1e1b4b !important;
        }
        .badge.bg-info,
        .badge.text-bg-info {
            background-color: #0ea5e9 !important;
            color: #ffffff !important;
        }

        /* Subtle (pastel) light backgrounds => Dark contextual font */
        .badge.bg-secondary-subtle,
        .badge.text-secondary,
        .badge.text-muted {
            background-color: #e2e8f0 !important;
            color: #1e293b !important;
            border-color: #cbd5e1 !important;
        }
        .badge.bg-indigo-subtle,
        .badge.bg-primary-subtle {
            background-color: #e0e7ff !important;
            color: #1e1b4b !important;
            border-color: #c7d2fe !important;
        }
        .badge.bg-success-subtle {
            background-color: #dcfce7 !important;
            color: #14532d !important;
            border-color: #bbf7d0 !important;
        }
        .badge.bg-warning-subtle {
            background-color: #fef3c7 !important;
            color: #78350f !important;
            border-color: #fde68a !important;
        }
        .badge.bg-danger-subtle {
            background-color: #fee2e2 !important;
            color: #7f1d1d !important;
            border-color: #fecaca !important;
        }
        .badge.bg-info-subtle {
            background-color: #e0f2fe !important;
            color: #0c4a6e !important;
            border-color: #bae6fd !important;
        }
    </style>
    @yield('styles')
</head>
<body class="py-4">
    <div class="container-fluid px-4 max-w-7xl">
        <!-- Top App Header -->
        <header class="card-custom p-3 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                @php
                    $navSchool = $currentSchool ?? (\App\Models\School::where('school_id', session('active_school_id') ?? (auth()->check() ? auth()->user()->school_id : null))->first() ?? \App\Models\School::first());
                @endphp
                @if($navSchool)
                    <a href="{{ route('school.profile') }}" title="Click to manage {{ $navSchool->school_name }} profile & logo">
                        <img src="{{ $navSchool->getLogoUrl() }}" alt="{{ $navSchool->school_name }}" class="rounded-2 border bg-white p-0.5 shadow-2xs" style="height: 38px; width: 38px; object-fit: contain;">
                    </a>
                @else
                    <span class="navbar-brand-badge">SR</span>
                @endif
                <div>
                    <h5 class="mb-0 fw-bold">School Reporting Department Hub</h5>
                    <small class="text-muted">Multi-Tenant Reporting & Ingestion Engine</small>
                </div>
            </div>

            <!-- Active Tenant & User Session Controls -->
            <div class="d-flex flex-wrap align-items-center gap-3">
                @auth
                    <!-- Super Admin School Switcher vs. Individual School Lock -->
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0 text-muted small fw-semibold">
                                {{ auth()->user()->isSuperAdmin() ? '🌍 Active School Switcher' : '🏫 Assigned School Tenant' }}
                            </label>
                            <a href="{{ route('school.profile') }}" class="text-primary small fw-semibold text-decoration-none ms-2" title="Manage School Profile & Logo">
                                🖼️ School Logo ↗
                            </a>
                        </div>
                        @if(auth()->user()->isSuperAdmin())
                            <select class="form-select form-select-sm fw-bold border-primary" onchange="window.location.href='{{ url('/admin/schools/switch') }}/' + this.value">
                                @foreach($allSchools ?? [\App\Models\School::first()] as $sch)
                                    <option value="{{ $sch->school_id }}" {{ (session('active_school_id') ?? $currentSchool->school_id ?? '') === $sch->school_id ? 'selected' : '' }}>
                                        🏫 {{ $sch->school_name }} ({{ $sch->school_code }})
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <div class="p-1 px-2 border rounded bg-light small fw-bold text-dark d-flex align-items-center justify-content-between gap-2">
                                <span>🏫 {{ auth()->user()->school->school_name ?? $currentSchool->school_name ?? 'School' }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- User Profile & Sign Out -->
                    <div class="d-flex align-items-center gap-2 border-start ps-3">
                        <div class="text-end">
                            <div class="fw-bold small text-dark">{{ auth()->user()->name }}</div>
                            <span class="badge {{ auth()->user()->isSuperAdmin() ? 'bg-dark' : 'bg-primary' }} text-white" style="font-size: 0.65rem;">
                                {{ auth()->user()->isSuperAdmin() ? '👑 SUPER ADMIN' : '🏫 SCHOOL ADMIN' }}
                            </span>
                        </div>
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Sign Out">
                                🚪 Exit
                            </button>
                        </form>
                    </div>
                @else
                    <!-- Guest Session Login Shortcut -->
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted">Viewing as Guest</span>
                        <a href="{{ route('login') }}" class="btn btn-sm btn-primary fw-bold px-3">
                            Sign In / Switch Role →
                        </a>
                    </div>
                @endauth
            </div>
        </header>

        <!-- Main Navigation Pills -->
        <ul class="nav nav-pills mb-4 gap-2 bg-white p-2 rounded-3 border border-slate-200 shadow-sm align-items-center">
            @auth
                @if(auth()->user()->isSuperAdmin())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.schools.*') ? 'active' : '' }}" href="{{ route('admin.schools.index') }}">
                            🏢 Manage Schools
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                            👥 User Directory
                        </a>
                    </li>
                    <li class="nav-item border-start ps-2 my-1"></li>
                @endif
            @endauth

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    📊 Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('config.levels*') ? 'active' : '' }}" href="{{ route('config.levels') }}">
                    🏛️ School Levels
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('config.attendance') ? 'active' : '' }}" href="{{ route('config.attendance') }}">
                    📅 Attendance (Pillar 1)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('config.grading') ? 'active' : '' }}" href="{{ route('config.grading') }}">
                    🎓 Grading & Templates (Pillar 2)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('config.class_subjects') ? 'active' : '' }}" href="{{ route('config.class_subjects') }}">
                    🏫 Class Subjects (Pillar 3)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('config.students*') ? 'active' : '' }}" href="{{ route('config.students.index') }}">
                    👥 Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.generator') }}">
                    📊 Reports Studio
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('ingestion.*') ? 'active' : '' }}" href="{{ route('ingestion.index') }}">
                    📥 Excel Ingestion
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('school.profile*') ? 'active' : '' }}" href="{{ route('school.profile') }}">
                    🖼️ School Logo &amp; Profile
                </a>
            </li>
        </ul>

        <!-- Flash Notifications -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show small py-2 px-3 mb-4 rounded-3" role="alert">
                <strong>✓ Success:</strong> {{ session('success') }}
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show small py-2 px-3 mb-4 rounded-3" role="alert">
                <strong>⚠️ Notice:</strong> {{ session('error') }}
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Main Content View -->
        <main>
            @yield('content')
        </main>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
