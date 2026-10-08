@extends('layouts.guru-workspace')

@section('title', 'Dashboard Guru Mapel')

@section('content')
    <section class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-600">Dashboard Guru Mapel</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Selamat datang, {{ $guru->nama }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Pantau tugas mengajar dan kelengkapan nilai dalam satu ruang kerja. NIP: {{ $guru->nip ?: 'Belum tersedia' }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <p class="text-[0.65rem] font-extrabold uppercase tracking-[0.16em] text-slate-400">Tahun Ajaran Aktif</p>
                <p class="mt-1 text-sm font-bold text-[#102d4b]">{{ $activeSchoolYear ? $activeSchoolYear->tahun.' · '.$activeSchoolYear->semester : 'Belum ditetapkan' }}</p>
            </div>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                ['value' => $metrics['classCount'], 'label' => 'Kelas Diajar', 'tone' => 'bg-blue-50 text-blue-700'],
                ['value' => $metrics['subjectCount'], 'label' => 'Mata Pelajaran', 'tone' => 'bg-violet-50 text-violet-700'],
                ['value' => $metrics['studentCount'], 'label' => 'Siswa Terjangkau', 'tone' => 'bg-emerald-50 text-emerald-700'],
                ['value' => $metrics['assessmentCount'], 'label' => 'Penilaian', 'tone' => 'bg-amber-50 text-amber-700'],
                ['value' => $metrics['missingScoreCount'], 'label' => 'Nilai Belum Diisi', 'tone' => 'bg-rose-50 text-rose-700'],
            ] as $summary)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.03]">
                    <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-bold {{ $summary['tone'] }}">{{ $summary['label'] }}</span>
                    <p class="mt-4 text-3xl font-extrabold tracking-tight text-[#102d4b]">{{ number_format($summary['value'], 0, ',', '.') }}</p>
                </article>
            @endforeach
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(19rem,0.75fr)]">
            <article id="tugas-mengajar" class="scroll-mt-28 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.03]">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div><h2 class="font-bold text-[#102d4b]">Tugas Mengajar</h2><p class="mt-1 text-sm text-slate-500">{{ $metrics['assignmentCount'] }} penugasan pada periode yang ditampilkan.</p></div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Jumlah Pengampu: {{ $metrics['assignmentCount'] }}</span>
                </div>

                @if ($assignments->isEmpty())
                    <div class="px-5 py-14 text-center sm:px-6">
                        <span class="mx-auto grid size-12 place-items-center rounded-xl bg-slate-100 text-slate-500"><x-nav-icon name="mapel" /></span>
                        <h3 class="mt-4 font-bold text-slate-700">Belum ada data pengampu</h3>
                        <p class="mt-2 text-sm text-slate-500">Tugas mengajar Anda belum ditambahkan oleh administrator.</p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($assignments as $assignment)
                            <div class="grid gap-4 px-5 py-5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:px-6">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-bold text-slate-800">{{ $assignment->mataPelajaran?->nama ?? 'Mata pelajaran tidak tersedia' }}</h3>
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[0.65rem] font-bold text-emerald-700">{{ $assignment->mataPelajaran?->kode ?? 'Tanpa kode' }}</span>
                                    </div>
                                    <p class="mt-2 text-sm text-slate-500">{{ $assignment->kelas ? $assignment->kelas->tingkat.' '.$assignment->kelas->nama : 'Kelas tidak tersedia' }} &middot; {{ $assignment->tahunAjaran ? $assignment->tahunAjaran->tahun.' '.$assignment->tahunAjaran->semester : 'Periode tidak tersedia' }}</p>
                                </div>
                                <div class="flex gap-2 text-center">
                                    <span class="min-w-20 rounded-xl bg-slate-50 px-3 py-2"><strong class="block text-sm text-slate-800">{{ $assignment->student_count }}</strong><small class="text-[0.65rem] font-semibold text-slate-500">Siswa</small></span>
                                    <span class="min-w-20 rounded-xl bg-slate-50 px-3 py-2"><strong class="block text-sm text-slate-800">{{ $assignment->penilaian_count }}</strong><small class="text-[0.65rem] font-semibold text-slate-500">Penilaian</small></span>
                                    <a href="{{ route('guru.kelas-mapel.show', $assignment) }}" class="inline-flex min-h-11 items-center rounded-xl border border-emerald-200 px-3 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50">Buka Kelas</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>

            <div class="space-y-6">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.03] sm:p-6">
                    <h2 class="font-bold text-[#102d4b]">Akses Cepat</h2>
                    <div class="mt-4 grid gap-3">
                        <a href="{{ route('guru.penilaian.create') }}" class="flex min-h-12 items-center justify-between rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50">Buat Penilaian <span aria-hidden="true">&rarr;</span></a>
                        <a href="{{ route('guru.nilai.index') }}" class="flex min-h-12 items-center justify-between rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50">Input Nilai <span aria-hidden="true">&rarr;</span></a>
                        <a href="{{ route('guru.analisis.index') }}" class="flex min-h-12 items-center justify-between rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50">Lihat Analisis <span aria-hidden="true">&rarr;</span></a>
                        <a href="{{ route('guru.rekap-nilai.index') }}" class="flex min-h-12 items-center justify-between rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50">Lihat Rekap <span aria-hidden="true">&rarr;</span></a>
                    </div>
                </article>

                <article id="perlu-diselesaikan" class="scroll-mt-28 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.03] sm:p-6">
                    <h2 class="font-bold text-[#102d4b]">Perlu Diselesaikan</h2>
                    <div class="mt-4 grid gap-3">
                        <div class="flex items-center justify-between rounded-xl bg-rose-50 px-4 py-3"><span class="text-sm font-semibold text-rose-800">Nilai belum diisi</span><strong class="text-rose-800">{{ $metrics['missingScoreCount'] }}</strong></div>
                        <div class="flex items-center justify-between rounded-xl bg-amber-50 px-4 py-3"><span class="text-sm font-semibold text-amber-800">Penilaian belum lengkap</span><strong class="text-amber-800">{{ $metrics['incompleteAssessmentCount'] }}</strong></div>
                        <div class="flex items-center justify-between rounded-xl bg-blue-50 px-4 py-3"><span class="text-sm font-semibold text-blue-800">Siswa di bawah KKM</span><strong class="text-blue-800">{{ $metrics['belowKkmStudentCount'] }}</strong></div>
                    </div>
                </article>
            </div>
        </div>

        @if ($incompleteAssessments->isNotEmpty())
            <article class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.03]">
                <div class="border-b border-slate-200 px-5 py-5 sm:px-6"><h2 class="font-bold text-[#102d4b]">Rincian Penilaian Belum Lengkap</h2><p class="mt-1 text-sm text-slate-500">Diurutkan dari jumlah nilai kosong terbanyak.</p></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <caption class="sr-only">Daftar siswa dan ringkasan nilai mata pelajaran</caption>
                        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3 sm:px-6">Penilaian</th><th class="px-5 py-3 sm:px-6">Mapel & Kelas</th><th class="px-5 py-3 sm:px-6">Progres</th><th class="px-5 py-3 sm:px-6">Belum Diisi</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($incompleteAssessments as $assessment)
                                <tr>
                                    <td class="px-5 py-4 sm:px-6"><strong class="block text-slate-700">{{ $assessment['nama'] }}</strong><span class="text-xs text-slate-400">{{ $assessment['jenis'] }} · {{ $assessment['tanggal']->translatedFormat('d M Y') }}</span></td>
                                    <td class="px-5 py-4 text-slate-600 sm:px-6">{{ $assessment['mataPelajaran'] }}<span class="block text-xs text-slate-400">{{ $assessment['kelas'] }}</span></td>
                                    <td class="px-5 py-4 text-slate-600 sm:px-6">{{ $assessment['filledCount'] }}/{{ $assessment['studentCount'] }}</td>
                                    <td class="px-5 py-4 sm:px-6"><span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">{{ $assessment['missingCount'] }} nilai</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </article>
        @endif
    </section>
@endsection
