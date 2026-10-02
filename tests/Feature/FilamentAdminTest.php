<?php

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

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

test('creating user in filament sends verification email', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Notification::fake();

    Livewire::actingAs($admin)
        ->test(CreateUser::class)
        ->fillForm([
            'email' => 'newuser@example.com',
            'password' => 'password1234',
            'profile.last_name' => 'Новый',
            'profile.first_name' => 'Пользователь',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'newuser@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

test('changing user email in filament resets verification and sends email', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create();

    Notification::fake();

    Livewire::actingAs($admin)
        ->test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['email' => 'changed@example.com'])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect($user->email)->toBe('changed@example.com');
    expect($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

test('saving user in filament without changing email keeps verification', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create();

    Notification::fake();

    Livewire::actingAs($admin)
        ->test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['profile.last_name' => 'Изменённая'])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect($user->hasVerifiedEmail())->toBeTrue();

    Notification::assertNotSentTo($user, VerifyEmailNotification::class);
});

test('admin can manually verify a user without sending an email', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->unverified()->create();

    Notification::fake();

    Livewire::actingAs($admin)
        ->test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['email_verified_at' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect($user->hasVerifiedEmail())->toBeTrue();

    Notification::assertNotSentTo($user, VerifyEmailNotification::class);
});

test('admin can manually unverify a user without sending an email', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create();

    Notification::fake();

    Livewire::actingAs($admin)
        ->test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['email_verified_at' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertNotSentTo($user, VerifyEmailNotification::class);
});

test('creating verified user in filament skips verification email', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Notification::fake();

    Livewire::actingAs($admin)
        ->test(CreateUser::class)
        ->fillForm([
            'email' => 'verified@example.com',
            'password' => 'password1234',
            'email_verified_at' => true,
            'profile.last_name' => 'Новый',
            'profile.first_name' => 'Пользователь',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'verified@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->hasVerifiedEmail())->toBeTrue();

    Notification::assertNotSentTo($user, VerifyEmailNotification::class);
});

test('user list shows localized role labels instead of slugs', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->assertSee('Главный редактор')
        ->assertDontSee('editor-in-chief');
});
