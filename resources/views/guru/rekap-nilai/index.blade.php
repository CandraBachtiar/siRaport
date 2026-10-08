@extends('layouts.guru-workspace')

@section('title', 'Rekap Nilai')

@section('content')
    <section class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between print:hidden">
            <div><p class="text-sm font-semibold text-emerald-600">Analisis</p><h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Rekap Nilai</h1><p class="mt-2 text-sm text-slate-500">Lihat nilai setiap penilaian, rata-rata akhir, dan status KKM per siswa.</p></div>
            @if ($selectedAssignment !== null)<button type="button" class="rk-button rk-button-secondary" data-print-page><span aria-hidden="true">⎙</span> Cetak Rekap</button>@endif
        </div>

        <form method="GET" action="{{ route('guru.rekap-nilai.index') }}" class="mt-8 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end print:hidden">
            <div class="grid gap-2"><label for="pengampu_id" class="text-sm font-bold text-slate-700">Kelas dan Mata Pelajaran</label><select id="pengampu_id" name="pengampu_id" class="rk-field"><option value="">Pilih tugas mengajar</option>@foreach ($assignments as $assignment)<option value="{{ $assignment->id }}" @selected($selectedAssignment?->id === $assignment->id)>{{ $assignment->mataPelajaran->nama }} · {{ $assignment->kelas->tingkat }} {{ $assignment->kelas->nama }} · {{ $assignment->tahunAjaran->tahun }} {{ $assignment->tahunAjaran->semester }}</option>@endforeach</select></div>
            <button type="submit" class="rk-button rk-button-primary">Tampilkan Rekap</button>
        </form>

        @if ($selectedAssignment === null)
            <div class="mt-6 rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center"><span class="mx-auto grid size-12 place-items-center rounded-xl bg-slate-100 text-slate-500"><x-nav-icon name="rekap" /></span><h2 class="mt-4 font-bold text-slate-700">Belum ada data untuk direkap</h2><p class="mt-2 text-sm text-slate-500">Tugas mengajar belum tersedia untuk akun Anda.</p></div>
        @else
            <div class="mt-6 flex flex-col gap-4 rounded-2xl border border-blue-200 bg-blue-50 p-5 text-sm text-blue-900 sm:flex-row sm:items-center sm:justify-between print:border-slate-300 print:bg-white"><div><p class="text-xs font-bold uppercase tracking-[0.14em] text-blue-600">Rekap Nilai</p><h2 class="mt-1 text-lg font-extrabold">{{ $selectedAssignment->mataPelajaran->nama }} · {{ $selectedAssignment->kelas->tingkat }} {{ $selectedAssignment->kelas->nama }}</h2><p class="mt-1 text-xs">{{ $selectedAssignment->tahunAjaran->tahun }} · {{ $selectedAssignment->tahunAjaran->semester }}</p></div><div class="flex flex-wrap gap-2"><span class="rounded-full bg-white/80 px-3 py-1 text-xs font-bold">KKM {{ number_format((float) $selectedAssignment->mataPelajaran->kkm, 0, ',', '.') }}</span><span class="rounded-full bg-white/80 px-3 py-1 text-xs font-bold">{{ $calculationMode }}</span></div></div>

            @if ($assessments->isEmpty())
                <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center"><h2 class="font-bold text-slate-700">Belum ada penilaian</h2><p class="mt-2 text-sm text-slate-500">Buat penilaian sebelum melihat rekap nilai kelas.</p><a href="{{ route('guru.penilaian.create', ['pengampu_id' => $selectedAssignment->id]) }}" class="rk-button rk-button-primary mt-5 print:hidden">+ Buat Penilaian</a></div>
            @else
                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm print:shadow-none">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <caption class="sr-only">Rekap nilai siswa</caption>
                            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500"><tr><th class="sticky left-0 z-10 min-w-56 bg-slate-50 px-4 py-4">Siswa</th>@foreach ($assessments as $assessment)<th class="min-w-28 px-4 py-4 text-center"><span class="block normal-case text-slate-700">{{ $assessment->nama }}</span><small class="mt-1 block font-medium text-slate-400">{{ $assessment->bobot ? number_format((float) $assessment->bobot, 0).'%' : 'Tanpa bobot' }}</small></th>@endforeach<th class="min-w-28 px-4 py-4 text-center">Rata-rata</th><th class="min-w-36 px-4 py-4">Status</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($rows as $row)
                                    <tr class="hover:bg-slate-50/70"><td class="sticky left-0 bg-white px-4 py-4"><strong class="block text-slate-700">{{ $row['student']->nama }}</strong><span class="text-xs text-slate-400">NIS {{ $row['student']->nis }}</span></td>@foreach ($assessments as $assessment)<td class="px-4 py-4 text-center font-semibold {{ $row['scores'][$assessment->id] !== null && $row['scores'][$assessment->id] < (float) $selectedAssignment->mataPelajaran->kkm ? 'text-rose-700' : 'text-slate-700' }}">{{ $row['scores'][$assessment->id] === null ? '—' : number_format($row['scores'][$assessment->id], 2, ',', '.') }}</td>@endforeach<td class="px-4 py-4 text-center font-extrabold text-[#102d4b]">{{ $row['average'] === null ? '—' : number_format($row['average'], 2, ',', '.') }}</td><td class="px-4 py-4">@if (! $row['isComplete'])<span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">Belum Lengkap</span>@elseif ($row['meetsKkm'])<span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Mencapai KKM</span>@else<span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">Di bawah KKM</span>@endif</td></tr>
                                @empty
                                    <tr><td colspan="{{ $assessments->count() + 3 }}" class="px-5 py-14 text-center"><p class="font-bold text-slate-700">Belum ada siswa di kelas ini</p><p class="mt-1 text-sm text-slate-500">Hubungi administrator untuk memeriksa data kelas.</p></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <p class="mt-4 text-xs leading-5 text-slate-500">Rata-rata berbobot digunakan hanya jika seluruh penilaian memiliki bobot. Jika tidak, sistem menggunakan rata-rata sederhana dari nilai yang sudah diisi.</p>
            @endif
        @endif
    </section>
@endsection
