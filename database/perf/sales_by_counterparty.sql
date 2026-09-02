-- ============================================================================
--  ALMIR · «Сравнительный анализ продаж → по поставщикам» для режима ПРОДАЖА
--
--  Проблема: вкладка «по поставщикам» (byTable='sc') всегда группировала по
--  sc_id + companies. Но в таблице продаж (drug_reports_s) sc_id ПУСТ у всех
--  строк — поставщик там лежит в counterparty_id (справочник contrahens).
--  Поэтому режим «продажа» давал пустой список (или 500 на пустом id).
--
--  Фикс: в ветке byTable='sc' выбираем источник по dataType:
--    dataType=2 (продажа) -> counterparty_id + contrahens
--    иначе    (приход)    -> sc_id + companies      (БЕЗ ИЗМЕНЕНИЙ — приход цел)
--
--  Затрагивает 3 ядровые процедуры: getCalcCounts, getPeriodDataList,
--  getCommonPerPrice. Применять на проде от пользователя с правами на процедуры.
--  (Детальные колонки — getQtyData/getDrugNames/getTotal* — пока не тронуты:
--   для продаж-по-поставщику они вернут пусто/0, но не падают. Доделываются
--   отдельным патчем при необходимости.)
-- ============================================================================

DELIMITER ;;
DROP PROCEDURE IF EXISTS getCalcCounts ;;
CREATE PROCEDURE `getCalcCounts`(IN fDate varchar(20),
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
DROP PROCEDURE IF EXISTS getCommonPerPrice ;;
CREATE PROCEDURE `getCommonPerPrice`(IN fDate varchar(20),
IN tDate varchar(20),
IN dataID int,
IN isActive tinyint,
IN isDeleted tinyint,
IN byTable varchar(20),
IN typeIdList varchar(255),
IN dataType tinyint,
IN regionID varchar(255),
IN districtID varchar(255))
    COMMENT 'TotalCommonPerPrice'
BEGIN
  DECLARE qWhere longtext DEFAULT '';
  DECLARE gBy varchar(64) DEFAULT '';
  DECLARE lJoin longtext DEFAULT '';

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

  /* base WHERE (data_type only if master) */
  SET qWhere = '1=1';

  IF needsDataTypeFilter = 1 THEN
    SET qWhere = CONCAT(qWhere, ' AND dr.data_type = ', dataType);
  END IF;

  /* region/district filters (fixed logic) */
  IF (regionID IS NOT NULL
    AND regionID <> '') THEN
    SET qWhere = CONCAT(qWhere, ' AND dr.region_id IN (', regionID, ')');
  END IF;

  IF (districtID IS NOT NULL
    AND districtID <> '') THEN
    SET qWhere = CONCAT(qWhere, ' AND dr.district_id IN (', districtID, ')');
  END IF;

  /* byTable branches */
  IF (byTable = 'dist') THEN
    SET gBy = 'dr.m40d_id';
    SET qWhere = CONCAT(qWhere, ' AND dr.m40d_id = ', dataID);

    IF (typeIdList IS NOT NULL
      AND typeIdList <> '') THEN
      SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs d ON dr.drug_id = d.id ');
      SET qWhere = CONCAT(qWhere, ' AND d.dt_id IN (', typeIdList, ')');
    END IF;

  ELSEIF (byTable = 'sc') THEN
    /* продажа (dataType=2): поставщик = counterparty_id (contrahens); приход: sc_id (companies) */
    IF dataType = 2 THEN
      SET gBy = 'dr.counterparty_id';
      SET qWhere = CONCAT(qWhere, ' AND dr.counterparty_id = ', dataID);
    ELSE
      SET gBy = 'dr.sc_id';
      SET qWhere = CONCAT(qWhere, ' AND dr.sc_id = ', dataID);
    END IF;

    IF (typeIdList IS NOT NULL
      AND typeIdList <> '') THEN
      SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs d ON dr.drug_id = d.id ');
      SET qWhere = CONCAT(qWhere, ' AND d.dt_id IN (', typeIdList, ')');
    END IF;

  ELSEIF (byTable = 'mf') THEN
    SET gBy = 'dr.mf_id';
    SET qWhere = CONCAT(qWhere, ' AND dr.mf_id = ', dataID);

    IF (typeIdList IS NOT NULL
      AND typeIdList <> '') THEN
      SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs d ON dr.drug_id = d.id ');
      SET qWhere = CONCAT(qWhere, ' AND d.dt_id IN (', typeIdList, ')');
    END IF;

  ELSEIF (byTable = 'inn') THEN
    SET gBy = 'di.id';
    SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs d ON dr.drug_id = d.id LEFT JOIN drug_inns di ON d.di_id = di.id ');
    SET qWhere = CONCAT(qWhere, ' AND di.id = ', dataID);

    IF (typeIdList IS NOT NULL
      AND typeIdList <> '') THEN
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

    IF (typeIdList IS NOT NULL
      AND typeIdList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND d.dt_id IN (', typeIdList, ')');
    END IF;

  ELSEIF (byTable = 'dfg') THEN
    SET gBy = 'dfg.id';
    SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs d ON dr.drug_id = d.id LEFT JOIN drug_farm_groups dfg ON d.dfg_id = dfg.id ');
    SET qWhere = CONCAT(qWhere, ' AND dfg.id = ', dataID);

    IF (typeIdList IS NOT NULL
      AND typeIdList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND d.dt_id IN (', typeIdList, ')');
    END IF;

  ELSEIF (byTable = 'dtg') THEN
    SET gBy = 'dtg.id';
    SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs d ON dr.drug_id = d.id LEFT JOIN drug_ts_groups dtg ON d.dtg_id = dtg.id ');
    SET qWhere = CONCAT(qWhere, ' AND dtg.id = ', dataID);

    IF (typeIdList IS NOT NULL
      AND typeIdList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND d.dt_id IN (', typeIdList, ')');
    END IF;

  ELSEIF (byTable = 'trademark') THEN
    SET gBy = 't.id';
    SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs d ON dr.drug_id = d.id LEFT JOIN trademarks t ON d.trademark_id = t.id ');
    SET qWhere = CONCAT(qWhere, ' AND t.id = ', dataID);

    IF (typeIdList IS NOT NULL
      AND typeIdList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND d.dt_id IN (', typeIdList, ')');
    END IF;

  ELSEIF (byTable = 'drugs') THEN
    SET gBy = 'd.id';
    SET lJoin = CONCAT(lJoin, ' LEFT JOIN drugs d ON dr.drug_id = d.id ');
    SET qWhere = CONCAT(qWhere, ' AND d.id = ', dataID);

    IF (typeIdList IS NOT NULL
      AND typeIdList <> '') THEN
      SET qWhere = CONCAT(qWhere, ' AND d.dt_id IN (', typeIdList, ')');
    END IF;
  END IF;

  /* Final query */
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

  PREPARE stmt FROM @q;
  EXECUTE stmt;
  DEALLOCATE PREPARE stmt;
END ;;
DROP PROCEDURE IF EXISTS getPeriodDataList ;;
CREATE PROCEDURE `getPeriodDataList`(IN fDate varchar(20),
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
