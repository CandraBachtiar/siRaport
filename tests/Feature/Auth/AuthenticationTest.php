<?php

namespace Tests\Feature\Auth;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Pengampu;
use App\Models\User;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_login_page_is_rendered_for_guests(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertSee('Masuk ke RaporKu')
            ->assertSee('Tampilkan password')
            ->assertSee('Email')
            ->assertSee('Password')
            ->assertSee('max-w-[58rem]', false)
            ->assertSee('min-h-dvh', false)
            ->assertSee('overflow-y-auto', false)
            ->assertSee('rounded-[20px]', false)
            ->assertSee('lg:grid-cols-2', false)
            ->assertSee('href="#main-content"', false)
            ->assertSee('id="main-content"', false)
            ->assertDontSee('Register');
    }

    public function test_admin_can_authenticate_and_session_is_regenerated(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.test',
            'password' => 'password',
        ]);
        $this->withSession(['login_marker' => true]);
        $previousSessionId = session()->getId();

        $response = $this->post(route('login.store'), [
            'email' => 'admin@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('admin.dashboard');
        $this->assertAuthenticatedAs($admin);
        $this->assertNotSame($previousSessionId, session()->getId());
    }

    public function test_invalid_credentials_are_rejected_with_a_generic_message(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.test',
            'password' => 'password',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'admin@example.test',
            'password' => 'incorrect-password',
        ]);

        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Email atau password tidak sesuai.',
            ]);
        $this->assertGuest();
    }

    public function test_guru_can_authenticate_and_is_redirected_to_guru_dashboard(): void
    {
        $guruUser = User::factory()->guru()->create([
            'email' => 'guru@example.test',
            'password' => 'password',
        ]);
        Guru::factory()->for($guruUser)->create();

        $response = $this->post(route('login.store'), [
            'email' => 'guru@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('guru.dashboard');
        $this->assertAuthenticatedAs($guruUser);
    }

    public function test_login_requires_a_valid_email_and_password(): void
    {
        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'bukan-email',
            'password' => '',
        ]);

        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Format email tidak valid.',
                'password' => 'Password wajib diisi.',
            ]);
        $this->assertGuest();
    }

    public function test_authenticated_admin_is_redirected_away_from_login_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('login'));

        $response->assertRedirectToRoute('admin.dashboard');
    }

    public function test_authenticated_guru_is_redirected_away_from_login_page(): void
    {
        $guruUser = User::factory()->guru()->create();
        Guru::factory()->for($guruUser)->create();

        $response = $this->actingAs($guruUser)->get(route('login'));

        $response->assertRedirectToRoute('guru.dashboard');
    }

    public function test_admin_can_logout_and_session_data_is_invalidated(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->withSession(['private_marker' => 'secret'])
            ->post(route('logout'));

        $response->assertRedirectToRoute('login')
            ->assertSessionMissing('private_marker');
        $this->assertGuest();
    }

    public function test_admin_layout_exposes_post_logout_control_in_the_header(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('aria-label="Keluar dari akun"', false)
            ->assertSee('min-h-11', false);
    }

    public function test_guru_and_wali_layouts_expose_post_logout_control_in_the_header(): void
    {
        $guruUser = User::factory()->guru()->create();
        $guru = Guru::factory()->for($guruUser)->create();
        $assignment = Pengampu::factory()->for($guru)->create();
        Kelas::factory()->create(['wali_kelas_id' => $guru->id]);

        $mapelResponse = $this->actingAs($guruUser)->get(route('guru.penilaian.index', ['pengampu_id' => $assignment->id]));
        $mapelResponse->assertOk()
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('aria-label="Keluar dari akun"', false);

        $this->actingAs($guruUser)->get(route('guru.wali.siswa.index'))
            ->assertOk()
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_guru_can_logout_and_session_data_is_invalidated(): void
    {
        $guruUser = User::factory()->guru()->create();
        Guru::factory()->for($guruUser)->create();

        $response = $this->actingAs($guruUser)
            ->withSession(['private_marker' => 'secret'])
            ->post(route('logout'));

        $response->assertRedirectToRoute('login')
            ->assertSessionMissing('private_marker');
        $this->assertGuest();
    }
}
