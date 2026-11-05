@php
    $editing = $record !== null;
    $resource = $entity === 'supplier' ? 'suppliers' : 'customers';
@endphp
<form method="POST" action="{{ $editing ? route($resource.'.update', $record) : route($resource.'.store') }}" class="space-y-5">
    @csrf
    @if ($editing) @method('PUT') @endif
    <section class="grid gap-5 rounded-xl border border-slate-200 bg-white p-5 sm:grid-cols-2 sm:p-6">
        <div class="sm:col-span-2">
            <label for="name" class="mb-1.5 block text-sm font-medium">Nama {{ $entity === 'supplier' ? 'pemasok' : 'pelanggan' }}</label>
            <input id="name" name="name" value="{{ old('name', $record->name ?? '') }}" required maxlength="255" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
            @error('name')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label for="phone" class="mb-1.5 block text-sm font-medium">Nomor telepon</label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone', $record->phone ?? '') }}" required maxlength="40" autocomplete="tel" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">
            @error('phone')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label for="address" class="mb-1.5 block text-sm font-medium">Alamat</label>
            <textarea id="address" name="address" rows="4" required maxlength="2000" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-base outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15">{{ old('address', $record->address ?? '') }}</textarea>
            @error('address')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
    </section>
    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route($resource.'.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-white">Batal</a>
        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">{{ $editing ? 'Simpan perubahan' : 'Simpan' }}</button>
    </div>
</form>
