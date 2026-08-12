# SPEC-19: Content Manager Role

A dedicated role for managing site content — events, pages, conferences, and organisations — through the Filament admin panel, without access to editorial workflow resources (articles, reviews, issues) or administrative resources (users, settings, copyright agreements).

Depends on: —

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: An admin can assign the "content-manager" role to any user via the User management interface
- AC-2: A user with the "content-manager" role can log in at `/admin` and sees the Filament dashboard
- AC-3: The content manager sees only the "Контент" navigation group in the sidebar, containing Events, Pages, Conferences, and Organisations
- AC-4: The content manager can create, edit, and delete Events — title, description, dates, type (conference/forum/deadline/webinar), location, URL, and published toggle
- AC-5: The content manager can create, edit, and delete Pages — title, slug (disabled on edit for existing pages), and body (RichEditor)
- AC-6: The content manager can create, edit, and delete Conferences — title, slug, description, body, dates, location, URL, and published toggle
- AC-7: The content manager can create, edit, and delete Organisations — name, description, logo upload, website URL, and sort order
- AC-8: The content manager does NOT see editorial navigation items: Articles, Issues, Categories, Authors, Reviews
- AC-9: The content manager does NOT see administrative navigation items: Users, Copyright Agreements
- AC-10: Navigating directly to a restricted resource URL (e.g. `/admin/articles`, `/admin/users`) returns a 403 Forbidden page
- AC-11: Editor-in-chief and managing-editor cannot access `/admin` — they are redirected to the login page with an unauthorised message
- AC-12: Admin retains full access to all Filament resources (editorial, content, and administrative)
- AC-13: A user with both admin and content-manager roles retains full access (admin role overrides content-manager restrictions)

## UI/UX Notes

- The content manager sees the standard Filament login page at `/admin`
- After login, the left sidebar shows a single group "Контент" with four items sorted logically: Events, Pages, Conferences, Organisations
- The Filament dashboard page (widgets) is visible but may show empty or non-relevant widgets — this is acceptable behaviour for the content manager
- The Page resource is moved from the "Настройки" group to the "Контент" group so it appears alongside other content entities
- The account widget in the top-right corner allows the content manager to update their password or log out (standard Filament behaviour)

## Behavior

### Background
Given: the user is authenticated

### Rule: Content Manager Panel Access

#### Scenario: Content manager logs into /admin

Given the user has the "content-manager" role (with `manage-content` permission)
When  they navigate to `/admin`
Then  the login page is displayed
And   after logging in, they see the Filament dashboard
And   the sidebar shows the "Контент" navigation group with Events, Pages, Conferences, and Organisations

#### Scenario: User without manage-content permission visits /admin

Given the user has a role without `manage-content` permission (author, reviewer, section-editor)
When  they navigate to `/admin`
Then  they are redirected to the login page
And   the message "These credentials do not match our records" or an unauthorised message is shown

### Rule: Content Entity CRUD

#### Scenario: Edit an event

Given the content manager is on the event edit page
When  they change the event date and type, then save
Then  the event is updated
And   the public `/events` page reflects the changes

#### Scenario: Delete a page

Given the content manager is on the pages list
When  they select a page and confirm deletion
Then  the page is soft-deleted
And   navigating to the page's public URL returns a 404

#### Scenario: Create a conference

Given the content manager is on the conferences list page
When  they fill in the title, slug, dates, description, and toggle "Published"
Then  the conference is saved
And   the public conference listing reflects the new entry

#### Scenario: Edit an organisation

Given the content manager is on the organisation edit page
When  they update the logo and website URL, then save
Then  the organisation is updated
And   the public listing of partner organisations shows the new logo and link

### Rule: Restricted Resource Access

#### Scenario: Content manager tries to access Articles resource

Given the content manager is logged into `/admin`
When  they navigate directly to `/admin/articles`
Then  they see a 403 Forbidden page
And   the "Статьи" navigation item is not visible in the sidebar

#### Scenario: Content manager tries to access User management

Given the content manager is logged into `/admin`
When  they navigate directly to `/admin/users`
Then  they see a 403 Forbidden page
And   the "Пользователи" navigation item is not visible in the sidebar

#### Scenario: Content manager tries to access Reviews

Given the content manager is logged into `/admin`
When  they navigate directly to `/admin/reviews`
Then  they see a 403 Forbidden page

### Rule: Editorial Role Exclusion

#### Scenario: Editor-in-chief visits /admin

Given the user has the "editor-in-chief" role but NOT the `manage-content` permission
When  they navigate to `/admin`
Then  they are redirected to the login page
But   their dashboard at `/dashboard/*` remains fully accessible

#### Scenario: Managing editor visits /admin

Given the user has the "managing-editor" role but NOT the `manage-content` permission
When  they navigate to `/admin`
Then  they are redirected to the login page
But   their dashboard at `/dashboard/*` remains fully accessible

### Rule: Combined Roles

#### Scenario: User has both admin and content-manager roles

Given the user has both the "admin" and "content-manager" roles
When  they log into `/admin`
Then  they see all navigation groups: "Контент" and "Настройки" with all resources
And   they have full access to editorial resources (Articles, Issues, Categories, Authors, Reviews)
And   they have full access to administrative resources (Users, Copyright Agreements)
