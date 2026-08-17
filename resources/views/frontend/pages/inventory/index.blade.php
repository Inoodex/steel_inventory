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
                <h4 class="card-title fw-bold text-dark mb-1">Steel Inventory Stock</h4>
                <p class="text-muted small mb-0">Real-time tracking of in-stock steel coils, procurement lot sources, specifications & yard locations</p>
            </div>
            <div>
                <a class="btn btn-outline-danger px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2" href="{{ route('inventory.pdf') }}" target="_blank">
                    <i class="fe fe-file-text fs-6"></i>
                    <span>Export PDF Report</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Summary Stats Bar -->
    @php
        $totalAvailWeight = $coils->sum('remaining_weight');
        $totalIntakeWeight = $coils->sum(fn($c) => $c->initial_weight);
        $totalRemainingCoilCount = $coils->sum(fn($c) => $c->remaining_coils);
        $overallRetentionPct = $totalIntakeWeight > 0 ? round(($totalAvailWeight / $totalIntakeWeight) * 100, 1) : 0;
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-disc fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">In-Stock Coils</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalRemainingCoilCount, 1) }} <small class="text-muted fs-7 fw-normal">({{ number_format($coils->count()) }} batches)</small></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-package fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Available Stock Weight</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalAvailWeight, 2) }} kg</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-layers fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Net Intake Weight</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalIntakeWeight, 2) }} kg</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-warning-light text-warning rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-pie-chart fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Yard Stock Retention</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ $overallRetentionPct }}% <small class="text-muted fs-7 fw-normal">in yard</small></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Summary Stats Bar -->

    <!-- Inventory Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <!-- Search Controls -->
        <div class="card-header bg-white py-3 border-bottom border-light">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-6 col-lg-6">
                    <div class="search-box-custom">
                        <input type="text" id="inventorySearchInput" class="form-control border-light-subtle" placeholder="Search coil no, lot no, vendor, warehouse, specifications...">
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-6 text-md-end text-muted small">
                    Showing <span id="visibleInventoryCount" class="fw-bold text-dark">{{ $coils->count() }}</span> of {{ $coils->count() }} in-stock coils
                </div>
            </div>
        </div>

        <!-- Table Body -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0" id="inventoryTable">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Coil &amp; Specifications</th>
                            <th>Lot Source &amp; Vendor</th>
                            <th>Warehouse</th>
                            <th>Rate (KG)</th>
                            <th>Available Weight</th>
                            <th style="min-width: 190px;">Remaining Coils &amp; Stock %</th>
                            <th>Status</th>
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
                                $searchText = strtolower($coil->coil_number . ' ' . $lotNo . ' ' . $vendorName . ' ' . $whName . ' ' . $spec . ' ' . $coil->formatted_remaining_coils . ' coils');
                            @endphp
                            <tr class="inventory-row" data-search="{{ $searchText }}">
                                <td class="ps-4 text-muted fw-semibold">{{ $loop->iteration }}</td>
                                <td>
                                    <span class="fw-bold text-primary d-block">{{ $coil->coil_number }}</span>
                                    <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                        @if($coil->thickness)
                                            <span class="badge bg-light text-dark border px-2 py-0 fs-8">Thk: {{ $coil->thickness }}</span>
                                        @endif
                                        @if($coil->width)
                                            <span class="badge bg-light text-secondary border px-2 py-0 fs-8">Size: {{ $coil->width }} {{ $coil->length ?? 'ft' }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="fw-bold text-dark d-block">{{ $lotNo }}</span>
                                        <small class="text-muted fs-7"><i class="fe fe-truck me-1"></i>{{ $vendorName }}</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 fs-7">
                                        <i class="fe fe-map-pin text-primary me-1"></i>{{ Str::limit($whName, 18) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark small">৳{{ number_format($coil->rate_per_ton, 2) }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-soft-success px-3 py-2 rounded-pill fs-7 fw-bold">
                                        <i class="fe fe-check-circle me-1"></i> {{ number_format($rem, 2) }} kg
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 mb-2">
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
                                <td>
                                    <span class="badge badge-soft-secondary px-3 py-1 rounded-pill fs-7 text-capitalize">
                                        In Stock
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyStateRow">
                                <td colspan="8" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <div class="avatar avatar-xl bg-primary-light text-primary rounded-circle mb-3 d-flex align-items-center justify-content-center">
                                            <i class="fe fe-disc fs-1"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">No In-Stock Coils Available</h5>
                                        <p class="text-muted small mb-3">Add procurement lots and steel coils via Purchase Intake to populate inventory.</p>
                                        <a href="{{ route('purchase.create') }}" class="btn btn-primary btn-sm px-3 rounded-2">
                                            Add Purchase Intake
                                        </a>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('inventorySearchInput');
    const rows = document.querySelectorAll('.inventory-row');
    const visibleCountSpan = document.getElementById('visibleInventoryCount');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            rows.forEach(row => {
                const rowSearchText = row.dataset.search || '';
                if (query === '' || rowSearchText.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (visibleCountSpan) {
                visibleCountSpan.textContent = visibleCount;
            }
        });
    }
});
</script>
@endpush
@endsection