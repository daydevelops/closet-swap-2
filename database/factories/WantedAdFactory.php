<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WantedAd;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WantedAd>
 */
class WantedAdFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'category' => WantedAd::CATEGORIES[array_rand(WantedAd::CATEGORIES)],
        ];
    }
}
