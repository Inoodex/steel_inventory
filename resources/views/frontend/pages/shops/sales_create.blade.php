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
                    <h4 class="card-title fw-bold text-dark mb-0">Shop Direct Sale</h4>
                    <span class="badge bg-primary text-white px-2.5 py-1 rounded-pill"><i class="fe fe-shopping-cart me-1"></i>{{ $shop->name }}</span>
                </div>
                <p class="text-muted small mb-0">Fast retail point-of-sale invoicing from shop inventory with live calculation &amp; instant billing</p>
            </div>
            <div>
                <a href="{{ route('shops.sales.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back to Shop Sales</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <form action="{{ route('sales.store') }}" method="POST" target="_blank" onsubmit="return validateSaleFormSubmission(event)" id="createSaleForm">
        @csrf
        <input type="hidden" name="warehouse_id" id="warehouse_id" value="{{ $shop->id }}">
        <input type="hidden" name="delivery_status" value="delivered">

        <!-- Section 1: Customer & Order Dispatch Information -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3"><i class="fe fe-user me-2 text-primary"></i>Customer &amp; Dispatch Details</h6>

                <!-- Customer Type Toggle -->
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="form-label small text-secondary fw-semibold mb-2">Customer Type <span class="text-danger">*</span></label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="client_type" id="newClient" value="new" {{ old('client_type') === 'new' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold text-dark" for="newClient">New Customer</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="client_type" id="existingClient" value="existing" {{ old('client_type', 'existing') === 'existing' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold text-dark" for="existingClient">Existing Customer</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Client Form -->
                <div id="newClientForm" style="display: none;">
                    <div class="row g-3">
                        <div class="col-lg-4 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control border-light-subtle" id="newClientName" autocomplete="off">
                        </div>
                        <div class="col-lg-4 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control border-light-subtle" id="newClientPhone" autocomplete="off">
                        </div>
                        <div class="col-lg-4 col-md-12 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Address <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control border-light-subtle" id="newClientAddress" autocomplete="off">
                        </div>
                    </div>
                </div>

                <!-- Existing Client Form -->
                <div id="existingClientForm">
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
                        
                        <!-- Customer Profile & Due Widget -->
                        <div class="col-lg-6 col-md-6 col-12">
                            <div id="customerBalanceCard" class="p-3 bg-light rounded-3 border h-100 d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div>
                                    <div class="fw-semibold text-dark mb-0" id="custNameText">No Customer Selected</div>
                                    <div class="small text-muted" id="custContactText" style="font-size: 11px;">Select customer to view previous balance &amp; advance amount</div>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <div id="custOpeningDueWrapper" style="display:none;">
                                        <span class="text-muted small d-block" style="font-size: 10px;">Opening Due:</span>
                                        <span class="fw-bold text-dark fs-7" id="custOpeningDueBadge">৳ 0.00</span>
                                    </div>
                                    <div id="custSalesDueWrapper" style="display:none;">
                                        <span class="text-muted small d-block" style="font-size: 10px;">Sales Due:</span>
                                        <span class="fw-bold text-dark fs-7" id="custSalesDueBadge">৳ 0.00</span>
                                    </div>
                                    <div id="custAdvanceWrapper" style="display:none;">
                                        <span class="text-muted small d-block" style="font-size: 10px; color:#198754 !important;">Advance Deposit:</span>
                                        <span class="fw-bold text-success fs-7" id="custAdvanceCreditBadge">৳ 0.00</span>
                                    </div>
                                    <div id="custBalanceWrapper">
                                        <span class="text-muted small d-block" style="font-size: 10px;">Net Balance:</span>
                                        <span class="fw-bold text-secondary fs-7" id="custBalanceBadge">৳ 0.00</span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-success py-1 px-2.5 rounded-pill shadow-none" id="btnApplyAdvance" style="display:none; font-size:11px;" onclick="applyCustomerAdvanceCredit()">
                                        <i class="fe fe-check-circle me-1"></i>Apply Advance
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Meta Fields -->
                <div class="row g-3 mt-1 pt-3 border-top">
                    <div class="col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Invoice / Order No <span class="text-danger">*</span></label>
                        <input type="text" name="order_no" class="form-control border-light-subtle bg-light text-muted fw-bold" value="{{ $orderNo }}" readonly>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Order Date <span class="text-danger">*</span></label>
                        <input type="date" name="order_date" class="form-control border-light-subtle" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">
                            Dispatch Shop Location <span class="text-danger">*</span>
                            <span class="badge bg-primary-subtle text-primary border ms-1"><i class="fe fe-shopping-cart me-1"></i>Shop Locked</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-primary border-primary-subtle"><i class="fe fe-home"></i></span>
                            <input type="text" class="form-control bg-light fw-bold text-dark border-primary-subtle" value="{{ $shop->name }} {{ $shop->code ? '('.$shop->code.')' : '' }}" readonly>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Dispatch Note / Truck &amp; Transport Details</label>
                        <input type="text" name="dispatch_note" class="form-control border-light-subtle" placeholder="e.g. Counter pickup / Local van">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Add Product / Coils to Invoice -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3"><i class="fe fe-box me-2 text-primary"></i>Add Product / Coils to Invoice</h6>

                <!-- Coil Builder Card -->
                <div class="bg-light p-3 rounded-3 mb-3 border">
                    <div class="row g-3 align-items-end">
                        <!-- 0. Stock / Lot Source Selector -->
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">
                                Stock / Lot Source
                                <span id="lotAvgRateBadge" class="badge bg-secondary-subtle text-secondary ms-1 fs-8" style="display: none;"></span>
                            </label>
                            <select id="builder_lot_id" class="form-select select2 border-light-subtle" onchange="handleLotSelection(this.value)">
                                <option value="">Select Stock / Lot Source</option>
                            </select>
                        </div>

                        <!-- 1. Coil Selection -->
                        <div class="col-lg-4 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">
                                Select Coil / Stock Piece <span class="text-danger">*</span>
                            </label>
                            <select id="product1" class="form-select select2 border-light-subtle" onchange="getProduct(1)">
                                <option value="">Select Stock Source First</option>
                            </select>
                        </div>

                        <!-- 2. Available Total Weight -->
                        <div class="col-lg-3 col-md-3 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Total Available Stock</label>
                            <input type="text" id="stock1" class="form-control border-light-subtle bg-white fw-bold text-primary" readonly placeholder="0.00 kg">
                        </div>

                        <!-- 3. Cost Rate -->
                        <div class="col-lg-2 col-md-3 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Avg Cost Rate (৳)</label>
                            <input type="number" id="purchase_price1" class="form-control border-light-subtle bg-white" readonly placeholder="0.00">
                        </div>

                        <!-- 4. Custom Size / Specs -->
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Custom Size / Specs</label>
                            <input type="text" id="custom_size1" class="form-control border-light-subtle" placeholder="e.g. 4x8 ft cut">
                        </div>

                        <!-- 5. Selling Rate -->
                        <div class="col-lg-2 col-md-3 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Selling Rate (৳) <span class="text-danger">*</span></label>
                            <input oninput="updatePreviewTotal()" onchange="updatePreviewTotal()" type="number" id="unit_price1" class="form-control border-light-subtle" step="0.01" min="0" placeholder="0.00">
                        </div>

                        <!-- 6. Selling Quantity -->
                        <div class="col-lg-2 col-md-3 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Selling Qty / Wt (kg) <span class="text-danger">*</span></label>
                            <input oninput="updatePreviewTotal()" onchange="updatePreviewTotal()" type="number" id="qty1" class="form-control border-light-subtle text-dark fw-bold text-center" step="0.01" min="0.01" placeholder="0.00">
                        </div>

                        <!-- 7. Line Total Preview -->
                        <div class="col-lg-3 col-md-6 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Line Total (৳)</label>
                            <input type="text" id="total1" class="form-control border-light-subtle bg-white fw-bold text-success text-end" readonly value="0.00">
                        </div>

                        <!-- 8. Add Item Button -->
                        <div class="col-lg-2 col-md-6 col-12">
                            <button type="button" onclick="addItem()" class="btn btn-success w-100 rounded-3 d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm fw-semibold" style="height: 38px;">
                                <i class="fe fe-plus"></i>
                                <span class="text-nowrap">Add Steel Item</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Added Items List Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="cartItemsTable">
                        <thead class="bg-light text-secondary fs-7 text-uppercase">
                            <tr>
                                <th style="width: 29%;">Product &amp; Specifications</th>
                                <th style="width: 16%;">Lot Source</th>
                                <th style="width: 14%;">Custom Size (Admin)</th>
                                <th style="width: 11%;">Unit Price</th>
                                <th style="width: 8%;">Quantity</th>
                                <th style="width: 17%;">Total Price</th>
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
                <h6 class="fw-bold text-dark mb-3"><i class="fe fe-dollar-sign me-2 text-primary"></i>Operational Charges &amp; Financial Breakdown</h6>

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

                    <div class="col-lg-3 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Delivery / Transport (৳)</label>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="delivery_charge" name="delivery_charge" class="form-control border-light-subtle" value="{{ old('delivery_charge', 0) }}" min="0" step="0.01">
                    </div>

                    <div class="col-lg-3 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Cutting &amp; Labour Load-Unload (৳)</label>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="labour_cost" name="labour_cost" class="form-control border-light-subtle" value="0" min="0" step="0.01">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Scale &amp; Labour Charge (৳)</label>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="weight_scale_cost" name="weight_scale_cost" class="form-control border-light-subtle" value="0" min="0" step="0.01">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Other Charges (৳)</label>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="other_charges" name="other_charges" class="form-control border-light-subtle" value="0" min="0" step="0.01">
                    </div>

                    <input type="hidden" id="grandTotal" name="grandTotal" value="0.00">
                    <input type="hidden" id="duePayment" name="duePayment" value="0.00">
                </div>

                <!-- Financial & Cash Tender / Change Cards Row -->
                <div class="row g-3 mb-3">
                    <!-- Grand Total -->
                    <div class="col-xl-3 col-lg-3 col-md-6 col-12">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <span class="text-muted small fw-semibold d-block mb-1">Invoice Grand Total</span>
                            <h4 class="mb-0 fw-bold text-dark" id="grandTotalDisplay">৳ 0.00</h4>
                        </div>
                    </div>

                    <!-- Cash Tendered / Received Input -->
                    <div class="col-xl-3 col-lg-3 col-md-6 col-12">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small text-muted fw-semibold mb-0">
                                    Cash Tendered / Received <span class="text-danger">*</span>
                                </label>
                                <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 rounded-pill shadow-none" onclick="setExactTendered()" style="font-size: 11px;">
                                    Exact
                                </button>
                            </div>
                            <input oninput="handleTenderedInput()" onchange="handleTenderedInput()" type="number" id="tenderedAmount" 
                                class="form-control border-light-subtle fw-bold text-dark bg-white fs-6" placeholder="0.00" min="0" step="0.01">
                            <input type="hidden" name="advanced_payment" id="advancedPayment" value="0.00">
                            <input type="hidden" id="changeReturnAmount" value="0.00">
                            <small class="text-muted d-block mt-1" style="font-size: 11px;" id="appliedPaymentHelp">
                                Settled: <strong class="text-success" id="displayAppliedPayment">৳ 0.00</strong>
                            </small>
                        </div>
                    </div>

                    <!-- Change Return (ফেরত) -->
                    <div class="col-xl-2 col-lg-2 col-md-4 col-12">
                        <div class="p-3 rounded-3 border h-100 transition-all" id="changeCardWrapper" style="background-color: #f8fafc; border-color: #e2e8f0;">
                            <span class="small fw-semibold d-block mb-1 text-success">
                                <i class="fe fe-corner-down-left me-1"></i>Change Return (ফেরত)
                            </span>
                            <h4 class="mb-0 fw-bold text-success" id="changeReturnDisplay">৳ 0.00</h4>
                        </div>
                    </div>

                    <!-- Current Invoice Due -->
                    <div class="col-xl-2 col-lg-2 col-md-4 col-12">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <span class="text-muted small fw-semibold d-block mb-1">Current Invoice Due</span>
                            <h4 class="mb-0 fw-bold text-danger" id="currentDueDisplay">৳ 0.00</h4>
                        </div>
                    </div>

                    <!-- Customer Previous Due -->
                    <div class="col-xl-2 col-lg-2 col-md-4 col-12">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <span class="text-muted small fw-semibold d-block mb-1">Customer Previous Due</span>
                            <h4 class="mb-0 fw-bold text-secondary" id="previousDueDisplay">৳ 0.00</h4>
                        </div>
                    </div>
                </div>

                <!-- Payment Method & Banking Details Section -->
                <div class="card border border-light-subtle rounded-3 bg-light-subtle p-3 mb-3" id="paymentMethodSection">
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

                    <div class="row g-3 align-items-center">
                        <div class="col-lg-7 col-md-7 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">
                                <i class="fe fe-layers me-1 text-primary"></i> Receiving Account / Method
                            </label>
                            <select id="saleAccountSelector" class="form-select border-light-subtle" onchange="handleSaleAccountChange(this)">
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
                            <input type="hidden" name="payment_method" id="hidden_payment_method" value="bank">
                            <input type="hidden" name="bank_detail_id" id="hidden_bank_detail_id" value="">
                        </div>

                        <!-- Transaction Reference / Notes -->
                        <div class="col-lg-5 col-md-5 col-12" id="transactionRefContainer">
                            <label class="form-label small text-secondary fw-semibold mb-1">
                                <i class="fe fe-file-text me-1 text-secondary"></i> TrxID / Ref #
                            </label>
                            <input type="text" name="transaction_ref" id="transactionRefInput" class="form-control border-light-subtle bg-white" placeholder="e.g. TXN-12345">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 pt-2 border-top">
                    <a href="{{ route('shops.sales.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3">Cancel</a>
                    <button type="submit" class="btn btn-primary px-5 py-2 rounded-3 fw-semibold shadow-sm">
                        Save &amp; Generate Invoice
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
        if (newClientRadio && newClientRadio.checked) {
            if (newClientForm) newClientForm.style.display = 'block';
            if (existingClientForm) existingClientForm.style.display = 'none';
            newClientInputs.forEach(input => input.required = true);
            const clientSel = document.getElementById('clientSelect');
            if (clientSel) clientSel.required = false;
            window.selectedCustomerPreviousDue = 0;
            updateCustomerBalanceCard(0, null, null);
        } else {
            if (newClientForm) newClientForm.style.display = 'none';
            if (existingClientForm) existingClientForm.style.display = 'block';
            newClientInputs.forEach(input => input.required = false);
            const clientSel = document.getElementById('clientSelect');
            if (clientSel) clientSel.required = true;
            handleCustomerChange(document.getElementById('clientSelect'));
        }
        calculateTotal();
    }

    if (newClientRadio && existingClientRadio) {
        newClientRadio.addEventListener('change', toggleClientForms);
        existingClientRadio.addEventListener('change', toggleClientForms);
        toggleClientForms();
    } else if (existingClientRadio) {
        existingClientRadio.addEventListener('change', toggleClientForms);
        toggleClientForms();
    }

    // Populate initial stock/lot source based on locked shop warehouse
    populateWarehouseLotSources($('#warehouse_id').val());

    // Initialize payment method
    handleSaleAccountChange(document.getElementById('saleAccountSelector'));
});

