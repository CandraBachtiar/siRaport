<?php

namespace Tests\Feature\Admin;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class PengampuControllerTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminTestSchema();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.pengampu.index'))->assertRedirectToRoute('login');
    }

    public function test_guru_is_forbidden_from_data_pengampu(): void
    {
        $this->actingAs(User::factory()->guru()->create())->get(route('admin.pengampu.index'))->assertForbidden();
    }

    public function test_admin_can_view_list_and_create_form_contains_database_options(): void
    {
        $admin = User::factory()->admin()->create();
        $guru = Guru::factory()->create(['nama' => 'Budi']);
        $mapel = MataPelajaran::factory()->create(['nama' => 'Matematika']);
        $kelas = Kelas::factory()->create(['tingkat' => 'V', 'nama' => 'A']);
        $tahun = TahunAjaran::factory()->create(['tahun' => '2025/2026', 'semester' => 'Ganjil', 'aktif' => true]);

        $this->actingAs($admin)->get(route('admin.pengampu.create'))
            ->assertOk()
            ->assertSee('Budi')->assertSee('Matematika')->assertSee('V A')->assertSee('2025/2026');

        Pengampu::factory()->create([
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'kelas_id' => $kelas->id,
            'tahun_ajaran_id' => $tahun->id,
        ]);

        $this->actingAs($admin)->get(route('admin.pengampu.index'))
            ->assertOk()->assertSee('Budi')->assertSee('Matematika')->assertSee('2025/2026');
    }

    public function test_admin_can_create_pengampu_with_valid_foreign_keys(): void
    {
        $admin = User::factory()->admin()->create();
        $pengampu = Pengampu::factory()->make();

        $this->actingAs($admin)->post(route('admin.pengampu.store'), $pengampu->only([
            'guru_id', 'mata_pelajaran_id', 'kelas_id', 'tahun_ajaran_id',
        ]))->assertRedirectToRoute('admin.pengampu.index')
            ->assertSessionHas('success', 'Data pengampu berhasil ditambahkan.');

        $this->assertDatabaseHas('pengampu', $pengampu->only(['guru_id', 'mata_pelajaran_id', 'kelas_id', 'tahun_ajaran_id']));
    }

    public function test_duplicate_pengampu_combination_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $pengampu = Pengampu::factory()->create();

        $this->actingAs($admin)->from(route('admin.pengampu.create'))
            ->post(route('admin.pengampu.store'), $pengampu->only(['guru_id', 'mata_pelajaran_id', 'kelas_id', 'tahun_ajaran_id']))
            ->assertRedirect(route('admin.pengampu.create'))
            ->assertSessionHasErrors(['guru_id' => 'Kombinasi guru, mata pelajaran, kelas, dan tahun ajaran sudah digunakan.']);
    }

    public function test_invalid_foreign_keys_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->from(route('admin.pengampu.create'))
            ->post(route('admin.pengampu.store'), [
                'guru_id' => 999,
                'mata_pelajaran_id' => 999,
                'kelas_id' => 999,
                'tahun_ajaran_id' => 999,
            ])->assertRedirect(route('admin.pengampu.create'))
            ->assertSessionHasErrors(['guru_id', 'mata_pelajaran_id', 'kelas_id', 'tahun_ajaran_id']);
    }

    public function test_admin_can_update_pengampu(): void
    {
        $admin = User::factory()->admin()->create();
        $pengampu = Pengampu::factory()->create();
        $newClass = Kelas::factory()->create(['nama' => 'B']);

        $this->actingAs($admin)->put(route('admin.pengampu.update', $pengampu), [
            'guru_id' => $pengampu->guru_id,
            'mata_pelajaran_id' => $pengampu->mata_pelajaran_id,
            'kelas_id' => $newClass->id,
            'tahun_ajaran_id' => $pengampu->tahun_ajaran_id,
        ])->assertRedirectToRoute('admin.pengampu.index')
            ->assertSessionHas('success', 'Data pengampu berhasil diperbarui.');

        $this->assertDatabaseHas('pengampu', ['id' => $pengampu->id, 'kelas_id' => $newClass->id]);
    }

    public function test_admin_can_delete_pengampu_without_nilai(): void
    {
        $admin = User::factory()->admin()->create();
        $pengampu = Pengampu::factory()->create();

        $this->actingAs($admin)->delete(route('admin.pengampu.destroy', $pengampu))
            ->assertRedirectToRoute('admin.pengampu.index')
            ->assertSessionHas('success', 'Data pengampu berhasil dihapus.');

        $this->assertModelMissing($pengampu);
    }

    public function test_pengampu_used_by_nilai_cannot_be_deleted_when_column_exists(): void
    {
        $admin = User::factory()->admin()->create();
        $pengampu = Pengampu::factory()->create();
        DB::table('nilai')->insert(['siswa_id' => 1, 'pengampu_id' => $pengampu->id]);

        $this->actingAs($admin)->delete(route('admin.pengampu.destroy', $pengampu))
            ->assertRedirectToRoute('admin.pengampu.index')
            ->assertSessionHas('error', 'Data pengampu tidak dapat dihapus karena sudah digunakan pada data nilai.');

        $this->assertModelExists($pengampu);
    }
}
