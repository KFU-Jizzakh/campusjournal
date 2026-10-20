<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Author;
use App\Models\Issue;

it('escapes script-breaking payloads in the article JSON-LD', function () {
    $payload = '</script><script>alert(1)</script>';

    $article = Article::factory()->published()->create([
        'title' => $payload,
        'keywords' => [$payload],
    ]);

    $author = Author::factory()->create(['full_name' => 'Иванов Иван </script>']);
    $article->authors()->attach($author->id, ['order' => 1]);

    $this->get(route('articles.show', $article))
        ->assertOk()
        ->assertSee('\u003Cscript\u003Ealert(1)', false);
});

it('escapes script-breaking payloads in the issue JSON-LD', function () {
    $issue = Issue::factory()->create([
        'description' => '</script><script>alert(2)</script>',
    ]);

    $this->get(route('issues.show', $issue))
        ->assertOk()
        ->assertSee('\u003Cscript\u003Ealert(2)', false);
});

it('renders the article JSON-LD with expected fields', function () {
    $article = Article::factory()->published()->create([
        'title' => 'Тестовая статья',
        'abstract_ru' => 'Аннотация статьи для проверки',
        'doi' => '10.1234/jsonld',
        'keywords' => ['кириллица', 'тест'],
    ]);

    $author = Author::factory()->create([
        'full_name' => 'Петров Пётр Петрович',
        'orcid' => '0000-0002-1825-0097',
    ]);
    $article->authors()->attach($author->id, ['order' => 1]);

    $this->get(route('articles.show', $article))
        ->assertOk()
        ->assertSee('<script type="application/ld+json">', false)
        ->assertSee('"@type": "ScholarlyArticle"', false)
        ->assertSee('"headline": "Тестовая статья"', false)
        ->assertSee('"name": "Петров Пётр Петрович"', false)
        ->assertSee('https://orcid.org/0000-0002-1825-0097', false)
        ->assertSee('"value": "10.1234/jsonld"', false)
        ->assertSee('"keywords": "кириллица, тест"', false);
});

it('renders the issue JSON-LD with expected fields', function () {
    $issue = Issue::factory()->create([
        'volume' => 5,
        'number' => 2,
    ]);

    $this->get(route('issues.show', $issue))
        ->assertOk()
        ->assertSee('<script type="application/ld+json">', false)
        ->assertSee('"@type": "PublicationIssue"', false)
        ->assertSee('"@type": "Periodical"', false);
});
