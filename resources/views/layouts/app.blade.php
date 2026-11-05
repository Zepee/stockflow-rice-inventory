<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dasbor') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f3f6f4] font-sans text-slate-900 antialiased">
    <div class="min-h-screen md:flex">
        <aside class="hidden w-64 shrink-0 border-r border-emerald-950 bg-[#13251f] text-white md:flex md:flex-col">
            <div class="flex h-[4.5rem] items-center border-b border-white/10 px-6">
                <span class="mr-3 flex size-9 items-center justify-center rounded-xl bg-emerald-400 text-emerald-950 shadow-sm shadow-emerald-950/30" aria-hidden="true"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V3M12 7c-3-3-6-2-7-1 1 3 3 4 7 4m0 2c3-3 6-2 7-1-1 3-3 4-7 4m0 2c-3-3-6-2-7-1 1 3 3 4 7 4m0-17c2 2 2 4 1 6"/></svg></span>
                <span class="font-semibold tracking-tight">StockFlow</span>
            </div>
            <nav class="flex-1 space-y-1 px-3 py-6" aria-label="Navigasi utama">
                <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-100/45">Menu utama</p>
                <a href="{{ route('dashboard') }}" @class(['flex min-h-11 items-center rounded-lg border-l-2 px-3 text-sm font-medium transition-colors', 'border-emerald-300 bg-white/10 text-white' => request()->routeIs('dashboard'), 'border-transparent text-emerald-50/70 hover:bg-white/5 hover:text-white' => ! request()->routeIs('dashboard')])>Dasbor</a>
                <a href="{{ route('products.index') }}" @class(['flex min-h-11 items-center rounded-lg border-l-2 px-3 text-sm font-medium transition-colors', 'border-emerald-300 bg-white/10 text-white' => request()->routeIs('products.*'), 'border-transparent text-emerald-50/70 hover:bg-white/5 hover:text-white' => ! request()->routeIs('products.*')])>Produk</a>
                <a href="{{ route('purchases.index') }}" @class(['flex min-h-11 items-center rounded-lg border-l-2 px-3 text-sm font-medium transition-colors', 'border-emerald-300 bg-white/10 text-white' => request()->routeIs('purchases.*'), 'border-transparent text-emerald-50/70 hover:bg-white/5 hover:text-white' => ! request()->routeIs('purchases.*')])>Pembelian</a>
                <a href="{{ route('sales.index') }}" @class(['flex min-h-11 items-center rounded-lg border-l-2 px-3 text-sm font-medium transition-colors', 'border-emerald-300 bg-white/10 text-white' => request()->routeIs('sales.*'), 'border-transparent text-emerald-50/70 hover:bg-white/5 hover:text-white' => ! request()->routeIs('sales.*')])>Penjualan</a>
                <a href="{{ route('inventory-movements.index') }}" @class(['flex min-h-11 items-center rounded-lg border-l-2 px-3 text-sm font-medium transition-colors', 'border-emerald-300 bg-white/10 text-white' => request()->routeIs('inventory-movements.*'), 'border-transparent text-emerald-50/70 hover:bg-white/5 hover:text-white' => ! request()->routeIs('inventory-movements.*')])>Riwayat stok</a>
                <a href="{{ route('reports.index') }}" @class(['flex min-h-11 items-center rounded-lg border-l-2 px-3 text-sm font-medium transition-colors', 'border-emerald-300 bg-white/10 text-white' => request()->routeIs('reports.*'), 'border-transparent text-emerald-50/70 hover:bg-white/5 hover:text-white' => ! request()->routeIs('reports.*')])>Laporan</a>
                @if (auth()->user()->isOwner())
                    <p class="px-3 pb-2 pt-7 text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-100/45">Pengelolaan</p>
                    <a href="{{ route('stock-adjustments.create') }}" @class(['flex min-h-11 items-center rounded-lg border-l-2 px-3 text-sm font-medium transition-colors', 'border-emerald-300 bg-white/10 text-white' => request()->routeIs('stock-adjustments.create'), 'border-transparent text-emerald-50/70 hover:bg-white/5 hover:text-white' => ! request()->routeIs('stock-adjustments.create')])>Penyesuaian stok</a>
                    <a href="{{ route('suppliers.index') }}" @class(['flex min-h-11 items-center rounded-lg border-l-2 px-3 text-sm font-medium transition-colors', 'border-emerald-300 bg-white/10 text-white' => request()->routeIs('suppliers.*'), 'border-transparent text-emerald-50/70 hover:bg-white/5 hover:text-white' => ! request()->routeIs('suppliers.*')])>Pemasok</a>
                    <a href="{{ route('customers.index') }}" @class(['flex min-h-11 items-center rounded-lg border-l-2 px-3 text-sm font-medium transition-colors', 'border-emerald-300 bg-white/10 text-white' => request()->routeIs('customers.*'), 'border-transparent text-emerald-50/70 hover:bg-white/5 hover:text-white' => ! request()->routeIs('customers.*')])>Pelanggan</a>
                    <a href="{{ route('owner.index') }}" class="flex min-h-11 items-center rounded-lg border-l-2 border-transparent px-3 text-sm font-medium text-emerald-50/70 transition-colors hover:bg-white/5 hover:text-white">Area Owner</a>
                @endif
            </nav>
            <div class="border-t border-white/10 p-4">
                <p class="truncate text-sm font-medium">{{ auth()->user()->name }}</p>
                <p class="mt-1 text-xs text-emerald-100/55">{{ auth()->user()->role->label() }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="min-h-10 w-full rounded-lg border border-white/15 px-3 text-left text-sm font-medium text-emerald-50/75 transition-colors hover:bg-white/5 hover:text-white focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:ring-offset-2 focus:ring-offset-[#13251f]">Keluar</button>
                </form>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="border-b border-slate-200/80 bg-white/95 px-4 shadow-[0_1px_2px_rgba(15,23,42,0.03)] sm:px-6 md:px-8">
                <div class="flex min-h-16 items-center justify-between">
                    <div class="flex items-center gap-3 md:hidden">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-emerald-900 text-emerald-50" aria-hidden="true"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V3M12 7c-3-3-6-2-7-1 1 3 3 4 7 4m0 2c3-3 6-2 7-1-1 3-3 4-7 4m0 2c-3-3-6-2-7-1 1 3 3 4 7 4m0-17c2 2 2 4 1 6"/></svg></span>
                        <span class="font-semibold tracking-tight">StockFlow</span>
                    </div>
                    <p class="hidden text-sm text-slate-600 md:block">{{ auth()->user()->name }} <span class="mx-1 text-slate-300">·</span> {{ auth()->user()->role->label() }}</p>
                    <p class="hidden text-sm font-medium text-slate-700 md:hidden">{{ auth()->user()->name }}</p>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="min-h-10 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Keluar</button>
                    </form>
                </div>
            </header>

            <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white px-1 pt-1 pb-[calc(env(safe-area-inset-bottom)+0.25rem)] shadow-[0_-8px_24px_rgba(15,23,42,0.08)] md:hidden" aria-label="Navigasi utama">
                <div class="mx-auto grid max-w-xl grid-cols-5 gap-1">
                    <a href="{{ route('dashboard') }}" @class(['flex min-h-14 flex-col items-center justify-center gap-1 rounded-lg px-1 text-[10px] font-semibold transition-colors sm:text-xs', 'bg-emerald-50 text-emerald-900' => request()->routeIs('dashboard'), 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('dashboard')]) @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg><span>Dasbor</span>
                    </a>
                    <a href="{{ route('products.index') }}" @class(['flex min-h-14 flex-col items-center justify-center gap-1 rounded-lg px-1 text-[10px] font-semibold transition-colors sm:text-xs', 'bg-emerald-50 text-emerald-900' => request()->routeIs('products.*'), 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('products.*')]) @if(request()->routeIs('products.*')) aria-current="page" @endif>
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m12 3 9 4.5v9L12 21l-9-4.5v-9zM3.5 7.7 12 12l8.5-4.3M12 12v9m-4.3-15.8 8.7 4.4"/></svg><span>Produk</span>
                    </a>
                    <a href="{{ route('purchases.index') }}" @class(['flex min-h-14 flex-col items-center justify-center gap-1 rounded-lg px-1 text-[10px] font-semibold transition-colors sm:text-xs', 'bg-emerald-50 text-emerald-900' => request()->routeIs('purchases.*'), 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('purchases.*')]) @if(request()->routeIs('purchases.*')) aria-current="page" @endif>
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v3h16v-3"/></svg><span>Pembelian</span>
                    </a>
                    <a href="{{ route('sales.index') }}" @class(['flex min-h-14 flex-col items-center justify-center gap-1 rounded-lg px-1 text-[10px] font-semibold transition-colors sm:text-xs', 'bg-emerald-50 text-emerald-900' => request()->routeIs('sales.*'), 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('sales.*')]) @if(request()->routeIs('sales.*')) aria-current="page" @endif>
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21V9m0 0 4 4m-4-4-4 4M4 7V4h16v3"/></svg><span>Penjualan</span>
                    </a>
                    <details class="group relative">
                        <summary @class(['flex min-h-14 cursor-pointer list-none flex-col items-center justify-center gap-1 rounded-lg px-1 text-[10px] font-semibold transition-colors sm:text-xs', 'bg-emerald-50 text-emerald-900' => request()->routeIs('inventory-movements.*', 'reports.*', 'stock-adjustments.*', 'suppliers.*', 'customers.*', 'owner.*'), 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('inventory-movements.*', 'reports.*', 'stock-adjustments.*', 'suppliers.*', 'customers.*', 'owner.*')])>
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><path stroke-linecap="round" d="M4 5h16M4 19h16"/></svg><span>Lainnya</span>
                        </summary>
                        <div class="absolute bottom-full right-0 mb-2 max-h-[70vh] w-[min(19rem,calc(100vw-1rem))] overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/15">
                            <a href="{{ route('inventory-movements.index') }}" @class(['flex min-h-11 items-center rounded-lg px-3 text-sm font-medium', 'bg-emerald-50 text-emerald-900' => request()->routeIs('inventory-movements.*'), 'text-slate-700 hover:bg-slate-50' => ! request()->routeIs('inventory-movements.*')])>Riwayat stok</a>
                            <a href="{{ route('reports.index') }}" @class(['flex min-h-11 items-center rounded-lg px-3 text-sm font-medium', 'bg-emerald-50 text-emerald-900' => request()->routeIs('reports.*'), 'text-slate-700 hover:bg-slate-50' => ! request()->routeIs('reports.*')])>Laporan</a>
                            @if (auth()->user()->isOwner())
                                <div class="mx-3 my-1 border-t border-slate-100"></div>
                                <p class="px-3 py-2 text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Pengelolaan</p>
                                <a href="{{ route('stock-adjustments.create') }}" @class(['flex min-h-11 items-center rounded-lg px-3 text-sm font-medium', 'bg-emerald-50 text-emerald-900' => request()->routeIs('stock-adjustments.*'), 'text-slate-700 hover:bg-slate-50' => ! request()->routeIs('stock-adjustments.*')])>Penyesuaian stok</a>
                                <a href="{{ route('suppliers.index') }}" @class(['flex min-h-11 items-center rounded-lg px-3 text-sm font-medium', 'bg-emerald-50 text-emerald-900' => request()->routeIs('suppliers.*'), 'text-slate-700 hover:bg-slate-50' => ! request()->routeIs('suppliers.*')])>Pemasok</a>
                                <a href="{{ route('customers.index') }}" @class(['flex min-h-11 items-center rounded-lg px-3 text-sm font-medium', 'bg-emerald-50 text-emerald-900' => request()->routeIs('customers.*'), 'text-slate-700 hover:bg-slate-50' => ! request()->routeIs('customers.*')])>Pelanggan</a>
                                <a href="{{ route('owner.index') }}" @class(['flex min-h-11 items-center rounded-lg px-3 text-sm font-medium', 'bg-emerald-50 text-emerald-900' => request()->routeIs('owner.*'), 'text-slate-700 hover:bg-slate-50' => ! request()->routeIs('owner.*')])>Area Owner</a>
                            @endif
                        </div>
                    </details>
                </div>
            </nav>

            <main class="mx-auto w-full max-w-7xl px-4 py-7 pb-28 sm:px-6 md:px-8 md:py-9 md:pb-9">
                @if (session('status'))
                    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">{{ session('status') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    <dialog id="transaction-dialog" aria-labelledby="transaction-dialog-title" aria-describedby="transaction-dialog-description" class="fixed inset-0 m-0 h-dvh max-h-dvh w-full max-w-none border-0 bg-white p-0 text-slate-900 backdrop:bg-slate-950/55 sm:inset-y-auto sm:left-1/2 sm:top-1/2 sm:right-auto sm:bottom-auto sm:-translate-x-1/2 sm:-translate-y-1/2 sm:h-[min(92dvh,54rem)] sm:max-h-[92dvh] sm:w-[min(56rem,calc(100vw-2rem))] sm:rounded-2xl sm:border sm:border-slate-200 sm:shadow-2xl">
        <div class="flex h-full min-h-0 flex-col">
            <header class="flex shrink-0 items-center justify-between gap-4 border-b border-slate-200 px-4 py-3.5 sm:px-6">
                <div class="min-w-0">
                    <p id="transaction-dialog-step" class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-800">Langkah 1 dari 2 · Isi transaksi</p>
                    <h2 id="transaction-dialog-title" tabindex="-1" class="mt-0.5 truncate text-base font-semibold text-slate-950 sm:text-lg">Catat transaksi</h2>
                    <p id="transaction-dialog-description" class="mt-1 max-w-2xl text-xs leading-5 text-slate-500"></p>
                </div>
                <button type="button" data-transaction-dialog-close aria-label="Tutup dialog transaksi" class="inline-flex size-10 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-700">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </header>
            <div id="transaction-dialog-content" tabindex="0" class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-5 sm:px-6 sm:py-6"></div>
        </div>
    </dialog>
</body>
</html>
