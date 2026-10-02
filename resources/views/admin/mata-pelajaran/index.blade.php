@extends('layouts.admin')

@section('title', 'Data Mata Pelajaran')

@section('content')
    <section class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-600">Master Data</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Data Mata Pelajaran</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Kelola kode, nama, dan KKM mata pelajaran di EduRaport.</p>
            </div>
            <a href="{{ route('admin.mata-pelajaran.create') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20">
                <span class="text-lg leading-none" aria-hidden="true">+</span> Tambah Mata Pelajaran
            </a>
        </div>

        <form method="GET" action="{{ route('admin.mata-pelajaran.index') }}" class="mt-8 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.03] sm:flex-row sm:items-end">
            <div class="grid flex-1 gap-2">
                <label for="search" class="text-sm font-semibold text-slate-700">Cari Mata Pelajaran</label>
                <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Cari kode atau nama mata pelajaran" class="h-11 rounded-xl border border-slate-300 bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="inline-flex h-11 flex-1 items-center justify-center rounded-xl bg-[#102d4b] px-5 text-sm font-bold text-white transition hover:bg-[#0b2945] sm:flex-none">Cari</button>
                @if ($search !== '')
                    <a href="{{ route('admin.mata-pelajaran.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-bold text-slate-600 transition hover:bg-slate-50">Reset</a>
                @endif
            </div>
        </form>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.03]">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-16 px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">No</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Kode</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Nama Mata Pelajaran</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">KKM</th>
                            <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($mataPelajaran as $item)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">{{ $mataPelajaran->firstItem() + $loop->index }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-700">{{ $item->kode }}</td>
                                <td class="px-5 py-4 text-sm font-bold text-[#102d4b]">{{ $item->nama }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $item->kkm }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.mata-pelajaran.edit', $item) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">Edit</a>
                                        <form method="POST" action="{{ route('admin.mata-pelajaran.destroy', $item) }}" onsubmit="return confirm('Yakin ingin menghapus data mata pelajaran ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600 transition hover:bg-red-50">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-16 text-center">
                                    <span class="mx-auto grid size-12 place-items-center rounded-full bg-slate-100 text-lg font-bold text-slate-400">M</span>
                                    <p class="mt-4 font-bold text-slate-700">Data mata pelajaran tidak ditemukan</p>
                                    <p class="mt-1 text-sm text-slate-500">Tambahkan mata pelajaran baru atau sesuaikan pencarian.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($mataPelajaran->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $mataPelajaran->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
