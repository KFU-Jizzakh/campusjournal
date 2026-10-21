<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\GalleyRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleyRevision>
 */
class GalleyRevisionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_id' => Article::factory()->production(),
            'requested_by' => User::factory(),
            'comment' => fake()->paragraph(),
        ];
    }
}
