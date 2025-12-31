<?php

namespace App\Domains\Inventory\Actions;

use App\Domains\Inventory\Models\InventoryAdjustment;
use App\Domains\Product\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustInventoryAction
{
    public function execute(int $productId, int $qtyChange, string $reason, User $user): InventoryAdjustment
    {
        return DB::transaction(function () use ($productId, $qtyChange, $reason, $user) {
            if ($qtyChange === 0) {
                throw ValidationException::withMessages(['qty_change' => 'Quantity change cannot be zero.']);
            }

            $product = Product::lockForUpdate()->findOrFail($productId);

            if ($product->stock + $qtyChange < 0) {
                throw ValidationException::withMessages([
                    'qty_change' => "Adjustment results in negative stock. Current: {$product->stock}, Change: {$qtyChange}",
                ]);
            }

            if ($qtyChange > 0) {
                $product->increaseStock($qtyChange);
            } else {
                $product->decreaseStock(abs($qtyChange));
            }

            $adjustment = InventoryAdjustment::create([
                'product_id' => $product->id,
                'qty_change' => $qtyChange,
                'reason' => $reason,
                'adjusted_by' => $user->id,
            ]);

            \App\Support\CacheHelper::invalidateProductsCache();

            return $adjustment;  
        });
    }
}
