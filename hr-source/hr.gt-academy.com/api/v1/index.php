<?php
/**
 * Vision HR - API v1 Entry Point
 * Routes all API requests to the appropriate controller
 */

// Bootstrap (loads core, helpers, middleware)
require_once __DIR__ . '/bootstrap.php';

// Load router
require_once __DIR__ . '/router.php';

// Load controllers
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/EmployeeController.php';
require_once __DIR__ . '/controllers/AttendanceController.php';
require_once __DIR__ . '/controllers/LeaveController.php';
require_once __DIR__ . '/controllers/AdvanceController.php';
require_once __DIR__ . '/controllers/ApprovalController.php';
require_once __DIR__ . '/controllers/NotificationController.php';
require_once __DIR__ . '/controllers/DocumentController.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/UploadController.php';
require_once __DIR__ . '/controllers/PushController.php';
require_once __DIR__ . '/controllers/BiometricController.php';
require_once __DIR__ . '/controllers/PolicyController.php';

// Apply global rate limiting (except for OPTIONS)
if (getMethod() !== 'OPTIONS') {
    rateLimitMiddleware('api');
}

// Initialize router
$router = new Router();

// ============================================================
// AUTH ROUTES (public - no JWT required)
// ============================================================
$router->post('/auth/login', function () {
    AuthController::login();
});

$router->post('/auth/refresh', function () {
    AuthController::refresh();
});

$router->post('/auth/forgot-password', function () {
    AuthController::forgotPassword();
});

$router->post('/auth/reset-password', function () {
    AuthController::resetPassword();
});

// AUTH ROUTES (protected)
$router->post('/auth/logout', function () {
    AuthController::logout();
});

$router->get('/auth/me', function () {
    AuthController::me();
});

$router->post('/auth/change-password', function () {
    AuthController::changePassword();
});

// ============================================================
// EMPLOYEE SELF-SERVICE ROUTES
// ============================================================
$router->get('/employee/profile', function () {
    EmployeeController::profile();
});

$router->put('/employee/profile', function () {
    EmployeeController::updateProfile();
});

$router->get('/employee/salary-slips', function () {
    EmployeeController::salarySlips();
});

$router->get('/employee/certificates', function () {
    EmployeeController::certificates();
});

$router->get('/employee/experience', function () {
    EmployeeController::experience();
});

$router->get('/employee/salary-slips/:id', function ($params) {
    EmployeeController::salarySlipById($params);
});

$router->get('/employee/documents', function () {
    EmployeeController::documents();
});

$router->get('/employee/contracts', function () {
    EmployeeController::contracts();
});

// ============================================================
// ATTENDANCE ROUTES
// ============================================================
$router->post('/attendance/check-in', function () {
    AttendanceController::checkIn();
});

$router->post('/attendance/check-out', function () {
    AttendanceController::checkOut();
});

$router->get('/attendance/today', function () {
    AttendanceController::today();
});

$router->get('/attendance/history', function () {
    AttendanceController::history();
});

$router->post('/attendance/qr-scan', function () {
    AttendanceController::qrScan();
});

$router->get('/attendance/qr-generate', function () {
    AttendanceController::qrGenerate();
});

$router->get('/attendance/qr-active', function () {
    AttendanceController::qrActive();
});

// ============================================================
// LEAVE ROUTES
// ============================================================
$router->get('/leaves/types', function () {
    LeaveController::types();
});

$router->get('/leaves/balance', function () {
    LeaveController::balance();
});

$router->post('/leaves/request', function () {
    LeaveController::createRequest();
});

$router->get('/leaves/requests', function () {
    LeaveController::listRequests();
});

$router->get('/leaves/requests/:id', function ($params) {
    LeaveController::getRequest($params);
});

$router->delete('/leaves/requests/:id', function ($params) {
    LeaveController::cancelRequest($params);
});

// ============================================================
// ADVANCE ROUTES
// ============================================================
$router->post('/advances/request', function () {
    AdvanceController::createRequest();
});

$router->get('/advances/requests', function () {
    AdvanceController::listRequests();
});

