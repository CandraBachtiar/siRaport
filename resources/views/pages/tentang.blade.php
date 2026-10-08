<x-layouts.public
    title="Tentang"
    description="Kenali RaporKu, ruang kerja digital untuk pengelolaan nilai, pemantauan perkembangan siswa, dan penyusunan rapor sekolah."
>
    <section class="border-b border-slate-200 bg-white">
        <div class="rk-shell py-16 text-center lg:py-20">
            <p class="rk-eyebrow">Tentang RaporKu</p>
            <h1 class="mx-auto mt-4 max-w-3xl text-4xl font-extrabold tracking-tight text-raporku-navy sm:text-5xl">Pengelolaan rapor yang lebih terstruktur dan mudah ditinjau.</h1>
            <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-slate-600">RaporKu menyatukan pekerjaan administrator, guru mata pelajaran, dan wali kelas tanpa mengaburkan batas tanggung jawab masing-masing.</p>
        </div>
    </section>

    <section class="py-16 lg:py-20">
        <div class="rk-shell grid gap-6 lg:grid-cols-2">
            <article class="rk-card p-7 sm:p-8">
                <span class="grid size-11 place-items-center rounded-xl bg-emerald-50 text-emerald-700" aria-hidden="true">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none"><path d="M5 4h11a3 3 0 0 1 3 3v13H7a2 2 0 0 1-2-2V4Z" stroke="currentColor" stroke-width="1.8"/><path d="M8 8h7M8 12h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </span>
                <h2 class="mt-5 text-2xl font-extrabold text-raporku-navy">Apa itu RaporKu?</h2>
                <p class="mt-3 text-sm leading-7 text-slate-600">RaporKu adalah sistem rapor digital berbasis web untuk mengelola data akademik, penilaian, perkembangan siswa, deskripsi rapor, dan kesiapan cetak dalam satu alur kerja.</p>
            </article>
            <article class="rk-card p-7 sm:p-8">
                <span class="grid size-11 place-items-center rounded-xl bg-blue-50 text-blue-700" aria-hidden="true">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none"><path d="M4 19V5m0 14h16M7 15l4-4 3 2 5-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <h2 class="mt-5 text-2xl font-extrabold text-raporku-navy">Masalah yang diselesaikan</h2>
                <p class="mt-3 text-sm leading-7 text-slate-600">Data yang tersebar, nilai yang belum lengkap, dan status rapor yang sulit dipantau sering memperlambat pekerjaan. RaporKu menampilkan status dan tindakan berikutnya secara jelas.</p>
            </article>
        </div>
    </section>

    <section class="border-y border-slate-200 bg-white py-16 lg:py-20">
        <div class="rk-shell">
            <div class="max-w-2xl">
                <p class="rk-eyebrow">Pengguna sistem</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-raporku-navy">Satu sistem, tiga pengalaman kerja.</h2>
            </div>
            <div class="mt-9 grid gap-5 md:grid-cols-3">
                @foreach ([
                    ['Administrator', 'Menyiapkan guru, siswa, kelas, mata pelajaran, tahun ajaran, dan tugas mengajar.'],
                    ['Guru Mapel', 'Membuat penilaian, memasukkan nilai, serta membaca perkembangan pada mata pelajaran yang diampu.'],
                    ['Wali Kelas', 'Meninjau siswa lintas mata pelajaran, memvalidasi deskripsi, dan memeriksa kesiapan rapor.'],
                ] as [$role, $description])
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                        <span class="text-xs font-extrabold text-emerald-700">0{{ $loop->iteration }}</span>
                        <h3 class="mt-3 text-lg font-extrabold text-raporku-navy">{{ $role }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-16 lg:py-20">
        <div class="rk-shell grid gap-10 lg:grid-cols-[1fr_1.1fr] lg:items-center">
            <div>
                <p class="rk-eyebrow">Fitur unggulan</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-raporku-navy">Analisis yang berasal dari data nilai nyata.</h2>
                <p class="mt-4 text-base leading-7 text-slate-600">Grafik dan indikator membantu guru melihat rata-rata, ketuntasan, nilai yang belum lengkap, serta kecenderungan perkembangan. Hasilnya dijelaskan secara transparan, tanpa klaim kecerdasan buatan.</p>
            </div>
            <div class="rk-card p-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        ['Status terlihat', 'Pengguna langsung mengetahui data yang lengkap dan yang perlu ditindaklanjuti.'],
                        ['Akses terjaga', 'Guru hanya bekerja pada kelas dan mata pelajaran yang menjadi tanggung jawabnya.'],
                        ['Bahasa sederhana', 'Label, pesan kesalahan, dan bantuan menggunakan istilah yang mudah dipahami.'],
                        ['Mudah digunakan', 'Navigasi konsisten, fokus keyboard jelas, dan tampilan menyesuaikan ukuran layar.'],
                    ] as [$title, $description])
                        <div class="rounded-xl bg-slate-50 p-4"><h3 class="text-sm font-bold text-raporku-navy">{{ $title }}</h3><p class="mt-2 text-xs leading-5 text-slate-500">{{ $description }}</p></div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
