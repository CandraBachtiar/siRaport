@extends('layouts.admin-v2')

@section('title', 'Dashboard Admin')

@section('content')
    <section class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-600">Dashboard</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Selamat datang, {{ $admin->name }}</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Ringkasan data utama sistem rapor digital.</p>
            </div>
            <p class="text-sm font-medium text-slate-500">{{ now()->translatedFormat('l, d F Y') }}</p>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Jumlah Guru', 'value' => $statistics['guru'], 'tone' => 'bg-emerald-50 text-emerald-700', 'icon' => 'guru'],
                ['label' => 'Jumlah Siswa', 'value' => $statistics['siswa'], 'tone' => 'bg-blue-50 text-blue-700', 'icon' => 'siswa'],
                ['label' => 'Jumlah Kelas', 'value' => $statistics['kelas'], 'tone' => 'bg-amber-50 text-amber-700', 'icon' => 'kelas'],
                ['label' => 'Mata Pelajaran', 'value' => $statistics['mata_pelajaran'], 'tone' => 'bg-violet-50 text-violet-700', 'icon' => 'mapel'],
            ] as $statistic)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.03]">
                    <div class="flex items-center justify-between">
                        <span class="grid size-11 place-items-center rounded-xl {{ $statistic['tone'] }}"><x-nav-icon :name="$statistic['icon']" /></span>
                        <span class="text-xs font-semibold text-slate-400">Total data</span>
                    </div>
                    <p class="mt-5 text-3xl font-extrabold tracking-tight text-[#102d4b]">{{ number_format($statistic['value'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm font-medium text-slate-500">{{ $statistic['label'] }}</p>
                </article>
            @endforeach
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/[0.03]">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="rk-eyebrow">Kontrol kesiapan</p>
                        <h2 class="mt-2 text-xl font-extrabold text-[#102d4b]">Persiapan Data Akademik</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">Pastikan data dasar tersedia sebelum guru mulai mengelola penilaian.</p>
                    </div>
                    <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600">{{ collect($academicChecklist)->where('complete', true)->count() }}/{{ count($academicChecklist) }} selesai</span>
                </div>

                <ul class="mt-6 grid gap-3">
                    @foreach ($academicChecklist as $item)
                        <li class="flex flex-col gap-3 rounded-xl border {{ $item['complete'] ? 'border-emerald-100 bg-emerald-50/60' : 'border-amber-100 bg-amber-50/60' }} px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid size-8 shrink-0 place-items-center rounded-full {{ $item['complete'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}" aria-hidden="true">
                                    @if ($item['complete'])
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none"><path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    @else
                                        <x-nav-icon name="attention" class="size-4" />
                                    @endif
                                </span>
                                <span class="text-sm font-semibold {{ $item['complete'] ? 'text-emerald-900' : 'text-amber-900' }}">{{ $item['label'] }}</span>
                            </div>
                            @unless ($item['complete'])
                                <a href="{{ route($item['route']) }}" class="inline-flex min-h-9 items-center justify-center rounded-lg border border-amber-200 bg-white px-3 text-xs font-bold text-amber-800 transition hover:border-amber-300 hover:bg-amber-50 focus:outline-none focus:ring-4 focus:ring-amber-500/20">{{ $item['action'] }}</a>
                            @endunless
                        </li>
                    @endforeach
                </ul>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/[0.03]">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Ringkasan Periode</p>
                    <x-nav-icon name="calendar" class="size-5 text-emerald-600" />
                </div>
                <dl class="mt-5 grid gap-4">
                    <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-semibold text-slate-400">Tahun ajaran aktif</dt><dd class="mt-1 text-sm font-bold text-[#102d4b]">{{ $statistics['active_school_year'] ? $statistics['active_school_year']->tahun.' · '.$statistics['active_school_year']->semester : 'Belum ditetapkan' }}</dd></div>
                    <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-semibold text-slate-400">Pengampu terdaftar</dt><dd class="mt-1 text-2xl font-extrabold text-[#102d4b]">{{ number_format($statistics['pengampu'], 0, ',', '.') }}</dd></div>
                </dl>
                <p class="mt-5 border-t border-slate-100 pt-4 text-xs leading-5 text-slate-500">Data diambil langsung dari master akademik RaporKu.</p>
            </article>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/[0.03]">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-emerald-50 text-emerald-700">✓</span>
                    <div><h2 class="font-bold text-[#102d4b]">Akses administrator aktif</h2><p class="mt-1 text-sm text-slate-500">Autentikasi dan proteksi area admin telah aktif.</p></div>
                </div>
                <div class="mt-6 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm leading-7 text-slate-600">Gunakan checklist persiapan di atas untuk melengkapi master data secara bertahap. Setiap tindakan akan membuka halaman pengelolaan terkait.</p>
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/[0.03]">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Akun aktif</p>
                <dl class="mt-5 grid gap-4">
                    <div><dt class="text-xs font-semibold text-slate-400">Nama Admin</dt><dd class="mt-1 text-sm font-bold text-slate-700">{{ $admin->name }}</dd></div>
                    <div><dt class="text-xs font-semibold text-slate-400">Email Admin</dt><dd class="mt-1 break-all text-sm font-bold text-slate-700">{{ $admin->email }}</dd></div>
                    <div><dt class="text-xs font-semibold text-slate-400">Role</dt><dd class="mt-1 text-sm font-bold text-emerald-700">Administrator</dd></div>
                </dl>
            </article>
        </div>
    </section>
@endsection
