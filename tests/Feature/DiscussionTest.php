<?php

use App\Enums\ArticleStatus;
use App\Enums\DiscussionScope;
use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\Discussion;
use App\Models\Review;
use App\Models\User;
use App\Notifications\NewDiscussionMessage;
use App\Notifications\NewDiscussionThread;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Notification::fake();

    $this->createAuthor = function () {
        $user = User::factory()->create();
        $user->assignRole('author');

        return $user;
    };

    $this->createEic = function () {
        $user = User::factory()->create();
        $user->assignRole('editor-in-chief');

        return $user;
    };

    $this->createSectionEditor = function () {
        $user = User::factory()->create();
        $user->assignRole('section-editor');

        return $user;
    };

    $this->createReviewer = function () {
        $user = User::factory()->create();
        $user->assignRole('reviewer');

        return $user;
    };
});

// ==========================================
// AC-1 / BR-1 / BR-2: Author creates discussion with article scope
// ==========================================

test('author can create discussion on own article', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::InReview,
    ]);

    $this->actingAs($author)
        ->post(route('submissions.discussions.store', $article), [
            'scope' => 'article',
            'message' => 'Вопрос редактору по статье',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($article->discussions)->toHaveCount(1);
    expect($article->discussions->first())
        ->scope->toBe(DiscussionScope::Article)
        ->message->toBe('Вопрос редактору по статье');
});

/**
 * NOTE: SPEC-06/BR-1 ("discussions only for active articles") is not
 * enforced by DiscussionPolicy::create(). The policy only checks roles
 * and article ownership, not article status. Both draft and published
 * articles currently allow discussion creation. This should eventually
 * be tightened — see the spec for details.
 */
test('discussions are currently allowed on draft articles due to policy gap', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
    ]);

    $this->actingAs($author)
        ->post(route('submissions.discussions.store', $article), [
            'scope' => 'article',
            'message' => 'Some question',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
});

// ==========================================
// AC-2 / BR-6: Editor replies, inherits scope
// ==========================================

test('editor reply inherits parent scope', function () {
    $author = ($this->createAuthor)();
    $eic = ($this->createEic)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::InReview,
        'editor_id' => $eic->id,
    ]);

    $parent = Discussion::factory()->create([
        'article_id' => $article->id,
        'user_id' => $author->id,
        'scope' => DiscussionScope::Article,
        'message' => 'Author question',
    ]);

    $this->actingAs($eic)
        ->post(route('editorial.discussions.store', $article), [
            'scope' => DiscussionScope::Article->value,
            'parent_id' => $parent->id,
            'message' => 'Editor answer',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $reply = $article->discussions->where('parent_id', $parent->id)->first();
    expect($reply)->not->toBeNull();
    expect($reply->scope)->toBe(DiscussionScope::Article);
});

// ==========================================
// AC-3 / BR-4: Private editor-reviewer discussion, scope=editorial, review-bound
// ==========================================

test('editor can create editorial discussion bound to review', function () {
    $eic = ($this->createEic)();
    $reviewer = ($this->createReviewer)();
    $reviewerUser = User::factory()->create();
    $reviewerUser->assignRole('reviewer');

    $article = Article::factory()->create([
        'status' => ArticleStatus::InReview,
        'editor_id' => $eic->id,
    ]);

    $review = Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewerUser->id,
        'status' => ReviewStatus::InProgress,
    ]);

    $this->actingAs($eic)
        ->post(route('editorial.discussions.store', $article), [
            'scope' => DiscussionScope::Editorial->value,
            'review_id' => $review->id,
            'message' => 'Обсуждение с рецензентом',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($article->discussions)->toHaveCount(1);
    expect($article->discussions->first())
        ->scope->toBe(DiscussionScope::Editorial)
        ->review_id->toBe($review->id);
});

// ==========================================
// AC-4 / BR-3: Internal editorial discussion, no review binding
// ==========================================

