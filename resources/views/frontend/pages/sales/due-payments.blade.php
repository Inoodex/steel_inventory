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
    .table-custom th, .table-custom td {
        white-space: nowrap;
    }
    .nav-tabs-custom .nav-link {
        border: none;
        border-bottom: 2.5px solid transparent;
        color: #64748b;
        font-weight: 600;
        padding: 10px 18px;
        background: transparent;
        transition: all 0.2s ease;
    }
    .nav-tabs-custom .nav-link.active {
        color: #7638ff;
        border-bottom-color: #7638ff;
        background: transparent;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header (No Breadcrumbs) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Customer Dues &amp; Receivables</h4>
                <p class="text-muted small mb-0">Overview of customer opening dues, unpaid sales orders, and credit settlements</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('due-payments.pdf') }}" target="_blank" class="btn btn-outline-danger px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-file-text fs-6"></i>
                    <span>Export PDF</span>
                </a>
                <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3 shadow-sm">
                    <i class="fe fe-arrow-left me-2"></i>    
                    Back to Sales
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fe fe-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fe fe-alert-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Summary Stats Bar -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-users fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Customers with Due</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($customersWithDue->count()) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-warning-light text-warning rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-layers fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Opening Dues</h6>
                        <h4 class="mb-0 fw-bold text-warning">৳{{ number_format($totalOpeningDues, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-shopping-cart fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Sales Invoices Due</h6>
                        <h4 class="mb-0 fw-bold text-info">৳{{ number_format($totalInvoiceDues, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-danger-light text-danger rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Grand Outstanding Due</h6>
                        <h4 class="mb-0 fw-bold text-danger">৳{{ number_format($grandTotalDues, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Summary Stats Bar -->

    <!-- Tabbed Dues Management Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs nav-tabs-custom px-3 pt-2" id="duesTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="customer-summary-tab" data-bs-toggle="tab" data-bs-target="#customerSummaryPane" type="button" role="tab" aria-selected="true">
                        <i class="fe fe-users me-2"></i>By Customer Summary (Opening + Sales Dues)
                        <span class="badge bg-primary ms-2 rounded-pill">{{ $customersWithDue->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="invoice-dues-tab" data-bs-toggle="tab" data-bs-target="#invoiceDuesPane" type="button" role="tab" aria-selected="false">
                        <i class="fe fe-file-text me-2"></i>By Sales Invoices (Orders)
                        <span class="badge bg-danger ms-2 rounded-pill">{{ $sales->count() }}</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content" id="duesTabContent">
            
            <!-- ==========================================
                 TAB 1: CUSTOMER SUMMARY (OPENING + SALES DUE)
                 ========================================== -->
            <div class="tab-pane fade show active" id="customerSummaryPane" role="tabpanel">
                <div class="p-3 border-bottom bg-light-subtle">
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="search-box-custom">
                                <input type="text" id="custDueSearchInput" class="form-control border-light-subtle" placeholder="Search customer name, phone, address..." autocomplete="off">
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-6 text-md-end text-muted small">
                            Showing <span id="visibleCustDueCount" class="fw-bold text-dark">{{ $customersWithDue->count() }}</span> of {{ $customersWithDue->count() }} customers with dues
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle mb-0" id="customersDueTable">
                        <thead class="bg-light text-secondary fs-7 text-uppercase">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Customer Name</th>
                                <th>Contact</th>
                                <th>Opening Due</th>
                                <th>Sales Due</th>
                                <th>Total Due</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @forelse ($customersWithDue as $cust)
                                <tr class="cust-due-row" data-search="{{ strtolower($cust->name . ' ' . $cust->phone . ' ' . $cust->email . ' ' . $cust->address) }}">
                                    <td class="ps-4 text-muted fw-semibold">{{ $loop->iteration }}</td>
                                    <td>
                                        <div>
                                            <a href="{{ route('customers.show', $cust->id) }}" class="fw-bold text-dark hover-primary mb-0 text-decoration-none d-block text-truncate" style="max-width: 200px;">
                                                {{ $cust->name }}
                                            </a>
                                            @if($cust->address)
                                                <small class="text-muted fs-8 d-block text-truncate" style="max-width: 200px;">{{ $cust->address }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <div class="fw-semibold text-dark fs-7">
                                                <i class="fe fe-phone me-1 text-muted fs-8"></i>{{ $cust->phone }}
                                            </div>
                                            @if($cust->email)
                                                <small class="text-muted fs-8"><i class="fe fe-mail me-1"></i>{{ $cust->email }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($cust->opening_due > 0)
                                            <span class="badge badge-soft-warning px-2.5 py-1 rounded-pill fs-7 fw-semibold">
                                                ৳{{ number_format($cust->opening_due, 2) }}
                                            </span>
                                        @else
                                            <span class="text-muted small">৳0.00</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($cust->sales_due > 0)
                                            <span class="badge badge-soft-info px-2.5 py-1 rounded-pill fs-7 fw-semibold">
                                                ৳{{ number_format($cust->sales_due, 2) }}
                                            </span>
                                        @else
                                            <span class="text-muted small">৳0.00</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-danger px-3 py-1.5 rounded-pill fs-7 fw-bold">
                                            ৳{{ number_format($cust->total_due, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <button type="button" class="btn btn-sm btn-success rounded-2 px-2.5 py-1 d-inline-flex align-items-center gap-1"
                                                onclick="openCustomerPaymentModal({{ $cust->id }}, '{{ addslashes($cust->name) }}', {{ $cust->opening_due }}, {{ $cust->total_due }})">
                                                <i class="fe fe-dollar-sign"></i>
                                                <span>Collect Due</span>
                                            </button>
                                            <a href="{{ route('customers.ledger', $cust->id) }}" class="btn btn-sm btn-outline-primary rounded-2 px-2.5 py-1" title="View Ledger">
                                                <i class="fe fe-book-open"></i> View Ledger
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center justify-content-center">
                                            <div class="avatar avatar-xl bg-success-light text-success rounded-circle mb-3 d-flex align-items-center justify-content-center">
                                                <i class="fe fe-check-circle fs-1"></i>
                                            </div>
                                            <h5 class="fw-bold text-dark mb-1">No Customer Dues Outstanding</h5>
                                            <p class="text-muted small mb-0">All customers have cleared their opening balances and invoices!</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ==========================================
                 TAB 2: BY SALES INVOICES (ORDER BY ORDER)
                 ========================================== -->
            <div class="tab-pane fade" id="invoiceDuesPane" role="tabpanel">
                <div class="p-3 border-bottom bg-light-subtle">
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="search-box-custom">
                                <input type="text" id="dueSearchInput" class="form-control border-light-subtle" placeholder="Search order no, customer name, phone..." autocomplete="off">
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-6 text-md-end text-muted small">
                            Showing <span id="visibleDueCount" class="fw-bold text-dark">{{ $sales->count() }}</span> of {{ $sales->count() }} due invoices
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle mb-0" id="duePaymentsTable">
                        <thead class="bg-light text-secondary fs-7 text-uppercase">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Date</th>
                                <th>Order No</th>
                                <th>Customer</th>
                                <!-- <th>Warehouse</th> -->
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Due Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @forelse ($sales as $sale)
                                @php
                                    $customerName = $sale->customer->name ?? 'N/A';
                                    $customerPhone = $sale->customer->phone ?? 'N/A';
                                    $warehouseName = $sale->warehouse->name ?? 'Main Yard';
                                @endphp
                                <tr class="due-row" data-search="{{ strtolower($sale->order_no . ' ' . $customerName . ' ' . $customerPhone . ' ' . $warehouseName) }}">
                                    <td class="ps-4 text-muted fw-semibold">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="text-secondary small fw-semibold">
                                            {{ $sale->created_at ? $sale->created_at->format('d M Y') : 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('sales.show', $sale->id) }}" class="fw-bold text-primary font-monospace text-decoration-none">
                                            #{{ $sale->order_no }}
                                        </a>
                                    </td>
                                    <td>
                                        <div>
                                            <span class="fw-bold text-dark d-block">{{ $customerName }}</span>
                                            <small class="text-muted fs-7"><i class="fe fe-phone me-1"></i>{{ $customerPhone }}</small>
                                        </div>
                                    </td>
                                    <!-- <td>
                                        <span class="badge bg-light text-secondary border px-2 py-1 fs-8">
                                            {{ $warehouseName }}
                                        </span>
                                    </td> -->
                                    <td>
                                        <span class="badge badge-soft-primary px-3 py-1 rounded-pill fs-7">
                                            ৳{{ number_format($sale->payble, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-success px-3 py-1 rounded-pill fs-7">
                                            ৳{{ number_format($sale->advanced_payment, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-danger px-3 py-1 rounded-pill fs-7">
                                            ৳{{ number_format($sale->due_payment, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($sale->due_payment > 0)
                                            <a href="{{ route('sales.payments', $sale->id) }}" class="btn btn-sm btn-outline-success rounded-2 px-3">
                                                <i class="fe fe-credit-card me-1"></i> Pay Now
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center justify-content-center">
                                            <div class="avatar avatar-xl bg-success-light text-success rounded-circle mb-3 d-flex align-items-center justify-content-center">
                                                <i class="fe fe-check-circle fs-1"></i>
                                            </div>
                                            <h5 class="fw-bold text-dark mb-1">No Due Orders Found</h5>
                                            <p class="text-muted small mb-0">All sales orders are fully paid</p>
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
</div>

<!-- ==========================================
     DIRECT CUSTOMER DUE PAYMENT MODAL
     (Placed at bottom outside tables per AGENTS.md)
     ========================================== -->
<div class="modal fade" id="customerPaymentModal" tabindex="-1" aria-labelledby="customerPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom bg-light">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-md bg-success-light text-success rounded-circle d-flex align-items-center justify-content-center">
                        <i class="fe fe-dollar-sign fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Collect Customer Due Payment</h5>
                        <small class="text-muted" id="modalCustomerTitle">Customer Due Settlement</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('customer.due.pay') }}" method="POST">
                @csrf
                <input type="hidden" name="customer_id" id="modalCustomerId">

                <div class="modal-body p-4">
                    <!-- Customer Current Dues Status Badge -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-muted">Opening Due:</span>
                            <span class="fw-bold text-warning" id="modalOpeningDueDisplay">৳ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-muted">Total Outstanding Balance:</span>
                            <span class="fw-bold text-danger fs-6" id="modalTotalDueDisplay">৳ 0.00</span>
                        </div>
                    </div>

                    <!-- Payment Type Selection -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark mb-1">Payment Allocation Target <span class="text-danger">*</span></label>
                        <select name="payment_type" id="modalPaymentType" class="form-select border-light-subtle" required>
                            <option value="opening_due">Settle Opening Due (Reduces Opening Balance)</option>
                            <option value="general_payment">General Account Payment / Credit Collection</option>
                        </select>
                    </div>

                    <!-- Collection Amount -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark mb-1">Amount to Collect (৳) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="modalAmountInput" class="form-control fw-bold text-dark" placeholder="0.00" required>
                    </div>

                    <!-- Payment Method -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark mb-1">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" id="modalPaymentMethod" class="form-select border-light-subtle" onchange="toggleModalBankDetails(this.value)">
                            <option value="cash" selected>Cash</option>
                            <option value="bank">Bank Transfer / Deposit</option>
                            <option value="mobile_banking">Mobile Banking (bKash/Nagad)</option>
                        </select>
                    </div>

                    <!-- Bank Account Selector -->
                    <div class="mb-3" id="modalBankContainer" style="display: none;">
                        <label class="form-label fw-bold small text-dark mb-1">Receiving Bank Account <span class="text-danger">*</span></label>
                        <select name="bank_detail_id" class="form-select border-light-subtle">
                            <option value="">Select Bank Account</option>
                            @foreach($bankAccounts ?? [] as $bank)
                                <option value="{{ $bank->id }}" {{ $bank->is_default ? 'selected' : '' }}>
                                    {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Transaction Ref -->
                    <div class="mb-3" id="modalTrxContainer" style="display: none;">
                        <label class="form-label fw-bold small text-dark mb-1">Transaction Ref / Cheque No</label>
                        <input type="text" name="transaction_ref" class="form-control border-light-subtle" placeholder="e.g. TrxID / Cheque #">
                    </div>

                    <!-- Remarks / Notes -->
                    <div class="mb-0">
                        <label class="form-label fw-bold small text-dark mb-1">Remarks / Note</label>
                        <input type="text" name="remarks" class="form-control border-light-subtle" placeholder="Optional payment remarks...">
                    </div>
                </div>

                <div class="modal-footer border-top p-3 bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary px-3 py-2 rounded-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 py-2 rounded-2 shadow fw-semibold">
                        <i class="fe fe-check me-1"></i>Confirm Collection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openCustomerPaymentModal(id, name, openingDue, totalDue) {
    document.getElementById('modalCustomerId').value = id;
    document.getElementById('modalCustomerTitle').textContent = 'Settling dues for ' + name;
    document.getElementById('modalOpeningDueDisplay').textContent = '৳ ' + parseFloat(openingDue || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('modalTotalDueDisplay').textContent = '৳ ' + parseFloat(totalDue || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('modalAmountInput').value = parseFloat(openingDue > 0 ? openingDue : totalDue).toFixed(2);

    const typeSelect = document.getElementById('modalPaymentType');
    if (openingDue > 0) {
        typeSelect.value = 'opening_due';
    } else {
        typeSelect.value = 'general_payment';
    }

    const modal = new bootstrap.Modal(document.getElementById('customerPaymentModal'));
    modal.show();
}

function toggleModalBankDetails(val) {
    const bankBox = document.getElementById('modalBankContainer');
    const trxBox = document.getElementById('modalTrxContainer');
    if (val === 'cash') {
        bankBox.style.display = 'none';
        trxBox.style.display = 'none';
    } else {
        bankBox.style.display = 'block';
        trxBox.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Tab 1: Customer Summary Filter
    const custSearch = document.getElementById('custDueSearchInput');
    const custRows = document.querySelectorAll('.cust-due-row');
    const custCountSpan = document.getElementById('visibleCustDueCount');

    if (custSearch) {
        custSearch.addEventListener('input', function() {
            const q = custSearch.value.toLowerCase().trim();
            let count = 0;
            custRows.forEach(r => {
                const text = r.dataset.search || '';
                if (q === '' || text.includes(q)) {
                    r.style.display = '';
                    count++;
                } else {
                    r.style.display = 'none';
                }
            });
            if (custCountSpan) custCountSpan.textContent = count;
        });
    }

    // Tab 2: Invoices Due Filter
    const invoiceSearch = document.getElementById('dueSearchInput');
    const invoiceRows = document.querySelectorAll('.due-row');
    const invoiceCountSpan = document.getElementById('visibleDueCount');

    if (invoiceSearch) {
        invoiceSearch.addEventListener('input', function() {
            const q = invoiceSearch.value.toLowerCase().trim();
            let count = 0;
            invoiceRows.forEach(r => {
                const text = r.dataset.search || '';
                if (q === '' || text.includes(q)) {
                    r.style.display = '';
                    count++;
                } else {
                    r.style.display = 'none';
                }
            });
            if (invoiceCountSpan) invoiceCountSpan.textContent = count;
        });
    }
});
</script>
@endpush
