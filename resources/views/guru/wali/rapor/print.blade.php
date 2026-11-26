php artisan view:clear<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Rapor {{ $studentRow['student']->nama }} - RaporKu</title>

    @include('components.favicon')
    @vite(['resources/css/raporku.css', 'resources/js/app.js'])

    <style>
        .rapor-isi {
            font: 10pt/1.25 Arial, sans-serif;
            color: #000;
        }

        .rapor-halaman {
            box-sizing: border-box;
            max-width: 210mm;
            min-height: 297mm;
            margin: 8mm auto;
            padding: 14mm;
            background: white;
            display: flex;
            flex-direction: column;
        }

        .rapor-isi table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .rapor-isi th,
        .rapor-isi td {
            border: 1px solid #000;
            padding: 4px 5px;
            overflow-wrap: anywhere;
            vertical-align: middle;
        }

        .rapor-isi th {
            font-weight: bold;
            background: #f2f2f5;
            text-align: center;
        }

        .rapor-identitas {
            border-bottom: 1px solid #000;
            padding-bottom: 3mm;
            margin-bottom: 4mm;
        }

        .rapor-isi .rapor-identitas td {
            border: 0;
            padding: 1px 2px;
            vertical-align: top;
        }

        .rapor-judul {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            margin: 0 0 3mm;
        }

        .rapor-isi .tengah {
            text-align: center;
        }

        .rapor-isi .narasi {
            white-space: pre-line;
            text-align: left;
            vertical-align: top;
        }

        .rapor-blok {
            margin-bottom: 3mm;
        }

        .rapor-isi .rapor-dua > tbody > tr > td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }

        .rapor-isi .rapor-dua > tbody > tr > td:first-child {
            padding-right: 4mm;
        }

        .rapor-isi .rapor-ttd td {
            border: 0;
            text-align: center;
            vertical-align: top;
        }

        .rapor-ttd-ruang {
            height: 24mm;
        }

        .rapor-footer {
            border-top: 1px solid #000;
            margin-top: auto;
            padding-top: 2mm;
            display: flex;
            justify-content: space-between;
            gap: 5mm;
            font-size: 8pt;
            font-style: italic;
        }

        .rapor-footer span:last-child {
            white-space: nowrap;
        }

        @media print {
            .rapor-isi {
                font: 10pt/1.25 Arial, sans-serif;
            }

            .rapor-halaman {
                margin: 0;
                padding: 0;
                max-width: none;
                min-height: 267mm;
            }

            .rapor-halaman + .rapor-halaman {
                break-before: page;
                page-break-before: always;
            }

            .rapor-isi thead {
                display: table-header-group;
            }

            .rapor-isi tr,
            .rapor-ttd,
            .rapor-footer {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .rapor-isi th {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>

<body class="bg-slate-100 p-4 text-slate-900 sm:p-8">

    {{-- Tombol navigasi dan cetak rapor --}}
    <div class="no-print mx-auto mb-4 flex max-w-[210mm] items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4">

        <a href="{{ route('guru.wali.rapor.show', [
            'siswa' => $studentRow['student'],
            'tahun_ajaran_id' => $selectedSchoolYear?->id
        ]) }}"
            class="font-bold text-emerald-700">
            ← Kembali ke Preview
        </a>

        <button type="button"
                class="rk-button rk-button-primary"
                data-print-page>
            <x-nav-icon name="printer" />
            Cetak / Simpan PDF
        </button>

    </div>

    {{-- Cover sementara, dipertahankan dari kode sebelumnya --}}
    @if ($printMode === 'lengkap')

        <section class="report-sheet report-cover border border-slate-200 bg-white p-10 shadow-sm"
                 data-report-cover>

            <p class="text-sm font-extrabold uppercase tracking-[0.25em] text-emerald-700">
                RaporKu
            </p>

            <h1 class="mt-8 text-3xl font-extrabold text-[#102d4b]">
                Rapor Hasil Belajar
            </h1>

            <p class="mt-3 text-lg text-slate-600">
                Tahun Ajaran {{ $selectedSchoolYear->tahun }}
                · Semester {{ $selectedSchoolYear->semester }}
            </p>

            <div class="report-cover__identity mt-16 border-y border-slate-300 py-8">

                <h2 class="text-xl font-bold text-[#102d4b]">
                    {{ $school?->nama_sekolah ?: 'Identitas sekolah belum tersedia' }}
                </h2>

                @if ($school?->npsn)
                    <p class="mt-2 text-slate-600">
                        NPSN {{ $school->npsn }}
                    </p>
                @endif

                @if ($school?->alamat)
                    <p class="mt-1 text-slate-600">
                        {{ $school->alamat }}
                    </p>
                @endif

            </div>

            <dl class="report-cover__student mt-12 grid gap-x-8 gap-y-4 text-left sm:grid-cols-2">

                <div>
                    <dt class="text-sm text-slate-500">
                        Nama siswa
                    </dt>

                    <dd class="mt-1 text-lg font-bold text-[#102d4b]">
                        {{ $studentRow['student']->nama }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-slate-500">
                        Kelas
                    </dt>

                    <dd class="mt-1 text-lg font-bold text-[#102d4b]">
                        {{ $selectedClass->tingkat }}
                        {{ $selectedClass->nama }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-slate-500">
                        NIS
                    </dt>

                    <dd class="mt-1 font-semibold">
                        {{ $studentRow['student']->nis }}
                    </dd>
                </div>

                @if ($studentRow['student']->nisn)
                    <div>
                        <dt class="text-sm text-slate-500">
                            NISN
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $studentRow['student']->nisn }}
                        </dd>
                    </div>
                @endif

            </dl>

        </section>

    @endif

    {{-- Menyiapkan data identitas dan fase pembelajaran --}}
    @php
        $murid = $studentRow['student'];

        $tingkat = trim((string) $selectedClass->tingkat);

        $angka = [
            'I' => 1,
            'II' => 2,
            'III' => 3,
            'IV' => 4,
            'V' => 5,
            'VI' => 6
        ];

        $nomorKelas = $angka[strtoupper($tingkat)]
            ?? (ctype_digit($tingkat) ? (int) $tingkat : null);

        $fase = match ($nomorKelas) {
            1, 2 => 'A',
            3, 4 => 'B',
            5, 6 => 'C',
            default => '—'
        };

        $kelasRapor = trim(
            $selectedClass->tingkat . ' ' . $selectedClass->nama
        );

        $semesterRapor = match ($selectedSchoolYear->semester) {
            'Ganjil' => '1',
            'Genap' => '2',
            default => $selectedSchoolYear->semester
        };
    @endphp

    {{-- ISI RAPOR --}}
    <main class="rapor-isi" data-report-content>

        {{-- ====================================== --}}
        {{-- HALAMAN 1: LAPORAN HASIL BELAJAR --}}
        {{-- ====================================== --}}

        <section class="rapor-halaman">

            {{-- Identitas murid dan sekolah --}}
            @include('guru.wali.rapor._identitas-cetak')

            <h1 class="rapor-judul">
                LAPORAN HASIL BELAJAR
            </h1>

            {{-- Tabel nilai dan capaian kompetensi --}}
            <table class="rapor-blok">

                <colgroup>
                    <col style="width:5%">
                    <col style="width:25%">
                    <col style="width:13%">
                    <col style="width:57%">
                </colgroup>

                <thead>
                    <tr>
                        <th scope="col">No</th>
                        <th scope="col">Mata Pelajaran</th>
                        <th scope="col">Nilai Akhir</th>
                        <th scope="col">Capaian Kompetensi</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($studentRow['subjects'] as $subject)

                        <tr>
                            <td class="tengah">
                                {{ $loop->iteration }}
                            </td>

                            <td>
                                {{ $subject['assignment']->mataPelajaran->nama }}
                            </td>

                            <td class="tengah">
                                Belum ditetapkan
                            </td>

                            <td class="narasi">
                                {{ $subject['description']?->deskripsi_akhir
                                    ?: 'Deskripsi belum tersedia.' }}
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="4" class="tengah">
                                Belum ada mata pelajaran.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

            {{-- Informasi sementara nilai akhir --}}
            <p class="rapor-blok">
                Nilai akhir belum ditetapkan karena rumus atau
                bobot pengolahan belum ditetapkan sekolah.
            </p>

            {{-- Footer halaman pertama --}}
            @include('guru.wali.rapor._footer-cetak', [
                'halaman' => 1
            ])

        </section>

        {{-- ====================================== --}}
        {{-- HALAMAN 2: DATA PENDUKUNG RAPOR --}}
        {{-- ====================================== --}}

        <section class="rapor-halaman">

            {{-- Identitas murid dan sekolah --}}
            @include('guru.wali.rapor._identitas-cetak')

            {{-- Kokurikuler --}}
            <table class="rapor-blok">

                <thead>
                    <tr>
                        <th>Kokurikuler</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td class="narasi" style="height:22mm">
                            Belum tersedia.
                        </td>
                    </tr>
                </tbody>

            </table>

            {{-- Ekstrakurikuler --}}
            <table class="rapor-blok">

                <colgroup>
                    <col style="width:5%">
                    <col style="width:30%">
                    <col style="width:65%">
                </colgroup>

                <thead>
                    <tr>
                        <th scope="col">No</th>
                        <th scope="col">Ekstrakurikuler</th>
                        <th scope="col">Keterangan</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($extracurricular as $activity)

                        <tr>
                            <td class="tengah">
                                {{ $loop->iteration }}
                            </td>

                            <td>
                                {{ $activity->nama_kegiatan }}
                            </td>

                            <td class="narasi">
                                {{ $activity->keterangan ?: '—' }}
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="3" class="tengah">
                                Belum tersedia.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

            {{-- Ketidakhadiran dan catatan wali kelas --}}
            <table class="rapor-dua rapor-blok">

                <colgroup>
                    <col style="width:33%">
                    <col style="width:67%">
                </colgroup>

                <tbody>
                    <tr>

                        {{-- Ketidakhadiran --}}
                        <td>

                            <table>

                                <thead>
                                    <tr>
                                        <th colspan="2">
                                            Ketidakhadiran
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @foreach ([
                                        'sakit' => 'Sakit',
                                        'izin' => 'Izin',
                                        'tanpa_keterangan' => 'Tanpa Keterangan'
                                    ] as $field => $label)

                                        <tr>
                                            <td>{{ $label }}</td>

                                            <td class="tengah">
                                                {{ $reportRecord?->$field === null
                                                    ? '—'
                                                    : $reportRecord->$field . ' hari' }}
                                            </td>
                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </td>

                        {{-- Catatan wali kelas --}}
                        <td>

                            <table>

                                <thead>
                                    <tr>
                                        <th>
                                            Catatan Wali Kelas
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr>
                                        <td class="narasi" style="height:20mm">
                                            {{ $reportRecord?->catatan_wali_kelas
                                                ?: 'Belum tersedia.' }}
                                        </td>
                                    </tr>
                                </tbody>

                            </table>

                        </td>

                    </tr>
                </tbody>

            </table>

            {{-- Keterangan kenaikan kelas --}}
            {{-- Hanya ditampilkan pada semester genap --}}
            @if ($selectedSchoolYear->semester === 'Genap')

                <table class="rapor-blok">

                    <tbody>
                        <tr>
                            <td class="tengah">

                                <strong>
                                    Keterangan Kenaikan Kelas :

                                    {{ match ($reportRecord?->keputusan_kenaikan) {
                                        'naik' => 'Naik kelas',
                                        'tidak_naik' => 'Tidak naik kelas',
                                        default => 'Belum ditetapkan'
                                    } }}
                                </strong>

                            </td>
                        </tr>
                    </tbody>

                </table>

            @endif

            {{-- Tanggapan orang tua --}}
            <table class="rapor-blok">

                <thead>
                    <tr>
                        <th>
                            Tanggapan Orang Tua/Wali Murid
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td style="height:20mm">
                            &nbsp;
                        </td>
                    </tr>
                </tbody>

            </table>

            {{-- Tanda tangan rapor --}}
            <table class="rapor-ttd rapor-blok"
                   aria-label="Tanda tangan rapor">

                <tbody>

                    {{-- Tempat dan tanggal pengesahan sementara --}}
                    <tr>
                        <td></td>
                        <td></td>
                        <td>
                            ................, ................
                        </td>
                    </tr>

                    {{-- Jabatan penandatangan --}}
                    <tr>
                        <td>Orang Tua/Wali,</td>
                        <td>Kepala Sekolah</td>
                        <td>Wali Kelas</td>
                    </tr>

                    {{-- Ruang tanda tangan --}}
                    <tr>
                        <td colspan="3" class="rapor-ttd-ruang">
                        </td>
                    </tr>

                    {{-- Nama penandatangan --}}
                    <tr>

                        <td>
                            ................................
                        </td>

                        <td>
                            <strong>
                                <u>
                                    {{ $school?->nama_kepala_sekolah
                                        ?: '................................' }}
                                </u>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <u>{{ $guru->nama }}</u>
                            </strong>
                        </td>

                    </tr>

                    {{-- NIP kepala sekolah dan wali kelas --}}
                    <tr>

                        <td></td>
                        <td>
                            NIP. {{ $school?->nip_kepala_sekolah ?: '—' }}
                        </td>

                        <td>
                            NIP. {{ $guru->nip ?: '—' }}
                        </td>

                    </tr>
                </tbody>
            </table>

            {{-- Footer halaman kedua --}}
            @include('guru.wali.rapor._footer-cetak', [
                'halaman' => 2
            ])

        </section>
    </main>
</body>
</html>
