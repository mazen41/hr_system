<?php
/**
 * Seed script — run once to populate database with proper UTF-8 Arabic data
 * Usage: php seed.php OR visit http://localhost/HR/inc/seed.php
 */
require_once __DIR__ . '/config.php';

echo "<pre>\n";

// Reset auto-increment
$tables = ['tblsite','branches','apps','tblbranchesapps','tblusergroups','tblpermissions','tblusers','tblremewal',
           'tblsection','tbljobgrade','tbljobtitle','tblgroup','tblemploymenttype','tbshift','leaveclassification',
           'reports','setting_account_salary'];
foreach ($tables as $t) {
    try { $connect_pdo->exec("ALTER TABLE `$t` AUTO_INCREMENT = 1"); } catch(Exception $e) {}
}

// Site
$connect_pdo->exec("INSERT INTO tblsite (SiteTitle, SiteCurrencyID, SiteTimeZone, SiteDateFormat) VALUES ('Vision HR', 'SAR', 'Asia/Riyadh', 'Y-m-d')");
echo "✓ tblsite\n";

// Branch
$connect_pdo->exec("INSERT INTO branches (branch_name, isdefault, TypeBracnhLocation, Onepoint) VALUES ('الفرع الرئيسي', 1, 1, '24.7136,46.6753,500')");
echo "✓ branches\n";

// Apps
$connect_pdo->exec("INSERT INTO apps (AppID, AppName, Sort) VALUES ('HR', 'الموارد البشرية', 1)");
$connect_pdo->exec("INSERT INTO apps (AppID, AppName, Sort) VALUES ('SAL', 'المبيعات', 2)");
$connect_pdo->exec("INSERT INTO apps (AppID, AppName, Sort) VALUES ('ACC', 'المحاسبة', 3)");
echo "✓ apps\n";

// Branch-App
$connect_pdo->exec("INSERT INTO tblbranchesapps (BranchID, AppID) VALUES (1, 'HR')");
echo "✓ tblbranchesapps\n";

// User Groups
$connect_pdo->exec("INSERT INTO tblusergroups (GroupName, FullAccess, IsSystem, BranchID) VALUES ('مدير النظام', 1, 1, 1)");
$connect_pdo->exec("INSERT INTO tblusergroups (GroupName, FullAccess, IsSystem, BranchID) VALUES ('موظف', NULL, NULL, 1)");
echo "✓ tblusergroups\n";

// Permissions
$perms = ['إضافة موظف','عرض موظف','تعديل موظف','الفترات','اجهزة البصمة','الاقسام',
          'المسميات الوظيفية','شركات التامين','المجموعات','الدرجات الوظيفية','انماط العمل',
          'الاجازات الرسمية','الاجازات العامة','الحضور والانصراف','عرض الحضور والانصراف',
          'تحضير موظف','رفع ملف الاكسل','إضافة تعويض','إضافة خصم','إضافة حافز',
          'اضافة فترة','اضافة بصمة','اضافة قسم','اضافة عقد','اضافة ترقية','إصدار الرواتب'];
$stmt = $connect_pdo->prepare("INSERT INTO tblpermissions (GroupID, AppID, PermName) VALUES (1, 'HR', :p)");
foreach ($perms as $p) { $stmt->execute([':p' => $p]); }
echo "✓ tblpermissions (" . count($perms) . ")\n";

// Users
$connect_pdo->exec("INSERT INTO tblusers (UserEmail, UserPassword, FirstName, LastName, UserGroupID, IsSystem, AllowedBranches, BranchID, isemp, home_page) VALUES ('admin@vision.hr', 'admin123', 'مدير', 'النظام', 1, 1, '1', 1, 1, 'Hrdashboard')");
$connect_pdo->exec("INSERT INTO tblusers (UserEmail, UserPassword, FirstName, LastName, UserGroupID, AllowedBranches, BranchID, isemp, home_page, Sex, marital_status) VALUES ('emp@vision.hr', 'emp123', 'أحمد', 'محمد', 2, '1', 1, 1, 'Hrdashboard', 'ذكر', 'متزوج')");
echo "✓ tblusers\n";

// Employment types
$types = ['دوام كامل','دوام جزئي','عقد مؤقت','تدريب'];
$stmt = $connect_pdo->prepare("INSERT INTO tblemploymenttype (Name) VALUES (:n)");
foreach ($types as $t) { $stmt->execute([':n' => $t]); }
echo "✓ tblemploymenttype\n";

// Sections
$connect_pdo->exec("INSERT INTO tblsection (Name, BranchID) VALUES ('الإدارة العامة', 1)");
$connect_pdo->exec("INSERT INTO tblsection (Name, ParentID, BranchID) VALUES ('تقنية المعلومات', 1, 1)");
$connect_pdo->exec("INSERT INTO tblsection (Name, ParentID, BranchID) VALUES ('الموارد البشرية', 1, 1)");
echo "✓ tblsection\n";

