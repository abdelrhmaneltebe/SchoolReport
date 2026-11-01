<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — School Reporting Department Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 460px;
            width: 100%;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        }
        .brand-badge {
            width: 48px;
            height: 48px;
            background: #4f46e5;
            color: white;
            font-weight: 800;
            font-size: 1.25rem;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body class="p-3">
    <div class="login-card p-4 p-md-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <a href="{{ route('home') }}" class="text-decoration-none small text-muted">
                ← Back to Home
            </a>
            <a href="{{ route('home') }}#demo" class="text-decoration-none small text-primary fw-semibold">
                Request Demo
            </a>
        </div>
        <div class="text-center mb-4">
            <div class="brand-badge mb-2">SR</div>
            <h4 class="fw-bold mb-1">School Reporting Hub</h4>
            <p class="text-muted small mb-0">Multi-School Management & Departmental Access</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success py-2 px-3 small rounded-3 mb-3">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3">{{ session('error') }}</div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-bold text-dark">Email Address</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                       value="{{ old('email', 'admin@schoolreport.com') }}" required autofocus>
                @error('email')
                    <div class="invalid-feedback small">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="form-label small fw-bold text-dark mb-0">Password</label>
                    <span class="small text-muted">Default: <code>password123</code></span>
                </div>
                <input type="password" name="password" class="form-control mt-1" value="password123" required>
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" name="remember" class="form-check-input" id="remember" checked>
                <label class="form-check-label small text-muted" for="remember">Keep me signed in</label>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm mb-3">
                Sign In to Dashboard →
            </button>
        </form>

        <!-- Quick Demo Accounts -->
        <div class="border-top pt-3 text-center">
            <span class="small text-muted fw-semibold d-block mb-2">⚡ Quick 1-Click Role Login:</span>
            <div class="d-grid gap-2">
                <a href="{{ route('quick_login', 'SUPER_ADMIN') }}" class="btn btn-sm btn-outline-dark fw-semibold text-start px-3 py-2">
                    👑 <strong>Super Administrator</strong> (Create & Manage All Schools)
                </a>
                <a href="{{ route('quick_login', 'SCHOOL_ADMIN') }}" class="btn btn-sm btn-outline-primary fw-semibold text-start px-3 py-2">
                    🏫 <strong>School Administrator</strong> (Individual School Tenant)
                </a>
            </div>
        </div>
    </div>
</body>
</html>
