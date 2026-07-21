<?php

use App\Models\CrossrefDeposit;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('user with manage-doi can viewAny crossref deposits', function () {
    $user = User::factory()->create();
    $user->assignRole('editor-in-chief');

    expect($user->can('viewAny', CrossrefDeposit::class))->toBeTrue();
});

test('user with manage-submissions but not manage-doi cannot viewAny crossref deposits', function () {
    $user = User::factory()->create();
    $user->assignRole('author');

    expect($user->can('viewAny', CrossrefDeposit::class))->toBeFalse();
});

test('user with manage-doi can view crossref deposit', function () {
    $user = User::factory()->create();
    $user->assignRole('editor-in-chief');
    $deposit = CrossrefDeposit::factory()->create();

    expect($user->can('view', $deposit))->toBeTrue();
});

test('user without manage-submissions cannot view crossref deposit', function () {
    $user = User::factory()->create();
    $user->assignRole('author');
    $deposit = CrossrefDeposit::factory()->create();

    expect($user->can('view', $deposit))->toBeFalse();
});

test('user with manage-doi can create crossref deposit', function () {
    $user = User::factory()->create();
    $user->assignRole('editor-in-chief');

    expect($user->can('create', CrossrefDeposit::class))->toBeTrue();
});

test('user without manage-doi cannot create crossref deposit', function () {
    $user = User::factory()->create();
    $user->assignRole('section-editor');

    expect($user->can('create', CrossrefDeposit::class))->toBeFalse();
});

test('user with manage-doi can update crossref deposit', function () {
    $user = User::factory()->create();
    $user->assignRole('editor-in-chief');
    $deposit = CrossrefDeposit::factory()->create();

    expect($user->can('update', $deposit))->toBeTrue();
});

test('user without manage-doi cannot update crossref deposit', function () {
    $user = User::factory()->create();
    $user->assignRole('section-editor');
    $deposit = CrossrefDeposit::factory()->create();

    expect($user->can('update', $deposit))->toBeFalse();
});

test('user with manage-doi can delete crossref deposit', function () {
    $user = User::factory()->create();
    $user->assignRole('editor-in-chief');
    $deposit = CrossrefDeposit::factory()->create();

    expect($user->can('delete', $deposit))->toBeTrue();
});

test('user without manage-doi cannot delete crossref deposit', function () {
    $user = User::factory()->create();
    $user->assignRole('section-editor');
    $deposit = CrossrefDeposit::factory()->create();

    expect($user->can('delete', $deposit))->toBeFalse();
});

test('user with manage-doi can restore crossref deposit', function () {
    $user = User::factory()->create();
    $user->assignRole('editor-in-chief');
    $deposit = CrossrefDeposit::factory()->create();

    expect($user->can('restore', $deposit))->toBeTrue();
});

test('user without manage-doi cannot restore crossref deposit', function () {
    $user = User::factory()->create();
    $user->assignRole('section-editor');
    $deposit = CrossrefDeposit::factory()->create();

    expect($user->can('restore', $deposit))->toBeFalse();
});

test('user with manage-doi can forceDelete crossref deposit', function () {
    $user = User::factory()->create();
    $user->assignRole('editor-in-chief');
    $deposit = CrossrefDeposit::factory()->create();

    expect($user->can('forceDelete', $deposit))->toBeTrue();
});

test('user without manage-doi cannot forceDelete crossref deposit', function () {
    $user = User::factory()->create();
    $user->assignRole('section-editor');
    $deposit = CrossrefDeposit::factory()->create();

    expect($user->can('forceDelete', $deposit))->toBeFalse();
});
