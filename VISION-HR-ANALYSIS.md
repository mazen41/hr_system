# Vision HR System - Full Technical Analysis & Development Plan
## التحليل الفني الشامل لنظام الموارد البشرية "فيجن" وخطة التطوير

---

## 1. تحليل النظام الحالي (Current System Analysis)

### 1.1 البنية التقنية (Tech Stack)
| Component | Technology |
|-----------|-----------|
| **Backend** | Raw PHP (No Framework) |
| **Database** | MySQL via PDO |
| **Frontend** | AdminLTE 3 + jQuery + Bootstrap 4 |
| **Charts** | Chart.js 3.0 |
| **UI Plugins** | Select2, DataTables, SweetAlert2, Toastr, DateRangePicker, Bootstrap-Select |
| **PDF** | jsPDF + html2canvas (client-side) |
| **PWA** | Service Worker registered in login page (sw.js + manifest.json) |
| **Architecture** | Monolithic - pages include `inc/header.php` and `inc/footer.php` from parent Vision system |

### 1.2 بنية المشروع (Project Structure)
النظام هو **وحدة HR ضمن منظومة "فيجن" الأكبر** (Vision ERP). الملفات الموجودة هي فقط صفحات HR، بينما:
- `inc/header.php` و `inc/footer.php` → موجودة في المشروع الأب (Vision core)
- `$connect_pdo` → اتصال PDO يتم تهيئته في الـ core
- `$User` object → كلاس المستخدم من الـ core (يحتوي على methods مثل `login()`, `userIsAdmin()`, `isAllowedPerm()`, `allBranches()`)
- `$branch`, `$user`, `$today_date`, `$now_date` → متغيرات عامة من الـ core
- `sanitizingData()` → دالة تنظيف المدخلات من الـ core
- AJAX endpoints تذهب إلى `hr-app/` و `sal-app/` و `sheard/` → مجلدات في المشروع الأب

### 1.3 نمط الملفات (File Pattern)
كل وحدة (module) تتبع نمط CRUD ثابت:
```
[module]-add.php      → صفحة الإضافة/التعديل (form + display)
[module]-list.php     → صفحة القائمة (DataTable + AJAX)
[module]-view.php     → صفحة العرض التفصيلي
[module]-remove.php   → حذف (AJAX handler)
[module]-conform.php  → اعتماد/موافقة
[module]-upload.php   → رفع مرفقات
```
بعض الوحدات لها 3 مستويات:
- بدون لاحقة → للموظف العادي
- `-add` → للمدير المباشر
- `-admin` → للإدارة العليا

---

## 2. قاعدة البيانات - الجداول المكتشفة (Database Schema)

### 2.1 جداول النظام الأساسية (Core Tables)
| Table | Purpose |
|-------|---------|
| `tblsite` | إعدادات الموقع/المنشأة (SiteTitle, SiteLogo, SiteEndDate, SiteCurrencyID, SiteTimeZone) |
| `tblusers` | المستخدمون/الموظفون (UserID, UserEmail, FirstName, SecondName, LastName, Photo, Phone, FingerID, BranchID, UserGroupID, IsDisabled, IsSystem, AllowedBranches, lastversion, user_insurance, user_bank_name, user_account_bank, Sex, marital_status, user_address, Id_h, Id_license, Id_passport, Id_health + تواريخ ومرفقات لكل وثيقة) |
| `tblusergroups` | مجموعات الصلاحيات (GroupID, GroupName, GroupNumber, FullAccess, IsSystem, IsDisabled, BranchID) |
| `branches` | الفروع (branch_id, branch_name, branch_ref, branch_style, branch_address, isdefault, isstopped, TypeBracnhLocation, Onepoint, MorePoint) |
| `apps` | التطبيقات/الوحدات (AppID, AppName, Sort, Disabled, IsRequired) |
| `tblbranchesapps` | ربط الفروع بالتطبيقات المفعلة |

