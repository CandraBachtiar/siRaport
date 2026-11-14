<?php

namespace Database\Factories;

use App\Models\RiwayatPenempatanSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiwayatPenempatanSiswa>
 */
class RiwayatPenempatanSiswaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'siswa_id' => Siswa::factory(),
            'kelas_id' => fn (array $attributes): int => Siswa::query()->findOrFail($attributes['siswa_id'])->kelas_id,
            'tahun_ajaran_id' => TahunAjaran::factory(),
            'mulai_pada' => now()->toDateString(),
            'berakhir_pada' => null,
        ];
    }
}
