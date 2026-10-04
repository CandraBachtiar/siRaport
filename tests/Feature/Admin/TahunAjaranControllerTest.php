<?php

namespace Tests\Feature\Admin;

use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class TahunAjaranControllerTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminTestSchema();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.tahun-ajaran.index'))->assertRedirectToRoute('login');
    }

    public function test_guru_is_forbidden_from_data_tahun_ajaran(): void
    {
        $this->actingAs(User::factory()->guru()->create())->get(route('admin.tahun-ajaran.index'))->assertForbidden();
    }

    public function test_admin_can_view_tahun_ajaran_list(): void
    {
        $admin = User::factory()->admin()->create();
        TahunAjaran::factory()->create(['tahun' => '2025/2026', 'semester' => 'Ganjil', 'aktif' => true]);

        $this->actingAs($admin)->get(route('admin.tahun-ajaran.index'))
            ->assertOk()->assertSee('2025/2026')->assertSee('Ganjil')->assertSee('Aktif');
    }

    public function test_admin_can_open_create_form(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.tahun-ajaran.create'))
            ->assertOk()->assertSee('Tambah Tahun Ajaran')->assertSee('Semester');
    }

    public function test_admin_can_create_tahun_ajaran(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.tahun-ajaran.store'), [
            'tahun' => '2025/2026', 'semester' => 'Ganjil', 'aktif' => '1',
        ])->assertRedirectToRoute('admin.tahun-ajaran.index')
            ->assertSessionHas('success', 'Data tahun ajaran berhasil ditambahkan.');

        $this->assertDatabaseHas('tahun_ajaran', ['tahun' => '2025/2026', 'semester' => 'Ganjil', 'aktif' => true]);
    }

    public function test_creating_active_tahun_ajaran_deactivates_previous_one(): void
    {
        $admin = User::factory()->admin()->create();
        $previous = TahunAjaran::factory()->create(['tahun' => '2024/2025', 'semester' => 'Ganjil', 'aktif' => true]);

        $this->actingAs($admin)->post(route('admin.tahun-ajaran.store'), [
            'tahun' => '2025/2026', 'semester' => 'Genap', 'aktif' => '1',
        ])->assertRedirectToRoute('admin.tahun-ajaran.index');

        $this->assertDatabaseHas('tahun_ajaran', ['id' => $previous->id, 'aktif' => false]);
        $this->assertDatabaseHas('tahun_ajaran', ['tahun' => '2025/2026', 'semester' => 'Genap', 'aktif' => true]);
    }

    public function test_creating_inactive_tahun_ajaran_preserves_active_one(): void
    {
        $admin = User::factory()->admin()->create();
        $active = TahunAjaran::factory()->create(['tahun' => '2024/2025', 'semester' => 'Ganjil', 'aktif' => true]);

        $this->actingAs($admin)->post(route('admin.tahun-ajaran.store'), [
            'tahun' => '2025/2026', 'semester' => 'Genap', 'aktif' => '0',
        ])->assertRedirectToRoute('admin.tahun-ajaran.index');

        $this->assertDatabaseHas('tahun_ajaran', ['id' => $active->id, 'aktif' => true]);
    }

    public function test_create_tahun_ajaran_requires_database_fields(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.tahun-ajaran.create'))->post(route('admin.tahun-ajaran.store'), [])
            ->assertRedirect(route('admin.tahun-ajaran.create'))
            ->assertSessionHasErrors(['tahun' => 'Tahun ajaran wajib diisi.', 'semester' => 'Semester wajib dipilih.']);
    }

    public function test_create_tahun_ajaran_rejects_invalid_format(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.tahun-ajaran.create'))
            ->post(route('admin.tahun-ajaran.store'), ['tahun' => '2025', 'semester' => 'Ganjil'])
            ->assertRedirect(route('admin.tahun-ajaran.create'))
            ->assertSessionHasErrors(['tahun' => 'Format tahun ajaran harus seperti 2025/2026.']);
    }

    public function test_duplicate_tahun_ajaran_and_semester_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        TahunAjaran::factory()->create(['tahun' => '2025/2026', 'semester' => 'Ganjil']);

        $this->actingAs($admin)->from(route('admin.tahun-ajaran.create'))
            ->post(route('admin.tahun-ajaran.store'), ['tahun' => '2025/2026', 'semester' => 'Ganjil'])
            ->assertRedirect(route('admin.tahun-ajaran.create'))
            ->assertSessionHasErrors(['tahun' => 'Tahun ajaran dan semester tersebut sudah digunakan.']);
    }

    public function test_same_tahun_with_different_semester_is_allowed(): void
    {
        $admin = User::factory()->admin()->create();
        TahunAjaran::factory()->create(['tahun' => '2025/2026', 'semester' => 'Ganjil']);

        $this->actingAs($admin)->post(route('admin.tahun-ajaran.store'), [
            'tahun' => '2025/2026', 'semester' => 'Genap', 'aktif' => '0',
        ])->assertRedirectToRoute('admin.tahun-ajaran.index');

        $this->assertDatabaseHas('tahun_ajaran', ['tahun' => '2025/2026', 'semester' => 'Genap']);
    }

    public function test_database_unique_constraint_protects_tahun_and_semester(): void
    {
        TahunAjaran::factory()->create(['tahun' => '2025/2026', 'semester' => 'Ganjil']);

        $this->expectException(QueryException::class);
        DB::table('tahun_ajaran')->insert([
            'tahun' => '2025/2026',
            'semester' => 'Ganjil',
            'aktif' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_admin_can_update_tahun_ajaran_without_self_duplicate_error(): void
    {
        $admin = User::factory()->admin()->create();
        $tahunAjaran = TahunAjaran::factory()->create(['tahun' => '2025/2026', 'semester' => 'Ganjil']);

        $this->actingAs($admin)->put(route('admin.tahun-ajaran.update', $tahunAjaran), [
            'tahun' => '2025/2026', 'semester' => 'Ganjil', 'aktif' => '1',
        ])->assertRedirectToRoute('admin.tahun-ajaran.index');

        $this->assertDatabaseHas('tahun_ajaran', ['id' => $tahunAjaran->id, 'aktif' => true]);
    }

    public function test_activating_new_tahun_ajaran_deactivates_previous_one(): void
    {
        $admin = User::factory()->admin()->create();
        $previous = TahunAjaran::factory()->create(['tahun' => '2024/2025', 'aktif' => true]);
        $new = TahunAjaran::factory()->create(['tahun' => '2025/2026', 'aktif' => false]);

        $this->actingAs($admin)->put(route('admin.tahun-ajaran.update', $new), [
            'tahun' => $new->tahun, 'semester' => $new->semester, 'aktif' => '1',
        ]);

        $this->assertDatabaseHas('tahun_ajaran', ['id' => $previous->id, 'aktif' => false]);
        $this->assertDatabaseHas('tahun_ajaran', ['id' => $new->id, 'aktif' => true]);
    }

    public function test_admin_can_delete_unused_inactive_tahun_ajaran(): void
    {
        $admin = User::factory()->admin()->create();
        $tahunAjaran = TahunAjaran::factory()->create(['aktif' => false]);

        $this->actingAs($admin)->delete(route('admin.tahun-ajaran.destroy', $tahunAjaran))
            ->assertRedirectToRoute('admin.tahun-ajaran.index')
            ->assertSessionHas('success', 'Data tahun ajaran berhasil dihapus.');

        $this->assertModelMissing($tahunAjaran);
    }

    public function test_active_tahun_ajaran_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $tahunAjaran = TahunAjaran::factory()->create(['aktif' => true]);

        $this->actingAs($admin)->delete(route('admin.tahun-ajaran.destroy', $tahunAjaran))
            ->assertSessionHas('error', 'Tahun ajaran aktif tidak dapat dihapus.');

        $this->assertModelExists($tahunAjaran);
    }

    public function test_tahun_ajaran_used_by_pengampu_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $tahunAjaran = TahunAjaran::factory()->create(['aktif' => false]);
        DB::table('pengampu')->insert(['guru_id' => 1, 'tahun_ajaran_id' => $tahunAjaran->id]);

        $this->actingAs($admin)->delete(route('admin.tahun-ajaran.destroy', $tahunAjaran))
            ->assertSessionHas('error', 'Tahun ajaran tidak dapat dihapus karena masih digunakan pada data lain.');

        $this->assertModelExists($tahunAjaran);
    }
}