function handleSaleAccountChange(selectEl) {
    if (!selectEl) return;
    const selectedOpt = selectEl.options[selectEl.selectedIndex];
    if (!selectedOpt) return;
    const methodInput = document.getElementById('hidden_payment_method');
    const bankInput = document.getElementById('hidden_bank_detail_id');
    const refContainer = document.getElementById('transactionRefContainer');

    const method = selectedOpt.getAttribute('data-method') || 'bank';
    const bankId = selectedOpt.getAttribute('data-id') || '';
    if (methodInput) methodInput.value = method;
    if (bankInput) bankInput.value = bankId;

    if (refContainer) {
        refContainer.style.display = (method === 'cash') ? 'none' : 'block';
    }
}

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
        contactText.innerText = 'Select customer to view previous balance & advance amount';
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
    
    const tenderedInput = document.getElementById('tenderedAmount');
    if (tenderedInput) {
        tenderedInput.value = applyAmt.toFixed(2);
    }
    const advInput = document.getElementById('advancedPayment');
    if (advInput) {
        advInput.value = applyAmt.toFixed(2);
    }
    calculateTotal();
}

const allAvailableCoils = @json($coils);
const allAvailableLots = @json($lots->keyBy('id'));
const shopWarehouseId = {{ (int)$shop->id }};
let currentSelectedLot = null;
let currentSelectedCoil = null;

