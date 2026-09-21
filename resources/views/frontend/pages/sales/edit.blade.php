@extends('frontend.layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 6px !important;
        display: flex !important;
        align-items: center !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        top: 6px !important;
    }
    .select2-dropdown {
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        z-index: 9999 !important;
    }
    .select2-results__option--highlighted[aria-selected] {
        background-color: #3b82f6 !important;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Edit Sale Order #{{ $sales->order_no }}</h4>
                <p class="text-muted small mb-0">Update customer information, add or adjust steel line items, and manage charges</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('sales.show', $sales->id) }}" class="btn btn-outline-primary px-3 py-2 rounded-3 shadow-sm">
                    <i class="fe fe-eye me-1"></i>View Details
                </a>
                <a href="{{ route('sales.invoice.pdf', $sales->id) }}" target="_blank" class="btn btn-outline-info px-3 py-2 rounded-3 shadow-sm">
                    <i class="fe fe-download me-1"></i>Invoice PDF
                </a>
                <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 shadow-sm">
                    <i class="fe fe-arrow-left me-1"></i>Back to Sales
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <form action="{{ route('sales.update', $sales->id) }}" method="POST" id="saleForm">
        @csrf
        @method('PUT')

        <!-- Section 1: Customer & Dispatch Details -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center mb-3">
                    <div class="col-md-6 col-12">
                        <h6 class="fw-bold text-dark mb-0"><i class="fe fe-user me-2 text-primary"></i>Customer & Dispatch Information</h6>
                    </div>
                    <div class="col-md-6 col-12 text-md-end mt-2 mt-md-0">
                        @php
                            $isExisting = !empty($sales->customer_id) && $existingClients->contains('id', $sales->customer_id);
                        @endphp
                        <div class="d-inline-flex align-items-center gap-3 bg-light px-3 py-1 rounded-3 border">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="client_type" id="existingClient" value="existing" {{ $isExisting ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold text-dark fs-7" for="existingClient">Existing Customer</label>
                            </div>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="client_type" id="newClient" value="new" {{ !$isExisting ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold text-dark fs-7" for="newClient">New Customer</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Client Form -->
                <div id="newClientForm" style="{{ $isExisting ? 'display: none;' : 'display: block;' }}">
                    <div class="row g-3">
                        <div class="col-lg-4 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control border-light-subtle" id="newClientName" value="{{ old('name', $customer->name ?? '') }}" autocomplete="off">
                        </div>
                        <div class="col-lg-4 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control border-light-subtle" id="newClientPhone" value="{{ old('phone', $customer->phone ?? '') }}" autocomplete="off">
                        </div>
                        <div class="col-lg-4 col-md-12 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Address <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control border-light-subtle" id="newClientAddress" value="{{ old('address', $customer->address ?? '') }}" autocomplete="off">
                        </div>
                    </div>
                </div>

                <!-- Existing Client Form -->
                <div id="existingClientForm" style="{{ $isExisting ? 'display: block;' : 'display: none;' }}">
                    <div class="row g-3 align-items-stretch">
                        <div class="col-lg-6 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Select Existing Customer <span class="text-danger">*</span></label>
                            <select name="existing_client_id" class="form-select select2 border-light-subtle" id="clientSelect" onchange="handleCustomerChange(this)">
                                <option value="">Select Customer</option>
                                @foreach ($existingClients as $client)
                                    @php
                                        $openingDue = (float)($client->opening_balance ?? 0);
                                        $salesDue = (float)($client->sales_sum_due_payment ?? 0);
                                        $totalDue = $openingDue + $salesDue;
                                    @endphp
                                    <option value="{{ $client->id }}" 
                                        {{ old('existing_client_id', $sales->customer_id) == $client->id ? 'selected' : '' }}
                                        data-name="{{ $client->name }}"
                                        data-phone="{{ $client->phone }}" 
                                        data-address="{{ $client->address }}" 
                                        data-opening-due="{{ $openingDue }}"
                                        data-sales-due="{{ $salesDue }}"
                                        data-previous-due="{{ $totalDue }}">
                                        {{ $client->name }} — {{ $client->phone }} {{ $totalDue > 0 ? '(Due: ৳'.number_format($totalDue, 2).')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Customer Profile & Due Widget -->
                        <div class="col-lg-6 col-md-6 col-12">
                            <div id="customerBalanceCard" class="p-3 bg-light rounded-3 border h-100 d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div>
                                    <div class="fw-semibold text-dark mb-0" id="custNameText">{{ $customer->name ?? 'No Customer Selected' }}</div>
                                    <div class="small text-muted" id="custContactText" style="font-size: 11px;">
                                        Phone: {{ $customer->phone ?? 'N/A' }} | Addr: {{ $customer->address ?? 'N/A' }}
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 text-end flex-wrap">
                                    @php
                                        $custOpening = (float)($customer->opening_balance ?? 0);
                                        $custSalesDue = (float)($customer->sales_sum_due_payment ?? 0);
                                        $custTotalDue = $custOpening + $custSalesDue;
                                    @endphp
                                    <div class="bg-white px-2 py-1 rounded border">
                                        <span class="text-muted d-block" style="font-size: 9px; text-transform: uppercase;">Opening Due</span>
                                        <span id="custOpeningDueBadge" class="fw-bold text-warning fs-7">৳ {{ number_format($custOpening, 2) }}</span>
                                    </div>
                                    <div class="bg-white px-2 py-1 rounded border">
                                        <span class="text-muted d-block" style="font-size: 9px; text-transform: uppercase;">Sales Due</span>
                                        <span id="custSalesDueBadge" class="fw-bold text-info fs-7">৳ {{ number_format($custSalesDue, 2) }}</span>
                                    </div>
                                    <div class="bg-white px-2 py-1 rounded border">
                                        <span class="text-muted d-block" style="font-size: 9px; text-transform: uppercase;">Total Prev Due</span>
                                        <span id="custBalanceBadge" class="fw-bold {{ $custTotalDue > 0 ? 'text-danger' : 'text-success' }} fs-7">
                                            ৳ {{ number_format($custTotalDue, 2) }}
                                        </span>
                                    </div>
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
                                <option value="{{ $wh->id }}" {{ old('warehouse_id', $sales->warehouse_id) == $wh->id ? 'selected' : '' }}>
                                    {{ $wh->name }} {{ $wh->code ? '('.$wh->code.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Delivery Status <span class="text-danger">*</span></label>
                        <select name="delivery_status" id="delivery_status" class="form-select border-light-subtle" required>
                            <option value="pending" {{ old('delivery_status', $sales->delivery_status) == 'pending' ? 'selected' : '' }}>Pending (Order Received)</option>
                            <option value="dispatched" {{ old('delivery_status', $sales->delivery_status) == 'dispatched' ? 'selected' : '' }}>Dispatched (In Transit / Truck Loaded)</option>
                            <option value="delivered" {{ old('delivery_status', $sales->delivery_status) == 'delivered' ? 'selected' : '' }}>Delivered (Received at Site)</option>
                            <option value="partial_delivered" {{ old('delivery_status', $sales->delivery_status) == 'partial_delivered' ? 'selected' : '' }}>Partial Delivered</option>
                        </select>
                    </div>

                    <div class="col-lg-6 col-md-12 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Dispatch Note / Truck & Transport Details</label>
                        <input type="text" name="note" id="note" class="form-control border-light-subtle" value="{{ old('note', $sales->note) }}" placeholder="Vehicle number, driver contact, delivery instructions...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Items & Lot Selection Builder -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="mb-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="fe fe-shopping-cart me-2 text-primary"></i>Steel Items & In-Stock Coils Selection</h6>
                    <small class="text-muted">Add new coils to this sale or adjust existing lines below</small>
                </div>

                <!-- Product Add Builder Card -->
                <div class="p-3 bg-light rounded-3 mb-4 border" id="form-group-item1">
                    <div class="row g-3 align-items-end">
                        <!-- 1. Stock / Lot Source Select Dropdown -->
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">
                                1. Select Stock / Lot Source
                            </label>
                            <select id="builder_lot_id" class="form-select select2 border-light-subtle" onchange="handleLotSelection(this.value)">
                                <option value="">Select Stock / Lot Source</option>
                                <optgroup label="Direct / Opening Warehouse Stock">
                                    <option value="opening_stock" data-vendor="Direct Yard Stock" data-lot-number="Opening Stock">
                                        📦 Opening Stock / Direct Inventory
                                    </option>
                                    <option value="all_stock" data-vendor="All Inventory" data-lot-number="All Stock">
                                        🌐 All In-Stock Coils (All Sources)
                                    </option>
                                </optgroup>
                                @if($lots->isNotEmpty())
                                    <optgroup label="Purchase Mill Lots">
                                        @foreach ($lots as $lot)
                                            <option value="{{ $lot->id }}" 
                                                data-vendor="{{ $lot->vendor ? $lot->vendor->name : 'No Vendor' }}"
                                                data-lot-number="{{ $lot->lot_number }}">
                                                {{ $lot->lot_number }} {{ $lot->vendor ? '('.$lot->vendor->name.')' : '' }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                        </div>

                        <!-- 2. Coil Select Dropdown -->
                        <div class="col-lg-3 col-md-6 col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small text-secondary fw-semibold mb-0">
                                    2. Select In-Stock Coil
                                </label>
                                <span id="lotAvgRateBadge" class="badge bg-primary-subtle text-primary border" style="display:none; font-size: 11px;"></span>
                            </div>
                            <select onchange="selectCoil(this)" id="coil_select" class="form-select select2 border-light-subtle" disabled>
                                <option value="">Select Stock Source first</option>
                            </select>
                        </div>

                        <!-- 3. Per Coil Weight -->
                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Per Coil Wt (kg)</label>
                            <input type="text" id="per_coil_weight1" class="form-control border-light-subtle bg-white fw-bold text-dark" readonly>
                        </div>

                        <!-- 4. Available Coil Weight -->
                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Available Weight</label>
                            <input type="text" id="stock1" class="form-control border-light-subtle bg-white fw-bold text-primary" readonly>
                        </div>

                        <!-- 5. Cost Rate -->
                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">Cost Rate (৳)</label>
                            <input type="number" id="purchase_price1" class="form-control border-light-subtle bg-white" readonly>
                        </div>

                        <!-- 6. Custom Size (Admin Only) -->
                        <div class="col-lg-3 col-md-6 col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small text-secondary fw-semibold mb-0">Custom Size</label>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 9px;"><i class="fe fe-lock me-1"></i>Admin Only</span>
                            </div>
                            <input type="text" id="custom_size1" class="form-control border-light-subtle" placeholder="e.g. 4x8 ft cut (Internal note)">
                        </div>

                        <!-- 7. Selling Rate -->
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Selling Rate (৳)</label>
                            <input oninput="updatePreviewTotal()" onchange="updatePreviewTotal()" type="number" id="unit_price1" class="form-control border-light-subtle" step="0.01" min="0">
                        </div>

                        <!-- 8. Selling Quantity -->
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Selling Qty / Wt (kg)</label>
                            <input oninput="updatePreviewTotal()" onchange="updatePreviewTotal()" type="number" id="qty1" class="form-control border-light-subtle text-dark" step="0.01" min="0.01">
                        </div>

                        <!-- 9. Line Total Preview -->
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">Line Total (৳)</label>
                            <input type="text" id="total1" class="form-control border-light-subtle bg-white fw-bold text-success" readonly value="0.00">
                        </div>

                        <!-- 10. Add Item Button -->
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
                            @foreach ($items as $index => $item)
                                @php
                                    $coil = $item->coil ?? $item->product;
                                    $coilId = $item->coil_id ?? ($coil->id ?? '');
                                    $coilNumber = $coil ? $coil->coil_number : ($item->name ?? 'Steel Coil');
                                    $thickness = $item->thickness ?: ($coil ? $coil->thickness : '');
                                    $size = $item->size ?: ($coil ? $coil->width : '');
                                    $sizeType = $item->size_type ?: ($coil ? ($coil->length ?: $coil->size_type) : 'ft');
                                    $itemNumber = $index + 1;
                                    
                                    // Effective available stock for this coil
                                    $availStock = $coil ? ((float)$coil->remaining_weight + (float)$item->qty) : (float)$item->qty;
                                @endphp
                                <tr class="item-coil-{{ $coilId }} group-item" data-itemnumber="{{ $itemNumber }}" id="form-group-item{{ $itemNumber }}">
                                    <td>
                                        <input type="hidden" name="coil_id[]" value="{{ $coilId }}">
                                        <input type="hidden" name="lot_id[]" value="{{ $item->lot_id }}">
                                        <input type="hidden" name="thickness[]" value="{{ $thickness }}">
                                        <input type="hidden" name="size[]" value="{{ $size }}">
                                        <input type="hidden" name="size_type[]" value="{{ $sizeType }}">
                                        <span class="fw-bold text-dark d-block">Coil No - {{ $coilNumber }}</span>
                                        <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                            @if($coil && ($coil->purchase_id === null || !$coil->lot_id))
                                                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 fs-8">Opening Stock</span>
                                            @endif
                                            @if($thickness)
                                                <span class="badge bg-light text-dark border px-2 py-1 fs-8">Thickness: {{ $thickness }}</span>
                                            @endif
                                            @if($size)
                                                <span class="badge bg-light text-secondary border px-2 py-1 fs-8">Size: {{ $size }} {{ $sizeType }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($item->lot)
                                            <span class="badge bg-light text-dark border px-2 py-1 fs-8"><i class="fe fe-package text-primary me-1"></i>{{ $item->lot->lot_number }}</span>
                                        @elseif($coil && $coil->lot)
                                            <span class="badge bg-light text-dark border px-2 py-1 fs-8"><i class="fe fe-package text-primary me-1"></i>{{ $coil->lot->lot_number }}</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 fs-8"><i class="fe fe-archive me-1"></i>Opening Stock</span>
                                        @endif
                                    </td>
                                    <td>
                                        <input type="text" name="custom_size[]" class="form-control form-control-sm border-light-subtle" value="{{ $item->custom_size }}" placeholder="Admin custom size...">
                                    </td>
                                    <td>
                                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" step="0.01" name="unit_price[]" id="unit_price{{ $itemNumber }}" class="form-control border-light-subtle unit-price" value="{{ number_format((float)$item->unit_price, 2, '.', '') }}">
                                    </td>
                                    <td>
                                        <input oninput="validateRowQty(this); calculateTotal()" onchange="validateRowQty(this); calculateTotal()" type="number" step="0.01" name="qty[]" id="qty{{ $itemNumber }}" class="form-control border-light-subtle qty fw-bold" min="0.01" max="{{ $availStock }}" data-available="{{ $availStock }}" data-coil-id="{{ $coilId }}" data-coil-number="{{ $coilNumber }}" value="{{ number_format((float)$item->qty, 2, '.', '') }}">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="total" id="total{{ $itemNumber }}" class="form-control border-light-subtle bg-light total fw-bold text-dark" readonly value="{{ number_format((float)$item->total_price, 2, '.', '') }}">
                                    </td>
                                    <td class="text-end">
                                        <button onclick="removeItem({{ $itemNumber }})" type="button" class="btn btn-outline-danger btn-sm px-3 rounded-2" title="Remove Item">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Section 3: Summary Breakdown & Financials -->
        <div id="summerySection" class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3"><i class="fe fe-dollar-sign me-2 text-primary"></i>Operational Charges & Financial Breakdown</h6>

                <!-- Charges & Adjustments Row -->
                <div class="row g-3 align-items-end mb-4">
                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Sub Total (৳)</label>
                        <input onchange="calculateTotal()" type="number" id="subTotal" name="subTotal" class="form-control border-light-subtle bg-light" readonly value="{{ number_format((float)$sales->bill, 2, '.', '') }}">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Discount Amount (৳)</label>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="discount" name="discount" class="form-control border-light-subtle" value="{{ number_format((float)$sales->discount, 2, '.', '') }}" min="0" step="0.01">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">VAT (%)</label>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="vat" name="vat" class="form-control border-light-subtle" value="{{ number_format((float)$sales->vat, 2, '.', '') }}" min="0" step="0.01">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Delivery / Transport (৳)</label>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="delivery_charge" name="delivery_charge" class="form-control border-light-subtle" value="{{ number_format((float)$sales->delivery_charge, 2, '.', '') }}" min="0" step="0.01">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Cutting & Labour Load-Unload (৳)</label>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="labour_cost" name="labour_cost" class="form-control border-light-subtle" value="{{ number_format((float)$sales->labour_cost, 2, '.', '') }}" min="0" step="0.01">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Scale & Labour Charge (৳)</label>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="weight_scale_cost" name="weight_scale_cost" class="form-control border-light-subtle" value="{{ number_format((float)$sales->weight_scale_cost, 2, '.', '') }}" min="0" step="0.01">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Other Charges (৳)</label>
                        <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" id="other_charges" name="other_charges" class="form-control border-light-subtle" value="{{ number_format((float)$sales->other_charges, 2, '.', '') }}" min="0" step="0.01">
                    </div>

                    <input type="hidden" id="grandTotal" name="grandTotal" value="{{ number_format((float)$sales->payble, 2, '.', '') }}">
                    <input type="hidden" id="duePayment" name="duePayment" value="{{ number_format((float)$sales->due_payment, 2, '.', '') }}">
                </div>

                <!-- Minimal Financial Metric Cards Row -->
                <div class="row g-3 mb-3">
                    <!-- Grand Total -->
                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <span class="text-muted small fw-medium d-block mb-1">Invoice Grand Total</span>
                            <h4 class="mb-0 fw-bold text-dark" id="grandTotalDisplay">৳ {{ number_format((float)$sales->payble, 2) }}</h4>
                        </div>
                    </div>

                    <!-- Payment Received Input -->
                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <label class="form-label small text-muted fw-medium mb-1">Current Payment (৳) <span class="text-danger">*</span></label>
                            <input oninput="calculateTotal()" onchange="calculateTotal()" type="number" name="advanced_payment" id="advancedPayment" 
                                class="form-control border-light-subtle fw-bold text-dark bg-white" value="{{ number_format((float)$sales->advanced_payment, 2, '.', '') }}" min="0" step="0.01">
                        </div>
                    </div>

                    <!-- Current Invoice Due -->
                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <span class="text-muted small fw-medium d-block mb-1">Current Invoice Due</span>
                            <h4 class="mb-0 fw-bold text-danger" id="currentDueDisplay">৳ {{ number_format((float)$sales->due_payment, 2) }}</h4>
                        </div>
                    </div>

                    <!-- Customer Previous Due -->
                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <span class="text-muted small fw-medium d-block mb-1">Customer Previous Due</span>
                            <h4 class="mb-0 fw-bold text-secondary" id="previousDueDisplay">৳ {{ number_format($custTotalDue ?? 0, 2) }}</h4>
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
                                <option value="cash" {{ ($sales->payment_method ?? 'cash') === 'cash' ? 'selected' : '' }}>Cash in Hand</option>
                                <option value="bank" {{ ($sales->payment_method ?? '') === 'bank' ? 'selected' : '' }}>Bank Transfer / Deposit</option>
                                <option value="mobile_banking" {{ ($sales->payment_method ?? '') === 'mobile_banking' ? 'selected' : '' }}>Mobile Banking (bKash/Nagad)</option>
                            </select>
                        </div>

                        <!-- Bank Account Selector -->
                        <div class="col-lg-4 col-md-6 col-12" id="bankAccountContainer" style="{{ ($sales->payment_method ?? 'cash') === 'cash' ? 'display: none;' : '' }}">
                            <label class="form-label small text-secondary fw-semibold mb-1">
                                <i class="fe fe-layers me-1 text-info"></i> Deposit Bank Account <span class="text-danger">*</span>
                            </label>
                            <select name="bank_detail_id" id="bankDetailSelect" class="form-select border-light-subtle">
                                <option value="">Select Bank Account</option>
                                @foreach($bankAccounts ?? [] as $bank)
                                    <option value="{{ $bank->id }}" {{ ($sales->bank_detail_id == $bank->id || ($sales->bank_detail_id == null && $bank->is_default)) ? 'selected' : '' }}>
                                        {{ $bank->bank_name }} - {{ $bank->account_name }} ({{ $bank->account_number }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Transaction Reference / Notes -->
                        <div class="col-lg-4 col-md-6 col-12" id="transactionRefContainer" style="{{ ($sales->payment_method ?? 'cash') === 'cash' ? 'display: none;' : '' }}">
                            <label class="form-label small text-secondary fw-semibold mb-1">
                                <i class="fe fe-file-text me-1 text-secondary"></i> Transaction Ref / TrxID
                            </label>
                            <input type="text" name="transaction_ref" id="transactionRefInput" class="form-control border-light-subtle bg-white" value="{{ $sales->transaction_ref }}">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 pt-2 border-top">
                    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3">Cancel</a>
                    <button type="submit" class="btn btn-primary px-5 py-2 rounded-3 fw-semibold shadow-sm">
                        Update Sale Order
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
var itemNumber = {{ $items->count() + 10 }};
window.selectedCustomerPreviousDue = {{ $custTotalDue ?? 0 }};
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
    }
});

function handleCustomerChange(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    if (selectedOption && selectedOption.value) {
        const openingDue = parseFloat(selectedOption.dataset.openingDue) || 0;
        const salesDue = parseFloat(selectedOption.dataset.salesDue) || 0;
        const totalDue = parseFloat(selectedOption.dataset.previousDue) || (openingDue + salesDue);
        const name = selectedOption.dataset.name || selectedOption.text.split('—')[0].trim();
        const phone = selectedOption.dataset.phone || 'N/A';
        const address = selectedOption.dataset.address || 'N/A';
        window.selectedCustomerPreviousDue = totalDue;
        window.selectedCustomerOpeningDue = openingDue;
        window.selectedCustomerSalesDue = salesDue;
        updateCustomerBalanceCard(totalDue, name, `Phone: ${phone} | Addr: ${address}`, openingDue, salesDue);
    } else {
        window.selectedCustomerPreviousDue = 0;
        window.selectedCustomerOpeningDue = 0;
        window.selectedCustomerSalesDue = 0;
        updateCustomerBalanceCard(0, null, null, 0, 0);
    }
    calculateTotal();
}

function updateCustomerBalanceCard(totalDue, name, details, openingDue = 0, salesDue = 0) {
    const nameText = document.getElementById('custNameText');
    const contactText = document.getElementById('custContactText');
    const totalBadge = document.getElementById('custBalanceBadge');
    const openingBadge = document.getElementById('custOpeningDueBadge');
    const salesBadge = document.getElementById('custSalesDueBadge');
    const prevDueDisplay = document.getElementById('previousDueDisplay');

    if (!nameText || !totalBadge) return;

    if (!name) {
        nameText.innerText = 'No Customer Selected';
        contactText.innerText = 'Select customer to view previous balance';
        totalBadge.innerText = '৳ 0.00';
        totalBadge.className = 'fw-bold text-secondary fs-7';
        if (openingBadge) openingBadge.innerText = '৳ 0.00';
        if (salesBadge) salesBadge.innerText = '৳ 0.00';
        if (prevDueDisplay) prevDueDisplay.innerText = '৳ 0.00';
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

    if (totalDue > 0) {
        totalBadge.className = 'fw-bold text-danger fs-7';
        totalBadge.innerText = '৳ ' + totalDue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    } else {
        totalBadge.className = 'fw-bold text-success fs-7';
        totalBadge.innerText = '৳ 0.00 (Clear)';
    }

    if (prevDueDisplay) {
        prevDueDisplay.innerText = '৳ ' + totalDue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
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

    const lotAvgBadge = document.getElementById('lotAvgRateBadge');

    if (!lotId) {
        if (lotAvgBadge) {
            lotAvgBadge.style.display = 'none';
            lotAvgBadge.innerHTML = '';
        }
        coilSelect.append('<option value="">Select Stock Source first</option>');
        coilSelect.prop('disabled', true);
        coilSelect.trigger('change');
        return;
    }

    let filtered = [];

    if (lotId === 'opening_stock') {
        currentSelectedLot = {
            id: '',
            lot_number: 'Opening Stock',
            vendor: 'Direct Yard Stock'
        };
        filtered = allAvailableCoils.filter(c => (c.purchase_id === null || !c.lot_id) && parseFloat(c.remaining_weight) > 0);
    } else if (lotId === 'all_stock') {
        currentSelectedLot = {
            id: '',
            lot_number: 'All Stock',
            vendor: 'All Inventory'
        };
        filtered = allAvailableCoils.filter(c => parseFloat(c.remaining_weight) > 0);
    } else {
        const lotOption = $(`#builder_lot_id option[value="${lotId}"]`);
        if (lotOption.length) {
            currentSelectedLot = {
                id: lotId,
                lot_number: lotOption.data('lot-number') || lotOption.text().trim(),
                vendor: lotOption.data('vendor') || ''
            };
        }
        filtered = allAvailableCoils.filter(c => String(c.lot_id) === String(lotId) && parseFloat(c.remaining_weight) > 0);
    }

    if (filtered.length === 0) {
        if (lotAvgBadge) {
            lotAvgBadge.style.display = 'none';
            lotAvgBadge.innerHTML = '';
        }
        const msg = lotId === 'opening_stock' ? 'No opening stock coils available' : 'No in-stock coils in this selection';
        coilSelect.append(`<option value="">${msg}</option>`);
        coilSelect.prop('disabled', true);
        coilSelect.trigger('change');
        return;
    }

    // Calculate Lot Weighted Average Cost Price based on stock coil amount / kg
    const totalLotWeight = filtered.reduce((sum, c) => sum + (parseFloat(c.remaining_weight) || 0), 0);
    const totalLotCost = filtered.reduce((sum, c) => sum + ((parseFloat(c.remaining_weight) || 0) * (parseFloat(c.rate_per_ton) || 0)), 0);
    const avgLotRate = totalLotWeight > 0 ? (totalLotCost / totalLotWeight) : 0;

    if (lotAvgBadge) {
        if (avgLotRate > 0) {
            lotAvgBadge.style.display = 'inline-block';
            lotAvgBadge.innerHTML = `Avg Cost: ৳${avgLotRate.toFixed(2)}/kg`;
            lotAvgBadge.title = `Combined Weighted Avg Cost of all ${filtered.length} in-stock coils in this Lot (Total Stock: ${totalLotWeight.toLocaleString()} kg)`;
        } else {
            lotAvgBadge.style.display = 'none';
        }
    }

    coilSelect.prop('disabled', false);
    const defaultOptionText = avgLotRate > 0 
        ? `Lot Avg: ৳${avgLotRate.toFixed(2)}/kg`
        : 'Choose In-Stock Coil / Plate';
    coilSelect.append(`<option value="">${defaultOptionText}</option>`);

    filtered.forEach(coil => {
        const remaining = parseFloat(coil.remaining_weight) || 0;
        const thickness = coil.thickness ? ` | Thk: ${coil.thickness}` : '';
        const sizeVal = coil.width || coil.size || '';
        const sizeUnit = (coil.length && coil.length !== 'N/A') ? coil.length : (coil.size_type || '');
        const sizeText = sizeVal ? ` | Size: ${sizeVal}${sizeUnit ? ' ' + sizeUnit : ''}` : '';
        const yard = coil.warehouse ? ` (${coil.warehouse.name})` : '';
        const isOpening = (coil.purchase_id === null || !coil.lot_id);
        const sourceTag = isOpening ? ' [Opening Stock]' : (coil.lot ? ` [Lot: ${coil.lot.lot_number}]` : '');
        const pieceCount = coil.piece_count ? Number(coil.piece_count) : 1;
        const grossWeight = parseFloat(coil.gross_weight || coil.net_weight || 0);
        const unitWeight = pieceCount > 0 && grossWeight > 0 ? (grossWeight / pieceCount) : remaining;
        const remainingCoils = unitWeight > 0 ? (remaining / unitWeight) : (pieceCount > 0 ? pieceCount : 1);
        const formattedRemCoils = (Math.round(remainingCoils * 100) / 100).toFixed(remainingCoils % 1 === 0 ? 0 : (remainingCoils * 10 % 1 === 0 ? 1 : 2));
        const remainingPct = grossWeight > 0 ? Math.min(100, Math.max(0, (remaining / grossWeight) * 100)).toFixed(1) : '100.0';
        const text = `${coil.coil_number}${sourceTag} | Stock: ${formattedRemCoils}/${pieceCount} Coils (${remainingPct}%) ${thickness}${sizeText}${yard} | Avail: ${remaining.toLocaleString()} kg`;

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
            .attr('data-is-opening', isOpening ? '1' : '0')
            .attr('data-lot-id', coil.lot_id || '')
            .attr('data-lot-number', coil.lot ? coil.lot.lot_number : (isOpening ? 'Opening Stock' : (currentSelectedLot ? currentSelectedLot.lot_number : '')))
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
    
    const customSizeEl = document.getElementById('custom_size1');
    if (customSizeEl) customSizeEl.value = '';
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
    const lotId = selectedOption.dataset.lotId || '';
    const isOpening = selectedOption.dataset.isOpening === '1';

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
        is_opening: isOpening,
        lot_id: lotId,
        lot_number: selectedOption.dataset.lotNumber,
        warehouse: selectedOption.dataset.warehouse
    };

    const perCoilEl = document.getElementById('per_coil_weight1');
    if (perCoilEl) {
        perCoilEl.value = unitWeight > 0 ? (unitWeight.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' kg') : '—';
    }
    document.getElementById('stock1').value = remaining.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ` kg`;
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

function validateRowQty(input) {
    const max = parseFloat(input.dataset.available) || 999999999;
    const val = parseFloat(input.value) || 0;
    if (val > max) {
        alert(`Entered quantity (${val.toLocaleString()} kg) exceeds available coil capacity (${max.toLocaleString()} kg)!`);
        input.value = max;
    }
}

function addItem() {
    if (!currentSelectedLot) {
        alert('Please select a Stock / Lot Source first.');
        $('#builder_lot_id').focus();
        return;
    }

    if (!currentSelectedCoil) {
        alert('Please select an In-Stock Coil from the selected source.');
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

    const customSize = (document.getElementById('custom_size1')?.value || '').trim();

    const coil = currentSelectedCoil;
    const coilId = coil.id;
    const thickness = coil.thickness || '';
    const size = coil.size || '';
    const sizeType = coil.size_type || 'ft';
    const rowTotal = (qty * unitPrice).toFixed(2);

    const lot = currentSelectedLot;
    const lotId = coil.lot_id || (lot && lot.id && lot.id !== 'opening_stock' && lot.id !== 'all_stock' ? lot.id : '');
    
    let lotLabel = '';
    if (coil.is_opening || (!lotId && coil.lot_number === 'Opening Stock') || (lot && lot.lot_number === 'Opening Stock' && !lotId)) {
        lotLabel = `<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 fs-8"><i class="fe fe-archive me-1"></i>Opening Stock</span>`;
    } else if (coil.lot_number && coil.lot_number !== 'All Stock') {
        lotLabel = `<span class="badge bg-light text-dark border px-2 py-1 fs-8"><i class="fe fe-package text-primary me-1"></i>${coil.lot_number}</span>`;
    } else if (lot && lot.lot_number && lot.lot_number !== 'All Stock') {
        lotLabel = `<span class="badge bg-light text-dark border px-2 py-1 fs-8"><i class="fe fe-package text-primary me-1"></i>${lot.lot_number}</span>`;
    } else {
        lotLabel = `<span class="badge bg-light text-secondary border px-2 py-1 fs-8">Direct Stock</span>`;
    }

    const pieceCount = coil.piece_count ? Number(coil.piece_count) : 1;
    let specBadges = ``;
    if (coil.is_opening) specBadges += `<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 fs-8 me-1">Opening Stock</span>`;
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
                <input type="text" name="custom_size[]" class="form-control form-control-sm border-light-subtle" value="${customSize}" placeholder="Admin custom size...">
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

    calculateTotal();
}

function removeItem(item) {
    document.getElementById('form-group-item' + item)?.remove();
    calculateTotal();
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
    const deliveryCharge = parseFloat(document.getElementById('delivery_charge')?.value) || 0;
    const labourCost = parseFloat(document.getElementById('labour_cost')?.value) || 0;
    const weightScaleCost = parseFloat(document.getElementById('weight_scale_cost')?.value) || 0;
    const otherCharges = parseFloat(document.getElementById('other_charges')?.value) || 0;

    const vatAmount = (subTotal * vatPercent) / 100;
    const grandTotal = Math.max(0, subTotal - discount + vatAmount + deliveryCharge + labourCost + weightScaleCost + otherCharges);
    
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
</script>
@endpush
