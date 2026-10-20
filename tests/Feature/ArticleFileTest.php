<?php

use App\Enums\ArticleFileLicense;
use App\Enums\ArticleFileType;
use App\Enums\ArticleFileVisibility;
use App\Enums\ArticleStatus;
use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Models\Review;
use App\Models\User;
use App\Policies\ArticleFilePolicy;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('public');
    Storage::fake('local');

    // Helper functions as closures
    $this->createAuthor = function () {
        $user = User::factory()->create();
        $user->assignRole('author');

        return $user;
    };

    $this->createEditorInChief = function () {
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

    $this->createManagingEditor = function () {
        $user = User::factory()->create();
        $user->assignRole('managing-editor');

        return $user;
    };

    // Real temp-file upload: testing fakes report the MIME derived from
    // the file NAME, but production validation sniffs the CONTENT via
    // finfo. This helper builds a Symfony UploadedFile over an actual
    // temp file so mimetypes rules are exercised against real sniffing.
    $this->makeContentUpload = function (string $name, string $content): SymfonyUploadedFile {
        $path = tempnam(sys_get_temp_dir(), 'cj-upload');
        file_put_contents($path, $content);
        register_shutdown_function(fn () => @unlink($path));

        return new SymfonyUploadedFile($path, $name, test: true);
    };
});

// ==========================================
// Upload Tests (store)
// ==========================================

test('author can upload file to own draft article', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
    ]);

    $response = $this->actingAs($author)
        ->post(route('article-files.store', $article), [
            'file' => UploadedFile::fake()->create('document.pdf', 512, 'application/pdf'),
            'file_type' => ArticleFileType::Document->value,
            'visibility' => ArticleFileVisibility::EditorialOnly->value,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($article->fresh()->files)->toHaveCount(1);
    $file = $article->files->first();
    Storage::disk($file->disk)->assertExists($file->file_path);
});

test('author can upload file to own revision article', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Revision,
    ]);

    $response = $this->actingAs($author)
        ->post(route('article-files.store', $article), [
            'file' => UploadedFile::fake()->create('data.csv', 256, 'text/csv'),
            'file_type' => ArticleFileType::ResearchData->value,
            'visibility' => ArticleFileVisibility::ReviewersOnly->value,
        ]);

    $response->assertRedirect();
    expect($article->fresh()->files)->toHaveCount(1);
});

test('author cannot upload file to submitted article', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->submitted()->create([
        'submitted_by' => $author->id,
    ]);

    $response = $this->actingAs($author)
        ->post(route('article-files.store', $article), [
            'file' => UploadedFile::fake()->create('document.pdf', 512, 'application/pdf'),
            'file_type' => ArticleFileType::Document->value,
            'visibility' => ArticleFileVisibility::EditorialOnly->value,
        ]);

    $response->assertForbidden();
    expect($article->fresh()->files)->toHaveCount(0);
});

test('author cannot upload file to other authors article', function () {
    $author1 = ($this->createAuthor)();
    $author2 = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author2->id,
        'status' => ArticleStatus::Draft,
    ]);

    $response = $this->actingAs($author1)
        ->post(route('article-files.store', $article), [
            'file' => UploadedFile::fake()->create('document.pdf', 512, 'application/pdf'),
            'file_type' => ArticleFileType::Document->value,
            'visibility' => ArticleFileVisibility::EditorialOnly->value,
        ]);

    $response->assertForbidden();
});

test('editor can upload file to any article', function () {
    $editor = ($this->createEditorInChief)();
    $article = Article::factory()->submitted()->create();

    $response = $this->actingAs($editor)
        ->post(route('article-files.store', $article), [
            'file' => UploadedFile::fake()->create('document.pdf', 512, 'application/pdf'),
            'file_type' => ArticleFileType::Document->value,
            'visibility' => ArticleFileVisibility::EditorialOnly->value,
        ]);

    $response->assertRedirect();
    expect($article->fresh()->files)->toHaveCount(1);
});

