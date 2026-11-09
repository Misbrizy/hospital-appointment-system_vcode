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
