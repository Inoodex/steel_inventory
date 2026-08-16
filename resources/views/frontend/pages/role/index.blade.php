@extends('frontend.layouts.app')

@push('styles')
<style>
    .stat-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
    }
    .table-custom tbody tr:hover td {
        background-color: #f8fafc !important;
    }
    .badge-soft-primary {
        background-color: rgba(118, 56, 255, 0.12) !important;
        color: #7638ff !important;
        font-weight: 600;
    }
    .badge-soft-info {
        background-color: rgba(13, 202, 240, 0.12) !important;
        color: #0dcaf0 !important;
        font-weight: 600;
    }
    .badge-soft-success {
        background-color: rgba(25, 135, 84, 0.12) !important;
        color: #198754 !important;
        font-weight: 600;
    }
    .btn-action-icon {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #dbe2ea !important;
        border-radius: 8px !important;
        background-color: #ffffff !important;
        color: #555e6d !important;
        padding: 0;
        transition: all 0.2s ease;
    }
    .btn-action-icon:hover {
        background-color: #7638ff !important;
        color: #ffffff !important;
        border-color: #7638ff !important;
    }
    .table-custom th, .table-custom td {
        white-space: nowrap;
    }
    .table-responsive {
        overflow: visible !important;
    }
    .dropdown-menu {
        z-index: 1060 !important;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Role Management</h4>
                <p class="text-muted small mb-0">Define user access levels and configure module authorization privileges</p>
            </div>
            <div>
                <a href="{{ route('role.create') }}" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-plus-circle fs-6"></i>
                    <span>Create New Role</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Summary Stats Bar -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-shield fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Defined Roles</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($roles->count()) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-users fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Assigned Users Total</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($roles->sum('users_count')) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-check-circle fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Access Control</h6>
                        <h4 class="mb-0 fw-bold text-success">RBAC Active</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Summary Stats Bar -->

    <!-- Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <!-- Search Header -->
        <div class="card-header bg-white py-3 border-bottom border-light">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-light-subtle text-muted">
                            <i class="fe fe-search"></i>
                        </span>
                        <input type="text" id="roleSearchInput" class="form-control border-light-subtle" placeholder="Search role name..." autocomplete="off">
                    </div>
                </div>
                <div class="col-12 col-md-6 text-md-end text-muted small">
                    Showing <span id="visibleRoleCount" class="fw-bold text-dark">{{ $roles->count() }}</span> roles
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0" id="rolesTable">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4" style="width: 70px;">#</th>
                            <th>Role Title</th>
                            <th>Assigned Users</th>
                            <th>Granted Permissions</th>
                            <th>Created Date</th>
                            <th class="text-end pe-4" style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse ($roles as $role)
                            @php
                                $isSuperAdmin = in_array(strtolower($role->name), ['super admin', 'admin']);
                            @endphp
                            <tr class="role-row" data-search="{{ strtolower($role->name) }}">
                                <td class="ps-4 text-muted fw-semibold">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar avatar-xs bg-primary-light text-primary rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="fe fe-shield fs-7"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark fs-7">
                                                {{ $role->name }}
                                            </span>
                                            @if($isSuperAdmin)
                                                <span class="badge bg-primary text-white ms-1 px-2 py-0 fs-8">System</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-soft-info px-2 py-1 rounded-2">
                                        <i class="fe fe-user me-1"></i>{{ $role->users_count }} Users
                                    </span>
                                </td>
                                <td>
                                    @if($isSuperAdmin)
                                        <span class="badge badge-soft-success px-3 py-1 rounded-pill">
                                            <i class="fe fe-check-all me-1"></i> Full System Access (All Permissions)
                                        </span>
                                    @elseif($role->permissions->count() > 0)
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($role->permissions->take(3) as $perm)
                                                <span class="badge badge-soft-primary px-2 py-1 rounded-2">
                                                    {{ $perm->name }}
                                                </span>
                                            @endforeach
                                            @if($role->permissions->count() > 3)
                                                <span class="badge badge-light text-muted border px-2 py-1 rounded-2">
                                                    +{{ $role->permissions->count() - 3 }} more
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small">No permissions assigned</span>
                                    @endif
                                </td>
                                <td class="text-muted small">
                                    {{ $role->created_at ? $role->created_at->format('d M Y') : 'System Default' }}
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="dropdown d-inline-block">
                                        <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('role.edit', $role->id) }}">
                                                    <i class="fe fe-edit text-primary"></i>
                                                    <span>Edit Role</span>
                                                </a>
                                            </li>
                                            @if(!$isSuperAdmin)
                                                <li><hr class="dropdown-divider opacity-50"></li>
                                                <li>
                                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#deleteRoleModal{{ $role->id }}">
                                                        <i class="fe fe-trash-2 text-danger"></i>
                                                        <span>Delete Role</span>
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <div class="avatar avatar-xl bg-primary-light text-primary rounded-circle mb-3 d-flex align-items-center justify-content-center">
                                            <i class="fe fe-shield fs-1"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">No Roles Found</h5>
                                        <p class="text-muted small mb-3">Create access roles to organize user permissions</p>
                                        <a href="{{ route('role.create') }}" class="btn btn-primary btn-sm px-3 rounded-2">
                                            Create Role
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modals Placement: Outside Table Structure to prevent DOM & stacking context issues -->
@foreach ($roles as $role)
    @php
        $isSuperAdmin = in_array(strtolower($role->name), ['super admin', 'admin']);
    @endphp
    @if(!$isSuperAdmin)
        <div id="deleteRoleModal{{ $role->id }}" class="modal fade" tabindex="-1" aria-labelledby="deleteModalLabel{{ $role->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-3 text-start">
                    <div class="modal-header bg-light border-bottom">
                        <h5 class="modal-title fw-bold text-dark" id="deleteModalLabel{{ $role->id }}">
                            <i class="fe fe-alert-triangle text-danger me-1"></i> Confirm Role Deletion
                        </h5>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="mb-0 text-muted">
                            Are you sure you want to delete role <strong class="text-dark">{{ $role->name }}</strong>? 
                            Users currently assigned to this role may lose their access permissions.
                        </p>
                    </div>
                    <div class="modal-footer bg-light border-top">
                        <button type="button" class="btn btn-outline-secondary px-4 rounded-2" data-bs-dismiss="modal">Cancel</button>
                        <form action="{{ route('role.destroy', $role->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger px-4 rounded-2">Delete Role</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('roleSearchInput');
    const rows = document.querySelectorAll('.role-row');
    const visibleCountSpan = document.getElementById('visibleRoleCount');

    function filterTable() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        let count = 0;

        rows.forEach(row => {
            const rowSearchText = row.dataset.search || '';
            if (query === '' || rowSearchText.includes(query)) {
                row.style.display = '';
                count++;
            } else {
                row.style.display = 'none';
            }
        });

        if (visibleCountSpan) {
            visibleCountSpan.textContent = count;
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
});
</script>
@endsection
