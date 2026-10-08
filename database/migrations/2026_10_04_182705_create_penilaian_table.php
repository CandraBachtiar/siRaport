<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('penilaian')) {
            throw new RuntimeException('Migrasi penilaian dihentikan: tabel penilaian sudah ada meskipun migration masih pending. Periksa struktur tabel sebelum melanjutkan.');
        }

        Schema::create('penilaian', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pengampu_id')->constrained('pengampu')->cascadeOnDelete();
            $table->string('nama');
            $table->enum('jenis', ['tugas', 'ulangan_harian', 'uts', 'uas']);
            $table->date('tanggal');
            $table->decimal('bobot', 5, 2)->nullable();
            $table->unsignedInteger('urutan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian');
    }
};
