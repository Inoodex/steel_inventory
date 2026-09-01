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
            @php
                $totalQty = $itemCollection->sum('qty') ?: ($sales->qty ?? 0);
                $subTotal = (float)($sales->total ?? $sales->bill ?? $itemCollection->sum('total_price'));
                $discount = (float)($sales->discount ?? 0);
                $vatRate = (float)($sales->vat ?? 0);
                $vatAmount = $vatRate > 0 ? ($subTotal * $vatRate) / 100 : 0;
                $taxRate = (float)($sales->tax ?? 0);
                $taxAmount = $taxRate > 0 ? ($subTotal * $taxRate) / 100 : 0;
                $weightScale = (float)($sales->weight_scale_cost ?? 0);
                $labourCost = (float)($sales->labour_cost ?? 0);
                $deliveryCharge = (float)($sales->delivery_charge ?? 0);
                $otherCharges = (float)($sales->other_charges ?? 0);
                $prevDue = (float)($sales->previous_due ?? 0);
                $grandPayable = (float)($sales->payble ?? ($subTotal - $discount + $vatAmount + $taxAmount + $weightScale + $labourCost + $deliveryCharge + $otherCharges)) + $prevDue;
                $paidAmount = (float)($sales->advanced_payment ?? 0);
                $finalDue = (float)($sales->due_payment ?? ($grandPayable - $prevDue - $paidAmount)) + $prevDue;
            @endphp
            <!-- Total (Items Sum) -->
            <tr>
                <td colspan="2" style="border: 1px solid #334155; padding: 3px 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; text-align: right; background-color: #f1f5f9; color: #0f172a; height: 20px;">
                    <strong>TOTAL</strong>
                </td>
                <td style="border: 1px solid #334155; padding: 3px 6px; font-size: 11px; font-weight: 800; color: #0f172a; text-align: center; background-color: #f1f5f9; height: 20px;">
                    <strong>{{ number_format($totalQty) }}</strong>
                </td>
                <td style="border: 1px solid #334155; padding: 3px 6px; font-size: 11px; color: #0f172a; text-align: right; background-color: #f1f5f9; height: 20px;">&nbsp;</td>
                <td style="border: 1px solid #334155; padding: 3px 8px; font-size: 11px; font-weight: 800; color: #0f172a; text-align: right; background-color: #f1f5f9; height: 20px;">
                    <strong>{{ number_format($subTotal, 2) }}</strong>
                </td>
            </tr>

            @if($discount > 0)
            <tr>
                <td colspan="2" style="border: none; background: transparent; height: 20px;"></td>
                <td colspan="2" style="border: 1px solid #334155; padding: 3px 4px; font-size: 11px; font-weight: 700; text-align: right; color: #dc2626; height: 20px;">
                    DISCOUNT
                </td>
                <td style="border: 1px solid #334155; padding: 3px 8px; font-size: 11px; font-weight: 700; color: #dc2626; text-align: right; height: 20px;">
                    - {{ number_format($discount, 2) }}
                </td>
            </tr>
            @endif

            @if($vatAmount > 0)
            <tr>
                <td colspan="2" style="border: none; background: transparent; height: 20px;"></td>
                <td colspan="2" style="border: 1px solid #334155; padding: 3px 4px; font-size: 11px; font-weight: 600; text-align: right; color: #334155; height: 20px;">
                    VAT ({{ number_format($vatRate, 2) }}%)
                </td>
                <td style="border: 1px solid #334155; padding: 3px 8px; font-size: 11px; font-weight: 600; color: #0f172a; text-align: right; height: 20px;">
                    {{ number_format($vatAmount, 2) }}
                </td>
            </tr>
            @endif

            @if($taxAmount > 0)
            <tr>
                <td colspan="2" style="border: none; background: transparent; height: 20px;"></td>
                <td colspan="2" style="border: 1px solid #334155; padding: 3px 4px; font-size: 11px; font-weight: 600; text-align: right; color: #334155; height: 20px;">
                    TAX ({{ number_format($taxRate, 2) }}%)
                </td>
                <td style="border: 1px solid #334155; padding: 3px 8px; font-size: 11px; font-weight: 600; color: #0f172a; text-align: right; height: 20px;">
                    {{ number_format($taxAmount, 2) }}
                </td>
            </tr>
            @endif

            <tr>
                <td colspan="2" style="border: none; background: transparent; height: 20px;"></td>
                <td colspan="2" style="border: 1px solid #334155; padding: 3px 4px; font-size: 11px; font-weight: 600; text-align: right; color: #334155; height: 20px;">
                    SCALE & LABOUR CHARGE
                </td>
                <td style="border: 1px solid #334155; padding: 3px 8px; font-size: 11px; font-weight: 600; color: #0f172a; text-align: right; height: 20px;">
                    {{ number_format($weightScale, 2) }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border: none; background: transparent; height: 20px;"></td>
                <td colspan="2" style="border: 1px solid #334155; padding: 3px 4px; font-size: 11px; font-weight: 600; text-align: right; color: #334155; height: 20px;">
                    CUTTING & LABOUR LOAD-UNLOAD
                </td>
                <td style="border: 1px solid #334155; padding: 3px 8px; font-size: 11px; font-weight: 600; color: #0f172a; text-align: right; height: 20px;">
                    {{ number_format($labourCost, 2) }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border: none; background: transparent; height: 20px;"></td>
                <td colspan="2" style="border: 1px solid #334155; padding: 3px 4px; font-size: 11px; font-weight: 600; text-align: right; color: #334155; height: 20px;">
                    TRANSPORT BILL
                </td>
                <td style="border: 1px solid #334155; padding: 3px 8px; font-size: 11px; font-weight: 600; color: #0f172a; text-align: right; height: 20px;">
                    {{ number_format($deliveryCharge, 2) }}
                </td>
            </tr>
            @if($otherCharges > 0)
            <tr>
                <td colspan="2" style="border: none; background: transparent; height: 20px;"></td>
                <td colspan="2" style="border: 1px solid #334155; padding: 3px 4px; font-size: 11px; font-weight: 600; text-align: right; color: #334155; height: 20px;">
                    OTHER CHARGES
                </td>
                <td style="border: 1px solid #334155; padding: 3px 8px; font-size: 11px; font-weight: 600; color: #0f172a; text-align: right; height: 20px;">
                    {{ number_format($otherCharges, 2) }}
                </td>
            </tr>
            @endif
            @if($prevDue > 0)
            <tr>
                <td colspan="2" style="border: none; background: transparent; height: 20px;"></td>
                <td colspan="2" style="border: 1px solid #334155; padding: 3px 4px; font-size: 11px; font-weight: 700; text-align: right; color: #003df4; background-color: #f8fafc; height: 20px;">
                    PREVIOUS DUE
                </td>
                <td style="border: 1px solid #334155; padding: 3px 8px; font-size: 11px; font-weight: 700; color: #003df4; text-align: right; background-color: #f8fafc; height: 20px;">
                    {{ number_format($prevDue, 2) }}
                </td>
            </tr>
            @endif

            <!-- Total Amount (Grand Payable) -->
            <tr>
                <td colspan="2" style="border: none; background: transparent; height: 21px;"></td>
                <td colspan="2" style="border: 1px solid #334155; padding: 4px 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; text-align: right; background-color: #f1f5f9; color: #f97316; height: 21px;">
                    <strong>TOTAL AMOUNT</strong>
                </td>
                <td style="border: 1px solid #334155; padding: 4px 8px; font-size: 11px; font-weight: 800; color: #f97316; text-align: right; background-color: #f1f5f9; height: 21px;">
                    <strong>{{ number_format($grandPayable, 2) }}</strong>
                </td>
            </tr>

            <!-- Paid Amount -->
            <tr>
                <td colspan="2" style="border: none; background: transparent; height: 20px;"></td>
                <td colspan="2" style="border: 1px solid #334155; padding: 3px 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; text-align: right; color: #16a34a; background-color: #f8fafc; height: 20px;">
                    PAID AMOUNT
                </td>
                <td style="border: 1px solid #334155; padding: 3px 8px; font-size: 11px; font-weight: 700; color: #16a34a; text-align: right; background-color: #f8fafc; height: 20px;">
                    {{ number_format($paidAmount, 2) }}
                </td>
            </tr>

            <!-- Final Amount -->
            <tr>
                <td colspan="2" style="border: none; background: transparent; height: 21px;"></td>
                <td colspan="2" style="border: 1px solid #334155; padding: 4px 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; text-align: right; background-color: #f1f5f9; color: {{ $finalDue > 0 ? '#dc2626' : '#16a34a' }}; height: 21px;">
                    <strong>FINAL AMOUNT</strong>
                </td>
                <td style="border: 1px solid #334155; padding: 4px 8px; font-size: 11px; font-weight: 800; color: {{ $finalDue > 0 ? '#dc2626' : '#16a34a' }}; text-align: right; background-color: #f1f5f9; height: 21px;">
                    <strong>{{ number_format($finalDue, 2) }}</strong>
                </td>
            </tr>
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

    <!-- Amount In Words Card -->
    <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px;">
        <tr>
            <td style="padding: 8px 12px; font-size: 11px; color: #334155;">
                <strong style="color: #f97316; margin-right: 6px;">Amount In Words:</strong>
                {{ numberToWords((float)($grandPayable ?: ($sales->bill ?? 0))) }} Taka Only
            </td>
        </tr>
    </table>

    <!-- Signature Section -->
    <!-- <table style="width: 100%; border-collapse: collapse; margin-top: 350px;">
        <tr>
            <td style="width: 50%; vertical-align: bottom; text-align: left;">
                <span style="border-top: 1px dotted #475569; padding-top: 5px; font-size: 11px; font-weight: 600; color: #334155; display: inline-block; line-height: 1;">Customer Signature</span>
            </td>
            <td style="width: 50%; vertical-align: bottom; text-align: right;">
                <span style="border-top: 1px dotted #475569; padding-top: 5px; font-size: 11px; font-weight: 600; color: #334155; display: inline-block; line-height: 1;">Authorized Signature</span>
            </td>
        </tr>
    </table> -->

<table style="width:100%; border-collapse:collapse; margin-top:350px;">
    <tr>
        <td style="width:50%; text-align:left;">
            <table style="border-collapse:collapse; width:100px;">
                <tr>
                    <td style="
                        border-top:1px dotted #475569;
                        height:1px;
                        padding:0;
                        font-size:0;
                        line-height:0;
                    ">&nbsp;</td>
                </tr>

                <tr>
                    <td style="
                        padding-top:8px;
                        padding-left:0;
                        padding-right:0;
                        font-size:11px;
                        font-weight:600;
                        color:#334155;
                        text-align:center;
                    ">
                        Customer Signature
                    </td>
                </tr>
            </table>
        </td>
        <td style="width:50%; text-align:right;">
    <table style="border-collapse:collapse; width:100px; margin-left:auto;">
        <tr>
            <td style="
                border-top:1px dotted #475569;
                height:1px;
                padding:0;
                font-size:0;
                line-height:0;
            ">&nbsp;</td>
        </tr>

        <tr>
            <td style="
                padding-top:8px;
                padding-left:0;
                padding-right:0;
                font-size:11px;
                font-weight:600;
                color:#334155;
                text-align:center;
            ">
                Authorized Signature
            </td>
        </tr>
    </table>
</td>

    </tr>
</table>

</body>
</html>