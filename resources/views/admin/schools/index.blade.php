@extends('layouts.app')

@section('title', 'All Schools Management — Super Admin')

@section('content')
<!-- Header & Top Statistics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-custom p-3">
            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Total Individual Schools</span>
            <h3 class="fw-bold my-1 text-primary">{{ $schools->count() }}</h3>
            <small class="text-success fw-semibold">✓ Standalone Tenant Environments</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3">
            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Total Enrolled Students</span>
            <h3 class="fw-bold my-1 text-primary">{{ $schools->sum('students_count') }}</h3>
            <small class="text-muted">Across all schools</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3">
            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Active Classes</span>
            <h3 class="fw-bold my-1 text-primary">{{ $schools->sum('classes_count') }}</h3>
            <small class="text-muted">Class cohorts & sections</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3">
            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">School Staff & Admins</span>
            <h3 class="fw-bold my-1 text-success">{{ $schools->sum('users_count') }} Users</h3>
            <small class="text-muted">Assigned administrators</small>
        </div>
    </div>
</div>

<div class="card card-custom p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom pb-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary px-2 py-1 small">Super Admin Console</span>
                <h4 class="fw-bold mb-0">Individual Schools & Institutional Tenants</h4>
            </div>
            <p class="text-muted small mb-0">Create new individual schools with fully isolated grading templates, attendance rules, and staff accounts.</p>
        </div>

        <button class="btn btn-primary px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#createSchoolModal">
            + Provision New School
        </button>
    </div>

    <!-- School Cards Grid -->
    <div class="row g-4">
        @foreach($schools as $school)
        <div class="col-lg-6">
            <div class="card h-100 rounded-3 border {{ $activeSchoolId === $school->school_id ? 'border-primary border-2 shadow-sm bg-light bg-opacity-25' : 'border-slate-200 bg-white' }}">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-primary px-2 py-1 small font-monospace">{{ $school->school_code }}</span>
                                <span class="badge bg-secondary-subtle text-secondary small ms-1">{{ $school->school_id }}</span>
                            </div>
                            <span class="badge {{ $activeSchoolId === $school->school_id ? 'bg-success text-white' : 'bg-light text-muted border' }} px-2 py-1 small">
                                {{ $activeSchoolId === $school->school_id ? '● CURRENT WORKSPACE' : 'INDEPENDENT TENANT' }}
                            </span>
                        </div>

                        <div class="d-flex align-items-center gap-3 mb-2">
                            <a href="{{ route('school.profile') }}?school_id={{ $school->school_id }}" title="Click to manage Logo">
                                <img src="{{ $school->getLogoUrl() }}?v={{ time() }}" alt="{{ $school->school_name }}" class="rounded-3 border bg-white p-1 shadow-2xs" style="height: 54px; width: auto; max-width: 90px; object-fit: contain;">
                            </a>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">{{ $school->school_name }}</h5>
                                <p class="small text-muted mb-0">
                                    Principal: <strong>{{ $school->principal_name ?? 'Academic Director' }}</strong> • {{ $school->city ?? 'Central' }}, {{ $school->country }}
                                </p>
                            </div>
                        </div>

                        <!-- Operational Counters -->
                        <div class="row g-2 mb-3 text-center small">
                            <div class="col-4">
                                <div class="p-2 border rounded bg-white">
                                    <span class="text-muted d-block font-monospace">STUDENTS</span>
                                    <strong class="fs-6 text-primary">{{ $school->students_count }}</strong>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 border rounded bg-white">
                                    <span class="text-muted d-block font-monospace">CLASSES</span>
                                    <strong class="fs-6 text-dark">{{ $school->classes_count }}</strong>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 border rounded bg-white">
                                    <span class="text-muted d-block font-monospace">SCALE</span>
                                    <strong class="fs-6 text-success">{{ $school->active_scale_model }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Administrator Info -->
                        @if($school->adminUser)
                            <div class="p-2 bg-light rounded border mb-3 small d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">ASSIGNED SCHOOL ADMIN</span>
                                    <strong class="text-dark">{{ $school->adminUser->name }}</strong>
                                </div>
                                <code class="text-primary">{{ $school->adminUser->email }}</code>
                            </div>
                        @endif
                    </div>

                    <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold py-1 px-2" data-bs-toggle="modal" data-bs-target="#editSchoolModal_{{ $school->id }}">
                            ✏️ Edit &amp; Logo
                        </button>
                        <a href="{{ route('admin.schools.switch', $school->school_id) }}" 
                           class="btn btn-sm {{ $activeSchoolId === $school->school_id ? 'btn-success' : 'btn-outline-primary' }} fw-bold px-3">
                            {{ $activeSchoolId === $school->school_id ? '✓ Working in School' : 'Enter School Workspace →' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Edit School & Logo -->
        <div class="modal fade" id="editSchoolModal_{{ $school->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content border-0 shadow">
                    <form action="{{ route('admin.schools.update', $school->school_id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header bg-dark text-white p-3">
                            <h5 class="modal-title fw-bold">✏️ Edit School Profile &amp; Logo — {{ $school->school_name }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body p-4">
                            <!-- Logo Preview & Upload Section -->
                            <div class="p-3 bg-light rounded-3 border mb-4 d-flex align-items-center gap-3">
                                <div class="p-2 bg-white rounded-3 border shadow-2xs">
                                    <img src="{{ $school->getLogoUrl() }}?v={{ time() }}" alt="{{ $school->school_name }}" style="max-height: 60px; max-width: 100px; object-fit: contain;">
                                </div>
                                <div class="flex-grow-1">
                                    <label class="form-label small fw-bold text-dark mb-1">Upload New Logo for this School</label>
                                    <input type="file" name="logo" class="form-control form-control-sm" accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp">
                                    <small class="text-muted d-block mt-1">Replaces existing logo across all report cards, broardsheets, and Jasper packages.</small>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold text-dark">School Name <span class="text-danger">*</span></label>
                                    <input type="text" name="school_name" class="form-control" value="{{ $school->school_name }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">School Code <span class="text-danger">*</span></label>
                                    <input type="text" name="school_code" class="form-control font-monospace" value="{{ $school->school_code }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Principal / Director Name</label>
                                    <input type="text" name="principal_name" class="form-control" value="{{ $school->principal_name }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Grading Scale Model</label>
                                    <select name="active_scale_model" class="form-select">
                                        <option value="PERCENTAGE_100" {{ $school->active_scale_model === 'PERCENTAGE_100' ? 'selected' : '' }}>Percentage (0 - 100%) Standard</option>
                                        <option value="GPA_4_0" {{ $school->active_scale_model === 'GPA_4_0' ? 'selected' : '' }}>Letter Grade + GPA (4.0 Scale)</option>
                                        <option value="FRENCH_20" {{ $school->active_scale_model === 'FRENCH_20' ? 'selected' : '' }}>French Baccalaureate (0 - 20 pts)</option>
                                        <option value="IGCSE_9_1" {{ $school->active_scale_model === 'IGCSE_9_1' ? 'selected' : '' }}>British IGCSE (9 to 1 Scale)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Contact Email</label>
                                    <input type="email" name="contact_email" class="form-control" value="{{ $school->contact_email }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Contact Phone</label>
                                    <input type="text" name="contact_phone" class="form-control" value="{{ $school->contact_phone }}">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold text-dark">Campus Address</label>
                                    <input type="text" name="address" class="form-control" value="{{ $school->address }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">City / District</label>
                                    <input type="text" name="city" class="form-control" value="{{ $school->city }}">
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer bg-light p-3">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary fw-bold px-4">
                                💾 Save Changes &amp; Logo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Modal: Provision New School -->
<div class="modal fade" id="createSchoolModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.schools.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-primary text-white p-3">
                    <h5 class="modal-title fw-bold">🏫 Provision New Individual School</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <p class="small text-muted mb-4">
                        Creating a school will automatically provision an individual tenant environment with default attendance policies, standard grading templates (Case 1, Case 2, Case 3), divisions, and an administrator account.
                    </p>

                    <!-- Section A: School Profile -->
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">1. School Information</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">School Name</label>
                            <input type="text" name="school_name" class="form-control" placeholder="e.g. Oakridge International Academy" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">School Code (Unique)</label>
                            <input type="text" name="school_code" class="form-control font-monospace" placeholder="e.g. OAK" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Principal / Director Name</label>
                            <input type="text" name="principal_name" class="form-control" placeholder="Dr. Jonathan Reed">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Primary Grading Scale</label>
                            <select name="active_scale_model" class="form-select">
                                <option value="PERCENTAGE_100" selected>Percentage (0 - 100%) Standard</option>
                                <option value="GPA_4_0">Letter Grade + GPA (4.0 Scale)</option>
                                <option value="FRENCH_20">French Baccalaureate (0 - 20 pts)</option>
                                <option value="IGCSE_9_1">British IGCSE (9 to 1 Scale)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contact Email</label>
                            <input type="email" name="contact_email" class="form-control" placeholder="admin@oakridge.edu">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">City / District</label>
                            <input type="text" name="city" class="form-control" placeholder="New York">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Official School Logo (Optional)</label>
                            <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp">
                            <div class="form-text small">Upload transparent PNG, SVG, or JPG (Max 5MB). Automatically displayed in report headers and cards.</div>
                        </div>
                    </div>

                    <!-- Section B: Initial School Administrator -->
                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3">2. Dedicated School Administrator Account</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Administrator Name</label>
                            <input type="text" name="admin_name" class="form-control" placeholder="Maria Santos" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Admin Email (Login)</label>
                            <input type="email" name="admin_email" class="form-control" placeholder="registrar@oakridge.edu" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Admin Password</label>
                            <input type="password" name="admin_password" class="form-control" value="password123" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        ✓ Create & Provision Individual School
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
