# Future Plan 006: GC Notify Email Integration

**Status**: In Progress  
**Date Planned**: 2026-05-01  
**Reference**: [GC Notify Documentation](https://notification.canada.ca/en)

## Overview

Enable email notifications via the GC Notify API (Government of Canada's notification service). The integration code exists in the codebase and the shared notification boundary now supports environment-driven delivery controls for safe non-production testing. This document captures the current setup direction and the remaining work.

## Current State

The following files contain the GC Notify integration, using `.env` variables for credentials and delivery controls:

| File | Purpose |
|------|---------|
| `app/emailController.php` | Core `sendEmail()` function used by the app and the notify-mode redirect/disabled logic |
| `app/send-me-mail.php` | Standalone test page to verify the integration works |
| `app/check_curl2.php` | cURL connectivity test against the GC Notify API |
| `app/sendmailtest1.sh` | Shell script for testing from the command line |
| `app/env.php` | Runtime environment helpers including `app_notify_mode()` |

All credentials are loaded from `.env` and runtime environment variables. Notification workflows now resolve template IDs through named template keys, and request email links use `APP_BASE_URL` (or runtime host detection fallback) instead of a hardcoded domain in the request flows.

## Required `.env` Variables

```env
GCNOTIFY_API_KEY=your_gc_notify_api_key
GCNOTIFY_TEMPLATE_ID=your_notification_template_id
GCNOTIFY_TEST_EMAIL=your_test_email@example.com
APP_BASE_URL=https://your-dev-or-prod-base-url
NOTIFY_MODE=redirect
NOTIFY_REDIRECT_FORCE_OVERRIDE=false
NOTIFY_OVERRIDE_EMAIL=
NOTIFY_OVERRIDE_CLIENT_EMAIL=
NOTIFY_OVERRIDE_INTERNAL_EMAIL=
```

### Notification delivery modes

- `live` — send to the intended client/team recipients
- `redirect` — in non-production, send to the logged-in user's email when available; otherwise fall back to the configured override addresses
- `disabled` — do not send notifications; log the skip instead

### Safelist key behavior (Team and safelist)

If your GC Notify API key type is `Team and safelist`, recipients outside your team/safelist will be rejected by the API until your service is approved for live sending.

Recommended dev settings:

- `NOTIFY_MODE=redirect`
- `NOTIFY_REDIRECT_FORCE_OVERRIDE=true`
- `NOTIFY_OVERRIDE_EMAIL=<a safelisted mailbox>`

With these settings, all app-triggered notifications are redirected to a known safelisted inbox for predictable testing.

### Template override variables

The app now supports per-event/per-language template overrides so UUID changes can be handled via environment settings instead of code edits.

Examples:

- `GCNOTIFY_TEMPLATE_REQUEST_TEAM_EN`
- `GCNOTIFY_TEMPLATE_REQUEST_CLIENT_FR`
- `GCNOTIFY_TEMPLATE_RESOLVED_CLIENT_EN`
- `GCNOTIFY_TEMPLATE_STATUS_CHANGED_CLIENT_FR`
- `GCNOTIFY_TEMPLATE_REASSIGNED_TEAM_EN`

See `.env.example` for the full list.

Recommended default:

- Development: `NOTIFY_MODE=redirect`
- Production: `NOTIFY_MODE=live`

## Setup Steps

### 1. Create a GC Notify Account
- Go to [https://notification.canada.ca](https://notification.canada.ca)
- Request access for the AAACT program
- Create a service named "Request Management Tool" (or similar)

### 2. Create an Email Template
- In GC Notify, create a bilingual email template for request notifications
- The template should use the following personalisation variables (already in the code):
  - `requestid` — the RMT request ID
  - `nrequestid` — the notification request ID
  - `teamname` — the assigned team name
  - `requesttitle` — the request title/subject
  - `nrequestemailid` — notification email record ID
  - `nrequestemail` — the recipient email address
- Copy the template ID (UUID) — this goes in `GCNOTIFY_TEMPLATE_ID`

### 3. Generate an API Key
- In GC Notify, go to API keys → Create key
- Use `Team and safelist` type for testing, `Live` type for production
- Copy the key — this goes in `GCNOTIFY_API_KEY`
- **Important**: Store the key only in `.env` — never commit it to git

### 4. Test the Integration
- Set the required `.env` variables
- In development, set `NOTIFY_MODE=redirect`
- Ensure the logged-in tester account has a valid email address, or set `NOTIFY_OVERRIDE_EMAIL` / the more specific override addresses
- Set `GCNOTIFY_TEST_EMAIL` to your own email address for the standalone connectivity pages
- Visit `check_curl2.php` in a browser to test the API connection
- Verify you receive the test email
- Alternatively, run `app/sendmailtest1.sh` from the repo root

### 5. Remaining Implementation Work
- Expand documentation for the full event/template matrix by language
- Add deeper operational logging if a durable audit trail is required

## Notes

- GC Notify supports both English and French — consider creating separate templates per language or using bilingual templates
- The request lifecycle resolves templates via named keys in `app_notify_template_id(...)` and supports environment-level UUID overrides
- Rate limits and quotas apply — check the GC Notify dashboard for current limits
- Test keys and live keys are separate in GC Notify; use the correct one per environment
