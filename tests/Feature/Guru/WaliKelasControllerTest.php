<?php

namespace Tests\Feature\Guru;

use App\Models\Deskripsi;
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

class WaliKelasControllerTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_students_page_displays_only_students_from_the_wali_class(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $ownClass = Kelas::factory()->create(['wali_kelas_id' => $guru->id]);
        $otherClass = Kelas::factory()->create();
        Siswa::factory()->for($ownClass)->create(['nama' => 'Siswa Kelas Wali']);
        Siswa::factory()->for($otherClass)->create(['nama' => 'Siswa Kelas Lain']);

        $response = $this->actingAs($user)->get(route('guru.wali.siswa.index'));

        $response->assertOk()
            ->assertSee('Siswa Kelas Wali')
            ->assertDontSee('Siswa Kelas Lain');
    }

    public function test_wali_cannot_open_a_student_from_another_class(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        Kelas::factory()->create(['wali_kelas_id' => $guru->id]);
        $otherStudent = Siswa::factory()->create();

        $response = $this->actingAs($user)->get(route('guru.wali.siswa.show', $otherStudent));

        $response->assertNotFound();
    }

    public function test_moved_student_keeps_old_score_but_disappears_from_old_period_views(): void
    {
        $admin = User::factory()->admin()->create();
        $waliUser = User::factory()->guru()->create();
        $wali = Guru::factory()->for($waliUser)->create();
        $subjectTeacher = Guru::factory()->create();
        $oldClass = Kelas::factory()->create(['wali_kelas_id' => $wali->id]);
        $newClass = Kelas::factory()->create();
        $schoolYear = TahunAjaran::factory()->create(['tahun' => '2025/2026', 'semester' => 'Ganjil']);
        $student = Siswa::factory()->for($oldClass)->create(['nama' => 'Siswa Pindah Histori']);
        $subject = MataPelajaran::factory()->create(['nama' => 'Matematika Riwayat']);
        $assignment = Pengampu::factory()->create([
            'guru_id' => $subjectTeacher->id,
            'mata_pelajaran_id' => $subject->id,
            'kelas_id' => $oldClass->id,
            'tahun_ajaran_id' => $schoolYear->id,
        ]);
        $assessment = Penilaian::factory()->for($assignment)->create(['nama' => 'Penilaian Riwayat']);
        $score = Nilai::factory()->create([
            'siswa_id' => $student->id,
            'penilaian_id' => $assessment->id,
            'nilai' => 88,
        ]);
        $payload = [
            'kelas_id' => $newClass->id,
            'nis' => $student->nis,
            'nisn' => $student->nisn,
            'nama' => $student->nama,
            'jenis_kelamin' => $student->jenis_kelamin,
            'tanggal_lahir' => $student->tanggal_lahir?->toDateString(),
            'tempat_lahir' => $student->tempat_lahir,
            'alamat' => $student->alamat,
        ];

        $this->actingAs($admin)->put(route('admin.siswa.update', $student), $payload)
            ->assertRedirectToRoute('admin.siswa.index');

        $this->assertDatabaseHas('nilai', [
            'id' => $score->id,
            'siswa_id' => $student->id,
            'penilaian_id' => $assessment->id,
            'nilai' => 88,
        ]);

        $this->actingAs($subjectTeacher->user)->get(route('guru.rekap-nilai.index', ['pengampu_id' => $assignment->id]))
            ->assertOk()
            ->assertDontSee('Siswa Pindah Histori');
        $this->actingAs($subjectTeacher->user)->get(route('guru.analisis.index', ['pengampu_id' => $assignment->id]))
            ->assertOk()
            ->assertDontSee('Siswa Pindah Histori');
        $this->actingAs($waliUser)->get(route('guru.wali.rapor.show', [
            'siswa' => $student,
            'tahun_ajaran_id' => $schoolYear->id,
        ]))->assertNotFound();
    }

    public function test_progress_combines_real_scores_from_all_subjects(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create(['wali_kelas_id' => $guru->id]);
        $year = TahunAjaran::factory()->create(['aktif' => true]);
        $student = Siswa::factory()->for($class)->create(['nama' => 'Alya Lintas Mapel']);
        $math = Pengampu::factory()->create([
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $year->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create(['nama' => 'Matematika', 'kkm' => 75])->id,
        ]);
        $science = Pengampu::factory()->create([
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $year->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create(['nama' => 'IPA', 'kkm' => 75])->id,
        ]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => Penilaian::factory()->for($math)->create()->id, 'nilai' => 80]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => Penilaian::factory()->for($science)->create()->id, 'nilai' => 60]);

        $response = $this->actingAs($user)->get(route('guru.wali.perkembangan.index', [
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $year->id,
            'siswa_id' => $student->id,
        ]));

        $response->assertOk()
            ->assertSee('Alya Lintas Mapel')
            ->assertSee('Matematika')
            ->assertSee('IPA')
            ->assertViewHas('studentRow', fn (array $row): bool => $row['overallAverage'] === 70.0 && $row['belowKkmCount'] === 1);
    }

    public function test_attention_page_aggregates_indicators_across_subjects(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create(['wali_kelas_id' => $guru->id]);
        $year = TahunAjaran::factory()->create(['aktif' => true]);
        $student = Siswa::factory()->for($class)->create(['nama' => 'Bima Perlu Dukungan']);
        $assignment = Pengampu::factory()->create([
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $year->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create(['kkm' => 75])->id,
        ]);
        $firstAssessment = Penilaian::factory()->for($assignment)->create(['urutan' => 1]);
        Penilaian::factory()->for($assignment)->create(['urutan' => 2]);
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $firstAssessment->id, 'nilai' => 60]);

        $response = $this->actingAs($user)->get(route('guru.wali.perhatian.index', [
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $year->id,
        ]));

        $response->assertOk()
            ->assertSee('Bima Perlu Dukungan')
            ->assertSee('1 mata pelajaran di bawah KKM')
            ->assertSee('1 nilai belum lengkap')
            ->assertSee('1 mata pelajaran belum cukup data untuk menentukan tren');
    }

    public function test_teacher_and_wali_show_the_same_trend_for_the_same_assessment_scores(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create(['wali_kelas_id' => $guru->id]);
        $year = TahunAjaran::factory()->create(['aktif' => true]);
        $subject = MataPelajaran::factory()->create(['nama' => 'Matematika Konsistensi']);
        $assignment = Pengampu::factory()->for($guru)->create([
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $year->id,
            'mata_pelajaran_id' => $subject->id,
        ]);
        $student = Siswa::factory()->for($class)->create();
        $createdOutOfOrder = [
            ['tanggal' => '2026-10-05', 'urutan' => 1, 'nilai' => 90],
            ['tanggal' => '2026-10-04', 'urutan' => 2, 'nilai' => 70],
            ['tanggal' => '2026-10-03', 'urutan' => 3, 'nilai' => 100],
            ['tanggal' => '2026-10-02', 'urutan' => 4, 'nilai' => 100],
            ['tanggal' => '2026-10-01', 'urutan' => 5, 'nilai' => 0],
        ];

        foreach ($createdOutOfOrder as $scoreData) {
            $assessment = Penilaian::factory()->for($assignment)->create([
                'tanggal' => $scoreData['tanggal'],
                'urutan' => $scoreData['urutan'],
                'nama' => 'Penilaian '.$scoreData['urutan'],
            ]);
            Nilai::factory()->create([
                'siswa_id' => $student->id,
                'penilaian_id' => $assessment->id,
                'nilai' => $scoreData['nilai'],
            ]);
        }

        $teacherResponse = $this->actingAs($user)->get(route('guru.analisis.index', [
            'pengampu_id' => $assignment->id,
            'siswa_id' => $student->id,
        ]));
        $waliResponse = $this->actingAs($user)->get(route('guru.wali.perkembangan.index', [
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $year->id,
            'siswa_id' => $student->id,
        ]));

        $teacherResponse->assertOk()
            ->assertSee('Penilaian 5')
            ->assertViewHas('studentAnalysis', fn (array $analysis): bool => $analysis['series']->pluck('value')->all() === [0.0, 100.0, 100.0, 70.0, 90.0]
                && $analysis['trend']['label'] === 'Meningkat'
                && $analysis['trend']['difference'] === 30.0);
        $waliResponse->assertOk()
            ->assertSee('Periode analisis: '.$year->tahun)
            ->assertViewHas('studentRow', function (array $row): bool {
                $this->assertSame([0.0, 100.0, 100.0, 70.0, 90.0], $row['progressSeries']->pluck('value')->all());
                $this->assertSame('Meningkat', $row['subjects']->first()['trend']['label']);
                $this->assertSame(30.0, $row['subjects']->first()['trend']['difference']);

                return true;
            });
    }

    public function test_subject_average_chart_keeps_valid_zero_and_missing_data_distinct(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create(['wali_kelas_id' => $guru->id]);
        $year = TahunAjaran::factory()->create(['aktif' => true]);
        $student = Siswa::factory()->for($class)->create();
        $mathAssignment = Pengampu::factory()->create([
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $year->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create(['nama' => 'Matematika Nol'])->id,
        ]);
        $scienceAssignment = Pengampu::factory()->create([
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $year->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create(['nama' => 'IPA Kosong'])->id,
        ]);
        $mathAssessment = Penilaian::factory()->for($mathAssignment)->create();
        Penilaian::factory()->for($scienceAssignment)->create();
        Nilai::factory()->create([
            'siswa_id' => $student->id,
            'penilaian_id' => $mathAssessment->id,
            'nilai' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('guru.wali.perkembangan.index', [
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $year->id,
            'siswa_id' => $student->id,
        ]));

        $response->assertOk()
            ->assertSee('Matematika Nol')
            ->assertSee('IPA Kosong')
            ->assertViewHas('studentRow', function (array $row): bool {
                $valuesBySubject = $row['subjectSeries']->keyBy('label')->map(fn (array $item): ?float => $item['value']);

                $this->assertSame(0.0, $valuesBySubject->get('Matematika Nol'));
                $this->assertNull($valuesBySubject->get('IPA Kosong'));

                return true;
            });
    }

    public function test_print_is_blocked_until_scores_and_descriptions_are_complete(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create(['wali_kelas_id' => $guru->id]);
        $year = TahunAjaran::factory()->create(['aktif' => true]);
        $student = Siswa::factory()->for($class)->create();
        $assignment = Pengampu::factory()->create(['kelas_id' => $class->id, 'tahun_ajaran_id' => $year->id]);
        $assessment = Penilaian::factory()->for($assignment)->create();
        Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessment->id]);

        $response = $this->actingAs($user)->get(route('guru.wali.rapor.print', [
            'siswa' => $student,
            'tahun_ajaran_id' => $year->id,
        ]));

        $response->assertRedirectToRoute('guru.wali.rapor.show', [
            'siswa' => $student,
            'tahun_ajaran_id' => $year->id,
        ])->assertSessionHas('error', 'Lengkapi nilai dan validasi seluruh deskripsi sebelum mencetak rapor.');
    }

    public function test_ready_report_can_be_opened_in_the_print_view(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create(['nama' => 'Ibu Wali']);
        $class = Kelas::factory()->create(['wali_kelas_id' => $guru->id]);
        $year = TahunAjaran::factory()->create(['aktif' => true]);
        $student = Siswa::factory()->for($class)->create(['nama' => 'Citra Siap Cetak']);
        $assignment = Pengampu::factory()->create(['kelas_id' => $class->id, 'tahun_ajaran_id' => $year->id]);
        $score = Nilai::factory()->create([
            'siswa_id' => $student->id,
            'penilaian_id' => Penilaian::factory()->for($assignment)->create()->id,
            'nilai' => 88,
        ]);
        Deskripsi::factory()->for($score)->create([
            'deskripsi_akhir' => 'Menunjukkan capaian yang konsisten dan sangat baik.',
            'status' => 'tervalidasi',
        ]);

        $response = $this->actingAs($user)->get(route('guru.wali.rapor.print', [
            'siswa' => $student,
            'tahun_ajaran_id' => $year->id,
        ]));

        $response->assertOk()
            ->assertSee('Citra Siap Cetak')
            ->assertSee('Menunjukkan capaian yang konsisten dan sangat baik.')
            ->assertSee('Ibu Wali');
    }

    public function test_report_preview_displays_identity_subject_and_completion_status(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create(['wali_kelas_id' => $guru->id, 'tingkat' => 'VIII', 'nama' => 'C']);
        $year = TahunAjaran::factory()->create(['aktif' => true]);
        $student = Siswa::factory()->for($class)->create(['nama' => 'Dina Preview']);
        $assignment = Pengampu::factory()->create([
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $year->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create(['nama' => 'Bahasa Indonesia'])->id,
        ]);
        Nilai::factory()->create([
            'siswa_id' => $student->id,
            'penilaian_id' => Penilaian::factory()->for($assignment)->create()->id,
            'nilai' => 82,
        ]);

        $response = $this->actingAs($user)->get(route('guru.wali.rapor.show', [
            'siswa' => $student,
            'tahun_ajaran_id' => $year->id,
        ]));

        $response->assertOk()
            ->assertSee('Dina Preview')
            ->assertSee('VIII C')
            ->assertSee('Bahasa Indonesia')
            ->assertSee('Rapor belum siap dicetak.');
    }

    public function test_profile_displays_the_authenticated_wali_identity(): void
    {
        $user = User::factory()->guru()->create(['email' => 'wali@example.test']);
        $guru = Guru::factory()->for($user)->create(['nama' => 'Bapak Wali Profil', 'nip' => '198765']);
        Kelas::factory()->create(['wali_kelas_id' => $guru->id, 'tingkat' => 'IX', 'nama' => 'B']);

        $response = $this->actingAs($user)->get(route('guru.wali.profil.show'));

        $response->assertOk()
            ->assertSee('Bapak Wali Profil')
            ->assertSee('198765')
            ->assertSee('wali@example.test')
            ->assertSee('IX B');
    }

    public function test_filter_rejects_a_school_year_without_an_assignment_in_the_wali_class(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create(['wali_kelas_id' => $guru->id]);
        $ownYear = TahunAjaran::factory()->create();
        $otherYear = TahunAjaran::factory()->create();
        Pengampu::factory()->create(['kelas_id' => $class->id, 'tahun_ajaran_id' => $ownYear->id]);

        $response = $this->actingAs($user)->get(route('guru.wali.rapor.index', [
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $otherYear->id,
        ]));

        $response->assertNotFound();
    }
}
