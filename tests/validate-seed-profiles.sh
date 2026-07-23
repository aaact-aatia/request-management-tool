#!/bin/sh
set -eu

current_project=""

cleanup() {
    if [ -n "$current_project" ]; then
        docker compose -p "$current_project" -f docker-compose.test.yml down --volumes --remove-orphans
    fi
}
trap cleanup EXIT INT TERM

for profile in clean example ssc; do
    current_project="rmt-seed-${profile}-test"
    cleanup

    RMT_SEED_PROFILE="$profile" docker compose \
        -p "$current_project" \
        -f docker-compose.test.yml \
        up -d --wait seed-db

    counts=$(docker compose \
        -p "$current_project" \
        -f docker-compose.test.yml \
        exec -T seed-db \
        mysql -uroot -prmt_test_root rmt_seed_test -N -B -e '
            SELECT
                (SELECT COUNT(*) FROM tblaccounttype),
                (SELECT COUNT(*) FROM tblcatalogue),
                (SELECT COUNT(*) FROM tblservices),
                (SELECT COUNT(*) FROM tblteams),
                (SELECT COUNT(*) FROM tblusers),
                (SELECT COUNT(*) FROM tbltriage)
        ' 2>/dev/null)

    case "$profile" in
        clean) expected=$(printf '6\t0\t0\t0\t0\t0') ;;
        example) expected=$(printf '6\t3\t10\t2\t12\t12') ;;
        ssc) expected=$(printf '6\t4\t13\t3\t2\t0') ;;
    esac

    if [ "$counts" != "$expected" ]; then
        printf 'FAIL: %s profile returned %s; expected %s\n' "$profile" "$counts" "$expected" >&2
        exit 1
    fi

    printf 'PASS: %s profile (%s)\n' "$profile" "$counts"
    cleanup
    current_project=""
done

current_project="rmt-seed-invalid-test"
cleanup

if RMT_SEED_PROFILE=invalid docker compose \
    -p "$current_project" \
    -f docker-compose.test.yml \
    up -d --wait seed-db; then
    echo "FAIL: invalid profile initialized successfully" >&2
    exit 1
fi

if ! docker compose \
    -p "$current_project" \
    -f docker-compose.test.yml \
    logs seed-db 2>&1 | grep -q "Unsupported RMT_SEED_PROFILE: invalid"; then
    echo "FAIL: invalid profile did not report the expected error" >&2
    exit 1
fi

echo "PASS: invalid profile rejected"
cleanup
current_project=""