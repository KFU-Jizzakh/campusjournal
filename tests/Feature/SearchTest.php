<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Issue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('finds published articles by title', function () {
    $issue = Issue::factory()->create(['status' => 'published']);
    Article::factory()->published()->create(['title' => 'Quantum Computing Review', 'issue_id' => $issue->id]);
    Article::factory()->published()->create(['title' => 'Biology Methods', 'issue_id' => $issue->id]);

    $response = $this->get(route('search', ['q' => 'Quantum']));

    $response->assertOk()
        ->assertSee('Quantum Computing Review')
        ->assertDontSee('Biology Methods');
});

it('does not treat percent as wildcard', function () {
    $issue = Issue::factory()->create(['status' => 'published']);
    Article::factory()->published()->create(['title' => 'Normal Article', 'issue_id' => $issue->id]);

    $response = $this->get(route('search', ['q' => '%']));

    $response->assertOk()
        ->assertDontSee('Normal Article');
});

it('does not treat underscore as wildcard', function () {
    $issue = Issue::factory()->create(['status' => 'published']);
    Article::factory()->published()->create(['title' => 'AB', 'issue_id' => $issue->id]);

    $response = $this->get(route('search', ['q' => 'A_']));

    $response->assertOk()
        ->assertDontSee('AB');
});

it('does not return draft articles', function () {
    Article::factory()->create(['title' => 'Secret Draft', 'status' => ArticleStatus::Draft]);

    $response = $this->get(route('search', ['q' => 'Secret']));

    $response->assertOk()
        ->assertDontSee('Secret Draft');
});
