@extends('layouts.admin-v2')

@section('title', 'Import Data Siswa')

@section('content')
    <section class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-600">Data Siswa</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Import Data Siswa</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Periksa data dari Excel terlebih dahulu. Fitur ini hanya menambahkan siswa baru.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.siswa.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Kembali ke Data Siswa</a>
                <a href="{{ route('admin.siswa.import.template') }}" class="inline-flex h-11 items-center justify-center rounded-xl bg-emerald-600 px-5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700">Download Template Excel</a>
            </div>
        </div>

        @if (session('siswa_import_header_error'))
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700" role="alert">{{ session('siswa_import_header_error') }}</div>
        @endif
        @if (session('error'))
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700" role="alert">
                <p>Data belum dapat diimpor:</p>
                <ul class="mt-2 list-inside list-disc font-medium">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/[0.03] sm:p-8">
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm leading-6 text-emerald-900">
                <p><strong class="font-bold">Kolom:</strong> NIS, NISN, Nama Siswa, Jenis Kelamin, Kelas, Tempat Lahir, Tanggal Lahir, dan Alamat.</p>
                <p class="mt-2">NIS, nama, jenis kelamin, dan kelas wajib diisi. NISN, tempat lahir, tanggal lahir, dan alamat boleh kosong. Jenis kelamin harus <strong>L</strong> atau <strong>P</strong>. Isi kolom Kelas dengan ID dari lembar <strong>Daftar Kelas</strong>, bukan nama kelas.</p>
                <p class="mt-2">Tanggal lahir dapat berupa tanggal Excel atau teks dengan format <strong>YYYY-MM-DD</strong>. Kolom NIS dan NISN pada template sudah diformat sebagai teks agar nol di depan tetap tersimpan. File .xlsx atau .xls maksimal 5 MB.</p>
            </div>
            <form method="POST" action="{{ route('admin.siswa.import.preview') }}" enctype="multipart/form-data" class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-end">
                @csrf
                <div class="grid flex-1 gap-2">
                    <label for="file" class="text-sm font-semibold text-slate-700">File Excel Siswa</label>
                    <input id="file" name="file" type="file" accept=".xlsx,.xls" required class="block h-12 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-bold file:text-emerald-700 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10" @if ($errors->has('file')) aria-invalid="true" aria-describedby="file-error" @endif>
                    @error('file')
                        <p id="file-error" class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="inline-flex h-12 items-center justify-center rounded-xl bg-[#102d4b] px-6 text-sm font-bold text-white transition hover:bg-[#0b2945]">Periksa Data</button>
            </form>
        </div>

        @if ($previewRows !== [])
            @php($validCount = collect($previewRows)->where('valid', true)->count())
            @php($errorCount = count($previewRows) - $validCount)
            <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.03]">
                <div class="flex flex-wrap gap-3 border-b border-slate-200 p-5 text-sm font-bold">
                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-slate-600">Total: {{ count($previewRows) }}</span>
                    <span class="rounded-full bg-emerald-100 px-3 py-1.5 text-emerald-700">Valid: {{ $validCount }}</span>
                    <span class="rounded-full bg-red-100 px-3 py-1.5 text-red-700">Error: {{ $errorCount }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <caption class="sr-only">Preview data siswa dari file Excel</caption>
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Baris</th>
                                <th class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">NIS / NISN</th>
                                <th class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Nama</th>
                                <th class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">JK</th>
                                <th class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Kelas</th>
                                <th class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Tempat / Tanggal Lahir</th>
                                <th class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Alamat</th>
                                <th class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status / Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($previewRows as $row)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-slate-500">{{ $row['row'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-slate-700"><span class="font-semibold">{{ $row['nis'] ?: '—' }}</span><br><span class="text-slate-500">{{ $row['nisn'] ?: '—' }}</span></td>
                                    <td class="px-4 py-4 text-sm font-bold text-[#102d4b]">{{ $row['name'] ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-slate-600">{{ $row['gender'] ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-slate-600">{{ $row['kelas_label'] }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-600">{{ $row['birth_place'] ?: '—' }}<br>{{ $row['birth_date'] ?: '—' }}</td>
                                    <td class="max-w-xs px-4 py-4 text-sm text-slate-600">{{ $row['address'] ?: '—' }}</td>
                                    <td class="min-w-56 px-4 py-4 text-sm">
                                        @if ($row['valid'])
                                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">VALID</span>
                                        @else
                                            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">ERROR</span>
                                            <p class="mt-2 font-semibold text-red-600">{{ $row['message'] }}</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($errorCount === 0)
                    <div class="flex justify-end border-t border-slate-200 p-5">
                        <form method="POST" action="{{ route('admin.siswa.import.store') }}">
                            @csrf
                            <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-emerald-600 px-6 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700">Import Data</button>
                        </form>
                    </div>
                @endif
            </div>
        @endif
    </section>
@endsection
