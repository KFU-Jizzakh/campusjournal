<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\User;
use App\Notifications\AuthorStatusChanged;
use App\Notifications\InvitationNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function inviteArticleWithCoauthor(string $coauthorEmail, User $submitter): array
{
    $category = Category::factory()->create();
    $article = Article::submit($submitter, [
        'title' => 'Совместная статья',
        'abstract_ru' => 'Аннотация',
        'category_id' => $category->id,
        'pdf_path' => 'submissions/test.pdf',
    ]);

    $author = Author::create([
        'full_name' => 'Петров Пётр Петрович',
        'email' => $coauthorEmail,
        'invitation_sent_at' => now(),
    ]);
    $article->authors()->attach($author->id, ['order' => 2, 'email' => $coauthorEmail]);

    return [$article, $author];
}

function invitationUrlFor(Article $article, Author $author): string
{
    $relative = URL::temporarySignedRoute(
        'invitations.accept',
        now()->addDays(7),
        ['article' => $article->id, 'author' => $author->id],
        absolute: false
    );

    return rtrim(config('app.url'), '/').$relative;
}

// --- Claim ---

test('user can claim coauthor record via signed invitation url', function () {
    $submitter = User::factory()->create();
    $coauthorUser = User::factory()->create(['email_verified_at' => now()]);
    [$article, $author] = inviteArticleWithCoauthor($coauthorUser->email, $submitter);

    $authorsCount = Author::count();

    $this->actingAs($coauthorUser)
        ->get(invitationUrlFor($article, $author))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');

    $author->refresh();

    expect($author->user_id)->toBe($coauthorUser->id)
        ->and(Author::count())->toBe($authorsCount)
        ->and($author->email)->toBe($coauthorUser->email);
});

test('claim is rejected when pivot email does not match the user email', function () {
    $submitter = User::factory()->create();
    $coauthorUser = User::factory()->create(['email_verified_at' => now()]);
    [$article, $author] = inviteArticleWithCoauthor('someone-else@example.test', $submitter);

    $this->actingAs($coauthorUser)
        ->get(invitationUrlFor($article, $author))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error');

    expect($author->refresh()->user_id)->toBeNull();
});

test('claim is rejected when the record belongs to another user', function () {
    $submitter = User::factory()->create();
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $intruder = User::factory()->create(['email_verified_at' => now()]);
    [$article, $author] = inviteArticleWithCoauthor($owner->email, $submitter);
    $author->update(['user_id' => $owner->id]);

    $this->actingAs($intruder)
        ->get(invitationUrlFor($article, $author))
        ->assertSessionHas('error');

    expect($author->refresh()->user_id)->toBe($owner->id);
});

test('claim is rejected for users without verified email', function () {
    $submitter = User::factory()->create();
    $coauthorUser = User::factory()->create(['email_verified_at' => null]);
    [$article, $author] = inviteArticleWithCoauthor($coauthorUser->email, $submitter);

    $this->actingAs($coauthorUser)
        ->get(invitationUrlFor($article, $author))
        ->assertRedirect(route('verification.notice'));
});

test('claim email matching is case insensitive', function () {
    $submitter = User::factory()->create();
    $coauthorUser = User::factory()->create([
        'email' => 'Petrov@Example.Test',
        'email_verified_at' => now(),
    ]);
    [$article, $author] = inviteArticleWithCoauthor('petrov@example.test', $submitter);

    $this->actingAs($coauthorUser)
        ->get(invitationUrlFor($article, $author))
        ->assertSessionHas('success');

    expect($author->refresh()->user_id)->toBe($coauthorUser->id);
});

test('claim is idempotent for the owning user', function () {
    $submitter = User::factory()->create();
    $coauthorUser = User::factory()->create(['email_verified_at' => now()]);
    [$article, $author] = inviteArticleWithCoauthor($coauthorUser->email, $submitter);

    $this->actingAs($coauthorUser)->get(invitationUrlFor($article, $author));
    $this->actingAs($coauthorUser)
        ->get(invitationUrlFor($article, $author))
        ->assertSessionHas('success');

    expect($author->refresh()->user_id)->toBe($coauthorUser->id);
});

