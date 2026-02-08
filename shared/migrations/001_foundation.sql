-- Vision HR - Foundation Migration
-- Phase 1: Audit Log, JWT Tokens, Notifications, QR Codes
-- Run: mysql -u root vision_hr < shared/migrations/001_foundation.sql

SET NAMES utf8mb4;

-- Audit Log
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(50) NOT NULL COMMENT 'create,update,delete,login,login_failed,logout,view,export,approve,reject',
    `table_name` VARCHAR(100) DEFAULT '',
    `record_id` INT DEFAULT NULL,
    `old_data` JSON DEFAULT NULL,
    `new_data` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_table` (`table_name`, `record_id`),
    INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- JWT Refresh Tokens
CREATE TABLE IF NOT EXISTS `jwt_refresh_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL COMMENT 'SHA-256 hash of refresh token',
    `device_info` VARCHAR(255) DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `expires_at` DATETIME NOT NULL,
    `revoked` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_jwt_user` (`user_id`),
    INDEX `idx_jwt_hash` (`token_hash`),
    INDEX `idx_jwt_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `body` TEXT DEFAULT NULL,
    `type` VARCHAR(50) DEFAULT 'info' COMMENT 'info,success,warning,danger,leave,advance,attendance,approval',
    `reference_table` VARCHAR(100) DEFAULT NULL,
    `reference_id` INT DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `read_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_notif_user` (`user_id`, `is_read`),
    INDEX `idx_notif_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- QR Attendance Codes (TOTP-based, rotating)
CREATE TABLE IF NOT EXISTS `attendance_qr_codes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `branch_id` INT NOT NULL,
    `code` VARCHAR(64) NOT NULL,
    `hmac_secret` VARCHAR(64) NOT NULL,
    `generated_by` INT DEFAULT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_count` INT DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_qr_branch` (`branch_id`),
    INDEX `idx_qr_code` (`code`),
    INDEX `idx_qr_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Push notification subscriptions (for FCM)
CREATE TABLE IF NOT EXISTS `push_subscriptions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `endpoint` TEXT NOT NULL,
    `fcm_token` VARCHAR(255) DEFAULT NULL,
    `device_type` VARCHAR(20) DEFAULT 'web' COMMENT 'web,android,ios',
    `device_name` VARCHAR(100) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_push_user` (`user_id`),
    INDEX `idx_push_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add device_fingerprint and gps_accuracy to attendance for anti-spoofing
ALTER TABLE `tblattendance` ADD COLUMN IF NOT EXISTS `device_fingerprint` VARCHAR(64) DEFAULT NULL;
ALTER TABLE `tblattendance` ADD COLUMN IF NOT EXISTS `gps_accuracy` DECIMAL(8,2) DEFAULT NULL COMMENT 'GPS accuracy in meters';
ALTER TABLE `tblattendance` ADD COLUMN IF NOT EXISTS `mock_location` TINYINT(1) DEFAULT NULL COMMENT '1=mock location detected';
ALTER TABLE `tblattendance` ADD COLUMN IF NOT EXISTS `qr_code_id` INT DEFAULT NULL COMMENT 'FK to attendance_qr_codes if QR used';
