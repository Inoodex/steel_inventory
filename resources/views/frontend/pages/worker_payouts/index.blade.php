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
                    <div>
                        <div class="pt-2 border-top d-flex justify-content-between align-items-center kpi-sub">
                            <span>Paid: <strong class="text-success">৳{{ number_format($totalPaidCharges, 2) }}</strong></span>
                            <span>Due: <strong class="text-warning">৳{{ number_format($totalDueCharges, 2) }}</strong></span>
                        </div>
                        @if($totalDueCharges > 0.001)
                            <button type="button" class="btn btn-sm btn-outline-primary w-100 mt-2 rounded-2 fw-semibold d-flex align-items-center justify-content-center gap-1 shadow-none" data-bs-toggle="modal" data-bs-target="#quickPayAllModal">
                                <i class="fe fe-check-circle fs-7"></i> Pay All Charges (৳{{ number_format($totalDueCharges, 2) }})
                            </button>
                        @endif
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
                    <div>
                        <div class="pt-2 border-top d-flex justify-content-between align-items-center kpi-sub">
                            <span>Paid: <strong class="text-success">৳{{ number_format($totalPaidLabour, 2) }}</strong></span>
                            <span>Due: <strong class="text-danger">৳{{ number_format($totalDueLabour, 2) }}</strong></span>
                        </div>
                        @if($totalDueLabour > 0.001)
                            <button type="button" class="btn btn-sm btn-outline-success w-100 mt-2 rounded-2 fw-semibold d-flex align-items-center justify-content-center gap-1 shadow-none" data-bs-toggle="modal" data-bs-target="#quickPayLabourModal">
                                <i class="fe fe-check-circle fs-7"></i> Pay All Labour (৳{{ number_format($totalDueLabour, 2) }})
                            </button>
                        @endif
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
                    <div>
                        <div class="pt-2 border-top d-flex justify-content-between align-items-center kpi-sub">
                            <span>Paid: <strong class="text-success">৳{{ number_format($totalPaidDelivery, 2) }}</strong></span>
                            <span>Due: <strong class="text-danger">৳{{ number_format($totalDueDelivery, 2) }}</strong></span>
                        </div>
                        @if($totalDueDelivery > 0.001)
                            <button type="button" class="btn btn-sm btn-outline-info w-100 mt-2 rounded-2 fw-semibold d-flex align-items-center justify-content-center gap-1 shadow-none" data-bs-toggle="modal" data-bs-target="#quickPayDeliveryModal">
                                <i class="fe fe-check-circle fs-7"></i> Pay All Delivery (৳{{ number_format($totalDueDelivery, 2) }})
                            </button>
                        @endif
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
                    <div>
                        <div class="pt-2 border-top d-flex justify-content-between align-items-center kpi-sub">
                            <span>Paid: <strong class="text-success">৳{{ number_format($totalPaidScale, 2) }}</strong></span>
                            <span>Due: <strong class="text-danger">৳{{ number_format($totalDueScale, 2) }}</strong></span>
                        </div>
                        @if($totalDueScale > 0.001)
                            <button type="button" class="btn btn-sm btn-outline-warning w-100 mt-2 rounded-2 fw-semibold d-flex align-items-center justify-content-center gap-1 shadow-none" data-bs-toggle="modal" data-bs-target="#quickPayScaleModal">
                                <i class="fe fe-check-circle fs-7"></i> Pay All Scale (৳{{ number_format($totalDueScale, 2) }})
                            </button>
                        @endif
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
                        <i class="fe fe-clock me-1"></i> Unsettled Charges 
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
                            <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}">
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
                                    <th class="ps-4">Date</th>
                                    <th>Invoice No</th>
                                    <th>Customer</th>
                                    <th>Labour Charge</th>
                                    <th>Delivery Charge</th>
                                    <th>Scale Fee</th>
                                    <th>Total Charge</th>
                                    <th class="pe-4 text-end">Status</th>
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
                                        <td class="ps-4">{{ $sale->order_date ? \Carbon\Carbon::parse($sale->order_date)->format('d M Y') : ($sale->created_at ? $sale->created_at->format('d M Y') : 'N/A') }}</td>
                                        <td>
                                            <a href="{{ route('sales.invoice', $sale->id) }}" class="fw-bold text-primary">
                                                #{{ $sale->order_no }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="text-dark">{{ $sale->customer->name ?? 'Walk-in' }}</span>
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
                                        <td class="pe-4 text-end">
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
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
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
                            <input type="text" name="history_search" class="form-control form-control-sm" value="{{ request('history_search') }}">
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
                                                            <span>Cancel Payout</span>
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

@php
    $labourDueSales = $sales->filter(fn($s) => $s->due_labour_cost > 0.001);
    $deliveryDueSales = $sales->filter(fn($s) => $s->due_delivery_charge > 0.001);
    $scaleDueSales = $sales->filter(fn($s) => $s->due_weight_scale_cost > 0.001);
    $allDueSales = $sales->filter(fn($s) => $s->total_charges_due > 0.001);
@endphp

<!-- ==================== QUICK-PAY CATEGORY MODALS ==================== -->

