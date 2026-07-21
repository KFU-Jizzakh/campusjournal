<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// --- view ---

test('submitter can view own article', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['submitted_by' => $user->id]);

    expect($user->can('view', $article))->toBeTrue();
});

test('other user cannot view article', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create();

    expect($user->can('view', $article))->toBeFalse();
});

// --- update ---

test('submitter can update draft article', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['submitted_by' => $user->id, 'status' => ArticleStatus::Draft]);

    expect($user->can('update', $article))->toBeTrue();
});

test('submitter can update revision article', function () {
    $user = User::factory()->create();
    $article = Article::factory()->revision()->create(['submitted_by' => $user->id]);

    expect($user->can('update', $article))->toBeTrue();
});

test('submitter cannot update submitted article', function () {
    $user = User::factory()->create();
    $article = Article::factory()->submitted()->create(['submitted_by' => $user->id]);

    expect($user->can('update', $article))->toBeFalse();
});

test('other user cannot update article even if draft', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['status' => ArticleStatus::Draft]);

    expect($user->can('update', $article))->toBeFalse();
});

// --- viewEditorial ---

test('editor-in-chief can view editorial for non-draft article', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');
    $article = Article::factory()->submitted()->create();

    expect($eic->can('viewEditorial', $article))->toBeTrue();
});

test('managing-editor can view editorial for non-draft article', function () {
    $me = User::factory()->create();
    $me->assignRole('managing-editor');
    $article = Article::factory()->submitted()->create();

    expect($me->can('viewEditorial', $article))->toBeTrue();
});

test('section-editor can view editorial for assigned article', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');
    $article = Article::factory()->submitted()->create(['editor_id' => $editor->id]);

    expect($editor->can('viewEditorial', $article))->toBeTrue();
});

test('section-editor cannot view editorial for unassigned article', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');
    $article = Article::factory()->submitted()->create();

    expect($editor->can('viewEditorial', $article))->toBeFalse();
});

test('nobody can view editorial for draft article', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');
    $article = Article::factory()->create(['status' => ArticleStatus::Draft]);

    expect($eic->can('viewEditorial', $article))->toBeFalse();
});

// --- decide ---

test('editor-in-chief can decide on non-draft article', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');
    $article = Article::factory()->submitted()->create();

    expect($eic->can('decide', $article))->toBeTrue();
});

test('section-editor can decide on assigned article', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');
    $article = Article::factory()->submitted()->create(['editor_id' => $editor->id]);

    expect($editor->can('decide', $article))->toBeTrue();
});

test('section-editor cannot decide on unassigned article', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');
    $article = Article::factory()->submitted()->create();

    expect($editor->can('decide', $article))->toBeFalse();
});

// --- sendToCopyediting ---

test('editor-in-chief can sendToCopyediting on non-draft article', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');
    $article = Article::factory()->accepted()->create();

    expect($eic->can('sendToCopyediting', $article))->toBeTrue();
});

test('section-editor can sendToCopyediting on assigned article', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');
    $article = Article::factory()->accepted()->create(['editor_id' => $editor->id]);

    expect($editor->can('sendToCopyediting', $article))->toBeTrue();
});

test('section-editor cannot sendToCopyediting on unassigned article', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');
    $article = Article::factory()->accepted()->create();

    expect($editor->can('sendToCopyediting', $article))->toBeFalse();
});

// --- sendToProduction ---

test('editor-in-chief can sendToProduction on non-draft article', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');
    $article = Article::factory()->copyediting()->create();

    expect($eic->can('sendToProduction', $article))->toBeTrue();
});

test('section-editor can sendToProduction on assigned article', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');
    $article = Article::factory()->copyediting()->create(['editor_id' => $editor->id]);

    expect($editor->can('sendToProduction', $article))->toBeTrue();
});

test('section-editor cannot sendToProduction on unassigned article', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');
    $article = Article::factory()->copyediting()->create();

    expect($editor->can('sendToProduction', $article))->toBeFalse();
});

