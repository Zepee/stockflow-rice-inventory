@extends('layouts.app')

@section('title', 'Ubah pelanggan')

@section('content')
<div class="mb-6"><a href="{{ route('customers.show', $customer) }}" class="text-sm font-medium text-emerald-800">← Kembali ke pelanggan</a><h1 class="mt-3 text-2xl font-semibold tracking-tight">Ubah pelanggan</h1></div>
@include('contacts._form', ['entity' => 'customer', 'record' => $customer])
@endsection
