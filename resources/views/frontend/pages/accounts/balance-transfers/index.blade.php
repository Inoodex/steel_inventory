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
    .badge-soft-warning {
        background-color: rgba(255, 193, 7, 0.15) !important;
        color: #b58105 !important;
        font-weight: 600;
    }
    .badge-soft-info {
        background-color: rgba(13, 202, 240, 0.12) !important;
        color: #0dcaf0 !important;
        font-weight: 600;
    }
    .fs-8 {
        font-size: 0.8rem;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header (No Breadcrumbs) -->
    <div class="page-header mb-4">
        <div class="content-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="card-title fw-bold text-dark mb-1">Balance &amp; Fund Transfers</h4>
                <p class="text-muted small mb-0">Record and track internal fund transfers between Hand Cash, Bank accounts, and mobile banking</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('balance-transfers.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fe fe-repeat fs-6"></i>
                    <span>New Balance Transfer</span>
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Quick Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 stat-card bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-primary-light text-primary rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-repeat fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Transfers</small>
                        <h5 class="fw-bold text-dark mb-0">{{ number_format($totalTransfersCount) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 stat-card bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-success-light text-success rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-dollar-sign fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Transferred Volume</small>
                        <h5 class="fw-bold text-success mb-0">৳{{ number_format($totalTransferredAmount, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 stat-card bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-info-light text-info rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-calendar fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Today's Transfers</small>
                        <h5 class="fw-bold text-info mb-0">৳{{ number_format($todayTransferredAmount, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 stat-card bg-white mb-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md bg-warning-light text-warning rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fe fe-briefcase fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Liquid Accounts</small>
                        <h5 class="fw-bold text-dark mb-0">{{ $liquidAccounts->count() }} Accounts</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Liquid Balances Overview Pill Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-light p-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <i class="fe fe-activity text-primary fs-5"></i>
                <span class="fw-bold text-dark small">Live Liquid Balances:</span>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($liquidAccounts as $acc)
                    <div class="bg-white border rounded-pill px-3 py-1 shadow-sm d-flex align-items-center gap-2 fs-8">
                        <span class="text-muted">{{ $acc->account_name }}:</span>
                        <strong class="{{ $acc->running_balance >= 0 ? 'text-success' : 'text-danger' }} font-monospace">
                            ৳{{ number_format($acc->running_balance, 2) }}
                        </strong>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Filters & Main Transfers Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom border-light">
            <form method="GET" action="{{ route('balance-transfers.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4 col-12">
                    <div class="input-group">
                        <input type="text" class="form-control bg-light border-light-subtle" name="search" value="{{ request('search') }}" placeholder="Search voucher #, account, notes...">
                        <button type="submit" class="btn btn-primary"><i class="fe fe-search"></i></button>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <input type="date" class="form-control bg-light border-light-subtle" name="from" value="{{ request('from') }}" placeholder="From date">
                </div>
                <div class="col-md-3 col-6">
                    <input type="date" class="form-control bg-light border-light-subtle" name="to" value="{{ request('to') }}" placeholder="To date">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-secondary px-3">Filter</button>
                    @if(request()->hasAny(['search', 'from', 'to']))
                        <a href="{{ route('balance-transfers.index') }}" class="btn btn-light px-3 text-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4">Voucher #</th>
                            <th>Date</th>
                            <th>Source (From)</th>
                            <th class="text-center" style="width: 40px;"></th>
                            <th>Destination (To)</th>
                            <th class="text-end">Amount (৳)</th>
                            <th class="text-center">Status</th>
                            <th>Created By</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        @forelse($transfers as $trf)
                            @php
                                $toItem = $trf->items->firstWhere('debit', '>', 0);
                                $fromItem = $trf->items->firstWhere('credit', '>', 0);
                                $isReversed = $trf->status === 'reversed';
                            @endphp
                            <tr class="{{ $isReversed ? 'opacity-75 bg-light-subtle' : '' }}">
                                <td class="ps-4">
                                    <a href="{{ route('balance-transfers.show', $trf->id) }}" class="fw-bold text-primary font-monospace text-decoration-none">
                                        <i class="fe fe-file-text me-1"></i>{{ $trf->journal_no }}
                                    </a>
                                </td>
                                <td>{{ $trf->entry_date ? $trf->entry_date->format('d M Y') : '—' }}</td>
                                <td>
                                    @if($fromItem && $fromItem->account)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 fs-8">
                                            <i class="fe fe-arrow-up-right me-1"></i>{{ $fromItem->account->account_name }}
                                        </span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td class="text-center text-muted">
                                    <i class="fe fe-arrow-right fs-6"></i>
                                </td>
                                <td>
                                    @if($toItem && $toItem->account)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-8">
                                            <i class="fe fe-arrow-down-left me-1"></i>{{ $toItem->account->account_name }}
                                        </span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold font-monospace fs-7 {{ $isReversed ? 'text-muted text-decoration-line-through' : 'text-success' }}">
                                    ৳{{ number_format($trf->total_debit, 2) }}
                                </td>
                                <td class="text-center">
                                    @if($isReversed)
                                        <span class="badge badge-soft-danger px-2.5 py-1 rounded-pill">Reversed</span>
                                    @else
                                        <span class="badge badge-soft-success px-2.5 py-1 rounded-pill">Executed</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-dark fw-semibold">{{ $trf->creator?->name ?? 'System' }}</small>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <a href="javascript:void(0)" class="btn-action-icon shadow-none" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('balance-transfers.show', $trf->id) }}">
                                                    <i class="fe fe-eye text-primary"></i>
                                                    <span>View Voucher</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('balance-transfers.pdf', $trf->id) }}" target="_blank">
                                                    <i class="fe fe-download text-success"></i>
                                                    <span>Download Voucher</span>
                                                </a>
                                            </li>
                                            @if(!$isReversed)
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" href="javascript:void(0)" onclick="openReverseModal({{ $trf->id }}, '{{ $trf->journal_no }}')">
                                                        <i class="fe fe-rotate-ccw"></i>
                                                        <span>Reverse Transfer</span>
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <div class="mb-2"><i class="fe fe-repeat fs-1 text-secondary opacity-50"></i></div>
                                    <h6 class="fw-bold">No Balance Transfers Found</h6>
                                    <p class="small text-muted mb-3">Transfer liquid balances between Hand Cash and Bank accounts with instant double-entry ledger posting.</p>
                                    <a href="{{ route('balance-transfers.create') }}" class="btn btn-primary btn-sm px-3 rounded-2">
                                        <i class="fe fe-plus-circle me-1"></i> New Balance Transfer
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($transfers->hasPages())
                <div class="card-footer bg-white py-3 border-top border-light">
                    {{ $transfers->links() }}
                </div>
            @endif
        </div>
    </div>

</div>

<!-- Reverse Transfer Modal (Placed outside table container) -->
<div class="modal fade" id="reverseTransferModal" tabindex="-1" aria-labelledby="reverseTransferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="reverseTransferModalLabel">
                    <i class="fe fe-rotate-ccw text-danger me-2"></i>Reverse Balance Transfer
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reverseTransferForm" method="POST" action="">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted small">
                        Are you sure you want to reverse Contra Voucher <strong id="reverseVoucherNo" class="text-primary font-monospace"></strong>?
                        This will automatically generate a counter-reversal journal entry and restore the balances in both accounts.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Reason for Reversal <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Enter justification for reversing this transfer..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-outline-secondary px-3 py-2 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4 py-2 rounded-3 fw-semibold">
                        <i class="fe fe-check-circle me-1"></i>Confirm Reversal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openReverseModal(id, voucherNo) {
        var form = document.getElementById('reverseTransferForm');
        form.action = "{{ url('accounts/balance-transfers') }}/" + id + "/reverse";
        document.getElementById('reverseVoucherNo').textContent = voucherNo;
        var modal = new bootstrap.Modal(document.getElementById('reverseTransferModal'));
        modal.show();
    }
</script>
@endpush
@endsection
