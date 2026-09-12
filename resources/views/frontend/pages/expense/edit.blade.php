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

                    <div class="col-lg-4 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Spend Method <span class="text-danger">*</span></label>
                        <select name="spend_method" id="spendMethodSelect" class="form-select border-light-subtle select2" required data-placeholder="Select Spend Method" onchange="toggleBankSelect(this.value)">
                            <option value="cash" {{ old('spend_method', $expense->spend_method) == 'cash' ? 'selected' : '' }}>Cash Payment</option>
                            <option value="bank" {{ old('spend_method', $expense->spend_method) == 'bank' ? 'selected' : '' }}>Bank Transfer / Deposit</option>
                            <option value="mobile_banking" {{ old('spend_method', $expense->spend_method) == 'mobile_banking' ? 'selected' : '' }}>Mobile Banking (bKash / Nagad / Rocket)</option>
                            <option value="card" {{ old('spend_method', $expense->spend_method) == 'card' ? 'selected' : '' }}>Card Payment</option>
                            <option value="other" {{ old('spend_method', $expense->spend_method) == 'other' ? 'selected' : '' }}>Other / Online</option>
                        </select>
                    </div>

                    <div class="col-lg-4 col-md-6 col-12" id="bankAccountWrapper" style="{{ old('spend_method', $expense->spend_method) === 'cash' ? 'display: none;' : '' }}">
                        <label class="form-label small text-secondary fw-semibold mb-1">
                            <i class="fe fe-layers me-1 text-primary"></i> Bank / MFS Account <span class="text-danger">*</span>
                        </label>
                        <select name="bank_detail_id" id="bankDetailSelect" class="form-select border-light-subtle select2" data-placeholder="Select Bank / MFS Account">
                            <option value="">Select Bank / MFS Account</option>
                            @foreach ($bankDetails as $bank)
                                <option value="{{ $bank->id }}" {{ old('bank_detail_id', $expense->bank_detail_id) == $bank->id ? 'selected' : '' }}>
                                    {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                </option>
                            @endforeach
                        </select>
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
    function toggleBankSelect(method) {
        const wrapper = document.getElementById('bankAccountWrapper');
        const bankSelect = document.getElementById('bankDetailSelect');
        
        if (method === 'cash') {
            wrapper.style.display = 'none';
            if (bankSelect) {
                bankSelect.removeAttribute('required');
            }
        } else {
            wrapper.style.display = 'block';
            if (bankSelect) {
                bankSelect.setAttribute('required', 'required');
            }
        }
    }

    $(document).ready(function() {
        $('.select2').select2({
            width: '100%'
        });

        $('#spendMethodSelect').on('change', function() {
            toggleBankSelect($(this).val());
        });

        toggleBankSelect($('#spendMethodSelect').val());
    });
</script>
@endpush
