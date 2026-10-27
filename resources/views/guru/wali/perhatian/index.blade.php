@extends('layouts.guru-workspace')

@section('title', 'Siswa Perlu Perhatian')

@section('content')
<section class="mx-auto max-w-7xl">
    <div><p class="rk-eyebrow">Pemantauan Akademik</p><h1 class="mt-2 text-3xl font-extrabold text-[#102d4b]">Siswa Perlu Perhatian</h1><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Daftar lintas mata pelajaran berdasarkan rata-rata di bawah KKM, tren menurun, atau nilai belum lengkap.</p></div>
    <div class="mt-7">@include('guru.wali.partials.filters', ['filterAction' => route('guru.wali.perhatian.index')])</div>
    <div class="mt-6 grid gap-4 sm:grid-cols-3"><div class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Siswa terpantau</p><strong class="mt-2 block text-3xl text-[#102d4b]">{{ $attentionRows->count() }}</strong></div><div class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Di bawah KKM</p><strong class="mt-2 block text-3xl text-rose-700">{{ $attentionRows->where('belowKkmCount', '>', 0)->count() }}</strong></div><div class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Nilai belum lengkap</p><strong class="mt-2 block text-3xl text-amber-700">{{ $attentionRows->where('missingScoreCount', '>', 0)->count() }}</strong></div></div>
    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-4">Siswa</th><th class="px-5 py-4">Rata-rata</th><th class="px-5 py-4">Mapel Terkait</th><th class="px-5 py-4">Alasan</th><th class="px-5 py-4 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">
        @forelse ($attentionRows as $row)
            @php($flaggedSubjects = $row['subjects']->filter(fn ($subject) => ($subject['average'] !== null && ! $subject['meetsKkm']) || $subject['missingCount'] > 0 || $subject['trend']['label'] === 'Menurun'))
            <tr><td class="px-5 py-4"><strong class="text-[#102d4b]">{{ $row['student']->nama }}</strong><small class="block text-slate-500">{{ $selectedClass?->tingkat }} {{ $selectedClass?->nama }}</small></td><td class="px-5 py-4 font-bold">{{ $row['overallAverage'] === null ? '—' : number_format($row['overallAverage'], 2, ',', '.') }}</td><td class="px-5 py-4"><div class="flex max-w-sm flex-wrap gap-1.5">@foreach ($flaggedSubjects as $subject)<span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $subject['assignment']->mataPelajaran->nama }}: {{ $subject['average'] === null ? '—' : number_format($subject['average'], 0, ',', '.') }}</span>@endforeach</div></td><td class="px-5 py-4"><ul class="grid gap-1 text-xs text-slate-600">@foreach ($row['attentionReasons'] as $reason)<li>• {{ $reason }}</li>@endforeach</ul></td><td class="px-5 py-4 text-right"><a href="{{ route('guru.wali.siswa.show', ['siswa' => $row['student'], 'tahun_ajaran_id' => $selectedSchoolYear?->id]) }}" class="font-bold text-emerald-700">Lihat Detail</a></td></tr>
        @empty
            <tr><td colspan="5" class="px-6 py-14 text-center"><x-nav-icon name="attention" class="mx-auto text-emerald-600" />
                @if ($attentionData['assignmentCount'] === 0)
                    <strong class="mt-3 block text-slate-700">Belum ada penugasan mata pelajaran.</strong><span class="mt-1 block text-slate-500">Periksa kelas dan tahun ajaran terpilih atau hubungi administrator.</span>
                @elseif ($attentionData['assessmentCount'] === 0)
                    <strong class="mt-3 block text-slate-700">Belum ada data penilaian.</strong><span class="mt-1 block text-slate-500">Belum ada penilaian pada penugasan untuk periode ini.</span>
                @elseif ($attentionData['studentCount'] === 0)
                    <strong class="mt-3 block text-slate-700">Belum ada siswa pada kelas ini.</strong><span class="mt-1 block text-slate-500">Data perhatian akan tersedia setelah siswa ditempatkan di kelas.</span>
                @elseif ($attentionData['insufficientTrendCount'] > 0 && $attentionData['insufficientTrendCount'] === $attentionData['trendCount'])
                    <strong class="mt-3 block text-slate-700">Data belum cukup untuk dianalisis.</strong><span class="mt-1 block text-slate-500">Tren memerlukan sedikitnya empat nilai. Lengkapi penilaian untuk mendapatkan analisis perkembangan.</span>
                @else
                    <strong class="mt-3 block text-slate-700">Tidak ditemukan indikator perhatian dari nilai yang tersedia.</strong>
                    @if ($attentionData['insufficientTrendCount'] > 0)
                        <span class="mt-1 block text-slate-500">{{ $attentionData['insufficientTrendCount'] }} data siswa dan mata pelajaran belum cukup untuk dianalisis trennya.</span>
                    @else
                        <span class="mt-1 block text-slate-500">Tidak ada indikator nilai di bawah KKM, tren menurun, atau nilai belum lengkap pada filter ini.</span>
                    @endif
                @endif
            </td></tr>
        @endforelse
    </tbody></table></div></div>
</section>
@endsection
