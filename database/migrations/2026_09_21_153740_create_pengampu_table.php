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
        Schema::create('pengampu', function (Blueprint $table) {
            $table->id();

            $table->foreignId('guru_id')
                ->constrained('guru')
                ->cascadeOnDelete();

            $table->foreignId('mata_pelajaran_id')
                ->constrained('mata_pelajaran')
                ->cascadeOnDelete();

            $table->foreignId('kelas_id')
                ->constrained('kelas')
                ->cascadeOnDelete();

            $table->foreignId('tahun_ajaran_id')
                ->constrained('tahun_ajaran')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
		[
		   'guru_id',
		   'mata_pelajaran_id',
		   'kelas_id',
		   'tahun_ajaran_id',
		],
		'pengampu_unique'
	 );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengampu');
    }
};
