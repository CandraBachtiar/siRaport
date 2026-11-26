@extends('layouts.admin-v2')

@section('title', 'Identitas Sekolah')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 sm:text-3xl">
                Identitas Sekolah
            </h1>

            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                Kelola informasi identitas sekolah yang digunakan
                pada dokumen dan pencetakan rapor siswa.
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <h2 class="text-lg font-bold text-slate-900">
                    Informasi Sekolah
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Pastikan informasi sesuai dengan data resmi sekolah.
                </p>
            </div>

            <form method="POST"
                action="{{ route('admin.identitas-sekolah.update') }}"
                class="space-y-6">

                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="nama_sekolah"
                            class="mb-2 block text-sm font-semibold text-slate-700">
                            Nama Sekolah
                            <span class="text-red-500">*</span>
                        </label>

                        <input type="text"
                            id="nama_sekolah"
                            name="nama_sekolah"
                            value="{{ old('nama_sekolah', $school?->nama_sekolah) }}"
                            maxlength="255"
                            placeholder="Masukkan nama sekolah"
                            required
                            @error('nama_sekolah')
                                aria-invalid="true"
                                aria-describedby="nama_sekolah_error"
                            @enderror
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">

                        @error('nama_sekolah')
                            <p id="nama_sekolah_error"
                                class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label for="npsn"
                            class="mb-2 block text-sm font-semibold text-slate-700">
                            NPSN
                        </label>

                        <input type="text"
                            id="npsn"
                            name="npsn"
                            value="{{ old('npsn', $school?->npsn) }}"
                            maxlength="20"
                            placeholder="Masukkan NPSN sekolah"
                            @error('npsn')
                                aria-invalid="true"
                                aria-describedby="npsn_error"
                            @enderror
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">

                        @error('npsn')
                            <p id="npsn_error"
                                class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="alamat"
                            class="mb-2 block text-sm font-semibold text-slate-700">
                            Alamat Sekolah
                        </label>

                        <textarea id="alamat"
                            name="alamat"
                            rows="4"
                            maxlength="2000"
                            placeholder="Masukkan alamat lengkap sekolah"
                            @error('alamat')
                                aria-invalid="true"
                                aria-describedby="alamat_error"
                            @enderror
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">{{ old('alamat', $school?->alamat) }}</textarea>

                        @error('alamat')
                            <p id="alamat_error"
                                class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="border-t border-slate-200 pt-6">
                    <h2 class="text-lg font-bold text-slate-900">
                        Informasi Kepala Sekolah
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Informasi ini digunakan pada bagian pengesahan rapor.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="nama_kepala_sekolah"
                            class="mb-2 block text-sm font-semibold text-slate-700">
                            Nama Kepala Sekolah
                        </label>

                        <input type="text"
                            id="nama_kepala_sekolah"
                            name="nama_kepala_sekolah"
                            value="{{ old('nama_kepala_sekolah', $school?->nama_kepala_sekolah) }}"
                            maxlength="255"
                            placeholder="Masukkan nama kepala sekolah"
                            @error('nama_kepala_sekolah')
                                aria-invalid="true"
                                aria-describedby="nama_kepala_sekolah_error"
                            @enderror
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">

                        @error('nama_kepala_sekolah')
                            <p id="nama_kepala_sekolah_error"
                                class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label for="nip_kepala_sekolah"
                            class="mb-2 block text-sm font-semibold text-slate-700">
                            NIP Kepala Sekolah
                        </label>

                        <input type="text"
                            id="nip_kepala_sekolah"
                            name="nip_kepala_sekolah"
                            value="{{ old('nip_kepala_sekolah', $school?->nip_kepala_sekolah) }}"
                            maxlength="50"
                            placeholder="Masukkan NIP kepala sekolah"
                            @error('nip_kepala_sekolah')
                                aria-invalid="true"
                                aria-describedby="nip_kepala_sekolah_error"
                            @enderror
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">

                        @error('nip_kepala_sekolah')
                            <p id="nip_kepala_sekolah_error"
                                class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-6">
                    <a href="{{ route('admin.dashboard') }}"
                        class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        Kembali
                    </a>

                    <button type="submit"
                        class="rounded-xl bg-raporku-navy-deep px-5 py-3 text-sm font-semibold text-white hover:opacity-90">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection