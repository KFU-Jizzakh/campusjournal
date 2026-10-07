<?php

use App\Filament\Resources\PageResource\Pages\EditPage;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Exceptions\MaxNestingDepthExceededException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('rich editor state accepts deep tiptap document paths', function () {
    $user = User::factory()->create()->assignRole('content-manager');
    $page = Page::factory()->create(['body' => '<p>text</p>']);

    // Same depth as the production failure: a nested list inside a list item.
    $path = 'data.body.content.0.content.0.content.0.content.0.content.0.content.0';

    Livewire::actingAs($user)
        ->test(EditPage::class, ['record' => $page->id])
        ->set($path, 'deep edit')
        ->assertSet($path, 'deep edit');
});

test('payload guard still rejects absurdly deep property paths', function () {
    $user = User::factory()->create()->assignRole('content-manager');
    $page = Page::factory()->create(['body' => '<p>text</p>']);

    $path = 'data.'.implode('.', array_fill(0, 60, 'content.0'));

    $component = Livewire::actingAs($user)
        ->test(EditPage::class, ['record' => $page->id]);

    expect(fn () => $component->set($path, 'deep edit'))
        ->toThrow(MaxNestingDepthExceededException::class);
});
