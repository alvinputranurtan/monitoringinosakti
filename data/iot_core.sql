-- =========================================
-- GLOBAL
-- =========================================
SET NAMES utf8mb4;
SET time_zone = '+00:00';
-- CREATE DATABASE IF NOT EXISTS inosakti_iot_core CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
-- USE inosakti_iot_core;

-- =========================================
-- USERS
-- =========================================
CREATE TABLE users (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  username        VARCHAR(50)  NOT NULL UNIQUE,
  email           VARCHAR(100) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  role            ENUM('admin','user') NOT NULL DEFAULT 'user',
  data_user       JSON NULL,
  last_login_at   DATETIME(3) NULL DEFAULT NULL,
  created_at      DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  updated_at      DATETIME(3) NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  deleted_at      DATETIME(3) NULL DEFAULT NULL,
  INDEX idx_users_role (role),
  INDEX idx_users_lastlogin (last_login_at),
  INDEX idx_users_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- DEVICES (semua status & data terkini disatukan di data_device)
-- =========================================
CREATE TABLE devices (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id         BIGINT UNSIGNED NOT NULL,
  device_code     VARCHAR(64) NULL UNIQUE,
  name            VARCHAR(100) NULL,
  data_device     JSON NULL,
  last_seen       DATETIME(3) NULL,                              -- changed to DATETIME(3) for ms precision
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at      TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_devices_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_user_device (user_id),
  INDEX idx_devices_lastseen (last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- DEVICE AUTH (API key; unlimited expiry)
-- =========================================
CREATE TABLE device_auth (
  id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  device_id        BIGINT UNSIGNED NOT NULL,
  api_key_sha256   BINARY(32) NOT NULL,                   -- SHA-256(api_key)
  status           ENUM('active','revoked') NOT NULL DEFAULT 'active',
  issued_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at       TIMESTAMP NULL DEFAULT NULL,            -- NULL = unlimited
  CONSTRAINT fk_deviceauth_device FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_device_key (device_id, api_key_sha256),
  INDEX idx_deviceauth_device (device_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- CONFIGURATIONS (versioned)
-- =========================================
CREATE TABLE configurations (
  id                   BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  device_id            BIGINT UNSIGNED NOT NULL,
  version              INT NOT NULL DEFAULT 1,
  is_active            TINYINT(1) NOT NULL DEFAULT 1,
  data_configuration   JSON NOT NULL,                     -- {"on_time":1,"off_time":10,...}
  created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at           TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_config_device FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_device_version (device_id, version),
  INDEX idx_config_active (device_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- MONITOR (telemetri historis variatif)
-- =========================================
CREATE TABLE monitor (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  device_id       BIGINT UNSIGNED NOT NULL,
  data_monitor    JSON NOT NULL,
  created_at      DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),  -- default server timestamp with ms
  INDEX idx_monitor_device_time (device_id, created_at),
  CONSTRAINT fk_monitor_device FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- TRIGGER: update snapshot devices saat telemetry masuk
-- =========================================
DELIMITER $$
CREATE TRIGGER trg_monitor_after_insert
AFTER INSERT ON monitor
FOR EACH ROW
BEGIN
  UPDATE devices
     SET data_device = NEW.data_monitor,
         last_seen   = NEW.created_at
   WHERE id = NEW.device_id;
END$$
DELIMITER ;

-- =========================================
-- VIEW (opsional)
-- =========================================
CREATE OR REPLACE VIEW v_device_latest AS
SELECT 
  d.id AS device_id,
  d.user_id,
  d.name,
  d.last_seen,
  d.data_device
FROM devices d
WHERE d.deleted_at IS NULL;