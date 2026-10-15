@extends('frontend.layouts.app')

@push('styles')
<style>
    .stat-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
    }
    .table-custom tbody tr {
        transition: background-color 0.15s ease;
    }
    .table-custom tbody tr:hover {
        background-color: #fcfbff !important;
    }
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
    .table-custom th, .table-custom td {
        white-space: nowrap;
    }
    .table-responsive {
        overflow: visible !important;
    }
    .dropdown-menu {
        z-index: 1060 !important;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Vendor Due Payments</h4>
                <p class="text-muted small mb-0">Overview of outstanding supplier payable balances and purchase order settlements</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('vendor-due-payments.pdf') }}" target="_blank" class="btn btn-outline-danger px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-file-text fs-6"></i>
                    <span>Export PDF</span>
                </a>
                <a href="{{ route('purchase.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3 shadow-sm">
                    <i class="fa fa-arrow-left me-2"></i>    
                    Back to Purchases
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Summary Stats Bar -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-danger-light text-danger rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-alert-circle fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Due Orders</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($purchases->count()) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-danger-light text-danger rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Outstanding Dues</h6>
                        <h4 class="mb-0 fw-bold text-danger">৳{{ number_format($purchases->sum('due'), 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-check-circle fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Paid on Due Orders</h6>
                        <h4 class="mb-0 fw-bold text-dark">৳{{ number_format($purchases->sum('payment'), 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-truck fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Vendors with Balance</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($purchases->pluck('vendor_id')->unique()->count()) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Summary Stats Bar -->

    <!-- Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <!-- Filter Controls -->
        <div class="card-header bg-white py-3 border-bottom border-light">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-6 col-lg-6">
                    <div class="search-box-custom">
                        <input type="text" id="dueSearchInput" class="form-control border-light-subtle" placeholder="Search PO #, vendor name, phone, lot..." autocomplete="off">
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-6 text-md-end text-muted small">
                    Showing <span id="visibleDueCount" class="fw-bold text-dark">{{ $purchases->count() }}</span> of {{ $purchases->count() }} records
                </div>
            </div>
        </div>

        <!-- Table Body -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0" id="duePaymentsTable">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Date</th>
                            <th>PO / Lot #</th>
                            <th>Vendor Details</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Outstanding Due</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse ($purchases as $purchase)
                            @php
                                $vendorName = $purchase->vendor->name ?? 'N/A';
                                $vendorPhone = $purchase->vendor->phone ?? 'N/A';
                                $lotNo = $purchase->lot->lot_number ?? '';
                            @endphp
                            <tr class="due-row" data-search="{{ strtolower('po-' . $purchase->id . ' ' . $vendorName . ' ' . $vendorPhone . ' ' . $lotNo) }}">
                                <td class="ps-4 text-muted fw-semibold">{{ $loop->iteration }}</td>
                                <td>
                                    <span class="text-secondary small fw-semibold">
                                        {{ $purchase->created_at ? $purchase->created_at->format('d M Y') : 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold text-primary font-monospace d-block">#PO-{{ $purchase->id }}</span>
                                    @if($purchase->lot)
                                        <small class="text-muted fs-8">{{ $purchase->lot->lot_number }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        <span class="fw-bold text-dark d-block">{{ Str::limit($vendorName, 18, '...') }}</span>
                                        <small class="text-muted fs-7"><i class="fe fe-phone me-1"></i>{{ $vendorPhone }}</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-soft-info px-3 py-1 rounded-pill fs-7">
                                        ৳{{ number_format($purchase->total_price, 2) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-soft-success px-3 py-1 rounded-pill fs-7">
                                        ৳{{ number_format($purchase->payment, 2) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-soft-danger px-3 py-1 rounded-pill fs-7">
                                        ৳{{ number_format($purchase->due, 2) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-success fw-semibold" href="javascript:void(0)"
                                                    onclick="openVendorDueModal('{{ $purchase->id }}', '{{ $purchase->vendor_id }}', '{{ addslashes($vendorName) }}', '{{ $purchase->due }}')">
                                                    <i class="fe fe-dollar-sign text-success"></i>
                                                    <span>Pay Due</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('purchase.show', $purchase->id) }}">
                                                    <i class="fe fe-eye text-info"></i>
                                                    <span>View Purchase Order</span>
                                                </a>
                                            </li>
                                            @if($purchase->lot_id)
                                                <li>
                                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('lots.show', $purchase->lot_id) }}">
                                                        <i class="fe fe-package text-primary"></i>
                                                        <span>View Lot / Batch</span>
                                                    </a>
                                                </li>
                                            @endif
                                            @if($purchase->vendor_id)
                                                <li>
                                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('vendors.ledger', $purchase->vendor_id) }}">
                                                        <i class="fe fe-book-open text-secondary"></i>
                                                        <span>Vendor Ledger</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('vendors.show', $purchase->vendor_id) }}">
                                                        <i class="fe fe-user text-dark"></i>
                                                        <span>Vendor Profile</span>
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyRow">
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <div class="avatar avatar-xl bg-success-light text-success rounded-circle mb-3 d-flex align-items-center justify-content-center">
                                            <i class="fe fe-check-circle fs-1"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">No Outstanding Vendor Dues</h5>
                                        <p class="text-muted small mb-0">All vendor purchase orders are fully settled and paid up to date.</p>
                                    </div>
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
<div class="modal fade" id="vendorDueModal" tabindex="-1" aria-labelledby="vendorDueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="vendorDueModalLabel">
                    <i class="fe fe-dollar-sign me-2 text-success"></i>Pay Vendor Purchase Due
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('vendor-payments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="vendor_id" id="modalVendorId" value="">
                <input type="hidden" name="purchase_id" id="modalPurchaseId" value="">

                <div class="modal-body p-4">
                    <div class="alert alert-light border rounded-3 mb-3 p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary small fw-semibold">Vendor:</span>
                            <span class="text-dark fw-bold" id="modalVendorName">Vendor</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="text-secondary small fw-semibold">Purchase Order:</span>
                            <span class="text-primary fw-bold font-monospace" id="modalPoNumber">#PO</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="text-secondary small fw-semibold">Outstanding Due:</span>
                            <span class="text-danger fw-bold fs-6" id="modalDueDisplay">৳ 0.00</span>
                        </div>
                    </div>

                    @php
                        $regularPurchaseDueBanks = ($bankAccounts ?? collect())->filter(function($b) {
                            $type = $b->account_type ?? '';
                            $name = $b->bank_name ?? '';
                            return !(stripos($type, 'MFS') !== false || preg_match('/(bkash|nagad|rocket|upay|mfs)/i', $name . ' ' . $type));
                        });
                        $mfsPurchaseDueBanks = ($bankAccounts ?? collect())->filter(function($b) {
                            $type = $b->account_type ?? '';
                            $name = $b->bank_name ?? '';
                            return (stripos($type, 'MFS') !== false || preg_match('/(bkash|nagad|rocket|upay|mfs)/i', $name . ' ' . $type));
                        });
                    @endphp

                    <!-- Unified Disbursement Account Selector -->
                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold mb-1">Disbursement Account / Method <span class="text-danger">*</span></label>
                        <select id="purchaseDueAccountSelector" class="form-select border-light-subtle" onchange="handlePurchaseDueAccountChange(this)" required>
                            <optgroup label="Cash">
                                <option value="cash" data-method="cash">Hand Cash</option>
                            </optgroup>
                            @if($regularPurchaseDueBanks->count() > 0)
                                <optgroup label="Bank Accounts">
                                    @foreach($regularPurchaseDueBanks as $bank)
                                        <option value="bank_{{ $bank->id }}" data-method="bank" data-id="{{ $bank->id }}" {{ $bank->is_default ? 'selected' : '' }}>
                                            {{ $bank->bank_name }} — {{ $bank->account_number }} ({{ $bank->account_name }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if($mfsPurchaseDueBanks->count() > 0)
                                <optgroup label="Mobile Banking / MFS">
                                    @foreach($mfsPurchaseDueBanks as $bank)
                                        <option value="mfs_{{ $bank->id }}" data-method="mobile_banking" data-id="{{ $bank->id }}" {{ (!isset($regularPurchaseDueBanks) || $regularPurchaseDueBanks->isEmpty()) && $loop->first ? 'selected' : '' }}>
                                            {{ $bank->bank_name }} — {{ $bank->account_number }} ({{ $bank->account_name }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        <input type="hidden" name="payment_method" id="hidden_purchase_due_method" value="bank">
                        <input type="hidden" name="bank_detail_id" id="hidden_purchase_due_bank_id" value="">
                    </div>

                    <!-- Transaction Ref (Shown for Bank / MFS) -->
                    <div class="mb-3" id="modalRefContainer" style="display: none;">
                        <label class="form-label small text-secondary fw-semibold mb-1">Transaction Ref / TrxID</label>
                        <input type="text" name="transaction_ref" class="form-control border-light-subtle" placeholder="e.g. TrxID / Cheque #">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold mb-1">Payment Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="modalAmount" class="form-control fw-bold text-success fs-5 border-light-subtle" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold mb-1">Remarks / Note</label>
                        <textarea name="remarks" class="form-control border-light-subtle" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top p-3 gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3 py-2 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 py-2 rounded-3 fw-semibold text-white">
                        Confirm Disbursement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('dueSearchInput');
    const rows = document.querySelectorAll('.due-row');
    const countDisplay = document.getElementById('visibleDueCount');

    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const query = this.value.toLowerCase().trim();
            let visibleCount = 0;

            rows.forEach(function (row) {
                const searchData = row.getAttribute('data-search') || '';
                if (searchData.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (countDisplay) {
                countDisplay.textContent = visibleCount;
            }
        });
    }
});

function openVendorDueModal(purchaseId, vendorId, vendorName, maxDue) {
    document.getElementById('modalPurchaseId').value = purchaseId;
    document.getElementById('modalVendorId').value = vendorId;
    document.getElementById('modalVendorName').textContent = vendorName;
    document.getElementById('modalPoNumber').textContent = '#PO-' + purchaseId;

    const numMax = parseFloat(maxDue) || 0;
    document.getElementById('modalDueDisplay').textContent = '৳ ' + numMax.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const amountInput = document.getElementById('modalAmount');
    amountInput.value = numMax > 0 ? numMax.toFixed(2) : '';
    amountInput.max = numMax > 0 ? numMax : '';

    const selector = document.getElementById('purchaseDueAccountSelector');
    if (selector) {
        handlePurchaseDueAccountChange(selector);
    }

    const modalEl = document.getElementById('vendorDueModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function handlePurchaseDueAccountChange(select) {
    if (!select) return;
    const selectedOption = select.options[select.selectedIndex];
    if (!selectedOption) return;
    const method = selectedOption.getAttribute('data-method') || 'bank';
    const bankId = selectedOption.getAttribute('data-id') || '';

    const hiddenMethod = document.getElementById('hidden_purchase_due_method');
    const hiddenBank = document.getElementById('hidden_purchase_due_bank_id');
    const refContainer = document.getElementById('modalRefContainer');

    if (hiddenMethod) hiddenMethod.value = method;
    if (hiddenBank) hiddenBank.value = bankId;

    if (refContainer) {
        refContainer.style.display = bankId ? 'block' : 'none';
    }
}
</script>
@endpush
