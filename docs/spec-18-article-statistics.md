# SPEC-18: Article Statistics (Views & Downloads)

The system tracks two article-level metrics — views of the article detail page and downloads of the published PDF — using session-deduplicated counters displayed on the public site and in the admin panel.

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: When a visitor opens a Published or Retracted article detail page for the first time in their session, `views_count` is incremented by 1. Repeat visits in the same session do not increment it further.
- AC-2: When a visitor downloads a Published or Retracted article PDF with the `?download=1` query parameter for the first time in their session, `downloads_count` is incremented by 1. Downloads without `?download=1` (e.g. iframe embeds) do not increment it. Authenticated downloads of non-published articles do not increment it.
- AC-3: `views_count` and `downloads_count` are displayed on the article detail page, article listing, search results, and the Filament admin table (as sortable columns).
- AC-4: No `OutboxEvent` is logged for views or downloads — the counters use raw `increment()` calls.

## Business Rules

- BR-1: Only Published and Retracted articles are counted. Non-published articles (Draft, Submitted, In Review, etc.) are never counted — the public show action returns 404, and the `pdf()` method skips the increment guard.
- BR-2: Session arrays (`viewed_articles` and `downloaded_articles`) deduplicate within a single session so that a user cannot inflate the counters by refreshing or re-downloading.
- BR-3: Downloads are only counted when `request()->boolean('download')` is true — this prevents iframe PDF embeds on the detail page from double-counting every view as a download.
- BR-4: An authenticated user downloading their own non-published article (e.g. author viewing a submitted manuscript) does not increment `downloads_count` — the status guard is evaluated regardless of authentication.

## Database

| Column | Type | Default | Notes |
|--------|------|---------|-------|
| `views_count` | `unsignedInteger` | `0` | Incremented in `ArticleController::show()` |
| `downloads_count` | `unsignedInteger` | `0` | Incremented in `ArticleController::pdf()` |

## Behavior

### Rule: View counting (BR-1, BR-2)

#### Scenario: First view of a published article

Given the article is Published
When  a visitor opens the article detail page for the first time
Then  `views_count` is incremented by 1

#### Scenario: Repeat view in same session

Given the article has already been viewed in the current session
When  the visitor opens the same article page again
Then  `views_count` is not changed

#### Scenario: Non-published article returns 404

Given the article is in Draft status
When  a visitor attempts to open the article detail page
Then  a 404 response is returned
And   `views_count` is not incremented

### Rule: Download counting (BR-1, BR-2, BR-3)

#### Scenario: First download with ?download=1

Given the article is Published and has a PDF file
When  a visitor downloads the PDF with `?download=1`
Then  `downloads_count` is incremented by 1

#### Scenario: iframe embed does not count as download

Given the article is Published and has a PDF file
When  the PDF is served without the `?download` parameter (e.g. via an iframe embed)
Then  `downloads_count` is not incremented

#### Scenario: Repeat download in same session

Given the article PDF has already been downloaded with `?download=1` in the current session
When  the visitor downloads the same PDF again
Then  `downloads_count` is not changed

#### Scenario: Retracted article download is counted

Given the article is Retracted and has a PDF file
When  a visitor downloads the PDF with `?download=1`
Then  `downloads_count` is incremented by 1

#### Scenario: Authenticated download of non-published article

Given the article is Submitted (not Published or Retracted) and has a PDF file
And   the authenticated user is the article submitter
When  the user downloads the PDF with `?download=1`
Then  `downloads_count` is not incremented

### Rule: Independent counting across articles (BR-2)

#### Scenario: Different articles counted independently

Given two Published articles A and B
When  a visitor views article A, then downloads article B with `?download=1`
Then  `views_count` of A is 1, `downloads_count` of B is 1
And   `downloads_count` of A is 0, `views_count` of B is 0

### Rule: Display (AC-3)

#### Scenario: Public display

Given the article is Published
When  a visitor opens the article detail page, article listing, or search results
Then  both `views_count` and `downloads_count` are displayed with icon labels

#### Scenario: Filament admin display

Given the user is an admin
When  viewing the articles table in Filament
Then  columns "Просмотры" (`views_count`) and "Скачивания" (`downloads_count`) are sortable
