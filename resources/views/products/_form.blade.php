@php($editing = isset($product))
<form method="POST" action="{{ $editing ? route('products.update', $product) : route('products.store') }}" class="space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif

    <section class="grid gap-5 rounded-xl border border-slate-200 bg-white p-5 sm:grid-cols-2 sm:p-6">
        <div class="sm:col-span-2">
            <h2 class="text-base font-semibold">Informasi produk</h2>
            <p class="mt-1 text-sm text-slate-600">Gunakan satuan dasar yang konsisten untuk setiap produk.</p>
        </div>

        <div>
            <label for="name" class="mb-1.5 block text-sm font-medium">Nama produk</label>
            <input id="name" name="name" value="{{ old('name', $product->name ?? '') }}" required maxlength="255" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
            @error('name')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="sku" class="mb-1.5 block text-sm font-medium">SKU unik</label>
            <input id="sku" name="sku" value="{{ old('sku', $product->sku ?? '') }}" required maxlength="64" autocomplete="off" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-base uppercase outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
            @error('sku')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="unit" class="mb-1.5 block text-sm font-medium">Satuan dasar</label>
            <input id="unit" name="unit" value="{{ old('unit', $product->unit ?? 'kg') }}" required maxlength="16" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
            <p class="mt-1.5 text-xs text-slate-500">Contoh: kg. Kuantitas mendukung hingga 3 angka desimal.</p>
            @error('unit')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="minimum_stock" class="mb-1.5 block text-sm font-medium">Batas stok minimum</label>
            <input id="minimum_stock" name="minimum_stock" type="text" inputmode="decimal" value="{{ old('minimum_stock', $product->minimum_stock ?? '0.000') }}" required placeholder="0.000" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
            @error('minimum_stock')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-2">
            <label for="description" class="mb-1.5 block text-sm font-medium">Deskripsi <span class="font-normal text-slate-500">(opsional)</span></label>
            <textarea id="description" name="description" rows="3" maxlength="4000" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">{{ old('description', $product->description ?? '') }}</textarea>
            @error('description')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
    </section>

    <section class="grid gap-5 rounded-xl border border-slate-200 bg-white p-5 sm:grid-cols-2 sm:p-6">
        <div class="sm:col-span-2">
            <h2 class="text-base font-semibold">Harga</h2>
            <p class="mt-1 text-sm text-slate-600">Masukkan angka rupiah utuh tanpa titik atau koma.</p>
        </div>
        <div>
            <label for="purchase_price" class="mb-1.5 block text-sm font-medium">Harga beli per satuan</label>
            <input id="purchase_price" name="purchase_price" type="text" inputmode="numeric" value="{{ old('purchase_price', $product->purchase_price ?? '') }}" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
            @error('purchase_price')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="selling_price" class="mb-1.5 block text-sm font-medium">Harga jual per satuan</label>
            <input id="selling_price" name="selling_price" type="text" inputmode="numeric" value="{{ old('selling_price', $product->selling_price ?? '') }}" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
            @error('selling_price')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        @if ($editing)
            <div class="sm:col-span-2 rounded-lg bg-slate-50 p-4">
                <p class="text-sm font-medium text-slate-800">Saldo stok saat ini</p>
                <p class="mt-1 text-sm text-slate-600">{{ \App\Support\QuantityFormatter::format($product->current_stock) }} {{ $product->unit }}. Saldo stok hanya berubah melalui transaksi inventaris.</p>
            </div>
        @else
            <p class="sm:col-span-2 text-sm text-slate-600">Stok awal produk dibuat nol dan nantinya dimasukkan melalui transaksi penyesuaian dengan alasan.</p>
        @endif
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
        <input type="hidden" name="is_active" value="0">
        <label class="flex min-h-11 cursor-pointer items-center gap-3">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true)) class="size-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-700">
            <span class="text-sm font-medium text-slate-800">Produk aktif</span>
        </label>
        @error('is_active')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </section>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ $editing ? route('products.show', $product) : route('products.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">{{ $editing ? 'Simpan perubahan' : 'Simpan produk' }}</button>
    </div>
</form>