function populateWarehouseLotSources(selectedWhId) {
    const lotSelect = $('#builder_lot_id');
    const currentLotVal = lotSelect.val();
    lotSelect.empty();

    const whId = selectedWhId || shopWarehouseId;
    const whCoils = allAvailableCoils.filter(c => String(c.warehouse_id) === String(whId) && parseFloat(c.remaining_weight) > 0);

    lotSelect.prop('disabled', false);
    lotSelect.append('<option value="">Select Stock / Lot Source</option>');

    // Direct / Opening Stock optgroup
    const openingCoils = whCoils.filter(c => (c.purchase_id === null || !c.lot_id));
    const openingCount = openingCoils.length;
    const openingWeight = openingCoils.reduce((sum, c) => sum + (parseFloat(c.remaining_weight) || 0), 0);

    const totalCount = whCoils.length;
    const totalWeight = whCoils.reduce((sum, c) => sum + (parseFloat(c.remaining_weight) || 0), 0);

    let directOptgroup = $('<optgroup label="Shop Inventory & Opening Stock"></optgroup>');
    if (openingCount > 0) {
        directOptgroup.append(`
            <option value="opening_stock" data-vendor="Opening Stock" data-lot-number="Opening Stock">
                📦 Opening Stock (${openingCount} coils, ${openingWeight.toLocaleString()} kg)
            </option>
        `);
    }
    if (totalCount > 0) {
        directOptgroup.append(`
            <option value="all_stock" data-vendor="All Inventory" data-lot-number="All Stock">
                🌐 All In-Stock in {{ $shop->name }} (${totalCount} items, ${totalWeight.toLocaleString()} kg)
            </option>
        `);
    }
    if (openingCount > 0 || totalCount > 0) {
        lotSelect.append(directOptgroup);
    }

    // Purchase Lots optgroup
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
        let millOptgroup = $('<optgroup label="Procurement Lots in this Shop"></optgroup>');
        lotIds.forEach(lId => {
            const g = lotGroups[lId];
            const lotObj = g.lot;
            const vendorName = (lotObj.vendor && lotObj.vendor.name) ? lotObj.vendor.name : (lotObj.vendor_name || '');
            const vendorLabel = vendorName ? ` (${vendorName})` : '';
            const text = `${lotObj.lot_number}${vendorLabel} — ${g.coilsCount} ${g.coilsCount === 1 ? 'item' : 'items'} (${g.weight.toLocaleString()} kg)`;
            millOptgroup.append(`
                <option value="${lId}" data-vendor="${escapeHtml(vendorName || 'Lot')}" data-lot-number="${escapeHtml(lotObj.lot_number)}">
                    ${escapeHtml(text)}
                </option>
            `);
        });
        lotSelect.append(millOptgroup);
    }

    if (totalCount === 0) {
        lotSelect.append('<option value="" disabled>No in-stock steel coils found in this shop</option>');
    }

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
    const selectedWhId = $('#warehouse_id').val() || shopWarehouseId;

    if (!lotId || !selectedWhId) {
        if (lotAvgBadge) {
            lotAvgBadge.style.display = 'none';
            lotAvgBadge.innerHTML = '';
        }
        return;
    }

    const whCoils = allAvailableCoils.filter(c => String(c.warehouse_id) === String(selectedWhId) && parseFloat(c.remaining_weight) > 0);

    let filtered = [];
    let lotNumberDisplay = '';
    let vendorDisplay = '';

    if (lotId === 'opening_stock') {
        lotNumberDisplay = 'Opening Stock';
        vendorDisplay = 'Shop Stock';
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

    let totalWeight = 0;
    let totalCost = 0;
    filtered.forEach(c => {
        const w = parseFloat(c.remaining_weight) || 0;
        const rate = parseFloat(c.rate_per_ton) || parseFloat(c.cost_per_kg) || parseFloat(c.rate) || 0;
        totalWeight += w;
        totalCost += (w * rate);
    });

    const avgRate = totalWeight > 0 ? (totalCost / totalWeight).toFixed(2) : '0.00';
    if (lotAvgBadge && totalWeight > 0) {
        lotAvgBadge.style.display = 'inline-block';
        lotAvgBadge.innerHTML = `<i class="fe fe-dollar-sign me-1"></i>Avg Cost: ৳${avgRate}/kg (${totalWeight.toLocaleString()} kg)`;
    }

    currentSelectedLot = {
        id: lotId,
        lot_number: lotNumberDisplay,
        vendor_name: vendorDisplay,
        availableWeight: totalWeight,
        avgRate: avgRate
    };

    // Immediately show Lot Available Stock & Avg Cost Rate in builder fields upon Lot selection
    if (totalWeight > 0) {
        $('#stock1').val(totalWeight.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' kg');
        $('#stock1').data('raw-stock', totalWeight);
        $('#purchase_price1').val(avgRate);
        if (!$('#unit_price1').val() || parseFloat($('#unit_price1').val()) === 0) {
            $('#unit_price1').val(parseFloat(avgRate) > 0 ? avgRate : '');
        }
        $('#qty1').attr('max', totalWeight);
    }

    const productSelect = $('#product1');
    productSelect.empty().append('<option value="">Select In-Stock Item</option>');

    filtered.forEach(c => {
        const coilNo = c.coil_number ? `#${c.coil_number}` : `Coil #${c.id}`;
        const coilTitle = c.product_name ? `${c.product_name} (${coilNo})` : coilNo;
        const thick = c.thickness ? `${c.thickness}` : '';
        const size = c.size ? `${c.size} ${c.size_type || 'ft'}` : '';
        const specs = [thick, size].filter(Boolean).join(' | ');
        const specsLabel = specs ? ` (${specs})` : '';
        const weight = parseFloat(c.remaining_weight).toLocaleString();
        const text = `${coilTitle}${specsLabel} — Available: ${weight} kg`;
        const coilRate = parseFloat(c.rate_per_ton) || parseFloat(c.cost_per_kg) || parseFloat(c.rate) || 0;
        const coilSellingRate = parseFloat(c.selling_rate) || coilRate;

        productSelect.append(`
            <option value="${c.id}" 
                data-stock="${c.remaining_weight}" 
                data-thickness="${c.thickness || ''}"
                data-size="${c.size || ''}"
                data-size-type="${c.size_type || 'ft'}"
                data-purchase-price="${coilRate}"
                data-selling-price="${coilSellingRate}"
                data-lot-id="${c.lot_id || ''}"
                data-lot-number="${escapeHtml(lotNumberDisplay)}"
                data-vendor-name="${escapeHtml(vendorDisplay)}"
                data-coil-number="${escapeHtml(coilNo)}">
                ${escapeHtml(text)}
            </option>
        `);
    });

    if ($.fn.select2) {
        if (productSelect.hasClass('select2-hidden-accessible')) {
            productSelect.select2('destroy');
        }
        productSelect.select2({ width: '100%' });
    }
}

