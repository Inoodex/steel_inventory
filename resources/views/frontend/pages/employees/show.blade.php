@extends('frontend.layouts.app')

@push('styles')
<style>
    .profile-avatar-container {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        border: 4px solid #ffffff;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        overflow: hidden;
        background-color: #f8fafc;
    }
    .stat-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
    }
    .nav-tabs-custom .nav-link {
        color: #64748b;
        font-weight: 600;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 12px 20px;
        transition: all 0.2s ease;
    }
    .nav-tabs-custom .nav-link.active {
        color: #7638ff;
        border-bottom-color: #7638ff;
        background: transparent;
    }
    .badge-soft-success {
        background-color: rgba(25, 135, 84, 0.12) !important;
        color: #198754 !important;
        font-weight: 600;
    }
    .badge-soft-danger {
        background-color: rgba(220, 53, 69, 0.12) !important;
        color: #dc3545 !important;
        font-weight: 600;
    }
    .badge-soft-primary {
        background-color: rgba(118, 56, 255, 0.12) !important;
        color: #7638ff !important;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header & Action Bar -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Employee Profile</h4>
                <p class="text-muted small mb-0">Detailed view of employment records, contact info, salary, advances, and TA/DA history</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left fs-6"></i>
                    <span>Back to Directory</span>
                </a>
                <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-edit fs-6"></i>
                    <span>Edit Profile</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Profile Header Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
        <div class="card-body p-4 bg-white">
            <div class="row align-items-center g-4">
                <div class="col-auto">
                    <div class="profile-avatar-container position-relative d-flex align-items-center justify-content-center">
                        @if ($employee->image && file_exists(public_path('uploads/employees/' . $employee->image)))
                            <img src="{{ asset('uploads/employees/' . $employee->image) }}" alt="{{ $employee->name }}" class="w-100 h-100 object-fit-cover">
                        @else
                            <span class="fs-2 fw-bold text-primary">
                                {{ strtoupper(substr($employee->name, 0, 2)) }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="col">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h3 class="fw-bold text-dark mb-0">{{ $employee->name }}</h3>
                        <span class="badge badge-soft-primary px-3 py-1 rounded-pill font-monospace fw-bold fs-7">
                            {{ $employee->employee_id }}
                        </span>
                        @if ($employee->status == 'active')
                            <span class="badge badge-soft-success px-3 py-1 rounded-pill fs-7">Active</span>
                        @else
                            <span class="badge badge-soft-danger px-3 py-1 rounded-pill fs-7">Inactive</span>
                        @endif
                    </div>
                    <p class="text-secondary fw-medium mb-2">{{ $employee->designation ?? 'Staff Member' }}</p>

                    <div class="d-flex flex-wrap align-items-center gap-4 text-muted small">
                        <div>
                            <i class="fe fe-mail text-primary me-1"></i>
                            <span>{{ $employee->email ?? 'No email registered' }}</span>
                        </div>
                        <div>
                            <i class="fe fe-phone text-primary me-1"></i>
                            <span>{{ $employee->phone ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <i class="fe fe-calendar text-primary me-1"></i>
                            <span>Joined: {{ $employee->join_date ? date('d M, Y', strtotime($employee->join_date)) : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Profile Header Card -->

    <!-- Summary Stats Bar -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Basic Monthly Salary</h6>
                        <h4 class="mb-0 fw-bold text-dark">৳ {{ number_format($employee->salary ?? 0, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-warning-light text-warning rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-credit-card fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Advance Drawn</h6>
                        <h4 class="mb-0 fw-bold text-dark">৳ {{ number_format($totalAdvance, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-file-text fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total TA/DA Claims</h6>
                        <h4 class="mb-0 fw-bold text-dark">৳ {{ number_format($totalTaDa, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-shield fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">System Account</h6>
                        <h5 class="mb-0 fw-bold text-dark">{{ $employee->user->name ?? 'No System User' }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Summary Stats Bar -->

    <!-- Tabs Content Container -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white pb-0 border-bottom border-light">
            <ul class="nav nav-tabs nav-tabs-custom border-bottom-0" id="employeeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active d-flex align-items-center gap-2" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true">
                        <i class="fe fe-user"></i>
                        <span>Overview & Details</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center gap-2" id="advance-tab" data-bs-toggle="tab" data-bs-target="#advance" type="button" role="tab" aria-controls="advance" aria-selected="false">
                        <i class="fe fe-dollar-sign"></i>
                        <span>Advance Salary History</span>
                        <span class="badge bg-light text-dark rounded-pill border ms-1">{{ $advanceExpenses->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center gap-2" id="tada-tab" data-bs-toggle="tab" data-bs-target="#tada" type="button" role="tab" aria-controls="tada" aria-selected="false">
                        <i class="fe fe-briefcase"></i>
                        <span>TA/DA Claims History</span>
                        <span class="badge bg-light text-dark rounded-pill border ms-1">{{ $employee->tadas->count() }}</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content" id="employeeTabsContent">

                <!-- TAB 1: OVERVIEW -->
                <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Employment Details</h6>
                            <table class="table table-borderless table-sm align-middle mb-0">
                                <tr>
                                    <td class="text-muted py-2 ps-0" style="width: 40%;">Employee ID</td>
                                    <td class="fw-bold text-dark py-2">{{ $employee->employee_id }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2 ps-0">Full Name</td>
                                    <td class="fw-bold text-dark py-2">{{ $employee->name }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2 ps-0">Designation</td>
                                    <td class="text-dark py-2">{{ $employee->designation ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2 ps-0">Joining Date</td>
                                    <td class="text-dark py-2">{{ $employee->join_date ? date('F d, Y', strtotime($employee->join_date)) : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2 ps-0">Employment Status</td>
                                    <td class="py-2">
                                        @if ($employee->status == 'active')
                                            <span class="badge badge-soft-success px-3 py-1 rounded-pill">Active</span>
                                        @else
                                            <span class="badge badge-soft-danger px-3 py-1 rounded-pill">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Contact & Compensation</h6>
                            <table class="table table-borderless table-sm align-middle mb-0">
                                <tr>
                                    <td class="text-muted py-2 ps-0" style="width: 40%;">Phone Number</td>
                                    <td class="fw-semibold text-dark py-2">{{ $employee->phone ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2 ps-0">Email Address</td>
                                    <td class="text-dark py-2">{{ $employee->email ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2 ps-0">Monthly Basic Salary</td>
                                    <td class="fw-bold text-success py-2">৳ {{ number_format($employee->salary ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2 ps-0">Linked System User</td>
                                    <td class="text-dark py-2">
                                        @if($employee->user)
                                            <span class="badge bg-light text-dark border px-2 py-1">
                                                <i class="fe fe-user text-primary me-1"></i>{{ $employee->user->name }} ({{ $employee->user->email }})
                                            </span>
                                        @else
                                            <span class="text-muted italic">No portal login linked</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: ADVANCE SALARY HISTORY -->
                <div class="tab-pane fade" id="advance" role="tabpanel" aria-labelledby="advance-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary fs-7 text-uppercase">
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Voucher / Title</th>
                                    <th>Category</th>
                                    <th>Remarks / Notes</th>
                                    <th class="text-end">Amount (৳)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($advanceExpenses as $expense)
                                    <tr>
                                        <td class="text-muted fw-semibold">{{ $loop->iteration }}</td>
                                        <td>{{ date('d M, Y', strtotime($expense->date)) }}</td>
                                        <td class="fw-bold text-dark">{{ $expense->title ?? 'Advance Disbursement' }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark border px-2 py-1">
                                                {{ $expense->category->name ?? 'Advance Salary' }}
                                            </span>
                                        </td>
                                        <td class="text-muted small">{{ $expense->description ?? '-' }}</td>
                                        <td class="text-end fw-bold text-danger">৳ {{ number_format($expense->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            No advance salary receipts recorded for this employee.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 3: TA/DA CLAIMS HISTORY -->
                <div class="tab-pane fade" id="tada" role="tabpanel" aria-labelledby="tada-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary fs-7 text-uppercase">
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Travel Title / Reason</th>
                                    <th>Transport Fee</th>
                                    <th>Food Allowance</th>
                                    <th>Total Bill (৳)</th>
                                    <th class="text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employee->tadas as $tada)
                                    <tr>
                                        <td class="text-muted fw-semibold">{{ $loop->iteration }}</td>
                                        <td>{{ date('d M, Y', strtotime($tada->date ?? $tada->created_at)) }}</td>
                                        <td class="fw-bold text-dark">{{ $tada->title ?? 'Official Travel' }}</td>
                                        <td>৳ {{ number_format($tada->transport_fee ?? 0, 2) }}</td>
                                        <td>৳ {{ number_format($tada->food_allowance ?? 0, 2) }}</td>
                                        <td class="fw-bold text-dark">৳ {{ number_format($tada->total_bill ?? 0, 2) }}</td>
                                        <td class="text-end">
                                            @if($tada->status == 'approved')
                                                <span class="badge badge-soft-success px-3 py-1 rounded-pill">Approved</span>
                                            @elseif($tada->status == 'rejected')
                                                <span class="badge badge-soft-danger px-3 py-1 rounded-pill">Rejected</span>
                                            @else
                                                <span class="badge badge-soft-primary px-3 py-1 rounded-pill">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            No TA/DA allowance claims recorded for this employee.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
