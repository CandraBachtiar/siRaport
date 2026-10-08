@extends('layouts.guru-workspace')

@section('title', 'Siswa Kelas')

@section('content')
<section class="mx-auto max-w-7xl">
    <div><p class="rk-eyebrow">Wali Kelas</p><h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Siswa Kelas</h1><p class="mt-2 text-sm text-slate-500">Identitas, kelengkapan nilai, dan status rapor siswa dalam kelas perwalian.</p></div>
    <div class="mt-7">@include('guru.wali.partials.filters', ['filterAction' => route('guru.wali.siswa.index')])</div>

    <form method="GET" action="{{ route('guru.wali.siswa.index') }}" class="mt-5 flex flex-col gap-3 sm:flex-row">
        <input type="hidden" name="kelas_id" value="{{ $selectedClass?->id }}"><input type="hidden" name="tahun_ajaran_id" value="{{ $selectedSchoolYear?->id }}">
        <label for="q" class="sr-only">Cari nama, NIS, atau NISN</label><input id="q" name="q" value="{{ $search }}" class="rk-field" placeholder="Cari nama, NIS, atau NISN">
        <button class="rk-button rk-button-secondary" type="submit">Cari Siswa</button>
    </form>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-4">Siswa</th><th class="px-5 py-4">Jenis Kelamin</th><th class="px-5 py-4">Nilai</th><th class="px-5 py-4">Status Rapor</th><th class="px-5 py-4 text-right">Aksi</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($rows as $row)
                <tr class="hover:bg-slate-50/70"><td class="px-5 py-4"><strong class="block text-[#102d4b]">{{ $row['student']->nama }}</strong><span class="mt-1 block text-xs text-slate-500">NIS {{ $row['student']->nis }} · NISN {{ $row['student']->nisn ?: '—' }}</span></td><td class="px-5 py-4 text-slate-600">{{ $row['student']->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</td><td class="px-5 py-4"><span class="font-bold text-[#102d4b]">{{ $row['overallAverage'] === null ? '—' : number_format($row['overallAverage'], 2, ',', '.') }}</span><small class="block text-slate-500">{{ $row['missingScoreCount'] }} belum diisi</small></td><td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $row['readyToPrint'] ? 'bg-emerald-50 text-emerald-700' : ($row['allScoresComplete'] ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600') }}">{{ $row['reportStatus'] }}</span></td><td class="px-5 py-4 text-right"><a class="font-bold text-emerald-700 hover:text-emerald-800" href="{{ route('guru.wali.siswa.show', ['siswa' => $row['student'], 'tahun_ajaran_id' => $selectedSchoolYear?->id]) }}">Detail</a></td></tr>
            @empty
                <tr><td colspan="5" class="px-6 py-14 text-center text-slate-500">Tidak ada siswa yang sesuai dengan filter atau pencarian.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</section>
@endsection
