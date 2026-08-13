# SPEC-16: Post-Publication (Retraction, Withdrawal, Corrigendum)

Article retraction after publication, author-initiated withdrawal before publication,
and post-publication corrections (corrigendum/erratum) with Crossmark DOI update support.

Depends on: SPEC-04, SPEC-08

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: The author can withdraw their own article before publication — status changes to Withdrawn, reason is stored
- AC-2: A workflow manager (EiC/ME) can withdraw any pre-published article — status changes to Withdrawn, reason is stored
- AC-3: A workflow manager (EiC/ME) can retract a published article — status changes to Retracted, retraction reason is stored, article remains publicly visible with a retraction banner
- AC-4: The editorial staff can add corrections (corrigendum/erratum/expression_of_concern) to a published article — corrections appear on the public page and in the editorial interface
- AC-5: On retraction or correction, if Crossref is enabled, the DOI prefix and the Crossmark policy DOI are configured, the DOI is re-deposited with Crossmark update metadata (`retraction` or `correction` update type). If the policy DOI is missing, no re-deposit is queued and the user is shown a warning that the Crossmark re-deposit was skipped
- AC-6: The public article page shows a prominent retraction banner for retracted articles, lists correction notices, and includes a Crossmark button
- AC-7: Withdrawn articles are not shown on the public site, listed only in editorial dashboard with Withdrawn status
- AC-8: Outbox events are recorded for withdrawal (`article.withdrawn`), retraction (`article.retracted`), and correction addition (`article.correction_added`)
- AC-9: Authors are notified of retraction and withdrawal (if performed by editor); editors are notified of author-initiated withdrawal; if no editor is assigned, managing-editors and editor-in-chief are notified instead
- AC-10: The public article page embeds the official Crossref Crossmark widget (v2.0): `<meta name="dc.identifier" content="doi:...">` in the page head — rendered for every published article with a DOI (required by Crossref for Crossmark and a standard DOI citation meta) — a `<a data-target="crossmark">` anchor with the official button image near the article title, and the `crossmark.js` widget script loaded once per page. The button and the widget script appear only when the article has a DOI and the Crossmark policy DOI is configured

## UI/UX Notes

- "Withdraw" button shown to author on their article page for all pre-published non-terminal statuses (Submitted through Approved)
- "Withdraw" and "Retract" buttons shown in editorial dashboard for workflow managers
- Correction management UI in editorial dashboard — add, edit, delete corrections for published articles
- Retraction banner: red background, "Статья отозвана (ретрекшн)" heading with reason text
- Correction list: each correction shows type badge (corrigendum/erratum/expression_of_concern), title, description, date, optional PDF link
- Crossmark button: official Crossref widget v2.0 — `<a data-target="crossmark">` anchor with the color horizontal button image (`https://crossmark-cdn.crossref.org/widget/v2.0/logos/CROSSMARK_Color_horizontal.svg`), placed near the article title, plus a "Что это?" link to the policy page. The widget script is loaded once per page. The button must not be self-hosted, recolored, or resized (other than proportional scaling)

## Business Rules

