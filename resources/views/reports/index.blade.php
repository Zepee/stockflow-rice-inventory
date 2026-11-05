@extends('layouts.app')

@section('title', 'Laporan')

@section('content')
@php($reportTypes = auth()->user()->isOwner() ? ['inventory' => 'Inventaris', 'movements' => 'Pergerakan stok', 'purchases' => 'Pembelian', 'sales' => 'Penjualan'] : ['inventory' => 'Inventaris', 'movements' => 'Pergerakan stok'])
<div>
    <p class="text-sm font-medium text-emerald-800">Informasi usaha</p>
    <h1 class="mt-1 text-2xl font-semibold tracking-tight">Laporan</h1>
    <p class="mt-2 text-sm text-slate-600">Gunakan tanggal bisnis untuk menyaring transaksi dan pergerakan stok.</p>

    <nav class="mt-6 flex gap-2 overflow-x-auto border-b border-slate-200" aria-label="Jenis laporan">
        @foreach ($reportTypes as $key => $label)
            <a href="{{ route('reports.index', ['type' => $key]) }}" @class(['shrink-0 border-b-2 px-3 py-3 text-sm font-medium', 'border-emerald-700 text-emerald-900' => $type === $key, 'border-transparent text-slate-600 hover:text-slate-900' => $type !== $key])>{{ $label }}</a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('reports.index') }}" class="mt-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-5 xl:items-end">
        <input type="hidden" name="type" value="{{ $type }}">
        @if ($type !== 'inventory')
            <div><label for="from" class="mb-1.5 block text-sm font-medium">Dari tanggal bisnis</label><input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="min-h-11 w-full rounded-lg border border-slate-300 px-3 text-sm"></div>
            <div><label for="to" class="mb-1.5 block text-sm font-medium">Sampai tanggal bisnis</label><input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="min-h-11 w-full rounded-lg border border-slate-300 px-3 text-sm"></div>
        @endif
        @if (in_array($type, ['inventory', 'movements'], true))
            <div><label for="product_id" class="mb-1.5 block text-sm font-medium">Produk</label><select id="product_id" name="product_id" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm"><option value="">Semua produk</option>@foreach ($products as $product)<option value="{{ $product->id }}" @selected(($filters['product_id'] ?? '') == $product->id)>{{ $product->name }} · {{ $product->sku }}</option>@endforeach</select></div>
        @endif
        @if ($type === 'movements')
            <div><label for="movement_type" class="mb-1.5 block text-sm font-medium">Jenis movement</label><select id="movement_type" name="movement_type" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm"><option value="">Semua jenis</option>@foreach (['purchase' => 'Pembelian', 'sale' => 'Penjualan', 'adjustment' => 'Penyesuaian', 'reversal' => 'Reversal'] as $key => $label)<option value="{{ $key }}" @selected(($filters['movement_type'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></div>
        @endif
        <div class="flex gap-2"><button type="submit" class="min-h-11 flex-1 rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">Terapkan</button><a href="{{ route('reports.index', ['type' => $type]) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-3 text-sm font-medium text-slate-700">Reset</a></div>
    </form>
    @error('from')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror

    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-2 text-sm">
            @if ($type === 'inventory')
                <span class="rounded-lg border border-slate-200 bg-white px-3 py-2">Produk dalam laporan: <strong>{{ number_format($summary['product_count'], 0, ',', '.') }}</strong></span>
                <span class="rounded-lg border border-slate-200 bg-white px-3 py-2">Total stok: <strong>{{ \App\Support\QuantityFormatter::format($summary['total_stock']) }}</strong></span>
                <span class="rounded-lg border border-slate-200 bg-white px-3 py-2">Stok minimum: <strong>{{ number_format($summary['low_stock_count'], 0, ',', '.') }}</strong></span>
            @elseif ($type === 'movements')
                <span class="rounded-lg border border-slate-200 bg-white px-3 py-2">Jumlah movement: <strong>{{ number_format($summary['movement_count'], 0, ',', '.') }}</strong></span>
            @else
                <span class="rounded-lg border border-slate-200 bg-white px-3 py-2">Nilai transaksi aktif: <strong>{{ \App\Support\RupiahFormatter::format($summary['active_total']) }}</strong></span>
                <span class="self-center text-xs text-slate-500">Transaksi batal tetap tampil; nilainya tidak dijumlahkan.</span>
            @endif
        </div>
        <a href="{{ route('reports.export', array_merge(['type' => $type], request()->query())) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Unduh CSV</a>
    </div>

    @if ($rows->isEmpty())
        <div class="mt-5 rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center"><h2 class="text-base font-semibold">Belum ada data laporan</h2><p class="mt-2 text-sm text-slate-600">Data akan muncul setelah transaksi atau movement dicatat.</p></div>
    @elseif ($type === 'inventory')
        <div class="mt-5 space-y-3 sm:hidden">
            @foreach ($rows as $product)
                <article class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-start justify-between gap-3"><div><p class="font-medium">{{ $product->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $product->sku }} · {{ $product->is_active ? 'Aktif' : 'Diarsipkan' }}</p></div><p class="shrink-0 font-semibold">{{ \App\Support\QuantityFormatter::format($product->current_stock) }} {{ $product->unit }}</p></div><p class="mt-3 text-xs text-slate-500">Batas minimum: {{ \App\Support\QuantityFormatter::format($product->minimum_stock) }} {{ $product->unit }}</p></article>
            @endforeach
        </div>
        <div class="mt-5 hidden overflow-hidden rounded-xl border border-slate-200 bg-white sm:block"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3 font-medium">SKU</th><th class="px-4 py-3 font-medium">Produk</th><th class="px-4 py-3 font-medium">Status</th><th class="px-4 py-3 font-medium">Stok saat ini</th><th class="px-4 py-3 font-medium">Minimum</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($rows as $product)<tr><td class="whitespace-nowrap px-4 py-3.5">{{ $product->sku }}</td><td class="px-4 py-3.5 font-medium">{{ $product->name }}</td><td class="px-4 py-3.5">{{ $product->is_active ? 'Aktif' : 'Diarsipkan' }}</td><td class="whitespace-nowrap px-4 py-3.5 font-semibold">{{ \App\Support\QuantityFormatter::format($product->current_stock) }} {{ $product->unit }}</td><td class="whitespace-nowrap px-4 py-3.5">{{ \App\Support\QuantityFormatter::format($product->minimum_stock) }} {{ $product->unit }}</td></tr>@endforeach</tbody></table></div></div>
    @elseif ($type === 'movements')
        <div class="mt-5 space-y-3 sm:hidden">
            @foreach ($rows as $movement)
                @php($movementNumber = $movement->transactionCancellation?->transaction_number ?? $movement->adjustmentItem?->adjustment?->transaction_number ?? $movement->purchaseItem?->purchase?->transaction_number ?? $movement->saleItem?->sale?->transaction_number ?? '—')
                <article class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex justify-between gap-3"><div class="min-w-0"><p class="font-medium">{{ $movement->product->name }}</p><p class="mt-1 break-all text-xs text-slate-500">{{ $movementNumber }} · {{ $movement->business_date->format('d/m/Y') }} · {{ ['purchase' => 'Pembelian', 'sale' => 'Penjualan', 'adjustment' => 'Penyesuaian', 'reversal' => 'Reversal'][$movement->movement_type] ?? $movement->movement_type }}</p></div><p class="shrink-0 font-semibold tabular-nums">{{ \App\Support\QuantityFormatter::format($movement->quantity) }} {{ $movement->product->unit }}</p></div><p class="mt-3 text-xs text-slate-500">Saldo: {{ \App\Support\QuantityFormatter::format($movement->balance_after) }} {{ $movement->product->unit }} · {{ $movement->creator->name }}</p></article>
            @endforeach
        </div>
        <div class="mt-5 hidden overflow-hidden rounded-xl border border-slate-200 bg-white sm:block"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3 font-medium">Tanggal</th><th class="px-4 py-3 font-medium">Nomor transaksi</th><th class="px-4 py-3 font-medium">Produk</th><th class="px-4 py-3 font-medium">Jenis</th><th class="px-4 py-3 font-medium">Perubahan</th><th class="px-4 py-3 font-medium">Saldo</th><th class="px-4 py-3 font-medium">Pengguna</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($rows as $movement)<tr>@php($movementNumber = $movement->transactionCancellation?->transaction_number ?? $movement->adjustmentItem?->adjustment?->transaction_number ?? $movement->purchaseItem?->purchase?->transaction_number ?? $movement->saleItem?->sale?->transaction_number ?? '—')<td class="whitespace-nowrap px-4 py-3.5">{{ $movement->business_date->format('d/m/Y') }}</td><td class="whitespace-nowrap px-4 py-3.5">{{ $movementNumber }}</td><td class="px-4 py-3.5">{{ $movement->product->name }}</td><td class="px-4 py-3.5">{{ ['purchase' => 'Pembelian', 'sale' => 'Penjualan', 'adjustment' => 'Penyesuaian', 'reversal' => 'Reversal'][$movement->movement_type] ?? $movement->movement_type }}</td><td class="whitespace-nowrap px-4 py-3.5 tabular-nums">{{ \App\Support\QuantityFormatter::format($movement->quantity) }} {{ $movement->product->unit }}</td><td class="whitespace-nowrap px-4 py-3.5 tabular-nums">{{ \App\Support\QuantityFormatter::format($movement->balance_after) }} {{ $movement->product->unit }}</td><td class="whitespace-nowrap px-4 py-3.5">{{ $movement->creator->name }}</td></tr>@endforeach</tbody></table></div></div>
    @else
        @php($partnerField = $type === 'purchases' ? 'supplier' : 'customer')
        <div class="mt-5 space-y-3 sm:hidden">
            @foreach ($rows as $transaction)
                <article class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-start justify-between gap-3"><div><p class="font-semibold">{{ $transaction->transaction_number }}</p><p class="mt-1 text-sm text-slate-600">{{ $transaction->{$partnerField}->name }}</p></div><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $transaction->status === 'cancelled' ? 'bg-red-50 text-red-800' : 'bg-emerald-50 text-emerald-800' }}">{{ $transaction->status === 'cancelled' ? 'Dibatalkan' : 'Terkonfirmasi' }}</span></div><div class="mt-3 flex items-center justify-between text-sm"><span class="text-slate-500">{{ $transaction->business_date->format('d/m/Y') }}</span><strong>{{ \App\Support\RupiahFormatter::format($transaction->total_value) }}</strong></div></article>
            @endforeach
        </div>
        <div class="mt-5 hidden overflow-hidden rounded-xl border border-slate-200 bg-white sm:block"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3 font-medium">Nomor transaksi</th><th class="px-4 py-3 font-medium">Tanggal bisnis</th><th class="px-4 py-3 font-medium">{{ $type === 'purchases' ? 'Pemasok' : 'Pelanggan' }}</th><th class="px-4 py-3 font-medium">Status</th><th class="px-4 py-3 text-right font-medium">Nilai IDR</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($rows as $transaction)<tr><td class="whitespace-nowrap px-4 py-3.5 font-medium">{{ $transaction->transaction_number }}</td><td class="whitespace-nowrap px-4 py-3.5">{{ $transaction->business_date->format('d/m/Y') }}</td><td class="px-4 py-3.5">{{ $transaction->{$partnerField}->name }}</td><td class="px-4 py-3.5"><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $transaction->status === 'cancelled' ? 'bg-red-50 text-red-800' : 'bg-emerald-50 text-emerald-800' }}">{{ $transaction->status === 'cancelled' ? 'Dibatalkan' : 'Terkonfirmasi' }}</span></td><td class="whitespace-nowrap px-4 py-3.5 text-right">{{ \App\Support\RupiahFormatter::format($transaction->total_value) }}</td></tr>@endforeach</tbody></table></div></div>
    @endif
    <div class="mt-5">{{ $rows->links() }}</div>
</div>
@endsection
