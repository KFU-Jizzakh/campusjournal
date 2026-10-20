<?php

declare(strict_types=1);

use App\Models\Article;
use Illuminate\Database\QueryException;

it('rejects duplicate DOIs across articles', function () {
    $doi = '10.5555/duplicate-test';

    Article::factory()->published()->create(['doi' => $doi]);

    Article::factory()->published()->create(['doi' => $doi]);
})->throws(QueryException::class);

it('allows multiple articles without a DOI', function () {
    Article::factory()->published()->create(['doi' => null]);
    Article::factory()->published()->create(['doi' => null]);

    expect(Article::whereNull('doi')->count())->toBe(2);
});
