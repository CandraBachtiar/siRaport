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
        Schema::create('deskripsi', function (Blueprint $table) {
            $table->id();

            $table->foreignId('siswa_id')
                ->constrained('siswa')
                ->cascadeOnDelete();

            $table->foreignId('mata_pelajaran_id')
                ->constrained('mata_pelajaran')
                ->cascadeOnDelete();

            $table->foreignId('nilai_id')
                ->constrained('nilai')
                ->cascadeOnDelete();

            $table->text('rekomendasi')->nullable();

            $table->text('deskripsi_akhir')->nullable();

            $table->enum('status', [
                'draft',
                'tervalidasi'
            ])->default('draft');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deskripsi');
    }
};
