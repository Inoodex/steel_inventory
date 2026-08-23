<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Invoice #{{ $sales->order_no }}</title>
    @php
        $padBase64 = function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '';
        if (!$padBase64) {
            $padPath = public_path('assets/invoice/pad.png');
            if (file_exists($padPath)) {
                $padBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($padPath));
            }
        }
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
    <!-- Top Memo Header: MEMO NO & Date -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 6px;">
        <tr>
            <td style="width: 50%; vertical-align: middle;">
                <table style="border-collapse: collapse; background-color: #f97316; border-radius: 3px;">
                    <tr>
                        <td style="color: #ffffff; font-weight: 800; font-size: 12px; padding: 4px 6px 4px 10px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;">
                            MEMO NO:
                        </td>
                        <td style="color: #ffffff; font-size: 13px; font-weight: 800; padding: 4px 12px 4px 4px; white-space: nowrap;">
                            {{ $sales->order_no }}
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: middle; text-align: right;">
                <span style="font-size: 13px; font-weight: 700; color: #0f172a;">Date:</span>
                <span style="display: inline-block; font-size: 13px; font-weight: 700; color: #0f172a; border-bottom: 1.5px solid #0f172a; padding: 0 10px 2px 10px; min-width: 120px; text-align: center;">
                    {{ $sales->created_at ? $sales->created_at->format('d.m.Y') : date('d.m.Y') }}
                </span>
            </td>
        </tr>
    </table>

    <!-- Customer Memo Info: Name & Address with underline -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
        <tr>
            <td style="width: 70px; font-size: 13px; font-weight: 700; color: #0f172a; padding: 4px 0; vertical-align: bottom;">Name:</td>
            <td style="border-bottom: 1.5px solid #475569; font-size: 13px; font-weight: 700; color: #0f172a; padding: 4px 8px; vertical-align: bottom;">
                {{ $customer->name ?? 'N/A' }}
            </td>
        </tr>
        <tr>
            <td style="width: 70px; font-size: 13px; font-weight: 700; color: #0f172a; padding: 4px 0; vertical-align: bottom;">Address:</td>
            <td style="border-bottom: 1.5px solid #475569; font-size: 12px; color: #334155; padding: 4px 8px; vertical-align: bottom;">
                {{ $customer->address ?? 'N/A' }}
            </td>
        </tr>
        <tr>
            <td style="width: 70px; font-size: 13px; font-weight: 700; color: #0f172a; padding: 4px 0; vertical-align: bottom;">Phone:</td>
            <td style="border-bottom: 1.5px solid #475569; font-size: 12px; color: #334155; padding: 4px 8px; vertical-align: bottom;">
                {{ $customer->phone }}
            </td>
        </tr>
    </table>

    <!-- Items Table with individual box margins/borders -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
        <thead>
            <tr>
                <th style="border: 1px solid #334155; background-color: #f1f5f9; color: #0f172a; padding: 6px 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; text-align: center; width: 8%;"><strong>SL NO.</strong></th>
                <th style="border: 1px solid #334155; background-color: #f1f5f9; color: #0f172a; padding: 6px 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; text-align: left; width: 48%;"><strong>DESCRIPTION</strong></th>
                <th style="border: 1px solid #334155; background-color: #f1f5f9; color: #0f172a; padding: 6px 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; text-align: center; width: 14%;"><strong>QTY</strong></th>
                <th style="border: 1px solid #334155; background-color: #f1f5f9; color: #0f172a; padding: 6px 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; text-align: right; width: 14%;"><strong>UNIT PRICE</strong></th>
                <th style="border: 1px solid #334155; background-color: #f1f5f9; color: #0f172a; padding: 6px 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; text-align: right; width: 16%;"><strong>AMOUNT</strong></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                @php
                    $coil = $item->coil ?? $item->product;
                    $coilNumber = $coil ? $coil->coil_number : ($item->name ?? 'Steel Coil');
                    $thickness = $item->thickness ?: ($coil ? $coil->thickness : '');
                    $size = $item->size ?: ($coil ? $coil->width : '');
                    $sizeType = $item->size_type ?: ($coil ? ($coil->length ?: $coil->size_type) : 'ft');
                @endphp
                <tr>
                    <td style="border: 1px solid #334155; padding: 7px 6px; font-size: 11px; color: #0f172a; text-align: center;">{{ $loop->index + 1 }}</td>
                    <td style="border: 1px solid #334155; padding: 7px 8px; font-size: 11px; color: #0f172a;">
                        <strong>#{{ $coilNumber }}</strong>
                        @if($thickness || $size)
                            <span style="font-size: 10px; color: #475569; margin-left: 6px;">
                                ({{ $thickness ? 'Thick: '.$thickness : '' }}{{ ($thickness && $size) ? ' | ' : '' }}{{ $size ? 'Size: '.$size.' '.$sizeType : '' }})
                            </span>
                        @endif
                    </td>
                    <td style="border: 1px solid #334155; padding: 7px 6px; font-size: 11px; color: #0f172a; text-align: center;">{{ number_format($item->qty ?? 0) }}</td>
                    <td style="border: 1px solid #334155; padding: 7px 6px; font-size: 11px; color: #0f172a; text-align: right;">{{ $item->unit_price ? number_format($item->unit_price, 2) : '0.00' }}</td>
                    <td style="border: 1px solid #334155; padding: 7px 8px; font-size: 11px; font-weight: 700; color: #0f172a; text-align: right;">{{ $item->total_price ? number_format($item->total_price, 2) : '0.00' }}</td>
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
    <table style="width: 100%; border-collapse: collapse; background: #fff5f5; margin-bottom: 16px;">
        <thead>
            <tr>
                <th style="border: 1px solid #f87171; background-color: #dc2626; color: white; padding: 6px; font-size: 10px; font-weight: 800; text-align: center;"><strong>Return #</strong></th>
                <th style="border: 1px solid #f87171; background-color: #dc2626; color: white; padding: 6px; font-size: 10px; font-weight: 800; text-align: left;"><strong>Product / Coil</strong></th>
                <th style="border: 1px solid #f87171; background-color: #dc2626; color: white; padding: 6px; font-size: 10px; font-weight: 800; text-align: center;"><strong>Qty (kg)</strong></th>
                <th style="border: 1px solid #f87171; background-color: #dc2626; color: white; padding: 6px; font-size: 10px; font-weight: 800; text-align: right;"><strong>Refund Amount</strong></th>
            </tr>
        </thead>
        <tbody>
            @foreach($completedReturns as $return)
                @foreach($return->items as $returnItem)
                    <tr>
                        <td style="border: 1px solid #fecaca; padding: 6px; font-size: 10px; text-align: center;">#{{ $return->id }}</td>
                        <td style="border: 1px solid #fecaca; padding: 6px; font-size: 10px;">{{ $returnItem->product->name ?? 'Coil' }}</td>
                        <td style="border: 1px solid #fecaca; padding: 6px; font-size: 10px; text-align: center;">{{ number_format($returnItem->quantity, 2) }}</td>
                        <td style="border: 1px solid #fecaca; padding: 6px; font-size: 10px; text-align: right;">{{ number_format($returnItem->total_price, 2) }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Summary & Financials Grid -->
    <table style="width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 16px;">
        <tr>
            <td style="width: 48%; vertical-align: top; padding-right: 10px;">
                <!-- Left column placeholder -->
            </td>
            <td style="width: 52%; vertical-align: top; padding-left: 10px;">
                <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px;">
                    <tr>
                        <td style="padding: 10px 12px;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                                <tr>
                                    <td style="padding: 2px 0; color: #475569;">Sub Total:</td>
                                    <td style="padding: 2px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ number_format($sales->total ?? $sales->bill, 2) }}</td>
                                </tr>
                                @if(($sales->discount ?? 0) > 0)
                                <tr>
                                    <td style="padding: 2px 0; color: #16a34a;">Discount:</td>
                                    <td style="padding: 2px 0; text-align: right; font-weight: 600; color: #16a34a;">- {{ number_format($sales->discount, 2) }}</td>
                                </tr>
                                @endif
                                @if(($sales->vat ?? 0) > 0)
                                @php $vatAmount = (($sales->total ?? $sales->bill) * $sales->vat) / 100; @endphp
                                <tr>
                                    <td style="padding: 2px 0; color: #475569;">VAT ({{ number_format($sales->vat, 2) }}%):</td>
                                    <td style="padding: 2px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ number_format($vatAmount, 2) }}</td>
                                </tr>
                                @endif
                                @if(($sales->tax ?? 0) > 0)
                                @php $taxAmount = (($sales->total ?? $sales->bill) * $sales->tax) / 100; @endphp
                                <tr>
                                    <td style="padding: 2px 0; color: #475569;">Tax ({{ number_format($sales->tax, 2) }}%):</td>
                                    <td style="padding: 2px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ number_format($taxAmount, 2) }}</td>
                                </tr>
                                @endif
                                @if(($sales->delivery_charge ?? 0) > 0)
                                <tr>
                                    <td style="padding: 2px 0; color: #475569;">Delivery Charge:</td>
                                    <td style="padding: 2px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ number_format($sales->delivery_charge, 2) }}</td>
                                </tr>
                                @endif
                                @if(($sales->labour_cost ?? 0) > 0)
                                <tr>
                                    <td style="padding: 2px 0; color: #475569;">Labour Cost:</td>
                                    <td style="padding: 2px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ number_format($sales->labour_cost, 2) }}</td>
                                </tr>
                                @endif
                                @if(($sales->weight_scale_cost ?? 0) > 0)
                                <tr>
                                    <td style="padding: 2px 0; color: #475569;">Weight Scale Fee:</td>
                                    <td style="padding: 2px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ number_format($sales->weight_scale_cost, 2) }}</td>
                                </tr>
                                @endif
                                @if(($sales->other_charges ?? 0) > 0)
                                <tr>
                                    <td style="padding: 2px 0; color: #475569;">Other Charges:</td>
                                    <td style="padding: 2px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ number_format($sales->other_charges, 2) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding: 4px 0; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; font-size: 12px; font-weight: 800; color: #f97316;">Grand Total:</td>
                                    <td style="padding: 4px 0; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; text-align: right; font-size: 12px; font-weight: 800; color: #f97316;">{{ number_format($sales->payble, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 2px 0; color: #16a34a; font-weight: 600;">Paid Amount:</td>
                                    <td style="padding: 2px 0; text-align: right; font-weight: 700; color: #16a34a;">{{ number_format($sales->advanced_payment ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 2px 0; font-weight: 800; color: #dc2626;">Invoice Due:</td>
                                    <td style="padding: 2px 0; text-align: right; font-size: 12px; font-weight: 800; color: {{ ($sales->due_payment ?? 0) > 0 ? '#dc2626' : '#16a34a' }};">{{ number_format($sales->due_payment ?? 0, 2) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Amount In Words Card -->
    <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px;">
        <tr>
            <td style="padding: 8px 12px; font-size: 11px; color: #334155;">
                <strong style="color: #f97316; margin-right: 6px;">Amount In Words:</strong>
                {{ numberToWords((float)($sales->payble ?? $sales->bill ?? 0)) }} Taka Only
            </td>
        </tr>
    </table>

</body>
</html>