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
        .table-custom th {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            background-color: #f8fafc;
        }
        .table-custom td {
            vertical-align: middle;
        }
        .table-responsive {
            overflow: visible !important;
        }
        .table-custom th, .table-custom td {
            white-space: nowrap;
        }
        .dropdown-menu {
            z-index: 1060 !important;
        }
        .builder-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }
        .summary-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
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
                    <a href="{{ route('shops.purchases.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                        <i class="fe fe-arrow-left"></i>
                        <span>Back to Shop Purchases</span>
                    </a>
                </div>
            </div>
        </div>
        <!-- /Page Header -->

        <form action="{{ route('purchase.store') }}" method="POST" id="createPurchaseForm">
            @csrf
            <input type="hidden" name="warehouse_id" id="warehouse_id" value="{{ $shop->id }}">

            <!-- Section 1: Lot & Shop Intake Details -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-1">
                                <i class="fe fe-anchor text-primary me-2"></i>Lot &amp; Shop Intake Details
                            </h6>
                            <p class="text-muted small mb-0">Create a new procurement lot or assign inward items to an existing lot in this shop</p>
                        </div>
                        <div class="btn-group shadow-sm rounded-3 gap-2" role="group" aria-label="Lot Mode Selection">
                            <input type="radio" class="btn-check" name="lot_type" id="lot_type_new" value="new" {{ old('lot_type', 'new') === 'new' ? 'checked' : '' }} onchange="toggleLotMode('new')">
                            <label class="btn btn-outline-primary btn-sm px-3 py-2 fw-semibold" for="lot_type_new">
                                <i class="fe fe-plus-circle me-1"></i> Create New Lot
                            </label>

                            <input type="radio" class="btn-check" name="lot_type" id="lot_type_existing" value="existing" {{ old('lot_type') === 'existing' ? 'checked' : '' }} onchange="toggleLotMode('existing')">
                            <label class="btn btn-outline-primary btn-sm px-3 py-2 fw-semibold" for="lot_type_existing">
                                <i class="fe fe-layers me-1"></i> Select Existing Lot
                            </label>
                        </div>
                    </div>

                    <!-- DEFAULT VENDOR SELECTION (OPTIONAL) -->
                    <div class="row g-3 mb-3 pb-3 border-bottom align-items-center">
                        <div class="col-lg-5 col-md-6 col-12">
                            <label for="default_vendor_id" class="form-label fw-semibold small text-secondary mb-1">
                                <i class="fe fe-user text-primary me-1"></i>Default Vendor / Supplier <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <select id="default_vendor_id" name="default_vendor_id" class="form-select select2" onchange="handleDefaultVendorChange(this.value)">
                                <option value="">None (Choose individual vendor per steel item)</option>
                                @foreach ($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" 
                                        data-opening-due="{{ (float)($vendor->opening_due ?? $vendor->opening_balance ?? 0) }}"
                                        data-purchase-due="{{ (float)($vendor->purchase_due ?? $vendor->purchases_sum_due ?? 0) }}"
                                        data-advance-balance="{{ (float)($vendor->advance_balance ?? 0) }}"
                                        data-total-due="{{ (float)($vendor->total_due ?? 0) }}"
                                        {{ old('default_vendor_id', old('vendor_id')) == $vendor->id ? 'selected' : '' }}>
                                        {{ $vendor->name }} ({{ $vendor->phone }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-7 col-md-6 col-12">
                            <div id="vendorBalanceCard" class="p-2.5 bg-light rounded-3 border d-flex flex-wrap align-items-center justify-content-between gap-2" style="display: none !important;">
                                <div>
                                    <span class="small fw-semibold text-dark d-block" id="vendorNameText">Supplier Balance Overview</span>
                                    <span class="text-muted" style="font-size: 11px;">Active account balances &amp; dues</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <div id="vendorOpeningDueWrapper">
                                        <span class="text-muted d-block" style="font-size: 10px;">Opening Due:</span>
                                        <span class="fw-bold text-dark fs-8" id="vendorOpeningDueBadge">৳ 0.00</span>
                                    </div>
                                    <div id="vendorPurchaseDueWrapper">
                                        <span class="text-muted d-block" style="font-size: 10px;">PO Dues:</span>
                                        <span class="fw-bold text-dark fs-8" id="vendorPurchaseDueBadge">৳ 0.00</span>
                                    </div>
                                    <div id="vendorAdvanceWrapper">
                                        <span class="text-muted d-block" style="font-size: 10px; color:#198754 !important;">Advance Balance:</span>
                                        <span class="fw-bold text-success fs-8" id="vendorAdvanceBadge">৳ 0.00</span>
                                    </div>
                                    <div id="vendorTotalDueWrapper">
                                        <span class="text-muted d-block" style="font-size: 10px;">Net Total Due:</span>
                                        <span class="fw-bold text-danger fs-8" id="vendorTotalDueBadge">৳ 0.00</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-muted small" id="defaultVendorHelpText">
                                <i class="fe fe-info text-info me-1"></i>If selected, all steel items will automatically use this vendor and per-item vendor selection will be hidden.
                            </div>
                        </div>
                    </div>

                    <!-- NEW LOT FIELDS -->
                    <div id="newLotContainer" style="{{ old('lot_type', 'new') === 'new' ? '' : 'display: none;' }}">
                        <div class="row g-3">
                            <div class="col-lg-4 col-md-6 col-12">
                                <label for="new_lot_number" class="form-label fw-semibold small text-secondary mb-1">
                                    New Lot Number <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-primary"><i class="fe fe-tag"></i></span>
                                    <input type="text" name="new_lot_number" id="new_lot_number" class="form-control fw-bold text-dark font-monospace"
                                        value="{{ old('new_lot_number', $suggestedLotNumber ?? '') }}">
                                </div>
                                <small class="text-muted fs-8">Auto-generated, editable</small>
                            </div>

                            <div class="col-lg-4 col-md-6 col-12">
                                <label class="form-label fw-semibold small text-secondary mb-1">
                                    Shop Intake Location <span class="text-danger">*</span>
                                    <span class="badge bg-primary-subtle text-primary border ms-1"><i class="fe fe-shopping-cart me-1"></i>Shop Locked</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-primary border-primary-subtle"><i class="fe fe-home"></i></span>
                                    <input type="text" class="form-control bg-light fw-bold text-dark border-primary-subtle" value="{{ $shop->name }} {{ $shop->code ? '('.$shop->code.')' : '' }}" readonly>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6 col-12">
                                <label for="purchase_date" class="form-label fw-semibold small text-secondary mb-1">
                                    Intake Date <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fe fe-calendar"></i></span>
                                    <input type="date" name="purchase_date" id="purchase_date" class="form-control"
                                        value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-6 col-12">
                                <label for="lot_name" class="form-label fw-semibold small text-secondary mb-1">
                                    Lot / Vessel / Shipment Description <span class="text-muted fw-normal">(Optional)</span>
                                </label>
                                <input type="text" name="lot_name" id="lot_name" class="form-control"
                                    placeholder="e.g. Retail Shop Intake Batch #01" value="{{ old('lot_name') }}">
                            </div>

                            <div class="col-lg-6 col-md-12 col-12">
                                <label for="lot_notes" class="form-label fw-semibold small text-secondary mb-1">
                                    Lot Notes / Shipment Remarks <span class="text-muted fw-normal">(Optional)</span>
                                </label>
                                <input type="text" name="lot_notes" id="lot_notes" class="form-control"
                                    placeholder="Internal memo, delivery challan #, or mill details" value="{{ old('lot_notes') }}">
                            </div>
                        </div>
                    </div>

                    <!-- EXISTING LOT FIELDS -->
                    <div id="existingLotContainer" style="{{ old('lot_type') === 'existing' ? '' : 'display: none;' }}">
                        <div class="row g-3">
                            <div class="col-lg-6 col-md-6 col-12">
                                <label for="purchase_lot_id" class="form-label fw-semibold small text-secondary mb-1">
                                    Select Existing Lot in this Shop <span class="text-danger">*</span>
                                </label>
                                <select id="purchase_lot_id" name="lot_id" class="form-select select2" onchange="handleExistingLotChange(this)">
                                    <option value="">-- Choose Existing Lot --</option>
                                    @foreach ($lots as $lot)
                                        @php
                                            $lotVendorName = $lot->vendor->name ?? $lot->vendor_name ?? 'Multiple Vendors';
                                            $lotTotalWeight = $lot->purchases->sum('total_weight');
                                            $lotTotalCoils = $lot->purchases->sum('quantity');
                                        @endphp
                                        <option value="{{ $lot->id }}"
                                            data-vendor-id="{{ $lot->vendor_id }}"
                                            data-vendor-name="{{ $lotVendorName }}"
                                            data-lot-number="{{ $lot->lot_number }}"
                                            data-total-weight="{{ $lotTotalWeight }}"
                                            data-total-coils="{{ $lotTotalCoils }}"
                                            {{ old('lot_id') == $lot->id ? 'selected' : '' }}>
                                            {{ $lot->lot_number }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6 col-md-6 col-12">
                                <div class="p-3 bg-light rounded-3 border h-100" id="existingLotDetailsCard" style="display: none;">
                                    <div class="fw-semibold text-dark mb-1" id="existingLotNumberDisplay">Lot Selected</div>
                                    <div class="small text-muted" id="existingLotMetaDisplay">Supplier &amp; Intake Details</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Steel & Coil Intake (Persistent Entry Form + Detailed Items Table) -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fe fe-disc text-primary me-2"></i>Steel Items &amp; Stock Intake
                        </h6>
                        <small class="text-muted">Enter coil dimensions, weight, and rate, then click <strong>"Add to Table"</strong>. The entry form remains ready for your next item.</small>
                    </div>
                </div>

                <div class="card-body p-3 p-md-4 bg-light-subtle">
                    <!-- Builder Form Card (Fixed Persistent Form) -->
                    <div class="builder-card p-3 p-md-4 shadow-sm mb-4">

                        <!-- Line 0: Dedicated Vendor Selection Bar -->
                        <div class="row g-3 mb-3 pb-3 border-bottom align-items-center" id="builderVendorRow">
                            <div class="col-lg-5 col-md-6 col-12">
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
                            <div class="col-lg-7 col-md-6 col-12">
                                <div class="text-muted small mt-md-4 pt-md-1">
                                    <i class="fe fe-info text-info me-1"></i>Select the supplier for the coils/steel plates you are adding below.
                                </div>
                            </div>
                        </div>

                        <!-- Line 1: Physical Specifications & Dimensions -->
                        <div class="row g-2 mb-3">
                            <div class="col-lg-3 col-md-6 col-12">
                                <label for="builder_product_name" class="form-label small text-secondary fw-semibold mb-1">
                                    Product Name
                                </label>
                                <input type="text" id="builder_product_name" class="form-control form-control-sm">
                            </div>

                            <div class="col-lg-2 col-md-6 col-12">
                                <label for="builder_coil_number" class="form-label small text-secondary fw-semibold mb-1">
                                    Coil / Tag No
                                </label>
                                <input type="text" id="builder_coil_number" class="form-control form-control-sm font-monospace">
                            </div>

                            <div class="col-lg-2 col-md-3 col-6">
                                <label for="builder_thickness" class="form-label small text-secondary fw-semibold mb-1">
                                    Thickness
                                </label>
                                <input type="text" id="builder_thickness" class="form-control form-control-sm text-center" placeholder="e.g. 2.50mm">
                            </div>

                            <div class="col-lg-3 col-md-3 col-6">
                                <label for="builder_size" class="form-label small text-secondary fw-semibold mb-1">
                                    Size / Width
                                </label>
                                <input type="text" id="builder_size" class="form-control form-control-sm text-center" placeholder="e.g. 4x8">
                            </div>

                            <div class="col-lg-2 col-md-6 col-6">
                                <label for="builder_size_type" class="form-label small text-secondary fw-semibold mb-1">
                                    Size Type
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
                        </div>

                        <!-- Line 2: Weights, Rates & Action Button -->
                        <div class="row g-2 align-items-end p-3 rounded-2 bg-light border border-light-subtle">
                            <div class="col-lg-2 col-md-4 col-6">
                                <label for="builder_quantity" class="form-label small text-secondary fw-semibold mb-1">
                                    Coil / Piece Qty <span class="text-danger">*</span>
                                </label>
                                <input type="number" min="1" step="1" id="builder_quantity" class="form-control form-control-sm text-center fw-bold" value="1" oninput="calculateBuilderPreview()">
                            </div>

                            <div class="col-lg-2 col-md-4 col-6">
                                <label for="builder_unit_weight" class="form-label small text-secondary fw-semibold mb-1">
                                    Per Coil Wt (kg) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" min="0.01" id="builder_unit_weight" class="form-control text-end fw-bold text-primary" oninput="calculateBuilderPreview()">
                                    <span class="input-group-text bg-white text-muted">kg</span>
                                </div>
                            </div>

                            <div class="col-lg-2 col-md-4 col-6">
                                <label for="builder_total_weight" class="form-label small text-secondary fw-semibold mb-1">
                                    Total Weight (kg)
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" id="builder_total_weight" class="form-control bg-white text-end fw-bold text-dark" readonly tabindex="-1">
                                    <span class="input-group-text bg-white text-muted">kg</span>
                                </div>
                            </div>

                            <div class="col-lg-2 col-md-4 col-6">
                                <label for="builder_unit_price" class="form-label small text-secondary fw-semibold mb-1">
                                    Unit Rate (৳/kg) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-muted">৳</span>
                                    <input type="number" step="0.01" min="0" id="builder_unit_price" class="form-control text-end fw-semibold" oninput="calculateBuilderPreview()">
                                </div>
                            </div>

                            <div class="col-lg-2 col-md-4 col-6">
                                <label for="builder_sub_price" class="form-label small text-secondary fw-semibold mb-1">
                                    Line Sub Total (৳)
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-muted">৳</span>
                                    <input type="number" step="0.01" id="builder_sub_price" class="form-control bg-white text-end fw-bold text-success" readonly tabindex="-1">
                                </div>
                            </div>

                            <div class="col-lg-2 col-md-4 col-12">
                                <button type="button" class="btn btn-primary btn-sm w-100 shadow-sm fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 py-1.5" id="addSteelBtn" onclick="addSteelItemToTable()" style="height: 31px;">
                                    <i class="fe fe-plus-circle"></i>
                                    <span>Add</span>
                                </button>
                            </div>
                        </div>

                        <!-- Optional Notes Row -->
                        <div class="row g-2 mt-2">
                            <div class="col-12">
                                <input type="text" id="builder_notes" class="form-control form-control-sm text-secondary" placeholder="Optional notes, challan details, or bundle remarks">
                            </div>
                        </div>
                    </div>

                    <!-- Added Steel Items Table Card -->
                    <div class="card border border-light-subtle rounded-3 shadow-none overflow-hidden bg-white mb-0">
                        <div class="card-header bg-light py-2.5 px-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fe fe-list text-primary me-1"></i>Added Steel Items &amp; Stock Table
                                </h6>
                                <span class="badge bg-primary rounded-pill px-2 py-0.5 fs-8" id="itemsCountBadge">0 Items</span>
                            </div>
                            <small class="text-muted">Items added below will be stored in this shop purchase lot</small>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-custom align-middle mb-0" id="steelItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 50px;">#</th>
                                        <th class="vendor-col" style="{{ old('default_vendor_id') ? 'display: none;' : '' }}">Vendor / Supplier</th>
                                        <th>Product &amp; Specifications</th>
                                        <th class="text-center" style="width: 90px;">Coil Qty</th>
                                        <th class="text-end" style="width: 120px;">Per Coil Wt</th>
                                        <th class="text-end" style="width: 130px;">Total Wt</th>
                                        <th class="text-end" style="width: 110px;">Rate (৳/kg)</th>
                                        <th class="text-end" style="width: 130px;">Sub Total (৳)</th>
                                        <th class="text-center" style="width: 60px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="steelTableBody">
                                    <!-- Dynamic Rows Inserted Here -->
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="3" class="text-end text-uppercase fs-8 text-secondary">Batch Totals:</td>
                                        <td class="text-center text-dark" id="displayTotalQuantity">0</td>
                                        <td></td>
                                        <td class="text-end text-primary" id="displayTotalWeight">0.00 kg</td>
                                        <td></td>
                                        <td class="text-end text-success fs-7" id="displayTotalSubPrice">৳ 0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Extra Charges & Adjustments Breakdown -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fe fe-truck text-primary me-2"></i>Extra Charges &amp; Logistics Breakdown
                        </h6>
                        <small class="text-muted">Pass-through freight, delivery, handling &amp; weighbridge surcharges</small>
                    </div>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-8 px-2.5 py-1" id="displayTotalChargesBadge">
                        Extra Charges: ৳ 0.00
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6 col-12">
                            <label for="delivery_charge" class="form-label fw-semibold small text-secondary mb-1">Delivery / Transport (৳)</label>
                            <input type="number" step="0.01" min="0" name="delivery_charge" id="delivery_charge" class="form-control"
                                value="{{ old('delivery_charge', 0) }}" oninput="calculateBatchTotals()">
                        </div>

                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label fw-semibold small text-secondary mb-1">Transport Payer</label>
                            <div class="d-flex gap-2 pt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="transport_payer" id="transport_payer_me" value="me"
                                        {{ old('transport_payer', 'me') === 'me' ? 'checked' : '' }} onchange="calculateBatchTotals()">
                                    <label class="form-check-label small fw-semibold" for="transport_payer_me">Paid by Shop</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="transport_payer" id="transport_payer_vendor" value="vendor"
                                        {{ old('transport_payer') === 'vendor' ? 'checked' : '' }} onchange="calculateBatchTotals()">
                                    <label class="form-check-label small fw-semibold" for="transport_payer_vendor">Paid by Supplier</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <label for="labour_cost" class="form-label fw-semibold small text-secondary mb-1">Labour Charge (৳)</label>
                            <input type="number" step="0.01" min="0" name="labour_cost" id="labour_cost" class="form-control"
                                value="{{ old('labour_cost', 0) }}" oninput="calculateBatchTotals()">
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <label for="weight_scale_cost" class="form-label fw-semibold small text-secondary mb-1">Scale Charge (৳)</label>
                            <input type="number" step="0.01" min="0" name="weight_scale_cost" id="weight_scale_cost" class="form-control"
                                value="{{ old('weight_scale_cost', 0) }}" oninput="calculateBatchTotals()">
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <label for="other_charges" class="form-label fw-semibold small text-secondary mb-1">Other Surcharges (৳)</label>
                            <input type="number" step="0.01" min="0" name="other_charges" id="other_charges" class="form-control"
                                value="{{ old('other_charges', 0) }}" oninput="calculateBatchTotals()">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Financial Settlement & Vendor Disbursement -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fe fe-dollar-sign text-primary me-2"></i>Financial Settlement &amp; Vendor Disbursement
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <!-- Left: Financial Calculations Summary -->
                        <div class="col-lg-6 col-12 border-end-lg">
                            <h6 class="fw-bold text-secondary mb-3 text-uppercase fs-8">Batch Financial Summary</h6>

                            <div class="p-3 bg-light rounded-3 mb-3 border">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Items Sub Total:</span>
                                    <span class="fw-bold text-dark" id="summarySubTotal">৳ 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Extra Charges:</span>
                                    <span class="fw-bold text-dark" id="summaryExtraCharges">৳ 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Discount:</span>
                                    <div style="width: 130px;">
                                        <input type="number" step="0.01" min="0" name="discount" id="batch_discount" class="form-control form-control-sm text-end"
                                            value="{{ old('discount', 0) }}" oninput="calculateBatchTotals()">
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark">Landed Cost / Net Payable:</span>
                                    <span class="fs-5 fw-bold text-primary" id="displayTotalPrice">৳ 0.00</span>
                                    <input type="hidden" name="total_price" id="total_price_hidden" value="0.00">
                                </div>
                            </div>

                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="p-2.5 bg-light rounded-3 border text-center">
                                        <span class="text-muted small d-block mb-1">Total Quantity:</span>
                                        <span class="fw-bold text-dark" id="displaySummaryQty">0 Pieces</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2.5 bg-light rounded-3 border text-center">
                                        <span class="text-muted small d-block mb-1">Total Weight:</span>
                                        <span class="fw-bold text-primary" id="displaySummaryWeight">0.00 MT</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Payment & Settlement Form -->
                        <div class="col-lg-6 col-12">
                            <h6 class="fw-bold text-secondary mb-3 text-uppercase fs-8">Payment Disbursement Details</h6>

                            <!-- Payment Allocation Mode Toggle -->
                            <div class="p-2.5 bg-light rounded-3 border mb-3" id="paymentModeToggleContainer" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-semibold text-dark small d-block">Multi-Vendor Detected</span>
                                        <span class="text-muted" style="font-size: 11px;">Select how to disburse payments to suppliers</span>
                                    </div>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <input type="radio" class="btn-check" name="payment_allocation_mode" id="mode_single" value="single" checked onchange="togglePaymentAllocationMode('single')">
                                        <label class="btn btn-outline-primary" for="mode_single">Single / Proportional</label>

                                        <input type="radio" class="btn-check" name="payment_allocation_mode" id="mode_individual" value="individual" onchange="togglePaymentAllocationMode('individual')">
                                        <label class="btn btn-outline-primary" for="mode_individual">Individual</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Single / Proportional Payment Section -->
                            <div id="singlePaymentSection">
                                <div class="row g-3 mb-3">
                                    <div class="col-12">
                                        <label for="batch_payment" class="form-label fw-semibold small text-secondary mb-1">
                                            Payment Amount (৳) <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-success fw-bold">৳</span>
                                            <input type="number" step="0.01" min="0" name="payment" id="batch_payment"
                                                class="form-control form-control-lg fw-bold text-success"
                                                value="{{ old('payment', 0) }}" oninput="calculateBatchTotals()">
                                            <button type="button" class="btn btn-outline-secondary" onclick="setFullPayment()">Full</button>
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
                                    @endphp

                                    <div class="col-12">
                                        <label class="form-label fw-semibold small text-secondary mb-1">
                                            <i class="fe fe-layers me-1 text-primary"></i> Disbursing Account / Channel <span class="text-danger">*</span>
                                        </label>
                                        <select id="purchaseAccountSelector" class="form-select border-light-subtle" onchange="handlePurchaseAccountChange(this)">
                                            <optgroup label="Cash">
                                                <option value="cash" data-method="cash">Hand Cash</option>
                                            </optgroup>
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
                                        <input type="hidden" name="payment_method" id="hidden_purchase_payment_method" value="bank">
                                        <input type="hidden" name="bank_detail_id" id="hidden_purchase_bank_detail_id" value="">
                                    </div>

                                    <!-- Transaction Ref (conditional) -->
                                    <div id="purchaseTransactionRefContainer" style="display: none;">
                                        <label class="form-label fw-semibold small text-secondary mb-1">Transaction Ref / TrxID / Cheque #</label>
                                        <input type="text" name="transaction_ref" id="purchaseTransactionRef" class="form-control border-light-subtle bg-white" placeholder="e.g. TrxID / Cheque #">
                                    </div>
                                </div>
                            </div>

                            <!-- Multi-Vendor Individual Payments Breakdown Section -->
                            <div id="multiVendorPaymentSection" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                    <span class="small text-muted fw-semibold">
                                        <i class="fe fe-info text-info me-1"></i>Pay each vendor individually:
                                    </span>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-success btn-sm py-0.5 px-2 fs-8 rounded-pill" onclick="setAllVendorsFullPayment()">
                                            <i class="fe fe-check-circle me-1"></i>Pay All Full
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2 fs-8 rounded-pill" onclick="clearAllVendorPayments()">
                                            Clear All
                                        </button>
                                    </div>
                                </div>

                                <div id="vendorPaymentCardsList" class="mb-3">
                                    <!-- Dynamic Vendor Payment Cards rendered by JavaScript -->
                                </div>

                                <div class="p-2.5 bg-light rounded-3 d-flex justify-content-between align-items-center mb-3 border">
                                    <span class="small text-secondary fw-semibold">Total Paid to All Vendors:</span>
                                    <span class="fw-bold text-success fs-6" id="displayTotalPaidMulti">৳ 0.00</span>
                                </div>
                            </div>

                            <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center mb-4 border">
                                <span class="fw-semibold text-secondary">Outstanding Due:</span>
                                <span class="fs-5 fw-bold text-danger" id="displayDueAmount">৳ 0.00</span>
                                <input type="hidden" name="due" id="due_hidden" value="0.00">
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 shadow-sm fw-bold fs-6 d-flex align-items-center justify-content-center gap-2" id="submitPurchaseBtn">
                                <span>Confirm &amp; Save Shop Purchase</span>
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

        function handleDefaultVendorChange(vendorId) {
            const selectEl = document.getElementById('default_vendor_id');
            const selectedOpt = selectEl ? selectEl.options[selectEl.selectedIndex] : null;

            if (vendorId && selectedOpt) {
                $('#builderVendorRow').hide();
                $('.vendor-col').hide();

                const openDue = parseFloat(selectedOpt.dataset.openingDue) || 0;
                const poDue = parseFloat(selectedOpt.dataset.purchaseDue) || 0;
                const advBal = parseFloat(selectedOpt.dataset.advanceBalance) || 0;
                const totDue = parseFloat(selectedOpt.dataset.totalDue) || (openDue + poDue);

                $('#vendorOpeningDueBadge').text('৳ ' + openDue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#vendorPurchaseDueBadge').text('৳ ' + poDue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#vendorAdvanceBadge').text('৳ ' + advBal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#vendorTotalDueBadge').text('৳ ' + totDue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#vendorNameText').text(selectedOpt.text.trim());

                $('#vendorBalanceCard').attr('style', 'display: flex !important;');
                $('#defaultVendorHelpText').hide();
            } else {
                $('#builderVendorRow').show();
                $('.vendor-col').show();
                $('#vendorBalanceCard').attr('style', 'display: none !important;');
                $('#defaultVendorHelpText').show();
            }
            calculateBatchTotals();
        }

        function handleExistingLotChange(selectEl) {
            const opt = selectEl.options[selectEl.selectedIndex];
            if (opt && opt.value) {
                const vendorName = opt.dataset.vendorName || '';
                const lotNum = opt.dataset.lotNumber || '';
                const totalWt = parseFloat(opt.dataset.totalWeight) || 0;
                const totalCoils = parseInt(opt.dataset.totalCoils) || 0;

                $('#existingLotNumberDisplay').text(lotNum + ' (' + vendorName + ')');
                $('#existingLotMetaDisplay').text(`Current Batch Stock: ${totalWt.toLocaleString()} kg • ${totalCoils} coils registered`);
                $('#existingLotDetailsCard').fadeIn(150);

                const vendorId = opt.dataset.vendorId;
                if (vendorId && !$('#default_vendor_id').val()) {
                    $('#builder_vendor_id').val(vendorId).trigger('change');
                }
            } else {
                $('#existingLotDetailsCard').hide();
            }
        }

        function calculateBuilderPreview() {
            const qty = parseInt($('#builder_quantity').val()) || 0;
            const uWeight = parseFloat($('#builder_unit_weight').val()) || 0;
            const rate = parseFloat($('#builder_unit_price').val()) || 0;

            const tWeight = qty * uWeight;
            const subPrice = tWeight * rate;

            $('#builder_total_weight').val(tWeight.toFixed(2));
            $('#builder_sub_price').val(subPrice.toFixed(2));
        }

        function addSteelItemToTable() {
            const defaultVendorId = $('#default_vendor_id').val();
            const vendorId = defaultVendorId || $('#builder_vendor_id').val();
            const vendorName = defaultVendorId 
                ? $('#default_vendor_id option:selected').text().split('(')[0].trim() 
                : $('#builder_vendor_id option:selected').text().split('(')[0].trim();

            if (!vendorId) {
                alert('Please select a Vendor/Supplier for this steel item.');
                if (defaultVendorId) {
                    $('#default_vendor_id').focus();
                } else {
                    $('#builder_vendor_id').focus();
                }
                return;
            }

            const qty = parseInt($('#builder_quantity').val()) || 0;
            const uWeight = parseFloat($('#builder_unit_weight').val()) || 0;
            const rate = parseFloat($('#builder_unit_price').val()) || 0;

            if (qty <= 0) {
                alert('Please enter a valid coil/piece quantity.');
                $('#builder_quantity').focus();
                return;
            }
            if (uWeight <= 0) {
                alert('Please enter a valid weight per coil.');
                $('#builder_unit_weight').focus();
                return;
            }
            if (rate < 0) {
                alert('Please enter a valid unit rate.');
                $('#builder_unit_price').focus();
                return;
            }

            const productName = $('#builder_product_name').val().trim();
            const coilNumber = $('#builder_coil_number').val().trim();
            const thickness = $('#builder_thickness').val().trim();
            const size = $('#builder_size').val().trim();
            const sizeType = $('#builder_size_type').val() || 'ft';
            const notes = $('#builder_notes').val().trim();

            const tWeight = (qty * uWeight).toFixed(2);
            const subPrice = (tWeight * rate).toFixed(2);

            const sizeTypeLabel = $('#builder_size_type option:selected').text();
            let specHtml = '';
            if (productName) {
                specHtml += `<div class="fw-bold text-dark fs-7 mb-1"><i class="fe fe-box text-primary me-1"></i>${escapeHtml(productName)}</div>`;
            }
            let badges = [];
            if (coilNumber) {
                badges.push(`<span class="badge bg-light text-dark font-monospace border"><i class="fe fe-tag me-1 text-primary"></i>#${escapeHtml(coilNumber)}</span>`);
            }
            if (thickness) {
                badges.push(`<span class="badge bg-light text-dark border"><i class="fe fe-layers me-1 text-primary"></i>Thickness: ${escapeHtml(thickness)}</span>`);
            }
            if (size) {
                badges.push(`<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="fe fe-maximize-2 me-1"></i>${escapeHtml(size)} (${sizeTypeLabel})</span>`);
            } else {
                badges.push(`<span class="badge bg-light text-muted border">${sizeTypeLabel}</span>`);
            }
            specHtml += `<div class="d-flex flex-wrap align-items-center gap-1">${badges.join(' ')}</div>`;
            if (notes) {
                specHtml += `<div class="small text-muted mt-1"><i class="fe fe-file-text me-1 text-info"></i>${escapeHtml(notes)}</div>`;
            }

            const isVendorHidden = defaultVendorId ? 'display: none;' : '';

            const rowHtml = `
                <tr class="item-row" data-index="${itemIndex}">
                    <td class="text-center">
                        <span class="badge bg-soft-dark rounded-pill px-2 py-0.5 row-serial-number">${itemIndex + 1}</span>
                    </td>
                    <td class="vendor-col" style="${isVendorHidden}">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 vendor-col-badge">
                            <i class="fe fe-user me-1"></i>${escapeHtml(vendorName || 'Vendor #' + vendorId)}
                        </span>
                        <input type="hidden" name="items[${itemIndex}][vendor_id]" class="row-vendor-id" value="${vendorId}">
                    </td>
                    <td>
                        ${specHtml}
                        <input type="hidden" name="items[${itemIndex}][product_name]" value="${escapeHtml(productName)}">
                        <input type="hidden" name="items[${itemIndex}][coil_number]" value="${escapeHtml(coilNumber)}">
                        <input type="hidden" name="items[${itemIndex}][thickness]" value="${escapeHtml(thickness)}">
                        <input type="hidden" name="items[${itemIndex}][size]" value="${escapeHtml(size)}">
                        <input type="hidden" name="items[${itemIndex}][size_type]" value="${escapeHtml(sizeType)}">
                        <input type="hidden" name="items[${itemIndex}][notes]" value="${escapeHtml(notes)}">
                    </td>
                    <td class="text-center fw-bold">
                        ${qty}
                        <input type="hidden" name="items[${itemIndex}][quantity]" class="row-quantity" value="${qty}">
                    </td>
                    <td class="text-end font-monospace">
                        ${parseFloat(uWeight).toFixed(2)} kg
                        <input type="hidden" name="items[${itemIndex}][unit_weight]" class="row-unit-weight" value="${uWeight}">
                    </td>
                    <td class="text-end font-monospace fw-bold text-primary">
                        ${parseFloat(tWeight).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} kg
                        <input type="hidden" name="items[${itemIndex}][total_weight]" class="row-total-weight" value="${tWeight}">
                    </td>
                    <td class="text-end font-monospace">
                        ৳ ${parseFloat(rate).toFixed(2)}
                        <input type="hidden" name="items[${itemIndex}][unit_price]" class="row-unit-price" value="${rate}">
                    </td>
                    <td class="text-end font-monospace fw-bold text-success">
                        ৳ ${parseFloat(subPrice).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                        <input type="hidden" name="items[${itemIndex}][sub_price]" class="row-sub-price" value="${subPrice}">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 rounded-2" onclick="removeSteelRow(this)">
                            <i class="fe fe-trash-2"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#steelTableBody').append(rowHtml);
            itemIndex++;

            // Reset builder fields
            $('#builder_product_name').val('');
            $('#builder_coil_number').val('');
            $('#builder_quantity').val('1');
            $('#builder_unit_weight').val('');
            $('#builder_total_weight').val('');
            $('#builder_sub_price').val('');
            $('#builder_notes').val('');

            calculateBatchTotals();
        }

        function removeSteelRow(btn) {
            $(btn).closest('tr').remove();
            renumberRows();
            calculateBatchTotals();
        }

        function renumberRows() {
            $('#steelTableBody tr.item-row').each(function(idx) {
                $(this).find('.row-serial-number').text(idx + 1);
            });
        }

        function calculateBatchTotals() {
            let totalQty = 0;
            let totalWeight = 0;
            let totalSubPrice = 0;

            const vendorTotals = {};

            $('#steelTableBody tr.item-row').each(function() {
                const qty = parseInt($(this).find('.row-quantity').val()) || 0;
                const weight = parseFloat($(this).find('.row-total-weight').val()) || 0;
                const sub = parseFloat($(this).find('.row-sub-price').val()) || 0;
                const vId = $(this).find('.row-vendor-id').val();

                totalQty += qty;
                totalWeight += weight;
                totalSubPrice += sub;

                if (vId) {
                    if (!vendorTotals[vId]) {
                        vendorTotals[vId] = { count: 0, weight: 0, sub: 0 };
                    }
                    vendorTotals[vId].count += qty;
                    vendorTotals[vId].weight += weight;
                    vendorTotals[vId].sub += sub;
                }
            });

            const rowCount = $('#steelTableBody tr.item-row').length;
            $('#itemsCountBadge').text(rowCount + (rowCount === 1 ? ' Item' : ' Items'));
            $('#displayTotalQuantity').text(totalQty.toLocaleString());
            $('#displaySummaryQty').text(totalQty.toLocaleString() + ' Pieces');
            $('#displayTotalWeight').text(totalWeight.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' kg');
            $('#displaySummaryWeight').text((totalWeight / 1000).toFixed(3) + ' MT');
            $('#displayTotalSubPrice').text('৳ ' + totalSubPrice.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#summarySubTotal').text('৳ ' + totalSubPrice.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));

            const delivery = parseFloat($('#delivery_charge').val()) || 0;
            const labour = parseFloat($('#labour_cost').val()) || 0;
            const scale = parseFloat($('#weight_scale_cost').val()) || 0;
            const other = parseFloat($('#other_charges').val()) || 0;
            const discount = parseFloat($('#batch_discount').val()) || 0;
            const transportPayer = $('input[name="transport_payer"]:checked').val() || 'me';

            const totalCharges = delivery + labour + scale + other;
            $('#displayTotalChargesBadge').text('Extra Charges: ৳ ' + totalCharges.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#summaryExtraCharges').text('৳ ' + totalCharges.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));

            const landedCost = Math.max(0, totalSubPrice + totalCharges - discount);
            $('#displayTotalPrice').text('৳ ' + landedCost.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#total_price_hidden').val(landedCost.toFixed(2));

            const payment = parseFloat($('#batch_payment').val()) || 0;
            const due = Math.max(0, landedCost - payment);
            $('#displayDueAmount').text('৳ ' + due.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#due_hidden').val(due.toFixed(2));
        }

        function setFullPayment() {
            const landedCost = parseFloat($('#total_price_hidden').val()) || 0;
            $('#batch_payment').val(landedCost.toFixed(2));
            calculateBatchTotals();
        }

        function handlePurchaseAccountChange(selectEl) {
            if (!selectEl) return;
            const selectedOpt = selectEl.options[selectEl.selectedIndex];
            if (!selectedOpt) return;
            const methodInput = document.getElementById('hidden_purchase_payment_method');
            const bankInput = document.getElementById('hidden_purchase_bank_detail_id');
            const refContainer = document.getElementById('purchaseTransactionRefContainer');

            const method = selectedOpt.getAttribute('data-method') || 'bank';
            const bankId = selectedOpt.getAttribute('data-id') || '';
            if (methodInput) methodInput.value = method;
            if (bankInput) bankInput.value = bankId;

            if (refContainer) {
                refContainer.style.display = (method === 'cash') ? 'none' : 'block';
            }
        }

        $(document).ready(function() {
            $('.select2').select2({ width: '100%' });
            handlePurchaseAccountChange(document.getElementById('purchaseAccountSelector'));
            handleDefaultVendorChange($('#default_vendor_id').val());
            calculateBatchTotals();
        });
    </script>
@endpush
