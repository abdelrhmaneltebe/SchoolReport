@extends('layouts.app')

@section('title', 'Subscription Plans & Feature Management — Super Admin')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card card-custom p-3">
                <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Total Plans</span>
                <h3 class="fw-bold my-1 text-primary">{{ $plans->count() }}</h3>
                <small class="text-success fw-semibold">✓ Ready for School Assignment</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-custom p-3">
                <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Subscribed Schools</span>
                <h3 class="fw-bold my-1 text-primary">{{ $schools->whereNotNull('plan_id')->count() }} of {{ $schools->count() }}</h3>
                <small class="text-muted">On active plan tiers</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-custom p-3">
                <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Default Tier</span>
                <h4 class="fw-bold my-1 text-dark text-truncate">
                    {{ $plans->firstWhere('is_default', true)?->name ?? 'Standard' }}
                </h4>
                <small class="text-muted">Auto-assigned to new schools</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-custom p-3">
                <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">System Features</span>
                <h3 class="fw-bold my-1 text-success">{{ count($availableFeatures) }} Features</h3>
                <small class="text-muted">Configurable plan modules</small>
            </div>
        </div>
    </div>

    <!-- Main Plans Card -->
    <div class="card card-custom p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom pb-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-primary px-2 py-1 small">Super Admin Console</span>
                    <h4 class="fw-bold mb-0">Subscription Plans &amp; School Feature Gates</h4>
                </div>
                <p class="text-muted small mb-0">
                    Define custom subscription plans, select which modules each plan includes, and assign individual schools to their specific tiers.
                </p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#quickAssignSchoolModal">
                    ⚡ Assign School to Plan
                </button>
                <button type="button" class="btn btn-primary px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#createPlanModal">
                    + Create New Plan
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>✓ Success:</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>⚠️ Note:</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Plans Cards Grid -->
        <div class="row g-4">
            @foreach($plans as $plan)
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 rounded-3 border {{ $plan->is_default ? 'border-primary border-2 shadow-sm' : 'border-slate-200' }} bg-white d-flex flex-column justify-content-between">
                        
                        <!-- Card Header -->
                        <div class="p-3 border-bottom bg-light bg-opacity-50">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-secondary font-monospace">{{ $plan->code }}</span>
                                    @if($plan->is_default)
                                        <span class="badge bg-primary ms-1">★ DEFAULT</span>
                                    @endif
                                </div>
                                <span class="badge {{ $plan->is_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary text-white' }} px-2 py-1">
                                    {{ $plan->is_active ? 'Active Tier' : 'Inactive' }}
                                </span>
                            </div>

                            <h5 class="fw-bold text-dark mb-1">{{ $plan->name }}</h5>
                            <p class="text-muted small mb-2 text-truncate-2" style="min-height: 38px;">
                                {{ $plan->description ?? 'No description provided.' }}
                            </p>

                            <div class="d-flex align-items-baseline gap-1 mt-2">
                                <span class="h3 fw-bold text-primary mb-0">${{ number_format($plan->price, 2) }}</span>
                                <span class="text-muted small">/ {{ $plan->billing_cycle }}</span>
                            </div>
                        </div>

                        <!-- Card Body: Capacity & Features -->
                        <div class="p-3 flex-grow-1">
                            <!-- Capacity Badges -->
                            <div class="d-flex flex-wrap gap-1 mb-3">
                                <span class="badge bg-light text-dark border small" title="Student capacity">
                                    👥 {{ $plan->max_students ? number_format($plan->max_students) . ' Students' : 'Unlimited Students' }}
                                </span>
                                <span class="badge bg-light text-dark border small" title="Class capacity">
                                    🏛️ {{ $plan->max_classes ? number_format($plan->max_classes) . ' Classes' : 'Unlimited Classes' }}
                                </span>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle small fw-bold">
                                    🏫 {{ $plan->schools_count }} Subscribed {{ Str::plural('School', $plan->schools_count) }}
                                </span>
                            </div>

                            <!-- Feature List -->
                            <h6 class="fw-bold small text-muted text-uppercase mb-2" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                Included Features ({{ count($plan->features ?? []) }} of {{ count($availableFeatures) }}):
                            </h6>
                            <ul class="list-unstyled mb-0 small">
                                @forelse($availableFeatures as $fKey => $fMeta)
                                    @php
                                        $hasThis = $plan->hasFeature($fKey);
                                    @endphp
                                    <li class="py-1 d-flex align-items-center justify-content-between {{ $hasThis ? 'text-dark' : 'text-muted opacity-50' }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <span>{{ $fMeta['icon'] ?? '•' }}</span>
                                            <span class="{{ $hasThis ? 'fw-semibold' : 'text-decoration-line-through' }}">{{ $plan->getFeatureDisplayName($fKey) }}</span>
                                            @if($fKey === 'student_profiles' && $hasThis)
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-1.5 py-0" style="font-size: 0.68rem;">
                                                    {{ $plan->max_students ? number_format($plan->max_students) . ' Students' : 'Unlimited' }}
                                                </span>
                                            @endif
                                        </div>
                                        @if($hasThis)
                                            <span class="text-success fw-bold">✓</span>
                                        @else
                                            <span class="text-muted" style="font-size: 0.75rem;">✕</span>
                                        @endif
                                    </li>
                                @empty
                                    <li class="text-muted small">No features configured.</li>
                                @endforelse
                            </ul>
                        </div>

                        <!-- Card Footer: Actions -->
                        <div class="p-3 border-top bg-light bg-opacity-25 d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold px-3 shadow-2xs" data-bs-toggle="modal" data-bs-target="#editPlanModal_{{ $plan->id }}">
                                ✏️ Edit Plan &amp; Features
                            </button>

                            @if($plan->schools_count === 0 && !$plan->is_default)
                                <form action="{{ route('admin.plans.destroy', $plan->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete plan {{ $plan->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger px-2" title="Delete Plan">
                                        🗑️
                                    </button>
                                </form>
                            @else
                                <span class="badge bg-light text-muted border small" title="Plans with assigned schools cannot be deleted">
                                    In Use
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Modal: Edit Plan -->
                <div class="modal fade" id="editPlanModal_{{ $plan->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content border-0 shadow">
                            <form action="{{ route('admin.plans.update', $plan->id) }}" method="POST">
                                @csrf
                                <div class="modal-header bg-dark text-white p-3">
                                    <h5 class="modal-title fw-bold">✏️ Edit Subscription Plan — {{ $plan->name }}</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body p-4">
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-7">
                                            <label class="form-label small fw-bold">Plan Name <span class="text-danger">*</span></label>
                                            <input type="text" name="name" class="form-control" value="{{ $plan->name }}" required>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small fw-bold">Code / Slug <span class="text-danger">*</span></label>
                                            <input type="text" name="code" class="form-control font-monospace" value="{{ $plan->code }}" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small fw-bold">Description</label>
                                            <textarea name="description" class="form-control" rows="2">{{ $plan->description }}</textarea>
                                        </div>

                                        <!-- Pricing & Billing -->
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Price ($) <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" name="price" class="form-control" value="{{ $plan->price }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Billing Cycle</label>
                                            <select name="billing_cycle" class="form-select">
                                                <option value="monthly" {{ $plan->billing_cycle === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                                <option value="yearly" {{ $plan->billing_cycle === 'yearly' ? 'selected' : '' }}>Yearly</option>
                                                <option value="custom" {{ $plan->billing_cycle === 'custom' ? 'selected' : '' }}>Custom / One-time</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Currency</label>
                                            <input type="text" name="currency" class="form-control" value="{{ $plan->currency ?? 'USD' }}">
                                        </div>

                                        <!-- Capacity & Sorting Limits -->
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Max Classes Limit</label>
                                            <input type="number" name="max_classes" class="form-control" value="{{ $plan->max_classes }}" placeholder="Blank = Unlimited">
                                            <small class="text-muted">Maximum classes allowed (empty = unlimited)</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Display Order</label>
                                            <input type="number" name="display_order" class="form-control" value="{{ $plan->display_order }}">
                                            <small class="text-muted">Sort priority on public pricing lists</small>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-check form-switch mt-2">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive_{{ $plan->id }}" {{ $plan->is_active ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold small" for="isActive_{{ $plan->id }}">Active (Available for schools)</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mt-2">
                                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="isDefault_{{ $plan->id }}" {{ $plan->is_default ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold small" for="isDefault_{{ $plan->id }}">Default Plan for new schools</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Feature Checkboxes by Category -->
                                    <div class="border rounded-3 p-3 bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="fw-bold mb-0 text-dark">📦 Modules &amp; Feature Gates Included</h6>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-secondary py-0" onclick="toggleAllPlanFeatures('edit_{{ $plan->id }}', true)">Select All</button>
                                                <button type="button" class="btn btn-outline-secondary py-0" onclick="toggleAllPlanFeatures('edit_{{ $plan->id }}', false)">Deselect All</button>
                                            </div>
                                        </div>

                                        <div class="row g-3" id="edit_{{ $plan->id }}_features">
                                            @foreach($categorizedFeatures as $category => $catFeatures)
                                                <div class="col-md-6">
                                                    <div class="p-2 border rounded bg-white h-100">
                                                        <span class="badge bg-secondary-subtle text-secondary small mb-2 fw-bold">{{ $category }}</span>
                                                        @foreach($catFeatures as $fKey => $fMeta)
                                                            @if($fKey === 'report_studio_customizer')
                                                                <div class="form-check mb-2 p-2 rounded border bg-light bg-opacity-50">
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <input class="form-check-input feat-checkbox m-0" 
                                                                               type="checkbox" 
                                                                               name="features[]" 
                                                                               value="{{ $fKey }}" 
                                                                               id="feat_{{ $plan->id }}_{{ $fKey }}"
                                                                               {{ $plan->hasFeature($fKey) ? 'checked' : '' }}>
                                                                        <label class="form-check-label small lh-sm w-100 m-0" for="feat_{{ $plan->id }}_{{ $fKey }}">
                                                                            <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                                                                <span>{{ $fMeta['icon'] }}</span>
                                                                                <input type="number" 
                                                                                       name="max_templates" 
                                                                                       class="form-control form-control-sm d-inline-block text-center fw-bold text-primary py-0 px-1 border-primary bg-white shadow-2xs" 
                                                                                       style="width: 55px; height: 26px; font-size: 0.82rem;"
                                                                                       value="{{ $plan->max_templates }}" 
                                                                                       placeholder="∞"
                                                                                       title="Number of custom templates allowed (leave blank for unlimited)"
                                                                                       min="0"
                                                                                       step="1"
                                                                                       onclick="event.stopPropagation();"
                                                                                       oninput="if(this.value > 0) document.getElementById('feat_{{ $plan->id }}_{{ $fKey }}').checked = true;">
                                                                                <strong>{{ $fMeta['name'] }}</strong>
                                                                            </div>
                                                                        </label>
                                                                    </div>
                                                                    <div class="text-muted mt-1 ps-4" style="font-size: 0.72rem;">{{ $fMeta['description'] }}</div>
                                                                    <div class="text-muted ps-4" style="font-size: 0.68rem;">
                                                                        💡 <em>Specify allowed templates (e.g. <strong>3</strong> or <strong>10</strong>), or leave blank for Unlimited.</em>
                                                                    </div>
                                                                </div>
                                                            @else
                                                                <div class="form-check mb-2">
                                                                    <input class="form-check-input feat-checkbox" 
                                                                           type="checkbox" 
                                                                           name="features[]" 
                                                                           value="{{ $fKey }}" 
                                                                           id="feat_{{ $plan->id }}_{{ $fKey }}"
                                                                           {{ $plan->hasFeature($fKey) ? 'checked' : '' }}>
                                                                    <label class="form-check-label small lh-sm" for="feat_{{ $plan->id }}_{{ $fKey }}">
                                                                        <strong>{{ $fMeta['icon'] }} {{ $fMeta['name'] }}</strong>
                                                                        <div class="text-muted" style="font-size: 0.72rem;">{{ $fMeta['description'] }}</div>
                                                                    </label>
                                                                </div>
                                                            @endif
                                                        @endforeach

                                                        @if($category === 'Students')
                                                            <div class="mt-2 pt-2 border-top bg-light p-2 rounded-2">
                                                                <label class="form-label small fw-bold text-dark mb-1 d-flex justify-content-between align-items-center">
                                                                    <span>👥 Number of Students Allowed:</span>
                                                                    <span class="badge bg-white text-muted border fw-normal" style="font-size: 0.68rem;">Blank = Unlimited</span>
                                                                </label>
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text bg-white text-muted fw-semibold">Max</span>
                                                                    <input type="number" 
                                                                           name="max_students" 
                                                                           class="form-control form-control-sm fw-bold text-primary bg-white" 
                                                                           value="{{ $plan->max_students }}" 
                                                                           placeholder="e.g. 250 (leave blank for unlimited)" 
                                                                           min="0"
                                                                           step="1">
                                                                    <span class="input-group-text bg-white text-muted">Students</span>
                                                                </div>
                                                                <div class="form-text text-muted" style="font-size: 0.68rem;">
                                                                    Maximum student enrollment capacity permitted under this plan.
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer bg-light p-3">
                                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary fw-bold px-4">💾 Save Plan Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Subscribed Schools Directory Table -->
    <div class="card card-custom p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-1">🏫 Schools Plan Assignments</h5>
                <p class="text-muted small mb-0">Overview of all schools and their current subscription plan tier.</p>
            </div>
            <a href="{{ route('admin.schools.index') }}" class="btn btn-sm btn-outline-secondary fw-semibold">
                Manage Schools Console →
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th>School Name</th>
                        <th>School Code</th>
                        <th>Assigned Plan Tier</th>
                        <th>Subscription Status</th>
                        <th>Students</th>
                        <th class="text-end">Quick Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($schools as $sch)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $sch->getLogoUrl() }}?v={{ time() }}" alt="" style="width: 28px; height: 28px; object-fit: contain;" class="rounded border p-0.5">
                                    <span class="fw-bold text-dark">{{ $sch->school_name }}</span>
                                </div>
                            </td>
                            <td>
                                <code class="fw-bold text-primary">{{ $sch->school_code }}</code>
                            </td>
                            <td>
                                @if($sch->plan)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-bold">
                                        ⭐ {{ $sch->plan->name }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                        Standard Fallback
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $sch->getSubscriptionStatusBadgeClass() }} px-2 py-1 small">
                                    ● {{ $sch->subscription_status ?? 'ACTIVE' }}
                                </span>
                            </td>
                            <td class="small text-muted">
                                <strong>{{ $sch->students()->count() }}</strong>
                                @if($sch->plan && $sch->plan->max_students)
                                    / {{ number_format($sch->plan->max_students) }}
                                @endif
                            </td>
                            <td class="text-end">
                                <button type="button" 
                                        class="btn btn-sm btn-outline-primary fw-bold px-2 py-1 shadow-2xs" 
                                        onclick="openQuickAssignModal('{{ $sch->school_id }}', '{{ $sch->plan_id }}', '{{ $sch->subscription_status ?? 'ACTIVE' }}')">
                                    Change Plan ▾
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Create New Plan -->
<div class="modal fade" id="createPlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.plans.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-dark text-white p-3">
                    <h5 class="modal-title fw-bold">+ Create New Subscription Plan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-7">
                            <label class="form-label small fw-bold">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Starter Plan, Pro Plan, Enterprise" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Code / Identifier</label>
                            <input type="text" name="code" class="form-control font-monospace" placeholder="e.g. starter, pro (optional)">
                            <small class="text-muted">Auto-generated from name if left blank</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Brief overview of who this plan is tailored for..."></textarea>
                        </div>

                        <!-- Pricing -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Price ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price" class="form-control" value="99.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Billing Cycle</label>
                            <select name="billing_cycle" class="form-select">
                                <option value="monthly" selected>Monthly</option>
                                <option value="yearly">Yearly</option>
                                <option value="custom">Custom / One-time</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Currency</label>
                            <input type="text" name="currency" class="form-control" value="USD">
                        </div>

                        <!-- Capacity & Sorting Limits -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Max Classes Limit</label>
                            <input type="number" name="max_classes" class="form-control" placeholder="Blank = Unlimited">
                            <small class="text-muted">Maximum classes allowed (empty = unlimited)</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="0">
                            <small class="text-muted">Sort priority on public pricing lists</small>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="newPlanIsActive" checked>
                                <label class="form-check-label fw-semibold small" for="newPlanIsActive">Active (Available for schools)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="newPlanIsDefault">
                                <label class="form-check-label fw-semibold small" for="newPlanIsDefault">Set as Default Plan for new schools</label>
                            </div>
                        </div>
                    </div>

                    <!-- Features Matrix Checkboxes -->
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark">📦 Select Features Included in This Plan</h6>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary py-0" onclick="toggleAllPlanFeatures('create_plan', true)">Select All</button>
                                <button type="button" class="btn btn-outline-secondary py-0" onclick="toggleAllPlanFeatures('create_plan', false)">Deselect All</button>
                            </div>
                        </div>

                        <div class="row g-3" id="create_plan_features">
                            @foreach($categorizedFeatures as $category => $catFeatures)
                                <div class="col-md-6">
                                    <div class="p-2 border rounded bg-white h-100">
                                        <span class="badge bg-secondary-subtle text-secondary small mb-2 fw-bold">{{ $category }}</span>
                                        @foreach($catFeatures as $fKey => $fMeta)
                                            @if($fKey === 'report_studio_customizer')
                                                <div class="form-check mb-2 p-2 rounded border bg-light bg-opacity-50">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <input class="form-check-input feat-checkbox m-0" 
                                                               type="checkbox" 
                                                               name="features[]" 
                                                               value="{{ $fKey }}" 
                                                               id="create_feat_{{ $fKey }}"
                                                               {{ in_array($fKey, ['homeroom_attendance', 'batch_gradebook', 'student_profiles']) ? 'checked' : '' }}>
                                                        <label class="form-check-label small lh-sm w-100 m-0" for="create_feat_{{ $fKey }}">
                                                            <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                                                <span>{{ $fMeta['icon'] }}</span>
                                                                <input type="number" 
                                                                       name="max_templates" 
                                                                       class="form-control form-control-sm d-inline-block text-center fw-bold text-primary py-0 px-1 border-primary bg-white shadow-2xs" 
                                                                       style="width: 55px; height: 26px; font-size: 0.82rem;"
                                                                       placeholder="∞"
                                                                       title="Number of custom templates allowed (leave blank for unlimited)"
                                                                       min="0"
                                                                       step="1"
                                                                       onclick="event.stopPropagation();"
                                                                       oninput="if(this.value > 0) document.getElementById('create_feat_{{ $fKey }}').checked = true;">
                                                                <strong>{{ $fMeta['name'] }}</strong>
                                                            </div>
                                                        </label>
                                                    </div>
                                                    <div class="text-muted mt-1 ps-4" style="font-size: 0.72rem;">{{ $fMeta['description'] }}</div>
                                                    <div class="text-muted ps-4" style="font-size: 0.68rem;">
                                                        💡 <em>Specify allowed templates (e.g. <strong>3</strong> or <strong>10</strong>), or leave blank for Unlimited.</em>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input feat-checkbox" 
                                                           type="checkbox" 
                                                           name="features[]" 
                                                           value="{{ $fKey }}" 
                                                           id="create_feat_{{ $fKey }}"
                                                           {{ in_array($fKey, ['homeroom_attendance', 'batch_gradebook', 'student_profiles']) ? 'checked' : '' }}>
                                                    <label class="form-check-label small lh-sm" for="create_feat_{{ $fKey }}">
                                                        <strong>{{ $fMeta['icon'] }} {{ $fMeta['name'] }}</strong>
                                                        <div class="text-muted" style="font-size: 0.72rem;">{{ $fMeta['description'] }}</div>
                                                    </label>
                                                </div>
                                            @endif
                                        @endforeach

                                        @if($category === 'Students')
                                            <div class="mt-2 pt-2 border-top bg-light p-2 rounded-2">
                                                <label class="form-label small fw-bold text-dark mb-1 d-flex justify-content-between align-items-center">
                                                    <span>👥 Number of Students Allowed:</span>
                                                    <span class="badge bg-white text-muted border fw-normal" style="font-size: 0.68rem;">Blank = Unlimited</span>
                                                </label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-white text-muted fw-semibold">Max</span>
                                                    <input type="number" 
                                                           name="max_students" 
                                                           class="form-control form-control-sm fw-bold text-primary bg-white" 
                                                           placeholder="e.g. 250 (leave blank for unlimited)" 
                                                           min="0"
                                                           step="1">
                                                    <span class="input-group-text bg-white text-muted">Students</span>
                                                </div>
                                                <div class="form-text text-muted" style="font-size: 0.68rem;">
                                                    Maximum student enrollment capacity permitted under this plan.
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">+ Create Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Quick Assign School to Plan -->
<div class="modal fade" id="quickAssignSchoolModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.plans.assign_school') }}" method="POST">
                @csrf
                <div class="modal-header bg-dark text-white p-3">
                    <h5 class="modal-title fw-bold">⚡ Assign School to Subscription Plan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select School <span class="text-danger">*</span></label>
                        <select name="school_id" id="assignModalSchoolSelect" class="form-select" required>
                            @foreach($schools as $sch)
                                <option value="{{ $sch->school_id }}">
                                    {{ $sch->school_name }} ({{ $sch->school_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Target Plan <span class="text-danger">*</span></label>
                        <select name="plan_id" id="assignModalPlanSelect" class="form-select" required>
                            @foreach($plans as $pl)
                                <option value="{{ $pl->id }}">
                                    {{ $pl->name }} — ${{ number_format($pl->price, 2) }}/{{ $pl->billing_cycle }} ({{ count($pl->features ?? []) }} features)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Subscription Status <span class="text-danger">*</span></label>
                        <select name="subscription_status" id="assignModalStatusSelect" class="form-select" required>
                            <option value="ACTIVE" selected>ACTIVE (Full Access to Plan Modules)</option>
                            <option value="TRIAL">TRIAL (Evaluation Mode)</option>
                            <option value="EXPIRED">EXPIRED (Restricted / Read-Only)</option>
                            <option value="SUSPENDED">SUSPENDED (Locked by Super Admin)</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">✓ Apply Plan to School</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleAllPlanFeatures(containerId, checkState) {
        const container = document.getElementById(containerId + '_features');
        if (container) {
            container.querySelectorAll('input.feat-checkbox').forEach(cb => {
                cb.checked = checkState;
            });
        }
    }

    function openQuickAssignModal(schoolId, planId, status) {
        const schoolSelect = document.getElementById('assignModalSchoolSelect');
        const planSelect = document.getElementById('assignModalPlanSelect');
        const statusSelect = document.getElementById('assignModalStatusSelect');

        if (schoolSelect && schoolId) schoolSelect.value = schoolId;
        if (planSelect && planId) planSelect.value = planId;
        if (statusSelect && status) statusSelect.value = status;

        const modal = new bootstrap.Modal(document.getElementById('quickAssignSchoolModal'));
        modal.show();
    }
</script>
@endsection
