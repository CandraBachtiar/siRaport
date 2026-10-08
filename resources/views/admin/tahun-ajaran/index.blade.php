@extends('layouts.admin-v2')

@section('title', 'Data Tahun Ajaran')

@section('content')
    <section class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-600">Master Data</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Data Tahun Ajaran</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Kelola periode tahun ajaran dan semester aktif.</p>
            </div>
            <a href="{{ route('admin.tahun-ajaran.create') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20">
                <span class="text-lg leading-none" aria-hidden="true">+</span> Tambah Tahun Ajaran
            </a>
        </div>

        <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.03]">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <caption class="sr-only">Daftar tahun ajaran</caption>
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-16 px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">No</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Tahun Ajaran</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Semester</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                            <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($tahunAjaran as $item)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">{{ $tahunAjaran->firstItem() + $loop->index }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-bold text-[#102d4b]">{{ $item->tahun }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $item->semester }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    @if ($item->aktif)
                                        <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Aktif</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">Tidak Aktif</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.tahun-ajaran.edit', $item) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">Edit</a>
                                        <form method="POST" action="{{ route('admin.tahun-ajaran.destroy', $item) }}" onsubmit="return confirm('Yakin ingin menghapus data tahun ajaran ini?')">
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
                                    <span class="mx-auto grid size-12 place-items-center rounded-full bg-slate-100 text-lg font-bold text-slate-400">T</span>
                                    <p class="mt-4 font-bold text-slate-700">Data tahun ajaran belum tersedia</p>
                                    <p class="mt-1 text-sm text-slate-500">Tambahkan tahun ajaran untuk mulai mengelola periode akademik.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($tahunAjaran->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $tahunAjaran->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
