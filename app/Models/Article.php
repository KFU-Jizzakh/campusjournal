<?php

namespace App\Models;

use App\Enums\ArticleFileLicense;
use App\Enums\ArticleStatus;
use App\Enums\ReviewStatus;
use App\Enums\ReviewType;
use App\Exceptions\ArticleNotRetractableException;
use App\Exceptions\ArticleNotWithdrawableException;
use App\Exceptions\AssignEditorFailedException;
use App\Exceptions\AssignReviewerFailedException;
use App\Exceptions\CannotReviewArticleException;
use App\Exceptions\CopyeditedFileNotUploadedException;
use App\Exceptions\DeleteCopyeditedFileFailedException;
use App\Exceptions\DuplicateReviewerException;
use App\Exceptions\EditorIsAuthorException;
use App\Exceptions\GalleyApprovalRequiredException;
use App\Exceptions\GalleyNotAwaitingApprovalException;
use App\Exceptions\GalleyNotProductionException;
use App\Exceptions\GalleyPdfNotUploadedException;
use App\Exceptions\InvalidTransitionException;
use App\Exceptions\IssueNotPublishedException;
use App\Exceptions\MissingBlindedPdfException;
use App\Exceptions\MissingCompletedReviewsException;
use App\Exceptions\NotSectionEditorException;
use App\Exceptions\ReviewerIsAuthorException;
use App\Exceptions\ReviewTypeChangeForbiddenException;
use App\Exceptions\SendToCopyeditingFailedException;
use App\Exceptions\SendToProductionFailedException;
use App\Exceptions\UploadCopyeditedFileFailedException;
use App\Notifications\AuthorApprovedGalley;
use App\Notifications\AuthorDecisionMade;
use App\Notifications\AuthorGalleyReady;
use App\Notifications\AuthorResubmitted;
use App\Notifications\AuthorStatusChanged;
use App\Notifications\AuthorSubmissionReceived;
use App\Notifications\EditorGalleyRevisionRequested;
use App\Notifications\ReviewReRequested;
use App\Services\Doi\DoiMinter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * PURPOSE: Core editorial workflow entity representing a submitted
 * manuscript, managing the full lifecycle from draft through peer
 * review, decision, copyediting, production, and publication.
 *
 * SPECIFICATION: SPEC-01/AC-1, SPEC-01/AC-7, SPEC-01/BR-1, SPEC-01/BR-2, SPEC-01/BR-3, SPEC-01/BR-4, SPEC-01/BR-5, SPEC-01/BR-6, SPEC-01/BR-7, SPEC-02/BR-6, SPEC-04/BR-1, SPEC-04/BR-5, SPEC-05/BR-1, SPEC-05/BR-2, SPEC-05/BR-3, SPEC-05/BR-4, SPEC-13/BR-1, SPEC-13/BR-2, SPEC-13/BR-3, SPEC-15/AC-2, SPEC-15/AC-4, SPEC-15/BR-1, SPEC-15/BR-2, SPEC-15/BR-3, SPEC-15/BR-4, SPEC-15/BR-5, SPEC-16/BR-1, SPEC-16/BR-2, SPEC-16/BR-3, SPEC-17/BR-1, SPEC-25/BR-1, SPEC-25/BR-2, SPEC-25/BR-3, SPEC-25/BR-4
 */
