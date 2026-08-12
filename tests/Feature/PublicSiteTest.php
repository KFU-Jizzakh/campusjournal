<?php

use App\Enums\ArticleFileLicense;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleAgreement;
use App\Models\Conference;
use App\Models\CopyrightAgreement;
use App\Models\Event;
use App\Models\Issue;
use App\Models\Page;
use App\Models\User;

// --- Home ---

test('home page loads successfully', function () {
    $this->get(route('home'))->assertOk();
});

test('home page shows planned issues', function () {
    $issue = Issue::factory()->create([
        'status' => 'planned',
        'title' => 'Тематический номер тест',
        'year' => 2026,
        'number' => 1,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Тематический номер тест');
});

test('home page shows upcoming events', function () {
    Event::factory()->create(['title' => 'Будущая конференция']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Будущая конференция');
});

// --- Public nav ---

test('public nav links to education and no longer to news or editorial board', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('education'))
        ->assertDontSee('/editorial-board')
        ->assertDontSee('/news')
        ->assertDontSee('Редколлегия')
        ->assertDontSee('Новости');
});

test('removed sections return 404', function () {
    $this->get('/news')->assertNotFound();
    $this->get('/editorial-board')->assertNotFound();
});

// --- Issues ---

test('issues index shows published issues', function () {
    $published = Issue::factory()->create(['status' => 'published', 'title' => 'Published Issue']);
    $planned = Issue::factory()->create(['status' => 'planned', 'title' => 'Planned Issue']);

    $response = $this->get(route('issues.index'));
    $response->assertOk();
    $response->assertSee('Published Issue');
    $response->assertDontSee('Planned Issue');
});

test('issue show page loads', function () {
    $issue = Issue::factory()->create(['status' => 'published']);

    $this->get(route('issues.show', $issue))->assertOk();
});

test('issue show displays published articles', function () {
    $issue = Issue::factory()->create(['status' => 'published']);
    $article = Article::factory()->published()->create([
        'issue_id' => $issue->id,
        'title' => 'Published Article Title',
    ]);

    $this->get(route('issues.show', $issue))
        ->assertOk()
        ->assertSee('Published Article Title');
});

// --- Articles ---

test('articles index shows published articles', function () {
    Article::factory()->published()->create(['title' => 'Visible Article']);
    Article::factory()->submitted()->create(['title' => 'Hidden Submitted']);

    $response = $this->get(route('articles.index'));
    $response->assertOk();
    $response->assertSee('Visible Article');
    $response->assertDontSee('Hidden Submitted');
});

test('published article can be viewed', function () {
    $article = Article::factory()->published()->create(['title' => 'My Published Article']);

    $this->get(route('articles.show', $article))
        ->assertOk()
        ->assertSee('My Published Article');
});

test('non-published article returns 404', function () {
    $article = Article::factory()->submitted()->create();

    $this->get(route('articles.show', $article))->assertNotFound();
});

test('draft article returns 404', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Draft]);

    $this->get(route('articles.show', $article))->assertNotFound();
});

test('published article shows license from accepted agreement', function () {
    $agreement = CopyrightAgreement::create([
        'version' => 1,
        'title' => 'CC BY Agreement',
        'short_text' => 'Short text.',
        'license' => ArticleFileLicense::CcBy->value,
        'is_active' => true,
        'published_at' => now(),
    ]);

    $article = Article::factory()->published()->create([
        'title' => 'Licensed Article',
    ]);

    ArticleAgreement::create([
        'article_id' => $article->id,
        'copyright_agreement_id' => $agreement->id,
        'accepted_by' => User::factory()->create()->id,
        'accepted_ip' => '127.0.0.1',
    ]);

    $this->get(route('articles.show', $article))
        ->assertOk()
        ->assertSee('Licensed Article')
        ->assertSee(ArticleFileLicense::CcBy->label())
        ->assertSee(ArticleFileLicense::CcBy->url());
});

test('published article without accepted agreement does not show license block', function () {
    $article = Article::factory()->published()->create([
        'title' => 'Unlicensed Article',
    ]);

    $this->get(route('articles.show', $article))
        ->assertOk()
        ->assertSee('Unlicensed Article')
        ->assertDontSee(ArticleFileLicense::CcBy->label());
});

