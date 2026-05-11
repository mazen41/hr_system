-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 09, 2026 at 11:50 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `erp_hr3`
--

-- --------------------------------------------------------

--
-- Table structure for table `all_months`
--

CREATE TABLE `all_months` (
  `a_month_id` int(10) UNSIGNED NOT NULL,
  `a_month` varchar(20) NOT NULL,
  `a_month_ar` varchar(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `all_months`
--

INSERT INTO `all_months` (`a_month_id`, `a_month`, `a_month_ar`) VALUES
(1, 'January', 'يناير'),
(2, 'February', 'فبراير'),
(3, 'March', 'مارس'),
(4, 'April', 'ابريل'),
(5, 'May', 'مايو'),
(6, 'June', 'يونيو'),
(7, 'July', 'يوليو'),
(8, 'August', 'أغسطس'),
(9, 'September', 'سبتمبر'),
(10, 'October', 'أكتوبر'),
(11, 'November', 'نوفمبر'),
(12, 'December', 'ديسمبر');

-- --------------------------------------------------------

--
-- Table structure for table `apps`
--

CREATE TABLE `apps` (
  `AppID` varchar(5) NOT NULL,
  `AppName` varchar(50) NOT NULL,
  `Sort` smallint(6) NOT NULL,
  `IsRrequred` tinyint(1) DEFAULT NULL,
  `Disabled` tinyint(1) DEFAULT NULL,
  `is_private` tinyint(1) DEFAULT NULL,
  `AppIcon` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `apps`
--

INSERT INTO `apps` (`AppID`, `AppName`, `Sort`, `IsRrequred`, `Disabled`, `is_private`, `AppIcon`) VALUES
('ACC', 'الحسابات العامة', 70, NULL, NULL, NULL, 'money-check-alt'),
('BRA', 'الفروع', 100, 1, NULL, NULL, 'map'),
('CLI', 'العملاء', 40, NULL, NULL, NULL, 'user-friends'),
('DOC', 'إدارة المستندات', 160, NULL, 1, 1, 'file'),
('FIN', 'المالية', 60, NULL, NULL, NULL, 'money-bill'),
('GEN', 'الملف الشخصي', 90, 1, NULL, NULL, 'user-tie'),
('HR', 'الموارد البشرية', 90, NULL, NULL, NULL, 'user-cog'),
('ORD', 'الطلبات', 20, NULL, 1, NULL, 'signature'),
('POS', 'نقاط البيع', 30, NULL, NULL, NULL, 'cash-register'),
('PRO', 'المشاريع', 150, NULL, 1, 1, 'tasks'),
('PUR', 'المشتريات', 50, NULL, NULL, NULL, 'shipping-fast'),
('REP', 'التقارير', 110, NULL, NULL, NULL, 'chart-pie'),
('SAL', 'المبيعات', 20, NULL, NULL, NULL, 'shopping-cart'),
('SET', 'الاعدادات', 120, 1, NULL, NULL, 'cog'),
('STO', 'المخزون', 10, NULL, NULL, NULL, 'th'),
('USR', 'المستخدمين', 80, NULL, NULL, NULL, 'users');

-- --------------------------------------------------------

--
-- Table structure for table `attendancet`
--

CREATE TABLE `attendancet` (
  `ID` int(11) NOT NULL COMMENT 'الرقم',
  `EmpID` int(11) DEFAULT NULL COMMENT 'رقم الموظف',
  `who_add` int(11) DEFAULT NULL COMMENT 'اسم الجهاز',
  `Time` time DEFAULT NULL COMMENT 'وقت البصمة',
  `Type` int(11) DEFAULT NULL COMMENT '1- enter   2- out',
  `Date` date DEFAULT NULL COMMENT 'تاريخ البصمة'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `attendancet`
--

INSERT INTO `attendancet` (`ID`, `EmpID`, `who_add`, `Time`, `Type`, `Date`) VALUES
(1, 12, 1, '08:00:00', NULL, '2025-01-01'),
(2, 12, 1, '17:00:00', NULL, '2025-01-01'),
(3, 11, 1, '01:00:00', NULL, '2025-08-01'),
(4, 11, 1, '01:00:00', NULL, '2025-08-13'),
(5, 11, 1, '08:00:00', NULL, '2025-08-14'),
(6, 11, 1, '17:00:00', NULL, '2025-08-14'),
(7, 11, 9, '08:00:00', NULL, '2025-08-13'),
(8, 11, 9, '17:00:00', NULL, '2025-08-13'),
(9, 11, 1, '01:00:00', NULL, '2025-08-14'),
(10, 11, 1, '01:00:00', NULL, '2025-08-15'),
(11, 11, 1, '01:00:00', NULL, '2025-08-29'),
(12, 11, 1, '01:00:00', NULL, '2025-08-28'),
(13, 11, 1, '01:00:00', NULL, '2025-08-27'),
(14, 11, 1, '01:00:00', NULL, '2025-08-26'),
(15, 11, 1, '01:00:00', NULL, '2025-08-25'),
(16, 11, 1, '01:00:00', NULL, '2025-08-24'),
(17, 11, 1, '01:00:00', NULL, '2025-08-23'),
(18, 11, 1, '01:00:00', NULL, '2025-08-22'),
(19, 11, 1, '01:00:00', NULL, '2025-08-21'),
(20, 11, 1, '01:00:00', NULL, '2025-08-20'),
(21, 11, 1, '01:00:00', NULL, '2025-08-19'),
(22, 11, 1, '01:00:00', NULL, '2025-08-18'),
(23, 11, 1, '01:00:00', NULL, '2025-08-17'),
(24, 11, 1, '01:00:00', NULL, '2025-08-16'),
(25, 12, 9, '08:00:00', NULL, '2025-08-31'),
(26, 12, 9, '17:00:00', NULL, '2025-08-31'),
(27, 10, 1, '08:00:00', NULL, '2025-09-01'),
(28, 10, 1, '17:00:00', NULL, '2025-09-01'),
(29, 11, 1, '08:00:00', NULL, '2025-09-01'),
(30, 11, 1, '17:00:00', NULL, '2025-09-01'),
(31, 10, 1, '08:00:00', NULL, '2025-09-01'),
(32, 10, 1, '17:00:00', NULL, '2025-09-01'),
(33, 11, 1, '08:00:00', NULL, '2025-09-01'),
(34, 11, 1, '17:00:00', NULL, '2025-09-01'),
(35, 10, 1, '08:00:00', NULL, '2025-09-03'),
(36, 10, 1, '17:00:00', NULL, '2025-09-03'),
(37, 11, 1, '08:00:00', NULL, '2025-09-03'),
(38, 11, 1, '17:00:00', NULL, '2025-09-03'),
(39, 10, 1, '08:00:00', NULL, '2025-09-01'),
(40, 10, 1, '17:00:00', NULL, '2025-09-01'),
(41, 11, 1, '08:00:00', NULL, '2025-09-01'),
(42, 11, 1, '17:00:00', NULL, '2025-09-01'),
(43, 10, 1, '09:00:00', NULL, '2025-09-03'),
(44, 10, 1, '15:00:00', NULL, '2025-09-03'),
(45, 11, 1, '09:00:00', NULL, '2025-09-03'),
(46, 11, 1, '15:00:00', NULL, '2025-09-03');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `branch_id` int(11) NOT NULL,
  `is_local` tinyint(1) DEFAULT NULL,
  `local_setup_time` datetime DEFAULT NULL,
  `branch_ref` int(11) DEFAULT NULL,
  `business` varchar(15) DEFAULT NULL,
  `account` int(11) DEFAULT NULL,
  `branch_name` varchar(50) NOT NULL,
  `branch_country` varchar(3) DEFAULT 'SA',
  `branch_currency_id` varchar(3) NOT NULL DEFAULT 'SAR',
  `isdefault` tinyint(1) DEFAULT NULL,
  `branch_style` varchar(20) DEFAULT NULL,
  `isstopped` tinyint(1) DEFAULT NULL,
  `branch_address` int(11) DEFAULT NULL,
  `branch_admin` int(11) DEFAULT NULL,
  `created_user` int(11) DEFAULT NULL,
  `created_date` date DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`branch_id`, `is_local`, `local_setup_time`, `branch_ref`, `business`, `account`, `branch_name`, `branch_country`, `branch_currency_id`, `isdefault`, `branch_style`, `isstopped`, `branch_address`, `branch_admin`, `created_user`, `created_date`, `last_update`) VALUES
(1, NULL, NULL, 1, NULL, 1, 'شركة صدى الملاعب للملابس الرياضية', 'SA', 'SAR', 1, NULL, NULL, 29, 1, 0, '2023-10-19', '2025-05-14 21:48:25'),
(2, NULL, NULL, 2, NULL, 1, 'صدى الملاعب بريدة', 'SA', 'SAR', NULL, '#009688', NULL, 38, NULL, 1, '2025-02-13', '2025-09-01 21:46:33'),
(3, NULL, NULL, 3, NULL, 1, 'ركن ميلان', 'SA', 'SAR', NULL, '#28a745', NULL, 4, NULL, 1, '2025-02-13', '2025-05-07 14:04:01'),
(4, NULL, NULL, 4, NULL, 1, 'صدى الملاعب حفر الباطن', 'SA', 'SAR', NULL, '#ffc107', NULL, 5, NULL, 1, '2025-02-13', '2025-02-21 05:06:27'),
(5, NULL, NULL, 5, NULL, 1, 'صدى الملاعب تبوك', 'SA', 'SAR', NULL, '#9c27b0', NULL, 6, NULL, 1, '2025-02-13', '2025-02-25 22:38:30'),
(6, NULL, NULL, 6, NULL, 1, 'صدى الملاعب حايل', 'SA', 'SAR', NULL, '#6c757d', NULL, 23, NULL, 1, '2025-03-18', '2025-03-18 20:31:48'),
(7, NULL, NULL, 7, NULL, 1, 'صدى الملاعب الاحساء', 'SA', 'SAR', NULL, '#f06292', NULL, 39, NULL, 1, '2025-09-04', '2025-09-09 21:27:20');

-- --------------------------------------------------------

--
-- Table structure for table `emp_order`
--

CREATE TABLE `emp_order` (
  `Id` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `Status` int(11) DEFAULT NULL,
  `Draft` int(11) DEFAULT NULL,
  `isread` int(11) DEFAULT NULL,
  `title` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `CreatedDate` date DEFAULT NULL,
  `LastUpdateDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `emp_order`
--

INSERT INTO `emp_order` (`Id`, `UserID`, `BranchID`, `Status`, `Draft`, `isread`, `title`, `description`, `created_by`, `CreatedDate`, `LastUpdateDate`) VALUES
(2, 19, 1, 1, 1, 1, 'طلب طابعة', 'طابعة A4', 19, '2025-10-09', '2025-10-09 00:01:47');

-- --------------------------------------------------------

--
-- Table structure for table `emp_salary`
--

CREATE TABLE `emp_salary` (
  `Id` int(11) NOT NULL,
  `UserID` int(10) DEFAULT NULL,
  `incentive` varchar(100) DEFAULT NULL,
  `benefit` varchar(100) DEFAULT NULL,
  `advances` varchar(100) DEFAULT NULL,
  `deductions` varchar(100) DEFAULT NULL,
  `absent_salary` varchar(100) DEFAULT NULL,
  `net_salary` varchar(100) DEFAULT NULL,
  `end_salary` varchar(100) DEFAULT NULL,
  `month` int(30) DEFAULT NULL,
  `id_registration` int(10) DEFAULT NULL,
  `created_by` int(10) DEFAULT NULL,
  `created_date` date DEFAULT NULL,
  `lastupdatedate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `emp_salary`
--

INSERT INTO `emp_salary` (`Id`, `UserID`, `incentive`, `benefit`, `advances`, `deductions`, `absent_salary`, `net_salary`, `end_salary`, `month`, `id_registration`, `created_by`, `created_date`, `lastupdatedate`) VALUES
(3, 18, '0.00', '0.00', '0.00', '0.00', '3300.00', '4500.00', '1200.00', 9, 2, 9, '2025-10-09', '2025-10-08 23:57:02'),
(4, 19, '0.00', '0.00', '0.00', '0.00', '1466.67', '2000.00', '533.33', 9, 2, 9, '2025-10-09', '2025-10-08 23:57:02');

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `Id` int(11) NOT NULL,
  `Holiday_ID` int(11) NOT NULL,
  `BranchID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Start_date` date NOT NULL,
  `End_date` date NOT NULL,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDate` date NOT NULL,
  `LastUpdateDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`Id`, `Holiday_ID`, `BranchID`, `Name`, `Start_date`, `End_date`, `CreatedBy`, `CreatedDate`, `LastUpdateDate`) VALUES
(10, 1, 1, 'اجازة اسبوعية', '2025-01-01', '2026-01-01', 1, '2025-10-11', '2025-10-11 19:49:55'),
(11, 2, 1, 'اجازة اسبوعه 2', '2025-01-01', '2026-01-01', 1, '2025-10-11', '2025-10-11 19:54:42');

-- --------------------------------------------------------

--
-- Table structure for table `holidays_day`
--

CREATE TABLE `holidays_day` (
  `Id` int(11) NOT NULL,
  `HolidayID` int(11) NOT NULL,
  `Description` text NOT NULL,
  `Date` date NOT NULL,
  `CreatedDate` date NOT NULL,
  `LastUpdateDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `holidays_day`
--

INSERT INTO `holidays_day` (`Id`, `HolidayID`, `Description`, `Date`, `CreatedDate`, `LastUpdateDate`) VALUES
(368, 1, 'الجمعة', '2025-01-03', '2025-10-11', '2025-10-11 19:49:55'),
(369, 1, 'الجمعة', '2025-01-10', '2025-10-11', '2025-10-11 19:49:55'),
(370, 1, 'الجمعة', '2025-01-17', '2025-10-11', '2025-10-11 19:49:55'),
(371, 1, 'الجمعة', '2025-01-24', '2025-10-11', '2025-10-11 19:49:55'),
(372, 1, 'الجمعة', '2025-01-31', '2025-10-11', '2025-10-11 19:49:55'),
(373, 1, 'الجمعة', '2025-02-07', '2025-10-11', '2025-10-11 19:49:55'),
(374, 1, 'الجمعة', '2025-02-14', '2025-10-11', '2025-10-11 19:49:55'),
(375, 1, 'الجمعة', '2025-02-21', '2025-10-11', '2025-10-11 19:49:55'),
(376, 1, 'الجمعة', '2025-02-28', '2025-10-11', '2025-10-11 19:49:55'),
(377, 1, 'الجمعة', '2025-03-07', '2025-10-11', '2025-10-11 19:49:55'),
(378, 1, 'الجمعة', '2025-03-14', '2025-10-11', '2025-10-11 19:49:55'),
(379, 1, 'الجمعة', '2025-03-21', '2025-10-11', '2025-10-11 19:49:55'),
(380, 1, 'الجمعة', '2025-03-28', '2025-10-11', '2025-10-11 19:49:55'),
(381, 1, 'الجمعة', '2025-04-04', '2025-10-11', '2025-10-11 19:49:55'),
(382, 1, 'الجمعة', '2025-04-11', '2025-10-11', '2025-10-11 19:49:55'),
(383, 1, 'الجمعة', '2025-04-18', '2025-10-11', '2025-10-11 19:49:55'),
(384, 1, 'الجمعة', '2025-04-25', '2025-10-11', '2025-10-11 19:49:55'),
(385, 1, 'الجمعة', '2025-05-02', '2025-10-11', '2025-10-11 19:49:55'),
(386, 1, 'الجمعة', '2025-05-09', '2025-10-11', '2025-10-11 19:49:55'),
(387, 1, 'الجمعة', '2025-05-16', '2025-10-11', '2025-10-11 19:49:55'),
(388, 1, 'الجمعة', '2025-05-23', '2025-10-11', '2025-10-11 19:49:55'),
(389, 1, 'الجمعة', '2025-05-30', '2025-10-11', '2025-10-11 19:49:55'),
(390, 1, 'الجمعة', '2025-06-06', '2025-10-11', '2025-10-11 19:49:55'),
(391, 1, 'الجمعة', '2025-06-13', '2025-10-11', '2025-10-11 19:49:55'),
(392, 1, 'الجمعة', '2025-06-20', '2025-10-11', '2025-10-11 19:49:55'),
(393, 1, 'الجمعة', '2025-06-27', '2025-10-11', '2025-10-11 19:49:55'),
(394, 1, 'الجمعة', '2025-07-04', '2025-10-11', '2025-10-11 19:49:55'),
(395, 1, 'الجمعة', '2025-07-11', '2025-10-11', '2025-10-11 19:49:55'),
(396, 1, 'الجمعة', '2025-07-18', '2025-10-11', '2025-10-11 19:49:55'),
(397, 1, 'الجمعة', '2025-07-25', '2025-10-11', '2025-10-11 19:49:55'),
(398, 1, 'الجمعة', '2025-08-01', '2025-10-11', '2025-10-11 19:49:55'),
(399, 1, 'الجمعة', '2025-08-08', '2025-10-11', '2025-10-11 19:49:55'),
(400, 1, 'الجمعة', '2025-08-15', '2025-10-11', '2025-10-11 19:49:55'),
(401, 1, 'الجمعة', '2025-08-22', '2025-10-11', '2025-10-11 19:49:55'),
(402, 1, 'الجمعة', '2025-08-29', '2025-10-11', '2025-10-11 19:49:55'),
(403, 1, 'الجمعة', '2025-09-05', '2025-10-11', '2025-10-11 19:49:55'),
(404, 1, 'الجمعة', '2025-09-12', '2025-10-11', '2025-10-11 19:49:55'),
(405, 1, 'الجمعة', '2025-09-19', '2025-10-11', '2025-10-11 19:49:55'),
(406, 1, 'الجمعة', '2025-09-26', '2025-10-11', '2025-10-11 19:49:55'),
(407, 1, 'الجمعة', '2025-10-03', '2025-10-11', '2025-10-11 19:49:55'),
(408, 1, 'الجمعة', '2025-10-10', '2025-10-11', '2025-10-11 19:49:55'),
(409, 1, 'الجمعة', '2025-10-17', '2025-10-11', '2025-10-11 19:49:55'),
(410, 1, 'الجمعة', '2025-10-24', '2025-10-11', '2025-10-11 19:49:55'),
(411, 1, 'الجمعة', '2025-10-31', '2025-10-11', '2025-10-11 19:49:55'),
(412, 1, 'الجمعة', '2025-11-07', '2025-10-11', '2025-10-11 19:49:55'),
(413, 1, 'الجمعة', '2025-11-14', '2025-10-11', '2025-10-11 19:49:55'),
(414, 1, 'الجمعة', '2025-11-21', '2025-10-11', '2025-10-11 19:49:55'),
(415, 1, 'الجمعة', '2025-11-28', '2025-10-11', '2025-10-11 19:49:55'),
(416, 1, 'الجمعة', '2025-12-05', '2025-10-11', '2025-10-11 19:49:55'),
(417, 1, 'الجمعة', '2025-12-12', '2025-10-11', '2025-10-11 19:49:55'),
(418, 1, 'الجمعة', '2025-12-19', '2025-10-11', '2025-10-11 19:49:55'),
(419, 1, 'الجمعة', '2025-12-26', '2025-10-11', '2025-10-11 19:49:55'),
(420, 2, 'السبت', '2025-01-04', '2025-10-11', '2025-10-11 19:54:42'),
(421, 2, 'السبت', '2025-01-11', '2025-10-11', '2025-10-11 19:54:42'),
(422, 2, 'السبت', '2025-01-18', '2025-10-11', '2025-10-11 19:54:42'),
(423, 2, 'السبت', '2025-01-25', '2025-10-11', '2025-10-11 19:54:42'),
(424, 2, 'السبت', '2025-02-01', '2025-10-11', '2025-10-11 19:54:42'),
(425, 2, 'السبت', '2025-02-08', '2025-10-11', '2025-10-11 19:54:42'),
(426, 2, 'السبت', '2025-02-15', '2025-10-11', '2025-10-11 19:54:42'),
(427, 2, 'السبت', '2025-02-22', '2025-10-11', '2025-10-11 19:54:42'),
(428, 2, 'السبت', '2025-03-01', '2025-10-11', '2025-10-11 19:54:42'),
(429, 2, 'السبت', '2025-03-08', '2025-10-11', '2025-10-11 19:54:42'),
(430, 2, 'السبت', '2025-03-15', '2025-10-11', '2025-10-11 19:54:42'),
(431, 2, 'السبت', '2025-03-22', '2025-10-11', '2025-10-11 19:54:42'),
(432, 2, 'السبت', '2025-03-29', '2025-10-11', '2025-10-11 19:54:42'),
(433, 2, 'السبت', '2025-04-05', '2025-10-11', '2025-10-11 19:54:42'),
(434, 2, 'السبت', '2025-04-12', '2025-10-11', '2025-10-11 19:54:42'),
(435, 2, 'السبت', '2025-04-19', '2025-10-11', '2025-10-11 19:54:42'),
(436, 2, 'السبت', '2025-04-26', '2025-10-11', '2025-10-11 19:54:42'),
(437, 2, 'السبت', '2025-05-03', '2025-10-11', '2025-10-11 19:54:42'),
(438, 2, 'السبت', '2025-05-10', '2025-10-11', '2025-10-11 19:54:42'),
(439, 2, 'السبت', '2025-05-17', '2025-10-11', '2025-10-11 19:54:42'),
(440, 2, 'السبت', '2025-05-24', '2025-10-11', '2025-10-11 19:54:42'),
(441, 2, 'السبت', '2025-05-31', '2025-10-11', '2025-10-11 19:54:42'),
(442, 2, 'السبت', '2025-06-07', '2025-10-11', '2025-10-11 19:54:42'),
(443, 2, 'السبت', '2025-06-14', '2025-10-11', '2025-10-11 19:54:42'),
(444, 2, 'السبت', '2025-06-21', '2025-10-11', '2025-10-11 19:54:42'),
(445, 2, 'السبت', '2025-06-28', '2025-10-11', '2025-10-11 19:54:42'),
(446, 2, 'السبت', '2025-07-05', '2025-10-11', '2025-10-11 19:54:42'),
(447, 2, 'السبت', '2025-07-12', '2025-10-11', '2025-10-11 19:54:42'),
(448, 2, 'السبت', '2025-07-19', '2025-10-11', '2025-10-11 19:54:42'),
(449, 2, 'السبت', '2025-07-26', '2025-10-11', '2025-10-11 19:54:42'),
(450, 2, 'السبت', '2025-08-02', '2025-10-11', '2025-10-11 19:54:42'),
(451, 2, 'السبت', '2025-08-09', '2025-10-11', '2025-10-11 19:54:42'),
(452, 2, 'السبت', '2025-08-16', '2025-10-11', '2025-10-11 19:54:42'),
(453, 2, 'السبت', '2025-08-23', '2025-10-11', '2025-10-11 19:54:42'),
(454, 2, 'السبت', '2025-08-30', '2025-10-11', '2025-10-11 19:54:42'),
(455, 2, 'السبت', '2025-09-06', '2025-10-11', '2025-10-11 19:54:42'),
(456, 2, 'السبت', '2025-09-13', '2025-10-11', '2025-10-11 19:54:42'),
(457, 2, 'السبت', '2025-09-20', '2025-10-11', '2025-10-11 19:54:42'),
(458, 2, 'السبت', '2025-09-27', '2025-10-11', '2025-10-11 19:54:42'),
(459, 2, 'السبت', '2025-10-04', '2025-10-11', '2025-10-11 19:54:42'),
(460, 2, 'السبت', '2025-10-11', '2025-10-11', '2025-10-11 19:54:42'),
(461, 2, 'السبت', '2025-10-18', '2025-10-11', '2025-10-11 19:54:42'),
(462, 2, 'السبت', '2025-10-25', '2025-10-11', '2025-10-11 19:54:42'),
(463, 2, 'السبت', '2025-11-01', '2025-10-11', '2025-10-11 19:54:42'),
(464, 2, 'السبت', '2025-11-08', '2025-10-11', '2025-10-11 19:54:42'),
(465, 2, 'السبت', '2025-11-15', '2025-10-11', '2025-10-11 19:54:42'),
(466, 2, 'السبت', '2025-11-22', '2025-10-11', '2025-10-11 19:54:42'),
(467, 2, 'السبت', '2025-11-29', '2025-10-11', '2025-10-11 19:54:42'),
(468, 2, 'السبت', '2025-12-06', '2025-10-11', '2025-10-11 19:54:42'),
(469, 2, 'السبت', '2025-12-13', '2025-10-11', '2025-10-11 19:54:42'),
(470, 2, 'السبت', '2025-12-20', '2025-10-11', '2025-10-11 19:54:42'),
(471, 2, 'السبت', '2025-12-27', '2025-10-11', '2025-10-11 19:54:42');

-- --------------------------------------------------------

--
-- Table structure for table `leaveclassification`
--

CREATE TABLE `leaveclassification` (
  `Id` int(11) NOT NULL,
  `BranchID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text NOT NULL,
  `isaccept` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `state` int(11) DEFAULT NULL,
  `for_what` int(11) DEFAULT NULL,
  `chose` text DEFAULT NULL,
  `AmountType` varchar(20) DEFAULT NULL,
  `Amount` int(11) DEFAULT NULL,
  `RequiresAttachment` int(11) NOT NULL,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDate` date NOT NULL,
  `LastUpdateDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `leaveclassification`
--

INSERT INTO `leaveclassification` (`Id`, `BranchID`, `Name`, `Description`, `isaccept`, `type`, `state`, `for_what`, `chose`, `AmountType`, `Amount`, `RequiresAttachment`, `CreatedBy`, `CreatedDate`, `LastUpdateDate`) VALUES
(9, 1, 'اجازة مرضية', '', 1, 1, NULL, 2, '3', 'avg', NULL, 1, 1, '2025-10-09', '2025-10-08 23:14:28'),
(10, 1, 'اجازة نصف فترة', '', 1, 2, NULL, NULL, NULL, 'avg', 50, 2, 1, '2025-10-09', '2025-10-08 23:15:53'),
(11, 1, 'اجازة يوم كامل', '', 1, 3, NULL, NULL, NULL, 'avg', NULL, 2, 1, '2025-10-09', '2025-10-08 23:17:35');

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `menu_id` int(11) NOT NULL,
  `AppID` varchar(5) NOT NULL,
  `icon` text NOT NULL,
  `sort` int(11) NOT NULL,
  `menu_name` varchar(255) NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT 0 COMMENT '0 if menu is root level or menuid if this is child on any menu',
  `link` varchar(255) NOT NULL,
  `status` enum('0','1') NOT NULL DEFAULT '1' COMMENT '0 for disabled menu or 1 for enabled menu',
  `dropdown` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`menu_id`, `AppID`, `icon`, `sort`, `menu_name`, `parent_id`, `link`, `status`, `dropdown`) VALUES
(1, 'GEN', 'nav-icon fas fa-tachometer-alt', 10, 'لوحة التحكم', 0, 'dashboard', '1', '0'),
(2, 'STO', 'nav-icon fas fa-th', 20, 'المخزون', 0, '#', '1', '1'),
(3, 'STO', 'far fa-circle nav-icon', 5, 'إدارة المنتجات', 2, 'products-list', '1', '0'),
(4, 'STO', 'far fa-circle nav-icon', 10, 'الأذون المخزنية', 2, 'requisition-list', '1', '0'),
(5, 'STO', 'far fa-circle nav-icon', 40, 'المستودعات', 2, 'stores-list', '1', '0'),
(7, 'STO', 'far fa-circle nav-icon', 60, 'إعدادات المخزون', 2, 'inventory-settings', '1', '0'),
(8, 'STO', 'far fa-circle nav-icon', 70, 'إعدادات المنتجات', 2, 'product-settings', '1', '0'),
(9, 'SAL', 'nav-icon fas fa-shopping-cart', 30, 'المبيعات', 0, '#', '1', '1'),
(10, 'SAL', 'far fa-circle nav-icon', 90, 'إدارة الفواتير', 9, 'invoices-list', '1', '0'),
(11, 'SAL', 'far fa-circle nav-icon', 100, 'إنشاء فاتورة', 9, 'invoice-add', '1', '0'),
(12, 'SAL', 'far fa-circle nav-icon', 110, 'مدفوعات العملاء', 9, 'payments', '0', '0'),
(55, 'SAL', 'far fa-circle nav-icon', 105, 'إشعارات دائنة', 9, 'invoice-creditnote-list', '1', '0'),
(56, 'SAL', 'far fa-circle nav-icon', 140, 'فواتير مرتجعة', 9, 'invoice-refund-list', '1', '0'),
(57, 'POS', 'nav-icon fas fa-cash-register', 40, 'نقاط البيع', 0, '#', '1', '1'),
(58, 'POS', 'far fa-circle nav-icon', 160, 'بدء عملية البيع', 57, 'pos-start', '1', '0'),
(59, 'POS', 'far fa-circle nav-icon', 170, 'جلسات البيع', 57, 'pos-sessions', '1', '0'),
(60, 'POS', 'far fa-circle nav-icon', 180, 'إعدادات نقاط البيع', 57, 'pos-settings', '1', '0'),
(61, 'PUR', 'nav-icon fas fa-shipping-fast', 50, 'المشتريات', 0, '#', '1', '1'),
(62, 'PUR', 'far fa-circle nav-icon', 10, 'إدارة فواتير الشراء', 61, 'purchase-list', '1', '0'),
(63, 'PUR', 'far fa-circle nav-icon', 20, 'مرتجعات المشتريات', 61, 'purchase-refund-list', '1', '0'),
(64, 'PUR', 'far fa-circle nav-icon', 30, 'إدارة الموردين', 61, 'purchase-suppliers', '1', '0'),
(66, 'PUR', 'far fa-circle nav-icon', 50, 'إعدادات المشتريات', 61, 'purchase-settings', '1', '0'),
(67, 'REP', 'nav-icon fas fa-chart-pie', 70, 'التقارير', 0, 'report-center', '1', '0'),
(68, 'GEN', 'nav-icon fas fa-user-tie', 300, 'ملف المستخدم', 0, 'user-profile', '0', '0'),
(69, 'GEN', 'nav-icon fas fa-sign-out-alt', 310, 'تسجيل الخروج', 0, 'logout', '0', '0'),
(70, 'SET', 'nav-icon fas fa-cog', 100, 'الاعدادات', 0, '#', '1', '1'),
(71, 'SET', 'far fa-circle nav-icon', 250, 'الاعدادات العامة', 70, 'settings', '0', '0'),
(72, 'POS', 'far fa-circle nav-icon', 242, 'إدارة نقاط البيع', 70, 'points', '0', '0'),
(73, 'SET', 'far fa-circle nav-icon', 60, 'معلومات الحساب', 70, 'settings-account-info', '0', '0'),
(86, 'ACC', 'nav-icon fas fa-money-check-alt', 55, 'الحسابات العامة', 0, '#', '1', '1'),
(87, 'ACC', 'far fa-circle nav-icon', 551, 'دليل الحسابات', 86, 'accountant-coa', '1', '0'),
(88, 'ACC', 'far fa-circle nav-icon', 552, 'القيود البسيطة', 86, 'accountant-simple-entries', '0', '0'),
(89, 'ACC', 'far fa-circle nav-icon', 553, 'مراكز التكلفة', 86, 'costcenter-coa', '1', '0'),
(90, 'ACC', 'far fa-circle nav-icon', 554, 'إعدادات الحسابات العامة', 86, 'accountant-settings', '1', '0'),
(91, 'FIN', 'nav-icon fas fa-money-bill', 52, 'المالية', 0, '#', '1', '1'),
(92, 'FIN', 'far fa-circle nav-icon', 5, 'سند قبض', 91, 'financial-incomes-list', '1', '0'),
(93, 'FIN', 'far fa-circle nav-icon', 10, 'سند صرف', 91, 'testsss', '0', '0'),
(94, 'FIN', 'far fa-circle nav-icon', 15, 'خزائن وحسابات بنكية', 91, 'financial-treasuries', '1', '0'),
(95, 'FIN', 'far fa-circle nav-icon', 20, 'إعدادات المالية', 91, 'financial-settings', '1', '0'),
(96, 'REP', 'far fa-circle nav-icon', 70, 'تقارير الحسابات', 67, 'reports-account-statement', '0', '0'),
(97, 'REP', 'far fa-circle nav-icon', 61, 'تقارير أخرى', 67, 'reports', '0', '0'),
(98, 'ACC', 'far fa-circle nav-icon', 550, 'القيود اليومية', 86, 'accountant-journals-list', '1', '0'),
(99, 'BRA', 'nav-icon fas fa-map', 80, 'الفروع', 0, '#', '1', '1'),
(100, 'BRA', 'far fa-circle nav-icon', 75, 'إدارة الفروع', 99, 'branches-list', '1', '0'),
(101, 'USR', 'nav-icon fas fa-users', 60, 'المستخدمين', 0, '#', '1', '1'),
(102, 'USR', 'far fa-circle nav-icon', 62, 'إدارة المستخدمين', 101, 'users-list', '1', '0'),
(103, 'BRA', 'far fa-circle nav-icon', 80, 'إعدادت الفروع', 99, '', '0', '0'),
(104, 'USR', 'far fa-circle nav-icon', 65, 'الأدوار الوظيفية', 101, 'users-group-list', '1', '0'),
(105, 'SET', 'far fa-circle nav-icon', 65, 'إعدادت الجهة', 70, 'settings-account', '1', '0'),
(106, 'SET', 'far fa-circle nav-icon', 70, 'إدارة التطبيقات', 70, 'settings-apps', '1', '0'),
(107, 'USR', 'far fa-circle nav-icon', 67, 'إدارة الجلسات', 101, 'users-session-list', '1', '0'),
(108, 'SET', 'far fa-circle nav-icon', 70, 'إعدادت الترقيم', 70, 'settings-numbering', '1', '0'),
(109, 'CLI', 'nav-icon fas fa-user-friends', 45, 'العملاء', 0, '#', '1', '1'),
(110, 'CLI', 'far fa-circle nav-icon', 5, 'إدارة العملاء', 109, 'client-list', '1', '0'),
(111, 'CLI', 'far fa-circle nav-icon', 10, 'إضافة عميل', 109, 'client-add', '1', '0'),
(112, 'CLI', 'far fa-circle nav-icon', 15, 'إعدادت العملاء', 109, 'client-settings', '1', '0'),
(113, 'SET', 'far fa-circle nav-icon', 75, 'إعدادت الضرائب', 70, 'settings-tax', '1', '0'),
(114, 'SAL', 'far fa-circle nav-icon', 130, 'عروض الأسعار', 9, 'invoices-estimates-list', '1', '0'),
(115, 'FIN', 'far fa-circle nav-icon', 7, 'سند صرف', 91, 'financial-outcomes-list', '1', '0'),
(116, 'STO', 'far fa-circle nav-icon', 7, 'إضافة منتج', 2, 'products-add', '1', '0'),
(117, 'SAL', 'far fa-circle nav-icon', 150, 'إعدادات الفواتير', 9, 'invoice-settings', '1', '0'),
(119, 'ORD', 'nav-icon fas fa-file-signature', 22, 'الطلبات', 0, '#', '0', '1'),
(120, 'ORD', 'far fa-circle nav-icon', 2, 'طلب جديد', 119, 'orders-add', '0', '0'),
(121, 'ORD', 'far fa-circle nav-icon', 5, 'إعدادات الطلبات', 119, 'orders-settings', '0', '0'),
(122, 'ORD', 'far fa-circle nav-icon', 1, 'إدارة الطلبات', 119, 'orders-list', '0', '0'),
(124, 'BRA', 'far fa-circle nav-icon', 80, 'إعدادات الفروع', 99, 'branches-settings', '0', '0'),
(128, 'SET', 'far fa-circle nav-icon', 80, 'وسائل الدفع', 70, 'payment-method', '1', '0'),
(130, 'STO', 'far fa-circle nav-icon', 58, 'إدارة الجرد', 2, 'stockings-list', '0', '0'),
(131, 'STO', 'far fa-circle nav-icon', 7, 'طباعة باركوود', 2, 'barcodes-generator', '1', '0'),
(132, 'SAL', 'far fa-circle nav-icon', 200, 'الربط والتكامل مع الهيئة', 9, 'zatca', '1', '0'),
(133, 'HR', 'nav-icon fas fa-user-cog', 70, 'إدارة الموارد البشرية', 0, '#', '1', '1'),
(135, 'PRO', 'nav-icon fas fa-tasks', 50, 'إدارة المشاريع', 0, '#', '1', '1'),
(136, 'PRO', 'far fa-circle nav-icon', 10, 'المشاريع', 135, 'project-list', '1', '0'),
(137, 'PRO', 'far fa-circle nav-icon', 50, 'إعدادات المشاريع', 135, 'project-settings', '1', '0'),
(138, 'PRO', 'far fa-circle nav-icon', 20, 'المهام', 135, 'task-list', '1', '0'),
(139, 'DOC', 'nav-icon fas fa-file', 50, 'إدارة المستندات', 0, '#', '1', '1'),
(140, 'DOC', 'far fa-circle nav-icon', 20, 'المستكشف', 139, 'document-list', '1', '0'),
(141, 'HR', 'far fa-circle nav-icon', 79, 'المكافاّت', 133, 'incentive-list', '1', '0'),
(144, 'HR', 'far fa-circle nav-icon', 81, 'التعويضات والمزايا', 133, 'Benefits-list', '1', '0'),
(145, 'HR', 'far fa-circle nav-icon', 82, 'الخصومات', 133, 'deductions-list', '1', '0'),
(146, 'HR', 'far fa-circle nav-icon', 83, 'الترقيات', 133, 'promotion-list', '1', '0'),
(147, 'HR', 'far fa-circle nav-icon', 84, 'تجديد العقود', 133, 'contractRenewal-list', '1', '0'),
(148, 'HR', 'far fa-circle nav-icon', 85, 'السلف', 133, 'EmpAdvances-list-admin', '1', '0'),
(149, 'HR', 'far fa-circle nav-icon', 86, ' الاستقالات ', 133, 'resignation-list-admin', '1', '0'),
(150, 'HR', 'far fa-circle nav-icon', 87, 'إدارة الاجازات', 133, 'leaveRequest-list-admin', '1', '0'),
(151, 'HR', 'far fa-circle nav-icon', 70, 'ادارة الموظفين', 133, 'employer-list', '1', '0'),
(152, 'HR', 'far fa-circle nav-icon', 76, 'كشف الحضور', 133, 'reveal-attendance', '1', '0'),
(153, 'HR', 'far fa-circle nav-icon', 200, ' الاعدادات', 133, 'hr-setting', '1', '0'),
(154, 'HR', 'far fa-circle nav-icon', 199, 'اعدادات الموظفين', 133, 'emp-setting', '1', '0'),
(155, 'HR', 'far fa-circle nav-icon', 88, 'إدارة الطلبات ', 133, 'order-emp-list-admin', '1', '0'),
(156, 'HR', 'far fa-circle nav-icon', 88, 'طلبات نسيان البصمة', 133, 'finger-forget-list-admin', '1', '0'),
(157, 'HR', 'far fa-circle nav-icon', 76, 'إصدار الرواتب', 133, 'Issuing-salaries-list', '1', '0');

-- --------------------------------------------------------

--
-- Table structure for table `order_finger_add`
--

CREATE TABLE `order_finger_add` (
  `Id` int(11) NOT NULL,
  `BranchID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `num_finger` tinyint(4) DEFAULT NULL,
  `time` time DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `isdraft` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `name_type` text DEFAULT NULL,
  `created_by` tinyint(4) DEFAULT NULL,
  `CreatedDate` date DEFAULT NULL,
  `LastUpdateDate` year(4) DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `order_finger_add`
--

INSERT INTO `order_finger_add` (`Id`, `BranchID`, `UserID`, `date`, `num_finger`, `time`, `status`, `isdraft`, `description`, `name_type`, `created_by`, `CreatedDate`, `LastUpdateDate`) VALUES
(1, 1, 11, '2025-08-01', 1, '01:00:00', 1, 1, NULL, 'حضور', 11, '2025-08-31', '2025'),
(2, 1, 11, '2025-08-13', 1, '01:00:00', 1, 1, NULL, 'حضور', 11, '2025-08-31', '2025'),
(3, 1, 11, '2025-08-14', 1, '01:00:00', 1, 1, NULL, 'حضور', 11, '2025-08-31', '2025'),
(4, 1, 11, '2025-08-15', 1, '01:00:00', 1, 1, NULL, 'حضور', 11, '2025-08-31', '2025'),
(5, 1, 11, '2025-08-16', 1, '01:00:00', 1, 1, NULL, 'حضور', 11, '2025-08-31', '2025'),
(6, 1, 11, '2025-08-17', 1, '01:00:00', 1, 1, NULL, 'حضور', 11, '2025-08-31', '2025'),
(7, 1, 11, '2025-08-18', 1, '01:00:00', 1, 1, NULL, 'حضور', 11, '2025-08-31', '2025'),
(8, 1, 11, '2025-08-19', 1, '01:00:00', 1, 1, NULL, 'حضور', 11, '2025-08-31', '2025'),
(9, 1, 11, '2025-08-20', 1, '01:00:00', 1, 1, NULL, 'حضور', 11, '2025-08-31', '2025'),
(10, 1, 11, '2025-08-21', 1, '01:00:00', 1, 1, NULL, 'حضور', 11, '2025-08-31', '2025'),
(11, 1, 11, '2025-08-22', 1, '01:00:00', 1, 1, NULL, 'حضور', 11, '2025-08-31', '2025'),
(12, 1, 11, '2025-08-23', 1, '01:00:00', 1, 1, NULL, 'حضور', 11, '2025-08-31', '2025'),
(13, 1, 11, '2025-08-24', 1, '01:00:00', 1, 1, NULL, 'حضور', 11, '2025-08-31', '2025'),
(14, 1, 11, '2025-08-25', 1, '01:00:00', 1, 1, NULL, 'حضور', 11, '2025-08-31', '2025'),
(15, 1, 11, '2025-08-26', 1, '01:00:00', 1, 1, NULL, 'حضور', 11, '2025-08-31', '2025'),
(16, 1, 11, '2025-08-27', 1, '01:00:00', 1, 1, NULL, 'حضور', 11, '2025-08-31', '2025'),
(17, 1, 11, '2025-08-28', 1, '01:00:00', 1, 1, NULL, 'حضور', 11, '2025-08-31', '2025'),
(18, 1, 11, '2025-08-29', 1, '01:00:00', 1, 1, NULL, 'حضور', 11, '2025-08-31', '2025'),
(19, 1, 13, '2025-10-01', 1, '01:00:00', NULL, 1, NULL, 'حضور', 13, '2025-10-09', '2025'),
(20, 1, 19, '2025-10-01', 2, '01:00:00', NULL, 1, NULL, 'حضور', 19, '2025-10-09', '2025');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` tinyint(4) NOT NULL,
  `app` varchar(5) NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent` tinyint(4) DEFAULT NULL,
  `sort` tinyint(4) DEFAULT NULL,
  `icon` varchar(20) DEFAULT NULL,
  `url` varchar(100) DEFAULT NULL,
  `stopped` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `app`, `name`, `parent`, `sort`, `icon`, `url`, `stopped`) VALUES
(1, 'STO', 'تقارير المخزون', 0, 1, 'dolly', '#', NULL),
(2, 'SAL', 'تقارير المبيعات', 0, 2, 'shopping-cart', '#', NULL),
(3, 'CLI', 'تقارير العملاء', 0, 3, 'user-friends', '#', NULL),
(4, 'PUR', 'تقارير المشتريات', 0, 4, 'shipping-fast', '#', NULL),
(5, 'ACC', 'تقارير الحسابات', 0, 5, 'money-check-alt', '#', NULL),
(6, 'STO', 'ملخص عمليات المخزون', 1, 1, NULL, 'report-store-summary\r\n', NULL),
(7, 'STO', 'الحركة التفصيلية للمخزون\r\n', 1, 1, NULL, 'report-stock-transactions\r\n', NULL),
(8, 'ACC', 'كشف حساب\r\n', 5, 1, NULL, 'reports-account-statement', NULL),
(9, 'STO', 'ملخص رصيد المخازن', 1, 1, NULL, 'report-store-balance', NULL),
(10, 'STO', 'القيمة التقديرية للمخزون', 1, 1, NULL, 'report-store-estimated-value', NULL),
(11, 'CLI', 'خارطة العملاء', 3, 25, NULL, 'report-clients-map', NULL),
(12, 'ACC', 'ميزان المراجعة - أرصدة', 5, 2, NULL, 'reports-account-balance', NULL),
(13, 'ACC', 'ميزان المراجعة - مجاميع وارصدة', 5, 3, NULL, 'reports-account-balance2', NULL),
(14, 'PUR', 'فواتير المشتريات الضريبية ', 4, 30, NULL, 'reports-pur-invs', NULL),
(15, 'PUR', 'المشتريات حسب الشهر - تفصيلي', 4, 25, NULL, 'reports-pur-items?groupby=month', NULL),
(16, 'PUR', 'المشتريات حسب السنة - تفصيلي', 4, 20, NULL, 'reports-pur-items?groupby=year', NULL),
(17, 'PUR', 'المشتريات حسب الفرع - تفصيلي', 4, 15, NULL, 'reports-pur-items?groupby=branch', NULL),
(18, 'PUR', 'المشتريات حسب المورد - تفصيلي', 4, 10, NULL, 'reports-pur-items?groupby=supplyer', NULL),
(19, 'PUR', 'المشتريات حسب المنتج - تفصيلي', 4, 5, NULL, 'reports-pur-items?groupby=products', NULL),
(20, 'SAL', 'خلاصة حركة المبيعات اليوم', 2, 1, NULL, 'report-sales-by-date', NULL),
(21, 'SAL', 'المبيعات حسب المنتج - تفصيلي', 2, 5, NULL, 'reports-sal-items?groupby=products', NULL),
(22, 'SAL', 'المبيعات حسب العميل - تفصيلي', 2, 10, NULL, 'reports-sal-items?groupby=client', NULL),
(23, 'SAL', 'المبيعات حسب الفرع - تفصيلي', 2, 15, NULL, 'reports-sal-items?groupby=branch', NULL),
(24, 'SAL', 'المبيعات بحسب التصنيف', 2, 1, NULL, '#', 1),
(25, 'SAL', 'منتجات راكدة', 2, 1, NULL, '#', 1),
(26, 'CLI', 'دليل العملاء', 3, 5, NULL, 'reports-clients', NULL),
(27, 'CLI', 'أرصدة العملاء', 3, 10, NULL, 'reports-client-balance', NULL),
(28, 'CLI', 'مدفوعات العملاء للفواتير', 3, 15, NULL, 'reports-clients-payments', NULL),
(29, 'ACC', 'حساب الاستاذ\r\n', 5, 1, NULL, 'reports-journal-trs', NULL),
(30, 'SAL', 'فواتير المبيعات الضريبية', 2, 30, NULL, 'reports-sal-invs', NULL),
(31, 'STO', 'تقارير المعرض', 0, 1, 'calendar', '#', 1),
(32, 'SAL', 'التقرير اليومي', 31, 0, NULL, 'invoices/tailor_daily_report.php', 1),
(33, 'SAL', 'التقرير اليومي', 31, 0, NULL, 'invoices/tailor_daily_report.php', NULL),
(34, 'SAL', 'أرباح المبيعات', 2, 35, NULL, 'reports-sal-profit', NULL),
(35, 'SAL', 'المبيعات حسب السنة - تفصيلي', 2, 20, NULL, 'reports-sal-items?groupby=year', NULL),
(36, 'SAL', 'المبيعات حسب الشهر - تفصيلي', 2, 25, NULL, 'reports-sal-items?groupby=month', NULL),
(37, 'CLI', 'المبيعات للعملاء - تفصيلي', 3, 7, NULL, 'reports-clients-invs', NULL),
(38, 'STO', 'تقرير جرد مستودع', 1, 1, NULL, 'report-sealer-store', NULL),
(39, 'SAL', 'مدفوعات وتحصيلات العملاء حسب البائع', 2, 1, NULL, 'report-payments-by-user', NULL),
(40, 'STO', 'تقرير الأسعار والكميات', 1, 0, NULL, 'products-prices-report', 1),
(41, 'ACC', 'قائمة الدخل', 5, 5, NULL, 'reports-income', NULL),
(42, 'ACC', 'تقرير الإقرار الضريبي', 5, 4, NULL, 'report-tax-decision', NULL),
(43, 'HR', 'تقارير الموظفين', 0, 1, 'user-friends', '#', NULL),
(44, 'HR', 'جميع الموظفين', 43, 1, NULL, 'report-all-emplyer', NULL),
(45, 'HR', 'تقرير تفصيلي لموظف', 43, 2, NULL, 'report-one-empyer', NULL),
(50, 'HR', 'المكافئات / الاستقطاعات', 0, 2, 'as fa-award', '#', NULL),
(51, 'HR', 'تقرير المكافئات', 50, 1, NULL, 'report-incentive', NULL),
(52, 'HR', 'تقرير العويضات والمزياء', 50, 2, NULL, 'report-benefits', NULL),
(53, 'HR', 'تقرير الخصومات', 50, 2, NULL, 'report-deductions', NULL),
(60, 'HR', 'تقارير الاعدادات', 0, 2, 'fa fa-cog', '#', NULL),
(61, 'HR', 'تقرير الفترات', 60, 1, NULL, 'report-shift', NULL),
(62, 'HR', 'تقرير اجهزة البصمة', 60, 1, NULL, 'report-fingerprint', NULL),
(63, 'HR', 'تقرير الاقسام ', 60, 3, NULL, 'report-section', NULL),
(64, 'HR', 'تقرير المسميات الوظيفية ', 60, 4, NULL, 'report-jobtitle', NULL),
(65, 'HR', 'تقرير شركات التأمين ', 60, 5, NULL, 'report-insurance', NULL),
(66, 'HR', 'تقرير المجموعات', 60, 6, NULL, 'report-group', NULL),
(67, 'HR', 'تقرير الدرجات الوظيفية', 60, 7, NULL, 'report-jobgrade', NULL),
(68, 'HR', 'تقرير أنماط العمل', 60, 8, NULL, 'report-emplyements', NULL),
(69, 'HR', 'تقرير الإجازات الرسمية', 60, 9, NULL, 'report-holidays', NULL),
(100, 'HR', 'سلف الموظفين', 50, 3, NULL, 'report-empAdvances', NULL),
(101, 'HR', 'الاستقالات', 50, 4, NULL, 'report-resignation', NULL),
(102, 'HR', 'طلبات الاجازات', 50, 5, NULL, 'report-leaveRequest', NULL),
(127, 'HR', 'رواتب الموظفين', 43, 3, NULL, 'report-export-salarys', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `salary_registration`
--

CREATE TABLE `salary_registration` (
  `Id` int(11) NOT NULL,
  `registration_id` int(10) DEFAULT NULL,
  `registration_id_end` int(11) DEFAULT NULL,
  `month` int(10) DEFAULT NULL,
  `year` varchar(20) DEFAULT NULL,
  `BranchID` text DEFAULT NULL,
  `created_by` int(10) DEFAULT NULL,
  `created_date` date DEFAULT NULL,
  `lastupdatedate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `salary_registration`
--

INSERT INTO `salary_registration` (`Id`, `registration_id`, `registration_id_end`, `month`, `year`, `BranchID`, `created_by`, `created_date`, `lastupdatedate`) VALUES
(1, 2291, 2292, 8, '2025', '1', 1, '2025-09-01', '2025-08-31 23:51:10'),
(2, 2293, 2294, 9, '2025', '1', 9, '2025-10-09', '2025-10-08 23:57:02');

-- --------------------------------------------------------

--
-- Table structure for table `setting_account_salary`
--

CREATE TABLE `setting_account_salary` (
  `Id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `account_name` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` date DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `setting_account_salary`
--

INSERT INTO `setting_account_salary` (`Id`, `account_id`, `account_name`, `created_by`, `created_date`, `last_update`) VALUES
(1, 68, '(3110001#) - مرتبات وأجور', 1, '2025-08-31', '2025-08-30 23:04:38'),
(2, 499, '(3110015#) - مكافاءت وهداية ونسب عاملين', 1, '2025-08-31', '2025-08-30 23:04:38'),
(3, 503, '(3110016#) - تعويضات الموظفين', 1, '2025-08-31', '2025-08-30 23:04:38'),
(4, 504, '(123320001#) - سلف الموظفين', 1, '2025-08-31', '2025-08-30 23:04:38'),
(5, 501, '(2620002#) - خصومات الموظفين', 1, '2025-08-31', '2025-08-30 23:04:38'),
(6, 413, '(2620001#) - مرتبات الموظفين المستحقة', 1, '2025-08-31', '2025-08-30 23:04:38');

-- --------------------------------------------------------

--
-- Table structure for table `shifts_schedule`
--

CREATE TABLE `shifts_schedule` (
  `Id` int(11) NOT NULL,
  `shift_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `total_work_hour` time DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` date DEFAULT NULL,
  `last_update_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `shifts_schedule`
--

INSERT INTO `shifts_schedule` (`Id`, `shift_id`, `start_date`, `end_date`, `start_time`, `end_time`, `total_work_hour`, `created_by`, `created_date`, `last_update_date`) VALUES
(8, 2, '2025-01-01', '2025-12-31', '08:00:00', '12:00:00', '04:00:00', 1, '2025-10-09', '2025-10-08 23:00:35'),
(9, 3, '2025-01-01', '2026-01-01', '08:00:00', '17:00:00', '09:00:00', 1, '2025-10-09', '2025-10-08 23:21:14');

-- --------------------------------------------------------

--
-- Table structure for table `shift_setting`
--

CREATE TABLE `shift_setting` (
  `id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `allowed_late_minutes` varchar(20) DEFAULT NULL COMMENT 'دقائق التأخير المسموح بها	',
  `enable_half_day` int(11) DEFAULT NULL COMMENT 'تمكين نصف دوام عند التأخير	',
  `half_day_minutes` varchar(20) DEFAULT NULL COMMENT 'وقت بداية اعتبار نصف دوام	',
  `absent_minutes` varchar(20) DEFAULT NULL COMMENT 'وقت بداية اعتبار غياب	',
  `allowed_early_leave` varchar(20) DEFAULT NULL COMMENT 'دقائق الانصراف المبكر المسموح بها	',
  `enable_early_half_day` int(11) DEFAULT NULL COMMENT 'تمكين نصف دوام عند الانصراف المبكر	',
  `early_half_day_minutes` varchar(20) DEFAULT NULL COMMENT 'بداية اعتبار نصف دوام (قبل نهاية الدوام بـ)	',
  `early_absent_minutes` varchar(20) DEFAULT NULL COMMENT 'بداية اعتبار غياب (قبل نهاية الدوام بـ)	',
  `missing_checkout_action` int(11) DEFAULT NULL COMMENT 'الإجراء عند عدم تسجيل الانصراف	',
  `late_checkout_policy` int(11) DEFAULT NULL COMMENT 'سياسة تسجيل الانصراف بعد نهاية الدوام	',
  `created_by` int(11) DEFAULT NULL,
  `created_date` date DEFAULT NULL,
  `last_update` time NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `shift_setting`
--

INSERT INTO `shift_setting` (`id`, `shift_id`, `allowed_late_minutes`, `enable_half_day`, `half_day_minutes`, `absent_minutes`, `allowed_early_leave`, `enable_early_half_day`, `early_half_day_minutes`, `early_absent_minutes`, `missing_checkout_action`, `late_checkout_policy`, `created_by`, `created_date`, `last_update`) VALUES
(2, 2, '5', 1, NULL, NULL, '1', 1, NULL, NULL, 1, 1, 1, '2025-10-09', '02:00:35'),
(3, 3, '5', 1, NULL, NULL, '1', 1, NULL, NULL, 1, 1, 1, '2025-10-09', '02:21:14');

-- --------------------------------------------------------

--
-- Table structure for table `tbfingerprint`
--

CREATE TABLE `tbfingerprint` (
  `FingerprintID` int(11) NOT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `FingerprintName` varchar(100) DEFAULT NULL,
  `FingerprintType` varchar(100) DEFAULT NULL,
  `FingerprintState` int(11) DEFAULT NULL,
  `FingerprintSerailnumber` varchar(100) DEFAULT NULL,
  `ip` text DEFAULT NULL,
  `port` text DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedDate` date NOT NULL DEFAULT current_timestamp(),
  `lastUpdateDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tbfingerprint`
--

INSERT INTO `tbfingerprint` (`FingerprintID`, `BranchID`, `FingerprintName`, `FingerprintType`, `FingerprintState`, `FingerprintSerailnumber`, `ip`, `port`, `CreatedBy`, `CreatedDate`, `lastUpdateDate`) VALUES
(2, 1, 'جهاز بصمة1', '', 1, '', NULL, NULL, 1, '2025-10-09', '2025-10-08 23:01:38');

-- --------------------------------------------------------

--
-- Table structure for table `tbinsurance`
--

CREATE TABLE `tbinsurance` (
  `Id` int(11) NOT NULL,
  `BranchID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Phone` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Address` varchar(100) NOT NULL,
  `type` int(11) NOT NULL,
  `NameOfRepresentative` varchar(100) NOT NULL,
  `state` int(11) NOT NULL,
  `Note` text NOT NULL,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDate` date NOT NULL,
  `LastUpdateDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tbinsurance`
--

INSERT INTO `tbinsurance` (`Id`, `BranchID`, `Name`, `Phone`, `Email`, `Address`, `type`, `NameOfRepresentative`, `state`, `Note`, `CreatedBy`, `CreatedDate`, `LastUpdateDate`) VALUES
(2, 1, 'شركة تامين 1', '', '', '', 2, 'سليمان علي', 1, '', 1, '2025-10-09', '2025-10-08 23:04:36');

-- --------------------------------------------------------

--
-- Table structure for table `tblaccountguide`
--

CREATE TABLE `tblaccountguide` (
  `AccountID` bigint(20) NOT NULL,
  `AccountNumber` bigint(20) NOT NULL,
  `ParentNumber` bigint(20) NOT NULL,
  `AccountName` varchar(250) NOT NULL,
  `AccountType` tinyint(1) NOT NULL,
  `IsSystem` tinyint(1) DEFAULT NULL,
  `UserID` int(11) NOT NULL,
  `BranchID` int(11) NOT NULL,
  `RowTime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblaccountguide`
--

INSERT INTO `tblaccountguide` (`AccountID`, `AccountNumber`, `ParentNumber`, `AccountName`, `AccountType`, `IsSystem`, `UserID`, `BranchID`, `RowTime`) VALUES
(1, 1, 0, 'الأصول', 1, 1, 0, 1, '2022-06-18 04:19:46'),
(2, 2, 0, 'الخصوم', 1, 1, 0, 1, '2022-06-18 04:19:46'),
(3, 3, 0, 'المصروفات', 1, 1, 0, 1, '2022-06-18 04:19:46'),
(4, 4, 0, 'الإيرادات', 1, 1, 0, 1, '2022-06-18 04:19:46'),
(5, 5, 0, 'رأس المال وحقوق الملكية', 1, 1, 0, 1, '2022-06-18 04:52:31'),
(9, 11, 1, 'الأصول الثابتة', 1, 1, 0, 1, '2022-06-18 04:19:46'),
(10, 12, 1, 'الأصول المتداولة', 1, 0, 41, 1, '2022-06-18 04:34:43'),
(11, 111, 9, 'الاراضي', 1, 0, 41, 1, '2022-06-18 04:37:28'),
(12, 112, 9, 'المباني', 1, 0, 41, 1, '2022-06-18 04:37:46'),
(13, 113, 9, 'السيارات', 1, 0, 41, 1, '2022-06-18 04:37:54'),
(14, 114, 9, 'الاثاث والتجهيزات', 1, 0, 41, 1, '2022-06-18 04:38:04'),
(15, 115, 9, 'نفقات التأسيس', 1, 0, 41, 1, '2022-06-18 04:38:16'),
(16, 116, 9, 'مشاريع تحت التنفيذ', 1, 0, 41, 1, '2022-06-18 04:38:32'),
(17, 121, 10, 'الأموال الجاهزة', 1, 0, 41, 1, '2022-06-18 04:38:48'),
(18, 122, 10, 'المدينون - الزبائن', 1, 0, 41, 1, '2022-06-18 04:38:56'),
(19, 123, 10, 'المدينون', 1, 0, 41, 1, '2022-06-18 04:39:04'),
(20, 124, 10, 'المخزون', 1, 0, 41, 1, '2022-06-18 04:39:11'),
(21, 125, 10, 'عهد الموظفين', 1, 0, 41, 1, '2022-06-18 04:39:23'),
(22, 1211, 17, 'الصناديق', 1, 0, 41, 1, '2022-06-18 04:40:24'),
(23, 1212, 17, 'البنوك', 1, 0, 41, 1, '2022-06-18 04:40:34'),
(24, 1213, 17, 'الشيكات', 1, 0, 41, 1, '2022-06-18 04:40:45'),
(28, 12130001, 24, 'شيكات تحت التحصيل', 0, 0, 41, 1, '2022-06-18 04:45:30'),
(29, 12130002, 24, 'شيكات مؤجلة الدفع', 0, 0, 41, 1, '2022-06-18 04:45:54'),
(31, 1231, 19, 'مدينون افراد', 1, 0, 41, 1, '2022-06-18 04:49:22'),
(32, 1232, 19, 'مدينون شركات', 1, 0, 41, 1, '2022-06-18 04:49:32'),
(33, 1233, 19, 'العمال والموظفون', 1, 0, 41, 1, '2022-06-18 04:49:38'),
(34, 1234, 19, 'مسحوبات شخصية', 1, 0, 41, 1, '2022-06-18 04:49:58'),
(35, 1235, 19, 'ديون مشكوك في تحصيلها', 1, 0, 41, 1, '2022-06-18 04:50:07'),
(36, 1236, 19, 'مدينون آخرون', 1, 0, 41, 1, '2022-06-18 04:50:15'),
(37, 12331, 33, 'جاري العمال والموظفين', 1, 0, 41, 1, '2022-06-18 04:50:36'),
(38, 12332, 33, 'سلف العمال والموظفين', 1, 0, 41, 1, '2022-06-18 04:50:44'),
(39, 12333, 33, 'عهد العمال والموظفين', 1, 0, 41, 1, '2022-06-18 04:50:53'),
(42, 1240001, 20, 'مخزون أول الفترة', 0, 0, 41, 1, '2022-06-18 04:52:02'),
(43, 1240002, 20, 'مخزون آخر الفترة', 0, 0, 41, 1, '2022-06-18 04:52:14'),
(45, 22, 2, 'المخصصات', 1, 0, 41, 1, '2022-06-18 04:52:39'),
(46, 23, 2, 'الدائنون', 1, 0, 41, 1, '2022-06-18 04:52:47'),
(47, 24, 2, 'حسابات دائنة أخرى', 1, 0, 41, 1, '2022-06-18 04:52:54'),
(48, 25, 2, 'حساب النتائج', 1, 0, 41, 1, '2022-06-18 04:53:03'),
(50, 50002, 5, 'حساب صاحب المنشاة', 0, 0, 41, 1, '2022-06-18 04:54:10'),
(51, 221, 45, 'مخصص اهلاك الاصول الثابتة', 1, 0, 41, 1, '2022-06-18 04:54:55'),
(52, 222, 45, 'مخصص الديون المشكوك في تحصيلها', 1, 0, 41, 1, '2022-06-18 04:55:34'),
(53, 2210001, 51, 'مخصص اهلاك المباني', 0, 0, 41, 1, '2022-06-18 04:56:02'),
(54, 2210002, 51, 'مخصص اهلاك السيارات', 0, 0, 41, 1, '2022-06-18 04:56:20'),
(55, 2210003, 51, 'مخصص اهلاك الاثاث والتجهيزات', 0, 0, 41, 1, '2022-06-18 04:56:36'),
(62, 250002, 48, 'صافي الدخل', 0, 0, 41, 1, '2022-06-18 05:00:36'),
(63, 250003, 48, 'حساب الأرباح والخسائر', 0, 0, 41, 1, '2022-06-18 05:00:46'),
(64, 31, 3, 'مصروفات إدارية وعمومية', 1, 0, 41, 1, '2022-06-18 05:01:10'),
(65, 32, 3, 'صافي المشتريات', 1, 0, 41, 1, '2022-06-18 05:01:19'),
(66, 311, 64, 'مصروفات إدارية', 1, 0, 41, 1, '2022-06-18 05:01:53'),
(67, 312, 64, 'مصروفات عمومية', 1, 0, 41, 1, '2022-06-18 05:01:59'),
(68, 3110001, 66, 'مرتبات وأجور', 0, 0, 41, 1, '2022-06-18 05:02:25'),
(69, 3110002, 66, 'البدلات', 0, 0, 41, 1, '2022-06-18 05:02:33'),
(70, 3110003, 66, 'العمل الاضافي', 0, 0, 41, 1, '2022-06-18 05:02:39'),
(71, 3110004, 66, 'التأمينات', 0, 0, 41, 1, '2022-06-18 05:02:47'),
(72, 3110005, 66, 'ضريبة كسب العمل', 0, 0, 41, 1, '2022-06-18 05:02:57'),
(73, 3110006, 66, 'نقل وانتقالات', 0, 0, 41, 1, '2022-06-18 05:03:05'),
(74, 3110007, 66, 'قطع غيار وصيانة ووقود وزيوت', 0, 0, 41, 1, '2022-06-18 05:03:14'),
(75, 3110008, 66, 'ضيافات واستقبالات', 0, 0, 41, 1, '2022-06-18 05:03:21'),
(76, 3110009, 66, 'تبرعات واعانات ومساعدات', 0, 0, 41, 1, '2022-06-18 05:03:28'),
(77, 311010, 66, 'الديون المعدومة', 0, 0, 41, 1, '2022-06-18 05:03:37'),
(78, 3110011, 66, 'الخسائر الراسمالية', 0, 0, 41, 1, '2022-06-18 05:04:21'),
(79, 3110012, 66, 'مصروفات ادارية اخرى', 0, 0, 41, 1, '2022-06-18 05:04:42'),
(80, 3120001, 67, 'عمولات الوكلاء المدينة', 0, 0, 41, 1, '2022-06-18 05:06:06'),
(81, 3120002, 67, 'كهرباء ومياه', 0, 0, 41, 1, '2022-06-18 05:06:17'),
(82, 3120003, 67, 'قرطاسية ومطبوعات', 0, 0, 41, 1, '2022-06-18 05:06:31'),
(83, 3120004, 67, 'هاتف وفاكس وبريد', 0, 0, 41, 1, '2022-06-18 05:06:42'),
(84, 3120005, 67, 'الايجارات', 0, 0, 41, 1, '2022-06-18 05:06:51'),
(85, 3120006, 67, 'الضرائب والرسوم والزكوات', 0, 0, 41, 1, '2022-06-18 05:07:01'),
(86, 3120007, 67, 'الاهلاكات', 0, 0, 41, 1, '2022-06-18 05:07:06'),
(87, 3120008, 67, 'عمولات بنكية مدينة', 0, 0, 41, 1, '2022-06-18 05:07:15'),
(88, 3120009, 67, 'مصروفات عمومية اخرى', 0, 0, 41, 1, '2022-06-18 05:07:23'),
(89, 3120010, 67, 'دعاية واعلان', 0, 0, 41, 1, '2022-06-18 05:07:35'),
(90, 320001, 65, 'غير معروف مشتريات', 0, 0, 41, 1, '2022-06-18 05:09:16'),
(91, 320002, 65, 'مردود المشتريات', 0, 0, 41, 1, '2022-06-18 05:09:27'),
(92, 320003, 65, 'المشتريات', 0, 0, 41, 1, '2022-06-18 05:09:37'),
(93, 320004, 65, 'الأصناف التالفة', 0, 0, 41, 1, '2022-06-18 05:09:46'),
(94, 41, 4, 'ايرادات غير معروف', 1, 0, 41, 1, '2022-06-18 05:10:45'),
(95, 42, 4, 'إيرادات أخرى', 1, 0, 41, 1, '2022-06-18 05:10:53'),
(96, 43, 4, 'صافي المبيعات', 1, 0, 41, 1, '2022-06-18 05:11:05'),
(97, 420001, 95, 'فوارق صرف عملة', 0, 0, 41, 1, '2022-06-18 05:11:51'),
(98, 420002, 95, 'فوارق القبض النقدي', 0, 0, 41, 1, '2022-06-18 05:11:59'),
(99, 420003, 95, 'فوارق الصرف النقدي', 0, 0, 41, 1, '2022-06-18 05:12:07'),
(100, 420004, 95, 'فارق جرد مخزني', 0, 0, 41, 1, '2022-06-18 05:12:15'),
(101, 420005, 95, 'تركيب الأصناف', 0, 0, 41, 1, '2022-06-18 05:12:26'),
(102, 420006, 95, 'تسوية مخزون الأصناف', 0, 0, 41, 1, '2022-06-18 05:12:34'),
(103, 430001, 96, 'مردود المبيعات', 0, 0, 41, 1, '2022-06-18 05:13:05'),
(104, 430002, 96, 'المبيعات', 0, 0, 41, 1, '2022-06-18 05:13:11'),
(105, 430003, 96, 'تكلفة المبيعات', 0, 0, 41, 1, '2022-06-18 05:13:29'),
(106, 430004, 96, 'مخزون البضاعة', 0, 0, 41, 1, '2022-06-18 05:13:35'),
(212, 12360002, 36, 'أطراف مدينة أخرى', 0, 0, 41, 1, '2022-06-29 23:20:18'),
(354, 2610001, 385, 'ضريبة القيمة المضافة المحصلة', 0, 0, 1, 1, '2022-09-05 17:01:44'),
(356, 2610002, 385, 'ضريبة القيمة المضافة المدفوعة', 0, 0, 1, 1, '2022-09-05 17:03:43'),
(357, 2340002, 47, 'تسويات ضريبة القيمة المضافة', 0, 0, 1, 1, '2022-09-05 17:04:43'),
(378, 2340003, 47, 'أطراف دائنة أخرى', 0, 0, 1, 1, '2022-09-15 23:48:22'),
(379, 125001, 21, 'عهدة نقاط البيع', 0, 0, 1, 1, '2022-10-05 02:45:59'),
(380, 33, 3, 'تكاليف المبيعات', 1, 0, 1, 1, '2022-11-24 09:30:05'),
(381, 330001, 380, 'تكلفة المبيعات -', 0, 0, 1, 1, '2022-11-24 09:31:23'),
(382, 330002, 380, 'شحن مشتريات', 0, 0, 1, 1, '2022-11-24 09:31:39'),
(383, 330003, 380, 'خصم مسموح به', 0, 0, 1, 1, '2022-11-24 09:31:51'),
(384, 26, 2, 'الخصوم المتداولة', 1, NULL, 1, 1, '2023-08-13 21:08:58'),
(385, 261, 384, 'ضرائب القيمة المضافة (المطلوبة)', 1, NULL, 1, 1, '2023-08-13 21:12:27'),
(386, 1240003, 20, 'المستودع الرئيسي', 0, NULL, 1, 1, '2024-01-17 02:41:31'),
(387, 125002, 21, 'مدير النظام', 0, 1, 1, 1, '2024-01-17 02:42:37'),
(388, 12110001, 22, 'الخزينة الرئيسية', 0, 1, 1, 1, '2024-01-17 02:44:18'),
(389, 12120001, 23, 'بنك الراجحي', 0, 1, 1, 1, '2024-01-17 02:45:03'),
(390, 126, 10, 'العجز & الزيادة', 1, 0, 1, 1, '2024-03-23 22:08:21'),
(391, 1260001, 390, 'عجز & زيادة الصندوق', 0, 0, 1, 1, '2024-03-23 22:08:21'),
(392, 12110002, 22, 'خزينة بريدة', 0, 1, 1, 2, '2025-02-13 10:50:11'),
(393, 12120002, 23, 'بنك هلا بريدة', 0, 1, 1, 2, '2025-02-13 10:50:52'),
(394, 1240004, 20, 'مستودع بريدة BURIDAH', 0, NULL, 1, 2, '2025-02-13 10:52:43'),
(395, 1240005, 20, 'مستودع الحفر', 0, NULL, 1, 4, '2025-02-13 10:58:31'),
(396, 12110003, 22, 'خزينة الحفر HAFER', 0, 1, 1, 4, '2025-02-13 10:59:12'),
(397, 12120003, 23, 'بنك الحفر', 0, 1, 1, 4, '2025-02-13 11:01:18'),
(398, 125003, 21, 'ابو عبدالوهاب', 0, 1, 3, 2, '2025-02-14 18:39:57'),
(399, 125004, 21, 'محمد خليل', 0, 1, 4, 4, '2025-02-21 16:11:47'),
(402, 230001, 46, 'بضاعة مصر', 0, NULL, 1, 1, '2025-02-22 19:02:51'),
(403, 1220001, 18, 'نقدي', 0, NULL, 1, 5, '2025-02-25 17:39:16'),
(404, 1240006, 20, 'مستودع تبوك TABUK', 0, NULL, 1, 5, '2025-02-25 17:41:36'),
(405, 125005, 21, 'حكيم الامير', 0, 1, 6, 5, '2025-02-25 17:53:13'),
(406, 12110004, 22, 'خزينة صدى تبوك', 0, NULL, 1, 5, '2025-03-01 17:27:15'),
(407, 12120004, 23, 'بنك تبوك', 0, NULL, 1, 5, '2025-03-01 17:27:58'),
(408, 230002, 46, 'بضاعة تركي عمر', 0, NULL, 1, 1, '2025-03-02 23:05:47'),
(409, 230003, 46, 'مؤسسة ابهار', 0, NULL, 1, 1, '2025-03-05 23:57:39'),
(410, 123310001, 37, 'اكرم عبدالوهاب', 0, NULL, 1, 2, '2025-03-10 15:32:29'),
(411, 123310002, 37, 'سمير شعيب', 0, NULL, 1, 2, '2025-03-10 15:32:43'),
(412, 262, 384, 'مصروفات مستحقة', 1, NULL, 1, 2, '2025-03-10 15:38:55'),
(413, 2620001, 412, 'مرتبات الموظفين المستحقة', 0, NULL, 1, 2, '2025-03-10 15:40:18'),
(414, 123310003, 37, 'معمر', 0, NULL, 1, 2, '2025-03-10 15:59:05'),
(415, 123310004, 37, 'عبدالرحمن', 0, NULL, 1, 2, '2025-03-10 16:00:22'),
(416, 123310005, 37, 'علاء النعماني', 0, NULL, 1, 2, '2025-03-10 16:00:47'),
(417, 123310006, 37, 'عباس الشميري', 0, NULL, 1, 2, '2025-03-10 16:02:01'),
(418, 123310007, 37, 'ارشاد الهندي', 0, NULL, 1, 2, '2025-03-10 16:02:19'),
(419, 123310008, 37, 'نور البنجالي', 0, NULL, 1, 2, '2025-03-10 16:02:38'),
(420, 123310009, 37, 'عادل الهندي', 0, NULL, 1, 2, '2025-03-10 16:03:28'),
(421, 123310010, 37, 'غازي', 0, NULL, 1, 2, '2025-03-10 16:03:40'),
(422, 123310011, 37, 'منور الفضلي', 0, NULL, 1, 2, '2025-03-10 16:03:55'),
(423, 123310012, 37, 'مرزوق', 0, NULL, 1, 2, '2025-03-10 16:04:31'),
(424, 123310013, 37, 'فهد', 0, NULL, 1, 2, '2025-03-10 16:04:42'),
(425, 123310014, 37, 'خالد ياسين', 0, NULL, 1, 2, '2025-03-10 16:04:57'),
(426, 123310015, 37, 'محمد المصعبي', 0, NULL, 1, 2, '2025-03-10 16:05:08'),
(427, 123310016, 37, 'محمدخليل', 0, NULL, 1, 1, '2025-03-10 16:31:58'),
(428, 123310017, 37, 'احمد الواسعي', 0, NULL, 1, 1, '2025-03-10 16:32:13'),
(429, 123310018, 37, 'هارون', 0, NULL, 1, 1, '2025-03-10 16:32:27'),
(430, 123310019, 37, 'وليد', 0, NULL, 1, 1, '2025-03-10 16:32:40'),
(431, 123310020, 37, 'حسن الهندي', 0, NULL, 1, 1, '2025-03-10 16:32:52'),
(432, 123310021, 37, 'عيد الحربي', 0, NULL, 1, 1, '2025-03-10 16:33:31'),
(433, 123310022, 37, 'عبدالحكيم الاميري', 0, NULL, 1, 1, '2025-03-10 16:33:53'),
(434, 123310023, 37, 'اياد الرديني', 0, NULL, 1, 1, '2025-03-10 16:34:11'),
(435, 123310024, 37, 'فيصل الهدادي', 0, NULL, 1, 1, '2025-03-10 16:34:25'),
(436, 123310025, 37, 'هشام الهدادي', 0, NULL, 1, 1, '2025-03-10 16:34:44'),
(437, 123310026, 37, 'رياض الهندي', 0, NULL, 1, 1, '2025-03-10 16:35:28'),
(438, 123310027, 37, 'مروان البعداني', 0, NULL, 1, 1, '2025-03-10 16:35:47'),
(439, 123310028, 37, 'فيصل الرشيدي', 0, NULL, 1, 1, '2025-03-10 16:36:11'),
(440, 230004, 46, 'مصطفى الرصيص', 0, NULL, 1, 1, '2025-03-11 06:19:31'),
(441, 230005, 46, 'ازهار دبي', 0, NULL, 1, 1, '2025-03-12 03:21:53'),
(442, 230006, 46, 'مصطفى ج', 0, NULL, 1, 1, '2025-03-15 03:32:09'),
(443, 50003, 5, 'المدير العام', 0, NULL, 1, 1, '2025-03-15 03:33:18'),
(444, 230007, 46, 'المهري شرابات', 0, NULL, 1, 1, '2025-03-15 22:49:14'),
(445, 230008, 46, 'علوان خطوة شباب', 0, NULL, 1, 1, '2025-03-15 23:14:01'),
(446, 230009, 46, 'كشخة', 0, NULL, 1, 1, '2025-03-15 23:14:21'),
(447, 230010, 46, 'شهاب احمد المهندس', 0, NULL, 1, 1, '2025-03-15 23:14:39'),
(448, 230011, 46, 'سليم البنجالي', 0, NULL, 1, 1, '2025-03-15 23:15:09'),
(449, 230012, 46, 'عارف البنجالي', 0, NULL, 1, 1, '2025-03-15 23:15:22'),
(450, 230013, 46, 'نبيل الديكورات', 0, NULL, 1, 1, '2025-03-15 23:15:46'),
(451, 230014, 46, 'احمد عبده ناجي', 0, NULL, 1, 1, '2025-03-15 23:16:09'),
(452, 230015, 46, 'خليل عبدالولي', 0, NULL, 1, 1, '2025-03-15 23:16:52'),
(453, 230016, 46, 'ياسر الهدادي', 0, NULL, 1, 1, '2025-03-15 23:17:07'),
(454, 230017, 46, 'رعد الرديني', 0, NULL, 1, 1, '2025-03-15 23:17:23'),
(455, 230018, 46, 'الفاتح', 0, NULL, 1, 1, '2025-03-15 23:17:40'),
(456, 230019, 46, 'النبراس', 0, NULL, 1, 1, '2025-03-15 23:17:53'),
(457, 230020, 46, 'صدى الاسعار', 0, NULL, 1, 1, '2025-03-15 23:18:07'),
(458, 230021, 46, 'عبدالخالق', 0, NULL, 1, 1, '2025-03-15 23:19:08'),
(459, 230022, 46, 'الصيني مفيد', 0, NULL, 1, 1, '2025-03-15 23:19:24'),
(460, 123310029, 37, 'نديم الهندي', 0, NULL, 1, 2, '2025-03-16 16:48:44'),
(461, 12120005, 23, 'بنك هلا تبوك', 0, 1, 1, 5, '2025-03-16 22:06:20'),
(462, 230023, 46, 'شركة لمحة', 0, NULL, 1, 1, '2025-03-18 22:37:26'),
(463, 1220002, 18, 'نقدا1', 0, NULL, 1, 6, '2025-03-18 23:43:05'),
(464, 1240007, 20, 'مستودع حايل HAIL', 0, NULL, 1, 6, '2025-03-18 23:45:25'),
(465, 12110005, 22, 'خزينة حايل', 0, 1, 1, 6, '2025-03-19 00:07:31'),
(466, 12120006, 23, 'بنك حايل هلا', 0, 1, 1, 6, '2025-03-19 00:07:57'),
(467, 125006, 21, 'معمر القذافي', 0, 1, 7, 6, '2025-03-25 20:38:42'),
(468, 230024, 46, 'اتجاه واحد WO', 0, NULL, 1, 1, '2025-04-03 10:35:36'),
(469, 230025, 46, 'ابو عمار بضاعة', 0, NULL, 1, 1, '2025-04-07 20:09:05'),
(470, 12310001, 31, 'وليد شبيكان', 0, NULL, 1, 1, '2025-04-08 18:44:05'),
(471, 123310030, 37, 'عبدالهادي الامير', 0, NULL, 1, 2, '2025-04-23 17:55:17'),
(472, 1140001, 14, 'سلم حديد', 0, NULL, 1, 1, '2025-05-06 20:56:09'),
(473, 1140002, 14, 'مبز خشبي', 0, NULL, 1, 1, '2025-05-06 20:56:27'),
(474, 117, 9, 'اجهزة و معدات', 1, NULL, 1, 1, '2025-05-06 20:57:05'),
(475, 1170001, 474, 'جهاز لابتوب', 0, NULL, 1, 1, '2025-05-06 20:57:25'),
(476, 1170002, 474, 'طابعة باركود', 0, NULL, 1, 1, '2025-05-06 20:57:52'),
(477, 1170003, 474, 'طابعة hp ملون', 0, NULL, 1, 1, '2025-05-06 20:58:17'),
(478, 1150001, 15, 'ديكور المستودع', 0, NULL, 1, 1, '2025-05-06 20:58:40'),
(479, 50004, 5, 'راس المال', 0, NULL, 1, 1, '2025-05-06 21:01:06'),
(480, 1240008, 20, 'مستودع ميلانو', 0, NULL, 1, 3, '2025-05-07 10:04:45'),
(481, 118, 9, 'ادوات سلامة', 1, NULL, 1, 2, '2025-05-07 19:22:31'),
(482, 1180001, 481, 'كامرة و شاشة عرض', 0, NULL, 1, 2, '2025-05-07 19:22:47'),
(483, 1140003, 14, 'ستاند', 0, NULL, 1, 2, '2025-05-07 19:23:49'),
(484, 1140004, 14, 'اصنام عرض', 0, NULL, 1, 2, '2025-05-07 19:24:05'),
(485, 1140005, 14, 'مكيف دولاب', 0, NULL, 1, 2, '2025-05-07 19:24:22'),
(486, 1140006, 14, 'مكيف اسبليت', 0, NULL, 1, 2, '2025-05-07 19:24:37'),
(487, 1140007, 14, 'معالق', 0, NULL, 1, 2, '2025-05-07 19:24:48'),
(488, 1170004, 474, 'جهاز كمبيوتر+طابعة', 0, NULL, 1, 2, '2025-05-07 19:25:21'),
(489, 1170005, 474, 'مكينة خياطة', 0, NULL, 1, 2, '2025-05-07 19:25:36'),
(490, 1170006, 474, 'كاوية', 0, NULL, 1, 2, '2025-05-07 19:25:50'),
(491, 1150002, 15, 'ديكور مستودع المحل', 0, NULL, 1, 2, '2025-05-07 19:26:23'),
(492, 1150003, 15, 'ديكور المحل', 0, NULL, 1, 2, '2025-05-07 19:26:34'),
(493, 1140008, 14, 'ميز زجاج', 0, NULL, 1, 2, '2025-05-07 19:29:20'),
(494, 3120011, 67, 'مخالفات مرورية', 0, NULL, 1, 1, '2025-05-18 22:00:06'),
(495, 3120012, 67, 'رسوم حكومية', 0, NULL, 1, 1, '2025-05-18 22:00:57'),
(496, 3110013, 66, 'مصروفات خروج وعودة', 0, NULL, 1, 1, '2025-05-18 22:01:47'),
(497, 3110014, 66, 'تجديد اقامات ونقل كفالات', 0, NULL, 1, 4, '2025-05-19 18:19:06'),
(498, 230026, 46, 'عبدالعزيز المهري', 0, NULL, 1, 1, '2025-05-27 20:13:01'),
(499, 3110015, 66, 'مكافاءت وهداية ونسب عاملين', 0, NULL, 1, 1, '2025-05-28 21:34:59'),
(500, 1220003, 18, 'محل ميلانو', 0, NULL, 1, 1, '2025-06-03 20:17:51'),
(501, 2620002, 412, 'خصومات الموظفين', 0, NULL, 1, 2, NULL),
(503, 3110016, 66, 'تعويضات الموظفين', 0, NULL, 1, 1, '2025-08-06 00:20:58'),
(504, 123320001, 38, 'سلف الموظفين', 0, NULL, 1, 1, '2025-08-06 00:21:40');

-- --------------------------------------------------------

--
-- Table structure for table `tbladdress`
--

CREATE TABLE `tbladdress` (
  `AddressID` int(11) NOT NULL,
  `AddressType` enum('MAIN','BRANCH','CLIENT','SUPPLYER','ZATCA') NOT NULL,
  `SourceID` int(11) DEFAULT NULL,
  `AddressTitle` varchar(100) NOT NULL,
  `Street` varchar(100) DEFAULT NULL,
  `Block` varchar(50) DEFAULT NULL,
  `City` varchar(30) DEFAULT NULL,
  `Building` varchar(30) DEFAULT NULL,
  `Phone` varchar(15) DEFAULT NULL,
  `Mobile` varchar(15) DEFAULT NULL,
  `ZipCode` varchar(10) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `VatNumber` varchar(30) DEFAULT NULL,
  `VatGNumber` varchar(30) DEFAULT NULL,
  `IdentityType` varchar(6) DEFAULT NULL,
  `IdentityDetail` varchar(25) DEFAULT NULL,
  `CreatedDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `UserID` int(11) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `LastUpdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tbladdress`
--

INSERT INTO `tbladdress` (`AddressID`, `AddressType`, `SourceID`, `AddressTitle`, `Street`, `Block`, `City`, `Building`, `Phone`, `Mobile`, `ZipCode`, `Email`, `VatNumber`, `VatGNumber`, `IdentityType`, `IdentityDetail`, `CreatedDate`, `UserID`, `BranchID`, `LastUpdate`) VALUES
(1, 'CLIENT', 1, 'زبون نقد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-01-17 07:39:30', 1, 1, '2025-02-25 22:37:26'),
(2, 'MAIN', 1, 'اسم الجهة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-01-17 07:39:30', 1, 1, '2024-01-17 07:42:57'),
(3, 'BRANCH', 2, 'صدى الملاعب بريدة', 'طريق الامير سلطان', 'الصفراء', 'بريدة', NULL, NULL, NULL, NULL, NULL, '311292735800003', NULL, NULL, NULL, '2025-02-15 02:03:08', 1, 2, '2025-02-15 02:03:08'),
(4, 'BRANCH', 3, 'ركن ميلان', 'طريق الامير سلطان', 'الصفراء', 'بريدة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-07 14:04:01', 1, 3, '2025-05-07 14:04:01'),
(5, 'BRANCH', 4, 'صدى الملاعب حفر الباطن', 'طريق الملك عبدالله', 'الواحة', 'حفر الباطن', NULL, NULL, NULL, NULL, NULL, '311292735800003', NULL, NULL, NULL, '2025-02-21 05:06:27', 1, 4, '2025-02-21 05:06:27'),
(6, 'BRANCH', 5, 'صدى الملاعب تبوك', 'مروج الامير', 'المروج', 'تبوك', NULL, NULL, NULL, NULL, NULL, '311292735800003', NULL, NULL, NULL, '2025-02-25 22:38:30', 1, 5, '2025-02-25 22:38:30'),
(7, 'CLIENT', 2, 'نقدا', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-02-14 23:50:31', 1, 2, '2025-02-14 23:52:58'),
(8, 'MAIN', 0, 'شركة صدى الملاعب', 'طريق الامير سلطان', 'الصفراء', 'القصيم', NULL, NULL, NULL, NULL, NULL, '311292735800003', NULL, NULL, NULL, '2025-02-14 18:02:36', 1, NULL, '2025-02-14 18:02:36'),
(9, 'MAIN', 0, 'شركة صدى الملاعب', 'طريق الامير سلطان', 'الصفراء', 'القصيم', NULL, NULL, NULL, NULL, NULL, '311292735800003', NULL, NULL, NULL, '2025-02-14 18:02:52', 1, NULL, '2025-02-14 18:02:52');

-- --------------------------------------------------------

--
-- Table structure for table `tblbenefit`
--

CREATE TABLE `tblbenefit` (
  `Id` int(11) NOT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `UserID` text DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `beneft_type` tinyint(1) DEFAULT NULL,
  `AmountType` varchar(20) DEFAULT NULL,
  `Amount` varchar(100) DEFAULT NULL,
  `Currency` varchar(20) DEFAULT NULL,
  `Reason` text DEFAULT NULL,
  `Status` int(11) DEFAULT NULL,
  `for_what` int(11) DEFAULT NULL,
  `extionsion` text DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `monthly` int(11) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedDate` date DEFAULT NULL,
  `LastUpdateDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblbenefit`
--

INSERT INTO `tblbenefit` (`Id`, `BranchID`, `UserID`, `name`, `beneft_type`, `AmountType`, `Amount`, `Currency`, `Reason`, `Status`, `for_what`, `extionsion`, `DueDate`, `monthly`, `CreatedBy`, `CreatedDate`, `LastUpdateDate`) VALUES
(2, 1, '3', 'بدل مواصلات', 1, 'amount', '50', 'SAR', NULL, 1, 2, '18', '2025-10-31', NULL, 9, '2025-10-09', '2025-10-08 23:58:54');

-- --------------------------------------------------------

--
-- Table structure for table `tblbranchesapps`
--

CREATE TABLE `tblbranchesapps` (
  `BranchID` int(11) NOT NULL,
  `AppID` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblbranchesapps`
--

INSERT INTO `tblbranchesapps` (`BranchID`, `AppID`) VALUES
(1, 'ACC'),
(1, 'BRA'),
(1, 'CLI'),
(1, 'FIN'),
(1, 'GEN'),
(1, 'HR'),
(1, 'POS'),
(1, 'PUR'),
(1, 'REP'),
(1, 'SAL'),
(1, 'SET'),
(1, 'STO'),
(1, 'USR'),
(2, 'ACC'),
(2, 'CLI'),
(2, 'FIN'),
(2, 'POS'),
(2, 'PUR'),
(2, 'REP'),
(2, 'SAL'),
(2, 'STO'),
(2, 'USR'),
(3, 'ACC'),
(3, 'CLI'),
(3, 'FIN'),
(3, 'POS'),
(3, 'PUR'),
(3, 'REP'),
(3, 'SAL'),
(3, 'STO'),
(3, 'USR'),
(4, 'ACC'),
(4, 'CLI'),
(4, 'FIN'),
(4, 'POS'),
(4, 'PUR'),
(4, 'REP'),
(4, 'SAL'),
(4, 'STO'),
(4, 'USR'),
(5, 'ACC'),
(5, 'CLI'),
(5, 'FIN'),
(5, 'POS'),
(5, 'PUR'),
(5, 'REP'),
(5, 'SAL'),
(5, 'STO'),
(5, 'USR'),
(6, 'ACC'),
(6, 'CLI'),
(6, 'FIN'),
(6, 'POS'),
(6, 'PUR'),
(6, 'REP'),
(6, 'SAL'),
(6, 'STO'),
(6, 'USR');

-- --------------------------------------------------------

--
-- Table structure for table `tblcurrenciesguide`
--

CREATE TABLE `tblcurrenciesguide` (
  `CurrencyID` varchar(3) NOT NULL,
  `CurrencyName` varchar(50) NOT NULL,
  `CurrencyShortName` varchar(6) NOT NULL,
  `IsLocalCurrency` bit(1) DEFAULT b'0',
  `ExchangePrice` decimal(20,12) DEFAULT NULL,
  `MinExchangePrice` decimal(20,10) DEFAULT NULL,
  `MaxExchangePrice` decimal(20,10) DEFAULT NULL,
  `DicimalUnit` varchar(15) DEFAULT NULL,
  `IsStopped` bit(1) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `BranchID` int(11) NOT NULL DEFAULT 1,
  `RowTime` datetime NOT NULL DEFAULT current_timestamp(),
  `RowVersion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblcurrenciesguide`
--

INSERT INTO `tblcurrenciesguide` (`CurrencyID`, `CurrencyName`, `CurrencyShortName`, `IsLocalCurrency`, `ExchangePrice`, `MinExchangePrice`, `MaxExchangePrice`, `DicimalUnit`, `IsStopped`, `UserID`, `BranchID`, `RowTime`, `RowVersion`) VALUES
('SAR', 'ريال سعودي', 'رس', b'1', 1.000000000000, 1.0000000000, NULL, 'هللة', NULL, NULL, 1, '2022-06-06 03:54:17', '2022-11-27 01:03:16');

-- --------------------------------------------------------

--
-- Table structure for table `tbldeductions`
--

CREATE TABLE `tbldeductions` (
  `Id` int(11) NOT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `UserID` text DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `Amount` varchar(100) DEFAULT NULL,
  `Currency` varchar(20) DEFAULT NULL,
  `Reason` text DEFAULT NULL,
  `Status` int(11) DEFAULT NULL,
  `for_what` int(11) DEFAULT NULL,
  `extionsion` text DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedDate` date DEFAULT NULL,
  `LastUpdateDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tbldeductions`
--

INSERT INTO `tbldeductions` (`Id`, `BranchID`, `UserID`, `name`, `Amount`, `Currency`, `Reason`, `Status`, `for_what`, `extionsion`, `DueDate`, `CreatedBy`, `CreatedDate`, `LastUpdateDate`) VALUES
(2, 1, '19', 'تجاوز اداري', '50', 'SAR', NULL, 1, 1, NULL, '2025-10-09', 9, '2025-10-09', '2025-10-08 23:59:56'),
(3, 1, '18', 'تجاوز اداري', '100', 'SAR', NULL, 1, 1, NULL, '2025-10-09', 1, '2025-10-09', '2025-10-09 00:42:12');

-- --------------------------------------------------------

--
-- Table structure for table `tbldocumentnums`
--

CREATE TABLE `tbldocumentnums` (
  `ID` int(5) NOT NULL,
  `App` varchar(5) DEFAULT NULL,
  `Description` varchar(50) NOT NULL,
  `Document` varchar(20) NOT NULL,
  `DocID` tinyint(4) DEFAULT NULL,
  `Patern` varchar(50) DEFAULT NULL,
  `MinLength` varchar(2) DEFAULT NULL,
  `IsUnique` tinyint(1) DEFAULT NULL,
  `TheNum` varchar(18) NOT NULL,
  `NoEdite` tinyint(1) DEFAULT NULL,
  `branch_id` int(11) NOT NULL DEFAULT 1,
  `IsDisabled` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tbldocumentnums`
--

INSERT INTO `tbldocumentnums` (`ID`, `App`, `Description`, `Document`, `DocID`, `Patern`, `MinLength`, `IsUnique`, `TheNum`, `NoEdite`, `branch_id`, `IsDisabled`) VALUES
(1, NULL, 'فاتورة مشتريات', 'pur_inv', NULL, NULL, '4', 1, '113', NULL, 1, NULL),
(2, NULL, 'المورد', 'supplyer', NULL, NULL, '4', 1, '18', NULL, 1, NULL),
(3, NULL, 'المنتجات', 'product', NULL, NULL, '1', 1, '1296', NULL, 1, NULL),
(4, 'STO', 'إذن إضافة يدوي', 'req_in', 4, NULL, '4', 1, '122', NULL, 1, NULL),
(5, 'STO', 'إذن صرف يدوي', 'req_out', 5, NULL, '4', 1, '89', NULL, 1, NULL),
(6, 'STO', 'إذن تحويل يدوي', 'req_tran', NULL, NULL, '4', 1, '800', NULL, 1, NULL),
(7, 'SAL', 'العميل', 'client', NULL, NULL, '4', 1, '12', NULL, 1, NULL),
(8, 'STO', 'ورقة جرد', 'stocking', NULL, NULL, '4', 1, '14', NULL, 1, NULL),
(9, NULL, 'مرتجع مشتريات', 'pur_inv_refund', NULL, NULL, '4', 1, '3', NULL, 1, NULL),
(10, NULL, 'فاتورة مبيعات', 'sal_inv', NULL, NULL, '4', 1, '1', 1, 1, NULL),
(11, NULL, 'عرض سعر', 'estimate_inv', NULL, '', '4', 1, '1', NULL, 1, NULL),
(12, NULL, 'فاتورة مشتريات', 'pur_inv', NULL, '#B', '4', 1, '113', NULL, 2, NULL),
(13, NULL, 'المورد', 'supplyer', NULL, '#B', '4', 1, '18', NULL, 2, NULL),
(14, NULL, 'المنتجات', 'product', NULL, '#B', '1', 1, '1', NULL, 2, NULL),
(15, 'STO', 'إذن إضافة يدوي', 'req_in', 4, '#B', '4', 1, '2.22222222222e15', NULL, 2, NULL),
(16, 'STO', 'إذن صرف يدوي', 'req_out', 5, '#B', '4', 1, '2222222220044', NULL, 2, NULL),
(17, 'STO', 'إذن تحويل يدوي', 'req_tran', NULL, '#B', '4', 1, '6', NULL, 2, NULL),
(18, 'SAL', 'العميل', 'client', NULL, '#B', '4', 1, '11', NULL, 2, NULL),
(19, 'STO', 'ورقة جرد', 'stocking', NULL, '#B', '4', 1, '14', NULL, 2, NULL),
(20, NULL, 'مرتجع مشتريات', 'pur_inv_refund', NULL, '#B', '4', 1, '3', NULL, 2, NULL),
(21, NULL, 'فاتورة مبيعات', 'sal_inv', NULL, '#B', '4', 1, '19003', 1, 2, NULL),
(22, NULL, 'عرض سعر', 'estimate_inv', NULL, '#B', '4', 1, '1', NULL, 2, NULL),
(27, NULL, 'فاتورة مشتريات', 'pur_inv', NULL, '#B', '4', 1, '113', NULL, 3, NULL),
(28, NULL, 'المورد', 'supplyer', NULL, '#B', '4', 1, '18', NULL, 3, NULL),
(29, NULL, 'المنتجات', 'product', NULL, '#B', '1', 1, '894', NULL, 3, NULL),
(30, 'STO', 'إذن إضافة يدوي', 'req_in', 4, '#B', '4', 1, '1', NULL, 3, NULL),
(31, 'STO', 'إذن صرف يدوي', 'req_out', 5, '#B', '4', 1, '1', NULL, 3, NULL),
(32, 'STO', 'إذن تحويل يدوي', 'req_tran', NULL, '#B', '4', 1, '1', NULL, 3, NULL),
(33, 'SAL', 'العميل', 'client', NULL, '#B', '4', 1, '11', NULL, 3, NULL),
(34, 'STO', 'ورقة جرد', 'stocking', NULL, '#B', '4', 1, '14', NULL, 3, NULL),
(35, NULL, 'مرتجع مشتريات', 'pur_inv_refund', NULL, '#B', '4', 1, '3', NULL, 3, NULL),
(36, NULL, 'فاتورة مبيعات', 'sal_inv', NULL, '#B', '4', 1, '1', 1, 3, NULL),
(37, NULL, 'عرض سعر', 'estimate_inv', NULL, '#B', '4', 1, '1', NULL, 3, NULL),
(42, NULL, 'فاتورة مشتريات', 'pur_inv', NULL, '#B', '4', 1, '113', NULL, 4, NULL),
(43, NULL, 'المورد', 'supplyer', NULL, '#B', '4', 1, '18', NULL, 4, NULL),
(44, NULL, 'المنتجات', 'product', NULL, '#B', '1', 1, '1', NULL, 4, NULL),
(45, 'STO', 'إذن إضافة يدوي', 'req_in', 4, '#B', '4', 1, '4.4444444444444e17', NULL, 4, NULL),
(46, 'STO', 'إذن صرف يدوي', 'req_out', 5, '#B', '4', 1, '4.44444444444e15', NULL, 4, NULL),
(47, 'STO', 'إذن تحويل يدوي', 'req_tran', NULL, '#B', '4', 1, '7', NULL, 4, NULL),
(48, 'SAL', 'العميل', 'client', NULL, '#B', '4', 1, '11', NULL, 4, NULL),
(49, 'STO', 'ورقة جرد', 'stocking', NULL, '#B', '4', 1, '14', NULL, 4, NULL),
(50, NULL, 'مرتجع مشتريات', 'pur_inv_refund', NULL, '#B', '4', 1, '3', NULL, 4, NULL),
(51, NULL, 'فاتورة مبيعات', 'sal_inv', NULL, '#B', '4', 1, '21611', 1, 4, NULL),
(52, NULL, 'عرض سعر', 'estimate_inv', NULL, '#B', '4', 1, '1', NULL, 4, NULL),
(57, NULL, 'فاتورة مشتريات', 'pur_inv', NULL, '#B', '4', 1, '113', NULL, 5, NULL),
(58, NULL, 'المورد', 'supplyer', NULL, '#B', '4', 1, '18', NULL, 5, NULL),
(59, NULL, 'المنتجات', 'product', NULL, '#B', '1', 1, '1', NULL, 5, NULL),
(60, 'STO', 'إذن إضافة يدوي', 'req_in', 4, '#B', '4', 1, '55550015', NULL, 5, NULL),
(61, 'STO', 'إذن صرف يدوي', 'req_out', 5, '#B', '4', 1, '550009', NULL, 5, NULL),
(62, 'STO', 'إذن تحويل يدوي', 'req_tran', NULL, '#B', '4', 1, '4', NULL, 5, NULL),
(63, 'SAL', 'العميل', 'client', NULL, '#B', '4', 1, '11', NULL, 5, NULL),
(64, 'STO', 'ورقة جرد', 'stocking', NULL, '#B', '4', 1, '14', NULL, 5, NULL),
(65, NULL, 'مرتجع مشتريات', 'pur_inv_refund', NULL, '#B', '4', 1, '3', NULL, 5, NULL),
(66, NULL, 'فاتورة مبيعات', 'sal_inv', NULL, '#B', '4', 1, '22880', 1, 5, NULL),
(67, NULL, 'عرض سعر', 'estimate_inv', NULL, '#B', '4', 1, '1', NULL, 5, NULL),
(68, NULL, 'فاتورة مشتريات', 'pur_inv', NULL, '#B', '4', 1, '93', NULL, 6, NULL),
(69, NULL, 'المورد', 'supplyer', NULL, '#B', '4', 1, '10', NULL, 6, NULL),
(70, NULL, 'المنتجات', 'product', NULL, '#B', '1', 1, '1', NULL, 6, NULL),
(71, 'STO', 'إذن إضافة يدوي', 'req_in', 4, '#B', '4', 1, '6660008', NULL, 6, NULL),
(72, 'STO', 'إذن صرف يدوي', 'req_out', 5, '#B', '4', 1, '6660007', NULL, 6, NULL),
(73, 'STO', 'إذن تحويل يدوي', 'req_tran', NULL, '#B', '4', 1, '1', NULL, 6, NULL),
(74, 'SAL', 'العميل', 'client', NULL, '#B', '4', 1, '6', NULL, 6, NULL),
(75, 'STO', 'ورقة جرد', 'stocking', NULL, '#B', '4', 1, '10', NULL, 6, NULL),
(76, NULL, 'مرتجع مشتريات', 'pur_inv_refund', NULL, '#B', '4', 1, '3', NULL, 6, NULL),
(77, NULL, 'فاتورة مبيعات', 'sal_inv', NULL, '#B', '4', 1, '12261', 1, 6, NULL),
(78, NULL, 'عرض سعر', 'estimate_inv', NULL, '#B', '4', 1, '1', NULL, 6, NULL),
(79, NULL, 'فاتورة مشتريات', 'pur_inv', NULL, '#B', '4', 1, '37', NULL, 7, NULL),
(80, NULL, 'المورد', 'supplyer', NULL, '#B', '4', 1, '3', NULL, 7, NULL),
(81, NULL, 'المنتجات', 'product', NULL, '#B', '1', 1, '1', NULL, 7, NULL),
(82, 'STO', 'إذن إضافة يدوي', 'req_in', 4, '#B', '4', 1, '70003', NULL, 7, NULL),
(83, 'STO', 'إذن صرف يدوي', 'req_out', 5, '#B', '4', 1, '770006', NULL, 7, NULL),
(84, 'STO', 'إذن تحويل يدوي', 'req_tran', NULL, '#B', '4', 1, '5', NULL, 7, NULL),
(85, 'SAL', 'العميل', 'client', NULL, '#B', '4', 1, '3', NULL, 7, NULL),
(86, 'STO', 'ورقة جرد', 'stocking', NULL, '#B', '4', 1, '5', NULL, 7, NULL),
(87, NULL, 'مرتجع مشتريات', 'pur_inv_refund', NULL, '#B', '4', 1, '1', NULL, 7, NULL),
(88, NULL, 'فاتورة مبيعات', 'sal_inv', NULL, '#B', '4', 1, '5762', 1, 7, NULL),
(89, NULL, 'عرض سعر', 'estimate_inv', NULL, '#B', '4', 1, '1', NULL, 7, NULL),
(90, 'STO', 'ورقة جرد (نهاية الفترة)', 'finally_stocking', NULL, NULL, '4', 1, '2', NULL, 1, NULL),
(91, 'STO', 'ورقة جرد (نهاية الفترة)', 'finally_stocking', NULL, '#B', '4', 1, '3', NULL, 2, NULL),
(92, 'STO', 'ورقة جرد (نهاية الفترة)', 'finally_stocking', NULL, '#B', '4', 1, '1', NULL, 3, NULL),
(93, 'STO', 'ورقة جرد (نهاية الفترة)', 'finally_stocking', NULL, '#B', '4', 1, '3', NULL, 4, NULL),
(94, 'STO', 'ورقة جرد (نهاية الفترة)', 'finally_stocking', NULL, '#B', '4', 1, '2', NULL, 5, NULL),
(95, 'STO', 'ورقة جرد (نهاية الفترة)', 'finally_stocking', NULL, '#B', '4', 1, '4', NULL, 6, NULL),
(96, 'STO', 'ورقة جرد (نهاية الفترة)', 'finally_stocking', NULL, '#B', '4', 1, '1', NULL, 7, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbldocuments`
--

CREATE TABLE `tbldocuments` (
  `DocumentID` smallint(6) NOT NULL,
  `App` varchar(5) DEFAULT NULL,
  `DocumentName` varchar(25) NOT NULL,
  `Sort` tinyint(4) DEFAULT NULL,
  `Description` varchar(50) NOT NULL,
  `TblSource` varchar(30) DEFAULT NULL,
  `DataSource` varchar(40) DEFAULT NULL,
  `ViewSource` bigint(20) DEFAULT NULL,
  `IsDisabled` tinyint(1) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `RowTime` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tbldocuments`
--

INSERT INTO `tbldocuments` (`DocumentID`, `App`, `DocumentName`, `Sort`, `Description`, `TblSource`, `DataSource`, `ViewSource`, `IsDisabled`, `UserID`, `RowTime`) VALUES
(1, NULL, 'simple_entry', 20, 'قيد بسيط', NULL, 'tblsimpleentries', NULL, 1, NULL, '2023-01-31 03:40:24'),
(2, NULL, 'receipt', 30, 'سند قبض', NULL, 'financial-incomes-view', NULL, NULL, NULL, '2022-11-28 14:51:06'),
(3, NULL, 'journal', 10, 'قيد يومية', NULL, 'accountant-journals-add', NULL, NULL, NULL, '2023-08-15 13:34:54'),
(4, NULL, 'money_transfer', 40, 'تحويل أموال', NULL, 'tbltreasuriestrans', NULL, NULL, NULL, '2022-09-07 14:21:20'),
(5, 'SAL', 'sal_inv', 50, 'فاتورة مبيعات', 'tblinvsh', 'invoice-view', NULL, NULL, NULL, '2022-10-28 21:33:10'),
(6, NULL, 'stock_manual', 50, 'تعديل يدوي للمخزون', NULL, 'products-stock-adjust', 0, NULL, NULL, '2022-09-22 03:15:45'),
(7, 'STO', 'req_in', 50, 'إذن إضافة مخزون', NULL, 'requisition-view', 0, NULL, NULL, '2023-10-02 15:47:24'),
(8, 'STO', 'req_out', 50, 'إذن صرف مخزني', NULL, 'requisition-view', 0, NULL, NULL, '2023-10-11 21:07:35'),
(9, 'STO', 'req_tran', 50, 'إذن تحويل مخزني', NULL, 'requisition-view', 0, NULL, NULL, '2023-10-02 15:47:05'),
(10, 'STO', 'req', 50, 'إذن مخزن', NULL, 'requisition?id=', 0, NULL, NULL, '2022-09-25 00:00:56'),
(11, 'PUR', 'pur_inv', 50, 'فاتورة شراء', 'tblpurinvsh', 'purchase-view', 0, NULL, NULL, '2022-10-18 00:45:13'),
(12, 'SAL', 'pay_clients', 50, 'مدفوعات العميل', NULL, 'payment-view?id=', 0, NULL, NULL, '2022-10-05 00:11:21'),
(13, 'STO', 'first_period_stock', 50, 'مخزون بداية الفترة', NULL, 'inventory-first-period-doc-view', NULL, NULL, NULL, '2023-12-15 01:45:06'),
(14, 'PUR', 'pur_inv_payment', 50, 'دفع فاتورة الشراء', NULL, 'purchase-payment-view', NULL, NULL, NULL, '2022-10-29 05:33:28'),
(15, 'PUR', 'pur_inv_refund', 50, 'مرتجع مشتريات', 'tblpurinvsh', 'purchase-refund-view', 0, NULL, NULL, '2023-03-23 20:07:54'),
(16, 'SAL', 'sal_inv_payment', 50, 'مدفوعات العميل للفاتورة', NULL, 'invoice-payment-view', NULL, NULL, NULL, '2023-08-19 02:01:54'),
(17, 'SAL', 'credit_note', 51, 'إشعار دائن', 'tblinvsh', 'invoice-creditnote-view', NULL, NULL, NULL, '2023-08-19 02:01:57'),
(18, 'SAL', 'sal_inv_refund', 51, 'فاتورة مرتجعة', 'tblinvsh', 'invoice-refund-view', NULL, NULL, NULL, '2023-03-23 20:03:44'),
(19, 'SAL', 'sales_cost', 51, 'تكلفة مبيعات فاتورة', NULL, 'invoice-view', NULL, NULL, NULL, '2022-12-14 04:23:01'),
(20, NULL, 'outcash', 32, 'سند صرف', NULL, 'financial-outcomes-view', NULL, NULL, NULL, '2022-11-28 14:51:06'),
(21, 'SAL', 'sales_refund_cost', 51, 'تكلفة مبيعات فاتورة مرتجعة', NULL, 'invoice-refund-view', NULL, NULL, NULL, '2022-12-14 04:19:03'),
(22, 'POS', 'emp_custody', 120, 'عهدة الموظف', NULL, 'pos-money-transaction', NULL, NULL, NULL, '2023-05-02 21:00:00'),
(23, 'SAL', 'refound_inv_payment', 50, 'مرتجع نقدي', NULL, 'invoice-payment-view', 0, NULL, NULL, '2023-08-19 02:00:22'),
(25, 'STO', 'man_tran', 50, 'نقل يدوي للمخزون', NULL, 'product-transfer', 0, NULL, NULL, '2023-08-31 02:30:18'),
(26, 'POS', 'close_sale_pos', 120, 'POS-مبيعات', NULL, 'pos-session-view', NULL, NULL, NULL, '2023-03-17 04:00:00'),
(27, 'POS', 'validate_pos', 122, 'إخلاء عهد POS-مبيعات', NULL, 'pos-session-view', NULL, NULL, NULL, '2024-03-17 04:00:00'),
(28, 'POS', 'close_refound_pos', 120, 'POS-مردود مبيعات', NULL, 'pos-session-view', NULL, NULL, NULL, '2023-03-17 04:00:00'),
(29, 'HR', 'salary disbursement', 11, 'صرف الرواتب', 'salary_registration', 'Issuing-salaries-view', NULL, NULL, NULL, '2025-08-05 20:52:32');

-- --------------------------------------------------------

--
-- Table structure for table `tbldocumentsdetails`
--

CREATE TABLE `tbldocumentsdetails` (
  `DocumentDetailsID` smallint(6) NOT NULL,
  `ParentID` smallint(6) NOT NULL,
  `DetailsDocumentName` varchar(25) NOT NULL,
  `DetailsDescription` varchar(50) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `RowTime` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tbldocumentsdetails`
--

INSERT INTO `tbldocumentsdetails` (`DocumentDetailsID`, `ParentID`, `DetailsDocumentName`, `DetailsDescription`, `UserID`, `RowTime`) VALUES
(1, 3, 'journal_normal', 'قيد يومية', NULL, '2022-07-02 16:56:48'),
(2, 3, 'journal_oppening', 'قيد رصيد إفتتاحي', NULL, '2022-07-02 16:56:50'),
(3, 3, 'journal_currencies', 'قيد فوارق عملة', NULL, '2022-07-02 16:56:56'),
(4, 3, 'journal_closing', 'قيد إقفال', NULL, '2022-07-02 16:56:59');

-- --------------------------------------------------------

--
-- Table structure for table `tblempadvances`
--

CREATE TABLE `tblempadvances` (
  `Id` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `Amount` varbinary(100) DEFAULT NULL,
  `currency` varchar(20) DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `Status` int(11) DEFAULT NULL,
  `Draft` int(11) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `CreatedDate` date NOT NULL,
  `LastUpdateDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblempadvances`
--

INSERT INTO `tblempadvances` (`Id`, `UserID`, `BranchID`, `Amount`, `currency`, `DueDate`, `Status`, `Draft`, `type`, `description`, `created_by`, `CreatedDate`, `LastUpdateDate`) VALUES
(1, 11, 1, 0x313230, 'SAR', '2025-08-31', 1, 1, 1, NULL, 11, '2025-08-31', '2025-08-30 23:43:50'),
(2, 19, 1, 0x323030, 'SAR', '2025-10-09', 1, 1, 1, NULL, 19, '2025-10-09', '2025-10-09 00:02:31');

-- --------------------------------------------------------

--
-- Table structure for table `tblemployee`
--

CREATE TABLE `tblemployee` (
  `EmpID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `BranchID` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `FirstName` varchar(20) NOT NULL,
  `SecondName` varchar(20) DEFAULT NULL,
  `LastName` varchar(20) NOT NULL,
  `EmpPhoto` varchar(250) DEFAULT NULL,
  `EmpPhone` varchar(15) DEFAULT NULL,
  `EmpNote` varchar(200) DEFAULT NULL,
  `CreatedDate` date DEFAULT NULL,
  `CreatedUser` int(11) DEFAULT NULL,
  `IsDisabled` tinyint(1) DEFAULT NULL,
  `LastUpdate` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblentries`
--

CREATE TABLE `tblentries` (
  `EntryID` bigint(20) NOT NULL,
  `DocumentID` smallint(6) NOT NULL,
  `TheDate` date NOT NULL,
  `IsCredend` tinyint(1) NOT NULL,
  `RecordID` bigint(20) NOT NULL,
  `RecordNunmber` varchar(20) NOT NULL,
  `ProjectID` int(11) DEFAULT NULL,
  `TaskID` int(11) DEFAULT NULL,
  `Notes` varchar(150) DEFAULT NULL,
  `UserID` int(11) NOT NULL,
  `BranchID` int(11) NOT NULL,
  `RowTime` datetime NOT NULL DEFAULT current_timestamp(),
  `RowVersion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblentries`
--

INSERT INTO `tblentries` (`EntryID`, `DocumentID`, `TheDate`, `IsCredend`, `RecordID`, `RecordNunmber`, `ProjectID`, `TaskID`, `Notes`, `UserID`, `BranchID`, `RowTime`, `RowVersion`) VALUES
(2258, 8, '2025-06-03', 1, 958, '44444444440023', NULL, NULL, NULL, 1, 4, '2025-06-03 23:23:51', '2025-06-04 03:23:51'),
(2259, 26, '2025-06-01', 1, 379, '99', NULL, NULL, 'إغلاق مبيعات POS 99 # POS-2', 1, 5, '2025-06-03 23:24:32', '2025-06-04 03:24:32'),
(2260, 28, '2025-06-01', 1, 379, '99', NULL, NULL, 'إغلاق مردودات POS 99 # POS-2', 1, 5, '2025-06-03 23:24:32', '2025-06-04 03:24:32'),
(2261, 27, '2025-06-01', 1, 379, '99', NULL, NULL, 'إخلاء عهدة جلسة مبيعات99 # POS-2', 1, 5, '2025-06-03 23:24:32', '2025-06-04 03:24:32'),
(2262, 7, '2025-06-03', 1, 961, '5550009', NULL, NULL, NULL, 1, 5, '2025-06-03 23:24:36', '2025-06-04 03:24:36'),
(2263, 8, '2025-06-03', 1, 960, '50003', NULL, NULL, NULL, 1, 5, '2025-06-03 23:24:38', '2025-06-04 03:24:38'),
(2264, 26, '2025-06-02', 1, 383, '100', NULL, NULL, 'إغلاق مبيعات POS 100 # POS-2', 1, 5, '2025-06-03 23:25:26', '2025-06-04 03:25:26'),
(2265, 28, '2025-06-02', 1, 383, '100', NULL, NULL, 'إغلاق مردودات POS 100 # POS-2', 1, 5, '2025-06-03 23:25:26', '2025-06-04 03:25:26'),
(2266, 27, '2025-06-02', 1, 383, '100', NULL, NULL, 'إخلاء عهدة جلسة مبيعات100 # POS-2', 1, 5, '2025-06-03 23:25:26', '2025-06-04 03:25:26'),
(2267, 7, '2025-06-03', 1, 963, '5550009', NULL, NULL, NULL, 1, 5, '2025-06-03 23:25:30', '2025-06-04 03:25:30'),
(2268, 8, '2025-06-03', 1, 962, '50003', NULL, NULL, NULL, 1, 5, '2025-06-03 23:25:31', '2025-06-04 03:25:31'),
(2269, 26, '2025-06-01', 1, 378, '72', NULL, NULL, 'إغلاق مبيعات POS 72 # جهاز حايل', 1, 6, '2025-06-03 23:26:21', '2025-06-04 03:26:21'),
(2270, 28, '2025-06-01', 1, 378, '72', NULL, NULL, 'إغلاق مردودات POS 72 # جهاز حايل', 1, 6, '2025-06-03 23:26:21', '2025-06-04 03:26:21'),
(2271, 27, '2025-06-01', 1, 378, '72', NULL, NULL, 'إخلاء عهدة جلسة مبيعات72 # جهاز حايل', 1, 6, '2025-06-03 23:26:21', '2025-06-04 03:26:21'),
(2272, 7, '2025-06-03', 1, 965, '6660006', NULL, NULL, NULL, 1, 6, '2025-06-03 23:26:25', '2025-06-04 03:26:25'),
(2273, 8, '2025-06-03', 1, 964, '6660005', NULL, NULL, NULL, 1, 6, '2025-06-03 23:26:28', '2025-06-04 03:26:28'),
(2274, 26, '2025-06-02', 1, 381, '73', NULL, NULL, 'إغلاق مبيعات POS 73 # جهاز حايل', 1, 6, '2025-06-03 23:26:55', '2025-06-04 03:26:55'),
(2275, 28, '2025-06-02', 1, 381, '73', NULL, NULL, 'إغلاق مردودات POS 73 # جهاز حايل', 1, 6, '2025-06-03 23:26:55', '2025-06-04 03:26:55'),
(2276, 27, '2025-06-02', 1, 381, '73', NULL, NULL, 'إخلاء عهدة جلسة مبيعات73 # جهاز حايل', 1, 6, '2025-06-03 23:26:55', '2025-06-04 03:26:55'),
(2277, 7, '2025-06-03', 1, 967, '6660006', NULL, NULL, NULL, 1, 6, '2025-06-03 23:26:58', '2025-06-04 03:26:58'),
(2278, 8, '2025-06-03', 1, 966, '6660005', NULL, NULL, NULL, 1, 6, '2025-06-03 23:26:59', '2025-06-04 03:26:59'),
(2279, 11, '2025-06-08', 1, 59, '0053', NULL, NULL, NULL, 1, 1, '2025-06-08 02:01:52', '2025-06-07 23:01:52'),
(2280, 11, '2025-06-08', 1, 60, '0054', NULL, NULL, NULL, 1, 1, '2025-06-08 02:06:06', '2025-06-07 23:06:06'),
(2281, 14, '2025-06-08', 1, 1, '0054', NULL, NULL, NULL, 1, 1, '2025-06-08 02:06:06', '2025-06-07 23:06:06'),
(2282, 29, '2025-08-06', 1, 0, '1', NULL, NULL, 'صرف مستحقات الموظفين', 1, 1, '2025-08-06 00:00:20', '2025-08-05 21:00:20'),
(2283, 29, '2025-08-06', 1, 0, '2', NULL, NULL, 'صرف مستحقات الموظفين', 1, 1, '2025-08-06 00:22:04', '2025-08-05 21:22:04'),
(2285, 29, '2025-08-06', 1, 2285, '3', NULL, NULL, 'صرف مستحقات الموظفين', 1, 1, '2025-08-06 00:45:53', '2025-08-05 21:45:53'),
(2286, 29, '2025-08-06', 1, 2286, '4', NULL, NULL, 'استحقاق رواتب الموظفين', 1, 1, '2025-08-06 00:45:53', '2025-08-05 21:45:53'),
(2287, 5, '2025-08-17', 1, 20152, '0001', NULL, NULL, NULL, 1, 1, '2025-08-17 10:16:04', '2025-08-17 07:16:04'),
(2288, 20, '2025-08-29', 1, 7, '1', NULL, NULL, NULL, 1, 1, '2025-08-29 22:12:51', '2025-08-29 19:12:51'),
(2289, 29, '2025-08-31', 1, 0, '5', NULL, NULL, 'صرف مستحقات الموظفين', 1, 1, '2025-08-31 01:43:29', '2025-08-30 22:43:29'),
(2290, 29, '2025-08-31', 1, 2290, '6', NULL, NULL, 'استحقاق رواتب الموظفين', 1, 1, '2025-08-31 01:43:29', '2025-08-30 22:43:29'),
(2291, 29, '2025-09-01', 1, 0, '7', NULL, NULL, 'صرف مستحقات الموظفين', 1, 1, '2025-09-01 02:51:10', '2025-08-31 23:51:10'),
(2292, 29, '2025-09-01', 1, 2292, '8', NULL, NULL, 'استحقاق رواتب الموظفين', 1, 1, '2025-09-01 02:51:10', '2025-08-31 23:51:10'),
(2293, 29, '2025-10-09', 1, 0, '9', NULL, NULL, 'صرف مستحقات الموظفين', 9, 1, '2025-10-09 02:57:02', '2025-10-08 23:57:02'),
(2294, 29, '2025-10-09', 1, 2294, '10', NULL, NULL, 'استحقاق رواتب الموظفين', 9, 1, '2025-10-09 02:57:02', '2025-10-08 23:57:02');

-- --------------------------------------------------------

--
-- Table structure for table `tblentriesdetails`
--

CREATE TABLE `tblentriesdetails` (
  `EntryDetailsID` bigint(20) NOT NULL,
  `ParentID` bigint(20) NOT NULL,
  `Amount` decimal(20,12) DEFAULT NULL,
  `CurrencyID` varchar(3) NOT NULL DEFAULT 'SAR',
  `ExchangePrice` decimal(20,12) DEFAULT NULL,
  `LocalAmount` decimal(20,12) DEFAULT NULL,
  `AccountID` bigint(20) NOT NULL,
  `Notes` varchar(150) DEFAULT NULL,
  `CostCenterID` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `BranchID` int(11) NOT NULL DEFAULT 1,
  `RowTime` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblentriesdetails`
--

INSERT INTO `tblentriesdetails` (`EntryDetailsID`, `ParentID`, `Amount`, `CurrencyID`, `ExchangePrice`, `LocalAmount`, `AccountID`, `Notes`, `CostCenterID`, `UserID`, `BranchID`, `RowTime`) VALUES
(5778, 2258, 6444.437377264300, 'SAR', 1.000000000000, 6444.437377264300, 395, NULL, NULL, 1, 4, '2025-06-03 16:23:51'),
(5779, 2258, -6444.437377264300, 'SAR', 1.000000000000, -6444.437377264300, 381, NULL, NULL, 1, 4, '2025-06-03 16:23:51'),
(5780, 2259, -11308.480000000000, 'SAR', 1.000000000000, -11308.480000000000, 379, 'إغلاق مبيعات POS 99 # POS-2', NULL, 1, 5, '2025-06-03 16:24:32'),
(5781, 2259, 9833.430000000000, 'SAR', 1.000000000000, 9833.430000000000, 104, 'إغلاق مبيعات POS 99 # POS-2', NULL, 1, 5, '2025-06-03 16:24:32'),
(5782, 2259, 1475.050000000000, 'SAR', 1.000000000000, 1475.050000000000, 354, 'إغلاق مبيعات POS 99 # POS-2', NULL, 1, 5, '2025-06-03 16:24:32'),
(5783, 2260, 651.000000000000, 'SAR', 1.000000000000, 651.000000000000, 379, 'إغلاق مردودات POS 99 # POS-2', NULL, 1, 5, '2025-06-03 16:24:32'),
(5784, 2260, -566.070000000000, 'SAR', 1.000000000000, -566.070000000000, 103, 'إغلاق مردودات POS 99 # POS-2', NULL, 1, 5, '2025-06-03 16:24:32'),
(5785, 2260, -84.930000000000, 'SAR', 1.000000000000, -84.930000000000, 354, 'إغلاق مردودات POS 99 # POS-2', NULL, 1, 5, '2025-06-03 16:24:32'),
(5786, 2261, -10657.480000000000, 'SAR', 1.000000000000, -10657.480000000000, 406, 'إخلاء عهدة جلسة مبيعات99 # POS-2', NULL, 1, 5, '2025-06-03 16:24:32'),
(5787, 2261, 10657.480000000000, 'SAR', 1.000000000000, 10657.480000000000, 379, 'إخلاء عهدة جلسة مبيعات99 # POS-2', NULL, 1, 5, '2025-06-03 16:24:32'),
(5788, 2262, -300.347551476060, 'SAR', 1.000000000000, -300.347551476060, 404, NULL, NULL, 1, 5, '2025-06-03 16:24:36'),
(5789, 2262, 300.347551476060, 'SAR', 1.000000000000, 300.347551476060, 381, NULL, NULL, 1, 5, '2025-06-03 16:24:36'),
(5790, 2263, 4873.860106312500, 'SAR', 1.000000000000, 4873.860106312500, 404, NULL, NULL, 1, 5, '2025-06-03 16:24:38'),
(5791, 2263, -4873.860106312500, 'SAR', 1.000000000000, -4873.860106312500, 381, NULL, NULL, 1, 5, '2025-06-03 16:24:38'),
(5792, 2264, -17768.010000000000, 'SAR', 1.000000000000, -17768.010000000000, 379, 'إغلاق مبيعات POS 100 # POS-2', NULL, 1, 5, '2025-06-03 16:25:26'),
(5793, 2264, 15450.410000000000, 'SAR', 1.000000000000, 15450.410000000000, 104, 'إغلاق مبيعات POS 100 # POS-2', NULL, 1, 5, '2025-06-03 16:25:26'),
(5794, 2264, 2317.600000000000, 'SAR', 1.000000000000, 2317.600000000000, 354, 'إغلاق مبيعات POS 100 # POS-2', NULL, 1, 5, '2025-06-03 16:25:26'),
(5795, 2265, 961.000000000000, 'SAR', 1.000000000000, 961.000000000000, 379, 'إغلاق مردودات POS 100 # POS-2', NULL, 1, 5, '2025-06-03 16:25:26'),
(5796, 2265, -835.660000000000, 'SAR', 1.000000000000, -835.660000000000, 103, 'إغلاق مردودات POS 100 # POS-2', NULL, 1, 5, '2025-06-03 16:25:26'),
(5797, 2265, -125.340000000000, 'SAR', 1.000000000000, -125.340000000000, 354, 'إغلاق مردودات POS 100 # POS-2', NULL, 1, 5, '2025-06-03 16:25:26'),
(5798, 2266, -16807.010000000000, 'SAR', 1.000000000000, -16807.010000000000, 406, 'إخلاء عهدة جلسة مبيعات100 # POS-2', NULL, 1, 5, '2025-06-03 16:25:26'),
(5799, 2266, 16807.010000000000, 'SAR', 1.000000000000, 16807.010000000000, 379, 'إخلاء عهدة جلسة مبيعات100 # POS-2', NULL, 1, 5, '2025-06-03 16:25:26'),
(5800, 2267, -422.336038437690, 'SAR', 1.000000000000, -422.336038437690, 404, NULL, NULL, 1, 5, '2025-06-03 16:25:30'),
(5801, 2267, 422.336038437690, 'SAR', 1.000000000000, 422.336038437690, 381, NULL, NULL, 1, 5, '2025-06-03 16:25:30'),
(5802, 2268, 7590.226253611700, 'SAR', 1.000000000000, 7590.226253611700, 404, NULL, NULL, 1, 5, '2025-06-03 16:25:31'),
(5803, 2268, -7590.226253611700, 'SAR', 1.000000000000, -7590.226253611700, 381, NULL, NULL, 1, 5, '2025-06-03 16:25:31'),
(5804, 2269, -11542.000000000000, 'SAR', 1.000000000000, -11542.000000000000, 379, 'إغلاق مبيعات POS 72 # جهاز حايل', NULL, 1, 6, '2025-06-03 16:26:21'),
(5805, 2269, 10036.520000000000, 'SAR', 1.000000000000, 10036.520000000000, 104, 'إغلاق مبيعات POS 72 # جهاز حايل', NULL, 1, 6, '2025-06-03 16:26:21'),
(5806, 2269, 1505.480000000000, 'SAR', 1.000000000000, 1505.480000000000, 354, 'إغلاق مبيعات POS 72 # جهاز حايل', NULL, 1, 6, '2025-06-03 16:26:21'),
(5807, 2270, 797.000000000000, 'SAR', 1.000000000000, 797.000000000000, 379, 'إغلاق مردودات POS 72 # جهاز حايل', NULL, 1, 6, '2025-06-03 16:26:21'),
(5808, 2270, -693.050000000000, 'SAR', 1.000000000000, -693.050000000000, 103, 'إغلاق مردودات POS 72 # جهاز حايل', NULL, 1, 6, '2025-06-03 16:26:21'),
(5809, 2270, -103.950000000000, 'SAR', 1.000000000000, -103.950000000000, 354, 'إغلاق مردودات POS 72 # جهاز حايل', NULL, 1, 6, '2025-06-03 16:26:21'),
(5810, 2271, -10504.000000000000, 'SAR', 1.000000000000, -10504.000000000000, 465, 'إخلاء عهدة جلسة مبيعات72 # جهاز حايل', NULL, 1, 6, '2025-06-03 16:26:21'),
(5811, 2271, -241.000000000000, 'SAR', 1.000000000000, -241.000000000000, 391, 'إخلاء عهدة جلسة مبيعات72 # جهاز حايل', NULL, 1, 6, '2025-06-03 16:26:21'),
(5812, 2271, 10745.000000000000, 'SAR', 1.000000000000, 10745.000000000000, 379, 'إخلاء عهدة جلسة مبيعات72 # جهاز حايل', NULL, 1, 6, '2025-06-03 16:26:21'),
(5813, 2272, -464.617681076720, 'SAR', 1.000000000000, -464.617681076720, 464, NULL, NULL, 1, 6, '2025-06-03 16:26:25'),
(5814, 2272, 464.617681076720, 'SAR', 1.000000000000, 464.617681076720, 381, NULL, NULL, 1, 6, '2025-06-03 16:26:25'),
(5815, 2273, 6412.712190082600, 'SAR', 1.000000000000, 6412.712190082600, 464, NULL, NULL, 1, 6, '2025-06-03 16:26:28'),
(5816, 2273, -6412.712190082600, 'SAR', 1.000000000000, -6412.712190082600, 381, NULL, NULL, 1, 6, '2025-06-03 16:26:28'),
(5817, 2274, -8377.000000000000, 'SAR', 1.000000000000, -8377.000000000000, 379, 'إغلاق مبيعات POS 73 # جهاز حايل', NULL, 1, 6, '2025-06-03 16:26:55'),
(5818, 2274, 7284.340000000000, 'SAR', 1.000000000000, 7284.340000000000, 104, 'إغلاق مبيعات POS 73 # جهاز حايل', NULL, 1, 6, '2025-06-03 16:26:55'),
(5819, 2274, 1092.660000000000, 'SAR', 1.000000000000, 1092.660000000000, 354, 'إغلاق مبيعات POS 73 # جهاز حايل', NULL, 1, 6, '2025-06-03 16:26:55'),
(5820, 2275, 291.000000000000, 'SAR', 1.000000000000, 291.000000000000, 379, 'إغلاق مردودات POS 73 # جهاز حايل', NULL, 1, 6, '2025-06-03 16:26:55'),
(5821, 2275, -253.030000000000, 'SAR', 1.000000000000, -253.030000000000, 103, 'إغلاق مردودات POS 73 # جهاز حايل', NULL, 1, 6, '2025-06-03 16:26:55'),
(5822, 2275, -37.970000000000, 'SAR', 1.000000000000, -37.970000000000, 354, 'إغلاق مردودات POS 73 # جهاز حايل', NULL, 1, 6, '2025-06-03 16:26:55'),
(5823, 2276, -8086.000000000000, 'SAR', 1.000000000000, -8086.000000000000, 465, 'إخلاء عهدة جلسة مبيعات73 # جهاز حايل', NULL, 1, 6, '2025-06-03 16:26:55'),
(5824, 2276, 8086.000000000000, 'SAR', 1.000000000000, 8086.000000000000, 379, 'إخلاء عهدة جلسة مبيعات73 # جهاز حايل', NULL, 1, 6, '2025-06-03 16:26:55'),
(5825, 2277, -159.053485685710, 'SAR', 1.000000000000, -159.053485685710, 464, NULL, NULL, 1, 6, '2025-06-03 16:26:58'),
(5826, 2277, 159.053485685710, 'SAR', 1.000000000000, 159.053485685710, 381, NULL, NULL, 1, 6, '2025-06-03 16:26:58'),
(5827, 2278, 4503.630067800400, 'SAR', 1.000000000000, 4503.630067800400, 464, NULL, NULL, 1, 6, '2025-06-03 16:27:00'),
(5828, 2278, -4503.630067800400, 'SAR', 1.000000000000, -4503.630067800400, 381, NULL, NULL, 1, 6, '2025-06-03 16:27:00'),
(5829, 2279, -0.521739130435, 'SAR', 1.000000000000, -0.521739130435, 356, NULL, NULL, 1, 1, '2025-06-08 02:01:52'),
(5830, 2279, -3.478260869565, 'SAR', 1.000000000000, -3.478260869565, 386, NULL, NULL, 1, 1, '2025-06-08 02:01:52'),
(5831, 2279, 4.000000000000, 'SAR', 1.000000000000, 4.000000000000, 468, NULL, NULL, 1, 1, '2025-06-08 02:01:52'),
(5832, 2280, -1.043478260870, 'SAR', 1.000000000000, -1.043478260870, 356, NULL, NULL, 1, 1, '2025-06-08 02:06:06'),
(5833, 2280, -6.956521739130, 'SAR', 1.000000000000, -6.956521739130, 386, NULL, NULL, 1, 1, '2025-06-08 02:06:06'),
(5834, 2280, 8.000000000000, 'SAR', 1.000000000000, 8.000000000000, 445, NULL, NULL, 1, 1, '2025-06-08 02:06:06'),
(5835, 2281, 8.000000000000, 'SAR', 1.000000000000, 8.000000000000, 388, NULL, NULL, 1, 1, '2025-06-08 02:06:06'),
(5836, 2281, -8.000000000000, 'SAR', 1.000000000000, -8.000000000000, 445, NULL, NULL, 1, 1, '2025-06-08 02:06:06'),
(5837, 2282, -13100.000000000000, 'SAR', 1.000000000000, -13100.000000000000, 68, NULL, NULL, 1, 1, '2025-08-06 00:00:21'),
(5838, 2283, -13100.000000000000, 'SAR', 1.000000000000, -13100.000000000000, 68, NULL, NULL, 1, 1, '2025-08-06 00:22:04'),
(5840, 2285, -13100.000000000000, 'SAR', 1.000000000000, -13100.000000000000, 68, NULL, NULL, 1, 1, '2025-08-06 00:45:53'),
(5841, 2285, 0.000000000000, 'SAR', 1.000000000000, 0.000000000000, 499, NULL, NULL, 1, 1, '2025-08-06 00:45:53'),
(5842, 2285, 0.000000000000, 'SAR', 1.000000000000, 0.000000000000, 503, NULL, NULL, 1, 1, '2025-08-06 00:45:53'),
(5843, 2285, 4226.000000000000, 'SAR', 1.000000000000, 4226.000000000000, 413, NULL, NULL, 1, 1, '2025-08-06 00:45:53'),
(5844, 2285, 0.000000000000, 'SAR', 1.000000000000, 0.000000000000, 501, NULL, NULL, 1, 1, '2025-08-06 00:45:53'),
(5845, 2285, 8874.000000000000, 'SAR', 1.000000000000, 8874.000000000000, 504, NULL, NULL, 1, 1, '2025-08-06 00:45:53'),
(5846, 2286, -4226.000000000000, 'SAR', 1.000000000000, -4226.000000000000, 413, NULL, NULL, 1, 1, '2025-08-06 00:45:53'),
(5847, 2286, 4226.000000000000, 'SAR', 1.000000000000, 4226.000000000000, 388, NULL, NULL, 1, 1, '2025-08-06 00:45:53'),
(5848, 2287, -99.000000000000, 'SAR', 1.000000000000, -99.000000000000, 500, NULL, NULL, 1, 1, '2025-08-17 10:16:04'),
(5849, 2287, 12.913043478261, 'SAR', 1.000000000000, 12.913043478261, 354, NULL, NULL, 1, 1, '2025-08-17 10:16:04'),
(5850, 2287, 86.086956521739, 'SAR', 1.000000000000, 86.086956521739, 104, NULL, NULL, 1, 1, '2025-08-17 10:16:04'),
(5851, 2288, -8.000000000000, 'SAR', 1.000000000000, -8.000000000000, 82, NULL, NULL, 1, 1, '2025-08-29 22:12:51'),
(5852, 2288, 8.000000000000, 'SAR', 1.000000000000, 8.000000000000, 388, NULL, NULL, 1, 1, '2025-08-29 22:12:51'),
(5853, 2289, -7506.000000000000, 'SAR', 1.000000000000, -7506.000000000000, 68, NULL, NULL, 1, 1, '2025-08-31 01:43:30'),
(5854, 2289, 0.000000000000, 'SAR', 1.000000000000, 0.000000000000, 499, NULL, NULL, 1, 1, '2025-08-31 01:43:30'),
(5855, 2289, 0.000000000000, 'SAR', 1.000000000000, 0.000000000000, 503, NULL, NULL, 1, 1, '2025-08-31 01:43:30'),
(5856, 2289, 232.000000000000, 'SAR', 1.000000000000, 232.000000000000, 413, NULL, NULL, 1, 1, '2025-08-31 01:43:30'),
(5857, 2289, 0.000000000000, 'SAR', 1.000000000000, 0.000000000000, 501, NULL, NULL, 1, 1, '2025-08-31 01:43:30'),
(5858, 2289, 7274.000000000000, 'SAR', 1.000000000000, 7274.000000000000, 504, NULL, NULL, 1, 1, '2025-08-31 01:43:30'),
(5859, 2290, -232.000000000000, 'SAR', 1.000000000000, -232.000000000000, 413, NULL, NULL, 1, 1, '2025-08-31 01:43:30'),
(5860, 2290, 232.000000000000, 'SAR', 1.000000000000, 232.000000000000, 388, NULL, NULL, 1, 1, '2025-08-31 01:43:30'),
(5861, 2291, -4006.000000000000, 'SAR', 1.000000000000, -4006.000000000000, 68, NULL, NULL, 1, 1, '2025-09-01 02:51:10'),
(5862, 2291, -550.000000000000, 'SAR', 1.000000000000, -550.000000000000, 499, NULL, NULL, 1, 1, '2025-09-01 02:51:10'),
(5863, 2291, -150.000000000000, 'SAR', 1.000000000000, -150.000000000000, 503, NULL, NULL, 1, 1, '2025-09-01 02:51:10'),
(5864, 2291, 812.000000000000, 'SAR', 1.000000000000, 812.000000000000, 413, NULL, NULL, 1, 1, '2025-09-01 02:51:10'),
(5865, 2291, 120.000000000000, 'SAR', 1.000000000000, 120.000000000000, 501, NULL, NULL, 1, 1, '2025-09-01 02:51:10'),
(5866, 2291, 3774.000000000000, 'SAR', 1.000000000000, 3774.000000000000, 504, NULL, NULL, 1, 1, '2025-09-01 02:51:10'),
(5867, 2292, -812.000000000000, 'SAR', 1.000000000000, -812.000000000000, 413, NULL, NULL, 1, 1, '2025-09-01 02:51:10'),
(5868, 2292, 812.000000000000, 'SAR', 1.000000000000, 812.000000000000, 388, NULL, NULL, 1, 1, '2025-09-01 02:51:10'),
(5869, 2293, -6500.000000000000, 'SAR', 1.000000000000, -6500.000000000000, 68, NULL, NULL, 9, 1, '2025-10-09 02:57:02'),
(5870, 2293, 0.000000000000, 'SAR', 1.000000000000, 0.000000000000, 499, NULL, NULL, 9, 1, '2025-10-09 02:57:02'),
(5871, 2293, 0.000000000000, 'SAR', 1.000000000000, 0.000000000000, 503, NULL, NULL, 9, 1, '2025-10-09 02:57:02'),
(5872, 2293, 1733.000000000000, 'SAR', 1.000000000000, 1733.000000000000, 413, NULL, NULL, 9, 1, '2025-10-09 02:57:02'),
(5873, 2293, 0.000000000000, 'SAR', 1.000000000000, 0.000000000000, 501, NULL, NULL, 9, 1, '2025-10-09 02:57:02'),
(5874, 2293, 4767.000000000000, 'SAR', 1.000000000000, 4767.000000000000, 504, NULL, NULL, 9, 1, '2025-10-09 02:57:02'),
(5875, 2294, -1733.000000000000, 'SAR', 1.000000000000, -1733.000000000000, 413, NULL, NULL, 9, 1, '2025-10-09 02:57:02'),
(5876, 2294, 1733.000000000000, 'SAR', 1.000000000000, 1733.000000000000, 388, NULL, NULL, 9, 1, '2025-10-09 02:57:02');

-- --------------------------------------------------------

--
-- Table structure for table `tblgroup`
--

CREATE TABLE `tblgroup` (
  `Id` int(11) NOT NULL,
  `BranchID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `LeaderGroup` int(11) NOT NULL,
  `Description` text NOT NULL,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDate` date NOT NULL,
  `LastUpdateDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblgroup`
--

INSERT INTO `tblgroup` (`Id`, `BranchID`, `Name`, `LeaderGroup`, `Description`, `CreatedBy`, `CreatedDate`, `LastUpdateDate`) VALUES
(3, 1, 'مجموعة الموارد البشرية', 0, '', 1, '2025-10-09', '2025-10-08 23:04:57');

-- --------------------------------------------------------

--
-- Table structure for table `tblincentives`
--

CREATE TABLE `tblincentives` (
  `Id` int(11) NOT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `UserID` varchar(100) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `incentive_type` tinytext DEFAULT NULL COMMENT 'نوع المكافئة 1 شهرية 2 لها تاريخ استحقاق',
  `AmountType` varchar(20) DEFAULT NULL,
  `Amount` text DEFAULT NULL,
  `Currency` varchar(20) DEFAULT NULL,
  `Reason` text DEFAULT NULL,
  `Status` int(11) DEFAULT NULL,
  `for_what` int(11) DEFAULT NULL,
  `extionsion` text DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `monthly` int(11) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedDate` date NOT NULL,
  `LastUpdateDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblincentives`
--

INSERT INTO `tblincentives` (`Id`, `BranchID`, `UserID`, `name`, `incentive_type`, `AmountType`, `Amount`, `Currency`, `Reason`, `Status`, `for_what`, `extionsion`, `DueDate`, `monthly`, `CreatedBy`, `CreatedDate`, `LastUpdateDate`) VALUES
(3, 1, '19', 'اداء متميز', '2', 'amount', '100', 'SAR', NULL, 1, 1, NULL, '2025-10-31', NULL, 9, '2025-10-09', '2025-10-08 23:58:15');

-- --------------------------------------------------------

--
-- Table structure for table `tbljobgrade`
--

CREATE TABLE `tbljobgrade` (
  `Id` int(11) NOT NULL,
  `BranchID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text NOT NULL,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDate` date NOT NULL,
  `LastUpdateDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tbljobgrade`
--

INSERT INTO `tbljobgrade` (`Id`, `BranchID`, `Name`, `Description`, `CreatedBy`, `CreatedDate`, `LastUpdateDate`) VALUES
(4, 1, 'الدرجة الوظيفية السابعة', '', 1, '2025-10-09', '2025-10-08 23:06:15');

-- --------------------------------------------------------

--
-- Table structure for table `tbljobtitle`
--

CREATE TABLE `tbljobtitle` (
  `Id` int(11) NOT NULL,
  `BranchID` int(11) NOT NULL,
  `Name` varchar(100) DEFAULT NULL,
  `ParentID` int(11) DEFAULT NULL,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDate` date NOT NULL,
  `lastUpdateDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tbljobtitle`
--

INSERT INTO `tbljobtitle` (`Id`, `BranchID`, `Name`, `ParentID`, `CreatedBy`, `CreatedDate`, `lastUpdateDate`) VALUES
(7, 1, 'المسميات الوظيفية لادارة الموارد البشرية', NULL, 1, '2025-10-09', '2025-10-08 23:03:13'),
(8, 1, 'مدير الموارد البشرية', 7, 1, '2025-10-09', '2025-10-08 23:03:30'),
(9, 1, 'مختص تدريب', 7, 1, '2025-10-09', '2025-10-08 23:03:56'),
(10, 1, 'مدير33', 7, 1, '2026-02-03', '2026-02-02 23:02:26'),
(11, 1, 'بسيييي', 7, 1, '2026-02-03', '2026-02-02 23:05:07');

-- --------------------------------------------------------

--
-- Table structure for table `tblleaverequest`
--

CREATE TABLE `tblleaverequest` (
  `Id` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `leavetype` varchar(20) DEFAULT NULL,
  `path` text DEFAULT NULL,
  `leave_start_date` date DEFAULT NULL,
  `leave_end_date` date DEFAULT NULL,
  `day_leave` varchar(100) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `Draft` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `CreatedDate` date DEFAULT NULL,
  `LastUpdateDate` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblleaverequest`
--

INSERT INTO `tblleaverequest` (`Id`, `UserID`, `BranchID`, `leavetype`, `path`, `leave_start_date`, `leave_end_date`, `day_leave`, `status`, `Draft`, `description`, `created_by`, `CreatedDate`, `LastUpdateDate`) VALUES
(2, 11, 1, '7', '../uploads/hrsys/leavefile/leavefile68b3759ee0b181756591518.jpg', '2025-08-24', '2025-08-25', '2', 1, 1, NULL, 11, '2025-08-31', '2025-08-30 22:05:18'),
(3, 11, 1, '8', NULL, '2025-07-23', '2025-07-30', '8', 1, 1, NULL, 11, '2025-08-31', '2025-08-31 15:23:53'),
(4, 19, 1, '9', '../uploads/hrsys/leavefile/leavefile68e6f58870c401759966600.jpeg', '2025-10-09', '2025-10-09', '1', 1, 1, NULL, 19, '2025-10-09', '2025-10-08 23:36:40');

-- --------------------------------------------------------

--
-- Table structure for table `tblpermission`
--

CREATE TABLE `tblpermission` (
  `PermID` int(11) NOT NULL,
  `Parent` int(11) DEFAULT NULL,
  `code` varchar(20) DEFAULT NULL,
  `AppID` varchar(5) DEFAULT NULL,
  `PermName` varchar(100) NOT NULL,
  `Menus` varchar(50) DEFAULT NULL,
  `IsDisabled` tinyint(1) DEFAULT NULL,
  `LastUpdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblpermission`
--

INSERT INTO `tblpermission` (`PermID`, `Parent`, `code`, `AppID`, `PermName`, `Menus`, `IsDisabled`, `LastUpdate`) VALUES
(1, NULL, NULL, 'STO', 'عرض المنتج', '2,3', NULL, '2023-01-16 23:36:45'),
(2, NULL, NULL, 'STO', 'إضافة منتج', '2,116', NULL, '2023-01-17 14:39:31'),
(3, 1, NULL, 'STO', 'تعديل منتج', NULL, NULL, '2023-01-17 19:46:09'),
(4, 1, NULL, 'STO', 'حذف منتج', NULL, NULL, '2023-01-17 19:46:06'),
(5, 1, NULL, 'STO', 'معلومات المنتج', NULL, NULL, '2023-01-17 19:46:03'),
(6, 1, NULL, 'STO', 'حركة المنتج', NULL, NULL, '2023-01-17 19:45:59'),
(7, 1, NULL, 'STO', 'مخزون المنتج', NULL, NULL, '2023-01-17 19:45:57'),
(8, NULL, NULL, 'STO', 'الأوامر المخزنية(إضافة)', '2,4', NULL, '2023-01-17 19:45:57'),
(9, NULL, NULL, 'STO', 'الأوامر المخزنية(صرف)', '2,4', NULL, '2023-01-17 19:45:57'),
(10, NULL, NULL, 'STO', 'الأوامر المخزنية(تحويل)', '2,4', NULL, '2023-01-17 19:45:57'),
(11, NULL, NULL, 'STO', 'تعديل واضافة المستودعات', '2,5', NULL, '2023-01-18 14:53:49'),
(12, NULL, NULL, 'STO', 'تعديل إعدادات المخزون', '2,7', NULL, '2023-01-18 14:53:49'),
(13, NULL, NULL, 'STO', 'تعديل إعدادات المنتجات', '2,8', NULL, '2023-01-18 14:53:49'),
(14, NULL, NULL, 'SAL', 'عرض الفواتير', '9,10', NULL, '2023-10-10 02:22:20'),
(15, 14, NULL, 'SAL', 'عرض فواتيرة فقط', '9,10', NULL, '2023-10-10 02:25:59'),
(16, 14, NULL, 'SAL', 'عرض ربح الفاتورة', '9,10', NULL, '2023-10-10 02:21:49'),
(17, NULL, NULL, 'SAL', 'إنشاء فواتير بيع', '9,11', NULL, '2023-10-10 02:08:47'),
(18, 17, NULL, 'SAL', 'تسجيل عميل جديد عند الفوترة', NULL, NULL, '2023-10-10 02:14:22'),
(19, NULL, NULL, 'SAL', 'إنشاء فواتيرمرتجعة', '9,56', NULL, '2023-10-10 02:09:39'),
(20, NULL, NULL, 'POS', 'كاشير', '57,58,59', NULL, '2023-05-06 20:44:12'),
(21, NULL, NULL, 'POS', 'إعدادت نقاط البيع', '57,60', NULL, '2023-02-07 01:01:39'),
(22, NULL, NULL, 'CLI', 'عرض العملاء', '109,110', NULL, '2023-02-07 01:01:39'),
(23, 22, NULL, 'CLI', 'إضافة عميل', '109,111', NULL, '2023-02-07 01:19:41'),
(24, NULL, NULL, 'CLI', 'إعدادات العملاء', '109,112', NULL, '2023-02-07 01:01:39'),
(25, NULL, NULL, 'PUR', 'عرض فواتير اشراء', '61,62', NULL, '2023-02-07 01:01:39'),
(26, NULL, NULL, 'PUR', 'مرتجعات المشتريات', '61,63', NULL, '2023-02-07 01:01:39'),
(27, NULL, NULL, 'PUR', 'إدارة الموردين', '61,64', NULL, '2023-02-07 01:01:39'),
(28, NULL, NULL, 'PUR', 'إعدادات المشتريات', '61,66', NULL, '2023-02-07 01:01:39'),
(29, NULL, NULL, 'FIN', 'إصدار سندات قبض', '91,92', NULL, '2023-02-07 01:01:39'),
(30, NULL, NULL, 'FIN', 'إصدار سندات صرف', '91,115', NULL, '2023-02-07 01:17:51'),
(31, NULL, NULL, 'FIN', 'إدارة الخزائن والحسابات البنكية', '91,94', NULL, '2023-02-07 01:01:39'),
(32, NULL, NULL, 'FIN', 'تعديل إعدادات المالية', '91,95', NULL, '2023-02-07 01:01:39'),
(33, NULL, NULL, 'ACC', 'عرض قيود اليومية', '86,98', NULL, '2023-02-07 01:01:39'),
(34, 33, NULL, 'ACC', 'إضافة قيود يومية', NULL, NULL, '2023-02-07 01:19:53'),
(35, NULL, NULL, 'ACC', 'استعراض شجرة الحسابات', '86,87', NULL, '2023-02-07 01:19:53'),
(36, 35, NULL, 'ACC', 'الاضافة والتعديل على شجرة الحسابات', NULL, NULL, '2023-02-07 01:21:57'),
(37, NULL, NULL, 'ACC', 'عرض مراكز التكلقة', '86,89', NULL, '2023-02-07 01:21:57'),
(38, 37, NULL, 'ACC', 'الاضافة والتعديل على مراكز التكلقة', NULL, NULL, '2023-02-07 01:23:15'),
(39, NULL, NULL, 'ACC', 'إدارة إعدادت الحسابات العامة', '86,90', NULL, '2023-02-07 01:23:15'),
(40, 39, NULL, 'ACC', 'إغلاق / فتح الفترات', NULL, NULL, '2023-02-07 01:24:47'),
(41, NULL, NULL, 'REP', 'استعراض التقارير', '67', NULL, '2023-02-07 01:24:47'),
(42, NULL, NULL, 'USR', 'إدارة المستخدمين', '101,102', NULL, '2023-02-07 01:24:47'),
(43, NULL, NULL, 'USR', 'إدارة الأدوار الوظيفية', '101,104', NULL, '2023-02-07 01:24:47'),
(44, NULL, NULL, 'USR', 'إدارة الجلسات', '101,107', NULL, '2023-02-07 01:24:47'),
(45, 51, 'add_own_session', 'POS', 'فتح جلسة لنفسه', '57,58', NULL, '2023-05-07 23:26:55'),
(47, 50, 'add_any_session', 'POS', 'فتح جلسات لجميع المستخدمين', '57,58', NULL, '2023-05-07 23:27:11'),
(48, 51, 'close_own_session', 'POS', 'اغلاق الجلسات الخاصة بة', '57,59', NULL, '2023-05-07 20:58:30'),
(49, 50, 'close_any_session', 'POS', 'اغلاق جلسات جميع المستخدمين', '57,59', NULL, '2023-05-07 22:51:05'),
(50, 51, 'show_any_session', 'POS', 'عرض جميع الجلسات', '57,59', NULL, '2023-05-07 21:01:40'),
(51, NULL, 'show_his_session', 'POS', 'عرض الجلسات الخاصة به', '57,59', NULL, '2023-05-07 23:06:32'),
(52, 50, 'confirm_any_session', 'POS', 'تاكيد اغلاق جميع الجلسات', '57,59', NULL, '2023-05-07 23:03:51'),
(54, 51, 'confirm_own_session', 'POS', 'تاكيد اغلاق الجلسات الخاصة به', '57,59', NULL, '2023-05-07 23:03:58'),
(55, NULL, 'change_pos_price', 'POS', 'تعديل سعر البيع', NULL, NULL, '2023-05-07 18:51:38'),
(56, NULL, 'add_pos_discount', 'POS', 'اضافة خصم', NULL, NULL, '2023-05-07 18:51:38'),
(200, NULL, NULL, 'SAL', 'إنشاء إشعارات الفواتير', '9,55', NULL, '2023-10-10 02:11:56'),
(205, NULL, NULL, 'SAL', 'إنشاء عروض أسعار', '9,114', NULL, '2023-10-10 02:11:51'),
(210, NULL, NULL, 'SAL', 'تعديل إعدادت الفواتير', '9,117', NULL, '2023-10-10 02:12:15'),
(211, 1, NULL, 'STO', 'طباعة باركود', '2,131', NULL, '2025-05-09 18:39:18'),
(212, 17, 'inv_sign_required', 'SAL', 'طلب توقيع العميل عند إصدار الفاتورة', NULL, NULL, '2026-01-22 00:11:58'),
(220, NULL, NULL, 'HR', 'إضافة موظف', '133,151', NULL, '2025-06-04 13:23:58'),
(221, 220, NULL, 'HR', 'عرض موظف', NULL, NULL, '2025-06-04 13:23:58'),
(222, 220, NULL, 'HR', 'تعديل موظف', NULL, NULL, '2025-06-04 13:23:58'),
(240, NULL, NULL, 'HR', 'إضافة مكافئه', '133,141', NULL, '2025-06-04 13:28:09'),
(241, 240, NULL, 'HR', 'عرض مكافئه', '', NULL, '2025-06-04 13:28:09'),
(242, 240, NULL, 'HR', 'تعديل مكافئه', '', NULL, '2025-06-04 13:28:09'),
(243, 240, NULL, 'HR', 'حذف مكافئه', '', NULL, '2025-06-04 13:28:09'),
(244, 240, NULL, 'HR', 'اعتماد مكافئه', '', NULL, '2025-06-04 13:28:09'),
(250, NULL, NULL, 'HR', 'إضافة تعويض', '133,144', NULL, '2025-06-04 13:28:09'),
(251, 250, NULL, 'HR', 'عرض تعويض', '', NULL, '2025-06-04 13:28:09'),
(252, 250, NULL, 'HR', 'تعديل تعويض', '', NULL, '2025-06-04 13:28:09'),
(253, 250, NULL, 'HR', 'حذف تعويض', '', NULL, '2025-06-04 13:28:09'),
(254, 250, NULL, 'HR', 'اعتماد تعويض', '', NULL, '2025-06-04 13:28:09'),
(260, NULL, NULL, 'HR', 'إضافة خصم', '133,145', NULL, '2025-06-04 13:28:09'),
(261, 260, NULL, 'HR', 'عرض خصم', '', NULL, '2025-06-04 13:28:09'),
(262, 260, NULL, 'HR', 'تعديل خصم', '', NULL, '2025-06-04 13:28:09'),
(263, 260, NULL, 'HR', 'حذف خصم', '', NULL, '2025-06-04 13:28:09'),
(264, 260, NULL, 'HR', 'اعتماد خصم', '', NULL, '2025-06-04 13:28:09'),
(270, NULL, NULL, 'HR', 'الاعدادات', '133,153', NULL, '2025-06-04 13:28:09'),
(271, 270, NULL, 'HR', 'الفترات', '', NULL, '2025-06-04 13:28:09'),
(272, 270, NULL, 'HR', 'اجهزة البصمة', '', NULL, '2025-06-04 13:28:09'),
(273, 270, NULL, 'HR', 'الاقسام', '', NULL, '2025-06-04 13:28:09'),
(274, 270, NULL, 'HR', 'المسميات الوظيفية', '', NULL, '2025-06-04 13:28:09'),
(275, 270, NULL, 'HR', 'شركات التامين', '', NULL, '2025-06-04 13:28:09'),
(276, 270, NULL, 'HR', 'المجموعات', '', NULL, '2025-06-04 13:28:09'),
(277, 270, NULL, 'HR', 'الدرجات الوظيفية', '', NULL, '2025-06-04 13:28:09'),
(278, 270, NULL, 'HR', 'انماط العمل', '', NULL, '2025-06-04 13:28:09'),
(279, 270, NULL, 'HR', 'الاجازات الرسمية', '', NULL, '2025-06-04 13:28:09'),
(280, 270, NULL, 'HR', 'الاجازات العامة', '', NULL, '2025-06-04 13:28:09'),
(281, 271, NULL, 'HR', 'اضافة فترة', '', NULL, '2025-06-04 13:28:09'),
(282, 271, NULL, 'HR', 'عرض فترة', '', NULL, '2025-06-04 13:28:10'),
(283, 271, NULL, 'HR', 'تعديل فترة', '', NULL, '2025-06-04 13:28:10'),
(284, 271, NULL, 'HR', 'حذف فترة', '', NULL, '2025-06-04 13:28:10'),
(285, 272, NULL, 'HR', 'اضافة بصمة', '', NULL, '2025-06-04 13:28:10'),
(286, 272, NULL, 'HR', 'عرض بصمة', '', NULL, '2025-06-04 13:28:10'),
(287, 272, NULL, 'HR', 'تعديل بصمة', '', NULL, '2025-06-04 13:28:10'),
(288, 272, NULL, 'HR', 'حذف بصمة', '', NULL, '2025-06-04 13:28:10'),
(289, 273, NULL, 'HR', 'اضافة قسم', '', NULL, '2025-06-04 13:28:10'),
(290, 273, NULL, 'HR', 'عرض قسم', '', NULL, '2025-06-04 13:28:10'),
(291, 273, NULL, 'HR', 'تعديل قسم', '', NULL, '2025-06-04 13:28:10'),
(292, 273, NULL, 'HR', 'حذف قسم', '', NULL, '2025-06-04 13:28:10'),
(293, 274, NULL, 'HR', 'اضافة مسمى وظيفي', '', NULL, '2025-06-04 13:30:33'),
(294, 274, NULL, 'HR', 'عرض مسمى وظيفي', '', NULL, '2025-06-04 13:30:33'),
(295, 274, NULL, 'HR', 'تعديل مسمى وظيفي', '', NULL, '2025-06-04 13:30:33'),
(296, 274, NULL, 'HR', 'حذف مسمى وظيفي', '', NULL, '2025-06-04 13:30:33'),
(297, 275, NULL, 'HR', 'اضافة شركة تامين', '', NULL, '2025-06-04 13:30:33'),
(298, 275, NULL, 'HR', 'عرض شركة تامين', '', NULL, '2025-06-04 13:30:33'),
(299, 275, NULL, 'HR', 'تعديل شركة تامين', '', NULL, '2025-06-04 13:30:33'),
(300, 275, NULL, 'HR', 'حذف شركة تامين', '', NULL, '2025-06-04 13:30:33'),
(301, 276, NULL, 'HR', 'اضافة مجموعه', '', NULL, '2025-06-04 13:30:33'),
(302, 276, NULL, 'HR', 'عرض مجموعه', '', NULL, '2025-06-04 13:30:33'),
(303, 276, NULL, 'HR', 'تعديل مجموعه', '', NULL, '2025-06-04 13:30:33'),
(304, 276, NULL, 'HR', 'حذف مجموعه', '', NULL, '2025-06-04 13:30:33'),
(305, 277, NULL, 'HR', 'اضافة درجة وظيفية', '', NULL, '2025-06-04 13:30:33'),
(306, 277, NULL, 'HR', 'عرض درجة وظيفية', '', NULL, '2025-06-04 13:30:33'),
(307, 277, NULL, 'HR', 'تعديل درجة وظيفية', '', NULL, '2025-06-04 13:30:33'),
(308, 277, NULL, 'HR', 'حذف درجة وظيفية', '', NULL, '2025-06-04 13:30:33'),
(309, 278, NULL, 'HR', 'اضافة نمط عمل', '', NULL, '2025-06-04 13:30:34'),
(310, 278, NULL, 'HR', 'عرض نمط عمل', '', NULL, '2025-06-04 13:30:34'),
(311, 278, NULL, 'HR', 'تعديل نمط عمل', '', NULL, '2025-06-04 13:30:34'),
(312, 278, NULL, 'HR', 'حذف نمط عمل', '', NULL, '2025-06-04 13:30:34'),
(313, 279, NULL, 'HR', 'اضافة اجازة رسمية', '', NULL, '2025-06-04 13:30:34'),
(314, 279, NULL, 'HR', 'عرض اجازة رسمية', '', NULL, '2025-06-04 13:30:34'),
(315, 279, NULL, 'HR', 'تعديل اجازة رسمية', '', NULL, '2025-06-04 13:30:34'),
(316, 279, NULL, 'HR', 'حذف اجازة رسمية', '', NULL, '2025-06-04 13:30:34'),
(317, 280, NULL, 'HR', 'اضافة اجازة عامة', '', NULL, '2025-06-04 13:30:34'),
(318, 280, NULL, 'HR', 'عرض اجازة عامة', '', NULL, '2025-06-04 13:30:34'),
(319, 280, NULL, 'HR', 'تعديل اجازة عامة', '', NULL, '2025-06-04 13:30:34'),
(320, 280, NULL, 'HR', 'حذف اجازة عامة', '', NULL, '2025-06-04 13:30:34'),
(340, NULL, NULL, 'HR', 'اضافة ترقية', '133,146', NULL, '2025-06-04 13:31:59'),
(341, 340, NULL, 'HR', 'عرض ترقية', '', NULL, '2025-06-04 13:31:59'),
(342, 340, NULL, 'HR', 'تعديل ترقية', '', NULL, '2025-06-04 13:31:59'),
(343, 340, NULL, 'HR', 'حذف ترقية', '', NULL, '2025-06-04 13:31:59'),
(344, 340, NULL, 'HR', 'اعتماد ترقية', '', NULL, '2025-06-04 13:31:59'),
(350, NULL, NULL, 'HR', 'اضافة عقد', '133,147', NULL, '2025-06-04 13:31:59'),
(351, 350, NULL, 'HR', 'عرض عقد', '', NULL, '2025-06-04 13:31:59'),
(352, 350, NULL, 'HR', 'تعديل عقد', '', NULL, '2025-06-04 13:31:59'),
(353, 350, NULL, 'HR', 'حذف عقد', '', NULL, '2025-06-04 13:31:59'),
(354, 350, NULL, 'HR', 'اعتماد عقد', '', NULL, '2025-06-04 13:31:59'),
(360, NULL, NULL, 'HR', 'الحضور والانصراف', '133,152', NULL, '2025-06-04 13:31:59'),
(361, 360, NULL, 'HR', 'عرض الحضور والانصراف', '', NULL, '2025-06-04 13:31:59'),
(362, 360, NULL, 'HR', 'تحضير موظف', '', NULL, '2025-06-04 13:31:59'),
(363, 360, NULL, 'HR', 'رفع ملف الاكسل', '', NULL, '2025-06-04 13:31:59'),
(370, NULL, NULL, 'HR', 'إدارة الاجازات', '133,150', NULL, '2025-07-24 17:45:45'),
(371, NULL, NULL, 'HR', 'إدارة السلف', '133,148', NULL, '2025-07-24 17:45:45'),
(372, NULL, NULL, 'HR', 'إدارة الاستقالات', '133,149', NULL, '2025-07-24 17:45:45'),
(373, NULL, NULL, 'HR', 'إدارة الطلبات', '133,155', NULL, '2025-07-24 17:45:45'),
(374, NULL, NULL, 'HR', 'طلبات نسيان البصمة', '133,156', NULL, '2025-07-24 17:45:45'),
(380, NULL, NULL, 'HR', 'اعدادات الموظفين', '133,154', NULL, '2025-07-24 17:45:45'),
(381, 380, NULL, 'HR', 'إضافة استقاله لموظف', NULL, NULL, '2025-07-24 17:45:45'),
(382, 380, NULL, 'HR', 'إضافة إجازة لموظف', NULL, NULL, '2025-07-24 17:45:45'),
(383, 380, NULL, 'HR', 'إضافة سلفة لموظف', NULL, NULL, '2025-07-24 17:45:45'),
(384, 380, NULL, 'HR', 'فصل موظف', NULL, NULL, '2025-07-24 17:45:45'),
(386, 380, NULL, 'HR', 'تغير المدير لمجموعة الموظفين', NULL, NULL, '2025-07-24 17:45:45'),
(387, NULL, NULL, 'HR', 'إصدار الرواتب', '133,157', NULL, '2025-08-05 20:53:08');

-- --------------------------------------------------------

--
-- Table structure for table `tblpermissionmenu`
--

CREATE TABLE `tblpermissionmenu` (
  `PermID` int(11) NOT NULL,
  `MenuID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblpermissionmenu`
--

INSERT INTO `tblpermissionmenu` (`PermID`, `MenuID`) VALUES
(1, 2),
(1, 3),
(4, 2),
(4, 3);

-- --------------------------------------------------------

--
-- Table structure for table `tblremewal`
--

CREATE TABLE `tblremewal` (
  `Id` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `SectionID` int(11) DEFAULT NULL,
  `GroupID` int(11) DEFAULT NULL,
  `GradeID` int(11) DEFAULT NULL,
  `jobtitleID` int(11) DEFAULT NULL,
  `TypeID` int(11) DEFAULT NULL,
  `shiftID` varchar(30) DEFAULT NULL,
  `fingerID` varchar(30) DEFAULT NULL,
  `Salary` varchar(100) DEFAULT NULL,
  `Currency` varchar(20) DEFAULT NULL,
  `day` varchar(100) DEFAULT NULL,
  `Reason` text DEFAULT NULL,
  `state` int(22) DEFAULT NULL,
  `conform_date` date DEFAULT NULL,
  `come_id` int(11) DEFAULT NULL,
  `come_name` text DEFAULT NULL,
  `new_s_date` date DEFAULT NULL,
  `new_e_date` date DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedDate` date DEFAULT NULL,
  `LastUpdateDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblremewal`
--

INSERT INTO `tblremewal` (`Id`, `UserID`, `BranchID`, `SectionID`, `GroupID`, `GradeID`, `jobtitleID`, `TypeID`, `shiftID`, `fingerID`, `Salary`, `Currency`, `day`, `Reason`, `state`, `conform_date`, `come_id`, `come_name`, `new_s_date`, `new_e_date`, `CreatedBy`, `CreatedDate`, `LastUpdateDate`) VALUES
(1, 10, 1, 6, NULL, 3, 5, 1, '1', '1', '3000', 'SAR', NULL, NULL, 1, '2025-08-02', 1, 'انشاء موظف', '2025-08-02', '2028-08-02', 1, '2025-08-30', '2025-08-30 21:13:41'),
(2, 11, 1, 3, 1, 1, 3, 5, '1', '1', '1800', 'SAR', NULL, NULL, 1, '2025-08-13', 1, 'انشاء موظف', '2025-08-13', '2028-08-13', 1, '2025-08-31', '2025-08-31 14:51:35'),
(3, 12, 1, 3, 1, 3, 2, 1, '1', '1', '3500', 'SAR', NULL, NULL, 1, '2025-01-01', 1, 'انشاء موظف', '2025-01-01', '2028-01-01', 1, '2025-08-31', '2025-09-03 14:02:07'),
(4, 13, 1, 5, 2, 3, 5, 1, '1', '1', '4000', 'SAR', NULL, NULL, 1, '2025-05-01', 1, 'انشاء موظف', '2025-05-01', '2026-10-06', 1, '2025-10-06', '2025-10-05 23:04:43'),
(5, 14, 1, 6, 2, 2, 6, 4, '1', '1', '2750', 'SAR', NULL, NULL, 1, '2025-10-01', 1, 'انشاء موظف', '2025-10-01', '2027-10-06', 1, '2025-10-06', '2025-10-05 23:45:31'),
(6, 17, 1, 5, 2, 3, 5, 1, '1', '1', '4500', 'SAR', NULL, NULL, 1, '2024-10-06', 1, 'انشاء موظف', '2024-10-06', '2027-10-06', 1, '2025-10-06', '2025-10-05 23:36:11'),
(7, 18, 1, 8, 3, 4, 7, 7, '3', '2', '4700', 'SAR', NULL, NULL, 1, '2025-01-01', 1, 'انشاء موظف', '2025-01-01', '2027-01-01', 1, '2025-10-09', '2026-02-02 23:07:29'),
(8, 19, 1, 8, 3, 4, 9, 7, '2', '2', '2000', 'SAR', NULL, NULL, 1, '2024-01-01', 1, 'انشاء موظف', '2024-01-01', '2026-01-01', 1, '2025-10-09', '2025-10-08 23:27:24'),
(9, 19, 1, 8, 3, 4, 9, 7, '2', '2', '2500', 'SAR', NULL, NULL, 1, '2025-10-09', 3, 'ترقية موظف', '2024-01-01', '2026-01-01', 9, '2025-10-09', '2025-10-09 00:00:47'),
(10, 19, 1, 8, 3, 4, 9, 7, '2', '2', '3000', 'SAR', NULL, NULL, 1, '2025-10-09', 3, 'ترقية موظف', '2024-01-01', '2026-01-01', 9, '2025-10-09', '2025-10-09 00:34:27'),
(11, 18, 1, 8, 3, 4, 7, 7, '3', '2', '4700', 'SAR', NULL, NULL, 1, '2025-10-09', 3, 'ترقية موظف', '2025-01-01', '2027-01-01', 1, '2025-10-09', '2026-02-02 23:07:29'),
(12, 20, 1, 8, 3, 4, 8, 7, '2', '2', '3333', 'SAR', NULL, NULL, 1, '2025-01-28', 1, 'انشاء موظف', '2025-01-28', '2026-10-23', 1, '2026-02-03', '2026-02-03 00:04:55');

-- --------------------------------------------------------

--
-- Table structure for table `tblresignation`
--

CREATE TABLE `tblresignation` (
  `Id` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `Reason` text DEFAULT NULL,
  `Status` int(11) DEFAULT NULL,
  `Draft` int(11) DEFAULT NULL,
  `type` int(11) DEFAULT NULL COMMENT '1- استقاله\r\n2- فصل',
  `created_by` int(11) DEFAULT NULL,
  `CreatedDate` date DEFAULT NULL,
  `LastUpdateDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblresignation`
--

INSERT INTO `tblresignation` (`Id`, `UserID`, `BranchID`, `DueDate`, `Reason`, `Status`, `Draft`, `type`, `created_by`, `CreatedDate`, `LastUpdateDate`) VALUES
(1, 12, 1, '2025-08-31', 'تجريبي', 1, 1, 2, 1, '2025-08-31', '2025-08-31 00:18:44');

-- --------------------------------------------------------

--
-- Table structure for table `tblroutguide`
--

CREATE TABLE `tblroutguide` (
  `ID` smallint(11) NOT NULL,
  `AppCode` varchar(3) NOT NULL,
  `ControllerCode` varchar(20) NOT NULL,
  `ControllerName` varchar(200) NOT NULL,
  `wizard_url` varchar(20) DEFAULT NULL,
  `DefaultAccountID` bigint(20) DEFAULT NULL,
  `RoutingAccountID` bigint(20) DEFAULT NULL,
  `account_type` tinyint(1) DEFAULT NULL,
  `routing_options` varchar(5) DEFAULT NULL,
  `controller_sort` varchar(5) DEFAULT NULL,
  `is_hidden` tinyint(1) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `BranchID` int(10) UNSIGNED DEFAULT NULL,
  `RowVersion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblroutguide`
--

INSERT INTO `tblroutguide` (`ID`, `AppCode`, `ControllerCode`, `ControllerName`, `wizard_url`, `DefaultAccountID`, `RoutingAccountID`, `account_type`, `routing_options`, `controller_sort`, `is_hidden`, `UserID`, `BranchID`, `RowVersion`) VALUES
(2, 'STO', 'store', 'المستودعات', NULL, 20, 20, 1, 'A,M', '1', NULL, 0, 1, '2022-06-22 00:16:54'),
(6, 'PUR', 'supplier', 'الموردين', NULL, 46, 46, 1, 'A,M,N', '2', NULL, 1, 1, '2022-06-22 00:16:54'),
(7, 'STO', 'req_in', 'إذن إضافة مخزن', NULL, 378, 378, 0, NULL, '2', NULL, NULL, 1, '2022-06-22 00:16:54'),
(8, 'STO', 'req_out', 'إذن صرف مخزن', NULL, 212, 212, 0, NULL, '3', NULL, NULL, 1, '2022-06-22 00:16:54'),
(11, 'STO', 'req_tran', 'إذن تحويل مخزن', NULL, 30, 30, 0, NULL, '4', 1, NULL, 1, '2022-06-22 00:16:54'),
(12, 'PUR', 'pur_inv', 'المشتريات', NULL, 92, 92, 0, 'A,M,N', '1', NULL, NULL, 1, '2022-06-22 00:16:54'),
(14, 'SAL', 'sal_refund', 'مردود المبيعات', NULL, 103, 103, 0, 'A,M,N', '3', NULL, NULL, 1, '2022-10-04 21:21:39'),
(15, 'SAL', 'pos_salesman', 'عهد الموظفين', NULL, 21, 21, 1, NULL, '5', 1, NULL, 1, '2022-10-04 21:21:39'),
(16, 'SAL', 'client', 'العملاء', 'client-add', 18, 18, 1, 'A,M', '2', NULL, 0, 1, '2022-06-22 00:16:54'),
(19, 'SAL', 'sal_vat', 'ضريبة القيمة المضافة المحصلة', NULL, 354, 354, 0, NULL, '4', NULL, NULL, 1, '2022-10-27 15:20:14'),
(20, 'PUR', 'pur_ship_cost', 'شحن المشتريات', NULL, 382, 382, 0, NULL, '4', 1, NULL, 1, '2022-10-27 15:20:14'),
(21, 'SAL', 'sales_cost', 'حساب تكلفة المبيعات', NULL, 381, 381, 0, NULL, '6', 1, NULL, 1, '2022-11-08 23:58:19'),
(22, 'SAL', 'sal_inv', 'المبيعات', NULL, 104, 104, 0, 'A,M,N', '1', NULL, NULL, 1, '2022-10-04 21:21:39'),
(67, 'FIN', 'treasury', 'الخزائن', NULL, 22, 22, 1, 'A,M', '1', NULL, 0, 1, '2022-06-22 00:16:54'),
(68, 'FIN', 'banks', 'البنوك', NULL, 23, 23, 1, 'A,M', '2', NULL, 0, 1, '2022-06-22 00:16:54'),
(69, 'SAL', 'sal_ship_cost', 'مصاريف توصيل مبيعات', NULL, 381, -1, 0, 'A,N', '6', NULL, NULL, 1, '2023-08-09 19:35:42'),
(71, 'SAL', 'sal_discount', 'خصم مسموح بة', NULL, NULL, NULL, 0, 'A,N', '6', 1, NULL, 1, '2023-08-09 19:35:42'),
(150, 'STO', 'store', 'المستودعات', NULL, 20, 20, 1, 'A,M', '1', NULL, 1, 55, '2023-12-25 00:07:33'),
(151, 'PUR', 'supplier', 'الموردين', NULL, 46, 46, 1, 'A,M,N', '2', NULL, 1, 55, '2023-12-25 00:07:33'),
(152, 'STO', 'req_in', 'إذن إضافة مخزن', NULL, 378, 378, 0, NULL, '2', NULL, 1, 55, '2023-12-25 00:07:33'),
(153, 'STO', 'req_out', 'إذن صرف مخزن', NULL, 212, 212, 0, NULL, '3', NULL, 1, 55, '2023-12-25 00:07:33'),
(154, 'STO', 'req_tran', 'إذن تحويل مخزن', NULL, 30, 30, 0, NULL, '4', 1, 1, 55, '2023-12-25 00:07:33'),
(155, 'PUR', 'pur_inv', 'المشتريات', NULL, 92, 92, 0, 'A,M,N', '1', NULL, 1, 55, '2023-12-25 00:07:33'),
(156, 'SAL', 'sal_refund', 'مردود المبيعات', NULL, 103, 103, 0, 'A,M,N', '3', NULL, 1, 55, '2023-12-25 00:07:33'),
(157, 'SAL', 'pos_salesman', 'عهد الموظفين', NULL, 21, 21, 1, NULL, '5', 1, 1, 55, '2023-12-25 00:07:33'),
(158, 'SAL', 'client', 'العملاء', 'client-add', 18, 18, 1, 'A,M', '2', NULL, 1, 55, '2023-12-25 00:07:33'),
(159, 'SAL', 'sal_vat', 'ضريبة القيمة المضافة المحصلة', NULL, 354, 354, 0, NULL, '4', NULL, 1, 55, '2023-12-25 00:07:33'),
(160, 'PUR', 'pur_ship_cost', 'شحن المشتريات', NULL, 382, 382, 0, NULL, '4', 1, 1, 55, '2023-12-25 00:07:33'),
(161, 'SAL', 'sales_cost', 'حساب تكلفة المبيعات', NULL, 381, 381, 0, NULL, '6', 1, 1, 55, '2023-12-25 00:07:33'),
(162, 'SAL', 'sal_inv', 'المبيعات', NULL, 104, 104, 0, 'A,M,N', '1', NULL, 1, 55, '2023-12-25 00:07:33'),
(163, 'FIN', 'treasury', 'الخزائن', NULL, 22, 22, 1, 'A,M', '1', NULL, 1, 55, '2023-12-25 00:07:33'),
(164, 'FIN', 'banks', 'البنوك', NULL, 23, 23, 1, 'A,M', '2', NULL, 1, 55, '2023-12-25 00:07:33'),
(165, 'SAL', 'sal_ship_cost', 'مصاريف توصيل مبيعات', NULL, 381, -1, 0, 'A,N', '6', NULL, 1, 55, '2023-12-25 00:07:33'),
(166, 'SAL', 'sal_discount', 'خصم مسموح بة', NULL, NULL, NULL, 0, 'A,N', '6', 1, 1, 55, '2023-12-25 00:07:33'),
(167, 'STO', 'store', 'المستودعات', NULL, 20, 20, 1, 'A,M', '1', NULL, 1, 56, '2023-12-30 19:34:35'),
(168, 'PUR', 'supplier', 'الموردين', NULL, 46, 46, 1, 'A,M,N', '2', NULL, 1, 56, '2023-12-30 19:34:35'),
(169, 'STO', 'req_in', 'إذن إضافة مخزن', NULL, 378, 378, 0, NULL, '2', NULL, 1, 56, '2023-12-30 19:34:35'),
(170, 'STO', 'req_out', 'إذن صرف مخزن', NULL, 212, 212, 0, NULL, '3', NULL, 1, 56, '2023-12-30 19:34:35'),
(171, 'STO', 'req_tran', 'إذن تحويل مخزن', NULL, 30, 30, 0, NULL, '4', 1, 1, 56, '2023-12-30 19:34:35'),
(172, 'PUR', 'pur_inv', 'المشتريات', NULL, 92, 92, 0, 'A,M,N', '1', NULL, 1, 56, '2023-12-30 19:34:35'),
(173, 'SAL', 'sal_refund', 'مردود المبيعات', NULL, 103, 103, 0, 'A,M,N', '3', NULL, 1, 56, '2023-12-30 19:34:35'),
(174, 'SAL', 'pos_salesman', 'عهد الموظفين', NULL, 21, 21, 1, NULL, '5', 1, 1, 56, '2023-12-30 19:34:35'),
(175, 'SAL', 'client', 'العملاء', 'client-add', 18, 18, 1, 'A,M', '2', NULL, 1, 56, '2023-12-30 19:34:35'),
(176, 'SAL', 'sal_vat', 'ضريبة القيمة المضافة المحصلة', NULL, 354, 354, 0, NULL, '4', NULL, 1, 56, '2023-12-30 19:34:35'),
(177, 'PUR', 'pur_ship_cost', 'شحن المشتريات', NULL, 382, 382, 0, NULL, '4', 1, 1, 56, '2023-12-30 19:34:35'),
(178, 'SAL', 'sales_cost', 'حساب تكلفة المبيعات', NULL, 381, 381, 0, NULL, '6', 1, 1, 56, '2023-12-30 19:34:35'),
(179, 'SAL', 'sal_inv', 'المبيعات', NULL, 104, 104, 0, 'A,M,N', '1', NULL, 1, 56, '2023-12-30 19:34:35'),
(180, 'FIN', 'treasury', 'الخزائن', NULL, 22, 22, 1, 'A,M', '1', NULL, 1, 56, '2023-12-30 19:34:35'),
(181, 'FIN', 'banks', 'البنوك', NULL, 23, 23, 1, 'A,M', '2', NULL, 1, 56, '2023-12-30 19:34:35'),
(182, 'SAL', 'sal_ship_cost', 'مصاريف توصيل مبيعات', NULL, 381, -1, 0, 'A,N', '6', NULL, 1, 56, '2023-12-30 19:34:35'),
(183, 'SAL', 'sal_discount', 'خصم مسموح بة', NULL, NULL, NULL, 0, 'A,N', '6', 1, 1, 56, '2023-12-30 19:34:35'),
(198, 'SAL', 'profit_loss_account', 'حساب عجز & زيادة الصندوق', NULL, 391, 391, 0, NULL, '7', NULL, NULL, 1, '2024-03-23 04:21:39'),
(199, 'SAL', 'pos_sales_close', 'عهد مبيعات POS', NULL, 379, 379, 0, NULL, '7', NULL, NULL, 1, '2024-03-23 04:21:39'),
(200, 'STO', 'store', 'المستودعات', NULL, 20, 20, 1, 'A,M', '1', NULL, 1, 2, '2025-02-13 07:37:58'),
(201, 'PUR', 'supplier', 'الموردين', NULL, 46, 46, 1, 'A,M,N', '2', NULL, 1, 2, '2025-02-13 07:37:58'),
(202, 'STO', 'req_in', 'إذن إضافة مخزن', NULL, 378, 378, 0, NULL, '2', NULL, 1, 2, '2025-02-13 07:37:58'),
(203, 'STO', 'req_out', 'إذن صرف مخزن', NULL, 212, 212, 0, NULL, '3', NULL, 1, 2, '2025-02-13 07:37:58'),
(204, 'STO', 'req_tran', 'إذن تحويل مخزن', NULL, 30, 30, 0, NULL, '4', 1, 1, 2, '2025-02-13 07:37:58'),
(205, 'PUR', 'pur_inv', 'المشتريات', NULL, 92, 92, 0, 'A,M,N', '1', NULL, 1, 2, '2025-02-13 07:37:58'),
(206, 'SAL', 'sal_refund', 'مردود المبيعات', NULL, 103, 103, 0, 'A,M,N', '3', NULL, 1, 2, '2025-02-13 07:37:58'),
(207, 'SAL', 'pos_salesman', 'عهد الموظفين', NULL, 21, 21, 1, NULL, '5', 1, 1, 2, '2025-02-13 07:37:58'),
(208, 'SAL', 'client', 'العملاء', 'client-add', 18, 18, 1, 'A,M', '2', NULL, 1, 2, '2025-02-13 07:37:58'),
(209, 'SAL', 'sal_vat', 'ضريبة القيمة المضافة المحصلة', NULL, 354, 354, 0, NULL, '4', NULL, 1, 2, '2025-02-13 07:37:58'),
(210, 'PUR', 'pur_ship_cost', 'شحن المشتريات', NULL, 382, 382, 0, NULL, '4', 1, 1, 2, '2025-02-13 07:37:58'),
(211, 'SAL', 'sales_cost', 'حساب تكلفة المبيعات', NULL, 381, 381, 0, NULL, '6', 1, 1, 2, '2025-02-13 07:37:58'),
(212, 'SAL', 'sal_inv', 'المبيعات', NULL, 104, 104, 0, 'A,M,N', '1', NULL, 1, 2, '2025-02-13 07:37:58'),
(213, 'FIN', 'treasury', 'الخزائن', NULL, 22, 22, 1, 'A,M', '1', NULL, 1, 2, '2025-02-13 07:37:58'),
(214, 'FIN', 'banks', 'البنوك', NULL, 23, 23, 1, 'A,M', '2', NULL, 1, 2, '2025-02-13 07:37:58'),
(215, 'SAL', 'sal_ship_cost', 'مصاريف توصيل مبيعات', NULL, 381, -1, 0, 'A,N', '6', NULL, 1, 2, '2025-02-13 07:37:58'),
(216, 'SAL', 'sal_discount', 'خصم مسموح بة', NULL, NULL, NULL, 0, 'A,N', '6', 1, 1, 2, '2025-02-13 07:37:58'),
(217, 'SAL', 'profit_loss_account', 'حساب عجز & زيادة الصندوق', NULL, 391, 391, 0, NULL, '7', NULL, 1, 2, '2025-02-13 07:37:58'),
(218, 'SAL', 'pos_sales_close', 'عهد مبيعات POS', NULL, 379, 379, 0, NULL, '7', NULL, 1, 2, '2025-02-13 07:37:58'),
(231, 'STO', 'store', 'المستودعات', NULL, 20, 20, 1, 'A,M', '1', NULL, 1, 3, '2025-02-13 07:40:04'),
(232, 'PUR', 'supplier', 'الموردين', NULL, 46, 46, 1, 'A,M,N', '2', NULL, 1, 3, '2025-02-13 07:40:04'),
(233, 'STO', 'req_in', 'إذن إضافة مخزن', NULL, 378, 378, 0, NULL, '2', NULL, 1, 3, '2025-02-13 07:40:04'),
(234, 'STO', 'req_out', 'إذن صرف مخزن', NULL, 212, 212, 0, NULL, '3', NULL, 1, 3, '2025-02-13 07:40:04'),
(235, 'STO', 'req_tran', 'إذن تحويل مخزن', NULL, 30, 30, 0, NULL, '4', 1, 1, 3, '2025-02-13 07:40:04'),
(236, 'PUR', 'pur_inv', 'المشتريات', NULL, 92, 92, 0, 'A,M,N', '1', NULL, 1, 3, '2025-02-13 07:40:04'),
(237, 'SAL', 'sal_refund', 'مردود المبيعات', NULL, 103, 103, 0, 'A,M,N', '3', NULL, 1, 3, '2025-02-13 07:40:04'),
(238, 'SAL', 'pos_salesman', 'عهد الموظفين', NULL, 21, 21, 1, NULL, '5', 1, 1, 3, '2025-02-13 07:40:04'),
(239, 'SAL', 'client', 'العملاء', 'client-add', 18, 18, 1, 'A,M', '2', NULL, 1, 3, '2025-02-13 07:40:04'),
(240, 'SAL', 'sal_vat', 'ضريبة القيمة المضافة المحصلة', NULL, 354, 354, 0, NULL, '4', NULL, 1, 3, '2025-02-13 07:40:04'),
(241, 'PUR', 'pur_ship_cost', 'شحن المشتريات', NULL, 382, 382, 0, NULL, '4', 1, 1, 3, '2025-02-13 07:40:04'),
(242, 'SAL', 'sales_cost', 'حساب تكلفة المبيعات', NULL, 381, 381, 0, NULL, '6', 1, 1, 3, '2025-02-13 07:40:04'),
(243, 'SAL', 'sal_inv', 'المبيعات', NULL, 104, 104, 0, 'A,M,N', '1', NULL, 1, 3, '2025-02-13 07:40:04'),
(244, 'FIN', 'treasury', 'الخزائن', NULL, 22, 22, 1, 'A,M', '1', NULL, 1, 3, '2025-02-13 07:40:04'),
(245, 'FIN', 'banks', 'البنوك', NULL, 23, 23, 1, 'A,M', '2', NULL, 1, 3, '2025-02-13 07:40:04'),
(246, 'SAL', 'sal_ship_cost', 'مصاريف توصيل مبيعات', NULL, 381, -1, 0, 'A,N', '6', NULL, 1, 3, '2025-02-13 07:40:04'),
(247, 'SAL', 'sal_discount', 'خصم مسموح بة', NULL, NULL, NULL, 0, 'A,N', '6', 1, 1, 3, '2025-02-13 07:40:04'),
(248, 'SAL', 'profit_loss_account', 'حساب عجز & زيادة الصندوق', NULL, 391, 391, 0, NULL, '7', NULL, 1, 3, '2025-02-13 07:40:04'),
(249, 'SAL', 'pos_sales_close', 'عهد مبيعات POS', NULL, 379, 379, 0, NULL, '7', NULL, 1, 3, '2025-02-13 07:40:04'),
(262, 'STO', 'store', 'المستودعات', NULL, 20, 20, 1, 'A,M', '1', NULL, 1, 4, '2025-02-13 07:43:59'),
(263, 'PUR', 'supplier', 'الموردين', NULL, 46, 46, 1, 'A,M,N', '2', NULL, 1, 4, '2025-02-13 07:43:59'),
(264, 'STO', 'req_in', 'إذن إضافة مخزن', NULL, 378, 378, 0, NULL, '2', NULL, 1, 4, '2025-02-13 07:43:59'),
(265, 'STO', 'req_out', 'إذن صرف مخزن', NULL, 212, 212, 0, NULL, '3', NULL, 1, 4, '2025-02-13 07:43:59'),
(266, 'STO', 'req_tran', 'إذن تحويل مخزن', NULL, 30, 30, 0, NULL, '4', 1, 1, 4, '2025-02-13 07:43:59'),
(267, 'PUR', 'pur_inv', 'المشتريات', NULL, 92, 92, 0, 'A,M,N', '1', NULL, 1, 4, '2025-02-13 07:43:59'),
(268, 'SAL', 'sal_refund', 'مردود المبيعات', NULL, 103, 103, 0, 'A,M,N', '3', NULL, 1, 4, '2025-02-13 07:43:59'),
(269, 'SAL', 'pos_salesman', 'عهد الموظفين', NULL, 21, 21, 1, NULL, '5', 1, 1, 4, '2025-02-13 07:43:59'),
(270, 'SAL', 'client', 'العملاء', 'client-add', 18, 18, 1, 'A,M', '2', NULL, 1, 4, '2025-02-13 07:43:59'),
(271, 'SAL', 'sal_vat', 'ضريبة القيمة المضافة المحصلة', NULL, 354, 354, 0, NULL, '4', NULL, 1, 4, '2025-02-13 07:43:59'),
(272, 'PUR', 'pur_ship_cost', 'شحن المشتريات', NULL, 382, 382, 0, NULL, '4', 1, 1, 4, '2025-02-13 07:43:59'),
(273, 'SAL', 'sales_cost', 'حساب تكلفة المبيعات', NULL, 381, 381, 0, NULL, '6', 1, 1, 4, '2025-02-13 07:43:59'),
(274, 'SAL', 'sal_inv', 'المبيعات', NULL, 104, 104, 0, 'A,M,N', '1', NULL, 1, 4, '2025-02-13 07:43:59'),
(275, 'FIN', 'treasury', 'الخزائن', NULL, 22, 22, 1, 'A,M', '1', NULL, 1, 4, '2025-02-13 07:43:59'),
(276, 'FIN', 'banks', 'البنوك', NULL, 23, 23, 1, 'A,M', '2', NULL, 1, 4, '2025-02-13 07:43:59'),
(277, 'SAL', 'sal_ship_cost', 'مصاريف توصيل مبيعات', NULL, 381, -1, 0, 'A,N', '6', NULL, 1, 4, '2025-02-13 07:43:59'),
(278, 'SAL', 'sal_discount', 'خصم مسموح بة', NULL, NULL, NULL, 0, 'A,N', '6', 1, 1, 4, '2025-02-13 07:43:59'),
(279, 'SAL', 'profit_loss_account', 'حساب عجز & زيادة الصندوق', NULL, 391, 391, 0, NULL, '7', NULL, 1, 4, '2025-02-13 07:43:59'),
(280, 'SAL', 'pos_sales_close', 'عهد مبيعات POS', NULL, 379, 379, 0, NULL, '7', NULL, 1, 4, '2025-02-13 07:43:59'),
(293, 'STO', 'store', 'المستودعات', NULL, 20, 20, 1, 'A,M', '1', NULL, 1, 5, '2025-02-13 07:47:16'),
(294, 'PUR', 'supplier', 'الموردين', NULL, 46, 46, 1, 'A,M,N', '2', NULL, 1, 5, '2025-02-13 07:47:16'),
(295, 'STO', 'req_in', 'إذن إضافة مخزن', NULL, 378, 378, 0, NULL, '2', NULL, 1, 5, '2025-02-13 07:47:16'),
(296, 'STO', 'req_out', 'إذن صرف مخزن', NULL, 212, 212, 0, NULL, '3', NULL, 1, 5, '2025-02-13 07:47:16'),
(297, 'STO', 'req_tran', 'إذن تحويل مخزن', NULL, 30, 30, 0, NULL, '4', 1, 1, 5, '2025-02-13 07:47:16'),
(298, 'PUR', 'pur_inv', 'المشتريات', NULL, 92, 92, 0, 'A,M,N', '1', NULL, 1, 5, '2025-02-13 07:47:16'),
(299, 'SAL', 'sal_refund', 'مردود المبيعات', NULL, 103, 103, 0, 'A,M,N', '3', NULL, 1, 5, '2025-02-13 07:47:16'),
(300, 'SAL', 'pos_salesman', 'عهد الموظفين', NULL, 21, 21, 1, NULL, '5', 1, 1, 5, '2025-02-13 07:47:16'),
(301, 'SAL', 'client', 'العملاء', 'client-add', 18, 18, 1, 'A,M', '2', NULL, 1, 5, '2025-02-13 07:47:16'),
(302, 'SAL', 'sal_vat', 'ضريبة القيمة المضافة المحصلة', NULL, 354, 354, 0, NULL, '4', NULL, 1, 5, '2025-02-13 07:47:16'),
(303, 'PUR', 'pur_ship_cost', 'شحن المشتريات', NULL, 382, 382, 0, NULL, '4', 1, 1, 5, '2025-02-13 07:47:16'),
(304, 'SAL', 'sales_cost', 'حساب تكلفة المبيعات', NULL, 381, 381, 0, NULL, '6', 1, 1, 5, '2025-02-13 07:47:16'),
(305, 'SAL', 'sal_inv', 'المبيعات', NULL, 104, 104, 0, 'A,M,N', '1', NULL, 1, 5, '2025-02-13 07:47:16'),
(306, 'FIN', 'treasury', 'الخزائن', NULL, 22, 22, 1, 'A,M', '1', NULL, 1, 5, '2025-02-13 07:47:16'),
(307, 'FIN', 'banks', 'البنوك', NULL, 23, 23, 1, 'A,M', '2', NULL, 1, 5, '2025-02-13 07:47:16'),
(308, 'SAL', 'sal_ship_cost', 'مصاريف توصيل مبيعات', NULL, 381, -1, 0, 'A,N', '6', NULL, 1, 5, '2025-02-13 07:47:16'),
(309, 'SAL', 'sal_discount', 'خصم مسموح بة', NULL, NULL, NULL, 0, 'A,N', '6', 1, 1, 5, '2025-02-13 07:47:16'),
(310, 'SAL', 'profit_loss_account', 'حساب عجز & زيادة الصندوق', NULL, 391, 391, 0, NULL, '7', NULL, 1, 5, '2025-02-13 07:47:16'),
(311, 'SAL', 'pos_sales_close', 'عهد مبيعات POS', NULL, 379, 379, 0, NULL, '7', NULL, 1, 5, '2025-02-13 07:47:16'),
(312, 'STO', 'store', 'المستودعات', NULL, 20, 20, 1, 'A,M', '1', NULL, 1, 6, '2025-03-18 20:31:48'),
(313, 'PUR', 'supplier', 'الموردين', NULL, 46, 46, 1, 'A,M,N', '2', NULL, 1, 6, '2025-03-18 20:31:48'),
(314, 'STO', 'req_in', 'إذن إضافة مخزن', NULL, 378, 378, 0, NULL, '2', NULL, 1, 6, '2025-03-18 20:31:48'),
(315, 'STO', 'req_out', 'إذن صرف مخزن', NULL, 212, 212, 0, NULL, '3', NULL, 1, 6, '2025-03-18 20:31:48'),
(316, 'STO', 'req_tran', 'إذن تحويل مخزن', NULL, 30, 30, 0, NULL, '4', 1, 1, 6, '2025-03-18 20:31:48'),
(317, 'PUR', 'pur_inv', 'المشتريات', NULL, 92, 92, 0, 'A,M,N', '1', NULL, 1, 6, '2025-03-18 20:31:48'),
(318, 'SAL', 'sal_refund', 'مردود المبيعات', NULL, 103, 103, 0, 'A,M,N', '3', NULL, 1, 6, '2025-03-18 20:31:48'),
(319, 'SAL', 'pos_salesman', 'عهد الموظفين', NULL, 21, 21, 1, NULL, '5', 1, 1, 6, '2025-03-18 20:31:48'),
(320, 'SAL', 'client', 'العملاء', 'client-add', 18, 18, 1, 'A,M', '2', NULL, 1, 6, '2025-03-18 20:31:48'),
(321, 'SAL', 'sal_vat', 'ضريبة القيمة المضافة المحصلة', NULL, 354, 354, 0, NULL, '4', NULL, 1, 6, '2025-03-18 20:31:48'),
(322, 'PUR', 'pur_ship_cost', 'شحن المشتريات', NULL, 382, 382, 0, NULL, '4', 1, 1, 6, '2025-03-18 20:31:48'),
(323, 'SAL', 'sales_cost', 'حساب تكلفة المبيعات', NULL, 381, 381, 0, NULL, '6', 1, 1, 6, '2025-03-18 20:31:48'),
(324, 'SAL', 'sal_inv', 'المبيعات', NULL, 104, 104, 0, 'A,M,N', '1', NULL, 1, 6, '2025-03-18 20:31:48'),
(325, 'FIN', 'treasury', 'الخزائن', NULL, 22, 22, 1, 'A,M', '1', NULL, 1, 6, '2025-03-18 20:31:48'),
(326, 'FIN', 'banks', 'البنوك', NULL, 23, 23, 1, 'A,M', '2', NULL, 1, 6, '2025-03-18 20:31:48'),
(327, 'SAL', 'sal_ship_cost', 'مصاريف توصيل مبيعات', NULL, 381, -1, 0, 'A,N', '6', NULL, 1, 6, '2025-03-18 20:31:48'),
(328, 'SAL', 'sal_discount', 'خصم مسموح بة', NULL, NULL, NULL, 0, 'A,N', '6', 1, 1, 6, '2025-03-18 20:31:48'),
(329, 'SAL', 'profit_loss_account', 'حساب عجز & زيادة الصندوق', NULL, 391, 391, 0, NULL, '7', NULL, 1, 6, '2025-03-18 20:31:48'),
(330, 'SAL', 'pos_sales_close', 'عهد مبيعات POS', NULL, 379, 379, 0, NULL, '7', NULL, 1, 6, '2025-03-18 20:31:48');

-- --------------------------------------------------------

--
-- Table structure for table `tblsection`
--

CREATE TABLE `tblsection` (
  `Id` int(11) NOT NULL,
  `BranchID` int(11) NOT NULL,
  `Name` varchar(100) DEFAULT NULL,
  `ParentID` int(11) DEFAULT NULL,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDate` date NOT NULL,
  `lastUpdateDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblsection`
--

INSERT INTO `tblsection` (`Id`, `BranchID`, `Name`, `ParentID`, `CreatedBy`, `CreatedDate`, `lastUpdateDate`) VALUES
(7, 1, 'ادارة الموارد البشرية', NULL, 1, '2025-10-09', '2025-10-08 23:02:10'),
(8, 1, 'قسم الموارد البشرية', 7, 1, '2025-10-09', '2025-10-08 23:02:42');

-- --------------------------------------------------------

--
-- Table structure for table `tblsessions`
--

CREATE TABLE `tblsessions` (
  `session_id` varchar(255) NOT NULL,
  `UserID` int(11) UNSIGNED NOT NULL,
  `BranchID` int(11) NOT NULL,
  `GroupID` int(11) NOT NULL,
  `UserAgent` varchar(250) DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblsessions`
--

INSERT INTO `tblsessions` (`session_id`, `UserID`, `BranchID`, `GroupID`, `UserAgent`, `login_time`, `end_date`) VALUES
('t69jsjejs4iqlbo74ml7uj748b', 1, 1, 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-09 22:48:29', '2026-12-31');

-- --------------------------------------------------------

--
-- Table structure for table `tblsite`
--

CREATE TABLE `tblsite` (
  `AccountID` int(11) NOT NULL,
  `sys` varchar(15) DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  `SiteUrl` varchar(50) NOT NULL,
  `SiteTitle` varchar(100) NOT NULL,
  `SiteLogo` varchar(250) DEFAULT NULL,
  `SiteVersion` varchar(50) DEFAULT NULL,
  `zatca_integration_time` timestamp NULL DEFAULT NULL,
  `SiteCountryID` varchar(5) NOT NULL DEFAULT 'SA',
  `SiteAddressID` int(11) DEFAULT NULL,
  `SiteCurrencyID` varchar(3) NOT NULL DEFAULT 'SAR',
  `SiteTimeZone` varchar(50) NOT NULL DEFAULT 'UTC',
  `SiteDateFormat` varchar(15) NOT NULL DEFAULT 'Y-m-d',
  `SiteProductsType` int(1) NOT NULL DEFAULT 1,
  `sheared_products` tinyint(1) DEFAULT 1,
  `shear_branches_client` tinyint(1) DEFAULT NULL,
  `clients_users_linked` tinyint(1) DEFAULT NULL,
  `SiteStartDate` date DEFAULT NULL,
  `SiteEndDate` date DEFAULT NULL,
  `SiteNote` varchar(250) DEFAULT NULL,
  `SiteLastUpdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblsite`
--

INSERT INTO `tblsite` (`AccountID`, `sys`, `version`, `SiteUrl`, `SiteTitle`, `SiteLogo`, `SiteVersion`, `zatca_integration_time`, `SiteCountryID`, `SiteAddressID`, `SiteCurrencyID`, `SiteTimeZone`, `SiteDateFormat`, `SiteProductsType`, `sheared_products`, `shear_branches_client`, `clients_users_linked`, `SiteStartDate`, `SiteEndDate`, `SiteNote`, `SiteLastUpdate`) VALUES
(1, 'vision', 1, 'aoad', 'شركة صدى الملاعب', NULL, NULL, NULL, 'SA', 31, 'SAR', 'Asia/Riyadh', 'Y/m/d', 1, 1, NULL, NULL, NULL, '2026-12-31', NULL, '2026-01-05 14:52:46');

-- --------------------------------------------------------

--
-- Table structure for table `tblusergroups`
--

CREATE TABLE `tblusergroups` (
  `GroupID` int(11) NOT NULL,
  `GroupNumber` smallint(6) NOT NULL,
  `FullAccess` tinyint(1) DEFAULT 0,
  `GroupName` varchar(50) NOT NULL,
  `GroupDesc` varchar(100) DEFAULT NULL,
  `IsSystem` tinyint(1) DEFAULT NULL,
  `IsAdmin2222` tinyint(1) DEFAULT NULL,
  `IsDisabled` tinyint(1) DEFAULT NULL,
  `CreatedDate` date DEFAULT NULL,
  `CreatedUser` int(11) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `LastUpdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblusergroups`
--

INSERT INTO `tblusergroups` (`GroupID`, `GroupNumber`, `FullAccess`, `GroupName`, `GroupDesc`, `IsSystem`, `IsAdmin2222`, `IsDisabled`, `CreatedDate`, `CreatedUser`, `BranchID`, `LastUpdate`) VALUES
(1, -1, NULL, 'owner', 'المالك', 1, NULL, NULL, '2022-07-05', NULL, NULL, '2022-11-24 03:26:54'),
(2, 0, 1, 'admin', 'تحكم كامل', NULL, NULL, NULL, '2022-07-05', NULL, 1, '2022-11-24 04:44:58'),
(3, 1, NULL, 'بائعين', NULL, NULL, NULL, NULL, '2024-01-17', 1, 1, '2025-03-06 03:09:00'),
(4, 2, NULL, 'مدير موارد بشرية', NULL, NULL, NULL, NULL, '2025-07-17', 1, 1, '2025-10-05 23:34:06'),
(5, 3, NULL, 'الموارد البشريه', NULL, NULL, NULL, NULL, '2025-07-17', 1, 1, '2025-07-17 19:57:09');

-- --------------------------------------------------------

--
-- Table structure for table `tblusers`
--

CREATE TABLE `tblusers` (
  `UserID` int(11) NOT NULL,
  `clients_accounts` bigint(20) DEFAULT NULL COMMENT 'link user clients folder',
  `user_seals_account` bigint(20) DEFAULT NULL,
  `IsSystem` tinyint(1) DEFAULT NULL,
  `BranchID` int(11) NOT NULL,
  `AllowedBranches` varchar(250) DEFAULT NULL,
  `UserGroupID` int(11) DEFAULT NULL,
  `pos_session` int(11) DEFAULT NULL,
  `UserEmail` varchar(255) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `FirstName` varchar(20) NOT NULL,
  `SecondName` varchar(20) DEFAULT NULL,
  `LastName` varchar(20) DEFAULT NULL,
  `Photo` varchar(250) DEFAULT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Note` varchar(250) DEFAULT NULL,
  `IsDisabled` tinyint(1) DEFAULT NULL,
  `resigned_or_dismissed` int(11) DEFAULT NULL,
  `CreatedDate` date DEFAULT NULL,
  `CreatedUser` int(11) DEFAULT NULL,
  `UpdateBy` int(11) DEFAULT NULL,
  `LastUpdate` timestamp NULL DEFAULT NULL,
  `LastLogin` timestamp NULL DEFAULT NULL,
  `lastversion` int(11) DEFAULT NULL COMMENT 'اخر تحديثات المستخدم',
  `isemp` int(11) DEFAULT NULL COMMENT 'هل هذا الموظف',
  `manager` int(11) DEFAULT NULL COMMENT 'من المدير حق الموظف',
  `FingerID` text DEFAULT NULL COMMENT 'بصمة الموظف',
  `related_to` int(11) DEFAULT NULL COMMENT 'من هو المستخدم المرتبط على موظف',
  `user_insurance` varchar(250) DEFAULT NULL COMMENT 'شركات التامين',
  `user_bank_name` varchar(100) DEFAULT NULL COMMENT 'اسم البنك الخاص بالموظف',
  `user_account_bank` varchar(100) DEFAULT NULL COMMENT 'حساب الموظف البنكي',
  `ohter_phone` varchar(50) DEFAULT NULL COMMENT 'هاتف اخر',
  `HealthCondition` text DEFAULT NULL COMMENT 'الحاله الصحية',
  `Sex` int(11) DEFAULT NULL COMMENT 'الجنس',
  `marital_status` int(11) DEFAULT NULL COMMENT 'الحالة الاجتماعية 1 متزوج 2 عازب 3 مطلق 4 ارملة	',
  `user_address` text DEFAULT NULL COMMENT 'عنوان الموظف',
  `Id_h` varchar(100) DEFAULT NULL COMMENT 'رقم الهوية',
  `start_date_h` date DEFAULT NULL COMMENT 'تاريخ اصدار الهوية',
  `end_date_h` date DEFAULT NULL COMMENT 'تاريخ انتهاء الهوية',
  `path_h` text DEFAULT NULL COMMENT 'ملف الهوية',
  `Id_license` varchar(100) DEFAULT NULL COMMENT 'الرخصة',
  `start_date_license` date DEFAULT NULL COMMENT 'تاريخ اصدار الرخصة',
  `end_date_license` date DEFAULT NULL COMMENT 'تاريخ انتهاء الرخصة',
  `path_license` text DEFAULT NULL COMMENT 'ملف الرخصة',
  `Id_passport` varchar(100) DEFAULT NULL COMMENT 'الجواز',
  `start_date_passport` date DEFAULT NULL COMMENT 'تاريخ الاصدار',
  `end_date_passport` date DEFAULT NULL COMMENT 'تاريخ الانتهاء',
  `path_passport` text DEFAULT NULL COMMENT 'ملف الجواز',
  `Id_health` varchar(100) DEFAULT NULL COMMENT 'رقم الشهادة الصحية',
  `start_date_health` date DEFAULT NULL COMMENT 'تاريخ الاصدار',
  `end_date_health` date DEFAULT NULL COMMENT 'تاريخ الانتهاء',
  `path_health` text DEFAULT NULL COMMENT 'ملف الشهاده الصحية'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tblusers`
--

INSERT INTO `tblusers` (`UserID`, `clients_accounts`, `user_seals_account`, `IsSystem`, `BranchID`, `AllowedBranches`, `UserGroupID`, `pos_session`, `UserEmail`, `Password`, `FirstName`, `SecondName`, `LastName`, `Photo`, `Phone`, `Note`, `IsDisabled`, `resigned_or_dismissed`, `CreatedDate`, `CreatedUser`, `UpdateBy`, `LastUpdate`, `LastLogin`, `lastversion`, `isemp`, `manager`, `FingerID`, `related_to`, `user_insurance`, `user_bank_name`, `user_account_bank`, `ohter_phone`, `HealthCondition`, `Sex`, `marital_status`, `user_address`, `Id_h`, `start_date_h`, `end_date_h`, `path_h`, `Id_license`, `start_date_license`, `end_date_license`, `path_license`, `Id_passport`, `start_date_passport`, `end_date_passport`, `path_passport`, `Id_health`, `start_date_health`, `end_date_health`, `path_health`) VALUES
(1, NULL, 387, 1, 1, NULL, 1, 394, 'demo@admin.com', '$2y$10$9GUJIkqcBQsL5sCDBGqTqe95a/9dgWNIPkxJ1ISmmwWAvEdVVr3e.', 'مدير', NULL, 'النظام', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-10-13 22:56:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, NULL, NULL, NULL, 1, '1', 3, NULL, 's1@gmail.com', '$2y$10$Qy97cHqSxUYS4gOb8z8BXODmVaGAOctvHF.EbcKn24fg.vR0uASxa', 'مبيعات', NULL, '1', NULL, NULL, NULL, NULL, NULL, '2024-01-17', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, NULL, 398, NULL, 2, '2', 3, 391, 'ekrami2@gmail.com', '$2y$10$QlPWfy2EmemyjlzDlggQKObprE91WeiechOyW5ZnyaTzhYpG3lhv.', 'ابو', NULL, 'عبدالوهاب', NULL, NULL, NULL, NULL, NULL, '2025-02-14', 1, NULL, '2025-06-04 04:04:17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, NULL, 399, NULL, 4, '4', 3, 388, 'khalel@gmail.com', '$2y$10$qX/DKeoSTP2hBpdBA5nxdur55aY.1el8ERv.BfXqXpmcBnypbQ0nq', 'محمد', NULL, 'خليل', NULL, NULL, NULL, NULL, NULL, '2025-02-21', 1, NULL, '2025-06-04 04:00:27', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, NULL, NULL, NULL, 1, '1,2,3,4,5,6', 2, NULL, 'munwar@gmail.com', '$2y$10$p9o9SHUo5hSuEafKQ/vc0eAJtzHVhRdZhPuGnaDWxPhULtHn3RiIS', 'Munwar', NULL, 'Alfadhli', NULL, NULL, NULL, NULL, NULL, '2025-02-21', 1, 1, '2025-05-12 01:28:15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, NULL, 405, NULL, 5, '5', 3, 390, 'hakem@gmail.com', '$2y$10$Vnjj5SpIuzAd4KeJVYJOG.G5FQVXtAvaJP8f8dowyJvXO4HVAjxKC', 'حكيم', NULL, 'الامير', NULL, NULL, NULL, NULL, NULL, '2025-02-25', 1, NULL, '2025-06-04 04:04:06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, NULL, 467, NULL, 6, '6', 3, 389, 'mumer@gmail.com', '$2y$10$kmpdfsHaJn1bpc7ZJTkW7uPmWa64tgUwyw8X.Ee37N/55lO.65dbG', 'معمر', NULL, 'القذافي', NULL, NULL, NULL, NULL, NULL, '2025-03-19', 1, NULL, '2025-06-04 04:01:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, NULL, NULL, NULL, 3, '3', 3, NULL, 'melano@gmail.com', '$2y$10$yI2laOXeg66OgNb7T19b1eT709HO2fYpKfuEohS949eLjNzhJPYkC', 'ركن', NULL, 'ميلانو', NULL, NULL, NULL, NULL, NULL, '2025-05-06', 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, NULL, NULL, NULL, 1, '1', 4, NULL, 'hr@gmail.com', '$2y$10$2d6hHYbb99AepXa0uhtNcuTpUcMOh81U/43a5tyb/3.kvkS/qJ3O6', 'HR-', NULL, 'Manager', NULL, NULL, NULL, NULL, NULL, '2025-08-30', 1, 9, '2025-10-08 23:30:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, NULL, NULL, NULL, 1, '1', NULL, NULL, 'emp1@gmail.com', '$2y$10$1Cg7pqMgJd2aX/F2I6K5t.2W/skXsE7UDggzvJtP1ukVz4lCTVONi', 'موظف مدير موارد', NULL, 'بشرية', NULL, NULL, NULL, NULL, NULL, '2025-10-09', 1, 1, '2026-02-02 23:07:29', NULL, 11, 1, 18, NULL, NULL, '2', 'بنك الراجحي', '10008954132', NULL, NULL, 1, 1, NULL, '1010101010', '2024-01-11', '2034-01-11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, NULL, NULL, NULL, 1, '1', NULL, NULL, 'emp2@gmail.com', '$2y$10$JQ8sRCJId/2APuf3qvuLL.iWoIFH.JFxlg9qWe1WtYOK9Q1Vk5xUi', 'موظف', NULL, 'تدريب', NULL, NULL, NULL, NULL, NULL, '2025-10-09', 1, 19, '2025-10-11 16:02:29', NULL, 10, 1, 18, NULL, NULL, '2', NULL, NULL, NULL, NULL, 2, 2, NULL, '12345678911', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, NULL, NULL, NULL, 1, '1', NULL, NULL, 'asd@fff.com', NULL, 'ييي', NULL, 'سسس', NULL, NULL, NULL, NULL, NULL, '2026-02-03', 1, NULL, NULL, NULL, 12, 1, NULL, NULL, NULL, '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbshift`
--

CREATE TABLE `tbshift` (
  `ShiftID` int(11) NOT NULL,
  `BranchID` int(11) NOT NULL,
  `ShiftName` varchar(100) NOT NULL,
  `ShiftStartTime` time NOT NULL,
  `ShiftEndTime` time NOT NULL,
  `ShiftState` int(11) NOT NULL DEFAULT 0,
  `TotalworkHour` time NOT NULL,
  `NumFootprint` int(11) NOT NULL,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDate` date NOT NULL,
  `LastUpdatetim` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tbshift`
--

INSERT INTO `tbshift` (`ShiftID`, `BranchID`, `ShiftName`, `ShiftStartTime`, `ShiftEndTime`, `ShiftState`, `TotalworkHour`, `NumFootprint`, `CreatedBy`, `CreatedDate`, `LastUpdatetim`) VALUES
(1, 1, 'فترة كاملة', '01:00:00', '24:00:00', 0, '24:00:00', 2, 1, '2025-08-30', '2025-10-05 23:24:58'),
(2, 1, 'نصف فترة', '01:00:00', '24:00:00', 0, '24:00:00', 1, 1, '2025-10-09', '2025-10-08 23:00:35'),
(3, 1, 'فترة دوام كاملة', '01:00:00', '24:00:00', 0, '24:00:00', 2, 1, '2025-10-09', '2025-10-08 23:21:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `all_months`
--
ALTER TABLE `all_months`
  ADD PRIMARY KEY (`a_month_id`),
  ADD UNIQUE KEY `all_months_uidx1` (`a_month`);

--
-- Indexes for table `apps`
--
ALTER TABLE `apps`
  ADD PRIMARY KEY (`AppID`),
  ADD KEY `app` (`AppName`),
  ADD KEY `isrequred` (`IsRrequred`),
  ADD KEY `dissabled` (`Disabled`),
  ADD KEY `Sort` (`Sort`);

--
-- Indexes for table `attendancet`
--
ALTER TABLE `attendancet`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`branch_id`),
  ADD UNIQUE KEY `branch_name_2` (`branch_name`),
  ADD UNIQUE KEY `isdefault_2` (`isdefault`),
  ADD KEY `isdefault` (`isdefault`),
  ADD KEY `branch_currency_id` (`branch_currency_id`),
  ADD KEY `isstopped` (`isstopped`),
  ADD KEY `branch_address` (`branch_address`),
  ADD KEY `branch_admin` (`branch_admin`),
  ADD KEY `branch_name` (`branch_name`);

--
-- Indexes for table `emp_order`
--
ALTER TABLE `emp_order`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `emp_salary`
--
ALTER TABLE `emp_salary`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `holidays_day`
--
ALTER TABLE `holidays_day`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `leaveclassification`
--
ALTER TABLE `leaveclassification`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`menu_id`),
  ADD KEY `AppID` (`AppID`),
  ADD KEY `status` (`status`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `sort` (`sort`),
  ADD KEY `dropdown` (`dropdown`);

--
-- Indexes for table `order_finger_add`
--
ALTER TABLE `order_finger_add`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `salary_registration`
--
ALTER TABLE `salary_registration`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `setting_account_salary`
--
ALTER TABLE `setting_account_salary`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `shifts_schedule`
--
ALTER TABLE `shifts_schedule`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `shift_setting`
--
ALTER TABLE `shift_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbfingerprint`
--
ALTER TABLE `tbfingerprint`
  ADD PRIMARY KEY (`FingerprintID`);

--
-- Indexes for table `tbinsurance`
--
ALTER TABLE `tbinsurance`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `tblaccountguide`
--
ALTER TABLE `tblaccountguide`
  ADD PRIMARY KEY (`AccountID`),
  ADD UNIQUE KEY `AccountNumber_2` (`AccountNumber`,`BranchID`),
  ADD UNIQUE KEY `AccountName_2` (`AccountName`,`BranchID`),
  ADD KEY `ParentNumber` (`ParentNumber`),
  ADD KEY `AccountNumber` (`AccountNumber`),
  ADD KEY `AccountName` (`AccountName`),
  ADD KEY `AccountType` (`AccountType`),
  ADD KEY `IsSystem` (`IsSystem`);

--
-- Indexes for table `tbladdress`
--
ALTER TABLE `tbladdress`
  ADD PRIMARY KEY (`AddressID`);

--
-- Indexes for table `tblbenefit`
--
ALTER TABLE `tblbenefit`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `tblbranchesapps`
--
ALTER TABLE `tblbranchesapps`
  ADD UNIQUE KEY `BranchID_2` (`BranchID`,`AppID`),
  ADD KEY `BranchID` (`BranchID`),
  ADD KEY `AppID` (`AppID`);

--
-- Indexes for table `tblcurrenciesguide`
--
ALTER TABLE `tblcurrenciesguide`
  ADD PRIMARY KEY (`CurrencyID`),
  ADD UNIQUE KEY `IsLocalCurrency_2` (`IsLocalCurrency`,`BranchID`),
  ADD KEY `IsLocalCurrency` (`IsLocalCurrency`),
  ADD KEY `BranchID` (`BranchID`);

--
-- Indexes for table `tbldeductions`
--
ALTER TABLE `tbldeductions`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `tbldocumentnums`
--
ALTER TABLE `tbldocumentnums`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tbldocuments`
--
ALTER TABLE `tbldocuments`
  ADD PRIMARY KEY (`DocumentID`),
  ADD UNIQUE KEY `DocumentName_2` (`DocumentName`),
  ADD KEY `DocumentName` (`DocumentName`);

--
-- Indexes for table `tbldocumentsdetails`
--
ALTER TABLE `tbldocumentsdetails`
  ADD PRIMARY KEY (`DocumentDetailsID`),
  ADD KEY `ParentID` (`ParentID`);

--
-- Indexes for table `tblempadvances`
--
ALTER TABLE `tblempadvances`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `tblemployee`
--
ALTER TABLE `tblemployee`
  ADD PRIMARY KEY (`EmpID`);

--
-- Indexes for table `tblentries`
--
ALTER TABLE `tblentries`
  ADD PRIMARY KEY (`EntryID`),
  ADD KEY `RecordNunmber` (`RecordNunmber`);

--
-- Indexes for table `tblentriesdetails`
--
ALTER TABLE `tblentriesdetails`
  ADD PRIMARY KEY (`EntryDetailsID`),
  ADD KEY `ParentID` (`ParentID`),
  ADD KEY `RowTime` (`RowTime`),
  ADD KEY `BranchID` (`BranchID`),
  ADD KEY `AccountID` (`AccountID`);

--
-- Indexes for table `tblgroup`
--
ALTER TABLE `tblgroup`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `tblincentives`
--
ALTER TABLE `tblincentives`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `tbljobgrade`
--
ALTER TABLE `tbljobgrade`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `tbljobtitle`
--
ALTER TABLE `tbljobtitle`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `tblleaverequest`
--
ALTER TABLE `tblleaverequest`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `tblpermission`
--
ALTER TABLE `tblpermission`
  ADD PRIMARY KEY (`PermID`) USING BTREE,
  ADD UNIQUE KEY `AppID` (`AppID`,`PermName`),
  ADD UNIQUE KEY `un_code` (`code`);

--
-- Indexes for table `tblremewal`
--
ALTER TABLE `tblremewal`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `tblresignation`
--
ALTER TABLE `tblresignation`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `tblroutguide`
--
ALTER TABLE `tblroutguide`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `AppCode_2` (`AppCode`,`ControllerCode`,`BranchID`) USING BTREE,
  ADD KEY `ControllerCode` (`ControllerCode`),
  ADD KEY `AppCode` (`AppCode`);

--
-- Indexes for table `tblsection`
--
ALTER TABLE `tblsection`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `tblsessions`
--
ALTER TABLE `tblsessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `login_time` (`login_time`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `UserAgent` (`UserAgent`);

--
-- Indexes for table `tblsite`
--
ALTER TABLE `tblsite`
  ADD PRIMARY KEY (`AccountID`),
  ADD UNIQUE KEY `SiteUrl` (`SiteUrl`);

--
-- Indexes for table `tblusergroups`
--
ALTER TABLE `tblusergroups`
  ADD PRIMARY KEY (`GroupID`),
  ADD UNIQUE KEY `groupNumber` (`GroupNumber`),
  ADD UNIQUE KEY `GroupName_2` (`GroupName`),
  ADD KEY `BranchID` (`BranchID`),
  ADD KEY `GroupName` (`GroupName`),
  ADD KEY `IsSystem` (`IsSystem`),
  ADD KEY `IsDisabled` (`IsDisabled`),
  ADD KEY `FullAccess` (`FullAccess`);

--
-- Indexes for table `tblusers`
--
ALTER TABLE `tblusers`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `UserEmail` (`UserEmail`),
  ADD KEY `IsSystem` (`IsSystem`);

--
-- Indexes for table `tbshift`
--
ALTER TABLE `tbshift`
  ADD PRIMARY KEY (`ShiftID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendancet`
--
ALTER TABLE `attendancet`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'الرقم', AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `branch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `emp_order`
--
ALTER TABLE `emp_order`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `emp_salary`
--
ALTER TABLE `emp_salary`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `holidays_day`
--
ALTER TABLE `holidays_day`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=472;

--
-- AUTO_INCREMENT for table `leaveclassification`
--
ALTER TABLE `leaveclassification`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `order_finger_add`
--
ALTER TABLE `order_finger_add`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` tinyint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT for table `salary_registration`
--
ALTER TABLE `salary_registration`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `setting_account_salary`
--
ALTER TABLE `setting_account_salary`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `shifts_schedule`
--
ALTER TABLE `shifts_schedule`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `shift_setting`
--
ALTER TABLE `shift_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbfingerprint`
--
ALTER TABLE `tbfingerprint`
  MODIFY `FingerprintID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbinsurance`
--
ALTER TABLE `tbinsurance`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tblaccountguide`
--
ALTER TABLE `tblaccountguide`
  MODIFY `AccountID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=505;

--
-- AUTO_INCREMENT for table `tbladdress`
--
ALTER TABLE `tbladdress`
  MODIFY `AddressID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `tblbenefit`
--
ALTER TABLE `tblbenefit`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbldeductions`
--
ALTER TABLE `tbldeductions`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbldocumentnums`
--
ALTER TABLE `tbldocumentnums`
  MODIFY `ID` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `tbldocuments`
--
ALTER TABLE `tbldocuments`
  MODIFY `DocumentID` smallint(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `tbldocumentsdetails`
--
ALTER TABLE `tbldocumentsdetails`
  MODIFY `DocumentDetailsID` smallint(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tblempadvances`
--
ALTER TABLE `tblempadvances`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tblemployee`
--
ALTER TABLE `tblemployee`
  MODIFY `EmpID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblentries`
--
ALTER TABLE `tblentries`
  MODIFY `EntryID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2295;

--
-- AUTO_INCREMENT for table `tblentriesdetails`
--
ALTER TABLE `tblentriesdetails`
  MODIFY `EntryDetailsID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5877;

--
-- AUTO_INCREMENT for table `tblgroup`
--
ALTER TABLE `tblgroup`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tblincentives`
--
ALTER TABLE `tblincentives`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbljobgrade`
--
ALTER TABLE `tbljobgrade`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbljobtitle`
--
ALTER TABLE `tbljobtitle`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tblleaverequest`
--
ALTER TABLE `tblleaverequest`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tblremewal`
--
ALTER TABLE `tblremewal`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tblresignation`
--
ALTER TABLE `tblresignation`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tblroutguide`
--
ALTER TABLE `tblroutguide`
  MODIFY `ID` smallint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=331;

--
-- AUTO_INCREMENT for table `tblsection`
--
ALTER TABLE `tblsection`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tblsite`
--
ALTER TABLE `tblsite`
  MODIFY `AccountID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT for table `tblusergroups`
--
ALTER TABLE `tblusergroups`
  MODIFY `GroupID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tblusers`
--
ALTER TABLE `tblusers`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `tbshift`
--
ALTER TABLE `tbshift`
  MODIFY `ShiftID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
