@extends('layouts.app')

@section('title', 'Penyesuaian stok')

@section('content')
<div class="max-w-4xl">
    <a href="{{ route('inventory-movements.index') }}" class="text-sm font-medium text-emerald-800 hover:text-emerald-950">← Riwayat stok</a>
    <h1 class="mt-4 text-2xl font-semibold tracking-tight">Penyesuaian stok</h1>
    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Catat stok awal atau koreksi fisik. Setiap baris akan masuk ke ledger dan memperbarui saldo produk dalam satu transaksi database.</p>

    @if ($products->isEmpty())
        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
            Belum ada produk. Buat produk terlebih dahulu sebelum mencatat stok.
            <a href="{{ route('products.create') }}" class="ml-1 font-semibold underline">Tambah produk</a>
        </div>
    @else
        <form method="POST" action="{{ route('stock-adjustments.store') }}" class="mt-6 space-y-5" data-adjustment-form>
            @csrf
            <input type="hidden" name="submission_key" value="{{ old('submission_key', $submissionKey) }}">

            <section class="grid gap-5 rounded-xl border border-slate-200 bg-white p-5 sm:grid-cols-2 sm:p-6">
                <div>
                    <label for="business_date" class="mb-1.5 block text-sm font-medium">Tanggal bisnis</label>
                    <input id="business_date" name="business_date" type="date" value="{{ old('business_date', now(config('app.timezone'))->toDateString()) }}" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
                    <p class="mt-1.5 text-xs text-slate-500">Tanggal ini dipakai untuk laporan. Waktu konfirmasi dicatat terpisah otomatis.</p>
                    @error('business_date')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="reason" class="mb-1.5 block text-sm font-medium">Alasan penyesuaian</label>
                    <textarea id="reason" name="reason" rows="3" required maxlength="1000" placeholder="Contoh: menghitung stok awal sebelum mulai menggunakan StockFlow" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">{{ old('reason') }}</textarea>
                    @error('reason')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold">Produk yang disesuaikan</h2>
                        <p class="mt-1 text-sm text-slate-600">Gunakan kuantitas positif hingga tiga angka desimal.</p>
                    </div>
                    <button type="button" data-add-adjustment-row class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Tambah produk</button>
                </div>

                <div class="mt-5 space-y-4" data-adjustment-rows>
                    @foreach (old('items', [['product_id' => '', 'direction' => 'increase', 'quantity' => '']]) as $index => $item)
                        <div class="grid gap-3 rounded-lg border border-slate-200 p-4 sm:grid-cols-[minmax(0,2fr)_minmax(8rem,1fr)_minmax(8rem,1fr)_auto] sm:items-end" data-transaction-row>
                            <div>
                                <label for="product-{{ $index }}" class="mb-1.5 block text-sm font-medium">Produk</label>
                                <select id="product-{{ $index }}" name="items[{{ $index }}][product_id]" required class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
                                    <option value="">Pilih produk</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" @selected((string) ($item['product_id'] ?? '') === (string) $product->id)>{{ $product->name }} · {{ $product->sku }} · stok {{ \App\Support\QuantityFormatter::format($product->current_stock) }} {{ $product->unit }}</option>
                                    @endforeach
                                </select>
                                @error("items.$index.product_id")<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="direction-{{ $index }}" class="mb-1.5 block text-sm font-medium">Aksi</label>
                                <select id="direction-{{ $index }}" name="items[{{ $index }}][direction]" required class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
                                    <option value="increase" @selected(($item['direction'] ?? 'increase') === 'increase')>Tambah stok</option>
                                    <option value="decrease" @selected(($item['direction'] ?? '') === 'decrease')>Kurangi stok</option>
                                </select>
                                @error("items.$index.direction")<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="quantity-{{ $index }}" class="mb-1.5 block text-sm font-medium">Kuantitas</label>
                                <input id="quantity-{{ $index }}" name="items[{{ $index }}][quantity]" type="text" inputmode="decimal" value="{{ $item['quantity'] ?? '' }}" required placeholder="0.000" class="min-h-11 w-full rounded-lg border border-slate-300 px-3 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
                                @error("items.$index.quantity")<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                            </div>
                            <button type="button" data-remove-transaction-row class="min-h-11 rounded-lg px-3 text-sm font-medium text-slate-600 hover:bg-slate-100">Hapus baris</button>
                        </div>
                    @endforeach
                </div>
                @error('items')<p class="mt-3 text-sm text-red-700">{{ $message }}</p>@enderror
            </section>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('inventory-movements.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
                <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">Konfirmasi penyesuaian</button>
            </div>
        </form>

        <template id="adjustment-row-template">
            <div class="grid gap-3 rounded-lg border border-slate-200 p-4 sm:grid-cols-[minmax(0,2fr)_minmax(8rem,1fr)_minmax(8rem,1fr)_auto] sm:items-end" data-transaction-row>
                <div>
                    <label for="product-__INDEX__" class="mb-1.5 block text-sm font-medium">Produk</label>
                    <select id="product-__INDEX__" name="items[__INDEX__][product_id]" required class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
                        <option value="">Pilih produk</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} · {{ $product->sku }} · stok {{ \App\Support\QuantityFormatter::format($product->current_stock) }} {{ $product->unit }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="direction-__INDEX__" class="mb-1.5 block text-sm font-medium">Aksi</label>
                    <select id="direction-__INDEX__" name="items[__INDEX__][direction]" required class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15"><option value="increase">Tambah stok</option><option value="decrease">Kurangi stok</option></select>
                </div>
                <div>
                    <label for="quantity-__INDEX__" class="mb-1.5 block text-sm font-medium">Kuantitas</label>
                    <input id="quantity-__INDEX__" name="items[__INDEX__][quantity]" type="text" inputmode="decimal" required placeholder="0.000" class="min-h-11 w-full rounded-lg border border-slate-300 px-3 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
                </div>
                <button type="button" data-remove-transaction-row class="min-h-11 rounded-lg px-3 text-sm font-medium text-slate-600 hover:bg-slate-100">Hapus baris</button>
            </div>
        </template>
    @endif
</div>
@endsection