$router->get('/advances/requests/:id', function ($params) {
    AdvanceController::getRequest($params);
});

// ============================================================
// APPROVAL ROUTES (Manager/Admin)
// ============================================================
$router->get('/approvals/pending', function () {
    ApprovalController::pending();
});

$router->post('/approvals/:type/:id/approve', function ($params) {
    ApprovalController::approve($params);
});

$router->post('/approvals/:type/:id/reject', function ($params) {
    ApprovalController::reject($params);
});

// ============================================================
// NOTIFICATION ROUTES
// ============================================================
$router->get('/notifications', function () {
    NotificationController::list();
});

$router->put('/notifications/:id/read', function ($params) {
    NotificationController::markRead($params);
});

$router->put('/notifications/read-all', function () {
    NotificationController::markAllRead();
});

$router->get('/notifications/unread-count', function () {
    NotificationController::unreadCount();
});

// ============================================================
// DOCUMENT ROUTES
// ============================================================
$router->get('/documents/salary-slip/:month/:year', function ($params) {
    DocumentController::salarySlip($params);
});

$router->get('/documents/experience-letter', function () {
    DocumentController::experienceLetter();
});

$router->get('/documents/salary-definition', function () {
    DocumentController::salaryDefinition();
});

// ============================================================
// DASHBOARD ROUTES
// ============================================================
$router->get('/dashboard/employee', function () {
    DashboardController::employee();
});

$router->get('/dashboard/manager', function () {
    DashboardController::manager();
});

// ============================================================
// FILE UPLOAD ROUTES
// ============================================================
$router->post('/upload/leave/:id', function ($params) {
    UploadController::leaveAttachment($params);
});

$router->post('/upload/advance/:id', function ($params) {
    UploadController::advanceAttachment($params);
});

$router->post('/upload/order/:id', function ($params) {
    UploadController::orderAttachment($params);
});

$router->post('/upload/resignation/:id', function ($params) {
    UploadController::resignationAttachment($params);
});

$router->post('/upload/profile-photo', function () {
    UploadController::profilePhoto();
});

// ============================================================
// PUSH NOTIFICATION ROUTES
// ============================================================
$router->post('/push/subscribe', function () {
    PushController::subscribe();
});

$router->post('/push/unsubscribe', function () {
    PushController::unsubscribe();
});

$router->get('/push/subscriptions', function () {
    PushController::listSubscriptions();
});

$router->post('/push/test', function () {
    PushController::test();
});

// ============================================================
// BIOMETRIC DEVICE ROUTES (Admin)
// ============================================================
$router->get('/biometric/devices', function () {
    BiometricController::devices();
});

$router->post('/biometric/sync', function () {
    BiometricController::syncAll();
});

$router->post('/biometric/sync/:id', function ($params) {
    BiometricController::syncDevice($params);
});

$router->get('/biometric/test/:id', function ($params) {
    BiometricController::testDevice($params);
});

$router->get('/biometric/sync-log', function () {
    BiometricController::syncLog();
});

// ============================================================
// POLICY SYSTEM ROUTES
// ============================================================

$makePolicyController = function (): PolicyController {
    global $connect_pdo;
    $apiUser = authMiddleware();
    return new PolicyController($connect_pdo, (int) $apiUser['id']);
};
$GLOBALS['makePolicyController'] = $makePolicyController;

// Leave Balance & Policy
$router->get('/policy/leave-balance', function () {
    $makePolicyController = $GLOBALS['makePolicyController'] ?? null;
    if (!$makePolicyController) {
        Response::serverError('Policy controller factory unavailable');
    }
    $makePolicyController()->getLeaveBalance();
});

$router->get('/policy/leave-policy', function () {
    $makePolicyController = $GLOBALS['makePolicyController'] ?? null;
    if (!$makePolicyController) {
        Response::serverError('Policy controller factory unavailable');
    }
    $makePolicyController()->getLeavePolicy();
});

