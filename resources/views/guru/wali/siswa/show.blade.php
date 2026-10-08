@extends('layouts.guru-workspace')

@section('title', 'Detail '.$studentRow['student']->nama)

@section('content')
<section class="mx-auto max-w-7xl">
    <a href="{{ route('guru.wali.siswa.index', ['kelas_id' => $selectedClass?->id, 'tahun_ajaran_id' => $selectedSchoolYear?->id]) }}" class="text-sm font-bold text-emerald-700">← Kembali ke Siswa Kelas</a>
    <div class="mt-5 grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div><p class="rk-eyebrow">Detail Siswa</p><h1 class="mt-2 text-3xl font-extrabold text-[#102d4b]">{{ $studentRow['student']->nama }}</h1><p class="mt-2 text-sm text-slate-500">{{ $selectedClass?->tingkat }} {{ $selectedClass?->nama }} · {{ $selectedSchoolYear?->tahun }} {{ $selectedSchoolYear?->semester }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Identitas</p><dl class="mt-3 grid gap-2 text-sm"><div class="flex justify-between gap-4"><dt class="text-slate-500">NIS</dt><dd class="font-bold">{{ $studentRow['student']->nis }}</dd></div><div class="flex justify-between gap-4"><dt class="text-slate-500">NISN</dt><dd class="font-bold">{{ $studentRow['student']->nisn ?: '—' }}</dd></div><div class="flex justify-between gap-4"><dt class="text-slate-500">Rata-rata</dt><dd class="font-bold">{{ $studentRow['overallAverage'] === null ? '—' : number_format($studentRow['overallAverage'], 2, ',', '.') }}</dd></div></dl></div>
    </div>

    <div class="mt-7 grid gap-6 xl:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6"><h2 class="font-bold text-[#102d4b]">Rata-rata Mata Pelajaran</h2><x-charts.bar-chart :items="$studentRow['subjectSeries']" title="Rata-rata siswa per mata pelajaran" class="mt-5" /></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6"><h2 class="font-bold text-[#102d4b]">Perkembangan Penilaian</h2><x-charts.line-chart :points="$studentRow['progressSeries']" title="Perkembangan nilai lintas mata pelajaran" class="mt-5" /></article>
    </div>

    <article class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-200 px-5 py-5"><h2 class="font-bold text-[#102d4b]">Ringkasan Seluruh Mata Pelajaran</h2></div><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-5 py-4">Mata Pelajaran</th><th class="px-5 py-4">Rata-rata</th><th class="px-5 py-4">KKM</th><th class="px-5 py-4">Tren</th><th class="px-5 py-4">Deskripsi</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($studentRow['subjects'] as $subject)<tr><td class="px-5 py-4"><strong>{{ $subject['assignment']->mataPelajaran->nama }}</strong><small class="block text-slate-500">{{ $subject['assignment']->guru->nama }}</small></td><td class="px-5 py-4 font-bold">{{ $subject['average'] === null ? '—' : number_format($subject['average'], 2, ',', '.') }}</td><td class="px-5 py-4">{{ number_format((float) $subject['assignment']->mataPelajaran->kkm, 0, ',', '.') }}</td><td class="px-5 py-4">{{ $subject['trend']['label'] }}</td><td class="px-5 py-4">{{ $subject['description']?->status === 'tervalidasi' ? 'Tervalidasi' : ($subject['description'] ? 'Draf' : 'Belum dibuat') }}</td></tr>@empty<tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">Belum ada mata pelajaran pada periode ini.</td></tr>@endforelse</tbody></table></div></article>
</section>
@endsection
