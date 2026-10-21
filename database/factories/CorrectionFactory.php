<?php

namespace Database\Factories;

use App\Enums\CorrectionType;
use App\Models\Article;
use App\Models\Correction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Correction>
 */
class CorrectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_id' => Article::factory()->published(),
            'type' => fake()->randomElement(CorrectionType::cases()),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'file_path' => null,
            'published_at' => now(),
            'created_by' => User::factory(),
        ];
    }
}
