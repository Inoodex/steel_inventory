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
            white-space: nowrap;
        }
        .table-custom td {
            vertical-align: middle;
        }
        .table-responsive {
            overflow-x: auto !important;
        }
        .stat-banner {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #bbf7d0;
            border-radius: 12px;
        }
        .summary-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }
        .row-item {
            transition: background-color 0.2s ease;
        }
        .row-item:hover {
            background-color: #fafafa;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header mb-4">
            <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="card-title fw-bold text-dark mb-1">Opening Stock & Yard Intake</h4>
                    <p class="text-muted small mb-0">Record pre-existing steel inventory (coils, plates, scrap) directly into warehouse yards</p>
                </div>
                <div>
                    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                        <i class="fe fe-arrow-left"></i>
                        <span>Back to Inventory</span>
                    </a>
                </div>
            </div>
        </div>
        <!-- /Page Header -->

        <!-- Info Card Banner -->
        <!-- <div class="stat-banner p-3 p-md-4 mb-4">
            <div class="d-flex align-items-start gap-3">
                <div class="avatar avatar-md bg-success text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                    <i class="fe fe-layers fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-1">How Opening Stock Works</h6>
                    <p class="text-secondary small mb-0">
                        Opening stock allows you to register physical inventory currently in your yards without creating fake vendor purchase invoices or artificial payables. 
                        Once registered, coils immediately become available for <strong>Sales Orders</strong>, <strong>Cutting/Processing</strong>, and <strong>Inventory Valuation</strong>.
                    </p>
                </div>
            </div>
        </div> -->

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fe fe-alert-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong class="d-block mb-1"><i class="fe fe-alert-circle me-2"></i>Please resolve the following input issues:</strong>
                <ul class="mb-0 ps-3 small">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('inventory.opening-stock.store') }}" method="POST" id="openingStockForm">
            @csrf

            <!-- Default Batch Yard Selector -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-3 p-md-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-4 col-md-4 col-12">
                            <label class="form-label fw-bold small text-dark mb-1">Default Warehouse / Yard <span class="text-danger">*</span></label>
                            <select id="defaultWarehouse" class="form-select form-select-sm select2">
                                <option value="">Select Default Warehouse</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->id }}">{{ $wh->name }} {{ $wh->location ? '('.$wh->location.')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-4 col-md-4 col-12">
                            <label class="form-label fw-bold small text-dark mb-1">Default Lot / Consignment (Optional)</label>
                            <select id="defaultLot" class="form-select form-select-sm select2">
                                <option value="">None / Auto Opening</option>
                                @foreach($lots as $lot)
                                    <option value="{{ $lot->id }}">{{ $lot->lot_number }} {{ $lot->name ? '- '.$lot->name : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-4 col-md-4 col-12 d-flex align-items-center gap-2">
                            <button type="button" id="btnApplyDefaults" class="btn btn-outline-primary px-3 rounded-3 d-inline-flex align-items-center justify-content-center flex-fill" style="height: 38px;">
                                <i class="fe fe-check-circle me-1"></i>Apply to All Rows
                            </button>
                            <button type="button" id="btnAddFiveRows" class="btn btn-outline-secondary px-3 rounded-3 d-inline-flex align-items-center justify-content-center flex-fill" style="height: 38px;">
                                <i class="fe fe-plus me-1"></i>Add 5 More Rows
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Physical Steel Stock Registry (2-Line Row Layout per Item) -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fe fe-package text-primary me-2"></i>Physical Steel Stock Registry
                        </h6>
                    </div>
                    <span class="badge bg-primary-light text-primary rounded-pill px-3 py-2 fw-semibold" id="rowCountBadge">
                        1 Item
                    </span>
                </div>

                <div class="card-body p-3 p-md-4 bg-light-subtle">
                    <div id="stockItemsContainer" class="d-flex flex-column gap-3">
                        <!-- Initial Row 0 -->
                        <div class="row-item card border shadow-none rounded-3 mb-0 bg-white" data-index="0">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary text-white rounded-pill px-3 py-1 fs-7 fw-bold">
                                            Item #<span class="row-num">1</span>
                                        </span>
                                    </div>
                                    <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 rounded-2 btn-remove-row" title="Remove Item">
                                        <i class="fe fe-trash-2 me-1"></i>Remove
                                    </button>
                                </div>

                                <!-- Line 1: Location & Physical Specs -->
                                <div class="row g-2 mb-2">
                                    <div class="col-lg-3 col-md-6 col-12">
                                        <label class="form-label small fw-bold text-dark mb-1">Warehouse / Yard <span class="text-danger">*</span></label>
                                        <select name="items[0][warehouse_id]" class="form-select form-select-sm row-warehouse" required>
                                            <option value="">Select Warehouse</option>
                                            @foreach($warehouses as $wh)
                                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-md-6 col-12">
                                        <label class="form-label small fw-bold text-dark mb-1">Lot / Consignment</label>
                                        <select name="items[0][lot_id]" class="form-select form-select-sm row-lot">
                                            <option value="">None / Auto Opening</option>
                                            @foreach($lots as $lot)
                                                <option value="{{ $lot->id }}">{{ $lot->lot_number }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-md-4 col-6">
                                        <label class="form-label small fw-bold text-dark mb-1">Coil / Batch Tag</label>
                                        <input type="text" name="items[0][coil_number]" class="form-control form-control-sm" />
                                    </div>
                                    <div class="col-lg-2 col-md-4 col-6">
                                        <label class="form-label small fw-bold text-dark mb-1">Thickness <span class="text-danger">*</span></label>
                                        <input type="text" name="items[0][thickness]" class="form-control form-control-sm" required />
                                    </div>
                                    <div class="col-lg-1 col-md-2 col-6">
                                        <label class="form-label small fw-bold text-dark mb-1">Width <span class="text-danger">*</span></label>
                                        <input type="text" name="items[0][width]" class="form-control form-control-sm" required />
                                    </div>
                                    <div class="col-lg-2 col-md-2 col-6">
                                        <label class="form-label small fw-bold text-dark mb-1">Size Type <span class="text-danger">*</span></label>
                                        <select name="items[0][length]" class="form-select form-select-sm" required>
                                            <option value="ft" selected>Feet (ft)</option>
                                            <option value="mm">Millimeter (mm)</option>
                                            <option value="inch">Inch (in)</option>
                                            <option value="Coil">Coil</option>
                                            <option value="Plate">Plate</option>
                                            <option value="Standard">Standard</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Line 2: Quantities, Rates & Notes -->
                                <div class="row g-2">
                                    <div class="col-lg-2 col-md-3 col-6">
                                        <label class="form-label small fw-bold text-dark mb-1">Qty (Pcs) <span class="text-danger">*</span></label>
                                        <input type="number" step="any" min="0.01" name="items[0][piece_count]" class="form-control form-control-sm row-qty text-end" value="1" required />
                                    </div>
                                    <div class="col-lg-2 col-md-3 col-6">
                                        <label class="form-label small fw-bold text-dark mb-1">Net Weight (kg) <span class="text-danger">*</span></label>
                                        <input type="number" step="any" min="0.01" name="items[0][net_weight]" class="form-control form-control-sm row-weight text-end" required />
                                    </div>
                                    <div class="col-lg-2 col-md-3 col-6">
                                        <label class="form-label small fw-bold text-dark mb-1">Cost Rate (৳/kg)</label>
                                        <input type="number" step="any" min="0" name="items[0][rate_per_ton]" class="form-control form-control-sm row-rate text-end" value="0" />
                                    </div>
                                    <div class="col-lg-2 col-md-3 col-6">
                                        <label class="form-label small fw-bold text-dark mb-1">Total Valuation (৳)</label>
                                        <input type="text" class="form-control form-control-sm row-total text-end bg-light fw-bold text-success" readonly value="0.00" />
                                    </div>
                                    <div class="col-lg-4 col-md-12 col-12">
                                        <label class="form-label small fw-bold text-dark mb-1">Notes / Origin Remarks</label>
                                        <input type="text" name="items[0][notes]" class="form-control form-control-sm" value="Opening Stock" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <button type="button" id="btnAddRow" class="btn btn-outline-primary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                        <i class="fe fe-plus-circle"></i>
                        <span>Add Another Item Row</span>
                    </button>
                    <div class="text-muted small">
                        Tip: You can add multiple coils, plates, or batches across different warehouses at once.
                    </div>
                </div>
            </div>

            <!-- Live Summary Metrics & Submit -->
            <div class="card summary-card shadow-sm rounded-3 mb-4">
                <div class="card-body p-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-8 col-12">
                            <div class="row g-3 text-center text-md-start">
                                <div class="col-sm-3 col-6 border-end">
                                    <span class="text-muted small d-block">Total Items</span>
                                    <h5 class="fw-bold text-dark mb-0" id="summaryTotalItems">1</h5>
                                </div>
                                <div class="col-sm-3 col-6 border-end">
                                    <span class="text-muted small d-block">Total Pieces (Qty)</span>
                                    <h5 class="fw-bold text-primary mb-0" id="summaryTotalPieces">1</h5>
                                </div>
                                <div class="col-sm-3 col-6 border-end">
                                    <span class="text-muted small d-block">Total Weight</span>
                                    <h5 class="fw-bold text-success mb-0" id="summaryTotalWeight">0.00 <small class="text-muted fs-8">kg</small></h5>
                                </div>
                                <div class="col-sm-3 col-6">
                                    <span class="text-muted small d-block">Total Valuation</span>
                                    <h5 class="fw-bold text-info mb-0">৳ <span id="summaryTotalValuation">0.00</span></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-12 d-flex justify-content-lg-end justify-content-center gap-2">
                            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3">Cancel</a>
                            <button type="submit" id="btnSubmitStock" class="btn btn-success px-4 py-2 rounded-3 shadow d-inline-flex align-items-center gap-2 fw-semibold">
                                <span>Register Opening Stock</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </form>

    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({ width: '100%' });

    let rowIndex = 1;

    const warehouseOptions = `@foreach($warehouses as $wh)<option value="{{ $wh->id }}">{{ $wh->name }}</option>@endforeach`;
    const lotOptions = `@foreach($lots as $lot)<option value="{{ $lot->id }}">{{ $lot->lot_number }}</option>@endforeach`;

    function createRow(index) {
        const defaultWh = $('#defaultWarehouse').val();
        const defaultLot = $('#defaultLot').val();

        return `
            <div class="row-item card border shadow-none rounded-3 mb-0 bg-white" data-index="${index}">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white rounded-pill px-3 py-1 fs-7 fw-bold">
                                Item #<span class="row-num">${index + 1}</span>
                            </span>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 rounded-2 btn-remove-row" title="Remove Item">
                            <i class="fe fe-trash-2 me-1"></i>Remove
                        </button>
                    </div>

                    <!-- Line 1: Location & Physical Specs -->
                    <div class="row g-2 mb-2">
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label small fw-bold text-dark mb-1">Warehouse / Yard <span class="text-danger">*</span></label>
                            <select name="items[${index}][warehouse_id]" class="form-select form-select-sm row-warehouse" required>
                                <option value="">Select Warehouse</option>
                                ${warehouseOptions}
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6 col-12">
                            <label class="form-label small fw-bold text-dark mb-1">Lot / Consignment</label>
                            <select name="items[${index}][lot_id]" class="form-select form-select-sm row-lot">
                                <option value="">None / Auto Opening</option>
                                ${lotOptions}
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label small fw-bold text-dark mb-1">Coil / Batch Tag</label>
                            <input type="text" name="items[${index}][coil_number]" class="form-control form-control-sm" />
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label small fw-bold text-dark mb-1">Thickness <span class="text-danger">*</span></label>
                            <input type="text" name="items[${index}][thickness]" class="form-control form-control-sm" required />
                        </div>
                        <div class="col-lg-1 col-md-2 col-6">
                            <label class="form-label small fw-bold text-dark mb-1">Width <span class="text-danger">*</span></label>
                            <input type="text" name="items[${index}][width]" class="form-control form-control-sm" required />
                        </div>
                        <div class="col-lg-2 col-md-2 col-6">
                            <label class="form-label small fw-bold text-dark mb-1">Size Type <span class="text-danger">*</span></label>
                            <select name="items[${index}][length]" class="form-select form-select-sm" required>
                                <option value="ft" selected>Feet (ft)</option>
                                <option value="mm">Millimeter (mm)</option>
                                <option value="inch">Inch (in)</option>
                                <option value="Coil">Coil</option>
                                <option value="Plate">Plate</option>
                                <option value="Standard">Standard</option>
                            </select>
                        </div>
                    </div>

                    <!-- Line 2: Quantities, Rates & Notes -->
                    <div class="row g-2">
                        <div class="col-lg-2 col-md-3 col-6">
                            <label class="form-label small fw-bold text-dark mb-1">Qty (Pcs) <span class="text-danger">*</span></label>
                            <input type="number" step="any" min="0.01" name="items[${index}][piece_count]" class="form-control form-control-sm row-qty text-end" value="1" required />
                        </div>
                        <div class="col-lg-2 col-md-3 col-6">
                            <label class="form-label small fw-bold text-dark mb-1">Net Weight (kg) <span class="text-danger">*</span></label>
                            <input type="number" step="any" min="0.01" name="items[${index}][net_weight]" class="form-control form-control-sm row-weight text-end" required />
                        </div>
                        <div class="col-lg-2 col-md-3 col-6">
                            <label class="form-label small fw-bold text-dark mb-1">Cost Rate (৳/kg)</label>
                            <input type="number" step="any" min="0" name="items[${index}][rate_per_ton]" class="form-control form-control-sm row-rate text-end" value="0" />
                        </div>
                        <div class="col-lg-2 col-md-3 col-6">
                            <label class="form-label small fw-bold text-dark mb-1">Total Valuation (৳)</label>
                            <input type="text" class="form-control form-control-sm row-total text-end bg-light fw-bold text-success" readonly value="0.00" />
                        </div>
                        <div class="col-lg-4 col-md-12 col-12">
                            <label class="form-label small fw-bold text-dark mb-1">Notes / Origin Remarks</label>
                            <input type="text" name="items[${index}][notes]" class="form-control form-control-sm" value="Opening Stock" />
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function recalculate() {
        let totalItems = 0;
        let totalPieces = 0;
        let totalWeight = 0;
        let totalValuation = 0;

        $('#stockItemsContainer .row-item').each(function(idx) {
            $(this).find('.row-num').text(idx + 1);
            totalItems++;

            const qty = parseFloat($(this).find('.row-qty').val()) || 0;
            const weight = parseFloat($(this).find('.row-weight').val()) || 0;
            const rate = parseFloat($(this).find('.row-rate').val()) || 0;

            const rowTotal = weight * rate;
            $(this).find('.row-total').val(rowTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

            totalPieces += qty;
            totalWeight += weight;
            totalValuation += rowTotal;
        });

        $('#rowCountBadge').text(totalItems + (totalItems === 1 ? ' Item' : ' Items'));
        $('#summaryTotalItems').text(totalItems);
        $('#summaryTotalPieces').text(totalPieces.toLocaleString('en-US'));
        $('#summaryTotalWeight').html(totalWeight.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' <small class="text-muted fs-8">kg</small>');
        $('#summaryTotalValuation').text(totalValuation.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }

    // Add 1 Row
    $('#btnAddRow').on('click', function() {
        const newHtml = createRow(rowIndex);
        const $row = $(newHtml);
        const defaultWh = $('#defaultWarehouse').val();
        const defaultLot = $('#defaultLot').val();

        if (defaultWh) $row.find('.row-warehouse').val(defaultWh);
        if (defaultLot) $row.find('.row-lot').val(defaultLot);

        $('#stockItemsContainer').append($row);
        rowIndex++;
        recalculate();
    });

    // Add 5 Rows
    $('#btnAddFiveRows').on('click', function() {
        const defaultWh = $('#defaultWarehouse').val();
        const defaultLot = $('#defaultLot').val();

        for (let i = 0; i < 5; i++) {
            const newHtml = createRow(rowIndex);
            const $row = $(newHtml);
            if (defaultWh) $row.find('.row-warehouse').val(defaultWh);
            if (defaultLot) $row.find('.row-lot').val(defaultLot);
            $('#stockItemsContainer').append($row);
            rowIndex++;
        }
        recalculate();
    });

    // Apply Defaults
    $('#btnApplyDefaults').on('click', function() {
        const defaultWh = $('#defaultWarehouse').val();
        const defaultLot = $('#defaultLot').val();

        if (defaultWh) {
            $('.row-warehouse').val(defaultWh);
        }
        if (defaultLot) {
            $('.row-lot').val(defaultLot);
        }
    });

    // Remove row
    $(document).on('click', '.btn-remove-row', function() {
        if ($('#stockItemsContainer .row-item').length <= 1) {
            alert('At least one item row is required.');
            return;
        }
        $(this).closest('.row-item').remove();
        recalculate();
    });

    // Input changes
    $(document).on('input', '.row-qty, .row-weight, .row-rate', function() {
        recalculate();
    });

    // Initial calculation
    recalculate();
});
</script>
@endpush
