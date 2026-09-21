# Manual Smoke-Test Checklist

Use this checklist after making a routine content or configuration change in the Request Management Tool. It is written for administrators and content editors who use the application through a web browser and do not need PHP, Docker, or database access.

This checklist confirms the core request workflow. It does not replace the automated tests described in [TESTING.md](../TESTING.md) and [tests/README.md](../tests/README.md).

## Before You Begin

- Use a non-production or approved test environment whenever possible.
- Confirm that you have an administrator account and the correct permission for the change.
- Make one related change at a time so a problem can be traced to the right change.
- Record the date, environment, change made, and your name in the team change record.
- Do not enter real client personal information into a test request.
- Use a clearly identifiable test title, such as `Smoke test - catalogue update - 2026-09-21`.

## Part 1: Make the Routine Change

Use the administration menu to make one small, reversible change. Examples include:

- Add or edit a catalogue, service, or sub-service.
- Add or edit a team contact.
- Add or edit a holiday.
- Add or edit a request status label.

1. Sign in to the application.
2. Open the relevant page from the **Administration** menu.
3. Confirm that you are editing the intended environment and language.
4. Record the existing value before changing it.
5. Make the smallest necessary change.
6. Save the change.
7. Confirm that the application shows a success message or that the updated value appears in the list.
8. If the save fails, stop here. Do not continue with the request workflow until the change has been investigated.

For catalogue changes, check the related service and sub-service pages as well. A service must remain associated with the intended catalogue, and a sub-service must remain associated with the intended service.

## Part 2: Submit a Test Request

1. Open **New request**.
2. Complete step 1 using the updated catalogue or content, if the change affects the intake choices.
3. Confirm that dependent service and sub-service choices show the expected values.
4. Continue to step 2.
5. Complete the required fields with test data:
   - Use a test name and an approved test email address.
   - Use a title that identifies this smoke test.
   - Do not use real client information.
6. Review the information for accuracy.
7. Submit the request.
8. Record the request number shown after submission.
9. Confirm that the submission success message or confirmation page appears.

If the updated catalogue, service, sub-service, status, or other content is missing or incorrect, stop and record what appeared on screen. Do not submit repeated requests while investigating.

## Part 3: Review the Request in the Queue

1. Open **Requests** or the request overview.
2. Find the test request using its request number or title.
3. Open the request.
4. Confirm that the following information is present and correct:
   - Request number and title.
   - Catalogue, service, and sub-service, when applicable.
   - Client language.
   - Test contact information.
   - Received date and current status.
5. Confirm that the request appears only in the expected team or queue.

## Part 4: Change the Status

1. From the request details page, choose an ordinary working status such as **In progress**.
2. Save the update.
3. Confirm that a success message appears.
4. Refresh the page once.
5. Confirm that the new status remains selected and the request is still visible in the expected queue.
6. Confirm that the request history or communication area records the update, when that area is available to your account.

Do not resolve or close the test request unless your team specifically requires that part of the test. If you do resolve it, follow the team procedure for the client survey and cleanup afterward.

## Part 5: Check Notifications

The expected result depends on the environment's notification configuration:

- **Live:** the configured recipients may receive a real email.
- **Redirect:** the notification is sent to the configured test or redirect mailbox instead of the normal recipient.
- **Disabled:** no email is expected, but the application should still complete the request update without an error.

1. Check the communication or notification information on the request, if it is visible to your account.
2. If the environment uses redirect mode, check only the approved redirect or test mailbox.
3. Confirm that the notification identifies the correct request number and status.
4. Confirm that the message language matches the request language.
5. If the environment records notification activity or errors, check the relevant administration or error-log view.
6. Do not treat the absence of a client email as a failure when notifications are intentionally redirected or disabled.

Never send a test notification to a real client or an unapproved external address.

## Part 6: Sign Out

1. Sign out using the account menu.
2. Confirm that the application returns to the sign-in page or another signed-out page.
3. Use the browser Back button once, if appropriate, and confirm that protected request information is not displayed without signing in again.

## Pass Criteria

The smoke test passes when:

- The routine change saved successfully.
- The updated content was available where expected.
- A test request could be submitted without an error.
- The request appeared in the correct queue.
- The request details and status update were correct after refresh.
- Notification behavior matched the environment configuration, or the configured disabled mode was respected.
- The user could sign out and protected information was not available while signed out.

## If Something Looks Wrong

Stop testing and record:

- Environment and application URL.
- Date and time, including time zone if known.
- The change that was made.
- Request number and test title.
- Page URL and language.
- Exact error text.
- What you expected and what happened.
- A screenshot, if the screenshot does not contain real client information.

Contact the application owner or support team using your normal escalation channel. Ask the technical maintainer to check the application error log, web and database logs, request history, and GC Notify configuration as appropriate. Do not edit the database directly, repeatedly resubmit the request, or change notification settings while investigating unless the application owner asks you to.

After the problem is resolved, repeat the checklist from Part 1 in a fresh test request and record the result.

## Cleanup

Follow the environment's data-retention procedure after testing. Remove or anonymize the test request only if your role and local procedure allow it. Do not delete a request from production without an approved change or retention decision.

## Technical References

- [Automated testing overview](../TESTING.md)
- [Test suite documentation](../tests/README.md)
- [Maintenance and utility scripts](maintenance-scripts.md)
- [Configuration change control](config-management/change-control-sop.md)
