<?php

namespace Tests\Feature\Services;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\RiwayatPenempatanSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\AssessmentRosterService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class AssessmentRosterServiceTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->createAdminTestSchema();
    }

    public function test_siswa_yang_sudah_pindah_tetap_masuk_roster_penilaian_lama(): void
    {
        $kelasLama = Kelas::factory()->create(['nama' => 'VII A']);
        $kelasBaru = Kelas::factory()->create(['nama' => 'VII B']);
        $periode = TahunAjaran::factory()->create();
        $guru = Guru::factory()->create();

        $pengampu = Pengampu::factory()
            ->for($guru)
            ->for($kelasLama)
            ->for($periode)
            ->create();

        $penilaian = Penilaian::factory()
            ->for($pengampu)
            ->create(['tanggal' => '2026-11-10']);

        $siswa = Siswa::factory()
            ->for($kelasBaru)
            ->create();

        RiwayatPenempatanSiswa::factory()->create([
            'siswa_id' => $siswa->id,
            'kelas_id' => $kelasLama->id,
            'tahun_ajaran_id' => $periode->id,
            'mulai_pada' => '2026-07-01',
            'berakhir_pada' => '2026-12-31',
        ]);

        $studentIds = app(AssessmentRosterService::class)
            ->verifiedStudentIds($guru->user, $penilaian);

        $this->assertContains($siswa->id, $studentIds->all());
    }

    public function test_roster_uses_inclusive_dates_and_matching_school_year(): void
    {
        $class = Kelas::factory()->create();
        $otherClass = Kelas::factory()->create();
        $schoolYear = TahunAjaran::factory()->create();
        $otherSchoolYear = TahunAjaran::factory()->create();
        $teacher = Guru::factory()->create();
        $assessment = Penilaian::factory()->for(Pengampu::factory()
            ->for($teacher)
            ->for($class)
            ->for($schoolYear)
            ->create())->create(['tanggal' => '2026-11-10']);
        $atStart = Siswa::factory()->for($otherClass)->create();
        $atEnd = Siswa::factory()->for($otherClass)->create();
        $wrongYear = Siswa::factory()->for($otherClass)->create();

        foreach ([
            [$atStart, $schoolYear, '2026-11-10', '2026-11-10'],
            [$atEnd, $schoolYear, '2026-10-01', '2026-11-10'],
            [$wrongYear, $otherSchoolYear, '2026-10-01', '2026-12-01'],
        ] as [$student, $period, $start, $end]) {
            RiwayatPenempatanSiswa::factory()->create([
                'siswa_id' => $student->id,
                'kelas_id' => $class->id,
                'tahun_ajaran_id' => $period->id,
                'mulai_pada' => $start,
                'berakhir_pada' => $end,
            ]);
        }

        $studentIds = app(AssessmentRosterService::class)->verifiedStudentIds($teacher->user, $assessment);

        $this->assertEqualsCanonicalizing([$atStart->id, $atEnd->id], $studentIds->all());
    }

    public function test_missing_date_and_conflicting_history_are_not_verified(): void
    {
        $class = Kelas::factory()->create();
        $schoolYear = TahunAjaran::factory()->create();
        $teacher = Guru::factory()->create();
        $assignment = Pengampu::factory()->for($teacher)->for($class)->for($schoolYear)->create();
        $datedAssessment = Penilaian::factory()->for($assignment)->create(['tanggal' => '2026-11-10']);
        $undatedAssessment = new Penilaian(['tanggal' => null, 'pengampu_id' => $assignment->id]);
        $undatedAssessment->setRelation('pengampu', $assignment);
        $student = Siswa::factory()->create();

        RiwayatPenempatanSiswa::factory()->create([
            'siswa_id' => $student->id,
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $schoolYear->id,
            'mulai_pada' => '2026-01-01',
            'berakhir_pada' => null,
        ]);
        RiwayatPenempatanSiswa::factory()->create([
            'siswa_id' => $student->id,
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $schoolYear->id,
            'mulai_pada' => '2026-06-01',
            'berakhir_pada' => null,
        ]);

        $service = app(AssessmentRosterService::class);

        $this->assertSame([], $service->verifiedStudentIds($teacher->user, $undatedAssessment)->all());
        $this->assertSame([], $service->verifiedStudentIds($teacher->user, $datedAssessment)->all());
    }

    public function test_teacher_cannot_read_roster_for_another_teachers_assignment(): void
    {
        $teacher = Guru::factory()->create();
        $otherTeacher = Guru::factory()->create();
        $assignment = Pengampu::factory()->for($otherTeacher)->create();
        $assessment = Penilaian::factory()->for($assignment)->create(['tanggal' => '2026-11-10']);

        $this->expectException(AuthorizationException::class);

        app(AssessmentRosterService::class)->verifiedStudentIds($teacher->user, $assessment);
    }
}
