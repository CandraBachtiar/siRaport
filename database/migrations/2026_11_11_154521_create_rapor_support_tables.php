<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
            $table->foreignId('siswa_id')->constrained('siswa')->restrictOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->restrictOnDelete();
            $table->unsignedSmallInteger('sakit')->nullable();
            $table->unsignedSmallInteger('izin')->nullable();
            $table->unsignedSmallInteger('tanpa_keterangan')->nullable();
            $table->text('catatan_wali_kelas')->nullable();
            $table->enum('keputusan_kenaikan', ['naik', 'tidak_naik'])->nullable();
            $table->foreignId('keputusan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['siswa_id', 'tahun_ajaran_id'], 'data_rapor_siswa_periode_unique');
        });

        Schema::create('ekstrakurikuler_siswa', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->restrictOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->restrictOnDelete();
            $table->string('nama_kegiatan');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->unique(['siswa_id', 'tahun_ajaran_id', 'nama_kegiatan'], 'ekstrakurikuler_siswa_periode_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ekstrakurikuler_siswa');
        Schema::dropIfExists('data_rapor_siswa');
        Schema::dropIfExists('identitas_sekolah');
    }
};
