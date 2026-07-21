<?php

namespace Database\Factories;

use App\Enums\Country;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'middle_name' => null,
            'affiliation' => fake()->company(),
            'country' => fake()->randomElement(Country::cases())->value,
            'orcid' => null,
            'url' => null,
            'phone' => null,
            'bio' => null,
            'signature' => null,
        ];
    }
}
