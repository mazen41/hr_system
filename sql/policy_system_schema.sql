-- =====================================================
-- VISION HR - DYNAMIC POLICY SYSTEM DATABASE SCHEMA
-- Version: 1.0
-- Date: 2026-02-15
-- Description: Flexible policy management for leave, 
--              violations, promotions, and org structure
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. COMPANY POLICIES (Master Configuration)
-- =====================================================
CREATE TABLE IF NOT EXISTS `company_policies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `policy_code` VARCHAR(50) NOT NULL UNIQUE,
    `policy_name_ar` VARCHAR(255) NOT NULL,
    `policy_name_en` VARCHAR(255),
    `policy_category` ENUM('leave', 'attendance', 'promotion', 'violation', 'general') NOT NULL,
    `description_ar` TEXT,
    `description_en` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `effective_date` DATE,
    `expiry_date` DATE,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category` (`policy_category`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. LEAVE POLICIES (Flexible Leave Configuration)
-- =====================================================
CREATE TABLE IF NOT EXISTS `leave_policies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `policy_name_ar` VARCHAR(255) NOT NULL,
    `policy_name_en` VARCHAR(255),
    `leave_type_id` INT COMMENT 'Links to leaveclassification',
    
    -- Annual Leave Configuration
    `annual_days` DECIMAL(5,2) DEFAULT 30.00 COMMENT 'Total annual leave days (30, 21, 15, etc.)',
    `accrual_method` ENUM('monthly', 'yearly', 'custom') DEFAULT 'monthly',
    `monthly_accrual` DECIMAL(5,2) GENERATED ALWAYS AS (annual_days / 12) STORED,
    
    -- Carryover Configuration
    `allow_carryover` TINYINT(1) DEFAULT 1,
    `max_carryover_days` DECIMAL(5,2) DEFAULT 15.00,
    `carryover_expiry_months` INT DEFAULT 3 COMMENT 'Months after fiscal year start',
    `compensate_unused` TINYINT(1) DEFAULT 0 COMMENT 'Pay for unused days',
    `force_leave_before_expiry` TINYINT(1) DEFAULT 0 COMMENT 'Force employee to take leave',
    
    -- Hourly Leave Configuration
    `allow_hourly_leave` TINYINT(1) DEFAULT 1,
    `max_hours_per_day` DECIMAL(4,2) DEFAULT 4.00,
    `hours_per_day` DECIMAL(4,2) DEFAULT 8.00 COMMENT 'Working hours per day for conversion',
    `min_hours_per_request` DECIMAL(4,2) DEFAULT 1.00,
    
    -- Eligibility Rules
    `min_service_months` INT DEFAULT 0 COMMENT 'Minimum months before eligible',
    `probation_eligible` TINYINT(1) DEFAULT 0,
    
    -- Approval Configuration
    `requires_approval` TINYINT(1) DEFAULT 1,
    `approval_levels` INT DEFAULT 1,
    `advance_notice_days` INT DEFAULT 3,
    `max_consecutive_days` INT DEFAULT 30,
    
    -- Scope (which employees this applies to)
    `applies_to_all` TINYINT(1) DEFAULT 1,
    `applies_to_grades` JSON COMMENT 'Array of grade IDs',
    `applies_to_departments` JSON COMMENT 'Array of department IDs',
    `applies_to_job_titles` JSON COMMENT 'Array of job title IDs',
    `applies_to_branches` JSON COMMENT 'Array of branch IDs',
    
    `is_active` TINYINT(1) DEFAULT 1,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_leave_type` (`leave_type_id`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. EMPLOYEE LEAVE BALANCES (Tracking)
-- =====================================================
CREATE TABLE IF NOT EXISTS `employee_leave_balances` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `leave_policy_id` INT NOT NULL,
    `fiscal_year` YEAR NOT NULL,
    
    -- Balance Tracking
    `entitled_days` DECIMAL(6,2) DEFAULT 0.00 COMMENT 'Total entitled for the year',
    `accrued_days` DECIMAL(6,2) DEFAULT 0.00 COMMENT 'Accrued so far',
    `used_days` DECIMAL(6,2) DEFAULT 0.00,
    `used_hours` DECIMAL(6,2) DEFAULT 0.00,
    `pending_days` DECIMAL(6,2) DEFAULT 0.00 COMMENT 'Pending approval',
    `carryover_days` DECIMAL(6,2) DEFAULT 0.00 COMMENT 'Carried from previous year',
    `compensated_days` DECIMAL(6,2) DEFAULT 0.00 COMMENT 'Paid out',
    `forfeited_days` DECIMAL(6,2) DEFAULT 0.00 COMMENT 'Expired/forfeited',
    
    `available_days` DECIMAL(6,2) GENERATED ALWAYS AS (
        accrued_days + carryover_days - used_days - pending_days - compensated_days - forfeited_days
    ) STORED,
    
    `last_accrual_date` DATE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_user_policy_year` (`user_id`, `leave_policy_id`, `fiscal_year`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_year` (`fiscal_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. LEAVE ACCRUAL LOG (Monthly Accrual History)
-- =====================================================
CREATE TABLE IF NOT EXISTS `leave_accrual_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `leave_policy_id` INT NOT NULL,
    `accrual_date` DATE NOT NULL,
    `accrual_month` INT NOT NULL,
    `accrual_year` YEAR NOT NULL,
    `days_accrued` DECIMAL(5,2) NOT NULL,
    `balance_after` DECIMAL(6,2),
    `notes` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_user_date` (`user_id`, `accrual_date`),
    INDEX `idx_month_year` (`accrual_month`, `accrual_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. VIOLATION TYPES (Configurable Violation Categories)
-- =====================================================
CREATE TABLE IF NOT EXISTS `violation_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name_ar` VARCHAR(255) NOT NULL,
    `name_en` VARCHAR(255),
    `category` ENUM('attendance', 'conduct', 'performance', 'safety', 'policy', 'other') NOT NULL,
    `severity` ENUM('minor', 'moderate', 'major', 'critical') DEFAULT 'minor',
    `description_ar` TEXT,
    `description_en` TEXT,
    
    -- Penalty Configuration
    `default_penalty_type` ENUM('warning', 'deduction', 'suspension', 'termination') DEFAULT 'warning',
    `default_penalty_value` DECIMAL(10,2) DEFAULT 0 COMMENT 'Days or amount',
    `escalation_enabled` TINYINT(1) DEFAULT 1,
    
    -- Impact on Promotions
    `blocks_promotion` TINYINT(1) DEFAULT 0,
    `promotion_block_months` INT DEFAULT 6 COMMENT 'Months to block promotion after violation',
    
    -- Impact on Leave
    `affects_leave` TINYINT(1) DEFAULT 0,
    `leave_deduction_days` DECIMAL(5,2) DEFAULT 0,
    
    `is_active` TINYINT(1) DEFAULT 1,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_category` (`category`),
    INDEX `idx_severity` (`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. EMPLOYEE VIOLATIONS (Violation Records)
-- =====================================================
CREATE TABLE IF NOT EXISTS `employee_violations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `violation_type_id` INT NOT NULL,
    `violation_date` DATE NOT NULL,
    `reported_by` INT,
    `description` TEXT,
    `evidence_path` VARCHAR(500),
    
    -- Penalty Applied
    `penalty_type` ENUM('warning', 'deduction', 'suspension', 'termination'),
    `penalty_value` DECIMAL(10,2),
    `penalty_start_date` DATE,
    `penalty_end_date` DATE,
    
    -- Status
    `status` ENUM('reported', 'under_review', 'confirmed', 'appealed', 'dismissed', 'closed') DEFAULT 'reported',
    `appeal_notes` TEXT,
    `resolution_notes` TEXT,
    `resolved_by` INT,
    `resolved_at` DATETIME,
    
    -- Occurrence Tracking
    `occurrence_number` INT DEFAULT 1 COMMENT 'Nth occurrence of this type',
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_user` (`user_id`),
    INDEX `idx_type` (`violation_type_id`),
    INDEX `idx_date` (`violation_date`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. VIOLATION ESCALATION RULES
-- =====================================================
CREATE TABLE IF NOT EXISTS `violation_escalation_rules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `violation_type_id` INT NOT NULL,
    `occurrence_number` INT NOT NULL COMMENT '1st, 2nd, 3rd occurrence',
    `penalty_type` ENUM('warning', 'deduction', 'suspension', 'termination') NOT NULL,
    `penalty_value` DECIMAL(10,2),
    `penalty_duration_days` INT,
    `blocks_promotion` TINYINT(1) DEFAULT 0,
    `promotion_block_months` INT DEFAULT 0,
    `notes_ar` VARCHAR(255),
    `notes_en` VARCHAR(255),
    
    UNIQUE KEY `uk_type_occurrence` (`violation_type_id`, `occurrence_number`),
    INDEX `idx_type` (`violation_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. PROMOTION POLICIES
-- =====================================================
CREATE TABLE IF NOT EXISTS `promotion_policies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `policy_name_ar` VARCHAR(255) NOT NULL,
    `policy_name_en` VARCHAR(255),
    
    -- Violation Handling Mode
    `violation_handling` ENUM('block', 'warn_allow', 'notify_only') DEFAULT 'warn_allow'
        COMMENT 'block=prevent, warn_allow=show warning but allow override, notify_only=just show info',
    
    -- Eligibility Criteria
    `min_service_months` INT DEFAULT 12,
    `min_performance_score` DECIMAL(5,2),
    `require_no_violations` TINYINT(1) DEFAULT 0,
    `violation_lookback_months` INT DEFAULT 12 COMMENT 'Check violations in last N months',
    `blocking_violation_severities` JSON COMMENT '["major", "critical"]',
    `blocking_violation_types` JSON COMMENT 'Array of violation type IDs',
    
    -- Approval Configuration
    `requires_hr_approval` TINYINT(1) DEFAULT 1,
    `requires_manager_approval` TINYINT(1) DEFAULT 1,
    `requires_ceo_approval` TINYINT(1) DEFAULT 0,
    
    -- Scope
    `applies_to_all` TINYINT(1) DEFAULT 1,
    `applies_to_grades` JSON,
    `applies_to_departments` JSON,
    
    `is_active` TINYINT(1) DEFAULT 1,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. PROMOTION REQUESTS
-- =====================================================
CREATE TABLE IF NOT EXISTS `promotion_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `requested_by` INT NOT NULL,
    `promotion_policy_id` INT,
    
    -- Current Position
    `current_grade_id` INT,
    `current_job_title_id` INT,
    `current_salary` DECIMAL(12,2),
    
    -- Proposed Position
    `proposed_grade_id` INT,
    `proposed_job_title_id` INT,
    `proposed_salary` DECIMAL(12,2),
    `effective_date` DATE,
    
    -- Justification
    `justification` TEXT,
    `performance_notes` TEXT,
    
    -- Violation Check Results
    `has_violations` TINYINT(1) DEFAULT 0,
    `violation_count` INT DEFAULT 0,
    `violation_summary` JSON COMMENT 'Summary of violations found',
    `violation_override` TINYINT(1) DEFAULT 0 COMMENT 'Approved despite violations',
    `override_reason` TEXT,
    `override_by` INT,
    
    -- Approval Status
    `status` ENUM('draft', 'pending', 'manager_approved', 'hr_approved', 'approved', 'rejected', 'cancelled') DEFAULT 'draft',
    `manager_approval` TINYINT(1),
    `manager_approved_by` INT,
    `manager_approved_at` DATETIME,
    `hr_approval` TINYINT(1),
    `hr_approved_by` INT,
    `hr_approved_at` DATETIME,
    `final_approved_by` INT,
    `final_approved_at` DATETIME,
    `rejection_reason` TEXT,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_user` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_date` (`effective_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. EXTERNAL TASKS/MISSIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS `external_tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `task_type` ENUM('meeting', 'visit', 'training', 'workshop', 'mission', 'other') NOT NULL,
    `title_ar` VARCHAR(255) NOT NULL,
    `title_en` VARCHAR(255),
    `description` TEXT,
    
    -- Location
    `location_name` VARCHAR(255),
    `location_address` TEXT,
    `location_lat` DECIMAL(10,8),
    `location_lng` DECIMAL(11,8),
    
    -- Time
    `scheduled_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `estimated_hours` DECIMAL(5,2) GENERATED ALWAYS AS (
        TIMESTAMPDIFF(MINUTE, start_time, end_time) / 60
    ) STORED,
    
    -- Attendance Linking
    `attendance_check_in_id` INT COMMENT 'Links to attendancet',
    `attendance_check_out_id` INT,
    `actual_start_time` TIME,
    `actual_end_time` TIME,
    `actual_hours` DECIMAL(5,2),
    
    -- Late Calculation
    `late_minutes` INT DEFAULT 0,
    `early_leave_minutes` INT DEFAULT 0,
    `overtime_minutes` INT DEFAULT 0,
    
    -- Status
    `status` ENUM('scheduled', 'in_progress', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    `completion_notes` TEXT,
    
    -- Approval
    `requires_approval` TINYINT(1) DEFAULT 1,
    `approved_by` INT,
    `approved_at` DATETIME,
    
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_user` (`user_id`),
    INDEX `idx_date` (`scheduled_date`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 11. EMPLOYEE PRESENCE STATUS
-- =====================================================
CREATE TABLE IF NOT EXISTS `employee_presence` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    
    -- Auto Status (from attendance)
    `auto_status` ENUM('present', 'absent', 'late', 'on_leave', 'off_duty') DEFAULT 'off_duty',
    `last_check_in` DATETIME,
    `last_check_out` DATETIME,
    
    -- Manual Override Status
    `manual_status` ENUM('in_meeting', 'external_task', 'training', 'break', 'busy', 'available', 'away') DEFAULT NULL,
    `manual_status_note` VARCHAR(255),
    `manual_status_until` DATETIME,
    `manual_status_set_by` INT,
    
    -- Location (for external tasks)
    `current_location` VARCHAR(255),
    `current_task_id` INT,
    
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 12. ORGANIZATIONAL STRUCTURE (Enhanced)
-- =====================================================
CREATE TABLE IF NOT EXISTS `org_structure` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT DEFAULT NULL,
    `node_type` ENUM('company', 'division', 'department', 'section', 'team') NOT NULL,
    `name_ar` VARCHAR(255) NOT NULL,
    `name_en` VARCHAR(255),
    `code` VARCHAR(50),
    `description_ar` TEXT,
    `description_en` TEXT,
    
    -- Linking to existing tables
    `section_id` INT COMMENT 'Links to tblsection',
    `branch_id` INT COMMENT 'Links to branches',
    
    -- Manager
    `manager_id` INT COMMENT 'Links to tblusers',
    
    -- Display Order
    `sort_order` INT DEFAULT 0,
    `level` INT DEFAULT 0,
    `path` VARCHAR(500) COMMENT 'Materialized path for hierarchy',
    
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_parent` (`parent_id`),
    INDEX `idx_type` (`node_type`),
    INDEX `idx_section` (`section_id`),
    INDEX `idx_manager` (`manager_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 13. FISCAL YEAR SETTINGS
-- =====================================================
CREATE TABLE IF NOT EXISTS `fiscal_year_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `fiscal_year` YEAR NOT NULL UNIQUE,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `is_current` TINYINT(1) DEFAULT 0,
    
    -- Leave Carryover Settings for this year
    `carryover_deadline` DATE COMMENT 'Deadline to use carryover',
    `auto_forfeit_carryover` TINYINT(1) DEFAULT 0,
    `auto_compensate_unused` TINYINT(1) DEFAULT 0,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 14. POLICY AUDIT LOG
-- =====================================================
CREATE TABLE IF NOT EXISTS `policy_audit_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `table_name` VARCHAR(100) NOT NULL,
    `record_id` INT NOT NULL,
    `action` ENUM('create', 'update', 'delete', 'approve', 'reject', 'override') NOT NULL,
    `old_values` JSON,
    `new_values` JSON,
    `changed_by` INT NOT NULL,
    `change_reason` TEXT,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(500),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_table_record` (`table_name`, `record_id`),
    INDEX `idx_user` (`changed_by`),
    INDEX `idx_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Default Leave Policy (Saudi Labor Law - 30 days)
INSERT INTO `leave_policies` (`policy_name_ar`, `policy_name_en`, `annual_days`, `accrual_method`, `allow_carryover`, `max_carryover_days`, `allow_hourly_leave`, `is_active`) VALUES
('سياسة الإجازة السنوية - 30 يوم', 'Annual Leave Policy - 30 Days', 30.00, 'monthly', 1, 15.00, 1, 1),
('سياسة الإجازة السنوية - 21 يوم', 'Annual Leave Policy - 21 Days', 21.00, 'monthly', 1, 10.00, 1, 1),
('سياسة الإجازة السنوية - 15 يوم', 'Annual Leave Policy - 15 Days', 15.00, 'monthly', 1, 7.00, 1, 1);

-- Default Violation Types
INSERT INTO `violation_types` (`code`, `name_ar`, `name_en`, `category`, `severity`, `default_penalty_type`, `blocks_promotion`, `promotion_block_months`) VALUES
('LATE_ARRIVAL', 'التأخر عن الحضور', 'Late Arrival', 'attendance', 'minor', 'warning', 0, 0),
('EARLY_LEAVE', 'المغادرة المبكرة', 'Early Leave', 'attendance', 'minor', 'warning', 0, 0),
('ABSENCE_NO_NOTICE', 'الغياب بدون إشعار', 'Absence Without Notice', 'attendance', 'moderate', 'deduction', 1, 3),
('POLICY_VIOLATION', 'مخالفة السياسات', 'Policy Violation', 'policy', 'moderate', 'warning', 1, 6),
('MISCONDUCT', 'سوء السلوك', 'Misconduct', 'conduct', 'major', 'suspension', 1, 12),
('SAFETY_VIOLATION', 'مخالفة السلامة', 'Safety Violation', 'safety', 'major', 'suspension', 1, 12),
('PERFORMANCE_ISSUE', 'مشكلة في الأداء', 'Performance Issue', 'performance', 'moderate', 'warning', 1, 6),
('INSUBORDINATION', 'عدم الامتثال', 'Insubordination', 'conduct', 'critical', 'termination', 1, 24);

-- Default Escalation Rules for Late Arrival
INSERT INTO `violation_escalation_rules` (`violation_type_id`, `occurrence_number`, `penalty_type`, `penalty_value`, `blocks_promotion`, `promotion_block_months`, `notes_ar`) VALUES
(1, 1, 'warning', 0, 0, 0, 'إنذار شفهي'),
(1, 2, 'warning', 0, 0, 0, 'إنذار كتابي'),
(1, 3, 'deduction', 1, 0, 0, 'خصم يوم واحد'),
(1, 4, 'deduction', 2, 1, 3, 'خصم يومين مع إيقاف الترقية'),
(1, 5, 'suspension', 3, 1, 6, 'إيقاف 3 أيام');

-- Default Promotion Policy
INSERT INTO `promotion_policies` (`policy_name_ar`, `policy_name_en`, `violation_handling`, `min_service_months`, `violation_lookback_months`, `blocking_violation_severities`) VALUES
('سياسة الترقيات الافتراضية', 'Default Promotion Policy', 'warn_allow', 12, 12, '["major", "critical"]');

-- Current Fiscal Year
INSERT INTO `fiscal_year_settings` (`fiscal_year`, `start_date`, `end_date`, `is_current`) VALUES
(2026, '2026-01-01', '2026-12-31', 1);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- VIEWS FOR REPORTING
-- =====================================================

-- Employee Leave Summary View
CREATE OR REPLACE VIEW `v_employee_leave_summary` AS
SELECT 
    elb.user_id,
    u.FirstName,
    u.LastName,
    lp.policy_name_ar,
    lp.annual_days,
    elb.fiscal_year,
    elb.entitled_days,
    elb.accrued_days,
    elb.used_days,
    elb.carryover_days,
    elb.available_days,
    elb.pending_days
FROM employee_leave_balances elb
JOIN tblusers u ON u.UserID = elb.user_id
JOIN leave_policies lp ON lp.id = elb.leave_policy_id;

-- Employee Violation Summary View
CREATE OR REPLACE VIEW `v_employee_violation_summary` AS
SELECT 
    ev.user_id,
    u.FirstName,
    u.LastName,
    COUNT(*) as total_violations,
    SUM(CASE WHEN vt.severity = 'minor' THEN 1 ELSE 0 END) as minor_count,
    SUM(CASE WHEN vt.severity = 'moderate' THEN 1 ELSE 0 END) as moderate_count,
    SUM(CASE WHEN vt.severity = 'major' THEN 1 ELSE 0 END) as major_count,
    SUM(CASE WHEN vt.severity = 'critical' THEN 1 ELSE 0 END) as critical_count,
    SUM(CASE WHEN vt.blocks_promotion = 1 THEN 1 ELSE 0 END) as promotion_blocking_count,
    MAX(ev.violation_date) as last_violation_date
FROM employee_violations ev
JOIN tblusers u ON u.UserID = ev.user_id
JOIN violation_types vt ON vt.id = ev.violation_type_id
WHERE ev.status IN ('confirmed', 'closed')
GROUP BY ev.user_id, u.FirstName, u.LastName;

-- Promotion Eligibility Check View
CREATE OR REPLACE VIEW `v_promotion_eligibility` AS
SELECT 
    u.UserID as user_id,
    u.FirstName,
    u.LastName,
    r.GradeID as current_grade,
    r.jobtitleID as current_job_title,
    TIMESTAMPDIFF(MONTH, u.CreatedDate, NOW()) as service_months,
    COALESCE(vs.total_violations, 0) as total_violations,
    COALESCE(vs.promotion_blocking_count, 0) as blocking_violations,
    CASE 
        WHEN COALESCE(vs.promotion_blocking_count, 0) > 0 THEN 'has_blocking_violations'
        WHEN TIMESTAMPDIFF(MONTH, u.CreatedDate, NOW()) < 12 THEN 'insufficient_service'
        ELSE 'eligible'
    END as eligibility_status
FROM tblusers u
LEFT JOIN tblremewal r ON r.Id = u.lastversion
LEFT JOIN v_employee_violation_summary vs ON vs.user_id = u.UserID
WHERE u.isemp = 1;
