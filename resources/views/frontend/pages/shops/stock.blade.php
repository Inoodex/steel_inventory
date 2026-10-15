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
    .lot-parent-row {
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .lot-parent-row:hover {
        background-color: #f1f5f9 !important;
    }
    .nested-coil-table th {
        font-size: 11px !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background-color: #f8fafc !important;
    }
    .nested-coil-table td {
        font-size: 12px !important;
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
    .badge-soft-secondary {
        background-color: rgba(108, 117, 125, 0.12) !important;
        color: #6c757d !important;
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
        box-shadow: 0 4px 10px rgba(118, 56, 255, 0.3) !important;
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
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h4 class="card-title fw-bold text-dark mb-0">Shop Stock &amp; Inventory</h4>
                    <span class="badge bg-primary text-white px-2.5 py-1 rounded-pill"><i class="fe fe-shopping-cart me-1"></i>{{ $shop->name }}</span>
                </div>
                <p class="text-muted small mb-0">Live stock valuation, lot sources, thickness breakdown &amp; physical coil registry for retail outlet</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a class="btn btn-outline-danger px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2" 
                   href="{{ route('shops.stock.pdf', request()->query()) }}" target="_blank" title="Export shop inventory as PDF">
                    <i class="fe fe-file-text fs-6"></i>
                    <span>Export PDF Report</span>
                </a>
                <a href="{{ route('shops.index') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back to Shop Hub</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Top KPI Metrics Strip (Matching Normal Stock Inventory) -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0 p-3" style="border-left: 4px solid #4f46e5 !important;">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-package fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-medium d-block mb-1">Available Shop Weight</span>
                        <h4 class="mb-0 fw-bold text-primary">{{ number_format($totalInStockWeight, 2) }} <small class="text-muted fs-7 fw-normal">kg</small></h4>
                        <small class="text-muted fs-8">{{ number_format($totalInStockWeight / 1000, 3) }} MT active retail stock</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0 p-3" style="border-left: 4px solid #16a34a !important;">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-disc fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-medium d-block mb-1">In-Stock Coils &amp; Sheets</span>
                        <h4 class="mb-0 fw-bold text-success">{{ number_format($inStockCount) }} <small class="text-muted fs-7 fw-normal">Batches</small></h4>
                        <small class="text-muted fs-8">Ready for retail POS sale</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0 p-3" style="border-left: 4px solid #0ea5e9 !important;">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-medium d-block mb-1">Live Stock Valuation</span>
                        <h4 class="mb-0 fw-bold text-dark">৳ {{ number_format($totalValuation, 2) }}</h4>
                        <small class="text-muted fs-8">Estimated retail inventory valuation</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Top KPI Metrics Strip -->

    <!-- Filter & Search Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('shops.stock.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-xl-5 col-lg-5 col-md-6 col-12">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-light-subtle text-muted"><i class="fe fe-search"></i></span>
                        <input type="text" name="search" class="form-control border-light-subtle" placeholder="Search coil no, thickness, width, lot..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-xl-3 col-lg-3 col-md-3 col-6">
                    <select name="lot_id" class="form-select form-select-sm border-light-subtle">
                        <option value="">All Consignment Lots</option>
                        @foreach($lots as $lot)
                            <option value="{{ $lot->id }}" {{ request('lot_id') == $lot->id ? 'selected' : '' }}>
                                {{ $lot->lot_number }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <select name="status" class="form-select form-select-sm border-light-subtle">
                        <option value="in_stock" {{ request('status', 'in_stock') == 'in_stock' ? 'selected' : '' }}>In Stock Only</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="reserved" {{ request('status') == 'reserved' ? 'selected' : '' }}>Reserved</option>
                        <option value="exhausted" {{ request('status') == 'exhausted' ? 'selected' : '' }}>Exhausted</option>
                        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Records</option>
                    </select>
                </div>
                <div class="col-xl-2 col-lg-2 col-md-6 col-12 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary flex-fill rounded-2" title="Apply Filter">
                        <i class="fe fe-filter"></i> Filter
                    </button>
                    @if(request()->hasAny(['search', 'lot_id', 'status']) && (request('status') != 'in_stock' || request('search') || request('lot_id')))
                        <a href="{{ route('shops.stock.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-2" title="Reset Filters">
                            <i class="fe fe-x"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Inventory Table Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom border-light d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="avatar avatar-sm bg-primary-light text-primary rounded-circle d-flex align-items-center justify-content-center">
                    <i class="fe fe-layers fs-5"></i>
                </span>
                <div>
                    <h6 class="fw-bold text-dark mb-0">
                        Lot-Wise Shop Inventory &amp; Consignments
                    </h6>
                    <small class="text-muted">Click any lot row to view and expand physical coils &amp; sheets</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark border px-2.5 py-1.5 fs-7">
                    <strong>{{ $lotGroups->count() }}</strong> Lots &bull; <strong>{{ $totalCoilsCount }}</strong> Coils
                </span>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-2 px-2.5 py-1 d-inline-flex align-items-center gap-1" onclick="expandAllLots()">
                    <i class="fe fe-maximize-2 fs-8"></i> Expand All
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 px-2.5 py-1 d-inline-flex align-items-center gap-1" onclick="collapseAllLots()">
                    <i class="fe fe-minimize-2 fs-8"></i> Collapse All
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0" id="inventoryTable">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th style="width: 40px;" class="ps-3 text-center"></th>
                            <th>Lot / Consignment Details</th>
                            <th>Vendor / Supplier</th>
                            <th class="text-center">Coils in Lot</th>
                            <th>Available Weight &amp; Stock %</th>
                            <th class="text-end">Estimated Valuation</th>
                            <th class="text-center pe-3" style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse ($lotGroups as $index => $group)
                            @php
                                $lotKey = $group['lot_key'];
                                $isDirect = ($lotKey === 'direct_stock');
                                $remWt = $group['total_remaining_weight'];
                                $intakeWt = $group['total_intake_weight'];
                                $pct = $group['pct_remaining'];
                                $inStockCount = $group['in_stock_coils'];
                                $totalCoils = $group['total_coils'];
                            @endphp
                            <!-- PARENT LOT ROW -->
                            <tr class="lot-parent-row {{ $remWt > 0 ? '' : 'opacity-75' }}" onclick="toggleLot('{{ $lotKey }}')" id="row-lot-{{ $lotKey }}">
                                <td class="ps-3 text-center" onclick="event.stopPropagation(); toggleLot('{{ $lotKey }}')">
                                    <button type="button" class="btn btn-sm btn-light border p-1 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px;" title="Expand/Collapse">
                                        <i class="fe fe-chevron-right text-primary lot-chevron-{{ $lotKey }} transition-transform" style="transition: transform 0.2s ease;"></i>
                                    </button>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar avatar-sm bg-light text-primary rounded-2 d-flex align-items-center justify-content-center fw-bold">
                                            <i class="fe fe-anchor"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                                <span>{{ $group['lot_number'] }}</span>
                                                @if($isDirect)
                                                    <span class="badge bg-secondary-subtle text-secondary fs-8">Opening Stock</span>
                                                @endif
                                            </div>
                                            <small class="text-muted d-block">Intake: {{ $group['lot_date'] }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fe fe-truck text-muted"></i>
                                        <span class="fw-medium text-dark">{{ $group['vendor_name'] }}</span>
                                    </div>
                                </td>
                                <td class="text-center font-monospace">
                                    <span class="fw-bold text-dark">{{ $inStockCount }}</span>
                                    <small class="text-muted">/ {{ $totalCoils }} Coils</small>
                                    <small class="d-block text-muted" style="font-size: 11px;">({{ $group['in_stock_pieces'] }} pcs)</small>
                                </td>
                                <td>
                                    <div style="min-width: 170px;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold {{ $remWt > 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($remWt, 2) }} kg
                                            </span>
                                            <small class="text-muted">{{ $pct }}%</small>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar {{ $pct > 50 ? 'bg-success' : ($pct > 20 ? 'bg-warning' : 'bg-danger') }}" 
                                                 role="progressbar" style="width: {{ $pct }}%;" 
                                                 aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small class="text-muted d-block mt-0.5" style="font-size: 10.5px;">
                                            Intake: {{ number_format($intakeWt, 2) }} kg
                                        </small>
                                    </div>
                                </td>
                                <td class="text-end font-monospace">
                                    <span class="fw-bold text-dark">৳ {{ number_format($group['total_valuation'], 2) }}</span>
                                    <small class="text-muted d-block" style="font-size: 11px;">
                                        Avg: ৳ {{ $remWt > 0 ? number_format(($group['total_valuation'] / $remWt) * 1000, 2) : '0.00' }}/Ton
                                    </small>
                                </td>
                                <td class="text-center pe-3" onclick="event.stopPropagation();">
                                    <div class="dropdown">
                                        <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('shops.stock.pdf', ['lot_id' => $group['lot_id']]) }}" target="_blank">
                                                <i class="fe fe-file-text text-danger"></i>
                                                <span>Export Lot PDF</span>
                                            </a>
                                            @if($group['lot'])
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('lots.show', $group['lot']->id) }}">
                                                    <i class="fe fe-eye text-primary"></i>
                                                    <span>View Lot Overview</span>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- CHILD DRAWER ROW: NESTED PHYSICAL COILS TABLE -->
                            <tr class="lot-coil-drawer" id="drawer-lot-{{ $lotKey }}" style="display: none;">
                                <td colspan="7" class="p-0 bg-light-subtle">
                                    <div class="p-3 ps-5 border-start border-3 border-primary">
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                            <div>
                                                <span class="fw-bold text-dark fs-7">
                                                    <i class="fe fe-disc text-primary me-1"></i>Physical Steel Coils &amp; Sheets in {{ $group['lot_number'] }}
                                                </span>
                                                <span class="badge bg-secondary-subtle text-secondary ms-2">{{ $group['coils']->count() }} Batches</span>
                                            </div>
                                            <div>
                                                <small class="text-muted fs-8">Click any Coil No to view complete weight specs and stock details</small>
                                            </div>
                                        </div>

                                        <div class="table-responsive bg-white rounded-3 border shadow-sm">
                                            <table class="table table-sm table-hover align-middle mb-0 nested-coil-table">
                                                <thead class="bg-light text-secondary fs-8 text-uppercase">
                                                    <tr>
                                                        <th class="ps-3" style="width: 130px;">Coil No</th>
                                                        <th>Thickness</th>
                                                        <th>Dimensions</th>
                                                        <th class="text-center">Pieces</th>
                                                        <th class="text-end">Intake Wt</th>
                                                        <th class="text-end">Available Wt</th>
                                                        <th class="text-end">Rate (৳/Ton)</th>
                                                        <th>Location</th>
                                                        <th class="text-center">Status</th>
                                                        <th class="text-center pe-3" style="width: 50px;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="fs-7">
                                                    @foreach ($group['coils'] as $coil)
                                                        @php
                                                            $cRem = (float) $coil->remaining_weight;
                                                            $cPct = $coil->remaining_percentage;
                                                            $whName = $coil->warehouse ? $coil->warehouse->name : $shop->name;
                                                            $vendorName = $group['vendor_name'];
                                                            $coilData = [
                                                                'id' => $coil->id,
                                                                'coil_number' => $coil->coil_number,
                                                                'created_at' => $coil->created_at ? $coil->created_at->format('d M Y, h:i A') : '—',
                                                                'lot_number' => $group['lot_number'],
                                                                'lot_url' => $group['lot'] ? route('lots.show', $group['lot']->id) : '',
                                                                'vendor_name' => $vendorName,
                                                                'vendor_url' => $group['vendor'] ? route('vendors.show', $group['vendor']->id) : '',
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
                                                                'remaining_pct' => $cPct,
                                                                'rate_per_ton' => '৳ ' . number_format($coil->rate_per_ton, 2),
                                                                'total_price' => '৳ ' . number_format((float)$coil->remaining_weight * (float)$coil->rate_per_ton, 2),
                                                                'initial_total' => '৳ ' . number_format($coil->total_price, 2),
                                                                'status' => $coil->status,
                                                                'status_label' => ucfirst(str_replace('_', ' ', $coil->status)),
                                                                'notes' => $coil->notes ?: 'No additional notes recorded for this coil.'
                                                            ];
                                                        @endphp
                                                        <tr>
                                                            <td class="ps-3">
                                                                <a href="javascript:void(0)" class="fw-bold text-primary text-decoration-none font-monospace d-inline-flex align-items-center gap-1" onclick='openCoilModal(@json($coilData))'>
                                                                    <i class="fe fe-disc fs-8"></i>
                                                                    <span>{{ $coil->coil_number }}</span>
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-light text-dark border px-2 py-0.5 fs-8">
                                                                    {{ $coil->thickness ?: 'Standard' }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="text-secondary">{{ $coil->width ?: '—' }} {{ $coil->length ? '× ' . $coil->length : '' }}</span>
                                                            </td>
                                                            <td class="text-center font-monospace">
                                                                <span class="fw-semibold">{{ $coil->formatted_remaining_coils }}</span>
                                                                <small class="text-muted">/ {{ $coil->piece_count ?: 1 }}</small>
                                                            </td>
                                                            <td class="text-end text-muted font-monospace">
                                                                {{ number_format($coil->net_weight, 2) }}
                                                            </td>
                                                            <td class="text-end">
                                                                <span class="fw-bold {{ $cRem > 0 ? 'text-success' : 'text-danger' }} font-monospace">
                                                                    {{ number_format($cRem, 2) }} kg
                                                                </span>
                                                            </td>
                                                            <td class="text-end font-monospace text-secondary">
                                                                ৳ {{ number_format($coil->rate_per_ton, 2) }}
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-light text-dark border px-2 py-0.5 fs-8">
                                                                    <i class="fe fe-map-pin text-primary me-1"></i>{{ $whName }}
                                                                </span>
                                                            </td>
                                                            <td class="text-center">
                                                                @if($cRem > 0)
                                                                    <span class="badge badge-soft-success px-2 py-0.5 rounded-pill fs-8">
                                                                        In Stock
                                                                    </span>
                                                                @else
                                                                    <span class="badge badge-soft-secondary px-2 py-0.5 rounded-pill fs-8">
                                                                        Exhausted
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center pe-3">
                                                                <div class="dropdown">
                                                                    <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                                                        <i class="fas fa-ellipsis-v"></i>
                                                                    </a>
                                                                    <div class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="javascript:void(0)" onclick='openCoilModal(@json($coilData))'>
                                                                            <i class="fe fe-eye text-primary"></i>
                                                                            <span>View Specs</span>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <div class="avatar avatar-xl bg-primary-light text-primary rounded-circle mb-3 d-flex align-items-center justify-content-center">
                                            <i class="fe fe-layers fs-1"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">No Shop Stock Found</h5>
                                        <p class="text-muted small mb-3">No inventory items matched your selected filter criteria for {{ $shop->name }}.</p>
                                        <div class="d-flex gap-2">
                                            @if(request()->hasAny(['search', 'lot_id', 'status']))
                                                <a href="{{ route('shops.stock.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-2">
                                                    Clear All Filters
                                                </a>
                                            @endif
                                            <a href="{{ route('shops.purchases.create') }}" class="btn btn-primary btn-sm px-3 rounded-2">
                                                Add Shop Purchase Intake
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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
                                    <span class="text-secondary fw-semibold flex-shrink-0">Location:</span>
                                    <span class="text-dark fw-bold text-end text-break" id="modalWarehouse">{{ $shop->name }}</span>
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
    if (pBar) {
        pBar.style.width = c.remaining_pct + '%';
        pBar.className = 'progress-bar ' + (c.remaining_pct > 50 ? 'bg-success' : (c.remaining_pct > 20 ? 'bg-warning' : 'bg-danger'));
    }

    // Physical Specs
    document.getElementById('modalThickness').textContent = c.thickness;
    document.getElementById('modalDimensions').textContent = c.dimensions;
    document.getElementById('modalPieceCount').textContent = c.piece_count + ' Coils';
    document.getElementById('modalUnitWeight').textContent = c.unit_weight;

    // Commercial & Source
    const vLink = document.getElementById('modalVendorLink');
    if (vLink) {
        vLink.textContent = c.vendor_name;
        vLink.href = c.vendor_url || 'javascript:void(0)';
    }

    const lBadge = document.getElementById('modalLotBadge');
    if (lBadge) {
        lBadge.textContent = c.lot_number;
    }

    document.getElementById('modalWarehouse').textContent = c.warehouse_name + (c.warehouse_location ? ' (' + c.warehouse_location + ')' : '');
    document.getElementById('modalRate').textContent = c.rate_per_ton;
    document.getElementById('modalValuation').textContent = c.total_price;
    document.getElementById('modalNotes').textContent = c.notes;

    // Show Modal
    const modalEl = document.getElementById('coilDetailModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

$(document).ready(function() {
    // Auto-expand if search filter is active
    @if(request()->filled('search') || request()->filled('lot_id'))
        expandAllLots();
    @endif
});

function toggleLot(key) {
    const drawer = document.getElementById('drawer-lot-' + key);
    const chevron = document.querySelector('.lot-chevron-' + key);
    if (!drawer) return;

    if (drawer.style.display === 'none' || drawer.style.display === '') {
        drawer.style.display = 'table-row';
        if (chevron) {
            chevron.style.transform = 'rotate(90deg)';
        }
    } else {
        drawer.style.display = 'none';
        if (chevron) {
            chevron.style.transform = 'rotate(0deg)';
        }
    }
}

function expandAllLots() {
    document.querySelectorAll('.lot-coil-drawer').forEach(function(drawer) {
        drawer.style.display = 'table-row';
    });
    document.querySelectorAll('[class*="lot-chevron-"]').forEach(function(chevron) {
        chevron.style.transform = 'rotate(90deg)';
    });
}

function collapseAllLots() {
    document.querySelectorAll('.lot-coil-drawer').forEach(function(drawer) {
        drawer.style.display = 'none';
    });
    document.querySelectorAll('[class*="lot-chevron-"]').forEach(function(chevron) {
        chevron.style.transform = 'rotate(0deg)';
    });
}
</script>
@endpush
