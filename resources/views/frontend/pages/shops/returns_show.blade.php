@extends('frontend.layouts.app')

@push('styles')
<style>
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
    .badge-soft-secondary {
        background-color: rgba(108, 117, 125, 0.12) !important;
        color: #6c757d !important;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    @if(!$return)
        <div class="alert alert-warning rounded-3">
            <i class="fe fe-alert-triangle me-2"></i> Shop return record not found.
            <a href="{{ route('shops.returns.index') }}" class="alert-link ms-2">Back to Shop Returns</a>
        </div>
    @else
        <!-- Page Header (No Breadcrumbs per AGENTS.md) -->
        <div class="page-header mb-4">
            <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h4 class="card-title fw-bold text-dark mb-0">Shop Return Details #{{ $return->id }}</h4>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill small">
                            <i class="fe fe-home me-1"></i>{{ $shop->name }}
                        </span>
                    </div>
                    <p class="text-muted small mb-0">Customer return record and restocking history for <strong>{{ $shop->name }}</strong></p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('shops.returns.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                        <i class="fe fe-arrow-left fs-6"></i>
                        <span>Back to Shop Returns</span>
                    </a>
                    @if($return->sale)
                        <a href="{{ route('sales.show', $return->sale->id) }}" class="btn btn-light border px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                            <i class="fe fe-file-text fs-6"></i>
                            <span>View Sale Invoice</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
        <!-- /Page Header -->

        <!-- Status Banner -->
        @php
            $statusConfig = [
                'pending' => ['class' => 'alert-warning text-dark', 'icon' => 'fe-clock', 'msg' => 'This shop return request is pending management approval.'],
                'approved' => ['class' => 'alert-success text-dark', 'icon' => 'fe-check-circle', 'msg' => 'Return approved. Inventory stock restocked into ' . $shop->name . ' and invoice balance adjusted.'],
                'completed' => ['class' => 'alert-success text-dark', 'icon' => 'fe-check-circle', 'msg' => 'Return process completed and inventory stock updated in ' . $shop->name . '.'],
                'rejected' => ['class' => 'alert-danger text-dark', 'icon' => 'fe-x-circle', 'msg' => 'This shop return request has been rejected.']
            ][$return->status] ?? ['class' => 'alert-secondary', 'icon' => 'fe-help-circle', 'msg' => 'Unknown status'];
        @endphp

        <div class="alert {{ $statusConfig['class'] }} border-0 shadow-sm rounded-3 d-flex align-items-center p-3 mb-4">
            <i class="fe {{ $statusConfig['icon'] }} fs-4 me-3"></i>
            <div>
                <strong class="d-block">Status: {{ ucfirst($return->status) }}</strong>
                <span class="small">{{ $statusConfig['msg'] }}</span>
            </div>
        </div>

        <!-- Info Cards Row -->
        <div class="row g-4 mb-4">
            <!-- Return Info Card -->
            <div class="col-lg-6 col-12">
                <div class="card border-0 shadow-sm rounded-3 h-100 mb-0">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark mb-3"><i class="fe fe-rotate-ccw me-2 text-primary"></i>Return Information</h6>
                        <table class="table table-borderless align-middle mb-0">
                            <tr>
                                <td class="text-secondary small fw-semibold ps-0" style="width: 40%">Return Record ID:</td>
                                <td class="fw-bold text-dark">#{{ $return->id }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold ps-0">Retail Outlet:</td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 rounded-2">
                                        <i class="fe fe-home me-1 text-primary"></i>{{ $shop->name }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold ps-0">Return Date:</td>
                                <td class="fw-bold text-dark">{{ $return->return_date ? $return->return_date->format('d M Y') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold ps-0">Created At:</td>
                                <td class="text-muted">{{ $return->created_at ? $return->created_at->format('d M Y, h:i A') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold ps-0">Total Refund:</td>
                                <td>
                                    <span class="badge badge-soft-danger px-3 py-1 rounded-pill fs-7">
                                        ৳{{ number_format($return->total_refund_amount, 2) }}
                                    </span>
                                </td>
                            </tr>
                            @if($return->reason)
                                <tr>
                                    <td class="text-secondary small fw-semibold ps-0">General Reason:</td>
                                    <td class="text-dark">{{ $return->reason }}</td>
                                </tr>
                            @endif
                            @if($return->notes)
                                <tr>
                                    <td class="text-secondary small fw-semibold ps-0">Notes:</td>
                                    <td class="text-muted">{{ $return->notes }}</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sale & Customer Info Card -->
            <div class="col-lg-6 col-12">
                <div class="card border-0 shadow-sm rounded-3 h-100 mb-0">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark mb-3"><i class="fe fe-user me-2 text-primary"></i>Sale & Customer Details</h6>
                        <table class="table table-borderless align-middle mb-0">
                            <tr>
                                <td class="text-secondary small fw-semibold ps-0" style="width: 40%">Shop Order No:</td>
                                <td>
                                    @if($return->sale)
                                        <a href="{{ route('sales.show', $return->sale->id) }}" class="fw-bold text-primary font-monospace">
                                            #{{ $return->sale->order_no }}
                                        </a>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold ps-0">Customer Name:</td>
                                <td class="fw-bold text-dark">{{ $return->customer->name ?? 'Walk-in / N/A' }}</td>
                            </tr>
                            @if($return->customer && $return->customer->phone)
                                <tr>
                                    <td class="text-secondary small fw-semibold ps-0">Phone Number:</td>
                                    <td class="text-muted"><i class="fe fe-phone me-1"></i>{{ $return->customer->phone }}</td>
                                </tr>
                            @endif
                            @if($return->processedBy)
                                <tr>
                                    <td class="text-secondary small fw-semibold ps-0">Processed By:</td>
                                    <td class="fw-semibold text-dark">{{ $return->processedBy->name }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary small fw-semibold ps-0">Processed Date:</td>
                                    <td class="text-muted">{{ $return->processed_at?->format('d M Y, h:i A') }}</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Settlement Card (when return is approved or completed) -->
        @if($return->isApproved() || $return->isCompleted())
            @php
                $sale = $return->sale;
                $refundAmount = (float)$return->total_refund_amount;
                $saleDue = $sale ? (float)$sale->due_payment : 0;
                $salePaid = $sale ? (float)$sale->advanced_payment : 0;
            @endphp
            <div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-4 border-success">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar avatar-sm bg-success-light text-success rounded-circle d-flex align-items-center justify-content-center">
                                <i class="fe fe-dollar-sign fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0">Financial Settlement & Refund Breakdown</h6>
                                <small class="text-muted">How this ৳{{ number_format($refundAmount, 2) }} refund is settled in the accounts</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @if($return->customer_id)
                                <a href="{{ route('customers.ledger', $return->customer_id) }}" class="btn btn-sm btn-outline-primary rounded-2 d-inline-flex align-items-center gap-1">
                                    <i class="fe fe-book-open"></i>
                                    <span>View Customer Ledger</span>
                                </a>
                            @endif
                            @if($sale)
                                <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-sm btn-light border rounded-2 d-inline-flex align-items-center gap-1">
                                    <i class="fe fe-file-text"></i>
                                    <span>View Sale Invoice</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4 col-12">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted small d-block mb-1">Total Refund Value</span>
                                <h5 class="fw-bold text-danger mb-0">৳{{ number_format($refundAmount, 2) }}</h5>
                                <small class="text-muted">Restocked & credited</small>
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted small d-block mb-1">Invoice Net Payable</span>
                                <h5 class="fw-bold text-dark mb-0">৳{{ number_format($sale?->payble ?? 0, 2) }}</h5>
                                <small class="text-muted">Total adjusted by return</small>
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted small d-block mb-1">Current Invoice Remaining Due</span>
                                <h5 class="fw-bold {{ ($saleDue > 0) ? 'text-warning' : 'text-success' }} mb-0">৳{{ number_format($saleDue, 2) }}</h5>
                                <small class="text-muted">{{ $saleDue > 0 ? 'Customer still owes this balance' : 'Invoice fully cleared' }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 p-3 rounded-3 bg-white border d-flex align-items-start gap-3">
                        <div class="mt-1"><i class="fe fe-info fs-5 text-primary"></i></div>
                        <div>
                            @if($saleDue > 0)
                                <div class="fw-bold text-dark mb-1">Applied as Due Deduction (No Cash Payout Needed)</div>
                                <p class="small text-muted mb-0">
                                    The customer had an unpaid due on this order. Approving this return automatically reduced their due balance by <strong>৳{{ number_format($refundAmount, 2) }}</strong>. The customer now owes <strong>৳{{ number_format($saleDue, 2) }}</strong> instead of the previous balance.
                                </p>
                            @else
                                <div class="fw-bold text-dark mb-1">Cash / Bank Refund to Customer</div>
                                <p class="small text-muted mb-0">
                                    A negative payment record was posted to the ledger and accounting vouchers. You can hand over <strong>৳{{ number_format($refundAmount, 2) }}</strong> in cash from the shop register or transfer to the customer. Their party ledger is already credited.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Returned Items Table Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3"><i class="fe fe-box me-2 text-primary"></i>Returned Items Breakdown</h6>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary fs-7 text-uppercase">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Item / Stock Source</th>
                                <th>Returned Qty</th>
                                <th>Unit Price</th>
                                <th>Subtotal Refund</th>
                                <th>Return Reason</th>
                                <th>Condition</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @foreach($return->items as $item)
                                <tr>
                                    <td class="ps-3 text-muted fw-semibold">{{ $loop->iteration }}</td>
                                    <td>
                                        @php
                                            $coilNo = $item->product->coil_number ?? $item->salesItem?->coil?->coil_number ?? null;
                                            $lotNo = $item->salesItem?->lot?->lot_number ?? null;
                                            $name = $coilNo ? ('Coil #' . $coilNo) : ($lotNo ? ('Lot #' . $lotNo) : ('Item #' . $item->id));
                                            if ($item->salesItem && $item->salesItem->size) {
                                                $name .= " ({$item->salesItem->size} " . ($item->salesItem->size_type ?: '') . ")";
                                            }
                                        @endphp
                                        <span class="fw-bold text-dark d-block">{{ $name }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-info px-2 py-1 rounded-2">{{ number_format($item->quantity, 2) }}</span>
                                    </td>
                                    <td>৳{{ number_format($item->unit_price, 2) }}</td>
                                    <td>
                                        <span class="fw-bold text-danger">৳{{ number_format($item->total_price, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-secondary px-2 py-1 rounded-2 text-capitalize">
                                            {{ $item->reason_label }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $conditionClass = [
                                                'good' => 'badge-soft-success',
                                                'damaged' => 'badge-soft-warning',
                                                'defective' => 'badge-soft-danger'
                                            ][$item->condition] ?? 'badge-soft-secondary';
                                        @endphp
                                        <span class="badge {{ $conditionClass }} px-2 py-1 rounded-2 text-capitalize">
                                            {{ $item->condition_label }}
                                        </span>
                                    </td>
                                    <td class="text-muted small">{{ $item->notes ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-top">
                            <tr>
                                <td colspan="4" class="text-end fw-bold text-dark py-3">Total Calculated Refund:</td>
                                <td colspan="4" class="fw-bold text-danger fs-6 py-3">৳{{ number_format($return->total_refund_amount, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Actions Bar Card (Only for Pending Returns) -->
        @if($return->isPending())
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h6 class="fw-bold text-dark mb-1"><i class="fe fe-settings me-2 text-primary"></i>Approval Actions</h6>
                        <span class="text-muted small">Approving this return will immediately restock items into <strong>{{ $shop->name }}</strong> inventory and adjust the invoice balance.</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success px-4 py-2 rounded-3 d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                            <i class="fe fe-check-circle"></i>
                            <span>Approve & Restock</span>
                        </button>

                        <button type="button" class="btn btn-outline-danger px-4 py-2 rounded-3 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <span>Reject Return</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Approve & Refund Modal -->
            <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow rounded-3">
                        <form method="POST" action="{{ route('returns.approve', $return->id) }}">
                            @csrf
                            @method('PATCH')
                            <div class="modal-header border-bottom">
                                <h5 class="modal-title fw-bold text-dark">
                                    <i class="fe fe-check-circle text-success me-2"></i>Approve Return &amp; Refund
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                @php
                                    $refundTotal = (float)($return->total_refund_amount ?? 0);
                                    $saleDue = (float)($return->sale->due_payment ?? 0);
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

                                <!-- Refund Overview -->
                                <div class="p-3 bg-light rounded-3 border mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-secondary small fw-semibold">Return Amount:</span>
                                        <span class="fw-bold text-danger fs-6">৳{{ number_format($refundTotal, 2) }}</span>
                                    </div>
                                    @if($return->sale)
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-muted small">Current Invoice Due:</span>
                                            <span class="fw-semibold text-dark small">৳{{ number_format($saleDue, 2) }}</span>
                                        </div>
                                        @if($saleDue >= $refundTotal)
                                            <div class="mt-2 pt-2 border-top text-success small">
                                                <i class="fe fe-info me-1"></i>Full return amount will be adjusted against invoice due balance.
                                            </div>
                                        @elseif($saleDue > 0)
                                            <div class="mt-2 pt-2 border-top text-primary small">
                                                <i class="fe fe-info me-1"></i>৳{{ number_format($saleDue, 2) }} will reduce invoice due. The remaining <strong>৳{{ number_format($refundTotal - $saleDue, 2) }}</strong> will be refunded from the account selected below.
                                            </div>
                                        @else
                                            <div class="mt-2 pt-2 border-top text-primary small">
                                                <i class="fe fe-info me-1"></i>Full <strong>৳{{ number_format($refundTotal, 2) }}</strong> will be refunded to customer from the account selected below.
                                            </div>
                                        @endif
                                    @endif
                                </div>

                                <!-- Refund Disbursed From Dropdown -->
                                <div class="mb-3">
                                    <label class="form-label small text-secondary fw-semibold mb-1">
                                        Refund Disbursed From <span class="text-danger">*</span>
                                    </label>
                                    <select id="returnApproveAccountSelector" class="form-select border-light-subtle" onchange="handleReturnApproveAccountChange(this)" required>
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
                                    <input type="hidden" name="payment_method" id="hidden_return_approve_payment_method" value="bank">
                                    <input type="hidden" name="bank_detail_id" id="hidden_return_approve_bank_detail_id" value="">
                                </div>

                                <!-- TrxID / Reference -->
                                <div class="mb-2" id="returnApproveTrxContainer" style="display: none;">
                                    <label class="form-label small text-secondary fw-semibold mb-1">TrxID / Cheque Ref</label>
                                    <input type="text" name="transaction_ref" id="returnApproveTrxRef" class="form-control border-light-subtle" placeholder="Transaction Reference / TrxID">
                                </div>
                            </div>
                            <div class="modal-footer border-top pt-3">
                                <button type="button" class="btn btn-outline-secondary rounded-2 px-3" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success rounded-2 px-4 fw-semibold">
                                    <i class="fe fe-check me-1"></i>Approve &amp; Restock
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Reject Modal (Placed outside table cards per AGENTS.md) -->
            <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow rounded-3">
                        <form method="POST" action="{{ route('returns.reject', $return->id) }}">
                            @csrf
                            @method('PATCH')
                            <div class="modal-header border-bottom">
                                <h5 class="modal-title fw-bold text-dark">Reject Shop Return #{{ $return->id }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="mb-3">
                                    <label class="form-label small text-secondary fw-semibold mb-1">Rejection Reason <span class="text-danger">*</span></label>
                                    <textarea name="reason" class="form-control border-light-subtle" rows="3" placeholder="Provide a reason for rejecting this return..." required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer border-top pt-3">
                                <button type="button" class="btn btn-outline-secondary rounded-2 px-3" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger rounded-2 px-4">Reject Return</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endif

</div>
@endsection

@push('scripts')
<script>
function handleReturnApproveAccountChange(select) {
    if (!select) return;
    const selectedOption = select.options[select.selectedIndex];
    if (!selectedOption) return;
    const method = selectedOption.getAttribute('data-method') || 'bank';
    const bankId = selectedOption.getAttribute('data-id') || '';

    const hiddenMethod = document.getElementById('hidden_return_approve_payment_method');
    const hiddenBank = document.getElementById('hidden_return_approve_bank_detail_id');
    const trxContainer = document.getElementById('returnApproveTrxContainer');

    if (hiddenMethod) hiddenMethod.value = method;
    if (hiddenBank) hiddenBank.value = bankId;

    if (trxContainer) {
        trxContainer.style.display = bankId ? 'block' : 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('returnApproveAccountSelector');
    if (sel) handleReturnApproveAccountChange(sel);
});
</script>
@endpush
