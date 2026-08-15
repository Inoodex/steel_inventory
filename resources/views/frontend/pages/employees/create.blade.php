@extends('frontend.layouts.app')

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Add Employee</h4>
                <p class="text-muted small mb-0">Register a new staff member to the employee directory</p>
            </div>
            <div>
                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left fs-6"></i>
                    <span>Back to Directory</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Employee ID <span class="text-danger">*</span></label>
                        <input type="text" name="employee_id" class="form-control border-light-subtle" placeholder="e.g. EMP-1001" required>
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control border-light-subtle" placeholder="Enter employee full name" required>
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Email Address</label>
                        <input type="email" name="email" class="form-control border-light-subtle" placeholder="employee@company.com">
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Phone Number</label>
                        <input type="text" name="phone" class="form-control border-light-subtle" placeholder="017xxxxxxxx">
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Designation</label>
                        <input type="text" name="designation" class="form-control border-light-subtle" placeholder="e.g. Manager / Accountant / Supervisor">
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Join Date</label>
                        <input type="date" name="join_date" class="form-control border-light-subtle" value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Basic Monthly Salary (৳)</label>
                        <input type="number" step="0.01" name="salary" class="form-control border-light-subtle" placeholder="0.00">
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Photo Upload</label>
                        <input type="file" name="image" class="form-control border-light-subtle">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-4">
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary px-4 rounded-3">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 rounded-3">Save Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
