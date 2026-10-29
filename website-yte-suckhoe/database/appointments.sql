-- Chạy trong phpMyAdmin: chọn database healthconnect_moi -> tab SQL -> dán -> Go
USE healthconnect_moi;
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