### 2.2 جداول الهيكل التنظيمي (Organizational Structure)
| Table | Purpose |
|-------|---------|
| `tblsection` | الأقسام (Id, Name, ParentID, BranchID) - هيكل شجري |
| `tblgroup` | المجموعات الوظيفية (Id, Name, BranchID) |
| `tbljobgrade` | الدرجات الوظيفية (Id, Name, BranchID) |
| `tbljobtitle` | المسميات الوظيفية (Id, Name, BranchID) |
| `tblemploymenttype` | أنواع التوظيف (Id, Name) |

### 2.3 جداول العقود والترقيات (Contracts & Promotions)
| Table | Purpose |
|-------|---------|
| `tblremewal` | العقود/التجديدات/الترقيات (Id, UserID, SectionID, BranchID, GroupID, GradeID, shiftID, TypeID, fingerID, jobtitleID, Salary, Currency, new_s_date, new_e_date, state, come_name, day, Reason) - **الجدول المحوري** الذي يربط الموظف بكل بياناته الوظيفية |

### 2.4 جداول الحضور والانصراف (Attendance)
| Table | Purpose |
|-------|---------|
| `tbshift` | الفترات/الورديات (ShiftID, BranchID, ShiftName, ShiftStartTime, ShiftEndTime, ShiftState, NumFootprint) |
| `shift_setting` | إعدادات الفترة (shift_id + إعدادات إضافية) |
| `shifts_schedule` | جدول الفترات (shift_id + أيام/أوقات) |
| `tbfingerprint` | أجهزة البصمة (FingerprintID, BranchID, FingerprintName, FingerprintType, FingerprintState, FingerprintSerailnumber, ip, port) |

### 2.5 جداول الإجازات (Leaves)
| Table | Purpose |
|-------|---------|
| `leaveclassification` | تصنيفات الإجازات (Id, BranchID, Name, Description, isaccept, type, state, RequiresAttachment, for_what, chose) |
| `tblleave` (مفترض) | طلبات الإجازات |

### 2.6 جداول مالية (Financial)
| Table | Purpose |
|-------|---------|
| `tblbenefit` | التعويضات والمزايا (Id, BranchID, UserID, name, Amount, Currency, Reason, for_what, extionsion, beneft_type, DueDate, AmountType, monthly, Status) |
| `tbldeductions` | الخصومات (Id, BranchID, UserID, name, Amount, Status, Currency, Reason, for_what, extionsion, DueDate) |
| `tblempadvances` | السلف (Id, UserID, Amount, currency, DueDate, Status, type, description) |
| `tblincentive` (مفترض) | الحوافز |
| `setting_account_salary` | إعدادات حسابات الرواتب (account_id, account_name) - 6 حسابات: مرتبات، مكافآت، تعويضات، سلف، خصومات، مستحقة |
| `salary_registration` | سجل صرف الرواتب (Id, month, year, BranchID) |
| `tblaccountguide` | دليل الحسابات (AccountID) |

### 2.7 جداول أخرى
| Table | Purpose |
|-------|---------|
| `tblresignation` | الاستقالات (UserID, BranchID, DueDate, Reason, type, created_by, CreatedDate, Status) |
| `tbinsurance` | التأمينات (Id, Name) |
| `tbladdress` | العناوين (AddressID, AddressType) |
| `tblidentitytypes` | أنواع الهويات (IDType, TypeName, AvailableFor) |
| `reports` | التقارير (id, app, name, parent, icon, url, sort, stopped) - هيكل شجري |

---

## 3. الوحدات الموجودة (Existing Modules)

### 3.1 إدارة الموظفين (Employee Management) ✅
- إضافة/تعديل/عرض/قائمة الموظفين
- بيانات شخصية شاملة (هوية، جواز، رخصة، تأمين صحي + مرفقات)
- ربط بالهيكل التنظيمي (قسم، مجموعة، درجة، مسمى وظيفي)
- إدارة المدير المباشر (`change-manager-emp.php`)
- إيقاف/تفعيل موظف (`stop_emp.php`)
- المرؤوسين (`subordinate-emp.php`)
- معلومات الموظف الذاتية (`emp-info.php`)
- شهادات الموظف (`user-certifacte.php`, `UserCertifcate-add.php`)
- خبرات الموظف (`user-experince.php`, `UserExperince-add.php`)

