@extends('layouts.guru-workspace')

@section('title', 'Preview Rapor '.$studentRow['student']->nama)

@section('content')
    <section class="mx-auto max-w-6xl">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div><a href="{{ route('guru.wali.rapor.index', ['kelas_id' => $selectedClass?->id, 'tahun_ajaran_id' => $selectedSchoolYear?->id]) }}" class="text-sm font-bold text-emerald-700">← Data Rapor</a><p class="rk-eyebrow mt-5">Preview Rapor</p><h1 class="mt-2 text-3xl font-extrabold text-[#102d4b]">{{ $studentRow['student']->nama }}</h1></div>
            <div class="flex flex-wrap gap-3">@if ($studentRow['readyToPrint'])<a href="{{ route('guru.wali.rapor.print', ['siswa' => $studentRow['student'], 'tahun_ajaran_id' => $selectedSchoolYear?->id]) }}" class="rk-button rk-button-primary"><x-nav-icon name="printer" /> Cetak / Simpan PDF</a>@else<span class="rk-button cursor-not-allowed bg-slate-200 text-slate-500" aria-disabled="true" title="Lengkapi data yang tercantum pada status kesiapan rapor.">Cetak Belum Tersedia</span>@endif</div>
        </div>
        @unless ($studentRow['readyToPrint'])
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800" role="status">
                <strong class="block">Rapor belum siap ditinjau.</strong>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @unless ($studentRow['allScoresComplete'])<li>{{ $studentRow['missingScoreCount'] }} nilai tugas belum diisi atau hasil TP belum lengkap. Periksa kemungkinan susulan dan klasifikasi penilaian.</li>@endunless
                    @unless ($studentRow['allDescriptionsValidated'])<li>Deskripsi rapor belum divalidasi untuk seluruh mata pelajaran.</li>@endunless
                </ul>
            </div>
        @endunless
        <article class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <header class="border-b-2 border-[#102d4b] pb-6 text-center"><p class="text-xs font-extrabold uppercase tracking-[0.2em] text-emerald-700">RaporKu</p><h2 class="mt-2 text-2xl font-extrabold text-[#102d4b]">Laporan Hasil Belajar</h2><p class="mt-1 text-sm text-slate-500">{{ $selectedSchoolYear?->tahun }} · Semester {{ $selectedSchoolYear?->semester }}</p></header>
            <dl class="mt-6 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2"><div class="flex justify-between border-b border-slate-100 py-2"><dt class="text-slate-500">Nama</dt><dd class="font-bold">{{ $studentRow['student']->nama }}</dd></div><div class="flex justify-between border-b border-slate-100 py-2"><dt class="text-slate-500">NIS / NISN</dt><dd class="font-bold">{{ $studentRow['student']->nis }} / {{ $studentRow['student']->nisn ?: '—' }}</dd></div><div class="flex justify-between border-b border-slate-100 py-2"><dt class="text-slate-500">Kelas</dt><dd class="font-bold">{{ $selectedClass?->tingkat }} {{ $selectedClass?->nama }}</dd></div><div class="flex justify-between border-b border-slate-100 py-2"><dt class="text-slate-500">Wali Kelas</dt><dd class="font-bold">{{ $guru->nama }}</dd></div></dl>
            <p class="mt-6 rounded-xl bg-blue-50 p-4 text-sm leading-6 text-blue-900">Nilai akhir mata pelajaran belum ditetapkan karena rumus atau bobot khusus sekolah belum dikonfigurasi. Berikut hasil per TP untuk ditinjau; KKTP masing-masing TP menjadi acuannya.</p>
            <div class="mt-8 grid gap-5">
                @foreach ($studentRow['subjects'] as $subject)
                    <section class="rounded-xl border border-slate-200 p-4 sm:p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3"><h3 class="font-extrabold text-[#102d4b]">{{ $subject['assignment']->mataPelajaran->nama }}</h3><span class="text-xs font-semibold text-slate-500">Ringkasan tugas terisi: {{ $subject['average'] === null ? '—' : number_format($subject['average'], 2, ',', '.') }} · bukan nilai akhir</span></div>
                        @foreach ($subject['goalSummaries'] as $goal)
                            <div class="mt-4 rounded-lg bg-slate-50 p-4">
                                <h4 class="text-sm font-bold text-slate-800">TP {{ $goal['goal']->urutan }} · {{ $goal['goal']->tujuan }}</h4>
                                <p class="mt-1 text-xs text-slate-500">KKTP {{ number_format((float) $goal['goal']->kktp, 2, ',', '.') }} · Metode {{ \App\Models\TujuanPembelajaran::METODE_REKAP_LABELS[$goal['goal']->metode_rekap] ?? 'belum dipilih' }}</p>
                                @foreach ($goal['categories'] as $category)
                                    @php($result = $category['rows']->get($studentRow['student']->id))
                                    <p class="mt-2 text-sm"><strong>{{ $category['label'] }}:</strong>
                                        @if ($result['missingCount'] > 0)
                                            {{ $category['isFormative'] ? 'Umpan balik belum lengkap' : ($result['score'] === null ? '—' : 'Hasil sementara: '.number_format($result['score'], 2, ',', '.')) }}
                                            <small class="mt-1 block text-xs text-slate-500">{{ $result['filledCount'] }}/{{ $result['totalCount'] }} asesmen terisi</small>
                                        @else
                                            {{ $category['isFormative'] || $result['score'] === null ? '—' : number_format($result['score'], 2, ',', '.') }}
                                        @endif
                                        · {{ $result['status'] }}
                                    </p>
                                @endforeach
                                @if ($goal['unclassifiedAssessments']->isNotEmpty())<p class="mt-2 text-xs text-amber-800">{{ $goal['unclassifiedAssessments']->count() }} penilaian belum diklasifikasikan.</p>@endif
                            </div>
                        @endforeach
                        <p class="mt-4 border-t border-slate-100 pt-4 text-sm leading-6">{{ $subject['description']?->deskripsi_akhir ?: 'Deskripsi belum ditinjau.' }}</p>
                    </section>
                @endforeach
            </div>
        </article>
    </section>
@endsection
