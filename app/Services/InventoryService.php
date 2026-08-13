<?php

namespace App\Services;

use App\Models\Inventory;

class InventoryService
{
    /**
     * Increment stock when an intake is made.
     */
    public function incrementStock(?int $productId, float $quantity, string $notes = 'Stock added'): ?Inventory
    {
        if (!$productId) {
            return null;
        }

        $inventory = Inventory::where('product_id', $productId)->first();

        if ($inventory) {
            $inventory->current_stock += $quantity;
            $inventory->save();
        } else {
            $inventory = Inventory::create([
                'product_id'    => $productId,
                'current_stock' => $quantity,
                'opening_stock' => $quantity,
                'notes'         => $notes,
            ]);
        }

        return $inventory;
    }

    /**
     * Decrement stock when a sale is made.
     */
    public function decrementStock(?int $productId, float $quantity): ?Inventory
    {
        if (!$productId) {
            return null;
        }

        $inventory = Inventory::where('product_id', $productId)->first();

        if ($inventory) {
            $inventory->current_stock = max(0, $inventory->current_stock - $quantity);
            $inventory->save();
            return $inventory;
        }

        return null;
    }

    /**
     * Get current stock level for an item.
     */
    public function getStock(int $productId): float
    {
        $inventory = Inventory::where('product_id', $productId)->first();
        return $inventory ? (float) $inventory->current_stock : 0;
    }
}
