<?php
// header.php is included by page files which are loaded via index.php (router)
// If loaded directly (not through router), bootstrap manually
if (!isset($connect_pdo)) {
    session_start();
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/User.php';
    require_once __DIR__ . '/functions.php';
    $User = new User($connect_pdo);
    $user = $_SESSION['user_id'] ?? null;
    $branch = $_SESSION['branch'] ?? null;
    $coreDir = __DIR__ . '/../';
    $page = basename($_SERVER['SCRIPT_NAME'], '.php');
    if (!empty($user)) {
        $User->loadFromSession();
    }
}

// Permission check
if (!empty($page_perm) && !empty($appid)) {
    if (!$User->isAllowedPerm($page_perm, $appid)) {
        // For now, allow access in dev mode
    }
}

// Only main branch check
if (!empty($only_main_branch) && empty($branch)) {
    $q = $connect_pdo->prepare("SELECT branch_id FROM branches ORDER BY branch_id LIMIT 1");
    $q->execute();
    $b = $q->fetch(PDO::FETCH_ASSOC);
    if ($b) {
        $branch = $b['branch_id'];
        $_SESSION['branch'] = $branch;
    }
}

$userName = $_SESSION['user']['name'] ?? 'مستخدم';
$userFirstName = $_SESSION['user']['first_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#1e3a5f">
    <title><?= !empty($page_title) ? $page_title . ' - ' : '' ?>Vision HR</title>

    <!-- Google Font: Tajawal (Arabic) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <!-- Bootstrap Select -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.18/dist/css/bootstrap-select.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">
    <!-- DateRangePicker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
    :root {
        --sidebar-width: 270px;
        --sidebar-bg: #1e3a5f;
        --sidebar-hover: #2a4f7a;
        --sidebar-active: #3b82f6;
        --navbar-height: 60px;
        --font-base: 16px;
        --font-lg: 18px;
        --font-xl: 20px;
        --radius: 12px;
        --shadow: 0 2px 12px rgba(0,0,0,0.08);
        --transition: all 0.3s ease;
    }

    * { box-sizing: border-box; }

    html { font-size: var(--font-base); }

    body {
        font-family: 'Tajawal', -apple-system, BlinkMacSystemFont, sans-serif !important;
        direction: rtl;
        text-align: right;
        background: #f0f2f5 !important;
        color: #1a1a2e;
        -webkit-font-smoothing: antialiased;
        overflow-x: hidden;
    }

    /* ===== NAVBAR ===== */
    .main-header.navbar {
        height: var(--navbar-height);
        background: #fff !important;
        border: none !important;
        box-shadow: var(--shadow);
        z-index: 1030;
        padding: 0 20px;
        /* Padding-right accounts for sidebar width + 20px padding */
        padding-right: calc(var(--sidebar-width) + 20px) !important;
        direction: rtl;
        position: fixed;
        top: 0;
        right: 0 !important;
        left: 0 !important;
        width: 100% !important;
        margin: 0 !important;
        transition: padding-right 0.3s ease;
    }
    .main-header .navbar-nav { direction: rtl; }
    .main-header .nav-link {
        font-size: var(--font-lg) !important;
        color: #333 !important;
        padding: 10px 14px !important;
    }
    .main-header .nav-link:hover { color: var(--sidebar-active) !important; }
    .main-header .user-menu-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f0f2f5;
        border-radius: 50px;
        padding: 6px 16px 6px 10px;
        font-size: var(--font-base);
        font-weight: 500;
        color: #333;
        text-decoration: none;
        transition: var(--transition);
    }
    .main-header .user-menu-btn:hover { background: #e2e6ea; text-decoration: none; color: #333; }
    .main-header .user-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: var(--sidebar-active);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 15px;
    }
    .main-header .dropdown-menu {
        border: none;
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        border-radius: var(--radius);
        padding: 8px;
        min-width: 200px;
        direction: rtl;
        text-align: right;
    }
    .main-header .dropdown-item {
        border-radius: 8px;
        padding: 10px 16px;
        font-size: var(--font-base);
        font-weight: 500;
    }
    .main-header .dropdown-item:hover { background: #f0f2f5; }
    .main-header .dropdown-item i { margin-left: 10px; width: 20px; text-align: center; }

    /* ===== SIDEBAR ===== */
    .main-sidebar {
        background: var(--sidebar-bg) !important;
        width: var(--sidebar-width) !important;
        right: 0; left: auto;
        top: 0;
        position: fixed;
        height: 100vh;
        z-index: 1040;
        overflow-y: auto;
        overflow-x: hidden;
        transition: var(--transition);
        box-shadow: -4px 0 20px rgba(0,0,0,0.1);
        scrollbar-width: thin;
        scrollbar-color: rgba(255,255,255,0.2) transparent;
    }
    .main-sidebar::-webkit-scrollbar { width: 4px; }
    .main-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }

    .brand-link {
        display: flex !important;
        align-items: center;
        justify-content: center;
        height: var(--navbar-height);
        background: rgba(0,0,0,0.15) !important;
        border: none !important;
        padding: 0 20px !important;
    }
    .brand-link .brand-text {
        font-size: 22px !important;
        font-weight: 700 !important;
        color: #fff !important;
        letter-spacing: 1px;
    }

    .sidebar { padding: 10px 0; }

    .nav-sidebar .nav-item { margin: 2px 10px; }
    .nav-sidebar > .nav-item > .nav-link {
        border-radius: 10px !important;
        padding: 12px 16px !important;
        color: rgba(255,255,255,0.8) !important;
        font-size: var(--font-base) !important;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: var(--transition);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .nav-sidebar > .nav-item > .nav-link:hover {
        background: var(--sidebar-hover) !important;
        color: #fff !important;
    }
    .nav-sidebar > .nav-item > .nav-link.active {
        background: var(--sidebar-active) !important;
        color: #fff !important;
        font-weight: 700;
        box-shadow: 0 4px 15px rgba(59,130,246,0.4);
    }
    .nav-sidebar .nav-icon {
        font-size: 18px !important;
        width: 24px;
        text-align: center;
        flex-shrink: 0;
    }
    .nav-sidebar .nav-link p {
        margin: 0 !important;
        text-align: right;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 1;
    }
    .nav-sidebar .nav-link p .right {
        float: left;
        margin-top: 3px;
        transition: transform 0.2s ease;
    }
    .nav-item.has-treeview.menu-open > .nav-link p .right {
        transform: rotate(-90deg);
    }

    .nav-header {
        color: rgba(255,255,255,0.4) !important;
        font-size: 13px !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 20px 20px 8px !important;
    }

    .nav-treeview {
        padding: 4px 0 4px 0 !important;
        margin: 0 12px 0 0 !important;
        border-right: 2px solid rgba(255,255,255,0.15);
        display: none;
    }
    /* Removed conflicting display:block rule to let jQuery slideToggle work */
    .nav-treeview .nav-item { margin: 1px 0; }
    .nav-treeview .nav-link {
        padding: 10px 16px 10px 16px !important;
        font-size: 15px !important;
        color: rgba(255,255,255,0.6) !important;
        border-radius: 8px !important;
    }
    .nav-treeview .nav-link:hover { color: #fff !important; background: rgba(255,255,255,0.08) !important; }
    .nav-treeview .nav-icon { font-size: 8px !important; }

    /* ===== CONTENT ===== */
    .content-wrapper {
        margin-right: 0 !important;
        margin-left: 0 !important;
        padding-right: var(--sidebar-width) !important;
        width: 100% !important;
        min-height: 100vh;
        background: #f0f2f5 !important;
        padding-top: var(--navbar-height);
        transition: padding-right 0.3s ease;
    }
    .main-footer {
        margin-right: 0 !important;
        padding-right: var(--sidebar-width) !important;
        margin-left: 0;
        width: 100% !important;
        transition: padding-right 0.3s ease;
    }

    .page-nav {
        position: sticky;
        top: 0;
        z-index: 100;
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        padding: 14px 20px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    }
    .page-title {
        font-size: var(--font-xl);
        font-weight: 700;
        color: #1a1a2e;
    }

    /* ===== CARDS & COMMON ===== */
    .card {
        border: none !important;
        border-radius: var(--radius) !important;
        box-shadow: var(--shadow) !important;
    }
    .card-header {
        border-radius: var(--radius) var(--radius) 0 0 !important;
        font-weight: 600;
    }
    .invoice {
        background: #fff;
        padding: 20px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    }
    .bold { font-weight: 700; }
    .bg-gry { background: #f8f9fa; }
    .required::after { content: " *"; color: #ef4444; }

    /* ===== BUTTONS ===== */
    .btn {
        border-radius: 10px !important;
        font-weight: 600 !important;
        font-size: var(--font-base) !important;
        padding: 10px 20px !important;
        transition: var(--transition);
    }
    .btn-lg, .btn-group-lg > .btn {
        padding: 14px 28px !important;
        font-size: var(--font-lg) !important;
    }

    /* ===== FORMS ===== */
    .form-control {
        border-radius: 10px !important;
        padding: 10px 16px !important;
        font-size: var(--font-base) !important;
        border: 2px solid #e5e7eb !important;
        height: auto !important;
        transition: var(--transition);
    }
    .form-control:focus {
        border-color: var(--sidebar-active) !important;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.15) !important;
    }
    .col-form-label {
        font-weight: 600;
        font-size: var(--font-base);
        color: #374151;
    }

    /* ===== TABLES ===== */
    .table { font-size: var(--font-base); }
    .table thead th {
        background: #f8f9fa;
        font-weight: 700;
        border-bottom: 2px solid #e5e7eb !important;
        padding: 14px 12px;
    }
    .table td { padding: 12px; vertical-align: middle; }

    /* ===== PRELOADER ===== */
    #preloading {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(255,255,255,0.85);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
    }
    #preloading.show { display: flex; }
    #preloading .spinner {
        width: 50px; height: 50px;
        border: 4px solid #e5e7eb;
        border-top-color: var(--sidebar-active);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ===== INFO BOXES ===== */
    .info-box {
        border-radius: var(--radius) !important;
        box-shadow: var(--shadow);
        min-height: 90px;
    }
    .info-box-icon { border-radius: var(--radius) 0 0 var(--radius) !important; width: 80px; }
    .info-box-text { font-size: 15px; font-weight: 600; }
    .info-box-number { font-size: 22px; font-weight: 800; }

    /* ===== MOBILE OVERLAY ===== */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1035;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .sidebar-overlay.show { display: block; opacity: 1; }

    /* ===== MOBILE FIRST ===== */
    @media (max-width: 991.98px) {
        .main-sidebar {
            transform: translateX(100%);
            box-shadow: none;
        }
        .main-sidebar.sidebar-open {
            transform: translateX(0);
            box-shadow: -4px 0 30px rgba(0,0,0,0.2);
        }
        .content-wrapper {
            margin-right: 0 !important;
            padding-right: 0 !important;
            width: 100% !important;
            padding-top: var(--navbar-height);
        }
        .main-header.navbar {
            right: 0 !important;
            width: 100% !important;
            padding: 0 12px !important;
            padding-right: 12px !important;
        }
        .main-footer {
            margin-right: 0 !important;
            padding-right: 0 !important;
            width: 100% !important;
        }
        .page-nav { padding: 10px 12px; }
        .page-title { font-size: var(--font-lg); }
        .container-fluid { padding: 0 10px; }
    }

    @media (max-width: 575.98px) {
        :root { --font-base: 15px; }
        .btn { padding: 10px 16px !important; }
        .info-box-number { font-size: 18px; }
        .table { font-size: 14px; }
    }

    /* ===== ACCESSIBILITY (Elderly-friendly) ===== */
    a, button, .nav-link, .dropdown-item { min-height: 44px; }
    .nav-sidebar > .nav-item > .nav-link { min-height: 48px; }
    :focus-visible {
        outline: 3px solid var(--sidebar-active) !important;
        outline-offset: 2px;
    }

    /* ===== BOOTSTRAP 4 RTL OVERRIDES ===== */
    /* Flip margin/padding utilities */
    .ml-auto { margin-right: auto !important; margin-left: 0 !important; }
    .mr-auto { margin-left: auto !important; margin-right: 0 !important; }
    .ml-1,.ml-2,.ml-3,.ml-4,.ml-5 { margin-right: inherit; margin-left: 0; }
    .mr-1,.mr-2,.mr-3,.mr-4,.mr-5 { margin-left: inherit; margin-right: 0; }
    .pl-0 { padding-right: 0 !important; padding-left: inherit !important; }
    .pr-0 { padding-left: 0 !important; padding-right: inherit !important; }
    .text-left { text-align: right !important; }
    .text-right { text-align: left !important; }
    .float-left { float: right !important; }
    .float-right { float: left !important; }

    /* Dropdown RTL */
    .dropdown-menu { text-align: right; }
    .dropdown-menu-left { right: 0; left: auto; }
    .dropdown-menu-right { right: auto; left: 0; }

    /* Modal RTL */
    .modal-header .close { margin: -1rem auto -1rem -1rem; }

    /* Alert RTL */
    .alert-dismissible { padding-right: 1.25rem; padding-left: 4rem; }
    .alert-dismissible .close { right: auto; left: 0; }

    /* Breadcrumb RTL */
    .breadcrumb-item + .breadcrumb-item::before { float: right; padding-left: 0.5rem; padding-right: 0; }

    /* Input group RTL */
    .input-group > .input-group-prepend > .btn,
    .input-group > .input-group-prepend > .input-group-text { border-radius: 0 10px 10px 0 !important; }
    .input-group > .input-group-append > .btn,
    .input-group > .input-group-append > .input-group-text { border-radius: 10px 0 0 10px !important; }
    .input-group > .form-control:first-child { border-radius: 0 10px 10px 0 !important; }
    .input-group > .form-control:last-child { border-radius: 10px 0 0 10px !important; }

    /* Custom select RTL */
    .custom-select { background-position: left 0.75rem center; padding: 0.375rem 0.75rem 0.375rem 1.75rem; }

    /* Checkbox/Radio RTL */
    .custom-control { padding-right: 1.5rem; padding-left: 0; }
    .custom-control-label::before,
    .custom-control-label::after { right: -1.5rem; left: auto; }

    /* Select2 RTL */
    .select2-container--default .select2-selection--single .select2-selection__arrow { right: auto; left: 1px; }
    .select2-container { direction: rtl; text-align: right; }
    .select2-search__field { direction: rtl; }

    /* Bootstrap-Select RTL */
    .bootstrap-select .dropdown-toggle .filter-option { text-align: right; }
    .bootstrap-select .dropdown-toggle::after { margin-right: auto; margin-left: 0; }

    /* DataTables RTL */
    .dataTables_wrapper { direction: rtl; }
    .dataTables_wrapper .dataTables_filter { float: right; text-align: right; }
    .dataTables_wrapper .dataTables_length { float: right; }
    .dataTables_wrapper .dataTables_info { float: right; text-align: right; }
    .dataTables_wrapper .dataTables_paginate { float: left; text-align: left; }
    .dataTables_wrapper .dataTables_processing { direction: rtl; }
    table.dataTable thead th, table.dataTable thead td { text-align: right; }

    /* Toastr RTL */
    .toast-top-left { top: 12px; right: 12px; left: auto; }
    #toast-container > div { direction: rtl; }

    /* Page content padding fix */
    .content-header { padding: 15px 20px; }
    .content { padding: 0 20px 20px; }
    section.content { padding: 0 20px 20px; }

    /* Fix AdminLTE sidebar-mini body class dynamic width */
    body.sidebar-collapse {
        --sidebar-width: 73px;
    }
    
    body.sidebar-collapse .nav-sidebar .nav-link p,
    body.sidebar-collapse .brand-text,
    body.sidebar-collapse .user-panel .info {
        display: none !important;
    }
    
    body.sidebar-collapse .brand-link {
        justify-content: center;
        padding: 0 !important;
    }
    
    body.sidebar-collapse .nav-sidebar .nav-item {
        margin: 2px 0;
    }
    
    body.sidebar-collapse .nav-sidebar .nav-link {
        justify-content: center;
        padding: 12px !important;
    }
    
    body.sidebar-collapse .nav-sidebar .nav-icon {
        margin: 0 !important;
        font-size: 20px !important;
    }

    .sidebar-mini .content-wrapper,
    .sidebar-mini .main-header,
    .sidebar-mini .main-footer {
        /* Transition handled by variable change on body */
    }
    </style>
</head>
<body class="sidebar-mini" dir="rtl">
<div class="wrapper">

    <!-- Preloading overlay -->
    <div id="preloading"><div class="spinner"></div></div>

    <!-- Mobile sidebar overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" href="#" role="button" id="sidebarToggle" aria-label="القائمة">
                    <i class="fas fa-bars" style="font-size:22px;"></i>
                </a>
            </li>
            <li class="nav-item d-none d-md-block">
                <span class="nav-link" style="font-weight:600;color:#6b7280 !important;">
                    <?= $page_title ?? '' ?>
                </span>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="user-menu-btn" data-toggle="dropdown" href="#" aria-haspopup="true" aria-expanded="false">
                    <div class="user-avatar"><?= mb_substr($userFirstName ?: $userName, 0, 1, 'UTF-8') ?></div>
                    <span class="d-none d-sm-inline"><?= $userName ?></span>
                    <i class="fas fa-chevron-down" style="font-size:11px;color:#9ca3af;"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-left">
                    <a href="emp-info" class="dropdown-item"><i class="fas fa-user-circle"></i> الملف الشخصي</a>
                    <a href="emp-setting" class="dropdown-item"><i class="fas fa-cog"></i> الإعدادات</a>
                    <div class="dropdown-divider"></div>
                    <a href="?logout=1" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt"></i> تسجيل خروج</a>
                </div>
            </li>
        </ul>
    </nav>

    <!-- Main Sidebar -->
    <aside class="main-sidebar elevation-4" id="mainSidebar">
        <a href="Hrdashboard" class="brand-link">
            <span class="brand-text"><b>Vision</b> HR</span>
        </a>
        <div class="sidebar">
            <nav class="mt-1 pb-4">
                <ul class="nav nav-pills nav-sidebar flex-column" role="menu">

                    <li class="nav-item">
                        <a href="Hrdashboard" class="nav-link <?= ($page ?? '') == 'Hrdashboard' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-fingerprint"></i>
                            <p>الحضور والانصراف</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="dashboard-emp" class="nav-link <?= ($page ?? '') == 'dashboard-emp' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>لوحة المعلومات</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="emp-info" class="nav-link <?= ($page ?? '') == 'emp-info' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-id-card"></i>
                            <p>معلوماتي</p>
                        </a>
                    </li>

                    <?php if($User->userIsAdmin() || $User->isAllowedPerm(['عرض موظف'], 'HR')): ?>
                    <li class="nav-header">إدارة الموارد البشرية</li>

                    <li class="nav-item">
                        <a href="employer-list" class="nav-link <?= ($page ?? '') == 'employer-list' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-users"></i>
                            <p>الموظفون</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="reveal-attendance" class="nav-link <?= ($page ?? '') == 'reveal-attendance' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-clock"></i>
                            <p>كشف الحضور</p>
                        </a>
                    </li>

                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-money-bill-wave"></i>
                            <p>المالية <i class="fas fa-angle-left right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="Benefits-list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>التعويضات</p></a></li>
                            <li class="nav-item"><a href="deductions-list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>الخصومات</p></a></li>
                            <li class="nav-item"><a href="incentive-list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>الحوافز</p></a></li>
                            <li class="nav-item"><a href="EmpAdvances-list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>السلف</p></a></li>
                            <li class="nav-item"><a href="Issuing-salaries" class="nav-link"><i class="far fa-circle nav-icon"></i><p>إصدار الرواتب</p></a></li>
                        </ul>
                    </li>

                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-calendar-alt"></i>
                            <p>الإجازات <i class="fas fa-angle-left right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="leaveRequest-list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>طلبات الإجازات</p></a></li>
                            <li class="nav-item"><a href="leaveClassficate-list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>تصنيفات الإجازات</p></a></li>
                        </ul>
                    </li>

                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-file-contract"></i>
                            <p>العقود والترقيات <i class="fas fa-angle-left right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="contractRenewal-list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>تجديد العقود</p></a></li>
                            <li class="nav-item"><a href="promotion-list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>الترقيات</p></a></li>
                        </ul>
                    </li>

                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-user-slash"></i>
                            <p>إنهاء الخدمة <i class="fas fa-angle-left right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="resignation-list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>الاستقالات</p></a></li>
                            <li class="nav-item"><a href="dismissal-list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>الفصل</p></a></li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a href="report-center" class="nav-link <?= ($page ?? '') == 'report-center' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>التقارير</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="hr-setting" class="nav-link <?= ($page ?? '') == 'hr-setting' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>الإعدادات</p>
                        </a>
                    </li>
                    <?php endif; ?>

                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
