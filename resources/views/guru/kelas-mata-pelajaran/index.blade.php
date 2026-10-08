@extends('layouts.guru-workspace')

@section('title', 'Kelas & Mata Pelajaran')

@section('content')
    <section class="mx-auto max-w-7xl">
        <div>
            <p class="text-sm font-semibold text-emerald-600">Pembelajaran</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Kelas & Mata Pelajaran</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Buka ruang kelas sesuai tugas mengajar yang telah ditetapkan administrator.</p>
        </div>

        @if ($assignments->isEmpty())
            <div class="mt-8 rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm">
                <span class="mx-auto grid size-12 place-items-center rounded-xl bg-slate-100 text-slate-500"><x-nav-icon name="mapel" /></span>
                <h2 class="mt-4 font-bold text-slate-700">Belum ada tugas mengajar</h2>
                <p class="mt-2 text-sm text-slate-500">Administrator belum menetapkan kelas dan mata pelajaran untuk akun Anda.</p>
            </div>
        @else
            <div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($assignments as $assignment)
                    <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.03]">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">{{ $assignment->mataPelajaran?->kode ?? 'Tanpa kode' }}</span>
                                <h2 class="mt-3 text-lg font-extrabold text-[#102d4b]">{{ $assignment->mataPelajaran?->nama ?? 'Mata pelajaran tidak tersedia' }}</h2>
                                <p class="mt-1 text-sm font-semibold text-slate-600">{{ $assignment->kelas ? $assignment->kelas->tingkat.' '.$assignment->kelas->nama : 'Kelas tidak tersedia' }}</p>
                            </div>
                            @if ($assignment->tahunAjaran?->aktif)
                                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[0.65rem] font-bold text-blue-700">Aktif</span>
                            @endif
                        </div>
                        <p class="mt-4 text-xs text-slate-500">{{ $assignment->tahunAjaran ? $assignment->tahunAjaran->tahun.' · '.$assignment->tahunAjaran->semester : 'Periode tidak tersedia' }}</p>
                        <dl class="mt-5 grid grid-cols-2 gap-3 border-y border-slate-100 py-4 text-center">
                            <div><dt class="text-xs text-slate-400">Siswa</dt><dd class="mt-1 font-extrabold text-slate-700">{{ $assignment->kelas?->siswa_count ?? 0 }}</dd></div>
                            <div><dt class="text-xs text-slate-400">Penilaian</dt><dd class="mt-1 font-extrabold text-slate-700">{{ $assignment->penilaian_count }}</dd></div>
                        </dl>
                        <a href="{{ route('guru.kelas-mapel.show', $assignment) }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-600 px-4 text-sm font-bold text-white transition hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-500/20">Buka Kelas</a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
