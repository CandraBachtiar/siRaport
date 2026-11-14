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
        Schema::table('mata_pelajaran', function (Blueprint $table): void {
            $table->decimal('kkm', 5, 2)->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('mata_pelajaran')->whereNull('kkm')->exists()) {
            throw new RuntimeException('KKM legacy tidak dapat dikembalikan menjadi wajib selama masih ada mata pelajaran tanpa KKM.');
        }

        Schema::table('mata_pelajaran', function (Blueprint $table): void {
            $table->decimal('kkm', 5, 2)->default(75)->change();
        });
    }
};
