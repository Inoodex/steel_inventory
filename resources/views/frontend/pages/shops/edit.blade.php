@extends('frontend.layouts.app')

@push('styles')
<style>
    .form-section-title {
        font-size: 15px;
        font-weight: 700;
        color: #2c3038;
        border-bottom: 1px solid #f0f0f5;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .form-control:focus, .form-select:focus {
        border-color: #7638ff;
        box-shadow: 0 0 0 0.2rem rgba(118, 56, 255, 0.15);
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header (No Breadcrumbs) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Edit Shop / Outlet Profile</h4>
                <p class="text-muted small mb-0">Update branch details, manager assignment, and location for {{ $shop->name }}</p>
            </div>
            <div>
                <a href="{{ route('shops.sales.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back to Shop Sales</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Form Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('shops.update', $shop->id) }}">
                @csrf
                @method('PUT')

                <div class="form-section-title">
                    Shop Information
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-7">
                        <label class="form-label fw-semibold text-secondary small mb-1">Shop / Outlet Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $shop->name) }}" required>
                        @error('name') <div class="text-danger fs-7 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-semibold text-secondary small mb-1">Branch Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code', $shop->code) }}" required>
                        @error('code') <div class="text-danger fs-7 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-secondary small mb-1">Manager / In-Charge Person</label>
                        <input type="text" class="form-control" name="contact_person" value="{{ old('contact_person', $shop->contact_person) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-secondary small mb-1">Contact Phone Number</label>
                        <input type="text" class="form-control" name="contact_phone" value="{{ old('contact_phone', $shop->contact_phone) }}">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-semibold text-secondary small mb-1">Location / Shop Address</label>
                        <input type="text" class="form-control" name="location" value="{{ old('location', $shop->location) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-secondary small mb-1">Status <span class="text-danger">*</span></label>
                        <select class="form-select" name="status" required>
                            <option value="active" {{ old('status', $shop->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $shop->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold text-secondary small mb-1">Notes / Description</label>
                        <textarea class="form-control" name="notes" rows="3">{{ old('notes', $shop->notes) }}</textarea>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top border-light">
                    <a href="{{ route('shops.sales.index') }}" class="btn btn-light px-4 py-2 rounded-3 text-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm">Save &amp; Update Shop</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
