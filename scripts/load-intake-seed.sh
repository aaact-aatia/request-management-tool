#!/usr/bin/env bash
# =============================================================================
# load-intake-seed.sh
#
# Loads a registered intake-flow seed into the local Docker MySQL service.
# Seeds are loaded explicitly; they are never part of Docker auto-initialization.
#
# Usage:
#   ./scripts/load-intake-seed.sh list
#   ./scripts/load-intake-seed.sh check <seed-name>
#   ./scripts/load-intake-seed.sh load  <seed-name>
#   ./scripts/load-intake-seed.sh help
#
# Adding a new seed (for another team or flow version):
#   1. Create your SQL file in database/seeds/<org>/<name>-vN.sql.
#      Copy database/seeds/template/intake-flow-template.sql as a starting point.
#   2. Add a case entry to seed_path() below.
#   3. Add the name to seed_names() below.
#   4. Run: ./scripts/load-intake-seed.sh load <org>/<name>-vN
#
# The loader is organization-neutral. SSC-specific knowledge lives only in the
# seed SQL files and their registry entries below.
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Expected Docker Compose service for the database
EXPECTED_DB_SERVICE="db"

# Safe identifier pattern — database names must match this exactly
SAFE_IDENT_PATTERN='^[A-Za-z0-9_]+$'

# =============================================================================
# Seed registry — implemented as functions for bash 3.2 compatibility.
# seed_path <name>  -> prints relative path, or empty string if unknown.
# seed_names        -> prints all registered names, one per line.
#
# Only names explicitly listed here are accepted.  Unknown names never reach
# the filesystem lookup.
# =============================================================================
seed_path() {
  local name="$1"
  case "$name" in
    "ssc/website-testing-v1") echo "database/seeds/ssc/website-testing-v1.sql" ;;
    # @@ADD new seeds here (org/name -> relative path):
    # "esdc/my-flow-v1") echo "database/seeds/esdc/my-flow-v1.sql" ;;
    *) echo "" ;;
  esac
}

seed_names() {
  echo "ssc/website-testing-v1"
  # @@ADD new seed names here (one per line):
  # echo "esdc/my-flow-v1"
}

# =============================================================================
# Helpers
# =============================================================================
usage() {
  cat <<USAGE_EOF
Usage: $(basename "$0") <command> [seed-name]

Commands:
  list            List all registered seeds and their file paths.
  check <name>    Check whether a seed's SQL file exists (does not query the DB).
  load  <name>    Load a seed into the local dev database.
  help            Show this help message.

Registered seed names:
USAGE_EOF
  seed_names | while IFS= read -r name; do
    printf "  %-40s %s\n" "$name" "$(seed_path "$name")"
  done
}

info()    { echo "[INFO]  $*"; }
success() { echo "[OK]    $*"; }
fail()    { echo "[ERROR] $*" >&2; exit 1; }

# =============================================================================
# Load .env — reads only the variables needed; never prints credentials.
# Sets: _app_env  _db_name  _db_host  _root_pw
# =============================================================================
load_env() {
  local env_file="${REPO_ROOT}/.env"
  [[ -f "$env_file" ]] || fail ".env not found at ${env_file}"

  _app_env=$(grep -E '^APP_ENV=' "$env_file" | head -1 | cut -d= -f2- | tr -d ' \t')
  _db_name=$(grep -E '^DB_NAME='  "$env_file" | head -1 | cut -d= -f2- | tr -d ' \t')
  _db_host=$(grep -E '^DB_HOST='  "$env_file" | head -1 | cut -d= -f2- | tr -d ' \t')
  _root_pw=$(grep -E '^MYSQL_ROOT_PASSWORD=' "$env_file" | head -1 | cut -d= -f2-)

  [[ "$_app_env" == "development" ]] \
    || fail "APP_ENV is '${_app_env}', not 'development'. The seed loader only runs in a local development environment."

  [[ -n "$_db_name" ]] || fail "DB_NAME not set in .env"
  [[ -n "$_db_host" ]] || fail "DB_HOST not set in .env"
  [[ -n "$_root_pw" ]] || fail "MYSQL_ROOT_PASSWORD not set in .env"

  # Validate DB_NAME before any SQL usage — must be a safe identifier
  [[ "$_db_name" =~ $SAFE_IDENT_PATTERN ]] \
    || fail "DB_NAME '${_db_name}' contains unsafe characters. It must match: ${SAFE_IDENT_PATTERN}"
}

