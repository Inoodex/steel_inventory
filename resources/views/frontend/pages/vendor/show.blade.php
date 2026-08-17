@extends('frontend.layouts.app')

@push('styles')
<style>
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
    .stat-card-mini {
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    @php
        $vData = $vendor ?? $customer;
        $isActive = in_array($vData->status, ['active', '1', 1]);
        $totalPurchases = $purchases->count();
        $totalSpent = $purchases->sum(fn($p) => (float)($p->total_price ?? $p->total_amount ?? 0));
        $totalWeight = $purchases->sum(fn($p) => (float)($p->total_weight ?? 0));
        $totalDue = $purchases->sum(fn($p) => (float)($p->due ?? 0));
    @endphp

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Vendor Profile</h4>
                <p class="text-muted small mb-0">Detailed supplier contact profile, steel procurement volume and purchase order history</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('vendors.edit', $vData->id) }}" class="btn btn-primary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="fe fe-edit"></i>
                    <span>Edit Vendor</span>
                </a>
                <a href="{{ route('vendors.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Vendor Summary Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center g-4">
                <div class="col-md-6 col-lg-4">
                    <div>
                        <h4 class="fw-bold text-dark mb-1">{{ $vData->name }}</h4>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            @if ($isActive)
                                <span class="badge badge-soft-success px-3 py-1 rounded-pill fs-7">
                                    <i class="fe fe-check-circle me-1"></i> Active Vendor
                                </span>
                            @else
                                <span class="badge badge-soft-danger px-3 py-1 rounded-pill fs-7">
                                    <i class="fe fe-x-circle me-1"></i> Inactive
                                </span>
                            @endif
                            <span class="text-muted small">ID #VND-{{ str_pad($vData->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-8 border-start border-light ps-lg-4">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded-3 text-center stat-card-mini">
                                <small class="text-muted text-uppercase fw-semibold fs-7 d-block mb-1">Phone Number</small>
                                <span class="fw-bold text-dark">{{ $vData->phone }}</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded-3 text-center stat-card-mini">
                                <small class="text-muted text-uppercase fw-semibold fs-7 d-block mb-1">Email Address</small>
                                <span class="fw-bold text-dark text-truncate d-block" title="{{ $vData->email }}">{{ $vData->email ?: 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded-3 text-center stat-card-mini">
                                <small class="text-muted text-uppercase fw-semibold fs-7 d-block mb-1">Vendor Since</small>
                                <span class="fw-bold text-dark">{{ $vData->created_at?->format('d M Y') ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address Row -->
            <div class="row mt-4 pt-3 border-top border-light">
                <div class="col-12">
                    <span class="fw-semibold text-secondary small d-block mb-1">Office / Warehouse Address:</span>
                    <p class="text-dark mb-0">{{ $vData->address }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Metrics Strip -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card border-0 shadow-sm rounded-3 bg-white h-100 mb-0">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-shopping-cart fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Purchase Items</small>
                        <h5 class="fw-bold text-dark mb-0">{{ number_format($totalPurchases) }} <small class="text-muted fs-7 fw-normal">orders</small></h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card border-0 shadow-sm rounded-3 bg-white h-100 mb-0">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-package fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Weight Supplied</small>
                        <h5 class="fw-bold text-dark mb-0">{{ number_format($totalWeight, 2) }} <small class="text-muted fs-7 fw-normal">kg</small></h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card border-0 shadow-sm rounded-3 bg-white h-100 mb-0">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Purchased Value</small>
                        <h5 class="fw-bold text-dark mb-0">৳{{ number_format($totalSpent, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card border-0 shadow-sm rounded-3 bg-white h-100 mb-0">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-danger-light text-danger rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-alert-circle fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Outstanding Due</small>
                        <h5 class="fw-bold text-danger mb-0">৳{{ number_format($totalDue, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Order History Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0">Vendor Purchase History</h5>
            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">{{ $totalPurchases }} Procurement Orders</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4">PO / Lot #</th>
                            <th>Date</th>
                            <th>Specifications &amp; Quantity</th>
                            <th>Rate</th>
                            <th>Total Price</th>
                            <th>Paid / Due</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                            @php
                                $price = (float)($purchase->total_price ?? $purchase->total_amount ?? 0);
                                $paid = (float)($purchase->payment ?? $purchase->paid_amount ?? 0);
                                $due = (float)($purchase->due ?? $purchase->due_amount ?? 0);
                                $pieceCount = (float)($purchase->quantity ?? 1);
                                $formattedPieces = floor($pieceCount) == $pieceCount ? (int)$pieceCount : $pieceCount;
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-dark d-block">#PO-{{ $purchase->id }}</span>
                                    @if($purchase->lot)
                                        <div class="mt-1">
                                            <a href="{{ route('lots.show', $purchase->lot_id) }}" class="badge bg-light text-primary border text-decoration-none px-2 py-1 fs-8 d-inline-flex align-items-center">
                                                <i class="fe fe-package me-1"></i>{{ $purchase->lot->lot_number }}
                                            </a>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-secondary small">{{ $purchase->created_at?->format('d M Y, h:i A') }}</span>
                                </td>
                                <td>
                                    <div>
                                        @if($purchase->thickness || $purchase->size)
                                            <span class="fw-bold text-dark fs-7 d-block">
                                                {{ $purchase->thickness ? 'Thk: ' . $purchase->thickness : '' }} 
                                                {{ $purchase->size ? '| Size: ' . $purchase->size . ' ' . ($purchase->size_type ?: 'inch') : '' }}
                                            </span>
                                        @endif
                                        <div class="d-flex align-items-center gap-1 mt-1">
                                            <span class="badge bg-light text-dark border px-2 py-0 fs-8">
                                                Qty: {{ $formattedPieces }} Coils
                                            </span>
                                            @if($purchase->total_weight > 0)
                                                <small class="text-muted fs-8">({{ number_format($purchase->total_weight, 2) }} kg)</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-dark small fw-semibold">৳{{ number_format($purchase->unit_price ?? 0, 2) }}</span>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark">৳{{ number_format($price, 2) }}</span>
                                </td>
                                <td>
                                    <div>
                                        <div class="text-success small fw-semibold">
                                            Paid: ৳{{ number_format($paid, 2) }}
                                        </div>
                                        @if($due > 0)
                                            <div class="text-danger small fw-semibold">
                                                Due: ৳{{ number_format($due, 2) }}
                                            </div>
                                        @else
                                            <div class="text-muted fs-8">
                                                No Due
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-soft-success px-3 py-1 rounded-pill">Completed</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fe fe-truck fs-1 mb-2 text-secondary d-block"></i>
                                    <span>No purchase order transactions recorded for this vendor yet.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
