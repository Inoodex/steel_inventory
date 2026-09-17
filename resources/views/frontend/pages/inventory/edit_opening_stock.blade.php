@extends('frontend.layouts.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: 38px !important;
            border: 1px solid #ced4da !important;
            border-radius: 6px !important;
            display: flex !important;
            align-items: center !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }
        .stat-badge-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
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
                        <h4 class="card-title fw-bold text-dark mb-0">Edit Opening Stock Coil</h4>
                        <span class="badge bg-primary-light text-primary fs-7 fw-bold px-3 py-1 rounded-pill">
                            #{{ $coil->coil_number }}
                        </span>
                        <span class="badge bg-info-light text-info fs-8 fw-semibold px-2 py-1 rounded-pill">
                            Opening Stock
                        </span>
                    </div>
                    <p class="text-muted small mb-0">Update physical specifications, weight, yard allocation, and cost valuation</p>
                </div>
                <div>
                    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                        <i class="fe fe-arrow-left"></i>
                        <span>Back to Inventory</span>
                    </a>
                </div>
            </div>
        </div>
        <!-- /Page Header -->

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fe fe-alert-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong class="d-block mb-1"><i class="fe fe-alert-circle me-2"></i>Please resolve the following input issues:</strong>
                <ul class="mb-0 ps-3 small">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @php
            $consumedWeight = max(0, (float)$coil->net_weight - (float)$coil->remaining_weight);
            $initialWeight = (float)$coil->net_weight;
            $remainingWeight = (float)$coil->remaining_weight;
            $pctRemaining = $initialWeight > 0 ? round(($remainingWeight / $initialWeight) * 100, 1) : 0;
        @endphp

        <!-- Stock Status Overview Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 stat-badge-card">
            <div class="card-body p-3 p-md-4">
                <div class="row g-3 text-center text-md-start align-items-center">
                    <div class="col-lg-3 col-sm-6 col-12 border-end">
                        <span class="text-muted small d-block">Initial Registered Weight</span>
                        <h5 class="fw-bold text-dark mb-0">{{ number_format($initialWeight, 2) }} <small class="text-muted fs-8">kg</small></h5>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-12 border-end">
                        <span class="text-muted small d-block">Sold / Consumed Weight</span>
                        <h5 class="fw-bold {{ $consumedWeight > 0 ? 'text-danger' : 'text-muted' }} mb-0">
                            {{ number_format($consumedWeight, 2) }} <small class="text-muted fs-8">kg</small>
                        </h5>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-12 border-end">
                        <span class="text-muted small d-block">Current Available Stock</span>
                        <h5 class="fw-bold text-success mb-0">
                            {{ number_format($remainingWeight, 2) }} <small class="text-muted fs-8">kg</small>
                            <span class="badge bg-success-light text-success fs-8 rounded-pill ms-1">{{ $pctRemaining }}%</span>
                        </h5>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-12">
                        <span class="text-muted small d-block">Current Status</span>
                        <span class="badge {{ $coil->status == 'in_stock' ? 'bg-success text-white' : ($coil->status == 'in_processing' ? 'bg-warning text-dark' : 'bg-secondary text-white') }} px-3 py-1 rounded-pill mt-1">
                            {{ ucfirst(str_replace('_', ' ', $coil->status)) }}
                        </span>
                    </div>
                </div>

                @if($consumedWeight > 0)
                    <div class="alert alert-warning py-2 px-3 mt-3 mb-0 rounded-2 border-0 d-flex align-items-center gap-2 small">
                        <i class="fe fe-alert-triangle flex-shrink-0"></i>
                        <span>This coil has already had <strong>{{ number_format($consumedWeight, 2) }} kg</strong> sold or consumed. The net weight cannot be reduced below this amount.</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Edit Form -->
        <form action="{{ route('inventory.opening-stock.update', $coil->id) }}" method="POST" id="editOpeningStockForm">
            @csrf
            @method('PUT')

            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fe fe-edit-2 text-primary me-2"></i>Opening Stock Specifications & Physical Specs
                    </h6>
                </div>
                <div class="card-body p-4">
                    <!-- Row 1: Warehouse & Lot Allocation -->
                    <div class="row g-3 mb-3">
                        <div class="col-lg-6 col-md-6 col-12">
                            <label class="form-label fw-bold small text-dark mb-1">
                                Warehouse / Stockyard <span class="text-danger">*</span>
                            </label>
                            <select name="warehouse_id" class="form-select select2" required>
                                <option value="">Select Warehouse</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->id }}" {{ (string)old('warehouse_id', $coil->warehouse_id) === (string)$wh->id ? 'selected' : '' }}>
                                        {{ $wh->name }} {{ $wh->location ? '('.$wh->location.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-6 col-md-6 col-12">
                            <label class="form-label fw-bold small text-dark mb-1">
                                Lot / Consignment Allocation (Optional)
                            </label>
                            <select name="lot_id" class="form-select select2">
                                <option value="">None / Auto Opening Stock</option>
                                @foreach($lots as $lot)
                                    <option value="{{ $lot->id }}" {{ (string)old('lot_id', $coil->lot_id) === (string)$lot->id ? 'selected' : '' }}>
                                        {{ $lot->lot_number }} {{ $lot->name ? '- '.$lot->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Row 2: Tag & Physical Dimensions -->
                    <div class="row g-3 mb-3">
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label fw-bold small text-dark mb-1">
                                Coil / Batch Tag Number <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="coil_number" class="form-control" value="{{ old('coil_number', $coil->coil_number) }}" required />
                        </div>
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label fw-bold small text-dark mb-1">
                                Thickness <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="thickness" class="form-control" value="{{ old('thickness', $coil->thickness) }}" placeholder="e.g. 15, 0.45 mm" required />
                        </div>
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label fw-bold small text-dark mb-1">
                                Width / Size <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="width" class="form-control" value="{{ old('width', $coil->width) }}" placeholder="e.g. 1220, 3ft" required />
                        </div>
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label fw-bold small text-dark mb-1">
                                Size Type / Length Unit <span class="text-danger">*</span>
                            </label>
                            <select name="length" class="form-select" required>
                                @php $currentLength = old('length', $coil->length ?: 'ft'); @endphp
                                <option value="ft" {{ $currentLength == 'ft' ? 'selected' : '' }}>Feet (ft)</option>
                                <option value="mm" {{ $currentLength == 'mm' ? 'selected' : '' }}>Millimeter (mm)</option>
                                <option value="inch" {{ $currentLength == 'inch' ? 'selected' : '' }}>Inch (in)</option>
                                <option value="Coil" {{ $currentLength == 'Coil' ? 'selected' : '' }}>Coil</option>
                                <option value="Plate" {{ $currentLength == 'Plate' ? 'selected' : '' }}>Plate</option>
                                <option value="Standard" {{ $currentLength == 'Standard' ? 'selected' : '' }}>Standard</option>
                            </select>
                        </div>
                    </div>

                    <!-- Row 3: Quantities, Weight & Valuation -->
                    <div class="row g-3 mb-3">
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label fw-bold small text-dark mb-1">
                                Piece Count (Qty) <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="any" min="0.01" name="piece_count" id="fieldPieceCount" class="form-control text-end" value="{{ old('piece_count', (float)$coil->piece_count) }}" required />
                        </div>
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label fw-bold small text-dark mb-1">
                                Total Net Weight (kg) <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="any" min="{{ max(0.01, $consumedWeight) }}" name="net_weight" id="fieldNetWeight" class="form-control text-end" value="{{ old('net_weight', (float)$coil->net_weight) }}" required />
                            @if($consumedWeight > 0)
                                <small class="text-muted fs-8">Min allowable: {{ number_format($consumedWeight, 2) }} kg</small>
                            @endif
                        </div>
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label fw-bold small text-dark mb-1">
                                Cost Rate (৳/kg)
                            </label>
                            <input type="number" step="any" min="0" name="rate_per_ton" id="fieldRate" class="form-control text-end" value="{{ old('rate_per_ton', (float)$coil->rate_per_ton) }}" />
                        </div>
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label fw-bold small text-dark mb-1">
                                Total Valuation (৳)
                            </label>
                            @php
                                $totalVal = (float)old('net_weight', $coil->net_weight) * (float)old('rate_per_ton', $coil->rate_per_ton);
                            @endphp
                            <input type="text" id="fieldTotalValuation" class="form-control text-end bg-light fw-bold text-success" readonly value="{{ number_format($totalVal, 2) }}" />
                        </div>
                    </div>

                    <!-- Row 4: Notes / Remarks -->
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold small text-dark mb-1">
                                Notes / Origin Remarks
                            </label>
                            <textarea name="notes" rows="3" class="form-control" placeholder="Enter any notes, origins, heat numbers, or inventory comments...">{{ old('notes', $coil->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="card-footer bg-light border-top py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-3">
                        <i class="fe fe-x me-1"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 shadow d-inline-flex align-items-center gap-2 fw-semibold">
                        <i class="fe fe-check-circle"></i>
                        <span>Update Opening Stock</span>
                    </button>
                </div>
            </div>
        </form>

    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({ width: '100%' });

    function recalcTotal() {
        const weight = parseFloat($('#fieldNetWeight').val()) || 0;
        const rate = parseFloat($('#fieldRate').val()) || 0;
        const total = weight * rate;
        $('#fieldTotalValuation').val(total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }

    $('#fieldNetWeight, #fieldRate').on('input', recalcTotal);
});
</script>
@endpush
