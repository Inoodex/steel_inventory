@php
    $padPath = public_path('assets/invoice/inoodex_invoice.jpg');
    $padBase64 = file_exists($padPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($padPath)) : (function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Balance Transfer Voucher - {{ $transfer->journal_no }}</title>
    <style>
        @page {
            @if($padBase64)
            background-image: url('{{ $padBase64 }}');
            background-image-resize: 6;
            @endif
            margin-top: 45mm;
            margin-bottom: 25mm;
            margin-left: 15mm;
            margin-right: 15mm;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #0f172a;
            font-size: 12px;
            line-height: 1.4;
        }
        .header-title {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .sub-title {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 15px;
        }
        .info-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 15px;
            font-size: 11px;
            color: #334155;
        }
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        .table-custom th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            padding: 8px 10px;
            border: 1px solid #1e293b;
        }
        .table-custom td {
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            font-size: 11px;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .fw-bold {
            font-weight: 700;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
        }
        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }
        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .signature-table {
            width: 100%;
            margin-top: 40px;
            border: none;
        }
        .signature-table td {
            border: none;
            padding: 0;
        }
    </style>
</head>
<body>

    <table style="width: 100%; border: none; margin-bottom: 10px;">
        <tr>
            <td style="border: none;">
                <div class="header-title">Balance Transfer Voucher</div>
                <div class="sub-title">Internal Fund Transfer &amp; Contra Entry</div>
            </td>
            <td style="border: none; text-align: right;">
                <div style="font-size: 14px; font-weight: 800; color: #1e293b; font-family: monospace;">{{ $transfer->journal_no }}</div>
                <div style="font-size: 11px; color: #64748b;">Date: {{ $transfer->entry_date ? $transfer->entry_date->format('d M Y') : '—' }}</div>
                <div style="font-size: 10px; margin-top: 2px;">
                    Status: <span class="badge {{ $transfer->status === 'reversed' ? 'badge-danger' : 'badge-success' }}">{{ strtoupper($transfer->status) }}</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="info-card">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 45%; border: none; vertical-align: top;">
                    <strong style="color: #64748b; font-size: 10px; text-transform: uppercase;">Deducted From (Source):</strong><br>
                    <span style="font-size: 12px; font-weight: 700; color: #b91c1c;">{{ $fromItem?->account?->account_name }}</span><br>
                    <small style="color: #64748b;">Code: {{ $fromItem?->account?->account_code }}</small>
                </td>
                <td style="width: 10%; border: none; text-align: center; font-size: 16px; color: #64748b; vertical-align: middle;">
                    &rarr;
                </td>
                <td style="width: 45%; border: none; text-align: right; vertical-align: top;">
                    <strong style="color: #64748b; font-size: 10px; text-transform: uppercase;">Credited To (Destination):</strong><br>
                    <span style="font-size: 12px; font-weight: 700; color: #15803d;">{{ $toItem?->account?->account_name }}</span><br>
                    <small style="color: #64748b;">Code: {{ $toItem?->account?->account_code }}</small>
                </td>
            </tr>
        </table>
    </div>

    <table class="table-custom">
        <thead>
            <tr>
                <th style="width: 35px;">#</th>
                <th>Account Head</th>
                <th>Particulars / Description</th>
                <th class="text-end" style="width: 110px;">Debit </th>
                <th class="text-end" style="width: 110px;">Credit </th>
            </tr>
        </thead>
        <tbody>
            @foreach($transfer->items as $idx => $item)
                <tr style="background-color: {{ $idx % 2 == 0 ? '#ffffff' : '#f8fafc' }};">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $item->account?->account_name }}</strong><br>
                        <small style="color: #64748b;">{{ $item->account?->account_code }}</small>
                    </td>
                    <td>{{ $item->description ?: 'Fund Transfer' }}</td>
                    <td class="text-end fw-bold" style="color: {{ $item->debit > 0 ? '#15803d' : '#64748b' }};">
                        {{ $item->debit > 0 ? number_format($item->debit, 2) : '—' }}
                    </td>
                    <td class="text-end fw-bold" style="color: {{ $item->credit > 0 ? '#b91c1c' : '#64748b' }};">
                        {{ $item->credit > 0 ? number_format($item->credit, 2) : '—' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: 700;">
                <td colspan="3" class="text-end" style="text-transform: uppercase;">Total Transferred:</td>
                <td class="text-end" style="color: #15803d;">{{ number_format($transfer->total_debit, 2) }}</td>
                <td class="text-end" style="color: #b91c1c;">{{ number_format($transfer->total_credit, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    @if($transfer->description)
        <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; margin-top: 10px; font-size: 11px;">
            <strong>Remarks / Purpose:</strong> {{ $transfer->description }}
        </div>
    @endif

    <table class="signature-table">
        <tr>
            <td style="width: 30%; text-align: center;">
                <div style="border-top: 1.5px solid #475569; width: 150px; margin: 0 auto; padding-top: 4px; font-size: 11px; font-weight: 600; color: #475569;">
                    Prepared By
                </div>
            </td>
            <td style="width: 40%; text-align: center;">
                <div style="border-top: 1.5px solid #475569; width: 150px; margin: 0 auto; padding-top: 4px; font-size: 11px; font-weight: 600; color: #475569;">
                    Checked By
                </div>
            </td>
            <td style="width: 30%; text-align: center;">
                <div style="border-top: 1.5px solid #475569; width: 150px; margin: 0 auto; padding-top: 4px; font-size: 11px; font-weight: 600; color: #475569;">
                    Authorized Signature
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
