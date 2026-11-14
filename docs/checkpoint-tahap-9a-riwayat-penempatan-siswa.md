# Checkpoint Tahap 9A — Riwayat Penempatan Siswa

Tanggal: 14 November 2026  
Branch: `tahap-7-panduan-pengujian-menyeluruh`

## 1. Hasil Tahap 9A

### Audit struktur

- `siswa.kelas_id` adalah penempatan siswa yang dipakai saat ini. Admin mengubahnya melalui form siswa; import siswa juga menulis `kelas_id`.
- Daftar nilai, rekap, analisis Guru Mapel, dashboard guru, import nilai, dan banyak operasi Wali Kelas membentuk daftar siswa dengan `siswa.kelas_id`.
- Wali Kelas memilih kelas dan tahun ajaran, tetapi `WaliKelasService` masih mengambil siswa berdasarkan kelas terkini. `RaporSiswaController` dan request otorisasi rapor juga memakai kelas terkini. Akibatnya, setelah siswa dipindahkan, daftar historis untuk kelas lama berpotensi tidak lagi memuat siswa tersebut.
- Nilai terhubung ke siswa dan penilaian; penilaian terhubung ke pengampu yang menyimpan kelas dan tahun ajaran. Data nilai lama tetap memiliki jalur ke kelas/periode asal, tetapi roster historis belum menggunakannya.
- `data_rapor_siswa` dan ekstrakurikuler menyimpan siswa serta tahun ajaran, bukan kelas. `DataRaporSiswa` juga memiliki `keputusan_kenaikan`; keputusan ini bukan bukti kelas penempatan berikutnya.
- `TahunAjaran` berisi tahun, semester, dan status aktif. Tahap ini memakai record tersebut secara langsung dan tidak menambah entitas semester atau periode baru.

### Desain yang dipilih

Tabel `riwayat_penempatan_siswa` berisi `siswa_id`, `kelas_id`, `tahun_ajaran_id`, `mulai_pada`, dan `berakhir_pada` nullable, beserta timestamps. Rentang tanggal mendukung beberapa penempatan dalam semester yang sama, misalnya perpindahan rombel. Tidak ada unique constraint satu siswa untuk satu periode.

Foreign key memakai `restrictOnDelete()` agar siswa, kelas, atau periode yang dirujuk riwayat tidak terhapus beserta bukti sejarahnya. Indeks disediakan untuk riwayat per siswa/periode, pencarian rentang siswa, serta roster kelas/periode.

Perpindahan berurutan dapat dicatat dengan menutup rentang lama dan menambahkan rentang baru. Tahap ini belum menetapkan inklusivitas tanggal akhir dan belum mengimplementasikan pemeriksaan rentang yang bertabrakan. Belum ada jalur tulis riwayat di aplikasi; sebelum jalur itu dibuat, sekolah perlu menetapkan semantik tanggal. Rancangan yang disarankan adalah validasi overlap dalam transaksi dengan mengunci baris siswa, tanpa unique constraint siswa-periode. Perpindahan dalam satu periode tetap diperbolehkan selama rentangnya tidak bertumpang tindih.

### Implementasi dan yang ditunda

- Migration additive, model `RiwayatPenempatanSiswa`, factory, dan relasi dari `Siswa`, `Kelas`, serta `TahunAjaran` ditambahkan.
- Penghapusan Admin untuk siswa, kelas, dan periode kini ditolak dengan pesan yang sesuai jika riwayat sudah merujuk record tersebut.
- Tidak ada route atau UI penempatan, perubahan kelas aktif, promosi otomatis, keputusan naik/tidak naik, backfill, atau penggantian roster pada fitur Guru/Wali Kelas.
- Data lama tidak ditebak dari `siswa.kelas_id`. Migration tidak membuat catatan riwayat otomatis.

## 2. Perubahan kode Tahap 9A

### File baru

- `database/migrations/2026_11_14_124453_create_riwayat_penempatan_siswa_table.php` — tabel, foreign key restrict, indeks, dan rollback yang menolak menghapus tabel bila berisi catatan.
- `app/Models/RiwayatPenempatanSiswa.php` — model, cast tanggal, dan relasi ke siswa, kelas, serta tahun ajaran.
- `database/factories/RiwayatPenempatanSiswaFactory.php` — data untuk pengujian.
- `tests/Feature/RiwayatPenempatanSiswaTest.php` — tes struktur, relasi, periode berbeda, tinggal kelas, perpindahan rombel, nilai historis, tanpa backfill, serta rollback.

### File yang diubah Tahap 9A

