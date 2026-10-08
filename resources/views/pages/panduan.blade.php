<x-layouts.public
    title="Panduan Pengguna"
    description="Panduan langkah demi langkah penggunaan RaporKu untuk administrator, guru mata pelajaran, dan wali kelas."
>
    <section class="border-b border-slate-200 bg-white">
        <div class="rk-shell py-16 text-center lg:py-20">
            <p class="rk-eyebrow">Panduan Pengguna</p>
            <h1 class="mx-auto mt-4 max-w-3xl text-4xl font-extrabold tracking-tight text-raporku-navy sm:text-5xl">Mulai dari langkah yang sesuai dengan tanggung jawab Anda.</h1>
            <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-slate-600">Pilih peran untuk melihat urutan kerja yang disarankan. Setiap langkah dapat diselesaikan secara bertahap.</p>
        </div>
    </section>

    <section class="py-16 lg:py-20">
        <div class="rk-shell">
            <div class="flex flex-col gap-2 rounded-2xl border border-slate-200 bg-slate-100 p-2 sm:flex-row" role="tablist" aria-label="Pilih panduan berdasarkan peran">
                @foreach ([
                    ['admin', 'Administrator'],
                    ['guru', 'Guru Mapel'],
                    ['wali', 'Wali Kelas'],
                ] as [$key, $label])
                    <button id="guide-tab-{{ $key }}" type="button" role="tab" data-guide-tab="{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" aria-controls="guide-{{ $key }}" tabindex="{{ $loop->first ? '0' : '-1' }}" @class([
                        'min-h-11 flex-1 rounded-xl px-4 py-2.5 text-sm font-bold transition',
                        'bg-raporku-navy text-white shadow-sm' => $loop->first,
                        'bg-white text-slate-600 hover:text-raporku-navy' => ! $loop->first,
                    ])>{{ $label }}</button>
                @endforeach
            </div>

            @php
                $guides = [
                    'admin' => [
                        'title' => 'Panduan Administrator',
                        'intro' => 'Siapkan data dasar terlebih dahulu agar guru dapat bekerja pada konteks akademik yang benar.',
                        'steps' => ['Masuk menggunakan akun administrator', 'Buat dan aktifkan tahun ajaran', 'Tambahkan data guru', 'Tambahkan kelas dan tentukan wali kelas', 'Tambahkan siswa ke kelas', 'Tambahkan mata pelajaran', 'Tetapkan data pengampu'],
                    ],
                    'guru' => [
                        'title' => 'Panduan Guru Mapel',
                        'intro' => 'Kelola hanya kelas dan mata pelajaran yang telah ditetapkan oleh administrator.',
                        'steps' => ['Buka kelas dan mata pelajaran', 'Buat penilaian', 'Masukkan nilai siswa', 'Periksa nilai yang belum lengkap', 'Lihat analisis perkembangan', 'Tinjau siswa yang perlu perhatian', 'Buka rekap nilai'],
                    ],
                    'wali' => [
                        'title' => 'Panduan Wali Kelas',
                        'intro' => 'Tinjau perkembangan seluruh siswa pada kelas wali sebelum rapor difinalkan.',
                        'steps' => ['Buka workspace wali kelas', 'Periksa daftar siswa kelas', 'Lihat perkembangan lintas mata pelajaran', 'Tinjau siswa yang perlu perhatian', 'Tinjau dan revisi saran deskripsi', 'Periksa data rapor', 'Preview lalu cetak rapor yang sudah lengkap'],
                    ],
                ];
            @endphp

            @foreach ($guides as $key => $guide)
                <section id="guide-{{ $key }}" role="tabpanel" aria-labelledby="guide-tab-{{ $key }}" tabindex="0" data-guide-panel="{{ $key }}" @class(['mt-8', 'hidden' => ! $loop->first])>
                    <div class="rk-card overflow-hidden">
                        <div class="border-b border-slate-200 bg-white p-6 sm:p-8">
                            <h2 class="text-2xl font-extrabold text-raporku-navy">{{ $guide['title'] }}</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{{ $guide['intro'] }}</p>
                        </div>
                        <ol class="grid gap-px bg-slate-200 sm:grid-cols-2">
                            @foreach ($guide['steps'] as $step)
                                <li class="flex min-h-28 items-start gap-4 bg-white p-6">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-emerald-50 text-xs font-extrabold text-emerald-700">{{ $loop->iteration }}</span>
                                    <div><p class="text-sm font-bold text-raporku-navy">{{ $step }}</p><p class="mt-1 text-xs leading-5 text-slate-500">Selesaikan langkah ini sebelum melanjutkan jika data berikutnya bergantung padanya.</p></div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </section>
            @endforeach

            <div class="mt-8 rounded-2xl border border-blue-200 bg-blue-50 p-5 text-sm leading-6 text-blue-900">
                <strong>Perlu bantuan?</strong> Jika menu atau data yang Anda butuhkan belum tersedia, periksa kembali akun dan penugasan Anda, lalu hubungi administrator sekolah.
                <a class="ml-1 font-bold underline decoration-blue-300 underline-offset-4" href="{{ route('bantuan') }}">Buka Pusat Bantuan</a>
            </div>
        </div>
    </section>
</x-layouts.public>
