<?php

use App\Filament\Resources\OutboxEventResource;
use App\Filament\Resources\OutboxEventResource\Pages\ListOutboxEvents;
use App\Models\Article;
use App\Models\OutboxEvent;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('admin can browse the outbox event log', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $article = Article::factory()->submitted()->create();

    OutboxEvent::log('article.submitted', $article, ['article_id' => $article->id], $admin);

    Livewire::actingAs($admin)
        ->test(ListOutboxEvents::class)
        ->assertSee('article.submitted')
        ->assertSee($admin->email);
});

test('outbox event resource is admin-only', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $contentManager = User::factory()->create();
    $contentManager->assignRole('content-manager');

    expect(OutboxEventResource::canAccess())->toBeFalse();

    $this->actingAs($admin);
    auth()->setUser($admin);
    expect(OutboxEventResource::canAccess())->toBeTrue();

    auth()->setUser($contentManager);
    expect(OutboxEventResource::canAccess())->toBeFalse();
});
