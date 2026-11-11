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
        Schema::table('deskripsi', function (Blueprint $table): void {
            $table->char('validasi_fingerprint', 64)->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deskripsi', function (Blueprint $table): void {
            $table->dropColumn('validasi_fingerprint');
        });
    }
};
