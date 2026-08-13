@extends('frontend.layouts.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: 38px !important;
            border: 1px solid #dbe2ea !important;
            border-radius: 8px !important;
            display: flex;
            align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px !important;
            padding-left: 12px;
            color: #334155;
            font-size: 13px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
            right: 8px;
        }
        .table-custom th,
        .table-custom td {
            white-space: nowrap;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header mb-4">
            <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="card-title fw-bold text-dark mb-1">Purchase</h4>
                    <p class="text-muted small mb-0">Record steel procurement, coil tags, dimensions, weights, and yard intake</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('coils.index') }}" class="btn btn-outline-primary px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-1">
                        <i class="fe fe-disc"></i>
                        <span>Coils & Plates Registry</span>
                    </a>
                    <a href="{{ route('purchase.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                        <i class="fe fe-arrow-left"></i>
                        <span>Back to Purchases</span>
                    </a>
                </div>
            </div>
        </div>
        <!-- /Page Header -->

        <form action="{{ route('purchase.store') }}" method="POST" id="createPurchaseForm">
            @csrf

            <!-- Section 1: Lot & Yard Information -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3">
                       <i class="fe fe-anchor text-primary me-2"></i>Lot & Yard Intake Details
                    </h6>

                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6 col-12">
                            <label for="purchase_lot_id" class="form-label fw-semibold small text-secondary mb-1">
                                Purchase Lot <span class="text-danger">*</span>
                            </label>
                            <select id="purchase_lot_id" name="lot_id" class="form-select select2" required>
                                <option value="">Select Purchase Lot</option>
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
                                Vendor / Supplier <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="vendor_display" class="form-control bg-light" readonly
                                placeholder="Auto-fills on Lot selection" style="cursor: not-allowed;">
                            <input type="hidden" name="vendor_id" id="vendor_hidden" value="{{ old('vendor_id') }}">
                        </div>

                        <div class="col-lg-3 col-md-6 col-12">
                            <label for="warehouse_id" class="form-label fw-semibold small text-secondary mb-1">
                                Stockyard Location (Cutting) <span class="text-danger">*</span>
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
                                Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="purchase_date" id="purchase_date" class="form-control"
                                value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Steel & Coil Intake Line Items -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fe fe-disc text-primary me-2"></i>Steel Items & Coil Intake Grid
                        </h6>
                        <small class="text-muted">Specify coil tags, thickness, size, size type, weight/quantity, and rate</small>
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
                                    <th style="width: 40px;" class="ps-3 text-center">#</th>
                                    <th style="min-width: 170px;">Coil No</th>
                                    <th style="width: 130px;">Thickness</th>
                                    <th style="width: 140px;">Size</th>
                                    <th style="width: 120px;">Size Type</th>
                                    <th style="width: 140px;">Weight / Qty (kg) <span class="text-danger">*</span></th>
                                    <th style="width: 150px;">Rate (৳) <span class="text-danger">*</span></th>
                                    <th style="width: 160px;">Sub Total (৳)</th>
                                    <th style="width: 50px;" class="text-center pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody id="steelRowsContainer">
                                <tr class="steel-row" data-index="0">
                                    <td class="ps-3 text-center text-muted small fw-semibold row-number">1</td>
                                    <td>
                                        <input type="text" name="items[0][coil_number]" class="form-control form-control-sm row-coil-number font-monospace">
                                    </td>
                                    <td>
                                        <input type="text" name="items[0][thickness]" class="form-control form-control-sm row-thickness text-center">
                                    </td>
                                    <td>
                                        <input type="text" name="items[0][size]" class="form-control form-control-sm row-size text-center">
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
                                        <input oninput="calculateRow(this)" type="number" step="0.001" min="0.001" name="items[0][quantity]" class="form-control form-control-sm row-quantity text-end fw-bold text-primary" placeholder="0.000" required>
                                        <input type="hidden" name="items[0][net_weight]" class="row-net-weight" value="0">
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

            <!-- Section 3: Summary Breakdown & Payment Settlement -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3">
                        <i class="fe fe-dollar-sign text-primary me-2"></i>Valuation & Settlement
                    </h6>

                    <!-- Metrics Row -->
                    <div class="row g-3 mb-3">
                        <!-- Total Weight Metric -->
                        <div class="col-lg-3 col-md-6 col-12">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="text-muted small fw-medium d-block mb-1">Total Batch Weight</span>
                                <h4 class="mb-0 fw-bold text-primary"><span id="displayTotalNetWeight">0.000</span> <small class="fs-7 text-muted">Kg</small></h4>
                            </div>
                        </div>

                        <!-- Grand Total Metric -->
                        <div class="col-lg-3 col-md-6 col-12">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="text-muted small fw-medium d-block mb-1">Total Purchase Bill</span>
                                <h4 class="mb-0 fw-bold text-dark" id="displayGrandTotal">৳ 0.00</h4>
                                <input type="hidden" name="grand_total" id="grand_total_hidden" value="0.00">
                            </div>
                        </div>

                        <!-- Payment Input -->
                        <div class="col-lg-3 col-md-6 col-12">
                            <div class="p-3 bg-light rounded-3 border h-100 position-relative">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small text-muted fw-medium mb-0">Payment (৳) <span class="text-danger">*</span></label>
                                    <a href="javascript:void(0)" onclick="setFullPayment()" class="text-primary small fw-semibold text-decoration-none">
                                        <i class="fe fe-check-circle me-1"></i>Pay Full
                                    </a>
                                </div>
                                <input oninput="recalculateSummary()" type="number" step="0.01" min="0" name="payment" id="paymentInput" 
                                    class="form-control border-light-subtle fw-bold text-success bg-white @error('payment') is-invalid @enderror" 
                                    value="{{ old('payment', 0) }}" required>
                                
                                <div id="paymentErrorMsg" class="text-danger small mt-2 fw-semibold" style="display: none;">
                                    <i class="fe fe-alert-triangle me-1"></i> Payment cannot exceed total bill (<span id="maxBillFormatted">৳ 0.00</span>).
                                </div>
                                @error('payment')
                                    <div class="text-danger small mt-1 fw-semibold">
                                        <i class="fe fe-alert-circle me-1"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <!-- Outstanding Due -->
                        <div class="col-lg-3 col-md-6 col-12">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="text-muted small fw-medium d-block mb-1">Outstanding Due</span>
                                <h4 class="mb-0 fw-bold text-danger" id="displayDueAmount">৳ 0.00</h4>
                                <input type="hidden" name="due" id="due_hidden" value="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3 pt-3 border-top">
                        <a href="{{ route('purchase.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3">Cancel</a>
                        <button type="submit" class="btn btn-primary px-5 py-2 rounded-3 fw-semibold shadow-sm">
                          Save Purchase
                        </button>
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

        $(document).ready(function () {
            $('.select2').select2({
                width: '100%'
            });

            $('#purchase_lot_id').on('change select2:select', function () {
                const selectedOption = $(this).find('option:selected');
                const vendorId = selectedOption.data('vendor-id') || '';
                const vendorName = selectedOption.data('vendor-name') || '';

                $('#vendor_hidden').val(vendorId);
                $('#vendor_display').val(vendorName || 'No Vendor Linked');
            });

            // Trigger on load if pre-selected
            if ($('#purchase_lot_id').val()) {
                $('#purchase_lot_id').trigger('change');
            }
        });

        function addSteelRow() {
            const html = `
                <tr class="steel-row" data-index="${rowIndex}">
                    <td class="ps-3 text-center text-muted small fw-semibold row-number">${rowIndex + 1}</td>
                    <td>
                        <input type="text" name="items[${rowIndex}][coil_number]" class="form-control form-control-sm row-coil-number font-monospace" placeholder="e.g. COIL-0${rowIndex + 1}">
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
                        <input oninput="calculateRow(this)" type="number" step="0.001" min="0.001" name="items[${rowIndex}][quantity]" class="form-control form-control-sm row-quantity text-end fw-bold text-primary" placeholder="0.000" required>
                        <input type="hidden" name="items[${rowIndex}][net_weight]" class="row-net-weight" value="0">
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
            const qty = parseFloat(row.find('.row-quantity').val()) || 0;
            row.find('.row-net-weight').val(qty);

            const rate = parseFloat(row.find('.row-rate').val()) || 0;
            const subTotal = qty * rate;
            row.find('.row-sub-total').val(subTotal.toFixed(2));

            recalculateSummary();
        }

        function recalculateSummary() {
            let totalWeight = 0;
            let grandTotal = 0;

            $('#steelRowsContainer tr.steel-row').each(function () {
                const qty = parseFloat($(this).find('.row-quantity').val()) || 0;
                const sub = parseFloat($(this).find('.row-sub-total').val()) || 0;
                totalWeight += qty;
                grandTotal += sub;
            });

            const formattedGrandTotal = grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
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

        $('#createPurchaseForm').on('submit', function (e) {
            const grandTotal = parseFloat($('#grand_total_hidden').val()) || 0;
            const payment = parseFloat($('#paymentInput').val()) || 0;

            if (grandTotal <= 0) {
                e.preventDefault();
                alert('Please enter valid quantities and rates for your steel items.');
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
