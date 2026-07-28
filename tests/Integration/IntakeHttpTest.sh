#!/usr/bin/env bash
set -euo pipefail

base_url="${RMT_BASE_URL:-http://localhost:8080}"
work_dir=$(mktemp -d)
created_id=""
organization_created_id=""
organization_session_id=""

cleanup() {
    if [[ -n "$created_id" ]]; then
        db_query "DELETE FROM tbladminlog WHERE triageid = ${created_id}; DELETE FROM tblcommlog WHERE triageid = ${created_id}; DELETE FROM tblfiles WHERE requestid = (SELECT requestid FROM tbltriage WHERE id = ${created_id}); DELETE FROM tbltriage WHERE id = ${created_id};" >/dev/null || true
    fi
    if [[ -n "$organization_created_id" ]]; then
        db_query "DELETE FROM tblorganizations WHERE id = ${organization_created_id};" >/dev/null || true
    fi
    if [[ -n "$organization_session_id" ]]; then
        db_query "DELETE FROM tblphp_sessions WHERE id = '${organization_session_id}';" >/dev/null || true
    fi
    rm -rf "$work_dir"
}
trap cleanup EXIT

db_query() {
    docker compose exec -T db sh -lc \
        'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -N -B -e "$1"' \
        sh "$1" 2>/dev/null | tr -d '\r'
}

request() {
    curl -sS -H 'X-Forwarded-Proto: https' "$@"
}

assert_contains() {
    local file=$1
    local expected=$2
    local message=$3

    if ! grep -Fq "$expected" "$file"; then
        printf 'FAIL: %s\n' "$message" >&2
        exit 1
    fi
    printf 'PASS: %s\n' "$message"
}

download_status=$(request -o /dev/null -w '%{http_code}' "${base_url}/download.php?code=not-authorized")
if [[ "$download_status" != "403" ]]; then
    printf 'FAIL: unauthorized file download returned HTTP %s instead of 403\n' "$download_status" >&2
    exit 1
fi
printf 'PASS: file downloads require a session grant\n'

