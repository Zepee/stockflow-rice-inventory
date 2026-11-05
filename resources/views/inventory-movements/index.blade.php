@extends('layouts.app')

@section('title', 'Riwayat stok')

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-800">Inventaris</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">Riwayat stok</h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-600">Telusuri setiap perubahan stok, saldo setelah transaksi, dan pengguna yang mencatatnya.</p>
    </div>
    @if (auth()->user()->isOwner())
        <a href="{{ route('stock-adjustments.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-800 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-900">Penyesuaian stok</a>
    @endif
</div>

<form method="GET" action="{{ route('inventory-movements.index') }}" class="mt-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-900/[0.02] sm:grid-cols-2 xl:grid-cols-6 xl:items-end">
    <div><label for="from" class="mb-1.5 block text-sm font-medium">Dari tanggal</label><input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="min-h-11 w-full rounded-lg border border-slate-300 px-3 text-sm"></div>
    <div><label for="to" class="mb-1.5 block text-sm font-medium">Sampai tanggal</label><input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="min-h-11 w-full rounded-lg border border-slate-300 px-3 text-sm"></div>
    <div><label for="product_id" class="mb-1.5 block text-sm font-medium">Produk</label><select id="product_id" name="product_id" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm"><option value="">Semua produk</option>@foreach ($products as $product)<option value="{{ $product->id }}" @selected(($filters['product_id'] ?? '') == $product->id)>{{ $product->name }} · {{ $product->sku }}</option>@endforeach</select></div>
    <div><label for="movement_type" class="mb-1.5 block text-sm font-medium">Jenis pergerakan</label><select id="movement_type" name="movement_type" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15"><option value="">Semua jenis</option>@foreach (['purchase' => 'Pembelian', 'sale' => 'Penjualan', 'adjustment' => 'Penyesuaian', 'reversal' => 'Pembatalan / reversal'] as $key => $label)<option value="{{ $key }}" @selected(($filters['movement_type'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></div>
    <button type="submit" class="min-h-11 rounded-lg bg-emerald-800 px-4 text-sm font-semibold text-white transition hover:bg-emerald-900">Terapkan filter</button>
    <a href="{{ route('inventory-movements.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg px-3 text-sm font-medium text-slate-600 hover:bg-slate-50">Reset</a>
</form>
@error('from')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
@error('product_id')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror

@if ($movements->isEmpty())
    <div class="mt-5 rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center">
        <h2 class="text-base font-semibold text-slate-900">Belum ada pergerakan stok</h2>
        <p class="mt-2 text-sm text-slate-600">Pergerakan akan muncul setelah Owner mencatat penyesuaian stok.</p>
    </div>
