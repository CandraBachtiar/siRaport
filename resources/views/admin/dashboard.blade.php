@extends('layouts.admin')

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
                ['label' => 'Jumlah Guru', 'value' => $statistics['guru'], 'tone' => 'bg-emerald-50 text-emerald-700', 'icon' => 'G'],
                ['label' => 'Jumlah Siswa', 'value' => $statistics['siswa'], 'tone' => 'bg-blue-50 text-blue-700', 'icon' => 'S'],
                ['label' => 'Jumlah Kelas', 'value' => $statistics['kelas'], 'tone' => 'bg-amber-50 text-amber-700', 'icon' => 'K'],
                ['label' => 'Mata Pelajaran', 'value' => $statistics['mata_pelajaran'], 'tone' => 'bg-violet-50 text-violet-700', 'icon' => 'M'],
            ] as $statistic)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.03]">
                    <div class="flex items-center justify-between">
                        <span class="grid size-11 place-items-center rounded-xl text-sm font-extrabold {{ $statistic['tone'] }}">{{ $statistic['icon'] }}</span>
                        <span class="text-xs font-semibold text-slate-400">Total data</span>
                    </div>
                    <p class="mt-5 text-3xl font-extrabold tracking-tight text-[#102d4b]">{{ number_format($statistic['value'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm font-medium text-slate-500">{{ $statistic['label'] }}</p>
                </article>
            @endforeach
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/[0.03]">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-emerald-50 text-emerald-700">✓</span>
                    <div><h2 class="font-bold text-[#102d4b]">Sistem siap digunakan</h2><p class="mt-1 text-sm text-slate-500">Autentikasi dan proteksi area admin telah aktif.</p></div>
                </div>
                <div class="mt-6 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm leading-7 text-slate-600">Menu pengelolaan data telah disiapkan sebagai navigasi nonaktif dan akan diimplementasikan pada tahap pengembangan berikutnya.</p>
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
