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
        Schema::table('penilaian', function (Blueprint $table) {
            $table->foreignId('tujuan_pembelajaran_id')
                ->nullable()
                ->constrained('tujuan_pembelajaran')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penilaian', function (Blueprint $table) {
            $table->dropForeign(['tujuan_pembelajaran_id']);
            $table->dropColumn('tujuan_pembelajaran_id');
        });
    }
};
