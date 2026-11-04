@extends('layouts.app')

@section('title', 'User & Access Management — Super Admin')

@section('content')
<div class="card card-custom p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom pb-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary px-2 py-1 small">Security & RBAC</span>
                <h4 class="fw-bold mb-0">System & School User Directory</h4>
            </div>
            <p class="text-muted small mb-0">
                Manage Super Administrators with global access and School Administrators assigned to single or multiple institutional campuses.
            </p>
        </div>

        <button class="btn btn-primary px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
            + Create New User
        </button>
    </div>

    <!-- Users Table -->
    <div class="table-responsive border rounded-3">
        <table class="table table-hover align-middle mb-0 text-center small">
            <thead class="table-light">
                <tr>
                    <th class="text-start">User / Credentials</th>
                    <th>Role</th>
                    <th class="text-start">Permitted School Access</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                @php
                    $assignedSchools = $u->accessibleSchools();
                @endphp
                <tr>
                    <td class="text-start">
                        <div class="fw-bold text-dark fs-6">{{ $u->name }}</div>
                        <code class="text-muted">{{ $u->email }}</code>
                    </td>
                    <td>
                        @if($u->role === 'SUPER_ADMIN')
                            <span class="badge bg-dark text-white px-2.5 py-1">👑 Super Administrator</span>
                        @elseif($u->role === 'SCHOOL_ADMIN')
                            <span class="badge bg-primary text-white px-2.5 py-1">🏫 School Administrator</span>
                        @else
                            <span class="badge bg-info text-dark px-2.5 py-1">🎓 Faculty / Teacher</span>
                        @endif
                    </td>
                    <td class="text-start">
                        @if($u->role === 'SUPER_ADMIN')
                            <div class="d-flex align-items-center gap-1.5">
                                <span class="badge bg-dark-subtle text-dark border px-2 py-1">
                                    ⭐ Global Access (All {{ $schools->count() }} Schools)
                                </span>
                            </div>
                            <small class="text-muted">Unrestricted system-wide permission</small>
                        @else
                            @if($assignedSchools->isNotEmpty())
                                <div class="d-flex flex-wrap gap-1 align-items-center mb-1">
                                    @foreach($assignedSchools as $sch)
                                        <span class="badge bg-light text-dark border px-2 py-1 small">
                                            🏛️ {{ $sch->school_name }} <span class="text-primary font-monospace">({{ $sch->school_code }})</span>
                                        </span>
                                    @endforeach
                                </div>
                                <small class="text-muted fw-semibold">
                                    {{ $assignedSchools->count() }} {{ Str::plural('school', $assignedSchools->count()) }} assigned
                                </small>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                    ⚠️ No school assigned
                                </span>
                            @endif
                        @endif
                    </td>
                    <td>{{ $u->phone ?? '--' }}</td>
                    <td>
                        <span class="badge {{ $u->status === 'ACTIVE' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary' }} px-2 py-1">
                            {{ $u->status }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex justify-content-center align-items-center gap-1">
                            <!-- Edit Button -->
                            <button type="button" 
                                    class="btn btn-outline-primary btn-sm py-1 px-2 fw-semibold" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editUserModal{{ $u->id }}"
                                    title="Edit user details & assigned schools">
                                ✏️ Edit
                            </button>

                            @if($u->id !== auth()->id())
                                <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete user {{ $u->name }}?');" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2 fw-semibold" title="Delete account">
                                        🗑️ Delete
                                    </button>
                                </form>
                            @else
                                <span class="badge bg-light text-muted border py-1 px-2 font-monospace">You</span>
                            @endif
                        </div>
                    </td>
                </tr>

                <!-- Modal: Edit User & School Assignments -->
                <div class="modal fade text-start" id="editUserModal{{ $u->id }}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content border-0 shadow">
                            <form action="{{ route('admin.users.update_post', $u->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-header bg-dark text-white p-3">
                                    <h5 class="modal-title fw-bold">✏️ Edit User &amp; School Assignments: {{ $u->name }}</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body p-4">
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Full Name</label>
                                            <input type="text" name="name" class="form-control" value="{{ $u->name }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Email Address</label>
                                            <input type="email" name="email" class="form-control" value="{{ $u->email }}" required>
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Role &amp; Privilege Level</label>
                                            <select name="role" class="form-select fw-bold" id="editRoleSelect{{ $u->id }}" onchange="toggleEditSchoolField({{ $u->id }}, this.value)" required>
                                                <option value="SCHOOL_ADMIN" {{ $u->role === 'SCHOOL_ADMIN' ? 'selected' : '' }}>
                                                    🏫 School Administrator (Assigned Schools)
                                                </option>
                                                <option value="SUPER_ADMIN" {{ $u->role === 'SUPER_ADMIN' ? 'selected' : '' }}>
                                                    👑 Super Administrator (All Schools)
                                                </option>
                                                <option value="TEACHER" {{ $u->role === 'TEACHER' ? 'selected' : '' }}>
                                                    🎓 Teacher / Faculty
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Account Status</label>
                                            <select name="status" class="form-select">
                                                <option value="ACTIVE" {{ $u->status === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                                                <option value="INACTIVE" {{ $u->status === 'INACTIVE' ? 'selected' : '' }}>Inactive / Suspended</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Contact Phone</label>
                                            <input type="text" name="phone" class="form-control" value="{{ $u->phone }}">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">New Password (Leave blank to keep unchanged)</label>
                                        <input type="password" name="password" class="form-control" placeholder="Optional: Enter new password (min. 6 chars)">
                                    </div>

                                    <!-- Multi-School Assignment Box for Edit -->
                                    <div class="mb-3" id="editSchoolContainer{{ $u->id }}" style="{{ $u->role === 'SUPER_ADMIN' ? 'display: none;' : '' }}">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label small fw-bold mb-0">
                                                🏫 Assign to Schools <span class="text-danger">*</span> 
                                                <small class="text-muted fw-normal">(Select one or more schools this admin manages)</small>
                                            </label>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-secondary py-0 px-2 small" onclick="selectAllSchools('editSchoolCheck{{ $u->id }}', true)">
                                                    Select All
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary py-0 px-2 small" onclick="selectAllSchools('editSchoolCheck{{ $u->id }}', false)">
                                                    Clear
                                                </button>
                                            </div>
                                        </div>

                                        <div class="card p-3 bg-light border rounded-3" style="max-height: 220px; overflow-y: auto;">
                                            @php
                                                $userSchoolIds = $u->schools->pluck('school_id')->toArray();
                                                if (empty($userSchoolIds) && $u->school_id) {
                                                    $userSchoolIds = [$u->school_id];
                                                }
                                            @endphp
                                            @foreach($schools as $s)
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input editSchoolCheck{{ $u->id }}" 
                                                           type="checkbox" 
                                                           name="school_ids[]" 
                                                           value="{{ $s->school_id }}" 
                                                           id="edit_sch_{{ $u->id }}_{{ $s->school_id }}"
                                                           {{ in_array($s->school_id, $userSchoolIds) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold text-dark" for="edit_sch_{{ $u->id }}_{{ $s->school_id }}">
                                                        🏛️ {{ $s->school_name }}
                                                        <span class="badge bg-white text-secondary border font-monospace ms-1">{{ $s->school_code }}</span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Super Admin Global Access Notice -->
                                    <div class="alert alert-info py-2 px-3 small mb-0" id="editSuperNotice{{ $u->id }}" style="{{ $u->role === 'SUPER_ADMIN' ? '' : 'display: none;' }}">
                                        👑 <strong>Super Administrator:</strong> Has unrestricted global access across all existing and future schools. No specific school binding is required.
                                    </div>
                                </div>

                                <div class="modal-footer bg-light p-3">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary fw-bold px-4">💾 Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Create New User -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white p-3">
                    <h5 class="modal-title fw-bold">👤 Create User Account &amp; Assign Schools</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Dr. Sarah Johnson" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="sarah.johnson@school.edu" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Initial Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" value="password123" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contact Phone (Optional)</label>
                            <input type="text" name="phone" class="form-control" placeholder="+1 (555) 000-0000">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Role &amp; Permission Level <span class="text-danger">*</span></label>
                        <select name="role" class="form-select fw-bold" id="createRoleSelect" onchange="toggleCreateSchoolField(this.value)" required>
                            <option value="SCHOOL_ADMIN" selected>🏫 School Administrator (Assigned to one or more schools)</option>
                            <option value="SUPER_ADMIN">👑 Super Administrator (Full Global Access to All Schools)</option>
                            <option value="TEACHER">🎓 Teacher / Faculty</option>
                        </select>
                    </div>

                    <!-- Multi-School Assignment for New User -->
                    <div class="mb-3" id="createSchoolContainer">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label small fw-bold mb-0">
                                🏫 Assign to Schools <span class="text-danger">*</span> 
                                <small class="text-muted fw-normal">(Select one or multiple schools)</small>
                            </label>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 small" onclick="selectAllSchools('createSchoolCheck', true)">
                                    Select All
                                </button>
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 small" onclick="selectAllSchools('createSchoolCheck', false)">
                                    Clear
                                </button>
                            </div>
                        </div>

                        <div class="card p-3 bg-light border rounded-3" style="max-height: 220px; overflow-y: auto;">
                            @foreach($schools as $index => $s)
                                <div class="form-check mb-2">
                                    <input class="form-check-input createSchoolCheck" 
                                           type="checkbox" 
                                           name="school_ids[]" 
                                           value="{{ $s->school_id }}" 
                                           id="create_sch_{{ $s->school_id }}"
                                           {{ $index === 0 ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold text-dark" for="create_sch_{{ $s->school_id }}">
                                        🏛️ {{ $s->school_name }}
                                        <span class="badge bg-white text-secondary border font-monospace ms-1">{{ $s->school_code }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Super Admin Info Note -->
                    <div class="alert alert-info py-2 px-3 small mb-0" id="createSuperNotice" style="display: none;">
                        👑 <strong>Super Administrator:</strong> Has unrestricted global access across all current and future schools. No specific school binding is required.
                    </div>
                </div>

                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Create User Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleCreateSchoolField(role) {
    const schoolBox = document.getElementById('createSchoolContainer');
    const notice = document.getElementById('createSuperNotice');
    if (role === 'SUPER_ADMIN') {
        schoolBox.style.display = 'none';
        notice.style.display = 'block';
    } else {
        schoolBox.style.display = 'block';
        notice.style.display = 'none';
    }
}

function toggleEditSchoolField(userId, role) {
    const schoolBox = document.getElementById('editSchoolContainer' + userId);
    const notice = document.getElementById('editSuperNotice' + userId);
    if (role === 'SUPER_ADMIN') {
        schoolBox.style.display = 'none';
        notice.style.display = 'block';
    } else {
        schoolBox.style.display = 'block';
        notice.style.display = 'none';
    }
}

function selectAllSchools(className, checked) {
    document.querySelectorAll('.' + className).forEach(cb => {
        cb.checked = checked;
    });
}
</script>
@endsection
