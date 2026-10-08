@extends('layouts.guru-workspace')

@section('title', 'Detail Kelas')

@section('content')
    <section class="mx-auto max-w-7xl">
        <a href="{{ route('guru.kelas-mapel.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-emerald-700">&larr; Kembali ke Kelas & Mata Pelajaran</a>

        <div class="mt-6 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-600">{{ $assignment->mataPelajaran->kode }} · {{ $assignment->tahunAjaran->tahun }} {{ $assignment->tahunAjaran->semester }}</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">{{ $assignment->mataPelajaran->nama }} — {{ $assignment->kelas->tingkat }} {{ $assignment->kelas->nama }}</h1>
                <p class="mt-2 text-sm text-slate-500">Ringkasan siswa, penilaian, dan progres nilai pada tugas mengajar ini.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('guru.penilaian.create', ['pengampu_id' => $assignment->id]) }}" class="rk-button rk-button-secondary">+ Buat Penilaian</a>
                <a href="{{ route('guru.nilai.index', ['tahun_ajaran_id' => $assignment->tahun_ajaran_id, 'mata_pelajaran_id' => $assignment->mata_pelajaran_id, 'kelas_id' => $assignment->kelas_id]) }}" class="rk-button rk-button-primary">Input Nilai</a>
            </div>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Jumlah Siswa', 'value' => $assignment->kelas->siswa->count()],
                ['label' => 'Penilaian', 'value' => $assignment->penilaian->count()],
                ['label' => 'Rata-rata Kelas', 'value' => $classAverage === null ? '—' : number_format($classAverage, 2, ',', '.')],
                ['label' => 'KKM', 'value' => number_format((float) $assignment->mataPelajaran->kkm, 0, ',', '.')],
            ] as $summary)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ $summary['label'] }}</p><p class="mt-3 text-2xl font-extrabold text-[#102d4b]">{{ $summary['value'] }}</p></article>
            @endforeach
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-2">
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-5"><div><h2 class="font-bold text-[#102d4b]">Daftar Penilaian</h2><p class="mt-1 text-sm text-slate-500">Urutan penilaian dan progres pengisian nilai.</p></div><a href="{{ route('guru.penilaian.index', ['pengampu_id' => $assignment->id]) }}" class="text-sm font-bold text-emerald-700">Kelola</a></div>
                <div class="divide-y divide-slate-100">
                    @forelse ($assignment->penilaian as $assessment)
                        <div class="flex items-center justify-between gap-4 px-5 py-4"><div><h3 class="text-sm font-bold text-slate-700">{{ $assessment->urutan }}. {{ $assessment->nama }}</h3><p class="mt-1 text-xs text-slate-400">{{ str($assessment->jenis)->replace('_', ' ')->title() }} · {{ $assessment->tanggal->translatedFormat('d M Y') }}</p></div><div class="text-right"><strong class="text-sm text-slate-700">{{ $assessment->nilai_count }}/{{ $assignment->kelas->siswa->count() }}</strong><small class="block text-xs text-slate-400">nilai terisi</small></div></div>
                    @empty
                        <div class="px-5 py-10 text-center"><p class="font-bold text-slate-700">Belum ada penilaian</p><p class="mt-1 text-sm text-slate-500">Buat penilaian pertama untuk mulai memasukkan nilai siswa.</p></div>
                    @endforelse
                </div>
            </article>

            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-5"><h2 class="font-bold text-[#102d4b]">Daftar Siswa</h2><p class="mt-1 text-sm text-slate-500">Siswa yang saat ini terdaftar di kelas.</p></div>
                <div class="max-h-[30rem] divide-y divide-slate-100 overflow-y-auto">
                    @forelse ($assignment->kelas->siswa as $student)
                        <div class="flex items-center justify-between gap-4 px-5 py-4"><div><h3 class="text-sm font-bold text-slate-700">{{ $student->nama }}</h3><p class="mt-1 text-xs text-slate-400">NIS {{ $student->nis }}{{ $student->nisn ? ' · NISN '.$student->nisn : '' }}</p></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ $student->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</span></div>
                    @empty
                        <div class="px-5 py-10 text-center"><p class="font-bold text-slate-700">Belum ada siswa di kelas ini</p><p class="mt-1 text-sm text-slate-500">Hubungi administrator untuk menempatkan siswa.</p></div>
                    @endforelse
                </div>
            </article>
        </div>
    </section>
@endsection
