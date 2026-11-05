<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelTransactionRequest;
use App\Services\TransactionCancellationService;
use Illuminate\Http\RedirectResponse;

class TransactionCancellationController extends Controller
{
    public function store(CancelTransactionRequest $request, string $type, string $transaction, TransactionCancellationService $cancellations): RedirectResponse
    {
        abort_unless(in_array($type, ['purchase', 'sale', 'adjustment'], true), 404);
        $cancellation = $cancellations->cancel($type, $transaction, $request->validated('reason'), $request->user());

        $route = match ($type) {
            'purchase' => ['purchases.show', $cancellation->purchase_id],
            'sale' => ['sales.show', $cancellation->sale_id],
            default => ['stock-adjustments.show', $cancellation->stock_adjustment_id],
        };

        return redirect()->route($route[0], $route[1])->with('status', 'Transaksi dibatalkan dan movement reversal '.$cancellation->transaction_number.' telah dicatat.');
    }
}
