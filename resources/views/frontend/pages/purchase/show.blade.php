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
    .info-table td {
        padding: 0.65rem 0.5rem;
        vertical-align: middle;
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
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h4 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-shopping-cart text-primary me-2"></i>Purchase Consignment #PO-{{ $purchase->id }}
                    </h4>
                </div>
                <p class="text-muted small mb-0 mt-1">
                    Recorded on {{ $purchase->created_at ? $purchase->created_at->format('d M Y') : 'N/A' }} 
                    • Consignment items: {{ $consignmentPurchases->count() }} {{ Str::plural('line item', $consignmentPurchases->count()) }} ({{ $consignmentTotalQty }} Coils)
                </p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if($purchase->lot)
                    <a href="{{ route('lots.show', $purchase->lot->id) }}" class="btn btn-outline-primary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                        <i class="fe fe-package"></i>
                        <span>View Lot Profile</span>
                    </a>
                @endif

                <a href="{{ route('purchase.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back to Purchases</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Summary Stats Bar (Full Consignment Aggregates) -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Consignment Total Bill</h6>
                        <h4 class="mb-0 fw-bold text-dark">৳ {{ number_format($consignmentGrandTotal, 2) }}</h4>
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
                        <h6 class="text-muted fw-normal mb-1">Total Net Weight</h6>
                        <h4 class="mb-0 fw-bold text-dark">
                            {{ number_format($consignmentTotalWeight, 2) }} kg
                        </h4>
                        @if($consignmentTotalWeight >= 1000)
                            <small class="text-muted font-monospace d-block fs-8">({{ number_format($consignmentTotalWeight / 1000, 3) }} MT)</small>
                        @endif
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
                        <h6 class="text-muted fw-normal mb-1">Total Disbursed Payment</h6>
                        <h4 class="mb-0 fw-bold text-success">৳ {{ number_format($consignmentPayment, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg {{ $consignmentDue > 0 ? 'bg-danger-light text-danger' : 'bg-success-light text-success' }} rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe {{ $consignmentDue > 0 ? 'fe-alert-circle' : 'fe-shield' }} fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Outstanding Due</h6>
                        <h4 class="mb-0 fw-bold {{ $consignmentDue > 0 ? 'text-danger' : 'text-success' }}">
                            @if($consignmentDue > 0)
                                ৳ {{ number_format($consignmentDue, 2) }}
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

    <!-- Main Details Row (Vendor Info & Lot Logistics) -->
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
                        <i class="fe fe-package text-primary me-2"></i>Consignment Logistics & Lot
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
                                        <span class="badge bg-light text-dark border ms-2 fs-8">{{ $purchase->lot->status ?? 'Active' }}</span>
                                    @else
                                        <span class="badge bg-light text-muted border">Direct Stock Intake</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Stockyard Depot:</td>
                                <td class="fw-bold text-dark">
                                    <i class="fe fe-map-pin text-danger me-1"></i>
                                    @php
                                        $warehouses = $consignmentPurchases->map(fn($p) => $p->warehouse?->name)->filter()->unique();
                                    @endphp
                                    @if($warehouses->isNotEmpty())
                                        {{ $warehouses->implode(', ') }}
                                    @else
                                        Main Stockyard
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Intake Date:</td>
                                <td class="fw-semibold text-dark">{{ $purchase->created_at ? $purchase->created_at->format('d M Y') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Consignment Scope:</td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2.5 py-1 fs-8">
                                        <i class="fe fe-layers text-primary me-1"></i>{{ $consignmentPurchases->count() }} Line Items
                                    </span>
                                    <span class="badge bg-light text-dark border px-2.5 py-1 fs-8 ms-1">
                                        <i class="fe fe-disc text-primary me-1"></i>{{ $consignmentTotalQty }} Coils / Pieces
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Recorded By:</td>
                                <td class="text-secondary">{{ $purchase->creator ? $purchase->creator->name : 'Super Admin' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Received Steel Items & Specifications Table (Full Consignment Items) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="card-title fw-bold text-dark mb-0">
                    <i class="fe fe-disc text-primary me-2"></i>Received Steel Items & Coils
                </h5>
                <small class="text-muted">All steel line items, coil tags, weights, and pure steel rates in this consignment</small>
            </div>
            @if($purchase->lot)
                <a href="{{ route('purchase.create') }}?lot_id={{ $purchase->lot->id }}" class="btn btn-sm btn-primary rounded-pill px-3 shadow-none fw-semibold">
                    <i class="fe fe-plus me-1"></i>Add Coil to Consignment
                </a>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0">
                    <thead class="bg-light text-secondary fs-8 text-uppercase">
                        <tr>
                            <th class="ps-4" style="width: 40px;">#</th>
                            <th>Specifications</th>
                            <th>Coil Tag / Id</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Per Coil Wt</th>
                            <th class="text-end">Total Net Wt</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Line Subtotal</th>
                            <!-- <th>Stockyard</th> -->
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($consignmentPurchases as $item)
                            <tr>
                                <td class="ps-4 text-muted fw-semibold fs-8">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex flex-wrap align-items-center gap-1">
                                        @if($item->thickness)
                                            <span class="badge bg-light text-dark border">
                                                <i class="fe fe-layers me-1 text-primary"></i>{{ $item->thickness }}
                                            </span>
                                        @endif
                                        @if($item->size)
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                                <i class="fe fe-maximize-2 me-1"></i>{{ $item->size }} {{ $item->size_type ? "({$item->size_type})" : '' }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($item->notes)
                                        <div class="small text-muted mt-1"><i class="fe fe-tag me-1 text-info"></i>{{ $item->notes }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($item->coils && $item->coils->count() > 0)
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($item->coils as $coil)
                                                <a href="{{ route('coils.index', ['search' => $coil->coil_number]) }}" class="badge bg-white text-dark border font-monospace text-decoration-none shadow-none" title="View in Coils Registry">
                                                    {{ $coil->coil_number }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted fs-8">—</span>
                                    @endif
                                </td>
                                <td class="text-center fw-bold">{{ (int) $item->quantity }}</td>
                                <td class="text-end font-monospace">{{ number_format($item->unit_weight, 2) }} kg</td>
                                <td class="text-end font-monospace fw-bold text-primary">
                                    {{ number_format($item->total_weight, 2) }} kg
                                    @if($item->total_weight >= 1000)
                                        <small class="text-muted fw-normal d-block">({{ number_format($item->total_weight / 1000, 3) }} MT)</small>
                                    @endif
                                </td>
                                <td class="text-end font-monospace">৳ {{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end font-monospace fw-bold text-success">
                                    ৳ {{ number_format($item->sub_price ?: ($item->unit_price * $item->total_weight), 2) }}
                                </td>
                                <!-- <td>
                                    <span class="text-muted small">
                                        <i class="fe fe-map-pin me-1 text-danger"></i>{{ $item->warehouse ? $item->warehouse->name : 'Main Stockyard' }}
                                    </span>
                                </td> -->
                                <td class="text-end pe-4" onclick="event.stopPropagation()">
                                    <div class="dropdown">
                                        <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('purchase.edit', $item->id) }}">
                                                    <i class="fe fe-edit text-warning"></i>
                                                    <span>Edit Line Item</span>
                                                </a>
                                            </li>
                                            @if($item->coils && $item->coils->count() > 0)
                                                <li>
                                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('coils.index', ['search' => $item->coils->first()->coil_number]) }}">
                                                        <i class="fe fe-disc text-info"></i>
                                                        <span>View Attached Coils</span>
                                                    </a>
                                                </li>
                                            @endif
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <form action="{{ route('purchase.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this item from the purchase order? Attached coils and stock will be deleted.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger border-0 bg-transparent">
                                                        <i class="fe fe-trash-2"></i>
                                                        <span>Delete Item</span>
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light border-top">
                        <tr class="fw-bold">
                            <td colspan="3" class="ps-4 text-dark">
                                Consignment Base Steel Totals ({{ $consignmentPurchases->count() }} {{ Str::plural('Item', $consignmentPurchases->count()) }})
                            </td>
                            <td class="text-center text-dark">{{ $consignmentTotalQty }} Coils</td>
                            <td></td>
                            <td class="text-end font-monospace text-primary fs-7">
                                {{ number_format($consignmentTotalWeight, 2) }} kg
                                @if($consignmentTotalWeight >= 1000)
                                    <small class="text-muted fw-normal d-block">({{ number_format($consignmentTotalWeight / 1000, 3) }} MT)</small>
                                @endif
                            </td>
                            <td></td>
                            <td class="text-end font-monospace text-success fs-6">
                                ৳ {{ number_format($consignmentSubTotal, 2) }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Consignment Financial Adjustments & Payment Settlement Row -->
    <div class="row g-4 mb-4">
        <!-- Financial Adjustments & Landed Costs Card -->
        <div class="col-lg-7 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-dollar-sign text-primary me-2"></i>Consignment Financial Adjustments & Landed Costs
                    </h5>
                    <small class="text-muted">Total extra delivery, handling charges, and supplier discount for this consignment</small>
                </div>
                <div class="card-body">
                    <table class="table table-borderless info-table mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width: 45%;">
                                    <i class="fe fe-box me-2 text-secondary"></i>Base Steel Net Subtotal:
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark fs-6">
                                    ৳ {{ number_format($consignmentSubTotal, 2) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">
                                    <i class="fe fe-truck me-2 text-primary"></i>Delivery / Freight Charge:
                                </td>
                                <td class="text-end font-monospace fw-semibold {{ $consignmentDelivery > 0 ? 'text-dark' : 'text-muted' }}">
                                    ৳ {{ number_format($consignmentDelivery, 2) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">
                                    <i class="fe fe-user-check me-2 text-info"></i>Cutting & Labour / Crane Handling:
                                </td>
                                <td class="text-end font-monospace fw-semibold {{ $consignmentLabour > 0 ? 'text-dark' : 'text-muted' }}">
                                    ৳ {{ number_format($consignmentLabour, 2) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">
                                    <i class="fe fe-activity me-2 text-warning"></i>Weight Scale Slip Fee:
                                </td>
                                <td class="text-end font-monospace fw-semibold {{ $consignmentScale > 0 ? 'text-dark' : 'text-muted' }}">
                                    ৳ {{ number_format($consignmentScale, 2) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">
                                    <i class="fe fe-plus-circle me-2 text-secondary"></i>Other Shipment Charges:
                                </td>
                                <td class="text-end font-monospace fw-semibold {{ $consignmentOther > 0 ? 'text-dark' : 'text-muted' }}">
                                    ৳ {{ number_format($consignmentOther, 2) }}
                                </td>
                            </tr>
                            @if($consignmentExtra > 0)
                                <tr class="bg-light-subtle rounded-2">
                                    <td class="text-primary fw-semibold ps-2">
                                        <i class="fe fe-trending-up me-2"></i>Total Procurement Extra Costs:
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-primary pe-2">
                                        + ৳ {{ number_format($consignmentExtra, 2) }}
                                    </td>
                                </tr>
                            @endif
                            @if($consignmentDiscount > 0)
                                <tr>
                                    <td class="text-danger fw-semibold">
                                        <i class="fe fe-tag me-2 text-danger"></i>Supplier Discount:
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-danger">
                                        - ৳ {{ number_format($consignmentDiscount, 2) }}
                                    </td>
                                </tr>
                            @endif
                            <tr class="border-top pt-2">
                                <td class="fw-bold text-dark fs-6 pt-3">
                                    <i class="fe fe-check-circle me-2 text-success"></i>Net Payable Grand Total:
                                </td>
                                <td class="text-end font-monospace fw-bold text-primary fs-5 pt-3">
                                    ৳ {{ number_format($consignmentGrandTotal, 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment Settlement Card -->
        <div class="col-lg-5 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-credit-card text-primary me-2"></i>Payment & Settlement
                    </h5>
                    <small class="text-muted">Disbursements and due settlement balance</small>
                </div>
                <div class="card-body d-flex flex-column justify-content-between">
                    <table class="table table-borderless info-table mb-3">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width: 45%;">Consignment Bill:</td>
                                <td class="font-monospace fw-bold text-dark fs-6">৳ {{ number_format($consignmentGrandTotal, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Disbursed Payment:</td>
                                <td class="font-monospace fw-bold text-success fs-6">৳ {{ number_format($consignmentPayment, 2) }}</td>
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
                                    @if($consignmentDue > 0)
                                        <span class="badge badge-soft-danger px-3 py-1 rounded-pill fs-6 fw-bold">
                                            ৳ {{ number_format($consignmentDue, 2) }}
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

                    @if($consignmentDue > 0)
                        <button type="button" class="btn btn-success w-100 py-2.5 rounded-3 shadow-sm fw-semibold text-white d-flex align-items-center justify-content-center gap-2 mt-2"
                            onclick="openPurchaseDueModal('{{ $purchase->id }}', '{{ $purchase->vendor_id }}', '{{ addslashes($purchase->vendor->name ?? 'Vendor') }}', '{{ $consignmentDue }}')">
                            <i class="fe fe-dollar-sign"></i>
                            <span>Disburse Due Payment (৳{{ number_format($consignmentDue, 2) }})</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Physical Coils / Yard Inventory Stock Table (All Coils across the Consignment) -->
    @if($allCoils && $allCoils->count() > 0)
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-disc text-primary me-2"></i>Physical Coils / Yard Inventory Stock ({{ $allCoils->count() }} Coils)
                    </h5>
                    <small class="text-muted">Track actual physical coil pieces, current stockyard location, and remaining weights</small>
                </div>
                <a href="{{ route('coils.index', ['search' => $purchase->lot ? $purchase->lot->lot_number : '']) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="fe fe-external-link me-1"></i>View in Coils Registry
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle mb-0">
                        <thead class="bg-light text-secondary fs-8 text-uppercase">
                            <tr>
                                <th class="ps-4">Coil Number</th>
                                <th>Specification</th>
                                <th>Initial Weight</th>
                                <th>Remaining Weight</th>
                                <th>Stockyard Location</th>
                                <th class="text-end pe-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allCoils as $coil)
                                <tr>
                                    <td class="ps-4">
                                        <a href="{{ route('coils.index', ['search' => $coil->coil_number]) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                                            {{ $coil->coil_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark">{{ $coil->thickness }}</span>
                                        @if($coil->width || $coil->length)
                                            <span class="text-muted">| {{ $coil->width }} {{ $coil->length }}</span>
                                        @endif
                                    </td>
                                    <td class="text-secondary fw-medium font-monospace">{{ number_format($coil->net_weight ?? $coil->gross_weight, 2) }} kg</td>
                                    <td class="fw-bold text-primary font-monospace">{{ number_format($coil->remaining_weight, 2) }} kg</td>
                                    <td>
                                        <span class="text-muted small">
                                            <i class="fe fe-map-pin me-1 text-danger"></i>{{ $coil->warehouse ? $coil->warehouse->name : ($purchase->warehouse ? $purchase->warehouse->name : 'Main Yard') }}
                                        </span>
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
            </div>
        </div>
    @endif

    <!-- Payment & Disbursement History Table (Across Consignment) -->
    @php
        $consignmentPayments = $consignmentPurchases->flatMap->payments->sortByDesc('id');
    @endphp
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="card-title fw-bold text-dark mb-0">
                    <i class="fe fe-dollar-sign text-success me-2"></i>Payment & Disbursement History
                    <span class="badge badge-soft-primary ms-2">{{ $consignmentPayments->count() }}</span>
                </h5>
                <small class="text-muted">Recorded payment vouchers for this consignment</small>
            </div>
            @if($consignmentDue > 0)
                <button type="button" class="btn btn-sm btn-success rounded-pill px-3 shadow-none fw-semibold"
                    onclick="openPurchaseDueModal('{{ $purchase->id }}', '{{ $purchase->vendor_id }}', '{{ addslashes($purchase->vendor->name ?? 'Vendor') }}', '{{ $consignmentDue }}')">
                    <i class="fe fe-plus me-1"></i>Add Disbursement
                </button>
            @endif
        </div>
        <div class="card-body p-0">
            @if($consignmentPayments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle mb-0">
                        <thead class="bg-light text-secondary fs-8 text-uppercase">
                            <tr>
                                <th class="ps-4">Voucher / Ref</th>
                                <th>Date</th>
                                <th>Channel</th>
                                <th>Account / Bank</th>
                                <th>Transaction Ref</th>
                                <th class="text-end">Amount Disbursed</th>
                                <th>Purchase Ref</th>
                                <th>Recorded By</th>
                                <th class="text-end pe-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($consignmentPayments as $pmt)
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold font-monospace text-primary">#PAY-{{ $pmt->id }}</span>
                                    </td>
                                    <td class="text-dark fw-medium">
                                        {{ $pmt->payment_date ? \Carbon\Carbon::parse($pmt->payment_date)->format('d M, Y') : $pmt->created_at->format('d M, Y') }}
                                    </td>
                                    <td>
                                        @if($pmt->payment_method === 'bank')
                                            <span class="badge badge-soft-primary px-2 py-1 rounded-pill">Bank Transfer</span>
                                        @elseif($pmt->payment_method === 'mobile_banking')
                                            <span class="badge badge-soft-info px-2 py-1 rounded-pill">MFS</span>
                                        @elseif($pmt->payment_method === 'cheque')
                                            <span class="badge badge-soft-warning px-2 py-1 rounded-pill">Cheque</span>
                                        @else
                                            <span class="badge badge-soft-success px-2 py-1 rounded-pill">Cash</span>
                                        @endif
                                    </td>
                                    <td class="text-secondary small">
                                        @if($pmt->bankDetail)
                                            <span class="fw-semibold text-dark">{{ $pmt->bankDetail->bank_name }}</span>
                                            <div class="text-muted fs-8">{{ $pmt->bankDetail->account_number }}</div>
                                        @else
                                            <span class="text-muted">Cash in Hand</span>
                                        @endif
                                    </td>
                                    <td class="font-monospace small text-muted">
                                        {{ $pmt->transaction_ref ?: '—' }}
                                    </td>
                                    <td class="text-end fw-bold text-success fs-6">
                                        ৳ {{ number_format($pmt->amount, 2) }}
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace">#PO-{{ $pmt->purchase_id }}</span>
                                    </td>
                                    <td class="text-secondary small">
                                        {{ $pmt->creator?->name ?? 'System' }}
                                    </td>
                                    <td class="text-end pe-4">
                                        <span class="badge badge-soft-success px-3 py-1 rounded-pill">
                                            <i class="fe fe-check me-1"></i>Settled
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light-subtle border-top">
                            <tr class="fw-bold">
                                <td colspan="5" class="ps-4 text-dark">Total Disbursements Recorded</td>
                                <td class="text-end text-success fs-6">৳ {{ number_format($consignmentPayments->sum('amount'), 2) }}</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="p-4 text-center">
                    <div class="avatar avatar-lg bg-light-warning text-warning rounded-circle mb-2 mx-auto d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fe fe-info fs-4"></i>
                    </div>
                    <p class="text-muted mb-1">No payment vouchers recorded for this consignment yet.</p>
                    @if($consignmentDue > 0)
                        <span class="text-danger fw-semibold small">Outstanding Due: ৳ {{ number_format($consignmentDue, 2) }}</span>
                    @endif
                </div>
            @endif
        </div>
    </div>

</div>

<!-- Purchase Due Settlement Modal (outside table structure) -->
<div class="modal fade" id="purchaseDuePaymentModal" tabindex="-1" aria-labelledby="purchaseDuePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="purchaseDuePaymentModalLabel">
                    <i class="fe fe-dollar-sign me-2 text-success"></i>Pay Consignment Due
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
                            <span class="text-secondary small fw-semibold">Consignment Outstanding Due:</span>
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
