<?php

namespace Tests\Feature\Services;

use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\RiwayatPenempatanSiswaAudit;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\PlacementHistoryManager;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class PlacementHistoryManagerTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->createAdminTestSchema();

        $migration = require database_path('migrations/2026_11_19_213932_create_riwayat_penempatan_siswa_audit_table.php');
        $migration->up();
    }

    public function test_admin_can_record_placements_for_next_period_and_repeat_class(): void
    {
        $admin = User::factory()->admin()->create();
        $class = Kelas::factory()->create();
        $student = Siswa::factory()->for($class)->create();
        $firstPeriod = TahunAjaran::factory()->create(['tahun' => '2024/2025', 'semester' => 'Ganjil']);
        $nextPeriod = TahunAjaran::factory()->create(['tahun' => '2025/2026', 'semester' => 'Ganjil']);
        $manager = new PlacementHistoryManager;

        $first = $manager->record($admin, $student->id, $class->id, $firstPeriod->id, '2024-07-01', '2025-06-30', 'Penempatan awal', 'SK-001');
        $repeat = $manager->record($admin, $student->id, $class->id, $nextPeriod->id, '2025-07-01', null, 'Siswa melanjutkan di kelas yang sama', 'SK-002');

        $this->assertModelExists($first);
        $this->assertModelExists($repeat);
        $this->assertSame($class->id, $student->fresh()->kelas_id);
        $this->assertDatabaseCount('riwayat_penempatan_siswa_audit', 2);
        $this->assertSame('pencatatan', $first->audit()->sole()->aksi);
    }

    public function test_admin_can_close_a_placement_before_recording_a_sequential_class_transfer(): void
    {
        $admin = User::factory()->admin()->create();
        $oldClass = Kelas::factory()->create(['nama' => '2A']);
        $newClass = Kelas::factory()->create(['nama' => '2B']);
        $student = Siswa::factory()->for($oldClass)->create();
        $period = TahunAjaran::factory()->create(['tahun' => '2025/2026', 'semester' => 'Genap']);
        $manager = new PlacementHistoryManager;
        $oldPlacement = $manager->record($admin, $student->id, $oldClass->id, $period->id, '2026-01-01', null, 'Penempatan awal', 'SK-010');

        $closed = $manager->close($admin, $oldPlacement->id, '2026-03-14', 'Pindah rombel', 'BA-011');
        $newPlacement = $manager->record($admin, $student->id, $newClass->id, $period->id, '2026-03-15', null, 'Pindah ke rombel 2B', 'SK-012');

        $this->assertSame('2026-03-14', $closed->fresh()->berakhir_pada->toDateString());
        $this->assertSame($newClass->id, $newPlacement->kelas_id);
        $this->assertSame('penutupan', $closed->audit()->latest('id')->firstOrFail()->aksi);
        $this->assertDatabaseCount('riwayat_penempatan_siswa', 2);
    }

    public function test_rejects_an_end_date_before_the_start_date(): void
    {
        $this->expectException(ValidationException::class);
        $admin = User::factory()->admin()->create();
        $class = Kelas::factory()->create();
        $student = Siswa::factory()->for($class)->create();
        $period = TahunAjaran::factory()->create();

        (new PlacementHistoryManager)->record($admin, $student->id, $class->id, $period->id, '2026-03-16', '2026-03-15', 'Rentang keliru', 'SK-020');
    }

    public function test_rejects_overlaps_across_classes_and_academic_periods(): void
    {
        $this->expectException(ValidationException::class);
        $admin = User::factory()->admin()->create();
        $firstClass = Kelas::factory()->create();
        $secondClass = Kelas::factory()->create();
        $student = Siswa::factory()->for($firstClass)->create();
        $firstPeriod = TahunAjaran::factory()->create(['tahun' => '2024/2025', 'semester' => 'Ganjil']);
        $secondPeriod = TahunAjaran::factory()->create(['tahun' => '2025/2026', 'semester' => 'Ganjil']);
        $manager = new PlacementHistoryManager;
        $manager->record($admin, $student->id, $firstClass->id, $firstPeriod->id, '2025-01-01', '2025-08-15', 'Penempatan terdahulu', 'SK-030');

        $manager->record($admin, $student->id, $secondClass->id, $secondPeriod->id, '2025-08-15', null, 'Tanggal mulai beririsan', 'SK-031');
    }

    public function test_null_end_date_remains_open_and_blocks_a_conflicting_future_placement(): void
    {
        $this->expectException(ValidationException::class);
        $admin = User::factory()->admin()->create();
        $class = Kelas::factory()->create();
        $otherClass = Kelas::factory()->create();
        $student = Siswa::factory()->for($class)->create();
        $firstPeriod = TahunAjaran::factory()->create();
        $secondPeriod = TahunAjaran::factory()->create();
        $manager = new PlacementHistoryManager;
        $placement = $manager->record($admin, $student->id, $class->id, $firstPeriod->id, '2026-01-01', null, 'Belum ada tanggal akhir', 'SK-040');

        $this->assertNull($placement->berakhir_pada);
        $manager->record($admin, $student->id, $otherClass->id, $secondPeriod->id, '2027-01-01', null, 'Tidak boleh tumpang tindih', 'SK-041');
    }

    public function test_correction_preserves_the_previous_values_in_the_audit_log(): void
    {
        $admin = User::factory()->admin()->create();
        $oldClass = Kelas::factory()->create();
        $correctedClass = Kelas::factory()->create();
        $student = Siswa::factory()->for($oldClass)->create();
        $period = TahunAjaran::factory()->create();
        $placement = (new PlacementHistoryManager)->record($admin, $student->id, $oldClass->id, $period->id, '2026-01-01', '2026-06-30', 'Catat dari dokumen', 'SK-050');

        $corrected = (new PlacementHistoryManager)->correct(
            $admin,
            $placement->id,
            $correctedClass->id,
            $period->id,
            '2026-01-02',
            '2026-06-30',
            'Tanggal pada dokumen sebelumnya salah',
            'SK-koreksi-051',
        );
        $correctionAudit = $corrected->audit()->where('aksi', 'koreksi')->sole();

        $this->assertSame($correctedClass->id, $corrected->kelas_id);
        $this->assertSame($oldClass->id, $correctionAudit->data_sebelum['kelas_id']);
        $this->assertSame($correctedClass->id, $correctionAudit->data_sesudah['kelas_id']);
        $this->assertSame('2026-01-01', $correctionAudit->data_sebelum['mulai_pada']);
        $this->assertSame('2026-01-02', $correctionAudit->data_sesudah['mulai_pada']);
        $this->assertSame('2026-06-30', $correctionAudit->data_sebelum['berakhir_pada']);
        $this->assertSame('2026-06-30', $correctionAudit->data_sesudah['berakhir_pada']);
        $this->assertSame(2, $corrected->audit()->count());
        $this->assertSame($oldClass->id, $student->fresh()->kelas_id);
    }

    public function test_rejects_invalid_class_or_academic_period_ids(): void
    {
        $admin = User::factory()->admin()->create();
        $class = Kelas::factory()->create();
        $student = Siswa::factory()->for($class)->create();
        $period = TahunAjaran::factory()->create();
        $manager = new PlacementHistoryManager;

        try {
            $manager->record($admin, $student->id, 99999, $period->id, '2026-01-01', null, 'Uji kelas invalid', 'SK-060');
            $this->fail('Kelas yang tidak ditemukan seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('kelas_id', $exception->errors());
        }

        try {
            $manager->record($admin, $student->id, $class->id, 99999, '2026-01-01', null, 'Uji periode invalid', 'SK-061');
            $this->fail('Periode yang tidak ditemukan seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('tahun_ajaran_id', $exception->errors());
        }

        $this->assertDatabaseCount('riwayat_penempatan_siswa', 0);
    }

    public function test_denies_non_admin_users_and_leaves_records_unchanged(): void
    {
        $teacher = User::factory()->guru()->create();
        $class = Kelas::factory()->create();
        $student = Siswa::factory()->for($class)->create();
        $period = TahunAjaran::factory()->create();

        try {
            (new PlacementHistoryManager)->record($teacher, $student->id, $class->id, $period->id, '2026-01-01', null, 'Percobaan guru', 'SK-070');
            $this->fail('Guru tidak boleh mencatat riwayat penempatan.');
        } catch (AccessDeniedHttpException) {
            $this->assertDatabaseCount('riwayat_penempatan_siswa', 0);
        }
    }

    public function test_rolls_back_placement_when_audit_write_fails(): void
    {
        DB::statement("CREATE TRIGGER reject_placement_audit BEFORE INSERT ON riwayat_penempatan_siswa_audit BEGIN SELECT RAISE(ABORT, 'audit write failed'); END");
        $admin = User::factory()->admin()->create();
        $class = Kelas::factory()->create();
        $student = Siswa::factory()->for($class)->create();
        $period = TahunAjaran::factory()->create();

        try {
            (new PlacementHistoryManager)->record($admin, $student->id, $class->id, $period->id, '2026-01-01', null, 'Harus dibatalkan', 'SK-080');
            $this->fail('Kegagalan audit harus membatalkan transaksi.');
        } catch (QueryException) {
            $this->assertDatabaseCount('riwayat_penempatan_siswa', 0);
            $this->assertDatabaseCount('riwayat_penempatan_siswa_audit', 0);
        }
    }

    public function test_recording_history_does_not_change_current_class_or_existing_scores(): void
    {
        $admin = User::factory()->admin()->create();
        $historicalClass = Kelas::factory()->create();
        $currentClass = Kelas::factory()->create();
        $student = Siswa::factory()->for($currentClass)->create();
        $period = TahunAjaran::factory()->create();
        $assignment = Pengampu::factory()->create([
            'kelas_id' => $historicalClass->id,
            'tahun_ajaran_id' => $period->id,
        ]);
        $assessment = Penilaian::factory()->for($assignment)->create();
        $score = Nilai::factory()->create([
            'siswa_id' => $student->id,
            'penilaian_id' => $assessment->id,
            'nilai' => 78,
        ]);

        (new PlacementHistoryManager)->record($admin, $student->id, $historicalClass->id, $period->id, $assessment->tanggal->toDateString(), null, 'Bukti penempatan lama', 'SK-090');

        $this->assertSame($currentClass->id, $student->fresh()->kelas_id);
        $this->assertSame($historicalClass->id, $assessment->fresh()->pengampu->kelas_id);
        $this->assertSame(78.0, (float) $score->fresh()->nilai);
        $this->assertSame($assessment->id, $score->fresh()->penilaian_id);
    }

    public function test_audit_migration_refuses_to_drop_existing_audit_records(): void
    {
        $admin = User::factory()->admin()->create();
        $class = Kelas::factory()->create();
        $student = Siswa::factory()->for($class)->create();
        $period = TahunAjaran::factory()->create();
        $placement = (new PlacementHistoryManager)->record($admin, $student->id, $class->id, $period->id, '2026-01-01', null, 'Riwayat untuk uji rollback', 'SK-100');
        $migration = require database_path('migrations/2026_11_19_213932_create_riwayat_penempatan_siswa_audit_table.php');

        try {
            $migration->down();
            $this->fail('Rollback tidak boleh menghapus jejak audit.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('tidak dapat dihapus otomatis', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasTable('riwayat_penempatan_siswa_audit'));
        $this->assertSame(1, $placement->audit()->count());
    }

    public function test_placement_and_audit_records_cannot_be_deleted_or_audit_changed(): void
    {
        $admin = User::factory()->admin()->create();
        $class = Kelas::factory()->create();
        $student = Siswa::factory()->for($class)->create();
        $period = TahunAjaran::factory()->create();
        $placement = (new PlacementHistoryManager)->record($admin, $student->id, $class->id, $period->id, '2026-01-01', null, 'Catatan yang dipertahankan', 'SK-110');
        $audit = $placement->audit()->sole();

        try {
            $audit->update(['alasan' => 'alasan diubah']);
            $this->fail('Jejak audit harus tidak dapat diubah.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('tidak dapat diubah', $exception->getMessage());
        }

        try {
            $audit->delete();
            $this->fail('Jejak audit harus tidak dapat dihapus.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('tidak dapat dihapus', $exception->getMessage());
        }

        try {
            $placement->delete();
            $this->fail('Riwayat penempatan harus dikoreksi, bukan dihapus.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('Catat koreksi', $exception->getMessage());
        }

        $this->assertSame('pencatatan', RiwayatPenempatanSiswaAudit::query()->sole()->aksi);
        $this->assertModelExists($placement->fresh());
    }
}
