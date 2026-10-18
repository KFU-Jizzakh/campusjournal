<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Country;
use App\Enums\DiscussionScope;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\CopyrightAgreement;
use App\Models\User;
use App\Notifications\AuthorSubmissionReceived;
use App\Notifications\InvitationNotification;
use App\Policies\DiscussionPolicy;
use App\Rules\ClaimableOrcid;
use App\Rules\Orcid;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * PURPOSE: Handles article submission creation, editing of
 * drafts, and resubmission after revision, managing PDF
 * upload and author metadata synchronisation.
 *
 * SPECIFICATION: SPEC-01/AC-1, SPEC-01/AC-2, SPEC-01/AC-6, SPEC-01/AC-7
 */
class SubmissionController extends Controller
{
    /**
     * Session key holding the manuscript PDF parked by the
     * duplicate-warning redirect: file inputs cannot be repopulated
     * by withInput(), so the first attempt's upload is reused when
     * the author confirms the submission.
     */
    private const PENDING_PDF_KEY = 'submission.pending_pdf';

    public function create(Request $request)
    {
        $categories = Category::orderBy('sort_order')->get();
        $agreement = CopyrightAgreement::current();
        $countries = Country::cases();
        $existingAuthor = Author::where('user_id', $request->user()->id)->first();

        return view('dashboard.articles.create', compact('categories', 'agreement', 'countries', 'existingAuthor'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'abstract_ru' => 'required|string',
            'abstract_en' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'keywords' => 'nullable|string|max:1000',
            // Required on a first attempt, but nullable when confirming
            // the duplicate warning: the warning redirect cannot
            // repopulate a file input, so the confirming request may
            // legitimately carry no file — see resolveSubmissionPdf(),
            // which still rejects a missing PDF.
            'pdf_file' => [
                Rule::requiredIf(! $request->boolean('duplicate_acknowledged')),
                'file',
                'mimetypes:application/pdf',
                'max:51200',
            ],
            ...$this->authorRules($request->user()),
            'references' => 'nullable|string|max:10000',
            ...$this->fundingRules(),
            'agreement_accepted' => 'accepted',
        ]);

        $this->validateOrcidDistinct($validated);

        $duplicates = $this->findDuplicateTitles($validated['title']);

        if ($duplicates->isNotEmpty() && ! $request->boolean('duplicate_acknowledged')) {
            if ($request->file('pdf_file')) {
                $this->rememberPendingSubmissionPdf($request, $validated['title']);
            }

            return back()
                ->withInput()
                ->with('warning', __('dashboard.duplicate_warning'))
                ->with('duplicates', $duplicates->all());
        }

        $pdfPath = $this->resolveSubmissionPdf($request, $validated['title']);

        $keywords = ! empty($validated['keywords'])
            ? array_map('trim', explode(',', $validated['keywords']))
            : null;

        $agreement = CopyrightAgreement::current();

        $article = DB::transaction(function () use ($request, $validated, $keywords, $pdfPath, $agreement) {
            $article = Article::submit($request->user(), [
                'title' => $validated['title'],
                'abstract_ru' => $validated['abstract_ru'],
                'abstract_en' => $validated['abstract_en'] ?? null,
                'keywords' => $keywords,
                'funding' => $validated['funding'] ?? null,
                'category_id' => $validated['category_id'],
                'pdf_path' => $pdfPath,
            ]);

            $article->syncAuthors(
                $request->user(),
                [
                    'full_name' => $validated['author_name'],
                    'degree' => $validated['author_degree'] ?? null,
                    'position' => $validated['author_position'] ?? null,
                    'organization' => $validated['author_organization'] ?? null,
                    'email' => $validated['author_email'],
                    'phone' => $validated['author_phone'],
                    'country' => $validated['author_country'],
                    'city' => $validated['author_city'],
                    'website' => $validated['author_website'] ?? null,
                    'orcid' => $validated['author_orcid'] ?? null,
                ],
                $validated['coauthors'] ?? [],
            );

            $lines = preg_split('/\r\n|\r|\n/', trim($validated['references'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
            $article->syncReferences($lines);

            if ($agreement) {
                $article->saveAgreement($agreement, $request->user(), $request->ip());
            }

            $this->sendCoauthorInvitations($article);

            $article->notifiableUsers()
                ->reject(fn ($user) => $user->id === $article->submitted_by)
                ->each(fn ($user) => $user->notify(new AuthorSubmissionReceived($article)));

            return $article;
        });

        return redirect()->route('submissions.show', $article)
            ->with('success', 'Рукопись успешно подана на рассмотрение.');
    }

    public function show(Request $request, Article $article)
    {
        $this->authorize('view', $article);

        $article->load('authors', 'category', 'reviews', 'responseLetters.uploader', 'references', 'discussions.article', 'discussions.user.profile', 'discussions.replies.user.profile', 'latestAgreement.agreement');

        $user = $request->user();

        $article->discussions
            ->filter(fn ($d) => $d->isVisibleTo($user, $article))
            ->each(function ($d) use ($user) {
                $d->wasUnread = $d->isUnreadBy($user);
                $d->readBy($user);
            });

        // Threads and form visibility are precomputed so the view stays
        // declarative: coauthors can read threads but cannot start or
        // reply to them — DiscussionPolicy::create allows the submitter
        // (and editors) only. The policy is invoked directly (like
        // DiscussionController::authorizeCreate does) because Gate would
        // resolve ArticlePolicy from the article argument.
        $canDiscuss = app(DiscussionPolicy::class)->create($user, $article, DiscussionScope::Article);
        $visibleDiscussions = $article->discussions
            ->where('parent_id', null)
            ->filter(fn ($d) => $d->isVisibleTo($user, $article))
            ->sortByDesc('created_at')
            ->each(fn ($d) => $d->canReply = $canDiscuss && ! $d->is_resolved);

        $timelineSteps = $article->workflowSteps();

        return view('dashboard.articles.show', compact('article', 'timelineSteps', 'visibleDiscussions', 'canDiscuss'));
    }

    public function edit(Request $request, Article $article)
    {
        $this->authorize('update', $article);

        $article->load(['authors', 'references']);
        $categories = Category::orderBy('sort_order')->get();
        $agreement = $article->isRevision() ? CopyrightAgreement::current() : null;
        $countries = Country::cases();
        $primaryAuthor = $article->authors->first();
        // Decision and reviews of the round being revised — shown to the
        // author above the response-letter field while resubmitting.
        $roundReviews = $article->isRevision()
            ? $article->currentRoundCompletedReviews()->orderBy('completed_at')->get()
            : collect();
        $decisionComments = $article->isRevision() ? $article->decision_comments : null;
        $coauthorsData = old('coauthors', $article->authors->skip(1)->map(fn (Author $author) => [
            'full_name' => $author->full_name,
            'degree' => $author->degree,
            'position' => $author->position,
            'organization' => $author->organization,
            'email' => $author->pivot?->email,
            'phone' => $author->pivot?->phone,
            'country' => $author->pivot?->country,
            'city' => $author->pivot?->city,
            'website' => $author->pivot?->website,
            'orcid' => $author->orcid,
        ])->values()->toArray());

        return view('dashboard.articles.edit', compact('article', 'categories', 'agreement', 'countries', 'primaryAuthor', 'coauthorsData', 'roundReviews', 'decisionComments'));
    }

    public function update(Request $request, Article $article)
    {
        $this->authorize('update', $article);

        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'abstract_ru' => 'required|string',
            'abstract_en' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'keywords' => 'nullable|string|max:1000',
            'pdf_file' => 'nullable|file|mimetypes:application/pdf|max:51200',
            ...$this->authorRules($request->user()),
            'references' => 'nullable|string|max:10000',
            ...$this->fundingRules(),
        ]);

        if ($article->isRevision()) {
            $validated = array_merge($validated, $request->validate([
                'agreement_accepted' => 'accepted',
                'response_letter' => 'required|string',
                'response_letter_file' => 'nullable|file|mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document|max:51200',
            ]));
        }

        $this->validateOrcidDistinct($validated);

        $keywords = ! empty($validated['keywords'])
            ? array_map('trim', explode(',', $validated['keywords']))
            : null;

        $data = [
            'title' => $validated['title'],
            'abstract_ru' => $validated['abstract_ru'],
            'abstract_en' => $validated['abstract_en'] ?? null,
            'keywords' => $keywords,
            'funding' => $validated['funding'] ?? null,
            'category_id' => $validated['category_id'],
        ];

        if ($request->hasFile('pdf_file')) {
            if ($article->pdf_path) {
                Storage::disk($article->pdf_disk)->delete($article->pdf_path);
            }
            $data['pdf_path'] = $request->file('pdf_file')->store('submissions', 'local');
        }

        $isRevision = $article->isRevision();
        $agreement = $isRevision ? CopyrightAgreement::current() : null;

        $responseLetterFilePath = $request->hasFile('response_letter_file')
            ? $request->file('response_letter_file')->store('response-letters', 'local')
            : null;

        DB::transaction(function () use ($request, $article, $validated, $data, $isRevision, $agreement, $responseLetterFilePath) {
            $article->updateOrRevise($data);

            // After revise() the article has moved to the new round — the
            // response letter belongs to that new round.
            if ($isRevision) {
                $article->responseLetters()->create([
                    'round' => $article->current_round,
                    'body' => $validated['response_letter'],
                    'file_path' => $responseLetterFilePath,
                    'uploaded_by' => $request->user()->id,
                ]);
            }

            $article->syncAuthors(
                $request->user(),
                [
                    'full_name' => $validated['author_name'],
                    'degree' => $validated['author_degree'] ?? null,
                    'position' => $validated['author_position'] ?? null,
                    'organization' => $validated['author_organization'] ?? null,
                    'email' => $validated['author_email'],
                    'phone' => $validated['author_phone'],
                    'country' => $validated['author_country'],
                    'city' => $validated['author_city'],
                    'website' => $validated['author_website'] ?? null,
                    'orcid' => $validated['author_orcid'] ?? null,
                ],
                $validated['coauthors'] ?? [],
            );

            $lines = preg_split('/\r\n|\r|\n/', trim($validated['references'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
            $article->syncReferences($lines);

            $this->sendCoauthorInvitations($article);

            if ($isRevision && $agreement) {
                $article->saveAgreement($agreement, $request->user(), $request->ip());
            }
        });

        return redirect()->route('submissions.show', $article)
            ->with('success', 'Рукопись обновлена.');
    }

    /**
     * Author approves the galley proof, unblocking publication.
     *
     * SPECIFICATION: SPEC-13/AC-4
     */
    public function approveGalley(Request $request, Article $article)
    {
        $this->authorize('approveGalley', $article);

        try {
            $article->approveGalley($request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('article.galley_approved_success'));
    }

    /**
     * Author requests revisions to the galley proof.
     *
     * SPECIFICATION: SPEC-13/AC-5
     */
    public function requestGalleyRevision(Request $request, Article $article)
    {
        $this->authorize('requestGalleyRevision', $article);

        $validated = $request->validate([
            'comment' => 'required|string|max:5000',
        ]);

        try {
            $article->requestGalleyRevision($request->user(), $validated['comment']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('article.galley_revision_requested_success'));
    }

    /**
     * Author withdraws their article before publication.
     *
     * SPECIFICATION: SPEC-16/AC-1
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

        return redirect()->route('dashboard')->with('success', 'Статья отозвана.');
    }

    private function authorRules(User $user): array
    {
        return [
            'author_name' => 'required|string|max:255',
            'author_degree' => 'nullable|string|max:255',
            'author_position' => 'nullable|string|max:255',
            'author_organization' => 'nullable|string|max:255',
            'author_email' => 'required|email|max:255',
            'author_phone' => ['required', 'string', 'max:25', 'regex:/^[+\d][\d\s()\-]{5,24}$/'],
            'author_country' => ['required', Rule::enum(Country::class)],
            'author_city' => 'required|string|max:255',
            'author_website' => 'nullable|url|max:255',
            'author_orcid' => ['nullable', 'string', 'max:50', new Orcid, new ClaimableOrcid($user)],
            'coauthors' => 'nullable|array',
            'coauthors.*.full_name' => 'required|string|max:255',
            'coauthors.*.degree' => 'nullable|string|max:255',
            'coauthors.*.position' => 'nullable|string|max:255',
            'coauthors.*.organization' => 'nullable|string|max:255',
            'coauthors.*.email' => 'required|email|max:255',
            'coauthors.*.phone' => ['required', 'string', 'max:25', 'regex:/^[+\d][\d\s()\-]{5,24}$/'],
            'coauthors.*.country' => ['required', Rule::enum(Country::class)],
            'coauthors.*.city' => 'required|string|max:255',
            'coauthors.*.website' => 'nullable|url|max:255',
            'coauthors.*.orcid' => ['nullable', 'string', 'max:50', new Orcid],
        ];
    }

    private function fundingRules(): array
    {
        return [
            'funding' => 'nullable|array|max:20',
            'funding.*.funder_name' => 'required|string|max:500',
            'funding.*.funder_identifier' => 'nullable|string|max:500',
            'funding.*.award_number' => 'nullable|string|max:255',
        ];
    }

    /**
     * PURPOSE: Invite verified users whose email was listed for an
     * unlinked coauthor row to claim the author record. Throttled to
     * one invitation per 24 hours so resubmits do not spam.
     */
    private function sendCoauthorInvitations(Article $article): void
    {
        $candidates = $article->authors()
            ->whereNull('authors.user_id')
            ->whereNotNull('article_author.email')
            ->where(function ($query) {
                $query->whereNull('authors.invitation_sent_at')
                    ->orWhere('authors.invitation_sent_at', '<', now()->subDay());
            })
            ->get();

        foreach ($candidates as $author) {
            $user = User::query()
                ->whereRaw('LOWER(email) = LOWER(?)', [$author->pivot->email])
                ->whereNotNull('email_verified_at')
                ->first();

            if (! $user) {
                continue;
            }

            // A submitter listing their own email as a coauthor invites
            // themselves: their own listing proves no ownership, so no
            // signed link is issued (claimFor would reject it anyway).
            if ((int) $user->id === (int) $article->submitted_by) {
                continue;
            }

            $user->notify(new InvitationNotification($article, $author));

            $author->update(['invitation_sent_at' => now()]);
        }
    }

    private function validateOrcidDistinct(array $validated): void
    {
        $orcids = array_filter([
            $validated['author_orcid'] ?? null,
            ...array_column($validated['coauthors'] ?? [], 'orcid'),
        ]);

        if (count($orcids) !== count(array_unique($orcids))) {
            throw ValidationException::withMessages([
                'coauthors' => 'У каждого автора должен быть уникальный ORCID.',
            ]);
        }
    }

    /**
     * PURPOSE: Non-blocking duplicate check — titles of existing
     * non-draft articles similar to the submitted one (normalized
     * exact match, substring, or high similarity). Shorter titles are
     * ignored to avoid false positives.
     *
     * @return Collection<int, string>
     */
    private function findDuplicateTitles(string $title)
    {
        $normalized = $this->normalizeTitle($title);

        if (mb_strlen($normalized) < 10) {
            return collect();
        }

        return Article::submitted()
            ->select('id', 'title')
            ->get()
            ->filter(function (Article $article) use ($normalized) {
                $candidate = $this->normalizeTitle($article->title);

                if ($candidate === '' || $candidate === $normalized) {
                    return $candidate === $normalized;
                }

                similar_text($normalized, $candidate, $percent);

                return str_contains($candidate, $normalized)
                    || str_contains($normalized, $candidate)
                    || $percent >= 88;
            })
            ->pluck('title')
            ->values();
    }

    private function normalizeTitle(string $title): string
    {
        $lower = mb_strtolower($title);

        return trim((string) preg_replace('/\s+/u', ' ', (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $lower)));
    }

    /**
     * Park the uploaded manuscript PDF in the session so the request
     * confirming the duplicate warning can reuse it — file inputs are
     * not repopulated by withInput(). The title guard prevents a
     * parked upload from an abandoned attempt being attached to a
     * different manuscript.
     */
    private function rememberPendingSubmissionPdf(Request $request, string $title): void
    {
        $previous = $request->session()->get(self::PENDING_PDF_KEY);

        if (is_array($previous) && ! empty($previous['path'])) {
            Storage::disk('local')->delete($previous['path']);
        }

        $request->session()->put(self::PENDING_PDF_KEY, [
            'title' => $title,
            'path' => $request->file('pdf_file')->store('submissions-pending', 'local'),
        ]);
    }

    /**
     * Resolve the manuscript PDF for the submission being stored: a
     * freshly uploaded file wins; otherwise the copy parked by the
     * duplicate-warning redirect is promoted from its pending
     * location. Throws a validation error when neither exists.
     */
    private function resolveSubmissionPdf(Request $request, string $title): string
    {
        if ($request->file('pdf_file')) {
            $this->forgetPendingSubmissionPdf($request);

            return $request->file('pdf_file')->store('submissions', 'local');
        }

        $pending = $request->session()->pull(self::PENDING_PDF_KEY, []);

        if (is_array($pending) && ($pending['title'] ?? null) === $title && ! empty($pending['path'])) {
            $path = 'submissions/'.Str::random(40).'.pdf';

            Storage::disk('local')->move($pending['path'], $path);

            return $path;
        }

        throw ValidationException::withMessages([
            'pdf_file' => __('validation.required', ['attribute' => 'pdf_file']),
        ]);
    }

    private function forgetPendingSubmissionPdf(Request $request): void
    {
        $pending = $request->session()->pull(self::PENDING_PDF_KEY, []);

        if (is_array($pending) && ! empty($pending['path'])) {
            Storage::disk('local')->delete($pending['path']);
        }
    }
}
