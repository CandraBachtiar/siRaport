<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_missing_page_renders_the_branded_accessible_404_state(): void
    {
        $response = $this->get('/halaman-yang-tidak-tersedia');

        $response->assertNotFound()
            ->assertSee('RaporKu')
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('Lewati ke konten utama')
            ->assertSee('id="main-content"', false);
    }

    public function test_forbidden_page_renders_the_branded_403_state(): void
    {
        $guru = User::factory()->guru()->create();

        $response = $this->actingAs($guru)->get(route('admin.dashboard'));

        $response->assertForbidden()
            ->assertSee('Akses tidak diizinkan')
            ->assertSee('Kembali ke Dashboard');
    }
}
