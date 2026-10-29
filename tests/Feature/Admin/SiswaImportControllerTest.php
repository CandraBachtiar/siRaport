<?php

namespace Tests\Feature\Admin;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Services\SiswaImportService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class SiswaImportControllerTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_admin_can_open_siswa_import_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.siswa.import.create'))
            ->assertOk()
            ->assertSee('Import Data Siswa')
            ->assertSee('Periksa Data');
    }

    public function test_guest_and_guru_cannot_access_siswa_import(): void
    {
        $this->get(route('admin.siswa.import.create'))->assertRedirectToRoute('login');

        $guru = User::factory()->guru()->create();
        $this->actingAs($guru)->get(route('admin.siswa.import.create'))->assertForbidden();
        $this->actingAs($guru)->get(route('admin.siswa.import.template'))->assertForbidden();
    }

    public function test_admin_can_download_template_with_expected_columns_and_class_reference(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create(['tingkat' => 'VI', 'nama' => 'B']);
        $response = $this->actingAs($admin)->get(route('admin.siswa.import.template'))
            ->assertOk()
            ->assertDownload('template-import-siswa.xlsx');

        $workbook = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $this->assertSame(
            ['NIS', 'NISN', 'Nama Siswa', 'Jenis Kelamin', 'Kelas', 'Tempat Lahir', 'Tanggal Lahir', 'Alamat'],
            $workbook->getSheet(0)->rangeToArray('A1:H1')[0],
        );
        $this->assertSame(['Data Siswa', 'Daftar Kelas'], $workbook->getSheetNames());
        $this->assertSame([(string) $kelas->id, 'VI B'], $workbook->getSheet(1)->rangeToArray('A2:B2')[0]);
        $this->assertSame(NumberFormat::FORMAT_TEXT, $workbook->getSheet(0)->getStyle('A2')->getNumberFormat()->getFormatCode());
        $this->assertSame(NumberFormat::FORMAT_TEXT, $workbook->getSheet(0)->getStyle('B2')->getNumberFormat()->getFormatCode());
        $this->assertSame(NumberFormat::FORMAT_TEXT, $workbook->getSheet(0)->getStyle('E2')->getNumberFormat()->getFormatCode());
    }

    public function test_valid_excel_preview_persists_across_get_without_writing_students(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        $file = $this->excelFile([['00001', '0000000001', '<script>alert(1)</script>', 'P', (string) $kelas->id, 'Makassar', '2012-05-14', 'Alamat']]);

        $this->actingAs($admin)->from(route('admin.siswa.import.create'))
            ->post(route('admin.siswa.import.preview'), ['file' => $file])
            ->assertRedirectToRoute('admin.siswa.import.create')
            ->assertSessionHas('siswa_import_preview.0.valid', true);

        $this->actingAs($admin)->get(route('admin.siswa.import.create'))
            ->assertOk()
            ->assertSee('Total: 1')
            ->assertSee('Valid: 1')
            ->assertSee('Error: 0')
            ->assertSee('00001')
            ->assertSee($kelas->tingkat.' '.$kelas->nama.' (ID: '.$kelas->id.')')
            ->assertSee('<script>alert(1)</script>')
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->assertDatabaseCount('siswa', 0);
    }

    public function test_invalid_rows_show_duplicate_class_gender_and_date_errors(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        $file = $this->excelFile([
            ['00002', '0000000002', 'Siswa A', 'X', (string) $kelas->id, '', '31/12/2012', ''],
            ['00002', '0000000002', 'Siswa B', 'L', '99999', '', '', ''],
            ['', '', '', 'L', '', '', '', ''],
            ['00004', '0000000004', 'Siswa D', 'L', 'VI A', '', '', ''],
            ['', '', '', '', '', '', '', ''],
        ]);

        $this->actingAs($admin)->post(route('admin.siswa.import.preview'), ['file' => $file]);

        $this->actingAs($admin)->get(route('admin.siswa.import.create'))
            ->assertOk()
            ->assertSee('Total: 4')
            ->assertSee('Error: 4')
            ->assertSee('NIS duplikat pada file.')
            ->assertSee('NISN duplikat pada file.')
            ->assertSee('Jenis kelamin harus bernilai L atau P.')
            ->assertSee('NIS wajib diisi.')
            ->assertSee('Nama siswa wajib diisi.')
            ->assertSee('Kelas wajib diisi dengan ID')
            ->assertSee('Tanggal lahir harus berupa tanggal valid')
            ->assertSee('ID kelas tidak ditemukan');

        $this->actingAs($admin)->post(route('admin.siswa.import.store'))
            ->assertRedirectToRoute('admin.siswa.import.create')
            ->assertSessionHas('error', 'Perbaiki semua data yang error sebelum melakukan import.');
        $this->assertDatabaseCount('siswa', 0);
    }

    public static function importBatchSizes(): array
    {
        return ['one_student' => [1], 'thirty_students' => [30], 'one_hundred_students' => [100]];
    }

    #[DataProvider('importBatchSizes')]
    public function test_admin_can_import_valid_batch_without_per_student_query_growth(int $count): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        $file = $this->excelFile($this->validRows($count, $kelas));
        $phase = null;
        $importQueryCount = 0;
        DB::listen(function (QueryExecuted $query) use (&$phase, &$importQueryCount): void {
            if ($phase === 'import') {
                $importQueryCount++;
            }
        });

        $this->actingAs($admin)->post(route('admin.siswa.import.preview'), ['file' => $file])
            ->assertRedirectToRoute('admin.siswa.import.create');
        $this->actingAs($admin)->get(route('admin.siswa.import.create'))
            ->assertOk()
            ->assertSee('Total: '.$count)
            ->assertSee('Valid: '.$count)
            ->assertSee('Error: 0');

        $phase = 'import';
        $this->actingAs($admin)->post(route('admin.siswa.import.store'))
            ->assertRedirectToRoute('admin.siswa.index')
            ->assertSessionHas('success', $count.' data siswa berhasil diimport.');
        $phase = null;

        $this->assertSame(4, $importQueryCount);
        $this->assertDatabaseCount('siswa', $count);
        $this->assertDatabaseCount('users', 1);
        $firstStudent = Siswa::query()->with('kelas')->where('nis', '00001')->firstOrFail();
        $this->assertSame($kelas->id, $firstStudent->kelas->id);
        $this->assertSame('0000000001', $firstStudent->nisn);
    }

    public function test_duplicate_nis_and_nisn_already_in_database_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        $existing = Siswa::factory()->for($kelas)->create(['nis' => 'NIS-ADA', 'nisn' => 'NISN-ADA']);
        $file = $this->excelFile([
            ['NIS-ADA', 'NISN-ADA', 'Siswa Baru', 'L', (string) $kelas->id, '', '', ''],
        ]);

        $this->actingAs($admin)->post(route('admin.siswa.import.preview'), ['file' => $file]);
        $this->actingAs($admin)->get(route('admin.siswa.import.create'))
            ->assertSee('NIS sudah terdaftar.')
            ->assertSee('NISN sudah terdaftar.');
        $this->actingAs($admin)->post(route('admin.siswa.import.store'))
            ->assertSessionHas('error', 'Perbaiki semua data yang error sebelum melakukan import.');

        $this->assertDatabaseCount('siswa', 1);
        $this->assertDatabaseHas('siswa', ['id' => $existing->id, 'nama' => $existing->nama, 'kelas_id' => $kelas->id]);
    }

    public function test_import_without_preview_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.siswa.import.store'))
            ->assertRedirectToRoute('admin.siswa.import.create')
            ->assertSessionHas('error', 'Preview tidak tersedia. Periksa kembali file yang akan diimpor.');
        $this->assertDatabaseCount('siswa', 0);
    }

    public function test_invalid_new_upload_clears_previous_preview(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        $file = $this->excelFile($this->validRows(1, $kelas));

        $this->actingAs($admin)->post(route('admin.siswa.import.preview'), ['file' => $file]);
        $this->actingAs($admin)->get(route('admin.siswa.import.create'))->assertSee('Total: 1');
        $this->actingAs($admin)
            ->from(route('admin.siswa.import.create'))
            ->post(route('admin.siswa.import.preview'), [])
            ->assertRedirect(route('admin.siswa.import.create'))
            ->assertSessionHasErrors('file');

        $this->actingAs($admin)->post(route('admin.siswa.import.store'))
            ->assertRedirectToRoute('admin.siswa.import.create')
            ->assertSessionHas('error', 'Preview tidak tersedia. Periksa kembali file yang akan diimpor.');
        $this->assertDatabaseCount('siswa', 0);
    }

    public function test_non_excel_upload_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->createWithContent('siswa.csv', 'NIS,Nama Siswa');

        $this->actingAs($admin)->from(route('admin.siswa.import.create'))
            ->post(route('admin.siswa.import.preview'), ['file' => $file])
            ->assertRedirectToRoute('admin.siswa.import.create')
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('siswa', 0);
    }

    public function test_upload_larger_than_five_megabytes_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->create('siswa.xlsx', 5121, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($admin)->from(route('admin.siswa.import.create'))
            ->post(route('admin.siswa.import.preview'), ['file' => $file])
            ->assertRedirectToRoute('admin.siswa.import.create')
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('siswa', 0);
    }

    public function test_successful_import_cannot_be_replayed(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        $file = $this->excelFile($this->validRows(1, $kelas));

        $this->actingAs($admin)->post(route('admin.siswa.import.preview'), ['file' => $file]);
        $this->actingAs($admin)->post(route('admin.siswa.import.store'))
            ->assertRedirectToRoute('admin.siswa.index');
        $this->actingAs($admin)->post(route('admin.siswa.import.store'))
            ->assertRedirectToRoute('admin.siswa.import.create')
            ->assertSessionHas('error', 'Preview tidak tersedia. Periksa kembali file yang akan diimpor.');

        $this->assertDatabaseCount('siswa', 1);
    }

    public function test_preview_cannot_be_used_by_a_different_admin_session(): void
    {
        $firstAdmin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        $file = $this->excelFile($this->validRows(1, $kelas));

        $this->actingAs($firstAdmin)->post(route('admin.siswa.import.preview'), ['file' => $file]);
        $this->actingAs($otherAdmin)->post(route('admin.siswa.import.store'))
            ->assertRedirectToRoute('admin.siswa.import.create')
            ->assertSessionHas('error', 'Preview tidak tersedia. Periksa kembali file yang akan diimpor.');

        $this->assertDatabaseCount('siswa', 0);
    }

    public function test_transaction_rolls_back_when_bulk_insert_fails(): void
    {
        $kelas = Kelas::factory()->create();
        $row = [
            'kelas_id' => $kelas->id,
            'nis' => 'NIS-DUPLIKAT',
            'nisn' => null,
            'name' => 'Siswa Gagal',
            'gender' => 'L',
            'birth_place' => null,
            'birth_date' => null,
            'address' => null,
            'valid' => true,
        ];

        try {
            app(SiswaImportService::class)->import([$row, $row]);
            $this->fail('Insert dengan NIS duplikat seharusnya gagal.');
        } catch (QueryException) {
            $this->assertDatabaseCount('siswa', 0);
        }
    }

    public function test_database_conflict_after_preview_does_not_change_existing_student(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        $file = $this->excelFile([['NIS-BARU', '', 'Siswa Diubah', 'P', (string) $kelas->id, '', '', '']]);

        $this->actingAs($admin)->post(route('admin.siswa.import.preview'), ['file' => $file])
            ->assertRedirectToRoute('admin.siswa.import.create');
        $existing = Siswa::factory()->for($kelas)->create(['nis' => 'NIS-BARU', 'nisn' => null, 'nama' => 'Siswa Lama']);
        $this->actingAs($admin)->post(route('admin.siswa.import.store'))
            ->assertRedirectToRoute('admin.siswa.import.create')
            ->assertSessionHasErrors('import');

        $this->assertDatabaseCount('siswa', 1);
        $this->assertDatabaseHas('siswa', ['id' => $existing->id, 'nis' => 'NIS-BARU', 'nama' => 'Siswa Lama']);
    }

    public function test_excel_serial_birth_date_is_normalized_for_preview(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        $excelSerial = Date::PHPToExcel(new \DateTimeImmutable('2012-05-14'));
        $file = $this->excelFile([['NIS-DATE', '', 'Siswa Tanggal', 'P', (string) $kelas->id, '', $excelSerial, '']]);

        $this->actingAs($admin)->post(route('admin.siswa.import.preview'), ['file' => $file]);

        $this->actingAs($admin)->get(route('admin.siswa.import.create'))
            ->assertOk()
            ->assertSee('2012-05-14')
            ->assertSessionHas('siswa_import_preview.0.birth_date', '2012-05-14')
            ->assertSessionHas('siswa_import_preview.0.valid', true);
    }

    public function test_import_rechecks_a_class_removed_after_preview(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        $file = $this->excelFile($this->validRows(1, $kelas));

        $this->actingAs($admin)->post(route('admin.siswa.import.preview'), ['file' => $file]);
        $kelas->delete();
        $this->actingAs($admin)->post(route('admin.siswa.import.store'))
            ->assertRedirectToRoute('admin.siswa.import.create')
            ->assertSessionHasErrors('import');

        $this->assertDatabaseCount('siswa', 0);
    }

    public function test_class_ids_disambiguate_classes_with_the_same_display_name(): void
    {
        $admin = User::factory()->admin()->create();
        $firstClass = Kelas::factory()->create(['tingkat' => 'VI', 'nama' => 'A']);
        $secondClass = Kelas::factory()->create(['tingkat' => 'VI', 'nama' => 'A']);
        $file = $this->excelFile([['NIS-PILIHAN', '', 'Siswa Pilihan', 'P', (string) $secondClass->id, '', '', '']]);

        $this->actingAs($admin)->post(route('admin.siswa.import.preview'), ['file' => $file]);
        $this->actingAs($admin)->get(route('admin.siswa.import.create'))
            ->assertSee('VI A (ID: '.$secondClass->id.')')
            ->assertDontSee('VI A (ID: '.$firstClass->id.')');
        $this->actingAs($admin)->post(route('admin.siswa.import.store'))
            ->assertRedirectToRoute('admin.siswa.index');

        $this->assertDatabaseHas('siswa', ['nis' => 'NIS-PILIHAN', 'kelas_id' => $secondClass->id]);
    }

    /** @return array<int, array<int, string>> */
    private function validRows(int $count, Kelas $kelas): array
    {
        $rows = [];
        for ($index = 1; $index <= $count; $index++) {
            $number = str_pad((string) $index, 5, '0', STR_PAD_LEFT);
            $rows[] = [$number, '00000'.$number, 'Siswa Contoh '.$index, 'L', (string) $kelas->id, 'Makassar', '2012-05-14', 'Alamat '.$index];
        }

        return $rows;
    }

    /** @param array<int, array<int, mixed>> $rows */
    private function excelFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $headings = ['NIS', 'NISN', 'Nama Siswa', 'Jenis Kelamin', 'Kelas', 'Tempat Lahir', 'Tanggal Lahir', 'Alamat'];
        $sheet->fromArray([$headings, ...$rows]);
        $path = tempnam(sys_get_temp_dir(), 'siswa-import-');
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'siswa.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
