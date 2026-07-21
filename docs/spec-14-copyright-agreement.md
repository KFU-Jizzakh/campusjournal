# SPEC-14: Copyright License Agreement

On manuscript submission, the author accepts the terms of the license agreement (CC BY or another journal license) and confirms that they hold the rights to publish. The agreement is saved as a legally significant record. Submission is blocked without accepting the agreement. On the public article page, the license under which the article is published is displayed with a link to the license terms.

Depends on: SPEC-01, SPEC-04

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: On the submission form, the author sees the license agreement text and a checkbox "I accept the terms"
- AC-2: Without checking the checkbox, manuscript submission is blocked
- AC-3: The accepted agreement is saved: date, IP address, agreement version
- AC-4: The author can view the accepted agreement on their article page in the dashboard
- AC-5: The agreement text is managed via the admin panel or a CMS page
- AC-6: On the public article page (for published articles), the license under which the article is published is displayed with a link to the full license terms

## UI/UX Notes

- The agreement text on the submission form — a short version with a "full text" link
- The checkbox is mandatory; without it, the "Submit" button is inactive
- On the dashboard article page — a "Copyright Agreement" block with the date and a link to the full text
- On the public article page — a license badge (e.g., "CC BY") with a link to the license terms; displayed only for published articles

## Business Rules

- BR-1: Without accepting the agreement, the manuscript cannot be submitted
- BR-2: Each submission requires a new acceptance (even on revision)
- BR-3: The agreement cannot be changed retroactively for already submitted articles
- BR-4: The agreement version history is stored for audit
- BR-5: The article's publication license is the license defined in the latest accepted CopyrightAgreement version; displayed on the public page after publication

## Behavior

### Background
Given the author opens the submission form

### Rule: Mandatory agreement acceptance (BR-1, BR-2)

#### Scenario: Submission with agreement acceptance

Given the author has filled in all submission form fields
When  they check the "I accept the terms of the license agreement" checkbox and click "Submit"
Then  the agreement is saved: version, date, IP address
And   the article is created with status "Submitted" (see SPEC-01)

#### Scenario: Attempt to submit without agreement

Given the author has filled in the form fields, the checkbox is unchecked
When  they click "Submit"
Then  validation rejects the submission with the message "You must accept the terms of the agreement"
But   the article is not created

### Rule: Access to the accepted agreement (AC-4, BR-3)

#### Scenario: Author views the agreement in the dashboard

Given the article is submitted, the author opens the article page in the dashboard
When  they scroll to the "Copyright Agreement" block
Then  they see the acceptance date and a link to the full agreement text

### Rule: License display on the public article page (AC-6, BR-5)

#### Scenario: Visitor views the article license

Given the article is published and has an accepted CopyrightAgreement
When  a visitor opens the public article page
Then  the license badge (e.g., "CC BY") is displayed
And   the badge links to the Creative Commons license URL or the full agreement text

#### Scenario: No license displayed before publication

Given the article is not published (in review, in copyediting, etc.)
When  a visitor opens the public article page
Then  the license badge is not displayed
But   the article page may still show other metadata (title, authors, abstract, etc.)

#### Scenario: No license displayed when no agreement was accepted

Given the article is published but has no accepted CopyrightAgreement (edge case, legacy articles)
When  a visitor opens the public article page
Then  no license badge is displayed
