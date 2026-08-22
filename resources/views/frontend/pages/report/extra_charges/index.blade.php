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
    .badge-soft-success {
        background-color: rgba(25, 135, 84, 0.12) !important;
        color: #198754 !important;
        font-weight: 600;
    }
    .badge-soft-warning {
        background-color: rgba(255, 193, 7, 0.18) !important;
        color: #b45309 !important;
        font-weight: 600;
    }
    .btn-action-icon {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #dbe2ea !important;
        border-radius: 8px !important;
        background-color: #ffffff !important;
        color: #555e6d !important;
        padding: 0;
        transition: all 0.2s ease;
    }
    .btn-action-icon:hover {
        background-color: #7638ff !important;
        color: #ffffff !important;
        border-color: #7638ff !important;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Extra Charges & Worker Payouts</h4>
                <p class="text-muted small mb-0">Track collected delivery charges, labour/loading fees, scale costs & record payments to workers/drivers</p>
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

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('sales.extra-charges-report') }}" class="row g-3 align-items-end">
                <div class="col-md-3 col-12">
                    <label class="form-label small fw-semibold text-secondary mb-1">From Date</label>
                    <input type="date" name="from" class="form-control" value="{{ request('from', date('Y-m-01')) }}">
                </div>
                <div class="col-md-3 col-12">
                    <label class="form-label small fw-semibold text-secondary mb-1">To Date</label>
                    <input type="date" name="to" class="form-control" value="{{ request('to', date('Y-m-d')) }}">
                </div>
                <div class="col-md-3 col-12">
                    <label class="form-label small fw-semibold text-secondary mb-1">Payout Status</label>
                    <select name="payout_status" class="form-select">
                        <option value="all" {{ request('payout_status') == 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="unpaid" {{ request('payout_status') == 'unpaid' ? 'selected' : '' }}>Unpaid / Pending Payout</option>
                        <option value="paid" {{ request('payout_status') == 'paid' ? 'selected' : '' }}>Paid to Workers</option>
                    </select>
                </div>
                <div class="col-md-3 col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-3 rounded-3 w-100">
                        <i class="fe fe-filter me-1"></i> Filter
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
                        <i class="fe fe-dollar-sign fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Charges Collected</h6>
                        <h4 class="mb-0 fw-bold text-dark">৳ {{ number_format($totalCharges, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-check-circle fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Paid to Workers</h6>
                        <h4 class="mb-0 fw-bold text-success">৳ {{ number_format($totalPaidCharges, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-warning-light text-warning rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-clock fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Pending / Unpaid Payout</h6>
                        <h4 class="mb-0 fw-bold text-warning">৳ {{ number_format($totalUnpaidCharges, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-truck fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Delivery & Loading Total</h6>
                        <h4 class="mb-0 fw-bold text-info">৳ {{ number_format($totalDelivery + $totalLabour, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charges Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-bold text-dark mb-0">Itemized Charges & Payouts Log</h5>
            <span class="text-muted small">Total: <strong class="text-dark">{{ $sales->count() }}</strong> invoices</span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Date</th>
                            <th>Invoice No</th>
                            <th>Customer</th>
                            <th>Delivery (৳)</th>
                            <th>Labour (৳)</th>
                            <th>Scale Fee (৳)</th>
                            <th>Other (৳)</th>
                            <th>Total Charges</th>
                            <th>Worker Payout Status</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse ($sales as $sale)
                            @php
                                $rowTotal = (float)$sale->delivery_charge + (float)$sale->labour_cost + (float)$sale->weight_scale_cost + (float)$sale->other_charges;
                                $isPaid = $sale->charges_payout_status === 'paid';
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
                                <td class="fw-bold text-dark">৳ {{ number_format($rowTotal, 2) }}</td>
                                <td>
                                    @if ($isPaid)
                                        <span class="badge badge-soft-success px-3 py-1 rounded-pill d-inline-flex align-items-center gap-1">
                                            <i class="fe fe-check-circle"></i> Paid to Workers
                                        </span>
                                        @if($sale->charges_payout_at)
                                            <small class="d-block text-muted fs-8 mt-1">
                                                Paid on {{ $sale->charges_payout_at->format('d M Y') }}
                                            </small>
                                        @endif
                                    @else
                                        <span class="badge badge-soft-warning px-3 py-1 rounded-pill d-inline-flex align-items-center gap-1">
                                            <i class="fe fe-clock"></i> Unpaid / Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    @if (!$isPaid)
                                        <button type="button" class="btn btn-sm btn-primary px-3 rounded-2 shadow-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#payModal{{ $sale->id }}">
                                            <i class="fe fe-check"></i> Pay Workers
                                        </button>
                                    @else
                                        <div class="dropdown d-inline-block">
                                            <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                                <li>
                                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#viewPayoutModal{{ $sale->id }}">
                                                        <i class="fe fe-eye text-info"></i>
                                                        <span>View Details</span>
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider opacity-50"></li>
                                                <li>
                                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" href="javascript:void(0)" onclick="if (confirm('Revert payout status to Unpaid? This will remove the payout journal entry.')) { document.getElementById('revertForm{{ $sale->id }}').submit(); }">
                                                        <i class="fe fe-rotate-ccw text-danger"></i>
                                                        <span>Mark as Unpaid</span>
                                                    </a>
                                                    <form id="revertForm{{ $sale->id }}" action="{{ route('sales.extra-charges.revert', $sale->id) }}" method="POST" class="d-none">
                                                        @csrf
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    No sales with extra charges found for the selected criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modals Placement: Outside Table Structure to prevent DOM & clipping issues -->
@foreach ($sales as $sale)
    @php
        $rowTotal = (float)$sale->delivery_charge + (float)$sale->labour_cost + (float)$sale->weight_scale_cost + (float)$sale->other_charges;
        $isPaid = $sale->charges_payout_status === 'paid';
    @endphp

    @if(!$isPaid)
        <!-- Pay Workers Modal -->
        <div class="modal fade" id="payModal{{ $sale->id }}" tabindex="-1" aria-labelledby="payModalLabel{{ $sale->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-3">
                    <form action="{{ route('sales.extra-charges.payout', $sale->id) }}" method="POST">
                        @csrf
                        <div class="modal-header bg-light border-bottom">
                            <h5 class="modal-title fw-bold text-dark" id="payModalLabel{{ $sale->id }}">
                                <i class="fe fe-dollar-sign text-primary me-1"></i> Record Worker Payout
                            </h5>
                            <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <!-- Invoice Summary Card -->
                            <div class="bg-light p-3 rounded-3 border mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted small">Invoice:</span>
                                    <strong class="text-dark">#{{ $sale->order_no }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Customer:</span>
                                    <span class="text-dark">{{ $sale->customer->name ?? 'Walk-in' }}</span>
                                </div>
                                <hr class="my-2 opacity-50">
                                <div class="row g-2 small text-muted">
                                    <div class="col-6">Delivery: <strong>৳{{ number_format($sale->delivery_charge ?? 0, 2) }}</strong></div>
                                    <div class="col-6">Labour: <strong>৳{{ number_format($sale->labour_cost ?? 0, 2) }}</strong></div>
                                    <div class="col-6">Scale Fee: <strong>৳{{ number_format($sale->weight_scale_cost ?? 0, 2) }}</strong></div>
                                    <div class="col-6">Other: <strong>৳{{ number_format($sale->other_charges ?? 0, 2) }}</strong></div>
                                </div>
                                <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                                    <span class="fw-bold text-dark">Total Payout Amount:</span>
                                    <h5 class="fw-bold text-primary mb-0">৳ {{ number_format($rowTotal, 2) }}</h5>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary mb-1">Payout Date <span class="text-danger">*</span></label>
                                <input type="date" name="payout_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary mb-1">Disbursed From (Payment Account) <span class="text-danger">*</span></label>
                                <select name="payment_account_id" class="form-select" required>
                                    @foreach($paymentAccounts as $acc)
                                        <option value="{{ $acc->id }}" {{ $acc->account_code == '1110' ? 'selected' : '' }}>
                                            [{{ $acc->account_code }}] {{ $acc->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-0">
                                <label class="form-label small fw-semibold text-secondary mb-1">Notes / Recipient Details</label>
                                <textarea name="payout_note" class="form-control" rows="2" placeholder="e.g. Paid to Truck Driver Karim & Labour Team"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light border-top gap-2">
                            <button type="button" class="btn btn-outline-secondary px-3 rounded-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4 rounded-2">Confirm & Pay ৳{{ number_format($rowTotal, 2) }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @else
        <!-- View Payout Details Modal -->
        <div class="modal fade" id="viewPayoutModal{{ $sale->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-3">
                    <div class="modal-header bg-light border-bottom">
                        <h5 class="modal-title fw-bold text-dark">
                            <i class="fe fe-check-circle text-success me-1"></i> Payout Details — #{{ $sale->order_no }}
                        </h5>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
                            <i class="fe fe-check-circle fs-5"></i>
                            <div>
                                <strong>Status: Paid to Workers</strong>
                                <div class="small">Paid on {{ $sale->charges_payout_at ? $sale->charges_payout_at->format('F d, Y h:i A') : 'N/A' }}</div>
                            </div>
                        </div>

                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 45%;">Total Amount:</td>
                                <td class="fw-bold text-dark">৳ {{ number_format($rowTotal, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Recorded By:</td>
                                <td class="text-dark">{{ $sale->payoutUser->name ?? 'System Admin' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Payout Note:</td>
                                <td class="text-dark">{{ $sale->charges_payout_note ?? 'No notes recorded' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="modal-footer bg-light border-top">
                        <button type="button" class="btn btn-secondary px-4 rounded-2" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach

@endsection