#[Fillable(['title', 'abstract_ru', 'abstract_en', 'body', 'doi', 'keywords', 'funding', 'pages', 'first_page', 'last_page', 'views_count', 'downloads_count', 'pdf_path', 'blinded_pdf_path', 'blinded_at', 'blinded_by', 'status', 'review_type', 'current_round', 'issue_id', 'category_id', 'submitted_by', 'submitted_at', 'published_at', 'doi_registered_at', 'editor_id', 'decision', 'decision_comments', 'decided_at', 'decided_by', 'copyedited_at', 'copyedited_by', 'copyedited_file_path', 'copyedited_file_uploaded_at', 'copyedited_file_uploaded_by', 'production_at', 'production_by', 'galley_pdf_path', 'galley_uploaded_at', 'galley_uploaded_by', 'galley_sent_at', 'galley_sent_by', 'galley_approved_at', 'galley_approved_by', 'withdrawal_reason', 'withdrawn_at', 'retraction_reason', 'retracted_at'])]
class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'review_type' => ReviewType::class,
            'current_round' => 'integer',
            'submitted_at' => 'datetime',
            'published_at' => 'datetime',
            'doi_registered_at' => 'datetime',
            'decided_at' => 'datetime',
            'blinded_at' => 'datetime',
            'copyedited_at' => 'datetime',
            'copyedited_file_uploaded_at' => 'datetime',
            'production_at' => 'datetime',
            'galley_uploaded_at' => 'datetime',
            'galley_sent_at' => 'datetime',
            'galley_approved_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'retracted_at' => 'datetime',
            'keywords' => 'array',
            'funding' => 'array',
        ];
    }

    /**
     * PURPOSE: Guard the public visibility invariant — a published
     * or retracted article cannot be saved against an issue that
     * has not been published.
     *
     * SPECIFICATION: SPEC-24/BR-1
     */
    protected static function booted(): void
    {
        static::saving(function (self $article) {
            if (! $article->issue_id) {
                return;
            }

            if (! in_array($article->status, [ArticleStatus::Published, ArticleStatus::Retracted], true)) {
                return;
            }

            $issue = Issue::query()->whereKey($article->issue_id)->first();

            if ($issue && ! $issue->isPublished()) {
                throw new IssueNotPublishedException;
            }
        });
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    /**
     * The user who uploaded the anonymised manuscript for double-blind review.
     *
     * SPECIFICATION: SPEC-05/AC-3, SPEC-05/AC-4
     */
    public function blindedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blinded_by');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function copyeditedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'copyedited_by');
    }

    /**
     * The user who uploaded the corrected manuscript file
     * during the Copyediting stage.
     *
     * SPECIFICATION: SPEC-04/AC-4a
     */
    public function copyeditedFileUploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'copyedited_file_uploaded_by');
    }

    public function productionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'production_by');
    }

    /**
     * The user who uploaded the typeset galley PDF.
     *
     * SPECIFICATION: SPEC-13/AC-1
     */
    public function galleyUploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'galley_uploaded_by');
    }

    /**
     * The user who sent the galley proof to the author.
     *
     * SPECIFICATION: SPEC-13/AC-1
     */
    public function galleySentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'galley_sent_by');
    }

    /**
     * The user (author) who approved the galley proof.
     *
     * SPECIFICATION: SPEC-13/AC-4
     */
    public function galleyApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'galley_approved_by');
    }

    /**
     * Galley revision request records, newest first.
     *
     * SPECIFICATION: SPEC-13/BR-3
     */
    public function galleyRevisions(): HasMany
    {
        return $this->hasMany(GalleyRevision::class)->orderByDesc('created_at');
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'article_author')
            ->withPivot('order', 'email', 'phone', 'country', 'city', 'website')
            ->orderByPivot('order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Author response letters submitted with revision resubmissions,
     * ordered by review round.
     *
     * SPECIFICATION: SPEC-25/AC-2
     */
    public function responseLetters(): HasMany
    {
        return $this->hasMany(ResponseLetter::class)->orderBy('round')->orderBy('created_at');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ArticleFile::class)->orderBy('created_at');
    }

    /**
     * Post-publication corrections: corrigenda, errata,
     * and expressions of concern.
     *
     * SPECIFICATION: SPEC-16/AC-4, SPEC-16/BR-6
     */
    public function corrections(): HasMany
    {
        return $this->hasMany(Correction::class)->orderByDesc('published_at');
    }

    public function crossrefDeposits(): HasMany
    {
        return $this->hasMany(CrossrefDeposit::class)->orderByDesc('created_at');
    }

    public function references(): HasMany
    {
        return $this->hasMany(Reference::class)->orderBy('order');
    }

    /**
     * Joined raw reference texts for pre-filling the dashboard textarea.
     *
     * SPECIFICATION: SPEC-15/BR-6
     */
    public function getReferencesTextAttribute(): string
    {
        return $this->relationLoaded('references')
            ? $this->references->pluck('raw')->implode("\n")
            : '';
    }

    public function latestCrossrefDeposit(): HasOne
    {
        return $this->hasOne(CrossrefDeposit::class)->latestOfMany();
    }

    /**
     * Copyright agreement acceptance records for this article,
     * newest first. Multiple records exist when an article is
     * resubmitted after revision (BR-2).
     *
     * SPECIFICATION: SPEC-14/AC-3, SPEC-14/BR-2, SPEC-14/BR-4
     */
    public function agreements(): HasMany
    {
        return $this->hasMany(ArticleAgreement::class)->orderByDesc('created_at');
    }

    /**
     * Most recent copyright agreement acceptance record.
     *
     * SPECIFICATION: SPEC-14/AC-4
     */
    public function latestAgreement(): HasOne
    {
        return $this->hasOne(ArticleAgreement::class)->latestOfMany();
    }

    /**
     * The license under which the article is published,
     * derived from the latest accepted CopyrightAgreement.
     *
     * SPECIFICATION: SPEC-14/AC-6, SPEC-14/BR-5
     */
    public function publicationLicense(): ?ArticleFileLicense
    {
        return $this->latestAgreement?->agreement?->license;
    }

    /**
     * Record acceptance of the given copyright agreement version
     * by the specified user from the given IP address.
     *
     * SPECIFICATION: SPEC-14/AC-3, SPEC-14/BR-1, SPEC-14/BR-2
     */
    public function saveAgreement(CopyrightAgreement $agreement, User $user, string $ip): ArticleAgreement
    {
        return ArticleAgreement::create([
            'article_id' => $this->id,
            'copyright_agreement_id' => $agreement->id,
            'accepted_by' => $user->id,
            'accepted_ip' => $ip,
        ]);
    }

    public function scopePublished($query)
    {
        return $query->whereIn('status', [ArticleStatus::Published, ArticleStatus::Retracted]);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', '!=', ArticleStatus::Draft);
    }

    /**
     * Determine which disk the article's PDF is stored on.
     * Legacy files on the public disk (path starts with "articles/") stay there;
     * submissions are on the local (private) disk.
     */
    public function getPdfDiskAttribute(): string
    {
        if (! $this->pdf_path) {
            return 'local';
        }

        // Files uploaded via Filament admin go to public disk "articles/" directory
        if (str_starts_with($this->pdf_path, 'articles/')) {
            return 'public';
        }

        // Legacy submissions already on public disk
        if (Storage::disk('public')->exists($this->pdf_path)) {
            return 'public';
        }

        return 'local';
    }

    /**
     * Validate and perform a status transition.
     *
     * SPECIFICATION: SPEC-01/BR-5, SPEC-04/BR-5
     */
    public function transitionTo(ArticleStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new InvalidTransitionException($this->status->label(), $target->label());
        }

        $this->status = $target;
    }

    /**
     * Create a new article submission from the given data,
     * attach the submitter, and transition to Submitted status.
     *
     * SPECIFICATION: SPEC-01/AC-1
     */
    public static function submit(User $submitter, array $data): static
    {
        return DB::transaction(function () use ($submitter, $data) {
            // Auto-assign the section editor mapped to the rubric, if any —
            // a silent routing hint, not a formal editorial assignment.
            // The submitter is never auto-assigned as editor of their own article.
            $sectionEditorId = Category::query()->whereKey($data['category_id'])->value('section_editor_id');
            $sectionEditorId = $sectionEditorId !== $submitter->id ? $sectionEditorId : null;

            $article = static::create([
                ...$data,
                'status' => ArticleStatus::Submitted,
                'submitted_by' => $submitter->id,
                'submitted_at' => now(),
                'editor_id' => $sectionEditorId,
            ]);

            OutboxEvent::log('submission.created', $article, [
                'title' => $article->title,
                'category_id' => $article->category_id,
            ]);

            $submitter->notify(new AuthorSubmissionReceived($article));

            return $article;
        });
    }

    /**
     * Resubmit after a revision request — clear decision fields
     * and transition back to Submitted status.
     * Notifies the assigned editor that the author resubmitted.
     *
     * SPECIFICATION: SPEC-01/AC-7, SPEC-01/BR-5
     */
    public function revise(array $data): void
    {
        $oldPath = null;
        $oldBlindedPath = null;
        DB::transaction(function () use ($data, &$oldPath, &$oldBlindedPath) {
            $lockedArticle = static::lockForUpdate()->findOrFail($this->id);

            $oldPath = $lockedArticle->copyedited_file_path;
            $oldBlindedPath = $lockedArticle->blinded_pdf_path;

            $lockedArticle->update([
                ...$data,
                'submitted_at' => now(),
                'current_round' => $lockedArticle->current_round + 1,
                'decision' => null,
                'decision_comments' => null,
                'decided_at' => null,
                'decided_by' => null,
                // A new round must not reuse the previous round's anonymised
                // PDF — otherwise a double-blind round 2 would leak identity.
                'blinded_pdf_path' => null,
                'blinded_at' => null,
                'blinded_by' => null,
                'copyedited_at' => null,
                'copyedited_by' => null,
                'copyedited_file_path' => null,
                'copyedited_file_uploaded_at' => null,
                'copyedited_file_uploaded_by' => null,
                'production_at' => null,
                'production_by' => null,
            ]);

            $lockedArticle->transitionTo(ArticleStatus::Submitted);
            $lockedArticle->save();

            OutboxEvent::log('submission.revised', $lockedArticle, [
                'title' => $lockedArticle->title,
            ]);

            if ($lockedArticle->editor) {
                $lockedArticle->editor->notify(new AuthorResubmitted($lockedArticle));
            }
        });

        $this->refresh();

        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        if ($oldBlindedPath) {
            Storage::disk('local')->delete($oldBlindedPath);
        }
    }

    /**
     * Update article data, automatically handling revision
     * resubmission when in Revision status.
     *
     * SPECIFICATION: SPEC-01/BR-5, SPEC-01/BR-6
     */
    public function updateOrRevise(array $data): void
    {
        if ($this->status === ArticleStatus::Revision) {
            $this->revise($data);
        } else {
            $this->update($data);
        }
    }

    /**
     * Assign a section editor to the article (must be in Submitted status).
     *
     * SPECIFICATION: SPEC-02/AC-2, SPEC-02/BR-1, SPEC-02/BR-2, SPEC-02/BR-3
     */
    public function assignEditor(User $editor): void
    {
        if (! $editor->hasRole('section-editor')) {
            throw new NotSectionEditorException;
        }

        if ($this->isAuthoredBy($editor)) {
            throw new EditorIsAuthorException;
        }

        DB::transaction(function () use ($editor) {
            $lockedArticle = static::lockForUpdate()->findOrFail($this->id);

            if ($lockedArticle->status !== ArticleStatus::Submitted) {
                throw new AssignEditorFailedException;
            }

            $lockedArticle->update(['editor_id' => $editor->id]);

            OutboxEvent::log('editor.assigned', $lockedArticle, [
                'editor_id' => $editor->id,
            ]);
        });
    }

    /**
     * Assign a reviewer to the article with response and review
     * deadlines; transition article to InReview if Submitted.
     *
     * SPECIFICATION: SPEC-02/AC-3, SPEC-02/BR-4, SPEC-02/BR-5, SPEC-02/BR-6, SPEC-02/BR-7, SPEC-02/BR-8, SPEC-05/BR-2
     */
    public function assignReviewer(User $reviewer, User $assignedBy): Review
    {
        if (! $reviewer->hasPermissionTo('review-article')) {
            throw new CannotReviewArticleException;
        }

        return DB::transaction(function () use ($reviewer, $assignedBy) {
            $lockedArticle = static::lockForUpdate()->findOrFail($this->id);

            if (! in_array($lockedArticle->status, [ArticleStatus::Submitted, ArticleStatus::InReview])) {
                throw new AssignReviewerFailedException;
            }

            // Conflict of interest: the article's authors can never review it.
            if ($lockedArticle->isAuthoredBy($reviewer)) {
                throw new ReviewerIsAuthorException;
            }

            // Guard: double-blind requires an anonymised PDF before reviewers can be assigned.
            // Prevents author identity exposure through the manuscript file. SPECIFICATION: SPEC-05/BR-2
            if ($lockedArticle->review_type === ReviewType::DoubleBlind && ! $lockedArticle->blinded_pdf_path) {
                throw new MissingBlindedPdfException;
            }

            // Check for an existing active (non-declined, non-cancelled) review
            // in the CURRENT round only — reviewers who completed, declined, or
            // were cancelled earlier may be re-invited.
            if ($lockedArticle->reviews()
                ->where('reviewer_id', $reviewer->id)
                ->where('round', $lockedArticle->current_round)
                ->whereNotIn('status', [ReviewStatus::Declined, ReviewStatus::Cancelled])
                ->exists()) {
                throw new DuplicateReviewerException;
            }

            // A completed review from an earlier round means this is a re-request.
            $isReRequest = $lockedArticle->reviews()
                ->where('reviewer_id', $reviewer->id)
                ->where('round', '<', $lockedArticle->current_round)
                ->where('status', ReviewStatus::Completed)
                ->exists();

            $responseDays = (int) Setting::get('review_response_days', '7');
            $deadlineDays = (int) Setting::get('review_deadline_days', '30');

            try {
                $review = Review::create([
                    'article_id' => $lockedArticle->id,
                    'reviewer_id' => $reviewer->id,
                    'assigned_by' => $assignedBy->id,
                    'status' => ReviewStatus::Pending,
                    'round' => $lockedArticle->current_round,
                    'assigned_at' => now(),
                    'response_due_at' => now()->addDays($responseDays),
                    'review_due_at' => now()->addDays($deadlineDays),
                ]);
            } catch (QueryException $e) {
                // PostgreSQL unique_violation (SQLSTATE 23505) from the partial unique index
                if ($e->getPrevious()?->getCode() === '23505') {
                    throw new DuplicateReviewerException;
                }
                throw $e;
            }

            if ($isReRequest) {
                $reviewer->notify(new ReviewReRequested($review));
            }

            if ($lockedArticle->status === ArticleStatus::Submitted) {
                $lockedArticle->transitionTo(ArticleStatus::InReview);
                $lockedArticle->save();

                $lockedArticle->notifiableUsers()->each(
                    fn (User $user) => $user->notify(
                        new AuthorStatusChanged($lockedArticle, 'article.in_review', 'Статья отправлена на рецензирование')
                    )
                );
                $lockedArticle->markNotified('article.in_review');
            }

            OutboxEvent::log('reviewer.assigned', $review, [
                'article_id' => $lockedArticle->id,
                'reviewer_id' => $reviewer->id,
                'response_due_at' => $review->response_due_at?->toIso8601String(),
                'review_due_at' => $review->review_due_at?->toIso8601String(),
            ]);

            return $review;
        });
    }

    /**
     * Make an editorial decision (accept/revision/reject).
     * Requires at least one completed review.
     *
     * SPECIFICATION: SPEC-04/AC-2, SPEC-04/BR-1
     */
    public function decide(string $decision, string $comments, User $decidedBy): void
    {
        $statusMap = [
            'accept' => ArticleStatus::Accepted,
            'revision' => ArticleStatus::Revision,
            'reject' => ArticleStatus::Rejected,
        ];

        DB::transaction(function () use ($decision, $comments, $decidedBy, $statusMap) {
            $lockedArticle = static::lockForUpdate()->findOrFail($this->id);

            // Only completed reviews of the CURRENT round justify a decision —
            // otherwise the editor could decide on stale round 1 feedback.
            if ($lockedArticle->reviews()
                ->where('status', ReviewStatus::Completed)
                ->where('round', $lockedArticle->current_round)
                ->doesntExist()) {
                throw new MissingCompletedReviewsException;
            }

            $lockedArticle->update([
                'decision' => $decision,
                'decision_comments' => $comments,
                'decided_at' => now(),
                'decided_by' => $decidedBy->id,
            ]);

            $lockedArticle->transitionTo($statusMap[$decision]);
            $lockedArticle->save();

            OutboxEvent::log('decision.made', $lockedArticle, [
                'decision' => $decision,
                'new_status' => $statusMap[$decision]->value,
            ]);

            $lockedArticle->notifiableUsers()->each(
                fn (User $user) => $user->notify(new AuthorDecisionMade($lockedArticle))
            );
        });
    }

    /**
     * Transition the article from Accepted to Copyediting status.
     *
     * SPECIFICATION: SPEC-04/AC-4, SPEC-04/BR-5
     */
    public function sendToCopyediting(User $actor): void
    {
        DB::transaction(function () use ($actor) {
            $lockedArticle = static::lockForUpdate()->findOrFail($this->id);

            if ($lockedArticle->status !== ArticleStatus::Accepted) {
                throw new SendToCopyeditingFailedException;
            }

            $lockedArticle->transitionTo(ArticleStatus::Copyediting);

            $lockedArticle->fill([
                'copyedited_at' => now(),
                'copyedited_by' => $actor->id,
            ])->save();

            OutboxEvent::log('article.sent_to_copyediting', $lockedArticle);

            if (! $lockedArticle->wasRecentlyNotified('article.sent_to_copyediting')) {
                $lockedArticle->notifiableUsers()->each(
                    fn (User $user) => $user->notify(
                        new AuthorStatusChanged($lockedArticle, 'article.sent_to_copyediting', 'Статья отправлена на корректуру')
                    )
                );
                $lockedArticle->markNotified('article.sent_to_copyediting');
            }
        });
    }

    /**
     * Upload a corrected manuscript file during the Copyediting stage,
     * replacing any previously uploaded version.
     *
     * SPECIFICATION: SPEC-04/AC-4, SPEC-04/AC-4a
     */
    public function uploadCopyeditedFile(User $actor, string $filePath): void
    {
        $oldPath = null;
        DB::transaction(function () use ($actor, $filePath, &$oldPath) {
            $lockedArticle = static::lockForUpdate()->findOrFail($this->id);

            if ($lockedArticle->status !== ArticleStatus::Copyediting) {
                throw new UploadCopyeditedFileFailedException;
            }

            $oldPath = $lockedArticle->copyedited_file_path;

            $lockedArticle->update([
                'copyedited_file_path' => $filePath,
                'copyedited_file_uploaded_at' => now(),
                'copyedited_file_uploaded_by' => $actor->id,
            ]);

            OutboxEvent::log('copyedited.file_uploaded', $lockedArticle, [
                'file_path' => $filePath,
            ], $actor);
        });

        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }
    }

    /**
     * Delete the corrected manuscript file uploaded during Copyediting.
     *
     * SPECIFICATION: SPEC-04/AC-4a
     */
    public function deleteCopyeditedFile(User $actor): void
    {
        $oldPath = null;
        DB::transaction(function () use ($actor, &$oldPath) {
            $lockedArticle = static::lockForUpdate()->findOrFail($this->id);

            if ($lockedArticle->status !== ArticleStatus::Copyediting) {
                throw new DeleteCopyeditedFileFailedException;
            }

            $oldPath = $lockedArticle->copyedited_file_path;

            $lockedArticle->update([
                'copyedited_file_path' => null,
                'copyedited_file_uploaded_at' => null,
                'copyedited_file_uploaded_by' => null,
            ]);

            OutboxEvent::log('copyedited.file_deleted', $lockedArticle, [], $actor);
        });

        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }
    }

    /**
     * Transition the article from Copyediting to Production status.
     *
     * SPECIFICATION: SPEC-04/AC-5, SPEC-04/BR-4a, SPEC-04/BR-5
     */
    public function sendToProduction(User $actor): void
    {
        DB::transaction(function () use ($actor) {
            $lockedArticle = static::lockForUpdate()->findOrFail($this->id);

            if ($lockedArticle->status !== ArticleStatus::Copyediting) {
                throw new SendToProductionFailedException;
            }

            if (! $lockedArticle->copyedited_file_path) {
                throw new CopyeditedFileNotUploadedException;
            }

            $lockedArticle->transitionTo(ArticleStatus::Production);

            $lockedArticle->fill([
                'production_at' => now(),
                'production_by' => $actor->id,
            ])->save();

            OutboxEvent::log('article.sent_to_production', $lockedArticle);

            if (! $lockedArticle->wasRecentlyNotified('article.sent_to_production')) {
                $lockedArticle->notifiableUsers()->each(
                    fn (User $user) => $user->notify(
                        new AuthorStatusChanged($lockedArticle, 'article.sent_to_production', 'Статья отправлена в производство')
                    )
                );
                $lockedArticle->markNotified('article.sent_to_production');
            }
        });
    }

    /**
     * Publish the article — assign to an issue, set published_at,
     * mint and persist a DOI if none exists, and transition to
     * Published status. Requires prior author galley approval (BR-1)
     * and a published issue (SPEC-24/BR-1).
     *
     * SPECIFICATION: SPEC-04/AC-6, SPEC-04/BR-6, SPEC-04/BR-7, SPEC-13/BR-1, SPEC-08/AC-2, SPEC-08/BR-2a, SPEC-24/BR-1
     */
    public function publish(Issue $issue): void
    {
        if ($this->status !== ArticleStatus::Approved) {
            throw new GalleyApprovalRequiredException;
        }

        if (! $issue->isPublished()) {
            throw new IssueNotPublishedException;
        }

        DB::transaction(function () use ($issue) {
            $lockedArticle = static::lockForUpdate()->findOrFail($this->id);
            $lockedArticle->transitionTo(ArticleStatus::Published);

            $data = [
                'issue_id' => $issue->id,
                'published_at' => now(),
            ];

            $minter = app(DoiMinter::class);
            if ($minter->isConfigured() && ! filled($lockedArticle->doi)) {
                $data['doi'] = $minter->mint($lockedArticle);
            }

            $lockedArticle->fill($data)->save();

            OutboxEvent::log('article.published', $lockedArticle, [
                'issue_id' => $issue->id,
                'doi' => $lockedArticle->doi,
            ]);

            if (! $lockedArticle->wasRecentlyNotified('article.published')) {
                $lockedArticle->notifiableUsers()->each(
                    fn (User $user) => $user->notify(
                        new AuthorStatusChanged($lockedArticle, 'article.published', 'Статья опубликована')
                    )
                );
                $lockedArticle->markNotified('article.published');
            }
        });
    }

    /**
     * Send the uploaded galley proof to the author for final approval.
     *
     * SPECIFICATION: SPEC-13/AC-1, SPEC-13/AC-2, SPEC-13/BR-3
     */
    public function sendGalleyToAuthor(User $actor): void
    {
        if ($this->status !== ArticleStatus::Production) {
            throw new GalleyNotProductionException;
        }

        if (! $this->galley_pdf_path) {
            throw new GalleyPdfNotUploadedException;
        }

        DB::transaction(function () use ($actor) {
            $lockedArticle = static::lockForUpdate()->findOrFail($this->id);
            $lockedArticle->transitionTo(ArticleStatus::AwaitingApproval);

            $lockedArticle->fill([
                'galley_sent_at' => now(),
                'galley_sent_by' => $actor->id,
            ])->save();

            OutboxEvent::log('galley.sent_to_author', $lockedArticle, [
                'galley_pdf_path' => $lockedArticle->galley_pdf_path,
            ]);

            if (! $lockedArticle->wasRecentlyNotified('galley.sent_to_author')) {
                $lockedArticle->notifiableUsers()->each(
                    fn (User $user) => $user->notify(
                        new AuthorGalleyReady($lockedArticle)
                    )
                );
                $lockedArticle->markNotified('galley.sent_to_author');
            }
        });
    }

    /**
     * Approve the galley proof, unblocking publication.
     * Notifies the editor that the author approved the galley.
     *
     * SPECIFICATION: SPEC-13/AC-4, SPEC-13/BR-1
     */
    public function approveGalley(User $actor): void
    {
        if ($this->status !== ArticleStatus::AwaitingApproval) {
            throw new GalleyNotAwaitingApprovalException;
        }

        DB::transaction(function () use ($actor) {
            $lockedArticle = static::lockForUpdate()->findOrFail($this->id);
            $lockedArticle->transitionTo(ArticleStatus::Approved);

            $lockedArticle->fill([
                'galley_approved_at' => now(),
                'galley_approved_by' => $actor->id,
            ])->save();

            OutboxEvent::log('galley.approved', $lockedArticle);

            if ($lockedArticle->editor) {
                $lockedArticle->editor->notify(new AuthorApprovedGalley($lockedArticle));
            }
        });
    }

    /**
     * Request revisions to the galley proof, returning the
     * article to Production and notifying the editor.
     *
     * SPECIFICATION: SPEC-13/AC-5, SPEC-13/BR-2, SPEC-13/BR-3
     */
    public function requestGalleyRevision(User $actor, string $comment): void
    {
        if ($this->status !== ArticleStatus::AwaitingApproval) {
            throw new GalleyNotAwaitingApprovalException;
        }

        DB::transaction(function () use ($actor, $comment) {
            $lockedArticle = static::lockForUpdate()->findOrFail($this->id);
            $lockedArticle->transitionTo(ArticleStatus::Production);

            $lockedArticle->fill([
                'galley_sent_at' => null,
                'galley_sent_by' => null,
            ])->save();

            GalleyRevision::create([
                'article_id' => $lockedArticle->id,
                'requested_by' => $actor->id,
                'comment' => $comment,
            ]);

            OutboxEvent::log('galley.revision_requested', $lockedArticle, [
                'comment' => $comment,
            ]);

            if ($lockedArticle->editor) {
                $lockedArticle->editor->notify(
                    new EditorGalleyRevisionRequested($lockedArticle, $comment)
                );
            }
        });
    }

    /**
     * Withdraw the article before publication.
     * Only possible from non-terminal, non-published statuses.
     *
     * SPECIFICATION: SPEC-16/AC-1, SPEC-16/AC-2, SPEC-16/BR-1, SPEC-16/BR-3
     */
    public function withdraw(string $reason, User $actor): void
    {
        DB::transaction(function () use ($reason, $actor) {
            $lockedArticle = static::lockForUpdate()->findOrFail($this->id);

            if (! $lockedArticle->isWithdrawable()) {
                throw new ArticleNotWithdrawableException;
            }

            $lockedArticle->transitionTo(ArticleStatus::Withdrawn);

            $lockedArticle->fill([
                'withdrawal_reason' => $reason,
                'withdrawn_at' => now(),
            ])->save();

            $wasAuthor = $actor->id === $lockedArticle->submitted_by;

            OutboxEvent::log('article.withdrawn', $lockedArticle, [
                'reason' => $reason,
                'by_author' => $wasAuthor,
            ]);

            if ($wasAuthor) {
                $notifiable = $lockedArticle->editor
                    ? collect([$lockedArticle->editor])
                    : User::role(['managing-editor', 'editor-in-chief'])->get();

                $notifiable->each(
                    fn (User $user) => $user->notify(
                        new AuthorStatusChanged(
                            $lockedArticle,
                            'article.withdrawn',
                            'Статья отозвана автором'
                        )
                    )
                );
            }

            if (! $wasAuthor && ! $lockedArticle->wasRecentlyNotified('article.withdrawn')) {
                $lockedArticle->notifiableUsers()->each(
                    fn (User $user) => $user->notify(
                        new AuthorStatusChanged($lockedArticle, 'article.withdrawn', 'Статья отозвана редакцией')
                    )
                );
                $lockedArticle->markNotified('article.withdrawn');
            }
        });
    }

    /**
     * Retract a published article. Article remains visible
     * but marked as retracted with a reason.
     *
     * SPECIFICATION: SPEC-16/AC-3, SPEC-16/BR-2, SPEC-16/BR-4
     */
    public function retract(string $reason, User $actor): void
    {
        DB::transaction(function () use ($reason, $actor) {
            $lockedArticle = static::lockForUpdate()->findOrFail($this->id);

            if (! $lockedArticle->isRetractable()) {
                throw new ArticleNotRetractableException;
            }

            $lockedArticle->transitionTo(ArticleStatus::Retracted);

            $lockedArticle->fill([
                'retraction_reason' => $reason,
                'retracted_at' => now(),
            ])->save();

            OutboxEvent::log('article.retracted', $lockedArticle, [
                'reason' => $reason,
                'by' => $actor->id,
            ]);

            if (! $lockedArticle->wasRecentlyNotified('article.retracted')) {
                $lockedArticle->notifiableUsers()->each(
                    fn (User $user) => $user->notify(
                        new AuthorStatusChanged($lockedArticle, 'article.retracted', 'Статья отозвана (ретрекшн)')
                    )
                );
                $lockedArticle->markNotified('article.retracted');
            }
        });
    }

    /**
     * Sync primary author (from submitter) and coauthors via pivot table.
     * Contact details are snapshotted onto the article_author pivot so
     * each article keeps the data as submitted. The submitter's own record
     * is resolved by resolvePrimaryAuthor (adoption of an unlinked ORCID
     * row), coauthors by resolveCoauthorByOrcid. Cleans up orphaned
     * coauthors no longer attached to any article.
     *
     * SPECIFICATION: SPEC-01/AC-1, SPEC-01/BR-4, SPEC-01/BR-8
     */
    public function syncAuthors(User $submitter, array $authorData, array $coauthorsData = []): void
    {
        $previousCoauthorIds = $this->authors()
            ->whereNull('user_id')
            ->pluck('authors.id');

        $primaryAuthor = $this->resolvePrimaryAuthor($submitter, [
            'email' => $authorData['email'] ?? $submitter->email,
            'full_name' => $authorData['full_name'],
            'degree' => $authorData['degree'] ?? null,
            'position' => $authorData['position'] ?? null,
            'organization' => $authorData['organization'] ?? null,
            'orcid' => $authorData['orcid'] ?? null,
            'phone' => $authorData['phone'] ?? null,
            'country' => $authorData['country'] ?? null,
            'city' => $authorData['city'] ?? null,
            'website' => $authorData['website'] ?? null,
        ]);

        $authors = [
            $primaryAuthor->id => [
                'order' => 1,
                'email' => $authorData['email'] ?? $submitter->email,
                'phone' => $authorData['phone'] ?? null,
                'country' => $authorData['country'] ?? null,
                'city' => $authorData['city'] ?? null,
                'website' => $authorData['website'] ?? null,
            ],
        ];

        foreach ($coauthorsData as $index => $coauthorData) {
            $attrs = [
                'full_name' => $coauthorData['full_name'],
                'degree' => $coauthorData['degree'] ?? null,
                'position' => $coauthorData['position'] ?? null,
                'organization' => $coauthorData['organization'] ?? null,
                'orcid' => $coauthorData['orcid'] ?? null,
                'email' => $coauthorData['email'] ?? null,
                'phone' => $coauthorData['phone'] ?? null,
                'country' => $coauthorData['country'] ?? null,
                'city' => $coauthorData['city'] ?? null,
                'website' => $coauthorData['website'] ?? null,
            ];

            if (! empty($coauthorData['orcid'])) {
                $coauthor = $this->resolveCoauthorByOrcid($coauthorData['orcid'], $attrs);
            } else {
                $coauthor = Author::create($attrs);
            }

            $authors[$coauthor->id] = [
                'order' => $index + 2,
                'email' => $coauthorData['email'] ?? null,
                'phone' => $coauthorData['phone'] ?? null,
                'country' => $coauthorData['country'] ?? null,
                'city' => $coauthorData['city'] ?? null,
                'website' => $coauthorData['website'] ?? null,
            ];
        }

        $this->authors()->sync($authors);

        // Delete previous coauthors that are no longer attached to any article
        if ($previousCoauthorIds->isNotEmpty()) {
            Author::whereIn('id', $previousCoauthorIds)
                ->whereNull('user_id')
                ->whereDoesntHave('articles')
                ->delete();
        }
    }

    /**
     * Resolve the submitter's own author record: reused when they already
     * have one, otherwise adopted from an unlinked row carrying the
     * submitted ORCID (the claim itself is gated upstream by
     * ClaimableOrcid), falling back to a fresh row. Rows linked to
     * another account are unreachable here — validation has failed.
     */
    private function resolvePrimaryAuthor(User $submitter, array $attrs): Author
    {
        $own = Author::where('user_id', $submitter->id)->first();

        if ($own !== null) {
            $own->update($attrs);

            return $own;
        }

        if (! empty($attrs['orcid'])) {
            $candidate = Author::withTrashed()
                ->where('orcid', $attrs['orcid'])
                ->whereNull('user_id')
                ->orderBy('id')
                ->first();

            if ($candidate !== null) {
                if ($candidate->trashed()) {
                    $candidate->restore();
                }

                $candidate->update($attrs + ['user_id' => $submitter->id]);

                return $candidate;
            }
        }

        return Author::create($attrs + ['user_id' => $submitter->id]);
    }

    /**
     * Resolve the author row for a listed ORCID: linked rows (user_id set)
     * are reused untouched — the submitter's snapshot lives on the pivot —
     * while unlinked rows follow "latest listing wins". A soft-deleted
     * match is restored instead of spawning an ORCID duplicate
     * (updateOrCreate skips trashed rows). The lookup is deterministic:
     * linked row first, then the oldest row.
     */
    private function resolveCoauthorByOrcid(string $orcid, array $attrs): Author
    {
        $existing = Author::withTrashed()
            ->where('orcid', $orcid)
            ->orderByRaw('user_id IS NULL')
            ->orderBy('id')
            ->first();

        if ($existing === null) {
            return Author::create($attrs);
        }

        if ($existing->trashed()) {
            $existing->restore();
        }

        if ($existing->user_id !== null) {
            return $existing;
        }

        $existing->update($attrs);

        return $existing;
    }

    /**
     * Check whether the article has any active (pending or in-progress)
     * reviewers, used as a guard for review_type changes and blinded
     * PDF deletion.
     *
     * SPECIFICATION: SPEC-05/BR-1, SPEC-05/BR-3
     */
    public function hasActiveReviewers(): bool
    {
        return $this->reviews()
            ->whereIn('status', [ReviewStatus::Pending, ReviewStatus::InProgress])
            ->exists();
    }

    /**
     * Change the review anonymity model for this article.
     *
     * SPECIFICATION: SPEC-05/AC-1, SPEC-05/BR-1
     */
    public function setReviewType(ReviewType $type): void
    {
        DB::transaction(function () use ($type) {
            $lockedArticle = static::lockForUpdate()->findOrFail($this->id);

            if ($lockedArticle->hasActiveReviewers()) {
                throw new ReviewTypeChangeForbiddenException;
            }

            $oldType = $lockedArticle->review_type;

            $lockedArticle->update(['review_type' => $type]);

            OutboxEvent::log('review_type.changed', $lockedArticle, [
                'old' => $oldType->value,
                'new' => $type->value,
            ]);
        });
    }

    public function isDraft(): bool
    {
        return $this->status === ArticleStatus::Draft;
    }

    public function isSubmitted(): bool
    {
        return $this->status === ArticleStatus::Submitted;
    }

    public function isInReview(): bool
    {
        return $this->status === ArticleStatus::InReview;
    }

    public function isAccepted(): bool
    {
        return $this->status === ArticleStatus::Accepted;
    }

    public function isRevision(): bool
    {
        return $this->status === ArticleStatus::Revision;
    }

    public function isRejected(): bool
    {
        return $this->status === ArticleStatus::Rejected;
    }

    public function isCopyediting(): bool
    {
        return $this->status === ArticleStatus::Copyediting;
    }

    public function isProduction(): bool
    {
        return $this->status === ArticleStatus::Production;
    }

    public function isAwaitingApproval(): bool
    {
        return $this->status === ArticleStatus::AwaitingApproval;
    }

    public function isApproved(): bool
    {
        return $this->status === ArticleStatus::Approved;
    }

    public function isPublished(): bool
    {
        return $this->status === ArticleStatus::Published;
    }

    public function isWithdrawn(): bool
    {
        return $this->status === ArticleStatus::Withdrawn;
    }

    public function isRetracted(): bool
    {
        return $this->status === ArticleStatus::Retracted;
    }

    /**
     * Whether the article is open for reviewer assignment.
     */
    public function isReviewable(): bool
    {
        return $this->isSubmitted() || $this->isInReview();
    }

    /**
     * Whether the article can be edited by its submitter.
     */
    public function isEditable(): bool
    {
        return $this->isDraft() || $this->isRevision();
    }

    /**
     * Whether the article can be withdrawn by the author or editor.
     */
    public function isWithdrawable(): bool
    {
        return $this->status->canTransitionTo(ArticleStatus::Withdrawn);
    }

    /**
     * Whether the article can be retracted (published articles).
     */
    public function isRetractable(): bool
    {
        return $this->status->canTransitionTo(ArticleStatus::Retracted);
    }

    /**
     * Whether the editor can make a decision — in review with at least one
     * completed review in the current round.
     */
    public function canBeDecided(): bool
    {
        return $this->isInReview()
            && $this->currentRoundCompletedReviews()->exists();
    }

    /**
     * Completed reviews of the current review round only.
     *
     * SPECIFICATION: SPEC-25/BR-1
     */
    public function currentRoundCompletedReviews(): HasMany
    {
        return $this->completedReviews()->where('round', $this->current_round);
    }

    /**
     * Whether the user is the submitter or a credited coauthor of
     * this article — the conflict-of-interest boundary for editorial
     * and reviewer assignments.
     */
    public function isAuthoredBy(User $user): bool
    {
        if ($this->submitted_by === $user->id) {
            return true;
        }

        return $this->authors()->where('user_id', $user->id)->exists();
    }

    /**
     * Days since the last recorded activity — the "no movement"
     * signal for pipeline monitoring.
     */
    public function daysInStatus(): int
    {
        return $this->updated_at->diffInDays(now());
    }

    /**
     * PURPOSE: Visual lifecycle stepper for the article page — steps
     * from submission to publication with per-step state and date.
     *
     * SPECIFICATION: Empty for drafts and withdrawn articles; rejected
     * articles terminate the stepper at the decision step. The `current`
     * state marks the article's active pipeline position — for statuses
     * awaiting a future milestone (e.g. Approved) it denotes the next
     * step, which carries no date until it happens. Articles under
     * revision show the decision step as `done` (the decision is already
     * made) with the later steps pending.
     *
     * Each step carries presentation-ready values (dotClass,
     * connectorClass, titleClass, icon) so views render them as is.
     *
     * @return array<int, array{key: string, label: string, state: string, date: Carbon|null, dotClass: string, connectorClass: string, titleClass: string, icon: string|null}>
     */
    public function workflowSteps(): array
    {
        $progress = match ($this->status) {
            ArticleStatus::Submitted => 1,
            ArticleStatus::InReview => 2,
            ArticleStatus::Revision, ArticleStatus::Rejected => 3,
            ArticleStatus::Accepted, ArticleStatus::Copyediting => 4,
            ArticleStatus::Production => 5,
            ArticleStatus::AwaitingApproval => 6,
            ArticleStatus::Approved => 7,
            ArticleStatus::Published, ArticleStatus::Retracted => 8,
            default => 0,
        };

        if ($progress === 0) {
            return [];
        }

        $steps = [
            ['key' => 'submitted', 'label' => __('dashboard.timeline.submitted'), 'date' => $this->submitted_at],
            ['key' => 'review', 'label' => __('dashboard.timeline.review'), 'date' => $this->reviewStartedAt()],
            ['key' => 'decision', 'label' => __('dashboard.timeline.decision'), 'date' => $this->decided_at],
            ['key' => 'copyediting', 'label' => __('dashboard.timeline.copyediting'), 'date' => $this->copyedited_at],
            ['key' => 'production', 'label' => __('dashboard.timeline.production'), 'date' => $this->production_at],
            ['key' => 'galley_approval', 'label' => __('dashboard.timeline.galley_approval'), 'date' => $this->galley_approved_at ?? $this->galley_sent_at],
            ['key' => 'published', 'label' => __('dashboard.timeline.published'), 'date' => $this->published_at],
        ];

        return collect($steps)->map(function (array $step, int $index) use ($progress) {
            $number = $index + 1;

            if ($this->status === ArticleStatus::Rejected && $number === 3) {
                $step['label'] = __('dashboard.timeline.decision_rejected');
                $step['state'] = 'rejected';
            } elseif ($this->status === ArticleStatus::Revision && $number === 3) {
                $step['state'] = 'done';
            } elseif ($number < $progress) {
                $step['state'] = 'done';
            } elseif ($number === $progress) {
                $step['state'] = 'current';
            } elseif ($this->status === ArticleStatus::Rejected) {
                $step['state'] = 'cancelled';
            } else {
                $step['state'] = 'pending';
            }

            $step['dotClass'] = match ($step['state']) {
                'done' => 'bg-green-500 text-white',
                'current' => 'bg-blue-600 text-white ring-4 ring-blue-100',
                'rejected' => 'bg-red-500 text-white',
                'cancelled' => 'bg-gray-100 text-gray-300',
                default => 'bg-gray-200 text-gray-400',
            };

            $step['connectorClass'] = in_array($step['state'], ['done', 'rejected'], true)
                ? 'bg-green-400'
                : 'bg-gray-200';

            $step['titleClass'] = match ($step['state']) {
                'current' => 'text-gray-900',
                'cancelled' => 'text-gray-300',
                default => 'text-gray-500',
            };

            $step['icon'] = match ($step['state']) {
                'done' => 'check',
                'rejected' => 'cross',
                default => null,
            };

            return $step;
        })->all();
    }

    /**
     * PURPOSE: Production readiness checklist for the editorial article
     * page — which pipeline prerequisites are already satisfied. Each
     * item carries a presentation-ready `rowClass` so views render it
     * as is.
     *
     * @return array<int, array{label: string, done: bool, skipped: bool, rowClass: string}>
     */
    public function productionChecklist(): array
    {
        $completedReviewsCount = $this->completedReviews()->count();
        $doubleBlind = $this->review_type === ReviewType::DoubleBlind;

        $items = [
            [
                'label' => __('dashboard.checklist.blinded_pdf'),
                'done' => $doubleBlind ? (bool) $this->blinded_pdf_path : true,
                'skipped' => ! $doubleBlind,
            ],
            [
                'label' => __('dashboard.checklist.reviews', ['count' => $completedReviewsCount]),
                'done' => $completedReviewsCount > 0,
                'skipped' => false,
            ],
            [
                'label' => __('dashboard.checklist.decision'),
                'done' => $this->decided_at !== null,
                'skipped' => false,
            ],
            [
                'label' => __('dashboard.checklist.copyedited_file'),
                'done' => $this->copyedited_file_path !== null,
                'skipped' => false,
            ],
            [
                'label' => __('dashboard.checklist.galley'),
                'done' => $this->galley_pdf_path !== null,
                'skipped' => false,
            ],
            [
                'label' => __('dashboard.checklist.doi'),
                'done' => $this->doi !== null,
                'skipped' => false,
            ],
        ];

        return array_map(fn (array $item) => $item + [
            'rowClass' => $item['skipped'] ? 'text-gray-300' : ($item['done'] ? 'text-gray-700' : 'text-gray-400'),
        ], $items);
    }

    private function reviewStartedAt(): ?Carbon
    {
        $assignedAt = $this->reviews()->min('assigned_at');

        return $assignedAt === null ? null : Carbon::parse($assignedAt);
    }

    /**
     * Whether the review type can be changed (no active reviewers).
     */
    public function canChangeReviewType(): bool
    {
        return ! $this->hasActiveReviewers();
    }

    /**
     * Whether the blinded PDF is required but missing.
     */
    public function needsBlindedPdf(): bool
    {
        return $this->review_type === ReviewType::DoubleBlind && ! $this->blinded_pdf_path;
    }

    public function isDoubleBlind(): bool
    {
        return $this->review_type === ReviewType::DoubleBlind;
    }

    /**
     * PURPOSE: Detect the institution identifier type (doi/isni/ror)
     * for JATS `<institution-id>` output from a funder_identifier URL.
     *
     * SPECIFICATION: SPEC-17/BR-3
     */
    public static function funderIdentifierType(?string $identifier): ?string
    {
        if (empty($identifier)) {
            return null;
        }

        return match (true) {
            str_contains($identifier, 'doi.org') || str_contains($identifier, 'dx.doi.org') => 'doi',
            str_contains($identifier, 'ror.org') => 'ror',
            str_contains($identifier, 'isni.org') => 'isni',
            default => null,
        };
    }

    /**
     * Users who should receive status-change notifications:
     * the submitter plus any coauthors linked to a User account.
     *
     * SPECIFICATION: SPEC-12/BR-2
     */
    public function notifiableUsers(): Collection
    {
        $userIds = collect([$this->submitted_by]);

        $coauthorUserIds = $this->authors()
            ->whereNotNull('user_id')
            ->where('user_id', '!=', $this->submitted_by)
            ->pluck('user_id');

        if ($coauthorUserIds->isNotEmpty()) {
            $userIds = $userIds->merge($coauthorUserIds);
        }

        return User::whereIn('id', $userIds->unique())->get();
    }

    /**
     * Whether a notification of the same type was already sent
     * for this article within the last hour (BR-3 throttling).
     */
    public function wasRecentlyNotified(string $event): bool
    {
        return Cache::has("notification_throttle:{$event}:{$this->id}");
    }

    /**
     * Mark a notification event as sent for this article
     * to prevent duplicates within the cooldown window.
     */
    public function markNotified(string $event): void
    {
        Cache::put("notification_throttle:{$event}:{$this->id}", true, now()->addHour());
    }

    /**
     * Completed reviews, available to the submitter after the editor's decision.
     */
    public function completedReviews()
    {
        return $this->reviews()->where('status', ReviewStatus::Completed);
    }

    /**
     * Sync references from an array of text lines. Deletes existing
     * references, creates new rows with auto-extracted DOI, and
     * recalculates citation counts.
     *
     * SPECIFICATION: SPEC-15/BR-2, SPEC-15/BR-4, SPEC-15/BR-5
     */
    public function syncReferences(array $lines): void
    {
        DB::transaction(function () use ($lines) {
            $this->references()->delete();

            $order = 1;
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $this->references()->create([
                    'raw' => $line,
                    'order' => $order++,
                ]);
            }

            $this->load('references');
            $this->countCitations();
        });
    }

    /**
     * Count how many times each reference is cited in the article body.
     * Finds bracket citations ([1], [1,2], [1-3]) and maps resolved
     * indices to references by order column.
     *
     * SPECIFICATION: SPEC-15/AC-4
     */
    public function countCitations(): void
    {
        if (! $this->body || ! $this->relationLoaded('references')) {
            return;
        }

        $text = strip_tags($this->body);

        $counts = [];
        foreach ($this->references as $ref) {
            $counts[$ref->order] = 0;
        }

        preg_match_all('/\[(\s*\d[\d,\-\s]*)\]/', $text, $matches);

        foreach ($matches[1] as $group) {
            foreach ($this->parseCitationGroup($group) as $id) {
                if (isset($counts[$id])) {
                    $counts[$id]++;
                }
            }
        }

        $rows = [];
        foreach ($this->references as $ref) {
            $new = $counts[$ref->order] ?? 0;
            if ($ref->cited_count !== $new) {
                $rows[] = [
                    'id' => $ref->id,
                    'article_id' => $ref->article_id,
                    'raw' => $ref->raw,
                    'doi' => $ref->doi,
                    'order' => $ref->order,
                    'cited_count' => $new,
                    'updated_at' => now(),
                    'created_at' => $ref->created_at,
                ];
            }
        }

        if ($rows) {
            DB::transaction(fn () => Reference::upsert($rows, ['id'], ['cited_count', 'updated_at']));
        }
    }

    /**
     * Parse a citation group like "1,2,5-7" or "1, 2, 3"
     * into an array of individual reference indices.
     */
    private function parseCitationGroup(string $group): array
    {
        $ids = [];
        $parts = explode(',', $group);

        foreach ($parts as $part) {
            $part = trim($part);
            if (str_contains($part, '-')) {
                [$start, $end] = explode('-', $part);
                $start = trim($start);
                $end = trim($end);
                if (is_numeric($start) && is_numeric($end)) {
                    for ($i = (int) $start; $i <= (int) $end; $i++) {
                        $ids[] = $i;
                    }
                }
            } elseif (is_numeric($part)) {
                $ids[] = (int) $part;
            }
        }

        return array_unique($ids);
    }
}
