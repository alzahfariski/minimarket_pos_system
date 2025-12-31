<?php

namespace App\Domains\Pos\Controllers;

use App\Domains\Pos\Actions\ProcessPosTransactionAction;
use App\Domains\Pos\Models\PosTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function store(
        Request $request,
        ProcessPosTransactionAction $action
    ): JsonResponse {
        $this->authorize('create', PosTransaction::class);

        $validated = $request->validate([
            'payment_amount' => 'required|integer|min:0',
            'payment_method' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $transaction = $action->execute(
            $validated['items'],
            $validated['payment_amount'],
            $validated['payment_method'],
            $request->user()
        );

        return response()->json($transaction->load('items'), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PosTransaction::class);

        $query = PosTransaction::with(['cashier', 'items.product'])->latest();

        if ($request->has(['start_date', 'end_date'])) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        return response()->json($query->get());
    }

    public function show(PosTransaction $pos): JsonResponse
    {
        $this->authorize('view', $pos);
        
        return response()->json($pos->load(['cashier', 'items.product']));
    }
}
