<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Issue;
use App\Models\OutboxEvent;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('submitting an article logs submission.created', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $article = Article::submit($user, [
        'title' => 'Test Article',
        'abstract_ru' => 'Abstract',
        'category_id' => Category::factory()->create()->id,
        'pdf_path' => 'submissions/test.pdf',
    ]);

    $event = OutboxEvent::where('name', 'submission.created')
        ->where('subject_id', $article->id)
        ->first();

    expect($event)->not->toBeNull();
    expect($event->actor_id)->toBe($user->id);
    expect($event->payload['title'])->toBe('Test Article');
    expect($event->payload)->toHaveKeys(['title', 'category_id']);
});

test('revising an article logs submission.revised', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $article = Article::factory()->revision()->create(['submitted_by' => $user->id]);
    $article->revise(['title' => 'Revised Title', 'abstract_ru' => 'New abstract']);

    $event = OutboxEvent::where('name', 'submission.revised')
        ->where('subject_id', $article->id)
        ->first();

    expect($event)->not->toBeNull();
    expect($event->payload['title'])->toBe('Revised Title');
});

test('assigning an editor logs editor.assigned', function () {
    $user = User::factory()->create();
    $user->assignRole('editor-in-chief');
    $this->actingAs($user);

    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $article = Article::factory()->submitted()->create();
    $article->assignEditor($editor);

    $event = OutboxEvent::where('name', 'editor.assigned')
        ->where('subject_id', $article->id)
        ->first();

    expect($event)->not->toBeNull();
    expect($event->payload['editor_id'])->toBe($editor->id);
});

test('assigning a reviewer logs reviewer.assigned', function () {
    $user = User::factory()->create();
    $user->assignRole('editor-in-chief');
    $this->actingAs($user);

    $reviewer = User::factory()->create();
    $reviewer->givePermissionTo('review-article');

    $article = Article::factory()->submitted()->create();
    $review = $article->assignReviewer($reviewer, $user);

    $event = OutboxEvent::where('name', 'reviewer.assigned')
        ->where('subject_id', $review->id)
        ->first();

    expect($event)->not->toBeNull();
    expect($event->payload)->toHaveKeys(['article_id', 'reviewer_id', 'response_due_at', 'review_due_at']);
    expect($event->payload['reviewer_id'])->toBe($reviewer->id);
});

test('making a decision logs decision.made', function () {
    $user = User::factory()->create();
    $user->assignRole('editor-in-chief');
    $this->actingAs($user);

    $article = Article::factory()->inReview()->create();
    Review::factory()->completed()->create(['article_id' => $article->id]);

    $article->decide('accept', 'Good work', $user);

    $event = OutboxEvent::where('name', 'decision.made')
        ->where('subject_type', Article::class)
        ->first();

    expect($event)->not->toBeNull();
    expect($event->payload['decision'])->toBe('accept');
    expect($event->payload['new_status'])->toBe('accepted');
});

test('sending to copyediting logs article.sent_to_copyediting', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $article = Article::factory()->accepted()->create();
    $article->sendToCopyediting($user);

    $event = OutboxEvent::where('name', 'article.sent_to_copyediting')->first();

    expect($event)->not->toBeNull();
    expect($event->subject_id)->toBe($article->id);
});

test('sending to production logs article.sent_to_production', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $article = Article::factory()->copyediting()->withCopyeditedFile()->create();
    $article->sendToProduction($user);

    $event = OutboxEvent::where('name', 'article.sent_to_production')->first();

    expect($event)->not->toBeNull();
});

test('publishing an article logs article.published', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $article = Article::factory()->approved()->create();
    $issue = Issue::factory()->create();
    $article->publish($issue);

    $event = OutboxEvent::where('name', 'article.published')->first();

    expect($event)->not->toBeNull();
    expect($event->payload['issue_id'])->toBe($issue->id);
});

test('accepting a review logs review.accepted', function () {
    $reviewer = User::factory()->create();
    $this->actingAs($reviewer);

    $review = Review::factory()->create(['reviewer_id' => $reviewer->id]);
    $review->accept();

    $event = OutboxEvent::where('name', 'review.accepted')
        ->where('subject_id', $review->id)
        ->first();

    expect($event)->not->toBeNull();
    expect($event->payload['article_id'])->toBe($review->article_id);
});

