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
        background-color: #f8fafc !important;
    }
    .table-responsive {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }
    .dropdown-menu {
        z-index: 1060 !important;
    }
    .table-custom th, .table-custom td {
        white-space: nowrap;
        vertical-align: middle;
    }
    @media (max-width: 1400px) {
        .table-custom th, .table-custom td {
            padding: 8px 10px !important;
            font-size: 12.5px;
        }
        .stat-card h4 {
            font-size: 1.25rem !important;
        }
    }
    .badge-soft-success {
        background-color: rgba(22, 163, 74, 0.12) !important;
        color: #16a34a !important;
        font-weight: 600;
    }
    .badge-soft-danger {
        background-color: rgba(220, 38, 38, 0.12) !important;
        color: #dc2626 !important;
        font-weight: 600;
    }
    .badge-soft-warning {
        background-color: rgba(217, 119, 6, 0.12) !important;
        color: #d97706 !important;
        font-weight: 600;
    }
    .badge-soft-primary {
        background-color: rgba(118, 56, 255, 0.12) !important;
        color: #7638ff !important;
        font-weight: 600;
    }
    .badge-soft-secondary {
        background-color: rgba(100, 116, 139, 0.12) !important;
        color: #475569 !important;
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
        background-color: #f1f5f9 !important;
        color: #1e293b !important;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Lot Profitability &amp; Lifecycle Report</h4>
                <p class="text-muted small mb-0">In-depth analytics of procurement costs, sales revenue, customer distributions, and gross profit/loss per lot</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('lots.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Manage Lots</span>
                </a>
                <a href="{{ route('lots.report.pdf', request()->query()) }}" class="btn btn-outline-danger px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2" target="_blank">
                    <i class="fe fe-download fs-6"></i>
                    <span>Export PDF</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0" style="border-left: 4px solid #7638ff !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Total Analyzed Lots</span>
                        <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center" style="background: rgba(118, 56, 255, 0.12); color: #7638ff;">
                            <i class="fe fe-layers fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 flex-wrap">
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalLotsCount) }}</h4>
                        <span class="badge badge-soft-success rounded-pill px-2">{{ $totalActiveLots }} Active</span>
                        <span class="badge badge-soft-secondary rounded-pill px-2">{{ $totalClosedLots }} Closed</span>
                    </div>
                    <small class="text-muted d-block mt-2">Vendor Dues: <strong class="text-danger">৳{{ number_format($totalVendorDue, 2) }}</strong></small>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0" style="border-left: 4px solid #0ea5e9 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Steel Weight Movement</span>
                        <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center" style="background: rgba(14, 165, 233, 0.12); color: #0ea5e9;">
                            <i class="fe fe-database fs-5"></i>
                        </div>
                    </div>
                    <h4 class="mb-1 fw-bold text-dark">{{ number_format($totalIntakeWeight, 2) }} <span class="fs-7 text-muted fw-normal">kg In</span></h4>
                    <div class="d-flex justify-content-between text-muted small mt-2 flex-wrap gap-1">
                        <span>Sold: <strong class="text-primary">{{ number_format($totalSoldWeight, 2) }} kg</strong></span>
                        <span>Stock: <strong class="text-success">{{ number_format($totalRemainingWeight, 2) }} kg</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0" style="border-left: 4px solid #3b82f6 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Procurement vs Sales</span>
                        <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center" style="background: rgba(59, 130, 246, 0.12); color: #3b82f6;">
                            <i class="fe fe-shopping-cart fs-5"></i>
                        </div>
                    </div>
                    <h4 class="mb-1 fw-bold text-dark">৳{{ number_format($totalSalesRevenue, 2) }} <span class="fs-8 text-muted fw-normal">Invoiced</span></h4>
                    <small class="text-muted d-block mt-2">Purchased Cost: <strong>৳{{ number_format($totalPurchaseValuation, 2) }}</strong></small>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0" style="border-left: 4px solid {{ $totalRealizedProfit >= 0 ? '#16a34a' : '#dc2626' }} !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Net Realized Profit / Loss</span>
                        <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center" style="background: {{ $totalRealizedProfit >= 0 ? 'rgba(22, 163, 74, 0.12)' : 'rgba(220, 38, 38, 0.12)' }}; color: {{ $totalRealizedProfit >= 0 ? '#16a34a' : '#dc2626' }};">
                            <i class="fe {{ $totalRealizedProfit >= 0 ? 'fe-trending-up' : 'fe-trending-down' }} fs-5"></i>
                        </div>
                    </div>
                    <h4 class="mb-1 fw-bold {{ $totalRealizedProfit >= 0 ? 'text-success' : 'text-danger' }}">
                        ৳{{ number_format($totalRealizedProfit, 2) }}
                    </h4>
                    <div class="d-flex align-items-center justify-content-between mt-2 small">
                        <span class="text-muted">Overall Margin:</span>
                        <span class="badge {{ $overallProfitMargin >= 0 ? 'badge-soft-success' : 'badge-soft-danger' }} rounded-pill px-2 py-1">
                            {{ $overallProfitMargin }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Summary KPI Cards -->

    <!-- Multi-Criteria Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
            <h6 class="fw-bold text-dark mb-3"><i class="fe fe-filter me-2 text-primary"></i>Filter Lots &amp; Performance</h6>
            <form action="{{ route('lots.report') }}" method="GET">
                <div class="row g-3 align-items-end">

                    <!-- Vendor Filter -->
                    <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Vendor / Supplier</label>
                        <select name="vendor_id" class="form-select border-light-subtle">
                            <option value="">All Vendors</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->name }} {{ $vendor->company_name ? "({$vendor->company_name})" : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Customer Filter -->
                    <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Purchased by Customer</label>
                        <select name="customer_id" class="form-select border-light-subtle">
                            <option value="">All Customers</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} ({{ $customer->phone }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Profitability Status -->
                    <div class="col-xl-2 col-lg-4 col-md-4 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Profitability</label>
                        <select name="profitability" class="form-select border-light-subtle">
                            <option value="">All Statuses</option>
                            <option value="profitable" {{ request('profitability') == 'profitable' ? 'selected' : '' }}>Profitable (> ৳0)</option>
                            <option value="loss" {{ request('profitability') == 'loss' ? 'selected' : '' }}>Loss Making (< ৳0)</option>
                            <option value="sold_out" {{ request('profitability') == 'sold_out' ? 'selected' : '' }}>100% Sold Out</option>
                            <option value="in_stock" {{ request('profitability') == 'in_stock' ? 'selected' : '' }}>Has Stock</option>
                        </select>
                    </div>

                    <!-- Date Preset -->
                    <div class="col-xl-2 col-lg-4 col-md-4 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Quick Date</label>
                        <select name="date_preset" class="form-select border-light-subtle" id="lotDatePreset">
                            <option value="">Custom Date</option>
                            <option value="today" {{ request('date_preset') == 'today' ? 'selected' : '' }}>Today</option>
                            <option value="this_week" {{ request('date_preset') == 'this_week' ? 'selected' : '' }}>This Week</option>
                            <option value="this_month" {{ request('date_preset') == 'this_month' ? 'selected' : '' }}>This Month</option>
                            <option value="last_month" {{ request('date_preset') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                            <option value="this_year" {{ request('date_preset') == 'this_year' ? 'selected' : '' }}>This Year</option>
                        </select>
                    </div>

                    <!-- Lot Status -->
                    <div class="col-xl-2 col-lg-4 col-md-4 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Lot Status</label>
                        <select name="status" class="form-select border-light-subtle">
                            <option value="all">All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>

                    <!-- Custom Date From -->
                    <div class="col-xl-3 col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">From Date</label>
                        <input type="date" name="from_date" class="form-control border-light-subtle" value="{{ request('from_date') }}">
                    </div>

                    <!-- Custom Date To -->
                    <div class="col-xl-3 col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">To Date</label>
                        <input type="date" name="to_date" class="form-control border-light-subtle" value="{{ request('to_date') }}">
                    </div>

                    <!-- Warehouse -->
                    <div class="col-xl-3 col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Warehouse / Yard</label>
                        <select name="warehouse_id" class="form-select border-light-subtle">
                            <option value="">All Warehouses</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                    {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="col-xl-3 col-lg-3 col-md-6 col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary text-nowrap rounded-3 px-3 py-2 flex-fill d-inline-flex align-items-center justify-content-center gap-1 shadow-sm">
                            <i class="fe fe-filter"></i>
                            <span>Filter</span>
                        </button>
                        <a href="{{ route('lots.report') }}" class="btn btn-outline-secondary text-nowrap rounded-3 px-3 py-2 d-inline-flex align-items-center justify-content-center shadow-sm">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- /Filter Card -->
    <!-- /Filter Card -->

    <!-- Report Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <!-- Table Header Bar -->
            <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-light text-primary fs-7 rounded-pill px-3 py-2">
                        Showing <strong id="visibleLotCount">{{ $analyzedLots->count() }}</strong> Lots
                    </span>
                </div>
                <div class="col-md-4 col-12">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-light-subtle text-muted"><i class="fe fe-search"></i></span>
                        <input type="text" id="lotReportSearchInput" class="form-control border-light-subtle" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0" id="lotReportTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 40px;">#</th>
                            <th>Lot Number</th>
                            <th>Vendor</th>
                            <th>Intake Wt &amp; Cost</th>
                            <th>Sold Wt &amp; Revenue</th>
                            <th>Stock Remaining</th>
                            <th>Customers</th>
                            <th>Gross Profit / Loss</th>
                            <!-- <th class="text-center">Status</th> -->
                            <th class="text-end" style="width: 70px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($analyzedLots as $index => $item)
                            @php
                                $searchString = strtolower($item->lot_number . ' ' . $item->vendor_name . ' ' . $item->vendor_company . ' ' . $item->warehouse_name . ' ' . $item->customers->pluck('name')->implode(' '));
                            @endphp
                            <tr class="lot-report-row" data-search="{{ $searchString }}">
                                <td class="text-center text-muted fw-semibold">{{ $index + 1 }}</td>
                                <td>
                                    <div>
                                        <a href="{{ route('lots.show', $item->id) }}" class="fw-bold text-dark text-decoration-none d-block">
                                            {{ $item->lot_number }}
                                        </a>
                                        <small class="text-muted d-block">
                                            <i class="fe fe-calendar me-1"></i>{{ $item->lot_date ? \Carbon\Carbon::parse($item->lot_date)->format('d M Y') : 'N/A' }}
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="fw-semibold text-dark d-block">{{ $item->vendor_name }}</span>
                                        @if($item->vendor_company)
                                            <small class="text-muted">{{ $item->vendor_company }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="fw-bold text-dark d-block">{{ number_format($item->intake_weight, 2) }} kg</span>
                                        <!-- <small class="text-muted">
                                            @ ৳{{ number_format($item->avg_purchase_rate, 2) }}/kg &bull; <strong class="text-dark">৳{{ number_format($item->purchase_cost, 2) }}</strong>
                                        </small> -->
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="fw-bold text-primary d-block">{{ number_format($item->sold_weight, 2) }} kg</span>
                                        <!-- <small class="text-muted">
                                            @ ৳{{ number_format($item->avg_sell_rate, 2) }}/kg &bull; <strong class="text-primary">৳{{ number_format($item->sales_revenue, 2) }}</strong>
                                        </small> -->
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="fw-semibold {{ $item->remaining_weight > 0 ? 'text-success' : 'text-muted' }} d-block">
                                            {{ number_format($item->remaining_weight, 2) }} kg
                                        </span>
                                        <!-- <small class="text-muted">Val: ৳{{ number_format($item->remaining_valuation, 2) }}</small> -->
                                    </div>
                                </td>
                                <td>
                                    @if($item->customers_count > 0)
                                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 rounded-pill d-inline-flex align-items-center gap-1 fs-8" data-bs-toggle="modal" data-bs-target="#lotCustomerModal-{{ $item->id }}">
                                            <i class="fe fe-users"></i>
                                            <span>{{ $item->customers_count }} Customer{{ $item->customers_count > 1 ? 's' : '' }}</span>
                                        </button>
                                    @else
                                        <span class="badge badge-soft-secondary rounded-pill px-2 py-1 fs-8">No Sales Yet</span>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        @if($item->sales_revenue > 0)
                                            <span class="fw-bold {{ $item->realized_profit >= 0 ? 'text-success' : 'text-danger' }} d-block">
                                                ৳{{ number_format($item->realized_profit, 2) }}
                                            </span>
                                            <span class="badge {{ $item->profit_margin >= 0 ? 'badge-soft-success' : 'badge-soft-danger' }} rounded-pill px-2 py-0 fs-8">
                                                {{ $item->profit_margin }}% Margin
                                            </span>
                                        @else
                                            <span class="badge badge-soft-secondary rounded-pill px-2 py-1 fs-8">In Stock</span>
                                        @endif
                                    </div>
                                </td>
                                <!-- <td class="text-center">
                                    <span class="badge {{ $item->status === 'active' ? 'badge-soft-success' : 'badge-soft-secondary' }} px-2 py-1 rounded-pill">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </td> -->
                                <td class="text-end">
                                    <div class="dropdown dropdown-action">
                                        <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                            <a class="dropdown-item py-2" href="{{ route('lots.show', $item->id) }}">
                                                <i class="fe fe-eye me-2 text-primary"></i>View Lot Profile
                                            </a>
                                            @if($item->customers_count > 0)
                                                <a class="dropdown-item py-2" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#lotCustomerModal-{{ $item->id }}">
                                                    <i class="fe fe-users me-2 text-info"></i>View Customer
                                                </a>
                                            @endif
                                            <a class="dropdown-item py-2" href="{{ route('lots.report.pdf', array_merge(request()->query(), ['lot_id' => $item->id])) }}" target="_blank">
                                                <i class="fe fe-download me-2 text-danger"></i>Export PDF
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="noLotRow">
                                <td colspan="10" class="text-center py-5">
                                    <div class="py-3">
                                        <i class="fe fe-layers text-muted fs-1 mb-2 d-block"></i>
                                        <h5 class="fw-bold text-dark mb-1">No Lot Data Found</h5>
                                        <p class="text-muted small mb-0">Try changing your filters or date range.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($analyzedLots->count() > 0)
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="3" class="text-end text-dark">Grand Totals:</td>
                            <td class="text-dark">
                                <div>{{ number_format($totalIntakeWeight, 2) }} kg</div>
                                <small class="text-muted">Cost: ৳{{ number_format($totalPurchaseValuation, 2) }}</small>
                            </td>
                            <td class="text-primary">
                                <div>{{ number_format($totalSoldWeight, 2) }} kg</div>
                                <small>Rev: ৳{{ number_format($totalSalesRevenue, 2) }}</small>
                            </td>
                            <td class="text-success">{{ number_format($totalRemainingWeight, 2) }} kg</td>
                            <td></td>
                            <td class="{{ $totalRealizedProfit >= 0 ? 'text-success' : 'text-danger' }}">
                                <div>৳{{ number_format($totalRealizedProfit, 2) }}</div>
                                <small>Margin: {{ $overallProfitMargin }}%</small>
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
    <!-- /Report Table Card -->

</div>

{{-- MODALS PLACED AT THE BOTTOM OF BLADE OUTSIDE THE TABLE CONTAINER (Per AGENTS.md rules) --}}
@foreach($analyzedLots as $item)
    @if($item->customers_count > 0)
    <div class="modal fade" id="lotCustomerModal-{{ $item->id }}" tabindex="-1" aria-labelledby="lotCustomerModalLabel-{{ $item->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom px-4 py-3 bg-light rounded-top-4">
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="lotCustomerModalLabel-{{ $item->id }}">
                            <i class="fe fe-users text-primary me-2"></i>Customer Breakdown &bull; {{ $item->lot_number }}
                        </h5>
                        <small class="text-muted">All customers and sales orders dispatched from this lot</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-4">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <small class="text-muted d-block">Sold Quantity</small>
                                <strong class="text-dark fs-6">{{ number_format($item->sold_weight, 2) }} kg</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <small class="text-muted d-block">Sales Revenue</small>
                                <strong class="text-primary fs-6">৳{{ number_format($item->sales_revenue, 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <small class="text-muted d-block">Realized Profit</small>
                                <strong class="{{ $item->realized_profit >= 0 ? 'text-success' : 'text-danger' }} fs-6">৳{{ number_format($item->realized_profit, 2) }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Customer</th>
                                    <th>Order No &amp; Date</th>
                                    <th class="text-end">Qty (kg)</th>
                                    <th class="text-end">Sell Rate (৳)</th>
                                    <th class="text-end">Total (৳)</th>
                                    <th class="text-end">Profit (৳)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($item->customers as $cust)
                                    @foreach($cust['orders'] as $ord)
                                        <tr>
                                            <td class="fw-semibold text-dark">
                                                {{ $cust['name'] }}
                                                <small class="text-muted d-block">{{ $cust['phone'] }}</small>
                                            </td>
                                            <td>
                                                <a href="{{ route('sales.show', $ord['sale_id']) }}" class="text-primary fw-bold" target="_blank">
                                                    {{ $ord['order_no'] }}
                                                </a>
                                                <small class="text-muted d-block">{{ $ord['date'] }}</small>
                                            </td>
                                            <td class="text-end fw-semibold">{{ number_format($ord['qty'], 2) }}</td>
                                            <td class="text-end">৳{{ number_format($ord['unit_price'], 2) }}</td>
                                            <td class="text-end fw-bold text-dark">৳{{ number_format($ord['total'], 2) }}</td>
                                            <td class="text-end fw-bold {{ $ord['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                ৳{{ number_format($ord['profit'], 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-2 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary px-4 py-2 rounded-3" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endforeach

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('lotReportSearchInput');
    const rows = document.querySelectorAll('.lot-report-row');
    const visibleCountSpan = document.getElementById('visibleLotCount');

    if (searchInput && rows.length > 0) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            let visibleCount = 0;

            rows.forEach(function (row) {
                const searchData = row.getAttribute('data-search') || '';
                if (searchData.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (visibleCountSpan) {
                visibleCountSpan.textContent = visibleCount;
            }
        });
    }
});
</script>
@endpush
