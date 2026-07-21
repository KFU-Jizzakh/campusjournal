# SPEC-03: Review Process

The reviewer accepts or declines the review invitation, and after acceptance — conducts the review: sets a recommendation and writes comments for the author and editor. The editor receives notifications about acceptance, decline, and review completion.

Depends on: SPEC-02

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: The reviewer opens the list of assigned reviews and sees deadlines with color indicators
- AC-2: The reviewer accepts the invitation — the review transitions to "In Progress", the editor receives in-app and email notification about acceptance
- AC-3: The reviewer declines the invitation — the review transitions to "Declined", the editor receives a notification
- AC-4: The reviewer fills in the review form (recommendation, comments to the author, comments to the editor) and submits — the review transitions to "Completed", the editor receives a notification
- AC-5: The reviewer sees the deadline with color indicators: green (normal), yellow (≤7 days), orange (≤3 days), red (overdue)

## UI/UX Notes

- Accept/decline is only possible in "Pending" status
- Submit a review is only possible in "In Progress" status
- Editor comments are highlighted with a yellow background and visible only to editors
- Author comments are visible to the author only after the editor makes a decision (see SPEC-01, SPEC-04)
- The author's name is not displayed to the reviewer (single-blind; see SPEC-05 for double-blind)

## Business Rules

- BR-1: After accepting the invitation there is no going back — the reviewer must either complete the review, or the editor may delete the review
- BR-2: After declining, the review is finished; the editor can assign another reviewer
- BR-3: The reviewer sees and can only act on their own reviews (admin is an exception)
- BR-4: Admin can accept/decline/complete any review (editorial substitution), but only within allowed status transitions

## Behavior

### Background
Given the reviewer is authenticated and has the review-article permission

### Rule: Accepting and declining the invitation (BR-1, BR-2)

#### Scenario: Accepting the invitation

Given the review is in "Pending" status, belongs to the reviewer
When  the reviewer clicks "Accept"
Then  the review transitions to "In Progress"
And   the `review.accepted` event is recorded
And   the editor receives an in-app and email notification about invitation acceptance
And   a message with the deadline date is displayed

#### Scenario: Declining the invitation

Given the review is in "Pending" status, belongs to the reviewer
When  the reviewer clicks "Decline" and confirms
Then  the review transitions to "Declined"
And   the `review.declined` event is recorded
And   the editor receives an in-app notification about invitation decline

#### Scenario: Attempt to accept or decline outside "Pending" status

Given the review is not in "Pending" status
When  the reviewer attempts to accept or decline
Then  the action is blocked
But   the review status is not changed

### Rule: Conducting the review (BR-1, BR-3, BR-4)

#### Scenario: Completing the review

Given the review is in "In Progress" status, belongs to the reviewer
When  the reviewer selects a recommendation (accept / minor revision / major revision / reject), writes comments for the author and editor, and clicks "Submit review"
Then  the review transitions to "Completed"
And   the completion date, recommendation, and comments are saved
And   the editor receives an in-app and email notification about review completion
And   the `review.completed` event is recorded

#### Scenario: Submission with incomplete required fields

Given the review is in "In Progress" status
When  the reviewer attempts to submit the form with incomplete required fields
Then  validation rejects the submission, fields are highlighted
But   the review is not completed

#### Scenario: Admin substitutes for a reviewer

Given the admin is authenticated, the review is in "In Progress" status and belongs to another user
When  the admin fills in the review form and submits
Then  the review is completed on behalf of the admin
And   the `review.completed` event is recorded
