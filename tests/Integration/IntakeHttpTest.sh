#!/usr/bin/env bash
set -euo pipefail

base_url="${RMT_BASE_URL:-http://localhost:8080}"
work_dir=$(mktemp -d)
trap 'rm -rf "$work_dir"' EXIT

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