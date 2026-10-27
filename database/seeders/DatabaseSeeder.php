<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = config('auth.bootstrap_admin');

        if (blank($admin['email']) || blank($admin['password'])) {
            throw new \RuntimeException(
                'Konfigurasi admin awal belum lengkap. Atur ADMIN_EMAIL dan ADMIN_PASSWORD sebelum menjalankan seeder.',
            );
        }

        User::query()->firstOrCreate(
            ['email' => $admin['email']],
            [
                'name' => $admin['name'],
                'password' => Hash::make($admin['password']),
                'role' => 'admin',
            ],
        );
    }
}
