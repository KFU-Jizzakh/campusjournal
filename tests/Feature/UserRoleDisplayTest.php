<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('dashboard navigation shows localized role badge', function () {
    $user = User::factory()->create();
    $user->assignRole('editor-in-chief');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Главный редактор');
});

test('dashboard navigation orders multiple role badges by importance', function () {
    $user = User::factory()->create();
    $user->assignRole(['author', 'reviewer', 'editor-in-chief']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Главный редактор', 'Рецензент', 'Автор']);
});

test('dashboard navigation shows no role badges for roleless user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('user-role-badges');
});

test('role badges expose localized labels and badge colors', function () {
    $user = User::factory()->create();
    $user->assignRole(['admin', 'author']);

    expect($user->roleBadges())->toBe([
        ['label' => 'Администратор', 'color' => 'danger'],
        ['label' => 'Автор', 'color' => 'success'],
    ]);
});

test('role label falls back to humanized slug for unknown role', function () {
    expect(User::roleLabel('meta-reviewer'))->toBe('Meta Reviewer');
});