test('guest cannot upload file', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Draft]);

    $response = $this->post(route('article-files.store', $article), [
        'file' => UploadedFile::fake()->create('document.pdf', 512, 'application/pdf'),
        'file_type' => ArticleFileType::Document->value,
        'visibility' => ArticleFileVisibility::EditorialOnly->value,
    ]);

    $response->assertRedirect(route('login'));
});

test('upload validates file type mime', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
    ]);

    $response = $this->actingAs($author)
        ->post(route('article-files.store', $article), [
            'file' => UploadedFile::fake()->create('document.exe', 512, 'application/x-msdownload'),
            'file_type' => ArticleFileType::Document->value,
            'visibility' => ArticleFileVisibility::EditorialOnly->value,
        ]);

    $response->assertSessionHasErrors('file');
});

test('author cannot upload html payload as other file type', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
    ]);

    // Real HTML content under a .csv name: the finfo sniffer reports
    // text/html, which is outside the "other" allowlist.
    $response = $this->actingAs($author)
        ->post(route('article-files.store', $article), [
            'file' => ($this->makeContentUpload)('payload.csv', '<html><script>alert(1)</script></html>'),
            'file_type' => ArticleFileType::Other->value,
            'visibility' => ArticleFileVisibility::Public->value,
        ]);

    $response->assertSessionHasErrors('file');
    expect($article->fresh()->files)->toHaveCount(0);
});

test('stored file extension is derived from content mime, never from the client filename', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
    ]);

    // Content mime text/plain is inside the "other" allowlist; the .html
    // client filename must not leak into the stored path — hashName()
    // derives the extension from the guessed mime, so the file lands on
    // disk as .txt and the public disk can never serve it as text/html.
    $response = $this->actingAs($author)
        ->post(route('article-files.store', $article), [
            'file' => UploadedFile::fake()->create('payload.html', 64, 'text/plain'),
            'file_type' => ArticleFileType::Other->value,
            'visibility' => ArticleFileVisibility::Public->value,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $file = $article->fresh()->files->first();
    expect(pathinfo($file->file_path, PATHINFO_EXTENSION))->toBe('txt');
    Storage::disk($file->disk)->assertExists($file->file_path);
});

test('author cannot upload file whose content mime is outside the allowlist', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
    ]);

    // .pdf extension is allowed for "other", but the finfo-sniffed
    // content mime (text/html) is not — the content-based mimetypes
    // rule must reject it.
    $response = $this->actingAs($author)
        ->post(route('article-files.store', $article), [
            'file' => ($this->makeContentUpload)('payload.pdf', '<script>alert(1)</script>'),
            'file_type' => ArticleFileType::Other->value,
            'visibility' => ArticleFileVisibility::Public->value,
        ]);

    $response->assertSessionHasErrors('file');
    expect($article->fresh()->files)->toHaveCount(0);
});

test('upload with array file_type input yields validation error instead of crashing', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
    ]);

    // file_type[]=x must not reach the "(string)" cast path — the
    // enum lookup is skipped and Rule::enum rejects the array as 422.
    $response = $this->actingAs($author)
        ->post(route('article-files.store', $article), [
            'file' => UploadedFile::fake()->create('document.pdf', 64, 'application/pdf'),
            'file_type' => [ArticleFileType::Other->value],
            'visibility' => ArticleFileVisibility::EditorialOnly->value,
        ]);

    $response->assertSessionHasErrors('file_type');
    expect($article->fresh()->files)->toHaveCount(0);
});

test('author can upload legitimate archive or presentation as other file type', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
    ]);

    $response = $this->actingAs($author)
        ->post(route('article-files.store', $article), [
            'file' => UploadedFile::fake()->create(
                'slides.pptx',
                64,
                'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            ),
            'file_type' => ArticleFileType::Other->value,
            'visibility' => ArticleFileVisibility::Public->value,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($article->fresh()->files)->toHaveCount(1);
    $file = $article->files->first();
    expect($file->file_type)->toBe(ArticleFileType::Other);
    Storage::disk($file->disk)->assertExists($file->file_path);
});

test('upload creates thumbnail for images', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
    ]);

    $response = $this->actingAs($author)
        ->post(route('article-files.store', $article), [
            'file' => UploadedFile::fake()->image('photo.jpg', 800, 600),
            'file_type' => ArticleFileType::Image->value,
            'visibility' => ArticleFileVisibility::Public->value,
        ]);

    $response->assertRedirect();
    $file = $article->fresh()->files->first();

    Storage::disk('public')->assertExists($file->file_path);
    Storage::disk('public')->assertExists($file->getThumbnailPath());
});