// --- Galley policies (SPEC-13) ---

test('editor-in-chief can send galley to author', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');
    $article = Article::factory()->production()->create();

    expect($eic->can('sendGalleyToAuthor', $article))->toBeTrue();
});

test('author cannot send galley to author', function () {
    $author = User::factory()->create();
    $author->assignRole('author');
    $article = Article::factory()->production()->create(['submitted_by' => $author->id]);

    expect($author->can('sendGalleyToAuthor', $article))->toBeFalse();
});

test('submitter can approve galley for own article', function () {
    $author = User::factory()->create();
    $article = Article::factory()->awaitingApproval()->create(['submitted_by' => $author->id]);

    expect($author->can('approveGalley', $article))->toBeTrue();
});

test('other user cannot approve galley', function () {
    $other = User::factory()->create();
    $article = Article::factory()->awaitingApproval()->create();

    expect($other->can('approveGalley', $article))->toBeFalse();
});

test('submitter can request galley revision', function () {
    $author = User::factory()->create();
    $article = Article::factory()->awaitingApproval()->create(['submitted_by' => $author->id]);

    expect($author->can('requestGalleyRevision', $article))->toBeTrue();
});

test('other user cannot request galley revision', function () {
    $other = User::factory()->create();
    $article = Article::factory()->awaitingApproval()->create();

    expect($other->can('requestGalleyRevision', $article))->toBeFalse();
});

// --- uploadCopyeditedFile ---

test('section-editor can uploadCopyeditedFile on assigned article', function () {
    $editor = User::factory()->create()->assignRole('section-editor');
    $article = Article::factory()->copyediting()->create(['editor_id' => $editor->id]);

    expect($editor->can('uploadCopyeditedFile', $article))->toBeTrue();
});

test('section-editor cannot uploadCopyeditedFile on unassigned article', function () {
    $editor = User::factory()->create()->assignRole('section-editor');
    $article = Article::factory()->copyediting()->create();

    expect($editor->can('uploadCopyeditedFile', $article))->toBeFalse();
});

// --- deleteCopyeditedFile ---

test('section-editor can deleteCopyeditedFile on assigned article', function () {
    $editor = User::factory()->create()->assignRole('section-editor');
    $article = Article::factory()->copyediting()->withCopyeditedFile()->create(['editor_id' => $editor->id]);

    expect($editor->can('deleteCopyeditedFile', $article))->toBeTrue();
});

test('section-editor cannot deleteCopyeditedFile on unassigned article', function () {
    $editor = User::factory()->create()->assignRole('section-editor');
    $article = Article::factory()->copyediting()->withCopyeditedFile()->create();

    expect($editor->can('deleteCopyeditedFile', $article))->toBeFalse();
});

// --- downloadCopyeditedFile ---

test('submitter can download copyedited file', function () {
    $author = User::factory()->create();
    $article = Article::factory()->copyediting()->withCopyeditedFile()->create(['submitted_by' => $author->id]);

    expect($author->can('downloadCopyeditedFile', $article))->toBeTrue();
});

test('random user cannot download copyedited file', function () {
    $other = User::factory()->create();
    $article = Article::factory()->copyediting()->withCopyeditedFile()->create();

    expect($other->can('downloadCopyeditedFile', $article))->toBeFalse();
});

test('section-editor can download copyedited file on assigned article', function () {
    $editor = User::factory()->create()->assignRole('section-editor');
    $article = Article::factory()->copyediting()->withCopyeditedFile()->create(['editor_id' => $editor->id]);

    expect($editor->can('downloadCopyeditedFile', $article))->toBeTrue();
});

test('section-editor cannot download copyedited file on unassigned article', function () {
    $editor = User::factory()->create()->assignRole('section-editor');
    $article = Article::factory()->copyediting()->withCopyeditedFile()->create();

    expect($editor->can('downloadCopyeditedFile', $article))->toBeFalse();
});
