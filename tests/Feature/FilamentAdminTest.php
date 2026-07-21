<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('admin can access filament panel', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();
});

test('non-admin cannot access filament panel', function () {
    $user = User::factory()->create();
    $user->assignRole('author');

    $response = $this->actingAs($user)->get('/admin');

    expect($response->status())->toBeIn([302, 403]);
});

test('editor-in-chief cannot access filament panel', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $response = $this->actingAs($eic)->get('/admin');

    expect($response->status())->toBeIn([302, 403]);
});

test('guest cannot access filament panel', function () {
    $response = $this->get('/admin');

    expect($response->status())->toBeIn([302, 403]);
});