test('upload warns when thumbnail creation fails for corrupted image', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
    ]);

    $response = $this->actingAs($author)
        ->post(route('article-files.store', $article), [
            'file' => UploadedFile::fake()->create('corrupt.jpg', 10, 'image/jpeg'),
            'file_type' => ArticleFileType::Image->value,
            'visibility' => ArticleFileVisibility::Public->value,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $response->assertSessionHas('warning');

    $file = $article->fresh()->files->first();
    expect($file)->not->toBeNull();
    Storage::disk('public')->assertExists($file->file_path);
    Storage::disk('public')->assertMissing($file->getThumbnailPath());
});

// ==========================================
// Download Tests
// ==========================================

test('guest can download public file from published article', function () {
    $article = Article::factory()->published()->create();
    $file = ArticleFile::factory()
        ->image($article)
        ->withVisibility(ArticleFileVisibility::Public)
        ->create();

    $response = $this->get(route('article-files.download', $file));

    $response->assertOk();
    $response->assertHeader('content-disposition', 'attachment; filename='.$file->original_name);
});

test('guest cannot download public file from unpublished article', function () {
    $article = Article::factory()->submitted()->create();
    $file = ArticleFile::factory()
        ->image($article)
        ->withVisibility(ArticleFileVisibility::Public)
        ->create();

    $response = $this->get(route('article-files.download', $file));

    $response->assertNotFound();
});

test('guest cannot download editorial_only file', function () {
    $article = Article::factory()->published()->create();
    $file = ArticleFile::factory()
        ->document($article)
        ->withVisibility(ArticleFileVisibility::EditorialOnly)
        ->create();

    $response = $this->get(route('article-files.download', $file));

    $response->assertNotFound();
});

test('guest cannot download reviewers_only file', function () {
    $article = Article::factory()->published()->create();
    $file = ArticleFile::factory()
        ->document($article)
        ->withVisibility(ArticleFileVisibility::ReviewersOnly)
        ->create();

    $response = $this->get(route('article-files.download', $file));

    $response->assertNotFound();
});

test('guest cannot download public file from soft-deleted article', function () {
    $article = Article::factory()->published()->create();
    $file = ArticleFile::factory()
        ->document($article)
        ->withVisibility(ArticleFileVisibility::Public)
        ->create();
    $article->delete();

    $this->get(route('article-files.download', $file))
        ->assertNotFound();
});

test('author can download own files of any visibility', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Submitted,
    ]);

    $publicFile = ArticleFile::factory()
        ->image($article, $author)
        ->withVisibility(ArticleFileVisibility::Public)
        ->create();
    $editorialFile = ArticleFile::factory()
        ->document($article, $author)
        ->withVisibility(ArticleFileVisibility::EditorialOnly)
        ->create();
    $reviewerFile = ArticleFile::factory()
        ->researchData($article, $author)
        ->withVisibility(ArticleFileVisibility::ReviewersOnly)
        ->create();

    $this->actingAs($author)
        ->get(route('article-files.download', $publicFile))
        ->assertOk();

    $this->actingAs($author)
        ->get(route('article-files.download', $editorialFile))
        ->assertOk();

    $this->actingAs($author)
        ->get(route('article-files.download', $reviewerFile))
        ->assertOk();
});

test('reviewer can download public and reviewers_only files', function () {
    $reviewer = ($this->createReviewer)();
    $article = Article::factory()->inReview()->create();

    // Assign reviewer to article
    Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::Pending,
    ]);

    $publicFile = ArticleFile::factory()
        ->image($article)
        ->withVisibility(ArticleFileVisibility::Public)
        ->create();
    $reviewerFile = ArticleFile::factory()
        ->document($article)
        ->withVisibility(ArticleFileVisibility::ReviewersOnly)
        ->create();

    $this->actingAs($reviewer)
        ->get(route('article-files.download', $publicFile))
        ->assertOk();

    $this->actingAs($reviewer)
        ->get(route('article-files.download', $reviewerFile))
        ->assertOk();
});

