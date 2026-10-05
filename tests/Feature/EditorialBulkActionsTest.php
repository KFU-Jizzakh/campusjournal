<?php

use App\Models\Article;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function bulkEicUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('editor-in-chief');

    return $user;
}

test('leadership can bulk assign editor to submitted articles', function () {
    $eic = bulkEicUser();

    $sectionEditor = User::factory()->create();
    $sectionEditor->assignRole('section-editor');

    $first = Article::factory()->submitted()->create();
    $second = Article::factory()->submitted()->create();

    $this->actingAs($eic)
        ->post(route('editorial.bulk-assign-editor'), [
            'article_ids' => [$first->id, $second->id],
            'editor_id' => $sectionEditor->id,
        ])
        ->assertSessionHas('success');

    expect($first->fresh()->editor_id)->toBe($sectionEditor->id)
        ->and($second->fresh()->editor_id)->toBe($sectionEditor->id);
});

test('bulk assign skips articles that cannot take an editor', function () {
    $eic = bulkEicUser();

    $sectionEditor = User::factory()->create();
    $sectionEditor->assignRole('section-editor');

    $assignable = Article::factory()->submitted()->create();
    $inReview = Article::factory()->inReview()->create();

    $this->actingAs($eic)
        ->post(route('editorial.bulk-assign-editor'), [
            'article_ids' => [$assignable->id, $inReview->id],
            'editor_id' => $sectionEditor->id,
        ])
        ->assertSessionHas('success', fn (string $message) => str_contains($message, 'пропущено: 1'));

    expect($assignable->fresh()->editor_id)->toBe($sectionEditor->id)
        ->and($inReview->fresh()->editor_id)->toBeNull();
});

test('section editor is forbidden from bulk assignment', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $article = Article::factory()->submitted()->create();

    $this->actingAs($editor)
        ->post(route('editorial.bulk-assign-editor'), [
            'article_ids' => [$article->id],
            'editor_id' => $editor->id,
        ])
        ->assertForbidden();
});

test('bulk assign rejects non section editor target', function () {
    $eic = bulkEicUser();

    $article = Article::factory()->submitted()->create();
    $plainUser = User::factory()->create();

    $this->actingAs($eic)
        ->post(route('editorial.bulk-assign-editor'), [
            'article_ids' => [$article->id],
            'editor_id' => $plainUser->id,
        ])
        ->assertNotFound();
});

test('bulk bar is visible to leadership only', function () {
    $eic = bulkEicUser();
    $sectionEditor = User::factory()->create();
    $sectionEditor->assignRole('section-editor');

    Article::factory()->submitted()->create();

    $this->actingAs($eic)
        ->get(route('editorial.index'))
        ->assertOk()
        ->assertSee('bulk-check', escape: false);

    $this->actingAs($sectionEditor)
        ->get(route('editorial.index'))
        ->assertOk()
        ->assertDontSee('bulk-check');
});
