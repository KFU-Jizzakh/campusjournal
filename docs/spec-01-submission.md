# SPEC-01: Submission and Revision

The author submits a manuscript to the journal through the dashboard, tracks its status, sees editorial decisions and anonymized reviews, and upon revision request — makes changes and resubmits the article for peer review.

Depends on: SPEC-14

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: The author fills in the submission form (title up to 500 characters, Russian abstract — required, English abstract — optional, category, keywords, PDF up to 50 MB, author and co-author data) and clicks "Submit" — the system creates the article with status "Submitted"
- AC-2: The author opens the dashboard and sees a list of their articles with status badges
- AC-3: The author opens the article page and sees: title, abstracts, authors, status, submission date
- AC-4: If the editor has made a decision — the author sees an "Editorial Decision" block with the verdict and comment
- AC-5: If there are completed reviews — the author sees anonymized feedback ("Reviewer 1", "Reviewer 2") with recommendations and comments
- AC-6: If the article is in "Revision" status — the author sees an "Edit" button, can modify data, replace the PDF, and resubmit
- AC-7: On resubmission after revision, the decision, copyediting, and production fields are cleared; the section editor is retained

## UI/UX Notes

- The author does not see the reviewer's name — displayed as "Reviewer N" everywhere
- The author does not see the reviewer's confidential comments to the editor
- The "Edit" button is visible only in "Draft" and "Revision" statuses
- The discussions block is not displayed for articles in "Draft" and "Published" statuses

## Business Rules

- BR-1: The author sees only their own articles
- BR-2: The PDF is stored on a secure disk, not accessible anonymously
- BR-3: ORCID is validated against the `0000-0000-0000-0000` format
- BR-4: Co-authors without an ORCID are created as new records; those with an ORCID are looked up or created
- BR-5: During revision, data is updated, decision/copyediting/production fields are cleared, status changes to "Submitted"
- BR-6: In statuses other than "Revision", a simple data update is applied
- BR-7: When replacing the PDF, the old file is deleted from disk

## Behavior

### Background
Given: the user is authenticated and has the author role

### Rule: Submission form validation (BR-3, BR-4)

#### Scenario: Successful manuscript submission

Given the user opens the submission form
When  they fill in the required fields, accept the license agreement (see SPEC-14), and click "Submit"
Then  the article is created with status "Submitted"
And   the PDF is saved on a secure disk
And   the author and co-authors are synchronized (created or updated, linked with order)
And   the `submission.created` event is recorded
And   the author is redirected to the article page with the message "Manuscript successfully submitted"

#### Scenario: Non-PDF file upload

Given the user fills in the form
When  they attach a non-PDF file
Then  validation rejects the upload with an error message
But   the article is not created

#### Scenario: ORCID already taken by another author

Given the user fills in the form
When  they specify an ORCID belonging to another author
Then  validation returns the error "Each author must have a unique ORCID"
But   the article is not created

### Rule: Revision after editorial decision (BR-5, BR-6, BR-7)

#### Scenario: Successful revision

Given the article is in "Revision" status, belongs to the user, the editor has left a decision and comment
When  the user clicks "Edit", makes changes, and clicks "Save changes"
Then  the article data is updated
And   the decision fields are cleared
And   the copyediting and production fields are cleared
And   the section editor is retained
And   the status changes from "Revision" to "Submitted"
And   the `submission.revised` event is recorded
And   the section editor receives a notification about resubmission
And   the author is redirected to the article page

#### Scenario: Editing attempt in invalid status

Given the article is not in "Draft" or "Revision" status
When  the author opens the article page
Then  the "Edit" button is hidden
But   the author cannot modify the article data
