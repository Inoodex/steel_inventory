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
        .steel-row:hover {
            background-color: #fafbfc;
        }
        .summary-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
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

                    <!-- NEW LOT FIELDS -->
                    <div id="newLotContainer" style="{{ old('lot_type', 'new') === 'new' ? '' : 'display: none;' }}">
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6 col-12">
                                <label for="new_lot_number" class="form-label fw-semibold small text-secondary mb-1">
                                    New Lot Number <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-primary"><i class="fe fe-tag"></i></span>
                                    <input type="text" name="new_lot_number" id="new_lot_number" class="form-control fw-bold text-dark font-monospace"
                                        value="{{ old('new_lot_number', $suggestedLotNumber ?? '') }}" placeholder="e.g. LOT-20260818-0001">
                                </div>
                                <small class="text-muted fs-8">Auto-generated, editable</small>
                            </div>

                            <div class="col-lg-3 col-md-6 col-12">
                                <label for="new_vendor_id" class="form-label fw-semibold small text-secondary mb-1">
                                    Vendor / Ship Breaker <span class="text-danger">*</span>
                                </label>
                                <select id="new_vendor_id" name="new_vendor_id" class="form-select select2">
                                    <option value="">Select Vendor / Supplier</option>
                                    @foreach ($vendors as $vendor)
                                        <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                            {{ $vendor->name }} ({{ $vendor->phone }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-6 col-12">
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

                            <div class="col-lg-3 col-md-6 col-12">
                                <label for="purchase_date" class="form-label fw-semibold small text-secondary mb-1">
                                    Intake Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="purchase_date" id="purchase_date" class="form-control"
                                    value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                            </div>

                            <div class="col-12 mt-2">
                                <label for="lot_notes" class="form-label fw-semibold small text-secondary mb-1">
                                    Vessel / Shipment Notes <span class="text-muted">(Optional)</span>
                                </label>
                                <input type="text" name="lot_notes" id="lot_notes" class="form-control"
                                    value="{{ old('lot_notes') }}" placeholder="e.g. Vessel: MT Ocean Pride, Scrap Plate cuttings from Chittagong Ship Breaking Yard">
                            </div>
                        </div>
                    </div>

                    <!-- EXISTING LOT FIELDS -->
                    <div id="existingLotContainer" style="{{ old('lot_type') === 'existing' ? '' : 'display: none;' }}">
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6 col-12">
                                <label for="purchase_lot_id" class="form-label fw-semibold small text-secondary mb-1">
                                    Existing Purchase Lot <span class="text-danger">*</span>
                                </label>
                                <select id="purchase_lot_id" name="lot_id" class="form-select select2">
                                    <option value="">Select Existing Purchase Lot</option>
                                    @foreach ($lots as $lot)
                                        <option value="{{ $lot->id }}" 
                                            data-vendor-id="{{ $lot->vendor_id }}"
                                            data-vendor-name="{{ $lot->vendor ? $lot->vendor->name : 'No Vendor' }}"
                                            {{ old('lot_id', request('lot_id')) == $lot->id ? 'selected' : '' }}>
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
                                    placeholder="Auto-fills from selected Lot">
                            </div>

                            <div class="col-lg-3 col-md-6 col-12">
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

                            <div class="col-lg-3 col-md-6 col-12">
                                <label for="purchase_date_existing" class="form-label fw-semibold small text-secondary mb-1">
                                    Intake Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" id="purchase_date_existing" class="form-control"
                                    value="{{ old('purchase_date', date('Y-m-d')) }}" onchange="$('#purchase_date').val($(this).val())">
                            </div>
                        </div>
                    </div>

                    <!-- Hidden vendor id field for submission -->
                    <input type="hidden" name="vendor_id" id="vendor_hidden" value="{{ old('vendor_id') }}">
                </div>
            </div>

            <!-- Section 2: Steel & Coil Intake Line Items -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fe fe-disc text-primary me-2"></i>Steel Items & Coil Intake Grid
                        </h6>
                        <small class="text-muted">Specify coil quantity, thickness, size, per-coil weight, total weight, and unit rate</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary rounded-3 px-3 shadow-sm d-inline-flex align-items-center gap-2" id="addRowBtn" onclick="addSteelRow()">
                        <i class="fe fe-plus"></i>
                        <span>Add Steel Item / Coil</span>
                    </button>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom align-middle mb-0" id="steelItemsTable">
                            <thead class="bg-light text-secondary fs-7 text-uppercase">
                                <tr>
                                    <th style="width: 35px;" class="ps-3 text-center">#</th>
                                    <th style="width: 95px;">Coil No <span class="text-danger">*</span></th>
                                    <th style="width: 115px;">Thickness</th>
                                    <th style="width: 115px;">Size</th>
                                    <th style="width: 110px;">Size Type</th>
                                    <th style="min-width: 140px;">Per Coil Wt (kg) <span class="text-danger">*</span></th>
                                    <th style="min-width: 140px;">Total Wt (kg)</th>
                                    <th style="min-width: 135px;">Rate (৳) <span class="text-danger">*</span></th>
                                    <th style="min-width: 150px;">Sub Total (৳)</th>
                                    <th style="width: 45px;" class="text-center pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody id="steelRowsContainer">
                                <tr class="steel-row" data-index="0">
                                    <td class="ps-3 text-center text-muted small fw-semibold row-number">1</td>
                                    <td>
                                        <input oninput="calculateRow(this)" type="number" min="1" step="1" name="items[0][quantity]" class="form-control form-control-sm row-quantity text-center fw-bold" value="1" required>
                                    </td>
                                    <td>
                                        <input type="text" name="items[0][thickness]" class="form-control form-control-sm row-thickness text-center" placeholder="e.g. 16mm">
                                    </td>
                                    <td>
                                        <input type="text" name="items[0][size]" class="form-control form-control-sm row-size text-center" placeholder="e.g. 5x20">
                                    </td>
                                    <td>
                                        <select name="items[0][size_type]" class="form-select form-select-sm row-size-type">
                                            <option value="ft" selected>Feet (ft)</option>
                                            <option value="mm">Millimeter (mm)</option>
                                            <option value="inch">Inch (in)</option>
                                            <option value="m">Meter (m)</option>
                                            <option value="pcs">Pieces (pcs)</option>
                                            <option value="ton">Ton</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input oninput="calculateRow(this)" type="number" step="0.001" min="0.001" name="items[0][unit_weight]" class="form-control form-control-sm row-unit-weight text-end fw-bold text-primary" placeholder="0.000" required>
                                        <input type="hidden" name="items[0][net_weight]" class="row-net-weight" value="0">
                                    </td>
                                    <td>
                                        <input type="number" step="0.001" name="items[0][total_weight]" class="form-control form-control-sm row-total-weight bg-light text-end fw-bold text-dark" readonly placeholder="0.000">
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light text-muted">৳</span>
                                            <input oninput="calculateRow(this)" type="number" step="0.01" min="0" name="items[0][unit_price]" class="form-control row-rate text-end fw-semibold" placeholder="0.00" required>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light text-muted">৳</span>
                                            <input type="number" step="0.01" name="items[0][sub_price]" class="form-control row-sub-total bg-light text-end fw-bold text-dark" readonly placeholder="0.00">
                                        </div>
                                    </td>
                                    <td class="text-center pe-3">
                                        <button type="button" onclick="removeSteelRow(this)" class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1" title="Remove row">
                                            <i class="fe fe-trash-2"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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
                                <div class="col-sm-4 col-12">
                                    <div class="bg-white p-3 rounded-3 shadow-sm border border-light-subtle">
                                        <span class="text-secondary small d-block mb-1">Total Coils / Pieces</span>
                                        <span class="fs-4 fw-bold text-dark" id="displayTotalCoils">1</span>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-12">
                                    <div class="bg-white p-3 rounded-3 shadow-sm border border-light-subtle">
                                        <span class="text-secondary small d-block mb-1">Total Intake Weight</span>
                                        <span class="fs-4 fw-bold text-primary"><span id="displayTotalNetWeight">0.000</span> <small class="fs-7 text-muted">kg</small></span>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-12">
                                    <div class="bg-white p-3 rounded-3 shadow-sm border border-light-subtle">
                                        <span class="text-secondary small d-block mb-1">Grand Purchase Bill</span>
                                        <span class="fs-4 fw-bold text-dark" id="displayGrandTotal">৳ 0.00</span>
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
                                    <!-- <option value="mobile_banking">Mobile Banking (bKash/Nagad)</option> -->
                                </select>

                                <!-- Bank Account Selector (conditional) -->
                                <div id="purchaseBankAccountContainer" class="mb-2" style="display: none;">
                                    <label class="form-label fw-semibold small text-secondary mb-1">Disbursement Bank Account</label>
                                    <select name="bank_detail_id" id="purchaseBankDetail" class="form-select border-light-subtle">
                                        <option value="">Select Bank Account</option>
                                        @foreach($bankAccounts ?? [] as $bank)
                                            <option value="{{ $bank->id }}" {{ $bank->is_default ? 'selected' : '' }}>
                                                {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Transaction Ref (conditional) -->
                                <div id="purchaseTransactionRefContainer" style="display: none;">
                                    <label class="form-label fw-semibold small text-secondary mb-1">Transaction Ref / TrxID</label>
                                    <input type="text" name="transaction_ref" class="form-control border-light-subtle bg-white" placeholder="e.g. Bank Trx # or Deposit Slip Ref">
                                </div>
                            </div>

                            <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center mb-4">
                                <span class="fw-semibold text-secondary">Outstanding Due:</span>
                                <span class="fs-5 fw-bold text-danger" id="displayDueAmount">৳ 0.00</span>
                                <input type="hidden" name="due" id="due_hidden" value="0.00">
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 shadow-sm fw-bold fs-6 d-flex align-items-center justify-content-center gap-2" id="submitPurchaseBtn">
                                <span>Confirm Steel Intake & Save</span>
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
        let rowIndex = 1;

        function toggleLotMode(mode) {
            if (mode === 'new') {
                $('#newLotContainer').fadeIn(150);
                $('#existingLotContainer').hide();
                $('#new_lot_number').attr('required', true);
                $('#purchase_lot_id').removeAttr('required');

                const newVendorId = $('#new_vendor_id').val();
                $('#vendor_hidden').val(newVendorId);
            } else {
                $('#newLotContainer').hide();
                $('#existingLotContainer').fadeIn(150);
                $('#new_lot_number').removeAttr('required');
                $('#purchase_lot_id').attr('required', true);

                const selectedOption = $('#purchase_lot_id').find('option:selected');
                const vendorId = selectedOption.data('vendor-id') || '';
                const vendorName = selectedOption.data('vendor-name') || '';
                $('#vendor_hidden').val(vendorId);
                $('#vendor_display').val(vendorName || 'No Vendor Linked');
            }
        }

        $(document).ready(function () {
            $('.select2').select2({
                width: '100%'
            });

            $('#new_vendor_id').on('change select2:select', function () {
                if ($('#lot_type_new').is(':checked')) {
                    $('#vendor_hidden').val($(this).val());
                }
            });

            $('#purchase_lot_id').on('change select2:select', function () {
                if ($('#lot_type_existing').is(':checked')) {
                    const selectedOption = $(this).find('option:selected');
                    const vendorId = selectedOption.data('vendor-id') || '';
                    const vendorName = selectedOption.data('vendor-name') || '';

                    $('#vendor_hidden').val(vendorId);
                    $('#vendor_display').val(vendorName || 'No Vendor Linked');
                }
            });

            // Initial mode trigger
            if ($('#lot_type_existing').is(':checked')) {
                toggleLotMode('existing');
            } else {
                toggleLotMode('new');
            }
        });

        function addSteelRow() {
            const html = `
                <tr class="steel-row" data-index="${rowIndex}">
                    <td class="ps-3 text-center text-muted small fw-semibold row-number">${rowIndex + 1}</td>
                    <td>
                        <input oninput="calculateRow(this)" type="number" min="1" step="1" name="items[${rowIndex}][quantity]" class="form-control form-control-sm row-quantity text-center fw-bold" value="1" required>
                    </td>
                    <td>
                        <input type="text" name="items[${rowIndex}][thickness]" class="form-control form-control-sm row-thickness text-center" placeholder="e.g. 16mm">
                    </td>
                    <td>
                        <input type="text" name="items[${rowIndex}][size]" class="form-control form-control-sm row-size text-center" placeholder="e.g. 5x20">
                    </td>
                    <td>
                        <select name="items[${rowIndex}][size_type]" class="form-select form-select-sm row-size-type">
                            <option value="ft" selected>Feet (ft)</option>
                            <option value="mm">Millimeter (mm)</option>
                            <option value="inch">Inch (in)</option>
                            <option value="m">Meter (m)</option>
                            <option value="pcs">Pieces (pcs)</option>
                            <option value="ton">Ton</option>
                        </select>
                    </td>
                    <td>
                        <input oninput="calculateRow(this)" type="number" step="0.001" min="0.001" name="items[${rowIndex}][unit_weight]" class="form-control form-control-sm row-unit-weight text-end fw-bold text-primary" placeholder="0.000" required>
                        <input type="hidden" name="items[${rowIndex}][net_weight]" class="row-net-weight" value="0">
                    </td>
                    <td>
                        <input type="number" step="0.001" name="items[${rowIndex}][total_weight]" class="form-control form-control-sm row-total-weight bg-light text-end fw-bold text-dark" readonly placeholder="0.000">
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted">৳</span>
                            <input oninput="calculateRow(this)" type="number" step="0.01" min="0" name="items[${rowIndex}][unit_price]" class="form-control row-rate text-end fw-semibold" placeholder="0.00" required>
                        </div>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted">৳</span>
                            <input type="number" step="0.01" name="items[${rowIndex}][sub_price]" class="form-control row-sub-total bg-light text-end fw-bold text-dark" readonly placeholder="0.00">
                        </div>
                    </td>
                    <td class="text-center pe-3">
                        <button type="button" onclick="removeSteelRow(this)" class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1" title="Remove row">
                            <i class="fe fe-trash-2"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#steelRowsContainer').append(html);
            rowIndex++;
            updateRowNumbers();
        }

        function removeSteelRow(btn) {
            const rows = $('#steelRowsContainer tr.steel-row');
            if (rows.length <= 1) {
                alert('At least one item row is required.');
                return;
            }
            $(btn).closest('tr.steel-row').remove();
            updateRowNumbers();
            recalculateSummary();
        }

        function updateRowNumbers() {
            $('#steelRowsContainer tr.steel-row').each(function (index) {
                $(this).find('.row-number').text(index + 1);
            });
        }

        function calculateRow(inputEl) {
            const row = $(inputEl).closest('tr.steel-row');
            const qty = parseInt(row.find('.row-quantity').val()) || 1;
            const unitWeight = parseFloat(row.find('.row-unit-weight').val()) || 0;
            const totalWeight = qty * unitWeight;

            row.find('.row-net-weight').val(unitWeight);
            row.find('.row-total-weight').val(totalWeight > 0 ? totalWeight.toFixed(3) : '');

            const rate = parseFloat(row.find('.row-rate').val()) || 0;
            const subTotal = totalWeight * rate;
            row.find('.row-sub-total').val(subTotal > 0 ? subTotal.toFixed(2) : '');

            recalculateSummary();
        }

        function recalculateSummary() {
            let totalCoils = 0;
            let totalWeight = 0;
            let grandTotal = 0;

            $('#steelRowsContainer tr.steel-row').each(function () {
                const qty = parseInt($(this).find('.row-quantity').val()) || 0;
                const unitWeight = parseFloat($(this).find('.row-unit-weight').val()) || 0;
                const rowTotalWeight = qty * unitWeight;
                const sub = parseFloat($(this).find('.row-sub-total').val()) || 0;

                totalCoils += qty;
                totalWeight += rowTotalWeight;
                grandTotal += sub;
            });

            const formattedGrandTotal = grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            $('#displayTotalCoils').text(totalCoils);
            $('#displayTotalNetWeight').text(totalWeight.toFixed(3));
            $('#displayGrandTotal').text('৳ ' + formattedGrandTotal);
            $('#grand_total_hidden').val(grandTotal.toFixed(2));
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
            const grandTotal = parseFloat($('#grand_total_hidden').val()) || 0;
            $('#paymentInput').val(grandTotal.toFixed(2));
            recalculateSummary();
        }

        function handlePurchasePaymentMethodChange(method) {
            const bankContainer = document.getElementById('purchaseBankAccountContainer');
            const refContainer = document.getElementById('purchaseTransactionRefContainer');
            if (!bankContainer || !refContainer) return;

            if (method === 'cash') {
                bankContainer.style.display = 'none';
                refContainer.style.display = 'none';
            } else {
                bankContainer.style.display = 'block';
                refContainer.style.display = 'block';
            }
        }

        $('#createPurchaseForm').on('submit', function (e) {
            const grandTotal = parseFloat($('#grand_total_hidden').val()) || 0;
            const payment = parseFloat($('#paymentInput').val()) || 0;

            if ($('#lot_type_new').is(':checked')) {
                const newVendor = $('#new_vendor_id').val();
                if (!newVendor) {
                    e.preventDefault();
                    alert('Please select a Vendor / Supplier for the new lot.');
                    $('#new_vendor_id').select2('open');
                    return false;
                }
                $('#vendor_hidden').val(newVendor);
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
