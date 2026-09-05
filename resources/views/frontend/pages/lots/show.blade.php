@extends('frontend.layouts.app')

@push('styles')
<style>
    .stat-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
    }
    .badge-soft-success {
        background-color: rgba(25, 135, 84, 0.12) !important;
        color: #198754 !important;
        font-weight: 600;
    }
    .badge-soft-secondary {
        background-color: rgba(108, 117, 125, 0.15) !important;
        color: #6c757d !important;
        font-weight: 600;
    }
    .badge-soft-warning {
        background-color: rgba(255, 193, 7, 0.15) !important;
        color: #b58105 !important;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title fw-bold text-dark mb-1">Lot Details: {{ $lot->lot_number }}</h3>
            </div>
            <div class="col-auto">
                <a href="{{ route('lots.index') }}" class="btn btn-outline-secondary rounded-3 me-2">
                    <i class="fe fe-arrow-left me-1"></i> Back to Lots
                </a>
                <a href="{{ route('purchase.create', ['lot_id' => $lot->id]) }}" class="btn btn-primary rounded-3">
                    <i class="fe fe-plus-circle me-1"></i> Add Steel Coils to Lot
                </a>
            </div>
        </div>
    </div>

    <!-- Lot Information & Stat Cards -->
    <div class="row g-3 mb-4">
        <!-- Lot Summary Card -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-transparent border-bottom border-light">
                    <h5 class="card-title fw-bold m-0 text-dark">
                        <i class="fe fe-package text-primary me-2"></i> Lot Summary
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted ps-0">Lot Number:</td>
                            <td class="fw-bold text-end pe-0 text-dark">{{ $lot->lot_number }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Vendor / Mill:</td>
                            <td class="fw-bold text-end pe-0 text-dark">{{ $lot->vendor ? $lot->vendor->name : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Lot Date:</td>
                            <td class="fw-bold text-end pe-0 text-dark">{{ \Carbon\Carbon::parse($lot->lot_date)->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Status:</td>
                            <td class="text-end pe-0">
                                @if($lot->status === 'active')
                                    <span class="badge badge-soft-success px-2 py-1 rounded-2">Active</span>
                                @else
                                    <span class="badge badge-soft-secondary px-2 py-1 rounded-2">Closed</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Created By:</td>
                            <td class="fw-semibold text-end pe-0 text-muted">{{ $lot->creator ? $lot->creator->name : 'System' }}</td>
                        </tr>
                    </table>
                    @if($lot->notes)
                        <div class="mt-3 p-2 bg-light rounded-3 text-muted small">
                            <strong>Notes:</strong> {{ $lot->notes }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="col-md-8">
            <div class="row g-3">
                <div class="col-sm-6 col-md">
                    <div class="card stat-card border-0 shadow-sm rounded-3 bg-white p-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Purchases</div>
                        <h4 class="fw-bold text-primary m-0">{{ $totalPurchases }}</h4>
                        <div class="text-muted extra-small">Line Items</div>
                    </div>
                </div>
                <div class="col-sm-6 col-md">
                    <div class="card stat-card border-0 shadow-sm rounded-3 bg-white p-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Total Weight</div>
                        <h4 class="fw-bold text-dark m-0">{{ number_format($totalQuantity, 2) }}</h4>
                        <div class="text-muted extra-small">kg @if($totalQuantity >= 1000)({{ number_format($totalQuantity / 1000, 2) }} MT)@endif</div>
                    </div>
                </div>
                <div class="col-sm-6 col-md">
                    <div class="card stat-card border-0 shadow-sm rounded-3 bg-white p-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Total Lot Bill</div>
                        <h4 class="fw-bold text-dark m-0">৳{{ number_format($totalAmount, 2) }}</h4>
                        <div class="text-muted extra-small">Consignment Cost</div>
                    </div>
                </div>
                <div class="col-sm-6 col-md">
                    <div class="card stat-card border-0 shadow-sm rounded-3 bg-white p-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Paid Amount</div>
                        <h4 class="fw-bold text-success m-0">৳{{ number_format($totalPaid, 2) }}</h4>
                        <div class="text-muted extra-small">Disbursed</div>
                    </div>
                </div>
                <div class="col-sm-6 col-md">
                    <div class="card stat-card border-0 shadow-sm rounded-3 bg-white p-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Outstanding Due</div>
                        <h4 class="fw-bold {{ $totalDue > 0 ? 'text-danger' : 'text-success' }} m-0">
                            {{ $totalDue > 0 ? '৳' . number_format($totalDue, 2) : 'Settled' }}
                        </h4>
                        <div class="text-muted extra-small">Due to Vendor</div>
                    </div>
                </div>
            </div>

            <!-- Purchases Attached Table Card -->
            <div class="card border-0 shadow-sm rounded-3 mt-3">
                <div class="card-header bg-transparent border-bottom border-light d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold m-0 text-dark">Purchases Attached to this Lot</h6>
                    @if($lot->purchases->isNotEmpty())
                        <a href="{{ route('purchase.show', $lot->purchases->first()->id) }}" class="btn btn-sm btn-primary rounded-pill px-3 shadow-none fw-semibold">
                            <i class="fe fe-shopping-cart me-1"></i>View Full Consignment Order
                        </a>
                    @endif
                </div>
                @php
                    $lotDelivery = (float) $lot->purchases->sum('delivery_charge');
                    $lotLabour   = (float) $lot->purchases->sum('labour_cost');
                    $lotScale    = (float) $lot->purchases->sum('weight_scale_cost');
                    $lotOther    = (float) $lot->purchases->sum('other_charges');
                    $lotDiscount = (float) $lot->purchases->sum('discount');
                    $lotTotalExtra = $lotDelivery + $lotLabour + $lotScale + $lotOther;
                @endphp
                @if($lotTotalExtra > 0 || $lotDiscount > 0)
                    <div class="card-header bg-light border-bottom border-light-subtle py-2">
                        <div class="d-flex flex-wrap align-items-center gap-2 small">
                            <span class="text-dark fw-semibold">
                                <i class="fe fe-dollar-sign text-primary me-1"></i>Consignment Extra Charges:
                            </span>
                            @if($lotDelivery > 0)
                                <span class="badge bg-white text-dark border">Delivery: <strong>৳{{ number_format($lotDelivery, 2) }}</strong></span>
                            @endif
                            @if($lotLabour > 0)
                                <span class="badge bg-white text-dark border">Labour: <strong>৳{{ number_format($lotLabour, 2) }}</strong></span>
                            @endif
                            @if($lotScale > 0)
                                <span class="badge bg-white text-dark border">Scale: <strong>৳{{ number_format($lotScale, 2) }}</strong></span>
                            @endif
                            @if($lotOther > 0)
                                <span class="badge bg-white text-dark border">Other: <strong>৳{{ number_format($lotOther, 2) }}</strong></span>
                            @endif
                            @if($lotDiscount > 0)
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Discount: <strong>-৳{{ number_format($lotDiscount, 2) }}</strong></span>
                            @endif
                        </div>
                    </div>
                @endif
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Date</th>
                                    <th>Product / Steel Spec</th>
                                    <th>Stockyard</th>
                                    <th class="text-end">Net Weight</th>
                                    <th class="text-end">Unit Rate</th>
                                    <th class="text-end">Sub Total</th>
                                    <th class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lot->purchases as $purchase)
                                    <tr>
                                        <td class="ps-3 text-muted small fw-semibold">{{ $loop->iteration }}</td>
                                        <td class="small text-muted">{{ $purchase->created_at ? $purchase->created_at->format('d M Y') : 'N/A' }}</td>
                                        <td class="fw-semibold">
                                            <span class="text-dark">Spec: {{ $purchase->thickness ? $purchase->thickness . 'mm' : 'Standard' }} {{ $purchase->size ? ' | ' . $purchase->size : '' }} {{ $purchase->size_type ? '(' . $purchase->size_type . ')' : '' }}</span>
                                            @if($purchase->coils->count() > 0)
                                                <div class="mt-1 d-flex flex-wrap gap-1">
                                                    @foreach($purchase->coils as $coil)
                                                        <span class="badge bg-light text-dark border font-monospace fs-8">{{ $coil->coil_number }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td class="small text-muted">
                                            <i class="fe fe-map-pin text-danger me-1"></i>{{ $purchase->warehouse ? $purchase->warehouse->name : 'Main Depot' }}
                                        </td>
                                        <td class="text-end fw-bold text-primary font-monospace">{{ number_format($purchase->total_weight ?? $purchase->quantity, 2) }} kg</td>
                                        <td class="text-end font-monospace">৳{{ number_format($purchase->unit_price, 2) }}</td>
                                        <td class="text-end fw-bold text-dark font-monospace">৳{{ number_format($purchase->sub_price ?: ($purchase->unit_price * $purchase->total_weight), 2) }}</td>
                                        <td class="text-end pe-3">
                                            <a href="{{ route('purchase.show', $purchase->id) }}" class="btn btn-sm btn-outline-primary rounded-2 px-2.5 py-1" title="View Full Consignment">
                                                <i class="fe fe-eye me-1"></i>Consignment
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            No purchase line items recorded under this Lot yet.
                                        </td>
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
