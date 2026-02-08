-- Missing tables for Vision HR
-- Run via: mysql -u root vision_hr < inc/schema_missing.sql

-- Incentives table
CREATE TABLE IF NOT EXISTS `tblincentive` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `BranchID` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `Amount` decimal(12,2) DEFAULT 0,
  `Currency` varchar(10) DEFAULT 'SAR',
  `Reason` text DEFAULT NULL,
  `for_what` varchar(100) DEFAULT NULL,
  `extionsion` varchar(255) DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `AmountType` varchar(50) DEFAULT NULL,
  `monthly` tinyint(1) DEFAULT 0,
  `Status` tinyint(1) DEFAULT NULL,
  `who_add` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Leave requests table
CREATE TABLE IF NOT EXISTS `tblleave` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `BranchID` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `ClassificationID` int(11) DEFAULT NULL,
  `StartDate` date DEFAULT NULL,
  `EndDate` date DEFAULT NULL,
  `Days` int(11) DEFAULT 0,
  `Reason` text DEFAULT NULL,
  `Status` tinyint(1) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `reject_reason` text DEFAULT NULL,
  `FilePath` varchar(500) DEFAULT NULL,
  `who_add` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Resignation table
CREATE TABLE IF NOT EXISTS `tblresignation` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `Reason` text DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `CreatedDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `Status` tinyint(1) DEFAULT NULL,
  `FilePath` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dismissal table
CREATE TABLE IF NOT EXISTS `tbldismissal` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `Reason` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `CreatedDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `Status` tinyint(1) DEFAULT NULL,
  `FilePath` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Salary registration
CREATE TABLE IF NOT EXISTS `salary_registration` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `month` int(2) DEFAULT NULL,
  `year` int(4) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `total_amount` decimal(14,2) DEFAULT 0,
  `status` tinyint(1) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Salary details
CREATE TABLE IF NOT EXISTS `salary_details` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `registration_id` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `basic_salary` decimal(12,2) DEFAULT 0,
  `benefits` decimal(12,2) DEFAULT 0,
  `deductions` decimal(12,2) DEFAULT 0,
  `incentives` decimal(12,2) DEFAULT 0,
  `advances` decimal(12,2) DEFAULT 0,
  `net_salary` decimal(12,2) DEFAULT 0,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Shift settings
CREATE TABLE IF NOT EXISTS `shift_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shift_id` int(11) DEFAULT NULL,
  `late_tolerance` int(11) DEFAULT 15,
  `early_leave_tolerance` int(11) DEFAULT 15,
  `overtime_start_after` int(11) DEFAULT 30,
  `absent_after` int(11) DEFAULT 240,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Shift schedule
CREATE TABLE IF NOT EXISTS `shifts_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shift_id` int(11) DEFAULT NULL,
  `day_of_week` tinyint(1) DEFAULT NULL,
  `is_working` tinyint(1) DEFAULT 1,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insurance table
CREATE TABLE IF NOT EXISTS `tbinsurance` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Holidays table
CREATE TABLE IF NOT EXISTS `tblholidays` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `BranchID` int(11) DEFAULT NULL,
  `Name` varchar(255) DEFAULT NULL,
  `StartDate` date DEFAULT NULL,
  `EndDate` date DEFAULT NULL,
  `Days` int(11) DEFAULT 1,
  `Description` text DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Account guide (chart of accounts)
CREATE TABLE IF NOT EXISTS `tblaccountguide` (
  `AccountID` int(11) NOT NULL AUTO_INCREMENT,
  `AccountName` varchar(255) DEFAULT NULL,
  `AccountNumber` varchar(50) DEFAULT NULL,
  `ParentID` int(11) DEFAULT NULL,
  `AccountType` varchar(50) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  PRIMARY KEY (`AccountID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Address types
CREATE TABLE IF NOT EXISTS `tbladdress` (
  `AddressID` int(11) NOT NULL AUTO_INCREMENT,
  `AddressType` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`AddressID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Identity types
CREATE TABLE IF NOT EXISTS `tblidentitytypes` (
  `IDType` int(11) NOT NULL AUTO_INCREMENT,
  `TypeName` varchar(100) DEFAULT NULL,
  `AvailableFor` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`IDType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permissions table (if not exists)
CREATE TABLE IF NOT EXISTS `tblpermissions` (
  `PermID` int(11) NOT NULL AUTO_INCREMENT,
  `GroupID` int(11) DEFAULT NULL,
  `AppID` varchar(10) DEFAULT NULL,
  `PermName` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`PermID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Employee orders
CREATE TABLE IF NOT EXISTS `tblorders` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `OrderType` varchar(100) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Status` tinyint(1) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `FilePath` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure tblattendance has all needed columns
ALTER TABLE `tblattendance` ADD COLUMN IF NOT EXISTS `source` varchar(20) DEFAULT 'manual';
ALTER TABLE `tblattendance` ADD COLUMN IF NOT EXISTS `who_add` int(11) DEFAULT NULL;
ALTER TABLE `tblattendance` ADD COLUMN IF NOT EXISTS `lat` decimal(10,7) DEFAULT NULL;
ALTER TABLE `tblattendance` ADD COLUMN IF NOT EXISTS `lng` decimal(10,7) DEFAULT NULL;

-- Ensure leaveclassification has all needed columns  
ALTER TABLE `leaveclassification` ADD COLUMN IF NOT EXISTS `max_days` int(11) DEFAULT NULL;
ALTER TABLE `leaveclassification` ADD COLUMN IF NOT EXISTS `isaccept` tinyint(1) DEFAULT 1;
ALTER TABLE `leaveclassification` ADD COLUMN IF NOT EXISTS `chose` varchar(100) DEFAULT NULL;

-- Ensure tblbenefit has all needed columns
ALTER TABLE `tblbenefit` ADD COLUMN IF NOT EXISTS `beneft_type` varchar(50) DEFAULT NULL;
ALTER TABLE `tblbenefit` ADD COLUMN IF NOT EXISTS `monthly` tinyint(1) DEFAULT 0;
ALTER TABLE `tblbenefit` ADD COLUMN IF NOT EXISTS `who_add` int(11) DEFAULT NULL;

-- Ensure tbldeductions has all needed columns
ALTER TABLE `tbldeductions` ADD COLUMN IF NOT EXISTS `who_add` int(11) DEFAULT NULL;

-- Ensure tblempadvances has all needed columns
ALTER TABLE `tblempadvances` ADD COLUMN IF NOT EXISTS `description` text DEFAULT NULL;
ALTER TABLE `tblempadvances` ADD COLUMN IF NOT EXISTS `who_add` int(11) DEFAULT NULL;
ALTER TABLE `tblempadvances` ADD COLUMN IF NOT EXISTS `FilePath` varchar(500) DEFAULT NULL;
