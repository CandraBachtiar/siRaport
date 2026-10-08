<?php

namespace Tests\Feature\Guru;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\User;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class InputNilaiControllerTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_input_page_displays_students_existing_scores_and_status(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $assessment = Penilaian::factory()->for($assignment)->create(['nama' => 'Tugas Tampilan']);
        $student = Siswa::factory()->for($class)->create(['nama' => 'Siswa Tampilan']);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessment->id, 'nilai' => 65]);

        $response = $this->actingAs($user)->get(route('guru.nilai.index', [
            'tahun_ajaran_id' => $assignment->tahun_ajaran_id,
            'mata_pelajaran_id' => $assignment->mata_pelajaran_id,
            'kelas_id' => $assignment->kelas_id,
            'penilaian_id' => $assessment->id,
        ]));

        $response->assertOk()
            ->assertSee('Tugas Tampilan')
            ->assertSee('Siswa Tampilan')
            ->assertSee('65.00')
            ->assertSee('Di bawah KKM');
    }

    public function test_guru_can_save_scores_for_students_in_own_assignment_class(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $assessment = Penilaian::factory()->for($assignment)->create();
        $students = Siswa::factory()->count(2)->for($class)->create();

        $response = $this->actingAs($user)->put(route('guru.nilai.update', $assessment), [
            'nilai' => [
                $students[0]->id => 0,
                $students[1]->id => 87.5,
            ],
        ]);

        $response->assertRedirect()
            ->assertSessionHas('success', 'Nilai berhasil disimpan.');
        $this->assertDatabaseHas('nilai', ['siswa_id' => $students[0]->id, 'penilaian_id' => $assessment->id, 'nilai' => 0]);
        $this->assertDatabaseHas('nilai', ['siswa_id' => $students[1]->id, 'penilaian_id' => $assessment->id, 'nilai' => 87.5]);
    }

    public function test_unexpected_student_key_is_ignored_when_saving_scores(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $otherClass = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $assessment = Penilaian::factory()->for($assignment)->create();
        $ownStudent = Siswa::factory()->for($class)->create();
        $otherStudent = Siswa::factory()->for($otherClass)->create();

        $response = $this->actingAs($user)->put(route('guru.nilai.update', $assessment), [
            'nilai' => [
                $ownStudent->id => 90,
                $otherStudent->id => 100,
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('nilai', ['siswa_id' => $ownStudent->id, 'penilaian_id' => $assessment->id, 'nilai' => 90]);
        $this->assertDatabaseMissing('nilai', ['siswa_id' => $otherStudent->id, 'penilaian_id' => $assessment->id]);
    }

    public function test_score_above_one_hundred_is_rejected(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assessment = Penilaian::factory()->for(Pengampu::factory()->for($guru)->state(['kelas_id' => $class->id]))->create();
        $student = Siswa::factory()->for($class)->create();

        $response = $this->actingAs($user)
            ->from(route('guru.nilai.index'))
            ->put(route('guru.nilai.update', $assessment), ['nilai' => [$student->id => 101]]);

        $response->assertRedirect(route('guru.nilai.index'))
            ->assertSessionHasErrors(['nilai.'.$student->id => 'Nilai siswa maksimal 100.']);
        $this->assertDatabaseMissing('nilai', ['siswa_id' => $student->id, 'penilaian_id' => $assessment->id]);
    }

    public function test_invalid_score_is_shown_beside_the_related_student_input(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $assessment = Penilaian::factory()->for($assignment)->create();
        $student = Siswa::factory()->for($class)->create(['nama' => 'Siswa Invalid']);

        $inputUrl = route('guru.nilai.index', [
            'tahun_ajaran_id' => $assignment->tahun_ajaran_id,
            'mata_pelajaran_id' => $assignment->mata_pelajaran_id,
            'kelas_id' => $assignment->kelas_id,
            'penilaian_id' => $assessment->id,
        ]);

        $response = $this->actingAs($user)
            ->from($inputUrl)
            ->followingRedirects()
            ->put(route('guru.nilai.update', $assessment), [
                'nilai' => [$student->id => 101],
            ]);

        $response->assertOk()
            ->assertSee('Nilai siswa maksimal 100.')
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="score-error-'.$student->id.'"', false);
    }

    public function test_guru_cannot_update_scores_for_another_gurus_assessment(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        Pengampu::factory()->for($guru)->create();
        $otherAssessment = Penilaian::factory()->create();
        $student = Siswa::factory()->create();

        $response = $this->actingAs($user)->put(route('guru.nilai.update', $otherAssessment), [
            'nilai' => [$student->id => 88],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('nilai', ['siswa_id' => $student->id, 'penilaian_id' => $otherAssessment->id]);
    }

    public function test_input_page_rejects_another_gurus_assessment_filter(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $ownAssignment = Pengampu::factory()->for($guru)->create();
        $otherAssessment = Penilaian::factory()->create([
            'pengampu_id' => Pengampu::factory()->create([
                'tahun_ajaran_id' => $ownAssignment->tahun_ajaran_id,
                'mata_pelajaran_id' => $ownAssignment->mata_pelajaran_id,
                'kelas_id' => $ownAssignment->kelas_id,
            ])->id,
        ]);

        $response = $this->actingAs($user)->get(route('guru.nilai.index', [
            'tahun_ajaran_id' => $ownAssignment->tahun_ajaran_id,
            'mata_pelajaran_id' => $ownAssignment->mata_pelajaran_id,
            'kelas_id' => $ownAssignment->kelas_id,
            'penilaian_id' => $otherAssessment->id,
        ]));

        $response->assertNotFound();
    }
}
