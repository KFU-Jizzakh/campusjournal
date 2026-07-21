<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Issue;

/**
 * PURPOSE: Serves author profile pages with their published
 * articles and issue history.
 */
class AuthorController extends Controller
{
    public function show(Author $author)
    {
        $articles = $author->articles()
            ->published()
            ->with('issue', 'category', 'authors')
            ->orderByDesc('published_at')
            ->get();

        $issues = Issue::whereHas('articles', function ($query) use ($author) {
            $query->published()
                ->whereHas('authors', fn ($q) => $q->where('authors.id', $author->id));
        })
            ->orderByDesc('year')
            ->orderByDesc('number')
            ->get();

        return view('authors.show', compact('author', 'articles', 'issues'));
    }
}
