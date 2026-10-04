<?php

namespace Database\Factories;

use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Nilai>
 */
class NilaiFactory extends Factory
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
            'pengampu_id' => Pengampu::factory(),
            'tugas' => null,
            'ulangan_harian' => null,
            'uts' => null,
            'uas' => null,
            'nilai_akhir' => null,
        ];
    }
}
