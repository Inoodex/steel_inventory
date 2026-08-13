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
    .table-custom tbody tr {
        transition: background-color 0.15s ease;
    }
    .table-custom tbody tr:hover {
        background-color: #fcfbff !important;
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

    .table-custom th, .table-custom td {
        white-space: nowrap;
    }

    .table-responsive {
        overflow: visible !important;
    }

    .dropdown-menu {
        z-index: 1050 !important;
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
                <p class="text-muted small mb-0">Manage stock purchases, vendor payments, unit costs, and serial numbers</p>
            </div>
            <div>
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
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-shopping-cart fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Orders</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($purchases->total()) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Amount</h6>
                        <h4 class="mb-0 fw-bold text-dark">৳{{ number_format($purchases->sum('total_price'), 2) }}</h4>
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
                        <h6 class="text-muted fw-normal mb-1">Total Paid</h6>
                        <h4 class="mb-0 fw-bold text-dark">৳{{ number_format($purchases->sum('payment'), 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-danger-light text-danger rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-alert-circle fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Outstanding Due</h6>
                        <h4 class="mb-0 fw-bold text-dark">৳{{ number_format($purchases->sum('due'), 2) }}</h4>
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
                            <input type="text" name="search" class="form-control border-light-subtle" placeholder="Search product, vendor, lot..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <select name="lot_id" class="form-select border-light-subtle select2" onchange="document.getElementById('purchaseFilterForm').submit()">
                            <option value="">All Lots</option>
                            @foreach ($lots as $lot)
                                <option value="{{ $lot->id }}" {{ request('lot_id') == $lot->id ? 'selected' : '' }}>
                                    {{ $lot->lot_number }} ({{ $lot->vendor ? $lot->vendor->name : 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <select name="product_id" class="form-select border-light-subtle select2" onchange="document.getElementById('purchaseFilterForm').submit()">
                            <option value="">All Products</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
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
                    <div class="col-12 col-md-2 text-md-end text-muted small">
                        Showing <span class="fw-bold text-dark">{{ $purchases->count() }}</span> entries
                    </div>
                </div>
            </form>
        </div>

        <!-- Table Body -->
        <div class="card-body p-0" style="overflow: visible;">
            <div class="table-responsive" style="overflow: visible !important;">
                <table class="table table-hover table-custom align-middle mb-0" id="purchaseTable">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Date</th>
                            <th>Lot Number</th>
                            <!-- <th>Product & Model</th> -->
                            <th>Vendor</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total Price</th>
                            <th>Payment</th>
                            <th>Due</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse ($purchases as $purchase)
                            <tr>
                                <td class="ps-4 text-muted fw-semibold">{{ $loop->iteration }}</td>
                                <td>
                                    <span class="text-secondary small">
                                        {{ $purchase->created_at ? $purchase->created_at->format('d M Y') : 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    @if($purchase->lot)
                                        <a href="{{ route('lots.show', $purchase->lot->id) }}">
                                            <i class="fe fe-package me-1"></i> {{ $purchase->lot->lot_number }}
                                        </a>
                                    @else
                                        <span class="text-muted small">No Lot</span>
                                    @endif
                                </td>
                                <!-- <td>
                                    <div>
                                        <span class="fw-bold text-dark d-block" title="{{ $purchase->product->name ?? '' }}">
                                            {{ Str::limit($purchase->product->name ?? 'N/A', 25) }}
                                        </span>
                                        @php
                                            $pThickness = $purchase->thickness ?: ($purchase->product->thickness ?? null);
                                            $pSize = $purchase->size ?: ($purchase->product->size ?? null);
                                        @endphp
                                        @if($pThickness || $pSize)
                                            <small class="text-secondary d-block">
                                                <i class="fe fe-layers me-1 text-primary"></i>{{ $pThickness ? $pThickness . ' | ' : '' }}{{ $pSize }}
                                            </small>
                                        @else
                                            <small class="text-muted fs-7">Model: {{ $purchase->product->model ?? 'N/A' }}</small>
                                        @endif
                                    </div>
                                </td> -->
                                <td>
                                    <span class="fw-semibold text-dark">
                                        {{ Str::limit($purchase->vendor->name ?? 'N/A', 20) }}
                                    </span>
                                </td>
                                <td>
                                    <!-- <span class="badge badge-soft-info px-3 py-1 rounded-pill fs-7">
                                        {{ number_format($purchase->quantity, 2) }} Units
                                    </span> -->
                                    @php
                                        $totW = $purchase->total_weight ?: ($purchase->unit_weight ? ($purchase->unit_weight * $purchase->quantity) : ($purchase->product && $purchase->product->weight ? $purchase->product->weight * $purchase->quantity : null));
                                    @endphp
                                    @if($totW)
                                        <small class="d-block mt-1">{{ number_format($totW, 3) }} kg</small>
                                    @endif
                                </td>
                                <td>৳{{ number_format($purchase->unit_price, 2) }}</td>
                                <td class="fw-bold text-dark">৳{{ number_format($purchase->total_price, 2) }}</td>
                                <td class="text-success fw-semibold">৳{{ number_format($purchase->payment, 2) }}</td>
                                <td>
                                    @if($purchase->due > 0)
                                        <span class="badge badge-soft-danger px-3 py-1 rounded-pill fs-7">
                                            ৳{{ number_format($purchase->due, 2) }}
                                        </span>
                                    @else
                                        <span class="badge badge-soft-success px-3 py-1 rounded-pill fs-7">
                                            Paid
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#edit-purchase-{{ $purchase->id }}">
                                                    <i class="fe fe-edit text-primary"></i>
                                                    <span>Edit Purchase</span>
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider opacity-50"></li>
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" href="javascript:void(0)"
                                                    onclick="if (confirm('Are you sure you want to delete this purchase record?')) { document.getElementById('deletePurchase{{ $purchase->id }}').submit(); }">
                                                    <i class="fe fe-trash-2 text-danger"></i>
                                                    <span>Delete Purchase</span>
                                                </a>
                                                <form id="deletePurchase{{ $purchase->id }}" action="{{ route('purchase.destroy', $purchase->id) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </li>
                                        </ul>
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
                                        <p class="text-muted small mb-3">Add a new purchase to update product inventory and vendor bills</p>
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

            @if($purchases->hasPages())
                <div class="p-3 border-top">
                    {{ $purchases->links() }}
                </div>
            @endif
        </div>
    </div>
</div>


<!-- Edit Purchase Modals -->
@foreach ($purchases as $purchase)
<div class="modal fade" id="edit-purchase-{{ $purchase->id }}" aria-hidden="true" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light py-3 border-bottom">
                <h5 class="modal-title fw-bold text-dark">Edit Purchase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('purchase.update', $purchase->id) }}">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit-lot_id-{{ $purchase->id }}" class="form-label fw-semibold small text-secondary">Purchase Lot <span class="text-danger">*</span></label>
                            <select id="edit-lot_id-{{ $purchase->id }}" name="lot_id" class="form-select select2" required>
                                <option value="">Select Purchase Lot</option>
                                @foreach ($lots as $lot)
                                    <option value="{{ $lot->id }}" data-vendor-id="{{ $lot->vendor_id }}" {{ $lot->id == $purchase->lot_id ? 'selected' : '' }}>
                                        {{ $lot->lot_number }} — {{ $lot->vendor ? $lot->vendor->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="edit-warehouse_id-{{ $purchase->id }}" class="form-label fw-semibold small text-secondary">Stockyard / Warehouse</label>
                            <select id="edit-warehouse_id-{{ $purchase->id }}" name="warehouse_id" class="form-select select2">
                                <option value="">Select Stockyard / Warehouse</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}" {{ $wh->id == $purchase->warehouse_id ? 'selected' : '' }}>
                                        {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">
                                Vendor <span class="text-danger">*</span>
                                <span class="ms-1 text-muted" style="font-size:10px; font-weight:400;"></span>
                            </label>
                            <input type="text" id="edit-vendor-display-{{ $purchase->id }}" class="form-control" readonly
                                value="{{ $purchase->vendor ? $purchase->vendor->name : '' }}"
                                style="background-color:#f1f5f9; cursor:not-allowed;">
                            <input type="hidden" name="vendor_id" id="edit-vendor-hidden-{{ $purchase->id }}" value="{{ $purchase->vendor_id }}">
                        </div>

                        <div class="col-md-4">
                            <label for="edit-thickness-{{ $purchase->id }}" class="form-label fw-semibold small text-secondary">Thickness</label>
                            <input id="edit-thickness-{{ $purchase->id }}" name="thickness" value="{{ old('thickness', $purchase->thickness ?? ($purchase->product->thickness ?? '')) }}" class="form-control" placeholder="e.g. 16mm" />
                        </div>
                        <div class="col-md-4">
                            <label for="edit-size-{{ $purchase->id }}" class="form-label fw-semibold small text-secondary">Size / Length</label>
                            <input id="edit-size-{{ $purchase->id }}" name="size" value="{{ old('size', $purchase->size ?? ($purchase->product->size ?? '')) }}" class="form-control" placeholder="e.g. 12m" />
                        </div>
                        <div class="col-md-4">
                            <label for="edit-unit_weight-{{ $purchase->id }}" class="form-label fw-semibold small text-secondary">Unit Weight (kg)</label>
                            <input type="number" step="0.001" id="edit-unit_weight-{{ $purchase->id }}" name="unit_weight" value="{{ old('unit_weight', $purchase->unit_weight ?? ($purchase->product->weight ?? '')) }}" class="form-control" placeholder="0.000" />
                        </div>

                        <div class="col-md-4">
                            <label for="edit-quantity-{{ $purchase->id }}" class="form-label fw-semibold small text-secondary">Quantity</label>
                            <input id="edit-quantity-{{ $purchase->id }}" name="quantity" value="{{ $purchase->quantity }}" class="form-control" placeholder="Quantity" />
                        </div>

                        <div class="col-md-4">
                            <label for="edit-unit_price-{{ $purchase->id }}" class="form-label fw-semibold small text-secondary">Unit Cost Price</label>
                            <input id="edit-unit_price-{{ $purchase->id }}" name="unit_price" value="{{ $purchase->unit_price }}" class="form-control" placeholder="Unit Price" />
                        </div>

                        <div class="col-md-4">
                            <label for="edit-sub_price-{{ $purchase->id }}" class="form-label fw-semibold small text-secondary">Sub Price</label>
                            <input id="edit-sub_price-{{ $purchase->id }}" name="sub_price" value="{{ $purchase->sub_price }}" class="form-control bg-light" readonly placeholder="Sub Price" />
                        </div>

                        <div class="col-md-4">
                            <label for="edit-total_price-{{ $purchase->id }}" class="form-label fw-semibold small text-secondary">Payable Total Price</label>
                            <input id="edit-total_price-{{ $purchase->id }}" name="total_price" value="{{ $purchase->total_price }}" class="form-control" placeholder="Total Price" />
                        </div>

                        <div class="col-md-4">
                            <label for="edit-payment-{{ $purchase->id }}" class="form-label fw-semibold small text-secondary">Payment</label>
                            <input id="edit-payment-{{ $purchase->id }}" name="payment" value="{{ $purchase->payment }}" class="form-control" placeholder="Payment" />
                        </div>

                        <div class="col-md-4">
                            <label for="edit-due-{{ $purchase->id }}" class="form-label fw-semibold small text-secondary">Outstanding Due</label>
                            <input id="edit-due-{{ $purchase->id }}" name="due" value="{{ $purchase->due }}" class="form-control bg-light" readonly placeholder="Due" />
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 p-3 border-top bg-light">
                    <button type="button" class="btn btn-light px-4 rounded-3 text-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-3 shadow-sm">Update Purchase</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- Quick Add Lot Modal -->
<div class="modal fade" id="quickAddLotModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form id="quickAddLotForm">
                @csrf
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="fe fe-package text-primary me-2"></i> Quick Create Lot
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <div id="quickLotAlert" class="alert d-none rounded-3 mb-3"></div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Vendor <span class="text-danger">*</span></label>
                        <select name="vendor_id" id="quick_lot_vendor_id" class="form-select rounded-3" required>
                            <option value="">Select Vendor</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Lot Number (Leave blank to auto-generate)</label>
                        <input type="text" name="lot_number" id="quick_lot_number" class="form-control rounded-3">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Lot Date <span class="text-danger">*</span></label>
                        <input type="date" name="lot_date" id="quick_lot_date" class="form-control rounded-3" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Notes / Mill Specs</label>
                        <textarea name="notes" id="quick_lot_notes" class="form-control rounded-3" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSaveQuickLot" class="btn btn-primary rounded-3">
                       Save & Select Lot
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Handle Quick Lot Creation AJAX
    document.addEventListener('DOMContentLoaded', function() {
        const quickLotForm = document.getElementById('quickAddLotForm');
        if (quickLotForm) {
            quickLotForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const alertDiv = document.getElementById('quickLotAlert');
                const btn = document.getElementById('btnSaveQuickLot');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

                const formData = new FormData(quickLotForm);

                fetch("{{ route('lots.quick_store') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fe fe-check me-1"></i> Save & Select Lot';

                    if (data.success && data.lot) {
                        // Append option to purchase_lot_id select dropdown
                        const lotSelect = document.getElementById('purchase_lot_id');
                        if (lotSelect) {
                            const newOption = new Option(`${data.lot.lot_number} — ${data.lot.vendor_name}`, data.lot.id, true, true);
                            newOption.setAttribute('data-vendor-id', data.lot.vendor_id);
                            $(newOption).data('vendor-id', data.lot.vendor_id);
                            lotSelect.add(newOption);
                            $(lotSelect).trigger('change');
                        }
                        
                        // Also auto select vendor display & hidden
                        if (data.lot.vendor_id) {
                            if (typeof window.vendorMap !== 'undefined') {
                                window.vendorMap[data.lot.vendor_id] = data.lot.vendor_name;
                            }
                            var vDisp = document.getElementById('vendor_display');
                            var vHidd = document.getElementById('vendor_hidden');
                            if (vDisp) vDisp.value = data.lot.vendor_name;
                            if (vHidd) vHidd.value = data.lot.vendor_id;
                        }

                        // Close modal & reset form
                        const modalEl = document.getElementById('quickAddLotModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                        quickLotForm.reset();
                    } else {
                        alertDiv.className = 'alert alert-danger rounded-3 mb-3';
                        alertDiv.textContent = data.message || 'Error creating Lot. Please check inputs.';
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fe fe-check me-1"></i> Save & Select Lot';
                    alertDiv.className = 'alert alert-danger rounded-3 mb-3';
                    alertDiv.textContent = 'Server error. Please try again.';
                });
            });
        }
    });
</script>





<script>
    document.addEventListener('DOMContentLoaded', function() {
        @foreach ($purchases as $purchase)
            const qInput_{{ $purchase->id }} = document.getElementById('edit-quantity-{{ $purchase->id }}');
            const uInput_{{ $purchase->id }} = document.getElementById('edit-unit_price-{{ $purchase->id }}');
            const sInput_{{ $purchase->id }} = document.getElementById('edit-sub_price-{{ $purchase->id }}');
            const tInput_{{ $purchase->id }} = document.getElementById('edit-total_price-{{ $purchase->id }}');
            const pInput_{{ $purchase->id }} = document.getElementById('edit-payment-{{ $purchase->id }}');
            const dInput_{{ $purchase->id }} = document.getElementById('edit-due-{{ $purchase->id }}');

            function calcEditSub_{{ $purchase->id }}() {
                if (!qInput_{{ $purchase->id }} || !uInput_{{ $purchase->id }}) return;
                const q = parseFloat(qInput_{{ $purchase->id }}.value) || 0;
                const u = parseFloat(uInput_{{ $purchase->id }}.value) || 0;
                if (sInput_{{ $purchase->id }}) sInput_{{ $purchase->id }}.value = (q * u).toFixed(2);
            }

            function calcEditDue_{{ $purchase->id }}() {
                if (!tInput_{{ $purchase->id }} || !pInput_{{ $purchase->id }}) return;
                const t = parseFloat(tInput_{{ $purchase->id }}.value) || 0;
                const p = parseFloat(pInput_{{ $purchase->id }}.value) || 0;
                if (dInput_{{ $purchase->id }}) dInput_{{ $purchase->id }}.value = (t - p).toFixed(2);
            }

            if (qInput_{{ $purchase->id }}) qInput_{{ $purchase->id }}.addEventListener('input', calcEditSub_{{ $purchase->id }});
            if (uInput_{{ $purchase->id }}) uInput_{{ $purchase->id }}.addEventListener('input', calcEditSub_{{ $purchase->id }});
            if (tInput_{{ $purchase->id }}) tInput_{{ $purchase->id }}.addEventListener('input', calcEditDue_{{ $purchase->id }});
            if (pInput_{{ $purchase->id }}) pInput_{{ $purchase->id }}.addEventListener('input', calcEditDue_{{ $purchase->id }});
        @endforeach

        // Vendor auto-fill from Lot (always read-only, driven by Lot selection)
        if (typeof $ !== 'undefined') {

            // Build a vendor map from PHP: { id: name }
            var vendorMap = {
                @foreach ($vendors as $vendor)
                    {{ $vendor->id }}: "{{ addslashes($vendor->name) }}",
                @endforeach
            };

            // --- Add Purchase modal ---
            $('#purchase_lot_id').on('change', function() {
                var selectedOpt = $(this).find('option:selected');
                var vendorId = selectedOpt.data('vendor-id');
                if (vendorId && vendorMap[vendorId]) {
                    $('#vendor_display').val(vendorMap[vendorId]);
                    $('#vendor_hidden').val(vendorId);
                } else {
                    $('#vendor_display').val('');
                    $('#vendor_hidden').val('');
                }
            });

            // --- Edit Purchase modals ---
            @foreach ($purchases as $purchase)
                $('#edit-lot_id-{{ $purchase->id }}').on('change', function() {
                    var selectedOpt = $(this).find('option:selected');
                    var vendorId = selectedOpt.data('vendor-id');
                    if (vendorId && vendorMap[vendorId]) {
                        $('#edit-vendor-display-{{ $purchase->id }}').val(vendorMap[vendorId]);
                        $('#edit-vendor-hidden-{{ $purchase->id }}').val(vendorId);
                    } else {
                        $('#edit-vendor-display-{{ $purchase->id }}').val('');
                        $('#edit-vendor-hidden-{{ $purchase->id }}').val('');
                    }
                });
            @endforeach
        }
    });
</script>
@endpush
@endsection
