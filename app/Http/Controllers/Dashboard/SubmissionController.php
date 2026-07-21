<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\CopyrightAgreement;
use App\Notifications\AuthorSubmissionReceived;
use App\Rules\Orcid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
    public function create()
    {
        $categories = Category::orderBy('sort_order')->get();
        $agreement = CopyrightAgreement::current();

        return view('dashboard.articles.create', compact('categories', 'agreement'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'abstract_ru' => 'required|string',
            'abstract_en' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'keywords' => 'nullable|string|max:1000',
            'pdf_file' => 'required|file|mimetypes:application/pdf|max:51200',
            'author_name' => 'required|string|max:255',
            'author_degree' => 'nullable|string|max:255',
            'author_position' => 'nullable|string|max:255',
            'author_organization' => 'nullable|string|max:255',
            'author_orcid' => ['nullable', 'string', 'max:50', new Orcid, 'unique:authors,orcid'],
            'coauthors' => 'nullable|array',
            'coauthors.*.full_name' => 'required|string|max:255',
            'coauthors.*.degree' => 'nullable|string|max:255',
            'coauthors.*.position' => 'nullable|string|max:255',
            'coauthors.*.organization' => 'nullable|string|max:255',
            'coauthors.*.orcid' => ['nullable', 'string', 'max:50', new Orcid],
            'references' => 'nullable|string|max:10000',
            'funding' => 'nullable|array|max:20',
            'funding.*.funder_name' => 'required|string|max:500',
            'funding.*.funder_identifier' => 'nullable|string|max:500',
            'funding.*.award_number' => 'nullable|string|max:255',
            'agreement_accepted' => 'accepted',
        ]);

        $this->validateOrcidDistinct($validated);

        $pdfPath = $request->file('pdf_file')->store('submissions', 'local');

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
                    'orcid' => $validated['author_orcid'] ?? null,
                ],
                $validated['coauthors'] ?? [],
            );

            $lines = preg_split('/\r\n|\r|\n/', trim($validated['references'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
            $article->syncReferences($lines);

            if ($agreement) {
                $article->saveAgreement($agreement, $request->user(), $request->ip());
            }

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

        $article->load('authors', 'category', 'reviews', 'references', 'discussions.article', 'discussions.user.profile', 'discussions.replies.user.profile', 'latestAgreement.agreement');

        $article->discussions
            ->filter(fn ($d) => $d->isVisibleTo($request->user(), $article))
            ->each(function ($d) use ($request) {
                $d->wasUnread = $d->isUnreadBy($request->user());
                $d->readBy($request->user());
            });

        return view('dashboard.articles.show', compact('article'));
    }

    public function edit(Request $request, Article $article)
    {
        $this->authorize('update', $article);

        $article->load(['authors', 'references']);
        $categories = Category::orderBy('sort_order')->get();
        $agreement = $article->isRevision() ? CopyrightAgreement::current() : null;

        return view('dashboard.articles.edit', compact('article', 'categories', 'agreement'));
    }

    public function update(Request $request, Article $article)
    {
        $this->authorize('update', $article);

        // Get current author's ORCID to exclude from unique check
        $currentAuthor = Author::where('user_id', $request->user()->id)->first();

        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'abstract_ru' => 'required|string',
            'abstract_en' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'keywords' => 'nullable|string|max:1000',
            'pdf_file' => 'nullable|file|mimetypes:application/pdf|max:51200',
            'author_name' => 'required|string|max:255',
            'author_degree' => 'nullable|string|max:255',
            'author_position' => 'nullable|string|max:255',
            'author_organization' => 'nullable|string|max:255',
            'author_orcid' => [
                'nullable',
                'string',
                'max:50',
                new Orcid,
                Rule::unique('authors', 'orcid')->ignore($currentAuthor?->id),
            ],
            'coauthors' => 'nullable|array',
            'coauthors.*.full_name' => 'required|string|max:255',
            'coauthors.*.degree' => 'nullable|string|max:255',
            'coauthors.*.position' => 'nullable|string|max:255',
            'coauthors.*.organization' => 'nullable|string|max:255',
            'coauthors.*.orcid' => ['nullable', 'string', 'max:50', new Orcid],
            'references' => 'nullable|string|max:10000',
            'funding' => 'nullable|array|max:20',
            'funding.*.funder_name' => 'required|string|max:500',
            'funding.*.funder_identifier' => 'nullable|string|max:500',
            'funding.*.award_number' => 'nullable|string|max:255',
        ]);

        if ($article->isRevision()) {
            $request->validate([
                'agreement_accepted' => 'accepted',
            ]);
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

        DB::transaction(function () use ($request, $article, $validated, $data, $isRevision, $agreement) {
            $article->updateOrRevise($data);

            $article->syncAuthors(
                $request->user(),
                [
                    'full_name' => $validated['author_name'],
                    'degree' => $validated['author_degree'] ?? null,
                    'position' => $validated['author_position'] ?? null,
                    'organization' => $validated['author_organization'] ?? null,
                    'orcid' => $validated['author_orcid'] ?? null,
                ],
                $validated['coauthors'] ?? [],
            );

            $lines = preg_split('/\r\n|\r|\n/', trim($validated['references'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
            $article->syncReferences($lines);

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
}
