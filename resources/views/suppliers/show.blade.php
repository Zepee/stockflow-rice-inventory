@extends('layouts.app')

@section('title', $supplier->name)

@section('content')
<div class="mb-6"><a href="{{ route('suppliers.index') }}" class="text-sm font-medium text-emerald-800">← Kembali ke pemasok</a><div class="mt-3 flex flex-wrap items-center justify-between gap-3"><h1 class="text-2xl font-semibold tracking-tight">{{ $supplier->name }}</h1><a href="{{ route('suppliers.edit', $supplier) }}" class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-white">Ubah</a></div></div>
<div class="grid gap-5 lg:grid-cols-2"><section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6"><h2 class="font-semibold">Informasi kontak</h2><dl class="mt-4 space-y-4"><div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Telepon</dt><dd class="mt-1 text-sm">{{ $supplier->phone }}</dd></div><div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Alamat</dt><dd class="mt-1 whitespace-pre-line text-sm leading-6">{{ $supplier->address }}</dd></div></dl></section><section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6"><h2 class="font-semibold">Riwayat transaksi</h2><p class="mt-3 text-sm leading-6 text-slate-600">Riwayat pembelian akan tampil setelah modul stok masuk tersedia.</p></section></div>
@endsection
