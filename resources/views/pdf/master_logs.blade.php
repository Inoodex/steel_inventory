@php
    $padPath = public_path('assets/invoice/inoodex_invoice.jpg');
    $padBase64 = file_exists($padPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($padPath)) : (function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Master Activity &amp; Transaction Logs Report</title>
    <style>
        @page {
            @if($padBase64)
            background-image: url('{{ $padBase64 }}');
            background-image-resize: 6;
            @endif
            margin-top: 35mm;
            margin-bottom: 20mm;
            margin-left: 12mm;
            margin-right: 12mm;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #0f172a;
            font-size: 10px;
            line-height: 1.3;
        }

        .header-table {
            width: 100%;
            margin-bottom: 12px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
        }

        .header-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-subtitle {
            font-size: 10px;
            color: #475569;
            margin-top: 2px;
        }

        .filter-card {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 12px;
            font-size: 9px;
            color: #334155;
        }

        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 12px;
        }

        .kpi-box {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px 10px;
            text-align: center;
        }

        .kpi-label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .kpi-val {
            font-size: 12px;
            font-weight: 800;
            color: #0f172a;
        }

        .kpi-val-green { color: #16a34a; }
        .kpi-val-red { color: #dc2626; }
        .kpi-val-blue { color: #2563eb; }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: 700;
            font-size: 8.5px;
            text-transform: uppercase;
            padding: 6px 6px;
            text-align: left;
            border: none;
        }

        .items-table td {
            padding: 5px 6px;
            font-size: 8.5px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }

        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 7.5px;
            font-weight: 700;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .badge-sales { background: #dbeafe; color: #1d4ed8; }
        .badge-purchases { background: #f3e8ff; color: #7e22ce; }
        .badge-payments { background: #dcfce7; color: #15803d; }
        .badge-expenses { background: #f1f5f9; color: #475569; }
        .badge-returns { background: #fee2e2; color: #b91c1c; }
        .badge-audit { background: #f8fafc; color: #64748b; border: 1px solid #cbd5e1; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        .text-green { color: #16a34a; }
        .text-red { color: #dc2626; }

        .signature-table {
            width: 100%;
            margin-top: 25px;
        }

        .sig-line {
            width: 160px;
            border-top: 1.5px solid #475569;
            text-align: center;
            padding-top: 4px;
            font-size: 9px;
            font-weight: 700;
            color: #334155;
        }
    </style>
</head>
<body>

    <!-- Header Table -->
    <table class="header-table">
        <tr>
            <td>
                <div class="header-title">Master Activity &amp; Transaction Logs</div>
                <div class="header-subtitle">Executive Consolidated Operations &amp; Audit Trail</div>
            </td>
            <td class="text-right" style="vertical-align: bottom;">
                <div style="font-size: 9px; color: #64748b;">Generated on: <strong>{{ date('d M Y, h:i A') }}</strong></div>
                <div style="font-size: 9px; color: #64748b;">Report Period: <strong>{{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }}</strong> to <strong>{{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</strong></div>
            </td>
        </tr>
    </table>

    <!-- Filter Summary Bar -->
    <div class="filter-card">
        <table style="width: 100%;">
            <tr>
                <td style="width: 33%;"><strong>Category:</strong> {{ strtoupper($eventType) }}</td>
                <td style="width: 33%; text-align: center;"><strong>Location:</strong> {{ $warehouseName }}</td>
                <td style="width: 33%; text-align: right;"><strong>Total Records:</strong> {{ number_format($kpis['total_events']) }} Events</td>
            </tr>
        </table>
    </div>

    <!-- KPI Box Summary Table -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-box" style="width: 16.6%;">
                <div class="kpi-label">Money In (Collections)</div>
                <div class="kpi-val kpi-val-green">৳{{ number_format($kpis['total_inflow'], 2) }}</div>
            </td>
            <td class="kpi-box" style="width: 16.6%;">
                <div class="kpi-label">Money Out (Disbursed)</div>
                <div class="kpi-val kpi-val-red">৳{{ number_format($kpis['total_outflow'], 2) }}</div>
            </td>
            <td class="kpi-box" style="width: 16.6%;">
                <div class="kpi-label">Net Cash Position</div>
                <div class="kpi-val {{ $kpis['net_cashflow'] >= 0 ? 'kpi-val-blue' : 'kpi-val-red' }}">৳{{ number_format($kpis['net_cashflow'], 2) }}</div>
            </td>
            <td class="kpi-box" style="width: 16.6%;">
                <div class="kpi-label">Sales Turnover</div>
                <div class="kpi-val">৳{{ number_format($kpis['total_sales_value'], 2) }}</div>
            </td>
            <td class="kpi-box" style="width: 16.6%;">
                <div class="kpi-label">Steel Inward (kg)</div>
                <div class="kpi-val">{{ number_format($kpis['total_weight_in'], 2) }}</div>
            </td>
            <td class="kpi-box" style="width: 16.6%;">
                <div class="kpi-label">Steel Dispatched (kg)</div>
                <div class="kpi-val">{{ number_format($kpis['total_weight_out'], 2) }}</div>
            </td>
        </tr>
    </table>

    <!-- Main Events Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 70px;">Date</th>
                <th style="width: 85px;">Category</th>
                <th style="width: 85px;">Reference #</th>
                <th>Party / Target</th>
                <th style="width: 80px;">Location</th>
                <th class="text-right" style="width: 65px;">Weight (kg)</th>
                <th class="text-right" style="width: 75px;">Bill Amount</th>
                <th class="text-right" style="width: 70px;">Money In</th>
                <th class="text-right" style="width: 70px;">Money Out</th>
                <th style="width: 75px;">Operator</th>
            </tr>
        </thead>
        <tbody>
            @forelse($allLogs as $log)
                <tr>
                    <td>
                        <span class="font-bold">{{ \Carbon\Carbon::parse($log['timestamp'])->format('d M Y') }}</span>
                    </td>
                    <td>
                        <span class="badge badge-{{ $log['category'] }}">{{ $log['type_label'] }}</span>
                    </td>
                    <td class="font-bold">{{ $log['reference_no'] }}</td>
                    <td>
                        <span class="font-bold">{{ $log['party_name'] }}</span>
                        <div style="font-size: 7.5px; color: #64748b;">{{ $log['party_role'] }}</div>
                    </td>
                    <td>{{ $log['location'] }}</td>
                    <td class="text-right">
                        {{ $log['weight_kg'] > 0 ? number_format($log['weight_kg'], 2) : '—' }}
                    </td>
                    <td class="text-right font-bold">
                        {{ $log['amount'] > 0 ? '৳' . number_format($log['amount'], 2) : '—' }}
                    </td>
                    <td class="text-right text-green font-bold">
                        {{ $log['inflow'] > 0 ? '+৳' . number_format($log['inflow'], 2) : '—' }}
                    </td>
                    <td class="text-right text-red font-bold">
                        {{ $log['outflow'] > 0 ? '-৳' . number_format($log['outflow'], 2) : '—' }}
                    </td>
                    <td>{{ $log['operator'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px; color: #64748b;">
                        No activity or transaction logs found for the selected criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Signature Block -->
    <table class="signature-table">
        <tr>
            <td style="width: 33%;">
                <div class="sig-line">Prepared By</div>
            </td>
            <td style="width: 33%; text-align: center;">
                <div class="sig-line" style="margin: 0 auto;">Head of Accounts</div>
            </td>
            <td style="width: 33%; text-align: right;">
                <div class="sig-line" style="margin-left: auto;">Authorized Signature</div>
            </td>
        </tr>
    </table>

</body>
</html>
