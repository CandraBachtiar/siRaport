<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\RiwayatPenempatanSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class RiwayatPenempatanSiswaTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->createAdminTestSchema();
        Schema::dropIfExists('riwayat_penempatan_siswa');

        $migration = require database_path('migrations/2026_11_14_124453_create_riwayat_penempatan_siswa_table.php');
        $migration->up();
    }

    public function test_migration_creates_period_placement_schema_and_relationships(): void
    {
        $columns = collect(Schema::getColumns('riwayat_penempatan_siswa'))->keyBy('name');
        $this->assertFalse($columns['mulai_pada']['nullable']);
        $this->assertTrue($columns['berakhir_pada']['nullable']);

        $class = Kelas::factory()->create();
        $student = Siswa::factory()->for($class)->create();
        $schoolYear = TahunAjaran::factory()->create(['tahun' => '2026/2027', 'semester' => 'Ganjil']);
        $placement = RiwayatPenempatanSiswa::factory()->create([
            'siswa_id' => $student->id,
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $schoolYear->id,
            'mulai_pada' => '2026-07-01',
        ]);

        $this->assertSame($student->id, $placement->fresh()->siswa->id);
        $this->assertSame($class->id, $placement->fresh()->kelas->id);
        $this->assertSame($schoolYear->id, $placement->fresh()->tahunAjaran->id);
        $this->assertSame($placement->id, $student->riwayatPenempatan()->sole()->id);
        $this->assertSame($placement->id, $class->riwayatPenempatanSiswa()->sole()->id);
        $this->assertSame($placement->id, $schoolYear->riwayatPenempatanSiswa()->sole()->id);
    }

    public function test_migration_does_not_guess_historical_placements_from_current_student_class(): void
    {
        Schema::dropIfExists('riwayat_penempatan_siswa');
        $student = Siswa::factory()->create();
        $assessment = Penilaian::factory()->create();
        $score = Nilai::factory()->create([
            'siswa_id' => $student->id,
            'penilaian_id' => $assessment->id,
            'nilai' => 73,
        ]);
        $currentClassId = $student->kelas_id;
        $migration = require database_path('migrations/2026_11_14_124453_create_riwayat_penempatan_siswa_table.php');

        $migration->up();

        $this->assertDatabaseCount('riwayat_penempatan_siswa', 0);
        $this->assertDatabaseHas('siswa', ['id' => $student->id, 'kelas_id' => $currentClassId]);
        $this->assertDatabaseHas('nilai', ['id' => $score->id, 'siswa_id' => $student->id, 'nilai' => 73]);
        $this->assertSame($assessment->id, $score->fresh()->penilaian->id);
    }

    public function test_student_can_have_sequential_class_placements_within_one_period_without_losing_old_scores(): void
    {
        $oldClass = Kelas::factory()->create(['nama' => 'A']);
        $currentClass = Kelas::factory()->create(['nama' => 'B']);
        $student = Siswa::factory()->for($currentClass)->create();
        $schoolYear = TahunAjaran::factory()->create(['tahun' => '2026/2027', 'semester' => 'Ganjil']);
        $assignment = Pengampu::factory()->create([
            'kelas_id' => $oldClass->id,
            'tahun_ajaran_id' => $schoolYear->id,
        ]);
        $assessment = Penilaian::factory()->for($assignment)->create();
        $score = Nilai::factory()->create([
            'siswa_id' => $student->id,
            'penilaian_id' => $assessment->id,
            'nilai' => 84,
        ]);

        $oldPlacement = RiwayatPenempatanSiswa::factory()->create([
            'siswa_id' => $student->id,
            'kelas_id' => $oldClass->id,
            'tahun_ajaran_id' => $schoolYear->id,
            'mulai_pada' => '2026-07-01',
            'berakhir_pada' => '2026-10-15 00:00:00',
        ]);
        $newPlacement = RiwayatPenempatanSiswa::factory()->create([
            'siswa_id' => $student->id,
            'kelas_id' => $currentClass->id,
            'tahun_ajaran_id' => $schoolYear->id,
            'mulai_pada' => '2026-10-16',
            'berakhir_pada' => null,
        ]);

        $this->assertCount(2, $student->riwayatPenempatan()->get());
        $this->assertDatabaseHas('riwayat_penempatan_siswa', [
            'id' => $oldPlacement->id,
            'kelas_id' => $oldClass->id,
            'berakhir_pada' => '2026-10-15 00:00:00',
        ]);
        $this->assertDatabaseHas('riwayat_penempatan_siswa', [
            'id' => $newPlacement->id,
            'kelas_id' => $currentClass->id,
            'berakhir_pada' => null,
        ]);
        $this->assertDatabaseHas('siswa', ['id' => $student->id, 'kelas_id' => $currentClass->id]);
        $this->assertDatabaseHas('nilai', [
            'id' => $score->id,
            'siswa_id' => $student->id,
            'penilaian_id' => $assessment->id,
            'nilai' => 84,
        ]);
        $this->assertSame($oldClass->id, $score->fresh()->penilaian->pengampu->kelas_id);
    }

    public function test_student_can_remain_in_the_same_class_across_different_school_year_periods(): void
    {
        $class = Kelas::factory()->create(['tingkat' => '3', 'nama' => 'A']);
        $student = Siswa::factory()->for($class)->create();
        $firstPeriod = TahunAjaran::factory()->create(['tahun' => '2025/2026', 'semester' => 'Ganjil']);
        $nextPeriod = TahunAjaran::factory()->create(['tahun' => '2026/2027', 'semester' => 'Ganjil']);

        $firstPlacement = RiwayatPenempatanSiswa::factory()->create([
            'siswa_id' => $student->id,
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $firstPeriod->id,
            'mulai_pada' => '2025-07-01',
            'berakhir_pada' => '2026-06-30',
        ]);
        $nextPlacement = RiwayatPenempatanSiswa::factory()->create([
            'siswa_id' => $student->id,
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $nextPeriod->id,
            'mulai_pada' => '2026-07-01',
        ]);

        $this->assertCount(2, $student->riwayatPenempatan()->get());
        $this->assertSame($class->id, $firstPlacement->kelas_id);
        $this->assertSame($class->id, $nextPlacement->kelas_id);
        $this->assertSame($class->id, $student->fresh()->kelas_id);
    }

    public function test_migration_rollback_refuses_to_drop_saved_placement_history(): void
    {
        $class = Kelas::factory()->create();
        $student = Siswa::factory()->for($class)->create();
        $schoolYear = TahunAjaran::factory()->create();
        $placement = RiwayatPenempatanSiswa::factory()->create([
            'siswa_id' => $student->id,
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $schoolYear->id,
        ]);
        $migration = require database_path('migrations/2026_11_14_124453_create_riwayat_penempatan_siswa_table.php');

        try {
            $migration->down();
            $this->fail('Rollback harus mempertahankan riwayat penempatan yang sudah tersimpan.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('tidak dapat dihapus otomatis', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasTable('riwayat_penempatan_siswa'));
        $this->assertDatabaseHas('riwayat_penempatan_siswa', ['id' => $placement->id]);
    }

    public function test_empty_placement_migration_can_be_rolled_back(): void
    {
        $migration = require database_path('migrations/2026_11_14_124453_create_riwayat_penempatan_siswa_table.php');

        $migration->down();

        $this->assertFalse(Schema::hasTable('riwayat_penempatan_siswa'));
    }
}
