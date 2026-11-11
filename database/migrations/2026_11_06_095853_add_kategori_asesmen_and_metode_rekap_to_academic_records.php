<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penilaian', function (Blueprint $table): void {
            $table->string('kategori_asesmen')->nullable()->after('jenis');
        });

        Schema::table('tujuan_pembelajaran', function (Blueprint $table): void {
            $table->string('metode_rekap')->nullable()->after('kktp');
        });
    }

    public function down(): void
    {
        Schema::table('tujuan_pembelajaran', function (Blueprint $table): void {
            $table->dropColumn('metode_rekap');
        });

        Schema::table('penilaian', function (Blueprint $table): void {
            $table->dropColumn('kategori_asesmen');
        });
    }
};
