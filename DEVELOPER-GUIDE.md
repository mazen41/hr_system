# Vision HR — Complete Developer Guide
## دليل المطور الشامل لنظام الموارد البشرية "فيجن"

> **Last Updated:** 2026-02-08  
> **Version:** 2.0 (API Layer Added)  
> **Author:** Senior PHP Engineer

---

## Table of Contents

1. [Environment Setup](#1-environment-setup)
2. [Project Architecture](#2-project-architecture)
3. [Database Schema](#3-database-schema)
4. [Existing Web Application (AdminLTE)](#4-existing-web-application-adminlte)
5. [New REST API Layer](#5-new-rest-api-layer)
6. [Shared Services Layer](#6-shared-services-layer)
7. [Authentication & Authorization](#7-authentication--authorization)
8. [Attendance System (GPS + QR + Anti-Spoof)](#8-attendance-system-gps--qr--anti-spoof)
9. [Notification System](#9-notification-system)
10. [Biometric Device Integration](#10-biometric-device-integration)
11. [File Upload System](#11-file-upload-system)
12. [Testing Guide](#12-testing-guide)
13. [Remaining Work / Roadmap](#13-remaining-work--roadmap)
14. [Coding Standards & Rules](#14-coding-standards--rules)
15. [Troubleshooting](#15-troubleshooting)

---

## 1. Environment Setup

### 1.1 Requirements

| Component | Version | Notes |
|-----------|---------|-------|
| **PHP** | 8.0+ | Extensions: pdo_mysql, mbstring, json, openssl, curl, fileinfo |
| **MySQL** | 5.7+ / MariaDB 10.3+ | utf8mb4 charset |
| **Apache** | 2.4+ | mod_rewrite enabled |
| **XAMPP** | 8.x | Recommended for local dev |

### 1.2 Installation Steps

```bash
# 1. Clone/copy project to XAMPP htdocs
# Project lives at: c:\xampp\htdocs\HR\

# 2. Create database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS vision_hr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Run schema + seed data
mysql -u root vision_hr < inc/schema.sql

# 4. Run API migrations (new tables)
mysql -u root vision_hr < shared/migrations/001_foundation.sql
mysql -u root vision_hr < shared/migrations/002_missing_features.sql

# 5. Verify Apache mod_rewrite is enabled
# In httpd.conf: LoadModule rewrite_module modules/mod_rewrite.so
# AllowOverride All for the htdocs directory

# 6. Start Apache + MySQL via XAMPP Control Panel
```

### 1.3 Configuration Files

| File | Purpose |
|------|---------|
| `inc/config.php` | DB connection, site URL, timezone, uploads dir |
| `api/v1/config.php` | JWT secret, rate limits, CORS origins, GPS settings, FCM key |

### 1.4 Default Credentials

| Role | Email | Password | Home Page |
|------|-------|----------|-----------|
| **Admin** | `admin@vision.hr` | `admin123` | `Hrdashboard` |
| **Employee** | `emp@vision.hr` | `emp123` | `Hrdashboard` |

### 1.5 URLs

| What | URL |
|------|-----|
| **Web App** | `http://localhost/HR/login-sys` |
| **Admin Dashboard** | `http://localhost/HR/Hrdashboard` |
| **API Base** | `http://localhost/HR/api/v1/` |
| **API Health** | `http://localhost/HR/api/v1/health` |
| **API Docs** | See `api/API-DOCS.md` |

---

## 2. Project Architecture

### 2.1 High-Level Overview

```
Vision HR has TWO parallel interfaces sharing the SAME database:

┌──────────────────────────────────────────────────────────┐
│                      MySQL (vision_hr)                    │
│  30+ existing tables + 9 new API tables                  │
└──────────────┬───────────────────────┬───────────────────┘
               │                       │
    ┌──────────┴──────────┐  ┌────────┴────────────┐
    │  WEB APP (existing) │  │  REST API (new)      │
    │  AdminLTE + jQuery  │  │  Pure PHP + JWT      │
    │  Session-based auth │  │  Token-based auth    │
    │  Server-rendered    │  │  JSON responses      │
    │  ~180 PHP pages     │  │  12 controllers      │
    │  For: desktop users │  │  For: mobile/PWA/SPA │
    └─────────────────────┘  └──────────────────────┘
```

### 2.2 Directory Structure

```
c:\xampp\htdocs\HR\
│
├── index.php                    # Main router (loads pages via ?page=xxx)
├── login-sys.php                # Login page
├── .htaccess                    # URL rewriting (API + apps routing)
├── VISION-HR-ANALYSIS.md        # Original analysis document
├── DEVELOPER-GUIDE.md           # THIS FILE
│
├── inc/                         # Core framework (shared with Vision ERP)
│   ├── config.php               # DB connection + site settings
│   ├── User.php                 # User class (login, session, permissions)
│   ├── functions.php            # Helper functions (sanitize, upload, format)
│   ├── header.php               # HTML header + sidebar + navbar (732 lines)
│   ├── footer.php               # HTML footer + JS libraries
│   ├── schema.sql               # Full DB schema + seed data (628 lines)
│   ├── schema_missing.sql       # Additional schema patches
│   ├── seed.php                 # PHP-based seeder
│   ├── check_db.php             # DB connectivity check
│   └── logout.php               # Session destroy
│
├── api/                         # ★ NEW: REST API Layer
│   ├── .htaccess                # API URL rewriting
│   ├── API-DOCS.md              # Full API documentation
│   └── v1/
│       ├── index.php            # API entry point + ~65 route definitions
│       ├── bootstrap.php        # Core loader (no HTML) + helper functions
│       ├── config.php           # API-specific config (JWT, CORS, GPS, FCM)
│       ├── router.php           # URI pattern router with :param support
│       ├── controllers/         # 12 API controllers
│       │   ├── AuthController.php
│       │   ├── EmployeeController.php
│       │   ├── AttendanceController.php
│       │   ├── LeaveController.php
│       │   ├── AdvanceController.php
│       │   ├── ApprovalController.php
│       │   ├── NotificationController.php
│       │   ├── DocumentController.php
│       │   ├── DashboardController.php
│       │   ├── UploadController.php
│       │   ├── PushController.php
│       │   └── BiometricController.php
│       ├── helpers/
│       │   ├── JWTHelper.php    # Pure PHP JWT (HS256, no composer)
│       │   ├── Response.php     # Standardized JSON responses
│       │   └── Validator.php    # Input validation rules
│       └── middleware/
│           ├── auth.php         # JWT token verification
│           ├── rbac.php         # Role-based access control
│           ├── audit.php        # Audit logging middleware
│           └── ratelimit.php    # File-based rate limiting
│
├── shared/                      # ★ NEW: Shared services (used by API + can be used by web)
│   ├── Security.php             # CSRF tokens, CSP headers, bcrypt hashing
│   ├── RateLimiter.php          # File-based rate limiter
│   ├── AuditLog.php             # Audit trail logging
│   ├── QRCodeService.php        # TOTP-based rotating QR codes (HMAC-SHA256)
│   ├── NotificationService.php  # In-app + FCM push notifications
│   ├── AntiSpoof.php            # GPS spoofing prevention + risk scoring
│   ├── BiometricSync.php        # Fingerprint device sync (ZKTeco, Hikvision)
│   └── migrations/
│       ├── 001_foundation.sql   # Core API tables
│       └── 002_missing_features.sql  # Extended tables
│
├── uploads/                     # ★ NEW: File upload storage
│   ├── .htaccess                # Security (no PHP execution)
│   ├── leaves/
│   ├── advances/
│   ├── orders/
│   ├── resignations/
│   └── photos/
│
├── dist/                        # Static assets (CSS, JS, images)
├── plugins/                     # jQuery plugins
│
├── hr-app/                      # AJAX handlers for web app
├── sal-app/                     # Salary AJAX handlers
├── sheard/                      # Legacy shared AJAX handlers
├── users-app/                   # User management AJAX handlers
│
└── [~180 PHP page files]        # Web application pages (see Section 4)
```

### 2.3 Request Flow

**Web App Request:**
```
Browser → /HR/Hrdashboard
  → index.php (router)
    → session check
    → include Hrdashboard.php
      → include inc/header.php (HTML + sidebar)
      → page content (PHP + HTML + jQuery)
      → include inc/footer.php (JS libraries)
```

**API Request:**
```
Client → /HR/api/v1/auth/login
  → .htaccess rewrites to api/v1/index.php?route=auth/login
    → bootstrap.php (loads core, helpers, middleware, services)
    → Router matches route → AuthController::login()
      → Validator checks input
      → Business logic (DB queries)
      → Response::success() → JSON output
```

---

## 3. Database Schema

### 3.1 Existing Tables (30+)

#### Core System
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `tblsite` | Site/company settings | AccountID, SiteTitle, SiteLogo, SiteCurrencyID |
| `branches` | Company branches | branch_id, branch_name, TypeBracnhLocation, Onepoint, MorePoint |
| `apps` | Application modules | AppID (HR/SAL/ACC), AppName |
| `tblbranchesapps` | Branch ↔ App mapping | BranchID, AppID |

#### Users & Permissions
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `tblusers` | Users/Employees (main table) | UserID, UserEmail, UserPassword, FirstName, LastName, UserGroupID, IsSystem, AllowedBranches, BranchID, FingerID, lastversion, manager, isemp + document IDs/dates |
| `tblusergroups` | Permission groups/roles | GroupID, GroupName, FullAccess, IsSystem |
| `tblpermissions` | Individual permissions | PermID, GroupID, AppID, PermName |

#### Organizational Structure
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `tblsection` | Departments (tree) | Id, Name, ParentID, BranchID |
| `tblgroup` | Job groups | Id, Name, BranchID |
| `tbljobgrade` | Job grades | Id, Name, BranchID |
| `tbljobtitle` | Job titles | Id, Name, BranchID, SectionID |
| `tblemploymenttype` | Employment types | Id, Name |

#### Contracts & HR
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `tblremewal` | **PIVOT TABLE** — Contracts/renewals/promotions | Id, UserID, SectionID, BranchID, GroupID, GradeID, shiftID, TypeID, jobtitleID, Salary, Currency, new_s_date, new_e_date |
| `tblresignation` | Resignations | Id, UserID, DueDate, Reason, Status |
| `tbldismissal` | Dismissals | Id, UserID, DueDate, Reason, Status |

> **CRITICAL:** `tblusers.lastversion` → FK to `tblremewal.Id`. This is how the system knows the employee's CURRENT contract, salary, section, job title, shift, etc. Every contract change creates a new `tblremewal` row and updates `lastversion`.

#### Attendance
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `tbshift` | Work shifts | ShiftID, ShiftStartTime, ShiftEndTime, BranchID |
| `shift_setting` | Shift rules | shift_id, late_tolerance, early_leave_tolerance, absent_after |
| `shifts_schedule` | Weekly schedule | shift_id, day_of_week, start_time, end_time, is_off |
| `tbfingerprint` | Fingerprint devices | FingerprintID, BranchID, ip, port, FingerprintType |
| `tblattendance` | Attendance records | AttendanceID, EmpID, Date, Type (1=in/2=out), Time, source, lat, lng + anti-spoof columns |

#### Leaves & Requests
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `leaveclassification` | Leave types | Id, Name, max_days, RequiresAttachment, for_what, chose |
| `tblleave` | Leave requests | Id, UserID, LeaveTypeID, StartDate, EndDate, Days, Status (0/1/2) |
| `tblorders` | Employee orders | Id, UserID, OrderType, Description, Status |
| `tblfinger_forget` | Finger forget requests | Id, UserID, date, Reason, Status |

#### Financial
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `tblbenefit` | Benefits/allowances | Id, UserID, Amount, beneft_type, monthly |
| `tbldeductions` | Deductions | Id, UserID, Amount |
| `tblempadvances` | Salary advances/loans | Id, UserID, Amount, DueDate, Status |
| `tblincentive` | Incentives | Id, UserID, Amount |
| `salary_registration` | Payroll runs | Id, month, year, BranchID, totals |
| `salary_details` | Per-employee salary details | Id, registration_id, UserID, basic_salary, benefits, deductions, net_salary |
| `setting_account_salary` | Accounting mapping | account_id, account_name |
| `tblaccountguide` | Chart of accounts | AccountID, AccountNumber, AccountName |

#### Employee Records
| Table | Purpose |
|-------|---------|
| `tblcertificates` | Employee certificates |
| `tblexperience` | Work experience |
| `tbinsurance` | Insurance companies |
| `tblholidays` | Official holidays |

### 3.2 New API Tables (Migration 001)

| Table | Purpose |
|-------|---------|
| `audit_log` | Action audit trail (user_id, action, table_name, record_id, old_data, new_data, ip, timestamp) |
| `jwt_refresh_tokens` | JWT refresh token storage (user_id, token_hash, expires_at, revoked) |
| `notifications` | In-app notifications (user_id, title, body, type, is_read, reference_table, reference_id) |
| `attendance_qr_codes` | TOTP rotating QR codes (branch_id, code, hmac_secret, generated_by, expires_at, used_count) |
| `push_subscriptions` | FCM push subscriptions (user_id, fcm_token, device_type, device_name, is_active) |

### 3.3 New API Tables (Migration 002)

| Table | Purpose |
|-------|---------|
| `device_fingerprints` | Device tracking for anti-spoofing (user_id, fingerprint, device_info, ip_address, use_count, is_trusted) |
| `branch_ip_ranges` | Branch allowed IP ranges (branch_id, cidr, ip_range_start, ip_range_end) |
| `password_resets` | Password reset tokens (user_id, token_hash, expires_at, used) |
| `biometric_sync_log` | Biometric device sync history (device_id, records_fetched, records_imported, status, error_message) |

### 3.4 Modified Tables

- `tblattendance` — Added columns: `device_fingerprint`, `gps_accuracy`, `mock_location`, `qr_code_id`, `risk_score`

### 3.5 Key Relationships

```
tblusers.lastversion ──→ tblremewal.Id (current contract)
tblusers.manager ──→ tblusers.UserID (manager hierarchy)
tblusers.UserGroupID ──→ tblusergroups.GroupID (permissions)
tblremewal.BranchID ──→ branches.branch_id
tblremewal.shiftID ──→ tbshift.ShiftID
tblremewal.jobtitleID ──→ tbljobtitle.Id
tblremewal.GradeID ──→ tbljobgrade.Id
tblremewal.SectionID ──→ tblsection.Id
tblleave.LeaveTypeID ──→ leaveclassification.Id
tblattendance.EmpID ──→ tblusers.UserID
```

---

## 4. Existing Web Application (AdminLTE)

### 4.1 Tech Stack
- **Template:** AdminLTE 3 (RTL Arabic)
- **CSS:** Bootstrap 4 + custom CSS variables in header.php
- **JS:** jQuery, DataTables, SweetAlert2, Toastr, Select2, Chart.js, DateRangePicker
- **Font:** Tajawal (Arabic Google Font)
- **Icons:** Font Awesome 5 + Bootstrap Icons
- **PDF:** jsPDF + html2canvas (client-side generation)

### 4.2 Page Naming Convention

Every module follows a CRUD pattern:
```
[module]-add.php        → Create/Edit form
[module]-list.php       → DataTable listing
[module]-view.php       → Detail view
[module]-remove.php     → Delete handler (AJAX)
[module]-conform.php    → Approve/confirm action
[module]-upload.php     → File upload handler
```

Three access levels for some modules:
```
leaveRequest-add.php        → Employee's own request
leaveRequest-add-add.php    → Manager creates for subordinate
leaveRequest-list-admin.php → Admin sees all
```

### 4.3 All Existing Pages by Module

#### Dashboard
| Page | Description |
|------|-------------|
| `Hrdashboard.php` | Admin dashboard (stats, charts, quick links) |
| `dashboard-emp.php` | Employee dashboard |
| `dashboard.php` | General dashboard |

#### Employees (الموظفون)
| Page | Description |
|------|-------------|
| `employer-add.php` | Add/edit employee (51KB — largest form) |
| `employer-list.php` | Employee list with DataTable |
| `employer-view.php` | Employee detail view |
| `emp-info.php` | Employee info panel (38KB) |
| `emp-info-advances.php` | Employee advances info |
| `emp-info-leaves.php` | Employee leaves info |
| `emp-info-resignation.php` | Employee resignation info |
| `emp-info-shift.php` | Employee shift info |
| `emp-chart.php` | Organization chart |
| `emp-related-manager.php` | Manager's subordinates |
| `emp-setting.php` | Employee settings |
| `allUserInfo.php` | All users info |
| `allUserinfo_Search.php` | User search |
| `empvalidate.php` | Employee validation |
| `stop_emp.php` | Disable employee |
| `change-manager-emp.php` | Change employee's manager |
| `subordinate-emp.php` | Subordinate employees list |

#### Attendance (الحضور والانصراف)
| Page | Description |
|------|-------------|
| `attendancet-emp.php` | **Attendance page** (shown in screenshot — GPS check-in/out, clock, geofence check) |
| `reveal-attendance.php` | Attendance report/reveal |
| `reveal-attendance-view.php` | Attendance detail view |
| `import-emp-atten.php` | Import attendance from Excel |
| `fingerprint-add.php` | Add fingerprint device |
| `fingerprint-list.php` | List fingerprint devices |
| `fingerprint-view.php` | View fingerprint device |
| `fingerprint-remove.php` | Remove fingerprint device |
| `finger-forget-add.php` | Finger forget request (employee) |
| `finger-forget-list.php` | Finger forget list (employee) |
| `finger-forget-list-admin.php` | Finger forget list (admin) |
| `finger-forget-view.php` | View finger forget request |
| `finger-forget-view-admin.php` | View finger forget (admin) |
| `finger-forget-conform-admin.php` | Approve finger forget |
| `finger-forget-remove.php` | Delete finger forget request |
| `finger-forget-remove-admin.php` | Admin delete finger forget |
| `finger-forget-upload.php` | Upload attachment |

#### Leaves (الإجازات)
| Page | Description |
|------|-------------|
| `leaveClassficate-add.php` | Add leave type |
| `leaveClassficate-list.php` | Leave types list |
| `leaveClassficate-view.php` | View leave type |
| `leaveClassficate-remove.php` | Remove leave type |
| `leaveRequest-add.php` | Employee leave request |
| `leaveRequest-add-add.php` | Manager creates leave for employee |
| `leaveRequest-list.php` | Employee's leave list |
| `leaveRequest-list-add.php` | Manager's leave list |
| `leaveRequest-list-admin.php` | Admin leave list |
| `leaveRequest-view.php` | View leave request |
| `leaveRequest-view-add.php` | Manager view leave |
| `leaveRequest-view-admin.php` | Admin view leave |
| `leaveRequest-conform-admin.php` | Approve leave |
| `leaveRequest-deni-admin.php` | Reject leave |
| `leaveRequest-remove.php` | Delete leave request |
| `leaveRequest-remove-add.php` | Manager delete leave |
| `leaveRequest-remove-admin.php` | Admin delete leave |
| `leaveRequest-upload.php` | Upload leave attachment |
| `leaveRequest-upload-add.php` | Manager upload attachment |
| `get-leaves-info.php` | AJAX: get leave balance info |

#### Advances (السلف)
| Page | Description |
|------|-------------|
| `EmpAdvances-add.php` | Employee advance request |
| `EmpAdvances-add-add.php` | Manager creates advance |
| `EmpAdvances-list.php` | Employee advance list |
| `EmpAdvances-list-add.php` | Manager advance list |
| `EmpAdvances-list-admin.php` | Admin advance list |
| `EmpAdvances-view.php` | View advance |
| `EmpAdvances-view-add.php` | Manager view advance |
| `EmpAdvances-view-admin.php` | Admin view advance |
| `EmpAdvances-conform-admin.php` | Approve advance |
| `EmpAdvances-remove.php` | Delete advance |
| `EmpAdvances-remove-add.php` | Manager delete advance |
| `EmpAdvances-remove-admin.php` | Admin delete advance |
| `EmpAdvances-upload.php` | Upload advance attachment |
| `EmpAdvances-upload-add.php` | Manager upload attachment |

#### Contracts & Promotions (العقود والترقيات)
| Page | Description |
|------|-------------|
| `contractRenewal-add.php` | Add/renew contract |
| `contractRenewal-list.php` | Contract list |
| `contractRenewal-emp-list.php` | Employee contract history |
| `contractRenewal-view.php` | View contract |
| `contractRenewal-conform.php` | Confirm contract |
| `contractRenewal-remove.php` | Remove contract |
| `promotion-add.php` | Add promotion |
| `promotion-list.php` | Promotion list |
| `promotion-view.php` | View promotion |
| `promotion-conform.php` | Confirm promotion |
| `promotion-remove.php` | Remove promotion |

#### Resignation & Dismissal (إنهاء الخدمة)
| Page | Description |
|------|-------------|
| `resignation-add.php` / `resignation-add-add.php` | Resignation request |
| `resignation-list.php` / `resignation-list-add.php` / `resignation-list-admin.php` | Resignation lists |
| `resignation-view.php` / `resignation-view-add.php` / `resignation-view-admin.php` | View resignation |
| `resignation-conform-admin.php` | Approve resignation |
| `resignation-remove.php` / `resignation-remove-add.php` / `resignation-remove-admin.php` | Delete |
| `resignation-upload.php` / `resignation-upload-add.php` | Upload attachment |
| `dismissal-add.php` | Add dismissal |
| `dismissal-list.php` | Dismissal list |
| `dismissal-upload.php` | Upload dismissal attachment |

#### Employee Orders (أوامر الموظفين)
| Page | Description |
|------|-------------|
| `order-emp-add.php` | Create order |
| `order-emp-list.php` / `order-emp-list-admin.php` | Order lists |
| `order-emp-view.php` / `order-emp-view-admin.php` | View order |
| `order-emp-conform-admin.php` | Approve order |
| `order-emp-remove.php` / `order-emp-remove-admin.php` | Delete order |
| `order-emp-upload.php` | Upload attachment |

#### Financial (المالية)
| Page | Description |
|------|-------------|
| `Benefits-add.php` | Add benefit/allowance |
| `Benefits-list.php` | Benefits list |
| `Benefits-view.php` | View benefit |
| `Benefits-conform.php` | Confirm benefit |
| `Benefits-remove.php` | Remove benefit |
| `deductions-add.php` | Add deduction |
| `deductions-list.php` | Deductions list |
| `deductions-view.php` | View deduction |
| `deductions-conform.php` | Confirm deduction |
| `deductions-remove.php` | Remove deduction |
| `incentive-add.php` | Add incentive |
| `incentive-list.php` | Incentives list |
| `incentive-view.php` | View incentive |
| `incentive-conform.php` | Confirm incentive |
| `incentive-remove.php` | Remove incentive |
| `incentive-extion.php` | Incentive extension |
| `incentive-info.php` / `incentive-info_show.php` | Incentive info |
| `benefit-info_show.php` | Benefit info |
| `dection-info_show.php` | Deduction info |

#### Salary (الرواتب)
| Page | Description |
|------|-------------|
| `Issuing-salaries.php` | **Payroll processing** (39KB — complex) |
| `Issuing-salaries-list.php` | Payroll runs list |
| `Issuing-salaries-view.php` | View payroll run |
| `salary-disbursement.php` | Salary disbursement |
| `save-setting-account-salary.php` | Salary account settings |

#### Settings (الإعدادات)
| Page | Description |
|------|-------------|
| `branches-add.php` | Add/edit branch (includes geofence config) |
| `branches-list.php` | Branches list |
| `branches-settings.php` | Branch settings |
| `section-add.php` | Add department |
| `section-list.php` | Departments list |
| `section-view.php` | View department |
| `section-tree.php` | Department tree view |
| `section-remove.php` | Remove department |
| `groub-add.php` | Add job group |
| `groub-list.php` | Job groups list |
| `groub-view.php` | View job group |
| `groub-remove.php` | Remove job group |
| `jobgrade-add.php` | Add job grade |
| `jobgrade-list.php` | Job grades list |
| `jobgrade-view.php` | View job grade |
| `jobgrade-remove.php` | Remove job grade |
| `jobtitle-add.php` | Add job title |
| `jobtitle-list.php` | Job titles list |
| `jobtitle-view.php` | View job title |
| `jobtitle-remove.php` | Remove job title |
| `empolyment-add.php` | Add employment type |
| `empolyment-list.php` | Employment types list |
| `empolyment-view.php` | View employment type |
| `empolyment-remove.php` | Remove employment type |
| `insurance-add.php` | Add insurance company |
| `insurance-list.php` | Insurance list |
| `insurance-view.php` | View insurance |
| `insurance-remove.php` | Remove insurance |
| `holidays-add.php` / `hodidays-add.php` | Add holiday |
| `holidays-list.php` | Holidays list |
| `holidays-view.php` | View holiday |
| `holidays-remove.php` | Remove holiday |
| `shift-add.php` | Add shift (31KB — complex) |
| `shift-list.php` | Shifts list |
| `shift-view.php` | View shift |
| `shift-remove.php` | Remove shift |
| `users-group-add.php` | Add/edit user group + permissions |
| `hr-setting.php` | HR module settings |
| `accountant-coa.php` | Chart of accounts |

#### Reports (التقارير)
| Page | Description |
|------|-------------|
| `report-center.php` | Reports center |
| `report-all-emplyer.php` | All employees report |
| `report-one-empyer.php` | Single employee report |
| `report-section.php` / `report-one-section.php` | Department reports |
| `report-jobtitle.php` / `report-one-jobtitle.php` | Job title reports |
| `report-jobgrade.php` / `report-one-jobgrade.php` | Job grade reports |
| `report-group.php` / `report-one-group.php` | Group reports |
| `report-shift.php` / `report-one-shift.php` | Shift reports |
| `report-leaveRequest.php` / `report-one-leaveRequest.php` | Leave reports |
| `report-leaveclassficate.php` / `report-one-leaveclassficate.php` | Leave classification reports |
| `report-one-specifac-leaveRequest.php` | Specific leave report |
| `report-empAdvances.php` / `report-one-empAdvances.php` | Advance reports |
| `report-benefits.php` / `report-one-benefit.php` | Benefit reports |
| `report-deductions.php` / `report-one-deductions.php` | Deduction reports |
| `report-incentive.php` / `report-one-incentive.php` | Incentive reports |
| `report-emplyements.php` / `report-one-emplyement.php` | Employment reports |
| `report-insurance.php` / `report-one-insurance.php` | Insurance reports |
| `report-holidays.php` / `report-one-holiday.php` | Holiday reports |
| `report-resignation.php` / `report-one-resignation.php` | Resignation reports |
| `report-fingerprint.php` | Fingerprint device report |
| `report-export-salarys.php` | Salary export report |

#### Employee Self-Service
| Page | Description |
|------|-------------|
| `user-certifacte.php` | Employee certificates |
| `user-experince.php` | Employee experience |
| `UserCertifcate-add.php` | Add certificate |
| `UserExperince-add.php` | Add experience |

#### Misc
| Page | Description |
|------|-------------|
| `switch-branch.php` | Switch active branch |
| `denied-branch.php` | Branch access denied page |
| `download-file.php` | File download handler |
| `info-of-section-and-job-title.php` | AJAX: section/jobtitle info |

---

## 5. New REST API Layer

### 5.1 Architecture

The API is a **parallel layer** that:
- Shares the same `$connect_pdo` database connection
- Uses the same `inc/config.php`, `inc/User.php`, `inc/functions.php`
- Does NOT output any HTML — only JSON
- Uses JWT authentication instead of sessions
- Has its own middleware stack (auth, rbac, audit, ratelimit)

### 5.2 Controllers (12)

| Controller | Methods | Purpose |
|------------|---------|---------|
| `AuthController` | login, refresh, logout, me, forgotPassword, resetPassword, changePassword | Authentication |
| `EmployeeController` | profile, updateProfile, salarySlips, salarySlipById, documents, contracts, certificates, experience | Employee self-service |
| `AttendanceController` | checkIn, checkOut, today, history, qrScan, qrGenerate, qrActive | Attendance with GPS + QR |
| `LeaveController` | types, balance, createRequest, listRequests, getRequest, cancelRequest | Leave management |
| `AdvanceController` | createRequest, listRequests, getRequest | Salary advances |
| `ApprovalController` | pending, approve, reject | Manager approvals (5 types) |
| `NotificationController` | list, markRead, markAllRead, unreadCount | Notifications |
| `DocumentController` | salarySlip, experienceLetter, salaryDefinition | Document generation |
| `DashboardController` | employee, manager | Dashboard statistics |
| `UploadController` | leaveAttachment, advanceAttachment, orderAttachment, resignationAttachment, profilePhoto | File uploads |
| `PushController` | subscribe, unsubscribe, listSubscriptions, test | FCM push notifications |
| `BiometricController` | devices, syncAll, syncDevice, testDevice, syncLog | Biometric device management |

### 5.3 How to Add a New API Endpoint

1. **Add method to controller** (or create new controller in `api/v1/controllers/`)
2. **Add route in `api/v1/index.php`:**
```php
$router->get('/my-endpoint', function () {
    MyController::myMethod();
});
$router->post('/my-endpoint/:id', function ($params) {
    MyController::myMethod($params);
});
```
3. **If new controller**, add `require_once` at top of `index.php`
4. **Update `api/API-DOCS.md`** with the new endpoint

### 5.4 Helper Functions Available in API Context

Defined in `bootstrap.php`:
```php
getRequestBody()                          // Parse JSON body (cached)
getMethod()                               // GET/POST/PUT/DELETE
authMiddleware()                          // Returns $apiUser array or 401
rbacMiddleware($apiUser, $perms)          // Check permissions or 403
rateLimitMiddleware($key, $max, $window)  // Rate limit or 429
requireOwnerOrAdmin($apiUser, $ownerId)   // Check ownership or 403
requireManager($apiUser)                  // Check is manager or 403
requireBranchAccess($apiUser, $branchId)  // Check branch access or 403
```

Global objects:
```php
$connect_pdo   // PDO database connection
$User          // User class instance
$jwt           // JWTHelper instance
$auditLog      // AuditLog instance
$qrService     // QRCodeService instance
$notifService  // NotificationService instance
$antiSpoof     // AntiSpoof instance
```

---

## 6. Shared Services Layer

### 6.1 QRCodeService (`shared/QRCodeService.php`)

**Purpose:** TOTP-based rotating QR codes for attendance.

**How it works:**
1. Admin calls `generate($branchId, $generatedBy)` → creates HMAC-signed QR payload
2. QR rotates every 30s, expires after 60s
3. Employee scans → `validate($scannedData)` verifies HMAC signature + time window + branch
4. Supports both JSON payload and short-code formats

**Key methods:**
```php
$qrService->generate(int $branchId, int $generatedBy): array
$qrService->validate(string $scannedData): array  // {valid, branch_id, qr_id, error}
$qrService->recordUsage(int $qrId): void
$qrService->getActive(int $branchId): ?array
$qrService->invalidateExpired(): int
$qrService->dualVerify(string $qrData, float $lat, float $lng, float $accuracy, int $userBranchId): array
```

### 6.2 AntiSpoof (`shared/AntiSpoof.php`)

**Purpose:** Prevent GPS spoofing and attendance fraud.

**Checks performed:**
1. **GPS accuracy** — Rejects if > 100m
2. **Mock location** — Rejects if device reports mock
3. **Device fingerprint** — Tracks devices per user, warns if too many
4. **IP range** — Validates against branch allowed IP ranges (CIDR)
5. **Velocity check** — Detects impossible travel between records
6. **Risk scoring** — Combines all checks into 0-100 score, blocks if ≥ 70

**Key method:**
```php
$antiSpoof->check([
    'user_id' => int,
    'lat' => float, 'lng' => float,
    'accuracy' => float,
    'mock_location' => bool,
    'device_fingerprint' => string,
    'branch_id' => int,
    'ip' => string,
]): array  // {allowed, risk_score, warnings[], reason}
```

### 6.3 NotificationService (`shared/NotificationService.php`)

**Purpose:** Centralized in-app + FCM push notifications.

**Event helpers:**
```php
$notifService->notifyLeaveApproved($userId, $leaveId)
$notifService->notifyLeaveRejected($userId, $leaveId, $reason)
$notifService->notifyAdvanceApproved($userId, $advanceId)
$notifService->notifyNewLeaveRequest($managerId, $employeeName, $leaveId)
$notifService->notifyContractExpiring($userId, $daysLeft)
$notifService->notifyAttendanceAnomaly($userId, $type, $details)
// ... and more
```

### 6.4 BiometricSync (`shared/BiometricSync.php`)

**Purpose:** Pull attendance logs from fingerprint devices.

**Supported devices:** ZKTeco, Hikvision

**Key methods:**
```php
$sync = new BiometricSync($connect_pdo);
$sync->syncAll(): array           // Sync all active devices
$sync->syncDevice(int $id): array // Sync specific device
$sync->testConnection(int $id): array
```

### 6.5 Other Shared Services

| Service | Purpose |
|---------|---------|
| `Security.php` | CSRF tokens, CSP headers, bcrypt password hashing |
| `RateLimiter.php` | File-based rate limiting (no Redis needed) |
| `AuditLog.php` | Audit trail with logCreate/logUpdate/logDelete/logLogin/logLogout |

---

## 7. Authentication & Authorization

### 7.1 Web App (Session-based)

```
Login page: login-sys.php
Session vars: $_SESSION['user_id'], $_SESSION['user'], $_SESSION['branch']
User class: inc/User.php → login(), loadFromSession(), isAllowedPerm()
Logout: inc/logout.php (session_destroy)
```

Permission check in pages:
```php
$page_perm = 'إضافة موظف';  // Arabic permission name
$appid = 'HR';
// Checked in header.php via $User->isAllowedPerm()
```

### 7.2 API (JWT-based)

```
Login: POST /api/v1/auth/login → returns access_token + refresh_token
Access token: 15 min TTL, HS256 signed
Refresh token: 7 day TTL, stored hashed in jwt_refresh_tokens table
Header: Authorization: Bearer <access_token>
```

The `authMiddleware()` function:
1. Extracts Bearer token from Authorization header
2. Decodes + verifies JWT signature and expiry
3. Loads full user data (profile + contract via lastversion)
4. Returns `$apiUser` array with: id, email, name, is_admin, branch_id, contract details

RBAC in API:
```php
rbacMiddleware($apiUser, ['الحضور والانصراف']); // checks permission names
```

---

## 8. Attendance System (GPS + QR + Anti-Spoof)

### 8.1 How Attendance Works (Web App — `attendancet-emp.php`)

The screenshot shows this page. It:
1. Gets user's GPS coordinates via browser Geolocation API
2. Checks if user is within branch geofence (Onepoint/MorePoint from `branches` table)
3. Shows "أنت خارج نطاق الشركة" (outside range) if not in geofence
4. If inside, shows check-in/check-out buttons
5. Records to `tblattendance` with source='manual' or 'app'

### 8.2 Branch Geofence Configuration

In `branches` table:
- `TypeBracnhLocation = 1` → Single point + radius: `Onepoint = "lat,lng,radius_meters"` (e.g., `24.7136,46.6753,500`)
- `TypeBracnhLocation = 2` → Polygon: `MorePoint = "lat1-lng1,lat2-lng2,lat3-lng3,..."`

### 8.3 How Attendance Works (API — Enhanced)

```
POST /api/v1/attendance/check-in
  ↓
1. authMiddleware() → verify JWT
2. Validate input (lat, lng, accuracy)
3. AntiSpoof->check() → risk scoring
   - GPS accuracy check
   - Mock location detection
   - Device fingerprint tracking
   - IP range validation
   - Velocity check
   - Risk score calculation
4. validateLocation() → branch geofence check
5. Check not already checked in today
6. INSERT into tblattendance (with risk_score, device_fingerprint, etc.)
7. Check lateness against shift schedule
8. Return response
```

### 8.4 QR Attendance Flow

```
ADMIN:                              EMPLOYEE:
GET /attendance/qr-generate         
  → QRCodeService->generate()       
  → Returns QR payload              
  → Display as QR image             → Scans QR with phone camera
                                    POST /attendance/qr-scan
                                      → QRCodeService->validate()
                                      → HMAC signature check
                                      → Time window check
                                      → Branch match check
                                      → INSERT attendance record
                                      → recordUsage()
```

---

## 9. Notification System

### 9.1 Architecture

```
Event occurs (e.g., leave approved)
  → NotificationService->notifyLeaveApproved($userId, $leaveId)
    → INSERT into notifications table (in-app)
    → sendPush() → FCM HTTP API → device push notification
```

### 9.2 Notification Types
- `info` — General information
- `success` — Approval, confirmation
- `warning` — Expiring documents, contracts
- `danger` — Rejection, anomaly detection

### 9.3 FCM Setup (for push notifications)
1. Create Firebase project at https://console.firebase.google.com
2. Go to Project Settings → Cloud Messaging → Get Server Key
3. Set `FCM_SERVER_KEY` in `api/v1/config.php`
4. Client app registers device token via `POST /push/subscribe`

---

## 10. Biometric Device Integration

### 10.1 Supported Devices
- **ZKTeco** — REST API at `http://{ip}:{port}/iclock/api/transactions/`
- **Hikvision** — ISAPI at `http://{ip}:{port}/ISAPI/AccessControl/AcsEvent`

### 10.2 How Sync Works
1. Admin configures device in `tbfingerprint` (IP, port, type)
2. Admin triggers sync via `POST /biometric/sync` or `POST /biometric/sync/:id`
3. `BiometricSync` fetches logs from device API
4. Maps device finger IDs to `tblusers.FingerID`
5. Imports records into `tblattendance` with `source='device'`
6. Logs sync result to `biometric_sync_log`

### 10.3 Device Configuration
In `tbfingerprint` table:
- `ip` — Device IP address (e.g., `192.168.1.100`)
- `port` — Device port (e.g., `80` or `4370`)
- `FingerprintType` — `zkteco` or `hikvision`
- `BranchID` — Which branch this device belongs to

---

## 11. File Upload System

### 11.1 API Uploads
- Endpoint: `POST /upload/{type}/{id}` (multipart/form-data)
- Field name: `file`
- Max size: 10MB (configurable in `api/v1/config.php`)
- Allowed types: jpg, jpeg, png, pdf, doc, docx, xls, xlsx
- Storage: `uploads/{subfolder}/vhr_{uniqid}_{timestamp}.{ext}`
- Protection: `.htaccess` blocks PHP execution in uploads directory

### 11.2 Web App Uploads
- Uses `uploadFile()` from `inc/functions.php`
- Various `-upload.php` pages handle specific upload types

---

## 12. Testing Guide

### 12.1 API Testing with cURL

```bash
# 1. Login
curl -X POST http://localhost/HR/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@vision.hr","password":"admin123"}'

# Save the access_token from response, then:

# 2. Get profile
curl http://localhost/HR/api/v1/employee/profile \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

# 3. Check-in
curl -X POST http://localhost/HR/api/v1/attendance/check-in \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"lat":24.7136,"lng":46.6753,"accuracy":10,"device_fingerprint":"test1"}'

# 4. Dashboard
curl http://localhost/HR/api/v1/dashboard/employee \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

# 5. Health check (no auth)
curl http://localhost/HR/api/v1/health
```

### 12.2 API Testing with Postman

1. Import the following base URL: `http://localhost/HR/api/v1`
2. Create environment variable `token` 
3. In login request, add Post-response script:
```javascript
var json = pm.response.json();
if (json.data && json.data.access_token) {
    pm.environment.set("token", json.data.access_token);
}
```
4. For all protected requests, set header: `Authorization: Bearer {{token}}`

### 12.3 PHP Syntax Check (All Files)

```powershell
# Run from project root
Get-ChildItem -Path "c:\xampp\htdocs\HR\api" -Filter "*.php" -Recurse | ForEach-Object { php -l $_.FullName }
Get-ChildItem -Path "c:\xampp\htdocs\HR\shared" -Filter "*.php" -Recurse | ForEach-Object { php -l $_.FullName }
```

---

## 13. Remaining Work / Roadmap

### 13.1 Phase 3: ESS PWA Application (NOT YET STARTED)

The REST API is ready. The next step is building a **Progressive Web App** (PWA) or mobile-first SPA that consumes the API.

**Recommended stack:** React/Vue + Vite + TailwindCSS + PWA plugin

**Screens to build:**
| Screen | API Endpoints Used |
|--------|-------------------|
| Login | `POST /auth/login` |
| Dashboard | `GET /dashboard/employee` |
| Attendance (check-in/out) | `POST /attendance/check-in`, `POST /attendance/check-out`, `GET /attendance/today` |
| QR Scanner | `POST /attendance/qr-scan` (use device camera) |
| Leave Request | `POST /leaves/request`, `GET /leaves/balance`, `GET /leaves/requests` |
| Advance Request | `POST /advances/request`, `GET /advances/requests` |
| Profile | `GET /employee/profile`, `PUT /employee/profile` |
| Documents | `GET /employee/documents`, `GET /employee/salary-slips` |
| Notifications | `GET /notifications`, `PUT /notifications/:id/read` |
| Manager Approvals | `GET /approvals/pending`, `POST /approvals/:type/:id/approve` |

### 13.2 Phase 4: Web App Enhancements (OPTIONAL)

These are improvements to the existing AdminLTE web app:

| Task | Priority | Description |
|------|----------|-------------|
| **Integrate AuditLog** | High | Add `$auditLog->logCreate/Update/Delete()` calls to existing CRUD pages |
| **Integrate NotificationService** | High | Call `$notifService->notifyXxx()` in approval/rejection pages |
| **Add CSRF protection** | High | Use `Security::generateCsrfToken()` / `Security::verifyCsrfToken()` in all forms |
| **Hash existing passwords** | High | Run migration to bcrypt-hash all plain-text passwords in `tblusers` |
| **Add CSP headers** | Medium | Call `Security::sendCspHeaders()` in `header.php` |
| **Fix SQL injection risks** | High | Audit all pages for raw `$_GET`/`$_POST` in SQL queries |
| **Integrate QR display** | Medium | Add QR code display widget to `attendancet-emp.php` for managers |
| **Real-time notifications** | Low | Add notification bell in `header.php` sidebar using AJAX polling |

### 13.3 Phase 5: Advanced Features (FUTURE)

| Feature | Description |
|---------|-------------|
| **Overtime calculation** | Auto-calculate overtime from shift_setting + attendance |
| **Auto salary calculation** | Integrate attendance data into payroll (late deductions, absence deductions) |
| **Email notifications** | Send emails for approvals, password resets, document expiry |
| **Multi-language** | i18n support (currently Arabic-only) |
| **Report export** | PDF/Excel export for all reports via API |
| **Cron jobs** | Scheduled tasks: expire QR codes, sync biometric, check document expiry, send reminders |
| **WebSocket** | Real-time notifications instead of polling |

### 13.4 Known Issues / Technical Debt

| Issue | File | Description |
|-------|------|-------------|
| Ternary syntax | `report-one-shift.php:348` | Unparenthesized `a ? b : c ? d : e` — needs parentheses |
| Plain-text passwords | `tblusers` | Some passwords may be plain text (API handles both, but should migrate all to bcrypt) |
| No CSRF on forms | All `-add.php` pages | Forms don't have CSRF tokens |
| Mixed SQL patterns | Various | Some pages use raw string concatenation instead of prepared statements |
| Inconsistent naming | Various | Mix of camelCase, snake_case, and Arabic in column/table names |
| No input validation | Some pages | Some forms don't validate server-side |

---

## 14. Coding Standards & Rules

### 14.1 CRITICAL RULE: Do NOT Modify Existing Files

> **All new code MUST go in `api/` or `shared/` directories.**  
> **Never modify existing files in the HR root or `inc/` directory** unless explicitly fixing a bug in that file.  
> This ensures the existing web app continues to work while new features are added in parallel.

### 14.2 API Controller Pattern

```php
<?php
class MyController
{
    public static function myMethod(): void
    {
        global $connect_pdo, $auditLog;
        $apiUser = authMiddleware();  // Always first for protected endpoints

        // Validate input
        $body = getRequestBody();
        $v = new Validator($body);
        $v->required('field', 'اسم الحقل');
        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        // Business logic with prepared statements
        $stm = $connect_pdo->prepare("SELECT ... WHERE id = :id");
        $stm->execute([':id' => $body['id']]);

        // Audit log
        $auditLog->logCreate($apiUser['id'], 'table_name', $recordId, $data);

        // Response
        Response::success($data, 'رسالة النجاح');
    }
}
```

### 14.3 Database Queries

- **ALWAYS** use PDO prepared statements with named parameters
- **NEVER** concatenate user input into SQL strings
- Use `$connect_pdo` (global PDO instance)

### 14.4 Response Format

```php
Response::success($data, 'message');           // 200
Response::created($data, 'message');           // 201
Response::error('message', 422);               // Any error
Response::validationError(['field' => 'msg']); // 422
Response::unauthorized();                       // 401
Response::forbidden();                          // 403
Response::notFound();                           // 404
Response::paginated($items, $total, $page, $perPage); // 200 with meta
```

---

## 15. Troubleshooting

### 15.1 Common Issues

| Problem | Solution |
|---------|----------|
| **404 on API routes** | Ensure `.htaccess` rewrite rule exists and `mod_rewrite` is enabled |
| **"أنت خارج نطاق الشركة"** | Branch geofence not configured or user is outside range. Check `branches.Onepoint` |
| **JWT token expired** | Access tokens expire in 15 min. Use `POST /auth/refresh` with refresh_token |
| **CORS errors** | Add your frontend origin to `API_CORS_ORIGINS` in `api/v1/config.php` |
| **Rate limited** | Wait for window to expire. API: 60/min, Login: 5/5min |
| **Upload fails** | Check `uploads/` directory permissions (755), PHP `upload_max_filesize` in php.ini |
| **Biometric sync fails** | Verify device IP/port is reachable, check `FingerprintType` matches device |
| **Anti-spoof blocks** | Risk score ≥ 70. Check `audit_log` for `antispoof_blocked` entries |

### 15.2 Useful Debug Commands

```powershell
# Check PHP syntax on all API files
Get-ChildItem -Path "c:\xampp\htdocs\HR\api" -Filter "*.php" -Recurse | ForEach-Object { php -l $_.FullName }

# Test API health
curl http://localhost/HR/api/v1/health

# Check Apache error log
Get-Content "c:\xampp\apache\logs\error.log" -Tail 50

# Check MySQL connection
php -r "new PDO('mysql:host=localhost;dbname=vision_hr', 'root', '');"
```

### 15.3 Log Locations

| Log | Location |
|-----|----------|
| Apache errors | `c:\xampp\apache\logs\error.log` |
| PHP errors | `c:\xampp\php\logs\php_error_log` |
| Rate limiter data | `c:\xampp\htdocs\HR\shared\rate_limits\` (auto-created) |
| Audit trail | `audit_log` table in database |
| Biometric sync | `biometric_sync_log` table in database |

---

## Quick Reference Card

```
┌─────────────────────────────────────────────────────────────┐
│  VISION HR — Quick Reference                                │
├─────────────────────────────────────────────────────────────┤
│  Web App:  http://localhost/HR/login-sys                    │
│  API:      http://localhost/HR/api/v1/                      │
│  DB:       vision_hr @ localhost (root, no password)        │
│  Admin:    admin@vision.hr / admin123                       │
│  Employee: emp@vision.hr / emp123                           │
├─────────────────────────────────────────────────────────────┤
│  New code goes in: api/ and shared/ ONLY                    │
│  Never modify: existing PHP pages or inc/ files             │
│  Always use: PDO prepared statements                        │
│  API auth: Bearer JWT in Authorization header               │
│  API docs: api/API-DOCS.md                                  │
│  DB migrations: shared/migrations/001 + 002                 │
└─────────────────────────────────────────────────────────────┘
```
