-- Vision HR - Migration 002: Missing Features
-- Device fingerprints, IP ranges, password resets, FCM config
-- Run: mysql -u root vision_hr < shared/migrations/002_missing_features.sql

SET NAMES utf8mb4;

-- Device fingerprints for anti-spoofing
CREATE TABLE IF NOT EXISTS `device_fingerprints` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `fingerprint` VARCHAR(64) NOT NULL,
    `device_info` TEXT DEFAULT NULL COMMENT 'User-Agent string',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `first_seen` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_seen` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `use_count` INT DEFAULT 1,
    `is_trusted` TINYINT(1) DEFAULT 0,
    INDEX `idx_devfp_user` (`user_id`),
    INDEX `idx_devfp_fp` (`fingerprint`),
    INDEX `idx_devfp_seen` (`last_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Branch IP ranges for anti-spoofing
CREATE TABLE IF NOT EXISTS `branch_ip_ranges` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `branch_id` INT NOT NULL,
    `ip_range_start` VARCHAR(45) DEFAULT NULL,
    `ip_range_end` VARCHAR(45) DEFAULT NULL,
    `cidr` VARCHAR(50) DEFAULT NULL COMMENT 'e.g. 192.168.1.0/24',
    `description` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ipr_branch` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password reset tokens
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL COMMENT 'SHA-256 hash of reset token',
    `expires_at` DATETIME NOT NULL,
    `used` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pwreset_hash` (`token_hash`),
    INDEX `idx_pwreset_user` (`user_id`),
    INDEX `idx_pwreset_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Biometric sync log
CREATE TABLE IF NOT EXISTS `biometric_sync_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `device_id` INT NOT NULL,
    `records_fetched` INT DEFAULT 0,
    `records_imported` INT DEFAULT 0,
    `status` VARCHAR(20) DEFAULT 'success' COMMENT 'success/error',
    `error_message` TEXT DEFAULT NULL,
    `synced_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_biosync_device` (`device_id`),
    INDEX `idx_biosync_date` (`synced_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add FCM config constant placeholder
-- (actual FCM_SERVER_KEY should be set in api/v1/config.php)

-- Ensure attendance has anti-spoof columns (idempotent)
ALTER TABLE `tblattendance` ADD COLUMN IF NOT EXISTS `device_fingerprint` VARCHAR(64) DEFAULT NULL;
ALTER TABLE `tblattendance` ADD COLUMN IF NOT EXISTS `gps_accuracy` DECIMAL(8,2) DEFAULT NULL;
ALTER TABLE `tblattendance` ADD COLUMN IF NOT EXISTS `mock_location` TINYINT(1) DEFAULT NULL;
ALTER TABLE `tblattendance` ADD COLUMN IF NOT EXISTS `qr_code_id` INT DEFAULT NULL;
ALTER TABLE `tblattendance` ADD COLUMN IF NOT EXISTS `risk_score` INT DEFAULT NULL COMMENT 'Anti-spoof risk score 0-100';
