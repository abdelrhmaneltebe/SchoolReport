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
            <p class="text-muted small mb-0">Manage Super Administrators, School Administrators, and Faculty accounts across all institutions.</p>
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
                    <th class="text-start">User / Name</th>
                    <th>Role</th>
                    <th class="text-start">Assigned School Tenant</th>
                    <th>Contact Phone</th>
                    <th>Account Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                <tr>
                    <td class="text-start">
                        <div class="fw-bold text-dark">{{ $u->name }}</div>
                        <code class="text-muted">{{ $u->email }}</code>
                    </td>
                    <td>
                        @if($u->role === 'SUPER_ADMIN')
                            <span class="badge bg-dark text-white px-2 py-1">👑 Super Administrator</span>
                        @elseif($u->role === 'SCHOOL_ADMIN')
                            <span class="badge bg-primary text-white px-2 py-1">🏫 School Administrator</span>
                        @else
                            <span class="badge bg-info text-dark px-2 py-1">🎓 Faculty / Teacher</span>
                        @endif
                    </td>
                    <td class="text-start">
                        @if($u->school)
                            <div class="fw-semibold text-dark">{{ $u->school->school_name }}</div>
                            <small class="text-muted font-monospace">{{ $u->school->school_code }} ({{ $u->school->school_id }})</small>
                        @else
                            <span class="badge bg-light text-muted border">Global System (All Schools)</span>
                        @endif
                    </td>
                    <td>{{ $u->phone ?? '--' }}</td>
                    <td>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                            {{ $u->status }}
                        </span>
                    </td>
                    <td>
                        @if($u->id !== auth()->id())
                            <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Delete user {{ $u->name }}?');" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2 fw-semibold">
                                    🗑️ Delete
                                </button>
                            </form>
                        @else
                            <span class="small text-muted font-monospace">Current Session</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Create New User -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white p-3">
                    <h5 class="modal-title fw-bold">👤 Create User Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Jane Doe" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="user@school.edu" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password</label>
                        <input type="password" name="password" class="form-control" value="password123" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Role & Permissions</label>
                        <select name="role" class="form-select" id="roleSelect" onchange="toggleSchoolField(this.value)" required>
                            <option value="SCHOOL_ADMIN" selected>🏫 School Administrator (Bound to 1 School)</option>
                            <option value="SUPER_ADMIN">👑 Super Administrator (All Schools & Provisioning)</option>
                            <option value="TEACHER">🎓 Teacher / Faculty</option>
                        </select>
                    </div>

                    <div class="mb-3" id="schoolSelectContainer">
                        <label class="form-label small fw-bold">Assign to School</label>
                        <select name="school_id" class="form-select">
                            @foreach($schools as $s)
                                <option value="{{ $s->school_id }}">{{ $s->school_name }} ({{ $s->school_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Contact Phone (Optional)</label>
                        <input type="text" name="phone" class="form-control" placeholder="+1 (555) 000-0000">
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
function toggleSchoolField(role) {
    const container = document.getElementById('schoolSelectContainer');
    if (role === 'SUPER_ADMIN') {
        container.style.display = 'none';
    } else {
        container.style.display = 'block';
    }
}
</script>
@endsection
