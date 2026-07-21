# SPEC-17: Funding and Grant Information

Authors can specify funding organisations and grant numbers during submission.
Metadata is exported to Crossref (fundref), JATS, and DOAJ OAI-PMH.

Depends on: SPEC-01, SPEC-08, SPEC-10

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: Author can add one or more funding entries during submission (funder name, funder identifier, award number)
- AC-2: Author can edit funding information during revision
- AC-3: Funding data is stored as a JSON array on the article
- AC-4: Crossref deposit XML includes `<fr:program name="fundref">` with funder assertions
- AC-5: JATS export includes `<funding-group>` with `<award-group>` elements
- AC-6: DOAJ OAI-PMH format includes `<funding>` elements per funder
- AC-7: Funding information is displayed read-only on the article detail pages (author and editorial)

## UI/UX Notes

- Alpine.js repeater "Добавить спонсора" with three fields: organisation, identifier, award number
- Funder name is required; identifier and award number are optional
- Read-only display on author show and editorial show views
- Section placed above the copyright agreement in submission forms

## Business Rules

- BR-1: An article can have zero or more funding entries
- BR-2: Each entry requires at least a funder name
- BR-3: Funder identifier is a DOI, ISNI, or ROR URL
- BR-4: Award number is free-text

## Data Model

### Article — new column
| Column | Type | Description |
|--------|------|-------------|
| `funding` | json nullable | Array of `{funder_name, funder_identifier, award_number}` |

## Behavior

### Rule: Adding funding during submission (AC-1, BR-1, BR-2)

Given the author is on the submission form
When  they add funder name, optional identifier and award number
Then  the data is stored as JSON on the article

### Rule: Crossref fundref export (AC-4)

Given an article with funding data and Crossref enabled
When  DOI deposit XML is built
Then  `<fr:program name="fundref">` is included inside `<journal_article>`

### Rule: JATS export (AC-5)

Given an article with funding data
When  JATS XML is generated
Then  `<funding-group><award-group>` is included inside `<article-meta>`

### Rule: DOAJ export (AC-6)

Given an article with funding data
When  DOAJ OAI-PMH record is built
Then  each funder produces a `<funding>` element
