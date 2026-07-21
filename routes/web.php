<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\ConferenceController;
use App\Http\Controllers\CopyrightAgreementController;
use App\Http\Controllers\Dashboard\ArticleFileController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\DiscussionController;
use App\Http\Controllers\Dashboard\EditorialController;
use App\Http\Controllers\Dashboard\NotificationController;
use App\Http\Controllers\Dashboard\ReviewController;
use App\Http\Controllers\Dashboard\SubmissionController;
use App\Http\Controllers\EditorialBoardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\OaiPmhController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/issues', [IssueController::class, 'index'])->name('issues.index');
Route::get('/issues/{issue}', [IssueController::class, 'show'])->name('issues.show');
Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/{article}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/articles/{article}/pdf', [ArticleController::class, 'pdf'])->name('articles.pdf');
Route::get('/articles/{article}/export/bibtex', [ArticleController::class, 'exportBibtex'])->name('articles.export.bibtex');
Route::get('/articles/{article}/export/ris', [ArticleController::class, 'exportRis'])->name('articles.export.ris');
Route::get('/articles/{article}/jats.xml', [ArticleController::class, 'exportJats'])->name('articles.export.jats');
Route::get('/articles/{article}/blinded-pdf', [ArticleController::class, 'blindedPdf'])->name('articles.blinded-pdf');
Route::get('/authors/{author}', [AuthorController::class, 'show'])->name('authors.show');
Route::get('/editorial-board', [EditorialBoardController::class, 'index'])->name('editorial-board');
Route::get('/for-authors', [PageController::class, 'forAuthors'])->name('for-authors');
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{news}', [NewsController::class, 'show'])->name('news.show');
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/conferences', [ConferenceController::class, 'index'])->name('conferences.index');
Route::get('/conferences/{conference:slug}', [ConferenceController::class, 'show'])->name('conferences.show');
Route::get('/contacts', [PageController::class, 'contacts'])->name('contacts');
Route::get('/search', [SearchController::class, 'index'])->name('search');

// OAI-PMH endpoint
Route::match(['get', 'post'], '/oai', [OaiPmhController::class, 'handle'])->name('oai');

// Copyright agreement full text (public)
Route::get('/agreements/{agreement}', [CopyrightAgreementController::class, 'show'])->name('agreements.show');

