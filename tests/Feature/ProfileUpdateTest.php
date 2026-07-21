<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'last_name' => 'Иванов',
            'first_name' => 'Иван',
            'email' => 'updated@example.com',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect($user->email)->toBe('updated@example.com');
    expect($user->profile->last_name)->toBe('Иванов');
    expect($user->profile->first_name)->toBe('Иван');
});

test('email verification resets when email changes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'last_name' => 'Test',
            'first_name' => 'User',
            'email' => 'newemail@example.com',
        ])
        ->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->email)->toBe('newemail@example.com');
});

test('email verification status unchanged when email stays same', function () {
    $user = User::factory()->create();
    $originalVerified = $user->email_verified_at;

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'last_name' => 'Test',
            'first_name' => 'User',
            'email' => $user->email,
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('profile update requires last_name and first_name', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'email' => $user->email,
        ])
        ->assertSessionHasErrors(['last_name', 'first_name']);
});

test('profile can include optional fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'last_name' => 'Тест',
            'first_name' => 'Пользователь',
            'middle_name' => 'Отчество',
            'email' => $user->email,
            'affiliation' => 'КФУ',
            'country' => 'Россия',
            'phone' => '+7-999-123-4567',
            'bio' => 'Биография',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $profile = $user->refresh()->profile;
    expect($profile->middle_name)->toBe('Отчество');
    expect($profile->affiliation)->toBe('КФУ');
    expect($profile->phone)->toBe('+7-999-123-4567');
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ])
        ->assertRedirect('/');

    $this->assertGuest();
    expect(User::find($user->id))->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    expect(User::find($user->id))->not->toBeNull();
});
