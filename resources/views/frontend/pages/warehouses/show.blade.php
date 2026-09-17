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
    .info-table td {
        padding: 0.65rem 0.5rem;
        vertical-align: middle;
    }
    .table-custom th, .table-custom td {
        white-space: nowrap;
    }
    .table-custom tbody tr:hover {
        background-color: #fcfbff !important;
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
    .badge-soft-info {
        background-color: rgba(13, 202, 240, 0.12) !important;
        color: #0dcaf0 !important;
        font-weight: 600;
    }
    .badge-soft-warning {
        background-color: rgba(255, 193, 7, 0.15) !important;
        color: #b58105 !important;
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
    .nav-tabs-custom .nav-link {
        color: #64748b;
        font-weight: 600;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 0.75rem 1.25rem;
        background: transparent;
    }
    .nav-tabs-custom .nav-link.active {
        color: #7638ff;
        border-bottom-color: #7638ff;
        background: transparent;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header (No breadcrumbs per project guidelines) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-light text-dark border font-monospace px-2 py-1 fs-8">
                        {{ $warehouse->code ?? 'WH-' . $warehouse->id }}
                    </span>
                    <h4 class="card-title fw-bold text-dark mb-0">
                        {{ $warehouse->name }}
                    </h4>
                    @if($warehouse->status === 'active')
                        <span class="badge badge-soft-success px-3 py-1 rounded-pill">
                            <i class="fe fe-check-circle me-1"></i>Active Yard
                        </span>
                    @else
                        <span class="badge badge-soft-danger px-3 py-1 rounded-pill">
                            <i class="fe fe-x-circle me-1"></i>Inactive Yard
                        </span>
                    @endif
                </div>
                <p class="text-muted small mb-0">
                    <i class="fe fe-map-pin text-secondary me-1"></i>{{ $warehouse->location ?: 'Location not specified' }}
                    @if($warehouse->contact_person)
                        • <i class="fe fe-user text-secondary me-1 ms-1"></i>{{ $warehouse->contact_person }}
                        @if($warehouse->contact_phone)
                            (<a href="tel:{{ $warehouse->contact_phone }}" class="text-muted">{{ $warehouse->contact_phone }}</a>)
                        @endif
                    @endif
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('warehouses.edit', $warehouse->id) }}" class="btn btn-outline-primary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-edit"></i>
                    <span>Edit Stockyard</span>
                </a>

                <a href="{{ route('inventory.index', ['warehouse_id' => $warehouse->id]) }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2 text-white">
                    <i class="fe fe-disc"></i>
                    <span>View Yard Inventory</span>
                </a>

                <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>All Stockyards</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Summary Stats Bar -->
    <div class="row g-3 mb-4">
        <!-- Live Coils -->
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-disc fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Coils in Stock</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($inStockCoilsCount) }} <span class="fs-7 text-muted fw-normal">Coils</span></h4>
                        @if($inProcessingCoilsCount > 0)
                            <small class="text-warning fw-semibold">+{{ $inProcessingCoilsCount }} processing</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- On-Hand Weight -->
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-anchor fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">On-Hand Steel Tonnage</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalStockTonnageMT, 2) }} <span class="fs-7 text-muted fw-normal">MT</span></h4>
                        <small class="text-muted">{{ number_format($totalStockTonnageKg, 0) }} kg</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Capacity Utilization -->
        <!-- <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="text-muted fw-normal mb-0">Yard Utilization</h6>
                        <span class="fw-bold {{ $utilizationPercent >= 90 ? 'text-danger' : ($utilizationPercent >= 75 ? 'text-warning' : 'text-success') }}">
                            {{ $utilizationPercent }}%
                        </span>
                    </div>
                    <div class="progress mb-2" style="height: 6px;">
                        <div class="progress-bar {{ $utilizationPercent >= 90 ? 'bg-danger' : ($utilizationPercent >= 75 ? 'bg-warning' : 'bg-success') }}" 
                             role="progressbar" style="width: {{ min(100, $utilizationPercent) }}%;" 
                             aria-valuenow="{{ $utilizationPercent }}" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Used: {{ number_format($totalStockTonnageMT, 1) }} MT</span>
                        <span>Cap: {{ $capacityTon > 0 ? number_format($capacityTon, 1) . ' MT' : 'Uncapped' }}</span>
                    </div>
                </div>
            </div>
        </div> -->

        <!-- Stock Valuation -->
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Yard Stock Valuation</h6>
                        <h4 class="mb-0 fw-bold text-success">৳ {{ number_format($totalStockValuation, 2) }}</h4>
                        <small class="text-muted">Live asset value</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Section: Two Column Profile & Activity Cards Side by Side -->
    <div class="row g-4 mb-4">
        <!-- Stockyard Profile Card -->
        <div class="col-xl-6 col-lg-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100 mb-0">
                <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-info text-primary me-2"></i>Stockyard Profile
                    </h5>
                    <a href="{{ route('warehouses.edit', $warehouse->id) }}" class="btn btn-sm btn-light border px-2 py-1 rounded-2" title="Edit Stockyard">
                        <i class="fe fe-edit-2 text-muted"></i>
                    </a>
                </div>
                <div class="card-body">
                    <table class="table table-borderless info-table mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width: 35%;">Yard Code:</td>
                                <td class="font-monospace fw-bold text-dark">{{ $warehouse->code ?? 'WH-' . $warehouse->id }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Yard Name:</td>
                                <td class="fw-semibold text-dark">{{ $warehouse->name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Status:</td>
                                <td>
                                    @if($warehouse->status === 'active')
                                        <span class="badge badge-soft-success px-2 py-1 rounded-pill">Active</span>
                                    @else
                                        <span class="badge badge-soft-danger px-2 py-1 rounded-pill">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Location:</td>
                                <td class="text-dark">{{ $warehouse->location ?: '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Contact Person:</td>
                                <td class="fw-semibold text-dark">{{ $warehouse->contact_person ?: '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Contact Phone:</td>
                                <td>
                                    @if($warehouse->contact_phone)
                                        <a href="tel:{{ $warehouse->contact_phone }}" class="text-primary fw-medium">
                                            <i class="fe fe-phone me-1"></i>{{ $warehouse->contact_phone }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Max Capacity:</td>
                                <td class="fw-semibold text-dark">
                                    {{ $warehouse->capacity_ton ? number_format($warehouse->capacity_ton, 1) . ' MT' : 'Unspecified' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Registered:</td>
                                <td class="text-muted small">
                                    {{ $warehouse->created_at ? $warehouse->created_at->format('d M, Y') : '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    @if($warehouse->notes)
                        <div class="mt-3 pt-3 border-top">
                            <h6 class="text-secondary small fw-bold text-uppercase mb-1">Operational Notes & Facilities</h6>
                            <p class="text-muted small mb-0 bg-light p-3 rounded-2 border">
                                {{ $warehouse->notes }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quick Operational Actions Card -->
        <div class="col-xl-6 col-lg-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100 mb-0">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-activity text-primary me-2"></i>Yard Activity Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between p-3 bg-light-subtle rounded-3 mb-3 border">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-md bg-primary-light text-primary rounded-2 d-flex align-items-center justify-content-center">
                                <i class="fe fe-shopping-cart fs-5"></i>
                            </div>
                            <div>
                                <span class="text-dark fw-bold d-block">Procurement Intakes</span>
                                <small class="text-muted">Total purchases into this yard</small>
                            </div>
                        </div>
                        <span class="badge badge-soft-primary fs-6 px-3 py-1 rounded-pill">
                            {{ $warehouse->purchases_count ?? $warehouse->purchases()->count() }}
                        </span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between p-3 bg-light-subtle rounded-3 mb-3 border">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-md bg-success-light text-success rounded-2 d-flex align-items-center justify-content-center">
                                <i class="fe fe-truck fs-5"></i>
                            </div>
                            <div>
                                <span class="text-dark fw-bold d-block">Sales Dispatches</span>
                                <small class="text-muted">Total orders shipped from this yard</small>
                            </div>
                        </div>
                        <span class="badge badge-soft-success fs-6 px-3 py-1 rounded-pill">
                            {{ $warehouse->sales_count ?? $warehouse->sales()->count() }}
                        </span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between p-3 bg-light-subtle rounded-3 border">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-md bg-info-light text-info rounded-2 d-flex align-items-center justify-content-center">
                                <i class="fe fe-disc fs-5"></i>
                            </div>
                            <div>
                                <span class="text-dark fw-bold d-block">Physical Coils</span>
                                <small class="text-muted">Coil registers assigned to yard</small>
                            </div>
                        </div>
                        <span class="badge badge-soft-info fs-6 px-3 py-1 rounded-pill">
                            {{ $warehouse->coils_count ?? $warehouse->coils()->count() }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

     <div class="col-xl-12 col-lg-12 col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-bottom border-light p-0">
                    <ul class="nav nav-tabs nav-tabs-custom mb-0" id="yardTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active d-flex align-items-center gap-2" id="thickness-tab" data-bs-toggle="tab" data-bs-target="#thickness-content" type="button" role="tab" aria-selected="true">
                                <i class="fe fe-layers"></i>
                                <span>Thickness & Avg Rates</span>
                                <span class="badge badge-soft-primary rounded-pill">{{ $thicknessBreakdown->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link d-flex align-items-center gap-2" id="coils-tab" data-bs-toggle="tab" data-bs-target="#coils-content" type="button" role="tab" aria-selected="false">
                                <i class="fe fe-disc"></i>
                                <span>All Yard Coils</span>
                                <span class="badge bg-light text-muted border rounded-pill">{{ $coils->total() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link d-flex align-items-center gap-2" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases-content" type="button" role="tab" aria-selected="false">
                                <i class="fe fe-download"></i>
                                <span>Recent Intakes</span>
                                <span class="badge bg-light text-muted border rounded-pill">{{ $recentPurchases->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link d-flex align-items-center gap-2" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales-content" type="button" role="tab" aria-selected="false">
                                <i class="fe fe-upload"></i>
                                <span>Recent Dispatches</span>
                                <span class="badge bg-light text-muted border rounded-pill">{{ $recentSales->count() }}</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-0">
                    <div class="tab-content" id="yardTabContent">
                        <!-- TAB 1: THICKNESS & WEIGHTED AVERAGE PRICE BREAKDOWN -->
                        <div class="tab-pane fade show active" id="thickness-content" role="tabpanel" aria-labelledby="thickness-tab">
                            @if($thicknessBreakdown->isNotEmpty())
                                <div class="p-3 bg-light-subtle border-bottom d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fe fe-info text-primary fs-5"></i>
                                        <span class="small text-dark">
                                            <strong>Weighted Average Cost (AVCO)</strong> = Total Stock Valuation ÷ Total Available Weight (kg)
                                        </span>
                                    </div>
                                    <span class="badge bg-white text-dark border px-3 py-1 font-monospace">
                                        {{ $thicknessBreakdown->count() }} Thickness Specs
                                    </span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-custom align-middle mb-0">
                                        <thead class="bg-light text-secondary fs-7 text-uppercase">
                                            <tr>
                                                <th class="ps-4">Thickness</th>
                                                <th>Dimensions in Stock</th>
                                                <th class="text-center">Batches / Qty</th>
                                                <th class="text-end">Available Weight</th>
                                                <th class="text-end">Total Valuation</th>
                                                <th class="text-end">Weighted Avg Rate</th>
                                                <th class="text-end pe-4">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($thicknessBreakdown as $row)
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="avatar avatar-xs bg-primary-light text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 11px;">
                                                                T
                                                            </div>
                                                            <div>
                                                                <span class="fw-bold text-dark fs-6">{{ $row['thickness'] }}</span>
                                                                <small class="text-muted d-block" style="font-size: 10px;">Thickness</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if(!empty($row['sizes']))
                                                            <div class="d-flex flex-wrap gap-1">
                                                                @foreach($row['sizes'] as $sz)
                                                                    <span class="badge bg-light text-dark border px-2 py-1 fs-8">{{ $sz }}</span>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <span class="text-muted small">Standard Coil</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="fw-bold text-dark font-monospace">{{ $row['coils_count'] }}</span>
                                                        <small class="text-muted d-block" style="font-size: 10px;">{{ $row['pieces_count'] }} pcs</small>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="fw-bold text-primary font-monospace">{{ number_format($row['total_weight'], 2) }} kg</span>
                                                        <small class="text-muted d-block" style="font-size: 10px;">{{ number_format($row['total_weight_mt'], 3) }} MT</small>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="fw-bold text-success font-monospace">৳ {{ number_format($row['total_valuation'], 2) }}</span>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="d-inline-block text-end">
                                                            <span class="badge bg-primary-light text-primary fs-7 fw-bold px-3 py-1 font-monospace d-block">
                                                                ৳ {{ number_format($row['avg_price_per_kg'], 2) }} / kg
                                                            </span>
                                                            <small class="text-muted fw-semibold" style="font-size: 10px;">
                                                                (৳ {{ number_format($row['avg_price_per_ton'], 0) }} / MT)
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        <a href="{{ route('inventory.index', ['warehouse_id' => $warehouse->id, 'search' => $row['thickness']]) }}" class="btn btn-sm btn-outline-primary rounded-2 px-2 py-1 d-inline-flex align-items-center gap-1" title="View Coils">
                                                            <i class="fe fe-list"></i>
                                                            <span class="fs-8">View Coils</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-light-subtle border-top fw-bold">
                                            <tr>
                                                <td class="ps-4 text-uppercase text-secondary fs-8">Total Yard Stock</td>
                                                <td></td>
                                                <td class="text-center font-monospace">{{ $thicknessBreakdown->sum('coils_count') }} Coils</td>
                                                <td class="text-end text-primary font-monospace">{{ number_format($thicknessBreakdown->sum('total_weight'), 2) }} kg</td>
                                                <td class="text-end text-success font-monospace">৳ {{ number_format($thicknessBreakdown->sum('total_valuation'), 2) }}</td>
                                                <td class="text-end text-primary font-monospace">
                                                    @php
                                                        $overallWeight = $thicknessBreakdown->sum('total_weight');
                                                        $overallVal = $thicknessBreakdown->sum('total_valuation');
                                                        $overallAvg = $overallWeight > 0 ? ($overallVal / $overallWeight) : 0;
                                                    @endphp
                                                    ৳ {{ number_format($overallAvg, 2) }} / kg (Avg)
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @else
                                <div class="p-5 text-center">
                                    <div class="avatar avatar-xxl bg-light text-secondary rounded-circle mb-3 mx-auto d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                                        <i class="fe fe-layers fs-3 opacity-50"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">No active stock in this yard currently</h6>
                                    <p class="text-muted small mb-0">Thickness groups and weighted average rates will automatically calculate once stock is received.</p>
                                </div>
                            @endif
                        </div>

                        <!-- TAB 2: ALL COILS IN YARD -->
                        <div class="tab-pane fade" id="coils-content" role="tabpanel" aria-labelledby="coils-tab">
                            @if($coils->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover table-custom align-middle mb-0">
                                        <thead class="bg-light text-secondary fs-7 text-uppercase">
                                            <tr>
                                                <th class="ps-4">Coil Number</th>
                                                <th>Specification</th>
                                                <th class="text-end">Initial Wt</th>
                                                <th class="text-end">Remaining Wt</th>
                                                <th class="text-end">Est. Value</th>
                                                <th>Lot / Vendor</th>
                                                <th class="text-end pe-4">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($coils as $coil)
                                                <tr>
                                                    <td class="ps-4">
                                                        <span class="fw-bold font-monospace text-dark">{{ $coil->coil_number }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-semibold text-dark">{{ $coil->thickness ?: '—' }}</span>
                                                        @if($coil->width || $coil->length)
                                                            <small class="text-muted d-block">{{ $coil->width }} {{ $coil->length }}</small>
                                                        @endif
                                                    </td>
                                                    <td class="text-end text-muted font-monospace">
                                                        {{ number_format($coil->net_weight ?? $coil->gross_weight, 2) }} kg
                                                    </td>
                                                    <td class="text-end fw-bold text-primary font-monospace">
                                                        {{ number_format($coil->remaining_weight, 2) }} kg
                                                    </td>
                                                    <td class="text-end fw-bold text-success font-monospace">
                                                        ৳ {{ number_format($coil->total_price, 2) }}
                                                    </td>
                                                    <td class="small">
                                                        @if($coil->lot)
                                                            <span class="fw-medium text-dark d-block">Lot: {{ $coil->lot->lot_number }}</span>
                                                        @endif
                                                        <span class="text-muted">{{ $coil->vendor?->name ?? '—' }}</span>
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        @if($coil->status === 'in_stock')
                                                            <span class="badge badge-soft-success px-3 py-1 rounded-pill"><i class="fe fe-check-circle me-1"></i>In Stock</span>
                                                        @elseif($coil->status === 'processing')
                                                            <span class="badge badge-soft-warning px-3 py-1 rounded-pill"><i class="fe fe-activity me-1"></i>Processing</span>
                                                        @elseif($coil->status === 'reserved')
                                                            <span class="badge badge-soft-info px-3 py-1 rounded-pill"><i class="fe fe-bookmark me-1"></i>Reserved</span>
                                                        @elseif($coil->status === 'exhausted')
                                                            <span class="badge badge-soft-secondary px-3 py-1 rounded-pill"><i class="fe fe-archive me-1"></i>Exhausted</span>
                                                        @elseif($coil->status === 'scrapped')
                                                            <span class="badge badge-soft-danger px-3 py-1 rounded-pill"><i class="fe fe-trash-2 me-1"></i>Scrapped</span>
                                                        @else
                                                            <span class="badge bg-light text-muted border px-3 py-1 rounded-pill">{{ ucfirst(str_replace('_', ' ', $coil->status)) }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if($coils->hasPages())
                                    <div class="p-3 border-top">
                                        {{ $coils->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="p-5 text-center">
                                    <div class="avatar avatar-xxl bg-light text-secondary rounded-circle mb-3 mx-auto d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                                        <i class="fe fe-disc fs-3 opacity-50"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">No coils stored in this yard currently</h6>
                                    <p class="text-muted small mb-0">Purchases and coils assigned to {{ $warehouse->name }} will automatically appear here.</p>
                                </div>
                            @endif
                        </div>

                        <!-- TAB 2: RECENT INWARD PURCHASES -->
                        <div class="tab-pane fade" id="purchases-content" role="tabpanel" aria-labelledby="purchases-tab">
                            @if($recentPurchases->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover table-custom align-middle mb-0">
                                        <thead class="bg-light text-secondary fs-7 text-uppercase">
                                            <tr>
                                                <th class="ps-4">PO Number</th>
                                                <th>Date</th>
                                                <th>Vendor</th>
                                                <th>Specs</th>
                                                <th class="text-end">Weight</th>
                                                <th class="text-end">Total Amount</th>
                                                <th class="text-center">Settlement</th>
                                                <th class="text-end pe-4">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($recentPurchases as $p)
                                                <tr>
                                                    <td class="ps-4">
                                                        <a href="{{ route('purchase.show', $p->id) }}" class="fw-bold text-primary font-monospace">
                                                            #PO-{{ $p->id }}
                                                        </a>
                                                    </td>
                                                    <td class="text-dark small">
                                                        {{ $p->created_at ? $p->created_at->format('d M, Y') : '—' }}
                                                    </td>
                                                    <td class="fw-medium text-dark small">
                                                        {{ $p->vendor?->name ?? '—' }}
                                                    </td>
                                                    <td>
                                                        <span class="small fw-semibold text-dark">{{ $p->thickness ?: '—' }}</span>
                                                        @if($p->size)
                                                            <small class="text-muted">| {{ $p->size }}</small>
                                                        @endif
                                                    </td>
                                                    <td class="text-end font-monospace text-muted small">
                                                        {{ number_format($p->total_weight, 2) }} kg
                                                    </td>
                                                    <td class="text-end fw-bold text-dark font-monospace">
                                                        ৳ {{ number_format($p->total_price, 2) }}
                                                    </td>
                                                    <td class="text-center">
                                                        @if($p->due > 0)
                                                            <span class="badge badge-soft-danger px-2 py-1 rounded-pill fs-8">Due: ৳{{ number_format($p->due, 0) }}</span>
                                                        @else
                                                            <span class="badge badge-soft-success px-2 py-1 rounded-pill fs-8">Settled</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        <a href="{{ route('purchase.show', $p->id) }}" class="btn btn-sm btn-outline-primary rounded-2 px-2 py-1" title="View Purchase Order">
                                                            <i class="fe fe-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="p-5 text-center">
                                    <div class="avatar avatar-xxl bg-light text-secondary rounded-circle mb-3 mx-auto d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                                        <i class="fe fe-shopping-cart fs-3 opacity-50"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">No purchase intakes recorded for this yard</h6>
                                    <p class="text-muted small mb-0">Intake purchase orders linked to this yard location will display here.</p>
                                </div>
                            @endif
                        </div>

                        <!-- TAB 3: RECENT DISPATCHES / SALES -->
                        <div class="tab-pane fade" id="sales-content" role="tabpanel" aria-labelledby="sales-tab">
                            @if($recentSales->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover table-custom align-middle mb-0">
                                        <thead class="bg-light text-secondary fs-7 text-uppercase">
                                            <tr>
                                                <th class="ps-4">Order #</th>
                                                <th>Date</th>
                                                <th>Customer</th>
                                                <th class="text-end">Dispatched Qty</th>
                                                <th class="text-end">Total Amount</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-end pe-4">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($recentSales as $sale)
                                                <tr>
                                                    <td class="ps-4">
                                                        <a href="{{ route('sales.show', $sale->id) }}" class="fw-bold text-primary font-monospace">
                                                            {{ $sale->order_no }}
                                                        </a>
                                                    </td>
                                                    <td class="text-dark small">
                                                        {{ $sale->order_date ? \Carbon\Carbon::parse($sale->order_date)->format('d M, Y') : ($sale->created_at ? $sale->created_at->format('d M, Y') : '—') }}
                                                    </td>
                                                    <td class="fw-medium text-dark small">
                                                        {{ $sale->customer?->name ?? '—' }}
                                                    </td>
                                                    <td class="text-end font-monospace text-muted small">
                                                        {{ number_format($sale->qty, 2) }}
                                                    </td>
                                                    <td class="text-end fw-bold text-dark font-monospace">
                                                        ৳ {{ number_format($sale->total, 2) }}
                                                    </td>
                                                    <td class="text-center">
                                                        @if($sale->status === 'paid')
                                                            <span class="badge badge-soft-success px-2 py-1 rounded-pill fs-8">Paid</span>
                                                        @elseif($sale->status === 'partial')
                                                            <span class="badge badge-soft-warning px-2 py-1 rounded-pill fs-8">Partial</span>
                                                        @else
                                                            <span class="badge badge-soft-danger px-2 py-1 rounded-pill fs-8">{{ ucfirst($sale->status) }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-sm btn-outline-primary rounded-2 px-2 py-1" title="View Sale Order">
                                                            <i class="fe fe-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="p-5 text-center">
                                    <div class="avatar avatar-xxl bg-light text-secondary rounded-circle mb-3 mx-auto d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                                        <i class="fe fe-truck fs-3 opacity-50"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">No sales dispatches recorded for this yard</h6>
                                    <p class="text-muted small mb-0">Dispatched orders lifted from this yard will display here.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

</div>
@endsection
