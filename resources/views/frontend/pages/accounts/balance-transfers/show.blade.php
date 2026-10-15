@extends('frontend.layouts.app')

@push('styles')
<style>
    .voucher-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }
    .flow-display {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        color: #ffffff;
        border-radius: 12px;
        padding: 24px;
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
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h4 class="card-title fw-bold text-dark mb-0">Balance Transfer Voucher</h4>
                    <span class="badge bg-light text-primary border font-monospace px-2.5 py-1">{{ $transfer->journal_no }}</span>
                    @if($transfer->status === 'reversed')
                        <span class="badge badge-soft-danger px-2.5 py-1 rounded-pill">Reversed</span>
                    @else
                        <span class="badge badge-soft-success px-2.5 py-1 rounded-pill">Executed / Posted</span>
                    @endif
                </div>
                <p class="text-muted small mb-0">Internal contra fund transfer record and General Ledger posting details</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('balance-transfers.index') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-arrow-left"></i>
                    <span>Back to List</span>
                </a>
                <a href="{{ route('balance-transfers.pdf', $transfer->id) }}" target="_blank" class="btn btn-outline-danger px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                    <i class="fe fe-file-text"></i>
                    <span>Export PDF</span>
                </a>
                @if($transfer->status !== 'reversed')
                    <button type="button" class="btn btn-danger px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2" onclick="openReverseModal()">
                        <i class="fe fe-rotate-ccw"></i>
                        <span>Reverse Transfer</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- Main Visual Flow Banner -->
    <div class="flow-display mb-4 shadow-sm p-2">
        <div class="row align-items-center g-3 text-center text-md-start">
            <div class="col-md-4 col-12">
                <span class="text-black-50 small text-uppercase fw-semibold d-block mb-1">Source (Deducted From)</span>
                <h5 class="fw-bold text-balck mb-1">{{ $fromItem?->account?->account_name ?? 'Source Account' }}</h5>
                <span class="badge bg-danger text-balck border border-danger-subtle font-monospace fs-8">
                    {{ $fromItem?->account?->account_code ?? '---' }}
                </span>
            </div>

            <div class="col-md-4 col-12 text-center">
                <span class="text-balck-50 small text-uppercase fw-semibold d-block mb-1">Transferred Amount</span>
                <h2 class="fw-bold text-success mb-0 font-monospace">৳{{ number_format($transfer->total_debit, 2) }}</h2>
                <div class="d-flex align-items-center justify-content-center gap-2 mt-1">
                    <span class="badge bg-dark-subtle text-black border border-secondary fs-8">
                        <i class="fe fe-calendar me-1"></i>{{ $transfer->entry_date ? $transfer->entry_date->format('d M Y') : '—' }}
                    </span>
                </div>
            </div>

            <div class="col-md-4 col-12 text-md-end text-center">
                <span class="text-balck-50 small text-uppercase fw-semibold d-block mb-1">Destination (Credited To)</span>
                <h5 class="fw-bold text-balck mb-1">{{ $toItem?->account?->account_name ?? 'Destination Account' }}</h5>
                <span class="badge bg-success text-balck border border-success-subtle font-monospace fs-8">
                    {{ $toItem?->account?->account_code ?? '---' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Voucher Details Card -->
    <div class="row g-4">
        <div class="col-lg-8 col-12">
            <div class="voucher-card p-4 shadow-sm mb-4">
                <h6 class="fw-bold text-dark mb-3"><i class="fe fe-layers text-primary me-2"></i>Double-Entry Journal Postings</h6>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light fs-8 text-uppercase text-secondary">
                            <tr>
                                <th>Account Code &amp; Title</th>
                                <th>Description / Particulars</th>
                                <th class="text-end">Debit (৳)</th>
                                <th class="text-end">Credit (৳)</th>
                            </tr>
                        </thead>
                        <tbody class="fs-7">
                            @foreach($transfer->items as $item)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $item->account?->account_name }}</div>
                                        <span class="badge bg-light text-secondary border font-monospace fs-8">{{ $item->account?->account_code }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $item->description ?: 'Fund Transfer' }}</span>
                                    </td>
                                    <td class="text-end fw-bold font-monospace {{ $item->debit > 0 ? 'text-success' : 'text-muted' }}">
                                        {{ $item->debit > 0 ? '৳' . number_format($item->debit, 2) : '—' }}
                                    </td>
                                    <td class="text-end fw-bold font-monospace {{ $item->credit > 0 ? 'text-danger' : 'text-muted' }}">
                                        {{ $item->credit > 0 ? '৳' . number_format($item->credit, 2) : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-top bg-light fw-bold">
                            <tr>
                                <td colspan="2" class="text-uppercase text-dark ps-3">Total Equilibrium Balance:</td>
                                <td class="text-end text-success font-monospace">৳{{ number_format($transfer->total_debit, 2) }}</td>
                                <td class="text-end text-danger font-monospace">৳{{ number_format($transfer->total_credit, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($transfer->description)
                    <div class="alert alert-light border rounded-3 mt-4 mb-0 p-3">
                        <span class="small fw-bold text-secondary text-uppercase d-block mb-1">Transfer Remarks / Notes:</span>
                        <p class="mb-0 text-dark small">{{ $transfer->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <!-- Audit & Metadata Card -->
            <div class="voucher-card p-4 shadow-sm mb-4">
                <h6 class="fw-bold text-dark mb-3"><i class="fe fe-info text-primary me-2"></i>Audit Information</h6>
                
                <ul class="list-unstyled mb-0 d-flex flex-column gap-3 small">
                    <li class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Voucher Type:</span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Contra Voucher (Fund Transfer)</span>
                    </li>
                    <li class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Fiscal Year:</span>
                        <span class="fw-semibold text-dark">{{ $transfer->fiscalYear?->year_name ?? date('Y') }}</span>
                    </li>
                    <li class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Created By:</span>
                        <span class="fw-semibold text-dark">{{ $transfer->creator?->name ?? 'System' }}</span>
                    </li>
                    <li class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Created At:</span>
                        <span class="fw-semibold text-dark">{{ $transfer->created_at ? $transfer->created_at->format('d M Y, h:i A') : '—' }}</span>
                    </li>
                    @if($transfer->reversedEntry)
                        <li class="border-top pt-2">
                            <span class="text-danger fw-bold d-block mb-1"><i class="fe fe-alert-circle me-1"></i>Reversal Voucher:</span>
                            <a href="{{ route('journal-entries.show', $transfer->reversedEntry->id) }}" class="fw-bold text-primary font-monospace">
                                #{{ $transfer->reversedEntry->journal_no }}
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

</div>

<!-- Reverse Modal -->
<div class="modal fade" id="reverseTransferModal" tabindex="-1" aria-labelledby="reverseTransferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="reverseTransferModalLabel">
                    <i class="fe fe-rotate-ccw text-danger me-2"></i>Reverse Balance Transfer
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('balance-transfers.reverse', $transfer->id) }}">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted small">
                        Are you sure you want to reverse Contra Voucher <strong class="text-primary font-monospace">{{ $transfer->journal_no }}</strong>?
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
    function openReverseModal() {
        var modal = new bootstrap.Modal(document.getElementById('reverseTransferModal'));
        modal.show();
    }
</script>
@endpush
@endsection
