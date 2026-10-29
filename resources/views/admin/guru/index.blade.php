@extends('layouts.admin-v2')

@section('title', 'Data Guru')

@section('content')
    <section class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-600">Master Data</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Data Guru</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Kelola identitas dan akun guru yang terhubung ke RaporKu.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.guru.import.create') }}" class="rk-button rk-button-secondary"><x-nav-icon name="upload" class="size-4 text-emerald-700" /> Import Excel</a>
                <a href="{{ route('admin.guru.create') }}" class="rk-button rk-button-primary"><span class="text-lg leading-none" aria-hidden="true">+</span> Tambah Guru</a>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.guru.index') }}" class="mt-8 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.03] sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
            <div class="grid gap-2">
                <label for="q" class="text-sm font-semibold text-slate-700">Cari Guru</label>
                <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari nama, NIP, atau email..." class="h-11 rounded-xl border border-slate-300 bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex h-11 flex-1 items-center justify-center rounded-xl bg-[#102d4b] px-5 text-sm font-bold text-white transition hover:bg-[#0b2945] sm:flex-none">Cari</button>
                @if ($search !== '')
                    <a href="{{ route('admin.guru.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-bold text-slate-600 transition hover:bg-slate-50">Reset</a>
                @endif
            </div>
        </form>

        <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.03]">
            <div class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-600" role="status" aria-live="polite">
                @if ($search !== '')
                    Jumlah hasil pencarian: {{ $gurus->total() }} guru
                @else
                    Jumlah guru: {{ $gurus->total() }}
                @endif
            </div>
            <div class="overflow-x-auto">   
                <table class="min-w-full divide-y divide-slate-200">
                    <caption class="sr-only">Daftar guru</caption>
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-16 px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">No</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">NIP</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Nama Guru</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Email</th>
                            <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($gurus as $guru)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">{{ $gurus->firstItem() + $loop->index }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-700">{{ $guru->nip }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-bold text-[#102d4b]">{{ $guru->user->name }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $guru->user->email }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.guru.edit', $guru) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">Edit</a>
                                        <form method="POST" action="{{ route('admin.guru.destroy', $guru) }}" onsubmit="return confirm('Yakin ingin menghapus data guru ini?')">
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
                                    <span class="mx-auto grid size-12 place-items-center rounded-full bg-slate-100 text-lg font-bold text-slate-400">G</span>
                                    @if ($search !== '')
                                        <p class="mt-4 font-bold text-slate-700">Guru tidak ditemukan</p>
                                        <p class="mt-1 text-sm text-slate-500">Coba kata kunci lain atau hapus pencarian.</p>
                                        <a href="{{ route('admin.guru.index') }}" class="mt-4 inline-flex h-10 items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Hapus pencarian</a>
                                    @else
                                        <p class="mt-4 font-bold text-slate-700">Belum ada data guru</p>
                                        <p class="mt-1 text-sm text-slate-500">Tambahkan guru pertama untuk membuat akun secara otomatis.</p>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($gurus->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $gurus->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
