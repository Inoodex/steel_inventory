<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Vendor Due Payments Report</title>
    @php
        $padPath = public_path('assets/invoice/inoodex_invoice.jpg');
        $padBase64 = file_exists($padPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($padPath)) : (function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '');
        $totalPurchasesPrice = $purchases->sum('total_price');
        $totalPurchasesPaid = $purchases->sum('payment');
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
            margin-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }

        .report-title {
            text-align: right;
        }

        .report-title h1 {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 3px;
        }

        .report-title p {
            font-size: 11px;
            color: #64748b;
        }

        .summary-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 11px;
            color: #334155;
            margin-bottom: 18px;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 20px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
        }

        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            padding: 8px 10px;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table td {
            padding: 8px 10px;
            font-size: 11px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }

        .grand-total-row td {
            background-color: #f1f5f9 !important;
            font-weight: bold;
            color: #0f172a;
            border-top: 2px solid #cbd5e1;
            font-size: 11px;
        }

        .signature-table {
            width: 100%;
            margin-top: 35px;
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
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div style="font-size: 18px; font-weight: 800; color: #0f172a;">STEEL INVENTORY &amp; MANAGEMENT</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Procurement &amp; Accounts Payable Division</div>
            </td>
            <td style="width: 50%; vertical-align: top;" class="report-title">
                <h1>VENDOR DUE REPORT</h1>
                <p>Generated on: {{ now()->format('d M, Y h:i A') }}</p>
                <p>Vendors with Balance: {{ count($vendorsWithDue ?? []) }} | Unsettled Orders: {{ $purchases->count() }}</p>
            </td>
        </tr>
    </table>

    <!-- Summary Box -->
    <div class="summary-card">
        <strong>Vendors with Dues:</strong> {{ count($vendorsWithDue ?? []) }} &nbsp;|&nbsp;
        <strong>Opening Dues:</strong> <span style="color: #b45309; font-weight: bold;">৳{{ number_format($totalOpeningDues ?? 0, 2) }}</span> &nbsp;|&nbsp;
        <strong>Purchase Orders Due:</strong> <span style="color: #0284c7; font-weight: bold;">৳{{ number_format($totalPurchaseDues ?? 0, 2) }}</span> &nbsp;|&nbsp;
        <strong>Grand Outstanding Due:</strong> <span style="color: #dc2626; font-weight: bold;">৳{{ number_format($grandTotalDues ?? 0, 2) }}</span>
    </div>

    <!-- Section 1: Vendor Dues Summary Table (One Vendor Per Row) -->
    <div class="section-title">1. Vendor Dues Summary (Consolidated By Vendor)</div>
    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">#</th>
                <th style="width: 35%; text-align: left;">Vendor Name &amp; Contact</th>
                <th style="width: 15%; text-align: center;">Due Orders</th>
                <th style="width: 15%; text-align: right;">Opening Due</th>
                <th style="width: 15%; text-align: right;">Purchase Due</th>
                <th style="width: 15%; text-align: right;">Total Due</th>
            </tr>
        </thead>
        <tbody>
            @forelse($vendorsWithDue ?? [] as $index => $v)
                <tr style="background-color: {{ $loop->even ? '#f8fafc' : '#ffffff' }};">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $v->name }}</strong>
                        @if($v->company_name)
                            <div style="font-size: 10px; color: #475569;">{{ $v->company_name }}</div>
                        @endif
                        <div style="font-size: 10px; color: #64748b;">{{ $v->phone }}</div>
                    </td>
                    <td class="text-center">{{ $v->purchases_count ?? 0 }} Orders</td>
                    <td class="text-right" style="color: #b45309;">৳{{ number_format($v->opening_due, 2) }}</td>
                    <td class="text-right" style="color: #0284c7;">৳{{ number_format($v->purchase_due, 2) }}</td>
                    <td class="text-right fw-bold" style="color: #dc2626;">৳{{ number_format($v->total_due, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 15px; color: #64748b;">No vendor dues outstanding.</td>
                </tr>
            @endforelse
            <tr class="grand-total-row">
                <td colspan="3" class="text-right" style="font-size: 10.5px; text-transform: uppercase;">Total Vendor Cumulative Dues:</td>
                <td class="text-right" style="color: #b45309;">৳{{ number_format($totalOpeningDues ?? 0, 2) }}</td>
                <td class="text-right" style="color: #0284c7;">৳{{ number_format($totalPurchaseDues ?? 0, 2) }}</td>
                <td class="text-right" style="color: #dc2626;">৳{{ number_format($grandTotalDues ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Section 2: Purchase Order Due Breakdown -->
    <div class="section-title" style="margin-top: 15px;">2. Purchase Orders Due Breakdown</div>
    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 14%;">Date</th>
                <th style="width: 15%;">PO / Lot #</th>
                <th style="width: 24%;">Vendor Name &amp; Contact</th>
                <th style="width: 14%;" class="text-right">Total Price</th>
                <th style="width: 14%;" class="text-right">Paid Amount</th>
                <th style="width: 14%;" class="text-right">Payable Due</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchases as $index => $purchase)
                <tr style="background-color: {{ $loop->even ? '#f8fafc' : '#ffffff' }};">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $purchase->created_at ? $purchase->created_at->format('d M, Y') : 'N/A' }}</td>
                    <td>
                        <strong>#PO-{{ $purchase->id }}</strong>
                        @if($purchase->lot)
                            <div style="font-size: 10px; color: #64748b;">Lot: {{ $purchase->lot->lot_number }}</div>
                        @endif
                        @if($purchase->warehouse)
                            <div style="font-size: 9px; color: #475569;">{{ $purchase->warehouse->type === 'shop' ? 'Shop: ' : 'Yard: ' }}{{ $purchase->warehouse->name }}</div>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight: 600; color: #0f172a;">{{ $purchase->vendor->name ?? 'N/A' }}</div>
                        <div style="font-size: 10px; color: #64748b;">{{ $purchase->vendor->phone ?? 'N/A' }}</div>
                    </td>
                    <td class="text-right">৳{{ number_format($purchase->total_price, 2) }}</td>
                    <td class="text-right" style="color: #16a34a;">৳{{ number_format($purchase->payment, 2) }}</td>
                    <td class="text-right" style="font-weight: 700; color: #dc2626;">৳{{ number_format($purchase->due, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 15px; color: #64748b;">
                        No pending purchase order due balances recorded.
                    </td>
                </tr>
            @endforelse
            <tr class="grand-total-row">
                <td colspan="4" class="text-right" style="font-size: 10.5px; text-transform: uppercase;">Total Orders Summary:</td>
                <td class="text-right">৳{{ number_format($totalPurchasesPrice, 2) }}</td>
                <td class="text-right" style="color: #16a34a;">৳{{ number_format($totalPurchasesPaid, 2) }}</td>
                <td class="text-right" style="color: #dc2626;">৳{{ number_format($totalPurchaseDues ?? 0, 2) }}</td>
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
