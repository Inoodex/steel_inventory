@extends('frontend.layouts.app')
@section('content')
    @if (session('taDaAdvance') !== null && session('taDaClaim') !== null)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Auto-calculate with session data
                calculateNetSalary({{ session('taDaAdvance') }}, {{ session('taDaClaim') }});

                // Auto-select the previous selections
                @if (session('selectedEmployee'))
                    document.getElementById('employeeSelect').value = '{{ session('selectedEmployee') }}';
                @endif

                @if (session('selectedMonth'))
                    document.getElementById('monthSelect').value = '{{ session('selectedMonth') }}';
                @endif
            });
        </script>
    @endif
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Process Employee Salary</h4>
                <p class="text-muted small mb-0">Calculate net salary, allowances, deductions, and payment status</p>
            </div>
            <div>
                <a href="{{ route('salary.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3 shadow-sm">
                    <i class="fa fa-arrow-left me-2"></i>Back to Salary List
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('salary.store') }}" id="salaryForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Month <span class="text-danger">*</span></label>
                            <select name="month" class="form-select border-light-subtle select2" id="monthSelect" required data-placeholder="Select Month">
                                <option value="">Select Month</option>
                                @php
                                    $months = [
                                        '01' => 'January',
                                        '02' => 'February',
                                        '03' => 'March',
                                        '04' => 'April',
                                        '05' => 'May',
                                        '06' => 'June',
                                        '07' => 'July',
                                        '08' => 'August',
                                        '09' => 'September',
                                        '10' => 'October',
                                        '11' => 'November',
                                        '12' => 'December',
                                    ];
                                    $currentMonth = date('m');
                                    $currentYear = date('Y');
                                @endphp
                                @foreach ($months as $key => $month)
                                    <option value="{{ $currentYear }}-{{ $key }}"
                                        {{ $key == $currentMonth ? 'selected' : '' }}>
                                        {{ $month }} {{ $currentYear }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select border-light-subtle select2" id="employeeSelect" required data-placeholder="Select Employee">
                                <option value="">Select Employee</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}" data-salary="{{ $emp->salary }}">
                                        {{ $emp->name }} ({{ $emp->employee_id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Basic Salary</label>
                            <input type="number" id="basicSalary" name="basic_salary" class="form-control border-light-subtle bg-light" readonly>
                        </div>

                        <div class="col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Advance (Salary)</label>
                            <input type="number" id="advanceInput" name="advance" class="form-control border-light-subtle bg-light" readonly>
                        </div>

                        <div class="col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Allowance (TA/DA)</label>
                            <input type="number" name="allowance" class="form-control border-light-subtle bg-light" id="allowanceInput" readonly>
                        </div>

                        <div class="col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Deduction</label>
                            <input type="number" name="deduction" class="form-control border-light-subtle" id="deductionInput" value="0">
                        </div>

                        <div class="col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Net Salary</label>
                            <input type="number" name="net_salary" class="form-control border-light-subtle fw-bold bg-light" id="netSalary" readonly>
                        </div>

                        <div class="col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Payment Status</label>
                            <select name="payment_status" class="form-select border-light-subtle select2" data-placeholder="Select Status">
                                <option value="paid">Paid</option>
                                <option value="unpaid">Unpaid</option>
                            </select>
                        </div>

                        <div class="col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control border-light-subtle" value="{{ date('Y-m-d') }}">
                        </div>

                        <div class="col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Notes</label>
                            <textarea name="note" class="form-control border-light-subtle" rows="2" placeholder="Optional notes..."></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-3">
                            <a href="{{ route('salary.index') }}" class="btn btn-outline-secondary px-4 rounded-3">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 rounded-3">Save Salary</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            width: '100%'
        });

        const $employeeSelect = $('#employeeSelect');
        const $monthSelect = $('#monthSelect');
        const $basicSalaryInput = $('#basicSalary');
        const $deductionInput = $('#deductionInput');

        // Handle Employee Change (supports both native and Select2 events)
        $employeeSelect.on('change select2:select', function() {
            const selectedOpt = $(this).find('option:selected')[0];
            const salary = selectedOpt ? selectedOpt.dataset.salary : '';
            $basicSalaryInput.val(salary || '');

            const employeeId = $(this).val();
            const month = $monthSelect.val();

            if (!employeeId) {
                $('#advanceInput').val(0);
                resetCalculations();
                return;
            }

            fetchAdvanceByMonth(employeeId, month);
        });

        // Handle Month Change
        $monthSelect.on('change select2:select', function() {
            const employeeId = $employeeSelect.val();
            const month = $(this).val();

            if (employeeId && month) {
                fetchAdvanceByMonth(employeeId, month);
                fetchTaDaDataViaAjax(employeeId, month);
            }
        });

        // Deduction input event
        $deductionInput.on('input', function() {
            calculateNetSalaryWithCurrentData();
        });

        // Trigger initial calculation if employee is already selected (e.g. from session)
        if ($employeeSelect.val()) {
            $employeeSelect.trigger('change');
        }
    });

    // Fetch advance from daily_expenses table with month filter
    function fetchAdvanceByMonth(employeeId, month) {
        if (!employeeId || !month) {
            $('#advanceInput').val(0);
            return;
        }

        fetch(`/employee/${employeeId}/advance-sum-by-month?month=${month}`)
            .then(response => response.json())
            .then(data => {
                $('#advanceInput').val(data.sum || 0);
                // Also fetch TA/DA data
                fetchTaDaDataViaAjax(employeeId, month);
            })
            .catch(err => {
                console.error(err);
                $('#advanceInput').val(0);
                calculateNetSalary(0, 0);
            });
    }

    // Fetch TA/DA data
    function fetchTaDaDataViaAjax(employeeId, month) {
        $('#allowanceInput').val('Loading...');

        fetch('/salary/get-tada-data-ajax', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                employee_id: employeeId,
                month: month
            })
        })
        .then(response => response.json())
        .then(data => {
            calculateNetSalary(data.total_advance || 0, data.total_claim || 0);
        })
        .catch(error => {
            console.error('Error:', error);
            $('#allowanceInput').val(0);
            calculateNetSalary(0, 0);
        });
    }

    function calculateNetSalary(taDaAdvance = 0, taDaClaim = 0) {
        const basicSalary = parseFloat($('#basicSalary').val()) || 0;
        const salaryAdvance = parseFloat($('#advanceInput').val()) || 0;
        const deduction = parseFloat($('#deductionInput').val()) || 0;

        let netSalary = basicSalary - salaryAdvance - deduction;
        let allowance = 0;

        if (taDaAdvance > 0) {
            netSalary -= taDaAdvance;
            allowance -= taDaAdvance;
        }

        if (taDaClaim > 0) {
            netSalary += taDaClaim;
            allowance += taDaClaim;
        }

        $('#allowanceInput').val(allowance);
        $('#netSalary').val(Math.max(0, netSalary).toFixed(2));
    }

    function calculateNetSalaryWithCurrentData() {
        const basicSalary = parseFloat($('#basicSalary').val()) || 0;
        const salaryAdvance = parseFloat($('#advanceInput').val()) || 0;
        const allowance = parseFloat($('#allowanceInput').val()) || 0;
        const deduction = parseFloat($('#deductionInput').val()) || 0;

        const netSalary = basicSalary - salaryAdvance + allowance - deduction;
        $('#netSalary').val(Math.max(0, netSalary).toFixed(2));
    }

    function resetCalculations() {
        $('#allowanceInput').val(0);
        $('#netSalary').val(0);
        $('#deductionInput').val(0);
    }
</script>
@endpush
