<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ContactMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name'    => preg_replace('/[^a-zA-Z \-\.]/', '', $this->faker->name()),
            'email'   => $this->faker->safeEmail(),
            'subject' => $this->faker->sentence(4),
            'message' => $this->faker->paragraph(),
            'read_at' => null,
        ];
    }
}
