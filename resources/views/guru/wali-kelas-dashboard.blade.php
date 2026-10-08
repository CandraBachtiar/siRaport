@extends('layouts.guru-workspace')

@section('title', 'Dashboard Wali Kelas')

@section('content')
    <section class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-600">Dashboard Wali Kelas</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Selamat datang, {{ $guru->nama }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Pantau perkembangan lintas mata pelajaran dan kesiapan rapor kelas Anda.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <p class="text-[0.65rem] font-extrabold uppercase tracking-[0.16em] text-slate-400">Tahun Ajaran Aktif</p>
                <p class="mt-1 text-sm font-bold text-[#102d4b]">{{ $selectedSchoolYear ? $selectedSchoolYear->tahun.' · '.$selectedSchoolYear->semester : 'Belum tersedia' }}</p>
            </div>
        </div>

        <div class="mt-7">
            @include('guru.wali.partials.filters', ['filterAction' => route('guru.wali.dashboard')])
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                ['value' => $metrics['studentCount'], 'label' => 'Total Siswa', 'tone' => 'bg-emerald-50 text-emerald-700'],
                ['value' => $metrics['classAverage'] ?? '—', 'label' => 'Rata-rata Kelas', 'tone' => 'bg-blue-50 text-blue-700'],
                ['value' => $metrics['masteredStudentCount'], 'label' => 'Siswa Tuntas', 'tone' => 'bg-violet-50 text-violet-700'],
                ['value' => $metrics['attentionCount'], 'label' => 'Perlu Perhatian', 'tone' => 'bg-amber-50 text-amber-700'],
                ['value' => $metrics['reportCompletionPercentage'].'%', 'label' => 'Rapor Siap', 'tone' => 'bg-slate-100 text-slate-700'],
            ] as $summary)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.03]">
                    <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-bold {{ $summary['tone'] }}">{{ $summary['label'] }}</span>
                    <p class="mt-4 text-3xl font-extrabold tracking-tight text-[#102d4b]">{{ is_numeric($summary['value']) ? number_format((float) $summary['value'], 0, ',', '.') : $summary['value'] }}</p>
                </article>
            @endforeach
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(22rem,0.85fr)]">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div><h2 class="font-bold text-[#102d4b]">Perkembangan Kelas</h2><p class="mt-1 text-sm text-slate-500">Rata-rata siswa pada setiap mata pelajaran.</p></div>
                    <a href="{{ route('guru.wali.perkembangan.index', ['kelas_id' => $selectedClass?->id, 'tahun_ajaran_id' => $selectedSchoolYear?->id]) }}" class="text-sm font-bold text-emerald-700">Lihat detail</a>
                </div>
                <x-charts.bar-chart :items="$subjectSeries" title="Rata-rata kelas per mata pelajaran" class="mt-6" empty-message="Belum ada nilai lintas mata pelajaran pada periode ini." />
            </article>

            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-5">
                    <div><h2 class="font-bold text-[#102d4b]">Siswa Perlu Perhatian</h2><p class="mt-1 text-sm text-slate-500">Prioritas berdasarkan data nilai nyata.</p></div>
                    <a href="{{ route('guru.wali.perhatian.index', ['kelas_id' => $selectedClass?->id, 'tahun_ajaran_id' => $selectedSchoolYear?->id]) }}" class="text-sm font-bold text-emerald-700">Lihat semua</a>
                </div>
                @forelse ($attentionRows->take(5) as $row)
                    <a href="{{ route('guru.wali.siswa.show', ['siswa' => $row['student'], 'tahun_ajaran_id' => $selectedSchoolYear?->id]) }}" class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 last:border-0 hover:bg-slate-50">
                        <span><strong class="block text-sm text-[#102d4b]">{{ $row['student']->nama }}</strong><small class="mt-1 block text-slate-500">{{ $row['attentionReasons']->first() }}</small></span>
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">{{ $row['overallAverage'] === null ? '—' : number_format($row['overallAverage'], 1, ',', '.') }}</span>
                    </a>
                @empty
                    <div class="px-5 py-12 text-center"><x-nav-icon name="attention" class="mx-auto text-emerald-600" /><p class="mt-3 text-sm font-bold text-slate-700">Tidak ada siswa dalam daftar perhatian.</p></div>
                @endforelse
            </article>
        </div>

        <article class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-5 sm:px-6"><h2 class="font-bold text-[#102d4b]">Status Penyusunan Rapor</h2><p class="mt-1 text-sm text-slate-500">Nilai dan deskripsi harus lengkap sebelum rapor dapat dicetak.</p></div>
            <div class="grid gap-4 p-5 sm:grid-cols-3 sm:p-6">
                <div class="rounded-xl bg-slate-50 p-4"><p class="text-sm text-slate-500">Nilai lengkap</p><strong class="mt-2 block text-2xl text-[#102d4b]">{{ $metrics['completedStudentCount'] }}/{{ $metrics['studentCount'] }}</strong></div>
                <div class="rounded-xl bg-slate-50 p-4"><p class="text-sm text-slate-500">Deskripsi tervalidasi</p><strong class="mt-2 block text-2xl text-[#102d4b]">{{ $metrics['validatedDescriptionCount'] }}/{{ $metrics['studentCount'] }}</strong></div>
                <div class="rounded-xl bg-emerald-50 p-4"><p class="text-sm text-emerald-700">Rapor siap dicetak</p><strong class="mt-2 block text-2xl text-emerald-800">{{ $metrics['readyReportCount'] }}/{{ $metrics['studentCount'] }}</strong></div>
            </div>
            <div class="border-t border-slate-100 px-5 py-4 sm:px-6"><a href="{{ route('guru.wali.rapor.index', ['kelas_id' => $selectedClass?->id, 'tahun_ajaran_id' => $selectedSchoolYear?->id]) }}" class="rk-button rk-button-primary">Tinjau Data Rapor</a></div>
        </article>

        @if ($selectedClass === null)
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">Belum ada kelas perwalian. Hubungi administrator untuk menetapkan wali kelas.</div>
        @elseif ($selectedSchoolYear === null)
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">Belum ada penugasan mata pelajaran untuk kelas ini.</div>
        @endif
    </section>
@endsection
