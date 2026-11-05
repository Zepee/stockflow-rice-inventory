@extends('layouts.app')

@section('title', 'Ubah produk')

@section('content')
<div class="mb-6">
    <a href="{{ route('products.show', $product) }}" class="text-sm font-medium text-emerald-800 hover:text-emerald-950">← Kembali ke produk</a>
    <h1 class="mt-3 text-2xl font-semibold tracking-tight">Ubah produk</h1>
    <p class="mt-2 text-sm text-slate-600">Perubahan harga jual akan masuk ke riwayat audit.</p>
</div>
@include('products._form', ['product' => $product])
@endsection