test('published article with agreement but no license set does not show license block', function () {
    $agreement = CopyrightAgreement::create([
        'version' => 1,
        'title' => 'No License Agreement',
        'short_text' => 'Short text.',
        'license' => null,
        'is_active' => true,
        'published_at' => now(),
    ]);

    $article = Article::factory()->published()->create([
        'title' => 'No License Article',
    ]);

    ArticleAgreement::create([
        'article_id' => $article->id,
        'copyright_agreement_id' => $agreement->id,
        'accepted_by' => User::factory()->create()->id,
        'accepted_ip' => '127.0.0.1',
    ]);

    $this->get(route('articles.show', $article))
        ->assertOk()
        ->assertSee('No License Article')
        ->assertDontSee(ArticleFileLicense::CcBy->label());
});

// --- Events ---

test('events page loads', function () {
    $this->get(route('events.index'))->assertOk();
});

test('events page shows upcoming published events', function () {
    Event::factory()->create(['title' => 'Upcoming Conference']);
    Event::factory()->unpublished()->create(['title' => 'Hidden Event']);

    $response = $this->get(route('events.index'));
    $response->assertOk();
    $response->assertSee('Upcoming Conference');
    $response->assertDontSee('Hidden Event');
});

// --- Pages ---

test('about page loads', function () {
    Page::factory()->create(['slug' => 'about', 'title' => 'О журнале']);

    $this->get(route('about'))
        ->assertOk()
        ->assertSee('О журнале');
});

test('about page shows editorial board section', function () {
    Page::factory()->create([
        'slug' => 'about',
        'title' => 'О журнале',
        'body' => '<h2>Редакционная коллегия</h2><ul><li><strong>Галимов Алмаз Мирзанурович</strong> — главный редактор.</li></ul>',
    ]);

    $this->get(route('about'))
        ->assertOk()
        ->assertSee('Редакционная коллегия')
        ->assertSee('Галимов Алмаз Мирзанурович');
});

test('for-authors page loads', function () {
    Page::factory()->create(['slug' => 'for-authors', 'title' => 'Для авторов']);

    $this->get(route('for-authors'))
        ->assertOk()
        ->assertSee('Для авторов');
});

test('contacts page loads', function () {
    Page::factory()->create(['slug' => 'contacts', 'title' => 'Контакты']);

    $this->get(route('contacts'))
        ->assertOk()
        ->assertSee('Контакты');
});

test('education page loads', function () {
    Page::factory()->create([
        'slug' => 'education',
        'title' => 'Образование',
        'body' => '<h2>Повышение квалификации</h2><p>Программы для преподавателей.</p>',
    ]);

    $this->get(route('education'))
        ->assertOk()
        ->assertSee('Образование')
        ->assertSee('Повышение квалификации');
});

// --- Conferences ---

test('conferences page loads', function () {
    $this->get(route('conferences.index'))->assertOk();
});

test('conferences page shows upcoming published conferences', function () {
    Conference::factory()->create(['title' => 'Upcoming Conference']);
    Conference::factory()->unpublished()->create(['title' => 'Hidden Conference']);

    $response = $this->get(route('conferences.index'));
    $response->assertOk();
    $response->assertSee('Upcoming Conference');
    $response->assertDontSee('Hidden Conference');
});

test('published conference can be viewed', function () {
    $conference = Conference::factory()->create([
        'title' => 'Test Conference Detail',
        'body' => '<p>Conference program here</p>',
    ]);

    $this->get(route('conferences.show', $conference))
        ->assertOk()
        ->assertSee('Test Conference Detail');
});

test('unpublished conference returns 404', function () {
    $conference = Conference::factory()->unpublished()->create();

    $this->get(route('conferences.show', $conference))->assertNotFound();
});

test('past conferences are shown', function () {
    Conference::factory()->past()->create(['title' => 'Past Conference']);

    $this->get(route('conferences.index'))
        ->assertOk()
        ->assertSee('Past Conference');
});

// ---

test('about page returns 404 when page not in db', function () {
    $this->get(route('about'))->assertNotFound();
});

test('education page returns 404 when page not in db', function () {
    $this->get(route('education'))->assertNotFound();
});
