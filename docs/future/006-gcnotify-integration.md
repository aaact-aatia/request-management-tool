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
GCNOTIFY_CURL_CA_BUNDLE=
GCNOTIFY_CURL_INSECURE=false
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

### Template override variable

The app now uses one generic template override key. Update `GCNOTIFY_TEMPLATE_ID` to point at the GC Notify template that contains the `((message))` placeholder.

See `.env.example` for the generic setting.

### Template model and ID mapping

The application now uses a single GC Notify template ID for all notification types. The app supplies the event-specific body text through `((message))`.

### Communication language policy

The application should record the language of the site the request came from when the request is created. Recommended field name: `requestlang` in `tbltriage`.

- Set it from the current site/session language at submission time.
- Use it to decide the language order for client-facing notifications.
- Do not infer it later from the request text or the current user session.

Policy:

- Client-facing emails follow the request language first.
- Internal/team/generic inbox emails are English-first.
- If a message has both client and internal audiences, treat them as separate sends when possible.

#### Communication matrix

| Audience | Stored request language | Language order | Template family |
|----------|-------------------------|----------------|-----------------|
| Client | `en` | English first, then French | `*_CLIENT_EN` |
| Client | `fr` | French first, then English | `*_CLIENT_FR` |
| Team / internal inbox | `en` or `fr` | English first, then French if bilingual text is required | `*_TEAM_EN` |
| Generic inbox | `en` or `fr` | English first, then French if bilingual text is required | `*_TEAM_EN` or a generic internal template |

Recommended implementation rule:

1. Use `requestlang` to pick the client-facing template order.
2. Use English-first templates for internal and generic mailbox notifications.
3. Keep template text grouped by audience, not by the sender's current UI state.

#### Key to environment variable map

| Flow | Key | App setting |
|------|-----|-------------|
| Generic notification shell | `notification_generic` | `GCNOTIFY_TEMPLATE_ID` |

#### Recommended way to build templates

Start small and expand:

1. Create one connectivity template for `check_curl2.php` and set `GCNOTIFY_TEMPLATE_ID`.
2. Create one generic workflow template for EN and one for FR.
3. Point all EN override settings to the EN workflow template ID.
4. Point all FR override settings to the FR workflow template ID.
5. Run the full checklist and capture gaps in wording/fields.
6. Split into specialized templates (new, reassigned, resolved, status changed) only when content needs diverge.

#### Placeholder guidance

Use placeholders that already exist in personalization payloads, such as:

- `requestid`
- `nrequestid`
- `teamname`
- `requesttitle`
- `nrequestemail`
- `url`

When adding a placeholder to a template, verify that the relevant flow always supplies it.

#### App Service setup checklist for template IDs

1. Set `GCNOTIFY_TEMPLATE_ID` for direct connectivity test pages.
2. Set all `GCNOTIFY_TEMPLATE_*` values used by the flows you are testing.
3. Save settings and restart app service.
4. Run `check_curl2.php` first, then run the workflow checklist.

### TLS troubleshooting (self-signed certificate chain)

If you see this error from `check_curl2.php`:

- `SSL certificate problem: self-signed certificate in certificate chain`

use one of these options:

1. Preferred: set `GCNOTIFY_CURL_CA_BUNDLE` to a CA bundle path that trusts your intercepting/proxy certificate.
2. Temporary dev diagnostic only: set `GCNOTIFY_CURL_INSECURE=true`, test, then revert to `false`.

Do not use `GCNOTIFY_CURL_INSECURE=true` in production.

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
