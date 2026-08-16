<?php

namespace App\Http\Requests;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;

class StoreReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Filter items: only keep rows with quantity > 0
        if ($this->has('items') && is_array($this->items)) {
            $filteredItems = array_values(array_filter($this->items, function ($item) {
                return isset($item['quantity']) && (float)$item['quantity'] > 0;
            }));
            $this->merge(['items' => $filteredItems]);
        }

        // Auto-fill customer_id from sale if not provided
        if (!$this->filled('customer_id') && $this->filled('sale_id')) {
            $sale = Sale::find($this->sale_id);
            if ($sale) {
                $this->merge(['customer_id' => $sale->customer_id]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'sale_id'               => 'required|exists:sales,id',
            'customer_id'           => 'nullable|exists:customers,id',
            'return_date'           => 'required|date',
            'reason'                => 'nullable|string|max:1000',
            'notes'                 => 'nullable|string|max:1000',
            'items'                 => 'required|array|min:1',
            'items.*.sales_item_id' => 'nullable|exists:sales_items,id',
            'items.*.product_id'    => 'nullable',
            'items.*.quantity'      => 'required|numeric|min:0.001',
            'items.*.unit_price'    => 'required|numeric|min:0',
            'items.*.return_reason' => 'nullable|string',
            'items.*.condition'     => 'nullable|string',
            'items.*.notes'         => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'sale_id.required'      => 'Please select the original sale order.',
            'sale_id.exists'        => 'Selected sale order does not exist.',
            'return_date.required'  => 'Return date is required.',
            'items.required'        => 'Please specify at least one item to return with quantity greater than 0.',
            'items.min'             => 'Please specify at least one item to return with quantity greater than 0.',
            'items.*.quantity.min'  => 'Return quantity must be greater than 0.',
            'items.*.unit_price.min'=> 'Unit price cannot be negative.',
        ];
    }
}
