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
                <h4 class="card-title fw-bold text-dark mb-1">Shop Sales Returns</h4>
                <p class="text-muted small mb-0">Track returned inventory and customer refunds processed for <strong>{{ $shop->name }}</strong></p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('returns.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-refresh-cw fs-6"></i>
                    <span>Record Return</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Quick Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 stat-card bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-warning-light text-warning rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-refresh-cw fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Return Cases</small>
                        <h5 class="fw-bold text-dark mb-0">{{ number_format($totalReturnsCount) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 stat-card bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-danger-light text-danger rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Refund Amount</small>
                        <h5 class="fw-bold text-danger mb-0">৳{{ number_format($totalRefundAmount, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Main Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom border-light">
            <form method="GET" action="{{ route('shops.returns.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <select class="form-select bg-light border-light-subtle" name="customer_id" onchange="this.form.submit()">
                        <option value="">All Customers</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->phone }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select bg-light border-light-subtle" name="status" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-secondary px-3 rounded-3">Filter</button>
                    @if(request()->hasAny(['shop_id', 'customer_id', 'status', 'from_date', 'to_date']))
                        <a href="{{ route('shops.returns.index') }}" class="btn btn-light px-3 rounded-3 text-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4">Return ID</th>
                            <th>Date</th>
                            <th>Shop / Branch</th>
                            <th>Customer</th>
                            <th>Order No</th>
                            <th class="text-end">Refund Amount (৳)</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        @forelse($returns as $ret)
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-light text-dark border font-monospace">#RET-{{ str_pad($ret->id, 4, '0', STR_PAD_LEFT) }}</span>
                                </td>
                                <td>{{ $ret->return_date?->format('d M Y') }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="fe fe-home text-primary me-1"></i> {{ $ret->sale?->warehouse?->name ?? 'Shop' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $ret->customer?->name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $ret->customer?->phone }}</small>
                                </td>
                                <td>
                                    @if($ret->sale)
                                        <a href="{{ route('sales.show', $ret->sale->id) }}" class="fw-bold text-primary text-decoration-none">
                                            {{ $ret->sale->order_no }}
                                        </a>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold text-danger">৳{{ number_format($ret->total_refund_amount, 2) }}</td>
                                <td class="text-center">
                                    @if($ret->status === 'completed')
                                        <span class="badge badge-soft-success px-2 py-1 rounded-pill">Completed</span>
                                    @elseif($ret->status === 'approved')
                                        <span class="badge badge-soft-info px-2 py-1 rounded-pill">Approved</span>
                                    @elseif($ret->status === 'pending')
                                        <span class="badge badge-soft-warning px-2 py-1 rounded-pill">Pending</span>
                                    @else
                                        <span class="badge badge-soft-danger px-2 py-1 rounded-pill">{{ ucfirst($ret->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('returns.show', $ret->id) }}" class="btn btn-sm btn-light border px-2 py-1 rounded-2" title="View Return Details">
                                        <i class="fe fe-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="mb-2"><i class="fe fe-refresh-cw fs-1 text-secondary opacity-50"></i></div>
                                    <h6 class="fw-bold">No Shop Sales Returns Found</h6>
                                    <p class="small text-muted mb-0">Customer return records for shops will appear here.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($returns->hasPages())
                <div class="card-footer bg-white py-3 border-top border-light">
                    {{ $returns->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
