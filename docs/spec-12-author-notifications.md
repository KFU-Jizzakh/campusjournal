# SPEC-12: Author Status Notifications

The author receives in-app and email notifications about key events in the editorial process: manuscript receipt, review completion, editorial decision, sending to copyediting, production, publication. Notification settings are available in the profile.

Depends on: SPEC-01, SPEC-03, SPEC-04

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: On manuscript submission, the author and co-authors receive an email confirmation "Manuscript received" with a link to the article
- AC-2: On review completion, the author receives a notification "A review has been completed for your article"
- AC-3: When the editor makes a decision, the author receives a notification with the verdict and a link
- AC-4: When the status changes (copyediting, production, publication), the author receives a notification
- AC-5: All notifications are duplicated: in-system (bell icon) and email
- AC-6: In profile settings, the author can disable email notifications or all notifications

## UI/UX Notes

- Bell icon in the dashboard header with an unread badge
- Clicking a notification navigates to the article
- The notification is marked as read on opening
- Email notifications contain a link to the article in the dashboard

## Business Rules

- BR-1: All notifications are enabled by default
- BR-2: Notifications are sent to the article author and co-authors
- BR-3: Notifications about status changes (copyediting, production, publication) and galley sending are throttled — a repeated event of the same type within one hour does not generate a notification. Critical events (editorial decision, review completion, manuscript receipt) are always delivered, without throttling

## Behavior

### Background
Given: the author has submitted a manuscript and is its owner

### Rule: Editorial process event notifications (BR-1, BR-2)

#### Scenario: Manuscript receipt notification

Given: the author submitted a manuscript (see SPEC-01)
When:  the article is created with status "Submitted"
Then:  the author receives an email "Your manuscript has been received"
And:   an in-app notification appears in the dashboard
And:   the bell icon is updated with a +1 badge

#### Scenario Outline: Article status change notification

Given: the article is in the editorial review process
When:  the article status changes to "<status>"
Then:  the author receives a notification about "<event description>"

##### Examples:

| status          | event description                  |
|-----------------|------------------------------------|
| In Review       | Article sent for peer review       |
| In Copyediting  | Article sent to copyediting        |
| In Production   | Article sent to production         |
| Published       | Article published                  |

### Rule: Notification settings (AC-6, BR-3)

#### Scenario: Disabling email notifications

Given: the author opens profile settings
When:  they uncheck "Email notifications for status changes" and save
Then:  on subsequent events, no email is sent
But:   in-app notifications continue to arrive
