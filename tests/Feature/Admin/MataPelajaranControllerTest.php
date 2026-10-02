<?php

namespace Tests\Feature\Admin;

use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class MataPelajaranControllerTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.mata-pelajaran.index'));

        $response->assertRedirectToRoute('login');
    }

    public function test_guru_is_forbidden_from_data_mata_pelajaran(): void
    {
        $guru = User::factory()->guru()->create();

        $response = $this->actingAs($guru)->get(route('admin.mata-pelajaran.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_and_search_mata_pelajaran_list(): void
    {
        $admin = User::factory()->admin()->create();
        MataPelajaran::factory()->create([
            'kode' => 'MTK',
            'nama' => 'Matematika',
            'kkm' => 78,
        ]);
        MataPelajaran::factory()->create([
            'kode' => 'BIN',
            'nama' => 'Bahasa Indonesia',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.mata-pelajaran.index', [
            'search' => 'MTK',
        ]));

        $response->assertOk()
            ->assertSee('MTK')
            ->assertSee('Matematika')
            ->assertSee('78.00')
            ->assertDontSee('Bahasa Indonesia');
    }

    public function test_admin_can_open_create_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.mata-pelajaran.create'));

        $response->assertOk()
            ->assertSee('Tambah Mata Pelajaran')
            ->assertSee('Kode Mata Pelajaran')
            ->assertSee('Nama Mata Pelajaran')
            ->assertSee('KKM');
    }

    public function test_admin_can_create_mata_pelajaran(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.mata-pelajaran.store'),
            $this->validPayload(),
        );

        $response->assertRedirectToRoute('admin.mata-pelajaran.index')
            ->assertSessionHas('success', 'Data mata pelajaran berhasil ditambahkan.');
        $this->assertDatabaseHas('mata_pelajaran', [
            'kode' => 'IPA',
            'nama' => 'Ilmu Pengetahuan Alam',
            'kkm' => 77.5,
        ]);
    }

    public function test_create_mata_pelajaran_requires_all_database_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->from(route('admin.mata-pelajaran.create'))
            ->post(route('admin.mata-pelajaran.store'), []);

        $response->assertRedirect(route('admin.mata-pelajaran.create'))
            ->assertSessionHasErrors([
                'kode' => 'Kode mata pelajaran wajib diisi.',
                'nama' => 'Nama mata pelajaran wajib diisi.',
                'kkm' => 'KKM wajib diisi.',
            ]);
    }

    public function test_create_mata_pelajaran_rejects_duplicate_code(): void
    {
        $admin = User::factory()->admin()->create();
        MataPelajaran::factory()->create(['kode' => 'IPA']);

        $response = $this->actingAs($admin)
            ->from(route('admin.mata-pelajaran.create'))
            ->post(route('admin.mata-pelajaran.store'), $this->validPayload());

        $response->assertRedirect(route('admin.mata-pelajaran.create'))
            ->assertSessionHasErrors([
                'kode' => 'Kode mata pelajaran sudah digunakan.',
            ]);
    }

    public function test_create_mata_pelajaran_rejects_kkm_outside_score_range(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = $this->validPayload();
        $payload['kkm'] = 101;

        $response = $this->actingAs($admin)
            ->from(route('admin.mata-pelajaran.create'))
            ->post(route('admin.mata-pelajaran.store'), $payload);

        $response->assertRedirect(route('admin.mata-pelajaran.create'))
            ->assertSessionHasErrors([
                'kkm' => 'KKM harus berada di antara 0 dan 100.',
            ]);
        $this->assertDatabaseMissing('mata_pelajaran', ['kode' => 'IPA']);
    }

    public function test_admin_can_open_edit_form_with_existing_data(): void
    {
        $admin = User::factory()->admin()->create();
        $mataPelajaran = MataPelajaran::factory()->create([
            'kode' => 'IPS',
            'nama' => 'Ilmu Pengetahuan Sosial',
            'kkm' => 76,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.mata-pelajaran.edit', $mataPelajaran));

        $response->assertOk()
            ->assertSee('Edit Mata Pelajaran')
            ->assertSee('IPS')
            ->assertSee('Ilmu Pengetahuan Sosial')
            ->assertSee('76.00');
    }

    public function test_admin_can_update_mata_pelajaran_without_self_duplicate_error(): void
    {
        $admin = User::factory()->admin()->create();
        $mataPelajaran = MataPelajaran::factory()->create([
            'kode' => 'IPA',
            'nama' => 'IPA Lama',
            'kkm' => 75,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.mata-pelajaran.update', $mataPelajaran), [
            'kode' => 'IPA',
            'nama' => 'Ilmu Pengetahuan Alam',
            'kkm' => 80,
        ]);

        $response->assertRedirectToRoute('admin.mata-pelajaran.index')
            ->assertSessionHas('success', 'Data mata pelajaran berhasil diperbarui.');
        $this->assertDatabaseHas('mata_pelajaran', [
            'id' => $mataPelajaran->id,
            'kode' => 'IPA',
            'nama' => 'Ilmu Pengetahuan Alam',
            'kkm' => 80,
        ]);
    }

    public function test_admin_can_delete_unused_mata_pelajaran(): void
    {
        $admin = User::factory()->admin()->create();
        $mataPelajaran = MataPelajaran::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.mata-pelajaran.destroy', $mataPelajaran));

        $response->assertRedirectToRoute('admin.mata-pelajaran.index')
            ->assertSessionHas('success', 'Data mata pelajaran berhasil dihapus.');
        $this->assertModelMissing($mataPelajaran);
    }

    public function test_mata_pelajaran_used_by_pengampu_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $guru = Guru::factory()->create();
        $mataPelajaran = MataPelajaran::factory()->create();
        DB::table('pengampu')->insert([
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mataPelajaran->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.mata-pelajaran.destroy', $mataPelajaran));

        $response->assertRedirectToRoute('admin.mata-pelajaran.index')
            ->assertSessionHas('error', 'Mata pelajaran tidak dapat dihapus karena masih digunakan pada data pengampu.');
        $this->assertModelExists($mataPelajaran);
    }

    public function test_mata_pelajaran_list_escapes_user_data(): void
    {
        $admin = User::factory()->admin()->create();
        $dangerousName = '<script>alert("xss")</script>';
        MataPelajaran::factory()->create(['nama' => $dangerousName]);

        $response = $this->actingAs($admin)->get(route('admin.mata-pelajaran.index'));

        $response->assertSee($dangerousName)
            ->assertDontSee($dangerousName, false);
    }

    /**
     * @return array<string, float|string>
     */
    private function validPayload(): array
    {
        return [
            'kode' => 'IPA',
            'nama' => 'Ilmu Pengetahuan Alam',
            'kkm' => 77.5,
        ];
    }
}
