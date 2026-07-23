#!/bin/sh
set -eu

project_name="rmt-integration-tests"

cleanup() {
	docker compose -p "$project_name" -f docker-compose.test.yml down --volumes --remove-orphans
}
trap cleanup EXIT INT TERM

docker compose -p "$project_name" -f docker-compose.test.yml up -d --wait test-db
docker compose -p "$project_name" -f docker-compose.test.yml run --rm --no-deps --build test-runner
docker compose -p "$project_name" -f docker-compose.test.yml run --rm --no-deps \
	--entrypoint php test-runner /var/www/tests/Integration/FileStorageTest.php