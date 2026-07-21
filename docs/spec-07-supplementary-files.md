# SPEC-07: Supplementary Files

The author and editors can attach supplementary files to the article: research data, images, video, audio, code, documents. Each file has a type, access level, and license. For public images, a thumbnail is automatically generated. Files can be deleted by the author or editor.

Depends on: SPEC-01

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: The user attaches a file to the article, selects the type, access level, and license — the file is saved
- AC-2: For public images, a thumbnail (300x300) is automatically generated
- AC-3: Public files of a published article are visible to all site visitors
- AC-4: Files with "editorial only" access are visible only to editors
- AC-5: Files with "for reviewers" access are visible to editors and assigned reviewers
- AC-6: The author can delete their own files while the article is in "Draft" or "Revision" status
- AC-7: The editor can delete any file of an article they have access to

## UI/UX Notes

- "Supplementary Files" block on the article page
- On upload — file type selection from a dropdown (research data, image, video, audio, code, document, JATS XML, other)
- Access level selection: public / editorial / for reviewers
- License selection: CC BY, CC BY-SA, CC BY-NC, CC BY-NC-SA, CC BY-ND, CC BY-NC-ND, CC0, all rights reserved
- For images — a preview thumbnail in the file list

## Business Rules

- BR-1: The author uploads files only in "Draft" and "Revision" statuses
- BR-2: The editor uploads files in any status (except draft)
- BR-3: Public files are available anonymously only after the article is published
- BR-4: Files deleted by the author or editor are removed from disk

## Behavior

### Background
Given the user is authenticated and has access to the article

### Rule: File upload by the author (BR-1)

#### Scenario: Uploading an image

Given the article is in "Revision" status, belongs to the user
When  the user selects a file, type "Image", "public" access, license "CC BY", and clicks "Upload"
Then  the file is saved to disk
And   a 300x300 thumbnail is generated (public image)
And   the `article_file.uploaded` event is recorded

#### Scenario: Attempt to upload a file in an invalid status

Given the article is not in "Draft" or "Revision" status, the user is the author
When  the author attempts to upload a file
Then  the upload button is hidden

### Rule: File deletion (BR-1, BR-2, BR-4)

#### Scenario: File deletion by the author

Given the file belongs to the author's article, the article is in "Draft" status
When  the author clicks "Delete" next to the file and confirms
Then  the file is deleted from disk
And   the `article_file.deleted` event is recorded

#### Scenario: Attempt to delete by the author in an invalid status

Given the file belongs to the author's article, the article is in "In Review" status
When  the author attempts to delete the file
Then  the delete button is hidden
But   the file is not deleted

### Rule: File access (BR-3)

#### Scenario Outline: Viewing a file by access level

Given the file has "<access level>" access, the user is "<user role>"
When  the user attempts to open the file
Then  "<result>"

##### Examples:

| access level      | user role              | result               |
|-------------------|------------------------|----------------------|
| public            | anonymous visitor      | file is served       |
| editorial only    | reviewer               | access denied        |
| for reviewers     | article reviewer       | file is served       |
