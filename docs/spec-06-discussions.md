# SPEC-06: Discussions and Notifications

The discussion system allows editorial workflow participants to communicate within the system. The author can ask a question to the editor, the editor can discuss the article with a reviewer, and editors can hold internal discussions. Each message is linked to an article and has a visibility scope. Threads can be closed. Participants receive notifications about new messages.

Depends on: SPEC-01

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: The author opens their article page, sees the "Discussions" block, can create a message (visibility defaults to "general")
- AC-2: The editor replies to the author in the same thread — the reply inherits the visibility scope
- AC-3: The editor starts a private discussion with a reviewer: the message has "editorial" scope and is linked to a review; the author does not see it
- AC-4: Editors hold an internal discussion: "editorial" scope, without a review link; the author and reviewers do not see it
- AC-5: The user sees a list of threads accessible to their role, with visibility and review-link badges
- AC-6: New messages are marked with an unread indicator
- AC-7: When a message is created, participants receive in-app and email notifications
- AC-8: The editor closes a thread (marked "Resolved") — replies are blocked, the thread is collapsed and greyed out; can be reopened
- AC-9: The author sees that the thread is closed but cannot reopen it
- AC-10: In profile settings, the user can disable email notifications for discussions or all discussion notifications

## UI/UX Notes

- The discussions block is displayed only for active articles (not draft, not published)
- Messages are plain text without formatting
- Threads are sorted newest to oldest; replies within a thread are oldest to newest
- Pagination: 20 threads per page
- Closed threads are greyed out, collapsed, sorted to the end
- Badges: "Editorial" for editorial discussions, "with reviewer" for review-linked threads
- Unread message indicator
- The "Discussion with editor" block on the reviewer page is visible only while the review is active
- Filter tabs: "All", "General", "Editorial"

## Business Rules

- BR-1: Discussions are created only for articles in active statuses (not draft, not published)
- BR-2: "General" visibility — seen by the article author and all editors with access
- BR-3: "Editorial" visibility — seen only by editors
- BR-4: A message linked to a review — additionally seen by the reviewer of that review
- BR-5: The author does not see messages with "editorial" visibility
- BR-6: A reply inherits the visibility and review link from the parent message
- BR-7: Notifications are sent to all thread participants except the message author
- BR-8: Only an editor can close threads
- BR-9: Replying to a closed thread is prohibited
- BR-10: Discussion notifications are enabled by default (email and in-app)

## Behavior

### Background
Given the article is submitted and not in "Draft" or "Published" status

### Rule: Creating and replying to messages (BR-1, BR-6)

#### Scenario: Author starts a discussion

Given the author opens their article page
When  they click "Ask a question", enter text, and send
Then  a message is created with "general" visibility
And   the assigned section editor, EiC, and ME receive a notification
And   the `discussion.created` event is recorded

#### Scenario: Editor replies to the author

Given a message from the author with "general" visibility exists
When  the editor clicks "Reply", enters text, and sends
Then  a reply is created in the same thread with the same visibility
And   the author receives a notification about the reply

#### Scenario: Empty message

Given the user enters empty text
When  they click "Send"
Then  the submission is rejected, the field is highlighted as required
But   the message is not created

### Rule: Visibility scopes (BR-2, BR-3, BR-4, BR-5)

#### Scenario: Private discussion between editor and reviewer

Given the article is in review, an active review is assigned
When  the editor selects "Discuss with reviewer" and sends a message
Then  the message is created with "editorial" visibility and linked to the review
And   the reviewer receives a notification
But   the author does not see this message

#### Scenario: Internal editors' discussion

Given the editor opens the article page
When  they switch to the "Editorial" tab, click "New message", and send
Then  the message is created with "editorial" visibility, without a review link
And   all editors with access to the article receive a notification
But   the author and reviewers do not see this message

### Rule: Closing and reopening threads (BR-8, BR-9)

#### Scenario: Closing a thread

Given the root message is not closed
When  the editor clicks "Resolved"
Then  the thread is marked as resolved
And   visually: greyed out, collapsed, reply button hidden
And   the "Reopen" button appears
And   the `discussion.resolved` event is recorded

#### Scenario: Attempt to reply to a closed thread

Given the thread is closed
When  the user attempts to reply
Then  the reply is rejected
But   the message is not created

### Rule: Notifications (BR-7)

#### Scenario: Notification about a new message

Given the user is a participant of the thread (or has access to the visibility scope of a new thread)
When  another participant creates a message
Then  the user receives an in-app notification (bell icon with badge)
And   the user receives an email with a link to the article

#### Scenario: Disabling email notifications for discussions

Given the user opens profile settings
When  they uncheck "Email notifications for discussions" and save
Then  on new discussion messages, no email is sent
But   in-app notifications continue to arrive
