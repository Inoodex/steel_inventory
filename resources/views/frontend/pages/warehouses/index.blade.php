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
        background-color: #fcfbff !important;
    }
    .badge-soft-success {
        background-color: rgba(25, 135, 84, 0.12) !important;
        color: #198754 !important;
        font-weight: 600;
    }
    .badge-soft-danger {
        background-color: rgba(220, 53, 69, 0.12) !important;
        color: #dc3545 !important;
        font-weight: 600;
    }
    .badge-soft-primary {
        background-color: rgba(118, 56, 255, 0.12) !important;
        color: #7638ff !important;
        font-weight: 600;
    }
    .badge-soft-info {
        background-color: rgba(13, 202, 240, 0.12) !important;
        color: #0dcaf0 !important;
        font-weight: 600;
    }
    .badge-soft-warning {
        background-color: rgba(255, 193, 7, 0.15) !important;
        color: #b58105 !important;
        font-weight: 600;
    }
    .search-box-custom input {
        border-radius: 8px;
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
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Stockyards & Warehouses</h4>
                <p class="text-muted small mb-0">Manage steel depot yards, cutting plots, storage locations, and yard capacities</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addWarehouseModal">
                    <i class="fe fe-plus-circle fs-6"></i>
                    <span>Add Stockyard / Warehouse</span>
                </button>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Summary Stats Bar -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-map-pin fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Stockyards</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalWarehouses ?? $warehouses->total()) }}</h4>
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
                        <h6 class="text-muted fw-normal mb-1">Active Yards</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($activeWarehouses ?? 0) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-disc fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Live Coils Stored</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalCoilsStored ?? 0) }} Coils</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-warning-light text-warning rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-layers fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Stock Weight</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalYardWeightTon ?? 0, 2) }} MT</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Summary Stats Bar -->

    <!-- Warehouses Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <!-- Search & Counter Header -->
        <div class="card-header bg-white py-3 border-bottom border-light">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-6 col-lg-5">
                    <form action="{{ route('warehouses.index') }}" method="GET" class="search-box-custom d-flex gap-2">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control border-light-subtle" 
                                placeholder="Search yard name, code, location, contact..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary px-3">
                                <i class="fe fe-search"></i>
                            </button>
                            @if(request()->filled('search'))
                                <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary px-3 d-flex align-items-center">
                                    <i class="fe fe-x"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
                <div class="col-12 col-md-6 col-lg-7 text-md-end text-muted small">
                    Showing <span class="fw-bold text-dark">{{ $warehouses->count() }}</span> of {{ $warehouses->total() }} stockyard entries
                </div>
            </div>
        </div>

        <!-- Table Body -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4">Code</th>
                            <th>Stockyard / Depot Name</th>
                            <th>Location / Address</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th class="text-center">Stored Coils</th>
                            <th class="text-center">Inward / Dispatches</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($warehouses as $wh)
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1 fs-8">
                                        {{ $wh->code ?? 'WH-'.$wh->id }}
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <span class="fw-bold text-dark fs-6 d-block">{{ $wh->name }}</span>
                                        @if($wh->capacity_ton)
                                            <small class="text-muted fs-7">Capacity: {{ number_format($wh->capacity_ton, 1) }} MT</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        <i class="fe fe-map-pin text-secondary me-1"></i>{{ $wh->location ?? 'Sitakunda Yard' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-medium text-dark small">{{ $wh->contact_person ?? 'Yard Manager' }}</span>
                                </td>
                                <td>
                                    <span class="text-muted small">{{ $wh->phone ?? 'N/A' }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-soft-primary px-3 py-2 rounded-pill fs-7">
                                        <i class="fe fe-disc me-1"></i> {{ $wh->coils_count ?? 0 }} Coils
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border px-2 py-1 fs-8">
                                        {{ $wh->purchases_count }} Inward | {{ $wh->sales_count }} Dispatches
                                    </span>
                                </td>
                                <td>
                                    @if($wh->status === 'active')
                                        <span class="badge badge-soft-success px-3 py-2 rounded-pill fs-7">
                                            <i class="fe fe-check-circle me-1"></i> Active
                                        </span>
                                    @else
                                        <span class="badge badge-soft-danger px-3 py-2 rounded-pill fs-7">
                                            <i class="fe fe-x-circle me-1"></i> Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <a href="javascript:void(0)" class="btn-action-icon shadow-none" 
                                           data-bs-toggle="dropdown" 
                                           data-bs-popper-config='{"strategy":"fixed"}' 
                                           aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#editWarehouseModal{{ $wh->id }}">
                                                    <i class="fe fe-edit text-primary"></i>
                                                    <span>Edit Stockyard</span>
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider opacity-50"></li>
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" href="javascript:void(0)"
                                                    onclick="if (confirm('Are you sure you want to delete this stockyard?')) { document.getElementById('delete-wh-{{ $wh->id }}').submit(); }">
                                                    <i class="fe fe-trash-2"></i>
                                                    <span>Delete</span>
                                                </a>
                                                <form id="delete-wh-{{ $wh->id }}" action="{{ route('warehouses.destroy', $wh->id) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <div class="avatar avatar-xxl bg-light rounded-circle text-secondary mb-3 d-inline-flex align-items-center justify-content-center">
                                        <i class="fe fe-map-pin fs-1 opacity-50"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">No stockyards or warehouses found</h5>
                                    <p class="text-muted small mb-3">Click "Add Stockyard / Warehouse" to add your first steel yard depot location.</p>
                                    <button type="button" class="btn btn-primary btn-sm px-3 rounded-2" data-bs-toggle="modal" data-bs-target="#addWarehouseModal">
                                        <i class="fe fe-plus-circle me-1"></i> Add Stockyard
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($warehouses->hasPages())
                <div class="p-3 border-top d-flex justify-content-end">
                    {{ $warehouses->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Edit Modals (Outside table container to prevent DOM/overflow issues) -->
    @foreach($warehouses as $wh)
        <div class="modal fade" id="editWarehouseModal{{ $wh->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-3">
                    <div class="modal-header bg-light py-3 border-bottom">
                        <h5 class="modal-title fw-bold text-dark">Edit Stockyard / Warehouse</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('warehouses.update', $wh->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary mb-1">Yard / Depot Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control border-light-subtle" value="{{ $wh->name }}" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Yard Code</label>
                                    <input type="text" name="code" class="form-control border-light-subtle" value="{{ $wh->code }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Status <span class="text-danger">*</span></label>
                                    <select name="status" class="form-select border-light-subtle" required>
                                        <option value="active" {{ $wh->status === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ $wh->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary mb-1">Location / Address</label>
                                <input type="text" name="location" class="form-control border-light-subtle" value="{{ $wh->location }}">
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Contact Person</label>
                                    <input type="text" name="contact_person" class="form-control border-light-subtle" value="{{ $wh->contact_person }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Phone Number</label>
                                    <input type="text" name="phone" class="form-control border-light-subtle" value="{{ $wh->phone }}">
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label small fw-semibold text-secondary mb-1">Capacity (Metric Tons)</label>
                                <input type="number" step="0.001" name="capacity_ton" class="form-control border-light-subtle" value="{{ $wh->capacity_ton }}" placeholder="e.g. 5000">
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-light">
                            <button type="button" class="btn btn-light px-4 rounded-3 text-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4 rounded-3 shadow-sm">Update Stockyard</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Add Warehouse Modal (Outside table container to prevent DOM/overflow issues) -->
    <div class="modal fade" id="addWarehouseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header bg-light py-3 border-bottom">
                    <h5 class="modal-title fw-bold text-dark">Add New Stockyard / Warehouse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('warehouses.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Yard / Depot Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control border-light-subtle" placeholder="e.g. Main Steel Yard, Sitakunda Plot 14" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">Yard Code</label>
                                <input type="text" name="code" class="form-control border-light-subtle" placeholder="e.g. WH-YARD1">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select border-light-subtle" required>
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Location / Address</label>
                            <input type="text" name="location" class="form-control border-light-subtle" placeholder="e.g. Sitakunda, Chittagong">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">Contact Person</label>
                                <input type="text" name="contact_person" class="form-control border-light-subtle" placeholder="Yard Manager">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">Phone Number</label>
                                <input type="text" name="phone" class="form-control border-light-subtle" placeholder="017XXXXXXXX">
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-semibold text-secondary mb-1">Capacity (Metric Tons)</label>
                            <input type="number" step="0.001" name="capacity_ton" class="form-control border-light-subtle" placeholder="e.g. 5000">
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-light px-4 rounded-3 text-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 rounded-3 shadow-sm">Save Stockyard</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
