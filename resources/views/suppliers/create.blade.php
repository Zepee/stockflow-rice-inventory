@extends('layouts.app')

@section('title', 'Tambah pemasok')

@section('content')
<div class="mb-6"><a href="{{ route('suppliers.index') }}" class="text-sm font-medium text-emerald-800">← Kembali ke pemasok</a><h1 class="mt-3 text-2xl font-semibold tracking-tight">Tambah pemasok</h1><p class="mt-2 text-sm text-slate-600">Informasi ini digunakan saat mencatat stok masuk.</p></div>
@include('contacts._form', ['entity' => 'supplier', 'record' => null])
@endsection
