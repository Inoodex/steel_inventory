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
    <!-- Customer Info & Memo Grid (Top Section) -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 14px;">
        <tr>
            <!-- Left Column: Buyer Details -->
            <td style="width: 60%; vertical-align: top; padding: 0;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="border: 1px solid #334155; padding: 5px 8px; width: 115px; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #0f172a; background-color: #f8fafc;">
                            BUYER NAME
                        </td>
                        <td style="border: 1px solid #334155; padding: 5px 8px; font-size: 11px; font-weight: 700; color: #0f172a;">
                            {{ $customer->name ?? '' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #334155; padding: 5px 8px; width: 115px; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #0f172a; background-color: #f8fafc;">
                            BUYER ADDRESS
                        </td>
                        <td style="border: 1px solid #334155; padding: 5px 8px; font-size: 11px; color: #0f172a;">
                            {{ $customer->address ?? '' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #334155; padding: 5px 8px; width: 115px; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #0f172a; background-color: #f8fafc;">
                            PHONE NUMBER
                        </td>
                        <td style="border: 1px solid #334155; padding: 5px 8px; font-size: 11px; color: #0f172a;">
                            {{ $customer->phone ?? '' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #334155; padding: 5px 8px; width: 115px; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #0f172a; background-color: #f8fafc;">
                            REMARKS
                        </td>
                        <td style="border: 1px solid #334155; padding: 5px 8px; font-size: 11px; color: #0f172a;">
                            {{ $sales->note ?? '' }}
                        </td>
                    </tr>
                </table>
            </td>

            <!-- Right Column: Date & Memo No -->
            <td style="width: 40%; vertical-align: top; padding: 0; padding-left: 8px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="border: 1px solid #334155; padding: 5px 8px; width: 85px; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #0f172a; background-color: #f8fafc;">
                            DATE
                        </td>
                        <td style="border: 1px solid #334155; padding: 5px 8px; font-size: 11px; font-weight: 700; color: #0f172a;">
                            {{ $sales->created_at ? $sales->created_at->format('d.m.Y') : date('d.m.Y') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #334155; padding: 5px 8px; width: 85px; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #0f172a; background-color: #f8fafc;">
                            MEMO NO
                        </td>
                        <td style="border: 1px solid #334155; padding: 5px 8px; font-size: 11px; font-weight: 800; color: #0f172a;">
                            {{ $sales->order_no }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="border: 1px solid #334155; padding: 5px 8px; font-size: 11px; height: 23px;">
                            &nbsp;
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="border: 1px solid #334155; padding: 5px 8px; font-size: 11px; height: 23px;">
                            &nbsp;
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Items Table with individual box margins/borders -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
        <thead>
            <tr>
                <th style="border: 1px solid #334155; background-color: #f1f5f9; color: #0f172a; padding: 6px 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; text-align: center; width: 8%;"><strong>SL NO.</strong></th>
                <th style="border: 1px solid #334155; background-color: #f1f5f9; color: #0f172a; padding: 6px 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; text-align: center; width: 48%;"><strong>DESCRIPTION</strong></th>
                <th style="border: 1px solid #334155; background-color: #f1f5f9; color: #0f172a; padding: 6px 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; text-align: center; width: 14%;"><strong>QTY</strong></th>
                <th style="border: 1px solid #334155; background-color: #f1f5f9; color: #0f172a; padding: 6px 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; text-align: right; width: 14%;"><strong>UNIT PRICE</strong></th>
                <th style="border: 1px solid #334155; background-color: #f1f5f9; color: #0f172a; padding: 6px 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; text-align: right; width: 16%;"><strong>AMOUNT</strong></th>
            </tr>
        </thead>
        <tbody>
            @php
                $itemCollection = is_array($items) ? collect($items) : $items;
                $totalRows = min(15, max(10, $itemCollection->count()));
            @endphp
            @for ($i = 0; $i < $totalRows; $i++)
                @if(isset($itemCollection[$i]))
                    @php
                        $item = $itemCollection[$i];
                        $coil = $item->coil ?? $item->product;
                        $coilNumber = $coil ? $coil->coil_number : ($item->name ?? 'Steel Coil');
                        $thickness = $item->thickness ?: ($coil ? $coil->thickness : '');
                        $size = $item->size ?: ($coil ? $coil->width : '');
                        $sizeType = $item->size_type ?: ($coil ? ($coil->length ?: $coil->size_type) : 'ft');
                    @endphp
                    <tr>
                        <td style="border: 1px solid #334155; padding: 4px 6px; font-size: 11px; color: #0f172a; text-align: center; height: 21px;">{{ $i + 1 }}</td>
                        <td style="border: 1px solid #334155; padding: 4px 8px; font-size: 11px; color: #0f172a; height: 21px;">
                            <strong>{{ $coilNumber }}</strong>
                            @if($thickness || $size)
                                <span style="font-size: 10px; color: #475569; margin-left: 6px;">
                                    ({{ $thickness ? 'Thick: '.$thickness : '' }}{{ ($thickness && $size) ? ' | ' : '' }}{{ $size ? 'Size: '.$size.' '.$sizeType : '' }})
                                </span>
                            @endif
                        </td>
                        <td style="border: 1px solid #334155; padding: 4px 6px; font-size: 11px; color: #0f172a; text-align: center; height: 21px;">{{ number_format($item->qty ?? 0) }}</td>
                        <td style="border: 1px solid #334155; padding: 4px 6px; font-size: 11px; color: #0f172a; text-align: right; height: 21px;">{{ $item->unit_price ? number_format($item->unit_price, 2) : '0.00' }}</td>
                        <td style="border: 1px solid #334155; padding: 4px 8px; font-size: 11px; font-weight: 700; color: #0f172a; text-align: right; height: 21px;">{{ $item->total_price ? number_format($item->total_price, 2) : '0.00' }}</td>
                    </tr>
                @else
                    <tr>
                        <td style="border: 1px solid #334155; padding: 4px 6px; font-size: 11px; color: #94a3b8; text-align: center; height: 21px;">{{ $i + 1 }}</td>
                        <td style="border: 1px solid #334155; padding: 4px 8px; font-size: 11px; color: #0f172a; height: 21px;">&nbsp;</td>
                        <td style="border: 1px solid #334155; padding: 4px 6px; font-size: 11px; color: #0f172a; text-align: center; height: 21px;">&nbsp;</td>
                        <td style="border: 1px solid #334155; padding: 4px 6px; font-size: 11px; color: #0f172a; text-align: right; height: 21px;">&nbsp;</td>
                        <td style="border: 1px solid #334155; padding: 4px 8px; font-size: 11px; color: #0f172a; text-align: right; height: 21px;">&nbsp;</td>
                    </tr>
                @endif
            @endfor
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
                                    <td style="padding: 2px 0; color: #dc2626;">Discount:</td>
                                    <td style="padding: 2px 0; text-align: right; font-weight: 600; color: #dc2626;">- {{ number_format($sales->discount, 2) }}</td>
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
                                @if(($sales->weight_scale_cost ?? 0) > 0)
                                <tr>
                                    <td style="padding: 2px 0; color: #475569;">Scale & Labour Charge:</td>
                                    <td style="padding: 2px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ number_format($sales->weight_scale_cost, 2) }}</td>
                                </tr>
                                @endif
                                @if(($sales->labour_cost ?? 0) > 0)
                                <tr>
                                    <td style="padding: 2px 0; color: #475569;">Cutting & Labour Load-Unload:</td>
                                    <td style="padding: 2px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ number_format($sales->labour_cost, 2) }}</td>
                                </tr>
                                @endif
                                @if(($sales->delivery_charge ?? 0) > 0)
                                <tr>
                                    <td style="padding: 2px 0; color: #475569;">Transport Bill:</td>
                                    <td style="padding: 2px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ number_format($sales->delivery_charge, 2) }}</td>
                                </tr>
                                @endif
                                @if(($sales->other_charges ?? 0) > 0)
                                <tr>
                                    <td style="padding: 2px 0; color: #475569;">Other Charges:</td>
                                    <td style="padding: 2px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ number_format($sales->other_charges, 2) }}</td>
                                </tr>
                                @endif
                                @php
                                    $prevDue = (float)($sales->previous_due ?? 0);
                                    $grandPayable = (float)($sales->payble ?? 0) + $prevDue;
                                    $finalDue = (float)($sales->due_payment ?? 0) + $prevDue;
                                @endphp
                                @if($prevDue > 0)
                                <tr>
                                    <td style="padding: 2px 0; color: #003df4ff; font-weight: 600;">Previous Due:</td>
                                    <td style="padding: 2px 0; text-align: right; font-weight: 700; color: #003df4ff;">{{ number_format($prevDue, 2) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding: 4px 0; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; font-size: 12px; font-weight: 800; color: #f97316;">Total Amount:</td>
                                    <td style="padding: 4px 0; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; text-align: right; font-size: 12px; font-weight: 800; color: #f97316;">{{ number_format($grandPayable, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 2px 0; color: #16a34a; font-weight: 600;">Paid Amount:</td>
                                    <td style="padding: 2px 0; text-align: right; font-weight: 700; color: #16a34a;">{{ number_format($sales->advanced_payment ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 2px 0; font-size:12px; font-weight: 800; color: #dc2626;">Final Amount:</td>
                                    <td style="padding: 2px 0; text-align: right; font-size: 12px; font-weight: 800; color: {{ $finalDue > 0 ? '#dc2626' : '#16a34a' }};">{{ number_format($finalDue, 2) }}</td>
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
                {{ numberToWords((float)($grandPayable ?: ($sales->bill ?? 0))) }} Taka Only
            </td>
        </tr>
    </table>

</body>
</html>