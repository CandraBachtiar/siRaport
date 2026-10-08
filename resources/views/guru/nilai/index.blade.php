@extends('layouts.guru-workspace')

@section('title', 'Input Nilai')

@section('content')
    <section class="mx-auto max-w-7xl">
        <div><p class="text-sm font-semibold text-emerald-600">Pembelajaran</p><h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Input Nilai</h1><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Pilih periode, mata pelajaran, kelas, dan penilaian, lalu simpan seluruh nilai dalam satu tabel.</p></div>

        <form method="GET" action="{{ route('guru.nilai.index') }}" class="mt-8 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-2 xl:grid-cols-4" data-score-filter-form>
            <div class="grid gap-2"><label for="tahun_ajaran_id" class="text-sm font-bold text-slate-700">1. Tahun Ajaran</label><select id="tahun_ajaran_id" name="tahun_ajaran_id" class="rk-field" data-auto-submit data-score-filter="year"><option value="">Pilih periode</option>@foreach ($schoolYears as $schoolYear)<option value="{{ $schoolYear->id }}" @selected($schoolYearId === $schoolYear->id)>{{ $schoolYear->tahun }} · {{ $schoolYear->semester }}{{ $schoolYear->aktif ? ' (Aktif)' : '' }}</option>@endforeach</select></div>
            <div class="grid gap-2"><label for="mata_pelajaran_id" class="text-sm font-bold text-slate-700">2. Mata Pelajaran</label><select id="mata_pelajaran_id" name="mata_pelajaran_id" class="rk-field" data-auto-submit data-score-filter="subject" @disabled($schoolYearId === 0)><option value="">Pilih mata pelajaran</option>@foreach ($subjects as $subject)<option value="{{ $subject->id }}" @selected($subjectId === $subject->id)>{{ $subject->nama }}</option>@endforeach</select></div>
            <div class="grid gap-2"><label for="kelas_id" class="text-sm font-bold text-slate-700">3. Kelas</label><select id="kelas_id" name="kelas_id" class="rk-field" data-auto-submit data-score-filter="class" @disabled($subjectId === 0)><option value="">Pilih kelas</option>@foreach ($classes as $class)<option value="{{ $class->id }}" @selected($classId === $class->id)>{{ $class->tingkat }} {{ $class->nama }}</option>@endforeach</select></div>
            <div class="grid gap-2"><label for="penilaian_id" class="text-sm font-bold text-slate-700">4. Penilaian</label><select id="penilaian_id" name="penilaian_id" class="rk-field" data-auto-submit data-score-filter="assessment" @disabled($classId === 0)><option value="">Pilih penilaian</option>@foreach ($assessments as $assessment)<option value="{{ $assessment->id }}" @selected($assessmentId === $assessment->id)>{{ $assessment->urutan }}. {{ $assessment->nama }}</option>@endforeach</select></div>
            <noscript><button type="submit" class="rk-button rk-button-primary md:col-span-2 xl:col-span-4">Tampilkan Siswa</button></noscript>
        </form>

        @if ($assignments->isEmpty())
            <div class="mt-6 rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center"><h2 class="font-bold text-slate-700">Belum ada tugas mengajar</h2><p class="mt-2 text-sm text-slate-500">Administrator belum menetapkan kelas dan mata pelajaran untuk akun Anda.</p></div>
        @elseif ($selectedAssessment === null)
            <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center"><span class="mx-auto grid size-12 place-items-center rounded-xl bg-blue-50 text-blue-700"><x-nav-icon name="nilai" /></span><h2 class="mt-4 font-bold text-slate-700">Pilih penilaian untuk mulai mengisi nilai</h2><p class="mt-2 text-sm text-slate-500">Pilihan dibuat bertahap agar nilai tidak masuk ke kelas atau mata pelajaran yang salah.</p>@if ($classId > 0 && $assessments->isEmpty())<a href="{{ route('guru.penilaian.create', ['pengampu_id' => $filteredAssignments->first()?->id]) }}" class="rk-button rk-button-primary mt-5">+ Buat Penilaian</a>@endif</div>
        @else
            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert"><p class="font-bold">Nilai belum dapat disimpan.</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <div class="mt-6 rounded-2xl border border-blue-200 bg-blue-50 p-5 text-sm text-blue-900"><div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"><div><strong class="block text-base">{{ $selectedAssessment->nama }}</strong><span>{{ $selectedAssessment->pengampu->mataPelajaran->nama }} · {{ $selectedAssessment->pengampu->kelas->tingkat }} {{ $selectedAssessment->pengampu->kelas->nama }}</span></div><span class="rounded-full bg-white/80 px-3 py-1 text-xs font-bold">KKM {{ number_format((float) $selectedAssessment->pengampu->mataPelajaran->kkm, 0, ',', '.') }}</span></div></div>

            <form method="POST" action="{{ route('guru.nilai.update', $selectedAssessment) }}" class="mt-4" data-loading-form>
                @csrf
                @method('PUT')
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <caption class="sr-only">Daftar siswa dan input nilai untuk {{ $selectedAssessment->nama }}</caption>
                            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500"><tr><th class="w-16 px-5 py-4">No</th><th class="px-5 py-4">NIS</th><th class="px-5 py-4">Nama Siswa</th><th class="w-48 px-5 py-4">Nilai</th><th class="px-5 py-4">Status</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($students as $student)
                                    @php
                                        $scoreValue = old('nilai.'.$student->id, $scores->get($student->id)?->nilai);
                                        $hasScore = $scoreValue !== null && $scoreValue !== '';
                                        $meetsKkm = $hasScore && (float) $scoreValue >= (float) $selectedAssessment->pengampu->mataPelajaran->kkm;
                                        $scoreErrorKey = 'nilai.'.$student->id;
                                        $scoreHasError = $errors->has($scoreErrorKey);
                                    @endphp
                                    <tr class="hover:bg-slate-50/70"><td class="px-5 py-4 text-slate-400">{{ $loop->iteration }}</td><td class="px-5 py-4 font-mono text-xs text-slate-500">{{ $student->nis }}</td><td class="px-5 py-4 font-bold text-slate-700">{{ $student->nama }}</td><td class="px-5 py-3"><input id="score-{{ $student->id }}" name="nilai[{{ $student->id }}]" type="number" min="0" max="100" step="0.01" inputmode="decimal" value="{{ $scoreValue }}" placeholder="0–100" class="h-11 w-32 rounded-xl border px-3 text-sm font-bold outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $scoreHasError ? 'border-red-400' : 'border-slate-300' }}" aria-label="Nilai {{ $student->nama }}" @if ($scoreHasError) aria-invalid="true" aria-describedby="score-error-{{ $student->id }}" @endif data-score-input="{{ $student->id }}" data-kkm="{{ (float) $selectedAssessment->pengampu->mataPelajaran->kkm }}">@error($scoreErrorKey)<p id="score-error-{{ $student->id }}" class="mt-1 max-w-44 text-xs font-semibold text-red-600" role="alert">{{ $message }}</p>@enderror</td><td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ ! $hasScore ? 'bg-slate-100 text-slate-600' : ($meetsKkm ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700') }}" data-score-status="{{ $student->id }}">{{ ! $hasScore ? 'Belum diisi' : ($meetsKkm ? 'Mencapai KKM' : 'Di bawah KKM') }}</span></td></tr>
                                @empty
                                    <tr><td colspan="5" class="px-5 py-14 text-center"><p class="font-bold text-slate-700">Belum ada siswa di kelas ini</p><p class="mt-1 text-sm text-slate-500">Hubungi administrator sebelum mengisi nilai.</p></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($students->isNotEmpty())
                        <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><p class="text-xs text-slate-500">Kolom kosong tetap berstatus belum diisi. Nilai lama tidak dihapus otomatis.</p><div class="flex gap-3"><a href="{{ route('guru.nilai.index', ['tahun_ajaran_id' => $schoolYearId, 'mata_pelajaran_id' => $subjectId, 'kelas_id' => $classId]) }}" class="rk-button rk-button-secondary">Batal</a><button type="submit" class="rk-button rk-button-primary"><span data-submit-label>Simpan Nilai</span></button></div></div>
                    @endif
                </div>
            </form>
        @endif
    </section>
@endsection
