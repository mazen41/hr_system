<?php
// header.php is included by page files which are loaded via index.php (router)
// If loaded directly (not through router), bootstrap manually
if (!isset($connect_pdo)) {
    // Secure session cookie settings for HTTPS - only if session hasn't started
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_secure', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        session_start();
    }
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
<?php
// UI direction: default rtl, allow override via ?dir=rtl|ltr stored in session
$uiDir = $_SESSION['ui_dir'] ?? 'rtl';
if (isset($_GET['dir'])) {
    $d = $_GET['dir'];
    if ($d === 'rtl' || $d === 'ltr') {
        $_SESSION['ui_dir'] = $d;
        $uiDir = $d;
    }
}
$uiLang = ($uiDir === 'rtl') ? 'ar' : 'en';
$currentPage = $page ?? basename($_SERVER['SCRIPT_NAME'], '.php');
$siteUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'hr.gt-academy.com');
$canonicalUrl = $siteUrl . strtok($_SERVER['REQUEST_URI'] ?? ('/' . $currentPage), '?');
$metaTitle = trim((!empty($page_title) ? $page_title . ' - ' : '') . 'Vision HR');
$metaDescription = !empty($screen)
    ? trim(strip_tags((string) $screen)) . ' - Vision HR'
    : 'Vision HR human resources platform for attendance, payroll, leave requests, employee services, and HR operations.';
