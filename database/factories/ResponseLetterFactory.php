<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ResponseLetter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResponseLetter>
 */
class ResponseLetterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_id' => Article::factory()->revision(),
            'round' => 1,
            'body' => fake()->paragraph(),
            'file_path' => 'response-letters/'.fake()->uuid().'.pdf',
            'uploaded_by' => User::factory(),
        ];
    }
}
