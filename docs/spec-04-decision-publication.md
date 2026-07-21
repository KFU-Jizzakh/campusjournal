# SPEC-04: Editorial Decision and Publication

The editor makes a decision on the article based on completed reviews ("accept" / "revise" / "reject"), then moves the article through copyediting (including upload of a corrected manuscript file), production, and galley proof stages, and publishes it in a journal issue. On publication, DOI registration with Crossref is optionally triggered.

Depends on: SPEC-03

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: The editor sees completed reviews with recommendations and comments
- AC-2: The editor selects a verdict (accept / send for revision / reject), writes a comment to the author, and confirms the decision — the article status changes accordingly
- AC-3: On an "accept" decision — a "Send to Copyediting" button appears
- AC-4: The editor sends the article to copyediting — status changes to "In Copyediting", date and performer are saved. During copyediting, the editorial staff can upload a corrected manuscript file (.docx/.pdf), replacing any previous version.
- AC-4a: The corrected manuscript file can be downloaded by editorial staff and the article submitter. Uploading a new version replaces the previous one (old file is deleted from storage).
- AC-5: After copyediting and once a corrected manuscript file has been uploaded, the editor sends the article to production — status changes to "In Production", date and performer are saved. Attempting to send to production without an uploaded corrected file is blocked with an error message.
- AC-6: After author approval of galleys (see SPEC-13), EiC/ME selects an issue and publishes the article — status changes to "Published", the article appears on the public site
- AC-7: On publication, if Crossref is enabled, background DOI registration is triggered (see SPEC-08)

## UI/UX Notes

- The "Make Decision" block is shown only when there are completed reviews and the status is "In Review"
- The "Send to Copyediting" button is shown only in "Accepted" status. When in "In Copyediting" status, a panel for uploading/downloading the corrected manuscript file is displayed, along with the "Send to Production" button.
- The "Production" block is visible only in "In Copyediting" status, with info about who performed copyediting, when, and a link to the corrected manuscript file.
- The "Publication" block is visible only in "Approved" status and only to users with the publish-issue permission
- The section editor cannot publish — a role with publish-issue (EiC, ME) is required

## Business Rules

- BR-1: A decision can only be made when there is at least one completed review
- BR-2: The section editor makes decisions only on their own articles
- BR-3: On a "Revision" decision — the author sees the editor's comment and can edit the article
- BR-4: On a "Rejected" decision — the process is permanently finished
- BR-4a: Sending to production requires an uploaded copyedited manuscript file
- BR-5: Status transitions are strictly fixed: "Accepted" → "In Copyediting" → "In Production" → "Awaiting Author Approval" → "Author Approved" → "Published"
- BR-6: Only a user with the publish-issue permission can publish
- BR-7: An issue is required for publication

## Behavior

### Background
Given the editor is authenticated and has access to the article

### Rule: Making an editorial decision (BR-1, BR-2)

#### Scenario Outline: Making a decision on the article

Given the article is in "In Review" status, there is at least one completed review
When  the editor selects "<verdict>", writes a comment, and clicks "Confirm decision"
Then  the article status changes to "<status>"

##### Examples:

| verdict              | status      | effect                                              |
|----------------------|-------------|-----------------------------------------------------|
| Accept               | Accepted    | the "Copyediting" block appears, event decision.made  |
| Send for revision    | Revision    | the author can edit the article (see SPEC-01)       |
| Reject               | Rejected    | the process is permanently finished, event decision.made |

#### Scenario: Attempt to make a decision without completed reviews

Given there are no completed reviews
When  the editor attempts to make a decision
Then  the decision block is hidden, direct invocation is blocked
But   the decision is not made

### Rule: Copyediting and production (BR-5)

#### Scenario: Sending to copyediting

Given the article is in "Accepted" status
When  the editor clicks "Send to Copyediting"
Then  the status changes to "In Copyediting"
And   the date and the editor who performed the action are saved
And   the `article.sent_to_copyediting` event is recorded

#### Scenario: Sending to production

Given the article is in "In Copyediting" status
And   a corrected manuscript file has been uploaded
When  the editor clicks "Send to Production"
Then  the status changes to "In Production"
And   the date and the editor who performed the action are saved
And   the `article.sent_to_production` event is recorded

#### Scenario: Attempt to send to production without copyedited file

Given the article is in "In Copyediting" status
And   no corrected manuscript file has been uploaded
When  the editor clicks "Send to Production"
Then  the action is blocked with the message "Загрузите исправленный файл перед отправкой в производство"
But   the article remains in "In Copyediting" status

#### Scenario: Uploading corrected manuscript file during copyediting

Given the article is in "In Copyediting" status
When  the editor uploads a corrected manuscript file (.docx or .pdf)
Then  the file is stored and associated with the article
And   the file path, upload date, and uploader are saved
And   the `copyedited.file_uploaded` event is recorded
And   if a previous corrected file existed, it is deleted

#### Scenario: Downloading the corrected manuscript file

Given the article is in "In Copyediting" status or later
And   a corrected manuscript file has been uploaded
When  an editorial staff member or the article submitter requests the download
Then  the file is served for download

#### Scenario: Attempt to send to copyediting outside "Accepted" status

Given the article is not in "Accepted" status
When  the editor attempts to send to copyediting
Then  the block is hidden, direct invocation is blocked

### Rule: Publication (BR-6, BR-7)

#### Scenario: Publishing the article

Given the article is in "Author Approved" status, the user has the publish-issue permission
When  the editor selects an issue from the list and clicks "Publish"
Then  the status changes to "Published"
And   the issue and publication date are saved
And   the `article.published` event is recorded
And   the article appears on the public site in the selected issue

#### Scenario: Attempt to publish without the publish-issue permission

Given the article is in "Author Approved" status, the user is a section editor
When  the section editor opens the article
Then  the publication block is not displayed
But   the section editor cannot publish the article

#### Scenario: Attempt to publish without selecting an issue

Given the article is in "Author Approved" status, the user has the publish-issue permission
When  the editor clicks "Publish" without selecting an issue
Then  validation requires selecting an issue
But   the publication is not performed
