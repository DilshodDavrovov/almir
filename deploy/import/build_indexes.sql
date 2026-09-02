-- Post-load index build for fact tables: all original indexes MINUS Tier A "dead" ones
-- (equivalent end state to: full dump import + database/perf/drop_dead_indexes.sql Tier A).
-- idx_dr_updated on drug_reports is added here too (p2_rollup_migration.sql adds it if missing).
SET SESSION foreign_key_checks = 0;
SET SESSION unique_checks = 0;

SELECT NOW() AS started_drug_reports_s;
ALTER TABLE drug_reports_s
  ADD INDEX idx_counterparty_id (counterparty_id),
  ADD INDEX idx_created_at (created_at),
  ADD INDEX idx_drug_id (drug_id),
  ADD INDEX idx_m40d_id (m40d_id),
  ADD INDEX idx_m70d_id (m70d_id),
  ADD INDEX idx_main_period (mode_40_date, data_type, is_active, is_deleted),
  ADD INDEX idx_mf_id (mf_id),
  ADD INDEX idx_sc_id (sc_id),
  ADD INDEX idx_user_id (user_id),
  ADD INDEX mode_70_date (mode_70_date),
  ADD INDEX quantity (quantity),
  ADD INDEX serial_number (serial_number),
  ADD INDEX shelf_life (shelf_life),
  ADD UNIQUE INDEX period_code (period_code),
  ALGORITHM=INPLACE;

SELECT NOW() AS started_drug_reports_p;
ALTER TABLE drug_reports_p
  ADD INDEX idx_counterparty_id (counterparty_id),
  ADD INDEX idx_created_at (created_at),
  ADD INDEX idx_drug_id (drug_id),
  ADD INDEX idx_m40d_id (m40d_id),
  ADD INDEX idx_m70d_id (m70d_id),
  ADD INDEX idx_main_period (mode_40_date, data_type, is_active, is_deleted),
  ADD INDEX idx_mf_id (mf_id),
  ADD INDEX idx_sc_id (sc_id),
  ADD INDEX idx_user_id (user_id),
  ADD INDEX mode_70_date (mode_70_date),
  ADD INDEX quantity (quantity),
  ADD INDEX serial_number (serial_number),
  ADD INDEX shelf_life (shelf_life),
  ADD UNIQUE INDEX period_code (period_code),
  ALGORITHM=INPLACE;

SELECT NOW() AS started_drug_reports;
ALTER TABLE drug_reports
  ADD INDEX idx_counterparty_id (counterparty_id),
  ADD INDEX idx_created_at (created_at),
  ADD INDEX idx_drug_id (drug_id),
  ADD INDEX idx_m40d_id (m40d_id),
  ADD INDEX idx_m70d_id (m70d_id),
  ADD INDEX idx_main_period (mode_40_date, data_type, is_active, is_deleted),
  ADD INDEX idx_mf_id (mf_id),
  ADD INDEX idx_sc_id (sc_id),
  ADD INDEX idx_user_id (user_id),
  ADD INDEX mode_70_date (mode_70_date),
  ADD INDEX quantity (quantity),
  ADD INDEX serial_number (serial_number),
  ADD INDEX shelf_life (shelf_life),
  ADD UNIQUE INDEX period_code (period_code),
  ADD INDEX idx_dr_updated (updated_at),
  ALGORITHM=INPLACE;

SELECT NOW() AS started_fks;
-- Foreign keys of drug_reports, verbatim from the dump (validation skipped: data comes from the same DB)
ALTER TABLE drug_reports ADD CONSTRAINT fk_dr_drug_id FOREIGN KEY (drug_id) REFERENCES drugs (id) ON UPDATE CASCADE;
ALTER TABLE drug_reports ADD CONSTRAINT fk_dr_m40d_id FOREIGN KEY (m40d_id) REFERENCES distributors (id) ON UPDATE CASCADE;
ALTER TABLE drug_reports ADD CONSTRAINT fk_dr_m70d_id FOREIGN KEY (m70d_id) REFERENCES distributors (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE drug_reports ADD CONSTRAINT fk_dr_mf_id FOREIGN KEY (mf_id) REFERENCES manufacturers (id) ON UPDATE CASCADE;
ALTER TABLE drug_reports ADD CONSTRAINT fk_dr_sc_id FOREIGN KEY (sc_id) REFERENCES companies (id) ON UPDATE CASCADE;
ALTER TABLE drug_reports ADD CONSTRAINT fk_dr_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE;

SELECT NOW() AS started_analyze;
ANALYZE TABLE drug_reports_s;
ANALYZE TABLE drug_reports_p;
ANALYZE TABLE drug_reports;
SELECT table_name, table_rows, ROUND(data_length/1024/1024) AS data_mb, ROUND(index_length/1024/1024) AS index_mb
FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('drug_reports','drug_reports_s','drug_reports_p');
SELECT NOW() AS finished;
