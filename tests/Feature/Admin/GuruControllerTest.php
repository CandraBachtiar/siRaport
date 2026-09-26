<?php

namespace Tests\Feature\Admin;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class GuruControllerTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.guru.index'));

        $response->assertRedirectToRoute('login');
    }

    public function test_guru_is_forbidden_from_data_guru(): void
    {
        $guruUser = User::factory()->guru()->create();

        $response = $this->actingAs($guruUser)->get(route('admin.guru.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_guru_list_without_passwords(): void
    {
        $admin = User::factory()->admin()->create();
        $guruUser = User::factory()->guru()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'password' => 'rahasia-guru',
        ]);
        Guru::factory()->for($guruUser)->create([
            'nip' => '198501012010011001',
            'nama' => 'Budi Santoso',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.guru.index'));

        $response->assertOk()
            ->assertSee('198501012010011001')
            ->assertSee('Budi Santoso')
            ->assertSee('budi@example.test')
            ->assertDontSee('rahasia-guru');
    }

    public function test_admin_can_open_create_guru_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.guru.create'));

        $response->assertOk()
            ->assertSee('Tambah Guru')
            ->assertSee('Nama Guru')
            ->assertSee('NIP')
            ->assertSee('Email')
            ->assertSee('Password');
    }

    public function test_admin_can_open_edit_guru_form_with_existing_data(): void
    {
        $admin = User::factory()->admin()->create();
        $guruUser = User::factory()->guru()->create([
            'name' => 'Guru Lama',
            'email' => 'guru-lama@example.test',
        ]);
        $guru = Guru::factory()->for($guruUser)->create([
            'nip' => '199001012015011001',
            'nama' => 'Guru Lama',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.guru.edit', $guru));

        $response->assertOk()
            ->assertSee('Edit Guru')
            ->assertSee('Guru Lama')
            ->assertSee('199001012015011001')
            ->assertSee('guru-lama@example.test')
            ->assertSee('Opsional');
    }

    public function test_admin_can_create_guru_account_with_server_controlled_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.guru.store'), [
            'name' => 'Siti Aminah',
            'nip' => '198702022012022002',
            'email' => 'siti@example.test',
            'password' => 'password-baru',
            'role' => 'admin',
        ]);

        $response->assertRedirectToRoute('admin.guru.index')
            ->assertSessionHas('success', 'Data guru berhasil ditambahkan.');
        $this->assertDatabaseHas('users', [
            'name' => 'Siti Aminah',
            'email' => 'siti@example.test',
            'role' => 'guru',
        ]);

        $guruUser = User::query()->where('email', 'siti@example.test')->firstOrFail();

        $this->assertTrue(Hash::check('password-baru', $guruUser->password));
        $this->assertDatabaseHas('guru', [
            'user_id' => $guruUser->id,
            'nip' => '198702022012022002',
            'nama' => 'Siti Aminah',
        ]);
    }

    public function test_create_guru_requires_all_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->from(route('admin.guru.create'))
            ->post(route('admin.guru.store'), []);

        $response->assertRedirect(route('admin.guru.create'))
            ->assertSessionHasErrors([
                'name' => 'Nama guru wajib diisi.',
                'nip' => 'NIP wajib diisi.',
                'email' => 'Email wajib diisi.',
                'password' => 'Password wajib diisi.',
            ]);
    }

    public function test_create_guru_rejects_duplicate_email_and_nip(): void
    {
        $admin = User::factory()->admin()->create();
        $existingUser = User::factory()->guru()->create([
            'email' => 'existing@example.test',
        ]);
        Guru::factory()->for($existingUser)->create([
            'nip' => '197001012000011001',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.guru.create'))
            ->post(route('admin.guru.store'), [
                'name' => 'Guru Duplikat',
                'nip' => '197001012000011001',
                'email' => 'existing@example.test',
                'password' => 'password',
            ]);

        $response->assertRedirect(route('admin.guru.create'))
            ->assertSessionHasErrors([
                'nip' => 'NIP sudah digunakan.',
                'email' => 'Email sudah digunakan.',
            ]);
    }

    public function test_admin_can_update_guru_without_changing_password(): void
    {
        $admin = User::factory()->admin()->create();
        $guruUser = User::factory()->guru()->create([
            'name' => 'Nama Lama',
            'email' => 'lama@example.test',
            'password' => 'password-lama',
        ]);
        $guru = Guru::factory()->for($guruUser)->create([
            'nip' => '111111111111111111',
            'nama' => 'Nama Lama',
        ]);
        $originalPassword = $guruUser->password;

        $response = $this->actingAs($admin)->put(route('admin.guru.update', $guru), [
            'name' => 'Nama Baru',
            'nip' => '222222222222222222',
            'email' => 'baru@example.test',
            'password' => '',
        ]);

        $response->assertRedirectToRoute('admin.guru.index')
            ->assertSessionHas('success', 'Data guru berhasil diperbarui.');
        $this->assertDatabaseHas('users', [
            'id' => $guruUser->id,
            'name' => 'Nama Baru',
            'email' => 'baru@example.test',
        ]);
        $this->assertDatabaseHas('guru', [
            'id' => $guru->id,
            'nip' => '222222222222222222',
            'nama' => 'Nama Baru',
        ]);
        $this->assertSame($originalPassword, $guruUser->fresh()->password);
    }

    public function test_admin_can_change_guru_password(): void
    {
        $admin = User::factory()->admin()->create();
        $guruUser = User::factory()->guru()->create([
            'password' => 'password-lama',
        ]);
        $guru = Guru::factory()->for($guruUser)->create();

        $response = $this->actingAs($admin)->put(route('admin.guru.update', $guru), [
            'name' => $guruUser->name,
            'nip' => $guru->nip,
            'email' => $guruUser->email,
            'password' => 'password-baru',
        ]);

        $response->assertRedirectToRoute('admin.guru.index');
        $this->assertTrue(Hash::check('password-baru', $guruUser->fresh()->password));
    }

    public function test_admin_can_delete_unused_guru_and_linked_user(): void
    {
        $admin = User::factory()->admin()->create();
        $guruUser = User::factory()->guru()->create();
        $guru = Guru::factory()->for($guruUser)->create();

        $response = $this->actingAs($admin)->delete(route('admin.guru.destroy', $guru));

        $response->assertRedirectToRoute('admin.guru.index')
            ->assertSessionHas('success', 'Data guru berhasil dihapus.');
        $this->assertDatabaseMissing('guru', ['id' => $guru->id]);
        $this->assertDatabaseMissing('users', ['id' => $guruUser->id]);
    }

    public function test_guru_used_by_pengampu_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $guruUser = User::factory()->guru()->create();
        $guru = Guru::factory()->for($guruUser)->create();
        DB::table('pengampu')->insert(['guru_id' => $guru->id]);

        $response = $this->actingAs($admin)->delete(route('admin.guru.destroy', $guru));

        $response->assertRedirectToRoute('admin.guru.index')
            ->assertSessionHas('error', 'Guru tidak dapat dihapus karena masih digunakan pada data lain.');
        $this->assertModelExists($guru);
        $this->assertModelExists($guruUser);
    }

    public function test_guru_used_as_wali_kelas_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $guruUser = User::factory()->guru()->create();
        $guru = Guru::factory()->for($guruUser)->create();
        DB::table('kelas')->insert(['wali_kelas_id' => $guru->id]);

        $response = $this->actingAs($admin)->delete(route('admin.guru.destroy', $guru));

        $response->assertRedirectToRoute('admin.guru.index')
            ->assertSessionHas('error', 'Guru tidak dapat dihapus karena masih digunakan pada data lain.');
        $this->assertModelExists($guru);
        $this->assertModelExists($guruUser);
    }

    public function test_guru_list_escapes_user_data(): void
    {
        $admin = User::factory()->admin()->create();
        $dangerousName = '<script>alert("xss")</script>';
        $guruUser = User::factory()->guru()->create([
            'name' => $dangerousName,
        ]);
        Guru::factory()->for($guruUser)->create([
            'nama' => $dangerousName,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.guru.index'));

        $response->assertSee($dangerousName)
            ->assertDontSee($dangerousName, false);
    }
}
