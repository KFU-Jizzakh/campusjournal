<?php

namespace Database\Factories;

use App\Models\Conference;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Conference> */
class ConferenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'slug' => fake()->unique()->slug(3),
            'description' => fake()->paragraph(),
            'event_date' => now()->addDays(30),
            'location' => fake()->city(),
            'is_published' => true,
        ];
    }

    public function past(): static
    {
        return $this->state(['event_date' => now()->subDays(30)]);
    }

    public function unpublished(): static
    {
        return $this->state(['is_published' => false]);
    }
}
