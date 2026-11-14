<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesAdminTestSchema
{
    protected function createAdminTestSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'guru'])->default('guru');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('guru', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->string('nip')->nullable()->unique();
            $table->string('nama');
            $table->string('no_hp')->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();
        });

        Schema::create('kelas', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->default('A');
            $table->string('tingkat')->default('VII');
            $table->foreignId('wali_kelas_id')->nullable();
            $table->timestamps();
        });

        Schema::create('tahun_ajaran', function (Blueprint $table): void {
            $table->id();
            $table->string('tahun');
            $table->enum('semester', ['Ganjil', 'Genap']);
            $table->boolean('aktif')->default(false);
            $table->unique(['tahun', 'semester'], 'tahun_ajaran_tahun_semester_unique');
            $table->timestamps();
        });

        Schema::create('pengampu', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guru_id');
            $table->foreignId('kelas_id')->nullable();
            $table->foreignId('mata_pelajaran_id')->nullable();
            $table->foreignId('tahun_ajaran_id')->nullable();
            $table->timestamps();
        });

        Schema::create('tujuan_pembelajaran', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pengampu_id')->constrained('pengampu')->restrictOnDelete();
            $table->unsignedSmallInteger('urutan');
            $table->text('tujuan');
            $table->decimal('kktp', 5, 2);
            $table->string('metode_rekap')->nullable();
            $table->timestamps();
            $table->unique(['pengampu_id', 'urutan']);
        });

        Schema::create('siswa', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kelas_id');
            $table->string('nis')->unique();
            $table->string('nisn')->nullable()->unique();
            $table->string('nama');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->date('tanggal_lahir')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();
        });

        Schema::create('penilaian', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pengampu_id')->constrained('pengampu')->restrictOnDelete();
            $table->foreignId('tujuan_pembelajaran_id')->nullable()->constrained('tujuan_pembelajaran')->restrictOnDelete();
            $table->string('nama');
            $table->enum('jenis', ['tugas', 'ulangan_harian', 'uts', 'uas']);
            $table->string('kategori_asesmen')->nullable();
            $table->date('tanggal');
            $table->decimal('bobot', 5, 2)->nullable();
            $table->unsignedInteger('urutan');
            $table->timestamps();
        });

        Schema::create('nilai', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->restrictOnDelete();
            $table->foreignId('penilaian_id')->constrained('penilaian')->restrictOnDelete();
            $table->decimal('nilai', 5, 2);
            $table->unique(['siswa_id', 'penilaian_id'], 'nilai_siswa_id_penilaian_id_unique');
            $table->timestamps();
        });

        Schema::create('deskripsi', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nilai_id')->constrained('nilai')->cascadeOnDelete();
            $table->text('rekomendasi')->nullable();
            $table->text('deskripsi_akhir')->nullable();
            $table->enum('status', ['draft', 'tervalidasi'])->default('draft');
            $table->string('validasi_fingerprint', 64)->nullable();
            $table->unique('nilai_id', 'deskripsi_nilai_id_unique');
            $table->timestamps();
        });

        Schema::create('identitas_sekolah', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_sekolah');
            $table->string('npsn')->nullable();
            $table->text('alamat')->nullable();
            $table->string('nama_kepala_sekolah')->nullable();
            $table->string('nip_kepala_sekolah')->nullable();
            $table->timestamps();
        });

        Schema::create('data_rapor_siswa', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa');
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran');
            $table->unsignedSmallInteger('sakit')->nullable();
            $table->unsignedSmallInteger('izin')->nullable();
            $table->unsignedSmallInteger('tanpa_keterangan')->nullable();
            $table->text('catatan_wali_kelas')->nullable();
            $table->enum('keputusan_kenaikan', ['naik', 'tidak_naik'])->nullable();
            $table->foreignId('keputusan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['siswa_id', 'tahun_ajaran_id']);
        });

        Schema::create('ekstrakurikuler_siswa', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa');
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran');
            $table->string('nama_kegiatan');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->unique(['siswa_id', 'tahun_ajaran_id', 'nama_kegiatan']);
        });

        Schema::create('mata_pelajaran', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->decimal('kkm', 5, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('riwayat_penempatan_siswa', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->restrictOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->restrictOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->restrictOnDelete();
            $table->date('mulai_pada');
            $table->date('berakhir_pada')->nullable();
            $table->timestamps();
            $table->index(['siswa_id', 'tahun_ajaran_id', 'mulai_pada'], 'rps_siswa_tahun_mulai_idx');
            $table->index(['siswa_id', 'mulai_pada', 'berakhir_pada'], 'rps_siswa_rentang_idx');
            $table->index(['kelas_id', 'tahun_ajaran_id'], 'rps_kelas_periode_idx');
        });
    }
}
