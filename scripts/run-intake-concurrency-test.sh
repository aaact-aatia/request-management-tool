#!/usr/bin/env bash

set -euo pipefail

repo_root=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
suffix="${PPID}-$$"
network="rmt-session-lock-test-${suffix}"
mysql_container="rmt-session-lock-mysql-${suffix}"
php_container="rmt-session-lock-php-${suffix}"
database="rmt_session_test"
password="rmt-session-lock-${suffix}"

cleanup() {
    docker rm -f "$php_container" >/dev/null 2>&1 || true
    docker rm -f "$mysql_container" >/dev/null 2>&1 || true
    docker network rm "$network" >/dev/null 2>&1 || true
}
trap cleanup EXIT

cd "$repo_root"

web_image=$(docker compose images -q web)
if [[ -z "$web_image" ]]; then
    docker compose build web
    web_image=$(docker compose images -q web)
fi

docker network create "$network" >/dev/null
docker run -d \
    --name "$mysql_container" \
    --network "$network" \
    --network-alias database \
    -e MYSQL_ROOT_PASSWORD="$password" \
    -e MYSQL_DATABASE="$database" \
    mysql:5.7 >/dev/null

docker exec "$mysql_container" timeout 30 sh -c \
    'until mysql -h127.0.0.1 -uroot -p"$MYSQL_ROOT_PASSWORD" -N -s -e "SELECT 1" >/dev/null 2>&1; do sleep 0.25; done'

docker run --rm \
    --name "$php_container" \
    --entrypoint php \
    --network "$network" \
    -v "$repo_root:/workspace:ro" \
    -w /workspace \
    -e RMT_TEST_DB_HOST=database \
    -e RMT_TEST_DB_PORT=3306 \
    -e RMT_TEST_DB_USER=root \
    -e RMT_TEST_DB_PASS="$password" \
    -e RMT_TEST_DB_NAME="$database" \
    "$web_image" \
    tests/Integration/MySQLSessionHandlerConcurrencyTest.php

remaining=$(docker exec "$mysql_container" mysql -N -s -uroot -p"$password" "$database" \
    -e 'SELECT COUNT(*) FROM tblphp_sessions' 2>/dev/null)
if [[ "$remaining" != 0 ]]; then
    printf 'Expected no disposable session rows, found %s.\n' "$remaining" >&2
    exit 1
fi

printf '%s\n' 'PASS: disposable session table is empty after the concurrency test'