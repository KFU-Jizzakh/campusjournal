<?php

namespace App\Http\Controllers;

use App\Models\Article;

/**
 * PURPOSE: Full-text search across published article titles,
 * abstracts, and keywords with LIKE matching.
 *
 * SPECIFICATION: SPEC-11/AC-6, SPEC-11/AC-7
 */
class SearchController extends Controller
{
    public function index()
    {
        $query = request('q');
        $articles = null;

        if ($query) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);
            $pattern = "%{$escaped}%";

            $articles = Article::published()
                ->with('authors', 'category', 'issue')
                ->where(function ($q) use ($pattern) {
                    $q->where('title', 'LIKE', $pattern)
                        ->orWhere('abstract_ru', 'LIKE', $pattern)
                        ->orWhere('abstract_en', 'LIKE', $pattern)
                        ->orWhere('keywords', 'LIKE', $pattern);
                })
                ->orderByDesc('published_at')
                ->paginate(12)
                ->withQueryString();
        }

        return view('search.index', compact('query', 'articles'));
    }
}
