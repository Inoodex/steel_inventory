<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Charges Report</title>
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
                <h1>Charges Report</h1>
                <p>Generated on: {{ now()->format('d M Y, h:i A') }}</p>
            </td>
        </tr>
    </table>

    <div class="filter-card">
        @if ($request->filled('customer_id'))
            <strong>Customer:</strong> {{ $customers->find($request->customer_id)?->name ?? 'Selected' }} &nbsp;|&nbsp;
        @else
            <strong>Customer:</strong> All Customers &nbsp;|&nbsp;
        @endif
        @if ($request->filled('charge_type'))
            <strong>Charge Type:</strong> {{ ucfirst($request->charge_type) }} &nbsp;|&nbsp;
        @endif
        @if ($request->filled('status'))
            <strong>Status:</strong> {{ ucfirst($request->status) }} &nbsp;|&nbsp;
        @endif
        @if ($request->filled('from_date'))
            <strong>From:</strong> {{ $request->from_date }} &nbsp;|&nbsp;
        @endif
        @if ($request->filled('to_date'))
            <strong>To:</strong> {{ $request->to_date }} &nbsp;|&nbsp;
        @endif
        <strong>Total Records:</strong> {{ $analyzedCharges->count() }}
    </div>

    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 4%; text-align: center;">#</th>
                <th style="width: 11%; text-align: left;">Date</th>
                <th style="width: 13%; text-align: left;">Order No</th>
                <th style="width: 18%; text-align: left;">Customer</th>
                <th style="width: 10%; text-align: right;">Labour</th>
                <th style="width: 10%; text-align: right;">Delivery</th>
                <th style="width: 10%; text-align: right;">Scale / Other</th>
                <th style="width: 12%; text-align: right;">Total (BDT)</th>
                <th style="width: 12%; text-align: right;">Due (BDT)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($analyzedCharges as $index => $item)
                @php
                    $scaleOther = $item->scale_col + $item->other_col;
                    $orderDate = $item->order_date ? \Carbon\Carbon::parse($item->order_date)->format('d M Y') : 'N/A';
                @endphp
                <tr style="background-color: {{ $loop->even ? '#f8fafc' : '#ffffff' }};">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $orderDate }}</td>
                    <td style="font-weight: 700; color: #1e293b;">{{ $item->order_no }}</td>
                    <td>{{ $item->customer_name }}</td>
                    <td class="text-right">{{ number_format($item->labour_col, 2) }}</td>
                    <td class="text-right">{{ number_format($item->delivery_col, 2) }}</td>
                    <td class="text-right">{{ number_format($scaleOther, 2) }}</td>
                    <td class="text-right" style="font-weight: 700; color: #1e293b;">{{ number_format($item->total_col, 2) }}</td>
                    <td class="text-right" style="font-weight: 700; color: {{ $item->total_due > 0 ? '#dc2626' : '#16a34a' }};">
                        {{ number_format($item->total_due, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 15px; color: #64748b;">No charge records found for the selected criteria</td>
                </tr>
            @endforelse
        </tbody>
        @if($analyzedCharges->isNotEmpty())
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: 700; border-top: 1.5px solid #cbd5e1;">
                <td colspan="4" style="text-align: right; text-transform: uppercase; font-size: 9px; padding: 7px 10px; color: #475569;">Total:</td>
                <td class="text-right" style="padding: 7px 10px;">{{ number_format($totalLabourCol, 2) }}</td>
                <td class="text-right" style="padding: 7px 10px;">{{ number_format($totalDeliveryCol, 2) }}</td>
                <td class="text-right" style="padding: 7px 10px;">{{ number_format($totalScaleCol + $totalOtherCol, 2) }}</td>
                <td class="text-right" style="padding: 7px 10px;">{{ number_format($totalCollectedGrand, 2) }}</td>
                <td class="text-right" style="padding: 7px 10px; color: {{ $totalDueGrand > 0 ? '#dc2626' : '#16a34a' }};">
                    {{ number_format($totalDueGrand, 2) }}
                </td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="summary-card">
        <table>
            <tr>
                <td style="color: #475569; width: 25%;">Total Invoiced: <br><span style="color: #0f172a; font-size: 11px;">BDT {{ number_format($totalCollectedGrand, 2) }}</span></td>
                <td style="color: #475569; width: 25%; text-align: center;">Total Paid to Workers: <br><span style="color: #16a34a; font-size: 11px;">BDT {{ number_format($totalPaidGrand, 2) }}</span></td>
                <td style="color: #475569; width: 25%; text-align: center;">Labour / Delivery: <br><span style="color: #0f172a; font-size: 11px;">BDT {{ number_format($totalLabourCol + $totalDeliveryCol, 2) }}</span></td>
                <td class="text-right" style="color: #475569; width: 25%;">Total Outstanding Due: <br><span style="color: #dc2626; font-size: 12px; font-weight: 800;">BDT {{ number_format($totalDueGrand, 2) }}</span></td>
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