@else
    <div class="mt-5 space-y-3 lg:hidden">
        @foreach ($movements as $movement)
            @php($outgoing = str_starts_with((string) $movement->quantity, '-'))
            <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.02]">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 p-4 sm:px-5">
                    <div class="min-w-0"><p class="break-words font-semibold text-slate-950">{{ $movement->product->name }}</p><p class="mt-1 break-all text-xs text-slate-500">SKU {{ $movement->product->sku }}</p></div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $movement->movement_type === 'purchase' ? 'bg-emerald-50 text-emerald-800' : ($movement->movement_type === 'sale' ? 'bg-rose-50 text-rose-800' : ($movement->movement_type === 'adjustment' ? 'bg-amber-50 text-amber-900' : 'bg-violet-50 text-violet-800')) }}">{{ ['purchase' => 'Pembelian', 'sale' => 'Penjualan', 'adjustment' => 'Penyesuaian', 'reversal' => 'Pembatalan'][$movement->movement_type] ?? $movement->movement_type }}</span>
                </div>
                <div class="flex items-center justify-between gap-3 bg-slate-50/80 px-4 py-3 sm:px-5">
                    <div><p class="text-xs font-medium text-slate-500">Perubahan stok</p><p class="mt-1 text-lg font-semibold tabular-nums {{ $outgoing ? 'text-rose-700' : 'text-emerald-800' }}">{{ $outgoing ? '−' : '+' }}{{ \App\Support\QuantityFormatter::format(ltrim((string) $movement->quantity, '-')) }} <span class="text-sm font-medium">{{ $movement->product->unit }}</span></p></div>
                    <div class="text-right"><p class="text-xs font-medium text-slate-500">Saldo setelah</p><p class="mt-1 text-sm font-semibold tabular-nums text-slate-900">{{ \App\Support\QuantityFormatter::format($movement->balance_after) }} {{ $movement->product->unit }}</p></div>
                </div>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-4 p-4 text-sm sm:px-5">
                    <div><dt class="text-xs text-slate-500">Tanggal bisnis</dt><dd class="mt-1 font-medium text-slate-800">{{ $movement->business_date->format('d/m/Y') }} <span class="text-xs font-normal text-slate-500">· {{ $movement->confirmed_at->format('H:i') }}</span></dd></div>
                    <div><dt class="text-xs text-slate-500">Dicatat oleh</dt><dd class="mt-1 font-medium text-slate-800">{{ $movement->creator->name }}</dd></div>
                    <div class="col-span-2"><dt class="text-xs text-slate-500">Transaksi</dt><dd class="mt-1 break-all">@if ($movement->adjustmentItem)<a href="{{ route('stock-adjustments.show', $movement->adjustmentItem->adjustment) }}" class="font-semibold text-emerald-800 hover:text-emerald-950">{{ $movement->adjustmentItem->adjustment->transaction_number }}</a>@elseif ($movement->purchaseItem)<a href="{{ route('purchases.show', $movement->purchaseItem->purchase) }}" class="font-semibold text-emerald-800 hover:text-emerald-950">{{ $movement->purchaseItem->purchase->transaction_number }}</a>@elseif ($movement->saleItem)<a href="{{ route('sales.show', $movement->saleItem->sale) }}" class="font-semibold text-emerald-800 hover:text-emerald-950">{{ $movement->saleItem->sale->transaction_number }}</a>@elseif ($movement->transactionCancellation){{ $movement->transactionCancellation->transaction_number }} · Pembatalan @else—@endif</dd></div>
                </dl>
            </article>
        @endforeach
    </div>
    <div class="mt-5 hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.02] lg:block">
        <div class="overflow-x-auto" role="region" aria-label="Tabel riwayat stok" tabindex="0">
            <table class="w-full min-w-[1050px] divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50/90 text-[11px] uppercase tracking-[0.12em] text-slate-500"><tr><th scope="col" class="px-5 py-3.5 font-semibold">Tanggal bisnis</th><th scope="col" class="px-5 py-3.5 font-semibold">Produk</th><th scope="col" class="px-5 py-3.5 font-semibold">Jenis</th><th scope="col" class="px-5 py-3.5 text-right font-semibold">Perubahan</th><th scope="col" class="px-5 py-3.5 text-right font-semibold">Saldo setelah</th><th scope="col" class="px-5 py-3.5 font-semibold">Transaksi</th><th scope="col" class="px-5 py-3.5 font-semibold">Pengguna</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($movements as $movement)
                        @php($outgoing = str_starts_with((string) $movement->quantity, '-'))
                        <tr class="transition-colors hover:bg-slate-50/80">
                            <td class="whitespace-nowrap px-5 py-4"><p class="font-medium text-slate-800">{{ $movement->business_date->format('d/m/Y') }}</p><p class="mt-1 text-xs text-slate-500">{{ $movement->confirmed_at->format('H:i') }}</p></td>
                            <td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $movement->product->name }}</p><p class="mt-1 text-xs text-slate-500">SKU {{ $movement->product->sku }}</p></td>
                            <td class="whitespace-nowrap px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $movement->movement_type === 'purchase' ? 'bg-emerald-50 text-emerald-800' : ($movement->movement_type === 'sale' ? 'bg-rose-50 text-rose-800' : ($movement->movement_type === 'adjustment' ? 'bg-amber-50 text-amber-900' : 'bg-violet-50 text-violet-800')) }}">{{ ['purchase' => 'Pembelian', 'sale' => 'Penjualan', 'adjustment' => 'Penyesuaian', 'reversal' => 'Pembatalan'][$movement->movement_type] ?? $movement->movement_type }}</span></td>
                            <td class="whitespace-nowrap px-5 py-4 text-right font-semibold tabular-nums {{ $outgoing ? 'text-rose-700' : 'text-emerald-800' }}">{{ $outgoing ? '−' : '+' }}{{ \App\Support\QuantityFormatter::format(ltrim((string) $movement->quantity, '-')) }} {{ $movement->product->unit }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right font-semibold tabular-nums text-slate-900">{{ \App\Support\QuantityFormatter::format($movement->balance_after) }} {{ $movement->product->unit }}</td>
                            <td class="whitespace-nowrap px-5 py-4">@if ($movement->adjustmentItem)<a href="{{ route('stock-adjustments.show', $movement->adjustmentItem->adjustment) }}" class="font-semibold text-emerald-800 hover:text-emerald-950">{{ $movement->adjustmentItem->adjustment->transaction_number }}</a>@elseif ($movement->purchaseItem)<a href="{{ route('purchases.show', $movement->purchaseItem->purchase) }}" class="font-semibold text-emerald-800 hover:text-emerald-950">{{ $movement->purchaseItem->purchase->transaction_number }}</a>@elseif ($movement->saleItem)<a href="{{ route('sales.show', $movement->saleItem->sale) }}" class="font-semibold text-emerald-800 hover:text-emerald-950">{{ $movement->saleItem->sale->transaction_number }}</a>@elseif ($movement->transactionCancellation){{ $movement->transactionCancellation->transaction_number }} · Pembatalan @else—@endif</td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-700">{{ $movement->creator->name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5 flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-slate-500">Menampilkan {{ $movements->firstItem() ?? 0 }}–{{ $movements->lastItem() ?? 0 }} dari {{ $movements->total() }} pergerakan</p>
        {{ $movements->links() }}
    </div>
@endif
@endsection
