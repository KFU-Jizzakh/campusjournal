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
- AC-5: On retraction or correction, if Crossref is enabled, the DOI is re-deposited with Crossmark update metadata (`retraction` or `correction` update type)
- AC-6: The public article page shows a prominent retraction banner for retracted articles, lists correction notices, and includes a Crossmark button
- AC-7: Withdrawn articles are not shown on the public site, listed only in editorial dashboard with Withdrawn status
- AC-8: Outbox events are recorded for withdrawal (`article.withdrawn`), retraction (`article.retracted`), and correction addition (`article.correction_added`)
- AC-9: Authors are notified of retraction and withdrawal (if performed by editor); editors are notified of author-initiated withdrawal; if no editor is assigned, managing-editors and editor-in-chief are notified instead

## UI/UX Notes

- "Withdraw" button shown to author on their article page for all pre-published non-terminal statuses (Submitted through Approved)
- "Withdraw" and "Retract" buttons shown in editorial dashboard for workflow managers
- Correction management UI in editorial dashboard — add, edit, delete corrections for published articles
- Retraction banner: red background, "Статья отозвана (ретрекшн)" heading with reason text
- Correction list: each correction shows type badge (corrigendum/erratum/expression_of_concern), title, description, date, optional PDF link
- Crossmark button: small "Crossmark" logo/badge linking to the DOI via Crossmark service

## Business Rules

- BR-1: Withdrawal moves article to terminal status Withdrawn; only possible from non-terminal, non-published statuses
- BR-2: Retraction is only possible from Published status, performed by EiC or managing editor
- BR-3: Withdrawn articles are hidden from public site, visible only in editorial dashboard and author's submissions list
- BR-4: Retracted articles remain publicly accessible with a retraction watermar/banner and the retraction reason
- BR-5: Corrections can only be added to published articles by workflow managers
- BR-6: Each correction has a type, title, description, optional PDF notice file, and publication date
- BR-7: On retraction or correction, the Crossref DOI deposit is re-sent with Crossmark update metadata (`update_type` in XML head); for corrections, if no corrections remain after deletion, the `<doi_updates>` block is omitted
- BR-8: Crossmark update deposit uses the same DOI — it is re-deposited as an update, not a new DOI
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
And   if Crossref is enabled, a DOI re-deposit with Crossmark `retraction` update is queued

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
And   if Crossref is enabled, a DOI re-deposit with Crossmark `correction` update is queued

#### Scenario: Attempt to add correction to non-published article

Given the article is not Published
When  the user attempts to add a correction
Then  the action is blocked

### Rule: Crossmark DOI update (AC-5, BR-7, BR-8)

#### Scenario: DOIs re-deposited with Crossmark on retraction

Given Crossref is enabled
And   the article is retracted
When  the retraction is saved
Then  a DOI deposit job is queued with Crossmark update type `retraction`
And   the deposit XML includes `<crossmark>` elements with version, update type, and policy URL

#### Scenario: DOIs re-deposited with Crossmark on correction

Given Crossref is enabled
And   a correction is added to a published article
When  the correction is saved
Then  a DOI deposit job is queued with Crossmark update type `correction`
And   the deposit XML includes updated `<crossmark>` elements

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
    'policy_url' => env('CROSSMARK_POLICY_URL', url('/crossmark-policy')),
    'domains' => array_filter(explode(',', env('CROSSMARK_DOMAINS', ''))),
],
```
