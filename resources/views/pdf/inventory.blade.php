<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Stock Inventory Report</title>
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
            margin-left: 12mm;
            margin-right: 12mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Helvetica, Arial, sans-serif;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10.5px;
            color: #0f172a;
            line-height: 1.35;
        }

        .header-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }

        .report-title {
            text-align: right;
        }

        .report-title h1 {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .report-title p {
            font-size: 10px;
            color: #64748b;
        }

        .summary-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 7px 12px;
            font-size: 10.5px;
            color: #475569;
            margin-bottom: 12px;
        }

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .inventory-table thead th {
            background-color: #103567;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 7px 6px;
            text-align: left;
            border: none;
        }

        .inventory-table tbody td {
            padding: 6px 6px;
            font-size: 10px;
            color: #334155;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .inventory-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .inventory-table tbody tr:nth-child(odd) td {
            background-color: #ffffff;
        }

        .inventory-table tfoot td {
            background-color: #f1f5f9;
            font-weight: 700;
            font-size: 10px;
            padding: 7px 6px;
            border-top: 1.5px solid #cbd5e1;
            color: #0f172a;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: 700; }

        .status-available {
            color: #166534;
            font-weight: 600;
        }

        .signature-table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }

        .signature-line {
            width: 170px;
            border-top: 1.5px solid #475569;
            margin: 0 auto 4px auto;
        }
    </style>
</head>
<body>

    <!-- Header Table -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 50%; vertical-align: bottom;">
                <div style="font-size: 14px; font-weight: 800; color: #103567; text-transform: uppercase;">
                    STOCK INVENTORY
                </div>
            </td>
            <td style="width: 50%;" class="report-title">
                <h1>INVENTORY STOCK REPORT</h1>
                <p>Printed: {{ now()->format('d M Y, h:i A') }}</p>
            </td>
        </tr>
    </table>

    <!-- Summary Overview Card -->
    <div class="summary-card">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%;">
                    <strong>Total In-Stock Items:</strong> {{ count($coils) }} Coils
                </td>
                <td style="width: 50%; text-align: right;">
                    <strong>Total Available Stock:</strong>
                    <span style="color: #103567; font-weight: 800; font-size: 11px;">
                        {{ number_format($coils->sum('remaining_weight'), 0) }} Kg
                    </span>
                    @if($coils->sum('remaining_weight') >= 1000)
                        <span style="color: #64748b; font-size: 9.5px;">
                            ({{ number_format($coils->sum('remaining_weight') / 1000, 3) }} MT)
                        </span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Stock Inventory Table Matching Requested Format -->
    <table class="inventory-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">SI.</th>
                <th style="width: 11%;">COIL NO</th>
                <th style="width: 12%;">Thickness</th>
                <th style="width: 9%;">Size</th>
                <th style="width: 13%;">Weight (Kg)</th>
                <th style="width: 8%;">DIS</th>
                <th style="width: 15%;">Lot Name</th>
                <th style="width: 16%;">Cutting Name</th>
                <th style="width: 11%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($coils as $index => $coil)
                @php
                    // 1. SI
                    $si = $index + 1;

                    // 2. Coil No
                    $coilNo = $coil->coil_number;

                    // 3. Thickness
                    $thkVal = trim($coil->thickness ?? '');
                    if ($thkVal !== '') {
                        $thickness = str_contains(strtolower($thkVal), 'mm') ? $thkVal : ($thkVal . ' mm');
                    } else {
                        $thickness = '—';
                    }

                    // 4. Size
                    $sizeVal = trim($coil->width ?: ($coil->size ?: ''));
                    $size = $sizeVal !== '' ? $sizeVal : '—';

                    // 5. Weight (Kg)
                    $weightVal = (float)($coil->remaining_weight ?: $coil->net_weight ?: $coil->gross_weight ?: 0);
                    $weight = $weightVal == intval($weightVal) ? number_format($weightVal, 0) : number_format($weightVal, 2);

                    // 6. DIS (Steel Classification: HR, PO, CR, GP etc.)
                    $disVal = trim($coil->length ?? '');
                    if (in_array(strtoupper($disVal), ['HR', 'PO', 'CR', 'GP', 'MS', 'GI', 'SS'])) {
                        $dis = strtoupper($disVal);
                    } elseif ($coil->purchase && !empty($coil->purchase->category?->name)) {
                        $dis = $coil->purchase->category->name;
                    } elseif (!empty($coil->notes) && in_array(strtoupper(trim($coil->notes)), ['HR', 'PO', 'CR', 'GP', 'MS', 'GI'])) {
                        $dis = strtoupper(trim($coil->notes));
                    } else {
                        $dis = 'HR';
                    }

                    // 7. Lot Name
                    if ($coil->lot && !empty($coil->lot->lot_number)) {
                        $lotName = $coil->lot->lot_number;
                    } elseif ($coil->purchase_id) {
                        $lotName = 'PO #' . $coil->purchase_id;
                    } else {
                        $lotName = 'Opening Stock';
                    }

                    // 8. Cutting Name / Brand / Yard Source
                    $notes = trim($coil->notes ?? '');
                    $pNotes = trim($coil->purchase?->notes ?? '');
                    if ($notes !== '' && $notes !== 'Opening Stock' && $notes !== 'Opening Stock Intake') {
                        $cuttingName = $notes;
                    } elseif ($pNotes !== '') {
                        $cuttingName = $pNotes;
                    } elseif ($coil->vendor) {
                        $cuttingName = $coil->vendor->name;
                    } elseif ($coil->warehouse) {
                        $cuttingName = $coil->warehouse->name;
                    } else {
                        $cuttingName = 'SB CUTTING';
                    }

                    // 9. Status
                    $rawStatus = $coil->status ?? 'in_stock';
                    if ($rawStatus === 'in_stock' || (float)$coil->remaining_weight > 0) {
                        $statusText = 'Available';
                    } else {
                        $statusText = ucfirst(str_replace('_', ' ', $rawStatus));
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $si }}</td>
                    <td class="fw-bold">{{ $coilNo }}</td>
                    <td>{{ $thickness }}</td>
                    <td>{{ $size }}</td>
                    <td class="fw-bold">{{ $weight }}</td>
                    <td>{{ $dis }}</td>
                    <td>{{ $lotName }}</td>
                    <td>{{ $cuttingName }}</td>
                    <td>
                        <span class="{{ $statusText === 'Available' ? 'status-available' : '' }}">
                            {{ $statusText }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px; color: #64748b;">
                        No in-stock steel coils found in inventory.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($coils->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="4" style="text-align: right; text-transform: uppercase;">Total Available Stock:</td>
                <td class="fw-bold" style="color: #103567;">{{ number_format($coils->sum('remaining_weight'), 0) }}</td>
                <td colspan="4" style="color: #475569;">({{ count($coils) }} Coils)</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <!-- Signature Block -->
    <table class="signature-table">
        <tr>
            <td style="width: 50%; text-align: center;">
                <div class="signature-line"></div>
                <div style="font-size: 9.5px; font-weight: 700; color: #475569; text-transform: uppercase;">Stock In-Charge</div>
            </td>
            <td style="width: 50%; text-align: center;">
                <div class="signature-line"></div>
                <div style="font-size: 9.5px; font-weight: 700; color: #475569; text-transform: uppercase;">Authorized Signature</div>
            </td>
        </tr>
    </table>

</body>
</html>