- `app/Models/Siswa.php`, `app/Models/Kelas.php`, `app/Models/TahunAjaran.php` — relasi riwayat terbatas.
- `app/Http/Controllers/Admin/SiswaController.php`, `KelasController.php`, `TahunAjaranController.php` — mencegah penghapusan record yang masih dirujuk riwayat.
- `tests/Concerns/CreatesAdminTestSchema.php` — skema SQLite bersama untuk pengujian fitur.
- `tests/Feature/Admin/SiswaControllerTest.php`, `KelasControllerTest.php`, `TahunAjaranControllerTest.php` — uji perlindungan penghapusan.

Perubahan lokal Tahap 8 sebelumnya tetap ada dan tidak dibuang. Daftar Git lengkap di bawah mencakup perubahan dari tahap sebelumnya juga.

### Status migration

Migration penempatan **belum diterapkan** ke database aplikasi. Tes memanggil `up()` dan `down()` hanya pada koneksi SQLite in-memory PHPUnit.

Migration KKM Tahap 8I (`2026_11_14_121030_make_kkm_legacy_nullable_on_mata_pelajaran_table.php`) juga masih belum diterapkan; verifikasi MySQL 8 Tahap 8J tertunda. Tidak ada perintah migrate, migrate:fresh, atau db:wipe yang dijalankan ke `rapor_digital`.

## 3. Pengujian

- `php artisan test --compact`: **356 tes lulus, 2.372 assertion**.
- Tes khusus riwayat penempatan: **6 tes lulus, 32 assertion**.
- Laravel Pint: lulus pada file PHP yang berubah.
- `npm.cmd run build`: lulus. Vite menampilkan peringatan opsional bahwa `fontaine` tidak terpasang.
- `git diff --check`: lulus; ada peringatan akhir baris `.gitignore` yang sudah berubah sebelumnya.
- PHPUnit dikonfigurasi memakai SQLite `:memory:`. MySQL 8 belum diuji, sehingga kompatibilitas MySQL migration belum terbukti.
- Pemeriksaan visual browser tidak dilakukan. Tidak ada perubahan tampilan pada Tahap 9A; pemeriksaan visual dari tahap sebelumnya masih perlu dilakukan manual bila dibutuhkan.

Tidak ada migration yang dijalankan ke database pengembangan atau akademik utama.

## 4. Rekap tahap 8E–9A

| Tahap | Status berdasarkan konteks dan bukti yang tersedia |
|---|---|
| 8E | Indikator Dashboard Guru dialihkan dari KKM ke Siswa Perlu Perhatian dan memakai mekanisme perhatian yang sudah ada. Perhitungan memakai siswa unik; suite berikutnya tetap menguji konsistensi dashboard/perhatian. Tidak ada hitungan tes terpisah yang tercatat di checkpoint ini. |
| 8G | Label kartu statistik Siswa Perlu Perhatian dirapikan. Selesai; 343 tes dan 2.296 assertion dilaporkan pengguna. |
| 8H | Petunjuk kesiapan simulasi Rekap Nilai, state disabled, ringkasan hambatan, dan keadaan kosong/cetak diperjelas. 344 tes dan 2.310 assertion lulus. Visual browser/print belum terverifikasi. |
| 8I | Form/daftar Admin tidak lagi meminta/menampilkan KKM; KKM legacy dibuat nullable tanpa default 75; request manual KKM diabaikan. 347 tes dan 2.325 assertion lulus pada SQLite. |
| 8J | Tertunda. Koneksi yang diarahkan ke `siraport_migration_test` gagal dengan error MySQL 1049 karena database uji belum tersedia. Migration MySQL 8 tidak dijalankan atau diverifikasi. |
| 9A | Fondasi riwayat additive selesai dan diuji di SQLite; migration belum diterapkan ke MySQL. Tidak ada backfill atau penggantian sumber roster lama. |

## 5. Masalah yang belum selesai

1. **MySQL 8:** database uji `siraport_migration_test` belum tersedia. Kedua migration KKM dan penempatan belum diverifikasi dengan MySQL 8.
2. **Roster historis:** dashboard, input nilai, rekap, analisis, serta Wali Kelas masih memakai `siswa.kelas_id`. Tabel baru belum dipakai membaca roster historis.
3. **Backfill:** data lama tidak cukup untuk menebak keanggotaan kelas lengkap pada setiap periode. Jangan isi otomatis dari kelas siswa sekarang. Sumber dan proses verifikasi sekolah harus dirancang terpisah.
4. **Pencegahan overlap:** schema mengizinkan beberapa penempatan per siswa/periode dan belum menolak rentang tanggal bertabrakan. Sebelum ada form/import penempatan, tetapkan semantik tanggal dan buat validator transaksional yang mengunci siswa.
5. **Keputusan sekolah:** aturan naik, tinggal kelas, serta kapan penempatan baru berlaku perlu konfirmasi sekolah. Keputusan akademik tidak boleh disimpulkan dari `siswa.kelas_id` atau dari adanya placement.
6. **Nilai akhir resmi:** Tahap 8H menampilkan simulasi saja. Metode penetapan nilai akhir resmi tetap memerlukan keputusan sekolah.
7. **Browser/manual:** Print Preview Rekap Nilai, tampilan mobile form Admin, dan skenario historis sesudah roster memakai riwayat belum diuji secara visual.