function resetLotFields() {
    $('#stock1').val('');
    $('#purchase_price1').val('');
    $('#unit_price1').val('');
    $('#qty1').val('');
    $('#total1').val('0.00');
    $('#custom_size1').val('');
    currentSelectedCoil = null;

    const productSelect = $('#product1');
    productSelect.empty().append('<option value="">Select Stock Source First</option>');
    if ($.fn.select2 && productSelect.hasClass('select2-hidden-accessible')) {
        productSelect.select2('destroy').select2({ width: '100%' });
    }
}

function getProduct(index) {
    const selected = $(`#product${index} option:selected`);
    if (selected.val()) {
        const stock = parseFloat(selected.data('stock')) || 0;
        const purchasePrice = parseFloat(selected.data('purchase-price')) || 0;
        const sellingPrice = parseFloat(selected.data('selling-price')) || 0;
        const thickness = selected.data('thickness') || '';
        const size = selected.data('size') || '';
        const sizeType = selected.data('size-type') || 'ft';

        $(`#stock${index}`).val(stock.toLocaleString() + ' kg');
        $(`#stock${index}`).data('raw-stock', stock);
        $(`#purchase_price${index}`).val(purchasePrice.toFixed(2));
        $(`#unit_price${index}`).val(sellingPrice.toFixed(2));
        $(`#qty${index}`).attr('max', stock);

        if (thickness || size) {
            $(`#custom_size${index}`).val(`${thickness} ${size} ${sizeType}`.trim());
        }

        currentSelectedCoil = {
            id: selected.val(),
            coil_number: selected.data('coil-number'),
            thickness: thickness,
            size: size,
            size_type: sizeType,
            stock: stock,
            purchase_price: purchasePrice,
            selling_price: sellingPrice,
            lot_id: selected.data('lot-id'),
            lot_number: selected.data('lot-number'),
            vendor_name: selected.data('vendor-name')
        };
    } else {
        if (currentSelectedLot && currentSelectedLot.availableWeight > 0) {
            $(`#stock${index}`).val(currentSelectedLot.availableWeight.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' kg');
            $(`#stock${index}`).data('raw-stock', currentSelectedLot.availableWeight);
            $(`#purchase_price${index}`).val(currentSelectedLot.avgRate);
            $(`#unit_price${index}`).val(parseFloat(currentSelectedLot.avgRate) > 0 ? currentSelectedLot.avgRate : '');
            $(`#qty${index}`).attr('max', currentSelectedLot.availableWeight);
            $(`#custom_size${index}`).val('');
        } else {
            $(`#stock${index}`).val('');
            $(`#stock${index}`).data('raw-stock', 0);
            $(`#purchase_price${index}`).val('');
            $(`#unit_price${index}`).val('');
            $(`#custom_size${index}`).val('');
        }
        currentSelectedCoil = null;
    }
    updatePreviewTotal();
}

