<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Author> */
class AuthorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'full_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'degree' => fake()->randomElement(['к.н.', 'д.н.', null]),
            'position' => fake()->jobTitle(),
            'organization' => fake()->company(),
        ];
    }
}
