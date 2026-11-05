<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockAdjustment;
use App\Models\TransactionCancellation;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = now('Asia/Jakarta')->toDateString();
        $movementTotals = InventoryMovement::query()
            ->whereDate('business_date', $today)
            ->selectRaw("COALESCE(SUM(CASE WHEN movement_type <> 'reversal' AND quantity > 0 THEN quantity ELSE 0 END), 0) as incoming")
            ->selectRaw("COALESCE(SUM(CASE WHEN movement_type <> 'reversal' AND quantity < 0 THEN -quantity ELSE 0 END), 0) as outgoing")
            ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'reversal' AND quantity > 0 THEN quantity ELSE 0 END), 0) as reversal_incoming")
            ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'reversal' AND quantity < 0 THEN -quantity ELSE 0 END), 0) as reversal_outgoing")
            ->first();

        $inventory = Product::query()
            ->selectRaw('COALESCE(SUM(current_stock), 0) as total_stock')
            ->first();

        $metrics = [
            'product_count' => Product::query()->where('is_active', true)->count(),
            'total_stock' => (string) $inventory->total_stock,
            'low_stock_count' => Product::query()->where('is_active', true)->whereColumn('current_stock', '<=', 'minimum_stock')->count(),
            'incoming' => (string) $movementTotals->incoming,
            'outgoing' => (string) $movementTotals->outgoing,
            'reversal_incoming' => (string) $movementTotals->reversal_incoming,
            'reversal_outgoing' => (string) $movementTotals->reversal_outgoing,
            'purchase_value' => null,
            'sales_value' => null,
        ];

        if (auth()->user()->isOwner()) {
            $metrics['purchase_value'] = (string) Purchase::query()->whereDate('business_date', $today)->where('status', 'confirmed')->sum('total_value');
            $metrics['sales_value'] = (string) Sale::query()->whereDate('business_date', $today)->where('status', 'confirmed')->sum('total_value');
        }

        $recentTransactions = collect()
            ->concat(Purchase::query()->select(['id', 'transaction_number', 'business_date', 'confirmed_at', 'status', 'supplier_id'])->with('supplier:id,name')->latest('confirmed_at')->limit(8)->get()->map(fn (Purchase $purchase) => [
                'number' => $purchase->transaction_number, 'type' => 'Pembelian', 'partner' => $purchase->supplier->name,
                'business_date' => $purchase->business_date, 'confirmed_at' => $purchase->confirmed_at, 'status' => $purchase->status,
                'url' => route('purchases.show', $purchase),
            ]))
            ->concat(Sale::query()->select(['id', 'transaction_number', 'business_date', 'confirmed_at', 'status', 'customer_id'])->with('customer:id,name')->latest('confirmed_at')->limit(8)->get()->map(fn (Sale $sale) => [
                'number' => $sale->transaction_number, 'type' => 'Penjualan', 'partner' => $sale->customer->name,
                'business_date' => $sale->business_date, 'confirmed_at' => $sale->confirmed_at, 'status' => $sale->status,
                'url' => route('sales.show', $sale),
            ]))
            ->concat(StockAdjustment::query()->select(['id', 'transaction_number', 'business_date', 'confirmed_at', 'status'])->latest('confirmed_at')->limit(8)->get()->map(fn (StockAdjustment $adjustment) => [
                'number' => $adjustment->transaction_number, 'type' => 'Penyesuaian stok', 'partner' => null,
                'business_date' => $adjustment->business_date, 'confirmed_at' => $adjustment->confirmed_at, 'status' => $adjustment->status,
                'url' => route('stock-adjustments.show', $adjustment),
            ]))
            ->concat(TransactionCancellation::query()->select(['id', 'transaction_number', 'purchase_id', 'sale_id', 'stock_adjustment_id', 'confirmed_at'])->with([
                'purchase:id,transaction_number', 'sale:id,transaction_number', 'stockAdjustment:id,transaction_number',
            ])->latest('confirmed_at')->limit(8)->get()->map(function (TransactionCancellation $cancellation): array {
                $source = $cancellation->purchase ?? $cancellation->sale ?? $cancellation->stockAdjustment;
                $url = $cancellation->purchase
                    ? route('purchases.show', $cancellation->purchase)
                    : ($cancellation->sale ? route('sales.show', $cancellation->sale) : route('stock-adjustments.show', $cancellation->stockAdjustment));

                return [
                    'number' => $cancellation->transaction_number, 'type' => 'Pembatalan', 'partner' => $source?->transaction_number,
                    'business_date' => $cancellation->confirmed_at->toDateString(), 'confirmed_at' => $cancellation->confirmed_at,
                    'status' => 'cancelled', 'url' => $url,
                ];
            }))
            ->sortByDesc(fn (array $transaction) => $transaction['confirmed_at']->getTimestamp())
            ->take(8)->values();
        $lowStockProducts = Product::query()->where('is_active', true)->whereColumn('current_stock', '<=', 'minimum_stock')
            ->orderBy('current_stock')->limit(8)->get(['id', 'name', 'sku', 'unit', 'current_stock', 'minimum_stock']);

        return view('dashboard', compact('metrics', 'recentTransactions', 'lowStockProducts', 'today'));
    }
}
