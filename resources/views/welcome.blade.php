<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="EduRaport membantu guru mengelola nilai, memantau perkembangan belajar, dan menyusun rapor digital dengan lebih mudah.">
    <meta name="theme-color" content="#ffffff">
    <title>EduRaport — Rapor Digital untuk Masa Depan</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
        <script src="{{ asset('js/landing.js') }}" defer></script>
    @endif
</head>
<body>
    <header class="site-header">
        <div class="nav-wrap">
            <a class="brand" href="#beranda" aria-label="EduRaport, beranda">
                <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 40 40"><rect width="40" height="40" rx="13" fill="currentColor"/><path d="M10 12.5c4.1-1.3 7.4-.8 10 1.4v16c-2.6-2.2-5.9-2.7-10-1.4v-16Zm20 0c-4.1-1.3-7.4-.8-10 1.4v16c2.6-2.2 5.9-2.7 10-1.4v-16Z" fill="none" stroke="white" stroke-width="2" stroke-linejoin="round"/><path d="m15 20 2.2 2.2 3.5-4" fill="none" stroke="#83e3b1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span class="brand-copy"><strong>EduRaport</strong><small>Rapor Digital untuk Masa Depan</small></span>
            </a>
            <button class="menu-toggle" type="button" aria-label="Buka menu" aria-expanded="false" aria-controls="main-nav"><span></span><span></span><span></span></button>
            <nav class="main-nav" id="main-nav" aria-label="Navigasi utama">
                <a class="active" href="#beranda">Beranda</a><a href="#tentang">Tentang</a><a href="#panduan">Panduan</a><a href="#bantuan">Bantuan</a>
            </nav>
            <a class="nav-login" href="{{ Route::has('login') ? route('login') : '#mulai' }}">Masuk <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h11m-4-4 4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        </div>
    </header>

    <main>
        <section class="hero section-shell" id="beranda">
            <div class="hero-copy">
                <div class="eyebrow"><span class="eyebrow-dot"></span> SISTEM RAPOR DIGITAL</div>
                <h1>Kelola nilai.<br><span>Pantau perkembangan.</span><br>Susun rapor <span class="title-last">lebih mudah.</span></h1>
                <p class="hero-description">EduRaport membantu guru mengelola nilai siswa, menganalisis perkembangan hasil belajar, memberikan rekomendasi deskripsi yang dapat divalidasi oleh guru, dan menghasilkan rapor digital maupun cetak secara otomatis.</p>
                <div class="hero-actions"><a class="button button-primary" href="#mulai">Mulai Mengelola Rapor <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h11m-4-4 4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a><a class="button button-secondary" href="#panduan"><span class="play-icon">▶</span> Lihat Panduan</a></div>
                <div class="trust-points"><span><i class="check-icon">✓</i> Aman &amp; Terpercaya</span><span><i class="check-icon">✓</i> Mudah Digunakan</span><span><i class="check-icon">✓</i> Berbasis Web</span></div>
                <div class="hero-note"><span class="note-line"></span><span>Waktu guru lebih berharga saat digunakan untuk mendampingi.</span></div>
            </div>
            <div class="hero-visual" aria-label="Pratinjau dashboard EduRaport">
                <div class="decor decor-orbit"></div><div class="decor decor-dot dot-one"></div><div class="decor decor-dot dot-two"></div><div class="decor decor-dash"></div>
                <div class="dashboard-window">
                    <aside class="dashboard-sidebar">
                        <div class="dash-brand"><span class="dash-brand-icon"><svg viewBox="0 0 40 40"><path d="M10 12.5c4.1-1.3 7.4-.8 10 1.4v16c-2.6-2.2-5.9-2.7-10-1.4v-16Zm20 0c-4.1-1.3-7.4-.8-10 1.4v16c2.6-2.2 5.9-2.7 10-1.4v-16Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></span><span><b>EduRaport</b><small>PORTAL GURU</small></span></div>
                        <div class="side-label">MENU UTAMA</div>
                        <div class="side-menu">
                            <a class="selected" href="#beranda"><span class="side-icon">⌂</span>Dashboard</a><a href="#fitur"><span class="side-icon">▤</span>Data Siswa</a><a href="#fitur"><span class="side-icon">✎</span>Input Nilai</a><a href="#fitur"><span class="side-icon">◫</span>Analisis Nilai</a><a href="#fitur"><span class="side-icon">✧</span>Rekomendasi Deskripsi</a><a href="#fitur"><span class="side-icon">▣</span>Data Rapor</a><a href="#fitur"><span class="side-icon">⇩</span>Cetak PDF</a>
                        </div>
                        <div class="side-help"><span class="help-bubble">?</span><b>Butuh bantuan?</b><small>Tim kami siap membantu.</small><a href="#bantuan">Hubungi kami <span>→</span></a></div>
                        <div class="teacher-mini"><div class="avatar avatar-teacher">SR</div><span><b>Siti Rahmawati</b><small>Guru Kelas</small></span><span class="more-dots">···</span></div>
                    </aside>
                    <div class="dashboard-main">
                        <div class="dash-topbar"><div class="crumb">Halaman <span>/</span> <b>Dashboard</b></div><div class="dash-top-actions"><span class="dash-period">Tahun Ajaran 2025/2026⌄</span><span class="bell">♧<i></i></span><div class="avatar avatar-small">SR</div></div></div>
                        <div class="dash-heading"><div><div class="dash-date">SENIN, 15 SEPTEMBER 2025</div><h2>Selamat datang, Bu Siti <span>✦</span></h2><p>Guru Kelas V A <i></i> Tahun Ajaran 2025/2026</p></div><span class="date-chip">▦ &nbsp; Semester Ganjil</span></div>
                        <div class="stat-grid">
                            <article class="stat-card"><div class="stat-top"><span class="stat-icon green-icon">♙</span><span class="stat-trend">+2 siswa</span></div><div class="stat-value">32</div><div class="stat-label">Jumlah Siswa</div><div class="stat-foot"><span class="tiny-avatars"><i>AP</i><i>BS</i><i>+30</i></span> siswa terdaftar</div></article>
                            <article class="stat-card"><div class="stat-top"><span class="stat-icon blue-icon">⌁</span><span class="stat-trend">↗ 4,2%</span></div><div class="stat-value">82,6</div><div class="stat-label">Rata-rata Kelas</div><div class="stat-foot"><span class="foot-green">↑ Meningkat</span> dari semester lalu</div></article>
                            <article class="stat-card attention-stat"><div class="stat-top"><span class="stat-icon amber-icon">◎</span><span class="stat-alert">Perlu ditinjau</span></div><div class="stat-value">5</div><div class="stat-label">Siswa Perlu Perhatian</div><div class="stat-foot"><span class="attention-bar"><i></i></span> dari 32 siswa</div></article>
                        </div>
                        <div class="dash-lower">
                            <article class="chart-card"><div class="card-heading"><div><h3>Perkembangan Nilai Siswa</h3><p>Rata-rata kelas selama semester ini</p></div><button type="button" class="more-button" aria-label="Opsi grafik">···</button></div><div class="chart-key"><span><i></i> Rata-rata kelas</span><span class="chart-current">82,6 <small>+4,2%</small></span></div>
                                <div class="chart-area"><div class="chart-y"><span>100</span><span>80</span><span>60</span><span>40</span></div><div class="chart-plot"><div class="grid-line g1"></div><div class="grid-line g2"></div><div class="grid-line g3"></div><div class="grid-line g4"></div><svg viewBox="0 0 500 150" preserveAspectRatio="none" role="img" aria-label="Grafik nilai meningkat dari bulan Juli hingga November"><defs><linearGradient id="chartFill" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#25a86c" stop-opacity=".16"/><stop offset="100%" stop-color="#25a86c" stop-opacity="0"/></linearGradient></defs><path d="M0 119 C35 113 45 102 75 105 S120 88 150 94 S195 70 225 79 S270 63 300 70 S345 48 375 58 S420 33 450 42 S480 27 500 19 L500 150 L0 150Z" fill="url(#chartFill)"/><path d="M0 119 C35 113 45 102 75 105 S120 88 150 94 S195 70 225 79 S270 63 300 70 S345 48 375 58 S420 33 450 42 S480 27 500 19" fill="none" stroke="#20a66a" stroke-width="3" vector-effect="non-scaling-stroke" stroke-linecap="round"/><circle cx="500" cy="19" r="5" fill="#fff" stroke="#20a66a" stroke-width="3" vector-effect="non-scaling-stroke"/></svg><div class="chart-x"><span>Jul</span><span>Agu</span><span>Sep</span><span>Okt</span><span>Nov</span></div></div></div><div class="chart-foot"><span><i></i> Data nilai seluruh mata pelajaran</span><a href="#fitur">Lihat analisis <span>→</span></a></div>
                            </article>
                            <article class="attention-card"><div class="card-heading"><div><h3>Perlu Perhatian <span class="count-pill">5</span></h3><p>Pantau dan dampingi siswa</p></div><button type="button" class="more-button" aria-label="Opsi perhatian">···</button></div><div class="student-list">
                                <div class="student-row"><div class="avatar student-avatar tone-peach">AP</div><div class="student-info"><b>Alya Putri</b><small>Matematika</small></div><span class="status status-down">↘ Menurun</span></div>
                                <div class="student-row"><div class="avatar student-avatar tone-blue">BS</div><div class="student-info"><b>Bima Santoso</b><small>Kehadiran</small></div><span class="status status-attend">Rendah</span></div>
                                <div class="student-row"><div class="avatar student-avatar tone-lilac">CL</div><div class="student-info"><b>Citra Lestari</b><small>Bahasa Indonesia</small></div><span class="status status-guide">Dampingan</span></div>
                                <div class="student-row"><div class="avatar student-avatar tone-mint">DP</div><div class="student-info"><b>Dafa Pratama</b><small>IPA</small></div><span class="status status-down">↘ Menurun</span></div>
                            </div><a href="#bantuan" class="all-students">Lihat semua siswa <span>→</span></a></article>
                        </div>
                        <div class="dash-bottom-note"><span>✦</span> 3 rekomendasi deskripsi belajar siap divalidasi <a href="#fitur">Tinjau sekarang →</a></div>
                    </div>
                </div>
                <div class="floating-note"><span class="floating-icon">✓</span><span><b>Rapor siap ditinjau</b><small>Semua nilai tersimpan aman</small></span><span class="floating-spark">✦</span></div>
            </div>
        </section>

        <section class="trust-strip"><div class="section-shell trust-strip-inner"><span>DIRANCANG UNTUK MEMBANTU GURU</span><i></i><span>Lebih terstruktur</span><i></i><span>Lebih banyak waktu untuk siswa</span><i></i><span>Semua dalam satu tempat</span></div></section>

        <section class="features section-shell" id="tentang"><div class="section-heading"><span class="section-kicker">SATU SISTEM, SEMUA TERKELOLA</span><h2>Semua kebutuhan rapor<br><span>dalam satu sistem.</span></h2><p>Dari nilai pertama hingga rapor siap dibagikan, EduRaport menemani setiap langkah kerja guru.</p></div>
            <div class="feature-grid" id="fitur">
                <article class="feature-card"><span class="feature-icon icon-green"><svg viewBox="0 0 24 24"><path d="M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20m7-9a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7-6.5a4 4 0 0 1 0 7.7m5 8.8v-1.5a4 4 0 0 0-3-3.9"/></svg></span><span class="feature-index">01</span><h3>Data Siswa</h3><p>Kelola identitas dan data siswa secara rapi, aman, dan terstruktur.</p><a href="#panduan">Pelajari fitur <span>→</span></a></article>
                <article class="feature-card"><span class="feature-icon icon-blue"><svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2ZM8 7h8m-8 4h8"/></svg></span><span class="feature-index">02</span><h3>Input Nilai</h3><p>Masukkan nilai tugas, ulangan harian, UTS, dan UAS dalam satu tempat.</p><a href="#panduan">Pelajari fitur <span>→</span></a></article>
                <article class="feature-card"><span class="feature-icon icon-violet"><svg viewBox="0 0 24 24"><path d="M3 3v18h18M7 14l4-4 4 3 6-7"/><path d="M16 6h5v5"/></svg></span><span class="feature-index">03</span><h3>Analisis Nilai</h3><p>Lihat tren dan perkembangan hasil belajar setiap siswa melalui grafik.</p><a href="#panduan">Pelajari fitur <span>→</span></a></article>
                <article class="feature-card"><span class="feature-icon icon-amber"><svg viewBox="0 0 24 24"><path d="m12 3 1.9 5.8L20 11l-6.1 2.2L12 19l-1.9-5.8L4 11l6.1-2.2L12 3Zm7 12 .9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15Z"/></svg></span><span class="feature-index">04</span><h3>Rekomendasi Deskripsi</h3><p>Dapatkan saran deskripsi belajar yang relevan berdasarkan perkembangan nilai.</p><a href="#panduan">Pelajari fitur <span>→</span></a></article>
                <article class="feature-card"><span class="feature-icon icon-rose"><svg viewBox="0 0 24 24"><path d="M6 2h9l5 5v15H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z"/><path d="M14 2v6h6M8 13h8m-8 4h8"/></svg></span><span class="feature-index">05</span><h3>Data Rapor</h3><p>Atur dan periksa data rapor siswa sebelum dibagikan kepada orang tua.</p><a href="#panduan">Pelajari fitur <span>→</span></a></article>
                <article class="feature-card"><span class="feature-icon icon-teal"><svg viewBox="0 0 24 24"><path d="M12 3v12m-5-5 5 5 5-5M4 17v3h16v-3"/></svg></span><span class="feature-index">06</span><h3>Cetak PDF</h3><p>Hasilkan rapor digital dalam format PDF yang rapi dan siap dicetak.</p><a href="#panduan">Pelajari fitur <span>→</span></a></article>
            </div>
        </section>

        <section class="workflow-section" id="panduan"><div class="section-shell workflow-inner"><div class="workflow-intro"><span class="section-kicker">ALUR YANG LEBIH SEDERHANA</span><h2>Bagaimana EduRaport<br><span>bekerja?</span></h2><p>Alur kerja yang jelas membantu guru melangkah dari input nilai sampai rapor selesai dengan percaya diri.</p><a href="#mulai" class="text-link">Mulai langkah pertama <span>→</span></a><div class="workflow-decoration"> <span>✳</span><i></i><b></b></div></div><div class="workflow-steps">
                <div class="workflow-step"><span class="step-number">01</span><span class="step-icon">✎</span><div><b>Input Nilai</b><small>Masukkan hasil belajar siswa</small></div><span class="step-check">✓</span></div>
                <div class="workflow-step"><span class="step-number">02</span><span class="step-icon">∑</span><div><b>Perhitungan Nilai</b><small>Nilai akhir dihitung otomatis</small></div><span class="step-check">✓</span></div>
                <div class="workflow-step"><span class="step-number">03</span><span class="step-icon">⌁</span><div><b>Analisis Perkembangan</b><small>Kenali kemajuan belajar siswa</small></div><span class="step-check">✓</span></div>
                <div class="workflow-step"><span class="step-number">04</span><span class="step-icon">◎</span><div><b>Identifikasi Siswa</b><small>Temukan siswa yang perlu perhatian</small></div><span class="step-check">✓</span></div>
                <div class="workflow-step"><span class="step-number">05</span><span class="step-icon">✧</span><div><b>Rekomendasi Deskripsi</b><small>Saran deskripsi sesuai hasil belajar</small></div><span class="step-check">✓</span></div>
                <div class="workflow-step"><span class="step-number">06</span><span class="step-icon">☑</span><div><b>Validasi Guru</b><small>Periksa dan sesuaikan rekomendasi</small></div><span class="step-check">✓</span></div>
                <div class="workflow-step"><span class="step-number">07</span><span class="step-icon">▤</span><div><b>Rapor Siap</b><small>Bagikan digital atau cetak PDF</small></div><span class="step-check">✓</span></div>
            </div></div></section>

        <section class="cta-wrap section-shell" id="mulai"><div class="cta-card"><div class="cta-decor cta-circle"></div><div class="cta-decor cta-grid"></div><div class="cta-content"><span class="cta-kicker"><i></i> WAKTU UNTUK MENGAJAR</span><h2>Kelola rapor<br><span>dengan lebih mudah.</span></h2><p>Gunakan EduRaport untuk membantu proses pengelolaan nilai dan penyusunan rapor menjadi lebih terstruktur.</p><a href="{{ Route::has('login') ? route('login') : '#beranda' }}" class="button cta-button">Mulai Mengelola Rapor <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h11m-4-4 4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a></div><div class="cta-illustration" aria-hidden="true"><div class="illustration-back"></div><div class="illustration-sheet"><div class="sheet-mark">✓</div><i></i><i></i><i></i><span class="sheet-chart"><b></b><b></b><b></b><b></b><b></b></span><small>RAPOR SISWA</small></div><div class="illustration-pencil"></div><span class="illustration-star star-a">✳</span><span class="illustration-star star-b">✦</span></div></div></section>
    </main>

    <footer class="site-footer" id="bantuan"><div class="section-shell footer-main"><div class="footer-brand"><a class="brand" href="#beranda"><span class="brand-mark"><svg viewBox="0 0 40 40"><rect width="40" height="40" rx="13" fill="currentColor"/><path d="M10 12.5c4.1-1.3 7.4-.8 10 1.4v16c-2.6-2.2-5.9-2.7-10-1.4v-16Zm20 0c-4.1-1.3-7.4-.8-10 1.4v16c2.6-2.2 5.9-2.7 10-1.4v-16Z" fill="none" stroke="white" stroke-width="2" stroke-linejoin="round"/></svg></span><span class="brand-copy"><strong>EduRaport</strong><small>Rapor Digital untuk Masa Depan</small></span></a><p>Membantu guru memberi perhatian<br>lebih pada setiap perkembangan.</p></div><div class="footer-links"><div><b>Jelajahi</b><a href="#beranda">Beranda</a><a href="#tentang">Tentang</a></div><div><b>Butuh bantuan?</b><a href="#panduan">Panduan</a><a href="mailto:bantuan@eduraport.id">Bantuan</a></div><div class="footer-contact"><b>Tetap terhubung</b><span>Untuk pendidikan yang lebih berarti.</span><a href="mailto:bantuan@eduraport.id"><span class="mail-icon">✉</span> bantuan@eduraport.id</a></div></div></div><div class="section-shell footer-bottom"><span>© {{ date('Y') }} EduRaport. Dibuat untuk mendukung guru Indonesia.</span><span class="footer-made">Dengan perhatian untuk pendidikan <i>✦</i></span></div></footer>
</body>
</html>
