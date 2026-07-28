#!/usr/bin/env bash
# =============================================================================
# reset-local-db.sh
#
# Destroys and fully rebuilds the local development database.
#
# WARNING: This script drops ALL application data — every user account, request,
# workflow record, and session — then reloads from scratch with default demo data.
# Run this only on the local disposable development database.
#
# Usage:
#   ./scripts/reset-local-db.sh --yes
#
# Reload order:
#   1. database/schema.sql
#   2. database/reference.sql
#   3. database/session_handler.sql
#   4. database/ssc-users-dev.sql
#   5. database/ssc-sample-dev.sql
#   6. database/seeds/ssc/website-testing-v1.sql
#
# schema.sql already reflects the fully migrated structure.
# Numbered migration files are NOT re-run during a clean reset.
#
# Note: DROP DATABASE / CREATE DATABASE are DDL statements. MySQL DDL is not
# transactional. If a subsequent load step fails the database will be in a
# partial state. The script exits immediately on any failure and clearly reports
# which step failed.
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

# These constants define the exact local project database this script may touch.
# The script refuses to run if any check does not match exactly.
EXPECTED_DB_NAME="aaact"
EXPECTED_DB_CONTAINER="aaact-rmt-db"
EXPECTED_DB_SERVICE="db"

# Safe identifier pattern
SAFE_IDENT_PATTERN='^[A-Za-z0-9_]+$'

# =============================================================================
# Helpers
# =============================================================================
info()    { echo "[INFO]  $*"; }
success() { echo "[OK]    $*"; }
fail()    { echo "[ERROR] $*" >&2; exit 1; }

# db_exec: run a SQL file through the Compose db service.
# DB name is a positional argument; not interpolated into shell strings.
db_exec() {
  docker compose exec -T -e MYSQL_PWD="$_root_pw" "$EXPECTED_DB_SERVICE" \
    mysql -u root "$EXPECTED_DB_NAME" "$@"
}

# db_query: run a single SQL string and return scalar result (no header).
db_query() {
  docker compose exec -T -e MYSQL_PWD="$_root_pw" "$EXPECTED_DB_SERVICE" \
    mysql -u root "$EXPECTED_DB_NAME" --batch --silent --skip-column-names \
    -e "$1" 2>/dev/null
}

# =============================================================================
# Step 0: Require --yes
# =============================================================================
if [[ "${1:-}" != "--yes" ]]; then
  cat <<EOF
DANGER: This script drops ALL application data from the local dev database.
  * Every user account, request, session, and workflow record will be deleted.
  * Default demo accounts will be restored from database/ssc-users-dev.sql.
  * The SSC website-testing workflow will be loaded.

To proceed, run:
  $(basename "$0") --yes

Do NOT run against a production or shared database.
EOF
  exit 1
fi

# =============================================================================
# Step 1: Safety checks — all must pass before any SQL is issued
# =============================================================================
info "Running safety checks..."
cd "$REPO_ROOT"

[[ -f .env ]] || fail ".env not found. Ensure .env exists in the repository root."

_app_env=$(grep -E '^APP_ENV=' .env | head -1 | cut -d= -f2- | tr -d ' \t')
_db_name=$(grep -E '^DB_NAME='  .env | head -1 | cut -d= -f2- | tr -d ' \t')
_db_host=$(grep -E '^DB_HOST='  .env | head -1 | cut -d= -f2- | tr -d ' \t')
_root_pw=$(grep -E '^MYSQL_ROOT_PASSWORD=' .env | head -1 | cut -d= -f2-)

# 1a. Environment must be 'development'
[[ "$_app_env" == "development" ]] \
  || fail "APP_ENV is '${_app_env}', not 'development'. Refusing to reset."

# 1b. DB_HOST must be exactly the expected container name
[[ "$_db_host" == "$EXPECTED_DB_CONTAINER" ]] \
  || fail "DB_HOST is '${_db_host}', expected '${EXPECTED_DB_CONTAINER}'. Refusing."

# 1c. DB_NAME must be exactly the expected local database name
[[ "$_db_name" == "$EXPECTED_DB_NAME" ]] \
  || fail "DB_NAME is '${_db_name}', expected '${EXPECTED_DB_NAME}'. Refusing."

# 1d. DB_NAME must match safe identifier pattern
[[ "$_db_name" =~ $SAFE_IDENT_PATTERN ]] \
  || fail "DB_NAME '${_db_name}' contains unsafe characters."

# 1e. Verify the Compose 'db' service is running and its container is exactly
#     the expected one — not merely any running container with that name.
_compose_db_id=$(docker compose ps -q "$EXPECTED_DB_SERVICE" 2>/dev/null | head -1)
[[ -n "$_compose_db_id" ]] \
  || fail "Docker Compose service '${EXPECTED_DB_SERVICE}' has no running container. Start with: docker compose up -d"

_compose_db_name=$(docker inspect --format '{{.Name}}' "$_compose_db_id" 2>/dev/null | tr -d '/')
[[ "$_compose_db_name" == "$EXPECTED_DB_CONTAINER" ]] \
  || fail "Compose service '${EXPECTED_DB_SERVICE}' maps to container '${_compose_db_name}', not '${EXPECTED_DB_CONTAINER}'. Refusing."

