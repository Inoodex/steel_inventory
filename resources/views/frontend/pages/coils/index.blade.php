@extends('frontend.layouts.app')

@push('styles')
<style>
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
                <h4 class="card-title fw-bold text-dark mb-1"> Steel Coils Registry</h4>
                <p class="text-muted small mb-0">Track dismantled vessel steel coils, marine hull plates, weighbridge tonnage, and yard locations</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('purchase.create') }}" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-plus-circle"></i>
                    <span>Receive Ship Steel / Coils</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Top KPI Metrics Strip -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white" style="border-left: 4px solid #4f46e5 !important;">
                <span class="text-muted small fw-medium d-block mb-1">Available Yard Weight</span>
                <h3 class="mb-0 fw-bold text-primary">{{ number_format($totalInStockWeight, 2) }} <span class="fs-6 fw-normal text-muted">KG</span></h3>
                <small class="text-muted fs-8">Active in-stock ship steel tonnage</small>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white" style="border-left: 4px solid #16a34a !important;">
                <span class="text-muted small fw-medium d-block mb-1">In-Stock Coils</span>
                <h3 class="mb-0 fw-bold text-success">{{ number_format($inStockCount) }} <span class="fs-6 fw-normal text-muted">Units</span></h3>
                <small class="text-muted fs-8">Ready for dispatch or cutting</small>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white" style="border-left: 4px solid #f59e0b !important;">
                <span class="text-muted small fw-medium d-block mb-1">Total Lifetime Received</span>
                <h3 class="mb-0 fw-bold text-warning">{{ number_format($totalCoilsCount) }} <span class="fs-6 fw-normal text-muted">Units</span></h3>
                <small class="text-muted fs-8">All vessel batches recorded</small>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white" style="border-left: 4px solid #0ea5e9 !important;">
                <span class="text-muted small fw-medium d-block mb-1">Stock Valuation</span>
                <h3 class="mb-0 fw-bold text-dark">৳ {{ number_format($totalValuation, 2) }}</h3>
                <small class="text-muted fs-8">Estimated in-stock valuation</small>
            </div>
        </div>
    </div>

    <!-- Filter & Search Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('coils.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-lg-4 col-md-6 col-12">
                    <input type="text" name="search" class="form-control form-control-sm border-light-subtle" 
                        placeholder="Search Coil Tag, Thickness, Vessel Lot..." value="{{ request('search') }}">
                </div>
                <div class="col-lg-3 col-md-3 col-6">
                    <select name="lot_id" class="form-select form-select-sm border-light-subtle">
                        <option value="">All Lots</option>
                        @foreach($lots as $lot)
                            <option value="{{ $lot->id }}" {{ request('lot_id') == $lot->id ? 'selected' : '' }}>
                                {{ $lot->lot_number }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-3 col-6">
                    <select name="warehouse_id" class="form-select form-select-sm border-light-subtle">
                        <option value="">All Yards</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-12 col-12 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary flex-fill rounded-2" title="Apply Filter">
                        <i class="fe fe-filter"></i> Filter
                    </button>
                    @if(request()->hasAny(['search', 'lot_id', 'warehouse_id']))
                        <a href="{{ route('coils.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 px-2" title="Clear Filter">
                            <i class="fe fe-x"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Coils Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-3">Coil Tag</th>
                            <th>Lot Source</th>
                            <th>Vendor</th>
                            <th>Rate (KG)</th>
                            <th>Net Available</th>
                            <th>Yard Location</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coils as $coil)
                            @php
                                $coilPct = $coil->remaining_percentage;
                                $coilData = [
                                    'id' => $coil->id,
                                    'coil_number' => $coil->coil_number,
                                    'created_at' => $coil->created_at->format('d M Y, h:i A'),
                                    'lot_number' => $coil->lot->lot_number ?? 'N/A',
                                    'lot_url' => $coil->lot ? route('lots.show', $coil->lot->id) : '',
                                    'vendor_name' => $coil->vendor->name ?? 'N/A',
                                    'vendor_phone' => $coil->vendor->phone ?? 'N/A',
                                    'vendor_url' => $coil->vendor ? route('vendors.show', $coil->vendor->id) : '',
                                    'warehouse_name' => $coil->warehouse->name ?? 'N/A',
                                    'warehouse_location' => $coil->warehouse->location ?? '',
                                    'thickness' => $coil->thickness ?: 'N/A',
                                    'dimensions' => ($coil->width || $coil->length) ? ($coil->width . ($coil->length ? ' × ' . $coil->length : '')) : 'N/A',
                                    'piece_count' => (int)($coil->piece_count ?? 1),
                                    'remaining_coils' => $coil->formatted_remaining_coils,
                                    'unit_weight' => number_format($coil->unit_weight, 2) . ' kg',
                                    'initial_weight' => number_format($coil->initial_weight, 2) . ' kg',
                                    'remaining_weight' => number_format($coil->remaining_weight, 2) . ' kg',
                                    'consumed_weight' => number_format(max(0, $coil->initial_weight - $coil->remaining_weight), 2) . ' kg',
                                    'remaining_pct' => $coilPct,
                                    'rate_per_ton' => '৳ ' . number_format($coil->rate_per_ton, 2),
                                    'total_price' => '৳ ' . number_format($coil->total_price, 2),
                                    'purchase_id' => $coil->purchase_id ? '#PO-' . $coil->purchase_id : 'N/A',
                                    'notes' => $coil->notes ?: 'No additional notes recorded for this coil.'
                                ];
                            @endphp
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-dark">{{ Str::limit($coil->coil_number, 20) }}</span>
                                        @if(($coil->piece_count ?? 1) > 1)
                                            <span class="badge bg-light text-dark border px-2 py-0 fs-8">{{ (int)$coil->piece_count }} Coils</span>
                                        @endif
                                    </div>
                                    <small class="text-muted d-block" style="font-size: 11px;">{{ $coil->created_at->format('d M Y') }}</small>
                                </td>
                                <td>
                                    @if($coil->lot)
                                        <span class="badge bg-light text-primary border text-decoration-none px-2 py-1 fs-8">
                                            {{ Str::limit($coil->lot->lot_number ?? 'N/A', 20) }}
                                        </span>
                                    @else
                                        <span class="text-muted small">Standard Inward</span>
                                    @endif
                                </td>
                                <td>
                                    {{ Str::limit($coil->vendor->name ?? 'N/A', 20) }}
                                </td>
                                <td>
                                    ৳ {{ number_format($coil->rate_per_ton, 2) }}
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="fw-bold text-dark">{{ number_format($coil->remaining_weight) }} kg</span>
                                        <span class="badge {{ $coilPct > 50 ? 'badge-soft-success' : ($coilPct > 20 ? 'badge-soft-warning' : 'badge-soft-danger') }} rounded-pill px-2 py-0 fs-8">
                                            {{ $coilPct }}%
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-light text-dark border px-2 py-0 fs-8">
                                            {{ $coil->formatted_remaining_coils }} / {{ $coil->formatted_piece_count }} Coils
                                        </span>
                                        @if(($coil->piece_count ?? 1) > 1)
                                            <small class="text-muted" style="font-size: 10px;">({{ number_format($coil->unit_weight, 1) }} kg/ea)</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <i class="fe fe-map-pin me-1 text-primary"></i>{{ Str::limit($coil->warehouse->name, 15) }}
                                </td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-2 py-1 px-2 d-inline-flex align-items-center gap-1"
                                        onclick='openCoilModal(@json($coilData))' title="View Coil Details">
                                        <i class="fe fe-eye"></i>
                                        <span class="d-none d-md-inline">View</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fe fe-disc fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    <p class="mb-1 fw-semibold">No steel coils or plates recorded yet.</p>
                                    <small class="text-muted">Receive your first steel lot in Inward Steel Purchase.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($coils->hasPages())
                <div class="p-3 border-top d-flex justify-content-end">
                    {{ $coils->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Coil Details Modal (outside table to avoid stacking clipping) -->
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
                            <span class="text-muted d-block fs-8">Sold / Dispatched Wt</span>
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
                                        <span id="modalLotBadge" class="badge bg-light text-primary border text-break" style="white-space: normal; word-break: break-all;">Lot #</span>
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
                                    <span class="text-secondary fw-semibold flex-shrink-0">Initial Valuation:</span>
                                    <span class="text-success fw-bold text-end text-break" id="modalValuation">৳ 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes / Remarks -->
                <div class="mb-2">
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
    pBar.style.width = c.remaining_pct + '%';
    pBar.className = 'progress-bar ' + (c.remaining_pct > 50 ? 'bg-success' : (c.remaining_pct > 20 ? 'bg-warning' : 'bg-danger'));

    // Physical Specs
    document.getElementById('modalThickness').textContent = c.thickness;
    document.getElementById('modalDimensions').textContent = c.dimensions;
    document.getElementById('modalPieceCount').textContent = c.piece_count + ' Coils';
    document.getElementById('modalUnitWeight').textContent = c.unit_weight;

    // Commercial
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

    // Show Modal
    const modalEl = document.getElementById('coilDetailModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}
</script>
@endpush

