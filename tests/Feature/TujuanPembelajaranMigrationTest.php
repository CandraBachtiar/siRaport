<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TujuanPembelajaranMigrationTest extends TestCase
{
    public function test_goal_migrations_preserve_legacy_assessments_and_restrict_deleting_used_goals(): void
    {
        Schema::create('pengampu', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('penilaian', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('jenis')->default('tugas');
            $table->timestamps();
        });

        $legacyAssessmentId = DB::table('penilaian')->insertGetId([
            'nama' => 'Penilaian Lama Tanpa TP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $createGoalMigration = require database_path('migrations/2026_11_06_055042_create_tujuan_pembelajaran_table.php');
        $addGoalRelationMigration = require database_path('migrations/2026_11_06_055043_add_tujuan_pembelajaran_id_to_penilaian_table.php');
        $createGoalMigration->up();
        $addGoalRelationMigration->up();
        $legacyGoalAssignmentId = DB::table('pengampu')->insertGetId([]);
        $legacyGoalId = DB::table('tujuan_pembelajaran')->insertGetId([
            'pengampu_id' => $legacyGoalAssignmentId,
            'urutan' => 1,
            'tujuan' => 'TP lama tanpa metode rekap.',
            'kktp' => 75,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $addStageFourMigration = require database_path('migrations/2026_11_06_095853_add_kategori_asesmen_and_metode_rekap_to_academic_records.php');
        $addStageFourMigration->up();

        $this->assertTrue(Schema::hasTable('tujuan_pembelajaran'));
        $this->assertTrue(Schema::hasColumn('penilaian', 'tujuan_pembelajaran_id'));
        $this->assertSame(
            'Penilaian Lama Tanpa TP',
            DB::table('penilaian')->where('id', $legacyAssessmentId)->value('nama'),
        );
        $this->assertNull(DB::table('penilaian')->where('id', $legacyAssessmentId)->value('tujuan_pembelajaran_id'));
        $this->assertNull(DB::table('penilaian')->where('id', $legacyAssessmentId)->value('kategori_asesmen'));
        $this->assertNull(DB::table('tujuan_pembelajaran')->where('id', $legacyGoalId)->value('metode_rekap'));

        $goalId = DB::table('tujuan_pembelajaran')->insertGetId([
            'pengampu_id' => DB::table('pengampu')->insertGetId([]),
            'urutan' => 1,
            'tujuan' => 'Tujuan uji migrasi.',
            'kktp' => 75,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('penilaian')->where('id', $legacyAssessmentId)->update(['tujuan_pembelajaran_id' => $goalId]);

        try {
            DB::table('tujuan_pembelajaran')->where('id', $goalId)->delete();
            $this->fail('Penghapusan TP yang sudah digunakan seharusnya ditolak oleh foreign key.');
        } catch (QueryException) {
            $this->assertDatabaseHas('tujuan_pembelajaran', ['id' => $goalId]);
        }
    }
}
