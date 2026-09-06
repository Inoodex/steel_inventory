@extends('frontend.layouts.app')

@push('styles')
<style>
    .stat-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.07) !important;
    }
    .badge-soft-success {
        background-color: rgba(25, 135, 84, 0.12) !important;
        color: #198754 !important;
        font-weight: 600;
    }
    .badge-soft-secondary {
        background-color: rgba(108, 117, 125, 0.15) !important;
        color: #6c757d !important;
        font-weight: 600;
    }
    .badge-soft-primary {
        background-color: rgba(118, 56, 255, 0.12) !important;
        color: #7638ff !important;
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
        color: #0aa2c0 !important;
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
    .financial-pill {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 14px;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    @php
        $lotSubTotal   = (float) $lot->purchases->sum(fn($p) => (float)($p->sub_price ?: ($p->unit_price * $p->total_weight)));
        $lotDelivery   = (float) $lot->purchases->sum('delivery_charge');
        $lotLabour     = (float) $lot->purchases->sum('labour_cost');
        $lotScale      = (float) $lot->purchases->sum('weight_scale_cost');
        $lotOther      = (float) $lot->purchases->sum('other_charges');
        $lotDiscount   = (float) $lot->purchases->sum('discount');
        $lotTotalExtra = $lotDelivery + $lotLabour + $lotScale + $lotOther;
        $firstPurchase = $lot->purchases->first();
    @endphp

    <!-- Page Header (No breadcrumbs per project guidelines) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h4 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-package text-primary me-2"></i>Consignment Lot #{{ $lot->lot_number }}
                    </h4>
                    @if($lot->status === 'active')
                        <span class="badge badge-soft-success px-2.5 py-1 rounded-pill fs-8">
                            <i class="fe fe-check-circle me-1"></i>Active Consignment
                        </span>
                    @else
                        <span class="badge badge-soft-secondary px-2.5 py-1 rounded-pill fs-8">
                            <i class="fe fe-lock me-1"></i>Closed
                        </span>
                    @endif
                </div>
                <p class="text-muted small mb-0 mt-1">
                    Procurement Date: <strong class="text-dark">{{ \Carbon\Carbon::parse($lot->lot_date)->format('d M, Y') }}</strong>
                    • Vendor: <strong class="text-dark">{{ $lot->vendor ? $lot->vendor->name : 'N/A' }}</strong>
                    • Steel Intake: <span class="text-primary fw-semibold">{{ $totalPurchases }} {{ Str::plural('item', $totalPurchases) }} ({{ $totalCoils }} Coils)</span>
                </p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('lots.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-1.5 shadow-sm">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back to Lots</span>
                </a>
                <a href="{{ route('purchase.create', ['lot_id' => $lot->id]) }}" class="btn btn-outline-primary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-1.5 shadow-sm">
                    <i class="fe fe-plus-circle"></i>
                    <span>Add Steel Coils</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Section 1: Full-Width 5-Metric Responsive Grid -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Weight -->
        <div class="col-xxl col-xl col-lg-4 col-md-6 col-12">
            <div class="card stat-card border-0 shadow-sm rounded-3 bg-white h-100 mb-0">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="background-color: rgba(118, 56, 255, 0.12); color: #7638ff; width: 44px; height: 44px;">
                        <i class="fe fe-anchor fs-5"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <span class="text-muted small fw-medium d-block mb-1 text-truncate">Total Weight</span>
                        <h5 class="mb-0 fw-bold text-dark font-monospace text-truncate">
                            {{ number_format($totalWeight, 2) }} <span class="fs-8 fw-normal text-muted">kg</span>
                        </h5>
                        <small class="text-muted fs-8 d-block text-truncate">
                            @if($totalWeight >= 1000)
                                {{ number_format($totalWeight / 1000, 2) }} Metric Tons
                            @else
                                Gross Intake Weight
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Coils & Line Items -->
        <div class="col-xxl col-xl col-lg-4 col-md-6 col-12">
            <div class="card stat-card border-0 shadow-sm rounded-3 bg-white h-100 mb-0">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="background-color: rgba(13, 202, 240, 0.12); color: #0aa2c0; width: 44px; height: 44px;">
                        <i class="fe fe-layers fs-5"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <span class="text-muted small fw-medium d-block mb-1 text-truncate">Stockyard Intake</span>
                        <h5 class="mb-0 fw-bold text-dark text-truncate">
                            {{ $totalCoils }} <span class="fs-8 fw-normal text-muted">Coils</span>
                        </h5>
                        <small class="text-muted fs-8 d-block text-truncate">
                            Across {{ $totalPurchases }} {{ Str::plural('line item', $totalPurchases) }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Total Consignment Bill -->
        <div class="col-xxl col-xl col-lg-4 col-md-6 col-12">
            <div class="card stat-card border-0 shadow-sm rounded-3 bg-white h-100 mb-0">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="background-color: rgba(15, 23, 42, 0.08); color: #0f172a; width: 44px; height: 44px;">
                        <i class="fe fe-dollar-sign fs-5"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <span class="text-muted small fw-medium d-block mb-1 text-truncate">Consignment Bill</span>
                        <h5 class="mb-0 fw-bold text-dark font-monospace text-truncate">
                            ৳ {{ number_format($totalAmount, 2) }}
                        </h5>
                        <small class="text-muted fs-8 d-block text-truncate">
                            Including all charges
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Paid Disbursed -->
        <div class="col-xxl col-xl col-lg-4 col-md-6 col-12">
            <div class="card stat-card border-0 shadow-sm rounded-3 bg-white h-100 mb-0">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="background-color: rgba(25, 135, 84, 0.12); color: #198754; width: 44px; height: 44px;">
                        <i class="fe fe-check-circle fs-5"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <span class="text-muted small fw-medium d-block mb-1 text-truncate">Paid Disbursed</span>
                        <h5 class="mb-0 fw-bold text-success font-monospace text-truncate">
                            ৳ {{ number_format($totalPaid, 2) }}
                        </h5>
                        <small class="text-muted fs-8 d-block text-truncate">
                            Disbursed to vendor
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Outstanding Due -->
        <div class="col-xxl col-xl col-lg-4 col-md-6 col-12">
            <div class="card stat-card border-0 shadow-sm rounded-3 bg-white h-100 mb-0">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="background-color: {{ $totalDue > 0 ? 'rgba(220, 53, 69, 0.12)' : 'rgba(25, 135, 84, 0.12)' }}; color: {{ $totalDue > 0 ? '#dc3545' : '#198754' }}; width: 44px; height: 44px;">
                        <i class="fe {{ $totalDue > 0 ? 'fe-alert-circle' : 'fe-shield' }} fs-5"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <span class="text-muted small fw-medium d-block mb-1 text-truncate">Outstanding Due</span>
                        <h5 class="mb-0 fw-bold {{ $totalDue > 0 ? 'text-danger' : 'text-success' }} font-monospace text-truncate">
                            {{ $totalDue > 0 ? '৳ ' . number_format($totalDue, 2) : 'Paid in Full' }}
                        </h5>
                        <small class="text-muted fs-8 d-block text-truncate">
                            {{ $totalDue > 0 ? 'Due to vendor' : 'Settled clean' }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2: Lot Profile & Extra Charges Breakdown (Balanced 2-Column on Laptops) -->
    <div class="row g-3 mb-4">
        <!-- Lot Summary Card -->
        <div class="col-xl-4 col-lg-5 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100 mb-0">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h6 class="card-title fw-bold m-0 text-dark d-flex align-items-center gap-2">
                        <i class="fe fe-info text-primary"></i>
                        <span>Consignment Overview</span>
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex flex-column gap-1">
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                            <span class="text-muted small text-nowrap me-2">Lot Identifier:</span>
                            <span class="fw-bold text-dark font-monospace text-end">#{{ $lot->lot_number }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-start py-2 border-bottom border-light">
                            <span class="text-muted small text-nowrap me-2">Vendor / Mill:</span>
                            <div class="text-end text-break" style="max-width: 65%;">
                                <span class="fw-bold text-dark d-block">{{ $lot->vendor ? $lot->vendor->name : 'N/A' }}</span>
                                @if($lot->vendor && $lot->vendor->phone)
                                    <small class="text-muted d-block fs-8">{{ $lot->vendor->phone }}</small>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                            <span class="text-muted small text-nowrap me-2">Consignment Date:</span>
                            <span class="fw-semibold text-dark text-end">
                                {{ \Carbon\Carbon::parse($lot->lot_date)->format('d M, Y') }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                            <span class="text-muted small text-nowrap me-2">Consignment Status:</span>
                            <div class="text-end">
                                @if($lot->status === 'active')
                                    <span class="badge badge-soft-success px-2.5 py-1 rounded-pill fs-8">Active</span>
                                @else
                                    <span class="badge badge-soft-secondary px-2.5 py-1 rounded-pill fs-8">Closed</span>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted small text-nowrap me-2">Recorded By:</span>
                            <span class="fw-semibold text-secondary text-end text-truncate" style="max-width: 60%;">
                                {{ $lot->creator ? $lot->creator->name : 'System' }}
                            </span>
                        </div>
                    </div>

                    @if($lot->notes)
                        <div class="mt-3 p-2.5 bg-light rounded-3 text-secondary small border border-light-subtle">
                            <strong class="text-dark"><i class="fe fe-file-text me-1 text-muted"></i>Notes:</strong> {{ $lot->notes }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Consignment Financial & Procurement Summary Card -->
        <div class="col-xl-8 col-lg-7 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100 mb-0">
                <div class="card-header bg-transparent border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="card-title fw-bold m-0 text-dark d-flex align-items-center gap-2">
                        <i class="fe fe-dollar-sign text-primary"></i>
                        <span>Financial Summary & Procurement Charges</span>
                    </h6>
                    @if($firstPurchase)
                        <a href="{{ route('purchase.show', $firstPurchase->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-none fw-semibold">
                            <i class="fe fe-external-link me-1"></i>Open Consignment Order
                        </a>
                    @endif
                </div>
                <div class="card-body p-3">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-4 col-12">
                            <div class="financial-pill h-100">
                                <span class="text-muted small d-block mb-1">Base Steel Cost:</span>
                                <h5 class="fw-bold text-dark font-monospace mb-0">৳ {{ number_format($lotSubTotal, 2) }}</h5>
                                <small class="text-muted fs-8">Rate × Weight</small>
                            </div>
                        </div>
                        <div class="col-sm-4 col-12">
                            <div class="financial-pill h-100">
                                <span class="text-muted small d-block mb-1">Total Extra Charges:</span>
                                <h5 class="fw-bold text-primary font-monospace mb-0">৳ {{ number_format($lotTotalExtra, 2) }}</h5>
                                <small class="text-muted fs-8">Logistics & Handling</small>
                            </div>
                        </div>
                        <div class="col-sm-4 col-12">
                            <div class="financial-pill h-100">
                                <span class="text-muted small d-block mb-1">Net Lot Bill:</span>
                                <h5 class="fw-bold text-dark font-monospace mb-0">৳ {{ number_format($totalAmount, 2) }}</h5>
                                <small class="text-muted fs-8">Grand Procurement Total</small>
                            </div>
                        </div>
                    </div>

                    <!-- Extra Charges Detail Pills -->
                    <div class="p-3 bg-light rounded-3 border border-light-subtle">
                        <span class="text-dark small fw-bold d-block mb-2">
                            <i class="fe fe-sliders me-1 text-primary"></i>Intake Logistics & Handling Breakdown:
                        </span>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge bg-white text-dark border px-2.5 py-1.5 fs-8">
                                <i class="fe fe-truck me-1 text-primary"></i>Transport Charge: 
                                <strong class="ms-1 font-monospace">৳ {{ number_format($lotDelivery, 2) }}</strong>
                            </span>
                            <span class="badge bg-white text-dark border px-2.5 py-1.5 fs-8">
                                <i class="fe fe-scissors me-1 text-primary"></i>Cutting & Load-Unload: 
                                <strong class="ms-1 font-monospace">৳ {{ number_format($lotLabour, 2) }}</strong>
                            </span>
                            <span class="badge bg-white text-dark border px-2.5 py-1.5 fs-8">
                                <i class="fe fe-activity me-1 text-primary"></i>Scale & Labour Charge: 
                                <strong class="ms-1 font-monospace">৳ {{ number_format($lotScale, 2) }}</strong>
                            </span>
                            @if($lotOther > 0)
                                <span class="badge bg-white text-dark border px-2.5 py-1.5 fs-8">
                                    <i class="fe fe-plus-circle me-1 text-secondary"></i>Other Charges: 
                                    <strong class="ms-1 font-monospace">৳ {{ number_format($lotOther, 2) }}</strong>
                                </span>
                            @endif
                            @if($lotDiscount > 0)
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 fs-8">
                                    <i class="fe fe-tag me-1 text-danger"></i>Discount: 
                                    <strong class="ms-1 font-monospace">-৳ {{ number_format($lotDiscount, 2) }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Full-Width Purchases & Coils Attached Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-transparent border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h6 class="card-title fw-bold m-0 text-dark">
                    <i class="fe fe-grid text-primary me-2"></i>Purchases & Steel Coils Attached to this Consignment
                </h6>
                <p class="text-muted small mb-0 mt-0.5">Physical coils registered into yard stock under this lot</p>
            </div>
            @if($firstPurchase)
                <a href="{{ route('purchase.show', $firstPurchase->id) }}" class="btn btn-sm btn-primary rounded-3 px-3 shadow-sm d-inline-flex align-items-center gap-1.5">
                    <i class="fe fe-eye"></i>
                    <span>View Full Consignment View</span>
                </a>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0">
                    <thead class="table-light text-secondary text-uppercase fs-8">
                        <tr>
                            <th class="ps-4" style="width: 50px;">#</th>
                            <th>Date</th>
                            <th>Specifications</th>
                            <th>Physical Coils</th>
                            <th>Stockyard Depot</th>
                            <th class="text-end">Total Weight</th>
                            <th class="text-end">Unit Rate</th>
                            <th class="text-end">Sub Total</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lot->purchases as $purchase)
                            <tr>
                                <td class="ps-4 text-muted small fw-semibold">{{ $loop->iteration }}</td>
                                <td class="text-dark small">
                                    {{ $purchase->created_at ? $purchase->created_at->format('d M, Y') : 'N/A' }}
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">
                                        {{ $purchase->thickness ? $purchase->thickness . ' mm' : 'Standard' }}
                                        @if($purchase->size)
                                            * {{ $purchase->size }} {{ $purchase->size_type ?? 'ft' }}
                                        @endif
                                    </div>
                                    <small class="text-muted font-monospace fs-8">#PO-{{ $purchase->id }}</small>
                                </td>
                                <td>
                                    @if($purchase->coils->count() > 0)
                                        <div class="d-flex flex-wrap gap-1 align-items-center" style="max-width: 280px;">
                                            @foreach($purchase->coils as $coil)
                                                <span class="badge bg-light text-dark border font-monospace fs-8 px-2 py-1">
                                                    <i class="fe fe-disc me-1 text-primary"></i>{{ $coil->coil_number }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted small">No coils registered</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-secondary small fw-medium">
                                        <i class="fe fe-map-pin text-danger me-1"></i>
                                        {{ $purchase->warehouse ? $purchase->warehouse->name : 'Main Yard Depot' }}
                                    </div>
                                </td>
                                <td class="text-end fw-bold text-primary font-monospace">
                                    {{ number_format($purchase->total_weight ?? $purchase->quantity, 2) }} kg
                                    <div class="text-muted fw-normal fs-8">
                                        {{ (int)$purchase->quantity }} {{ Str::plural('coil', (int)$purchase->quantity) }}
                                    </div>
                                </td>
                                <td class="text-end font-monospace text-dark">
                                    ৳ {{ number_format($purchase->unit_price, 2) }}
                                </td>
                                <td class="text-end fw-bold text-dark font-monospace">
                                    ৳ {{ number_format($purchase->sub_price ?: ($purchase->unit_price * $purchase->total_weight), 2) }}
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown dropdown-action">
                                        <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            <a class="dropdown-item py-2" href="{{ route('purchase.show', $purchase->id) }}">
                                                <i class="fe fe-eye me-2 text-primary"></i>View Consignment Order
                                            </a>
                                            <a class="dropdown-item py-2" href="{{ route('purchase.edit', $purchase->id) }}">
                                                <i class="fe fe-edit me-2 text-success"></i>Edit Purchase Item
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <i class="fe fe-package fs-1 text-secondary mb-2" style="opacity: 0.3;"></i>
                                        <h6 class="fw-semibold text-secondary mb-1">No Purchase Line Items Attached</h6>
                                        <p class="small text-muted mb-3">No steel intake has been recorded under Lot #{{ $lot->lot_number }} yet.</p>
                                        <a href="{{ route('purchase.create', ['lot_id' => $lot->id]) }}" class="btn btn-sm btn-primary rounded-3 px-3">
                                            <i class="fe fe-plus-circle me-1"></i>Add Steel Coils Now
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
@endsection