### 3.2 الهيكل التنظيمي (Org Structure) ✅
- الأقسام (شجرية) - `section-add/list/view/remove/tree`
- المجموعات - `groub-add/list/view/remove`
- الدرجات الوظيفية - `jobgrade-add/list/view/remove`
- المسميات الوظيفية - `jobtitle-add/list/view/remove`
- أنواع التوظيف - `empolyment-add/list/view/remove`

### 3.3 العقود والترقيات (Contracts & Promotions) ✅
- تجديد العقود - `contractRenewal-add/list/view/remove/conform`
- الترقيات - `promotion-add/list/view/remove/conform`
- قائمة عقود الموظف - `contractRenewal-emp-list.php`

### 3.4 الحضور والانصراف (Attendance) ✅
- كشف الحضور والانصراف - `reveal-attendance.php` + `reveal-attendance-view.php`
- حضور الموظف الذاتي - `attendancet-emp.php`
- أجهزة البصمة - `fingerprint-add/list/view/remove`
- نسيان البصمة - `finger-forget-add/list/view/remove` (+ admin versions)
- استيراد من Excel - `import-emp-atten.php`
- **حضور GPS من التطبيق** - `Hrdashboard.php` (موجود بشكل أولي!)
  - يدعم نوعين من التحقق الجغرافي:
    - **نقطة واحدة + نصف قطر** (TypeBracnhLocation=1)
    - **أربع نقاط (مضلع)** (TypeBracnhLocation=2)

### 3.5 الإجازات (Leaves) ✅
- تصنيفات الإجازات - `leaveClassficate-add/list/view/remove`
- طلبات الإجازات - `leaveRequest-add/list/view/remove/upload` (+ admin + add versions)
- موافقة/رفض - `leaveRequest-conform-admin.php`, `leaveRequest-deni-admin.php`
- دعم المرفقات (RequiresAttachment)
- تصنيف حسب: موظف محدد، قسم، مجموعة، درجة، مسمى وظيفي

### 3.6 المالية (Financial) ✅
- التعويضات والمزايا - `Benefits-add/list/view/remove/conform`
- الخصومات - `deductions-add/list/view/remove/conform`
- السلف - `EmpAdvances-add/list/view/remove/upload` (+ admin + add versions)
- الحوافز - `incentive-add/list/view/remove/conform`
- إصدار الرواتب - `Issuing-salaries.php` + `Issuing-salaries-list/view`
- صرف الرواتب - `salary-disbursement.php`
- إعدادات حسابات الرواتب - `save-setting-account-salary.php`
- ربط محاسبي - `accountant-coa.php`

### 3.7 الاستقالات والفصل (Resignation & Dismissal) ✅
- استقالات - `resignation-add/list/view/remove/upload` (+ admin + add versions)
- فصل - `dismissal-add/list/upload`

### 3.8 أوامر الموظفين (Employee Orders) ✅
- `order-emp-add/list/view/remove/upload` (+ admin versions)

### 3.9 الإجازات الرسمية (Holidays) ✅
- `holidays-add/list/view/remove`

### 3.10 التأمينات (Insurance) ✅
- `insurance-add/list/view/remove`

### 3.11 الفروع (Branches) ✅
- `branches-add/list/settings`
- دعم تعدد الفروع
- إعدادات الموقع الجغرافي للفرع

### 3.12 التقارير (Reports) ✅
- مركز تقارير شجري (`report-center.php`)
- تقارير شاملة لكل وحدة (30+ تقرير)
- تقارير تفصيلية (report-one-*)
- تصدير الرواتب (`report-export-salarys.php`)

### 3.13 الصلاحيات (Permissions) ✅
- مجموعات صلاحيات (`users-group-add.php`)
- صلاحيات على مستوى الصفحة (`$page_perm`)
- صلاحيات على مستوى التطبيق (`$appid = 'HR'`)
- فحص الصلاحيات: `$User->isAllowedPerm(['permission_name'], $appid)`
- تعدد الفروع للمستخدم (`AllowedBranches`)

