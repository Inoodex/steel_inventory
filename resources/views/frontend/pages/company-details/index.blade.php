@extends('frontend.layouts.app')

@push('styles')
<style>
    .table-responsive {
        overflow: visible !important;
    }

    .dropdown-menu {
        z-index: 1060 !important;
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

    .table-custom tbody tr:hover {
        background-color: #fcfbff !important;
    }

    .badge-soft-success {
        background-color: rgba(25, 135, 84, 0.12) !important;
        color: #198754 !important;
        font-weight: 600;
    }

    .badge-soft-primary {
        background-color: rgba(118, 56, 255, 0.12) !important;
        color: #7638ff !important;
        font-weight: 600;
    }

    .badge-soft-info {
        background-color: rgba(13, 202, 240, 0.12) !important;
        color: #0aa2c0 !important;
        font-weight: 600;
    }

    .search-box-custom input {
        border-radius: 8px;
    }

    .table-custom th,
    .table-custom td {
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
                <h4 class="card-title fw-bold text-dark mb-1">Company Details</h4>
                <p class="text-muted small mb-0">Manage corporate entities, legal addresses, BIN/TIN registrations, logos, and billing defaults</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#add-company-modal">
                    <i class="fe fe-plus-circle fs-6"></i>
                    <span>Add Company Details</span>
                </button>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Summary Stats Bar -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-briefcase fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Total Entities</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($companies->count()) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-file-text fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Tax / BIN Registered</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($companies->filter(fn($c) => !empty($c->bin_number) || !empty($c->tin_number))->count()) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 col-12">
            <div class="card stat-card bg-white shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-star fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-normal mb-1">Default Billing Company</h6>
                        <h5 class="mb-0 fw-bold text-dark text-truncate" style="max-width: 220px;" title="{{ $companies->firstWhere('is_default', true)?->company_name }}">
                            {{ $companies->firstWhere('is_default', true)?->company_name ?? 'None' }}
                        </h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Summary Stats Bar -->

    <!-- Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <!-- Search Controls -->
        <div class="card-header bg-white py-3 border-bottom border-light">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-6 col-lg-5">
                    <div class="search-box-custom">
                        <input type="text" id="companySearchInput" class="form-control border-light-subtle" placeholder="Search company name, BIN, phone, email, address...">
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-7 text-md-end text-muted small">
                    Showing <span id="visibleCompanyCount" class="fw-bold text-dark">{{ $companies->count() }}</span> of {{ $companies->count() }} companies
                </div>
            </div>
        </div>

        <!-- Table Body -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0" id="companyTable">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Company &amp; Tagline</th>
                            <th>Tax &amp; Trade Identifiers</th>
                            <th>Contact Information</th>
                            <th>Currency</th>
                            <th>Default</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse ($companies as $company)
                        @php
                        $searchString = strtolower(($company->company_name ?? '') . ' ' . ($company->tagline ?? '') . ' ' . ($company->bin_number ?? '') . ' ' . ($company->tin_number ?? '') . ' ' . ($company->phone ?? '') . ' ' . ($company->email ?? '') . ' ' . ($company->address ?? ''));
                        @endphp
                        <tr class="company-row" data-search="{{ $searchString }}">
                            <td class="ps-4 text-muted fw-semibold">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if ($company->logo_path && file_exists(public_path($company->logo_path)))
                                        <img src="{{ asset($company->logo_path) }}" alt="Logo" class="rounded border" style="width: 32px; height: 32px; object-fit: contain;">
                                    @endif
                                    <div>
                                        <span class="fw-bold text-dark d-block" title="{{ $company->company_name }}">
                                            {{ $company->company_name }}
                                        </span>
                                        @if ($company->tagline)
                                            <small class="text-muted fs-8">{{ Str::limit($company->tagline, 35) }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1 small">
                                    @if ($company->bin_number)
                                        <span><strong class="text-secondary">BIN:</strong> <code class="text-dark">{{ $company->bin_number }}</code></span>
                                    @endif
                                    @if ($company->tin_number || $company->tax_number)
                                        <span><strong class="text-secondary">TIN:</strong> <code class="text-dark">{{ $company->tin_number ?: $company->tax_number }}</code></span>
                                    @endif
                                    @if ($company->trade_license)
                                        <small class="text-muted"><i class="fe fe-file-text me-1"></i>{{ $company->trade_license }}</small>
                                    @endif
                                    @if (!$company->bin_number && !$company->tin_number && !$company->trade_license)
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column small">
                                    @if ($company->phone)
                                        <span class="text-dark"><i class="fe fe-phone me-1 text-muted"></i>{{ $company->phone }}</span>
                                    @endif
                                    @if ($company->email)
                                        <small class="text-muted"><i class="fe fe-mail me-1 text-muted"></i>{{ $company->email }}</small>
                                    @endif
                                    @if ($company->address)
                                        <small class="text-muted text-truncate" style="max-width: 200px;" title="{{ $company->address }}"><i class="fe fe-map-pin me-1 text-muted"></i>{{ $company->address }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1 fs-7">
                                    {{ $company->currency_symbol ?? '৳' }} {{ $company->currency_code ?? 'BDT' }}
                                </span>
                            </td>
                            <td>
                                @if ($company->is_default)
                                    <span class="badge badge-soft-success px-3 py-2 rounded-pill fs-7">
                                        <i class="fe fe-star me-1"></i> Default
                                    </span>
                                @else
                                    <form action="{{ route('company-details.set-default', $company->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 fs-7">
                                            Set Default
                                        </button>
                                    </form>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                        @if(!$company->is_default)
                                        <li>
                                            <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="javascript:void(0)"
                                                onclick="document.getElementById('setDefaultForm{{ $company->id }}').submit();">
                                                <i class="fe fe-star text-warning"></i>
                                                <span>Set as Default</span>
                                            </a>
                                            <form id="setDefaultForm{{ $company->id }}" action="{{ route('company-details.set-default', $company->id) }}" method="POST" class="d-none">
                                                @csrf
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider opacity-50 my-1"></li>
                                        @endif
                                        <li>
                                            <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#edit-company-modal{{ $company->id }}">
                                                <i class="fe fe-edit text-primary"></i>
                                                <span>Edit Company</span>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider opacity-50 my-1"></li>
                                        <li>
                                            <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" href="javascript:void(0)"
                                                onclick="if (confirm('Are you sure you want to delete this company profile?')) { document.getElementById('deleteCompany{{ $company->id }}').submit(); }">
                                                <i class="fe fe-trash-2 text-danger"></i>
                                                <span>Delete Company</span>
                                            </a>
                                            <form id="deleteCompany{{ $company->id }}" action="{{ route('company-details.destroy', $company->id) }}" method="POST" class="d-none">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr id="emptyStateRow">
                            <td colspan="7" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center justify-content-center">
                                    <div class="avatar avatar-xl bg-primary-light text-primary rounded-circle mb-3 d-flex align-items-center justify-content-center">
                                        <i class="fe fe-briefcase fs-1"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">No Company Details Found</h5>
                                    <p class="text-muted small mb-3">Create company details to manage legal entities and signatories for invoices & bills</p>
                                    <button type="button" class="btn btn-primary btn-sm px-3 rounded-2" data-bs-toggle="modal" data-bs-target="#add-company-modal">
                                        Add Company Details
                                    </button>
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

<!-- Add Company Details Modal (Outside table container) -->
<div class="modal fade" id="add-company-modal" aria-hidden="true" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light py-3 border-bottom">
                <h5 class="modal-title fw-bold text-dark">Add Company Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('company-details.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small text-secondary">Company / Entity Name <span class="text-danger">*</span></label>
                            <input type="text" name="company_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Tagline / Business Slogan</label>
                            <input type="text" name="tagline" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">BIN / VAT Registration #</label>
                            <input type="text" name="bin_number" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">TIN / Tax Number</label>
                            <input type="text" name="tin_number" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Trade License #</label>
                            <input type="text" name="trade_license" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Primary Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Alternate Phone</label>
                            <input type="text" name="alternate_phone" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Email Address</label>
                            <input type="email" name="email" class="form-control">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold small text-secondary">Registered Office / Yard Address</label>
                            <textarea name="address" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">City</label>
                            <input type="text" name="city" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">State / Division</label>
                            <input type="text" name="state" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Postal Code</label>
                            <input type="text" name="postal_code" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Country</label>
                            <input type="text" name="country" class="form-control" value="Bangladesh">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Website URL</label>
                            <input type="text" name="website" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Currency Symbol</label>
                            <input type="text" name="currency_symbol" class="form-control" value="৳">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Currency Code</label>
                            <input type="text" name="currency_code" class="form-control" value="BDT">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Company Logo</label>
                            <input type="file" name="logo_path" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Authorized Signature Image</label>
                            <input type="file" name="signature_path" class="form-control" accept="image/*">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Default Invoice Notes</label>
                            <textarea name="invoice_notes" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Terms &amp; Conditions</label>
                            <textarea name="terms_and_conditions" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check mt-1">
                                <input type="checkbox" name="is_default" value="1" class="form-check-input" id="add_is_default">
                                <label class="form-check-label fw-semibold small text-dark" for="add_is_default">
                                    Set as Default Billing Company
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 p-3 border-top bg-light">
                    <button type="button" class="btn btn-light px-4 rounded-3 text-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-3 shadow-sm">Save Company</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Company Details Modals (Outside table container) -->
@foreach ($companies as $company)
<div class="modal fade" id="edit-company-modal{{ $company->id }}" aria-hidden="true" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light py-3 border-bottom">
                <h5 class="modal-title fw-bold text-dark">Edit Company Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('company-details.update', $company->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small text-secondary">Company / Entity Name <span class="text-danger">*</span></label>
                            <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $company->company_name) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Tagline / Business Slogan</label>
                            <input type="text" name="tagline" class="form-control" value="{{ old('tagline', $company->tagline) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">BIN / VAT Registration #</label>
                            <input type="text" name="bin_number" class="form-control" value="{{ old('bin_number', $company->bin_number) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">TIN / Tax Number</label>
                            <input type="text" name="tin_number" class="form-control" value="{{ old('tin_number', $company->tin_number ?: $company->tax_number) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Trade License #</label>
                            <input type="text" name="trade_license" class="form-control" value="{{ old('trade_license', $company->trade_license) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Primary Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $company->phone) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Alternate Phone</label>
                            <input type="text" name="alternate_phone" class="form-control" value="{{ old('alternate_phone', $company->alternate_phone) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Email Address</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $company->email) }}">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold small text-secondary">Registered Office / Yard Address</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address', $company->address) }}</textarea>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">City</label>
                            <input type="text" name="city" class="form-control" value="{{ old('city', $company->city) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">State / Division</label>
                            <input type="text" name="state" class="form-control" value="{{ old('state', $company->state) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Postal Code</label>
                            <input type="text" name="postal_code" class="form-control" value="{{ old('postal_code', $company->postal_code) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Country</label>
                            <input type="text" name="country" class="form-control" value="{{ old('country', $company->country ?: 'Bangladesh') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Website URL</label>
                            <input type="text" name="website" class="form-control" value="{{ old('website', $company->website) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Currency Symbol</label>
                            <input type="text" name="currency_symbol" class="form-control" value="{{ old('currency_symbol', $company->currency_symbol ?: '৳') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Currency Code</label>
                            <input type="text" name="currency_code" class="form-control" value="{{ old('currency_code', $company->currency_code ?: 'BDT') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Company Logo</label>
                            <input type="file" name="logo_path" class="form-control" accept="image/*">
                            @if ($company->logo_path && file_exists(public_path($company->logo_path)))
                                <div class="mt-2">
                                    <img src="{{ asset($company->logo_path) }}" style="max-height: 40px;" alt="Logo Preview">
                                </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Authorized Signature Image</label>
                            <input type="file" name="signature_path" class="form-control" accept="image/*">
                            @if ($company->signature_path && file_exists(public_path($company->signature_path)))
                                <div class="mt-2">
                                    <img src="{{ asset($company->signature_path) }}" style="max-height: 40px;" alt="Signature Preview">
                                </div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Default Invoice Notes</label>
                            <textarea name="invoice_notes" class="form-control" rows="2">{{ old('invoice_notes', $company->invoice_notes) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Terms &amp; Conditions</label>
                            <textarea name="terms_and_conditions" class="form-control" rows="2">{{ old('terms_and_conditions', $company->terms_and_conditions) }}</textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check mt-1">
                                <input type="checkbox" name="is_default" value="1" class="form-check-input" id="edit_is_default_{{ $company->id }}" {{ $company->is_default ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold small text-dark" for="edit_is_default_{{ $company->id }}">
                                    Set as Default Billing Company
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 p-3 border-top bg-light">
                    <button type="button" class="btn btn-light px-4 rounded-3 text-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-3 shadow-sm">Update Company</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('companySearchInput');
        const rows = document.querySelectorAll('.company-row');
        const visibleCountSpan = document.getElementById('visibleCompanyCount');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
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
