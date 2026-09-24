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
        Schema::create('nilai', function (Blueprint $table) {
            $table->id();

            $table->foreignId('siswa_id')
                ->constrained('siswa')
                ->cascadeOnDelete();

            $table->foreignId('mata_pelajaran_id')
                ->constrained('mata_pelajaran')
                ->cascadeOnDelete();

            $table->foreignId('guru_id')
                ->constrained('guru')
                ->cascadeOnDelete();

            $table->foreignId('tahun_ajaran_id')
                ->constrained('tahun_ajaran')
                ->cascadeOnDelete();

            $table->decimal('tugas', 5, 2)->nullable();
            $table->decimal('ulangan_harian', 5, 2)->nullable();
            $table->decimal('uts', 5, 2)->nullable();
            $table->decimal('uas', 5, 2)->nullable();

            $table->decimal('nilai_akhir', 5, 2)->nullable();

            $table->timestamps();

            $table->unique([
                'siswa_id',
                'mata_pelajaran_id',
                'guru_id',
                'tahun_ajaran_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nilai');
    }
};
