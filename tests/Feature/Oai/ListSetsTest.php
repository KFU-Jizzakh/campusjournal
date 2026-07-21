<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Issue;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    config(['oai.repository_id' => 'test.example']);
    Cache::forget('oai.sets');
});

test('lists category and issue sets with published articles', function () {
    $cat = Category::factory()->create(['slug' => 'chem', 'name' => 'Chemistry']);
    $emptyCat = Category::factory()->create(['slug' => 'empty', 'name' => 'Empty']);
    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2025, 'title' => 'First']);

    Article::factory()->published()->create(['category_id' => $cat->id, 'issue_id' => $issue->id]);

    $body = $this->get('/oai?verb=ListSets')->getContent();

    expect($body)->toContain('<setSpec>category:chem</setSpec>');
    expect($body)->toContain('<setName>Chemistry</setName>');
    expect($body)->toContain('<setSpec>issue:'.$issue->id.'</setSpec>');
    expect($body)->not->toContain('category:empty');
});
