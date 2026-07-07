# GC Notify Manual Test Checklist (Dev)

Use this checklist to validate GC Notify behavior before merging notification-related changes.

## Scope

Run this full checklist for pull requests that touch:
- Notification sending logic
- Request lifecycle transitions (new, assign/reassign, status, resolved)
- Template mapping or language routing
- Environment and recipient routing settings

Run a reduced check for unrelated changes.

## Preconditions

1. Confirm dev environment settings in `.env` or App Service:
   - `APP_ENV=development`
   - `NOTIFY_MODE=redirect`
   - `NOTIFY_REDIRECT_FORCE_OVERRIDE=false`
2. Log in as a test user with a valid email you can access.
3. Ensure this mailbox is allowed by your current GC Notify key type (`Team and safelist`).
4. Record current timestamp so you can identify new notifications.

If `check_curl2.php` shows `SSL certificate problem: self-signed certificate in certificate chain`:
- Preferred: configure `GCNOTIFY_CURL_CA_BUNDLE` to a trusted CA bundle path.
- Temporary dev diagnostic only: set `GCNOTIFY_CURL_INSECURE=true`, test, then revert to `false`.

## Test Data

1. Use unique request titles for each run, for example:
   - `Notify Test A - YYYY-MM-DD HH:MM`
2. Keep one reusable test request for status/reassign/resolved checks.

## Test 1: New Request Notification

1. Create a new request.
2. Submit successfully.
3. Verify expected result:
   - Request is created.
   - Notification arrives to logged-in user mailbox (redirect behavior in dev).
   - Message contains expected request identifiers and link.
4. Mark `PASS` or `FAIL`.

## Test 2: Assign/Reassign Notification

1. Open an active request.
2. Change assignment/team to trigger reassign path.
3. Save changes.
4. Verify expected result:
   - Assignment update succeeds.
   - Reassign notification arrives to logged-in user mailbox.
   - Content reflects new assignment/team.
5. Mark `PASS` or `FAIL`.

## Test 3: Generic Status Change Notification

1. Change status to a non-resolved state that should trigger a status update notification.
2. Save changes.
3. Verify expected result:
   - Status updates in UI.
   - Status-change notification arrives.
   - Content reflects updated status.
4. Mark `PASS` or `FAIL`.

## Test 4: Resolved Notification

1. Change status to resolved.
2. Save changes.
3. Verify expected result:
   - Request is resolved in UI.
   - Resolved notification arrives.
   - Content indicates completion.
4. Mark `PASS` or `FAIL`.

## Safety Test: Notifications Disabled

1. Temporarily set `NOTIFY_MODE=disabled` in dev.
2. Perform one notification-triggering action (for example, status change on a test request).
3. Verify expected result:
   - Business action succeeds.
   - No notification is sent.
4. Restore `NOTIFY_MODE=redirect`.
5. Mark `PASS` or `FAIL`.

## Optional Bilingual Check

1. Run at least one trigger in English session.
2. Run at least one trigger in French session.
3. Verify language/template output matches session language.
4. Mark `PASS` or `FAIL`.

## Failure Triage

If a test fails, collect:
1. Scenario name and timestamp.
2. Expected vs actual behavior.
3. Recipient mailbox that received (or did not receive) email.
4. Relevant application logs from notify send path.

## Quick Troubleshooting

Use this map to move quickly from error to fix:

| Where | Error | Meaning | Action |
|------|------|---------|--------|
| `check_curl2.php` | `SSL certificate problem: self-signed certificate in certificate chain` | TLS trust chain issue between app service and GC Notify | Preferred: set `GCNOTIFY_CURL_CA_BUNDLE`. Temporary dev test: set `GCNOTIFY_CURL_INSECURE=true`, retest, then revert to `false`. |
| `check_curl2.php` | `email_address Not a valid email address` | `GCNOTIFY_TEST_EMAIL` is empty or malformed | Set `GCNOTIFY_TEST_EMAIL` to a valid address and restart app service. |
| app logs | `GC Notify skipped: GCNOTIFY_API_KEY is missing.` | API key is not available at runtime | Set `GCNOTIFY_API_KEY` in app settings and restart. |
| app logs | `GC Notify returned HTTP 401/403` | Key invalid, wrong service, or recipient not allowed for key type | Verify key value, service, and recipient safelist. |
| app logs | `GC Notify redirect skipped: no safe redirect recipient configured...` | Redirect mode has no valid recipient | Ensure logged-in account email is valid or set `NOTIFY_OVERRIDE_EMAIL`. |

If direct checks pass but request workflow still fails:
1. Re-run one New Request test.
2. Capture request timestamp.
3. Compare app logs around that timestamp.
4. Confirm which template key was used in that flow.

## Merge Gate Recommendation

Allow merge only when all required tests pass:
- New request
- Reassign
- Status changed
- Resolved

Block merge when:
- Any required scenario fails
- Recipient routing is incorrect
- Unexpected external recipient receives notification

## Run Record Template

Use this section for each PR:

- PR:
- Branch:
- Tester:
- Date/time:
- Env:
- Test 1 (New): PASS/FAIL
- Test 2 (Reassign): PASS/FAIL
- Test 3 (Status change): PASS/FAIL
- Test 4 (Resolved): PASS/FAIL
- Safety test (Disabled mode): PASS/FAIL
- Optional bilingual: PASS/FAIL
- Notes:
