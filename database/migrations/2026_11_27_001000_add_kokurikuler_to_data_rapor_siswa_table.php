<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_rapor_siswa', function (Blueprint $table): void {
            $table->text('kokurikuler')->nullable()->after('catatan_wali_kelas');
        });
    }

    public function down(): void
    {
        Schema::table('data_rapor_siswa', function (Blueprint $table): void {
            $table->dropColumn('kokurikuler');
        });
    }
};