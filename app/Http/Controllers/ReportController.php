<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(ReportFilterRequest $request): View
    {
        $filters = $request->validated();
        $type = $filters['type'] ?? 'inventory';
        $this->authorizeReport($request, $type);
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);
        $owner = $request->user()->isOwner();
        $rows = collect();
        $summary = [];

        if ($type === 'inventory') {
            $query = $this->inventoryQuery($filters);
            $rows = $query->paginate(30)->withQueryString();
            $summary = [
                'product_count' => (clone $query)->count(),
                'total_stock' => (string) (clone $query)->sum('current_stock'),
                'low_stock_count' => (clone $query)->whereColumn('current_stock', '<=', 'minimum_stock')->count(),
            ];
        } elseif ($type === 'movements') {
            $rows = $this->movementQuery($filters)->paginate(30)->withQueryString();
            $summary = ['movement_count' => $rows->total()];
        } else {
            $model = $type === 'purchases' ? Purchase::class : Sale::class;
            $query = $this->transactionQuery($model, $filters);
            $rows = $query->paginate(30)->withQueryString();
            $summary = ['active_total' => (string) $this->transactionQuery($model, $filters)->where('status', 'confirmed')->sum('total_value')];
        }

        return view('reports.index', compact('type', 'rows', 'summary', 'products', 'filters', 'owner'));
    }

    public function export(ReportFilterRequest $request, string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['inventory', 'movements', 'purchases', 'sales'], true), 404);
        $this->authorizeReport($request, $type);
        $filters = $request->validated();
        $date = now('Asia/Jakarta')->format('Ymd_His');

        return response()->streamDownload(function () use ($type, $filters): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");

            if ($type === 'inventory') {
                fputcsv($stream, ['SKU', 'Produk', 'Status', 'Stok saat ini', 'Batas stok minimum', 'Satuan'], ',', '"', '');
                $this->inventoryQuery($filters)->reorder()->orderBy('id')->lazyById(500)->each(function (Product $product) use ($stream): void {
                    fputcsv($stream, [$this->csvText($product->sku), $this->csvText($product->name), $product->is_active ? 'Aktif' : 'Arsip', (string) $product->current_stock, (string) $product->minimum_stock, $this->csvText($product->unit)], ',', '"', '');
                });
            } elseif ($type === 'movements') {
                fputcsv($stream, ['Tanggal bisnis', 'Waktu konfirmasi', 'Nomor transaksi', 'Tipe', 'SKU', 'Produk', 'Perubahan stok', 'Saldo setelah', 'Pengguna'], ',', '"', '');
                $this->movementQuery($filters)->reorder()->orderBy('id')->lazyById(500)->each(function (InventoryMovement $movement) use ($stream): void {
                    fputcsv($stream, [
                        $movement->business_date->format('Y-m-d'),
                        $movement->confirmed_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                        $this->csvText($this->movementNumber($movement)),
                        $this->csvText($movement->movement_type),
                        $this->csvText($movement->product->sku),
                        $this->csvText($movement->product->name),
                        (string) $movement->quantity,
                        (string) $movement->balance_after,
                        $this->csvText($movement->creator->name),
                    ], ',', '"', '');
                });
            } else {
                $purchases = $type === 'purchases';
                fputcsv($stream, ['Nomor transaksi', 'Tanggal bisnis', $purchases ? 'Pemasok' : 'Pelanggan', 'Status', 'Nilai IDR'], ',', '"', '');
                $model = $purchases ? Purchase::class : Sale::class;
                $partnerRelation = $purchases ? 'supplier' : 'customer';
                $this->transactionQuery($model, $filters)->reorder()->with($partnerRelation.':id,name')->orderBy('id')->lazyById(500)->each(function ($transaction) use ($stream, $partnerRelation): void {
                    fputcsv($stream, [
                        $this->csvText($transaction->transaction_number),
                        $transaction->business_date->format('Y-m-d'),
                        $this->csvText($transaction->{$partnerRelation}->name),
                        $transaction->status === 'cancelled' ? 'Dibatalkan' : 'Terkonfirmasi',
                        (string) $transaction->total_value,
                    ], ',', '"', '');
                });
            }

            fclose($stream);
        }, 'stockflow_'.$type.'_'.$date.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function authorizeReport(ReportFilterRequest $request, string $type): void
    {
        abort_unless($request->user()->isOwner() || in_array($type, ['inventory', 'movements'], true), 403);
    }

    private function inventoryQuery(array $filters)
    {
        return Product::query()
            ->select(['id', 'name', 'sku', 'unit', 'is_active', 'current_stock', 'minimum_stock'])
            ->where(fn ($query) => $query->where('is_active', true)->orWhere('current_stock', '>', 0))
            ->when($filters['product_id'] ?? null, fn ($query, $id) => $query->whereKey($id));
    }

    private function movementQuery(array $filters)
    {
        return InventoryMovement::query()->with([
            'product:id,name,sku,unit',
            'creator:id,name',
            'adjustmentItem:id,stock_adjustment_id',
            'adjustmentItem.adjustment:id,transaction_number,status',
            'purchaseItem:id,purchase_id',
            'purchaseItem.purchase:id,transaction_number,status',
            'saleItem:id,sale_id',
            'saleItem.sale:id,transaction_number,status',
            'transactionCancellation:id,transaction_number',
        ])
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('business_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('business_date', '<=', $to))
            ->when($filters['product_id'] ?? null, fn ($query, $id) => $query->where('product_id', $id))
            ->when($filters['movement_type'] ?? null, fn ($query, $movementType) => $query->where('movement_type', $movementType))
            ->orderByDesc('business_date')->orderByDesc('confirmed_at');
    }

    /** @param class-string<Purchase|Sale> $model */
    private function transactionQuery(string $model, array $filters)
    {
        $partner = $model === Purchase::class ? 'supplier:id,name' : 'customer:id,name';

        return $model::query()->with($partner)
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('business_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('business_date', '<=', $to))
            ->orderByDesc('business_date')->orderByDesc('confirmed_at');
    }

    private function movementNumber(InventoryMovement $movement): string
    {
        if ($movement->transactionCancellation) {
            return $movement->transactionCancellation->transaction_number;
        }

        return $movement->adjustmentItem?->adjustment?->transaction_number
            ?? $movement->purchaseItem?->purchase?->transaction_number
            ?? $movement->saleItem?->sale?->transaction_number
            ?? '—';
    }

    private function csvText(string $value): string
    {
        return preg_match('/^[=+@\-\t\r]/', $value) ? "'".$value : $value;
    }
}
