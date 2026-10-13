@extends('frontend.layouts.app')

@push('styles')
<style>
    .badge-soft-success {
        background-color: rgba(25, 135, 84, 0.12) !important;
        color: #198754 !important;
        font-weight: 600;
    }
    .badge-soft-info {
        background-color: rgba(13, 202, 240, 0.12) !important;
        color: #0dcaf0 !important;
        font-weight: 600;
    }
    .badge-soft-warning {
        background-color: rgba(255, 193, 7, 0.15) !important;
        color: #b58105 !important;
        font-weight: 600;
    }
    .qty-input:focus {
        border-color: #7638ff !important;
        box-shadow: 0 0 0 0.2rem rgba(118, 56, 255, 0.15) !important;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header (No Breadcrumbs per AGENTS.md) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h4 class="card-title fw-bold text-dark mb-0">Create Shop Return</h4>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill small">
                        <i class="fe fe-home me-1"></i>{{ $shop->name }}
                    </span>
                </div>
                <p class="text-muted small mb-0">Process customer returns and restock inventory exclusively for retail outlet: <strong>{{ $shop->name }}</strong></p>
            </div>
            <div>
                <a href="{{ route('shops.returns.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left fs-6"></i>
                    <span>Back to Shop Returns</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <div class="d-flex align-items-center mb-2">
                <i class="fe fe-alert-triangle fs-4 me-2"></i>
                <strong class="fs-6">Please check the form below for errors:</strong>
            </div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('shops.returns.store') }}" id="shopReturnForm">
        @csrf

        <!-- Section 1: Shop & Sale Order Selection -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fe fe-shopping-bag me-2 text-primary"></i>1. Select Shop Sale Order
                    </h6>
                    <span class="text-muted small">Warehouse: <strong>{{ $shop->name }}</strong> ({{ $shop->code ?? 'Shop' }})</span>
                </div>

                <div class="row g-3">
                    <div class="col-lg-7 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">
                            Shop Sale Invoice / Order <span class="text-danger">*</span>
                        </label>
                        <select name="sale_id" id="saleSelect" class="form-select border-light-subtle select2" required onchange="handleSaleChange(this.value)">
                            <option value="">-- Select a Sale Order from {{ $shop->name }} --</option>
                            @foreach($sales as $s)
                                <option value="{{ $s->id }}" {{ (old('sale_id', request('sale_id')) == $s->id) ? 'selected' : '' }}>
                                    #{{ $s->order_no }} &mdash; {{ $s->customer->name ?? 'Walk-in Customer' }} &bull; ৳{{ number_format($s->total ?? $s->payble ?? 0, 2) }} ({{ $s->order_date ? \Carbon\Carbon::parse($s->order_date)->format('d M Y') : $s->created_at->format('d M Y') }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">Only sales completed through <strong>{{ $shop->name }}</strong> are listed.</small>
                    </div>

                    <div class="col-lg-5 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">
                            Return Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="return_date" class="form-control border-light-subtle" value="{{ old('return_date', date('Y-m-d')) }}" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Sale Items Display -->
        <div id="saleItemsSection" class="card border-0 shadow-sm rounded-3 mb-4 {{ ($sale && count($saleItems) > 0) ? '' : 'd-none' }}">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fe fe-box me-2 text-primary"></i>2. Items to Return & Restock
                    </h6>
                    <small class="text-muted">Enter quantity to return for applicable items. Leave 0 for items not being returned.</small>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary fs-7 text-uppercase">
                            <tr>
                                <th>Item / Stock Source</th>
                                <th class="text-center" style="width: 100px;">Sold Qty</th>
                                <th class="text-center" style="width: 110px;">Avail. Return</th>
                                <th style="width: 130px;">Return Qty <span class="text-danger">*</span></th>
                                <th style="width: 130px;">Unit Price</th>
                                <th style="width: 130px;">Subtotal</th>
                                <th style="width: 160px;">Return Reason</th>
                                <th style="width: 130px;">Condition</th>
                                <th>Notes</th>
                                <th class="text-end" style="width: 60px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="saleItemsTable">
                            @if($sale && $saleItems)
                                @foreach($saleItems as $item)
                                    @php
                                        $coilNo = $item->coil->coil_number ?? $item->product->coil_number ?? null;
                                        $itemName = $coilNo ? ('Coil #' . $coilNo) : ($item->lot ? ('Lot #' . $item->lot->lot_number) : ('Item #' . $item->id));
                                        if ($item->size) {
                                            $itemName .= " ({$item->size}" . ($item->size_type ? " {$item->size_type}" : '') . ")";
                                        }
                                        $availQty = (float)($item->qty - ($item->returned_qty ?? 0));
                                    @endphp
                                    <tr data-product-id="{{ $item->coil_id ?? $item->product_id }}" data-max-qty="{{ max(0, $availQty) }}">
                                        <td>
                                            <span class="fw-bold text-dark d-block">{{ $itemName }}</span>
                                            <small class="text-muted">Ref ID: #{{ $item->id }}</small>
                                            <input type="hidden" name="items[{{ $loop->index }}][sales_item_id]" value="{{ $item->id }}">
                                            <input type="hidden" name="items[{{ $loop->index }}][product_id]" value="{{ $item->coil_id ?? $item->product_id }}">
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-soft-info px-2 py-1 rounded-2">{{ number_format($item->qty, 2) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $availQty > 0 ? 'badge-soft-success' : 'badge-soft-warning' }} px-2 py-1 rounded-2">
                                                {{ number_format(max(0, $availQty), 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <input type="number" step="any" name="items[{{ $loop->index }}][quantity]" class="form-control border-light-subtle qty-input" min="0" max="{{ max(0, $availQty) }}" value="0" style="width: 110px;" oninput="calculateTotal()" {{ $availQty <= 0 ? 'disabled' : '' }}>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm" style="width: 120px;">
                                                <span class="input-group-text bg-light border-light-subtle">৳</span>
                                                <input type="number" step="0.01" name="items[{{ $loop->index }}][unit_price]" class="form-control border-light-subtle bg-light unit-price" value="{{ $item->unit_price }}" readonly>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-danger line-subtotal">৳0.00</span>
                                        </td>
                                        <td>
                                            <select name="items[{{ $loop->index }}][return_reason]" class="form-select form-select-sm border-light-subtle">
                                                <option value="customer_changed_mind">Customer Changed Mind</option>
                                                <option value="wrong_item">Wrong Item</option>
                                                <option value="damaged">Damaged</option>
                                                <option value="defective">Defective</option>
                                                <option value="expired">Expired</option>
                                                <option value="other" selected>Other</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="items[{{ $loop->index }}][condition]" class="form-select form-select-sm border-light-subtle">
                                                <option value="good" selected>Good (Restock)</option>
                                                <option value="damaged">Damaged</option>
                                                <option value="defective">Defective</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="items[{{ $loop->index }}][notes]" class="form-control form-select-sm border-light-subtle" placeholder="Optional notes">
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 rounded-2" title="Remove Item" onclick="this.closest('tr').remove(); calculateTotal();">
                                                <i class="fe fe-trash-2"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info border-0 rounded-3 mt-3 mb-0" id="noItemsMessage" style="display: {{ ($sale && count($saleItems) > 0) ? 'none' : 'block' }}">
                    <div class="d-flex align-items-center">
                        <i class="fe fe-info fs-5 me-2"></i>
                        <span>Select a sale order above to load items available for return.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Reason & Summary Breakdown -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3">
                    <i class="fe fe-file-text me-2 text-primary"></i>3. Return Details & Financial Refund Summary
                </h6>

                <div class="row g-4 align-items-center mb-4">
                    <div class="col-lg-7 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">General Return Reason / Staff Remarks</label>
                        <textarea name="reason" class="form-control border-light-subtle" rows="3" placeholder="Explain the reason for customer return and any special notes...">{{ old('reason') }}</textarea>
                    </div>

                    <div class="col-lg-5 col-md-6 col-12">
                        <div class="p-4 bg-light rounded-3 border">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small">Total Return Items:</span>
                                <span class="fw-bold text-dark" id="totalItemsCount">0 item(s)</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted small">Total Return Qty / Weight:</span>
                                <span class="fw-bold text-primary"><span id="totalReturnQty">0.00</span> kg</span>
                            </div>
                            <div class="border-top pt-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted small d-block">Total Refund Amount:</span>
                                    <small class="text-muted" style="font-size: 11px;">Credited to customer invoice</small>
                                </div>
                                <h3 class="fw-bold text-danger mb-0">৳<span id="totalRefund">0.00</span></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('shops.returns.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                        <span>Create Shop Return</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    window.handleSaleChange = function(saleId) {
        const saleItemsSection = document.getElementById('saleItemsSection');
        const saleItemsTable = document.getElementById('saleItemsTable');
        const noItemsMessage = document.getElementById('noItemsMessage');

        if (!saleId) {
            if (saleItemsSection) saleItemsSection.classList.add('d-none');
            if (noItemsMessage) {
                noItemsMessage.style.display = 'block';
                noItemsMessage.innerHTML = '<div class="d-flex align-items-center"><i class="fe fe-info fs-5 me-2"></i><span>Select a sale order above to load items available for return.</span></div>';
            }
            calculateTotal();
            return;
        }

        if (saleItemsTable) {
            saleItemsTable.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-muted"><i class="fe fe-loader fa-spin me-2"></i>Loading items from shop sale order...</td></tr>';
        }
        if (saleItemsSection) saleItemsSection.classList.remove('d-none');

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        fetch(`/shops/returns/sale-items/${saleId}`, {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Server error: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.items && data.items.length > 0) {
                let html = '';
                data.items.forEach((item, index) => {
                    const avail = Math.max(0, parseFloat(item.available_qty || 0));
                    const isExhausted = avail <= 0;
                    html += `
                        <tr data-product-id="${item.product_id || ''}" data-max-qty="${avail}">
                            <td>
                                <span class="fw-bold text-dark d-block">${item.product_name}</span>
                                <small class="text-muted">Ref ID: #${item.id}</small>
                                <input type="hidden" name="items[${index}][sales_item_id]" value="${item.id}">
                                <input type="hidden" name="items[${index}][product_id]" value="${item.product_id || ''}">
                            </td>
                            <td class="text-center">
                                <span class="badge badge-soft-info px-2 py-1 rounded-2">${parseFloat(item.quantity).toFixed(2)}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge ${avail > 0 ? 'badge-soft-success' : 'badge-soft-warning'} px-2 py-1 rounded-2">
                                    ${avail.toFixed(2)}
                                </span>
                            </td>
                            <td>
                                <input type="number" step="any" name="items[${index}][quantity]"
                                    class="form-control border-light-subtle qty-input" min="0" max="${avail}"
                                    value="0" style="width: 110px;" oninput="calculateTotal()" ${isExhausted ? 'disabled' : ''}>
                            </td>
                            <td>
                                <div class="input-group input-group-sm" style="width: 120px;">
                                    <span class="input-group-text bg-light border-light-subtle">৳</span>
                                    <input type="number" step="0.01" name="items[${index}][unit_price]"
                                        class="form-control border-light-subtle bg-light unit-price" value="${item.unit_price}" readonly>
                                </div>
                            </td>
                            <td>
                                <span class="fw-bold text-danger line-subtotal">৳0.00</span>
                            </td>
                            <td>
                                <select name="items[${index}][return_reason]" class="form-select form-select-sm border-light-subtle">
                                    <option value="customer_changed_mind">Customer Changed Mind</option>
                                    <option value="wrong_item">Wrong Item</option>
                                    <option value="damaged">Damaged</option>
                                    <option value="defective">Defective</option>
                                    <option value="expired">Expired</option>
                                    <option value="other" selected>Other</option>
                                </select>
                            </td>
                            <td>
                                <select name="items[${index}][condition]" class="form-select form-select-sm border-light-subtle">
                                    <option value="good" selected>Good (Restock)</option>
                                    <option value="damaged">Damaged</option>
                                    <option value="defective">Defective</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="items[${index}][notes]"
                                    class="form-control form-select-sm border-light-subtle" placeholder="Optional notes">
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 rounded-2" title="Remove Item" onclick="this.closest('tr').remove(); calculateTotal();">
                                    <i class="fe fe-trash-2"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                if (saleItemsTable) saleItemsTable.innerHTML = html;
                if (saleItemsSection) saleItemsSection.classList.remove('d-none');
                if (noItemsMessage) noItemsMessage.style.display = 'none';
            } else {
                if (saleItemsTable) saleItemsTable.innerHTML = '';
                if (saleItemsSection) saleItemsSection.classList.add('d-none');
                if (noItemsMessage) {
                    noItemsMessage.style.display = 'block';
                    noItemsMessage.innerHTML = '<div class="d-flex align-items-center"><i class="fe fe-alert-circle fs-5 me-2"></i><span>No items found or all items have already been returned for this sale order.</span></div>';
                }
            }
            calculateTotal();
        })
        .catch(error => {
            if (saleItemsTable) {
                saleItemsTable.innerHTML = '<tr><td colspan="10" class="text-center text-danger py-3">Error loading items from shop sale.</td></tr>';
            }
        });
    };

    window.calculateTotal = function() {
        let totalRefund = 0;
        let totalQty = 0;
        let validRowsCount = 0;

        document.querySelectorAll('#saleItemsTable tr').forEach(row => {
            const qtyInput = row.querySelector('.qty-input');
            const priceInput = row.querySelector('.unit-price');
            const subtotalSpan = row.querySelector('.line-subtotal');

            if (qtyInput && priceInput) {
                const maxQty = parseFloat(row.getAttribute('data-max-qty') || 0);
                let qty = parseFloat(qtyInput.value || 0);

                if (qty > maxQty) {
                    qty = maxQty;
                    qtyInput.value = maxQty;
                }
                if (qty < 0) {
                    qty = 0;
                    qtyInput.value = 0;
                }

                const price = parseFloat(priceInput.value || 0);
                const lineTotal = qty * price;

                if (subtotalSpan) {
                    subtotalSpan.textContent = '৳' + lineTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }

                if (qty > 0) {
                    totalRefund += lineTotal;
                    totalQty += qty;
                    validRowsCount++;
                }
            }
        });

        const refundEl = document.getElementById('totalRefund');
        if (refundEl) refundEl.textContent = totalRefund.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const qtyEl = document.getElementById('totalReturnQty');
        if (qtyEl) qtyEl.textContent = totalQty.toFixed(2);

        const countEl = document.getElementById('totalItemsCount');
        if (countEl) countEl.textContent = validRowsCount + ' item(s)';
    };

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('shopReturnForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                const rows = document.querySelectorAll('#saleItemsTable tr');
                let hasValidQty = false;

                rows.forEach(row => {
                    const qtyInput = row.querySelector('.qty-input');
                    if (qtyInput) {
                        const qty = parseFloat(qtyInput.value || 0);
                        if (qty > 0) {
                            hasValidQty = true;
                        }
                    }
                });

                if (!hasValidQty) {
                    e.preventDefault();
                    alert('Please enter a return quantity greater than 0 for at least one item.');
                    return false;
                }
            });
        }

        const saleSelect = document.getElementById('saleSelect');
        if (saleSelect && saleSelect.value) {
            const saleItemsTable = document.getElementById('saleItemsTable');
            if (saleItemsTable && saleItemsTable.children.length === 0) {
                handleSaleChange(saleSelect.value);
            } else {
                calculateTotal();
            }
        }
    });
</script>
@endpush
