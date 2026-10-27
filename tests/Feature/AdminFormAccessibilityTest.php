<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class AdminFormAccessibilityTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_guru_form_links_validation_error_to_the_invalid_field(): void
    {
        $this->assertInvalidCreateFieldIsAccessible('admin.guru.create', 'admin.guru.store', 'name');
    }

    public function test_guru_import_form_links_validation_error_to_the_file_field(): void
    {
        $this->assertInvalidCreateFieldIsAccessible('admin.guru.import.create', 'admin.guru.import.preview', 'file');
    }

    public function test_siswa_form_links_validation_error_to_the_invalid_field(): void
    {
        $this->assertInvalidCreateFieldIsAccessible('admin.siswa.create', 'admin.siswa.store', 'nis');
    }

    public function test_mata_pelajaran_form_links_validation_error_to_the_invalid_field(): void
    {
        $this->assertInvalidCreateFieldIsAccessible('admin.mata-pelajaran.create', 'admin.mata-pelajaran.store', 'kode');
    }

    public function test_kelas_form_links_validation_error_to_the_invalid_field(): void
    {
        $this->assertInvalidCreateFieldIsAccessible('admin.kelas.create', 'admin.kelas.store', 'tingkat');
    }

    public function test_pengampu_form_links_validation_error_to_the_invalid_field(): void
    {
        $this->assertInvalidCreateFieldIsAccessible('admin.pengampu.create', 'admin.pengampu.store', 'guru_id');
    }

    public function test_tahun_ajaran_form_links_validation_error_to_the_invalid_field(): void
    {
        $this->assertInvalidCreateFieldIsAccessible('admin.tahun-ajaran.create', 'admin.tahun-ajaran.store', 'tahun');
    }

    private function assertInvalidCreateFieldIsAccessible(string $formRoute, string $storeRoute, string $field): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->from(route($formRoute))
            ->followingRedirects()
            ->post(route($storeRoute), []);

        $response->assertOk()
            ->assertSee('id="'.$field.'"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="'.$field.'-error"', false)
            ->assertSee('id="'.$field.'-error"', false);
    }
}
