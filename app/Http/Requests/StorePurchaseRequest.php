<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $defaultVendor = $this->input('default_vendor_id') ?: $this->input('vendor_id') ?: $this->input('new_vendor_id');
        $items = $this->input('items', []);
        if (is_array($items)) {
            foreach ($items as $k => $item) {
                if (empty($item['vendor_id']) && !empty($defaultVendor)) {
                    $items[$k]['vendor_id'] = $defaultVendor;
                }
            }
            $this->merge(['items' => $items]);
        }
        if ($defaultVendor && !$this->input('vendor_id')) {
            $this->merge(['vendor_id' => $defaultVendor]);
        }

        // Auto-sum total payment from vendor_payments if multi-vendor payments are provided
        $vendorPayments = $this->input('vendor_payments');
        if (is_array($vendorPayments) && count($vendorPayments) > 0) {
            $totalVendorPay = 0;
            foreach ($vendorPayments as $vp) {
                $totalVendorPay += (float) ($vp['amount'] ?? 0);
            }
            if ($totalVendorPay > 0 || !$this->has('payment')) {
                $this->merge(['payment' => round($totalVendorPay, 2)]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'lot_type'            => 'nullable|string|in:existing,new',
            'lot_id'              => 'nullable|required_if:lot_type,existing|exists:lots,id',
            'new_lot_number'      => 'nullable|required_if:lot_type,new|string|max:100|unique:lots,lot_number',
            'default_vendor_id'   => 'nullable|exists:vendors,id',
            'vendor_id'           => 'nullable|exists:vendors,id',
            'new_vendor_id'       => 'nullable|exists:vendors,id',
            'lot_notes'           => 'nullable|string|max:500',
            'warehouse_id'        => 'nullable|exists:warehouses,id',
            'purchase_date'       => 'nullable|date',
            'payment'             => 'required|numeric|min:0',
            'due'                 => 'nullable|numeric',
            'grand_total'         => 'nullable|numeric|min:0',
            'delivery_charge'     => 'nullable|numeric|min:0',
            'labour_cost'         => 'nullable|numeric|min:0',
            'weight_scale_cost'   => 'nullable|numeric|min:0',
            'other_charges'       => 'nullable|numeric|min:0',
            'discount'            => 'nullable|numeric|min:0',
            'payment_method'      => 'nullable|string|in:cash,bank,cheque,mobile_banking',
            'bank_detail_id'      => 'nullable|exists:bank_details,id',
            'transaction_ref'     => 'nullable|string|max:255',

            // Multi-vendor individual payments
            'vendor_payments'                    => 'nullable|array',
            'vendor_payments.*.vendor_id'        => 'nullable|exists:vendors,id',
            'vendor_payments.*.amount'           => 'nullable|numeric|min:0',
            'vendor_payments.*.payment_method'   => 'nullable|string|in:cash,bank,cheque,mobile_banking',
            'vendor_payments.*.bank_detail_id'   => 'nullable|exists:bank_details,id',
            'vendor_payments.*.transaction_ref'  => 'nullable|string|max:255',
            
            // Multi-row items / coils validation
            'items'                => 'required|array|min:1',
            'items.*.vendor_id'    => 'required|exists:vendors,id',
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
            'lot_id.required_if'         => 'Please select an existing Purchase Lot.',
            'lot_id.exists'              => 'Selected Lot does not exist.',
            'new_lot_number.required_if' => 'Lot Number is required when creating a new Lot.',
            'new_lot_number.unique'      => 'This Lot Number already exists in the system. Please use a unique number.',
            'items.*.vendor_id.required' => 'Please select a vendor for each steel item.',
            'items.*.vendor_id.exists'   => 'Selected vendor for an item does not exist.',
            'items.required'             => 'At least one ship steel or coil item is required.',
            'items.min'                  => 'Please add at least one steel item row.',
            'payment.required'           => 'Payment amount is required.',
            'payment.min'                => 'Payment amount cannot be negative.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            $calculatedGrandTotal = 0;
            $vendorBills = [];

            if (is_array($items)) {
                foreach ($items as $item) {
                    $vId = $item['vendor_id'] ?? $this->input('vendor_id');
                    $coilQty = max(1, floatval($item['quantity'] ?? 1));
                    $perCoilWeight = floatval($item['unit_weight'] ?? $item['net_weight'] ?? 0);
                    $totalWeight = floatval($item['total_weight'] ?? ($coilQty * $perCoilWeight));
                    if ($totalWeight <= 0 && $perCoilWeight > 0) {
                        $totalWeight = $coilQty * $perCoilWeight;
                    }
                    $rate = floatval($item['unit_price'] ?? 0);
                    $itemSub = !empty($item['sub_price']) ? floatval($item['sub_price']) : ($totalWeight * $rate);
                    $calculatedGrandTotal += $itemSub;

                    if ($vId) {
                        $vendorBills[$vId] = ($vendorBills[$vId] ?? 0) + $itemSub;
                    }
                }
            }

            $deliveryCharge  = (float) $this->input('delivery_charge', 0);
            $labourCost      = (float) $this->input('labour_cost', 0);
            $weightScaleCost = (float) $this->input('weight_scale_cost', 0);
            $otherCharges    = (float) $this->input('other_charges', 0);
            $discount        = (float) $this->input('discount', 0);
            $netExtraCharges = ($deliveryCharge + $labourCost + $weightScaleCost + $otherCharges) - $discount;

            $totalBill = max(0, round($calculatedGrandTotal + $netExtraCharges, 2));

            // Validate individual vendor payments if provided
            $vendorPayments = $this->input('vendor_payments', []);
            if (is_array($vendorPayments) && count($vendorPayments) > 0) {
                $totalVendorPay = 0;
                foreach ($vendorPayments as $vId => $vp) {
                    $vpAmount = floatval($vp['amount'] ?? 0);
                    $totalVendorPay += $vpAmount;
                    $vSub = $vendorBills[$vId] ?? 0;
                    $vRatio = $calculatedGrandTotal > 0 ? ($vSub / $calculatedGrandTotal) : 0;
                    $vEstimatedBill = max(0, round($vSub + ($netExtraCharges * $vRatio), 2));

                    if ($vpAmount > $vEstimatedBill + 0.05) {
                        $validator->errors()->add(
                            "vendor_payments.{$vId}.amount",
                            "Payment amount (৳ " . number_format($vpAmount, 2) . ") for vendor exceeds their total bill of ৳ " . number_format($vEstimatedBill, 2) . "."
                        );
                    }
                }
            }

            $payment = floatval($this->input('payment', 0));
            $submittedGrandTotal = floatval($this->input('grand_total', $totalBill));
            $finalMaxBill = max($submittedGrandTotal, $totalBill);

            if ($payment > $finalMaxBill + 0.01) {
                $validator->errors()->add(
                    'payment',
                    'Payment amount (৳ ' . number_format($payment, 2) . ') cannot exceed the total purchase bill (৳ ' . number_format($finalMaxBill, 2) . ').'
                );
            }
        });
    }
}
