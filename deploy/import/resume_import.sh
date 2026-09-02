#!/usr/bin/env bash
# Resumes the import after the data load: indexes (batched, resumable) + perf layer.
# Safe to re-run: index steps skip what already exists, the perf scripts are idempotent.
set -euo pipefail
cd "$(dirname "$0")/.."
set -a; . ./.env; set +a
log() { echo "[$(date '+%F %T')] $*"; }
MY="mysql -uroot -p${MYSQL_ROOT_PASSWORD} --max_allowed_packet=1G"

until docker compose exec -T db sh -c "$MY -e 'SELECT 1' >/dev/null 2>&1"; do log "waiting for db"; sleep 5; done
docker compose exec -T db sh -c "$MY -e 'SET GLOBAL event_scheduler=OFF'" 2>/dev/null

log "building indexes (batched)"
docker compose exec -T db sh -c "$MY almir_db_new < /import/build_indexes.sql" 2>&1 | grep -v "Using a password" || true
log "indexes done"

for f in sales_by_counterparty.sql drop_dead_indexes.applied.sql p2_rollup_migration.sql p3_analytics_cubes.sql dev_admin.sql; do
  log "running perf/$f"
  docker compose exec -T db sh -c "$MY almir_db_new < /import/perf/$f" 2>&1 | grep -v "Using a password" | tail -5 || true
  log "done perf/$f"
done

docker compose exec -T db sh -c "$MY -e 'SET GLOBAL event_scheduler=ON'" 2>/dev/null
log "verification"
docker compose exec -T db sh -c "$MY -N almir_db_new -e \"SELECT 'routines', COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA='almir_db_new' UNION ALL SELECT 'events', COUNT(*) FROM information_schema.EVENTS WHERE EVENT_SCHEMA='almir_db_new' UNION ALL SELECT 'drug_reports', COUNT(*) FROM drug_reports UNION ALL SELECT 'drug_reports_s', COUNT(*) FROM drug_reports_s UNION ALL SELECT 'drug_reports_p', COUNT(*) FROM drug_reports_p UNION ALL SELECT 'dr_rollup', COUNT(*) FROM dr_rollup UNION ALL SELECT 'dr_cube_l1', COUNT(*) FROM dr_cube_l1 UNION ALL SELECT 'dr_cube_l2', COUNT(*) FROM dr_cube_l2\"" 2>&1 | grep -v "Using a password"
log "ALL DONE"
