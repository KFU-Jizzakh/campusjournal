<?php

namespace Database\Factories;

use App\Enums\ArticleFileLicense;
use App\Models\CopyrightAgreement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CopyrightAgreement>
 */
class CopyrightAgreementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'version' => fake()->unique()->randomNumber(4),
            'title' => 'Соглашение об авторских правах',
            'short_text' => fake()->paragraph(),
            'full_text' => fake()->paragraphs(3, true),
            'license' => ArticleFileLicense::CcBy,
            'is_active' => true,
            'published_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
