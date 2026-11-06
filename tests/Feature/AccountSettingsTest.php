<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_guest_cannot_view_or_update_account_password(): void
    {
        $this->get(route('akun.edit'))->assertRedirectToRoute('login');
        $this->put(route('akun.password.update'))->assertRedirectToRoute('login');
    }

    public function test_administrator_can_view_account_settings(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin Sekolah',
            'email' => 'admin@example.test',
        ]);

        $this->actingAs($admin)
            ->get(route('akun.edit'))
            ->assertOk()
            ->assertViewIs('account.settings')
            ->assertSee('Admin Sekolah')
            ->assertSee('admin@example.test')
            ->assertSee('Ubah Password');
    }

    public function test_guru_without_wali_class_access_can_view_account_settings(): void
    {
        $guruUser = User::factory()->guru()->create([
            'name' => 'Guru Mapel',
            'email' => 'guru@example.test',
        ]);
        Guru::factory()->for($guruUser)->create();

        $this->actingAs($guruUser)
            ->get(route('akun.edit'))
            ->assertOk()
            ->assertViewIs('account.settings')
            ->assertSee('Guru Mapel')
            ->assertSee('guru@example.test')
            ->assertSee('Pengaturan Akun');
    }

    public function test_password_update_requires_the_current_password(): void
    {
        $guruUser = User::factory()->guru()->create([
            'password' => 'password-lama',
        ]);
        Guru::factory()->for($guruUser)->create();

        $this->actingAs($guruUser)
            ->from(route('akun.edit'))
            ->put(route('akun.password.update'), [
                'current_password' => 'bukan-password-lama',
                'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
            ])
            ->assertRedirect(route('akun.edit'))
            ->assertSessionHasErrors([
                'current_password' => 'Password saat ini tidak sesuai.',
            ]);

        $this->assertTrue(Hash::check('password-lama', $guruUser->fresh()->password));
    }

    public function test_password_update_requires_a_matching_confirmation(): void
    {
        $admin = User::factory()->admin()->create([
            'password' => 'password-lama',
        ]);

        $this->actingAs($admin)
            ->from(route('akun.edit'))
            ->put(route('akun.password.update'), [
                'current_password' => 'password-lama',
                'password' => 'password-baru-123',
                'password_confirmation' => 'password-berbeda-123',
            ])
            ->assertRedirect(route('akun.edit'))
            ->assertSessionHasErrors([
                'password' => 'Konfirmasi password baru tidak cocok.',
            ]);

        $this->assertTrue(Hash::check('password-lama', $admin->fresh()->password));
    }

    public function test_guru_can_change_password_and_new_value_is_hashed(): void
    {
        $guruUser = User::factory()->guru()->create([
            'password' => 'password-lama',
        ]);
        Guru::factory()->for($guruUser)->create();

        $this->actingAs($guruUser)
            ->put(route('akun.password.update'), [
                'current_password' => 'password-lama',
                'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
            ])
            ->assertRedirectToRoute('akun.edit')
            ->assertSessionHas('success', 'Password berhasil diperbarui.');

        $updatedPassword = $guruUser->fresh()->password;

        $this->assertNotSame('password-baru-123', $updatedPassword);
        $this->assertTrue(Hash::check('password-baru-123', $updatedPassword));
        $this->assertFalse(Hash::check('password-lama', $updatedPassword));
    }
}
