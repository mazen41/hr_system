-- =====================================================
-- WORKFLOW & NOTIFICATION SYSTEM SCHEMA
-- Vision HR - Dynamic Approval Workflows with Pusher
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. WORKFLOW CONFIGURATIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS `workflow_configs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `entity_type` VARCHAR(50) NOT NULL UNIQUE,
    `name_ar` VARCHAR(255) NOT NULL,
    `name_en` VARCHAR(255),
    `description` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `require_all_approvers` TINYINT(1) DEFAULT 0 COMMENT 'If true, all approvers must approve. If false, any one can approve.',
    `auto_approve_after_days` INT DEFAULT NULL COMMENT 'Auto-approve if no action after X days',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_entity_type` (`entity_type`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. WORKFLOW STEPS
-- =====================================================
CREATE TABLE IF NOT EXISTS `workflow_steps` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `workflow_id` INT NOT NULL,
    `step_order` INT NOT NULL,
    `name_ar` VARCHAR(255) NOT NULL,
    `name_en` VARCHAR(255),
    `approver_type` ENUM('direct_manager', 'hr_manager', 'department_head', 'specific_user', 'role') NOT NULL,
    `approver_id` INT DEFAULT NULL COMMENT 'For specific_user type',
    `approver_role` VARCHAR(100) DEFAULT NULL COMMENT 'For role type',
    `is_optional` TINYINT(1) DEFAULT 0,
    `can_skip` TINYINT(1) DEFAULT 0,
    `timeout_hours` INT DEFAULT NULL COMMENT 'Auto-escalate after X hours',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`workflow_id`) REFERENCES `workflow_configs`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_workflow_step` (`workflow_id`, `step_order`),
    INDEX `idx_workflow` (`workflow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. WORKFLOW INSTANCES (Running workflows)
