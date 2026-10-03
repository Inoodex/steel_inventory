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
    .badge-soft-warning {
        background-color: rgba(255, 193, 7, 0.15) !important;
        color: #b58105 !important;
        font-weight: 600;
    }
    .badge-soft-primary {
        background-color: rgba(118, 56, 255, 0.12) !important;
        color: #7638ff !important;
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
                <h4 class="card-title fw-bold text-dark mb-1">Shop Stock &amp; Inventory Report</h4>
                <p class="text-muted small mb-0">Live stock valuation, thickness breakdown, and coil inventory for <strong>{{ $shop->name }}</strong></p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('shops.stock.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-danger px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-download fs-6"></i>
                    <span>Export Stock PDF</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Quick Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 stat-card bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-database fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Inventory Items</small>
                        <h5 class="fw-bold text-dark mb-0">{{ number_format($totalItems) }} <span class="text-muted fs-7 fw-normal">({{ number_format($totalPieces) }} pcs)</span></h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 stat-card bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-warning-light text-warning rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-anchor fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Available Weight</small>
                        <h5 class="fw-bold text-dark mb-0">{{ number_format($totalWeightTon, 3) }} <span class="text-muted fs-7 fw-normal">MT ({{ number_format($totalWeightKg, 1) }} kg)</span></h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 stat-card bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Live Stock Valuation</small>
                        <h5 class="fw-bold text-success mb-0">৳{{ number_format($totalValuation, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 stat-card bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-layers fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Avg Valuation Rate</small>
                        @php
                            $avgRate = $totalWeightKg > 0 ? ($totalValuation / $totalWeightKg) * 1000 : 0;
                        @endphp
                        <h5 class="fw-bold text-dark mb-0">৳{{ number_format($avgRate, 2) }} <span class="text-muted fs-7 fw-normal">/ MT</span></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thickness Summary Accordion / Section -->
    @if($thicknessBreakdown->count() > 0)
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom border-light">
            <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="fe fe-layers text-primary"></i>
                <span>Thickness-Wise Live Summary</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4">Thickness</th>
                            <th class="text-center">Items Count</th>
                            <th class="text-center">Pieces</th>
                            <th class="text-end">Weight (Kg)</th>
                            <th class="text-end">Weight (MT)</th>
                            <th class="text-end">Total Valuation (৳)</th>
                            <th class="text-end pe-4">Avg Rate / Ton (৳)</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        @foreach($thicknessBreakdown as $row)
                            <tr>
                                <td class="ps-4"><span class="fw-bold text-dark">{{ $row['thickness'] }}</span></td>
                                <td class="text-center"><span class="badge bg-light text-dark border">{{ $row['count'] }}</span></td>
                                <td class="text-center">{{ number_format($row['pieces']) }}</td>
                                <td class="text-end fw-bold text-dark">{{ number_format($row['weight'], 2) }} kg</td>
                                <td class="text-end fw-bold text-primary">{{ number_format($row['weight_ton'], 3) }} MT</td>
                                <td class="text-end fw-bold text-success">৳{{ number_format($row['valuation'], 2) }}</td>
                                <td class="text-end pe-4">৳{{ number_format($row['avg_rate'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Filters & Main Inventory Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom border-light">
            <form method="GET" action="{{ route('shops.stock.index') }}" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select class="form-select bg-light border-light-subtle" name="thickness" onchange="this.form.submit()">
                        <option value="">All Thicknesses</option>
                        @foreach($availableThicknesses as $th)
                            <option value="{{ $th }}" {{ request('thickness') == $th ? 'selected' : '' }}>{{ $th }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select bg-light border-light-subtle" name="status" onchange="this.form.submit()">
                        <option value="">Active (In-Stock/Processing)</option>
                        <option value="in_stock" {{ request('status') == 'in_stock' ? 'selected' : '' }}>In Stock Only</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="sold" {{ request('status') == 'sold' ? 'selected' : '' }}>Sold Out</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control bg-light border-light-subtle" name="search" value="{{ request('search') }}" placeholder="Coil #, size, thickness...">
                        <button type="submit" class="btn btn-secondary"><i class="fe fe-search"></i></button>
                    </div>
                </div>
                <div class="col-auto">
                    @if(request()->hasAny(['shop_id', 'thickness', 'status', 'search']))
                        <a href="{{ route('shops.stock.index') }}" class="btn btn-light px-3 rounded-3 text-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4">Coil / Item #</th>
                            <th>Shop / Branch</th>
                            <th>Thickness</th>
                            <th>Width / Size</th>
                            <th>Length</th>
                            <th class="text-center">Pieces</th>
                            <th class="text-end">Rem. Weight (Kg)</th>
                            <th class="text-end">Rate / Ton (৳)</th>
                            <th class="text-end">Valuation (৳)</th>
                            <th class="text-center pe-4">Status</th>
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
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="fe fe-home text-primary me-1"></i> {{ $coil->warehouse?->name ?? 'Shop' }}
                                    </span>
                                </td>
                                <td><span class="fw-bold text-dark">{{ $coil->thickness }}</span></td>
                                <td>{{ $coil->width }}</td>
                                <td>{{ $coil->length ?: 'N/A' }}</td>
                                <td class="text-center">{{ number_format($coil->piece_count ?: 1) }}</td>
                                <td class="text-end fw-bold text-dark">{{ number_format($coil->remaining_weight, 2) }} kg</td>
                                <td class="text-end">৳{{ number_format($coil->rate_per_ton * 1000, 2) }}</td>
                                <td class="text-end fw-bold text-success">৳{{ number_format($val, 2) }}</td>
                                <td class="text-center pe-4">
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
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <div class="mb-2"><i class="fe fe-box fs-1 text-secondary opacity-50"></i></div>
                                    <h6 class="fw-bold">No Stock Coils Found in Selected Shops</h6>
                                    <p class="small text-muted mb-0">Record a purchase or stock intake to assign inventory.</p>
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
    </div>

</div>
@endsection
