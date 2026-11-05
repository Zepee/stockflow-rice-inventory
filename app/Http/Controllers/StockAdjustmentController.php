<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockAdjustments\StoreStockAdjustmentRequest;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Services\StockAdjustmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function create(): View
    {
        return view('stock-adjustments.create', [
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'sku', 'unit', 'current_stock']),
            'submissionKey' => (string) str()->uuid(),
        ]);
    }

    public function store(StoreStockAdjustmentRequest $request, StockAdjustmentService $adjustments): RedirectResponse
    {
        $adjustment = $adjustments->confirm($request->validated(), $request->user());

        return redirect()->route('stock-adjustments.show', $adjustment)
            ->with('status', 'Penyesuaian stok berhasil dikonfirmasi.');
    }

    public function show(StockAdjustment $stockAdjustment): View
    {
        $stockAdjustment->load([
            'warehouse:id,name',
            'creator:id,name',
            'cancellation.authorizer:id,name',
            'items.product:id,name,sku,unit',
            'items.movement:id,stock_adjustment_item_id,quantity,balance_after',
        ]);

        return view('stock-adjustments.show', ['adjustment' => $stockAdjustment]);
    }
}
