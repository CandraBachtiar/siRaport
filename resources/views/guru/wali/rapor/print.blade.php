<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapor {{ $studentRow['student']->nama }} - RaporKu</title>
    @include('components.favicon')
    @vite(['resources/css/raporku.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 p-4 text-slate-900 sm:p-8">
    <div class="no-print mx-auto mb-4 flex max-w-[210mm] items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4">
        <a href="{{ route('guru.wali.rapor.show', ['siswa' => $studentRow['student'], 'tahun_ajaran_id' => $selectedSchoolYear?->id]) }}" class="font-bold text-emerald-700">← Kembali ke Preview</a>
        <button type="button" class="rk-button rk-button-primary" data-print-page><x-nav-icon name="printer" /> Cetak / Simpan PDF</button>
    </div>

    @if ($printMode === 'lengkap')
        <section class="report-sheet report-cover border border-slate-200 bg-white p-10 shadow-sm" data-report-cover>
            <p class="text-sm font-extrabold uppercase tracking-[0.25em] text-emerald-700">RaporKu</p>
            <h1 class="mt-8 text-3xl font-extrabold text-[#102d4b]">Rapor Hasil Belajar</h1>
            <p class="mt-3 text-lg text-slate-600">Tahun Ajaran {{ $selectedSchoolYear->tahun }} · Semester {{ $selectedSchoolYear->semester }}</p>

            <div class="report-cover__identity mt-16 border-y border-slate-300 py-8">
                <h2 class="text-xl font-bold text-[#102d4b]">{{ $school?->nama_sekolah ?: 'Identitas sekolah belum tersedia' }}</h2>
                @if ($school?->npsn)<p class="mt-2 text-slate-600">NPSN {{ $school->npsn }}</p>@endif
                @if ($school?->alamat)<p class="mt-1 text-slate-600">{{ $school->alamat }}</p>@endif
            </div>

            <dl class="report-cover__student mt-12 grid gap-x-8 gap-y-4 text-left sm:grid-cols-2">
                <div><dt class="text-sm text-slate-500">Nama siswa</dt><dd class="mt-1 text-lg font-bold text-[#102d4b]">{{ $studentRow['student']->nama }}</dd></div>
                <div><dt class="text-sm text-slate-500">Kelas</dt><dd class="mt-1 text-lg font-bold text-[#102d4b]">{{ $selectedClass->tingkat }} {{ $selectedClass->nama }}</dd></div>
                <div><dt class="text-sm text-slate-500">NIS</dt><dd class="mt-1 font-semibold">{{ $studentRow['student']->nis }}</dd></div>
                @if ($studentRow['student']->nisn)<div><dt class="text-sm text-slate-500">NISN</dt><dd class="mt-1 font-semibold">{{ $studentRow['student']->nisn }}</dd></div>@endif
            </dl>
        </section>
    @endif

    <main class="report-sheet mt-8 bg-white p-7 shadow-sm sm:p-10" data-report-content>
        <header class="border-b-2 border-slate-900 pb-5 text-center">
            <p class="text-xs font-extrabold uppercase tracking-[0.2em]">{{ $school?->nama_sekolah ?: 'RaporKu' }}</p>
            @if ($school?->npsn || $school?->alamat)
                <p class="mt-1 text-xs">{{ $school?->npsn ? 'NPSN '.$school->npsn : '' }}{{ $school?->npsn && $school?->alamat ? ' · ' : '' }}{{ $school?->alamat }}</p>
            @endif
            <h1 class="mt-3 text-2xl font-extrabold">Laporan Hasil Belajar</h1>
            <p class="mt-1 text-sm">Tahun Ajaran {{ $selectedSchoolYear->tahun }} · Semester {{ $selectedSchoolYear->semester }}</p>
        </header>

        <dl class="mt-6 grid grid-cols-2 gap-x-10 gap-y-3 text-sm">
            <div><dt class="text-slate-500">Nama siswa</dt><dd class="mt-1 font-bold">{{ $studentRow['student']->nama }}</dd></div>
            <div><dt class="text-slate-500">Kelas</dt><dd class="mt-1 font-bold">{{ $selectedClass->tingkat }} {{ $selectedClass->nama }}</dd></div>
            <div><dt class="text-slate-500">NIS</dt><dd class="mt-1 font-bold">{{ $studentRow['student']->nis }}</dd></div>
            @if ($studentRow['student']->nisn)<div><dt class="text-slate-500">NISN</dt><dd class="mt-1 font-bold">{{ $studentRow['student']->nisn }}</dd></div>@endif
        </dl>

        <section class="mt-8">
            <h2 class="mb-3 text-lg font-bold">Nilai Intrakurikuler</h2>
            <p class="mb-4 text-sm leading-5">Nilai akhir mata pelajaran belum ditetapkan karena sekolah belum menetapkan rumus atau bobot pengolahannya.</p>
            <table class="report-subject-table">
                <thead><tr><th scope="col">Mata Pelajaran</th><th scope="col">Nilai Akhir</th></tr></thead>
                <tbody>
                    @foreach ($studentRow['subjects'] as $subject)
                        <tr>
                            <td>{{ $subject['assignment']->mataPelajaran->nama }}</td>
                            <td>Belum ditetapkan</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="mt-8 space-y-5">
            <h2 class="text-lg font-bold">Deskripsi Capaian Hasil Belajar</h2>
            @foreach ($studentRow['subjects'] as $subject)
                <article class="report-description border-t border-slate-300 pt-4">
                    <h3 class="font-bold">{{ $subject['assignment']->mataPelajaran->nama }}</h3>
                    <p class="mt-2 leading-6">{{ $subject['description']->deskripsi_akhir ?: 'Deskripsi belum tersedia.' }}</p>
                </article>
            @endforeach
        </section>

        <section class="mt-8 grid gap-5 border-t border-slate-300 pt-5 text-sm sm:grid-cols-2">
            <div>
                <h2 class="font-bold">Ekstrakurikuler</h2>
                @forelse ($extracurricular as $activity)
                    <p class="mt-2">{{ $activity->nama_kegiatan }}{{ $activity->keterangan ? ': '.$activity->keterangan : '' }}</p>
                @empty
                    <p class="mt-2 text-slate-500">Belum tersedia</p>
                @endforelse
            </div>
            <div>
                <h2 class="font-bold">Kehadiran</h2>
                <p class="mt-2">Sakit: {{ $reportRecord?->sakit ?? 'Belum diisi' }}</p>
                <p>Izin: {{ $reportRecord?->izin ?? 'Belum diisi' }}</p>
                <p>Tanpa keterangan: {{ $reportRecord?->tanpa_keterangan ?? 'Belum diisi' }}</p>
            </div>
            <div class="sm:col-span-2">
                <h2 class="font-bold">Catatan Wali Kelas</h2>
                <p class="mt-2 whitespace-pre-line">{{ $reportRecord?->catatan_wali_kelas ?: 'Belum tersedia' }}</p>
            </div>
            @if ($selectedSchoolYear->semester === 'Genap')
                <div class="sm:col-span-2">
                    <h2 class="font-bold">Keputusan Kenaikan Kelas</h2>
                    <p class="mt-2">{{ match ($reportRecord?->keputusan_kenaikan) { 'naik' => 'Naik kelas', 'tidak_naik' => 'Tidak naik kelas', default => 'Belum ditetapkan' } }}</p>
                </div>
            @endif
        </section>

        <section class="report-signatures mt-12 grid grid-cols-3 gap-5 text-center text-sm" aria-label="Tanda tangan rapor">
            <div class="report-signature"><p>Orang Tua/Wali,</p><div class="h-20"></div><p class="border-b border-slate-900 pb-1">&nbsp;</p><p class="mt-1 text-slate-500">Nama belum tersedia</p></div>
            <div class="report-signature"><p>{{ now()->translatedFormat('d F Y') }}</p><p class="mt-1">Wali Kelas,</p><div class="h-20"></div><p class="border-b border-slate-900 pb-1 font-bold">{{ $guru->nama }}</p><p class="mt-1">{{ $guru->nip ? 'NIP '.$guru->nip : 'NIP belum tersedia' }}</p></div>
            <div class="report-signature"><p>Kepala Sekolah,</p><div class="h-20"></div><p class="border-b border-slate-900 pb-1 font-bold">{{ $school?->nama_kepala_sekolah ?: 'Nama belum tersedia' }}</p><p class="mt-1">{{ $school?->nip_kepala_sekolah ? 'NIP '.$school->nip_kepala_sekolah : 'NIP belum tersedia' }}</p></div>
        </section>
    </main>
</body>
</html>
