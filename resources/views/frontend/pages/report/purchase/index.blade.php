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
    .table-custom tbody tr.purchase-row {
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .table-custom tbody tr.purchase-row:hover {
        background-color: #f8fafc !important;
    }
    .purchase-details-row {
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
    .badge-soft-warning {
        background-color: rgba(255, 193, 7, 0.15) !important;
        color: #b58105 !important;
        font-weight: 600;
    }
    .badge-soft-primary {
        background-color: rgba(118, 56, 255, 0.12) !important;
        color: #7638ff !important;
        font-weight: 600;
    }
    .table-custom th, .table-custom td {
        white-space: nowrap;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Purchases Report</h4>
                <p class="text-muted small mb-0">Track raw steel intake consignments, vendor procurements, and payments</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('purchase.report.pdf', request()->query()) }}" class="btn btn-outline-danger px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 shadow-sm" target="_blank">
                    <i class="fe fe-download fs-6"></i>
                    <span>Export PDF</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Summary Stats Bar (Grouped by Consignment / Lot) -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="avatar avatar-lg bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-shopping-cart fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Consignments</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($purchases->count()) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-layers fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Weight Intake</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($purchases->sum(fn($l) => $l->purchases->sum('total_weight')), 2) }} kg</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="avatar avatar-lg bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Order Value</h6>
                        <h4 class="mb-0 fw-bold text-dark">৳{{ number_format($purchases->sum(fn($l) => $l->purchases->sum('total_price')), 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="avatar avatar-lg bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-credit-card fs-4"></i>
                    </div>
                    <div class="flex-grow-1 row g-0 align-items-center">
                        <div class="col-6 pe-2">
                            <span class="text-muted small fw-medium d-block mb-1">Total Paid</span>
                            <h4 class="mb-0 fw-bold text-success fs-5">৳{{ number_format($purchases->sum(fn($l) => $l->purchases->sum('payment')), 2) }}</h4>
                        </div>
                        <div class="col-6 ps-2 border-start">
                            <span class="text-muted small fw-medium d-block mb-1">Total Due</span>
                            <h4 class="mb-0 fw-bold text-danger fs-5">৳{{ number_format($purchases->sum(fn($l) => $l->purchases->sum('due')), 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Summary Stats Bar -->

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
            <h6 class="fw-bold text-dark mb-3"><i class="fe fe-filter me-2 text-primary"></i>Filter Report Data</h6>
            <form action="{{ route('purchase.report.get') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Lot Number</label>
                        <select name="lot_id" class="form-select border-light-subtle">
                            <option value="">All Lots</option>
                            @foreach ($allLots ?? $lots as $l)
                                <option value="{{ $l->id }}" {{ request('lot_id') == $l->id ? 'selected' : '' }}>
                                    {{ $l->lot_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Vendor</label>
                        <select name="vendor_id" class="form-select border-light-subtle">
                            <option value="">All Vendors</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">From Date</label>
                        <input type="date" class="form-control border-light-subtle" name="from" value="{{ request('from') }}">
                    </div>

                    <div class="col-lg-2 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">To Date</label>
                        <input type="date" class="form-control border-light-subtle" name="to" value="{{ request('to') }}">
                    </div>

                    <div class="col-lg-2 col-md-12 col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill rounded-3 py-2">Filter</button>
                        <a href="{{ route('purchase.report') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <!-- Live Search Header with Expand All Button -->
        <div class="card-header bg-white py-3 border-bottom border-light">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-6">
                    <div class="search-box-custom">
                        <input type="text" id="purchaseSearchInput" class="form-control border-light-subtle" placeholder="Search lot no, vendor, warehouse, weight, amount..." autocomplete="off">
                    </div>
                </div>
                <div class="col-12 col-md-6 text-md-end d-flex align-items-center justify-content-md-end justify-content-between gap-3">
                    <span class="text-muted small">
                        Showing <span id="visiblePurchaseCount" class="fw-bold text-dark">{{ $purchases->count() }}</span> consignments
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-3 px-2 py-1 shadow-none" id="toggleAllPurchasesBtn" onclick="toggleAllPurchases()" title="Expand or collapse all consignment details">
                        <i class="fe fe-maximize-2 me-1" id="toggleAllIcon"></i><span id="toggleAllText">Expand All</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0" id="purchasesReportTable">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-3" style="width: 70px;">#</th>
                            <th>Date</th>
                            <th>Lot Number</th>
                            <th>Vendor</th>
                            <th>Warehouse</th>
                            <th class="text-center">Steel Items</th>
                            <th class="text-end">Total Weight</th>
                            <th class="text-end">Total Bill</th>
                            <th class="text-center">Payment / Due</th>
                            <th class="pe-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($purchases as $index => $lot)
                            @php
                                $lotPurchases = $lot->purchases ?? collect();
                                $lotNo = $lot->lot_number ?? 'N/A';
                                $vendorName = $lot->vendor->name ?? 'N/A';
                                $vendorPhone = $lot->vendor->phone ?? '';
                                $whNames = $lotPurchases->pluck('warehouse.name')->filter()->unique()->implode(', ') ?: 'Main Yard';
                                $lotWeight = (float) $lotPurchases->sum('total_weight');
                                $lotBill = (float) $lotPurchases->sum('total_price');
                                $lotPaid = (float) $lotPurchases->sum('payment');
                                $lotDue = (float) $lotPurchases->sum('due');
                                $lotDate = $lot->lot_date ? $lot->lot_date->format('d M Y') : ($lot->created_at ? $lot->created_at->format('d M Y') : 'N/A');
                                $coilCount = (int) $lotPurchases->sum('quantity');
                                $itemCount = $lotPurchases->count();
                                $searchData = strtolower($lotNo . ' ' . $vendorName . ' ' . $whNames . ' ' . $lotWeight . ' ' . $lotBill . ' ' . $lotPaid . ' ' . $lotDue . ' ' . ($lotDue > 0 ? 'due unpaid partial' : 'paid'));
                            @endphp
                            <!-- Main Consignment Row (Consolidated by Lot - No Fractional Dividing!) -->
                            <tr class="purchase-row" data-purchase-id="{{ $lot->id }}" data-search="{{ $searchData }}" onclick="togglePurchaseDetails({{ $lot->id }}, event)" title="Click to view coils and items breakdown" style="cursor: pointer;">
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fe fe-chevron-right expand-icon text-muted fs-8" id="chevron-{{ $lot->id }}"></i>
                                        <span class="text-muted fw-semibold small">{{ $index + 1 }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-secondary small fw-semibold">
                                        {{ $lotDate }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('lots.show', $lot->id) }}" class="fw-bold text-primary font-monospace text-decoration-none d-inline-flex align-items-center gap-1" onclick="event.stopPropagation()">
                                        <i class="fe fe-package"></i>
                                        <span>{{ $lotNo }}</span>
                                    </a>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark d-block">{{ $vendorName }}</span>
                                    @if($vendorPhone)
                                        <small class="text-muted fs-8">{{ $vendorPhone }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 fs-7">
                                        <i class="fe fe-map-pin text-primary me-1"></i>{{ $whNames }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-dark rounded-pill px-2.5 py-1 fs-8">
                                        {{ $itemCount }} {{ Str::plural('Item', $itemCount) }}
                                    </span>
                                    @if($coilCount > 0)
                                        <small class="text-muted d-block fs-8 mt-0.5">
                                            {{ $coilCount }} {{ Str::plural('Coil', $coilCount) }}
                                        </small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold text-dark font-monospace d-block">
                                        {{ number_format($lotWeight, 2) }} kg
                                    </span>
                                    @if($lotWeight >= 1000)
                                        <small class="text-muted fs-8">({{ number_format($lotWeight / 1000, 2) }} MT)</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold text-dark font-monospace">
                                        ৳{{ number_format($lotBill, 2) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <!-- Actual Consignment Paid & Due Amounts (Whole Batch - Not Divided) -->
                                    <div class="d-inline-flex flex-column gap-1 text-start" style="min-width: 140px;">
                                        <span class="badge badge-soft-success px-2.5 py-1 rounded-pill fs-8 d-flex align-items-center justify-content-between">
                                            <span class="text-muted me-2">Paid:</span> <strong class="text-success font-monospace">৳{{ number_format($lotPaid, 2) }}</strong>
                                        </span>
                                        @if($lotDue > 0)
                                            <span class="badge badge-soft-danger px-2.5 py-1 rounded-pill fs-8 d-flex align-items-center justify-content-between">
                                                <span class="text-muted me-2">Due:</span> <strong class="text-danger font-monospace">৳{{ number_format($lotDue, 2) }}</strong>
                                            </span>
                                        @else
                                            <span class="badge badge-soft-success px-2.5 py-1 rounded-pill fs-8 d-flex align-items-center justify-content-between">
                                                <span class="text-muted me-2">Due:</span> <strong class="text-success font-monospace">৳0.00</strong>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="pe-4 text-center">
                                    @if($lotDue <= 0)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fs-8 rounded-pill">
                                            <i class="fe fe-check me-1"></i>Paid
                                        </span>
                                    @elseif($lotPaid > 0)
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1 fs-8 rounded-pill">
                                            <i class="fe fe-clock me-1"></i>Partial
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 fs-8 rounded-pill">
                                            <i class="fe fe-alert-triangle me-1"></i>Unpaid
                                        </span>
                                    @endif
                                </td>
                            </tr>

                            <!-- Minimal Expandable Item Breakdown Row (NO divided payment/due on items!) -->
                            <tr class="purchase-details-row" id="purchase-details-{{ $lot->id }}" style="display: none; background-color: #f8fafc;">
                                <td colspan="10" class="p-2 ps-5 pe-4 border-0">
                                    <div class="table-responsive bg-white rounded-2 border border-light-subtle shadow-sm">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="bg-light fs-8 text-uppercase text-secondary">
                                                <tr>
                                                    <th class="ps-3" style="width: 40px;">#</th>
                                                    <th>Specifications & Thickness</th>
                                                    <th>Dimensions / Size</th>
                                                    <th class="text-center">Qty / Coils</th>
                                                    <th class="text-end">Total Weight</th>
                                                    <th class="text-end">Unit Rate</th>
                                                    <th class="text-end pe-3">Sub Total</th>
                                                </tr>
                                            </thead>
                                            <tbody class="fs-7">
                                                @foreach($lotPurchases as $item)
                                                    <tr>
                                                        <td class="ps-3 text-muted fw-semibold fs-8">{{ $loop->iteration }}</td>
                                                        <td class="fw-bold text-dark">
                                                            {{ $item->thickness ? $item->thickness . ' mm' : 'Standard' }}
                                                        </td>
                                                        <td>
                                                            {{ $item->size ? $item->size . ' (' . ($item->size_type ?: 'ft') . ')' : ($item->size_type ?: 'Standard') }}
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-light text-dark border font-monospace">
                                                                {{ (int) $item->quantity }} {{ Str::plural('Coil', (int) $item->quantity) }}
                                                            </span>
                                                            @if($item->coils && $item->coils->count() > 0)
                                                                <small class="text-muted d-block fs-8">
                                                                    ({{ $item->coils->pluck('coil_number')->join(', ') }})
                                                                </small>
                                                            @endif
                                                        </td>
                                                        <td class="text-end font-monospace text-primary fw-semibold">
                                                            {{ number_format((float)($item->total_weight ?? 0), 2) }} kg
                                                        </td>
                                                        <td class="text-end font-monospace">৳{{ number_format((float)($item->unit_price ?? 0), 2) }}</td>
                                                        <td class="text-end font-monospace fw-bold text-dark pe-3">
                                                            ৳{{ number_format((float)($item->sub_price ?: $item->total_price ?: 0), 2) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @if($lot->notes || $lotPurchases->pluck('notes')->filter()->isNotEmpty())
                                        @php
                                            $lotNotesText = $lot->notes ?: $lotPurchases->pluck('notes')->filter()->join(' | ');
                                        @endphp
                                        @if($lotNotesText)
                                            <div class="mt-1 text-muted small fst-italic ps-2">
                                                <i class="fe fe-file-text me-1 text-info"></i>{{ $lotNotesText }}
                                            </div>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <div class="avatar avatar-xl bg-primary-light text-primary rounded-circle mb-3 d-flex align-items-center justify-content-center">
                                            <i class="fe fe-shopping-bag fs-1"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">No Purchase Data Found</h5>
                                        <p class="text-muted small mb-0">Adjust your date range or filter criteria</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($purchases->isNotEmpty())
                    <tfoot class="bg-light fw-bold">
                        <tr>
                            <td colspan="6" class="ps-3 text-uppercase text-secondary fs-7">Total Summary</td>
                            <td class="text-end text-dark fs-7">{{ number_format($purchases->sum(fn($l) => $l->purchases->sum('total_weight')), 2) }} kg</td>
                            <td class="text-end text-primary fs-7">৳{{ number_format($purchases->sum(fn($l) => $l->purchases->sum('total_price')), 2) }}</td>
                            <td class="text-center fs-8">
                                <span class="text-success me-1">Paid: ৳{{ number_format($purchases->sum(fn($l) => $l->purchases->sum('payment')), 2) }}</span>
                                <span class="text-muted">|</span>
                                <span class="text-danger ms-1">Due: ৳{{ number_format($purchases->sum(fn($l) => $l->purchases->sum('due')), 2) }}</span>
                            </td>
                            <td class="pe-4"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Expand / Collapse Consignment Details
    function togglePurchaseDetails(lotId, event) {
        if (event) {
            const target = event.target;
            if (target.closest('a') || target.closest('button') || target.closest('.dropdown') || target.closest('input') || target.closest('select')) {
                return;
            }
        }

        const detailsRow = document.getElementById('purchase-details-' + lotId);
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

    // Expand or Collapse All Consignment Details
    function toggleAllPurchases() {
        const detailRows = document.querySelectorAll('.purchase-details-row');
        const chevrons = document.querySelectorAll('.expand-icon');
        const toggleIcon = document.getElementById('toggleAllIcon');
        const toggleText = document.getElementById('toggleAllText');

        if (!detailRows.length) return;

        let anyHidden = false;
        detailRows.forEach(row => {
            if (row.style.display === 'none' || getComputedStyle(row).display === 'none') {
                anyHidden = true;
            }
        });

        detailRows.forEach(row => {
            row.style.display = anyHidden ? 'table-row' : 'none';
        });

        chevrons.forEach(chevron => {
            if (anyHidden) {
                chevron.classList.add('rotate-90');
            } else {
                chevron.classList.remove('rotate-90');
            }
        });

        if (toggleIcon && toggleText) {
            if (anyHidden) {
                toggleIcon.className = 'fe fe-minimize-2 me-1';
                toggleText.textContent = 'Collapse All';
            } else {
                toggleIcon.className = 'fe fe-maximize-2 me-1';
                toggleText.textContent = 'Expand All';
            }
        }
    }

    function updateToggleAllBtn() {
        const detailRows = document.querySelectorAll('.purchase-details-row');
        const toggleIcon = document.getElementById('toggleAllIcon');
        const toggleText = document.getElementById('toggleAllText');
        if (!detailRows.length || !toggleIcon || !toggleText) return;

        let allExpanded = true;
        detailRows.forEach(row => {
            if (row.style.display === 'none' || getComputedStyle(row).display === 'none') {
                allExpanded = false;
            }
        });

        if (allExpanded) {
            toggleIcon.className = 'fe fe-minimize-2 me-1';
            toggleText.textContent = 'Collapse All';
        } else {
            toggleIcon.className = 'fe fe-maximize-2 me-1';
            toggleText.textContent = 'Expand All';
        }
    }

    // Live Instant Search
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('purchaseSearchInput');
        const rows = document.querySelectorAll('.purchase-row');
        const countEl = document.getElementById('visiblePurchaseCount');

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                let count = 0;

                rows.forEach(row => {
                    const lotId = row.getAttribute('data-purchase-id');
                    const searchData = row.getAttribute('data-search') || '';
                    const detailsRow = document.getElementById('purchase-details-' + lotId);

                    if (query === '' || searchData.includes(query)) {
                        row.style.display = '';
                        count++;
                    } else {
                        row.style.display = 'none';
                        if (detailsRow) {
                            detailsRow.style.display = 'none';
                        }
                    }
                });

                if (countEl) {
                    countEl.textContent = count;
                }
            });
        }
    });
</script>
@endpush
@endsection
