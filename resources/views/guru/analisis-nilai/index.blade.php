@extends('layouts.guru-workspace')

@section('title', 'Analisis Nilai')

@section('content')
    <section class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-semibold text-emerald-600">Analisis</p><h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Analisis Nilai</h1><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Baca perkembangan kelas dan siswa dari nilai yang benar-benar tersimpan. Tidak ada prediksi atau data buatan.</p></div>
            @if ($selectedAssignment)<div class="flex gap-3"><a href="{{ route('guru.nilai.index', ['tahun_ajaran_id' => $selectedAssignment->tahun_ajaran_id, 'mata_pelajaran_id' => $selectedAssignment->mata_pelajaran_id, 'kelas_id' => $selectedAssignment->kelas_id]) }}" class="rk-button rk-button-secondary">Input Nilai</a><a href="{{ route('guru.rekap-nilai.index', ['pengampu_id' => $selectedAssignment->id]) }}" class="rk-button rk-button-primary">Lihat Rekap</a></div>@endif
        </div>

        <form method="GET" action="{{ route('guru.analisis.index') }}" class="mt-8 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-2 xl:grid-cols-4" data-score-filter-form>
            <div class="grid gap-2"><label for="tahun_ajaran_id" class="text-sm font-bold text-slate-700">Tahun Ajaran</label><select id="tahun_ajaran_id" name="tahun_ajaran_id" class="rk-field" data-auto-submit data-score-filter="year"><option value="">Pilih periode</option>@foreach ($schoolYears as $schoolYear)<option value="{{ $schoolYear->id }}" @selected($schoolYearId === $schoolYear->id)>{{ $schoolYear->tahun }} · {{ $schoolYear->semester }}{{ $schoolYear->aktif ? ' (Aktif)' : '' }}</option>@endforeach</select></div>
            <div class="grid gap-2"><label for="mata_pelajaran_id" class="text-sm font-bold text-slate-700">Mata Pelajaran</label><select id="mata_pelajaran_id" name="mata_pelajaran_id" class="rk-field" data-auto-submit data-score-filter="subject" @disabled($schoolYearId === 0)><option value="">Pilih mata pelajaran</option>@foreach ($subjects as $subject)<option value="{{ $subject->id }}" @selected($subjectId === $subject->id)>{{ $subject->nama }}</option>@endforeach</select></div>
            <div class="grid gap-2"><label for="kelas_id" class="text-sm font-bold text-slate-700">Kelas</label><select id="kelas_id" name="kelas_id" class="rk-field" data-auto-submit data-score-filter="class" @disabled($subjectId === 0)><option value="">Pilih kelas</option>@foreach ($classes as $class)<option value="{{ $class->id }}" @selected($classId === $class->id)>{{ $class->tingkat }} {{ $class->nama }}</option>@endforeach</select></div>
            <div class="grid gap-2"><label for="siswa_id" class="text-sm font-bold text-slate-700">Siswa <span class="font-normal text-slate-400">(opsional)</span></label><select id="siswa_id" name="siswa_id" class="rk-field" data-auto-submit data-score-filter="student" @disabled($selectedAssignment === null)><option value="">Analisis tingkat kelas</option>@foreach ($students as $student)<option value="{{ $student->id }}" @selected($studentId === $student->id)>{{ $student->nama }} · {{ $student->nis }}</option>@endforeach</select></div>
            <noscript><button type="submit" class="rk-button rk-button-primary md:col-span-2 xl:col-span-4">Tampilkan Analisis</button></noscript>
        </form>

        @if ($assignments->isEmpty())
            <div class="mt-6 rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center"><h2 class="font-bold text-slate-700">Belum ada tugas mengajar</h2><p class="mt-2 text-sm text-slate-500">Administrator belum menetapkan kelas dan mata pelajaran untuk akun Anda.</p></div>
        @elseif ($selectedAssignment === null)
            <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center"><span class="mx-auto grid size-12 place-items-center rounded-xl bg-blue-50 text-blue-700"><x-nav-icon name="chart" /></span><h2 class="mt-4 font-bold text-slate-700">Pilih mata pelajaran dan kelas</h2><p class="mt-2 text-sm text-slate-500">Analisis akan tampil setelah konteks pembelajaran dipilih.</p></div>
        @else
            <div class="mt-6 flex flex-col gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-5 text-blue-900 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-xs font-bold uppercase tracking-[0.14em] text-blue-600">Analisis Tingkat Kelas</p><h2 class="mt-1 text-lg font-extrabold">{{ $selectedAssignment->mataPelajaran->nama }} · {{ $selectedAssignment->kelas->tingkat }} {{ $selectedAssignment->kelas->nama }}</h2><p class="mt-1 text-xs">{{ $selectedAssignment->tahunAjaran->tahun }} · {{ $selectedAssignment->tahunAjaran->semester }}</p></div><span class="rounded-full bg-white/80 px-3 py-1 text-xs font-bold">KKM {{ number_format((float) $selectedAssignment->mataPelajaran->kkm, 0, ',', '.') }}</span></div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ([
                    ['label' => 'Rata-rata', 'value' => $classStats['average'] === null ? '—' : number_format($classStats['average'], 2, ',', '.')],
                    ['label' => 'Nilai Tertinggi', 'value' => $classStats['highest'] === null ? '—' : number_format($classStats['highest'], 2, ',', '.')],
                    ['label' => 'Nilai Terendah', 'value' => $classStats['lowest'] === null ? '—' : number_format($classStats['lowest'], 2, ',', '.')],
                    ['label' => 'Di bawah KKM', 'value' => $classStats['belowKkmCount']],
                    ['label' => 'Mencapai KKM', 'value' => $classStats['meetsKkmCount']],
                ] as $stat)
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ $stat['label'] }}</p><p class="mt-3 text-2xl font-extrabold text-[#102d4b]">{{ $stat['value'] }}</p></article>
                @endforeach
            </div>

            <div class="mt-6 grid gap-6 xl:grid-cols-2">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><div><h3 class="font-bold text-[#102d4b]">Rata-rata Kelas per Penilaian</h3><p class="mt-1 text-sm text-slate-500">Garis diurutkan berdasarkan tanggal, lalu urutan penilaian.</p></div><x-charts.line-chart :points="$classSeries" title="Grafik rata-rata kelas per penilaian" class="mt-5" /></article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><div><h3 class="font-bold text-[#102d4b]">Distribusi Rata-rata Siswa</h3><p class="mt-1 text-sm text-slate-500">Setiap siswa dihitung satu kali berdasarkan rata-rata nilai yang sudah terisi.</p></div><x-charts.bar-chart :items="$distribution" title="Grafik distribusi rata-rata siswa" class="mt-5" /></article>
            </div>

            @if ($studentAnalysis !== null)
                @php
                    $trendTone = match ($studentAnalysis['trend']['label']) {
                        'Meningkat' => 'bg-emerald-50 text-emerald-700',
                        'Menurun' => 'bg-rose-50 text-rose-700',
                        default => 'bg-slate-100 text-slate-700',
                    };
                @endphp
                <article class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-600">Analisis Tingkat Siswa</p><h3 class="mt-2 text-xl font-extrabold text-[#102d4b]">{{ $studentAnalysis['student']->nama }}</h3><p class="mt-1 text-sm text-slate-500">NIS {{ $studentAnalysis['student']->nis }}</p></div><span class="rounded-full px-3 py-1.5 text-xs font-bold {{ $trendTone }}">Tren {{ $studentAnalysis['trend']['label'] }}</span></div>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                        @foreach ([
                            ['label' => 'Rata-rata', 'value' => $studentAnalysis['average']],
                            ['label' => 'Nilai Terakhir', 'value' => $studentAnalysis['latest']],
                            ['label' => 'Nilai Tertinggi', 'value' => $studentAnalysis['highest']],
                            ['label' => 'Nilai Terendah', 'value' => $studentAnalysis['lowest']],
                        ] as $stat)
                            <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-bold text-slate-400">{{ $stat['label'] }}</p><p class="mt-2 text-xl font-extrabold text-slate-800">{{ $stat['value'] === null ? '—' : number_format($stat['value'], 2, ',', '.') }}</p></div>
                        @endforeach
                        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-bold text-slate-400">Status KKM</p><p class="mt-2 text-sm font-extrabold {{ $studentAnalysis['average'] === null ? 'text-slate-500' : ($studentAnalysis['meetsKkm'] ? 'text-emerald-700' : 'text-rose-700') }}">{{ $studentAnalysis['average'] === null ? 'Belum ada nilai' : ($studentAnalysis['meetsKkm'] ? 'Mencapai KKM' : 'Di bawah KKM') }}</p></div>
                    </div>
                    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(18rem,0.6fr)]"><x-charts.line-chart :points="$studentAnalysis['series']" title="Grafik perkembangan nilai {{ $studentAnalysis['student']->nama }}" /><div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 text-sm leading-6 text-blue-900"><strong class="block">Berdasarkan perkembangan nilai</strong><p class="mt-2">{{ $studentAnalysis['trend']['explanation'] }}</p>@if ($studentAnalysis['missingCount'] > 0)<p class="mt-3 font-semibold">{{ $studentAnalysis['missingCount'] }} penilaian belum memiliki nilai.</p>@endif</div></div>
                </article>
            @endif
        @endif
    </section>
@endsection
