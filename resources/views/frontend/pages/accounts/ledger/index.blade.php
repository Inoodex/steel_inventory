@extends('frontend.layouts.app')

@push('styles')
    <style>
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

        .dropdown-menu {
            z-index: 9999 !important;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">

        <div class="page-header mb-3">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title font-weight-bold" style="color: #1e293b;">General Ledger &amp; Sub-Ledgers</h3>
                </div>
                @if($selectedAccount)
                    <div class="col-auto d-flex align-items-center gap-2">
                        <a href="{{ route('ledger.csv', request()->all()) }}"
                            class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-1">
                            <i class="fas fa-file-excel"></i>
                            <span>Export CSV</span>
                        </a>
                        <a href="{{ route('ledger.pdf', request()->all()) }}"
                            class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1" target="_blank">
                            <i class="fas fa-file-pdf"></i>
                            <span>Export PDF</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Filter Form -->
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 10px;">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('ledger.index') }}" class="row g-2 align-items-end" id="ledgerFilterForm">
                    <div class="col-lg-3 col-md-6 col-12">
                        <label class="form-label small fw-bold text-secondary mb-1">Master Account:</label>
                        <select name="account_id" id="accountSelect" class="form-select form-select-sm">
                            <option value="">-- All Accounts / Auto --</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ $accountId == $acc->id ? 'selected' : '' }}>
                                    [{{ $acc->account_code }}] {{ $acc->account_name }} ({{ strtoupper($acc->account_type) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3 col-6">
                        <label class="form-label small fw-bold text-secondary mb-1">Sub-Ledger Party:</label>
                        <select name="party_type" id="partyTypeSelect" class="form-select form-select-sm" onchange="togglePartySelect()">
                            <option value="">-- None (All Parties) --</option>
                            <option value="customer" {{ $partyType === 'customer' ? 'selected' : '' }}>Customer (Debtor)</option>
                            <option value="vendor" {{ $partyType === 'vendor' ? 'selected' : '' }}>Vendor (Creditor)</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-3 col-6" id="partyIdWrapper" style="{{ $partyType ? '' : 'display: none;' }}">
                        <label class="form-label small fw-bold text-secondary mb-1" id="partyIdLabel">Select Party:</label>
                        <select name="party_id" id="partyIdSelect" class="form-select form-select-sm">
                            <option value="">-- Choose Party --</option>
                            @if($partyType === 'customer')
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" {{ $partyId == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->phone }})</option>
                                @endforeach
                            @elseif($partyType === 'vendor')
                                @foreach($vendors as $v)
                                    <option value="{{ $v->id }}" {{ $partyId == $v->id ? 'selected' : '' }}>{{ $v->name }} ({{ $v->phone }})</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small fw-bold text-secondary mb-1">From Date:</label>
                        <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small fw-bold text-secondary mb-1">To Date:</label>
                        <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
                    </div>
                    <div class="col-lg-2 col-md-4 col-12 d-flex gap-1 ms-auto">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill">
                            <i class="fas fa-search me-1"></i> View
                        </button>
                        <a href="{{ route('ledger.index') }}" class="btn btn-outline-secondary btn-sm px-2" title="Reset">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        @if($selectedAccount)
            <!-- Ledger Summary Header Card -->
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; background-color: #1e293b; color: #ffffff;">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                <span class="badge bg-secondary text-uppercase">{{ $selectedAccount->account_type }}</span>
                                @if($selectedParty)
                                    <span class="badge bg-primary px-2 py-1">
                                        <i class="fe fe-user me-1"></i>{{ ucfirst($partyType) }}: {{ $selectedParty->name }}
                                    </span>
                                @endif
                            </div>
                            <h4 class="text-white fs-5 fw-bold mb-1">[{{ $selectedAccount->account_code }}]
                                {{ $selectedAccount->account_name }}</h4>
                            <p class="text-white-50 mb-0 font-monospace" style="font-size: 12px;">Period:
                                {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} —
                                {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}</p>
                        </div>
                        <div class="col-md-3 text-md-end mt-3 mt-md-0 border-start border-secondary">
                            <span class="text-white-50 text-uppercase fw-semibold" style="font-size: 11px;">Opening
                                Balance</span>
                            <h4 class="text-white fw-bold mt-1 mb-0">৳{{ number_format($openingBalance, 2) }}</h4>
                        </div>
                        <div class="col-md-3 text-md-end mt-3 mt-md-0 border-start border-secondary">
                            <span class="text-white-50 text-uppercase fw-semibold" style="font-size: 11px;">Ending
                                Balance</span>
                            <h3 class="text-success fw-bold mt-1 mb-0">৳{{ number_format($closingBalance, 2) }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ledger Entries Table -->
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background-color: #f8fafc; font-size: 11px; text-transform: uppercase;">
                                <tr>
                                    <th style="width: 120px;" class="ps-3">Date</th>
                                    <th style="width: 140px;">Voucher #</th>
                                    <th>Narration / Details</th>
                                    <th class="text-end" style="width: 130px;">Debit</th>
                                    <th class="text-end" style="width: 130px;">Credit</th>
                                    <th class="text-end" style="width: 150px;">Running Balance</th>
                                    <th class="text-end pe-4" style="width: 80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Opening Balance Row -->
                                <tr style="background-color: #f1f5f9; font-weight: 700;">
                                    <td class="ps-3">{{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }}</td>
                                    <td>-</td>
                                    <td>Opening Balance Brought Forward</td>
                                    <td class="text-end">-</td>
                                    <td class="text-end">-</td>
                                    <td class="text-end text-dark">৳{{ number_format($openingBalance, 2) }}</td>
                                    <td></td>
                                </tr>

                                @php
                                    $running = $openingBalance;
                                    $periodDebit = 0;
                                    $periodCredit = 0;
                                @endphp

                                @forelse($ledgerItems as $item)
                                    @php
                                        $jv = $item->journalEntry;
                                        $d = (float) $item->debit;
                                        $c = (float) $item->credit;
                                        $periodDebit += $d;
                                        $periodCredit += $c;

                                        if ($selectedAccount->isDebitNormal()) {
                                            $running += ($d - $c);
                                        } else {
                                            $running += ($c - $d);
                                        }
                                    @endphp
                                    <tr>
                                        <td class="ps-3">{{ \Carbon\Carbon::parse($jv->entry_date)->format('d M, Y') }}</td>
                                        <td>
                                            <a href="{{ route('journal-entries.show', $jv->id) }}" class="fw-bold text-primary">
                                                {{ $jv->journal_no }}
                                            </a>
                                        </td>
                                        <td>
                                            <div class="text-dark">{{ $item->description ?? $jv->description ?? '-' }}</div>
                                            <span class="badge bg-light text-muted text-uppercase"
                                                style="font-size: 9px;">{{ $jv->reference_type }}</span>
                                        </td>
                                        <td class="text-end fw-semibold text-dark">
                                            {{ $d > 0 ? '৳' . number_format($d, 2) : '-' }}
                                        </td>
                                        <td class="text-end fw-semibold text-dark">
                                            {{ $c > 0 ? '৳' . number_format($c, 2) : '-' }}
                                        </td>
                                        <td class="text-end fw-bold {{ $running < 0 ? 'text-danger' : 'text-primary' }}">
                                            ৳{{ number_format($running, 2) }}
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="dropdown">
                                                <a href="javascript:void(0)" class="btn-action-icon shadow-none"
                                                    data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}'
                                                    aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                                    <li>
                                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2"
                                                            href="{{ route('journal-entries.show', $jv->id) }}">
                                                            <i class="fe fe-eye text-info"></i>
                                                            <span>View Voucher</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2"
                                                            href="{{ route('journal-entries.pdf', $jv->id) }}">
                                                            <i class="fe fe-download text-primary"></i>
                                                            <span>Download PDF</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No journal transactions recorded for
                                            this account during the selected date range.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot style="background-color: #f8fafc; font-weight: 800;">
                                <tr>
                                    <td colspan="3" class="text-end text-uppercase ps-3">Total Period Activity:</td>
                                    <td class="text-end text-dark">৳{{ number_format($periodDebit, 2) }}</td>
                                    <td class="text-end text-dark">৳{{ number_format($periodCredit, 2) }}</td>
                                    <td class="text-end text-success">৳{{ number_format($running, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="card shadow-sm border-0 py-5 text-center" style="border-radius: 12px;">
                <div class="card-body">
                    <i class="fas fa-book fa-3x text-muted mb-3"></i>
                    <h5 class="fw-bold text-dark">Select an Account to View Ledger</h5>
                    <p class="text-muted">Pick any asset, liability, equity, revenue, or expense account above to inspect
                        chronological double-entry lines.</p>
                </div>
            </div>
        @endif

    </div>
@endsection

@push('scripts')
<script>
const customerOptions = @json($customers->map(fn($c) => ['id' => $c->id, 'text' => $c->name . ($c->phone ? ' (' . $c->phone . ')' : '')]));
const vendorOptions = @json($vendors->map(fn($v) => ['id' => $v->id, 'text' => $v->name . ($v->phone ? ' (' . $v->phone . ')' : '')]));
const currentPartyId = '{{ $partyId }}';

function togglePartySelect() {
    const type = document.getElementById('partyTypeSelect').value;
    const wrapper = document.getElementById('partyIdWrapper');
    const select = document.getElementById('partyIdSelect');
    const label = document.getElementById('partyIdLabel');
    const accSelect = document.getElementById('accountSelect');

    select.innerHTML = '<option value="">-- Choose Party --</option>';

    if (type === 'customer') {
        wrapper.style.display = 'block';
        label.textContent = 'Select Customer:';
        customerOptions.forEach(opt => {
            const el = document.createElement('option');
            el.value = opt.id;
            el.textContent = opt.text;
            if (opt.id == currentPartyId) el.selected = true;
            select.appendChild(el);
        });
        if (!accSelect.value) {
            for (let opt of accSelect.options) {
                if (opt.text.includes('1130')) {
                    accSelect.value = opt.value;
                    break;
                }
            }
        }
    } else if (type === 'vendor') {
        wrapper.style.display = 'block';
        label.textContent = 'Select Vendor:';
        vendorOptions.forEach(opt => {
            const el = document.createElement('option');
            el.value = opt.id;
            el.textContent = opt.text;
            if (opt.id == currentPartyId) el.selected = true;
            select.appendChild(el);
        });
        if (!accSelect.value) {
            for (let opt of accSelect.options) {
                if (opt.text.includes('2110')) {
                    accSelect.value = opt.value;
                    break;
                }
            }
        }
    } else {
        wrapper.style.display = 'none';
    }
}
</script>
@endpush