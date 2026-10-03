@extends('frontend.layouts.app')


@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Add Sale Order</h4>
                <p class="text-muted small mb-0">Create a steel sales invoice, select mill procurement lots, track dispatch depot & operational costs</p>
            </div>
            <div>
                <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3 shadow-sm">
                    <i class="fe fe-arrow-left me-2"></i>Back to Sales
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <form action="{{ route('sales.store') }}" method="POST" target="_blank" onsubmit="return validateSaleFormSubmission(event)" id="createSaleForm">
        @csrf

        <!-- Section 1: Customer & Order Dispatch Information -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3"><i class="fe fe-user me-2 text-primary"></i>Customer & Dispatch Details</h6>

                <!-- Customer Type Toggle -->
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="form-label small text-secondary fw-semibold mb-2">Customer Type <span class="text-danger">*</span></label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="client_type" id="newClient" value="new" checked>
                                <label class="form-check-label fw-semibold text-dark" for="newClient">New Customer</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="client_type" id="existingClient" value="existing">
                                <label class="form-check-label fw-semibold text-dark" for="existingClient">Existing Customer</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Client Form -->
                <div id="newClientForm">
                    <div class="row g-3">
                        <div class="col-lg-4 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control border-light-subtle" id="newClientName" required autocomplete="off">
                        </div>
                        <div class="col-lg-4 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control border-light-subtle" id="newClientPhone" required autocomplete="off">
                        </div>
                        <div class="col-lg-4 col-md-12 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Address <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control border-light-subtle" id="newClientAddress" required autocomplete="off">
                        </div>
                    </div>
                </div>

                <!-- Existing Client Form -->
                <div id="existingClientForm" style="display: none;">
                    <div class="row g-3 align-items-stretch">
                        <div class="col-lg-6 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Select Existing Customer <span class="text-danger">*</span></label>
                            <select name="existing_client_id" class="form-select select2 border-light-subtle" id="clientSelect" onchange="handleCustomerChange(this)">
                                <option value="">Select Customer</option>
                                @foreach ($existingClients as $client)
                                    @php
                                        $openingDue = (float)($client->opening_balance ?? 0);
                                        $salesDue = (float)($client->sales_sum_due_payment ?? 0);
                                        $advanceCredit = (float)($client->advance_credit ?? 0);
                                        $netDue = (float)($client->net_due ?? ($openingDue + $salesDue));
                                    @endphp
                                    <option value="{{ $client->id }}" 
                                        data-name="{{ $client->name }}"
                                        data-phone="{{ $client->phone }}" 
                                        data-address="{{ $client->address }}" 
                                        data-opening-due="{{ $openingDue }}"
                                        data-sales-due="{{ $salesDue }}"
                                        data-advance-credit="{{ $advanceCredit }}"
                                        data-net-due="{{ $netDue }}"
                                        data-previous-due="{{ $netDue }}">
                                        {{ $client->name }} — {{ $client->phone }} {{ $advanceCredit > 0 ? '(Advance: ৳'.number_format($advanceCredit, 2).')' : ($netDue > 0 ? '(Due: ৳'.number_format($netDue, 2).')' : '') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Customer Profile & Due Widget (Opening Due + Sales Due + Advance Credit / Total) -->
                        <div class="col-lg-6 col-md-6 col-12">
                            <div id="customerBalanceCard" class="p-3 bg-light rounded-3 border h-100 d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div>
                                    <div class="fw-semibold text-dark mb-0" id="custNameText">No Customer Selected</div>
                                    <div class="small text-muted" id="custContactText" style="font-size: 11px;">Select customer to view previous balance &amp; advance credits</div>
                                </div>
                                <div class="d-flex align-items-center gap-2 text-end flex-wrap">
                                    <div class="bg-white px-2 py-1 rounded border" id="custOpeningDueWrapper">
                                        <span class="text-muted d-block" style="font-size: 9px; text-transform: uppercase;">Opening Due</span>
                                        <span id="custOpeningDueBadge" class="fw-bold text-warning fs-7">৳ 0.00</span>
                                    </div>
                                    <div class="bg-white px-2 py-1 rounded border" id="custSalesDueWrapper">
                                        <span class="text-muted d-block" style="font-size: 9px; text-transform: uppercase;">Sales Due</span>
                                        <span id="custSalesDueBadge" class="fw-bold text-info fs-7">৳ 0.00</span>
                                    </div>
                                    <div class="bg-white px-2 py-1 rounded border" id="custBalanceWrapper">
                                        <span class="text-muted d-block" style="font-size: 9px; text-transform: uppercase;">Total Prev Due</span>
                                        <span id="custBalanceBadge" class="fw-bold text-secondary fs-7">৳ 0.00</span>
                                    </div>
                                    <div class="bg-success-subtle px-2 py-1 rounded border border-success-subtle" id="custAdvanceWrapper" style="display: none;">
                                        <span class="text-success d-block" style="font-size: 9px; text-transform: uppercase; font-weight: 700;">Advance Credit</span>
                                        <span id="custAdvanceCreditBadge" class="fw-bold text-success fs-7">৳ 0.00</span>
                                    </div>
                                    <button type="button" id="btnApplyAdvance" class="btn btn-sm btn-success px-2.5 py-1.5 rounded-2 fw-bold shadow-sm" style="display: none; font-size: 11px;" onclick="applyCustomerAdvanceCredit()">
                                        ⚡ Apply Advance
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4 text-light">

                <!-- Order & Dispatch Meta Row -->
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6 col-12">
                        @php
                            $targetWhId = request('warehouse_id', old('warehouse_id'));
                            $isLocked = request('locked') || request('is_shop') || request('from') === 'shop';
                            $selectedWh = $targetWhId ? $warehouses->firstWhere('id', $targetWhId) : null;
                        @endphp
                        <label class="form-label small text-secondary fw-semibold mb-1">
                            Warehouse / Dispatch Yard <span class="text-danger">*</span>
                            @if($isLocked && $selectedWh)
                                <span class="badge bg-primary-subtle text-primary border ms-1"><i class="fe fe-shopping-cart me-1"></i>Shop Locked</span>
                            @endif
                        </label>
                        @if($isLocked && $selectedWh)
                            <div class="input-group">
                                <span class="input-group-text bg-light text-primary border-primary-subtle"><i class="fe fe-home"></i></span>
                                <input type="text" class="form-control bg-light fw-bold text-dark border-primary-subtle" value="{{ $selectedWh->name }} {{ $selectedWh->code ? '('.$selectedWh->code.')' : '' }}" readonly>
                                <input type="hidden" name="warehouse_id" id="warehouse_id" value="{{ $selectedWh->id }}">
                            </div>
                        @else
                            <select name="warehouse_id" id="warehouse_id" class="form-select select2" required>
                                <option value="">Select Warehouse / Yard</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}" {{ (string)$targetWhId === (string)$wh->id || ($loop->first && !$targetWhId) ? 'selected' : '' }}>
                                        {{ $wh->name }} {{ $wh->code ? '('.$wh->code.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Delivery Status <span class="text-danger">*</span></label>
                        <select name="delivery_status" id="delivery_status" class="form-select border-light-subtle" required>
                            <option value="delivered" selected>Delivered</option>
                        </select>
                    </div>

                    <div class="col-lg-6 col-md-12 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Dispatch Note / Truck & Transport Details</label>
                        <input type="text" name="note" id="note" class="form-control border-light-subtle">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Items & Lot Selection Builder -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="mb-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="fe fe-shopping-cart me-2 text-primary"></i>Steel Items & Stock Selection</h6>
                    <small class="text-muted">1. Select Stock / Lot Source &rarr; 2. System automatically combines all in-stock coils with total weight and cost rate &rarr; 3. Enter selling rate and selling quantity</small>
                </div>

                <!-- Product Add Builder Card -->
                <div class="p-3 bg-light rounded-3 mb-4 border" id="form-group-item1">
                    <div class="row g-3 align-items-end">
                        <!-- 1. Stock / Lot Source Select Dropdown -->
                        <div class="col-lg-4 col-md-6 col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small text-secondary fw-semibold mb-0">
                                    1. Select Stock / Lot Source <span class="text-danger">*</span>
                                </label>
                                <span id="lotAvgRateBadge" class="badge bg-primary-subtle text-primary border" style="display:none; font-size: 11px;"></span>
                            </div>
                            <select id="builder_lot_id" class="form-select select2 border-light-subtle" onchange="handleLotSelection(this.value)">
                                <option value="">Select Warehouse first</option>
                            </select>
                        </div>

                        <!-- 2. Available Total Weight -->
                        <div class="col-lg-4 col-md-3 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Total Available Stock</label>
                            <input type="text" id="stock1" class="form-control border-light-subtle bg-white fw-bold text-primary" readonly placeholder="0.00 kg">
                        </div>

                        <!-- 3. Cost Rate -->
                        <div class="col-lg-4 col-md-3 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Avg Cost Rate (৳)</label>
                            <input type="number" id="purchase_price1" class="form-control border-light-subtle bg-white" readonly placeholder="0.00">
                        </div>

                        <!-- 4. Custom Size / Specs (Admin Only) -->
                        <div class="col-lg-3 col-md-6 col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small text-secondary fw-semibold mb-0">Custom Size / Specs</label>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 9px;"><i class="fe fe-lock me-1"></i>Admin Only</span>
                            </div>
                            <input type="text" id="custom_size1" class="form-control border-light-subtle" placeholder="e.g. 4x8 ft cut (Internal note)">
                        </div>

                        <!-- 5. Selling Rate -->
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Selling Rate (৳) <span class="text-danger">*</span></label>
                            <input oninput="updatePreviewTotal()" onchange="updatePreviewTotal()" type="number" id="unit_price1" class="form-control border-light-subtle" step="0.01" min="0" placeholder="0.00">
                        </div>

                        <!-- 6. Selling Quantity -->
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Selling Qty / Wt (kg) <span class="text-danger">*</span></label>
                            <input oninput="updatePreviewTotal()" onchange="updatePreviewTotal()" type="number" id="qty1" class="form-control border-light-subtle text-dark fw-bold" step="0.01" min="0.01" placeholder="0.00">
                        </div>

                        <!-- 7. Line Total Preview -->
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Line Total (৳)</label>
                            <input type="text" id="total1" class="form-control border-light-subtle bg-white fw-bold text-success" readonly value="0.00">
                        </div>

                        <!-- 8. Add Item Button -->
                        <div class="col-12 text-end pt-1">
                            <button type="button" onclick="addItem()" class="btn btn-success px-4 rounded-3 d-inline-flex align-items-center justify-content-center gap-2 py-2 shadow-sm">
                                <i class="fe fe-plus"></i>
                                <span>Add Steel Item</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Added Items List Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="cartItemsTable">
                        <thead class="bg-light text-secondary fs-7 text-uppercase">
                            <tr>
                                <th style="width: 32%;">Product & Specifications</th>
                                <th style="width: 18%;">Lot Source</th>
                                <th style="width: 15%;">Custom Size (Admin)</th>
                                <th style="width: 12%;">Unit Price</th>
                                <th style="width: 10%;">Quantity</th>
                                <th style="width: 8%;">Total Price</th>
                                <th style="width: 5%;" class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="item_container">
                            <!-- Dynamic Cart Rows Added Here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Section 3: Summary Breakdown & Financials -->
        <div id="summerySection" class="card border-0 shadow-sm rounded-3 mb-4 d-none">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3"><i class="fe fe-dollar-sign me-2 text-primary"></i>Operational Charges & Financial Breakdown</h6>

                <!-- Charges & Adjustments Row -->
                <div class="row g-3 align-items-end mb-4">
                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Sub Total (৳)</label>
                        <input onchange="calculateTotal()" type="number" id="subTotal" name="subTotal" class="form-control border-light-subtle bg-light" readonly>
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Discount Amount (৳)</label>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="discount" name="discount" class="form-control border-light-subtle" value="0" min="0" step="0.01">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">VAT (%)</label>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="vat" name="vat" class="form-control border-light-subtle" value="0" min="0" step="0.01">
                    </div>

                    <!-- <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Tax (%)</label>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="tax" name="tax" class="form-control border-light-subtle" value="0" min="0" step="0.01">
                    </div> -->

                    <div class="col-lg-3 col-md-4 col-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small text-secondary fw-semibold mb-0">Delivery / Transport (৳)</label>
                            <div class="d-inline-flex gap-2">
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input" type="radio" name="transport_payer" id="transport_payer_me" value="me" {{ old('transport_payer', 'me') === 'me' ? 'checked' : '' }} onchange="calculateTotal()">
                                    <label class="form-check-label small fw-semibold text-muted" for="transport_payer_me" style="font-size: 0.75rem;">Paid by Me</label>
                                </div>
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input" type="radio" name="transport_payer" id="transport_payer_vendor" value="vendor" {{ old('transport_payer') === 'vendor' ? 'checked' : '' }} onchange="calculateTotal()">
                                    <label class="form-check-label small fw-semibold text-muted" for="transport_payer_vendor" style="font-size: 0.75rem;">Paid by Vendor</label>
                                </div>
                            </div>
                        </div>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="delivery_charge" name="delivery_charge" class="form-control border-light-subtle" value="{{ old('delivery_charge', 0) }}" min="0" step="0.01">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Cutting & Labour Load-Unload (৳)</label>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="labour_cost" name="labour_cost" class="form-control border-light-subtle" value="0" min="0" step="0.01">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Scale & Labour Charge (৳)</label>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="weight_scale_cost" name="weight_scale_cost" class="form-control border-light-subtle" value="0" min="0" step="0.01">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Other Charges (৳)</label>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="other_charges" name="other_charges" class="form-control border-light-subtle" value="0" min="0" step="0.01">
                    </div>

                    <input type="hidden" id="grandTotal" name="grandTotal" value="0.00">
                    <input type="hidden" id="duePayment" name="duePayment" value="0.00">
                </div>

                <!-- Minimal Financial Metric Cards Row -->
                <div class="row g-3 mb-3">
                    <!-- Grand Total -->
                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <span class="text-muted small fw-medium d-block mb-1">Invoice Grand Total</span>
                            <h4 class="mb-0 fw-bold text-dark" id="grandTotalDisplay">৳ 0.00</h4>
                        </div>
                    </div>

                    <!-- Payment Received Input -->
                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <label class="form-label small text-muted fw-medium mb-1">Current Payment (৳) <span class="text-danger">*</span></label>
                            <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" name="advanced_payment" id="advancedPayment" 
                                class="form-control border-light-subtle fw-bold text-dark bg-white" value="0" min="0" step="0.01">
                        </div>
                    </div>

                    <!-- Current Invoice Due -->
                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <span class="text-muted small fw-medium d-block mb-1">Current Invoice Due</span>
                            <h4 class="mb-0 fw-bold text-danger" id="currentDueDisplay">৳ 0.00</h4>
                        </div>
                    </div>

                    <!-- Customer Previous Due -->
                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <span class="text-muted small fw-medium d-block mb-1">Customer Previous Due</span>
                            <h4 class="mb-0 fw-bold text-secondary" id="previousDueDisplay">৳ 0.00</h4>
                        </div>
                    </div>
                </div>

                <!-- Payment Method & Banking Details Section -->
                <div class="card border border-light-subtle rounded-3 bg-light-subtle p-3 mb-3" id="paymentMethodSection">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-4 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">
                                <i class="fe fe-credit-card me-1 text-primary"></i> Payment Method <span class="text-danger">*</span>
                            </label>
                            <select name="payment_method" id="paymentMethodSelect" class="form-select border-light-subtle" onchange="handlePaymentMethodChange(this.value)">
                                <option value="cash" selected>Cash in Hand</option>
                                <option value="advance_credit">💰 Advance Balance / Deposit Adjustment</option>
                                <option value="bank">Bank Transfer / Deposit</option>
                                <option value="mobile_banking">Mobile Banking (bKash/Nagad)</option>
                            </select>
                        </div>

                        <!-- Bank Account Selector (Shown for Bank, Mobile Banking) -->
                        <div class="col-lg-4 col-md-6 col-12" id="bankAccountContainer" style="display: none;">
                            <label class="form-label small text-secondary fw-semibold mb-1">
                                <i class="fe fe-layers me-1 text-info"></i> Deposit Bank Account <span class="text-danger">*</span>
                            </label>
                            <select name="bank_detail_id" id="bankDetailSelect" class="form-select border-light-subtle">
                                <option value="">Select Bank Account</option>
                                @foreach($bankAccounts ?? [] as $bank)
                                    <option value="{{ $bank->id }}" {{ $bank->is_default ? 'selected' : '' }}>
                                        {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Transaction Reference / Notes -->
                        <div class="col-lg-4 col-md-6 col-12" id="transactionRefContainer" style="display: none;">
                            <label class="form-label small text-secondary fw-semibold mb-1">
                                <i class="fe fe-file-text me-1 text-secondary"></i> Transaction Ref / TrxID
                            </label>
                            <input type="text" name="transaction_ref" id="transactionRefInput" class="form-control border-light-subtle bg-white">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 pt-2 border-top">
                    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3">Cancel</a>
                    <button type="submit" class="btn btn-primary px-5 py-2 rounded-3 fw-semibold shadow-sm">
                        Save & Generate Invoice
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
var itemNumber = 2;
window.selectedCustomerPreviousDue = 0;
window.currentSelectedLot = null;

$(document).ready(function () {
    $('.select2').select2({
        width: '100%'
    });

    $('#clientSelect').on('change select2:select', function () {
        handleCustomerChange(this);
    });

    $('#warehouse_id').on('change select2:select', function () {
        populateWarehouseLotSources($(this).val());
    });

    const newClientRadio = document.getElementById('newClient');
    const existingClientRadio = document.getElementById('existingClient');
    const newClientForm = document.getElementById('newClientForm');
    const existingClientForm = document.getElementById('existingClientForm');
    const newClientInputs = document.querySelectorAll('#newClientForm input');

    function toggleClientForms() {
        if (newClientRadio.checked) {
            newClientForm.style.display = 'block';
            existingClientForm.style.display = 'none';
            newClientInputs.forEach(input => input.required = true);
            document.getElementById('clientSelect').required = false;
            window.selectedCustomerPreviousDue = 0;
            updateCustomerBalanceCard(0, null, null);
        } else {
            newClientForm.style.display = 'none';
            existingClientForm.style.display = 'block';
            newClientInputs.forEach(input => input.required = false);
            document.getElementById('clientSelect').required = true;
            handleCustomerChange(document.getElementById('clientSelect'));
        }
        calculateTotal();
    }

    if (newClientRadio && existingClientRadio) {
        newClientRadio.addEventListener('change', toggleClientForms);
        existingClientRadio.addEventListener('change', toggleClientForms);
        toggleClientForms();
    }

    // Populate initial stock/lot source based on default selected warehouse
    populateWarehouseLotSources($('#warehouse_id').val());
});

function escapeHtml(text) {
    if (!text) return '';
    return $('<div>').text(text).html();
}

function handleCustomerChange(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    if (selectedOption && selectedOption.value) {
        const openingDue = parseFloat(selectedOption.dataset.openingDue) || 0;
        const salesDue = parseFloat(selectedOption.dataset.salesDue) || 0;
        const advanceCredit = parseFloat(selectedOption.dataset.advanceCredit) || 0;
        const netDue = parseFloat(selectedOption.dataset.netDue) || 0;
        const totalDue = parseFloat(selectedOption.dataset.previousDue) || netDue;
        const name = selectedOption.dataset.name || selectedOption.text.split('—')[0].trim();
        const phone = selectedOption.dataset.phone || 'N/A';
        const address = selectedOption.dataset.address || 'N/A';
        window.selectedCustomerPreviousDue = totalDue;
        window.selectedCustomerOpeningDue = openingDue;
        window.selectedCustomerSalesDue = salesDue;
        window.selectedCustomerAdvanceCredit = advanceCredit;
        updateCustomerBalanceCard(totalDue, name, `Phone: ${phone} | Addr: ${address}`, openingDue, salesDue, advanceCredit);
    } else {
        window.selectedCustomerPreviousDue = 0;
        window.selectedCustomerOpeningDue = 0;
        window.selectedCustomerSalesDue = 0;
        window.selectedCustomerAdvanceCredit = 0;
        updateCustomerBalanceCard(0, null, null, 0, 0, 0);
    }
    calculateTotal();
}

function updateCustomerBalanceCard(totalDue, name, details, openingDue = 0, salesDue = 0, advanceCredit = 0) {
    const nameText = document.getElementById('custNameText');
    const contactText = document.getElementById('custContactText');
    const totalBadge = document.getElementById('custBalanceBadge');
    const totalWrapper = document.getElementById('custBalanceWrapper');
    const openingBadge = document.getElementById('custOpeningDueBadge');
    const openingWrapper = document.getElementById('custOpeningDueWrapper');
    const salesBadge = document.getElementById('custSalesDueBadge');
    const salesWrapper = document.getElementById('custSalesDueWrapper');
    const advanceWrapper = document.getElementById('custAdvanceWrapper');
    const advanceBadge = document.getElementById('custAdvanceCreditBadge');
    const btnApplyAdv = document.getElementById('btnApplyAdvance');

    if (!nameText || !totalBadge) return;

    if (!name) {
        nameText.innerText = 'No Customer Selected';
        nameText.className = 'fw-semibold text-dark mb-0';
        contactText.innerText = 'Select customer to view previous balance & advance credits';
        totalBadge.innerText = '৳ 0.00';
        totalBadge.className = 'fw-bold text-secondary fs-7';
        if (openingBadge) openingBadge.innerText = '৳ 0.00';
        if (salesBadge) salesBadge.innerText = '৳ 0.00';
        if (advanceWrapper) advanceWrapper.style.display = 'none';
        if (btnApplyAdv) btnApplyAdv.style.display = 'none';
        if (openingWrapper) openingWrapper.style.display = 'block';
        if (salesWrapper) salesWrapper.style.display = 'block';
        if (totalWrapper) totalWrapper.style.display = 'block';
        return;
    }

    nameText.innerText = name;
    contactText.innerText = details;

    if (openingBadge) {
        openingBadge.innerText = '৳ ' + openingDue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    if (salesBadge) {
        salesBadge.innerText = '৳ ' + salesDue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    if (advanceCredit > 0) {
        if (advanceWrapper) advanceWrapper.style.display = 'block';
        if (advanceBadge) advanceBadge.innerText = '৳ ' + advanceCredit.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (btnApplyAdv) btnApplyAdv.style.display = 'inline-block';
        if (totalBadge) {
            totalBadge.className = 'fw-bold text-success fs-7';
            totalBadge.innerText = '৳ 0.00 (Has Advance)';
        }
    } else {
        if (advanceWrapper) advanceWrapper.style.display = 'none';
        if (btnApplyAdv) btnApplyAdv.style.display = 'none';
        if (totalDue > 0) {
            totalBadge.className = 'fw-bold text-danger fs-7';
            totalBadge.innerText = '৳ ' + totalDue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        } else {
            totalBadge.className = 'fw-bold text-success fs-7';
            totalBadge.innerText = '৳ 0.00 (Clear)';
        }
    }
}

function applyCustomerAdvanceCredit() {
    const adv = parseFloat(window.selectedCustomerAdvanceCredit || 0);
    if (adv <= 0) return;
    const grandTotal = parseFloat(document.getElementById('grandTotal')?.value || 0);
    const applyAmt = grandTotal > 0 ? Math.min(grandTotal, adv) : adv;
    
    const advInput = document.getElementById('advancedPayment');
    if (advInput) {
        advInput.value = applyAmt.toFixed(2);
    }
    const methodSelect = document.getElementById('paymentMethodSelect');
    if (methodSelect) {
        methodSelect.value = 'advance_credit';
        handlePaymentMethodChange('advance_credit');
    }
    calculateTotal();
}

const allAvailableCoils = @json($coils);
const allAvailableLots = @json($lots->keyBy('id'));
const allWarehouses = @json($warehouses->keyBy('id'));
let currentSelectedLot = null;
let currentSelectedCoil = null;

function populateWarehouseLotSources(selectedWhId) {
    const lotSelect = $('#builder_lot_id');
    const currentLotVal = lotSelect.val();
    lotSelect.empty();

    const whCoils = selectedWhId 
        ? allAvailableCoils.filter(c => String(c.warehouse_id) === String(selectedWhId) && parseFloat(c.remaining_weight) > 0)
        : allAvailableCoils.filter(c => parseFloat(c.remaining_weight) > 0);

    const whName = selectedWhId && allWarehouses[selectedWhId] ? allWarehouses[selectedWhId].name : 'All Warehouses';

    if (!selectedWhId) {
        lotSelect.append('<option value="">Please select a Warehouse / Dispatch Yard first</option>');
        lotSelect.prop('disabled', true);
        if ($.fn.select2 && lotSelect.hasClass('select2-hidden-accessible')) {
            lotSelect.select2('destroy').select2({ width: '100%' });
        }
        handleLotSelection('');
        return;
    }

    lotSelect.prop('disabled', false);
    lotSelect.append('<option value="">Select Stock / Lot Source</option>');

    // Direct / Opening Stock optgroup
    const openingCoils = whCoils.filter(c => (c.purchase_id === null || !c.lot_id));
    const openingCount = openingCoils.length;
    const openingWeight = openingCoils.reduce((sum, c) => sum + (parseFloat(c.remaining_weight) || 0), 0);

    const totalCount = whCoils.length;
    const totalWeight = whCoils.reduce((sum, c) => sum + (parseFloat(c.remaining_weight) || 0), 0);

    let directOptgroup = $('<optgroup label="Direct / Opening Yard Stock"></optgroup>');
    if (openingCount > 0) {
        directOptgroup.append(`
            <option value="opening_stock" data-vendor="Direct Yard Stock" data-lot-number="Opening Stock">
                📦 Opening Stock (${openingCount} coils, ${openingWeight.toLocaleString()} kg)
            </option>
        `);
    }
    if (totalCount > 0) {
        directOptgroup.append(`
            <option value="all_stock" data-vendor="All Inventory" data-lot-number="All Stock">
                🌐 All In-Stock in ${escapeHtml(whName)} (${totalCount} coils, ${totalWeight.toLocaleString()} kg)
            </option>
        `);
    }
    if (openingCount > 0 || totalCount > 0) {
        lotSelect.append(directOptgroup);
    }

    // Purchase Mill Lots optgroup
    const lotGroups = {};
    whCoils.forEach(c => {
        if (c.lot_id) {
            if (!lotGroups[c.lot_id]) {
                lotGroups[c.lot_id] = {
                    coilsCount: 0,
                    weight: 0,
                    lot: c.lot || allAvailableLots[c.lot_id] || { id: c.lot_id, lot_number: 'Lot #' + c.lot_id }
                };
            }
            lotGroups[c.lot_id].coilsCount++;
            lotGroups[c.lot_id].weight += (parseFloat(c.remaining_weight) || 0);
        }
    });

    const lotIds = Object.keys(lotGroups);
    if (lotIds.length > 0) {
        let millOptgroup = $('<optgroup label="Purchase Mill Lots in this Yard"></optgroup>');
        lotIds.forEach(lId => {
            const g = lotGroups[lId];
            const lotObj = g.lot;
            const vendorName = (lotObj.vendor && lotObj.vendor.name) ? lotObj.vendor.name : (lotObj.vendor_name || '');
            const vendorLabel = vendorName ? ` (${vendorName})` : '';
            const text = `${lotObj.lot_number}${vendorLabel} — ${g.coilsCount} ${g.coilsCount === 1 ? 'coil' : 'coils'} (${g.weight.toLocaleString()} kg)`;
            millOptgroup.append(`
                <option value="${lId}" data-vendor="${escapeHtml(vendorName || 'Mill Lot')}" data-lot-number="${escapeHtml(lotObj.lot_number)}">
                    ${escapeHtml(text)}
                </option>
            `);
        });
        lotSelect.append(millOptgroup);
    }

    if (totalCount === 0) {
        lotSelect.append('<option value="" disabled>No in-stock steel coils found in this warehouse</option>');
    }

    // Restore previously selected lot if it exists in this warehouse
    if (currentLotVal && lotSelect.find(`option[value="${currentLotVal}"]`).length) {
        lotSelect.val(currentLotVal);
    } else {
        lotSelect.val('');
    }

    if ($.fn.select2) {
        if (lotSelect.hasClass('select2-hidden-accessible')) {
            lotSelect.select2('destroy');
        }
        lotSelect.select2({ width: '100%' });
    }

    handleLotSelection(lotSelect.val());
}

function handleLotSelection(lotId) {
    currentSelectedLot = null;
    resetLotFields();

    const lotAvgBadge = document.getElementById('lotAvgRateBadge');
    const selectedWhId = $('#warehouse_id').val();

    if (!lotId || !selectedWhId) {
        if (lotAvgBadge) {
            lotAvgBadge.style.display = 'none';
            lotAvgBadge.innerHTML = '';
        }
        return;
    }

    // Filter in-stock coils in this warehouse
    const whCoils = allAvailableCoils.filter(c => String(c.warehouse_id) === String(selectedWhId) && parseFloat(c.remaining_weight) > 0);

    let filtered = [];
    let lotNumberDisplay = '';
    let vendorDisplay = '';

    if (lotId === 'opening_stock') {
        lotNumberDisplay = 'Opening Stock';
        vendorDisplay = 'Direct Yard Stock';
        filtered = whCoils.filter(c => (c.purchase_id === null || !c.lot_id));
    } else if (lotId === 'all_stock') {
        lotNumberDisplay = 'All Stock';
        vendorDisplay = 'All Inventory';
        filtered = whCoils;
    } else {
        const lotOption = $(`#builder_lot_id option[value="${lotId}"]`);
        lotNumberDisplay = lotOption.data('lot-number') || lotOption.text().trim();
        vendorDisplay = lotOption.data('vendor') || '';
        filtered = whCoils.filter(c => String(c.lot_id) === String(lotId));
    }

    if (filtered.length === 0) {
        if (lotAvgBadge) {
            lotAvgBadge.style.display = 'none';
            lotAvgBadge.innerHTML = '';
        }
        return;
    }

    // Calculate Lot Weighted Average Cost Price and total combined weight
    const totalLotWeight = filtered.reduce((sum, c) => sum + (parseFloat(c.remaining_weight) || 0), 0);
    const totalLotCost = filtered.reduce((sum, c) => sum + ((parseFloat(c.remaining_weight) || 0) * (parseFloat(c.rate_per_ton) || 0)), 0);
    const avgLotRate = totalLotWeight > 0 ? (totalLotCost / totalLotWeight) : 0;

    // Determine representative thickness and sizes
    const thicknessList = [...new Set(filtered.map(c => c.thickness).filter(Boolean))];
    const sizeList = [...new Set(filtered.map(c => c.width || c.size).filter(Boolean))];
    const sizeTypeList = [...new Set(filtered.map(c => (c.length && c.length !== 'N/A') ? c.length : (c.size_type || '')).filter(Boolean))];

    currentSelectedLot = {
        id: (lotId === 'opening_stock' || lotId === 'all_stock') ? '' : lotId,
        raw_source: lotId,
        lot_number: lotNumberDisplay,
        vendor: vendorDisplay,
        availableWeight: totalLotWeight,
        avgRate: avgLotRate,
        coilsCount: filtered.length,
        thickness: thicknessList.join(', '),
        size: sizeList.join(', '),
        size_type: sizeTypeList[0] || 'ft'
    };

    // Display Lot Combined Avg Badge
    if (lotAvgBadge) {
        if (avgLotRate > 0) {
            lotAvgBadge.style.display = 'inline-block';
            lotAvgBadge.innerHTML = `Avg Cost: ৳${avgLotRate.toFixed(2)}/kg`;
            lotAvgBadge.title = `Combined Weighted Avg Cost of ${filtered.length} in-stock coils in this Lot (Total Stock: ${totalLotWeight.toLocaleString()} kg)`;
        } else {
            lotAvgBadge.style.display = 'none';
        }
    }

    document.getElementById('stock1').value = totalLotWeight.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' kg';
    document.getElementById('purchase_price1').value = avgLotRate.toFixed(2);
    document.getElementById('unit_price1').value = avgLotRate > 0 ? avgLotRate.toFixed(2) : '';
    document.getElementById('qty1').value = '';
    document.getElementById('qty1').setAttribute('max', totalLotWeight);
    document.getElementById('qty1').focus();

    updatePreviewTotal();
}

function resetLotFields() {
    currentSelectedLot = null;
    document.getElementById('stock1').value = '';
    document.getElementById('purchase_price1').value = '';
    document.getElementById('unit_price1').value = '';
    document.getElementById('qty1').value = '';
    document.getElementById('total1').value = '0.00';
    const customSizeEl = document.getElementById('custom_size1');
    if (customSizeEl) customSizeEl.value = '';
}

function updatePreviewTotal() {
    const qty = parseFloat(document.getElementById('qty1').value) || 0;
    const price = parseFloat(document.getElementById('unit_price1').value) || 0;
    const total = qty * price;
    document.getElementById('total1').value = '৳ ' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function addItem() {
    if (!currentSelectedLot) {
        alert('Please select a Stock / Lot Source first.');
        $('#builder_lot_id').focus();
        return;
    }

    const qtyInput = document.getElementById('qty1');
    const qty = parseFloat(qtyInput.value) || 0;
    if (qty <= 0) {
        alert('Please enter your desired selling quantity (kg).');
        qtyInput.focus();
        return;
    }

    const availableStock = currentSelectedLot.availableWeight;
    if (qty > availableStock) {
        alert(`Cannot sell more than available lot stock!\n\nRequested: ${qty.toLocaleString()} kg\nAvailable Stock: ${availableStock.toLocaleString()} kg`);
        qtyInput.value = availableStock;
        qtyInput.focus();
        updatePreviewTotal();
        return;
    }

    // Check if this lot is already added in another row in the cart
    const lotRawSource = currentSelectedLot.raw_source;
    let alreadyAddedQty = 0;
    document.querySelectorAll(`#item_container tr[data-lot-source="${lotRawSource}"]`).forEach(row => {
        alreadyAddedQty += parseFloat(row.querySelector('.qty')?.value) || 0;
    });

    if ((alreadyAddedQty + qty) > (availableStock + 0.0001)) {
        const remainingAllowed = Math.max(0, availableStock - alreadyAddedQty);
        alert(`Cannot exceed available lot stock!\n\nLot: ${currentSelectedLot.lot_number}\nTotal Available: ${availableStock.toLocaleString()} kg\nAlready in Cart: ${alreadyAddedQty.toLocaleString()} kg\nRemaining Allowed: ${remainingAllowed.toLocaleString()} kg`);
        qtyInput.value = remainingAllowed > 0 ? remainingAllowed : '';
        qtyInput.focus();
        updatePreviewTotal();
        return;
    }

    const unitPriceInput = document.getElementById('unit_price1');
    const unitPrice = parseFloat(unitPriceInput.value) || 0;
    if (unitPrice < 0) {
        alert('Please enter a valid selling rate.');
        unitPriceInput.focus();
        return;
    }

    const customSize = (document.getElementById('custom_size1')?.value || '').trim();
    const lot = currentSelectedLot;
    const lotId = lot.id;
    const thickness = lot.thickness || '';
    const size = lot.size || '';
    const sizeType = lot.size_type || 'ft';
    const rowTotal = (qty * unitPrice).toFixed(2);

    let lotBadge = '';
    if (!lotId || lot.lot_number === 'Opening Stock') {
        lotBadge = `<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 fs-8"><i class="fe fe-archive me-1"></i>Opening Stock</span>`;
    } else {
        lotBadge = `<span class="badge bg-light text-dark border px-2 py-1 fs-8"><i class="fe fe-package text-primary me-1"></i>${escapeHtml(lot.lot_number)}</span>`;
    }

    let specBadges = ``;
    if (lot.coilsCount > 1) {
        specBadges += `<span class="badge bg-light text-dark border px-2 py-1 fs-8 me-1"><i class="fe fe-layers me-1 text-primary"></i>${lot.coilsCount} Coils Consolidated</span>`;
    }
    if (thickness) {
        specBadges += `<span class="badge bg-light text-dark border px-2 py-1 fs-8 me-1">Thickness: ${escapeHtml(thickness)}</span>`;
    }
    if (size) {
        specBadges += `<span class="badge bg-light text-secondary border px-2 py-1 fs-8 me-1">Size: ${escapeHtml(size)} ${escapeHtml(sizeType)}</span>`;
    }

    const html = `
        <tr data-lot-source="${escapeHtml(lotRawSource)}" class="group-item" data-itemnumber="${itemNumber}" id="form-group-item${itemNumber}">
            <td>
                <input type="hidden" name="coil_id[]" value="">
                <input type="hidden" name="lot_id[]" value="${escapeHtml(lotId)}">
                <input type="hidden" name="thickness[]" value="${escapeHtml(thickness)}">
                <input type="hidden" name="size[]" value="${escapeHtml(size)}">
                <input type="hidden" name="size_type[]" value="${escapeHtml(sizeType)}">
                <span class="fw-bold text-dark d-block">${escapeHtml(lot.lot_number)} ${lot.vendor ? '(' + escapeHtml(lot.vendor) + ')' : ''}</span>
                <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                    ${specBadges}
                </div>
            </td>
            <td>
                ${lotBadge}
            </td>
            <td>
                <input type="text" name="custom_size[]" class="form-control form-control-sm border-light-subtle" value="${escapeHtml(customSize)}" placeholder="Admin custom size...">
            </td>
            <td>
                <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" step="0.01" name="unit_price[]" id="unit_price${itemNumber}" class="form-control border-light-subtle unit-price" value="${unitPrice.toFixed(2)}">
            </td>
            <td>
                <input oninput="validateLotRowQty(this); calculateTotal()" onchange="validateLotRowQty(this); calculateTotal()" type="number" step="0.01" name="qty[]" id="qty${itemNumber}" class="form-control border-light-subtle qty fw-bold" min="0.01" max="${availableStock}" data-available="${availableStock}" data-lot-source="${escapeHtml(lotRawSource)}" data-lot-number="${escapeHtml(lot.lot_number)}" value="${qty}">
            </td>
            <td>
                <input type="number" step="0.01" name="total" id="total${itemNumber}" class="form-control border-light-subtle bg-light total fw-bold text-dark" readonly value="${rowTotal}">
            </td>
            <td class="text-end">
                <button onclick="removeItem(${itemNumber})" type="button" class="btn btn-outline-danger btn-sm px-3 rounded-2" title="Remove Item">
                    <i class="fa fa-times"></i>
                </button>
            </td>
        </tr>
    `;

    $('#item_container').append(html);
    itemNumber++;

    // Reset builder inputs
    $('#builder_lot_id').val('').trigger('change');
    resetLotFields();

    toggleSummarySection();
    calculateTotal();
}

function removeItem(item) {
    document.getElementById('form-group-item' + item)?.remove();
    toggleSummarySection();
    calculateTotal();
}

function toggleSummarySection() {
    const hasItems = document.querySelectorAll('#item_container tr').length > 0;
    const summerySection = document.getElementById('summerySection');
    if (summerySection) {
        if (hasItems) {
            summerySection.classList.remove('d-none');
        } else {
            summerySection.classList.add('d-none');
        }
    }
}

function calculateTotal() {
    let subTotal = 0;
    document.querySelectorAll('#item_container tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.qty')?.value) || 0;
        const price = parseFloat(row.querySelector('.unit-price')?.value) || 0;
        const rowTot = qty * price;
        const totInput = row.querySelector('.total');
        if (totInput) totInput.value = rowTot.toFixed(2);
        subTotal += rowTot;
    });

    const discount = parseFloat(document.getElementById('discount')?.value) || 0;
    const vatPercent = parseFloat(document.getElementById('vat')?.value) || 0;
    const taxPercent = parseFloat(document.getElementById('tax')?.value) || 0;
    const deliveryCharge = parseFloat(document.getElementById('delivery_charge')?.value) || 0;
    const transportPayer = document.querySelector('input[name="transport_payer"]:checked')?.value || 'me';
    const billedDelivery = (transportPayer === 'vendor') ? deliveryCharge : 0;
    const labourCost = parseFloat(document.getElementById('labour_cost')?.value) || 0;
    const weightScaleCost = parseFloat(document.getElementById('weight_scale_cost')?.value) || 0;
    const otherCharges = parseFloat(document.getElementById('other_charges')?.value) || 0;

    const vatAmount = (subTotal * vatPercent) / 100;
    const taxAmount = (subTotal * taxPercent) / 100;

    const grandTotal = Math.max(0, subTotal - discount + vatAmount + taxAmount + billedDelivery + labourCost + weightScaleCost + otherCharges);
    
    document.getElementById('subTotal').value = subTotal.toFixed(2);
    document.getElementById('grandTotal').value = grandTotal.toFixed(2);
    
    const gtDisplay = document.getElementById('grandTotalDisplay');
    if (gtDisplay) {
        gtDisplay.innerText = '৳ ' + grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    const advanced = parseFloat(document.getElementById('advancedPayment')?.value) || 0;
    const currentDue = Math.max(0, grandTotal - advanced);
    document.getElementById('duePayment').value = currentDue.toFixed(2);
    
    const cdDisplay = document.getElementById('currentDueDisplay');
    if (cdDisplay) {
        cdDisplay.innerText = '৳ ' + currentDue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    const previousDue = parseFloat(window.selectedCustomerPreviousDue || 0);

    const pdDisplay = document.getElementById('previousDueDisplay');
    if (pdDisplay) {
        pdDisplay.innerText = '৳ ' + previousDue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    toggleSummarySection();
}

function validateLotRowQty(inputEl) {
    const lotSource = inputEl.getAttribute('data-lot-source');
    const max = parseFloat(inputEl.getAttribute('data-available')) || 0;
    const lotName = inputEl.getAttribute('data-lot-number') || 'Stock Lot';
    
    let totalLotInCart = 0;
    document.querySelectorAll(`#item_container tr[data-lot-source="${lotSource}"]`).forEach(row => {
        totalLotInCart += parseFloat(row.querySelector('.qty')?.value) || 0;
    });

    if (totalLotInCart > (max + 0.0001)) {
        alert(`Quantity exceeds available stock for ${lotName}!\n\nAvailable: ${max.toLocaleString()} kg\nTotal In Cart: ${totalLotInCart.toLocaleString()} kg`);
        const otherRowsQty = totalLotInCart - (parseFloat(inputEl.value) || 0);
        const maxForThisRow = Math.max(0.01, max - otherRowsQty);
        inputEl.value = maxForThisRow.toFixed(2);
        inputEl.classList.add('is-invalid');
        setTimeout(() => inputEl.classList.remove('is-invalid'), 2000);
    }
}

function validateSaleFormSubmission(e) {
    const rows = document.querySelectorAll('#item_container tr');
    if (rows.length === 0) {
        if (e) e.preventDefault();
        alert('Please add at least one steel line item before submitting.');
        return false;
    }

    let hasError = false;
    let errorMsg = '';
    const lotTotals = {};
    const lotMax = {};
    const lotNames = {};

    rows.forEach(row => {
        const qtyInput = row.querySelector('.qty');
        const lotSource = qtyInput?.getAttribute('data-lot-source') || row.getAttribute('data-lot-source');
        const qty = parseFloat(qtyInput?.value) || 0;
        const maxStock = parseFloat(qtyInput?.getAttribute('data-available')) || 0;
        const lotNum = qtyInput?.getAttribute('data-lot-number') || 'Stock Lot';

        if (lotSource) {
            lotTotals[lotSource] = (lotTotals[lotSource] || 0) + qty;
            lotMax[lotSource] = maxStock;
            lotNames[lotSource] = lotNum;
        }
    });

    for (const [lotSource, totalQty] of Object.entries(lotTotals)) {
        const maxStock = lotMax[lotSource];
        if (totalQty > (maxStock + 0.0001)) {
            hasError = true;
            errorMsg += `• ${lotNames[lotSource]}: Total selling weight (${totalQty.toLocaleString()} kg) exceeds available stock (${maxStock.toLocaleString()} kg).\n`;
        }
    }

    if (hasError) {
        if (e) e.preventDefault();
        alert(`Cannot save sale invoice due to stock limits:\n\n${errorMsg}`);
        return false;
    }

    reloadAfterSubmit();
    return true;
}

function handlePaymentMethodChange(method) {
    const bankContainer = document.getElementById('bankAccountContainer');
    const refContainer = document.getElementById('transactionRefContainer');
    if (!bankContainer || !refContainer) return;

    if (method === 'cash' || method === 'advance_credit') {
        bankContainer.style.display = 'none';
        refContainer.style.display = 'none';
    } else {
        bankContainer.style.display = 'block';
        refContainer.style.display = 'block';
    }
}

function reloadAfterSubmit() {
    setTimeout(function() {
        window.location.href = "{{ route('sales.index') }}";
    }, 500);
}
</script>
@endpush