-- =====================================================
CREATE TABLE IF NOT EXISTS `workflow_instances` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `workflow_id` INT NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` INT NOT NULL,
    `requester_id` INT NOT NULL,
    `current_step` INT DEFAULT 1,
    `status` ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    `data` JSON COMMENT 'Additional data for the workflow',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME DEFAULT NULL,
    
    FOREIGN KEY (`workflow_id`) REFERENCES `workflow_configs`(`id`),
    FOREIGN KEY (`requester_id`) REFERENCES `tblusers`(`UserID`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_requester` (`requester_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. WORKFLOW APPROVALS
-- =====================================================
CREATE TABLE IF NOT EXISTS `workflow_approvals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `instance_id` INT NOT NULL,
    `step_number` INT NOT NULL,
    `approver_id` INT NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected', 'skipped') DEFAULT 'pending',
    `comment` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `actioned_at` DATETIME DEFAULT NULL,
    
    FOREIGN KEY (`instance_id`) REFERENCES `workflow_instances`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approver_id`) REFERENCES `tblusers`(`UserID`),
    INDEX `idx_instance` (`instance_id`),
    INDEX `idx_approver` (`approver_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_pending` (`approver_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. WORKFLOW AUDIT LOG
-- =====================================================
CREATE TABLE IF NOT EXISTS `workflow_audit_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `instance_id` INT NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `user_id` INT NOT NULL,
    `details` TEXT,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`instance_id`) REFERENCES `workflow_instances`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `tblusers`(`UserID`),
    INDEX `idx_instance` (`instance_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. NOTIFICATION SETTINGS (Pusher config)
-- =====================================================
CREATE TABLE IF NOT EXISTS `notification_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `provider` VARCHAR(50) NOT NULL UNIQUE,
    `is_enabled` TINYINT(1) DEFAULT 0,
    `app_id` VARCHAR(100),
    `api_key` VARCHAR(255),
    `api_secret` VARCHAR(255),
    `cluster` VARCHAR(20) DEFAULT 'eu',
    `extra_config` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. REWARDS & EVALUATION SYSTEM
-- =====================================================
CREATE TABLE IF NOT EXISTS `evaluation_periods` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name_ar` VARCHAR(255) NOT NULL,
    `name_en` VARCHAR(255),
    `period_type` ENUM('probation', 'annual', 'quarterly', 'project', 'custom') NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `evaluation_criteria` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name_ar` VARCHAR(255) NOT NULL,
    `name_en` VARCHAR(255),
    `category` ENUM('performance', 'behavior', 'skills', 'attendance', 'teamwork') NOT NULL,
    `weight` DECIMAL(5,2) DEFAULT 1.00 COMMENT 'Weight in final score calculation',
    `max_score` INT DEFAULT 5,
    `description` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employee_evaluations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    `evaluator_id` INT NOT NULL,
    `period_id` INT NOT NULL,
    `evaluation_date` DATE NOT NULL,
    `total_score` DECIMAL(5,2),
    `percentage` DECIMAL(5,2),
    `grade` ENUM('excellent', 'very_good', 'good', 'acceptable', 'poor') DEFAULT NULL,
    `strengths` TEXT,
    `weaknesses` TEXT,
    `recommendations` TEXT,
    `employee_comment` TEXT,
    `status` ENUM('draft', 'submitted', 'reviewed', 'approved', 'acknowledged') DEFAULT 'draft',
    `acknowledged_at` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`employee_id`) REFERENCES `tblusers`(`UserID`),
    FOREIGN KEY (`evaluator_id`) REFERENCES `tblusers`(`UserID`),
    FOREIGN KEY (`period_id`) REFERENCES `evaluation_periods`(`id`),
    INDEX `idx_employee` (`employee_id`),
    INDEX `idx_period` (`period_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `evaluation_scores` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `evaluation_id` INT NOT NULL,
    `criteria_id` INT NOT NULL,
    `score` INT NOT NULL,
    `comment` TEXT,
    
    FOREIGN KEY (`evaluation_id`) REFERENCES `employee_evaluations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`criteria_id`) REFERENCES `evaluation_criteria`(`id`),
    UNIQUE KEY `uk_eval_criteria` (`evaluation_id`, `criteria_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rewards` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    `reward_type` ENUM('bonus', 'certificate', 'promotion', 'gift', 'time_off', 'other') NOT NULL,
    `title_ar` VARCHAR(255) NOT NULL,
    `title_en` VARCHAR(255),
    `description` TEXT,
    `amount` DECIMAL(10,2) DEFAULT NULL COMMENT 'For monetary rewards',
    `currency` VARCHAR(3) DEFAULT 'SAR',
    `linked_evaluation_id` INT DEFAULT NULL,
    `linked_task_id` INT DEFAULT NULL,
    `awarded_by` INT NOT NULL,
    `awarded_date` DATE NOT NULL,
    `status` ENUM('pending', 'approved', 'delivered', 'cancelled') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`employee_id`) REFERENCES `tblusers`(`UserID`),
    FOREIGN KEY (`awarded_by`) REFERENCES `tblusers`(`UserID`),
    FOREIGN KEY (`linked_evaluation_id`) REFERENCES `employee_evaluations`(`id`) ON DELETE SET NULL,
    INDEX `idx_employee` (`employee_id`),
    INDEX `idx_type` (`reward_type`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. SALARY RANGES PER DEPARTMENT
-- =====================================================
CREATE TABLE IF NOT EXISTS `department_salary_ranges` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `section_id` INT NOT NULL,
    `grade_id` INT DEFAULT NULL,
    `job_title_id` INT DEFAULT NULL,
    `min_salary` DECIMAL(10,2) NOT NULL,
    `max_salary` DECIMAL(10,2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'SAR',
    `effective_date` DATE NOT NULL,
    `notes` TEXT,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`section_id`) REFERENCES `tblsection`(`Id`),
    INDEX `idx_section` (`section_id`),
    INDEX `idx_grade` (`grade_id`),
    INDEX `idx_job_title` (`job_title_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DEFAULT DATA
-- =====================================================

-- Default Workflow Configurations
INSERT INTO `workflow_configs` (`entity_type`, `name_ar`, `name_en`, `description`) VALUES
('leave_request', 'طلب إجازة', 'Leave Request', 'سير عمل الموافقة على طلبات الإجازة'),
('advance_request', 'طلب سلفة', 'Advance Request', 'سير عمل الموافقة على طلبات السلف'),
('promotion_request', 'طلب ترقية', 'Promotion Request', 'سير عمل الموافقة على طلبات الترقية'),
('violation', 'تسجيل مخالفة', 'Violation Record', 'سير عمل اعتماد المخالفات'),
('order', 'طلب إداري', 'Administrative Order', 'سير عمل الموافقة على الطلبات الإدارية'),
('evaluation', 'تقييم أداء', 'Performance Evaluation', 'سير عمل اعتماد تقييمات الأداء'),
('reward', 'مكافأة', 'Reward', 'سير عمل اعتماد المكافآت');

-- Default Workflow Steps for Leave Request
INSERT INTO `workflow_steps` (`workflow_id`, `step_order`, `name_ar`, `name_en`, `approver_type`) VALUES
(1, 1, 'موافقة المدير المباشر', 'Direct Manager Approval', 'direct_manager'),
(1, 2, 'موافقة الموارد البشرية', 'HR Approval', 'hr_manager');

-- Default Workflow Steps for Advance Request
INSERT INTO `workflow_steps` (`workflow_id`, `step_order`, `name_ar`, `name_en`, `approver_type`) VALUES
(2, 1, 'موافقة المدير المباشر', 'Direct Manager Approval', 'direct_manager'),
(2, 2, 'موافقة المالية', 'Finance Approval', 'role'),
(2, 3, 'موافقة الموارد البشرية', 'HR Approval', 'hr_manager');

-- Default Workflow Steps for Promotion
INSERT INTO `workflow_steps` (`workflow_id`, `step_order`, `name_ar`, `name_en`, `approver_type`) VALUES
(3, 1, 'موافقة المدير المباشر', 'Direct Manager Approval', 'direct_manager'),
(3, 2, 'موافقة رئيس القسم', 'Department Head Approval', 'department_head'),
(3, 3, 'الاعتماد النهائي', 'Final Approval', 'hr_manager');

-- Default Workflow Steps for Violation
INSERT INTO `workflow_steps` (`workflow_id`, `step_order`, `name_ar`, `name_en`, `approver_type`) VALUES
(4, 1, 'مراجعة الموارد البشرية', 'HR Review', 'hr_manager');

-- Default Workflow Steps for Order
INSERT INTO `workflow_steps` (`workflow_id`, `step_order`, `name_ar`, `name_en`, `approver_type`) VALUES
(5, 1, 'موافقة المدير المباشر', 'Direct Manager Approval', 'direct_manager');

-- Default Workflow Steps for Evaluation
INSERT INTO `workflow_steps` (`workflow_id`, `step_order`, `name_ar`, `name_en`, `approver_type`) VALUES
(6, 1, 'مراجعة رئيس القسم', 'Department Head Review', 'department_head'),
(6, 2, 'اعتماد الموارد البشرية', 'HR Approval', 'hr_manager');

-- Default Workflow Steps for Reward
INSERT INTO `workflow_steps` (`workflow_id`, `step_order`, `name_ar`, `name_en`, `approver_type`) VALUES
(7, 1, 'موافقة الموارد البشرية', 'HR Approval', 'hr_manager');

-- Default Notification Settings (Pusher - disabled by default)
INSERT INTO `notification_settings` (`provider`, `is_enabled`, `cluster`) VALUES
('pusher', 0, 'eu');

-- Default Evaluation Criteria
INSERT INTO `evaluation_criteria` (`name_ar`, `name_en`, `category`, `weight`, `max_score`, `sort_order`) VALUES
('جودة العمل', 'Work Quality', 'performance', 1.5, 5, 1),
('الإنتاجية', 'Productivity', 'performance', 1.5, 5, 2),
('الالتزام بالمواعيد', 'Punctuality', 'attendance', 1.0, 5, 3),
('العمل الجماعي', 'Teamwork', 'teamwork', 1.0, 5, 4),
('المبادرة', 'Initiative', 'behavior', 1.0, 5, 5),
('التواصل', 'Communication', 'skills', 1.0, 5, 6),
('حل المشكلات', 'Problem Solving', 'skills', 1.0, 5, 7),
('الالتزام بالسياسات', 'Policy Compliance', 'behavior', 1.0, 5, 8);

SET FOREIGN_KEY_CHECKS = 1;
