<?php

namespace Tests\Feature\Integration;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class RaporDigitalFlowTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_complete_admin_to_printed_report_flow_uses_consistent_academic_data(): void
    {
        $context = $this->createAcademicContext();
        $student = $context['students'][0];
        $firstAssessment = $this->createAssessment($context['assignment'], 1, 'Tugas Pecahan');
        $secondAssessment = $this->createAssessment($context['assignment'], 2, 'Ulangan Pecahan');

        $this->saveScore($context['teacherUser'], $firstAssessment, $student, 60);
        $this->saveScore($context['teacherUser'], $secondAssessment, $student, 80);

        $recap = $this->actingAs($context['teacherUser'])
            ->get(route('guru.rekap-nilai.index', ['pengampu_id' => $context['assignment']->id]));
        $recap->assertOk()
            ->assertSee('Tugas Pecahan')
            ->assertSee('Ulangan Pecahan')
            ->assertSee('Siswa Lengkap')
            ->assertSee('70,00')
            ->assertViewHas('rows', function ($rows) use ($student): bool {
                $row = $rows->firstWhere('student.id', $student->id);

                return $row !== null && $row['average'] === 70.0 && $row['isComplete'];
            });

        $analysis = $this->actingAs($context['teacherUser'])->get(route('guru.analisis.index', [
            'pengampu_id' => $context['assignment']->id,
            'siswa_id' => $student->id,
        ]));
        $analysis->assertOk()
            ->assertViewHas('studentAnalysis', fn (?array $studentAnalysis): bool => $studentAnalysis !== null
                && $studentAnalysis['average'] === 70.0
                && $studentAnalysis['student']->id === $student->id);

        $attention = $this->actingAs($context['teacherUser'])->get(route('guru.perhatian.index', [
            'tahun_ajaran_id' => $context['schoolYear']->id,
        ]));
        $attention->assertOk()
            ->assertSee($student->nama)
            ->assertSee('Perlu Tindak Lanjut');

        $description = 'Menunjukkan pemahaman yang berkembang dan dapat menerapkan konsep pecahan dengan baik.';
        $this->actingAs($context['teacherUser'])
            ->put(route('guru.wali.deskripsi.update', [$context['assignment'], $student]), [
                'deskripsi_akhir' => $description,
                'status' => 'tervalidasi',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Deskripsi rapor berhasil divalidasi.');

        $report = $this->actingAs($context['teacherUser'])->get(route('guru.wali.rapor.show', [
            'siswa' => $student,
            'tahun_ajaran_id' => $context['schoolYear']->id,
        ]));
        $report->assertOk()
            ->assertSee($student->nama)
            ->assertSee($student->nis)
            ->assertSee('2025/2026')
            ->assertSee('Semester Ganjil')
            ->assertSee('V A')
            ->assertSee('Guru Kelas Integrasi')
            ->assertSee($description)
            ->assertSee('Cetak / Simpan PDF')
            ->assertViewHas('studentRow', fn (array $row): bool => $row['readyToPrint']
                && $row['subjects']->first()['average'] === 70.0);

        $print = $this->actingAs($context['teacherUser'])->get(route('guru.wali.rapor.print', [
            'siswa' => $student,
            'tahun_ajaran_id' => $context['schoolYear']->id,
        ]));
        $print->assertOk()
            ->assertSee('Laporan Hasil Belajar')
            ->assertSee('2025/2026')
            ->assertSee('Semester Ganjil')
            ->assertSee('V A')
            ->assertSee($student->nama)
            ->assertSee('70,00')
            ->assertSee($description);
    }

    public function test_incomplete_scores_remain_empty_and_block_report_validation_and_printing(): void
    {
        $context = $this->createAcademicContext(studentCount: 2);
        [$student, $otherStudent] = $context['students'];
        $firstAssessment = $this->createAssessment($context['assignment'], 1, 'Tugas Belum Lengkap');
        $secondAssessment = $this->createAssessment($context['assignment'], 2, 'Ulangan Belum Lengkap');
        $this->saveScore($context['teacherUser'], $firstAssessment, $student, 76);

        $recap = $this->actingAs($context['teacherUser'])
            ->get(route('guru.rekap-nilai.index', ['pengampu_id' => $context['assignment']->id]));
        $recap->assertOk()
            ->assertSee('Belum Lengkap')
            ->assertViewHas('rows', function ($rows) use ($student, $secondAssessment, $otherStudent): bool {
                $row = $rows->firstWhere('student.id', $student->id);
                $otherRow = $rows->firstWhere('student.id', $otherStudent->id);

                return $row !== null
                    && $row['average'] === 76.0
                    && $row['scores']->get($secondAssessment->id) === null
                    && ! $row['isComplete']
                    && $otherRow !== null
                    && $otherRow['average'] === null;
            });

        $analysis = $this->actingAs($context['teacherUser'])->get(route('guru.analisis.index', [
            'pengampu_id' => $context['assignment']->id,
            'siswa_id' => $student->id,
        ]));
        $analysis->assertOk()
            ->assertViewHas('studentAnalysis', fn (?array $studentAnalysis): bool => $studentAnalysis !== null
                && $studentAnalysis['average'] === 76.0
                && $studentAnalysis['missingCount'] === 1
                && $studentAnalysis['trend']['label'] === 'Belum cukup data');

        $this->actingAs($context['teacherUser'])
            ->put(route('guru.wali.deskripsi.update', [$context['assignment'], $student]), [
                'deskripsi_akhir' => 'Deskripsi tidak boleh tervalidasi sebelum semua nilai terisi.',
                'status' => 'tervalidasi',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Lengkapi seluruh nilai mata pelajaran sebelum memvalidasi deskripsi.');

        $report = $this->actingAs($context['teacherUser'])->get(route('guru.wali.rapor.show', [
            'siswa' => $student,
            'tahun_ajaran_id' => $context['schoolYear']->id,
        ]));
        $report->assertOk()
            ->assertSee('Rapor belum siap dicetak.')
            ->assertSee('1 nilai belum lengkap')
            ->assertSee('Deskripsi rapor belum divalidasi untuk seluruh mata pelajaran.')
            ->assertSee('Cetak Belum Tersedia');

        $this->actingAs($context['teacherUser'])->get(route('guru.wali.rapor.print', [
            'siswa' => $student,
            'tahun_ajaran_id' => $context['schoolYear']->id,
        ]))->assertRedirect(route('guru.wali.rapor.show', [
            'siswa' => $student,
            'tahun_ajaran_id' => $context['schoolYear']->id,
        ]))->assertSessionHas('error', 'Rapor belum dapat dicetak: nilai belum lengkap dan deskripsi belum divalidasi.');

        $this->assertDatabaseMissing('deskripsi', ['deskripsi_akhir' => 'Deskripsi tidak boleh tervalidasi sebelum semua nilai terisi.']);
        $this->assertDatabaseMissing('nilai', ['siswa_id' => $otherStudent->id, 'penilaian_id' => $firstAssessment->id]);
    }

    public function test_updated_zero_and_cleared_scores_are_reflected_in_recap_and_analysis(): void
    {
        $context = $this->createAcademicContext();
        $student = $context['students'][0];
        $firstAssessment = $this->createAssessment($context['assignment'], 1, 'Tugas Perubahan');
        $secondAssessment = $this->createAssessment($context['assignment'], 2, 'Ulangan Perubahan');
        $this->saveScore($context['teacherUser'], $firstAssessment, $student, 60);
        $this->saveScore($context['teacherUser'], $secondAssessment, $student, 100);

        $this->saveScore($context['teacherUser'], $firstAssessment, $student, 0);
        $this->saveScore($context['teacherUser'], $secondAssessment, $student, 80);

        $updatedRecap = $this->actingAs($context['teacherUser'])
            ->get(route('guru.rekap-nilai.index', ['pengampu_id' => $context['assignment']->id]));
        $updatedRecap->assertOk()
            ->assertViewHas('rows', fn ($rows): bool => $rows->first()['average'] === 40.0
                && $rows->first()['scores']->get($firstAssessment->id) === 0.0);

        $this->saveScore($context['teacherUser'], $secondAssessment, $student, '');

        $recap = $this->actingAs($context['teacherUser'])
            ->get(route('guru.rekap-nilai.index', ['pengampu_id' => $context['assignment']->id]));
        $recap->assertOk()
            ->assertSee('0,00')
            ->assertSee('Belum Lengkap')
            ->assertViewHas('rows', fn ($rows): bool => $rows->first()['average'] === 0.0
                && $rows->first()['scores']->get($secondAssessment->id) === null
                && ! $rows->first()['isComplete']);

        $this->assertDatabaseHas('nilai', [
            'siswa_id' => $student->id,
            'penilaian_id' => $firstAssessment->id,
            'nilai' => 0,
        ]);
        $this->assertDatabaseMissing('nilai', ['siswa_id' => $student->id, 'penilaian_id' => $secondAssessment->id]);

        $analysis = $this->actingAs($context['teacherUser'])->get(route('guru.analisis.index', [
            'pengampu_id' => $context['assignment']->id,
            'siswa_id' => $student->id,
        ]));
        $analysis->assertOk()
            ->assertViewHas('studentAnalysis', fn (?array $studentAnalysis): bool => $studentAnalysis !== null
                && $studentAnalysis['average'] === 0.0
                && $studentAnalysis['latest'] === 0.0
                && $studentAnalysis['missingCount'] === 1);
    }

    public function test_admin_and_unassigned_teacher_cannot_write_teacher_or_wali_data(): void
    {
        $context = $this->createAcademicContext();
        $student = $context['students'][0];
        $assessment = $this->createAssessment($context['assignment'], 1, 'Penilaian Hak Akses');
        $otherTeacher = Guru::factory()->create();

        $this->actingAs($context['admin'])->put(route('guru.nilai.update', $assessment), [
            'nilai' => [$student->id => 90],
        ])->assertForbidden();

        $this->actingAs($otherTeacher->user)->put(route('guru.nilai.update', $assessment), [
            'nilai' => [$student->id => 90],
        ])->assertForbidden();

        $this->actingAs($otherTeacher->user)->put(route('guru.wali.deskripsi.update', [$context['assignment'], $student]), [
            'deskripsi_akhir' => 'Pengguna ini tidak memiliki penugasan wali untuk kelas siswa.',
            'status' => 'draft',
        ])->assertForbidden();

        $this->assertDatabaseMissing('nilai', ['siswa_id' => $student->id, 'penilaian_id' => $assessment->id]);
        $this->assertDatabaseMissing('deskripsi', ['deskripsi_akhir' => 'Pengguna ini tidak memiliki penugasan wali untuk kelas siswa.']);
    }

    /**
     * @return array{
     *     admin: User,
     *     teacherUser: User,
     *     teacher: Guru,
     *     class: Kelas,
     *     subject: MataPelajaran,
     *     schoolYear: TahunAjaran,
     *     assignment: Pengampu,
     *     students: array<int, Siswa>
     * }
     */
    private function createAcademicContext(int $studentCount = 1): array
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.guru.store'), [
            'name' => 'Guru Kelas Integrasi',
            'nip' => 'NIP-INTEGRASI-001',
            'email' => 'guru-integrasi@example.test',
            'password' => 'Password123!',
        ])->assertRedirectToRoute('admin.guru.index');
        $teacher = Guru::query()->where('nip', 'NIP-INTEGRASI-001')->firstOrFail();
        $teacherUser = $teacher->user;

        $this->actingAs($admin)->post(route('admin.kelas.store'), [
            'tingkat' => 'V',
            'nama' => 'A',
            'wali_kelas_id' => $teacher->id,
        ])->assertRedirectToRoute('admin.kelas.index');
        $class = Kelas::query()->where('nama', 'A')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.mata-pelajaran.store'), [
            'kode' => 'MAT-INTEGRASI',
            'nama' => 'Matematika Integrasi',
            'kkm' => 75,
        ])->assertRedirectToRoute('admin.mata-pelajaran.index');
        $subject = MataPelajaran::query()->where('kode', 'MAT-INTEGRASI')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.tahun-ajaran.store'), [
            'tahun' => '2025/2026',
            'semester' => 'Ganjil',
            'aktif' => '1',
        ])->assertRedirectToRoute('admin.tahun-ajaran.index');
        $schoolYear = TahunAjaran::query()->where('tahun', '2025/2026')->where('semester', 'Ganjil')->firstOrFail();

        $students = [];

        for ($index = 1; $index <= $studentCount; $index++) {
            $this->actingAs($admin)->post(route('admin.siswa.store'), [
                'kelas_id' => $class->id,
                'nis' => 'NIS-INTEGRASI-'.$index,
                'nisn' => 'NISN-INTEGRASI-'.$index,
                'nama' => $index === 1 ? 'Siswa Lengkap' : 'Siswa Belum Lengkap',
                'jenis_kelamin' => 'P',
                'tanggal_lahir' => '2015-04-20',
                'tempat_lahir' => 'Makassar',
                'alamat' => 'Alamat fiktif untuk pengujian',
            ])->assertRedirectToRoute('admin.siswa.index');

            $students[] = Siswa::query()->where('nis', 'NIS-INTEGRASI-'.$index)->firstOrFail();
        }

        $this->actingAs($admin)->post(route('admin.pengampu.store'), [
            'guru_id' => $teacher->id,
            'mata_pelajaran_id' => $subject->id,
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $schoolYear->id,
        ])->assertRedirectToRoute('admin.pengampu.index');
        $assignment = Pengampu::query()
            ->where('guru_id', $teacher->id)
            ->where('kelas_id', $class->id)
            ->where('mata_pelajaran_id', $subject->id)
            ->where('tahun_ajaran_id', $schoolYear->id)
            ->firstOrFail();

        return compact('admin', 'teacherUser', 'teacher', 'class', 'subject', 'schoolYear', 'assignment', 'students');
    }

    private function createAssessment(Pengampu $assignment, int $order, string $name): Penilaian
    {
        $user = $assignment->guru->user;
        $this->actingAs($user)->post(route('guru.penilaian.store'), [
            'pengampu_id' => $assignment->id,
            'nama' => $name,
            'jenis' => 'tugas',
            'tanggal' => '2025-09-'.str_pad((string) $order, 2, '0', STR_PAD_LEFT),
            'bobot' => null,
            'urutan' => $order,
        ])->assertRedirectToRoute('guru.penilaian.index', ['pengampu_id' => $assignment->id]);

        return Penilaian::query()->where('pengampu_id', $assignment->id)->where('urutan', $order)->firstOrFail();
    }

    private function saveScore(User $teacherUser, Penilaian $assessment, Siswa $student, int|string $value): void
    {
        $this->actingAs($teacherUser)->put(route('guru.nilai.update', $assessment), [
            'nilai' => [$student->id => $value],
        ])->assertRedirect();
    }
}
