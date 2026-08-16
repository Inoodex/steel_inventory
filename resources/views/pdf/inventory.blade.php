<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Steel Inventory Stock Report</title>
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
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 3px;
        }

        .report-title p {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
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
    </style>
</head>
<body>
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:50%;"></td>
            <td style="width:50%;" class="report-title">
                <h1>INVENTORY STOCK REPORT</h1>
                <p>Generated: {{ date('d M Y') }}</p>
            </td>
        </tr>
    </table>

    <!-- Summary Card -->
    <div class="summary-card">
        <strong>Total In-Stock Coils:</strong> {{ count($coils) }} &nbsp;|&nbsp;
        <strong>Available Weight:</strong> <span style="color: #15803d; font-weight: bold;">{{ number_format($coils->sum('remaining_weight'), 2) }} kg</span> &nbsp;|&nbsp;
        <strong>Stock Valuation:</strong> <span style="color: #4f46e5; font-weight: bold;">৳{{ number_format($coils->sum(fn($c) => ($c->remaining_weight / 1000) * $c->rate_per_ton), 2) }}</span>
    </div>

    <!-- Main Data Table -->
    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">#</th>
                <th style="width: 25%; text-align: left;">Coil Number</th>
                <th style="width: 25%; text-align: left;">Lot Source &amp; Vendor</th>
                <th style="width: 20%; text-align: left;">Warehouse / Yard</th>
                <th style="width: 25%; text-align: right;">Available Stock Weight</th>
            </tr>
        </thead>
        <tbody>
            @forelse($coils as $index => $coil)
                @php
                    $vendorName = $coil->lot && $coil->lot->vendor ? $coil->lot->vendor->name : ($coil->vendor->name ?? 'N/A');
                    $lotNo = $coil->lot ? $coil->lot->lot_number : 'N/A';
                    $whName = $coil->warehouse ? $coil->warehouse->name : 'Main Yard';
                    $rem = (float) $coil->remaining_weight;
                @endphp
                <tr style="background-color: {{ $loop->even ? '#f8fafc' : '#ffffff' }};">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="fw-bold">Coil No - {{ $coil->coil_number }}</td>
                    <td>
                        <strong>{{ $lotNo }}</strong>
                        <div style="font-size: 9px; color: #64748b;">{{ $vendorName }}</div>
                    </td>
                    <td>{{ $whName }}</td>
                    <td class="text-right fw-bold" style="color: #15803d;">{{ number_format($rem, 2) }} kg</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 20px; color: #64748b;">No in-stock steel coils found.</td>
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
                <div style="font-size: 11px; font-weight: 600; color: #475569; padding-right: 35px;">Authorized Signature</div>
            </td>
        </tr>
    </table>
</body>
</html>
