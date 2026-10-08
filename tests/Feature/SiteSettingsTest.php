<?php

use App\Filament\Pages\SiteSettings;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Cache::flush();
});

function siteSettingsAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    return $admin;
}

test('admin can save site settings without social links', function () {
    Livewire::actingAs(siteSettingsAdmin())
        ->test(SiteSettings::class)
        ->fillForm([
            'social_vk' => null,
            'social_telegram' => null,
            'social_whatsapp' => null,
            'social_rutube' => null,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    foreach (['social_vk', 'social_telegram', 'social_whatsapp', 'social_rutube'] as $key) {
        expect(Setting::get($key))->toBeNull();
    }
});

test('admin can save social links', function () {
    Livewire::actingAs(siteSettingsAdmin())
        ->test(SiteSettings::class)
        ->fillForm(['social_vk' => 'https://vk.com/test'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Setting::get('social_vk'))->toBe('https://vk.com/test');
});

test('admin cannot save an invalid social link', function () {
    Livewire::actingAs(siteSettingsAdmin())
        ->test(SiteSettings::class)
        ->fillForm(['social_vk' => 'not-a-url'])
        ->call('save')
        ->assertHasFormErrors(['social_vk' => 'url']);
});

test('admin can view the site settings page', function () {
    $this->actingAs(siteSettingsAdmin())
        ->get('/admin/site-settings')
        ->assertOk();
});

test('content-manager cannot view the site settings page', function () {
    $contentManager = User::factory()->create();
    $contentManager->assignRole('content-manager');

    $this->actingAs($contentManager)
        ->get('/admin/site-settings')
        ->assertForbidden();
});

test('content-manager cannot save site settings', function () {
    $contentManager = User::factory()->create();
    $contentManager->assignRole('content-manager');

    Livewire::actingAs($contentManager)
        ->test(SiteSettings::class)
        ->assertForbidden();
});

test('public site hides social links when settings are empty', function () {
    Setting::set('social_vk', null);
    Setting::set('social_telegram', null);
    Setting::set('social_whatsapp', null);
    Setting::set('social_rutube', null);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('vk.com')
        ->assertDontSee('t.me')
        ->assertDontSee('whatsapp.com')
        ->assertDontSee('rutube.ru');
});

test('public site shows social links when configured', function () {
    Setting::set('social_vk', 'https://vk.com/example');
    Setting::set('social_telegram', 'https://t.me/example');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('https://vk.com/example')
        ->assertSee('https://t.me/example');
});
