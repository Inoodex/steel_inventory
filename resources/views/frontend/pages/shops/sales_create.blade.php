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

    <form method="POST" action="{{ route('sales.store') }}" id="shopSaleForm" target="_blank" onsubmit="return validateShopSaleForm(event)">
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
                                                data-sales-due="{{ $client->sales_sum_due_payment ?? 0 }}"
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
                        <span class="badge bg-primary-subtle text-primary" id="badgeStockSummary">In-Stock Lots &amp; Opening Stock</span>
                    </div>
                    <div class="card-body p-3">
                        
                        <!-- Item Selector Row (7 Fields Exact Same as Normal Sale) -->
                        <div class="bg-light p-3 rounded-3 border mb-3" id="form-group-item1">
                            <div class="row g-3 align-items-end">
                                <!-- 1. Stock / Lot Source Select Dropdown -->
                                <div class="col-lg-4 col-md-6 col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label small text-secondary fw-semibold mb-0">
                                            1. Select Stock / Lot Source <span class="text-danger">*</span>
                                        </label>
                                        <span id="lotAvgRateBadge" class="badge bg-primary-subtle text-primary border" style="display:none; font-size: 11px;"></span>
                                    </div>
                                    <select id="builder_lot_id" class="form-select select2 border-light-subtle" onchange="handleLotSelected(this)">
                                        <option value="">Select Stock / Lot Source...</option>
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

                                <!-- 4. Custom Size / Specs (Required, not auto-filled) -->
                                <div class="col-lg-3 col-md-6 col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label small text-secondary fw-semibold mb-0">Custom Size / Specs <span class="text-danger">*</span></label>
                                    </div>
                                    <input type="text" id="custom_size1" class="form-control border-light-subtle" placeholder="e.g. 4x8 ft cut">
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

                                <!-- Add Item Button -->
                                <div class="col-12 text-end pt-1">
                                    <button type="button" onclick="addShopLineItem()" class="btn btn-success px-4 rounded-3 d-inline-flex align-items-center justify-content-center gap-2 py-2 shadow-sm">
                                        <i class="fe fe-plus"></i>
                                        <span>Add Steel Item</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Line Items Table -->
                        <div class="table-responsive">
                            <table class="table table-custom table-hover align-middle mb-0" id="shopItemsTable">
                                <thead class="bg-light text-secondary fs-7 text-uppercase">
                                    <tr>
                                        <th class="ps-3">Stock / Lot Source</th>
                                        <th>Thickness</th>
                                        <th>Custom Size / Spec</th>
                                        <th class="text-end" style="width: 120px;">Weight (Kg)</th>
                                        <th class="text-end" style="width: 130px;">Rate (৳/kg)</th>
                                        <th class="text-end" style="width: 130px;">Sub Total (৳)</th>
                                        <th class="text-center" style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="item_container" class="fs-7">
                                    <!-- Dynamic rows inserted here -->
                                </tbody>
                                <tfoot id="tableFooterEmpty">
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fe fe-shopping-bag fs-2 text-secondary opacity-50 d-block mb-1"></i>
                                            <span>No steel items added yet. Select a Stock / Lot Source above and click Add Steel Item.</span>
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
                                <small class="text-muted text-uppercase fw-semibold fs-8 d-block mb-1">Total Items</small>
                                <h5 class="fw-bold text-dark mb-0" id="displayTotalPieces">0 items</h5>
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
                                    <div class="d-flex align-items-center gap-2 d-none">
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



                                <div class="mb-2">
                                    <label class="form-label small text-secondary fw-semibold mb-1">
                                        <i class="fe fe-layers me-1 text-primary"></i> Receiving Account / Method
                                    </label>
                                    <select id="shopSaleAccountSelector" class="form-select form-select-sm" onchange="handleShopSaleAccountChange(this)">
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
                                    <input type="hidden" name="payment_method" id="hidden_payment_method" value="bank">
                                    <input type="hidden" name="bank_detail_id" id="hidden_bank_detail_id" value="">
                                </div>

                                <div id="txnRefDiv" class="mb-3" style="display: none;">
                                    <label class="form-label small text-secondary fw-semibold mb-1">Transaction Ref #</label>
                                    <input type="text" name="transaction_ref" id="transaction_ref" class="form-control form-control-sm" placeholder="e.g. TXN-12345">
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-3 shadow fw-bold d-flex align-items-center justify-content-center gap-2">
                                <span>Save &amp; Generate Invoice</span>
                            </button>

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
const allAvailableCoils = @json($coils);
const allAvailableLots = @json($lots->keyBy('id'));
const currentShopId = "{{ $shop->id }}";
let currentSelectedLot = null;
let shopLineItems = [];
let selectedCustomerAdvance = 0;

