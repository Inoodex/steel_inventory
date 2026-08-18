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
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Edit Sale Order #{{ $sales->order_no }}</h4>
                <p class="text-muted small mb-0">Update customer details, items, dispatch warehouse, and operational charges</p>
            </div>
            <div>
                <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3 shadow-sm">
                    <i class="fe fe-arrow-left me-2"></i>Back to Sales
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <form action="{{ route('sales.update', $sales->id) }}" method="POST" id="editSaleForm">
        @csrf
        @method('PUT')

        <!-- Section 1: Customer & Dispatch Information -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3"><i class="fe fe-user me-2 text-primary"></i>Customer & Dispatch Information</h6>
                
                <div class="row g-3 mb-3">
                    <div class="col-lg-4 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control border-light-subtle" value="{{ old('name', $customer->name ?? '') }}" required autocomplete="off">
                    </div>
                    <div class="col-lg-4 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control border-light-subtle" value="{{ old('phone', $customer->phone ?? '') }}" required autocomplete="off">
                    </div>
                    <div class="col-lg-4 col-md-12 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Address <span class="text-danger">*</span></label>
                        <input type="text" name="address" class="form-control border-light-subtle" value="{{ old('address', $customer->address ?? '') }}" required autocomplete="off">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Warehouse / Dispatch Yard <span class="text-danger">*</span></label>
                        <select name="warehouse_id" id="warehouse_id" class="form-select select2" required>
                            <option value="">Select Warehouse / Yard</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ old('warehouse_id', $sales->warehouse_id) == $wh->id ? 'selected' : '' }}>
                                    {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Delivery Status <span class="text-danger">*</span></label>
                        <select name="delivery_status" id="delivery_status" class="form-select border-light-subtle" required>
                            <option value="pending" {{ old('delivery_status', $sales->delivery_status) == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="dispatched" {{ old('delivery_status', $sales->delivery_status) == 'dispatched' ? 'selected' : '' }}>Dispatched</option>
                            <option value="delivered" {{ old('delivery_status', $sales->delivery_status) == 'delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="partial_delivered" {{ old('delivery_status', $sales->delivery_status) == 'partial_delivered' ? 'selected' : '' }}>Partial Delivered</option>
                        </select>
                    </div>

                    <div class="col-lg-6 col-md-12 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Dispatch Note / Vehicle Info</label>
                        <input type="text" name="note" id="note" class="form-control border-light-subtle" value="{{ old('note', $sales->note) }}" placeholder="Truck #, driver name & contact...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Cart Items -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3"><i class="fe fe-shopping-cart me-2 text-primary"></i>Sale Items & Mill Lots</h6>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="salesItemsTable">
                        <thead class="bg-light text-secondary fs-7 text-uppercase">
                            <tr>
                                <th style="width: 35%;">Product Name</th>
                                <th style="width: 25%;">Mill Lot Source</th>
                                <th style="width: 15%;">Unit Price</th>
                                <th style="width: 10%;">Quantity</th>
                                <th style="width: 15%;">Total Price</th>
                            </tr>
                        </thead>
                        <tbody id="item_container">
                            @foreach ($items as $index => $item)
                                <tr class="group-item item{{ $item->product_id }}" data-itemnumber="{{ $index + 1 }}" id="form-group-item{{ $index + 1 }}">
                                    <td>
                                        <input type="hidden" name="product[]" value="{{ $item->product_id }}">
                                        <span class="fw-bold text-dark d-block">
                                            {{ $item->product->name ?? 'Steel Item' }} {{ $item->product->model ? '('.$item->product->model.')' : '' }}
                                        </span>
                                    </td>
                                    <td>
                                        <select name="lot_id[]" class="form-select form-select-sm border-light-subtle">
                                            <option value="">Standard Stock (No Lot)</option>
                                            @foreach ($lots as $lot)
                                                <option value="{{ $lot->id }}" {{ $item->lot_id == $lot->id ? 'selected' : '' }}>
                                                    {{ $lot->lot_number }} ({{ $lot->vendor->name ?? 'Mill' }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="unit_price[]" class="form-control border-light-subtle unit-price" id="unit_price{{ $index + 1 }}" value="{{ $item->unit_price }}" oninput="calculateTotal()" onchange="calculateTotal()">
                                    </td>
                                    <td>
                                        <input type="number" name="qty[]" class="form-control border-light-subtle qty qty{{ $item->product_id }}" id="qty{{ $index + 1 }}" value="{{ $item->qty }}" min="1" oninput="calculateTotal()" onchange="calculateTotal()">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="total[]" class="form-control border-light-subtle bg-light total" id="total{{ $index + 1 }}" value="{{ $item->total_price }}" readonly>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Section 3: Summary Breakdown -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3"><i class="fe fe-dollar-sign me-2 text-primary"></i>Payment & Operational Charges Breakdown</h6>

                <div id="summerySection" class="row g-3 align-items-end mb-4">
                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Sub Total</label>
                        <input type="number" step="0.01" id="subTotal" class="form-control border-light-subtle bg-light" name="subTotal" value="{{ $sales->bill }}" readonly>
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Discount Amount</label>
                        <input type="number" step="any" id="discount" class="form-control border-light-subtle" name="discount" value="{{ $sales->discount }}" oninput="calculateTotal()" onchange="calculateTotal()">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Delivery Charge</label>
                        <input type="number" step="any" id="delivery_charge" class="form-control border-light-subtle" name="delivery_charge" value="{{ $sales->delivery_charge }}" oninput="calculateTotal()" onchange="calculateTotal()">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Labour / Loading</label>
                        <input type="number" step="any" id="labour_cost" class="form-control border-light-subtle" name="labour_cost" value="{{ $sales->labour_cost }}" oninput="calculateTotal()" onchange="calculateTotal()">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Weight Scale Charge</label>
                        <input type="number" step="any" id="weight_scale_cost" class="form-control border-light-subtle" name="weight_scale_cost" value="{{ $sales->weight_scale_cost }}" oninput="calculateTotal()" onchange="calculateTotal()">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Other Charges</label>
                        <input type="number" step="any" id="other_charges" class="form-control border-light-subtle" name="other_charges" value="{{ $sales->other_charges }}" oninput="calculateTotal()" onchange="calculateTotal()">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-4 col-md-4 col-12">
                        <div class="p-3 bg-light rounded-3 border">
                            <label class="form-label small text-secondary fw-semibold mb-1">Grand Total</label>
                            <input type="number" step="0.01" id="grandTotal" class="form-control border-light-subtle bg-white fw-bold text-primary fs-5" name="grandTotal" value="{{ $sales->payble }}" readonly>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-4 col-12">
                        <div class="p-3 bg-light rounded-3 border">
                            <label class="form-label small text-secondary fw-semibold mb-1">Advance / Received Payment</label>
                            <input type="number" step="0.01" id="advancedPayment" class="form-control border-light-subtle bg-white fw-bold text-success fs-5" name="advanced_payment" value="{{ $sales->advanced_payment }}" min="0" oninput="calculateTotal()" onchange="calculateTotal()">
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-4 col-12">
                        <div class="p-3 bg-light rounded-3 border">
                            <label class="form-label small text-secondary fw-semibold mb-1">Outstanding Due</label>
                            <input type="number" step="0.01" id="duePayment" class="form-control border-light-subtle bg-white fw-bold text-danger fs-5" name="due_payment" value="{{ $sales->due_payment }}" readonly>
                        </div>
                    </div>
                </div>

                <!-- Payment Method & Banking Details Section -->
                <div class="card border border-light-subtle rounded-3 bg-light-subtle p-3 mt-3 mb-2" id="paymentMethodSection">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-4 col-md-6 col-12">
                            <label class="form-label small text-secondary fw-semibold mb-1">
                                <i class="fe fe-credit-card me-1 text-primary"></i> Payment Method
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
                                <i class="fe fe-layers me-1 text-info"></i> Deposit Bank Account
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

                        <!-- Transaction Reference -->
                        <div class="col-lg-4 col-md-6 col-12" id="transactionRefContainer" style="{{ ($sales->payment_method ?? 'cash') === 'cash' ? 'display: none;' : '' }}">
                            <label class="form-label small text-secondary fw-semibold mb-1">
                                <i class="fe fe-file-text me-1 text-secondary"></i> Transaction Ref / TrxID
                            </label>
                            <input type="text" name="transaction_ref" id="transactionRefInput" class="form-control border-light-subtle bg-white" value="{{ $sales->transaction_ref }}" placeholder="e.g. Bank Trx # or Deposit Slip Ref">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-4 mt-3 border-top">
                    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3">Cancel</a>
                    <button type="submit" class="btn btn-primary px-5 py-2 rounded-3 fw-semibold shadow-sm">
                        <i class="fe fe-check-circle me-1"></i> Update Sale Order
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
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });
    });

    function calculateTotal() {
        let subTotal = 0;
        document.querySelectorAll('#item_container tr').forEach(row => {
            const qty = parseFloat(row.querySelector('.qty')?.value) || 0;
            const price = parseFloat(row.querySelector('.unit-price')?.value) || 0;
            const rowTotal = qty * price;
            const totInput = row.querySelector('.total');
            if (totInput) totInput.value = rowTotal.toFixed(2);
            subTotal += rowTotal;
        });

        const discount = parseFloat(document.getElementById('discount')?.value) || 0;
        const deliveryCharge = parseFloat(document.getElementById('delivery_charge')?.value) || 0;
        const labourCost = parseFloat(document.getElementById('labour_cost')?.value) || 0;
        const weightScaleCost = parseFloat(document.getElementById('weight_scale_cost')?.value) || 0;
        const otherCharges = parseFloat(document.getElementById('other_charges')?.value) || 0;

        const grandTotal = Math.max(0, subTotal - discount + deliveryCharge + labourCost + weightScaleCost + otherCharges);
        document.getElementById('subTotal').value = subTotal.toFixed(2);
        document.getElementById('grandTotal').value = grandTotal.toFixed(2);

        const advanced = parseFloat(document.getElementById('advancedPayment')?.value) || 0;
        const due = Math.max(0, grandTotal - advanced);
        document.getElementById('duePayment').value = due.toFixed(2);
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