// Sitemap
Route::get('/sitemap.xml', function () {
    $path = public_path('sitemap.xml');

    if (File::exists($path)) {
        return response(File::get($path), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    return response('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>', 200, [
        'Content-Type' => 'application/xml',
    ]);
})->name('sitemap');

// Public file download route
Route::get('/article-files/{file}/download', [ArticleFileController::class, 'download'])->name('article-files.download')->middleware('throttle:30,1');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Galley PDF download (auth only)
    Route::get('/articles/{article}/galley-pdf', [ArticleController::class, 'galleyPdf'])->name('articles.galley-pdf');

    // Article submissions (requires submit-article permission)
    Route::middleware('permission:submit-article')->group(function () {
        Route::get('/dashboard/articles/create', [SubmissionController::class, 'create'])->name('submissions.create');
        Route::post('/dashboard/articles', [SubmissionController::class, 'store'])->name('submissions.store');
        Route::get('/dashboard/articles/{article}', [SubmissionController::class, 'show'])->name('submissions.show');
        Route::get('/dashboard/articles/{article}/edit', [SubmissionController::class, 'edit'])->name('submissions.edit');
        Route::put('/dashboard/articles/{article}', [SubmissionController::class, 'update'])->name('submissions.update');

        // Article file uploads
        Route::post('/dashboard/articles/{article}/files', [ArticleFileController::class, 'store'])->name('article-files.store');
    });

    // Article file management (delete)
    Route::delete('/dashboard/article-files/{file}', [ArticleFileController::class, 'destroy'])->name('article-files.destroy')->middleware('permission:submit-article|manage-submissions');

    // Reviews (requires review-article permission)
    Route::middleware('permission:review-article')->group(function () {
        Route::get('/dashboard/reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::get('/dashboard/reviews/{review}', [ReviewController::class, 'show'])->name('reviews.show');
        Route::put('/dashboard/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
        Route::post('/dashboard/reviews/{review}/accept', [ReviewController::class, 'accept'])->name('reviews.accept');
        Route::post('/dashboard/reviews/{review}/decline', [ReviewController::class, 'decline'])->name('reviews.decline');
    });

    // Editorial workflow (requires manage-submissions permission)
    Route::middleware('permission:manage-submissions')->group(function () {
        Route::get('/dashboard/editorial', [EditorialController::class, 'index'])->name('editorial.index');
        Route::get('/dashboard/editorial/{article}', [EditorialController::class, 'show'])->name('editorial.show');
        Route::post('/dashboard/editorial/{article}/assign-editor', [EditorialController::class, 'assignEditor'])->name('editorial.assign-editor');
        Route::post('/dashboard/editorial/{article}/assign-reviewer', [EditorialController::class, 'assignReviewer'])->name('editorial.assign-reviewer');
        Route::post('/dashboard/editorial/{article}/decide', [EditorialController::class, 'decide'])->name('editorial.decide');
        Route::post('/dashboard/editorial/{article}/send-to-copyediting', [EditorialController::class, 'sendToCopyediting'])->name('editorial.send-to-copyediting');
        Route::post('/dashboard/editorial/{article}/send-to-production', [EditorialController::class, 'sendToProduction'])->name('editorial.send-to-production');
        Route::post('/dashboard/editorial/{article}/publish', [EditorialController::class, 'publish'])->name('editorial.publish');
        Route::put('/dashboard/editorial/{article}/review-type', [EditorialController::class, 'setReviewType'])->name('editorial.set-review-type');
        Route::post('/dashboard/editorial/{article}/blinded-pdf', [EditorialController::class, 'uploadBlindedPdf'])->name('editorial.upload-blinded-pdf');
        Route::delete('/dashboard/editorial/{article}/blinded-pdf', [EditorialController::class, 'deleteBlindedPdf'])->name('editorial.delete-blinded-pdf');
        Route::post('/dashboard/editorial/{article}/galley-pdf', [EditorialController::class, 'uploadGalleyPdf'])->name('editorial.upload-galley-pdf');
        Route::post('/dashboard/editorial/{article}/send-galley', [EditorialController::class, 'sendGalleyToAuthor'])->name('editorial.send-galley');
        Route::post('/dashboard/editorial/{article}/copyedited-file', [EditorialController::class, 'uploadCopyeditedFile'])->name('editorial.upload-copyedited-file');
        Route::delete('/dashboard/editorial/{article}/copyedited-file', [EditorialController::class, 'deleteCopyeditedFile'])->name('editorial.delete-copyedited-file');

        // Discussions
        Route::get('/dashboard/editorial/{article}/discussions', [DiscussionController::class, 'index'])->name('editorial.discussions.index');
        Route::post('/dashboard/editorial/{article}/discussions', [DiscussionController::class, 'store'])->name('editorial.discussions.store');
    });

    // Copyedited file download (authorized via policy, accessible to submitter and editorial staff)
    Route::get('/dashboard/editorial/{article}/copyedited-file/download', [EditorialController::class, 'downloadCopyeditedFile'])
        ->middleware('permission:submit-article|manage-submissions')
        ->name('editorial.download-copyedited-file');

    // Author discussions (requires submit-article permission)
    Route::middleware('permission:submit-article')->group(function () {
        Route::post('/dashboard/articles/{article}/discussions', [DiscussionController::class, 'store'])->name('submissions.discussions.store');
        Route::post('/dashboard/articles/{article}/approve-galley', [SubmissionController::class, 'approveGalley'])->name('submissions.approve-galley');
        Route::post('/dashboard/articles/{article}/request-revision', [SubmissionController::class, 'requestGalleyRevision'])->name('submissions.request-revision');
    });

    // Discussion management (authorized via policy)
    Route::post('/dashboard/discussions/{discussion}/resolve', [DiscussionController::class, 'resolve'])->name('discussions.resolve');
    Route::post('/dashboard/discussions/{discussion}/reopen', [DiscussionController::class, 'reopen'])->name('discussions.reopen');

    // Notifications
    Route::get('/dashboard/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/dashboard/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/dashboard/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
});

require __DIR__.'/auth.php';
