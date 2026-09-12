<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Lot Report</title>
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
                <h1>Lot Report</h1>
                <p>Generated on: {{ now()->format('d M Y, h:i A') }}</p>
            </td>
        </tr>
    </table>

    <div class="filter-card">
        @if ($request->filled('vendor_id'))
            <strong>Vendor:</strong> {{ $vendors->find($request->vendor_id)?->name ?? 'Selected' }} &nbsp;|&nbsp;
        @else
            <strong>Vendor:</strong> All Vendors &nbsp;|&nbsp;
        @endif
        @if ($request->filled('customer_id'))
            <strong>Customer:</strong> {{ $customers->find($request->customer_id)?->name ?? 'Selected' }} &nbsp;|&nbsp;
        @endif
        @if ($request->filled('profitability'))
            <strong>Profitability:</strong> {{ ucfirst(str_replace('_', ' ', $request->profitability)) }} &nbsp;|&nbsp;
        @endif
        @if ($request->filled('from_date'))
            <strong>From:</strong> {{ $request->from_date }} &nbsp;|&nbsp;
        @endif
        @if ($request->filled('to_date'))
            <strong>To:</strong> {{ $request->to_date }} &nbsp;|&nbsp;
        @endif
        <strong>Total Lots:</strong> {{ $analyzedLots->count() }}
    </div>

    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 4%; text-align: center;">#</th>
                <th style="width: 11%; text-align: left;">Date</th>
                <th style="width: 13%; text-align: left;">Lot Number</th>
                <th style="width: 15%; text-align: left;">Vendor</th>
                <th style="width: 10%; text-align: right;">Intake (kg)</th>
                <th style="width: 11%; text-align: right;">Cost (BDT)</th>
                <th style="width: 10%; text-align: right;">Sold (kg)</th>
                <th style="width: 12%; text-align: right;">Revenue (BDT)</th>
                <th style="width: 14%; text-align: right;">Profit (BDT)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($analyzedLots as $index => $item)
                @php
                    $lotDate = $item->lot_date ? \Carbon\Carbon::parse($item->lot_date)->format('d M Y') : 'N/A';
                    $profitColor = $item->realized_profit >= 0 ? '#16a34a' : '#dc2626';
                @endphp
                <tr style="background-color: {{ $loop->even ? '#f8fafc' : '#ffffff' }};">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $lotDate }}</td>
                    <td style="font-weight: 700; color: #1e293b;">{{ $item->lot_number }}</td>
                    <td>{{ $item->vendor_name }}</td>
                    <td class="text-right">{{ number_format($item->intake_weight, 2) }}</td>
                    <td class="text-right" style="font-weight: 700;">{{ number_format($item->purchase_cost, 2) }}</td>
                    <td class="text-right">{{ number_format($item->sold_weight, 2) }}</td>
                    <td class="text-right" style="font-weight: 700; color: #1e293b;">{{ number_format($item->sales_revenue, 2) }}</td>
                    <td class="text-right" style="font-weight: 700; color: {{ $profitColor }};">
                        @if($item->sales_revenue > 0)
                            {{ number_format($item->realized_profit, 2) }}
                            <span style="font-size: 8px; font-weight: normal; color: #64748b;">({{ $item->profit_margin }}%)</span>
                        @else
                            <span style="color: #64748b; font-weight: normal;">In Stock</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 15px; color: #64748b;">No lot records found for the selected criteria</td>
                </tr>
            @endforelse
        </tbody>
        @if($analyzedLots->isNotEmpty())
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: 700; border-top: 1.5px solid #cbd5e1;">
                <td colspan="4" style="text-align: right; text-transform: uppercase; font-size: 9px; padding: 7px 10px; color: #475569;">Total:</td>
                <td class="text-right" style="padding: 7px 10px;">{{ number_format($totalIntakeWeight, 2) }}</td>
                <td class="text-right" style="padding: 7px 10px;">{{ number_format($totalPurchaseValuation, 2) }}</td>
                <td class="text-right" style="padding: 7px 10px;">{{ number_format($totalSoldWeight, 2) }}</td>
                <td class="text-right" style="padding: 7px 10px;">{{ number_format($totalSalesRevenue, 2) }}</td>
                <td class="text-right" style="padding: 7px 10px; color: {{ $totalRealizedProfit >= 0 ? '#16a34a' : '#dc2626' }};">
                    {{ number_format($totalRealizedProfit, 2) }}
                </td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="summary-card">
        <table>
            <tr>
                <td style="color: #475569; width: 25%;">Total Intake Wt: <br><span style="color: #0f172a; font-size: 11px;">{{ number_format($totalIntakeWeight, 2) }} kg</span></td>
                <td style="color: #475569; width: 25%; text-align: center;">Total Purchase Value: <br><span style="color: #0f172a; font-size: 11px;">BDT {{ number_format($totalPurchaseValuation, 2) }}</span></td>
                <td style="color: #475569; width: 25%; text-align: center;">Total Sales Revenue: <br><span style="color: #1e293b; font-size: 11px;">BDT {{ number_format($totalSalesRevenue, 2) }}</span></td>
                <td class="text-right" style="color: #475569; width: 25%;">Net Realized Profit: <br><span style="color: {{ $totalRealizedProfit >= 0 ? '#16a34a' : '#dc2626' }}; font-size: 12px; font-weight: 800;">BDT {{ number_format($totalRealizedProfit, 2) }} ({{ $overallProfitMargin }}%)</span></td>
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
