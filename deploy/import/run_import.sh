#!/usr/bin/env bash
# Reproduces the local import of almir_db_new_202602171457.zip:
#   1. stream the dump through filter.awk (drops the 33 secondary indexes / FKs of the three
#      fact tables so the load runs ~3x faster), 2. load, 3. rebuild the surviving indexes,
#   4. apply database/perf scripts in the order used locally, 5. add the dev admin account.
set -euo pipefail
cd "$(dirname "$0")/.."
set -a; . ./.env; set +a
log() { echo "[$(date '+%F %T')] $*"; }
MYSQL_IN_DB="mysql -uroot -p${MYSQL_ROOT_PASSWORD} --max_allowed_packet=1G"

log "waiting for db"
until docker compose exec -T db sh -c "$MYSQL_IN_DB -e 'SELECT 1' >/dev/null 2>&1"; do sleep 5; done

if [ ! -f dump/almir_filtered.sql ]; then
  log "filtering dump (unzip | awk)"
  ( cd dump && unzip -p almir_db_new_202602171457.zip | awk -f ../import/filter.awk > almir_filtered.sql.tmp && mv almir_filtered.sql.tmp almir_filtered.sql )
fi
log "filtered dump: $(du -h dump/almir_filtered.sql | cut -f1)"

log "event scheduler OFF for the load"
docker compose exec -T db sh -c "$MYSQL_IN_DB -e 'SET GLOBAL event_scheduler=OFF'"

# the dbForge dump starts with a UTF-8 BOM; it must not end up mid-stream after our SET prefix
SKIP=1; [ "$(head -c 3 dump/almir_filtered.sql | od -An -tx1 | tr -d ' 
')" = "efbbbf" ] && SKIP=4
log "import start (BOM skip offset: $SKIP)"
docker compose exec -T db sh -c "( echo 'SET SESSION foreign_key_checks=0; SET SESSION unique_checks=0;'; tail -c +$SKIP /dump/almir_filtered.sql ) | $MYSQL_IN_DB almir_db_new"
log "import done"

log "building indexes"
docker compose exec -T db sh -c "$MYSQL_IN_DB almir_db_new < /import/build_indexes.sql"
log "indexes done"

for f in sales_by_counterparty.sql drop_dead_indexes.applied.sql p2_rollup_migration.sql p3_analytics_cubes.sql dev_admin.sql; do
  log "running perf/$f"
  docker compose exec -T db sh -c "$MYSQL_IN_DB almir_db_new < /import/perf/$f"
  log "done perf/$f"
done

log "event scheduler ON"
docker compose exec -T db sh -c "$MYSQL_IN_DB -e 'SET GLOBAL event_scheduler=ON'"
docker compose exec -T db sh -c "$MYSQL_IN_DB -N -e \"SELECT 'routines', COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA='almir_db_new'; SELECT EVENT_NAME, STATUS FROM information_schema.EVENTS WHERE EVENT_SCHEMA='almir_db_new'; SELECT 'dr_rollup', COUNT(*) FROM almir_db_new.dr_rollup; SELECT 'dr_cube_l1', COUNT(*) FROM almir_db_new.dr_cube_l1; SELECT 'dr_cube_l2', COUNT(*) FROM almir_db_new.dr_cube_l2; SELECT 'drug_reports', COUNT(*) FROM almir_db_new.drug_reports;\""
rm -f dump/almir_filtered.sql
log "ALL DONE"
