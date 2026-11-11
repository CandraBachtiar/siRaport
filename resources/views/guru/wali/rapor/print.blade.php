<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapor {{ $studentRow['student']->nama }} - RaporKu</title>
    @include('components.favicon')
    @vite(['resources/css/raporku.css', 'resources/js/app.js'])
    <style>@page{size:A4;margin:16mm}.report-sheet{max-width:210mm;margin:0 auto}.print-only{display:none}@media print{body{background:#fff}.no-print{display:none!important}.print-only{display:block}.report-sheet{max-width:none}.report-table{font-size:10pt}}</style>
</head>
<body class="bg-slate-100 p-4 text-slate-900 sm:p-8">
    <div class="no-print mx-auto mb-4 flex max-w-[210mm] items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4"><a href="{{ route('guru.wali.rapor.show', ['siswa' => $studentRow['student'], 'tahun_ajaran_id' => $selectedSchoolYear?->id]) }}" class="font-bold text-emerald-700">← Kembali ke Preview</a><button type="button" class="rk-button rk-button-primary" data-print-page><x-nav-icon name="printer" /> Cetak / Simpan PDF</button></div>
    <main class="report-sheet bg-white p-7 shadow-sm sm:p-10">
        <header class="border-b-2 border-slate-900 pb-5 text-center"><p class="text-xs font-extrabold uppercase tracking-[0.2em]">RaporKu</p><h1 class="mt-2 text-2xl font-extrabold">Laporan Hasil Belajar</h1><p class="mt-1 text-sm">Tahun Ajaran {{ $selectedSchoolYear->tahun }} · Semester {{ $selectedSchoolYear->semester }}</p></header>
        <dl class="mt-6 grid grid-cols-2 gap-x-10 gap-y-2 text-sm"><div><dt class="inline text-slate-500">Nama: </dt><dd class="inline font-bold">{{ $studentRow['student']->nama }}</dd></div><div><dt class="inline text-slate-500">NIS/NISN: </dt><dd class="inline font-bold">{{ $studentRow['student']->nis }} / {{ $studentRow['student']->nisn ?: '—' }}</dd></div><div><dt class="inline text-slate-500">Kelas: </dt><dd class="inline font-bold">{{ $selectedClass->tingkat }} {{ $selectedClass->nama }}</dd></div><div><dt class="inline text-slate-500">Wali Kelas: </dt><dd class="inline font-bold">{{ $guru->nama }}</dd></div></dl>
        <p class="mt-6 border border-slate-300 p-3 text-sm leading-5">Nilai akhir mata pelajaran belum ditetapkan; rumus atau bobot khusus sekolah belum dikonfigurasi. Hasil berikut ditampilkan per TP berdasarkan KKTP masing-masing.</p>
        <div class="mt-6 grid gap-5">
            @foreach ($studentRow['subjects'] as $subject)
                <section class="break-inside-avoid border border-slate-300 p-4">
                    <h2 class="font-bold">{{ $subject['assignment']->mataPelajaran->nama }}</h2>
                    <p class="mt-1 text-xs">Ringkasan tugas terisi: {{ $subject['average'] === null ? '—' : number_format($subject['average'], 2, ',', '.') }} · bukan nilai akhir</p>
                    @foreach ($subject['goalSummaries'] as $goal)
                        <div class="mt-3 border-t border-slate-200 pt-3">
                            <h3 class="text-sm font-bold">TP {{ $goal['goal']->urutan }} · {{ $goal['goal']->tujuan }}</h3>
                            <p class="text-xs">KKTP {{ number_format((float) $goal['goal']->kktp, 2, ',', '.') }} · Metode {{ \App\Models\TujuanPembelajaran::METODE_REKAP_LABELS[$goal['goal']->metode_rekap] ?? 'belum dipilih' }}</p>
                            @foreach ($goal['categories'] as $category)
                                @php($result = $category['rows']->get($studentRow['student']->id))
                                <p class="mt-1 text-sm"><strong>{{ $category['label'] }}:</strong>
                                    @if ($result['missingCount'] > 0)
                                        {{ $category['isFormative'] ? 'Umpan balik belum lengkap' : ($result['score'] === null ? '—' : 'Hasil sementara: '.number_format($result['score'], 2, ',', '.')) }}
                                        <small class="block text-xs">{{ $result['filledCount'] }}/{{ $result['totalCount'] }} asesmen terisi</small>
                                    @else
                                        {{ $category['isFormative'] || $result['score'] === null ? '—' : number_format($result['score'], 2, ',', '.') }}
                                    @endif
                                    · {{ $result['status'] }}
                                </p>
                            @endforeach
                            @if ($goal['unclassifiedAssessments']->isNotEmpty())<p class="mt-1 text-xs">{{ $goal['unclassifiedAssessments']->count() }} penilaian belum diklasifikasikan.</p>@endif
                        </div>
                    @endforeach
                    <p class="mt-3 border-t border-slate-200 pt-3 text-sm">{{ $subject['description']->deskripsi_akhir }}</p>
                </section>
            @endforeach
        </div>
        <div class="mt-12 ml-auto w-64 text-center text-sm"><p>{{ now()->translatedFormat('d F Y') }}</p><p class="mt-1">Wali Kelas,</p><div class="h-20"></div><p class="border-b border-slate-900 pb-1 font-bold">{{ $guru->nama }}</p><p class="mt-1">NIP {{ $guru->nip ?: '—' }}</p></div>
    </main>
</body>
</html>
