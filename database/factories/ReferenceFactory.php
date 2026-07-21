<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Reference;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Reference> */
class ReferenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'raw' => fake()->sentence(10),
            'doi' => '10.'.fake()->randomNumber(5).'/'.fake()->slug(),
            'order' => fake()->numberBetween(1, 20),
            'cited_count' => 0,
        ];
    }
}
