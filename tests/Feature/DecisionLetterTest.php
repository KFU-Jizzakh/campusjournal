<?php

use App\Models\Article;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use App\Support\DecisionLetter;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(SettingSeeder::class);
});

test('decision letter render substitutes all placeholders', function () {
    Setting::set('decision_template_accept', '{decision_label}: «{title}» (№{id})');

    $article = Article::factory()->submitted()->create([
        'title' => 'Квантовая хромодинамика',
    ]);

    $letter = DecisionLetter::render('accept', $article);

    expect($letter)
        ->toBe('Принять к публикации: «Квантовая хромодинамика» (№'.$article->id.')')
        ->not->toContain('{title}')
        ->not->toContain('{id}')
        ->not->toContain('{decision_label}');
});

test('renderAll returns rendered letters for all three decisions', function () {
    $article = Article::factory()->submitted()->create();

    $templates = DecisionLetter::renderAll($article);

    expect($templates)->toHaveKeys(['accept', 'revision', 'reject'])
        ->and($templates['accept'])->not->toBe('')
        ->and($templates['revision'])->toContain('доработк')
        ->and($templates['reject'])->not->toBe('');
});

test('custom template from settings is used with placeholders', function () {
    Setting::set('decision_template_accept', 'Уважаемые {authors}! Ваша статья №{id} «{title}» принята. {editor}');

    $article = Article::factory()->submitted()->create();
    $editor = User::factory()->create(['email' => 'chief@example.test']);
    $editor->profile()->updateOrCreate(['user_id' => $editor->id], ['last_name' => 'Главный', 'first_name' => 'Редактор']);

    $letter = DecisionLetter::render('accept', $article, $editor);

    expect($letter)->toContain('Главный Редактор')
        ->toContain($article->title)
        ->toContain((string) $article->id);
});

test('decision form on editorial page carries the templates', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $article = Article::factory()->inReview()->create(['editor_id' => $editor->id]);
    Review::factory()->completed()->create(['article_id' => $article->id]);

    $this->actingAs($editor)
        ->get(route('editorial.show', $article))
        ->assertOk()
        ->assertSee('data-template', escape: false)
        ->assertSee('Редакция рада сообщить');
});

test('decision stores the edited text as submitted', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $article = Article::factory()->inReview()->create(['editor_id' => $editor->id]);
    Review::factory()->completed()->create(['article_id' => $article->id]);

    $this->actingAs($editor)
        ->post(route('editorial.decide', $article), [
            'decision' => 'accept',
            'decision_comments' => 'Собственный текст решения без шаблона.',
        ]);

    expect($article->fresh()->decision_comments)->toBe('Собственный текст решения без шаблона.');
});
