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
                <h4 class="card-title fw-bold text-dark mb-1">Sales Report</h4>
                <p class="text-muted small mb-0">Comprehensive breakdown of customer sales, coil shipments, weights, rates, and revenue</p>
            </div>
            <div>
                <a href="{{ route('sales.report.pdf', request()->query()) }}" class="btn btn-outline-danger px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2" target="_blank">
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
                        <h6 class="text-muted fw-normal mb-1">Total Sales Records</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($salesReport->count()) }}</h4>
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
                        <h6 class="text-muted fw-normal mb-1">Total Weight Sold</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($salesReport->sum('qty'), 2) }} kg</h4>
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
                        <h6 class="text-muted fw-normal mb-1">Total Sales Revenue</h6>
                        <h4 class="mb-0 fw-bold text-dark">৳{{ number_format($salesReport->sum('total_price'), 2) }}</h4>
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
            <form action="{{ route('sales.report') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Customer</label>
                        <select name="customer_id" class="form-select border-light-subtle">
                            <option value="">All Customers</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} {{ $customer->phone ? '(' . $customer->phone . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Coil Number</label>
                        <select name="coil_id" class="form-select border-light-subtle">
                            <option value="">All Coils</option>
                            @foreach ($coils as $coil)
                                <option value="{{ $coil->id }}" {{ request('coil_id') == $coil->id ? 'selected' : '' }}>
                                    Coil No - {{ $coil->coil_number }}
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
                        <a href="{{ route('sales.report') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3">Reset</a>
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
                        <input type="text" id="salesReportSearchInput" class="form-control border-light-subtle" placeholder="Search customer, coil no, weight, price..." autocomplete="off">
                    </div>
                </div>
                <div class="col-12 col-md-6 text-md-end text-muted small">
                    Showing <span id="visibleSalesReportCount" class="fw-bold text-dark">{{ $salesReport->count() }}</span> records
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0" id="salesReportTable">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4" style="width: 80px;">#</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Coil No / Specification</th>
                            <th>Weight Sold</th>
                            <th>Unit Rate (BDT)</th>
                            <th class="pe-4">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($salesReport as $index => $saleItem)
                            @php
                                $custName = $saleItem->customer_name ?? 'N/A';
                                $custPhone = $saleItem->customer_phone ?? '';
                                $prodName = $saleItem->product_name ?? 'N/A';
                                $saleDate = $saleItem->sale_date ? \Carbon\Carbon::parse($saleItem->sale_date)->format('d M Y') : 'N/A';
                                $searchData = strtolower($custName . ' ' . $custPhone . ' ' . $prodName . ' ' . $saleItem->qty . ' ' . $saleItem->total_price);
                            @endphp
                            <tr class="sales-report-row" data-search="{{ $searchData }}">
                                <td class="ps-4 text-muted fw-semibold">{{ $index + 1 }}</td>
                                <td>
                                    <span class="text-secondary small fw-semibold">{{ $saleDate }}</span>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark d-block">{{ $custName }}</span>
                                    @if($custPhone)
                                        <small class="text-muted fs-7"><i class="fe fe-phone me-1"></i>{{ $custPhone }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-bold text-primary">
                                        @if(is_numeric($prodName) || str_starts_with($prodName, 'Coil'))
                                            {{ str_starts_with($prodName, 'Coil') ? $prodName : 'Coil No - ' . $prodName }}
                                        @else
                                            {{ $prodName }}
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-soft-info px-3 py-1 rounded-pill fs-7">
                                        {{ number_format($saleItem->qty, 2) }} kg
                                    </span>
                                </td>
                                <td>৳{{ number_format($saleItem->unit_price, 2) }}</td>
                                <td class="pe-4">
                                    <span class="badge badge-soft-primary px-3 py-1 rounded-pill fs-7">
                                        ৳{{ number_format($saleItem->total_price, 2) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <div class="avatar avatar-xl bg-primary-light text-primary rounded-circle mb-3 d-flex align-items-center justify-content-center">
                                            <i class="fe fe-shopping-cart fs-1"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">No Sales Report Data Found</h5>
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
    const searchInput = document.getElementById('salesReportSearchInput');
    const rows = document.querySelectorAll('.sales-report-row');
    const visibleCountSpan = document.getElementById('visibleSalesReportCount');

    function filterTable() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        let visibleCount = 0;

        rows.forEach(row => {
            const rowSearchText = row.dataset.search || '';
            if (query === '' || rowSearchText.includes(query)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (visibleCountSpan) {
            visibleCountSpan.textContent = visibleCount;
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
});
</script>
@endpush
@endsection
