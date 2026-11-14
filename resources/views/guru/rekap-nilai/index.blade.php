@extends('layouts.guru-workspace')

@section('title', 'Rekap Nilai')

@section('content')
    <section class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between print:hidden">
            <div>
                <p class="text-sm font-semibold text-emerald-600">Analisis</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Rekap Hasil per TP</h1>
                <p class="mt-2 text-sm text-slate-500">Tinjau nilai tugas dan ketercapaian setiap tujuan pembelajaran. Rekap ini belum menjadi nilai akhir mata pelajaran.</p>
            </div>
            @if ($selectedAssignment !== null)
                <button type="button" class="rk-button rk-button-secondary" data-print-page>Cetak Rekap</button>
            @endif
        </div>

        <form method="GET" action="{{ route('guru.rekap-nilai.index') }}" class="mt-8 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end print:hidden">
            <div class="grid gap-2">
                <label for="pengampu_id" class="text-sm font-bold text-slate-700">Tugas Mengajar dan Periode</label>
                <select id="pengampu_id" name="pengampu_id" class="rk-field">
                    <option value="">Pilih tugas mengajar</option>
                    @foreach ($assignments as $assignment)
                        <option value="{{ $assignment->id }}" @selected($selectedAssignment?->id === $assignment->id)>{{ $assignment->mataPelajaran->nama }} · {{ $assignment->kelas->tingkat }} {{ $assignment->kelas->nama }} · {{ $assignment->tahunAjaran->tahun }} — Semester {{ $assignment->tahunAjaran->semester }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rk-button rk-button-primary">Tampilkan Rekap</button>
        </form>

        @if ($selectedAssignment === null)
            <div class="mt-6 rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center">
                <h2 class="font-bold text-slate-700">Belum ada data untuk direkap</h2>
                <p class="mt-2 text-sm text-slate-500">Tugas mengajar belum tersedia untuk akun Anda.</p>
            </div>
        @else
            <div class="mt-6 rounded-2xl border border-blue-200 bg-blue-50 p-5 text-sm text-blue-900 print:border-slate-300 print:bg-white">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-blue-600">Rekap per TP</p>
                <h2 class="mt-1 text-lg font-extrabold">{{ $selectedAssignment->mataPelajaran->nama }} · {{ $selectedAssignment->kelas->tingkat }} {{ $selectedAssignment->kelas->nama }}</h2>
                <p class="mt-1 text-xs">Periode Akademik: {{ $selectedAssignment->tahunAjaran->tahun }} — Semester {{ $selectedAssignment->tahunAjaran->semester }}</p>
                <p class="mt-3 leading-6">Nilai akhir mata pelajaran belum dihitung. Sistem menampilkan hasil TP menurut metode rekap TP yang dipilih; bobot penilaian tidak dianggap sebagai bobot nilai akhir.</p>
            </div>

            @if ($assessments->whereNull('kategori_asesmen')->isNotEmpty())
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900" role="status">
                    <strong>{{ $assessments->whereNull('kategori_asesmen')->count() }} penilaian belum diklasifikasikan.</strong>
                    Periksa kategorinya di <a class="font-bold underline" href="{{ route('guru.penilaian.index', ['pengampu_id' => $selectedAssignment->id]) }}">daftar penilaian</a> agar hasil TP dapat diolah.
                </div>
            @endif

            @if ($assessments->isEmpty())
                <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center">
                    <h2 class="font-bold text-slate-700">Belum ada penilaian</h2>
                    <p class="mt-2 text-sm text-slate-500">Buat penilaian untuk mulai merekap hasil per TP.</p>
                    <a href="{{ route('guru.penilaian.create', ['pengampu_id' => $selectedAssignment->id]) }}" class="rk-button rk-button-primary mt-5 print:hidden">+ Buat Penilaian</a>
                </div>
            @else
                @php
                    $simulationBlockers = $simulations->flatMap(fn (array $simulation) => $simulation['reasons'])->unique()->values();
                    $simulationReady = $goalSummaries->isNotEmpty()
                        && $students->isNotEmpty()
                        && $simulations->every(fn (array $simulation): bool => $simulation['reasons']->isEmpty());
                @endphp
                <section class="mt-5 rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm print:hidden" aria-labelledby="course-score-simulation-title">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-700">Pratinjau pengolahan</p>
                        <h2 id="course-score-simulation-title" class="mt-1 text-lg font-extrabold text-[#102d4b]">Simulasi nilai akhir mata pelajaran</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Simulasi ini hanya pratinjau untuk tugas mengajar dan periode ini, tidak disimpan, serta bukan nilai resmi. Rata-rata cocok untuk TP mandiri; pembobotan atau persentase untuk TP progresif.</p>
                    </div>

                    @unless ($simulationReady)
                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="status">
                            <strong>Simulasi belum dapat dijalankan.</strong>
                            @if ($simulationBlockers->isNotEmpty())
                                <ul class="mt-2 list-inside list-disc leading-6">
                                    @foreach ($simulationBlockers as $reason)
                                        <li>{{ $reason }}</li>
                                    @endforeach
                                </ul>
                            @elseif ($goalSummaries->isEmpty())
                                <p class="mt-1">Siapkan TP terlebih dahulu agar hasil TP memiliki dasar pengolahan.</p>
                            @else
                                <p class="mt-1">Belum ada siswa yang dapat dianalisis pada tugas mengajar ini.</p>
                            @endif
                            @if ($goalSummaries->isEmpty())
                                <a href="{{ route('guru.tujuan-pembelajaran.create', $selectedAssignment) }}" class="mt-2 inline-flex font-bold underline">Siapkan TP</a>
                            @elseif ($students->isNotEmpty())
                                <a href="{{ route('guru.penilaian.index', ['pengampu_id' => $selectedAssignment->id]) }}" class="mt-2 inline-flex font-bold underline">Periksa penilaian dan lengkapi nilai</a>
                            @endif
                        </div>
                    @endunless

                    <form method="GET" action="{{ route('guru.rekap-nilai.index') }}" class="mt-5 grid gap-4 rounded-xl bg-slate-50 p-4">
                        <input type="hidden" name="pengampu_id" value="{{ $selectedAssignment->id }}">
                        <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                            <div class="grid gap-2">
                                <label for="metode_simulasi" class="text-sm font-bold text-slate-700">Metode simulasi</label>
                                <select id="metode_simulasi" name="metode_simulasi" class="rk-field" @disabled(! $simulationReady)>
                                    <option value="">Belum memilih simulasi</option>
                                    @foreach ($simulationMethods as $method => $label)
                                        <option value="{{ $method }}" @selected($simulationMethod === $method)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="rk-button rk-button-primary" @disabled(! $simulationReady)>Hitung Simulasi</button>
                        </div>

                        @if ($simulationMethod === \App\Services\NilaiAkhirSimulasiService::METODE_PEMBOBOTAN)
                            <fieldset class="grid gap-3 border-t border-slate-200 pt-4">
                                <legend class="text-sm font-bold text-slate-700">Bobot setiap TP</legend>
                                <p class="text-xs leading-5 text-slate-500">Masukkan bobot simulasi untuk semua TP. Jumlah harus 100%. Tidak ada bobot bawaan.</p>
                                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($goalSummaries as $summary)
                                        <div class="grid gap-2">
                                            <label for="bobot-tp-{{ $summary['goal']->id }}" class="text-sm font-semibold text-slate-700">TP {{ $summary['goal']->urutan }} · {{ str($summary['goal']->tujuan)->limit(55) }}</label>
                                            <div class="relative">
                                                <input id="bobot-tp-{{ $summary['goal']->id }}" name="bobot_tp[{{ $summary['goal']->id }}]" type="number" min="0" max="100" step="0.01" value="{{ $simulationWeights[$summary['goal']->id] ?? '' }}" class="rk-field pr-12" aria-label="Bobot simulasi TP {{ $summary['goal']->urutan }}">
                                                <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm text-slate-500">%</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endif
                    </form>

                    @if ($goalSummaries->isNotEmpty())
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="border-b border-slate-200 text-xs font-bold uppercase text-slate-500"><tr><th class="min-w-48 px-3 py-3">Siswa</th><th class="min-w-80 px-3 py-3">Sumber hasil TP</th><th class="min-w-64 px-3 py-3">Hasil simulasi mata pelajaran</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($students as $student)
                                    @php($simulation = $simulations->get($student->id))
                                    <tr class="align-top">
                                        <th scope="row" class="px-3 py-4 font-semibold text-slate-700">{{ $student->nama }}<span class="block text-xs font-normal text-slate-400">NIS {{ $student->nis }}</span></th>
                                        <td class="px-3 py-4">
                                            @forelse ($simulation['components'] as $component)
                                                <div class="mb-2 last:mb-0">
                                                    <p class="font-semibold text-slate-700">TP {{ $component['goalOrder'] }} · {{ $component['category'] }}:
                                                        @if ($component['score'] === null)
                                                            Belum lengkap
                                                        @else
                                                            {{ number_format($component['score'], 2, ',', '.') }}
                                                        @endif
                                                    </p>
                                                    @if ($simulationMethod === \App\Services\NilaiAkhirSimulasiService::METODE_PEMBOBOTAN && $component['weight'] !== null)
                                                        <p class="text-xs text-slate-500">Bobot simulasi {{ number_format($component['weight'], 2, ',', '.') }}%</p>
                                                    @endif
                                                    <details class="mt-1 text-xs text-slate-500">
                                                        <summary class="cursor-pointer font-semibold">Rincian sumber</summary>
                                                        <ul class="mt-1 list-inside list-disc">
                                                            @foreach ($component['assessmentScores'] as $source)
                                                                <li>{{ $source['name'] }}: {{ $source['score'] === null ? 'Belum diisi' : number_format($source['score'], 2, ',', '.') }}</li>
                                                            @endforeach
                                                        </ul>
                                                        <p class="mt-1">Metode rekap TP: {{ \App\Models\TujuanPembelajaran::METODE_REKAP_LABELS[$component['method']] ?? 'Belum dipilih' }} · KKTP {{ number_format($component['kktp'], 2, ',', '.') }}</p>
                                                    </details>
                                                </div>
                                            @empty
                                                <span class="text-slate-500">Belum ada sumber hasil TP.</span>
                                            @endforelse
                                        </td>
                                        <td class="px-3 py-4">
                                            @if ($simulation['status'] === 'simulasi')
                                                <strong class="block text-lg tabular-nums text-[#102d4b]">{{ number_format($simulation['nilai'], 2, ',', '.') }}</strong>
                                                <span class="mt-1 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">SIMULASI · Bukan nilai resmi</span>
                                                <p class="mt-2 text-xs leading-5 text-slate-600">{{ $simulation['metodeLabel'] }}</p>
                                            @elseif ($simulation['status'] === 'belum_ditetapkan')
                                                <span class="text-slate-500">Belum dihitung</span>
                                            @else
                                                <strong class="text-amber-800">{{ $simulationReady ? 'Simulasi belum tersedia' : 'Belum siap' }}</strong>
                                                <p class="mt-1 text-xs leading-5 text-slate-600">{{ $simulationReady ? $simulation['message'] : 'Periksa alasan di atas.' }}</p>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </section>

                @forelse ($goalSummaries as $summary)
                    <article class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm print:shadow-none">
                        <header class="border-b border-slate-200 bg-slate-50 p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">TP {{ $summary['goal']->urutan }} · KKTP {{ number_format((float) $summary['goal']->kktp, 2, ',', '.') }}</p>
                                    <h3 class="mt-1 font-bold leading-6 text-[#102d4b]">{{ $summary['goal']->tujuan }}</h3>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600">Metode: {{ \App\Models\TujuanPembelajaran::METODE_REKAP_LABELS[$summary['goal']->metode_rekap] ?? 'Belum dipilih' }}</span>
                            </div>
                        </header>

                        @forelse ($summary['categories'] as $category)
                            <section class="border-b border-slate-100 p-5 last:border-b-0">
                                <h4 class="font-bold text-slate-800">{{ $category['label'] }} @if ($category['isFormative'])<span class="ml-2 text-xs font-medium text-slate-500">(umpan balik, tidak digabung ke hasil sumatif)</span>@endif</h4>
                                <div class="mt-4 overflow-x-auto">
                                    <table class="min-w-full text-left text-sm">
                                        <caption class="sr-only">{{ $category['label'] }} untuk TP {{ $summary['goal']->urutan }}</caption>
                                        <thead class="border-b border-slate-200 text-xs font-bold text-slate-500"><tr><th class="min-w-44 px-3 py-3">Siswa</th>@foreach ($category['assessments'] as $assessment)<th class="min-w-28 px-3 py-3 text-center">{{ $assessment->nama }}</th>@endforeach<th class="min-w-28 px-3 py-3 text-center">{{ $category['isFormative'] ? 'Ringkasan' : 'Hasil kategori' }}</th><th class="min-w-44 px-3 py-3">Keterangan</th></tr></thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @forelse ($students as $student)
                                                @php($result = $category['rows']->get($student->id))
                                                <tr>
                                                    <th scope="row" class="px-3 py-3 font-semibold text-slate-700">{{ $student->nama }}<span class="block text-xs font-normal text-slate-400">NIS {{ $student->nis }}</span></th>
                                                    @foreach ($category['assessments'] as $assessment)
                                                        @php($score = $result['scores']->get($assessment->id))
                                                        <td class="px-3 py-3 text-center tabular-nums {{ $score === null ? 'text-slate-400' : 'text-slate-700' }}">{{ $score === null ? 'Belum diisi' : number_format($score, 2, ',', '.') }}</td>
                                                    @endforeach
                                                    <td class="px-3 py-3 text-center font-extrabold tabular-nums text-[#102d4b]">
                                                        @if ($result['missingCount'] > 0)
                                                            <span class="block">{{ $category['isFormative'] ? 'Umpan balik belum lengkap' : ($result['score'] === null ? '—' : 'Hasil sementara: '.number_format($result['score'], 2, ',', '.')) }}</span>
                                                            <small class="mt-1 block font-normal text-slate-500">{{ $result['filledCount'] }}/{{ $result['totalCount'] }} asesmen terisi</small>
                                                        @else
                                                            {{ $category['isFormative'] || $result['score'] === null ? '—' : number_format($result['score'], 2, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-3 text-xs font-semibold {{ $result['attainment'] === false ? 'text-rose-700' : ($result['attainment'] === true ? 'text-emerald-700' : 'text-slate-500') }}">{{ $result['status'] }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="{{ $category['assessments']->count() + 3 }}" class="px-3 py-6 text-center text-slate-500">Belum ada siswa di kelas ini.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if ($category['isFormative'])
                                    <p class="mt-3 text-xs leading-5 text-slate-500">Nilai formatif tetap ditampilkan per tugas untuk bahan umpan balik. Tidak dipakai menentukan hasil sumatif TP.</p>
                                @endif
                            </section>
                        @empty
                            <p class="p-5 text-sm text-slate-500">Belum ada penilaian yang diklasifikasikan untuk TP ini.</p>
                        @endforelse
                    </article>
                @empty
                    <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">
                        <strong class="text-slate-800">Belum ada TP untuk rekap.</strong>
                        <p class="mt-1">Siapkan TP terlebih dahulu. Tabel hasil TP akan muncul setelah TP dan penilaiannya tersedia.</p>
                        <a href="{{ route('guru.tujuan-pembelajaran.create', $selectedAssignment) }}" class="mt-3 inline-flex font-bold text-emerald-700 underline print:hidden">Siapkan TP</a>
                    </div>
                @endforelse
            @endif
        @endif
    </section>
@endsection