# =============================================================================
# Verify prerequisites.
# DB_NAME is passed as a positional argument to mysql, not interpolated into
# any shell command string.
# =============================================================================
verify_db_reachable() {
  if ! docker compose -f "${REPO_ROOT}/docker-compose.yml" exec -T \
      -e MYSQL_PWD="$_root_pw" \
      "$EXPECTED_DB_SERVICE" \
      mysql -u root "$_db_name" -e "SELECT 1" \
      2>/dev/null | grep -q 1; then
    fail "Cannot reach database '${_db_name}'. Is 'docker compose up -d' running?"
  fi
  success "Database is reachable (${_db_host}/${_db_name})."
}

verify_intake_tables() {
  local table_count
  table_count=$(
    docker compose -f "${REPO_ROOT}/docker-compose.yml" exec -T \
      -e MYSQL_PWD="$_root_pw" \
      "$EXPECTED_DB_SERVICE" \
      mysql -u root "$_db_name" --batch --silent --skip-column-names \
      -e "SELECT COUNT(*) FROM information_schema.TABLES
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME IN (
              'tblintakeflows','tblintakenodes',
              'tblintakeoptions','tblintakeresources','tblintakeresponses'
            )" \
      2>/dev/null
  )
  if [[ "${table_count:-0}" -lt 5 ]]; then
    fail "Intake-flow tables are missing (found ${table_count:-0}/5). Apply migration 016-intake-flows.sql first."
  fi
  success "All 5 intake-flow tables are present."
}

# =============================================================================
# Commands
# =============================================================================
cmd_list() {
  echo "Registered seeds:"
  seed_names | while IFS= read -r name; do
    printf "  %-40s %s\n" "$name" "$(seed_path "$name")"
  done
}

cmd_check() {
  local name="${1:-}"
  [[ -n "$name" ]] || fail "No seed name provided. Run '$(basename "$0") list' to see options."
  local path
  path="$(seed_path "$name")"
  [[ -n "$path" ]] || fail "Unknown seed '${name}'. Run '$(basename "$0") list' to see registered names."

  local seed_file="${REPO_ROOT}/${path}"
  if [[ -f "$seed_file" ]]; then
    success "Seed file exists: ${path}"
  else
    fail "Seed file NOT found: ${path}"
  fi
}

cmd_load() {
  local name="${1:-}"
  [[ -n "$name" ]] || fail "No seed name provided. Run '$(basename "$0") list' to see options."
  local path
  path="$(seed_path "$name")"
  [[ -n "$path" ]] || fail "Unknown seed '${name}'. Run '$(basename "$0") list' to see registered names."

  local seed_file="${REPO_ROOT}/${path}"
  [[ -f "$seed_file" ]] || fail "Seed file not found: ${path}"

  load_env
  verify_db_reachable
  verify_intake_tables

  info "Loading seed '${name}' into '${_db_name}'..."

  # Run the seed. Capture combined stdout+stderr so we can inspect output and
  # preserve the exit code. DB_NAME is a positional arg, not shell-interpolated.
  local _seed_out _seed_rc
  _seed_rc=0
  _seed_out=$(
    docker compose -f "${REPO_ROOT}/docker-compose.yml" exec -T \
      -e MYSQL_PWD="$_root_pw" \
      "$EXPECTED_DB_SERVICE" \
      mysql -u root "$_db_name" \
      < "$seed_file" 2>&1
  ) || _seed_rc=$?

  # Remove the standard password-on-command-line warning
  local _seed_clean
  _seed_clean=$(echo "$_seed_out" \
    | grep -v 'Using a password on the command line interface can be insecure')

  if [[ "$_seed_rc" -ne 0 ]]; then
    echo "$_seed_clean" >&2
    fail "Seed '${name}' failed (exit ${_seed_rc}). See error output above."
  fi

  # Surface the SQL message row (SUCCESS / already installed / VALIDATION ERROR)
  echo "$_seed_clean" | grep -v '^$' || true
  success "Seed '${name}' completed."
}

# =============================================================================
# Entry point
# =============================================================================
cd "$REPO_ROOT"

case "${1:-help}" in
  list)            cmd_list ;;
  check)           cmd_check "${2:-}" ;;
  load)            cmd_load  "${2:-}" ;;
  help|--help|-h)  usage ;;
  *)               usage; exit 1 ;;
esac
