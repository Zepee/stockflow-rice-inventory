@extends('layouts.app')

@section('title', 'Pemasok')

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div><p class="text-sm font-medium text-emerald-800">Data master</p><h1 class="mt-1 text-2xl font-semibold tracking-tight">Pemasok</h1><p class="mt-2 text-sm text-slate-600">Kelola pemasok dan informasi kontaknya.</p></div>
    <a href="{{ route('suppliers.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">Tambah pemasok</a>
</div>
<form method="GET" action="{{ route('suppliers.index') }}" class="mt-6 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-end">
    <div class="flex-1"><label for="q" class="mb-1.5 block text-sm font-medium">Cari pemasok</label><input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Nama pemasok" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15"></div>
    <button class="min-h-11 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cari</button>
</form>
@if ($suppliers->isEmpty())
    <div class="mt-5 rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center"><h2 class="text-base font-semibold">Belum ada pemasok</h2><p class="mt-2 text-sm text-slate-600">Pemasok akan tercatat di sini setelah ditambahkan.</p></div>
@else
    <div class="mt-5 hidden overflow-hidden rounded-xl border border-slate-200 bg-white sm:block"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3 font-medium">Nama</th><th class="px-4 py-3 font-medium">Telepon</th><th class="px-4 py-3"><span class="sr-only">Aksi</span></th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($suppliers as $supplier)<tr><td class="px-4 py-3.5 font-medium"><a href="{{ route('suppliers.show', $supplier) }}" class="text-slate-900 hover:text-emerald-800">{{ $supplier->name }}</a></td><td class="px-4 py-3.5 text-slate-600">{{ $supplier->phone }}</td><td class="px-4 py-3.5 text-right"><a href="{{ route('suppliers.show', $supplier) }}" class="font-medium text-emerald-800">Lihat</a></td></tr>@endforeach</tbody></table></div></div>
    <div class="mt-5 space-y-3 sm:hidden">@foreach ($suppliers as $supplier)<a href="{{ route('suppliers.show', $supplier) }}" class="block rounded-xl border border-slate-200 bg-white p-4"><h2 class="font-semibold">{{ $supplier->name }}</h2><p class="mt-2 text-sm text-slate-600">{{ $supplier->phone }}</p></a>@endforeach</div>
    <div class="mt-5">{{ $suppliers->links() }}</div>
@endif
@endsection
