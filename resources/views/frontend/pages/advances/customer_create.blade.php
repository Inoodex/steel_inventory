@extends('frontend.layouts.app')

@push('styles')
<style>
    .customer-snapshot-card {
        border-radius: 12px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
    }
    .projected-box {
        background: #f0fdf4;
        border: 1.5px dashed #22c55e;
        border-radius: 10px;
        padding: 14px;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header (No Breadcrumbs per AGENTS.md) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Receive Customer Advance Deposit</h4>
                <p class="text-muted small mb-0">Record upfront prepayment or booking deposit to customer's account</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('customer-advances.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back to Customer Advances</span>
                </a>
            </div>
        </div>
    </div>

    @php
        $regularBanks = ($bankAccounts ?? collect())->filter(function($b) {
            $type = $b->account_type ?? '';
            $name = $b->bank_name ?? '';
            return !(stripos($type, 'MFS') !== false || preg_match('/(bkash|nagad|rocket|upay|mfs)/i', $name . ' ' . $type));
        });
        $mfsBanks = ($bankAccounts ?? collect())->filter(function($b) {
            $type = $b->account_type ?? '';
            $name = $b->bank_name ?? '';
            return (stripos($type, 'MFS') !== false || preg_match('/(bkash|nagad|rocket|upay|mfs)/i', $name . ' ' . $type));
        });
    @endphp

    <form method="POST" action="{{ route('customer-advances.store') }}" id="customerAdvanceForm">
        @csrf
        <div class="row g-4">
            <!-- Left Column: Form Details -->
            <div class="col-lg-7 col-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">
                            <i class="fe fe-arrow-down-left text-success me-2"></i>Deposit Transaction Details
                        </h6>

                        <!-- Customer Selection -->
                        <div class="mb-3">
                            <label class="form-label small text-secondary fw-semibold mb-1">
                                Customer <span class="text-danger">*</span>
                            </label>
                            <select name="customer_id" id="customerSelect" class="form-select border-light-subtle" required onchange="updateCustomerSnapshot()">
                                <option value="" data-name="" data-company="" data-phone="" data-due="0" data-advance="0">-- Select Customer --</option>
                                @foreach($customerList as $cust)
                                    <option value="{{ $cust->id }}"
                                        data-name="{{ $cust->name }}"
                                        data-company="{{ $cust->company ?: 'Individual' }}"
                                        data-phone="{{ $cust->phone }}"
                                        data-due="{{ $cust->net_due }}"
                                        data-advance="{{ $cust->advance_credit }}"
                                        {{ (old('customer_id', $selectedCustomerId) == $cust->id) ? 'selected' : '' }}>
                                        {{ $cust->name }} — {{ $cust->phone }} ({{ $cust->company ?: 'Individual' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Deposit Amount -->
                        <div class="mb-3">
                            <label class="form-label small text-secondary fw-semibold mb-1">
                                Advance Deposit Amount (৳) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-light-subtle fw-bold text-success">৳</span>
                                <input type="number" step="0.01" min="0.01" name="amount" id="depositAmount"
                                    class="form-control border-light-subtle fw-bold text-success fs-5"
                                    placeholder="0.00" value="{{ old('amount') }}" required oninput="calculateProjectedBalance()">
                            </div>
                            @error('amount')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Receiving Account (Clean Single Dropdown) -->
                        <div class="mb-3">
                            <label class="form-label small text-secondary fw-semibold mb-1">
                                Receiving Account <span class="text-danger">*</span>
                            </label>
                            <select id="accountSelector" class="form-select border-light-subtle" onchange="handleAccountChange(this)" required>
                                <optgroup label="Cash">
                                    <option value="cash" data-method="cash">Hand Cash</option>
                                </optgroup>
                                @if($regularBanks->count() > 0)
                                    <optgroup label="Bank Accounts">
                                        @foreach($regularBanks as $bank)
                                            <option value="bank_{{ $bank->id }}" data-method="bank" data-id="{{ $bank->id }}" {{ $bank->is_default ? 'selected' : '' }}>
                                                {{ $bank->bank_name }} — {{ $bank->account_number }} ({{ $bank->account_name }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                @if($mfsBanks->count() > 0)
                                    <optgroup label="Mobile Banking / MFS">
                                        @foreach($mfsBanks as $bank)
                                            <option value="mfs_{{ $bank->id }}" data-method="mobile_banking" data-id="{{ $bank->id }}" {{ (!isset($regularBanks) || $regularBanks->isEmpty()) && $loop->first ? 'selected' : '' }}>
                                                {{ $bank->bank_name }} — {{ $bank->account_number }} ({{ $bank->account_name }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                            <input type="hidden" name="payment_method" id="hidden_payment_method" value="bank">
                            <input type="hidden" name="bank_detail_id" id="hidden_bank_detail_id" value="">
                        </div>

                        <!-- TrxID / Reference (Conditional) -->
                        <div class="mb-3" id="trxContainer" style="display: none;">
                            <label class="form-label small text-secondary fw-semibold mb-1">TrxID / Cheque Ref</label>
                            <input type="text" name="transaction_ref" id="transaction_ref" class="form-control border-light-subtle" placeholder="e.g. TrxID / Cheque # / Deposit Slip" value="{{ old('transaction_ref') }}">
                        </div>

                        <!-- Payment Date -->
                        <div class="mb-3">
                            <label class="form-label small text-secondary fw-semibold mb-1">Deposit Date</label>
                            <input type="date" name="payment_date" class="form-control border-light-subtle" value="{{ old('payment_date', date('Y-m-d')) }}">
                        </div>

                        <!-- Remarks -->
                        <div class="mb-4">
                            <label class="form-label small text-secondary fw-semibold mb-1">Remarks / Note</label>
                            <textarea name="remarks" class="form-control border-light-subtle" rows="3" placeholder="e.g. Booking deposit for steel rods delivery, order commitment advance">{{ old('remarks') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <a href="{{ route('customer-advances.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-2">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-success px-4 py-2 rounded-2 fw-semibold shadow-sm">
                                <i class="fe fe-check-circle me-1"></i>Confirm &amp; Record Deposit
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Live Customer Snapshot & Impact Preview -->
            <div class="col-lg-5 col-12">
                <div class="card customer-snapshot-card shadow-sm border-0 mb-3">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">
                            <i class="fe fe-user text-primary me-2"></i>Customer Account Snapshot
                        </h6>

                        <!-- Customer Details Display -->
                        <div id="noCustomerSelected" class="text-center py-4 text-muted">
                            <i class="fe fe-user-x fs-1 d-block mb-2 text-secondary"></i>
                            <span>Select a customer from the left to preview live balances and projected advance credit.</span>
                        </div>

                        <div id="customerSelectedInfo" style="display: none;">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar avatar-lg bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center fw-bold fs-4" id="avatarInitial">
                                    C
                                </div>
                                <div>
                                    <h5 class="fw-bold text-dark mb-0" id="previewCustomerName">Customer Name</h5>
                                    <span class="text-muted small" id="previewCustomerCompany">Company</span>
                                    <div class="small text-muted mt-0.5"><i class="fe fe-phone me-1"></i><span id="previewCustomerPhone">Phone</span></div>
                                </div>
                            </div>

                            <div class="p-3 bg-light rounded-3 mb-3 border">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-secondary small fw-semibold">Current Outstanding Due:</span>
                                    <span class="fw-bold text-danger fs-6" id="previewCurrentDue">৳ 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-secondary small fw-semibold">Existing Advance Balance:</span>
                                    <span class="fw-bold text-success fs-6" id="previewCurrentAdvance">৳ 0.00</span>
                                </div>
                            </div>

                            <!-- Projected Box -->
                            <div class="projected-box mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-dark small fw-bold">New Deposit Amount:</span>
                                    <span class="fw-bold text-primary fs-6" id="previewDepositAmount">৳ 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <span class="text-success small fw-bold">Projected New Advance Credit:</span>
                                    <span class="fw-bold text-success fs-5" id="previewProjectedAdvance">৳ 0.00</span>
                                </div>
                            </div>

                            <div class="alert alert-info py-2 px-3 small rounded-2 mb-0">
                                <i class="fe fe-info me-1"></i> This advance deposit will be credited to the customer's account and automatically deducted when recording their next sales order.
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
function handleAccountChange(select) {
    if (!select) return;
    const selectedOption = select.options[select.selectedIndex];
    if (!selectedOption) return;
    const method = selectedOption.getAttribute('data-method') || 'bank';
    const bankId = selectedOption.getAttribute('data-id') || '';

    const hiddenMethod = document.getElementById('hidden_payment_method');
    const hiddenBank = document.getElementById('hidden_bank_detail_id');
    const trxContainer = document.getElementById('trxContainer');

    if (hiddenMethod) hiddenMethod.value = method;
    if (hiddenBank) hiddenBank.value = bankId;

    if (trxContainer) {
        trxContainer.style.display = bankId ? 'block' : 'none';
    }
}

function updateCustomerSnapshot() {
    const select = document.getElementById('customerSelect');
    const selectedOption = select.options[select.selectedIndex];
    const noInfo = document.getElementById('noCustomerSelected');
    const info = document.getElementById('customerSelectedInfo');

    if (!select.value) {
        if (noInfo) noInfo.style.display = 'block';
        if (info) info.style.display = 'none';
        return;
    }

    if (noInfo) noInfo.style.display = 'none';
    if (info) info.style.display = 'block';

    const name = selectedOption.getAttribute('data-name') || '';
    const company = selectedOption.getAttribute('data-company') || '';
    const phone = selectedOption.getAttribute('data-phone') || '';
    const due = parseFloat(selectedOption.getAttribute('data-due') || 0);
    const advance = parseFloat(selectedOption.getAttribute('data-advance') || 0);

    document.getElementById('previewCustomerName').innerText = name;
    document.getElementById('previewCustomerCompany').innerText = company;
    document.getElementById('previewCustomerPhone').innerText = phone;
    document.getElementById('avatarInitial').innerText = name.charAt(0).toUpperCase();

    document.getElementById('previewCurrentDue').innerText = '৳ ' + due.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('previewCurrentAdvance').innerText = '৳ ' + advance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

    calculateProjectedBalance();
}

function calculateProjectedBalance() {
    const select = document.getElementById('customerSelect');
    if (!select || !select.value) return;

    const selectedOption = select.options[select.selectedIndex];
    const due = parseFloat(selectedOption.getAttribute('data-due') || 0);
    const advance = parseFloat(selectedOption.getAttribute('data-advance') || 0);
    const deposit = parseFloat(document.getElementById('depositAmount').value || 0);

    document.getElementById('previewDepositAmount').innerText = '৳ ' + deposit.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

    // Projected advance calculation:
    // Net balance = due - advance - deposit
    // If net < 0 => advance credit = abs(net)
    const net = due - advance - deposit;
    const projectedAdvance = net < 0 ? Math.abs(net) : 0;

    document.getElementById('previewProjectedAdvance').innerText = '৳ ' + projectedAdvance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('customerSelect') && document.getElementById('customerSelect').value) {
        updateCustomerSnapshot();
    }
    const accSelect = document.getElementById('accountSelector');
    if (accSelect) handleAccountChange(accSelect);
});
</script>
@endpush
