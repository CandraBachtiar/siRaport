<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class TujuanPembelajaranTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_guru_can_create_a_goal_and_threshold_for_their_assignment(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $assignment = Pengampu::factory()->for($guru)->create();

        $this->actingAs($user)
            ->post(route('guru.tujuan-pembelajaran.store', $assignment), [
                'urutan' => 1,
                'tujuan' => 'Murid menjelaskan hubungan antar konsep.',
                'kktp' => 72.5,
            ])
            ->assertRedirectToRoute('guru.tujuan-pembelajaran.index', $assignment)
            ->assertSessionHas('success', 'Tujuan pembelajaran berhasil ditambahkan.');

        $this->assertDatabaseHas('tujuan_pembelajaran', [
            'pengampu_id' => $assignment->id,
            'urutan' => 1,
            'tujuan' => 'Murid menjelaskan hubungan antar konsep.',
            'kktp' => 72.5,
        ]);
    }

    public function test_guru_can_manage_goals_for_multiple_semester_assignments_independently(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $firstYear = TahunAjaran::factory()->create(['tahun' => '2026/2027', 'semester' => 'Ganjil']);
        $secondYear = TahunAjaran::factory()->create(['tahun' => '2026/2027', 'semester' => 'Genap']);
        $firstAssignment = Pengampu::factory()->for($guru)->create(['tahun_ajaran_id' => $firstYear->id]);
        $secondAssignment = Pengampu::factory()->for($guru)->create([
            'kelas_id' => $firstAssignment->kelas_id,
            'mata_pelajaran_id' => $firstAssignment->mata_pelajaran_id,
            'tahun_ajaran_id' => $secondYear->id,
        ]);

        $firstGoal = TujuanPembelajaran::factory()->for($firstAssignment)->create(['urutan' => 1, 'kktp' => 70]);
        $secondGoal = TujuanPembelajaran::factory()->for($secondAssignment)->create(['urutan' => 1, 'kktp' => 80]);

        $this->assertNotSame($firstGoal->pengampu_id, $secondGoal->pengampu_id);
        $this->assertSame('70.00', $firstGoal->fresh()->kktp);
        $this->assertSame('80.00', $secondGoal->fresh()->kktp);
    }

    public function test_guru_can_update_the_goal_and_threshold_for_their_assignment(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $assignment = Pengampu::factory()->for($guru)->create();
        $goal = TujuanPembelajaran::factory()->for($assignment)->create();

        $this->actingAs($user)
            ->put(route('guru.tujuan-pembelajaran.update', [$assignment, $goal]), [
                'urutan' => 2,
                'tujuan' => 'Murid membandingkan dua strategi penyelesaian.',
                'kktp' => 78.5,
            ])
            ->assertRedirectToRoute('guru.tujuan-pembelajaran.index', $assignment)
            ->assertSessionHas('success', 'Tujuan pembelajaran berhasil diperbarui.');

        $this->assertDatabaseHas('tujuan_pembelajaran', [
            'id' => $goal->id,
            'urutan' => 2,
            'tujuan' => 'Murid membandingkan dua strategi penyelesaian.',
            'kktp' => 78.5,
        ]);
    }

    public function test_guru_cannot_view_or_edit_another_gurus_goal(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        Pengampu::factory()->for($guru)->create();
        $otherAssignment = Pengampu::factory()->create();
        $otherGoal = TujuanPembelajaran::factory()->for($otherAssignment)->create();

        $this->actingAs($user)
            ->get(route('guru.tujuan-pembelajaran.edit', [$otherAssignment, $otherGoal]))
            ->assertForbidden();

        $this->put(route('guru.tujuan-pembelajaran.update', [$otherAssignment, $otherGoal]), [
            'urutan' => 2,
            'tujuan' => 'Perubahan tanpa izin.',
            'kktp' => 75,
        ])->assertForbidden();

        $this->assertDatabaseMissing('tujuan_pembelajaran', [
            'id' => $otherGoal->id,
            'tujuan' => 'Perubahan tanpa izin.',
        ]);
    }

    public function test_goal_id_must_match_the_assignment_in_the_url(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $firstAssignment = Pengampu::factory()->for($guru)->create();
        $secondAssignment = Pengampu::factory()->for($guru)->create();
        $goal = TujuanPembelajaran::factory()->for($secondAssignment)->create();

        $this->actingAs($user)
            ->get(route('guru.tujuan-pembelajaran.edit', [$firstAssignment, $goal]))
            ->assertNotFound();
    }

    public function test_duplicate_goal_order_is_rejected_within_one_assignment(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $assignment = Pengampu::factory()->for($guru)->create();
        TujuanPembelajaran::factory()->for($assignment)->create(['urutan' => 1]);

        $this->actingAs($user)
            ->from(route('guru.tujuan-pembelajaran.create', $assignment))
            ->post(route('guru.tujuan-pembelajaran.store', $assignment), [
                'urutan' => 1,
                'tujuan' => 'Tujuan lainnya.',
                'kktp' => 75,
            ])
            ->assertRedirect(route('guru.tujuan-pembelajaran.create', $assignment))
            ->assertSessionHasErrors([
                'urutan' => 'Nomor TP tersebut sudah digunakan pada tugas mengajar ini.',
            ]);

        $this->assertSame(1, TujuanPembelajaran::query()->where('pengampu_id', $assignment->id)->count());
    }

    public function test_goal_used_by_an_assessment_cannot_be_deleted(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $assignment = Pengampu::factory()->for($guru)->create();
        $goal = TujuanPembelajaran::factory()->for($assignment)->create();
        Penilaian::factory()->for($assignment)->create(['tujuan_pembelajaran_id' => $goal->id]);

        $this->actingAs($user)
            ->delete(route('guru.tujuan-pembelajaran.destroy', [$assignment, $goal]))
            ->assertRedirect()
            ->assertSessionHas('error', 'TP tidak dapat dihapus karena sudah digunakan oleh penilaian.');

        $this->assertModelExists($goal);
    }

    public function test_legacy_assessment_without_known_goal_remains_unmapped_when_edited(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $assignment = Pengampu::factory()->for($guru)->create();
        $assessment = Penilaian::factory()->for($assignment)->create([
            'tujuan_pembelajaran_id' => null,
        ]);

        $this->actingAs($user)
            ->put(route('guru.penilaian.update', $assessment), [
                'nama' => 'Nama Penilaian Diperbarui',
                'jenis' => 'tugas',
                'tanggal' => '2026-11-05',
                'urutan' => 2,
            ])
            ->assertRedirectToRoute('guru.penilaian.index', ['pengampu_id' => $assignment->id]);

        $this->assertDatabaseHas('penilaian', [
            'id' => $assessment->id,
            'tujuan_pembelajaran_id' => null,
            'nama' => 'Nama Penilaian Diperbarui',
        ]);
    }

    public function test_a_mapped_assessment_with_saved_scores_cannot_be_moved_to_another_goal(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $assignment = Pengampu::factory()->for($guru)->create();
        $firstGoal = TujuanPembelajaran::factory()->for($assignment)->create(['urutan' => 1]);
        $secondGoal = TujuanPembelajaran::factory()->for($assignment)->create(['urutan' => 2]);
        $assessment = Penilaian::factory()->for($assignment)->create([
            'tujuan_pembelajaran_id' => $firstGoal->id,
        ]);
        $student = Siswa::factory()->create(['kelas_id' => $assignment->kelas_id]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessment->id]);

        $this->actingAs($user)
            ->from(route('guru.penilaian.edit', $assessment))
            ->put(route('guru.penilaian.update', $assessment), [
                'nama' => $assessment->nama,
                'tujuan_pembelajaran_id' => $secondGoal->id,
                'jenis' => $assessment->jenis,
                'tanggal' => $assessment->tanggal->toDateString(),
                'urutan' => $assessment->urutan + 1,
            ])
            ->assertRedirect(route('guru.penilaian.edit', $assessment))
            ->assertSessionHasErrors([
                'tujuan_pembelajaran_id' => 'TP penilaian tidak dapat diubah setelah nilai siswa tersimpan.',
            ]);

        $this->assertDatabaseHas('penilaian', [
            'id' => $assessment->id,
            'tujuan_pembelajaran_id' => $firstGoal->id,
        ]);
    }
}
