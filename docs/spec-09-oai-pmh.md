# SPEC-09: OAI-PMH Repository

An OAI-PMH 2.0 server for metadata exchange with aggregators (DOAJ, Google Scholar, BASE, CORE). Supports 6 verbs, 3 metadata formats, a two-level set hierarchy, and tombstone records for deleted articles.

Depends on: SPEC-04

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: The `GET|POST /oai` endpoint is publicly accessible without authentication
- AC-2: The response is in XML format with UTF-8 encoding
- AC-3: Supported verbs: Identify, ListMetadataFormats, ListSets, ListIdentifiers, ListRecords, GetRecord
- AC-4: oai_dc format — Dublin Core (title, authors, subject, description, publisher, date, type, DOI, language, rights)
- AC-5: oai_doaj format — DOAJ article schema (journal, ISSN from `journal_issn_electronic` site setting, volume/issue, pages, DOI, authors, affiliations, abstract, keywords)
- AC-6: crossref format — Crossref XML 5.3.1
- AC-7: Sets: by category and by issue; empty categories are excluded; ListSets is cached for 5 minutes
- AC-8: Resumption token: stateless base64url(JSON) + HMAC-SHA256 signature; TTL is configurable
- AC-9: Soft-deleted articles are returned as a tombstone with header "deleted" and no metadata

## UI/UX Notes

- Fully machine-readable interface, no user-facing UI
- Record identifier: `oai:{repository_id}:article:{article_id}`
- The repository identifier is configured via an environment variable

## Business Rules

- BR-1: Only published articles are exported
- BR-2: Soft-deleted articles are returned as tombstones
- BR-3: The resumption token is signed with the application secret key — token substitution is impossible

## Behavior

### Rule: Basic verbs (AC-3, AC-4, AC-5, AC-6)

#### Scenario: Identify

Given the client sends a request with the Identify verb
When  the system processes the request
Then  XML is returned with repository information (name, URL, email, OAI-PMH versions, deleted records policy)

#### Scenario: Unsupported verb

Given the client sends a request with an unknown verb
When  the system processes the request
Then  a badVerb error is returned

### Rule: Record lists and sets (AC-7, AC-8)

#### Scenario: ListRecords with a set

Given the client sends a ListRecords request with format oai_dc and a category set
When  the system processes the request
Then  a list of records for articles in the category is returned in Dublin Core format
And   if there are more records than the page size — a resumption token is issued

#### Scenario: Resume via resumption token

Given the previous response contained a resumption token
When  the client sends a ListRecords request with this token
Then  the system decodes and verifies the token signature
And   the next page of records with the same filter is returned

#### Scenario: Expired resumption token

Given the resumption token has expired (TTL elapsed)
When  the client sends a request with the expired token
Then  a badResumptionToken error is returned
But   no records are returned

### Rule: Deleted records (BR-2)

#### Scenario: GetRecord with tombstone

Given the article has been soft-deleted
When  the client requests GetRecord by the identifier of the deleted article
Then  a tombstone is returned with header "deleted" and no metadata

### Rule: Format validation (AC-4, AC-5, AC-6)

#### Scenario: Unsupported metadata format

Given the client requests a non-existent metadata format
When  the system processes the request
Then  a cannotDisseminateFormat error is returned
But   no records are returned
