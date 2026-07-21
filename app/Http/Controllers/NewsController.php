<?php

namespace App\Http\Controllers;

use App\Models\News;

/**
 * PURPOSE: Serves published news listings and individual
 * news article detail pages.
 */
class NewsController extends Controller
{
    public function index()
    {
        $news = News::published()
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('news.index', compact('news'));
    }

    public function show(News $news)
    {
        abort_unless($news->is_published, 404);

        return view('news.show', compact('news'));
    }
}
