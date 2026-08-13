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
    .table-custom tbody tr:hover td {
        background-color: #f8fafc !important;
    }
    .table-custom th, .table-custom td {
        white-space: nowrap;
    }
    .table-responsive {
        overflow: visible !important;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Extra Charges & Payout Summary</h4>
                <p class="text-muted small mb-0">Track collected delivery charges, labour/loading costs, weight scale fees & other pass-through payouts</p>
            </div>
            <div>
                <a class="btn btn-outline-danger px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2" 
                   href="{{ route('sales.extra-charges-report.pdf', request()->all()) }}" target="_blank">
                    <i class="fe fe-file-text fs-6"></i>
                    <span>Export PDF Report</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Date Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('sales.extra-charges-report') }}" class="row g-3 align-items-end">
                <div class="col-md-4 col-12">
                    <label class="form-label small fw-semibold text-secondary mb-1">From Date</label>
                    <input type="date" name="from" class="form-control" value="{{ request('from', date('Y-m-01')) }}">
                </div>
                <div class="col-md-4 col-12">
                    <label class="form-label small fw-semibold text-secondary mb-1">To Date</label>
                    <input type="date" name="to" class="form-control" value="{{ request('to', date('Y-m-d')) }}">
                </div>
                <div class="col-md-4 col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4 rounded-3 w-100">
                        <i class="fe fe-filter me-1"></i> Filter Summary
                    </button>
                    <a href="{{ route('sales.extra-charges-report') }}" class="btn btn-light border rounded-3 px-3">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-truck fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Delivery Charges</h6>
                        <h4 class="mb-0 fw-bold text-dark">৳ {{ number_format($totalDelivery, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-users fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Labour / Loading Fees</h6>
                        <h4 class="mb-0 fw-bold text-dark">৳ {{ number_format($totalLabour, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-warning-light text-warning rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-hard-drive fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Weight Scale Fees</h6>
                        <h4 class="mb-0 fw-bold text-dark">৳ {{ number_format($totalScale, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Pass-Through Payouts</h6>
                        <h4 class="mb-0 fw-bold text-primary">৳ {{ number_format($totalCharges, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charges Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom border-light">
            <h5 class="card-title fw-bold text-dark mb-0">Itemized Charges Log</h5>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Date</th>
                            <th>Invoice No</th>
                            <th>Customer Name</th>
                            <th>Delivery (৳)</th>
                            <th>Labour (৳)</th>
                            <th>Scale Fee (৳)</th>
                            <th>Other (৳)</th>
                            <th class="pe-4 text-end">Total Charges (৳)</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse ($sales as $sale)
                            @php
                                $rowTotal = (float)$sale->delivery_charge + (float)$sale->labour_cost + (float)$sale->weight_scale_cost + (float)$sale->other_charges;
                            @endphp
                            <tr>
                                <td class="ps-4 text-muted fw-semibold">{{ $loop->iteration }}</td>
                                <td>{{ $sale->created_at ? $sale->created_at->format('d M Y') : 'N/A' }}</td>
                                <td>
                                    <a href="{{ route('sales.invoice', $sale->id) }}" class="fw-bold text-primary">
                                        #{{ $sale->order_no }}
                                    </a>
                                </td>
                                <td>{{ $sale->customer->name ?? 'Walk-in Customer' }}</td>
                                <td>৳ {{ number_format($sale->delivery_charge ?? 0, 2) }}</td>
                                <td>৳ {{ number_format($sale->labour_cost ?? 0, 2) }}</td>
                                <td>৳ {{ number_format($sale->weight_scale_cost ?? 0, 2) }}</td>
                                <td>৳ {{ number_format($sale->other_charges ?? 0, 2) }}</td>
                                <td class="pe-4 text-end fw-bold text-dark">৳ {{ number_format($rowTotal, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    No sales with extra charges found for the selected period.
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