$router->post('/policy/request-leave', function () {
    $makePolicyController = $GLOBALS['makePolicyController'] ?? null;
    if (!$makePolicyController) {
        Response::serverError('Policy controller factory unavailable');
    }
    $makePolicyController()->requestLeave();
});

// Violations
$router->get('/policy/my-violations', function () {
    $makePolicyController = $GLOBALS['makePolicyController'] ?? null;
    if (!$makePolicyController) {
        Response::serverError('Policy controller factory unavailable');
    }
    $makePolicyController()->getMyViolations();
});

$router->get('/policy/violation-summary', function () {
    $makePolicyController = $GLOBALS['makePolicyController'] ?? null;
    if (!$makePolicyController) {
        Response::serverError('Policy controller factory unavailable');
    }
    $makePolicyController()->getViolationSummary();
});

// External Tasks
$router->get('/policy/my-tasks', function () {
    $makePolicyController = $GLOBALS['makePolicyController'] ?? null;
    if (!$makePolicyController) {
        Response::serverError('Policy controller factory unavailable');
    }
    $makePolicyController()->getMyTasks();
});

$router->get('/policy/today-tasks', function () {
    $makePolicyController = $GLOBALS['makePolicyController'] ?? null;
    if (!$makePolicyController) {
        Response::serverError('Policy controller factory unavailable');
    }
    $makePolicyController()->getTodayTasks();
});

$router->post('/policy/create-task', function () {
    $makePolicyController = $GLOBALS['makePolicyController'] ?? null;
    if (!$makePolicyController) {
        Response::serverError('Policy controller factory unavailable');
    }
    $makePolicyController()->createTask();
});

$router->post('/policy/start-task', function () {
    $makePolicyController = $GLOBALS['makePolicyController'] ?? null;
    if (!$makePolicyController) {
        Response::serverError('Policy controller factory unavailable');
    }
    $makePolicyController()->startTask();
});

$router->post('/policy/end-task', function () {
    $makePolicyController = $GLOBALS['makePolicyController'] ?? null;
    if (!$makePolicyController) {
        Response::serverError('Policy controller factory unavailable');
    }
    $makePolicyController()->endTask();
});

// Presence
$router->get('/policy/my-presence', function () {
    $makePolicyController = $GLOBALS['makePolicyController'] ?? null;
    if (!$makePolicyController) {
        Response::serverError('Policy controller factory unavailable');
    }
    $makePolicyController()->getMyPresence();
});

$router->post('/policy/update-presence', function () {
    $makePolicyController = $GLOBALS['makePolicyController'] ?? null;
    if (!$makePolicyController) {
        Response::serverError('Policy controller factory unavailable');
    }
    $makePolicyController()->updatePresence();
});

$router->get('/policy/presence-options', function () {
    $makePolicyController = $GLOBALS['makePolicyController'] ?? null;
    if (!$makePolicyController) {
        Response::serverError('Policy controller factory unavailable');
    }
    $makePolicyController()->getPresenceOptions();
});

// Org Chart
$router->get('/policy/org-chart', function () {
    $makePolicyController = $GLOBALS['makePolicyController'] ?? null;
    if (!$makePolicyController) {
        Response::serverError('Policy controller factory unavailable');
    }
    $makePolicyController()->getOrgChart();
});

$router->get('/policy/department-employees', function () {
    $makePolicyController = $GLOBALS['makePolicyController'] ?? null;
    if (!$makePolicyController) {
        Response::serverError('Policy controller factory unavailable');
    }
    $makePolicyController()->getDepartmentEmployees();
});

// Promotion Eligibility
$router->get('/policy/promotion-eligibility', function () {
    $makePolicyController = $GLOBALS['makePolicyController'] ?? null;
    if (!$makePolicyController) {
        Response::serverError('Policy controller factory unavailable');
    }
    $makePolicyController()->checkPromotionEligibility();
});

// ============================================================
// HEALTH CHECK
// ============================================================
$router->get('/health', function () {
    Response::success([
        'status'  => 'ok',
        'version' => 'v1',
        'time'    => date('Y-m-d H:i:s'),
    ]);
});

// Dispatch the request
$router->dispatch();
