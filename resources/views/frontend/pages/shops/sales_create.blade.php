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
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header (No Breadcrumbs) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h4 class="card-title fw-bold text-dark mb-0">Shop Direct Sale</h4>
                    <span class="badge bg-primary text-white px-2.5 py-1 rounded-pill"><i class="fe fe-shopping-cart me-1"></i>{{ $shop->name }}</span>
                </div>
                <p class="text-muted small mb-0">Fast retail point-of-sale invoicing from shop inventory with live calculation &amp; instant billing</p>
            </div>
            <div>
                <a href="{{ route('shops.sales.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back to Shop Sales</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <form method="POST" action="{{ route('sales.store') }}" id="shopSaleForm" onsubmit="return validateShopSaleForm(event)">
        @csrf
        <input type="hidden" name="warehouse_id" id="warehouse_id" value="{{ $shop->id }}">
        <input type="hidden" name="delivery_status" value="delivered">

        <div class="row g-4">
            
            <!-- LEFT COLUMN (col-lg-8): Products & Line Items Builder -->
            <div class="col-lg-8 col-12">

                <!-- 1. Customer & Invoice Meta Box -->
                <div class="card border-0 shadow-sm rounded-3 mb-4 p-2">
                    <div class="pos-card-header d-flex justify-content-between align-items-center rounded-top-3">
                        <span><i class="fe fe-user text-primary me-2"></i>Customer &amp; Invoice Details</span>
                        <!-- <span class="badge bg-light text-dark border font-monospace">{{ $orderNo }}</span>
                        <input type="hidden" name="order_no" value="{{ $orderNo }}"> -->
                    </div>
                    <div class="card-body p-3">
                        
                        <!-- Customer Type Selector -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                            <div>
                                <label class="form-label small text-secondary fw-semibold mb-0 me-3">Customer Type <span class="text-danger">*</span></label>
                                <div class="form-check form-check-inline m-0 me-3">
                                    <input class="form-check-input" type="radio" name="client_type" id="shopExistingClient" value="existing" checked onchange="toggleCustomerType('existing')">
                                    <label class="form-check-label small fw-semibold text-dark" for="shopExistingClient">Existing Customer</label>
                                </div>
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input" type="radio" name="client_type" id="shopNewClient" value="new" onchange="toggleCustomerType('new')">
                                    <label class="form-check-label small fw-semibold text-dark" for="shopNewClient">New Customer</label>
                                </div>
                            </div>
                            <!-- <div class="d-flex align-items-center gap-2">
                                <label class="form-label small text-secondary fw-semibold mb-0">Date <span class="text-danger">*</span></label>
                                <input type="date" name="order_date" id="order_date" class="form-control form-control-sm" value="{{ old('order_date', date('Y-m-d')) }}" required style="width: 140px;">
                            </div> -->
                        </div>

                        <!-- Existing Client Selection Form -->
                        <div id="existingCustomerSection">
                            <div class="row g-3 align-items-center">
                                <div class="col-12">
                                    <label class="form-label small text-secondary fw-semibold mb-1">
                                        Select Existing Customer <span class="text-danger">*</span>
                                    </label>
                                    <select name="existing_client_id" id="customer_id" class="form-select select2" onchange="handleCustomerSelect(this)">
                                        <option value="">Select Customer...</option>
                                        @foreach ($existingClients as $client)
                                            <option value="{{ $client->id }}"
                                                data-name="{{ $client->name }}"
                                                data-phone="{{ $client->phone }}"
                                                data-address="{{ $client->address }}"
                                                data-opening-due="{{ $client->opening_balance > 0 ? $client->opening_balance : 0 }}"
                                                data-sales-due="{{ $client->sales_due ?? 0 }}"
                                                data-total-due="{{ $client->net_due }}"
                                                data-advance-credit="{{ $client->advance_credit }}"
                                                {{ old('existing_client_id') == $client->id ? 'selected' : '' }}>
                                                {{ $client->name }} ({{ $client->phone }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Customer Profile & Balance Preview Alert -->
                                <div class="col-12" id="customerDueAlert" style="display: none;">
                                    <div class="p-3 bg-light rounded-3 border d-flex flex-wrap align-items-center justify-content-between gap-2">
                                        <div>
                                            <span class="fw-bold text-dark d-block" id="custNameDisplay">Customer Name</span>
                                            <small class="text-muted" id="custPhoneDisplay">Phone: N/A</small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <div class="bg-white px-2 py-1 rounded border text-center">
                                                <small class="text-muted d-block" style="font-size: 9px; text-transform: uppercase;">Opening Due</small>
                                                <span id="custOpeningDueDisplay" class="fw-bold text-warning fs-7">৳0.00</span>
                                            </div>
                                            <div class="bg-white px-2 py-1 rounded border text-center">
                                                <small class="text-muted d-block" style="font-size: 9px; text-transform: uppercase;">Sales Due</small>
                                                <span id="custSalesDueDisplay" class="fw-bold text-danger fs-7">৳0.00</span>
                                            </div>
                                            <div class="bg-white px-2 py-1 rounded border text-center">
                                                <small class="text-muted d-block" style="font-size: 9px; text-transform: uppercase;">Net Outstanding</small>
                                                <span id="custNetDueDisplay" class="fw-bold text-dark fs-7">৳0.00</span>
                                            </div>
                                            <div class="bg-success-subtle px-2 py-1 rounded border border-success-subtle text-center" id="custAdvanceBadgeWrapper" style="display: none;">
                                                <small class="text-success d-block fw-bold" style="font-size: 9px; text-transform: uppercase;">Advance Amount</small>
                                                <span id="custAdvanceDisplay" class="fw-bold text-success fs-7">৳0.00</span>
                                            </div>
                                            <button type="button" id="btnQuickApplyAdv" class="btn btn-sm btn-success px-2 py-1 rounded-2 shadow-sm" style="display: none; font-size: 11px;" onclick="applyCustomerAdvance()">
                                                Apply Advance
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- New Customer Creation Form -->
                        <div id="newCustomerSection" style="display: none;">
                            <div class="row g-2">
                                <div class="col-md-4 col-12">
                                    <label class="form-label small text-secondary fw-semibold mb-1">Customer Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="new_cust_name" class="form-control form-control-sm" placeholder="e.g. Rahim Trading">
                                </div>
                                <div class="col-md-4 col-12">
                                    <label class="form-label small text-secondary fw-semibold mb-1">Phone Number <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" id="new_cust_phone" class="form-control form-control-sm" placeholder="017xxxxxxxx">
                                </div>
                                <div class="col-md-4 col-12">
                                    <label class="form-label small text-secondary fw-semibold mb-1">Address / Location</label>
                                    <input type="text" name="address" id="new_cust_address" class="form-control form-control-sm" placeholder="e.g. Tongi, Gazipur">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- 2. Shop Steel Items Picker & Line Items Grid -->
                <div class="card border-0 shadow-sm rounded-3 mb-4 p-2">
                    <div class="pos-card-header d-flex justify-content-between align-items-center rounded-top-3">
                        <span><i class="fe fe-box text-primary me-2"></i>Select Items From Shop Inventory</span>
                        <span class="badge bg-primary-subtle text-primary">{{ $coils->count() }} Items In Stock</span>
                    </div>
                    <div class="card-body p-3">
                        
                        <!-- Item Selector Row -->
                        <div class="bg-light p-3 rounded-3 border mb-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5 col-12">
                                    <label class="form-label small text-secondary fw-semibold mb-1">
                                        Choose Stock Item / Coil <span class="text-danger">*</span>
                                    </label>
                                    <select id="builder_coil_select" class="form-select select2" onchange="handleCoilSelected(this)">
                                        <option value="">Select an available coil in shop...</option>
                                        @foreach($coils as $c)
                                            @php
                                                $remKg = (float)$c->remaining_weight;
                                                $defRatePerTon = (float)($c->rate_per_ton > 0 ? $c->rate_per_ton * 1000 : 0);
                                                $defRatePerKg = (float)($c->rate_per_ton > 0 ? $c->rate_per_ton : 0);
                                            @endphp
                                            <option value="{{ $c->id }}"
                                                data-coil-no="{{ $c->coil_no }}"
                                                data-lot-id="{{ $c->lot_id ?? '' }}"
                                                data-lot-name="{{ $c->lot?->name ?? 'Direct Stock' }}"
                                                data-thickness="{{ $c->thickness }}"
                                                data-width="{{ $c->width }}"
                                                data-length="{{ $c->length }}"
                                                data-available="{{ $remKg }}"
                                                data-pieces="{{ $c->piece_count ?: 1 }}"
                                                data-rate-ton="{{ $defRatePerTon }}"
                                                data-rate-kg="{{ $defRatePerKg }}">
                                                {{ $c->coil_no }} | Thk: {{ $c->thickness }} | {{ $c->width }} | Avail: {{ number_format($remKg) }} kg
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2 col-6">
                                    <label class="form-label small text-secondary fw-semibold mb-1">Selling Pcs</label>
                                    <input type="number" id="builder_pcs" class="form-control" value="1" min="1" step="1">
                                </div>

                                <div class="col-md-2 col-6">
                                    <label class="form-label small text-secondary fw-semibold mb-1">Weight (Kg) <span class="text-danger">*</span></label>
                                    <input type="number" id="builder_weight" class="form-control fw-bold" placeholder="0.00" min="0.01" step="0.01">
                                </div>

                                <div class="col-md-2 col-6">
                                    <label class="form-label small text-secondary fw-semibold mb-1">Rate (৳/Ton) <span class="text-danger">*</span></label>
                                    <input type="number" id="builder_rate" class="form-control" placeholder="৳/Ton" min="0" step="0.01">
                                </div>

                                <div class="col-md-1 col-6">
                                    <button type="button" class="btn btn-primary w-100 py-2 rounded-2" onclick="addShopLineItem()" title="Add to Bill">
                                        <i class="fe fe-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2 text-muted small d-flex justify-content-between align-items-center" id="selectedCoilMeta" style="display: none !important;">
                                <span id="metaCoilSpec"></span>
                                <span class="badge bg-success-subtle text-success" id="metaCoilStock"></span>
                            </div>
                        </div>

                        <!-- Line Items Table -->
                        <div class="table-responsive">
                            <table class="table table-custom table-hover align-middle mb-0" id="shopItemsTable">
                                <thead class="bg-light text-secondary fs-7 text-uppercase">
                                    <tr>
                                        <th class="ps-3">Item / Coil</th>
                                        <th>Thickness</th>
                                        <th>Size / Spec</th>
                                        <th class="text-center" style="width: 80px;">Pieces</th>
                                        <th class="text-end" style="width: 120px;">Weight (Kg)</th>
                                        <th class="text-end" style="width: 130px;">Rate (৳/Ton)</th>
                                        <th class="text-end" style="width: 130px;">Sub Total (৳)</th>
                                        <th class="text-center" style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="item_container" class="fs-7">
                                    <!-- Dynamic rows inserted here -->
                                </tbody>
                                <tfoot id="tableFooterEmpty">
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <i class="fe fe-shopping-bag fs-2 text-secondary opacity-50 d-block mb-1"></i>
                                            <span>No steel items added yet. Select an available coil above and click (+).</span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                    </div>
                </div>

                <!-- 3. Dispatch Note -->
                <div class="card border-0 shadow-sm rounded-3 p-2">
                    <div class="pos-card-header rounded-top-3">
                        <span><i class="fe fe-file-text text-primary me-2"></i>Dispatch Note &amp; Delivery Transport Details</span>
                    </div>
                    <div class="card-body p-3">
                        <textarea name="note" id="note" class="form-control" rows="2" placeholder="Truck number, delivery driver contact, or transport instructions..."></textarea>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN (col-lg-4): Sticky Live Payment & Financial Summary -->
            <div class="col-lg-4 col-12">
                <div class="sticky-summary">

                    <!-- Live Items & Weight KPI Bar -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="p-3 bg-white rounded-3 shadow-sm border text-center">
                                <small class="text-muted text-uppercase fw-semibold fs-8 d-block mb-1">Total Pieces</small>
                                <h5 class="fw-bold text-dark mb-0" id="displayTotalPieces">0 pcs</h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-white rounded-3 shadow-sm border text-center">
                                <small class="text-muted text-uppercase fw-semibold fs-8 d-block mb-1">Total Weight</small>
                                <h5 class="fw-bold text-primary mb-0" id="displayTotalWeight">0.00 kg</h5>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary Card -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="pos-card-header rounded-top-3">
                            <span><i class="fe fe-dollar-sign text-success me-1"></i>Billing &amp; Financial Summary</span>
                        </div>
                        <div class="card-body p-3">
                            
                            <!-- Sub Total (৳) -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary small fw-semibold">Sub Total (৳):</span>
                                <span class="fw-bold text-dark" id="displaySubtotal">৳0.00</span>
                                <input type="hidden" name="subTotal" id="subTotal" value="0">
                                <input type="hidden" name="subtotal" id="subtotal" value="0">
                            </div>

                            <!-- Discount Amount (৳) & VAT (%) Row -->
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label fs-8 text-secondary fw-semibold mb-1">Discount Amount (৳)</label>
                                    <input type="number" name="discount" id="discount" class="form-control form-control-sm text-end" value="0.00" min="0" step="0.01" oninput="recalculateShopSummary()">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fs-8 text-secondary fw-semibold mb-1">VAT (%)</label>
                                    <input type="number" name="vat" id="vat" class="form-control form-control-sm text-end" value="0.00" min="0" step="0.01" oninput="recalculateShopSummary()">
                                </div>
                            </div>

                            <!-- Delivery / Transport (৳) with Two-Tick Selector (Paid by Me / Paid by Vendor) -->
                            <div class="border rounded-2 p-2 bg-light mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-semibold text-secondary">Delivery / Transport (৳):</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="form-check form-check-inline m-0">
                                            <input class="form-check-input" type="radio" name="transport_payer" id="transport_payer_me" value="me" checked onchange="recalculateShopSummary()">
                                            <label class="form-check-label small fs-8 text-primary fw-semibold" for="transport_payer_me">Paid by Me</label>
                                        </div>
                                        <div class="form-check form-check-inline m-0">
                                            <input class="form-check-input" type="radio" name="transport_payer" id="transport_payer_vendor" value="vendor" onchange="recalculateShopSummary()">
                                            <label class="form-check-label small fs-8 text-secondary fw-semibold" for="transport_payer_vendor">Paid by Vendor</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">৳</span>
                                    <input type="number" name="delivery_charge" id="delivery_charge" class="form-control text-end" value="0.00" min="0" step="0.01" oninput="recalculateShopSummary()">
                                </div>
                            </div>

                            <!-- Cutting & Labour Load-Unload (৳), Scale & Labour Charge (৳), Other Charges (৳) -->
                            <div class="row g-2 mb-3 align-items-end">
                                <div class="col-4">
                                    <label class="form-label fs-8 text-secondary fw-semibold mb-1" style="min-height: 30px; display: flex; align-items: flex-end;" title="Cutting & Labour Load-Unload">Cutting &amp; Labour (৳)</label>
                                    <input type="number" name="labour_cost" id="labour_cost" class="form-control form-control-sm text-end" value="0.00" min="0" step="0.01" oninput="recalculateShopSummary()" placeholder="0.00">
                                </div>
                                <div class="col-4">
                                    <label class="form-label fs-8 text-secondary fw-semibold mb-1" style="min-height: 30px; display: flex; align-items: flex-end;" title="Scale & Labour Charge">Scale &amp; Charge (৳)</label>
                                    <input type="number" name="weight_scale_cost" id="weight_scale_cost" class="form-control form-control-sm text-end" value="0.00" min="0" step="0.01" oninput="recalculateShopSummary()" placeholder="0.00">
                                </div>
                                <div class="col-4">
                                    <label class="form-label fs-8 text-secondary fw-semibold mb-1" style="min-height: 30px; display: flex; align-items: flex-end;" title="Other Charges">Other Charges (৳)</label>
                                    <input type="number" name="other_charges" id="other_charges" class="form-control form-control-sm text-end" value="0.00" min="0" step="0.01" oninput="recalculateShopSummary()" placeholder="0.00">
                                </div>
                            </div>

                            <!-- GRAND TOTAL BANNER -->
                            <div class="grand-total-display text-center mb-3">
                                <small class="text-white-50 text-uppercase fw-bold fs-8 d-block mb-1">Grand Total / Net Payable</small>
                                <h3 class="fw-bold text-white mb-0" id="displayGrandTotal">৳ 0.00</h3>
                                <input type="hidden" name="grandTotal" id="grandTotal" value="0">
                                <input type="hidden" name="total" id="total" value="0">
                                <input type="hidden" name="payble" id="payble" value="0">
                                <input type="hidden" name="bill" id="bill" value="0">
                            </div>

                            <!-- Payment Section -->
                            <div class="border-top pt-3">
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label small text-secondary fw-semibold mb-1">Current Payment (৳) <span class="text-danger">*</span></label>
                                        <input type="number" name="advanced_payment" id="advanced_payment" class="form-control text-end fw-bold text-success" value="0.00" min="0" step="0.01" oninput="recalculateShopSummary()">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-secondary fw-semibold mb-1">Remaining Due (৳)</label>
                                        <input type="number" name="due_payment" id="due_payment" class="form-control text-end fw-bold text-danger bg-light" value="0.00" readonly>
                                        <input type="hidden" name="duePayment" id="duePayment" value="0">
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label small text-secondary fw-semibold mb-1">Payment Method <span class="text-danger">*</span></label>
                                    <select name="payment_method" id="payment_method" class="form-select form-select-sm" onchange="handlePaymentMethod(this.value)">
                                        <option value="cash" selected>Cash Payment</option>
                                        <option value="bank">Bank Transfer</option>
                                        <option value="mobile_banking">Mobile Banking (bKash / Nagad)</option>
                                        <option value="advance_credit">Customer Advance Amount</option>
                                    </select>
                                </div>

                                <div id="bankSelectionDiv" class="mb-2" style="display: none;">
                                    <label class="form-label small text-secondary fw-semibold mb-1">Deposit Bank Account <span class="text-danger">*</span></label>
                                    <select name="bank_detail_id" id="bank_detail_id" class="form-select form-select-sm">
                                        <option value="">Select Bank Account</option>
                                        @foreach($bankAccounts as $bank)
                                            <option value="{{ $bank->id }}" {{ $bank->is_default ? 'selected' : '' }}>{{ $bank->bank_name }} - {{ $bank->account_number }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="txnRefDiv" class="mb-3" style="display: none;">
                                    <label class="form-label small text-secondary fw-semibold mb-1">Transaction Ref #</label>
                                    <input type="text" name="transaction_ref" id="transaction_ref" class="form-control form-control-sm" placeholder="e.g. TXN-12345">
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-3 shadow fw-bold d-flex align-items-center justify-content-center gap-2">
                                <span>Save &amp; Generate Invoice</span>
ene                            </button>

                        </div>
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
let shopLineItems = [];
let selectedCustomerAdvance = 0;

$(document).ready(function() {
    $('.select2').select2({ width: '100%' });
});

function handleCustomerSelect(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    const alertDiv = document.getElementById('customerDueAlert');
    if (!opt || !opt.value) {
        alertDiv.style.display = 'none';
        selectedCustomerAdvance = 0;
        return;
    }

    const name = opt.getAttribute('data-name');
    const phone = opt.getAttribute('data-phone');
    const openingDue = parseFloat(opt.getAttribute('data-opening-due') || 0);
    const salesDue = parseFloat(opt.getAttribute('data-sales-due') || 0);
    const netDue = parseFloat(opt.getAttribute('data-total-due') || 0);
    const advanceCredit = parseFloat(opt.getAttribute('data-advance-credit') || 0);

    selectedCustomerAdvance = advanceCredit;

    document.getElementById('custNameDisplay').innerText = name;
    document.getElementById('custPhoneDisplay').innerText = 'Phone: ' + (phone || 'N/A');
    document.getElementById('custOpeningDueDisplay').innerText = '৳' + openingDue.toFixed(2);
    document.getElementById('custSalesDueDisplay').innerText = '৳' + salesDue.toFixed(2);
    document.getElementById('custNetDueDisplay').innerText = '৳' + netDue.toFixed(2);

    const advWrapper = document.getElementById('custAdvanceBadgeWrapper');
    const btnAdv = document.getElementById('btnQuickApplyAdv');
    if (advanceCredit > 0) {
        advWrapper.style.display = 'block';
        btnAdv.style.display = 'inline-block';
        document.getElementById('custAdvanceDisplay').innerText = '৳' + advanceCredit.toFixed(2);
    } else {
        advWrapper.style.display = 'none';
        btnAdv.style.display = 'none';
    }

    alertDiv.style.display = 'block';
}

function applyCustomerAdvance() {
    if (selectedCustomerAdvance <= 0) return;
    const grandTotal = parseFloat(document.getElementById('grandTotal').value || document.getElementById('payble').value || 0);
    const applyAmt = grandTotal > 0 ? Math.min(grandTotal, selectedCustomerAdvance) : selectedCustomerAdvance;
    
    document.getElementById('advanced_payment').value = applyAmt.toFixed(2);
    document.getElementById('payment_method').value = 'advance_credit';
    handlePaymentMethod('advance_credit');
    recalculateShopSummary();
}

function handleCoilSelected(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    if (!opt || !opt.value) {
        document.getElementById('selectedCoilMeta').style.setProperty('display', 'none', 'important');
        return;
    }

    const thickness = opt.getAttribute('data-thickness');
    const width = opt.getAttribute('data-width');
    const length = opt.getAttribute('data-length');
    const available = parseFloat(opt.getAttribute('data-available') || 0);
    const defaultRate = parseFloat(opt.getAttribute('data-rate-ton') || 0);

    document.getElementById('builder_weight').value = available > 0 ? available.toFixed(2) : '';
    document.getElementById('builder_weight').setAttribute('max', available);
    document.getElementById('builder_rate').value = defaultRate > 0 ? defaultRate.toFixed(2) : '';

    const metaDiv = document.getElementById('selectedCoilMeta');
    document.getElementById('metaCoilSpec').innerText = `Spec: ${thickness} | ${width} ${length ? 'x ' + length : ''}`;
    document.getElementById('metaCoilStock').innerText = `Avail: ${available.toLocaleString()} kg`;
    metaDiv.style.display = 'flex';
}

function addShopLineItem() {
    const selectEl = document.getElementById('builder_coil_select');
    const opt = selectEl.options[selectEl.selectedIndex];
    if (!opt || !opt.value) {
        alert('Please select an available coil from shop inventory.');
        return;
    }

    const coilId = opt.value;
    const coilNo = opt.getAttribute('data-coil-no');
    const lotId = opt.getAttribute('data-lot-id');
    const thickness = opt.getAttribute('data-thickness');
    const width = opt.getAttribute('data-width');
    const length = opt.getAttribute('data-length');
    const maxAvailable = parseFloat(opt.getAttribute('data-available') || 0);
    
    const pcs = parseInt(document.getElementById('builder_pcs').value) || 1;
    const weight = parseFloat(document.getElementById('builder_weight').value) || 0;
    const ratePerTon = parseFloat(document.getElementById('builder_rate').value) || 0;

    if (weight <= 0) {
        alert('Please enter a valid selling weight.');
        return;
    }

    if (weight > maxAvailable + 0.01) {
        alert(`Weight (${weight} kg) cannot exceed available stock (${maxAvailable} kg).`);
        return;
    }

    const lineTotal = (weight * ratePerTon) / 1000;
    const ratePerKg = ratePerTon / 1000;

    // Check if coil already added
    const existingIndex = shopLineItems.findIndex(item => item.coil_id === coilId);
    if (existingIndex > -1) {
        alert('This coil is already added in the table. Remove or edit existing row.');
        return;
    }

    shopLineItems.push({
        coil_id: coilId,
        coil_no: coilNo,
        lot_id: lotId,
        thickness: thickness,
        width: width,
        length: length,
        custom_size: `${thickness || ''} | ${width || ''}${length ? ' x ' + length : ''}`.trim() || 'Standard Spec',
        pieces: pcs,
        weight: weight,
        rate_per_ton: ratePerTon,
        rate_per_kg: ratePerKg,
        subtotal: lineTotal,
        max_available: maxAvailable
    });

    // Reset builder inputs
    selectEl.value = '';
    $('#builder_coil_select').val('').trigger('change');
    document.getElementById('builder_pcs').value = '1';
    document.getElementById('builder_weight').value = '';
    document.getElementById('builder_rate').value = '';

    renderShopTable();
}

function removeShopItem(index) {
    shopLineItems.splice(index, 1);
    renderShopTable();
}

function renderShopTable() {
    const tbody = document.getElementById('item_container');
    const footerEmpty = document.getElementById('tableFooterEmpty');
    tbody.innerHTML = '';

    if (shopLineItems.length === 0) {
        footerEmpty.style.display = '';
        recalculateShopSummary();
        return;
    }

    footerEmpty.style.display = 'none';

    shopLineItems.forEach((item, idx) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="ps-3">
                <span class="badge bg-light text-dark border font-monospace fw-bold">${item.coil_no}</span>
                <input type="hidden" name="coil_id[]" value="${item.coil_id}">
                <input type="hidden" name="lot_id[]" value="${item.lot_id}">
                <input type="hidden" name="custom_size[]" value="${item.custom_size}">
            </td>
            <td>
                <span class="fw-bold text-dark">${item.thickness}</span>
                <input type="hidden" name="thickness[]" value="${item.thickness}">
            </td>
            <td>
                <span>${item.width} ${item.length ? 'x ' + item.length : ''}</span>
                <input type="hidden" name="size[]" value="${item.width}">
                <input type="hidden" name="size_type[]" value="${item.length || 'ft'}">
            </td>
            <td class="text-center">
                <input type="number" name="piece_count[]" class="form-control form-control-sm text-center" value="${item.pieces}" min="1" oninput="updateLinePiece(${idx}, this.value)">
            </td>
            <td class="text-end">
                <input type="number" name="qty[]" class="form-control form-control-sm text-end fw-bold" value="${item.weight.toFixed(2)}" min="0.01" step="0.01" max="${item.max_available}" oninput="updateLineWeight(${idx}, this.value)">
            </td>
            <td class="text-end">
                <input type="number" class="form-control form-control-sm text-end" value="${item.rate_per_ton.toFixed(2)}" min="0" step="0.01" oninput="updateLineRate(${idx}, this.value)">
                <input type="hidden" name="unit_price[]" id="unit_price_${idx}" value="${item.rate_per_kg.toFixed(4)}">
            </td>
            <td class="text-end fw-bold text-dark" id="line_subtotal_${idx}">
                ৳${item.subtotal.toFixed(2)}
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger p-1 rounded" onclick="removeShopItem(${idx})">
                    <i class="fe fe-trash-2"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    recalculateShopSummary();
}

function updateLinePiece(idx, val) {
    if (shopLineItems[idx]) {
        shopLineItems[idx].pieces = parseInt(val) || 1;
        recalculateShopSummary();
    }
}

function updateLineWeight(idx, val) {
    if (shopLineItems[idx]) {
        const w = parseFloat(val) || 0;
        shopLineItems[idx].weight = w;
        shopLineItems[idx].subtotal = (w * shopLineItems[idx].rate_per_ton) / 1000;
        const subDisplay = document.getElementById(`line_subtotal_${idx}`);
        if (subDisplay) subDisplay.innerText = `৳${shopLineItems[idx].subtotal.toFixed(2)}`;
        recalculateShopSummary();
    }
}

function updateLineRate(idx, val) {
    if (shopLineItems[idx]) {
        const r = parseFloat(val) || 0;
        shopLineItems[idx].rate_per_ton = r;
        shopLineItems[idx].rate_per_kg = r / 1000;
        shopLineItems[idx].subtotal = (shopLineItems[idx].weight * r) / 1000;
        
        const ratePerKgInput = document.getElementById(`unit_price_${idx}`);
        if (ratePerKgInput) ratePerKgInput.value = (r / 1000).toFixed(4);

        const subDisplay = document.getElementById(`line_subtotal_${idx}`);
        if (subDisplay) subDisplay.innerText = `৳${shopLineItems[idx].subtotal.toFixed(2)}`;
        recalculateShopSummary();
    }
}

function recalculateShopSummary() {
    let totalPieces = 0;
    let totalWeight = 0;
    let itemsSubtotal = 0;

    shopLineItems.forEach(item => {
        totalPieces += item.pieces;
        totalWeight += item.weight;
        itemsSubtotal += item.subtotal;
    });

    document.getElementById('displayTotalPieces').innerText = totalPieces + ' pcs';
    document.getElementById('displayTotalWeight').innerText = totalWeight.toFixed(2) + ' kg';
    document.getElementById('displaySubtotal').innerText = '৳' + itemsSubtotal.toFixed(2);
    
    document.getElementById('subTotal').value = itemsSubtotal.toFixed(2);
    document.getElementById('subtotal').value = itemsSubtotal.toFixed(2);

    const transportPayer = document.querySelector('input[name="transport_payer"]:checked')?.value || 'me';
    const deliveryCharge = parseFloat(document.getElementById('delivery_charge').value) || 0;
    const billedDelivery = (transportPayer === 'vendor') ? deliveryCharge : 0;
    
    const labourCost = parseFloat(document.getElementById('labour_cost').value) || 0;
    const scaleCost = parseFloat(document.getElementById('weight_scale_cost').value) || 0;
    const otherCharges = parseFloat(document.getElementById('other_charges').value) || 0;
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const vatPercent = parseFloat(document.getElementById('vat').value) || 0;
    const vatAmount = (itemsSubtotal * vatPercent) / 100;

    let grandTotal = itemsSubtotal - discount + vatAmount + billedDelivery + labourCost + scaleCost + otherCharges;
    grandTotal = Math.max(0, grandTotal);

    document.getElementById('displayGrandTotal').innerText = '৳ ' + grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('grandTotal').value = grandTotal.toFixed(2);
    document.getElementById('total').value = grandTotal.toFixed(2);
    document.getElementById('payble').value = grandTotal.toFixed(2);
    document.getElementById('bill').value = grandTotal.toFixed(2);

    const paidAmt = parseFloat(document.getElementById('advanced_payment').value) || 0;
    const dueAmt = Math.max(0, grandTotal - paidAmt);
    document.getElementById('due_payment').value = dueAmt.toFixed(2);
    document.getElementById('duePayment').value = dueAmt.toFixed(2);
}

function handlePaymentMethod(method) {
    const bankDiv = document.getElementById('bankSelectionDiv');
    const txnDiv = document.getElementById('txnRefDiv');
    if (method === 'bank' || method === 'mobile_banking') {
        bankDiv.style.display = 'block';
        txnDiv.style.display = 'block';
    } else {
        bankDiv.style.display = 'none';
        txnDiv.style.display = 'none';
    }
}

function toggleCustomerType(type) {
    const existingSection = document.getElementById('existingCustomerSection');
    const newSection = document.getElementById('newCustomerSection');
    const custDueAlert = document.getElementById('customerDueAlert');
    const custSelect = document.getElementById('customer_id');
    const newName = document.getElementById('new_cust_name');
    const newPhone = document.getElementById('new_cust_phone');

    if (type === 'new') {
        existingSection.style.display = 'none';
        newSection.style.display = 'block';
        if (custDueAlert) custDueAlert.style.display = 'none';
        selectedCustomerAdvance = 0;
        
        if (custSelect) custSelect.required = false;
        if (newName) newName.required = true;
        if (newPhone) newPhone.required = true;
    } else {
        existingSection.style.display = 'block';
        newSection.style.display = 'none';
        
        if (custSelect) {
            custSelect.required = true;
            handleCustomerSelect(custSelect);
        }
        if (newName) newName.required = false;
        if (newPhone) newPhone.required = false;
    }
}

function validateShopSaleForm(e) {
    if (shopLineItems.length === 0) {
        e.preventDefault();
        alert('Please add at least one steel line item before completing the sale.');
        return false;
    }

    const clientType = document.querySelector('input[name="client_type"]:checked')?.value || 'existing';
    if (clientType === 'existing') {
        const customerSelect = document.getElementById('customer_id');
        if (!customerSelect || !customerSelect.value) {
            e.preventDefault();
            alert('Please select an existing customer.');
            if (customerSelect) customerSelect.focus();
            return false;
        }
    } else {
        const nameInput = document.getElementById('new_cust_name');
        const phoneInput = document.getElementById('new_cust_phone');
        if (!nameInput?.value.trim()) {
            e.preventDefault();
            alert('Please enter customer name.');
            nameInput?.focus();
            return false;
        }
        if (!phoneInput?.value.trim()) {
            e.preventDefault();
            alert('Please enter customer phone number.');
            phoneInput?.focus();
            return false;
        }
    }
    return true;
}
</script>
@endpush

