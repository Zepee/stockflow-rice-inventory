@extends('layouts.app')

@section('title', 'Dasbor')

@section('content')
<div class="space-y-7">
    <section class="flex flex-col gap-5 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/[0.025] sm:flex-row sm:items-center sm:justify-between sm:p-7">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-800">Ringkasan operasional</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 sm:text-[1.75rem]">Selamat datang, {{ auth()->user()->name }}</h1>
            <p class="mt-2 text-sm text-slate-600">Pantau kondisi stok dan transaksi gudang Anda.</p>
            <p class="mt-3 inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600"><span class="size-1.5 rounded-full bg-emerald-600"></span>Tanggal bisnis {{ \Illuminate\Support\Carbon::parse($today)->format('d/m/Y') }} · Asia/Jakarta</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('purchases.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-800 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Catat stok masuk</a>
            <a href="{{ route('sales.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Catat stok keluar</a>
        </div>
    </section>

    <section aria-label="Statistik operasional" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        <article class="relative overflow-hidden rounded-xl border border-emerald-950 bg-[#17372d] p-5 text-white shadow-sm shadow-emerald-950/10">
            <span class="absolute right-0 top-0 h-20 w-20 translate-x-7 -translate-y-7 rounded-full border-[12px] border-emerald-300/10" aria-hidden="true"></span>
            <p class="text-sm font-medium text-emerald-100/75">Jumlah stok saat ini</p><p class="mt-3 text-[1.8rem] font-semibold tracking-tight tabular-nums">{{ \App\Support\QuantityFormatter::format($metrics['total_stock']) }} <span class="text-base font-medium text-emerald-100/70">kg</span></p><p class="mt-1 text-xs text-emerald-100/60">Saldo seluruh produk di gudang</p>
        </article>
        <article class="rounded-xl border border-slate-200/90 bg-white p-5 shadow-sm shadow-slate-900/[0.025]"><p class="text-sm font-medium text-slate-600">Produk aktif</p><p class="mt-3 text-[1.8rem] font-semibold tracking-tight tabular-nums text-slate-950">{{ number_format($metrics['product_count'], 0, ',', '.') }}</p><p class="mt-1 text-xs text-slate-500">Produk yang dapat dipakai dalam transaksi</p></article>
        <article class="rounded-xl border border-amber-200 bg-amber-50/80 p-5 shadow-sm shadow-amber-900/[0.025]"><p class="text-sm font-medium text-amber-900">Perlu diperiksa</p><p class="mt-3 text-[1.8rem] font-semibold tracking-tight tabular-nums text-amber-950">{{ number_format($metrics['low_stock_count'], 0, ',', '.') }}</p><p class="mt-1 text-xs text-amber-800/75">Produk pada atau di bawah stok minimum</p></article>
        <article class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-5 shadow-sm shadow-emerald-900/[0.025]"><p class="text-sm font-medium text-emerald-900">Stok masuk hari ini</p><p class="mt-3 text-[1.8rem] font-semibold tracking-tight tabular-nums text-emerald-950">{{ \App\Support\QuantityFormatter::format($metrics['incoming']) }} <span class="text-base font-medium text-emerald-800/70">kg</span></p><p class="mt-1 text-xs text-emerald-800/75">Pembelian dan penyesuaian masuk aktif</p></article>
        <article class="rounded-xl border border-rose-200 bg-rose-50/70 p-5 shadow-sm shadow-rose-900/[0.025]"><p class="text-sm font-medium text-rose-900">Stok keluar hari ini</p><p class="mt-3 text-[1.8rem] font-semibold tracking-tight tabular-nums text-rose-950">{{ \App\Support\QuantityFormatter::format($metrics['outgoing']) }} <span class="text-base font-medium text-rose-800/70">kg</span></p><p class="mt-1 text-xs text-rose-800/75">Penjualan dan transaksi keluar aktif</p></article>
        <article class="rounded-xl border border-violet-200 bg-violet-50/70 p-5 shadow-sm shadow-violet-900/[0.025]"><p class="text-sm font-medium text-violet-900">Dampak pembatalan hari ini</p><p class="mt-3 text-lg font-semibold tracking-tight tabular-nums text-violet-950"><span class="text-emerald-800">+{{ \App\Support\QuantityFormatter::format($metrics['reversal_incoming']) }}</span><span class="mx-1 text-violet-400">·</span><span class="text-rose-800">−{{ \App\Support\QuantityFormatter::format($metrics['reversal_outgoing']) }}</span> <span class="text-sm font-medium text-violet-800/70">kg</span></p><p class="mt-1 text-xs text-violet-800/75">Perubahan reversal ditampilkan terpisah</p></article>
        @if (auth()->user()->isOwner())
            <article class="rounded-xl border border-slate-200/90 bg-white p-5 shadow-sm shadow-slate-900/[0.025]"><p class="text-sm font-medium text-slate-600">Nilai pembelian hari ini</p><p class="mt-3 text-xl font-semibold tracking-tight tabular-nums text-slate-950">{{ \App\Support\RupiahFormatter::format($metrics['purchase_value']) }}</p><p class="mt-1 text-xs text-slate-500">Transaksi aktif menurut tanggal bisnis</p></article>
            <article class="rounded-xl border border-slate-200/90 bg-white p-5 shadow-sm shadow-slate-900/[0.025]"><p class="text-sm font-medium text-slate-600">Nilai penjualan hari ini</p><p class="mt-3 text-xl font-semibold tracking-tight tabular-nums text-slate-950">{{ \App\Support\RupiahFormatter::format($metrics['sales_value']) }}</p><p class="mt-1 text-xs text-slate-500">Transaksi aktif menurut tanggal bisnis</p></article>
        @endif
    </section>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.55fr)_minmax(18rem,0.85fr)]">
        <section class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm shadow-slate-900/[0.025]" aria-labelledby="recent-heading">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><h2 id="recent-heading" class="font-semibold text-slate-950">Transaksi terbaru</h2><p class="mt-1 text-xs text-slate-500">Pembelian, penjualan, penyesuaian, dan pembatalan</p></div><a href="{{ route('reports.index') }}" class="rounded-md px-2 py-1 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50">Lihat laporan</a></div>
            @forelse ($recentTransactions as $transaction)
                <a href="{{ $transaction['url'] }}" class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 transition-colors last:border-0 hover:bg-slate-50">
                    <div class="min-w-0"><p class="truncate text-sm font-medium">{{ $transaction['number'] }}</p><p class="mt-1 truncate text-xs text-slate-500">{{ $transaction['type'] }}@if ($transaction['partner']) · {{ $transaction['partner'] }}@endif · {{ \Illuminate\Support\Carbon::parse($transaction['business_date'])->format('d/m/Y') }}</p></div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $transaction['status'] === 'cancelled' ? 'bg-red-50 text-red-800' : 'bg-emerald-50 text-emerald-800' }}">{{ $transaction['status'] === 'cancelled' ? 'Dibatalkan' : 'Terkonfirmasi' }}</span>
                </a>
            @empty
                <p class="px-5 py-8 text-sm text-slate-600">Belum ada pergerakan stok.</p>
            @endforelse
        </section>

        <section class="rounded-xl border border-slate-200/90 bg-white shadow-sm shadow-slate-900/[0.025]" aria-labelledby="low-stock-heading">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4"><div><h2 id="low-stock-heading" class="font-semibold text-slate-950">Peringatan stok minimum</h2><p class="mt-1 text-xs text-slate-500">Produk aktif yang perlu diperiksa</p></div><span class="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-900">{{ number_format($metrics['low_stock_count'], 0, ',', '.') }} produk</span></div>
            @forelse ($lowStockProducts as $product)
                <a href="{{ route('products.show', $product) }}" class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-3.5 last:border-0 hover:bg-amber-50/50"><span class="min-w-0"><span class="block truncate text-sm font-medium">{{ $product->name }}</span><span class="mt-1 block text-xs text-slate-500">{{ $product->sku }} · minimum {{ \App\Support\QuantityFormatter::format($product->minimum_stock) }} {{ $product->unit }}</span><span class="mt-2 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-900">Stok rendah</span></span><span class="shrink-0 text-right"><span class="block text-sm font-semibold tabular-nums text-amber-900">{{ \App\Support\QuantityFormatter::format($product->current_stock) }} {{ $product->unit }}</span><span class="mt-2 block text-xs font-medium text-emerald-800">Lihat produk →</span></span></a>
            @empty
                <p class="px-5 py-8 text-sm text-slate-600">Tidak ada produk aktif yang berada di bawah batas stok minimum.</p>
            @endforelse
            @if ($metrics['low_stock_count'] > 0)
                <div class="border-t border-slate-100 p-4"><a href="{{ route('purchases.create') }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-emerald-800 px-4 text-sm font-semibold text-white transition hover:bg-emerald-900">Catat stok masuk</a></div>
            @endif
        </section>
    </div>
</div>
@endsection
