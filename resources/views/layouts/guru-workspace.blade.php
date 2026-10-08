@php
    $workspace = $workspace ?? 'mapel';
    $canAccessMapel = $canAccessMapel ?? false;
    $canAccessWaliKelas = $canAccessWaliKelas ?? false;
    $isWaliWorkspace = $workspace === 'wali';
    $dashboardRoute = $isWaliWorkspace ? route('guru.wali.dashboard') : route('guru.dashboard');
    $activeNavClass = 'border-l-2 border-emerald-300 bg-emerald-500/15 px-[10px] text-emerald-100 ring-1 ring-inset ring-emerald-400/20';
    $inactiveNavClass = 'border-l-2 border-transparent px-[10px] text-slate-300 hover:border-white/20 hover:bg-white/[0.08] hover:text-white';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Ruang kerja guru RaporKu untuk mengelola kegiatan akademik sesuai penugasan.">
    <meta name="theme-color" content="#0f2f4f">
    <title>@yield('title', 'Guru') - {{ config('app.name', 'RaporKu') }}</title>
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
                <x-brand :href="$dashboardRoute" subtitle="Workspace Guru" :on-dark="true" aria-label="RaporKu Guru" />
                <button type="button" class="grid size-10 place-items-center rounded-xl border border-white/10 text-slate-300 hover:bg-white/10 lg:hidden" aria-label="Tutup navigasi" data-sidebar-close>
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
            </div>

            @if ($canAccessMapel && $canAccessWaliKelas)
                <div class="mt-6 shrink-0 rounded-2xl border border-white/10 bg-white/[0.05] p-1.5" aria-label="Pilih workspace">
                    <div class="grid grid-cols-2 gap-1">
                        <a href="{{ route('guru.mapel.dashboard') }}" class="rounded-xl px-2 py-2.5 text-center text-xs font-bold transition {{ ! $isWaliWorkspace ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-300 hover:bg-white/10' }}" @if (! $isWaliWorkspace) aria-current="page" @endif>Guru Mapel</a>
                        <a href="{{ route('guru.wali.dashboard') }}" class="rounded-xl px-2 py-2.5 text-center text-xs font-bold transition {{ $isWaliWorkspace ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-300 hover:bg-white/10' }}" @if ($isWaliWorkspace) aria-current="page" @endif>Wali Kelas</a>
                    </div>
                </div>
            @endif

            <nav class="mt-6 min-h-0 flex-1 space-y-1 overflow-y-auto pr-1" aria-label="Navigasi guru">
                <p class="px-3 text-[0.65rem] font-extrabold tracking-[0.16em] text-slate-500">UTAMA</p>
                    <a href="{{ $dashboardRoute }}" class="mt-2 flex min-h-11 items-center gap-3 rounded-xl border-l-2 px-[10px] py-2.5 text-sm font-semibold transition focus-visible:ring-2 focus-visible:ring-emerald-300 {{ request()->routeIs($isWaliWorkspace ? 'guru.wali.dashboard' : 'guru.dashboard', 'guru.mapel.dashboard') ? $activeNavClass : $inactiveNavClass }}" @if (request()->routeIs($isWaliWorkspace ? 'guru.wali.dashboard' : 'guru.dashboard', 'guru.mapel.dashboard')) aria-current="page" @endif>
                    <x-nav-icon name="dashboard" /> Dashboard
                </a>
                @if ($isWaliWorkspace)
                    <p class="mt-7 px-3 text-[0.65rem] font-extrabold tracking-[0.16em] text-slate-500">KELAS</p>
                    <a href="{{ route('guru.wali.siswa.index') }}" class="mt-2 flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('guru.wali.siswa.*') ? $activeNavClass : $inactiveNavClass }}" @if (request()->routeIs('guru.wali.siswa.*')) aria-current="page" @endif><x-nav-icon name="siswa" /> Siswa Kelas</a>
                    <a href="{{ route('guru.wali.perkembangan.index') }}" class="mt-1 flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('guru.wali.perkembangan.*') ? $activeNavClass : $inactiveNavClass }}" @if (request()->routeIs('guru.wali.perkembangan.*')) aria-current="page" @endif><x-nav-icon name="chart" /> Perkembangan</a>
                    <a href="{{ route('guru.wali.perhatian.index') }}" class="mt-1 flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('guru.wali.perhatian.*') ? $activeNavClass : $inactiveNavClass }}" @if (request()->routeIs('guru.wali.perhatian.*')) aria-current="page" @endif><x-nav-icon name="attention" /> Siswa Perlu Perhatian</a>

                    <p class="mt-7 px-3 text-[0.65rem] font-extrabold tracking-[0.16em] text-slate-500">RAPOR</p>
                    <a href="{{ route('guru.wali.deskripsi.index') }}" class="mt-2 flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('guru.wali.deskripsi.*') ? $activeNavClass : $inactiveNavClass }}" @if (request()->routeIs('guru.wali.deskripsi.*')) aria-current="page" @endif><x-nav-icon name="description" /> Deskripsi Rapor</a>
                    <a href="{{ route('guru.wali.rapor.index') }}" class="mt-1 flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('guru.wali.rapor.index', 'guru.wali.rapor.show') ? $activeNavClass : $inactiveNavClass }}" @if (request()->routeIs('guru.wali.rapor.index', 'guru.wali.rapor.show')) aria-current="page" @endif><x-nav-icon name="rekap" /> Data Rapor</a>
                    <a href="{{ route('guru.wali.rapor.index') }}#siap-cetak" class="mt-1 flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('guru.wali.rapor.print') ? $activeNavClass : $inactiveNavClass }}" @if (request()->routeIs('guru.wali.rapor.print')) aria-current="page" @endif><x-nav-icon name="printer" /> Cetak Rapor</a>

                    <p class="mt-7 px-3 text-[0.65rem] font-extrabold tracking-[0.16em] text-slate-500">AKUN</p>
                    <a href="{{ route('guru.wali.profil.show') }}" class="mt-2 flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('guru.wali.profil.*') ? $activeNavClass : $inactiveNavClass }}" @if (request()->routeIs('guru.wali.profil.*')) aria-current="page" @endif><x-nav-icon name="profile" /> Profil</a>
                @else
                    <p class="mt-7 px-3 text-[0.65rem] font-extrabold tracking-[0.16em] text-slate-500">PEMBELAJARAN</p>
                    <a href="{{ route('guru.kelas-mapel.index') }}" class="mt-2 flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('guru.kelas-mapel.*') ? $activeNavClass : $inactiveNavClass }}" @if (request()->routeIs('guru.kelas-mapel.*')) aria-current="page" @endif><x-nav-icon name="mapel" /> Kelas & Mata Pelajaran</a>
                    <a href="{{ route('guru.penilaian.index') }}" class="mt-2 flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('guru.penilaian.*') ? $activeNavClass : $inactiveNavClass }}" @if (request()->routeIs('guru.penilaian.*')) aria-current="page" @endif><x-nav-icon name="penilaian" /> Penilaian</a>
                    <a href="{{ route('guru.nilai.index') }}" class="mt-2 flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('guru.nilai.*') ? $activeNavClass : $inactiveNavClass }}" @if (request()->routeIs('guru.nilai.*')) aria-current="page" @endif><x-nav-icon name="nilai" /> Input Nilai</a>

                    <p class="mt-7 px-3 text-[0.65rem] font-extrabold tracking-[0.16em] text-slate-500">ANALISIS</p>
                    <a href="{{ route('guru.analisis.index') }}" class="mt-2 flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('guru.analisis.*') ? $activeNavClass : $inactiveNavClass }}" @if (request()->routeIs('guru.analisis.*')) aria-current="page" @endif><x-nav-icon name="chart" /> Analisis Nilai</a>
                    <a href="{{ route('guru.perhatian.index') }}" class="mt-1 flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('guru.perhatian.*') ? $activeNavClass : $inactiveNavClass }}" @if (request()->routeIs('guru.perhatian.*')) aria-current="page" @endif><x-nav-icon name="attention" /> Siswa Perlu Perhatian</a>
                    <a href="{{ route('guru.rekap-nilai.index') }}" class="mt-1 flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('guru.rekap-nilai.*') ? $activeNavClass : $inactiveNavClass }}" @if (request()->routeIs('guru.rekap-nilai.*')) aria-current="page" @endif><x-nav-icon name="rekap" /> Rekap Nilai</a>
                @endif
            </nav>

            <div class="mt-5 shrink-0 border-t border-white/10 pt-5">
                <div class="mb-4 flex items-center gap-3 rounded-xl bg-white/[0.04] p-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-full bg-emerald-100 text-sm font-extrabold text-emerald-800">{{ mb_strtoupper(mb_substr($guru->nama, 0, 1)) }}</span>
                    <span class="min-w-0"><strong class="block truncate text-sm">{{ $guru->nama }}</strong><small class="block truncate text-xs text-slate-400">{{ $user->email }}</small></span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-white/10 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-red-300/20 hover:bg-red-400/10 hover:text-red-200"><x-nav-icon name="logout" /> Keluar</button>
                </form>
            </div>
        </aside>

        <div class="min-w-0">
            <header class="sticky top-0 z-30 flex min-h-20 items-center justify-between border-b border-slate-200 bg-white/95 px-5 shadow-sm backdrop-blur-md sm:px-8">
                <div class="flex items-center gap-3">
                    <button type="button" class="grid size-11 place-items-center rounded-xl border border-slate-200 bg-white text-slate-700 lg:hidden" aria-label="Buka navigasi" aria-expanded="false" aria-controls="dashboard-sidebar" data-sidebar-open><svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
                    <div><p class="text-xs font-extrabold uppercase tracking-[0.16em] text-emerald-600">{{ $isWaliWorkspace ? 'Workspace Wali Kelas' : 'Workspace Guru Mapel' }}</p><p class="mt-1 hidden text-sm text-slate-500 sm:block">{{ $isWaliWorkspace ? 'Pantau kelas yang menjadi tanggung jawab Anda' : 'Pantau tugas mengajar dan progres penilaian' }}</p></div>
                </div>
                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">{{ $isWaliWorkspace ? 'Wali Kelas' : 'Guru Mapel' }}</span>
            </header>
            <main id="main-content" tabindex="-1" class="p-5 sm:p-8 lg:p-10">
                @if (session('success'))
                    <div class="mx-auto mb-6 flex max-w-7xl items-start justify-between gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status" data-flash-message>
                        <span><strong>Berhasil.</strong> {{ session('success') }}</span>
                        <button type="button" class="font-bold text-emerald-700" aria-label="Tutup notifikasi" data-flash-close>&times;</button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="mx-auto mb-6 flex max-w-7xl items-start justify-between gap-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert" data-flash-message>
                        <span><strong>Tindakan gagal.</strong> {{ session('error') }}</span>
                        <button type="button" class="font-bold text-red-700" aria-label="Tutup notifikasi" data-flash-close>&times;</button>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
