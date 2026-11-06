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

        $this->assertTrue(Schema::hasTable('tujuan_pembelajaran'));
        $this->assertTrue(Schema::hasColumn('penilaian', 'tujuan_pembelajaran_id'));
        $this->assertSame(
            'Penilaian Lama Tanpa TP',
            DB::table('penilaian')->where('id', $legacyAssessmentId)->value('nama'),
        );
        $this->assertNull(DB::table('penilaian')->where('id', $legacyAssessmentId)->value('tujuan_pembelajaran_id'));

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
