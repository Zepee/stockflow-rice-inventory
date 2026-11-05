@extends('layouts.app')

@section('title', 'Pembelian')

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm font-medium text-emerald-800">Barang masuk</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">Pembelian</h1>
        <p class="mt-2 text-sm text-slate-600">Catat penerimaan barang dan tinjau riwayat transaksi pembelian.</p>
    </div>
    <a href="{{ route('purchases.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">Catat barang masuk</a>
</div>

<form method="GET" action="{{ route('purchases.index') }}" class="mt-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
    <div><label for="q" class="mb-1.5 block text-sm font-medium">Cari pembelian</label><input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Nomor transaksi atau pemasok" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15"></div>
    <button type="submit" class="min-h-11 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cari</button>
</form>

@if ($purchases->isEmpty())
    <div class="mt-5 rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center">
        <h2 class="text-base font-semibold text-slate-900">Belum ada pembelian</h2>
        <p class="mt-2 text-sm text-slate-600">Penerimaan barang yang dikonfirmasi akan tampil di sini.</p>
    </div>
@else
    <div class="mt-5 space-y-3 sm:hidden">
        @foreach ($purchases as $purchase)
            <a href="{{ route('purchases.show', $purchase) }}" class="block rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="break-all font-semibold">{{ $purchase->transaction_number }}</p><p class="mt-1 text-sm text-slate-600">{{ $purchase->supplier->name }}</p></div><span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $purchase->status === 'cancelled' ? 'bg-red-50 text-red-800' : 'bg-emerald-50 text-emerald-800' }}">{{ $purchase->status === 'cancelled' ? 'Dibatalkan' : 'Terkonfirmasi' }}</span></div>
                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm"><div><dt class="text-xs text-slate-500">Tanggal bisnis</dt><dd class="mt-1">{{ $purchase->business_date->format('d/m/Y') }}</dd></div><div><dt class="text-xs text-slate-500">Dicatat oleh</dt><dd class="mt-1">{{ $purchase->creator->name }}</dd></div>@if ($owner)<div class="col-span-2"><dt class="text-xs text-slate-500">Total pembelian</dt><dd class="mt-1 font-semibold">{{ \App\Support\RupiahFormatter::format($purchase->total_value) }}</dd></div>@endif</dl>
            </a>
        @endforeach
    </div>
    <div class="mt-5 hidden overflow-hidden rounded-xl border border-slate-200 bg-white sm:block">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3 font-medium">Nomor transaksi</th><th class="px-4 py-3 font-medium">Tanggal bisnis</th><th class="px-4 py-3 font-medium">Pemasok</th><th class="px-4 py-3 font-medium">Dicatat oleh</th>@if ($owner)<th class="px-4 py-3 font-medium">Total</th>@endif<th class="px-4 py-3 font-medium">Status</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($purchases as $purchase)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3.5"><a href="{{ route('purchases.show', $purchase) }}" class="font-medium text-emerald-800 hover:text-emerald-950">{{ $purchase->transaction_number }}</a></td>
                            <td class="whitespace-nowrap px-4 py-3.5">{{ $purchase->business_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3.5">{{ $purchase->supplier->name }}</td>
                            <td class="px-4 py-3.5">{{ $purchase->creator->name }}</td>
                            @if ($owner)<td class="whitespace-nowrap px-4 py-3.5">{{ \App\Support\RupiahFormatter::format($purchase->total_value) }}</td>@endif
                            <td class="px-4 py-3.5"><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $purchase->status === 'cancelled' ? 'bg-red-50 text-red-800' : 'bg-emerald-50 text-emerald-800' }}">{{ $purchase->status === 'cancelled' ? 'Dibatalkan' : 'Terkonfirmasi' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $purchases->links() }}</div>
@endif
@endsection