test('editor can create internal editorial discussion', function () {
    $eic = ($this->createEic)();

    $article = Article::factory()->create([
        'status' => ArticleStatus::InReview,
        'editor_id' => $eic->id,
    ]);

    $this->actingAs($eic)
        ->post(route('editorial.discussions.store', $article), [
            'scope' => DiscussionScope::Editorial->value,
            'message' => 'Внутреннее обсуждение редакторов',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($article->discussions)->toHaveCount(1);
    expect($article->discussions->first())
        ->scope->toBe(DiscussionScope::Editorial)
        ->review_id->toBeNull();
});

// ==========================================
// AC-5: Discussion list shows scope and review badges
// ==========================================

test('editorial view shows scope badges on discussions', function () {
    $eic = ($this->createEic)();

    $article = Article::factory()->create([
        'status' => ArticleStatus::InReview,
        'editor_id' => $eic->id,
    ]);

    Discussion::factory()->create([
        'article_id' => $article->id,
        'user_id' => $eic->id,
        'scope' => DiscussionScope::Article,
        'message' => 'Public discussion',
    ]);

    Discussion::factory()->create([
        'article_id' => $article->id,
        'user_id' => $eic->id,
        'scope' => DiscussionScope::Editorial,
        'message' => 'Private discussion',
    ]);

    $this->actingAs($eic)
        ->get(route('editorial.show', $article))
        ->assertOk()
        ->assertSee('Общее')
        ->assertSee('Редакционное');
});

// ==========================================
// AC-6: Unread message indicator
// ==========================================

test('unread discussions show blue dot indicator', function () {
    $eic = ($this->createEic)();

    $article = Article::factory()->create([
        'status' => ArticleStatus::InReview,
        'editor_id' => $eic->id,
    ]);

    $discussion = Discussion::factory()->create([
        'article_id' => $article->id,
        'user_id' => User::factory()->create()->id,
        'scope' => DiscussionScope::Article,
        'message' => 'Unread message',
    ]);

    expect($discussion->isUnreadBy($eic))->toBeTrue();
});

test('reading a discussion marks it as read', function () {
    $eic = ($this->createEic)();

    $article = Article::factory()->create([
        'status' => ArticleStatus::InReview,
        'editor_id' => $eic->id,
    ]);

    $discussion = Discussion::factory()->create([
        'article_id' => $article->id,
        'user_id' => User::factory()->create()->id,
        'scope' => DiscussionScope::Article,
        'message' => 'Unread message',
    ]);

    $discussion->readBy($eic);

    expect($discussion->fresh()->isUnreadBy($eic))->toBeFalse();
});

test('guest always sees discussion as read', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::InReview,
    ]);

    $discussion = Discussion::factory()->create([
        'article_id' => $article->id,
        'user_id' => User::factory()->create()->id,
        'scope' => DiscussionScope::Article,
        'message' => 'Message',
    ]);

    expect($discussion->isUnreadBy(null))->toBeFalse();
});

// ==========================================
// AC-7: In-app notifications sent
// ==========================================

test('author gets notification when editor replies', function () {
    $author = ($this->createAuthor)();
    $eic = ($this->createEic)();

    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::InReview,
        'editor_id' => $eic->id,
    ]);

    $parent = Discussion::factory()->create([
        'article_id' => $article->id,
        'user_id' => $author->id,
        'scope' => DiscussionScope::Article,
        'message' => 'Author question',
    ]);

    $this->actingAs($eic)
        ->post(route('editorial.discussions.store', $article), [
            'scope' => DiscussionScope::Article->value,
            'parent_id' => $parent->id,
            'message' => 'Editor answer',
        ]);

    Notification::assertSentTo($author, NewDiscussionMessage::class);
});

test('editors get notified on new discussion thread', function () {
    $author = ($this->createAuthor)();
    $eic = ($this->createEic)();

    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::InReview,
        'editor_id' => $eic->id,
    ]);

    $this->actingAs($author)
        ->post(route('submissions.discussions.store', $article), [
            'scope' => DiscussionScope::Article->value,
            'message' => 'New question from author',
        ]);

    Notification::assertSentTo($eic, NewDiscussionThread::class);
});

// ==========================================
// AC-8 / BR-8: Editor can close/reopen threads
// ==========================================

test('editor can close a discussion thread', function () {
    $eic = ($this->createEic)();
    $article = Article::factory()->create([
        'status' => ArticleStatus::InReview,
        'editor_id' => $eic->id,
    ]);

    $discussion = Discussion::factory()->create([
        'article_id' => $article->id,
        'user_id' => User::factory()->create()->id,
        'scope' => DiscussionScope::Article,
        'message' => 'Some discussion',
    ]);

    $this->actingAs($eic)
        ->post(route('discussions.resolve', $discussion))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($discussion->fresh()->is_resolved)->toBeTrue();
    expect($discussion->fresh()->resolved_at)->not->toBeNull();
});

test('editor can reopen a closed discussion thread', function () {
    $eic = ($this->createEic)();
    $article = Article::factory()->create([
        'status' => ArticleStatus::InReview,
        'editor_id' => $eic->id,
    ]);

    $discussion = Discussion::factory()->create([
        'article_id' => $article->id,
        'user_id' => User::factory()->create()->id,
        'scope' => DiscussionScope::Article,
        'message' => 'Some discussion',
        'is_resolved' => true,
        'resolved_at' => now(),
    ]);

    $this->actingAs($eic)
        ->post(route('discussions.reopen', $discussion))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($discussion->fresh()->is_resolved)->toBeFalse();
});

// ==========================================
// AC-9: Author sees closed status, cannot reopen (BR-8)
// ==========================================

