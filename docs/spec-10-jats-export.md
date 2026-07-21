# SPEC-10: JATS XML Export

Export an article in JATS/NLM Publishing Tag Set 1.3 format — for aggregators, archives (PMC-style), and external systems. Available via a public URL and OAI-PMH. Supports uploading a pre-built JATS XML to replace the auto-generated one.

Depends on: SPEC-04

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: The public URL serves the XML of a published article with the correct Content-Type
- AC-2: OAI-PMH with the nlm format embeds JATS in the response
- AC-3: If a pre-built JATS XML is attached to the article, its content is served instead of the generated one
- AC-4: The uploaded JATS is checked for well-formed XML; on error — automatic fallback to generated XML and the error is written to the log
- AC-5: Article metadata is mapped to JATS elements (title, DOI, abstracts, keywords, date, pages, volume/issue, authors with ORCID and affiliations)
- AC-6: The HTML body of the article is converted to JATS body: paragraphs, sections/headings, lists, quotes, tables, links, images, bold/italic
- AC-7: Article references from the structured `references` table are rendered as a `<ref-list>` block with individual `<ref>` elements; each `<ref>` contains `<mixed-citation>` text and `<pub-id pub-id-type="doi">` when a DOI is available

- AC-8: Journal ISSN (print and electronic) is read from the `journal_issn_print` and `journal_issn_electronic` site settings, output as `<issn pub-type="ppub">` and `<issn pub-type="epub">` respectively

## UI/UX Notes

- Public export — only for articles in "Published" status
- Response with Content-Disposition: attachment header with a filename
- In Filament admin — a warning if the uploaded JATS override contains XML errors

## Business Rules

- BR-1: Priority: uploaded JATS XML > auto-generated
- BR-2: On parsing error of the uploaded JATS — fallback to generation, the user is not blocked
- BR-3: Author affiliations are deduplicated by organization name
- BR-4: The electronic ISSN (`journal_issn_electronic` site setting) is used for `pub-type="epub"`; the print ISSN (`journal_issn_print` site setting) for `pub-type="ppub"`

## Behavior

### Rule: Public export (BR-1, BR-2)

#### Scenario: Automatic JATS generation

Given the article is in "Published" status
When  the client requests the public JATS export URL
Then  JATS XML with full metadata is returned
And   the response header contains Content-Disposition: attachment

#### Scenario: Using the uploaded JATS override

Given a published article has an attached pre-built JATS XML, the content is well-formed
When  the client requests the JATS export
Then  the uploaded file content is returned as-is (without regeneration)

#### Scenario: Error in the uploaded JATS override

Given a pre-built JATS XML is attached to the article, the content is broken
When  the client requests the JATS export
Then  the parsing error is written to the log
And   auto-generated JATS XML is returned
But   the client does not receive an error — the export always succeeds

#### Scenario: ISSN in JATS front matter

Given the journal has print and/or electronic ISSN configured via site settings
When  the system builds JATS XML
Then  `<issn pub-type="ppub">` is output from the `journal_issn_print` setting (if present)
And   `<issn pub-type="epub">` is output from the `journal_issn_electronic` setting (if present)
And   when neither ISSN is set, no `<issn>` elements are output

### Rule: OAI-PMH integration (AC-2)

#### Scenario: JATS via OAI-PMH

Given the client sends an OAI-PMH GetRecord request with the nlm format
When  the system builds JATS XML
Then  JATS is embedded in the OAI-PMH response in the metadata element
And   the XML prologue is omitted since the response is already XML

### Rule: References in JATS (AC-7)

#### Scenario: Export with references

Given a published article has references in the `references` table
When  the client requests the JATS export
Then  the `<back>` element contains a `<ref-list>` with one `<ref>` per reference
And   each `<ref>` has an `id` attribute (e.g., `ref1`, `ref2`)
And   `<pub-id pub-id-type="doi">` is present for references with a DOI

#### Scenario: Export without references

Given a published article has no references
When  the client requests the JATS export
Then  a self-closing `<back/>` element is output