function updatePreviewTotal() {
    const price = parseFloat($('#unit_price1').val()) || 0;
    const qty = parseFloat($('#qty1').val()) || 0;
    const total = price * qty;
    $('#total1').val(total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
}

function addItem() {
    const productSelect = $('#product1');
    const coilId = productSelect.val();
    const qty = parseFloat($('#qty1').val()) || 0;
    const unitPrice = parseFloat($('#unit_price1').val()) || 0;
    const rawStock = parseFloat($('#stock1').data('raw-stock')) || 0;
    const purchasePrice = parseFloat($('#purchase_price1').val()) || 0;
    const customSize = $('#custom_size1').val().trim();

    if (!coilId) {
        alert('Please select a steel coil or piece first.');
        productSelect.focus();
        return;
    }

    if (qty <= 0) {
        alert('Please enter a valid selling quantity / weight.');
        $('#qty1').focus();
        return;
    }

    if (qty > rawStock) {
        alert(`Requested quantity (${qty.toLocaleString()} kg) exceeds available stock (${rawStock.toLocaleString()} kg).`);
        $('#qty1').focus();
        return;
    }

    if (unitPrice <= 0) {
        alert('Please enter a valid selling unit price.');
        $('#unit_price1').focus();
        return;
    }

    const coil = currentSelectedCoil || {};
    const lotDisplay = (coil.lot_number && coil.lot_number !== 'Opening Stock' && coil.lot_number !== 'All Stock')
        ? `${coil.lot_number}${coil.vendor_name ? ' (' + coil.vendor_name + ')' : ''}`
        : (coil.lot_number || 'Direct Stock');

    const lotIdVal = (coil.lot_id && coil.lot_id !== 'opening_stock' && coil.lot_id !== 'all_stock') ? coil.lot_id : '';
    const specs = [coil.thickness, coil.size ? `${coil.size} ${coil.size_type || 'ft'}` : ''].filter(Boolean).join(' • ');
    const lineTotal = (qty * unitPrice).toFixed(2);

    const rowId = 'cart_row_' + itemNumber;
    const rowHtml = `
        <tr id="${rowId}">
            <td>
                <div class="fw-bold text-dark">${escapeHtml(coil.coil_number || 'Coil #' + coilId)}</div>
                <div class="small text-muted">${escapeHtml(specs || 'Standard Stock')}</div>
                <input type="hidden" name="coil_id[]" value="${coilId}">
                <input type="hidden" name="lot_id[]" value="${lotIdVal}">
                <input type="hidden" name="purchase_price[]" value="${purchasePrice}">
            </td>
            <td>
                <span class="badge bg-light text-secondary border">${escapeHtml(lotDisplay)}</span>
            </td>
            <td>
                <input type="text" name="custom_size[]" class="form-control form-control-sm border-light-subtle" value="${escapeHtml(customSize)}">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted">৳</span>
                    <input type="number" step="0.01" min="0" name="unit_price[]" class="form-control form-control-sm item-unit-price" 
                        value="${unitPrice.toFixed(2)}" oninput="recalcRow('${rowId}')" onchange="recalcRow('${rowId}')">
                </div>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" min="0.01" max="${rawStock}" name="qty[]" class="form-control form-control-sm item-qty fw-bold" 
                        value="${qty.toFixed(2)}" oninput="recalcRow('${rowId}')" onchange="recalcRow('${rowId}')">
                    <span class="input-group-text bg-light text-muted">kg</span>
                </div>
            </td>
            <td>
                <span class="fw-bold text-dark item-line-total-display">৳ ${parseFloat(lineTotal).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                <input type="hidden" name="total_price[]" class="item-line-total" value="${lineTotal}">
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 rounded-2" onclick="removeItem('${rowId}')">
                    <i class="fe fe-trash-2"></i>
                </button>
            </td>
        </tr>
    `;

    $('#item_container').append(rowHtml);
    itemNumber++;

    // Reset builder form
    $('#qty1').val('');
    $('#total1').val('0.00');
    $('#product1').val('').trigger('change');

    calculateTotal();
}

function recalcRow(rowId) {
    const row = $('#' + rowId);
    const price = parseFloat(row.find('.item-unit-price').val()) || 0;
    const qty = parseFloat(row.find('.item-qty').val()) || 0;
    const total = (price * qty).toFixed(2);
    row.find('.item-line-total').val(total);
    row.find('.item-line-total-display').text('৳ ' + parseFloat(total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    calculateTotal();
}

function removeItem(rowId) {
    $('#' + rowId).remove();
    calculateTotal();
}

function calculateTotal() {
    let subtotal = 0;
    $('.item-line-total').each(function () {
        subtotal += parseFloat($(this).val()) || 0;
    });

    const rowCount = $('#item_container tr').length;
    if (rowCount > 0) {
        $('#summerySection').removeClass('d-none');
    } else {
        $('#summerySection').addClass('d-none');
    }

    $('#subTotal').val(subtotal.toFixed(2));

    const discount = parseFloat($('#discount').val()) || 0;
    const vatPercent = parseFloat($('#vat').val()) || 0;
    const delivery = parseFloat($('#delivery_charge').val()) || 0;
    const labour = parseFloat($('#labour_cost').val()) || 0;
    const scale = parseFloat($('#weight_scale_cost').val()) || 0;
    const other = parseFloat($('#other_charges').val()) || 0;

    const afterDiscount = Math.max(0, subtotal - discount);
    const vatAmount = (afterDiscount * vatPercent) / 100;
    const grandTotal = afterDiscount + vatAmount + delivery + labour + scale + other;

    $('#grandTotal').val(grandTotal.toFixed(2));
    $('#grandTotalDisplay').text('৳ ' + grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));

    calculateTenderAndChange(grandTotal);

    const prevDue = parseFloat(window.selectedCustomerPreviousDue) || 0;
    $('#previousDueDisplay').text('৳ ' + prevDue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
}

function handleTenderedInput() {
    const grandTotal = parseFloat(document.getElementById('grandTotal')?.value) || 0;
    calculateTenderAndChange(grandTotal);
}

function calculateTenderAndChange(grandTotal) {
    const tenderedInput = document.getElementById('tenderedAmount');
    const advancedInput = document.getElementById('advancedPayment');
    const changeDisplay = document.getElementById('changeReturnDisplay');
    const changeCard = document.getElementById('changeCardWrapper');
    const appliedDisplay = document.getElementById('displayAppliedPayment');
    const currentDueDisplay = document.getElementById('currentDueDisplay');
    const duePaymentInput = document.getElementById('duePayment');
    const changeReturnInput = document.getElementById('changeReturnAmount');

    const tenderedStr = tenderedInput ? tenderedInput.value.trim() : '';
    const tendered = parseFloat(tenderedStr) || 0;

    let appliedPayment = 0;
    let changeReturn = 0;
    let currentDue = grandTotal;

    if (tenderedStr !== '') {
        if (tendered >= grandTotal && grandTotal > 0) {
            appliedPayment = grandTotal;
            changeReturn = tendered - grandTotal;
            currentDue = 0;
        } else {
            appliedPayment = Math.min(tendered, grandTotal);
            changeReturn = 0;
            currentDue = Math.max(0, grandTotal - appliedPayment);
        }
    } else {
        appliedPayment = Math.min(parseFloat(advancedInput?.value) || 0, grandTotal);
        currentDue = Math.max(0, grandTotal - appliedPayment);
    }

    if (advancedInput) {
        advancedInput.value = appliedPayment.toFixed(2);
    }
    if (changeReturnInput) {
        changeReturnInput.value = changeReturn.toFixed(2);
    }
    if (duePaymentInput) {
        duePaymentInput.value = currentDue.toFixed(2);
    }
    if (appliedDisplay) {
        appliedDisplay.innerText = '৳ ' + appliedPayment.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    if (currentDueDisplay) {
        currentDueDisplay.innerText = '৳ ' + currentDue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    if (changeDisplay) {
        changeDisplay.innerText = '৳ ' + changeReturn.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    if (changeCard) {
        if (changeReturn > 0) {
            changeCard.style.backgroundColor = '#dcfce7';
            changeCard.style.borderColor = '#86efac';
        } else {
            changeCard.style.backgroundColor = '#f8fafc';
            changeCard.style.borderColor = '#e2e8f0';
        }
    }
}

function setExactTendered() {
    const grandTotal = parseFloat(document.getElementById('grandTotal')?.value) || 0;
    const tenderedInput = document.getElementById('tenderedAmount');
    if (tenderedInput) {
        tenderedInput.value = grandTotal.toFixed(2);
    }
    calculateTenderAndChange(grandTotal);
}

function validateSaleFormSubmission(event) {
    const rowCount = $('#item_container tr').length;
    if (rowCount === 0) {
        event.preventDefault();
        alert('Please add at least one steel coil or piece to the invoice.');
        return false;
    }

    const isNew = document.getElementById('newClient').checked;
    if (isNew) {
        const name = document.getElementById('newClientName').value.trim();
        const phone = document.getElementById('newClientPhone').value.trim();
        const address = document.getElementById('newClientAddress').value.trim();
        if (!name || !phone || !address) {
            event.preventDefault();
            alert('Please fill in customer name, phone number, and address.');
            return false;
        }
    } else {
        const custId = document.getElementById('clientSelect').value;
        if (!custId) {
            event.preventDefault();
            alert('Please select an existing customer.');
            return false;
        }
    }

    const grandTotal = parseFloat($('#grandTotal').val()) || 0;
    const paid = parseFloat($('#advancedPayment').val()) || 0;
    if (paid > (grandTotal + 0.01)) {
        event.preventDefault();
        alert(`Current payment (৳${paid.toFixed(2)}) cannot exceed the invoice grand total (৳${grandTotal.toFixed(2)}).`);
        $('#advancedPayment').focus();
        return false;
    }

    setTimeout(function () {
        window.location.href = "{{ route('shops.sales.index') }}";
    }, 600);

    return true;
}
</script>
@endpush
