<?php

namespace Tests\Feature\Admin;

use App\Models\Guru;
use App\Models\User;
use App\Services\GuruImportService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class GuruImportPerformanceTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    /** @return array<string, array{int}> */
    public static function importBatchSizes(): array
    {
        return [
            'one_guru' => [1],
            'forty_gurus' => [40],
            'one_hundred_gurus' => [100],
        ];
    }

    #[DataProvider('importBatchSizes')]
    public function test_valid_import_batches_create_user_and_guru_records(int $rowCount): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));

        $admin = User::factory()->admin()->create();
        $file = $this->excelFile($rowCount);
        $phase = null;
        $queryMetrics = [
            'preview' => 0,
            'import' => 0,
        ];
        DB::listen(function (QueryExecuted $query) use (&$phase, &$queryMetrics): void {
            if ($phase === null) {
                return;
            }

            $queryMetrics[$phase]++;
        });

        $phase = 'preview';
        $this->actingAs($admin)->post(route('admin.guru.import.preview'), ['file' => $file])
            ->assertRedirectToRoute('admin.guru.import.create');
        $phase = null;

        $previewPage = $this->actingAs($admin)->get(route('admin.guru.import.create'));
        $previewPage->assertOk()
            ->assertSee('Total: '.$rowCount)
            ->assertSee('Valid: '.$rowCount)
            ->assertSee('Error: 0')
            ->assertSessionHas('guru_import_preview.0.valid', true);

        $phase = 'import';
        $this->actingAs($admin)->post(route('admin.guru.import.store'))
            ->assertRedirectToRoute('admin.guru.index')
            ->assertSessionHas('success', $rowCount.' data guru berhasil diimport.');
        $phase = null;
        $this->assertSame($rowCount + 1, $queryMetrics['import']);

        $gurus = Guru::query()->with('user')->orderBy('nip')->get();
        $this->assertCount($rowCount, $gurus);
        foreach ($gurus as $guru) {
            $this->assertNotNull($guru->user);
            $this->assertSame('guru', $guru->user->role);
            $this->assertSame($guru->nama, $guru->user->name);
        }

        $this->actingAs($admin)->post(route('admin.guru.import.store'))
            ->assertRedirectToRoute('admin.guru.import.create')
            ->assertSessionHas('error', 'Belum ada data valid untuk diimport.');
        $this->assertDatabaseCount('guru', $rowCount);
        $this->assertDatabaseCount('users', $rowCount + 1);
    }

    public function test_failed_batch_rolls_back_all_created_users_and_gurus(): void
    {
        User::factory()->admin()->create();

        try {
            app(GuruImportService::class)->import([
                ['name' => 'Guru Pertama', 'email' => 'pertama@example.test', 'nip' => 'NIP-DUPLIKAT'],
                ['name' => 'Guru Kedua', 'email' => 'kedua@example.test', 'nip' => 'NIP-DUPLIKAT'],
            ]);
            $this->fail('Import dengan NIP duplikat seharusnya gagal.');
        } catch (QueryException) {
            $this->assertDatabaseCount('guru', 0);
            $this->assertDatabaseCount('users', 1);
        }
    }

    private function excelFile(int $rowCount): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [['NIP', 'Nama Guru', 'Email']];

        for ($index = 1; $index <= $rowCount; $index++) {
            $number = str_pad((string) $index, 4, '0', STR_PAD_LEFT);
            $rows[] = ['NIP-'.$number, 'Guru Contoh '.$index, 'guru.'.$number.'@example.test'];
        }

        $sheet->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'guru-import-benchmark-');
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'guru-benchmark.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
