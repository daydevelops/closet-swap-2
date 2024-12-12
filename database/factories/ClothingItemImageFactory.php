<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClothingItemImage>
 */
class ClothingItemImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clothing_item_id' => \App\Models\ClothingItem::factory(),
            'image_url' => $this->faker->imageUrl(),
        ];
    }
}
