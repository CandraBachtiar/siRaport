<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Ruang kerja guru RaporKu untuk melihat tugas mengajar dan data akademik.">
    <meta name="theme-color" content="#0f2f4f">
    <title>@yield('title', 'Guru') — {{ config('app.name', 'RaporKu') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/raporku.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[linear-gradient(135deg,#f8fafc_0%,#f1f5f9_58%,#e9eef3_100%)] text-slate-800 antialiased">
    <x-skip-link />
    <div class="min-h-screen lg:grid lg:grid-cols-[18rem_minmax(0,1fr)]">
        <div class="fixed inset-0 z-40 hidden bg-slate-950/50 backdrop-blur-sm lg:hidden" aria-hidden="true" data-dashboard-backdrop></div>
        <aside id="dashboard-sidebar" class="fixed inset-y-0 left-0 z-50 hidden h-screen max-h-screen w-72 flex-col border-r border-white/10 bg-raporku-navy-deep px-4 py-5 text-white shadow-[8px_0_24px_rgba(15,47,79,0.08)] lg:sticky lg:top-0 lg:flex lg:w-auto lg:py-6" data-dashboard-sidebar>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-white/10 px-2 pb-5">
                <x-brand :href="route('guru.dashboard')" subtitle="Workspace Guru" :on-dark="true" aria-label="RaporKu Guru" />
                <button type="button" class="grid size-10 place-items-center rounded-xl border border-white/10 text-slate-300 hover:bg-white/10 lg:hidden" aria-label="Tutup navigasi" data-sidebar-close><svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
            </div>
            <nav class="mt-6 min-h-0 flex-1 space-y-1 overflow-y-auto pr-1" aria-label="Navigasi guru">
                <p class="px-3 text-[0.65rem] font-extrabold tracking-[0.16em] text-slate-500">UTAMA</p>
                <a href="{{ route('guru.dashboard') }}" class="mt-2 flex min-h-11 items-center gap-3 rounded-xl border-l-2 border-emerald-300 bg-emerald-500/15 px-[10px] py-2.5 text-sm font-semibold text-emerald-100 ring-1 ring-inset ring-emerald-400/20" aria-current="page"><x-nav-icon name="dashboard" /> Dashboard</a>
                <div class="mt-6 rounded-xl border border-white/10 bg-white/[0.04] p-4"><p class="text-xs font-bold text-slate-200">Tugas mengajar</p><p class="mt-1 text-xs leading-5 text-slate-400">Menu pembelajaran akan mengikuti kelas dan mata pelajaran yang ditetapkan administrator.</p></div>
            </nav>
            <div class="mt-5 shrink-0 border-t border-white/10 pt-5">
                <div class="mb-4 flex items-center gap-3 rounded-xl bg-white/[0.04] p-3"><span class="grid size-10 shrink-0 place-items-center rounded-full bg-emerald-100 text-sm font-extrabold text-emerald-800">{{ mb_strtoupper(mb_substr($guru->nama, 0, 1)) }}</span><span class="min-w-0"><strong class="block truncate text-sm">{{ $guru->nama }}</strong><small class="block truncate text-xs text-slate-400">{{ $user->email }}</small></span></div>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-white/10 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-red-300/20 hover:bg-red-400/10 hover:text-red-200"><x-nav-icon name="logout" /> Keluar</button></form>
            </div>
        </aside>
        <div class="min-w-0">
            <header class="sticky top-0 z-30 flex min-h-20 items-center justify-between border-b border-slate-200 bg-white/95 px-5 shadow-sm backdrop-blur-md sm:px-8">
                <div class="flex items-center gap-3"><button type="button" class="grid size-11 place-items-center rounded-xl border border-slate-200 bg-white text-slate-700 lg:hidden" aria-label="Buka navigasi" aria-expanded="false" aria-controls="dashboard-sidebar" data-sidebar-open><svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button><div><p class="text-xs font-extrabold uppercase tracking-[0.16em] text-emerald-600">Workspace Guru</p><p class="mt-1 hidden text-sm text-slate-500 sm:block">Lihat tugas mengajar Anda</p></div></div>
                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">Guru</span>
            </header>
            <main id="main-content" tabindex="-1" class="p-5 sm:p-8 lg:p-10">@yield('content')</main>
        </div>
    </div>
</body>
</html>
