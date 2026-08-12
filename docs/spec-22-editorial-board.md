# SPEC-22: Editorial Board in About Page

The editorial board composition must be published on the journal's website for indexing systems (DOI/Crossref, DOAJ, RSCI/РИНЦ) that verify the presence of the editorial board. Instead of a standalone `/editorial-board` page, the board is published as a "Редакционная коллегия" section inside the "О журнале" (About) page, rendered as part of the CMS-managed `Page` body with the slug `about`. This matches the Open Journal Systems pattern where the editorial team lives under the journal's About area. Crossref does not require editorial board metadata, so this does not affect DOI registration.

Depends on: SPEC-20

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: The "О журнале" page (`/about`) displays a "Редакционная коллегия" section listing the full board: name, role, degree and affiliation for every member
- AC-2: The section is part of the CMS-managed `about` Page body, editable via Filament `PageResource`
- AC-3: There is no standalone `/editorial-board` page and no nav item "Редколлегия" in the desktop nav, mobile nav, or footer
- AC-4: The page is accessible to anonymous visitors (no auth required)
- AC-5: The seeded board data contains at least the editor-in-chief and full board members

## UI/UX Notes

- The section is placed inside the `about` page body after the "Учредители" section as a heading + unordered list (or short paragraphs per member)
- Board composition is maintained by content managers as plain HTML content of the About page
- On existing (already-provisioned) databases the section appears after the next `php artisan migrate:fresh --seed`; fresh installs get it from the seeder directly

## Business Rules

- BR-1: The editorial board content is static HTML in the `about` Page body — no dedicated `EditorialBoardMember` entity exists
- BR-2: Board member data must remain fully visible on the site (names, roles, affiliations) since indexing systems verify the published board composition

## Behavior

### Background
Given the database has been seeded with a Page row for the slug `about` whose body contains a "Редакционная коллегия" section listing the editor-in-chief and board members

### Rule: Anonymous visitor can view the editorial board on the About page

#### Scenario: About page renders the editorial board section

Given the `about` Page exists with `title = "О журнале"` and a body containing the "Редакционная коллегия" section
When a visitor navigates to `/about`
Then the page displays the "Редакционная коллегия" heading
And the page lists the editor-in-chief and each board member with name and role

### Rule: No standalone editorial board page

#### Scenario: Old route is not available

Given the public site navigation
When a visitor opens the nav and footer
Then there is no link to `/editorial-board`
And no route with the name `editorial-board` exists
