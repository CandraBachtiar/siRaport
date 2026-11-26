@extends('layouts.guru-workspace')

@section('title', 'Preview Rapor '.$studentRow['student']->nama)

@section('content')
    <section class="mx-auto max-w-6xl">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('guru.wali.rapor.index', ['kelas_id' => $selectedClass?->id, 'tahun_ajaran_id' => $selectedSchoolYear?->id]) }}"
                    class="text-sm font-bold text-emerald-700">
                    ← Data Rapor
                </a>

                <p class="rk-eyebrow mt-5">
                    Preview Rapor
                </p>

                <h1 class="mt-2 text-3xl font-extrabold text-[#102d4b]">
                    {{ $studentRow['student']->nama }}
                </h1>
            </div>

            <div class="flex flex-wrap gap-3">
                @if ($studentRow['readyToPrint'])
                    <a href="{{ route('guru.wali.rapor.print', ['siswa' => $studentRow['student'], 'tahun_ajaran_id' => $selectedSchoolYear?->id, 'format' => 'lengkap']) }}"
                        class="rk-button rk-button-primary">
                        <x-nav-icon name="printer" />
                        Cetak Lengkap
                    </a>

                    <a href="{{ route('guru.wali.rapor.print', ['siswa' => $studentRow['student'], 'tahun_ajaran_id' => $selectedSchoolYear?->id, 'format' => 'isi']) }}"
                        class="rk-button rk-button-secondary">
                        <x-nav-icon name="printer" />
                        Cetak Isi
                    </a>
                @else
                    <span class="rk-button cursor-not-allowed bg-slate-200 text-slate-500"
                        aria-disabled="true"
                        title="Lengkapi data yang tercantum pada status kesiapan rapor.">
                        Cetak Lengkap Belum Tersedia
                    </span>

                    <span class="rk-button cursor-not-allowed bg-slate-200 text-slate-500"
                        aria-disabled="true"
                        title="Lengkapi data yang tercantum pada status kesiapan rapor.">
                        Cetak Isi Belum Tersedia
                    </span>
                @endif
            </div>
        </div>

        <a href="{{ route('guru.wali.rapor.data.edit', ['siswa' => $studentRow['student'], 'tahun_ajaran_id' => $selectedSchoolYear?->id]) }}"
            class="rk-button rk-button-secondary mt-4">
            Kelola Data Rapor
        </a>

        @unless ($studentRow['readyToPrint'])
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800"
                role="status">

                <strong class="block">
                    Rapor belum siap dicetak.
                </strong>

                <ul class="mt-2 list-inside list-disc space-y-1">
                    @unless ($studentRow['allScoresComplete'])
                        <li>
                            {{ $studentRow['missingScoreCount'] }} nilai
                            tugas belum diisi atau hasil TP belum lengkap.
                            Periksa kemungkinan susulan dan klasifikasi
                            penilaian.
                        </li>
                    @endunless

                    @unless ($studentRow['allDescriptionsValidated'])
                        <li>
                            Deskripsi rapor belum divalidasi untuk
                            seluruh mata pelajaran.
                        </li>
                    @endunless
                </ul>
            </div>
        @endunless

        <article class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <header class="border-b-2 border-[#102d4b] pb-6 text-center">
                <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-emerald-700">
                    RaporKu
                </p>

                <h2 class="mt-2 text-2xl font-extrabold text-[#102d4b]">
                    Laporan Hasil Belajar
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $selectedSchoolYear?->tahun }} ·
                    Semester {{ $selectedSchoolYear?->semester }}
                </p>
            </header>

            <dl class="mt-6 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                <div class="flex justify-between gap-4 border-b border-slate-100 py-2">
                    <dt class="text-slate-500">
                        Nama
                    </dt>

                    <dd class="text-right font-bold">
                        {{ $studentRow['student']->nama }}
                    </dd>
                </div>

                <div class="flex justify-between gap-4 border-b border-slate-100 py-2">
                    <dt class="text-slate-500">
                        NIS / NISN
                    </dt>

                    <dd class="text-right font-bold">
                        {{ $studentRow['student']->nis }} /
                        {{ $studentRow['student']->nisn ?: '—' }}
                    </dd>
                </div>

                <div class="flex justify-between gap-4 border-b border-slate-100 py-2">
                    <dt class="text-slate-500">
                        Kelas
                    </dt>

                    <dd class="text-right font-bold">
                        {{ $selectedClass?->tingkat }}
                        {{ $selectedClass?->nama }}
                    </dd>
                </div>

                <div class="flex justify-between gap-4 border-b border-slate-100 py-2">
                    <dt class="text-slate-500">
                        Wali Kelas
                    </dt>

                    <dd class="text-right font-bold">
                        {{ $guru->nama }}
                    </dd>
                </div>
            </dl>

            <p class="mt-6 rounded-xl bg-blue-50 p-4 text-sm leading-6 text-blue-900">
                Nilai akhir mata pelajaran belum ditetapkan karena
                rumus atau bobot khusus sekolah belum dikonfigurasi.
                Berikut hasil per TP untuk ditinjau; KKTP masing-masing
                TP menjadi acuannya.
            </p>

            <div class="mt-8 grid gap-5">
                @foreach ($studentRow['subjects'] as $subject)
                    <section class="rounded-xl border border-slate-200 p-4 sm:p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <h3 class="font-extrabold text-[#102d4b]">
                                {{ $subject['assignment']->mataPelajaran->nama }}
                            </h3>

                            <span class="text-xs font-semibold text-slate-500">
                                Ringkasan tugas terisi:
                                {{ $subject['average'] === null ? '—' : number_format($subject['average'], 2, ',', '.') }}
                                · bukan nilai akhir
                            </span>
                        </div>

                        @foreach ($subject['goalSummaries'] as $goal)
                            <div class="mt-4 rounded-lg bg-slate-50 p-4">
                                <h4 class="text-sm font-bold text-slate-800">
                                    TP {{ $goal['goal']->urutan }} ·
                                    {{ $goal['goal']->tujuan }}
                                </h4>

                                <p class="mt-1 text-xs text-slate-500">
                                    KKTP
                                    {{ number_format((float) $goal['goal']->kktp, 2, ',', '.') }}
                                    · Metode
                                    {{ \App\Models\TujuanPembelajaran::METODE_REKAP_LABELS[$goal['goal']->metode_rekap] ?? 'belum dipilih' }}
                                </p>

                                @foreach ($goal['categories'] as $category)
                                    @php
                                        $result = $category['rows']->get(
                                            $studentRow['student']->id
                                        );
                                    @endphp

                                    <p class="mt-2 text-sm">
                                        <strong>
                                            {{ $category['label'] }}:
                                        </strong>

                                        @if ($result['missingCount'] > 0)
                                            {{ $category['isFormative'] ? 'Umpan balik belum lengkap' : ($result['score'] === null ? '—' : 'Hasil sementara: '.number_format($result['score'], 2, ',', '.')) }}

                                            <small class="mt-1 block text-xs text-slate-500">
                                                {{ $result['filledCount'] }}/{{ $result['totalCount'] }}
                                                asesmen terisi
                                            </small>
                                        @else
                                            {{ $category['isFormative'] || $result['score'] === null ? '—' : number_format($result['score'], 2, ',', '.') }}
                                        @endif

                                        · {{ $result['status'] }}
                                    </p>
                                @endforeach

                                @if ($goal['unclassifiedAssessments']->isNotEmpty())
                                    <p class="mt-2 text-xs text-amber-800">
                                        {{ $goal['unclassifiedAssessments']->count() }}
                                        penilaian belum diklasifikasikan.
                                    </p>
                                @endif
                            </div>
                        @endforeach

                        <p class="mt-4 border-t border-slate-100 pt-4 text-sm leading-6">
                            @if ($subject['description'] === null)
                                Deskripsi belum ditinjau.
                            @elseif ($subject['descriptionIsCurrent'])
                                {{ $subject['description']->deskripsi_akhir }}
                            @else
                                <strong class="text-amber-800">
                                    Draf deskripsi tersimpan;
                                    perlu ditinjau ulang.
                                </strong>

                                {{ $subject['description']->deskripsi_akhir }}
                            @endif
                        </p>
                    </section>
                @endforeach
            </div>

            <section class="mt-8 border-t border-slate-200 pt-6">
                <h3 class="text-lg font-extrabold text-[#102d4b]">
                    Kokurikuler
                </h3>

                @if (filled($reportRecord?->kokurikuler))
                    <p class="mt-3 whitespace-pre-line break-words rounded-xl bg-slate-50 p-4 text-sm leading-7 text-slate-700">{{ $reportRecord->kokurikuler }}</p>
                @else
                    <p class="mt-3 rounded-xl bg-slate-50 p-4 text-sm text-slate-500">
                        Deskripsi kokurikuler belum diisi.
                    </p>
                @endif
            </section>

            <section class="mt-8 border-t border-slate-200 pt-6">
                <h3 class="text-lg font-extrabold text-[#102d4b]">
                    Ekstrakurikuler
                </h3>

                @if ($extracurricular->isNotEmpty())
                    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-slate-700">
                                <tr>
                                    <th scope="col" class="px-4 py-3">
                                        No.
                                    </th>

                                    <th scope="col" class="px-4 py-3">
                                        Kegiatan
                                    </th>

                                    <th scope="col" class="px-4 py-3">
                                        Keterangan
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                @foreach ($extracurricular as $activity)
                                    <tr class="align-top">
                                        <td class="px-4 py-3 text-slate-500">
                                            {{ $loop->iteration }}
                                        </td>

                                        <td class="px-4 py-3 font-semibold text-slate-800">
                                            {{ $activity->nama_kegiatan }}
                                        </td>

                                        <td class="whitespace-pre-line break-words px-4 py-3 leading-6 text-slate-700">{{ $activity->keterangan ?: 'Belum diisi' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mt-3 rounded-xl bg-slate-50 p-4 text-sm text-slate-500">
                        Data ekstrakurikuler belum tersedia.
                    </p>
                @endif
            </section>

            <section class="mt-8 border-t border-slate-200 pt-6">
                <h3 class="text-lg font-extrabold text-[#102d4b]">
                    Ketidakhadiran
                </h3>

                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    @foreach ([
                        'sakit' => 'Sakit',
                        'izin' => 'Izin',
                        'tanpa_keterangan' => 'Tanpa Keterangan'
                    ] as $field => $label)

                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-sm font-medium text-slate-500">
                                {{ $label }}
                            </p>

                            @if ($reportRecord?->$field !== null)
                                <p class="mt-2 text-2xl font-extrabold text-[#102d4b]">
                                    {{ $reportRecord->$field }}

                                    <span class="text-sm font-medium text-slate-500">
                                        hari
                                    </span>
                                </p>
                            @else
                                <p class="mt-2 text-sm text-slate-500">
                                    Belum diisi
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="mt-8 border-t border-slate-200 pt-6">
                <h3 class="text-lg font-extrabold text-[#102d4b]">
                    Catatan Wali Kelas
                </h3>

                @if (filled($reportRecord?->catatan_wali_kelas))
                    <p class="mt-3 whitespace-pre-line break-words rounded-xl bg-slate-50 p-4 text-sm leading-7 text-slate-700">{{ $reportRecord->catatan_wali_kelas }}</p>
                @else
                    <p class="mt-3 rounded-xl bg-slate-50 p-4 text-sm text-slate-500">
                        Catatan wali kelas belum diisi.
                    </p>
                @endif
            </section>

            @if ($selectedSchoolYear?->semester === 'Genap')
                <section class="mt-8 border-t border-slate-200 pt-6">
                    <h3 class="text-lg font-extrabold text-[#102d4b]">
                        Keputusan Kenaikan Kelas
                    </h3>

                    @if ($reportRecord?->keputusan_kenaikan === 'naik')
                        <div class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                            <p class="font-bold text-emerald-700">
                                Naik Kelas
                            </p>

                            <p class="mt-1 text-sm text-emerald-700">
                                Keputusan kenaikan kelas telah tercatat.
                            </p>
                        </div>
                    @elseif ($reportRecord?->keputusan_kenaikan === 'tidak_naik')
                        <div class="mt-3 rounded-xl border border-red-200 bg-red-50 p-4">
                            <p class="font-bold text-red-700">
                                Tidak Naik Kelas
                            </p>

                            <p class="mt-1 text-sm text-red-700">
                                Siswa tetap berada pada tingkat kelas
                                yang sama pada tahun ajaran berikutnya.
                            </p>
                        </div>
                    @else
                        <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <p class="font-bold text-amber-800">
                                Belum Ditetapkan
                            </p>

                            <p class="mt-1 text-sm text-amber-800">
                                Keputusan kenaikan kelas belum tercatat.
                                Wali kelas dapat mencatat keputusan
                                melalui halaman Kelola Data Rapor
                                apabila periode dan riwayat siswa
                                memenuhi ketentuan.
                            </p>
                        </div>
                    @endif
                </section>
            @endif

            <section class="mt-8 border-t border-slate-200 pt-6">
                <h3 class="text-lg font-extrabold text-[#102d4b]">
                    Identitas Sekolah
                </h3>

                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-500">
                            Nama Sekolah
                        </dt>

                        <dd class="mt-1 font-semibold text-slate-800">
                            {{ $school?->nama_sekolah ?: 'Belum tersedia' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-slate-500">
                            NPSN
                        </dt>

                        <dd class="mt-1 font-semibold text-slate-800">
                            {{ $school?->npsn ?: 'Belum tersedia' }}
                        </dd>
                    </div>

                    <div class="sm:col-span-2">
                        <dt class="text-slate-500">
                            Alamat Sekolah
                        </dt>

                        <dd class="mt-1 font-semibold text-slate-800">
                            {{ $school?->alamat ?: 'Belum tersedia' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-slate-500">
                            Kepala Sekolah
                        </dt>

                        <dd class="mt-1 font-semibold text-slate-800">
                            {{ $school?->nama_kepala_sekolah ?: 'Belum tersedia' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-slate-500">
                            NIP Kepala Sekolah
                        </dt>

                        <dd class="mt-1 font-semibold text-slate-800">
                            {{ $school?->nip_kepala_sekolah ?: 'Belum tersedia' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="mt-8 grid gap-4 border-t border-slate-200 pt-6 text-sm sm:grid-cols-3"
                aria-label="Penanda tangan rapor">

                <div>
                    <h3 class="font-bold text-[#102d4b]">
                        Orang Tua/Wali
                    </h3>

                    <p class="mt-2 text-slate-500">
                        Nama belum tersedia
                    </p>
                </div>

                <div>
                    <h3 class="font-bold text-[#102d4b]">
                        Wali Kelas
                    </h3>

                    <p class="mt-2">
                        {{ $guru->nama }}
                    </p>

                    <p class="text-slate-500">
                        {{ $guru->nip ? 'NIP '.$guru->nip : 'NIP belum tersedia' }}
                    </p>
                </div>

                <div>
                    <h3 class="font-bold text-[#102d4b]">
                        Kepala Sekolah
                    </h3>

                    <p class="mt-2">
                        {{ $school?->nama_kepala_sekolah ?: 'Nama belum tersedia' }}
                    </p>

                    <p class="text-slate-500">
                        {{ $school?->nip_kepala_sekolah ? 'NIP '.$school->nip_kepala_sekolah : 'NIP belum tersedia' }}
                    </p>
                </div>
            </section>
        </article>
    </section>
@endsection