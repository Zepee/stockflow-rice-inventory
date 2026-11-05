@extends('layouts.app')

@section('title', 'Tambah pelanggan')

@section('content')
<div class="mb-6"><a href="{{ route('customers.index') }}" class="text-sm font-medium text-emerald-800">← Kembali ke pelanggan</a><h1 class="mt-3 text-2xl font-semibold tracking-tight">Tambah pelanggan</h1><p class="mt-2 text-sm text-slate-600">Informasi ini digunakan saat mencatat stok keluar.</p></div>
@include('contacts._form', ['entity' => 'customer', 'record' => null])
@endsection
