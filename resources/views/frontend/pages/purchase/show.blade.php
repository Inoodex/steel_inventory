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
    .info-table td {
        padding: 0.65rem 0.5rem;
        vertical-align: middle;
    }
    .table-custom th, .table-custom td {
        white-space: nowrap;
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
                    <i class="fe fe-shopping-cart text-primary me-2"></i>Purchase Order #PO-{{ $purchase->id }}
                </h4>
                <p class="text-muted small mb-0">
                    Recorded on {{ $purchase->created_at ? $purchase->created_at->format('d M Y, h:i A') : 'N/A' }} 
                    • Status: 
                    @if($purchase->due > 0)
                        <span class="badge badge-soft-danger ms-1">Outstanding Due</span>
                    @else
                        <span class="badge badge-soft-success ms-1">Fully Settled</span>
                    @endif
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('purchase.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back to Purchases</span>
                </a>

                @if($purchase->due > 0)
                    <button type="button" class="btn btn-success px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2 text-white fw-semibold"
                        onclick="openPurchaseDueModal('{{ $purchase->id }}', '{{ $purchase->vendor_id }}', '{{ addslashes($purchase->vendor->name ?? 'Vendor') }}', '{{ $purchase->due }}')">
                        <i class="fe fe-dollar-sign"></i>
                        <span>Pay Due (৳{{ number_format($purchase->due, 2) }})</span>
                    </button>
                @endif

                <button type="button" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#edit-purchase-modal">
                    <i class="fe fe-edit"></i>
                    <span>Edit Purchase</span>
                </button>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Summary Stats Bar -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Order Value</h6>
                        <h4 class="mb-0 fw-bold text-dark">৳ {{ number_format($purchase->total_price, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-layers fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Weight</h6>
                        <h4 class="mb-0 fw-bold text-dark">
                            {{ number_format($purchase->total_weight ?? 0, 2) }} kg
                            @if(($purchase->total_weight ?? 0) >= 1000)
                                <small class="fs-7 text-muted fw-normal">({{ number_format($purchase->total_weight / 1000, 3) }} MT)</small>
                            @endif
                        </h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-check-circle fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Paid Amount</h6>
                        <h4 class="mb-0 fw-bold text-success">৳ {{ number_format($purchase->payment, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg {{ $purchase->due > 0 ? 'bg-danger-light text-danger' : 'bg-success-light text-success' }} rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe {{ $purchase->due > 0 ? 'fe-alert-circle' : 'fe-shield' }} fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Outstanding Due</h6>
                        <h4 class="mb-0 fw-bold {{ $purchase->due > 0 ? 'text-danger' : 'text-success' }}">
                            @if($purchase->due > 0)
                                ৳ {{ number_format($purchase->due, 2) }}
                            @else
                                Paid in Full
                            @endif
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Summary Stats Bar -->

    <!-- Main Details Row -->
    <div class="row g-4 mb-4">
        <!-- Vendor Information Card -->
        <div class="col-lg-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-user text-primary me-2"></i>Vendor / Supplier Details
                    </h5>
                    @if($purchase->vendor)
                        <a href="{{ route('vendors.show', $purchase->vendor->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="fe fe-external-link me-1"></i>View Profile
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    <table class="table table-borderless info-table mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width: 35%;">Supplier Name:</td>
                                <td class="fw-bold text-dark">{{ $purchase->vendor->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Contact Phone:</td>
                                <td class="fw-semibold text-dark">{{ $purchase->vendor->phone ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Email Address:</td>
                                <td class="text-secondary">{{ $purchase->vendor->email ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Address / Mill Location:</td>
                                <td class="text-secondary">{{ $purchase->vendor->address ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Overall Vendor Due:</td>
                                <td>
                                    @php
                                        $vendorTotalDue = $purchase->vendor ? $purchase->vendor->purchases()->sum('due') : 0;
                                    @endphp
                                    <span class="badge {{ $vendorTotalDue > 0 ? 'badge-soft-danger' : 'badge-soft-success' }} px-3 py-1 rounded-pill fs-7">
                                        ৳ {{ number_format($vendorTotalDue, 2) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Lot & Logistics Card -->
        <div class="col-lg-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-package text-primary me-2"></i>Lot & Logistics Information
                    </h5>
                    @if($purchase->lot)
                        <a href="{{ route('lots.show', $purchase->lot->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="fe fe-package me-1"></i>View Lot
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    <table class="table table-borderless info-table mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width: 35%;">Lot Reference:</td>
                                <td>
                                    @if($purchase->lot)
                                        <a href="{{ route('lots.show', $purchase->lot->id) }}" class="fw-bold text-primary text-decoration-none">
                                            <i class="fe fe-package me-1"></i>{{ $purchase->lot->lot_number }}
                                        </a>
                                    @else
                                        <span class="badge bg-light text-muted border">Direct Stock</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Stockyard / Depot:</td>
                                <td class="fw-bold text-dark">
                                    <i class="fe fe-map-pin text-danger me-1"></i>
                                    {{ $purchase->warehouse ? $purchase->warehouse->name : 'Main Stockyard' }}
                                    @if($purchase->warehouse && $purchase->warehouse->location)
                                        <small class="text-muted fw-normal d-block">({{ $purchase->warehouse->location }})</small>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Intake Date:</td>
                                <td class="fw-semibold text-dark">{{ $purchase->created_at ? $purchase->created_at->format('d M Y') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Recorded By:</td>
                                <td class="text-secondary">{{ $purchase->creator ? $purchase->creator->name : 'Super Admin' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Last Updated:</td>
                                <td class="text-secondary">{{ $purchase->updated_at ? $purchase->updated_at->format('d M Y') : 'N/A' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Steel Specs & Financial Breakdown Row -->
    <div class="row g-4 mb-4">
        <!-- Specification Breakdown -->
        <div class="col-lg-7 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-disc text-primary me-2"></i>Steel Specifications & Weight Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered border-light align-middle mb-0">
                            <thead class="bg-light text-secondary fs-7 text-uppercase">
                                <tr>
                                    <th>Specification</th>
                                    <th>Quantity</th>
                                    <th>Unit Weight</th>
                                    <th>Total Net Weight</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-dark d-block">
                                            {{ $purchase->thickness ? $purchase->thickness : 'Standard Thickness' }}
                                        </span>
                                        @if($purchase->size)
                                            <small class="text-secondary">Size: {{ $purchase->size }} {{ $purchase->size_type ? "({$purchase->size_type})" : '' }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1 fs-8 fw-bold">
                                            {{ (int) $purchase->quantity }} {{ Str::plural('Coil', (int)$purchase->quantity) }}
                                        </span>
                                    </td>
                                    <td class="fw-semibold text-dark">
                                        {{ number_format($purchase->unit_weight ?? 0, 3) }} kg
                                    </td>
                                    <td>
                                        <span class="fw-bold text-primary fs-6">{{ number_format($purchase->total_weight ?? 0, 2) }} kg</span>
                                        @if(($purchase->total_weight ?? 0) >= 1000)
                                            <small class="text-muted d-block font-monospace">({{ number_format($purchase->total_weight / 1000, 3) }} MT)</small>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <span class="text-muted small d-block">Unit Rate / Price</span>
                                <span class="fs-6 fw-bold text-dark">৳ {{ number_format($purchase->unit_price, 2) }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <span class="text-muted small d-block">Sub Total</span>
                                <span class="fs-6 fw-bold text-dark">৳ {{ number_format($purchase->sub_price ?: $purchase->total_price, 2) }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-primary-light rounded-3 text-center">
                                <span class="text-primary small d-block fw-semibold">Grand Payable Bill</span>
                                <span class="fs-5 fw-bold text-primary">৳ {{ number_format($purchase->total_price, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment & Settlement Info -->
        <div class="col-lg-5 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-credit-card text-primary me-2"></i>Payment & Settlement
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless info-table mb-3">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width: 45%;">Disbursed Payment:</td>
                                <td class="fw-bold text-success fs-6">৳ {{ number_format($purchase->payment, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Payment Channel:</td>
                                <td>
                                    @if($purchase->payment_method === 'bank')
                                        <span class="badge badge-soft-primary px-3 py-1 rounded-pill">Bank Transfer</span>
                                    @elseif($purchase->payment_method === 'mobile_banking')
                                        <span class="badge badge-soft-info px-3 py-1 rounded-pill">MFS (bKash/Nagad)</span>
                                    @else
                                        <span class="badge badge-soft-success px-3 py-1 rounded-pill">Cash in Hand</span>
                                    @endif
                                </td>
                            </tr>
                            @if($purchase->bankDetail)
                                <tr>
                                    <td class="text-muted">Bank Account:</td>
                                    <td class="fw-semibold text-dark">
                                        {{ $purchase->bankDetail->bank_name }}
                                        <small class="text-muted d-block fs-8">{{ $purchase->bankDetail->account_number }}</small>
                                    </td>
                                </tr>
                            @endif
                            @if($purchase->transaction_ref)
                                <tr>
                                    <td class="text-muted">Transaction Ref:</td>
                                    <td class="text-dark font-monospace">{{ $purchase->transaction_ref }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td class="text-muted">Outstanding Due:</td>
                                <td>
                                    @if($purchase->due > 0)
                                        <span class="badge badge-soft-danger px-3 py-1 rounded-pill fs-6 fw-bold">
                                            ৳ {{ number_format($purchase->due, 2) }}
                                        </span>
                                    @else
                                        <span class="badge badge-soft-success px-3 py-1 rounded-pill fs-7 fw-bold">
                                            Paid in Full
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    @if($purchase->due > 0)
                        <button type="button" class="btn btn-success w-100 py-2 rounded-3 shadow-sm fw-semibold text-white d-flex align-items-center justify-content-center gap-2"
                            onclick="openPurchaseDueModal('{{ $purchase->id }}', '{{ $purchase->vendor_id }}', '{{ addslashes($purchase->vendor->name ?? 'Vendor') }}', '{{ $purchase->due }}')">
                            <i class="fe fe-dollar-sign"></i>
                            <span>Disburse Due Payment</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Coils Stored Under this Purchase (if any) -->
    @if($purchase->coils && $purchase->coils->count() > 0)
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center">
                <h5 class="card-title fw-bold text-dark mb-0">
                    <i class="fe fe-disc text-primary me-2"></i>Physical Coils / Inventory Stock ({{ $purchase->coils->count() }} Coils)
                </h5>
                <a href="{{ route('coils.index', ['search' => $purchase->lot ? $purchase->lot->lot_number : '']) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="fe fe-external-link me-1"></i>View in Coils Registry
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle mb-0">
                        <thead class="bg-light text-secondary fs-7 text-uppercase">
                            <tr>
                                <th class="ps-4">Coil Number</th>
                                <th>Specification</th>
                                <th>Weight (Initial)</th>
                                <th>Weight (Remaining)</th>
                                <th>Yard Location</th>
                                <th class="text-end pe-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->coils as $coil)
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold font-monospace text-dark">{{ $coil->coil_number }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark">{{ $coil->thickness }}</span>
                                        @if($coil->size)
                                            <span class="text-muted">| {{ $coil->size }}</span>
                                        @endif
                                    </td>
                                    <td class="text-secondary fw-medium">{{ number_format($coil->weight, 2) }} kg</td>
                                    <td class="fw-bold text-primary">{{ number_format($coil->remaining_weight, 2) }} kg</td>
                                    <td>
                                        <span class="text-muted small">
                                            <i class="fe fe-map-pin me-1"></i>{{ $coil->warehouse ? $coil->warehouse->name : ($purchase->warehouse ? $purchase->warehouse->name : 'Main Yard') }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        @if($coil->status === 'in_stock')
                                            <span class="badge badge-soft-success px-3 py-1 rounded-pill">In Stock</span>
                                        @elseif($coil->status === 'cutting')
                                            <span class="badge badge-soft-warning px-3 py-1 rounded-pill">Cutting</span>
                                        @elseif($coil->status === 'sold')
                                            <span class="badge badge-soft-info px-3 py-1 rounded-pill">Sold</span>
                                        @else
                                            <span class="badge bg-light text-muted border px-3 py-1 rounded-pill">{{ ucfirst($coil->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

</div>

<!-- Edit Purchase Modal (Outside tables as per AGENTS.md rules) -->
<div class="modal fade" id="edit-purchase-modal" aria-hidden="true" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light py-3 border-bottom">
                <h5 class="modal-title fw-bold text-dark">Edit Purchase #PO-{{ $purchase->id }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('purchase.update', $purchase->id) }}">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit-lot_id" class="form-label fw-semibold small text-secondary">Purchase Lot <span class="text-danger">*</span></label>
                            <select id="edit-lot_id" name="lot_id" class="form-select select2" required>
                                <option value="">Select Purchase Lot</option>
                                @foreach (\App\Models\Lot::where('status', 'active')->get() as $lot)
                                    <option value="{{ $lot->id }}" data-vendor-id="{{ $lot->vendor_id }}" {{ $lot->id == $purchase->lot_id ? 'selected' : '' }}>
                                        {{ $lot->lot_number }} — {{ $lot->vendor ? $lot->vendor->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="edit-warehouse_id" class="form-label fw-semibold small text-secondary">Stockyard / Warehouse</label>
                            <select id="edit-warehouse_id" name="warehouse_id" class="form-select select2">
                                <option value="">Select Stockyard / Warehouse</option>
                                @foreach (\App\Models\Warehouse::where('status', 'active')->orderBy('name')->get() as $wh)
                                    <option value="{{ $wh->id }}" {{ $wh->id == $purchase->warehouse_id ? 'selected' : '' }}>
                                        {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Vendor <span class="text-danger">*</span></label>
                            <input type="text" id="edit-vendor-display" class="form-control bg-light" readonly value="{{ $purchase->vendor ? $purchase->vendor->name : '' }}">
                            <input type="hidden" name="vendor_id" id="edit-vendor-hidden" value="{{ $purchase->vendor_id }}">
                        </div>

                        <div class="col-md-3">
                            <label for="edit-thickness" class="form-label fw-semibold small text-secondary">Thickness</label>
                            <input id="edit-thickness" name="thickness" value="{{ $purchase->thickness }}" class="form-control" placeholder="e.g. 16mm" />
                        </div>
                        <div class="col-md-3">
                            <label for="edit-size" class="form-label fw-semibold small text-secondary">Size / Length</label>
                            <input id="edit-size" name="size" value="{{ $purchase->size }}" class="form-control" placeholder="e.g. 12m" />
                        </div>

                        <div class="col-md-4">
                            <label for="edit-unit_weight" class="form-label fw-semibold small text-secondary">Unit Weight (kg)</label>
                            <input type="number" step="0.001" id="edit-unit_weight" name="unit_weight" value="{{ $purchase->unit_weight }}" class="form-control" placeholder="0.000" />
                        </div>
                        <div class="col-md-4">
                            <label for="edit-quantity" class="form-label fw-semibold small text-secondary">Quantity (Coils)</label>
                            <input id="edit-quantity" name="quantity" value="{{ $purchase->quantity }}" class="form-control" placeholder="Quantity" />
                        </div>
                        <div class="col-md-4">
                            <label for="edit-unit_price" class="form-label fw-semibold small text-secondary">Unit Cost Rate (৳)</label>
                            <input id="edit-unit_price" name="unit_price" value="{{ $purchase->unit_price }}" class="form-control" placeholder="Unit Price" />
                        </div>

                        <div class="col-md-4">
                            <label for="edit-sub_price" class="form-label fw-semibold small text-secondary">Sub Price (৳)</label>
                            <input id="edit-sub_price" name="sub_price" value="{{ $purchase->sub_price }}" class="form-control bg-light" readonly />
                        </div>
                        <div class="col-md-4">
                            <label for="edit-total_price" class="form-label fw-semibold small text-secondary">Payable Total Price (৳)</label>
                            <input id="edit-total_price" name="total_price" value="{{ $purchase->total_price }}" class="form-control" />
                        </div>
                        <div class="col-md-4">
                            <label for="edit-payment" class="form-label fw-semibold small text-secondary">Payment (৳)</label>
                            <input id="edit-payment" name="payment" value="{{ $purchase->payment }}" class="form-control" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-light px-4 rounded-3 text-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-3 shadow-sm">Update Purchase</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Purchase Due Settlement Modal (outside table structure) -->
<div class="modal fade" id="purchaseDuePaymentModal" tabindex="-1" aria-labelledby="purchaseDuePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="purchaseDuePaymentModalLabel">
                    <i class="fe fe-dollar-sign me-2 text-success"></i>Pay Purchase Due
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('vendor-payments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="vendor_id" id="purchaseModalVendorId" value="">
                <input type="hidden" name="purchase_id" id="purchaseModalPurchaseId" value="">

                <div class="modal-body p-4">
                    <div class="alert alert-light border rounded-3 mb-3 p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary small fw-semibold">Vendor:</span>
                            <span class="text-dark fw-bold" id="purchaseModalVendorName">Vendor</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="text-secondary small fw-semibold">Purchase Order:</span>
                            <span class="text-primary fw-bold font-monospace" id="purchaseModalPoNumber">#PO</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="text-secondary small fw-semibold">Outstanding Due:</span>
                            <span class="text-danger fw-bold fs-6" id="purchaseModalMaxDueDisplay">৳ 0.00</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold mb-1">Disbursement Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" id="purchaseModalPaymentMethod" class="form-select border-light-subtle" required onchange="togglePurchaseDueModalBank(this.value)">
                            <option value="cash" selected>Cash in Hand</option>
                            <option value="bank">Bank Transfer / MFS</option>
                        </select>
                    </div>

                    <!-- Bank Account Selector -->
                    <div class="mb-3" id="purchaseModalBankContainer" style="display: none;">
                        <label class="form-label small text-secondary fw-semibold mb-1">Disbursement Bank / Wallet Account <span class="text-danger">*</span></label>
                        <select name="bank_detail_id" id="purchaseModalBankDetail" class="form-select border-light-subtle">
                            <option value="">Select Bank / MFS Account</option>
                            @foreach($bankAccounts ?? [] as $bank)
                                <option value="{{ $bank->id }}" {{ $bank->is_default ? 'selected' : '' }}>
                                    {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Transaction Ref -->
                    <div class="mb-3" id="purchaseModalRefContainer" style="display: none;">
                        <label class="form-label small text-secondary fw-semibold mb-1">Transaction Ref / TrxID</label>
                        <input type="text" name="transaction_ref" class="form-control border-light-subtle" placeholder="e.g. Bank Trx # or TrxID">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold mb-1">Payment Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="purchaseModalAmount" class="form-control fw-bold text-success fs-5 border-light-subtle" placeholder="0.00" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold mb-1">Remarks / Note</label>
                        <textarea name="remarks" class="form-control border-light-subtle" rows="2" placeholder="Optional payment note..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-outline-secondary px-3 py-2 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 py-2 rounded-3 fw-semibold text-white">
                        <i class="fe fe-check-circle me-1"></i>Confirm Disbursement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openPurchaseDueModal(purchaseId, vendorId, vendorName, maxDue) {
        document.getElementById('purchaseModalPurchaseId').value = purchaseId;
        document.getElementById('purchaseModalVendorId').value = vendorId;
        document.getElementById('purchaseModalVendorName').textContent = vendorName;
        document.getElementById('purchaseModalPoNumber').textContent = '#PO-' + purchaseId;

        const numMax = parseFloat(maxDue) || 0;
        document.getElementById('purchaseModalMaxDueDisplay').textContent = '৳ ' + numMax.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const amountInput = document.getElementById('purchaseModalAmount');
        amountInput.value = numMax > 0 ? numMax.toFixed(2) : '';
        amountInput.max = numMax > 0 ? numMax : '';

        const modalEl = document.getElementById('purchaseDuePaymentModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    function togglePurchaseDueModalBank(method) {
        const bankContainer = document.getElementById('purchaseModalBankContainer');
        const refContainer = document.getElementById('purchaseModalRefContainer');
        if (!bankContainer || !refContainer) return;

        if (method === 'cash') {
            bankContainer.style.display = 'none';
            refContainer.style.display = 'none';
        } else {
            bankContainer.style.display = 'block';
            refContainer.style.display = 'block';
        }
    }
</script>
@endpush
@endsection
