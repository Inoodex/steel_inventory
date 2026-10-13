@extends('frontend.layouts.app')

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Edit Daily Expense</h4>
                <p class="text-muted small mb-0">Update expense details, amount, category, and remarks</p>
            </div>
            <div>
                <a href="{{ route('dailyExpenses.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3 shadow-sm">
                    <i class="fa fa-arrow-left me-2"></i>Back to Daily Expenses
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            <form action="{{ route('dailyExpenses.update', $expense->id) }}" method="post" id="dailyExpenseEditForm">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-4">
                    <div class="col-lg-4 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control border-light-subtle" value="{{ old('date', $expense->date) }}" required>
                    </div>

                    <div class="col-lg-4 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Employee</label>
                        <select id="employeeSelect" class="form-select border-light-subtle select2" name="employee_id" data-placeholder="Select Employee">
                            <option value="">Select Employee</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}" {{ old('employee_id', $expense->employee_id) == $emp->id ? 'selected' : '' }} data-basic_salary="{{ $emp->basic_salary }}">
                                    {{ $emp->name }} ({{ $emp->employee_id }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-4 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Expense Category <span class="text-danger">*</span></label>
                        <select name="expense_category_id" id="categorySelect" class="form-select border-light-subtle select2" required data-placeholder="Select Category">
                            <option value="">Select Category</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('expense_category_id', $expense->expense_category_id) == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-4 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" class="form-control border-light-subtle" value="{{ old('amount', $expense->amount) }}" required>
                    </div>

                    @php
                        $regularBanks = ($bankDetails ?? collect())->filter(function($b) {
                            $type = $b->account_type ?? '';
                            $name = $b->bank_name ?? '';
                            return !(stripos($type, 'MFS') !== false || preg_match('/(bkash|nagad|rocket|upay|mfs)/i', $name . ' ' . $type));
                        });
                        $mfsBanks = ($bankDetails ?? collect())->filter(function($b) {
                            $type = $b->account_type ?? '';
                            $name = $b->bank_name ?? '';
                            return (stripos($type, 'MFS') !== false || preg_match('/(bkash|nagad|rocket|upay|mfs)/i', $name . ' ' . $type));
                        });
                        $currMethod = old('spend_method', $expense->spend_method ?? 'cash');
                        $currBankId = old('bank_detail_id', $expense->bank_detail_id ?? '');
                    @endphp

                    <div class="col-lg-4 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">
                            <i class="fe fe-credit-card me-1 text-primary"></i> Paid From Account / Method <span class="text-danger">*</span>
                        </label>
                        <select id="expenseAccountSelector" class="form-select border-light-subtle" onchange="handleExpenseAccountChange(this)" required>
                            @if($regularBanks->count() > 0)
                                <optgroup label="Bank Accounts">
                                    @foreach($regularBanks as $bank)
                                        <option value="bank_{{ $bank->id }}" data-method="bank" data-id="{{ $bank->id }}" {{ ($currMethod === 'bank' && $currBankId == $bank->id) ? 'selected' : '' }}>
                                            {{ $bank->bank_name }} — {{ $bank->account_number }} ({{ $bank->account_name }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if($mfsBanks->count() > 0)
                                <optgroup label="Mobile Banking / MFS">
                                    @foreach($mfsBanks as $bank)
                                        <option value="mfs_{{ $bank->id }}" data-method="mobile_banking" data-id="{{ $bank->id }}" {{ ($currMethod === 'mobile_banking' && $currBankId == $bank->id) ? 'selected' : '' }}>
                                            {{ $bank->bank_name }} — {{ $bank->account_number }} ({{ $bank->account_name }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        <input type="hidden" name="spend_method" id="hidden_expense_spend_method" value="{{ $currMethod }}">
                        <input type="hidden" name="bank_detail_id" id="hidden_expense_bank_id" value="{{ $currBankId }}">
                    </div>

                    <div class="col-lg-4 col-md-12 col-12" id="remarksWrapper">
                        <label class="form-label small text-secondary fw-semibold mb-1">Remarks</label>
                        <textarea name="remarks" class="form-control border-light-subtle" rows="2">{{ old('remarks', $expense->remarks) }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    <a href="{{ route('dailyExpenses.index') }}" class="btn btn-outline-secondary px-4 rounded-3">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 rounded-3">Update Daily Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function handleExpenseAccountChange(select) {
        const selectedOption = select.options[select.selectedIndex];
        const method = selectedOption.getAttribute('data-method') || 'cash';
        const bankId = selectedOption.getAttribute('data-id') || '';

        const hiddenMethod = document.getElementById('hidden_expense_spend_method');
        const hiddenBank = document.getElementById('hidden_expense_bank_id');

        if (hiddenMethod) hiddenMethod.value = method;
        if (hiddenBank) hiddenBank.value = bankId;
    }

    $(document).ready(function() {
        $('.select2').select2({
            width: '100%'
        });

        const selector = document.getElementById('expenseAccountSelector');
        if (selector) {
            handleExpenseAccountChange(selector);
        }
    });
</script>
@endpush