test('claim is rejected when no invitation was sent', function () {
    $submitter = User::factory()->create();
    $coauthorUser = User::factory()->create(['email_verified_at' => now()]);
    [$article, $author] = inviteArticleWithCoauthor($coauthorUser->email, $submitter);
    $author->update(['invitation_sent_at' => null]);

    $this->actingAs($coauthorUser)
        ->get(invitationUrlFor($article, $author))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error');

    expect($author->refresh()->user_id)->toBeNull();
});

test('claim is rejected when the claimer submitted the inviting article', function () {
    $submitter = User::factory()->create();
    [$article, $author] = inviteArticleWithCoauthor($submitter->email, $submitter);

    $this->actingAs($submitter)
        ->get(invitationUrlFor($article, $author))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error');

    expect($author->refresh()->user_id)->toBeNull();
});

test('claim is only proven by the pivot snapshot of the inviting article', function () {
    $submitter = User::factory()->create();
    $coauthorUser = User::factory()->create(['email_verified_at' => now()]);
    [$article, $author] = inviteArticleWithCoauthor('someone-else@example.test', $submitter);

    // A different article carries a pivot row with the claimer's email —
    // it must not prove ownership for the invitation of $article.
    $otherArticle = Article::submit($submitter, [
        'title' => 'Другая статья',
        'abstract_ru' => 'Аннотация',
        'category_id' => Category::factory()->create()->id,
        'pdf_path' => 'submissions/other.pdf',
    ]);
    $otherArticle->authors()->attach($author->id, [
        'order' => 2,
        'email' => $coauthorUser->email,
    ]);

    $this->actingAs($coauthorUser)
        ->get(invitationUrlFor($article, $author))
        ->assertSessionHas('error');

    $this->actingAs($coauthorUser)
        ->get(invitationUrlFor($otherArticle, $author))
        ->assertSessionHas('success');

    expect($author->refresh()->user_id)->toBe($coauthorUser->id);
});

test('expired invitation url is rejected', function () {
    $submitter = User::factory()->create();
    $coauthorUser = User::factory()->create(['email_verified_at' => now()]);
    [$article, $author] = inviteArticleWithCoauthor($coauthorUser->email, $submitter);

    $relative = URL::temporarySignedRoute(
        'invitations.accept',
        now()->subDay(),
        ['article' => $article->id, 'author' => $author->id],
        absolute: false
    );

    $this->actingAs($coauthorUser)
        ->get(rtrim(config('app.url'), '/').$relative)
        ->assertForbidden();
});

test('unsigned url is rejected', function () {
    $submitter = User::factory()->create();
    $coauthorUser = User::factory()->create(['email_verified_at' => now()]);
    [$article, $author] = inviteArticleWithCoauthor($coauthorUser->email, $submitter);

    $this->actingAs($coauthorUser)
        ->get('/invitations/'.$article->id.'/'.$author->id.'/accept')
        ->assertForbidden();
});

// --- Invitation trigger ---

test('submission sends invitation to verified user listed as coauthor', function () {
    Notification::fake();

    $submitter = User::factory()->create();
    $submitter->assignRole('author');
    $coauthorUser = User::factory()->create(['email_verified_at' => now()]);
    $category = Category::factory()->create();

    $this->actingAs($submitter)
        ->post(route('submissions.store'), [
            'title' => 'Новая совместная статья',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван Иванович',
            'author_email' => $submitter->email,
            'author_phone' => '+7 (900) 123-45-67',
            'author_country' => 'Россия',
            'author_city' => 'Казань',
            'agreement_accepted' => 'on',
            'coauthors' => [
                [
                    'full_name' => 'Петров Пётр Петрович',
                    'email' => $coauthorUser->email,
                    'phone' => '+7 (900) 765-43-21',
                    'country' => 'Россия',
                    'city' => 'Москва',
                ],
            ],
        ])
        ->assertRedirect();

    Notification::assertSentTo($coauthorUser, InvitationNotification::class);

    $author = Author::where('full_name', 'Петров Пётр Петрович')->sole();

    expect($author->invitation_sent_at)->not->toBeNull();
});