- BR-1: Withdrawal moves article to terminal status Withdrawn; only possible from non-terminal, non-published statuses
- BR-2: Retraction is only possible from Published status, performed by EiC or managing editor
- BR-3: Withdrawn articles are hidden from public site, visible only in editorial dashboard and author's submissions list
- BR-4: Retracted articles remain publicly accessible with a retraction watermar/banner and the retraction reason
- BR-5: Corrections can only be added to published articles by workflow managers
- BR-6: Each correction has a type, title, description, optional PDF notice file, and publication date
- BR-7: On retraction or correction, the Crossref DOI deposit is re-sent with Crossmark update metadata; for corrections, if no corrections remain after deletion, the `<updates>` block is omitted. Re-deposits are dispatched only when Crossref is enabled, the DOI prefix is configured (SPEC-08/BR-2a), and the Crossmark policy DOI is configured — without the policy DOI the `<updates>` block cannot be rendered, so the re-deposit is skipped and a warning flash is shown to the operator. The `<crossmark>` block is rendered inside `<journal_article>` (before `<doi_data>`, per schema 5.3.1) and only when the Crossmark policy DOI is configured. The `<updates>` block contains one `<update type="{correction_type}" date="YYYY-MM-DD">doi</update>` per remaining correction in chronological order (oldest first, ties broken by id, dated by `published_at` — falling back to today when missing) and, for retractions, a trailing `<update type="retraction" date="YYYY-MM-DD">doi</update>` (dated by `retracted_at`); the retraction update never replaces the correction updates — Crossref re-deposits replace the whole record, so corrections are preserved. `type` for corrections mirrors the correction's own type (`corrigendum`/`erratum`/`expression_of_concern`, schema-accepted values) and is `retraction` for the trailing update, so the Crossmark panel shows the precise update kind; the DOI content is the article's own DOI (the standard in-place update pattern when there is no separate notice DOI — or the explicitly minted DOI in the degraded persistence path). Crossref validates updates asynchronously: an HTTP 2xx acceptance of the deposit does not guarantee the update passed Crossmark/QC. A non-empty but invalid policy DOI (e.g. a URL) disables the `<crossmark>` block and the widget without crashing the application — configuration evaluation is side-effect free, and the misconfiguration is surfaced once per day via a `Log::warning` at application boot (`CrossrefConfig::misconfigured()`/`warnIfMisconfigured()`)
- BR-7a: Funding metadata (`<fr:program name="fundref">`) is rendered as a single program with one `funder_name`/`funder_identifier`/`award_number` assertion per funder. When the crossmark block is present it lives inside `<custom_metadata>` (the schema 5.3.1 `journal_article` choice makes `<crossmark>` and a direct `<fr:program>` mutually exclusive); otherwise it is a direct child of `journal_article`. In both cases it precedes `<doi_data>`
- BR-8: Crossmark update deposit uses the same DOI — it is re-deposited as an update, not a new DOI
- BR-8a: Re-deposits skipped while the policy DOI was missing are not re-queued automatically; the `php artisan crossref:redeposit` command (supports `--dry-run`) backfills them for registered articles that are retracted or have corrections once the policy DOI is configured
- BR-9: Notifications follow the existing pattern — AuthorStatusChanged for retraction/withdrawal, with one-hour throttle; if no editor is assigned, managing-editors and editor-in-chief receive author-initiated withdrawal notifications

## Behavior

### Background
Given the article exists in the system

### Rule: Author withdrawal (AC-1, BR-1, BR-3)

#### Scenario: Author withdraws own article before publication

Given the article is in Submitted/InReview/Accepted/Revision/Copyediting/Production/AwaitingApproval/Approved status
And   the authenticated user is the article submitter
When  the author provides a withdrawal reason and clicks "Withdraw"
Then  the status changes to Withdrawn
And   withdrawal reason, timestamp and actor are saved
And   the `article.withdrawn` event is recorded
And   editors who manage this article are notified
And   the article disappears from the public site

#### Scenario: Editor withdraws article before publication

Given the article is in a pre-published non-terminal status
And   the authenticated user is a workflow manager (EiC/ME)
When  the editor provides a withdrawal reason and clicks "Withdraw"
Then  the status changes to Withdrawn
And   the article author is notified

#### Scenario: Attempt to withdraw a published article

Given the article is Published
When  the user attempts withdrawal
Then  the action is blocked — retraction should be used instead

#### Scenario: Attempt to withdraw already withdrawn/retracted/rejected article

Given the article is in Withdrawn/Retracted/Rejected status
When  the user attempts withdrawal
Then  the action is blocked

### Rule: Retraction (AC-2, AC-3, BR-2, BR-4, BR-7)

#### Scenario: Workflow manager retracts a published article

Given the article is Published
And   the authenticated user is EiC or managing editor
When  the editor provides a retraction reason and clicks "Retract"
Then  the status changes to Retracted
And   retraction reason, timestamp and actor are saved
And   the `article.retracted` event is recorded
And   authors are notified
And   if Crossref is enabled and the DOI prefix is configured, a DOI re-deposit with Crossmark `retraction` update is queued

#### Scenario: Attempt to retract non-published article

Given the article is not Published
When  the user attempts retraction
Then  the action is blocked

#### Scenario: Non-EiC/ME attempts retraction

Given the article is Published
And   the authenticated user is a section-editor or author
Then  the retraction button is not shown, direct invocation is blocked by policy

### Rule: Corrections (AC-4, BR-5, BR-6, BR-7)

#### Scenario: Adding a correction to a published article

Given the article is Published
And   the authenticated user is a workflow manager
When  the user fills in correction type, title, description and clicks "Add correction"
Then  a Correction record is created and linked to the article
And   the `article.correction_added` event is recorded
And   the correction appears on the public article page

