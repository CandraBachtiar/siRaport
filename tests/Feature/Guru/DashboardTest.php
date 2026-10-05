<?php

namespace Tests\Feature\Guru;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\TahunAjaran;
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
        $response = $this->get(route('guru.dashboard'));

        $response->assertRedirectToRoute('login');
    }

    public function test_admin_is_forbidden_from_guru_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('guru.dashboard'));

        $response->assertForbidden();
    }

    public function test_guru_without_a_guru_record_is_forbidden(): void
    {
        $guru = User::factory()->guru()->create();

        $response = $this->actingAs($guru)->get(route('guru.dashboard'));

        $response->assertForbidden();
    }

    public function test_guru_dashboard_displays_identity_and_pengampu_count(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create([
            'nama' => 'Siti Aminah',
            'nip' => '198001012010012001',
        ]);
        Pengampu::factory()->for($guru)->create();

        $response = $this->actingAs($user)->get(route('guru.dashboard'));

        $response->assertOk()
            ->assertSee('Siti Aminah')
            ->assertSee('198001012010012001')
            ->assertSee('Jumlah Pengampu')
            ->assertSee('1')
            ->assertHeader('Cache-Control');
    }

    public function test_guru_dashboard_displays_only_the_authenticated_gurus_pengampu(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $otherGuru = Guru::factory()->create();

        $ownSubject = MataPelajaran::factory()->create(['nama' => 'Matematika Milik Saya']);
        $otherSubject = MataPelajaran::factory()->create(['nama' => 'IPA Milik Guru Lain']);
        $class = Kelas::factory()->create(['nama' => 'A', 'tingkat' => 'VII']);
        $schoolYear = TahunAjaran::factory()->create(['tahun' => '2025/2026', 'semester' => 'Ganjil']);
        Pengampu::factory()->for($guru)->create([
            'mata_pelajaran_id' => $ownSubject->id,
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $schoolYear->id,
        ]);
        Pengampu::factory()->for($otherGuru)->create([
            'mata_pelajaran_id' => $otherSubject->id,
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $schoolYear->id,
        ]);

        $response = $this->actingAs($user)->get(route('guru.dashboard'));

        $response->assertOk()
            ->assertSee('Matematika Milik Saya')
            ->assertDontSee('IPA Milik Guru Lain')
            ->assertSee('VII A')
            ->assertSee('2025/2026')
            ->assertSee('Ganjil');
    }

    public function test_guru_dashboard_shows_an_empty_state_without_pengampu(): void
    {
        $user = User::factory()->guru()->create();
        Guru::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('guru.dashboard'));

        $response->assertOk()
            ->assertSee('Belum ada data pengampu')
            ->assertSee('Tugas mengajar Anda belum ditambahkan oleh administrator.');
    }
}