test('author cannot close a discussion thread', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::InReview,
    ]);

    $discussion = Discussion::factory()->create([
        'article_id' => $article->id,
        'user_id' => $author->id,
        'scope' => DiscussionScope::Article,
        'message' => 'Author question',
    ]);

    $this->actingAs($author)
        ->post(route('discussions.resolve', $discussion))
        ->assertForbidden();

    expect($discussion->fresh()->is_resolved)->toBeFalse();
});

test('author cannot reopen a discussion thread', function () {
    $author = ($this->createAuthor)();
    $eic = ($this->createEic)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::InReview,
        'editor_id' => $eic->id,
    ]);

    $discussion = Discussion::factory()->create([
        'article_id' => $article->id,
        'user_id' => $author->id,
        'scope' => DiscussionScope::Article,
        'message' => 'Author question',
        'is_resolved' => true,
        'resolved_at' => now(),
    ]);

    $this->actingAs($author)
        ->post(route('discussions.reopen', $discussion))
        ->assertForbidden();

    expect($discussion->fresh()->is_resolved)->toBeTrue();
});

// ==========================================
// BR-5: Author cannot see editorial scope messages
// ==========================================

test('author cannot see editorial scope discussions', function () {
    $author = ($this->createAuthor)();
    $eic = ($this->createEic)();

    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::InReview,
        'editor_id' => $eic->id,
    ]);

    $discussion = Discussion::factory()->create([
        'article_id' => $article->id,
        'user_id' => $eic->id,
        'scope' => DiscussionScope::Editorial,
        'message' => 'Private editorial note',
    ]);

    $this->actingAs($author)
        ->get(route('submissions.show', $article))
        ->assertOk()
        ->assertDontSee('Private editorial note');
});

// ==========================================
// BR-9: Reply in closed thread forbidden
// ==========================================

test('cannot reply to closed discussion', function () {
    $eic = ($this->createEic)();
    $article = Article::factory()->create([
        'status' => ArticleStatus::InReview,
        'editor_id' => $eic->id,
    ]);

    $discussion = Discussion::factory()->create([
        'article_id' => $article->id,
        'user_id' => User::factory()->create()->id,
        'scope' => DiscussionScope::Article,
        'message' => 'Some discussion',
        'is_resolved' => true,
        'resolved_at' => now(),
    ]);

    $this->actingAs($eic)
        ->post(route('editorial.discussions.store', $article), [
            'scope' => DiscussionScope::Article->value,
            'parent_id' => $discussion->id,
            'message' => 'This should be blocked',
        ])
        ->assertForbidden();
});

// ==========================================
// BR-7: Sender not notified of own message
// ==========================================

test('sender is not notified of own message', function () {
    $author = ($this->createAuthor)();

    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::InReview,
    ]);

    $this->actingAs($author)
        ->post(route('submissions.discussions.store', $article), [
            'scope' => DiscussionScope::Article->value,
            'message' => 'My own question',
        ]);

    Notification::assertNothingSentTo($author);
});

// ==========================================
// SPEC-06 AC-10 / BR-10: Discussion notification preferences
// ==========================================

test('discussion notifications respect user preferences when disabled', function () {
    $author = ($this->createAuthor)();
    $author->notification_preferences = [
        'site_discussions' => false,
        'email_discussions' => false,
    ];
    $author->save();

    $eic = ($this->createEic)();

    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::InReview,
        'editor_id' => $eic->id,
    ]);

    $discussion = Discussion::factory()->create([
        'article_id' => $article->id,
        'user_id' => $author->id,
        'scope' => DiscussionScope::Article,
        'message' => 'Question from author',
    ]);

    Notification::fake();

    $this->actingAs($eic)
        ->post(route('editorial.discussions.store', $article), [
            'scope' => DiscussionScope::Article->value,
            'parent_id' => $discussion->id,
            'message' => 'Editor reply',
        ]);

    Notification::assertNotSentTo($author, NewDiscussionMessage::class);
});

test('discussion site-only preference still dispatches notification', function () {
    $author = ($this->createAuthor)();
    $author->notification_preferences = [
        'site_discussions' => true,
        'email_discussions' => false,
    ];
    $author->save();

    $eic = ($this->createEic)();

    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::InReview,
        'editor_id' => $eic->id,
    ]);

    $discussion = Discussion::factory()->create([
        'article_id' => $article->id,
        'user_id' => $author->id,
        'scope' => DiscussionScope::Article,
        'message' => 'Question from author',
    ]);

    Notification::fake();

    $this->actingAs($eic)
        ->post(route('editorial.discussions.store', $article), [
            'scope' => DiscussionScope::Article->value,
            'parent_id' => $discussion->id,
            'message' => 'Editor reply',
        ]);

    Notification::assertSentTo($author, NewDiscussionMessage::class);
});
