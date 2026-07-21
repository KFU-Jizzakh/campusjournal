<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\ArticleStatus;
use App\Enums\ReviewType;
use App\Http\Controllers\Controller;
use App\Jobs\DepositArticleToCrossref;
use App\Mail\ReviewAssignedDoubleBlindMailable;
use App\Mail\ReviewAssignedMailable;
use App\Models\Article;
use App\Models\Correction;
use App\Models\Issue;
use App\Models\OutboxEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * PURPOSE: Handles the editorial workflow: listing articles,
 * assigning editors and reviewers, making decisions, and
 * progressing through copyediting, production, and publication.
 *
 * SPECIFICATION: SPEC-02/AC-1, SPEC-02/AC-2, SPEC-02/AC-3, SPEC-04/AC-1, SPEC-04/AC-2, SPEC-04/AC-4, SPEC-04/AC-5, SPEC-04/AC-6, SPEC-05/AC-1, SPEC-05/AC-3, SPEC-16/AC-1, SPEC-16/AC-2, SPEC-16/AC-3, SPEC-16/AC-4
 */
class EditorialController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status');

        $query = Article::submitted()
            ->with('submitter.profile', 'editor.profile', 'category')
            ->orderByDesc('submitted_at');

        if ($user->hasRole('section-editor') && ! $user->hasAnyRole(['editor-in-chief', 'managing-editor'])) {
            $query->where('editor_id', $user->id);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $articles = $query->paginate(20)->withQueryString();

        $counts = Article::submitted()
            ->when(
                $user->hasRole('section-editor') && ! $user->hasAnyRole(['editor-in-chief', 'managing-editor']),
                fn ($q) => $q->where('editor_id', $user->id)
            )
            ->selectRaw('count(*) as total')
            ->selectRaw('count(*) filter (where status = ?) as submitted', [ArticleStatus::Submitted->value])
            ->selectRaw('count(*) filter (where status = ?) as in_review', [ArticleStatus::InReview->value])
            ->selectRaw('count(*) filter (where status = ?) as accepted', [ArticleStatus::Accepted->value])
            ->selectRaw('count(*) filter (where status = ?) as copyediting', [ArticleStatus::Copyediting->value])
            ->selectRaw('count(*) filter (where status = ?) as production', [ArticleStatus::Production->value])
            ->selectRaw('count(*) filter (where status = ?) as awaiting_approval', [ArticleStatus::AwaitingApproval->value])
            ->selectRaw('count(*) filter (where status = ?) as approved', [ArticleStatus::Approved->value])
            ->selectRaw('count(*) filter (where status = ?) as revision', [ArticleStatus::Revision->value])
            ->selectRaw('count(*) filter (where status = ?) as rejected', [ArticleStatus::Rejected->value])
            ->selectRaw('count(*) filter (where status = ?) as published', [ArticleStatus::Published->value])
            ->selectRaw('count(*) filter (where status = ?) as withdrawn', [ArticleStatus::Withdrawn->value])
            ->selectRaw('count(*) filter (where status = ?) as retracted', [ArticleStatus::Retracted->value])
            ->first();

        return view('dashboard.editorial.index', compact('articles', 'counts', 'status'));
    }

    public function show(Request $request, Article $article)
    {
        $this->authorize('viewEditorial', $article);

        $article->load('submitter.profile', 'editor.profile', 'category', 'authors', 'reviews.reviewer.profile', 'decidedBy.profile', 'copyeditedBy.profile', 'copyeditedFileUploadedBy.profile', 'productionBy.profile', 'galleyUploadedBy.profile', 'galleySentBy.profile', 'galleyApprovedBy.profile', 'galleyRevisions.requestedBy.profile', 'issue', 'files.uploader.profile', 'discussions.article', 'discussions.review', 'discussions.user.profile', 'discussions.replies.user.profile', 'corrections.createdBy.profile');

        $sectionEditors = User::role('section-editor')->with('profile')->orderBy('email')->get();
        $reviewers = User::permission('review-article')->with('profile')->orderBy('email')->get();
        $issues = Issue::orderByDesc('year')->orderByDesc('number')->get();

        $user = $request->user();
        $showAssignEditor = $article->isSubmitted() && $user->hasAnyRole(['admin', 'editor-in-chief', 'managing-editor']);
        $showPublish = $article->isApproved() && $user->hasPermissionTo('publish-issue');
        $showGalleyUpload = $article->isProduction();
        $showWithdraw = $article->isWithdrawable() && $user->can('withdraw', $article);
        $showRetract = $article->isRetractable() && $user->can('retract', $article);
        $showCorrections = $article->isPublished() && $user->can('manageCorrections', $article);

        $article->discussions
            ->filter(fn ($d) => $d->isVisibleTo($user, $article))
            ->each(function ($d) use ($user) {
                $d->wasUnread = $d->isUnreadBy($user);
                $d->readBy($user);
            });

        return view('dashboard.editorial.show', compact(
            'article', 'sectionEditors', 'reviewers', 'issues', 'showAssignEditor', 'showPublish', 'showGalleyUpload', 'showWithdraw', 'showRetract', 'showCorrections'
        ));
    }

    public function assignEditor(Request $request, Article $article)
    {
        $this->authorize('assignEditor', $article);

        $validated = $request->validate([
            'editor_id' => 'required|exists:users,id',
        ]);

        $editor = User::findOrFail($validated['editor_id']);

        try {
            $article->assignEditor($editor);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Редактор секции назначен.');
    }

    /**
     * Assign a reviewer to the article and send a notification email.
     *
     * Dispatches different mailables based on review_type:
     * - DoubleBlind → ReviewAssignedDoubleBlindMailable (includes blind review instructions)
     * - SingleBlind / Open → ReviewAssignedMailable (standard notice)
     *
     * Domain exceptions (e.g. MissingBlindedPdfException, DuplicateReviewerException)
     * are caught and shown to the user as flash error messages.
     *
     * SPECIFICATION: SPEC-02/AC-3
     */
    public function assignReviewer(Request $request, Article $article)
    {
        $this->authorize('assignReviewer', $article);

        $validated = $request->validate([
            'reviewer_id' => 'required|exists:users,id',
        ]);

        $reviewer = User::findOrFail($validated['reviewer_id']);

        try {
            $review = $article->assignReviewer($reviewer, $request->user());

            if ($reviewer->email) {
                if ($article->review_type === ReviewType::DoubleBlind) {
                    Mail::to($reviewer->email)->queue(new ReviewAssignedDoubleBlindMailable($review));
                } else {
                    Mail::to($reviewer->email)->queue(new ReviewAssignedMailable($review));
                }
            }
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Рецензент назначен и уведомлён по email.');
    }

    public function decide(Request $request, Article $article)
    {
        $this->authorize('decide', $article);

        $validated = $request->validate([
            'decision' => 'required|in:accept,revision,reject',
            'decision_comments' => 'required|string',
        ]);

        try {
            $article->decide($validated['decision'], $validated['decision_comments'], $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $messages = [
            'accept' => 'Статья принята.',
            'revision' => 'Статья отправлена на доработку.',
            'reject' => 'Статья отклонена.',
        ];

        return back()->with('success', $messages[$validated['decision']]);
    }

    public function sendToCopyediting(Request $request, Article $article)
    {
        $this->authorize('sendToCopyediting', $article);

        try {
            $article->sendToCopyediting($request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Статья отправлена на корректуру.');
    }

    public function sendToProduction(Request $request, Article $article)
    {
        $this->authorize('sendToProduction', $article);

        try {
            $article->sendToProduction($request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Статья отправлена в производство.');
    }

    public function publish(Request $request, Article $article)
    {
        $this->authorize('publish', $article);

        $validated = $request->validate([
            'issue_id' => 'required|exists:issues,id',
        ]);

        try {
            $article->publish(Issue::findOrFail($validated['issue_id']));
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (config('services.crossref.enabled')) {
            DepositArticleToCrossref::dispatch($article->fresh(), $request->user()?->id);
        }

        return back()->with('success', 'Статья опубликована.');
    }

    /**
     * Change the article's review anonymity model.
     *
     * Validates against the three allowed ReviewType values. Delegates the
     * actual model change to Article::setReviewType, which enforces the
     * active-reviewers guard and logs the OutboxEvent.
     *
     * SPECIFICATION: SPEC-05/AC-1
     */
    public function setReviewType(Request $request, Article $article)
    {
        $this->authorize('manageReviewType', $article);

        $validated = $request->validate([
            'review_type' => 'required|in:single_blind,double_blind,open',
        ]);

        try {
            $article->setReviewType(ReviewType::from($validated['review_type']));
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Тип рецензирования изменён.');
    }

    /**
     * Upload an anonymised manuscript for double-blind peer review.
     *
     * Replaces any existing blinded PDF (old file is deleted from disk).
     * Records who uploaded and when, and writes an OutboxEvent for the
     * audit trail. The file is stored on the local (private) disk so
     * it is never directly accessible via a public URL.
     *
     * SPECIFICATION: SPEC-05/AC-3
     */
    public function uploadBlindedPdf(Request $request, Article $article)
    {
        $this->authorize('uploadBlindedPdf', $article);

        $validated = $request->validate([
            'blinded_pdf' => 'required|file|mimetypes:application/pdf|max:51200',
        ]);

        if ($article->blinded_pdf_path) {
            Storage::disk('local')->delete($article->blinded_pdf_path);
        }

        $path = $request->file('blinded_pdf')->store('submissions', 'local');

        $article->update([
            'blinded_pdf_path' => $path,
            'blinded_at' => now(),
            'blinded_by' => $request->user()->id,
        ]);

        OutboxEvent::log('article.blinded_pdf_uploaded', $article, [
            'file_path' => $path,
        ]);

        return back()->with('success', 'Анонимизированная рукопись загружена.');
    }

    /**
     * Delete the anonymised manuscript.
     *
     * Blocked while active reviewers exist — removing the blinded PDF
     * during an ongoing double-blind review would break the anonymity
     * contract. Clears all blinded_pdf_* columns and writes an OutboxEvent.
     *
     * SPECIFICATION: SPEC-05/AC-3
     */
    public function deleteBlindedPdf(Request $request, Article $article)
    {
        $this->authorize('uploadBlindedPdf', $article);

        if ($article->hasActiveReviewers()) {
            return back()->with('error', 'Нельзя удалить анонимизированную рукопись при наличии активных рецензентов.');
        }

        if ($article->blinded_pdf_path) {
            Storage::disk('local')->delete($article->blinded_pdf_path);
        }

        $oldPath = $article->blinded_pdf_path;

        $article->update([
            'blinded_pdf_path' => null,
            'blinded_at' => null,
            'blinded_by' => null,
        ]);

        OutboxEvent::log('article.blinded_pdf_deleted', $article, [
            'old_file_path' => $oldPath,
        ]);

        return back()->with('success', 'Анонимизированная рукопись удалена.');
    }

    /**
     * Upload the typeset galley PDF during the Production stage,
     * replacing any previously uploaded version.
     *
     * SPECIFICATION: SPEC-13/AC-1
     */
    public function uploadGalleyPdf(Request $request, Article $article)
    {
        $this->authorize('uploadGalleyPdf', $article);

        if (! $article->isProduction()) {
            return back()->with('error', __('article.error_galley_not_production'));
        }

        $validated = $request->validate([
            'galley_pdf' => 'required|file|mimetypes:application/pdf|max:51200',
        ]);

        if ($article->galley_pdf_path) {
            Storage::disk('local')->delete($article->galley_pdf_path);
        }

        $path = $request->file('galley_pdf')->store('galley_uploads', 'local');

        $article->update([
            'galley_pdf_path' => $path,
            'galley_uploaded_at' => now(),
            'galley_uploaded_by' => $request->user()->id,
        ]);

        OutboxEvent::log('galley.pdf_uploaded', $article, [
            'file_path' => $path,
        ]);

        return back()->with('success', __('dashboard.galley_uploaded_success'));
    }

    /**
     * Send the uploaded galley proof to the author for final approval.
     *
     * SPECIFICATION: SPEC-13/AC-1, SPEC-13/AC-2
     */
    public function sendGalleyToAuthor(Request $request, Article $article)
    {
        $this->authorize('sendGalleyToAuthor', $article);

        try {
            $article->sendGalleyToAuthor($request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('dashboard.galley_sent_success'));
    }

    /**
     * Upload the corrected manuscript file during the Copyediting stage,
     * replacing any previously uploaded version.
     *
     * SPECIFICATION: SPEC-04/AC-4, SPEC-04/AC-4a
     */
    public function uploadCopyeditedFile(Request $request, Article $article)
    {
        $this->authorize('uploadCopyeditedFile', $article);

        $request->validate([
            'copyedited_file' => 'required|file|mimes:pdf,docx|max:51200',
        ]);

        $path = $request->file('copyedited_file')->store('copyedited', 'local');

        try {
            $article->uploadCopyeditedFile($request->user(), $path);
        } catch (\DomainException $e) {
            Storage::disk('local')->delete($path);

            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);

            throw $e;
        }

        return back()->with('success', __('dashboard.copyedited_file_uploaded'));
    }

    /**
     * Download the corrected manuscript file.
     *
     * SPECIFICATION: SPEC-04/AC-4a
     */
    public function downloadCopyeditedFile(Article $article)
    {
        $this->authorize('downloadCopyeditedFile', $article);

        abort_unless($article->copyedited_file_path, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($article->copyedited_file_path), 404);

        $extension = strtolower(pathinfo($article->copyedited_file_path, PATHINFO_EXTENSION));
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        $mimeType = $extension ? ($mimeTypes[$extension] ?? 'application/octet-stream') : 'application/octet-stream';
        $filename = $extension
            ? "article-{$article->id}-copyedited.{$extension}"
            : "article-{$article->id}-copyedited";

        return $disk->response($article->copyedited_file_path, $filename, [
            'Content-Type' => $mimeType,
        ]);
    }

    /**
     * Delete the corrected manuscript file.
     *
     * SPECIFICATION: SPEC-04/AC-4a
     */
    public function deleteCopyeditedFile(Request $request, Article $article)
    {
        $this->authorize('deleteCopyeditedFile', $article);

        if (! $article->copyedited_file_path) {
            return back()->with('error', __('article.error_no_copyedited_file_to_delete'));
        }

        try {
            $article->deleteCopyeditedFile($request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('dashboard.copyedited_file_deleted'));
    }

    /**
     * Withdraw an article from the editorial workflow.
     * Only pre-published articles can be withdrawn.
     *
     * SPECIFICATION: SPEC-16/AC-2
     */
    public function withdraw(Request $request, Article $article)
    {
        $this->authorize('withdraw', $article);

        $validated = $request->validate([
            'reason' => 'required|string|max:5000',
        ]);

        try {
            $article->withdraw($validated['reason'], $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Статья отозвана.');
    }

    /**
     * Retract a published article. Article remains visible
     * with a retraction notice. Re-deposits DOI with Crossmark
     * retraction metadata if Crossref is enabled.
     *
     * SPECIFICATION: SPEC-16/AC-3
     */
    public function retract(Request $request, Article $article)
    {
        $this->authorize('retract', $article);

        $validated = $request->validate([
            'reason' => 'required|string|max:5000',
        ]);

        try {
            $article->retract($validated['reason'], $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (config('services.crossref.enabled')) {
            DepositArticleToCrossref::dispatch($article->fresh(), $request->user()?->id, 'retraction');
        }

        return back()->with('success', 'Статья отозвана (ретрекшн).');
    }

    /**
     * Add a post-publication correction to a published article.
     *
     * SPECIFICATION: SPEC-16/AC-4
     */
    public function storeCorrection(Request $request, Article $article)
    {
        $this->authorize('manageCorrections', $article);

        $validated = $request->validate([
            'type' => 'required|in:corrigendum,erratum,expression_of_concern',
            'title' => 'required|string|max:500',
            'description' => 'required|string|max:10000',
            'published_at' => 'required|date',
            'file' => 'nullable|file|mimes:pdf|max:51200',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('corrections', 'local');
        }

        DB::transaction(function () use ($article, $request, $validated, $filePath) {
            Correction::create([
                'article_id' => $article->id,
                'type' => $validated['type'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'file_path' => $filePath,
                'published_at' => $validated['published_at'],
                'created_by' => $request->user()->id,
            ]);

            OutboxEvent::log('article.correction_added', $article, [
                'correction_type' => $validated['type'],
                'correction_title' => $validated['title'],
            ]);
        });

        if (config('services.crossref.enabled')) {
            DepositArticleToCrossref::dispatch($article->fresh()->load('corrections'), $request->user()?->id, 'correction');
        }

        return back()->with('success', 'Исправление добавлено.');
    }

    /**
     * Delete a post-publication correction.
     *
     * SPECIFICATION: SPEC-16/AC-4
     */
    public function destroyCorrection(Request $request, Article $article, Correction $correction)
    {
        abort_unless($correction->article_id === $article->id, 404);

        $this->authorize('manageCorrections', $article);

        $correction->delete();

        if ($correction->file_path) {
            Storage::disk('local')->delete($correction->file_path);
        }

        if (config('services.crossref.enabled')) {
            DepositArticleToCrossref::dispatch($article->fresh()->load('corrections'), $request->user()?->id, 'correction');
        }

        return back()->with('success', 'Исправление удалено.');
    }
}
