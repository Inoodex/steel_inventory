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
    .table-custom th, .table-custom td {
        white-space: nowrap;
    }
    .table-responsive {
        overflow: visible !important;
    }
    .dropdown-menu {
        z-index: 1050 !important;
    }
    .badge-soft-success {
        background-color: rgba(25, 135, 84, 0.12) !important;
        color: #198754 !important;
        font-weight: 600;
    }
    .badge-soft-secondary {
        background-color: rgba(108, 117, 125, 0.15) !important;
        color: #6c757d !important;
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
    <!-- Header -->
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title fw-bold text-dark mb-1">Lot Management</h3>
                <p class="text-muted small mb-0">Manage ship breaking procurement lots, intake specifications, and inventory sources</p>
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createLotModal">
                    <i class="fe fe-plus-circle fs-6"></i>
                    <span>Create New Lot</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Stats Bar -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0" style="border-left: 4px solid #fe3727 !important;">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="avatar avatar-lg rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="background-color: rgba(254, 55, 39, 0.12); color: #fe3727; width: 48px; height: 48px;">
                        <i class="fe fe-layers fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-medium d-block mb-1">Total Vessel Lots</span>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalLots ?? $lots->total()) }} <span class="fs-7 fw-normal text-muted">Lots</span></h4>
                        <small class="text-muted fs-8">All registered consignments</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0" style="border-left: 4px solid #16a34a !important;">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="avatar avatar-lg rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="background-color: rgba(22, 163, 74, 0.12); color: #16a34a; width: 48px; height: 48px;">
                        <i class="fe fe-check-circle fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-medium d-block mb-1">Active Intake Lots</span>
                        <h4 class="mb-0 fw-bold text-success">{{ number_format($activeLots ?? 0) }} <span class="fs-7 fw-normal text-muted">Active</span></h4>
                        <small class="text-muted fs-8">Open for procurement intake</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0" style="border-left: 4px solid #0ea5e9 !important;">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="avatar avatar-lg rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="background-color: rgba(14, 165, 233, 0.12); color: #0ea5e9; width: 48px; height: 48px;">
                        <i class="fe fe-database fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-medium d-block mb-1">Consignment Intake Wt</span>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalWeight ?? 0, 2) }} <span class="fs-7 fw-normal text-muted">kg</span></h4>
                        <small class="text-muted fs-8">Total steel weight purchased</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0" style="border-left: 4px solid #f59e0b !important;">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="avatar avatar-lg rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="background-color: rgba(245, 158, 11, 0.12); color: #f59e0b; width: 48px; height: 48px;">
                        <i class="fe fe-dollar-sign fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-medium d-block mb-1">Total Lot Valuation</span>
                        <h4 class="mb-0 fw-bold text-dark">৳ {{ number_format($totalValuation ?? 0, 2) }}</h4>
                        <small class="text-muted fs-8">Cumulative purchase cost</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('lots.index') }}" method="GET" id="lotFilterForm">
                <div class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <div class="position-relative">
                            <input type="text" name="search" id="lotSearchInput" class="form-control rounded-3 pe-4"
                                placeholder="Search Lot Number or Vendor..." value="{{ request('search') }}" autocomplete="off">
                            <span id="lotSearchSpinner" class="spinner-border spinner-border-sm text-primary position-absolute top-50 end-0 translate-middle-y me-3 d-none" role="status"></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="vendor_id" id="filterVendor" class="form-select rounded-3">
                            <option value="">All Vendors</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" id="filterStatus" class="form-select rounded-3">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <input type="date" name="from" id="filterFromDate" class="form-control rounded-3" value="{{ request('from') }}" title="From Date">
                        <input type="date" name="to" id="filterToDate" class="form-control rounded-3" value="{{ request('to') }}" title="To Date">
                    </div>
                    <div class="col-md-1 text-end">
                        <a href="{{ route('lots.index') }}" class="btn btn-outline-secondary w-100 rounded-3" id="resetFilterBtn" title="Reset Filters">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lots Table & Modals Container (AJAX Target) -->
    <div id="lotTableContainer">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0" style="overflow: visible;">
                <div class="table-responsive" style="overflow: visible !important;">
                    <table class="table table-custom align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Lot Number</th>
                                <th>Vendor</th>
                                <th>Lot Date</th>
                                <th class="text-center">Purchases Count</th>
                                <th class="text-end">Total Quantity / Weight</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-center">Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lots as $lot)
                                <tr>
                                    <td class="ps-3 fw-bold">
                                        <a href="{{ route('lots.show', $lot->id) }}" class="text-primary text-decoration-none">
                                            <i class="fe fe-package me-1"></i> {{ $lot->lot_number }}
                                        </a>
                                    </td>
                                    <td>{{ $lot->vendor ? $lot->vendor->name : 'N/A' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($lot->lot_date)->format('d M Y') }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-soft-primary px-2 py-1 rounded-pill">
                                            {{ $lot->purchases->count() }} Orders
                                        </span>
                                    </td>
                                    <td class="text-end fw-semibold text-dark">
                                        {{ number_format($lot->total_quantity, 2) }}
                                    </td>
                                    <td class="text-end fw-bold text-dark">
                                        ৳{{ number_format($lot->total_amount, 2) }}
                                    </td>
                                    <td class="text-center">
                                        @if($lot->status === 'active')
                                            <span class="badge badge-soft-success px-2 py-1 rounded-2">Active</span>
                                        @else
                                            <span class="badge badge-soft-secondary px-2 py-1 rounded-2">Closed</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                                <li>
                                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-success" href="{{ route('purchase.create', ['lot_id' => $lot->id]) }}">
                                                        <i class="fe fe-plus-circle text-success"></i>
                                                        <span>Add Coils</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('lots.show', $lot->id) }}">
                                                        <i class="fe fe-eye text-info"></i>
                                                        <span>View Summary</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#editLotModal{{ $lot->id }}">
                                                        <i class="fe fe-edit text-primary"></i>
                                                        <span>Edit Lot</span>
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider opacity-50"></li>
                                                <li>
                                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" href="javascript:void(0)"
                                                        onclick="if (confirm('Are you sure you want to delete this lot?')) { document.getElementById('deleteLotForm{{ $lot->id }}').submit(); }">
                                                        <i class="fe fe-trash-2 text-danger"></i>
                                                        <span>Delete Lot</span>
                                                    </a>
                                                    <form id="deleteLotForm{{ $lot->id }}" action="{{ route('lots.destroy', $lot->id) }}" method="POST" class="d-none">
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
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <div class="avatar avatar-xl bg-primary-light text-primary rounded-circle mb-3 d-inline-flex align-items-center justify-content-center">
                                            <i class="fe fe-package fs-1"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">No Lots Found</h5>
                                        <p class="text-muted small mb-0">No purchase lots match your search/filter criteria.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($lots->hasPages())
                    <div class="card-footer border-0 bg-transparent py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="text-muted small">
                            Showing {{ $lots->firstItem() }} to {{ $lots->lastItem() }} of {{ $lots->total() }} lots
                        </div>
                        <div>
                            {{ $lots->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Edit Lot Modals -->
        @foreach($lots as $lot)
        <div class="modal fade" id="editLotModal{{ $lot->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0">
                    <form action="{{ route('lots.update', $lot->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header border-bottom-0 pb-0">
                            <h5 class="modal-title fw-bold">Edit Lot: {{ $lot->lot_number }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body py-3">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Lot Number <span class="text-danger">*</span></label>
                                <input type="text" name="lot_number" class="form-control rounded-3" value="{{ $lot->lot_number }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" class="form-select select2-lot-vendor rounded-3" required>
                                    <option value="">Search & Select Vendor</option>
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor->id }}" {{ $lot->vendor_id == $vendor->id ? 'selected' : '' }}>
                                            {{ $vendor->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Lot Date <span class="text-danger">*</span></label>
                                <input type="date" name="lot_date" class="form-control rounded-3" value="{{ \Carbon\Carbon::parse($lot->lot_date)->format('Y-m-d') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select rounded-3" required>
                                    <option value="active" {{ $lot->status == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="closed" {{ $lot->status == 'closed' ? 'selected' : '' }}>Closed</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Notes</label>
                                <textarea name="notes" class="form-control rounded-3" rows="3">{{ $lot->notes }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-top-0 pt-0 gap-2">
                            <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary rounded-3">Update Lot</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Create Lot Modal -->
<div class="modal fade" id="createLotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <form action="{{ route('lots.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Create Purchase Lot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Lot Number (Leave blank to auto-generate)</label>
                        <input type="text" name="lot_number" class="form-control rounded-3">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Vendor <span class="text-danger">*</span></label>
                        <select name="vendor_id" id="createLotVendor" class="form-select select2-lot-vendor rounded-3" required>
                            <option value="">Search & Select Vendor</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Lot Date <span class="text-danger">*</span></label>
                        <input type="date" name="lot_date" class="form-control rounded-3" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select rounded-3" required>
                            <option value="active" selected>Active</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control rounded-3" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 gap-2">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3">Save Lot</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        let lotSearchTimer = null;

        // Initialize Select2 in Modals on open
        $(document).on('shown.bs.modal', '.modal', function () {
            $(this).find('.select2-lot-vendor').select2({
                dropdownParent: $(this),
                width: '100%',
                placeholder: 'Search & Select Vendor'
            });
        });

        function performLotFilter(targetUrl) {
            const spinner = $('#lotSearchSpinner');
            spinner.removeClass('d-none');

            const form = $('#lotFilterForm');
            const url = targetUrl || (form.attr('action') + '?' + form.serialize());

            $('#lotTableContainer').css('opacity', '0.6');

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const newContainer = doc.getElementById('lotTableContainer');
                if (newContainer) {
                    document.getElementById('lotTableContainer').innerHTML = newContainer.innerHTML;
                }

                $('#lotTableContainer').css('opacity', '1');
                spinner.addClass('d-none');
                history.pushState(null, '', url);
            })
            .catch(() => {
                $('#lotTableContainer').css('opacity', '1');
                spinner.addClass('d-none');
            });
        }

        // Live typing search with 300ms debounce
        $('#lotSearchInput').on('input', function () {
            clearTimeout(lotSearchTimer);
            lotSearchTimer = setTimeout(function () {
                performLotFilter();
            }, 300);
        });

        // Instant filter on vendor, status, or date changes
        $('#filterVendor, #filterStatus, #filterFromDate, #filterToDate').on('change', function () {
            performLotFilter();
        });

        // Prevent standard form submission on Enter
        $('#lotFilterForm').on('submit', function (e) {
            e.preventDefault();
            clearTimeout(lotSearchTimer);
            performLotFilter();
        });

        // Reset button AJAX clear
        $('#resetFilterBtn').on('click', function (e) {
            e.preventDefault();
            $('#lotFilterForm')[0].reset();
            performLotFilter("{{ route('lots.index') }}");
        });

        // Intercept AJAX pagination link clicks
        $(document).on('click', '#lotTableContainer .pagination a', function (e) {
            e.preventDefault();
            const pageUrl = $(this).attr('href');
            if (pageUrl) {
                performLotFilter(pageUrl);
            }
        });
    });
</script>
@endpush
