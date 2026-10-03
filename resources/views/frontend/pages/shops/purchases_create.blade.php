@extends('frontend.layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
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
    .table-custom th, .table-custom td { white-space: nowrap; }
    .builder-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }
    .fs-8 {
        font-size: 0.8rem;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header (No Breadcrumbs) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h4 class="card-title fw-bold text-dark mb-0">Shop Direct Stock Purchase &amp; Intake</h4>
                    <span class="badge bg-primary text-white px-2.5 py-1 rounded-pill"><i class="fe fe-shopping-cart me-1"></i>{{ $shop->name }}</span>
                </div>
                <p class="text-muted small mb-0">Record procurement lots and inward steel coils directly into retail shop stock</p>
            </div>
            <div>
                <a href="{{ route('shops.purchases.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back to Shop Purchases</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <form action="{{ route('purchase.store') }}" method="POST" id="shopPurchaseForm">
        @csrf
        <input type="hidden" name="warehouse_id" id="warehouse_id" value="{{ $shop->id }}">

        <div class="row g-4">

            <!-- LEFT COLUMN (col-lg-8): Lot Details, Item Intake Builder & Line Items Table -->
            <div class="col-lg-8 col-12">

                <!-- 1. Lot & Vendor Intake Setup Card -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="pos-card-header d-flex flex-wrap justify-content-between align-items-center rounded-top-3 gap-2">
                        <span><i class="fe fe-anchor text-primary me-2"></i>Lot &amp; Shop Intake Details</span>
                        <div class="btn-group shadow-sm rounded-3 gap-1" role="group" aria-label="Lot Mode">
                            <input type="radio" class="btn-check" name="lot_type" id="lot_type_new" value="new" {{ old('lot_type', 'new') === 'new' ? 'checked' : '' }} onchange="toggleLotMode('new')">
                            <label class="btn btn-outline-primary btn-sm px-2.5 py-1 fw-semibold" for="lot_type_new">
                                <i class="fe fe-plus-circle me-1"></i> New Lot
                            </label>

                            <input type="radio" class="btn-check" name="lot_type" id="lot_type_existing" value="existing" {{ old('lot_type') === 'existing' ? 'checked' : '' }} onchange="toggleLotMode('existing')">
                            <label class="btn btn-outline-primary btn-sm px-2.5 py-1 fw-semibold" for="lot_type_existing">
                                <i class="fe fe-layers me-1"></i> Existing Lot
                            </label>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        
                        <!-- Default Vendor Selection (Optional Shortcut) -->
                        <div class="row g-3 mb-3 pb-3 border-bottom align-items-center">
                            <div class="col-md-6 col-12">
                                <label for="default_vendor_id" class="form-label small text-secondary fw-semibold mb-1">
                                    <i class="fe fe-user text-primary me-1"></i>Default Vendor / Supplier <span class="text-muted fw-normal">(Optional)</span>
                                </label>
                                <select id="default_vendor_id" name="default_vendor_id" class="form-select select2" onchange="handleDefaultVendorChange(this.value)">
                                    <option value="">None (Choose per coil item below)</option>
                                    @foreach ($vendors as $vendor)
                                        <option value="{{ $vendor->id }}" {{ old('default_vendor_id', old('vendor_id')) == $vendor->id ? 'selected' : '' }}>
                                            {{ $vendor->name }} ({{ $vendor->phone }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="text-muted small mt-md-4 pt-md-1">
                                    <i class="fe fe-info text-info me-1"></i>If selected, all coils added below will automatically be assigned to this supplier.
                                </div>
                            </div>
                        </div>

                        <!-- NEW LOT FIELDS -->
                        <div id="newLotContainer" style="{{ old('lot_type', 'new') === 'new' ? '' : 'display: none;' }}">
                            <div class="row g-3">
                                <div class="col-md-4 col-12">
                                    <label for="new_lot_number" class="form-label small text-secondary fw-semibold mb-1">
                                        Lot Number <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-primary"><i class="fe fe-tag"></i></span>
                                        <input type="text" name="new_lot_number" id="new_lot_number" class="form-control fw-bold text-dark font-monospace"
                                            value="{{ old('new_lot_number', $suggestedLotNumber ?? '') }}" required>
                                    </div>
                                </div>

                                <div class="col-md-4 col-12">
                                    <label class="form-label small text-secondary fw-semibold mb-1">
                                        Target Shop Location
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-primary"><i class="fe fe-shopping-cart"></i></span>
                                        <input type="text" class="form-control bg-light fw-bold text-dark" value="{{ $shop->name }} (Shop Outlet)" readonly>
                                    </div>
                                </div>

                                <div class="col-md-4 col-12">
                                    <label for="purchase_date" class="form-label small text-secondary fw-semibold mb-1">
                                        Intake Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" name="purchase_date" id="purchase_date" class="form-control"
                                        value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                                </div>

                                <div class="col-12 mt-2">
                                    <label for="lot_notes" class="form-label small text-secondary fw-semibold mb-1">
                                        Shipment / Intake Notes <span class="text-muted">(Optional)</span>
                                    </label>
                                    <input type="text" name="lot_notes" id="lot_notes" class="form-control"
                                        value="{{ old('lot_notes') }}" placeholder="Truck receipt #, intake supervisor notes...">
                                </div>
                            </div>
                        </div>

                        <!-- EXISTING LOT FIELDS -->
                        <div id="existingLotContainer" style="{{ old('lot_type') === 'existing' ? '' : 'display: none;' }}">
                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <label for="purchase_lot_id" class="form-label small text-secondary fw-semibold mb-1">
                                        Existing Purchase Lot <span class="text-danger">*</span>
                                    </label>
                                    <select id="purchase_lot_id" name="lot_id" class="form-select select2">
                                        <option value="">Select Existing Lot</option>
                                        @foreach ($lots as $lot)
                                            <option value="{{ $lot->id }}" {{ old('lot_id') == $lot->id ? 'selected' : '' }}>
                                                {{ $lot->lot_number }} {{ $lot->vendor_names ? '('.$lot->vendor_names.')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label for="purchase_date_existing" class="form-label small text-secondary fw-semibold mb-1">
                                        Intake Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" id="purchase_date_existing" class="form-control"
                                        value="{{ old('purchase_date', date('Y-m-d')) }}" onchange="$('#purchase_date').val($(this).val())">
                                </div>
                            </div>
                        </div>

                        <!-- Hidden fallback for single vendor submission -->
                        <input type="hidden" name="vendor_id" id="vendor_hidden" value="{{ old('default_vendor_id', old('vendor_id')) }}">
                    </div>
                </div>

                <!-- 2. Steel Items & Coil Intake Builder -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="pos-card-header d-flex justify-content-between align-items-center rounded-top-3">
                        <span><i class="fe fe-disc text-primary me-2"></i>Shop Steel Coils &amp; Material Intake</span>
                        <small class="text-muted">Enter specifications &amp; weight, then click "+ Add to Table"</small>
                    </div>

                    <div class="card-body p-3 bg-light-subtle">
                        <!-- Builder Box -->
                        <div class="builder-card p-3 shadow-sm mb-3">
                            <!-- Line 0: Dedicated Vendor Row -->
                            <div class="row g-3 mb-3 pb-3 border-bottom align-items-center" id="builderVendorRow">
                                <div class="col-md-6 col-12">
                                    <label for="builder_vendor_id" class="form-label small text-secondary fw-bold mb-1">
                                        <i class="fe fe-user text-primary me-1"></i>Vendor / Supplier <span class="text-danger">*</span>
                                    </label>
                                    <select id="builder_vendor_id" class="form-select select2">
                                        <option value="">Select Vendor / Supplier</option>
                                        @foreach ($vendors as $vendor)
                                            <option value="{{ $vendor->id }}" {{ (old('vendor_id') == $vendor->id || (isset($vendors) && count($vendors) == 1)) ? 'selected' : '' }}>
                                                {{ $vendor->name }} ({{ $vendor->phone }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="text-muted small mt-md-4 pt-md-1">
                                        <i class="fe fe-info text-info me-1"></i>Select the supplier for this specific steel coil.
                                    </div>
                                </div>
                            </div>

                            <!-- Line 1: Dimensions & Notes -->
                            <div class="row g-2 mb-2">
                                <div class="col-md-2 col-6">
                                    <label for="builder_quantity" class="form-label small text-secondary fw-semibold mb-1">
                                        Coil / Pcs <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" min="1" step="1" id="builder_quantity" class="form-control form-control-sm text-center fw-bold" value="1" oninput="calculateBuilderPreview()">
                                </div>

                                <div class="col-md-2 col-6">
                                    <label for="builder_thickness" class="form-label small text-secondary fw-semibold mb-1">
                                        Thickness
                                    </label>
                                    <input type="text" id="builder_thickness" class="form-control form-control-sm text-center" placeholder="e.g. 2.50 mm">
                                </div>

                                <div class="col-md-3 col-6">
                                    <label for="builder_size" class="form-label small text-secondary fw-semibold mb-1">
                                        Size / Width
                                    </label>
                                    <input type="text" id="builder_size" class="form-control form-control-sm text-center" placeholder="e.g. 4x8 / 1250mm">
                                </div>

                                <div class="col-md-2 col-6">
                                    <label for="builder_size_type" class="form-label small text-secondary fw-semibold mb-1">
                                        Unit
                                    </label>
                                    <select id="builder_size_type" class="form-select form-select-sm">
                                        <option value="ft" selected>Feet (ft)</option>
                                        <option value="mm">Millimeter (mm)</option>
                                        <option value="inch">Inch (in)</option>
                                        <option value="m">Meter (m)</option>
                                        <option value="pcs">Pieces (pcs)</option>
                                        <option value="ton">Ton</option>
                                    </select>
                                </div>

                                <div class="col-md-3 col-12">
                                    <label for="builder_notes" class="form-label small text-secondary fw-semibold mb-1">
                                        Notes (Optional)
                                    </label>
                                    <input type="text" id="builder_notes" class="form-control form-control-sm" placeholder="Coil brand, color code...">
                                </div>
                            </div>

                            <!-- Line 2: Weights, Rates & Action Button -->
                            <div class="row g-2 align-items-end p-2.5 rounded-2 bg-light border border-light-subtle">
                                <div class="col-md-3 col-6">
                                    <label for="builder_unit_weight" class="form-label small text-secondary fw-semibold mb-1">
                                        Per Coil Wt (kg) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" min="0.01" id="builder_unit_weight" class="form-control text-end fw-bold text-primary" placeholder="0.00" oninput="calculateBuilderPreview()">
                                        <span class="input-group-text bg-white text-muted">kg</span>
                                    </div>
                                </div>

                                <div class="col-md-2 col-6">
                                    <label for="builder_total_weight" class="form-label small text-secondary fw-semibold mb-1">
                                        Total Wt (kg)
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" id="builder_total_weight" class="form-control bg-white text-end fw-bold text-dark" readonly tabindex="-1">
                                        <span class="input-group-text bg-white text-muted">kg</span>
                                    </div>
                                </div>

                                <div class="col-md-3 col-6">
                                    <label for="builder_unit_price" class="form-label small text-secondary fw-semibold mb-1">
                                        Unit Rate (৳/kg) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white text-muted">৳</span>
                                        <input type="number" step="0.01" min="0" id="builder_unit_price" class="form-control text-end fw-semibold" placeholder="0.00" oninput="calculateBuilderPreview()">
                                    </div>
                                </div>

                                <div class="col-md-2 col-6">
                                    <label for="builder_sub_price" class="form-label small text-secondary fw-semibold mb-1">
                                        Sub Total (৳)
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white text-muted">৳</span>
                                        <input type="number" step="0.01" id="builder_sub_price" class="form-control bg-white text-end fw-bold text-success" readonly tabindex="-1">
                                    </div>
                                </div>

                                <div class="col-md-2 col-12">
                                    <button type="button" class="btn btn-primary btn-sm w-100 shadow-sm fw-semibold d-inline-flex align-items-center justify-content-center gap-1 py-1.5" id="addSteelBtn" onclick="addSteelItemToTable()" style="height: 31px;">
                                        <i class="fe fe-plus-circle"></i>
                                        <span>Add</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Added Steel Items Table -->
                        <div class="card border border-light-subtle rounded-3 shadow-none overflow-hidden bg-white mb-0">
                            <div class="card-header bg-light py-2 px-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold text-dark fs-7">Added Coils / Plates</span>
                                    <span class="badge bg-primary rounded-pill px-2 py-0.5 fs-8" id="itemsCountBadge">0 Items</span>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover table-custom align-middle mb-0" id="steelItemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" style="width: 40px;">#</th>
                                            <th class="vendor-col" style="{{ old('default_vendor_id') ? 'display: none;' : '' }}">Vendor</th>
                                            <th>Specs &amp; Dimensions</th>
                                            <th class="text-center" style="width: 80px;">Qty</th>
                                            <th class="text-end" style="width: 110px;">Per Coil Wt</th>
                                            <th class="text-end" style="width: 120px;">Total Wt</th>
                                            <th class="text-end" style="width: 100px;">Rate (৳/kg)</th>
                                            <th class="text-end" style="width: 120px;">Sub Total (৳)</th>
                                            <th class="text-center" style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="steelTableBody">
                                        <tr id="emptyTablePlaceholder">
                                            <td colspan="9" class="text-center py-4 text-muted">
                                                <i class="fe fe-inbox fs-2 d-block mb-1 text-secondary opacity-50"></i>
                                                <span class="fw-medium">No coils added yet. Enter specifications above and click "Add".</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="table-light fw-bold" id="tableSummaryFooter" style="display: none;">
                                        <tr>
                                            <td colspan="3" class="text-end pe-3">Batch Total:</td>
                                            <td class="text-center"><span class="badge bg-dark rounded-pill px-2 py-0.5" id="tfootTotalQty">0</span></td>
                                            <td></td>
                                            <td class="text-end"><span class="badge bg-primary-subtle text-primary px-2 py-0.5 fs-8" id="tfootTotalWeight">0.00 kg</span></td>
                                            <td></td>
                                            <td class="text-end"><span class="text-success fs-7" id="tfootGrandTotal">৳ 0.00</span></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
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
                                <small class="text-muted text-uppercase fw-semibold fs-8 d-block mb-1">Total Coils</small>
                                <h5 class="fw-bold text-dark mb-0" id="displayTotalCoils">0</h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-white rounded-3 shadow-sm border text-center">
                                <small class="text-muted text-uppercase fw-semibold fs-8 d-block mb-1">Intake Weight</small>
                                <h5 class="fw-bold text-primary mb-0"><span id="displayTotalNetWeight">0.00</span> <small class="fs-8 text-muted">kg</small></h5>
                            </div>
                        </div>
                    </div>

                    <!-- Charges & Financial Adjustments Card -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="pos-card-header rounded-top-3">
                            <span><i class="fe fe-dollar-sign text-success me-1"></i>Procurement Charges &amp; Costs</span>
                        </div>
                        <div class="card-body p-3">
                            
                            <!-- Steel Subtotal -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary small fw-semibold">Steel Sub Total:</span>
                                <span class="fw-bold text-dark" id="displaySubTotal">৳ 0.00</span>
                                <input type="hidden" id="purchaseSubTotalDisplay" value="0.00">
                            </div>

                            <!-- Transport Freight with Two-Tick Toggle -->
                            <div class="border rounded-2 p-2 bg-light mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-semibold text-secondary">Transport Freight:</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="form-check form-check-inline m-0">
                                            <input class="form-check-input" type="radio" name="transport_payer" id="transport_payer_me" value="me" checked onchange="recalculateSummary()">
                                            <label class="form-check-label small fs-8 text-primary fw-semibold" for="transport_payer_me">Paid by Me</label>
                                        </div>
                                        <div class="form-check form-check-inline m-0">
                                            <input class="form-check-input" type="radio" name="transport_payer" id="transport_payer_vendor" value="vendor" onchange="recalculateSummary()">
                                            <label class="form-check-label small fs-8 text-secondary fw-semibold" for="transport_payer_vendor">Paid by Vendor</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">৳</span>
                                    <input type="number" name="delivery_charge" id="delivery_charge" class="form-control text-end" value="0.00" min="0" step="0.01" oninput="recalculateSummary()">
                                </div>
                            </div>

                            <!-- Cutting / Labour, Scale, Other Charges -->
                            <div class="row g-2 mb-2">
                                <div class="col-4">
                                    <label class="form-label fs-8 text-secondary fw-semibold mb-1">Labour (৳)</label>
                                    <input type="number" name="labour_cost" id="labour_cost" class="form-control form-control-sm text-end" value="0.00" min="0" step="0.01" oninput="recalculateSummary()">
                                </div>
                                <div class="col-4">
                                    <label class="form-label fs-8 text-secondary fw-semibold mb-1">Scale (৳)</label>
                                    <input type="number" name="weight_scale_cost" id="weight_scale_cost" class="form-control form-control-sm text-end" value="0.00" min="0" step="0.01" oninput="recalculateSummary()">
                                </div>
                                <div class="col-4">
                                    <label class="form-label fs-8 text-secondary fw-semibold mb-1">Other (৳)</label>
                                    <input type="number" name="other_charges" id="other_charges" class="form-control form-control-sm text-end" value="0.00" min="0" step="0.01" oninput="recalculateSummary()">
                                </div>
                            </div>

                            <!-- Discount -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary small fw-semibold">Discount (৳):</span>
                                <div class="input-group input-group-sm" style="max-width: 140px;">
                                    <span class="input-group-text bg-white">৳</span>
                                    <input type="number" name="discount" id="discount" class="form-control text-end" value="0.00" min="0" step="0.01" oninput="recalculateSummary()">
                                </div>
                            </div>

                            <!-- Landed Grand Total Display -->
                            <div class="grand-total-display mb-3 text-center">
                                <small class="text-white-50 text-uppercase fw-semibold d-block fs-8 mb-1">Grand Purchase Bill (Landed Cost)</small>
                                <h3 class="fw-bold mb-0 text-white" id="displayGrandTotal">৳ 0.00</h3>
                                <input type="hidden" name="grand_total" id="grand_total_hidden" value="0.00">
                                <small class="text-white-50 fs-8 d-block mt-1">Extra Charges: <span id="displayTotalCharges" class="text-warning">৳ 0.00</span></small>
                            </div>

                            <!-- Payment Settlement Section -->
                            <div class="border-top pt-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-dark fs-7"><i class="fe fe-check-circle text-success me-1"></i>Payment Settlement</span>
                                    <span id="paymentModeBadge" class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 fs-8" style="display: none;">
                                        Multi-Vendor
                                    </span>
                                </div>

                                <!-- Single Vendor Payment Form -->
                                <div id="singleVendorPaymentSection">
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label for="paymentInput" class="form-label small text-secondary fw-semibold mb-0">Paid Amount (৳)</label>
                                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold fs-8" onclick="setFullPayment()">
                                                Pay Full Bill
                                            </button>
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-success fw-bold">৳</span>
                                            <input type="number" step="0.01" min="0" name="payment" id="paymentInput"
                                                class="form-control form-control-lg fw-bold text-success"
                                                value="{{ old('payment', '0.00') }}" oninput="recalculateSummary()" required>
                                        </div>
                                        <div id="paymentErrorMsg" class="text-danger small mt-1 fw-semibold fs-8" style="display: none;">
                                            <i class="fe fe-alert-triangle me-1"></i> Payment cannot exceed vendor bill of <span id="maxBillFormatted">৳ 0.00</span>.
                                        </div>
                                    </div>

                                    <!-- Method & Bank Accounts -->
                                    <div class="p-2.5 bg-light rounded-3 border mb-2">
                                        <label class="form-label fs-8 text-secondary fw-semibold mb-1">Payment Method</label>
                                        <select name="payment_method" id="purchasePaymentMethod" class="form-select form-select-sm mb-2" onchange="handlePurchasePaymentMethodChange(this.value)">
                                            <option value="cash" selected>Cash in Hand</option>
                                            <option value="bank">Bank Transfer / MFS</option>
                                        </select>

                                        <div id="purchaseBankAccountContainer" class="mb-2" style="display: none;">
                                            <label class="form-label fs-8 text-secondary fw-semibold mb-1">Disbursement Bank</label>
                                            <select name="bank_detail_id" id="purchaseBankDetail" class="form-select form-select-sm">
                                                <option value="" selected>Select Bank Account</option>
                                                @foreach($bankAccounts ?? [] as $bank)
                                                    <option value="{{ $bank->id }}">
                                                        {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div id="purchaseTransactionRefContainer" style="display: none;">
                                            <label class="form-label fs-8 text-secondary fw-semibold mb-1">TrxID / Cheque Ref</label>
                                            <input type="text" name="transaction_ref" class="form-control form-control-sm bg-white" placeholder="Transaction Reference #">
                                        </div>
                                    </div>
                                </div>

                                <!-- Multi-Vendor Individual Payments Breakdown Section -->
                                <div id="multiVendorPaymentSection" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                        <span class="fs-8 text-muted fw-semibold">
                                            <i class="fe fe-info text-info me-1"></i>Individual vendor settlement:
                                        </span>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-outline-success btn-sm py-0.5 px-1.5 fs-8 rounded" onclick="setAllVendorsFullPayment()">
                                                Pay All
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-1.5 fs-8 rounded" onclick="clearAllVendorPayments()">
                                                Clear
                                            </button>
                                        </div>
                                    </div>

                                    <div id="vendorPaymentCardsList" class="mb-2">
                                        <!-- Dynamic Multi-Vendor Cards -->
                                    </div>

                                    <div class="p-2 bg-light rounded d-flex justify-content-between align-items-center mb-2 border">
                                        <span class="fs-8 text-secondary fw-semibold">Total Paid to All:</span>
                                        <span class="fw-bold text-success fs-7" id="displayTotalPaidMulti">৳ 0.00</span>
                                    </div>
                                </div>

                                <!-- Outstanding Due -->
                                <div class="p-2.5 bg-light rounded-3 d-flex justify-content-between align-items-center mb-3 border">
                                    <span class="fw-semibold text-secondary small">Outstanding Due:</span>
                                    <span class="fs-5 fw-bold text-danger" id="displayDueAmount">৳ 0.00</span>
                                    <input type="hidden" name="due" id="due_hidden" value="0.00">
                                </div>

                                <!-- Confirm & Save -->
                                <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-3 shadow fw-bold d-flex align-items-center justify-content-center gap-2" id="submitPurchaseBtn">
                                    <i class="fe fe-check-circle"></i>
                                    <span>Confirm &amp; Record Intake</span>
                                </button>
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    let itemIndex = 0;

    function escapeHtml(text) {
        if (!text) return '';
        return $('<div>').text(text).html();
    }

    function toggleLotMode(mode) {
        if (mode === 'new') {
            $('#newLotContainer').fadeIn(150);
            $('#existingLotContainer').hide();
            $('#new_lot_number').attr('required', true);
            $('#purchase_lot_id').removeAttr('required');
        } else {
            $('#newLotContainer').hide();
            $('#existingLotContainer').fadeIn(150);
            $('#new_lot_number').removeAttr('required');
            $('#purchase_lot_id').attr('required', true);
        }
    }

    function handleDefaultVendorChange(val) {
        $('#vendor_hidden').val(val);
        if (val) {
            $('#builderVendorRow').slideUp(150);
            $('.vendor-col').hide();
            const vendorName = $('#default_vendor_id option:selected').text().trim();
            $('#steelTableBody tr.item-row').each(function () {
                $(this).find('.item-vendor-id-input').val(val);
                $(this).find('.vendor-col-badge').html(`<i class="fe fe-user me-1"></i>${escapeHtml(vendorName)}`);
            });
        } else {
            $('#builderVendorRow').slideDown(150);
            $('.vendor-col').show();
        }
        recalculateSummary();
    }

    function handlePurchasePaymentMethodChange(val) {
        if (val === 'bank') {
            $('#purchaseBankAccountContainer').slideDown(150);
            $('#purchaseTransactionRefContainer').slideDown(150);
        } else {
            $('#purchaseBankAccountContainer').slideUp(150);
            $('#purchaseTransactionRefContainer').slideUp(150);
        }
    }

    function calculateBuilderPreview() {
        const qty = parseInt($('#builder_quantity').val()) || 0;
        const unitWeight = parseFloat($('#builder_unit_weight').val()) || 0;
        const totalWeight = qty * unitWeight;
        $('#builder_total_weight').val(totalWeight > 0 ? totalWeight.toFixed(2) : '');

        const rate = parseFloat($('#builder_unit_price').val()) || 0;
        const subTotal = totalWeight * rate;
        $('#builder_sub_price').val(subTotal > 0 ? subTotal.toFixed(2) : '');
    }

    function addSteelItemToTable() {
        const defaultVendorId = $('#default_vendor_id').val();
        let vendorId = defaultVendorId;
        let vendorName = '';

        if (defaultVendorId) {
            vendorName = $('#default_vendor_id option:selected').text().trim();
        } else {
            vendorId = $('#builder_vendor_id').val();
            vendorName = $('#builder_vendor_id option:selected').text().trim();

            if (!vendorId) {
                alert('Please select a Vendor / Supplier for this steel item.');
                $('#builder_vendor_id').select2('open');
                return;
            }
        }

        const qty = parseInt($('#builder_quantity').val()) || 0;
        const thickness = $('#builder_thickness').val().trim();
        const size = $('#builder_size').val().trim();
        const sizeType = $('#builder_size_type').val() || 'ft';
        const sizeTypeText = $('#builder_size_type option:selected').text();
        const notes = $('#builder_notes').val().trim();
        const unitWeight = parseFloat($('#builder_unit_weight').val()) || 0;
        const rate = parseFloat($('#builder_unit_price').val());

        if (qty <= 0) {
            alert('Please enter a valid coil / piece quantity (minimum 1).');
            $('#builder_quantity').focus();
            return;
        }

        if (unitWeight <= 0) {
            alert('Please enter a valid weight per coil in kg.');
            $('#builder_unit_weight').focus();
            return;
        }

        if (isNaN(rate) || rate < 0) {
            alert('Please enter a valid unit rate (৳/kg).');
            $('#builder_unit_price').focus();
            return;
        }

        const totalWeight = parseFloat((qty * unitWeight).toFixed(2));
        const subPrice = parseFloat((totalWeight * rate).toFixed(2));
        const formattedRate = rate.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const formattedSubPrice = subPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const formattedUnitWeight = unitWeight.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const formattedTotalWeight = totalWeight.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        let specHtml = '';
        if (thickness) {
            specHtml += `<span class="badge bg-light text-dark border me-1"><i class="fe fe-layers me-1 text-primary"></i>Thk: ${escapeHtml(thickness)}</span>`;
        }
        if (size) {
            specHtml += `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="fe fe-maximize-2 me-1"></i>${escapeHtml(size)} (${sizeTypeText})</span>`;
        } else {
            specHtml += `<span class="badge bg-light text-muted border">${sizeTypeText}</span>`;
        }
        if (notes) {
            specHtml += `<div class="small text-muted mt-1"><i class="fe fe-tag me-1 text-info"></i>${escapeHtml(notes)}</div>`;
        }

        const rowHtml = `
            <tr class="item-row" data-index="${itemIndex}">
                <td class="text-center">
                    <span class="badge bg-soft-dark rounded-pill px-2 py-0.5 row-serial-number">1</span>
                </td>
                <td class="vendor-col" style="${defaultVendorId ? 'display: none;' : ''}">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 vendor-col-badge">
                        <i class="fe fe-user me-1"></i>${escapeHtml(vendorName)}
                    </span>
                </td>
                <td>
                    <div class="d-flex flex-wrap align-items-center gap-1">
                        ${specHtml}
                    </div>
                </td>
                <td class="text-center fw-bold">${qty}</td>
                <td class="text-end font-monospace">${formattedUnitWeight} kg</td>
                <td class="text-end font-monospace fw-bold text-primary">${formattedTotalWeight} kg</td>
                <td class="text-end font-monospace">৳ ${formattedRate}</td>
                <td class="text-end font-monospace fw-bold text-success">৳ ${formattedSubPrice}</td>
                <td class="text-center">
                    <button type="button" onclick="removeSteelItem(this)" class="btn btn-sm btn-outline-danger border-0 rounded-2 px-2 py-1" title="Remove Coil">
                        <i class="fe fe-trash-2"></i>
                    </button>
                    <input type="hidden" name="items[${itemIndex}][vendor_id]" class="item-vendor-id-input" value="${vendorId}">
                    <input type="hidden" name="items[${itemIndex}][quantity]" class="item-qty-input" value="${qty}">
                    <input type="hidden" name="items[${itemIndex}][thickness]" value="${escapeHtml(thickness)}">
                    <input type="hidden" name="items[${itemIndex}][size]" value="${escapeHtml(size)}">
                    <input type="hidden" name="items[${itemIndex}][size_type]" value="${sizeType}">
                    <input type="hidden" name="items[${itemIndex}][notes]" value="${escapeHtml(notes)}">
                    <input type="hidden" name="items[${itemIndex}][unit_weight]" class="item-unit-weight-input" value="${unitWeight}">
                    <input type="hidden" name="items[${itemIndex}][net_weight]" value="${unitWeight}">
                    <input type="hidden" name="items[${itemIndex}][total_weight]" class="item-total-weight-input" value="${totalWeight}">
                    <input type="hidden" name="items[${itemIndex}][unit_price]" class="item-unit-price-input" value="${rate}">
                    <input type="hidden" name="items[${itemIndex}][sub_price]" class="item-sub-price-input" value="${subPrice}">
                </td>
            </tr>
        `;

        $('#steelTableBody').append(rowHtml);
        itemIndex++;

        // Reset builder fields
        if (!defaultVendorId) {
            $('#builder_vendor_id').val('').trigger('change');
            $('#builder_vendor_id').select2('open');
        } else {
            $('#builder_quantity').focus();
        }

        $('#builder_quantity').val('1');
        $('#builder_thickness').val('');
        $('#builder_size').val('');
        $('#builder_size_type').val('ft');
        $('#builder_notes').val('');
        $('#builder_unit_weight').val('');
        $('#builder_total_weight').val('');
        $('#builder_unit_price').val('');
        $('#builder_sub_price').val('');

        updateTableNumbers();
        recalculateSummary();
    }

    function removeSteelItem(btn) {
        $(btn).closest('tr.item-row').remove();
        updateTableNumbers();
        recalculateSummary();
    }

    function updateTableNumbers() {
        $('#steelTableBody tr.item-row').each(function (index) {
            $(this).find('.row-serial-number').text(index + 1);
        });
    }

    const vendorsMap = @json($vendors->keyBy('id'));
    const bankAccountsList = @json($bankAccounts ?? []);

    function roundTo2(num) {
        return Math.round((num + Number.EPSILON) * 100) / 100;
    }

    function recalculateSummary() {
        let totalCoils = 0;
        let totalWeight = 0;
        let subTotal = 0;
        let vendorMetrics = {};
        const rows = $('#steelTableBody tr.item-row');

        rows.each(function () {
            const qty = parseInt($(this).find('.item-qty-input').val()) || 0;
            const rowTotalWeight = parseFloat($(this).find('.item-total-weight-input').val()) || 0;
            const sub = parseFloat($(this).find('.item-sub-price-input').val()) || 0;
            const vId = $(this).find('.item-vendor-id-input').val();

            totalCoils += qty;
            totalWeight += rowTotalWeight;
            subTotal += sub;

            if (vId) {
                if (!vendorMetrics[vId]) {
                    vendorMetrics[vId] = { qty: 0, weight: 0, subTotal: 0 };
                }
                vendorMetrics[vId].qty += qty;
                vendorMetrics[vId].weight += rowTotalWeight;
                vendorMetrics[vId].subTotal += sub;
            }
        });

        const discount = parseFloat($('#discount').val()) || 0;
        const delivery = parseFloat($('#delivery_charge').val()) || 0;
        const transportPayer = $('input[name="transport_payer"]:checked').val() || 'me';
        const vendorDelivery = (transportPayer === 'vendor') ? delivery : 0;
        const labour = parseFloat($('#labour_cost').val()) || 0;
        const scale = parseFloat($('#weight_scale_cost').val()) || 0;
        const other = parseFloat($('#other_charges').val()) || 0;

        const totalCharges = delivery + labour + scale + other;
        const grandTotal = Math.max(0, (subTotal + totalCharges) - discount);
        const vendorTotalCharges = vendorDelivery + labour + scale + other;
        const vendorGrandTotal = Math.max(0, (subTotal + vendorTotalCharges) - discount);

        const formattedSubTotal = subTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const formattedTotalCharges = totalCharges.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const formattedGrandTotal = grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const formattedVendorGrandTotal = vendorGrandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const formattedTotalWeight = totalWeight.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        $('#purchaseSubTotalDisplay').val(subTotal.toFixed(2));
        $('#displayTotalCoils').text(totalCoils);
        $('#displayTotalNetWeight').text(formattedTotalWeight);
        $('#displaySubTotal').text('৳ ' + formattedSubTotal);
        $('#displayTotalCharges').text('৳ ' + formattedTotalCharges);
        $('#displayGrandTotal').text('৳ ' + formattedGrandTotal);
        $('#grand_total_hidden').val(grandTotal.toFixed(2));
        $('#maxBillFormatted').text('৳ ' + formattedVendorGrandTotal).data('vendor-grand-total', vendorGrandTotal);

        $('#itemsCountBadge').text(rows.length + (rows.length === 1 ? ' Item' : ' Items'));
        if (rows.length > 0) {
            $('#emptyTablePlaceholder').hide();
            $('#tableSummaryFooter').show();
            $('#tfootTotalQty').text(totalCoils);
            $('#tfootTotalWeight').text(formattedTotalWeight + ' kg');
            $('#tfootGrandTotal').text('৳ ' + formattedSubTotal);
        } else {
            $('#emptyTablePlaceholder').show();
            $('#tableSummaryFooter').hide();
        }

        const uniqueVendorIds = Object.keys(vendorMetrics);

        if (uniqueVendorIds.length <= 1) {
            $('#singleVendorPaymentSection').show();
            $('#multiVendorPaymentSection').hide();
            $('#paymentModeBadge').hide();
            $('#vendorPaymentCardsList').empty();

            const paymentInput = $('#paymentInput');
            const payment = parseFloat(paymentInput.val()) || 0;

            if (payment > vendorGrandTotal + 0.009) {
                $('#paymentErrorMsg').fadeIn(150);
                paymentInput.addClass('is-invalid border-danger');
                $('#displayDueAmount').text('৳ 0.00');
                $('#due_hidden').val('0.00');
            } else {
                $('#paymentErrorMsg').hide();
                paymentInput.removeClass('is-invalid border-danger');
                const due = Math.max(0, vendorGrandTotal - payment);
                $('#displayDueAmount').text('৳ ' + due.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#due_hidden').val(due.toFixed(2));
            }
        } else {
            $('#singleVendorPaymentSection').hide();
            $('#multiVendorPaymentSection').show();
            $('#paymentModeBadge').show();

            let existingVendorPay = {};
            let existingVendorMethod = {};
            let existingVendorBank = {};
            let existingVendorRef = {};

            $('.vendor-payment-card').each(function () {
                const vId = $(this).data('vendor-id');
                existingVendorPay[vId] = $(this).find('.vendor-pay-input').val();
                existingVendorMethod[vId] = $(this).find('.vendor-method-select').val();
                existingVendorBank[vId] = $(this).find('.vendor-bank-select').val();
                existingVendorRef[vId] = $(this).find('.vendor-ref-input').val();
            });

            uniqueVendorIds.forEach(vId => {
                const vRatio = subTotal > 0 ? (vendorMetrics[vId].subTotal / subTotal) : (1 / uniqueVendorIds.length);
                const vNetCharges = (vendorTotalCharges - discount) * vRatio;
                vendorMetrics[vId].bill = Math.max(0, roundTo2(vendorMetrics[vId].subTotal + vNetCharges));
            });

            let bankOptionsHtml = '<option value="">Select Bank Account</option>';
            bankAccountsList.forEach(b => {
                bankOptionsHtml += `<option value="${b.id}">${escapeHtml(b.bank_name)} - ${escapeHtml(b.account_name)} (${escapeHtml(b.account_number)})</option>`;
            });

            let cardsHtml = '';
            uniqueVendorIds.forEach(vId => {
                const vInfo = vendorsMap[vId] || { name: 'Vendor #' + vId, phone: '' };
                const m = vendorMetrics[vId];
                const prevPay = existingVendorPay[vId] !== undefined ? existingVendorPay[vId] : '0.00';
                const prevMethod = existingVendorMethod[vId] || 'cash';
                const prevRef = existingVendorRef[vId] || '';
                const vPaid = parseFloat(prevPay) || 0;
                const vDue = Math.max(0, m.bill - vPaid);

                cardsHtml += `
                    <div class="card border border-light-subtle rounded-3 p-2.5 mb-2 vendor-payment-card bg-white shadow-none" data-vendor-id="${vId}">
                        <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                            <div>
                                <h6 class="fw-bold text-dark mb-0 fs-8"><i class="fe fe-user text-primary me-1"></i>${escapeHtml(vInfo.name)}</h6>
                                <small class="text-muted fs-8">${m.qty} coils • ${m.weight.toFixed(2)} kg</small>
                            </div>
                            <div class="text-end">
                                <span class="small text-muted d-block fs-8">Bill</span>
                                <span class="fw-bold text-dark fs-7 v-bill-text">৳ ${m.bill.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                <input type="hidden" class="v-bill-val" value="${m.bill.toFixed(2)}">
                            </div>
                        </div>

                        <div class="row g-1 align-items-center mt-1">
                            <div class="col-sm-6 col-12">
                                <label class="form-label fs-8 text-secondary fw-semibold mb-0">Paid (৳)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-success fw-bold">৳</span>
                                    <input type="number" step="0.01" min="0" name="vendor_payments[${vId}][amount]" 
                                        class="form-control fw-bold text-success vendor-pay-input" 
                                        data-vendor-id="${vId}" value="${prevPay}" oninput="onVendorPaymentChange(this)">
                                    <button type="button" class="btn btn-outline-success btn-sm px-1.5 fs-8" onclick="setVendorPayFull(this)">Full</button>
                                </div>
                                <div class="vendor-pay-error text-danger small mt-0.5 fs-8" style="${vPaid > m.bill + 0.009 ? '' : 'display: none;'}">
                                    <i class="fe fe-alert-triangle me-1"></i>Exceeds (৳ ${m.bill.toFixed(2)})
                                </div>
                            </div>
                            <div class="col-sm-6 col-12">
                                <label class="form-label fs-8 text-secondary fw-semibold mb-0">Method</label>
                                <select name="vendor_payments[${vId}][payment_method]" class="form-select form-select-sm vendor-method-select" onchange="onVendorMethodChange(this)">
                                    <option value="cash" ${prevMethod === 'cash' ? 'selected' : ''}>Cash</option>
                                    <option value="bank" ${prevMethod === 'bank' ? 'selected' : ''}>Bank/MFS</option>
                                </select>
                            </div>
                            <div class="col-12 vendor-bank-row mt-1" style="${prevMethod === 'cash' ? 'display: none;' : ''}">
                                <div class="row g-1">
                                    <div class="col-sm-6 col-12">
                                        <select name="vendor_payments[${vId}][bank_detail_id]" class="form-select form-select-sm vendor-bank-select">
                                            ${bankOptionsHtml}
                                        </select>
                                    </div>
                                    <div class="col-sm-6 col-12">
                                        <input type="text" name="vendor_payments[${vId}][transaction_ref]" class="form-control form-select-sm vendor-ref-input" placeholder="TrxID #" value="${escapeHtml(prevRef)}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-1 pt-1 border-top">
                            <span class="fs-8 text-muted fw-semibold">Due:</span>
                            <span class="fw-bold text-danger fs-8 v-due-text">৳ ${vDue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                        </div>
                        <input type="hidden" name="vendor_payments[${vId}][vendor_id]" value="${vId}">
                    </div>
                `;
            });

            $('#vendorPaymentCardsList').html(cardsHtml);
            recalculateMultiVendorTotals();
        }
    }

    function setFullPayment() {
        const grandTotal = parseFloat($('#maxBillFormatted').data('vendor-grand-total')) || 0;
        $('#paymentInput').val(grandTotal.toFixed(2));
        recalculateSummary();
    }

    function setVendorPayFull(btn) {
        const card = $(btn).closest('.vendor-payment-card');
        const bill = parseFloat(card.find('.v-bill-val').val()) || 0;
        card.find('.vendor-pay-input').val(bill.toFixed(2));
        onVendorPaymentChange(card.find('.vendor-pay-input')[0]);
    }

    function setAllVendorsFullPayment() {
        $('.vendor-payment-card').each(function () {
            const bill = parseFloat($(this).find('.v-bill-val').val()) || 0;
            $(this).find('.vendor-pay-input').val(bill.toFixed(2));
            onVendorPaymentChange($(this).find('.vendor-pay-input')[0]);
        });
    }

    function clearAllVendorPayments() {
        $('.vendor-payment-card').each(function () {
            $(this).find('.vendor-pay-input').val('0.00');
            onVendorPaymentChange($(this).find('.vendor-pay-input')[0]);
        });
    }

    function onVendorPaymentChange(input) {
        const card = $(input).closest('.vendor-payment-card');
        const bill = parseFloat(card.find('.v-bill-val').val()) || 0;
        const paid = parseFloat($(input).val()) || 0;

        if (paid > bill + 0.009) {
            card.find('.vendor-pay-error').show();
            $(input).addClass('is-invalid border-danger');
        } else {
            card.find('.vendor-pay-error').hide();
            $(input).removeClass('is-invalid border-danger');
        }

        const due = Math.max(0, bill - paid);
        card.find('.v-due-text').text('৳ ' + due.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        recalculateMultiVendorTotals();
    }

    function onVendorMethodChange(select) {
        const card = $(select).closest('.vendor-payment-card');
        if ($(select).val() === 'bank') {
            card.find('.vendor-bank-row').slideDown(150);
        } else {
            card.find('.vendor-bank-row').slideUp(150);
        }
    }

    function recalculateMultiVendorTotals() {
        let totalPaid = 0;
        let totalDue = 0;

        $('.vendor-payment-card').each(function () {
            const bill = parseFloat($(this).find('.v-bill-val').val()) || 0;
            const paid = parseFloat($(this).find('.vendor-pay-input').val()) || 0;
            totalPaid += paid;
            totalDue += Math.max(0, bill - paid);
        });

        $('#displayTotalPaidMulti').text('৳ ' + totalPaid.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#displayDueAmount').text('৳ ' + totalDue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#due_hidden').val(totalDue.toFixed(2));
    }

    $(document).ready(function () {
        $('.select2').select2({
            width: '100%'
        });

        $('#builder_quantity, #builder_thickness, #builder_size, #builder_notes, #builder_unit_weight, #builder_unit_price').on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                addSteelItemToTable();
            }
        });

        if ($('#lot_type_existing').is(':checked')) {
            toggleLotMode('existing');
        } else {
            toggleLotMode('new');
        }

        const initialDefaultVendor = $('#default_vendor_id').val();
        if (initialDefaultVendor) {
            $('#builderVendorRow').hide();
            $('.vendor-col').hide();
            $('#vendor_hidden').val(initialDefaultVendor);
        }

        handlePurchasePaymentMethodChange($('#purchasePaymentMethod').val());
        recalculateSummary();
    });
</script>
@endpush
