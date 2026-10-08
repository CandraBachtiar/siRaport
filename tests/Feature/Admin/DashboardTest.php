<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirectToRoute('login');
    }

    public function test_guru_is_forbidden_from_admin_dashboard(): void
    {
        $guru = User::factory()->guru()->create();

        $response = $this->actingAs($guru)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_dashboard_and_account_information(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Administrator Utama',
            'email' => 'admin@example.test',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Administrator Utama')
            ->assertSee('admin@example.test')
            ->assertSee('Role')
            ->assertSee('Administrator')
            ->assertSee('Jumlah Guru')
            ->assertSee('Jumlah Siswa')
            ->assertSee('Jumlah Kelas')
            ->assertSee('Mata Pelajaran')
            ->assertSee('Persiapan Data Akademik')
            ->assertSee('Tahun ajaran aktif sudah tersedia')
            ->assertSee('href="#main-content"', false)
            ->assertSee('id="main-content"', false)
            ->assertSee('aria-controls="dashboard-sidebar"', false)
            ->assertHeader('Cache-Control');
    }

    public function test_dashboard_escapes_admin_information(): void
    {
        $dangerousName = '<script>alert("xss")</script>';
        $admin = User::factory()->admin()->create([
            'name' => $dangerousName,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSee($dangerousName)
            ->assertDontSee($dangerousName, false);
    }
}
