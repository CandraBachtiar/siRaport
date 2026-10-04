<?php

namespace Database\Factories;

use App\Models\Deskripsi;
use App\Models\Nilai;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deskripsi>
 */
class DeskripsiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nilai_id' => Nilai::factory(),
            'rekomendasi' => fake()->optional()->sentence(),
            'deskripsi_akhir' => null,
            'status' => 'draft',
        ];
    }
}
