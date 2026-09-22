#!/bin/sh
set -eu

project_name="rmt-integration-tests"

cleanup() {
	docker compose -p "$project_name" -f docker-compose.test.yml down --volumes --remove-orphans
}
trap cleanup EXIT INT TERM

compose_run() {
	docker compose -p "$project_name" -f docker-compose.test.yml run --rm --no-deps "$@"
}

docker compose -p "$project_name" -f docker-compose.test.yml up -d --wait test-db
docker compose -p "$project_name" -f docker-compose.test.yml run --rm --no-deps --build test-runner

# Standalone scripts that print their own PASS lines. These must be run with php.
for script in \
	/var/www/tests/Integration/FileStorageTest.php \
	/var/www/tests/Unit/PriorityCalculatorTest.php \
	/var/www/tests/Unit/DepartmentDirectoryTest.php \
	/var/www/tests/Unit/RequestCardTest.php \
	/var/www/tests/Integration/OrganizationDirectoryTest.php
do
	compose_run --entrypoint php test-runner "$script"
done

# PHPUnit cases. These need the autoloader and bootstrap, so php alone fails.
for suite in \
	/var/www/tests/Unit/HelpersTest.php \
	/var/www/tests/Unit/NotificationTemplateSyncTest.php
do
	compose_run --entrypoint php test-runner \
		/var/www/html/vendor/bin/phpunit -c /var/www/phpunit.xml "$suite"
done