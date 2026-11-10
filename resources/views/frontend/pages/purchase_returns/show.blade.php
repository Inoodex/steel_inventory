@extends('frontend.layouts.app')

@push('styles')
<style>
    .badge-soft-success {
        background-color: rgba(25, 135, 84, 0.12) !important;
        color: #198754 !important;
        font-weight: 600;
    }
    .badge-soft-warning {
        background-color: rgba(255, 193, 7, 0.15) !important;
        color: #b58105 !important;
        font-weight: 600;
    }
    .badge-soft-danger {
        background-color: rgba(220, 53, 69, 0.12) !important;
        color: #dc3545 !important;
        font-weight: 600;
    }
    .badge-soft-info {
        background-color: rgba(13, 202, 240, 0.12) !important;
        color: #0dcaf0 !important;
        font-weight: 600;
    }
    .badge-soft-primary {
        background-color: rgba(118, 56, 255, 0.12) !important;
        color: #7638ff !important;
        font-weight: 600;
    }
    .info-table td {
        padding: 0.65rem 0.5rem;
        vertical-align: middle;
    }
    .fs-8 {
        font-size: 0.8rem;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header (No breadcrumbs as per project guidelines) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h4 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-rotate-ccw text-warning me-2"></i>Purchase Return #{{ $return->return_no }}
                    </h4>
                    <span class="badge badge-soft-success text-uppercase px-2.5 py-1">
                        <i class="fe fe-check-circle me-1"></i>Completed
                    </span>
                    @if($return->refund_type === 'reduce_due')
                        <span class="badge badge-soft-warning px-2.5 py-1">
                            <i class="fe fe-minus-circle me-1"></i>Deduct from PO Due
                        </span>
                    @elseif($return->refund_type === 'advance_credit')
                        <span class="badge badge-soft-primary px-2.5 py-1">
                            <i class="fe fe-plus-circle me-1"></i>Advance Credit
                        </span>
                    @elseif($return->refund_type === 'cash_refund')
                        <span class="badge badge-soft-success px-2.5 py-1">
                            <i class="fe fe-dollar-sign me-1"></i>Cash Refund
                        </span>
                    @elseif($return->refund_type === 'bank_refund')
                        <span class="badge badge-soft-info px-2.5 py-1">
                            <i class="fe fe-credit-card me-1"></i>Bank Refund
                        </span>
                    @endif
                </div>
                <p class="text-muted small mb-0 mt-1">
                    Processed on {{ $return->return_date ? $return->return_date->format('d M Y') : 'N/A' }}
                    • Created by {{ $return->creator?->name ?? 'System' }}
                </p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('purchase-returns.pdf', $return->id) }}" target="_blank" class="btn btn-outline-danger px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 fw-semibold">
                    <i class="fe fe-file-text"></i>
                    <span>Print / PDF Voucher</span>
                </a>
                @if($return->purchase)
                    <a href="{{ route('purchase.show', $return->purchase->id) }}" class="btn btn-outline-primary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                        <i class="fe fe-shopping-cart"></i>
                        <span>View Consignment PO-{{ $return->purchase->id }}</span>
                    </a>
                @endif
                <a href="{{ route('purchase-returns.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back to Returns</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i class="fe fe-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Return Party & Meta Information -->
        <div class="col-lg-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100 mb-0">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="fe fe-truck text-primary"></i>
                        <span>Vendor &amp; Consignment Information</span>
                    </h5>
                </div>
                <div class="card-body p-3">
                    <table class="table info-table mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width: 38%;">Vendor Name:</td>
                                <td class="fw-bold text-dark">
                                    <a href="{{ route('vendors.show', $return->vendor_id) }}" class="text-decoration-none">
                                        {{ $return->vendor?->name ?? 'N/A' }}
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Vendor Phone:</td>
                                <td class="text-secondary">{{ $return->vendor?->phone ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Vendor Address:</td>
                                <td class="text-secondary">{{ $return->vendor?->address ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Original Purchase PO:</td>
                                <td>
                                    @if($return->purchase)
                                        <a href="{{ route('purchase.show', $return->purchase->id) }}" class="badge bg-light text-primary border font-monospace text-decoration-none">
                                            #PO-{{ $return->purchase->id }}
                                        </a>
                                    @else
                                        <span class="text-muted">Direct</span>
                                    @endif
                                </td>
                            </tr>
                            @if($return->purchase?->lot)
                            <tr>
                                <td class="text-muted">Consignment Lot:</td>
                                <td class="fw-semibold text-dark">
                                    <a href="{{ route('lots.show', $return->purchase->lot->id) }}" class="text-decoration-none">
                                        {{ $return->purchase->lot->lot_number }}
                                    </a>
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td class="text-muted">Stockyard / Shop Location:</td>
                                <td class="text-dark fw-semibold">
                                    {{ $return->warehouse?->name ?? 'Main Stockyard' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Voucher Settlement & Financials -->
        <div class="col-lg-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100 mb-0">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="fe fe-dollar-sign text-success"></i>
                        <span>Settlement &amp; Accounting Status</span>
                    </h5>
                </div>
                <div class="card-body p-3">
                    <table class="table info-table mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width: 38%;">Voucher Number:</td>
                                <td class="fw-bold text-primary font-monospace">{{ $return->return_no }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Return Date:</td>
                                <td class="fw-semibold text-dark">{{ $return->return_date ? $return->return_date->format('d M Y') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Settlement Method:</td>
                                <td>
                                    @if($return->refund_type === 'reduce_due')
                                        <span class="badge badge-soft-warning">Deduct from Purchase Due</span>
                                    @elseif($return->refund_type === 'advance_credit')
                                        <span class="badge badge-soft-primary">Vendor Advance Balance</span>
                                    @elseif($return->refund_type === 'cash_refund')
                                        <span class="badge badge-soft-success">Cash Refund</span>
                                    @elseif($return->refund_type === 'bank_refund')
                                        <span class="badge badge-soft-info">Bank Refund</span>
                                    @endif
                                </td>
                            </tr>
                            @if($return->bankDetail)
                            <tr>
                                <td class="text-muted">Bank Account:</td>
                                <td class="fw-semibold text-dark">
                                    {{ $return->bankDetail->bank_name }} - {{ $return->bankDetail->account_number }}
                                </td>
                            </tr>
                            @endif
                            @if($return->transaction_ref)
                            <tr>
                                <td class="text-muted">Transaction / Cheque:</td>
                                <td class="font-monospace text-dark">{{ $return->transaction_ref }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td class="text-muted">Return Reason:</td>
                                <td class="text-dark fw-semibold">
                                    {{ ucwords(str_replace('_', ' ', $return->reason)) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Total Claim / Refund:</td>
                                <td>
                                    <span class="fs-5 fw-bold text-success font-monospace">৳ {{ number_format($return->total_refund_amount, 2) }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Returned Items & Weight Details Table -->
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="fe fe-disc text-primary"></i>
                        <span>Returned Steel Item Specifications</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary fs-8 text-uppercase">
                                <tr>
                                    <th class="ps-4">Coil Tag / Lot</th>
                                    <th>Product / Grade</th>
                                    <th>Thickness &amp; Size</th>
                                    <th class="text-end">Return Weight (kg)</th>
                                    <th class="text-end">Return Weight (MT)</th>
                                    <th class="text-end">Cost Rate (৳/kg)</th>
                                    <th class="text-end pe-4">Total Refund (BDT)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="ps-4">
                                        @if($return->coil)
                                            <a href="{{ route('inventory.index', ['search' => $return->coil->coil_number]) }}" class="badge bg-light text-dark border font-monospace text-decoration-none">
                                                {{ $return->coil->coil_number }}
                                            </a>
                                        @else
                                            <span class="text-muted font-monospace">—</span>
                                        @endif
                                        @if($return->lot)
                                            <div class="small text-muted fs-8 mt-0.5">Lot: {{ $return->lot->lot_number }}</div>
                                        @endif
                                    </td>
                                    <td class="fw-semibold text-dark">
                                        {{ $return->purchase?->product_name ?? 'Steel Consignment' }}
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @if($return->purchase?->thickness)
                                                <span class="badge bg-light text-dark border">{{ $return->purchase->thickness }}</span>
                                            @endif
                                            @if($return->purchase?->size)
                                                <span class="badge bg-light text-dark border">{{ $return->purchase->size }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-danger">
                                        -{{ number_format($return->return_weight, 2) }} kg
                                    </td>
                                    <td class="text-end font-monospace text-secondary">
                                        -{{ number_format($return->return_weight / 1000, 3) }} MT
                                    </td>
                                    <td class="text-end font-monospace">
                                        ৳ {{ number_format($return->unit_price, 2) }}
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-success pe-4 fs-6">
                                        ৳ {{ number_format($return->total_refund_amount, 2) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <td colspan="3" class="ps-4 fw-bold text-dark text-uppercase fs-8">Total Return Deductions:</td>
                                    <td class="text-end font-monospace fw-bold text-danger">-{{ number_format($return->return_weight, 2) }} kg</td>
                                    <td class="text-end font-monospace text-secondary">-{{ number_format($return->return_weight / 1000, 3) }} MT</td>
                                    <td></td>
                                    <td class="text-end font-monospace fw-bold text-success pe-4 fs-6">৳ {{ number_format($return->total_refund_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes & Gate Pass Instructions -->
        @if($return->notes)
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fe fe-file-text text-info me-2"></i>Gate Pass &amp; Transport Notes
                    </h6>
                </div>
                <div class="card-body p-3">
                    <p class="text-dark mb-0" style="white-space: pre-line;">{{ $return->notes }}</p>
                </div>
            </div>
        </div>
        @endif
    </div>

</div>
@endsection