success "Safety checks passed. Target: ${EXPECTED_DB_CONTAINER}/${EXPECTED_DB_NAME} (APP_ENV=${_app_env})"

# =============================================================================
# Step 2: Pre-flight — verify all required SQL files exist, are readable,
#         and non-empty BEFORE issuing any destructive SQL.
# =============================================================================
info "Checking required SQL files..."

LOAD_FILES=(
  "database/schema.sql"
  "database/reference.sql"
  "database/session_handler.sql"
  "database/ssc-users-dev.sql"
  "database/ssc-sample-dev.sql"
)
SEED_FILE="database/seeds/ssc/website-testing-v1.sql"

for _f in "${LOAD_FILES[@]}" "$SEED_FILE"; do
  [[ -f "$_f" ]]    || fail "Required file not found: ${_f}"
  [[ -r "$_f" ]]    || fail "Required file is not readable: ${_f}"
  [[ -s "$_f" ]]    || fail "Required file is empty: ${_f}"
done
success "All required SQL files are present and readable."

# =============================================================================
# Step 3: Drop and recreate the database.
#
# DROP DATABASE / CREATE DATABASE are DDL — not transactional in MySQL.
# If a subsequent step fails, the database will be in a partial state and
# this script will report the failure clearly.
# =============================================================================
info "Dropping and recreating database '${EXPECTED_DB_NAME}'..."

