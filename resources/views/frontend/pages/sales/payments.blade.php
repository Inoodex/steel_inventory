@extends('frontend.layouts.app')

@push('styles')
<style>
    .kpi-card {
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.06) !important;
    }
    .table-custom tbody tr {
        transition: background-color 0.15s ease;
    }
    .table-custom tbody tr:hover {
        background-color: #fcfbff !important;
    }
    .table-responsive {
        overflow: visible !important;
    }
    .dropdown-menu {
        z-index: 1060 !important;
    }
    .table-custom th, .table-custom td {
        white-space: nowrap;
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
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">
                    @if (isset($sale))
                        Collect Due Payment - Order #{{ $sale->order_no }}
                    @else
                        Sales Payments History
                    @endif
                </h4>
                <p class="text-muted small mb-0">
                    @if (isset($sale))
                        Customer: <strong class="text-dark">{{ $sale->customer->name ?? 'N/A' }}</strong> ({{ $sale->customer->phone ?? 'N/A' }})
                    @else
                        Overview of customer payments received for sales orders
                    @endif
                </p>
            </div>
            <div>
                <a href="{{ route('due-payments.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3 shadow-sm">
                    <i class="fa fa-arrow-left me-2"></i>Back to Due Payments
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    @if (isset($sale))
        <!-- Section 1: Financial Overview KPI Bar -->
        <div class="row g-3 mb-4">
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card kpi-card bg-white shadow-sm rounded-3 h-100 mb-0">
                    <div class="card-body p-3">
                        <span class="text-muted small d-block mb-1">Total Bill</span>
                        <h5 class="fw-bold text-dark mb-0">৳{{ number_format($sale->bill, 2) }}</h5>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-6">
                <div class="card kpi-card bg-white shadow-sm rounded-3 h-100 mb-0">
                    <div class="card-body p-3">
                        <span class="text-muted small d-block mb-1">Discount</span>
                        <h5 class="fw-bold text-info mb-0">৳{{ number_format($sale->discount, 2) }}</h5>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-4 col-6">
                <div class="card kpi-card bg-white shadow-sm rounded-3 h-100 mb-0">
                    <div class="card-body p-3">
                        <span class="text-muted small d-block mb-1">Payable Amount</span>
                        <h5 class="fw-bold text-primary mb-0">৳{{ number_format($sale->payble, 2) }}</h5>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 col-6">
                <div class="card kpi-card bg-white shadow-sm rounded-3 h-100 mb-0">
                    <div class="card-body p-3">
                        <span class="text-muted small d-block mb-1">Paid Amount</span>
                        <h5 class="fw-bold text-success mb-0">৳{{ number_format($sale->advanced_payment, 2) }}</h5>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-6 col-12">
                <div class="card kpi-card bg-white shadow-sm rounded-3 h-100 mb-0">
                    <div class="card-body p-3">
                        <span class="text-muted small d-block mb-1">Outstanding Due</span>
                        <h5 class="fw-bold text-danger mb-0">৳{{ number_format($sale->due_payment, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Process Payment Form -->
        @if ($sale->due_payment > 0)
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3"><i class="fe fe-credit-card me-2 text-primary"></i>Receive Payment Entry</h6>

                    <form action="{{ route('sales.process-payment') }}" method="POST">
                        @csrf
                        <input type="hidden" name="sale_id" value="{{ $sale->id }}">

                        <div class="row g-3">
                            <div class="col-lg-4 col-md-6 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">Payment Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control border-light-subtle" name="payment_amount" id="payment_amount" max="{{ $sale->due_payment }}" min="0.01" value="{{ $sale->due_payment }}" required oninput="updateRemaining(this.value)">
                            </div>

                            @php
                                $regularSaleBanks = ($bankAccounts ?? collect())->filter(function($b) {
                                    $type = $b->account_type ?? '';
                                    $name = $b->bank_name ?? '';
                                    return !(stripos($type, 'MFS') !== false || preg_match('/(bkash|nagad|rocket|upay|mfs)/i', $name . ' ' . $type));
                                });
                                $mfsSaleBanks = ($bankAccounts ?? collect())->filter(function($b) {
                                    $type = $b->account_type ?? '';
                                    $name = $b->bank_name ?? '';
                                    return (stripos($type, 'MFS') !== false || preg_match('/(bkash|nagad|rocket|upay|mfs)/i', $name . ' ' . $type));
                                });
                            @endphp

                            <div class="col-lg-4 col-md-6 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">Receiving Account / Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select border-light-subtle" id="salesPaymentAccountSelector" onchange="handleSalesPaymentAccountChange(this)" required>
                                    <optgroup label="Cash">
                                        <option value="cash" data-method="cash">Hand Cash</option>
                                    </optgroup>
                                    @if($regularSaleBanks->count() > 0)
                                        <optgroup label="Bank Accounts">
                                            @foreach($regularSaleBanks as $bank)
                                                <option value="bank_{{ $bank->id }}" data-method="bank" data-id="{{ $bank->id }}" {{ $bank->is_default ? 'selected' : '' }}>
                                                    {{ $bank->bank_name }} — {{ $bank->account_number }} ({{ $bank->account_name }})
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    @if($mfsSaleBanks->count() > 0)
                                        <optgroup label="Mobile Banking / MFS">
                                            @foreach($mfsSaleBanks as $bank)
                                                <option value="mfs_{{ $bank->id }}" data-method="mobile_banking" data-id="{{ $bank->id }}" {{ (!isset($regularSaleBanks) || $regularSaleBanks->isEmpty()) && $loop->first ? 'selected' : '' }}>
                                                    {{ $bank->bank_name }} — {{ $bank->account_number }} ({{ $bank->account_name }})
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                                <input type="hidden" name="payment_method" id="hidden_sales_payment_method" value="bank">
                                <input type="hidden" name="bank_detail_id" id="hidden_sales_payment_bank_id" value="">
                            </div>

                            <div class="col-lg-4 col-md-12 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control border-light-subtle" name="payment_date" value="{{ now()->format('Y-m-d') }}" required>
                            </div>

                            <div class="col-lg-8 col-md-7 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">Payment Notes (Optional)</label>
                                <input type="text" class="form-control border-light-subtle" name="notes" autocomplete="off">
                            </div>

                            <div class="col-lg-4 col-md-5 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">Remaining Due After Payment</label>
                                <div class="p-2 bg-light border rounded-3 text-center">
                                    <h5 class="fw-bold mb-0 text-primary" id="remainingAmount">৳0.00</h5>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-4 mt-3 border-top">
                            <a href="{{ route('due-payments.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 py-2 rounded-3">Process Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        @else
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-5 text-center">
                    <div class="avatar avatar-xl bg-success-light text-success rounded-circle mb-3 mx-auto d-flex align-items-center justify-content-center">
                        <i class="fe fe-check-circle fs-1"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Order Fully Paid</h5>
                    <p class="text-muted small mb-0">This sale order has no remaining due balance.</p>
                </div>
            </div>
        @endif
    @endif

    <!-- Section 3: Payment Collections History Table -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0">
                <i class="fe fe-list text-primary me-2"></i>Payment Collections &amp; Receipts History
            </h5>
            <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill fs-7">
                {{ count($payments ?? []) }} Records Found
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4" style="width: 5%;">#</th>
                            <th style="width: 15%;">Date</th>
                            <th style="width: 25%;">Method &amp; Channel</th>
                            <th style="width: 20%;">Transaction Ref / TrxID</th>
                            <th style="width: 15%;">Amount (৳)</th>
                            <th style="width: 15%;">Remarks</th>
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
                                    <span class="badge bg-light text-dark border me-1">
                                        @if(in_array($pay->payment_method, ['bank', '2']))
                                            🏦
                                        @elseif(in_array($pay->payment_method, ['mobile_banking', '3']))
                                            📱
                                        @elseif($pay->payment_method === 'cheque')
                                            📜
                                        @else
                                            💵
                                        @endif
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
                                    <strong class="text-success fs-6">৳ {{ number_format($pay->amount, 2) }}</strong>
                                </td>
                                <td class="text-muted small">
                                    {{ $pay->remarks ?: '—' }}
                                </td>
                                <td class="text-center pe-3">
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
                                                    ]) }})">
                                                    <i class="fe fe-edit text-primary"></i>
                                                    <span>Edit Payment</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" href="javascript:void(0)"
                                                    onclick="if (confirm('Are you sure you want to revert this payment of ৳{{ number_format($pay->amount, 2) }}? This will restore the sale due balance.')) { document.getElementById('delCustPayment{{ $pay->id }}').submit(); }">
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
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fe fe-inbox fs-2 d-block mb-1"></i>No payment receipts recorded for this sale order yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Edit Customer Payment Modal (Placed Outside Tables) -->
<div class="modal fade" id="editCustomerPaymentModal" tabindex="-1" aria-labelledby="editCustomerPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="editCustomerPaymentModalLabel">
                    <i class="fe fe-edit me-2 text-primary"></i>Edit Customer Due Payment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCustomerPaymentForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small rounded-3 mb-3 d-flex align-items-center gap-2">
                        <i class="fe fe-info fs-6"></i>
                        <span>Adjusting this payment will automatically recalculate the sale due balance, update the receiving account, and synchronize journal vouchers.</span>
                    </div>

                    <!-- Payment Account / Method Selector -->
                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold mb-1">Receiving Account / Channel <span class="text-danger">*</span></label>
                        <select class="form-select border-light-subtle" id="editCustModalAccountSelector" onchange="handleEditCustModalAccountChange(this)" required>
                            <optgroup label="Cash">
                                <option value="cash" data-method="cash">Hand Cash (Cash in Hand)</option>
                            </optgroup>
                            @if(isset($regularSaleBanks) && $regularSaleBanks->count() > 0)
                                <optgroup label="Bank Accounts">
                                    @foreach($regularSaleBanks as $bank)
                                        <option value="bank_{{ $bank->id }}" data-method="bank" data-id="{{ $bank->id }}">
                                            {{ $bank->bank_name }} — {{ $bank->account_number }} ({{ $bank->account_name }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if(isset($mfsSaleBanks) && $mfsSaleBanks->count() > 0)
                                <optgroup label="Mobile Banking / MFS">
                                    @foreach($mfsSaleBanks as $bank)
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

                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-outline-secondary px-3 py-2 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold text-white">
                        <i class="fe fe-check me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
@if (isset($sale) && $sale->due_payment > 0)
    function updateRemaining(amount) {
        const dueAmount = {{ $sale->due_payment }};
        const paymentAmount = parseFloat(amount) || 0;
        const remaining = dueAmount - paymentAmount;

        const remainingElement = document.getElementById('remainingAmount');
        if (remainingElement) {
            remainingElement.textContent = '৳' + Math.max(0, remaining).toFixed(2);
            if (remaining <= 0) {
                remainingElement.className = 'fw-bold mb-0 text-success';
            } else {
                remainingElement.className = 'fw-bold mb-0 text-danger';
            }
        }
    }

    function handleSalesPaymentAccountChange(select) {
        if (!select) return;
        const selectedOption = select.options[select.selectedIndex];
        const method = selectedOption ? (selectedOption.getAttribute('data-method') || 'bank') : 'bank';
        const bankId = selectedOption ? (selectedOption.getAttribute('data-id') || '') : '';

        const hiddenMethod = document.getElementById('hidden_sales_payment_method');
        const hiddenBank = document.getElementById('hidden_sales_payment_bank_id');

        if (hiddenMethod) hiddenMethod.value = method;
        if (hiddenBank) hiddenBank.value = bankId;
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateRemaining({{ $sale->due_payment }});
        const accSelect = document.getElementById('salesPaymentAccountSelector');
        if (accSelect) handleSalesPaymentAccountChange(accSelect);
    });
@endif

function openEditCustomerPaymentModal(data) {
    const form = document.getElementById('editCustomerPaymentForm');
    form.action = '/payments/' + data.id;

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
</script>
@endpush
