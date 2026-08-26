# SPEC-23: Email Verification

After registration, the user must confirm their email address. Until confirmation, the user is logged in but has no access to `/dashboard/*` and `/profile` — every request is redirected to the "Verify your email" page, from which the verification email can be re-sent. The verification email is sent in Russian with a signed link valid for 60 minutes. Changing the email address in the profile resets verification and sends a new email. Users created by an admin in the Filament panel also receive a verification email; when an admin changes a user's email in the panel, verification is reset and a new email is sent to the new address. An admin can manually mark a user as verified or unverified in the panel (fallback when SMTP is unavailable). The Filament panel (`/admin`) itself is intentionally not gated by email verification.

Depends on: —

Status: IMPLEMENTED

## Acceptance Criteria

- AC-1: After registration, a verification email with a signed link (valid 60 minutes) is sent to the user's address, in Russian
- AC-2: Until the email is verified, an authenticated user is redirected to the "Verify your email" page when accessing any `/dashboard/*` or `/profile` route
- AC-3: The verification email can be re-sent from the "Verify your email" page (rate-limited: 6 per minute)
- AC-4: Clicking the signed link marks the user as verified and redirects to the dashboard with `verified=1`
- AC-5: Changing the email address in the profile resets `email_verified_at` and sends a new verification email to the new address
- AC-6: When an admin creates a user in the Filament panel, the new user receives a verification email
- AC-7: A link with an invalid hash does not verify the email
- AC-8: When an admin changes a user's email address in the Filament panel, verification is reset and a new verification email is sent to the new address
- AC-9: An admin can manually mark a user as verified or unverified in the Filament panel via a toggle; toggling does not send a verification email

## UI/UX Notes

- The "Verify your email" page (existing Breeze view) shows a "Resend verification email" button and a "verification link sent" confirmation after re-sending

## Business Rules

- BR-1: An unverified user can log in, but is redirected to the verification notice from any protected route
- BR-2: Seeded system users (admin, editorial staff) are considered verified and are not affected
- BR-3: Opening an already-used or expired link does not re-verify and does not break anything
- BR-4: Verification emails are sent through the notification system (queued); production requires a configured SMTP mailer and a running queue worker

## Behavior

### Background
Given: a new user is registered on the public registration form (role `author`, profile created)

### Rule: Registration verification flow (BR-1)

#### Scenario: Registration sends verification email

Given: the registration form is filled correctly
When:  the user submits the form
Then:  the account is created and the user is authenticated
And:   a verification email in Russian is sent to the user's address
And:   the user is redirected to the dashboard

#### Scenario: Unverified user is blocked from protected routes

Given: the user registered but has not verified their email
When:  the user opens any `/dashboard/*` or `/profile` route
Then:  the user is redirected to the "Verify your email" page

#### Scenario: Verified user accesses protected routes

Given: the user clicked the verification link from the email
When:  the user opens any `/dashboard/*` or `/profile` route
Then:  the page is rendered normally

### Rule: Resend and link handling (AC-3, AC-4, AC-7)

#### Scenario: Resend verification email

Given: the user is on the "Verify your email" page
When:  the user clicks "Resend verification email"
Then:  a new verification email is sent
And:   the page shows the "verification link sent" status

#### Scenario: Verification link with invalid hash

Given: the user opens a verification link with a wrong hash
When:  the link is followed
Then:  the email is NOT marked as verified

#### Scenario: Verification link expired (BR-3)

Given: the user opens a verification link after it expired (60 minutes)
When:  the link is followed
Then:  the request is rejected with 403
And:   the email is NOT marked as verified

### Rule: Email change (AC-5)

#### Scenario: Email change resets verification

Given: a verified user changes their email address in the profile
When:  the profile is saved
Then:  `email_verified_at` is set to null
And:   a new verification email is sent to the new address
And:   the user is redirected to the profile page (and subsequently to the verification notice on protected routes)

### Rule: Filament-created users (AC-6)

#### Scenario: Admin creates a user in the panel

Given: an admin opens `/admin` and creates a user
When:  the user record is saved
Then:  a verification email is sent to the new user's address

### Rule: Filament email change (AC-8)

#### Scenario: Admin changes a user's email in the panel

Given: an admin opens `/admin` and changes a user's email address
When:  the user record is saved
Then:  `email_verified_at` is set to null
And:   a new verification email is sent to the new address

#### Scenario: Admin saves a user without changing the email

Given: an admin opens `/admin` and edits a user without changing their email address
When:  the user record is saved
Then:  `email_verified_at` is unchanged
And:   no verification email is sent

### Rule: Manual verification by admin (AC-9)

#### Scenario: Admin marks a user as verified

Given: an admin opens `/admin` and edits an unverified user
When:  the admin turns on the "Email подтверждён" toggle and saves
Then:  `email_verified_at` is set
And:   no verification email is sent

#### Scenario: Admin marks a user as unverified

Given: an admin opens `/admin` and edits a verified user
When:  the admin turns off the "Email подтверждён" toggle and saves
Then:  `email_verified_at` is set to null
And:   no verification email is sent
