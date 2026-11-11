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
                <label for="pengampu_id" class="text-sm font-bold text-slate-700">Kelas dan Mata Pelajaran</label>
                <select id="pengampu_id" name="pengampu_id" class="rk-field">
                    <option value="">Pilih tugas mengajar</option>
                    @foreach ($assignments as $assignment)
                        <option value="{{ $assignment->id }}" @selected($selectedAssignment?->id === $assignment->id)>{{ $assignment->mataPelajaran->nama }} · {{ $assignment->kelas->tingkat }} {{ $assignment->kelas->nama }} · {{ $assignment->tahunAjaran->tahun }} {{ $assignment->tahunAjaran->semester }}</option>
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
                <p class="mt-1 text-xs">{{ $selectedAssignment->tahunAjaran->tahun }} · {{ $selectedAssignment->tahunAjaran->semester }}</p>
                <p class="mt-3 leading-6">Nilai akhir mata pelajaran belum dihitung. Sistem menampilkan hasil TP menurut metode rekap TP yang dipilih; bobot penilaian tidak dianggap sebagai bobot nilai akhir.</p>
            </div>

            @if ($assessments->whereNull('kategori_asesmen')->isNotEmpty())
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900" role="status">
                    <strong>{{ $assessments->whereNull('kategori_asesmen')->count() }} penilaian belum diklasifikasikan.</strong>
                    Jenis lama seperti tugas, ulangan harian, UTS, atau UAS tidak dipetakan otomatis. Klasifikasikan setelah memastikan kategorinya.
                </div>
            @endif

            @if ($assessments->isEmpty())
                <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center">
                    <h2 class="font-bold text-slate-700">Belum ada penilaian</h2>
                    <p class="mt-2 text-sm text-slate-500">Buat penilaian untuk mulai merekap hasil per TP.</p>
                    <a href="{{ route('guru.penilaian.create', ['pengampu_id' => $selectedAssignment->id]) }}" class="rk-button rk-button-primary mt-5 print:hidden">+ Buat Penilaian</a>
                </div>
            @else
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
                                        <thead class="border-b border-slate-200 text-xs font-bold text-slate-500"><tr><th class="min-w-44 px-3 py-3">Siswa</th>@foreach ($category['assessments'] as $assessment)<th class="min-w-28 px-3 py-3 text-center">{{ $assessment->nama }}</th>@endforeach<th class="min-w-28 px-3 py-3 text-center">{{ $category['isFormative'] ? 'Ringkasan' : 'Hasil TP' }}</th><th class="min-w-44 px-3 py-3">Keterangan</th></tr></thead>
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
                    <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">Belum ada TP yang terkait penilaian. Hubungkan penilaian ke TP setelah kesesuaiannya diperiksa.</div>
                @endforelse
            @endif
        @endif
    </section>
@endsection
