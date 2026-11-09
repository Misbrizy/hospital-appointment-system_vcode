-- Gahunda: Hospital Appointment & Patient Reminder Management System
-- MySQL 5.7+/8 or MariaDB 10.3+. Import with phpMyAdmin or `php bin/install.php`.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS plans (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(60) NOT NULL,
  max_patients INT UNSIGNED NULL,            -- NULL = unlimited
  max_sms_month INT UNSIGNED NULL,           -- NULL = unlimited
  max_branches INT UNSIGNED NOT NULL DEFAULT 1,
  price_month INT UNSIGNED NOT NULL DEFAULT 0,
  price_year INT UNSIGNED NOT NULL DEFAULT 0,
  advanced_reports TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS hospitals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(20) NULL,
  email VARCHAR(150) NULL,
  address VARCHAR(255) NULL,
  district VARCHAR(80) NULL,
  plan_id INT UNSIGNED NOT NULL,
  status ENUM('active','suspended') NOT NULL DEFAULT 'active',
  is_trial TINYINT(1) NOT NULL DEFAULT 1,
  subscription_ends_at DATE NULL,
  patient_seq INT UNSIGNED NOT NULL DEFAULT 0,
  appt_seq INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (plan_id) REFERENCES plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS hospital_settings (
  hospital_id INT UNSIGNED NOT NULL,
  `key` VARCHAR(60) NOT NULL,
  `value` TEXT NULL,
  PRIMARY KEY (hospital_id, `key`),
  FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS departments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hospital_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_dept (hospital_id, name),
  FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hospital_id INT UNSIGNED NULL,              -- NULL only for super_admin
  role ENUM('super_admin','hospital_admin','doctor','receptionist','nurse','patient') NOT NULL,
  department_id INT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NULL,
  phone VARCHAR(20) NULL,
  specialty VARCHAR(100) NULL,
  password_hash VARCHAR(255) NOT NULL,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  totp_secret TEXT NULL,                      -- encrypted
  totp_enabled TINYINT(1) NOT NULL DEFAULT 0,
  last_login_at DATETIME NULL,
  last_login_ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_email (email),
  UNIQUE KEY uq_phone (phone),
  KEY idx_hosp_role (hospital_id, role),
  FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS patients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hospital_id INT UNSIGNED NOT NULL,
  patient_no VARCHAR(20) NOT NULL,
  user_id INT UNSIGNED NULL,                  -- portal account, if any
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  dob DATE NULL,
  gender ENUM('female','male','other') NOT NULL DEFAULT 'female',
  phone VARCHAR(20) NULL,
  address VARCHAR(255) NULL,
  emergency_name VARCHAR(120) NULL,
  emergency_phone VARCHAR(20) NULL,
  blood_group VARCHAR(5) NULL,
  insurance_provider VARCHAR(80) NULL,
  insurance_number VARCHAR(60) NULL,
  preferred_language ENUM('rw','en') NOT NULL DEFAULT 'rw',
  sms_opt_in TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  deleted_at DATETIME NULL,
  UNIQUE KEY uq_patient_no (hospital_id, patient_no),
  KEY idx_name (hospital_id, last_name, first_name),
  KEY idx_phone (hospital_id, phone),
  FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS appointments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hospital_id INT UNSIGNED NOT NULL,
  appt_no VARCHAR(20) NOT NULL,
  patient_id INT UNSIGNED NOT NULL,
  department_id INT UNSIGNED NULL,
  doctor_id INT UNSIGNED NULL,
  appt_type VARCHAR(80) NOT NULL,
  appt_date DATE NOT NULL,
  appt_time TIME NOT NULL,
  status ENUM('scheduled','confirmed','checked_in','in_consultation','completed','cancelled','missed') NOT NULL DEFAULT 'scheduled',
  notes VARCHAR(500) NULL,                    -- scheduling notes only, never clinical detail
  cancel_reason VARCHAR(255) NULL,
  reminder_rule_ids VARCHAR(255) NULL,        -- CSV of reminder_rules chosen for this appointment
  queue_no INT UNSIGNED NULL,
  checked_in_at DATETIME NULL,
  consult_started_at DATETIME NULL,
  completed_at DATETIME NULL,
  anc_visit_id INT UNSIGNED NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_appt_no (hospital_id, appt_no),
  KEY idx_date (hospital_id, appt_date, appt_time),
  KEY idx_patient (hospital_id, patient_id),
  KEY idx_doctor (hospital_id, doctor_id, appt_date),
  FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sms_templates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hospital_id INT UNSIGNED NOT NULL,
  code VARCHAR(40) NOT NULL,
  language ENUM('rw','en') NOT NULL,
  name VARCHAR(80) NOT NULL,
  body TEXT NOT NULL,
  UNIQUE KEY uq_tpl (hospital_id, code, language),
  FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reminder_rules (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hospital_id INT UNSIGNED NOT NULL,
  label VARCHAR(80) NOT NULL,
  days_before TINYINT UNSIGNED NOT NULL,      -- 0 = on the day of the appointment
  send_time TIME NOT NULL DEFAULT '08:00:00',
  template_code VARCHAR(40) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sms_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hospital_id INT UNSIGNED NOT NULL,
  patient_id INT UNSIGNED NULL,
  appointment_id INT UNSIGNED NULL,
  kind VARCHAR(40) NOT NULL,
  to_phone VARCHAR(20) NOT NULL,
  body TEXT NOT NULL,
  status ENUM('pending','sending','sent','delivered','failed','cancelled') NOT NULL DEFAULT 'pending',
  scheduled_at DATETIME NOT NULL,
  sent_at DATETIME NULL,
  delivered_at DATETIME NULL,
  provider VARCHAR(30) NULL,
  provider_message_id VARCHAR(80) NULL,
  error VARCHAR(255) NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  KEY idx_queue (status, scheduled_at),
  KEY idx_hosp (hospital_id, created_at),
  KEY idx_provider (provider_message_id),
  KEY idx_appt (appointment_id),
  FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hospital_id INT UNSIGNED NULL,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  body VARCHAR(500) NULL,
  link VARCHAR(255) NULL,
  read_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  KEY idx_user (user_id, read_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pregnancy_records (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hospital_id INT UNSIGNED NOT NULL,
  patient_id INT UNSIGNED NOT NULL,
  lmp_date DATE NOT NULL,
  edd_date DATE NOT NULL,
  previous_pregnancies TINYINT UNSIGNED NOT NULL DEFAULT 0,
  risk_level ENUM('low','moderate','high') NOT NULL DEFAULT 'low',   -- set by a clinician, never by the system
  status ENUM('active','delivered','closed') NOT NULL DEFAULT 'active',
  notes_enc MEDIUMTEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  KEY idx_hosp (hospital_id, status),
  FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS anc_visits (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hospital_id INT UNSIGNED NOT NULL,
  pregnancy_id INT UNSIGNED NOT NULL,
  visit_no TINYINT UNSIGNED NOT NULL,
  planned_week TINYINT UNSIGNED NOT NULL,
  planned_date DATE NOT NULL,
  appointment_id INT UNSIGNED NULL,
  status ENUM('planned','booked','attended','missed','cancelled') NOT NULL DEFAULT 'planned',
  UNIQUE KEY uq_visit (pregnancy_id, visit_no),
  KEY idx_hosp_date (hospital_id, planned_date),
  FOREIGN KEY (pregnancy_id) REFERENCES pregnancy_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS medical_records (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hospital_id INT UNSIGNED NOT NULL,
  patient_id INT UNSIGNED NOT NULL,
  appointment_id INT UNSIGNED NULL,
  doctor_id INT UNSIGNED NULL,
  notes_enc MEDIUMTEXT NOT NULL,              -- encrypted at rest
  follow_up_date DATE NULL,
  created_at DATETIME NOT NULL,
  KEY idx_patient (hospital_id, patient_id),
  FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reschedule_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hospital_id INT UNSIGNED NOT NULL,
  appointment_id INT UNSIGNED NOT NULL,
  patient_id INT UNSIGNED NOT NULL,
  requested_date DATE NOT NULL,
  requested_time TIME NULL,
  reason VARCHAR(255) NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  reviewed_by INT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  response_note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  KEY idx_hosp_status (hospital_id, status),
  FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
  FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hospital_id INT UNSIGNED NULL,
  user_id INT UNSIGNED NULL,
  user_name VARCHAR(120) NULL,
  action VARCHAR(60) NOT NULL,
  entity VARCHAR(40) NULL,
  entity_id BIGINT UNSIGNED NULL,
  patient_id INT UNSIGNED NULL,
  details TEXT NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  KEY idx_hosp (hospital_id, created_at),
  KEY idx_patient (patient_id),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rate_limits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bucket VARCHAR(190) NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_bucket (bucket, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  KEY idx_token (token_hash),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hospital_id INT UNSIGNED NOT NULL,
  plan_id INT UNSIGNED NOT NULL,
  period ENUM('monthly','yearly') NOT NULL,
  amount INT UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'RWF',
  method ENUM('mtn_momo','airtel_money','bank','cash') NOT NULL,
  reference VARCHAR(80) NULL,
  status ENUM('pending','confirmed','rejected') NOT NULL DEFAULT 'pending',
  submitted_by INT UNSIGNED NULL,
  confirmed_by INT UNSIGNED NULL,
  confirmed_at DATETIME NULL,
  period_start DATE NULL,
  period_end DATE NULL,
  created_at DATETIME NOT NULL,
  KEY idx_hosp (hospital_id, status),
  FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
  FOREIGN KEY (plan_id) REFERENCES plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS demo_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  hospital VARCHAR(150) NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(150) NULL,
  message VARCHAR(1000) NULL,
  ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- Subscription plans. Prices are placeholders in RWF: change them in the Plans page after install.
INSERT INTO plans (code, name, max_patients, max_sms_month, max_branches, price_month, price_year, advanced_reports, sort_order) VALUES
 ('basic', 'Basic', 500, 1000, 1, 25000, 250000, 0, 1),
 ('professional', 'Professional', 5000, 10000, 1, 75000, 750000, 1, 2),
 ('enterprise', 'Enterprise', NULL, NULL, 10, 0, 0, 1, 3)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Ready-made super-admin login for first access: admin@example.com / Admin@2026
-- (Change this password from "My account" after your first sign-in.)
INSERT IGNORE INTO users (hospital_id, role, name, email, phone, password_hash, is_active, created_at)
VALUES (NULL, 'super_admin', 'Super Admin', 'admin@example.com', '0780000000',
        '$argon2id$v=19$m=65536,t=4,p=1$MWpaenlRbmRBRjVvQzFXag$3mPGAHhYyvqswA7dQlDUo9yN586W3eD0qmaQ1TwASZg', 1, NOW());

-- Enterprise plan contact requests (plan has no fixed price, so it's a message, not a payment).
CREATE TABLE IF NOT EXISTS enterprise_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hospital_id INT UNSIGNED NOT NULL,
  requested_by INT UNSIGNED NULL,
  message VARCHAR(1000) NULL,
  phone VARCHAR(20) NULL,
  status ENUM('new','contacted','closed') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL,
  FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Email / WhatsApp reminder support (additive).
ALTER TABLE patients
  ADD COLUMN IF NOT EXISTS email VARCHAR(150) NULL AFTER phone,
  ADD COLUMN IF NOT EXISTS whatsapp_phone VARCHAR(20) NULL AFTER email,
  ADD COLUMN IF NOT EXISTS email_opt_in TINYINT(1) NOT NULL DEFAULT 0 AFTER sms_opt_in,
  ADD COLUMN IF NOT EXISTS whatsapp_opt_in TINYINT(1) NOT NULL DEFAULT 0 AFTER email_opt_in;

ALTER TABLE sms_logs
  ADD COLUMN IF NOT EXISTS channel ENUM('sms','email','whatsapp') NOT NULL DEFAULT 'sms' AFTER kind;

-- Widen to_phone so it can also hold an email address (reused as a generic "to" field).
ALTER TABLE sms_logs MODIFY COLUMN to_phone VARCHAR(150) NOT NULL;
-- Migration 003: Finance module (invoices, payments against them, expenses) and a new
-- 'finance' staff role. Safe to run once on an existing database (phpMyAdmin > SQL tab).

ALTER TABLE users MODIFY COLUMN role ENUM('super_admin','hospital_admin','doctor','receptionist','nurse','patient','finance') NOT NULL;

CREATE TABLE IF NOT EXISTS invoices (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hospital_id INT UNSIGNED NOT NULL,
  patient_id INT UNSIGNED NOT NULL,
  appointment_id INT UNSIGNED NULL,
  invoice_no VARCHAR(20) NOT NULL,
  description VARCHAR(255) NOT NULL,
  amount INT UNSIGNED NOT NULL,
  amount_paid INT UNSIGNED NOT NULL DEFAULT 0,
  currency CHAR(3) NOT NULL DEFAULT 'RWF',
  status ENUM('unpaid','partial','paid','cancelled') NOT NULL DEFAULT 'unpaid',
  due_date DATE NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_invoice_no (hospital_id, invoice_no),
  KEY idx_hosp_status (hospital_id, status),
  KEY idx_patient (patient_id),
  FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoice_payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT UNSIGNED NOT NULL,
  amount INT UNSIGNED NOT NULL,
  method ENUM('cash','mtn_momo','airtel_money','bank','insurance') NOT NULL,
  reference VARCHAR(80) NULL,
  recorded_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expenses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hospital_id INT UNSIGNED NOT NULL,
  category VARCHAR(80) NOT NULL,
  description VARCHAR(255) NULL,
  amount INT UNSIGNED NOT NULL,
  expense_date DATE NOT NULL,
  recorded_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  KEY idx_hosp_date (hospital_id, expense_date),
  FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Finance staff also need next_seq for invoice numbers, reusing the hospitals counters pattern.
ALTER TABLE hospitals ADD COLUMN IF NOT EXISTS invoice_seq INT UNSIGNED NOT NULL DEFAULT 0;

-- ===== Migration 004 (included here for fresh installs) =====
-- 1. Reminder delivery
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
