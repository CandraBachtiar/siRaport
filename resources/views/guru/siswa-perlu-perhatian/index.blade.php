<div>
    <!-- The whole future lies in uncertainty: live immediately. - Seneca -->
</div>
@extends('layouts.guru-workspace')

@section('title', 'Siswa Perlu Perhatian')

@section('content')
    <section class="mx-auto max-w-7xl">
        <div><p class="text-sm font-semibold text-emerald-600">Analisis</p><h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Siswa Perlu Perhatian</h1><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Daftar ini disusun dari rata-rata di bawah KKM, tren nilai menurun, atau penilaian yang belum memiliki nilai. Gunakan sebagai titik awal peninjauan, bukan keputusan akhir.</p></div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Siswa Teridentifikasi', 'value' => $summary['studentCount'], 'tone' => 'bg-blue-50 text-blue-700'],
                ['label' => 'Kasus di bawah KKM', 'value' => $summary['belowKkmCount'], 'tone' => 'bg-rose-50 text-rose-700'],
                ['label' => 'Tren Menurun', 'value' => $summary['decliningCount'], 'tone' => 'bg-amber-50 text-amber-700'],
                ['label' => 'Nilai Belum Lengkap', 'value' => $summary['incompleteCount'], 'tone' => 'bg-slate-100 text-slate-700'],
            ] as $stat)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-bold {{ $stat['tone'] }}">{{ $stat['label'] }}</span><p class="mt-4 text-3xl font-extrabold text-[#102d4b]">{{ $stat['value'] }}</p></article>
            @endforeach
        </div>

        <form method="GET" action="{{ route('guru.perhatian.index') }}" class="mt-6 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_auto] md:items-end">
            <div class="grid gap-2"><label for="pengampu_id" class="text-sm font-bold text-slate-700">Kelas dan Mata Pelajaran</label><select id="pengampu_id" name="pengampu_id" class="rk-field"><option value="">Semua tugas mengajar</option>@foreach ($assignments as $assignment)<option value="{{ $assignment->id }}" @selected($assignmentId === $assignment->id)>{{ $assignment->mataPelajaran->nama }} · {{ $assignment->kelas->tingkat }} {{ $assignment->kelas->nama }} · {{ $assignment->tahunAjaran->tahun }} {{ $assignment->tahunAjaran->semester }}</option>@endforeach</select></div>
            <div class="grid gap-2"><label for="search" class="text-sm font-bold text-slate-700">Cari Siswa</label><input id="search" name="search" type="search" value="{{ $search }}" placeholder="Nama, NIS, atau NISN" class="rk-field"></div>
            <div class="flex gap-2"><button type="submit" class="rk-button rk-button-primary">Terapkan</button><a href="{{ route('guru.perhatian.index') }}" class="rk-button rk-button-secondary">Reset</a></div>
        </form>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <caption class="sr-only">Daftar siswa yang perlu perhatian</caption>
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-4">Siswa</th><th class="px-5 py-4">Kelas & Mata Pelajaran</th><th class="px-5 py-4">Rata-rata</th><th class="px-5 py-4">Tren</th><th class="px-5 py-4">Alasan</th><th class="px-5 py-4">Status</th><th class="px-5 py-4 text-right">Aksi</th></tr></thead>
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
                            <tr class="align-top transition hover:bg-slate-50/70"><td class="px-5 py-4"><strong class="block text-slate-800">{{ $row['student']->nama }}</strong><span class="mt-1 block text-xs text-slate-400">NIS {{ $row['student']->nis }}{{ $row['student']->nisn ? ' · NISN '.$row['student']->nisn : '' }}</span></td><td class="px-5 py-4 text-slate-600"><strong class="block text-slate-700">{{ $row['assignment']->mataPelajaran->nama }}</strong><span class="text-xs text-slate-400">{{ $row['assignment']->kelas->tingkat }} {{ $row['assignment']->kelas->nama }} · {{ $row['assignment']->tahunAjaran->tahun }} {{ $row['assignment']->tahunAjaran->semester }}</span></td><td class="px-5 py-4 font-extrabold {{ $row['average'] !== null && $row['average'] < (float) $row['assignment']->mataPelajaran->kkm ? 'text-rose-700' : 'text-slate-700' }}">{{ $row['average'] === null ? '—' : number_format($row['average'], 2, ',', '.') }}</td><td class="px-5 py-4"><strong class="{{ $trendTone }}">{{ $row['trend']['label'] }}</strong><span class="mt-1 block text-xs text-slate-400">{{ $row['trend']['difference'] === null ? 'Data belum cukup' : (($row['trend']['difference'] > 0 ? '+' : '').number_format($row['trend']['difference'], 2, ',', '.')) }}</span></td><td class="px-5 py-4"><div class="flex min-w-44 flex-wrap gap-1.5">@foreach ($row['reasons'] as $reason)<span class="rounded-full bg-slate-100 px-2.5 py-1 text-[0.65rem] font-bold text-slate-600">{{ $reason }}</span>@endforeach</div></td><td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $statusTone }}">{{ $row['status'] }}</span></td><td class="px-5 py-4 text-right"><a href="{{ route('guru.analisis.index', ['pengampu_id' => $row['assignment']->id, 'siswa_id' => $row['student']->id]) }}" class="inline-flex min-h-10 items-center rounded-lg border border-emerald-200 px-3 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50">Lihat Detail</a></td></tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-16 text-center"><span class="mx-auto grid size-12 place-items-center rounded-xl bg-emerald-50 text-emerald-700"><x-nav-icon name="attention" /></span><h2 class="mt-4 font-bold text-slate-700">{{ $search !== '' ? 'Siswa tidak ditemukan' : 'Tidak ada siswa yang perlu perhatian' }}</h2><p class="mt-2 text-sm text-slate-500">{{ $search !== '' ? 'Coba gunakan nama atau nomor identitas yang berbeda.' : 'Semua siswa pada filter ini tidak memenuhi indikator perhatian yang digunakan.' }}</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 text-xs leading-5 text-slate-500"><strong class="text-slate-700">Cara sistem menyusun daftar:</strong> rata-rata dihitung dari nilai yang telah diisi; tren membandingkan hingga tiga nilai terbaru dengan periode sebelumnya; dan nilai kosong dihitung dari jumlah penilaian pada tugas mengajar tersebut.</div>
    </section>
@endsection
