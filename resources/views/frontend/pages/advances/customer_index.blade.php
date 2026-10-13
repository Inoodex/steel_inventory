@extends('frontend.layouts.app')

@push('styles')
<style>
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
    .table th, .table td {
        white-space: nowrap;
    }
    .table-responsive {
        overflow: visible !important;
    }
    .dropdown-menu {
        z-index: 1060 !important;
    }
    .badge-soft-success {
        background-color: rgba(25, 135, 84, 0.12) !important;
        color: #198754 !important;
        font-weight: 600;
    }
    .badge-soft-primary {
        background-color: rgba(13, 110, 253, 0.12) !important;
        color: #0d6efd !important;
        font-weight: 600;
    }
    .nav-tabs .nav-link {
        font-weight: 600;
        color: #64748b;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 10px 18px;
    }
    .nav-tabs .nav-link.active {
        color: #7638ff;
        border-bottom: 2px solid #7638ff;
        background: transparent;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header (No Breadcrumbs per AGENTS.md) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Customer Advance Payments</h4>
                <p class="text-muted small mb-0">Track customer prepayments, advance deposit balances, and unallocated credits</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('customer-advances.pdf') }}" target="_blank" class="btn btn-outline-danger px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-download"></i>
                    <span>Statement PDF</span>
                </a>
                <a href="{{ route('customer-advances.create') }}" class="btn btn-primary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="fe fe-plus-circle"></i>
                    <span>Receive Advance Deposit</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-arrow-down-left fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Active Advance Credit Balance</small>
                        <h4 class="fw-bold text-success mb-0">৳{{ number_format($totalActiveAdvanceCredit, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-users fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Customers with Advance Credit</small>
                        <h4 class="fw-bold text-dark mb-0">{{ number_format($customersWithAdvance->count()) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Advance Receipts (Filtered)</small>
                        <h4 class="fw-bold text-info mb-0">৳{{ number_format($totalDepositsAmount, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-3 border-bottom" id="advanceTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="balances-tab" data-bs-toggle="tab" data-bs-target="#balances-pane" type="button" role="tab">
                <i class="fe fe-award me-1"></i>Active Customer Advance Balances ({{ $customersWithAdvance->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane" type="button" role="tab">
                <i class="fe fe-list me-1"></i>Advance Deposits History
            </button>
        </li>
    </ul>

    <div class="tab-content" id="advanceTabContent">
        <!-- Tab 1: Active Advance Balances -->
        <div class="tab-pane fade show active" id="balances-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3 text-secondary small fw-bold">Customer Name</th>
                                    <th class="text-secondary small fw-bold">Company / Phone</th>
                                    <th class="text-end text-secondary small fw-bold">Total Sales (৳)</th>
                                    <th class="text-end text-secondary small fw-bold">Total Paid (৳)</th>
                                    <th class="text-end text-secondary small fw-bold">Advance Credit (৳)</th>
                                    <th class="text-center text-secondary small fw-bold pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customersWithAdvance as $cust)
                                    <tr>
                                        <td class="ps-3">
                                            <a href="{{ route('customers.show', $cust->id) }}" class="fw-bold text-dark text-decoration-none">
                                                {{ $cust->name }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="d-block text-dark small fw-semibold">{{ $cust->company ?: 'Individual' }}</span>
                                            <small class="text-muted"><i class="fe fe-phone me-1"></i>{{ $cust->phone }}</small>
                                        </td>
                                        <td class="text-end fw-semibold text-secondary">
                                            ৳{{ number_format((float)($cust->sales_sum_payble ?? 0), 2) }}
                                        </td>
                                        <td class="text-end fw-semibold text-dark">
                                            ৳{{ number_format((float)($cust->payments_sum_amount ?? 0), 2) }}
                                        </td>
                                        <td class="text-end">
                                            <span class="badge badge-soft-success px-3 py-1.5 rounded-pill fs-7">
                                                ৳{{ number_format($cust->advance_credit, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-center pe-3">
                                            <div class="dropdown">
                                                <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                                    <li>
                                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('customers.show', $cust->id) }}">
                                                            <i class="fe fe-eye text-primary"></i>
                                                            <span>View Customer</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('customers.ledger', $cust->id) }}">
                                                            <i class="fe fe-book-open text-info"></i>
                                                            <span>Account Ledger</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-success fw-semibold" href="{{ route('customer-advances.create', ['customer_id' => $cust->id]) }}">
                                                            <i class="fe fe-plus-circle text-success"></i>
                                                            <span>Receive Advance Deposit</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fe fe-inbox fs-1 mb-2 text-secondary d-block"></i>
                                            <span>No customers currently have an active advance credit balance.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Advance Deposits History -->
        <div class="tab-pane fade" id="history-pane" role="tabpanel">
            <!-- Filter Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('customer-advances.index') }}" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small text-secondary fw-semibold mb-1">Customer</label>
                            <select name="customer_id" class="form-select form-select-sm border-light-subtle">
                                <option value="">All Customers</option>
                                @foreach($customerList as $c)
                                    <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                                        {{ $c->name }} ({{ $c->phone }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary fw-semibold mb-1">From Date</label>
                            <input type="date" name="date_from" class="form-control form-control-sm border-light-subtle" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary fw-semibold mb-1">To Date</label>
                            <input type="date" name="date_to" class="form-control form-control-sm border-light-subtle" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-secondary fw-semibold mb-1">Payment Method</label>
                            <select name="payment_method" class="form-select form-select-sm border-light-subtle">
                                <option value="">All Methods</option>
                                <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="bank" {{ request('payment_method') == 'bank' ? 'selected' : '' }}>Bank</option>
                                <option value="mobile_banking" {{ request('payment_method') == 'mobile_banking' ? 'selected' : '' }}>Mobile Banking</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm px-3 rounded-2 flex-grow-1">
                                <i class="fe fe-filter me-1"></i>Filter
                            </button>
                            <a href="{{ route('customer-advances.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-2">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- History Table Card -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3 text-secondary small fw-bold">Date</th>
                                    <th class="text-secondary small fw-bold">Customer</th>
                                    <th class="text-secondary small fw-bold">Receiving Account</th>
                                    <th class="text-secondary small fw-bold">TrxID / Reference</th>
                                    <th class="text-end text-secondary small fw-bold">Amount (৳)</th>
                                    <th class="text-secondary small fw-bold">Remarks</th>
                                    <th class="text-secondary small fw-bold">Recorded By</th>
                                    <th class="text-center text-secondary small fw-bold pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($advanceDeposits as $deposit)
                                    <tr>
                                        <td class="ps-3 text-dark fw-semibold small">
                                            {{ $deposit->payment_date ? \Carbon\Carbon::parse($deposit->payment_date)->format('d M Y') : $deposit->created_at->format('d M Y') }}
                                        </td>
                                        <td>
                                            @if($deposit->customer)
                                                <a href="{{ route('customers.show', $deposit->customer->id) }}" class="fw-bold text-dark text-decoration-none small">
                                                    {{ $deposit->customer->name }}
                                                </a>
                                                <small class="text-muted d-block">{{ $deposit->customer->phone }}</small>
                                            @else
                                                <span class="text-muted small">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($deposit->payment_method === 'cash')
                                                <span class="badge bg-light text-dark border px-2 py-1 rounded-2">
                                                    <i class="fe fe-dollar-sign text-success me-1"></i>Cash in Hand
                                                </span>
                                            @elseif($deposit->bankDetail)
                                                <span class="badge bg-light text-dark border px-2 py-1 rounded-2">
                                                    <i class="fe fe-credit-card text-primary me-1"></i>{{ $deposit->bankDetail->bank_name }} ({{ $deposit->bankDetail->account_number }})
                                                </span>
                                            @else
                                                <span class="badge bg-light text-dark border px-2 py-1 rounded-2 text-capitalize">
                                                    {{ $deposit->payment_method }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="font-monospace small text-muted">
                                            {{ $deposit->transaction_ref ?: '-' }}
                                        </td>
                                        <td class="text-end fw-bold text-success fs-7">
                                            ৳{{ number_format($deposit->amount, 2) }}
                                        </td>
                                        <td class="text-muted small">
                                            {{ $deposit->remarks ?: 'Customer Advance Deposit' }}
                                        </td>
                                        <td class="text-muted small">
                                            {{ $deposit->creator?->name ?? 'System' }}
                                        </td>
                                        <td class="text-center pe-3">
                                            <a href="{{ route('customer-advances.receipt-pdf', $deposit->id) }}" target="_blank" class="btn btn-sm btn-outline-danger py-1 px-2 rounded-2" title="Download Money Receipt PDF">
                                                <i class="fe fe-download me-1"></i>Receipt PDF
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="fe fe-inbox fs-1 mb-2 text-secondary d-block"></i>
                                            <span>No advance deposit history found.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($advanceDeposits->hasPages())
                        <div class="p-3 border-top d-flex justify-content-end">
                            {{ $advanceDeposits->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
