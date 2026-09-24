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
        Schema::table('nilai', function (Blueprint $table) {
            $table->index('siswa_id', 'nilai_siswa_id_index');
        });

        Schema::table('nilai', function (Blueprint $table) {
            $table->dropUnique(
                'nilai_siswa_id_mata_pelajaran_id_guru_id_tahun_ajaran_id_unique'
            );

            $table->dropColumn([
                'guru_id',
                'mata_pelajaran_id',
                'tahun_ajaran_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nilai', function (Blueprint $table) {
            $table->foreignId('guru_id')
                ->after('siswa_id')
                ->constrained('guru')
                ->cascadeOnDelete();

            $table->foreignId('mata_pelajaran_id')
                ->after('guru_id')
                ->constrained('mata_pelajaran')
                ->cascadeOnDelete();

            $table->foreignId('tahun_ajaran_id')
                ->after('mata_pelajaran_id')
                ->constrained('tahun_ajaran')
                ->cascadeOnDelete();

            $table->unique(
                [
                    'siswa_id',
                    'mata_pelajaran_id',
                    'guru_id',
                    'tahun_ajaran_id'
                ],
                'nilai_siswa_id_mata_pelajaran_id_guru_id_tahun_ajaran_id_unique'
            );
        });
    }
};
