@props([
    'title' => 'Sistem Rapor Digital',
    'description' => 'RaporKu membantu sekolah mengelola nilai, memantau perkembangan siswa, dan menyusun rapor dalam satu ruang kerja.',
])

@php
    $navigationItems = [
        ['label' => 'Beranda', 'route' => 'home'],
        ['label' => 'Tentang', 'route' => 'tentang'],
        ['label' => 'Panduan', 'route' => 'panduan'],
        ['label' => 'Bantuan', 'route' => 'bantuan'],
    ];
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description }}">
    <meta name="theme-color" content="#0f2f4f">
    <title>{{ $title }} — {{ config('app.name', 'RaporKu') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/raporku.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-50 via-slate-100/70 to-slate-50 text-raporku-ink antialiased">
    <x-skip-link />
    <header class="sticky top-0 z-40 border-b border-slate-300/80 bg-white shadow-[0_4px_20px_rgba(15,47,79,0.07)]">
        <div class="rk-shell flex min-h-16 items-center justify-between gap-4 py-2">
            <x-brand aria-label="RaporKu, kembali ke beranda" />

            <nav class="hidden items-center gap-1.5 md:flex" aria-label="Navigasi utama">
                @foreach ($navigationItems as $item)
                    <a href="{{ route($item['route']).(isset($item['fragment']) ? '#'.$item['fragment'] : '') }}" @class([
                        'relative rounded-lg px-3 py-2 text-sm font-semibold transition duration-200 hover:bg-slate-100 hover:text-raporku-navy focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/30',
                        'bg-emerald-50 text-emerald-700 after:absolute after:inset-x-3 after:-bottom-px after:h-0.5 after:rounded-full after:bg-emerald-600' => request()->routeIs($item['route']),
                        'text-slate-600' => ! request()->routeIs($item['route']),
                    ]) @if (request()->routeIs($item['route'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                <a href="{{ route('login') }}" class="rk-button rk-button-primary hidden sm:inline-flex">Masuk
                    <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h11m-4-4 4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
                <button type="button" class="grid size-11 place-items-center rounded-xl border border-slate-200 bg-white text-slate-700 md:hidden" aria-label="Buka menu utama" aria-expanded="false" aria-controls="mobile-navigation" data-public-menu-toggle>
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
            </div>
        </div>

        <nav id="mobile-navigation" class="hidden border-t border-slate-200 bg-white px-4 py-3 md:hidden" aria-label="Navigasi utama seluler" data-public-menu>
            <div class="mx-auto grid max-w-xl gap-1">
                @foreach ($navigationItems as $item)
                    <a href="{{ route($item['route']).(isset($item['fragment']) ? '#'.$item['fragment'] : '') }}" @class([
                        'rounded-lg px-4 py-2.5 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/30',
                        'bg-emerald-50 text-emerald-700' => request()->routeIs($item['route']),
                        'text-slate-700 hover:bg-slate-100 hover:text-raporku-navy' => ! request()->routeIs($item['route']),
                    ]) @if (request()->routeIs($item['route'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
                <a href="{{ route('login') }}" class="rk-button rk-button-primary mt-2 sm:hidden">Masuk</a>
            </div>
        </nav>
    </header>

    <main id="main-content" tabindex="-1">{{ $slot }}</main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="rk-shell grid gap-10 py-12 md:grid-cols-[1.4fr_1fr_1fr]">
            <div>
                <x-brand subtitle="Ruang kerja akademik sekolah" />
                <p class="mt-4 max-w-sm text-sm leading-6 text-slate-500">Kelola nilai, pantau perkembangan siswa, dan siapkan rapor melalui alur kerja yang jelas.</p>
            </div>
            <div>
                <h2 class="text-sm font-bold text-raporku-navy">Jelajahi</h2>
                <div class="mt-4 grid gap-2 text-sm text-slate-500">
                    <a class="hover:text-emerald-700" href="{{ route('tentang') }}">Tentang RaporKu</a>
                    <a class="hover:text-emerald-700" href="{{ route('panduan') }}">Panduan Pengguna</a>
                    <a class="hover:text-emerald-700" href="{{ route('bantuan') }}">Pusat Bantuan</a>
                </div>
            </div>
            <div>
                <h2 class="text-sm font-bold text-raporku-navy">Akses sistem</h2>
                <p class="mt-4 text-sm leading-6 text-slate-500">Gunakan akun yang diberikan administrator sekolah Anda.</p>
                <a class="mt-3 inline-flex text-sm font-bold text-emerald-700 hover:text-emerald-800" href="{{ route('login') }}">Masuk ke RaporKu <span aria-hidden="true">&nbsp;→</span></a>
            </div>
        </div>
        <div class="border-t border-slate-200">
            <div class="rk-shell flex min-h-16 flex-col justify-center gap-1 py-3 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <span>&copy; {{ date('Y') }} RaporKu. Sistem rapor digital sekolah.</span>
                <span>Dibuat untuk mendukung kerja administrator dan guru.</span>
            </div>
        </div>
    </footer>
</body>
</html>
