<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryMovementIndexRequest;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\View\View;

class InventoryMovementController extends Controller
{
    public function index(InventoryMovementIndexRequest $request): View
    {
        $filters = $request->validated();
        $movements = InventoryMovement::query()
            ->with([
                'product:id,name,sku,unit',
                'creator:id,name',
                'adjustmentItem:id,stock_adjustment_id',
                'adjustmentItem.adjustment:id,transaction_number,status',
                'purchaseItem:id,purchase_id',
                'purchaseItem.purchase:id,transaction_number,status',
                'saleItem:id,sale_id',
                'saleItem.sale:id,transaction_number,status',
                'transactionCancellation.purchase:id,transaction_number',
                'transactionCancellation.sale:id,transaction_number',
                'transactionCancellation.stockAdjustment:id,transaction_number',
            ])
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('business_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('business_date', '<=', $to))
            ->when($filters['product_id'] ?? null, fn ($query, $id) => $query->where('product_id', $id))
            ->when($filters['movement_type'] ?? null, fn ($query, $type) => $query->where('movement_type', $type))
            ->orderByDesc('business_date')
            ->orderByDesc('confirmed_at')
            ->paginate(20)
            ->withQueryString();

        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);

        return view('inventory-movements.index', compact('movements', 'filters', 'products'));
    }
}
