<?php

namespace Database\Factories;

use App\Models\Issue;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Issue> */
class IssueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'volume' => 1,
            'number' => fake()->numberBetween(1, 4),
            'year' => fake()->year(),
            'title' => fake()->sentence(3),
            'status' => 'planned',
        ];
    }
}
