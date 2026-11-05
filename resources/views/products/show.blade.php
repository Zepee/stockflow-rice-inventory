@extends('layouts.app')

@section('title', $product->name)

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div class="min-w-0">
        <a href="{{ route('products.index') }}" class="text-sm font-medium text-emerald-800 hover:text-emerald-950">← Kembali ke produk</a>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <h1 class="break-words text-2xl font-semibold tracking-tight">{{ $product->name }}</h1>
            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $product->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ $product->is_active ? 'Aktif' : 'Diarsipkan' }}</span>
        </div>
        <p class="mt-2 text-sm text-slate-600">SKU {{ $product->sku }} · satuan dasar {{ $product->unit }}</p>
    </div>
    @if ($owner)
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('products.edit', $product) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-white">Ubah</a>
            @if ($product->is_active)
                <form method="POST" action="{{ route('products.archive', $product) }}" onsubmit="return confirm('Arsipkan produk ini?')">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="min-h-11 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-white">Arsipkan</button>
                </form>
            @endif
        </div>
    @endif
</div>

<div class="mt-7 grid gap-5 lg:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]">
    <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
        <h2 class="text-base font-semibold">Informasi stok</h2>
        <dl class="mt-5 grid gap-5 sm:grid-cols-2">
            <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Saldo saat ini</dt><dd class="mt-1.5 text-xl font-semibold">{{ \App\Support\QuantityFormatter::format($product->current_stock) }} <span class="text-sm font-medium text-slate-500">{{ $product->unit }}</span></dd></div>
            <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Batas stok minimum</dt><dd class="mt-1.5 text-xl font-semibold">{{ \App\Support\QuantityFormatter::format($product->minimum_stock) }} <span class="text-sm font-medium text-slate-500">{{ $product->unit }}</span></dd></div>
        </dl>
        <div class="mt-6 border-t border-slate-100 pt-5">
            <h3 class="text-sm font-semibold">Deskripsi</h3>
            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $product->description ?: 'Belum ada deskripsi.' }}</p>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
        <h2 class="text-base font-semibold">Harga</h2>
        <dl class="mt-5 space-y-4">
            @if ($owner)
                <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Harga beli</dt><dd class="mt-1 text-base font-semibold">{{ \App\Support\RupiahFormatter::format($product->purchase_price) }}</dd></div>
            @endif
            <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Harga jual</dt><dd class="mt-1 text-base font-semibold">{{ \App\Support\RupiahFormatter::format($product->selling_price) }}</dd></div>
            @if ($owner)
                <div class="border-t border-slate-100 pt-4">
                    <h3 class="text-sm font-semibold">Riwayat perubahan harga jual</h3>
                    @if ($product->sellingPriceChanges->isEmpty())
                        <p class="mt-2 text-sm text-slate-500">Belum ada riwayat perubahan.</p>
                    @else
                        <ol class="mt-3 space-y-3">
                            @foreach ($product->sellingPriceChanges as $change)
                                <li class="border-l-2 border-slate-200 pl-3">
                                    <p class="text-sm font-medium text-slate-800">
                                        @if ($change->old_price === null)
                                            Harga awal {{ \App\Support\RupiahFormatter::format($change->new_price) }}
                                        @else
                                            {{ \App\Support\RupiahFormatter::format($change->old_price) }} → {{ \App\Support\RupiahFormatter::format($change->new_price) }}
                                        @endif
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $change->actor->name }} · {{ $change->changed_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            @endif
        </dl>
    </section>
</div>
@endsection
