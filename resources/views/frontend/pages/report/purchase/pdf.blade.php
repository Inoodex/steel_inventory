<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Report</title>
    @php
        $padPath = public_path('assets/invoice/inoodex_invoice.jpg');
        $padBase64 = file_exists($padPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($padPath)) : (function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '');
    @endphp
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
            font-size: 11px;
            color: #0f172a;
            line-height: 1.4;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .report-title {
            text-align: right;
        }

        .report-title h1 {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .report-title p {
            font-size: 10px;
            color: #64748b;
            margin: 3px 0 0 0;
        }

        .filter-card {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 10px;
            color: #475569;
            margin-bottom: 15px;
        }

        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 8px 10px;
            border: none;
        }

        .items-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 10px;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .summary-card {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 25px;
        }

        .summary-card table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-card td {
            font-size: 11px;
            font-weight: 700;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .signature-section {
            width: 100%;
            margin-top: 30px;
        }

        .signature-box {
            width: 180px;
            text-align: center;
            float: right;
        }

        .signature-line {
            border-top: 1.5px solid #475569;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:50%;"></td>
            <td style="width:50%;" class="report-title">
                <h1>Purchases Report</h1>
                <p>Generated on: {{ now()->format('d M Y, h:i A') }}</p>
            </td>
        </tr>
    </table>

    <div class="filter-card">
        <strong>Date Range:</strong> {{ $filters['from'] }} to {{ $filters['to'] }} &nbsp;|&nbsp;
        <strong>Vendor:</strong> {{ $filters['vendor'] }} &nbsp;|&nbsp;
        <strong>Lot:</strong> {{ $filters['lot'] }} &nbsp;|&nbsp;
        <strong>Total Records:</strong> {{ $purchases->count() }}
    </div>

    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">#</th>
                <th style="width: 12%; text-align: left;">Date</th>
                <th style="width: 18%; text-align: left;">Lot Number</th>
                <th style="width: 25%; text-align: left;">Vendor</th>
                <th style="width: 15%; text-align: left;">Warehouse</th>
                <th style="width: 12%; text-align: right;">Weight (kg)</th>
                <th style="width: 13%; text-align: right;">Total (BDT)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchases as $index => $purchase)
                <tr style="background-color: {{ $loop->even ? '#f8fafc' : '#ffffff' }};">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $purchase->created_at ? $purchase->created_at->format('d M Y') : 'N/A' }}</td>
                    <td style="font-weight: 700; color: #1e293b;">{{ $purchase->lot->lot_number ?? 'N/A' }}</td>
                    <td>{{ $purchase->vendor->name ?? 'N/A' }}</td>
                    <td>{{ $purchase->warehouse->name ?? 'Main Yard' }}</td>
                    <td class="text-right">{{ number_format($purchase->total_weight, 2) }}</td>
                    <td class="text-right" style="font-weight: 700;">{{ number_format($purchase->total_price, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 15px; color: #64748b;">No purchase data found for the selected criteria</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-card">
        <table>
            <tr>
                <td style="color: #475569;">Total Weight Intake: <span style="color: #0f172a;">{{ number_format($purchases->sum('total_weight'), 2) }} kg</span></td>
                <td class="text-right" style="color: #475569;">Total Purchase Value: <span style="color: #16a34a; font-size: 13px;">{{ number_format($purchases->sum('total_price'), 2) }}</span></td>
            </tr>
        </table>
    </div>

    <table class="signature-section" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 60%;"></td>
            <td style="width: 40%; text-align: right;">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <span style="font-size: 10px; color: #475569; font-weight: 600;">Authorized Signature</span>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
