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
    .table-custom tbody tr.lot-row {
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .table-custom tbody tr.lot-row:hover {
        background-color: #f8fafc !important;
    }
    .lot-details-row {
        transition: all 0.2s ease-in-out;
    }
    .expand-icon {
        transition: transform 0.2s ease-in-out;
        display: inline-block;
    }
    .rotate-90 {
        transform: rotate(90deg);
    }
    .shadow-inner {
        box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.03);
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
    .search-box-custom input {
        border-radius: 8px;
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

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Purchase List</h4>
                <p class="text-muted small mb-0">Purchases grouped by Lot intake batch. Click any row to expand coil specifications.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('purchase.create') }}" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-plus-circle fs-6"></i>
                    <span>Add Purchase</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Summary Stats Bar -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-3 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="avatar avatar-md bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-package fs-5"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-medium d-block mb-1">Total Lots / Intakes</span>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalLotsCount ?? $lots->total()) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-4 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="avatar avatar-md bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-5"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-medium d-block mb-1">Total Order Value</span>
                        <h4 class="mb-0 fw-bold text-dark">৳{{ number_format($totalOrderValue, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-5 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="avatar avatar-md bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-credit-card fs-5"></i>
                    </div>
                    <div class="flex-grow-1 row g-0 align-items-center">
                        <div class="col-6 pe-3">
                            <span class="text-muted small fw-medium d-block mb-1">Total Paid</span>
                            <h4 class="mb-0 fw-bold text-success">৳{{ number_format($totalPaid, 2) }}</h4>
                        </div>
                        <div class="col-6 ps-3 border-start">
                            <span class="text-muted small fw-medium d-block mb-1">Total Due</span>
                            <h4 class="mb-0 fw-bold text-danger">৳{{ number_format($totalDue, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Summary Stats Bar -->

    <!-- Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <!-- Filter Controls -->
        <div class="card-header bg-white py-3 border-bottom border-light">
            <form action="{{ route('purchase.index') }}" method="GET" id="purchaseFilterForm">
                <div class="row align-items-center g-2">
                    <div class="col-12 col-md-3">
                        <div class="search-box-custom">
                            <input type="text" name="search" class="form-control border-light-subtle" placeholder="Search lot, vendor, thickness, size..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <select name="lot_id" class="form-select border-light-subtle select2" onchange="document.getElementById('purchaseFilterForm').submit()">
                            <option value="">All Lots</option>
                            @foreach ($allLots ?? $lots as $l)
                                <option value="{{ $l->id }}" {{ request('lot_id') == $l->id ? 'selected' : '' }}>
                                    {{ $l->lot_number }} ({{ $l->vendor ? $l->vendor->name : 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <select name="warehouse_id" class="form-select border-light-subtle select2" onchange="document.getElementById('purchaseFilterForm').submit()">
                            <option value="">All Stockyards</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                    {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <select name="vendor_id" class="form-select border-light-subtle select2" onchange="document.getElementById('purchaseFilterForm').submit()">
                            <option value="">All Vendors</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2 text-md-end d-flex align-items-center justify-content-md-end justify-content-between gap-2">
                        <span class="text-muted small">
                            <span class="fw-bold text-dark">{{ $lots->count() }}</span> of {{ $lots->total() }} Lots
                        </span>
                        <!-- <button type="button" class="btn btn-sm btn-outline-primary rounded-3 px-2 py-1 shadow-none" id="toggleAllLotsBtn" onclick="toggleAllLots()" title="Expand or collapse all lot items">
                            <i class="fe fe-maximize-2 me-1" id="toggleAllIcon"></i><span id="toggleAllText">Expand All</span>
                        </button> -->
                    </div>
                </div>
            </form>
        </div>

        <!-- Table Body -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0" id="purchaseTable">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-3" style="width: 70px;">#</th>
                            <!-- <th>Date</th> -->
                            <th>Lot Number</th>
                            <th>Vendor</th>
                            <th>Steel Items / Coils</th>
                            <th>Total Weight</th>
                            <th>Total Bill</th>
                            <th>Status / Due</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse ($lots as $lot)
                            @php
                                $lotPurchases = $lot->purchases ?? collect();
                                $lotItemCount = $lotPurchases->count();
                                $lotCoilCount = (int) $lotPurchases->sum('quantity');
                                $lotTotalWeight = (float) $lotPurchases->sum('total_weight');
                                $lotTotalPrice = (float) $lotPurchases->sum('total_price');
                                $lotDue = (float) $lotPurchases->sum('due');
                                $warehousesInLot = $lotPurchases->pluck('warehouse.name')->filter()->unique();
                                $lotDelivery = (float) $lotPurchases->sum('delivery_charge');
                                $lotLabour = (float) $lotPurchases->sum('labour_cost');
                                $lotScale = (float) $lotPurchases->sum('weight_scale_cost');
                                $lotOther = (float) $lotPurchases->sum('other_charges');
                                $lotDiscount = (float) $lotPurchases->sum('discount');
                                $lotTotalExtra = $lotDelivery + $lotLabour + $lotScale + $lotOther;
                                $lotSubTotal = (float) $lotPurchases->sum(fn($p) => (float)($p->sub_price ?: $p->total_price));
                            @endphp

                            <!-- Main Lot Row (Clickable to Expand) -->
                            <tr class="lot-row" data-lot-id="{{ $lot->id }}" onclick="toggleLotDetails({{ $lot->id }}, event)" title="Click to view coils in this lot">
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted fw-semibold small">{{ $loop->iteration + ($lots->currentPage() - 1) * $lots->perPage() }}</span>
                                    </div>
                                </td>
                                <!-- <td>
                                    <span class="text-dark fw-semibold small d-block">
                                        {{ $lot->lot_date ? $lot->lot_date->format('d M Y') : ($lot->created_at ? $lot->created_at->format('d M Y') : 'N/A') }}
                                    </span>                                
                                </td> -->
                                <td>
                                    <a href="{{ route('lots.show', $lot->id) }}" class="fw-bold text-primary text-decoration-none d-inline-flex align-items-center gap-1" onclick="event.stopPropagation()">
                                        <i class="fe fe-package"></i>
                                        <span>{{ $lot->lot_number }}</span>
                                    </a>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark d-block">
                                        {{ Str::limit($lot->vendor->name ?? 'N/A', 18) }}
                                    </span>
                                    @if($lot->vendor && $lot->vendor->phone)
                                        <small class="text-muted fs-8">{{ $lot->vendor->phone }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-dark rounded-pill px-2.5 py-1 fs-8">
                                        {{ $lotItemCount }} {{ Str::plural('Item', $lotItemCount) }}
                                    </span>
                                    <small class="text-muted d-block fs-8 mt-0.5">
                                        {{ $lotCoilCount }} {{ Str::plural('Coil', $lotCoilCount) }}
                                    </small>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark font-monospace d-block">
                                        {{ number_format($lotTotalWeight, 2) }} kg
                                    </span>
                                    @if($lotTotalWeight >= 1000)
                                        <small class="text-muted fs-8">({{ number_format($lotTotalWeight / 1000, 2) }} MT)</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-bold text-dark font-monospace">
                                        ৳{{ number_format($lotTotalPrice, 2) }}
                                    </span>
                                </td>
                                <td>
                                    @if($lotDue > 0)
                                        <span class="badge badge-soft-danger px-2.5 py-1 rounded-pill fs-8">
                                            Due: ৳{{ number_format($lotDue, 2) }}
                                        </span>
                                    @else
                                        <span class="badge badge-soft-success px-2.5 py-1 rounded-pill fs-8">
                                            Paid
                                        </span>
                                    @endif
                                </td>
                                <td onclick="event.stopPropagation()">
                                    <div class="dropdown">
                                        <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                            @if($lotPurchases->isNotEmpty())
                                                <li>
                                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('purchase.show', $lotPurchases->first()->id) }}">
                                                        <i class="fe fe-shopping-cart text-primary"></i>
                                                        <span>View Consignment</span>
                                                    </a>
                                                </li>
                                            @endif
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="javascript:void(0)" onclick="toggleLotDetails({{ $lot->id }}, event)">
                                                    <i class="fe fe-list text-secondary"></i>
                                                    <span>Expand Items ({{ $lotItemCount }})</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('lots.show', $lot->id) }}">
                                                    <i class="fe fe-eye text-info"></i>
                                                    <span>View Lot Profile</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('purchase.create') }}?lot_id={{ $lot->id }}">
                                                    <i class="fe fe-plus text-success"></i>
                                                    <span>Add Coils to Lot</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>

                            <!-- Collapsible Details Row (Shows all steel items/coils for this lot) -->
                            <tr class="lot-details-row" id="lot-details-{{ $lot->id }}" style="display: none; background-color: #f8fafc;">
                                <td colspan="10" class="p-0 border-0">
                                    <div class="p-3 bg-light-subtle border-start border-4 border-primary shadow-inner">
                                        <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-primary rounded-pill px-2.5 py-1 fs-8">
                                                    <i class="fe fe-layers me-1"></i>Lot Items Breakdown
                                                </span>
                                                <span class="fw-bold text-dark fs-7">
                                                    Coils & Plates Intake Details for {{ $lot->lot_number }}
                                                </span>
                                            </div>
                                            <div class="text-muted small">
                                                Total: {{ $lotItemCount }} {{ Str::plural('item', $lotItemCount) }} | {{ number_format($lotTotalWeight, 2) }} kg
                                            </div>
                                        </div>

                                        @if($lotTotalExtra > 0 || $lotDiscount > 0)
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2 px-2 py-1.5 bg-white rounded-2 border border-light-subtle small">
                                                <span class="text-dark fw-semibold">
                                                    <i class="fe fe-dollar-sign text-primary me-1"></i>Consignment Extra Charges:
                                                </span>
                                                @if($lotDelivery > 0)
                                                    <span class="badge bg-light text-dark border">Delivery: <strong>৳{{ number_format($lotDelivery, 2) }}</strong></span>
                                                @endif
                                                @if($lotLabour > 0)
                                                    <span class="badge bg-light text-dark border">Labour: <strong>৳{{ number_format($lotLabour, 2) }}</strong></span>
                                                @endif
                                                @if($lotScale > 0)
                                                    <span class="badge bg-light text-dark border">Scale: <strong>৳{{ number_format($lotScale, 2) }}</strong></span>
                                                @endif
                                                @if($lotOther > 0)
                                                    <span class="badge bg-light text-dark border">Other: <strong>৳{{ number_format($lotOther, 2) }}</strong></span>
                                                @endif
                                                @if($lotDiscount > 0)
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Discount: <strong>-৳{{ number_format($lotDiscount, 2) }}</strong></span>
                                                @endif
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-auto">
                                                    Steel Subtotal: ৳{{ number_format($lotSubTotal, 2) }} | Total Bill: ৳{{ number_format($lotTotalPrice, 2) }}
                                                </span>
                                            </div>
                                        @endif

                                        <div class="table-responsive bg-white rounded-3 border shadow-sm">
                                            <table class="table table-sm table-hover table-custom align-middle mb-0">
                                                <thead class="bg-light fs-8 text-uppercase text-secondary">
                                                    <tr>
                                                        <th class="ps-3" style="width: 40px;">#</th>
                                                        <th>Specifications & Dimensions</th>
                                                        <th>Coil Tag / ID</th>
                                                        <th class="text-center">Qty</th>
                                                        <th class="text-end">Per Coil Wt</th>
                                                        <th class="text-end">Total Wt</th>
                                                        <th class="text-end">Unit Rate</th>
                                                        <th class="text-end">Sub Total</th>
                                                        <th class="text-end pe-4">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($lotPurchases as $item)
                                                        <tr>
                                                            <td class="ps-3 text-muted fw-semibold fs-8">{{ $loop->iteration }}</td>
                                                            <td>
                                                                <div class="d-flex flex-wrap align-items-center gap-1">
                                                                    @if($item->thickness)
                                                                        <span class="badge bg-light text-dark border"><i class="fe fe-layers me-1 text-primary"></i>Thickness: {{ $item->thickness }}</span>
                                                                    @endif
                                                                    @if($item->size)
                                                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="fe fe-maximize-2 me-1"></i>{{ $item->size }} ({{ $item->size_type ?: 'ft' }})</span>
                                                                    @else
                                                                        <span class="badge bg-light text-muted border">{{ $item->size_type ?: 'ft' }}</span>
                                                                    @endif
                                                                </div>
                                                                @if($item->notes)
                                                                    <div class="small text-muted mt-0.5"><i class="fe fe-tag me-1 text-info"></i>{{ $item->notes }}</div>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if($item->coils && $item->coils->count() > 0)
                                                                    @foreach($item->coils as $coil)
                                                                        <span class="badge bg-white text-dark border font-monospace me-1">{{ $coil->coil_number }}</span>
                                                                    @endforeach
                                                                @else
                                                                    <span class="text-muted fs-8">-</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center fw-bold">{{ (int) $item->quantity }}</td>
                                                            <td class="text-end font-monospace">{{ number_format($item->unit_weight, 2) }} kg</td>
                                                            <td class="text-end font-monospace fw-bold text-primary">
                                                                {{ number_format($item->total_weight, 2) }} kg
                                                                @if($item->total_weight >= 1000)
                                                                    <small class="text-muted fw-normal d-block">({{ number_format($item->total_weight / 1000, 2) }} MT)</small>
                                                                @endif
                                                            </td>
                                                            <td class="text-end font-monospace">৳{{ number_format($item->unit_price, 2) }}</td>
                                                            <td class="text-end font-monospace fw-bold text-success">
                                                                ৳{{ number_format($item->sub_price ?: $item->total_price, 2) }}
                                                            </td>
                                                            <td class="text-end pe-4" onclick="event.stopPropagation()">
                                                                <div class="dropdown">
                                                                    <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                                                        <i class="fas fa-ellipsis-v"></i>
                                                                    </a>
                                                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                                                        <li>
                                                                            <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('purchase.show', $item->id) }}">
                                                                                <i class="fe fe-shopping-cart text-info"></i>
                                                                                <span>View Consignment</span>
                                                                            </a>
                                                                        </li>
                                                                        <li>
                                                                            <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('purchase.edit', $item->id) }}">
                                                                                <i class="fe fe-edit text-primary"></i>
                                                                                <span>Edit Specifications</span>
                                                                            </a>
                                                                        </li>
                                                                        @if($item->coils && $item->coils->count() > 0)
                                                                            <li>
                                                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('coils.index') }}?search={{ $item->coils->first()->coil_number }}">
                                                                                    <i class="fe fe-disc text-secondary"></i>
                                                                                    <span>Track Coil in Yard</span>
                                                                                </a>
                                                                            </li>
                                                                        @endif
                                                                        <li><hr class="dropdown-divider my-1"></li>
                                                                        <li>
                                                                            <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" href="javascript:void(0)" onclick="if (confirm('Are you sure you want to delete purchase item #PO-{{ $item->id }}?')) { document.getElementById('deleteItem{{ $item->id }}').submit(); }">
                                                                                <i class="fe fe-trash-2"></i>
                                                                                <span>Delete Item</span>
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                                <form id="deleteItem{{ $item->id }}" action="{{ route('purchase.destroy', $item->id) }}" method="POST" class="d-none">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyStateRow">
                                <td colspan="10" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <div class="avatar avatar-xl bg-primary-light text-primary rounded-circle mb-3 d-flex align-items-center justify-content-center">
                                            <i class="fe fe-shopping-cart fs-1"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">No Purchase Records Found</h5>
                                        <p class="text-muted small mb-3">Add a new steel purchase intake to register coils, stockyards, and vendor payables</p>
                                        <a href="{{ route('purchase.create') }}" class="btn btn-primary btn-sm px-3 rounded-2">
                                            Add Purchase
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($lots->hasPages())
                <div class="p-3 border-top d-flex justify-content-end">
                    {{ $lots->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Expand / Collapse Lot Details
    function toggleLotDetails(lotId, event) {
        if (event) {
            const target = event.target;
            if (target.closest('a') || target.closest('button:not(.expand-btn)') || target.closest('.dropdown') || target.closest('input') || target.closest('select')) {
                return;
            }
        }

        const detailsRow = document.getElementById('lot-details-' + lotId);
        const chevron = document.getElementById('chevron-' + lotId);
        if (!detailsRow) return;

        const isHidden = detailsRow.style.display === 'none' || getComputedStyle(detailsRow).display === 'none';

        if (isHidden) {
            detailsRow.style.display = 'table-row';
            if (chevron) {
                chevron.classList.add('rotate-90');
            }
        } else {
            detailsRow.style.display = 'none';
            if (chevron) {
                chevron.classList.remove('rotate-90');
            }
        }

        updateToggleAllBtn();
    }

    // Expand or Collapse All Lots
    function toggleAllLots() {
        const detailRows = document.querySelectorAll('.lot-details-row');
        const chevrons = document.querySelectorAll('.expand-icon');
        const toggleIcon = document.getElementById('toggleAllIcon');
        const toggleText = document.getElementById('toggleAllText');

        if (!detailRows.length) return;

        let hasHidden = false;
        detailRows.forEach(row => {
            if (row.style.display === 'none' || getComputedStyle(row).display === 'none') {
                hasHidden = true;
            }
        });

        if (hasHidden) {
            // Expand all
            detailRows.forEach(row => row.style.display = 'table-row');
            chevrons.forEach(icon => icon.classList.add('rotate-90'));
            if (toggleIcon) toggleIcon.className = 'fe fe-minimize-2 me-1';
            if (toggleText) toggleText.textContent = 'Collapse All';
        } else {
            // Collapse all
            detailRows.forEach(row => row.style.display = 'none');
            chevrons.forEach(icon => icon.classList.remove('rotate-90'));
            if (toggleIcon) toggleIcon.className = 'fe fe-maximize-2 me-1';
            if (toggleText) toggleText.textContent = 'Expand All';
        }
    }

    function updateToggleAllBtn() {
        const detailRows = document.querySelectorAll('.lot-details-row');
        const toggleIcon = document.getElementById('toggleAllIcon');
        const toggleText = document.getElementById('toggleAllText');
        if (!detailRows.length || !toggleText) return;

        let hasHidden = false;
        detailRows.forEach(row => {
            if (row.style.display === 'none' || getComputedStyle(row).display === 'none') {
                hasHidden = true;
            }
        });

        if (hasHidden) {
            if (toggleIcon) toggleIcon.className = 'fe fe-maximize-2 me-1';
            if (toggleText) toggleText.textContent = 'Expand All';
        } else {
            if (toggleIcon) toggleIcon.className = 'fe fe-minimize-2 me-1';
            if (toggleText) toggleText.textContent = 'Collapse All';
        }
    }

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
                            <option value="bank">Bank Transfer / Deposit</option>
                            <option value="mobile_banking">Mobile Banking (bKash/Nagad)</option>
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
@endsection
