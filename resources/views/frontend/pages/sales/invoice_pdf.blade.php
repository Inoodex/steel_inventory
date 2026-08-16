<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Invoice #{{ $sales->order_no }}</title>
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
            margin-top: 42mm;
            margin-bottom: 15mm;
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
    </style>
</head>

<body>
    <!-- Header Title & Order Meta -->
    <table style="width: 100%; margin-bottom: 15px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">
        <tr>
            <td style="width: 50%;">
                <div style="font-size: 11px; color: #475569;">
                    Warehouse: <strong style="color: #0f172a;">{{ $sales->warehouse->name ?? 'Main Steel Yard' }}</strong><br>
                    Delivery Status: <strong style="color: #0f172a; text-transform: uppercase;">{{ str_replace('_', ' ', $sales->delivery_status ?? 'Pending') }}</strong>
                </div>
            </td>
            <td align="right" style="width: 50%;">
                <h1 style="font-size: 24px; color: #0f172a; margin-bottom: 3px;">SALES INVOICE</h1>
                <div style="font-size: 14px; font-weight: 700; color: #4f46e5;">Invoice No: #{{ $sales->order_no }}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Invoice Date: {{ $sales->created_at ? $sales->created_at->format('d M Y') : date('d M Y') }}</div>
            </td>
        </tr>
    </table>

    <!-- Customer Info Card -->
    <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; margin-bottom: 20px;">
        <tr>
            <td style="padding: 10px 14px; width: 33.33%; vertical-align: top;">
                <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-bottom: 3px;">CUSTOMER / CLIENT</div>
                <div style="font-size: 12px; font-weight: 700; color: #0f172a;">{{ $customer->name ?? 'N/A' }}</div>
            </td>
            <td style="padding: 10px 14px; width: 33.33%; vertical-align: top; border-left: 1px solid #cbd5e1;">
                <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-bottom: 3px;">PHONE NUMBER</div>
                <div style="font-size: 12px; color: #0f172a;">{{ $customer->phone ?? 'N/A' }}</div>
            </td>
            <td style="padding: 10px 14px; width: 33.33%; vertical-align: top; border-left: 1px solid #cbd5e1;">
                <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-bottom: 3px;">ADDRESS / LOCATION</div>
                <div style="font-size: 12px; color: #0f172a;">{{ $customer->address ?? 'N/A' }}</div>
            </td>
        </tr>
        @if($sales->note)
        <tr>
            <td colspan="3" style="padding: 6px 14px 10px 14px; border-top: 1px dashed #cbd5e1; font-size: 10px; color: #475569;">
                <strong style="color: #0f172a;">Transport / Note:</strong> {{ $sales->note }}
            </td>
        </tr>
        @endif
    </table>

    <!-- Items Table -->
    <table style="width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 20px; border: 1px solid #cbd5e1; border-radius: 10px; overflow: hidden;">
        <thead>
            <tr>
                <th style="background-color: #1e293b; color: #ffffff; padding: 8px 10px; font-size: 10px; font-weight: 700; text-transform: uppercase; text-align: center; width: 5%;">#</th>
                <th style="background-color: #1e293b; color: #ffffff; padding: 8px 10px; font-size: 10px; font-weight: 700; text-transform: uppercase; text-align: left; width: 38%;">Steel Product &amp; Specifications</th>
                <th style="background-color: #1e293b; color: #ffffff; padding: 8px 10px; font-size: 10px; font-weight: 700; text-transform: uppercase; text-align: left; width: 22%;">Mill Lot Source</th>
                <th style="background-color: #1e293b; color: #ffffff; padding: 8px 10px; font-size: 10px; font-weight: 700; text-transform: uppercase; text-align: center; width: 10%;">Qty</th>
                <th style="background-color: #1e293b; color: #ffffff; padding: 8px 10px; font-size: 10px; font-weight: 700; text-transform: uppercase; text-align: right; width: 12%;">Unit Price</th>
                <th style="background-color: #1e293b; color: #ffffff; padding: 8px 10px; font-size: 10px; font-weight: 700; text-transform: uppercase; text-align: right; width: 13%;">Total Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                @php
                    $prodName = $item->product ? $item->product->name : ($item->name ?? 'Steel Product');
                    $prodModel = $item->product ? $item->product->model : ($item->model ?? '');
                    $thickness = $item->product ? $item->product->thickness : '';
                    $size = $item->product ? ($item->product->size . ' ' . $item->product->size_type) : '';
                @endphp
                <tr style="background-color: {{ $loop->even ? '#f8fafc' : '#ffffff' }};">
                    <td style="padding: 8px 10px; font-size: 11px; color: #334155; border-bottom: 1px solid #f1f5f9; text-align: center;">{{ $loop->index + 1 }}</td>
                    <td style="padding: 8px 10px; font-size: 11px; color: #334155; border-bottom: 1px solid #f1f5f9;">
                        <strong>{{ $prodName }}</strong>
                        @if(!empty($prodModel))
                            <span style="font-size: 10px; color: #64748b;">({{ $prodModel }})</span>
                        @endif
                        @if($thickness || $size)
                            <div style="font-size: 9px; color: #475569; margin-top: 1px;">
                                {{ $thickness ? 'Thick: '.$thickness : '' }} {{ $size ? ' | Size: '.$size : '' }}
                            </div>
                        @endif
                    </td>
                    <td style="padding: 8px 10px; font-size: 10px; color: #334155; border-bottom: 1px solid #f1f5f9;">
                        @if($item->lot)
                            <strong>{{ $item->lot->lot_number }}</strong>
                            <div style="font-size: 9px; color: #64748b;">{{ $item->lot->vendor->name ?? 'Mill Batch' }}</div>
                        @else
                            <span style="color: #94a3b8;">Standard Stock</span>
                        @endif
                    </td>
                    <td style="padding: 8px 10px; font-size: 11px; color: #334155; border-bottom: 1px solid #f1f5f9; text-align: center;">{{ $item->qty ?? 1 }}</td>
                    <td style="padding: 8px 10px; font-size: 11px; color: #334155; border-bottom: 1px solid #f1f5f9; text-align: right;">{{ $item->unit_price ? number_format($item->unit_price, 2) : '0.00' }}</td>
                    <td style="padding: 8px 10px; font-size: 11px; font-weight: 700; color: #334155; border-bottom: 1px solid #f1f5f9; text-align: right;">{{ $item->total_price ? number_format($item->total_price, 2) : '0.00' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $completedReturns = $returns ? $returns->where('status', 'completed') : collect();
        $totalRefundAmount = $completedReturns->sum(function($return) {
            return $return->total_refund_amount ?? $return->items->sum('total_price');
        });
        $hasReturns = $completedReturns->count() > 0;
    @endphp

    @if($hasReturns)
    <!-- Returns Section -->
    <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: #fff5f5; border: 1px dashed #f87171; border-radius: 10px; margin-bottom: 20px;">
        <tr>
            <td style="padding: 10px 14px;">
                <h4 style="color: #dc2626; font-size: 12px; font-weight: 700; margin-bottom: 6px;">Returned Items</h4>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="background-color: #dc2626; color: white; padding: 6px; font-size: 10px;">Return #</th>
                            <th style="background-color: #dc2626; color: white; padding: 6px; font-size: 10px;">Product</th>
                            <th style="background-color: #dc2626; color: white; padding: 6px; font-size: 10px; text-align: center;">Qty</th>
                            <th style="background-color: #dc2626; color: white; padding: 6px; font-size: 10px; text-align: right;">Refund Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($completedReturns as $return)
                            @foreach($return->items as $returnItem)
                                <tr>
                                    <td style="padding: 6px; font-size: 10px; border-bottom: 1px solid #fecaca;">#{{ $return->id }}</td>
                                    <td style="padding: 6px; font-size: 10px; border-bottom: 1px solid #fecaca;">{{ $returnItem->product->name ?? 'N/A' }}</td>
                                    <td style="padding: 6px; font-size: 10px; border-bottom: 1px solid #fecaca; text-align: center;">{{ $returnItem->quantity }}</td>
                                    <td style="padding: 6px; font-size: 10px; border-bottom: 1px solid #fecaca; text-align: right;">{{ number_format($returnItem->total_price, 2) }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
    @endif

    <!-- Summary & Terms Grid -->
    <table style="width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 20px;">
        <tr>
            <td style="width: 48%; vertical-align: top; padding-right: 10px;">
                <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px;">
                    <tr>
                        <td style="padding: 12px 14px;">
                            <h3 style="font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">Terms &amp; Conditions</h3>
                            <p style="font-size: 10px; color: #475569; line-height: 1.4; margin-bottom: 4px;">&bull; Steel products must be verified upon dispatch / weighbridge slip.</p>
                            <p style="font-size: 10px; color: #475569; line-height: 1.4; margin-bottom: 6px;">&bull; All claims must reference this invoice #{{ $sales->order_no }}.</p>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 52%; vertical-align: top; padding-left: 10px;">
                <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px;">
                    <tr>
                        <td style="padding: 12px 14px;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                                <tr>
                                    <td style="padding: 3px 0; color: #475569;">Sub Total:</td>
                                    <td style="padding: 3px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ number_format($sales->total ?? $sales->bill, 2) }}</td>
                                </tr>
                                @if(($sales->discount ?? 0) > 0)
                                <tr>
                                    <td style="padding: 3px 0; color: #16a34a;">Discount:</td>
                                    <td style="padding: 3px 0; text-align: right; font-weight: 600; color: #16a34a;">- {{ number_format($sales->discount, 2) }}</td>
                                </tr>
                                @endif
                                @if(($sales->vat ?? 0) > 0)
                                @php $vatAmount = (($sales->total ?? $sales->bill) * $sales->vat) / 100; @endphp
                                <tr>
                                    <td style="padding: 3px 0; color: #475569;">VAT ({{ number_format($sales->vat, 2) }}%):</td>
                                    <td style="padding: 3px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ number_format($vatAmount, 2) }}</td>
                                </tr>
                                @endif
                                @if(($sales->tax ?? 0) > 0)
                                @php $taxAmount = (($sales->total ?? $sales->bill) * $sales->tax) / 100; @endphp
                                <tr>
                                    <td style="padding: 3px 0; color: #475569;">Tax ({{ number_format($sales->tax, 2) }}%):</td>
                                    <td style="padding: 3px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ number_format($taxAmount, 2) }}</td>
                                </tr>
                                @endif
                                @if(($sales->delivery_charge ?? 0) > 0)
                                <tr>
                                    <td style="padding: 3px 0; color: #475569;">Delivery Charge:</td>
                                    <td style="padding: 3px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ number_format($sales->delivery_charge, 2) }}</td>
                                </tr>
                                @endif
                                @if(($sales->labour_cost ?? 0) > 0)
                                <tr>
                                    <td style="padding: 3px 0; color: #475569;">Labour / Loading:</td>
                                    <td style="padding: 3px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ number_format($sales->labour_cost, 2) }}</td>
                                </tr>
                                @endif
                                @if(($sales->weight_scale_cost ?? 0) > 0)
                                <tr>
                                    <td style="padding: 3px 0; color: #475569;">Weight Scale Fee:</td>
                                    <td style="padding: 3px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ number_format($sales->weight_scale_cost, 2) }}</td>
                                </tr>
                                @endif
                                @if(($sales->other_charges ?? 0) > 0)
                                <tr>
                                    <td style="padding: 3px 0; color: #475569;">Other Charges:</td>
                                    <td style="padding: 3px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ number_format($sales->other_charges, 2) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding: 5px 0; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; font-size: 12px; font-weight: 800; color: #4f46e5;">Grand Total:</td>
                                    <td style="padding: 5px 0; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; text-align: right; font-size: 12px; font-weight: 800; color: #4f46e5;">{{ number_format($sales->payble, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 3px 0; color: #16a34a; font-weight: 600;">Paid Amount:</td>
                                    <td style="padding: 3px 0; text-align: right; font-weight: 700; color: #16a34a;">{{ number_format($sales->advanced_payment ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 3px 0; font-weight: 800; color: #dc2626;">Invoice Due:</td>
                                    <td style="padding: 3px 0; text-align: right; font-weight: 800; color: {{ ($sales->due_payment ?? 0) > 0 ? '#dc2626' : '#16a34a' }};">{{ number_format($sales->due_payment ?? 0, 2) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Amount In Words Card -->
    <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 30px;">
        <tr>
            <td style="padding: 8px 14px; font-size: 11px; color: #334155;">
                <strong style="color: #4f46e5; margin-right: 6px;">Amount In Words:</strong>
                {{ numberToWords((float)($sales->payble ?? $sales->bill ?? 0)) }} Taka Only
            </td>
        </tr>
    </table>

    <!-- Signatures -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 40px;">
        <tr>
            <td width="50%" align="center" style="vertical-align: bottom;">
                <table align="center" style="width: 180px; margin: 0 auto 6px auto; border-collapse: collapse;">
                    <tr>
                        <td style="border-top: 1.5px solid #475569; height: 1px; font-size: 1px; line-height: 1px;">&nbsp;</td>
                    </tr>
                </table>
                <div style="font-size: 10px; font-weight: 600; color: #475569;">Customer Signature</div>
            </td>
            <td width="50%" align="center" style="vertical-align: bottom;">
                <table align="center" style="width: 180px; margin: 0 auto 6px auto; border-collapse: collapse;">
                    <tr>
                        <td style="border-top: 1.5px solid #475569; height: 1px; font-size: 1px; line-height: 1px;">&nbsp;</td>
                    </tr>
                </table>
                <div style="font-size: 10px; font-weight: 600; color: #475569;">Authorized Signature</div>
            </td>
        </tr>
    </table>

</body>
</html>