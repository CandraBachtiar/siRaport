<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Panel administrasi EduRaport.">
    <title>@yield('title', 'Admin') — EduRaport</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-[17rem_1fr]">
        <aside class="bg-[#0b2945] px-5 py-5 text-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col lg:px-4 lg:py-6">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-2" aria-label="EduRaport Admin">
                <span class="grid size-11 place-items-center rounded-xl bg-emerald-500 text-white shadow-lg shadow-emerald-950/20">
                    <svg class="size-6" viewBox="0 0 40 40" aria-hidden="true"><path d="M10 12.5c4.1-1.3 7.4-.8 10 1.4v16c-2.6-2.2-5.9-2.7-10-1.4v-16Zm20 0c-4.1-1.3-7.4-.8-10 1.4v16c2.6-2.2 5.9-2.7 10-1.4v-16Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                </span>
                <span><strong class="block text-base font-extrabold">EduRaport</strong><small class="text-[0.65rem] font-semibold uppercase tracking-[0.16em] text-slate-400">Panel Admin</small></span>
            </a>

            <nav class="mt-6 flex gap-2 overflow-x-auto pb-2 lg:grid lg:overflow-visible lg:pb-0" aria-label="Navigasi admin">
                <a href="{{ route('admin.dashboard') }}" @class([
                    'flex shrink-0 items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition',
                    'bg-emerald-500/15 text-emerald-300 ring-1 ring-inset ring-emerald-400/20' => request()->routeIs('admin.dashboard'),
                    'text-slate-400 hover:bg-white/5 hover:text-slate-200' => ! request()->routeIs('admin.dashboard'),
                ])>
                    <span class="grid size-7 place-items-center rounded-lg bg-emerald-400/10">⌂</span> Dashboard
                </a>
                <a href="{{ route('admin.guru.index') }}" @class([
                    'flex shrink-0 items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition',
                    'bg-emerald-500/15 text-emerald-300 ring-1 ring-inset ring-emerald-400/20' => request()->routeIs('admin.guru.*'),
                    'text-slate-400 hover:bg-white/5 hover:text-slate-200' => ! request()->routeIs('admin.guru.*'),
                ])>
                    <span class="grid size-7 place-items-center rounded-lg bg-emerald-400/10">G</span> Data Guru
                </a>
                <a href="{{ route('admin.siswa.index') }}" @class([
                    'flex shrink-0 items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition',
                    'bg-emerald-500/15 text-emerald-300 ring-1 ring-inset ring-emerald-400/20' => request()->routeIs('admin.siswa.*'),
                    'text-slate-400 hover:bg-white/5 hover:text-slate-200' => ! request()->routeIs('admin.siswa.*'),
                ])>
                    <span class="grid size-7 place-items-center rounded-lg bg-emerald-400/10">S</span> Data Siswa
                </a>
                <a href="{{ route('admin.kelas.index') }}" @class([
                    'flex shrink-0 items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition',
                    'bg-emerald-500/15 text-emerald-300 ring-1 ring-inset ring-emerald-400/20' => request()->routeIs('admin.kelas.*'),
                    'text-slate-400 hover:bg-white/5 hover:text-slate-200' => ! request()->routeIs('admin.kelas.*'),
                ])>
                    <span class="grid size-7 place-items-center rounded-lg bg-emerald-400/10">K</span> Data Kelas
                </a>
                <a href="{{ route('admin.mata-pelajaran.index') }}" @class([
                    'flex shrink-0 items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition',
                    'bg-emerald-500/15 text-emerald-300 ring-1 ring-inset ring-emerald-400/20' => request()->routeIs('admin.mata-pelajaran.*'),
                    'text-slate-400 hover:bg-white/5 hover:text-slate-200' => ! request()->routeIs('admin.mata-pelajaran.*'),
                ])>
                    <span class="grid size-7 place-items-center rounded-lg bg-emerald-400/10">M</span> Mata Pelajaran
                </a>
                <a href="{{ route('admin.tahun-ajaran.index') }}" @class([
                    'flex shrink-0 items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition',
                    'bg-emerald-500/15 text-emerald-300 ring-1 ring-inset ring-emerald-400/20' => request()->routeIs('admin.tahun-ajaran.*'),
                    'text-slate-400 hover:bg-white/5 hover:text-slate-200' => ! request()->routeIs('admin.tahun-ajaran.*'),
                ])>
                    <span class="grid size-7 place-items-center rounded-lg bg-emerald-400/10">T</span> Data Tahun Ajaran
                </a>
                @foreach (['Pengampu', 'Import Data'] as $menu)
                    <span aria-disabled="true" title="Fitur akan tersedia pada tahap berikutnya" class="flex shrink-0 cursor-not-allowed items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-500">
                        <span class="grid size-7 place-items-center rounded-lg bg-white/5">·</span> {{ $menu }}
                    </span>
                @endforeach
            </nav>

            <div class="mt-5 border-t border-white/10 pt-5 lg:mt-auto">
                <div class="mb-4 flex items-center gap-3 px-2">
                    <span class="grid size-10 place-items-center rounded-full bg-emerald-100 text-sm font-extrabold text-emerald-800">{{ mb_strtoupper(mb_substr($admin->name, 0, 1)) }}</span>
                    <span class="min-w-0"><strong class="block truncate text-sm">{{ $admin->name }}</strong><small class="block truncate text-xs text-slate-400">{{ $admin->email }}</small></span>
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
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-600">Panel Administrator</p>
                    <p class="mt-1 text-sm text-slate-500">Kelola sistem EduRaport</p>
                </div>
                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">Administrator</span>
            </header>

            <main class="p-5 sm:p-8 lg:p-10">
                @if (session('success'))
                    <div class="mx-auto mb-6 flex max-w-7xl items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800" role="status">
                        <span aria-hidden="true">✓</span>
                        <p>{{ session('success') }}</p>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mx-auto mb-6 flex max-w-7xl items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800" role="alert">
                        <span aria-hidden="true">!</span>
                        <p>{{ session('error') }}</p>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
