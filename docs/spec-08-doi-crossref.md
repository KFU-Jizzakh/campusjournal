# SPEC-08: DOI Registration (Crossref)

Automatic DOI registration for an article via Crossref upon publication. The DOI is generated from a template, a deposit XML v5.3.1 is built, and sent to Crossref. The result is logged. A manual run is provided for articles without a DOI.

Depends on: SPEC-04

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: On article publication, if Crossref is enabled in settings, a DOI registration job is automatically queued
- AC-2: The DOI is generated from the configuration template (default: `{prefix}/kfujournal.{year}.{volume}.{article_id}`)
- AC-3: If the article already has a DOI (manually set), a new one is not generated
- AC-4: A deposit XML v5.3.1 is built based on article metadata, authors, and issue
- AC-5: The XML is sent via a multipart POST request to the Crossref endpoint
- AC-6: The result (status, HTTP code, response body) is saved in the deposits table
- AC-7: On success — the article's DOI registration date is updated, the `article.doi_deposited` event is recorded
- AC-8: On error — the job retries up to 3 times with exponential backoff (60/300/900 sec)
- AC-9: The `crossref:backfill` command sends deposits for published articles without a DOI (supports `--dry-run`)

## UI/UX Notes

- The functionality is entirely background, with no user interface (except the Filament admin panel)
- A deposit can be triggered manually via Filament admin
- Filament displays the status of the latest deposit for the article

## Business Rules

- BR-1: Crossref registration is triggered only if the service is enabled in settings
- BR-2: If the article already has a manually set DOI, auto-generation is not performed
- BR-3: Each deposit attempt is logged as a separate record (audit trail)
- BR-4: The deposit is executed asynchronously via a queue, without blocking publication

## Behavior

### Background
Given the Crossref service is enabled in configuration

### Rule: Automatic DOI registration (BR-1, BR-2, BR-4)

#### Scenario: Successful DOI registration on publication

Given the article is in "In Production" status
When  the editor publishes the article (see SPEC-04)
Then  a DOI registration job is queued
And   in the job: a DOI is generated, XML v5.3.1 is built, a POST to Crossref is performed
And   on a successful response: the DOI and registration date are saved on the article
And   a deposit is recorded with status "accepted" and the Crossref response
And   the `article.doi_deposited` event is recorded

#### Scenario: Article with a manually set DOI

Given the article has a filled-in DOI before publication
When  the article is published
Then  DOI auto-generation is not performed, the existing DOI is preserved
But   no new DOI is generated

### Rule: Error handling (BR-3)

#### Scenario: Error during deposit

Given the Crossref endpoint is unreachable or returned an error
When  the job executes the deposit
Then  the deposit is recorded with status "failed" and the error text
And   the job will be retried after 60 seconds (first retry), then 300, then 900
But   after 3 failures, the job is removed from the queue

### Rule: Manual backfill (AC-9)

#### Scenario: Running backfill for articles without a DOI

Given there are published articles without a DOI registration date
When  the administrator runs `crossref:backfill`
Then  a deposit job is created for each such article
And   `--dry-run` shows the list of articles without sending