test('reviewer cannot download editorial_only files', function () {
    $reviewer = ($this->createReviewer)();
    $article = Article::factory()->inReview()->create();

    Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::Pending,
    ]);

    $editorialFile = ArticleFile::factory()
        ->document($article)
        ->withVisibility(ArticleFileVisibility::EditorialOnly)
        ->create();

    $this->actingAs($reviewer)
        ->get(route('article-files.download', $editorialFile))
        ->assertNotFound();
});

test('editor can download any file', function () {
    $editor = ($this->createEditorInChief)();
    $article = Article::factory()->published()->create();

    $publicFile = ArticleFile::factory()
        ->image($article)
        ->withVisibility(ArticleFileVisibility::Public)
        ->create();
    $editorialFile = ArticleFile::factory()
        ->document($article)
        ->withVisibility(ArticleFileVisibility::EditorialOnly)
        ->create();
    $reviewerFile = ArticleFile::factory()
        ->researchData($article)
        ->withVisibility(ArticleFileVisibility::ReviewersOnly)
        ->create();

    $this->actingAs($editor)
        ->get(route('article-files.download', $publicFile))
        ->assertOk();

    $this->actingAs($editor)
        ->get(route('article-files.download', $editorialFile))
        ->assertOk();

    $this->actingAs($editor)
        ->get(route('article-files.download', $reviewerFile))
        ->assertOk();
});

// ==========================================
// Delete Tests (destroy)
// ==========================================

test('author can delete own file from draft article', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
    ]);
    $file = ArticleFile::factory()->document($article, $author)->create();

    Storage::disk('public')->assertExists($file->file_path);

    $response = $this->actingAs($author)
        ->delete(route('article-files.destroy', $file));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect(ArticleFile::find($file->id))->toBeNull();
});

test('author can delete own file from revision article', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Revision,
    ]);
    $file = ArticleFile::factory()->document($article, $author)->create();

    $response = $this->actingAs($author)
        ->delete(route('article-files.destroy', $file));

    $response->assertRedirect();
    expect(ArticleFile::find($file->id))->toBeNull();
});

test('author cannot delete file from submitted article', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->submitted()->create([
        'submitted_by' => $author->id,
    ]);
    $file = ArticleFile::factory()->document($article, $author)->create();

    $response = $this->actingAs($author)
        ->delete(route('article-files.destroy', $file));

    $response->assertForbidden();
    expect(ArticleFile::find($file->id))->not->toBeNull();
});

test('author cannot delete file from other authors article', function () {
    $author1 = ($this->createAuthor)();
    $author2 = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author2->id,
        'status' => ArticleStatus::Draft,
    ]);
    $file = ArticleFile::factory()->document($article, $author2)->create();

    $response = $this->actingAs($author1)
        ->delete(route('article-files.destroy', $file));

    $response->assertForbidden();
});

test('editor can delete any file', function () {
    $editor = ($this->createEditorInChief)();
    $author = ($this->createAuthor)();
    $article = Article::factory()->published()->create([
        'submitted_by' => $author->id,
    ]);
    $file = ArticleFile::factory()->document($article, $author)->create();

    $response = $this->actingAs($editor)
        ->delete(route('article-files.destroy', $file));

    $response->assertRedirect();
    expect(ArticleFile::find($file->id))->toBeNull();
});

test('deleting file soft deletes record and removes file from storage', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
    ]);
    $file = ArticleFile::factory()->document($article, $author)->create();

    Storage::disk('public')->assertExists($file->file_path);

    $this->actingAs($author)
        ->delete(route('article-files.destroy', $file));

    // File record is soft deleted (not visible in default queries)
    expect(ArticleFile::find($file->id))->toBeNull();
    // But exists in trashed queries
    expect(ArticleFile::withTrashed()->find($file->id))->not->toBeNull();
    expect(ArticleFile::withTrashed()->find($file->id)->deleted_at)->not->toBeNull();

    // File is removed from storage
    Storage::disk('public')->assertMissing($file->file_path);
});

