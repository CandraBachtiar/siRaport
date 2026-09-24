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
        Schema::create('siswa', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kelas_id')
                ->constrained('kelas')
                ->cascadeOnDelete();

            $table->string('nis')->unique();
            $table->string('nisn')->nullable()->unique();
            $table->string('nama');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->date('tanggal_lahir')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->text('alamat')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siswa');
    }
};
