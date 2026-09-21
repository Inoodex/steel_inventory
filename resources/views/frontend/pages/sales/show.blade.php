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

    @php
        $customer = $sale->customer;
        $items = $sale->items;
        $totalWeight = $items->sum('qty');
        $subTotal = (float)($sale->bill ?: $sale->subtotal ?: $items->sum('total_price'));
        $grandTotal = (float)($sale->payble ?: $sale->total);
        $paidAmount = (float)$sale->advanced_payment;
        $dueAmount = (float)$sale->due_payment;
        $deliveryStatus = $sale->delivery_status ?? 'pending';
    @endphp

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h4 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-shopping-cart text-primary me-2"></i>Sales Order #{{ $sale->order_no }}
                    </h4>
                    @if($dueAmount <= 0)
                        <span class="badge badge-soft-success px-3 py-1 rounded-pill"><i class="fe fe-check-circle me-1"></i>Paid in Full</span>
                    @elseif($paidAmount > 0)
                        <span class="badge badge-soft-warning px-3 py-1 rounded-pill"><i class="fe fe-activity me-1"></i>Partial Paid</span>
                    @else
                        <span class="badge badge-soft-danger px-3 py-1 rounded-pill"><i class="fe fe-alert-circle me-1"></i>Credit (Unpaid)</span>
                    @endif

                    @if($deliveryStatus === 'delivered')
                        <span class="badge badge-soft-success px-3 py-1 rounded-pill"><i class="fe fe-truck me-1"></i>Delivered</span>
                    @elseif($deliveryStatus === 'dispatched')
                        <span class="badge badge-soft-info px-3 py-1 rounded-pill"><i class="fe fe-navigation me-1"></i>Dispatched</span>
                    @elseif($deliveryStatus === 'partial_delivered')
                        <span class="badge badge-soft-warning px-3 py-1 rounded-pill"><i class="fe fe-truck me-1"></i>Partial Delivered</span>
                    @else
                        <span class="badge badge-soft-secondary px-3 py-1 rounded-pill"><i class="fe fe-clock me-1"></i>Pending Dispatch</span>
                    @endif
                </div>
                <p class="text-muted small mb-0 mt-1">
                    Invoice Date: {{ $sale->order_date ? date('d M Y', strtotime($sale->order_date)) : ($sale->created_at ? $sale->created_at->format('d M Y') : 'N/A') }}
                    • Items: {{ $items->count() }} {{ Str::plural('line item', $items->count()) }} ({{ number_format($totalWeight, 2) }} kg)
                </p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('sales.invoice.pdf', $sale->id) }}" target="_blank" class="btn btn-primary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="fe fe-download"></i>
                    <span>Download Invoice PDF</span>
                </a>
                <a href="{{ route('sales.edit', $sale->id) }}" class="btn btn-outline-warning px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-edit"></i>
                    <span>Edit Sale</span>
                </a>
                <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back to Sales</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Summary Stats Bar -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Invoice Grand Total</h6>
                        <h4 class="mb-0 fw-bold text-dark">৳ {{ number_format($grandTotal, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-disc fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Steel Sold</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalWeight, 2) }} kg</h4>
                        @if($totalWeight >= 1000)
                            <small class="text-muted font-monospace d-block fs-8">({{ number_format($totalWeight / 1000, 3) }} MT)</small>
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
                        <h6 class="text-muted fw-normal mb-1">Collected Payment</h6>
                        <h4 class="mb-0 fw-bold text-success">৳ {{ number_format($paidAmount, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg {{ $dueAmount > 0 ? 'bg-danger-light text-danger' : 'bg-success-light text-success' }} rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe {{ $dueAmount > 0 ? 'fe-alert-circle' : 'fe-shield' }} fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Outstanding Due</h6>
                        <h4 class="mb-0 fw-bold {{ $dueAmount > 0 ? 'text-danger' : 'text-success' }}">
                            @if($dueAmount > 0)
                                ৳ {{ number_format($dueAmount, 2) }}
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

    <!-- Main Details Row (Customer Info & Order Logistics) -->
    <div class="row g-4 mb-4">
        <!-- Customer Details Card -->
        <div class="col-lg-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-user text-primary me-2"></i>Customer & Buyer Details
                    </h5>
                    @if($customer)
                        <a href="{{ route('customers.ledger', $customer->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="fe fe-file-text me-1"></i>Customer Ledger
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    <table class="table table-borderless info-table mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width: 35%;">Customer Name:</td>
                                <td class="fw-bold text-dark">{{ $customer->name ?? 'Walk-in Customer' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Contact Phone:</td>
                                <td class="fw-semibold text-dark">{{ $customer->phone ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Billing Address:</td>
                                <td class="text-secondary">{{ $customer->address ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Overall Account Due:</td>
                                <td>
                                    @php
                                        $custOpening = (float)($customer->opening_balance ?? 0);
                                        $custSalesDue = (float)($customer->sales_sum_due_payment ?? 0);
                                        $custTotalDue = $custOpening + $custSalesDue;
                                    @endphp
                                    <span class="badge {{ $custTotalDue > 0 ? 'badge-soft-danger' : 'badge-soft-success' }} px-3 py-1 rounded-pill fs-7">
                                        ৳ {{ number_format($custTotalDue, 2) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Dispatch & Order Logistics Card -->
        <div class="col-lg-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-truck text-primary me-2"></i>Dispatch & Order Logistics
                    </h5>
                    <span class="badge bg-light text-secondary border px-2.5 py-1 fs-8">
                        Order #{{ $sale->order_no }}
                    </span>
                </div>
                <div class="card-body">
                    <table class="table table-borderless info-table mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width: 35%;">Warehouse / Yard:</td>
                                <td class="fw-bold text-dark">
                                    <i class="fe fe-map-pin text-danger me-1"></i>
                                    {{ $sale->warehouse->name ?? 'Main Stockyard' }} {{ $sale->warehouse && $sale->warehouse->code ? '('.$sale->warehouse->code.')' : '' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Delivery Status:</td>
                                <td>
                                    @if($deliveryStatus === 'delivered')
                                        <span class="badge badge-soft-success px-2.5 py-1 rounded-pill"><i class="fe fe-check-circle me-1"></i>Delivered</span>
                                    @elseif($deliveryStatus === 'dispatched')
                                        <span class="badge badge-soft-info px-2.5 py-1 rounded-pill"><i class="fe fe-navigation me-1"></i>Dispatched</span>
                                    @elseif($deliveryStatus === 'partial_delivered')
                                        <span class="badge badge-soft-warning px-2.5 py-1 rounded-pill"><i class="fe fe-truck me-1"></i>Partial Delivered</span>
                                    @else
                                        <span class="badge badge-soft-secondary px-2.5 py-1 rounded-pill"><i class="fe fe-clock me-1"></i>Pending</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Dispatch Notes / Transport:</td>
                                <td class="fw-semibold text-dark">{{ $sale->note ?: 'No special instructions recorded' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Sales Person:</td>
                                <td class="text-secondary">{{ $sale->salesPerson ? $sale->salesPerson->name : 'Super Admin' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Sold Steel Items & Specifications Table -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="card-title fw-bold text-dark mb-0">
                    <i class="fe fe-disc text-primary me-2"></i>Sold Steel Items & Coils ({{ $items->count() }} Items)
                </h5>
                <small class="text-muted">Specification breakdown, coil tags, standard sizes, custom sizes (admin), selling quantities, and rates</small>
            </div>
            <a href="{{ route('sales.edit', $sale->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-none fw-semibold">
                <i class="fe fe-edit me-1"></i>Edit Line Items
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0">
                    <thead class="bg-light text-secondary fs-8 text-uppercase">
                        <tr>
                            <th class="ps-4" style="width: 40px;">#</th>
                            <th>Coil Tag / Number</th>
                            <th>Specifications</th>
                            <th>Lot Source</th>
                            <th>Custom Size (Admin Only)</th>
                            <th class="text-end">Selling Rate (৳)</th>
                            <th class="text-end">Quantity (kg)</th>
                            <th class="text-end pe-4">Total Amount (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            @php
                                $coil = $item->coil ?? $item->product;
                                $coilNumber = $coil ? $coil->coil_number : ($item->name ?? 'Steel Coil');
                                $thickness = $item->thickness ?: ($coil ? $coil->thickness : '');
                                $size = $item->size ?: ($coil ? $coil->width : '');
                                $sizeType = $item->size_type ?: ($coil ? ($coil->length ?: $coil->size_type) : 'ft');
                            @endphp
                            <tr>
                                <td class="ps-4 text-muted fw-semibold fs-8">{{ $loop->iteration }}</td>
                                <td>
                                    @if($coil)
                                        <a href="{{ route('inventory.index', ['search' => $coil->coil_number]) }}" class="fw-bold font-monospace text-primary text-decoration-none" title="View in Steel Inventory">
                                            #{{ $coilNumber }}
                                        </a>
                                    @else
                                        <span class="fw-bold text-dark">#{{ $coilNumber }}</span>
                                    @endif
                                    @if($coil && ($coil->purchase_id === null || !$coil->lot_id))
                                        <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-0 fs-8 ms-1">Opening Stock</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap align-items-center gap-1">
                                        @if($thickness)
                                            <span class="badge bg-light text-dark border">
                                                <i class="fe fe-layers me-1 text-primary"></i>Thick: {{ $thickness }}
                                            </span>
                                        @endif
                                        @if($size)
                                            <span class="badge bg-light text-secondary border">
                                                <i class="fe fe-maximize-2 me-1"></i>Size: {{ $size }} {{ $sizeType }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($item->lot)
                                        <span class="badge bg-light text-dark border fs-8">
                                            <i class="fe fe-package text-primary me-1"></i>{{ $item->lot->lot_number }}
                                        </span>
                                    @elseif($coil && $coil->lot)
                                        <span class="badge bg-light text-dark border fs-8">
                                            <i class="fe fe-package text-primary me-1"></i>{{ $coil->lot->lot_number }}
                                        </span>
                                    @else
                                        <span class="badge bg-info-subtle text-info border border-info-subtle fs-8">Opening Stock</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->custom_size)
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1 fs-8" title="Internal admin custom size - Not printed on customer sales PDF">
                                            <i class="fe fe-lock me-1"></i>{{ $item->custom_size }}
                                        </span>
                                    @else
                                        <span class="text-muted fs-8">—</span>
                                    @endif
                                </td>
                                <td class="text-end font-monospace">৳ {{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end font-monospace fw-bold text-primary">
                                    {{ number_format($item->qty, 2) }} kg
                                    @if($item->qty >= 1000)
                                        <small class="text-muted fw-normal d-block">({{ number_format($item->qty / 1000, 3) }} MT)</small>
                                    @endif
                                </td>
                                <td class="text-end pe-4 font-monospace fw-bold text-success fs-7">
                                    ৳ {{ number_format($item->total_price ?: ($item->unit_price * $item->qty), 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light border-top">
                        <tr class="fw-bold">
                            <td colspan="4" class="ps-4 text-dark">
                                Sold Base Steel Totals ({{ $items->count() }} Line {{ Str::plural('Item', $items->count()) }})
                            </td>
                            <td></td>
                            <td></td>
                            <td class="text-end font-monospace text-primary fs-7">
                                {{ number_format($totalWeight, 2) }} kg
                                @if($totalWeight >= 1000)
                                    <small class="text-muted fw-normal d-block">({{ number_format($totalWeight / 1000, 3) }} MT)</small>
                                @endif
                            </td>
                            <td class="text-end pe-4 font-monospace text-success fs-6">
                                ৳ {{ number_format($subTotal, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Financial Adjustments & Settlement Grid -->
    <div class="row g-4 mb-4">
        <!-- Operational Charges & Landed Costs Card -->
        <div class="col-lg-7 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-dollar-sign text-primary me-2"></i>Invoice Charges & Adjustments Breakdown
                    </h5>
                    <small class="text-muted">Summary of base product price, added handling fees, and discounts</small>
                </div>
                <div class="card-body">
                    <table class="table table-borderless info-table mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width: 50%;">
                                    <i class="fe fe-box me-2 text-secondary"></i>Base Steel Subtotal:
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark fs-6">
                                    ৳ {{ number_format($subTotal, 2) }}
                                </td>
                            </tr>
                            @if((float)$sale->discount > 0)
                                <tr>
                                    <td class="text-danger fw-semibold">
                                        <i class="fe fe-tag me-2 text-danger"></i>Discount:
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-danger">
                                        - ৳ {{ number_format($sale->discount, 2) }}
                                    </td>
                                </tr>
                            @endif
                            @if((float)$sale->vat > 0)
                                <tr>
                                    <td class="text-muted">
                                        <i class="fe fe-percent me-2 text-info"></i>VAT ({{ number_format($sale->vat, 2) }}%):
                                    </td>
                                    <td class="text-end font-monospace fw-semibold text-dark">
                                        + ৳ {{ number_format(($subTotal * $sale->vat) / 100, 2) }}
                                    </td>
                                </tr>
                            @endif
                            @if((float)$sale->delivery_charge > 0)
                                <tr>
                                    <td class="text-muted">
                                        <i class="fe fe-truck me-2 text-primary"></i>Delivery / Transport Charge:
                                    </td>
                                    <td class="text-end font-monospace fw-semibold text-dark">
                                        + ৳ {{ number_format($sale->delivery_charge, 2) }}
                                    </td>
                                </tr>
                            @endif
                            @if((float)$sale->labour_cost > 0)
                                <tr>
                                    <td class="text-muted">
                                        <i class="fe fe-user-check me-2 text-info"></i>Cutting & Labour Handling:
                                    </td>
                                    <td class="text-end font-monospace fw-semibold text-dark">
                                        + ৳ {{ number_format($sale->labour_cost, 2) }}
                                    </td>
                                </tr>
                            @endif
                            @if((float)$sale->weight_scale_cost > 0)
                                <tr>
                                    <td class="text-muted">
                                        <i class="fe fe-activity me-2 text-warning"></i>Scale & Weight Slip Charge:
                                    </td>
                                    <td class="text-end font-monospace fw-semibold text-dark">
                                        + ৳ {{ number_format($sale->weight_scale_cost, 2) }}
                                    </td>
                                </tr>
                            @endif
                            @if((float)$sale->other_charges > 0)
                                <tr>
                                    <td class="text-muted">
                                        <i class="fe fe-plus-circle me-2 text-secondary"></i>Other Shipment Charges:
                                    </td>
                                    <td class="text-end font-monospace fw-semibold text-dark">
                                        + ৳ {{ number_format($sale->other_charges, 2) }}
                                    </td>
                                </tr>
                            @endif
                            <tr class="border-top pt-2">
                                <td class="fw-bold text-dark fs-6 pt-3">
                                    <i class="fe fe-check-circle me-2 text-success"></i>Invoice Payable Grand Total:
                                </td>
                                <td class="text-end font-monospace fw-bold text-primary fs-5 pt-3">
                                    ৳ {{ number_format($grandTotal, 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment Settlement & Banking Details Card -->
        <div class="col-lg-5 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-credit-card text-primary me-2"></i>Payment Settlement & Channels
                    </h5>
                    <small class="text-muted">Collection methods and account information</small>
                </div>
                <div class="card-body d-flex flex-column justify-content-between">
                    <table class="table table-borderless info-table mb-3">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width: 45%;">Invoice Grand Total:</td>
                                <td class="font-monospace fw-bold text-dark fs-6">৳ {{ number_format($grandTotal, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Collected Amount:</td>
                                <td class="font-monospace fw-bold text-success fs-6">৳ {{ number_format($paidAmount, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Payment Method:</td>
                                <td>
                                    @if($sale->payment_method === 'bank')
                                        <span class="badge badge-soft-primary px-3 py-1 rounded-pill"><i class="fe fe-credit-card me-1"></i>Bank Transfer</span>
                                    @elseif($sale->payment_method === 'mobile_banking')
                                        <span class="badge badge-soft-info px-3 py-1 rounded-pill"><i class="fe fe-smartphone me-1"></i>Mobile Banking</span>
                                    @else
                                        <span class="badge badge-soft-success px-3 py-1 rounded-pill"><i class="fe fe-dollar-sign me-1"></i>Cash in Hand</span>
                                    @endif
                                </td>
                            </tr>
                            @if($sale->payment_method !== 'cash' && $sale->bankDetail)
                                <tr>
                                    <td class="text-muted">Bank Account:</td>
                                    <td class="fw-semibold text-dark">
                                        {{ $sale->bankDetail->bank_name }}
                                        <small class="text-muted d-block fs-8">{{ $sale->bankDetail->account_number }} ({{ $sale->bankDetail->account_name }})</small>
                                    </td>
                                </tr>
                            @endif
                            @if($sale->transaction_ref)
                                <tr>
                                    <td class="text-muted">Trx Ref / Cheque:</td>
                                    <td class="text-dark font-monospace">{{ $sale->transaction_ref }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td class="text-muted">Outstanding Balance:</td>
                                <td>
                                    @if($dueAmount > 0)
                                        <span class="badge badge-soft-danger px-3 py-1 rounded-pill fs-6 fw-bold">
                                            ৳ {{ number_format($dueAmount, 2) }}
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
                </div>
            </div>
        </div>
    </div>

    <!-- Payment & Collection Receipts Table -->
    @if($sale->payments && $sale->payments->count() > 0)
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-dollar-sign text-success me-2"></i>Collection & Payment Receipts History
                        <span class="badge badge-soft-primary ms-2">{{ $sale->payments->count() }}</span>
                    </h5>
                    <small class="text-muted">All payment vouchers recorded against this sale</small>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle mb-0">
                        <thead class="bg-light text-secondary fs-8 text-uppercase">
                            <tr>
                                <th class="ps-4">Receipt #</th>
                                <th>Date</th>
                                <th>Channel</th>
                                <th>Account / Bank</th>
                                <th>Transaction Ref</th>
                                <th>Amount Collected</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->payments as $pmt)
                                <tr>
                                    <td class="ps-4 font-monospace fw-bold text-primary">#REC-{{ $pmt->id }}</td>
                                    <td>{{ $pmt->created_at ? $pmt->created_at->format('d M Y, h:i A') : 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border text-capitalize">{{ str_replace('_', ' ', $pmt->payment_method) }}</span>
                                    </td>
                                    <td>
                                        {{ $pmt->bankDetail ? $pmt->bankDetail->bank_name . ' (' . $pmt->bankDetail->account_number . ')' : 'Cash in Hand' }}
                                    </td>
                                    <td class="font-monospace">{{ $pmt->transaction_ref ?: '—' }}</td>
                                    <td class="fw-bold text-success font-monospace">৳ {{ number_format($pmt->amount, 2) }}</td>
                                    <td class="text-secondary small">{{ $pmt->remarks ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
