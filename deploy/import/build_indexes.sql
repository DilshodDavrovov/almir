-- Post-load index build, batched and resumable.
-- Same end state as the original build_indexes.sql (dump indexes minus the Tier A dead ones,
-- plus idx_dr_updated), but only a few indexes per ALTER and every step is skipped when the
-- index already exists - so it can be re-run after an interruption and keeps mysqld's peak
-- memory low on this shared host.
SET SESSION foreign_key_checks = 0;
SET SESSION unique_checks = 0;

SELECT NOW() AS `drug_reports_s batch 1`;
SET @todo := (SELECT SUM(index_name='idx_counterparty_id')=0 AND SUM(index_name='idx_created_at')=0 AND SUM(index_name='idx_drug_id')=0 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='drug_reports_s');
SET @sql := IF(@todo, "ALTER TABLE drug_reports_s ADD INDEX idx_counterparty_id (counterparty_id), ADD INDEX idx_created_at (created_at), ADD INDEX idx_drug_id (drug_id), ALGORITHM=INPLACE", 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SELECT NOW() AS `drug_reports_s batch 2`;
SET @todo := (SELECT SUM(index_name='idx_m40d_id')=0 AND SUM(index_name='idx_m70d_id')=0 AND SUM(index_name='idx_main_period')=0 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='drug_reports_s');
SET @sql := IF(@todo, "ALTER TABLE drug_reports_s ADD INDEX idx_m40d_id (m40d_id), ADD INDEX idx_m70d_id (m70d_id), ADD INDEX idx_main_period (mode_40_date, data_type, is_active, is_deleted), ALGORITHM=INPLACE", 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SELECT NOW() AS `drug_reports_s batch 3`;
SET @todo := (SELECT SUM(index_name='idx_mf_id')=0 AND SUM(index_name='idx_sc_id')=0 AND SUM(index_name='idx_user_id')=0 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='drug_reports_s');
SET @sql := IF(@todo, "ALTER TABLE drug_reports_s ADD INDEX idx_mf_id (mf_id), ADD INDEX idx_sc_id (sc_id), ADD INDEX idx_user_id (user_id), ALGORITHM=INPLACE", 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SELECT NOW() AS `drug_reports_s batch 4`;
SET @todo := (SELECT SUM(index_name='mode_70_date')=0 AND SUM(index_name='quantity')=0 AND SUM(index_name='serial_number')=0 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='drug_reports_s');
SET @sql := IF(@todo, "ALTER TABLE drug_reports_s ADD INDEX mode_70_date (mode_70_date), ADD INDEX quantity (quantity), ADD INDEX serial_number (serial_number), ALGORITHM=INPLACE", 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SELECT NOW() AS `drug_reports_s batch 5`;
SET @todo := (SELECT SUM(index_name='shelf_life')=0 AND SUM(index_name='period_code')=0 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='drug_reports_s');
SET @sql := IF(@todo, "ALTER TABLE drug_reports_s ADD INDEX shelf_life (shelf_life), ADD UNIQUE INDEX period_code (period_code), ALGORITHM=INPLACE", 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SELECT NOW() AS `drug_reports_p batch 1`;
SET @todo := (SELECT SUM(index_name='idx_counterparty_id')=0 AND SUM(index_name='idx_created_at')=0 AND SUM(index_name='idx_drug_id')=0 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='drug_reports_p');
SET @sql := IF(@todo, "ALTER TABLE drug_reports_p ADD INDEX idx_counterparty_id (counterparty_id), ADD INDEX idx_created_at (created_at), ADD INDEX idx_drug_id (drug_id), ALGORITHM=INPLACE", 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SELECT NOW() AS `drug_reports_p batch 2`;
SET @todo := (SELECT SUM(index_name='idx_m40d_id')=0 AND SUM(index_name='idx_m70d_id')=0 AND SUM(index_name='idx_main_period')=0 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='drug_reports_p');
SET @sql := IF(@todo, "ALTER TABLE drug_reports_p ADD INDEX idx_m40d_id (m40d_id), ADD INDEX idx_m70d_id (m70d_id), ADD INDEX idx_main_period (mode_40_date, data_type, is_active, is_deleted), ALGORITHM=INPLACE", 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SELECT NOW() AS `drug_reports_p batch 3`;
SET @todo := (SELECT SUM(index_name='idx_mf_id')=0 AND SUM(index_name='idx_sc_id')=0 AND SUM(index_name='idx_user_id')=0 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='drug_reports_p');
SET @sql := IF(@todo, "ALTER TABLE drug_reports_p ADD INDEX idx_mf_id (mf_id), ADD INDEX idx_sc_id (sc_id), ADD INDEX idx_user_id (user_id), ALGORITHM=INPLACE", 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SELECT NOW() AS `drug_reports_p batch 4`;
SET @todo := (SELECT SUM(index_name='mode_70_date')=0 AND SUM(index_name='quantity')=0 AND SUM(index_name='serial_number')=0 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='drug_reports_p');
SET @sql := IF(@todo, "ALTER TABLE drug_reports_p ADD INDEX mode_70_date (mode_70_date), ADD INDEX quantity (quantity), ADD INDEX serial_number (serial_number), ALGORITHM=INPLACE", 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SELECT NOW() AS `drug_reports_p batch 5`;
SET @todo := (SELECT SUM(index_name='shelf_life')=0 AND SUM(index_name='period_code')=0 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='drug_reports_p');
SET @sql := IF(@todo, "ALTER TABLE drug_reports_p ADD INDEX shelf_life (shelf_life), ADD UNIQUE INDEX period_code (period_code), ALGORITHM=INPLACE", 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SELECT NOW() AS `drug_reports batch 1`;
SET @todo := (SELECT SUM(index_name='idx_counterparty_id')=0 AND SUM(index_name='idx_created_at')=0 AND SUM(index_name='idx_drug_id')=0 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='drug_reports');
SET @sql := IF(@todo, "ALTER TABLE drug_reports ADD INDEX idx_counterparty_id (counterparty_id), ADD INDEX idx_created_at (created_at), ADD INDEX idx_drug_id (drug_id), ALGORITHM=INPLACE", 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SELECT NOW() AS `drug_reports batch 2`;
SET @todo := (SELECT SUM(index_name='idx_m40d_id')=0 AND SUM(index_name='idx_m70d_id')=0 AND SUM(index_name='idx_main_period')=0 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='drug_reports');
SET @sql := IF(@todo, "ALTER TABLE drug_reports ADD INDEX idx_m40d_id (m40d_id), ADD INDEX idx_m70d_id (m70d_id), ADD INDEX idx_main_period (mode_40_date, data_type, is_active, is_deleted), ALGORITHM=INPLACE", 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SELECT NOW() AS `drug_reports batch 3`;
SET @todo := (SELECT SUM(index_name='idx_mf_id')=0 AND SUM(index_name='idx_sc_id')=0 AND SUM(index_name='idx_user_id')=0 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='drug_reports');
SET @sql := IF(@todo, "ALTER TABLE drug_reports ADD INDEX idx_mf_id (mf_id), ADD INDEX idx_sc_id (sc_id), ADD INDEX idx_user_id (user_id), ALGORITHM=INPLACE", 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SELECT NOW() AS `drug_reports batch 4`;
SET @todo := (SELECT SUM(index_name='mode_70_date')=0 AND SUM(index_name='quantity')=0 AND SUM(index_name='serial_number')=0 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='drug_reports');
SET @sql := IF(@todo, "ALTER TABLE drug_reports ADD INDEX mode_70_date (mode_70_date), ADD INDEX quantity (quantity), ADD INDEX serial_number (serial_number), ALGORITHM=INPLACE", 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SELECT NOW() AS `drug_reports batch 5`;
SET @todo := (SELECT SUM(index_name='shelf_life')=0 AND SUM(index_name='period_code')=0 AND SUM(index_name='idx_dr_updated')=0 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='drug_reports');
SET @sql := IF(@todo, "ALTER TABLE drug_reports ADD INDEX shelf_life (shelf_life), ADD UNIQUE INDEX period_code (period_code), ADD INDEX idx_dr_updated (updated_at), ALGORITHM=INPLACE", 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SELECT NOW() AS `foreign keys`;
SET @todo := (SELECT COUNT(*)=0 FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='drug_reports' AND constraint_name='fk_dr_drug_id');
SET @sql := IF(@todo, 'ALTER TABLE drug_reports ADD CONSTRAINT fk_dr_drug_id FOREIGN KEY (drug_id) REFERENCES drugs (id) ON UPDATE CASCADE', 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
SET @todo := (SELECT COUNT(*)=0 FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='drug_reports' AND constraint_name='fk_dr_m40d_id');
SET @sql := IF(@todo, 'ALTER TABLE drug_reports ADD CONSTRAINT fk_dr_m40d_id FOREIGN KEY (m40d_id) REFERENCES distributors (id) ON UPDATE CASCADE', 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
SET @todo := (SELECT COUNT(*)=0 FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='drug_reports' AND constraint_name='fk_dr_m70d_id');
SET @sql := IF(@todo, 'ALTER TABLE drug_reports ADD CONSTRAINT fk_dr_m70d_id FOREIGN KEY (m70d_id) REFERENCES distributors (id) ON DELETE SET NULL ON UPDATE CASCADE', 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
SET @todo := (SELECT COUNT(*)=0 FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='drug_reports' AND constraint_name='fk_dr_mf_id');
SET @sql := IF(@todo, 'ALTER TABLE drug_reports ADD CONSTRAINT fk_dr_mf_id FOREIGN KEY (mf_id) REFERENCES manufacturers (id) ON UPDATE CASCADE', 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
SET @todo := (SELECT COUNT(*)=0 FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='drug_reports' AND constraint_name='fk_dr_sc_id');
SET @sql := IF(@todo, 'ALTER TABLE drug_reports ADD CONSTRAINT fk_dr_sc_id FOREIGN KEY (sc_id) REFERENCES companies (id) ON UPDATE CASCADE', 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
SET @todo := (SELECT COUNT(*)=0 FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='drug_reports' AND constraint_name='fk_dr_user_id');
SET @sql := IF(@todo, 'ALTER TABLE drug_reports ADD CONSTRAINT fk_dr_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE', 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SELECT NOW() AS `analyze`;
ANALYZE TABLE drug_reports_s;
ANALYZE TABLE drug_reports_p;
ANALYZE TABLE drug_reports;
SELECT table_name, table_rows, ROUND(data_length/1024/1024) AS data_mb, ROUND(index_length/1024/1024) AS index_mb
FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('drug_reports','drug_reports_s','drug_reports_p');
SELECT NOW() AS `finished`;
