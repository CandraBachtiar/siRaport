@extends('layouts.guru-workspace')

@section('title', 'Tujuan Pembelajaran & KKTP')

@section('content')
    <section class="mx-auto max-w-7xl">
        <a href="{{ route('guru.kelas-mapel.show', $assignment) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-emerald-700">&larr; Kembali ke Kelas</a>

        <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-600">{{ $assignment->mataPelajaran->nama }} · {{ $assignment->kelas->tingkat }} {{ $assignment->kelas->nama }} · {{ $assignment->tahunAjaran->tahun }} {{ $assignment->tahunAjaran->semester }}</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Tujuan Pembelajaran &amp; KKTP</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Kelola TP untuk tugas mengajar ini. Setiap TP memakai satu ambang KKTP angka yang ditetapkan guru sesuai kesepakatan sekolah.</p>
            </div>
            <a href="{{ route('guru.tujuan-pembelajaran.create', $assignment) }}" class="rk-button rk-button-primary">Tambah TP</a>
        </div>

        @if ($unmappedAssessmentCount > 0)
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900" role="status">
                <strong>{{ $unmappedAssessmentCount }} penilaian lama belum dipetakan ke TP.</strong>
                Hubungkan satu per satu hanya setelah guru memastikan TP yang sesuai. Data tidak dipetakan otomatis.
            </div>
        @endif

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <caption class="sr-only">Daftar tujuan pembelajaran dan KKTP untuk {{ $assignment->mataPelajaran->nama }} kelas {{ $assignment->kelas->nama }}</caption>
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-4">Urutan</th><th class="px-5 py-4">Tujuan Pembelajaran</th><th class="px-5 py-4">KKTP</th><th class="px-5 py-4">Penilaian Terkait</th><th class="px-5 py-4 text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($tujuanPembelajaran as $tujuan)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-700">TP {{ $tujuan->urutan }}</td>
                                <td class="min-w-[20rem] px-5 py-4 leading-6 text-slate-700">{{ $tujuan->tujuan }}</td>
                                <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-700">{{ number_format((float) $tujuan->kktp, 2, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-5 py-4">@if ($tujuan->penilaian_count > 0)<span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-800">{{ $tujuan->penilaian_count }} penilaian</span>@else<span class="text-slate-500">Belum digunakan</span>@endif</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('guru.tujuan-pembelajaran.edit', [$assignment, $tujuan]) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Edit</a>
                                        @if ($tujuan->penilaian_count === 0)
                                            <form method="POST" action="{{ route('guru.tujuan-pembelajaran.destroy', [$assignment, $tujuan]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">Hapus</button>
                                            </form>
                                        @else
                                            <span class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-500" title="TP yang sudah dipakai tidak dapat dihapus">Digunakan</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-14 text-center"><h2 class="font-bold text-slate-700">Belum ada tujuan pembelajaran</h2><p class="mt-2 text-sm text-slate-500">Tambahkan TP dan KKTP untuk mulai mengaitkan penilaian baru.</p><a href="{{ route('guru.tujuan-pembelajaran.create', $assignment) }}" class="rk-button rk-button-primary mt-5">Tambah TP Pertama</a></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
