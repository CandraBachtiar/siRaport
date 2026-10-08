@extends('layouts.guru-workspace')

@section('title', 'Penilaian')

@section('content')
    <section class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-semibold text-emerald-600">Pembelajaran</p><h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Penilaian</h1><p class="mt-2 text-sm text-slate-500">Kelola tugas, ulangan harian, UTS, dan UAS pada kelas yang Anda ampu.</p></div>
            <a href="{{ route('guru.penilaian.create', array_filter(['pengampu_id' => $assignmentId])) }}" class="rk-button rk-button-primary"><span aria-hidden="true">+</span> Tambah Penilaian</a>
        </div>

        <form method="GET" action="{{ route('guru.penilaian.index') }}" class="mt-8 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
            <div class="grid gap-2"><label for="pengampu_id" class="text-sm font-bold text-slate-700">Tugas Mengajar</label><select id="pengampu_id" name="pengampu_id" class="rk-field"><option value="">Semua tugas mengajar</option>@foreach ($assignments as $assignment)<option value="{{ $assignment->id }}" @selected($assignmentId === $assignment->id)>{{ $assignment->mataPelajaran->nama }} · {{ $assignment->kelas->tingkat }} {{ $assignment->kelas->nama }} · {{ $assignment->tahunAjaran->tahun }} {{ $assignment->tahunAjaran->semester }}</option>@endforeach</select></div>
            <div class="flex gap-2"><button type="submit" class="rk-button bg-[#102d4b] text-white hover:bg-[#0b2945]">Terapkan</button><a href="{{ route('guru.penilaian.index') }}" class="rk-button rk-button-secondary">Reset</a></div>
        </form>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <caption class="sr-only">Daftar penilaian</caption>
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-4">Penilaian</th><th class="px-5 py-4">Kelas & Mapel</th><th class="px-5 py-4">Tanggal</th><th class="px-5 py-4">Bobot</th><th class="px-5 py-4">Nilai Terisi</th><th class="px-5 py-4 text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($assessments as $assessment)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-5 py-4"><strong class="block text-slate-800">{{ $assessment->urutan }}. {{ $assessment->nama }}</strong><span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[0.65rem] font-bold text-slate-600">{{ str($assessment->jenis)->replace('_', ' ')->title() }}</span></td>
                                <td class="px-5 py-4 text-slate-600"><strong class="block text-slate-700">{{ $assessment->pengampu->mataPelajaran->nama }}</strong><span class="text-xs text-slate-400">{{ $assessment->pengampu->kelas->tingkat }} {{ $assessment->pengampu->kelas->nama }} · {{ $assessment->pengampu->tahunAjaran->tahun }} {{ $assessment->pengampu->tahunAjaran->semester }}</span></td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $assessment->tanggal->translatedFormat('d M Y') }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $assessment->bobot ? number_format((float) $assessment->bobot, 2, ',', '.').'%' : '—' }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">{{ $assessment->nilai_count }} nilai</span></td>
                                <td class="whitespace-nowrap px-5 py-4 text-right"><div class="inline-flex items-center gap-2"><a href="{{ route('guru.nilai.index', ['tahun_ajaran_id' => $assessment->pengampu->tahun_ajaran_id, 'mata_pelajaran_id' => $assessment->pengampu->mata_pelajaran_id, 'kelas_id' => $assessment->pengampu->kelas_id, 'penilaian_id' => $assessment->id]) }}" class="rounded-lg border border-emerald-200 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50">Input Nilai</a><a href="{{ route('guru.penilaian.edit', $assessment) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">Edit</a>@if ($assessment->nilai_count === 0)<button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600 transition hover:bg-red-50" data-delete-assessment data-delete-action="{{ route('guru.penilaian.destroy', $assessment) }}" data-delete-name="{{ $assessment->nama }}">Hapus</button>@else<span class="cursor-not-allowed rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-400" title="Penilaian yang memiliki nilai tidak dapat dihapus">Terkunci</span>@endif</div></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-16 text-center"><span class="mx-auto grid size-12 place-items-center rounded-xl bg-slate-100 text-slate-500"><x-nav-icon name="penilaian" /></span><h2 class="mt-4 font-bold text-slate-700">Belum ada penilaian</h2><p class="mt-2 text-sm text-slate-500">Buat penilaian pertama untuk mulai memasukkan nilai siswa.</p><a href="{{ route('guru.penilaian.create', array_filter(['pengampu_id' => $assignmentId])) }}" class="rk-button rk-button-primary mt-5">+ Buat Penilaian</a></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($assessments->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $assessments->links() }}</div>@endif
        </div>
    </section>

    <dialog class="m-auto w-[min(92vw,32rem)] rounded-2xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-950/50" data-delete-assessment-dialog>
        <div class="p-6 sm:p-7"><span class="grid size-11 place-items-center rounded-xl bg-red-50 text-red-700"><x-nav-icon name="attention" /></span><h2 class="mt-4 text-xl font-extrabold text-[#102d4b]">Hapus Penilaian?</h2><p class="mt-2 text-sm leading-6 text-slate-600">Anda akan menghapus <strong data-delete-assessment-name></strong>. Nilai siswa yang terkait dapat terdampak dan tindakan ini tidak dapat dibatalkan.</p><p class="mt-3 rounded-xl bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-800">Demi keamanan, sistem akan menolak penghapusan jika penilaian sudah memiliki nilai siswa.</p><form method="POST" class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end" data-delete-assessment-form>@csrf @method('DELETE')<button type="button" class="rk-button rk-button-secondary" data-dialog-cancel>Batal</button><button type="submit" class="rk-button bg-red-600 text-white hover:bg-red-700">Hapus Penilaian</button></form></div>
    </dialog>
@endsection
