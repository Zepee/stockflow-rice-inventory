@extends('layouts.app')

@section('title', 'Tambah produk')

@section('content')
<div class="mb-6">
    <a href="{{ route('products.index') }}" class="text-sm font-medium text-emerald-800 hover:text-emerald-950">← Kembali ke produk</a>
    <h1 class="mt-3 text-2xl font-semibold tracking-tight">Tambah produk</h1>
    <p class="mt-2 text-sm text-slate-600">Lengkapi informasi produk dan harga awalnya.</p>
</div>
@include('products._form', ['product' => null])
@endsection