$(document).ready(function() {
    $('.select2').select2({ width: '100%' });
    populateShopLotSources();
});

function escapeHtml(text) {
    if (!text) return '';
    return $('<div>').text(text).html();
}

function populateShopLotSources() {
    const lotSelect = $('#builder_lot_id');
    lotSelect.empty();
    lotSelect.append('<option value="">Select Stock / Lot Source...</option>');

    // Filter coils belonging to this shop with remaining weight > 0
    const shopCoils = allAvailableCoils.filter(c => String(c.warehouse_id) === String(currentShopId) && parseFloat(c.remaining_weight) > 0);

    const openingCoils = shopCoils.filter(c => (c.purchase_id === null || !c.lot_id));
    const openingCount = openingCoils.length;
    const openingWeight = openingCoils.reduce((sum, c) => sum + (parseFloat(c.remaining_weight) || 0), 0);

    const totalCount = shopCoils.length;
    const totalWeight = shopCoils.reduce((sum, c) => sum + (parseFloat(c.remaining_weight) || 0), 0);

    if (openingCount > 0 || totalCount > 0) {
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
                    🌐 All In-Stock in Shop (${totalCount} coils, ${totalWeight.toLocaleString()} kg)
                </option>
            `);
        }
        lotSelect.append(directOptgroup);
    }

    // Purchase Mill Lots optgroup
    const lotGroups = {};
    shopCoils.forEach(c => {
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
        lotSelect.append('<option value="" disabled>No in-stock steel coils found in shop inventory</option>');
    }

    if ($.fn.select2) {
        lotSelect.select2({ width: '100%' });
    }
}

function handleLotSelected(selectEl) {
    const lotId = $(selectEl).val();
    const lotAvgBadge = document.getElementById('lotAvgRateBadge');

    if (!lotId) {
        currentSelectedLot = null;
        resetBuilderFields();
        return;
    }

    const shopCoils = allAvailableCoils.filter(c => String(c.warehouse_id) === String(currentShopId) && parseFloat(c.remaining_weight) > 0);

    let filtered = [];
    let lotNumberDisplay = '';
    let vendorDisplay = '';

    if (lotId === 'opening_stock') {
        lotNumberDisplay = 'Opening Stock';
        vendorDisplay = 'Direct Yard Stock';
        filtered = shopCoils.filter(c => (c.purchase_id === null || !c.lot_id));
    } else if (lotId === 'all_stock') {
        lotNumberDisplay = 'All Stock';
        vendorDisplay = 'All Inventory';
        filtered = shopCoils;
    } else {
        const lotOption = $(`#builder_lot_id option[value="${lotId}"]`);
        lotNumberDisplay = lotOption.data('lot-number') || lotOption.text().trim();
        vendorDisplay = lotOption.data('vendor') || '';
        filtered = shopCoils.filter(c => String(c.lot_id) === String(lotId));
    }

    if (filtered.length === 0) {
        currentSelectedLot = null;
        resetBuilderFields();
        return;
    }

    const totalLotWeight = filtered.reduce((sum, c) => sum + (parseFloat(c.remaining_weight) || 0), 0);
    const totalLotCost = filtered.reduce((sum, c) => sum + ((parseFloat(c.remaining_weight) || 0) * (parseFloat(c.rate_per_ton) || 0)), 0);
    const avgLotRate = totalLotWeight > 0 ? (totalLotCost / totalLotWeight) : 0;

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
    document.getElementById('custom_size1').value = '';
    document.getElementById('qty1').focus();

    updatePreviewTotal();
}

