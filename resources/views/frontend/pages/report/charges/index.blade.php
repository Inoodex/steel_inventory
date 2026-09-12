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
    .table-custom tbody tr {
        transition: background-color 0.15s ease;
    }
    .table-custom tbody tr:hover {
        background-color: #f8fafc !important;
    }
    .table-responsive {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }
    .dropdown-menu {
        z-index: 1060 !important;
    }
    .table-custom th, .table-custom td {
        white-space: nowrap;
        vertical-align: middle;
    }
    @media (max-width: 1400px) {
        .table-custom th, .table-custom td {
            padding: 8px 10px !important;
            font-size: 12.5px;
        }
        .stat-card h4 {
            font-size: 1.25rem !important;
        }
    }
    .badge-soft-success {
        background-color: rgba(22, 163, 74, 0.12) !important;
        color: #16a34a !important;
        font-weight: 600;
    }
    .badge-soft-warning {
        background-color: rgba(217, 119, 6, 0.12) !important;
        color: #d97706 !important;
        font-weight: 600;
    }
    .badge-soft-danger {
        background-color: rgba(220, 38, 38, 0.12) !important;
        color: #dc2626 !important;
        font-weight: 600;
    }
    .badge-soft-primary {
        background-color: rgba(118, 56, 255, 0.12) !important;
        color: #7638ff !important;
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
        background-color: #f1f5f9 !important;
        color: #1e293b !important;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Extra Charges &amp; Worker Payouts Report</h4>
                <p class="text-muted small mb-0">Audit operational service charges collected on customer orders and disbursements paid to workers, drivers, and scale operators</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('worker-payouts.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-pocket"></i>
                    <span>Manage Payouts</span>
                </a>
                <a href="{{ route('charges.report.pdf', request()->query()) }}" class="btn btn-outline-danger px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2" target="_blank">
                    <i class="fe fe-download fs-6"></i>
                    <span>Export PDF</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0" style="border-left: 4px solid #3b82f6 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Total Invoiced Charges</span>
                        <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center" style="background: rgba(59, 130, 246, 0.12); color: #3b82f6;">
                            <i class="fe fe-dollar-sign fs-5"></i>
                        </div>
                    </div>
                    <h4 class="mb-1 fw-bold text-dark">৳{{ number_format($totalCollectedGrand, 2) }}</h4>
                    <small class="text-muted">Across {{ number_format($totalOrdersCount) }} orders with charges</small>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0" style="border-left: 4px solid #16a34a !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Paid Out to Workers</span>
                        <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center" style="background: rgba(220, 38, 38, 0.12); color: #16a34a;">
                            <i class="fe fe-check-circle fs-5"></i>
                        </div>
                    </div>
                    <h4 class="mb-1 fw-bold text-success">৳{{ number_format($totalPaidGrand, 2) }}</h4>
                    <small class="text-muted">Settled via payout vouchers</small>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0" style="border-left: 4px solid #dc2626 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Outstanding Due to Pay</span>
                        <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center" style="background: rgba(220, 38, 38, 0.12); color: #dc2626;">
                            <i class="fe fe-alert-circle fs-5"></i>
                        </div>
                    </div>
                    <h4 class="mb-1 fw-bold text-danger">৳{{ number_format($totalDueGrand, 2) }}</h4>
                    <small class="text-muted">Pending worker disbursement</small>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0" style="border-left: 4px solid #7638ff !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Category Breakdown</span>
                        <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center" style="background: rgba(118, 56, 255, 0.12); color: #7638ff;">
                            <i class="fe fe-pie-chart fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between text-muted small flex-wrap gap-1">
                        <span>Labour: <strong class="text-dark">৳{{ number_format($totalLabourCol, 2) }}</strong></span>
                        <span>Delivery: <strong class="text-dark">৳{{ number_format($totalDeliveryCol, 2) }}</strong></span>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mt-1 flex-wrap gap-1">
                        <span>Scale: <strong class="text-dark">৳{{ number_format($totalScaleCol, 2) }}</strong></span>
                        <span>Other: <strong class="text-dark">৳{{ number_format($totalOtherCol, 2) }}</strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Summary KPI Cards -->

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
            <h6 class="fw-bold text-dark mb-3"><i class="fe fe-filter me-2 text-primary"></i>Filter Charges &amp; Payouts</h6>
            <form action="{{ route('charges.report') }}" method="GET">
                <div class="row g-3 align-items-end">

                    <!-- Customer Filter -->
                    <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Customer</label>
                        <select name="customer_id" class="form-select border-light-subtle">
                            <option value="">All Customers</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} ({{ $customer->phone }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Charge Type Filter -->
                    <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Charge Type</label>
                        <select name="charge_type" class="form-select border-light-subtle">
                            <option value="">All Charge Types</option>
                            <option value="labour" {{ request('charge_type') == 'labour' ? 'selected' : '' }}>Labour Cost</option>
                            <option value="delivery" {{ request('charge_type') == 'delivery' ? 'selected' : '' }}>Delivery Transport</option>
                            <option value="scale" {{ request('charge_type') == 'scale' ? 'selected' : '' }}>Weight Scale</option>
                            <option value="other" {{ request('charge_type') == 'other' ? 'selected' : '' }}>Other Charges</option>
                        </select>
                    </div>

                    <!-- Settlement Status -->
                    <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Payout Status</label>
                        <select name="status" class="form-select border-light-subtle">
                            <option value="all">All Statuses</option>
                            <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Unpaid / Pending</option>
                            <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partially Paid</option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Fully Settled</option>
                        </select>
                    </div>

                    <!-- Date Preset -->
                    <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Date Preset</label>
                        <select name="date_preset" class="form-select border-light-subtle">
                            <option value="">Custom Date</option>
                            <option value="today" {{ request('date_preset') == 'today' ? 'selected' : '' }}>Today</option>
                            <option value="this_week" {{ request('date_preset') == 'this_week' ? 'selected' : '' }}>This Week</option>
                            <option value="this_month" {{ request('date_preset') == 'this_month' ? 'selected' : '' }}>This Month</option>
                            <option value="last_month" {{ request('date_preset') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                            <option value="this_year" {{ request('date_preset') == 'this_year' ? 'selected' : '' }}>This Year</option>
                        </select>
                    </div>

                    <!-- Custom Date From -->
                    <div class="col-xl-3 col-lg-4 col-md-4 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">From Date</label>
                        <input type="date" name="from_date" class="form-control border-light-subtle" value="{{ request('from_date') }}">
                    </div>

                    <!-- Custom Date To -->
                    <div class="col-xl-3 col-lg-4 col-md-4 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">To Date</label>
                        <input type="date" name="to_date" class="form-control border-light-subtle" value="{{ request('to_date') }}">
                    </div>

                    <!-- Buttons -->
                    <div class="col-xl-6 col-lg-4 col-md-4 col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary text-nowrap rounded-3 px-4 py-2 flex-grow-1 d-inline-flex align-items-center justify-content-center gap-1 shadow-sm">
                            <i class="fe fe-filter"></i>
                            <span>Filter</span>
                        </button>
                        <a href="{{ route('charges.report') }}" class="btn btn-outline-secondary text-nowrap rounded-3 px-4 py-2 d-inline-flex align-items-center justify-content-center shadow-sm">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- /Filter Card -->

    <!-- Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                <span class="badge bg-primary-light text-primary fs-7 rounded-pill px-3 py-2">
                    Showing <strong id="visibleChargesCount">{{ $analyzedCharges->count() }}</strong> Orders
                </span>
                <div class="col-md-4 col-12">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-light-subtle text-muted"><i class="fe fe-search"></i></span>
                        <input type="text" id="chargesReportSearchInput" class="form-control border-light-subtle" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0" id="chargesReportTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 40px;">#</th>
                            <th>Order No &amp; Date</th>
                            <th>Customer</th>
                            <th>Labour (Col / Paid)</th>
                            <th>Delivery (Col / Paid)</th>
                            <th>Scale (Col / Paid)</th>
                            <th>Other (Col / Paid)</th>
                            <th>Total Charges</th>
                            <!-- <th>Due to Workers</th>
                            <th class="text-center">Status</th> -->
                            <th class="text-end" style="width: 60px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($analyzedCharges as $index => $item)
                            @php
                                $searchStr = strtolower($item->order_no . ' ' . $item->customer_name . ' ' . $item->customer_phone);
                            @endphp
                            <tr class="charges-report-row" data-search="{{ $searchStr }}">
                                <td class="text-center text-muted fw-semibold">{{ $index + 1 }}</td>
                                <td>
                                    <a href="{{ route('sales.show', $item->id) }}" class="fw-bold text-dark text-decoration-none d-block">
                                        {{ $item->order_no }}
                                    </a>
                                    <small class="text-muted"><i class="fe fe-calendar me-1"></i>{{ \Carbon\Carbon::parse($item->order_date)->format('d M Y') }}</small>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark d-block">{{ $item->customer_name }}</span>
                                    <small class="text-muted">{{ $item->customer_phone }}</small>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark d-block">৳{{ number_format($item->labour_col, 2) }}</span>
                                    <small class="text-muted">Paid: <span class="text-success">৳{{ number_format($item->labour_paid, 2) }}</span></small>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark d-block">৳{{ number_format($item->delivery_col, 2) }}</span>
                                    <small class="text-muted">Paid: <span class="text-success">৳{{ number_format($item->delivery_paid, 2) }}</span></small>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark d-block">৳{{ number_format($item->scale_col, 2) }}</span>
                                    <small class="text-muted">Paid: <span class="text-success">৳{{ number_format($item->scale_paid, 2) }}</span></small>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark d-block">৳{{ number_format($item->other_col, 2) }}</span>
                                    <small class="text-muted">Paid: <span class="text-success">৳{{ number_format($item->other_paid, 2) }}</span></small>
                                </td>
                                <td>
                                    <strong class="text-primary fs-7">৳{{ number_format($item->total_col, 2) }}</strong>
                                    <small class="text-muted d-block">Paid: ৳{{ number_format($item->total_paid, 2) }}</small>
                                </td>
                                <!-- <td>
                                    <strong class="{{ $item->total_due > 0 ? 'text-danger' : 'text-success' }}">
                                        ৳{{ number_format($item->total_due, 2) }}
                                    </strong>
                                </td>
                                <td class="text-center">
                                    @if($item->status === 'paid')
                                        <span class="badge badge-soft-success px-2 py-1 rounded-pill">Settled</span>
                                    @elseif($item->status === 'partial')
                                        <span class="badge badge-soft-warning px-2 py-1 rounded-pill">Partial</span>
                                    @else
                                        <span class="badge badge-soft-danger px-2 py-1 rounded-pill">Unpaid</span>
                                    @endif
                                </td> -->
                                <td class="text-end">
                                    <div class="dropdown dropdown-action">
                                        <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                            <a class="dropdown-item py-2" href="{{ route('sales.show', $item->id) }}">
                                                <i class="fe fe-eye me-2 text-primary"></i>View Sale Order
                                            </a>
                                            <a class="dropdown-item py-2" href="{{ route('worker-payouts.index') }}?search={{ $item->order_no }}">
                                                <i class="fe fe-pocket me-2 text-success"></i>Record Payout
                                            </a>
                                            <a class="dropdown-item py-2" href="{{ route('charges.report.pdf', array_merge(request()->query(), ['customer_id' => $item->sale->customer_id])) }}" target="_blank">
                                                <i class="fe fe-download me-2 text-danger"></i>Export PDF
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5">
                                    <div class="py-3">
                                        <i class="fe fe-pocket text-muted fs-1 mb-2 d-block"></i>
                                        <h5 class="fw-bold text-dark mb-1">No Extra Charges Records Found</h5>
                                        <p class="text-muted small mb-0">Try changing your filters or date preset.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($analyzedCharges->count() > 0)
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="3" class="text-end text-dark">Grand Totals:</td>
                            <td>
                                <div>৳{{ number_format($totalLabourCol, 2) }}</div>
                                <small class="text-success">Paid: ৳{{ number_format($totalLabourPaid, 2) }}</small>
                            </td>
                            <td>
                                <div>৳{{ number_format($totalDeliveryCol, 2) }}</div>
                                <small class="text-success">Paid: ৳{{ number_format($totalDeliveryPaid, 2) }}</small>
                            </td>
                            <td>
                                <div>৳{{ number_format($totalScaleCol, 2) }}</div>
                                <small class="text-success">Paid: ৳{{ number_format($totalScalePaid, 2) }}</small>
                            </td>
                            <td>
                                <div>৳{{ number_format($totalOtherCol, 2) }}</div>
                                <small class="text-success">Paid: ৳{{ number_format($totalOtherPaid, 2) }}</small>
                            </td>
                            <td class="text-primary">৳{{ number_format($totalCollectedGrand, 2) }}</td>
                            <td class="text-danger">৳{{ number_format($totalDueGrand, 2) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
    <!-- /Table Card -->

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('chargesReportSearchInput');
    const rows = document.querySelectorAll('.charges-report-row');
    const visibleCountSpan = document.getElementById('visibleChargesCount');

    if (searchInput && rows.length > 0) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            let count = 0;

            rows.forEach(function (row) {
                const text = row.getAttribute('data-search') || '';
                if (text.includes(query)) {
                    row.style.display = '';
                    count++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (visibleCountSpan) {
                visibleCountSpan.textContent = count;
            }
        });
    }
});
</script>
@endpush
