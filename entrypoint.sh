#!/bin/sh
set -eu

exec docker-php-entrypoint apache2-foreground
