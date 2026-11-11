<?php

namespace Tests\Feature\Guru;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class RekapNilaiControllerTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_recap_displays_task_scores_and_goal_attainment_without_subject_final_grade(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $subject = MataPelajaran::factory()->create(['nama' => 'Matematika Rekap', 'kkm' => 75]);
        $assignment = Pengampu::factory()->for($guru)->create([
            'kelas_id' => $class->id,
            'mata_pelajaran_id' => $subject->id,
        ]);
        $goal = TujuanPembelajaran::factory()->for($assignment)->create(['kktp' => 75]);
        $student = Siswa::factory()->for($class)->create(['nama' => 'Alya Rekap']);
        $firstAssessment = Penilaian::factory()->for($assignment)->create(['tujuan_pembelajaran_id' => $goal->id, 'nama' => 'Tugas Satu', 'urutan' => 1, 'bobot' => null]);
        $secondAssessment = Penilaian::factory()->for($assignment)->create(['tujuan_pembelajaran_id' => $goal->id, 'nama' => 'Tugas Dua', 'urutan' => 2, 'bobot' => null]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $firstAssessment->id, 'nilai' => 80]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $secondAssessment->id, 'nilai' => 60]);

        $response = $this->actingAs($user)->get(route('guru.rekap-nilai.index', ['pengampu_id' => $assignment->id]));

        $response->assertOk()
            ->assertSee('Matematika Rekap')
            ->assertSee('Alya Rekap')
            ->assertSee('Tugas Satu')
            ->assertSee('70,00')
            ->assertSee('Belum mencapai KKTP')
            ->assertSee('Nilai akhir mata pelajaran belum dihitung')
            ->assertViewHas('goalSummaries', fn ($goals): bool => $goals->first()['categories'][Penilaian::KATEGORI_LINGKUP_MATERI]['rows'][$student->id]['score'] === 70.0);
    }

    public function test_recap_does_not_use_task_weights_as_subject_final_weights(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $goal = TujuanPembelajaran::factory()->for($assignment)->create();
        $student = Siswa::factory()->for($class)->create();
        $firstAssessment = Penilaian::factory()->for($assignment)->create(['tujuan_pembelajaran_id' => $goal->id, 'bobot' => 25, 'urutan' => 1]);
        $secondAssessment = Penilaian::factory()->for($assignment)->create(['tujuan_pembelajaran_id' => $goal->id, 'bobot' => 75, 'urutan' => 2]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $firstAssessment->id, 'nilai' => 100]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $secondAssessment->id, 'nilai' => 60]);

        $response = $this->actingAs($user)->get(route('guru.rekap-nilai.index', ['pengampu_id' => $assignment->id]));

        $response->assertOk()
            ->assertSee('80,00')
            ->assertSee('belum menjadi nilai akhir mata pelajaran')
            ->assertViewHas('goalSummaries', fn ($goals): bool => $goals->first()['categories'][Penilaian::KATEGORI_LINGKUP_MATERI]['rows'][$student->id]['score'] === 80.0);
    }

    public function test_guru_cannot_open_another_gurus_recap(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        Pengampu::factory()->for($guru)->create();
        $otherAssignment = Pengampu::factory()->create();

        $response = $this->actingAs($user)->get(route('guru.rekap-nilai.index', ['pengampu_id' => $otherAssignment->id]));

        $response->assertNotFound();
    }
}
