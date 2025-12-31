<?php

namespace App\Domains\Product\Controllers;

use App\Domains\Product\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        // Admin & Cashier can view
        $this->authorize('viewAny', Product::class);

        $products = \Illuminate\Support\Facades\Cache::remember('products:list', 300, function () {
            // Include image_url accessor in serialization
            return Product::query()
                ->orderByRaw('stock = 0')
                ->orderBy('created_at', 'desc') // Secondary sort for stability/freshness
                ->get()
                ->append('image_url');
        });

        return response()->json($products);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $validated = $request->validate([
            'sku' => 'required|string|unique:products,sku',
            'name' => 'required|string',
            'cost' => 'required|integer|min:0',
            'price' => 'required|integer|min:0',
        ]);

        $product = Product::create($validated);

        \App\Support\CacheHelper::invalidateProductsCache();

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'sku' => 'nullable|string|unique:products,sku,' . $product->id,
            'name' => 'nullable|string',
            'cost' => 'nullable|integer|min:0',
            'price' => 'nullable|integer|min:0',
        ]);

        $product->update($validated);
        
        \App\Support\CacheHelper::invalidateProductsCache();

        return response()->json($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        if ($product->stock > 0) {
            return response()->json(['message' => 'Cannot delete product with remaining stock.'], 400);
        }

        $product->delete();
        
        \App\Support\CacheHelper::invalidateProductsCache();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
