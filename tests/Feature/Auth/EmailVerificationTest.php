<?php

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
});

test('email can be verified', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
        absolute: false
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('verification link works behind a TLS-terminating reverse proxy', function () {
    $user = User::factory()->unverified()->create();

    config(['app.url' => 'https://journal.example.com']);

    $notification = new VerifyEmailNotification;
    preg_match_all('/href="([^"]+)"/', $notification->toMail($user)->render(), $matches);
    $verificationUrl = collect($matches[1])
        ->map(fn ($url) => html_entity_decode($url))
        ->first(fn ($url) => str_contains($url, '/verify-email/'));

    expect($verificationUrl)->toStartWith('https://journal.example.com/verify-email/');

    $path = parse_url($verificationUrl, PHP_URL_PATH).'?'.parse_url($verificationUrl, PHP_URL_QUERY);

    $this->actingAs($user)->get($path)
        ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')],
        absolute: false
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('email is not verified with expired link', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->subMinutes(1),
        ['id' => $user->id, 'hash' => sha1($user->email)],
        absolute: false
    );

    $this->actingAs($user)->get($verificationUrl)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('verification email can be resent', function () {
    $user = User::factory()->unverified()->create();

    Notification::fake();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

test('resending verification email is rate limited to 6 per minute', function () {
    $user = User::factory()->unverified()->create();

    Notification::fake();

    for ($i = 0; $i < 6; $i++) {
        $this->actingAs($user)->post(route('verification.send'));
    }

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertStatus(429);
});

test('verification email is queued', function () {
    expect(new VerifyEmailNotification)
        ->toBeInstanceOf(ShouldQueue::class);
});

test('verification email is in Russian', function () {
    $user = User::factory()->unverified()->create();

    Notification::fake();

    $this->actingAs($user)->post(route('verification.send'));

    Notification::assertSentTo($user, VerifyEmailNotification::class, function ($notification) use ($user) {
        $mail = $notification->toMail($user);

        return str_contains($mail->subject, 'Подтвердите ваш email')
            && str_contains($mail->render(), 'Спасибо за регистрацию');
    });
});

test('unverified user is redirected from profile to verification notice', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertRedirect(route('verification.notice'));
});
