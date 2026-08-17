<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lot_id'              => 'required|exists:lots,id',
            'vendor_id'           => 'required|exists:vendors,id',
            'warehouse_id'        => 'nullable|exists:warehouses,id',
            'purchase_date'       => 'nullable|date',
            'payment'             => 'required|numeric|min:0',
            'due'                 => 'nullable|numeric',
            'grand_total'         => 'nullable|numeric|min:0',
            
            // Multi-row items / coils validation
            'items'                => 'required|array|min:1',
            'items.*.coil_number'  => 'nullable|string|max:100',
            'items.*.thickness'    => 'nullable|string|max:100',
            'items.*.width'        => 'nullable|string|max:100',
            'items.*.length'       => 'nullable|string|max:100',
            'items.*.size'         => 'nullable|string|max:100',
            'items.*.size_type'    => 'nullable|string|max:50',
            'items.*.gross_weight' => 'nullable|numeric|min:0',
            'items.*.tare_weight'  => 'nullable|numeric|min:0',
            'items.*.net_weight'   => 'nullable|numeric|min:0',
            'items.*.unit_weight'  => 'nullable|numeric|min:0',
            'items.*.total_weight' => 'nullable|numeric|min:0',
            'items.*.quantity'     => 'nullable|numeric|min:0',
            'items.*.rate_per_ton' => 'nullable|numeric|min:0',
            'items.*.unit_price'   => 'nullable|numeric|min:0',
            'items.*.sub_price'    => 'nullable|numeric|min:0',
            'items.*.notes'        => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'lot_id.required'    => 'Please select a Vessel / Purchase Lot.',
            'lot_id.exists'      => 'Selected Lot does not exist.',
            'vendor_id.required' => 'Please select a vendor / ship breaker.',
            'vendor_id.exists'   => 'Selected vendor does not exist.',
            'items.required'     => 'At least one ship steel or coil item is required.',
            'items.min'          => 'Please add at least one steel item row.',
            'payment.required'   => 'Payment amount is required.',
            'payment.min'        => 'Payment amount cannot be negative.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            $calculatedGrandTotal = 0;
            if (is_array($items)) {
                foreach ($items as $item) {
                    $coilQty = max(1, floatval($item['quantity'] ?? 1));
                    $perCoilWeight = floatval($item['unit_weight'] ?? $item['net_weight'] ?? 0);
                    $totalWeight = floatval($item['total_weight'] ?? ($coilQty * $perCoilWeight));
                    if ($totalWeight <= 0 && $perCoilWeight > 0) {
                        $totalWeight = $coilQty * $perCoilWeight;
                    }
                    $rate = floatval($item['unit_price'] ?? 0);
                    $calculatedGrandTotal += ($totalWeight * $rate);
                }
            }

            $payment = floatval($this->input('payment', 0));
            $submittedGrandTotal = floatval($this->input('grand_total', $calculatedGrandTotal));
            $totalBill = max($submittedGrandTotal, $calculatedGrandTotal);

            if ($payment > $totalBill + 0.01) {
                $validator->errors()->add(
                    'payment',
                    'Payment amount (৳ ' . number_format($payment, 2) . ') cannot exceed the total purchase bill (৳ ' . number_format($totalBill, 2) . ').'
                );
            }
        });
    }
}
