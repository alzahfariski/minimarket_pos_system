<?php

namespace App\Domains\Product\Actions;

use App\Domains\Product\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadProductImageAction
{
    public function execute(Product $product, UploadedFile $file): Product
    {
        // Delete old image if exists
        if ($product->image_path) {
            Storage::disk('s3')->delete($product->image_path);
        }

        // Store new image
        $path = $file->store('products', 's3');

        $product->update(['image_path' => $path]);

        \App\Support\CacheHelper::invalidateProductsCache();

        return $product;
    }
}
