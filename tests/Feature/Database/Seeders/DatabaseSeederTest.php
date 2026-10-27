<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_seeder_creates_one_admin_and_remains_idempotent(): void
    {
        config()->set('auth.bootstrap_admin', [
            'name' => 'Administrator',
            'email' => 'admin@localhost.test',
            'password' => 'password',
        ]);

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@localhost.test')->firstOrFail();

        $this->assertSame(1, User::query()->where('email', 'admin@localhost.test')->count());
        $this->assertSame('Administrator', $admin->name);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('password', $admin->password));
    }

    public function test_seeder_does_not_change_an_existing_admin_password_or_profile(): void
    {
        config()->set('auth.bootstrap_admin', [
            'name' => 'Administrator Baru',
            'email' => 'admin@localhost.test',
            'password' => 'new-bootstrap-password',
        ]);
        $admin = User::factory()->admin()->create([
            'name' => 'Admin Existing',
            'email' => 'admin@localhost.test',
            'password' => Hash::make('existing-admin-password'),
        ]);

        $this->seed(DatabaseSeeder::class);

        $admin->refresh();

        $this->assertSame('Admin Existing', $admin->name);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('existing-admin-password', $admin->password));
        $this->assertSame(1, User::query()->where('email', 'admin@localhost.test')->count());
    }

    public function test_production_seeder_requires_bootstrap_admin_credentials(): void
    {
        config()->set('app.env', 'production');
        config()->set('auth.bootstrap_admin', [
            'name' => 'Administrator',
            'email' => null,
            'password' => null,
        ]);

        try {
            $this->seed(DatabaseSeeder::class);
            $this->fail('Seeder seharusnya menolak konfigurasi admin production yang kosong.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Konfigurasi admin awal belum lengkap. Atur ADMIN_EMAIL dan ADMIN_PASSWORD sebelum menjalankan seeder.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_production_seeder_uses_configured_credentials_to_create_admin_once(): void
    {
        config()->set('app.env', 'production');
        config()->set('auth.bootstrap_admin', [
            'name' => 'Admin Sekolah',
            'email' => 'admin@sekolah.example',
            'password' => 'production-bootstrap-password',
        ]);

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@sekolah.example')->firstOrFail();

        $this->assertSame('Admin Sekolah', $admin->name);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('production-bootstrap-password', $admin->password));
        $this->assertSame(1, User::query()->where('email', 'admin@sekolah.example')->count());
    }
}
