# Roles & Access Matrix

Reference for the seven roles of the journal system: which permissions each
role holds, which surfaces it can enter, and which row-level rules apply
inside the editorial workflow.

Sources of truth: `RoleSeeder` (permissions), `routes/web.php` (route
middleware), `app/Policies/*` (row-level rules), Filament resources
(`canAccess` / `canAccessPanel`).

## Authorization model

Two layers work together:

1. **Route middleware** `permission:<name>` (Spatie) gates entire route
   groups, e.g. `permission:manage-submissions` for `/dashboard/editorial/*`.
2. **Laravel Policies** (`ArticlePolicy`, `ReviewPolicy`, `DiscussionPolicy`)
   enforce row-level access within those groups — e.g. a section editor may
   manage only articles assigned to them (`editor_id = user->id`).

Email verification is required for the dashboard (`verified` middleware).

## Roles × Permissions

| Permission | admin | editor-in-chief | managing-editor | section-editor | reviewer | author | content-manager |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| `submit-article` | ✅ | ✅ | ✅ | ✅ | — | ✅ | — |
| `review-article` | ✅ | ✅ | ✅ | ✅ | ✅ | — | — |
| `manage-section` | ✅ | ✅ | ✅ | ✅ | — | — | — |
| `manage-issue` | ✅ | ✅ | ✅ | — | — | — | — |
| `publish-issue` | ✅ | ✅ | ✅ | — | — | — | — |
| `manage-users` | ✅ | — | — | — | — | — | — |
| `manage-settings` | ✅ | — | — | — | — | — | — |
| `manage-content` | ✅ | — | — | — | — | — | ✅ |
| `manage-submissions` | ✅ | ✅ | ✅ | ✅ | — | — | — |
| `manage-doi` | ✅ | ✅ | ✅ | — | — | — | — |

Notes:

- `admin` holds all ten permissions but is a role, not a "super-user" flag:
  dashboard routes still check permissions (which admin has), Filament
  resources check `hasRole('admin')` directly.
- `editor-in-chief` and `managing-editor` are identical in permissions;
  they differ by convention/responsibility, not by access.
- `manage-users` / `manage-settings` exist in the seeder but the current
  admin surface for them is Filament, not dashboard routes.

## Surface access

| Surface | Who can enter |
|---|---|
| Public site (`/`, `/issues`, `/articles`, `/about`, …) | Everyone (anonymous) |
| Dashboard home (`/dashboard`) | Any authenticated + verified user |
| `/dashboard/articles/create`, `store`, `edit`, `update`, file upload | `submit-article` |
| `/dashboard/articles/{article}` (submission page) | **Policy-gated, no permission middleware**: submitter, or any credited coauthor (non-draft articles) — so coauthors can follow notification links |
| `/dashboard/articles/{article}/approve-galley`, `request-revision`, `withdraw`, discussions | `submit-article` + policy (submitter for galley actions) |
| `/dashboard/reviews/*` | `review-article` + `ReviewPolicy` (own reviews; admin exception) |
| `/dashboard/editorial/*` | `manage-submissions` + `ArticlePolicy` (workflow scope) |
| `/dashboard/editorial/stats` (analytics) | Inside `manage-submissions`, additionally `publish-issue` (EiC / managing / admin) |
| Filament panel (`/admin`) | `hasRole('admin')` **or** `manage-content` → admin and content-manager |
| Filament — admin-only resources: Users, Articles, Reviews, Issues, Authors, Categories, Copyright Agreements, Outbox Events | `hasRole('admin')` |
| Filament — ungated resources: Pages, Events, Conferences, Organizations, Site Settings | Anyone who can enter the panel (admin, content-manager) |

## Row-level rules (policies)

### ArticlePolicy

| Rule | Who |
|---|---|
| `view` | Submitter always; credited coauthor (via `article_author` → `authors.user_id`) for **non-draft** articles |
| `update` (edit/resubmit) | Submitter, only in `draft` / `revision` |
| `uploadFiles` | Submitter in `draft`/`revision`; editorial workflow scope otherwise |
| `viewEditorial` | EiC / managing / admin — all non-draft; section editor — only `editor_id = own` |
| `assignEditor` | EiC / managing / admin (article must be `submitted`) |
| Workflow actions (`assignReviewer`, `decide`, `sendToCopyediting`, upload copyedited file, `sendToProduction`, galley upload/send) | Editorial workflow scope: leadership — any article; section editor — own articles; article must be in the matching status |
| `publish` | Workflow scope **+** `publish-issue` permission; article `approved`, issue published |
| `approveGalley`, `requestGalleyRevision` | Submitter only (article `awaiting_approval`) |
| `withdraw` | Submitter (any pre-publish status) or editorial workflow scope |
| `retract` | EiC / managing / admin (published articles) |
| `manageCorrections` | Editorial workflow scope |

