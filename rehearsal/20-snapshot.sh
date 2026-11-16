#!/bin/sh
set -eu
for secret in "$SNAPSHOT_READER_PASSWORD" "$SNAPSHOT_LOADER_PASSWORD"; do
    case "$secret" in ''|*[!0-9a-f]*) echo 'Password snapshot wajib hex.' >&2; exit 1;; esac
    [ "${#secret}" -ge 32 ] || exit 1
done
MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot <<SQL
CREATE DATABASE labmania_legacy_snapshot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'snapshot_reader'@'%' IDENTIFIED BY '$SNAPSHOT_READER_PASSWORD';
GRANT SELECT, SHOW VIEW ON labmania_legacy_snapshot.* TO 'snapshot_reader'@'%';
CREATE USER 'snapshot_loader'@'%' IDENTIFIED BY '$SNAPSHOT_LOADER_PASSWORD';
GRANT SELECT, INSERT, CREATE, ALTER, INDEX, DROP, REFERENCES ON labmania_legacy_snapshot.* TO 'snapshot_loader'@'%';
SQL
