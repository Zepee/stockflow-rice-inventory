@extends('layouts.app')

@section('title', 'Penjualan '.$sale->transaction_number)

@section('content')
<div class="max-w-4xl">
    <a href="{{ route('sales.index') }}" class="text-sm font-medium text-emerald-800 hover:text-emerald-950">← Riwayat penjualan</a>
    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-sm font-medium text-emerald-800">Barang keluar</p><h1 class="mt-1 break-all text-2xl font-semibold tracking-tight">{{ $sale->transaction_number }}</h1></div><span class="w-fit rounded-full px-3 py-1.5 text-sm font-medium {{ $sale->status === 'cancelled' ? 'bg-red-50 text-red-800' : 'bg-emerald-50 text-emerald-800' }}">{{ $sale->status === 'cancelled' ? 'Dibatalkan' : 'Terkonfirmasi' }}</span></div>

    <section class="mt-6 grid gap-5 rounded-xl border border-slate-200 bg-white p-5 sm:grid-cols-2 sm:p-6">
        <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Pelanggan</p><p class="mt-1.5 font-medium">{{ $sale->customer->name }}</p><p class="mt-1 text-sm text-slate-600">{{ $sale->customer->phone }}</p></div>
        <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Tanggal bisnis</p><p class="mt-1.5 font-medium">{{ $sale->business_date->format('d/m/Y') }}</p></div>
        <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Dikonfirmasi</p><p class="mt-1.5 font-medium">{{ $sale->confirmed_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p></div>
        <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Dicatat oleh</p><p class="mt-1.5 font-medium">{{ $sale->creator->name }}</p></div>
    </section>

    @if ($sale->notes)<section class="mt-5 rounded-xl border border-slate-200 bg-white p-5 sm:p-6"><h2 class="text-sm font-semibold">Catatan</h2><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $sale->notes }}</p></section>@endif

    <section class="mt-5 rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
        <h2 class="text-base font-semibold">Rincian penjualan</h2>
        <div class="mt-4 space-y-4">
            @foreach ($sale->items as $item)
                <article class="border-b border-slate-100 pb-4 last:border-0 last:pb-0">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"><div><p class="font-medium">{{ $item->product->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $item->product->sku }} · {{ \App\Support\QuantityFormatter::format($item->quantity) }} {{ $item->unit }}</p></div><p class="text-sm font-semibold">{{ \App\Support\RupiahFormatter::format($item->line_subtotal) }}</p></div>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs text-slate-500">Harga jual per satuan</dt><dd class="mt-1">{{ \App\Support\RupiahFormatter::format($item->selling_price) }} / {{ $item->unit }}</dd></div>
                        @if ($owner)
                            <div><dt class="text-xs text-slate-500">Harga default saat transaksi</dt><dd class="mt-1">{{ \App\Support\RupiahFormatter::format($item->configured_selling_price) }} / {{ $item->unit }}</dd></div>
                            @if ($item->override_reason)<div class="sm:col-span-2"><dt class="text-xs text-slate-500">Alasan override</dt><dd class="mt-1">{{ $item->override_reason }}</dd></div>@endif
                        @endif
                    </dl>
                </article>
            @endforeach
        </div>
        <div class="mt-5 flex items-center justify-between border-t border-slate-200 pt-4"><span class="text-sm font-semibold">Total penjualan</span><span class="text-lg font-semibold">{{ \App\Support\RupiahFormatter::format($sale->total_value) }}</span></div>
    </section>

    @if ($sale->cancellation)
        <section class="mt-5 rounded-xl border border-red-200 bg-red-50 p-5 sm:p-6">
            <h2 class="text-sm font-semibold text-red-900">Pembatalan {{ $sale->cancellation->transaction_number }}</h2>
            <p class="mt-2 whitespace-pre-line text-sm text-red-900">{{ $sale->cancellation->reason }}</p>
            <p class="mt-2 text-xs text-red-800">{{ $sale->cancellation->authorizer->name }} · {{ $sale->cancellation->confirmed_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
        </section>
    @elseif ($owner)
        <form method="POST" action="{{ route('transactions.cancel', ['type' => 'sale', 'transaction' => $sale->id]) }}" class="mt-5 rounded-xl border border-red-200 bg-white p-5 sm:p-6">
            @csrf
            <h2 class="text-sm font-semibold">Batalkan penjualan</h2>
            <label for="cancellation-reason" class="mt-4 block text-sm font-medium">Alasan pembatalan</label>
            <textarea id="cancellation-reason" name="reason" rows="3" required minlength="3" maxlength="2000" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm focus:border-emerald-700 focus:ring-emerald-700">{{ old('reason') }}</textarea>
            @error('reason')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            @error('transaction')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            <button type="submit" class="mt-4 min-h-11 rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-800 hover:bg-red-50">Konfirmasi pembatalan</button>
        </form>
    @endif
</div>
@endsection
