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
            margin: 0;
            padding: 0;
            overflow-x: hidden;
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
        .card-custom {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            background-color: #ffffff;
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

        /* High-Contrast Colors & Badges */
        .text-muted {
            color: #475569 !important;
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
        .badge {
            font-weight: 600;
        }
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

        .badge.bg-light,
        .badge.bg-white,
        .badge.text-bg-light {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
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

        /* SIDEBAR ARCHITECTURE */
        #app-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        #app-sidebar {
            width: 260px;
            min-width: 260px;
            background-color: #ffffff;
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            z-index: 1030;
            transition: transform 0.25s ease-in-out;
        }

        #app-main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f8fafc;
        }

        .sidebar-header {
            height: 64px;
            min-height: 64px;
            padding: 0 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-nav-container {
            flex: 1;
            overflow-y: auto;
            padding: 0.75rem 0.85rem;
        }

        .sidebar-heading {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #94a3b8;
            padding: 0.75rem 0.65rem 0.35rem 0.65rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.52rem 0.75rem;
            border-radius: 8px;
            color: #334155;
            font-size: 0.86rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
            margin-bottom: 2px;
        }

        .sidebar-link:hover {
            background-color: #f1f5f9;
            color: #0f172a;
        }

        .sidebar-link.active {
            background-color: #4f46e5;
            color: #ffffff !important;
            box-shadow: 0 2px 5px rgba(79, 70, 229, 0.25);
        }

        .sidebar-link .sidebar-icon {
            font-size: 1.1rem;
            width: 22px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            flex-shrink: 0;
        }

        .sidebar-footer {
            padding: 0.85rem 1.25rem;
            border-top: 1px solid #f1f5f9;
            background-color: #fafbfd;
        }

        .app-topbar {
            height: 64px;
            min-height: 64px;
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0 1.5rem;
            position: sticky;
            top: 0;
            z-index: 1020;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        .shadow-2xs {
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .gap-2\.5 {
            gap: 0.625rem !important;
        }

        .px-2\.5 {
            padding-left: 0.625rem !important;
            padding-right: 0.625rem !important;
        }

        .app-body-content {
            flex: 1;
            padding: 1.5rem;
        }

        /* Mobile Offcanvas overlay */
        @media (max-width: 991.98px) {
            #app-sidebar {
                position: fixed;
                left: 0;
                top: 0;
                bottom: 0;
                transform: translateX(-100%);
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            }

            #app-sidebar.show {
                transform: translateX(0);
            }

            .sidebar-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(15, 23, 42, 0.5);
                z-index: 1025;
                display: none;
            }

            .sidebar-backdrop.show {
                display: block;
            }

            .app-body-content {
                padding: 1rem;
            }
        }
    </style>
    @yield('styles')
</head>
<body>
    <div id="app-layout">
        <!-- Backdrop for mobile menu -->
        <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

        <!-- SIDEBAR NAVIGATION -->
        <aside id="app-sidebar">
            @php
                $navSchool = $currentSchool ?? (\App\Models\School::where('school_id', session('active_school_id') ?? (auth()->check() ? auth()->user()->school_id : null))->first() ?? \App\Models\School::first());
            @endphp

            <!-- Sidebar Header & School Brand -->
            <div class="sidebar-header">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none min-w-0" style="gap: 10px;" title="{{ $navSchool->school_name ?? 'School Reporting' }}">
                    @if($navSchool && $navSchool->hasCustomLogo())
                        <img src="{{ $navSchool->getLogoBase64() ?? $navSchool->getLogoUrl() }}" alt="{{ $navSchool->school_name }}" class="rounded-2 border bg-white p-1 shadow-2xs flex-shrink-0" style="height: 38px; width: 38px; object-fit: contain;">
                    @else
                        <span class="navbar-brand-badge flex-shrink-0">{{ $navSchool ? substr($navSchool->school_code, 0, 2) : 'SR' }}</span>
                    @endif
                    <div class="d-flex flex-column overflow-hidden">
                        <span class="fw-bold text-dark lh-sm text-truncate" style="font-size: 0.92rem; letter-spacing: -0.2px;">
                            {{ $navSchool ? $navSchool->school_name : 'School Reporting' }}
                        </span>
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge bg-indigo-subtle text-indigo px-1 py-0" style="font-size: 0.62rem;">{{ $navSchool ? $navSchool->school_code : 'HUB' }} Tenant</span>
                            <span class="text-muted" style="font-size: 0.68rem;">• Portal</span>
                        </div>
                    </div>
                </a>
                <button type="button" class="btn-close d-lg-none ms-2" id="btnCloseSidebar" aria-label="Close"></button>
            </div>

            <!-- Navigation Links Organized by Functionality -->
            <nav class="sidebar-nav-container">
                <!-- 1. OVERVIEW -->
                <div class="sidebar-heading">Overview</div>
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="sidebar-icon">📊</span>
                    <span>Dashboard</span>
                </a>

                <!-- 2. REPORTING & RESULTS -->
                <div class="sidebar-heading mt-2">Reports &amp; Results</div>
                <a href="{{ route('reports.generator') }}" class="sidebar-link {{ request()->routeIs('reports.*') && !request()->routeIs('reports.broadsheet*') ? 'active' : '' }}">
                    <span class="sidebar-icon">📈</span>
                    <span>Reports Studio</span>
                </a>
                <a href="{{ route('reports.broadsheet') }}" class="sidebar-link {{ request()->routeIs('reports.broadsheet*') ? 'active' : '' }}">
                    <span class="sidebar-icon">📋</span>
                    <span>Master Broadsheet</span>
                </a>

                <!-- 3. MARKS & STUDENTS -->
                <div class="sidebar-heading mt-2">Marks &amp; Students</div>
                <a href="{{ route('config.students.index') }}" class="sidebar-link {{ request()->routeIs('config.students*') ? 'active' : '' }}">
                    <span class="sidebar-icon">👥</span>
                    <span>Students Directory</span>
                </a>
                <a href="{{ route('attendance.index') }}" class="sidebar-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">📅</span>
                    <span>Attendance Register</span>
                </a>
                <a href="{{ route('ingestion.index') }}" class="sidebar-link {{ request()->routeIs('ingestion.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">📥</span>
                    <span>Excel Marksheets</span>
                </a>

                <!-- 4. ACADEMIC STRUCTURE -->
                <div class="sidebar-heading mt-2">Academic Structure</div>
                <a href="{{ route('config.levels') }}" class="sidebar-link {{ request()->routeIs('config.levels*') || request()->routeIs('config.classes*') ? 'active' : '' }}">
                    <span class="sidebar-icon">🏛️</span>
                    <span>Levels &amp; Classes</span>
                </a>
                <a href="{{ route('config.class_subjects') }}" class="sidebar-link {{ request()->routeIs('config.class_subjects*') || request()->routeIs('config.subjects*') ? 'active' : '' }}">
                    <span class="sidebar-icon">🏫</span>
                    <span>Class Subjects</span>
                </a>
                <a href="{{ route('config.grading') }}" class="sidebar-link {{ request()->routeIs('config.grading*') || request()->routeIs('grading.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">🎓</span>
                    <span>Grading &amp; Rules</span>
                </a>
                <a href="{{ route('config.attendance') }}" class="sidebar-link {{ request()->routeIs('config.attendance*') ? 'active' : '' }}">
                    <span class="sidebar-icon">📅</span>
                    <span>Attendance Policies</span>
                </a>

                <!-- 5. SETTINGS & ADMINISTRATION -->
                <div class="sidebar-heading mt-2">Settings &amp; Admin</div>
                <a href="{{ route('school.profile') }}" class="sidebar-link {{ request()->routeIs('school.profile*') ? 'active' : '' }}">
                    <span class="sidebar-icon">🖼️</span>
                    <span>School Logo &amp; Profile</span>
                </a>

                @auth
                    @if(auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.schools.index') }}" class="sidebar-link {{ request()->routeIs('admin.schools.*') ? 'active' : '' }}">
                            <span class="sidebar-icon">🏢</span>
                            <span>Manage Schools</span>
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <span class="sidebar-icon">👤</span>
                            <span>User Directory</span>
                        </a>
                    @endif
                @endauth
            </nav>

            <!-- Sidebar Footer -->
            <div class="sidebar-footer">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success-subtle text-success p-1 rounded-circle" style="width: 8px; height: 8px; display: inline-block;"></span>
                        <small class="text-muted fw-semibold" style="font-size: 0.72rem;">Online</small>
                    </div>
                    <small class="text-muted font-monospace" style="font-size: 0.68rem;">v2.6 Multi-Tenant</small>
                </div>
            </div>
        </aside>

        <!-- MAIN PAGE WRAPPER -->
        <div id="app-main">
            <!-- Topbar Header -->
            <header class="app-topbar">
                <div class="d-flex align-items-center" style="gap: 12px;">
                    <!-- Mobile Sidebar Toggle -->
                    <button type="button" class="btn btn-sm btn-light border d-lg-none" id="btnSidebarToggle" title="Toggle Navigation Menu">
                        <span style="font-size: 1.1rem; line-height: 1;">☰</span>
                    </button>
                    <nav aria-label="breadcrumb" class="d-none d-sm-block">
                        <ol class="breadcrumb mb-0 align-items-center" style="font-size: 0.85rem;">
                            <li class="breadcrumb-item text-muted">
                                <a href="{{ route('dashboard') }}" class="text-decoration-none text-muted d-inline-flex align-items-center gap-1.5">
                                    @if($navSchool && $navSchool->hasCustomLogo())
                                        <img src="{{ $navSchool->getLogoBase64() ?? $navSchool->getLogoUrl() }}" alt="{{ $navSchool->school_name }}" class="rounded bg-white p-0.5 border shadow-2xs flex-shrink-0" style="height: 18px; width: 18px; object-fit: contain;">
                                    @else
                                        <span>🏛️</span>
                                    @endif
                                    <span>{{ $navSchool->school_name ?? 'School' }}</span>
                                </a>
                            </li>
                            <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">
                                @yield('title', 'Workspace')
                            </li>
                        </ol>
                    </nav>
                </div>

                <!-- Topbar Right Controls -->
                <div class="d-flex align-items-center" style="gap: 16px;">
                    @auth
                        @if(auth()->user()->isSuperAdmin())
                            <div class="d-none d-md-flex align-items-center gap-2">
                                <label class="form-label mb-0 text-muted small fw-semibold text-nowrap">🌍 Active School:</label>
                                <select class="form-select form-select-sm fw-bold border-primary" style="max-width: 240px;" onchange="window.location.href='{{ url('/admin/schools/switch') }}/' + this.value">
                                    @foreach($allSchools ?? [\App\Models\School::first()] as $sch)
                                        <option value="{{ $sch->school_id }}" {{ (session('active_school_id') ?? $currentSchool->school_id ?? '') === $sch->school_id ? 'selected' : '' }}>
                                            🏫 {{ $sch->school_name }} ({{ $sch->school_code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <!-- User Profile & Sign Out -->
                        <div class="d-flex align-items-center ps-3 border-start" style="gap: 14px;">
                            <div class="text-end d-none d-sm-flex flex-column justify-content-center align-items-end lh-sm">
                                <span class="fw-bold text-dark small text-truncate" style="max-width: 160px; font-size: 0.85rem;">
                                    {{ auth()->user()->name }}
                                </span>
                                <span class="badge {{ auth()->user()->isSuperAdmin() ? 'bg-dark text-white' : 'bg-primary text-white' }} mt-1 px-2 py-0.5 rounded-pill" style="font-size: 0.65rem; letter-spacing: 0.3px;">
                                    {{ auth()->user()->isSuperAdmin() ? '👑 SUPER ADMIN' : '🏫 SCHOOL ADMIN' }}
                                </span>
                            </div>
                            <form action="{{ route('logout') }}" method="POST" class="m-0 p-0 flex-shrink-0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center px-2.5 py-1 fw-semibold shadow-2xs" style="font-size: 0.82rem; border-radius: 8px; gap: 6px; border-color: #fca5a5;" title="Sign out of system">
                                    <span>🚪</span>
                                    <span>Exit</span>
                                </button>
                            </form>
                        </div>
                    @else
                        <!-- Guest Mode -->
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-muted d-none d-sm-inline">Viewing as Guest</span>
                            <a href="{{ route('login') }}" class="btn btn-sm btn-primary fw-bold px-3">
                                Sign In →
                            </a>
                        </div>
                    @endauth
                </div>
            </header>

            <!-- Main Page Content Body -->
            <main class="app-body-content">
                <!-- Flash Notifications -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show small py-2 px-3 mb-4 rounded-3 border-0 shadow-2xs" role="alert">
                        <strong>✓ Success:</strong> {{ session('success') }}
                        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show small py-2 px-3 mb-4 rounded-3 border-0 shadow-2xs" role="alert">
                        <strong>⚠️ Notice:</strong> {{ session('error') }}
                        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show small py-2 px-3 mb-4 rounded-3 border-0 shadow-2xs" role="alert">
                        {{ session('info') }}
                        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnToggle = document.getElementById('btnSidebarToggle');
            const btnClose = document.getElementById('btnCloseSidebar');
            const sidebar = document.getElementById('app-sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');

            function toggleSidebar() {
                if (sidebar && backdrop) {
                    sidebar.classList.toggle('show');
                    backdrop.classList.toggle('show');
                }
            }

            if (btnToggle) btnToggle.addEventListener('click', toggleSidebar);
            if (btnClose) btnClose.addEventListener('click', toggleSidebar);
            if (backdrop) backdrop.addEventListener('click', toggleSidebar);
        });
    </script>
    @yield('scripts')
</body>
</html>
