<?php

namespace App\Http\Controllers;

use App\Enums\ArticleStatus;
use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Setting;
use App\Services\Jats\JatsXmlBuilder;
use Illuminate\Support\Facades\Storage;

/**
 * PURPOSE: Serves published article listings, detail pages,
 * PDF downloads, BibTeX/RIS/JATS exports, and blinded PDF
 * access for double-blind reviewers.
 *
 * SPECIFICATION: SPEC-10/AC-1, SPEC-11/AC-4, SPEC-11/AC-5
 */
class ArticleController extends Controller
{
    public function index()
    {
        $articles = Article::published()
            ->with('authors', 'category', 'issue')
            ->when(request('category'), fn ($q, $id) => $q->where('category_id', $id))
            ->when(request('keyword'), fn ($q, $kw) => $q->whereJsonContains('keywords', $kw))
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        $categories = Category::orderBy('sort_order')->get();

        return view('articles.index', compact('articles', 'categories'));
    }

    public function show(Article $article)
    {
        if (! in_array($article->status, [ArticleStatus::Published, ArticleStatus::Retracted])) {
            abort(404);
        }

        $article->load('authors', 'category', 'issue', 'latestAgreement.agreement', 'corrections');

        $publicationLicense = $article->publicationLicense();

        $viewed = session()->get('viewed_articles', []);
        if (! in_array($article->id, $viewed)) {
            $article->increment('views_count');
            session()->push('viewed_articles', $article->id);
        }

        $authorIds = $article->authors->pluck('id');

        // Batch-load other published articles for all authors (2 queries instead of 2*N)
        $relatedArticles = Article::published()
            ->where('id', '!=', $article->id)
            ->whereHas('authors', fn ($q) => $q->whereIn('authors.id', $authorIds))
            ->with(['issue', 'authors'])
            ->orderByDesc('published_at')
            ->get();

        $authorArticles = [];
        foreach ($authorIds as $authorId) {
            $authorArticles[$authorId] = $relatedArticles
                ->filter(fn ($a) => $a->authors->contains('id', $authorId))
                ->take(5)
                ->values();
        }

        // Build author→issues map from already-loaded related articles
        $authorIssues = [];
        foreach ($authorIds as $authorId) {
            $authorIssues[$authorId] = $relatedArticles
                ->filter(fn ($a) => $a->issue_id && $a->authors->contains('id', $authorId))
                ->pluck('issue')
                ->filter()
                ->unique('id')
                ->sortByDesc('year')
                ->values();
        }

        $printIssn = Setting::get('journal_issn_print');
        $electronicIssn = Setting::get('journal_issn_electronic');

        return view('articles.show', compact('article', 'authorArticles', 'authorIssues', 'publicationLicense', 'printIssn', 'electronicIssn'));
    }

    public function exportBibtex(Article $article)
    {
        abort_unless(in_array($article->status, [ArticleStatus::Published, ArticleStatus::Retracted]), 404);
        $article->load('authors', 'issue');

        $escape = fn (string $s): string => str_replace(
            ['{', '}', '&', '%', '#', '_'],
            ['\\{', '\\}', '\\&', '\\%', '\\#', '\\_'],
            $s,
        );

        $key = 'gcru'.$article->id;
        $authors = $escape($article->authors->pluck('full_name')->implode(' and '));
        $year = $article->issue?->year ?? $article->published_at?->year ?? '';
        $abstract = $article->abstract_en ?: $article->abstract_ru ?: '';
        $keywords = is_array($article->keywords) ? $escape(implode(', ', $article->keywords)) : '';

        $fields = [];
        $fields[] = "  author    = {{$authors}}";
        $fields[] = '  title     = {'.$escape($article->title).'}';
        $fields[] = '  journal   = {'.config('app.name').'}';
        $fields[] = "  year      = {{$year}}";
        if ($article->issue) {
            $fields[] = "  volume    = {{$article->issue->volume}}";
            $fields[] = "  number    = {{$article->issue->number}}";
        }
        if ($article->doi) {
            $fields[] = "  doi       = {{$article->doi}}";
        }
        if ($abstract) {
            $fields[] = '  abstract  = {'.$escape($abstract).'}';
        }
        if ($keywords) {
            $fields[] = "  keywords  = {{$keywords}}";
        }

        $bibtex = "@article{{$key},\n".implode(",\n", $fields)."\n}";

        return response($bibtex, 200, [
            'Content-Type' => 'application/x-bibtex; charset=utf-8',
            'Content-Disposition' => "attachment; filename=article-{$article->id}.bib",
        ]);
    }

