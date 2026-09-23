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

        <!-- Page Header -->
        <div class="page-header mb-4">
            <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="card-title fw-bold text-dark mb-1">Steel Purchase & Yard Intake</h4>
                    <p class="text-muted small mb-0">Record raw ship steel scrap, coils, plates intake directly into yard inventory</p>
                </div>
                <div>
                    <a href="{{ route('purchase.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3 shadow-sm">
                        <i class="fe fe-arrow-left me-2"></i>Back to Purchases
                    </a>
                </div>
            </div>
        </div>
        <!-- /Page Header -->

        <form action="{{ route('purchase.store') }}" method="POST" id="createPurchaseForm">
            @csrf

            <!-- Section 1: Lot & Yard Intake Details -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-1">
                                <i class="fe fe-anchor text-primary me-2"></i>Lot & Yard Intake Details
                            </h6>
                            <p class="text-muted small mb-0">Create a new ship lot on the fly or assign to an existing vessel batch</p>
                        </div>
                        <div class="btn-group shadow-sm rounded-3 gap-2" role="group" aria-label="Lot Mode Selection">
                            <input type="radio" class="btn-check" name="lot_type" id="lot_type_new" value="new" {{ old('lot_type', request('lot_id') ? 'existing' : 'new') === 'new' ? 'checked' : '' }} onchange="toggleLotMode('new')">
                            <label class="btn btn-outline-primary btn-sm px-3 py-2 fw-semibold" for="lot_type_new">
                                <i class="fe fe-plus-circle me-1"></i> Create New Lot
                            </label>

                            <input type="radio" class="btn-check" name="lot_type" id="lot_type_existing" value="existing" {{ old('lot_type', request('lot_id') ? 'existing' : 'new') === 'existing' ? 'checked' : '' }} onchange="toggleLotMode('existing')">
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
                                    <option value="{{ $vendor->id }}" {{ old('default_vendor_id', old('vendor_id')) == $vendor->id ? 'selected' : '' }}>
                                        {{ $vendor->name }} ({{ $vendor->phone }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-7 col-md-6 col-12">
                            <div class="text-muted small mt-md-4 pt-md-1">
                                <i class="fe fe-info text-info me-1"></i>If selected, all steel items will automatically use this vendor and per-item vendor selection will be hidden.
                            </div>
                        </div>
                    </div>

                    <!-- NEW LOT FIELDS -->
                    <div id="newLotContainer" style="{{ old('lot_type', request('lot_id') ? 'existing' : 'new') === 'new' ? '' : 'display: none;' }}">
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
                                <label for="warehouse_id" class="form-label fw-semibold small text-secondary mb-1">
                                    Stockyard Location <span class="text-danger">*</span>
                                </label>
                                <select id="warehouse_id" name="warehouse_id" class="form-select select2" required>
                                    <option value="">Select Stockyard Depot</option>
                                    @foreach ($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ $loop->first ? 'selected' : '' }}>
                                            {{ $wh->name }} {{ $wh->code ? '('.$wh->code.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-4 col-md-6 col-12">
                                <label for="purchase_date" class="form-label fw-semibold small text-secondary mb-1">
                                    Intake Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="purchase_date" id="purchase_date" class="form-control"
                                    value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                            </div>

                            <div class="col-12 mt-2">
                                <label for="lot_notes" class="form-label fw-semibold small text-secondary mb-1">
                                    Shipment Notes <span class="text-muted">(Optional)</span>
                                </label>
                                <input type="text" name="lot_notes" id="lot_notes" class="form-control"
                                    value="{{ old('lot_notes') }}">
                            </div>
                        </div>
                    </div>

                    <!-- EXISTING LOT FIELDS -->
                    <div id="existingLotContainer" style="{{ old('lot_type', request('lot_id') ? 'existing' : 'new') === 'existing' ? '' : 'display: none;' }}">
                        <div class="row g-3">
                            <div class="col-lg-4 col-md-6 col-12">
                                <label for="purchase_lot_id" class="form-label fw-semibold small text-secondary mb-1">
                                    Existing Purchase Lot <span class="text-danger">*</span>
                                </label>
                                <select id="purchase_lot_id" name="lot_id" class="form-select select2" onchange="handleExistingLotChange(this)">
                                    <option value="">Select Existing Purchase Lot</option>
                                    @foreach ($lots as $lot)
                                        @php
                                            $lotWarehouseId = $lot->purchases->first()?->warehouse_id;
                                            $lotVendorId = $lot->vendor_id ?: $lot->purchases->first()?->vendor_id;
                                        @endphp
                                        <option value="{{ $lot->id }}" 
                                            data-warehouse-id="{{ $lotWarehouseId }}"
                                            data-vendor-id="{{ $lotVendorId }}"
                                            {{ old('lot_id', request('lot_id')) == $lot->id ? 'selected' : '' }}>
                                            {{ $lot->lot_number }} {{ $lot->vendor_names ? '('.$lot->vendor_names.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-4 col-md-6 col-12">
                                <label for="warehouse_id_existing" class="form-label fw-semibold small text-secondary mb-1">
                                    Stockyard Location <span class="text-danger">*</span>
                                </label>
                                <select id="warehouse_id_existing" class="form-select select2" onchange="$('#warehouse_id').val($(this).val()).trigger('change')">
                                    <option value="">Select Stockyard Depot</option>
                                    @foreach ($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ $loop->first ? 'selected' : '' }}>
                                            {{ $wh->name }} {{ $wh->code ? '('.$wh->code.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-4 col-md-6 col-12">
                                <label for="purchase_date_existing" class="form-label fw-semibold small text-secondary mb-1">
                                    Intake Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" id="purchase_date_existing" class="form-control"
                                    value="{{ old('purchase_date', date('Y-m-d')) }}" onchange="$('#purchase_date').val($(this).val())">
                            </div>
                        </div>
                    </div>

                    <!-- Hidden vendor id field for submission fallback -->
                    <input type="hidden" name="vendor_id" id="vendor_hidden" value="{{ old('default_vendor_id', old('vendor_id')) }}">
                </div>
            </div>

            <!-- Section 2: Steel & Coil Intake (Persistent Entry Form + Detailed Items Table) -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fe fe-disc text-primary me-2"></i>Steel Items & Coil Intake
                        </h6>
                        <small class="text-muted">Enter coil dimensions, weight, and rate, then click <strong>"Add to Table"</strong>. The entry form remains ready for your next coil.</small>
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
                            <div class="col-lg-2 col-md-3 col-6">
                                <label for="builder_quantity" class="form-label small text-secondary fw-semibold mb-1">
                                    Coil / Piece Qty <span class="text-danger">*</span>
                                </label>
                                <input type="number" min="1" step="1" id="builder_quantity" class="form-control form-control-sm text-center fw-bold" value="1" oninput="calculateBuilderPreview()">
                            </div>

                            <div class="col-lg-2 col-md-3 col-6">
                                <label for="builder_thickness" class="form-label small text-secondary fw-semibold mb-1">
                                    Thickness
                                </label>
                                <input type="text" id="builder_thickness" class="form-control form-control-sm text-center">
                            </div>

                            <div class="col-lg-3 col-md-3 col-6">
                                <label for="builder_size" class="form-label small text-secondary fw-semibold mb-1">
                                    Size / Width
                                </label>
                                <input type="text" id="builder_size" class="form-control form-control-sm text-center">
                            </div>

                            <div class="col-lg-2 col-md-3 col-6">
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

                            <div class="col-lg-3 col-md-12 col-12">
                                <label for="builder_notes" class="form-label small text-secondary fw-semibold mb-1">
                                    Notes (Optional)
                                </label>
                                <input type="text" id="builder_notes" class="form-control form-control-sm">
                            </div>
                        </div>

                        <!-- Line 2: Weights, Rates & Action Button -->
                        <div class="row g-2 align-items-end p-3 rounded-2 bg-light border border-light-subtle">
                            <div class="col-lg-2 col-md-6 col-6">
                                <label for="builder_unit_weight" class="form-label small text-secondary fw-semibold mb-1">
                                    Per Coil Wt (kg) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" min="0.01" id="builder_unit_weight" class="form-control text-end fw-bold text-primary" oninput="calculateBuilderPreview()">
                                    <span class="input-group-text bg-white text-muted">kg</span>
                                </div>
                            </div>

                            <div class="col-lg-2 col-md-6 col-6">
                                <label for="builder_total_weight" class="form-label small text-secondary fw-semibold mb-1">
                                    Total Weight (kg)
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" id="builder_total_weight" class="form-control bg-white text-end fw-bold text-dark" readonly tabindex="-1">
                                    <span class="input-group-text bg-white text-muted">kg</span>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-6">
                                <label for="builder_unit_price" class="form-label small text-secondary fw-semibold mb-1">
                                    Unit Rate (৳/kg) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-muted">৳</span>
                                    <input type="number" step="0.01" min="0" id="builder_unit_price" class="form-control text-end fw-semibold" oninput="calculateBuilderPreview()">
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-6">
                                <label for="builder_sub_price" class="form-label small text-secondary fw-semibold mb-1">
                                    Line Sub Total (৳)
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-muted">৳</span>
                                    <input type="number" step="0.01" id="builder_sub_price" class="form-control bg-white text-end fw-bold text-success" readonly tabindex="-1">
                                </div>
                            </div>

                            <div class="col-lg-2 col-md-12 col-12">
                                <button type="button" class="btn btn-primary btn-sm w-100 shadow-sm fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 py-1.5" id="addSteelBtn" onclick="addSteelItemToTable()" style="height: 31px;">
                                    <i class="fe fe-plus-circle"></i>
                                    <span>Add</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Added Steel Items Table Card -->
                    <div class="card border border-light-subtle rounded-3 shadow-none overflow-hidden bg-white mb-0">
                        <div class="card-header bg-light py-2.5 px-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fe fe-list text-primary me-1"></i>Added Steel Items & Coils Table
                                </h6>
                                <span class="badge bg-primary rounded-pill px-2 py-0.5 fs-8" id="itemsCountBadge">0 Items</span>
                            </div>
                            <small class="text-muted">Items added below will be stored in this purchase lot</small>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-custom align-middle mb-0" id="steelItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 50px;">#</th>
                                        <th class="vendor-col" style="{{ old('default_vendor_id') ? 'display: none;' : '' }}">Vendor / Supplier</th>
                                        <th>Specifications & Dimensions</th>
                                        <th class="text-center" style="width: 90px;">Coil Qty</th>
                                        <th class="text-end" style="width: 120px;">Per Coil Wt</th>
                                        <th class="text-end" style="width: 130px;">Total Wt</th>
                                        <th class="text-end" style="width: 110px;">Rate (৳/kg)</th>
                                        <th class="text-end" style="width: 130px;">Sub Total (৳)</th>
                                        <th class="text-center" style="width: 60px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="steelTableBody">
                                    @php
                                        $oldItems = old('items', []);
                                        $sizeTypeLabels = [
                                            'ft' => 'Feet (ft)',
                                            'mm' => 'Millimeter (mm)',
                                            'inch' => 'Inch (in)',
                                            'm' => 'Meter (m)',
                                            'pcs' => 'Pieces (pcs)',
                                            'ton' => 'Ton'
                                        ];
                                    @endphp

                                    @if(!empty($oldItems) && is_array($oldItems))
                                        @foreach($oldItems as $idx => $item)
                                            @php
                                                $qty = max(1, (int)($item['quantity'] ?? 1));
                                                $uWeight = (float)($item['unit_weight'] ?? 0);
                                                $tWeight = (float)($item['total_weight'] ?? ($qty * $uWeight));
                                                $uPrice = (float)($item['unit_price'] ?? 0);
                                                $sPrice = (float)($item['sub_price'] ?? ($tWeight * $uPrice));
                                                $thk = $item['thickness'] ?? '';
                                                $sz = $item['size'] ?? '';
                                                $st = $item['size_type'] ?? 'ft';
                                                $stText = $sizeTypeLabels[$st] ?? $st;
                                                $nts = $item['notes'] ?? '';
                                                $itemVendorId = $item['vendor_id'] ?? old('default_vendor_id', old('vendor_id'));
                                                $itemVendor = $vendors->firstWhere('id', $itemVendorId);
                                            @endphp
                                            <tr class="item-row" data-index="{{ $idx }}">
                                                <td class="text-center">
                                                    <span class="badge bg-soft-dark rounded-pill px-2 py-0.5 row-serial-number">{{ $loop->iteration }}</span>
                                                </td>
                                                <td class="vendor-col" style="{{ old('default_vendor_id') ? 'display: none;' : '' }}">
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 vendor-col-badge">
                                                        <i class="fe fe-user me-1"></i>{{ $itemVendor ? $itemVendor->name : ($itemVendorId ? 'Vendor #'.$itemVendorId : 'Unassigned') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap align-items-center gap-1">
                                                        @if($thk)
                                                            <span class="badge bg-light text-dark border"><i class="fe fe-layers me-1 text-primary"></i>Thickness:{{ $thk }}</span>
                                                        @endif
                                                        @if($sz)
                                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="fe fe-maximize-2 me-1"></i>{{ $sz }} ({{ $stText }})</span>
                                                        @else
                                                            <span class="badge bg-light text-muted border">{{ $stText }}</span>
                                                        @endif
                                                    </div>
                                                    @if($nts)
                                                        <div class="small text-muted mt-1"><i class="fe fe-tag me-1 text-info"></i>{{ $nts }}</div>
                                                    @endif
                                                </td>
                                                <td class="text-center fw-bold">{{ $qty }}</td>
                                                <td class="text-end font-monospace">{{ number_format($uWeight, 2) }} kg</td>
                                                <td class="text-end font-monospace fw-bold text-primary">{{ number_format($tWeight, 2) }} kg</td>
                                                <td class="text-end font-monospace">৳ {{ number_format($uPrice, 2) }}</td>
                                                <td class="text-end font-monospace fw-bold text-success">৳ {{ number_format($sPrice, 2) }}</td>
                                                <td class="text-center">
                                                    <button type="button" onclick="removeSteelItem(this)" class="btn btn-sm btn-outline-danger border-0 rounded-2 px-2 py-1" title="Remove Coil">
                                                        <i class="fe fe-trash-2"></i>
                                                    </button>
                                                    <input type="hidden" name="items[{{ $idx }}][vendor_id]" class="item-vendor-id-input" value="{{ $itemVendorId }}">
                                                    <input type="hidden" name="items[{{ $idx }}][quantity]" class="item-qty-input" value="{{ $qty }}">
                                                    <input type="hidden" name="items[{{ $idx }}][thickness]" value="{{ $thk }}">
                                                    <input type="hidden" name="items[{{ $idx }}][size]" value="{{ $sz }}">
                                                    <input type="hidden" name="items[{{ $idx }}][size_type]" value="{{ $st }}">
                                                    <input type="hidden" name="items[{{ $idx }}][notes]" value="{{ $nts }}">
                                                    <input type="hidden" name="items[{{ $idx }}][unit_weight]" class="item-unit-weight-input" value="{{ $uWeight }}">
                                                    <input type="hidden" name="items[{{ $idx }}][net_weight]" value="{{ $uWeight }}">
                                                    <input type="hidden" name="items[{{ $idx }}][total_weight]" class="item-total-weight-input" value="{{ $tWeight }}">
                                                    <input type="hidden" name="items[{{ $idx }}][unit_price]" class="item-unit-price-input" value="{{ $uPrice }}">
                                                    <input type="hidden" name="items[{{ $idx }}][sub_price]" class="item-sub-price-input" value="{{ $sPrice }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif

                                    <tr id="emptyTablePlaceholder" style="{{ (!empty($oldItems) && count($oldItems) > 0) ? 'display: none;' : '' }}">
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <i class="fe fe-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                            <span class="fw-medium">No steel items added to this purchase yet.</span>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="table-light fw-bold" id="tableSummaryFooter" style="{{ (!empty($oldItems) && count($oldItems) > 0) ? '' : 'display: none;' }}">
                                    <tr>
                                        <td colspan="3" class="text-end pe-3">Batch Total:</td>
                                        <td class="text-center"><span class="badge bg-dark rounded-pill px-2.5 py-1" id="tfootTotalQty">0</span></td>
                                        <td></td>
                                        <td class="text-end"><span class="badge bg-primary-subtle text-primary px-2.5 py-1 fs-7" id="tfootTotalWeight">0.00 kg</span></td>
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
                            <input type="text" id="purchaseSubTotalDisplay" class="form-control border-light-subtle bg-light text-end fw-bold text-dark" readonly value="0.00">
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Discount Amount (৳)</label>
                            <input oninput="recalculateSummary()" onchange="recalculateSummary()" type="number" id="discount" name="discount" class="form-control border-light-subtle text-end" value="{{ old('discount', 0) }}" min="0" step="0.01">
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Transport Charge (৳)</label>
                            <input oninput="recalculateSummary()" onchange="recalculateSummary()" type="number" id="delivery_charge" name="delivery_charge" class="form-control border-light-subtle text-end" value="{{ old('delivery_charge', 0) }}" min="0" step="0.01">
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Cutting & Load-Unload (৳)</label>
                            <input oninput="recalculateSummary()" onchange="recalculateSummary()" type="number" id="labour_cost" name="labour_cost" class="form-control border-light-subtle text-end" value="{{ old('labour_cost', 0) }}" min="0" step="0.01">
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Scale & Labour Charge (৳)</label>
                            <input oninput="recalculateSummary()" onchange="recalculateSummary()" type="number" id="weight_scale_cost" name="weight_scale_cost" class="form-control border-light-subtle text-end" value="{{ old('weight_scale_cost', 0) }}" min="0" step="0.01">
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Other Charges (৳)</label>
                            <input oninput="recalculateSummary()" onchange="recalculateSummary()" type="number" id="other_charges" name="other_charges" class="form-control border-light-subtle text-end" value="{{ old('other_charges', 0) }}" min="0" step="0.01">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Summary, Payment Settlement & Submission -->
            <div class="row g-4 mb-4">
                <!-- Left: Intake Batch Stats Summary -->
                <div class="col-lg-7 col-12">
                    <div class="card border-0 shadow-sm rounded-3 h-100 summary-card">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="fe fe-check-square text-primary me-2"></i>Intake Batch Summary
                            </h6>
                            <div class="row g-3 text-center">
                                <div class="col-sm-3 col-6">
                                    <div class="bg-white p-3 rounded-3 shadow-sm border border-light-subtle h-100">
                                        <span class="text-secondary small d-block mb-1">Coil Quantity</span>
                                        <span class="fs-4 fw-bold text-dark" id="displayTotalCoils">0</span>
                                    </div>
                                </div>
                                <div class="col-sm-3 col-6">
                                    <div class="bg-white p-3 rounded-3 shadow-sm border border-light-subtle h-100">
                                        <span class="text-secondary small d-block mb-1">Intake Weight</span>
                                        <span class="fs-5 fw-bold text-primary"><span id="displayTotalNetWeight">0.00</span> <small class="fs-8 text-muted">kg</small></span>
                                    </div>
                                </div>
                                <div class="col-sm-3 col-6">
                                    <div class="bg-white p-3 rounded-3 shadow-sm border border-light-subtle h-100">
                                        <span class="text-secondary small d-block mb-1">Steel Sub Total</span>
                                        <span class="fs-6 fw-bold text-dark" id="displaySubTotal">৳ 0.00</span>
                                        <small class="text-muted fs-8 d-block mt-0.5">Charges: <span id="displayTotalCharges" class="text-primary fw-semibold">৳ 0.00</span></small>
                                    </div>
                                </div>
                                <div class="col-sm-3 col-6">
                                    <div class="bg-white p-3 rounded-3 shadow-sm border border-light-subtle h-100">
                                        <span class="text-secondary small d-block mb-1">Grand Purchase Bill</span>
                                        <span class="fs-5 fw-bold text-success" id="displayGrandTotal">৳ 0.00</span>
                                        <input type="hidden" name="grand_total" id="grand_total_hidden" value="0.00">
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
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fe fe-dollar-sign text-success me-2"></i>Payment Settlement
                                </h6>
                                <span id="paymentModeBadge" class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-8" style="display: none;">
                                    <i class="fe fe-users me-1"></i>Multi-Vendor Breakdown
                                </span>
                            </div>

                            <!-- Single Vendor Payment Section -->
                            <div id="singleVendorPaymentSection">
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
                                            value="{{ old('payment', '0.00') }}" oninput="recalculateSummary()" required>
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
                                        <option value="cash" selected>Cash in Hand</option>
                                        <option value="bank">Bank Transfer / MFS</option>
                                    </select>

                                    <!-- Bank Account Selector (conditional) -->
                                    <div id="purchaseBankAccountContainer" class="mb-2" style="display: none;">
                                        <label class="form-label fw-semibold small text-secondary mb-1">Disbursement Bank Account</label>
                                        <select name="bank_detail_id" id="purchaseBankDetail" class="form-select border-light-subtle">
                                            <option value="" selected>Select Bank Account</option>
                                            @foreach($bankAccounts ?? [] as $bank)
                                                <option value="{{ $bank->id }}">
                                                    {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Transaction Ref (conditional) -->
                                    <div id="purchaseTransactionRefContainer" style="display: none;">
                                        <label class="form-label fw-semibold small text-secondary mb-1">Transaction Ref / TrxID</label>
                                        <input type="text" name="transaction_ref" class="form-control border-light-subtle bg-white">
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
                                <span>Confirm & Save</span>
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
        let itemIndex = {{ (!empty($oldItems) && count($oldItems) > 0) ? count($oldItems) : 0 }};

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

        function handleExistingLotChange(selectEl) {
            const $selected = $(selectEl).find('option:selected');
            if (!$selected.length || !$selected.val()) return;

            const warehouseId = $selected.attr('data-warehouse-id') || $selected.data('warehouse-id');
            if (warehouseId) {
                $('#warehouse_id_existing').val(warehouseId).trigger('change');
                $('#warehouse_id').val(warehouseId).trigger('change');
            }
        }

        function handleDefaultVendorChange(val) {
            $('#vendor_hidden').val(val);
            if (val) {
                $('#builderVendorRow').slideUp(150);
                $('.vendor-col').hide();
                const vendorName = $('#default_vendor_id option:selected').text().trim();
                // Update all existing items in table to use the new default vendor
                $('#steelTableBody tr.item-row').each(function () {
                    $(this).find('.item-vendor-id-input').val(val);
                    $(this).find('.vendor-col-badge').html(`<i class="fe fe-user me-1"></i>${escapeHtml(vendorName)}`);
                });
            } else {
                $('#builderVendorRow').slideDown(150);
                $('.vendor-col').show();
            }
        }

        $(document).ready(function () {
            $('.select2').select2({
                width: '100%'
            });

            // Trigger Enter key in builder fields to add item
            $('#builder_quantity, #builder_thickness, #builder_size, #builder_notes, #builder_unit_weight, #builder_unit_price').on('keypress', function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    addSteelItemToTable();
                }
            });

            // Existing lot select change listener
            $('#purchase_lot_id').on('change select2:select', function () {
                handleExistingLotChange(this);
            });

            // Initial mode trigger
            if ($('#lot_type_existing').is(':checked')) {
                toggleLotMode('existing');
            } else {
                toggleLotMode('new');
            }

            // Auto-fill warehouse if a lot is already selected (e.g. from query param or old input)
            if ($('#purchase_lot_id').val()) {
                handleExistingLotChange($('#purchase_lot_id')[0]);
            }

            // Initial default vendor state
            const initialDefaultVendor = $('#default_vendor_id').val();
            if (initialDefaultVendor) {
                $('#builderVendorRow').hide();
                $('.vendor-col').hide();
                $('#vendor_hidden').val(initialDefaultVendor);
            }

            // Initial payment method trigger (disables bank account inputs if cash)
            handlePurchasePaymentMethodChange($('#purchasePaymentMethod').val());

            // Calculate summary on page load (in case old items were re-rendered)
            recalculateSummary();
        });

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
                specHtml += `<span class="badge bg-light text-dark border me-1"><i class="fe fe-layers me-1 text-primary"></i>Thickness: ${escapeHtml(thickness)}</span>`;
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

            // Clean reset of builder form fields
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

        const vendorsMap = @json($vendors->keyBy('id'));
        const bankAccountsList = @json($bankAccounts ?? []);

        function roundTo2(num) {
            return Math.round((num + Number.EPSILON) * 100) / 100;
        }

        function updateTableNumbers() {
            $('#steelTableBody tr.item-row').each(function (index) {
                $(this).find('.row-serial-number').text(index + 1);
            });
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

            // Financial adjustments
            const discount = parseFloat($('#discount').val()) || 0;
            const delivery = parseFloat($('#delivery_charge').val()) || 0;
            const labour = parseFloat($('#labour_cost').val()) || 0;
            const scale = parseFloat($('#weight_scale_cost').val()) || 0;
            const other = parseFloat($('#other_charges').val()) || 0;

            const totalCharges = delivery + labour + scale + other;
            const grandTotal = Math.max(0, (subTotal + totalCharges) - discount);

            const formattedSubTotal = subTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            const formattedTotalCharges = totalCharges.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            const formattedGrandTotal = grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            const formattedTotalWeight = totalWeight.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            // Update subtotal display input
            $('#purchaseSubTotalDisplay').val(subTotal.toFixed(2));

            // Update Section 3 displays
            $('#displayTotalCoils').text(totalCoils);
            $('#displayTotalNetWeight').text(formattedTotalWeight);
            $('#displaySubTotal').text('৳ ' + formattedSubTotal);
            $('#displayTotalCharges').text('৳ ' + formattedTotalCharges);
            $('#displayGrandTotal').text('৳ ' + formattedGrandTotal);
            $('#grand_total_hidden').val(grandTotal.toFixed(2));
            $('#maxBillFormatted').text('৳ ' + formattedGrandTotal);

            // Update Table Badges & Footers
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

            // Single Vendor vs Multi-Vendor Mode
            if (uniqueVendorIds.length <= 1) {
                $('#singleVendorPaymentSection').show();
                $('#multiVendorPaymentSection').hide();
                $('#paymentModeBadge').hide();
                $('#vendorPaymentCardsList').empty();

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
            } else {
                $('#singleVendorPaymentSection').hide();
                $('#multiVendorPaymentSection').show();
                $('#paymentModeBadge').show();

                // Preserve user-entered values
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

                // Calculate each vendor's bill
                uniqueVendorIds.forEach(vId => {
                    const vRatio = subTotal > 0 ? (vendorMetrics[vId].subTotal / subTotal) : (1 / uniqueVendorIds.length);
                    const vNetCharges = (totalCharges - discount) * vRatio;
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
                        <div class="card border border-light-subtle rounded-3 p-3 mb-3 vendor-payment-card bg-white shadow-sm" data-vendor-id="${vId}">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <div>
                                    <h6 class="fw-bold text-dark mb-0"><i class="fe fe-user text-primary me-1"></i>${escapeHtml(vInfo.name)}</h6>
                                    <small class="text-muted">${m.qty} ${m.qty === 1 ? 'item' : 'items'} • ${m.weight.toFixed(2)} kg</small>
                                </div>
                                <div class="text-end">
                                    <span class="small text-muted d-block fs-8">Vendor Bill</span>
                                    <span class="fw-bold text-dark fs-6 v-bill-text">৳ ${m.bill.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                    <input type="hidden" class="v-bill-val" value="${m.bill.toFixed(2)}">
                                </div>
                            </div>

                            <div class="row g-2 align-items-center">
                                <div class="col-sm-6 col-12">
                                    <label class="form-label small text-secondary fw-semibold mb-1">Paid to Vendor (৳)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light text-success fw-bold">৳</span>
                                        <input type="number" step="0.01" min="0" name="vendor_payments[${vId}][amount]" 
                                            class="form-control fw-bold text-success vendor-pay-input" 
                                            data-vendor-id="${vId}" value="${prevPay}" oninput="onVendorPaymentChange(this)">
                                        <button type="button" class="btn btn-outline-success btn-sm px-2" onclick="setVendorPayFull(this)">Pay Full</button>
                                    </div>
                                    <div class="vendor-pay-error text-danger small mt-1 fs-8" style="${vPaid > m.bill + 0.009 ? '' : 'display: none;'}">
                                        <i class="fe fe-alert-triangle me-1"></i>Exceeds bill (৳ ${m.bill.toFixed(2)})
                                    </div>
                                </div>
                                <div class="col-sm-6 col-12">
                                    <label class="form-label small text-secondary fw-semibold mb-1">Payment Method</label>
                                    <select name="vendor_payments[${vId}][payment_method]" class="form-select form-select-sm vendor-method-select" onchange="onVendorMethodChange(this)">
                                        <option value="cash" ${prevMethod === 'cash' ? 'selected' : ''}>Cash in Hand</option>
                                        <option value="bank" ${prevMethod === 'bank' ? 'selected' : ''}>Bank Transfer / MFS</option>
                                    </select>
                                </div>
                                <div class="col-12 vendor-bank-row mt-1" style="${prevMethod === 'cash' ? 'display: none;' : ''}">
                                    <div class="row g-2">
                                        <div class="col-sm-6 col-12">
                                            <select name="vendor_payments[${vId}][bank_detail_id]" class="form-select form-select-sm vendor-bank-select">
                                                ${bankOptionsHtml}
                                            </select>
                                        </div>
                                        <div class="col-sm-6 col-12">
                                            <input type="text" name="vendor_payments[${vId}][transaction_ref]" class="form-control form-select-sm vendor-ref-input" placeholder="TrxID / Cheque #" value="${escapeHtml(prevRef)}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                                <span class="small text-muted fw-semibold">Vendor Due:</span>
                                <span class="fw-bold text-danger fs-7 v-due-text">৳ ${vDue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                            </div>
                            <input type="hidden" name="vendor_payments[${vId}][vendor_id]" value="${vId}">
                        </div>
                    `;
                });

                $('#vendorPaymentCardsList').html(cardsHtml);

                // Restore selected bank dropdowns
                uniqueVendorIds.forEach(vId => {
                    if (existingVendorBank[vId]) {
                        $(`.vendor-payment-card[data-vendor-id="${vId}"] .vendor-bank-select`).val(existingVendorBank[vId]);
                    }
                });

                updateMultiVendorPaymentTotals();
            }
        }

        function onVendorPaymentChange(input) {
            const card = $(input).closest('.vendor-payment-card');
            const vBill = parseFloat(card.find('.v-bill-val').val()) || 0;
            const vPaid = parseFloat($(input).val()) || 0;

            if (vPaid > vBill + 0.009) {
                card.find('.vendor-pay-error').show();
                $(input).addClass('is-invalid border-danger');
            } else {
                card.find('.vendor-pay-error').hide();
                $(input).removeClass('is-invalid border-danger');
            }

            const vDue = Math.max(0, vBill - vPaid);
            card.find('.v-due-text').text('৳ ' + vDue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

            updateMultiVendorPaymentTotals();
        }

        function onVendorMethodChange(select) {
            const card = $(select).closest('.vendor-payment-card');
            const bankRow = card.find('.vendor-bank-row');
            if ($(select).val() === 'cash') {
                bankRow.hide();
                bankRow.find('.vendor-bank-select').val('');
                bankRow.find('.vendor-ref-input').val('');
            } else {
                bankRow.show();
            }
        }

        function setVendorPayFull(btn) {
            const card = $(btn).closest('.vendor-payment-card');
            const vBill = parseFloat(card.find('.v-bill-val').val()) || 0;
            const input = card.find('.vendor-pay-input');
            input.val(vBill.toFixed(2));
            onVendorPaymentChange(input[0]);
        }

        function setAllVendorsFullPayment() {
            $('.vendor-payment-card').each(function () {
                const vBill = parseFloat($(this).find('.v-bill-val').val()) || 0;
                const input = $(this).find('.vendor-pay-input');
                input.val(vBill.toFixed(2));
                onVendorPaymentChange(input[0]);
            });
        }

        function clearAllVendorPayments() {
            $('.vendor-payment-card').each(function () {
                const input = $(this).find('.vendor-pay-input');
                input.val('0.00');
                onVendorPaymentChange(input[0]);
            });
        }

        function updateMultiVendorPaymentTotals() {
            let totalPaid = 0;
            $('.vendor-pay-input').each(function () {
                totalPaid += parseFloat($(this).val()) || 0;
            });

            const grandTotal = parseFloat($('#grand_total_hidden').val()) || 0;
            $('#displayTotalPaidMulti').text('৳ ' + totalPaid.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#paymentInput').val(totalPaid.toFixed(2));

            const totalDue = Math.max(0, grandTotal - totalPaid);
            $('#displayDueAmount').text('৳ ' + totalDue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#due_hidden').val(totalDue.toFixed(2));
        }

        function setFullPayment() {
            const grandTotal = parseFloat($('#grand_total_hidden').val()) || 0;
            $('#paymentInput').val(grandTotal.toFixed(2));
            recalculateSummary();
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
                    bankSelect.disabled = true;
                    bankSelect.value = '';
                }
                if (refInput) {
                    refInput.disabled = true;
                    refInput.value = '';
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

        $('#createPurchaseForm').on('submit', function (e) {
            const rowCount = $('#steelTableBody tr.item-row').length;

            // Check if user filled in builder fields but forgot to click "Add to Table"
            const builderUnitWeight = parseFloat($('#builder_unit_weight').val()) || 0;
            const builderUnitPrice = parseFloat($('#builder_unit_price').val());
            if (rowCount === 0 && builderUnitWeight > 0 && !isNaN(builderUnitPrice) && builderUnitPrice >= 0) {
                addSteelItemToTable();
            }

            const updatedRowCount = $('#steelTableBody tr.item-row').length;
            if (updatedRowCount === 0) {
                e.preventDefault();
                alert('Please add at least one steel item / coil to the table before submitting.');
                $('#builder_unit_weight').focus();
                return false;
            }

            // Check if every row has a vendor_id
            let missingVendor = false;
            let firstRowVendor = '';
            $('#steelTableBody tr.item-row').each(function (idx) {
                const vId = $(this).find('.item-vendor-id-input').val();
                if (!vId) {
                    missingVendor = true;
                } else if (idx === 0) {
                    firstRowVendor = vId;
                }
            });

            if (missingVendor) {
                e.preventDefault();
                alert('One or more items in the table are missing a vendor. Please remove and re-add them with a vendor selected.');
                return false;
            }

            const grandTotal = parseFloat($('#grand_total_hidden').val()) || 0;
            const payment = parseFloat($('#paymentInput').val()) || 0;

            if ($('#lot_type_new').is(':checked')) {
                $('#vendor_hidden').val(firstRowVendor);
            } else {
                const existingLot = $('#purchase_lot_id').val();
                if (!existingLot) {
                    e.preventDefault();
                    alert('Please select an Existing Purchase Lot.');
                    $('#purchase_lot_id').select2('open');
                    return false;
                }
            }

            if (grandTotal <= 0) {
                e.preventDefault();
                alert('Please enter valid quantities, weights, and rates for your steel items.');
                return false;
            }

            // Check for multi-vendor overpayment errors
            let vendorOverpaid = false;
            $('.vendor-payment-card').each(function () {
                const vBill = parseFloat($(this).find('.v-bill-val').val()) || 0;
                const vPaid = parseFloat($(this).find('.vendor-pay-input').val()) || 0;
                if (vPaid > vBill + 0.009) {
                    vendorOverpaid = true;
                    $(this).find('.vendor-pay-input').focus();
                }
            });

            if (vendorOverpaid) {
                e.preventDefault();
                alert('One or more vendor payments exceed their respective bill. Please correct them before submitting.');
                return false;
            }

            if (payment > grandTotal + 0.009) {
                e.preventDefault();
                $('#paymentErrorMsg').show();
                $('#paymentInput').addClass('is-invalid border-danger').focus();
                alert('Payment amount (৳ ' + payment.toFixed(2) + ') cannot exceed the total purchase bill (৳ ' + grandTotal.toFixed(2) + '). Please adjust the payment.');
                return false;
            }
        });
    </script>
@endpush
