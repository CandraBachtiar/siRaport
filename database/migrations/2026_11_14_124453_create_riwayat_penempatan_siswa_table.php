<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('riwayat_penempatan_siswa', function (Blueprint $table) {
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('riwayat_penempatan_siswa')->exists()) {
            throw new RuntimeException('Riwayat penempatan siswa tidak dapat dihapus otomatis saat rollback.');
        }

        Schema::dropIfExists('riwayat_penempatan_siswa');
    }
};
