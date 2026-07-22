<?php

use App\Filament\Resources\ConferenceResource\Pages\CreateConference;
use App\Filament\Resources\ConferenceResource\Pages\EditConference;
use App\Filament\Resources\EventResource\Pages\CreateEvent;
use App\Filament\Resources\EventResource\Pages\EditEvent;
use App\Filament\Resources\NewsResource\Pages\CreateNews;
use App\Filament\Resources\NewsResource\Pages\EditNews;
use App\Filament\Resources\OrganizationResource\Pages\CreateOrganization;
use App\Filament\Resources\OrganizationResource\Pages\EditOrganization;
use App\Filament\Resources\PageResource\Pages\CreatePage;
use App\Filament\Resources\PageResource\Pages\EditPage;
use App\Models\Conference;
use App\Models\Event;
use App\Models\News;
use App\Models\Organization;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// --- Panel Access (SPEC-19/AC-2, AC-12) ---

test('content-manager can access filament panel', function () {
    $user = User::factory()->create();
    $user->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();
});

test('editor-in-chief cannot access filament panel', function () {
    $user = User::factory()->create();
    $user->assignRole('editor-in-chief');

    $response = $this->actingAs($user)->get('/admin');

    expect($response->status())->toBeIn([302, 403]);
});

test('managing-editor cannot access filament panel', function () {
    $user = User::factory()->create();
    $user->assignRole('managing-editor');

    $response = $this->actingAs($user)->get('/admin');

    expect($response->status())->toBeIn([302, 403]);
});

test('section-editor cannot access filament panel', function () {
    $user = User::factory()->create();
    $user->assignRole('section-editor');

    $response = $this->actingAs($user)->get('/admin');

    expect($response->status())->toBeIn([302, 403]);
});

test('reviewer cannot access filament panel', function () {
    $user = User::factory()->create();
    $user->assignRole('reviewer');

    $response = $this->actingAs($user)->get('/admin');

    expect($response->status())->toBeIn([302, 403]);
});

test('author cannot access filament panel', function () {
    $user = User::factory()->create();
    $user->assignRole('author');

    $response = $this->actingAs($user)->get('/admin');

    expect($response->status())->toBeIn([302, 403]);
});

test('guest cannot access filament panel', function () {
    $response = $this->get('/admin');

    expect($response->status())->toBeIn([302, 403]);
});

// --- Content Resource Access (SPEC-19/AC-4, AC-5, AC-6, AC-7, AC-8) ---

test('content-manager can access news', function () {
    $user = User::factory()->create();
    $user->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/news')
        ->assertOk();
});

test('content-manager can access events', function () {
    $user = User::factory()->create();
    $user->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/events')
        ->assertOk();
});

test('content-manager can access pages', function () {
    $user = User::factory()->create();
    $user->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/pages')
        ->assertOk();
});

test('content-manager can access conferences', function () {
    $user = User::factory()->create();
    $user->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/conferences')
        ->assertOk();
});

test('content-manager can access organisations', function () {
    $user = User::factory()->create();
    $user->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/organizations')
        ->assertOk();
});

// --- Restricted Resource Access (SPEC-19/AC-9, AC-10, AC-11) ---

test('content-manager cannot access articles', function () {
    $user = User::factory()->create();
    $user->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/articles')
        ->assertForbidden();
});

test('content-manager cannot access issues', function () {
    $user = User::factory()->create();
    $user->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/issues')
        ->assertForbidden();
});

test('content-manager cannot access categories', function () {
    $user = User::factory()->create();
    $user->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/categories')
        ->assertForbidden();
});

test('content-manager cannot access authors', function () {
    $user = User::factory()->create();
    $user->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/authors')
        ->assertForbidden();
});

test('content-manager cannot access editorial board members', function () {
    $user = User::factory()->create();
    $user->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/editorial-board-members')
        ->assertForbidden();
});

test('content-manager cannot access reviews', function () {
    $user = User::factory()->create();
    $user->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/reviews')
        ->assertForbidden();
});

test('content-manager cannot access users', function () {
    $user = User::factory()->create();
    $user->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/users')
        ->assertForbidden();
});

test('content-manager cannot access copyright agreements', function () {
    $user = User::factory()->create();
    $user->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/copyright-agreements')
        ->assertForbidden();
});

// --- Combined Roles (SPEC-19/AC-14) ---