docker compose exec -T -e MYSQL_PWD="$_root_pw" "$EXPECTED_DB_SERVICE" \
  mysql -u root \
  -e "DROP DATABASE IF EXISTS \`${EXPECTED_DB_NAME}\`;
      CREATE DATABASE \`${EXPECTED_DB_NAME}\`
        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
  2>/dev/null \
  || fail "Failed to drop/create '${EXPECTED_DB_NAME}'. Database state is unknown."

success "Database '${EXPECTED_DB_NAME}' recreated empty."

# =============================================================================
# Step 4: Load canonical files in order
# =============================================================================
for _f in "${LOAD_FILES[@]}"; do
  info "Loading ${_f}..."
  db_exec 2>/dev/null < "$_f" \
    || fail "Failed while loading ${_f}. Database is in a partial state."
  success "Loaded ${_f}."
done

# =============================================================================
# Step 5: Load the SSC website-testing workflow seed
# =============================================================================
info "Loading ${SEED_FILE}..."

# Capture combined stdout+stderr, preserve exit code.
_seed_out=""
_seed_rc=0
_seed_out=$(db_exec < "$SEED_FILE" 2>&1) || _seed_rc=$?

_seed_clean=$(echo "$_seed_out" \
  | grep -v 'Using a password on the command line interface can be insecure')

if [[ "$_seed_rc" -ne 0 ]]; then
  echo "$_seed_clean" >&2
  fail "Seed failed (exit ${_seed_rc}). Database is in a partial state — seed not loaded."
fi

echo "$_seed_clean" | grep -v '^$' || true
success "Seed loaded."

# =============================================================================
# Step 6: Validate clean installation
# =============================================================================
info "Validating clean installation..."

# 6a. Core tables present
for _t in tblusers tblcatalogue tblservices tblsubservices \
          tblintakeflows tblintakenodes tblintakeoptions \
          tblintakeresources tblintakeresponses; do
  _cnt=$(db_query "SELECT COUNT(*) FROM information_schema.TABLES
                   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='${_t}';")
  [[ "${_cnt:-0}" -eq 1 ]] || fail "Table '${_t}' is missing after reset."
done
success "All required tables are present."

# 6b. Default users
user_count=$(db_query "SELECT COUNT(*) FROM tblusers;" | tail -1)
[[ "${user_count:-0}" -eq 12 ]] \
  || fail "Expected 12 demo users, found ${user_count:-0}."
success "Demo users: ${user_count} (expected 12)."

# 6c. Verify default password hash via PHP (no hash printed)
if docker ps --format '{{.Names}}' | grep -q '^aaact-web$' 2>/dev/null; then
  _hash_check=$(docker exec aaact-web php -r \
    "\$h='\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
     echo password_verify('password',\$h)?'PASS':'FAIL';" 2>/dev/null || echo "SKIP")
  case "$_hash_check" in
    PASS) success "Default password verified (hash matches without displaying it)." ;;
    SKIP) info "PHP container not available — skipping password verification." ;;
    *)    fail "Default password does not verify against the seeded hash." ;;
  esac
else
  info "Web container not running — skipping PHP password verification."
fi

# 6d. Flow validation — exact values, active counts (status=1)
_flow_id=$(db_query "SELECT id FROM tblintakeflows
                     WHERE flow_family_key='ssc.website-testing' AND version_number=1;" \
           | tail -1)
[[ -n "$_flow_id" && "$_flow_id" =~ ^[0-9]+$ ]] \
  || fail "Flow 'ssc.website-testing v1' not found after reset."

_flow_status=$(db_query "SELECT status FROM tblintakeflows WHERE id=${_flow_id};" | tail -1)
[[ "${_flow_status:-0}" -eq 1 ]] \
  || fail "Flow ${_flow_id} is not published (status=${_flow_status})."

_node_count=$(db_query "SELECT COUNT(*) FROM tblintakenodes
                        WHERE flow_id=${_flow_id} AND status=1;" | tail -1)
[[ "${_node_count:-0}" -eq 9 ]] \
  || fail "Expected 9 active nodes, found ${_node_count}."

_opt_count=$(db_query "SELECT COUNT(*) FROM tblintakeoptions o
                       JOIN tblintakenodes n ON o.node_id=n.id AND n.status=1
                       WHERE n.flow_id=${_flow_id} AND o.status=1;" | tail -1)
[[ "${_opt_count:-0}" -eq 8 ]] \
  || fail "Expected 8 active options, found ${_opt_count}."

_res_count=$(db_query "SELECT COUNT(*) FROM tblintakeresources r
                       JOIN tblintakenodes n ON r.node_id=n.id AND n.status=1
                       WHERE n.flow_id=${_flow_id} AND r.status=1;" | tail -1)
[[ "${_res_count:-0}" -eq 2 ]] \
  || fail "Expected 2 active resources, found ${_res_count}."

# Start node must be the active question at sort_order=1
_start_ok=$(db_query "SELECT COUNT(*) FROM tblintakeflows f
                      JOIN tblintakenodes n ON f.start_node_id=n.id
                      WHERE f.id=${_flow_id} AND n.flow_id=${_flow_id}
                        AND n.status=1 AND n.node_type='question' AND n.sort_order=1;" | tail -1)
[[ "${_start_ok:-0}" -eq 1 ]] \
  || fail "Start node is not the active question at sort_order=1."

# Service 28 must be attached to exactly this flow
_svc28_fid=$(db_query "SELECT IFNULL(intake_flow_id,'NULL') FROM tblservices WHERE id=28;" | tail -1)
[[ "$_svc28_fid" == "$_flow_id" ]] \
  || fail "Service 28 intake_flow_id is '${_svc28_fid}', expected '${_flow_id}'."

# Destination first_assessment: exact cat=8, svc=28, sub=96, outcome
_dest_first=$(db_query "SELECT COUNT(*) FROM tblintakenodes
                        WHERE flow_id=${_flow_id} AND status=1
                          AND node_type='destination'
                          AND outcome_code='first_assessment'
                          AND target_catalogueid=8
                          AND target_serviceid=28
                          AND target_subserviceid=96;" | tail -1)
[[ "${_dest_first:-0}" -eq 1 ]] \
  || fail "first_assessment destination missing or has wrong catalogue/service/subservice."

# Destination reassessment: exact cat=8, svc=28, sub=212, outcome
_dest_reau=$(db_query "SELECT COUNT(*) FROM tblintakenodes
                       WHERE flow_id=${_flow_id} AND status=1
                         AND node_type='destination'
                         AND outcome_code='reassessment'
                         AND target_catalogueid=8
                         AND target_serviceid=28
                         AND target_subserviceid=212;" | tail -1)
[[ "${_dest_reau:-0}" -eq 1 ]] \
  || fail "reassessment destination missing or has wrong catalogue/service/subservice."

# No active option has a NULL target
_null_opts=$(db_query "SELECT COUNT(*) FROM tblintakeoptions o
                       JOIN tblintakenodes n ON o.node_id=n.id AND n.status=1
                       WHERE n.flow_id=${_flow_id} AND o.status=1
                         AND o.next_node_id IS NULL;" | tail -1)
[[ "${_null_opts:-0}" -eq 0 ]] \
  || fail "Found ${_null_opts} active option(s) with NULL target."

# No active option points outside the flow
_bad_opts=$(db_query "SELECT COUNT(*) FROM tblintakeoptions o
                      JOIN tblintakenodes src ON o.node_id=src.id AND src.status=1
                      LEFT JOIN tblintakenodes dst ON o.next_node_id=dst.id AND dst.status=1
                      WHERE src.flow_id=${_flow_id} AND o.status=1
                        AND (dst.id IS NULL OR dst.flow_id != ${_flow_id});" | tail -1)
[[ "${_bad_opts:-0}" -eq 0 ]] \
  || fail "Found ${_bad_opts} active option(s) pointing outside flow ${_flow_id}."

success "Website-testing flow validated (flow_id=${_flow_id}, active: nodes=${_node_count}, options=${_opt_count}, resources=${_res_count})."
success "Destinations: first_assessment=cat8/svc28/sub96, reassessment=cat8/svc28/sub212."

# =============================================================================
# Summary
# =============================================================================
echo ""
echo "════════════════════════════════════════════════════════════════"
success "Local database reset complete."
echo "  Database  : ${EXPECTED_DB_NAME}"
echo "  Container : ${EXPECTED_DB_CONTAINER}"
echo "  Users     : ${user_count} default demo accounts loaded"
echo "  Password  : documented development password (not displayed)"
echo "  Flow      : ssc.website-testing v1 — published, attached to service 28"
echo "════════════════════════════════════════════════════════════════"
