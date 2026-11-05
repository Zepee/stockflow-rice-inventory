@extends('layouts.app')

@section('title', 'Catat penjualan')

@section('content')
@php($formItems = old('items', $draftData['items'] ?? [['product_id' => '', 'quantity' => '', 'selling_price_override' => '', 'override_reason' => '']]))
<div class="max-w-5xl">
    <a href="{{ route('sales.index') }}" data-transaction-dialog-cancel data-transaction-dialog-back class="inline-flex min-h-10 items-center text-sm font-medium text-emerald-800 hover:text-emerald-950">← Riwayat penjualan</a>
    <div data-transaction-intro class="mt-3 border-b border-slate-200 pb-5 sm:mt-4">
        <p data-transaction-step class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-800">Langkah 1 dari 2 · Stok keluar</p>
        <h1 class="mt-1.5 text-2xl font-semibold tracking-tight text-slate-950">Catat penjualan</h1>
        <p data-transaction-description class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Pilih pelanggan dan barang yang terjual. Ketersediaan stok diperiksa lagi saat konfirmasi.</p>
    </div>

    @if ($customers->isEmpty() || $products->isEmpty())
        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-900">
            @if ($customers->isEmpty()) Belum ada pelanggan. @endif
            @if ($products->isEmpty()) Belum ada produk aktif dengan stok tersedia. @endif
            @if ($owner)
                @if ($customers->isEmpty()) <a href="{{ route('customers.create') }}" class="font-semibold underline">Tambah pelanggan</a>. @endif
                @if ($products->isEmpty()) <a href="{{ route('purchases.create') }}" class="font-semibold underline">Catat barang masuk</a> atau lakukan penyesuaian stok. @endif
            @else
                Hubungi Owner untuk menyiapkan pelanggan dan memastikan stok tersedia.
            @endif
        </div>
    @else
        <form method="POST" action="{{ route('sales.prepare') }}" class="mt-5 space-y-4" data-sale-form>
            @csrf
            <input type="hidden" name="draft_key" value="{{ $draftKey }}">

            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-900/[0.02] sm:p-6">
                <div class="mb-4 border-b border-slate-100 pb-3"><h2 class="text-sm font-semibold text-slate-950">Informasi penjualan</h2><p class="mt-1 text-xs text-slate-500">Pilih pelanggan dan tanggal yang akan digunakan dalam laporan.</p></div>
                <div class="grid gap-4 sm:grid-cols-2 sm:gap-5">
                <div>
                    <label for="customer_id" class="mb-1.5 block text-sm font-medium">Pelanggan</label>
                    <select id="customer_id" name="customer_id" required class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
                        <option value="">Pilih pelanggan</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) old('customer_id', $draftData['customer_id'] ?? '') === (string) $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    @error('customer_id')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="business_date" class="mb-1.5 block text-sm font-medium">Tanggal bisnis</label>
                    <input id="business_date" name="business_date" type="date" value="{{ old('business_date', $draftData['business_date'] ?? now(config('app.timezone'))->toDateString()) }}" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
                    <p class="mt-1.5 text-xs text-slate-500">Tanggal laporan; waktu konfirmasi dicatat terpisah.</p>
                    @error('business_date')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-900/[0.02] sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div><h2 class="text-base font-semibold text-slate-950">Produk terjual</h2><p class="mt-1 text-sm text-slate-600">Harga mengikuti pengaturan Owner. Kuantitas mendukung hingga tiga angka desimal.
                        @if ($owner) Override harga per item tersedia; alasan wajib jika digunakan. @endif
                    </p></div>
                    <button type="button" data-add-sale-row class="inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:border-emerald-700 hover:bg-emerald-50 sm:w-auto">Tambah produk</button>
                </div>

                <div class="mt-5 space-y-4" data-sale-rows>
                    @foreach ($formItems as $index => $item)
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 sm:p-5" data-transaction-row>
                            <div class="mb-4 flex items-center justify-between border-b border-slate-200/80 pb-3"><p class="flex items-center gap-2 text-sm font-semibold text-slate-800"><span class="flex size-7 items-center justify-center rounded-lg bg-rose-100 text-xs font-bold text-rose-900">{{ $loop->iteration }}</span>Produk terjual</p><button type="button" data-remove-transaction-row class="min-h-10 rounded-lg px-3 text-xs font-semibold text-slate-500 transition hover:bg-red-50 hover:text-red-700">Hapus</button></div>
                            <div class="grid gap-4 sm:grid-cols-2 sm:items-end lg:grid-cols-[minmax(0,2fr)_minmax(9rem,1fr)_minmax(10rem,1fr)_minmax(11rem,1fr)]">
                            <div>
                                <label for="product-{{ $index }}" class="mb-1.5 block text-sm font-medium">Produk</label>
                                <select id="product-{{ $index }}" name="items[{{ $index }}][product_id]" required class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
                                    <option value="">Pilih produk</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" @selected((string) ($item['product_id'] ?? '') === (string) $product->id)>{{ $product->name }} · {{ $product->sku }} · stok {{ \App\Support\QuantityFormatter::format($product->current_stock) }} {{ $product->unit }} · {{ \App\Support\RupiahFormatter::format($product->selling_price) }}/{{ $product->unit }}</option>
                                    @endforeach
                                </select>
                                @error("items.$index.product_id")<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="quantity-{{ $index }}" class="mb-1.5 block text-sm font-medium">Kuantitas ({{ $products->firstWhere('id', (int) ($item['product_id'] ?? 0))?->unit ?? 'kg' }})</label>
                                <input id="quantity-{{ $index }}" name="items[{{ $index }}][quantity]" type="text" inputmode="decimal" value="{{ $item['quantity'] ?? '' }}" required placeholder="0.000" class="min-h-11 w-full rounded-lg border border-slate-300 px-3 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
                                @error("items.$index.quantity")<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                            </div>
                            @if ($owner)
                                <div>
                                    <label for="selling-price-{{ $index }}" class="mb-1.5 block text-sm font-medium">Override harga jual <span class="font-normal text-slate-500">(opsional)</span></label>
                                    <input id="selling-price-{{ $index }}" name="items[{{ $index }}][selling_price_override]" type="text" inputmode="numeric" value="{{ $item['selling_price_override'] ?? '' }}" placeholder="Pakai harga default" class="min-h-11 w-full rounded-lg border border-slate-300 px-3 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
                                    @error("items.$index.selling_price_override")<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="override-reason-{{ $index }}" class="mb-1.5 block text-sm font-medium">Alasan override</label>
                                    <input id="override-reason-{{ $index }}" name="items[{{ $index }}][override_reason]" value="{{ $item['override_reason'] ?? '' }}" maxlength="1000" placeholder="Wajib jika harga diubah" class="min-h-11 w-full rounded-lg border border-slate-300 px-3 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
                                    @error("items.$index.override_reason")<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                                </div>
                            @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('items')<p class="mt-3 text-sm text-red-700">{{ $message }}</p>@enderror
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-900/[0.02] sm:p-6">
                <label for="notes" class="mb-1.5 block text-sm font-medium">Catatan <span class="font-normal text-slate-500">(opsional)</span></label>
                <textarea id="notes" name="notes" rows="3" maxlength="2000" placeholder="Catatan penjualan, bila ada" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">{{ old('notes', $draftData['notes'] ?? '') }}</textarea>
                @error('notes')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
            </section>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('sales.index') }}" data-transaction-dialog-cancel class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
                <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">Tinjau penjualan</button>
            </div>
        </form>

        <template id="sale-row-template">
            <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 sm:p-5" data-transaction-row>
                <div class="mb-4 flex items-center justify-between border-b border-slate-200/80 pb-3"><p class="flex items-center gap-2 text-sm font-semibold text-slate-800"><span class="flex size-7 items-center justify-center rounded-lg bg-rose-100 text-xs font-bold text-rose-900">+</span>Produk terjual</p><button type="button" data-remove-transaction-row class="min-h-10 rounded-lg px-3 text-xs font-semibold text-slate-500 transition hover:bg-red-50 hover:text-red-700">Hapus</button></div>
                <div class="grid gap-4 sm:grid-cols-2 sm:items-end lg:grid-cols-[minmax(0,2fr)_minmax(9rem,1fr)_minmax(10rem,1fr)_minmax(11rem,1fr)]">
                <div>
                    <label for="product-__INDEX__" class="mb-1.5 block text-sm font-medium">Produk</label>
                    <select id="product-__INDEX__" name="items[__INDEX__][product_id]" required class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
                        <option value="">Pilih produk</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} · {{ $product->sku }} · stok {{ \App\Support\QuantityFormatter::format($product->current_stock) }} {{ $product->unit }} · {{ \App\Support\RupiahFormatter::format($product->selling_price) }}/{{ $product->unit }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label for="quantity-__INDEX__" class="mb-1.5 block text-sm font-medium">Kuantitas</label><input id="quantity-__INDEX__" name="items[__INDEX__][quantity]" type="text" inputmode="decimal" required placeholder="0.000" class="min-h-11 w-full rounded-lg border border-slate-300 px-3 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15"></div>
                @if ($owner)
                    <div><label for="selling-price-__INDEX__" class="mb-1.5 block text-sm font-medium">Override harga jual <span class="font-normal text-slate-500">(opsional)</span></label><input id="selling-price-__INDEX__" name="items[__INDEX__][selling_price_override]" type="text" inputmode="numeric" placeholder="Pakai harga default" class="min-h-11 w-full rounded-lg border border-slate-300 px-3 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15"></div>
                    <div><label for="override-reason-__INDEX__" class="mb-1.5 block text-sm font-medium">Alasan override</label><input id="override-reason-__INDEX__" name="items[__INDEX__][override_reason]" maxlength="1000" placeholder="Wajib jika harga diubah" class="min-h-11 w-full rounded-lg border border-slate-300 px-3 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15"></div>
                @endif
                </div>
            </div>
        </template>
    @endif
</div>
@endsection
