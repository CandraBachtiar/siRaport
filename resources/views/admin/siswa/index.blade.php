@extends('layouts.admin-v2')

@section('title', 'Data Siswa')

@section('content')
    <section class="mx-auto max-w-7xl">
        @php($selectedClassLabel = isset($selectedClass) ? trim($selectedClass->tingkat.' '.$selectedClass->nama) : null)

        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-600">Master Data</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">
                    @if ($page === 'classes')
                        Rombongan Belajar Tingkat {{ $selectedTingkat }}
                    @elseif ($page === 'students' && $selectedClass)
                        Siswa {{ $selectedClassLabel }}
                    @elseif ($page === 'students')
                        Hasil Pencarian Siswa
                    @else
                        Data Siswa
                    @endif
                </h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    @if ($page === 'classes')
                        Pilih rombongan belajar untuk melihat daftar siswanya.
                    @elseif ($page === 'students' && $selectedClass)
                        Daftar siswa dan wali kelas pada rombongan belajar ini.
                    @elseif ($page === 'students')
                        Hasil pencarian dari seluruh kelas yang terdaftar.
                    @else
                        Pilih tingkat kelas atau cari siswa dari seluruh kelas.
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.siswa.import.create') }}" class="rk-button rk-button-secondary"><x-nav-icon name="upload" class="size-4 text-emerald-700" /> Import Excel</a>
                <a href="{{ route('admin.siswa.create') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20">
                    <span class="text-lg leading-none" aria-hidden="true">+</span> Tambah Siswa
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700" role="alert">{{ session('error') }}</div>
        @endif

        @if ($page !== 'levels')
            <nav class="mt-6 flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-500" aria-label="Breadcrumb">
                <a href="{{ route('admin.siswa.index') }}" class="transition hover:text-emerald-700">Data Siswa</a>
                @if ($page === 'classes')
                    <span aria-hidden="true">/</span>
                    <span class="text-slate-800">Tingkat {{ $selectedTingkat }}</span>
                @elseif ($page === 'students' && $selectedClass)
                    <span aria-hidden="true">/</span>
                    <a href="{{ route('admin.siswa.index', ['tingkat' => $selectedClass->tingkat]) }}" class="transition hover:text-emerald-700">Tingkat {{ $selectedClass->tingkat }}</a>
                    <span aria-hidden="true">/</span>
                    <span class="text-slate-800">{{ $selectedClassLabel }}</span>
                @elseif ($page === 'students')
                    <span aria-hidden="true">/</span>
                    <span class="text-slate-800">Hasil Pencarian</span>
                @endif
            </nav>
        @endif

        <form method="GET" action="{{ route('admin.siswa.index') }}" class="mt-6 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.03] sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
            @if ($page === 'students' && $selectedClass)
                <input type="hidden" name="kelas_id" value="{{ $selectedClass->id }}">
            @endif
            <div class="grid gap-2">
                <label for="search" class="text-sm font-semibold text-slate-700">Cari Siswa</label>
                <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Cari nama, NIS, atau NISN..." class="h-11 rounded-xl border border-slate-300 bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">
                @if ($page === 'students' && $selectedClass)
                    <p class="text-xs text-slate-500">Pencarian dibatasi pada {{ $selectedClassLabel }}. Kembali ke Data Siswa untuk mencari di semua kelas.</p>
                @else
                    <p class="text-xs text-slate-500">Pencarian mencakup seluruh kelas.</p>
                @endif
            </div>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex h-11 flex-1 items-center justify-center rounded-xl bg-[#102d4b] px-5 text-sm font-bold text-white transition hover:bg-[#0b2945] sm:flex-none">Cari</button>
                @if ($search !== '')
                    @if ($page === 'students' && $selectedClass)
                        <a href="{{ route('admin.siswa.index', ['kelas_id' => $selectedClass->id]) }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-bold text-slate-600 transition hover:bg-slate-50">Reset</a>
                    @else
                        <a href="{{ route('admin.siswa.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-bold text-slate-600 transition hover:bg-slate-50">Reset</a>
                    @endif
                @endif
            </div>
        </form>

        @if ($page === 'levels')
            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($levels as $level)
                    <a href="{{ route('admin.siswa.index', ['tingkat' => $level['tingkat']]) }}" class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/[0.03] transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-emerald-500/20">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-emerald-600">Tingkat Kelas</p>
                                <h2 class="mt-2 text-2xl font-extrabold text-[#102d4b]">{{ $level['tingkat'] }}</h2>
                            </div>
                            <span class="grid size-11 place-items-center rounded-xl bg-emerald-50 text-emerald-700" aria-hidden="true"><x-nav-icon name="kelas" class="size-5" /></span>
                        </div>
                        <div class="mt-6 flex flex-wrap gap-2 text-sm font-semibold">
                            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-slate-600">{{ $level['class_count'] }} rombel</span>
                            <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-emerald-700">{{ $level['student_count'] }} siswa</span>
                        </div>
                        <span class="mt-6 inline-flex text-sm font-bold text-emerald-700 group-hover:text-emerald-800">Lihat rombongan belajar <span class="ml-2" aria-hidden="true">→</span></span>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center sm:col-span-2 xl:col-span-3">
                        <p class="font-bold text-slate-700">Belum ada data kelas</p>
                        <p class="mt-1 text-sm text-slate-500">Tambahkan kelas terlebih dahulu untuk mulai mengelola siswa.</p>
                    </div>
                @endforelse
            </div>
        @elseif ($page === 'classes')
            <div class="mt-6 flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-4 text-sm">
                <span class="font-semibold text-slate-600">{{ $classes->count() }} rombongan belajar</span>
                <a href="{{ route('admin.siswa.index') }}" class="font-bold text-emerald-700 hover:text-emerald-800">Kembali ke tingkat kelas</a>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-2">
                @forelse ($classes as $kelas)
                    <a href="{{ route('admin.siswa.index', ['kelas_id' => $kelas->id]) }}" class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/[0.03] transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-emerald-500/20">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-emerald-600">Rombongan Belajar</p>
                                <h2 class="mt-2 text-2xl font-extrabold text-[#102d4b]">{{ trim($kelas->tingkat.' '.$kelas->nama) }}</h2>
                            </div>
                            <span class="grid size-11 place-items-center rounded-xl bg-slate-100 text-lg font-extrabold text-slate-500" aria-hidden="true">{{ $loop->iteration }}</span>
                        </div>
                        <div class="mt-5 min-h-12 text-sm">
                            @if ($kelas->waliKelas?->user)
                                <p class="text-slate-500">Wali kelas</p>
                                <p class="font-bold text-slate-800">{{ $kelas->waliKelas->user->name }}</p>
                            @else
                                <p class="font-semibold text-amber-700">Wali kelas belum ditetapkan</p>
                            @endif
                        </div>
                        <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                            <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-bold text-emerald-700">{{ $kelas->siswa_count }} siswa</span>
                            <span class="text-sm font-bold text-emerald-700 group-hover:text-emerald-800">Lihat siswa <span class="ml-1" aria-hidden="true">→</span></span>
                        </div>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center sm:col-span-2 xl:col-span-3">
                        <p class="font-bold text-slate-700">Belum ada rombongan belajar pada tingkat ini</p>
                    </div>
                @endforelse
            </div>
        @else
            @if ($selectedClass)
                <div class="mt-6 flex flex-col gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 p-5 text-sm sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-bold text-emerald-950">{{ $selectedClassLabel }} <span class="font-medium text-emerald-800">· {{ $selectedClass->siswa_count }} siswa</span></p>
                        <p class="mt-1 text-emerald-800">Wali kelas: {{ $selectedClass->waliKelas?->user?->name ?? 'Belum ditetapkan' }}</p>
                    </div>
                    <a href="{{ route('admin.siswa.index', ['tingkat' => $selectedClass->tingkat]) }}" class="font-bold text-emerald-800 hover:text-emerald-950">Kembali ke rombongan belajar</a>
                </div>
            @else
                <div class="mt-6 rounded-2xl border border-slate-200 bg-white px-5 py-4 text-sm font-semibold text-slate-600">
                    Jumlah hasil: {{ $siswa->total() }} siswa
                </div>
            @endif

            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.03]">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <caption class="sr-only">Daftar siswa</caption>
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="w-16 px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">No</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">NIS</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">NISN</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Nama Siswa</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Jenis Kelamin</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Kelas</th>
                                <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($siswa as $item)
                                <tr class="transition hover:bg-slate-50/80">
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">{{ $siswa->firstItem() + $loop->index }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-700">{{ $item->nis }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $item->nisn ?: '—' }}</td>
                                    <td class="px-5 py-4 text-sm font-bold text-[#102d4b]">{{ $item->nama }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $item->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ trim($item->kelas->tingkat.' '.$item->kelas->nama) }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <a href="{{ route('admin.siswa.edit', $item) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">Edit</a>
                                            <form method="POST" action="{{ route('admin.siswa.destroy', $item) }}" onsubmit="return confirm('Yakin ingin menghapus data siswa ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600 transition hover:bg-red-50">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-16 text-center">
                                        <span class="mx-auto grid size-12 place-items-center rounded-full bg-slate-100 text-lg font-bold text-slate-400">S</span>
                                        @if ($search !== '')
                                            <p class="mt-4 font-bold text-slate-700">Siswa tidak ditemukan</p>
                                            <p class="mt-1 text-sm text-slate-500">Coba kata kunci lain atau reset pencarian.</p>
                                        @elseif ($selectedClass)
                                            <p class="mt-4 font-bold text-slate-700">Belum ada siswa di kelas ini</p>
                                            <p class="mt-1 text-sm text-slate-500">Siswa baru dapat ditambahkan tanpa mengubah data kelas lain.</p>
                                        @else
                                            <p class="mt-4 font-bold text-slate-700">Belum ada data siswa</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($siswa->hasPages())
                    <div class="border-t border-slate-200 px-5 py-4">
                        {{ $siswa->links() }}
                    </div>
                @endif
            </div>
        @endif
    </section>
@endsection
