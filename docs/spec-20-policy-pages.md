# SPEC-20: Public Policy Pages

The journal must expose dedicated policy pages — Peer Review Process, Publication Ethics, and Archiving — as required by Crossref and other academic indexing/database standards. Each page is a CMS-managed `Page` row with a fixed slug, rendering via a shared Blade template.

Depends on: —

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: `/peer-review` renders the peer review policy as a standalone page with `<title>` "Рецензирование — Global Campus RU"
- AC-2: `/publication-ethics` renders the publication ethics policy as a standalone page with `<title>` "Публикационная этика — Global Campus RU"
- AC-3: `/archiving` renders the archiving policy as a standalone page with `<title>` "Архивирование — Global Campus RU"
- AC-4: All three pages are CMS-manageable via Filament `PageResource` (slug, title, body)
- AC-5: A "Политики журнала" footer section on every public page links to the three pages
- AC-6: The `/about` page body includes cross-reference links to the three policy pages
- AC-7: Policy pages are accessible to anonymous visitors (no auth required)
- AC-8: A non-existent slug returns a 404

## Behavior

### Background
Given the database has been seeded with Page rows for slugs `peer-review`, `publication-ethics`, and `archiving`

### Rule: Anonymous visitor can view any policy page

#### Scenario: Peer Review policy

Given the `peer-review` Page exists with `title = "Рецензирование"`
When a visitor navigates to `/peer-review`
Then the page displays `title` and `body` content
And the HTML `<title>` tag is "Рецензирование — Global Campus RU"

#### Scenario: Publication Ethics policy

Given the `publication-ethics` Page exists
When a visitor navigates to `/publication-ethics`
Then the page displays `title` and `body` content

#### Scenario: Archiving policy

Given the `archiving` Page exists
When a visitor navigates to `/archiving`
Then the page displays `title` and `body` content

### Rule: Footer links to policy pages

#### Scenario: Footer renders policy links

Given any public page
When the footer is rendered
Then the "Политики журнала" section contains links to `/peer-review`, `/publication-ethics`, and `/archiving`

### Rule: 404 for missing policy page

#### Scenario: Unseeded slug

Given the `archiving` Page does not exist in the database
When a visitor navigates to `/archiving`
Then a 404 response is returned
