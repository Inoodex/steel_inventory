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
                <div class="col-sm-6 col-md-3">
                    <div class="card stat-card border-0 shadow-sm rounded-3 bg-white p-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Purchases</div>
                        <h4 class="fw-bold text-primary m-0">{{ $totalPurchases }}</h4>
                        <div class="text-muted extra-small">Line Items</div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card stat-card border-0 shadow-sm rounded-3 bg-white p-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Total Quantity/Weight</div>
                        <h4 class="fw-bold text-dark m-0">{{ number_format($totalQuantity, 2) }}</h4>
                        <div class="text-muted extra-small">Units / Kg / Ton</div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card stat-card border-0 shadow-sm rounded-3 bg-white p-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Total Lot Amount</div>
                        <h4 class="fw-bold text-success m-0">{{ number_format($totalAmount, 2) }}</h4>
                        <div class="text-muted extra-small">Total Cost</div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card stat-card border-0 shadow-sm rounded-3 bg-white p-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Outstanding Due</div>
                        <h4 class="fw-bold text-danger m-0">{{ number_format($totalDue, 2) }}</h4>
                        <div class="text-muted extra-small">Due to Vendor</div>
                    </div>
                </div>
            </div>

            <!-- Purchases Attached Table Card -->
            <div class="card border-0 shadow-sm rounded-3 mt-3">
                <div class="card-header bg-transparent border-bottom border-light">
                    <h6 class="fw-bold m-0 text-dark">Purchases Attached to this Lot</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Date</th>
                                    <th>Product</th>
                                    <th class="text-end">Qty / Weight</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total Price</th>
                                    <th class="text-end pe-3">Paid / Due</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lot->purchases as $purchase)
                                    <tr>
                                        <td class="ps-3">{{ $purchase->created_at ? $purchase->created_at->format('d M Y') : 'N/A' }}</td>
                                        <td class="fw-semibold">
                                            <span class="text-dark">Steel Spec: {{ $purchase->thickness ? $purchase->thickness . 'mm' : 'Standard' }} {{ $purchase->size ? ' | ' . $purchase->size : '' }} {{ $purchase->size_type ? '(' . $purchase->size_type . ')' : '' }}</span>
                                            @if($purchase->coils->count() > 0)
                                                <small class="text-muted d-block font-monospace">
                                                    {{ $purchase->coils->count() }} Coil(s) Attached
                                                </small>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold">{{ number_format($purchase->quantity, 2) }}</td>
                                        <td class="text-end">{{ number_format($purchase->unit_price, 2) }}</td>
                                        <td class="text-end fw-bold text-dark">{{ number_format($purchase->total_price, 2) }}</td>
                                        <td class="text-end pe-3">
                                            <span class="text-success small fw-semibold">{{ number_format($purchase->payment, 2) }}</span> /
                                            <span class="text-danger small fw-semibold">{{ number_format($purchase->due, 2) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
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
