# Vision HR - REST API Documentation v1

**Base URL:** `/HR/api/v1`  
**Content-Type:** `application/json`  
**Authentication:** Bearer JWT Token (except public endpoints)

---

## Setup

### 1. Run Database Migrations
```sql
mysql -u root vision_hr < shared/migrations/001_foundation.sql
mysql -u root vision_hr < shared/migrations/002_missing_features.sql
```

### 2. Configure JWT Secret
Edit `api/v1/config.php` and change `JWT_SECRET` to a strong random string.

### 3. Configure CORS Origins
Edit `api/v1/config.php` → `API_CORS_ORIGINS` array.

### 4. Configure FCM (Optional)
Set `FCM_SERVER_KEY` in `api/v1/config.php` for push notifications.

---

## Standard Response Format

### Success
```json
{
  "success": true,
  "message": "...",
  "data": { ... }
}
```

### Error
```json
{
  "success": false,
  "message": "...",
  "errors": { "field": "error message" }
}
```

### Paginated
```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "total": 100,
    "page": 1,
    "per_page": 20,
    "total_pages": 5,
    "has_more": true
  }
}
```

---

## 1. Authentication

### POST `/auth/login`
Login and receive JWT tokens.

**Body:**
```json
{
  "email": "admin@vision.hr",
  "password": "admin123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح",
  "data": {
    "access_token": "eyJ...",
    "refresh_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 900,
    "user": {
      "id": 1,
      "email": "admin@vision.hr",
      "name": "مدير النظام",
      "is_admin": true,
      "branch_id": 1,
      "job_title": "مدير عام",
      "section": "الإدارة العامة"
    }
  }
}
```

**Rate Limited:** 5 attempts per 5 minutes per IP+email.

---

### POST `/auth/refresh`
Refresh an expired access token.

**Body:**
```json
{ "refresh_token": "eyJ..." }
```

**Response (200):** New `access_token` + `refresh_token`.

---

### POST `/auth/forgot-password`
Generate a password reset token. Rate limited: 3 per 10 minutes.

**Body:**
```json
{ "email": "user@vision.hr" }
```

**Response (200):** Returns `reset_token` + `expires_at` (dev mode). In production, sends email.

---

### POST `/auth/reset-password`
Reset password using a reset token.

