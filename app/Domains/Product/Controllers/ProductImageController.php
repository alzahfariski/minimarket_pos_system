<?php

namespace App\Domains\Product\Controllers;

use App\Domains\Product\Actions\UploadProductImageAction;
use App\Domains\Product\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function update(
        Request $request,
        Product $product,
        UploadProductImageAction $action
    ): JsonResponse {
        $this->authorize('admin-only');

        $request->validate([
            'image' => 'required|image|max:2048', // 2MB Max
        ]);

        $updatedProduct = $action->execute($product, $request->file('image'));

        return response()->json([
            'message' => 'Image uploaded successfully.',
            'image_url' => Storage::disk('s3')->url($updatedProduct->image_path),
        ]);
    }
}
