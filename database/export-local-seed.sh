#!/bin/sh
set -eu

output_file="database/local-seed.sql"
temporary_file="${output_file}.tmp"

cleanup() {
    rm -f "$temporary_file"
}
trap cleanup EXIT INT TERM

echo "Exporting local users, catalogue hierarchy, and teams to $output_file"
docker compose exec -T db sh -c '
    export_database="rmt_local_seed_export"

    cleanup_export_database() {
        mysql -S /var/run/mysqld/mysqld.sock -uroot -p"$MYSQL_ROOT_PASSWORD" \
            -e "DROP DATABASE IF EXISTS \`$export_database\`" >/dev/null 2>&1
    }
    trap cleanup_export_database EXIT INT TERM

    cleanup_export_database
    mysql -S /var/run/mysqld/mysqld.sock -uroot -p"$MYSQL_ROOT_PASSWORD" \
        -e "CREATE DATABASE \`$export_database\` CHARACTER SET utf8mb4"
    mysql -S /var/run/mysqld/mysqld.sock -uroot -p"$MYSQL_ROOT_PASSWORD" \
        "$export_database" < /opt/rmt-seeds/schema.sql
    mysql -S /var/run/mysqld/mysqld.sock -uroot -p"$MYSQL_ROOT_PASSWORD" <<SQL
INSERT INTO \`$export_database\`.tblusers
    (id, firstname, lastname, email, password, atype, is_superuser, is_admin, manager_id, team, status)
SELECT id, firstname, lastname, email, password, atype, is_superuser, is_admin, manager_id, team, status
FROM \`$MYSQL_DATABASE\`.tblusers;

INSERT INTO \`$export_database\`.tblteams
    (id, nameen, namefr, email, team_lead_user_id, dateadded, dateupdated, updatedby, status)
SELECT id, nameen, namefr, email, team_lead_user_id, dateadded, dateupdated, updatedby, status
FROM \`$MYSQL_DATABASE\`.tblteams;

INSERT INTO \`$export_database\`.tblcatalogue
    (id, nameen, namefr, contactid, survey, status)
SELECT id, nameen, namefr, contactid, survey, status
FROM \`$MYSQL_DATABASE\`.tblcatalogue;

INSERT INTO \`$export_database\`.tblservices
    (id, catalogueid, nameen, namefr, sds, contactid, status)
SELECT id, catalogueid, nameen, namefr, sds, contactid, status
FROM \`$MYSQL_DATABASE\`.tblservices;

INSERT INTO \`$export_database\`.tblsubservices
    (id, serviceid, nameen, namefr, sds, contactid, status)
SELECT id, serviceid, nameen, namefr, sds, contactid, status
FROM \`$MYSQL_DATABASE\`.tblsubservices;
SQL

    mysqldump \
        --protocol=socket \
        -uroot \
        -p"$MYSQL_ROOT_PASSWORD" \
        --no-create-info \
        --complete-insert \
        --skip-triggers \
        --set-gtid-purged=OFF \
        "$export_database" \
        tblusers tblteams tblcatalogue tblservices tblsubservices
' > "$temporary_file"

mv "$temporary_file" "$output_file"
echo "Local seed saved. This file is ignored by Git and may contain personal information."