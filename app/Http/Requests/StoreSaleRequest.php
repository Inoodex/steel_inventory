<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_type'          => 'required|in:new,existing',
            'existing_client_id'   => 'nullable|required_if:client_type,existing|exists:customers,id',
            'name'                 => 'nullable|required_if:client_type,new|string',
            'phone'                => 'nullable|required_if:client_type,new|string',
            'address'              => 'nullable|required_if:client_type,new|string',
            'coil_id'              => 'nullable|array',
            'coil_id.*'            => 'nullable|exists:coils,id',
            'thickness'            => 'nullable|array',
            'size'                 => 'nullable|array',
            'size_type'            => 'nullable|array',
            'custom_size'          => 'nullable|array',
            'custom_size.*'        => 'nullable|string|max:255',
            'product'              => 'nullable|array',
            'product.*'            => 'nullable',
            'qty'                  => 'required|array',
            'qty.*'                => 'required|numeric|min:0.01',
            'unit_price'           => 'required|array',
            'unit_price.*'         => 'required|numeric|min:0',
            'subTotal'             => 'required|numeric|min:0',
            'discount'             => 'nullable|numeric|min:0',
            'grandTotal'           => 'required|numeric|min:0',
            'advanced_payment'     => 'nullable|numeric|min:0',
            'duePayment'           => 'nullable|numeric|min:0',
            'payment_method'       => 'nullable|string|in:cash,bank,cheque,mobile_banking,advance_credit',
            'bank_detail_id'       => 'nullable|exists:bank_details,id',
            'transaction_ref'      => 'nullable|string|max:255',
            'vat'                  => 'nullable|numeric|min:0',
            'tax'                  => 'nullable|numeric|min:0',
            'delivery_charge'      => 'nullable|numeric|min:0',
            'transport_payer'      => 'nullable|string|in:me,vendor',
            'warehouse_id'         => 'nullable|exists:warehouses,id',
            'delivery_status'      => 'nullable|string|in:pending,dispatched,delivered,partial_delivered',
            'lot_id'               => 'nullable|array',
            'lot_id.*'             => 'nullable|exists:lots,id',
            'labour_cost'          => 'nullable|numeric|min:0',
            'weight_scale_cost'    => 'nullable|numeric|min:0',
            'other_charges'        => 'nullable|numeric|min:0',
            'note'                 => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'client_type.required'           => 'Please select a client type.',
            'existing_client_id.required_if' => 'Please select an existing customer.',
            'name.required_if'               => 'Customer name is required for new clients.',
            'phone.required_if'              => 'Phone number is required for new clients.',
            'qty.required'                   => 'At least one steel line item with quantity is required.',
            'subTotal.required'              => 'Sub total is required.',
            'grandTotal.required'            => 'Grand total is required.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $quantities = $this->input('qty', []);
            $coilIds = $this->input('coil_id', []);
            $lotIds = $this->input('lot_id', []);
            $warehouseId = $this->input('warehouse_id');

            // Aggregate requested weight per coil and per lot
            $aggregatedPerCoil = [];
            $aggregatedPerLot = [];

            foreach ($quantities as $index => $qty) {
                $coilId = $coilIds[$index] ?? null;
                $lotId = $lotIds[$index] ?? null;
                if ($coilId) {
                    $aggregatedPerCoil[$coilId] = ($aggregatedPerCoil[$coilId] ?? 0) + (float)$qty;
                } else {
                    $lotKey = $lotId ? (string)$lotId : 'opening_stock';
                    $aggregatedPerLot[$lotKey] = ($aggregatedPerLot[$lotKey] ?? 0) + (float)$qty;
                }
            }

            foreach ($aggregatedPerCoil as $coilId => $totalRequestedQty) {
                $coil = \App\Models\Coil::find($coilId);
                if ($coil) {
                    $available = (float) $coil->remaining_weight;
                    if ($totalRequestedQty > ($available + 0.0001)) {
                        $validator->errors()->add(
                            'qty',
                            "Selling quantity (" . number_format($totalRequestedQty, 2) . " kg) exceeds available stock for Coil #{$coil->coil_number} (Available: " . number_format($available, 2) . " kg)."
                        );
                    }
                }
            }

            foreach ($aggregatedPerLot as $lotKey => $totalRequestedQty) {
                $query = \App\Models\Coil::where('status', 'in_stock')->where('remaining_weight', '>', 0);
                if ($warehouseId) {
                    $query->where('warehouse_id', $warehouseId);
                }
                if ($lotKey === 'opening_stock') {
                    $query->where(function ($q) {
                        $q->whereNull('lot_id')->orWhereNull('purchase_id');
                    });
                    $lotName = 'Opening Stock';
                } else {
                    $query->where('lot_id', $lotKey);
                    $lotObj = \App\Models\Lot::find($lotKey);
                    $lotName = $lotObj ? "Lot #{$lotObj->lot_number}" : "Lot #{$lotKey}";
                }
                $availableWeight = (float) $query->sum('remaining_weight');
                if ($totalRequestedQty > ($availableWeight + 0.0001)) {
                    $validator->errors()->add(
                        'qty',
                        "Selling quantity (" . number_format($totalRequestedQty, 2) . " kg) exceeds available stock for {$lotName} in the selected warehouse (Available: " . number_format($availableWeight, 2) . " kg)."
                    );
                }
            }
        });
    }
}