// Job Grades
$grades = ['درجة أولى','درجة ثانية','درجة ثالثة'];
$stmt = $connect_pdo->prepare("INSERT INTO tbljobgrade (Name, BranchID) VALUES (:n, 1)");
foreach ($grades as $g) { $stmt->execute([':n' => $g]); }
echo "✓ tbljobgrade\n";

// Job Titles
$connect_pdo->exec("INSERT INTO tbljobtitle (Name, BranchID, SectionID) VALUES ('مدير عام', 1, 1)");
$connect_pdo->exec("INSERT INTO tbljobtitle (Name, BranchID, SectionID) VALUES ('مطور برمجيات', 1, 2)");
$connect_pdo->exec("INSERT INTO tbljobtitle (Name, BranchID, SectionID) VALUES ('أخصائي موارد بشرية', 1, 3)");
echo "✓ tbljobtitle\n";

// Groups
$connect_pdo->exec("INSERT INTO tblgroup (Name, BranchID) VALUES ('المجموعة أ', 1)");
$connect_pdo->exec("INSERT INTO tblgroup (Name, BranchID) VALUES ('المجموعة ب', 1)");
echo "✓ tblgroup\n";

// Shift
$connect_pdo->exec("INSERT INTO tbshift (BranchID, ShiftName, ShiftStartTime, ShiftEndTime, NumFootprint) VALUES (1, 'الفترة الصباحية', '08:00:00', '16:00:00', 2)");
echo "✓ tbshift\n";

// Contracts
$connect_pdo->exec("INSERT INTO tblremewal (UserID, SectionID, BranchID, GroupID, GradeID, shiftID, TypeID, jobtitleID, Salary, Currency, new_s_date, new_e_date, state) VALUES (1, 1, 1, 1, 1, '1', 1, 1, 15000.00, 'SAR', '2025-01-01', '2026-12-31', 1)");
$connect_pdo->exec("INSERT INTO tblremewal (UserID, SectionID, BranchID, GroupID, GradeID, shiftID, TypeID, jobtitleID, Salary, Currency, new_s_date, new_e_date, state) VALUES (2, 2, 1, 1, 2, '1', 1, 2, 8000.00, 'SAR', '2025-01-01', '2026-12-31', 1)");
echo "✓ tblremewal\n";

// Update lastversion
$connect_pdo->exec("UPDATE tblusers SET lastversion = 1 WHERE UserID = 1");
$connect_pdo->exec("UPDATE tblusers SET lastversion = 2 WHERE UserID = 2");
echo "✓ lastversion updated\n";

// Leave classifications
$connect_pdo->exec("INSERT INTO leaveclassification (BranchID, Name, Description, type, max_days) VALUES (1, 'إجازة سنوية', 'إجازة سنوية مدفوعة', 1, 30)");
$connect_pdo->exec("INSERT INTO leaveclassification (BranchID, Name, Description, type, max_days, RequiresAttachment) VALUES (1, 'إجازة مرضية', 'إجازة مرضية - تتطلب مرفق', 1, 15, 1)");
$connect_pdo->exec("INSERT INTO leaveclassification (BranchID, Name, Description, type, max_days) VALUES (1, 'إجازة طارئة', 'إجازة طارئة', 1, 5)");
echo "✓ leaveclassification\n";

// Reports
$reports = [
    [1, 'HR', 'الموارد البشرية', 0, 'users', '#', 1],
    [2, 'HR', 'تقرير الموظفين', 1, 'user', 'report-all-emplyer', 1],
    [3, 'HR', 'تقرير الأقسام', 1, 'sitemap', 'report-section', 2],
    [4, 'HR', 'تقرير المسميات', 1, 'briefcase', 'report-jobtitle', 3],
    [5, 'HR', 'تقرير الإجازات', 1, 'calendar', 'report-leaveRequest', 4],
    [6, 'HR', 'تقرير الحضور', 1, 'clock', 'report-fingerprint', 5],
    [7, 'HR', 'تقرير الرواتب', 1, 'money-bill', 'report-export-salarys', 6],
];
$stmt = $connect_pdo->prepare("INSERT INTO reports (id, app, name, parent, icon, url, sort) VALUES (:id, :app, :name, :parent, :icon, :url, :sort)");
foreach ($reports as $r) {
    $stmt->execute([':id'=>$r[0], ':app'=>$r[1], ':name'=>$r[2], ':parent'=>$r[3], ':icon'=>$r[4], ':url'=>$r[5], ':sort'=>$r[6]]);
}
echo "✓ reports\n";

// Salary account settings
$accs = [
    [1, 'مرتبات وأجور'],
    [2, 'مكافآت الموظفين'],
    [3, 'تعويضات الموظفين'],
    [4, 'سلف الموظفين'],
    [5, 'خصومات الموظفين'],
    [6, 'مرتبات مستحقة'],
];
$stmt = $connect_pdo->prepare("INSERT INTO setting_account_salary (account_id, account_name) VALUES (:id, :name)");
foreach ($accs as $a) { $stmt->execute([':id'=>$a[0], ':name'=>$a[1]]); }
echo "✓ setting_account_salary\n";

echo "\n=== SEED COMPLETE ===\n";
echo "</pre>";
