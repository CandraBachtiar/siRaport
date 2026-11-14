<?php

namespace Tests\Feature\Guru;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\TahunAjaran;
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
            ->assertSee('Tugas Mengajar dan Periode')
            ->assertSee('Tugas Satu')
            ->assertSee('70,00')
            ->assertSee('Belum mencapai KKTP')
            ->assertSee('Nilai akhir mata pelajaran belum dihitung')
            ->assertSee('Belum dihitung')
            ->assertViewHas('simulations', fn ($simulations): bool => $simulations[$student->id]['status'] === 'belum_ditetapkan'
                && $simulations[$student->id]['nilai'] === null)
            ->assertViewHas('goalSummaries', fn ($goals): bool => $goals->first()['categories'][Penilaian::KATEGORI_LINGKUP_MATERI]['rows'][$student->id]['score'] === 70.0);
    }

    public function test_recap_simulation_is_limited_to_the_selected_school_year_and_semester(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $subject = MataPelajaran::factory()->create();
        $ganjil = TahunAjaran::factory()->create(['tahun' => '2097/2098', 'semester' => 'Ganjil']);
        $genap = TahunAjaran::factory()->create(['tahun' => '2097/2098', 'semester' => 'Genap']);
        $firstAssignment = Pengampu::factory()->for($guru)->create([
            'kelas_id' => $class->id,
            'mata_pelajaran_id' => $subject->id,
            'tahun_ajaran_id' => $ganjil->id,
        ]);
        $secondAssignment = Pengampu::factory()->for($guru)->create([
            'kelas_id' => $class->id,
            'mata_pelajaran_id' => $subject->id,
            'tahun_ajaran_id' => $genap->id,
        ]);
        $firstGoal = TujuanPembelajaran::factory()->for($firstAssignment)->create(['urutan' => 1]);
        $secondGoal = TujuanPembelajaran::factory()->for($secondAssignment)->create(['urutan' => 1]);
        $firstAssessment = $this->assessmentForGoal($firstAssignment, $firstGoal, Penilaian::KATEGORI_LINGKUP_MATERI, 'Sumatif Ganjil', 1);
        $secondAssessment = $this->assessmentForGoal($secondAssignment, $secondGoal, Penilaian::KATEGORI_LINGKUP_MATERI, 'Sumatif Genap', 1);
        $student = Siswa::factory()->for($class)->create();
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $firstAssessment->id, 'nilai' => 60]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $secondAssessment->id, 'nilai' => 90]);

        $response = $this->actingAs($user)->get(route('guru.rekap-nilai.index', [
            'pengampu_id' => $firstAssignment->id,
            'metode_simulasi' => 'rata_rata',
        ]));

        $response->assertOk()
            ->assertSee('Ganjil')
            ->assertViewHas('assessments', fn ($assessments): bool => $assessments->pluck('id')->all() === [$firstAssessment->id])
            ->assertViewHas('simulations', fn ($simulations): bool => $simulations[$student->id]['nilai'] === 60.0);
    }

    public function test_recap_without_goals_explains_the_next_step_and_disables_simulation(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        Siswa::factory()->for($class)->create();
        Penilaian::factory()->for($assignment)->create(['tujuan_pembelajaran_id' => null]);

        $response = $this->actingAs($user)->get(route('guru.rekap-nilai.index', ['pengampu_id' => $assignment->id]));

        $response->assertOk()
            ->assertSee('Belum ada TP untuk rekap.')
            ->assertSee('Siapkan TP')
            ->assertSee('Simulasi belum dapat dijalankan.')

            ->assertSee('name="metode_simulasi" class="rk-field" disabled', false)
            ->assertSee('Hitung Simulasi', false);
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

    public function test_average_simulation_averages_each_tp_once_and_excludes_formative_scores(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $student = Siswa::factory()->for($class)->create(['nama' => 'Murid Simulasi']);
        $goals = collect([
            TujuanPembelajaran::factory()->for($assignment)->create(['urutan' => 1, 'metode_rekap' => TujuanPembelajaran::METODE_RATA_RATA]),
            TujuanPembelajaran::factory()->for($assignment)->create(['urutan' => 2, 'metode_rekap' => TujuanPembelajaran::METODE_RATA_RATA]),
            TujuanPembelajaran::factory()->for($assignment)->create(['urutan' => 3, 'metode_rekap' => TujuanPembelajaran::METODE_RATA_RATA]),
        ]);
        $assessments = collect([
            $this->assessmentForGoal($assignment, $goals[0], Penilaian::KATEGORI_LINGKUP_MATERI, 'Sumatif TP 1a', 1),
            $this->assessmentForGoal($assignment, $goals[0], Penilaian::KATEGORI_LINGKUP_MATERI, 'Sumatif TP 1b', 2),
            $this->assessmentForGoal($assignment, $goals[0], Penilaian::KATEGORI_FORMATIF, 'Umpan balik TP 1', 3),
            $this->assessmentForGoal($assignment, $goals[1], Penilaian::KATEGORI_LINGKUP_MATERI, 'Sumatif TP 2', 4),
            $this->assessmentForGoal($assignment, $goals[2], Penilaian::KATEGORI_LINGKUP_MATERI, 'Sumatif TP 3', 5),
        ]);
        foreach ([50, 70, 100, 90, 90] as $index => $score) {
            Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessments[$index]->id, 'nilai' => $score]);
        }

        $response = $this->actingAs($user)->get(route('guru.rekap-nilai.index', [
            'pengampu_id' => $assignment->id,
            'metode_simulasi' => 'rata_rata',
        ]));

        $response->assertOk()
            ->assertSee('SIMULASI')
            ->assertSee('name="metode_simulasi" class="rk-field"', false)
            ->assertDontSee('name="metode_simulasi" class="rk-field" disabled', false)
            ->assertSee('Rincian sumber')
            ->assertViewHas('simulations', fn ($simulations): bool => $simulations[$student->id]['nilai'] === 80.0
                && array_column($simulations[$student->id]['components']->all(), 'score') === [60.0, 90.0, 90.0]);
        $this->assertDatabaseCount('nilai', 5);
    }

    public function test_weighted_simulation_uses_tp_weights_only_when_they_total_one_hundred_percent(): void
    {
        [$user, $assignment, $student, $goals] = $this->completeSimulationContext([60, 90]);

        $valid = $this->actingAs($user)->get(route('guru.rekap-nilai.index', [
            'pengampu_id' => $assignment->id,
            'metode_simulasi' => 'pembobotan',
            'bobot_tp' => [$goals[0]->id => 25, $goals[1]->id => 75],
        ]));

        $valid->assertOk()
            ->assertViewHas('simulations', fn ($simulations): bool => $simulations[$student->id]['nilai'] === 82.5);

        $invalid = $this->actingAs($user)->get(route('guru.rekap-nilai.index', [
            'pengampu_id' => $assignment->id,
            'metode_simulasi' => 'pembobotan',
            'bobot_tp' => [$goals[0]->id => 50, $goals[1]->id => 40],
        ]));

        $invalid->assertOk()
            ->assertSee('Jumlah bobot seluruh TP harus tepat 100%.')
            ->assertViewHas('simulations', fn ($simulations): bool => $simulations[$student->id]['status'] === 'konfigurasi_tidak_valid'
                && $simulations[$student->id]['nilai'] === null);
    }

    public function test_percentage_simulation_counts_attained_goals_instead_of_averaging_scores(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $student = Siswa::factory()->for($class)->create();
        $scores = [60, 69, 80];

        foreach ($scores as $index => $score) {
            $goal = TujuanPembelajaran::factory()->for($assignment)->create([
                'urutan' => $index + 1,
                'kktp' => [60, 70, 70][$index],
                'metode_rekap' => TujuanPembelajaran::METODE_RATA_RATA,
            ]);
            $assessment = $this->assessmentForGoal($assignment, $goal, Penilaian::KATEGORI_LINGKUP_MATERI, 'Sumatif '.$index, $index + 1);
            Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessment->id, 'nilai' => $score]);
        }

        $response = $this->actingAs($user)->get(route('guru.rekap-nilai.index', [
            'pengampu_id' => $assignment->id,
            'metode_simulasi' => 'persentase',
        ]));

        $response->assertOk()
            ->assertSee('66,67')
            ->assertViewHas('simulations', fn ($simulations): bool => $simulations[$student->id]['nilai'] === 66.67);
    }

    public function test_zero_is_valid_but_a_missing_sumative_score_blocks_final_simulation(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $goal = TujuanPembelajaran::factory()->for($assignment)->create(['urutan' => 1, 'metode_rekap' => TujuanPembelajaran::METODE_RATA_RATA]);
        $student = Siswa::factory()->for($class)->create();
        $zero = $this->assessmentForGoal($assignment, $goal, Penilaian::KATEGORI_LINGKUP_MATERI, 'Nilai nol', 1);
        $missing = $this->assessmentForGoal($assignment, $goal, Penilaian::KATEGORI_LINGKUP_MATERI, 'Nilai kosong', 2);
        $formative = $this->assessmentForGoal($assignment, $goal, Penilaian::KATEGORI_FORMATIF, 'Formatif', 3);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $zero->id, 'nilai' => 0]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $formative->id, 'nilai' => 100]);

        $response = $this->actingAs($user)->get(route('guru.rekap-nilai.index', [
            'pengampu_id' => $assignment->id,
            'metode_simulasi' => 'rata_rata',
        ]));

        $response->assertOk()
            ->assertSee('Belum siap')
            ->assertSee('Nilai sumatif TP 1 belum lengkap')
            ->assertSee('Simulasi belum dapat dijalankan.')
            ->assertSee('name="metode_simulasi" class="rk-field" disabled', false)
            ->assertViewHas('goalSummaries', fn ($summaries): bool => $summaries[0]['categories'][Penilaian::KATEGORI_LINGKUP_MATERI]['rows'][$student->id]['score'] === 0.0
                && $summaries[0]['categories'][Penilaian::KATEGORI_LINGKUP_MATERI]['rows'][$student->id]['missingCount'] === 1);
    }

    public function test_multiple_sumative_categories_on_one_goal_block_simulation_instead_of_double_counting(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $goal = TujuanPembelajaran::factory()->for($assignment)->create(['urutan' => 1, 'metode_rekap' => TujuanPembelajaran::METODE_RATA_RATA]);
        $student = Siswa::factory()->for($class)->create();
        foreach ([Penilaian::KATEGORI_LINGKUP_MATERI, Penilaian::KATEGORI_PTS] as $index => $category) {
            $assessment = $this->assessmentForGoal($assignment, $goal, $category, 'Kategori '.$index, $index + 1);
            Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessment->id, 'nilai' => 80 + $index]);
        }

        $response = $this->actingAs($user)->get(route('guru.rekap-nilai.index', [
            'pengampu_id' => $assignment->id,
            'metode_simulasi' => 'rata_rata',
        ]));

        $response->assertOk()
            ->assertSee('TP 1 memiliki lebih dari satu kategori sumatif')
            ->assertViewHas('simulations', fn ($simulations): bool => $simulations[$student->id]['nilai'] === null);
    }

    public function test_unmapped_assessment_blocks_simulation_and_score_changes_are_recalculated(): void
    {
        [$user, $assignment, $student, $goals, $assessment] = $this->completeSimulationContext([60]);
        $unmappedAssessment = Penilaian::factory()->for($assignment)->create([
            'tujuan_pembelajaran_id' => null,
            'kategori_asesmen' => Penilaian::KATEGORI_LINGKUP_MATERI,
            'urutan' => 2,
        ]);
        $blocked = $this->actingAs($user)->get(route('guru.rekap-nilai.index', [
            'pengampu_id' => $assignment->id,
            'metode_simulasi' => 'rata_rata',
        ]));
        $blocked->assertOk()->assertSee('Ada penilaian yang belum dipetakan ke TP.');

        $unmappedAssessment->delete();
        $firstSimulation = $this->actingAs($user)->get(route('guru.rekap-nilai.index', [
            'pengampu_id' => $assignment->id,
            'metode_simulasi' => 'rata_rata',
        ]));
        $firstSimulation->assertViewHas('simulations', fn ($simulations): bool => $simulations[$student->id]['nilai'] === 60.0);

        Nilai::query()->where('penilaian_id', $assessment->id)->where('siswa_id', $student->id)->update(['nilai' => 80]);
        $updatedSimulation = $this->actingAs($user)->get(route('guru.rekap-nilai.index', [
            'pengampu_id' => $assignment->id,
            'metode_simulasi' => 'rata_rata',
        ]));
        $updatedSimulation->assertViewHas('simulations', fn ($simulations): bool => $simulations[$student->id]['nilai'] === 80.0);
        $this->assertDatabaseCount('nilai', 1);
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

    /**
     * @param  array<int, int|float>  $scores
     * @return array{User, Pengampu, Siswa, array<int, TujuanPembelajaran>, Penilaian|null}
     */
    private function completeSimulationContext(array $scores): array
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $student = Siswa::factory()->for($class)->create();
        $goals = [];
        $lastAssessment = null;

        foreach ($scores as $index => $score) {
            $goal = TujuanPembelajaran::factory()->for($assignment)->create([
                'urutan' => $index + 1,
                'metode_rekap' => TujuanPembelajaran::METODE_RATA_RATA,
            ]);
            $assessment = $this->assessmentForGoal($assignment, $goal, Penilaian::KATEGORI_LINGKUP_MATERI, 'Sumatif TP '.($index + 1), $index + 1);
            Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessment->id, 'nilai' => $score]);
            $goals[] = $goal;
            $lastAssessment = $assessment;
        }

        return [$user, $assignment, $student, $goals, $lastAssessment];
    }

    private function assessmentForGoal(
        Pengampu $assignment,
        TujuanPembelajaran $goal,
        string $category,
        string $name,
        int $order,
    ): Penilaian {
        return Penilaian::factory()->for($assignment)->create([
            'tujuan_pembelajaran_id' => $goal->id,
            'kategori_asesmen' => $category,
            'nama' => $name,
            'urutan' => $order,
        ]);
    }
}