    public function exportRis(Article $article)
    {
        abort_unless(in_array($article->status, [ArticleStatus::Published, ArticleStatus::Retracted]), 404);
        $article->load('authors', 'issue');

        $lines = [];
        $lines[] = 'TY  - JOUR';
        $lines[] = "TI  - {$article->title}";

        foreach ($article->authors as $author) {
            $lines[] = "AU  - {$author->full_name}";
        }

        $lines[] = 'JO  - '.config('app.name');

        if ($article->issue) {
            $lines[] = "VL  - {$article->issue->volume}";
            $lines[] = "IS  - {$article->issue->number}";
            $lines[] = "PY  - {$article->issue->year}///";
        } elseif ($article->published_at) {
            $lines[] = "PY  - {$article->published_at->year}///";
        }

        if ($article->doi) {
            $lines[] = "DO  - {$article->doi}";
        }

        $abstract = $article->abstract_en ?: $article->abstract_ru;
        if ($abstract) {
            $lines[] = "AB  - {$abstract}";
        }

        if (is_array($article->keywords)) {
            foreach ($article->keywords as $kw) {
                $lines[] = "KW  - {$kw}";
            }
        }

        $lines[] = 'ER  - ';

        $ris = implode("\r\n", $lines);

        return response($ris, 200, [
            'Content-Type' => 'application/x-research-info-systems; charset=utf-8',
            'Content-Disposition' => "attachment; filename=article-{$article->id}.ris",
        ]);
    }

    public function pdf(Article $article)
    {
        abort_unless($article->pdf_path, 404);

        // Published articles are publicly accessible
        // Non-published articles require the user to be the submitter, an editor, or a reviewer
        if (! in_array($article->status, [ArticleStatus::Published, ArticleStatus::Retracted])) {
            $user = auth()->user();
            abort_unless($user, 404);

            $allowed = $article->submitted_by === $user->id
                || $user->hasAnyRole(['editor-in-chief', 'managing-editor', 'admin'])
                || ($user->hasRole('section-editor') && $article->editor_id === $user->id)
                || $article->reviews()->where('reviewer_id', $user->id)->exists();

            abort_unless($allowed, 404);
        }

        $disk = Storage::disk($article->pdf_disk);

        abort_unless($disk->exists($article->pdf_path), 404);

        return $disk->response($article->pdf_path, "article-{$article->id}.pdf", [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Serve the anonymised manuscript for double-blind peer review.
     *
     * Access is restricted to authenticated users who are either:
     * - editorial staff (admin, EiC, ME, or assigned section editor), or
     * - an assigned reviewer with an active (non-declined) review.
     *
     * The file is always served from the local (private) disk — never from
     * the public disk — so it cannot be discovered through URL guessing.
     * Returns a 404 for missing files rather than a 403 to avoid information
     * leakage about the existence of blinded manuscripts.
     *
     * SPECIFICATION: SPEC-05/AC-6
     */
    public function blindedPdf(Article $article)
    {
        abort_unless($article->blinded_pdf_path, 404);

        $user = auth()->user();
        abort_unless($user, 404);

        $allowed = $user->hasAnyRole(['editor-in-chief', 'managing-editor', 'admin'])
            || ($user->hasRole('section-editor') && $article->editor_id === $user->id)
            || $article->reviews()->where('reviewer_id', $user->id)->whereNot('status', ReviewStatus::Declined)->exists();

        abort_unless($allowed, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($article->blinded_pdf_path), 404);

        return $disk->response($article->blinded_pdf_path, "article-{$article->id}-blinded.pdf", [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Serve the typeset galley PDF for download.
     * Accessible to editorial staff and the article submitter.
     *
     * SPECIFICATION: SPEC-13/AC-3
     */
    public function galleyPdf(Article $article)
    {
        abort_unless($article->galley_pdf_path, 404);

        $user = auth()->user();
        abort_unless($user, 404);

        $allowed = $user->hasAnyRole(['editor-in-chief', 'managing-editor', 'admin'])
            || ($user->hasRole('section-editor') && $article->editor_id === $user->id)
            || $article->submitted_by === $user->id;

        abort_unless($allowed, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($article->galley_pdf_path), 404);

        return $disk->response($article->galley_pdf_path, "article-{$article->id}-galley.pdf", [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function exportJats(Article $article, JatsXmlBuilder $builder)
    {
        abort_unless(in_array($article->status, [ArticleStatus::Published, ArticleStatus::Retracted]), 404);

        $xml = $builder->build($article);

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Content-Disposition' => "attachment; filename=article-{$article->id}.xml",
        ]);
    }
}
