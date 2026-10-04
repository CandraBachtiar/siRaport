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

        Schema::create('nilai', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siswa_id');
            $table->foreignId('pengampu_id')->nullable();
            $table->decimal('tugas', 5, 2)->nullable();
            $table->decimal('ulangan_harian', 5, 2)->nullable();
            $table->decimal('uts', 5, 2)->nullable();
            $table->decimal('uas', 5, 2)->nullable();
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->unique(['siswa_id', 'pengampu_id'], 'nilai_siswa_id_pengampu_id_unique');
            $table->timestamps();
        });

        Schema::create('mata_pelajaran', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->decimal('kkm', 5, 2)->default(75);
            $table->timestamps();
        });
    }
}
