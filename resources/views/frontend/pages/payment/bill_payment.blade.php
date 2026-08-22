@extends('frontend.layouts.app')
@section('content')
    <div class="content container-fluid">
        <!-- Service/Sale Info Card -->
        @if ($bill)
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body p-4">
                            <h5 class="card-title fw-bold text-dark mb-3">
                                @if ($payment_for == '1')
                                    <i class="fas fa-tools me-2 text-primary"></i>Service Payment Details
                                @else
                                    <i class="fas fa-shopping-cart me-2 text-primary"></i>Sale Order Payment (Invoice #{{ $bill->order_no ?? $bill->id }})
                                @endif
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-3 col-6">
                                    <span class="text-muted small d-block">Customer</span>
                                    <strong class="text-dark">{{ $bill->customer->name ?? ($bill->name ?? 'N/A') }}</strong>
                                </div>
                                <div class="col-md-3 col-6">
                                    <span class="text-muted small d-block">Total Bill</span>
                                    <strong class="text-dark">৳ {{ number_format($bill->payble ?? ($bill->total ?? ($bill->bill ?? 0)), 2) }}</strong>
                                </div>
                                <div class="col-md-3 col-6">
                                    <span class="text-muted small d-block">Total Paid</span>
                                    <strong class="text-success">৳ {{ number_format($bill->advanced_payment ?? ($bill->paid_amount ?? 0), 2) }}</strong>
                                </div>
                                <div class="col-md-3 col-6">
                                    <span class="text-muted small d-block">Current Due</span>
                                    <span class="badge bg-{{ ($bill->due_payment ?? ($bill->due_amount ?? 0)) > 0 ? 'danger' : 'success' }} fs-6 px-3 py-2">
                                        ৳ {{ number_format($bill->due_payment ?? ($bill->due_amount ?? 0), 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Page Header -->
        <div class="page-header d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Payment Collections & Receipts</h5>
            <button class="btn btn-primary rounded-3 px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#add-payment-modal">
                <i class="fa fa-plus-circle me-2"></i>Record New Payment
            </button>
        </div>
        <!-- /Page Header -->

        <!-- Payments Table Card -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-secondary fs-7 text-uppercase">
                                    <tr>
                                        <th class="ps-3" style="width: 5%;">#</th>
                                        <th style="width: 15%;">Date</th>
                                        <th style="width: 25%;">Payment Method & Channel</th>
                                        <th style="width: 20%;">Transaction Ref / TrxID</th>
                                        <th style="width: 15%;">Amount (৳)</th>
                                        <th style="width: 15%;">Remarks</th>
                                        <th style="width: 5%;" class="text-end pe-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($payments as $payment)
                                        @php
                                            $methodName = match($payment->payment_method) {
                                                'bank', '2' => 'Bank Transfer',
                                                'mobile_banking', '3' => 'Mobile Banking',
                                                default => 'Cash in Hand'
                                            };
                                        @endphp
                                        <tr>
                                            <td class="ps-3 text-muted">{{ $loop->iteration }}</td>
                                            <td class="fw-medium text-dark">{{ $payment->created_at->format('d M, Y h:i A') }}</td>
                                            <td>
                                                <span class="badge bg-light text-dark border me-1">
                                                    @if(in_array($payment->payment_method, ['bank', '2']))
                                                        🏦
                                                    @elseif(in_array($payment->payment_method, ['mobile_banking', '3']))
                                                        📱
                                                    @else
                                                        💵
                                                    @endif
                                                    {{ $methodName }}
                                                </span>
                                                @if($payment->bankDetail)
                                                    <small class="text-muted d-block mt-1">
                                                        <i class="fe fe-layers me-1 text-primary"></i>{{ $payment->bankDetail->bank_name }} ({{ $payment->bankDetail->account_number }})
                                                    </small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($payment->transaction_ref)
                                                    <span class="badge bg-secondary-subtle text-secondary border font-monospace">{{ $payment->transaction_ref }}</span>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong class="text-success fs-6">৳ {{ number_format($payment->amount, 2) }}</strong>
                                            </td>
                                            <td class="text-muted small">
                                                {{ $payment->remarks ?? '—' }}
                                            </td>
                                            <td class="text-end pe-3">
                                                <div class="dropdown">
                                                    <a href="javascript:void(0)" class="btn-action-icon shadow-none"
                                                        data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <ul>
                                                            <li>
                                                                <a onclick="if (confirm('Are you sure to delete this payment record? This will adjust the sale due balance.')) { document.getElementById('serviceDelete{{ $payment->id }}').submit(); }"
                                                                    class="dropdown-item text-danger" href="javascript:void(0)">
                                                                    <i class="far fa-trash-alt me-2"></i>Delete
                                                                </a>
                                                                <form id="serviceDelete{{ $payment->id }}"
                                                                    action="{{ route('delete.payment', $payment->id) }}"
                                                                    method="post" class="d-none">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <i class="fe fe-inbox fs-2 d-block mb-1"></i>No payment records found for this invoice.
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

    <!-- Add Payment Modal (Placed outside table to avoid stacking clipping) -->
    <div id="add-payment-modal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold text-dark"><i class="fe fe-credit-card me-2 text-primary"></i>Record Due Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="{{ route('add.payment') }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $id }}">
                    <input type="hidden" name="payment_for" value="{{ $payment_for }}">

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" name="payment_method" id="modalPaymentMethod" required onchange="toggleModalBankFields(this.value)">
                                <option value="cash" selected>Cash in Hand</option>
                                <option value="bank">Bank Transfer / Deposit</option>
                                <option value="mobile_banking">Mobile Banking (bKash/Nagad)</option>
                            </select>
                        </div>

                        <!-- Bank Account selector (conditional) -->
                        <div class="mb-3" id="modalBankContainer" style="display: none;">
                            <label class="form-label small fw-semibold text-secondary mb-1">Deposit Bank Account <span class="text-danger">*</span></label>
                            <select class="form-select" name="bank_detail_id" id="modalBankDetail">
                                <option value="">Select Bank Account</option>
                                @foreach($bankAccounts ?? [] as $bank)
                                    <option value="{{ $bank->id }}" {{ $bank->is_default ? 'selected' : '' }}>
                                        {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Transaction Reference -->
                        <div class="mb-3" id="modalRefContainer" style="display: none;">
                            <label class="form-label small fw-semibold text-secondary mb-1">Transaction Ref / TrxID</label>
                            <input class="form-control" type="text" name="transaction_ref" placeholder="e.g. Bank Trx # or Deposit Slip Ref">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Payment Amount (৳) <span class="text-danger">*</span></label>
                            <input class="form-control fw-bold text-success fs-5" type="number" step="0.01" min="0.01" name="amount" placeholder="0.00" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Payment Remarks</label>
                            <textarea class="form-control" name="remarks" rows="2" placeholder="Optional payment note..."></textarea>
                        </div>
                    </div>

                    <div class="modal-footer border-top p-3">
                        <button type="button" class="btn btn-outline-secondary px-3 py-2 rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold">Save Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function toggleModalBankFields(method) {
    const bankDiv = document.getElementById('modalBankContainer');
    const refDiv = document.getElementById('modalRefContainer');
    if (!bankDiv || !refDiv) return;

    if (method === 'cash') {
        bankDiv.style.display = 'none';
        refDiv.style.display = 'none';
    } else {
        bankDiv.style.display = 'block';
        refDiv.style.display = 'block';
    }
}
</script>
@endpush
