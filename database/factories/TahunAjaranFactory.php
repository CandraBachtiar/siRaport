<?php

namespace Database\Factories;

use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TahunAjaran>
 */
class TahunAjaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = fake()->unique()->numberBetween(2020, 2030);

        return [
            'tahun' => $startYear.'/'.($startYear + 1),
            'semester' => fake()->randomElement(['Ganjil', 'Genap']),
            'aktif' => false,
        ];
    }
}
