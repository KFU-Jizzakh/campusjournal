<?php

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function registerUser(array $extra = []): array
{
    $payload = array_merge([
        'last_name' => 'Новый',
        'first_name' => 'Пользователь',
        'email' => 'newbie@example.com',
        'password' => 'password1234',
        'password_confirmation' => 'password1234',
        'privacy' => '1',
    ], $extra);

    $response = test()->post(route('register'), $payload);

    return [$response, User::where('email', $payload['email'])->first()];
}

test('registration grants author role and optional reviewer role', function () {
    [$response, $user] = registerUser(['become_reviewer' => '1']);

    $response->assertRedirect(route('dashboard'));

    expect($user->hasRole('author'))->toBeTrue()
        ->and($user->hasRole('reviewer'))->toBeTrue();
});

test('registration without checkbox keeps only author role', function () {
    [, $user] = registerUser();

    expect($user->hasRole('author'))->toBeTrue()
        ->and($user->hasRole('reviewer'))->toBeFalse();
});

test('reviewer checkbox is ignored when self-registration setting is closed', function () {
    Setting::set('reviewer_self_registration', '0');

    [, $user] = registerUser(['become_reviewer' => '1']);

    expect($user->hasRole('author'))->toBeTrue()
        ->and($user->hasRole('reviewer'))->toBeFalse();

    $this->get(route('register'))->assertDontSee('become_reviewer');
});

test('reviewer checkbox renders on the registration form when open', function () {
    $this->get(route('register'))->assertSee('become_reviewer');
});

test('profile toggle grants reviewer role when open', function () {
    $user = User::factory()->create();
    $user->assignRole('author');

    $this->actingAs($user)
        ->put(route('profile.reviewer-role.update'), ['wants_to_review' => '1'])
        ->assertRedirect();

    expect($user->fresh()->hasRole('reviewer'))->toBeTrue();
});

test('profile toggle refuses to grant reviewer role when setting is closed', function () {
    Setting::set('reviewer_self_registration', '0');

    $user = User::factory()->create();
    $user->assignRole('author');

    $this->actingAs($user)
        ->put(route('profile.reviewer-role.update'), ['wants_to_review' => '1'])
        ->assertSessionHas('error');

    expect($user->fresh()->hasRole('reviewer'))->toBeFalse();
});

test('user can drop reviewer role without active reviews', function () {
    $user = User::factory()->create();
    $user->assignRole('reviewer');

    $this->actingAs($user)
        ->put(route('profile.reviewer-role.update'), ['wants_to_review' => '0'])
        ->assertSessionHas('success');

    expect($user->fresh()->hasRole('reviewer'))->toBeFalse();
});

test('dropping reviewer role is blocked by active review', function () {
    $user = User::factory()->create();
    $user->assignRole('reviewer');

    Review::factory()->withDeadlines()->create([
        'reviewer_id' => $user->id,
        'status' => ReviewStatus::Pending,
    ]);

    $this->actingAs($user)
        ->put(route('profile.reviewer-role.update'), ['wants_to_review' => '0'])
        ->assertSessionHas('error');

    expect($user->fresh()->hasRole('reviewer'))->toBeTrue();
});

test('dropping reviewer role is allowed with only completed reviews', function () {
    $user = User::factory()->create();
    $user->assignRole('reviewer');

    Review::factory()->completed()->create(['reviewer_id' => $user->id]);

    $this->actingAs($user)
        ->put(route('profile.reviewer-role.update'), ['wants_to_review' => '0']);

    expect($user->fresh()->hasRole('reviewer'))->toBeFalse();
});

test('profile page shows reviewer toggle state', function () {
    $user = User::factory()->create();
    $user->assignRole('reviewer');

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Рецензирование');
});
