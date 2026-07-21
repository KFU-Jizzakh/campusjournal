<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Review> */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'reviewer_id' => User::factory(),
            'assigned_by' => User::factory(),
            'status' => ReviewStatus::Pending,
            'assigned_at' => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => ReviewStatus::Completed,
            'recommendation' => fake()->randomElement(['accept', 'minor_revision', 'major_revision', 'reject']),
            'comments_for_editor' => fake()->paragraph(),
            'comments_for_author' => fake()->paragraph(),
            'completed_at' => now(),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state([
            'status' => ReviewStatus::InProgress,
            'assigned_at' => now()->subDays(fake()->numberBetween(1, 10)),
            'response_due_at' => now()->subDays(fake()->numberBetween(-5, 3)),
            'review_due_at' => now()->addDays(fake()->numberBetween(5, 25)),
        ]);
    }

    public function declined(): static
    {
        return $this->state([
            'status' => ReviewStatus::Declined,
            'assigned_at' => now()->subDays(fake()->numberBetween(5, 15)),
            'response_due_at' => now()->subDays(fake()->numberBetween(3, 10)),
        ]);
    }

    public function withDeadlines(): static
    {
        return $this->state([
            'response_due_at' => now()->addDays(7),
            'review_due_at' => now()->addDays(30),
        ]);
    }

    public function overdue(): static
    {
        return $this->state([
            'status' => ReviewStatus::Pending,
            'assigned_at' => now()->subDays(35),
            'response_due_at' => now()->subDays(28),
            'review_due_at' => now()->subDays(5),
        ]);
    }
}
