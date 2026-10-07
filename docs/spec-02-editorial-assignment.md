# SPEC-02: Editorial Board and Reviewers

The editor-in-chief or managing editor assigns a section editor to an article. The section editor (or EiC/ME) assigns reviewers, sets deadlines, and notifies the reviewer by email. A section editor works only with articles assigned to them.

Depends on: SPEC-01

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: EiC/ME opens the editorial desk, sees all submitted articles with status filters and counters
- AC-2: EiC/ME selects a section editor from a dropdown (users with the section-editor role) and assigns them to the article
- AC-3: The editor selects a reviewer from the list (users with the review-article permission), assigns them — the system creates a review with deadlines and sends an email
- AC-4: The section editor sees only articles assigned to them in the editorial desk
- AC-5: The same reviewer cannot be assigned two active reviews on the same article (duplicate is blocked)
- AC-6: A declined reviewer can be re-assigned to the same article

## UI/UX Notes

- The "Section Editor" block is shown only to EiC and ME, and only for articles in "Submitted" status
- The editor dropdown contains only users with the section-editor role
- For double-blind review, the "Assign" button is inactive until a blinded manuscript is uploaded (see SPEC-05)
- The section editor does not see the section editor assignment block

## Business Rules

- BR-1: Only a user with the editor-in-chief or managing-editor role can assign an editor
- BR-2: The user being assigned must have the section-editor role
- BR-3: An editor can only be assigned to an article in "Submitted" status
- BR-4: A reviewer can only be assigned to an article in "Submitted" or "In Review" status
- BR-5: The reviewer must have the review-article permission
- BR-6: When the first reviewer is assigned, the article transitions from "Submitted" to "In Review"
- BR-7: Reviewer response deadline — 7 days, review deadline — 30 days (configurable in settings)
- BR-8: Duplicate reviewer assignment is blocked at both the application and database level
- BR-9: Rubric auto-assignment never picks the submitter, a user without the section-editor role, or a conflicted editor (an author of the submission, claimed via `user_id` link or unclaimed via pivot email snapshot matching the account email); an auto-assignment found to be conflicted when authors are synced is released back to the unassigned pool. The same email snapshot counts as conflict evidence for manual assignment and workflow actions (deny-side only)

## Behavior

### Background
Given: the user is authenticated and has the editor-in-chief or managing-editor role

### Rule: Section editor assignment (BR-1, BR-2, BR-3)

#### Scenario: Successful assignment

Given the article is in "Submitted" status
When  the EiC selects a user with the section-editor role and clicks "Assign"
Then  the section editor is assigned to the article
And   the section editor sees this article in their editorial desk
And   the `editor.assigned` event is recorded

#### Scenario: Attempt to assign a non-section-editor

Given the article is in "Submitted" status
When  the system verifies the selected user's role and finds they lack the section-editor role
Then  an exception is thrown with an error message
But   the assignment is not performed

#### Scenario: Assignment to an article not in "Submitted" status

Given the article is not in "Submitted" status
When  the EiC attempts to assign an editor
Then  the assignment block is hidden, direct invocation is blocked
But   the editor is not assigned

#### Scenario: Attempt to assign a conflicted editor (BR-9)

Given the article lists the selected user as an author (claimed account link, or unclaimed listing whose pivot email matches the user's account email)
When  the EiC attempts to assign that user as editor
Then  an exception is thrown with an error message
But   the assignment is not performed

### Rule: Rubric auto-assignment (BR-9)

#### Scenario: Rubric mapping skipped for a role-less or submitting user

Given a category whose default section editor lacks the section-editor role, or is the submitter
When  a submission is made in that rubric
Then  the submission is created with `editor_id` null

#### Scenario: Conflicted auto-assignment is released

Given a category mapped to editor E
When  a submission by another user lists E as a coauthor with pivot email equal to E's account email
Then  the submission is created
And   `editor_id` is released to null when the authors are synced
And   the article appears in the unassigned pool for leadership to reassign

### Rule: Reviewer assignment (BR-4, BR-5, BR-6, BR-7, BR-8)

#### Scenario: Successful assignment

Given the article is in "Submitted" or "In Review" status, the editor is authenticated
When  the editor selects a reviewer with the review-article permission and clicks "Assign"
Then  a review is created with status "Pending"
And   deadlines are set: response — +7 days, review — +30 days
And   if the article was "Submitted" — it transitions to "In Review"
And   the reviewer receives an email invitation
And   the `reviewer.assigned` event is recorded

#### Scenario: Duplicate reviewer

Given the article already has an active (non-declined) review from reviewer X
When  the editor attempts to assign reviewer X again
Then  the assignment is blocked with the message "Reviewer already assigned"
But   a duplicate review is not created

#### Scenario: Reviewer without review-article permission

Given the selected user does not have the review-article permission
When  the editor attempts to assign them as a reviewer
Then  the assignment is blocked with a message about insufficient permissions
But   the review is not created
