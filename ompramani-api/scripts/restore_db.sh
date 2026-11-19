#!/usr/bin/env bash
# ==============================================================================
# OMPRAMANI POSTGRESQL RESTORE PROCEDURE SCRIPT
# ==============================================================================
# CAUTION:
# This script will overwrite target database tables!
# Only run on an isolated test database or during an authorized disaster recovery.
# ==============================================================================
set -euo pipefail

if [ "$#" -lt 1 ]; then
    echo "Usage: $0 <backup_file.sql.gz> [target_database_name]"
    echo "Example: $0 /var/backups/ompramani/postgres/ompramani_production_20261118_120000.sql.gz ompramani_restore_test"
    exit 1
fi

BACKUP_FILE="$1"
TARGET_DB="${2:-ompramani_restore_test}"
DB_USER="${DB_USERNAME:-postgres}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"

if [ ! -f "${BACKUP_FILE}" ]; then
    echo "ERROR: Backup file '${BACKUP_FILE}' does not exist!" >&2
    exit 1
fi

echo "======================================================================"
echo "WARNING: You are about to restore into target database: '${TARGET_DB}'"
echo "Backup source: ${BACKUP_FILE}"
echo "Host: ${DB_HOST}:${DB_PORT} (User: ${DB_USER})"
echo "======================================================================"

read -p "Type 'RESTORE-CONFIRM' to proceed: " CONFIRMATION
if [ "${CONFIRMATION}" != "RESTORE-CONFIRM" ]; then
    echo "Restore aborted by user."
    exit 0
fi

echo "Verifying backup archive integrity..."
gzip -t "${BACKUP_FILE}"

echo "Creating target database '${TARGET_DB}' if it does not exist..."
createdb -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" "${TARGET_DB}" || true

echo "Restoring database schema and data..."
gunzip -c "${BACKUP_FILE}" | psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" -d "${TARGET_DB}" --single-transaction

echo "======================================================================"
echo "Restore successfully completed into database '${TARGET_DB}'."
echo "Recommended post-restore verification:"
echo "  php artisan test (pointed to target DB)"
echo "  php artisan migrate:status"
echo "======================================================================"
