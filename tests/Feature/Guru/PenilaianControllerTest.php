<?php

namespace Tests\Feature\Guru;

use App\Models\Guru;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class PenilaianControllerTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_index_displays_only_authenticated_gurus_assessments(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $ownAssessment = Penilaian::factory()->for(Pengampu::factory()->for($guru))->create(['nama' => 'Penilaian Saya']);
        Penilaian::factory()->create(['nama' => 'Penilaian Guru Lain']);

        $response = $this->actingAs($user)->get(route('guru.penilaian.index', ['pengampu_id' => $ownAssessment->pengampu_id]));

        $response->assertOk()
            ->assertSee('Penilaian Saya')
            ->assertDontSee('Penilaian Guru Lain');
    }

    public function test_index_rejects_another_gurus_assignment_filter(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        Pengampu::factory()->for($guru)->create();
        $otherAssignment = Pengampu::factory()->create();

        $response = $this->actingAs($user)->get(route('guru.penilaian.index', ['pengampu_id' => $otherAssignment->id]));

        $response->assertNotFound();
    }

    public function test_guru_can_create_an_assessment_for_own_assignment(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $assignment = Pengampu::factory()->for($guru)->create();
        $tujuanPembelajaran = TujuanPembelajaran::factory()->for($assignment)->create();

        $response = $this->actingAs($user)->post(route('guru.penilaian.store'), [
            'pengampu_id' => $assignment->id,
            'tujuan_pembelajaran_id' => $tujuanPembelajaran->id,
            'nama' => 'Ulangan Bab Satu',
            'jenis' => 'ulangan_harian',
            'kategori_asesmen' => Penilaian::KATEGORI_LINGKUP_MATERI,
            'tanggal' => '2026-10-08',
            'bobot' => 25,
            'urutan' => 1,
        ]);

        $response->assertRedirectToRoute('guru.penilaian.index', ['pengampu_id' => $assignment->id])
            ->assertSessionHas('success', 'Penilaian berhasil ditambahkan.');
        $this->assertDatabaseHas('penilaian', [
            'pengampu_id' => $assignment->id,
            'tujuan_pembelajaran_id' => $tujuanPembelajaran->id,
            'nama' => 'Ulangan Bab Satu',
            'jenis' => 'ulangan_harian',
        ]);
    }

    public function test_guru_cannot_create_an_assessment_for_another_gurus_assignment(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        Pengampu::factory()->for($guru)->create();
        $otherAssignment = Pengampu::factory()->create();

        $response = $this->actingAs($user)->post(route('guru.penilaian.store'), [
            'pengampu_id' => $otherAssignment->id,
            'nama' => 'Penilaian Ilegal',
            'jenis' => 'tugas',
            'kategori_asesmen' => Penilaian::KATEGORI_LINGKUP_MATERI,
            'tanggal' => '2026-10-08',
            'urutan' => 1,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('penilaian', ['nama' => 'Penilaian Ilegal']);
    }

    public function test_assessment_must_use_a_goal_from_its_own_assignment(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $assignment = Pengampu::factory()->for($guru)->create();
        $otherAssignment = Pengampu::factory()->for($guru)->create();
        $otherGoal = TujuanPembelajaran::factory()->for($otherAssignment)->create();

        $this->actingAs($user)
            ->from(route('guru.penilaian.create', ['pengampu_id' => $assignment->id]))
            ->post(route('guru.penilaian.store'), [
                'pengampu_id' => $assignment->id,
                'tujuan_pembelajaran_id' => $otherGoal->id,
                'nama' => 'Penilaian pada TP yang salah',
                'jenis' => 'tugas',
                'kategori_asesmen' => Penilaian::KATEGORI_PTS,
                'tanggal' => '2026-11-05',
                'urutan' => 1,
            ])
            ->assertRedirect(route('guru.penilaian.create', ['pengampu_id' => $assignment->id]))
            ->assertSessionHasErrors([
                'tujuan_pembelajaran_id' => 'Tujuan pembelajaran harus berasal dari tugas mengajar yang dipilih.',
            ]);

        $this->assertDatabaseMissing('penilaian', ['nama' => 'Penilaian pada TP yang salah']);
    }

    public function test_invalid_assessment_payload_returns_specific_validation_errors(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $assignment = Pengampu::factory()->for($guru)->create();

        $response = $this->actingAs($user)
            ->from(route('guru.penilaian.create'))
            ->post(route('guru.penilaian.store'), [
                'pengampu_id' => $assignment->id,
                'nama' => '',
                'tujuan_pembelajaran_id' => '',
                'jenis' => 'tidak_valid',
                'tanggal' => 'bukan-tanggal',
                'bobot' => 101,
                'urutan' => 0,
            ]);

        $response->assertRedirect(route('guru.penilaian.create'))
            ->assertSessionHasErrors([
                'nama' => 'Nama penilaian wajib diisi.',
                'tujuan_pembelajaran_id' => 'Tujuan pembelajaran wajib dipilih.',
                'jenis' => 'Jenis penilaian tidak valid.',
                'tanggal' => 'Tanggal penilaian tidak valid.',
                'bobot' => 'Bobot maksimal 100.',
                'urutan' => 'Urutan penilaian minimal 1.',
            ]);
    }

    public function test_duplicate_assessment_payload_is_rejected(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $assignment = Pengampu::factory()->for($guru)->create();
        $tujuanPembelajaran = TujuanPembelajaran::factory()->for($assignment)->create();
        $assessment = Penilaian::factory()->for($assignment)->create([
            'nama' => 'Penilaian Sama',
            'jenis' => 'tugas',
            'tanggal' => '2026-10-08',
            'urutan' => 1,
        ]);

        $response = $this->actingAs($user)
            ->from(route('guru.penilaian.create'))
            ->post(route('guru.penilaian.store'), [
                'pengampu_id' => $assignment->id,
                'nama' => $assessment->nama,
                'tujuan_pembelajaran_id' => $tujuanPembelajaran->id,
                'jenis' => $assessment->jenis,
                'kategori_asesmen' => Penilaian::KATEGORI_LINGKUP_MATERI,
                'tanggal' => $assessment->tanggal->toDateString(),
                'urutan' => $assessment->urutan,
            ]);

        $response->assertRedirect(route('guru.penilaian.create'))
            ->assertSessionHasErrors(['nama' => 'Penilaian dengan data yang sama sudah tersedia.']);
        $this->assertSame(1, Penilaian::query()->where('pengampu_id', $assignment->id)->count());
    }

    public function test_guru_can_update_own_assessment_without_changing_assignment(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $assessment = Penilaian::factory()->for(Pengampu::factory()->for($guru))->create();
        $originalAssignmentId = $assessment->pengampu_id;

        $response = $this->actingAs($user)->put(route('guru.penilaian.update', $assessment), [
            'nama' => 'Nama Penilaian Baru',
            'jenis' => 'uts',
            'tanggal' => '2026-10-08',
            'bobot' => null,
            'urutan' => 2,
            'pengampu_id' => Pengampu::factory()->create()->id,
        ]);

        $response->assertRedirectToRoute('guru.penilaian.index', ['pengampu_id' => $originalAssignmentId]);
        $this->assertDatabaseHas('penilaian', [
            'id' => $assessment->id,
            'pengampu_id' => $originalAssignmentId,
            'nama' => 'Nama Penilaian Baru',
            'jenis' => 'uts',
        ]);
    }

    public function test_guru_cannot_update_another_gurus_assessment(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        Pengampu::factory()->for($guru)->create();
        $otherAssessment = Penilaian::factory()->create();

        $response = $this->actingAs($user)->put(route('guru.penilaian.update', $otherAssessment), [
            'nama' => 'Diubah Tanpa Izin',
            'jenis' => 'tugas',
            'tanggal' => '2026-10-08',
            'urutan' => 1,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('penilaian', ['id' => $otherAssessment->id, 'nama' => 'Diubah Tanpa Izin']);
    }

    public function test_assessment_without_scores_can_be_deleted(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $assessment = Penilaian::factory()->for(Pengampu::factory()->for($guru))->create();

        $response = $this->actingAs($user)->delete(route('guru.penilaian.destroy', $assessment));

        $response->assertRedirectToRoute('guru.penilaian.index')
            ->assertSessionHas('success', 'Penilaian berhasil dihapus.');
        $this->assertModelMissing($assessment);
    }

    public function test_assessment_with_scores_cannot_be_deleted(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $assignment = Pengampu::factory()->for($guru)->create();
        $assessment = Penilaian::factory()->for($assignment)->create();
        Nilai::factory()->create([
            'siswa_id' => Siswa::factory()->create(['kelas_id' => $assignment->kelas_id])->id,
            'penilaian_id' => $assessment->id,
        ]);

        $response = $this->actingAs($user)->delete(route('guru.penilaian.destroy', $assessment));

        $response->assertRedirect()
            ->assertSessionHas('error', 'Penilaian tidak dapat dihapus karena sudah memiliki nilai siswa.');
        $this->assertModelExists($assessment);
    }
}
