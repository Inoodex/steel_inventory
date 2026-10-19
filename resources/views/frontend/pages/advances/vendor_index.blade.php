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
                <h4 class="card-title fw-bold text-dark mb-1">Vendor Advance Payments</h4>
                <p class="text-muted small mb-0">Track advance disbursements, procurement booking deposits, and supplier credit balances</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('vendor-advances.pdf') }}" target="_blank" class="btn btn-outline-danger px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-download"></i>
                    <span>Statement PDF</span>
                </a>
                <a href="{{ route('vendor-advances.create') }}" class="btn btn-primary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="fe fe-plus-circle"></i>
                    <span>Pay Vendor Advance</span>
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
                        <i class="fe fe-arrow-up-right fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Active Advance Paid to Vendors</small>
                        <h4 class="fw-bold text-success mb-0">৳{{ number_format($totalActiveVendorAdvance, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-truck fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Vendors with Advance Balance</small>
                        <h4 class="fw-bold text-dark mb-0">{{ number_format($vendorsWithAdvance->count()) }}</h4>
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
                        <small class="text-muted d-block">Total Advance Disbursements (Filtered)</small>
                        <h4 class="fw-bold text-info mb-0">৳{{ number_format($totalDisbursementsAmount, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-3 border-bottom" id="vendorAdvanceTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="balances-tab" data-bs-toggle="tab" data-bs-target="#balances-pane" type="button" role="tab">
                <i class="fe fe-award me-1"></i>Active Vendor Advance Balances ({{ $vendorsWithAdvance->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane" type="button" role="tab">
                <i class="fe fe-list me-1"></i>Advance Disbursements History
            </button>
        </li>
    </ul>

    <div class="tab-content" id="vendorAdvanceTabContent">
        <!-- Tab 1: Active Advance Balances -->
        <div class="tab-pane fade show active" id="balances-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3 text-secondary small fw-bold">Vendor Name</th>
                                    <th class="text-secondary small fw-bold">Company / Phone</th>
                                    <th class="text-end text-secondary small fw-bold">Total Purchases (৳)</th>
                                    <th class="text-end text-secondary small fw-bold">Total Paid (৳)</th>
                                    <th class="text-end text-secondary small fw-bold">Advance Balance (৳)</th>
                                    <th class="text-center text-secondary small fw-bold pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vendorsWithAdvance as $ven)
                                    <tr>
                                        <td class="ps-3">
                                            <a href="{{ route('vendors.show', $ven->id) }}" class="fw-bold text-dark text-decoration-none">
                                                {{ $ven->name }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="d-block text-dark small fw-semibold">{{ $ven->company ?: 'Mill / Supplier' }}</span>
                                            <small class="text-muted"><i class="fe fe-phone me-1"></i>{{ $ven->phone }}</small>
                                        </td>
                                        <td class="text-end fw-semibold text-secondary">
                                            ৳{{ number_format((float)($ven->purchases_sum_total_price ?? 0), 2) }}
                                        </td>
                                        <td class="text-end fw-semibold text-dark">
                                            ৳{{ number_format((float)($ven->payments_sum_amount ?? 0), 2) }}
                                        </td>
                                        <td class="text-end">
                                            <span class="badge badge-soft-success px-3 py-1.5 rounded-pill fs-7">
                                                ৳{{ number_format($ven->advance_balance, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-center pe-3">
                                            <div class="dropdown">
                                                <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                                    <li>
                                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('vendors.show', $ven->id) }}">
                                                            <i class="fe fe-eye text-primary"></i>
                                                            <span>View Vendor Profile</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-success fw-semibold" href="{{ route('vendor-advances.create', ['vendor_id' => $ven->id]) }}">
                                                            <i class="fe fe-plus-circle text-success"></i>
                                                            <span>Pay More Advance</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger fw-semibold" href="javascript:void(0)" onclick="openRefundVendorAdvanceModal({{ $ven->id }}, '{{ addslashes($ven->name) }}', {{ (float)$ven->advance_balance }})">
                                                            <i class="fe fe-corner-down-left text-danger"></i>
                                                            <span>Return Advance Money</span>
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
                                            <span>No vendors currently hold an advance balance from you.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Advance Disbursements History -->
        <div class="tab-pane fade" id="history-pane" role="tabpanel">
            <!-- Filter Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('vendor-advances.index') }}" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small text-secondary fw-semibold mb-1">Vendor / Supplier</label>
                            <select name="vendor_id" class="form-select form-select-sm border-light-subtle">
                                <option value="">All Vendors</option>
                                @foreach($vendorList as $v)
                                    <option value="{{ $v->id }}" {{ request('vendor_id') == $v->id ? 'selected' : '' }}>
                                        {{ $v->name }} ({{ $v->phone }})
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
                            <a href="{{ route('vendor-advances.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-2">
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
                                    <th class="text-secondary small fw-bold">Vendor / Supplier</th>
                                    <th class="text-secondary small fw-bold">Paid From Account</th>
                                    <th class="text-secondary small fw-bold">TrxID / Reference</th>
                                    <th class="text-end text-secondary small fw-bold">Amount (৳)</th>
                                    <th class="text-secondary small fw-bold">Remarks</th>
                                    <th class="text-secondary small fw-bold">Recorded By</th>
                                    <th class="text-center text-secondary small fw-bold pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($advanceDisbursements as $disbursement)
                                    <tr>
                                        <td class="ps-3 text-dark fw-semibold small">
                                            {{ $disbursement->payment_date ? \Carbon\Carbon::parse($disbursement->payment_date)->format('d M Y') : $disbursement->created_at->format('d M Y') }}
                                        </td>
                                        <td>
                                            @if($disbursement->vendor)
                                                <a href="{{ route('vendors.show', $disbursement->vendor->id) }}" class="fw-bold text-dark text-decoration-none small">
                                                    {{ $disbursement->vendor->name }}
                                                </a>
                                                <small class="text-muted d-block">{{ $disbursement->vendor->phone }}</small>
                                            @else
                                                <span class="text-muted small">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($disbursement->payment_method === 'cash')
                                                <span class="badge bg-light text-dark border px-2 py-1 rounded-2">
                                                    <i class="fe fe-dollar-sign text-success me-1"></i>Cash in Hand
                                                </span>
                                            @elseif($disbursement->bankDetail)
                                                <span class="badge bg-light text-dark border px-2 py-1 rounded-2">
                                                    <i class="fe fe-credit-card text-primary me-1"></i>{{ $disbursement->bankDetail->bank_name }} ({{ $disbursement->bankDetail->account_number }})
                                                </span>
                                            @else
                                                <span class="badge bg-light text-dark border px-2 py-1 rounded-2 text-capitalize">
                                                    {{ $disbursement->payment_method }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="font-monospace small text-muted">
                                            {{ $disbursement->transaction_ref ?: '-' }}
                                        </td>
                                        <td class="text-end fw-bold {{ $disbursement->amount < 0 ? 'text-success' : 'text-danger' }} fs-7">
                                            {{ $disbursement->amount < 0 ? '+' : '' }}৳{{ number_format(abs($disbursement->amount), 2) }}
                                            @if($disbursement->amount < 0)
                                                <span class="badge bg-success-light text-success ms-1" style="font-size: 10px;">Returned</span>
                                            @endif
                                        </td>
                                        <td class="text-muted small">
                                            {{ $disbursement->remarks ?: ($disbursement->amount < 0 ? 'Vendor Advance Return' : 'Vendor Advance Prepayment') }}
                                        </td>
                                        <td class="text-muted small">
                                            {{ $disbursement->creator?->name ?? 'System' }}
                                        </td>
                                        <td class="text-center pe-3">
                                            <a href="{{ route('vendor-advances.voucher-pdf', $disbursement->id) }}" target="_blank" class="btn btn-sm btn-outline-danger py-1 px-2 rounded-2" title="{{ $disbursement->amount < 0 ? 'Download Advance Return Voucher PDF' : 'Download Payment Voucher PDF' }}">
                                                <i class="fe fe-download me-1"></i>{{ $disbursement->amount < 0 ? 'Return PDF' : 'Voucher PDF' }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="fe fe-inbox fs-1 mb-2 text-secondary d-block"></i>
                                            <span>No vendor advance disbursements found.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($advanceDisbursements->hasPages())
                        <div class="p-3 border-top d-flex justify-content-end">
                            {{ $advanceDisbursements->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Return Vendor Advance Modal (Placed outside table per AGENTS.md rules) -->
<div class="modal fade" id="refundVendorAdvanceModal" tabindex="-1" aria-labelledby="refundVendorAdvanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-success text-white px-4 py-3 border-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-sm bg-white text-success rounded-circle d-flex align-items-center justify-content-center">
                        <i class="fe fe-corner-down-left fs-6"></i>
                    </div>
                    <h5 class="modal-title fw-bold text-white mb-0" id="refundVendorAdvanceModalLabel">Receive Advance Return from Vendor</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('vendor-advances.refund') }}" method="POST">
                @csrf
                <input type="hidden" name="vendor_id" id="return_vendor_id" value="">

                <div class="modal-body p-4">
                    <!-- Vendor Information Banner -->
                    <div class="alert alert-light border border-light-subtle rounded-3 p-3 mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted text-uppercase fw-semibold d-block mb-1">Vendor / Supplier</small>
                            <h6 class="fw-bold text-dark mb-0" id="return_vendor_name">-</h6>
                        </div>
                        <div class="text-end">
                            <small class="text-muted text-uppercase fw-semibold d-block mb-1">Active Advance Balance</small>
                            <span class="badge badge-soft-success px-3 py-1 rounded-pill fw-bold fs-7" id="return_max_balance_badge">৳0.00</span>
                        </div>
                    </div>

                    <!-- Return Amount Input -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">
                            Returned Amount (৳) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 fw-bold text-secondary">৳</span>
                            <input type="number" step="0.01" min="0.01" name="amount" id="return_amount" class="form-control border-start-0 ps-0 fw-bold fs-6" required placeholder="0.00">
                        </div>
                        <small class="text-muted">Maximum returnable amount is <span class="fw-bold text-dark" id="return_max_balance_text">৳0.00</span></small>
                    </div>

                    <!-- Receiving Method -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">
                            Receiving Method <span class="text-danger">*</span>
                        </label>
                        <select name="payment_method" id="return_payment_method" class="form-select" onchange="toggleReturnBankFields()">
                            <option value="cash" selected>Cash in Hand</option>
                            <option value="bank">Bank Transfer / Cheque</option>
                            <option value="mobile_banking">Mobile Banking (bKash / Nagad / Rocket)</option>
                        </select>
                    </div>

                    <!-- Bank Account Selection (Conditional) -->
                    <div class="mb-3" id="return_bank_account_row" style="display: none;">
                        <label class="form-label small fw-bold text-dark">
                            Receiving Bank / MFS Account <span class="text-danger">*</span>
                        </label>
                        <select name="bank_detail_id" id="return_bank_detail_id" class="form-select">
                            <option value="">Select Bank / MFS Account</option>
                            @foreach($bankAccounts as $bank)
                                <option value="{{ $bank->id }}">
                                    {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Transaction Ref -->
                    <div class="mb-3" id="return_trx_ref_row" style="display: none;">
                        <label class="form-label small fw-bold text-dark">
                            Cheque / Transaction Reference
                        </label>
                        <input type="text" name="transaction_ref" class="form-control" placeholder="Cheque No. or TrxID">
                    </div>

                    <!-- Date -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">
                            Return Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="refund_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <!-- Remarks -->
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-dark">Remarks / Reason</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Vendor returned unused advance prepayment"></textarea>
                    </div>
                </div>

                <div class="modal-footer px-4 py-3 bg-light border-0 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3 rounded-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 rounded-2 fw-semibold text-white">
                        <i class="fe fe-check me-1"></i>Confirm Return
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openRefundVendorAdvanceModal(vendorId, vendorName, maxAdvance) {
        document.getElementById('return_vendor_id').value = vendorId;
        document.getElementById('return_vendor_name').textContent = vendorName;
        document.getElementById('return_max_balance_badge').textContent = '৳' + Number(maxAdvance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('return_max_balance_text').textContent = '৳' + Number(maxAdvance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        var amountInput = document.getElementById('return_amount');
        amountInput.max = maxAdvance;
        amountInput.value = maxAdvance > 0 ? maxAdvance.toFixed(2) : '';
        
        // Reset method
        document.getElementById('return_payment_method').value = 'cash';
        toggleReturnBankFields();

        var modal = new bootstrap.Modal(document.getElementById('refundVendorAdvanceModal'));
        modal.show();
    }

    function toggleReturnBankFields() {
        var method = document.getElementById('return_payment_method').value;
        var bankRow = document.getElementById('return_bank_account_row');
        var trxRow = document.getElementById('return_trx_ref_row');
        var bankSelect = document.getElementById('return_bank_detail_id');

        if (method === 'cash') {
            bankRow.style.display = 'none';
            trxRow.style.display = 'none';
            bankSelect.removeAttribute('required');
        } else {
            bankRow.style.display = 'block';
            trxRow.style.display = 'block';
            bankSelect.setAttribute('required', 'required');
        }
    }
</script>
@endpush
@endsection
