<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            Profile::factory()->for($user)->create();
        });
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * ROLE STATES — create the user with the Spatie role already
     * assigned, replacing the two-step create+assignRole dance (and
     * the per-file helper functions invented to avoid name clashes).
     */
    public function admin(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole('admin'));
    }

    public function editorInChief(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole('editor-in-chief'));
    }

    public function managingEditor(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole('managing-editor'));
    }

    public function sectionEditor(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole('section-editor'));
    }

    public function reviewer(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole('reviewer'));
    }

    public function author(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole('author'));
    }

    public function contentManager(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole('content-manager'));
    }
}
