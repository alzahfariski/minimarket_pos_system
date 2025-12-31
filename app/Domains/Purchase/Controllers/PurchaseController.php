<?php

namespace App\Domains\Purchase\Controllers;

use App\Domains\Purchase\Actions\CreatePurchaseAction;
use App\Domains\Purchase\Models\Purchase;
use App\Domains\Purchase\Requests\CreatePurchaseRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PurchaseController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Purchase::class);

        $purchases = Purchase::with(['supplier', 'items.product'])->latest()->get();

        return response()->json($purchases);
    }

    public function store(
        \Illuminate\Http\Request $request,
        CreatePurchaseAction $action
    ): JsonResponse {
        $this->authorize('create', Purchase::class); // Check PurchasePolicy

        $validated = $request->validate([
             'supplier_id' => 'required|exists:suppliers,id',
             'items' => 'required|array|min:1',
             'items.*.product_id' => 'required|exists:products,id',
             'items.*.quantity' => 'required|integer|min:1',
        ]);

        $purchase = $action->execute(
            $validated['supplier_id'],
            $validated['items'],
            $request->user()
        );

        return response()->json($purchase->load('items'), 201);
    }

    public function show(Purchase $purchase): JsonResponse
    {
        $this->authorize('view', $purchase);
        return response()->json($purchase->load('items'));
    }
}