"Editorial workflow scope" = `canManageWorkflow()`: role in
(`admin`, `editor-in-chief`, `managing-editor`), or `section-editor` with
`article.editor_id = user.id`.

### ReviewPolicy

- `view` / `update` / `accept`: the assigned reviewer (`reviewer_id`), within
  allowed status transitions. Admin may act on any review (editorial
  substitution), still bound by transitions.

### DiscussionPolicy

| Thread | Visible to |
|---|---|
| Any thread | `admin`, `editor-in-chief`, `managing-editor`; `section-editor` if `article.editor_id = own` |
| `article` scope, not bound to a review | All credited authors of the article (submitter + coauthors) |
| Bound to a review | The reviewer assigned to that review |

Creating and resolving threads remains with the submitter (article scope) and
the editorial workflow scope (resolve).

## Dashboard by role

The dashboard is task-oriented; what each role sees on `/dashboard`:

| Role | Inbox tasks ("Требуют внимания") | Other sections |
|---|---|---|
| author | Unsubmitted drafts; revisions to resubmit; galley proofs awaiting approval (inline approve) | "Мои статьи" with search; coauthored articles (read-only rows linked to the submission page); role badges in the user menu |
| reviewer | Pending invitations (inline accept/decline, response deadline); reviews to write (submission deadline) | "Мои рецензии": workload chips (active / completed / avg days / yearly declines), completed reviews with dates |
| section-editor | Own articles' next actions: upload blinded PDF, assign/reassign reviewers (declined or overdue), make decision, send to copyediting/production, send galley | "Дедлайны рецензий" (overdue + next 7 days, own scope); "На контроле" (articles waiting on reviewers/authors, with stale-age signals); editorial stat strip |
| editor-in-chief, managing-editor | Same as section editor but for **all** articles, plus: assign section editor to unassigned submissions; publish approved articles | Same as section editor plus: "Сборка выпуска" (last published issue + accepted/approved articles without an issue); "Статистика редакции" page (funnel, avg time to decision, reviewer turnaround, section-editor workload) |
| admin | Same as editor-in-chief | Same + Filament admin panel (Outbox event log, System Health page: queue, failed jobs, Crossref deposits) |
| content-manager | — (no workflow permissions) | Filament panel: content resources only |

The nav shows role badges in the user dropdown and an open-task counter on
the "Главная" item (computed from the same inbox builder).

## Notes

- **Test users** (password `password1234` for all): `admin@`, `galimov@`
  (editor-in-chief), `managing@`, `section@`, `reviewer@`, `author@`,
  `content@` — all at `globalcampus.local`.
- **Reviewer self-registration**: when the
  `reviewer_self_registration` setting is open (Filament → Site Settings),
  users can take the reviewer role via the registration form or the profile
  toggle, and drop it anytime — except while they have active (pending or
  in-progress) review assignments (`ReviewerRoleRemovalBlockedException`).
  Self-registered reviewers appear in the assignment select with the same
  workload card as appointed ones.
- **Active role switcher**: users with two or more roles
  can pick a working role in the user menu ("Работать как"). The choice is
  session-scoped and only filters which task families the dashboard and the
  nav counter show (author / reviewer / editorial); it never narrows real
  permissions, and an invalid or stale value falls back to "all roles".
- **Multiple roles stack**: e.g. a user with both `section-editor` and
  `editor-in-chief` gets the wider leadership scope (`RoleSeeder`-aware
  checks in `DashboardInbox::editorialArticles`).
- **Coauthors** see the submission page only while the article is not a
  draft; they cannot edit, withdraw, or approve galley proofs. When a
  submission lists the email of an existing verified user as a coauthor,
  that user receives a signed invitation (`invitations.accept`, 7 days) to
  claim the author record; after `Author::claimFor` they automatically get
  the full notification stream for the article. No specific role is
  required to claim — verified email matching the pivot snapshot is the
  ownership proof.
- **Review quality rating** (editors, `manage-submissions`): completed
  reviews can be rated 1–5 on the editorial page; the average feeds the
  reviewer assignment options. Reviewers never see ratings — neither on
  the review page nor in their own dashboard stats.
- **Auto-assignment by rubric**: when a category has a default section
  editor (Filament → Рубрики), new submissions in that rubric silently get
  `editor_id` — leadership can still reassign manually as before.
- Public author profile data (ORCID, affiliation) lives in the `authors`
  table; role membership lives in Spatie's `roles`/`permissions` tables on
  the `users` model.
