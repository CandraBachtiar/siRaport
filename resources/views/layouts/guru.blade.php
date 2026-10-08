<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Panel guru RaporKu.">
    <title>@yield('title', 'Guru') — RaporKu</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-[17rem_1fr]">
        <aside class="bg-[#0b2945] px-5 py-5 text-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col lg:px-4 lg:py-6">
            <a href="{{ route('guru.dashboard') }}" class="flex items-center gap-3 px-2" aria-label="RaporKu Guru">
                <span class="grid size-11 place-items-center rounded-xl bg-emerald-500 text-white shadow-lg shadow-emerald-950/20">
                    <svg class="size-6" viewBox="0 0 40 40" aria-hidden="true"><path d="M10 12.5c4.1-1.3 7.4-.8 10 1.4v16c-2.6-2.2-5.9-2.7-10-1.4v-16Zm20 0c-4.1-1.3-7.4-.8-10 1.4v16c2.6-2.2 5.9-2.7 10-1.4v-16Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                </span>
                <span><strong class="block text-base font-extrabold">RaporKu</strong><small class="text-[0.65rem] font-semibold uppercase tracking-[0.16em] text-slate-400">Panel Guru</small></span>
            </a>

            <nav class="mt-6" aria-label="Navigasi guru">
                <a href="{{ route('guru.dashboard') }}" class="flex items-center gap-3 rounded-xl bg-emerald-500/15 px-3 py-3 text-sm font-semibold text-emerald-300 ring-1 ring-inset ring-emerald-400/20">
                    <span class="grid size-7 place-items-center rounded-lg bg-emerald-400/10">⌂</span> Dashboard
                </a>
            </nav>

            <div class="mt-5 border-t border-white/10 pt-5 lg:mt-auto">
                <div class="mb-4 flex items-center gap-3 px-2">
                    <span class="grid size-10 place-items-center rounded-full bg-emerald-100 text-sm font-extrabold text-emerald-800">{{ mb_strtoupper(mb_substr($guru->nama, 0, 1)) }}</span>
                    <span class="min-w-0"><strong class="block truncate text-sm">{{ $guru->nama }}</strong><small class="block truncate text-xs text-slate-400">{{ $user->email }}</small></span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl border border-white/10 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-red-300/20 hover:bg-red-400/10 hover:text-red-200">
                        <span aria-hidden="true">↗</span> Logout
                    </button>
                </form>
            </div>
        </aside>

        <div class="min-w-0">
            <header class="flex min-h-20 items-center justify-between border-b border-slate-200 bg-white px-5 sm:px-8">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-600">Panel Guru</p>
                    <p class="mt-1 text-sm text-slate-500">Lihat tugas mengajar Anda</p>
                </div>
                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">Guru</span>
            </header>

            <main class="p-5 sm:p-8 lg:p-10">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