test('deleting image soft deletes record and removes file and thumbnail from storage', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
    ]);
    $file = ArticleFile::factory()->image($article, $author)->create();

    Storage::disk('public')->assertExists($file->file_path);
    Storage::disk('public')->assertExists($file->getThumbnailPath());

    $this->actingAs($author)
        ->delete(route('article-files.destroy', $file));

    // File record is soft deleted (not visible in default queries)
    expect(ArticleFile::find($file->id))->toBeNull();
    // But exists in trashed queries
    expect(ArticleFile::withTrashed()->find($file->id))->not->toBeNull();
    expect(ArticleFile::withTrashed()->find($file->id)->deleted_at)->not->toBeNull();

    // Files are removed from storage
    Storage::disk('public')->assertMissing($file->file_path);
    Storage::disk('public')->assertMissing($file->getThumbnailPath());
});

// ==========================================
// Policy Tests
// ==========================================

test('policy allows guest to view public file from published article', function () {
    $article = Article::factory()->published()->create();
    $file = ArticleFile::factory()
        ->image($article)
        ->withVisibility(ArticleFileVisibility::Public)
        ->create();

    $policy = new ArticleFilePolicy;

    expect($policy->view(null, $file))->toBeTrue();
    expect($policy->download(null, $file))->toBeTrue();
});

test('policy denies guest to view public file from unpublished article', function () {
    $article = Article::factory()->submitted()->create();
    $file = ArticleFile::factory()
        ->image($article)
        ->withVisibility(ArticleFileVisibility::Public)
        ->create();

    $policy = new ArticleFilePolicy;

    expect($policy->view(null, $file))->toBeFalse();
    expect($policy->download(null, $file))->toBeFalse();
});

test('policy denies guest to view public file from soft-deleted article', function () {
    $article = Article::factory()->published()->create();
    $file = ArticleFile::factory()
        ->image($article)
        ->withVisibility(ArticleFileVisibility::Public)
        ->create();
    $article->delete();

    $policy = new ArticleFilePolicy;

    expect($policy->view(null, $file))->toBeFalse();
    expect($policy->download(null, $file))->toBeFalse();
});

test('policy denies guest to view editorial_only file', function () {
    $article = Article::factory()->published()->create();
    $file = ArticleFile::factory()
        ->document($article)
        ->withVisibility(ArticleFileVisibility::EditorialOnly)
        ->create();

    $policy = new ArticleFilePolicy;

    expect($policy->view(null, $file))->toBeFalse();
});

test('policy allows reviewer to view reviewers_only file', function () {
    $reviewer = ($this->createReviewer)();
    $article = Article::factory()->inReview()->create();

    Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::Pending,
    ]);

    $file = ArticleFile::factory()
        ->document($article)
        ->withVisibility(ArticleFileVisibility::ReviewersOnly)
        ->create();

    $policy = new ArticleFilePolicy;

    expect($policy->view($reviewer, $file))->toBeTrue();
});

test('policy denies non-reviewer to view reviewers_only file', function () {
    $user = ($this->createAuthor)();
    $article = Article::factory()->inReview()->create();

    $file = ArticleFile::factory()
        ->document($article)
        ->withVisibility(ArticleFileVisibility::ReviewersOnly)
        ->create();

    $policy = new ArticleFilePolicy;

    expect($policy->view($user, $file))->toBeFalse();
});

test('policy allows editor to delete any file', function () {
    $editor = ($this->createEditorInChief)();
    $author = ($this->createAuthor)();
    $article = Article::factory()->published()->create([
        'submitted_by' => $author->id,
    ]);
    $file = ArticleFile::factory()->document($article, $author)->create();

    $policy = new ArticleFilePolicy;

    expect($policy->delete($editor, $file))->toBeTrue();
});

// ==========================================
// Model Tests
// ==========================================

