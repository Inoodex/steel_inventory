<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Vendor Ledger - {{ $vendor->name }}</title>
    @php
        $padPath = public_path('assets/invoice/inoodex_invoice.jpg');
        $padBase64 = file_exists($padPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($padPath)) : (function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '');
    @endphp
    <style>
        @page {
            @if(!empty($padBase64))
            background-image: url('{{ $padBase64 }}');
            background-image-resize: 6;
            @endif
            margin-top: 45mm;
            margin-bottom: 25mm;
            margin-left: 15mm;
            margin-right: 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Helvetica, Arial, sans-serif;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #0f172a;
            line-height: 1.4;
        }

        .header-table {
            width: 100%;
            margin-bottom: 14px;
            border-bottom: 1.5px solid #cbd5e1;
            padding-bottom: 10px;
        }

        .report-title {
            text-align: right;
        }

        .report-title h1 {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .report-title p {
            font-size: 10px;
            color: #64748b;
        }

        .party-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 14px;
        }

        .party-box table {
            width: 100%;
            border-collapse: collapse;
        }

        .party-title {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 3px;
        }

        .party-meta {
            font-size: 10px;
            color: #475569;
            line-height: 1.4;
        }

        .summary-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 10px;
            color: #334155;
            margin-bottom: 14px;
        }

        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 20px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
        }

        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            padding: 8px 10px;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table td {
            padding: 7px 10px;
            font-size: 10px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .items-table tr.total-row td {
            background-color: #f1f5f9;
            font-weight: 700;
            border-top: 1.5px solid #cbd5e1;
            color: #0f172a;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }

        .signature-section {
            margin-top: 30px;
            width: 100%;
        }

        .sig-table {
            width: 100%;
        }

        .sig-box {
            width: 200px;
            text-align: center;
        }

        .sig-line {
            border-top: 1.5px solid #475569;
            padding-top: 5px;
            font-size: 10px;
            color: #475569;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 50%; vertical-align: middle;">
                <div class="party-title">{{ $vendor->name }}</div>
                <div class="party-meta">
                    @if($vendor->company)<strong>{{ $vendor->company }}</strong><br>@endif
                    <span>Supplier / Vendor ID: #VND-{{ str_pad($vendor->id, 4, '0', STR_PAD_LEFT) }}</span>
                </div>
            </td>
            <td style="width: 50%; vertical-align: middle;" class="report-title">
                <h1>VENDOR STATEMENT</h1>
                <p>Generated: {{ date('d M Y, h:i A') }}</p>
                @if(!empty($fromDate) || !empty($toDate))
                    <p style="color: #0f172a; font-weight: 600; margin-top: 2px;">
                        Period: {{ $fromDate ? date('d M Y', strtotime($fromDate)) : 'Inception' }} &mdash; {{ $toDate ? date('d M Y', strtotime($toDate)) : date('d M Y') }}
                    </p>
                @else
                    <p style="color: #0f172a; font-weight: 600; margin-top: 2px;">Statement Period: All Time</p>
                @endif
            </td>
        </tr>
    </table>

    <!-- Party Contact Info -->
    <div class="party-box">
        <table cellpadding="0" cellspacing="0">
            <tr>
                <td style="width: 35%;" class="party-meta">
                    <strong>Phone:</strong> {{ $vendor->phone ?? 'N/A' }}<br>
                    <strong>Email:</strong> {{ $vendor->email ?? 'N/A' }}
                </td>
                <td style="width: 40%;" class="party-meta">
                    <strong>Address:</strong> {{ $vendor->address ?? 'N/A' }}
                </td>
                <td style="width: 25%; text-align: right;" class="party-meta">
                    @if($vendor->bin_number)<strong>BIN:</strong> {{ $vendor->bin_number }}<br>@endif
                    @if($vendor->tin_number)<strong>TIN:</strong> {{ $vendor->tin_number }}<br>@endif
                    <strong>Status:</strong> {{ in_array($vendor->status, ['1', 'active', 1]) ? 'Active' : 'Inactive' }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Summary Metrics -->
    <div class="summary-card">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 25%;"><strong>Opening Balance:</strong> ৳{{ number_format($openingBalance, 2) }}</td>
                <td style="width: 25%; color: #0284c7;"><strong>Total Purchases:</strong> ৳{{ number_format($totalCredit, 2) }}</td>
                <td style="width: 25%; color: #16a34a;"><strong>Total Disbursed:</strong> ৳{{ number_format($totalDebit, 2) }}</td>
                <td style="width: 25%; text-align: right; font-size: 11px;">
                    <strong>Net Payable Outstanding:</strong> 
                    <span style="font-weight: 800; color: {{ $closingBalance > 0 ? '#dc2626' : '#15803d' }};">
                        ৳{{ number_format($closingBalance, 2) }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Ledger Items Table -->
    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">#</th>
                <th style="width: 12%; text-align: left;">Date</th>
                <th style="width: 18%; text-align: left;">Type &amp; Ref</th>
                <th style="width: 27%; text-align: left;">Description</th>
                <th style="width: 12%; text-align: right;">Paid / Debit (৳)</th>
                <th style="width: 12%; text-align: right;">Bill / Credit (৳)</th>
                <th style="width: 14%; text-align: right;">Balance (৳)</th>
            </tr>
        </thead>
        <tbody>
            <!-- Opening Balance Row -->
            <tr style="background-color: #f8fafc;">
                <td class="text-center fw-bold" style="color: #64748b;">-</td>
                <td style="color: #64748b;">{{ $fromDate ? date('d-m-Y', strtotime($fromDate)) : 'Opening' }}</td>
                <td class="fw-bold" style="color: #475569;">OPENING BALANCE</td>
                <td style="color: #64748b;">Initial / Brought Forward Balance</td>
                <td class="text-right" style="color: #64748b;">-</td>
                <td class="text-right" style="color: #64748b;">-</td>
                <td class="text-right fw-bold" style="color: #0f172a;">৳{{ number_format($openingBalance, 2) }}</td>
            </tr>

            @forelse($ledgerRows as $index => $row)
                <tr>
                    <td class="text-center" style="color: #64748b;">{{ $index + 1 }}</td>
                    <td>{{ date('d-m-Y', strtotime($row['date'])) }}</td>
                    <td>
                        <strong style="color: #0f172a;">{{ $row['type'] }}</strong><br>
                        <span style="font-size: 8.5px; color: #64748b;">{{ $row['ref'] }}</span>
                    </td>
                    <td style="font-size: 9.5px;">{{ $row['description'] }}</td>
                    <td class="text-right" style="color: {{ $row['debit'] > 0 ? '#16a34a' : '#94a3b8' }};">
                        {{ $row['debit'] > 0 ? '৳' . number_format($row['debit'], 2) : '-' }}
                    </td>
                    <td class="text-right" style="color: {{ $row['credit'] > 0 ? '#0284c7' : '#94a3b8' }};">
                        {{ $row['credit'] > 0 ? '৳' . number_format($row['credit'], 2) : '-' }}
                    </td>
                    <td class="text-right fw-bold" style="color: {{ $row['balance'] > 0 ? '#b91c1c' : '#15803d' }};">
                        ৳{{ number_format($row['balance'], 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #94a3b8;">
                        No transactions recorded for this vendor in the selected date range.
                    </td>
                </tr>
            @endforelse

            <!-- Grand Totals Row -->
            <tr class="total-row">
                <td colspan="4" class="text-right" style="font-size: 10.5px;">TOTALS &amp; CLOSING PAYABLE:</td>
                <td class="text-right" style="color: #16a34a;">৳{{ number_format($totalDebit, 2) }}</td>
                <td class="text-right" style="color: #0284c7;">৳{{ number_format($totalCredit, 2) }}</td>
                <td class="text-right" style="font-size: 11px; color: {{ $closingBalance > 0 ? '#b91c1c' : '#15803d' }};">
                    ৳{{ number_format($closingBalance, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Signature Block -->
    <div class="signature-section">
        <table class="sig-table" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width: 50%;">
                    <div class="sig-box">
                        <div class="sig-line">Prepared By (Accounts)</div>
                    </div>
                </td>
                <td style="width: 50%; text-align: right;">
                    <div class="sig-box" style="margin-left: auto;">
                        <div class="sig-line">Authorized Signature</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