$isPerformanceCriticalPage = in_array($currentPage, ['dashboard-emp'], true);
$loadSharedHeavyPlugins = !$isPerformanceCriticalPage;
$loadSharedChartJs = in_array($currentPage, ['dashboard', 'dashboard-emp', 'employer-dashboard-modern'], true);
?>
<html lang="<?= $uiLang ?>" dir="<?= $uiDir ?>">

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0d21a5">
    <meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image"
        content="<?= htmlspecialchars($siteUrl . '/dist/img/brand/logo-icon.png', ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image"
        content="<?= htmlspecialchars($siteUrl . '/dist/img/brand/logo-icon.png', ENT_QUOTES, 'UTF-8') ?>">
    <!-- PWA Meta Tags -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Vision HR">
    <meta name="application-name" content="Vision HR">
    <meta name="msapplication-TileColor" content="#0d21a5">
    <meta name="msapplication-TileImage" content="dist/img/brand/logo-icon.png">
    <title><?= !empty($page_title) ? $page_title . ' - ' : '' ?>Vision HR</title>
    <link rel="icon" type="image/png" href="dist/img/brand/logo-icon.png">
    <link rel="preload" as="image" href="dist/img/brand/logo-icon.png" fetchpriority="high">
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="dist/img/brand/logo-icon.png">
    <link rel="apple-touch-icon" sizes="152x152" href="dist/img/brand/logo-icon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="dist/img/brand/logo-icon.png">
    <link rel="apple-touch-icon" sizes="167x167" href="dist/img/brand/logo-icon.png">

    <!-- PERFORMANCE: Preconnect to CDNs -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <?php if (!empty($loadSharedHeavyPlugins)): ?>
        <link rel="preconnect" href="https://cdn.datatables.net" crossorigin>
    <?php endif; ?>

    <!-- CRITICAL CSS: Bootstrap + AdminLTE core (render-blocking but essential) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">

    <!-- DEFERRED CSS: Non-critical styles loaded async -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap"
        media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" media="print"
        onload="this.media='all'">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" media="print"
        onload="this.media='all'">
    <?php if (!empty($loadSharedHeavyPlugins)): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" media="print"
            onload="this.media='all'">
        <link rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.18/dist/css/bootstrap-select.min.css" media="print"
            onload="this.media='all'">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" media="print"
            onload="this.media='all'">
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css"
            media="print" onload="this.media='all'">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css" media="print"
            onload="this.media='all'">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" media="print"
            onload="this.media='all'">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"
            media="print" onload="this.media='all'">
    <?php endif; ?>

    <!-- Local CSS (versioned, deferred) -->
    <link rel="stylesheet" href="dist/css/utilities.css?v=6.1" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="dist/css/brand.css?v=6.1" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="dist/css/rtl-fixes.css?v=6.1" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="dist/css/responsive-global.css?v=6.1" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="dist/css/ui-fixes-v3.css?v=6.1" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="dist/css/responsive-fixes.css?v=6.1" media="print" onload="this.media='all'">

    <!-- Fallback for browsers without JS -->
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <link rel="stylesheet" href="dist/css/brand.css?v=6.1">
    </noscript>

    <!-- REMOVED: Tailwind CDN (huge blocking resource ~300KB) - using utility classes from brand.css instead -->

    <!-- Alpine.js deferred -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <!-- Global UX helpers deferred -->
    <script src="dist/js/app-ux.js?v=6.1" defer></script>

    <!-- Inline Critical Fixes to bypass Cache v6.0 -->
    <style>
        /* FORCE SIDEBAR HEIGHT & COLOR */
        .main-sidebar .sidebar {
            height: calc(100vh - 60px) !important;
            min-height: calc(100vh - 60px) !important;
            padding-bottom: 0 !important;
            margin-bottom: 0 !important;
            background-color: #0d21a5 !important;
            overflow-y: auto !important;
        }

        /* FIX DROPDOWN CENTERING */
        .dropdown-menu {
            text-align: center !important;
        }

        .dropdown-item {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            width: 100% !important;
        }

        /* MOBILE: FORCE HIDE SIDEBAR TO PREVENT BLEEDING */
        @media (max-width: 991.98px) {

            /* Target ID for higher specificity */
            #mainSidebar {
                display: none !important;
                width: 0 !important;
                transform: translateX(100%) !important;
            }

            /* Only show when explicitly open */
            .sidebar-open #mainSidebar {
                display: block !important;
                width: 260px !important;
                transform: translateX(0) !important;
            }

            /* Kill the mini sidebar style on mobile completely */
            .sidebar-mini.sidebar-collapse .main-sidebar {
                width: 0 !important;
                transform: translateX(100%) !important;
            }
        }
    </style>

    <!-- AGGRESSIVE JS FIXER -->
    <script>
        (function () {
            function fixMobileSidebar() {
                if (!document.body) return; // Guard against early execution
                if (window.innerWidth < 992) {
                    // Remove sidebar-mini on mobile to prevent icon bleeding
                    document.body.classList.remove('sidebar-mini');
                    document.body.classList.remove('sidebar-collapse');

                    // Force hide if not open
                    var sidebar = document.getElementById('mainSidebar');
                    if (sidebar && !document.body.classList.contains('sidebar-open')) {
                        sidebar.style.display = 'none';
                        sidebar.style.width = '0';
                    }
                }
            }

            // Only run after DOM is ready, not immediately
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', fixMobileSidebar);
            } else {
                fixMobileSidebar();
            }
            window.addEventListener('load', fixMobileSidebar);
            window.addEventListener('resize', fixMobileSidebar);
        })();
    </script>

    <?php if (strpos($_SERVER['REQUEST_URI'], 'ess-') !== false || $page === 'ess-dashboard'): ?>
        <link rel="stylesheet" href="dist/css/ess-enhanced.css?v=6.1" media="print" onload="this.media='all'">
    <?php endif; ?>

    <style>
        :root {
            --sidebar-width: 220px;
            --sidebar-bg: #0d21a5;
            --sidebar-hover: #3d4fb8;
            --sidebar-active: #6d7dcb;
            --navbar-height: 60px;
            --font-base: 16px;
            --font-lg: 18px;
            --font-xl: 20px;
            --radius: 12px;
            --shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
        }

        html {
            font-size: var(--font-base);
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden !important;
            /* Prevent horizontal scroll */
            overflow-y: auto !important;
            /* Allow vertical scroll */
        }

        body {
            text-align: right;
            background: #f0f2f5 !important;
            color: #1a1a2e;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
        }

        .wrapper {
            overflow-x: hidden;
            width: 100%;
            position: relative;
        }

        /* ===== NAVBAR ===== */
        .main-header.navbar {
            height: var(--navbar-height);
            background: #fff !important;
            border: none !important;
            box-shadow: var(--shadow);
            z-index: 1020;
            padding: 0 20px;
            /* Padding-right accounts for sidebar width + 20px padding */
            padding-right: calc(var(--sidebar-width) + 16px) !important;
            direction: rtl;
            position: fixed;
            top: 0;
            right: 0 !important;
            left: 0 !important;
            width: 100% !important;
            margin: 0 !important;
            transition: padding-right 0.3s ease;
        }

        /* Mobile navbar sizing: real touch targets and spacing */
        @media (max-width: 991.98px) {
            :root {
                --navbar-height: 56px;
            }

            .main-header.navbar {
                position: sticky;
                top: 0;
                padding-right: 12px !important;
                padding-left: 12px !important;
            }

            .main-header .navbar-nav>.nav-item>.nav-link {
                min-width: 44px;
                min-height: 44px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 8px 10px !important;
                font-size: 16px !important;
            }

            .main-header .nav-link i,
            .main-header .nav-link .fas,
            .main-header .nav-link .far,
            .main-header .nav-link .bi {
                font-size: 18px;
                line-height: 1;
            }

            .main-header .user-menu-btn {
                padding: 6px 10px 6px 10px;
                gap: 8px;
            }

            .main-header .user-avatar {
                width: 28px;
                height: 28px;
                font-size: 13px;
            }
        }

        .main-header .navbar-nav {
            direction: rtl;
        }

        .main-header .nav-link {
            font-size: var(--font-lg) !important;
            color: #333 !important;
            padding: 10px 14px !important;
        }

        .main-header .nav-link:hover {
            color: var(--sidebar-active) !important;
        }

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

        .main-header .user-menu-btn:hover {
            background: #e2e6ea;
            text-decoration: none;
            color: #333;
        }

        .main-header .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--sidebar-active);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
        }

        .main-header .dropdown-menu {
            border: none;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            border-radius: var(--radius);
            padding: 8px;
            min-width: 200px;
            direction: rtl;
            text-align: right;
            z-index: 99999 !important;
            position: absolute !important;
        }

        body>.dropdown-menu {
            z-index: 99999 !important;
            position: fixed !important;
        }

        .main-header .dropdown-item {
            border-radius: 8px;
            padding: 10px 16px;
            font-size: var(--font-base);
            font-weight: 500;
        }

        .main-header .dropdown-item:hover {
            background: #f0f2f5;
        }

        .main-header .dropdown-item i {
            margin-left: 10px;
            width: 20px;
            text-align: center;
        }

        /* ===== SIDEBAR ===== */
        .main-sidebar,
        #mainSidebar {
            background: var(--sidebar-bg) !important;
            width: var(--sidebar-width) !important;
            right: 0 !important;
            left: auto !important;
            top: 0;
            bottom: 0;
            position: fixed;
            height: 100vh !important;
            height: 100dvh !important;
            min-height: 100% !important;
            max-height: none !important;
            z-index: 2147483647 !important;
            overflow: hidden !important;
            transition: transform 0.3s ease, width 0.3s ease;
            box-shadow: -4px 0 20px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            border: none !important;
        }

        .sidebar {
            flex: 1 1 auto;
            padding: 0 !important;
            margin: 0 !important;
            overflow-y: auto !important;
            overflow-x: hidden;
            height: calc(100vh - 70px) !important;
            min-height: calc(100vh - 70px) !important;
            width: 100% !important;
            display: block;
            background: var(--sidebar-bg) !important;
        }

        .sidebar nav {
            padding: 0 2px !important;
            margin: 0 !important;
        }

        .sidebar .nav-sidebar {
            padding-bottom: 0 !important;
            margin-bottom: 0 !important;
        }

        .main-sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .main-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }

        .brand-link {
            display: flex !important;
            align-items: center;
            justify-content: center;
            height: 70px;
            background: rgba(255, 255, 255, 0.05) !important;
            border-bottom: none !important;
            padding: 12px 8px !important;
            margin: 0;
            border-radius: 0 0 16px 16px;
        }

        .brand-link .brand-logo {
            max-height: 40px !important;
            width: auto !important;
            filter: brightness(0) invert(1) !important;
            transition: all 0.3s ease;
        }

        .brand-link:hover .brand-logo {
            transform: scale(1.05);
        }

        .nav-sidebar {
            padding-top: 10px;
        }

        .nav-sidebar .nav-item {
            margin: 0 0 4px 0;
        }

        .nav-sidebar>.nav-item>.nav-link {
            border-radius: 6px !important;
            padding: 10px 8px 10px 6px !important;
            color: rgba(255, 255, 255, 0.85) !important;
            font-size: 15px !important;
            /* Better readability */
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .nav-sidebar>.nav-item>.nav-link:hover {
            background: rgba(255, 255, 255, 0.08) !important;
            color: #fff !important;
            transform: translateX(-2px);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .nav-sidebar>.nav-item>.nav-link:hover .nav-icon {
            color: #fff !important;
            opacity: 1 !important;
        }

        .nav-sidebar>.nav-item>.nav-link.active {
            background: rgba(255, 255, 255, 0.15) !important;
            color: #fff !important;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .nav-sidebar>.nav-item>.nav-link.active .nav-icon {
            color: #fff !important;
            opacity: 1 !important;
        }

        .nav-sidebar .nav-icon {
            font-size: 17px !important;
            width: 18px;
            text-align: center;
            flex-shrink: 0;
            opacity: 1 !important;
            color: rgba(255, 255, 255, 0.9) !important;
        }

        .nav-sidebar .nav-link p {
            margin: 0 !important;
            text-align: right;
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
            line-height: 1.2;
            flex: 1;
        }

        .nav-sidebar .nav-link p .right {
            float: left;
            margin-top: 3px;
            transition: transform 0.2s ease;
        }

        .nav-item.has-treeview.menu-open>.nav-link p .right {
            transform: rotate(-90deg);
        }

        .nav-header {
            color: rgba(255, 255, 255, 0.6) !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 16px 8px 8px !important;
            opacity: 0.8;
            margin-bottom: 5px;
        }

        .nav-treeview {
            padding: 4px 0 !important;
            margin: 0 8px 0 0 !important;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .nav-treeview .nav-item {
            margin: 2px 4px;
        }

        .nav-treeview .nav-link {
            padding: 8px 10px !important;
            font-size: 14px !important;
            color: rgba(255, 255, 255, 0.7) !important;
            border-radius: 6px !important;
            min-height: 36px !important;
        }

        .nav-treeview .nav-link:hover {
            color: #fff !important;
            background: rgba(255, 255, 255, 0.05) !important;
            transform: none !important;
        }

        .nav-treeview .nav-link.active {
            background: rgba(255, 255, 255, 0.15) !important;
            color: #fff !important;
        }

        .nav-treeview .nav-icon {
            font-size: 10px !important;
            opacity: 0.7;
        }

        /* Treeview parent spacing */
        .nav-item.has-treeview {
            position: relative;
            overflow: visible;
        }

        .nav-item.has-treeview>.nav-link {
            cursor: pointer;
        }

        /* Treeview initial state - jQuery handles animation */
        .nav-item.has-treeview>.nav-treeview {
            display: none;
        }

        /* ===== CONTENT ===== */
        .content-wrapper {
            margin-right: var(--sidebar-width) !important;
            margin-left: 0 !important;
            padding: 0 !important;
            width: auto !important;
            min-height: calc(100vh - var(--navbar-height));
            background: #f8f9fa !important;
            margin-top: var(--navbar-height);
            transition: margin-right 0.3s ease;
            overflow-x: hidden;
            overflow-y: visible;
        }

        /* Page header styling */
        .content-header,
        .page-nav {
            background: #fff !important;
            padding: 12px 20px !important;
            margin: 0 !important;
            border-bottom: 1px solid #e5e7eb;
            position: relative !important;
            z-index: 10;
        }

        section.content {
            padding: 15px !important;
        }

        .main-footer {
            margin-right: var(--sidebar-width) !important;
            margin-left: 0 !important;
            padding-right: 0 !important;
            width: auto !important;
            transition: margin-right 0.3s ease;
        }

        .page-nav {
            position: relative;
            z-index: 100;
            background: #fff !important;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 20px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
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

        .bold {
            font-weight: 700;
        }

        .bg-gry {
            background: #f8f9fa;
        }

        .required::after {
            content: " *";
            color: #ef4444;
        }

        /* ===== BUTTONS ===== */
        .btn {
            border-radius: 10px !important;
            font-weight: 600 !important;
            font-size: var(--font-base) !important;
            padding: 10px 20px !important;
            transition: var(--transition);
        }

        .btn-lg,
        .btn-group-lg>.btn {
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
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
        }

        .col-form-label {
            font-weight: 600;
            font-size: var(--font-base);
            color: #374151;
        }

        /* ===== TABLES ===== */
        .table {
            font-size: var(--font-base);
        }

        .table thead th {
            background: #f8f9fa;
            font-weight: 700;
            border-bottom: 2px solid #e5e7eb !important;
            padding: 14px 12px;
        }

        .table td {
            padding: 12px;
            vertical-align: middle;
        }

        /* ===== PRELOADER ===== */
        #preloading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.85);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }

        #preloading.show {
            display: flex;
        }

        #preloading .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e5e7eb;
            border-top-color: var(--sidebar-active);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ===== INFO BOXES ===== */
        .info-box {
            border-radius: var(--radius) !important;
            box-shadow: var(--shadow);
            min-height: 90px;
        }

        .info-box-icon {
            border-radius: var(--radius) 0 0 var(--radius) !important;
            width: 80px;
        }

        .info-box-text {
            font-size: 15px;
            font-weight: 600;
        }

        .info-box-number {
            font-size: 22px;
            font-weight: 800;
        }

        /* ===== MOBILE OVERLAY ===== */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1035;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }

        /* ===== MOBILE FIRST ===== */
        @media (max-width: 991.98px) {

            /* FORCE hide sidebar completely on mobile when closed */
            .main-sidebar,
            body.sidebar-mini .main-sidebar,
            body.sidebar-collapse .main-sidebar,
            body.sidebar-mini.sidebar-collapse .main-sidebar {
                transform: translateX(100%) !important;
                box-shadow: none !important;
                width: 280px !important;
                z-index: 1040 !important;
                visibility: hidden !important;
            }

            .main-sidebar.sidebar-open,
            body.sidebar-mini .main-sidebar.sidebar-open,
            body.sidebar-collapse .main-sidebar.sidebar-open,
            body.sidebar-mini.sidebar-collapse .main-sidebar.sidebar-open {
                transform: translateX(0) !important;
                box-shadow: -4px 0 30px rgba(0, 0, 0, 0.2) !important;
                visibility: visible !important;
            }

            .content-wrapper,
            body.sidebar-mini .content-wrapper,
            body.sidebar-collapse .content-wrapper {
                margin-right: 0 !important;
                margin-left: 0 !important;
                padding-right: 0 !important;
                padding-left: 0 !important;
                width: 100% !important;
                padding-top: 0 !important;
                margin-top: var(--navbar-height) !important;
                min-height: calc(100vh - var(--navbar-height));
                overflow-x: hidden;
            }

            .main-header.navbar,
            body.sidebar-mini .main-header.navbar,
            body.sidebar-collapse .main-header.navbar {
                right: 0 !important;
                left: 0 !important;
                width: 100% !important;
                margin: 0 !important;
                margin-right: 0 !important;
                margin-left: 0 !important;
                padding: 0 15px !important;
                height: var(--navbar-height);
                z-index: 1030;
            }

            .main-footer,
            body.sidebar-mini .main-footer,
            body.sidebar-collapse .main-footer {
                margin-right: 0 !important;
                margin-left: 0 !important;
                padding-right: 0 !important;
                width: 100% !important;
            }

            .page-nav {
                padding: 10px 15px;
                background: #fff;
                border-bottom: 1px solid #e5e7eb;
                width: 100%;
            }

            .page-title {
                font-size: var(--font-lg);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .container-fluid {
                padding-left: 15px;
                padding-right: 15px;
                max-width: 100%;
                overflow-x: hidden;
                /* Contain row overflow */
            }

            .row {
                margin-left: -10px;
                margin-right: -10px;
            }

            .col-1,
            .col-2,
            .col-3,
            .col-4,
            .col-5,
            .col-6,
            .col-7,
            .col-8,
            .col-9,
            .col-10,
            .col-11,
            .col-12,
            .col,
            .col-auto,
            .col-sm-1,
            .col-sm-2,
            .col-sm-3,
            .col-sm-4,
            .col-sm-5,
            .col-sm-6,
            .col-sm-7,
            .col-sm-8,
            .col-sm-9,
            .col-sm-10,
            .col-sm-11,
            .col-sm-12,
            .col-sm,
            .col-md-1,
            .col-md-2,
            .col-md-3,
            .col-md-4,
            .col-md-5,
            .col-md-6,
            .col-md-7,
            .col-md-8,
            .col-md-9,
            .col-md-10,
            .col-md-11,
            .col-md-12,
            .col-md,
            .col-lg-1,
            .col-lg-2,
            .col-lg-3,
            .col-lg-4,
            .col-lg-5,
            .col-lg-6,
            .col-lg-7,
            .col-lg-8,
            .col-lg-9,
            .col-lg-10,
            .col-lg-11,
            .col-lg-12,
            .col-lg,
            .col-xl-1,
            .col-xl-2,
            .col-xl-3,
            .col-xl-4,
            .col-xl-5,
            .col-xl-6,
            .col-xl-7,
            .col-xl-8,
            .col-xl-9,
            .col-xl-10,
            .col-xl-11,
            .col-xl-12,
            .col-xl {
                padding-left: 10px;
                padding-right: 10px;
            }

            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1035;
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            .sidebar-overlay.show {
                display: block;
                opacity: 1;
            }

            .nav-sidebar .nav-link {
                padding: 10px 16px !important;
            }

            .nav-sidebar .nav-icon {
                font-size: 16px !important;
                width: 20px;
            }
        }

        @media (min-width: 992px) and (max-width: 1366.98px) {
            :root {
                --sidebar-width: 200px;
                --font-base: 15px;
                --font-lg: 17px;
                --font-xl: 18px;
            }

            .main-header.navbar {
                padding-right: calc(var(--sidebar-width) + 12px) !important;
            }

            .brand-link {
                padding: 12px !important;
            }

            .nav-sidebar>.nav-item>.nav-link {
                gap: 10px;
                padding: 9px 10px !important;
                font-size: 14px !important;
            }

            .nav-sidebar .nav-icon {
                width: 20px;
                font-size: 16px !important;
            }
        }

        @media (max-width: 575.98px) {
            :root {
                --font-base: 15px;
                --navbar-height: 60px;
            }

            .main-sidebar {
                width: 260px;
            }

            /* Fix sidebar menu spacing */
            .main-sidebar .sidebar {
                padding: 0 !important;
            }

            .main-sidebar .sidebar nav {
                padding: 0 8px !important;
            }

            .nav-sidebar {
                padding: 0 !important;
                margin: 0 !important;
            }

            .nav-sidebar .nav-item {
                margin: 2px 0 !important;
            }

            .nav-sidebar>.nav-item>.nav-link {
                padding: 10px 12px !important;
                margin: 0 !important;
                border-radius: 6px !important;
            }

            .nav-header {
                padding: 15px 12px 8px !important;
                margin: 0 !important;
            }

            .main-header.navbar {
                padding: 0 10px !important;
                height: 60px;
            }

            .page-nav {
                padding: 8px 10px;
            }

            .page-title {
                font-size: 1.1rem;
            }

            .container-fluid {
                padding-left: 10px;
                padding-right: 10px;
            }

            .btn {
                padding: 8px 12px !important;
                font-size: 0.9rem;
                min-height: 36px;
            }

            .info-box-number {
                font-size: 18px;
            }

            .table {
                font-size: 14px;
            }

            .sidebar-overlay {
                z-index: 1045;
            }

            .nav-sidebar .nav-link {
                padding: 8px 12px !important;
                font-size: 0.9rem;
            }

            .nav-sidebar .nav-icon {
                font-size: 14px !important;
                width: 18px;
            }
        }

        @media (max-width: 480px) {
            .main-sidebar {
                width: 240px;
            }

            .page-title {
                font-size: 1rem;
            }

            .btn {
                padding: 6px 10px !important;
                font-size: 0.85rem;
                min-height: 32px;
            }

            .container-fluid {
                padding: 0 6px;
            }

            .nav-sidebar .nav-link {
                padding: 6px 10px !important;
                font-size: 0.85rem;
            }

            .nav-sidebar .nav-icon {
                font-size: 12px !important;
                width: 16px;
            }
        }

        /* Desktop Sidebar Text Space Override */
        @media (min-width: 992px) {

            .main-sidebar,
            #mainSidebar {
                width: 232px !important;
            }

            .sidebar nav,
            .main-sidebar .sidebar nav {
                padding: 0 1px !important;
            }

            .brand-link {
                padding: 10px 6px !important;
            }

            .nav-sidebar>.nav-item>.nav-link {
                padding: 10px 6px 10px 4px !important;
                gap: 6px !important;
            }

            .nav-sidebar .nav-icon {
                width: 16px !important;
                min-width: 16px !important;
                margin-left: 0 !important;
            }

            .nav-sidebar .nav-link p {
                min-width: 0 !important;
                white-space: normal !important;
                overflow: visible !important;
                text-overflow: clip !important;
                line-height: 1.15 !important;
            }

            .nav-sidebar .nav-link p .right {
                margin-right: 4px !important;
                margin-left: 0 !important;
            }

            .nav-header {
                padding: 14px 6px 6px !important;
            }

            .nav-treeview {
                margin: 0 6px 0 0 !important;
            }

            .nav-treeview .nav-link {
                padding: 8px 8px !important;
            }
        }

        /* ===== ACCESSIBILITY ===== */
        a,
        button,
        .dropdown-item {
            min-height: 44px;
        }

        .nav-sidebar>.nav-item>.nav-link {
            min-height: 46px;
        }

        .nav-treeview .nav-link {
            min-height: 38px !important;
        }

        :focus-visible {
            outline: 3px solid var(--sidebar-active) !important;
            outline-offset: 2px;
        }

        /* ===== BOOTSTRAP 4 RTL OVERRIDES ===== */
        /* Flip margin/padding utilities */
        .ml-auto {
            margin-right: auto !important;
            margin-left: 0 !important;
        }

        .mr-auto {
            margin-left: auto !important;
            margin-right: 0 !important;
        }

        .ml-1,
        .ml-2,
        .ml-3,
        .ml-4,
        .ml-5 {
            margin-right: inherit;
            margin-left: 0;
        }

        .mr-1,
        .mr-2,
        .mr-3,
        .mr-4,
        .mr-5 {
            margin-left: inherit;
            margin-right: 0;
        }

        .pl-0 {
            padding-right: 0 !important;
            padding-left: inherit !important;
        }

        .pr-0 {
            padding-left: 0 !important;
            padding-right: inherit !important;
        }

        .text-left {
            text-align: right !important;
        }

        .text-right {
            text-align: left !important;
        }

        .float-left {
            float: right !important;
        }

        .float-right {
            float: left !important;
        }

        /* Dropdown RTL */
        .dropdown-menu {
            text-align: center !important;
            z-index: 1070;
        }

        .dropdown-menu-left {
            right: 0;
            left: auto;
        }

        .dropdown-menu-right {
            right: auto;
            left: 0;
        }

        /* CENTER ALL DROPDOWN ITEMS */
        .navbar .dropdown-menu .dropdown-item,
        .notification-dropdown .dropdown-item,
        .dropdown-menu .dropdown-item {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            text-align: center !important;
            width: 100%;
            gap: 8px;
        }

        .notification-dropdown .dropdown-header,
        .dropdown-menu .dropdown-header {
            text-align: center !important;
            justify-content: center !important;
            display: flex !important;
        }

        /* Fix AdminLTE layout - RTL Specific */
        @media (min-width: 992px) {
            body:not(.sidebar-collapse) .main-sidebar {
                width: var(--sidebar-width) !important;
                transform: translateX(0) !important;
                right: 0 !important;
                left: auto !important;
                z-index: 1050;
            }

            body:not(.sidebar-collapse) .content-wrapper,
            body:not(.sidebar-collapse) .main-footer {
                margin-right: var(--sidebar-width) !important;
                margin-left: 0 !important;
                padding-right: 0 !important;
            }

            body:not(.sidebar-collapse) .main-header.navbar {
                margin-right: var(--sidebar-width) !important;
                margin-left: 0 !important;
                width: calc(100% - var(--sidebar-width)) !important;
                z-index: 1040;
            }

            body.sidebar-collapse .main-sidebar {
                width: 0 !important;
                transform: translateX(100%) !important;
            }

            body.sidebar-collapse .content-wrapper,
            body.sidebar-collapse .main-header.navbar,
            body.sidebar-collapse .main-footer {
                margin-right: 0 !important;
                margin-left: 0 !important;
                width: 100% !important;
            }
        }

        /* Mobile Sidebar Specifics */
        @media (max-width: 991.98px) {
            .main-sidebar {
                right: 0 !important;
                left: auto !important;
                transform: translateX(100%);
                transition: transform 0.3s ease-in-out;
                z-index: 1060;
                width: 260px !important;
            }

            .sidebar-open .main-sidebar {
                transform: translateX(0);
            }

            .content-wrapper,
            .main-header.navbar,
            .main-footer {
                margin-right: 0 !important;
                margin-left: 0 !important;
                width: 100% !important;
            }

            .sidebar-overlay {
                z-index: 1055;
            }
        }

        /* VISUAL FAILSAFE: Force sidebar background - DESKTOP ONLY */
        @media (min-width: 992px) {
            .main-sidebar::before {
                content: '';
                position: fixed;
                top: 0;
                bottom: 0;
                right: 0;
                width: var(--sidebar-width);
                background-color: var(--sidebar-bg) !important;
                z-index: -1;
                pointer-events: none;
                height: 100vh;
                min-height: 100%;
                display: block;
            }
        }

        /* Hide pseudo-element on mobile completely */
        @media (max-width: 991.98px) {
            .main-sidebar::before {
                display: none !important;
            }

            .sidebar-bg-fix {
                display: none !important;
            }
        }

        /* Ensure variable updates in media queries for the fix element */
        @media (max-width: 575.98px) {
            .sidebar-bg-fix {
                width: 260px;
            }
        }

        @media (max-width: 480px) {
            .sidebar-bg-fix {
                width: 240px;
            }
        }
    </style>
</head>

<body class="sidebar-mini layout-fixed" dir="<?= htmlspecialchars($uiDir, ENT_QUOTES, 'UTF-8') ?>"
    data-page="<?= htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8') ?>"
    data-perf-profile="<?= $isPerformanceCriticalPage ? 'critical' : 'default' ?>">

    <div class="wrapper">

        <!-- Preloading overlay -->
        <div id="preloading">
            <div class="spinner"></div>
        </div>

        <!-- Mobile sidebar overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item d-none d-md-block">
                    <span class="nav-link" style="font-weight:600;color:#6b7280 !important;">
                        <?= $page_title ?? '' ?>
                    </span>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <!-- Sidebar Toggle (Right side, near sidebar) -->
                <li class="nav-item">
                    <a class="nav-link" href="#" role="button" id="sidebarToggle" aria-label="القائمة"
                        aria-controls="mainSidebar" aria-expanded="false">
                        <i class="fas fa-bars" style="font-size:20px;"></i>
                    </a>
                </li>
                <!-- Notification Bell -->
                <li class="nav-item dropdown" id="notificationDropdown">
                    <a class="nav-link notification-bell" data-toggle="dropdown" href="#" aria-label="الإشعارات"
                        aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-bell" style="font-size:20px;color:#6b7280;"></i>
                        <span class="notification-badge" id="notifBadge" style="display:none;"
                            aria-live="polite">0</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-left notification-dropdown"
                        style="width:320px;max-width:90vw;max-height:400px;overflow-y:auto;text-align:center !important;">
                        <div class="dropdown-header d-flex justify-content-between align-items-center"
                            style="justify-content:center !important;text-align:center !important;">
                            <strong>الإشعارات</strong>
                            <a href="#" onclick="markAllNotificationsRead();return false;"
                                class="text-primary small">تحديد الكل كمقروء</a>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div id="notificationItems">
                            <div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i></div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="<?= $User->userIsEmployee() ? 'ess-notifications' : 'admin-notifications' ?>"
                            class="dropdown-item text-center text-primary"
                            style="text-align:center !important;justify-content:center !important;display:flex !important;">
                            <i class="fas fa-list"></i> عرض جميع الإشعارات
                        </a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="user-menu-btn" data-toggle="dropdown" href="#" aria-haspopup="true" aria-expanded="false">
                        <div class="user-avatar"><?= mb_substr($userFirstName ?: $userName, 0, 1, 'UTF-8') ?></div>
                        <span class="d-none d-sm-inline"><?= $userName ?></span>
                        <i class="fas fa-chevron-down" style="font-size:11px;color:#9ca3af;"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-left" style="text-align:center !important;">
                        <a href="<?= $User->userIsEmployee() ? 'ess-profile' : 'emp-info' ?>" class="dropdown-item"
                            style="text-align:center !important; justify-content:center !important;"><i
                                class="fas fa-user-circle"></i> الملف الشخصي</a>
                        <a href="<?= $User->userIsEmployee() ? 'ess-settings' : 'emp-setting' ?>" class="dropdown-item"
                            style="text-align:center !important; justify-content:center !important;"><i
                                class="fas fa-cog"></i> الإعدادات</a>
                        <div class="dropdown-divider"></div>
                        <a href="?logout=1" class="dropdown-item text-danger"
                            style="text-align:center !important; justify-content:center !important;"><i
                                class="fas fa-sign-out-alt"></i> تسجيل خروج</a>
                    </div>
                </li>
            </ul>
        </nav>

        <!-- Main Sidebar -->
        <aside class="main-sidebar elevation-4" id="mainSidebar">
            <a href="<?= $User->userIsEmployee() ? 'ess-dashboard' : 'employer-dashboard' ?>" class="brand-link">
                <img src="dist/img/brand/logo-secondary.png" alt="Vision HR" class="brand-logo" width="40" height="40"
                    decoding="async" fetchpriority="high">
            </a>
            <div class="sidebar">
                <nav class="mt-1">
                    <ul class="nav nav-pills nav-sidebar flex-column" role="menu">

                        <?php if ($User->userIsEmployee()): ?>
                            <!-- Employee Navigation -->
                            <li class="nav-header">الخدمة الذاتية</li>
                            <li class="nav-item">
                                <a href="ess-dashboard"
                                    class="nav-link <?= ($page ?? '') == 'ess-dashboard' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-home"></i>
                                    <p>لوحة المعلومات</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="ess-attendance"
                                    class="nav-link <?= ($page ?? '') == 'ess-attendance' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-clock"></i>
                                    <p>سجل الحضور</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="ess-leaves" class="nav-link <?= ($page ?? '') == 'ess-leaves' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-calendar-alt"></i>
                                    <p>طلبات الإجازات</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="ess-advances"
                                    class="nav-link <?= ($page ?? '') == 'ess-advances' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-hand-holding-usd"></i>
                                    <p>طلبات السلف</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="ess-orders" class="nav-link <?= ($page ?? '') == 'ess-orders' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-file-signature"></i>
                                    <p>الطلبات الإدارية</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="ess-salary" class="nav-link <?= ($page ?? '') == 'ess-salary' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-wallet"></i>
                                    <p>كشوف الرواتب</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="ess-documents"
                                    class="nav-link <?= ($page ?? '') == 'ess-documents' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-file-pdf"></i>
                                    <p>المستندات والتقارير</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="ess-policies"
                                    class="nav-link <?= ($page ?? '') == 'ess-policies' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-book"></i>
                                    <p>سياسات الشركة</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="ess-profile"
                                    class="nav-link <?= ($page ?? '') == 'ess-profile' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-id-badge"></i>
                                    <p>ملفي الشخصي</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="ess-settings"
                                    class="nav-link <?= ($page ?? '') == 'ess-settings' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-cog"></i>
                                    <p>الإعدادات</p>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if ($User->userIsEmployer() || $User->userIsAdmin()): ?>
                            <!-- Employer/HR Manager Navigation -->
                            <li class="nav-header">إدارة الموارد البشرية</li>
                            <li class="nav-item">
                                <a href="employer-dashboard"
                                    class="nav-link <?= ($page ?? '') == 'employer-dashboard' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-chart-pie"></i>
                                    <p>لوحة التحكم</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="Hrdashboard"
                                    class="nav-link <?= ($page ?? '') == 'Hrdashboard' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-fingerprint"></i>
                                    <p>الحضور والانصراف</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="dashboard-emp"
                                    class="nav-link <?= ($page ?? '') == 'dashboard-emp' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-tachometer-alt"></i>
                                    <p>لوحة بيانات الموظفين</p>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if ($User->userIsEmployer() || $User->userIsAdmin()): ?>
                            <li class="nav-item has-treeview">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>إدارة الموظفين <i class="fas fa-angle-left right"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="employer-list" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>قائمة الموظفين</p>
                                        </a></li>
                                    <li class="nav-item"><a href="employer-add" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>إضافة موظف</p>
                                        </a></li>
                                    <li class="nav-item">
                                        <a href="applicants-list.php" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>قائمة المتقدمين</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li class="nav-item has-treeview">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-clock"></i>
                                    <p>الحضور <i class="fas fa-angle-left right"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="reveal-attendance" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>كشف الحضور</p>
                                        </a></li>
                                    <li class="nav-item"><a href="attendancet-emp" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>تسجيل الحضور</p>
                                        </a></li>
                                </ul>
                            </li>

                            <li class="nav-item has-treeview">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-money-bill-wave"></i>
                                    <p>المالية <i class="fas fa-angle-left right"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="Benefits-list" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>التعويضات</p>
                                        </a></li>
                                    <li class="nav-item"><a href="deductions-list" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>الخصومات</p>
                                        </a></li>
                                    <li class="nav-item"><a href="incentive-list" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>الحوافز</p>
                                        </a></li>
                                    <li class="nav-item"><a href="EmpAdvances-list" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>السلف</p>
                                        </a></li>
                                    <li class="nav-item"><a href="Issuing-salaries" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>إصدار الرواتب</p>
                                        </a></li>
                                </ul>
                            </li>

                            <li class="nav-item has-treeview">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-calendar-alt"></i>
                                    <p>الإجازات <i class="fas fa-angle-left right"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="leaveRequest-list" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>طلبات الإجازات</p>
                                        </a></li>
                                    <li class="nav-item"><a href="leaveClassficate-list" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>تصنيفات الإجازات</p>
                                        </a></li>
                                </ul>
                            </li>
                            <li class="nav-item has-treeview">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-file-contract"></i>
                                    <p>العقود والترقيات <i class="fas fa-angle-left right"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="contractRenewal-list" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>تجديد العقود</p>
                                        </a></li>
                                    <li class="nav-item"><a href="promotion-list" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>الترقيات</p>
                                        </a></li>
                                </ul>
                            </li>

                            <li class="nav-item has-treeview">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-user-slash"></i>
                                    <p>إنهاء الخدمة <i class="fas fa-angle-left right"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="resignation-list" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>الاستقالات</p>
                                        </a></li>
                                    <li class="nav-item"><a href="dismissal-list" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>الفصل</p>
                                        </a></li>
                                </ul>
                            </li>

                            <li class="nav-item">
                                <a href="report-center"
                                    class="nav-link <?= ($page ?? '') == 'report-center' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-chart-bar"></i>
                                    <p>التقارير</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="org-chart" class="nav-link <?= ($page ?? '') == 'org-chart' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-sitemap"></i>
                                    <p>الهيكل التنظيمي</p>
                                </a>
                            </li>

                            <li class="nav-item has-treeview">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-balance-scale"></i>
                                    <p>السياسات <i class="fas fa-angle-left right"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="admin-leave-policies" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>سياسات الإجازات</p>
                                        </a></li>
                                    <li class="nav-item"><a href="admin-violations" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>إدارة المخالفات</p>
                                        </a></li>
                                    <li class="nav-item"><a href="admin-promotions" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>إدارة الترقيات</p>
                                        </a></li>
                                    <li class="nav-item"><a href="admin-workflows" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>سير العمل</p>
                                        </a></li>
                                </ul>
                            </li>

                            <li class="nav-item has-treeview">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-star"></i>
                                    <p>التقييم والمكافآت <i class="fas fa-angle-left right"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="admin-evaluations" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>تقييم الأداء</p>
                                        </a></li>
                                    <li class="nav-item"><a href="admin-rewards" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>المكافآت</p>
                                        </a></li>
                                    <li class="nav-item"><a href="admin-salary-ranges" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>نطاقات الرواتب</p>
                                        </a></li>
                                </ul>
                            </li>

                            <li class="nav-item">
                                <a href="admin-approvals"
                                    class="nav-link <?= ($page ?? '') == 'admin-approvals' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-tasks"></i>
                                    <p>الموافقات المعلقة</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="audit-log" class="nav-link <?= ($page ?? '') == 'audit-log' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-shield-alt"></i>
                                    <p>سجل المراجعة</p>
                                </a>
                            </li>

                            <?php if ($User->userIsAdmin()): ?>
                                <li class="nav-item">
                                    <a href="hr-setting" class="nav-link <?= ($page ?? '') == 'hr-setting' ? 'active' : '' ?>">
                                        <i class="nav-icon fas fa-cogs"></i>
                                        <p>الإعدادات</p>
                                    </a>
                                </li>
                            <?php endif; ?>

                        <?php endif; ?>

                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">