@extends('frontend.layouts.app')

@section('content')
<div class="content container-fluid">

    <!-- Page Header (No Breadcrumbs) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Customer Account Statement &amp; Ledger</h4>
                <p class="text-muted small mb-0">Chronological transaction history, invoices, payments, and running receivable balance</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('customers.ledger.pdf', array_merge(['id' => $customer->id], request()->all())) }}" target="_blank" class="btn btn-primary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="fe fe-download"></i>
                    <span>Download Statement PDF</span>
                </a>
                <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-user"></i>
                    <span>Customer Profile</span>
                </a>
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Customer Bio Info & Filter Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-5 col-12">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-xl rounded-circle bg-primary-light text-primary fw-bold fs-4 d-flex align-items-center justify-content-center flex-shrink-0">
                            {{ strtoupper(substr($customer->name, 0, 2)) }}
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">{{ $customer->name }}</h5>
                            <p class="text-muted small mb-0">
                                @if($customer->company)<span class="fw-semibold text-dark">{{ $customer->company }}</span> &bull; @endif
                                <span>{{ $customer->phone }}</span>
                                @if($customer->email) &bull; <span>{{ $customer->email }}</span>@endif
                            </p>
                            <small class="text-secondary">{{ $customer->address }}</small>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7 col-12">
                    <form method="GET" action="{{ route('customers.ledger', $customer->id) }}" class="row g-2 align-items-end justify-content-lg-end">
                        <div class="col-sm-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">From Date</label>
                            <input type="date" name="from_date" class="form-control form-control-sm border-light-subtle rounded-3" value="{{ request('from_date', $fromDate) }}">
                        </div>
                        <div class="col-sm-4 col-6">
                            <label class="form-label small text-secondary fw-semibold mb-1">To Date</label>
                            <input type="date" name="to_date" class="form-control form-control-sm border-light-subtle rounded-3" value="{{ request('to_date', $toDate) }}">
                        </div>
                        <div class="col-sm-4 col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-dark w-100 rounded-3 py-2">
                                <i class="fe fe-filter me-1"></i>Filter
                            </button>
                            <a href="{{ route('customers.ledger', $customer->id) }}" class="btn btn-sm btn-outline-secondary rounded-3 py-2 px-3" title="Reset Filters">
                                <i class="fe fe-refresh-cw"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-secondary-light text-secondary rounded-3 me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-clock fs-5"></i>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Opening Balance</span>
                        <h5 class="mb-0 fw-bold text-dark">৳{{ number_format($openingBalance, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-primary-light text-primary rounded-3 me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-arrow-up-right fs-5"></i>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Total Invoiced (Debit)</span>
                        <h5 class="mb-0 fw-bold text-primary">৳{{ number_format($totalDebit, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100 mb-0">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-success-light text-success rounded-3 me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-arrow-down-left fs-5"></i>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Total Received (Credit)</span>
                        <h5 class="mb-0 fw-bold text-success">৳{{ number_format($totalCredit, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 h-100 mb-0 {{ $closingBalance > 0 ? 'bg-danger-subtle border border-danger-subtle' : 'bg-success-subtle border border-success-subtle' }}">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md {{ $closingBalance > 0 ? 'bg-danger text-white' : 'bg-success text-white' }} rounded-3 me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-5"></i>
                    </div>
                    <div>
                        <span class="{{ $closingBalance > 0 ? 'text-danger' : 'text-success' }} fw-semibold small d-block">Net Outstanding Due</span>
                        <h5 class="mb-0 fw-bold {{ $closingBalance > 0 ? 'text-danger' : 'text-success' }}">৳{{ number_format($closingBalance, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ledger Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="fe fe-list text-primary"></i>
                <span>Statement Details</span>
            </h6>
            <span class="badge bg-light text-secondary border px-3 py-1 rounded-pill">
                {{ count($ledgerRows) }} Transactions
            </span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4" style="width: 50px;">#</th>
                            <th style="width: 120px;">Date</th>
                            <th style="width: 180px;">Transaction &amp; Ref</th>
                            <th>Description</th>
                            <th class="text-end" style="width: 140px;">Debit (+Due)</th>
                            <th class="text-end" style="width: 140px;">Credit (-Paid)</th>
                            <th class="pe-4 text-end" style="width: 160px;">Running Balance</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        <!-- Opening Balance Row -->
                        <tr class="bg-light-subtle">
                            <td class="ps-4 text-muted fw-semibold">-</td>
                            <td class="text-muted small">{{ $fromDate ? date('d M Y', strtotime($fromDate)) : 'Opening' }}</td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 rounded-2">
                                    OPENING BALANCE
                                </span>
                            </td>
                            <td class="text-muted small">Initial / Brought Forward Receivable Balance</td>
                            <td class="text-end text-muted">-</td>
                            <td class="text-end text-muted">-</td>
                            <td class="pe-4 text-end fw-bold text-dark">৳{{ number_format($openingBalance, 2) }}</td>
                        </tr>

                        @forelse($ledgerRows as $index => $row)
                            <tr>
                                <td class="ps-4 text-muted fw-semibold">{{ $index + 1 }}</td>
                                <td class="fw-semibold text-dark">{{ date('d M Y', strtotime($row['date'])) }}</td>
                                <td>
                                    <span class="badge bg-{{ $row['badge'] }}-subtle text-{{ $row['badge'] }} border px-2 py-1 rounded-2 fw-semibold">
                                        {{ $row['type'] }}
                                    </span>
                                    <div class="small mt-1">
                                        @if($row['url'])
                                            <a href="{{ $row['url'] }}" class="text-primary fw-medium text-decoration-none">
                                                {{ $row['ref'] }}
                                            </a>
                                        @else
                                            <span class="text-secondary">{{ $row['ref'] }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="text-dark small">{{ $row['description'] }}</span>
                                </td>
                                <td class="text-end fw-semibold {{ $row['debit'] > 0 ? 'text-primary' : 'text-muted' }}">
                                    {{ $row['debit'] > 0 ? '৳' . number_format($row['debit'], 2) : '-' }}
                                </td>
                                <td class="text-end fw-semibold {{ $row['credit'] > 0 ? 'text-success' : 'text-muted' }}">
                                    {{ $row['credit'] > 0 ? '৳' . number_format($row['credit'], 2) : '-' }}
                                </td>
                                <td class="pe-4 text-end fw-bold {{ $row['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                                    ৳{{ number_format($row['balance'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fe fe-info fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                    No transactions recorded for this customer in the selected date range.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-light border-top">
                        <tr class="fw-bold text-dark">
                            <td colspan="4" class="ps-4 text-end text-uppercase fs-7">Totals &amp; Net Closing Balance:</td>
                            <td class="text-end text-primary">৳{{ number_format($totalDebit, 2) }}</td>
                            <td class="text-end text-success">৳{{ number_format($totalCredit, 2) }}</td>
                            <td class="pe-4 text-end fs-6 {{ $closingBalance > 0 ? 'text-danger' : 'text-success' }}">
                                ৳{{ number_format($closingBalance, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