### 3.14 لوحات المعلومات (Dashboards) ✅
- لوحة الإدارة - `dashboard.php` (مبيعات + إيرادات - من النظام الأم)
- لوحة الموظف - `dashboard-emp.php` (راتب، خصومات، حوافز، تعويضات)
- لوحة HR - `Hrdashboard.php` (حضور GPS)

---

## 4. نظام الصلاحيات والأمان الحالي (Current Auth & Security)

### 4.1 المصادقة (Authentication)
- تسجيل دخول بالبريد الإلكتروني + كلمة المرور
- `$User->login()` method من الـ core
- Session-based authentication
- دعم فترة تجريبية (`SiteEndDate`)
- توجيه حسب الصفحة الرئيسية (`home_page`)

### 4.2 التفويض (Authorization)
- **RBAC موجود** عبر `tblusergroups` + `$User->isAllowedPerm()`
- صلاحيات على مستوى:
  - التطبيق (`$appid`)
  - الصفحة (`$page_perm` array)
  - الفرع (`$branch`, `AllowedBranches`)
- فصل بين: Admin / Manager / Employee
- `$User->userIsAdmin()` للتحقق من صلاحيات الإدارة

### 4.3 نقاط ضعف أمنية ملاحظة ⚠️
- بعض الاستعلامات تستخدم string interpolation بدلاً من prepared statements (مثل `WHERE BranchID IN ($row[BranchID])`)
- لا يوجد CSRF token ظاهر
- لا يوجد rate limiting
- لا يوجد audit log
- لا يوجد API authentication (JWT/OAuth) - النظام يعتمد كلياً على sessions

---

## 5. ما يحتاجه المشروع بالضبط (What the Project Needs)

### المرحلة 1: تأمين وتحسين النظام الحالي (Foundation) - 3-4 أسابيع

#### 1.1 إصلاحات أمنية عاجلة
- [ ] إصلاح SQL Injection في الاستعلامات التي تستخدم string interpolation
- [ ] إضافة CSRF tokens لجميع النماذج
- [ ] إضافة rate limiting لصفحة تسجيل الدخول
- [ ] تشفير كلمات المرور بـ `password_hash()` (التحقق من الوضع الحالي في الـ core)
- [ ] إضافة Content Security Policy headers

#### 1.2 إنشاء طبقة API (بدون كسر النظام)
- [ ] إنشاء مجلد `api/v1/` منفصل تماماً عن الصفحات الحالية
- [ ] إنشاء `api/v1/bootstrap.php` يحمّل الـ core بدون HTML
- [ ] إنشاء JWT Authentication middleware
- [ ] إنشاء Response helper (JSON standardized responses)
- [ ] إنشاء Router بسيط للـ API endpoints
- [ ] إنشاء middleware للتحقق من الصلاحيات

#### 1.3 Audit Log
- [ ] إنشاء جدول `audit_log` (user_id, action, table_name, record_id, old_data, new_data, ip_address, user_agent, created_at)
- [ ] إنشاء helper function `logAction()` يتم استدعاؤها في كل عملية CRUD

### المرحلة 2: API Endpoints للـ ESS (4-5 أسابيع)

#### 2.1 Authentication API
```
POST   /api/v1/auth/login          → JWT token
POST   /api/v1/auth/refresh         → Refresh token
POST   /api/v1/auth/logout          → Invalidate token
POST   /api/v1/auth/forgot-password → Reset password
GET    /api/v1/auth/me              → Current user info
```

#### 2.2 Employee Self-Service API
```
GET    /api/v1/employee/profile           → بيانات الموظف
PUT    /api/v1/employee/profile           → تحديث بيانات محدودة
GET    /api/v1/employee/documents         → مستندات الموظف
GET    /api/v1/employee/salary-slips      → كشوف الرواتب
GET    /api/v1/employee/salary-slips/:id  → كشف راتب PDF
GET    /api/v1/employee/certificates      → الشهادات
GET    /api/v1/employee/experience        → الخبرات
```

