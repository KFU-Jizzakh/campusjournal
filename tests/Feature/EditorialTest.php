<?php

use App\Enums\ArticleStatus;
use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\Issue;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function createEic(): User
{
    $user = User::factory()->create();
    $user->assignRole('editor-in-chief');

    return $user;
}

function createSectionEditor(): User
{
    $user = User::factory()->create();
    $user->assignRole('section-editor');

    return $user;
}

function createReviewer(): User
{
    $user = User::factory()->create();
    $user->assignRole('reviewer');

    return $user;
}

function createAuthorUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('author');

    return $user;
}

function createPdfFile(): UploadedFile
{
    return UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');
}

// --- Index ---

test('editor-in-chief can view editorial index', function () {
    $this->actingAs(createEic())
        ->get(route('editorial.index'))
        ->assertOk();
});

test('section editor sees only assigned articles', function () {
    $editor = createSectionEditor();
    $assigned = Article::factory()->submitted()->create([
        'editor_id' => $editor->id,
        'title' => 'Assigned Article Title',
    ]);
    $other = Article::factory()->submitted()->create([
        'title' => 'Unassigned Article Title',
    ]);

    $response = $this->actingAs($editor)
        ->get(route('editorial.index'));

    $response->assertOk();
    $response->assertSee('Assigned Article Title');
    $response->assertDontSee('Unassigned Article Title');
});

test('guest cannot access editorial', function () {
    $this->get(route('editorial.index'))
        ->assertRedirect(route('login'));
});

test('author cannot access editorial', function () {
    $user = User::factory()->create();
    $user->assignRole('author');

    $this->actingAs($user)
        ->get(route('editorial.index'))
        ->assertForbidden();
});

// --- Show ---

test('editor-in-chief can view any non-draft article editorially', function () {
    $article = Article::factory()->submitted()->create();

    $this->actingAs(createEic())
        ->get(route('editorial.show', $article))
        ->assertOk();
});

test('section editor cannot view unassigned article', function () {
    $editor = createSectionEditor();
    $article = Article::factory()->submitted()->create();

    $this->actingAs($editor)
        ->get(route('editorial.show', $article))
        ->assertForbidden();
});

test('nobody can view draft article editorially', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Draft]);

    $this->actingAs(createEic())
        ->get(route('editorial.show', $article))
        ->assertForbidden();
});

// --- Assign editor ---

test('editor-in-chief can assign section editor', function () {
    $eic = createEic();
    $sectionEditor = createSectionEditor();
    $article = Article::factory()->submitted()->create();

    $this->actingAs($eic)
        ->post(route('editorial.assign-editor', $article), [
            'editor_id' => $sectionEditor->id,
        ])
        ->assertRedirect();

    expect($article->refresh()->editor_id)->toBe($sectionEditor->id);
});

test('section editor cannot assign editor', function () {
    $editor = createSectionEditor();
    $article = Article::factory()->submitted()->create(['editor_id' => $editor->id]);
    $otherEditor = createSectionEditor();

    $this->actingAs($editor)
        ->post(route('editorial.assign-editor', $article), [
            'editor_id' => $otherEditor->id,
        ])
        ->assertForbidden();
});

