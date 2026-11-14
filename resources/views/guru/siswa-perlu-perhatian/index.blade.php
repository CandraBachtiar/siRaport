@extends('layouts.guru-workspace')

@section('title', 'Siswa Perlu Perhatian')

@section('content')
    <section class="mx-auto max-w-7xl">
        <div><p class="text-sm font-semibold text-emerald-600">Analisis</p><h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Siswa Perlu Perhatian</h1><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Periksa siapa yang perlu ditinjau, alasan yang muncul, dan penilaian terkait.</p></div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Siswa Perlu Perhatian', 'value' => $summary['studentCount'], 'tone' => 'bg-blue-50 text-blue-700'],
                ['label' => 'Siswa dengan Nilai Belum Lengkap', 'value' => $summary['incompleteCount'], 'tone' => 'bg-slate-100 text-slate-700'],
                ['label' => 'Siswa dengan Tren Menurun', 'value' => $summary['decliningCount'], 'tone' => 'bg-amber-50 text-amber-700'],
                ['label' => 'Siswa dengan TP di Bawah KKTP', 'value' => $summary['belowKktpCount'], 'tone' => 'bg-rose-50 text-rose-700'],
            ] as $stat)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-bold {{ $stat['tone'] }}">{{ $stat['label'] }}</span><p class="mt-4 text-3xl font-extrabold text-[#102d4b]">{{ $stat['value'] }}</p></article>
            @endforeach
        </div>
        <p class="mt-3 text-xs leading-5 text-slate-500">Angka utama menghitung siswa unik. Satu siswa dapat muncul pada beberapa kategori, jadi angka-angka ini tidak dijumlahkan.</p>

        <details class="mt-4 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <summary class="cursor-pointer px-5 py-4 text-sm font-bold text-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">Rincian hasil TP dan kategori</summary>
            <div class="border-t border-slate-100 px-5 py-4">
                <p class="text-xs leading-5 text-slate-500">Rincian menghitung hasil per TP atau kategori, bukan siswa unik. Satu siswa dapat menyumbang lebih dari satu hasil.</p>
                <dl class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div><dt class="text-xs font-semibold text-slate-500">Jumlah hasil TP di bawah KKTP</dt><dd class="mt-1 text-xl font-extrabold text-[#102d4b]">{{ $summary['belowTpStudentCount'] }}</dd></div>
                    <div><dt class="text-xs font-semibold text-slate-500">Jumlah hasil kategori sumatif di bawah KKTP</dt><dd class="mt-1 text-xl font-extrabold text-[#102d4b]">{{ $summary['belowCategoryCount'] }}</dd></div>
                    <div><dt class="text-xs font-semibold text-slate-500">Jumlah hasil TP belum lengkap</dt><dd class="mt-1 text-xl font-extrabold text-[#102d4b]">{{ $summary['incompleteTpStudentCount'] }}</dd></div>
                </dl>
            </div>
        </details>

        <form method="GET" action="{{ route('guru.perhatian.index') }}" class="mt-6 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_auto] md:items-end">
            <div class="grid gap-2"><label for="pengampu_id" class="text-sm font-bold text-slate-700">Tugas Mengajar dan Periode</label><select id="pengampu_id" name="pengampu_id" class="rk-field"><option value="">Semua tugas mengajar{{ $schoolYearId > 0 ? ' pada periode terpilih' : '' }}</option>@foreach ($assignments as $assignment)<option value="{{ $assignment->id }}" @selected($assignmentId === $assignment->id)>{{ $assignment->mataPelajaran->nama }} · {{ $assignment->kelas->tingkat }} {{ $assignment->kelas->nama }} · {{ $assignment->tahunAjaran->tahun }} — Semester {{ $assignment->tahunAjaran->semester }}</option>@endforeach</select></div>
            <div class="grid gap-2"><label for="search" class="text-sm font-bold text-slate-700">Cari Siswa</label><input id="search" name="search" type="search" value="{{ $search }}" placeholder="Nama, NIS, atau NISN" class="rk-field"></div>
            <div class="flex gap-2"><button type="submit" class="rk-button rk-button-primary">Terapkan</button><a href="{{ route('guru.perhatian.index') }}" class="rk-button rk-button-secondary">Reset</a></div>
            <p class="text-xs leading-5 text-slate-500 md:col-span-full">Statistik mengikuti tugas mengajar dan periode; pencarian hanya menyaring daftar siswa.</p>
        </form>

        <div class="mt-5 flex flex-col gap-1 text-xs leading-5 text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <p>Setiap baris menunjukkan satu siswa pada satu tugas mengajar; siswa yang sama dapat muncul pada mata pelajaran lain.</p>
            <p class="sm:hidden">Geser tabel ke samping untuk melihat kolom lainnya.</p>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <caption class="sr-only">Daftar siswa yang perlu perhatian</caption>
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-4">Siswa</th><th class="px-5 py-4">Kelas & Mata Pelajaran</th><th class="px-5 py-4">Rata-rata Nilai Tugas</th><th class="px-5 py-4">Tren Nilai</th><th class="px-5 py-4">Alasan dan saran</th><th class="px-5 py-4">Status</th><th class="px-5 py-4 text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            @php
                                $statusTone = match ($row['status']) {
                                    'Perlu Tindak Lanjut' => 'bg-rose-50 text-rose-700',
                                    'Pantau Perkembangan' => 'bg-amber-50 text-amber-700',
                                    default => 'bg-blue-50 text-blue-700',
                                };
                                $trendTone = match ($row['trend']['label']) {
                                    'Meningkat' => 'text-emerald-700',
                                    'Menurun' => 'text-rose-700',
                                    default => 'text-slate-600',
                                };
                            @endphp
                            <tr class="align-top transition hover:bg-slate-50/70"><td class="px-5 py-4"><strong class="block text-slate-800">{{ $row['student']->nama }}</strong><span class="mt-1 block text-xs text-slate-400">NIS {{ $row['student']->nis }}{{ $row['student']->nisn ? ' · NISN '.$row['student']->nisn : '' }}</span></td><td class="px-5 py-4 text-slate-600"><strong class="block text-slate-700">{{ $row['assignment']->mataPelajaran->nama }}</strong><span class="text-xs text-slate-400">{{ $row['assignment']->kelas->tingkat }} {{ $row['assignment']->kelas->nama }} · {{ $row['assignment']->tahunAjaran->tahun }} · Semester {{ $row['assignment']->tahunAjaran->semester }}</span></td><td class="px-5 py-4 font-extrabold {{ $row['belowThresholdCount'] > 0 ? 'text-rose-700' : 'text-slate-700' }}">{{ $row['average'] === null ? '—' : number_format($row['average'], 2, ',', '.') }}<small class="block font-normal text-slate-400">Bukan nilai akhir</small></td><td class="px-5 py-4"><strong class="{{ $trendTone }}">{{ $row['trend']['label'] }}</strong><span class="mt-1 block text-xs text-slate-400">{{ $row['trend']['difference'] === null ? ($row['trend']['label'] === 'Belum ada data' ? 'Belum ada nilai' : 'Perlu sedikitnya 4 nilai') : 'Perubahan: '.($row['trend']['difference'] > 0 ? '+' : '').number_format($row['trend']['difference'], 2, ',', '.') }}</span></td><td class="px-5 py-4"><div class="grid min-w-52 gap-2">@foreach ($row['reasons'] as $reason)<div><span class="inline-block rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">{{ $reason['label'] }}</span><p class="mt-1 text-xs leading-5 text-slate-500">{{ $reason['guidance'] }}</p></div>@endforeach</div></td><td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $statusTone }}">{{ $row['status'] }}</span></td><td class="px-5 py-4 text-right"><a href="{{ route('guru.analisis.index', ['pengampu_id' => $row['assignment']->id, 'siswa_id' => $row['student']->id]) }}" class="inline-flex min-h-10 items-center rounded-lg border border-emerald-200 px-3 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50">Lihat Detail</a></td></tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-16 text-center"><span class="mx-auto grid size-12 place-items-center rounded-xl bg-emerald-50 text-emerald-700"><x-nav-icon name="attention" /></span>
                                @if ($search !== '')
                                    <h2 class="mt-4 font-bold text-slate-700">Siswa tidak ditemukan</h2><p class="mt-2 text-sm text-slate-500">Coba gunakan nama atau nomor identitas yang berbeda.</p>
                                @elseif ($summary['assignmentCount'] === 0)
                                    <h2 class="mt-4 font-bold text-slate-700">Belum ada penugasan pada periode ini</h2><p class="mt-2 text-sm text-slate-500">Periksa tahun ajaran yang dipilih atau hubungi administrator.</p>
                                @elseif ($summary['assessmentCount'] === 0)
                                    <h2 class="mt-4 font-bold text-slate-700">Belum ada data penilaian</h2><p class="mt-2 text-sm text-slate-500">Belum ada penilaian pada tugas mengajar yang dipilih.</p>
                                @elseif ($summary['rosterCount'] === 0)
                                    <h2 class="mt-4 font-bold text-slate-700">Belum ada siswa pada kelas ini</h2><p class="mt-2 text-sm text-slate-500">Data perhatian tersedia setelah siswa ditempatkan di kelas.</p>
                                @elseif ($summary['insufficientTrendCount'] > 0 && $summary['insufficientTrendCount'] === $summary['trendCount'])
                                    <h2 class="mt-4 font-bold text-slate-700">Data belum cukup untuk dianalisis</h2><p class="mt-2 text-sm text-slate-500">Tren memerlukan sedikitnya empat nilai. Lengkapi penilaian untuk mendapatkan analisis perkembangan.</p>
                                @else
                                    <h2 class="mt-4 font-bold text-slate-700">Tidak ditemukan indikator perhatian dari nilai yang tersedia</h2>
                                    @if ($summary['insufficientTrendCount'] > 0)
                                        <p class="mt-2 text-sm text-slate-500">{{ $summary['insufficientTrendCount'] }} data siswa dan mata pelajaran belum cukup untuk dianalisis trennya.</p>
                                    @else
                                        <p class="mt-2 text-sm text-slate-500">Tidak ada hasil TP di bawah KKTP, nilai belum lengkap, klasifikasi yang perlu ditinjau, atau tren menurun pada filter ini.</p>
                                    @endif
                                @endif
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 text-xs leading-5 text-slate-500"><strong class="text-slate-700">Catatan analisis:</strong> Hasil sumatif dinilai per TP terhadap KKTP. Nilai kosong tidak dianggap nol. Tren menunjukkan perubahan nilai tugas dan tidak menentukan hasil TP.</div>
    </section>
@endsection
