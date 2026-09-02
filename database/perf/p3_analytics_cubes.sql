-- ############################################################################
-- #  P3: МЕСЯЧНЫЕ КУБЫ ДЛЯ РАЗДЕЛОВ «АНАЛИТИКА» (диаграммы + геоотчёт) и «СВОДНЫЙ ОТЧЁТ (pivot)»
-- #  Аддитивная миграция: импорт/синк/роллап P2 не трогает. Откат — в хвосте файла.
-- #
-- #  Почему два куба: полная гранулярность (месяц × регион × район × дистрибьютор ×
-- #  контрагент × производитель × препарат) даёт ~16,6 млн строк — почти как сырые
-- #  факты, ускорения нет. Поэтому:
-- #    dr_cube_l1 — (тип, месяц, регион, дистрибьютор, производитель, препарат)   ~2,2 млн строк
-- #                 закрывает все «товарные» разрезы: препарат, МНН, форма, группы,
-- #                 торговая марка, тип препарата, RX/OTC, производитель, страна.
-- #    dr_cube_l2 — (тип, месяц, тип препарата, регион, район, дистрибьютор, контрагент/поставщик) ~0,3 млн строк
-- #                 закрывает географию и контрагентов (геоотчёт, топы по регионам/районам).
-- #  Комбинации «контрагент/район × препарат» в кубах нет — API считает их по факт-таблице
-- #  (медленно, с предупреждением в интерфейсе).
-- #
-- #  ym = год*100+месяц (например 202403). party_id: продажа -> counterparty_id (contrahens),
-- #  приход -> sc_id (companies). NULL-ключи хранятся как 0.
-- ############################################################################

