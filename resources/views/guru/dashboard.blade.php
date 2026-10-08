@extends('layouts.guru-v2')

@section('title', 'Dashboard Guru')

@section('content')
    <section class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-600">Dashboard</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Selamat datang, {{ $guru->nama }}</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Ringkasan identitas dan tugas mengajar Anda.</p>
            </div>
            <p class="text-sm font-medium text-slate-500">{{ now()->translatedFormat('l, d F Y') }}</p>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.03]">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Identitas Guru</p>
                <dl class="mt-5 grid gap-4">
                    <div><dt class="text-xs font-semibold text-slate-400">Nama</dt><dd class="mt-1 text-sm font-bold text-slate-700">{{ $guru->nama }}</dd></div>
                    <div><dt class="text-xs font-semibold text-slate-400">NIP</dt><dd class="mt-1 text-sm font-bold text-slate-700">{{ $guru->nip ?: 'Belum tersedia' }}</dd></div>
                </dl>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.03]">
                <div class="flex items-center justify-between">
                    <span class="grid size-11 place-items-center rounded-xl bg-emerald-50 text-sm font-extrabold text-emerald-700">P</span>
                    <span class="text-xs font-semibold text-slate-400">Total data</span>
                </div>
                <p class="mt-5 text-3xl font-extrabold tracking-tight text-[#102d4b]">{{ number_format($pengampu->count(), 0, ',', '.') }}</p>
                <p class="mt-1 text-sm font-medium text-slate-500">Jumlah Pengampu</p>
            </article>
        </div>

        <article class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.03]">
            <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                <h2 class="font-bold text-[#102d4b]">Daftar Pengampu</h2>
                <p class="mt-1 text-sm text-slate-500">Mata pelajaran dan kelas yang menjadi tanggung jawab Anda.</p>
            </div>

            @if ($pengampu->isEmpty())
                <div class="px-5 py-12 text-center sm:px-6">
                    <span class="mx-auto grid size-12 place-items-center rounded-xl bg-slate-100 text-lg text-slate-500">P</span>
                    <h3 class="mt-4 font-bold text-slate-700">Belum ada data pengampu</h3>
                    <p class="mt-2 text-sm text-slate-500">Tugas mengajar Anda belum ditambahkan oleh administrator.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <caption class="sr-only">Daftar tugas mengajar dan progres pengisian nilai</caption>
                        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3 sm:px-6">Mata Pelajaran</th>
                                <th scope="col" class="px-5 py-3 sm:px-6">Kelas</th>
                                <th scope="col" class="px-5 py-3 sm:px-6">Tahun Ajaran</th>
                                <th scope="col" class="px-5 py-3 sm:px-6">Semester</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($pengampu as $tugasMengajar)
                                <tr>
                                    <td class="px-5 py-4 font-semibold text-slate-700 sm:px-6">{{ $tugasMengajar->mataPelajaran->nama }}</td>
                                    <td class="px-5 py-4 text-slate-600 sm:px-6">{{ $tugasMengajar->kelas->tingkat }} {{ $tugasMengajar->kelas->nama }}</td>
                                    <td class="px-5 py-4 text-slate-600 sm:px-6">{{ $tugasMengajar->tahunAjaran->tahun }}</td>
                                    <td class="px-5 py-4 text-slate-600 sm:px-6">{{ $tugasMengajar->tahunAjaran->semester }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    </section>
@endsection
