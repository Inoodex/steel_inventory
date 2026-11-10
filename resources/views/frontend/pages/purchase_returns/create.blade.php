@extends('frontend.layouts.app')

@push('styles')
<style>
    .card-title-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 6px;
        font-size: 14px;
    }
    .settlement-card {
        cursor: pointer;
        border: 2px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    .settlement-card:hover {
        border-color: #cbd5e1;
        background-color: #f8fafc;
    }
    .settlement-card.active {
        border-color: #7638ff;
        background-color: rgba(118, 56, 255, 0.04);
    }
    .info-strip {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header (No breadcrumbs as per project guidelines) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">
                    <i class="fe fe-rotate-ccw text-warning me-2"></i>Create Purchase Return / Debit Note
                </h4>
                <p class="text-muted small mb-0">Return full or partial steel weight to vendor, auto-deduct stock from coil, and settle refund or due.</p>
            </div>
            <div>
                <a href="{{ route('purchase-returns.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back to Returns</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i class="fe fe-alert-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('purchase-returns.store') }}" method="POST" id="purchaseReturnForm">
        @csrf

        <div class="row g-4">
            <!-- Left Column: Return Details & Coil Selection -->
            <div class="col-xl-7 col-lg-7 col-12">
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom border-light d-flex align-items-center justify-content-between">
                        <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <span class="card-title-badge bg-primary-light text-primary">1</span>
                            <span>Vendor &amp; Consignment Selection</span>
                        </h5>
                        <span class="badge bg-light text-dark border font-monospace">{{ $returnNo }}</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6 col-12">
                                <label class="form-label fw-semibold text-dark">Return Date <span class="text-danger">*</span></label>
                                <input type="date" name="return_date" class="form-control @error('return_date') is-invalid @enderror" value="{{ old('return_date', date('Y-m-d')) }}" required>
                                @error('return_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 col-12">
                                <label class="form-label fw-semibold text-dark">Vendor / Supplier <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendorSelect" class="form-select select2 @error('vendor_id') is-invalid @enderror" required>
                                    <option value="">Select Vendor / Supplier</option>
                                    @foreach($vendors as $v)
                                        <option value="{{ $v->id }}" {{ (old('vendor_id', $selectedVendorId) == $v->id) ? 'selected' : '' }}>
                                            {{ $v->name }} ({{ $v->phone ?? 'No Phone' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('vendor_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold text-dark">
                                    Purchased Steel Consignment / Line Item <span class="text-danger">*</span>
                                </label>
                                <select name="purchase_id" id="purchaseSelect" class="form-select select2 @error('purchase_id') is-invalid @enderror" required>
                                    <option value="">Select Consignment Item (Filtered by Vendor)</option>
                                </select>
                                <small class="text-muted" id="purchaseHelpText">Only purchases with active, in-stock remaining weight will appear.</small>
                                @error('purchase_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Selected Consignment Detail Box -->
                        <div id="consignmentSummaryCard" class="info-strip p-3 mt-4" style="display: none;">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fe fe-info text-primary me-1"></i>Consignment Details
                                </h6>
                                <span class="badge bg-success-light text-success fw-bold" id="badgeStockStatus">In Stock</span>
                            </div>
                            <div class="row g-2 small">
                                <div class="col-6 col-md-3">
                                    <span class="text-muted d-block">Coil Tag / ID:</span>
                                    <strong class="text-dark font-monospace" id="sumCoilTag">â€”</strong>
                                </div>
                                <div class="col-6 col-md-3">
                                    <span class="text-muted d-block">Specifications:</span>
                                    <strong class="text-dark" id="sumSpecs">â€”</strong>
                                </div>
                                <div class="col-6 col-md-3">
                                    <span class="text-muted d-block">Intake Weight:</span>
                                    <strong class="text-dark" id="sumIntakeWeight">â€”</strong>
                                </div>
                                <div class="col-6 col-md-3">
                                    <span class="text-muted d-block">Available In Stock:</span>
                                    <strong class="text-primary font-monospace fw-bold" id="sumAvailableStock">â€”</strong>
                                </div>
                                <div class="col-6 col-md-3 mt-2">
                                    <span class="text-muted d-block">Location / Stockyard:</span>
                                    <strong class="text-dark" id="sumWarehouse">â€”</strong>
                                </div>
                                <div class="col-6 col-md-3 mt-2">
                                    <span class="text-muted d-block">Purchase Cost Rate:</span>
                                    <strong class="text-dark font-monospace" id="sumCostRate">â€”</strong>
                                </div>
                                <div class="col-12 col-md-6 mt-2">
                                    <span class="text-muted d-block">Current Open Due on PO:</span>
                                    <strong class="text-danger font-monospace" id="sumDueAmount">à§³ 0.00</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Weight & Rate Calculation Card -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <span class="card-title-badge bg-warning-light text-warning">2</span>
                            <span>Return Weight &amp; Pricing</span>
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6 col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-semibold text-dark mb-0">
                                        Return Weight (kg) <span class="text-danger">*</span>
                                    </label>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold" id="btnReturnAll" style="display: none;">
                                        Return Full In-Stock (<span id="allWeightLabel">0</span> kg)
                                    </button>
                                </div>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0.01" name="return_weight" id="returnWeightInput" class="form-control font-monospace fw-bold @error('return_weight') is-invalid @enderror" value="{{ old('return_weight') }}" required>
                                    <span class="input-group-text bg-light">kg</span>
                                </div>
                                <small class="text-muted d-block mt-1" id="stockLimitHelp">Enter weight to return to vendor.</small>
                                @error('return_weight')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 col-12">
                                <label class="form-label fw-semibold text-dark">
                                    Return Rate / Unit Cost (৳ per kg) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">৳</span>
                                    <input type="number" step="0.01" min="0" name="unit_price" id="unitPriceInput" class="form-control font-monospace fw-bold @error('unit_price') is-invalid @enderror" value="{{ old('unit_price') }}" required>
                                </div>
                                <small class="text-muted">Pre-filled with original purchase price.</small>
                                @error('unit_price')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Total Refund Subtotal Card -->
                        <div class="bg-light p-3 rounded-3 mt-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <span class="text-muted small fw-medium d-block">Total Return Value (Credit to Claim):</span>
                                <span class="small text-muted" id="calcBreakdown">0 kg Ã— à§³0.00</span>
                            </div>
                            <div class="text-end">
                                <h3 class="mb-0 fw-bold text-success font-monospace" id="totalRefundDisplay">à§³ 0.00</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Settlement & Reason -->
            <div class="col-xl-5 col-lg-5 col-12">
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <span class="card-title-badge bg-success-light text-success">3</span>
                            <span>Refund Settlement Method</span>
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <label class="form-label fw-semibold text-dark mb-2">How should this return be settled? <span class="text-danger">*</span></label>

                        <div class="d-flex flex-column gap-2 mb-3">
                            <!-- Option 1: Reduce PO Due -->
                            <div class="p-3 rounded-3 settlement-card active" data-type="reduce_due">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="refund_type" id="refund_reduce_due" value="reduce_due" checked>
                                    <label class="form-check-label w-100" for="refund_reduce_due">
                                        <strong class="d-block text-dark">Deduct from Consignment Due</strong>
                                        <small class="text-muted">Reduces unpaid amount owed to vendor on this purchase order.</small>
                                    </label>
                                </div>
                            </div>

                            <!-- Option 2: Advance Credit -->
                            <div class="p-3 rounded-3 settlement-card" data-type="advance_credit">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="refund_type" id="refund_advance_credit" value="advance_credit">
                                    <label class="form-check-label w-100" for="refund_advance_credit">
                                        <strong class="d-block text-dark">Vendor Credit / Advance</strong>
                                        <small class="text-muted">Keeps refund as vendor credit to offset future purchases.</small>
                                    </label>
                                </div>
                            </div>

                            <!-- Option 3: Immediate Cash Refund -->
                            <div class="p-3 rounded-3 settlement-card" data-type="cash_refund">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="refund_type" id="refund_cash" value="cash_refund">
                                    <label class="form-check-label w-100" for="refund_cash">
                                        <strong class="d-block text-dark">Cash Refund Received</strong>
                                        <small class="text-muted">Vendor paid cash directly back on return.</small>
                                    </label>
                                </div>
                            </div>

                            <!-- Option 4: Bank Refund -->
                            <div class="p-3 rounded-3 settlement-card" data-type="bank_refund">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="refund_type" id="refund_bank" value="bank_refund">
                                    <label class="form-check-label w-100" for="refund_bank">
                                        <strong class="d-block text-dark">Bank Transfer / Cheque Received</strong>
                                        <small class="text-muted">Refund deposited into company bank account.</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Selection (Shown only for Bank Refund) -->
                        <div id="bankDetailsSection" style="display: none;" class="p-3 bg-light rounded-3 mb-3 border">
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-dark">Deposit Into Bank Account <span class="text-danger">*</span></label>
                                <select name="bank_detail_id" class="form-select" id="bankSelect">
                                    <option value="">-- Choose Bank Account --</option>
                                    @foreach($bankAccounts as $bank)
                                        <option value="{{ $bank->id }}">
                                            {{ $bank->bank_name }} - {{ $bank->account_number }} (Bal: à§³{{ number_format($bank->current_balance, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label fw-semibold text-dark">Transaction / Cheque Ref</label>
                                <input type="text" name="transaction_ref" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Return Reason & Gate Pass Card -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <span class="card-title-badge bg-info-light text-info">4</span>
                            <span>Reason &amp; Gate Pass Details</span>
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Return Reason</label>
                            <select name="reason" class="form-select">
                                <option value="defective">Defective / Quality Issue</option>
                                <option value="mismatch">Specification Mismatch (Thickness/Size/Grade)</option>
                                <option value="damaged">Damage in Transit / Handling</option>
                                <option value="excess">Excess Intake / Cancelled Requirement</option>
                                <option value="other">Other Reason</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark">Notes / Gate Pass &amp; Transport Info</label>
                            <textarea name="notes" rows="3" class="form-control"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="fe fe-check-circle fs-5"></i>
                            <span>Process Purchase Return &amp; Deduct Stock</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const $vendorSelect = $('#vendorSelect');
    const $purchaseSelect = $('#purchaseSelect');
    const returnWeightInput = document.getElementById('returnWeightInput');
    const unitPriceInput = document.getElementById('unitPriceInput');
    const totalRefundDisplay = document.getElementById('totalRefundDisplay');
    const calcBreakdown = document.getElementById('calcBreakdown');
    const consignmentSummaryCard = document.getElementById('consignmentSummaryCard');
    const btnReturnAll = document.getElementById('btnReturnAll');
    const allWeightLabel = document.getElementById('allWeightLabel');
    const stockLimitHelp = document.getElementById('stockLimitHelp');

    const sumCoilTag = document.getElementById('sumCoilTag');
    const sumSpecs = document.getElementById('sumSpecs');
    const sumIntakeWeight = document.getElementById('sumIntakeWeight');
    const sumAvailableStock = document.getElementById('sumAvailableStock');
    const sumWarehouse = document.getElementById('sumWarehouse');
    const sumCostRate = document.getElementById('sumCostRate');
    const sumDueAmount = document.getElementById('sumDueAmount');

    const bankDetailsSection = document.getElementById('bankDetailsSection');
    const bankSelect = document.getElementById('bankSelect');

    let currentPurchases = [];
    let selectedPurchaseData = null;

    // Initialize Select2 with searchbar
    if ($.fn.select2) {
        $vendorSelect.select2({
            width: '100%'
        });

        $purchaseSelect.select2({
            width: '100%'
        });
    }

    // Refund type card switching
    document.querySelectorAll('.settlement-card').forEach(card => {
        card.addEventListener('click', function () {
            document.querySelectorAll('.settlement-card').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                handleRefundTypeChange(radio.value);
            }
        });
    });

    document.querySelectorAll('input[name="refund_type"]').forEach(radio => {
        radio.addEventListener('change', function () {
            handleRefundTypeChange(this.value);
        });
    });

    function handleRefundTypeChange(type) {
        if (type === 'bank_refund') {
            bankDetailsSection.style.display = 'block';
            bankSelect.required = true;
        } else {
            bankDetailsSection.style.display = 'none';
            bankSelect.required = false;
        }
    }

    // Load purchases when vendor is chosen
    function loadVendorPurchases(vendorId, autoSelectPurchaseId = null) {
        if (!vendorId) {
            $purchaseSelect.html('<option value="">-- Choose Vendor First --</option>');
            if ($.fn.select2) $purchaseSelect.trigger('change.select2');
            consignmentSummaryCard.style.display = 'none';
            selectedPurchaseData = null;
            recalcTotal();
            return;
        }

        $purchaseSelect.html('<option value="">Loading available in-stock consignments...</option>');
        if ($.fn.select2) $purchaseSelect.trigger('change.select2');

        fetch("{{ url('purchase-returns/ajax/vendor-purchases') }}/" + vendorId)
            .then(res => res.json())
            .then(data => {
                currentPurchases = data;
                if (!data || data.length === 0) {
                    $purchaseSelect.html('<option value="">No consignments with remaining stock found for this vendor</option>');
                    if ($.fn.select2) $purchaseSelect.trigger('change.select2');
                    consignmentSummaryCard.style.display = 'none';
                    selectedPurchaseData = null;
                    return;
                }

                let html = '<option value="">-- Select Consignment Item (' + data.length + ' available) --</option>';
                data.forEach(item => {
                    const selected = (autoSelectPurchaseId && autoSelectPurchaseId == item.id) ? 'selected' : '';
                    const coilText = item.coil_number ? ' | Coil: ' + item.coil_number : '';
                    html += `<option value="${item.id}" ${selected}>#PO-${item.id} | ${item.specs || item.product_name}${coilText} | In-Stock: ${Number(item.remaining_weight).toLocaleString()} kg | Rate: ৳${item.unit_price}</option>`;
                });
                $purchaseSelect.html(html);
                if ($.fn.select2) $purchaseSelect.trigger('change.select2');

                if (autoSelectPurchaseId) {
                    $purchaseSelect.val(autoSelectPurchaseId);
                    if ($.fn.select2) $purchaseSelect.trigger('change.select2');
                    onPurchaseSelected(autoSelectPurchaseId);
                }
            })
            .catch(err => {
                console.error(err);
                $purchaseSelect.html('<option value="">Error loading consignments</option>');
                if ($.fn.select2) $purchaseSelect.trigger('change.select2');
            });
    }

    $vendorSelect.on('change select2:select', function () {
        loadVendorPurchases($(this).val());
    });

    $vendorSelect.on('select2:clear', function () {
        loadVendorPurchases('');
    });

    $purchaseSelect.on('change select2:select', function () {
        onPurchaseSelected($(this).val());
    });

    $purchaseSelect.on('select2:clear', function () {
        onPurchaseSelected('');
    });

    function onPurchaseSelected(purchaseId) {
        if (!purchaseId) {
            consignmentSummaryCard.style.display = 'none';
            selectedPurchaseData = null;
            btnReturnAll.style.display = 'none';
            stockLimitHelp.textContent = 'Enter weight to return to vendor.';
            recalcTotal();
            return;
        }

        const found = currentPurchases.find(p => p.id == purchaseId);
        if (!found) return;

        selectedPurchaseData = found;

        // Populate summary box
        sumCoilTag.textContent = found.coil_number || 'Direct Consignment';
        sumSpecs.textContent = found.specs || found.product_name || 'Standard Steel';
        sumIntakeWeight.textContent = Number(found.total_weight).toLocaleString() + ' kg';
        sumAvailableStock.textContent = Number(found.remaining_weight).toLocaleString() + ' kg';
        sumWarehouse.textContent = found.warehouse_name;
        sumCostRate.textContent = '৳ ' + Number(found.unit_price).toFixed(2);
        sumDueAmount.textContent = '৳ ' + Number(found.due).toFixed(2);

        // Pre-fill unit price
        unitPriceInput.value = Number(found.unit_price).toFixed(2);

        // Update return weight limits
        returnWeightInput.max = found.remaining_weight;
        allWeightLabel.textContent = Number(found.remaining_weight).toLocaleString();
        btnReturnAll.style.display = 'inline-block';
        stockLimitHelp.innerHTML = `<span class="text-primary fw-semibold">Maximum returnable in-stock: ${Number(found.remaining_weight).toLocaleString()} kg</span>`;

        consignmentSummaryCard.style.display = 'block';

        recalcTotal();
    }

    btnReturnAll.addEventListener('click', function () {
        if (selectedPurchaseData) {
            returnWeightInput.value = selectedPurchaseData.remaining_weight;
            recalcTotal();
        }
    });

    returnWeightInput.addEventListener('input', function () {
        if (selectedPurchaseData && parseFloat(this.value) > parseFloat(selectedPurchaseData.remaining_weight)) {
            this.classList.add('is-invalid');
            stockLimitHelp.innerHTML = `<span class="text-danger fw-bold">Cannot exceed available in-stock weight of ${Number(selectedPurchaseData.remaining_weight).toLocaleString()} kg!</span>`;
        } else {
            this.classList.remove('is-invalid');
            if (selectedPurchaseData) {
                stockLimitHelp.innerHTML = `<span class="text-primary fw-semibold">Maximum returnable in-stock: ${Number(selectedPurchaseData.remaining_weight).toLocaleString()} kg</span>`;
            }
        }
        recalcTotal();
    });

    unitPriceInput.addEventListener('input', recalcTotal);

    function recalcTotal() {
        const wt = parseFloat(returnWeightInput.value) || 0;
        const rate = parseFloat(unitPriceInput.value) || 0;
        const total = (wt * rate).toFixed(2);

        calcBreakdown.textContent = `${wt.toLocaleString()} kg × ৳${rate.toFixed(2)}`;
        totalRefundDisplay.textContent = '৳ ' + Number(total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    // Initial load if vendor or purchase is passed in query/old
    @if(old('vendor_id', $selectedVendorId))
        const initialVendor = "{{ old('vendor_id', $selectedVendorId) }}";
        const initialPurchase = "{{ old('purchase_id', $selectedPurchase?->id) }}";
        $vendorSelect.val(initialVendor);
        if ($.fn.select2) $vendorSelect.trigger('change.select2');
        loadVendorPurchases(initialVendor, initialPurchase);
    @endif
});
</script>
@endpush

