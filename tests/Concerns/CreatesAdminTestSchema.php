<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesAdminTestSchema
{
    protected function createAdminTestSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'guru'])->default('guru');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('guru', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->string('nip')->nullable()->unique();
            $table->string('nama');
            $table->string('no_hp')->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();
        });

        Schema::create('kelas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wali_kelas_id')->nullable();
        });

        Schema::create('pengampu', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guru_id');
        });

        foreach (['siswa', 'mata_pelajaran'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
            });
        }
    }
}
