@extends('layouts.admin-v2')

@section('title', 'Riwayat Kelas Siswa')

@section('content')
    <section class="mx-auto grid max-w-7xl gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <a href="{{ route('admin.siswa.index', ['search' => $siswa->nis]) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-emerald-700">&larr; Kembali ke daftar siswa</a>
                <p class="mt-5 text-sm font-semibold text-emerald-600">Data Siswa</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-[#102d4b] sm:text-3xl">Riwayat Kelas</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Lihat dan catat penempatan kelas berdasarkan bukti sekolah.</p>
            </div>
            <a href="{{ route('admin.siswa.edit', $siswa) }}" class="inline-flex min-h-11 shrink-0 items-center justify-center whitespace-nowrap rounded-xl border border-slate-300 bg-white px-3 text-xs font-bold text-slate-700 transition hover:bg-slate-50 sm:text-sm">Edit data siswa</a>
        </div>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(16rem,0.65fr)]">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.03] sm:p-6">
                <p class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Identitas Siswa</p>
                <h2 class="mt-2 text-xl font-extrabold text-[#102d4b]">{{ $siswa->nama }}</h2>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="font-semibold text-slate-500">NIS</dt><dd class="mt-1 font-bold text-slate-800">{{ $siswa->nis }}</dd></div>
                    <div><dt class="font-semibold text-slate-500">NISN</dt><dd class="mt-1 font-bold text-slate-800">{{ $siswa->nisn ?: 'Belum diisi' }}</dd></div>
                </dl>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 sm:p-6">
                <p class="text-xs font-extrabold uppercase tracking-wider text-emerald-800">Kelas Aktif Saat Ini</p>
                <p class="mt-2 text-xl font-extrabold text-emerald-950">{{ $siswa->kelas ? trim($siswa->kelas->tingkat.' '.$siswa->kelas->nama) : 'Belum ditentukan' }}</p>
                <p class="mt-2 text-sm leading-6 text-emerald-900">Pencatatan riwayat di halaman ini tidak mengubah kelas aktif siswa.</p>
            </article>
        </div>

        @if (! $auditReady)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-900" role="status">
                <p class="font-bold">Fitur pencatatan sementara belum tersedia.</p>
                <p class="mt-1">Migration audit riwayat penempatan belum diterapkan. Riwayat yang sudah ada tetap dapat dilihat; pencatatan, penutupan, dan koreksi akan tersedia setelah migration audit diverifikasi dan diterapkan.</p>
            </div>
        @endif

        <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.03] sm:p-6" aria-labelledby="history-list-title">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-wider text-emerald-600">Catatan Sistem</p>
                    <h2 id="history-list-title" class="mt-1 text-xl font-extrabold text-[#102d4b]">Riwayat Penempatan Tercatat</h2>
                </div>
                <span class="w-fit rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600">{{ $placements->count() }} catatan</span>
            </div>

            @if ($placements->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center">
                    <p class="font-bold text-slate-700">Belum ada riwayat penempatan yang tercatat dalam sistem.</p>
                    <p class="mt-2 text-sm text-slate-500">Kondisi ini tidak menjadi bukti bahwa siswa belum pernah berada di kelas lain.</p>
                </div>
            @else
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <caption class="sr-only">Riwayat penempatan kelas {{ $siswa->nama }}</caption>
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-xs font-extrabold uppercase tracking-wider text-slate-500">Kelas</th>
                                <th class="px-4 py-3 text-xs font-extrabold uppercase tracking-wider text-slate-500">Periode Akademik</th>
                                <th class="px-4 py-3 text-xs font-extrabold uppercase tracking-wider text-slate-500">Mulai</th>
                                <th class="px-4 py-3 text-xs font-extrabold uppercase tracking-wider text-slate-500">Berakhir</th>
                                <th class="px-4 py-3 text-xs font-extrabold uppercase tracking-wider text-slate-500">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-extrabold uppercase tracking-wider text-slate-500">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($placements as $placement)
                                <tr class="align-top">
                                    <td class="whitespace-nowrap px-4 py-4 text-sm font-bold text-slate-800">{{ trim($placement->kelas->tingkat.' '.$placement->kelas->nama) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-slate-700">{{ $placement->tahunAjaran->tahun }} &middot; {{ $placement->tahunAjaran->semester }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-slate-700">{{ $placement->mulai_pada->format('d/m/Y') }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-slate-700">{{ $placement->berakhir_pada?->format('d/m/Y') ?? 'Belum dicatat' }}</td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        @if ($placement->berakhir_pada === null)
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800">Belum ditutup</span>
                                        @else
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">Ditutup</span>
                                        @endif
                                    </td>
                                    <td class="min-w-72 px-4 py-4 text-right">
                                        <div class="grid gap-3 text-left">
                                            @if ($auditReady && $placement->berakhir_pada === null)
                                                <details class="rounded-lg border border-slate-200 p-3">
                                                    <summary class="cursor-pointer text-sm font-bold text-slate-700">Tutup penempatan</summary>
                                                    <form method="POST" action="{{ route('admin.siswa.riwayat.close', [$siswa, $placement]) }}" class="mt-3 grid gap-3" onsubmit="return confirm('Tutup penempatan ini pada tanggal yang dipilih?')">
                                                        @csrf
                                                        <div class="grid gap-1.5">
                                                            <label for="close-date-{{ $placement->id }}" class="text-xs font-semibold text-slate-600">Tanggal Akhir Penempatan</label>
                                                            <input id="close-date-{{ $placement->id }}" name="berakhir_pada" type="date" value="{{ old('berakhir_pada') }}" required class="h-10 rounded-lg border border-slate-300 px-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <label for="close-reason-{{ $placement->id }}" class="text-xs font-semibold text-slate-600">Alasan penutupan</label>
                                                            <textarea id="close-reason-{{ $placement->id }}" name="alasan" rows="2" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">{{ old('alasan') }}</textarea>
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <label for="close-proof-{{ $placement->id }}" class="text-xs font-semibold text-slate-600">Referensi dokumen bukti</label>
                                                            <input id="close-proof-{{ $placement->id }}" name="referensi_bukti" value="{{ old('referensi_bukti') }}" required class="h-10 rounded-lg border border-slate-300 px-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                                                        </div>
                                                        <button type="submit" class="min-h-10 rounded-lg bg-slate-800 px-4 text-sm font-bold text-white hover:bg-slate-900">Simpan penutupan</button>
                                                    </form>
                                                </details>
                                            @endif

                                            @if ($auditReady)
                                                <details class="rounded-lg border border-slate-200 p-3">
                                                    <summary class="cursor-pointer text-sm font-bold text-slate-700">Koreksi penempatan</summary>
                                                    <div class="mt-3 rounded-lg bg-amber-50 p-3 text-xs leading-5 text-amber-900">
                                                        <p class="font-bold">Nilai sebelumnya</p>
                                                        <p>{{ trim($placement->kelas->tingkat.' '.$placement->kelas->nama) }} &middot; {{ $placement->tahunAjaran->tahun }} {{ $placement->tahunAjaran->semester }}</p>
                                                        <p>{{ $placement->mulai_pada->format('d/m/Y') }} &ndash; {{ $placement->berakhir_pada?->format('d/m/Y') ?? 'Belum dicatat' }}</p>
                                                    </div>
                                                    <form method="POST" action="{{ route('admin.siswa.riwayat.correct', [$siswa, $placement]) }}" class="mt-3 grid gap-3" onsubmit="return confirm('Simpan koreksi ini dan catat nilai sebelum serta sesudahnya pada audit?')">
                                                        @csrf
                                                        @method('PUT')
                                                        <p class="text-xs font-bold text-slate-700">Nilai perubahan yang akan disimpan</p>
                                                        <div class="grid gap-3 sm:grid-cols-2">
                                                            <div class="grid gap-1.5">
                                                                <label for="correct-class-{{ $placement->id }}" class="text-xs font-semibold text-slate-600">Kelas penempatan</label>
                                                                <select id="correct-class-{{ $placement->id }}" name="kelas_id" required class="h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm">
                                                                    @foreach ($classes as $class)
                                                                        <option value="{{ $class->id }}" @selected((int) old('kelas_id', $placement->kelas_id) === $class->id)>{{ $class->tingkat }} {{ $class->nama }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="grid gap-1.5">
                                                                <label for="correct-period-{{ $placement->id }}" class="text-xs font-semibold text-slate-600">Periode akademik</label>
                                                                <select id="correct-period-{{ $placement->id }}" name="tahun_ajaran_id" required class="h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm">
                                                                    @foreach ($schoolYears as $schoolYear)
                                                                        <option value="{{ $schoolYear->id }}" @selected((int) old('tahun_ajaran_id', $placement->tahun_ajaran_id) === $schoolYear->id)>{{ $schoolYear->tahun }} &middot; {{ $schoolYear->semester }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="grid gap-3 sm:grid-cols-2">
                                                            <div class="grid gap-1.5">
                                                                <label for="correct-start-{{ $placement->id }}" class="text-xs font-semibold text-slate-600">Tanggal Mulai Penempatan</label>
                                                                <input id="correct-start-{{ $placement->id }}" name="mulai_pada" type="date" value="{{ old('mulai_pada', $placement->mulai_pada->format('Y-m-d')) }}" required class="h-10 rounded-lg border border-slate-300 px-3 text-sm">
                                                            </div>
                                                            <div class="grid gap-1.5">
                                                                <label for="correct-end-{{ $placement->id }}" class="text-xs font-semibold text-slate-600">Tanggal Akhir Penempatan</label>
                                                                <input id="correct-end-{{ $placement->id }}" name="berakhir_pada" type="date" value="{{ old('berakhir_pada', $placement->berakhir_pada?->format('Y-m-d')) }}" class="h-10 rounded-lg border border-slate-300 px-3 text-sm">
                                                            </div>
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <label for="correct-reason-{{ $placement->id }}" class="text-xs font-semibold text-slate-600">Alasan koreksi</label>
                                                            <textarea id="correct-reason-{{ $placement->id }}" name="alasan" rows="2" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('alasan') }}</textarea>
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <label for="correct-proof-{{ $placement->id }}" class="text-xs font-semibold text-slate-600">Referensi dokumen bukti</label>
                                                            <input id="correct-proof-{{ $placement->id }}" name="referensi_bukti" value="{{ old('referensi_bukti') }}" required class="h-10 rounded-lg border border-slate-300 px-3 text-sm">
                                                        </div>
                                                        <button type="submit" class="min-h-10 rounded-lg border border-amber-300 bg-amber-50 px-4 text-sm font-bold text-amber-900 hover:bg-amber-100">Simpan koreksi dan audit</button>
                                                    </form>
                                                </details>
                                            @endif

                                            @if ($auditReady && $placement->audit->isNotEmpty())
                                                <details class="rounded-lg border border-slate-200 p-3">
                                                    <summary class="cursor-pointer text-sm font-bold text-slate-700">Lihat audit ({{ $placement->audit->count() }})</summary>
                                                    <ul class="mt-3 grid gap-3 text-left">
                                                        @foreach ($placement->audit as $audit)
                                                            @php
                                                                $beforeClass = $audit->data_sebelum ? $classes->firstWhere('id', $audit->data_sebelum['kelas_id']) : null;
                                                                $afterClass = $audit->data_sesudah ? $classes->firstWhere('id', $audit->data_sesudah['kelas_id']) : null;
                                                                $beforePeriod = $audit->data_sebelum ? $schoolYears->firstWhere('id', $audit->data_sebelum['tahun_ajaran_id']) : null;
                                                                $afterPeriod = $audit->data_sesudah ? $schoolYears->firstWhere('id', $audit->data_sesudah['tahun_ajaran_id']) : null;
                                                            @endphp
                                                            <li class="border-t border-slate-100 pt-3 text-xs leading-5 text-slate-600">
                                                                <p class="font-bold text-slate-800">{{ ucfirst($audit->aksi) }} &middot; {{ $audit->user->name }} &middot; {{ $audit->created_at->format('d/m/Y H:i') }}</p>
                                                                @if ($audit->data_sebelum)
                                                                    <p>Sebelumnya: {{ $beforeClass ? trim($beforeClass->tingkat.' '.$beforeClass->nama) : 'Kelas tidak ditemukan' }} &middot; {{ $beforePeriod?->tahun }} {{ $beforePeriod?->semester }} &middot; {{ \Illuminate\Support\Carbon::parse($audit->data_sebelum['mulai_pada'])->format('d/m/Y') }} &ndash; {{ isset($audit->data_sebelum['berakhir_pada']) ? \Illuminate\Support\Carbon::parse($audit->data_sebelum['berakhir_pada'])->format('d/m/Y') : 'Belum dicatat' }}</p>
                                                                @endif
                                                                @if ($audit->data_sesudah)
                                                                    <p>Sesudah: {{ $afterClass ? trim($afterClass->tingkat.' '.$afterClass->nama) : 'Kelas tidak ditemukan' }} &middot; {{ $afterPeriod?->tahun }} {{ $afterPeriod?->semester }} &middot; {{ \Illuminate\Support\Carbon::parse($audit->data_sesudah['mulai_pada'])->format('d/m/Y') }} &ndash; {{ isset($audit->data_sesudah['berakhir_pada']) ? \Illuminate\Support\Carbon::parse($audit->data_sesudah['berakhir_pada'])->format('d/m/Y') : 'Belum dicatat' }}</p>
                                                                @endif
                                                                <p>Alasan: {{ $audit->alasan }}</p>
                                                                <p>Bukti: {{ $audit->referensi_bukti }}</p>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </details>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        @if ($auditReady)
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.03] sm:p-6" aria-labelledby="new-placement-title">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-wider text-emerald-600">Pencatatan Manual</p>
                    <h2 id="new-placement-title" class="mt-1 text-xl font-extrabold text-[#102d4b]">Catat Penempatan Baru</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Gunakan dokumen sekolah yang dapat diperiksa. Tindakan ini hanya mencatat riwayat, bukan memindahkan siswa atau memutuskan kenaikan kelas.</p>
                </div>

                <form method="POST" action="{{ route('admin.siswa.riwayat.store', $siswa) }}" class="mt-5 grid gap-5">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <label for="new-class" class="text-sm font-semibold text-slate-700">Kelas yang Ditempati</label>
                            <select id="new-class" name="kelas_id" required class="h-12 rounded-xl border border-slate-300 bg-white px-4 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                                <option value="">Pilih kelas</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" @selected((int) old('kelas_id') === $class->id)>{{ $class->tingkat }} {{ $class->nama }}</option>
                                @endforeach
                            </select>
                            @error('kelas_id')<p class="text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid gap-2">
                            <label for="new-period" class="text-sm font-semibold text-slate-700">Periode akademik</label>
                            <select id="new-period" name="tahun_ajaran_id" required class="h-12 rounded-xl border border-slate-300 bg-white px-4 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                                <option value="">Pilih periode</option>
                                @foreach ($schoolYears as $schoolYear)
                                    <option value="{{ $schoolYear->id }}" @selected((int) old('tahun_ajaran_id') === $schoolYear->id)>{{ $schoolYear->tahun }} &middot; {{ $schoolYear->semester }}</option>
                                @endforeach
                            </select>
                            @error('tahun_ajaran_id')<p class="text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid gap-2">
                            <label for="new-start" class="text-sm font-semibold text-slate-700">Tanggal Mulai Penempatan</label>
                            <input id="new-start" name="mulai_pada" type="date" value="{{ old('mulai_pada') }}" required class="h-12 rounded-xl border border-slate-300 px-4 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                            @error('mulai_pada')<p class="text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid gap-2">
                            <label for="new-end" class="text-sm font-semibold text-slate-700">Tanggal Akhir Penempatan</label>
                            <input id="new-end" name="berakhir_pada" type="date" value="{{ old('berakhir_pada') }}" class="h-12 rounded-xl border border-slate-300 px-4 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                            @error('berakhir_pada')<p class="text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <label for="new-reason" class="text-sm font-semibold text-slate-700">Alasan pencatatan</label>
                            <textarea id="new-reason" name="alasan" rows="3" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">{{ old('alasan') }}</textarea>
                            @error('alasan')<p class="text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <label for="new-proof" class="text-sm font-semibold text-slate-700">Referensi dokumen bukti</label>
                            <input id="new-proof" name="referensi_bukti" value="{{ old('referensi_bukti') }}" required placeholder="Contoh: nomor surat keputusan atau berita acara" class="h-12 rounded-xl border border-slate-300 px-4 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                            @error('referensi_bukti')<p class="text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 lg:flex-row lg:items-center lg:justify-between">
                        <p class="text-xs leading-5 text-slate-500">Tanggal mulai dan akhir termasuk dalam masa penempatan. Tanggal akhir boleh dikosongkan jika belum diketahui; rentang yang bertabrakan akan ditolak.</p>
                        <button type="submit" class="inline-flex min-h-11 w-full shrink-0 items-center justify-center whitespace-nowrap rounded-xl bg-emerald-600 px-4 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20 lg:w-auto lg:px-5">Catat riwayat</button>
                    </div>
                </form>
            </section>
        @endif
    </section>
@endsection
