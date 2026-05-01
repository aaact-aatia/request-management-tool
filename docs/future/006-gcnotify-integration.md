# Future Plan 006: GC Notify Email Integration

**Status**: Planned — Not Yet Enabled  
**Date Planned**: 2026-05-01  
**Reference**: [GC Notify Documentation](https://notification.canada.ca/en)

## Overview

Enable email notifications via the GC Notify API (Government of Canada's notification service). The integration code exists in the codebase but is currently disabled. This document covers what needs to be done to activate it.

## Current State

The following files contain the GC Notify integration, using `.env` variables for credentials:

| File | Purpose |
|------|---------|
| `app/emailController.php` | Core `sendEmail()` function used by the app |
| `app/send-me-mail.php` | Standalone test page to verify the integration works |
| `app/check_curl2.php` | cURL connectivity test against the GC Notify API |
| `app/sendmailtest1.sh` | Shell script for testing from the command line |

All credentials are loaded from `.env` — no hardcoded values remain.

## Required `.env` Variables

```env
GCNOTIFY_API_KEY=your_gc_notify_api_key
GCNOTIFY_TEMPLATE_ID=your_notification_template_id
GCNOTIFY_TEST_EMAIL=your_test_email@example.com
```

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
- Use "Team and whitelist" type for testing, "Live" type for production
- Copy the key — this goes in `GCNOTIFY_API_KEY`
- **Important**: Store the key only in `.env` — never commit it to git

### 4. Test the Integration
- Set all three `.env` variables
- Set `GCNOTIFY_TEST_EMAIL` to your own email address
- Visit `check_curl2.php` in a browser to test the API connection
- Verify you receive the test email
- Alternatively, run `app/sendmailtest1.sh` from the repo root

### 5. Wire `emailController.php` into the App
- `sendEmail($emailAddress, $templateId, $personalisation)` in `emailController.php` is the function to call
- Find the appropriate trigger points in the request lifecycle (e.g., when a request is assigned, resolved, or updated)
- Include `emailController.php` in those pages and call `sendEmail()`

## Notes

- GC Notify supports both English and French — consider creating separate templates per language or using bilingual templates
- The `emailReader.php` file may contain additional integration logic — review it when activating
- Rate limits and quotas apply — check the GC Notify dashboard for current limits
- Test keys and live keys are separate in GC Notify; use the correct one per environment