test('no invitation for unknown or unverified coauthor emails', function () {
    Notification::fake();

    $submitter = User::factory()->create();
    $submitter->assignRole('author');
    $unverified = User::factory()->create(['email_verified_at' => null]);
    $category = Category::factory()->create();

    $this->actingAs($submitter)
        ->post(route('submissions.store'), [
            'title' => 'Статья с неизвестными соавторами',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван Иванович',
            'author_email' => $submitter->email,
            'author_phone' => '+7 (900) 123-45-67',
            'author_country' => 'Россия',
            'author_city' => 'Казань',
            'agreement_accepted' => 'on',
            'coauthors' => [
                [
                    'full_name' => 'Сидоров Сидор',
                    'email' => 'nobody-has-this@example.test',
                    'phone' => '+7 (900) 765-43-21',
                    'country' => 'Россия',
                    'city' => 'Москва',
                ],
                [
                    'full_name' => 'Петров Пётр',
                    'email' => $unverified->email,
                    'phone' => '+7 (900) 765-43-22',
                    'country' => 'Россия',
                    'city' => 'Тверь',
                ],
            ],
        ])
        ->assertRedirect();

    Notification::assertNotSentTo($unverified, InvitationNotification::class);
    Notification::assertNotSentTo($submitter, InvitationNotification::class);

    $timestamps = Author::whereIn('full_name', ['Сидоров Сидор', 'Петров Пётр'])->pluck('invitation_sent_at');

    expect($timestamps)->toHaveCount(2)
        ->and($timestamps->filter()->isEmpty())->toBeTrue();
});

test('no invitation when the submitter lists their own email as coauthor', function () {
    Notification::fake();

    $submitter = User::factory()->create();
    $submitter->assignRole('author');
    $category = Category::factory()->create();

    $this->actingAs($submitter)
        ->post(route('submissions.store'), [
            'title' => 'Статья с самоприглашением',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван Иванович',
            'author_email' => $submitter->email,
            'author_phone' => '+7 (900) 123-45-67',
            'author_country' => 'Россия',
            'author_city' => 'Казань',
            'agreement_accepted' => 'on',
            'coauthors' => [
                [
                    'full_name' => 'Иванов Иван Иванович',
                    'email' => $submitter->email,
                    'phone' => '+7 (900) 123-45-68',
                    'country' => 'Россия',
                    'city' => 'Казань',
                ],
            ],
        ])
        ->assertRedirect();

    Notification::assertNotSentTo($submitter, InvitationNotification::class);

    $coauthorRow = Author::whereNull('user_id')->where('full_name', 'Иванов Иван Иванович')->sole();

    expect($coauthorRow->invitation_sent_at)->toBeNull();
});

test('resubmission within 24 hours does not resend the invitation', function () {
    Notification::fake();

    $submitter = User::factory()->create();
    $submitter->assignRole('author');
    $coauthorUser = User::factory()->create(['email_verified_at' => now()]);
    $category = Category::factory()->create();

    $payload = fn (string $title) => [
        'title' => $title,
        'abstract_ru' => 'Аннотация',
        'category_id' => $category->id,
        'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
        'author_name' => 'Иванов Иван Иванович',
        'author_email' => $submitter->email,
        'author_phone' => '+7 (900) 123-45-67',
        'author_country' => 'Россия',
        'author_city' => 'Казань',
        'agreement_accepted' => 'on',
        'coauthors' => [
            [
                'full_name' => 'Петров Пётр Петрович',
                'email' => $coauthorUser->email,
                'phone' => '+7 (900) 765-43-21',
                'country' => 'Россия',
                'city' => 'Москва',
                'orcid' => '0000-0002-1825-0097',
            ],
        ],
    ];

    $this->actingAs($submitter)->post(route('submissions.store'), $payload('Первая подача'));
    $this->actingAs($submitter)->post(route('submissions.store'), $payload('Вторая подача'));

    Notification::assertSentToTimes($coauthorUser, InvitationNotification::class, 1);

    expect(Author::where('orcid', '0000-0002-1825-0097')->count())->toBe(1);
});

// --- Post-claim behaviour ---

test('claimed coauthor receives article notifications', function () {
    Notification::fake();

    $submitter = User::factory()->create();
    $coauthorUser = User::factory()->create(['email_verified_at' => now()]);
    [$article, $author] = inviteArticleWithCoauthor($coauthorUser->email, $submitter);

    $this->actingAs($coauthorUser)->get(invitationUrlFor($article, $author));

    $article->transitionTo(ArticleStatus::InReview);
    $article->notifiableUsers()->each(
        fn (User $user) => $user->notify(new AuthorStatusChanged($article, 'article.in_review', 'Статья отправлена на рецензирование'))
    );

    Notification::assertSentTo($coauthorUser, AuthorStatusChanged::class);
});
