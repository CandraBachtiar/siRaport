@extends('layouts.guru-workspace')

@section('title', 'Perkembangan Siswa')

@section('content')
<section class="mx-auto max-w-7xl">
    <div><p class="rk-eyebrow">Analisis Lintas Mata Pelajaran</p><h1 class="mt-2 text-3xl font-extrabold text-[#102d4b]">Perkembangan Siswa</h1><p class="mt-2 text-sm text-slate-500">Lihat satu siswa secara utuh dari seluruh mata pelajaran pada periode terpilih.</p></div>
    <div class="mt-7">@include('guru.wali.partials.filters', ['filterAction' => route('guru.wali.perkembangan.index')])</div>

    <form method="GET" action="{{ route('guru.wali.perkembangan.index') }}" class="mt-5 rounded-2xl border border-slate-200 bg-white p-5"><input type="hidden" name="kelas_id" value="{{ $selectedClass?->id }}"><input type="hidden" name="tahun_ajaran_id" value="{{ $selectedSchoolYear?->id }}"><label for="siswa_id" class="text-xs font-bold text-slate-600">Pilih siswa</label><div class="mt-2 flex flex-col gap-3 sm:flex-row"><select id="siswa_id" name="siswa_id" class="rk-field">@foreach ($studentRows as $row)<option value="{{ $row['student']->id }}" @selected($studentRow && $studentRow['student']->id === $row['student']->id)>{{ $row['student']->nama }} · {{ $row['student']->nis }}</option>@endforeach</select><button type="submit" class="rk-button rk-button-primary">Tampilkan</button></div></form>

    @if ($studentRow)
        <div class="mt-6 grid gap-4 sm:grid-cols-4">@foreach ([['Rata-rata', $studentRow['overallAverage'] === null ? '—' : number_format($studentRow['overallAverage'], 2, ',', '.')], ['Mapel di bawah KKM', $studentRow['belowKkmCount']], ['Nilai belum lengkap', $studentRow['missingScoreCount']], ['Status rapor', $studentRow['reportStatus']]] as [$label, $value])<div class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs font-bold uppercase text-slate-400">{{ $label }}</p><strong class="mt-3 block text-xl text-[#102d4b]">{{ $value }}</strong></div>@endforeach</div>
        <div class="mt-6 grid gap-6 xl:grid-cols-2"><article class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6"><h2 class="font-bold text-[#102d4b]">Perbandingan Mata Pelajaran</h2><p class="mt-1 text-sm text-slate-500">Rata-rata setiap mata pelajaran, bukan prediksi.</p><x-charts.bar-chart :items="$studentRow['subjectSeries']" title="Rata-rata mata pelajaran siswa" class="mt-5" /></article><article class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6"><h2 class="font-bold text-[#102d4b]">Perkembangan Berdasarkan Waktu</h2><p class="mt-1 text-sm text-slate-500">Urutan seluruh penilaian berdasarkan tanggal.</p><x-charts.line-chart :points="$studentRow['progressSeries']" title="Perkembangan nilai siswa lintas mata pelajaran" class="mt-5" /></article></div>
        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">@foreach ($studentRow['subjects'] as $subject)<article class="rounded-2xl border border-slate-200 bg-white p-5"><div class="flex items-start justify-between gap-3"><h3 class="font-bold text-[#102d4b]">{{ $subject['assignment']->mataPelajaran->nama }}</h3><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $subject['meetsKkm'] ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">{{ $subject['average'] === null ? 'Belum dinilai' : ($subject['meetsKkm'] ? 'Tuntas' : 'Di bawah KKM') }}</span></div><p class="mt-4 text-3xl font-extrabold text-[#102d4b]">{{ $subject['average'] === null ? '—' : number_format($subject['average'], 2, ',', '.') }}</p><p class="mt-3 text-sm leading-6 text-slate-500">{{ $subject['trend']['explanation'] }}</p><p class="mt-3 text-xs font-bold text-slate-600">Tren: {{ $subject['trend']['label'] }}</p></article>@endforeach</div>
    @else
        <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center text-sm text-slate-500">Belum ada siswa pada kelas terpilih.</div>
    @endif
</section>
@endsection
