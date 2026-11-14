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
        $goal = TujuanPembelajaran::factory()->for($assignment)->create(['kktp' => 80]);
        $decliningStudent = Siswa::factory()->for($class)->create(['nama' => 'Alya Menurun']);
        $incompleteStudent = Siswa::factory()->for($class)->create(['nama' => 'Bima Belum Lengkap']);
        $healthyStudent = Siswa::factory()->for($class)->create(['nama' => 'Citra Stabil']);
        foreach ([90, 85, 70, 60] as $index => $score) {
            $assessment = Penilaian::factory()->for($assignment)->create([
                'urutan' => $index + 1,
                'tanggal' => now()->addDays($index),
                'tujuan_pembelajaran_id' => $goal->id,
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
            ->assertSee('Siswa Perlu Perhatian')
            ->assertSee('Siswa dengan Nilai Belum Lengkap')
            ->assertSee('Siswa dengan Tren Menurun')
            ->assertSee('Siswa dengan TP di Bawah KKTP')
            ->assertSee('Rincian hasil TP dan kategori')
            ->assertSee('Jumlah hasil TP di bawah KKTP')
            ->assertSee('Jumlah hasil kategori sumatif di bawah KKTP')
            ->assertSee('Jumlah hasil TP belum lengkap')
            ->assertSee('1 hasil TP belum lengkap (1 kategori).')
            ->assertSee('Periksa nilai sumatif yang belum diisi atau metode rekap TP yang belum ditentukan.')
            ->assertSee('1 hasil TP di bawah KKTP (1 kategori).')
            ->assertSee('Tinjau capaian TP dan tentukan tindak lanjut pembelajaran.')
            ->assertSee('Periksa perkembangan nilai pada beberapa penilaian.')
            ->assertSee('2 nilai tugas belum diisi')
            ->assertSee('Periksa dan lengkapi nilai pada penilaian terkait.')
            ->assertSee('Rata-rata Nilai Tugas')
            ->assertSee('Bukan nilai akhir')
            ->assertSee(route('guru.analisis.index', ['pengampu_id' => $assignment->id, 'siswa_id' => $decliningStudent->id]))
            ->assertSee('Geser tabel ke samping untuk melihat kolom lainnya.')
            ->assertViewHas('rows', fn ($rows): bool => $rows->count() === 2)
            ->assertViewHas('summary', fn (array $summary): bool => $summary['studentCount'] === 2
                && $summary['belowKktpCount'] === 1
                && $summary['incompleteCount'] === 1
                && $summary['decliningCount'] === 1);
    }

    public function test_attention_counts_a_tp_once_and_reports_below_threshold_categories_separately(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $goal = TujuanPembelajaran::factory()->for($assignment)->create([
            'kktp' => 75,
            'metode_rekap' => TujuanPembelajaran::METODE_RATA_RATA,
        ]);
        $student = Siswa::factory()->for($class)->create(['nama' => 'Alya Satu TP']);
        $assessments = collect([
            [Penilaian::KATEGORI_LINGKUP_MATERI, 'Lingkup Materi'],
            [Penilaian::KATEGORI_PTS, 'PTS'],
            [Penilaian::KATEGORI_AKHIR_SEMESTER, 'AAS'],
        ])->map(fn (array $assessment): Penilaian => Penilaian::factory()->for($assignment)->create([
            'tujuan_pembelajaran_id' => $goal->id,
            'kategori_asesmen' => $assessment[0],
            'nama' => $assessment[1],
        ]));
        foreach ([60, 70, 90] as $index => $score) {
            Nilai::factory()->create([
                'siswa_id' => $student->id,
                'penilaian_id' => $assessments[$index]->id,
                'nilai' => $score,
            ]);
        }

        $response = $this->actingAs($user)->get(route('guru.perhatian.index', ['pengampu_id' => $assignment->id]));

        $response->assertOk()
            ->assertSee('1 hasil TP di bawah KKTP (2 kategori).')
            ->assertSee('Tinjau capaian TP dan tentukan tindak lanjut pembelajaran.')
            ->assertViewHas('rows', function ($rows) use ($student): bool {
                $row = $rows->firstWhere('student.id', $student->id);

                return $row['belowThresholdCount'] === 1
                    && $row['belowCategoryCount'] === 2;
            })
            ->assertViewHas('summary', fn (array $summary): bool => $summary['belowTpStudentCount'] === 1
                && $summary['belowCategoryCount'] === 2);
    }

    public function test_unclassified_assessment_has_a_clear_follow_up_hint(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $goal = TujuanPembelajaran::factory()->for($assignment)->create();
        $student = Siswa::factory()->for($class)->create(['nama' => 'Siswa Kategori Belum Diatur']);
        $assessment = Penilaian::factory()->for($assignment)->create([
            'tujuan_pembelajaran_id' => $goal->id,
            'kategori_asesmen' => null,
        ]);
        Nilai::factory()->create([
            'siswa_id' => $student->id,
            'penilaian_id' => $assessment->id,
            'nilai' => 80,
        ]);

        $response = $this->actingAs($user)->get(route('guru.perhatian.index', ['pengampu_id' => $assignment->id]));

        $response->assertOk()
            ->assertSee('1 penilaian belum memiliki kategori asesmen.')
            ->assertSee('Periksa kategori asesmen pada penilaian terkait.');
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
            ->assertSee('1 nilai tugas belum diisi')
            ->assertSee('Data belum cukup untuk melihat tren.')
            ->assertViewHas('rows', function ($rows) use ($student): bool {
                $studentRows = $rows->where('student.id', $student->id);

                return $studentRows->count() === 1
                    && $studentRows->first()['average'] === 0.0
                    && $studentRows->first()['trend']['label'] === 'Belum cukup data'
                    && $studentRows->first()['reasons']->count() === 2;
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

    public function test_student_search_filters_the_list_while_statistics_keep_the_selected_assignment_scope(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $firstStudent = Siswa::factory()->for($class)->create(['nama' => 'Alya Dicari']);
        Siswa::factory()->for($class)->create(['nama' => 'Bima Lain']);
        Penilaian::factory()->for($assignment)->create();

        $response = $this->actingAs($user)->get(route('guru.perhatian.index', [
            'pengampu_id' => $assignment->id,
            'search' => 'Alya',
        ]));

        $response->assertOk()
            ->assertSee('Alya Dicari')
            ->assertDontSee('Bima Lain')
            ->assertSee('Statistik mengikuti tugas mengajar dan periode; pencarian hanya menyaring daftar siswa.')
            ->assertViewHas('summary', fn (array $summary): bool => $summary['studentCount'] === 2)
            ->assertViewHas('rows', fn ($rows): bool => $rows->count() === 1
                && $rows->first()['student']->id === $firstStudent->id);
    }
}
