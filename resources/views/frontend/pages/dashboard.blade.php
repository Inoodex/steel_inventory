@extends('frontend.layouts.app')

@push('styles')
    <style>
        .stat-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid rgba(0, 0, 0, 0.06) !important;
            border-radius: 12px !important;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.07) !important;
        }
        .badge-soft-primary {
            background-color: rgba(118, 56, 255, 0.12) !important;
            color: #7638ff !important;
            font-weight: 600;
        }
        .badge-soft-success {
            background-color: rgba(25, 135, 84, 0.12) !important;
            color: #198754 !important;
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
        .badge-soft-danger {
            background-color: rgba(220, 53, 69, 0.12) !important;
            color: #dc3545 !important;
            font-weight: 600;
        }
        .btn-action-icon {
            width: 32px; height: 32px;
            display: inline-flex; align-items: center; justify-content: center;
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
        .table-custom th, .table-custom td { white-space: nowrap; }
        .table-custom tbody tr {
            transition: background-color 0.15s ease;
        }
        .table-custom tbody tr:hover {
            background-color: #fcfbff !important;
        }
        .financial-card {
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.06);
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header & Action Bar -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">
                    Welcome back, {{ auth()->user()->name }}!
                </h4>
                <p class="text-muted small mb-0">
                    Ship breaking & steel yard operations, coil tracking, sales dispatch, and accounting overview.
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('sales.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-shopping-cart fs-6"></i>
                    <span>New Steel Sale</span>
                </a>
                <a href="{{ route('purchase.create') }}" class="btn btn-outline-primary px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-plus-circle fs-6"></i>
                    <span>Steel Inward</span>
                </a>
                <a href="{{ route('dailyExpenses.create') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-dollar-sign fs-6"></i>
                    <span>Add Expense</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- ROW 1: Core Steel ERP Yard & Live Stock KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-disc fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">In-Stock Coils</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($inStockCoilCount ?? 0) }} Coils</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-anchor fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Stock Weight</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalYardTonnage ?? 0, 2) }} MT</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-package fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Active Procurement Lots</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalLots ?? 0) }} Lots</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-warning-light text-warning rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-database fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Stock Valuation (৳)</h6>
                        <h4 class="mb-0 fw-bold text-dark">৳{{ number_format($stockValuation ?? 0, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /ROW 1 -->

    <!-- ROW 2: Double-Entry Financial Health Section (Super Admin / Accounts) -->
    @if(auth()->check() && auth()->user()->hasRole(['Super Admin', 'Admin', 'admin']))
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="fe fe-shield me-2 text-primary"></i>Financial Health & Balances
                </h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('chart-of-accounts.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        <i class="fe fe-folder me-1"></i> Chart of Accounts
                    </a>
                    <a href="{{ route('journal-entries.create') }}" class="btn btn-sm btn-primary rounded-pill px-3">
                        <i class="fe fe-plus me-1"></i> New Voucher
                    </a>
                    <a href="{{ route('trial-balance.index') }}" class="btn btn-sm btn-outline-success rounded-pill px-3">
                        <i class="fe fe-check-square me-1"></i> Trial Balance
                    </a>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-xl-3 col-md-6 col-12">
                    <div class="card stat-card financial-card bg-white shadow-sm h-100 mb-0 border-start border-4 border-success">
                        <div class="card-body d-flex align-items-center">
                            <div class="avatar avatar-lg bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                                <i class="fe fe-dollar-sign fs-4"></i>
                            </div>
                            <div>
                                <h6 class="text-muted fw-normal mb-1">Cash in Hand (1110)</h6>
                                <h4 class="mb-0 fw-bold text-dark">৳{{ number_format($liquidCash ?? 0, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 col-12">
                    <div class="card stat-card financial-card bg-white shadow-sm h-100 mb-0 border-start border-4 border-info">
                        <div class="card-body d-flex align-items-center">
                            <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                                <i class="fe fe-credit-card fs-4"></i>
                            </div>
                            <div>
                                <h6 class="text-muted fw-normal mb-1">Total Bank Balance</h6>
                                <h4 class="mb-0 fw-bold text-dark">৳{{ number_format($bankBalance ?? 0, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 col-12">
                    <div class="card stat-card financial-card bg-white shadow-sm h-100 mb-0 border-start border-4 border-warning">
                        <div class="card-body d-flex align-items-center">
                            <div class="avatar avatar-lg bg-warning-light text-warning rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                                <i class="fe fe-user-check fs-4"></i>
                            </div>
                            <div>
                                <h6 class="text-muted fw-normal mb-1">Receivables (AR - 1130)</h6>
                                <h4 class="mb-0 fw-bold text-dark">৳{{ number_format($receivables ?? 0, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 col-12">
                    <div class="card stat-card financial-card bg-white shadow-sm h-100 mb-0 border-start border-4 border-danger">
                        <div class="card-body d-flex align-items-center">
                            <div class="avatar avatar-lg bg-danger-light text-danger rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                                <i class="fe fe-truck fs-4"></i>
                            </div>
                            <div>
                                <h6 class="text-muted fw-normal mb-1">Payables (AP - 2110)</h6>
                                <h4 class="mb-0 fw-bold text-dark">৳{{ number_format($payables ?? 0, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <!-- /ROW 2 -->

    <!-- ROW 3: Monthly & Daily Performance Overview -->
    <div class="mb-4">
        <h5 class="fw-bold text-dark mb-3">
            <i class="fe fe-trending-up me-2 text-success"></i>Revenue & Operations Performance
        </h5>
        <div class="row g-3">
            <div class="col-xl-3 col-md-6 col-12">
                <div class="card stat-card bg-white shadow-sm h-100 mb-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="avatar avatar-lg bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="fe fe-dollar-sign fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted fw-normal mb-1">Today's Sales</h6>
                            <h4 class="mb-0 fw-bold text-dark">৳{{ number_format($todaysSalesRevenue ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 col-12">
                <div class="card stat-card bg-white shadow-sm h-100 mb-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="avatar avatar-lg bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="fe fe-bar-chart-2 fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted fw-normal mb-1">This Month Sales</h6>
                            <h4 class="mb-0 fw-bold text-dark">৳{{ number_format($thisMonthsSalesRevenue ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 col-12">
                <div class="card stat-card bg-white shadow-sm h-100 mb-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="fe fe-shopping-cart fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted fw-normal mb-1">This Month Inward</h6>
                            <h4 class="mb-0 fw-bold text-dark">৳{{ number_format($thisMonthsPurchaseRevenue ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 col-12">
                <div class="card stat-card bg-white shadow-sm h-100 mb-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="avatar avatar-lg bg-danger-light text-danger rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="fe fe-pie-chart fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted fw-normal mb-1">This Month Expense</h6>
                            <h4 class="mb-0 fw-bold text-dark">৳{{ number_format($thisMonthsExpense ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /ROW 3 -->

    <!-- ROW 4: Interactive Charts Section -->
    <div class="row g-3 mb-4">
        <div class="col-xl-7 col-12 d-flex">
            <div class="card border-0 shadow-sm rounded-3 flex-fill">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fe fe-bar-chart-2 me-2 text-primary"></i>Monthly Sales & Procurement Trends ({{ date('Y') }})
                    </h6>
                </div>
                <div class="card-body">
                    <div id="monthly_sales_chart" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-5 col-12 d-flex">
            <div class="card border-0 shadow-sm rounded-3 flex-fill">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fe fe-trending-up me-2 text-success"></i>Yearly Revenue Trajectory
                    </h6>
                </div>
                <div class="card-body">
                    <div id="yearly_sales_chart" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- /ROW 4 -->

    <!-- ROW 5: Live Operations & Recent Tables -->
    <div class="row g-3">
        <!-- Live In-Stock Coils in Yard -->
        <div class="col-xl-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fe fe-disc me-2 text-primary"></i>Live In-Stock Coils in Yard
                    </h6>
                    <a href="{{ route('coils.index') }}" class="btn btn-sm btn-outline-primary rounded-2 px-3">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom align-middle mb-0">
                            <thead class="bg-light text-secondary fs-7 text-uppercase">
                                <tr>
                                    <th class="ps-3">Coil Tag #</th>
                                    <th>Specs (Thick / Size)</th>
                                    <th>Remaining (kg)</th>
                                    <th>Yard Location</th>
                                    <th class="pe-3 text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody class="border-top-0">
                                @forelse ($recentCoils ?? [] as $coil)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="fw-bold text-dark font-monospace fs-8">{{ $coil->coil_number }}</span>
                                            @if($coil->lot)
                                                <small class="text-muted d-block">{{ $coil->lot->lot_number }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-dark">{{ $coil->thickness ?? '-' }}</span>
                                            <small class="text-muted">[{{ $coil->width ?? '-' }} × {{ $coil->length ?? '-' }}]</small>
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-success px-2 py-1 rounded-2">
                                                {{ number_format($coil->remaining_weight, 2) }} kg
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted small">
                                                <i class="fe fe-map-pin text-secondary me-1"></i>{{ $coil->warehouse->name ?? 'Main Yard' }}
                                            </span>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <span class="badge badge-soft-success px-2 py-1 rounded-pill fs-8">In Stock</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted small">No coils currently in stock</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Steel Sales Orders -->
        <div class="col-xl-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fe fe-shopping-cart me-2 text-success"></i>Recent Steel Sales Orders
                    </h6>
                    <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-primary rounded-2 px-3">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom align-middle mb-0">
                            <thead class="bg-light text-secondary fs-7 text-uppercase">
                                <tr>
                                    <th class="ps-3">Order / Customer</th>
                                    <th>Payable Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th class="pe-3 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody class="border-top-0">
                                @forelse ($recentSales ?? [] as $sale)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="fw-bold text-dark d-block">{{ $sale->order_no ?? ('SAL-' . $sale->id) }}</span>
                                            <span class="text-muted small">{{ $sale->customer->name ?? 'Direct Buyer' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-primary px-3 py-1 rounded-pill fs-7">৳{{ number_format($sale->payble ?? $sale->total, 2) }}</span>
                                        </td>
                                        <td>
                                            @if($sale->status === 'paid')
                                                <span class="badge badge-soft-success px-2 py-1 rounded-pill fs-8">Paid</span>
                                            @elseif($sale->status === 'partial')
                                                <span class="badge badge-soft-warning px-2 py-1 rounded-pill fs-8">Partial</span>
                                            @else
                                                <span class="badge badge-soft-danger px-2 py-1 rounded-pill fs-8">Credit / Due</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-secondary small">{{ $sale->created_at ? $sale->created_at->format('d M, Y') : 'N/A' }}</span>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <a href="{{ route('sales.invoice', $sale->id) }}" class="btn btn-sm btn-outline-secondary rounded-2 px-2 py-1">
                                                <i class="fe fe-file-text"></i> Invoice
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted small">No recent sales records found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /ROW 5 -->

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const monthlyRevData = @json($monthlyRevenue ?? []);
        const monthlyPurData = @json($monthlyPurchases ?? []);
        const yearlyRevData  = @json($yearlyRevenue ?? []);

        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const monthlySalesValues = months.map(m => monthlyRevData[m] || 0);
        const monthlyPurValues   = months.map(m => monthlyPurData[m] || 0);
        const years = Object.keys(yearlyRevData);
        const yearlyValues = years.map(y => yearlyRevData[y] || 0);

        // Monthly Sales & Procurement Chart
        const monthlyEl = document.querySelector('#monthly_sales_chart');
        if (monthlyEl && typeof ApexCharts !== 'undefined') {
            new ApexCharts(monthlyEl, {
                series: [
                    { name: 'Sales Revenue (৳)', data: monthlySalesValues },
                    { name: 'Steel Procurement (৳)', data: monthlyPurValues }
                ],
                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                colors: ['#7638ff', '#0dcaf0'],
                plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 5 } },
                dataLabels: { enabled: false },
                stroke: { show: true, width: 2, colors: ['transparent'] },
                xaxis: { categories: months },
                yaxis: { labels: { formatter: val => '৳' + Number(val).toLocaleString() } },
                fill: { opacity: 1 },
                tooltip: { y: { formatter: val => '৳' + Number(val).toLocaleString() } },
                legend: { position: 'top', horizontalAlign: 'right' }
            }).render();
        }

        // Yearly Sales Chart
        const yearlyEl = document.querySelector('#yearly_sales_chart');
        if (yearlyEl && typeof ApexCharts !== 'undefined') {
            new ApexCharts(yearlyEl, {
                series: [{ name: 'Sales Revenue (৳)', data: yearlyValues }],
                chart: { type: 'area', height: 320, toolbar: { show: false } },
                colors: ['#10b981'],
                stroke: { curve: 'smooth', width: 3 },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05 } },
                xaxis: { categories: years },
                yaxis: { labels: { formatter: val => '৳' + Number(val).toLocaleString() } },
                tooltip: { y: { formatter: val => '৳' + Number(val).toLocaleString() } }
            }).render();
        }
    });
</script>
@endpush
@endsection