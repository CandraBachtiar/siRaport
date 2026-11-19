<?php

namespace Tests\Feature;

use App\Enums\AssessmentPlacementStatus;
use App\Enums\PlacementHistoryStatus;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\RiwayatPenempatanSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\AssessmentPlacementChecker;
use App\Services\AuthorizedPlacementHistoryLookup;
use App\Services\PlacementHistoryReader;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class PlacementHistoryReaderTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->createAdminTestSchema();
    }

    public function test_reads_each_sequential_period_and_includes_both_boundary_dates(): void
    {
        $firstClass = Kelas::factory()->create(['nama' => '1A']);
        $secondClass = Kelas::factory()->create(['nama' => '2A']);
        $student = Siswa::factory()->for($secondClass)->create();
        $firstPeriod = TahunAjaran::factory()->create(['tahun' => '2024/2025', 'semester' => 'Ganjil']);
        $secondPeriod = TahunAjaran::factory()->create(['tahun' => '2025/2026', 'semester' => 'Ganjil']);
        RiwayatPenempatanSiswa::factory()->create([
            'siswa_id' => $student->id,
            'kelas_id' => $firstClass->id,
            'tahun_ajaran_id' => $firstPeriod->id,
            'mulai_pada' => '2024-07-01',
            'berakhir_pada' => '2025-06-30',
        ]);
        RiwayatPenempatanSiswa::factory()->create([
            'siswa_id' => $student->id,
            'kelas_id' => $secondClass->id,
            'tahun_ajaran_id' => $secondPeriod->id,
            'mulai_pada' => '2025-07-01',
        ]);

        $reader = new PlacementHistoryReader;

        $firstDay = $reader->forDate($student->id, $firstClass->id, $firstPeriod->id, '2024-07-01');
        $lastDay = $reader->forDate($student->id, $firstClass->id, $firstPeriod->id, '2025-06-30');
        $nextPeriod = $reader->forDate($student->id, $secondClass->id, $secondPeriod->id, '2025-07-01');

        $this->assertSame(PlacementHistoryStatus::Verified, $firstDay->status);
        $this->assertSame(PlacementHistoryStatus::Verified, $lastDay->status);
        $this->assertSame(PlacementHistoryStatus::Verified, $nextPeriod->status);
        $this->assertSame($firstClass->id, $firstDay->placement()?->kelas_id);
        $this->assertSame($secondClass->id, $nextPeriod->placement()?->kelas_id);
    }

    public function test_reports_sequential_same_period_class_transfer_and_stay_class_records(): void
    {
        $oldClass = Kelas::factory()->create(['nama' => '2A']);
        $newClass = Kelas::factory()->create(['nama' => '2B']);
        $student = Siswa::factory()->for($newClass)->create();
        $period = TahunAjaran::factory()->create(['tahun' => '2025/2026', 'semester' => 'Genap']);
        RiwayatPenempatanSiswa::factory()->create([
            'siswa_id' => $student->id,
            'kelas_id' => $oldClass->id,
            'tahun_ajaran_id' => $period->id,
            'mulai_pada' => '2026-01-01',
            'berakhir_pada' => '2026-03-14',
        ]);
        RiwayatPenempatanSiswa::factory()->create([
            'siswa_id' => $student->id,
            'kelas_id' => $newClass->id,
            'tahun_ajaran_id' => $period->id,
            'mulai_pada' => '2026-03-15',
        ]);
        $sameClassStudent = Siswa::factory()->for($oldClass)->create();
        RiwayatPenempatanSiswa::factory()->create([
            'siswa_id' => $sameClassStudent->id,
            'kelas_id' => $oldClass->id,
            'tahun_ajaran_id' => $period->id,
            'mulai_pada' => '2026-01-01',
        ]);

        $reader = new PlacementHistoryReader;

        $beforeTransfer = $reader->forDate($student->id, $oldClass->id, $period->id, '2026-03-14');
        $afterTransfer = $reader->forDate($student->id, $newClass->id, $period->id, '2026-03-15');
        $stayed = $reader->forDate($sameClassStudent->id, $oldClass->id, $period->id, '2026-03-15');
        $wrongRequestedClass = $reader->forDate($student->id, $oldClass->id, $period->id, '2026-03-15');

        $this->assertSame(PlacementHistoryStatus::Verified, $beforeTransfer->status);
        $this->assertSame(PlacementHistoryStatus::Verified, $afterTransfer->status);
        $this->assertSame(PlacementHistoryStatus::Verified, $stayed->status);
        $this->assertSame(PlacementHistoryStatus::DifferentClass, $wrongRequestedClass->status);
    }

    public function test_reports_overlapping_placement_records_as_a_conflict(): void
    {
        $firstClass = Kelas::factory()->create();
        $secondClass = Kelas::factory()->create();
        $student = Siswa::factory()->for($secondClass)->create();
        $period = TahunAjaran::factory()->create();
        RiwayatPenempatanSiswa::factory()->create([
            'siswa_id' => $student->id,
            'kelas_id' => $firstClass->id,
            'tahun_ajaran_id' => $period->id,
            'mulai_pada' => '2026-01-01',
            'berakhir_pada' => '2026-03-15',
        ]);
        RiwayatPenempatanSiswa::factory()->create([
            'siswa_id' => $student->id,
            'kelas_id' => $secondClass->id,
            'tahun_ajaran_id' => $period->id,
            'mulai_pada' => '2026-03-15',
        ]);

        $result = (new PlacementHistoryReader)->forDate($student->id, $secondClass->id, $period->id, '2026-03-15');

        $this->assertSame(PlacementHistoryStatus::Conflict, $result->status);
        $this->assertCount(2, $result->placements);
        $this->assertNull($result->placement());
    }

    public function test_keeps_missing_history_unknown_instead_of_assuming_the_current_class(): void
    {
        $currentClass = Kelas::factory()->create();
        $student = Siswa::factory()->for($currentClass)->create();
        $period = TahunAjaran::factory()->create();

        $result = (new PlacementHistoryReader)->forDate($student->id, $currentClass->id, $period->id, '2025-10-01');

        $this->assertSame(PlacementHistoryStatus::Unknown, $result->status);
        $this->assertCount(0, $result->placements);
        $this->assertNull($result->placement());
    }

    public function test_historical_scores_remain_linked_to_their_original_assessment_class(): void
    {
        $originalClass = Kelas::factory()->create();
        $currentClass = Kelas::factory()->create();
        $student = Siswa::factory()->for($currentClass)->create();
        $period = TahunAjaran::factory()->create();
        $assignment = Pengampu::factory()->create([
            'kelas_id' => $originalClass->id,
            'tahun_ajaran_id' => $period->id,
        ]);
        $assessment = Penilaian::factory()->for($assignment)->create();
        $score = Nilai::factory()->create([
            'siswa_id' => $student->id,
            'penilaian_id' => $assessment->id,
            'nilai' => 82,
        ]);

        $result = (new PlacementHistoryReader)->forDate($student->id, $originalClass->id, $period->id, $assessment->tanggal->toDateString());

        $this->assertSame(PlacementHistoryStatus::Unknown, $result->status);
        $this->assertSame($originalClass->id, $score->fresh()->penilaian->pengampu->kelas_id);
        $this->assertSame(82.0, (float) $score->fresh()->nilai);
    }

    public function test_batch_lookup_matches_single_lookup_without_one_query_per_student(): void
    {
        $class = Kelas::factory()->create();
        $otherClass = Kelas::factory()->create();
        $period = TahunAjaran::factory()->create();
        $verified = Siswa::factory()->for($otherClass)->create();
        $different = Siswa::factory()->for($class)->create();
        $unknown = Siswa::factory()->for($class)->create();
        $conflict = Siswa::factory()->for($class)->create();

        foreach ([[$verified, $class, '2026-01-01', '2026-01-10'], [$different, $otherClass, '2026-01-01', null], [$conflict, $class, '2026-01-01', '2026-01-10'], [$conflict, $otherClass, '2026-01-10', null]] as [$student, $placementClass, $start, $end]) {
            RiwayatPenempatanSiswa::factory()->create(['siswa_id' => $student->id, 'kelas_id' => $placementClass->id, 'tahun_ajaran_id' => $period->id, 'mulai_pada' => $start, 'berakhir_pada' => $end]);
        }

        DB::enableQueryLog();
        DB::flushQueryLog();
        $results = (new PlacementHistoryReader)->forStudents([$verified->id, $different->id, $unknown->id, $conflict->id, $verified->id], $class->id, $period->id, '2026-01-10');
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(4, $results);
        $this->assertLessThanOrEqual(2, $queryCount);
        foreach ([$verified, $different, $unknown, $conflict] as $student) {
            $single = (new PlacementHistoryReader)->forDate($student->id, $class->id, $period->id, '2026-01-10');
            $this->assertSame($single->status, $results[$student->id]->status);
            $this->assertSame($single->placements->modelKeys(), $results[$student->id]->placements->modelKeys());
        }
        $this->assertSame(PlacementHistoryStatus::Verified, $results[$verified->id]->status);
        $this->assertSame(PlacementHistoryStatus::DifferentClass, $results[$different->id]->status);
        $this->assertSame(PlacementHistoryStatus::Unknown, $results[$unknown->id]->status);
        $this->assertSame(PlacementHistoryStatus::Conflict, $results[$conflict->id]->status);
        $this->assertCount(2, $results[$conflict->id]->placements);
        $this->assertNull($results[$conflict->id]->placement());
        $this->assertSame($otherClass->id, $verified->fresh()->kelas_id);
    }

    public function test_authorized_assignment_adapter_uses_only_supplied_ids_and_explicit_assessment_date(): void
    {
        $class = Kelas::factory()->create();
        $otherClass = Kelas::factory()->create();
        $period = TahunAjaran::factory()->create();
        $assignment = Pengampu::factory()->create(['kelas_id' => $class->id, 'tahun_ajaran_id' => $period->id]);
        $authorizedStudent = Siswa::factory()->for($class)->create();
        $outsideStudent = Siswa::factory()->for($otherClass)->create();
        RiwayatPenempatanSiswa::factory()->create([
            'siswa_id' => $authorizedStudent->id,
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $period->id,
            'mulai_pada' => '2026-02-10',
            'berakhir_pada' => '2026-02-10',
        ]);

        $result = (new AuthorizedPlacementHistoryLookup(new PlacementHistoryReader))
            ->forAssignmentDate([$authorizedStudent->id], $assignment, '2026-02-10');

        $this->assertSame([$authorizedStudent->id], $result->keys()->all());
        $this->assertSame(PlacementHistoryStatus::Verified, $result[$authorizedStudent->id]->status);
        $this->assertNotContains($outsideStudent->id, $result->keys());
    }

    public function test_assessment_checker_reports_verified_unknown_conflict_and_excludes_students_outside_assignment_roster(): void
    {
        $class = Kelas::factory()->create();
        $otherClass = Kelas::factory()->create();
        $period = TahunAjaran::factory()->create();
        $guru = Guru::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->for($class)->for($period)->create();
        $assessment = Penilaian::factory()->for($assignment)->create(['tanggal' => '2026-03-15']);
        $verified = Siswa::factory()->for($class)->create();
        $unknown = Siswa::factory()->for($class)->create();
        $conflict = Siswa::factory()->for($class)->create();
        $outside = Siswa::factory()->for($otherClass)->create();
        RiwayatPenempatanSiswa::factory()->create(['siswa_id' => $verified->id, 'kelas_id' => $class->id, 'tahun_ajaran_id' => $period->id, 'mulai_pada' => '2026-03-15', 'berakhir_pada' => '2026-03-15']);
        RiwayatPenempatanSiswa::factory()->create(['siswa_id' => $conflict->id, 'kelas_id' => $class->id, 'tahun_ajaran_id' => $period->id, 'mulai_pada' => '2026-03-01', 'berakhir_pada' => '2026-03-20']);
        RiwayatPenempatanSiswa::factory()->create(['siswa_id' => $conflict->id, 'kelas_id' => $otherClass->id, 'tahun_ajaran_id' => $period->id, 'mulai_pada' => '2026-03-15']);
        $score = Nilai::factory()->create(['siswa_id' => $verified->id, 'penilaian_id' => $assessment->id, 'nilai' => 86]);

        $result = (new AssessmentPlacementChecker(new AuthorizedPlacementHistoryLookup(new PlacementHistoryReader)))
            ->check($guru->user, $assignment, $assessment, [$verified->id, $unknown->id, $conflict->id, $outside->id]);

        $this->assertSame(AssessmentPlacementStatus::MatchesAssignment, $result[$verified->id]);
        $this->assertSame(AssessmentPlacementStatus::Unknown, $result[$unknown->id]);
        $this->assertSame(AssessmentPlacementStatus::Conflict, $result[$conflict->id]);
        $this->assertArrayNotHasKey($outside->id, $result->all());
        $this->assertSame($class->id, $verified->fresh()->kelas_id);
        $this->assertSame(86.0, (float) $score->fresh()->nilai);
    }

    public function test_assessment_checker_rejects_an_assignment_outside_teacher_authority(): void
    {
        $assignment = Pengampu::factory()->create();
        $assessment = Penilaian::factory()->for($assignment)->create(['tanggal' => '2026-03-15']);
        $otherGuru = Guru::factory()->create();

        $this->expectException(AuthorizationException::class);
        (new AssessmentPlacementChecker(new AuthorizedPlacementHistoryLookup(new PlacementHistoryReader)))
            ->check($otherGuru->user, $assignment, $assessment, []);
    }

    public function test_assessment_checker_marks_a_missing_assessment_date_without_looking_up_history(): void
    {
        $guru = Guru::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create();
        $student = Siswa::factory()->for($assignment->kelas)->create();
        $assessment = new Penilaian(['pengampu_id' => $assignment->id, 'tanggal' => null]);
        $assessment->id = 99;

        $result = (new AssessmentPlacementChecker(new AuthorizedPlacementHistoryLookup(new PlacementHistoryReader)))
            ->check($guru->user, $assignment, $assessment, [$student->id]);

        $this->assertSame(AssessmentPlacementStatus::DateUnavailable, $result[$student->id]);
    }
}