terminal_catalogue_id=$(db_query '
    SELECT c.id
    FROM tblcatalogue c
    WHERE c.status = 1
      AND NOT EXISTS (
          SELECT 1 FROM tblservices s
          WHERE s.catalogueid = c.id AND s.status = 1
      )
    ORDER BY c.id
    LIMIT 1
')

if [[ -z "$terminal_catalogue_id" ]]; then
    printf 'FAIL: no active terminal catalogue is available for HTTP tests\n' >&2
    exit 1
fi

request "${base_url}/openrequest.php?lang=en" > "$work_dir/openrequest.html"
assert_contains \
    "$work_dir/openrequest.html" \
    "&quot;name&quot;:&quot;intake_selection&quot;,&quot;value&quot;:&quot;${terminal_catalogue_id}:0:0&quot;" \
    'enhanced terminal catalogue uses one atomic hierarchy value'

request -X POST \
    --data-urlencode "intake_selection=${terminal_catalogue_id}:0:0" \
    "${base_url}/openrequest2.php?lang=en" > "$work_dir/terminal.html"
assert_contains "$work_dir/terminal.html" "name=\"catalogueid\" value=\"${terminal_catalogue_id}\"" \
    'terminal catalogue reaches step two'
assert_contains "$work_dir/terminal.html" 'name="serviceid" value="0"' \
    'terminal catalogue normalizes service ID to zero'
assert_contains "$work_dir/terminal.html" 'value="Treasury Board of Canada Secretariat (TBS)"' \
    'public intake loads organization titles and acronyms from the database'

IFS=$'\t' read -r leaf_catalogue_id leaf_service_id <<< "$(db_query '
    SELECT c.id, s.id
    FROM tblservices s
    INNER JOIN tblcatalogue c ON c.id = s.catalogueid
    WHERE c.status = 1 AND s.status = 1
      AND NOT EXISTS (
          SELECT 1 FROM tblsubservices ss
          WHERE ss.serviceid = s.id AND ss.status = 1
      )
    ORDER BY s.id
    LIMIT 1
')"

if [[ -n "${leaf_service_id:-}" ]]; then
    request -X POST \
        --data "catalogueid=${leaf_catalogue_id}&serviceid=${leaf_service_id}&subserviceid=999999" \
        "${base_url}/openrequest2.php?lang=en" > "$work_dir/leaf.html"
    assert_contains "$work_dir/leaf.html" 'name="subserviceid" value="0"' \
        'leaf service normalizes an injected subservice ID to zero'
fi

IFS=$'\t' read -r child_catalogue_id child_service_id child_subservice_id <<< "$(db_query '
    SELECT c.id, s.id, ss.id
    FROM tblsubservices ss
    INNER JOIN tblservices s ON s.id = ss.serviceid
    INNER JOIN tblcatalogue c ON c.id = s.catalogueid
    WHERE c.status = 1 AND s.status = 1 AND ss.status = 1
    ORDER BY ss.id
    LIMIT 1
')"

if [[ -n "${child_subservice_id:-}" ]]; then
    request -X POST \
        --data "catalogueid=${child_catalogue_id}&serviceid=${child_service_id}&subserviceid=${child_subservice_id}" \
        "${base_url}/openrequest2.php?lang=en" > "$work_dir/subservice.html"
    assert_contains "$work_dir/subservice.html" "name=\"subserviceid\" value=\"${child_subservice_id}\"" \
        'valid required subservice reaches step two'
fi

requests_before=$(db_query 'SELECT COUNT(*) FROM tbltriage')
response_headers="$work_dir/tampered-headers.txt"
request -D "$response_headers" -o /dev/null -X POST \
    --data 'catalogueid=999999&serviceid=999999&subserviceid=999999' \
    "${base_url}/openrequest3.php?lang=en"
requests_after=$(db_query 'SELECT COUNT(*) FROM tbltriage')

if ! grep -Fiq 'location: /openrequest.php?lang=en&status=failed#intake-error' "$response_headers"; then
    printf 'FAIL: tampered final submission redirects to the intake error\n' >&2
    exit 1
fi
printf 'PASS: tampered final submission redirects to the intake error\n'

if [[ "$requests_before" != "$requests_after" ]]; then
    printf 'FAIL: tampered final submission changed tbltriage row count\n' >&2
    exit 1
fi
printf 'PASS: tampered final submission creates no request\n'

test_email="prepared-intake-$$@example.com"
test_note="Client's note: keep 'quotes' exactly."
success_headers="$work_dir/success-headers.txt"

request -D "$success_headers" -o /dev/null -X POST \
    --data-urlencode "catalogueid=${terminal_catalogue_id}" \
    --data 'serviceid=0' \
    --data 'subserviceid=0' \
    --data-urlencode 'requesttitle=Client supplied title' \
    --data 'clientfname=Prepared' \
    --data "clientlname=O'Reilly" \
    --data-urlencode "clientemail=${test_email}" \
    --data-urlencode 'departmentagency=Client supplied department' \
    --data-urlencode 'clientphone=613-555-0100' \
    --data-urlencode "clientnotes=${test_note}" \
    --data 'notification=N' \
    "${base_url}/openrequest3.php?lang=en"

created_id=$(db_query "SELECT id FROM tbltriage WHERE clientemail = '${test_email}' ORDER BY id DESC LIMIT 1")

if [[ -z "${created_id:-}" ]]; then
    printf 'FAIL: prepared intake insert did not create a request\n' >&2
    exit 1
fi
printf 'PASS: prepared intake insert creates a request\n'

client_managed_values=$(db_query "SELECT CONCAT(COALESCE(title, ''), '|', COALESCE(clientphone, '')) FROM tbltriage WHERE id = ${created_id}")
if [[ "$client_managed_values" != '|' ]]; then
    printf 'FAIL: public intake accepted employee-managed title or phone values\n' >&2
    exit 1
fi
printf 'PASS: public intake ignores employee-managed title and phone values\n'

department_note=$(db_query "SELECT notes FROM tblcommlog WHERE triageid = ${created_id} AND notes LIKE 'Department/agency:%' ORDER BY id DESC LIMIT 1")
if [[ "$department_note" != 'Department/agency: Client supplied department' ]]; then
    printf 'FAIL: public intake did not preserve the department value\n' >&2
    exit 1
fi
printf 'PASS: public intake preserves the department value\n'

stored_note=$(db_query "SELECT notes FROM tblcommlog WHERE triageid = ${created_id} AND notes LIKE 'Client%' ORDER BY id DESC LIMIT 1")
if [[ "$stored_note" != "$test_note" ]]; then
    printf 'FAIL: prepared communication insert did not preserve quote-bearing note\n' >&2
    exit 1
fi
printf 'PASS: prepared communication insert preserves quote-bearing note\n'

organization_session_id="rmt-organization-http-$$"
organization_created_id=$(db_query "
    INSERT INTO tblorganizations
        (nameen, namefr, abbreviationen, abbreviationfr, source_part, status)
    VALUES
        ('Organization HTTP test', 'Test HTTP de l organisation', 'OLD', 'ANC', 1, 1);
    SELECT LAST_INSERT_ID();
")

docker compose exec -T web php -r '
    session_id($argv[1]);
    require "/var/www/html/includes/session_start.php";
    $_SESSION = [
        "pid" => 8,
        "atype" => 1,
        "primary_atype" => 1,
        "is_superuser" => 1,
        "is_admin" => 1,
        "email" => "organization-http@example.invalid",
        "firstname" => "Organization",
        "team" => "1",
        "lang" => "en",
    ];
    session_write_close();
' "$organization_session_id"

request \
    -H "Cookie: PHPSESSID=${organization_session_id}" \
    "${base_url}/organizations.php?lang=en" > "$work_dir/organizations.html"

assert_contains \
    "$work_dir/organizations.html" \
    'Records imported from the TBS Registry were loaded once on July 27, 2026 and are not automatically synchronized.' \
    'organization page explains that official records are a one-time snapshot'
assert_contains \
    "$work_dir/organizations.html" \
    'https://www.tbs-sct.gc.ca/ap/fip-pcim/reg-eng.asp' \
    'organization page links to the TBS Registry of Applied Titles'

request \
    -H "Cookie: PHPSESSID=${organization_session_id}" \
    "${base_url}/organizations.php?lang=fr" > "$work_dir/organizations-fr.html"
assert_contains \
    "$work_dir/organizations-fr.html" \
    'Les enregistrements importés du Registre du SCT ont été chargés une seule fois le 27 juillet 2026' \
    'French organization page explains that official records are a one-time snapshot'
assert_contains \
    "$work_dir/organizations-fr.html" \
    'https://www.tbs-sct.gc.ca/ap/fip-pcim/reg-fra.asp' \
    'French organization page links to the TBS Registry of Applied Titles'

assert_contains \
    "$work_dir/organizations.html" \
    "/includes/set-organization-status.php?id=${organization_created_id}&amp;lang=en" \
    'organization table uses a status lightbox link'

request \
    -H "Cookie: PHPSESSID=${organization_session_id}" \
    "${base_url}/includes/edit-organization.php?id=${organization_created_id}&lang=en" > "$work_dir/edit-organization.html"
assert_contains \
    "$work_dir/edit-organization.html" \
    "name=\"organization_id\" value=\"${organization_created_id}\"" \
    'organization edit dialog uses a WET-safe record key'
organization_csrf_token=$(sed -n 's/.*name="csrf_token" value="\([^"]*\)".*/\1/p' "$work_dir/edit-organization.html" | head -1)

if [[ ${#organization_csrf_token} -ne 64 ]]; then
    printf 'FAIL: organization edit dialog did not render a CSRF token\n' >&2
    exit 1
fi

request -D "$work_dir/organization-tampered-headers.txt" -o /dev/null \
    -H "Cookie: PHPSESSID=${organization_session_id}" \
    --data 'csrf_token=invalid' \
    --data 'organization_action=set_status' \
    --data-urlencode "organization_id=${organization_created_id}" \
    --data 'record_status=0' \
    "${base_url}/organizations.php?lang=en"

if ! grep -Fiq 'location: /organizations.php?lang=en&status=invalid_request' "$work_dir/organization-tampered-headers.txt"; then
    printf 'FAIL: organization action accepted a tampered CSRF token\n' >&2
    exit 1
fi
printf 'PASS: organization actions reject tampered CSRF tokens\n'

request -D "$work_dir/organization-edit-headers.txt" -o /dev/null \
    -H "Cookie: PHPSESSID=${organization_session_id}" \
    --data-urlencode "csrf_token=${organization_csrf_token}" \
    --data 'organization_action=save' \
    --data-urlencode "organization_id=${organization_created_id}" \
    --data 'nameen=Organization HTTP test' \
    --data 'namefr=Test HTTP de l organisation' \
    --data 'abbreviationen=NEW' \
    --data 'abbreviationfr=NOU' \
    --data 'record_status=1' \
    "${base_url}/organizations.php?lang=en"

if ! grep -Fiq 'location: /organizations.php?lang=en&status=success' "$work_dir/organization-edit-headers.txt"; then
    printf 'FAIL: organization edit did not accept the signed CSRF token\n' >&2
    exit 1
fi
printf 'PASS: organization edit accepts the signed CSRF token\n'

request \
    -H "Cookie: PHPSESSID=${organization_session_id}" \
    "${base_url}/includes/set-organization-status.php?id=${organization_created_id}&lang=en" > "$work_dir/set-organization-status.html"
assert_contains \
    "$work_dir/set-organization-status.html" \
    "name=\"organization_id\" value=\"${organization_created_id}\"" \
    'organization status dialog uses a WET-safe record key'
organization_status_csrf_token=$(sed -n 's/.*name="csrf_token" value="\([^"]*\)".*/\1/p' "$work_dir/set-organization-status.html" | head -1)

if [[ ${#organization_status_csrf_token} -ne 64 ]]; then
    printf 'FAIL: organization status dialog did not render a CSRF token\n' >&2
    exit 1
fi

request -D "$work_dir/organization-status-headers.txt" -o /dev/null \
    -H "Cookie: PHPSESSID=${organization_session_id}" \
    --data-urlencode "csrf_token=${organization_status_csrf_token}" \
    --data 'organization_action=set_status' \
    --data-urlencode "organization_id=${organization_created_id}" \
    --data 'record_status=0' \
    "${base_url}/organizations.php?lang=en"

if ! grep -Fiq 'location: /organizations.php?lang=en&status=success' "$work_dir/organization-status-headers.txt"; then
    printf 'FAIL: organization deactivation did not accept the signed CSRF token\n' >&2
    exit 1
fi

organization_values=$(db_query "SELECT CONCAT(abbreviationen, '|', abbreviationfr, '|', status, '|', source_part) FROM tblorganizations WHERE id = ${organization_created_id}")
if [[ "$organization_values" != 'NEW|NOU|0|1' ]]; then
    printf 'FAIL: imported organization changes were not saved with source provenance intact\n' >&2
    exit 1
fi
printf 'PASS: imported organization changes preserve source provenance\n'

request -D "$work_dir/organization-protected-delete-headers.txt" -o /dev/null \
    -H "Cookie: PHPSESSID=${organization_session_id}" \
    --data-urlencode "csrf_token=${organization_status_csrf_token}" \
    --data 'organization_action=delete' \
    --data-urlencode "organization_id=${organization_created_id}" \
    "${base_url}/organizations.php?lang=en"

if ! grep -Fiq 'location: /organizations.php?lang=en&status=failed' "$work_dir/organization-protected-delete-headers.txt"; then
    printf 'FAIL: imported organization deletion was not rejected\n' >&2
    exit 1
fi

organization_count=$(db_query "SELECT COUNT(*) FROM tblorganizations WHERE id = ${organization_created_id}")
if [[ "$organization_count" != '1' ]]; then
    printf 'FAIL: imported organization was deleted\n' >&2
    exit 1
fi
printf 'PASS: imported organizations cannot be deleted\n'

db_query "UPDATE tblorganizations SET source_part = 0 WHERE id = ${organization_created_id}" >/dev/null

request \
    -H "Cookie: PHPSESSID=${organization_session_id}" \
    "${base_url}/organizations.php?lang=en" > "$work_dir/organizations-manual.html"
assert_contains \
    "$work_dir/organizations-manual.html" \
    "/includes/delete-organization.php?id=${organization_created_id}&amp;lang=en" \
    'manual organization exposes a delete lightbox link'

request \
    -H "Cookie: PHPSESSID=${organization_session_id}" \
    "${base_url}/includes/delete-organization.php?id=${organization_created_id}&lang=en" > "$work_dir/delete-organization.html"
assert_contains \
    "$work_dir/delete-organization.html" \
    "name=\"organization_id\" value=\"${organization_created_id}\"" \
    'organization delete dialog uses a WET-safe record key'
organization_delete_csrf_token=$(sed -n 's/.*name="csrf_token" value="\([^"]*\)".*/\1/p' "$work_dir/delete-organization.html" | head -1)

request -D "$work_dir/organization-delete-headers.txt" -o /dev/null \
    -H "Cookie: PHPSESSID=${organization_session_id}" \
    --data-urlencode "csrf_token=${organization_delete_csrf_token}" \
    --data 'organization_action=delete' \
    --data-urlencode "organization_id=${organization_created_id}" \
    "${base_url}/organizations.php?lang=en"

if ! grep -Fiq 'location: /organizations.php?lang=en&status=success' "$work_dir/organization-delete-headers.txt"; then
    printf 'FAIL: manual organization deletion did not succeed\n' >&2
    exit 1
fi

organization_count=$(db_query "SELECT COUNT(*) FROM tblorganizations WHERE id = ${organization_created_id}")
if [[ "$organization_count" != '0' ]]; then
    printf 'FAIL: manual organization was not deleted\n' >&2
    exit 1
fi
organization_created_id=""
printf 'PASS: manual organizations can be deleted\n'
