#!/bin/bash
# GC Notify API test script (shell version)
# Requires GCNOTIFY_API_KEY, GCNOTIFY_TEMPLATE_ID, and GCNOTIFY_TEST_EMAIL in ../.env
# See docs/future/006-gcnotify-integration.md

# Load env vars from project root .env
set -a
source ../.env
set +a

curl --location 'https://api.notification.canada.ca/v2/notifications/email' \
--header 'Content-Type: application/json' \
--header "Authorization: ApiKey-v1 ${GCNOTIFY_API_KEY}" \
--data-raw '{
    "email_address":"'"${GCNOTIFY_TEST_EMAIL}"'",
    "template_id":"'"${GCNOTIFY_TEMPLATE_ID}"'",
    "personalisation":{
        "requestid": 1,
        "nrequestid": 1,
        "teamname": "Test Team",
        "requesttitle": "Test Request",
        "nrequestemailid": 1,
        "nrequestemail": "'"${GCNOTIFY_TEST_EMAIL}"'"
    }
}'
