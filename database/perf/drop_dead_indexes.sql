-- ============================================================================
--  ALMIR · Чистка «мёртвых» индексов на факт-таблицах отчётов
--  Таблицы: drug_reports (мастер), drug_reports_s (продажи), drug_reports_p (приход)
--  У всех трёх — ОДИНАКОВЫЕ 33 индекса, из них ~24 бесполезны для чтения
--  и только замедляют импорт (каждый INSERT обновляет 33 индексных дерева)
--  и раздувают буфер/диск (~2.3 ГБ индексов на drug_reports_s).
--
--  ⚠️  ПОРЯДОК ДЕЙСТВИЙ НА ПРОДЕ:
--   1. Сделать бэкап (или снять на реплике/копии).
--   2. Выполнить БЛОК 0 (диагностика) и убедиться, что индексы из Tier B
--      действительно не используются (по данным prod-нагрузки).
--   3. Прогнать Tier A (безопасно), замерить импорт/отчёты.
--   4. Только потом — Tier B (после проверки).
--   DROP INDEX в InnoDB — операция уровня метаданных (быстрая), но ALTER ниже
--   идёт с ALGORITHM=INPLACE, LOCK=NONE, чтобы не блокировать запись.
--   Если сервер MySQL < 5.6 — убрать хвост ", ALGORITHM=INPLACE, LOCK=NONE".
--   DROP INDEX не поддерживает IF EXISTS: если индекс уже удалён — строка выдаст
--   ошибку, просто пропустите её.
-- ============================================================================


-- ────────────────────────────────────────────────────────────────────────────
--  БЛОК 0. ДИАГНОСТИКА (только чтение) — выполнить и сохранить вывод ДО правок
-- ────────────────────────────────────────────────────────────────────────────

-- 0.1 Текущий размер данных/индексов
SELECT table_name,
       table_rows,
       ROUND(data_length /1024/1024) AS data_mb,
       ROUND(index_length/1024/1024) AS index_mb
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN ('drug_reports','drug_reports_s','drug_reports_p');

-- 0.2 Индексы, которые НИ РАЗУ не использовались с момента старта сервера.
--     Требует включённого performance_schema и достаточного аптайма под нагрузкой.
--     ВАЖНО: если сервер недавно перезапущен — список будет неверным, подождите
--     сутки-двое типичной нагрузки (включая отчёты и импорт) прежде чем доверять.
SELECT object_schema, object_name, index_name
FROM sys.schema_unused_indexes
WHERE object_schema = DATABASE()
  AND object_name IN ('drug_reports','drug_reports_s','drug_reports_p')
ORDER BY object_name, index_name;

-- 0.3 Статистика использования индексов (сколько раз читались)
SELECT object_name, index_name,
       count_star, count_read, count_fetch
FROM performance_schema.table_io_waits_summary_by_index_usage
WHERE object_schema = DATABASE()
  AND object_name IN ('drug_reports','drug_reports_s','drug_reports_p')
ORDER BY object_name, count_star DESC;


-- ────────────────────────────────────────────────────────────────────────────
--  СПРАВКА. Что оставляем (НЕ трогать):
--    PRIMARY(id)                                    — кластерный ключ
--    idx_main_period(mode_40_date,data_type,is_active,is_deleted) — фильтр отчётов
--    idx_drug_id(drug_id)                           — JOIN к drugs
--    idx_mf_id(mf_id), idx_sc_id(sc_id), idx_m40d_id(m40d_id) — GROUP BY измерений
--    idx_counterparty_id(counterparty_id)           — контрагенты/JOIN
--    idx_created_at(created_at)                      — вероятно нужен импорту (проверить в 0.2/0.3)
--    period_code(period_code)                        — вероятно нужен sync/импорту (проверить)
-- ────────────────────────────────────────────────────────────────────────────


-- ────────────────────────────────────────────────────────────────────────────
--  Tier A — ГАРАНТИРОВАННО МЁРТВЫЕ (безопасно удалять)
--  Одиночные индексы на ЗНАЧЕНИЯХ цен/сумм/валют (по ним никто не фильтрует —
--  в запросах только SUM(price), а не WHERE price = ...) + точные дубликаты.
-- ────────────────────────────────────────────────────────────────────────────

-- drug_reports_s (продажи — самая тяжёлая, ~2.3 ГБ индексов)
ALTER TABLE drug_reports_s
  DROP INDEX c_price_ccy,
  DROP INDEX c_price_ccy_rate,
  DROP INDEX c_price_eur,
  DROP INDEX c_price_rub,
  DROP INDEX c_price_usd,
  DROP INDEX c_price_uzs,
  DROP INDEX price_ccy,
  DROP INDEX price_ccy_rate,
  DROP INDEX price_eur,
  DROP INDEX price_rub,
  DROP INDEX price_usd,
  DROP INDEX price_uzs,
  DROP INDEX sum_price_eur,
  DROP INDEX sum_price_rub,
  DROP INDEX sum_price_usd,
  DROP INDEX sum_price_uzs,
  DROP INDEX mode_40_date,   -- дубль ведущей колонки idx_main_period
  DROP INDEX uk_id,          -- точный дубль PRIMARY(id)
  ALGORITHM=INPLACE, LOCK=NONE;

