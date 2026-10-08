<?php

namespace Tests\Feature\Guru;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class WaliKelasDashboardTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_dashboard_displays_only_the_authenticated_gurus_wali_classes(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $otherGuru = Guru::factory()->create();
        $ownClass = Kelas::factory()->create([
            'nama' => 'Wali Saya',
            'tingkat' => 'VIII',
            'wali_kelas_id' => $guru->id,
        ]);
        Kelas::factory()->create([
            'nama' => 'Bukan Wali Saya',
            'tingkat' => 'IX',
            'wali_kelas_id' => $otherGuru->id,
        ]);
        Siswa::factory()->count(3)->for($ownClass)->create();

        $response = $this->actingAs($user)->get(route('guru.wali.dashboard'));

        $response->assertOk()
            ->assertSee('VIII Wali Saya')
            ->assertDontSee('IX Bukan Wali Saya')
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['classCount'] === 1 && $metrics['studentCount'] === 3);
    }

    public function test_dashboard_uses_only_active_school_year_assignments_for_wali_metrics(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create(['wali_kelas_id' => $guru->id]);
        $activeYear = TahunAjaran::factory()->create([
            'tahun' => '2026/2027',
            'semester' => 'Ganjil',
            'aktif' => true,
        ]);
        $inactiveYear = TahunAjaran::factory()->create([
            'tahun' => '2025/2026',
            'semester' => 'Genap',
            'aktif' => false,
        ]);
        $activeSubject = MataPelajaran::factory()->create(['nama' => 'Bahasa Indonesia Aktif']);
        $inactiveSubject = MataPelajaran::factory()->create(['nama' => 'Sejarah Lama']);
        Pengampu::factory()->create([
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $activeYear->id,
            'mata_pelajaran_id' => $activeSubject->id,
        ]);
        Pengampu::factory()->create([
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $inactiveYear->id,
            'mata_pelajaran_id' => $inactiveSubject->id,
        ]);

        $response = $this->actingAs($user)->get(route('guru.wali.dashboard'));

        $response->assertOk()
            ->assertSee('2026/2027')
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['teacherAssignmentCount'] === 1 && $metrics['subjectCount'] === 1);
    }

    public function test_dual_role_guru_sees_the_workspace_selector(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        Kelas::factory()->create(['wali_kelas_id' => $guru->id]);
        Pengampu::factory()->for($guru)->create();

        $mapelResponse = $this->actingAs($user)->get(route('guru.mapel.dashboard'));
        $waliResponse = $this->actingAs($user)->get(route('guru.wali.dashboard'));

        $mapelResponse->assertOk()
            ->assertSee('Guru Mapel')
            ->assertSee('Wali Kelas');
        $waliResponse->assertOk()
            ->assertSee('Guru Mapel')
            ->assertSee('Wali Kelas');
    }
}
