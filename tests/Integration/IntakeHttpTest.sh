#!/usr/bin/env bash
set -euo pipefail

base_url="${RMT_BASE_URL:-http://localhost:8080}"
work_dir=$(mktemp -d)
created_id=""
organization_created_id=""
organization_session_id=""
edit_session_id=""
employee_edit_session_id=""
terminal_subject_type=""
conditional_leaf_service_id=""
conditional_branch_service_id=""
conditional_subservice_id=""

cleanup() {
    if [[ -n "$conditional_subservice_id" ]]; then
        db_query "DELETE FROM tblsubservices WHERE id = ${conditional_subservice_id};" >/dev/null || true
    fi
    if [[ -n "$conditional_leaf_service_id" || -n "$conditional_branch_service_id" ]]; then
        db_query "DELETE FROM tblservices WHERE id IN (${conditional_leaf_service_id:-0}, ${conditional_branch_service_id:-0});" >/dev/null || true
    fi
    if [[ -n "$created_id" ]]; then
        db_query "DELETE FROM RequestFieldHistory WHERE requestID = (SELECT requestid FROM tbltriage WHERE id = ${created_id}); DELETE FROM tbladminlog WHERE triageid = ${created_id}; DELETE FROM tblcommlog WHERE triageid = ${created_id}; DELETE FROM tblfiles WHERE requestid = (SELECT requestid FROM tbltriage WHERE id = ${created_id}); DELETE FROM tbltriage WHERE id = ${created_id};" >/dev/null || true
    fi
    if [[ -n "$organization_created_id" ]]; then
        db_query "DELETE FROM tblorganizations WHERE id = ${organization_created_id};" >/dev/null || true
    fi
    if [[ -n "$organization_session_id" ]]; then
        db_query "DELETE FROM tblphp_sessions WHERE id = '${organization_session_id}';" >/dev/null || true
    fi
    if [[ -n "$edit_session_id" ]]; then
        db_query "DELETE FROM tblphp_sessions WHERE id = '${edit_session_id}';" >/dev/null || true
    fi
    if [[ -n "$employee_edit_session_id" ]]; then
        db_query "DELETE FROM tblphp_sessions WHERE id = '${employee_edit_session_id}';" >/dev/null || true
    fi
    if [[ -n "$terminal_subject_type" ]]; then
        if [[ "$terminal_subject_type" == '__NULL__' ]]; then
            db_query "UPDATE tblcatalogue SET request_subject_type = NULL WHERE id = ${terminal_catalogue_id};" >/dev/null || true
        else
            db_query "UPDATE tblcatalogue SET request_subject_type = '${terminal_subject_type}' WHERE id = ${terminal_catalogue_id};" >/dev/null || true
        fi
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

assert_not_contains() {
    local file=$1
    local unexpected=$2
    local message=$3

    if grep -Fq "$unexpected" "$file"; then
        printf 'FAIL: %s\n' "$message" >&2
        exit 1
    fi
    printf 'PASS: %s\n' "$message"
}

assert_control_contains() {
    local file=$1
    local control_id=$2
    local expected=$3
    local message=$4

    if ! sed -n "/id=\"${control_id}\"/,/>/p" "$file" | grep -Fq "$expected"; then
        printf 'FAIL: %s\n' "$message" >&2
        exit 1
    fi
    printf 'PASS: %s\n' "$message"
}

assert_appears_before() {
    local file=$1
    local first=$2
    local second=$3
    local message=$4
    local first_line second_line

    first_line=$(grep -Fn "$first" "$file" | head -1 | cut -d: -f1)
    second_line=$(grep -Fn "$second" "$file" | head -1 | cut -d: -f1)
    if [[ -z "$first_line" || -z "$second_line" || "$first_line" -ge "$second_line" ]]; then
        printf 'FAIL: %s\n' "$message" >&2
        exit 1
    fi
    printf 'PASS: %s\n' "$message"
}

protected_file_code=$(db_query 'SELECT code FROM tblfiles ORDER BY id LIMIT 1')
if [[ -n "$protected_file_code" ]]; then
    download_status=$(request -o /dev/null -w '%{http_code}' "${base_url}/download.php?code=${protected_file_code}")
    if [[ "$download_status" != "403" ]]; then
        printf 'FAIL: unauthorized file download returned HTTP %s instead of 403\n' "$download_status" >&2
        exit 1
    fi
    printf 'PASS: file downloads require request access\n'

    bulk_download_status=$(request -o /dev/null -w '%{http_code}' "${base_url}/download-selected.php?codes[]=${protected_file_code}")
    if [[ "$bulk_download_status" != "403" ]]; then
        printf 'FAIL: unauthorized bulk download returned HTTP %s instead of 403\n' "$bulk_download_status" >&2
        exit 1
    fi
    printf 'PASS: bulk file downloads require request access\n'
else
    printf 'SKIP: attachment authorization HTTP checks require an existing tblfiles row\n'
fi

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

terminal_subject_type=$(db_query "SELECT COALESCE(request_subject_type, '__NULL__') FROM tblcatalogue WHERE id = ${terminal_catalogue_id}")
db_query "UPDATE tblcatalogue SET request_subject_type = 'system' WHERE id = ${terminal_catalogue_id}" >/dev/null

request "${base_url}/openrequest.php?lang=en" > "$work_dir/openrequest.html"
assert_contains "$work_dir/openrequest.html" 'What do you need?' \
    'English intake asks what the client needs'
assert_contains "$work_dir/openrequest.html" 'What type of service?' \
    'English intake asks for the service type'
assert_contains \
    "$work_dir/openrequest.html" \
    "&quot;name&quot;:&quot;intake_selection&quot;,&quot;value&quot;:&quot;${terminal_catalogue_id}:0:0&quot;" \
    'enhanced terminal catalogue uses one atomic hierarchy value'

request "${base_url}/openrequest.php?lang=fr" > "$work_dir/openrequest-fr.html"
assert_contains "$work_dir/openrequest-fr.html" 'De quoi avez-vous besoin?' \
    'French intake asks what the client needs'
assert_contains "$work_dir/openrequest-fr.html" 'De quel type de service avez-vous besoin?' \
    'French intake asks for the service type'

request "${base_url}/openrequest.php?lang=en&wbdisable=true" > "$work_dir/openrequest-basic.html"
assert_control_contains "$work_dir/openrequest-basic.html" 'basic-intake-selection' 'required' \
    'simplified intake keeps the combined question required'
assert_contains "$work_dir/openrequest-basic.html" 'What do you need?' \
    'simplified intake uses the client-facing question'

request -X POST \
    --data-urlencode "intake_selection=${terminal_catalogue_id}:0:0" \
    "${base_url}/openrequest2.php?lang=en" > "$work_dir/terminal.html"
assert_contains "$work_dir/terminal.html" "name=\"catalogueid\" value=\"${terminal_catalogue_id}\"" \
    'terminal catalogue reaches step two'
assert_contains "$work_dir/terminal.html" 'name="serviceid" value="0"' \
    'terminal catalogue normalizes service ID to zero'
assert_contains "$work_dir/terminal.html" 'value="Treasury Board of Canada Secretariat (TBS)"' \
    'public intake loads organization titles and acronyms from the database'
assert_control_contains "$work_dir/terminal.html" 'departmentagency' 'value=""' \
    'new intake leaves department or agency blank'
assert_contains "$work_dir/terminal.html" '<h2>System information</h2>' \
    'system subject type renders the English heading'
assert_contains "$work_dir/terminal.html" 'name="request_subject"' \
    'step two uses the consistent request_subject field name'
assert_contains "$work_dir/terminal.html" '<label for="request_subject">' \
    'request subject has an explicitly associated label'
assert_contains "$work_dir/terminal.html" 'aria-describedby="request-subject-help"' \
    'request subject is associated with its help text'
assert_contains "$work_dir/terminal.html" 'id="request_subject"' \
    'request subject label target is present'
assert_contains "$work_dir/terminal.html" 'required' \
    'request subject conveys required state programmatically'
assert_contains "$work_dir/terminal.html" 'System name <strong>(required)</strong>' \
    'system subject type renders a required System name label'
assert_contains "$work_dir/terminal.html" 'What is the full name of the system?' \
    'system subject type renders associated help text'
assert_appears_before "$work_dir/terminal.html" 'id="request_subject"' 'id="additionalinfo"' \
    'request subject information appears before Additional information'

request -X POST \
    --data-urlencode "intake_selection=${terminal_catalogue_id}:0:0" \
    "${base_url}/openrequest2.php?lang=fr" > "$work_dir/system-fr.html"
assert_contains "$work_dir/system-fr.html" 'Nom du système <strong>(obligatoire)</strong>' \
    'system subject type renders the French label'
assert_contains "$work_dir/system-fr.html" 'Quel est le nom complet du système?' \
    'system subject type renders French help text'

db_query "UPDATE tblcatalogue SET request_subject_type = 'document' WHERE id = ${terminal_catalogue_id}" >/dev/null
request -X POST \
    --data-urlencode "intake_selection=${terminal_catalogue_id}:0:0" \
    "${base_url}/openrequest2.php?lang=en" > "$work_dir/document.html"
assert_contains "$work_dir/document.html" '<h2>Document information</h2>' \
    'document subject type renders the Document information heading'
assert_contains "$work_dir/document.html" 'Document title <strong>(required)</strong>' \
    'document subject type renders the Document title label'

request -X POST \
    --data-urlencode "intake_selection=${terminal_catalogue_id}:0:0" \
    "${base_url}/openrequest2.php?lang=fr" > "$work_dir/document-fr.html"
assert_contains "$work_dir/document-fr.html" '<h2>Renseignements sur le document</h2>' \
    'document subject type renders the French heading'
assert_contains "$work_dir/document-fr.html" 'Titre du document <strong>(obligatoire)</strong>' \
    'document subject type renders the French label'

db_query "UPDATE tblcatalogue SET request_subject_type = 'subject' WHERE id = ${terminal_catalogue_id}" >/dev/null
request -X POST \
    --data-urlencode "intake_selection=${terminal_catalogue_id}:0:0" \
    "${base_url}/openrequest2.php?lang=en" > "$work_dir/subject.html"
assert_contains "$work_dir/subject.html" '<h2>Request information</h2>' \
    'subject type renders the Request information heading'
assert_contains "$work_dir/subject.html" 'Subject <strong>(required)</strong>' \
    'subject type renders the Subject label'

request -X POST \
    --data-urlencode "intake_selection=${terminal_catalogue_id}:0:0" \
    "${base_url}/openrequest2.php?lang=fr" > "$work_dir/subject-fr.html"
assert_contains "$work_dir/subject-fr.html" 'Objet <strong>(obligatoire)</strong>' \
    'subject type renders the French label'
assert_contains "$work_dir/subject-fr.html" 'Quel est l’objet de votre demande?' \
    'subject type renders French help text'

draft_headers="$work_dir/draft-headers.txt"
draft_cookies="$work_dir/draft-cookies.txt"
request -D "$draft_headers" -o /dev/null -c "$draft_cookies" -X POST \
    --data-urlencode "catalogueid=${terminal_catalogue_id}" \
    --data 'serviceid=0' \
    --data 'subserviceid=0' \
    --data-urlencode 'request_subject=Restored request subject' \
    --data 'clientfname=' \
    --data 'clientlname=Draft' \
    --data 'clientemail=draft@example.com' \
    "${base_url}/openrequest3.php?lang=en"
if ! grep -Fiq 'location: /openrequest2.php?lang=en&status=failed' "$draft_headers"; then
    printf 'FAIL: failed public intake did not return to step two\n' >&2
    exit 1
fi
request -b "$draft_cookies" "${base_url}/openrequest2.php?lang=en" > "$work_dir/draft.html"
assert_contains "$work_dir/draft.html" 'value="Restored request subject"' \
    'request subject survives draft restoration'

missing_subject_before=$(db_query 'SELECT COUNT(*) FROM tbltriage')
request -D "$work_dir/missing-subject-headers.txt" -o /dev/null -X POST \
    --data-urlencode "catalogueid=${terminal_catalogue_id}" \
    --data 'serviceid=0' \
    --data 'subserviceid=0' \
    --data 'clientfname=Missing' \
    --data 'clientlname=Subject' \
    --data 'clientemail=missing-subject@example.com' \
    "${base_url}/openrequest3.php?lang=en"
missing_subject_after=$(db_query 'SELECT COUNT(*) FROM tbltriage')
if ! grep -Fiq 'location: /openrequest2.php?lang=en&status=failed' "$work_dir/missing-subject-headers.txt" \
    || [[ "$missing_subject_before" != "$missing_subject_after" ]]; then
    printf 'FAIL: public intake accepted a missing request subject\n' >&2
    exit 1
fi
printf 'PASS: public intake requires request subject server-side\n'

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
additional_info="Accessibility testing context for request details."
success_headers="$work_dir/success-headers.txt"

request -D "$success_headers" -o /dev/null -X POST \
    --data-urlencode "catalogueid=${terminal_catalogue_id}" \
    --data 'serviceid=0' \
    --data 'subserviceid=0' \
    --data-urlencode 'requesttitle=Client supplied generated title' \
    --data-urlencode 'request_subject=GC Accessibility Conformance Testing Tool' \
    --data 'clientfname=Prepared' \
    --data "clientlname=O'Reilly" \
    --data-urlencode "clientemail=${test_email}" \
    --data-urlencode 'departmentagency=Treasury Board of Canada Secretariat (TBS)' \
    --data-urlencode 'clientphone=613-555-0100' \
    --data-urlencode "clientnotes=${test_note}" \
    --data-urlencode "additionalinfo=${additional_info}" \
    --data 'notification=N' \
    "${base_url}/openrequest3.php?lang=en"

created_id=$(db_query "SELECT id FROM tbltriage WHERE clientemail = '${test_email}' ORDER BY id DESC LIMIT 1")

if [[ -z "${created_id:-}" ]]; then
    printf 'FAIL: prepared intake insert did not create a request\n' >&2
    exit 1
fi
printf 'PASS: prepared intake insert creates a request\n'

request_values=$(db_query "SELECT CONCAT(requestid, '|', COALESCE(title, ''), '|', COALESCE(request_subject, ''), '|', COALESCE(clientphone, ''), '|', COALESCE(additionalinfo, '')) FROM tbltriage WHERE id = ${created_id}")
request_id=${request_values%%|*}
expected_values="${request_id}|${request_id} - TBS - GC Accessibility Conformance Testing Tool|GC Accessibility Conformance Testing Tool||${additional_info}"
if [[ "$request_values" != "$expected_values" ]]; then
    printf 'FAIL: public intake did not store request details and the server-generated title\n' >&2
    exit 1
fi
printf 'PASS: public intake stores request details and generates title server-side\n'

additional_comms=$(db_query "SELECT COUNT(*) FROM tblcommlog WHERE triageid = ${created_id} AND notes = '${additional_info}'")
if [[ "$additional_comms" != '0' ]]; then
    printf 'FAIL: public intake stored Additional information as a communication\n' >&2
    exit 1
fi
printf 'PASS: public intake keeps Additional information out of communications\n'

department_note=$(db_query "SELECT notes FROM tblcommlog WHERE triageid = ${created_id} AND notes LIKE 'Department/agency:%' ORDER BY id DESC LIMIT 1")
if [[ "$department_note" != 'Department/agency: Treasury Board of Canada Secretariat' ]]; then
    printf 'FAIL: public intake did not preserve the department value\n' >&2
    exit 1
fi
printf 'PASS: public intake preserves the department value\n'

edit_session_id="rmt-edit-http-$$"
docker compose exec -T web php -r '
    session_id($argv[1]);
    require "/var/www/html/includes/session_start.php";
    $_SESSION = [
        "pid" => 8,
        "atype" => 1,
        "primary_atype" => 1,
        "is_superuser" => 1,
        "is_admin" => 1,
        "email" => "edit-http@example.invalid",
        "firstname" => "Edit",
        "team" => "1",
        "lang" => "en",
    ];
    session_write_close();
' "$edit_session_id"

request \
    -H "Cookie: PHPSESSID=${edit_session_id}" \
    "${base_url}/viewrequest.php?lang=en&rid=${created_id}" > "$work_dir/view-request.html"
assert_contains "$work_dir/view-request.html" '<dt>Additional information</dt>' \
    'request view labels intake Additional information in Request details'
assert_not_contains "$work_dir/view-request.html" 'Client communications log' \
    'request view omits the Client communications log'
additional_occurrences=$(grep -Fc "$additional_info" "$work_dir/view-request.html")
if [[ "$additional_occurrences" != '1' ]]; then
    printf 'FAIL: request view displayed Additional information outside Request details\n' >&2
    exit 1
fi
printf 'PASS: request view does not repeat Additional information in communications\n'

request \
    -H "Cookie: PHPSESSID=${edit_session_id}" \
    "${base_url}/viewrequest.php?lang=fr&rid=${created_id}" > "$work_dir/view-request-fr.html"
assert_contains "$work_dir/view-request-fr.html" '<dt>Renseignements supplémentaires</dt>' \
    'French request view uses the intake Additional information label'
assert_not_contains "$work_dir/view-request-fr.html" 'Journal des communications avec le client' \
    'French request view omits the Client communications log'

request \
    -H "Cookie: PHPSESSID=${edit_session_id}" \
    "${base_url}/index.php?lang=en" > "$work_dir/overview.html"
assert_contains "$work_dir/overview.html" ">${request_id} - TBS - GC Accessibility Conformance Testing Tool</a>" \
    'overview card uses the generated request title as its link text'
assert_not_contains "$work_dir/overview.html" ">a11y-${request_id}</a>" \
    'overview card does not use the ticket code as link text when a title exists'

request \
    -H "Cookie: PHPSESSID=${edit_session_id}" \
    "${base_url}/editrequest.php?lang=en&id=${created_id}" > "$work_dir/edit-request.html"
assert_not_contains "$work_dir/edit-request.html" 'id="serviceid"' \
    'staff edit form hides Service when the catalogue has no active services'
assert_not_contains "$work_dir/edit-request.html" 'id="subserviceid"' \
    'staff edit form hides Sub-service when no service is available'
assert_contains "$work_dir/edit-request.html" 'class="form-group divsubservice"' \
    'staff edit form retains the Sub-service target for dependent updates'
assert_control_contains "$work_dir/edit-request.html" 'requesttitle' 'name="requesttitle"' \
    'staff edit form displays request title'
assert_control_contains "$work_dir/edit-request.html" 'requesttitle' 'readonly="readonly"' \
    'staff edit form displays request title read-only'
assert_contains "$work_dir/edit-request.html" '<label for="request_subject">' \
    'staff edit form explicitly labels request subject'
assert_control_contains "$work_dir/edit-request.html" 'request_subject' 'name="request_subject"' \
    'staff edit form exposes request subject as the editable source'
assert_control_contains "$work_dir/edit-request.html" 'request_subject' 'required' \
    'authorized staff request subject is required programmatically'

request -H "Cookie: PHPSESSID=${edit_session_id}" \
    "${base_url}/addrequest-ajax1.php?v1=${terminal_catalogue_id}" > "$work_dir/services-empty.html"
assert_not_contains "$work_dir/services-empty.html" 'id="serviceid"' \
    'catalogue AJAX hides Service when no active services exist'

conditional_leaf_service_id=$(db_query "INSERT INTO tblservices (catalogueid, nameen, namefr, status) VALUES (${terminal_catalogue_id}, 'Conditional leaf service', 'Service terminal conditionnel', 1); SELECT LAST_INSERT_ID();")
conditional_branch_service_id=$(db_query "INSERT INTO tblservices (catalogueid, nameen, namefr, status) VALUES (${terminal_catalogue_id}, 'Conditional branch service', 'Service parent conditionnel', 1); SELECT LAST_INSERT_ID();")
conditional_subservice_id=$(db_query "INSERT INTO tblsubservices (serviceid, nameen, namefr, status) VALUES (${conditional_branch_service_id}, 'Conditional sub-service', 'Sous-service conditionnel', 1); SELECT LAST_INSERT_ID();")

request "${base_url}/openrequest.php?lang=en" > "$work_dir/openrequest-with-subservice.html"
assert_contains "$work_dir/openrequest-with-subservice.html" 'What specific service do you need?' \
    'English intake asks for the specific service when available'
request "${base_url}/openrequest.php?lang=fr" > "$work_dir/openrequest-with-subservice-fr.html"
assert_contains "$work_dir/openrequest-with-subservice-fr.html" 'De quel service précis avez-vous besoin?' \
    'French intake asks for the specific service when available'

request -H "Cookie: PHPSESSID=${edit_session_id}" \
    "${base_url}/addrequest-ajax1.php?v1=${terminal_catalogue_id}" > "$work_dir/services-available.html"
assert_contains "$work_dir/services-available.html" 'id="serviceid"' \
    'catalogue AJAX shows Service when active services exist'

request -H "Cookie: PHPSESSID=${edit_session_id}" \
    "${base_url}/addrequest-ajax2.php?v1=${conditional_leaf_service_id}" > "$work_dir/subservices-empty.html"
assert_not_contains "$work_dir/subservices-empty.html" 'id="subserviceid"' \
    'service AJAX hides Sub-service when no active sub-services exist'

request -H "Cookie: PHPSESSID=${edit_session_id}" \
    "${base_url}/addrequest-ajax2.php?v1=${conditional_branch_service_id}" > "$work_dir/subservices-available.html"
assert_contains "$work_dir/subservices-available.html" 'id="subserviceid"' \
    'service AJAX shows Sub-service when active sub-services exist'

request \
    -H "Cookie: PHPSESSID=${edit_session_id}" \
    "${base_url}/editrequest.php?lang=fr&id=${created_id}" > "$work_dir/edit-request-fr.html"
assert_contains "$work_dir/edit-request-fr.html" 'Titre de la demande' \
    'staff edit form renders the French request title label'
assert_contains "$work_dir/edit-request-fr.html" 'Objet de la demande' \
    'staff edit form renders the French request subject label'
assert_control_contains "$work_dir/edit-request-fr.html" 'departmentagency' 'value="Secrétariat du Conseil du Trésor du Canada (SCT)"' \
    'staff edit form localizes an English department selection in French'
assert_control_contains "$work_dir/edit-request-fr.html" 'departmentagency' 'aria-describedby="departmentagency-hint"' \
    'localized department selection is not described by the custom-name warning'
assert_control_contains "$work_dir/edit-request-fr.html" 'departmentagency-review' 'role="status" hidden' \
    'localized department selection hides the custom-name warning status'
assert_contains "$work_dir/edit-request-fr.html" "departmentInput.addEventListener('input', updateDepartmentReview)" \
    'editable department field updates the warning when its value changes'

request -o /dev/null -X POST \
    -H "Cookie: PHPSESSID=${edit_session_id}" \
    --data 'form_action=update_request' \
    --data-urlencode 'requesttitle=Direct title tampering must be ignored' \
    --data-urlencode 'request_subject=Corrected accessibility testing tool' \
    --data-urlencode 'departmentagency=Secrétariat du Conseil du Trésor du Canada (SCT)' \
    --data-urlencode 'clientfname=Prepared' \
    --data-urlencode "clientlname=O'Reilly" \
    --data-urlencode "clientemail=${test_email}" \
    --data-urlencode 'clientphone=' \
    --data-urlencode 'datereceived='"$(date +%Y-%m-%d)" \
    --data-urlencode 'slatimer='"$(date +%Y-%m-%d)" \
    --data 'statusid=1' \
    --data-urlencode "catalogueid=${terminal_catalogue_id}" \
    --data 'serviceid=0' \
    --data 'subserviceid=0' \
    --data 'workerid=0' \
    --data 'requestlang=en' \
    "${base_url}/editrequest.php?lang=fr&id=${created_id}"

edited_values=$(db_query "SELECT CONCAT(title, '|', request_subject) FROM tbltriage WHERE id = ${created_id}")
expected_edited_values="${request_id} - TBS - Corrected accessibility testing tool|Corrected accessibility testing tool"
if [[ "$edited_values" != "$expected_edited_values" ]]; then
    printf 'FAIL: subject correction did not regenerate the derived title\n' >&2
    exit 1
fi
printf 'PASS: direct title edits are ignored and subject correction regenerates title\n'

subject_history=$(db_query "SELECT CONCAT(COALESCE(oldValue, ''), '|', COALESCE(newValue, '')) FROM RequestFieldHistory WHERE requestID = '${request_id}' AND fieldName = 'request_subject' ORDER BY id DESC LIMIT 1")
if [[ "$subject_history" != 'GC Accessibility Conformance Testing Tool|Corrected accessibility testing tool' ]]; then
    printf 'FAIL: subject correction was not recorded in RequestFieldHistory\n' >&2
    exit 1
fi
printf 'PASS: subject correction records old and new values in field history\n'

localized_department_note=$(db_query "SELECT notes FROM tblcommlog WHERE triageid = ${created_id} AND notes LIKE 'Department/agency:%' ORDER BY id ASC LIMIT 1")
localized_department_history_count=$(db_query "SELECT COUNT(*) FROM RequestFieldHistory WHERE requestID = '${request_id}' AND fieldName = 'department_agency'")
if [[ "$localized_department_note" != 'Department/agency: Treasury Board of Canada Secretariat' || "$localized_department_history_count" != '0' ]]; then
    printf 'FAIL: saving a localized department rendering was recorded as a department change\n' >&2
    exit 1
fi
printf 'PASS: saving a localized department rendering preserves the stored organization identity\n'

request -o /dev/null -X POST \
    -H "Cookie: PHPSESSID=${edit_session_id}" \
    --data 'form_action=update_request' \
    --data-urlencode 'requesttitle=Another ignored title' \
    --data-urlencode 'request_subject=Corrected accessibility testing tool' \
    --data-urlencode 'departmentagency=Shared Services Canada (SSC)' \
    --data-urlencode 'clientfname=Prepared' \
    --data-urlencode "clientlname=O'Reilly" \
    --data-urlencode "clientemail=${test_email}" \
    --data-urlencode 'clientphone=' \
    --data-urlencode 'datereceived='"$(date +%Y-%m-%d)" \
    --data-urlencode 'slatimer='"$(date +%Y-%m-%d)" \
    --data 'statusid=1' \
    --data-urlencode "catalogueid=${terminal_catalogue_id}" \
    --data 'serviceid=0' \
    --data 'subserviceid=0' \
    --data 'workerid=0' \
    --data 'requestlang=en' \
    "${base_url}/editrequest.php?lang=en&id=${created_id}"

department_title=$(db_query "SELECT title FROM tbltriage WHERE id = ${created_id}")
if [[ "$department_title" != "${request_id} - SSC - Corrected accessibility testing tool" ]]; then
    printf 'FAIL: department correction did not regenerate the title acronym\n' >&2
    exit 1
fi
printf 'PASS: department correction regenerates the title acronym\n'

db_query "UPDATE tbltriage SET workerid = 8 WHERE id = ${created_id}" >/dev/null
employee_edit_session_id="rmt-employee-edit-http-$$"
docker compose exec -T web php -r '
    session_id($argv[1]);
    require "/var/www/html/includes/session_start.php";
    $_SESSION = [
        "pid" => 8,
        "atype" => 5,
        "primary_atype" => 5,
        "is_superuser" => 0,
        "is_admin" => 0,
        "email" => "employee-edit-http@example.invalid",
        "firstname" => "Employee",
        "team" => "1",
        "lang" => "en",
    ];
    session_write_close();
' "$employee_edit_session_id"

request \
    -H "Cookie: PHPSESSID=${employee_edit_session_id}" \
    "${base_url}/editrequest.php?lang=en&id=${created_id}" > "$work_dir/edit-request-employee.html"
assert_control_contains "$work_dir/edit-request-employee.html" 'request_subject' 'readonly="readonly"' \
    'restricted staff see request subject read-only'

request -o /dev/null -X POST \
    -H "Cookie: PHPSESSID=${employee_edit_session_id}" \
    --data 'form_action=update_request' \
    --data-urlencode 'requesttitle=Unauthorized direct title change' \
    --data-urlencode 'request_subject=Unauthorized subject change' \
    --data 'statusid=1' \
    --data 'workerid=8' \
    --data-urlencode 'slatimer='"$(date +%Y-%m-%d)" \
    "${base_url}/editrequest.php?lang=en&id=${created_id}"

restricted_values=$(db_query "SELECT CONCAT(title, '|', request_subject) FROM tbltriage WHERE id = ${created_id}")
if [[ "$restricted_values" != "${request_id} - SSC - Corrected accessibility testing tool|Corrected accessibility testing tool" ]]; then
    printf 'FAIL: restricted staff changed the derived title or request subject\n' >&2
    exit 1
fi
printf 'PASS: restricted staff cannot change request subject or derived title\n'

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
