# SPEC-13: Galley Proofs (Author Proofreading)

Before publication, the author receives the typeset version of the article (galley proofs) for final review and approval. The author either approves publication or requests corrections with a comment. Publication is blocked without the author's approval. After approval, the status changes to ready for publication.

Depends on: SPEC-04

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: The editor uploads the typeset PDF (galley) at the "In Production" stage — a "Send for author approval" button appears
- AC-2: The author receives a notification "Galleys ready for review" with a link to the article
- AC-3: The author opens the article page, sees a download link for the typeset PDF and "Approve" / "Request corrections" buttons
- AC-4: The author approves — status changes to "Author approved", publication is unlocked, the editor receives a notification "Author has approved galleys"
- AC-5: The author requests corrections — writes a comment, status returns to "In Production", the editor receives a notification
- AC-6: Publication (see SPEC-04) is only possible after the author approves the galleys

## UI/UX Notes

- "Galleys" block on the article page: PDF for download, approval status
- The "Approve" button is green, "Request corrections" is yellow
- When requesting corrections — a text field for the comment
- Galley status is displayed in the editor's interface

## Business Rules

- BR-1: Publication is blocked until the author approves the galleys
- BR-2: Corrections can be requested an unlimited number of times
- BR-3: Each correction cycle is logged: who requested, comment, date

## Behavior

### Background
Given: the article has passed production and the editor has uploaded the typeset PDF

### Rule: Sending and approving galleys (BR-1)

#### Scenario: Sending galleys to the author

Given: the article is in "In Production" status, the editor has uploaded the typeset PDF
When:  the editor clicks "Send for author approval"
Then:  the article transitions to "Awaiting author approval" status
And:   the author receives a notification "Galleys ready for review"
And:   a "Galleys" block appears in the author's interface with the PDF and buttons

#### Scenario: Author approval of galleys

Given: the article is in "Awaiting author approval" status, the author opens the page
When:  the author clicks "Approve"
Then:  the status changes to "Author approved"
And:   the editor receives a notification "Author has approved galleys"
And:   the publication block is unlocked for the editor (see SPEC-04)

#### Scenario: Attempt to publish without galley approval

Given: the article is in "Awaiting author approval" status (not "Author approved")
When:  the editor attempts to publish the article
Then:  publication is blocked with the message "Author approval of galleys is required"
But:   the article is not published

### Rule: Requesting corrections (BR-2, BR-3)

#### Scenario: Author requests corrections

Given: the article is in "Awaiting author approval" status
When:  the author clicks "Request corrections", writes a comment, and sends
Then:  the status returns to "In Production"
And:   the editor receives a notification with the author's comment
And:   the `galley.revision_requested` event is recorded
