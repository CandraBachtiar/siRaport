<x-layouts.public
    title="Pusat Bantuan"
    description="Temukan jawaban untuk masalah login, input nilai, data siswa, dan kesiapan cetak rapor di RaporKu."
>
    <section class="border-b border-slate-200 bg-white">
        <div class="rk-shell py-16 text-center lg:py-20">
            <p class="rk-eyebrow">Pusat Bantuan</p>
            <h1 class="mx-auto mt-4 max-w-3xl text-4xl font-extrabold tracking-tight text-raporku-navy sm:text-5xl">Apa yang dapat kami bantu?</h1>
            <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-slate-600">Cari topik atau buka pertanyaan yang paling sesuai dengan kendala Anda.</p>
            <div class="relative mx-auto mt-8 max-w-2xl text-left">
                <svg class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <label class="sr-only" for="help-search">Cari bantuan</label>
                <input id="help-search" type="search" class="rk-field min-h-14 pl-12 shadow-sm" placeholder="Cari masalah login, input nilai, atau cetak rapor" data-help-search>
            </div>
        </div>
    </section>

    <section class="py-16 lg:py-20">
        <div class="rk-shell grid gap-8 lg:grid-cols-[0.7fr_1.3fr]">
            <aside>
                <div class="rk-card p-6 lg:sticky lg:top-28">
                    <h2 class="text-lg font-extrabold text-raporku-navy">Topik bantuan</h2>
                    <ul class="mt-4 grid gap-2 text-sm font-semibold text-slate-600">
                        @foreach (['Masalah Login', 'Masalah Input Nilai', 'Nilai Tidak Tampil', 'Data Siswa', 'Cetak Rapor'] as $topic)
                            <li class="flex items-center gap-3 rounded-lg bg-slate-50 px-3 py-2.5"><span class="size-2 rounded-full bg-emerald-500" aria-hidden="true"></span>{{ $topic }}</li>
                        @endforeach
                    </ul>
                    <div class="mt-6 rounded-xl bg-raporku-navy p-5 text-white">
                        <h3 class="text-sm font-bold">Masih memerlukan bantuan?</h3>
                        <p class="mt-2 text-xs leading-5 text-slate-300">Hubungi administrator sekolah. Sertakan halaman yang dibuka dan pesan kesalahan yang terlihat.</p>
                    </div>
                </div>
            </aside>

            <div>
                <h2 class="text-2xl font-extrabold text-raporku-navy">Pertanyaan yang sering diajukan</h2>
                <div class="mt-5 grid gap-3">
                    @foreach ([
                        ['Saya tidak dapat masuk ke RaporKu.', 'Pastikan email dan password sesuai akun sekolah. Periksa kembali huruf besar-kecil pada password. Jika tetap gagal, hubungi administrator untuk memeriksa status akun.'],
                        ['Mengapa kelas atau mata pelajaran tidak tampil?', 'Administrator mungkin belum menetapkan data pengampu untuk akun Anda pada tahun ajaran yang dipilih. Hubungi administrator sekolah untuk memeriksa penugasan.'],
                        ['Mengapa nilai siswa tidak tampil?', 'Pastikan tahun ajaran, semester, mata pelajaran, kelas, dan penilaian sudah dipilih dengan benar. Nilai hanya terlihat pada konteks pengampu yang sesuai.'],
                        ['Data siswa tidak ditemukan.', 'Periksa kata pencarian dan filter kelas. Jika siswa belum tercatat, administrator perlu menambahkan siswa dan menempatkannya pada kelas yang benar.'],
                        ['Mengapa tombol Cetak Rapor belum aktif?', 'Nilai atau deskripsi rapor siswa belum lengkap. Lengkapi data yang ditandai, tinjau deskripsi, lalu buka kembali preview rapor.'],
                        ['Apakah wali kelas dapat mengubah nilai guru lain?', 'Tidak. Wali kelas dapat meninjau perkembangan siswa, tetapi perubahan nilai tetap menjadi tanggung jawab guru mata pelajaran yang ditugaskan.'],
                    ] as [$question, $answer])
                        <details class="rk-card group overflow-hidden" data-help-item>
                            <summary class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-sm font-bold text-raporku-navy marker:content-none sm:px-6">
                                {{ $question }}
                                <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-slate-100 text-lg text-slate-500 transition group-open:rotate-45" aria-hidden="true">+</span>
                            </summary>
                            <div class="border-t border-slate-200 bg-slate-50 px-5 py-4 text-sm leading-7 text-slate-600 sm:px-6">{{ $answer }}</div>
                        </details>
                    @endforeach
                </div>
                <div class="mt-5 hidden rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center" data-help-empty>
                    <h3 class="font-bold text-raporku-navy">Topik tidak ditemukan</h3>
                    <p class="mt-2 text-sm text-slate-500">Coba kata kunci yang lebih singkat atau hubungi administrator sekolah.</p>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
