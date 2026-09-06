@extends('frontend.layouts.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: 38px !important;
            border: 1px solid #ced4da !important;
            border-radius: 6px !important;
            display: flex !important;
            align-items: center !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }
        .summary-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
        }
        .badge-soft-success {
            background-color: rgba(25, 135, 84, 0.12) !important;
            color: #198754 !important;
        }
        .badge-soft-warning {
            background-color: rgba(255, 193, 7, 0.15) !important;
            color: #b58105 !important;
        }
        .badge-soft-danger {
            background-color: rgba(220, 53, 69, 0.12) !important;
            color: #dc3545 !important;
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
                        <i class="fe fe-edit text-primary me-2"></i>Edit Purchase Order #PO-{{ $purchase->id }}
                    </h4>
                    <!-- <p class="text-muted small mb-0">
                        Lot: <span class="fw-semibold text-dark">{{ $purchase->lot ? $purchase->lot->lot_number : 'Direct Stock' }}</span>
                        • Recorded on {{ $purchase->created_at ? $purchase->created_at->format('d M Y, h:i A') : 'N/A' }}
                        • By: <span class="text-secondary fw-semibold">{{ $purchase->creator ? $purchase->creator->name : 'Admin' }}</span>
                    </p> -->
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('purchase.show', $purchase->id) }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                        <i class="fe fe-eye me-1"></i>View Details
                    </a>
                    <a href="{{ route('purchase.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 shadow-sm">
                        <i class="fe fe-arrow-left me-1"></i>Back to Purchases
                    </a>
                </div>
            </div>
        </div>
        <!-- /Page Header -->

        @php
            $coil = $purchase->coils->first();
            $soldWeight = 0;
            if ($coil) {
                $soldWeight = max(0, (float)$coil->net_weight - (float)$coil->remaining_weight);
            }
        @endphp

        <!-- Sold Weight Protection Banner -->
        @if($soldWeight > 0)
            <div class="alert alert-warning border-warning d-flex align-items-center gap-3 rounded-3 shadow-sm mb-4" role="alert">
                <i class="fe fe-alert-triangle fs-3 text-warning flex-shrink-0"></i>
                <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1 text-dark">Partial Steel Stock Dispatched</h6>
                    <p class="mb-0 small text-secondary">
                        This purchase item's coil (<strong>{{ $coil->coil_number }}</strong>) has already had 
                        <strong class="text-danger">{{ number_format($soldWeight, 2) }} kg</strong> sold and dispatched. 
                        Its remaining yard stock is <strong class="text-success">{{ number_format($coil->remaining_weight, 2) }} kg</strong>. 
                        Total intake weight cannot be adjusted below the sold quantity.
                    </p>
                </div>
            </div>
        @endif

        <form action="{{ route('purchase.update', $purchase->id) }}" method="POST" id="editPurchaseForm">
            @csrf
            @method('PUT')

            <!-- Section 1: Lot, Vendor & Yard Location -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                        <i class="fe fe-anchor text-primary me-2"></i>Lot & Stockyard Information
                    </h6>

                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6 col-12">
                            <label for="lot_id" class="form-label fw-semibold small text-secondary mb-1">
                                Purchase Lot <span class="text-danger">*</span>
                            </label>
                            <select id="lot_id" name="lot_id" class="form-select select2" required>
                                <option value="">Select Purchase Lot</option>
                                @foreach ($lots as $lot)
                                    <option value="{{ $lot->id }}" 
                                        data-vendor-id="{{ $lot->vendor_id }}"
                                        data-vendor-name="{{ $lot->vendor ? $lot->vendor->name : 'No Vendor' }}"
                                        {{ old('lot_id', $purchase->lot_id) == $lot->id ? 'selected' : '' }}>
                                        {{ $lot->lot_number }} — {{ $lot->vendor ? $lot->vendor->name : 'Lot' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Vendor / Supplier
                            </label>
                            <input type="text" id="vendor_display" class="form-control bg-light" readonly
                                value="{{ $purchase->vendor ? $purchase->vendor->name : '' }}">
                            <input type="hidden" name="vendor_id" id="vendor_hidden" value="{{ old('vendor_id', $purchase->vendor_id) }}">
                        </div>

                        <div class="col-lg-3 col-md-6 col-12">
                            <label for="warehouse_id" class="form-label fw-semibold small text-secondary mb-1">
                                Stockyard Location <span class="text-danger">*</span>
                            </label>
                            <select id="warehouse_id" name="warehouse_id" class="form-select select2" required>
                                <option value="">Select Stockyard Depot</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}" {{ old('warehouse_id', $purchase->warehouse_id) == $wh->id ? 'selected' : '' }}>
                                        {{ $wh->name }} {{ $wh->code ? '('.$wh->code.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-6 col-12">
                            <label for="purchase_date" class="form-label fw-semibold small text-secondary mb-1">
                                Intake Date
                            </label>
                            <input type="date" name="purchase_date" id="purchase_date" class="form-control"
                                value="{{ old('purchase_date', $purchase->created_at ? $purchase->created_at->format('Y-m-d') : date('Y-m-d')) }}">
                        </div>

                        <div class="col-12 mt-2">
                            <label for="notes" class="form-label fw-semibold small text-secondary mb-1">
                                Shipment / Procurement Notes
                            </label>
                            <input type="text" name="notes" id="notes" class="form-control"
                                value="{{ old('notes', $purchase->notes ?? $purchase->note) }}" placeholder="Optional procurement remarks...">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Physical Specifications & Coil Inventory (2-Line Layout) -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fe fe-disc text-primary me-2"></i>Physical Steel Specifications & Coil Details
                        </h6>
                        <small class="text-muted">Line 1: Physical specifications & dimensions | Line 2: Weights, unit rate & line subtotal</small>
                    </div>
                    @if($coil)
                        <span class="badge bg-dark rounded-pill px-3 py-2 fs-7 fw-bold font-monospace">
                            <i class="fe fe-tag me-1 text-primary"></i>{{ $coil->coil_number }}
                        </span>
                    @endif
                </div>

                <div class="card-body p-3 bg-light-subtle">
                    <div class="steel-row bg-white border rounded-3 p-3 shadow-sm position-relative">
                        <!-- Header / Item Index Bar -->
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary rounded-pill px-2.5 py-1 fs-8 fw-bold">Steel Item Details</span>
                                @if($coil)
                                    <span class="text-muted small">
                                        Current Yard Status: 
                                        @if($coil->status === 'exhausted' || $coil->remaining_weight <= 0)
                                            <span class="badge badge-soft-danger">Exhausted (0 kg)</span>
                                        @else
                                            <span class="badge badge-soft-success">In Stock ({{ number_format($coil->remaining_weight, 2) }} kg remaining)</span>
                                        @endif
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Line 1: Physical Specifications & Dimensions -->
                        <div class="row g-2 mb-2">
                            <div class="col-lg-2 col-md-3 col-6">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Coil / Piece Qty <span class="text-danger">*</span>
                                </label>
                                <input oninput="calculateRow()" type="number" min="1" step="1" name="quantity" id="quantityInput"
                                    class="form-control form-control-sm text-center fw-bold"
                                    value="{{ old('quantity', (int)$purchase->quantity ?: 1) }}" required>
                            </div>

                            <div class="col-lg-2 col-md-3 col-6">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Thickness
                                </label>
                                <input type="text" name="thickness" id="thicknessInput"
                                    class="form-control form-control-sm text-center"
                                    value="{{ old('thickness', $purchase->thickness ?? ($coil->thickness ?? '')) }}" placeholder="e.g. 10mm">
                            </div>

                            <div class="col-lg-3 col-md-3 col-6">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Size / Width
                                </label>
                                <input type="text" name="size" id="sizeInput"
                                    class="form-control form-control-sm text-center"
                                    value="{{ old('size', $purchase->size ?? ($coil->width ?? '')) }}" placeholder="e.g. 4x8 ft or 1250mm">
                            </div>

                            <div class="col-lg-2 col-md-3 col-6">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Size Type
                                </label>
                                <select name="size_type" id="sizeTypeInput" class="form-select form-select-sm">
                                    @php
                                        $currentSizeType = old('size_type', $purchase->size_type ?? ($coil->length ?? 'ft'));
                                    @endphp
                                    <option value="ft" {{ $currentSizeType === 'ft' ? 'selected' : '' }}>Feet (ft)</option>
                                    <option value="mm" {{ $currentSizeType === 'mm' ? 'selected' : '' }}>Millimeter (mm)</option>
                                    <option value="inch" {{ $currentSizeType === 'inch' ? 'selected' : '' }}>Inch (in)</option>
                                    <option value="m" {{ $currentSizeType === 'm' ? 'selected' : '' }}>Meter (m)</option>
                                    <option value="pcs" {{ $currentSizeType === 'pcs' ? 'selected' : '' }}>Pieces (pcs)</option>
                                    <option value="ton" {{ $currentSizeType === 'ton' ? 'selected' : '' }}>Ton</option>
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-12 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Notes (Optional)
                                </label>
                                <input type="text" name="coil_notes" id="coilNotesInput"
                                    class="form-control form-control-sm"
                                    value="{{ old('coil_notes', $coil->notes ?? '') }}" placeholder="Optional grade/tag">
                            </div>
                        </div>

                        <!-- Line 2: Weights, Rates & Subtotal (Financials) -->
                        <div class="row g-2 align-items-end p-2.5 rounded-2 bg-light border border-light-subtle">
                            <div class="col-lg-3 col-md-6 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Per Coil Wt (kg) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <input oninput="calculateRow()" type="number" step="0.001" min="0.001" name="unit_weight" id="unitWeightInput"
                                        class="form-control text-end fw-bold text-primary"
                                        value="{{ old('unit_weight', $purchase->unit_weight) }}" placeholder="0.000" required>
                                    <span class="input-group-text bg-white text-muted">kg</span>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Total Weight (kg)
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.001" name="total_weight" id="totalWeightInput"
                                        class="form-control bg-white text-end fw-bold text-dark"
                                        value="{{ old('total_weight', $purchase->total_weight) }}" placeholder="0.000" readonly>
                                    <span class="input-group-text bg-white text-muted">kg</span>
                                </div>
                                <input type="hidden" id="minAllowedWeight" value="{{ $soldWeight }}">
                            </div>

                            <div class="col-lg-3 col-md-6 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Unit Rate (৳) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-muted">৳</span>
                                    <input oninput="calculateRow()" type="number" step="0.01" min="0" name="unit_price" id="unitPriceInput"
                                        class="form-control text-end fw-semibold"
                                        value="{{ old('unit_price', $purchase->unit_price) }}" placeholder="0.00" required>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Line Sub Total (৳)
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-muted">৳</span>
                                    <input type="number" step="0.01" name="sub_price" id="subPriceInput"
                                        class="form-control bg-white text-end fw-bold text-success"
                                        value="{{ old('sub_price', $purchase->sub_price) }}" placeholder="0.00" readonly>
                                </div>
                                <input type="hidden" name="total_price" id="totalPriceInput" value="{{ old('total_price', $purchase->total_price) }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2.5: Extra Procurement Charges & Adjustments -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fe fe-dollar-sign text-primary me-2"></i>Procurement Charges & Financial Adjustments
                        </h6>
                        <small class="text-muted">Transport freight, yard crane/labour fees, weighbridge scale slips, and vendor discounts</small>
                    </div>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Steel Sub Total (৳)</label>
                            <input type="text" id="purchaseSubTotalDisplay" class="form-control border-light-subtle bg-light text-end fw-bold text-dark" readonly value="{{ number_format($purchase->sub_price, 2) }}">
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Discount Amount (৳)</label>
                            <input oninput="calculateFinancials()" onchange="calculateFinancials()" type="number" id="discount" name="discount" class="form-control border-light-subtle text-end" value="{{ old('discount', $purchase->discount ?? 0) }}" min="0" step="0.01" placeholder="0.00">
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Delivery / Transport (৳)</label>
                            <input oninput="calculateFinancials()" onchange="calculateFinancials()" type="number" id="delivery_charge" name="delivery_charge" class="form-control border-light-subtle text-end" value="{{ old('delivery_charge', $purchase->delivery_charge ?? 0) }}" min="0" step="0.01" placeholder="0.00">
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Cutting & Labour (৳)</label>
                            <input oninput="calculateFinancials()" onchange="calculateFinancials()" type="number" id="labour_cost" name="labour_cost" class="form-control border-light-subtle text-end" value="{{ old('labour_cost', $purchase->labour_cost ?? 0) }}" min="0" step="0.01" placeholder="0.00">
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Scale / Weighbridge (৳)</label>
                            <input oninput="calculateFinancials()" onchange="calculateFinancials()" type="number" id="weight_scale_cost" name="weight_scale_cost" class="form-control border-light-subtle text-end" value="{{ old('weight_scale_cost', $purchase->weight_scale_cost ?? 0) }}" min="0" step="0.01" placeholder="0.00">
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Other Charges (৳)</label>
                            <input oninput="calculateFinancials()" onchange="calculateFinancials()" type="number" id="other_charges" name="other_charges" class="form-control border-light-subtle text-end" value="{{ old('other_charges', $purchase->other_charges ?? 0) }}" min="0" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Summary & Payment Settlement -->
            <div class="row g-4 mb-4">
                <!-- Left: Intake Batch Stats Summary -->
                <div class="col-lg-7 col-12">
                    <div class="card border-0 shadow-sm rounded-3 h-100 summary-card">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="fe fe-check-square text-primary me-2"></i>Purchase Summary
                            </h6>
                            <div class="row g-3 text-center">
                                <div class="col-sm-3 col-6">
                                    <div class="bg-white p-3 rounded-3 shadow-sm border border-light-subtle h-100">
                                        <span class="text-secondary small d-block mb-1">Coil Quantity</span>
                                        <span class="fs-4 fw-bold text-dark" id="displayTotalCoils">{{ (int)$purchase->quantity ?: 1 }}</span>
                                    </div>
                                </div>
                                <div class="col-sm-3 col-6">
                                    <div class="bg-white p-3 rounded-3 shadow-sm border border-light-subtle h-100">
                                        <span class="text-secondary small d-block mb-1">Intake Weight</span>
                                        <span class="fs-5 fw-bold text-primary">
                                            <span id="displayTotalWeight">{{ number_format($purchase->total_weight, 2) }}</span> 
                                            <small class="fs-8 text-muted">kg</small>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-sm-3 col-6">
                                    <div class="bg-white p-3 rounded-3 shadow-sm border border-light-subtle h-100">
                                        <span class="text-secondary small d-block mb-1">Steel Sub Total</span>
                                        <span class="fs-6 fw-bold text-dark" id="displaySubTotal">৳ {{ number_format($purchase->sub_price, 2) }}</span>
                                        <small class="text-muted fs-8 d-block mt-0.5">Charges: <span id="displayTotalCharges" class="text-primary fw-semibold">৳ {{ number_format($purchase->total_extra_charges, 2) }}</span></small>
                                    </div>
                                </div>
                                <div class="col-sm-3 col-6">
                                    <div class="bg-white p-3 rounded-3 shadow-sm border border-light-subtle h-100">
                                        <span class="text-secondary small d-block mb-1">Payable Bill</span>
                                        <span class="fs-6 fw-bold text-dark" id="displayGrandTotal">৳ {{ number_format($purchase->total_price, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Payment & Balance Settlement -->
                <div class="col-lg-5 col-12">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="fe fe-dollar-sign text-success me-2"></i>Payment Settlement
                            </h6>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="paymentInput" class="form-label fw-semibold small text-secondary mb-0">
                                        Paid Amount (৳) <span class="text-danger">*</span>
                                    </label>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold fs-8" onclick="setFullPayment()">
                                        Pay Full Bill
                                    </button>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-success fw-bold">৳</span>
                                    <input type="number" step="0.01" min="0" name="payment" id="paymentInput"
                                        class="form-control form-control-lg fw-bold text-success"
                                        value="{{ old('payment', $purchase->payment) }}" oninput="calculateFinancials()" required>
                                </div>
                                <div id="paymentErrorMsg" class="text-danger small mt-1 fw-semibold" style="display: none;">
                                    <i class="fe fe-alert-triangle me-1"></i> Payment cannot exceed the total bill of <span id="maxBillFormatted">৳ 0.00</span>.
                                </div>
                            </div>

                            <!-- Settlement Payment Method & Channel -->
                            <div class="mb-3 p-3 bg-light rounded-3 border border-light-subtle">
                                <label class="form-label fw-semibold small text-secondary mb-1">
                                    <i class="fe fe-credit-card me-1 text-primary"></i> Settlement Payment Method
                                </label>
                                <select name="payment_method" id="purchasePaymentMethod" class="form-select border-light-subtle mb-2" onchange="handlePurchasePaymentMethodChange(this.value)">
                                    <option value="cash" {{ old('payment_method', $purchase->payment_method ?? 'cash') === 'cash' ? 'selected' : '' }}>Cash in Hand</option>
                                    <option value="bank" {{ old('payment_method', $purchase->payment_method) === 'bank' ? 'selected' : '' }}>Bank Transfer / MFS</option>
                                </select>

                                <!-- Bank Account Selector -->
                                <div id="purchaseBankAccountContainer" class="mb-2" style="{{ old('payment_method', $purchase->payment_method) === 'bank' ? '' : 'display: none;' }}">
                                    <label class="form-label fw-semibold small text-secondary mb-1">Disbursement Bank Account</label>
                                    <select name="bank_detail_id" id="purchaseBankDetail" class="form-select border-light-subtle">
                                        <option value="">Select Bank Account</option>
                                        @foreach($bankAccounts ?? [] as $bank)
                                            <option value="{{ $bank->id }}" {{ old('bank_detail_id', $purchase->bank_detail_id) == $bank->id ? 'selected' : '' }}>
                                                {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Transaction Ref -->
                                <div id="purchaseTransactionRefContainer" style="{{ old('payment_method', $purchase->payment_method) === 'bank' ? '' : 'display: none;' }}">
                                    <label class="form-label fw-semibold small text-secondary mb-1">Transaction Ref / TrxID</label>
                                    <input type="text" name="transaction_ref" class="form-control border-light-subtle bg-white"
                                        value="{{ old('transaction_ref', $purchase->transaction_ref) }}" placeholder="e.g. Cheque / TrxID">
                                </div>
                            </div>

                            <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center mb-4">
                                <span class="fw-semibold text-secondary">Outstanding Due:</span>
                                <span class="fs-5 fw-bold text-danger" id="displayDueAmount">৳ {{ number_format($purchase->due, 2) }}</span>
                                <input type="hidden" name="due" id="due_hidden" value="{{ $purchase->due }}">
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 shadow-sm fw-bold fs-6 d-flex align-items-center justify-content-center gap-2" id="submitPurchaseBtn">
                                <i class="fe fe-save"></i>
                                <span>Save Changes & Update Inventory</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('.select2').select2({ width: '100%' });

            $('#lot_id').on('change select2:select', function () {
                const selectedOption = $(this).find('option:selected');
                const vendorId = selectedOption.data('vendor-id') || '';
                const vendorName = selectedOption.data('vendor-name') || '';
                $('#vendor_hidden').val(vendorId);
                $('#vendor_display').val(vendorName || 'No Vendor Linked');
            });

            calculateRow();
            handlePurchasePaymentMethodChange($('#purchasePaymentMethod').val());
        });

        function calculateRow() {
            const qty = parseInt($('#quantityInput').val()) || 1;
            const unitWeight = parseFloat($('#unitWeightInput').val()) || 0;
            const totalWeight = qty * unitWeight;
            const rate = parseFloat($('#unitPriceInput').val()) || 0;
            const subTotal = totalWeight * rate;

            $('#totalWeightInput').val(totalWeight > 0 ? totalWeight.toFixed(2) : '0.00');
            $('#subPriceInput').val(subTotal > 0 ? subTotal.toFixed(2) : '0.00');
            $('#purchaseSubTotalDisplay').val(subTotal.toFixed(2));

            $('#displayTotalCoils').text(qty);
            $('#displayTotalWeight').text(totalWeight.toFixed(2));

            calculateFinancials();
        }

        function calculateFinancials() {
            const subTotal = parseFloat($('#subPriceInput').val()) || 0;
            const discount = parseFloat($('#discount').val()) || 0;
            const delivery = parseFloat($('#delivery_charge').val()) || 0;
            const labour = parseFloat($('#labour_cost').val()) || 0;
            const scale = parseFloat($('#weight_scale_cost').val()) || 0;
            const other = parseFloat($('#other_charges').val()) || 0;

            const totalCharges = delivery + labour + scale + other;
            const grandTotal = Math.max(0, (subTotal + totalCharges) - discount);

            $('#totalPriceInput').val(grandTotal.toFixed(2));
            $('#purchaseSubTotalDisplay').val(subTotal.toFixed(2));

            const formattedSubTotal = subTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            const formattedTotalCharges = totalCharges.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            const formattedGrandTotal = grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            $('#displaySubTotal').text('৳ ' + formattedSubTotal);
            $('#displayTotalCharges').text('৳ ' + formattedTotalCharges);
            $('#displayGrandTotal').text('৳ ' + formattedGrandTotal);
            $('#maxBillFormatted').text('৳ ' + formattedGrandTotal);

            const paymentInput = $('#paymentInput');
            const payment = parseFloat(paymentInput.val()) || 0;

            if (payment > grandTotal + 0.009) {
                $('#paymentErrorMsg').fadeIn(150);
                paymentInput.addClass('is-invalid border-danger');
                $('#displayDueAmount').text('৳ 0.00');
                $('#due_hidden').val('0.00');
            } else {
                $('#paymentErrorMsg').hide();
                paymentInput.removeClass('is-invalid border-danger');
                const due = Math.max(0, grandTotal - payment);
                $('#displayDueAmount').text('৳ ' + due.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#due_hidden').val(due.toFixed(2));
            }
        }

        function setFullPayment() {
            const grandTotal = parseFloat($('#totalPriceInput').val()) || 0;
            $('#paymentInput').val(grandTotal.toFixed(2));
            calculateFinancials();
        }

        function handlePurchasePaymentMethodChange(method) {
            const bankContainer = document.getElementById('purchaseBankAccountContainer');
            const refContainer = document.getElementById('purchaseTransactionRefContainer');
            const bankSelect = document.getElementById('purchaseBankDetail');
            const refInput = document.querySelector('input[name="transaction_ref"]');
            if (!bankContainer || !refContainer) return;

            if (method === 'cash') {
                bankContainer.style.display = 'none';
                refContainer.style.display = 'none';
                if (bankSelect) {
                    bankSelect.value = '';
                    bankSelect.disabled = true;
                }
                if (refInput) {
                    refInput.value = '';
                    refInput.disabled = true;
                }
            } else {
                bankContainer.style.display = 'block';
                refContainer.style.display = 'block';
                if (bankSelect) {
                    bankSelect.disabled = false;
                }
                if (refInput) {
                    refInput.disabled = false;
                }
            }
        }

        $('#editPurchaseForm').on('submit', function (e) {
            const totalWeight = parseFloat($('#totalWeightInput').val()) || 0;
            const minAllowedWeight = parseFloat($('#minAllowedWeight').val()) || 0;
            const grandTotal = parseFloat($('#totalPriceInput').val()) || 0;
            const payment = parseFloat($('#paymentInput').val()) || 0;

            if (minAllowedWeight > 0 && totalWeight < minAllowedWeight) {
                e.preventDefault();
                alert(`Cannot reduce total weight below ${minAllowedWeight.toFixed(2)} kg because this amount has already been sold and dispatched to customers.`);
                $('#unitWeightInput').focus();
                return false;
            }

            if (grandTotal <= 0) {
                e.preventDefault();
                alert('Please enter valid quantities, weights, and rates for the steel purchase.');
                return false;
            }

            if (payment > grandTotal + 0.009) {
                e.preventDefault();
                $('#paymentErrorMsg').show();
                $('#paymentInput').addClass('is-invalid border-danger').focus();
                alert('Payment amount cannot exceed the total purchase bill. Please adjust the payment.');
                return false;
            }
        });
    </script>
@endpush
