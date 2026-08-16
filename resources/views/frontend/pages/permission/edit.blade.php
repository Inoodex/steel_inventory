@extends('frontend.layouts.app')

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Edit Permission</h4>
                <p class="text-muted small mb-0">Modify system permission key</p>
            </div>
            <div>
                <a href="{{ route('permission.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back to Permissions</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <div class="row justify-content-center">
        <div class="col-lg-8 col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h5 class="card-title fw-bold text-dark mb-0">Edit Permission Details</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('permission.update', $permission->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="name" class="form-label small text-secondary fw-semibold mb-1">
                                Permission Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control border-light-subtle @error('name') is-invalid @enderror" 
                                   value="{{ old('name', $permission->name) }}" id="name" name="name" 
                                   placeholder="Enter permission name" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="guard_name" class="form-label small text-secondary fw-semibold mb-1">Guard Name</label>
                            <input type="text" class="form-control border-light-subtle" 
                                   value="{{ old('guard_name', $permission->guard_name) }}" id="guard_name" name="guard_name" readonly>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <a href="{{ route('permission.index') }}" class="btn btn-outline-secondary px-4 rounded-2">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 rounded-2">Update Permission</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
