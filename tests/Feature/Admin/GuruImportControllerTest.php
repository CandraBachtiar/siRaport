<?php

namespace Tests\Feature\Admin;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class GuruImportControllerTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminTestSchema();
    }

    public function test_admin_can_open_import_page_and_download_template(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.guru.import.create'))
            ->assertOk()->assertSee('Import Data Guru');
        $this->actingAs($admin)->get(route('admin.guru.template'))
            ->assertDownload('template-import-guru.xlsx');
    }

    public function test_guru_cannot_open_import_page_or_preview_data(): void
    {
        $guru = User::factory()->guru()->create();
        $file = $this->excelFile([['001', 'Budi Santoso', 'budi@example.test']]);

        $this->actingAs($guru)->get(route('admin.guru.import.create'))->assertForbidden();
        $this->actingAs($guru)->post(route('admin.guru.import.preview'), ['file' => $file])->assertForbidden();
    }

    public function test_valid_excel_is_previewed_without_creating_records(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->excelFile([
            ['001', 'Budi Santoso', 'budi@example.test'],
            ['002', 'Siti Aminah', 'siti@example.test'],
        ]);

        $this->actingAs($admin)->post(route('admin.guru.import.preview'), ['file' => $file])
            ->assertRedirectToRoute('admin.guru.import.create')
            ->assertSessionHas('guru_import_preview.0.valid', true);

        $this->assertDatabaseMissing('guru', ['nip' => '001']);
        $this->assertDatabaseMissing('users', ['email' => 'budi@example.test']);
    }

    public function test_invalid_header_is_rejected_without_preview(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->excelFile([['001', 'Budi Santoso', 'budi@example.test']], ['Kode', 'Nama', 'Surel']);

        $this->actingAs($admin)->post(route('admin.guru.import.preview'), ['file' => $file])
            ->assertRedirectToRoute('admin.guru.import.create')
            ->assertSessionHas('guru_import_header_error', 'Format kolom Excel tidak sesuai dengan template.');
    }

    public function test_duplicate_rows_and_existing_data_are_marked_as_errors(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->guru()->create(['email' => 'existing@example.test']);
        Guru::factory()->create(['nip' => '001']);
        $file = $this->excelFile([
            ['001', 'Budi', 'budi@example.test'],
            ['001', 'Andi', 'budi@example.test'],
            ['003', 'Siti', 'existing@example.test'],
        ]);

        $this->actingAs($admin)->post(route('admin.guru.import.preview'), ['file' => $file]);
        $response = $this->actingAs($admin)->get(route('admin.guru.import.create'));

        $response->assertOk()->assertSee('NIP sudah terdaftar.')->assertSee('NIP duplikat pada file.')
            ->assertSee('Email sudah digunakan.')->assertSee('Email duplikat pada file.');
    }

    public function test_admin_can_confirm_valid_preview_and_creates_user_and_guru(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->excelFile([['001', 'Budi Santoso', 'budi@example.test']]);

        $this->actingAs($admin)->post(route('admin.guru.import.preview'), ['file' => $file]);
        $this->actingAs($admin)->post(route('admin.guru.import.store'))
            ->assertRedirectToRoute('admin.guru.index')
            ->assertSessionHas('success', '1 data guru berhasil diimport.');

        $user = User::query()->where('email', 'budi@example.test')->firstOrFail();
        $this->assertSame('guru', $user->role);
        $this->assertNotSame('password', $user->password);
        $this->assertDatabaseHas('guru', ['user_id' => $user->id, 'nip' => '001', 'nama' => 'Budi Santoso']);
        $this->assertFalse(Hash::check('password', $user->password));
    }

    public function test_import_confirmation_is_blocked_when_preview_has_errors(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->excelFile([['', '', 'email-salah']]);

        $this->actingAs($admin)->post(route('admin.guru.import.preview'), ['file' => $file]);
        $this->actingAs($admin)->post(route('admin.guru.import.store'))
            ->assertRedirectToRoute('admin.guru.import.create')
            ->assertSessionHas('error', 'Perbaiki semua data yang error sebelum melakukan import.');

        $this->assertDatabaseCount('guru', 0);
    }

    /** @param array<int, array<int, string>> $rows */
    private function excelFile(array $rows, array $headings = ['NIP', 'Nama Guru', 'Email']): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([$headings, ...$rows]);
        $path = tempnam(sys_get_temp_dir(), 'guru-import-');
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'guru.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
