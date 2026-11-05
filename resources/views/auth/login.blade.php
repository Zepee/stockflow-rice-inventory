@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
<main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6">
    <section class="w-full max-w-md">
        <a href="{{ route('login') }}" class="mb-8 inline-flex items-center gap-3" aria-label="StockFlow">
            <span class="flex size-10 items-center justify-center rounded-lg bg-slate-900 text-white" aria-hidden="true"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V3M12 7c-3-3-6-2-7-1 1 3 3 4 7 4m0 2c3-3 6-2 7-1-1 3-3 4-7 4m0 2c-3-3-6-2-7-1 1 3 3 4 7 4m0-17c2 2 2 4 1 6"/></svg></span>
            <span class="text-xl font-semibold tracking-tight">StockFlow</span>
        </a>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="mb-7">
                <h1 class="text-2xl font-semibold tracking-tight">Masuk ke akun Anda</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">Gunakan akun StockFlow yang telah dibuat oleh pemilik usaha.</p>
            </div>

            <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Alamat email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        inputmode="email"
                        aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                        aria-describedby="email-error"
                        class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-base outline-none transition focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15"
                    >
                    @error('email')
                        <p id="email-error" class="mt-2 text-sm text-red-700" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Kata sandi</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                        aria-describedby="password-error"
                        class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-base outline-none transition focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15"
                    >
                    @error('password')
                        <p id="password-error" class="mt-2 text-sm text-red-700" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="min-h-11 w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                    Masuk
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs leading-5 text-slate-500">Akses aplikasi hanya untuk pengguna yang telah terdaftar.</p>
    </section>
</main>
@endsection
