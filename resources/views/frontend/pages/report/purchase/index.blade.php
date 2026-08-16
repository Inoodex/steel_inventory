@extends('frontend.layouts.app')

@push('styles')
<style>
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
                <p class="text-muted small mb-0">Track raw steel intake, vendor consignments, and procurement costs</p>
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

    <!-- Summary Stats Bar -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-4 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-shopping-cart fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Purchases</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($purchases->count()) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-4 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-layers fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Weight Intake</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($purchases->sum('total_weight'), 2) }} kg</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-4 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Purchase Value</h6>
                        <h4 class="mb-0 fw-bold text-dark">৳{{ number_format($purchases->sum('total_price'), 2) }}</h4>
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
                            @foreach ($lots as $lot)
                                <option value="{{ $lot->id }}" {{ request('lot_id') == $lot->id ? 'selected' : '' }}>
                                    {{ $lot->lot_number }}
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
        <!-- Live Search Header -->
        <div class="card-header bg-white py-3 border-bottom border-light">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-6">
                    <div class="search-box-custom">
                        <input type="text" id="purchaseSearchInput" class="form-control border-light-subtle" placeholder="Search lot no, vendor, warehouse, weight, amount..." autocomplete="off">
                    </div>
                </div>
                <div class="col-12 col-md-6 text-md-end text-muted small">
                    Showing <span id="visiblePurchaseCount" class="fw-bold text-dark">{{ $purchases->count() }}</span> records
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0" id="purchasesReportTable">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4" style="width: 80px;">#</th>
                            <th>Date</th>
                            <th>Lot Number</th>
                            <th>Vendor</th>
                            <th>Warehouse</th>
                            <th>Total Weight</th>
                            <th>Total Amount</th>
                            <th class="pe-4">Payment / Due</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($purchases as $index => $purchase)
                            @php
                                $lotNo = $purchase->lot->lot_number ?? 'N/A';
                                $vendorName = $purchase->vendor->name ?? 'N/A';
                                $whName = $purchase->warehouse->name ?? 'Main Yard';
                                $searchData = strtolower($lotNo . ' ' . $vendorName . ' ' . $whName . ' ' . $purchase->total_weight . ' ' . $purchase->total_price);
                            @endphp
                            <tr class="purchase-row" data-search="{{ $searchData }}">
                                <td class="ps-4 text-muted fw-semibold">{{ $index + 1 }}</td>
                                <td>
                                    <span class="text-secondary small fw-semibold">
                                        {{ $purchase->created_at ? $purchase->created_at->format('d M Y') : 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold text-primary font-monospace">{{ $lotNo }}</span>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark d-block">{{ $vendorName }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 fs-7">
                                        <i class="fe fe-map-pin text-primary me-1"></i>{{ $whName }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-soft-info px-3 py-1 rounded-pill fs-7">
                                        {{ number_format($purchase->total_weight, 2) }} kg
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-soft-primary px-3 py-1 rounded-pill fs-7">
                                        ৳{{ number_format($purchase->total_price, 2) }}
                                    </span>
                                </td>
                                <td class="pe-4">
                                    <span class="badge {{ $purchase->due > 0 ? 'badge-soft-danger' : 'badge-soft-success' }} px-3 py-1 rounded-pill fs-7">
                                        {{ $purchase->due > 0 ? 'Due: ৳' . number_format($purchase->due, 2) : 'Paid' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
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
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('purchaseSearchInput');
    const rows = document.querySelectorAll('.purchase-row');
    const countEl = document.getElementById('visiblePurchaseCount');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            let count = 0;

            rows.forEach(row => {
                const searchData = row.getAttribute('data-search') || '';
                if (query === '' || searchData.includes(query)) {
                    row.style.display = '';
                    count++;
                } else {
                    row.style.display = 'none';
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
