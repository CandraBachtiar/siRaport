<?php

namespace Tests\Feature\Admin;

use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\PlacementHistoryManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class RiwayatPenempatanSiswaControllerTest extends TestCase
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

    public function test_admin_can_open_student_placement_history_with_current_and_historical_classes(): void
    {
        $admin = User::factory()->admin()->create();
        $historicalClass = Kelas::factory()->create(['tingkat' => '1', 'nama' => 'A']);
        $currentClass = Kelas::factory()->create(['tingkat' => '2', 'nama' => 'A']);
        $student = Siswa::factory()->for($currentClass)->create(['nama' => 'Nadia Putri']);
        $period = TahunAjaran::factory()->create(['tahun' => '2024/2025', 'semester' => 'Ganjil']);
        $placement = $this->manager()->record($admin, $student->id, $historicalClass->id, $period->id, '2024-07-01', '2024-12-31', 'Bukti penempatan awal', 'SK-2024-01');
        $this->manager()->record($admin, $student->id, $historicalClass->id, $period->id, '2025-01-01', null, 'Penempatan masih berlangsung', 'SK-2025-02');

        $this->actingAs($admin)->get(route('admin.siswa.riwayat.index', $student))
            ->assertOk()
            ->assertSee('Nadia Putri')
            ->assertSee('Kelas Aktif Saat Ini')
            ->assertSee('2 A')
            ->assertSee('Riwayat Penempatan Tercatat')
            ->assertSee('1 A')
            ->assertSeeHtml('2024/2025 &middot; Ganjil')
            ->assertSee('01/07/2024')
            ->assertSee('31/12/2024')
            ->assertSee('Ditutup')
            ->assertSee('Belum ditutup')
            ->assertSee('Tutup penempatan')
            ->assertSee('Koreksi penempatan');

        $this->assertModelExists($placement);
    }

    public function test_student_without_history_gets_an_informative_empty_state(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Siswa::factory()->create();

        $this->actingAs($admin)->get(route('admin.siswa.riwayat.index', $student))
            ->assertOk()
            ->assertSee('Belum ada riwayat penempatan yang tercatat dalam sistem.')
            ->assertSee('Kondisi ini tidak menjadi bukti bahwa siswa belum pernah berada di kelas lain.');
    }

    public function test_admin_can_record_placement_with_evidence_without_changing_current_class_or_scores(): void
    {
        $admin = User::factory()->admin()->create();
        $currentClass = Kelas::factory()->create(['nama' => 'B']);
        $historicalClass = Kelas::factory()->create(['nama' => 'A']);
        $student = Siswa::factory()->for($currentClass)->create();
        $period = TahunAjaran::factory()->create();
        $assignment = Pengampu::factory()->create(['kelas_id' => $historicalClass->id, 'tahun_ajaran_id' => $period->id]);
        $assessment = Penilaian::factory()->for($assignment)->create();
        $score = Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessment->id, 'nilai' => 79]);

        $this->actingAs($admin)->post(route('admin.siswa.riwayat.store', $student), [
            'kelas_id' => $historicalClass->id,
            'tahun_ajaran_id' => $period->id,
            'mulai_pada' => '2026-01-10',
            'berakhir_pada' => '',
            'alasan' => 'Penempatan berdasarkan surat keputusan sekolah.',
            'referensi_bukti' => 'SK-2026-101',
        ])->assertRedirect(route('admin.siswa.riwayat.index', $student))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('riwayat_penempatan_siswa', [
            'siswa_id' => $student->id,
            'kelas_id' => $historicalClass->id,
            'tahun_ajaran_id' => $period->id,
            'mulai_pada' => '2026-01-10 00:00:00',
            'berakhir_pada' => null,
        ]);
        $this->assertDatabaseHas('riwayat_penempatan_siswa_audit', [
            'user_id' => $admin->id,
            'aksi' => 'pencatatan',
            'referensi_bukti' => 'SK-2026-101',
        ]);
        $this->assertSame($currentClass->id, $student->fresh()->kelas_id);
        $this->assertSame(79.0, (float) $score->fresh()->nilai);
        $this->assertSame($historicalClass->id, $assessment->fresh()->pengampu->kelas_id);
    }

    public function test_conflicting_placement_returns_a_user_facing_validation_error_and_keeps_old_input(): void
    {
        $admin = User::factory()->admin()->create();
        $class = Kelas::factory()->create();
        $otherClass = Kelas::factory()->create();
        $student = Siswa::factory()->for($class)->create();
        $period = TahunAjaran::factory()->create();
        $otherPeriod = TahunAjaran::factory()->create();
        $this->manager()->record($admin, $student->id, $class->id, $period->id, '2026-01-01', '2026-05-10', 'Penempatan lama', 'SK-201');

        $this->actingAs($admin)->from(route('admin.siswa.riwayat.index', $student))
            ->post(route('admin.siswa.riwayat.store', $student), [
                'kelas_id' => $otherClass->id,
                'tahun_ajaran_id' => $otherPeriod->id,
                'mulai_pada' => '2026-05-10',
                'berakhir_pada' => '',
                'alasan' => 'Tanggal mulai beririsan.',
                'referensi_bukti' => 'SK-2026-202',
            ])
            ->assertRedirect(route('admin.siswa.riwayat.index', $student))
            ->assertSessionHasErrors('mulai_pada');

        $this->assertSame('Tanggal mulai beririsan.', session()->getOldInput('alasan'));
        $this->assertDatabaseCount('riwayat_penempatan_siswa', 1);
    }

    public function test_admin_can_close_a_placement_and_audit_the_action(): void
    {
        $admin = User::factory()->admin()->create();
        $class = Kelas::factory()->create();
        $student = Siswa::factory()->for($class)->create();
        $period = TahunAjaran::factory()->create();
        $placement = $this->manager()->record($admin, $student->id, $class->id, $period->id, '2026-01-01', null, 'Penempatan awal', 'SK-301');

        $this->actingAs($admin)->post(route('admin.siswa.riwayat.close', [$student, $placement]), [
            'berakhir_pada' => '2026-06-30',
            'alasan' => 'Penempatan selesai sesuai berita acara.',
            'referensi_bukti' => 'BA-2026-302',
        ])->assertRedirect(route('admin.siswa.riwayat.index', $student))
            ->assertSessionHas('success');

        $this->assertSame('2026-06-30', $placement->fresh()->berakhir_pada->toDateString());
        $this->assertDatabaseHas('riwayat_penempatan_siswa_audit', [
            'riwayat_penempatan_siswa_id' => $placement->id,
            'user_id' => $admin->id,
            'aksi' => 'penutupan',
            'referensi_bukti' => 'BA-2026-302',
        ]);
    }

    public function test_admin_can_correct_placement_and_audit_before_and_after_values(): void
    {
        $admin = User::factory()->admin()->create();
        $studentClass = Kelas::factory()->create(['nama' => 'Aktif']);
        $oldClass = Kelas::factory()->create(['tingkat' => 'VII', 'nama' => 'A']);
        $correctedClass = Kelas::factory()->create(['tingkat' => 'VII', 'nama' => 'B']);
        $student = Siswa::factory()->for($studentClass)->create();
        $period = TahunAjaran::factory()->create();
        $placement = $this->manager()->record($admin, $student->id, $oldClass->id, $period->id, '2026-01-01', '2026-04-30', 'Pencatatan awal', 'SK-401');

        $this->actingAs($admin)->put(route('admin.siswa.riwayat.correct', [$student, $placement]), [
            'kelas_id' => $correctedClass->id,
            'tahun_ajaran_id' => $period->id,
            'mulai_pada' => '2026-01-02',
            'berakhir_pada' => '2026-04-30',
            'alasan' => 'Koreksi mengikuti dokumen terbaru.',
            'referensi_bukti' => 'SK-KOREKSI-402',
        ])->assertRedirect(route('admin.siswa.riwayat.index', $student))
            ->assertSessionHas('success');

        $this->assertSame($correctedClass->id, $placement->fresh()->kelas_id);
        $this->assertSame($studentClass->id, $student->fresh()->kelas_id);
        $correction = $placement->audit()->where('aksi', 'koreksi')->sole();
        $this->assertSame($oldClass->id, $correction->data_sebelum['kelas_id']);
        $this->assertSame($correctedClass->id, $correction->data_sesudah['kelas_id']);
        $this->assertSame('SK-KOREKSI-402', $correction->referensi_bukti);

        $this->actingAs($admin)->get(route('admin.siswa.riwayat.index', $student))
            ->assertOk()
            ->assertSee('Sebelumnya: VII A')
            ->assertSee('Sesudah: VII B')
            ->assertSee('Koreksi mengikuti dokumen terbaru.');
    }

    public function test_non_admin_users_cannot_read_or_change_student_placement_history(): void
    {
        $teacher = User::factory()->guru()->create();
        $student = Siswa::factory()->create();
        $class = Kelas::factory()->create();
        $period = TahunAjaran::factory()->create();

        $this->actingAs($teacher)->get(route('admin.siswa.riwayat.index', $student))->assertForbidden();
        $this->actingAs($teacher)->post(route('admin.siswa.riwayat.store', $student), [
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $period->id,
            'mulai_pada' => '2026-01-01',
            'alasan' => 'Percobaan tanpa hak akses.',
            'referensi_bukti' => 'SK-501',
        ])->assertForbidden();

        $this->assertDatabaseCount('riwayat_penempatan_siswa', 0);
    }

    public function test_invalid_class_and_period_ids_are_rejected_by_form_requests(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Siswa::factory()->create();

        $this->actingAs($admin)->post(route('admin.siswa.riwayat.store', $student), [
            'kelas_id' => 99999,
            'tahun_ajaran_id' => 99999,
            'mulai_pada' => '2026-01-01',
            'alasan' => 'Data tidak valid.',
            'referensi_bukti' => 'SK-601',
        ])->assertSessionHasErrors(['kelas_id', 'tahun_ajaran_id']);

        $this->assertDatabaseCount('riwayat_penempatan_siswa', 0);
    }

    public function test_evidence_reference_is_required_and_form_values_are_kept(): void
    {
        $admin = User::factory()->admin()->create();
        $class = Kelas::factory()->create();
        $student = Siswa::factory()->for($class)->create();
        $period = TahunAjaran::factory()->create();

        $this->actingAs($admin)->from(route('admin.siswa.riwayat.index', $student))
            ->post(route('admin.siswa.riwayat.store', $student), [
                'kelas_id' => $class->id,
                'tahun_ajaran_id' => $period->id,
                'mulai_pada' => '2026-01-01',
                'alasan' => 'Penempatan berdasar arsip.',
                'referensi_bukti' => '',
            ])
            ->assertRedirect(route('admin.siswa.riwayat.index', $student))
            ->assertSessionHasErrors('referensi_bukti');

        $this->assertSame('Penempatan berdasar arsip.', session()->getOldInput('alasan'));
    }

    public function test_audit_failure_rolls_back_write_and_never_shows_raw_sql(): void
    {
        DB::statement("CREATE TRIGGER reject_placement_audit BEFORE INSERT ON riwayat_penempatan_siswa_audit BEGIN SELECT RAISE(ABORT, 'raw sqlite failure'); END");
        $admin = User::factory()->admin()->create();
        $class = Kelas::factory()->create();
        $student = Siswa::factory()->for($class)->create();
        $period = TahunAjaran::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.siswa.riwayat.store', $student), [
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $period->id,
            'mulai_pada' => '2026-01-01',
            'alasan' => 'Gagal saat menulis audit.',
            'referensi_bukti' => 'SK-701',
        ]);

        $response->assertRedirect(route('admin.siswa.riwayat.index', $student))
            ->assertSessionHas('error', 'Riwayat tidak dapat disimpan. Pastikan migration audit tersedia, lalu coba kembali.')
            ->assertDontSee('raw sqlite failure');
        $this->assertDatabaseCount('riwayat_penempatan_siswa', 0);
        $this->assertDatabaseCount('riwayat_penempatan_siswa_audit', 0);
    }

    public function test_write_routes_are_disabled_until_the_audit_migration_is_available(): void
    {
        Schema::drop('riwayat_penempatan_siswa_audit');
        $admin = User::factory()->admin()->create();
        $class = Kelas::factory()->create();
        $student = Siswa::factory()->for($class)->create();
        $period = TahunAjaran::factory()->create();

        $this->actingAs($admin)->get(route('admin.siswa.riwayat.index', $student))
            ->assertOk()
            ->assertSee('Migration audit riwayat penempatan belum diterapkan.')
            ->assertDontSee('Catat Penempatan Baru');

        $this->post(route('admin.siswa.riwayat.store', $student), [
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $period->id,
            'mulai_pada' => '2026-01-01',
            'alasan' => 'Belum boleh disimpan.',
            'referensi_bukti' => 'SK-801',
        ])->assertRedirect(route('admin.siswa.riwayat.index', $student))
            ->assertSessionHas('error', 'Pencatatan riwayat belum tersedia karena migration audit belum diterapkan.');

        $this->assertDatabaseCount('riwayat_penempatan_siswa', 0);
    }

    public function test_placement_id_must_belong_to_the_selected_student(): void
    {
        $admin = User::factory()->admin()->create();
        $firstStudent = Siswa::factory()->create();
        $secondStudent = Siswa::factory()->create();
        $placement = $this->manager()->record($admin, $firstStudent->id, $firstStudent->kelas_id, TahunAjaran::factory()->create()->id, '2026-01-01', null, 'Catatan terpisah', 'SK-901');

        $this->actingAs($admin)->post(route('admin.siswa.riwayat.close', [$secondStudent, $placement]), [
            'berakhir_pada' => '2026-06-30',
            'alasan' => 'Tidak boleh menutup data siswa lain.',
            'referensi_bukti' => 'SK-902',
        ])->assertNotFound();
    }

    public function test_student_list_links_to_placement_history(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Siswa::factory()->create();

        $this->actingAs($admin)->get(route('admin.siswa.index', ['search' => $student->nis]))
            ->assertOk()
            ->assertSee(route('admin.siswa.riwayat.index', $student));
    }

    private function manager(): PlacementHistoryManager
    {
        return new PlacementHistoryManager;
    }
}
