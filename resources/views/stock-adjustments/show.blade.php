@extends('layouts.app')

@section('title', 'Penyesuaian '.$adjustment->transaction_number)

@section('content')
<div class="max-w-4xl">
    <a href="{{ route('inventory-movements.index') }}" class="text-sm font-medium text-emerald-800 hover:text-emerald-950">← Riwayat stok</a>
    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm font-medium text-emerald-800">Penyesuaian stok</p>
            <h1 class="mt-1 break-all text-2xl font-semibold tracking-tight">{{ $adjustment->transaction_number }}</h1>
        </div>
        <span class="w-fit rounded-full px-3 py-1.5 text-sm font-medium {{ $adjustment->status === 'cancelled' ? 'bg-red-50 text-red-800' : 'bg-emerald-50 text-emerald-800' }}">{{ $adjustment->status === 'cancelled' ? 'Dibatalkan' : 'Terkonfirmasi' }}</span>
    </div>

    <section class="mt-6 grid gap-5 rounded-xl border border-slate-200 bg-white p-5 sm:grid-cols-2 sm:p-6">
        <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Tanggal bisnis</p><p class="mt-1.5 text-sm font-medium">{{ $adjustment->business_date->format('d/m/Y') }}</p></div>
        <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Dikonfirmasi</p><p class="mt-1.5 text-sm font-medium">{{ $adjustment->confirmed_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p></div>
        <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Gudang</p><p class="mt-1.5 text-sm font-medium">{{ $adjustment->warehouse->name }}</p></div>
        <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Dicatat oleh</p><p class="mt-1.5 text-sm font-medium">{{ $adjustment->creator->name }}</p></div>
        <div class="sm:col-span-2"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Alasan</p><p class="mt-1.5 whitespace-pre-line text-sm leading-6">{{ $adjustment->reason }}</p></div>
    </section>

    <section class="mt-5 rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
        <h2 class="text-base font-semibold">Rincian perubahan</h2>
        <div class="mt-4 space-y-3">
            @foreach ($adjustment->items as $item)
                <div class="grid gap-3 border-b border-slate-100 pb-3 last:border-0 last:pb-0 sm:grid-cols-[minmax(0,2fr)_1fr_1fr] sm:items-center">
                    <div><p class="font-medium">{{ $item->product->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $item->product->sku }}</p></div>
                    <p class="text-sm text-slate-600">{{ $item->direction === 'increase' ? 'Stok ditambah' : 'Stok dikurangi' }} {{ \App\Support\QuantityFormatter::format($item->quantity) }} {{ $item->unit }}</p>
                    <p class="text-sm text-slate-600">Saldo setelah: <span class="font-medium text-slate-900">{{ \App\Support\QuantityFormatter::format($item->movement->balance_after) }} {{ $item->unit }}</span></p>
                </div>
            @endforeach
        </div>
    </section>

    @if ($adjustment->cancellation)
        <section class="mt-5 rounded-xl border border-red-200 bg-red-50 p-5 sm:p-6">
            <h2 class="text-sm font-semibold text-red-900">Pembatalan {{ $adjustment->cancellation->transaction_number }}</h2>
            <p class="mt-2 whitespace-pre-line text-sm text-red-900">{{ $adjustment->cancellation->reason }}</p>
            <p class="mt-2 text-xs text-red-800">{{ $adjustment->cancellation->authorizer->name }} · {{ $adjustment->cancellation->confirmed_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
        </section>
    @elseif (auth()->user()->isOwner())
        <form method="POST" action="{{ route('transactions.cancel', ['type' => 'adjustment', 'transaction' => $adjustment->id]) }}" class="mt-5 rounded-xl border border-red-200 bg-white p-5 sm:p-6">
            @csrf
            <h2 class="text-sm font-semibold">Batalkan penyesuaian stok</h2>
            <p class="mt-1 text-sm text-slate-600">Pembatalan ditolak jika saldo setelah reversal menjadi negatif.</p>
            <label for="cancellation-reason" class="mt-4 block text-sm font-medium">Alasan pembatalan</label>
            <textarea id="cancellation-reason" name="reason" rows="3" required minlength="3" maxlength="2000" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm focus:border-emerald-700 focus:ring-emerald-700">{{ old('reason') }}</textarea>
            @error('reason')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            @error('transaction')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            <button type="submit" class="mt-4 min-h-11 rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-800 hover:bg-red-50">Konfirmasi pembatalan</button>
        </form>
    @endif
</div>
@endsection