-- drug_reports_p (приход)
ALTER TABLE drug_reports_p
  DROP INDEX c_price_ccy,
  DROP INDEX c_price_ccy_rate,
  DROP INDEX c_price_eur,
  DROP INDEX c_price_rub,
  DROP INDEX c_price_usd,
  DROP INDEX c_price_uzs,
  DROP INDEX price_ccy,
  DROP INDEX price_ccy_rate,
  DROP INDEX price_eur,
  DROP INDEX price_rub,
  DROP INDEX price_usd,
  DROP INDEX price_uzs,
  DROP INDEX sum_price_eur,
  DROP INDEX sum_price_rub,
  DROP INDEX sum_price_usd,
  DROP INDEX sum_price_uzs,
  DROP INDEX mode_40_date,
  DROP INDEX uk_id,
  ALGORITHM=INPLACE, LOCK=NONE;

-- drug_reports (мастер)
ALTER TABLE drug_reports
  DROP INDEX c_price_ccy,
  DROP INDEX c_price_ccy_rate,
  DROP INDEX c_price_eur,
  DROP INDEX c_price_rub,
  DROP INDEX c_price_usd,
  DROP INDEX c_price_uzs,
  DROP INDEX price_ccy,
  DROP INDEX price_ccy_rate,
  DROP INDEX price_eur,
  DROP INDEX price_rub,
  DROP INDEX price_usd,
  DROP INDEX price_uzs,
  DROP INDEX sum_price_eur,
  DROP INDEX sum_price_rub,
  DROP INDEX sum_price_usd,
  DROP INDEX sum_price_uzs,
  DROP INDEX mode_40_date,
  DROP INDEX uk_id,
  ALGORITHM=INPLACE, LOCK=NONE;


-- ────────────────────────────────────────────────────────────────────────────
--  Tier B — ОЧЕНЬ ВЕРОЯТНО МЁРТВЫЕ, но СНАЧАЛА проверить по БЛОКУ 0
--  (низкая кардинальность / служебные колонки, не участвуют в фильтрах отчётов).
--  Раскомментируйте после подтверждения, что count_read = 0 в 0.3.
-- ────────────────────────────────────────────────────────────────────────────

-- ALTER TABLE drug_reports_s
--   DROP INDEX quantity,
--   DROP INDEX serial_number,
--   DROP INDEX shelf_life,
--   DROP INDEX idx_user_id,
--   DROP INDEX idx_m70d_id,
--   DROP INDEX mode_70_date,
--   ALGORITHM=INPLACE, LOCK=NONE;
--
-- ALTER TABLE drug_reports_p
--   DROP INDEX quantity, DROP INDEX serial_number, DROP INDEX shelf_life,
--   DROP INDEX idx_user_id, DROP INDEX idx_m70d_id, DROP INDEX mode_70_date,
--   ALGORITHM=INPLACE, LOCK=NONE;
--
-- ALTER TABLE drug_reports
--   DROP INDEX quantity, DROP INDEX serial_number, DROP INDEX shelf_life,
--   DROP INDEX idx_user_id, DROP INDEX idx_m70d_id, DROP INDEX mode_70_date,
--   ALGORITHM=INPLACE, LOCK=NONE;


-- ────────────────────────────────────────────────────────────────────────────
--  ПОСЛЕ УДАЛЕНИЯ — обновить статистику оптимизатора
-- ────────────────────────────────────────────────────────────────────────────
ANALYZE TABLE drug_reports_s;
ANALYZE TABLE drug_reports_p;
ANALYZE TABLE drug_reports;

-- Проверить новый размер индексов (сравнить с 0.1)
SELECT table_name, ROUND(index_length/1024/1024) AS index_mb
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN ('drug_reports','drug_reports_s','drug_reports_p');


-- ============================================================================
--  (ОПЦИОНАЛЬНО, отдельная задача — НЕ часть чистки)
--  Покрывающий индекс под горячий запрос отчёта ускоряет ХОЛОДНЫЙ путь:
--  сканирование идёт по индексу (узкие строки), без обращения к 612 МБ кучи.
--  ORDER BY SUM(...) он не убирает (для этого нужна предагрегация), но I/O режет.
--  Пример под «продажи, сортировка по USD», измерение mf/ sc/ m40d/ drug:
--
--  ALTER TABLE drug_reports_s
--    ADD INDEX idx_cov_active_period (is_active, is_deleted, mode_40_date,
--                                     mf_id, sc_id, m40d_id, drug_id,
--                                     sum_price_usd, quantity),
--    ALGORITHM=INPLACE, LOCK=NONE;
--
--  ⚠️ Проверить EXPLAIN до/после — широкий индекс тоже требует места и
--     обслуживания при импорте; добавлять только если EXPLAIN реально его берёт.
-- ============================================================================