#### Scenario: Deleting a correction

Given a correction exists on a published article
And   the authenticated user is a workflow manager
When  the user deletes the correction
Then  the correction is removed and its file (if any) is deleted from storage
And   if Crossref is enabled and the DOI prefix is configured, a DOI re-deposit with Crossmark `correction` update is queued

#### Scenario: Attempt to add correction to non-published article

Given the article is not Published
When  the user attempts to add a correction
Then  the action is blocked

### Rule: Crossmark DOI update (AC-5, BR-7, BR-8)

#### Scenario: DOIs re-deposited with Crossmark on retraction

Given Crossref is enabled and the DOI prefix is configured
And   the Crossmark policy DOI is configured
And   the article is retracted
When  the retraction is saved
Then  a DOI deposit job is queued with Crossmark update type `retraction`
And   the deposit XML includes `<crossmark>` inside `<journal_article>` with version, policy DOI, and domains
And   the `<updates>` block includes `<update type="retraction" date="{retracted_at}">{article_doi}</update>`
And   if the article has corrections, the `<updates>` block also includes `<update type="{correction_type}" date="{published_at}">{article_doi}</update>` for each remaining correction, before the retraction update

#### Scenario: DOIs re-deposited with Crossmark on correction

Given Crossref is enabled and the DOI prefix is configured
And   the Crossmark policy DOI is configured
And   a correction is added to a published article
When  the correction is saved
Then  a DOI deposit job is queued with Crossmark update type `correction`
And   the deposit XML includes an `<update type="{correction_type}" date="{published_at}">{article_doi}</update>` for each remaining correction

#### Scenario: No re-deposit when the prefix is not configured

Given Crossref is enabled but the DOI prefix is not configured
And   a retraction or correction is performed
When  the action is saved
Then  no DOI deposit job is queued

#### Scenario: No re-deposit when the policy DOI is missing

Given Crossref is enabled and the DOI prefix is configured
But   the Crossmark policy DOI is not configured
And   a retraction or correction is performed
When  the action is saved
Then  no DOI deposit job is queued
And   a warning is flashed that the Crossmark re-deposit was skipped

## Data Model

### Article — new columns
| Column | Type | Description |
|--------|------|-------------|
| `withdrawal_reason` | text nullable | Reason text for withdrawal |
| `withdrawn_at` | timestamp nullable | When the article was withdrawn |
| `retraction_reason` | text nullable | Reason text for retraction |
| `retracted_at` | timestamp nullable | When the article was retracted |

### Corrections table
| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint PK | |
| `article_id` | FK → articles ON DELETE CASCADE | |
| `type` | string | `corrigendum`, `erratum`, `expression_of_concern` |
| `title` | string | Short title of the correction |
| `description` | text | Full description of what was corrected |
| `file_path` | string nullable | PDF notice file (stored locally) |
| `published_at` | timestamp | When the correction was published |
| `created_by` | FK → users ON DELETE SET NULL | Who added the correction |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### ArticleStatus new cases

```
Withdrawn  = 'withdrawn'   — terminal, no further transitions
Retracted  = 'retracted'   — terminal, no further transitions
```

### Allowed transitions additions

```
Submitted, InReview, Accepted, Revision, Copyediting, Production, AwaitingApproval, Approved → Withdrawn
Published → Retracted
```

### Crossmark configuration (services.crossref)

```php
'crossmark' => [
    'policy_doi' => App\Support\CrossrefConfig::policyDoi(env('CROSSMARK_POLICY_DOI'), env('CROSSMARK_POLICY_URL')),
    'domains' => App\Support\CrossrefConfig::domains(env('CROSSMARK_DOMAINS', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: '')),
],
```

`policy_doi` is a Crossref-registered policy DOI (schema type `doi_t`, pattern `10.[0-9]{4,9}/...`) — a plain URL is not accepted by the schema. `CrossrefConfig::policyDoi()` validates the value against the `doi_t` pattern and returns `null` for anything malformed (so an invalid value disables the crossmark block and the widget instead of producing a schema-invalid deposit); it falls back to the deprecated `CROSSMARK_POLICY_URL` variable when `CROSSMARK_POLICY_DOI` is unset. `CrossrefConfig::domains()` trims the list and keeps only entries matching the schema's `cm_domain` pattern (dotted hostname, 4–1024 chars), dropping values like `localhost`. The public `/crossmark-policy` page remains as the human-readable policy statement.
