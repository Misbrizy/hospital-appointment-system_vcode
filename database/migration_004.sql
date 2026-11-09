-- Migration 004: reliable reminder delivery (retries + attempt history), structured consultation
-- records, invoice line items / tax, and appointment-linked notifications.
--
-- Additive and idempotent: nothing is dropped or rewritten, existing rows keep their data, and it is
-- safe to run more than once. Run with `php bin/migrate.php` (recommended) or paste into
-- phpMyAdmin > your database > SQL. Requires MariaDB 10.3+ (bundled with XAMPP).

-- ---------------------------------------------------------------------------------------------
-- 1. Reminder delivery: retry scheduling, attempt history
-- ---------------------------------------------------------------------------------------------
ALTER TABLE sms_logs
  ADD COLUMN IF NOT EXISTS reminder_rule_id INT UNSIGNED NULL AFTER appointment_id,
  ADD COLUMN IF NOT EXISTS next_attempt_at DATETIME NULL AFTER scheduled_at,
  ADD COLUMN IF NOT EXISTS last_attempt_at DATETIME NULL AFTER next_attempt_at,
  ADD COLUMN IF NOT EXISTS sending_started_at DATETIME NULL AFTER last_attempt_at;

CREATE INDEX IF NOT EXISTS idx_retry ON sms_logs (status, next_attempt_at);
CREATE INDEX IF NOT EXISTS idx_rule ON sms_logs (appointment_id, reminder_rule_id, channel);

-- One row per delivery attempt: when, on which channel/provider, outcome and the error text.
CREATE TABLE IF NOT EXISTS sms_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sms_log_id BIGINT UNSIGNED NOT NULL,
  attempt_no TINYINT UNSIGNED NOT NULL,
  channel ENUM('sms','email','whatsapp') NOT NULL DEFAULT 'sms',
  provider VARCHAR(30) NULL,
  outcome ENUM('success','retry','failed') NOT NULL,
  provider_message_id VARCHAR(80) NULL,
  error VARCHAR(255) NULL,
  duration_ms INT UNSIGNED NULL,
  attempted_at DATETIME NOT NULL,
  KEY idx_log (sms_log_id, attempt_no),
  FOREIGN KEY (sms_log_id) REFERENCES sms_logs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------------------------
-- 2. Notifications can point at the appointment they are about, so the department shown to a doctor
--    is read live from the appointment/doctor records instead of a text snapshot.
-- ---------------------------------------------------------------------------------------------
ALTER TABLE notifications
  ADD COLUMN IF NOT EXISTS appointment_id INT UNSIGNED NULL AFTER link;

-- Existing appointment notifications already carry the appointment id inside their link; copy it across so
-- they too show the live department. (Only rows whose appointment still exists; nothing is overwritten.)
UPDATE notifications n JOIN appointments a ON a.id = CAST(SUBSTRING_INDEX(n.link, 'id=', -1) AS UNSIGNED)
   SET n.appointment_id = a.id
 WHERE n.appointment_id IS NULL AND n.link LIKE '%page=appointment_edit&id=%';

-- ---------------------------------------------------------------------------------------------
-- 3. Consultation records: the existing medical_records table is extended (not duplicated).
--    Clinical content stays encrypted in notes_enc; these columns are non-sensitive metadata used
--    for listing, numbering and access checks.
-- ---------------------------------------------------------------------------------------------
ALTER TABLE hospitals
  ADD COLUMN IF NOT EXISTS consultation_seq INT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE medical_records
  ADD COLUMN IF NOT EXISTS consultation_no VARCHAR(20) NULL AFTER hospital_id,
  ADD COLUMN IF NOT EXISTS department_id INT UNSIGNED NULL AFTER doctor_id,
  ADD COLUMN IF NOT EXISTS consulted_at DATETIME NULL AFTER department_id,
  ADD COLUMN IF NOT EXISTS addendum_of INT UNSIGNED NULL AFTER consulted_at;

CREATE INDEX IF NOT EXISTS idx_doctor_date ON medical_records (hospital_id, doctor_id, consulted_at);
CREATE INDEX IF NOT EXISTS idx_appt ON medical_records (appointment_id);
CREATE UNIQUE INDEX IF NOT EXISTS uq_consultation_no ON medical_records (hospital_id, consultation_no);

-- Back-fill metadata for consultations that were recorded before this migration (read-only facts that
-- already exist elsewhere in the database).
UPDATE medical_records SET consulted_at = created_at WHERE consulted_at IS NULL;
UPDATE medical_records m JOIN appointments a ON a.id = m.appointment_id
   SET m.department_id = a.department_id WHERE m.department_id IS NULL;

-- ---------------------------------------------------------------------------------------------
-- 4. Invoices: line items, tax and extra charges. `amount` keeps meaning "grand total", so every
--    existing screen, payment and report continues to work unchanged.
-- ---------------------------------------------------------------------------------------------
ALTER TABLE invoices
  ADD COLUMN IF NOT EXISTS subtotal INT UNSIGNED NOT NULL DEFAULT 0 AFTER description,
  ADD COLUMN IF NOT EXISTS tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER subtotal,
  ADD COLUMN IF NOT EXISTS tax_amount INT UNSIGNED NOT NULL DEFAULT 0 AFTER tax_rate,
  ADD COLUMN IF NOT EXISTS other_charges INT UNSIGNED NOT NULL DEFAULT 0 AFTER tax_amount,
  ADD COLUMN IF NOT EXISTS other_charges_label VARCHAR(80) NULL AFTER other_charges;

CREATE TABLE IF NOT EXISTS invoice_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT UNSIGNED NOT NULL,
  line_no TINYINT UNSIGNED NOT NULL DEFAULT 1,
  description VARCHAR(255) NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  unit_price INT UNSIGNED NOT NULL DEFAULT 0,
  line_total INT UNSIGNED NOT NULL DEFAULT 0,
  KEY idx_invoice (invoice_id, line_no),
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Existing invoices were a single amount: their subtotal is that amount (no tax was ever charged).
UPDATE invoices SET subtotal = amount WHERE subtotal = 0 AND tax_amount = 0 AND other_charges = 0;
