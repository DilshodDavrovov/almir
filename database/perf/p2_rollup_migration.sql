-- ############################################################################
-- #  P2: ПРЕД-АГРЕГАЦИЯ ОТЧЁТА «Сравнительный анализ продаж» (dr_rollup)
-- #  Аддитивная, НЕ ломающая миграция. Импорт/синк не трогает.
-- #  Ускоряет: getCalcCounts, getPeriodDataList, getCommonPerPrice, getCommPrice.
-- #  *Fact-версии = оригиналы (fallback для region/district/type/вне-роллап dim).
-- #  Откат — в хвосте файла.
-- ############################################################################

-- ===== [1] ТАБЛИЦЫ + ИНДЕКС ================================================
-- ============================================================================
--  P2 / ЧАСТЬ 1: индекс-ускоритель + таблицы роллапа  (идемпотентно)
-- ============================================================================

/* Индекс на updated_at.
   Зачем: и НАШ refresh_dr_rollup, и УЖЕ СУЩЕСТВУЮЩИЙ sync_drug_reports_clones
   ищут изменённые строки через `WHERE updated_at > ...`. Сейчас индекса нет —
   каждый прогон = полный скан 21М строк. Индекс превращает это в мгновенный range-scan.
   Стоимость: ~один индекс (небольшой рост при импорте) — с лихвой окупается тем,
   что параллельно удаляем 18 «мёртвых» индексов (см. drop_dead_indexes.sql). */
SET @idx := (SELECT COUNT(*) FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = 'drug_reports'
               AND index_name = 'idx_dr_updated');
