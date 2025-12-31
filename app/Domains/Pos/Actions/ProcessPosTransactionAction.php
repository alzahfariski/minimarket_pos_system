<?php

namespace App\Domains\Pos\Actions;

use App\Domains\Pos\Models\PosTransaction;
use App\Domains\Pos\Models\PosTransactionItem;
use App\Domains\Product\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProcessPosTransactionAction
{
    public function execute(array $items, int $paymentAmount, string $paymentMethod, User $cashier): PosTransaction
    {
        return \Illuminate\Support\Facades\Cache::lock('pos_transaction_processing', 10)->block(5, function () use ($items, $paymentAmount, $paymentMethod, $cashier) {
            return DB::transaction(function () use ($items, $paymentAmount, $paymentMethod, $cashier) {
                if (empty($items)) {
                    throw ValidationException::withMessages(['items' => 'Transaction must have at least one item.']);
                }

                $transaction = PosTransaction::create([
                    'total_amount' => 0, // Interim
                    'payment_amount' => $paymentAmount,
                    'change_amount' => 0, // Interim
                    'payment_method' => $paymentMethod,
                    'invoice_number' => 'INV-' . date('YmdHis') . '-' . Str::upper(Str::random(3)),
                    'cashier_id' => $cashier->id,
                ]);

                $totalAmount = 0;

                foreach ($items as $itemData) {
                    // LOCK product for atomic update (DB Level)
                    $product = Product::lockForUpdate()->find($itemData['product_id']);

                    if (! $product) {
                        throw ValidationException::withMessages(['items' => "Product ID {$itemData['product_id']} not found."]);
                    }

                    $quantity = $itemData['qty'];

                    // Validate and Decrease Stock (Domain Logic)
                    try {
                        $product->decreaseStock($quantity);
                    } catch (\DomainException $e) {
                         throw ValidationException::withMessages(['items' => $e->getMessage()]);
                    }

                    $subtotal = $product->price * $quantity;
                    $totalAmount += $subtotal;

                    // Create Item Record
                    PosTransactionItem::create([
                        'pos_transaction_id' => $transaction->id,
                        'product_id' => $product->id,
                        'qty' => $quantity,
                        'price_snapshot' => $product->price,
                        'subtotal' => $subtotal,
                    ]);
                }

                // Validate Payment
                if ($paymentAmount < $totalAmount) {
                    throw ValidationException::withMessages([
                        'payment_amount' => "Insufficient payment. Total: {$totalAmount}, Paid: {$paymentAmount}",
                    ]);
                }

                // Finalize Transaction
                $transaction->update([
                    'total_amount' => $totalAmount,
                    'change_amount' => $paymentAmount - $totalAmount,
                ]);

                \App\Support\CacheHelper::invalidateProductsCache();

                return $transaction;
            });
        });
    }
}
