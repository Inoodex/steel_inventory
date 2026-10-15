@extends('frontend.layouts.app')

@push('styles')
<style>
    .transfer-box {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        padding: 20px;
    }
    .account-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px;
        transition: all 0.2s ease;
    }
    .account-card:hover {
        border-color: #7638ff;
    }
    .fs-8 {
        font-size: 0.8rem;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header (No Breadcrumbs) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">New Balance / Fund Transfer</h4>
                <p class="text-muted small mb-0">Record internal money movement between cash and bank accounts with automatic double-entry journal posting</p>
            </div>
            <div>
                <a href="{{ route('balance-transfers.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back to Transfers</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <div class="row justify-content-center">
        <div class="col-lg-12 col-12">
            
            <form action="{{ route('balance-transfers.store') }}" method="POST" id="balanceTransferForm">
                @csrf

                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark"><i class="fe fe-repeat text-primary me-2"></i>Transfer Configuration</span>
                        <span class="badge bg-light text-secondary border font-monospace">{{ $suggestedNo }}</span>
                    </div>

                    <div class="card-body p-4">

                        <!-- Transfer Date & Reference Row -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6 col-12">
                                <label for="transfer_date" class="form-label small fw-semibold text-secondary mb-1">
                                    Transfer Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="transfer_date" id="transfer_date" class="form-control" value="{{ old('transfer_date', $today) }}" required>
                            </div>

                            <div class="col-md-6 col-12">
                                <label for="reference_no" class="form-label small fw-semibold text-secondary mb-1">
                                    Reference # / TrxID / Cheque # <span class="text-muted fw-normal">(Optional)</span>
                                </label>
                                <input type="text" name="reference_no" id="reference_no" class="form-control" placeholder="e.g. Cheque #10293 or TrxID" value="{{ old('reference_no') }}">
                            </div>
                        </div>

                        <!-- Account Selection Row with Visual Flow -->
                        <div class="transfer-box mb-4">
                            <div class="row g-3 align-items-center">
                                
                                <!-- From Account (Source) -->
                                <div class="col-md-5 col-12">
                                    <div class="account-card">
                                        <label for="from_account_id" class="form-label small fw-bold text-danger mb-1 d-flex justify-content-between align-items-center">
                                            <span><i class="fe fe-arrow-up-right me-1"></i>From (Source Account)</span>
                                            <span id="fromBalanceBadge" class="badge bg-light text-muted border fs-8">Bal: ৳0.00</span>
                                        </label>
                                        <select name="from_account_id" id="from_account_id" class="form-select select2" required onchange="updateAccountBalances()">
                                            <option value="">Select Source Account</option>
                                            @foreach($accounts as $acc)
                                                <option value="{{ $acc->id }}" data-balance="{{ $acc->running_balance }}" {{ old('from_account_id') == $acc->id ? 'selected' : '' }}>
                                                    {{ $acc->account_name }} ({{ $acc->account_code }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-block mt-1 fs-8">Funds will be credited (deducted)</small>
                                    </div>
                                </div>

                                <!-- Transfer Arrow Indicator -->
                                <div class="col-md-2 col-12 text-center py-2">
                                    <div class="avatar avatar-md bg-primary-light text-primary rounded-circle mx-auto d-flex align-items-center justify-content-center shadow-sm">
                                        <i class="fe fe-arrow-right fs-5 d-none d-md-block"></i>
                                        <i class="fe fe-arrow-down fs-5 d-md-none"></i>
                                    </div>
                                </div>

                                <!-- To Account (Destination) -->
                                <div class="col-md-5 col-12">
                                    <div class="account-card">
                                        <label for="to_account_id" class="form-label small fw-bold text-success mb-1 d-flex justify-content-between align-items-center">
                                            <span><i class="fe fe-arrow-down-left me-1"></i>To (Destination Account)</span>
                                            <span id="toBalanceBadge" class="badge bg-light text-muted border fs-8">Bal: ৳0.00</span>
                                        </label>
                                        <select name="to_account_id" id="to_account_id" class="form-select select2" required onchange="updateAccountBalances()">
                                            <option value="">Select Destination Account</option>
                                            @foreach($accounts as $acc)
                                                <option value="{{ $acc->id }}" data-balance="{{ $acc->running_balance }}" {{ old('to_account_id') == $acc->id ? 'selected' : '' }}>
                                                    {{ $acc->account_name }} ({{ $acc->account_code }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-block mt-1 fs-8">Funds will be debited (added)</small>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Amount & Remarks -->
                        <div class="mb-4">
                            <label for="amount" class="form-label small fw-bold text-dark mb-1">
                                Transfer Amount (৳) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-success fw-bold fs-5">৳</span>
                                <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control form-control-lg fw-bold text-dark font-monospace" placeholder="0.00" value="{{ old('amount') }}" required oninput="checkInsufficientBalance()">
                            </div>
                            <div id="insufficientBalanceAlert" class="alert alert-danger py-2 px-3 mt-2 mb-0 d-none align-items-center gap-2 fs-8 rounded-3">
                                <i class="fe fe-alert-circle fs-6 flex-shrink-0"></i>
                                <span id="insufficientBalanceText">Insufficient funds. Amount exceeds available source balance.</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label small fw-semibold text-secondary mb-1">
                                Transfer Purpose / Description <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="e.g. Cash withdrawal from bank for weekly office expenses...">{{ old('notes') }}</textarea>
                        </div>

                    </div>

                    <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                        <a href="{{ route('balance-transfers.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3">Cancel</a>
                        <button type="submit" id="btnSubmitTransfer" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold d-inline-flex align-items-center gap-2">
                            <span>Execute Transfer</span>
                        </button>
                    </div>

                </div>

            </form>

        </div>
    </div>

</div>

@push('scripts')
<script>
    function updateAccountBalances() {
        var fromSelect = document.getElementById('from_account_id');
        var toSelect = document.getElementById('to_account_id');
        var fromBadge = document.getElementById('fromBalanceBadge');
        var toBadge = document.getElementById('toBalanceBadge');

        var fromOption = fromSelect.options[fromSelect.selectedIndex];
        if (fromOption && fromOption.value) {
            var fromBal = parseFloat(fromOption.getAttribute('data-balance') || 0);
            fromBadge.textContent = 'Bal: ৳' + fromBal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            fromBadge.className = 'badge border fs-8 ' + (fromBal >= 0 ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle');
        } else {
            fromBadge.textContent = 'Bal: ৳0.00';
            fromBadge.className = 'badge bg-light text-muted border fs-8';
        }

        var toOption = toSelect.options[toSelect.selectedIndex];
        if (toOption && toOption.value) {
            var toBal = parseFloat(toOption.getAttribute('data-balance') || 0);
            toBadge.textContent = 'Bal: ৳' + toBal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            toBadge.className = 'badge border fs-8 ' + (toBal >= 0 ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle');
        } else {
            toBadge.textContent = 'Bal: ৳0.00';
            toBadge.className = 'badge bg-light text-muted border fs-8';
        }

        checkInsufficientBalance();
    }

    function checkInsufficientBalance() {
        var fromSelect = document.getElementById('from_account_id');
        var fromOption = fromSelect.options[fromSelect.selectedIndex];
        var fromBal = fromOption && fromOption.value ? parseFloat(fromOption.getAttribute('data-balance') || 0) : null;
        var amtInput = document.getElementById('amount');
        var amt = parseFloat(amtInput.value || 0);
        var alertEl = document.getElementById('insufficientBalanceAlert');
        var alertText = document.getElementById('insufficientBalanceText');
        var submitBtn = document.getElementById('btnSubmitTransfer');

        if (fromOption && fromOption.value && fromBal !== null) {
            if (amt > fromBal) {
                alertEl.classList.remove('d-none');
                alertEl.classList.add('d-flex');
                alertText.innerHTML = '<strong>Insufficient Funds:</strong> ' + fromOption.text.trim() + ' has only <strong>৳' + fromBal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</strong> available. You cannot transfer <strong>৳' + amt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</strong>.';
                amtInput.classList.add('is-invalid');
                if (submitBtn) submitBtn.disabled = true;
                return false;
            }
        }

        alertEl.classList.add('d-none');
        alertEl.classList.remove('d-flex');
        amtInput.classList.remove('is-invalid');
        if (submitBtn) submitBtn.disabled = false;
        return true;
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateAccountBalances();

        document.getElementById('balanceTransferForm').addEventListener('submit', function (e) {
            var from = document.getElementById('from_account_id').value;
            var to = document.getElementById('to_account_id').value;
            var amt = parseFloat(document.getElementById('amount').value || 0);

            if (from === to && from !== '') {
                e.preventDefault();
                alert('Source account and Destination account cannot be the same. Please choose two different accounts.');
                return false;
            }

            if (amt <= 0) {
                e.preventDefault();
                alert('Please enter a valid transfer amount greater than zero.');
                return false;
            }

            if (!checkInsufficientBalance()) {
                e.preventDefault();
                return false;
            }
        });
    });
</script>
@endpush
@endsection
