<?php

namespace Database\Factories;

use App\Models\ClothingItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClothingItemFactory extends Factory
{
    protected $model = ClothingItem::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'brand' => $this->faker->company(),
            'ci_type_id' => \App\Models\CiType::inRandomOrder()->first()?->id,
            'ci_gender_id' => \App\Models\CiGender::inRandomOrder()->first()?->id,
            'ci_size_id' => \App\Models\CiSize::inRandomOrder()->first()?->id,
            'ci_units_id' => \App\Models\CiUnit::inRandomOrder()->first()?->id,
            'ci_fit_id' => \App\Models\CiFit::inRandomOrder()->first()?->id,
            'ci_condition_id' => \App\Models\CiCondition::inRandomOrder()->first()?->id,
            'user_id' => \App\Models\User::factory(),
            'status' => $this->faker->randomElement(['available', 'unavailable']),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (ClothingItem $item) {
            $tags = \App\Models\CiTags::inRandomOrder()->take(rand(1, 4))->pluck('id');
            $colors = \App\Models\CiColors::inRandomOrder()->take(rand(1, 3))->pluck('id');
            $materials = \App\Models\CiMaterial::inRandomOrder()->take(rand(1, 2))->pluck('id');

            $item->tags()->attach($tags);
            $item->colors()->attach($colors);
            $item->materials()->attach($materials);
        });
    }
}
