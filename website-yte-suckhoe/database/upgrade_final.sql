-- Chọn database của ứng dụng trước khi import. Sao lưu trước khi nâng cấp.
-- Chạy trong database healthconnect_moi hiện có. Không xóa dữ liệu.


CREATE TABLE IF NOT EXISTS login_attempts (
 attempt_key CHAR(64) PRIMARY KEY, failures INT NOT NULL DEFAULT 0,
 last_attempt DATETIME NOT NULL, locked_until DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS password_resets (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL UNIQUE,
 expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chạy trong phpMyAdmin: chọn database healthconnect_moi -> tab SQL -> dán -> Go

CREATE TABLE IF NOT EXISTS appointments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  patient_name VARCHAR(120) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  specialty VARCHAR(80) NOT NULL,
  appt_date DATE NOT NULL,
  appt_time TIME NOT NULL,
  reason VARCHAR(1000) NOT NULL DEFAULT '',
  status VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending | confirmed | done | cancelled | no_show
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_slot (appt_date, appt_time, status),
  KEY idx_user (user_id, appt_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Khóa chung cho các giao dịch đặt/hủy/cập nhật lịch; không chứa dữ liệu cá nhân.
CREATE TABLE IF NOT EXISTS appointment_mutex (
  id TINYINT UNSIGNED PRIMARY KEY
) ENGINE=InnoDB;
INSERT IGNORE INTO appointment_mutex(id) VALUES (1);

CREATE TABLE IF NOT EXISTS doctors (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL, specialty VARCHAR(80) NOT NULL,
 bio VARCHAR(2000) NOT NULL DEFAULT '', room VARCHAR(80) NOT NULL DEFAULT '',
 active TINYINT NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS doctor_schedules (
 doctor_id BIGINT UNSIGNED NOT NULL, weekday TINYINT NOT NULL, slot TIME NOT NULL,
 PRIMARY KEY(doctor_id,weekday,slot),
 FOREIGN KEY(doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS doctor_days_off (
 doctor_id BIGINT UNSIGNED NOT NULL, off_date DATE NOT NULL,
 PRIMARY KEY(doctor_id,off_date), FOREIGN KEY(doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS appointment_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, appointment_id BIGINT UNSIGNED NOT NULL,
 actor_id BIGINT UNSIGNED NULL, status VARCHAR(20) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_appt_events(appointment_id,id),
 FOREIGN KEY(appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS wellness_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
 log_date DATE NOT NULL, sleep_hours DECIMAL(3,1) NOT NULL,
 water_ml INT NOT NULL, exercise_minutes INT NOT NULL, note VARCHAR(1000) NOT NULL DEFAULT '',
 UNIQUE KEY uq_wellness_day(user_id,log_date),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS health_goals (
 user_id BIGINT UNSIGNED PRIMARY KEY, weight_kg DECIMAL(5,1) NOT NULL,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- MySQL 8 / MariaDB: thêm cột một lần, giữ nguyên dữ liệu lịch cũ (doctor_id NULL).
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='appointments' AND COLUMN_NAME='doctor_id')=0,
 'ALTER TABLE appointments ADD COLUMN doctor_id BIGINT UNSIGNED NULL, ADD KEY idx_doctor_slot(doctor_id,appt_date,appt_time)', 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql;
EXECUTE hc_stmt;
DEALLOCATE PREPARE hc_stmt;
