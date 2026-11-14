<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class LegacyKkmMigrationTest extends TestCase
{
    public function test_migration_preserves_legacy_kkm_and_new_rows_are_null(): void
    {
        $migration = $this->createLegacyTableAndApplyMigration();

        $kkmColumn = collect(Schema::getColumns('mata_pelajaran'))->firstWhere('name', 'kkm');
        $this->assertTrue($kkmColumn['nullable']);
        $this->assertNull($kkmColumn['default']);
        $this->assertDatabaseHas('mata_pelajaran', ['kode' => 'LEGACY', 'kkm' => '75.25']);

        DB::table('mata_pelajaran')->insert([
            'kode' => 'BARU',
            'nama' => 'Mapel Baru',
        ]);
        $this->assertDatabaseHas('mata_pelajaran', ['kode' => 'BARU', 'kkm' => null]);
    }

    public function test_migration_rollback_refuses_to_coerce_null_kkm_and_restores_legacy_schema_when_safe(): void
    {
        $migration = $this->createLegacyTableAndApplyMigration();
        DB::table('mata_pelajaran')->insert([
            'kode' => 'BARU',
            'nama' => 'Mapel Baru',
        ]);

        try {
            $migration->down();
            $this->fail('Rollback harus menolak perubahan ketika nilai KKM masih NULL.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('masih ada mata pelajaran tanpa KKM', $exception->getMessage());
        }

        $kkmColumn = collect(Schema::getColumns('mata_pelajaran'))->firstWhere('name', 'kkm');
        $this->assertTrue($kkmColumn['nullable']);
        $this->assertDatabaseHas('mata_pelajaran', ['kode' => 'BARU', 'kkm' => null]);

        DB::table('mata_pelajaran')->where('kode', 'BARU')->update(['kkm' => 75]);
        $migration->down();
        $rollbackColumn = collect(Schema::getColumns('mata_pelajaran'))->firstWhere('name', 'kkm');
        $this->assertFalse($rollbackColumn['nullable']);
        $this->assertEquals(75.0, (float) trim((string) $rollbackColumn['default'], "'\""));
        $this->assertDatabaseHas('mata_pelajaran', ['kode' => 'LEGACY', 'kkm' => '75.25']);
        $this->assertDatabaseHas('mata_pelajaran', ['kode' => 'BARU', 'kkm' => 75]);
    }

    private function createLegacyTableAndApplyMigration(): Migration
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        Schema::create('mata_pelajaran', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->decimal('kkm', 5, 2)->default(75);
            $table->timestamps();
        });
        DB::table('mata_pelajaran')->insert([
            'kode' => 'LEGACY',
            'nama' => 'Mapel Legacy',
            'kkm' => 75.25,
        ]);

        $migration = require database_path('migrations/2026_11_14_121030_make_kkm_legacy_nullable_on_mata_pelajaran_table.php');
        $migration->up();

        return $migration;
    }
}
