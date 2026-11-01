@extends('layouts.app')

@section('title', 'School Profile & Official Logo — ' . $activeSchool->school_name)

@section('content')
<div class="container-fluid px-0">
    <!-- Header Banner -->
    <div class="card border rounded-3 p-4 mb-4 bg-white shadow-xs">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <img src="{{ $activeSchool->getLogoBase64() ?? $activeSchool->getLogoUrl() }}" alt="{{ $activeSchool->school_name }}" class="rounded-3 border bg-white p-1 shadow-2xs" style="height: 60px; width: auto; max-width: 120px; object-fit: contain;">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-primary text-white px-2 py-1 small">Institutional Branding</span>
                        <span class="badge bg-light text-secondary border font-monospace">{{ $activeSchool->school_code }}</span>
                        <span class="badge bg-secondary-subtle text-secondary small">{{ $activeSchool->school_id }}</span>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark">🏫 {{ $activeSchool->school_name }} Profile &amp; Logo</h4>
                    <p class="text-muted small mb-0">
                        Upload and manage your official school logo. This logo is automatically rendered on <strong>Report Cards</strong>, <strong>Broadsheets</strong>, <strong>Analytics Dashboards</strong>, and injected into <strong>Jasper Report Templates</strong> (<code>P_LOGO_PATH</code>).
                    </p>
                </div>
            </div>

            @if(auth()->check() && auth()->user()->isSuperAdmin() && $allSchools->count() > 1)
                <!-- Super Admin School Switcher -->
                <div class="d-flex align-items-center gap-2 bg-light p-2 rounded-3 border">
                    <span class="small fw-bold text-dark text-nowrap">Switch School:</span>
                    <select class="form-select form-select-sm fw-semibold" onchange="window.location.href='/admin/schools/switch/' + this.value + '?redirect=' + encodeURIComponent(window.location.pathname)">
                        @foreach($allSchools as $s)
                            <option value="{{ $s->school_id }}" {{ $activeSchool->school_id === $s->school_id ? 'selected' : '' }}>
                                🏫 {{ $s->school_name }} ({{ $s->school_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <!-- LEFT: School Logo Management & Live Report Preview -->
        <div class="col-lg-5">
            <!-- Logo Upload Card -->
            <div class="card border rounded-3 p-4 bg-white shadow-xs mb-4">
                <h5 class="fw-bold text-dark border-bottom pb-3 mb-3 d-flex align-items-center justify-content-between">
                    <span>🖼️ Official School Logo</span>
                    @if($activeSchool->hasCustomLogo())
                        <span class="badge bg-success-subtle text-success border border-success-subtle small fw-bold">✓ Custom Logo Active</span>
                    @else
                        <span class="badge bg-light text-muted border small">Default Monogram</span>
                    @endif
                </h5>

                <div class="text-center p-4 bg-light rounded-3 border mb-3">
                    <div class="mb-3 d-flex justify-content-center">
                        <div class="p-3 bg-white rounded-3 border shadow-2xs d-inline-block" style="min-width: 140px; min-height: 140px;">
                            <img id="logoPreviewImg" src="{{ $activeSchool->getLogoBase64() ?? $activeSchool->getLogoUrl() }}" alt="{{ $activeSchool->school_name }}" style="max-height: 120px; max-width: 220px; object-fit: contain;">
                        </div>
                    </div>
                    <div class="small fw-bold text-dark" id="logoStatusText">
                        {{ $activeSchool->hasCustomLogo() ? 'Custom Institutional Emblem Loaded' : 'Dynamic Institutional Monogram (Default)' }}
                    </div>
                    <small class="text-muted d-block mt-1">Recommended format: Transparent PNG, SVG, or high-res JPG (Max 5MB)</small>
                </div>

                <!-- Logo Upload Form -->
                <form action="{{ route('school.profile.update') }}" method="POST" enctype="multipart/form-data" id="logoUploadForm">
                    @csrf
                    <input type="hidden" name="school_id" value="{{ $activeSchool->school_id }}">
                    <input type="hidden" name="school_name" value="{{ $activeSchool->school_name }}">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Select New School Logo Image</label>
                        <input type="file" name="logo" id="inputSchoolLogo" class="form-control" accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp" required onchange="previewSelectedLogo(this)">
                        <div class="form-text small">Accepted formats: <code>PNG</code>, <code>JPG</code>, <code>SVG</code>, <code>WEBP</code>.</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary fw-bold px-3 shadow-xs">
                            📤 Upload &amp; Apply Logo
                        </button>

                        @if($activeSchool->hasCustomLogo())
                            <button type="button" class="btn btn-outline-danger fw-semibold btn-sm" onclick="confirmResetLogo()">
                                🗑️ Remove Custom Logo
                            </button>
                        @endif
                    </div>
                </form>

                @if($activeSchool->hasCustomLogo())
                    <form action="{{ route('school.profile.remove_logo') }}" method="POST" id="formRemoveLogo" class="d-none">
                        @csrf
                        <input type="hidden" name="school_id" value="{{ $activeSchool->school_id }}">
                    </form>
                @endif
            </div>

            <!-- Live Report Card Header Preview -->
            <div class="card border rounded-3 p-4 bg-white shadow-xs">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center justify-content-between">
                    <span>🖨️ Report Card Header Preview</span>
                    <span class="badge bg-light text-secondary border small">Live Rendering</span>
                </h6>
                <p class="small text-muted mb-3">Here is how this logo appears at the top of printed report cards and transcripts:</p>

                <div class="p-3 border rounded-3 bg-white shadow-2xs">
                    <div class="d-flex justify-content-between align-items-center pb-2 border-bottom border-2 border-dark">
                        <div class="d-flex align-items-center gap-3">
                            <img id="liveReportLogo" src="{{ $activeSchool->getLogoBase64() ?? $activeSchool->getLogoUrl() }}" alt="{{ $activeSchool->school_name }}" class="rounded-2 border bg-white p-1" style="height: 52px; width: auto; max-width: 100px; object-fit: contain;">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark font-serif" id="liveReportSchoolName">{{ $activeSchool->school_name }}</h6>
                                <div class="text-muted" style="font-size: 0.7rem;">
                                    School Code: <span class="text-dark font-monospace fw-bold">{{ $activeSchool->school_code }}</span> • Official Achievement Report
                                </div>
                                <div class="text-muted" style="font-size: 0.68rem;">Academic Year: <strong>2026-2027</strong> • Continuous Assessment</div>
                            </div>
                        </div>
                        <div class="text-end d-none d-sm-block">
                            <span class="badge bg-dark text-white px-2 py-1 fw-bold text-uppercase" style="font-size: 0.7rem;">Annual Term</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: School Profile & Institutional Details Form -->
        <div class="col-lg-7">
            <div class="card border rounded-3 p-4 bg-white shadow-xs">
                <h5 class="fw-bold text-dark border-bottom pb-3 mb-4">
                    📝 Institutional Information &amp; Contacts
                </h5>

                <form action="{{ route('school.profile.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="school_id" value="{{ $activeSchool->school_id }}">

                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-dark">School Name <span class="text-danger">*</span></label>
                            <input type="text" name="school_name" class="form-control" value="{{ old('school_name', $activeSchool->school_name) }}" required oninput="document.getElementById('liveReportSchoolName').innerText = this.value">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">School Code</label>
                            <input type="text" class="form-control font-monospace bg-light" value="{{ $activeSchool->school_code }}" readonly title="School Code is fixed by tenant provisioning">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Principal / Head of School</label>
                            <input type="text" name="principal_name" class="form-control" value="{{ old('principal_name', $activeSchool->principal_name) }}" placeholder="e.g. Dr. Arthur Pendelton">
                            <div class="form-text small">Printed in official signature blocks on Report Cards.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Default Grading Scale Model</label>
                            <input type="text" class="form-control bg-light" value="{{ $activeSchool->active_scale_model }}" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Official Contact Email</label>
                            <input type="email" name="contact_email" class="form-control" value="{{ old('contact_email', $activeSchool->contact_email) }}" placeholder="admin@school.edu">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Contact Phone</label>
                            <input type="text" name="contact_phone" class="form-control" value="{{ old('contact_phone', $activeSchool->contact_phone) }}" placeholder="+1 (555) 019-2834">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark">Physical Address / Campus Location</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address', $activeSchool->address) }}" placeholder="124 Education Way, Academic District">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">City / District</label>
                            <input type="text" name="city" class="form-control" value="{{ old('city', $activeSchool->city) }}" placeholder="e.g. Cambridge">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Country</label>
                            <input type="text" name="country" class="form-control" value="{{ old('country', $activeSchool->country ?? 'United States') }}" placeholder="e.g. United States">
                        </div>
                    </div>

                    <!-- Statistics Summary Bar -->
                    <div class="row g-2 p-3 bg-light rounded-3 border mb-4 text-center small">
                        <div class="col-4">
                            <span class="text-muted d-block">STUDENTS ENROLLED</span>
                            <strong class="fs-5 text-primary">{{ $activeSchool->students_count ?? $activeSchool->students()->count() }}</strong>
                        </div>
                        <div class="col-4 border-start border-end">
                            <span class="text-muted d-block">CLASSES &amp; SECTIONS</span>
                            <strong class="fs-5 text-dark">{{ $activeSchool->classes_count ?? $activeSchool->classes()->count() }}</strong>
                        </div>
                        <div class="col-4">
                            <span class="text-muted d-block">ACTIVE STAFF USERS</span>
                            <strong class="fs-5 text-success">{{ $activeSchool->users_count ?? $activeSchool->users()->count() }}</strong>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm">
                            💾 Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function previewSelectedLogo(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();

        reader.onload = function(e) {
            const preview = document.getElementById('logoPreviewImg');
            const liveLogo = document.getElementById('liveReportLogo');
            const status = document.getElementById('logoStatusText');

            if (preview) preview.src = e.target.result;
            if (liveLogo) liveLogo.src = e.target.result;
            if (status) status.innerHTML = `<span class="text-primary fw-bold">✓ Ready to Upload:</span> ${file.name} (${Math.round(file.size / 1024)} KB)`;
        };

        reader.readAsDataURL(file);
    }
}

function confirmResetLogo() {
    if (confirm("Are you sure you want to remove the custom school logo and revert to the default monogram?")) {
        document.getElementById('formRemoveLogo').submit();
    }
}
</script>
@endsection
