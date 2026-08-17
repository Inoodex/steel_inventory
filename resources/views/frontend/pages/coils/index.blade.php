@extends('frontend.layouts.app')

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Ship Steel Coils Registry</h4>
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
                <div class="col-lg-3 col-md-6 col-12">
                    <input type="text" name="search" class="form-control form-control-sm border-light-subtle" 
                        placeholder="Search Coil Tag, Vessel Lot..." value="{{ request('search') }}">
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <select name="lot_id" class="form-select form-select-sm border-light-subtle">
                        <option value="">All Lots</option>
                        @foreach($lots as $lot)
                            <option value="{{ $lot->id }}" {{ request('lot_id') == $lot->id ? 'selected' : '' }}>
                                {{ $lot->lot_number }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <select name="warehouse_id" class="form-select form-select-sm border-light-subtle">
                        <option value="">All Yards</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <select name="status" class="form-select form-select-sm border-light-subtle">
                        <option value="">All Status</option>
                        <option value="in_stock" {{ request('status') == 'in_stock' ? 'selected' : '' }}>In Stock</option>
                        <option value="in_processing" {{ request('status') == 'in_processing' ? 'selected' : '' }}>In Processing</option>
                        <option value="exhausted" {{ request('status') == 'exhausted' ? 'selected' : '' }}>Exhausted</option>
                    </select>
                </div>
                <div class="col-lg-1 col-md-12 col-12 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100 rounded-2" title="Apply Filter">
                        <i class="fe fe-filter"></i> Filter
                    </button>
                    @if(request()->hasAny(['search', 'lot_id', 'warehouse_id', 'status']))
                        <a href="{{ route('coils.index') }}" class="btn btn-sm btn-outline-secondary rounded-2" title="Clear Filter">
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
                            <th class="ps-3">Coil</th>
                            <th>Lot Source</th>
                            <th>Thickness</th>
                            <th>Dimensions</th>
                            <th>Vendor</th>
                            <th>Rate (KG)</th>
                            <th>Net Available</th>
                            <th>Yard</th>
                            <th>Status</th>
                            <!-- <th class="text-end pe-3">Action</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coils as $coil)
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-dark">Coil No - {{ $coil->coil_number }}</span>
                                        @if(($coil->piece_count ?? 1) > 1)
                                            <span class="badge bg-light text-dark border px-2 py-0 fs-8">{{ (int)$coil->piece_count }} Coils</span>
                                        @endif
                                    </div>
                                    <small class="text-muted d-block" style="font-size: 11px;">{{ $coil->created_at->format('d M Y') }}</small>
                                </td>
                                <td>
                                    @if($coil->lot)
                                        <span class="badge bg-light text-dark border px-2 py-1 fs-8">
                                            <i class="fe fe-package text-primary me-1"></i>{{ $coil->lot->lot_number ?? 'N/A' }}
                                        </span>
                                    @else
                                        <span class="text-muted small">Standard Inward</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $coil->thickness ?? 'N/A' }}
                                </td>   
                                <td>
                                    <small class="text-dark fw-medium">
                                        {{ $coil->width ?? 'N/A' }} {{ $coil->length ? ' × ' . $coil->length : '' }}
                                    </small>
                                </td>
                                <td>
                                    {{ $coil->vendor ? $coil->vendor->name : 'N/A' }}
                                </td>
                                <td>
                                    ৳ {{ number_format($coil->rate_per_ton, 2) }}
                                </td>
                                <td>
                                    <span class="fw-bold text-dark">{{ number_format($coil->remaining_weight, 2) }} kg</span>
                                    @if(($coil->piece_count ?? 1) > 1)
                                        <small class="text-muted d-block" style="font-size: 11px;">({{ number_format($coil->gross_weight / $coil->piece_count, 2) }} kg/ea)</small>
                                    @endif
                                </td>
                                <td >
                                    <i class="fe fe-map-pin me-1"></i>{{ $coil->warehouse->name }}
                                </td>
                                <td>
                                    @if($coil->status === 'in_stock')
                                        <span class="badge bg-success-subtle text-success px-2 py-1 fs-8">In Stock</span>
                                    @elseif($coil->status === 'in_processing')
                                        <span class="badge bg-warning-subtle text-warning px-2 py-1 fs-8">Processing</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary px-2 py-1 fs-8">Exhausted</span>
                                    @endif
                                </td>
                                <!-- <td class="text-end pe-3">
                                    <div class="dropdown">
                                        <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            <li><h6 class="dropdown-header small text-muted">Change Status</h6></li>
                                            <li>
                                                <form action="{{ route('coils.update_status', $coil->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="status" value="in_stock">
                                                    <button type="submit" class="dropdown-item small text-success">
                                                        <i class="fe fe-check-circle me-1"></i> Mark In Stock
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <form action="{{ route('coils.update_status', $coil->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="status" value="in_processing">
                                                    <button type="submit" class="dropdown-item small text-warning">
                                                        <i class="fe fe-refresh-cw me-1"></i> Mark In Processing
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <form action="{{ route('coils.update_status', $coil->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="status" value="exhausted">
                                                    <button type="submit" class="dropdown-item small text-danger">
                                                        <i class="fe fe-x-circle me-1"></i> Mark Exhausted / Sold
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td> -->
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <i class="fe fe-disc fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    <p class="mb-1 fw-semibold">No ship steel coils or plates recorded yet.</p>
                                    <small class="text-muted">Receive your first vessel steel lot in Inward Steel Purchase.</small>
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
@endsection
