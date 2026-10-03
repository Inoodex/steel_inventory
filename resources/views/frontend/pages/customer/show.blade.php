@extends('frontend.layouts.app')

@push('styles')
<style>
    .avatar-initial-lg {
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, #7638ff 0%, #9a65ff 100%);
        color: #ffffff;
        font-weight: 700;
        font-size: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        box-shadow: 0 4px 12px rgba(118, 56, 255, 0.25);
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
    .stat-card-mini {
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
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
    .table th, .table td {
        white-space: nowrap;
    }
    .table-responsive {
        overflow: visible !important;
    }
    .dropdown-menu {
        z-index: 1060 !important;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Customer Profile</h4>
                <p class="text-muted small mb-0">Detailed customer information, prepayments, and purchase transaction history</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('customers.ledger.pdf', $customer->id) }}" target="_blank" class="btn btn-outline-danger px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-download"></i>
                    <span>Statement PDF</span>
                </a>
                <a href="{{ route('customers.ledger', $customer->id) }}" class="btn btn-outline-primary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-book-open"></i>
                    <span>Account Ledger</span>
                </a>
                <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-primary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="fe fe-edit"></i>
                    <span>Edit Customer</span>
                </a>
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    @php
        $isActive = in_array($customer->status, ['active', '1', 1]);
        $totalOrders = $sales->count();
        $totalSpent = $sales->sum(fn($s) => (float)($s->payble ?? $s->payable_amount ?? $s->total ?? 0));
        $salesDueAmount = $sales->sum(fn($s) => (float)($s->due_payment ?? $s->due_amount ?? 0));
        $openingDue = ($customer->opening_balance > 0) ? (float)$customer->opening_balance : 0.00;
        $advanceCredit = (float)($customer->advance_credit ?? 0.00);
        $netOutstandingDue = (float)($customer->effective_due ?? 0.00);
    @endphp

    <!-- Customer Summary Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center g-4">
                <div class="col-md-6 col-lg-4">
                    <div>
                        <h4 class="fw-bold text-dark mb-1">{{ $customer->name }}</h4>
                        <div class="d-flex align-items-center gap-2">
                            @if ($isActive)
                                <span class="badge badge-soft-success px-3 py-1 rounded-pill fs-7">
                                    <i class="fe fe-check-circle me-1"></i> Active Customer
                                </span>
                            @else
                                <span class="badge badge-soft-danger px-3 py-1 rounded-pill fs-7">
                                    <i class="fe fe-x-circle me-1"></i> Inactive
                                </span>
                            @endif
                            <span class="text-muted small">ID #CUST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-8 border-start border-light ps-lg-4">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded-3 text-center stat-card-mini">
                                <small class="text-muted text-uppercase fw-semibold fs-7 d-block mb-1">Phone Number</small>
                                <span class="fw-bold text-dark">{{ $customer->phone }}</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded-3 text-center stat-card-mini">
                                <small class="text-muted text-uppercase fw-semibold fs-7 d-block mb-1">Email Address</small>
                                <span class="fw-bold text-dark text-truncate d-block" title="{{ $customer->email }}">{{ $customer->email ?: 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded-3 text-center stat-card-mini">
                                <small class="text-muted text-uppercase fw-semibold fs-7 d-block mb-1">Customer Since</small>
                                <span class="fw-bold text-dark">{{ $customer->created_at?->format('d M Y') ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address Row -->
            <div class="row mt-4 pt-3 border-top border-light">
                <div class="col-12">
                    <span class="fw-semibold text-secondary small d-block mb-1">Billing & Delivery Address:</span>
                    <p class="text-dark mb-0">{{ $customer->address }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales & Financial Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-shopping-bag fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Orders</small>
                        <h5 class="fw-bold text-dark mb-0">{{ number_format($totalOrders) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Invoiced</small>
                        <h5 class="fw-bold text-dark mb-0">৳{{ number_format($totalSpent, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-warning-light text-warning rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-clock fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Opening Due Balance</small>
                        <h5 class="fw-bold text-dark mb-0">৳{{ number_format($openingDue, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-0 h-100 {{ $advanceCredit > 0 ? 'bg-success-subtle border border-success-subtle' : ($netOutstandingDue > 0 ? 'bg-danger-subtle border border-danger-subtle' : '') }}">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md {{ $advanceCredit > 0 ? 'bg-success text-white' : ($netOutstandingDue > 0 ? 'bg-danger text-white' : 'bg-success-light text-success') }} rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe {{ $advanceCredit > 0 ? 'fe-arrow-down-left' : ($netOutstandingDue > 0 ? 'fe-alert-circle' : 'fe-check-circle') }} fs-5"></i>
                    </div>
                    <div>
                        <small class="{{ $advanceCredit > 0 ? 'text-success fw-bold' : ($netOutstandingDue > 0 ? 'text-danger fw-bold' : 'text-muted') }} d-block">
                            {{ $advanceCredit > 0 ? 'Customer Advance Amount' : ($netOutstandingDue > 0 ? 'Net Outstanding Due' : 'Account Balance') }}
                        </small>
                        <h5 class="fw-bold {{ $advanceCredit > 0 ? 'text-success' : ($netOutstandingDue > 0 ? 'text-danger' : 'text-dark') }} mb-0">
                            {{ $advanceCredit > 0 ? '৳' . number_format($advanceCredit, 2) : '৳' . number_format($netOutstandingDue, 2) }}
                        </h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Advance Customer Deposits / Prepayments Card -->
    @if(isset($advancePayments) && $advancePayments->count() > 0)
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="fe fe-arrow-down-left text-success"></i>
                <span>Direct Advance Deposits &amp; Prepayments</span>
            </h5>
            <span class="badge bg-success-subtle text-success border px-3 py-1 rounded-pill">{{ $advancePayments->count() }} Deposits</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4">Receipt #</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Reference / Cheque</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($advancePayments as $adv)
                            @php
                                $methodLabel = ucfirst(str_replace('_', ' ', $adv->payment_method ?? 'cash'));
                            @endphp
                            <tr>
                                <td class="ps-4 fw-bold text-dark">
                                    <span class="badge bg-info-subtle text-info border px-2 py-1 rounded-2">
                                        ADV-{{ $adv->id }}
                                    </span>
                                </td>
                                <td>{{ $adv->payment_date ? date('d M Y', strtotime($adv->payment_date)) : $adv->created_at->format('d M Y') }}</td>
                                <td class="fw-bold text-success">৳{{ number_format($adv->amount, 2) }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill">
                                        {{ $methodLabel }}
                                    </span>
                                </td>
                                <td>{{ $adv->transaction_ref ?: '—' }}</td>
                                <td><span class="small text-muted">{{ $adv->remarks ?: 'Customer Advance Deposit' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Sales Order History Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0">Sales & Order History</h5>
            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">{{ $totalOrders }} Transactions</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4">Order #</th>
                            <th>Date</th>
                            <th>Payable Amount</th>
                            <th>Paid Amount</th>
                            <th>Due Amount</th>
                            <th>Payment Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                            @php
                                $salePayable = (float)($sale->payble ?? $sale->payable_amount ?? $sale->total ?? 0);
                                $salePaid = (float)($sale->advanced_payment ?? 0);
                                $saleDue = (float)($sale->due_payment ?? $sale->due_amount ?? 0);
                            @endphp
                            <tr>
                                <td class="ps-4 fw-bold text-dark">
                                    <a href="{{ route('sales.invoice', $sale->id) }}" class="text-primary text-decoration-none">
                                        #{{ $sale->order_no ?? $sale->id }}
                                    </a>
                                </td>
                                <td>{{ $sale->created_at?->format('d M Y, h:i A') }}</td>
                                <td class="fw-bold text-dark">৳{{ number_format($salePayable, 2) }}</td>
                                <td class="text-success">৳{{ number_format($salePaid, 2) }}</td>
                                <td class="text-danger">৳{{ number_format($saleDue, 2) }}</td>
                                <td>
                                    @if($saleDue <= 0)
                                        <span class="badge badge-soft-success px-3 py-1 rounded-pill">Paid</span>
                                    @elseif($salePaid > 0)
                                        <span class="badge bg-warning-light text-warning px-3 py-1 rounded-pill">Partial</span>
                                    @else
                                        <span class="badge badge-soft-danger px-3 py-1 rounded-pill">Due</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('sales.show', $sale->id) }}">
                                                    <i class="fe fe-eye text-info"></i>
                                                    <span>View Details</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('sales.invoice', $sale->id) }}">
                                                    <i class="fe fe-file-text text-primary"></i>
                                                    <span>View Invoice</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('sales.invoice.pdf', $sale->id) }}" target="_blank">
                                                    <i class="fe fe-download text-danger"></i>
                                                    <span>Invoice PDF</span>
                                                </a>
                                            </li>
                                            @if($saleDue > 0)
                                                <li>
                                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-success fw-semibold" href="{{ route('sales.payments', $sale->id) }}">
                                                        <i class="fe fe-credit-card text-success"></i>
                                                        <span>Collect Payment</span>
                                                    </a>
                                                </li>
                                            @endif
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('sales.edit', $sale->id) }}">
                                                    <i class="fe fe-edit text-warning"></i>
                                                    <span>Edit Sale</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fe fe-shopping-bag fs-1 mb-2 text-secondary d-block"></i>
                                    <span>No sales transactions recorded for this customer yet.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
