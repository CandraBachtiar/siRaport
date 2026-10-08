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

class AnalisisNilaiControllerTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_class_analysis_uses_real_scores_and_kkm(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $subject = MataPelajaran::factory()->create(['nama' => 'Matematika Analisis', 'kkm' => 75]);
        $assignment = Pengampu::factory()->for($guru)->create([
            'kelas_id' => $class->id,
            'mata_pelajaran_id' => $subject->id,
        ]);
        $students = Siswa::factory()->count(2)->for($class)->create();
        $firstAssessment = Penilaian::factory()->for($assignment)->create(['nama' => 'Penilaian Satu', 'urutan' => 1]);
        $secondAssessment = Penilaian::factory()->for($assignment)->create(['nama' => 'Penilaian Dua', 'urutan' => 2]);
        Nilai::factory()->create(['siswa_id' => $students[0]->id, 'penilaian_id' => $firstAssessment->id, 'nilai' => 80]);
        Nilai::factory()->create(['siswa_id' => $students[1]->id, 'penilaian_id' => $firstAssessment->id, 'nilai' => 60]);
        Nilai::factory()->create(['siswa_id' => $students[0]->id, 'penilaian_id' => $secondAssessment->id, 'nilai' => 100]);
        Nilai::factory()->create(['siswa_id' => $students[1]->id, 'penilaian_id' => $secondAssessment->id, 'nilai' => 80]);

        $response = $this->actingAs($user)->get(route('guru.analisis.index', [
            'tahun_ajaran_id' => $assignment->tahun_ajaran_id,
            'mata_pelajaran_id' => $assignment->mata_pelajaran_id,
            'kelas_id' => $assignment->kelas_id,
        ]));

        $response->assertOk()
            ->assertSee('Matematika Analisis')
            ->assertSee('Penilaian Satu')
            ->assertSee('Penilaian Dua')
            ->assertViewHas('classStats', fn (array $stats): bool => $stats['average'] === 80.0
                && $stats['highest'] === 100.0
                && $stats['lowest'] === 60.0
                && $stats['belowKkmCount'] === 1
                && $stats['meetsKkmCount'] === 1);
    }

    public function test_student_analysis_explains_an_increasing_trend(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $student = Siswa::factory()->for($class)->create(['nama' => 'Alya Meningkat']);
        $scores = [60, 65, 80, 85];

        foreach ($scores as $index => $score) {
            $assessment = Penilaian::factory()->for($assignment)->create([
                'nama' => 'Penilaian '.($index + 1),
                'urutan' => $index + 1,
                'tanggal' => now()->addDays($index),
            ]);
            Nilai::factory()->create([
                'siswa_id' => $student->id,
                'penilaian_id' => $assessment->id,
                'nilai' => $score,
            ]);
        }

        $response = $this->actingAs($user)->get(route('guru.analisis.index', [
            'pengampu_id' => $assignment->id,
            'siswa_id' => $student->id,
        ]));

        $response->assertOk()
            ->assertSee('Alya Meningkat')
            ->assertSee('Tren Meningkat')
            ->assertSee('Berdasarkan perkembangan nilai')
            ->assertViewHas('studentAnalysis', fn (array $analysis): bool => $analysis['trend']['label'] === 'Meningkat'
                && $analysis['trend']['difference'] === 20.0);
    }

    public function test_guru_cannot_analyze_another_gurus_assignment(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        Pengampu::factory()->for($guru)->create();
        $otherAssignment = Pengampu::factory()->create();

        $response = $this->actingAs($user)->get(route('guru.analisis.index', ['pengampu_id' => $otherAssignment->id]));

        $response->assertNotFound();
    }

    public function test_guru_cannot_analyze_a_student_outside_the_selected_class(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $assignment = Pengampu::factory()->for($guru)->create();
        $otherStudent = Siswa::factory()->create();

        $response = $this->actingAs($user)->get(route('guru.analisis.index', [
            'pengampu_id' => $assignment->id,
            'siswa_id' => $otherStudent->id,
        ]));

        $response->assertNotFound();
    }
}