<!-- 1. QUICK PAY: LABOUR CHARGES MODAL -->
<div class="modal fade" id="quickPayLabourModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-3">
            <form action="{{ route('worker-payouts.batch-settle') }}" method="POST">
                @csrf
                <input type="hidden" name="settle_all" value="1">
                <input type="hidden" name="charge_type" value="labour">
                @foreach($labourDueSales as $s)
                    <input type="hidden" name="sale_ids[]" value="{{ $s->id }}">
                @endforeach

                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="fe fe-users text-success me-1"></i> Pay All Labour / Loading Charges
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="bg-light p-3 rounded-3 border mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Category:</span>
                            <span class="badge badge-soft-success text-uppercase">Labour / Loading</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Invoices Affected:</span>
                            <strong class="text-dark">{{ $labourDueSales->count() }} invoices</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Total Unpaid Due:</span>
                            <strong class="text-dark">৳ {{ number_format($totalDueLabour, 2) }}</strong>
                        </div>
                        <div class="pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-dark mb-0">
                                    Payment Amount to Pay Now <span class="text-danger">*</span>
                                </label>
                                <button type="button" class="btn btn-link p-0 text-success text-decoration-none small fw-semibold" 
                                        onclick="setFullPayoutAmount(this, '{{ number_format($totalDueLabour, 2, '.', '') }}')">
                                    Pay Full Due
                                </button>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-white fw-bold text-success border-end-0">৳</span>
                                <input type="number" 
                                       step="0.01" 
                                       min="0.01" 
                                       max="{{ number_format($totalDueLabour, 2, '.', '') }}" 
                                       name="payout_amount" 
                                       class="form-control form-control-lg fw-bold text-success border-start-0 payout-amount-input" 
                                       value="{{ number_format($totalDueLabour, 2, '.', '') }}" 
                                       data-max-amount="{{ number_format($totalDueLabour, 2, '.', '') }}"
                                       required 
                                       oninput="handlePayoutAmountInput(this)">
                            </div>
                            <div class="d-flex justify-content-between text-muted small mt-1">
                                <span>Edit amount to make a partial payment</span>
                                <span class="payout-balance-remaining">Remaining Due: ৳ 0.00</span>
                            </div>
                        </div>
                    </div>

                    @if($labourDueSales->count() > 0)
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Invoices Breakdown &amp; Allocation</label>
                            <div class="table-responsive border rounded-3" style="max-height: 150px; overflow-y: auto;">
                                <table class="table table-sm table-hover mb-0 fs-7 align-middle">
                                    <thead class="bg-light text-muted sticky-top">
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Customer</th>
                                            <th class="text-end">Due</th>
                                            <th class="text-end" style="width: 125px;">Pay Now (৳)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($labourDueSales as $s)
                                            <tr>
                                                <td class="fw-bold text-primary">#{{ $s->order_no }}</td>
                                                <td class="text-truncate" style="max-width: 120px;">{{ $s->customer->name ?? 'Walk-in' }}</td>
                                                <td class="text-end fw-semibold text-secondary">৳ {{ number_format($s->due_labour_cost, 2) }}</td>
                                                <td class="text-end">
                                                    <input type="number" 
                                                           step="0.01" 
                                                           min="0" 
                                                           max="{{ number_format($s->due_labour_cost, 2, '.', '') }}" 
                                                           name="sale_amounts[{{ $s->id }}]" 
                                                           class="form-control form-control-sm text-end fw-semibold text-success sale-amount-input" 
                                                           value="{{ number_format($s->due_labour_cost, 2, '.', '') }}" 
                                                           data-max="{{ number_format($s->due_labour_cost, 2, '.', '') }}"
                                                           data-sale-id="{{ $s->id }}"
                                                           oninput="handleSaleAmountRowInput(this)">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            Select Existing Labour / Worker <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <select class="form-select form-select-sm recipient-quick-select" onchange="handleRecipientQuickSelect(this)">
                            <option value="" data-phone="">-- Select Existing Labour (Auto-fills Phone) --</option>
                            @if(isset($savedLabours) && $savedLabours->isNotEmpty())
                                @foreach($savedLabours as $labour)
                                    <option value="{{ $labour->recipient_name }}" data-phone="{{ $labour->recipient_phone ?? '' }}">
                                        {{ $labour->recipient_name }}@if(!empty($labour->recipient_phone)) ({{ $labour->recipient_phone }})@endif
                                    </option>
                                @endforeach
                            @endif
                            <option value="__new__">+ Enter New Labour...</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-7 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Labour Name <span class="text-danger">*</span></label>
                            <input type="text" name="recipient_name" class="form-control recipient-name-input" list="savedLaboursList" required autocomplete="off" oninput="handleRecipientNameInput(this)">
                            <datalist id="savedLaboursList">
                                @if(isset($savedLabours))
                                    @foreach($savedLabours as $labour)
                                        <option value="{{ $labour->recipient_name }}" data-phone="{{ $labour->recipient_phone ?? '' }}">{{ $labour->recipient_phone ?: '' }}</option>
                                    @endforeach
                                @endif
                            </datalist>
                        </div>
                        <div class="col-md-5 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Phone Number</label>
                            <input type="text" name="recipient_phone" class="form-control recipient-phone-input" autocomplete="off">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Payout Date <span class="text-danger">*</span></label>
                            <input type="date" name="payout_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select payout-method-select" required onchange="handlePayoutMethodChange(this)">
                                <option value="cash" selected>Cash in Hand</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="mobile_banking">Mobile Banking (bKash/Nagad)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Auto-filled Bank Account (Shown when Bank Transfer is selected) -->
                    <div class="mb-3 payout-bank-group" style="display: none;">
                        <label class="form-label small fw-semibold text-secondary mb-1">Disbursement Bank Account <span class="text-danger">*</span></label>
                        <select name="bank_detail_id" class="form-select payout-bank-select" onchange="handlePayoutBankChange(this)">
                            @forelse($bankAccounts as $bank)
                                <option value="{{ $bank->id }}" 
                                        data-chart-id="{{ $bank->chartOfAccount?->id ?? '' }}"
                                        {{ $bank->is_default ? 'selected' : '' }}>
                                    {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                </option>
                            @empty
                                @foreach($bankDetails as $bank)
                                    <option value="{{ $bank->id }}" 
                                            data-chart-id="{{ $bank->chartOfAccount?->id ?? '' }}"
                                            {{ $bank->is_default ? 'selected' : '' }}>
                                        {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                    </option>
                                @endforeach
                            @endforelse
                        </select>
                    </div>

                    <!-- Dynamic MFS Provider / Account (Shown when Mobile Banking is selected) -->
                    <div class="mb-3 payout-mfs-group" style="display: none;">
                        <label class="form-label small fw-semibold text-secondary mb-1">Disbursement MFS Account / Wallet <span class="text-danger">*</span></label>
                        <select name="mfs_bank_id" class="form-select payout-mfs-select" onchange="handlePayoutMfsChange(this)">
                            @forelse($mfsAccounts as $mfs)
                                <option value="{{ $mfs->id }}" 
                                        data-provider="{{ $mfs->bank_name }}" 
                                        data-chart-id="{{ $mfs->chartOfAccount?->id ?? '' }}"
                                        {{ $loop->first ? 'selected' : '' }}>
                                    {{ $mfs->bank_name }} - {{ $mfs->account_name }} ({{ $mfs->account_number }})
                                </option>
                            @empty
                                <option value="" disabled selected>No registered MFS accounts found</option>
                            @endforelse
                        </select>
                        <input type="hidden" name="mfs_provider" class="payout-mfs-provider-input" value="{{ $mfsAccounts->first()?->bank_name ?? '' }}">
                        @if(!isset($mfsAccounts) || $mfsAccounts->isEmpty())
                            <div class="mt-1">
                                <a href="{{ route('bank-details.create') }}" target="_blank" class="text-primary fs-8 text-decoration-none">
                                    <i class="fe fe-plus-circle me-1"></i>Add your company MFS account in Bank/MFS Accounts
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- Hidden Payment Account ID (Auto-selected based on Cash/Bank/MFS) -->
                    <input type="hidden" name="payment_account_id" class="payout-account-id-input" value="{{ $cashAccount?->id ?? '' }}">

                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-secondary mb-1">Narration / Settlement Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3 rounded-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 rounded-2">Confirm &amp; Pay Labour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. QUICK PAY: DELIVERY / TRANSPORT CHARGES MODAL -->
<div class="modal fade" id="quickPayDeliveryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-3">
            <form action="{{ route('worker-payouts.batch-settle') }}" method="POST">
                @csrf
                <input type="hidden" name="settle_all" value="1">
                <input type="hidden" name="charge_type" value="delivery">
                @foreach($deliveryDueSales as $s)
                    <input type="hidden" name="sale_ids[]" value="{{ $s->id }}">
                @endforeach

                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="fe fe-truck text-info me-1"></i> Pay All Delivery / Transport Charges
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="bg-light p-3 rounded-3 border mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Category:</span>
                            <span class="badge badge-soft-info text-uppercase">Delivery / Transport</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Invoices Affected:</span>
                            <strong class="text-dark">{{ $deliveryDueSales->count() }} invoices</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Total Unpaid Due:</span>
                            <strong class="text-dark">৳ {{ number_format($totalDueDelivery, 2) }}</strong>
                        </div>
                        <div class="pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-dark mb-0">
                                    Payment Amount to Pay Now <span class="text-danger">*</span>
                                </label>
                                <button type="button" class="btn btn-link p-0 text-info text-decoration-none small fw-semibold" 
                                        onclick="setFullPayoutAmount(this, '{{ number_format($totalDueDelivery, 2, '.', '') }}')">
                                    Pay Full Due
                                </button>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-white fw-bold text-info border-end-0">৳</span>
                                <input type="number" 
                                       step="0.01" 
                                       min="0.01" 
                                       max="{{ number_format($totalDueDelivery, 2, '.', '') }}" 
                                       name="payout_amount" 
                                       class="form-control form-control-lg fw-bold text-info border-start-0 payout-amount-input" 
                                       value="{{ number_format($totalDueDelivery, 2, '.', '') }}" 
                                       data-max-amount="{{ number_format($totalDueDelivery, 2, '.', '') }}"
                                       required 
                                       oninput="handlePayoutAmountInput(this)">
                            </div>
                            <div class="d-flex justify-content-between text-muted small mt-1">
                                <span>Edit amount to make a partial payment</span>
                                <span class="payout-balance-remaining">Remaining Due: ৳ 0.00</span>
                            </div>
                        </div>
                    </div>

                    @if($deliveryDueSales->count() > 0)
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Invoices Breakdown &amp; Allocation</label>
                            <div class="table-responsive border rounded-3" style="max-height: 150px; overflow-y: auto;">
                                <table class="table table-sm table-hover mb-0 fs-7 align-middle">
                                    <thead class="bg-light text-muted sticky-top">
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Customer</th>
                                            <th class="text-end">Due</th>
                                            <th class="text-end" style="width: 125px;">Pay Now (৳)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($deliveryDueSales as $s)
                                            <tr>
                                                <td class="fw-bold text-primary">#{{ $s->order_no }}</td>
                                                <td class="text-truncate" style="max-width: 120px;">{{ $s->customer->name ?? 'Walk-in' }}</td>
                                                <td class="text-end fw-semibold text-secondary">৳ {{ number_format($s->due_delivery_charge, 2) }}</td>
                                                <td class="text-end">
                                                    <input type="number" 
                                                           step="0.01" 
                                                           min="0" 
                                                           max="{{ number_format($s->due_delivery_charge, 2, '.', '') }}" 
                                                           name="sale_amounts[{{ $s->id }}]" 
                                                           class="form-control form-control-sm text-end fw-semibold text-info sale-amount-input" 
                                                           value="{{ number_format($s->due_delivery_charge, 2, '.', '') }}" 
                                                           data-max="{{ number_format($s->due_delivery_charge, 2, '.', '') }}"
                                                           data-sale-id="{{ $s->id }}"
                                                           oninput="handleSaleAmountRowInput(this)">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            Select Existing Driver / Transporter <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <select class="form-select form-select-sm recipient-quick-select" onchange="handleRecipientQuickSelect(this)">
                            <option value="" data-phone="">-- Select Existing Driver (Auto-fills Phone) --</option>
                            @if(isset($savedDrivers) && $savedDrivers->isNotEmpty())
                                @foreach($savedDrivers as $driver)
                                    <option value="{{ $driver->recipient_name }}" data-phone="{{ $driver->recipient_phone ?? '' }}">
                                        {{ $driver->recipient_name }}@if(!empty($driver->recipient_phone)) ({{ $driver->recipient_phone }})@endif
                                    </option>
                                @endforeach
                            @endif
                            <option value="__new__">+ Enter New Driver / Agency...</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-7 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Driver / Agency Name <span class="text-danger">*</span></label>
                            <input type="text" name="recipient_name" class="form-control recipient-name-input" list="savedDriversList" required autocomplete="off" oninput="handleRecipientNameInput(this)">
                            <datalist id="savedDriversList">
                                @if(isset($savedDrivers))
                                    @foreach($savedDrivers as $driver)
                                        <option value="{{ $driver->recipient_name }}" data-phone="{{ $driver->recipient_phone ?? '' }}">{{ $driver->recipient_phone ?: '' }}</option>
                                    @endforeach
                                @endif
                            </datalist>
                        </div>
                        <div class="col-md-5 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Phone Number</label>
                            <input type="text" name="recipient_phone" class="form-control recipient-phone-input" autocomplete="off">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Payout Date <span class="text-danger">*</span></label>
                            <input type="date" name="payout_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select payout-method-select" required onchange="handlePayoutMethodChange(this)">
                                <option value="cash" selected>Cash in Hand</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="mobile_banking">Mobile Banking (bKash/Nagad)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Auto-filled Bank Account (Shown when Bank Transfer is selected) -->
                    <div class="mb-3 payout-bank-group" style="display: none;">
                        <label class="form-label small fw-semibold text-secondary mb-1">Disbursement Bank Account <span class="text-danger">*</span></label>
                        <select name="bank_detail_id" class="form-select payout-bank-select" onchange="handlePayoutBankChange(this)">
                            @forelse($bankAccounts as $bank)
                                <option value="{{ $bank->id }}" 
                                        data-chart-id="{{ $bank->chartOfAccount?->id ?? '' }}"
                                        {{ $bank->is_default ? 'selected' : '' }}>
                                    {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                </option>
                            @empty
                                @foreach($bankDetails as $bank)
                                    <option value="{{ $bank->id }}" 
                                            data-chart-id="{{ $bank->chartOfAccount?->id ?? '' }}"
                                            {{ $bank->is_default ? 'selected' : '' }}>
                                        {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                    </option>
                                @endforeach
                            @endforelse
                        </select>
                    </div>

                    <!-- Dynamic MFS Provider / Account (Shown when Mobile Banking is selected) -->
                    <div class="mb-3 payout-mfs-group" style="display: none;">
                        <label class="form-label small fw-semibold text-secondary mb-1">Disbursement MFS Account / Wallet <span class="text-danger">*</span></label>
                        <select name="mfs_bank_id" class="form-select payout-mfs-select" onchange="handlePayoutMfsChange(this)">
                            @forelse($mfsAccounts as $mfs)
                                <option value="{{ $mfs->id }}" 
                                        data-provider="{{ $mfs->bank_name }}" 
                                        data-chart-id="{{ $mfs->chartOfAccount?->id ?? '' }}"
                                        {{ $loop->first ? 'selected' : '' }}>
                                    {{ $mfs->bank_name }} - {{ $mfs->account_name }} ({{ $mfs->account_number }})
                                </option>
                            @empty
                                <option value="" disabled selected>No registered MFS accounts found</option>
                            @endforelse
                        </select>
                        <input type="hidden" name="mfs_provider" class="payout-mfs-provider-input" value="{{ $mfsAccounts->first()?->bank_name ?? '' }}">
                        @if(!isset($mfsAccounts) || $mfsAccounts->isEmpty())
                            <div class="mt-1">
                                <a href="{{ route('bank-details.create') }}" target="_blank" class="text-primary fs-8 text-decoration-none">
                                    <i class="fe fe-plus-circle me-1"></i>Add your company MFS account in Bank/MFS Accounts
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- Hidden Payment Account ID (Auto-selected based on Cash/Bank/MFS) -->
                    <input type="hidden" name="payment_account_id" class="payout-account-id-input" value="{{ $cashAccount?->id ?? '' }}">

                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-secondary mb-1">Narration / Settlement Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3 rounded-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info px-4 rounded-2 text-white">Confirm &amp; Pay Delivery</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. QUICK PAY: WEIGHT SCALE CHARGES MODAL -->
<div class="modal fade" id="quickPayScaleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-3">
            <form action="{{ route('worker-payouts.batch-settle') }}" method="POST">
                @csrf
                <input type="hidden" name="settle_all" value="1">
                <input type="hidden" name="charge_type" value="weight_scale">
                @foreach($scaleDueSales as $s)
                    <input type="hidden" name="sale_ids[]" value="{{ $s->id }}">
                @endforeach

                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="fe fe-compass text-warning me-1"></i> Pay All Weight Scale Charges
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="bg-light p-3 rounded-3 border mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Category:</span>
                            <span class="badge badge-soft-warning text-uppercase">Weight Scale</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Invoices Affected:</span>
                            <strong class="text-dark">{{ $scaleDueSales->count() }} invoices</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Total Unpaid Due:</span>
                            <strong class="text-dark">৳ {{ number_format($totalDueScale, 2) }}</strong>
                        </div>
                        <div class="pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-dark mb-0">
                                    Payment Amount to Pay Now <span class="text-danger">*</span>
                                </label>
                                <button type="button" class="btn btn-link p-0 text-warning text-decoration-none small fw-semibold" 
                                        onclick="setFullPayoutAmount(this, '{{ number_format($totalDueScale, 2, '.', '') }}')">
                                    Pay Full Due
                                </button>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-white fw-bold text-warning border-end-0">৳</span>
                                <input type="number" 
                                       step="0.01" 
                                       min="0.01" 
                                       max="{{ number_format($totalDueScale, 2, '.', '') }}" 
                                       name="payout_amount" 
                                       class="form-control form-control-lg fw-bold text-warning border-start-0 payout-amount-input" 
                                       value="{{ number_format($totalDueScale, 2, '.', '') }}" 
                                       data-max-amount="{{ number_format($totalDueScale, 2, '.', '') }}"
                                       required 
                                       oninput="handlePayoutAmountInput(this)">
                            </div>
                            <div class="d-flex justify-content-between text-muted small mt-1">
                                <span>Edit amount to make a partial payment</span>
                                <span class="payout-balance-remaining">Remaining Due: ৳ 0.00</span>
                            </div>
                        </div>
                    </div>

                    @if($scaleDueSales->count() > 0)
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Invoices Breakdown &amp; Allocation</label>
                            <div class="table-responsive border rounded-3" style="max-height: 150px; overflow-y: auto;">
                                <table class="table table-sm table-hover mb-0 fs-7 align-middle">
                                    <thead class="bg-light text-muted sticky-top">
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Customer</th>
                                            <th class="text-end">Due</th>
                                            <th class="text-end" style="width: 125px;">Pay Now (৳)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($scaleDueSales as $s)
                                            <tr>
                                                <td class="fw-bold text-primary">#{{ $s->order_no }}</td>
                                                <td class="text-truncate" style="max-width: 120px;">{{ $s->customer->name ?? 'Walk-in' }}</td>
                                                <td class="text-end fw-semibold text-secondary">৳ {{ number_format($s->due_weight_scale_cost, 2) }}</td>
                                                <td class="text-end">
                                                    <input type="number" 
                                                           step="0.01" 
                                                           min="0" 
                                                           max="{{ number_format($s->due_weight_scale_cost, 2, '.', '') }}" 
                                                           name="sale_amounts[{{ $s->id }}]" 
                                                           class="form-control form-control-sm text-end fw-semibold text-warning sale-amount-input" 
                                                           value="{{ number_format($s->due_weight_scale_cost, 2, '.', '') }}" 
                                                           data-max="{{ number_format($s->due_weight_scale_cost, 2, '.', '') }}"
                                                           data-sale-id="{{ $s->id }}"
                                                           oninput="handleSaleAmountRowInput(this)">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            Select Existing Scale Operator / Payee <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <select class="form-select form-select-sm recipient-quick-select" onchange="handleRecipientQuickSelect(this)">
                            <option value="" data-phone="">-- Select Existing Scale Operator (Auto-fills Phone) --</option>
                            @if(isset($savedScalers) && $savedScalers->isNotEmpty())
                                @foreach($savedScalers as $scaler)
                                    <option value="{{ $scaler->recipient_name }}" data-phone="{{ $scaler->recipient_phone ?? '' }}">
                                        {{ $scaler->recipient_name }}@if(!empty($scaler->recipient_phone)) ({{ $scaler->recipient_phone }})@endif
                                    </option>
                                @endforeach
                            @endif
                            <option value="__new__">+ Enter New Scale Operator...</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-7 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Scale Operator / Payee Name <span class="text-danger">*</span></label>
                            <input type="text" name="recipient_name" class="form-control recipient-name-input" list="savedScalersList" required autocomplete="off" oninput="handleRecipientNameInput(this)">
                            <datalist id="savedScalersList">
                                @if(isset($savedScalers))
                                    @foreach($savedScalers as $scaler)
                                        <option value="{{ $scaler->recipient_name }}" data-phone="{{ $scaler->recipient_phone ?? '' }}">{{ $scaler->recipient_phone ?: '' }}</option>
                                    @endforeach
                                @endif
                            </datalist>
                        </div>
                        <div class="col-md-5 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Phone Number</label>
                            <input type="text" name="recipient_phone" class="form-control recipient-phone-input" autocomplete="off">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Payout Date <span class="text-danger">*</span></label>
                            <input type="date" name="payout_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select payout-method-select" required onchange="handlePayoutMethodChange(this)">
                                <option value="cash" selected>Cash in Hand</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="mobile_banking">Mobile Banking (bKash/Nagad)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Auto-filled Bank Account (Shown when Bank Transfer is selected) -->
                    <div class="mb-3 payout-bank-group" style="display: none;">
                        <label class="form-label small fw-semibold text-secondary mb-1">Disbursement Bank Account <span class="text-danger">*</span></label>
                        <select name="bank_detail_id" class="form-select payout-bank-select" onchange="handlePayoutBankChange(this)">
                            @forelse($bankAccounts as $bank)
                                <option value="{{ $bank->id }}" 
                                        data-chart-id="{{ $bank->chartOfAccount?->id ?? '' }}"
                                        {{ $bank->is_default ? 'selected' : '' }}>
                                    {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                </option>
                            @empty
                                @foreach($bankDetails as $bank)
                                    <option value="{{ $bank->id }}" 
                                            data-chart-id="{{ $bank->chartOfAccount?->id ?? '' }}"
                                            {{ $bank->is_default ? 'selected' : '' }}>
                                        {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                    </option>
                                @endforeach
                            @endforelse
                        </select>
                    </div>

                    <!-- Dynamic MFS Provider / Account (Shown when Mobile Banking is selected) -->
                    <div class="mb-3 payout-mfs-group" style="display: none;">
                        <label class="form-label small fw-semibold text-secondary mb-1">Disbursement MFS Account / Wallet <span class="text-danger">*</span></label>
                        <select name="mfs_bank_id" class="form-select payout-mfs-select" onchange="handlePayoutMfsChange(this)">
                            @forelse($mfsAccounts as $mfs)
                                <option value="{{ $mfs->id }}" 
                                        data-provider="{{ $mfs->bank_name }}" 
                                        data-chart-id="{{ $mfs->chartOfAccount?->id ?? '' }}"
                                        {{ $loop->first ? 'selected' : '' }}>
                                    {{ $mfs->bank_name }} - {{ $mfs->account_name }} ({{ $mfs->account_number }})
                                </option>
                            @empty
                                <option value="" disabled selected>No registered MFS accounts found</option>
                            @endforelse
                        </select>
                        <input type="hidden" name="mfs_provider" class="payout-mfs-provider-input" value="{{ $mfsAccounts->first()?->bank_name ?? '' }}">
                        @if(!isset($mfsAccounts) || $mfsAccounts->isEmpty())
                            <div class="mt-1">
                                <a href="{{ route('bank-details.create') }}" target="_blank" class="text-primary fs-8 text-decoration-none">
                                    <i class="fe fe-plus-circle me-1"></i>Add your company MFS account in Bank/MFS Accounts
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- Hidden Payment Account ID (Auto-selected based on Cash/Bank/MFS) -->
                    <input type="hidden" name="payment_account_id" class="payout-account-id-input" value="{{ $cashAccount?->id ?? '' }}">

                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-secondary mb-1">Narration / Settlement Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3 rounded-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4 rounded-2 text-dark">Confirm &amp; Pay Scale</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 4. QUICK PAY: ALL CHARGES (LABOUR, DELIVERY & SCALE) MODAL -->
<div class="modal fade" id="quickPayAllModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-3">
            <form action="{{ route('worker-payouts.batch-settle') }}" method="POST">
                @csrf
                <input type="hidden" name="settle_all" value="1">
                <input type="hidden" name="charge_type" value="all">
                @foreach($allDueSales as $s)
                    <input type="hidden" name="sale_ids[]" value="{{ $s->id }}">
                @endforeach

                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="fe fe-layers text-primary me-1"></i> Pay All Extra Charges Together
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="bg-light p-3 rounded-3 border mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Invoices Affected:</span>
                            <strong class="text-dark">{{ $allDueSales->count() }} invoices</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Labour / Loading Dues:</span>
                            <span class="text-success fw-bold">৳ {{ number_format($totalDueLabour, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Delivery / Transport Dues:</span>
                            <span class="text-info fw-bold">৳ {{ number_format($totalDueDelivery, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Weight Scale Dues:</span>
                            <span class="text-warning fw-bold">৳ {{ number_format($totalDueScale, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 pt-1 border-top">
                            <span class="text-muted small">Total Unpaid Dues:</span>
                            <strong class="text-dark">৳ {{ number_format($totalDueCharges, 2) }}</strong>
                        </div>
                        <div class="pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-dark mb-0">
                                    Payment Amount to Pay Now <span class="text-danger">*</span>
                                </label>
                                <button type="button" class="btn btn-link p-0 text-primary text-decoration-none small fw-semibold" 
                                        onclick="setFullPayoutAmount(this, '{{ number_format($totalDueCharges, 2, '.', '') }}')">
                                    Pay Full Due
                                </button>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-white fw-bold text-primary border-end-0">৳</span>
                                <input type="number" 
                                       step="0.01" 
                                       min="0.01" 
                                       max="{{ number_format($totalDueCharges, 2, '.', '') }}" 
                                       name="payout_amount" 
                                       class="form-control form-control-lg fw-bold text-primary border-start-0 payout-amount-input" 
                                       value="{{ number_format($totalDueCharges, 2, '.', '') }}" 
                                       data-max-amount="{{ number_format($totalDueCharges, 2, '.', '') }}"
                                       required 
                                       oninput="handlePayoutAmountInput(this)">
                            </div>
                            <div class="d-flex justify-content-between text-muted small mt-1">
                                <span>Edit amount to make a partial payment</span>
                                <span class="payout-balance-remaining">Remaining Due: ৳ 0.00</span>
                            </div>
                        </div>
                    </div>

                    @if($allDueSales->count() > 0)
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Invoices Breakdown &amp; Allocation</label>
                            <div class="table-responsive border rounded-3" style="max-height: 150px; overflow-y: auto;">
                                <table class="table table-sm table-hover mb-0 fs-7 align-middle">
                                    <thead class="bg-light text-muted sticky-top">
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Customer</th>
                                            <th class="text-end">Total Due</th>
                                            <th class="text-end" style="width: 125px;">Pay Now (৳)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($allDueSales as $s)
                                            <tr>
                                                <td class="fw-bold text-primary">#{{ $s->order_no }}</td>
                                                <td class="text-truncate" style="max-width: 120px;">{{ $s->customer->name ?? 'Walk-in' }}</td>
                                                <td class="text-end fw-semibold text-secondary">৳ {{ number_format($s->total_charges_due, 2) }}</td>
                                                <td class="text-end">
                                                    <input type="number" 
                                                           step="0.01" 
                                                           min="0" 
                                                           max="{{ number_format($s->total_charges_due, 2, '.', '') }}" 
                                                           name="sale_amounts[{{ $s->id }}]" 
                                                           class="form-control form-control-sm text-end fw-semibold text-primary sale-amount-input" 
                                                           value="{{ number_format($s->total_charges_due, 2, '.', '') }}" 
                                                           data-max="{{ number_format($s->total_charges_due, 2, '.', '') }}"
                                                           data-sale-id="{{ $s->id }}"
                                                           oninput="handleSaleAmountRowInput(this)">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            Select Existing Recipient / Payee <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <select class="form-select form-select-sm recipient-quick-select" onchange="handleRecipientQuickSelect(this)">
                            <option value="" data-phone="">-- Select Existing Recipient (Auto-fills Phone) --</option>
                            @if(isset($allSavedRecipients) && $allSavedRecipients->isNotEmpty())
                                @foreach($allSavedRecipients as $recip)
                                    <option value="{{ $recip->recipient_name }}" data-phone="{{ $recip->recipient_phone ?? '' }}">
                                        {{ $recip->recipient_name }}@if(!empty($recip->recipient_phone)) ({{ $recip->recipient_phone }})@endif
                                    </option>
                                @endforeach
                            @endif
                            <option value="__new__">+ Enter New Recipient...</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-7 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Recipient / Payee Name <span class="text-danger">*</span></label>
                            <input type="text" name="recipient_name" class="form-control recipient-name-input" list="allSavedRecipientsList" required autocomplete="off" oninput="handleRecipientNameInput(this)">
                            <datalist id="allSavedRecipientsList">
                                @if(isset($allSavedRecipients))
                                    @foreach($allSavedRecipients as $recip)
                                        <option value="{{ $recip->recipient_name }}" data-phone="{{ $recip->recipient_phone ?? '' }}">{{ $recip->recipient_phone ?: '' }}</option>
                                    @endforeach
                                @endif
                            </datalist>
                        </div>
                        <div class="col-md-5 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Phone Number</label>
                            <input type="text" name="recipient_phone" class="form-control recipient-phone-input" autocomplete="off">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Payout Date <span class="text-danger">*</span></label>
                            <input type="date" name="payout_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select payout-method-select" required onchange="handlePayoutMethodChange(this)">
                                <option value="cash" selected>Cash in Hand</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="mobile_banking">Mobile Banking (bKash/Nagad)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Auto-filled Bank Account (Shown when Bank Transfer is selected) -->
                    <div class="mb-3 payout-bank-group" style="display: none;">
                        <label class="form-label small fw-semibold text-secondary mb-1">Disbursement Bank Account <span class="text-danger">*</span></label>
                        <select name="bank_detail_id" class="form-select payout-bank-select" onchange="handlePayoutBankChange(this)">
                            @forelse($bankAccounts as $bank)
                                <option value="{{ $bank->id }}" 
                                        data-chart-id="{{ $bank->chartOfAccount?->id ?? '' }}"
                                        {{ $bank->is_default ? 'selected' : '' }}>
                                    {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                </option>
                            @empty
                                @foreach($bankDetails as $bank)
                                    <option value="{{ $bank->id }}" 
                                            data-chart-id="{{ $bank->chartOfAccount?->id ?? '' }}"
                                            {{ $bank->is_default ? 'selected' : '' }}>
                                        {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                    </option>
                                @endforeach
                            @endforelse
                        </select>
                    </div>

                    <!-- Dynamic MFS Provider / Account (Shown when Mobile Banking is selected) -->
                    <div class="mb-3 payout-mfs-group" style="display: none;">
                        <label class="form-label small fw-semibold text-secondary mb-1">Disbursement MFS Account / Wallet <span class="text-danger">*</span></label>
                        <select name="mfs_bank_id" class="form-select payout-mfs-select" onchange="handlePayoutMfsChange(this)">
                            @forelse($mfsAccounts as $mfs)
                                <option value="{{ $mfs->id }}" 
                                        data-provider="{{ $mfs->bank_name }}" 
                                        data-chart-id="{{ $mfs->chartOfAccount?->id ?? '' }}"
                                        {{ $loop->first ? 'selected' : '' }}>
                                    {{ $mfs->bank_name }} - {{ $mfs->account_name }} ({{ $mfs->account_number }})
                                </option>
                            @empty
                                <option value="" disabled selected>No registered MFS accounts found</option>
                            @endforelse
                        </select>
                        <input type="hidden" name="mfs_provider" class="payout-mfs-provider-input" value="{{ $mfsAccounts->first()?->bank_name ?? '' }}">
                        @if(!isset($mfsAccounts) || $mfsAccounts->isEmpty())
                            <div class="mt-1">
                                <a href="{{ route('bank-details.create') }}" target="_blank" class="text-primary fs-8 text-decoration-none">
                                    <i class="fe fe-plus-circle me-1"></i>Add your company MFS account in Bank/MFS Accounts
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- Hidden Payment Account ID (Auto-selected based on Cash/Bank/MFS) -->
                    <input type="hidden" name="payment_account_id" class="payout-account-id-input" value="{{ $cashAccount?->id ?? '' }}">

                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-secondary mb-1">Narration / Settlement Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3 rounded-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-2">Confirm &amp; Pay All Charges</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>


    function handlePayoutMethodChange(selectElem) {
        const modal = selectElem.closest('.modal');
        if (!modal) return;

        const method = selectElem.value;
        const bankGroup = modal.querySelector('.payout-bank-group');
        const mfsGroup = modal.querySelector('.payout-mfs-group');
        const bankSelect = modal.querySelector('.payout-bank-select');
        const mfsSelect = modal.querySelector('.payout-mfs-select');
        const accountInput = modal.querySelector('.payout-account-id-input');
        const defaultCashId = "{{ $cashAccount?->id ?? '' }}";
        const defaultBankMfsId = "{{ $defaultBankMfsAccount?->id ?? '' }}";

        if (method === 'cash') {
            if (bankGroup) bankGroup.style.display = 'none';
            if (mfsGroup) mfsGroup.style.display = 'none';
            if (bankSelect) bankSelect.disabled = true;
            if (mfsSelect) mfsSelect.disabled = true;
            if (accountInput) accountInput.value = defaultCashId;
        } else if (method === 'bank') {
            if (bankGroup) bankGroup.style.display = 'block';
            if (mfsGroup) mfsGroup.style.display = 'none';
            if (mfsSelect) mfsSelect.disabled = true;
            if (bankSelect) {
                bankSelect.disabled = false;
                if (!bankSelect.value && bankSelect.options.length > 0) {
                    bankSelect.selectedIndex = 0;
                }
                handlePayoutBankChange(bankSelect);
            } else if (accountInput) {
                accountInput.value = defaultBankMfsId;
            }
        } else if (method === 'mobile_banking') {
            if (bankGroup) bankGroup.style.display = 'none';
            if (mfsGroup) mfsGroup.style.display = 'block';
            if (bankSelect) bankSelect.disabled = true;
            if (mfsSelect) {
                mfsSelect.disabled = false;
                if (!mfsSelect.value && mfsSelect.options.length > 0) {
                    mfsSelect.selectedIndex = 0;
                }
                handlePayoutMfsChange(mfsSelect);
            } else if (accountInput) {
                accountInput.value = defaultBankMfsId;
            }
        }
    }

    function handlePayoutBankChange(bankSelect) {
        const modal = bankSelect.closest('.modal');
        if (!modal) return;

        const selectedOpt = bankSelect.options[bankSelect.selectedIndex];
        const chartId = selectedOpt ? selectedOpt.getAttribute('data-chart-id') : null;
        const accountInput = modal.querySelector('.payout-account-id-input');
        const defaultBankMfsId = "{{ $defaultBankMfsAccount?->id ?? '' }}";

        if (accountInput) {
            accountInput.value = chartId || defaultBankMfsId;
        }
    }

    function handlePayoutMfsChange(mfsSelect) {
        const modal = mfsSelect.closest('.modal');
        if (!modal) return;

        const selectedOpt = mfsSelect.options[mfsSelect.selectedIndex];
        const chartId = selectedOpt ? selectedOpt.getAttribute('data-chart-id') : null;
        const provider = selectedOpt ? selectedOpt.getAttribute('data-provider') : mfsSelect.value;
        const accountInput = modal.querySelector('.payout-account-id-input');
        const providerInput = modal.querySelector('.payout-mfs-provider-input');
        const defaultBankMfsId = "{{ $defaultBankMfsAccount?->id ?? '' }}";

        if (accountInput) {
            accountInput.value = chartId || defaultBankMfsId;
        }
        if (providerInput && provider) {
            providerInput.value = provider;
        }
    }

    function handleRecipientQuickSelect(selectEl) {
        const modal = selectEl.closest('.modal');
        if (!modal) return;

        const nameInput = modal.querySelector('.recipient-name-input');
        const phoneInput = modal.querySelector('.recipient-phone-input');
        if (!nameInput) return;

        const selectedVal = selectEl.value;
        if (selectedVal === '__new__') {
            nameInput.value = '';
            if (phoneInput) phoneInput.value = '';
            nameInput.focus();
            return;
        }

        if (!selectedVal) {
            return;
        }

        const selectedOption = selectEl.options[selectEl.selectedIndex];
        const phone = selectedOption ? selectedOption.getAttribute('data-phone') : '';

        nameInput.value = selectedVal;
        if (phoneInput) {
            phoneInput.value = phone || '';
        }
    }

    function handleRecipientNameInput(nameInput) {
        const modal = nameInput.closest('.modal');
        if (!modal) return;

        const val = nameInput.value.trim().toLowerCase();
        const phoneInput = modal.querySelector('.recipient-phone-input');
        const quickSelect = modal.querySelector('.recipient-quick-select');

        if (!val) {
            if (quickSelect) quickSelect.value = '';
            return;
        }

        let matched = false;
        if (quickSelect) {
            for (let i = 0; i < quickSelect.options.length; i++) {
                const opt = quickSelect.options[i];
                if (opt.value && opt.value.toLowerCase() === val) {
                    quickSelect.selectedIndex = i;
                    if (phoneInput && opt.getAttribute('data-phone')) {
                        phoneInput.value = opt.getAttribute('data-phone');
                    }
                    matched = true;
                    break;
                }
            }
            if (!matched && quickSelect.querySelector('option[value="__new__"]')) {
                quickSelect.value = '__new__';
            }
        }

        if (!matched) {
            const dlOptions = modal.querySelectorAll('datalist option');
            for (let i = 0; i < dlOptions.length; i++) {
                const dopt = dlOptions[i];
                if (dopt.value && dopt.value.toLowerCase() === val) {
                    if (phoneInput && dopt.getAttribute('data-phone')) {
                        phoneInput.value = dopt.getAttribute('data-phone');
                    }
                    break;
                }
            }
        }
    }

    function handlePayoutAmountInput(mainInput) {
        const modal = mainInput.closest('.modal');
        if (!modal) return;

        let enteredAmount = parseFloat(mainInput.value) || 0;
        const maxTotal = parseFloat(mainInput.dataset.maxAmount) || 0;

        if (enteredAmount > maxTotal) {
            enteredAmount = maxTotal;
            mainInput.value = maxTotal.toFixed(2);
        }

        const remainingEl = modal.querySelector('.payout-balance-remaining');
        if (remainingEl) {
            const diff = Math.max(0, maxTotal - enteredAmount);
            remainingEl.innerText = 'Remaining Due: ৳ ' + diff.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Distribute entered amount down the invoice rows (FIFO)
        let remainingToAllocate = enteredAmount;
        const rows = modal.querySelectorAll('.sale-amount-input');
        rows.forEach(function(row) {
            const rowMax = parseFloat(row.dataset.max) || 0;
            const allocated = Math.min(remainingToAllocate, rowMax);
            row.value = allocated.toFixed(2);
            remainingToAllocate -= allocated;
        });
    }

    function handleSaleAmountRowInput(rowInput) {
        const modal = rowInput.closest('.modal');
        if (!modal) return;

        let val = parseFloat(rowInput.value) || 0;
        const rowMax = parseFloat(rowInput.dataset.max) || 0;

        if (val > rowMax) {
            val = rowMax;
            rowInput.value = rowMax.toFixed(2);
        }

        let total = 0;
        const rows = modal.querySelectorAll('.sale-amount-input');
        rows.forEach(function(r) {
            total += (parseFloat(r.value) || 0);
        });

        const mainInput = modal.querySelector('.payout-amount-input');
        if (mainInput) {
            mainInput.value = total.toFixed(2);
            const maxTotal = parseFloat(mainInput.dataset.maxAmount) || 0;
            const remainingEl = modal.querySelector('.payout-balance-remaining');
            if (remainingEl) {
                const diff = Math.max(0, maxTotal - total);
                remainingEl.innerText = 'Remaining Due: ৳ ' + diff.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }
    }

    function setFullPayoutAmount(btn, fullAmount) {
        const modal = btn.closest('.modal');
        if (!modal) return;

        const mainInput = modal.querySelector('.payout-amount-input');
        if (mainInput) {
            mainInput.value = parseFloat(fullAmount).toFixed(2);
            handlePayoutAmountInput(mainInput);
        }
    }

    // Auto initialize payment state whenever any settlement modal is shown
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.modal').forEach(function(modal) {
            modal.addEventListener('shown.bs.modal', function() {
                const methodSelect = modal.querySelector('.payout-method-select');
                if (methodSelect) {
                    handlePayoutMethodChange(methodSelect);
                }
            });
        });
    });
</script>
@endpush
