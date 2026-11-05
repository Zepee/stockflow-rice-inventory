@extends('layouts.app')

@section('title', 'Produk')

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-800">Data master</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">Produk</h1>
        <p class="mt-2 text-sm text-slate-600">Kelola katalog dan pantau saldo stok terkini.</p>
    </div>
    @if ($owner)
        <a href="{{ route('products.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-800 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Tambah produk</a>
    @endif
</div>

@if ($products->getCollection()->contains(fn ($product) => str_starts_with($product->sku, 'SANIA-') || str_starts_with($product->sku, 'TOPIKOKI-')))
    <div class="mt-5 flex gap-3 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3.5 text-sm text-sky-950" role="note">
        <svg class="mt-0.5 size-5 shrink-0 text-sky-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v5m0-8h.01"/></svg>
        <p>Nama produk mengacu pada merek yang benar dijual di Indonesia. Harga dan saldo stok pada akun ini masih nilai latihan; perbarui sesuai data usaha sebelum dipakai untuk transaksi sebenarnya.</p>
    </div>
@endif

<form method="GET" action="{{ route('products.index') }}" class="mt-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-900/[0.02] sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-end">
    <div>
        <label for="q" class="mb-1.5 block text-sm font-medium text-slate-700">Cari produk</label>
        <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Nama atau SKU" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
    </div>
    @if ($owner)
        <div>
            <label for="status" class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
            <select id="status" name="status" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
                <option value="active" @selected(($filters['status'] ?? 'active') === 'active')>Aktif</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Diarsipkan</option>
                <option value="all" @selected(($filters['status'] ?? '') === 'all')>Semua</option>
            </select>
        </div>
    @endif
    <button type="submit" class="min-h-11 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Terapkan</button>
</form>

@if ($products->isEmpty())
    <div class="mt-5 rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center">
        <h2 class="text-base font-semibold text-slate-900">Belum ada produk</h2>
        <p class="mt-2 text-sm text-slate-600">Produk yang cocok dengan pencarian akan muncul di sini.</p>
    </div>
@else
    <div class="mt-5 hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.02] lg:block">
        <div class="overflow-x-auto" role="region" aria-label="Tabel produk" tabindex="0">
            <table class="w-full min-w-[900px] divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50/90 text-[11px] uppercase tracking-[0.12em] text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3.5 font-semibold">Produk</th>
                        <th scope="col" class="px-5 py-3.5 text-right font-semibold">Stok saat ini</th>
                        <th scope="col" class="px-5 py-3.5 text-right font-semibold">Harga jual</th>
                        @if ($owner)<th scope="col" class="px-5 py-3.5 text-right font-semibold">Harga beli</th>@endif
                        <th scope="col" class="px-5 py-3.5 font-semibold">Status</th>
                        <th scope="col" class="px-5 py-3.5"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($products as $product)
                        <tr class="transition-colors hover:bg-slate-50/80">
                            <td class="px-5 py-4">
                                <a href="{{ route('products.show', $product) }}" class="font-semibold text-slate-900 hover:text-emerald-800">{{ $product->name }}</a>
                                <p class="mt-1 text-xs text-slate-500">SKU {{ $product->sku }} <span class="mx-1 text-slate-300">·</span> Satuan {{ $product->unit }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right"><span class="font-semibold tabular-nums text-slate-900">{{ \App\Support\QuantityFormatter::format($product->current_stock) }}</span><span class="ml-1 text-slate-500">{{ $product->unit }}</span><p class="mt-1 text-xs text-slate-500">Minimum {{ \App\Support\QuantityFormatter::format($product->minimum_stock) }} {{ $product->unit }}</p></td>
                            <td class="whitespace-nowrap px-5 py-4 text-right font-medium tabular-nums text-slate-700">{{ \App\Support\RupiahFormatter::format($product->selling_price) }}</td>
                            @if ($owner)<td class="whitespace-nowrap px-5 py-4 text-right font-medium tabular-nums text-slate-700">{{ \App\Support\RupiahFormatter::format($product->purchase_price) }}</td>@endif
                            <td class="px-5 py-4"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $product->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-600' }}"><span class="size-1.5 rounded-full {{ $product->is_active ? 'bg-emerald-600' : 'bg-slate-400' }}"></span>{{ $product->is_active ? 'Aktif' : 'Diarsipkan' }}</span></td>
                            <td class="whitespace-nowrap px-5 py-4 text-right"><a href="{{ route('products.show', $product) }}" class="inline-flex min-h-9 items-center rounded-lg px-3 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50 hover:text-emerald-950">Detail <span class="ml-1" aria-hidden="true">→</span></a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5 space-y-3 lg:hidden">
        @foreach ($products as $product)
            <a href="{{ route('products.show', $product) }}" class="group block rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-900/[0.02] transition hover:border-emerald-200 hover:shadow-md hover:shadow-slate-900/[0.04] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="break-words font-semibold text-slate-950 group-hover:text-emerald-900">{{ $product->name }}</h2>
                        <p class="mt-1 break-all text-xs text-slate-500">SKU {{ $product->sku }} <span class="mx-1 text-slate-300">·</span> {{ $product->unit }}</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $product->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ $product->is_active ? 'Aktif' : 'Arsip' }}</span>
                </div>
                <div class="mt-4 rounded-lg bg-slate-50 px-3.5 py-3">
                    <p class="text-xs font-medium text-slate-500">Stok tersedia</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-slate-950">{{ \App\Support\QuantityFormatter::format($product->current_stock) }} <span class="text-sm font-medium text-slate-500">{{ $product->unit }}</span></p>
                    <p class="mt-1 text-xs text-slate-500">Batas minimum {{ \App\Support\QuantityFormatter::format($product->minimum_stock) }} {{ $product->unit }}</p>
                </div>
                <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-xs text-slate-500">Harga jual</dt><dd class="mt-1 font-semibold tabular-nums text-slate-800">{{ \App\Support\RupiahFormatter::format($product->selling_price) }}</dd></div>
                    @if ($owner)<div><dt class="text-xs text-slate-500">Harga beli</dt><dd class="mt-1 font-semibold tabular-nums text-slate-800">{{ \App\Support\RupiahFormatter::format($product->purchase_price) }}</dd></div>@endif
                </dl>
                <p class="mt-4 flex min-h-10 items-center justify-between border-t border-slate-100 pt-3 text-sm font-semibold text-emerald-800">Lihat detail <span aria-hidden="true">→</span></p>
            </a>
        @endforeach
    </div>

    <div class="mt-5 flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-slate-500">Menampilkan {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} dari {{ $products->total() }} produk</p>
        {{ $products->links() }}
    </div>
@endif
@endsection
