# Deployment (Docker Compose)

Everything needed to run ALMIR STATISTICS on a Linux host with Docker: MySQL 8, Redis 7,
PHP-FPM 8.1 (Laravel API) and nginx (React SPA + reverse proxy on one port).

```
deploy/
  docker-compose.yml     db / redis / app / web
  .env.example           APP_PORT + MySQL passwords  -> copy to .env
  mysql/almir.cnf        MySQL tuning (same as the local dev stack)
  php/                   PHP-FPM image (extensions, php.ini, pool)
  nginx/default.conf     / -> app/frontend (SPA), /api /sanctum /public -> Laravel
  import/                one-shot DB import: dump -> filter.awk -> load -> indexes -> perf scripts
```

## Layout on the server

```
~/almir/
  docker-compose.yml .env mysql/ php/ nginx/ import/
  app/        the repository (with vendor/) + app/.env for Laravel (DB_HOST=db, REDIS_HOST=redis)
  app/frontend/  React build made with REACT_APP_API_URL=origin (same host/port as the API)
  dump/       almir_db_new_*.zip
```

## First run

```bash
cp .env.example .env            # set real passwords, choose APP_PORT
docker compose up -d db redis
docker compose build app
# put the dump into ./dump and the perf scripts into ./import/perf
#   (database/perf/sales_by_counterparty.sql, p2_rollup_migration.sql, p3_analytics_cubes.sql,
#    drop_dead_indexes.applied.sql, dev_admin.sql)
nohup bash import/run_import.sh > import.log 2>&1 &
docker compose up -d app web
docker compose exec -T app php artisan config:clear
```

`run_import.sh` reproduces the local import: fact tables are loaded without their 33 secondary
indexes (filter.awk), indexes are rebuilt afterwards (build_indexes.sql), then the perf layer
(rollup + analytics cubes) is created and the event scheduler is switched on.

`import/resume_import.sh` runs everything *after* the data load (indexes + perf layer). Use it if
the import was interrupted — `build_indexes.sql` adds a few indexes per `ALTER` and skips those
that already exist, so both scripts are safe to re-run.

## Memory

`mysql/almir.cnf` is sized for a **shared** host (~3.5 GB peak for mysqld: 2 GB buffer pool,
2 DDL threads, 256 MB DDL buffer). Building the fact-table indexes is the memory peak — with a
6 GB pool plus 8 parallel DDL threads mysqld was OOM-killed here mid-`ALTER`. Raise the pool only
if the machine really has the RAM free (`free -h`, and check swap is not already exhausted).