#### 2.3 Attendance API
```
POST   /api/v1/attendance/check-in        → تسجيل حضور (GPS + device info)
POST   /api/v1/attendance/check-out       → تسجيل انصراف
GET    /api/v1/attendance/today           → حالة اليوم
GET    /api/v1/attendance/history         → سجل الحضور
POST   /api/v1/attendance/qr-scan        → حضور عبر QR
GET    /api/v1/attendance/qr-generate     → توليد QR (للإدارة)
```

#### 2.4 Leave Requests API
```
GET    /api/v1/leaves/types              → أنواع الإجازات المتاحة
GET    /api/v1/leaves/balance            → رصيد الإجازات
POST   /api/v1/leaves/request            → طلب إجازة جديد
GET    /api/v1/leaves/requests           → طلباتي
GET    /api/v1/leaves/requests/:id       → تفاصيل طلب
DELETE /api/v1/leaves/requests/:id       → إلغاء طلب
POST   /api/v1/leaves/requests/:id/upload → رفع مرفق
```

#### 2.5 Advances API
```
POST   /api/v1/advances/request          → طلب سلفة
GET    /api/v1/advances/requests         → طلباتي
GET    /api/v1/advances/requests/:id     → تفاصيل
```

#### 2.6 Manager Approvals API
```
GET    /api/v1/approvals/pending         → الطلبات المعلقة
POST   /api/v1/approvals/:type/:id/approve  → موافقة
POST   /api/v1/approvals/:type/:id/reject   → رفض
```

#### 2.7 Notifications API
```
GET    /api/v1/notifications             → الإشعارات
PUT    /api/v1/notifications/:id/read    → تعليم كمقروء
GET    /api/v1/notifications/unread-count → عدد غير المقروءة
```

#### 2.8 Document Download API
```
GET    /api/v1/documents/salary-slip/:month/:year  → كشف راتب PDF
GET    /api/v1/documents/experience-letter          → شهادة خبرة
GET    /api/v1/documents/salary-definition           → تعريف راتب
```

### المرحلة 3: تطبيق ESS (PWA) (5-6 أسابيع)

#### 3.1 التقنيات المقترحة
| Component | Technology | Reason |
|-----------|-----------|--------|
| **Framework** | React + Vite | سرعة، PWA support ممتاز |
| **UI** | Tailwind CSS + shadcn/ui | RTL support، حديث |
| **State** | Zustand or React Query | خفيف، فعال |
| **PWA** | Workbox | Service Worker management |
| **Push** | Firebase Cloud Messaging | إشعارات لحظية |
| **QR** | html5-qrcode | مسح QR |
| **GPS** | Geolocation API | موجود أصلاً في النظام |

#### 3.2 شاشات التطبيق
1. **تسجيل الدخول** - بريد + كلمة مرور
2. **الرئيسية** - ملخص (حالة الحضور، رصيد إجازات، إشعارات)
3. **الحضور** - زر حضور/انصراف + GPS + QR + سجل
4. **الإجازات** - طلب جديد + قائمة طلباتي + رصيد
5. **السلف** - طلب + قائمة
6. **المستندات** - كشف راتب + شهادات + تعريف
7. **الموافقات** (للمدراء) - قائمة + موافقة/رفض
8. **الإشعارات** - قائمة + تفاصيل
9. **الملف الشخصي** - بيانات + تعديل

### المرحلة 4: التكامل والاختبار (2-3 أسابيع)

#### 4.1 تكامل أجهزة البصمة
- النظام الحالي يدعم أجهزة بصمة عبر `tbfingerprint` (IP + Port + Serial)
- يجب إنشاء **sync service** يقرأ من أجهزة البصمة ويكتب في نفس جدول الحضور
- التطبيق يكتب في نفس الجدول مع حقل `source` (device/app/manual/excel)

#### 4.2 منع التلاعب في الحضور
- **GPS Spoofing Prevention:**
  - التحقق من GPS accuracy (رفض إذا accuracy > 100m)
  - مقارنة IP address مع نطاق الشركة
  - تسجيل device fingerprint
  - فحص Mock Location flag (Android)
- **QR Code Security:**
  - QR يتغير كل 30 ثانية (TOTP-based)
  - QR يحتوي على: branch_id + timestamp + HMAC signature
  - صالح فقط لمدة 60 ثانية
