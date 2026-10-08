<x-layouts.public
    title="Rapor digital untuk sekolah"
    description="RaporKu membantu administrator, guru mata pelajaran, dan wali kelas mengelola nilai serta menyiapkan rapor melalui alur yang jelas."
>
    <section class="overflow-hidden border-b border-slate-200 bg-gradient-to-br from-slate-50 via-white to-emerald-50/40">
        <div class="rk-shell grid items-center gap-14 py-16 lg:grid-cols-[0.9fr_1.1fr] lg:py-24">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-extrabold uppercase tracking-[0.14em] text-emerald-700">
                    <span class="size-2 rounded-full bg-emerald-500"></span> Sistem Rapor Digital
                </span>
                <h1 class="mt-6 text-4xl font-extrabold leading-[1.12] tracking-[-0.045em] text-raporku-navy sm:text-5xl lg:text-[3.55rem]">
                    Kelola nilai.<br>
                    <span class="text-emerald-600">Pantau perkembangan.</span><br>
                    Susun rapor lebih mudah.
                </h1>
                <p class="mt-6 max-w-xl text-base leading-7 text-slate-600 sm:text-lg">Satu ruang kerja untuk administrator, guru mata pelajaran, dan wali kelas—dari persiapan data sekolah sampai rapor siap ditinjau.</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a class="rk-button rk-button-primary min-h-12 px-5" href="{{ route('login') }}">Mulai Mengelola Rapor
                        <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h11m-4-4 4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                    <a class="rk-button rk-button-secondary min-h-12 px-5" href="{{ route('panduan') }}">
                        <svg class="size-4 text-emerald-600" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4h11a3 3 0 0 1 3 3v13H7a2 2 0 0 1-2-2V4Z" stroke="currentColor" stroke-width="1.8"/><path d="M8 8h7M8 12h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        Lihat Panduan
                    </a>
                </div>
                <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-3 text-sm font-semibold text-slate-600" aria-label="Keunggulan utama">
                    @foreach (['Alur kerja jelas', 'Data terstruktur', 'Akses sesuai tanggung jawab'] as $benefit)
                        <li class="flex items-center gap-2"><span class="grid size-5 place-items-center rounded-full bg-emerald-100 text-xs text-emerald-700" aria-hidden="true">✓</span>{{ $benefit }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="relative" aria-label="Pratinjau antarmuka dashboard RaporKu">
                <div class="absolute -inset-6 -z-10 rounded-[2rem] bg-gradient-to-br from-emerald-100/60 to-slate-200/50 blur-2xl"></div>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10">
                    <div class="flex min-h-[31rem]">
                        <aside class="hidden w-40 shrink-0 bg-raporku-navy-deep p-4 text-white sm:block">
                            <div class="flex items-center gap-2 border-b border-white/10 pb-4">
                                <span class="grid size-8 place-items-center rounded-lg bg-emerald-500"><svg class="size-4" viewBox="0 0 40 40" fill="none"><path d="M9 11.5c4.5-1.4 8.2-.8 11 1.6v16.4c-2.8-2.4-6.5-3-11-1.6V11.5Zm22 0c-4.5-1.4-8.2-.8-11 1.6v16.4c2.8-2.4 6.5-3 11-1.6V11.5Z" stroke="currentColor" stroke-width="2.5"/></svg></span>
                                <span><strong class="block text-xs">RaporKu</strong><small class="text-[0.5rem] text-slate-400">GURU MAPEL</small></span>
                            </div>
                            <p class="mt-5 text-[0.5rem] font-bold tracking-widest text-slate-500">UTAMA</p>
                            <div class="mt-2 grid gap-1.5 text-[0.62rem] font-semibold">
                                <span class="rounded-lg bg-emerald-500/20 px-2 py-2 text-emerald-200">Dashboard</span>
                                <span class="px-2 py-2 text-slate-400">Kelas &amp; Mapel</span>
                                <span class="px-2 py-2 text-slate-400">Penilaian</span>
                                <span class="px-2 py-2 text-slate-400">Input Nilai</span>
                            </div>
                            <p class="mt-5 text-[0.5rem] font-bold tracking-widest text-slate-500">ANALISIS</p>
                            <div class="mt-2 grid gap-1.5 text-[0.62rem] font-semibold text-slate-400"><span class="px-2 py-2">Analisis Nilai</span><span class="px-2 py-2">Rekap Nilai</span></div>
                        </aside>
                        <div class="min-w-0 flex-1 bg-slate-50 p-4 sm:p-5">
                            <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                                <div><p class="text-[0.6rem] font-bold uppercase tracking-widest text-emerald-600">Dashboard Guru</p><p class="mt-1 text-xs font-extrabold text-raporku-navy">Ringkasan pembelajaran</p></div>
                                <span class="rounded-full border border-slate-200 bg-white px-2 py-1 text-[0.55rem] font-semibold text-slate-500">Semester Ganjil</span>
                            </div>
                            <div class="mt-5">
                                <p class="text-[0.55rem] font-bold uppercase tracking-widest text-slate-400">Hari ini</p>
                                <h2 class="mt-1 text-base font-extrabold text-raporku-navy sm:text-lg">Selamat datang, Bapak/Ibu Guru</h2>
                                <p class="mt-1 text-[0.62rem] text-slate-500">Lihat tugas mengajar dan pekerjaan yang perlu diselesaikan.</p>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach ([['Kelas diajar', '4'], ['Penilaian', '12'], ['Belum lengkap', '7']] as [$label, $value])
                                    <div class="rounded-lg border border-slate-200 bg-white p-3"><strong class="text-lg text-raporku-navy">{{ $value }}</strong><p class="mt-1 text-[0.55rem] text-slate-500">{{ $label }}</p></div>
                                @endforeach
                            </div>
                            <div class="mt-3 grid gap-3 sm:grid-cols-[1.35fr_1fr]">
                                <article class="rounded-xl border border-slate-200 bg-white p-4">
                                    <div class="flex items-start justify-between"><div><h3 class="text-[0.72rem] font-bold text-raporku-navy">Perkembangan nilai</h3><p class="mt-1 text-[0.5rem] text-slate-400">Rata-rata per penilaian</p></div><span class="text-[0.5rem] font-bold text-emerald-600">Meningkat</span></div>
                                    <svg class="mt-4 h-28 w-full" viewBox="0 0 300 110" role="img" aria-label="Contoh grafik nilai yang meningkat">
                                        <path d="M0 88H300M0 58H300M0 28H300" stroke="#e2e8f0" stroke-width="1"/>
                                        <path d="M5 84 C45 78 58 70 88 72 S130 55 160 61 S208 35 238 43 S275 22 295 25" fill="none" stroke="#059669" stroke-width="4" stroke-linecap="round"/>
                                    </svg>
                                    <p class="mt-2 text-[0.5rem] leading-4 text-slate-500">Grafik membantu guru membaca perubahan nilai dari satu penilaian ke penilaian berikutnya.</p>
                                </article>
                                <article class="rounded-xl border border-slate-200 bg-white p-4">
                                    <h3 class="text-[0.72rem] font-bold text-raporku-navy">Perlu diselesaikan</h3>
                                    <div class="mt-3 grid gap-2">
                                        <div class="rounded-lg bg-amber-50 p-2.5"><p class="text-[0.58rem] font-bold text-amber-800">Nilai belum lengkap</p><p class="mt-0.5 text-[0.5rem] text-amber-700">Tinjau input nilai siswa.</p></div>
                                        <div class="rounded-lg bg-slate-100 p-2.5"><p class="text-[0.58rem] font-bold text-slate-700">Rekap nilai</p><p class="mt-0.5 text-[0.5rem] text-slate-500">Periksa sebelum finalisasi.</p></div>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="absolute -bottom-4 right-4 flex items-center gap-2 rounded-xl border border-emerald-200 bg-white px-3 py-2 shadow-lg sm:right-8">
                    <span class="grid size-7 place-items-center rounded-full bg-emerald-100 text-xs font-extrabold text-emerald-700">✓</span>
                    <span><strong class="block text-[0.65rem] text-raporku-navy">Status mudah dipahami</strong><small class="block text-[0.52rem] text-slate-500">Tindakan berikutnya selalu terlihat</small></span>
                </div>
            </div>
        </div>
    </section>

    <section id="fitur" class="scroll-mt-24 py-20 lg:py-24">
        <div class="rk-shell">
            <div class="max-w-2xl">
                <p class="rk-eyebrow">Fitur berdasarkan tanggung jawab</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-raporku-navy sm:text-4xl">Ruang kerja yang relevan untuk setiap pengguna.</h2>
                <p class="mt-4 text-base leading-7 text-slate-600">Setiap peran melihat informasi dan tindakan yang sesuai, sehingga pekerjaan lebih mudah dipahami dan risiko salah akses berkurang.</p>
            </div>
            <div class="mt-10 grid gap-5 lg:grid-cols-3">
                @foreach ([
                    ['Administrator', 'Menyiapkan data sekolah dan memastikan proses akademik siap berjalan.', ['Kelola guru, siswa, dan kelas', 'Atur mata pelajaran dan tahun ajaran', 'Tetapkan data pengampu']],
                    ['Guru Mapel', 'Mengelola penilaian untuk kelas dan mata pelajaran yang menjadi tanggung jawabnya.', ['Buat penilaian', 'Input dan rekap nilai', 'Pantau perkembangan siswa']],
                    ['Wali Kelas', 'Meninjau perkembangan siswa secara menyeluruh sebelum rapor disiapkan.', ['Pantau lintas mata pelajaran', 'Tinjau saran deskripsi', 'Preview dan cetak rapor']],
                ] as [$role, $summary, $items])
                    <article class="rk-card flex h-full flex-col p-6 transition duration-200 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-lg hover:shadow-slate-900/[0.05]">
                        <span class="grid size-11 place-items-center rounded-xl bg-emerald-50 text-emerald-700">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20m7-9a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7-6.5a4 4 0 0 1 0 7.7m5 8.8v-1.5a4 4 0 0 0-3-3.9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        </span>
                        <h3 class="mt-5 text-xl font-extrabold text-raporku-navy">Untuk {{ $role }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-500">{{ $summary }}</p>
                        <ul class="mt-5 grid gap-3 text-sm font-semibold text-slate-700">
                            @foreach ($items as $item)
                                <li class="flex items-start gap-2"><span class="mt-0.5 text-emerald-600" aria-hidden="true">✓</span>{{ $item }}</li>
                            @endforeach
                        </ul>
                        <a class="mt-6 inline-flex items-center gap-2 text-sm font-bold text-emerald-700 hover:text-emerald-800" href="{{ route('panduan') }}">Pelajari fitur <span aria-hidden="true">→</span></a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="border-y border-slate-200 bg-white py-20 lg:py-24">
        <div class="rk-shell grid gap-12 lg:grid-cols-[0.75fr_1.25fr]">
            <div>
                <p class="rk-eyebrow">Cara kerja</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-raporku-navy">Bagaimana RaporKu bekerja?</h2>
                <p class="mt-4 text-base leading-7 text-slate-600">Tujuh langkah yang dapat dikenali sejak persiapan data hingga rapor siap dicetak.</p>
                <a class="rk-button rk-button-secondary mt-6" href="{{ route('panduan') }}">Buka panduan lengkap</a>
            </div>
            <ol class="grid gap-3 sm:grid-cols-2">
                @foreach ([
                    'Admin menyiapkan data sekolah',
                    'Guru membuat penilaian',
                    'Guru memasukkan nilai',
                    'Sistem menampilkan perkembangan',
                    'Wali kelas meninjau siswa',
                    'Deskripsi rapor divalidasi',
                    'Rapor siap dicetak',
                ] as $step)
                    <li class="rk-card flex items-center gap-4 p-4">
                        <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-raporku-navy text-xs font-extrabold text-white">{{ str_pad((string) ($loop->index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="text-sm font-bold text-slate-700">{{ $step }}</span>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="py-20">
        <div class="rk-shell overflow-hidden rounded-3xl bg-raporku-navy px-6 py-10 text-white shadow-xl shadow-slate-900/10 sm:px-10 lg:flex lg:items-center lg:justify-between lg:px-14">
            <div class="max-w-2xl">
                <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-emerald-300">Mulai dari alur yang jelas</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight">Kelola rapor dalam satu ruang kerja sekolah.</h2>
                <p class="mt-4 text-sm leading-7 text-slate-300">Masuk menggunakan akun sekolah untuk mengakses fitur sesuai tanggung jawab Anda.</p>
            </div>
            <a class="rk-button mt-7 bg-white text-raporku-navy hover:bg-slate-100 lg:mt-0" href="{{ route('login') }}">Masuk ke RaporKu <span aria-hidden="true">→</span></a>
        </div>
    </section>
</x-layouts.public>
