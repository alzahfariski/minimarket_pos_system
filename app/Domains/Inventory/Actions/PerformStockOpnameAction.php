<?php

namespace App\Domains\Inventory\Actions;

use App\Domains\Inventory\Models\StockOpname;
use App\Domains\Product\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PerformStockOpnameAction
{
    public function execute(int $productId, int $physicalStock, ?string $note, User $user): StockOpname
    {
        return DB::transaction(function () use ($productId, $physicalStock, $note, $user) {
            if ($physicalStock < 0) {
                throw ValidationException::withMessages(['physical_stock' => 'Physical stock cannot be negative.']);
            }

            $product = Product::lockForUpdate()->findOrFail($productId);
            $systemStock = $product->stock;
            $difference = $physicalStock - $systemStock;

            // Update Stock to match Physical
            if ($difference > 0) {
                $product->increaseStock($difference);
            } elseif ($difference < 0) {
                $product->decreaseStock(abs($difference));
            }
            // If difference is 0, no stock change, but we still record opname.

            $opname = StockOpname::create([
                'product_id' => $product->id,
                'system_stock' => $systemStock,
                'physical_stock' => $physicalStock,
                'difference' => $difference,
                'note' => $note,
                'created_by' => $user->id,
            ]);

            \App\Support\CacheHelper::invalidateProductsCache();

            return $opname;
        });
    }
}
