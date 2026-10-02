@extends('layouts.admin')

@section('title', 'Data Kelas')

@section('content')
    <section class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-600">Master Data</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Data Kelas</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Kelola kelas, wali kelas, dan jumlah siswa yang terdaftar.</p>
            </div>
            <a href="{{ route('admin.kelas.create') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20">
                <span class="text-lg leading-none" aria-hidden="true">+</span> Tambah Kelas
            </a>
        </div>

        <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.03]">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-16 px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">No</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Nama Kelas</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Wali Kelas</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Jumlah Siswa</th>
                            <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($kelas as $item)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">{{ $kelas->firstItem() + $loop->index }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-bold text-[#102d4b]">{{ $item->tingkat }} {{ $item->nama }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ $item->waliKelas?->user->name ?? 'Belum ditentukan' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $item->siswa_count }} siswa</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.kelas.edit', $item) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">Edit</a>
                                        <form method="POST" action="{{ route('admin.kelas.destroy', $item) }}" onsubmit="return confirm('Yakin ingin menghapus data kelas ini?')">
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
                                    <span class="mx-auto grid size-12 place-items-center rounded-full bg-slate-100 text-lg font-bold text-slate-400">K</span>
                                    <p class="mt-4 font-bold text-slate-700">Belum ada data kelas</p>
                                    <p class="mt-1 text-sm text-slate-500">Tambahkan kelas pertama dan tentukan wali kelas jika diperlukan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($kelas->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $kelas->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
