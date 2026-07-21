<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Event> */
class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'event_date' => now()->addDays(30),
            'event_type' => 'conference',
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
