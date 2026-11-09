-- Migration 002: adds Enterprise-plan contact requests, email/WhatsApp fields on patients,
-- and a channel column on sms_logs so it can carry SMS, email or WhatsApp reminders.
-- Safe to run once on an existing database (phpMyAdmin > your database > SQL tab > paste > Go).
-- Already included in database.sql / database/schema.sql for brand-new installs.

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

ALTER TABLE patients
  ADD COLUMN IF NOT EXISTS email VARCHAR(150) NULL AFTER phone,
  ADD COLUMN IF NOT EXISTS whatsapp_phone VARCHAR(20) NULL AFTER email,
  ADD COLUMN IF NOT EXISTS email_opt_in TINYINT(1) NOT NULL DEFAULT 0 AFTER sms_opt_in,
  ADD COLUMN IF NOT EXISTS whatsapp_opt_in TINYINT(1) NOT NULL DEFAULT 0 AFTER email_opt_in;

ALTER TABLE sms_logs
  ADD COLUMN IF NOT EXISTS channel ENUM('sms','email','whatsapp') NOT NULL DEFAULT 'sms' AFTER kind;

-- Widen to_phone so it can also hold an email address (reused as a generic "to" field).
ALTER TABLE sms_logs MODIFY COLUMN to_phone VARCHAR(150) NOT NULL;
