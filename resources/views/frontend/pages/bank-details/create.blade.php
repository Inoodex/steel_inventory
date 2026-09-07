@extends('frontend.layouts.app')

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">{{ isset($bankDetail) ? 'Edit' : 'Add' }} Bank / MFS Account</h4>
                <p class="text-muted small mb-0">Configure company bank/mfs account information, branch, and routing number</p>
            </div>
            <div>
                <a href="{{ route('bank-details.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3 shadow-sm">
                    <i class="fa fa-arrow-left me-2"></i>Back to Bank/MFS Accounts
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            <!-- GL Integration Note Banner -->
            <div class="alert alert-soft-primary border-0 rounded-3 mb-4 d-flex align-items-center gap-3 py-3 px-4">
                <div class="avatar avatar-sm bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow-xs flex-shrink-0">
                    <i class="fe fe-link fs-6"></i>
                </div>
                <div>
                    <span class="fw-bold text-dark d-block">Automatic General Ledger (COA) Integration</span>
                    <span class="text-muted small">Saving this account will automatically create a sub-ledger under Asset <strong>[1120] Bank & Mobile Banking Accounts</strong> in the Chart of Accounts. Its opening balance will synchronize immediately with the balance sheet.</span>
                </div>
            </div>

            <form action="{{ isset($bankDetail) ? route('bank-details.update', $bankDetail->id) : route('bank-details.store') }}" method="POST">
                @csrf
                @if (isset($bankDetail))
                    @method('PUT')
                @endif

                <div class="row g-3 mb-4">
                    <div class="col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Account Name <span class="text-danger">*</span></label>
                        <input type="text" name="account_name" class="form-control border-light-subtle" value="{{ old('account_name', $bankDetail->account_name ?? '') }}" required>
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Bank / Provider Name <span class="text-danger">*</span></label>
                        <input type="text" name="bank_name" class="form-control border-light-subtle" value="{{ old('bank_name', $bankDetail->bank_name ?? '') }}" required>
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Branch / Location <span class="text-muted">(Optional for MFS)</span></label>
                        <input type="text" name="branch" class="form-control border-light-subtle" value="{{ old('branch', $bankDetail->branch ?? '') }}">
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Account / Wallet Number <span class="text-danger">*</span></label>
                        <input type="text" name="account_number" class="form-control border-light-subtle font-monospace" value="{{ old('account_number', $bankDetail->account_number ?? '') }}" required>
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Account Type <span class="text-danger">*</span></label>
                        <select name="account_type" class="form-select border-light-subtle" required>
                            <option value="">Select Account Type</option>
                            <option value="Bank" {{ old('account_type', $bankDetail->account_type ?? '') == 'Bank' ? 'selected' : '' }}>Bank Account</option>
                            <option value="MFS - bKash" {{ old('account_type', $bankDetail->account_type ?? '') == 'MFS - bKash' ? 'selected' : '' }}>MFS - bKash </option>
                            <option value="MFS - Nagad" {{ old('account_type', $bankDetail->account_type ?? '') == 'MFS - Nagad' ? 'selected' : '' }}>MFS - Nagad</option>
                            <option value="MFS - Rocket / Other" {{ old('account_type', $bankDetail->account_type ?? '') == 'MFS - Rocket / Other' ? 'selected' : '' }}>MFS - Rocket</option>                        
                        </select>
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Opening Balance (৳) <span class="text-muted">(Initial Ledger Balance)</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-light-subtle text-muted">৳</span>
                            <input type="number" step="0.01" min="0" name="opening_balance" class="form-control border-light-subtle fw-semibold text-dark" value="{{ old('opening_balance', '0.00') }}">
                        </div>
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Routing Number <span class="text-muted">(Optional)</span></label>
                        <input type="text" name="routing_number" class="form-control border-light-subtle font-monospace" value="{{ old('routing_number', '') }}">
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">SWIFT / BIC Code <span class="text-muted">(Optional for International)</span></label>
                        <input type="text" name="swift_code" class="form-control border-light-subtle font-monospace" value="{{ old('swift_code', '') }}">
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Account Notes / Remarks</label>
                        <input type="text" name="notes" class="form-control border-light-subtle" value="{{ old('notes', '') }}">
                    </div>

                    <div class="col-12 mt-4">
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input type="checkbox" name="is_default" value="1" {{ old('is_default', $bankDetail->is_default ?? false) ? 'checked' : '' }} class="form-check-input" id="is_default">
                                <label class="form-check-label text-dark fw-semibold" for="is_default">Set as Default Billing Account</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $bankDetail->is_active ?? true) ? 'checked' : '' }} class="form-check-input" id="is_active">
                                <label class="form-check-label text-dark fw-semibold" for="is_active">Active Status</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    <a href="{{ route('bank-details.index') }}" class="btn btn-outline-secondary px-4 rounded-3">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 rounded-3">
                        {{ isset($bankDetail) ? 'Update Account' : 'Save Account' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
