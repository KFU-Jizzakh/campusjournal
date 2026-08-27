<?php

use App\Enums\ArticleStatus;
use App\Filament\Resources\IssueResource\Pages\EditIssue;
use App\Models\Article;
use App\Models\Issue;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('unpublishing an issue with published articles shows a notification and does not save', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $issue = Issue::factory()->create(['status' => 'published']);
    Article::factory()->published()->create(['issue_id' => $issue->id]);

    Livewire::actingAs($admin)
        ->test(EditIssue::class, ['record' => $issue->getRouteKey()])
        ->fillForm(['status' => 'planned'])
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    expect($issue->fresh()->status)->toBe('published');
});

test('unpublishing an issue with only retracted articles shows a notification and does not save', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $issue = Issue::factory()->create(['status' => 'published']);
    Article::factory()->published()->create([
        'issue_id' => $issue->id,
        'status' => ArticleStatus::Retracted,
    ]);

    Livewire::actingAs($admin)
        ->test(EditIssue::class, ['record' => $issue->getRouteKey()])
        ->fillForm(['status' => 'in_progress'])
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    expect($issue->fresh()->status)->toBe('published');
});

test('unpublishing an empty issue saves the new status', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $issue = Issue::factory()->create(['status' => 'published']);

    Livewire::actingAs($admin)
        ->test(EditIssue::class, ['record' => $issue->getRouteKey()])
        ->fillForm(['status' => 'planned'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($issue->fresh()->status)->toBe('planned');
});

test('editing an issue with published articles without changing status saves', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $issue = Issue::factory()->create(['status' => 'published']);
    Article::factory()->published()->create(['issue_id' => $issue->id]);

    Livewire::actingAs($admin)
        ->test(EditIssue::class, ['record' => $issue->getRouteKey()])
        ->fillForm(['title' => 'Новое название'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($issue->fresh()->title)->toBe('Новое название');
    expect($issue->fresh()->status)->toBe('published');
});
