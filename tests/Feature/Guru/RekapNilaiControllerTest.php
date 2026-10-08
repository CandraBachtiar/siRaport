<?php

namespace Tests\Feature\Guru;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
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

    public function test_recap_displays_assessment_scores_average_and_kkm_status(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $subject = MataPelajaran::factory()->create(['nama' => 'Matematika Rekap', 'kkm' => 75]);
        $assignment = Pengampu::factory()->for($guru)->create([
            'kelas_id' => $class->id,
            'mata_pelajaran_id' => $subject->id,
        ]);
        $student = Siswa::factory()->for($class)->create(['nama' => 'Alya Rekap']);
        $firstAssessment = Penilaian::factory()->for($assignment)->create(['nama' => 'Tugas Satu', 'urutan' => 1, 'bobot' => null]);
        $secondAssessment = Penilaian::factory()->for($assignment)->create(['nama' => 'Tugas Dua', 'urutan' => 2, 'bobot' => null]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $firstAssessment->id, 'nilai' => 80]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $secondAssessment->id, 'nilai' => 60]);

        $response = $this->actingAs($user)->get(route('guru.rekap-nilai.index', ['pengampu_id' => $assignment->id]));

        $response->assertOk()
            ->assertSee('Matematika Rekap')
            ->assertSee('Alya Rekap')
            ->assertSee('Tugas Satu')
            ->assertSee('70,00')
            ->assertSee('Di bawah KKM')
            ->assertViewHas('calculationMode', 'Rata-rata sederhana');
    }

    public function test_recap_uses_weights_when_every_assessment_has_a_weight(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $student = Siswa::factory()->for($class)->create();
        $firstAssessment = Penilaian::factory()->for($assignment)->create(['bobot' => 25, 'urutan' => 1]);
        $secondAssessment = Penilaian::factory()->for($assignment)->create(['bobot' => 75, 'urutan' => 2]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $firstAssessment->id, 'nilai' => 100]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $secondAssessment->id, 'nilai' => 60]);

        $response = $this->actingAs($user)->get(route('guru.rekap-nilai.index', ['pengampu_id' => $assignment->id]));

        $response->assertOk()
            ->assertSee('70,00')
            ->assertViewHas('calculationMode', 'Rata-rata berbobot');
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
