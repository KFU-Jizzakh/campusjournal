<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\GalleyRevision;
use App\Models\Issue;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');

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

    $this->createAuthor = function () {
        $user = User::factory()->create();
        $user->assignRole('author');

        return $user;
    };
});

function createGalleyPdf(): UploadedFile
{
    return UploadedFile::fake()->create('galley.pdf', 1024, 'application/pdf');
}

// ==========================================
// AC-1: Editor uploads galley PDF
// ==========================================

test('editor can upload galley PDF to production article', function () {
    $eic = ($this->createEic)();
    $article = Article::factory()->production()->create();

    $this->actingAs($eic)
        ->post(route('editorial.upload-galley-pdf', $article), [
            'galley_pdf' => createGalleyPdf(),
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $article->refresh();
    expect($article->galley_pdf_path)->not->toBeNull();
    expect($article->galley_uploaded_at)->not->toBeNull();
    expect($article->galley_uploaded_by)->toBe($eic->id);
    Storage::disk('local')->assertExists($article->galley_pdf_path);
});

test('galley upload fails for non-production article', function () {
    $eic = ($this->createEic)();
    $article = Article::factory()->copyediting()->create();

    $this->actingAs($eic)
        ->post(route('editorial.upload-galley-pdf', $article), [
            'galley_pdf' => createGalleyPdf(),
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($article->fresh()->galley_pdf_path)->toBeNull();
});

test('section editor can upload galley PDF to assigned production article', function () {
    $editor = ($this->createSectionEditor)();
    $article = Article::factory()->production()->create(['editor_id' => $editor->id]);

    $this->actingAs($editor)
        ->post(route('editorial.upload-galley-pdf', $article), [
            'galley_pdf' => createGalleyPdf(),
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
});

test('section editor cannot upload galley PDF to unassigned production article', function () {
    $editor = ($this->createSectionEditor)();
    $article = Article::factory()->production()->create();

    $this->actingAs($editor)
        ->post(route('editorial.upload-galley-pdf', $article), [
            'galley_pdf' => createGalleyPdf(),
        ])
        ->assertForbidden();
});

test('re-uploading galley PDF replaces the old file', function () {
    $eic = ($this->createEic)();
    $article = Article::factory()->production()->create();

    $file1 = createGalleyPdf();
    $this->actingAs($eic)
        ->post(route('editorial.upload-galley-pdf', $article), [
            'galley_pdf' => $file1,
        ]);
    $firstPath = $article->fresh()->galley_pdf_path;

    $file2 = UploadedFile::fake()->create('galley_v2.pdf', 2048, 'application/pdf');
    $this->actingAs($eic)
        ->post(route('editorial.upload-galley-pdf', $article), [
            'galley_pdf' => $file2,
        ]);

    $article->refresh();
    expect($article->galley_pdf_path)->not->toBe($firstPath);
    Storage::disk('local')->assertMissing($firstPath);
    Storage::disk('local')->assertExists($article->galley_pdf_path);
});

// ==========================================
// AC-2 + AC-4: Send to author and approve
// ==========================================

test('editor can send galley to author', function () {
    $eic = ($this->createEic)();
    $article = Article::factory()->production()->create([
        'galley_pdf_path' => 'galley_uploads/test.pdf',
        'galley_uploaded_at' => now(),
        'galley_uploaded_by' => $eic->id,
    ]);

    $this->actingAs($eic)
        ->post(route('editorial.send-galley', $article))
        ->assertRedirect()
        ->assertSessionHas('success');

    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::AwaitingApproval);
    expect($article->galley_sent_at)->not->toBeNull();
    expect($article->galley_sent_by)->toBe($eic->id);
});

test('cannot send galley without PDF uploaded', function () {
    $eic = ($this->createEic)();
    $article = Article::factory()->production()->create();

    $this->actingAs($eic)
        ->post(route('editorial.send-galley', $article))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($article->fresh()->status)->toBe(ArticleStatus::Production);
});

test('cannot send galley from non-production status', function () {
    $eic = ($this->createEic)();
    $article = Article::factory()->copyediting()->create();

    $this->actingAs($eic)
        ->post(route('editorial.send-galley', $article))
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('author can approve galley', function () {
    $author = ($this->createAuthor)();
    $eic = ($this->createEic)();
    $article = Article::factory()->awaitingApproval()->create([
        'submitted_by' => $author->id,
        'galley_sent_by' => $eic->id,
    ]);

    $this->actingAs($author)
        ->post(route('submissions.approve-galley', $article))
        ->assertRedirect()
        ->assertSessionHas('success');

    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Approved);
    expect($article->galley_approved_at)->not->toBeNull();
    expect($article->galley_approved_by)->toBe($author->id);
});

test('cannot approve from wrong status', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->production()->create(['submitted_by' => $author->id]);

    $this->actingAs($author)
        ->post(route('submissions.approve-galley', $article))
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('non-author cannot approve galley', function () {
    $author = ($this->createAuthor)();
    $other = User::factory()->create();
    $other->assignRole('author');
    $eic = ($this->createEic)();
    $article = Article::factory()->awaitingApproval()->create([
        'submitted_by' => $author->id,
        'galley_sent_by' => $eic->id,
    ]);

    $this->actingAs($other)
        ->post(route('submissions.approve-galley', $article))
        ->assertForbidden();
});

// ==========================================
// AC-5 + BR-2: Revision requests (unlimited cycles)
// ==========================================

test('author can request galley revision with comment', function () {
    $author = ($this->createAuthor)();
    $eic = ($this->createEic)();
    $article = Article::factory()->awaitingApproval()->create([
        'submitted_by' => $author->id,
        'galley_sent_by' => $eic->id,
    ]);

    $this->actingAs($author)
        ->post(route('submissions.request-revision', $article), [
            'comment' => 'Please fix figure 3 alignment.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Production);
    expect($article->galley_sent_at)->toBeNull();
    expect($article->galley_sent_by)->toBeNull();

    $revision = GalleyRevision::where('article_id', $article->id)->first();
    expect($revision)->not->toBeNull();
    expect($revision->requested_by)->toBe($author->id);
    expect($revision->comment)->toBe('Please fix figure 3 alignment.');
});

test('cannot request revision from wrong status', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->production()->create(['submitted_by' => $author->id]);

    $this->actingAs($author)
        ->post(route('submissions.request-revision', $article), [
            'comment' => 'Fix it',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('non-author cannot request revision', function () {
    $author = ($this->createAuthor)();
    $other = User::factory()->create();
    $other->assignRole('author');
    $eic = ($this->createEic)();
    $article = Article::factory()->awaitingApproval()->create([
        'submitted_by' => $author->id,
        'galley_sent_by' => $eic->id,
    ]);

    $this->actingAs($other)
        ->post(route('submissions.request-revision', $article), [
            'comment' => 'Fix it',
        ])
        ->assertForbidden();
});

test('multiple revision request cycles work', function () {
    $author = ($this->createAuthor)();
    $eic = ($this->createEic)();
    $article = Article::factory()->production()->create([
        'submitted_by' => $author->id,
        'editor_id' => $eic->id,
    ]);

    // Cycle 1: send, request revision
    $article->update(['galley_pdf_path' => 'galley_uploads/test.pdf', 'galley_uploaded_by' => $eic->id]);
    $article->sendGalleyToAuthor($eic);
    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::AwaitingApproval);

    $article->requestGalleyRevision($author, 'Fix figure 1');
    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Production);

    // Cycle 2: re-send, request revision again
    $article->sendGalleyToAuthor($eic);
    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::AwaitingApproval);

    $article->requestGalleyRevision($author, 'Fix figure 2');
    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Production);

    // Cycle 3: re-send, approve
    $article->sendGalleyToAuthor($eic);
    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::AwaitingApproval);

    $article->approveGalley($author);
    expect($article->fresh()->status)->toBe(ArticleStatus::Approved);
});

test('each revision cycle creates a new GalleyRevision record', function () {
    $author = ($this->createAuthor)();
    $eic = ($this->createEic)();
    $article = Article::factory()->production()->create([
        'submitted_by' => $author->id,
        'editor_id' => $eic->id,
        'galley_pdf_path' => 'galley_uploads/test.pdf',
        'galley_uploaded_by' => $eic->id,
    ]);

    $article->sendGalleyToAuthor($eic);
    $article->refresh();
    $article->requestGalleyRevision($author, 'Comment 1');
    $article->refresh();
    $article->sendGalleyToAuthor($eic);
    $article->refresh();
    $article->requestGalleyRevision($author, 'Comment 2');
    $article->refresh();
    $article->sendGalleyToAuthor($eic);
    $article->refresh();
    $article->requestGalleyRevision($author, 'Comment 3');

    expect(GalleyRevision::where('article_id', $article->id)->count())->toBe(3);
});

test('after revision request, article returns to Production and editor can re-send', function () {
    $author = ($this->createAuthor)();
    $eic = ($this->createEic)();
    $article = Article::factory()->production()->create([
        'submitted_by' => $author->id,
        'editor_id' => $eic->id,
        'galley_pdf_path' => 'galley_uploads/test.pdf',
        'galley_uploaded_by' => $eic->id,
    ]);

    $article->sendGalleyToAuthor($eic);
    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::AwaitingApproval);

    $article->requestGalleyRevision($author, 'Fix it');
    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Production);

    $article->sendGalleyToAuthor($eic);
    expect($article->fresh()->status)->toBe(ArticleStatus::AwaitingApproval);
});

// ==========================================
// AC-6 + BR-1: Publication blocked until approval
// ==========================================

test('editor can publish article with approved galleys', function () {
    $eic = ($this->createEic)();
    $issue = Issue::factory()->create();
    $article = Article::factory()->approved()->create();

    $this->actingAs($eic)
        ->post(route('editorial.publish', $article), [
            'issue_id' => $issue->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Published);
});

test('cannot publish from Production without galley approval', function () {
    $eic = ($this->createEic)();
    $issue = Issue::factory()->create();
    $article = Article::factory()->production()->create();

    $this->actingAs($eic)
        ->post(route('editorial.publish', $article), [
            'issue_id' => $issue->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('cannot publish from AwaitingApproval', function () {
    $eic = ($this->createEic)();
    $issue = Issue::factory()->create();
    $article = Article::factory()->awaitingApproval()->create();

    $this->actingAs($eic)
        ->post(route('editorial.publish', $article), [
            'issue_id' => $issue->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

// ==========================================
// Galley PDF download
// ==========================================

test('author can download galley PDF', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->awaitingApproval()->create([
        'submitted_by' => $author->id,
    ]);
    Storage::disk('local')->put($article->galley_pdf_path, 'fake pdf content');

    $this->actingAs($author)
        ->get(route('articles.galley-pdf', $article))
        ->assertOk();
});

test('random user cannot download galley PDF', function () {
    $user = User::factory()->create();
    $article = Article::factory()->awaitingApproval()->create();
    Storage::disk('local')->put($article->galley_pdf_path, 'fake pdf content');

    $this->actingAs($user)
        ->get(route('articles.galley-pdf', $article))
        ->assertNotFound();
});