SET @sql := IF(@idx = 0,
  'ALTER TABLE drug_reports ADD INDEX idx_dr_updated (updated_at)',
  'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

/* Дневной роллап. Грануляция: (тип, измерение, id, день, активность, удалён).
   Регион/район В ГРАНУЛЯЦИЮ НЕ ВХОДЯТ (это раздувало бы таблицу ~в 30 раз);
   отчёты с фильтром по региону/району откатываются на точные *Fact-процедуры. */
CREATE TABLE IF NOT EXISTS dr_rollup (
  data_type tinyint NOT NULL,
  dim_type  varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  dim_id    int NOT NULL,
  day       date NOT NULL,
  is_active tinyint NOT NULL,
  is_deleted tinyint NOT NULL,
  qty      decimal(24,4) NOT NULL DEFAULT 0,
  sum_usd  decimal(24,4) NOT NULL DEFAULT 0,
  sum_uzs  decimal(24,4) NOT NULL DEFAULT 0,
  sum_eur  decimal(24,4) NOT NULL DEFAULT 0,
  sum_rub  decimal(24,4) NOT NULL DEFAULT 0,
  PRIMARY KEY (data_type, dim_type, dim_id, day, is_active, is_deleted),
  KEY idx_report (data_type, dim_type, day, is_active, is_deleted, dim_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* Состояние инкрементального refresh (водяной знак по updated_at мастера). */
CREATE TABLE IF NOT EXISTS dr_rollup_state (
  id tinyint NOT NULL PRIMARY KEY,
  last_at datetime NOT NULL
) ENGINE=InnoDB;

-- ===== [2] НАПОЛНЕНИЕ ИЗ КЛОНОВ ===========================================
-- ============================================================================
--  P2 / ЧАСТЬ 2: наполнение роллапа из клонов (_p = приход, _s = продажа)
--  Водяной знак берём ДО наполнения = last_sync_at клонов. Клоны отражают
--  мастер по updated_at<=last_sync_at, значит и роллап отражает ту же точку.
--  Первый refresh до-агрегирует всё, что импортировано в мастер после неё
--  (идемпотентно: он удаляет и пересобирает затронутые дни целиком).
--  ВНИМАНИЕ: тяжёлая одноразовая операция — запускать в окно обслуживания.
-- ============================================================================
SET @wm := (SELECT last_sync_at FROM dr_sync_state WHERE id = 1);
TRUNCATE TABLE dr_rollup;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 1,'counterparty',counterparty_id,mode_40_date,is_active,is_deleted,
 SUM(quantity),SUM(sum_price_usd),SUM(sum_price_uzs),SUM(sum_price_eur),SUM(sum_price_rub)
FROM drug_reports_p WHERE counterparty_id IS NOT NULL AND counterparty_id<>0
GROUP BY counterparty_id,mode_40_date,is_active,is_deleted;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 1,'sc',sc_id,mode_40_date,is_active,is_deleted,
 SUM(quantity),SUM(sum_price_usd),SUM(sum_price_uzs),SUM(sum_price_eur),SUM(sum_price_rub)
FROM drug_reports_p WHERE sc_id IS NOT NULL AND sc_id<>0
GROUP BY sc_id,mode_40_date,is_active,is_deleted;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 1,'mf',mf_id,mode_40_date,is_active,is_deleted,
 SUM(quantity),SUM(sum_price_usd),SUM(sum_price_uzs),SUM(sum_price_eur),SUM(sum_price_rub)
FROM drug_reports_p WHERE mf_id IS NOT NULL AND mf_id<>0
GROUP BY mf_id,mode_40_date,is_active,is_deleted;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 1,'m40d',m40d_id,mode_40_date,is_active,is_deleted,
 SUM(quantity),SUM(sum_price_usd),SUM(sum_price_uzs),SUM(sum_price_eur),SUM(sum_price_rub)
FROM drug_reports_p WHERE m40d_id IS NOT NULL AND m40d_id<>0
GROUP BY m40d_id,mode_40_date,is_active,is_deleted;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 1,'drug',drug_id,mode_40_date,is_active,is_deleted,
 SUM(quantity),SUM(sum_price_usd),SUM(sum_price_uzs),SUM(sum_price_eur),SUM(sum_price_rub)
FROM drug_reports_p WHERE drug_id IS NOT NULL AND drug_id<>0
GROUP BY drug_id,mode_40_date,is_active,is_deleted;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 1,'inn',d.di_id,dr.mode_40_date,dr.is_active,dr.is_deleted,
 SUM(dr.quantity),SUM(dr.sum_price_usd),SUM(dr.sum_price_uzs),SUM(dr.sum_price_eur),SUM(dr.sum_price_rub)
FROM drug_reports_p dr LEFT JOIN drugs d ON dr.drug_id=d.id WHERE d.di_id IS NOT NULL AND d.di_id<>0
GROUP BY d.di_id,dr.mode_40_date,dr.is_active,dr.is_deleted;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 1,'df',d.df_id,dr.mode_40_date,dr.is_active,dr.is_deleted,
 SUM(dr.quantity),SUM(dr.sum_price_usd),SUM(dr.sum_price_uzs),SUM(dr.sum_price_eur),SUM(dr.sum_price_rub)
FROM drug_reports_p dr LEFT JOIN drugs d ON dr.drug_id=d.id WHERE d.df_id IS NOT NULL AND d.df_id<>0
GROUP BY d.df_id,dr.mode_40_date,dr.is_active,dr.is_deleted;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 1,'dfg',d.dfg_id,dr.mode_40_date,dr.is_active,dr.is_deleted,
 SUM(dr.quantity),SUM(dr.sum_price_usd),SUM(dr.sum_price_uzs),SUM(dr.sum_price_eur),SUM(dr.sum_price_rub)
FROM drug_reports_p dr LEFT JOIN drugs d ON dr.drug_id=d.id WHERE d.dfg_id IS NOT NULL AND d.dfg_id<>0
GROUP BY d.dfg_id,dr.mode_40_date,dr.is_active,dr.is_deleted;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 1,'dtg',d.dtg_id,dr.mode_40_date,dr.is_active,dr.is_deleted,
 SUM(dr.quantity),SUM(dr.sum_price_usd),SUM(dr.sum_price_uzs),SUM(dr.sum_price_eur),SUM(dr.sum_price_rub)
FROM drug_reports_p dr LEFT JOIN drugs d ON dr.drug_id=d.id WHERE d.dtg_id IS NOT NULL AND d.dtg_id<>0
GROUP BY d.dtg_id,dr.mode_40_date,dr.is_active,dr.is_deleted;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 1,'trademark',d.trademark_id,dr.mode_40_date,dr.is_active,dr.is_deleted,
 SUM(dr.quantity),SUM(dr.sum_price_usd),SUM(dr.sum_price_uzs),SUM(dr.sum_price_eur),SUM(dr.sum_price_rub)
FROM drug_reports_p dr LEFT JOIN drugs d ON dr.drug_id=d.id WHERE d.trademark_id IS NOT NULL AND d.trademark_id<>0
GROUP BY d.trademark_id,dr.mode_40_date,dr.is_active,dr.is_deleted;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 2,'counterparty',counterparty_id,mode_40_date,is_active,is_deleted,
 SUM(quantity),SUM(sum_price_usd),SUM(sum_price_uzs),SUM(sum_price_eur),SUM(sum_price_rub)
FROM drug_reports_s WHERE counterparty_id IS NOT NULL AND counterparty_id<>0
GROUP BY counterparty_id,mode_40_date,is_active,is_deleted;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 2,'sc',sc_id,mode_40_date,is_active,is_deleted,
 SUM(quantity),SUM(sum_price_usd),SUM(sum_price_uzs),SUM(sum_price_eur),SUM(sum_price_rub)
FROM drug_reports_s WHERE sc_id IS NOT NULL AND sc_id<>0
GROUP BY sc_id,mode_40_date,is_active,is_deleted;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 2,'mf',mf_id,mode_40_date,is_active,is_deleted,
 SUM(quantity),SUM(sum_price_usd),SUM(sum_price_uzs),SUM(sum_price_eur),SUM(sum_price_rub)
FROM drug_reports_s WHERE mf_id IS NOT NULL AND mf_id<>0
GROUP BY mf_id,mode_40_date,is_active,is_deleted;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 2,'m40d',m40d_id,mode_40_date,is_active,is_deleted,
 SUM(quantity),SUM(sum_price_usd),SUM(sum_price_uzs),SUM(sum_price_eur),SUM(sum_price_rub)
FROM drug_reports_s WHERE m40d_id IS NOT NULL AND m40d_id<>0
GROUP BY m40d_id,mode_40_date,is_active,is_deleted;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 2,'drug',drug_id,mode_40_date,is_active,is_deleted,
 SUM(quantity),SUM(sum_price_usd),SUM(sum_price_uzs),SUM(sum_price_eur),SUM(sum_price_rub)
FROM drug_reports_s WHERE drug_id IS NOT NULL AND drug_id<>0
GROUP BY drug_id,mode_40_date,is_active,is_deleted;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 2,'inn',d.di_id,dr.mode_40_date,dr.is_active,dr.is_deleted,
 SUM(dr.quantity),SUM(dr.sum_price_usd),SUM(dr.sum_price_uzs),SUM(dr.sum_price_eur),SUM(dr.sum_price_rub)
FROM drug_reports_s dr LEFT JOIN drugs d ON dr.drug_id=d.id WHERE d.di_id IS NOT NULL AND d.di_id<>0
GROUP BY d.di_id,dr.mode_40_date,dr.is_active,dr.is_deleted;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 2,'df',d.df_id,dr.mode_40_date,dr.is_active,dr.is_deleted,
 SUM(dr.quantity),SUM(dr.sum_price_usd),SUM(dr.sum_price_uzs),SUM(dr.sum_price_eur),SUM(dr.sum_price_rub)
FROM drug_reports_s dr LEFT JOIN drugs d ON dr.drug_id=d.id WHERE d.df_id IS NOT NULL AND d.df_id<>0
GROUP BY d.df_id,dr.mode_40_date,dr.is_active,dr.is_deleted;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 2,'dfg',d.dfg_id,dr.mode_40_date,dr.is_active,dr.is_deleted,
 SUM(dr.quantity),SUM(dr.sum_price_usd),SUM(dr.sum_price_uzs),SUM(dr.sum_price_eur),SUM(dr.sum_price_rub)
FROM drug_reports_s dr LEFT JOIN drugs d ON dr.drug_id=d.id WHERE d.dfg_id IS NOT NULL AND d.dfg_id<>0
GROUP BY d.dfg_id,dr.mode_40_date,dr.is_active,dr.is_deleted;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 2,'dtg',d.dtg_id,dr.mode_40_date,dr.is_active,dr.is_deleted,
 SUM(dr.quantity),SUM(dr.sum_price_usd),SUM(dr.sum_price_uzs),SUM(dr.sum_price_eur),SUM(dr.sum_price_rub)
FROM drug_reports_s dr LEFT JOIN drugs d ON dr.drug_id=d.id WHERE d.dtg_id IS NOT NULL AND d.dtg_id<>0
GROUP BY d.dtg_id,dr.mode_40_date,dr.is_active,dr.is_deleted;
INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
SELECT 2,'trademark',d.trademark_id,dr.mode_40_date,dr.is_active,dr.is_deleted,
 SUM(dr.quantity),SUM(dr.sum_price_usd),SUM(dr.sum_price_uzs),SUM(dr.sum_price_eur),SUM(dr.sum_price_rub)
FROM drug_reports_s dr LEFT JOIN drugs d ON dr.drug_id=d.id WHERE d.trademark_id IS NOT NULL AND d.trademark_id<>0
GROUP BY d.trademark_id,dr.mode_40_date,dr.is_active,dr.is_deleted;

-- зафиксировать водяной знак роллапа = точке синка клонов (снятой ДО наполнения)
INSERT INTO dr_rollup_state (id, last_at) VALUES (1, @wm)
  ON DUPLICATE KEY UPDATE last_at = VALUES(last_at);

-- ===== [3] ИНКРЕМЕНТАЛЬНОЕ ОБНОВЛЕНИЕ ======================================
CREATE TABLE IF NOT EXISTS dr_rollup_state (id tinyint NOT NULL PRIMARY KEY, last_at datetime NOT NULL) ENGINE=InnoDB;
INSERT IGNORE INTO dr_rollup_state (id,last_at) VALUES (1,'2000-01-01 00:00:00');

DROP PROCEDURE IF EXISTS refresh_dr_rollup;
DELIMITER ;;
CREATE PROCEDURE refresh_dr_rollup()
BEGIN
  DECLARE v_last datetime; DECLARE v_now datetime;
  SET v_now = NOW();
  SELECT last_at INTO v_last FROM dr_rollup_state WHERE id=1 FOR UPDATE;

  DROP TEMPORARY TABLE IF EXISTS _chg;
  CREATE TEMPORARY TABLE _chg (day date NOT NULL PRIMARY KEY);
  INSERT IGNORE INTO _chg (day)
    SELECT DISTINCT mode_40_date FROM drug_reports
    WHERE updated_at > v_last AND updated_at <= v_now AND mode_40_date IS NOT NULL;

  DELETE r FROM dr_rollup r JOIN _chg c ON r.day = c.day;

  INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
  SELECT data_type,'counterparty',counterparty_id,mode_40_date,is_active,is_deleted,
   SUM(quantity),SUM(sum_price_usd),SUM(sum_price_uzs),SUM(sum_price_eur),SUM(sum_price_rub)
  FROM drug_reports WHERE counterparty_id IS NOT NULL AND counterparty_id<>0 AND mode_40_date IN (SELECT day FROM _chg)
  GROUP BY data_type,counterparty_id,mode_40_date,is_active,is_deleted;

  INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
  SELECT data_type,'sc',sc_id,mode_40_date,is_active,is_deleted,
   SUM(quantity),SUM(sum_price_usd),SUM(sum_price_uzs),SUM(sum_price_eur),SUM(sum_price_rub)
  FROM drug_reports WHERE sc_id IS NOT NULL AND sc_id<>0 AND mode_40_date IN (SELECT day FROM _chg)
  GROUP BY data_type,sc_id,mode_40_date,is_active,is_deleted;

  INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
  SELECT data_type,'mf',mf_id,mode_40_date,is_active,is_deleted,
   SUM(quantity),SUM(sum_price_usd),SUM(sum_price_uzs),SUM(sum_price_eur),SUM(sum_price_rub)
  FROM drug_reports WHERE mf_id IS NOT NULL AND mf_id<>0 AND mode_40_date IN (SELECT day FROM _chg)
  GROUP BY data_type,mf_id,mode_40_date,is_active,is_deleted;

  INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
  SELECT data_type,'m40d',m40d_id,mode_40_date,is_active,is_deleted,
   SUM(quantity),SUM(sum_price_usd),SUM(sum_price_uzs),SUM(sum_price_eur),SUM(sum_price_rub)
  FROM drug_reports WHERE m40d_id IS NOT NULL AND m40d_id<>0 AND mode_40_date IN (SELECT day FROM _chg)
  GROUP BY data_type,m40d_id,mode_40_date,is_active,is_deleted;

  INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
  SELECT data_type,'drug',drug_id,mode_40_date,is_active,is_deleted,
   SUM(quantity),SUM(sum_price_usd),SUM(sum_price_uzs),SUM(sum_price_eur),SUM(sum_price_rub)
  FROM drug_reports WHERE drug_id IS NOT NULL AND drug_id<>0 AND mode_40_date IN (SELECT day FROM _chg)
  GROUP BY data_type,drug_id,mode_40_date,is_active,is_deleted;

  INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
  SELECT dr.data_type,'inn',d.di_id,dr.mode_40_date,dr.is_active,dr.is_deleted,
   SUM(dr.quantity),SUM(dr.sum_price_usd),SUM(dr.sum_price_uzs),SUM(dr.sum_price_eur),SUM(dr.sum_price_rub)
  FROM drug_reports dr LEFT JOIN drugs d ON dr.drug_id=d.id
  WHERE d.di_id IS NOT NULL AND d.di_id<>0 AND dr.mode_40_date IN (SELECT day FROM _chg)
  GROUP BY dr.data_type,d.di_id,dr.mode_40_date,dr.is_active,dr.is_deleted;

  INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
  SELECT dr.data_type,'df',d.df_id,dr.mode_40_date,dr.is_active,dr.is_deleted,
   SUM(dr.quantity),SUM(dr.sum_price_usd),SUM(dr.sum_price_uzs),SUM(dr.sum_price_eur),SUM(dr.sum_price_rub)
  FROM drug_reports dr LEFT JOIN drugs d ON dr.drug_id=d.id
  WHERE d.df_id IS NOT NULL AND d.df_id<>0 AND dr.mode_40_date IN (SELECT day FROM _chg)
  GROUP BY dr.data_type,d.df_id,dr.mode_40_date,dr.is_active,dr.is_deleted;

  INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
  SELECT dr.data_type,'dfg',d.dfg_id,dr.mode_40_date,dr.is_active,dr.is_deleted,
   SUM(dr.quantity),SUM(dr.sum_price_usd),SUM(dr.sum_price_uzs),SUM(dr.sum_price_eur),SUM(dr.sum_price_rub)
  FROM drug_reports dr LEFT JOIN drugs d ON dr.drug_id=d.id
  WHERE d.dfg_id IS NOT NULL AND d.dfg_id<>0 AND dr.mode_40_date IN (SELECT day FROM _chg)
  GROUP BY dr.data_type,d.dfg_id,dr.mode_40_date,dr.is_active,dr.is_deleted;

  INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
  SELECT dr.data_type,'dtg',d.dtg_id,dr.mode_40_date,dr.is_active,dr.is_deleted,
   SUM(dr.quantity),SUM(dr.sum_price_usd),SUM(dr.sum_price_uzs),SUM(dr.sum_price_eur),SUM(dr.sum_price_rub)
  FROM drug_reports dr LEFT JOIN drugs d ON dr.drug_id=d.id
  WHERE d.dtg_id IS NOT NULL AND d.dtg_id<>0 AND dr.mode_40_date IN (SELECT day FROM _chg)
  GROUP BY dr.data_type,d.dtg_id,dr.mode_40_date,dr.is_active,dr.is_deleted;

  INSERT INTO dr_rollup (data_type,dim_type,dim_id,day,is_active,is_deleted,qty,sum_usd,sum_uzs,sum_eur,sum_rub)
  SELECT dr.data_type,'trademark',d.trademark_id,dr.mode_40_date,dr.is_active,dr.is_deleted,
   SUM(dr.quantity),SUM(dr.sum_price_usd),SUM(dr.sum_price_uzs),SUM(dr.sum_price_eur),SUM(dr.sum_price_rub)
  FROM drug_reports dr LEFT JOIN drugs d ON dr.drug_id=d.id
  WHERE d.trademark_id IS NOT NULL AND d.trademark_id<>0 AND dr.mode_40_date IN (SELECT day FROM _chg)
  GROUP BY dr.data_type,d.trademark_id,dr.mode_40_date,dr.is_active,dr.is_deleted;

  UPDATE dr_rollup_state SET last_at = v_now WHERE id=1;
  DROP TEMPORARY TABLE IF EXISTS _chg;
END ;;
DELIMITER ;

-- ===== [4] FACT-ПРОЦЕДУРЫ (list+counts, оригиналы) =========================
DELIMITER ;;
DROP PROCEDURE IF EXISTS getCalcCountsFact ;;
CREATE PROCEDURE getCalcCountsFact(IN fDate varchar(20),
IN tDate varchar(20),
IN isActive tinyint(1),
IN isDeleted tinyint(1),
IN byTable varchar(20),
IN typeIdList varchar(255),
IN dataType tinyint,
IN regionID varchar(255),
IN districtID varchar(255))
BEGIN
  DECLARE selectColumn varchar(100) DEFAULT '';
  DECLARE joinClause longtext DEFAULT '';
  DECLARE typeFilter longtext DEFAULT '';

  /* source table */
  DECLARE srcTable varchar(128) DEFAULT 'almir_db_new.drug_reports';
  DECLARE needsDataTypeFilter tinyint DEFAULT 1;

  IF dataType = 1 THEN
    SET srcTable = 'almir_db_new.drug_reports_p';
    SET needsDataTypeFilter = 0;
  ELSEIF dataType = 2 THEN
    SET srcTable = 'almir_db_new.drug_reports_s';
    SET needsDataTypeFilter = 0;
  ELSE
    SET srcTable = 'almir_db_new.drug_reports';
    SET needsDataTypeFilter = 1;
  END IF;

  /* Base WHERE */
  SET @where = CONCAT('WHERE dr.mode_40_date BETWEEN STR_TO_DATE(''', fDate, ''', ''%m.%d.%Y'')',
  ' AND STR_TO_DATE(''', tDate, ''', ''%m.%d.%Y'')',
  ' AND dr.is_active = ', isActive,
  ' AND dr.is_deleted = ', isDeleted);

  IF needsDataTypeFilter = 1 THEN
    SET @where = CONCAT(@where, ' AND dr.data_type = ', dataType);
  END IF;

  /* Region / district filters */
  IF regionID IS NOT NULL
    AND regionID <> '' THEN
    SET @where = CONCAT(@where, ' AND dr.region_id IN (', regionID, ')');
  END IF;

  IF districtID IS NOT NULL
    AND districtID <> '' THEN
    SET @where = CONCAT(@where, ' AND dr.district_id IN (', districtID, ')');
  END IF;

  /* selectColumn by byTable */
  IF byTable = 'dist' THEN
    SET selectColumn = 'dr.m40d_id';
  ELSEIF byTable = 'sc' THEN
    /* продажа (dataType=2): поставщик = counterparty_id (contrahens); приход: sc_id (companies) */
    SET selectColumn = IF(dataType = 2, 'dr.counterparty_id', 'dr.sc_id');
  ELSEIF byTable = 'mf' THEN
    SET selectColumn = 'dr.mf_id';
  ELSEIF byTable IN ('dt', 'df', 'dfg', 'dtg', 'inn', 'trademark', 'drugs') THEN
    SET selectColumn = 'd.id';
  ELSE
    SET selectColumn = 'dr.m40d_id';
  END IF;

  /*
    JOIN and type filter
    - "drug" alias is used for drugs table in most branches
    - "d" alias is the last dimension table (types/forms/groups/inns/trademarks/drugs)
  */
  IF typeIdList IS NOT NULL
    AND typeIdList <> '' THEN
    /* default type filter via drugs table (alias drug), overwritten for byTable='drugs' */
    SET typeFilter = CONCAT(' AND drug.dt_id IN (', typeIdList, ')');

    IF byTable IN ('dist', 'sc', 'mf') THEN
      SET joinClause = ' LEFT JOIN drugs drug ON dr.drug_id = drug.id ';

    ELSEIF byTable = 'dt' THEN
      SET joinClause = ' LEFT JOIN drugs drug ON dr.drug_id = drug.id LEFT JOIN drug_types d ON drug.dt_id = d.id ';

    ELSEIF byTable = 'df' THEN
      SET joinClause = ' LEFT JOIN drugs drug ON dr.drug_id = drug.id LEFT JOIN drug_forms d ON drug.df_id = d.id ';

    ELSEIF byTable = 'dfg' THEN
      SET joinClause = ' LEFT JOIN drugs drug ON dr.drug_id = drug.id LEFT JOIN drug_farm_groups d ON drug.dfg_id = d.id ';

    ELSEIF byTable = 'dtg' THEN
      SET joinClause = ' LEFT JOIN drugs drug ON dr.drug_id = drug.id LEFT JOIN drug_ts_groups d ON drug.dtg_id = d.id ';

    ELSEIF byTable = 'inn' THEN
      SET joinClause = ' LEFT JOIN drugs drug ON dr.drug_id = drug.id LEFT JOIN drug_inns d ON drug.di_id = d.id ';

    ELSEIF byTable = 'trademark' THEN
      SET joinClause = ' LEFT JOIN drugs drug ON dr.drug_id = drug.id LEFT JOIN trademarks d ON drug.trademark_id = d.id ';

    ELSEIF byTable = 'drugs' THEN
      /* byTable=drugs branch uses alias d for drugs */
      SET joinClause = ' LEFT JOIN drugs d ON dr.drug_id = d.id ';
      SET typeFilter = CONCAT(' AND d.dt_id IN (', typeIdList, ')');
    END IF;

  ELSE
    /* typeIdList empty: add only required joins by byTable */
    IF byTable = 'dt' THEN
      SET joinClause = ' LEFT JOIN drugs drug ON dr.drug_id = drug.id LEFT JOIN drug_types d ON drug.dt_id = d.id ';

    ELSEIF byTable = 'df' THEN
      SET joinClause = ' LEFT JOIN drugs drug ON dr.drug_id = drug.id LEFT JOIN drug_forms d ON drug.df_id = d.id ';

    ELSEIF byTable = 'dfg' THEN
      SET joinClause = ' LEFT JOIN drugs drug ON dr.drug_id = drug.id LEFT JOIN drug_farm_groups d ON drug.dfg_id = d.id ';

    ELSEIF byTable = 'dtg' THEN
      SET joinClause = ' LEFT JOIN drugs drug ON dr.drug_id = drug.id LEFT JOIN drug_ts_groups d ON drug.dtg_id = d.id ';

    ELSEIF byTable = 'inn' THEN
      SET joinClause = ' LEFT JOIN drugs drug ON dr.drug_id = drug.id LEFT JOIN drug_inns d ON drug.di_id = d.id ';

    ELSEIF byTable = 'trademark' THEN
      SET joinClause = ' LEFT JOIN drugs drug ON dr.drug_id = drug.id LEFT JOIN trademarks d ON drug.trademark_id = d.id ';

    ELSEIF byTable = 'drugs' THEN
      SET joinClause = ' LEFT JOIN drugs d ON dr.drug_id = d.id ';
    END IF;
  END IF;

  /* Final query */
  SET @q = CONCAT('SELECT COUNT(DISTINCT ', selectColumn, ') AS counts ',
  'FROM ', srcTable, ' dr ',
  joinClause,
  ' ', @where,
  typeFilter);

  PREPARE stmt FROM @q;
  EXECUTE stmt;
  DEALLOCATE PREPARE stmt;
END ;;
DROP PROCEDURE IF EXISTS getPeriodDataListFact ;;
CREATE PROCEDURE getPeriodDataListFact(IN fDate varchar(20),
IN tDate varchar(20),
IN dCount int,
IN dLimit smallint,
IN dOffset smallint,
IN isActive tinyint,
IN isDeleted tinyint,
IN byTable varchar(20),
IN idList varchar(255),
IN sortBy varchar(20),
IN typeIdList varchar(255),
IN dataType tinyint,
IN regionID varchar(255),
IN districtID varchar(255))
BEGIN
  DECLARE qWhere longtext DEFAULT '';
  DECLARE dWhere varchar(64);
  DECLARE gBy varchar(64);
  DECLARE sumBy varchar(128) DEFAULT '';
  DECLARE lJoin longtext DEFAULT '';
  DECLARE RxOTC varchar(64) DEFAULT '';
  DECLARE orderExpr varchar(64) DEFAULT 'd.name';
  DECLARE orderDir varchar(4) DEFAULT 'ASC';

  DECLARE df date;
  DECLARE dt date;

  /* choose source table (master or clones) */
  DECLARE srcTable varchar(128) DEFAULT 'almir_db_new.drug_reports';
  DECLARE needsDataTypeFilter tinyint DEFAULT 1;

  /* Date parse once */
  SET df = STR_TO_DATE(fDate, '%m.%d.%Y');
  SET dt = STR_TO_DATE(tDate, '%m.%d.%Y');

  /* Source by dataType */
  IF dataType = 1 THEN
    SET srcTable = 'almir_db_new.drug_reports_p';
    SET needsDataTypeFilter = 0;
  ELSEIF dataType = 2 THEN
    SET srcTable = 'almir_db_new.drug_reports_s';
    SET needsDataTypeFilter = 0;
  ELSE
    SET srcTable = 'almir_db_new.drug_reports';
    SET needsDataTypeFilter = 1;
  END IF;

  /* Validate / normalize ORDER BY (whitelist) */
  IF sortBy = 'USD DESC' THEN
    SET orderExpr = 'USD';
    SET orderDir = 'DESC';
  ELSEIF sortBy = 'USD ASC' THEN
    SET orderExpr = 'USD';
    SET orderDir = 'ASC';
  ELSEIF sortBy = 'qty DESC' THEN
    SET orderExpr = 'qty';
    SET orderDir = 'DESC';
  ELSEIF sortBy = 'qty ASC' THEN
    SET orderExpr = 'qty';
    SET orderDir = 'ASC';
  ELSEIF sortBy = 'name DESC' THEN
    SET orderExpr = 'd.name';
    SET orderDir = 'DESC';
  ELSE
    SET orderExpr = 'd.name';
    SET orderDir = 'ASC';
  END IF;

  /* Add SUM columns only when needed by sort */
  IF orderExpr = 'USD' THEN
    SET sumBy = ', SUM(dr.sum_price_usd) AS USD';
  ELSEIF orderExpr = 'qty' THEN
    SET sumBy = ', SUM(dr.quantity) AS qty';
  END IF;

  /* Base WHERE (data_type predicate only for master table) */
  SET qWhere = CONCAT(' dr.mode_40_date BETWEEN "', df, '" AND "', dt, '"',
  ' AND dr.is_active = ', isActive,
  ' AND dr.is_deleted = ', isDeleted);

  IF needsDataTypeFilter = 1 THEN
    SET qWhere = CONCAT(qWhere, ' AND dr.data_type = ', dataType);
  END IF;

  /* Optional filters */
  IF (regionID IS NOT NULL
    AND regionID <> '') THEN
    SET qWhere = CONCAT(qWhere, ' AND dr.region_id IN (', regionID, ')');
  END IF;

  IF (districtID IS NOT NULL
    AND districtID <> '') THEN
    SET qWhere = CONCAT(qWhere, ' AND dr.district_id IN (', districtID, ')');
  END IF;

  /* Build GROUP/JOIN depending on byTable */
  IF (byTable = 'dist') THEN
    SET gBy = 'dr.m40d_id';
    SET dWhere = 'd.id';
    SET lJoin = 'LEFT JOIN distributors d ON dr.m40d_id = d.id';

    IF (idList IS NOT NULL
      AND idList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND dr.m40d_id IN (', idList, ')');
    END IF;

    IF (typeIdList IS NOT NULL
      AND typeIdList <> '') THEN
      SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs ddd ON dr.drug_id = ddd.id');
      SET qWhere = CONCAT(qWhere, ' AND ddd.dt_id IN (', typeIdList, ')');
    END IF;

  ELSEIF (byTable = 'sc') THEN
    /* продажа (dataType=2): поставщик = counterparty_id (contrahens); приход: sc_id (companies) */
    IF dataType = 2 THEN
      SET gBy = 'dr.counterparty_id';
      SET dWhere = 'dr.counterparty_id';
      SET lJoin = 'LEFT JOIN contrahens d ON dr.counterparty_id = d.id';
    ELSE
      SET gBy = 'dr.sc_id';
      SET dWhere = 'dr.sc_id';
      SET lJoin = 'LEFT JOIN companies d ON dr.sc_id = d.id';
    END IF;

    IF (idList IS NOT NULL
      AND idList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND ', gBy, ' IN (', idList, ')');
    END IF;

    IF (typeIdList IS NOT NULL
      AND typeIdList <> '') THEN
      SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs ddd ON dr.drug_id = ddd.id');
      SET qWhere = CONCAT(qWhere, ' AND ddd.dt_id IN (', typeIdList, ')');
    END IF;

  ELSEIF (byTable = 'mf') THEN
    SET gBy = 'dr.mf_id';
    SET dWhere = 'dr.mf_id';
    SET lJoin = 'LEFT JOIN manufacturers d ON dr.mf_id = d.id';

    IF (idList IS NOT NULL
      AND idList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND dr.mf_id IN (', idList, ')');
    END IF;

    IF (typeIdList IS NOT NULL
      AND typeIdList <> '') THEN
      SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs ddd ON dr.drug_id = ddd.id');
      SET qWhere = CONCAT(qWhere, ' AND ddd.dt_id IN (', typeIdList, ')');
    END IF;

  ELSEIF (byTable = 'inn') THEN
    SET gBy = 'd.id';
    SET dWhere = 'd.id';
    SET lJoin = 'LEFT JOIN drugs drug ON dr.drug_id = drug.id LEFT JOIN drug_inns d ON drug.di_id = d.id';

    IF (idList IS NOT NULL
      AND idList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND d.id IN (', idList, ')');
    END IF;

    IF (typeIdList IS NOT NULL
      AND typeIdList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND drug.dt_id IN (', typeIdList, ')');
    END IF;

  ELSEIF (byTable = 'dt') THEN
    SET gBy = 'd.id';
    SET dWhere = 'd.id';
    SET lJoin = 'LEFT JOIN drugs drug ON dr.drug_id = drug.id LEFT JOIN drug_types d ON drug.dt_id = d.id';

    IF (idList IS NOT NULL
      AND idList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND d.id IN (', idList, ')');
    END IF;

  ELSEIF (byTable = 'df') THEN
    SET gBy = 'd.id';
    SET dWhere = 'd.id';
    SET lJoin = 'LEFT JOIN drugs drug ON dr.drug_id = drug.id LEFT JOIN drug_forms d ON drug.df_id = d.id';

    IF (idList IS NOT NULL
      AND idList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND d.id IN (', idList, ')');
    END IF;

    IF (typeIdList IS NOT NULL
      AND typeIdList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND drug.dt_id IN (', typeIdList, ')');
    END IF;

  ELSEIF (byTable = 'dfg') THEN
    SET gBy = 'd.id';
    SET dWhere = 'd.id';
    SET lJoin = 'LEFT JOIN drugs drug ON dr.drug_id = drug.id LEFT JOIN drug_farm_groups d ON drug.dfg_id = d.id';

    IF (idList IS NOT NULL
      AND idList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND d.id IN (', idList, ')');
    END IF;

    IF (typeIdList IS NOT NULL
      AND typeIdList <> '') THEN
      SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs ddd ON dr.drug_id = ddd.id');
      SET qWhere = CONCAT(qWhere, ' AND ddd.dt_id IN (', typeIdList, ')');
    END IF;

  ELSEIF (byTable = 'dtg') THEN
    SET gBy = 'd.id';
    SET dWhere = 'd.id';
    SET lJoin = 'LEFT JOIN drugs drug ON dr.drug_id = drug.id LEFT JOIN drug_ts_groups d ON drug.dtg_id = d.id';

    IF (idList IS NOT NULL
      AND idList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND d.id IN (', idList, ')');
    END IF;

    IF (typeIdList IS NOT NULL
      AND typeIdList <> '') THEN
      SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs ddd ON dr.drug_id = ddd.id');
      SET qWhere = CONCAT(qWhere, ' AND ddd.dt_id IN (', typeIdList, ')');
    END IF;

  ELSEIF (byTable = 'trademark') THEN
    SET gBy = 'd.id';
    SET dWhere = 'd.id';
    SET lJoin = 'LEFT JOIN drugs drug ON dr.drug_id = drug.id LEFT JOIN trademarks d ON drug.trademark_id = d.id';

    IF (idList IS NOT NULL
      AND idList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND d.id IN (', idList, ')');
    END IF;

    IF (typeIdList IS NOT NULL
      AND typeIdList <> '') THEN
      SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs ddd ON dr.drug_id = ddd.id');
      SET qWhere = CONCAT(qWhere, ' AND ddd.dt_id IN (', typeIdList, ')');
    END IF;

  ELSEIF (byTable = 'drugs') THEN
    SET gBy = 'dr.drug_id';
    SET dWhere = 'd.id';
    SET RxOTC = ', d.is_rx, d.is_otc';
    SET lJoin = 'LEFT JOIN drugs d ON dr.drug_id = d.id';

    IF (idList IS NOT NULL
      AND idList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND d.id IN (', idList, ')');
    END IF;

    IF (typeIdList IS NOT NULL
      AND typeIdList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND d.dt_id IN (', typeIdList, ')');
    END IF;

  ELSE
    SET gBy = 'dr.m40d_id';
    SET dWhere = 'd.id';
    SET lJoin = 'LEFT JOIN distributors d ON dr.m40d_id = d.id';
  END IF;

  /* Final query */
  SET @q = CONCAT('SELECT ',
  dWhere, ' AS id, ',
  'd.name AS name',
  RxOTC,
  sumBy,
  ' FROM ', srcTable, ' dr ',
  lJoin,
  ' WHERE ', qWhere,
  ' GROUP BY ', gBy,
  ' ORDER BY ', orderExpr, ' ', orderDir,
  ' LIMIT ', dOffset, ', ', dLimit);

  PREPARE stmt FROM @q;
  EXECUTE stmt;
  DEALLOCATE PREPARE stmt;
END ;;
DELIMITER ;

-- ===== [5] РОЛЛАП: list + counts ==========================================
-- ============================================================================
--  Роллап-версии отчётных процедур.
--  Быстрый путь: читают dr_rollup. Если задан фильтр по типу препарата
--  (typeIdList) — откатываются на точную факт-версию (*Fact), т.к. роллап
--  агрегирован по измерению без разбивки по типам.
--  byTable + dataType -> dim_type + таблица-справочник (для имени):
--    dist->m40d/distributors, sc->(продажа:counterparty/contrahens; приход:sc/companies),
--    mf->mf/manufacturers, inn->inn/drug_inns, df->df/drug_forms,
--    dfg->dfg/drug_farm_groups, dtg->dtg/drug_ts_groups,
--    trademark->trademark/trademarks, drugs->drug/drugs
-- ============================================================================
DELIMITER ;;

DROP PROCEDURE IF EXISTS getCalcCounts ;;
CREATE PROCEDURE getCalcCounts(IN fDate varchar(20), IN tDate varchar(20),
  IN isActive tinyint(1), IN isDeleted tinyint(1), IN byTable varchar(20),
  IN typeIdList varchar(255), IN dataType tinyint, IN regionID varchar(255), IN districtID varchar(255))
BEGIN
  DECLARE df date; DECLARE dt date;
  DECLARE dimType varchar(16);
  DECLARE qWhere longtext;

  /* фильтр по типу препарата / региону / району -> точная факт-версия (роллап их не хранит) */
  IF (typeIdList IS NOT NULL AND typeIdList <> '')
     OR (regionID IS NOT NULL AND regionID <> '')
     OR (districtID IS NOT NULL AND districtID <> '') THEN
    CALL getCalcCountsFact(fDate, tDate, isActive, isDeleted, byTable, typeIdList, dataType, regionID, districtID);
  ELSE
    SET df = STR_TO_DATE(fDate, '%m.%d.%Y');
    SET dt = STR_TO_DATE(tDate, '%m.%d.%Y');

    IF byTable = 'dist' THEN SET dimType = 'm40d';
    ELSEIF byTable = 'sc' THEN SET dimType = IF(dataType = 2, 'counterparty', 'sc');
    ELSEIF byTable = 'mf' THEN SET dimType = 'mf';
    ELSEIF byTable = 'inn' THEN SET dimType = 'inn';
    ELSEIF byTable = 'df' THEN SET dimType = 'df';
    ELSEIF byTable = 'dfg' THEN SET dimType = 'dfg';
    ELSEIF byTable = 'dtg' THEN SET dimType = 'dtg';
    ELSEIF byTable = 'trademark' THEN SET dimType = 'trademark';
    ELSEIF byTable = 'drugs' THEN SET dimType = 'drug';
    ELSE SET dimType = 'm40d'; END IF;

    SET qWhere = CONCAT(' r.data_type=', dataType, " AND r.dim_type='", dimType, "'",
      ' AND r.day BETWEEN "', df, '" AND "', dt, '"',
      ' AND r.is_active=', isActive, ' AND r.is_deleted=', isDeleted);

    SET @q = CONCAT('SELECT COUNT(DISTINCT r.dim_id) AS counts FROM dr_rollup r WHERE', qWhere);
    PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END ;;

DROP PROCEDURE IF EXISTS getPeriodDataList ;;
CREATE PROCEDURE getPeriodDataList(IN fDate varchar(20), IN tDate varchar(20),
  IN dCount int, IN dLimit smallint, IN dOffset smallint, IN isActive tinyint, IN isDeleted tinyint,
  IN byTable varchar(20), IN idList varchar(255), IN sortBy varchar(20),
  IN typeIdList varchar(255), IN dataType tinyint, IN regionID varchar(255), IN districtID varchar(255))
BEGIN
  DECLARE df date; DECLARE dt date;
  DECLARE dimType varchar(16); DECLARE dimTable varchar(64);
  DECLARE orderExpr varchar(64) DEFAULT 'name'; DECLARE orderDir varchar(4) DEFAULT 'ASC';
  DECLARE sumBy varchar(128) DEFAULT '';
  DECLARE RxOTC varchar(64) DEFAULT '';
  DECLARE qWhere longtext;

  /* фильтр по типу препарата / региону / району -> точная факт-версия (роллап их не хранит) */
  IF (typeIdList IS NOT NULL AND typeIdList <> '')
     OR (regionID IS NOT NULL AND regionID <> '')
     OR (districtID IS NOT NULL AND districtID <> '') THEN
    CALL getPeriodDataListFact(fDate, tDate, dCount, dLimit, dOffset, isActive, isDeleted,
      byTable, idList, sortBy, typeIdList, dataType, regionID, districtID);
  ELSE
    SET df = STR_TO_DATE(fDate, '%m.%d.%Y');
    SET dt = STR_TO_DATE(tDate, '%m.%d.%Y');

    IF byTable = 'dist' THEN SET dimType='m40d'; SET dimTable='distributors';
    ELSEIF byTable = 'sc' THEN
      IF dataType = 2 THEN SET dimType='counterparty'; SET dimTable='contrahens';
      ELSE SET dimType='sc'; SET dimTable='companies'; END IF;
    ELSEIF byTable = 'mf' THEN SET dimType='mf'; SET dimTable='manufacturers';
    ELSEIF byTable = 'inn' THEN SET dimType='inn'; SET dimTable='drug_inns';
    ELSEIF byTable = 'df' THEN SET dimType='df'; SET dimTable='drug_forms';
    ELSEIF byTable = 'dfg' THEN SET dimType='dfg'; SET dimTable='drug_farm_groups';
    ELSEIF byTable = 'dtg' THEN SET dimType='dtg'; SET dimTable='drug_ts_groups';
    ELSEIF byTable = 'trademark' THEN SET dimType='trademark'; SET dimTable='trademarks';
    ELSEIF byTable = 'drugs' THEN SET dimType='drug'; SET dimTable='drugs';
    ELSE SET dimType='m40d'; SET dimTable='distributors'; END IF;

    /* вкладка «по препаратам» отдаёт ещё is_rx/is_otc — сохраняем форму ответа fact-версии */
    IF dimType = 'drug' THEN SET RxOTC = ', d.is_rx, d.is_otc'; END IF;

    IF sortBy = 'USD DESC' THEN SET orderExpr='USD'; SET orderDir='DESC'; SET sumBy=', SUM(r.sum_usd) AS USD';
    ELSEIF sortBy = 'USD ASC' THEN SET orderExpr='USD'; SET orderDir='ASC'; SET sumBy=', SUM(r.sum_usd) AS USD';
    ELSEIF sortBy = 'qty DESC' THEN SET orderExpr='qty'; SET orderDir='DESC'; SET sumBy=', CAST(SUM(r.qty) AS SIGNED) AS qty';
    ELSEIF sortBy = 'qty ASC' THEN SET orderExpr='qty'; SET orderDir='ASC'; SET sumBy=', CAST(SUM(r.qty) AS SIGNED) AS qty';
    ELSEIF sortBy = 'name DESC' THEN SET orderExpr='name'; SET orderDir='DESC';
    ELSE SET orderExpr='name'; SET orderDir='ASC'; END IF;

    SET qWhere = CONCAT(' r.data_type=', dataType, " AND r.dim_type='", dimType, "'",
      ' AND r.day BETWEEN "', df, '" AND "', dt, '"',
      ' AND r.is_active=', isActive, ' AND r.is_deleted=', isDeleted);
    IF idList IS NOT NULL AND idList <> '' THEN SET qWhere = CONCAT(qWhere, ' AND r.dim_id IN (', idList, ')'); END IF;

    SET @q = CONCAT('SELECT r.dim_id AS id, d.name AS name', RxOTC, sumBy,
      ' FROM dr_rollup r LEFT JOIN ', dimTable, ' d ON d.id = r.dim_id',
      ' WHERE', qWhere,
      ' GROUP BY r.dim_id ORDER BY ', orderExpr, ' ', orderDir,
      ' LIMIT ', dOffset, ', ', dLimit);
    PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END ;;

DELIMITER ;

-- ===== [5b] РОЛЛАП: getCommonPerPrice (суммы по измерению) =================
-- ============================================================================
--  getCommonPerPrice: суммы (qty/USD/UZS/EUR/RUB) по ОДНОМУ измерению за период.
--  Это точный аналог одной строки dr_rollup -> быстрый путь читает роллап.
--  Fallback на точную факт-версию при фильтре по типу/региону/району, а также
--  для измерений, которых нет в роллапе (dt и любые прочие).
--  Форма ответа сохранена: колонки qty, USD, UZS, EUR, RUB (+ id измерения).
-- ============================================================================
DELIMITER ;;

DROP PROCEDURE IF EXISTS getCommonPerPriceFact ;;
CREATE PROCEDURE getCommonPerPriceFact(IN fDate varchar(20),
IN tDate varchar(20),
IN dataID int,
IN isActive tinyint,
IN isDeleted tinyint,
IN byTable varchar(20),
IN typeIdList varchar(255),
IN dataType tinyint,
IN regionID varchar(255),
IN districtID varchar(255))
    COMMENT 'TotalCommonPerPrice (fact)'
BEGIN
  DECLARE qWhere longtext DEFAULT '';
  DECLARE gBy varchar(64) DEFAULT '';
  DECLARE lJoin longtext DEFAULT '';

  DECLARE srcTable varchar(128) DEFAULT 'almir_db_new.drug_reports';
  DECLARE needsDataTypeFilter tinyint DEFAULT 1;

  IF dataType = 1 THEN
    SET srcTable = 'almir_db_new.drug_reports_p';
    SET needsDataTypeFilter = 0;
  ELSEIF dataType = 2 THEN
    SET srcTable = 'almir_db_new.drug_reports_s';
    SET needsDataTypeFilter = 0;
  ELSE
    SET srcTable = 'almir_db_new.drug_reports';
    SET needsDataTypeFilter = 1;
  END IF;

  SET qWhere = '1=1';
  IF needsDataTypeFilter = 1 THEN
    SET qWhere = CONCAT(qWhere, ' AND dr.data_type = ', dataType);
  END IF;

  IF (regionID IS NOT NULL AND regionID <> '') THEN
    SET qWhere = CONCAT(qWhere, ' AND dr.region_id IN (', regionID, ')');
  END IF;
  IF (districtID IS NOT NULL AND districtID <> '') THEN
    SET qWhere = CONCAT(qWhere, ' AND dr.district_id IN (', districtID, ')');
  END IF;

  IF (byTable = 'dist') THEN
    SET gBy = 'dr.m40d_id';
    SET qWhere = CONCAT(qWhere, ' AND dr.m40d_id = ', dataID);
    IF (typeIdList IS NOT NULL AND typeIdList <> '') THEN
      SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs d ON dr.drug_id = d.id ');
      SET qWhere = CONCAT(qWhere, ' AND d.dt_id IN (', typeIdList, ')');
    END IF;
  ELSEIF (byTable = 'sc') THEN
    IF dataType = 2 THEN
      SET gBy = 'dr.counterparty_id';
      SET qWhere = CONCAT(qWhere, ' AND dr.counterparty_id = ', dataID);
    ELSE
      SET gBy = 'dr.sc_id';
      SET qWhere = CONCAT(qWhere, ' AND dr.sc_id = ', dataID);
    END IF;
    IF (typeIdList IS NOT NULL AND typeIdList <> '') THEN
      SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs d ON dr.drug_id = d.id ');
      SET qWhere = CONCAT(qWhere, ' AND d.dt_id IN (', typeIdList, ')');
    END IF;
  ELSEIF (byTable = 'mf') THEN
    SET gBy = 'dr.mf_id';
    SET qWhere = CONCAT(qWhere, ' AND dr.mf_id = ', dataID);
    IF (typeIdList IS NOT NULL AND typeIdList <> '') THEN
      SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs d ON dr.drug_id = d.id ');
      SET qWhere = CONCAT(qWhere, ' AND d.dt_id IN (', typeIdList, ')');
    END IF;
  ELSEIF (byTable = 'inn') THEN
    SET gBy = 'di.id';
    SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs d ON dr.drug_id = d.id LEFT JOIN drug_inns di ON d.di_id = di.id ');
    SET qWhere = CONCAT(qWhere, ' AND di.id = ', dataID);
    IF (typeIdList IS NOT NULL AND typeIdList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND d.dt_id IN (', typeIdList, ')');
    END IF;
  ELSEIF (byTable = 'dt') THEN
    SET gBy = 'dt.id';
    SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs d ON dr.drug_id = d.id LEFT JOIN drug_types dt ON d.dt_id = dt.id ');
    SET qWhere = CONCAT(qWhere, ' AND dt.id = ', dataID);
  ELSEIF (byTable = 'df') THEN
    SET gBy = 'df.id';
    SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs d ON dr.drug_id = d.id LEFT JOIN drug_forms df ON d.df_id = df.id ');
    SET qWhere = CONCAT(qWhere, ' AND df.id = ', dataID);
    IF (typeIdList IS NOT NULL AND typeIdList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND d.dt_id IN (', typeIdList, ')');
    END IF;
  ELSEIF (byTable = 'dfg') THEN
    SET gBy = 'dfg.id';
    SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs d ON dr.drug_id = d.id LEFT JOIN drug_farm_groups dfg ON d.dfg_id = dfg.id ');
    SET qWhere = CONCAT(qWhere, ' AND dfg.id = ', dataID);
    IF (typeIdList IS NOT NULL AND typeIdList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND d.dt_id IN (', typeIdList, ')');
    END IF;
  ELSEIF (byTable = 'dtg') THEN
    SET gBy = 'dtg.id';
    SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs d ON dr.drug_id = d.id LEFT JOIN drug_ts_groups dtg ON d.dtg_id = dtg.id ');
    SET qWhere = CONCAT(qWhere, ' AND dtg.id = ', dataID);
    IF (typeIdList IS NOT NULL AND typeIdList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND d.dt_id IN (', typeIdList, ')');
    END IF;
  ELSEIF (byTable = 'trademark') THEN
    SET gBy = 't.id';
    SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs d ON dr.drug_id = d.id LEFT JOIN trademarks t ON d.trademark_id = t.id ');
    SET qWhere = CONCAT(qWhere, ' AND t.id = ', dataID);
    IF (typeIdList IS NOT NULL AND typeIdList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND d.dt_id IN (', typeIdList, ')');
    END IF;
  ELSEIF (byTable = 'drugs') THEN
    SET gBy = 'd.id';
    SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs d ON dr.drug_id = d.id ');
    SET qWhere = CONCAT(qWhere, ' AND d.id = ', dataID);
    IF (typeIdList IS NOT NULL AND typeIdList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND d.dt_id IN (', typeIdList, ')');
    END IF;
  END IF;

  SET @q = CONCAT('SELECT ', gBy, ', ',
  '  SUM(dr.quantity) AS qty,',
  '  SUM(dr.sum_price_usd) AS USD,',
  '  SUM(dr.sum_price_uzs) AS UZS,',
  '  SUM(dr.sum_price_eur) AS EUR,',
  '  SUM(dr.sum_price_rub) AS RUB',
  ' FROM ', srcTable, ' dr ',
  lJoin,
  ' WHERE ', qWhere,
  ' AND dr.is_active = ', isActive,
  ' AND dr.is_deleted = ', isDeleted,
  ' AND dr.mode_40_date BETWEEN STR_TO_DATE("', fDate, '", "%m.%d.%Y") AND STR_TO_DATE("', tDate, '", "%m.%d.%Y")',
  ' GROUP BY ', gBy);

  PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;
END ;;

DROP PROCEDURE IF EXISTS getCommonPerPrice ;;
CREATE PROCEDURE getCommonPerPrice(IN fDate varchar(20),
IN tDate varchar(20),
IN dataID int,
IN isActive tinyint,
IN isDeleted tinyint,
IN byTable varchar(20),
IN typeIdList varchar(255),
IN dataType tinyint,
IN regionID varchar(255),
IN districtID varchar(255))
    COMMENT 'TotalCommonPerPrice (rollup fast-path)'
BEGIN
  DECLARE df date; DECLARE dt date; DECLARE dimType varchar(16);

  IF (typeIdList IS NOT NULL AND typeIdList <> '')
     OR (regionID IS NOT NULL AND regionID <> '')
     OR (districtID IS NOT NULL AND districtID <> '') THEN
    CALL getCommonPerPriceFact(fDate, tDate, dataID, isActive, isDeleted, byTable, typeIdList, dataType, regionID, districtID);
  ELSEIF byTable IN ('dist','sc','mf','inn','df','dfg','dtg','trademark','drugs') THEN
    SET df = STR_TO_DATE(fDate, '%m.%d.%Y');
    SET dt = STR_TO_DATE(tDate, '%m.%d.%Y');
    IF byTable = 'dist' THEN SET dimType = 'm40d';
    ELSEIF byTable = 'sc' THEN SET dimType = IF(dataType = 2, 'counterparty', 'sc');
    ELSEIF byTable = 'mf' THEN SET dimType = 'mf';
    ELSEIF byTable = 'inn' THEN SET dimType = 'inn';
    ELSEIF byTable = 'df' THEN SET dimType = 'df';
    ELSEIF byTable = 'dfg' THEN SET dimType = 'dfg';
    ELSEIF byTable = 'dtg' THEN SET dimType = 'dtg';
    ELSEIF byTable = 'trademark' THEN SET dimType = 'trademark';
    ELSE SET dimType = 'drug'; END IF;

    SET @q = CONCAT('SELECT ', dataID, ' AS id,',
      ' CAST(SUM(qty) AS SIGNED) AS qty, SUM(sum_usd) AS USD, SUM(sum_uzs) AS UZS,',
      ' SUM(sum_eur) AS EUR, SUM(sum_rub) AS RUB',
      ' FROM dr_rollup',
      ' WHERE data_type=', dataType, " AND dim_type='", dimType, "'",
      ' AND dim_id=', dataID,
      ' AND day BETWEEN "', df, '" AND "', dt, '"',
      ' AND is_active=', isActive, ' AND is_deleted=', isDeleted,
      ' GROUP BY dim_id');
    PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  ELSE
    /* измерение без роллапа (например dt) -> точная факт-версия */
    CALL getCommonPerPriceFact(fDate, tDate, dataID, isActive, isDeleted, byTable, typeIdList, dataType, regionID, districtID);
  END IF;
END ;;

DELIMITER ;

-- ===== [5c] РОЛЛАП: getCommPrice (итого за период) ========================
-- ============================================================================
--  getCommPrice: «итого за период» (SUM qty/USD/UZS/EUR/RUB).
--  Быстрый путь через dr_rollup:
--    - без idList  -> grand-total: сумма ПОЛНОГО измерения 'drug' (в _s/_p нет
--                     строк с drug_id=0/NULL, поэтому это точный общий итог);
--    - с idList    -> сумма по нужному измерению (dim_id IN idList).
--  Fallback на точную факт-версию при фильтре по типу/региону/району и для
--  измерений вне роллапа (dt и т.п.). Форма ответа сохранена.
--  ВАЖНО: это был некэшируемый полный скан фактов (~30с на широком периоде).
-- ============================================================================
SET @old_sql_mode = @@session.sql_mode;
SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
DELIMITER ;;

DROP PROCEDURE IF EXISTS getCommPriceFact ;;
CREATE PROCEDURE getCommPriceFact(IN fDate varchar(20),
IN tDate varchar(20),
IN byTable varchar(20),
IN idList varchar(1000),
IN isActive tinyint,
IN isDeleted tinyint,
IN typeIdList varchar(255),
IN dataType tinyint,
IN regionID varchar(255),
IN districtID varchar(255))
BEGIN
  DECLARE qWhere longtext DEFAULT '';
  DECLARE lJoin longtext DEFAULT '';
  DECLARE srcTable varchar(128) DEFAULT 'almir_db_new.drug_reports';
  DECLARE needsDataTypeFilter tinyint DEFAULT 1;

  IF dataType = 1 THEN SET srcTable = 'almir_db_new.drug_reports_p'; SET needsDataTypeFilter = 0;
  ELSEIF dataType = 2 THEN SET srcTable = 'almir_db_new.drug_reports_s'; SET needsDataTypeFilter = 0;
  ELSE SET srcTable = 'almir_db_new.drug_reports'; SET needsDataTypeFilter = 1; END IF;

  SET qWhere = CONCAT(' dr.is_active = ', isActive,
  ' AND dr.is_deleted = ', isDeleted,
  ' AND (dr.mode_40_date BETWEEN STR_TO_DATE("', fDate, '", "%m.%d.%Y") AND STR_TO_DATE("', tDate, '", "%m.%d.%Y"))');
  IF needsDataTypeFilter = 1 THEN SET qWhere = CONCAT(' dr.data_type = ', dataType, ' AND ', qWhere); END IF;
  IF (regionID IS NOT NULL AND regionID <> '') THEN SET qWhere = CONCAT(' dr.region_id IN (', regionID, ') AND ', qWhere); END IF;
  IF (districtID IS NOT NULL AND districtID <> '') THEN SET qWhere = CONCAT(' dr.district_id IN (', districtID, ') AND ', qWhere); END IF;

  IF (byTable = 'drug') THEN
    IF (idList IS NOT NULL AND idList <> '') THEN SET qWhere = CONCAT(' dr.drug_id IN (', idList, ') AND ', qWhere); END IF;
  ELSEIF (byTable = 'sc') THEN
    /* продажа (dataType=2): поставщик = counterparty_id; приход: sc_id */
    IF (idList IS NOT NULL AND idList <> '') THEN
      SET qWhere = CONCAT(' ', IF(dataType=2,'dr.counterparty_id','dr.sc_id'), ' IN (', idList, ') AND ', qWhere);
    END IF;
    IF (typeIdList IS NOT NULL AND typeIdList <> '') THEN
      SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs ddd ON dr.drug_id = ddd.id ');
      SET qWhere = CONCAT(' ddd.dt_id IN (', typeIdList, ') AND ', qWhere);
    END IF;
  ELSEIF (byTable = 'dist') THEN
    IF (idList IS NOT NULL AND idList <> '') THEN SET qWhere = CONCAT(' dr.m40d_id IN (', idList, ') AND ', qWhere); END IF;
    IF (typeIdList IS NOT NULL AND typeIdList <> '') THEN
      SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs ddd ON dr.drug_id = ddd.id ');
      SET qWhere = CONCAT(' ddd.dt_id IN (', typeIdList, ') AND ', qWhere);
    END IF;
  ELSEIF (byTable = 'mf') THEN
    IF (idList IS NOT NULL AND idList <> '') THEN SET qWhere = CONCAT(' dr.mf_id IN (', idList, ') AND ', qWhere); END IF;
    IF (typeIdList IS NOT NULL AND typeIdList <> '') THEN
      SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs ddd ON dr.drug_id = ddd.id ');
      SET qWhere = CONCAT(' ddd.dt_id IN (', typeIdList, ') AND ', qWhere);
    END IF;
  ELSEIF (byTable = 'inn') THEN
    SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs drug ON dr.drug_id = drug.id ');
    IF (idList IS NOT NULL AND idList <> '') THEN SET qWhere = CONCAT(' drug.di_id IN (', idList, ') AND ', qWhere); END IF;
    IF (typeIdList IS NOT NULL AND typeIdList <> '') THEN SET qWhere = CONCAT(' drug.dt_id IN (', typeIdList, ') AND ', qWhere); END IF;
  ELSEIF (byTable = 'dt') THEN
    SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs drug ON dr.drug_id = drug.id ');
    IF (idList IS NOT NULL AND idList <> '') THEN SET qWhere = CONCAT(' drug.dt_id IN (', idList, ') AND ', qWhere); END IF;
    IF (typeIdList IS NOT NULL AND typeIdList <> '') THEN SET qWhere = CONCAT(' drug.dt_id IN (', typeIdList, ') AND ', qWhere); END IF;
  ELSEIF (byTable = 'df') THEN
    SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs drug ON dr.drug_id = drug.id ');
    IF (idList IS NOT NULL AND idList <> '') THEN SET qWhere = CONCAT(' drug.df_id IN (', idList, ') AND ', qWhere); END IF;
    IF (typeIdList IS NOT NULL AND typeIdList <> '') THEN SET qWhere = CONCAT(' drug.dt_id IN (', typeIdList, ') AND ', qWhere); END IF;
  ELSEIF (byTable = 'dfg') THEN
    SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs drug ON dr.drug_id = drug.id ');
    IF (idList IS NOT NULL AND idList <> '') THEN SET qWhere = CONCAT(' drug.dfg_id IN (', idList, ') AND ', qWhere); END IF;
    IF (typeIdList IS NOT NULL AND typeIdList <> '') THEN SET qWhere = CONCAT(' drug.dt_id IN (', typeIdList, ') AND ', qWhere); END IF;
  ELSEIF (byTable = 'dtg') THEN
    SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs drug ON dr.drug_id = drug.id ');
    IF (idList IS NOT NULL AND idList <> '') THEN SET qWhere = CONCAT(' drug.dtg_id IN (', idList, ') AND ', qWhere); END IF;
    IF (typeIdList IS NOT NULL AND typeIdList <> '') THEN SET qWhere = CONCAT(' drug.dt_id IN (', typeIdList, ') AND ', qWhere); END IF;
  ELSEIF (byTable = 'trademark') THEN
    SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs drug ON dr.drug_id = drug.id ');
    IF (idList IS NOT NULL AND idList <> '') THEN SET qWhere = CONCAT(' drug.trademark_id IN (', idList, ') AND ', qWhere); END IF;
    IF (typeIdList IS NOT NULL AND typeIdList <> '') THEN SET qWhere = CONCAT(' drug.dt_id IN (', typeIdList, ') AND ', qWhere); END IF;
  ELSEIF ((byTable = '' OR byTable IS NULL) AND (typeIdList IS NOT NULL AND typeIdList <> '')) THEN
    SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs drug ON dr.drug_id = drug.id ');
    SET qWhere = CONCAT(' drug.dt_id IN (', typeIdList, ') AND ', qWhere);
  END IF;

  SET @q = CONCAT('SELECT ',
  '  SUM(dr.quantity) AS qty,',
  '  SUM(dr.sum_price_usd) AS USD,',
  '  SUM(dr.sum_price_uzs) AS UZS,',
  '  SUM(dr.sum_price_eur) AS EUR,',
  '  SUM(dr.sum_price_rub) AS RUB',
  ' FROM ', srcTable, ' dr ',
  lJoin,
  ' WHERE ', qWhere);
  PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;
END ;;

DROP PROCEDURE IF EXISTS getCommPrice ;;
CREATE PROCEDURE getCommPrice(IN fDate varchar(20),
IN tDate varchar(20),
IN byTable varchar(20),
IN idList varchar(1000),
IN isActive tinyint,
IN isDeleted tinyint,
IN typeIdList varchar(255),
IN dataType tinyint,
IN regionID varchar(255),
IN districtID varchar(255))
BEGIN
  DECLARE df date; DECLARE dt date; DECLARE dimType varchar(16);

  IF (typeIdList IS NOT NULL AND typeIdList <> '')
     OR (regionID IS NOT NULL AND regionID <> '')
     OR (districtID IS NOT NULL AND districtID <> '')
     OR (dataType NOT IN (1,2)) THEN
    CALL getCommPriceFact(fDate, tDate, byTable, idList, isActive, isDeleted, typeIdList, dataType, regionID, districtID);
  ELSE
    SET df = STR_TO_DATE(fDate, '%m.%d.%Y');
    SET dt = STR_TO_DATE(tDate, '%m.%d.%Y');

    IF (idList IS NULL OR idList = '') THEN
      /* общий итог: полное измерение 'drug' (нет drug_id=0/NULL) */
      SET @q = CONCAT('SELECT CAST(SUM(qty) AS SIGNED) AS qty,',
        ' SUM(sum_usd) AS USD, SUM(sum_uzs) AS UZS, SUM(sum_eur) AS EUR, SUM(sum_rub) AS RUB',
        ' FROM dr_rollup WHERE data_type=', dataType, " AND dim_type='drug'",
        ' AND day BETWEEN "', df, '" AND "', dt, '"',
        ' AND is_active=', isActive, ' AND is_deleted=', isDeleted);
      PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    ELSE
      IF byTable = 'dist' THEN SET dimType='m40d';
      ELSEIF byTable = 'sc' THEN SET dimType=IF(dataType=2,'counterparty','sc');
      ELSEIF byTable = 'mf' THEN SET dimType='mf';
      ELSEIF byTable = 'inn' THEN SET dimType='inn';
      ELSEIF byTable = 'df' THEN SET dimType='df';
      ELSEIF byTable = 'dfg' THEN SET dimType='dfg';
      ELSEIF byTable = 'dtg' THEN SET dimType='dtg';
      ELSEIF byTable = 'trademark' THEN SET dimType='trademark';
      ELSEIF byTable = 'drug' THEN SET dimType='drug';
      ELSE SET dimType=''; END IF;

      IF dimType = '' THEN
        /* измерение вне роллапа (например dt) -> точная факт-версия */
        CALL getCommPriceFact(fDate, tDate, byTable, idList, isActive, isDeleted, typeIdList, dataType, regionID, districtID);
      ELSE
        SET @q = CONCAT('SELECT CAST(SUM(qty) AS SIGNED) AS qty,',
          ' SUM(sum_usd) AS USD, SUM(sum_uzs) AS UZS, SUM(sum_eur) AS EUR, SUM(sum_rub) AS RUB',
          ' FROM dr_rollup WHERE data_type=', dataType, " AND dim_type='", dimType, "'",
          ' AND dim_id IN (', idList, ')',
          ' AND day BETWEEN "', df, '" AND "', dt, '"',
          ' AND is_active=', isActive, ' AND is_deleted=', isDeleted);
        PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;
      END IF;
    END IF;
  END IF;
END ;;

DELIMITER ;
SET SESSION sql_mode = @old_sql_mode;

-- ===== [6] СОБЫТИЕ АВТО-ОБНОВЛЕНИЯ ========================================
-- ============================================================================
--  P2 / ЧАСТЬ 5: событие авто-обновления роллапа (каждые 30 мин)
--  Требует event_scheduler=ON (уже включён — работает ev_sync_drug_reports_clones).
--  Полностью независимо от синка: refresh_dr_rollup читает мастер напрямую и
--  инкрементально (по idx_dr_updated) пересобирает только изменённые дни.
-- ============================================================================
DROP EVENT IF EXISTS ev_refresh_dr_rollup;
CREATE EVENT ev_refresh_dr_rollup
  ON SCHEDULE EVERY 30 MINUTE
  STARTS (CURRENT_TIMESTAMP + INTERVAL 5 MINUTE)
  ON COMPLETION PRESERVE
  ENABLE
  DO CALL refresh_dr_rollup();

-- ############################################################################
-- #  ОТКАТ: DROP EVENT ev_refresh_dr_rollup; восстановить оригиналы из *Fact
-- #  (getCalcCounts/getPeriodDataList/getCommonPerPrice/getCommPrice <- *Fact);
-- #  DROP PROCEDURE refresh_dr_rollup; DROP TABLE dr_rollup, dr_rollup_state;
-- #  ALTER TABLE drug_reports DROP INDEX idx_dr_updated;  -- по желанию
-- #  ПЛЮС в коде: FilterController::getPeriodCommonPrice получил Redis-кэш (git).
-- ############################################################################
