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

    @php
        $isActive = in_array($customer->status, ['active', '1', 1]);
        $totalOrders = $sales->count();
        $totalSpent = $sales->sum(fn($s) => (float)($s->payble ?? $s->payable_amount ?? $s->total ?? 0));
        $salesDueAmount = $sales->sum(fn($s) => (float)($s->due_payment ?? $s->due_amount ?? 0));
        $openingDue = ($customer->opening_balance > 0) ? (float)$customer->opening_balance : 0.00;
        $advanceCredit = (float)($customer->advance_credit ?? 0.00);
        $netOutstandingDue = (float)($customer->effective_due ?? 0.00);
    @endphp

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Customer Profile</h4>
                <p class="text-muted small mb-0">Detailed customer information, prepayments, and purchase transaction history</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($advanceCredit > 0)
                    <button type="button" class="btn btn-danger px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 shadow-sm text-white" data-bs-toggle="modal" data-bs-target="#refundCustomerAdvanceModal">
                        <i class="fe fe-corner-up-left"></i>
                        <span>Refund Advance (৳{{ number_format($advanceCredit, 2) }})</span>
                    </button>
                @endif
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

    @php
        $groupedPayments = ($payments ?? collect())->groupBy(function($item) {
            return $item->sale_id ? 'sale_' . $item->sale_id : 'unallocated';
        });
    @endphp

    <!-- Customer Payments & Due Collections History Card -->
    <div class="card border-0 shadow-sm rounded-3 mt-4">
        <div class="card-header bg-white py-3 border-bottom border-light d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar avatar-sm bg-success-light text-success rounded-circle d-flex align-items-center justify-content-center">
                    <i class="fe fe-dollar-sign fs-5"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0">Payment Collections &amp; Due Settlements History</h5>
                    <p class="text-muted small mb-0">Categorized invoice receipts, installment history, and settlement ledger</p>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill fs-7">
                    {{ count($payments ?? []) }} Total Payments
                </span>
                
                <!-- View Toggle Pills -->
                <ul class="nav nav-pills bg-light p-1 rounded-pill border" id="paymentsViewTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active px-3 py-1 rounded-pill fs-7 fw-semibold" id="categorized-tab" data-bs-toggle="tab" data-bs-target="#categorized-view" type="button" role="tab">
                            <i class="fe fe-grid me-1"></i> Categorized by Invoice
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link px-3 py-1 rounded-pill fs-7 fw-semibold" id="flat-tab" data-bs-toggle="tab" data-bs-target="#flat-view" type="button" role="tab">
                            <i class="fe fe-list me-1"></i> All Payments (Flat)
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            <div class="tab-content" id="paymentsViewTabContent">
                
                <!-- TAB 1: CATEGORIZED (INVOICE BASED) VIEW -->
                <div class="tab-pane fade show active" id="categorized-view" role="tabpanel">
                    @if($groupedPayments->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fe fe-inbox fs-1 mb-2 text-secondary d-block"></i>
                            <span>No payment collections recorded for this customer yet.</span>
                        </div>
                    @else
                        <!-- Quick Filter & Action Bar -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
                            <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                <span class="small text-muted fw-bold text-uppercase me-1" style="font-size: 11px;">Filter Category:</span>
                                <button type="button" class="btn btn-sm btn-primary px-2.5 py-1 rounded-pill fw-semibold cat-filter-btn active" style="font-size: 11px;" onclick="filterCustomerInvoiceCategory('all', this)">
                                    All Categories ({{ $groupedPayments->count() }})
                                </button>
                                @foreach($groupedPayments as $grpKey => $grpPayments)
                                    @php
                                        if ($grpKey === 'unallocated') {
                                            $grpLabel = 'General / Advance (' . $grpPayments->count() . ')';
                                            $grpId = 'unallocated';
                                        } else {
                                            $sId = str_replace('sale_', '', $grpKey);
                                            $sObj = $grpPayments->first()->sale ?? $sales->firstWhere('id', $sId);
                                            $grpLabel = ($sObj ? ('#' . ($sObj->order_no ?? ('SO-' . $sObj->id))) : ('#SO-' . $sId)) . ' (' . $grpPayments->count() . ')';
                                            $grpId = $sId;
                                        }
                                    @endphp
                                    <button type="button" class="btn btn-sm btn-outline-secondary px-2.5 py-1 rounded-pill fw-semibold cat-filter-btn" style="font-size: 11px;" onclick="filterCustomerInvoiceCategory('{{ $grpId }}', this)">
                                        {{ $grpLabel }}
                                    </button>
                                @endforeach
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary px-2.5 py-1 rounded-2" style="font-size: 11px;" onclick="toggleAllInvoiceCards(true)">
                                    <i class="fe fe-maximize-2 me-1"></i>Expand All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary px-2.5 py-1 rounded-2" style="font-size: 11px;" onclick="toggleAllInvoiceCards(false)">
                                    <i class="fe fe-minimize-2 me-1"></i>Collapse All
                                </button>
                            </div>
                        </div>

                        <!-- Invoice Groups -->
                        @foreach($groupedPayments as $grpKey => $grpPayments)
                            @php
                                $isUnallocated = ($grpKey === 'unallocated');
                                $saleId = $isUnallocated ? null : (int)str_replace('sale_', '', $grpKey);
                                $sale = $isUnallocated ? null : ($grpPayments->first()->sale ?? $sales->firstWhere('id', $saleId));
                                $grpSum = $grpPayments->sum('amount');
                                $grpCount = $grpPayments->count();

                                $salePayable = $sale ? (float)($sale->payble ?? $sale->payable_amount ?? $sale->total ?? 0) : 0;
                                $salePaid = $sale ? (float)($sale->advanced_payment ?? 0) : 0;
                                $saleDue = $sale ? (float)($sale->due_payment ?? $sale->due_amount ?? 0) : 0;
                            @endphp

                            <div class="card border rounded-3 mb-3 shadow-none invoice-category-card overflow-hidden" data-invoice-id="{{ $isUnallocated ? 'unallocated' : $saleId }}">
                                <!-- Category Card Header -->
                                <div class="card-header bg-light py-2.5 px-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2" 
                                     style="cursor: pointer;" onclick="toggleInvoiceCardBody('body_{{ $grpKey }}', this)">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <i class="fe fe-chevron-down toggle-icon text-muted fs-6" style="transition: transform 0.2s ease;"></i>
                                        @if($isUnallocated)
                                            <span class="badge bg-secondary text-white px-2.5 py-1.5 rounded-2 fs-7 fw-bold">
                                                <i class="fe fe-folder me-1"></i> Opening Due &amp; Advance / General Collections
                                            </span>
                                            <span class="badge bg-light text-secondary border px-2 py-1 rounded-pill" style="font-size: 11px;">Unallocated</span>
                                        @else
                                            <a href="{{ route('sales.invoice', $sale->id) }}" class="badge bg-primary text-white px-2.5 py-1.5 rounded-2 fs-7 fw-bold text-decoration-none" onclick="event.stopPropagation();" target="_blank">
                                                <i class="fe fe-file-text me-1"></i> #{{ $sale->order_no ?? ('SO-' . $sale->id) }}
                                            </a>
                                            <span class="text-muted small">
                                                <i class="fe fe-calendar me-1"></i>{{ $sale->created_at?->format('d M Y') }}
                                            </span>
                                            @if($saleDue <= 0)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill" style="font-size: 11px;">
                                                    <i class="fe fe-check-circle me-1"></i>Paid in Full
                                                </span>
                                            @elseif($salePaid > 0)
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1 rounded-pill" style="font-size: 11px;">
                                                    <i class="fe fe-clock me-1"></i>Partially Paid
                                                </span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 rounded-pill" style="font-size: 11px;">
                                                    <i class="fe fe-alert-circle me-1"></i>Unpaid
                                                </span>
                                            @endif
                                        @endif
                                    </div>

                                    <div class="d-flex align-items-center gap-2" onclick="event.stopPropagation();">
                                        <div class="d-flex align-items-center gap-1.5 text-end flex-wrap">
                                            @if(!$isUnallocated && $sale)
                                                <div class="px-2 py-1 bg-white rounded border border-light-subtle small">
                                                    <span class="text-muted">Total:</span>
                                                    <span class="fw-bold text-dark">৳{{ number_format($salePayable, 2) }}</span>
                                                </div>
                                            @endif
                                            <div class="px-2 py-1 bg-white rounded border border-light-subtle small">
                                                <span class="text-muted">Collected:</span>
                                                <span class="fw-bold text-success">৳{{ number_format($grpSum, 2) }}</span>
                                                <span class="badge bg-success-subtle text-success ms-1" style="font-size: 10px;">{{ $grpCount }} {{ Str::plural('receipt', $grpCount) }}</span>
                                            </div>
                                            @if(!$isUnallocated && $saleDue > 0)
                                                <div class="px-2 py-1 bg-white rounded border border-light-subtle small">
                                                    <span class="text-muted">Due:</span>
                                                    <span class="fw-bold text-danger">৳{{ number_format($saleDue, 2) }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        @if(!$isUnallocated && $sale)
                                            <div class="d-flex align-items-center gap-1 ms-1">
                                                <a href="{{ route('sales.invoice', $sale->id) }}" class="btn btn-sm btn-outline-primary px-2 py-1 rounded-2" target="_blank" title="View Full Invoice">
                                                    <i class="fe fe-file-text"></i>
                                                </a>
                                                @if($saleDue > 0)
                                                    <a href="{{ route('sales.payments', $sale->id) }}" class="btn btn-sm btn-success px-2.5 py-1 rounded-2 text-white fw-semibold" title="Collect Due Payment">
                                                        <i class="fe fe-credit-card me-1"></i>Pay
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Category Card Body / Sub-table -->
                                <div class="card-body p-0 invoice-card-body" id="body_{{ $grpKey }}">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-white text-secondary border-bottom" style="font-size: 11px; text-transform: uppercase;">
                                                <tr>
                                                    <th class="ps-3" style="width: 5%;">#</th>
                                                    <th style="width: 15%;">Payment Date</th>
                                                    <th style="width: 25%;">Method &amp; Channel</th>
                                                    <th style="width: 15%;">Transaction Ref</th>
                                                    <th style="width: 20%;">Remarks / Purpose</th>
                                                    <th style="width: 15%;">Amount (৳)</th>
                                                    <th class="text-center" style="width: 5%;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($grpPayments as $pay)
                                                    @php
                                                        $methodName = match($pay->payment_method) {
                                                            'bank', '2' => 'Bank Transfer',
                                                            'mobile_banking', '3' => 'Mobile Banking',
                                                            'cheque' => 'Cheque',
                                                            default => 'Cash in Hand'
                                                        };
                                                    @endphp
                                                    <tr>
                                                        <td class="ps-3 text-muted">{{ $loop->iteration }}</td>
                                                        <td class="fw-medium text-dark">
                                                            <i class="fe fe-calendar me-1 text-muted"></i>
                                                            {{ $pay->payment_date ? \Carbon\Carbon::parse($pay->payment_date)->format('d M Y') : ($pay->created_at ? $pay->created_at->format('d M Y, h:i A') : 'N/A') }}
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-light text-dark border me-1">
                                                                {{ $methodName }}
                                                            </span>
                                                            @if($pay->bankDetail)
                                                                <small class="text-muted d-block mt-0.5">
                                                                    <i class="fe fe-layers me-1 text-primary"></i>{{ $pay->bankDetail->bank_name }} ({{ $pay->bankDetail->account_number }})
                                                                </small>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($pay->transaction_ref)
                                                                <span class="badge bg-secondary-subtle text-secondary border font-monospace">{{ $pay->transaction_ref }}</span>
                                                            @else
                                                                <span class="text-muted small">—</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="small text-dark">{{ $pay->remarks ?: ($isUnallocated ? 'Customer Due Settlement' : ('Payment for #' . ($sale->order_no ?? 'Invoice'))) }}</span>
                                                        </td>
                                                        <td>
                                                            @if($pay->amount < 0)
                                                                <span class="text-danger fw-bold fs-6">- ৳ {{ number_format(abs($pay->amount), 2) }}</span>
                                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1 font-monospace" style="font-size: 10px;">REFUND</span>
                                                            @else
                                                                <span class="text-success fw-bold fs-6">৳ {{ number_format($pay->amount, 2) }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="dropdown">
                                                                <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                                                    <i class="fas fa-ellipsis-v"></i>
                                                                </a>
                                                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                                                    <li>
                                                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-primary" href="javascript:void(0)"
                                                                            onclick="openEditCustomerPaymentModal({{ json_encode([
                                                                                'id' => $pay->id,
                                                                                'amount' => (float)$pay->amount,
                                                                                'payment_method' => $pay->payment_method,
                                                                                'bank_detail_id' => $pay->bank_detail_id,
                                                                                'transaction_ref' => $pay->transaction_ref,
                                                                                'payment_date' => $pay->payment_date ? \Carbon\Carbon::parse($pay->payment_date)->format('Y-m-d') : ($pay->created_at ? $pay->created_at->format('Y-m-d') : date('Y-m-d')),
                                                                                'remarks' => $pay->remarks ?? '',
                                                                                'sale_id' => $pay->sale_id,
                                                                                'order_no' => $pay->sale ? ($pay->sale->order_no ?? ('SO-' . $pay->sale->id)) : null,
                                                                            ]) }})">
                                                                            <i class="fe fe-edit text-primary"></i>
                                                                            <span>Edit Payment</span>
                                                                        </a>
                                                                    </li>
                                                                    @if(empty($pay->sale_id))
                                                                        <li>
                                                                            <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-info" href="{{ route('customer-advances.receipt-pdf', $pay->id) }}" target="_blank">
                                                                                <i class="fe fe-download text-info"></i>
                                                                                <span>Receipt PDF</span>
                                                                            </a>
                                                                        </li>
                                                                    @endif
                                                                    <li>
                                                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" href="javascript:void(0)"
                                                                            onclick="if (confirm('Are you sure you want to revert this payment of ৳{{ number_format($pay->amount, 2) }}? This will restore the customer due balance.')) { document.getElementById('delCustPayment{{ $pay->id }}').submit(); }">
                                                                            <i class="fe fe-trash-2 text-danger"></i>
                                                                            <span>Delete / Revert</span>
                                                                        </a>
                                                                        <form id="delCustPayment{{ $pay->id }}" action="{{ route('delete.payment', $pay->id) }}" method="POST" class="d-none">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                        </form>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <!-- TAB 2: ALL PAYMENTS (FLAT VIEW) -->
                <div class="tab-pane fade" id="flat-view" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary fs-7 text-uppercase">
                                <tr>
                                    <th class="ps-4" style="width: 5%;">#</th>
                                    <th style="width: 15%;">Date</th>
                                    <th style="width: 20%;">Invoice / Type</th>
                                    <th style="width: 25%;">Method &amp; Channel</th>
                                    <th style="width: 15%;">Transaction Ref</th>
                                    <th style="width: 15%;">Amount (৳)</th>
                                    <th class="text-center" style="width: 5%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments ?? [] as $pay)
                                    @php
                                        $methodName = match($pay->payment_method) {
                                            'bank', '2' => 'Bank Transfer',
                                            'mobile_banking', '3' => 'Mobile Banking',
                                            'cheque' => 'Cheque',
                                            default => 'Cash in Hand'
                                        };
                                    @endphp
                                    <tr>
                                        <td class="ps-4 text-muted">{{ $loop->iteration }}</td>
                                        <td class="fw-medium text-dark">
                                            {{ $pay->payment_date ? \Carbon\Carbon::parse($pay->payment_date)->format('d M Y') : ($pay->created_at ? $pay->created_at->format('d M Y, h:i A') : 'N/A') }}
                                        </td>
                                        <td>
                                            @if($pay->sale)
                                                <a href="{{ route('sales.invoice', $pay->sale_id) }}" class="badge bg-primary-light text-primary text-decoration-none" target="_blank">
                                                    #{{ $pay->sale->order_no ?? ('SO-' . $pay->sale_id) }}
                                                </a>
                                            @elseif(empty($pay->sale_id))
                                                <span class="badge bg-success-subtle text-success border">General Due / Advance</span>
                                            @endif
                                            @if($pay->remarks)
                                                <small class="text-muted d-block mt-0.5">{{ Str::limit($pay->remarks, 24) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border me-1">
                                                {{ $methodName }}
                                            </span>
                                            @if($pay->bankDetail)
                                                <small class="text-muted d-block mt-1">
                                                    <i class="fe fe-layers me-1 text-primary"></i>{{ $pay->bankDetail->bank_name }} ({{ $pay->bankDetail->account_number }})
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($pay->transaction_ref)
                                                <span class="badge bg-secondary-subtle text-secondary border font-monospace">{{ $pay->transaction_ref }}</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($pay->amount < 0)
                                                <span class="text-danger fw-bold fs-6">- ৳ {{ number_format(abs($pay->amount), 2) }}</span>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1 font-monospace" style="font-size: 10px;">REFUND</span>
                                            @else
                                                <strong class="text-success fs-6">৳ {{ number_format($pay->amount, 2) }}</strong>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                                    <li>
                                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-primary" href="javascript:void(0)"
                                                            onclick="openEditCustomerPaymentModal({{ json_encode([
                                                                'id' => $pay->id,
                                                                'amount' => (float)$pay->amount,
                                                                'payment_method' => $pay->payment_method,
                                                                'bank_detail_id' => $pay->bank_detail_id,
                                                                'transaction_ref' => $pay->transaction_ref,
                                                                'payment_date' => $pay->payment_date ? \Carbon\Carbon::parse($pay->payment_date)->format('Y-m-d') : ($pay->created_at ? $pay->created_at->format('Y-m-d') : date('Y-m-d')),
                                                                'remarks' => $pay->remarks ?? '',
                                                                'sale_id' => $pay->sale_id,
                                                                'order_no' => $pay->sale ? ($pay->sale->order_no ?? ('SO-' . $pay->sale->id)) : null,
                                                            ]) }})">
                                                            <i class="fe fe-edit text-primary"></i>
                                                            <span>Edit Payment</span>
                                                        </a>
                                                    </li>
                                                    @if(empty($pay->sale_id))
                                                        <li>
                                                            <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-info" href="{{ route('customer-advances.receipt-pdf', $pay->id) }}" target="_blank">
                                                                <i class="fe fe-download text-info"></i>
                                                                <span>Receipt PDF</span>
                                                            </a>
                                                        </li>
                                                    @endif
                                                    <li>
                                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" href="javascript:void(0)"
                                                            onclick="if (confirm('Are you sure you want to revert this payment of ৳{{ number_format($pay->amount, 2) }}? This will restore the customer due balance.')) { document.getElementById('delCustPaymentFlat{{ $pay->id }}').submit(); }">
                                                            <i class="fe fe-trash-2 text-danger"></i>
                                                            <span>Delete / Revert</span>
                                                        </a>
                                                        <form id="delCustPaymentFlat{{ $pay->id }}" action="{{ route('delete.payment', $pay->id) }}" method="POST" class="d-none">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fe fe-inbox fs-2 d-block mb-1"></i>No payment transactions recorded for this customer yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@php
    $regularCustBanks = ($bankAccounts ?? collect())->filter(function($b) {
        $type = $b->account_type ?? '';
        $name = $b->bank_name ?? '';
        return !(stripos($type, 'MFS') !== false || preg_match('/(bkash|nagad|rocket|upay|mfs)/i', $name . ' ' . $type));
    });
    $mfsCustBanks = ($bankAccounts ?? collect())->filter(function($b) {
        $type = $b->account_type ?? '';
        $name = $b->bank_name ?? '';
        return (stripos($type, 'MFS') !== false || preg_match('/(bkash|nagad|rocket|upay|mfs)/i', $name . ' ' . $type));
    });