test('thumbnail_url returns url for images', function () {
    Storage::fake('public');

    $article = Article::factory()->create();
    $file = ArticleFile::factory()->image($article)->create();

    // Create the thumbnail file manually since factory creates it
    Storage::disk('public')->put($file->getThumbnailPath(), 'thumbnail content');

    expect($file->thumbnail_url)->not->toBeNull();
    expect($file->thumbnail_url)->toContain('thumbnails');
});

test('thumbnail_url returns null for non-images', function () {
    $article = Article::factory()->create();
    $file = ArticleFile::factory()->document($article)->create();

    expect($file->thumbnail_url)->toBeNull();
});

test('isImage returns true for image type', function () {
    $article = Article::factory()->create();
    $imageFile = ArticleFile::factory()->image($article)->create();
    $documentFile = ArticleFile::factory()->document($article)->create();

    expect($imageFile->isImage())->toBeTrue();
    expect($documentFile->isImage())->toBeFalse();
});

test('deleteFile removes file and thumbnail', function () {
    Storage::fake('public');

    $article = Article::factory()->create();
    $file = ArticleFile::factory()->image($article)->create();

    // Manually create files since storage is faked
    Storage::disk('public')->put($file->file_path, 'image content');
    Storage::disk('public')->put($file->getThumbnailPath(), 'thumbnail content');

    Storage::disk('public')->assertExists($file->file_path);
    Storage::disk('public')->assertExists($file->getThumbnailPath());

    $file->deleteFile();

    Storage::disk('public')->assertMissing($file->file_path);
    Storage::disk('public')->assertMissing($file->getThumbnailPath());
});

test('formatted_size formats bytes correctly', function () {
    $article = Article::factory()->create();

    $bytesFile = ArticleFile::factory()->document($article)->create([
        'file_size' => 512,
    ]);
    expect($bytesFile->formatted_size)->toBe('512 bytes');

    $kbFile = ArticleFile::factory()->document($article)->create([
        'file_size' => 2048,
    ]);
    expect($kbFile->formatted_size)->toBe('2.00 KB');

    $mbFile = ArticleFile::factory()->document($article)->create([
        'file_size' => 2097152,
    ]);
    expect($mbFile->formatted_size)->toBe('2.00 MB');
});

// ==========================================
// Metadata Tests (License & Language)
// ==========================================

test('license is saved during file upload', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
    ]);

    $response = $this->actingAs($author)
        ->post(route('article-files.store', $article), [
            'file' => UploadedFile::fake()->create('document.pdf', 512, 'application/pdf'),
            'file_type' => ArticleFileType::Document->value,
            'visibility' => ArticleFileVisibility::Public->value,
            'license' => ArticleFileLicense::CcBy->value,
        ]);

    $response->assertRedirect();
    $file = $article->fresh()->files->first();

    expect($file->license)->toBe(ArticleFileLicense::CcBy);
});

test('language is saved during file upload', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
    ]);

    $response = $this->actingAs($author)
        ->post(route('article-files.store', $article), [
            'file' => UploadedFile::fake()->create('document.pdf', 512, 'application/pdf'),
            'file_type' => ArticleFileType::Document->value,
            'visibility' => ArticleFileVisibility::Public->value,
            'language' => 'en',
        ]);

    $response->assertRedirect();
    $file = $article->fresh()->files->first();

    expect($file->language)->toBe('en');
});

test('license is displayed on public article page', function () {
    $article = Article::factory()->published()->create();
    $file = ArticleFile::factory()
        ->document($article)
        ->withVisibility(ArticleFileVisibility::Public)
        ->create([
            'license' => ArticleFileLicense::CcBy,
        ]);

    $response = $this->get(route('articles.show', $article));

    $response->assertOk();
    $response->assertSee($file->license->label());
});

test('language is displayed in file list', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
    ]);
    $file = ArticleFile::factory()
        ->document($article, $author)
        ->create([
            'language' => 'en',
        ]);

    $response = $this->actingAs($author)
        ->get(route('submissions.show', $article));

    $response->assertOk();
    $response->assertSee('EN');
});
