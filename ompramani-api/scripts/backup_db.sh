#!/usr/bin/env bash
# ==============================================================================
# OMPRAMANI POSTGRESQL AUTOMATED BACKUP SCRIPT
# ==============================================================================
set -euo pipefail

# Configuration
BACKUP_DIR="${BACKUP_DIR:-/var/backups/ompramani/postgres}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DB_NAME="${DB_DATABASE:-ompramani_production}"
DB_USER="${DB_USERNAME:-ompramani_app}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"

BACKUP_FILE="${BACKUP_DIR}/${DB_NAME}_${TIMESTAMP}.sql.gz"

echo "=== [OmPraMani] Starting Database Backup: ${TIMESTAMP} ==="

mkdir -p "${BACKUP_DIR}"
chmod 700 "${BACKUP_DIR}"

# Perform backup using pg_dump with custom compressed archive format
echo "Dumping database '${DB_NAME}' from ${DB_HOST}:${DB_PORT}..."
pg_dump -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" -d "${DB_NAME}" --no-owner --no-privileges | gzip -9 > "${BACKUP_FILE}"

# Secure permissions of dump file (owner read/write only)
chmod 600 "${BACKUP_FILE}"

FILE_SIZE=$(du -h "${BACKUP_FILE}" | cut -f1)
echo "Backup successfully created: ${BACKUP_FILE} (Size: ${FILE_SIZE})"

# Verify archive integrity
if gzip -t "${BACKUP_FILE}"; then
    echo "Backup integrity test PASSED."
else
    echo "ERROR: Backup file is corrupted!" >&2
    exit 1
fi

# Clean up backups older than retention policy
echo "Cleaning up backups older than ${RETENTION_DAYS} days..."
find "${BACKUP_DIR}" -name "${DB_NAME}_*.sql.gz" -type f -mtime +"${RETENTION_DAYS}" -delete

echo "=== [OmPraMani] Backup Complete ==="
