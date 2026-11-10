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
    .badge-soft-warning {
        background-color: rgba(255, 193, 7, 0.15) !important;
        color: #b58105 !important;
        font-weight: 600;
    }
    .badge-soft-danger {
        background-color: rgba(220, 53, 69, 0.12) !important;
        color: #dc3545 !important;
        font-weight: 600;
    }
    .badge-soft-info {
        background-color: rgba(13, 202, 240, 0.12) !important;
        color: #0dcaf0 !important;
        font-weight: 600;
    }
    .badge-soft-primary {
        background-color: rgba(118, 56, 255, 0.12) !important;
        color: #7638ff !important;
        font-weight: 600;
    }
    .badge-soft-secondary {
        background-color: rgba(108, 117, 125, 0.12) !important;
        color: #6c757d !important;
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
    .fs-8 {
        font-size: 0.8rem;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header (No breadcrumbs as per project guidelines) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">
                    <i class="fe fe-rotate-ccw text-warning me-2"></i>Purchase Returns &amp; Debit Notes
                </h4>
                <p class="text-muted small mb-0">Record and track steel returns to vendors, stock deductions, and refund settlements.</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('purchase-returns.create') }}" class="btn btn-primary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 fw-semibold">
                    <i class="fe fe-plus-circle"></i>
                    <span>Create Purchase Return</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Summary Stats Bar -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-4 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="avatar avatar-md bg-warning-light text-warning rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-rotate-ccw fs-5"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-medium d-block mb-1">Total Return Vouchers</span>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalReturnsCount) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-4 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="avatar avatar-md bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-disc fs-5"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-medium d-block mb-1">Total Steel Returned</span>
                        <h4 class="mb-0 fw-bold text-primary font-monospace">
                            {{ number_format($totalReturnWeight, 2) }} kg
                            @if($totalReturnWeight >= 1000)
                                <small class="text-muted fs-8 fw-normal">({{ number_format($totalReturnWeight / 1000, 3) }} MT)</small>
                            @endif
                        </h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-4 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="avatar avatar-md bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-5"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-medium d-block mb-1">Total Refund Value</span>
                        <h4 class="mb-0 fw-bold text-success font-monospace">à§³ {{ number_format($totalRefundValue, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Summary Stats Bar -->

    <!-- Returns Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <!-- Filter Controls -->
        <div class="card-header bg-white py-3 border-bottom border-light">
            <form action="{{ route('purchase-returns.index') }}" method="GET" id="returnFilterForm">
                <div class="row g-2 align-items-end">
                    <div class="col-xl-3 col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-muted mb-1">Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fe fe-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="Return #, Vendor, PO #..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-muted mb-1">Vendor</label>
                        <select name="vendor_id" class="form-select select2">
                            <option value="">All Vendors</option>
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}" {{ request('vendor_id') == $v->id ? 'selected' : '' }}>
                                    {{ $v->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-muted mb-1">Stockyard / Shop</label>
                        <select name="warehouse_id" class="form-select">
                            <option value="">All Locations</option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}" {{ request('warehouse_id') == $w->id ? 'selected' : '' }}>
                                    {{ $w->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-muted mb-1">Refund Settlement</label>
                        <select name="refund_type" class="form-select">
                            <option value="">All Types</option>
                            <option value="reduce_due" {{ request('refund_type') == 'reduce_due' ? 'selected' : '' }}>Deduct From Due</option>
                            <option value="advance_credit" {{ request('refund_type') == 'advance_credit' ? 'selected' : '' }}>Vendor Advance Credit</option>
                            <option value="cash_refund" {{ request('refund_type') == 'cash_refund' ? 'selected' : '' }}>Cash Refund</option>
                            <option value="bank_refund" {{ request('refund_type') == 'bank_refund' ? 'selected' : '' }}>Bank Refund</option>
                        </select>
                    </div>
                    <div class="col-xl-3 col-lg-12 col-md-12 col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-3 py-2 flex-grow-1">
                            <i class="fe fe-filter me-1"></i> Filter
                        </button>
                        <a href="{{ route('purchase-returns.index') }}" class="btn btn-outline-secondary px-3 py-2">
                            <i class="fe fe-refresh-cw me-1"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0">
                    <thead class="bg-light text-secondary fs-8 text-uppercase">
                        <tr>
                            <th class="ps-4">Return #</th>
                            <th>Date</th>
                            <th>Vendor</th>
                            <th>Consignment / PO</th>
                            <th>Coil Tag / Specs</th>
                            <th class="text-end">Returned Wt</th>
                            <th class="text-end">Cost Rate</th>
                            <th class="text-end">Refund Value</th>
                            <th>Settlement</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returns as $ret)
                            <tr>
                                <td class="ps-4">
                                    <a href="{{ route('purchase-returns.show', $ret->id) }}" class="fw-bold text-primary font-monospace">
                                        {{ $ret->return_no }}
                                    </a>
                                </td>
                                <td>
                                    <span class="text-dark">{{ $ret->return_date ? $ret->return_date->format('d M Y') : 'N/A' }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('vendors.show', $ret->vendor_id) }}" class="fw-semibold text-dark text-decoration-none">
                                        {{ $ret->vendor?->name ?? 'N/A' }}
                                    </a>
                                    @if($ret->vendor?->phone)
                                        <div class="small text-muted fs-8">{{ $ret->vendor->phone }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($ret->purchase)
                                        <a href="{{ route('purchase.show', $ret->purchase->id) }}" class="badge bg-light text-dark border font-monospace text-decoration-none">
                                            PO-{{ $ret->purchase->id }}
                                        </a>
                                        @if($ret->purchase->lot)
                                            <div class="small text-muted fs-8 mt-0.5">Lot: {{ $ret->purchase->lot->lot_number }}</div>
                                        @endif
                                    @else
                                        <span class="text-muted fs-8">Direct</span>
                                    @endif
                                </td>
                                <td>
                                    @if($ret->coil)
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-monospace">
                                            {{ $ret->coil->coil_number }}
                                        </span>
                                    @endif
                                    @if($ret->purchase)
                                        <div class="small text-muted fs-8 mt-0.5">
                                            {{ $ret->purchase->thickness }} {{ $ret->purchase->size }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end font-monospace fw-bold text-danger">
                                    -{{ number_format($ret->return_weight, 2) }} kg
                                    @if($ret->return_weight >= 1000)
                                        <small class="text-muted fw-normal d-block fs-8">(-{{ number_format($ret->return_weight / 1000, 3) }} MT)</small>
                                    @endif
                                </td>
                                <td class="text-end font-monospace">
                                    à§³ {{ number_format($ret->unit_price, 2) }}
                                </td>
                                <td class="text-end font-monospace fw-bold text-success">
                                    à§³ {{ number_format($ret->total_refund_amount, 2) }}
                                </td>
                                <td>
                                    @if($ret->refund_type === 'reduce_due')
                                        <span class="badge badge-soft-warning">
                                            <i class="fe fe-minus-circle me-1"></i>Deduct Due
                                        </span>
                                    @elseif($ret->refund_type === 'advance_credit')
                                        <span class="badge badge-soft-primary">
                                            <i class="fe fe-plus-circle me-1"></i>Advance Credit
                                        </span>
                                    @elseif($ret->refund_type === 'cash_refund')
                                        <span class="badge badge-soft-success">
                                            <i class="fe fe-dollar-sign me-1"></i>Cash Refund
                                        </span>
                                    @elseif($ret->refund_type === 'bank_refund')
                                        <span class="badge badge-soft-info">
                                            <i class="fe fe-credit-card me-1"></i>Bank Refund
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('purchase-returns.show', $ret->id) }}">
                                                    <i class="fe fe-eye text-primary"></i>
                                                    <span>View Return Voucher</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('purchase-returns.pdf', $ret->id) }}" target="_blank">
                                                    <i class="fe fe-file-text text-danger"></i>
                                                    <span>Print / PDF Voucher</span>
                                                </a>
                                            </li>
                                            @if($ret->purchase)
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('purchase.show', $ret->purchase->id) }}">
                                                        <i class="fe fe-shopping-cart text-secondary"></i>
                                                        <span>View Original Consignment</span>
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="text-center">
                                        <div class="avatar avatar-xl bg-light text-muted rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center">
                                            <i class="fe fe-rotate-ccw fs-2"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1">No Purchase Returns Found</h6>
                                        <p class="text-muted small mb-3">No return vouchers recorded matching your search or filters.</p>
                                        <a href="{{ route('purchase-returns.create') }}" class="btn btn-sm btn-primary px-3 rounded-pill">
                                            <i class="fe fe-plus-circle me-1"></i>Record First Return
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($returns->hasPages())
                <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="text-muted small">
                        Showing {{ $returns->firstItem() }} to {{ $returns->lastItem() }} of {{ $returns->total() }} entries
                    </span>
                    <div>
                        {{ $returns->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

