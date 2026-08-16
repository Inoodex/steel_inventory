@extends('frontend.layouts.app')

@push('styles')
<style>
    .permission-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
        background: #ffffff;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .permission-card:hover {
        border-color: #7638ff;
        background-color: #fcfbff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(118, 56, 255, 0.06);
    }
    .form-check-input:checked {
        background-color: #7638ff;
        border-color: #7638ff;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Create New Role</h4>
                <p class="text-muted small mb-0">Define role title and assign module access permissions</p>
            </div>
            <div>
                <a href="{{ route('role.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back to Roles</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <form action="{{ route('role.store') }}" method="POST">
        @csrf

        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom border-light">
                <h5 class="card-title fw-bold text-dark mb-0">
                    <i class="fe fe-shield text-primary me-2"></i> Role Information
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-6 col-12">
                        <label for="name" class="form-label small text-secondary fw-semibold mb-1">
                            Role Title <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control border-light-subtle @error('name') is-invalid @enderror" 
                               value="{{ old('name') }}" id="name" name="name" 
                               placeholder="e.g. Sales Manager, Inventory Clerk, Accountant" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom border-light d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-lock text-primary me-2"></i> Module Access Permissions
                    </h5>
                    <p class="text-muted small mb-0">Toggle which modules and system features this role can access</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-2 px-3" id="selectAllBtn">
                        <i class="fe fe-check-square me-1"></i> Select All
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 px-3" id="deselectAllBtn">
                        <i class="fe fe-square me-1"></i> Deselect All
                    </button>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    @foreach ($permissions as $permission)
                        <div class="col-xl-4 col-md-6 col-12">
                            <label class="permission-card d-flex align-items-center justify-content-between w-100 mb-0" for="permission_{{ $permission->id }}">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar avatar-sm bg-light text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                                        <i class="fe fe-check-circle"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark fs-7">{{ $permission->name }}</div>
                                        <small class="text-muted">Guard: {{ $permission->guard_name }}</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input permission-checkbox" type="checkbox" role="switch" 
                                           name="permissions[]" id="permission_{{ $permission->id }}" 
                                           value="{{ $permission->id }}"
                                           {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer bg-light py-3 border-top d-flex justify-content-end gap-2">
                <a href="{{ route('role.index') }}" class="btn btn-outline-secondary px-4 rounded-2">Cancel</a>
                <button type="submit" class="btn btn-primary px-4 rounded-2">Create Role</button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllBtn = document.getElementById('selectAllBtn');
    const deselectAllBtn = document.getElementById('deselectAllBtn');
    const checkboxes = document.querySelectorAll('.permission-checkbox');

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = true);
        });
    }

    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = false);
        });
    }
});
</script>
@endsection
