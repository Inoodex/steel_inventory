<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Vendor Due Payments Report</title>
    @php
        $padPath = public_path('assets/invoice/inoodex_invoice.jpg');
        $padBase64 = file_exists($padPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($padPath)) : (function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '');
        $totalPrice = $purchases->sum('total_price');
        $totalPaid = $purchases->sum('payment');
        $totalDue = $purchases->sum('due');
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Helvetica, Arial, sans-serif;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #0f172a;
            line-height: 1.4;
        }

        .header-table {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
        }

        .report-title {
            text-align: right;
        }

        .report-title h1 {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .report-title p {
            font-size: 12px;
            color: #64748b;
            margin-top: 3px;
        }

        .summary-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 12px;
            color: #334155;
            margin-bottom: 20px;
        }

        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 25px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            overflow: hidden;
        }

        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            padding: 10px 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table td {
            padding: 10px 12px;
            font-size: 12px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }

        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .grand-total-row td {
            background-color: #f1f5f9 !important;
            font-weight: bold;
            color: #0f172a;
            border-top: 2px solid #cbd5e1;
            font-size: 12px;
        }

        .signature-table {
            width: 100%;
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-line {
            width: 180px;
            border-top: 1.5px solid #475569;
            margin: 0 auto 5px auto;
        }
        .signature-label {
            font-size: 11px;
            color: #475569;
            font-weight: 600;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div style="font-size: 18px; font-weight: 800; color: #0f172a;">STEEL INVENTORY &amp; MANAGEMENT</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Procurement &amp; Accounts Payable Division</div>
            </td>
            <td style="width: 50%; vertical-align: top;" class="report-title">
                <h1>VENDOR DUE REPORT</h1>
                <p>Generated on: {{ now()->format('d M, Y h:i A') }}</p>
                <p>Total Unsettled Orders: {{ $purchases->count() }}</p>
            </td>
        </tr>
    </table>

    <!-- Summary Box -->
    <table class="summary-card" style="width: 100%;">
        <tr>
            <td style="width: 33.33%;">
                <span style="color: #64748b; font-size: 11px; display: block;">Total Procurement Value:</span>
                <strong style="font-size: 14px; color: #0f172a;">BDT {{ number_format($totalPrice, 2) }}</strong>
            </td>
            <td style="width: 33.33%; text-align: center;">
                <span style="color: #64748b; font-size: 11px; display: block;">Total Paid to Vendors:</span>
                <strong style="font-size: 14px; color: #16a34a;">BDT {{ number_format($totalPaid, 2) }}</strong>
            </td>
            <td style="width: 33.33%; text-align: right;">
                <span style="color: #64748b; font-size: 11px; display: block;">Total Outstanding Payable Due:</span>
                <strong style="font-size: 15px; color: #dc2626;">BDT {{ number_format($totalDue, 2) }}</strong>
            </td>
        </tr>
    </table>

    <!-- Main Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 15%;">Date</th>
                <th style="width: 15%;">PO / Lot #</th>
                <th style="width: 25%;">Vendor Name &amp; Contact</th>
                <th style="width: 13%;" class="text-right">Total Price</th>
                <th style="width: 13%;" class="text-right">Paid Amount</th>
                <th style="width: 14%;" class="text-right">Payable Due</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchases as $index => $purchase)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $purchase->created_at ? $purchase->created_at->format('d M, Y') : 'N/A' }}</td>
                    <td>
                        <strong>#PO-{{ $purchase->id }}</strong>
                        @if($purchase->lot)
                            <div style="font-size: 10px; color: #64748b;">Lot: {{ $purchase->lot->lot_number }}</div>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight: 600; color: #0f172a;">{{ $purchase->vendor->name ?? 'N/A' }}</div>
                        <div style="font-size: 11px; color: #64748b;">{{ $purchase->vendor->phone ?? 'N/A' }}</div>
                    </td>
                    <td class="text-right">{{ number_format($purchase->total_price, 2) }}</td>
                    <td class="text-right" style="color: #16a34a;">{{ number_format($purchase->payment, 2) }}</td>
                    <td class="text-right" style="font-weight: 700; color: #dc2626;">{{ number_format($purchase->due, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #64748b;">
                        No pending vendor due balances recorded.
                    </td>
                </tr>
            @endforelse
            <tr class="grand-total-row">
                <td colspan="4" class="text-right" style="font-size: 11px; text-transform: uppercase;">Total Cumulative Summary:</td>
                <td class="text-right">BDT {{ number_format($totalPrice, 2) }}</td>
                <td class="text-right" style="color: #16a34a;">BDT {{ number_format($totalPaid, 2) }}</td>
                <td class="text-right" style="color: #dc2626;">BDT {{ number_format($totalDue, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Signature Block -->
    <table class="signature-table">
        <tr>
            <td style="width: 33.33%;">
                <div class="signature-line"></div>
                <div class="signature-label">Prepared By</div>
            </td>
            <td style="width: 33.33%;">
                <div class="signature-line"></div>
                <div class="signature-label">Accounts Officer</div>
            </td>
            <td style="width: 33.33%;">
                <div class="signature-line"></div>
                <div class="signature-label">Authorized Signature</div>
            </td>
        </tr>
    </table>

</body>
</html>
