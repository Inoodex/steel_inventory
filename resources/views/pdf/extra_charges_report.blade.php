<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Extra Charges Report</title>
    @php
        $padPath = public_path('assets/invoice/final_pad.png');
        $padBase64 = file_exists($padPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($padPath)) : '';
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
            font-size: 11px;
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
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 3px;
        }

        .report-title p {
            font-size: 10px;
            color: #64748b;
        }

        .summary-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 11px;
            color: #475569;
            margin-bottom: 15px;
        }

        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 20px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            overflow: hidden;
        }

        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            padding: 8px 10px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 8px 10px;
            font-size: 10px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body>
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:50%;"></td>
            <td style="width:50%;" class="report-title">
                <h1>EXTRA CHARGES REPORT</h1>
                <p>Generated: {{ date('d M Y') }}</p>
            </td>
        </tr>
    </table>

    <!-- Summary Card -->
    <div class="summary-card">
        <strong>Delivery:</strong> ৳ {{ number_format($totalDelivery, 2) }} &nbsp;|&nbsp;
        <strong>Labour:</strong> ৳ {{ number_format($totalLabour, 2) }} &nbsp;|&nbsp;
        <strong>Scale Fee:</strong> ৳ {{ number_format($totalScale, 2) }} &nbsp;|&nbsp;
        <strong>Other:</strong> ৳ {{ number_format($totalOther, 2) }} &nbsp;|&nbsp;
        <strong>Total Payouts:</strong> <span style="color: #4f46e5; font-weight: bold;">৳ {{ number_format($totalCharges, 2) }}</span>
    </div>

    <!-- Main Data Table -->
    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">#</th>
                <th style="width: 15%; text-align: left;">Date</th>
                <th style="width: 20%; text-align: left;">Invoice No</th>
                <th style="width: 25%; text-align: left;">Customer</th>
                <th style="width: 10%; text-align: right;">Delivery</th>
                <th style="width: 10%; text-align: right;">Labour</th>
                <th style="width: 15%; text-align: right;">Total Charges</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $index => $sale)
                @php
                    $rowTotal = (float)$sale->delivery_charge + (float)$sale->labour_cost + (float)$sale->weight_scale_cost + (float)$sale->other_charges;
                @endphp
                <tr style="background-color: {{ $loop->even ? '#f8fafc' : '#ffffff' }};">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $sale->created_at ? $sale->created_at->format('d M Y') : 'N/A' }}</td>
                    <td class="fw-bold">#{{ $sale->order_no }}</td>
                    <td>{{ $sale->customer->name ?? 'Walk-in Customer' }}</td>
                    <td class="text-right">৳ {{ number_format($sale->delivery_charge ?? 0, 2) }}</td>
                    <td class="text-right">৳ {{ number_format($sale->labour_cost ?? 0, 2) }}</td>
                    <td class="text-right fw-bold" style="color: #0f172a;">৳ {{ number_format($rowTotal, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #64748b;">No extra charges recorded for the period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Signature Block -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 50px;">
        <tr>
            <td width="100%" align="right" style="vertical-align: bottom;">
                <table align="right" style="width: 180px; margin: 0 0 8px auto; border-collapse: collapse;">
                    <tr>
                        <td style="border-top: 1.5px solid #475569; height: 1px; font-size: 1px; line-height: 1px;">&nbsp;</td>
                    </tr>
                </table>
                <div style="font-size: 10px; font-weight: 600; color: #475569; padding-right: 35px;">Authorized Signature</div>
            </td>
        </tr>
    </table>
</body>
</html>
