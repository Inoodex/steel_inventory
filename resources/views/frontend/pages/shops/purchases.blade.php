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
                <h4 class="card-title fw-bold text-dark mb-1">Shop Inward Purchases &amp; Procurement</h4>
                <p class="text-muted small mb-0">Track raw steel intake and procurement batches received directly into <strong>{{ $shop->name }}</strong></p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('shops.purchases.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-plus-circle fs-6"></i>
                    <span>New Stock Intake</span>
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
                        <i class="fe fe-download fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Intake Batches</small>
                        <h5 class="fw-bold text-dark mb-0">{{ number_format($totalPurchasesCount) }}</h5>
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
                        <small class="text-muted d-block">Total Purchase Cost</small>
                        <h5 class="fw-bold text-dark mb-0">৳{{ number_format($totalPurchasesAmount, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 stat-card bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-warning-light text-warning rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-anchor fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Procured Weight</small>
                        <h5 class="fw-bold text-dark mb-0">{{ number_format($totalPurchasesWeight / 1000, 2) }} <span class="text-muted fs-7 fw-normal">MT</span></h5>
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
                        <small class="text-muted d-block">Payable Vendor Due</small>
                        <h5 class="fw-bold text-danger mb-0">৳{{ number_format($totalPurchasesDue, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Main Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom border-light">
            <form method="GET" action="{{ route('shops.purchases.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <select class="form-select bg-light border-light-subtle" name="vendor_id" onchange="this.form.submit()">
                        <option value="">All Vendors</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}" {{ request('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control bg-light border-light-subtle" name="search" value="{{ request('search') }}" placeholder="Search thickness, size, vendor...">
                        <button type="submit" class="btn btn-secondary"><i class="fe fe-search"></i></button>
                    </div>
                </div>
                <div class="col-auto">
                    @if(request()->hasAny(['shop_id', 'vendor_id', 'search', 'from_date', 'to_date']))
                        <a href="{{ route('shops.purchases.index') }}" class="btn btn-light px-3 rounded-3 text-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4">Batch / Lot</th>
                            <th>Date</th>
                            <th>Shop / Branch</th>
                            <th>Vendor</th>
                            <th>Spec / Size</th>
                            <th class="text-end">Weight (Kg)</th>
                            <th class="text-end">Total Price (৳)</th>
                            <th class="text-end">Due (৳)</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        @forelse($purchases as $p)
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-light text-dark border font-monospace">{{ $p->lot?->name ?? 'Lot #' . $p->lot_id }}</span>
                                </td>
                                <td>{{ $p->created_at?->format('d M Y') }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="fe fe-home text-primary me-1"></i> {{ $p->warehouse?->name ?? 'Shop' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $p->vendor?->name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $p->vendor?->phone }}</small>
                                </td>
                                <td><span class="fw-bold text-dark">{{ $p->thickness }}</span> | {{ $p->size }}</td>
                                <td class="text-end fw-bold text-dark">{{ number_format($p->total_weight, 2) }} kg</td>
                                <td class="text-end fw-bold text-dark">৳{{ number_format($p->total_price, 2) }}</td>
                                <td class="text-end text-danger fw-bold">৳{{ number_format($p->due, 2) }}</td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('purchase.show', $p->id) }}">
                                                    <i class="fe fe-eye text-info"></i>
                                                    <span>View Details</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('purchase.edit', $p->id) }}">
                                                    <i class="fe fe-edit text-secondary"></i>
                                                    <span>Edit Purchase</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <div class="mb-2"><i class="fe fe-download fs-1 text-secondary opacity-50"></i></div>
                                    <h6 class="fw-bold">No Shop Purchases Found</h6>
                                    <p class="small text-muted mb-3">Record an inward purchase intake to assign material into a shop.</p>
                                    <a href="{{ route('shops.purchases.create') }}" class="btn btn-primary btn-sm px-3 rounded-3">
                                        <i class="fe fe-plus-circle me-1"></i> New Stock Intake
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($purchases->hasPages())
                <div class="card-footer bg-white py-3 border-top border-light">
                    {{ $purchases->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
