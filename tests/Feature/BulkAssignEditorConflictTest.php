<?php

use App\Models\Article;
use App\Models\Author;
use App\Models\User;

function bulkSectionEditor(): User
{
    $user = User::factory()->create();
    $user->assignRole('section-editor');

    return $user;
}

test('bulk assignment skips an article submitted by the acting leadership user', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $ownArticle = Article::factory()->submitted()->create([
        'submitted_by' => $eic->id,
        'title' => 'Собственная статья главреда',
    ]);
    $otherArticle = Article::factory()->submitted()->create([
        'title' => 'Чужая статья',
    ]);

    $editor = bulkSectionEditor();

    $this->actingAs($eic)
        ->post(route('editorial.bulk-assign-editor'), [
            'article_ids' => [$ownArticle->id, $otherArticle->id],
            'editor_id' => $editor->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($ownArticle->refresh()->editor_id)
        ->toBeNull()
        ->and($otherArticle->refresh()->editor_id)
        ->toBe($editor->id);
});

test('bulk assignment skips an article listing the acting leadership user as unclaimed coauthor', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $article = Article::factory()->submitted()->create();
    $article->authors()->attach(
        Author::factory()->create()->id,
        ['email' => $eic->email]
    );

    $editor = bulkSectionEditor();

    $this->actingAs($eic)
        ->post(route('editorial.bulk-assign-editor'), [
            'article_ids' => [$article->id],
            'editor_id' => $editor->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error', __('dashboard.bulk_none_assigned'));

    expect($article->refresh()->editor_id)->toBeNull();
});

test('bulk assignment reports mixed results to the acting user', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $ownArticle = Article::factory()->submitted()->create(['submitted_by' => $eic->id]);
    $otherArticle = Article::factory()->submitted()->create();

    $editor = bulkSectionEditor();

    $this->actingAs($eic)
        ->post(route('editorial.bulk-assign-editor'), [
            'article_ids' => [$ownArticle->id, $otherArticle->id],
            'editor_id' => $editor->id,
        ])
        ->assertSessionHas('success', __('dashboard.bulk_assigned', [
            'assigned' => 1,
            'skipped' => 1,
        ]));
});

test('section editor cannot use bulk assignment', function () {
    $editor = bulkSectionEditor();
    $article = Article::factory()->submitted()->create();

    $this->actingAs($editor)
        ->post(route('editorial.bulk-assign-editor'), [
            'article_ids' => [$article->id],
            'editor_id' => $editor->id,
        ])
        ->assertForbidden();
});
