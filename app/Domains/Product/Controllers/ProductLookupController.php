<?php

namespace App\Domains\Product\Controllers;

use App\Domains\Product\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ProductLookupController extends Controller
{
    public function scan(string $sku): JsonResponse
    {
        // Cache product details by SKU for 1 hour to ensure fast scanning at POS
        $product = Cache::remember("product:scan:{$sku}", 3600, function () use ($sku) {
            return Product::where('sku', $sku)->first();
        });

        if (! $product) {
             return response()->json(['message' => 'Product not found'], 404);
        }

        // Return simplest resource needed for POS line item
        return response()->json([
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'price' => $product->price,
            'image_url' => $product->image_url,
            'stock' => $product->stock,
        ]);
    }
}
