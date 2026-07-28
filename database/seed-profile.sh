#!/bin/bash
set -eu

seed_profile="${RMT_SEED_PROFILE:-example}"
local_seed_file="${RMT_LOCAL_SEED_FILE:-/opt/rmt-seeds/local-seed.sql}"

case "$seed_profile" in
    clean)
        echo "RMT seed profile: clean (reference data only)"
        ;;
    example)
        echo "RMT seed profile: example"
        mysql --protocol=socket -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" \
            < /opt/rmt-seeds/sample-dev.sql
        ;;
    local)
        if [ ! -f "$local_seed_file" ]; then
            echo "RMT local seed not found. Run ./database/export-local-seed.sh first." >&2
            exit 1
        fi
        echo "RMT seed profile: local"
        mysql --protocol=socket -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" \
            < "$local_seed_file"
        ;;
    *)
        echo "Unsupported RMT_SEED_PROFILE: $seed_profile" >&2
        exit 1
        ;;
esac