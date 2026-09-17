@extends('frontend.layouts.app')

@section('content')
<div class="content container-fluid">

    <!-- Page Header (No breadcrumbs per project guidelines) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-light text-dark border font-monospace px-2 py-1 fs-8">
                        {{ $warehouse->code ?? 'WH-' . $warehouse->id }}
                    </span>
                    <h4 class="card-title fw-bold text-dark mb-0">Edit Stockyard / Warehouse</h4>
                </div>
                <p class="text-muted small mb-0">Update storage yard location, contact personnel, metric tonnage capacity, and operational parameters</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('warehouses.show', $warehouse->id) }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back to Warehouse</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <div class="row g-4">
        <!-- Main Form Column -->
        <div class="col-xl-8 col-lg-8 col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-edit text-primary me-2"></i>Stockyard Information
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('warehouses.update', $warehouse->id) }}" method="POST" id="editWarehouseForm">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <!-- Stockyard Name -->
                            <div class="col-md-6 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Stockyard / Warehouse Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" class="form-control border-light-subtle @error('name') is-invalid @enderror" 
                                       value="{{ old('name', $warehouse->name) }}" required autocomplete="off">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Yard Identification Code -->
                            <div class="col-md-6 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Yard Code (Identifier)
                                </label>
                                <input type="text" name="code" class="form-control font-monospace border-light-subtle @error('code') is-invalid @enderror" 
                                       value="{{ old('code', $warehouse->code) }}" autocomplete="off">
                                <small class="text-muted fs-8">Unique internal tracking code (e.g. WH-001, YARD-A)</small>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Location / Address -->
                            <div class="col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Physical Location / Yard Address
                                </label>
                                <input type="text" name="location" class="form-control border-light-subtle @error('location') is-invalid @enderror" 
                                       value="{{ old('location', $warehouse->location) }}" autocomplete="off">
                                @error('location')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Contact Person -->
                            <div class="col-md-6 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Yard Manager / Contact Person
                                </label>
                                <input type="text" name="contact_person" class="form-control border-light-subtle @error('contact_person') is-invalid @enderror" 
                                       value="{{ old('contact_person', $warehouse->contact_person) }}" autocomplete="off">
                                @error('contact_person')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Contact Phone -->
                            <div class="col-md-6 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Contact Phone Number
                                </label>
                                <input type="text" name="contact_phone" class="form-control border-light-subtle @error('contact_phone') is-invalid @enderror" 
                                       value="{{ old('contact_phone', $warehouse->contact_phone) }}" autocomplete="off">
                                @error('contact_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Max Capacity (MT) -->
                            <div class="col-md-6 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Storage Capacity (Metric Tons - MT)
                                </label>
                                <div class="input-group">
                                    <input type="number" step="0.1" min="0" name="capacity_ton" class="form-control border-light-subtle @error('capacity_ton') is-invalid @enderror" 
                                           value="{{ old('capacity_ton', $warehouse->capacity_ton) }}">
                                    <span class="input-group-text bg-light border-light-subtle text-muted">MT</span>
                                </div>
                                <small class="text-muted fs-8">Nominal maximum steel holding capacity in Ton</small>
                                @error('capacity_ton')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Operating Status -->
                            <div class="col-md-6 col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Operating Status <span class="text-danger">*</span>
                                </label>
                                <select name="status" class="form-select border-light-subtle @error('status') is-invalid @enderror" required>
                                    <option value="active" {{ old('status', $warehouse->status) === 'active' ? 'selected' : '' }}>
                                        Active (Receiving &amp; Dispatches Open)
                                    </option>
                                    <option value="inactive" {{ old('status', $warehouse->status) === 'inactive' ? 'selected' : '' }}>
                                        Inactive (Suspended / Maintenance)
                                    </option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Operational Notes & Facilities -->
                            <div class="col-12">
                                <label class="form-label small text-secondary fw-semibold mb-1">
                                    Operational Notes, Crane Specs &amp; Facilities
                                </label>
                                <textarea name="notes" rows="4" class="form-control border-light-subtle @error('notes') is-invalid @enderror">{{ old('notes', $warehouse->notes) }}</textarea>
                                <small class="text-muted fs-8">Record crane capacity, scale calibration notes, gate security details, or depot layout</small>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-end gap-3 pt-4 mt-4 border-top">
                            <a href="{{ route('warehouses.show', $warehouse->id) }}" class="btn btn-outline-secondary px-4 py-2 rounded-3">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary px-5 py-2 rounded-3 fw-semibold shadow-sm">
                                Update Warehouse
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Side: Live Inventory & Stats Widget -->
        <div class="col-xl-4 col-lg-4 col-12">
            <!-- Current Status Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h6 class="card-title fw-bold text-dark mb-0">
                        <i class="fe fe-activity text-primary me-2"></i>Live Yard Utilization
                    </h6>
                </div>
                <div class="card-body p-3">
                    @php
                        $inStockCoils = $warehouse->coils()->where('status', 'in_stock')->count();
                        $inStockWeightKg = (float) $warehouse->coils()->where('status', 'in_stock')->sum('remaining_weight');
                        $inStockWeightMT = $inStockWeightKg / 1000;
                        $purchasesCount = $warehouse->purchases()->count();
                        $salesCount = $warehouse->sales()->count();
                    @endphp

                    <div class="d-flex align-items-center justify-content-between p-3 bg-light-subtle rounded-3 mb-2 border">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fe fe-disc text-primary fs-5"></i>
                            <div>
                                <span class="text-dark fw-bold d-block small">Live In-Stock Coils</span>
                                <small class="text-muted">Active physical inventory</small>
                            </div>
                        </div>
                        <span class="badge bg-primary text-white font-monospace fs-7 px-3 py-1 rounded-pill">
                            {{ number_format($inStockCoils) }} Coils
                        </span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between p-3 bg-light-subtle rounded-3 mb-2 border">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fe fe-anchor text-info fs-5"></i>
                            <div>
                                <span class="text-dark fw-bold d-block small">On-Hand Weight</span>
                                <small class="text-muted">{{ number_format($inStockWeightKg, 0) }} kg</small>
                            </div>
                        </div>
                        <span class="badge bg-info text-white font-monospace fs-7 px-3 py-1 rounded-pill">
                            {{ number_format($inStockWeightMT, 2) }} MT
                        </span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between p-3 bg-light-subtle rounded-3 mb-2 border">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fe fe-shopping-cart text-success fs-5"></i>
                            <div>
                                <span class="text-dark fw-bold d-block small">Inward Purchases</span>
                                <small class="text-muted">Lifetime receipts</small>
                            </div>
                        </div>
                        <span class="badge bg-success text-white font-monospace fs-7 px-3 py-1 rounded-pill">
                            {{ $purchasesCount }} Orders
                        </span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between p-3 bg-light-subtle rounded-3 border">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fe fe-truck text-warning fs-5"></i>
                            <div>
                                <span class="text-dark fw-bold d-block small">Outward Dispatches</span>
                                <small class="text-muted">Dispatched sales</small>
                            </div>
                        </div>
                        <span class="badge bg-warning text-dark font-monospace fs-7 px-3 py-1 rounded-pill">
                            {{ $salesCount }} Dispatches
                        </span>
                    </div>
                </div>
            </div>

            <!-- Quick Links Card -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <a href="{{ route('inventory.index', ['warehouse_id' => $warehouse->id]) }}" class="btn btn-outline-primary w-100 rounded-3 d-flex align-items-center justify-content-center gap-2 py-2 mb-2">
                        <i class="fe fe-disc"></i>
                        <span>Explore Yard Inventory</span>
                    </a>
                    <a href="{{ route('warehouses.index') }}" class="btn btn-light w-100 rounded-3 d-flex align-items-center justify-content-center gap-2 py-2">
                        <i class="fe fe-layers"></i>
                        <span>View All Stockyards</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
