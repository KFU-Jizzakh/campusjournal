# C4 Context — Journal Management System

Level 1: the journal system as a black box, its users, and the external
systems it talks to. The single source of truth for behavior is the test
suite (see `tests/Feature/`); this document describes structure only.

```mermaid
C4Context
    title Global Campus RU — Journal Management System (Context)

    Person(reader, "Anonymous Reader", "Browses issues and articles, downloads PDFs, uses search and exports")
    Person(author, "Author / Coauthor", "Submits manuscripts, revises them, discusses with editors, approves galleys")
    Person(reviewer, "Reviewer", "Accepts or declines invitations, writes reviews, discusses with the editor")
    Person(sectionEditor, "Section Editor", "Runs the workflow on articles assigned to their rubric")
    Person(leadership, "Editor-in-Chief / Managing Editor", "Assigns editors and reviewers, decides, publishes, retracts")
    Person(admin, "Admin", "Full Filament access: users, settings, reference data, event log")
    Person(contentManager, "Content Manager", "Maintains pages, events, conferences, organisations in Filament")

    System(journal, "Journal Management System", "Laravel monolith: public site, editorial workflow, peer review, publishing, DOI registration")

    System_Ext(crossref, "Crossref", "DOI deposit API (XML 5.3.1), Crossmark update metadata")
    System_Ext(smtp, "SMTP Relay", "Queued notification and verification email")
    System_Ext(harvester, "OAI-PMH Harvesters / Google Scholar", "Harvest Dublin Core, DOAJ and Crossref metadata via /oai")
    System_Ext(cdn, "Crossmark Widget CDN", "Serves crossmark.js and the widget button for published articles")

    Rel(reader, journal, "Reads published content, search, BibTeX/RIS/JATS exports, OAI-PMH")
    Rel(author, journal, "Submits and revises manuscripts, response letters, galley approval")
    Rel(reviewer, journal, "Accepts/declines/completes reviews, reads manuscripts and response letters")
    Rel(sectionEditor, journal, "Manages assigned submissions end-to-end")
    Rel(leadership, journal, "Assignment, decisions, publishing, retraction")
    Rel(admin, journal, "Administers users, roles, settings, issues, event log")
    Rel(contentManager, journal, "Maintains CMS content")

    Rel(journal, crossref, "Deposits and re-deposits DOI metadata (queued job)")
    Rel(journal, smtp, "Sends notifications (queued)")
    Rel(harvester, journal, "Harvests metadata (public /oai endpoint)")
    Rel(reader, cdn, "Loads the Crossmark widget")
```

## System responsibilities

- **Public site** — published issues and articles, PDF downloads, search,
  exports (BibTeX/RIS/JATS), CMS pages, events, conferences, OAI-PMH.
- **Peer review** — round-based review lifecycle with blind-review
  support, reviewer invitations and re-requests, response letters.
- **Editorial workflow** — assignment (single and bulk with
  conflict-of-interest guards), decisions, copyediting, production,
  galley approval, issue publishing, retraction and corrections.
- **Publishing infrastructure** — DOI minting and Crossref deposits,
  Crossmark updates, article statistics, indexing (sitemap, Scholar
  meta tags).

## Trust boundaries

- Everything under `/dashboard/*` is authenticated, permission-gated
  and email-verified; row-level access is enforced by policies.
- The Filament panel at `/admin` is admin-only, except content-manager
  resources restricted by `canAccess`.
- Manuscript files live on a private disk and are streamed through
  authorization-checked controller actions, never by public URL.
