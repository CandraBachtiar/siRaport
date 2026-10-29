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
            ->assertSee('2/2 siswa dinilai')
            ->assertSee('data-tooltip-value-label="Rata-rata Kelas"', false)
            ->assertSee('data-tooltip-subject="Matematika Analisis"', false)
            ->assertSee('data-tooltip-assessment="Penilaian Satu"', false)
            ->assertSee('data-chart-point data-tooltip-subject="Matematika Analisis"', false)
            ->assertSee('data-tooltip-score="70"', false)
            ->assertSee('data-tooltip-count="2"', false)
            ->assertSee('aria-label="Grafik rata-rata kelas per penilaian"', false)
            ->assertDontSee('<title>Grafik rata-rata kelas per penilaian</title>', false)
            ->assertSee(route('guru.nilai.index', [
                'tahun_ajaran_id' => $assignment->tahun_ajaran_id,
                'mata_pelajaran_id' => $assignment->mata_pelajaran_id,
                'kelas_id' => $assignment->kelas_id,
                'penilaian_id' => $firstAssessment->id,
            ]))
            ->assertSee('aria-label="Grafik distribusi rata-rata siswa: 0–59: 0; 60–69: 0; 70–79: 1; 80–89: 0; 90–100: 1"', false)
            ->assertViewHas('classStats', fn (array $stats): bool => $stats['average'] === 80.0
                && $stats['highest'] === 100.0
                && $stats['lowest'] === 60.0
                && $stats['belowKkmCount'] === 1
                && $stats['meetsKkmCount'] === 1);

        $detailResponse = $this->actingAs($user)->get(route('guru.nilai.index', [
            'tahun_ajaran_id' => $assignment->tahun_ajaran_id,
            'mata_pelajaran_id' => $assignment->mata_pelajaran_id,
            'kelas_id' => $assignment->kelas_id,
            'penilaian_id' => $firstAssessment->id,
        ]));

        $detailResponse->assertOk()
            ->assertSee($students[0]->nama)
            ->assertSee($students[1]->nama)
            ->assertSee('80.00')
            ->assertSee('60.00')
            ->assertSee('Di bawah KKM');
    }

    public function test_class_analysis_shows_unavailable_assessments_with_zero_marked_count_and_detail_link(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        Siswa::factory()->count(2)->for($class)->create();
        $assessment = Penilaian::factory()->for($assignment)->create(['nama' => 'Tugas Belum Dinilai']);

        $response = $this->actingAs($user)->get(route('guru.analisis.index', [
            'pengampu_id' => $assignment->id,
        ]));

        $response->assertOk()
            ->assertSee('Belum ada nilai tersimpan untuk penilaian pada kelas dan mata pelajaran ini.')
            ->assertSee('Tugas Belum Dinilai')
            ->assertSee('0/2 siswa dinilai')
            ->assertSee('Belum tersedia')
            ->assertSee(route('guru.nilai.index', [
                'tahun_ajaran_id' => $assignment->tahun_ajaran_id,
                'mata_pelajaran_id' => $assignment->mata_pelajaran_id,
                'kelas_id' => $assignment->kelas_id,
                'penilaian_id' => $assessment->id,
            ]));
    }

    public function test_student_analysis_explains_an_increasing_trend(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $subject = MataPelajaran::factory()->create(['nama' => 'Matematika Individu']);
        $assignment = Pengampu::factory()->for($guru)->create([
            'kelas_id' => $class->id,
            'mata_pelajaran_id' => $subject->id,
        ]);
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
            ->assertSee('data-tooltip-subject="Matematika Individu"', false)
            ->assertSee('data-tooltip-value-label="Nilai Siswa"', false)
            ->assertDontSee('<title>Grafik perkembangan nilai Alya Meningkat</title>', false)
            ->assertViewHas('studentAnalysis', fn (array $analysis): bool => $analysis['trend']['label'] === 'Meningkat'
                && $analysis['trend']['difference'] === 20.0);
    }

    public function test_student_analysis_reports_a_stable_trend_within_the_existing_threshold(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $student = Siswa::factory()->for($class)->create();

        foreach ([70, 71, 72, 70] as $index => $score) {
            $assessment = Penilaian::factory()->for($assignment)->create([
                'urutan' => $index + 1,
                'tanggal' => '2026-10-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
            ]);
            Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessment->id, 'nilai' => $score]);
        }

        $response = $this->actingAs($user)->get(route('guru.analisis.index', [
            'pengampu_id' => $assignment->id,
            'siswa_id' => $student->id,
        ]));

        $response->assertViewHas('studentAnalysis', fn (array $analysis): bool => $analysis['trend']['label'] === 'Stabil'
            && $analysis['trend']['difference'] === 0.5);
    }

    public function test_student_analysis_reports_no_data_without_inventing_a_trend(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $student = Siswa::factory()->for($class)->create();
        $assessment = Penilaian::factory()->for($assignment)->create(['tanggal' => '2026-10-01', 'urutan' => 1]);
        $deletedScore = Nilai::factory()->create([
            'siswa_id' => $student->id,
            'penilaian_id' => $assessment->id,
            'nilai' => 80,
        ]);
        $deletedScore->delete();

        $response = $this->actingAs($user)->get(route('guru.analisis.index', [
            'pengampu_id' => $assignment->id,
            'siswa_id' => $student->id,
        ]));

        $response->assertSee('Belum ada data yang dapat digambarkan.')
            ->assertViewHas('studentAnalysis', fn (array $analysis): bool => $analysis['average'] === null
            && $analysis['trend']['label'] === 'Belum ada data');
    }

    public function test_zero_is_valid_and_deleted_or_missing_scores_do_not_enter_the_trend(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $student = Siswa::factory()->for($class)->create();
        $assessments = collect([
            Penilaian::factory()->for($assignment)->create(['tanggal' => '2026-10-01', 'urutan' => 1]),
            Penilaian::factory()->for($assignment)->create(['tanggal' => '2026-10-02', 'urutan' => 2]),
            Penilaian::factory()->for($assignment)->create(['tanggal' => '2026-10-03', 'urutan' => 3]),
        ]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessments[0]->id, 'nilai' => 0]);
        $deletedScore = Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessments[1]->id, 'nilai' => 90]);
        $deletedScore->delete();

        $response = $this->actingAs($user)->get(route('guru.analisis.index', [
            'pengampu_id' => $assignment->id,
            'siswa_id' => $student->id,
        ]));

        $response->assertSee('0,0')
            ->assertSee('—')
            ->assertDontSee('90,0')
            ->assertViewHas('studentAnalysis', fn (array $analysis): bool => $analysis['average'] === 0.0
            && $analysis['latest'] === 0.0
            && $analysis['missingCount'] === 2
            && $analysis['series']->pluck('value')->all() === [0.0, null, null]
            && $analysis['trend']['label'] === 'Belum cukup data');
    }

    public function test_student_analysis_reports_insufficient_data_for_one_score(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $student = Siswa::factory()->for($class)->create();
        $assessment = Penilaian::factory()->for($assignment)->create(['tanggal' => '2026-10-01', 'urutan' => 1]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessment->id, 'nilai' => 80]);

        $response = $this->actingAs($user)->get(route('guru.analisis.index', [
            'pengampu_id' => $assignment->id,
            'siswa_id' => $student->id,
        ]));

        $response->assertViewHas('studentAnalysis', fn (array $analysis): bool => $analysis['trend']['label'] === 'Belum cukup data');
    }

    public function test_student_analysis_does_not_classify_a_trend_from_three_scores(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $student = Siswa::factory()->for($class)->create();

        foreach ([90, 20, 70] as $index => $score) {
            $assessment = Penilaian::factory()->for($assignment)->create([
                'urutan' => $index + 1,
                'tanggal' => '2026-10-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
            ]);
            Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessment->id, 'nilai' => $score]);
        }

        $response = $this->actingAs($user)->get(route('guru.analisis.index', [
            'pengampu_id' => $assignment->id,
            'siswa_id' => $student->id,
        ]));

        $response->assertViewHas('studentAnalysis', fn (array $analysis): bool => $analysis['trend']['label'] === 'Belum cukup data'
            && $analysis['trend']['difference'] === null);
    }

    public function test_student_analysis_orders_assessments_by_date_before_calculating_a_trend(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $student = Siswa::factory()->for($class)->create();
        foreach ([
            ['tanggal' => '2026-10-04', 'urutan' => 1, 'nilai' => 85],
            ['tanggal' => '2026-10-03', 'urutan' => 2, 'nilai' => 80],
            ['tanggal' => '2026-10-02', 'urutan' => 3, 'nilai' => 65],
            ['tanggal' => '2026-10-01', 'urutan' => 4, 'nilai' => 60],
        ] as $scoreData) {
            $assessment = Penilaian::factory()->for($assignment)->create([
                'tanggal' => $scoreData['tanggal'],
                'urutan' => $scoreData['urutan'],
            ]);
            Nilai::factory()->create([
                'siswa_id' => $student->id,
                'penilaian_id' => $assessment->id,
                'nilai' => $scoreData['nilai'],
            ]);
        }

        $response = $this->actingAs($user)->get(route('guru.analisis.index', [
            'pengampu_id' => $assignment->id,
            'siswa_id' => $student->id,
        ]));

        $response->assertViewHas('studentAnalysis', fn (array $analysis): bool => $analysis['series']->pluck('value')->all() === [60.0, 65.0, 80.0, 85.0]
            && $analysis['trend']['label'] === 'Meningkat');
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
