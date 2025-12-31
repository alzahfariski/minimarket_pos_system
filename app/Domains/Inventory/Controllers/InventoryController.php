<?php

namespace App\Domains\Inventory\Controllers;

use App\Domains\Inventory\Actions\AdjustInventoryAction;
use App\Domains\Inventory\Actions\PerformStockOpnameAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function adjust(Request $request, AdjustInventoryAction $action): JsonResponse
    {
        $this->authorize('admin-only'); // Use Gate

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty_change' => 'required|integer|not_in:0',
            'reason' => 'required|string|max:255',
        ]);

        $adjustment = $action->execute(
            $validated['product_id'],
            $validated['qty_change'],
            $validated['reason'],
            $request->user()
        );

        return response()->json($adjustment, 201);
    }

    public function opname(Request $request, PerformStockOpnameAction $action): JsonResponse
    {
        $this->authorize('admin-only');

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'physical_stock' => 'required|integer|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        $opname = $action->execute(
            $validated['product_id'],
            $validated['physical_stock'],
            $validated['note'],
            $request->user()
        );

        return response()->json($opname, 201);
    }

    public function historyAdjustments(Request $request): JsonResponse
    {
        $this->authorize('admin-only');

        $adjustments = \App\Domains\Inventory\Models\InventoryAdjustment::with(['product', 'adjustedBy'])->latest()->get();

        return response()->json($adjustments);
    }

    public function historyOpnames(Request $request): JsonResponse
    {
        $this->authorize('admin-only');

        $opnames = \App\Domains\Inventory\Models\StockOpname::with(['product', 'createdBy'])->latest()->get();

        return response()->json($opnames);
    }
}