@endphp

<!-- Edit Customer Payment Modal (Placed Outside Tables) -->
<div class="modal fade" id="editCustomerPaymentModal" tabindex="-1" aria-labelledby="editCustomerPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="editCustomerPaymentModalLabel">
                    <i class="fe fe-edit me-2 text-primary"></i>Edit Customer Due Collection
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCustomerPaymentForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small rounded-3 mb-3 d-flex align-items-center gap-2">
                        <i class="fe fe-info fs-6"></i>
                        <span>Adjusting this payment will automatically recalculate customer due balances, update bank ledgers, and synchronize journal vouchers.</span>
                    </div>

                    <div class="mb-3" id="editCustModalSaleRow" style="display: none;">
                        <span class="text-muted small d-block">Linked Sale Order</span>
                        <strong class="text-primary" id="editCustModalSaleNumber">#SO-0</strong>
                    </div>

                    <!-- Payment Account / Method Selector -->
                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold mb-1">Receiving Account / Channel <span class="text-danger">*</span></label>
                        <select class="form-select border-light-subtle" id="editCustModalAccountSelector" onchange="handleEditCustModalAccountChange(this)" required>
                            <optgroup label="Cash">
                                <option value="cash" data-method="cash">Hand Cash (Cash in Hand)</option>
                            </optgroup>
                            @if($regularCustBanks->count() > 0)
                                <optgroup label="Bank Accounts">
                                    @foreach($regularCustBanks as $bank)
                                        <option value="bank_{{ $bank->id }}" data-method="bank" data-id="{{ $bank->id }}">
                                            {{ $bank->bank_name }} — {{ $bank->account_number }} ({{ $bank->account_name }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if($mfsCustBanks->count() > 0)
                                <optgroup label="Mobile Banking / MFS">
                                    @foreach($mfsCustBanks as $bank)
                                        <option value="mfs_{{ $bank->id }}" data-method="mobile_banking" data-id="{{ $bank->id }}">
                                            {{ $bank->bank_name }} — {{ $bank->account_number }} ({{ $bank->account_name }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        <input type="hidden" name="payment_method" id="hidden_edit_cust_modal_method" value="cash">
                        <input type="hidden" name="bank_detail_id" id="hidden_edit_cust_modal_bank_id" value="">
                    </div>

                    <!-- Transaction Ref -->
                    <div class="mb-3" id="editCustModalRefContainer" style="display: none;">
                        <label class="form-label small text-secondary fw-semibold mb-1">Transaction Ref / TrxID / Cheque #</label>
                        <input type="text" name="transaction_ref" id="editCustModalRef" class="form-control border-light-subtle" placeholder="e.g. TrxID / Cheque #">
                    </div>

                    <!-- Payment Date -->
                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold mb-1">Payment Date</label>
                        <input type="date" name="payment_date" id="editCustModalDate" class="form-control border-light-subtle" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold mb-1">Payment Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="editCustModalAmount" class="form-control fw-bold text-success fs-5 border-light-subtle" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold mb-1">Remarks / Note</label>
                        <textarea name="remarks" id="editCustModalRemarks" class="form-control border-light-subtle" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top p-3 gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3 py-2 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold text-white">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Refund Customer Advance Modal -->
@if($advanceCredit > 0)
<div class="modal fade" id="refundCustomerAdvanceModal" tabindex="-1" aria-labelledby="refundCustomerAdvanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-danger text-white px-4 py-3 border-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-sm bg-white text-danger rounded-circle d-flex align-items-center justify-content-center">
                        <i class="fe fe-corner-up-left fs-6"></i>
                    </div>
                    <h5 class="modal-title fw-bold text-white mb-0" id="refundCustomerAdvanceModalLabel">Refund Customer Advance</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('customer-advances.refund') }}" method="POST">
                @csrf
                <input type="hidden" name="customer_id" value="{{ $customer->id }}">

                <div class="modal-body p-4">
                    <div class="alert alert-light border border-light-subtle rounded-3 p-3 mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted text-uppercase fw-semibold d-block mb-1">Customer</small>
                            <h6 class="fw-bold text-dark mb-0">{{ $customer->name }}</h6>
                        </div>
                        <div class="text-end">
                            <small class="text-muted text-uppercase fw-semibold d-block mb-1">Available Advance Credit</small>
                            <span class="badge badge-soft-success px-3 py-1 rounded-pill fw-bold fs-7">৳{{ number_format($advanceCredit, 2) }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">
                            Refund Amount (৳) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 fw-bold text-secondary">৳</span>
                            <input type="number" step="0.01" min="0.01" max="{{ $advanceCredit }}" name="amount" value="{{ number_format($advanceCredit, 2, '.', '') }}" class="form-control border-start-0 ps-0 fw-bold fs-6" required placeholder="0.00">
                        </div>
                        <small class="text-muted">Maximum refundable amount is <span class="fw-bold text-dark">৳{{ number_format($advanceCredit, 2) }}</span></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">
                            Disbursement Method <span class="text-danger">*</span>
                        </label>
                        <select name="payment_method" id="show_refund_payment_method" class="form-select" onchange="toggleShowRefundBankFields()">
                            <option value="cash" selected>Cash in Hand</option>
                            <option value="bank">Bank Transfer / Cheque</option>
                            <option value="mobile_banking">Mobile Banking (bKash / Nagad / Rocket)</option>
                        </select>
                    </div>

                    <div class="mb-3" id="show_refund_bank_account_row" style="display: none;">
                        <label class="form-label small fw-bold text-dark">
                            Disbursing Bank / MFS Account <span class="text-danger">*</span>
                        </label>
                        <select name="bank_detail_id" id="show_refund_bank_detail_id" class="form-select">
                            <option value="">Select Bank / MFS Account</option>
                            @foreach($bankAccounts as $bank)
                                <option value="{{ $bank->id }}">
                                    {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3" id="show_refund_trx_ref_row" style="display: none;">
                        <label class="form-label small fw-bold text-dark">
                            Cheque / Transaction Reference
                        </label>
                        <input type="text" name="transaction_ref" class="form-control" placeholder="Cheque No. or TrxID">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">
                            Refund Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="refund_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-dark">Remarks / Reason</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Refund of unallocated advance credit balance on request"></textarea>
                    </div>
                </div>

                <div class="modal-footer px-4 py-3 bg-light border-0 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3 rounded-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4 rounded-2 fw-semibold">
                        <i class="fe fe-check me-1"></i>Confirm Refund
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
function openEditCustomerPaymentModal(data) {
    const form = document.getElementById('editCustomerPaymentForm');
    form.action = '/payments/' + data.id;

    const saleRow = document.getElementById('editCustModalSaleRow');
    const saleNum = document.getElementById('editCustModalSaleNumber');
    if (data.order_no || data.sale_id) {
        saleRow.style.display = 'block';
        saleNum.textContent = '#' + (data.order_no || ('SO-' + data.sale_id));
    } else {
        saleRow.style.display = 'none';
    }

    document.getElementById('editCustModalAmount').value = parseFloat(data.amount).toFixed(2);
    document.getElementById('editCustModalDate').value = data.payment_date || '';
    document.getElementById('editCustModalRef').value = data.transaction_ref || '';
    document.getElementById('editCustModalRemarks').value = data.remarks || '';

    const selector = document.getElementById('editCustModalAccountSelector');
    if (data.bank_detail_id) {
        for (let i = 0; i < selector.options.length; i++) {
            if (selector.options[i].getAttribute('data-id') == data.bank_detail_id) {
                selector.selectedIndex = i;
                break;
            }
        }
    } else {
        selector.value = 'cash';
    }
    handleEditCustModalAccountChange(selector);

    const modalEl = document.getElementById('editCustomerPaymentModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function handleEditCustModalAccountChange(select) {
    if (!select) return;
    const selectedOption = select.options[select.selectedIndex];
    if (!selectedOption) return;
    const method = selectedOption.getAttribute('data-method') || 'cash';
    const bankId = selectedOption.getAttribute('data-id') || '';

    document.getElementById('hidden_edit_cust_modal_method').value = method;
    document.getElementById('hidden_edit_cust_modal_bank_id').value = bankId;

    const refContainer = document.getElementById('editCustModalRefContainer');
    if (refContainer) {
        refContainer.style.display = bankId ? 'block' : 'none';
    }
}

function toggleShowRefundBankFields() {
    var select = document.getElementById('show_refund_payment_method');
    if (!select) return;
    var method = select.value;
    var bankRow = document.getElementById('show_refund_bank_account_row');
    var trxRow = document.getElementById('show_refund_trx_ref_row');
    var bankSelect = document.getElementById('show_refund_bank_detail_id');

    if (method === 'cash') {
        if (bankRow) bankRow.style.display = 'none';
        if (trxRow) trxRow.style.display = 'none';
        if (bankSelect) bankSelect.removeAttribute('required');
    } else {
        if (bankRow) bankRow.style.display = 'block';
        if (trxRow) trxRow.style.display = 'block';
        if (bankSelect) bankSelect.setAttribute('required', 'required');
    }
}

function filterCustomerInvoiceCategory(saleId, btn) {
    document.querySelectorAll('.cat-filter-btn').forEach(b => {
        b.classList.remove('active', 'btn-primary');
        b.classList.add('btn-outline-secondary');
    });
    btn.classList.add('active', 'btn-primary');
    btn.classList.remove('btn-outline-secondary');

    const cards = document.querySelectorAll('.invoice-category-card');
    cards.forEach(card => {
        if (saleId === 'all' || card.getAttribute('data-invoice-id') === String(saleId)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function toggleInvoiceCardBody(bodyId, headerEl) {
    const body = document.getElementById(bodyId);
    if (!body) return;
    const icon = headerEl.querySelector('.toggle-icon');
    const isHidden = (body.style.display === 'none');
    if (isHidden) {
        body.style.display = 'block';
        if (icon) icon.style.transform = 'rotate(0deg)';
    } else {
        body.style.display = 'none';
        if (icon) icon.style.transform = 'rotate(-90deg)';
    }
}

function toggleAllInvoiceCards(expand) {
    document.querySelectorAll('.invoice-card-body').forEach(body => {
        body.style.display = expand ? 'block' : 'none';
    });
    document.querySelectorAll('.invoice-category-card .toggle-icon').forEach(icon => {
        icon.style.transform = expand ? 'rotate(0deg)' : 'rotate(-90deg)';
    });
}
</script>
@endpush
