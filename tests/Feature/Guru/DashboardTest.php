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

class DashboardTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('guru.dashboard'));

        $response->assertRedirectToRoute('login');
    }

    public function test_admin_is_forbidden_from_guru_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('guru.dashboard'));

        $response->assertForbidden();
    }

    public function test_guru_without_a_guru_record_is_forbidden(): void
    {
        $guru = User::factory()->guru()->create();

        $response = $this->actingAs($guru)->get(route('guru.dashboard'));

        $response->assertForbidden();
    }

    public function test_guru_dashboard_displays_identity_and_pengampu_count(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create([
            'nama' => 'Siti Aminah',
            'nip' => '198001012010012001',
        ]);
        Pengampu::factory()->for($guru)->create();

        $response = $this->actingAs($user)->get(route('guru.dashboard'));

        $response->assertOk()
            ->assertSee('Siti Aminah')
            ->assertSee('198001012010012001')
            ->assertSee('Jumlah Pengampu')
            ->assertSee('1')
            ->assertSee('Belum ada penilaian atau data siswa untuk dianalisis.')
            ->assertSee('href="#main-content"', false)
            ->assertSee('id="main-content"', false)
            ->assertSee('aria-controls="dashboard-sidebar"', false)
            ->assertHeader('Cache-Control');
    }

    public function test_guru_dashboard_displays_only_the_authenticated_gurus_pengampu(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $otherGuru = Guru::factory()->create();

        $ownSubject = MataPelajaran::factory()->create(['nama' => 'Matematika Milik Saya']);
        $otherSubject = MataPelajaran::factory()->create(['nama' => 'IPA Milik Guru Lain']);
        $class = Kelas::factory()->create(['nama' => 'A', 'tingkat' => 'VII']);
        $schoolYear = TahunAjaran::factory()->create(['tahun' => '2025/2026', 'semester' => 'Ganjil']);
        Pengampu::factory()->for($guru)->create([
            'mata_pelajaran_id' => $ownSubject->id,
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $schoolYear->id,
        ]);
        Pengampu::factory()->for($otherGuru)->create([
            'mata_pelajaran_id' => $otherSubject->id,
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $schoolYear->id,
        ]);

        $response = $this->actingAs($user)->get(route('guru.dashboard'));

        $response->assertOk()
            ->assertSee('Matematika Milik Saya')
            ->assertDontSee('IPA Milik Guru Lain')
            ->assertSee('VII A')
            ->assertSee('2025/2026')
            ->assertSee('Ganjil');
    }

    public function test_guru_dashboard_shows_an_empty_state_without_pengampu(): void
    {
        $user = User::factory()->guru()->create();
        Guru::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('guru.dashboard'));

        $response->assertOk()
            ->assertSee('Belum ada data pengampu')
            ->assertSee('Tugas mengajar Anda belum ditambahkan oleh administrator.')
            ->assertSee('Belum ada penilaian atau data siswa untuk dianalisis.');
    }

    public function test_guru_dashboard_calculates_score_progress_from_own_assignments(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $subject = MataPelajaran::factory()->create(['kkm' => 99]);
        $assignment = Pengampu::factory()->for($guru)->create([
            'kelas_id' => $class->id,
            'mata_pelajaran_id' => $subject->id,
        ]);
        $students = Siswa::factory()->count(3)->for($class)->create();
        $assessment = Penilaian::factory()->for($assignment)->create(['nama' => 'Tugas Pecahan']);
        Nilai::factory()->create([
            'siswa_id' => $students[0]->id,
            'penilaian_id' => $assessment->id,
            'nilai' => 80,
        ]);
        Nilai::factory()->create([
            'siswa_id' => $students[1]->id,
            'penilaian_id' => $assessment->id,
            'nilai' => 90,
        ]);

        $response = $this->actingAs($user)->get(route('guru.dashboard'));

        $response->assertOk()
            ->assertSee('Tugas Pecahan')
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['studentCount'] === 3
                && $metrics['assessmentCount'] === 1
                && $metrics['missingScoreCount'] === 1
                && $metrics['incompleteAssessmentCount'] === 1
                && $metrics['attentionStudentCount'] === 1)
            ->assertSee('Siswa Perlu Perhatian')
            ->assertSee('Siswa yang memerlukan tindak lanjut berdasarkan hasil TP, kelengkapan penilaian, atau perkembangan nilainya.')
            ->assertDontSee('Siswa di bawah KKM');
    }

    public function test_dashboard_attention_count_matches_attention_page_for_unique_students_in_active_period(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $otherGuru = Guru::factory()->create();
        $activeSchoolYear = TahunAjaran::factory()->create(['aktif' => true]);
        $previousSchoolYear = TahunAjaran::factory()->create(['aktif' => false]);
        $class = Kelas::factory()->create();
        $lowStudent = Siswa::factory()->for($class)->create();
        $noScoreStudent = Siswa::factory()->for($class)->create();
        $zeroScoreStudent = Siswa::factory()->for($class)->create();
        $healthyStudent = Siswa::factory()->for($class)->create();

        foreach ([1, 2] as $subjectNumber) {
            $assignment = Pengampu::factory()->for($guru)->create([
                'kelas_id' => $class->id,
                'mata_pelajaran_id' => MataPelajaran::factory()->create(['nama' => 'Mapel Aktif '.$subjectNumber])->id,
                'tahun_ajaran_id' => $activeSchoolYear->id,
            ]);
            $goal = TujuanPembelajaran::factory()->for($assignment)->create([
                'metode_rekap' => TujuanPembelajaran::METODE_RATA_RATA,
                'kktp' => 75,
            ]);
            $assessment = Penilaian::factory()->for($assignment)->create([
                'tujuan_pembelajaran_id' => $goal->id,
                'kategori_asesmen' => Penilaian::KATEGORI_PTS,
            ]);

            foreach ([[$lowStudent, 60], [$zeroScoreStudent, 0], [$healthyStudent, 85]] as [$student, $score]) {
                Nilai::factory()->create([
                    'siswa_id' => $student->id,
                    'penilaian_id' => $assessment->id,
                    'nilai' => $score,
                ]);
            }
        }

        $previousAssignment = Pengampu::factory()->for($guru)->create([
            'kelas_id' => $class->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
            'tahun_ajaran_id' => $previousSchoolYear->id,
        ]);
        $previousGoal = TujuanPembelajaran::factory()->for($previousAssignment)->create([
            'metode_rekap' => TujuanPembelajaran::METODE_RATA_RATA,
        ]);
        $previousAssessment = Penilaian::factory()->for($previousAssignment)->create([
            'tujuan_pembelajaran_id' => $previousGoal->id,
            'kategori_asesmen' => Penilaian::KATEGORI_PTS,
        ]);
        Nilai::factory()->create([
            'siswa_id' => $healthyStudent->id,
            'penilaian_id' => $previousAssessment->id,
            'nilai' => 0,
        ]);

        $otherAssignment = Pengampu::factory()->for($otherGuru)->create([
            'kelas_id' => $class->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
            'tahun_ajaran_id' => $activeSchoolYear->id,
        ]);
        $otherGoal = TujuanPembelajaran::factory()->for($otherAssignment)->create([
            'metode_rekap' => TujuanPembelajaran::METODE_RATA_RATA,
        ]);
        $otherAssessment = Penilaian::factory()->for($otherAssignment)->create([
            'tujuan_pembelajaran_id' => $otherGoal->id,
            'kategori_asesmen' => Penilaian::KATEGORI_PTS,
        ]);
        Nilai::factory()->create([
            'siswa_id' => $healthyStudent->id,
            'penilaian_id' => $otherAssessment->id,
            'nilai' => 0,
        ]);

        $dashboardResponse = $this->actingAs($user)->get(route('guru.dashboard'));
        $attentionResponse = $this->actingAs($user)->get(route('guru.perhatian.index', [
            'tahun_ajaran_id' => $activeSchoolYear->id,
        ]));

        $dashboardResponse->assertOk()
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['assignmentCount'] === 2
                && $metrics['assessmentCount'] === 2
                && $metrics['attentionStudentCount'] === 3)
            ->assertSee(route('guru.perhatian.index', ['tahun_ajaran_id' => $activeSchoolYear->id]), false);
        $attentionResponse->assertOk()
            ->assertViewHas('summary', fn (array $summary): bool => $summary['studentCount'] === 3
                && $summary['belowKktpCount'] === 2
                && $summary['belowTpStudentCount'] === 4)
            ->assertViewHas('rows', function ($rows) use ($lowStudent, $noScoreStudent, $zeroScoreStudent, $healthyStudent): bool {
                $lowRow = $rows->firstWhere('student.id', $lowStudent->id);
                $noScoreRow = $rows->firstWhere('student.id', $noScoreStudent->id);
                $zeroScoreRow = $rows->firstWhere('student.id', $zeroScoreStudent->id);

                return $lowRow['belowThresholdCount'] === 1
                    && $noScoreRow['belowThresholdCount'] === 0
                    && $noScoreRow['missingCount'] === 1
                    && $zeroScoreRow['belowThresholdCount'] === 1
                    && $rows->doesntContain(fn (array $row): bool => $row['student']->id === $healthyStudent->id);
            });
    }
}