function resetBuilderFields() {
    currentSelectedLot = null;
    document.getElementById('stock1').value = '';
    document.getElementById('purchase_price1').value = '';
    document.getElementById('custom_size1').value = '';
    document.getElementById('unit_price1').value = '';
    document.getElementById('qty1').value = '';
    document.getElementById('total1').value = '0.00';
    const lotAvgBadge = document.getElementById('lotAvgRateBadge');
    if (lotAvgBadge) lotAvgBadge.style.display = 'none';
}

function updatePreviewTotal() {
    const qty = parseFloat(document.getElementById('qty1')?.value) || 0;
    const price = parseFloat(document.getElementById('unit_price1')?.value) || 0;
    const total = qty * price;
    const totalEl = document.getElementById('total1');
    if (totalEl) {
        totalEl.value = '৳ ' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
}

function addShopLineItem() {
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
    if (qty > availableStock + 0.0001) {
        alert(`Cannot sell more than available lot stock!\n\nRequested: ${qty.toLocaleString()} kg\nAvailable Stock: ${availableStock.toLocaleString()} kg`);
        qtyInput.value = availableStock;
        qtyInput.focus();
        updatePreviewTotal();
        return;
    }

    // Check if lot already in cart
    const lotRawSource = currentSelectedLot.raw_source;
    let alreadyInCart = 0;
    shopLineItems.forEach(item => {
        if (item.raw_source === lotRawSource) {
            alreadyInCart += item.weight;
        }
    });

    if ((alreadyInCart + qty) > (availableStock + 0.0001)) {
        const remainingAllowed = Math.max(0, availableStock - alreadyInCart);
        alert(`Cannot exceed available lot stock!\n\nLot: ${currentSelectedLot.lot_number}\nTotal Available: ${availableStock.toLocaleString()} kg\nAlready in Cart: ${alreadyInCart.toLocaleString()} kg\nRemaining Allowed: ${remainingAllowed.toLocaleString()} kg`);
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

    const customSizeInput = document.getElementById('custom_size1');
    const customSize = (customSizeInput?.value || '').trim();
    if (!customSize) {
        alert('Please enter Custom Size / Specs.');
        customSizeInput?.focus();
        return;
    }

    const lineTotal = qty * unitPrice;

    shopLineItems.push({
        raw_source: lotRawSource,
        lot_id: currentSelectedLot.id,
        lot_number: currentSelectedLot.lot_number,
        vendor: currentSelectedLot.vendor,
        thickness: currentSelectedLot.thickness,
        size: currentSelectedLot.size,
        size_type: currentSelectedLot.size_type,
        custom_size: customSize,
        weight: qty,
        rate: unitPrice,
        subtotal: lineTotal,
        max_available: availableStock
    });

    // Reset builder inputs
    $('#builder_lot_id').val('').trigger('change');
    resetBuilderFields();

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
        const badgeClass = (!item.lot_id || item.lot_number === 'Opening Stock') ? 'bg-info-subtle text-info border-info-subtle' : 'bg-light text-dark border';
        tr.innerHTML = `
            <td class="ps-3">
                <span class="badge ${badgeClass} border fw-bold">${escapeHtml(item.lot_number)}</span>
                ${item.vendor ? `<small class="text-muted d-block" style="font-size: 11px;">${escapeHtml(item.vendor)}</small>` : ''}
                <input type="hidden" name="coil_id[]" value="">
                <input type="hidden" name="lot_id[]" value="${escapeHtml(item.lot_id)}">
            </td>
            <td>
                <span class="fw-bold text-dark">${escapeHtml(item.thickness || '—')}</span>
                <input type="hidden" name="thickness[]" value="${escapeHtml(item.thickness)}">
            </td>
            <td>
                <input type="text" name="custom_size[]" class="form-control form-control-sm" value="${escapeHtml(item.custom_size)}" required oninput="updateLineCustomSize(${idx}, this.value)">
                <input type="hidden" name="size[]" value="${escapeHtml(item.size)}">
                <input type="hidden" name="size_type[]" value="${escapeHtml(item.size_type)}">
            </td>
            <td class="text-end">
                <input type="number" name="qty[]" class="form-control form-control-sm text-end fw-bold" value="${item.weight.toFixed(2)}" min="0.01" step="0.01" max="${item.max_available}" oninput="updateLineWeight(${idx}, this.value)">
            </td>
            <td class="text-end">
                <input type="number" name="unit_price[]" class="form-control form-control-sm text-end" value="${item.rate.toFixed(2)}" min="0" step="0.01" oninput="updateLineRate(${idx}, this.value)">
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

function updateLineWeight(idx, val) {
    if (shopLineItems[idx]) {
        const w = parseFloat(val) || 0;
        shopLineItems[idx].weight = w;
        shopLineItems[idx].subtotal = w * shopLineItems[idx].rate;
        const subDisplay = document.getElementById(`line_subtotal_${idx}`);
        if (subDisplay) subDisplay.innerText = `৳${shopLineItems[idx].subtotal.toFixed(2)}`;
        recalculateShopSummary();
    }
}

function updateLineRate(idx, val) {
    if (shopLineItems[idx]) {
        const r = parseFloat(val) || 0;
        shopLineItems[idx].rate = r;
        shopLineItems[idx].subtotal = shopLineItems[idx].weight * r;
        const subDisplay = document.getElementById(`line_subtotal_${idx}`);
        if (subDisplay) subDisplay.innerText = `৳${shopLineItems[idx].subtotal.toFixed(2)}`;
        recalculateShopSummary();
    }
}

function updateLineCustomSize(idx, val) {
    if (shopLineItems[idx]) {
        shopLineItems[idx].custom_size = val;
    }
}

function recalculateShopSummary() {
    let totalWeight = 0;
    let itemsSubtotal = 0;

    shopLineItems.forEach(item => {
        totalWeight += item.weight;
        itemsSubtotal += item.subtotal;
    });

    document.getElementById('displayTotalPieces').innerText = shopLineItems.length + ' items';
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

    const paidInput = document.getElementById('advanced_payment');
    let paidAmt = parseFloat(paidInput?.value) || 0;
    if (paidInput) {
        paidInput.setAttribute('max', grandTotal.toFixed(2));
    }
    if (paidAmt > grandTotal) {
        paidAmt = grandTotal;
        if (paidInput) paidInput.value = grandTotal.toFixed(2);
    }
    const dueAmt = Math.max(0, grandTotal - paidAmt);
    document.getElementById('due_payment').value = dueAmt.toFixed(2);
    document.getElementById('duePayment').value = dueAmt.toFixed(2);
}

function handleShopSaleAccountChange(selectEl) {
    const selectedOpt = selectEl.options[selectEl.selectedIndex];
    if (!selectedOpt) return;
    const val = selectEl.value;
    const methodInput = document.getElementById('hidden_payment_method');
    const bankInput = document.getElementById('hidden_bank_detail_id');
    const txnDiv = document.getElementById('txnRefDiv');

    if (val === 'advance_credit') {
        if (methodInput) methodInput.value = 'advance_credit';
        if (bankInput) bankInput.value = '';
        if (txnDiv) txnDiv.style.display = 'none';
    } else {
        const method = selectedOpt.getAttribute('data-method') || 'bank';
        const bankId = selectedOpt.getAttribute('data-id') || '';
        if (methodInput) methodInput.value = method;
        if (bankInput) bankInput.value = bankId;
        if (txnDiv) txnDiv.style.display = bankId ? 'block' : 'none';
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

    const advancedPaymentVal = parseFloat(document.getElementById('advanced_payment')?.value) || 0;
    const grandTotalVal = parseFloat(document.getElementById('grandTotal')?.value) || 0;
    if (advancedPaymentVal > (grandTotalVal + 0.01)) {
        if (e) e.preventDefault();
        alert(`Current Payment (৳${advancedPaymentVal.toFixed(2)}) cannot exceed remaining due / invoice amount (৳${grandTotalVal.toFixed(2)}).`);
        document.getElementById('advanced_payment')?.focus();
        return false;
    }

    reloadAfterSubmit();
    return true;
}

function reloadAfterSubmit() {
    setTimeout(function() {
        window.location.href = "{{ route('shops.sales.index') }}";
    }, 500);
}
</script>
@endpush
