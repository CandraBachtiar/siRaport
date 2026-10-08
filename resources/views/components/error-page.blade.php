@props([
    'code',
    'title',
    'message',
    'actionLabel' => 'Kembali ke Beranda',
    'actionHref' => null,
])

@php
    $dashboardHref = match (auth()->user()?->role) {
        'admin' => route('admin.dashboard'),
        'guru' => route('guru.dashboard'),
        default => route('home'),
    };
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0f2f4f">
    <title>{{ $code }} · {{ $title }} — {{ config('app.name', 'RaporKu') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/raporku.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <x-skip-link />
    <main id="main-content" tabindex="-1" class="grid min-h-screen place-items-center px-5 py-12">
        <section class="w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-7 text-center shadow-xl shadow-slate-900/[0.06] sm:p-10" aria-labelledby="error-title">
            <x-brand :href="route('home')" subtitle="Sistem Rapor Digital" class="mx-auto w-fit" aria-label="RaporKu, kembali ke beranda" />
            <p class="mt-9 text-sm font-extrabold uppercase tracking-[0.2em] text-emerald-700">Kode {{ $code }}</p>
            <h1 id="error-title" class="mt-3 text-3xl font-extrabold tracking-tight text-raporku-navy sm:text-4xl">{{ $title }}</h1>
            <p class="mx-auto mt-4 max-w-md text-sm leading-7 text-slate-600">{{ $message }}</p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ $actionHref ?? $dashboardHref }}" class="rk-button rk-button-primary">{{ $actionLabel }}</a>
                <button type="button" class="rk-button rk-button-secondary" data-history-back data-fallback-url="{{ route('home') }}">Kembali ke Halaman Sebelumnya</button>
            </div>
        </section>
    </main>
</body>
</html>
