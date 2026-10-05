<?php

use App\Models\Profile;
use App\Models\User;

test('interests are stored as an array of trimmed tags', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'last_name' => 'Иванов',
            'first_name' => 'Иван',
            'email' => $user->email,
            'interests' => ' РКИ, методика,рки , , перевод ',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $interests = $user->refresh()->profile->interests;

    expect($interests)->toBe(['РКИ', 'методика', 'рки', 'перевод']);
});

test('empty interests are stored as null', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'last_name' => 'Иванов',
            'first_name' => 'Иван',
            'email' => $user->email,
            'interests' => ' , , ',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->profile->interests)->toBeNull();
});

test('existing interests can be cleared by omitting the field', function () {
    $user = User::factory()->create();
    $user->profile()->updateOrCreate(
        ['user_id' => $user->id],
        ['last_name' => 'Иванов', 'first_name' => 'Иван', 'interests' => ['РКИ']]
    );

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'last_name' => 'Иванов',
            'first_name' => 'Иван',
            'email' => $user->email,
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->profile->interests)->toBeNull();
});

test('interests are limited to 1000 characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'last_name' => 'Иванов',
            'first_name' => 'Иван',
            'email' => $user->email,
            'interests' => str_repeat('а', 1001),
        ])
        ->assertSessionHasErrors('interests');
});

test('interests field is rendered on the profile edit page', function () {
    $user = User::factory()->create();
    $user->profile()->updateOrCreate(
        ['user_id' => $user->id],
        ['last_name' => 'Иванов', 'first_name' => 'Иван', 'interests' => ['РКИ', 'методика']]
    );

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('name="interests"', false)
        ->assertSee('РКИ, методика');
});

test('profile interests cast works on the model level', function () {
    $user = User::factory()->create();
    $profile = Profile::factory()->create(['user_id' => $user->id, 'interests' => ['a', 'b']]);

    expect($profile->refresh()->interests)->toBe(['a', 'b']);
});