test('declining a review logs review.declined', function () {
    $reviewer = User::factory()->create();
    $this->actingAs($reviewer);

    $review = Review::factory()->create(['reviewer_id' => $reviewer->id]);
    $review->decline();

    $event = OutboxEvent::where('name', 'review.declined')
        ->where('subject_id', $review->id)
        ->first();

    expect($event)->not->toBeNull();
});

test('completing a review logs review.completed', function () {
    $reviewer = User::factory()->create();
    $this->actingAs($reviewer);

    $review = Review::factory()->inProgress()->create(['reviewer_id' => $reviewer->id]);
    $review->complete('accept', 'Great paper', 'Minor suggestions');

    $event = OutboxEvent::where('name', 'review.completed')
        ->where('subject_id', $review->id)
        ->first();

    expect($event)->not->toBeNull();
    expect($event->payload['recommendation'])->toBe('accept');
    expect($event->payload['article_id'])->toBe($review->article_id);
});

test('sending galley to author logs galley.sent_to_author', function () {
    $user = User::factory()->create();
    $user->assignRole('editor-in-chief');
    $this->actingAs($user);

    $article = Article::factory()->production()->create([
        'galley_pdf_path' => 'galley_uploads/test.pdf',
        'galley_uploaded_by' => $user->id,
    ]);
    $article->sendGalleyToAuthor($user);

    $event = OutboxEvent::where('name', 'galley.sent_to_author')
        ->where('subject_id', $article->id)
        ->first();

    expect($event)->not->toBeNull();
    expect($event->actor_id)->toBe($user->id);
    expect($event->payload)->toHaveKey('galley_pdf_path');
});

test('approving galley logs galley.approved', function () {
    $author = User::factory()->create();
    $this->actingAs($author);

    $article = Article::factory()->awaitingApproval()->create([
        'submitted_by' => $author->id,
    ]);
    $article->approveGalley($author);

    $event = OutboxEvent::where('name', 'galley.approved')
        ->where('subject_id', $article->id)
        ->first();

    expect($event)->not->toBeNull();
    expect($event->actor_id)->toBe($author->id);
});

test('requesting galley revision logs galley.revision_requested', function () {
    $author = User::factory()->create();
    $this->actingAs($author);

    $article = Article::factory()->awaitingApproval()->create([
        'submitted_by' => $author->id,
    ]);
    $article->requestGalleyRevision($author, 'Please fix figure 3');

    $event = OutboxEvent::where('name', 'galley.revision_requested')
        ->where('subject_id', $article->id)
        ->first();

    expect($event)->not->toBeNull();
    expect($event->actor_id)->toBe($author->id);
    expect($event->payload['comment'])->toBe('Please fix figure 3');
});

test('galley workflow produces correct event sequence', function () {
    $author = User::factory()->create();
    $editor = User::factory()->create();
    $editor->assignRole('editor-in-chief');
    $this->actingAs($editor);

    $article = Article::factory()->production()->create([
        'submitted_by' => $author->id,
        'galley_pdf_path' => 'galley_uploads/test.pdf',
        'galley_uploaded_by' => $editor->id,
    ]);
    $article->sendGalleyToAuthor($editor);
    $article->refresh();

    $this->actingAs($author);
    $article->requestGalleyRevision($author, 'Fix alignment');
    $article->refresh();
    $article->sendGalleyToAuthor($editor);
    $article->refresh();
    $article->approveGalley($author);

    $events = OutboxEvent::where('subject_id', $article->id)
        ->orderBy('created_at')
        ->pluck('name');

    expect($events->toArray())->toBe([
        'galley.sent_to_author',
        'galley.revision_requested',
        'galley.sent_to_author',
        'galley.approved',
    ]);
});

test('direct model CRUD does not log outbox events', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $article = Article::factory()->create(['submitted_by' => $user->id]);
    $article->update(['title' => 'Changed']);

    expect(OutboxEvent::count())->toBe(0);
});
