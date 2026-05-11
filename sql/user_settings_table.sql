-- User Settings Table for storing user preferences
-- Run this migration to add the user_settings table

CREATE TABLE IF NOT EXISTS `user_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_setting_unique` (`user_id`, `setting_key`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_user_settings_user` FOREIGN KEY (`user_id`) REFERENCES `tblusers` (`UserID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Attendance Settings Table for storing attendance configuration
CREATE TABLE IF NOT EXISTS `attendance_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) DEFAULT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_setting_unique` (`branch_id`, `setting_key`),
  KEY `idx_branch_id` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Insert default attendance settings
INSERT INTO `attendance_settings` (`branch_id`, `setting_key`, `setting_value`) VALUES
(NULL, 'gps_enabled', '1'),
(NULL, 'qr_enabled', '1'),
(NULL, 'manual_enabled', '1'),
(NULL, 'fingerprint_enabled', '0'),
(NULL, 'gps_required', '0'),
(NULL, 'max_gps_radius_meters', '500'),
(NULL, 'office_lat', ''),
(NULL, 'office_lng', ''),
(NULL, 'fingerprint_device_ip', ''),
(NULL, 'fingerprint_device_port', '4370'),
(NULL, 'fingerprint_device_type', 'zkteco')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Fingerprint Devices Table for managing multiple fingerprint devices
CREATE TABLE IF NOT EXISTS `fingerprint_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) DEFAULT NULL,
  `device_name` varchar(100) NOT NULL,
  `device_ip` varchar(45) NOT NULL,
  `device_port` int(11) NOT NULL DEFAULT 4370,
  `device_type` varchar(50) NOT NULL DEFAULT 'zkteco',
  `serial_number` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_sync` timestamp NULL DEFAULT NULL,
  `sync_status` varchar(50) DEFAULT 'never',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_device_ip` (`device_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Fingerprint Sync Log for tracking sync operations
CREATE TABLE IF NOT EXISTS `fingerprint_sync_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` int(11) NOT NULL,
  `sync_type` varchar(50) NOT NULL COMMENT 'attendance, users, all',
  `records_synced` int(11) DEFAULT 0,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
