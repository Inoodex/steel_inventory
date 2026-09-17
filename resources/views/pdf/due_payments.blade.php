<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Customer Due Payments Report</title>
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
            font-size: 12px;
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
            margin-bottom: 2px;
        }

        .report-title p {
            font-size: 11px;
            color: #64748b;
        }

        .summary-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 11px;
            color: #334155;
            margin-bottom: 18px;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 20px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
        }

        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            padding: 8px 10px;
            font-size: 10.5px;
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
                <h1>CUSTOMER DUES REPORT</h1>
                <p>Generated: {{ date('d M Y, h:i A') }}</p>
            </td>
        </tr>
    </table>

    <!-- Summary Card -->
    <div class="summary-card">
        <strong>Total Customers with Dues:</strong> {{ count($customersWithDue ?? []) }} &nbsp;|&nbsp;
        <strong>Opening Dues:</strong> <span style="color: #b45309; font-weight: bold;">৳{{ number_format($totalOpeningDues ?? 0, 2) }}</span> &nbsp;|&nbsp;
        <strong>Sales Orders Due:</strong> <span style="color: #0284c7; font-weight: bold;">৳{{ number_format($totalInvoiceDues ?? 0, 2) }}</span> &nbsp;|&nbsp;
        <strong>Grand Outstanding:</strong> <span style="color: #dc2626; font-weight: bold;">৳{{ number_format($grandTotalDues ?? 0, 2) }}</span>
    </div>

    <!-- Section 1: Customer Summary Table -->
    <div class="section-title">1. Customer Dues Summary (Opening + Sales Dues)</div>
    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">#</th>
                <th style="width: 35%; text-align: left;">Customer Name &amp; Contact</th>
                <th style="width: 20%; text-align: right;">Opening Due</th>
                <th style="width: 20%; text-align: right;">Sales Invoices Due</th>
                <th style="width: 20%; text-align: right;">Total Due</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customersWithDue ?? [] as $index => $c)
                <tr style="background-color: {{ $loop->even ? '#f8fafc' : '#ffffff' }};">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $c->name }}</strong><br>
                        <small style="color: #64748b;">{{ $c->phone }}</small>
                    </td>
                    <td class="text-right" style="color: #b45309;">৳{{ number_format($c->opening_due, 2) }}</td>
                    <td class="text-right" style="color: #0284c7;">৳{{ number_format($c->sales_due, 2) }}</td>
                    <td class="text-right fw-bold" style="color: #dc2626;">৳{{ number_format($c->total_due, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 15px; color: #64748b;">No customer dues outstanding.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Section 2: Unpaid Sales Invoices Table -->
    <div class="section-title">2. Unpaid Sales Orders Breakdown</div>
    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">#</th>
                <th style="width: 13%; text-align: left;">Date</th>
                <th style="width: 17%; text-align: left;">Order No</th>
                <th style="width: 29%; text-align: left;">Customer Name</th>
                <th style="width: 12%; text-align: right;">Payable</th>
                <th style="width: 12%; text-align: right;">Paid</th>
                <th style="width: 12%; text-align: right;">Due</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $index => $item)
                @php
                    $customerName = $item->customer->name ?? 'N/A';
                @endphp
                <tr style="background-color: {{ $loop->even ? '#f8fafc' : '#ffffff' }};">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->created_at ? $item->created_at->format('d-m-Y') : 'N/A' }}</td>
                    <td class="fw-bold">#{{ $item->order_no }}</td>
                    <td class="fw-bold">{{ $customerName }}</td>
                    <td class="text-right">৳{{ number_format($item->payble ?? 0, 2) }}</td>
                    <td class="text-right" style="color: #16a34a;">৳{{ number_format($item->advanced_payment ?? 0, 2) }}</td>
                    <td class="text-right fw-bold" style="color: #dc2626;">৳{{ number_format($item->due_payment ?? 0, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 15px; color: #64748b;">No unpaid sales orders found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Signature Block -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 30px;">
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
