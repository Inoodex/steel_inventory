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
    .table-responsive {
        overflow: visible !important;
    }
    .dropdown-menu {
        z-index: 1060 !important;
    }
    .table-custom th, .table-custom td {
        white-space: nowrap;
    }
    .fs-8 {
        font-size: 0.78rem;
    }
    .badge-soft-success { background-color: rgba(25, 135, 84, 0.12) !important; color: #198754 !important; }
    .badge-soft-danger { background-color: rgba(220, 53, 69, 0.12) !important; color: #dc3545 !important; }
    .badge-soft-warning { background-color: rgba(255, 193, 7, 0.15) !important; color: #b58105 !important; }
    .badge-soft-info { background-color: rgba(13, 202, 240, 0.12) !important; color: #0dcaf0 !important; }
    .badge-soft-primary { background-color: rgba(118, 56, 255, 0.12) !important; color: #7638ff !important; }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header (No Breadcrumbs) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">
                    <i class="fe fe-activity text-primary me-2"></i>Master Activity &amp; Transaction Logs
                </h4>
                <p class="text-muted small mb-0">Consolidated audit trail of sales, purchases, payments, inventory movements, expenses, and returns</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('reports.master.pdf', request()->all()) }}" target="_blank" class="btn btn-outline-danger px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-file-text"></i>
                    <span>Export Master PDF</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Quick Date Range & Category Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('reports.master') }}" id="filterForm">
                <div class="row g-3 align-items-end">
                    
                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">From Date</label>
                        <input type="date" name="from_date" id="fromDateInput" class="form-control form-control-sm border-light-subtle rounded-3" value="{{ $fromDate }}">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">To Date</label>
                        <input type="date" name="to_date" id="toDateInput" class="form-control form-control-sm border-light-subtle rounded-3" value="{{ $toDate }}">
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Event Category</label>
                        <select name="event_type" class="form-select form-select-sm border-light-subtle rounded-3" onchange="document.getElementById('filterForm').submit()">
                            <option value="all" {{ $eventType === 'all' ? 'selected' : '' }}>All Activity Logs</option>
                            <option value="sales" {{ $eventType === 'sales' ? 'selected' : '' }}>🛒 Sales &amp; Invoices</option>
                            <option value="purchases" {{ $eventType === 'purchases' ? 'selected' : '' }}>📥 Purchases &amp; Intakes</option>
                            <option value="payments" {{ $eventType === 'payments' ? 'selected' : '' }}>💵 Payments &amp; Collections</option>
                            <option value="returns" {{ $eventType === 'returns' ? 'selected' : '' }}>🔄 Sales Returns</option>
                            <option value="expenses" {{ $eventType === 'expenses' ? 'selected' : '' }}>💼 Expenses &amp; Payouts</option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small text-secondary fw-semibold mb-1">Stockyard / Shop</label>
                        <select name="warehouse_id" class="form-select form-select-sm border-light-subtle rounded-3" onchange="document.getElementById('filterForm').submit()">
                            <option value="all" {{ $warehouseId === 'all' ? 'selected' : '' }}>All Locations</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ (string)$warehouseId === (string)$wh->id ? 'selected' : '' }}>
                                    {{ $wh->name }} {{ $wh->type === 'shop' ? '(Shop)' : '(Yard)' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-8 col-12">
                        <label class="form-label small text-secondary fw-semibold mb-1">Search Logs</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-light-subtle"><i class="fe fe-search"></i></span>
                            <input type="text" name="search" class="form-control border-light-subtle" placeholder="Invoice #, Lot #, Customer, Vendor, Phone..." value="{{ $search }}">
                        </div>
                    </div>

                    <div class="col-lg-1 col-md-4 col-12 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm rounded-3 w-100 fw-semibold" title="Apply Filter">
                            <i class="fe fe-filter"></i>
                        </button>
                        <a href="{{ route('reports.master') }}" class="btn btn-outline-secondary btn-sm rounded-3" title="Reset Filters">
                            <i class="fe fe-refresh-cw"></i>
                        </a>
                    </div>

                </div>

                <!-- Quick Date Filter Presets -->
                <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-2 border-top border-light">
                    <span class="text-muted small fw-semibold me-1"><i class="fe fe-calendar me-1"></i>Quick Ranges:</span>
                    <button type="button" class="btn btn-light btn-sm rounded-pill px-2.5 py-0.5 fs-8 text-secondary" onclick="setDateRange('today')">Today</button>
                    <button type="button" class="btn btn-light btn-sm rounded-pill px-2.5 py-0.5 fs-8 text-secondary" onclick="setDateRange('yesterday')">Yesterday</button>
                    <button type="button" class="btn btn-light btn-sm rounded-pill px-2.5 py-0.5 fs-8 text-secondary" onclick="setDateRange('last7')">Last 7 Days</button>
                    <button type="button" class="btn btn-light btn-sm rounded-pill px-2.5 py-0.5 fs-8 text-secondary" onclick="setDateRange('thisMonth')">This Month</button>
                    <button type="button" class="btn btn-light btn-sm rounded-pill px-2.5 py-0.5 fs-8 text-secondary" onclick="setDateRange('lastMonth')">Last Month</button>
                    <button type="button" class="btn btn-light btn-sm rounded-pill px-2.5 py-0.5 fs-8 text-secondary" onclick="setDateRange('allTime')">All Time</button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Analytics Cards Banner -->
    <div class="row g-3 mb-4">
        
        <!-- Total Logged Events -->
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body p-3">
                    <span class="text-muted fs-8 text-uppercase fw-semibold d-block mb-1">Total Logs</span>
                    <h4 class="mb-0 fw-bold text-dark">{{ number_format($kpis['total_events']) }}</h4>
                    <small class="text-muted fs-8">Activity events</small>
                </div>
            </div>
        </div>

        <!-- Total Inflow -->
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body p-3">
                    <span class="text-muted fs-8 text-uppercase fw-semibold d-block mb-1">Money In (Collections)</span>
                    <h5 class="mb-0 fw-bold text-success">৳{{ number_format($kpis['total_inflow'], 2) }}</h5>
                    <small class="text-success fs-8">Receipts &amp; Advance</small>
                </div>
            </div>
        </div>

        <!-- Total Outflow -->
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body p-3">
                    <span class="text-muted fs-8 text-uppercase fw-semibold d-block mb-1">Money Out (Disbursed)</span>
                    <h5 class="mb-0 fw-bold text-danger">৳{{ number_format($kpis['total_outflow'], 2) }}</h5>
                    <small class="text-danger fs-8">Purchases &amp; Expenses</small>
                </div>
            </div>
        </div>

        <!-- Net Cashflow -->
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body p-3">
                    <span class="text-muted fs-8 text-uppercase fw-semibold d-block mb-1">Net Cash Position</span>
                    <h5 class="mb-0 fw-bold {{ $kpis['net_cashflow'] >= 0 ? 'text-primary' : 'text-danger' }}">
                        ৳{{ number_format($kpis['net_cashflow'], 2) }}
                    </h5>
                    <small class="text-muted fs-8">Inflow - Outflow</small>
                </div>
            </div>
        </div>

        <!-- Steel Inward Weight -->
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body p-3">
                    <span class="text-muted fs-8 text-uppercase fw-semibold d-block mb-1">Steel Inward</span>
                    <h5 class="mb-0 fw-bold text-indigo">{{ number_format($kpis['total_weight_in'], 2) }} <small class="fs-8 text-muted">kg</small></h5>
                    <small class="text-muted fs-8">{{ number_format($kpis['total_weight_in'] / 1000, 2) }} Tons received</small>
                </div>
            </div>
        </div>

        <!-- Steel Outward Weight -->
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body p-3">
                    <span class="text-muted fs-8 text-uppercase fw-semibold d-block mb-1">Steel Sold</span>
                    <h5 class="mb-0 fw-bold text-dark">{{ number_format($kpis['total_weight_out'], 2) }} <small class="fs-8 text-muted">kg</small></h5>
                    <small class="text-muted fs-8">{{ number_format($kpis['total_weight_out'] / 1000, 2) }} Tons dispatched</small>
                </div>
            </div>
        </div>

    </div>

    <!-- Master Unified Activity & Transaction Logs Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fe fe-list text-primary me-1"></i>Master Activity Feed
                </h6>
                <span class="badge bg-primary rounded-pill px-2.5 py-1 fs-8">
                    Showing {{ $paginatedLogs->firstItem() ?? 0 }} - {{ $paginatedLogs->lastItem() ?? 0 }} of {{ $paginatedLogs->total() }} Events
                </span>
            </div>
            <div class="small text-muted">
                Period: <span class="fw-bold text-dark">{{ Carbon\Carbon::parse($fromDate)->format('d M Y') }}</span> to <span class="fw-bold text-dark">{{ Carbon\Carbon::parse($toDate)->format('d M Y') }}</span>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 120px;">Date</th>
                            <th style="width: 180px;">Event Category</th>
                            <th style="width: 140px;">Reference #</th>
                            <th>Party / Account / Target</th>
                            <th>Channel / Yard</th>
                            <th class="text-end" style="width: 110px;">Weight (kg)</th>
                            <th class="text-end" style="width: 130px;">Transaction (৳)</th>
                            <th class="text-end" style="width: 120px;">Cash Inflow (৳)</th>
                            <th class="text-end" style="width: 120px;">Cash Outflow (৳)</th>
                            <th style="width: 120px;">Operator</th>
                            <th class="text-center pe-4" style="width: 50px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paginatedLogs as $log)
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-dark">{{ Carbon\Carbon::parse($log['timestamp'])->format('d M Y') }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $log['badge_class'] }} px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1">
                                        <i class="{{ $log['icon'] }} fs-8"></i>
                                        <span>{{ $log['type_label'] }}</span>
                                    </span>
                                </td>
                                <td>
                                    <span class="font-monospace fw-bold text-dark fs-8">{{ $log['reference_no'] }}</span>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark d-block">{{ $log['party_name'] }}</span>
                                    <small class="text-muted">{{ $log['party_role'] }} @if($log['party_phone']) &bull; {{ $log['party_phone'] }} @endif</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border px-2 py-1 fs-8">
                                        {{ $log['location'] }}
                                    </span>
                                </td>
                                <td class="text-end font-monospace">
                                    @if($log['weight_kg'] > 0)
                                        <span class="fw-bold text-dark">{{ number_format($log['weight_kg'], 2) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end font-monospace">
                                    @if($log['amount'] > 0)
                                        <span class="fw-bold text-dark">৳{{ number_format($log['amount'], 2) }}</span>
                                        @if($log['due_amount'] > 0)
                                            <small class="text-danger d-block fs-8">Due: ৳{{ number_format($log['due_amount'], 2) }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end font-monospace">
                                    @if($log['inflow'] > 0)
                                        <span class="fw-bold text-success">+৳{{ number_format($log['inflow'], 2) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end font-monospace">
                                    @if($log['outflow'] > 0)
                                        <span class="fw-bold text-danger">-৳{{ number_format($log['outflow'], 2) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="small text-secondary fw-semibold">{{ $log['operator'] }}</span>
                                </td>
                                <td class="text-center pe-4">
                                    @if($log['view_url'] && $log['view_url'] !== 'javascript:void(0)')
                                        <div class="dropdown">
                                            <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                                <li>
                                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ $log['view_url'] }}">
                                                        <i class="fe fe-eye text-info"></i>
                                                        <span>View Details</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    @else
                                        <span class="text-muted opacity-50">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <i class="fe fe-inbox fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                    <h6 class="fw-bold text-dark">No Activity or Transaction Logs Found</h6>
                                    <p class="small text-muted mb-0">Try selecting a wider date range or clearing your filter criteria.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($paginatedLogs->hasPages())
            <div class="card-footer bg-white py-3 px-4 border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="small text-muted">
                    Page {{ $paginatedLogs->currentPage() }} of {{ $paginatedLogs->lastPage() }}
                </span>
                <div>
                    {{ $paginatedLogs->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
    function setDateRange(range) {
        const today = new Date();
        let fromDate = new Date();
        let toDate = new Date();

        if (range === 'today') {
            fromDate = today;
            toDate = today;
        } else if (range === 'yesterday') {
            fromDate.setDate(today.getDate() - 1);
            toDate.setDate(today.getDate() - 1);
        } else if (range === 'last7') {
            fromDate.setDate(today.getDate() - 6);
            toDate = today;
        } else if (range === 'thisMonth') {
            fromDate = new Date(today.getFullYear(), today.getMonth(), 1);
            toDate = today;
        } else if (range === 'lastMonth') {
            fromDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            toDate = new Date(today.getFullYear(), today.getMonth(), 0);
        } else if (range === 'allTime') {
            fromDate = new Date(2020, 0, 1);
            toDate = today;
        }

        const formatDate = (d) => {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        document.getElementById('fromDateInput').value = formatDate(fromDate);
        document.getElementById('toDateInput').value = formatDate(toDate);
        document.getElementById('filterForm').submit();
    }
</script>
@endpush
