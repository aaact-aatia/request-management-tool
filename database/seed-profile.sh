#!/bin/bash
set -eu

seed_profile="${RMT_SEED_PROFILE:-example}"

case "$seed_profile" in
    clean)
        echo "RMT seed profile: clean (reference data only)"
        ;;
    example)
        echo "RMT seed profile: example"
        mysql --protocol=socket -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" \
            < /opt/rmt-seeds/sample-dev.sql
        ;;
    ssc)
        echo "RMT seed profile: ssc"
        mysql --protocol=socket -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" \
            < /opt/rmt-seeds/ssc-sample-dev.sql
        ;;
    *)
        echo "Unsupported RMT_SEED_PROFILE: $seed_profile" >&2
        exit 1
        ;;
esac