-- ===== [1] ТАБЛИЦЫ ==========================================================
CREATE TABLE IF NOT EXISTS dr_cube_l1 (
  data_type  tinyint NOT NULL,
  ym         int NOT NULL,
  region_id  int NOT NULL DEFAULT 0,
  m40d_id    int NOT NULL DEFAULT 0,
  mf_id      int NOT NULL DEFAULT 0,
  drug_id    int NOT NULL,
  is_active  tinyint NOT NULL,
  is_deleted tinyint NOT NULL,
  qty      decimal(24,4) NOT NULL DEFAULT 0,
  sum_usd  decimal(24,4) NOT NULL DEFAULT 0,
  sum_uzs  decimal(24,4) NOT NULL DEFAULT 0,
  sum_eur  decimal(24,4) NOT NULL DEFAULT 0,
  sum_rub  decimal(24,4) NOT NULL DEFAULT 0,
  PRIMARY KEY (data_type, ym, region_id, m40d_id, mf_id, drug_id, is_active, is_deleted),
  KEY idx_l1_drug (drug_id),
  KEY idx_l1_mf (mf_id),
  KEY idx_l1_m40d (m40d_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dr_cube_l2 (
  data_type   tinyint NOT NULL,
  ym          int NOT NULL,
  dt_id       int NOT NULL DEFAULT 0,
  region_id   int NOT NULL DEFAULT 0,
  district_id int NOT NULL DEFAULT 0,
  m40d_id     int NOT NULL DEFAULT 0,
  party_id    int NOT NULL DEFAULT 0,
  is_active   tinyint NOT NULL,
  is_deleted  tinyint NOT NULL,
  qty      decimal(24,4) NOT NULL DEFAULT 0,
  sum_usd  decimal(24,4) NOT NULL DEFAULT 0,
  sum_uzs  decimal(24,4) NOT NULL DEFAULT 0,
  sum_eur  decimal(24,4) NOT NULL DEFAULT 0,
  sum_rub  decimal(24,4) NOT NULL DEFAULT 0,
  PRIMARY KEY (data_type, ym, dt_id, region_id, district_id, m40d_id, party_id, is_active, is_deleted),
  KEY idx_l2_party (party_id),
  KEY idx_l2_region (region_id, district_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dr_cube_state (
  id tinyint NOT NULL PRIMARY KEY,
  last_at datetime NOT NULL
) ENGINE=InnoDB;

-- ===== [2] ПОЛНОЕ НАПОЛНЕНИЕ ИЗ МАСТЕРА (тяжёлая одноразовая операция) =======
-- Водяной знак снимаем ДО наполнения: всё, что импортируется во время наполнения,
-- подхватит первый инкрементальный refresh (он пересобирает затронутые месяцы целиком).
SET @wm := NOW();

TRUNCATE TABLE dr_cube_l1;
INSERT INTO dr_cube_l1 (data_type, ym, region_id, m40d_id, mf_id, drug_id, is_active, is_deleted, qty, sum_usd, sum_uzs, sum_eur, sum_rub)
SELECT dr.data_type,
       YEAR(dr.mode_40_date)*100 + MONTH(dr.mode_40_date),
       IFNULL(dr.region_id,0), IFNULL(dr.m40d_id,0), IFNULL(dr.mf_id,0), dr.drug_id,
       IFNULL(dr.is_active,0), IFNULL(dr.is_deleted,0),
       SUM(dr.quantity), SUM(dr.sum_price_usd), SUM(dr.sum_price_uzs), SUM(dr.sum_price_eur), SUM(dr.sum_price_rub)
FROM drug_reports dr
WHERE dr.mode_40_date IS NOT NULL AND dr.drug_id IS NOT NULL
GROUP BY dr.data_type, YEAR(dr.mode_40_date)*100 + MONTH(dr.mode_40_date),
         IFNULL(dr.region_id,0), IFNULL(dr.m40d_id,0), IFNULL(dr.mf_id,0), dr.drug_id,
         IFNULL(dr.is_active,0), IFNULL(dr.is_deleted,0);

TRUNCATE TABLE dr_cube_l2;
INSERT INTO dr_cube_l2 (data_type, ym, dt_id, region_id, district_id, m40d_id, party_id, is_active, is_deleted, qty, sum_usd, sum_uzs, sum_eur, sum_rub)
SELECT dr.data_type,
       YEAR(dr.mode_40_date)*100 + MONTH(dr.mode_40_date),
       IFNULL(d.dt_id,0), IFNULL(dr.region_id,0), IFNULL(dr.district_id,0), IFNULL(dr.m40d_id,0),
       IF(dr.data_type = 2, IFNULL(dr.counterparty_id,0), IFNULL(dr.sc_id,0)),
       IFNULL(dr.is_active,0), IFNULL(dr.is_deleted,0),
       SUM(dr.quantity), SUM(dr.sum_price_usd), SUM(dr.sum_price_uzs), SUM(dr.sum_price_eur), SUM(dr.sum_price_rub)
FROM drug_reports dr LEFT JOIN drugs d ON d.id = dr.drug_id
WHERE dr.mode_40_date IS NOT NULL
GROUP BY dr.data_type, YEAR(dr.mode_40_date)*100 + MONTH(dr.mode_40_date),
         IFNULL(d.dt_id,0), IFNULL(dr.region_id,0), IFNULL(dr.district_id,0), IFNULL(dr.m40d_id,0),
         IF(dr.data_type = 2, IFNULL(dr.counterparty_id,0), IFNULL(dr.sc_id,0)),
         IFNULL(dr.is_active,0), IFNULL(dr.is_deleted,0);

INSERT INTO dr_cube_state (id, last_at) VALUES (1, @wm)
  ON DUPLICATE KEY UPDATE last_at = VALUES(last_at);

-- ===== [3] ИНКРЕМЕНТАЛЬНОЕ ОБНОВЛЕНИЕ =======================================
-- Ищет строки мастера, изменённые после водяного знака (индекс idx_dr_updated из P2),
-- и пересобирает затронутые (тип, месяц) целиком в обоих кубах. Идемпотентно.
DROP PROCEDURE IF EXISTS refresh_dr_cubes;
DELIMITER ;;
CREATE PROCEDURE refresh_dr_cubes()
BEGIN
  DECLARE v_last datetime; DECLARE v_now datetime;
  SET v_now = NOW();
  SELECT last_at INTO v_last FROM dr_cube_state WHERE id = 1 FOR UPDATE;

  DROP TEMPORARY TABLE IF EXISTS _chg_ym;
  CREATE TEMPORARY TABLE _chg_ym (data_type tinyint NOT NULL, ym int NOT NULL, PRIMARY KEY (data_type, ym));
  INSERT IGNORE INTO _chg_ym (data_type, ym)
    SELECT DISTINCT data_type, YEAR(mode_40_date)*100 + MONTH(mode_40_date)
    FROM drug_reports
    WHERE updated_at > v_last AND updated_at <= v_now AND mode_40_date IS NOT NULL;

  DELETE c FROM dr_cube_l1 c JOIN _chg_ym x ON x.data_type = c.data_type AND x.ym = c.ym;
  DELETE c FROM dr_cube_l2 c JOIN _chg_ym x ON x.data_type = c.data_type AND x.ym = c.ym;

  INSERT INTO dr_cube_l1 (data_type, ym, region_id, m40d_id, mf_id, drug_id, is_active, is_deleted, qty, sum_usd, sum_uzs, sum_eur, sum_rub)
  SELECT dr.data_type, x.ym,
         IFNULL(dr.region_id,0), IFNULL(dr.m40d_id,0), IFNULL(dr.mf_id,0), dr.drug_id,
         IFNULL(dr.is_active,0), IFNULL(dr.is_deleted,0),
         SUM(dr.quantity), SUM(dr.sum_price_usd), SUM(dr.sum_price_uzs), SUM(dr.sum_price_eur), SUM(dr.sum_price_rub)
  FROM _chg_ym x
  JOIN drug_reports dr ON dr.data_type = x.data_type
                      AND dr.mode_40_date >= STR_TO_DATE(CONCAT(x.ym, '01'), '%Y%m%d')
                      AND dr.mode_40_date <  DATE_ADD(STR_TO_DATE(CONCAT(x.ym, '01'), '%Y%m%d'), INTERVAL 1 MONTH)
  WHERE dr.drug_id IS NOT NULL
  GROUP BY dr.data_type, x.ym, IFNULL(dr.region_id,0), IFNULL(dr.m40d_id,0), IFNULL(dr.mf_id,0), dr.drug_id,
           IFNULL(dr.is_active,0), IFNULL(dr.is_deleted,0);

  INSERT INTO dr_cube_l2 (data_type, ym, dt_id, region_id, district_id, m40d_id, party_id, is_active, is_deleted, qty, sum_usd, sum_uzs, sum_eur, sum_rub)
  SELECT dr.data_type, x.ym,
         IFNULL(d.dt_id,0), IFNULL(dr.region_id,0), IFNULL(dr.district_id,0), IFNULL(dr.m40d_id,0),
         IF(dr.data_type = 2, IFNULL(dr.counterparty_id,0), IFNULL(dr.sc_id,0)),
         IFNULL(dr.is_active,0), IFNULL(dr.is_deleted,0),
         SUM(dr.quantity), SUM(dr.sum_price_usd), SUM(dr.sum_price_uzs), SUM(dr.sum_price_eur), SUM(dr.sum_price_rub)
  FROM _chg_ym x
  JOIN drug_reports dr ON dr.data_type = x.data_type
                      AND dr.mode_40_date >= STR_TO_DATE(CONCAT(x.ym, '01'), '%Y%m%d')
                      AND dr.mode_40_date <  DATE_ADD(STR_TO_DATE(CONCAT(x.ym, '01'), '%Y%m%d'), INTERVAL 1 MONTH)
  LEFT JOIN drugs d ON d.id = dr.drug_id
  GROUP BY dr.data_type, x.ym, IFNULL(d.dt_id,0), IFNULL(dr.region_id,0), IFNULL(dr.district_id,0), IFNULL(dr.m40d_id,0),
           IF(dr.data_type = 2, IFNULL(dr.counterparty_id,0), IFNULL(dr.sc_id,0)),
           IFNULL(dr.is_active,0), IFNULL(dr.is_deleted,0);

  UPDATE dr_cube_state SET last_at = v_now WHERE id = 1;
  DROP TEMPORARY TABLE IF EXISTS _chg_ym;
END ;;
DELIMITER ;

-- ===== [4] СОБЫТИЕ АВТО-ОБНОВЛЕНИЯ (каждые 30 минут, со сдвигом от P2) ========
DROP EVENT IF EXISTS ev_refresh_dr_cubes;
CREATE EVENT ev_refresh_dr_cubes
  ON SCHEDULE EVERY 30 MINUTE STARTS (CURRENT_TIMESTAMP + INTERVAL 20 MINUTE)
  ON COMPLETION PRESERVE ENABLE
  DO CALL refresh_dr_cubes();

ANALYZE TABLE dr_cube_l1;
ANALYZE TABLE dr_cube_l2;
SELECT 'dr_cube_l1' AS cube_name, COUNT(*) AS rows_ FROM dr_cube_l1
UNION ALL SELECT 'dr_cube_l2', COUNT(*) FROM dr_cube_l2;

-- ############################################################################
-- #  ОТКАТ: DROP EVENT ev_refresh_dr_cubes; DROP PROCEDURE refresh_dr_cubes;
-- #         DROP TABLE dr_cube_l1, dr_cube_l2, dr_cube_state;
-- ############################################################################
