-- Vision HR Database Schema
-- Generated from codebase analysis

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Site/Account settings
CREATE TABLE IF NOT EXISTS `tblsite` (
    `AccountID` INT AUTO_INCREMENT PRIMARY KEY,
    `SiteUrl` VARCHAR(255),
    `SiteTitle` VARCHAR(255) DEFAULT 'Vision HR',
    `SiteLogo` VARCHAR(255),
    `SiteCountryID` VARCHAR(10) DEFAULT 'SA',
    `SiteTimeZone` VARCHAR(50) DEFAULT 'Asia/Riyadh',
    `SiteDateFormat` VARCHAR(20) DEFAULT 'Y-m-d',
    `SiteCurrencyID` VARCHAR(10) DEFAULT 'SAR',
    `SiteEndDate` DATE DEFAULT NULL,
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Branches
CREATE TABLE IF NOT EXISTS `branches` (
    `branch_id` INT AUTO_INCREMENT PRIMARY KEY,
    `branch_ref` VARCHAR(50),
    `branch_name` VARCHAR(255) NOT NULL,
    `branch_style` VARCHAR(50),
    `branch_address` INT DEFAULT NULL,
    `isdefault` TINYINT(1) DEFAULT NULL,
    `isstopped` TINYINT(1) DEFAULT NULL,
    `TypeBracnhLocation` TINYINT(1) DEFAULT NULL COMMENT '1=single point+radius, 2=polygon',
    `Onepoint` TEXT DEFAULT NULL COMMENT 'lat,lng,radius',
    `MorePoint` TEXT DEFAULT NULL COMMENT 'lat1-lng1,lat2-lng2,...',
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Apps/Modules
CREATE TABLE IF NOT EXISTS `apps` (
    `AppID` VARCHAR(10) PRIMARY KEY,
    `AppName` VARCHAR(100) NOT NULL,
    `Sort` INT DEFAULT 0,
    `Disabled` TINYINT(1) DEFAULT NULL,
    `IsRrequred` TINYINT(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Branch-App mapping
CREATE TABLE IF NOT EXISTS `tblbranchesapps` (
    `ID` INT AUTO_INCREMENT PRIMARY KEY,
    `BranchID` INT NOT NULL,
    `AppID` VARCHAR(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User Groups (Roles)
CREATE TABLE IF NOT EXISTS `tblusergroups` (
    `GroupID` INT AUTO_INCREMENT PRIMARY KEY,
    `GroupNumber` VARCHAR(50),
    `GroupName` VARCHAR(255) NOT NULL,
    `GroupDesc` TEXT,
    `FullAccess` TINYINT(1) DEFAULT NULL,
    `IsSystem` TINYINT(1) DEFAULT NULL,
    `IsDisabled` TINYINT(1) DEFAULT NULL,
    `BranchID` INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permissions
CREATE TABLE IF NOT EXISTS `tblpermissions` (
    `PermID` INT AUTO_INCREMENT PRIMARY KEY,
    `GroupID` INT NOT NULL,
    `AppID` VARCHAR(10),
    `PermName` VARCHAR(255) NOT NULL,
    `PermDesc` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users/Employees
CREATE TABLE IF NOT EXISTS `tblusers` (
    `UserID` INT AUTO_INCREMENT PRIMARY KEY,
    `UserEmail` VARCHAR(255) NOT NULL,
    `UserPassword` VARCHAR(255) NOT NULL,
    `FirstName` VARCHAR(100) NOT NULL,
    `SecondName` VARCHAR(100),
    `LastName` VARCHAR(100),
    `Photo` VARCHAR(255),
    `Phone` VARCHAR(50),
    `ohter_phone` VARCHAR(50),
    `Note` TEXT,
    `UserGroupID` INT DEFAULT NULL,
    `IsDisabled` TINYINT(1) DEFAULT NULL,
    `IsSystem` TINYINT(1) DEFAULT NULL,
    `AllowedBranches` VARCHAR(255),
    `BranchID` INT DEFAULT NULL,
    `FingerID` VARCHAR(50),
    `lastversion` INT DEFAULT NULL COMMENT 'FK to tblremewal.Id',
    `isemp` TINYINT(1) DEFAULT NULL,
    `confrom` TINYINT(1) DEFAULT NULL,
    `home_page` VARCHAR(100) DEFAULT 'Hrdashboard',
    `manager` INT DEFAULT NULL COMMENT 'FK to tblusers.UserID',
    `related_to` INT DEFAULT NULL,
    `user_insurance` VARCHAR(255),
    `user_bank_name` VARCHAR(255),
    `user_account_bank` VARCHAR(255),
    `HealthCondition` VARCHAR(255),
    `Sex` VARCHAR(10),
    `marital_status` VARCHAR(50),
    `user_address` TEXT,
    `Id_h` VARCHAR(50),
    `start_date_h` DATE,
    `end_date_h` DATE,
    `path_h` VARCHAR(255),
    `Id_license` VARCHAR(50),
    `start_date_license` DATE,
    `end_date_license` DATE,
    `path_license` VARCHAR(255),
    `Id_passport` VARCHAR(50),
    `start_date_passport` DATE,
    `end_date_passport` DATE,
    `path_passport` VARCHAR(255),
    `Id_health` VARCHAR(50),
    `start_date_health` DATE,
    `end_date_health` DATE,
    `path_health` VARCHAR(255),
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sections (Departments) - Tree structure
CREATE TABLE IF NOT EXISTS `tblsection` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `Name` VARCHAR(255) NOT NULL,
    `ParentID` INT DEFAULT NULL,
    `BranchID` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Groups (Job Groups)
CREATE TABLE IF NOT EXISTS `tblgroup` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `Name` VARCHAR(255) NOT NULL,
    `BranchID` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Job Grades
CREATE TABLE IF NOT EXISTS `tbljobgrade` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `Name` VARCHAR(255) NOT NULL,
    `BranchID` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Job Titles
CREATE TABLE IF NOT EXISTS `tbljobtitle` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `Name` VARCHAR(255) NOT NULL,
    `BranchID` INT NOT NULL,
    `SectionID` INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Employment Types
CREATE TABLE IF NOT EXISTS `tblemploymenttype` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `Name` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contracts/Renewals/Promotions (PIVOT TABLE)
CREATE TABLE IF NOT EXISTS `tblremewal` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `UserID` INT NOT NULL,
    `SectionID` INT DEFAULT NULL,
    `BranchID` INT DEFAULT NULL,
    `GroupID` INT DEFAULT NULL,
    `GradeID` INT DEFAULT NULL,
    `shiftID` VARCHAR(255) DEFAULT NULL,
    `TypeID` INT DEFAULT NULL,
    `fingerID` VARCHAR(255) DEFAULT NULL,
    `jobtitleID` INT DEFAULT NULL,
    `Salary` DECIMAL(12,2) DEFAULT 0,
    `Currency` VARCHAR(10) DEFAULT 'SAR',
    `new_s_date` DATE DEFAULT NULL,
    `new_e_date` DATE DEFAULT NULL,
    `state` TINYINT(1) DEFAULT NULL,
    `come_name` VARCHAR(255),
    `day` INT DEFAULT NULL,
    `Reason` TEXT,
    `created_by` INT DEFAULT NULL,
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `LastUpdateDate` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Shifts
CREATE TABLE IF NOT EXISTS `tbshift` (
    `ShiftID` INT AUTO_INCREMENT PRIMARY KEY,
    `BranchID` INT NOT NULL,
    `ShiftName` VARCHAR(255) NOT NULL,
    `ShiftStartTime` TIME,
    `ShiftEndTime` TIME,
    `ShiftState` TINYINT(1) DEFAULT NULL,
    `NumFootprint` INT DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Shift Settings
CREATE TABLE IF NOT EXISTS `shift_setting` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `shift_id` INT NOT NULL,
    `late_tolerance` INT DEFAULT 0,
    `early_leave_tolerance` INT DEFAULT 0,
    `overtime_start_after` INT DEFAULT 0,
    `absent_after` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Shift Schedule
CREATE TABLE IF NOT EXISTS `shifts_schedule` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `shift_id` INT NOT NULL,
    `day_of_week` INT DEFAULT NULL,
    `start_time` TIME,
    `end_time` TIME,
    `is_off` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Fingerprint Devices
CREATE TABLE IF NOT EXISTS `tbfingerprint` (
    `FingerprintID` INT AUTO_INCREMENT PRIMARY KEY,
    `BranchID` INT NOT NULL,
    `FingerprintName` VARCHAR(255) NOT NULL,
    `FingerprintType` VARCHAR(50),
    `FingerprintState` TINYINT(1) DEFAULT NULL,
    `FingerprintSerailnumber` VARCHAR(100),
    `ip` VARCHAR(50),
    `port` VARCHAR(10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Attendance Records
CREATE TABLE IF NOT EXISTS `tblattendance` (
    `AttendanceID` INT AUTO_INCREMENT PRIMARY KEY,
    `EmpID` INT NOT NULL,
    `BranchID` INT DEFAULT NULL,
    `Date` DATE NOT NULL,
    `Type` TINYINT(1) DEFAULT NULL COMMENT '1=in, 2=out',
    `Time` TIME,
    `who_add` INT DEFAULT NULL,
    `source` VARCHAR(20) DEFAULT 'manual' COMMENT 'manual/device/app/excel',
    `lat` DECIMAL(10,8) DEFAULT NULL,
    `lng` DECIMAL(11,8) DEFAULT NULL,
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Leave Classification
CREATE TABLE IF NOT EXISTS `leaveclassification` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `BranchID` INT DEFAULT NULL,
    `Name` VARCHAR(255) NOT NULL,
    `Description` TEXT,
    `isaccept` TINYINT(1) DEFAULT NULL,
    `type` TINYINT(1) DEFAULT NULL,
    `state` TINYINT(1) DEFAULT NULL,
    `RequiresAttachment` TINYINT(1) DEFAULT NULL,
    `for_what` TINYINT(1) DEFAULT NULL COMMENT '1=user,2=group,3=grade,4=section,5=jobtitle',
    `chose` INT DEFAULT NULL,
    `max_days` INT DEFAULT NULL,
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Leave Requests
CREATE TABLE IF NOT EXISTS `tblleave` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `UserID` INT NOT NULL,
    `BranchID` INT DEFAULT NULL,
    `LeaveTypeID` INT DEFAULT NULL,
    `StartDate` DATE,
    `EndDate` DATE,
    `Days` INT DEFAULT 0,
    `Reason` TEXT,
    `Status` TINYINT(1) DEFAULT 0 COMMENT '0=pending,1=approved,2=rejected',
    `attachment` VARCHAR(255),
    `type` TINYINT(1) DEFAULT 1,
    `created_by` INT DEFAULT NULL,
    `approved_by` INT DEFAULT NULL,
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `LastUpdateDate` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Benefits
CREATE TABLE IF NOT EXISTS `tblbenefit` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `BranchID` VARCHAR(255),
    `UserID` INT DEFAULT NULL,
    `name` VARCHAR(255),
    `Amount` DECIMAL(12,2) DEFAULT 0,
    `Currency` VARCHAR(10) DEFAULT 'SAR',
    `Reason` TEXT,
    `for_what` TINYINT(1) DEFAULT NULL,
    `extionsion` VARCHAR(255),
    `beneft_type` TINYINT(1) DEFAULT NULL,
    `DueDate` DATE DEFAULT NULL,
    `AmountType` TINYINT(1) DEFAULT NULL,
    `monthly` TINYINT(1) DEFAULT NULL,
    `Status` TINYINT(1) DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `LastUpdateDate` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Deductions
CREATE TABLE IF NOT EXISTS `tbldeductions` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `BranchID` VARCHAR(255),
    `UserID` INT DEFAULT NULL,
    `name` VARCHAR(255),
    `Amount` DECIMAL(12,2) DEFAULT 0,
    `Currency` VARCHAR(10) DEFAULT 'SAR',
    `Reason` TEXT,
    `for_what` TINYINT(1) DEFAULT NULL,
    `extionsion` VARCHAR(255),
    `DueDate` DATE DEFAULT NULL,
    `Status` TINYINT(1) DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `LastUpdateDate` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Employee Advances (Loans)
CREATE TABLE IF NOT EXISTS `tblempadvances` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `UserID` INT NOT NULL,
    `BranchID` INT DEFAULT NULL,
    `Amount` DECIMAL(12,2) DEFAULT 0,
    `currency` VARCHAR(10) DEFAULT 'SAR',
    `DueDate` DATE DEFAULT NULL,
    `Status` TINYINT(1) DEFAULT NULL,
    `type` TINYINT(1) DEFAULT NULL,
    `description` TEXT,
    `attachment` VARCHAR(255),
    `created_by` INT DEFAULT NULL,
    `approved_by` INT DEFAULT NULL,
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `LastUpdateDate` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Incentives
CREATE TABLE IF NOT EXISTS `tblincentive` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `BranchID` VARCHAR(255),
    `UserID` INT DEFAULT NULL,
    `name` VARCHAR(255),
    `Amount` DECIMAL(12,2) DEFAULT 0,
    `Currency` VARCHAR(10) DEFAULT 'SAR',
    `Reason` TEXT,
    `for_what` TINYINT(1) DEFAULT NULL,
    `extionsion` VARCHAR(255),
    `DueDate` DATE DEFAULT NULL,
    `Status` TINYINT(1) DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `LastUpdateDate` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Resignation
CREATE TABLE IF NOT EXISTS `tblresignation` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `UserID` INT NOT NULL,
    `BranchID` INT DEFAULT NULL,
    `DueDate` DATE,
    `Reason` TEXT,
    `Status` TINYINT(1) DEFAULT NULL,
    `type` TINYINT(1) DEFAULT 1,
    `attachment` VARCHAR(255),
    `created_by` INT DEFAULT NULL,
    `approved_by` INT DEFAULT NULL,
    `CreatedDate` DATE,
    `LastUpdateDate` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dismissal
CREATE TABLE IF NOT EXISTS `tbldismissal` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `UserID` INT NOT NULL,
    `BranchID` INT DEFAULT NULL,
    `DueDate` DATE,
    `Reason` TEXT,
    `Status` TINYINT(1) DEFAULT NULL,
    `attachment` VARCHAR(255),
    `created_by` INT DEFAULT NULL,
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insurance Companies
CREATE TABLE IF NOT EXISTS `tbinsurance` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `Name` VARCHAR(255) NOT NULL,
    `BranchID` INT DEFAULT NULL,
    `Description` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Holidays
CREATE TABLE IF NOT EXISTS `tblholidays` (
    `Holiday_ID` INT AUTO_INCREMENT PRIMARY KEY,
    `BranchID` INT DEFAULT NULL,
    `Name` VARCHAR(255) NOT NULL,
    `Start_date` DATE,
    `End_date` DATE,
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Employee Orders
CREATE TABLE IF NOT EXISTS `tblorders` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `UserID` INT NOT NULL,
    `BranchID` INT DEFAULT NULL,
    `OrderType` VARCHAR(100),
    `Description` TEXT,
    `Status` TINYINT(1) DEFAULT NULL,
    `attachment` VARCHAR(255),
    `created_by` INT DEFAULT NULL,
    `approved_by` INT DEFAULT NULL,
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `LastUpdateDate` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Finger Forget Requests
CREATE TABLE IF NOT EXISTS `tblfinger_forget` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `UserID` INT NOT NULL,
    `BranchID` INT DEFAULT NULL,
    `date` DATE,
    `Reason` TEXT,
    `Status` TINYINT(1) DEFAULT NULL,
    `attachment` VARCHAR(255),
    `created_by` INT DEFAULT NULL,
    `approved_by` INT DEFAULT NULL,
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Salary Settings (Account mapping)
CREATE TABLE IF NOT EXISTS `setting_account_salary` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `account_id` INT DEFAULT NULL,
    `account_name` VARCHAR(255),
    `created_by` INT DEFAULT NULL,
    `created_date` DATE,
    `last_update` DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Salary Registration (Payroll runs)
CREATE TABLE IF NOT EXISTS `salary_registration` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `BranchID` VARCHAR(255),
    `month` VARCHAR(2),
    `year` VARCHAR(4),
    `total_salary` DECIMAL(12,2) DEFAULT 0,
    `total_incentive` DECIMAL(12,2) DEFAULT 0,
    `total_benefit` DECIMAL(12,2) DEFAULT 0,
    `total_advance` DECIMAL(12,2) DEFAULT 0,
    `total_deduction` DECIMAL(12,2) DEFAULT 0,
    `net_salary` DECIMAL(12,2) DEFAULT 0,
    `created_by` INT DEFAULT NULL,
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Account Guide (Chart of Accounts)
CREATE TABLE IF NOT EXISTS `tblaccountguide` (
    `AccountID` INT AUTO_INCREMENT PRIMARY KEY,
    `AccountNumber` VARCHAR(50),
    `AccountName` VARCHAR(255),
    `AccountType` TINYINT(1) DEFAULT 1,
    `ParentID` INT DEFAULT 0,
    `BranchID` INT DEFAULT NULL,
    `IsDisabled` TINYINT(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Address
CREATE TABLE IF NOT EXISTS `tbladdress` (
    `AddressID` INT AUTO_INCREMENT PRIMARY KEY,
    `AddressType` VARCHAR(50),
    `Street` VARCHAR(255),
    `City` VARCHAR(100),
    `District` VARCHAR(100),
    `PostalCode` VARCHAR(20),
    `Country` VARCHAR(50) DEFAULT 'SA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Identity Types
CREATE TABLE IF NOT EXISTS `tblidentitytypes` (
    `IDType` INT AUTO_INCREMENT PRIMARY KEY,
    `TypeName` VARCHAR(100),
    `AvailableFor` VARCHAR(20) DEFAULT 'any'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reports (Tree structure)
CREATE TABLE IF NOT EXISTS `reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `app` VARCHAR(10),
    `name` VARCHAR(255) NOT NULL,
    `parent` INT DEFAULT 0,
    `icon` VARCHAR(50),
    `url` VARCHAR(255),
    `sort` INT DEFAULT 0,
    `stopped` TINYINT(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Employee Certificates
CREATE TABLE IF NOT EXISTS `tblcertificates` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `UserID` INT NOT NULL,
    `Name` VARCHAR(255),
    `Side` VARCHAR(255),
    `CertDate` DATE,
    `attachment` VARCHAR(255),
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Employee Experience
CREATE TABLE IF NOT EXISTS `tblexperience` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `UserID` INT NOT NULL,
    `JobTitle` VARCHAR(255),
    `Company` VARCHAR(255),
    `StartDate` DATE,
    `EndDate` DATE,
    `Tasks` TEXT,
    `attachment` VARCHAR(255),
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- SEED DATA
-- =============================================

-- Default site
INSERT INTO `tblsite` (`SiteTitle`, `SiteCurrencyID`, `SiteTimeZone`, `SiteDateFormat`) 
VALUES ('Vision HR', 'SAR', 'Asia/Riyadh', 'Y-m-d');

-- Default branch
INSERT INTO `branches` (`branch_name`, `isdefault`, `TypeBracnhLocation`, `Onepoint`) 
VALUES ('الفرع الرئيسي', 1, 1, '24.7136,46.6753,500');

-- Apps
INSERT INTO `apps` (`AppID`, `AppName`, `Sort`) VALUES 
('HR', 'الموارد البشرية', 1),
('SAL', 'المبيعات', 2),
('ACC', 'المحاسبة', 3);

-- Link HR app to branch
INSERT INTO `tblbranchesapps` (`BranchID`, `AppID`) VALUES (1, 'HR');

-- Admin user group
INSERT INTO `tblusergroups` (`GroupName`, `FullAccess`, `IsSystem`, `BranchID`) 
VALUES ('مدير النظام', 1, 1, 1);

-- Employee user group
INSERT INTO `tblusergroups` (`GroupName`, `FullAccess`, `IsSystem`, `BranchID`) 
VALUES ('موظف', NULL, NULL, 1);

-- Admin permissions (all)
INSERT INTO `tblpermissions` (`GroupID`, `AppID`, `PermName`) VALUES
(1, 'HR', 'إضافة موظف'), (1, 'HR', 'عرض موظف'), (1, 'HR', 'تعديل موظف'),
(1, 'HR', 'الفترات'), (1, 'HR', 'اجهزة البصمة'), (1, 'HR', 'الاقسام'),
(1, 'HR', 'المسميات الوظيفية'), (1, 'HR', 'شركات التامين'), (1, 'HR', 'المجموعات'),
(1, 'HR', 'الدرجات الوظيفية'), (1, 'HR', 'انماط العمل'), (1, 'HR', 'الاجازات الرسمية'),
(1, 'HR', 'الاجازات العامة'), (1, 'HR', 'الحضور والانصراف'), (1, 'HR', 'عرض الحضور والانصراف'),
(1, 'HR', 'تحضير موظف'), (1, 'HR', 'رفع ملف الاكسل'),
(1, 'HR', 'إضافة تعويض'), (1, 'HR', 'إضافة خصم'), (1, 'HR', 'إضافة حافز'),
(1, 'HR', 'اضافة فترة'), (1, 'HR', 'اضافة بصمة'), (1, 'HR', 'اضافة قسم'),
(1, 'HR', 'اضافة عقد'), (1, 'HR', 'اضافة ترقية'), (1, 'HR', 'إصدار الرواتب');

-- Admin user (password: admin123)
INSERT INTO `tblusers` (`UserEmail`, `UserPassword`, `FirstName`, `LastName`, `UserGroupID`, `IsSystem`, `AllowedBranches`, `BranchID`, `isemp`, `home_page`) 
VALUES ('admin@vision.hr', 'admin123', 'مدير', 'النظام', 1, 1, '1', 1, 1, 'Hrdashboard');

-- Sample employee (password: emp123)
INSERT INTO `tblusers` (`UserEmail`, `UserPassword`, `FirstName`, `LastName`, `UserGroupID`, `AllowedBranches`, `BranchID`, `isemp`, `home_page`, `Sex`, `marital_status`) 
VALUES ('emp@vision.hr', 'emp123', 'أحمد', 'محمد', 2, '1', 1, 1, 'Hrdashboard', 'ذكر', 'متزوج');

-- Employment types
INSERT INTO `tblemploymenttype` (`Name`) VALUES ('دوام كامل'), ('دوام جزئي'), ('عقد مؤقت'), ('تدريب');

-- Section
INSERT INTO `tblsection` (`Name`, `BranchID`) VALUES ('الإدارة العامة', 1);
INSERT INTO `tblsection` (`Name`, `ParentID`, `BranchID`) VALUES ('تقنية المعلومات', 1, 1);
INSERT INTO `tblsection` (`Name`, `ParentID`, `BranchID`) VALUES ('الموارد البشرية', 1, 1);

-- Job Grade
INSERT INTO `tbljobgrade` (`Name`, `BranchID`) VALUES ('درجة أولى', 1), ('درجة ثانية', 1), ('درجة ثالثة', 1);

-- Job Title
INSERT INTO `tbljobtitle` (`Name`, `BranchID`, `SectionID`) VALUES ('مدير عام', 1, 1), ('مطور برمجيات', 1, 2), ('أخصائي موارد بشرية', 1, 3);

-- Group
INSERT INTO `tblgroup` (`Name`, `BranchID`) VALUES ('المجموعة أ', 1), ('المجموعة ب', 1);

-- Shift
INSERT INTO `tbshift` (`BranchID`, `ShiftName`, `ShiftStartTime`, `ShiftEndTime`, `NumFootprint`) 
VALUES (1, 'الفترة الصباحية', '08:00:00', '16:00:00', 2);

-- Contract for admin
INSERT INTO `tblremewal` (`UserID`, `SectionID`, `BranchID`, `GroupID`, `GradeID`, `shiftID`, `TypeID`, `jobtitleID`, `Salary`, `Currency`, `new_s_date`, `new_e_date`, `state`) 
VALUES (1, 1, 1, 1, 1, '1', 1, 1, 15000.00, 'SAR', '2025-01-01', '2026-12-31', 1);

-- Contract for employee
INSERT INTO `tblremewal` (`UserID`, `SectionID`, `BranchID`, `GroupID`, `GradeID`, `shiftID`, `TypeID`, `jobtitleID`, `Salary`, `Currency`, `new_s_date`, `new_e_date`, `state`) 
VALUES (2, 2, 1, 1, 2, '1', 1, 2, 8000.00, 'SAR', '2025-01-01', '2026-12-31', 1);

-- Update lastversion
UPDATE `tblusers` SET `lastversion` = 1 WHERE `UserID` = 1;
UPDATE `tblusers` SET `lastversion` = 2 WHERE `UserID` = 2;

-- Leave classifications
INSERT INTO `leaveclassification` (`BranchID`, `Name`, `Description`, `type`, `max_days`) VALUES
(1, 'إجازة سنوية', 'إجازة سنوية مدفوعة', 1, 30),
(1, 'إجازة مرضية', 'إجازة مرضية - تتطلب مرفق', 1, 15),
(1, 'إجازة طارئة', 'إجازة طارئة', 1, 5);
UPDATE `leaveclassification` SET `RequiresAttachment` = 1 WHERE `Name` = 'إجازة مرضية';

-- Reports
INSERT INTO `reports` (`app`, `name`, `parent`, `icon`, `url`, `sort`) VALUES
('HR', 'الموارد البشرية', 0, 'users', '#', 1),
('HR', 'تقرير الموظفين', 1, 'user', 'report-all-emplyer', 1),
('HR', 'تقرير الأقسام', 1, 'sitemap', 'report-section', 2),
('HR', 'تقرير المسميات', 1, 'briefcase', 'report-jobtitle', 3),
('HR', 'تقرير الإجازات', 1, 'calendar', 'report-leaveRequest', 4),
('HR', 'تقرير الحضور', 1, 'clock', 'report-fingerprint', 5),
('HR', 'تقرير الرواتب', 1, 'money-bill', 'report-export-salarys', 6);

-- Salary account settings
INSERT INTO `setting_account_salary` (`account_id`, `account_name`) VALUES
(1, 'مرتبات وأجور'),
(2, 'مكافآت الموظفين'),
(3, 'تعويضات الموظفين'),
(4, 'سلف الموظفين'),
(5, 'خصومات الموظفين'),
(6, 'مرتبات مستحقة');
