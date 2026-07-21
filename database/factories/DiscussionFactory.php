<?php

namespace Database\Factories;

use App\Enums\DiscussionScope;
use App\Models\Article;
use App\Models\Discussion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Discussion> */
class DiscussionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'user_id' => User::factory(),
            'scope' => DiscussionScope::Article,
            'message' => fake()->sentence(),
            'is_resolved' => false,
        ];
    }

    public function editorial(): static
    {
        return $this->state([
            'scope' => DiscussionScope::Editorial,
        ]);
    }

    public function resolved(): static
    {
        return $this->state([
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);
    }
}