test('cannot assign editor to non-submitted article', function () {
    $eic = createEic();
    $sectionEditor = createSectionEditor();
    $article = Article::factory()->inReview()->create();

    $this->actingAs($eic)
        ->post(route('editorial.assign-editor', $article), [
            'editor_id' => $sectionEditor->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('cannot assign non-section-editor as editor', function () {
    $eic = createEic();
    $regularUser = User::factory()->create();
    $regularUser->assignRole('author');
    $article = Article::factory()->submitted()->create();

    $this->actingAs($eic)
        ->post(route('editorial.assign-editor', $article), [
            'editor_id' => $regularUser->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('editor-in-chief can re-assign section editor after revision resubmission', function () {
    $eic = createEic();
    $sectionEditor = createSectionEditor();
    $article = Article::factory()->revision()->create();

    $article->revise(['title' => 'Resubmitted Title', 'abstract_ru' => 'New abstract']);
    $article->refresh();

    expect($article->status)->toBe(ArticleStatus::Submitted);

    $this->actingAs($eic)
        ->post(route('editorial.assign-editor', $article), [
            'editor_id' => $sectionEditor->id,
        ])
        ->assertRedirect();

    expect($article->refresh()->editor_id)->toBe($sectionEditor->id);
});

// --- Assign reviewer ---

test('editor can assign reviewer to submitted article', function () {
    $eic = createEic();
    $reviewer = createReviewer();
    $article = Article::factory()->submitted()->create();

    $this->actingAs($eic)
        ->post(route('editorial.assign-reviewer', $article), [
            'reviewer_id' => $reviewer->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($article->reviews)->toHaveCount(1);
    expect($article->refresh()->status)->toBe(ArticleStatus::InReview);
});

test('assigning reviewer to in_review article does not change status', function () {
    $eic = createEic();
    $reviewer = createReviewer();
    $article = Article::factory()->inReview()->create();

    $this->actingAs($eic)
        ->post(route('editorial.assign-reviewer', $article), [
            'reviewer_id' => $reviewer->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($article->refresh()->status)->toBe(ArticleStatus::InReview);
});

test('cannot assign duplicate non-declined reviewer', function () {
    $eic = createEic();
    $reviewer = createReviewer();
    $article = Article::factory()->inReview()->create();

    Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::Pending,
    ]);

    $this->actingAs($eic)
        ->post(route('editorial.assign-reviewer', $article), [
            'reviewer_id' => $reviewer->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('can re-assign declined reviewer', function () {
    $eic = createEic();
    $reviewer = createReviewer();
    $article = Article::factory()->inReview()->create();

    Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::Declined,
    ]);

    $this->actingAs($eic)
        ->post(route('editorial.assign-reviewer', $article), [
            'reviewer_id' => $reviewer->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
});

test('cannot assign reviewer without review-article permission', function () {
    $eic = createEic();
    $user = User::factory()->create();
    $article = Article::factory()->submitted()->create();

    $this->actingAs($eic)
        ->post(route('editorial.assign-reviewer', $article), [
            'reviewer_id' => $user->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('cannot assign reviewer to accepted article', function () {
    $eic = createEic();
    $reviewer = createReviewer();
    $article = Article::factory()->accepted()->create();

    $this->actingAs($eic)
        ->post(route('editorial.assign-reviewer', $article), [
            'reviewer_id' => $reviewer->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

// --- Decide ---

test('editor can accept article with completed review', function () {
    $eic = createEic();
    $article = Article::factory()->inReview()->create();
    Review::factory()->completed()->create(['article_id' => $article->id]);

    $this->actingAs($eic)
        ->post(route('editorial.decide', $article), [
            'decision' => 'accept',
            'decision_comments' => 'Отличная работа.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $article->refresh();
    expect($article)
        ->status->toBe(ArticleStatus::Accepted)
        ->decision->toBe('accept')
        ->decided_by->toBe($eic->id);
});

test('editor can send article for revision', function () {
    $eic = createEic();
    $article = Article::factory()->inReview()->create();
    Review::factory()->completed()->create(['article_id' => $article->id]);

    $this->actingAs($eic)
        ->post(route('editorial.decide', $article), [
            'decision' => 'revision',
            'decision_comments' => 'Нужна доработка.',
        ])
        ->assertRedirect();

    expect($article->refresh()->status)->toBe(ArticleStatus::Revision);
});

test('editor can reject article', function () {
    $eic = createEic();
    $article = Article::factory()->inReview()->create();
    Review::factory()->completed()->create(['article_id' => $article->id]);

    $this->actingAs($eic)
        ->post(route('editorial.decide', $article), [
            'decision' => 'reject',
            'decision_comments' => 'Не соответствует требованиям.',
        ])
        ->assertRedirect();

    expect($article->refresh()->status)->toBe(ArticleStatus::Rejected);
});

test('cannot decide without completed review', function () {
    $eic = createEic();
    $article = Article::factory()->inReview()->create();
    Review::factory()->create(['article_id' => $article->id, 'status' => ReviewStatus::Pending]);

    $this->actingAs($eic)
        ->post(route('editorial.decide', $article), [
            'decision' => 'accept',
            'decision_comments' => 'OK',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('cannot decide on non-in_review article', function () {
    $eic = createEic();
    $article = Article::factory()->submitted()->create();
    Review::factory()->completed()->create(['article_id' => $article->id]);

    $this->actingAs($eic)
        ->post(route('editorial.decide', $article), [
            'decision' => 'accept',
            'decision_comments' => 'OK',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('section editor cannot decide on unassigned article', function () {
    $editor = createSectionEditor();
    $article = Article::factory()->inReview()->create();
    Review::factory()->completed()->create(['article_id' => $article->id]);

    $this->actingAs($editor)
        ->post(route('editorial.decide', $article), [
            'decision' => 'accept',
            'decision_comments' => 'OK',
        ])
        ->assertForbidden();
});

test('section editor can decide on assigned article', function () {
    $editor = createSectionEditor();
    $article = Article::factory()->inReview()->create(['editor_id' => $editor->id]);
    Review::factory()->completed()->create(['article_id' => $article->id]);

    $this->actingAs($editor)
        ->post(route('editorial.decide', $article), [
            'decision' => 'accept',
            'decision_comments' => 'OK',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($article->refresh()->status)->toBe(ArticleStatus::Accepted);
});

// --- Send to Copyediting ---

test('editor can send accepted article to copyediting', function () {
    $eic = createEic();
    $article = Article::factory()->accepted()->create();

    $this->actingAs($eic)
        ->post(route('editorial.send-to-copyediting', $article))
        ->assertRedirect()
        ->assertSessionHas('success');

    $article->refresh();
    expect($article)
        ->status->toBe(ArticleStatus::Copyediting)
        ->copyedited_at->not->toBeNull()
        ->copyedited_by->toBe($eic->id);
});

test('cannot send non-accepted article to copyediting', function () {
    $eic = createEic();
    $article = Article::factory()->inReview()->create();

    $this->actingAs($eic)
        ->post(route('editorial.send-to-copyediting', $article))
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('section editor cannot send unassigned accepted article to copyediting', function () {
    $editor = createSectionEditor();
    $article = Article::factory()->accepted()->create();

    $this->actingAs($editor)
        ->post(route('editorial.send-to-copyediting', $article))
        ->assertForbidden();
});

test('section editor can send assigned accepted article to copyediting', function () {
    $editor = createSectionEditor();
    $article = Article::factory()->accepted()->create(['editor_id' => $editor->id]);

    $this->actingAs($editor)
        ->post(route('editorial.send-to-copyediting', $article))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($article->refresh()->status)->toBe(ArticleStatus::Copyediting);
});

// --- Send to Production ---

test('editor can send copyediting article to production', function () {
    $eic = createEic();
    $article = Article::factory()->copyediting()->withCopyeditedFile()->create();

    $this->actingAs($eic)
        ->post(route('editorial.send-to-production', $article))
        ->assertRedirect()
        ->assertSessionHas('success');

    $article->refresh();
    expect($article)
        ->status->toBe(ArticleStatus::Production)
        ->production_at->not->toBeNull()
        ->production_by->toBe($eic->id);
});

test('cannot send non-copyediting article to production', function () {
    $eic = createEic();
    $article = Article::factory()->accepted()->create();

    $this->actingAs($eic)
        ->post(route('editorial.send-to-production', $article))
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('section editor cannot send unassigned copyediting article to production', function () {
    $editor = createSectionEditor();
    $article = Article::factory()->copyediting()->create();

    $this->actingAs($editor)
        ->post(route('editorial.send-to-production', $article))
        ->assertForbidden();
});

test('section editor can send assigned copyediting article to production', function () {
    $editor = createSectionEditor();
    $article = Article::factory()->copyediting()->withCopyeditedFile()->create(['editor_id' => $editor->id]);

    $this->actingAs($editor)
        ->post(route('editorial.send-to-production', $article))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($article->refresh()->status)->toBe(ArticleStatus::Production);
});

// --- Copyedited File ---

test('editor can upload copyedited file during copyediting stage', function () {
    $eic = createEic();
    $article = Article::factory()->copyediting()->create();

    $this->actingAs($eic)
        ->post(route('editorial.upload-copyedited-file', $article), [
            'copyedited_file' => createPdfFile(),
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $article->refresh();
    expect($article->copyedited_file_path)->not->toBeNull();
    expect($article->copyedited_file_uploaded_at)->not->toBeNull();
    expect($article->copyedited_file_uploaded_by)->toBe($eic->id);
});

test('cannot send to production without copyedited file', function () {
    $eic = createEic();
    $article = Article::factory()->copyediting()->create();

    $this->actingAs($eic)
        ->post(route('editorial.send-to-production', $article))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($article->refresh()->status)->toBe(ArticleStatus::Copyediting);
});

test('uploading copyedited file replaces previous version', function () {
    Storage::fake('local');
    $eic = createEic();
    $article = Article::factory()->copyediting()->create();

    $firstFile = createPdfFile();
    $this->actingAs($eic)
        ->post(route('editorial.upload-copyedited-file', $article), [
            'copyedited_file' => $firstFile,
        ]);

    $firstPath = $article->refresh()->copyedited_file_path;

    $this->actingAs($eic)
        ->post(route('editorial.upload-copyedited-file', $article), [
            'copyedited_file' => createPdfFile(),
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $article->refresh();
    expect($article->copyedited_file_path)->not->toBe($firstPath);
});

test('editor can download copyedited file', function () {
    Storage::fake('local');
    $eic = createEic();
    $article = Article::factory()->copyediting()->create();

    $this->actingAs($eic)
        ->post(route('editorial.upload-copyedited-file', $article), [
            'copyedited_file' => createPdfFile(),
        ]);

    $this->actingAs($eic)
        ->get(route('editorial.download-copyedited-file', $article))
        ->assertOk();
});

test('author can download own copyedited file', function () {
    Storage::fake('local');
    $eic = createEic();
    $author = createAuthorUser();
    $article = Article::factory()->copyediting()->create(['submitted_by' => $author->id]);

    $this->actingAs($eic)
        ->post(route('editorial.upload-copyedited-file', $article), [
            'copyedited_file' => createPdfFile(),
        ]);

    $this->actingAs($author)
        ->get(route('editorial.download-copyedited-file', $article))
        ->assertOk();
});

test('author cannot upload copyedited file', function () {
    $author = createAuthorUser();
    $article = Article::factory()->copyediting()->create(['submitted_by' => $author->id]);

    $this->actingAs($author)
        ->post(route('editorial.upload-copyedited-file', $article), [
            'copyedited_file' => createPdfFile(),
        ])
        ->assertForbidden();
});

test('editor can delete copyedited file', function () {
    Storage::fake('local');
    $eic = createEic();
    $article = Article::factory()->copyediting()->create();

    $this->actingAs($eic)
        ->post(route('editorial.upload-copyedited-file', $article), [
            'copyedited_file' => createPdfFile(),
        ]);

    expect($article->refresh()->copyedited_file_path)->not->toBeNull();

    $this->actingAs($eic)
        ->delete(route('editorial.delete-copyedited-file', $article))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($article->refresh()->copyedited_file_path)->toBeNull();
});

test('cannot upload copyedited file for non-copyediting article', function () {
    Storage::fake('local');
    $eic = createEic();
    $article = Article::factory()->accepted()->create();

    $this->actingAs($eic)
        ->post(route('editorial.upload-copyedited-file', $article), [
            'copyedited_file' => createPdfFile(),
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('cannot delete copyedited file for non-copyediting article', function () {
    $eic = createEic();
    $article = Article::factory()->production()->withCopyeditedFile()->create();

    $this->actingAs($eic)
        ->delete(route('editorial.delete-copyedited-file', $article))
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('copyedited file download returns 404 when not uploaded', function () {
    $eic = createEic();
    $article = Article::factory()->copyediting()->create();

    $this->actingAs($eic)
        ->get(route('editorial.download-copyedited-file', $article))
        ->assertNotFound();
});

// --- Publish ---

test('editor-in-chief can publish production article', function () {
    $eic = createEic();
    $issue = Issue::factory()->create();
    $article = Article::factory()->approved()->create();

    $this->actingAs($eic)
        ->post(route('editorial.publish', $article), [
            'issue_id' => $issue->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $article->refresh();
    expect($article)
        ->status->toBe(ArticleStatus::Published)
        ->issue_id->toBe($issue->id)
        ->published_at->not->toBeNull();
});

test('cannot publish accepted article directly', function () {
    $eic = createEic();
    $issue = Issue::factory()->create();
    $article = Article::factory()->accepted()->create();

    $this->actingAs($eic)
        ->post(route('editorial.publish', $article), [
            'issue_id' => $issue->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('cannot publish non-production article', function () {
    $eic = createEic();
    $issue = Issue::factory()->create();
    $article = Article::factory()->inReview()->create();

    $this->actingAs($eic)
        ->post(route('editorial.publish', $article), [
            'issue_id' => $issue->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('user without publish-issue permission cannot publish', function () {
    $editor = createSectionEditor();
    $issue = Issue::factory()->create();
    $article = Article::factory()->approved()->create(['editor_id' => $editor->id]);

    $this->actingAs($editor)
        ->post(route('editorial.publish', $article), [
            'issue_id' => $issue->id,
        ])
        ->assertForbidden();
});