test('user with admin and content-manager roles can access all resources', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $user->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/articles')
        ->assertOk();

    $this->actingAs($user)
        ->get('/admin/users')
        ->assertOk();
});

// --- Create Page Access (SPEC-19/AC-4, AC-5, AC-6, AC-7, AC-8) ---

test('content-manager can access news create page', function () {
    $user = User::factory()->create()->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/news/create')
        ->assertOk();
});

test('content-manager can access events create page', function () {
    $user = User::factory()->create()->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/events/create')
        ->assertOk();
});

test('content-manager can access pages create page', function () {
    $user = User::factory()->create()->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/pages/create')
        ->assertOk();
});

test('content-manager can access conferences create page', function () {
    $user = User::factory()->create()->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/conferences/create')
        ->assertOk();
});

test('content-manager can access organizations create page', function () {
    $user = User::factory()->create()->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/organizations/create')
        ->assertOk();
});

// --- Create via Livewire (SPEC-19/AC-4, AC-5, AC-6, AC-7, AC-8) ---

test('content-manager can create news', function () {
    $user = User::factory()->create()->assignRole('content-manager');

    Livewire::actingAs($user)
        ->test(CreateNews::class)
        ->fillForm([
            'title' => 'Test News Title',
            'body' => '<p>Test body</p>',
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('news', ['title' => 'Test News Title']);
});

test('content-manager can create event', function () {
    $user = User::factory()->create()->assignRole('content-manager');

    Livewire::actingAs($user)
        ->test(CreateEvent::class)
        ->fillForm([
            'title' => 'Test Event Title',
            'description' => 'Test event description',
            'event_date' => now()->addDays(30)->format('Y-m-d'),
            'event_type' => 'conference',
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('events', ['title' => 'Test Event Title']);
});

test('content-manager can create page', function () {
    $user = User::factory()->create()->assignRole('content-manager');

    Livewire::actingAs($user)
        ->test(CreatePage::class)
        ->fillForm([
            'title' => 'Test Page Title',
            'slug' => 'test-page-slug',
            'body' => '<p>Test page body</p>',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('pages', ['title' => 'Test Page Title']);
});

test('content-manager can create conference', function () {
    $user = User::factory()->create()->assignRole('content-manager');

    Livewire::actingAs($user)
        ->test(CreateConference::class)
        ->fillForm([
            'title' => 'Test Conference Title',
            'slug' => 'test-conference-slug',
            'description' => 'Test conference description',
            'event_date' => now()->addDays(60)->format('Y-m-d'),
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('conferences', ['title' => 'Test Conference Title']);
});

test('content-manager can create organization', function () {
    $user = User::factory()->create()->assignRole('content-manager');

    Livewire::actingAs($user)
        ->test(CreateOrganization::class)
        ->fillForm([
            'name' => 'Test Organization',
            'description' => 'Test organization description',
            'sort_order' => 1,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('organizations', ['name' => 'Test Organization']);
});

// --- Edit Page Access (SPEC-19/AC-4, AC-5, AC-6, AC-7, AC-8) ---

test('content-manager can access news edit page', function () {
    $user = User::factory()->create()->assignRole('content-manager');
    $news = News::factory()->create();

    $this->actingAs($user)
        ->get("/admin/news/{$news->id}/edit")
        ->assertOk();
});

test('content-manager can access events edit page', function () {
    $user = User::factory()->create()->assignRole('content-manager');
    $event = Event::factory()->create();

    $this->actingAs($user)
        ->get("/admin/events/{$event->id}/edit")
        ->assertOk();
});

test('content-manager can access pages edit page', function () {
    $user = User::factory()->create()->assignRole('content-manager');
    $page = Page::factory()->create();

    $this->actingAs($user)
        ->get("/admin/pages/{$page->id}/edit")
        ->assertOk();
});

test('content-manager can access conferences edit page', function () {
    $user = User::factory()->create()->assignRole('content-manager');
    $conference = Conference::factory()->create();

    $this->actingAs($user)
        ->get("/admin/conferences/{$conference->id}/edit")
        ->assertOk();
});

test('content-manager can access organizations edit page', function () {
    $user = User::factory()->create()->assignRole('content-manager');
    $organization = Organization::factory()->create();

    $this->actingAs($user)
        ->get("/admin/organizations/{$organization->id}/edit")
        ->assertOk();
});

// --- Update via Livewire (SPEC-19/AC-4, AC-5, AC-6, AC-7, AC-8) ---

test('content-manager can update news', function () {
    $user = User::factory()->create()->assignRole('content-manager');
    $news = News::factory()->create(['title' => 'Original Title']);

    Livewire::actingAs($user)
        ->test(EditNews::class, ['record' => $news->id])
        ->fillForm(['title' => 'Updated News Title'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($news->fresh()->title)->toBe('Updated News Title');
});

test('content-manager can update event', function () {
    $user = User::factory()->create()->assignRole('content-manager');
    $event = Event::factory()->create(['title' => 'Original Event']);

    Livewire::actingAs($user)
        ->test(EditEvent::class, ['record' => $event->id])
        ->fillForm(['title' => 'Updated Event Title'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($event->fresh()->title)->toBe('Updated Event Title');
});

test('content-manager can update page', function () {
    $user = User::factory()->create()->assignRole('content-manager');
    $page = Page::factory()->create(['title' => 'Original Page']);

    Livewire::actingAs($user)
        ->test(EditPage::class, ['record' => $page->id])
        ->fillForm(['title' => 'Updated Page Title'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($page->fresh()->title)->toBe('Updated Page Title');
});

test('content-manager can update conference', function () {
    $user = User::factory()->create()->assignRole('content-manager');
    $conference = Conference::factory()->create(['title' => 'Original Conference']);

    Livewire::actingAs($user)
        ->test(EditConference::class, ['record' => $conference->id])
        ->fillForm(['title' => 'Updated Conference Title'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($conference->fresh()->title)->toBe('Updated Conference Title');
});

test('content-manager can update organization', function () {
    $user = User::factory()->create()->assignRole('content-manager');
    $organization = Organization::factory()->create(['name' => 'Original Org']);

    Livewire::actingAs($user)
        ->test(EditOrganization::class, ['record' => $organization->id])
        ->fillForm(['name' => 'Updated Org Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($organization->fresh()->name)->toBe('Updated Org Name');
});

// --- Delete via Livewire (SPEC-19/AC-4, AC-5, AC-6, AC-7, AC-8) ---

test('content-manager can delete news', function () {
    $user = User::factory()->create()->assignRole('content-manager');
    $news = News::factory()->create();

    Livewire::actingAs($user)
        ->test(EditNews::class, ['record' => $news->id])
        ->callAction('delete');

    $this->assertSoftDeleted('news', ['id' => $news->id]);
});

test('content-manager can delete event', function () {
    $user = User::factory()->create()->assignRole('content-manager');
    $event = Event::factory()->create();

    Livewire::actingAs($user)
        ->test(EditEvent::class, ['record' => $event->id])
        ->callAction('delete');

    $this->assertSoftDeleted('events', ['id' => $event->id]);
});

test('content-manager can delete page', function () {
    $user = User::factory()->create()->assignRole('content-manager');
    $page = Page::factory()->create();

    Livewire::actingAs($user)
        ->test(EditPage::class, ['record' => $page->id])
        ->callAction('delete');

    $this->assertSoftDeleted('pages', ['id' => $page->id]);
});

test('content-manager can delete conference', function () {
    $user = User::factory()->create()->assignRole('content-manager');
    $conference = Conference::factory()->create();

    Livewire::actingAs($user)
        ->test(EditConference::class, ['record' => $conference->id])
        ->callAction('delete');

    $this->assertSoftDeleted('conferences', ['id' => $conference->id]);
});

test('content-manager can delete organization', function () {
    $user = User::factory()->create()->assignRole('content-manager');
    $organization = Organization::factory()->create();

    Livewire::actingAs($user)
        ->test(EditOrganization::class, ['record' => $organization->id])
        ->callAction('delete');

    $this->assertSoftDeleted('organizations', ['id' => $organization->id]);
});

// --- Validation ---

test('content-manager cannot create news without title', function () {
    $user = User::factory()->create()->assignRole('content-manager');

    Livewire::actingAs($user)
        ->test(CreateNews::class)
        ->fillForm([
            'body' => '<p>Body without title</p>',
        ])
        ->call('create')
        ->assertHasFormErrors(['title']);
});

// --- Restricted Create Page Access ---

test('content-manager cannot access articles create page', function () {
    $user = User::factory()->create()->assignRole('content-manager');

    $this->actingAs($user)
        ->get('/admin/articles/create')
        ->assertForbidden();
});
