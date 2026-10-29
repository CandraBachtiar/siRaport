<?php

namespace Tests\Feature\Admin;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
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
            ->assertSee('Import Excel')
            ->assertSee('Tambah Guru')
            ->assertDontSee('Import Guru')
            ->assertDontSee('rahasia-guru');
    }

    /** @return array<string, array{string}> */
    public static function guruNameSearchTerms(): array
    {
        return [
            'full_name' => ['Andi Suryana'],
            'partial_case_insensitive_name' => ['SURYA'],
        ];
    }

    #[DataProvider('guruNameSearchTerms')]
    public function test_admin_can_search_guru_by_full_or_partial_name(string $term): void
    {
        $admin = User::factory()->admin()->create();
        $guruUser = User::factory()->guru()->create(['name' => 'Andi Suryana', 'email' => 'andi@example.test']);
        Guru::factory()->for($guruUser)->create(['nama' => 'Andi Suryana', 'nip' => '197501012000011001']);
        $otherUser = User::factory()->guru()->create(['name' => 'Budi Santoso', 'email' => 'budi@example.test']);
        Guru::factory()->for($otherUser)->create(['nama' => 'Budi Santoso', 'nip' => '197601012000011002']);

        $this->actingAs($admin)->get(route('admin.guru.index', ['q' => $term]))
            ->assertOk()
            ->assertSee('Andi Suryana')
            ->assertDontSee('Budi Santoso')
            ->assertSee('Jumlah hasil pencarian: 1 guru')
            ->assertSee('value="'.$term.'"', false);
    }

    public function test_admin_can_search_guru_by_nip_across_all_pages(): void
    {
        $admin = User::factory()->admin()->create();
        for ($index = 1; $index <= 11; $index++) {
            $name = 'Guru '.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $user = User::factory()->guru()->create(['name' => $name]);
            Guru::factory()->for($user)->create(['nama' => $name, 'nip' => '1985'.str_pad((string) $index, 14, '0', STR_PAD_LEFT)]);
        }
        $targetUser = User::factory()->guru()->create(['name' => 'Guru Zulkifli']);
        Guru::factory()->for($targetUser)->create(['nama' => 'Guru Zulkifli', 'nip' => 'NIP-KHUSUS-9912']);

        $this->actingAs($admin)->get(route('admin.guru.index', ['q' => 'KHUSUS-99']))
            ->assertOk()
            ->assertSee('Guru Zulkifli')
            ->assertSee('NIP-KHUSUS-9912')
            ->assertDontSee('Guru 01')
            ->assertSee('Jumlah hasil pencarian: 1 guru');
    }

    public function test_admin_can_search_guru_by_email(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin-only@example.test']);
        $guruUser = User::factory()->guru()->create(['name' => 'Siti Aminah', 'email' => 'siti.aminah@sekolah.test']);
        Guru::factory()->for($guruUser)->create(['nama' => 'Siti Aminah', 'nip' => '198801012010012003']);

        $this->actingAs($admin)->get(route('admin.guru.index', ['q' => 'AMINAH@SEKOLAH']))
            ->assertOk()
            ->assertSee('Siti Aminah')
            ->assertSee('siti.aminah@sekolah.test')
            ->assertSee('Jumlah hasil pencarian: 1 guru');

        $this->actingAs($admin)->get(route('admin.guru.index', ['q' => 'admin-only']))
            ->assertOk()
            ->assertSee('Jumlah hasil pencarian: 0 guru')
            ->assertSee('Guru tidak ditemukan')
            ->assertDontSee('Siti Aminah');
    }

    public function test_unmatched_search_displays_empty_state_and_reset_link(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->guru()->create(['name' => 'Dewi Rahma']);
        Guru::factory()->for($user)->create(['nama' => 'Dewi Rahma']);

        $this->actingAs($admin)->get(route('admin.guru.index', ['q' => 'TidakAdaNamaIni']))
            ->assertOk()
            ->assertSee('Jumlah hasil pencarian: 0 guru')
            ->assertSee('Guru tidak ditemukan')
            ->assertSee('Hapus pencarian')
            ->assertSee(route('admin.guru.index'));
    }

    public function test_empty_search_shows_all_gurus_and_a_clear_result_count(): void
    {
        $admin = User::factory()->admin()->create();
        foreach (['Guru Satu', 'Guru Dua', 'Guru Tiga'] as $index => $name) {
            $user = User::factory()->guru()->create(['name' => $name]);
            Guru::factory()->for($user)->create(['nama' => $name, 'nip' => 'NIP-ALL-'.$index]);
        }

        $this->actingAs($admin)->get(route('admin.guru.index', ['q' => '']))
            ->assertOk()
            ->assertSee('Guru Satu')
            ->assertSee('Guru Dua')
            ->assertSee('Guru Tiga')
            ->assertSee('Jumlah guru: 3');
    }

    public function test_search_pagination_keeps_query_and_keyword_change_starts_on_first_page(): void
    {
        $admin = User::factory()->admin()->create();
        for ($index = 1; $index <= 12; $index++) {
            $name = 'Target Guru '.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $user = User::factory()->guru()->create(['name' => $name]);
            Guru::factory()->for($user)->create(['nama' => $name, 'nip' => 'NIP-PAGE-'.$index]);
        }
        $otherUser = User::factory()->guru()->create(['name' => 'Kata Baru']);
        Guru::factory()->for($otherUser)->create(['nama' => 'Kata Baru', 'nip' => 'NIP-BARU']);

        $firstPage = $this->actingAs($admin)->get(route('admin.guru.index', ['q' => 'Target']));
        $firstPage->assertOk()
            ->assertSee('Jumlah hasil pencarian: 12 guru')
            ->assertSee('Target Guru 01')
            ->assertDontSee('Target Guru 12')
            ->assertSee('q=Target')
            ->assertSee('page=2');

        $secondPage = $this->actingAs($admin)->get(route('admin.guru.index', ['q' => 'Target', 'page' => 2]));
        $secondPage->assertOk()
            ->assertSee('Target Guru 12')
            ->assertDontSee('Target Guru 01')
            ->assertSee('value="Target"', false)
            ->assertSee('q=Target');

        $newSearch = $this->actingAs($admin)->get(route('admin.guru.index', ['q' => 'Baru']));
        $newSearch->assertOk()
            ->assertSee('Kata Baru')
            ->assertDontSee('Target Guru 12')
            ->assertSee('value="Baru"', false);
    }

    public function test_search_does_not_change_guru_or_user_records(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->guru()->create(['name' => 'Guru Tetap', 'email' => 'tetap@example.test']);
        $guru = Guru::factory()->for($user)->create(['nama' => 'Guru Tetap', 'nip' => 'NIP-TETAP']);
        $userCount = User::query()->count();
        $guruCount = Guru::query()->count();

        $this->actingAs($admin)->get(route('admin.guru.index', ['q' => 'TETAP']))
            ->assertOk()
            ->assertSee('Guru Tetap');

        $this->assertSame($userCount, User::query()->count());
        $this->assertSame($guruCount, Guru::query()->count());
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Guru Tetap', 'email' => 'tetap@example.test']);
        $this->assertDatabaseHas('guru', ['id' => $guru->id, 'nama' => 'Guru Tetap', 'nip' => 'NIP-TETAP']);
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