## 6. Rencana prioritas berikutnya

### Kritis — verifikasi migration di MySQL 8 terisolasi

- **Tujuan:** menguji kedua migration tanpa menyentuh `rapor_digital`.
- **Modul:** migration KKM legacy dan `create_riwayat_penempatan_siswa_table`.
- **Risiko:** target koneksi salah, destructive rollback, atau variasi DDL MySQL.
- **Syarat:** DBA menyiapkan database MySQL 8 khusus bernama `siraport_migration_test`, memastikan kosong, lalu verifikasi `SELECT DATABASE()` dan versi server lewat koneksi yang akan dipakai. Jangan mengubah `.env` utama.
- **Tes:** semua migration pada database uji kosong; periksa metadata kolom, indeks, FK, data lama/fiktif; uji rollback hanya pada database uji. Backup dan verifikasi nama database sebelum penerapan ke lingkungan pengembangan.

### Penting — rancangan alur pencatatan placement dan konflik

- **Tujuan:** menentukan siapa yang berwenang mencatat, mengubah, dan mengakhiri penempatan, termasuk perpindahan di tengah semester.
- **Modul potensial:** Form Request/policy Admin, service riwayat, model penempatan, serta tes konkurensi.
- **Risiko:** rentang tumpang tindih, tanggal salah, atau kelas aktif ikut berubah tanpa sengaja.
- **Syarat:** konfirmasi sekolah tentang tanggal efektif, tanggal akhir, koreksi data, dan audit perubahan.
- **Tes:** rentang valid/tidak valid, benturan siswa lintas kelas/periode, perpindahan berurutan, otorisasi, dan tidak mengubah `siswa.kelas_id` secara implisit.

### Penting — perencanaan backfill dan roster historis

- **Tujuan:** menentukan sumber bukti dan menampilkan siswa yang benar pada periode lama.
- **Modul potensial:** import Admin, `GuruScoreService`, `GuruAnalysisService`, `WaliKelasService`, `RaporSiswaController`, request otorisasi rapor.
- **Risiko:** data nilai/rapor hilang dari roster atau backfill menebak kelas yang salah.
- **Syarat:** keputusan sekolah dan pemetaan sumber historis yang dapat diaudit; data tak diketahui harus tetap ditandai belum diketahui.
- **Tes:** siswa pindah kelas, tinggal kelas, pindah rombel dalam semester, hak akses wali kelas, serta nilai/rapor historis tetap terhubung.

### Penting — penetapan nilai akhir resmi

- **Tujuan:** menyepakati metode resmi yang saat ini hanya berupa simulasi.
- **Modul potensial:** service simulasi Rekap Nilai dan keputusan tata kelola sekolah.
- **Risiko:** perubahan rumus tanpa kebijakan sekolah.
- **Syarat:** keputusan tertulis sekolah; jangan menyalin KKM menjadi KKTP.
- **Tes:** kasus TP, kelengkapan nilai, dan rekonsiliasi hasil setelah keputusan disetujui.

### Penyempurnaan — pemeriksaan browser/manual

- **Tujuan:** memeriksa form tambah/edit Admin dan tampilan roster historis setelah implementasi pemakaian riwayat.
- **Modul:** halaman Admin Mata Pelajaran serta halaman roster Guru/Wali Kelas terkait.
- **Risiko:** layout mobile atau label konteks periode tidak jelas.
- **Syarat:** browser tersedia dan lingkungan data uji yang tidak memakai data akademik utama.
- **Tes:** desktop/mobile, form Admin, perpindahan periode, dan Print Preview yang relevan.

## 7. Status Git

- Branch aktif: `tahap-7-panduan-pengujian-menyeluruh`.
- Jumlah entri `git status --short` setelah checkpoint ini: **69** (termasuk perubahan belum di-commit dari tahap terdahulu dan folder dokumen checkpoint ini).
- Tidak ada commit, merge, atau push.
- Migration belum diterapkan: `2026_11_14_121030_make_kkm_legacy_nullable_on_mata_pelajaran_table.php` dan `2026_11_14_124453_create_riwayat_penempatan_siswa_table.php`.
- Output status mencakup seluruh perubahan lokal; tidak ada yang dihapus.

