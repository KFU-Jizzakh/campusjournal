<?php

namespace App\Models;

use App\Enums\ArticleFileLicense;
use App\Enums\ArticleStatus;
use App\Enums\ReviewStatus;
use App\Enums\ReviewType;
use App\Exceptions\AssignEditorFailedException;
use App\Exceptions\AssignReviewerFailedException;
use App\Exceptions\CannotReviewArticleException;
use App\Exceptions\CopyeditedFileNotUploadedException;
use App\Exceptions\DeleteCopyeditedFileFailedException;
use App\Exceptions\DuplicateReviewerException;
use App\Exceptions\GalleyApprovalRequiredException;
use App\Exceptions\GalleyNotAwaitingApprovalException;
use App\Exceptions\GalleyNotProductionException;
use App\Exceptions\GalleyPdfNotUploadedException;
use App\Exceptions\InvalidTransitionException;
use App\Exceptions\MissingBlindedPdfException;
use App\Exceptions\MissingCompletedReviewsException;
use App\Exceptions\NotSectionEditorException;
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
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * PURPOSE: Core editorial workflow entity representing a submitted
 * manuscript, managing the full lifecycle from draft through peer
 * review, decision, copyediting, production, and publication.
 *
 * SPECIFICATION: SPEC-01/AC-1, SPEC-01/AC-7, SPEC-01/BR-1, SPEC-01/BR-2, SPEC-01/BR-3, SPEC-01/BR-4, SPEC-01/BR-5, SPEC-01/BR-6, SPEC-01/BR-7, SPEC-02/BR-6, SPEC-04/BR-1, SPEC-04/BR-5, SPEC-05/BR-1, SPEC-05/BR-2, SPEC-05/BR-3, SPEC-05/BR-4, SPEC-13/BR-1, SPEC-13/BR-2, SPEC-13/BR-3, SPEC-15/AC-2, SPEC-15/AC-4, SPEC-15/BR-1, SPEC-15/BR-2, SPEC-15/BR-3, SPEC-15/BR-4, SPEC-15/BR-5
 */
#[Fillable(['title', 'abstract_ru', 'abstract_en', 'body', 'doi', 'keywords', 'pages', 'first_page', 'last_page', 'views_count', 'pdf_path', 'blinded_pdf_path', 'blinded_at', 'blinded_by', 'status', 'review_type', 'issue_id', 'category_id', 'submitted_by', 'submitted_at', 'published_at', 'doi_registered_at', 'editor_id', 'decision', 'decision_comments', 'decided_at', 'decided_by', 'copyedited_at', 'copyedited_by', 'copyedited_file_path', 'copyedited_file_uploaded_at', 'copyedited_file_uploaded_by', 'production_at', 'production_by', 'galley_pdf_path', 'galley_uploaded_at', 'galley_uploaded_by', 'galley_sent_at', 'galley_sent_by', 'galley_approved_at', 'galley_approved_by'])]
class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'review_type' => ReviewType::class,
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
            'keywords' => 'array',
        ];
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
            ->withPivot('order')
            ->orderByPivot('order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ArticleFile::class)->orderBy('created_at');
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
        return $query->where('status', ArticleStatus::Published);
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
            $article = static::create([
                ...$data,
                'status' => ArticleStatus::Submitted,
                'submitted_by' => $submitter->id,
                'submitted_at' => now(),
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
        DB::transaction(function () use ($data, &$oldPath) {
            $lockedArticle = static::lockForUpdate()->findOrFail($this->id);

            $oldPath = $lockedArticle->copyedited_file_path;

            $lockedArticle->update([
                ...$data,
                'submitted_at' => now(),
                'decision' => null,
                'decision_comments' => null,
                'decided_at' => null,
                'decided_by' => null,
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

            // Guard: double-blind requires an anonymised PDF before reviewers can be assigned.
            // Prevents author identity exposure through the manuscript file. SPECIFICATION: SPEC-05/BR-2
            if ($lockedArticle->review_type === ReviewType::DoubleBlind && ! $lockedArticle->blinded_pdf_path) {
                throw new MissingBlindedPdfException;
            }

            // Check for existing non-declined review (pre-query check for better error message)
            if ($lockedArticle->reviews()->where('reviewer_id', $reviewer->id)->where('status', '!=', ReviewStatus::Declined)->exists()) {
                throw new DuplicateReviewerException;
            }

            $responseDays = (int) Setting::get('review_response_days', '7');
            $deadlineDays = (int) Setting::get('review_deadline_days', '30');

            try {
                $review = Review::create([
                    'article_id' => $lockedArticle->id,
                    'reviewer_id' => $reviewer->id,
                    'assigned_by' => $assignedBy->id,
                    'status' => ReviewStatus::Pending,
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

            if ($lockedArticle->reviews()->where('status', ReviewStatus::Completed)->doesntExist()) {
                throw new MissingCompletedReviewsException('Необходимо дождаться хотя бы одной завершённой рецензии.');
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
     * and transition to Published status.
     * Requires prior author galley approval (BR-1).
     *
     * SPECIFICATION: SPEC-04/AC-6, SPEC-04/BR-6, SPEC-04/BR-7, SPEC-13/BR-1
     */
    public function publish(Issue $issue): void
    {
        if ($this->status !== ArticleStatus::Approved) {
            throw new GalleyApprovalRequiredException;
        }

        DB::transaction(function () use ($issue) {
            $lockedArticle = static::lockForUpdate()->findOrFail($this->id);
            $lockedArticle->transitionTo(ArticleStatus::Published);

            $lockedArticle->fill([
                'issue_id' => $issue->id,
                'published_at' => now(),
            ])->save();

            OutboxEvent::log('article.published', $lockedArticle, [
                'issue_id' => $issue->id,
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
     * Sync primary author (from submitter) and coauthors via pivot table.
     * Cleans up orphaned coauthors no longer attached to any article.
     *
     * SPECIFICATION: SPEC-01/AC-1, SPEC-01/BR-4
     */
    public function syncAuthors(User $submitter, array $authorData, array $coauthorsData = []): void
    {
        $previousCoauthorIds = $this->authors()
            ->whereNull('user_id')
            ->pluck('authors.id');

        $primaryAuthor = Author::updateOrCreate(
            ['user_id' => $submitter->id],
            [
                'email' => $submitter->email,
                'full_name' => $authorData['full_name'],
                'degree' => $authorData['degree'] ?? null,
                'position' => $authorData['position'] ?? null,
                'organization' => $authorData['organization'] ?? null,
                'orcid' => $authorData['orcid'] ?? null,
            ]
        );

        $authors = [$primaryAuthor->id => ['order' => 1]];

        foreach ($coauthorsData as $index => $coauthorData) {
            $attrs = [
                'full_name' => $coauthorData['full_name'],
                'degree' => $coauthorData['degree'] ?? null,
                'position' => $coauthorData['position'] ?? null,
                'organization' => $coauthorData['organization'] ?? null,
                'orcid' => $coauthorData['orcid'] ?? null,
            ];

            if (! empty($coauthorData['orcid'])) {
                $coauthor = Author::updateOrCreate(['orcid' => $coauthorData['orcid']], $attrs);
            } else {
                $coauthor = Author::create($attrs);
            }

            $authors[$coauthor->id] = ['order' => $index + 2];
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
     * Whether the editor can make a decision — in review with completed reviews.
     */
    public function canBeDecided(): bool
    {
        return $this->isInReview()
            && $this->reviews()->where('status', ReviewStatus::Completed)->exists();
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
