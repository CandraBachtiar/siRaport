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
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@localhost.test')->firstOrFail();

        $this->assertSame(1, User::query()->where('email', 'admin@localhost.test')->count());
        $this->assertSame('Administrator', $admin->name);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('password', $admin->password));
    }
}
