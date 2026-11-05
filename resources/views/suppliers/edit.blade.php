@extends('layouts.app')

@section('title', 'Ubah pemasok')

@section('content')
<div class="mb-6"><a href="{{ route('suppliers.show', $supplier) }}" class="text-sm font-medium text-emerald-800">← Kembali ke pemasok</a><h1 class="mt-3 text-2xl font-semibold tracking-tight">Ubah pemasok</h1></div>
@include('contacts._form', ['entity' => 'supplier', 'record' => $supplier])
@endsection
