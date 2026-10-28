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
    .pos-card-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 10px 14px;
        font-weight: 700;
        font-size: 13.5px;
        color: #1e293b;
    }
    .sticky-summary {
        position: sticky;
        top: 15px;
        z-index: 10;
        max-height: calc(100vh - 35px);
        overflow-y: auto;
    }
    .sticky-summary::-webkit-scrollbar {
        width: 4px;
    }
    .sticky-summary::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 4px;
    }
    .grand-total-display {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        color: #ffffff;
        border-radius: 12px;
        padding: 14px;
    }
    .badge-soft-success { background-color: rgba(25, 135, 84, 0.12) !important; color: #198754 !important; }
    .badge-soft-danger { background-color: rgba(220, 53, 69, 0.12) !important; color: #dc3545 !important; }
    .badge-soft-warning { background-color: rgba(255, 193, 7, 0.15) !important; color: #b58105 !important; }
    .badge-soft-info { background-color: rgba(13, 202, 240, 0.12) !important; color: #0dcaf0 !important; }
    .fs-8 { font-size: 0.8rem; }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header (No Breadcrumbs) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h4 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-edit text-primary me-2"></i>Edit Shop Purchase Order #PO-{{ $purchase->id }}
                    </h4>
                    <span class="badge bg-primary text-white px-2.5 py-1 rounded-pill">
                        <i class="fe fe-shopping-cart me-1"></i>{{ $shop->name }}
                    </span>
                </div>
                <p class="text-muted small mb-0">Update intake specifications, consignment weights, vendor charges, and payments for retail shop stock</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('purchase.show', $purchase->id) }}" class="btn btn-outline-info px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="fe fe-eye"></i>
                    <span>View Details</span>
                </a>
                <a href="{{ route('shops.purchases.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back to Shop Purchases</span>
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

    <!-- Sold Weight Protection Alert Banner -->
    @if($soldWeight > 0)
        <div class="alert alert-warning border-warning d-flex align-items-center gap-3 rounded-3 shadow-sm mb-4" role="alert">
            <i class="fe fe-alert-triangle fs-3 text-warning flex-shrink-0"></i>
            <div class="flex-grow-1">
                <h6 class="fw-bold mb-1 text-dark">Partial Shop Steel Stock Dispatched</h6>
                <p class="mb-0 small text-secondary">
                    This shop purchase item's coil (<strong>{{ $coil->coil_number }}</strong>) has already had 
                    <strong class="text-danger">{{ number_format($soldWeight, 2) }} kg</strong> sold and dispatched. 
                    Its remaining shop stock is <strong class="text-success">{{ number_format($coil->remaining_weight, 2) }} kg</strong>. 
                    Total intake weight cannot be adjusted below the sold quantity.
                </p>
            </div>
        </div>
    @endif

    <form action="{{ route('shops.purchases.update', $purchase->id) }}" method="POST" id="editShopPurchaseForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="warehouse_id" id="warehouse_id" value="{{ $shop->id }}">

        <div class="row g-4">

            <!-- LEFT COLUMN (col-lg-8): Lot Details, Specifications & Charges -->
            <div class="col-lg-8 col-12">

                <!-- 1. Lot & Vendor Intake Setup Card -->
                <div class="card border-0 shadow-sm rounded-3 mb-4 p-2">
                    <div class="pos-card-header rounded-top-3 d-flex justify-content-between align-items-center">
                        <span><i class="fe fe-anchor text-primary me-2"></i>Lot &amp; Shop Intake Details</span>
                        <span class="badge bg-light text-secondary border px-2.5 py-1 rounded-pill fs-8">
                            Shop: {{ $shop->name }}
                        </span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-md-4 col-12">
                                <label for="lot_id" class="form-label small text-secondary fw-semibold mb-1">
                                    Intake Lot <span class="text-danger">*</span>
                                </label>
                                <select id="lot_id" name="lot_id" class="form-select select2" required onchange="handleLotChange(this)">
                                    <option value="">Select Lot</option>
                                    @foreach ($lots as $lot)
                                        <option value="{{ $lot->id }}" 
                                            data-vendor-id="{{ $lot->vendor_id }}"
                                            data-vendor-name="{{ $lot->vendor ? $lot->vendor->name : '' }}"
                                            {{ old('lot_id', $purchase->lot_id) == $lot->id ? 'selected' : '' }}>
                                            {{ $lot->lot_number }} {{ $lot->vendor ? '— ' . $lot->vendor->name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 col-12">
                                <label for="vendor_id" class="form-label small text-secondary fw-semibold mb-1">
                                    Vendor / Supplier <span class="text-danger">*</span>
                                </label>
                                <select id="vendor_id" name="vendor_id" class="form-select select2" required>
                                    <option value="">Select Vendor</option>
                                    @foreach ($vendors as $vendor)
                                        <option value="{{ $vendor->id }}" {{ old('vendor_id', $purchase->vendor_id) == $vendor->id ? 'selected' : '' }}>
                                            {{ $vendor->name }} ({{ $vendor->phone }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 col-12">
                                <label for="purchase_date" class="form-label small text-secondary fw-semibold mb-1">
                                    Intake Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="purchase_date" id="purchase_date" class="form-control"
                                    value="{{ old('purchase_date', $purchase->created_at ? $purchase->created_at->format('Y-m-d') : date('Y-m-d')) }}" required>
                            </div>

                            <div class="col-12 mt-2">
                                <label for="notes" class="form-label small text-secondary fw-semibold mb-1">
                                    Shipment / Intake Notes <span class="text-muted">(Optional)</span>
                                </label>
                                <input type="text" name="notes" id="notes" class="form-control"
                                    value="{{ old('notes', $purchase->notes ?? $purchase->note) }}" placeholder="Truck receipt #, intake supervisor notes...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Physical Specifications & Dimensions Card -->
                <div class="card border-0 shadow-sm rounded-3 mb-4 p-2">
                    <div class="pos-card-header rounded-top-3 d-flex justify-content-between align-items-center">
                        <span><i class="fe fe-disc text-primary me-2"></i>Physical Steel Specifications &amp; Stock Details</span>
                        @if($coil)
                            <span class="badge bg-dark rounded-pill px-3 py-1 fs-8 fw-bold font-monospace">
                                <i class="fe fe-tag me-1 text-primary"></i>{{ $coil->coil_number }}
                            </span>
                        @endif
                    </div>
                    <div class="card-body p-3">

                        <!-- Line 1: Physical Dimensions -->
                        <div class="row g-2 mb-3">
                            <div class="col-lg-2 col-md-3 col-6">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Coil / Piece Qty <span class="text-danger">*</span>
                                </label>
                                <input oninput="calculateFinancials()" type="number" min="1" step="1" name="quantity" id="quantityInput"
                                    class="form-control form-control-sm text-center fw-bold"
                                    value="{{ old('quantity', (int)$purchase->quantity ?: 1) }}" required>
                            </div>

                            <div class="col-lg-2 col-md-3 col-6">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Thickness
                                </label>
                                <input type="text" name="thickness" id="thicknessInput"
                                    class="form-control form-control-sm text-center"
                                    value="{{ old('thickness', $purchase->thickness ?? ($coil->thickness ?? '')) }}" placeholder="e.g. 0.45mm">
                            </div>

                            <div class="col-lg-3 col-md-3 col-6">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Size / Width
                                </label>
                                <input type="text" name="size" id="sizeInput"
                                    class="form-control form-control-sm text-center"
                                    value="{{ old('size', $purchase->size ?? ($coil->width ?? '')) }}" placeholder="e.g. 3.0ft">
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
                                    Coil Notes (Optional)
                                </label>
                                <input type="text" name="coil_notes" id="coilNotesInput"
                                    class="form-control form-control-sm"
                                    value="{{ old('coil_notes', $coil->notes ?? '') }}" placeholder="Brand, colour, grade...">
                            </div>
                        </div>

                        <!-- Line 2: Weights, Unit Rate & Subtotal -->
                        <div class="row g-2 align-items-end p-2.5 rounded-2 bg-light border border-light-subtle">
                            <div class="col-lg-3 col-md-6 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Per Coil Wt (kg) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <input oninput="calculateFinancials()" type="number" step="0.001" min="0.001" name="unit_weight" id="unitWeightInput"
                                        class="form-control text-end fw-bold text-primary"
                                        value="{{ old('unit_weight', $purchase->unit_weight) }}" required>
                                    <span class="input-group-text bg-white text-muted">kg</span>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Total Intake Weight (kg)
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.001" name="total_weight" id="totalWeightInput"
                                        class="form-control bg-white text-end fw-bold text-dark"
                                        value="{{ old('total_weight', $purchase->total_weight) }}" readonly>
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
                                    <input oninput="calculateFinancials()" type="number" step="0.01" min="0" name="unit_price" id="unitPriceInput"
                                        class="form-control text-end fw-semibold"
                                        value="{{ old('unit_price', $purchase->unit_price) }}" required>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Steel Sub Total (৳)
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-muted">৳</span>
                                    <input type="number" step="0.01" name="sub_price" id="subPriceInput"
                                        class="form-control bg-white text-end fw-bold text-success"
                                        value="{{ old('sub_price', $purchase->sub_price) }}" readonly>
                                </div>
                                <input type="hidden" name="total_price" id="totalPriceInput" value="{{ old('total_price', $purchase->total_price) }}">
                            </div>
                        </div>

                    </div>
                </div>

                <!-- 3. Procurement Charges & Adjustments Card -->
                <div class="card border-0 shadow-sm rounded-3 mb-4 p-2">
                    <div class="pos-card-header rounded-top-3">
                        <span><i class="fe fe-dollar-sign text-success me-1"></i>Procurement Charges &amp; Costs</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small text-secondary fw-semibold mb-0">Delivery / Transport (৳)</label>
                                    <div class="d-inline-flex gap-2 d-none">
                                        <div class="form-check form-check-inline m-0">
                                            <input class="form-check-input" type="radio" name="transport_payer" id="transport_payer_me" value="me" {{ old('transport_payer', $purchase->transport_payer ?? 'me') === 'me' ? 'checked' : '' }} onchange="calculateFinancials()">
                                            <label class="form-check-label small fw-semibold text-muted" for="transport_payer_me" style="font-size: 0.75rem;">Paid by Me</label>
                                        </div>
                                        <div class="form-check form-check-inline m-0">
                                            <input class="form-check-input" type="radio" name="transport_payer" id="transport_payer_vendor" value="vendor" {{ old('transport_payer', $purchase->transport_payer) === 'vendor' ? 'checked' : '' }} onchange="calculateFinancials()">
                                            <label class="form-check-label small fw-semibold text-muted" for="transport_payer_vendor" style="font-size: 0.75rem;">Paid by Vendor</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">৳</span>
                                    <input oninput="calculateFinancials()" type="number" id="delivery_charge" name="delivery_charge" class="form-control text-end" value="{{ old('delivery_charge', $purchase->delivery_charge ?? 0) }}" min="0" step="0.01">
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">Cutting &amp; Load-Unload (৳)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">৳</span>
                                    <input oninput="calculateFinancials()" type="number" id="labour_cost" name="labour_cost" class="form-control text-end" value="{{ old('labour_cost', $purchase->labour_cost ?? 0) }}" min="0" step="0.01">
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">Scale &amp; Labour Charge (৳)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">৳</span>
                                    <input oninput="calculateFinancials()" type="number" id="weight_scale_cost" name="weight_scale_cost" class="form-control text-end" value="{{ old('weight_scale_cost', $purchase->weight_scale_cost ?? 0) }}" min="0" step="0.01">
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">Other Charges (৳)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">৳</span>
                                    <input oninput="calculateFinancials()" type="number" id="other_charges" name="other_charges" class="form-control text-end" value="{{ old('other_charges', $purchase->other_charges ?? 0) }}" min="0" step="0.01">
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">Discount Amount (৳)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">৳</span>
                                    <input oninput="calculateFinancials()" type="number" id="discount" name="discount" class="form-control text-end" value="{{ old('discount', $purchase->discount ?? 0) }}" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN (col-lg-4): Sticky Procurement Financials & Payment Settlement -->
            <div class="col-lg-4 col-12">
                <div class="sticky-summary">

                    <!-- Live KPI Cards -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="p-3 bg-white rounded-3 shadow-sm border text-center">
                                <small class="text-muted text-uppercase fw-semibold fs-8 d-block mb-1">Coil Quantity</small>
                                <h5 class="fw-bold text-dark mb-0" id="displayTotalCoils">{{ (int)$purchase->quantity ?: 1 }}</h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-white rounded-3 shadow-sm border text-center">
                                <small class="text-muted text-uppercase fw-semibold fs-8 d-block mb-1">Intake Weight</small>
                                <h5 class="fw-bold text-primary mb-0"><span id="displayTotalWeight">{{ number_format($purchase->total_weight, 2) }}</span> <small class="fs-8 text-muted">kg</small></h5>
                            </div>
                        </div>
                    </div>

                    <!-- Grand Total Display Card -->
                    <div class="grand-total-display mb-3 text-center shadow-sm">
                        <small class="text-white-50 text-uppercase fw-semibold d-block fs-8 mb-1">Payable Grand Bill (৳)</small>
                        <h3 class="fw-bold mb-0 text-white" id="displayGrandTotal">৳ {{ number_format($purchase->total_price, 2) }}</h3>
                        <small class="text-white-50 fs-8 d-block mt-1">Extra Charges: <span id="displayTotalCharges" class="text-warning">৳ {{ number_format($purchase->total_extra_charges, 2) }}</span></small>
                    </div>

                    <!-- Payment Settlement Card -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="pos-card-header rounded-top-3">
                            <span><i class="fe fe-check-circle text-success me-1"></i>Payment Settlement</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="paymentInput" class="form-label small text-secondary fw-semibold mb-0">Paid Amount (৳) <span class="text-danger">*</span></label>
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
                                <div id="paymentErrorMsg" class="text-danger small mt-1 fw-semibold fs-8" style="display: none;">
                                    <i class="fe fe-alert-triangle me-1"></i> Payment cannot exceed the total bill of <span id="maxBillFormatted">৳ 0.00</span>.
                                </div>
                            </div>

                            @php
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
                                $currMethod = old('payment_method', $purchase->payment_method ?? 'cash');
                                $currBankId = old('bank_detail_id', $purchase->bank_detail_id ?? '');
                            @endphp

                            <!-- Settlement Account Selection -->
                            <div class="p-2.5 bg-light rounded-3 border mb-3">
                                <div class="mb-2">
                                    <label class="form-label fs-8 text-secondary fw-semibold mb-1">
                                        <i class="fe fe-layers me-1 text-primary"></i> Settlement Account / Method
                                    </label>
                                    <select id="shopPurchaseEditAccountSelector" class="form-select form-select-sm" onchange="handleAccountChange(this)">
                                        <optgroup label="Cash">
                                            <option value="cash" data-method="cash" {{ $currMethod === 'cash' ? 'selected' : '' }}>Hand Cash</option>
                                        </optgroup>
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
                                    <input type="hidden" name="payment_method" id="hidden_payment_method" value="{{ $currMethod }}">
                                    <input type="hidden" name="bank_detail_id" id="hidden_bank_detail_id" value="{{ $currBankId }}">
                                </div>

                                <div id="trxRefContainer" style="{{ $currMethod === 'cash' ? 'display: none;' : '' }}">
                                    <label class="form-label fs-8 text-secondary fw-semibold mb-1">TrxID / Cheque Ref</label>
                                    <input type="text" name="transaction_ref" id="transaction_ref" class="form-control form-control-sm bg-white" 
                                        value="{{ old('transaction_ref', $purchase->transaction_ref) }}" placeholder="Transaction Reference #">
                                </div>
                            </div>

                            <!-- Outstanding Due Indicator -->
                            <div class="p-2.5 rounded-3 bg-light border d-flex justify-content-between align-items-center mb-3">
                                <span class="small fw-semibold text-secondary">Remaining Due:</span>
                                <span class="fw-bold text-danger fs-6" id="displayDueAmount">৳ {{ number_format($purchase->due, 2) }}</span>
                                <input type="hidden" name="due" id="dueHiddenInput" value="{{ $purchase->due }}">
                            </div>

                            <!-- Submit & Cancel Buttons -->
                            <div class="d-grid gap-2">
                                <button type="submit" id="submitBtn" class="btn btn-primary btn-lg rounded-3 fw-bold d-inline-flex align-items-center justify-content-center gap-2 shadow-sm">
                                    <span>Update Shop Purchase</span>
                                </button>
                                <a href="{{ route('shops.purchases.index') }}" class="btn btn-outline-secondary rounded-3">
                                    Cancel
                                </a>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

        </div>
    </form>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%'
        });

        calculateFinancials();
    });

    function handleLotChange(selectElement) {
        const selectedOption = $(selectElement).find('option:selected');
        const vendorId = selectedOption.data('vendor-id');
        if (vendorId) {
            $('#vendor_id').val(vendorId).trigger('change');
        }
    }

    function handleAccountChange(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const method = selectedOption.getAttribute('data-method');
        const bankId = selectedOption.getAttribute('data-id') || '';

        document.getElementById('hidden_payment_method').value = method;
        document.getElementById('hidden_bank_detail_id').value = bankId;

        const trxContainer = document.getElementById('trxRefContainer');
        if (method === 'cash') {
            trxContainer.style.display = 'none';
        } else {
            trxContainer.style.display = 'block';
        }
    }

    function calculateFinancials() {
        const qty = Math.max(1, parseInt($('#quantityInput').val(), 10) || 1);
        const unitWeight = parseFloat($('#unitWeightInput').val()) || 0;
        const totalWeight = Math.round((qty * unitWeight) * 1000) / 1000;
        $('#totalWeightInput').val(totalWeight.toFixed(3));
        $('#displayTotalCoils').text(qty);
        $('#displayTotalWeight').text(totalWeight.toFixed(2));

        const rate = parseFloat($('#unitPriceInput').val()) || 0;
        const subPrice = Math.round((totalWeight * rate) * 100) / 100;
        $('#subPriceInput').val(subPrice.toFixed(2));

        const transportPayer = $('input[name="transport_payer"]:checked').val() || 'me';
        const delivery = parseFloat($('#delivery_charge').val()) || 0;
        const vendorDelivery = (transportPayer === 'vendor') ? delivery : 0;
        const labour = parseFloat($('#labour_cost').val()) || 0;
        const scale = parseFloat($('#weight_scale_cost').val()) || 0;
        const other = parseFloat($('#other_charges').val()) || 0;
        const discount = parseFloat($('#discount').val()) || 0;

        const totalExtraCharges = (vendorDelivery + labour + scale + other) - discount;
        const grandTotal = Math.max(0, Math.round((subPrice + totalExtraCharges) * 100) / 100);

        $('#totalPriceInput').val(grandTotal.toFixed(2));
        $('#displayGrandTotal').text('৳ ' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#displayTotalCharges').text('৳ ' + Math.max(0, totalExtraCharges).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#maxBillFormatted').text('৳ ' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        let payment = parseFloat($('#paymentInput').val()) || 0;
        if (payment > grandTotal) {
            $('#paymentErrorMsg').show();
            $('#submitBtn').prop('disabled', true);
        } else {
            $('#paymentErrorMsg').hide();
            $('#submitBtn').prop('disabled', false);
        }

        const due = Math.max(0, Math.round((grandTotal - payment) * 100) / 100);
        $('#dueHiddenInput').val(due.toFixed(2));
        $('#displayDueAmount').text('৳ ' + due.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }

    function setFullPayment() {
        const grandTotal = parseFloat($('#totalPriceInput').val()) || 0;
        $('#paymentInput').val(grandTotal.toFixed(2));
        calculateFinancials();
    }

    $('#editShopPurchaseForm').on('submit', function(e) {
        const minAllowedWeight = parseFloat($('#minAllowedWeight').val()) || 0;
        const currentTotalWeight = parseFloat($('#totalWeightInput').val()) || 0;

        if (minAllowedWeight > 0 && currentTotalWeight < minAllowedWeight) {
            e.preventDefault();
            alert('Cannot reduce total intake weight below ' + minAllowedWeight.toFixed(2) + ' kg because that quantity has already been dispatched.');
            return false;
        }

        const payment = parseFloat($('#paymentInput').val()) || 0;
        const grandTotal = parseFloat($('#totalPriceInput').val()) || 0;
        if (payment > grandTotal) {
            e.preventDefault();
            alert('Payment cannot exceed the grand total bill.');
            return false;
        }
    });
</script>
@endpush

