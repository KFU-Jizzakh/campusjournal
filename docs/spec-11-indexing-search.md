# SPEC-11: Indexing, Sitemap, and Search

The journal's public site is indexed by search engines through a static sitemap.xml, Google Scholar meta tags on article pages, and full-text search over published articles.

Depends on: SPEC-04

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: The static sitemap.xml serves all public URLs (articles, issues, authors, news, conferences, events, OAI)
- AC-2: The sitemap generation command collects URLs and writes sitemap.xml
- AC-3: The robots.txt generation command creates the file with a link to the sitemap
- AC-4: On the article page, citation_* meta tags for Google Scholar are output in the head
- AC-5: Meta tags include: title, authors, publication date, journal name, volume/issue, pages, DOI, PDF link, ISSN (both print and electronic from the `journal_issn_print` and `journal_issn_electronic` site settings)
- AC-6: Search performs matching against title, abstracts, and keywords of published articles
- AC-7: Search results are displayed with pagination and snippets

## UI/UX Notes

- The sitemap is generated manually or on deploy; no auto-regeneration
- If the sitemap.xml file is absent, an empty valid sitemap is returned
- robots.txt is generated on the server, not committed to the repository
- A search bar on the public site; results with title, authors, category, issue
- URL priority in sitemap: articles 0.8, issues 0.7, homepage 1.0

## Business Rules

- BR-1: Only published articles and issues are included in the sitemap
- BR-2: Search works only on published articles
- BR-3: Special LIKE characters in the search query are escaped

## Behavior

### Rule: Sitemap generation (BR-1)

#### Scenario: Sitemap generation

Given the system has published articles, issues, authors with publications, news, conferences, events
When  the administrator runs the sitemap generation command
Then  sitemap.xml is created with all public URLs
And   for each URL, priority and changefreq are specified

### Rule: Full-text search (BR-2, BR-3)

#### Scenario: Keyword search

Given the system has published articles
When  the user enters a query in the search bar and clicks "Search"
Then  the system performs a search across fields: title, abstracts (ru/en), keywords
And   results are sorted by publication date (newest first)
And   results display title, authors, category, issue, and a text snippet

#### Scenario: Search with an empty query

Given the user opens the search page
When  the query is empty or absent
Then  no search is performed, only the search form is displayed

#### Scenario: Special character escaping

Given the user enters a query containing % or _ characters
When  the search is performed
Then  special characters are escaped, the search works correctly
But   the query does not cause a database error

### Rule: Google Scholar (AC-4, AC-5)

#### Scenario: Meta tags on the article page

Given the article is published
When  a search bot requests the article page
Then  citation_* meta tags are present in the head: title, authors, date, journal, volume/issue, pages, DOI, PDF URL, ISSN (both print and electronic from site settings)
