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
        Schema::create('riwayat_penempatan_siswa_audit', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('riwayat_penempatan_siswa_id')
                ->constrained('riwayat_penempatan_siswa', 'id', 'rps_audit_placement_fk')
                ->restrictOnDelete();
            $table->foreignId('user_id')
                ->constrained('users', 'id', 'rps_audit_user_fk')
                ->restrictOnDelete();
            $table->string('aksi', 20);
            $table->text('alasan');
            $table->string('referensi_bukti', 1000);
            $table->json('data_sebelum')->nullable();
            $table->json('data_sesudah')->nullable();
            $table->timestamps();

            $table->index(['riwayat_penempatan_siswa_id', 'created_at'], 'rps_audit_placement_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('riwayat_penempatan_siswa_audit')->exists()) {
            throw new RuntimeException('Jejak audit riwayat penempatan tidak dapat dihapus otomatis saat rollback.');
        }

        Schema::dropIfExists('riwayat_penempatan_siswa_audit');
    }
};
