<?php

namespace Database\Factories;

use App\Models\Pengampu;
use App\Models\Penilaian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Penilaian>
 */
class PenilaianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pengampu_id' => Pengampu::factory(),
            'nama' => fake()->words(2, true),
            'jenis' => fake()->randomElement(['tugas', 'ulangan_harian', 'uts', 'uas']),
            'tanggal' => fake()->dateTimeBetween('-1 year'),
            'bobot' => null,
            'urutan' => fake()->numberBetween(1, 20),
        ];
    }
}
