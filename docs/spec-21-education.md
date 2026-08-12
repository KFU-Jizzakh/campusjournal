# SPEC-21: Education Section

The journal must expose an "Образование" (Education) page listing educational services offered by the journal: повышение квалификации, профессиональная переподготовка, получение свидетельства о публикации, получение свидетельства об участии в семинарах/вебинарах, стажировки, получение благодарности от научного руководителя. The page is a CMS-managed `Page` row with the fixed slug `education`, rendering via the shared Blade template, linked from the desktop nav, mobile nav and footer.

Depends on: SPEC-20

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: `/education` renders the education page as a standalone page with `<title>` "Образование — Global Campus RU"
- AC-2: The page body lists all six services with a heading and a short description for each
- AC-3: The page is CMS-manageable via Filament `PageResource` (slug, title, body)
- AC-4: A nav item "Образование" is present in the desktop nav, the mobile nav, and the "Разделы" footer section on every public page
- AC-5: The page is accessible to anonymous visitors (no auth required)
- AC-6: A non-existent `education` Page row returns a 404
- AC-7: `/education` is included in the generated sitemap

## UI/UX Notes

- Nav placement: directly after "Авторам" (thematically related), in all three locations
- Render through the generic policy-page template `pages.show` (prose HTML, `@purify`)

## Business Rules

- BR-1: The section is a single CMS page; the service list is free-form HTML maintained by content managers, not a structured catalog
- BR-2: The slug is fixed (`education`); the page title defaults to "Образование"

## Behavior

### Background
Given the database has been seeded with a Page row for the slug `education` with `title = "Образование"` and a body containing six services

### Rule: Anonymous visitor can view the education page

#### Scenario: Education page renders

Given the `education` Page exists with `title = "Образование"`
When a visitor navigates to `/education`
Then the page displays `title` and `body` content
And the HTML `<title>` tag is "Образование — Global Campus RU"
And the body lists "Повышение квалификации", "Профессиональная переподготовка", "Получение свидетельства о публикации", "Получение свидетельства об участии в семинарах/вебинарах", "Стажировки" and "Получение благодарности от научного руководителя"

### Rule: Nav links to the education page

#### Scenario: Nav renders education link

Given any public page
When the header and footer are rendered
Then the desktop nav, the mobile nav and the "Разделы" footer section each contain a link to `/education`

### Rule: 404 for missing education page

#### Scenario: Unseeded slug

Given the `education` Page does not exist in the database
When a visitor navigates to `/education`
Then a 404 response is returned

### Rule: Sitemap contains the education page

#### Scenario: Sitemap generation

Given the sitemap generation command runs
Then the generated `sitemap.xml` contains the `/education` URL
