<?php

namespace Tests\Feature;

use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\TujuanPembelajaran;
use App\Services\TujuanPembelajaranScoreService;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class TujuanPembelajaranScoreServiceTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_it_aggregates_each_sumative_category_per_tp_and_keeps_formative_separate(): void
    {
        $assignment = Pengampu::factory()->create();
        $goal = TujuanPembelajaran::factory()->for($assignment)->create([
            'kktp' => 75,
            'metode_rekap' => TujuanPembelajaran::METODE_RATA_RATA,
        ]);
        $student = Siswa::factory()->create(['kelas_id' => $assignment->kelas_id]);
        $assessments = collect([
            $this->assessment($assignment, $goal, Penilaian::KATEGORI_LINGKUP_MATERI, 'Lingkup 1'),
            $this->assessment($assignment, $goal, Penilaian::KATEGORI_LINGKUP_MATERI, 'Lingkup 2'),
            $this->assessment($assignment, $goal, Penilaian::KATEGORI_PTS, 'PTS'),
            $this->assessment($assignment, $goal, Penilaian::KATEGORI_FORMATIF, 'Formatif'),
            $this->assessment($assignment, $goal, Penilaian::KATEGORI_FORMATIF, 'Formatif belum diisi'),
        ]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessments[0]->id, 'nilai' => 80]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessments[1]->id, 'nilai' => 100]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessments[2]->id, 'nilai' => 0]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessments[3]->id, 'nilai' => 50]);
        $assessments->each(fn (Penilaian $assessment): Penilaian => $assessment->load('nilai'));

        $summary = app(TujuanPembelajaranScoreService::class)->summarize(collect([$goal]), collect([$student]), $assessments)->first();

        $this->assertSame(90.0, $summary['categories'][Penilaian::KATEGORI_LINGKUP_MATERI]['rows'][$student->id]['score']);
        $this->assertTrue($summary['categories'][Penilaian::KATEGORI_LINGKUP_MATERI]['rows'][$student->id]['attainment']);
        $this->assertSame(0.0, $summary['categories'][Penilaian::KATEGORI_PTS]['rows'][$student->id]['score']);
        $this->assertFalse($summary['categories'][Penilaian::KATEGORI_PTS]['rows'][$student->id]['attainment']);
        $this->assertNull($summary['categories'][Penilaian::KATEGORI_FORMATIF]['rows'][$student->id]['attainment']);
        $this->assertSame('Nilai belum lengkap · umpan balik pembelajaran', $summary['categories'][Penilaian::KATEGORI_FORMATIF]['rows'][$student->id]['status']);
        $this->assertSame(1, $summary['categories'][Penilaian::KATEGORI_FORMATIF]['rows'][$student->id]['filledCount']);
        $this->assertSame(2, $summary['categories'][Penilaian::KATEGORI_FORMATIF]['rows'][$student->id]['totalCount']);
    }

    public function test_average_and_highest_methods_use_the_configured_aggregation_for_filled_scores(): void
    {
        $assignment = Pengampu::factory()->create();
        $averageGoal = TujuanPembelajaran::factory()->for($assignment)->create([
            'kktp' => 75,
            'metode_rekap' => TujuanPembelajaran::METODE_RATA_RATA,
        ]);
        $highestGoal = TujuanPembelajaran::factory()->for($assignment)->create([
            'kktp' => 75,
            'metode_rekap' => TujuanPembelajaran::METODE_NILAI_TERTINGGI,
        ]);
        $student = Siswa::factory()->create(['kelas_id' => $assignment->kelas_id]);
        $assessments = collect([
            $this->assessment($assignment, $averageGoal, Penilaian::KATEGORI_LINGKUP_MATERI, 'Rata-rata nol'),
            $this->assessment($assignment, $averageGoal, Penilaian::KATEGORI_LINGKUP_MATERI, 'Rata-rata tinggi'),
            $this->assessment($assignment, $highestGoal, Penilaian::KATEGORI_LINGKUP_MATERI, 'Tertinggi nol'),
            $this->assessment($assignment, $highestGoal, Penilaian::KATEGORI_LINGKUP_MATERI, 'Tertinggi tinggi'),
        ]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessments[0]->id, 'nilai' => 0]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessments[1]->id, 'nilai' => 90]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessments[2]->id, 'nilai' => 0]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessments[3]->id, 'nilai' => 90]);
        $assessments->each(fn (Penilaian $assessment): Penilaian => $assessment->load('nilai'));

        $summaries = app(TujuanPembelajaranScoreService::class)->summarize(
            collect([$averageGoal, $highestGoal]),
            collect([$student]),
            $assessments,
        );
        $averageResult = $summaries[0]['categories'][Penilaian::KATEGORI_LINGKUP_MATERI]['rows'][$student->id];
        $highestResult = $summaries[1]['categories'][Penilaian::KATEGORI_LINGKUP_MATERI]['rows'][$student->id];

        $this->assertSame(45.0, $averageResult['score']);
        $this->assertFalse($averageResult['attainment']);
        $this->assertSame(90.0, $highestResult['score']);
        $this->assertTrue($highestResult['attainment']);
        $this->assertSame(2, $highestResult['filledCount']);
        $this->assertSame(2, $highestResult['totalCount']);
        $this->assertTrue($highestResult['isComplete']);
    }

    public function test_highest_method_counts_zero_and_missing_score_stays_incomplete_without_threshold_result(): void
    {
        $assignment = Pengampu::factory()->create();
        $goal = TujuanPembelajaran::factory()->for($assignment)->create([
            'kktp' => 0,
            'metode_rekap' => TujuanPembelajaran::METODE_NILAI_TERTINGGI,
        ]);
        $student = Siswa::factory()->create(['kelas_id' => $assignment->kelas_id]);
        $first = $this->assessment($assignment, $goal, Penilaian::KATEGORI_LINGKUP_MATERI, 'Nilai nol');
        $second = $this->assessment($assignment, $goal, Penilaian::KATEGORI_LINGKUP_MATERI, 'Belum diisi');
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $first->id, 'nilai' => 0]);
        $assessments = collect([$first, $second]);
        $assessments->each(fn (Penilaian $assessment): Penilaian => $assessment->load('nilai'));

        $summary = app(TujuanPembelajaranScoreService::class)->summarize(collect([$goal]), collect([$student]), $assessments)->first();
        $result = $summary['categories'][Penilaian::KATEGORI_LINGKUP_MATERI]['rows'][$student->id];

        $this->assertSame(0.0, $result['score']);
        $this->assertSame(1, $result['missingCount']);
        $this->assertSame(1, $result['filledCount']);
        $this->assertSame(2, $result['totalCount']);
        $this->assertFalse($result['isComplete']);
        $this->assertNull($result['attainment']);
        $this->assertSame('Nilai belum lengkap', $result['status']);
    }

    public function test_threshold_uses_unrounded_result_and_keeps_unclassified_legacy_assessment_separate(): void
    {
        $assignment = Pengampu::factory()->create();
        $goal = TujuanPembelajaran::factory()->for($assignment)->create([
            'kktp' => 75,
            'metode_rekap' => TujuanPembelajaran::METODE_RATA_RATA,
        ]);
        $student = Siswa::factory()->create(['kelas_id' => $assignment->kelas_id]);
        $assessment = $this->assessment($assignment, $goal, Penilaian::KATEGORI_LINGKUP_MATERI, 'Nilai batas');
        $secondAssessment = $this->assessment($assignment, $goal, Penilaian::KATEGORI_LINGKUP_MATERI, 'Nilai pembanding');
        $legacyAssessment = $this->assessment($assignment, $goal, null, 'Legacy');
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessment->id, 'nilai' => 74.99]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $secondAssessment->id, 'nilai' => 75]);
        $assessments = collect([$assessment, $secondAssessment, $legacyAssessment]);
        $assessments->each(fn (Penilaian $item): Penilaian => $item->load('nilai'));

        $summary = app(TujuanPembelajaranScoreService::class)->summarize(collect([$goal]), collect([$student]), $assessments)->first();
        $result = $summary['categories'][Penilaian::KATEGORI_LINGKUP_MATERI]['rows'][$student->id];

        $this->assertSame(1, $summary['unclassifiedAssessments']->count());
        $this->assertSame(74.995, $result['score']);
        $this->assertSame(2, $result['filledCount']);
        $this->assertSame(2, $result['totalCount']);
        $this->assertFalse($result['attainment']);
    }

    private function assessment(Pengampu $assignment, TujuanPembelajaran $goal, ?string $category, string $name): Penilaian
    {
        return Penilaian::factory()->for($assignment)->create([
            'tujuan_pembelajaran_id' => $goal->id,
            'kategori_asesmen' => $category,
            'nama' => $name,
        ]);
    }
}
