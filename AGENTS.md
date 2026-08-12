# Overview

Academic journal management system with full editorial workflow.

# Rules
- After work show commit message
- Use Conventional Commits spec for commit msg
- Don't commit
- Use ripgrep instead of grep if available
- Before implementing a feature, add or update detailed specs in @docs using the template @docs/_spec-template.md. Ask questions if anything is more than 1% unclear.
- The feature’s behavior and key points from Acceptance Criteria and Business Rules must be covered by tests.

## Docblock format
All classes and key methods must have a docblock in the format below

```php
/**
 * PURPOSE: [one-line summary of what the class or method does]
 *
 * SPECIFICATION: [spec-item-id], [spec-item-id], ...
 */
```

- `PURPOSE:` — single sentence describing the class or method purpose
- `SPECIFICATION:` — comma-separated list of identifiers referencing the relevant items in `docs/spec-*.md`. Format mirrors the spec's own numbering (e.g. `SPEC-01, SPEC-03, SPEC-05` for the main spec). Omit for cross-cutting infrastructure files (User, Profile, Setting, etc.).
- Do not duplicate Laravel boilerplate methods (`casts()`, `envelope()`, `attachments()`)

# Stack

- **Backend:** Laravel 13, PHP 8.3+, PostgreSQL
- **Admin:** Filament 5.4 (admin only)
- **Auth:** Laravel Breeze, Spatie Permission 7.2
- **Frontend:** Tailwind CSS 4, Alpine.js 3, Vite 8
- **Security:** HTMLPurifier (ezyang/htmlpurifier)
- **Media:** Spatie Media Library 11, Intervention Image (thumbnails)
- **Tests:** Pest 4.4


# Test users (password `password1234` for all):

| Email | Role |
|-------|------|
| admin@globalcampus.local | admin |
| galimov@globalcampus.local | editor-in-chief |
| managing@globalcampus.local | managing-editor |
| section@globalcampus.local | section-editor |
| reviewer@globalcampus.local | reviewer |
| author@globalcampus.local | author |
| content@globalcampus.local | content-manager |


# Commands

```bash
# Setup
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed

# Dev server (serve + queue + vite + logs concurrently)
composer dev

# Tests (PostgreSQL, requires test database — see .env.testing)
php artisan test
php artisan test --filter=TestClassName
php artisan test tests/Feature/ProfileTest.php

# Code style
./vendor/bin/pint

# Single migration rollback / re-seed
php artisan migrate:fresh --seed
```

> **Deploy note:** the News and Editorial Board removals take effect on
> already-provisioned databases with the next `php artisan migrate:fresh --seed`.
> Orphaned `news` and `editorial_board_members` tables left by older schemas
> are inert (no model or resource references them) and can be dropped manually.


# Architecture

## Two distinct surfaces

**Public site** (`/`, `/issues`, `/articles`, `/education`, `/events`, `/about`, `/for-authors`, `/contacts`, `/join`) — anonymous access, read-only, served by controllers in `app/Http/Controllers/`.

**Dashboard** (`/dashboard/*`) — authenticated, permission-gated, served by controllers in `app/Http/Controllers/Dashboard/`. Three sub-areas:
- `/dashboard/articles/*` — requires `submit-article` permission → `SubmissionController`
- `/dashboard/reviews/*` — requires `review-article` permission → `ReviewController`
- `/dashboard/editorial/*` — requires `manage-submissions` permission → `EditorialController`

**Filament admin** (`/admin`) — `admin` role only, full CRUD via 12 Filament resources in `app/Filament/Resources/`.

## Authorization model

Two layers work together:
1. Route middleware: `permission:<name>` (Spatie) gates entire route groups
2. Laravel Policies (`ArticlePolicy`, `ReviewPolicy`) enforce row-level access within those groups

`ArticlePolicy::viewEditorial` is the key policy: EiC/managing-editor see all non-draft articles; section-editor sees only articles where `editor_id = user->id`.

## User identity

`User` stores only `email` and `password`. Display name comes from the related `Profile` model (`HasOne`). `user->full_name` falls back to email if no profile exists. `Author` is a separate model for article authorship metadata (ORCID, SPIN, organisation) — linked to `User` via `user_id`.

## OutboxEvent / event log

Outbox logging is performed manually inside model workflow methods (e.g. `Article::submit()`, `Article::assignReviewer()`, `Review::complete()`, etc.). Each call writes a row to `outbox_events` with: event name (e.g. `article.updated`), morphed subject reference, full attribute snapshot as JSON payload, and the authenticated user as actor.

`OutboxEvent` has no `updated_at` column (`const UPDATED_AT = null`).


## Domain Exceptions

All domain rule violations must use typed `\DomainException` subclasses.

- Each exception is a **final** class in its own file under `app/Exceptions/`
- Extend `\DomainException` (never `\Exception` or other bases)
- Resolve the localized message in `__construct()` via `__()` — the class body is otherwise empty
- Name after the violation in past tense (e.g. `AssignEditorFailedException`, `NotSectionEditorException`)
- Throw from **model methods** when a domain rule is violated
- Catch in **controllers** as `catch (\DomainException $e)` and pass to user as `->with('error', $e->getMessage())`
- Do **not** use generic `\Exception` or `\RuntimeException` for domain errors

## Eloquent Model Attributes

All Eloquent models use PHP 8 attributes where the framework supports them.

- **`$fillable`** → `#[Fillable([...])]` above the class declaration
- **`$hidden`** → `#[Hidden([...])]` above the class declaration
- **`$timestamps = false`** → `#[WithoutTimestamps]` above the class declaration
- **`$incrementing = false`** → `#[WithoutIncrementing]` above the class declaration
- **`protected function casts()`** — stays as a traditional method (no `#[Casts]` attribute in this Laravel version)
- **`$primaryKey`**, **`$keyType`**, **`const`** — stay as traditional declarations (no attribute equivalents)

Each attribute requires its own `use` import at the top of the file (e.g. `use Illuminate\Database\Eloquent\Attributes\Fillable;`).

## Views

Blade templates must be declarative — they render data, never compute it.

- **No business logic in views.** Status comparisons (`$article->status === App\Enums\ArticleStatus::Submitted`), `in_array(…)`, `switch` on model values, and `@php` blocks belong in the model (as `is*()` / computed accessor methods) or in the controller (as pre-computed variables).
- **No enum constants in views.** Views call `$article->isSubmitted()`, not `$article->status === App\Enums\ArticleStatus::Submitted`. Style/colour logic lives in enum methods like `badgeClass()`.
- **No permission checks in views.** Use controller-passed flags (`$showPublish`), never `auth()->user()->hasPermissionTo(…)` in a Blade file.
- **No collection filtering in views.** Use scoped model relations or accessor methods (`$article->completedReviews()`, not `$article->reviews->where('status', …)`).
- **No inline `@php` blocks.** Deadlines, colour classes, derived booleans — compute in the model method and expose as a single value the view can use directly.


## Testing

Tests use PostgreSQL (configured in `.env.testing`). Use `RefreshDatabase` for tests that need the schema.

