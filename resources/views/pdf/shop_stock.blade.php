@php
    $padPath = public_path('assets/invoice/inoodex_invoice.jpg');
    $padBase64 = file_exists($padPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($padPath)) : (function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shop Stock &amp; Inventory Report</title>
    <style>
        @page {
            @if($padBase64)
            background-image: url('{{ $padBase64 }}');
            background-image-resize: 6;
            @endif
            margin-top: 40mm;
            margin-bottom: 22mm;
            margin-left: 12mm;
            margin-right: 12mm;
        }
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            font-size: 11px;
            color: #0f172a;
            line-height: 1.4;
        }
        .header-section {
            margin-bottom: 12px;
        }
        .report-title {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 4px 0;
        }
        .report-subtitle {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
            margin: 0;
        }
        .filter-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 12px;
            color: #475569;
            font-size: 11px;
            margin-bottom: 12px;
        }
        .summary-grid {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: separate;
            border-spacing: 8px 0;
        }
        .summary-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 10px;
            text-align: center;
        }
        .summary-card .label {
            font-size: 9px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 3px;
        }
        .summary-card .value {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            border: 1px solid #cbd5e1;
        }
        table.items-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            padding: 7px 8px;
            text-align: left;
            border: 1px solid #1e293b;
        }
        table.items-table td {
            padding: 6px 8px;
            font-size: 10px;
            border: 1px solid #e2e8f0;
            color: #1e293b;
        }
        table.items-table tr.even-row {
            background-color: #f8fafc;
        }
        table.items-table tr.odd-row {
            background-color: #ffffff;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .text-success { color: #16a34a; }
        .text-primary { color: #2563eb; }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 9px;
            border-radius: 4px;
            font-weight: bold;
        }
        .badge-stock { background: #dcfce7; color: #15803d; }
        .badge-proc { background: #fef3c7; color: #b45309; }

        .signature-section {
            margin-top: 25px;
            width: 100%;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature-table td {
            border: none;
            padding: 0;
            vertical-align: bottom;
        }
        .signature-line {
            width: 160px;
            border-top: 1.5px solid #475569;
            text-align: center;
            padding-top: 5px;
            font-size: 10px;
            font-weight: bold;
            color: #475569;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <div class="header-section">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="border: none;">
                    <div class="report-title">Shop Stock &amp; Inventory Report</div>
                    <div class="report-subtitle">Outlet: <strong>{{ $selectedShopName }}</strong> | Generated on {{ now()->format('d M Y, h:i A') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Summary Metrics -->
    <table class="summary-grid">
        <tr>
            <td class="summary-card" style="width: 25%;">
                <div class="label">Total Items</div>
                <div class="value">{{ number_format($totalItems) }} <span style="font-size: 10px; font-weight: normal; color: #64748b;">({{ number_format($totalPieces) }} pcs)</span></div>
            </td>
            <td class="summary-card" style="width: 25%;">
                <div class="label">Available Weight</div>
                <div class="value text-primary">{{ number_format($totalWeightTon, 3) }} MT</div>
            </td>
            <td class="summary-card" style="width: 25%;">
                <div class="label">Total Weight (Kg)</div>
                <div class="value">{{ number_format($totalWeightKg, 1) }} kg</div>
            </td>
            <td class="summary-card" style="width: 25%;">
                <div class="label">Total Valuation</div>
                <div class="value text-success">৳{{ number_format($totalValuation, 2) }}</div>
            </td>
        </tr>
    </table>

    <!-- Thickness Summary Sub-Table (if applicable) -->
    @if($thicknessBreakdown->count() > 0)
    <div style="margin-bottom: 8px; font-weight: bold; font-size: 11px; text-transform: uppercase; color: #334155;">
        Thickness-Wise Inventory Breakdown
    </div>
    <table class="items-table" style="margin-bottom: 12px;">
        <thead>
            <tr>
                <th>Thickness</th>
                <th class="text-center">Count</th>
                <th class="text-center">Pieces</th>
                <th class="text-end">Weight (Kg)</th>
                <th class="text-end">Weight (MT)</th>
                <th class="text-end">Total Valuation (৳)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($thicknessBreakdown as $tb)
            <tr class="{{ $loop->even ? 'even-row' : 'odd-row' }}">
                <td><strong>{{ $tb['thickness'] }}</strong></td>
                <td class="text-center">{{ $tb['count'] }}</td>
                <td class="text-center">{{ number_format($tb['pieces']) }}</td>
                <td class="text-end">{{ number_format($tb['weight'], 2) }} kg</td>
                <td class="text-end font-bold text-primary">{{ number_format($tb['weight_ton'], 3) }} MT</td>
                <td class="text-end font-bold text-success">৳{{ number_format($tb['valuation'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Main Stock Inventory Table -->
    <div style="margin-bottom: 8px; font-weight: bold; font-size: 11px; text-transform: uppercase; color: #334155;">
        Detailed Coils &amp; Inventory Items
    </div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 14%;">Coil / Item #</th>
                <th style="width: 14%;">Shop / Branch</th>
                <th style="width: 10%;">Thickness</th>
                <th style="width: 12%;">Width / Size</th>
                <th style="width: 10%;">Length</th>
                <th class="text-center" style="width: 7%;">Pcs</th>
                <th class="text-end" style="width: 12%;">Weight (Kg)</th>
                <th class="text-end" style="width: 16%;">Valuation (৳)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($coils as $idx => $coil)
                @php
                    $cVal = (float)$coil->remaining_weight * (float)$coil->rate_per_ton;
                @endphp
                <tr class="{{ $idx % 2 === 1 ? 'even-row' : 'odd-row' }}">
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td><strong>{{ $coil->coil_no }}</strong></td>
                    <td>{{ $coil->warehouse?->name ?? 'Shop' }}</td>
                    <td><strong>{{ $coil->thickness }}</strong></td>
                    <td>{{ $coil->width }}</td>
                    <td>{{ $coil->length ?: 'N/A' }}</td>
                    <td class="text-center">{{ number_format($coil->piece_count ?: 1) }}</td>
                    <td class="text-end"><strong>{{ number_format($coil->remaining_weight, 2) }}</strong> kg</td>
                    <td class="text-end text-success"><strong>৳{{ number_format($cVal, 2) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px; color: #64748b;">No inventory stock items currently in selected shops.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Signature Section Standard -->
    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td style="width: 50%; text-align: left;">
                    <div class="signature-line" style="margin-left: 0;">
                        Store In-Charge
                    </div>
                </td>
                <td style="width: 50%; text-align: right;">
                    <div class="signature-line" style="margin-left: auto;">
                        Authorized Signature
                    </div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
