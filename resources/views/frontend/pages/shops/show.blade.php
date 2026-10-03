@extends('frontend.layouts.app')

@push('styles')
<style>
    .stat-card-mini {
        border: 1px solid rgba(0, 0, 0, 0.05);
        background: #f8fafc;
        transition: transform 0.2s ease;
    }
    .stat-card-mini:hover {
        transform: translateY(-2px);
    }
    .nav-tabs-custom .nav-link {
        border: none;
        color: #64748b;
        font-weight: 600;
        padding: 12px 18px;
        border-bottom: 2px solid transparent;
        transition: all 0.2s ease;
    }
    .nav-tabs-custom .nav-link.active {
        color: #7638ff;
        border-bottom-color: #7638ff;
        background: transparent;
    }
    .badge-soft-success {
        background-color: rgba(25, 135, 84, 0.12) !important;
        color: #198754 !important;
        font-weight: 600;
    }
    .badge-soft-danger {
        background-color: rgba(220, 53, 69, 0.12) !important;
        color: #dc3545 !important;
        font-weight: 600;
    }
    .badge-soft-primary {
        background-color: rgba(118, 56, 255, 0.12) !important;
        color: #7638ff !important;
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
    .table-responsive {
        overflow: visible !important;
    }
    .dropdown-menu {
        z-index: 1060 !important;
    }
    .table-custom th, .table-custom td {
        white-space: nowrap;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header (No Breadcrumbs) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Shop Profile &amp; Operations Hub</h4>
                <p class="text-muted small mb-0">Overview of live inventory, sales, purchases, and branch transactions for <strong>{{ $shop->name }}</strong></p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('sales.create') }}?warehouse_id={{ $shop->id }}" class="btn btn-primary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="fe fe-plus-circle"></i>
                    <span>New Sale from Shop</span>
                </a>
                <a href="{{ route('purchase.create') }}?warehouse_id={{ $shop->id }}" class="btn btn-outline-info px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="fe fe-download"></i>
                    <span>New Stock Intake</span>
                </a>
                <a href="{{ route('returns.create') }}" class="btn btn-outline-warning px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="fe fe-refresh-cw"></i>
                    <span>Sales Return</span>
                </a>
                <a href="{{ route('shops.stock.pdf') }}?shop_id={{ $shop->id }}" target="_blank" class="btn btn-outline-danger px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-file-text"></i>
                    <span>Stock PDF</span>
                </a>
                <a href="{{ route('shops.edit', $shop->id) }}" class="btn btn-light px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 border">
                    <i class="fe fe-edit"></i>
                    <span>Edit Shop</span>
                </a>
                <a href="{{ route('shops.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Shop Information Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center g-4">
                <div class="col-md-5 col-lg-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-xl bg-primary-light text-primary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 60px; height: 60px;">
                            <i class="fe fe-shopping-cart fs-2"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold text-dark mb-1">{{ $shop->name }}</h4>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-light text-dark border px-2 py-1 font-monospace fw-bold">{{ $shop->code }}</span>
                                @if($shop->status === 'active')
                                    <span class="badge badge-soft-success px-2 py-1 rounded-pill"><i class="fe fe-check-circle me-1"></i>Active Outlet</span>
                                @else
                                    <span class="badge badge-soft-danger px-2 py-1 rounded-pill"><i class="fe fe-x-circle me-1"></i>Inactive</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-7 col-lg-8 border-start border-light ps-lg-4">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded-3 text-center stat-card-mini">
                                <small class="text-muted text-uppercase fw-semibold fs-7 d-block mb-1">Branch Manager</small>
                                <span class="fw-bold text-dark">{{ $shop->contact_person ?: 'Not Assigned' }}</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded-3 text-center stat-card-mini">
                                <small class="text-muted text-uppercase fw-semibold fs-7 d-block mb-1">Contact Phone</small>
                                <span class="fw-bold text-dark">{{ $shop->contact_phone ?: 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded-3 text-center stat-card-mini">
                                <small class="text-muted text-uppercase fw-semibold fs-7 d-block mb-1">Registered Since</small>
                                <span class="fw-bold text-dark">{{ $shop->created_at?->format('d M Y') ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($shop->location || $shop->notes)
            <div class="row mt-3 pt-3 border-top border-light g-3">
                @if($shop->location)
                <div class="col-md-6">
                    <small class="text-muted fw-semibold d-block">Shop Address / Location:</small>
                    <span class="text-dark"><i class="fe fe-map-pin text-primary me-1"></i>{{ $shop->location }}</span>
                </div>
                @endif
                @if($shop->notes)
                <div class="col-md-6">
                    <small class="text-muted fw-semibold d-block">Notes:</small>
                    <span class="text-dark">{{ $shop->notes }}</span>
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>

    <!-- Financial & Stock Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-database fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Live Stock In-Shop</small>
                        <h5 class="fw-bold text-dark mb-0">{{ $inStockCoilsCount }} Items <span class="text-muted fs-7 fw-normal">({{ number_format($totalStockWeightTon, 2) }} MT)</span></h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Stock Valuation</small>
                        <h5 class="fw-bold text-dark mb-0">৳{{ number_format($totalStockValuation, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-shopping-bag fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Sales Invoiced</small>
                        <h5 class="fw-bold text-dark mb-0">৳{{ number_format($totalSalesAmount, 2) }} <span class="text-muted fs-7 fw-normal">({{ $totalSalesCount }} Orders)</span></h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-warning-light text-warning rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-download fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Inward Purchases</small>
                        <h5 class="fw-bold text-dark mb-0">৳{{ number_format($totalPurchasesAmount, 2) }} <span class="text-muted fs-7 fw-normal">({{ $totalPurchasesCount }} Batches)</span></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Operations Tabs Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white p-0 border-bottom border-light">
            <ul class="nav nav-tabs nav-tabs-custom px-3" id="shopTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active d-flex align-items-center gap-2" id="stock-tab" data-bs-toggle="tab" data-bs-target="#stock-tab-pane" type="button" role="tab">
                        <i class="fe fe-database"></i>
                        <span>Live Stock Inventory ({{ $inStockCoilsCount }})</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center gap-2" id="thickness-tab" data-bs-toggle="tab" data-bs-target="#thickness-tab-pane" type="button" role="tab">
                        <i class="fe fe-layers"></i>
                        <span>Thickness Breakdown ({{ $thicknessBreakdown->count() }})</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center gap-2" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales-tab-pane" type="button" role="tab">
                        <i class="fe fe-shopping-bag"></i>
                        <span>Recent Sales ({{ $recentSales->count() }})</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center gap-2" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases-tab-pane" type="button" role="tab">
                        <i class="fe fe-download"></i>
                        <span>Inward Purchases ({{ $recentPurchases->count() }})</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center gap-2" id="returns-tab" data-bs-toggle="tab" data-bs-target="#returns-tab-pane" type="button" role="tab">
                        <i class="fe fe-refresh-cw"></i>
                        <span>Sales Returns ({{ $recentReturns->count() }})</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-0">
            <div class="tab-content" id="shopTabContent">

                <!-- TAB 1: Live Stock Inventory -->
                <div class="tab-pane fade show active p-0" id="stock-tab-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary fs-7 text-uppercase">
                                <tr>
                                    <th class="ps-4">Coil / Item #</th>
                                    <th>Thickness</th>
                                    <th>Width / Size</th>
                                    <th>Length / Spec</th>
                                    <th class="text-center">Pieces</th>
                                    <th class="text-end">Rem. Weight (Kg)</th>
                                    <th class="text-end">Rate / Ton (৳)</th>
                                    <th class="text-end">Valuation (৳)</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="fs-7">
                                @forelse($coils as $coil)
                                    @php
                                        $val = (float)$coil->remaining_weight * (float)$coil->rate_per_ton;
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <span class="badge bg-light text-dark border font-monospace">{{ $coil->coil_no }}</span>
                                        </td>
                                        <td><span class="fw-bold text-dark">{{ $coil->thickness }}</span></td>
                                        <td>{{ $coil->width }}</td>
                                        <td>{{ $coil->length ?: 'N/A' }}</td>
                                        <td class="text-center">{{ number_format($coil->piece_count ?: 1) }}</td>
                                        <td class="text-end fw-bold text-dark">{{ number_format($coil->remaining_weight, 2) }} kg</td>
                                        <td class="text-end">৳{{ number_format($coil->rate_per_ton * 1000, 2) }}</td>
                                        <td class="text-end fw-bold text-success">৳{{ number_format($val, 2) }}</td>
                                        <td class="text-center">
                                            @if($coil->status === 'in_stock')
                                                <span class="badge badge-soft-success px-2 py-1 rounded-pill">In Stock</span>
                                            @elseif($coil->status === 'processing')
                                                <span class="badge badge-soft-warning px-2 py-1 rounded-pill">Processing</span>
                                            @else
                                                <span class="badge badge-soft-danger px-2 py-1 rounded-pill">{{ ucfirst($coil->status) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <div class="mb-2"><i class="fe fe-box fs-1 text-secondary opacity-50"></i></div>
                                            <h6 class="fw-bold">No Active Stock In This Shop</h6>
                                            <p class="small text-muted mb-3">Record a purchase or stock intake to assign inventory to this shop.</p>
                                            <a href="{{ route('purchase.create') }}?warehouse_id={{ $shop->id }}" class="btn btn-primary btn-sm px-3 rounded-3">
                                                <i class="fe fe-plus-circle me-1"></i> Inward Stock Intake
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($coils->hasPages())
                        <div class="card-footer bg-white py-3 border-top border-light">
                            {{ $coils->links() }}
                        </div>
                    @endif
                </div>

                <!-- TAB 2: Thickness Breakdown -->
                <div class="tab-pane fade p-4" id="thickness-tab-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary fs-7 text-uppercase">
                                <tr>
                                    <th>Thickness</th>
                                    <th class="text-center">Coil Count</th>
                                    <th class="text-center">Pieces</th>
                                    <th class="text-end">Total Weight (Kg)</th>
                                    <th class="text-end">Weight (MT)</th>
                                    <th class="text-end">Total Valuation (৳)</th>
                                    <th class="text-end">Avg Rate / Ton (৳)</th>
                                </tr>
                            </thead>
                            <tbody class="fs-7">
                                @forelse($thicknessBreakdown as $row)
                                    <tr>
                                        <td><span class="fw-bold text-dark">{{ $row['thickness'] }}</span></td>
                                        <td class="text-center"><span class="badge bg-light text-dark border">{{ $row['coils_count'] }}</span></td>
                                        <td class="text-center">{{ number_format($row['pieces_count']) }}</td>
                                        <td class="text-end fw-bold text-dark">{{ number_format($row['total_weight'], 2) }} kg</td>
                                        <td class="text-end fw-bold text-primary">{{ number_format($row['total_weight_mt'], 3) }} MT</td>
                                        <td class="text-end fw-bold text-success">৳{{ number_format($row['total_valuation'], 2) }}</td>
                                        <td class="text-end">৳{{ number_format($row['avg_price_per_ton'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No stock data available for thickness breakdown.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 3: Recent Sales -->
                <div class="tab-pane fade p-0" id="sales-tab-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary fs-7 text-uppercase">
                                <tr>
                                    <th class="ps-4">Order #</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th class="text-end">Total Bill (৳)</th>
                                    <th class="text-end">Paid (৳)</th>
                                    <th class="text-end">Due (৳)</th>
                                    <th class="text-center">Payment Status</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody class="fs-7">
                                @forelse($recentSales as $sale)
                                    <tr>
                                        <td class="ps-4">
                                            <a href="{{ route('sales.show', $sale->id) }}" class="fw-bold text-primary text-decoration-none">
                                                {{ $sale->order_no }}
                                            </a>
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($sale->order_date)->format('d M Y') }}</td>
                                        <td>{{ $sale->customer?->name ?? 'Walk-in Customer' }}</td>
                                        <td class="text-end fw-bold text-dark">৳{{ number_format((float)($sale->payble ?? 0), 2) }}</td>
                                        <td class="text-end text-success fw-bold">৳{{ number_format((float)($sale->advanced_payment ?? 0), 2) }}</td>
                                        <td class="text-end text-danger fw-bold">৳{{ number_format((float)($sale->due_payment ?? 0), 2) }}</td>
                                        <td class="text-center">
                                            @if((float)($sale->due_payment ?? 0) <= 0)
                                                <span class="badge badge-soft-success px-2 py-1 rounded-pill">Paid</span>
                                            @elseif((float)($sale->advanced_payment ?? 0) > 0)
                                                <span class="badge badge-soft-warning px-2 py-1 rounded-pill">Partial</span>
                                            @else
                                                <span class="badge badge-soft-danger px-2 py-1 rounded-pill">Unpaid</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="{{ route('sales.invoice.pdf', $sale->id) }}" target="_blank" class="btn btn-sm btn-outline-danger px-2 py-1 rounded-2" title="Invoice PDF">
                                                <i class="fe fe-download"></i>
                                            </a>
                                            <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-sm btn-light border px-2 py-1 rounded-2" title="View Sale">
                                                <i class="fe fe-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No sales recorded from this shop yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 4: Inward Purchases -->
                <div class="tab-pane fade p-0" id="purchases-tab-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary fs-7 text-uppercase">
                                <tr>
                                    <th class="ps-4">Batch / Lot</th>
                                    <th>Date</th>
                                    <th>Vendor</th>
                                    <th>Spec / Size</th>
                                    <th class="text-end">Weight (Kg)</th>
                                    <th class="text-end">Total Cost (৳)</th>
                                    <th class="text-end">Due (৳)</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody class="fs-7">
                                @forelse($recentPurchases as $purchase)
                                    <tr>
                                        <td class="ps-4">
                                            <span class="badge bg-light text-dark border font-monospace">{{ $purchase->lot?->name ?? 'Lot #' . $purchase->lot_id }}</span>
                                        </td>
                                        <td>{{ $purchase->created_at?->format('d M Y') }}</td>
                                        <td>{{ $purchase->vendor?->name ?? 'N/A' }}</td>
                                        <td>{{ $purchase->thickness }} | {{ $purchase->size }}</td>
                                        <td class="text-end fw-bold text-dark">{{ number_format($purchase->total_weight, 2) }} kg</td>
                                        <td class="text-end fw-bold text-dark">৳{{ number_format($purchase->total_price, 2) }}</td>
                                        <td class="text-end text-danger fw-bold">৳{{ number_format($purchase->due, 2) }}</td>
                                        <td class="text-end pe-4">
                                            <a href="{{ route('purchase.show', $purchase->id) }}" class="btn btn-sm btn-light border px-2 py-1 rounded-2">
                                                <i class="fe fe-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No inward purchase batches for this shop yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 5: Sales Returns -->
                <div class="tab-pane fade p-0" id="returns-tab-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary fs-7 text-uppercase">
                                <tr>
                                    <th class="ps-4">Return ID</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Invoice #</th>
                                    <th class="text-end">Refund Amount (৳)</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody class="fs-7">
                                @forelse($recentReturns as $ret)
                                    <tr>
                                        <td class="ps-4">
                                            <span class="badge bg-light text-dark border font-monospace">#RET-{{ str_pad($ret->id, 4, '0', STR_PAD_LEFT) }}</span>
                                        </td>
                                        <td>{{ $ret->return_date?->format('d M Y') }}</td>
                                        <td>{{ $ret->customer?->name ?? 'N/A' }}</td>
                                        <td><span class="fw-bold text-dark">{{ $ret->sale?->order_no ?? 'N/A' }}</span></td>
                                        <td class="text-end fw-bold text-danger">৳{{ number_format($ret->total_refund_amount, 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-soft-info px-2 py-1 rounded-pill">{{ ucfirst($ret->status) }}</span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="{{ route('returns.show', $ret->id) }}" class="btn btn-sm btn-light border px-2 py-1 rounded-2">
                                                <i class="fe fe-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No sales returns recorded for this shop.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection
