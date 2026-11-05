@extends('layouts.app')

@section('title', 'Tinjau pembelian')

@section('content')
<div class="max-w-4xl">
    <section data-transaction-intro>
        <a href="{{ route('purchases.create', $draftKey) }}" data-transaction-dialog-back class="text-sm font-medium text-emerald-800 hover:text-emerald-950">← Kembali ke input</a>
        <p data-transaction-step class="mt-4 text-sm font-semibold text-emerald-800">Langkah 2 dari 2 · Tinjau</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">Konfirmasi barang masuk</h1>
        <p data-transaction-description class="mt-2 text-sm text-slate-600">Periksa rincian sebelum mencatat pembelian dan menambahkan stok.</p>
    </section>

    <section class="mt-6 grid gap-4 rounded-xl border border-slate-200 bg-white p-5 sm:grid-cols-2 sm:p-6">
        <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Pemasok</p><p class="mt-1.5 font-medium">{{ $supplier->name }}</p></div>
        <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Tanggal bisnis</p><p class="mt-1.5 font-medium">{{ \Illuminate\Support\Carbon::parse($businessDate)->format('d/m/Y') }}</p></div>
    </section>

    @if ($notes)
        <section class="mt-5 rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
            <h2 class="text-sm font-semibold">Catatan</h2>
            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $notes }}</p>
        </section>
    @endif

    <section class="mt-5 rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
        <h2 class="text-base font-semibold">Produk diterima</h2>
        <div class="mt-4 space-y-4">
            @foreach ($lines as $line)
                <article class="border-b border-slate-100 pb-4 last:border-0 last:pb-0">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div><p class="font-medium">{{ $line['name'] }}</p><p class="mt-1 text-xs text-slate-500">{{ $line['sku'] }} · {{ $line['quantity'] }} {{ $line['unit'] }}</p></div>
                        @if ($owner)<p class="text-sm font-semibold">{{ \App\Support\RupiahFormatter::format($line['line_subtotal']) }}</p>@endif
                    </div>
                    @if ($owner)
                        <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                            <div><dt class="text-xs text-slate-500">Harga default saat ditinjau</dt><dd class="mt-1">{{ \App\Support\RupiahFormatter::format($line['default_purchase_price']) }} / {{ $line['unit'] }}</dd></div>
                            <div><dt class="text-xs text-slate-500">Harga beli diterapkan</dt><dd class="mt-1">{{ \App\Support\RupiahFormatter::format($line['purchase_price']) }} / {{ $line['unit'] }}</dd></div>
                            @if ($line['override_reason'])<div class="sm:col-span-2"><dt class="text-xs text-slate-500">Alasan override</dt><dd class="mt-1">{{ $line['override_reason'] }}</dd></div>@endif
                        </dl>
                    @endif
                </article>
            @endforeach
        </div>
        @if ($owner)
            <div class="mt-5 flex items-center justify-between border-t border-slate-200 pt-4">
                <span class="text-sm font-semibold">Total pembelian</span>
                <span class="text-lg font-semibold">{{ \App\Support\RupiahFormatter::format($totalValue) }}</span>
            </div>
        @else
            <p class="mt-5 border-t border-slate-200 pt-4 text-sm text-slate-600">Harga beli dan total finansial hanya dapat dilihat Owner. Harga default produk akan diterapkan saat pembelian dikonfirmasi.</p>
        @endif
    </section>

    @if ($errors->has('purchase'))<p class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first('purchase') }}</p>@endif

    <form method="POST" action="{{ route('purchases.confirm', $draftKey) }}" data-transaction-confirm="purchase" data-prevent-double-submit data-submit-label="Memproses pembelian…" class="sticky bottom-2 z-30 mt-5 flex flex-col-reverse gap-2 rounded-xl border border-slate-200 bg-white/95 p-3 shadow-lg shadow-slate-900/10 backdrop-blur sm:static sm:flex-row sm:justify-end sm:border-0 sm:bg-transparent sm:p-0 sm:shadow-none">
        @csrf
        <a href="{{ route('purchases.create', $draftKey) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Ubah rincian</a>
        <button type="submit" data-submit-button class="inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-800 px-4 text-sm font-semibold text-white hover:bg-emerald-900">Konfirmasi pembelian</button>
    </form>
</div>
@endsection
