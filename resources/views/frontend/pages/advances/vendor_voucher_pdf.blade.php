@php
    $padPath = public_path('assets/invoice/inoodex_invoice.jpg');
    $padBase64 = file_exists($padPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($padPath)) : (function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Vendor Advance Payment Voucher #{{ $payment->id }}</title>
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
            font-size: 12px;
            line-height: 1.4;
        }
        .header-title {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        table.items-table {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 16px;
            overflow: hidden;
        }
        table.items-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            padding: 8px 10px;
            text-align: left;
        }
        table.items-table td {
            padding: 10px;
            font-size: 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #0f172a;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: 700; }
        .summary-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 12px 16px;
            width: 280px;
            margin-left: auto;
            margin-bottom: 24px;
        }
        .signature-table {
            width: 100%;
            margin-top: 40px;
        }
        .sig-line {
            width: 180px;
            border-top: 1.5px solid #475569;
            text-align: center;
            padding-top: 6px;
            font-size: 11px;
            color: #475569;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="header-title">Vendor Advance Payment Voucher</div>
    
    <div class="meta-card">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;"><strong>Voucher No:</strong> ADV-VOU-{{ str_pad($payment->id, 5, '0', STR_PAD_LEFT) }}</td>
                <td style="width: 50%; text-align: right;"><strong>Payment Date:</strong> {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : date('d M Y') }}</td>
            </tr>
            <tr>
                <td><strong>Vendor / Supplier:</strong> {{ $payment->vendor?->name }} ({{ $payment->vendor?->phone }})</td>
                <td style="text-align: right;"><strong>Company:</strong> {{ $payment->vendor?->company ?: 'Mill' }}</td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Disbursed From Account</th>
                <th>TrxID / Reference</th>
                <th class="text-end">Amount Disbursed</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="fw-bold">{{ $payment->remarks ?: 'Vendor Advance Prepayment / Procurement Deposit' }}</div>
                    <small style="color: #64748b;">Advance booking deposit credited to supplier ledger</small>
                </td>
                <td>
                    @if($payment->payment_method === 'cash')
                        Cash in Hand
                    @elseif($payment->bankDetail)
                        {{ $payment->bankDetail->bank_name }} ({{ $payment->bankDetail->account_number }})
                    @else
                        {{ ucfirst($payment->payment_method) }}
                    @endif
                </td>
                <td>{{ $payment->transaction_ref ?: 'Cash Payment' }}</td>
                <td class="text-end fw-bold" style="color: #166534; font-size: 14px;">
                    ৳{{ number_format($payment->amount, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="summary-card">
        <table style="width: 100%;">
            <tr>
                <td>Total Disbursed:</td>
                <td class="text-end fw-bold" style="color: #166534; font-size: 14px;">৳{{ number_format($payment->amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <div class="sig-line" style="margin-left: 0;">Recipient Signature</div>
            </td>
            <td style="width: 50%;">
                <div class="sig-line" style="margin-left: auto;">Authorized Signature</div>
            </td>
        </tr>
    </table>

</body>
</html>
