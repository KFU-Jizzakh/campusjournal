# SPEC-08: DOI Registration (Crossref)

Automatic DOI registration for an article via Crossref. The full DOI (prefix + opaque random suffix) is minted at the moment of publication, a deposit XML v5.3.1 is built, and sent to Crossref. The result is logged. A manual run is provided for articles without a DOI.

Depends on: SPEC-04

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: On article publication, if Crossref is enabled in settings, a DOI registration job is automatically queued
- AC-2: The DOI is generated at publication time from the configured prefix and a short, opaque random suffix (Crossref best practice: no readable metadata — no journal initials, dates, page numbers, or internal identifiers in the suffix). Default suffix length is 8 characters
- AC-3: If the article already has a DOI (manually set), a new one is not generated
- AC-4: A deposit XML v5.3.1 is built based on article metadata, authors, and issue
- AC-5: The XML is sent via a multipart POST request to the Crossref endpoint
- AC-6: The result (status, HTTP code, response body) is saved in the deposits table
- AC-7: On success — the article's DOI registration date is updated, the `article.doi_deposited` event is recorded. The DOI itself is already saved on the article from publication (AC-2)
- AC-8: On error — the job retries up to 3 times with exponential backoff (60/300/900 sec). Retries reuse the already-minted DOI (never regenerated)
- AC-9: The `crossref:backfill` command sends deposits for published articles without a DOI (supports `--dry-run`), only when Crossref is enabled and the prefix is configured. The job mints and persists a DOI for such legacy articles before the first deposit attempt

## UI/UX Notes

- The functionality is entirely background, with no user interface (except the Filament admin panel)
- A deposit can be triggered manually via Filament admin, only when Crossref is enabled and the prefix is configured
- Filament displays the status of the latest deposit for the article

## Business Rules

- BR-1: Crossref registration is triggered only if the service is enabled in settings
- BR-1a: Every dispatch path (publication, `crossref:backfill`, Filament manual deposit, crossmark re-deposits) uses the same readiness check: Crossref enabled AND the DOI prefix configured (`DoiMinter::isReady()`)
- BR-2: If the article already has a DOI, auto-generation is not performed (neither at publication nor in the job)
- BR-2a: A DOI is generated only if the Crossref prefix is configured. Without a prefix, `DoiMinter::mint()` throws `DoiPrefixNotConfiguredException` and no dispatch path (publication, backfill, Filament manual deposit) queues a deposit job — the `doi` field stays empty
- BR-2b: The suffix is generated from an unambiguous 32-character charset (`abcdefghjkmnpqrstuvwxyz23456789` — no `0/O/1/I/l`); its length is configurable (`doi_suffix_length`, default 8)
- BR-2c: The suffix never changes between deposit attempts. A DOI is either persisted on the article (at publication, or at job start for legacy articles) or reserved in the first deposit record before the network call; a retry reuses the reserved DOI instead of minting a new one. If the pre-deposit persist fails (e.g. DB outage), the job still proceeds with the same minted DOI and the success path persists both the DOI and the registration date together. A job failing before any reservation exists never reached the network, so no suffix was delivered to Crossref and re-minting is safe
- BR-3: Each deposit attempt is logged as a separate record (audit trail)
- BR-4: The deposit is executed asynchronously via a queue, without blocking publication

## Behavior

### Background
Given the Crossref service is enabled in configuration

### Rule: Automatic DOI generation at publication (BR-2, BR-2a, BR-2b, BR-2c)

#### Scenario: DOI minted on publication

Given the article is in "In Production" status
And   the article has no DOI yet
And   the Crossref prefix is configured
When  the editor publishes the article (see SPEC-04)
Then  a DOI `{prefix}/{random_suffix}` is generated and saved on the article
And   the DOI registration date is not set yet
And   a DOI registration job is queued

#### Scenario: Article with a manually set DOI

Given the article has a filled-in DOI before publication
When  the article is published
Then  DOI auto-generation is not performed, the existing DOI is preserved
But   no new DOI is generated

#### Scenario: Publication without a configured Crossref prefix

Given the Crossref prefix is not configured
When  the editor publishes the article
Then  the article is published without a DOI
And   no deposit job is queued
But   the DOI can still be assigned later once the prefix is configured (AC-9)

#### Scenario: Deposit job with an unconfigured prefix

Given the Crossref prefix is not configured
And   a deposit job is invoked directly (e.g. from a queue worker)
When  the job mints a DOI
Then  `DoiMinter::mint()` throws `DoiPrefixNotConfiguredException`
And   no DOI is persisted and no deposit record is created

### Rule: Automatic DOI registration (BR-1, BR-4)

#### Scenario: Successful DOI registration on publication

Given the article is published and has a DOI from publication
When  the deposit job executes
Then  the existing DOI is reused (not regenerated)
And   XML v5.3.1 is built, a POST to Crossref is performed
And   on a successful response: the DOI registration date is saved on the article
And   a deposit is recorded with status "accepted" and the Crossref response
And   the `article.doi_deposited` event is recorded

#### Scenario: Deposit job for a legacy article without a DOI

Given a published article without a DOI (published before AC-2 or without prefix)
When  the deposit job executes
Then  a DOI is minted and persisted on the article before the deposit attempt
And   retries reuse the same DOI

#### Scenario: Persist failure during deposit

Given a legacy article without a DOI
And   the article's DOI write fails before the deposit attempt
When  the deposit job executes
Then  the job logs the persist error and continues with the minted DOI
And   the deposit XML contains the minted DOI
And   the DOI is reserved in the deposit record before the network call
And   on a successful response: the DOI and the registration date are saved on the article together
And   on a failed response: a retry reuses the DOI reserved in the previous deposit record instead of minting a new one

### Rule: Error handling (BR-3)

#### Scenario: Error during deposit

Given the Crossref endpoint is unreachable or returned an error
When  the job executes the deposit
Then  the deposit is recorded with status "failed" and the error text
And   the job will be retried after 60 seconds (first retry), then 300, then 900
And   the retry uses the same DOI as the first attempt
But   after 3 failures, the job is removed from the queue

### Rule: Manual backfill (AC-9)

#### Scenario: Running backfill for articles without a DOI

Given there are published articles without a DOI registration date
And   Crossref is enabled and the prefix is configured
When  the administrator runs `crossref:backfill`
Then  a deposit job is created for each such article
And   `--dry-run` shows the list of articles without sending

#### Scenario: Backfill with Crossref disabled or prefix missing

Given Crossref is disabled or the prefix is not configured
When  the administrator runs `crossref:backfill`
Then  the command exits with an error and no deposit jobs are created
