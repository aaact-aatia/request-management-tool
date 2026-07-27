#!/usr/bin/env bash
set -euo pipefail

base_url="${RMT_BASE_URL:-http://localhost:8080}"
work_dir=$(mktemp -d)
created_id=""

cleanup() {
    if [[ -n "$created_id" ]]; then
        db_query "DELETE FROM tbladminlog WHERE triageid = ${created_id}; DELETE FROM tblcommlog WHERE triageid = ${created_id}; DELETE FROM tblfiles WHERE requestid = (SELECT requestid FROM tbltriage WHERE id = ${created_id}); DELETE FROM tbltriage WHERE id = ${created_id};" >/dev/null || true
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
