# SPEC-05: Review Type and Anonymization

The article has a configurable review type: single-blind (default), double-blind, and open. For double-blind, the editor uploads an anonymized version of the manuscript. Reviewer assignment is blocked until the anonymized version is uploaded. The reviewer receives the anonymized PDF and does not see the author's name. The review type is locked after the first reviewer is assigned.

Depends on: SPEC-02

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: The editor sees the current review type of the article and can change it while there are no assigned reviewers
- AC-2: When selecting "double-blind", a blinded manuscript upload block appears
- AC-3: The editor uploads the anonymized PDF — the indicator changes to "Uploaded" with a date and a download link
- AC-4: The editor can replace the anonymized file with a new one
- AC-5: The editor can delete the anonymized file only if there are no active reviewers
- AC-6: For double-blind, the reviewer receives the anonymized PDF instead of the original
- AC-7: For double-blind, the author's name is not displayed to the reviewer anywhere in the interface
- AC-8: After a reviewer is assigned, the change-type button is hidden with an explanation
- AC-9: The author and editor always receive the original manuscript, regardless of the review type

## UI/UX Notes

- The review type management block is visible only to editors (not the author)
- The change-type button is hidden if there are active reviewers — with text "Review type cannot be changed after reviewers are assigned"
- The blinded manuscript upload block is visible only when the type is "double-blind"
- Blinded manuscript indicator: red "Not uploaded" / green "Uploaded" with date
- For double-blind, the "Assign reviewer" button is inactive without the blinded manuscript
- The PDF link for the reviewer in double-blind is labeled "Anonymized version"

## Business Rules

- BR-1: The review type cannot be changed after at least one reviewer is assigned (excluding declined)
- BR-2: A reviewer cannot be assigned in double-blind without an uploaded blinded manuscript
- BR-3: The blinded manuscript cannot be deleted while there are active reviewers
- BR-4: The reviewer always receives the anonymized PDF in double-blind, the original in other types
- BR-5: The author never sees the reviewer's name
- BR-6: The reviewer never sees the author's name in double-blind

## Behavior

### Background
Given the editor is authenticated and has access to the article

### Rule: Managing the review type (BR-1)

#### Scenario: Changing the type to double-blind

Given the article is in "Submitted" status, no reviewers are assigned
When  the editor clicks "Change", selects "Double-blind", and saves
Then  the review type is changed to "double-blind"
And   the blinded manuscript upload block appears
And   the action is recorded in the event log

#### Scenario: Attempt to change the type when reviewers are assigned

Given the article has at least one assigned reviewer (not declined)
When  the editor opens the article page
Then  the change-type button is hidden, an explanation about the inability to change is displayed
But   the review type is not changed

### Rule: Blinded manuscript (BR-2, BR-3)

#### Scenario: Uploading the blinded manuscript

Given the review type is "double-blind", the blinded version is not uploaded
When  the editor uploads a PDF file
Then  the file is validated for PDF format and size
And   the file is saved on a secure disk
And   the indicator changes to "Uploaded" with date and link
And   the `article.blinded_pdf_uploaded` event is recorded

#### Scenario: Replacing the blinded manuscript

Given the blinded version is uploaded
When  the editor uploads a new file
Then  the old file is deleted, the new one is saved
And   the indicator is updated

#### Scenario: Deleting the blinded manuscript

Given the blinded version is uploaded, no active reviewers
When  the editor deletes the file
Then  the file is deleted from disk
And   the indicator changes to "Not uploaded"
And   the `article.blinded_pdf_deleted` event is recorded

#### Scenario: Attempt to delete with active reviewers

Given the blinded version is uploaded, there are reviewers in "Pending" or "In Progress" status
When  the editor attempts to delete the file
Then  deletion is blocked with the message "Cannot delete the blinded manuscript while there are active reviewers"
But   the file is not deleted

### Rule: Assigning a reviewer in double-blind (BR-2, BR-4)

#### Scenario: Assignment without a blinded manuscript

Given the type is "double-blind", the blinded version is not uploaded
When  the editor attempts to assign a reviewer
Then  assignment is blocked, the "Assign" button is inactive
And   the message "For double-blind review, a blinded version of the manuscript must be uploaded" is displayed
But   the reviewer is not assigned

### Rule: Reviewer workflow with the blinded manuscript (BR-4, BR-6)

#### Scenario: Reviewer in double-blind

Given the review is accepted (status "In Progress"), review type is "double-blind"
When  the reviewer opens the review page
Then  the author's name is hidden in all interface elements
And   the PDF link points to the anonymized version
And   a label "Anonymized version" is shown next to the link
And   supplementary files are filtered: only public and reviewer-visible files are shown