- **Dual Verification:** GPS + QR معاً للأمان الأعلى

---

## 6. كيف سيتم إضافة API و ESS بدون كسر النظام؟

### المبدأ: **Parallel Architecture**

```
Vision System (existing)
├── inc/                    ← Core (لا يُمس)
├── hr/                     ← HR pages (لا تُمس)
├── hr-app/                 ← HR AJAX handlers (لا تُمس)
│
├── api/                    ← [NEW] API Layer
│   ├── v1/
│   │   ├── bootstrap.php   ← يحمّل core بدون HTML output
│   │   ├── router.php      ← Simple router
│   │   ├── middleware/
│   │   │   ├── auth.php    ← JWT verification
│   │   │   ├── rbac.php    ← Permission check
│   │   │   └── audit.php   ← Audit logging
│   │   ├── controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── AttendanceController.php
│   │   │   ├── LeaveController.php
│   │   │   ├── AdvanceController.php
│   │   │   ├── DocumentController.php
│   │   │   └── NotificationController.php
│   │   └── helpers/
│   │       ├── Response.php
│   │       ├── Validator.php
│   │       └── JWTHelper.php
│   └── .htaccess           ← Route all /api/* requests
│
├── ess/                    ← [NEW] PWA App (built files)
│   ├── index.html
│   ├── manifest.json
│   ├── sw.js
│   └── assets/
│
└── shared/                 ← [NEW] Shared logic
    ├── models/             ← Reusable data access
    ├── services/           ← Business logic
    └── notifications/      ← Push notification service
```

### القواعد الذهبية:
1. **لا تعديل على أي ملف موجود** - كل الكود الجديد في مجلدات منفصلة
2. **نفس قاعدة البيانات** - API يقرأ ويكتب في نفس الجداول
3. **نفس Business Logic** - يتم استخراج الـ logic المشترك إلى `shared/`
4. **الـ API يستخدم الـ core** - يحمّل `$connect_pdo` و `$User` من الـ core
5. **Backward Compatible** - أي جدول جديد أو حقل جديد لا يؤثر على الموجود

---

## 7. الجدول الزمني (Timeline)

| المرحلة | المدة | التسليمات |
|---------|-------|----------|
| **Phase 1: Foundation** | أسبوع 1-4 | إصلاحات أمنية + API skeleton + JWT auth + Audit log |
| **Phase 2: Core API** | أسبوع 5-9 | جميع API endpoints + اختبارات + توثيق |
| **Phase 3: ESS PWA** | أسبوع 10-15 | تطبيق PWA كامل + GPS attendance + QR + Push notifications |
| **Phase 4: Integration** | أسبوع 16-18 | تكامل أجهزة البصمة + منع التلاعب + اختبار شامل |
| **Phase 5: Polish** | أسبوع 19-20 | تحسينات UX + أداء + توثيق نهائي + تدريب |

**المدة الإجمالية: 18-20 أسبوع (4.5-5 أشهر)**

---

## 8. ملخص تنفيذي (Executive Summary)

### النظام الحالي يحتوي على:
- ✅ 14+ وحدة HR كاملة تعمل
- ✅ نظام صلاحيات RBAC
- ✅ دعم تعدد الفروع
- ✅ نظام حضور GPS أولي
- ✅ نظام رواتب + ربط محاسبي
- ✅ 30+ تقرير
- ✅ بنية PWA أولية (Service Worker مسجل)

### ما ينقص:
- ❌ REST API آمن (JWT)
- ❌ تطبيق ESS للموظفين
- ❌ إشعارات لحظية (Push Notifications)
- ❌ Audit Log
- ❌ حضور QR Code
- ❌ تحميل مستندات (كشف راتب PDF، شهادة خبرة، تعريف راتب)
- ❌ منع التلاعب في GPS
- ❌ CSRF Protection
- ❌ API Rate Limiting

### المخاطر:
- ⚠️ الـ core code غير متاح في هذا المستودع - يجب الوصول إليه
- ⚠️ بعض الاستعلامات بها ثغرات SQL Injection
- ⚠️ لا يوجد unit tests
- ⚠️ الكود غير موثق (no documentation)
