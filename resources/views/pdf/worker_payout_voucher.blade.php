<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Worker Charge Settlement Voucher - {{ $payout->payout_no }}</title>
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
            color: #0f172a;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header-title {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .header-subtitle {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 15px;
        }
        .meta-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 18px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 4px 8px;
            vertical-align: top;
            font-size: 11px;
        }
        .meta-label {
            color: #64748b;
            font-weight: 600;
            width: 18%;
        }
        .meta-val {
            color: #0f172a;
            font-weight: 700;
            width: 32%;
        }
        .badge-type {
            display: inline-block;
            background-color: #e0e7ff;
            color: #4338ca;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
        }
        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            padding: 8px 10px;
            text-align: left;
        }
        .items-table th.text-end {
            text-align: right;
        }
        .items-table td {
            padding: 7px 10px;
            font-size: 11px;
            border-bottom: 1px solid #f1f5f9;
        }
        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .text-end {
            text-align: right;
        }
        .summary-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 25px;
        }
        .amount-big {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
        }
        .signature-table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }
        .signature-line {
            width: 180px;
            border-top: 1.5px solid #475569;
            padding-top: 5px;
            text-align: center;
            font-size: 10px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <div class="header-title">Worker Charge Settlement Voucher</div>
    <div class="header-subtitle">Pass-Through Extra Charges Disbursed to Labour / Handlers / Drivers</div>

    <div class="meta-card">
        <table class="meta-table">
            <tr>
                <td class="meta-label">Voucher No:</td>
                <td class="meta-val" style="color: #4338ca; font-size: 13px;">{{ $payout->payout_no }}</td>
                <td class="meta-label">Disbursement Date:</td>
                <td class="meta-val">{{ $payout->payout_date ? $payout->payout_date->format('d M Y') : 'N/A' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Recipient / Worker:</td>
                <td class="meta-val">{{ $payout->recipient_name }}</td>
                <td class="meta-label">Recipient Phone:</td>
                <td class="meta-val">{{ $payout->recipient_phone ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Charge Category:</td>
                <td class="meta-val">
                    <span class="badge-type">{{ ucwords(str_replace('_', ' ', $payout->charge_type)) }}</span>
                </td>
                <td class="meta-label">Payment Method:</td>
                <td class="meta-val">{{ ucfirst($payout->payment_method) }}</td>
            </tr>
            <tr>
                <td class="meta-label">Paid From Account:</td>
                <td class="meta-val">[{{ $payout->paymentAccount->account_code ?? 'N/A' }}] {{ $payout->paymentAccount->account_name ?? 'Cash/Bank' }}</td>
                <td class="meta-label">Journal Voucher:</td>
                <td class="meta-val">{{ $payout->journalEntry->journal_no ?? 'Auto Posted' }}</td>
            </tr>
            @if($payout->notes)
            <tr>
                <td class="meta-label">Narration / Memo:</td>
                <td class="meta-val" colspan="3" style="font-weight: normal; color: #334155;">{{ $payout->notes }}</td>
            </tr>
            @endif
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 20%;">Invoice No</th>
                <th style="width: 15%;">Invoice Date</th>
                <th style="width: 30%;">Customer Name</th>
                <th style="width: 15%;">Charge Settled</th>
                <th style="width: 15%;" class="text-end">Amount (BDT)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payout->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td style="font-weight: 700; color: #1e293b;">#{{ $item->sale->order_no ?? 'N/A' }}</td>
                    <td>{{ $item->sale && $item->sale->order_date ? \Carbon\Carbon::parse($item->sale->order_date)->format('d M Y') : 'N/A' }}</td>
                    <td>{{ $item->sale->customer->name ?? 'Walk-in Customer' }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $item->charge_type)) }}</td>
                    <td class="text-end" style="font-weight: 700;">৳ {{ number_format($item->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #94a3b8; padding: 15px;">No itemized sales linked.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-card">
        <table style="width: 100%;">
            <tr>
                <td style="vertical-align: middle;">
                    <div style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 600;">Total Invoices Settled:</div>
                    <div style="font-size: 13px; font-weight: 700; color: #0f172a;">{{ $payout->items->count() }} sales invoices</div>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    <div style="font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 600;">Total Disbursed Amount:</div>
                    <div class="amount-big">৳ {{ number_format($payout->total_amount, 2) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="signature-table">
        <tr>
            <td style="width: 50%; vertical-align: bottom;">
                <div class="signature-line">
                    Received By<br>
                    <span style="font-size: 9px; color: #64748b; text-transform: none;">({{ $payout->recipient_name }})</span>
                </div>
            </td>
            <td style="width: 50%; text-align: right; vertical-align: bottom;">
                <div class="signature-line" style="margin-left: auto;">
                    Authorized Signature<br>
                    <span style="font-size: 9px; color: #64748b; text-transform: none;">(Accounts / Finance Dept)</span>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
