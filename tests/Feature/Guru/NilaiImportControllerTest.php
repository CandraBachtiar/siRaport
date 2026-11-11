<?php

namespace Tests\Feature\Guru;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class NilaiImportControllerTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminTestSchema();
    }

    public function test_teacher_can_download_template_for_owned_assessment(): void
    {
        [$user, $assessment, $students] = $this->assessmentContext();

        $response = $this->actingAs($user)->get(route('guru.nilai.template', $assessment));

        $response->assertOk()->assertDownload('template-nilai-'.$assessment->id.'.xlsx');
        $this->assertCount(2, $students);
    }

    public function test_import_preview_preserves_blank_scores_and_saves_zero_by_student_id(): void
    {
        [$user, $assessment, $students] = $this->assessmentContext();
        Nilai::factory()->create(['siswa_id' => $students[0]->id, 'penilaian_id' => $assessment->id, 'nilai' => 72]);
        $file = $this->excelFile([
            [$assessment->id, $students[0]->id, $students[0]->nis, 'Nama sengaja diubah', ''],
            [$assessment->id, $students[1]->id, $students[1]->nis, $students[1]->nama, '0'],
        ]);
        $inputUrl = route('guru.nilai.index', [
            'tahun_ajaran_id' => $assessment->pengampu->tahun_ajaran_id,
            'mata_pelajaran_id' => $assessment->pengampu->mata_pelajaran_id,
            'kelas_id' => $assessment->pengampu->kelas_id,
            'penilaian_id' => $assessment->id,
        ]);

        $this->actingAs($user)->from($inputUrl)->post(route('guru.nilai.import.preview', $assessment), ['file' => $file])
            ->assertRedirect($inputUrl)
            ->assertSessionHas('guru.nilai_import_preview.'.$assessment->id.'.can_import', true);

        $preview = $this->actingAs($user)->get($inputUrl);
        $preview->assertOk()
            ->assertSee('Pratinjau impor nilai')
            ->assertSee('Nilai kosong; nilai lama tetap dipertahankan.')
            ->assertSee($students[0]->nama)
            ->assertDontSee('Nama sengaja diubah')
            ->assertSee('0');
        $this->assertDatabaseHas('nilai', ['siswa_id' => $students[0]->id, 'penilaian_id' => $assessment->id, 'nilai' => 72]);
        $this->assertDatabaseMissing('nilai', ['siswa_id' => $students[1]->id, 'penilaian_id' => $assessment->id]);

        $this->actingAs($user)->post(route('guru.nilai.import.store', $assessment))
            ->assertRedirect($inputUrl)
            ->assertSessionHas('success', '1 nilai berhasil diimpor.');
        $this->assertDatabaseHas('nilai', ['siswa_id' => $students[0]->id, 'penilaian_id' => $assessment->id, 'nilai' => 72]);
        $this->assertDatabaseHas('nilai', ['siswa_id' => $students[1]->id, 'penilaian_id' => $assessment->id, 'nilai' => 0]);
        $this->assertDatabaseCount('nilai', 2);

        $this->actingAs($user)->from($inputUrl)->post(route('guru.nilai.import.preview', $assessment), ['file' => $file])
            ->assertRedirect($inputUrl);
        $this->actingAs($user)->post(route('guru.nilai.import.store', $assessment))
            ->assertRedirect($inputUrl);
        $this->assertDatabaseCount('nilai', 2);

        $this->actingAs($user)->post(route('guru.nilai.import.store', $assessment))
            ->assertRedirect($inputUrl)
            ->assertSessionHas('error', 'Pratinjau tidak ditemukan. Unggah ulang file untuk membuat pratinjau.');
        $this->assertDatabaseCount('nilai', 2);
    }

    public function test_invalid_duplicate_wrong_assessment_and_outside_class_rows_cannot_be_imported(): void
    {
        [$user, $assessment, $students] = $this->assessmentContext();
        $outsider = Siswa::factory()->create();
        $file = $this->excelFile([
            [$assessment->id, $students[0]->id, $students[0]->nis, $students[0]->nama, 85],
            [$assessment->id, $students[0]->id, $students[0]->nis, $students[0]->nama, 86],
            [$assessment->id + 1, $students[1]->id, $students[1]->nis, $students[1]->nama, 87],
            [$assessment->id, $outsider->id, $outsider->nis, $outsider->nama, 101],
        ]);

        $this->actingAs($user)->post(route('guru.nilai.import.preview', $assessment), ['file' => $file]);
        $this->actingAs($user)->get(route('guru.nilai.index', [
            'tahun_ajaran_id' => $assessment->pengampu->tahun_ajaran_id,
            'mata_pelajaran_id' => $assessment->pengampu->mata_pelajaran_id,
            'kelas_id' => $assessment->pengampu->kelas_id,
            'penilaian_id' => $assessment->id,
        ]))->assertOk()
            ->assertSee('ID siswa duplikat pada file.')
            ->assertSee('ID penilaian tidak sesuai dengan penilaian yang dipilih.')
            ->assertSee('ID siswa tidak ditemukan pada kelas penilaian ini.')
            ->assertSee('Nilai maksimal 100.');

        $this->actingAs($user)->post(route('guru.nilai.import.store', $assessment))
            ->assertSessionHas('error', 'Perbaiki semua baris pada pratinjau dan unggah ulang sebelum menyimpan.');
        $this->assertDatabaseCount('nilai', 0);
    }

    public function test_invalid_new_upload_clears_previous_preview(): void
    {
        [$user, $assessment, $students] = $this->assessmentContext();
        $file = $this->excelFile([
            [$assessment->id, $students[0]->id, $students[0]->nis, $students[0]->nama, 88],
        ]);
        $inputUrl = route('guru.nilai.index', [
            'tahun_ajaran_id' => $assessment->pengampu->tahun_ajaran_id,
            'mata_pelajaran_id' => $assessment->pengampu->mata_pelajaran_id,
            'kelas_id' => $assessment->pengampu->kelas_id,
            'penilaian_id' => $assessment->id,
        ]);

        $this->actingAs($user)->from($inputUrl)->post(route('guru.nilai.import.preview', $assessment), ['file' => $file]);
        $this->actingAs($user)->from($inputUrl)->post(route('guru.nilai.import.preview', $assessment), [])
            ->assertRedirect($inputUrl)
            ->assertSessionHasErrors('file');
        $this->actingAs($user)->post(route('guru.nilai.import.store', $assessment))
            ->assertSessionHas('error', 'Pratinjau tidak ditemukan. Unggah ulang file untuk membuat pratinjau.');
        $this->assertDatabaseCount('nilai', 0);
    }

    public function test_teacher_cannot_download_or_import_another_teachers_assessment(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        Pengampu::factory()->for($guru)->create();
        [, $assessment, $students] = $this->assessmentContext();
        $file = $this->excelFile([
            [$assessment->id, $students[0]->id, $students[0]->nis, $students[0]->nama, 88],
        ]);

        $this->actingAs($user)->get(route('guru.nilai.template', $assessment))->assertForbidden();
        $this->actingAs($user)->post(route('guru.nilai.import.preview', $assessment), ['file' => $file])->assertForbidden();
        $this->actingAs($user)->post(route('guru.nilai.import.store', $assessment))->assertForbidden();
        $this->assertDatabaseCount('nilai', 0);
    }

    /** @return array{User, Penilaian, Collection<int, Siswa>} */
    private function assessmentContext(): array
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create();
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        $assessment = Penilaian::factory()->for($assignment)->create(['nama' => 'Penilaian Impor']);
        $students = Siswa::factory()->count(2)->for($class)->create();

        return [$user, $assessment, $students];
    }

    /** @param list<array<int, int|string|null>> $rows */
    private function excelFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['ID Penilaian', 'ID Siswa', 'NIS', 'Nama Siswa', 'Nilai'],
            ...$rows,
        ]);
        $path = tempnam(sys_get_temp_dir(), 'nilai-import-');
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'nilai.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
