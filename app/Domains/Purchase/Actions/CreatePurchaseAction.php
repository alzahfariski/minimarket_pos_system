<?php

namespace App\Domains\Purchase\Actions;

use App\Domains\Product\Models\Product;
use App\Domains\Purchase\Models\Purchase;
use App\Domains\Purchase\Models\PurchaseItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePurchaseAction
{
    public function execute(int $supplierId, array $items, User $user): Purchase
    {
        return DB::transaction(function () use ($supplierId, $items, $user) {
            if (empty($items)) {
                throw ValidationException::withMessages(['items' => 'Purchase must have at least one item.']);
            }

            $purchase = Purchase::create([
                'supplier_id' => $supplierId,
                'created_by' => $user->id,
                'total_cost' => 0, // Will update later
            ]);

            $totalCost = 0;

            foreach ($items as $itemData) {
                // Lock product for update to prevent race conditions
                $product = Product::lockForUpdate()->find($itemData['product_id']);

                if (! $product) {
                    throw ValidationException::withMessages(['items' => "Product ID {$itemData['product_id']} not found."]);
                }

                $quantity = $itemData['quantity'];

                if ($quantity <= 0) {
                     throw ValidationException::withMessages(['items' => "Quantity for product {$product->name} must be greater than zero."]);
                }

                // Create Purchase Item
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'cost' => $product->cost, // Snapshot current cost
                ]);

                // Increase Stock via Domain Method
                $product->increaseStock($quantity);

                $totalCost += ($product->cost * $quantity);
            }

            // Update Total Cost
            $purchase->update(['total_cost' => $totalCost]);

            \App\Support\CacheHelper::invalidateProductsCache();

            return $purchase;
        });
    }
}
