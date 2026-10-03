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
    .badge-soft-warning {
        background-color: rgba(255, 193, 7, 0.15) !important;
        color: #b58105 !important;
        font-weight: 600;
    }
    .badge-soft-info {
        background-color: rgba(13, 202, 240, 0.12) !important;
        color: #0dcaf0 !important;
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
    .table-responsive {
        overflow: visible !important;
    }
    .dropdown-menu {
        z-index: 1060 !important;
    }
    .table-custom th, .table-custom td {
        white-space: nowrap;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header (No Breadcrumbs) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Shop Sales &amp; Invoices</h4>
                <p class="text-muted small mb-0">Browse and manage retail point-of-sale orders and invoices for <strong>{{ $shop->name }}</strong></p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('shops.sales.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-plus-circle fs-6"></i>
                    <span>New Shop Sale</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Quick Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 stat-card bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-shopping-bag fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Orders</small>
                        <h5 class="fw-bold text-dark mb-0">{{ number_format($totalSalesCount) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 stat-card bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Invoiced</small>
                        <h5 class="fw-bold text-dark mb-0">৳{{ number_format($totalSalesAmount, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 stat-card bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-check-circle fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Collected Payments</small>
                        <h5 class="fw-bold text-success mb-0">৳{{ number_format($totalPaidAmount, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 stat-card bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-danger-light text-danger rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-alert-circle fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Outstanding Due</small>
                        <h5 class="fw-bold text-danger mb-0">৳{{ number_format($totalDueAmount, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Main Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom border-light">
            <form method="GET" action="{{ route('shops.sales.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <select class="form-select bg-light border-light-subtle" name="customer_id" onchange="this.form.submit()">
                        <option value="">All Customers</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->phone }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select bg-light border-light-subtle" name="payment_status" onchange="this.form.submit()">
                        <option value="">All Payments</option>
                        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="due" {{ request('payment_status') == 'due' ? 'selected' : '' }}>Due / Partial</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <input type="text" class="form-control bg-light border-light-subtle" name="search" value="{{ request('search') }}" placeholder="Order # or Customer...">
                        <button type="submit" class="btn btn-secondary"><i class="fe fe-search"></i></button>
                    </div>
                </div>
                <div class="col-auto">
                    @if(request()->hasAny(['shop_id', 'customer_id', 'payment_status', 'search', 'from_date', 'to_date']))
                        <a href="{{ route('shops.sales.index') }}" class="btn btn-light px-3 rounded-3 text-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4">Order #</th>
                            <th>Date</th>
                            <th>Shop / Branch</th>
                            <th>Customer</th>
                            <th class="text-end">Total Bill (৳)</th>
                            <th class="text-end">Paid (৳)</th>
                            <th class="text-end">Due (৳)</th>
                            <th class="text-center">Payment Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        @forelse($sales as $sale)
                            <tr>
                                <td class="ps-4">
                                    <a href="{{ route('sales.show', $sale->id) }}" class="fw-bold text-primary text-decoration-none">
                                        {{ $sale->order_no }}
                                    </a>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($sale->order_date)->format('d M Y') }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="fe fe-home text-primary me-1"></i> {{ $sale->warehouse?->name ?? 'Shop' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $sale->customer?->name ?? 'Walk-in Customer' }}</div>
                                    <small class="text-muted">{{ $sale->customer?->phone }}</small>
                                </td>
                                <td class="text-end fw-bold text-dark">৳{{ number_format((float)($sale->payble ?? 0), 2) }}</td>
                                <td class="text-end text-success fw-bold">৳{{ number_format((float)($sale->advanced_payment ?? 0), 2) }}</td>
                                <td class="text-end text-danger fw-bold">৳{{ number_format((float)($sale->due_payment ?? 0), 2) }}</td>
                                <td class="text-center">
                                    @if((float)($sale->due_payment ?? 0) <= 0)
                                        <span class="badge badge-soft-success px-2 py-1 rounded-pill">Paid</span>
                                    @elseif((float)($sale->advanced_payment ?? 0) > 0)
                                        <span class="badge badge-soft-warning px-2 py-1 rounded-pill">Partial</span>
                                    @else
                                        <span class="badge badge-soft-danger px-2 py-1 rounded-pill">Unpaid</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('sales.show', $sale->id) }}">
                                                    <i class="fe fe-eye text-info"></i>
                                                    <span>View Details</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('sales.invoice.pdf', $sale->id) }}" target="_blank">
                                                    <i class="fe fe-download text-danger"></i>
                                                    <span>Invoice PDF</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('sales.edit', $sale->id) }}">
                                                    <i class="fe fe-edit text-secondary"></i>
                                                    <span>Edit Sale</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <div class="mb-2"><i class="fe fe-shopping-bag fs-1 text-secondary opacity-50"></i></div>
                                    <h6 class="fw-bold">No Shop Sales Found</h6>
                                    <p class="small text-muted mb-3">Create your first sale originating from a shop.</p>
                                    <a href="{{ route('shops.sales.create', ['warehouse_id' => $shop->id, 'locked' => 1, 'from' => 'shop']) }}" class="btn btn-primary btn-sm px-3 rounded-3">
                                        <i class="fe fe-plus-circle me-1"></i> New Shop Sale
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($sales->hasPages())
                <div class="card-footer bg-white py-3 border-top border-light">
                    {{ $sales->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
