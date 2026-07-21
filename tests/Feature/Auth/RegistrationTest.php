<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('registration screen can be rendered', function () {
    $this->get('/register')->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'last_name' => 'Иванов',
        'first_name' => 'Иван',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'privacy' => true,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registered user gets author role', function () {
    $this->post('/register', [
        'last_name' => 'Петров',
        'first_name' => 'Пётр',
        'email' => 'petrov@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'privacy' => true,
    ]);

    $user = User::where('email', 'petrov@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->hasRole('author'))->toBeTrue();
});

test('registered user has profile created', function () {
    $this->post('/register', [
        'last_name' => 'Сидоров',
        'first_name' => 'Сергей',
        'middle_name' => 'Алексеевич',
        'email' => 'sidorov@example.com',
        'affiliation' => 'КФУ',
        'country' => 'Россия',
        'password' => 'password',
        'password_confirmation' => 'password',
        'privacy' => true,
    ]);

    $user = User::where('email', 'sidorov@example.com')->first();

    expect($user->profile)->not->toBeNull();
    expect($user->profile->last_name)->toBe('Сидоров');
    expect($user->profile->first_name)->toBe('Сергей');
    expect($user->profile->middle_name)->toBe('Алексеевич');
    expect($user->profile->affiliation)->toBe('КФУ');
});

test('registration requires privacy acceptance', function () {
    $this->post('/register', [
        'last_name' => 'Test',
        'first_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('privacy');
});

test('registration requires last_name and first_name', function () {
    $this->post('/register', [
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'privacy' => true,
    ])->assertSessionHasErrors(['last_name', 'first_name']);
});
