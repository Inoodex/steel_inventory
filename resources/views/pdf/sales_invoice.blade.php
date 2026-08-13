@php
    $padPath = public_path('assets/invoice/final_pad.png');
    $padBase64 = file_exists($padPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($padPath)) : '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Invoice #{{ $sale->order_no }}</title>
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
            margin-bottom: 12px;
        }

        .doc-title {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doc-meta {
            font-size: 10px;
            color: #64748b;
        }

        .info-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 15px;
        }

        .info-table {
            width: 100%;
            font-size: 10.5px;
        }

        .info-table td {
            vertical-align: top;
            padding: 2px 0;
        }

        .info-label {
            color: #64748b;
            font-weight: 600;
            width: 90px;
        }

        .info-value {
            color: #0f172a;
            font-weight: 700;
        }

        .items-table {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 15px;
        }

        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            padding: 7px 10px;
            text-align: left;
        }

        .items-table td {
            padding: 7px 10px;
            font-size: 10px;
            border-top: 1px solid #e2e8f0;
        }

        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .text-end { text-align: right; }
        .text-center { text-align: center; }

        .summary-wrapper {
            width: 100%;
            margin-top: 5px;
        }

        .summary-card {
            width: 280px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            float: right;
        }

        .summary-card table {
            width: 100%;
            font-size: 10.5px;
        }

        .summary-card td {
            padding: 3px 0;
        }

        .signature-table {
            width: 100%;
            margin-top: 45px;
        }

        .signature-line {
            width: 180px;
            border-top: 1.5px solid #475569;
            text-align: center;
            padding-top: 5px;
            font-size: 10px;
            font-weight: 700;
            color: #475569;
        }

        .badge-status {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="doc-title">Sales Invoice</div>
                <div class="doc-meta">Invoice No: <strong style="color:#0f172a;">#{{ $sale->order_no }}</strong> &bull; Date: {{ $sale->created_at ? $sale->created_at->format('d M Y, h:i A') : date('d M Y') }}</div>
            </td>
            <td style="width: 40%; text-align: right;">
                <div style="font-size: 11px; color: #475569;">
                    Warehouse: <strong style="color:#0f172a;">{{ $sale->warehouse->name ?? 'Main Steel Yard' }}</strong><br>
                    Delivery: <strong style="color:#0f172a; text-transform: uppercase;">{{ str_replace('_', ' ', $sale->delivery_status ?? 'Pending') }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <!-- Customer & Dispatch Info Card -->
    <div class="info-card">
        <table class="info-table">
            <tr>
                <td style="width: 50%;">
                    <table>
                        <tr>
                            <td class="info-label">Customer:</td>
                            <td class="info-value">{{ $sale->customer->name ?? $sale->client->name ?? 'Walk-in Customer' }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Phone:</td>
                            <td>{{ $sale->customer->phone ?? $sale->client->phone ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Address:</td>
                            <td>{{ $sale->customer->address ?? $sale->client->address ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </td>
                <td style="width: 50%;">
                    <table>
                        <tr>
                            <td class="info-label">Sales By:</td>
                            <td class="info-value">{{ $sale->salesPerson->name ?? 'System Admin' }}</td>
                        </tr>
                        @if($sale->note)
                        <tr>
                            <td class="info-label">Transport/Note:</td>
                            <td>{{ $sale->note }}</td>
                        </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 35%;">Steel Product & Specifications</th>
                <th style="width: 20%;">Mill Lot Source</th>
                <th style="width: 12%;" class="text-center">Quantity</th>
                <th style="width: 13%;" class="text-end">Unit Price</th>
                <th style="width: 15%;" class="text-end">Total (৳)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sale->items as $index => $item)
                @php
                    $prod = $item->product;
                    $specs = [];
                    if ($prod && $prod->thickness) $specs[] = 'Thick: ' . $prod->thickness;
                    if ($prod && $prod->size) $specs[] = 'Size: ' . $prod->size . ' ' . $prod->size_type;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $prod->name ?? 'Steel Item' }}</strong>
                        @if($prod && $prod->model) <span style="color:#64748b;">({{ $prod->model }})</span> @endif
                        @if(!empty($specs))
                            <div style="font-size: 9px; color: #475569; margin-top: 2px;">
                                {{ implode(' | ', $specs) }}
                            </div>
                        @endif
                    </td>
                    <td>
                        @if($item->lot)
                            <strong>{{ $item->lot->lot_number }}</strong>
                            <div style="font-size: 9px; color: #64748b;">{{ $item->lot->vendor->name ?? 'Mill Batch' }}</div>
                        @else
                            <span style="color: #94a3b8;">Standard Stock</span>
                        @endif
                    </td>
                    <td class="text-center font-bold">{{ $item->qty }}</td>
                    <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-end" style="font-weight: 700;">{{ number_format($item->total_price ?? ($item->qty * $item->unit_price), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 15px; color: #64748b;">No line items found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Financial Summary -->
    <div class="summary-wrapper">
        <div class="summary-card">
            <table>
                <tr>
                    <td>Sub Total:</td>
                    <td class="text-end font-bold">৳ {{ number_format($sale->total, 2) }}</td>
                </tr>
                @if($sale->discount > 0)
                <tr>
                    <td style="color: #16a34a;">Discount:</td>
                    <td class="text-end" style="color: #16a34a;">- ৳ {{ number_format($sale->discount, 2) }}</td>
                </tr>
                @endif
                @if($sale->delivery_charge > 0)
                <tr>
                    <td>Delivery / Transport:</td>
                    <td class="text-end">৳ {{ number_format($sale->delivery_charge, 2) }}</td>
                </tr>
                @endif
                @if($sale->labour_cost > 0)
                <tr>
                    <td>Labour / Loading:</td>
                    <td class="text-end">৳ {{ number_format($sale->labour_cost, 2) }}</td>
                </tr>
                @endif
                @if($sale->weight_scale_cost > 0)
                <tr>
                    <td>Weight Scale Charge:</td>
                    <td class="text-end">৳ {{ number_format($sale->weight_scale_cost, 2) }}</td>
                </tr>
                @endif
                @if($sale->other_charges > 0)
                <tr>
                    <td>Other Charges:</td>
                    <td class="text-end">৳ {{ number_format($sale->other_charges, 2) }}</td>
                </tr>
                @endif
                <tr style="border-top: 1px solid #cbd5e1;">
                    <td style="font-weight: 700; font-size: 11.5px;">Grand Total:</td>
                    <td class="text-end" style="font-weight: 800; font-size: 11.5px; color: #0f172a;">৳ {{ number_format($sale->payble, 2) }}</td>
                </tr>
                <tr>
                    <td style="color: #16a34a; font-weight: 600;">Paid Amount:</td>
                    <td class="text-end" style="color: #16a34a; font-weight: 700;">৳ {{ number_format($sale->advanced_payment, 2) }}</td>
                </tr>
                <tr style="border-top: 1px dashed #cbd5e1;">
                    <td style="color: #ef4444; font-weight: 700;">Invoice Due:</td>
                    <td class="text-end" style="color: #ef4444; font-weight: 800;">৳ {{ number_format($sale->due_payment, 2) }}</td>
                </tr>
            </table>
        </div>
        <div style="clear: both;"></div>
    </div>

    <!-- Signatures -->
    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <div class="signature-line" style="margin-left: 0;">
                    Customer Acceptance
                </div>
            </td>
            <td style="width: 50%; text-align: right;">
                <div class="signature-line" style="margin-left: auto; margin-right: 0;">
                    Authorized Signature
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
