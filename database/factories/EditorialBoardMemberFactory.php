<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\EditorialBoardMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EditorialBoardMember> */
class EditorialBoardMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'author_id' => Author::factory(),
            'role' => fake()->randomElement(['chief-editor', 'member', 'reviewer']),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
