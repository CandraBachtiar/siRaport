<?php

namespace Tests\Feature\Guru;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Pengampu;
use App\Models\User;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class WorkspaceAccessTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_guru_without_an_assignment_cannot_access_the_mapel_workspace_route(): void
    {
        $user = User::factory()->guru()->create();
        Guru::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('guru.mapel.dashboard'));

        $response->assertForbidden();
    }

    public function test_guru_with_an_assignment_can_access_the_mapel_workspace_route(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        Pengampu::factory()->for($guru)->create();

        $response = $this->actingAs($user)->get(route('guru.mapel.dashboard'));

        $response->assertOk()
            ->assertSee('Dashboard Guru Mapel');
    }

    public function test_non_wali_guru_cannot_access_the_wali_workspace(): void
    {
        $user = User::factory()->guru()->create();
        Guru::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('guru.wali.dashboard'));

        $response->assertForbidden();
    }

    public function test_wali_guru_can_access_the_wali_workspace(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        Kelas::factory()->create(['wali_kelas_id' => $guru->id]);

        $response = $this->actingAs($user)->get(route('guru.wali.dashboard'));

        $response->assertOk()
            ->assertSee('Dashboard Wali Kelas');
    }

    public function test_wali_only_guru_is_redirected_from_the_legacy_dashboard_to_the_wali_workspace(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        Kelas::factory()->create(['wali_kelas_id' => $guru->id]);

        $response = $this->actingAs($user)->get(route('guru.dashboard'));

        $response->assertRedirectToRoute('guru.wali.dashboard');
    }
}
