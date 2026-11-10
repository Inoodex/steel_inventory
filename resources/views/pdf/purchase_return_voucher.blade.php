@php
    $padPath = public_path('assets/invoice/inoodex_invoice.jpg');
    $padBase64 = file_exists($padPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($padPath)) : (function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Return Voucher - {{ $return->return_no }}</title>
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
            color: #0f172a;
            font-size: 12px;
            line-height: 1.4;
        }

        .header-section {
            width: 100%;
            margin-bottom: 16px;
            border-bottom: 1.5px solid #cbd5e1;
            padding-bottom: 10px;
        }

        .header-title {
            font-size: 26px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .header-subtitle {
            font-size: 11px;
            color: #64748b;
        }

        .meta-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            color: #475569;
            font-size: 11px;
            margin-bottom: 16px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 4px 6px;
            vertical-align: top;
            font-size: 11px;
        }

        .meta-label {
            color: #64748b;
            font-weight: 600;
            width: 20%;
        }

        .meta-val {
            color: #0f172a;
            font-weight: 700;
            width: 30%;
        }

        .items-table {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            padding: 8px 10px;
            text-align: left;
            letter-spacing: 0.5px;
        }

        .items-table th.text-right {
            text-align: right;
        }

        .items-table td {
            padding: 8px 10px;
            font-size: 11px;
            color: #0f172a;
            border-bottom: 1px solid #f1f5f9;
        }

        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .items-table tr.total-row td {
            background-color: #f1f5f9;
            font-weight: 800;
            border-top: 1.5px solid #cbd5e1;
            border-bottom: none;
        }

        .text-right {
            text-align: right;
        }

        .summary-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 24px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 3px 0;
            font-size: 11.5px;
        }

        .badge-type {
            display: inline-block;
            background-color: #e0e7ff;
            color: #4338ca;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
        }

        .signature-section {
            margin-top: 30px;
            width: 100%;
        }

        .sig-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sig-box {
            width: 180px;
            text-align: center;
        }

        .sig-line {
            border-top: 1.5px solid #475569;
            padding-top: 5px;
            font-size: 10px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

    <div class="header-section">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: top;">
                    <div class="header-title">Purchase Return Voucher</div>
                    <div class="header-subtitle">Vendor Debit Note &amp; Material Return Gate Pass</div>
                </td>
                <td style="text-align: right; vertical-align: top;">
                    <div style="font-size: 14px; font-weight: 800; color: #4338ca;">{{ $return->return_no }}</div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                        Date: {{ $return->return_date ? $return->return_date->format('d M Y') : 'N/A' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Vendor & Consignment Meta Card -->
    <div class="meta-card">
        <table class="meta-table">
            <tr>
                <td class="meta-label">Vendor / Supplier:</td>
                <td class="meta-val">{{ $return->vendor?->name ?? 'N/A' }}</td>
                <td class="meta-label">Return Date:</td>
                <td class="meta-val">{{ $return->return_date ? $return->return_date->format('d M Y') : 'N/A' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Vendor Phone:</td>
                <td class="meta-val">{{ $return->vendor?->phone ?? 'N/A' }}</td>
                <td class="meta-label">Original Consignment:</td>
                <td class="meta-val">
                    PO-{{ $return->purchase_id }}
                    @if($return->lot)
                        (Lot: {{ $return->lot->lot_number }})
                    @endif
                </td>
            </tr>
            <tr>
                <td class="meta-label">Stockyard / Shop:</td>
                <td class="meta-val">{{ $return->warehouse?->name ?? 'Main Stockyard' }}</td>
                <td class="meta-label">Settlement Mode:</td>
                <td class="meta-val">
                    <span class="badge-type">{{ ucwords(str_replace('_', ' ', $return->refund_type)) }}</span>
                </td>
            </tr>
            @if($return->bankDetail)
            <tr>
                <td class="meta-label">Bank Settlement:</td>
                <td class="meta-val" colspan="3">
                    {{ $return->bankDetail->bank_name }} - A/C {{ $return->bankDetail->account_number }}
                    @if($return->transaction_ref)
                        (Ref: {{ $return->transaction_ref }})
                    @endif
                </td>
            </tr>
            @endif
            <tr>
                <td class="meta-label">Return Reason:</td>
                <td class="meta-val" colspan="3" style="text-transform: capitalize;">
                    {{ str_replace('_', ' ', $return->reason ?? 'Defective') }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Items Specifications Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 25%;">Coil Tag / Lot</th>
                <th style="width: 30%;">Product Specifications</th>
                <th style="width: 15%;" class="text-right">Return Wt (kg)</th>
                <th style="width: 10%;" class="text-right">Cost Rate</th>
                <th style="width: 15%;" class="text-right">Refund Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>
                    <strong style="color: #0f172a;">{{ $return->coil?->coil_number ?? 'Direct Consignment' }}</strong>
                    @if($return->lot)
                        <div style="font-size: 9.5px; color: #64748b;">Lot: {{ $return->lot->lot_number }}</div>
                    @endif
                </td>
                <td>
                    <strong>{{ $return->purchase?->product_name ?? 'Steel Consignment' }}</strong>
                    <div style="font-size: 10px; color: #475569; margin-top: 2px;">
                        {{ $return->purchase?->thickness }} {{ $return->purchase?->size }}
                    </div>
                </td>
                <td class="text-right" style="font-weight: 700; color: #dc2626;">
                    -{{ number_format($return->return_weight, 2) }} kg
                    <div style="font-size: 9.5px; color: #64748b;">(-{{ number_format($return->return_weight / 1000, 3) }} MT)</div>
                </td>
                <td class="text-right">
                    ৳ {{ number_format($return->unit_price, 2) }}
                </td>
                <td class="text-right" style="font-weight: 800; color: #15803d; font-size: 12px;">
                    ৳ {{ number_format($return->total_refund_amount, 2) }}
                </td>
            </tr>

            <!-- Totals Row -->
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL MATERIAL RETURN VALUE:</td>
                <td class="text-right" style="color: #dc2626;">-{{ number_format($return->return_weight, 2) }} kg</td>
                <td></td>
                <td class="text-right" style="color: #15803d; font-size: 13px;">৳ {{ number_format($return->total_refund_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Summary / Gate Pass Card -->
    <div class="summary-card">
        <table class="summary-table">
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    <strong style="font-size: 11px; color: #0f172a; text-transform: uppercase;">Gate Pass &amp; Transport Remarks:</strong>
                    <p style="margin-top: 4px; color: #334155; font-size: 10.5px; line-height: 1.4;">
                        {{ $return->notes ?: 'Stock deducted from inventory. Delivered back to vendor dispatch address.' }}
                    </p>
                </td>
                <td style="width: 40%; vertical-align: top; text-align: right;">
                    <div style="font-size: 11px; color: #64748b;">Total Net Credit Claim:</div>
                    <div style="font-size: 18px; font-weight: 800; color: #15803d; margin-top: 2px;">
                        BDT {{ number_format($return->total_refund_amount, 2) }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Signature Block -->
    <div class="signature-section">
        <table class="sig-table">
            <tr>
                <td style="width: 33.33%;">
                    <div class="sig-box">
                        <div class="sig-line">Prepared By (Store)</div>
                    </div>
                </td>
                <td style="width: 33.33%; text-align: center;">
                    <div class="sig-box" style="margin: 0 auto;">
                        <div class="sig-line">Gate Security / Dispatch</div>
                    </div>
                </td>
                <td style="width: 33.33%; text-align: right;">
                    <div class="sig-box" style="margin-left: auto;">
                        <div class="sig-line">Authorized Signature</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>

