# SPEC-15: Structured References

Article references are stored in a normalized relational model — each reference is a separate row in the `references` table linked to the article. The system extracts DOIs from reference text, counts citations in the article body, and supports individual reference export.

Depends on: SPEC-10

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: The editor manages article references in the Filament admin panel via a structured repeater (one text field per reference), not a single textarea
- AC-2: Each reference is stored as a separate row in the `references` table, linked to the article via `article_id`, with explicit ordering
- AC-3: DOI is automatically extracted from the reference text via regex (pattern `10.\d{4,9}/[^\s,]+`) and stored in a separate `doi` column
- AC-4: Citation count is calculated — the system finds bracketed citations in the article body (`[1]`, `[1,2]`, `[1-3]`, `[1, 2, 3]`) and records the count for each reference
- AC-5: References are exported as `<ref-list>` with `<ref><mixed-citation>` elements and `<pub-id pub-id-type="doi">` when a DOI is present (JATS XML)
- AC-6: An individual reference can be exported to RIS and BibTeX formats

## UI/UX Notes

- In Filament admin article form — a `Repeater` block instead of a single `Textarea` for `references_list`
- Each repeater item: a `Textarea` for the raw reference text, a read-only `TextInput` for the extracted DOI, a read-only `TextInput` for the citation count
- Author dashboard — a `textarea` (one reference per line, same as OJS)
- Existing articles' `references_list` data is migrated: one row per line, DOIs extracted

## Business Rules

- BR-1: References belong to an article (FK `article_id`, cascade on delete)
- BR-2: References are ordered by the `order` column; order is maintained when syncing
- BR-3: DOI extraction happens at save time via regex; the extracted DOI is stored but never blocks saving — invalid/missing DOI is acceptable
- BR-4: Citation counting runs whenever references are synced; it strips HTML from the article body, finds numeric references in brackets, and maps them to references by position (1-based)
- BR-5: Syncing references is destructive: existing references for the article are deleted, then new rows are created from the submitted list
- BR-6: Existing `references_list` text data is migrated to the `references` table during deployment — each non-empty line becomes a reference row

## Behavior

### Background
Given the admin is authenticated

### Rule: Managing references via Filament admin (BR-1, BR-2, BR-3, BR-4, BR-5)

#### Scenario: Adding references to an article

Given the admin opens the article edit form in Filament
When  they enter reference texts in the Repeater, one per item, and save
Then  each reference is stored as a separate Reference row with `order` matching the repeater position
And   DOIs are extracted and stored in the `doi` column where found
And   citation counts are calculated from the article body
And   the article's old references are replaced entirely

#### Scenario: Editing existing references

Given the article has 3 references, the admin opens the edit form
When  the admin changes the text of reference 2, removes reference 3, and saves
Then  2 references remain (old reference 3 deleted, reference 2 text updated)
And   DOIs are re-extracted
And   citation counts are recalculated

### Rule: JATS export (AC-5)

#### Scenario: References in generated JATS XML

Given a published article has references in the `references` table
When  JATS XML is generated via `JatsXmlBuilder`
Then  a `<ref-list>` block is rendered inside `<back>`
And   each reference is a `<ref id="refN">` element with `<mixed-citation>` containing the raw text
And   when a reference has a DOI, `<pub-id pub-id-type="doi">` is embedded

#### Scenario: Article with no references

Given a published article has no references
When  JATS XML is generated
Then  a self-closing `<back/>` tag is output

### Rule: Individual reference export (AC-6)

#### Scenario: Export reference as RIS

Given a Reference record
When  the RIS format is requested
Then  a valid RIS record is returned with `TY`, `DO` (DOI), `ER` tags

#### Scenario: Export reference as BibTeX

Given a Reference record
When  the BibTeX format is requested
Then  a valid BibTeX entry is returned with `doi`, `note` (raw text) fields

### Rule: Migration of legacy data (BR-6)

#### Scenario: Existing references_list is migrated

Given an article has text in the `references_list` column with DOIs embedded in some lines
When  the migration runs
Then  each non-empty line becomes a Reference row with the correct `order`
And   DOIs are extracted from each line into the `doi` column
And   the `references_list` column is dropped from the `articles` table
