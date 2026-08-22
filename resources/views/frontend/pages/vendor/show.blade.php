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
    .stat-card-mini {
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    @php
        $vData = $vendor ?? $customer;
        $isActive = in_array($vData->status, ['active', '1', 1]);
        $totalPurchases = $purchases->count();
        $totalSpent = $purchases->sum(fn($p) => (float)($p->total_price ?? $p->total_amount ?? 0));
        $totalWeight = $purchases->sum(fn($p) => (float)($p->total_weight ?? 0));
        $totalDue = $purchases->sum(fn($p) => (float)($p->due ?? 0));
    @endphp

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Vendor Profile</h4>
                <p class="text-muted small mb-0">Detailed supplier contact profile, steel procurement volume and purchase order history</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($totalDue > 0)
                    <button type="button" class="btn btn-success px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 shadow-sm text-white" onclick="openVendorPaymentModal('', '{{ $totalDue }}')">
                        <i class="fe fe-dollar-sign"></i>
                        <span>Pay Vendor Due (৳{{ number_format($totalDue, 2) }})</span>
                    </button>
                @endif
                <a href="{{ route('vendors.ledger.pdf', $vData->id) }}" target="_blank" class="btn btn-outline-danger px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-download"></i>
                    <span>Statement PDF</span>
                </a>
                <a href="{{ route('vendors.ledger', $vData->id) }}" class="btn btn-outline-primary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-book-open"></i>
                    <span>Account Ledger</span>
                </a>
                <a href="{{ route('vendors.edit', $vData->id) }}" class="btn btn-primary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="fe fe-edit"></i>
                    <span>Edit Vendor</span>
                </a>
                <a href="{{ route('vendors.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Vendor Summary Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center g-4">
                <div class="col-md-6 col-lg-4">
                    <div>
                        <h4 class="fw-bold text-dark mb-1">{{ $vData->name }}</h4>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            @if ($isActive)
                                <span class="badge badge-soft-success px-3 py-1 rounded-pill fs-7">
                                    <i class="fe fe-check-circle me-1"></i> Active Vendor
                                </span>
                            @else
                                <span class="badge badge-soft-danger px-3 py-1 rounded-pill fs-7">
                                    <i class="fe fe-x-circle me-1"></i> Inactive
                                </span>
                            @endif
                            <span class="text-muted small">ID #VND-{{ str_pad($vData->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-8 border-start border-light ps-lg-4">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded-3 text-center stat-card-mini">
                                <small class="text-muted text-uppercase fw-semibold fs-7 d-block mb-1">Phone Number</small>
                                <span class="fw-bold text-dark">{{ $vData->phone }}</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded-3 text-center stat-card-mini">
                                <small class="text-muted text-uppercase fw-semibold fs-7 d-block mb-1">Email Address</small>
                                <span class="fw-bold text-dark text-truncate d-block" title="{{ $vData->email }}">{{ $vData->email ?: 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded-3 text-center stat-card-mini">
                                <small class="text-muted text-uppercase fw-semibold fs-7 d-block mb-1">Vendor Since</small>
                                <span class="fw-bold text-dark">{{ $vData->created_at?->format('d M Y') ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address Row -->
            <div class="mt-4 pt-3 border-top border-light">
                <div class="d-flex align-items-center gap-2">
                    <i class="fe fe-map-pin text-primary"></i>
                    <span class="text-secondary small">{{ $vData->address }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Procurement Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card border-0 shadow-sm rounded-3 bg-white h-100 mb-0">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-shopping-cart fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Purchase Orders</small>
                        <h5 class="fw-bold text-dark mb-0">{{ number_format($totalPurchases) }} Orders</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card border-0 shadow-sm rounded-3 bg-white h-100 mb-0">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-layers fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Steel Procured</small>
                        <h5 class="fw-bold text-dark mb-0">{{ number_format($totalWeight, 2) }} kg</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card border-0 shadow-sm rounded-3 bg-white h-100 mb-0">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Purchase Value</small>
                        <h5 class="fw-bold text-success mb-0">৳{{ number_format($totalSpent, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card border-0 shadow-sm rounded-3 bg-white h-100 mb-0">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-danger-light text-danger rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-alert-circle fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Outstanding Due</small>
                        <h5 class="fw-bold text-danger mb-0">৳{{ number_format($totalDue, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Order History Table -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0">Vendor Purchase History</h5>
            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">{{ $totalPurchases }} Procurement Orders</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4">PO / Lot #</th>
                            <th>Date</th>
                            <th>Specifications &amp; Quantity</th>
                            <th>Rate</th>
                            <th>Total Price</th>
                            <th>Paid / Due</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                            @php
                                $price = (float)($purchase->total_price ?? $purchase->total_amount ?? 0);
                                $paid = (float)($purchase->payment ?? $purchase->paid_amount ?? 0);
                                $due = (float)($purchase->due ?? $purchase->due_amount ?? 0);
                                $pieceCount = (float)($purchase->quantity ?? 1);
                                $formattedPieces = floor($pieceCount) == $pieceCount ? (int)$pieceCount : $pieceCount;
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-dark d-block">#PO-{{ $purchase->id }}</span>
                                    @if($purchase->lot)
                                        <div class="mt-1">
                                            <a href="{{ route('lots.show', $purchase->lot_id) }}" class="badge bg-light text-primary border text-decoration-none px-2 py-1 fs-8 d-inline-flex align-items-center">
                                                <i class="fe fe-package me-1"></i>{{ $purchase->lot->lot_number }}
                                            </a>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-secondary small">{{ $purchase->created_at?->format('d M Y, h:i A') }}</span>
                                </td>
                                <td>
                                    <div>
                                        @if($purchase->thickness || $purchase->size)
                                            <span class="fw-bold text-dark fs-7 d-block">
                                                {{ $purchase->thickness ? 'Thk: ' . $purchase->thickness : '' }} 
                                                {{ $purchase->size ? '| Size: ' . $purchase->size . ' ' . ($purchase->size_type ?: 'inch') : '' }}
                                            </span>
                                        @endif
                                        <div class="d-flex align-items-center gap-1 mt-1">
                                            <span class="badge bg-light text-dark border px-2 py-0 fs-8">
                                                Qty: {{ $formattedPieces }} Coils
                                            </span>
                                            @if($purchase->total_weight > 0)
                                                <small class="text-muted fs-8">({{ number_format($purchase->total_weight, 2) }} kg)</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-dark small fw-semibold">৳{{ number_format($purchase->unit_price ?? 0, 2) }}</span>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark">৳{{ number_format($price, 2) }}</span>
                                </td>
                                <td>
                                    <div>
                                        <div class="text-success small fw-semibold">
                                            Paid: ৳{{ number_format($paid, 2) }}
                                        </div>
                                        @if($due > 0)
                                            <div class="text-danger small fw-semibold">
                                                Due: ৳{{ number_format($due, 2) }}
                                            </div>
                                        @else
                                            <div class="text-muted fs-8">
                                                <i class="fe fe-check text-success me-1"></i>Fully Paid
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    @if($due > 0)
                                        <button type="button" class="btn btn-sm btn-outline-success rounded-2 px-3 py-1 fw-semibold"
                                            onclick="openVendorPaymentModal('{{ $purchase->id }}', '{{ $due }}')">
                                            <i class="fe fe-credit-card me-1"></i>Pay Due
                                        </button>
                                    @else
                                        <span class="badge badge-soft-success px-3 py-1 rounded-pill fs-7">Settled</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fe fe-truck fs-1 mb-2 text-secondary d-block"></i>
                                    <span>No purchase order transactions recorded for this vendor yet.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Vendor Payment & Disbursement History Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0">
                <i class="fe fe-dollar-sign text-success me-1"></i> Disbursement & Payment History
            </h5>
            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">{{ count($payments ?? []) }} Payments Made</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4" style="width: 5%;">#</th>
                            <th style="width: 15%;">Date</th>
                            <th style="width: 25%;">Payment Method & Channel</th>
                            <th style="width: 20%;">Transaction Ref / TrxID</th>
                            <th style="width: 15%;">PO Reference</th>
                            <th style="width: 15%;">Amount (৳)</th>
                            <th class="text-end pe-4" style="width: 5%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments ?? [] as $payment)
                            @php
                                $methodName = match($payment->payment_method) {
                                    'bank', '2' => 'Bank Transfer',
                                    'mobile_banking', '3' => 'Mobile Banking',
                                    default => 'Cash in Hand'
                                };
                            @endphp
                            <tr>
                                <td class="ps-4 text-muted">{{ $loop->iteration }}</td>
                                <td class="fw-medium text-dark">{{ $payment->created_at?->format('d M Y, h:i A') }}</td>
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
                                    @if($payment->purchase_id)
                                        <span class="badge bg-primary-light text-primary">#PO-{{ $payment->purchase_id }}</span>
                                    @else
                                        <span class="badge bg-light text-secondary border">General Due</span>
                                    @endif
                                </td>
                                <td>
                                    <strong class="text-success fs-6">৳ {{ number_format($payment->amount, 2) }}</strong>
                                </td>
                                <td class="text-end pe-4">
                                    <form action="{{ route('vendor-payments.destroy', $payment->id) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to revert this payment of ৳{{ number_format($payment->amount, 2) }}? This will restore the purchase due balance.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-2 py-1 px-2" title="Delete Payment Record">
                                            <i class="fe fe-trash-2"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fe fe-inbox fs-2 d-block mb-1"></i>No payments recorded for this vendor yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Placed Outside Tables to Prevent Clipping Context Issues -->
<div class="modal fade" id="vendorPaymentModal" tabindex="-1" aria-labelledby="vendorPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="vendorPaymentModalLabel">
                    <i class="fe fe-dollar-sign me-2 text-success"></i>Record Vendor Due Payment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('vendor-payments.store') }}" method="POST" id="vendorPaymentForm">
                @csrf
                <input type="hidden" name="vendor_id" value="{{ $vData->id }}">
                <input type="hidden" name="purchase_id" id="modal_purchase_id" value="">

                <div class="modal-body p-4">
                    <div class="alert alert-light border rounded-3 mb-3 p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary small fw-semibold">Vendor:</span>
                            <span class="text-dark fw-bold">{{ $vData->name }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1" id="modalPoRow" style="display: none;">
                            <span class="text-secondary small fw-semibold">Target Purchase Order:</span>
                            <span class="text-primary fw-bold font-monospace" id="modalPoNumber">#PO</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="text-secondary small fw-semibold">Max Settlable Due:</span>
                            <span class="text-danger fw-bold fs-6" id="modalMaxDueDisplay">৳ 0.00</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold mb-1">Disbursement Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" id="vendorModalPaymentMethod" class="form-select border-light-subtle" required onchange="toggleVendorModalBank(this.value)">
                            <option value="cash" selected>Cash in Hand</option>
                            <option value="bank">Bank Transfer / Deposit</option>
                            <option value="mobile_banking">Mobile Banking (bKash/Nagad)</option>
                        </select>
                    </div>

                    <!-- Bank Account Selector -->
                    <div class="mb-3" id="vendorModalBankContainer" style="display: none;">
                        <label class="form-label small text-secondary fw-semibold mb-1">Disbursement Bank / Wallet Account <span class="text-danger">*</span></label>
                        <select name="bank_detail_id" id="vendorModalBankDetail" class="form-select border-light-subtle">
                            <option value="">Select Bank / MFS Account</option>
                            @foreach($bankAccounts ?? [] as $bank)
                                <option value="{{ $bank->id }}" {{ $bank->is_default ? 'selected' : '' }}>
                                    {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Transaction Ref -->
                    <div class="mb-3" id="vendorModalRefContainer" style="display: none;">
                        <label class="form-label small text-secondary fw-semibold mb-1">Transaction Ref / TrxID</label>
                        <input type="text" name="transaction_ref" class="form-control border-light-subtle" placeholder="e.g. Bank Trx # or TrxID">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold mb-1">Payment Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="vendorModalAmount" class="form-control fw-bold text-success fs-5 border-light-subtle" placeholder="0.00" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold mb-1">Remarks / Note</label>
                        <textarea name="remarks" class="form-control border-light-subtle" rows="2" placeholder="Optional payment note..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-outline-secondary px-3 py-2 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 py-2 rounded-3 fw-semibold text-white">
                        <i class="fe fe-check-circle me-1"></i>Confirm Disbursement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openVendorPaymentModal(purchaseId, maxAmount) {
    document.getElementById('modal_purchase_id').value = purchaseId || '';
    const poRow = document.getElementById('modalPoRow');
    const poNum = document.getElementById('modalPoNumber');
    
    if (purchaseId) {
        poRow.style.display = 'flex';
        poNum.textContent = '#PO-' + purchaseId;
    } else {
        poRow.style.display = 'none';
    }

    const numMax = parseFloat(maxAmount) || 0;
    document.getElementById('modalMaxDueDisplay').textContent = '৳ ' + numMax.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const amountInput = document.getElementById('vendorModalAmount');
    amountInput.value = numMax > 0 ? numMax.toFixed(2) : '';
    amountInput.max = numMax > 0 ? numMax : '';

    const modalEl = document.getElementById('vendorPaymentModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function toggleVendorModalBank(method) {
    const bankContainer = document.getElementById('vendorModalBankContainer');
    const refContainer = document.getElementById('vendorModalRefContainer');
    if (!bankContainer || !refContainer) return;

    if (method === 'cash') {
        bankContainer.style.display = 'none';
        refContainer.style.display = 'none';
    } else {
        bankContainer.style.display = 'block';
        refContainer.style.display = 'block';
    }
}
</script>
@endpush
