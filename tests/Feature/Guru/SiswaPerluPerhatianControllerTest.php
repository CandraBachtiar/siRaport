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
use App\Models\User;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class SiswaPerluPerhatianControllerTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_page_identifies_below_kkm_declining_and_incomplete_students(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $subject = MataPelajaran::factory()->create(['kkm' => 80]);
        $assignment = Pengampu::factory()->for($guru)->create([
            'kelas_id' => $class->id,
            'mata_pelajaran_id' => $subject->id,
        ]);
        $decliningStudent = Siswa::factory()->for($class)->create(['nama' => 'Alya Menurun']);
        $incompleteStudent = Siswa::factory()->for($class)->create(['nama' => 'Bima Belum Lengkap']);
        $healthyStudent = Siswa::factory()->for($class)->create(['nama' => 'Citra Stabil']);
        foreach ([90, 85, 70, 60] as $index => $score) {
            $assessment = Penilaian::factory()->for($assignment)->create([
                'urutan' => $index + 1,
                'tanggal' => now()->addDays($index),
            ]);
            Nilai::factory()->create(['siswa_id' => $decliningStudent->id, 'penilaian_id' => $assessment->id, 'nilai' => $score]);
            Nilai::factory()->create(['siswa_id' => $healthyStudent->id, 'penilaian_id' => $assessment->id, 'nilai' => 90]);

            if ($index < 2) {
                Nilai::factory()->create(['siswa_id' => $incompleteStudent->id, 'penilaian_id' => $assessment->id, 'nilai' => 90]);
            }
        }

        $response = $this->actingAs($user)->get(route('guru.perhatian.index', ['pengampu_id' => $assignment->id]));

        $response->assertOk()
            ->assertSee('Alya Menurun')
            ->assertSee('Bima Belum Lengkap')
            ->assertDontSee('Citra Stabil')
            ->assertSee('Rata-rata di bawah KKM')
            ->assertSee('Tren nilai menurun')
            ->assertSee('2 nilai belum diisi')
            ->assertViewHas('rows', fn ($rows): bool => $rows->count() === 2);
    }

    public function test_page_does_not_display_students_from_another_guru(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $ownAssignment = Pengampu::factory()->for($guru)->create();
        Penilaian::factory()->for($ownAssignment)->create();
        $otherAssignment = Pengampu::factory()->create();
        $otherStudent = Siswa::factory()->create([
            'kelas_id' => $otherAssignment->kelas_id,
            'nama' => 'Siswa Guru Lain',
        ]);
        Penilaian::factory()->for($otherAssignment)->create();

        $response = $this->actingAs($user)->get(route('guru.perhatian.index'));

        $response->assertOk()
            ->assertDontSee($otherStudent->nama);
    }

    public function test_guru_cannot_filter_attention_by_another_gurus_assignment(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        Pengampu::factory()->for($guru)->create();
        $otherAssignment = Pengampu::factory()->create();

        $response = $this->actingAs($user)->get(route('guru.perhatian.index', ['pengampu_id' => $otherAssignment->id]));

        $response->assertNotFound();
    }

    public function test_empty_state_explains_when_trend_data_is_insufficient(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $student = Siswa::factory()->for($class)->create();
        $assessment = Penilaian::factory()->for($assignment)->create();
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessment->id, 'nilai' => 95]);

        $response = $this->actingAs($user)->get(route('guru.perhatian.index', ['pengampu_id' => $assignment->id]));

        $response->assertOk()
            ->assertSee('Data belum cukup untuk dianalisis')
            ->assertDontSee('Semua siswa pada filter ini tidak memenuhi indikator perhatian yang digunakan.');
    }

    public function test_empty_state_explains_when_no_assignment_matches_the_period(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        Pengampu::factory()->for($guru)->create(['tahun_ajaran_id' => TahunAjaran::factory()->create(['aktif' => false])->id]);
        $schoolYearWithoutAssignment = TahunAjaran::factory()->create(['aktif' => true]);

        $response = $this->actingAs($user)->get(route('guru.perhatian.index', [
            'tahun_ajaran_id' => $schoolYearWithoutAssignment->id,
        ]));

        $response->assertOk()
            ->assertSee('Belum ada penugasan pada periode ini');
    }

    public function test_empty_state_explains_when_assignments_have_no_assessments(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $assignment = Pengampu::factory()->for($guru)->create();

        $response = $this->actingAs($user)->get(route('guru.perhatian.index', ['pengampu_id' => $assignment->id]));

        $response->assertOk()
            ->assertSee('Belum ada data penilaian');
    }

    public function test_empty_state_explains_when_assignment_class_has_no_students(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $assignment = Pengampu::factory()->for($guru)->create();
        Penilaian::factory()->for($assignment)->create();

        $response = $this->actingAs($user)->get(route('guru.perhatian.index', ['pengampu_id' => $assignment->id]));

        $response->assertOk()
            ->assertSee('Belum ada siswa pada kelas ini');
    }

    public function test_empty_state_reports_no_indicators_when_all_students_have_analyzable_trends(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $student = Siswa::factory()->for($class)->create();

        foreach ([85, 85, 85, 85] as $index => $score) {
            $assessment = Penilaian::factory()->for($assignment)->create(['urutan' => $index + 1]);
            Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessment->id, 'nilai' => $score]);
        }

        $response = $this->actingAs($user)->get(route('guru.perhatian.index', ['pengampu_id' => $assignment->id]));

        $response->assertOk()
            ->assertSee('Tidak ditemukan indikator perhatian dari nilai yang tersedia')
            ->assertDontSee('Semua siswa pada filter ini tidak memenuhi indikator perhatian yang digunakan.');
    }

    public function test_attention_reasons_keep_zero_valid_and_explain_insufficient_trend_data_once(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $subject = MataPelajaran::factory()->create(['kkm' => 75]);
        $assignment = Pengampu::factory()->for($guru)->create([
            'kelas_id' => $class->id,
            'mata_pelajaran_id' => $subject->id,
        ]);
        $student = Siswa::factory()->for($class)->create(['nama' => 'Dina Data Terbatas']);
        $firstAssessment = Penilaian::factory()->for($assignment)->create(['urutan' => 1]);
        Penilaian::factory()->for($assignment)->create(['urutan' => 2]);
        Nilai::factory()->create([
            'siswa_id' => $student->id,
            'penilaian_id' => $firstAssessment->id,
            'nilai' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('guru.perhatian.index', ['pengampu_id' => $assignment->id]));

        $response->assertOk()
            ->assertSee('Dina Data Terbatas')
            ->assertSee('Rata-rata di bawah KKM')
            ->assertSee('1 nilai belum diisi')
            ->assertSee('Data belum cukup untuk menentukan tren')
            ->assertViewHas('rows', function ($rows) use ($student): bool {
                $studentRows = $rows->where('student.id', $student->id);

                return $studentRows->count() === 1
                    && $studentRows->first()['average'] === 0.0
                    && $studentRows->first()['trend']['label'] === 'Belum cukup data'
                    && $studentRows->first()['reasons']->count() === 3;
            });
    }

    public function test_default_attention_view_uses_the_active_school_year(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $activeYear = TahunAjaran::factory()->create(['tahun' => '2026/2027', 'semester' => 'Ganjil', 'aktif' => true]);
        $inactiveYear = TahunAjaran::factory()->create(['tahun' => '2025/2026', 'semester' => 'Genap', 'aktif' => false]);
        $activeAssignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id, 'tahun_ajaran_id' => $activeYear->id]);
        $inactiveAssignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id, 'tahun_ajaran_id' => $inactiveYear->id]);
        $student = Siswa::factory()->for($class)->create(['nama' => 'Siswa Periode Aktif']);
        $activeAssessment = Penilaian::factory()->for($activeAssignment)->create();
        $inactiveAssessment = Penilaian::factory()->for($inactiveAssignment)->create();
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $activeAssessment->id, 'nilai' => 50]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $inactiveAssessment->id, 'nilai' => 50]);

        $response = $this->actingAs($user)->get(route('guru.perhatian.index'));

        $response->assertOk()
            ->assertSee('2026/2027')
            ->assertDontSee('2025/2026');
    }
}