```text
 M .gitignore
 M app/Http/Controllers/Admin/KelasController.php
 M app/Http/Controllers/Admin/MataPelajaranController.php
 M app/Http/Controllers/Admin/SiswaController.php
 M app/Http/Controllers/Admin/TahunAjaranController.php
 M app/Http/Controllers/Guru/AnalisisNilaiController.php
 M app/Http/Controllers/Guru/InputNilaiController.php
 M app/Http/Controllers/Guru/KelasMataPelajaranController.php
 M app/Http/Controllers/Guru/RekapNilaiController.php
 M app/Http/Requests/StoreMataPelajaranRequest.php
 M app/Http/Requests/UpdateMataPelajaranRequest.php
 M app/Models/Kelas.php
 M app/Models/Siswa.php
 M app/Models/TahunAjaran.php
 M app/Services/GuruAnalysisService.php
 M app/Services/GuruDashboardService.php
 M app/Services/GuruScoreService.php
 M app/Services/WaliKelasService.php
 M database/factories/MataPelajaranFactory.php
 M resources/views/admin/dashboard.blade.php
 M resources/views/admin/identitas-sekolah/edit.blade.php
 M resources/views/admin/mata-pelajaran/_form.blade.php
 M resources/views/admin/mata-pelajaran/create.blade.php
 M resources/views/admin/mata-pelajaran/edit.blade.php
 M resources/views/admin/mata-pelajaran/index.blade.php
 M resources/views/admin/pengampu/_form.blade.php
 M resources/views/admin/pengampu/create.blade.php
 M resources/views/admin/pengampu/index.blade.php
 M resources/views/admin/tahun-ajaran/_form.blade.php
 M resources/views/admin/tahun-ajaran/index.blade.php
 M resources/views/guru/analisis-nilai/index.blade.php
 M resources/views/guru/kelas-mata-pelajaran/index.blade.php
 M resources/views/guru/kelas-mata-pelajaran/show.blade.php
 M resources/views/guru/mapel-dashboard.blade.php
 M resources/views/guru/nilai/index.blade.php
 M resources/views/guru/penilaian/_form.blade.php
 M resources/views/guru/penilaian/index.blade.php
 M resources/views/guru/rekap-nilai/index.blade.php
 M resources/views/guru/siswa-perlu-perhatian/index.blade.php
 M resources/views/guru/tujuan-pembelajaran/_form.blade.php
 M resources/views/guru/tujuan-pembelajaran/index.blade.php
 M resources/views/guru/wali-kelas-dashboard.blade.php
 M resources/views/guru/wali/partials/filters.blade.php
 M resources/views/guru/wali/perhatian/index.blade.php
 M tests/Concerns/CreatesAdminTestSchema.php
 M tests/Feature/Admin/DashboardTest.php
 M tests/Feature/Admin/KelasControllerTest.php
 M tests/Feature/Admin/MataPelajaranControllerTest.php
 M tests/Feature/Admin/PengampuControllerTest.php
 M tests/Feature/Admin/SiswaControllerTest.php
 M tests/Feature/Admin/TahunAjaranControllerTest.php
 M tests/Feature/Guru/AnalisisNilaiControllerTest.php
 M tests/Feature/Guru/DashboardTest.php
 M tests/Feature/Guru/InputNilaiControllerTest.php
 M tests/Feature/Guru/KelasMataPelajaranControllerTest.php
 M tests/Feature/Guru/PenilaianControllerTest.php
 M tests/Feature/Guru/RekapNilaiControllerTest.php
 M tests/Feature/Guru/SiswaPerluPerhatianControllerTest.php
 M tests/Feature/Guru/WaliKelasControllerTest.php
 M tests/Feature/Guru/WaliKelasDashboardTest.php
 M tests/Feature/TujuanPembelajaranTest.php
?? app/Models/RiwayatPenempatanSiswa.php
?? app/Services/NilaiAkhirSimulasiService.php
?? database/factories/RiwayatPenempatanSiswaFactory.php
?? database/migrations/2026_11_14_121030_make_kkm_legacy_nullable_on_mata_pelajaran_table.php
?? database/migrations/2026_11_14_124453_create_riwayat_penempatan_siswa_table.php
?? docs/
?? tests/Feature/LegacyKkmMigrationTest.php
?? tests/Feature/RiwayatPenempatanSiswaTest.php
```

## 8. Checkpoint untuk sesi berikutnya

Langkah pertama adalah menyiapkan dan memverifikasi database **MySQL 8 terisolasi** `siraport_migration_test`. Pastikan database kosong dan koneksi aktual menunjuk ke nama tersebut sebelum menjalankan migration di sana. Jangan gunakan `rapor_digital` untuk pengujian. Setelah migration diverifikasi, lanjutkan dengan rancangan resmi rentang penempatan dan overlap sebelum membuat UI atau mengubah roster historis.

Tahap 9A berhenti di fondasi data dan relasi. Tidak ada Tahap 9B, migrasi database pengembangan, atau commit yang dilakukan.