**Body:**
```json
{
  "token": "abc123...",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

**Response (200):** Success. All refresh tokens revoked (forces re-login).

---

### POST `/auth/logout` 🔒
Revoke refresh token.

**Body:**
```json
{
  "refresh_token": "eyJ...",
  "all_devices": false
}
```

---

### GET `/auth/me` 🔒
Get current user info with contract details.

---

### POST `/auth/change-password` 🔒
Change password for authenticated user.

**Body:**
```json
{
  "current_password": "oldpass",
  "new_password": "newpass",
  "new_password_confirmation": "newpass"
}
```

---

## 2. Employee Self-Service

### GET `/employee/profile` 🔒
Full employee profile (personal, employment, documents, banking).

### PUT `/employee/profile` 🔒
Update limited fields: `phone`, `other_phone`, `address`, `marital_status`, `health_condition`.

### GET `/employee/salary-slips` 🔒
Paginated salary slip history. Query: `?page=1&per_page=20`

### GET `/employee/salary-slips/:id` 🔒
Detailed salary slip by ID with full earnings/deductions breakdown.

### GET `/employee/documents` 🔒
Document expiry summary (national ID, license, passport, health insurance) with status: `valid`, `expiring_soon`, `expired`, `missing`.

### GET `/employee/contracts` 🔒
Contract renewal history with job title, grade, salary changes.

### GET `/employee/certificates` 🔒
Employee certificates list.

### GET `/employee/experience` 🔒
Employee work experience list.

---

## 3. Attendance

### POST `/attendance/check-in` 🔒
Record check-in with GPS + comprehensive anti-spoofing.

**Body:**
```json
{
  "lat": 24.7136,
  "lng": 46.6753,
  "accuracy": 15.5,
  "mock_location": false,
  "device_fingerprint": "abc123"
}
```

**Anti-Spoofing Checks:**
- GPS accuracy ≤ 100m
- Mock location detection → rejected
- Device fingerprint tracking (max 3 devices per user)
- IP range validation against branch allowed ranges
- Velocity check (impossible travel detection)
- Risk score calculation (blocked if ≥ 70)
- Branch geofence validation (point+radius or polygon)

---

### POST `/attendance/check-out` 🔒
Same body as check-in. Returns working hours.

### GET `/attendance/today` 🔒
Today's attendance status: `absent`, `checked_in`, or `checked_out`.

### GET `/attendance/history` 🔒
Attendance history grouped by date.  
Query: `?date_from=2025-01-01&date_to=2025-01-31&page=1`

### POST `/attendance/qr-scan` 🔒
Scan QR code for attendance. Uses TOTP-based HMAC validation.

**Body:**
```json
{
  "qr_code": "json_or_shortcode...",
  "lat": 24.7136,
  "lng": 46.6753,
  "accuracy": 15.5
}
```

### GET `/attendance/qr-generate` 🔒 (Admin/Manager)
Generate a TOTP-based rotating QR code with HMAC-SHA256 signing.  
Query: `?branch_id=1`  
QR codes rotate every 30s, expire after 60s.

### GET `/attendance/qr-active` 🔒 (Admin/Manager)
Get the current active QR code for a branch.  
Query: `?branch_id=1`

---

## 4. Leave Requests

### GET `/leaves/types` 🔒
Available leave types filtered by employee eligibility.

### GET `/leaves/balance` 🔒
Leave balance per type. Query: `?year=2025`

### POST `/leaves/request` 🔒
Submit leave request.

**Body:**
```json
{
  "leave_type_id": 1,
  "start_date": "2025-02-01",
  "end_date": "2025-02-05",
  "reason": "إجازة سنوية"
}
```

**Validations:** Balance check, overlap check, date validation.

### GET `/leaves/requests` 🔒
My leave requests. Query: `?status=0&page=1` (0=pending, 1=approved, 2=rejected)

### GET `/leaves/requests/:id` 🔒
Leave request details.

### DELETE `/leaves/requests/:id` 🔒
Cancel a pending leave request.

---

## 5. Advances (Salary Loans)

### POST `/advances/request` 🔒
```json
{
  "amount": 5000,
  "due_date": "2025-03-01",
  "description": "سلفة شخصية",
  "type": 1
}
```

### GET `/advances/requests` 🔒
My advance requests (paginated).

### GET `/advances/requests/:id` 🔒
Advance request details.

---

## 6. Manager Approvals

### GET `/approvals/pending` 🔒 (Manager/Admin)
All pending requests from subordinates.  
Query: `?type=leave` (leave, advance, resignation, order, finger_forget)

### POST `/approvals/:type/:id/approve` 🔒 (Manager/Admin)
Approve a request. Optional body: `{ "notes": "..." }`

### POST `/approvals/:type/:id/reject` 🔒 (Manager/Admin)
Reject a request. Optional body: `{ "reason": "..." }`

**Types:** `leave`, `advance`, `resignation`, `order`, `finger_forget`

---

## 7. Notifications

### GET `/notifications` 🔒
User notifications (paginated). Query: `?unread=1&page=1`

### PUT `/notifications/:id/read` 🔒
Mark notification as read.

### PUT `/notifications/read-all` 🔒
Mark all notifications as read.

### GET `/notifications/unread-count` 🔒
Get unread notification count.

---

## 8. Documents

### GET `/documents/salary-slip/:month/:year` 🔒
Salary slip data for PDF generation.

### GET `/documents/experience-letter` 🔒
Experience letter data.

### GET `/documents/salary-definition` 🔒
Salary definition letter data.

---

## 9. Dashboard

### GET `/dashboard/employee` 🔒
Employee home screen stats:
- Today's attendance status (check-in/out times)
- Leave balance summary (per type + totals)
- Pending requests count (leaves, advances, orders)
- Unread notifications count
- Salary info (basic + currency)
- Contract expiry warning (if ≤ 30 days)
- Days present this month

### GET `/dashboard/manager` 🔒 (Manager/Admin)
Manager dashboard stats:
- Total employees count
- Today's attendance (present, absent, late, on leave)
- Pending approvals breakdown (leaves, advances, resignations, orders, finger forget)
- Expiring contracts count (next 30 days)

---

## 10. File Uploads

All upload endpoints accept `multipart/form-data` with a `file` field.  
**Max size:** 10MB. **Allowed types:** jpg, jpeg, png, pdf, doc, docx, xls, xlsx.

### POST `/upload/leave/:id` 🔒
Upload attachment for a leave request.

### POST `/upload/advance/:id` 🔒
Upload attachment for an advance request.

### POST `/upload/order/:id` 🔒
Upload attachment for an employee order.

### POST `/upload/resignation/:id` 🔒
Upload attachment for a resignation.

### POST `/upload/profile-photo` 🔒
Upload profile photo. **Allowed types:** jpg, jpeg, png, webp.

---

## 11. Push Notifications (FCM)

### POST `/push/subscribe` 🔒
Register a device for push notifications.

**Body:**
```json
{
  "fcm_token": "firebase_token...",
  "device_type": "android",
  "device_name": "Samsung Galaxy S24"
}
```

### POST `/push/unsubscribe` 🔒
Unregister a device.

**Body:**
```json
{ "fcm_token": "firebase_token..." }
```

### GET `/push/subscriptions` 🔒
List active push subscriptions for current user.

### POST `/push/test` 🔒
Send a test push notification to current user.

---

## 12. Biometric Devices (Admin)

### GET `/biometric/devices` 🔒 (Admin)
List all fingerprint devices with branch info.

### POST `/biometric/sync` 🔒 (Admin)
Trigger sync for all active devices. Returns per-device results.

### POST `/biometric/sync/:id` 🔒 (Admin)
Trigger sync for a specific device.

### GET `/biometric/test/:id` 🔒 (Admin)
Test connection to a specific device.

### GET `/biometric/sync-log` 🔒 (Admin)
Paginated sync history log. Query: `?page=1&per_page=20`

---

## 13. Health Check

### GET `/health`
No auth required.
```json
{ "success": true, "data": { "status": "ok", "version": "v1" } }
```

---

## Security Features

| Feature | Implementation |
|---------|---------------|
| **JWT Auth** | HS256, 15min access + 7day refresh tokens |
| **Rate Limiting** | 60 req/min API, 5 login/5min, 3 forgot-pwd/10min |
| **CSRF** | Token-based (for web forms via `shared/Security.php`) |
| **CSP Headers** | Via `Security::sendCspHeaders()` |
| **Audit Log** | All CRUD operations logged to `audit_log` table |
| **Password Hashing** | bcrypt with auto-migration from plain text |
| **GPS Anti-Spoofing** | Accuracy, mock detection, geofence, velocity, IP range, device fingerprint, risk scoring |
| **QR Security** | TOTP-based, HMAC-SHA256 signed, 30s rotation, 60s expiry, branch-locked |
| **CORS** | Configurable allowed origins |
| **Input Validation** | Server-side validation on all endpoints |
| **Device Fingerprinting** | Track & limit devices per user |
| **IP Range Validation** | Branch-specific allowed IP ranges (CIDR) |
| **Velocity Check** | Detect impossible travel between attendance records |
| **FCM Push** | Firebase Cloud Messaging for real-time notifications |

---

## New Database Tables

| Table | Purpose | Migration |
|-------|---------|-----------|
| `audit_log` | Action audit trail | 001 |
| `jwt_refresh_tokens` | JWT refresh token storage | 001 |
| `notifications` | User notifications | 001 |
| `attendance_qr_codes` | TOTP rotating QR codes for attendance | 001 |
| `push_subscriptions` | FCM push notification subscriptions | 001 |
| `device_fingerprints` | Device fingerprint tracking for anti-spoofing | 002 |
| `branch_ip_ranges` | Branch-specific allowed IP ranges | 002 |
| `password_resets` | Password reset tokens | 002 |
| `biometric_sync_log` | Biometric device sync history | 002 |

**Modified Tables:**
- `tblattendance` — Added columns: `device_fingerprint`, `gps_accuracy`, `mock_location`, `qr_code_id`, `risk_score`

---

## File Structure
```
api/
├── API-DOCS.md                  # This documentation
└── v1/
    ├── index.php                # Entry point + route definitions (65 routes)
    ├── bootstrap.php            # Core loader (no HTML) + helper functions
    ├── config.php               # API configuration (JWT, CORS, GPS, FCM)
    ├── router.php               # Simple URI router with :param support
    ├── controllers/
    │   ├── AuthController.php        # Login, refresh, logout, me, forgot/reset/change password
    │   ├── EmployeeController.php    # Profile, salary slips, documents, contracts, certs, experience
    │   ├── AttendanceController.php  # Check-in/out, GPS, QR scan/generate, history
    │   ├── LeaveController.php       # Leave types, balance, requests CRUD
    │   ├── AdvanceController.php     # Advance requests CRUD
    │   ├── ApprovalController.php    # Manager approve/reject (5 types)
    │   ├── NotificationController.php # Notifications list, read, unread count
    │   ├── DocumentController.php    # Salary slip, experience letter, salary definition
    │   ├── DashboardController.php   # Employee + manager dashboard stats
    │   ├── UploadController.php      # File uploads (leave, advance, order, resignation, photo)
    │   ├── PushController.php        # FCM subscribe/unsubscribe/test
    │   └── BiometricController.php   # Device list, sync, test, sync log
    ├── helpers/
    │   ├── JWTHelper.php        # Pure PHP JWT encode/decode (HS256)
    │   ├── Response.php         # Standardized JSON responses (success, error, paginated)
    │   └── Validator.php        # Input validation (required, email, date, lat/lng, etc.)
    └── middleware/
        ├── auth.php             # JWT authentication
        ├── rbac.php             # Permission checks (role-based)
        ├── audit.php            # Audit logging middleware
        └── ratelimit.php        # File-based rate limiting

shared/
├── Security.php                 # CSRF tokens, CSP headers, password hashing (bcrypt)
├── RateLimiter.php              # File-based rate limiter (no Redis required)
├── AuditLog.php                 # Audit log helper + logAction()
├── QRCodeService.php            # TOTP-based QR code generation/validation with HMAC-SHA256
├── NotificationService.php      # Centralized in-app + FCM push notification service
├── AntiSpoof.php                # GPS spoofing prevention, device fingerprint, IP range, velocity
├── BiometricSync.php            # Fingerprint device sync (ZKTeco, Hikvision)
└── migrations/
    ├── 001_foundation.sql       # Core tables (audit_log, jwt, notifications, qr_codes, push)
    └── 002_missing_features.sql # Extended tables (device_fingerprints, ip_ranges, password_resets, sync_log)
```
