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
                            <input type="text" name="name" class="form-control border-light-subtle" id="newClientName" placeholder="Enter Customer Name" required autocomplete="off">
                        </div>
                        <div class="col-lg-4 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control border-light-subtle" id="newClientPhone" placeholder="Enter Phone Number" required autocomplete="off">
                        </div>
                        <div class="col-lg-4 col-md-12 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Address <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control border-light-subtle" id="newClientAddress" placeholder="Enter Customer Address" required autocomplete="off">
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
                                        $prevDue = (float)($client->sales_sum_due_payment ?? 0);
                                    @endphp
                                    <option value="{{ $client->id }}" 
                                        data-name="{{ $client->name }}"
                                        data-phone="{{ $client->phone }}" 
                                        data-address="{{ $client->address }}" 
                                        data-previous-due="{{ $prevDue }}">
                                        {{ $client->name }} — {{ $client->phone }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Minimal Customer Profile & Due Widget -->
                        <div class="col-lg-6 col-md-6 col-12">
                            <div id="customerBalanceCard" class="p-3 bg-light rounded-3 border h-100 d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-semibold text-dark mb-0" id="custNameText">No Customer Selected</div>
                                    <div class="small text-muted" id="custContactText" style="font-size: 11px;">Select customer to view previous balance</div>
                                </div>
                                <div class="text-end">
                                    <span class="text-secondary small d-block" style="font-size: 10px; text-transform: uppercase;">Previous Due</span>
                                    <span id="custBalanceBadge" class="fw-bold text-secondary fs-6">৳ 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4 text-light">

                <!-- Order & Dispatch Meta Row -->
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Warehouse / Dispatch Yard <span class="text-danger">*</span></label>
                        <select name="warehouse_id" id="warehouse_id" class="form-select select2" required>
                            <option value="">Select Warehouse / Yard</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ $loop->first ? 'selected' : '' }}>
                                    {{ $wh->name }} {{ $wh->code ? '('.$wh->code.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Delivery Status <span class="text-danger">*</span></label>
                        <select name="delivery_status" id="delivery_status" class="form-select border-light-subtle" required>
                            <option value="pending" selected>Pending (Order Received)</option>
                            <option value="dispatched">Dispatched (In Transit / Truck Loaded)</option>
                            <option value="delivered">Delivered (Received at Site)</option>
                            <option value="partial_delivered">Partial Delivered</option>
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
                    <h6 class="fw-bold text-dark mb-0"><i class="fe fe-shopping-cart me-2 text-primary"></i>Steel Items & In-Stock Coils Selection</h6>
                    <small class="text-muted">1. Select Lot &rarr; 2. Select Coil from that Lot &rarr; 3. Enter desired selling quantity and rate</small>
                </div>

                <!-- Product Add Builder Card -->
                <div class="p-3 bg-light rounded-3 mb-4 border" id="form-group-item1">
                    <div class="row g-3 align-items-end">
                        <!-- 1. Mandatory Lot Select Dropdown -->
                        <!-- 1. Select Lot Source -->
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">
                                1. Select Lot Source <span class="text-danger">*</span>
                            </label>
                            <select id="builder_lot_id" class="form-select select2 border-light-subtle" onchange="handleLotSelection(this.value)" required>
                                <option value="">Select Lot</option>
                                @foreach ($lots as $lot)
                                    <option value="{{ $lot->id }}" 
                                        data-vendor="{{ $lot->vendor ? $lot->vendor->name : 'No Vendor' }}"
                                        data-lot-number="{{ $lot->lot_number }}">
                                        {{ $lot->lot_number }} {{ $lot->vendor ? '('.$lot->vendor->name.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 2. Coil Select Dropdown (Filtered by selected Lot) -->
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">
                                2. Select In-Stock Coil <span class="text-danger">*</span>
                            </label>
                            <select onchange="selectCoil(this)" id="coil_select" class="form-select select2 border-light-subtle" disabled>
                                <option value="">Select a Lot first</option>
                            </select>
                        </div>

                        <!-- 3. Per Coil Weight (Readonly) -->
                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Per Coil Wt (kg)</label>
                            <input type="text" id="per_coil_weight1" class="form-control border-light-subtle bg-white fw-bold text-dark" readonly placeholder="0.00 kg">
                        </div>

                        <!-- 4. Available Coil Weight -->
                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Available Weight</label>
                            <input type="text" id="stock1" class="form-control border-light-subtle bg-white fw-bold text-primary" readonly placeholder="0.00 kg">
                        </div>

                        <!-- 5. Cost Rate -->
                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Cost Rate (৳)</label>
                            <input type="number" id="purchase_price1" class="form-control border-light-subtle bg-white" readonly placeholder="0.00">
                        </div>

                        <!-- 6. Selling Rate -->
                        <div class="col-lg-3 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Selling Rate (৳) <span class="text-danger">*</span></label>
                            <input oninput="updatePreviewTotal()" onchange="updatePreviewTotal()" type="number" id="unit_price1" class="form-control border-light-subtle" step="0.01" min="0" placeholder="0.00">
                        </div>

                        <!-- 7. Selling Quantity -->
                        <div class="col-lg-3 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Selling Qty / Wt (kg) <span class="text-danger">*</span></label>
                            <input oninput="updatePreviewTotal()" onchange="updatePreviewTotal()" type="number" id="qty1" class="form-control border-light-subtle text-dark" step="0.01" min="0.01" placeholder="Enter weight in kg">
                        </div>

                        <!-- 8. Line Total Preview -->
                        <div class="col-lg-3 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Line Total (৳)</label>
                            <input type="text" id="total1" class="form-control border-light-subtle bg-white fw-bold text-success" readonly value="0.00">
                        </div>

                        <!-- 9. Add Item Button -->
                        <div class="col-lg-3 col-md-6 col-12 ms-auto">
                            <button type="button" onclick="addItem()" class="btn btn-success w-100 rounded-3 d-inline-flex align-items-center justify-content-center gap-2 py-2">
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
                                <th style="width: 35%;">Product & Specifications</th>
                                <th style="width: 20%;">Lot Source</th>
                                <th style="width: 15%;">Unit Price</th>
                                <th style="width: 12%;">Quantity</th>
                                <th style="width: 13%;">Total Price</th>
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

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Tax (%)</label>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="tax" name="tax" class="form-control border-light-subtle" value="0" min="0" step="0.01">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Delivery / Transport (৳)</label>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="delivery_charge" name="delivery_charge" class="form-control border-light-subtle" value="0" min="0" step="0.01">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Labour / Loading (৳)</label>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="labour_cost" name="labour_cost" class="form-control border-light-subtle" value="0" min="0" step="0.01">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Weight Scale (৳)</label>
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
                            <input type="text" name="transaction_ref" id="transactionRefInput" class="form-control border-light-subtle bg-white" placeholder="e.g. Bank Trx # or Deposit Slip Ref">
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
});

function handleCustomerChange(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    if (selectedOption && selectedOption.value) {
        const prevDue = parseFloat(selectedOption.dataset.previousDue) || 0;
        const name = selectedOption.dataset.name || selectedOption.text.split('—')[0].trim();
        const phone = selectedOption.dataset.phone || 'N/A';
        const address = selectedOption.dataset.address || 'N/A';
        window.selectedCustomerPreviousDue = prevDue;
        updateCustomerBalanceCard(prevDue, name, `Phone: ${phone} | Addr: ${address}`);
    } else {
        window.selectedCustomerPreviousDue = 0;
        updateCustomerBalanceCard(0, null, null);
    }
    calculateTotal();
}

function updateCustomerBalanceCard(due, name, details) {
    const nameText = document.getElementById('custNameText');
    const contactText = document.getElementById('custContactText');
    const badge = document.getElementById('custBalanceBadge');

    if (!nameText || !badge) return;

    if (!name) {
        nameText.innerText = 'No Customer Selected';
        nameText.className = 'fw-semibold text-dark mb-0';
        contactText.innerText = 'Select customer to view previous balance';
        badge.innerText = '৳ 0.00';
        badge.className = 'fw-bold text-secondary fs-6';
        return;
    }

    nameText.innerText = name;
    contactText.innerText = details;

    if (due > 0) {
        badge.className = 'fw-bold text-danger fs-6';
        badge.innerText = '৳ ' + due.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    } else {
        badge.className = 'fw-bold text-success fs-6';
        badge.innerText = '৳ 0.00 (Clear)';
    }
}

const allAvailableCoils = @json($coils);
let currentSelectedLot = null;
let currentSelectedCoil = null;

function handleLotSelection(lotId) {
    const coilSelect = $('#coil_select');
    coilSelect.empty();
    
    currentSelectedLot = null;
    currentSelectedCoil = null;
    resetCoilFields();

    if (!lotId) {
        coilSelect.append('<option value="">Select a Lot first</option>');
        coilSelect.prop('disabled', true);
        coilSelect.trigger('change');
        return;
    }

    const lotOption = $(`#builder_lot_id option[value="${lotId}"]`);
    if (lotOption.length) {
        currentSelectedLot = {
            id: lotId,
            lot_number: lotOption.data('lot-number') || lotOption.text().trim(),
            vendor: lotOption.data('vendor') || ''
        };
    }

    const filtered = allAvailableCoils.filter(c => String(c.lot_id) === String(lotId) && parseFloat(c.remaining_weight) > 0);

    if (filtered.length === 0) {
        coilSelect.append('<option value="">No in-stock coils in this Lot</option>');
        coilSelect.prop('disabled', true);
        coilSelect.trigger('change');
        return;
    }

    coilSelect.prop('disabled', false);
    coilSelect.append('<option value="">Choose In-Stock Coil</option>');
    filtered.forEach(coil => {
        const remaining = parseFloat(coil.remaining_weight) || 0;
        const thickness = coil.thickness ? ` | Thk: ${coil.thickness}` : '';
        const sizeVal = coil.width || coil.size || '';
        const sizeUnit = (coil.length && coil.length !== 'N/A') ? coil.length : (coil.size_type || '');
        const sizeText = sizeVal ? ` | Size: ${sizeVal}${sizeUnit ? ' ' + sizeUnit : ''}` : '';
        const yard = coil.warehouse ? ` (${coil.warehouse.name})` : '';
        const pieceCount = coil.piece_count ? Number(coil.piece_count) : 1;
        const grossWeight = parseFloat(coil.gross_weight || coil.net_weight || 0);
        const unitWeight = pieceCount > 0 && grossWeight > 0 ? (grossWeight / pieceCount) : remaining;
        const remainingCoils = unitWeight > 0 ? (remaining / unitWeight) : (pieceCount > 0 ? pieceCount : 1);
        const formattedRemCoils = (Math.round(remainingCoils * 100) / 100).toFixed(remainingCoils % 1 === 0 ? 0 : (remainingCoils * 10 % 1 === 0 ? 1 : 2));
        const remainingPct = grossWeight > 0 ? Math.min(100, Math.max(0, (remaining / grossWeight) * 100)).toFixed(1) : '100.0';
        const text = `${coil.coil_number} | Stock: ${formattedRemCoils}/${pieceCount} Coils (${remainingPct}%) ${thickness}${sizeText} | Avail: ${remaining.toLocaleString()} kg`;

        const opt = $('<option></option>')
            .val(coil.id)
            .text(text)
            .attr('data-coil-number', coil.coil_number)
            .attr('data-thickness', coil.thickness || '')
            .attr('data-size', sizeVal)
            .attr('data-size-type', sizeUnit || 'ft')
            .attr('data-piece-count', pieceCount)
            .attr('data-remaining-coils', formattedRemCoils)
            .attr('data-remaining-pct', remainingPct)
            .attr('data-unit-weight', unitWeight)
            .attr('data-gross-weight', grossWeight)
            .attr('data-remaining', remaining)
            .attr('data-rate', coil.rate_per_ton || 0)
            .attr('data-lot-id', coil.lot_id)
            .attr('data-lot-number', coil.lot ? coil.lot.lot_number : (currentSelectedLot ? currentSelectedLot.lot_number : ''))
            .attr('data-warehouse', coil.warehouse ? coil.warehouse.name : '');

        coilSelect.append(opt);
    });

    if ($.fn.select2) {
        if (coilSelect.hasClass('select2-hidden-accessible')) {
            coilSelect.select2('destroy');
        }
        coilSelect.select2({ width: '100%' });
    }
    coilSelect.trigger('change');
}

function resetCoilFields() {
    currentSelectedCoil = null;
    const perCoilEl = document.getElementById('per_coil_weight1');
    if (perCoilEl) perCoilEl.value = '';
    document.getElementById('stock1').value = '';
    document.getElementById('purchase_price1').value = '';
    document.getElementById('unit_price1').value = '';
    document.getElementById('qty1').value = '';
    document.getElementById('total1').value = '0.00';
}

function selectCoil(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    if (!selectedOption || !selectedOption.value) {
        resetCoilFields();
        return;
    }

    const remaining = parseFloat(selectedOption.dataset.remaining) || 0;
    const rate = parseFloat(selectedOption.dataset.rate) || 0;
    const unitWeight = parseFloat(selectedOption.dataset.unitWeight) || 0;
    const pieceCount = parseFloat(selectedOption.dataset.pieceCount) || 1;
    const remainingCoils = selectedOption.dataset.remainingCoils || '1';
    const remainingPct = selectedOption.dataset.remainingPct || '100.0';
    const lotId = selectedOption.dataset.lotId;

    currentSelectedCoil = {
        id: selectedOption.value,
        coil_number: selectedOption.dataset.coilNumber,
        thickness: selectedOption.dataset.thickness,
        size: selectedOption.dataset.size,
        size_type: selectedOption.dataset.sizeType,
        piece_count: pieceCount,
        remaining_coils: remainingCoils,
        remaining_pct: remainingPct,
        unit_weight: unitWeight,
        remaining: remaining,
        rate: rate,
        lot_id: lotId,
        lot_number: selectedOption.dataset.lotNumber,
        warehouse: selectedOption.dataset.warehouse
    };

    const perCoilEl = document.getElementById('per_coil_weight1');
    if (perCoilEl) {
        perCoilEl.value = unitWeight > 0 ? (unitWeight.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' kg') : '—';
    }
    document.getElementById('stock1').value = remaining.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ` kg (${remainingCoils} Coils | ${remainingPct}%)`;
    document.getElementById('purchase_price1').value = rate.toFixed(2);
    document.getElementById('unit_price1').value = rate > 0 ? rate.toFixed(2) : '';
    document.getElementById('qty1').value = '';
    document.getElementById('qty1').focus();

    updatePreviewTotal();
}

function updatePreviewTotal() {
    const qty = parseFloat(document.getElementById('qty1').value) || 0;
    const price = parseFloat(document.getElementById('unit_price1').value) || 0;
    const total = qty * price;
    document.getElementById('total1').value = '৳ ' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function addItem() {
    if (!currentSelectedLot) {
        alert('Please select a Lot first.');
        $('#builder_lot_id').focus();
        return;
    }

    if (!currentSelectedCoil) {
        alert('Please select an In-Stock Coil from the selected lot.');
        $('#coil_select').focus();
        return;
    }

    const qtyInput = document.getElementById('qty1');
    const qty = parseFloat(qtyInput.value) || 0;
    if (qty <= 0) {
        alert('Please enter your desired selling quantity (kg).');
        qtyInput.focus();
        return;
    }

    const availableStock = currentSelectedCoil.remaining;
    if (qty > availableStock) {
        alert(`Cannot sell more than available coil stock!\n\nRequested: ${qty.toLocaleString()} kg\nAvailable Stock: ${availableStock.toLocaleString()} kg`);
        qtyInput.value = availableStock;
        qtyInput.focus();
        updatePreviewTotal();
        return;
    }

    // Check if coil is already added in another row in the cart
    let alreadyAddedQty = 0;
    document.querySelectorAll(`#item_container tr.item-coil-${currentSelectedCoil.id}`).forEach(row => {
        alreadyAddedQty += parseFloat(row.querySelector('.qty')?.value) || 0;
    });
    if ((alreadyAddedQty + qty) > (availableStock + 0.0001)) {
        const remainingAllowed = Math.max(0, availableStock - alreadyAddedQty);
        alert(`Cannot exceed available coil stock!\n\nCoil: ${currentSelectedCoil.coil_number}\nTotal Available: ${availableStock.toLocaleString()} kg\nAlready in Cart: ${alreadyAddedQty.toLocaleString()} kg\nRemaining Allowed: ${remainingAllowed.toLocaleString()} kg`);
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

    const coil = currentSelectedCoil;
    const coilId = coil.id;
    const thickness = coil.thickness || '';
    const size = coil.size || '';
    const sizeType = coil.size_type || 'ft';
    const rowTotal = (qty * unitPrice).toFixed(2);

    const lot = currentSelectedLot;
    const lotId = lot ? lot.id : (coil.lot_id || '');
    const lotLabel = lot ? `<span class="badge bg-light text-dark border px-2 py-1 fs-8"><i class="fe fe-package text-primary me-1"></i>${lot.lot_number}</span>` : '<span class="text-muted small">Lot</span>';
    const pieceCount = coil.piece_count ? Number(coil.piece_count) : 1;
    let specBadges = ``;
    if (pieceCount > 1) specBadges += `<span class="badge bg-light text-dark border px-2 py-1 fs-8 me-1">Qty: ${pieceCount} Coils</span>`;
    if (thickness) specBadges += `<span class="badge bg-light text-dark border px-2 py-1 fs-8 me-1">Thickness: ${thickness}</span>`;
    if (size) specBadges += `<span class="badge bg-light text-secondary border px-2 py-1 fs-8 me-1">Size: ${size} ${sizeType}</span>`;

    const html = `
        <tr class="item-coil-${coilId} group-item" data-itemnumber="${itemNumber}" id="form-group-item${itemNumber}">
            <td>
                <input type="hidden" name="coil_id[]" value="${coilId}">
                <input type="hidden" name="lot_id[]" value="${lotId}">
                <input type="hidden" name="thickness[]" value="${thickness}">
                <input type="hidden" name="size[]" value="${size}">
                <input type="hidden" name="size_type[]" value="${sizeType}">
                <span class="fw-bold text-dark d-block">Coil No - ${coil.coil_number}</span>
                <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                    ${specBadges}
                </div>
            </td>
            <td>
                ${lotLabel}
            </td>
            <td>
                <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" step="0.01" name="unit_price[]" id="unit_price${itemNumber}" class="form-control border-light-subtle unit-price" value="${unitPrice.toFixed(2)}">
            </td>
            <td>
                <input oninput="validateRowQty(this); calculateTotal()" onchange="validateRowQty(this); calculateTotal()" type="number" step="0.01" name="qty[]" id="qty${itemNumber}" class="form-control border-light-subtle qty fw-bold" min="0.01" max="${availableStock}" data-available="${availableStock}" data-coil-id="${coilId}" data-coil-number="${coil.coil_number}" value="${qty}">
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

    // Reset coil inputs for next addition
    $('#coil_select').val('').trigger('change');
    resetCoilFields();

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
    const labourCost = parseFloat(document.getElementById('labour_cost')?.value) || 0;
    const weightScaleCost = parseFloat(document.getElementById('weight_scale_cost')?.value) || 0;
    const otherCharges = parseFloat(document.getElementById('other_charges')?.value) || 0;

    const vatAmount = (subTotal * vatPercent) / 100;
    const taxAmount = (subTotal * taxPercent) / 100;

    const grandTotal = Math.max(0, subTotal - discount + vatAmount + taxAmount + deliveryCharge + labourCost + weightScaleCost + otherCharges);
    
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

function validateRowQty(inputEl) {
    const coilId = inputEl.getAttribute('data-coil-id');
    const availableStock = parseFloat(inputEl.getAttribute('data-available')) || 0;
    const coilNumber = inputEl.getAttribute('data-coil-number') || '';
    
    let totalCoilInCart = 0;
    document.querySelectorAll(`#item_container tr.item-coil-${coilId}`).forEach(row => {
        totalCoilInCart += parseFloat(row.querySelector('.qty')?.value) || 0;
    });

    if (totalCoilInCart > (availableStock + 0.0001)) {
        alert(`Quantity exceeds available stock for Coil ${coilNumber}!\n\nAvailable: ${availableStock.toLocaleString()} kg\nTotal In Cart: ${totalCoilInCart.toLocaleString()} kg`);
        const otherRowsQty = totalCoilInCart - (parseFloat(inputEl.value) || 0);
        const maxForThisRow = Math.max(0.01, availableStock - otherRowsQty);
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
    const coilTotals = {};
    const coilMax = {};
    const coilNames = {};

    rows.forEach(row => {
        const qtyInput = row.querySelector('.qty');
        const coilId = qtyInput?.getAttribute('data-coil-id');
        const qty = parseFloat(qtyInput?.value) || 0;
        const maxStock = parseFloat(qtyInput?.getAttribute('data-available')) || 0;
        const coilNum = qtyInput?.getAttribute('data-coil-number') || '';

        if (coilId) {
            coilTotals[coilId] = (coilTotals[coilId] || 0) + qty;
            coilMax[coilId] = maxStock;
            coilNames[coilId] = coilNum;
        }
    });

    for (const [coilId, totalQty] of Object.entries(coilTotals)) {
        const maxStock = coilMax[coilId];
        if (totalQty > (maxStock + 0.0001)) {
            hasError = true;
            errorMsg += `• Coil ${coilNames[coilId]}: Total selling weight (${totalQty.toLocaleString()} kg) exceeds available stock (${maxStock.toLocaleString()} kg).\n`;
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

    if (method === 'cash') {
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
