<?php

namespace Database\Factories;

use App\Models\Pengampu;
use App\Models\TujuanPembelajaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TujuanPembelajaran>
 */
class TujuanPembelajaranFactory extends Factory
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
            'urutan' => fake()->numberBetween(1, 999),
            'tujuan' => fake()->sentence(),
            'kktp' => fake()->randomFloat(2, 60, 85),
            'metode_rekap' => TujuanPembelajaran::METODE_RATA_RATA,
        ];
    }
}
