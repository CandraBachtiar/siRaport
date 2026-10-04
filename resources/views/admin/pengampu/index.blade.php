@extends('layouts.admin')

@section('title', 'Data Pengampu')

@section('content')
    <section class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-600">Master Data</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Data Pengampu</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Atur guru, mata pelajaran, kelas, dan tahun ajaran.</p>
            </div>
            <a href="{{ route('admin.pengampu.create') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20"><span class="text-lg leading-none" aria-hidden="true">+</span> Tambah Pengampu</a>
        </div>

        <form method="GET" action="{{ route('admin.pengampu.index') }}" class="mt-8 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.03] sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
            <div class="grid gap-2 lg:col-span-2"><label for="search" class="text-sm font-semibold text-slate-700">Cari Pengampu</label><input id="search" name="search" type="search" value="{{ $search }}" placeholder="Nama guru, mapel, atau kelas" class="h-11 rounded-xl border border-slate-300 bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"></div>
            <div class="grid gap-2"><label for="guru_id" class="text-sm font-semibold text-slate-700">Guru</label><select id="guru_id" name="guru_id" class="h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"><option value="">Semua Guru</option>@foreach ($guruList as $guru)<option value="{{ $guru->id }}" @selected($guruId === $guru->id)>{{ $guru->nama }}</option>@endforeach</select></div>
            <div class="grid gap-2"><label for="tahun_ajaran_id" class="text-sm font-semibold text-slate-700">Tahun Ajaran</label><select id="tahun_ajaran_id" name="tahun_ajaran_id" class="h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"><option value="">Semua Tahun</option>@foreach ($tahunAjaranList as $tahun)<option value="{{ $tahun->id }}" @selected($tahunAjaranId === $tahun->id)>{{ $tahun->tahun }} — {{ $tahun->semester }}</option>@endforeach</select></div>
            <div class="flex gap-2"><button type="submit" class="inline-flex h-11 flex-1 items-center justify-center rounded-xl bg-[#102d4b] px-4 text-sm font-bold text-white transition hover:bg-[#0b2945]">Filter</button><a href="{{ route('admin.pengampu.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-bold text-slate-600 transition hover:bg-slate-50">Reset</a></div>
        </form>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.03]"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200"><thead class="bg-slate-50"><tr><th class="w-16 px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">No</th><th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Guru</th><th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Mata Pelajaran</th><th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Kelas</th><th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Tahun Ajaran</th><th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100 bg-white">
            @forelse ($pengampu as $item)
                <tr class="transition hover:bg-slate-50/80"><td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">{{ $pengampu->firstItem() + $loop->index }}</td><td class="whitespace-nowrap px-5 py-4 text-sm font-bold text-[#102d4b]">{{ $item->guru->nama }}</td><td class="px-5 py-4 text-sm text-slate-700"><span class="font-semibold">{{ $item->mataPelajaran->nama }}</span><small class="ml-1 text-slate-400">({{ $item->mataPelajaran->kode }})</small></td><td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $item->kelas->tingkat }} {{ $item->kelas->nama }}</td><td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $item->tahunAjaran->tahun }} — {{ $item->tahunAjaran->semester }} @if ($item->tahunAjaran->aktif)<span class="ml-1 inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Aktif</span>@endif</td><td class="whitespace-nowrap px-5 py-4 text-right"><div class="inline-flex items-center gap-2"><a href="{{ route('admin.pengampu.edit', $item) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">Edit</a><form method="POST" action="{{ route('admin.pengampu.destroy', $item) }}" onsubmit="return confirm('Yakin ingin menghapus data pengampu ini?')">@csrf @method('DELETE')<button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600 transition hover:bg-red-50">Hapus</button></form></div></td></tr>
            @empty
                <tr><td colspan="6" class="px-5 py-16 text-center"><span class="mx-auto grid size-12 place-items-center rounded-full bg-slate-100 text-lg font-bold text-slate-400">P</span><p class="mt-4 font-bold text-slate-700">Data pengampu belum tersedia</p><p class="mt-1 text-sm text-slate-500">Tambahkan data pengampu untuk mengatur pembelajaran.</p></td></tr>
            @endforelse
        </tbody></table></div>@if ($pengampu->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $pengampu->links() }}</div>@endif</div>
    </section>
@endsection
