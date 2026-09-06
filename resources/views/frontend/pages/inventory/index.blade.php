@extends('frontend.layouts.app')

@push('styles')
<style>
    .table-responsive {
        overflow: visible !important;
    }
    .dropdown-menu {
        z-index: 1060 !important;
    }
    .table-custom th, .table-custom td {
        white-space: nowrap;
    }
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
    .table-custom tbody tr:hover td {
        background-color: #f8fafc !important;
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
    .badge-soft-danger {
        background-color: rgba(220, 53, 69, 0.12) !important;
        color: #dc3545 !important;
        font-weight: 600;
    }
    .badge-soft-secondary {
        background-color: rgba(108, 117, 125, 0.12) !important;
        color: #6c757d !important;
        font-weight: 600;
    }
    #coilDetailModal .modal-body {
        overflow-x: hidden;
        word-break: break-word;
        overflow-wrap: break-word;
    }
    #coilDetailModal table {
        table-layout: fixed;
        width: 100%;
    }
    #coilDetailModal td, 
    #coilDetailModal th {
        white-space: normal !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
    }
    #coilDetailModal .modal-title {
        word-break: break-word;
        overflow-wrap: break-word;
        max-width: 100%;
    }
    #modalLotBadge {
        white-space: normal !important;
        word-break: break-word !important;
        display: inline-block;
        max-width: 100%;
        text-align: left;
    }
    #coilDetailModal .text-break {
        word-break: break-word !important;
        overflow-wrap: break-word !important;
        white-space: normal !important;
        max-width: 100%;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Steel Inventory</h4>
                <p class="text-muted small mb-0">Unified tracking of ship steel coils &amp; plates, consignment lot sources, stockyard locations &amp; yard valuation</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a class="btn btn-outline-danger px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2" 
                   href="{{ route('inventory.pdf', request()->query()) }}" target="_blank" title="Export current inventory as PDF">
                    <i class="fe fe-file-text fs-6"></i>
                    <span>Export PDF Report</span>
                </a>
                <a href="{{ route('purchase.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-plus-circle"></i>
                    <span>Receive Ship Steel</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Top KPI Metrics Strip (Unified 4-Metric Overview) -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0 p-3" style="border-left: 4px solid #4f46e5 !important;">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-package fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-medium d-block mb-1">Available Yard Weight</span>
                        <h4 class="mb-0 fw-bold text-primary">{{ number_format($totalInStockWeight, 2) }} <small class="text-muted fs-7 fw-normal">kg</small></h4>
                        <small class="text-muted fs-8">Active in-stock ship steel tonnage</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0 p-3" style="border-left: 4px solid #16a34a !important;">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-disc fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-medium d-block mb-1">In-Stock Coils</span>
                        <h4 class="mb-0 fw-bold text-success">{{ number_format($inStockCount) }} <small class="text-muted fs-7 fw-normal">Batches</small></h4>
                        <small class="text-muted fs-8">Ready for dispatch or cutting</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0 p-3" style="border-left: 4px solid #f59e0b !important;">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-warning-light text-warning rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-layers fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-medium d-block mb-1">Total Lifetime Received</span>
                        <h4 class="mb-0 fw-bold text-warning">{{ number_format($totalCoilsCount) }} <small class="text-muted fs-7 fw-normal">Batches</small></h4>
                        <small class="text-muted fs-8">All vessel batches recorded</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0 p-3" style="border-left: 4px solid #0ea5e9 !important;">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-medium d-block mb-1">Stock Valuation</span>
                        <h4 class="mb-0 fw-bold text-dark">৳ {{ number_format($totalValuation, 2) }}</h4>
                        <small class="text-muted fs-8">Estimated live in-stock valuation</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Top KPI Metrics Strip -->

    <!-- Filter & Search Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('inventory.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-xl-4 col-lg-4 col-md-6 col-12">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-light-subtle text-muted"><i class="fe fe-search"></i></span>
                        <input type="text" name="search" class="form-control border-light-subtle" 
                               placeholder="Search Coil #, thickness, size, lot, vendor..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-xl-3 col-lg-2 col-md-3 col-6">
                    <select name="lot_id" class="form-select form-select-sm border-light-subtle">
                        <option value="">All Lots / Vessels</option>
                        @foreach($lots as $lot)
                            <option value="{{ $lot->id }}" {{ request('lot_id') == $lot->id ? 'selected' : '' }}>
                                {{ $lot->lot_number }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <select name="warehouse_id" class="form-select form-select-sm border-light-subtle">
                        <option value="">All Yards</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-2 col-lg-2 col-md-6 col-6">
                    <select name="status" class="form-select form-select-sm border-light-subtle">
                        <option value="in_stock" {{ request('status', 'in_stock') == 'in_stock' ? 'selected' : '' }}>In Stock (Active)</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>In Processing / Cutting</option>
                        <option value="reserved" {{ request('status') == 'reserved' ? 'selected' : '' }}>Reserved</option>
                        <option value="exhausted" {{ request('status') == 'exhausted' ? 'selected' : '' }}>Exhausted / Consumed</option>
                        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Batches (Lifetime)</option>
                    </select>
                </div>
                <div class="col-xl-1 col-lg-2 col-md-6 col-6 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary flex-fill rounded-2" title="Apply Filter">
                        <i class="fe fe-filter"></i> Filter
                    </button>
                    @if(request()->hasAny(['search', 'lot_id', 'warehouse_id', 'status']) && (request('status') != 'in_stock' || request('search') || request('lot_id') || request('warehouse_id')))
                        <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-2" title="Reset Filters">
                            <i class="fe fe-x"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
    <!-- /Filter & Search Card -->

    <!-- Inventory Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0">
                <i class="fe fe-database me-2 text-primary"></i>Live Steel Inventory Registry
            </h6>
            <div class="text-muted small">
                Showing <span class="fw-bold text-dark">{{ $coils->count() }}</span> of {{ $coils->total() }} records
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0" id="inventoryTable">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4" style="width: 50px;">#</th>
                            <th>Coil &amp; Specifications</th>
                            <th>Lot Source &amp; Vendor</th>
                            <th>Warehouse / Yard</th>
                            <th>Available Weight</th>
                            <th style="min-width: 200px;">Remaining Coils &amp; Stock %</th>
                            <th class="text-end pe-4" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse ($coils as $coil)
                            @php
                                $rem = (float) $coil->remaining_weight;
                                $pct = $coil->remaining_percentage;
                                $vendorName = $coil->lot && $coil->lot->vendor ? $coil->lot->vendor->name : ($coil->vendor->name ?? 'N/A');
                                $lotNo = $coil->lot ? $coil->lot->lot_number : 'N/A';
                                $whName = $coil->warehouse ? $coil->warehouse->name : 'Main Yard';
                                $spec = trim(($coil->thickness ? 'Thk: '.$coil->thickness : '') . ' ' . ($coil->width ? 'Size: '.$coil->width . ' ' . ($coil->length ?? 'ft') : ''));
                                $coilData = [
                                    'id' => $coil->id,
                                    'coil_number' => $coil->coil_number,
                                    'created_at' => $coil->created_at->format('d M Y, h:i A'),
                                    'lot_number' => $lotNo,
                                    'lot_url' => $coil->lot ? route('lots.show', $coil->lot->id) : '',
                                    'vendor_name' => $vendorName,
                                    'vendor_url' => $coil->vendor ? route('vendors.show', $coil->vendor->id) : '',
                                    'warehouse_name' => $whName,
                                    'warehouse_location' => $coil->warehouse->location ?? '',
                                    'thickness' => $coil->thickness ?: 'N/A',
                                    'dimensions' => ($coil->width || $coil->length) ? ($coil->width . ($coil->length ? ' × ' . $coil->length : '')) : 'N/A',
                                    'piece_count' => (int)($coil->piece_count ?? 1),
                                    'remaining_coils' => $coil->formatted_remaining_coils,
                                    'unit_weight' => number_format($coil->unit_weight, 2) . ' kg',
                                    'initial_weight' => number_format($coil->initial_weight, 2) . ' kg',
                                    'remaining_weight' => number_format($coil->remaining_weight, 2) . ' kg',
                                    'consumed_weight' => number_format(max(0, $coil->initial_weight - $coil->remaining_weight), 2) . ' kg',
                                    'remaining_pct' => $pct,
                                    'rate_per_ton' => '৳ ' . number_format($coil->rate_per_ton, 2),
                                    'total_price' => '৳ ' . number_format((float)$coil->remaining_weight * (float)$coil->rate_per_ton, 2),
                                    'initial_total' => '৳ ' . number_format($coil->total_price, 2),
                                    'status' => $coil->status,
                                    'status_label' => ucfirst(str_replace('_', ' ', $coil->status)),
                                    'notes' => $coil->notes ?: 'No additional notes recorded for this coil.'
                                ];
                            @endphp
                            <tr>
                                <td class="ps-4 text-muted fw-semibold">{{ $coils->firstItem() + $loop->index }}</td>
                                <td>
                                    <a href="javascript:void(0)" class="fw-bold text-primary text-decoration-none d-block" onclick='openCoilModal(@json($coilData))'>
                                        {{ $coil->coil_number }}
                                    </a>
                                    <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                        @if($coil->thickness)
                                            <span class="badge bg-light text-dark border px-2 py-0 fs-8">Thk: {{ $coil->thickness }}</span>
                                        @endif
                                        @if($coil->width)
                                            <span class="badge bg-light text-secondary border px-2 py-0 fs-8">Size: {{ $coil->width }} {{ $coil->length ?? 'ft' }}</span>
                                        @endif
                                        @if($coil->status !== 'in_stock')
                                            <span class="badge {{ $coil->status == 'in_processing' ? 'badge-soft-warning' : 'badge-soft-secondary' }} px-2 py-0 fs-8">
                                                {{ ucfirst(str_replace('_', ' ', $coil->status)) }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        @if($coil->lot)
                                            <a href="{{ route('lots.show', $coil->lot->id) }}" class="fw-bold text-dark text-decoration-none d-block">
                                                {{ $lotNo }}
                                            </a>
                                        @else
                                            <span class="fw-bold text-dark d-block">{{ $lotNo }}</span>
                                        @endif
                                        <small class="text-muted fs-7"><i class="fe fe-truck me-1"></i>{{ Str::limit($vendorName, 22) }}</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 fs-7">
                                        <i class="fe fe-map-pin text-primary me-1"></i>{{ Str::limit($whName, 18) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $rem > 0 ? 'badge-soft-success' : 'badge-soft-secondary' }} px-3 py-2 rounded-pill fs-7 fw-bold">
                                        <i class="fe {{ $rem > 0 ? 'fe-check-circle' : 'fe-alert-circle' }} me-1"></i> {{ number_format($rem, 2) }} kg
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-light text-dark border px-2 py-0 fs-8 fw-semibold">
                                            <i class="fe fe-disc text-primary me-1"></i>{{ $coil->formatted_remaining_coils }} / {{ $coil->formatted_piece_count }} Coils
                                        </span>
                                        <span class="badge {{ $pct > 50 ? 'badge-soft-success' : ($pct > 20 ? 'badge-soft-warning' : 'badge-soft-danger') }} rounded-pill px-2 py-0 fs-8">
                                            {{ $pct }}% Left
                                        </span>
                                    </div>
                                    <div class="progress mt-2" style="height: 4px; background-color: #e2e8f0;">
                                        <div class="progress-bar {{ $pct > 50 ? 'bg-success' : ($pct > 20 ? 'bg-warning' : 'bg-danger') }}" 
                                             role="progressbar" 
                                             style="width: {{ $pct }}%;" 
                                             aria-valuenow="{{ $pct }}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="javascript:void(0)" onclick='openCoilModal(@json($coilData))'>
                                                <i class="fe fe-eye text-primary"></i>
                                                <span>View Coil Details</span>
                                            </a>

                                            @if($coil->lot_id)
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('lots.show', $coil->lot_id) }}">
                                                    <i class="fe fe-layers text-secondary"></i>
                                                    <span>View Lot Details</span>
                                                </a>
                                            @endif

                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <div class="avatar avatar-xl bg-primary-light text-primary rounded-circle mb-3 d-flex align-items-center justify-content-center">
                                            <i class="fe fe-disc fs-1"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">No Steel Coils Found</h5>
                                        <p class="text-muted small mb-3">No inventory items matched your selected filter criteria.</p>
                                        <div class="d-flex gap-2">
                                            @if(request()->hasAny(['search', 'lot_id', 'warehouse_id', 'status']))
                                                <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-2">
                                                    Clear All Filters
                                                </a>
                                            @endif
                                            <a href="{{ route('purchase.create') }}" class="btn btn-primary btn-sm px-3 rounded-2">
                                                Add Purchase Intake
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($coils->hasPages())
                <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="text-muted small">
                        Showing {{ $coils->firstItem() ?? 0 }} to {{ $coils->lastItem() ?? 0 }} of {{ $coils->total() }} coils
                    </div>
                    <div>
                        {{ $coils->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Coil Details Modal (Outside table card container to prevent clipping / DOM issues) -->
<div class="modal fade" id="coilDetailModal" tabindex="-1" aria-labelledby="coilDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom bg-light">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-md bg-primary-light text-primary rounded-circle d-flex align-items-center justify-content-center">
                        <i class="fe fe-disc fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="modalCoilTitle">Coil Details</h5>
                        <small class="text-muted" id="modalCoilDate">Recorded on —</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- Weight & Stock Progress Strip -->
                <div class="p-3 bg-light rounded-3 border border-light-subtle mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <span class="text-secondary small fw-semibold">Available Stock Weight:</span>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fs-5 fw-bold text-dark" id="modalRemainingWeight">0.00 kg</span>
                            <span class="badge bg-primary text-white rounded-pill px-2 py-1 fs-8" id="modalPercentageBadge">100% Available</span>
                        </div>
                    </div>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-success" role="progressbar" id="modalProgressBar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="row g-2 text-center text-secondary small pt-2 border-top border-light-subtle">
                        <div class="col-4 border-end">
                            <span class="text-muted d-block fs-8">Initial Intake Wt</span>
                            <strong class="text-dark" id="modalInitialWeight">0.00 kg</strong>
                        </div>
                        <div class="col-4 border-end">
                            <span class="text-muted d-block fs-8">Sold / Consumed Wt</span>
                            <strong class="text-danger" id="modalConsumedWeight">0.00 kg</strong>
                        </div>
                        <div class="col-4">
                            <span class="text-muted d-block fs-8">Remaining Pieces</span>
                            <strong class="text-primary" id="modalRemainingPieces">0 / 0 Coils</strong>
                        </div>
                    </div>
                </div>

                <!-- Specifications & Origin Grid -->
                <div class="row g-3 mb-4">
                    <!-- Physical Specifications -->
                    <div class="col-md-6 col-12">
                        <div class="p-3 rounded-3 border h-100 bg-white">
                            <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2 border-bottom pb-2">
                                <i class="fe fe-sliders text-primary"></i>
                                <span>Physical Specifications</span>
                            </h6>
                            <div class="d-flex flex-column gap-2 small">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <span class="text-secondary fw-semibold flex-shrink-0">Thickness:</span>
                                    <span class="text-dark fw-bold text-end text-break" id="modalThickness">N/A</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <span class="text-secondary fw-semibold flex-shrink-0">Dimensions (W × L):</span>
                                    <span class="text-dark fw-bold text-end text-break" id="modalDimensions">N/A</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <span class="text-secondary fw-semibold flex-shrink-0">Total Coils in Batch:</span>
                                    <span class="text-dark fw-bold text-end text-break" id="modalPieceCount">1</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <span class="text-secondary fw-semibold flex-shrink-0">Average Unit Weight:</span>
                                    <span class="text-dark fw-bold text-end text-break" id="modalUnitWeight">0.00 kg/ea</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Commercial & Origin -->
                    <div class="col-md-6 col-12">
                        <div class="p-3 rounded-3 border h-100 bg-white">
                            <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2 border-bottom pb-2">
                                <i class="fe fe-truck text-success"></i>
                                <span>Commercial &amp; Source</span>
                            </h6>
                            <div class="d-flex flex-column gap-2 small">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <span class="text-secondary fw-semibold flex-shrink-0">Vendor / Supplier:</span>
                                    <span class="text-end text-break">
                                        <a href="javascript:void(0)" id="modalVendorLink" class="text-primary fw-bold text-decoration-none">Vendor</a>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <span class="text-secondary fw-semibold flex-shrink-0">Consignment Lot:</span>
                                    <span class="text-end text-break">
                                        <span id="modalLotBadge" class="badge bg-light text-primary border text-break">Lot #</span>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <span class="text-secondary fw-semibold flex-shrink-0">Yard / Warehouse:</span>
                                    <span class="text-dark fw-bold text-end text-break" id="modalWarehouse">Main Yard</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <span class="text-secondary fw-semibold flex-shrink-0">Purchase Rate (KG):</span>
                                    <span class="text-dark fw-bold text-end text-break" id="modalRate">৳ 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <span class="text-secondary fw-semibold flex-shrink-0">Current Valuation:</span>
                                    <span class="text-success fw-bold text-end text-break" id="modalValuation">৳ 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes / Remarks -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">
                        <i class="fe fe-file-text me-1"></i>Coil Notes &amp; Consignment Remarks:
                    </label>
                    <div class="p-3 bg-light rounded-3 border small text-dark" id="modalNotes">
                        No special notes recorded.
                    </div>
                </div>

                <!-- Status Update inside Modal -->
                <div class="p-3 bg-light rounded-3 border">
                    <form id="modalStatusForm" method="POST" action="">
                        @csrf
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <span class="fw-semibold text-dark small d-block">Update Coil Status:</span>
                                <small class="text-muted">Current status: <strong class="text-primary" id="modalCurrentStatus">In Stock</strong></small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <select name="status" id="modalStatusSelect" class="form-select form-select-sm" style="width: 170px;">
                                    <option value="in_stock">In Stock</option>
                                    <option value="processing">In Processing</option>
                                    <option value="reserved">Reserved</option>
                                    <option value="exhausted">Exhausted</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary px-3 rounded-2">Update</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal-footer border-top p-3 bg-light">
                <button type="button" class="btn btn-secondary px-4 py-2 rounded-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openCoilModal(c) {
    if (!c) return;

    document.getElementById('modalCoilTitle').textContent = 'Coil #' + c.coil_number;
    document.getElementById('modalCoilDate').textContent = 'Recorded on ' + c.created_at;

    // Weight & Percentages
    document.getElementById('modalRemainingWeight').textContent = c.remaining_weight;
    document.getElementById('modalInitialWeight').textContent = c.initial_weight;
    document.getElementById('modalConsumedWeight').textContent = c.consumed_weight;
    document.getElementById('modalRemainingPieces').textContent = c.remaining_coils + ' / ' + c.piece_count + ' Coils';
    document.getElementById('modalPercentageBadge').textContent = c.remaining_pct + '% Available';

    const pBar = document.getElementById('modalProgressBar');
    pBar.style.width = c.remaining_pct + '%';
    pBar.className = 'progress-bar ' + (c.remaining_pct > 50 ? 'bg-success' : (c.remaining_pct > 20 ? 'bg-warning' : 'bg-danger'));

    // Physical Specs
    document.getElementById('modalThickness').textContent = c.thickness;
    document.getElementById('modalDimensions').textContent = c.dimensions;
    document.getElementById('modalPieceCount').textContent = c.piece_count + ' Coils';
    document.getElementById('modalUnitWeight').textContent = c.unit_weight;

    // Commercial & Source
    const vLink = document.getElementById('modalVendorLink');
    vLink.textContent = c.vendor_name;
    vLink.href = c.vendor_url || 'javascript:void(0)';

    const lBadge = document.getElementById('modalLotBadge');
    if (lBadge) {
        lBadge.textContent = c.lot_number;
    }

    document.getElementById('modalWarehouse').textContent = c.warehouse_name + (c.warehouse_location ? ' (' + c.warehouse_location + ')' : '');
    document.getElementById('modalRate').textContent = c.rate_per_ton;
    document.getElementById('modalValuation').textContent = c.total_price;
    document.getElementById('modalNotes').textContent = c.notes;

    // Status form
    document.getElementById('modalCurrentStatus').textContent = c.status_label;
    const sSelect = document.getElementById('modalStatusSelect');
    if (sSelect) {
        sSelect.value = c.status;
    }
    const sForm = document.getElementById('modalStatusForm');
    if (sForm) {
        sForm.action = '{{ url("inventory") }}/' + c.id + '/status';
    }

    // Show Modal
    const modalEl = document.getElementById('coilDetailModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}
</script>
@endpush