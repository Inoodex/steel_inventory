@extends('frontend.layouts.app')

@push('styles')
<style>
    .stat-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid rgba(0, 0, 0, 0.06) !important;
        border-radius: 12px !important;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.06) !important;
    }
    .stat-card .card-body {
        padding: 1rem !important;
    }
    .avatar-kpi {
        width: 34px;
        height: 34px;
        min-width: 34px;
        font-size: 0.95rem;
    }
    .kpi-title {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        color: #64748b;
    }
    .kpi-value {
        font-size: 1.3rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .kpi-sub {
        font-size: 0.75rem;
        color: #64748b;
    }
    @media (max-width: 1400px) {
        .stat-card .card-body {
            padding: 0.85rem !important;
        }
        .avatar-kpi {
            width: 30px;
            height: 30px;
            min-width: 30px;
            font-size: 0.85rem;
        }
        .kpi-title {
            font-size: 0.72rem;
        }
        .kpi-value {
            font-size: 1.15rem;
        }
        .kpi-sub {
            font-size: 0.72rem;
        }
    }
    .table-custom tbody tr:hover td {
        background-color: #f8fafc !important;
    }
    .table-custom th, .table-custom td {
        white-space: nowrap;
        vertical-align: middle;
    }
    .table-responsive {
        overflow: visible !important;
    }
    .dropdown-menu {
        z-index: 1060 !important;
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
        background-color: #4338ca !important;
        color: #ffffff !important;
        border-color: #4338ca !important;
    }
    .badge-soft-success {
        background-color: rgba(22, 163, 74, 0.12) !important;
        color: #16a34a !important;
        font-weight: 600;
    }
    .badge-soft-warning {
        background-color: rgba(217, 119, 6, 0.14) !important;
        color: #d97706 !important;
        font-weight: 600;
    }
    .badge-soft-primary {
        background-color: rgba(67, 56, 202, 0.12) !important;
        color: #4338ca !important;
        font-weight: 600;
    }
    .badge-soft-info {
        background-color: rgba(2, 132, 199, 0.12) !important;
        color: #0284c7 !important;
        font-weight: 600;
    }
    .badge-soft-secondary {
        background-color: rgba(100, 116, 139, 0.12) !important;
        color: #64748b !important;
        font-weight: 600;
    }
    .nav-tabs-custom .nav-link {
        border: none;
        border-bottom: 2px solid transparent;
        color: #64748b;
        font-weight: 600;
        padding: 12px 20px;
        transition: all 0.2s ease;
    }
    .nav-tabs-custom .nav-link.active {
        color: #4338ca;
        border-bottom: 2px solid #4338ca;
        background: transparent;
    }
    .batch-bar {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1050;
        min-width: 480px;
        max-width: 90%;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.25);
        border-radius: 50px;
        animation: slideUp 0.3s ease;
    }
    @keyframes slideUp {
        from { transform: translate(-50%, 60px); opacity: 0; }
        to { transform: translate(-50%, 0); opacity: 1; }
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header (Strict Rule: NO breadcrumbs) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Worker Charges &amp; Payouts</h4>
                <p class="text-muted small mb-0">Disburse pass-through extra charges (Labour, Transport/Delivery, Weight Scale) &amp; manage daily/weekly worker settlement records</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a class="btn btn-outline-danger px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2" 
                   href="{{ route('sales.extra-charges-report.pdf', request()->all()) }}" target="_blank">
                    <i class="fe fe-file-text fs-6"></i>
                    <span>Export Summary PDF</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm d-flex align-items-center justify-content-between mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="fe fe-check-circle fs-5"></i>
                <span>{{ session('success') }}</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if(session('last_payout_id'))
                    <a href="{{ route('worker-payouts.voucher-pdf', session('last_payout_id')) }}" target="_blank" class="btn btn-sm btn-light text-success fw-bold">
                        <i class="fe fe-printer me-1"></i> Print Voucher
                    </a>
                @endif
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="fe fe-alert-circle fs-5"></i>
            <span>{{ session('error') }}</span>
            <button type="button" class="btn-close shadow-none ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- 4 KPI Summary Cards (Optimized for Laptop & Desktop Responsiveness) -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Pass-Through Liability -->
        <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm h-100 mb-0">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="kpi-title text-truncate me-2">Total Extra Charges</span>
                            <div class="avatar-kpi bg-primary-light text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                                <i class="fe fe-layers"></i>
                            </div>
                        </div>
                        <div class="kpi-value text-dark mb-2">৳ {{ number_format($totalCollectedCharges, 2) }}</div>
                    </div>
                    <div class="pt-2 border-top d-flex justify-content-between align-items-center kpi-sub">
                        <span>Paid: <strong class="text-success">৳{{ number_format($totalPaidCharges, 2) }}</strong></span>
                        <span>Due: <strong class="text-warning">৳{{ number_format($totalDueCharges, 2) }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Labour / Loading Costs -->
        <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm h-100 mb-0">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="kpi-title text-truncate me-2">Labour / Loading</span>
                            <div class="avatar-kpi bg-success-light text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                                <i class="fe fe-users"></i>
                            </div>
                        </div>
                        <div class="kpi-value text-success mb-2">৳ {{ number_format($totalCollectedLabour, 2) }}</div>
                    </div>
                    <div class="pt-2 border-top d-flex justify-content-between align-items-center kpi-sub">
                        <span>Paid: <strong class="text-success">৳{{ number_format($totalPaidLabour, 2) }}</strong></span>
                        <span>Due: <strong class="text-danger">৳{{ number_format($totalDueLabour, 2) }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Delivery / Transport Charges -->
        <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm h-100 mb-0">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="kpi-title text-truncate me-2">Delivery / Transport</span>
                            <div class="avatar-kpi bg-info-light text-info rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                                <i class="fe fe-truck"></i>
                            </div>
                        </div>
                        <div class="kpi-value text-info mb-2">৳ {{ number_format($totalCollectedDelivery, 2) }}</div>
                    </div>
                    <div class="pt-2 border-top d-flex justify-content-between align-items-center kpi-sub">
                        <span>Paid: <strong class="text-success">৳{{ number_format($totalPaidDelivery, 2) }}</strong></span>
                        <span>Due: <strong class="text-danger">৳{{ number_format($totalDueDelivery, 2) }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Weight Scale Costs -->
        <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm h-100 mb-0">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="kpi-title text-truncate me-2">Weight Scale Cost</span>
                            <div class="avatar-kpi bg-warning-light text-warning rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                                <i class="fe fe-compass"></i>
                            </div>
                        </div>
                        <div class="kpi-value text-warning mb-2">৳ {{ number_format($totalCollectedScale, 2) }}</div>
                    </div>
                    <div class="pt-2 border-top d-flex justify-content-between align-items-center kpi-sub">
                        <span>Paid: <strong class="text-success">৳{{ number_format($totalPaidScale, 2) }}</strong></span>
                        <span>Due: <strong class="text-danger">৳{{ number_format($totalDueScale, 2) }}</strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white p-0 border-bottom">
            <ul class="nav nav-tabs nav-tabs-custom border-0" id="payoutTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="unsettled-tab" data-bs-toggle="tab" data-bs-target="#unsettled" type="button" role="tab">
                        <i class="fe fe-clock me-1"></i> Unsettled Charges &amp; Batch Settle 
                        <span class="badge bg-warning-light text-warning ms-2 rounded-pill">{{ $sales->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                        <i class="fe fe-file-text me-1"></i> Payout Records &amp; Vouchers Ledger
                        @if($payouts->count() > 0)
                            <span class="badge bg-primary-light text-primary ms-2 rounded-pill">{{ $payouts->total() }}</span>
                        @endif
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content" id="payoutTabsContent">

            <!-- TAB 1: Unsettled Charges & Batch Settle -->
            <div class="tab-pane fade show active" id="unsettled" role="tabpanel">

                <!-- Filter Bar -->
                <div class="p-3 bg-light border-bottom">
                    <form method="GET" action="{{ route('worker-payouts.index') }}" class="row g-3 align-items-end">
                        <div class="col-xl-2 col-md-3 col-6">
                            <label class="form-label small fw-semibold text-secondary mb-1">From Date</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
                        </div>
                        <div class="col-xl-2 col-md-3 col-6">
                            <label class="form-label small fw-semibold text-secondary mb-1">To Date</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
                        </div>
                        <div class="col-xl-2 col-md-4 col-6">
                            <label class="form-label small fw-semibold text-secondary mb-1">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="" {{ !request()->filled('status') || request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending (Unpaid / Partial)</option>
                                <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Unpaid Only</option>
                                <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial Only</option>
                                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Fully Settled</option>
                            </select>
                        </div>
                        <div class="col-xl-4 col-md-8 col-12 d-flex gap-2">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Invoice # or customer..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-sm btn-primary px-3 rounded-2">
                                <i class="fe fe-filter"></i>
                            </button>
                            <a href="{{ route('worker-payouts.index') }}" class="btn btn-sm btn-outline-secondary rounded-2">Reset</a>
                        </div>
                    </form>
                </div>

                <!-- Sales Invoices Table -->
                <div class="p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom align-middle mb-0">
                            <thead class="bg-white text-secondary fs-7 text-uppercase border-bottom">
                                <tr>
                                    <th class="ps-4" style="width: 40px;">
                                        <input class="form-check-input shadow-none" type="checkbox" id="selectAllSales" onchange="toggleSelectAll(this)">
                                    </th>
                                    <th>Date</th>
                                    <th>Invoice No</th>
                                    <th>Labour Charge</th>
                                    <th>Delivery Charge</th>
                                    <th>Scale Fee</th>
                                    <th>Total Charge</th>
                                    <th>Status</th>
                                    <th class="pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody class="border-top-0">
                                @forelse ($sales as $sale)
                                    @php
                                        $dueLabour = $sale->due_labour_cost;
                                        $dueDelivery = $sale->due_delivery_charge;
                                        $dueScale = $sale->due_weight_scale_cost;
                                        $dueOther = $sale->due_other_charges;
                                        $totalDue = $sale->total_charges_due;
                                        $isFullyPaid = $totalDue <= 0.001;
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            @if(!$isFullyPaid)
                                                <input class="form-check-input sale-checkbox shadow-none" type="checkbox" 
                                                       value="{{ $sale->id }}" 
                                                       data-due-labour="{{ $dueLabour }}"
                                                       data-due-delivery="{{ $dueDelivery }}"
                                                       data-due-scale="{{ $dueScale }}"
                                                       data-due-total="{{ $totalDue }}"
                                                       onchange="updateBatchSelection()">
                                            @else
                                                <i class="fe fe-check-circle text-success fs-6"></i>
                                            @endif
                                        </td>
                                        <td>{{ $sale->order_date ? \Carbon\Carbon::parse($sale->order_date)->format('d M Y') : ($sale->created_at ? $sale->created_at->format('d M Y') : 'N/A') }}</td>
                                        <td>
                                            <a href="{{ route('sales.invoice', $sale->id) }}" class="fw-bold text-primary">
                                                #{{ $sale->order_no }}
                                            </a>
                                        </td>
                                        <td>
                                            @if((float)$sale->labour_cost > 0)
                                                @if($dueLabour > 0)
                                                    <span class="badge badge-soft-warning">৳ {{ number_format($dueLabour, 2) }}</span>
                                                    <small class="text-muted d-block fs-8">of ৳{{ number_format($sale->labour_cost, 2) }}</small>
                                                @else
                                                    <span class="badge badge-soft-success"><i class="fe fe-check"></i> Paid</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if((float)$sale->delivery_charge > 0)
                                                @if($dueDelivery > 0)
                                                    <span class="badge badge-soft-info">৳ {{ number_format($dueDelivery, 2) }}</span>
                                                    <small class="text-muted d-block fs-8">of ৳{{ number_format($sale->delivery_charge, 2) }}</small>
                                                @else
                                                    <span class="badge badge-soft-success"><i class="fe fe-check"></i> Paid</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if((float)$sale->weight_scale_cost > 0)
                                                @if($dueScale > 0)
                                                    <span class="badge badge-soft-warning">৳ {{ number_format($dueScale, 2) }}</span>
                                                    <small class="text-muted d-block fs-8">of ৳{{ number_format($sale->weight_scale_cost, 2) }}</small>
                                                @else
                                                    <span class="badge badge-soft-success"><i class="fe fe-check"></i> Paid</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($totalDue > 0)
                                                <span class="fw-bold text-dark">৳ {{ number_format($totalDue, 2) }}</span>
                                            @else
                                                <span class="text-success fw-bold">৳ 0.00</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($sale->charges_payout_status === 'paid' || $isFullyPaid)
                                                <span class="badge badge-soft-success px-2 py-1 rounded-pill d-inline-flex align-items-center gap-1">
                                                    <i class="fe fe-check-circle"></i> Fully Settled
                                                </span>
                                            @elseif($sale->charges_payout_status === 'partial')
                                                <span class="badge badge-soft-info px-2 py-1 rounded-pill d-inline-flex align-items-center gap-1">
                                                    <i class="fe fe-pie-chart"></i> Partially Settled
                                                </span>
                                            @else
                                                <span class="badge badge-soft-warning px-2 py-1 rounded-pill d-inline-flex align-items-center gap-1">
                                                    <i class="fe fe-clock"></i> Unsettled / Due
                                                </span>
                                            @endif
                                        </td>
                                        <td class="pe-4 text-end">
                                            @if(!$isFullyPaid)
                                                <button type="button" class="btn btn-sm btn-primary py-1 px-2 rounded-2 shadow-sm" style="font-size: 11px; font-weight: 500;"
                                                        data-bs-toggle="modal" data-bs-target="#singlePayModal{{ $sale->id }}">
                                                    Pay
                                                </button>
                                            @else
                                                <span class="text-muted small"><i class="fe fe-check text-success me-1"></i> Completed</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-5 text-muted">
                                            <i class="fe fe-check-circle fs-2 text-success d-block mb-2"></i>
                                            No pending sales with extra charges matching the filter criteria.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Payout Records Ledger -->
            <div class="tab-pane fade" id="history" role="tabpanel">

                <!-- History Filter Bar -->
                <div class="p-3 bg-light border-bottom">
                    <form method="GET" action="{{ route('worker-payouts.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-3 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Charge Category</label>
                            <select name="history_charge_type" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all" {{ request('history_charge_type', 'all') == 'all' ? 'selected' : '' }}>All Categories</option>
                                <option value="labour" {{ request('history_charge_type') == 'labour' ? 'selected' : '' }}>Labour / Loading</option>
                                <option value="delivery" {{ request('history_charge_type') == 'delivery' ? 'selected' : '' }}>Delivery / Transport</option>
                                <option value="weight_scale" {{ request('history_charge_type') == 'weight_scale' ? 'selected' : '' }}>Weight Scale Cost</option>
                                <option value="mixed" {{ request('history_charge_type') == 'mixed' ? 'selected' : '' }}>Mixed Charges</option>
                            </select>
                        </div>
                        <div class="col-md-5 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Search Vouchers</label>
                            <input type="text" name="history_search" class="form-control form-control-sm" placeholder="Search by Voucher #, Recipient Name, or Phone..." value="{{ request('history_search') }}">
                        </div>
                        <div class="col-md-4 col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary px-3 rounded-2">
                                <i class="fe fe-search me-1"></i> Search History
                            </button>
                            <a href="{{ route('worker-payouts.index') }}" class="btn btn-sm btn-outline-secondary rounded-2">Reset</a>
                        </div>
                    </form>
                </div>

                <!-- Payout History Table -->
                <div class="p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom align-middle mb-0">
                            <thead class="bg-white text-secondary fs-7 text-uppercase border-bottom">
                                <tr>
                                    <th class="ps-4">Voucher No</th>
                                    <th>Payout Date</th>
                                    <th>Recipient / Worker</th>
                                    <th>Charge Type</th>
                                    <th>Invoices Settled</th>
                                    <th>Total Disbursed</th>
                                    <th>Disbursed From</th>
                                    <th>Journal Voucher</th>
                                    <th class="pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody class="border-top-0">
                                @forelse ($payouts as $payout)
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-bold text-primary font-monospace">{{ $payout->payout_no }}</span>
                                        </td>
                                        <td>{{ $payout->payout_date ? $payout->payout_date->format('d M Y') : 'N/A' }}</td>
                                        <td>
                                            <span class="fw-semibold text-dark">{{ $payout->recipient_name }}</span>
                                            @if($payout->recipient_phone)
                                                <small class="text-muted d-block">{{ $payout->recipient_phone }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-primary text-uppercase px-2 py-1 rounded">
                                                {{ ucwords(str_replace('_', ' ', $payout->charge_type)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-secondary px-2 py-1 rounded">
                                                {{ $payout->items->count() }} invoices
                                            </span>
                                        </td>
                                        <td class="fw-bold text-success fs-6">৳ {{ number_format($payout->total_amount, 2) }}</td>
                                        <td>
                                            <span class="small text-muted">
                                                [{{ $payout->paymentAccount->account_code ?? 'N/A' }}] {{ $payout->paymentAccount->account_name ?? 'Cash' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($payout->journalEntry)
                                                <span class="badge badge-soft-success font-monospace">{{ $payout->journalEntry->journal_no }}</span>
                                            @else
                                                <span class="text-muted small">Auto Posted</span>
                                            @endif
                                        </td>
                                        <td class="pe-4 text-end">
                                            <!-- Strict Rule: 3-dot dropdown with fixed popper strategy -->
                                            <div class="dropdown d-inline-block">
                                                <a href="javascript:void(0)" class="btn-action-icon shadow-none" 
                                                   data-bs-toggle="dropdown" 
                                                   data-bs-popper-config='{"strategy":"fixed"}' 
                                                   aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                                    <li>
                                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#viewPayoutModal{{ $payout->id }}">
                                                            <i class="fe fe-eye text-info"></i>
                                                            <span>View Breakdown</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('worker-payouts.voucher-pdf', $payout->id) }}" target="_blank">
                                                            <i class="fe fe-printer text-primary"></i>
                                                            <span>Print PDF Voucher</span>
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider opacity-50"></li>
                                                    <li>
                                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" href="javascript:void(0)" 
                                                           onclick="if(confirm('Are you sure you want to void voucher #{{ $payout->payout_no }}? This will remove the journal entry and restore the unpaid charge balance.')) { document.getElementById('voidPayoutForm{{ $payout->id }}').submit(); }">
                                                            <i class="fe fe-trash-2 text-danger"></i>
                                                            <span>Void / Cancel Payout</span>
                                                        </a>
                                                        <form id="voidPayoutForm{{ $payout->id }}" action="{{ route('worker-payouts.void', $payout->id) }}" method="POST" class="d-none">
                                                            @csrf
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            No payout vouchers recorded yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($payouts->hasPages())
                        <div class="p-3 border-top d-flex justify-content-end">
                            {{ $payouts->links() }}
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

</div>

<!-- FLOATING BATCH ACTION BAR -->
<div id="batchActionBar" class="batch-bar bg-dark text-white p-3 shadow-lg d-none align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
        <span class="badge bg-primary px-3 py-2 rounded-pill fs-7" id="selectedCountBadge">0 Selected</span>
        <span class="text-light small">Total Payable Dues: <strong class="text-white fs-6" id="selectedTotalAmount">৳ 0.00</strong></span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-outline-light btn-sm rounded-pill px-3" onclick="clearBatchSelection()">Cancel</button>
        <button type="button" class="btn btn-success btn-sm rounded-pill px-4 fw-bold shadow-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#batchSettleModal">
            <i class="fe fe-check-circle"></i> Settle &amp; Pay Selected
        </button>
    </div>
</div>

<!-- ==================== MODALS PLACEMENT (Outside Table to prevent DOM clipping) ==================== -->

<!-- 1. BATCH SETTLEMENT MODAL -->
<div class="modal fade" id="batchSettleModal" tabindex="-1" aria-labelledby="batchSettleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-3">
            <form action="{{ route('worker-payouts.batch-settle') }}" method="POST">
                @csrf
                <div id="batchHiddenInputsContainer"></div>

                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold text-dark" id="batchSettleModalLabel">
                        <i class="fe fe-users text-primary me-1"></i> Disburse Batch Worker Settlement
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="bg-light p-3 rounded-3 border mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Invoices Selected:</span>
                            <strong class="text-dark" id="modalSelectedCount">0 invoices</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Available Labour Dues:</span>
                            <span class="text-dark fw-bold" id="modalLabourDue">৳ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Available Delivery Dues:</span>
                            <span class="text-dark fw-bold" id="modalDeliveryDue">৳ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Available Scale Dues:</span>
                            <span class="text-dark fw-bold" id="modalScaleDue">৳ 0.00</span>
                        </div>
                        <hr class="my-2 opacity-50">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark">Total Payout Amount:</span>
                            <h4 class="fw-bold text-primary mb-0" id="modalTotalDisburseAmount">৳ 0.00</h4>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Settlement Category <span class="text-danger">*</span></label>
                        <select name="charge_type" id="batchChargeType" class="form-select" onchange="recalcModalAmounts()" required>
                            <option value="labour">Labour / Loading Cost (Loading Workers / Sardar)</option>
                            <option value="delivery">Delivery / Transport Charge (Truck Drivers / Transport Agency)</option>
                            <option value="weight_scale">Weight Scale Cost (Scale Operator)</option>
                            <option value="all">All Available Extra Charges</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-7 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Recipient / Payee Name <span class="text-danger">*</span></label>
                            <input type="text" name="recipient_name" class="form-control" placeholder="e.g. Labour Sardar Rahim / Driver Karim" required>
                        </div>
                        <div class="col-md-5 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Phone Number</label>
                            <input type="text" name="recipient_phone" class="form-control" placeholder="017xxxxxxxx">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Payout Date <span class="text-danger">*</span></label>
                            <input type="date" name="payout_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select" required>
                                <option value="cash" selected>Cash in Hand</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="mobile_banking">Mobile Banking (bKash/Nagad)</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Disburse From Account <span class="text-danger">*</span></label>
                        <select name="payment_account_id" class="form-select" required>
                            @foreach($paymentAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ $acc->account_code == '1110' ? 'selected' : '' }}>
                                    [{{ $acc->account_code }}] {{ $acc->account_name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted fs-8">Debits Pass-Through Liability (2140) &amp; Credits Selected Account</small>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-secondary mb-1">Narration / Settlement Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Daily settlement for 5 trucks loaded today"></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3 rounded-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-2">Confirm &amp; Disburse Payout</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. SINGLE INVOICE SETTLEMENT MODALS -->
@foreach ($sales as $sale)
    @php
        $dueLabour = $sale->due_labour_cost;
        $dueDelivery = $sale->due_delivery_charge;
        $dueScale = $sale->due_weight_scale_cost;
        $dueOther = $sale->due_other_charges;
        $totalDue = $sale->total_charges_due;
    @endphp

    @if($totalDue > 0.001)
        <div class="modal fade" id="singlePayModal{{ $sale->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-3">
                    <form action="{{ route('worker-payouts.single-settle', $sale->id) }}" method="POST">
                        @csrf
                        <div class="modal-header bg-light border-bottom">
                            <h5 class="modal-title fw-bold text-dark">
                                <i class="fe fe-dollar-sign text-primary me-1"></i> Pay Charges — #{{ $sale->order_no }}
                            </h5>
                            <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body p-4">
                            <!-- Invoice Summary Card -->
                            <div class="bg-light p-3 rounded-3 border mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted small">Customer:</span>
                                    <strong class="text-dark">{{ $sale->customer->name ?? 'Walk-in' }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Invoice Date:</span>
                                    <span class="text-dark">{{ $sale->order_date ? \Carbon\Carbon::parse($sale->order_date)->format('d M Y') : 'N/A' }}</span>
                                </div>
                                <hr class="my-2 opacity-50">

                                <!-- Itemized Check & Amount Fields -->
                                <div class="mb-2">
                                    <label class="form-label small fw-bold text-dark mb-1">Select Charges &amp; Amounts to Pay:</label>
                                    
                                    <!-- Labour -->
                                    <div class="d-flex align-items-center justify-content-between py-1 border-bottom">
                                        <div>
                                            <span class="fw-semibold text-dark">Labour / Loading:</span>
                                            <small class="text-muted d-block fs-8">Due: ৳ {{ number_format($dueLabour, 2) }}</small>
                                        </div>
                                        <div style="width: 140px;">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">৳</span>
                                                <input type="number" step="0.01" max="{{ $dueLabour }}" name="charges[labour]" 
                                                       class="form-control text-end single-charge-input" 
                                                       value="{{ $dueLabour > 0 ? $dueLabour : 0 }}" 
                                                       {{ $dueLabour <= 0 ? 'readonly disabled' : '' }}
                                                       oninput="recalcSingleTotal({{ $sale->id }})">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delivery -->
                                    <div class="d-flex align-items-center justify-content-between py-1 border-bottom">
                                        <div>
                                            <span class="fw-semibold text-dark">Delivery / Transport:</span>
                                            <small class="text-muted d-block fs-8">Due: ৳ {{ number_format($dueDelivery, 2) }}</small>
                                        </div>
                                        <div style="width: 140px;">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">৳</span>
                                                <input type="number" step="0.01" max="{{ $dueDelivery }}" name="charges[delivery]" 
                                                       class="form-control text-end single-charge-input" 
                                                       value="{{ $dueDelivery > 0 ? $dueDelivery : 0 }}" 
                                                       {{ $dueDelivery <= 0 ? 'readonly disabled' : '' }}
                                                       oninput="recalcSingleTotal({{ $sale->id }})">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Weight Scale -->
                                    <div class="d-flex align-items-center justify-content-between py-1 border-bottom">
                                        <div>
                                            <span class="fw-semibold text-dark">Weight Scale Cost:</span>
                                            <small class="text-muted d-block fs-8">Due: ৳ {{ number_format($dueScale, 2) }}</small>
                                        </div>
                                        <div style="width: 140px;">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">৳</span>
                                                <input type="number" step="0.01" max="{{ $dueScale }}" name="charges[weight_scale]" 
                                                       class="form-control text-end single-charge-input" 
                                                       value="{{ $dueScale > 0 ? $dueScale : 0 }}" 
                                                       {{ $dueScale <= 0 ? 'readonly disabled' : '' }}
                                                       oninput="recalcSingleTotal({{ $sale->id }})">
                                            </div>
                                        </div>
                                    </div>

                                    @if((float)$sale->other_charges > 0)
                                    <!-- Other Charges -->
                                    <div class="d-flex align-items-center justify-content-between py-1">
                                        <div>
                                            <span class="fw-semibold text-dark">Other Charges:</span>
                                            <small class="text-muted d-block fs-8">Due: ৳ {{ number_format($dueOther, 2) }}</small>
                                        </div>
                                        <div style="width: 140px;">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">৳</span>
                                                <input type="number" step="0.01" max="{{ $dueOther }}" name="charges[other]" 
                                                       class="form-control text-end single-charge-input" 
                                                       value="{{ $dueOther > 0 ? $dueOther : 0 }}" 
                                                       {{ $dueOther <= 0 ? 'readonly disabled' : '' }}
                                                       oninput="recalcSingleTotal({{ $sale->id }})">
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                                    <span class="fw-bold text-dark">Total Disbursing Now:</span>
                                    <h5 class="fw-bold text-primary mb-0" id="singleTotalAmount{{ $sale->id }}">৳ {{ number_format($totalDue, 2) }}</h5>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-7 col-12">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Recipient Name <span class="text-danger">*</span></label>
                                    <input type="text" name="recipient_name" class="form-control" placeholder="Worker / Driver / Sardar" required>
                                </div>
                                <div class="col-md-5 col-12">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Phone Number</label>
                                    <input type="text" name="recipient_phone" class="form-control" placeholder="017xxxxxxxx">
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6 col-12">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Payout Date <span class="text-danger">*</span></label>
                                    <input type="date" name="payout_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-6 col-12">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Payment Method <span class="text-danger">*</span></label>
                                    <select name="payment_method" class="form-select" required>
                                        <option value="cash" selected>Cash in Hand</option>
                                        <option value="bank">Bank Transfer</option>
                                        <option value="mobile_banking">Mobile Banking</option>
                                        <option value="cheque">Cheque</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary mb-1">Disburse From Account <span class="text-danger">*</span></label>
                                <select name="payment_account_id" class="form-select" required>
                                    @foreach($paymentAccounts as $acc)
                                        <option value="{{ $acc->id }}" {{ $acc->account_code == '1110' ? 'selected' : '' }}>
                                            [{{ $acc->account_code }}] {{ $acc->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-0">
                                <label class="form-label small fw-semibold text-secondary mb-1">Notes / Narration</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Paid loading charge to Hamal gang"></textarea>
                            </div>
                        </div>

                        <div class="modal-footer bg-light border-top gap-2">
                            <button type="button" class="btn btn-outline-secondary px-3 rounded-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4 rounded-2">Confirm &amp; Disburse</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach

<!-- 3. VIEW PAYOUT DETAILS MODALS -->
@foreach ($payouts as $payout)
    <div class="modal fade" id="viewPayoutModal{{ $payout->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow rounded-3">
                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="fe fe-file-text text-primary me-1"></i> Payout Voucher #{{ $payout->payout_no }}
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted" style="width: 45%;">Disbursement Date:</td>
                                    <td class="fw-bold text-dark">{{ $payout->payout_date ? $payout->payout_date->format('d M Y') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Recipient / Worker:</td>
                                    <td class="fw-bold text-dark">{{ $payout->recipient_name }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Contact Phone:</td>
                                    <td class="text-dark">{{ $payout->recipient_phone ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted" style="width: 45%;">Payment Account:</td>
                                    <td class="fw-bold text-dark">[{{ $payout->paymentAccount->account_code ?? 'N/A' }}] {{ $payout->paymentAccount->account_name ?? 'Cash' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Journal Entry:</td>
                                    <td class="font-monospace text-primary fw-bold">{{ $payout->journalEntry->journal_no ?? 'Auto Posted' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Recorded By:</td>
                                    <td class="text-dark">{{ $payout->creator->name ?? 'Admin' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark mb-2">Itemized Invoices Settled:</h6>
                    <div class="table-responsive border rounded-3 mb-3">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="bg-light fs-7">
                                <tr>
                                    <th>#</th>
                                    <th>Invoice No</th>
                                    <th>Customer</th>
                                    <th>Charge Type</th>
                                    <th class="text-end">Amount Settled</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payout->items as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="fw-bold text-primary">#{{ $item->sale->order_no ?? 'N/A' }}</td>
                                        <td>{{ $item->sale->customer->name ?? 'Walk-in' }}</td>
                                        <td><span class="badge badge-soft-primary text-uppercase">{{ ucwords(str_replace('_', ' ', $item->charge_type)) }}</span></td>
                                        <td class="text-end fw-bold">৳ {{ number_format($item->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light fw-bold">
                                <tr>
                                    <td colspan="4" class="text-end">Total Disbursed:</td>
                                    <td class="text-end text-success fs-6">৳ {{ number_format($payout->total_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @if($payout->notes)
                        <div class="alert alert-light border p-2 mb-0">
                            <small class="text-muted fw-bold d-block">Narration / Notes:</small>
                            <span class="text-secondary small">{{ $payout->notes }}</span>
                        </div>
                    @endif
                </div>
                <div class="modal-footer bg-light border-top gap-2">
                    <a href="{{ route('worker-payouts.voucher-pdf', $payout->id) }}" target="_blank" class="btn btn-outline-primary px-3 rounded-2">
                        <i class="fe fe-printer me-1"></i> Print PDF Voucher
                    </a>
                    <button type="button" class="btn btn-secondary px-4 rounded-2" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

@endsection

@push('scripts')
<script>
    let selectedSales = [];

    function toggleSelectAll(masterCheckbox) {
        const checkboxes = document.querySelectorAll('.sale-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = masterCheckbox.checked;
        });
        updateBatchSelection();
    }

    function updateBatchSelection() {
        const checkboxes = document.querySelectorAll('.sale-checkbox:checked');
        selectedSales = [];
        let totalLabour = 0.0;
        let totalDelivery = 0.0;
        let totalScale = 0.0;
        let totalSum = 0.0;

        checkboxes.forEach(cb => {
            const id = cb.value;
            const labour = parseFloat(cb.getAttribute('data-due-labour')) || 0;
            const delivery = parseFloat(cb.getAttribute('data-due-delivery')) || 0;
            const scale = parseFloat(cb.getAttribute('data-due-scale')) || 0;
            const due = parseFloat(cb.getAttribute('data-due-total')) || 0;

            selectedSales.push({ id, labour, delivery, scale, due });
            totalLabour += labour;
            totalDelivery += delivery;
            totalScale += scale;
            totalSum += due;
        });

        const bar = document.getElementById('batchActionBar');
        if (selectedSales.length > 0) {
            bar.classList.remove('d-none');
            bar.classList.add('d-flex');
            document.getElementById('selectedCountBadge').textContent = `${selectedSales.length} Selected`;
            document.getElementById('selectedTotalAmount').textContent = `৳ ${totalSum.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

            // Update modal hidden inputs and labels
            const container = document.getElementById('batchHiddenInputsContainer');
            container.innerHTML = '';
            selectedSales.forEach(s => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'sale_ids[]';
                input.value = s.id;
                container.appendChild(input);
            });

            document.getElementById('modalSelectedCount').textContent = `${selectedSales.length} invoices`;
            document.getElementById('modalLabourDue').textContent = `৳ ${totalLabour.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            document.getElementById('modalDeliveryDue').textContent = `৳ ${totalDelivery.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            document.getElementById('modalScaleDue').textContent = `৳ ${totalScale.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

            recalcModalAmounts();
        } else {
            bar.classList.add('d-none');
            bar.classList.remove('d-flex');
            document.getElementById('selectAllSales').checked = false;
        }
    }

    function clearBatchSelection() {
        document.querySelectorAll('.sale-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('selectAllSales').checked = false;
        updateBatchSelection();
    }

    function recalcModalAmounts() {
        const type = document.getElementById('batchChargeType').value;
        let total = 0.0;

        selectedSales.forEach(s => {
            if (type === 'labour') total += s.labour;
            else if (type === 'delivery') total += s.delivery;
            else if (type === 'weight_scale') total += s.scale;
            else if (type === 'all') total += s.due;
        });

        document.getElementById('modalTotalDisburseAmount').textContent = `৳ ${total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    }

    function recalcSingleTotal(saleId) {
        const modal = document.getElementById(`singlePayModal${saleId}`);
        if (!modal) return;
        const inputs = modal.querySelectorAll('.single-charge-input');
        let total = 0.0;
        inputs.forEach(inp => {
            if (!inp.disabled) {
                total += parseFloat(inp.value) || 0;
            }
        });
        const totalElem = document.getElementById(`singleTotalAmount${saleId}`);
        if (totalElem) {
            totalElem.textContent = `৳ ${total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        }
    }
</script>
@endpush
