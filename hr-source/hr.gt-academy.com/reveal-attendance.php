<?php
$appid  = 'HR';
$page_perm = ['الحضور والانصراف', 'عرض الحضور والانصراف', 'تحضير موظف', 'رفع ملف الاكسل'];

$screen = 'إدارة الموارد البشرية';
$page_title = 'كشف الحضور والانصراف';

include_once('inc/header.php');
$allowed_branches = $User->allBranches($User->branches);
?>

<style>
    :root {
        --primary-color: #4e73df;
        --secondary-color: #858796;
        --success-color: #1cc88a;
        --info-color: #36b9cc;
        --warning-color: #f6c23e;
        --danger-color: #e74a3b;
        --light-color: #f8f9fc;
        --dark-color: #5a5c69;
        --text-color: #5a5c69;
        --card-bg: #ffffff;
        --border-color: #e3e6f0;
        --shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.08);
    }

    body {
        background-color: #f0f2f5; /* Light grey background for the whole page */
    }

    /* Page Navigation (Header) */
    .page-nav {
        background: var(--card-bg);
        padding: 1.5rem 1.5rem; /* Increased padding */
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 25px; /* Added more space below header */
        box-shadow: var(--shadow);
        border-radius: 8px; /* Slightly rounded corners for the header card */
    }

    .page-title {
        font-size: 1.75rem; /* Larger title */
        font-weight: 700;
        color: var(--dark-color);
        display: flex;
        align-items: center;
    }

    .page-title i {
        margin-left: 10px;
        color: var(--info-color);
    }

    .content-header .btn {
        margin-left: 10px; /* Space between header buttons */
    }
    .content-header .btn-icon-split .icon {
        padding: .5em .75em;
        background-color: rgba(0,0,0,0.05);
        border-radius: .35rem 0 0 .35rem;
    }
    .content-header .btn-icon-split .text {
        padding: .5em .75em;
    }

    /* General Card Styling */
    .card {
        border: none;
        border-radius: 12px; /* Uniform rounded corners */
        box-shadow: var(--shadow);
        margin-bottom: 25px; /* Consistent card spacing */
    }

    .card-header {
        background-color: var(--light-color); /* Light header background */
        border-bottom: 1px solid var(--border-color);
        padding: 1.25rem 1.5rem; /* More padding */
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        display: flex;
        align-items: center;
    }
    .card-header.bg-warning {
        background-color: var(--warning-color) !important;
        color: #fff;
    }
    .card-header.bg-warning .card-title {
        color: #fff;
    }

    .card-title {
        font-weight: 600;
        color: var(--dark-color);
        font-size: 1.25rem;
    }

    .card-body {
        padding: 1.5rem; /* Generous padding inside cards */
    }

    /* Filter Card */
    .filter-card .card-body {
        background-color: #fdfdfd;
    }
    .filter-advance {
        display: none;
        padding-top: 15px;
        border-top: 1px solid var(--border-color);
        margin-top: 15px;
    }

    .btn-filter-toggle {
        background: var(--light-color);
        border: 1px solid var(--border-color);
        color: var(--text-color);
        transition: all 0.2s ease-in-out;
    }
    .btn-filter-toggle:hover {
        background: #e9e9f1;
        border-color: #d1d3e2;
    }

    /* Data Table Styling */
    .table-responsive {
        border-radius: 12px;
        overflow-x: auto;
        overflow-y: visible;
        box-shadow: var(--shadow);
    }

    .dataTables_wrapper,
    .dataTables_wrapper .row,
    .card-body {
        overflow: visible;
    }

    #data_tb .btn-group,
    #requests_tb .btn-group {
        position: relative;
    }

    #data_tb .dropdown-menu,
    #requests_tb .dropdown-menu {
        min-width: 180px;
        z-index: 1065;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.16);
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 12px;
    }

    .attendance-view-modal .modal-dialog {
        max-width: 980px;
    }

    .attendance-view-modal .modal-content {
        border: none;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
    }

    .attendance-view-modal .modal-header {
        background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 100%);
        border-bottom: 1px solid var(--border-color);
    }

    .attendance-view-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .attendance-view-summary .summary-card {
        background: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 14px 16px;
    }

    .attendance-view-summary .summary-label {
        color: var(--secondary-color);
        font-size: 0.88rem;
        margin-bottom: 6px;
    }

    .attendance-view-summary .summary-value {
        color: var(--dark-color);
        font-size: 1rem;
        font-weight: 700;
    }

    #attendanceDetailsTable th,
    #attendanceDetailsTable td {
        text-align: center;
        vertical-align: middle;
    }

    #attendanceDetailsEmpty {
        display: none;
    }

    .table.dataTable {
        margin-top: 0 !important;
        width: 100% !important; /* Ensure table takes full width */
        background-color: var(--card-bg);
    }

    .table.dataTable thead th {
        background-color: var(--primary-color);
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        border-bottom: 2px solid #e3e6f0;
        padding: 12px 15px;
        text-align: center; /* Center align table headers */
    }
    #requests_tb thead th {
        background-color: var(--warning-color); /* Specific header for requests */
    }

    .table.dataTable tbody tr {
        background-color: var(--card-bg);
        transition: background-color 0.2s ease;
    }

    .table.dataTable tbody tr:hover {
        background-color: var(--light-color);
    }

    .table.dataTable td, .table.dataTable th {
        vertical-align: middle;
        padding: 10px 15px;
        border-color: var(--border-color);
        text-align: center; /* Center align table cells */
    }
    .table.dataTable td:first-child { text-align: right; } /* Adjust for right-to-left layout */


    /* Small Boxes (Stats) */
    .small-box {
        border-radius: 10px;
        box-shadow: var(--shadow);
        transition: transform 0.3s ease;
    }
    .small-box:hover {
        transform: translateY(-5px);
    }
    .small-box h3 {
        font-weight: 700;
        font-size: 2.2rem;
        margin-bottom: 5px;
    }
    .small-box p {
        font-size: 1.1rem;
        opacity: 0.9;
    }
    .small-box .icon {
        top: 15px; /* Adjust icon position */
        font-size: 60px; /* Larger icon */
    }

    /* Custom Switch (toggle) */
    .custom-switch .custom-control-label::before {
        background-color: var(--secondary-color);
        border-color: var(--secondary-color);
    }
    .custom-switch .custom-control-input:checked ~ .custom-control-label::before {
        background-color: var(--info-color);
        border-color: var(--info-color);
    }

    /* QR Panel Styling */
    .qr-display-container img {
        transition: all 0.3s ease;
    }
    .qr-display-container img:hover {
        transform: scale(1.03);
        box-shadow: 0 6px 16px rgba(0,0,0,0.15) !important;
    }
    .qr-details {
        background: #f8fafc;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid var(--border-color);
    }
    .qr-details strong {
        font-size: 1.1rem;
    }
    #activeTokensList .table {
        margin-top: 10px;
        border-radius: 8px;
        overflow: hidden;
    }
    #activeTokensList .progress {
        height: 18px;
        border-radius: 5px;
        background-color: #e9ecef;
    }
    #activeTokensList .progress-bar {
        font-size: 0.75rem;
        line-height: 18px;
        text-align: center;
    }
    #activeTokensList .badge {
        font-size: 0.8em;
        padding: 0.4em 0.6em;
    }

    /* DataTable Pagination */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.5rem 0.75rem;
        margin-left: 2px;
        border-radius: 5px !important;
        border: 1px solid #ddd !important;
        color: var(--primary-color) !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: var(--primary-color) !important;
        color: white !important;
        border-color: var(--primary-color) !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: var(--primary-color) !important;
        color: white !important;
        border: 1px solid var(--primary-color) !important;
    }
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_length {
        color: var(--secondary-color);
        font-size: 0.9rem;
        padding-top: 0.85em;
    }

    /* Date Range Picker fix */
    .daterangepicker {
        font-family: 'Cairo', sans-serif !important; /* Ensure consistent font */
    }

    /* Responsive adjustments */
    @media (max-width: 991.98px) {
        .page-nav {
            padding: 1rem;
            margin-bottom: 20px;
        }
        .page-title {
            font-size: 1.4rem;
        }
        .content-header .col-sm-6 {
            text-align: center !important;
            margin-bottom: 10px;
        }
        .content-header .col-sm-6:first-child {
            margin-bottom: 15px;
        }
        .content-header .btn {
            margin: 5px;
            display: inline-flex;
            align-items: center;
        }
        .content-header .btn .text {
            display: none !important;
        }
        .small-box .inner {
            padding: 10px;
        }
        .small-box h3 {
            font-size: 1.8rem;
        }
        .small-box p {
            font-size: 0.9rem;
        }
        .card-header {
            padding: 1rem;
        }
        .card-title {
            font-size: 1.1rem;
        }
    }

    @media (max-width: 767.98px) {
        .page-nav {
            padding: 0.75rem;
            margin-bottom: 15px;
        }
        .page-title {
            font-size: 1.2rem;
            justify-content: center;
        }
        .content-header .col-sm-6 {
            margin-bottom: 10px;
        }
        .content-header .btn {
            padding: 8px 12px;
            font-size: 0.85rem;
            margin: 3px;
        }
        .card {
            margin-bottom: 20px;
        }
        .card-body {
            padding: 1rem;
        }
        .filter-area .col-md-4 {
            margin-bottom: 15px;
        }
        .btn-group .btn {
            display: block;
            width: 100%;
            margin-bottom: 5px;
        }
        .table-responsive .table {
            font-size: 0.85rem;
        }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_paginate {
            text-align: center !important;
            padding-top: 10px;
            margin-bottom: 10px;
        }
    }

    @media (max-width: 575.98px) {
        .page-title {
            font-size: 1.1rem;
        }
        .content-header .btn .text {
            display: inline !important; /* show text on smallest screens for critical buttons */
        }
        .content-header .btn .icon {
            padding: .5em .5em;
            border-radius: .35rem; /* Make icon circular if no text is shown */
        }
        .content-header .btn:not(.btn-icon-split) {
            padding: .5rem .75rem;
        }
        .content-header .btn {
            width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: none;
        }
        .content-header .row > div {
             flex-direction: column; /* Stack buttons vertically if needed */
        }
        .small-box {
            margin-bottom: 15px;
        }
        .small-box h3 {
            font-size: 1.5rem;
        }
        .small-box p {
            font-size: 0.85rem;
        }
        .card-header {
            padding: 0.75rem 1rem;
        }
        .card-title {
            font-size: 1rem;
        }
        .card-body {
            padding: 0.75rem;
        }
        .filter-area .col-md-4 {
            flex: 0 0 100%;
            max-width: 100%;
        }
        .modal-dialog {
            margin: 0.5rem;
        }
        .page-title {
            text-align: center;
            width: 100%;
        }
    }

</style>
    <!-- Content Header -->
    <div class="content-header page-nav">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <span class="page-title"><i class="fas fa-fingerprint mr-2"></i> كشف الحضور والانصراف</span>
                </div>
                <div class="col-sm-6 text-left d-flex flex-wrap justify-content-end">
                    <?php if ($User->isAllowedPerm(['تحضير موظف'], $appid)) { ?>
                        <button type="button" class="btn btn-primary btn-icon-split" id="add-emp-bt">
                            <span class="icon text-white-50"><i class="fas fa-user-check"></i></span>
                            <span class="text">تحضير موظف</span>
                        </button>
                    <?php }
                    if ($User->isAllowedPerm(['رفع ملف الاكسل'], $appid)) { ?>
                        <button type="button" class="btn btn-primary btn-icon-split" id="add-emp-bt-excel">
                            <span class="icon text-white-50"><i class="fas fa-file-excel"></i></span>
                            <span class="text">رفع من ملف اكسل</span>
                        </button>
                    <?php } ?>
                    <button type="button" class="btn btn-info btn-icon-split" data-toggle="collapse" data-target="#attConfigPanel">
                        <span class="icon text-white-50"><i class="fas fa-cog"></i></span>
                        <span class="text">إعدادات الحضور</span>
                    </button>
                    <button type="button" class="btn btn-success btn-icon-split" data-toggle="collapse" data-target="#qrPanel">
                        <span class="icon text-white-50"><i class="fas fa-qrcode"></i></span>
                        <span class="text">رمز QR</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Settings Panel -->
    <div class="collapse mt-4" id="attConfigPanel">
        <div class="container-fluid">
            <div class="card card-outline card-info">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-cog mr-2"></i> إعدادات طرق الحضور</h3></div>
                <div class="card-body">
                    <form id="attSettingsForm">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="card bg-light p-3 h-100">
                                    <div class="custom-control custom-switch mb-2">
                                        <input type="checkbox" class="custom-control-input" id="sw_gps" name="gps_enabled" value="1">
                                        <label class="custom-control-label font-weight-bold" for="sw_gps"><i class="fas fa-map-marker-alt text-info mr-2"></i> تفعيل GPS</label>
                                    </div>
                                    <small class="text-muted mb-3 d-block">يسمح للموظفين بتسجيل الحضور عبر الموقع الجغرافي</small>
                                    <div class="mt-2" id="gpsExtraSettings">
                                        <div class="custom-control custom-switch mb-2">
                                            <input type="checkbox" class="custom-control-input" id="sw_gps_req" name="gps_required" value="1">
                                            <label class="custom-control-label" for="sw_gps_req">إلزامي (يجب إرسال الموقع)</label>
                                        </div>
                                        <div class="form-group mb-2">
                                            <label class="small">أقصى مسافة عن المكتب (متر)</label>
                                            <input type="number" class="form-control form-control-sm" name="max_gps_radius_meters" id="inp_radius" value="500">
                                        </div>
                                        <div class="row">
                                            <div class="col-6"><label class="small">خط العرض</label><input type="text" class="form-control form-control-sm" name="office_lat" id="inp_lat"></div>
                                            <div class="col-6"><label class="small">خط الطول</label><input type="text" class="form-control form-control-sm" name="office_lng" id="inp_lng"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-light p-3 h-100">
                                    <div class="custom-control custom-switch mb-2">
                                        <input type="checkbox" class="custom-control-input" id="sw_qr" name="qr_enabled" value="1">
                                        <label class="custom-control-label font-weight-bold" for="sw_qr"><i class="fas fa-qrcode text-primary mr-2"></i> تفعيل QR</label>
                                    </div>
                                    <small class="text-muted d-block">يسمح للموظفين بتسجيل الحضور عبر مسح رمز QR</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-light p-3 h-100">
                                    <div class="custom-control custom-switch mb-2">
                                        <input type="checkbox" class="custom-control-input" id="sw_manual" name="manual_enabled" value="1">
                                        <label class="custom-control-label font-weight-bold" for="sw_manual"><i class="fas fa-hand-pointer text-secondary mr-2"></i> تفعيل اليدوي</label>
                                    </div>
                                    <small class="text-muted d-block">يسمح بتسجيل الحضور يدوياً بدون GPS أو QR</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-light p-3 h-100">
                                    <div class="custom-control custom-switch mb-2">
                                        <input type="checkbox" class="custom-control-input" id="sw_fingerprint" name="fingerprint_enabled" value="1">
                                        <label class="custom-control-label font-weight-bold" for="sw_fingerprint"><i class="fas fa-fingerprint text-success mr-2"></i> تفعيل البصمة</label>
                                    </div>
                                    <small class="text-muted d-block">تسجيل الحضور عبر جهاز البصمة TCP/IP</small>
                                    <div class="mt-2" id="fingerprintExtraSettings" style="display:none;">
                                        <div class="form-group mb-2">
                                            <label class="small">عنوان IP للجهاز</label>
                                            <input type="text" class="form-control form-control-sm" name="fingerprint_device_ip" id="inp_fp_ip" placeholder="192.168.1.100">
                                        </div>
                                        <div class="form-group mb-2">
                                            <label class="small">المنفذ (Port)</label>
                                            <input type="number" class="form-control form-control-sm" name="fingerprint_device_port" id="inp_fp_port" value="4370">
                                        </div>
                                        <div class="form-group mb-2">
                                            <label class="small">نوع الجهاز</label>
                                            <select class="form-control form-control-sm" name="fingerprint_device_type" id="inp_fp_type">
                                                <option value="zkteco">ZKTeco</option>
                                                <option value="anviz">Anviz</option>
                                                <option value="hikvision">Hikvision</option>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-success btn-block mt-2" onclick="testFingerprintConnection()">
                                            <i class="fas fa-plug mr-2"></i> اختبار الاتصال
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-block mt-1" onclick="syncFingerprintData()">
                                            <i class="fas fa-sync-alt mr-2"></i> مزامنة البيانات
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-left mt-3">
                            <button type="submit" class="btn btn-info px-4"><i class="fas fa-save mr-2"></i> حفظ الإعدادات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Token Generation Panel -->
    <div class="collapse mt-4" id="qrPanel">
        <div class="container-fluid">
            <div class="card card-outline card-success">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-qrcode mr-2"></i> إنشاء رمز QR للحضور</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5">
                            <form id="qrGenForm">
                                <div class="form-group mb-3">
                                    <label>مدة الصلاحية (دقيقة)</label>
                                    <input type="number" class="form-control" name="valid_minutes" value="30" min="5" max="1440">
                                </div>
                                <div class="form-group mb-3">
                                    <label>الحد الأقصى للاستخدام</label>
                                    <input type="number" class="form-control" name="max_uses" value="50" min="1" max="500">
                                </div>
                                <div class="form-group mb-4">
                                    <label>الفرع</label>
                                    <select class="form-control selectpicker" name="branch_id" data-live-search="true" data-width="100%" title="كل الفروع">
                                        <?php foreach ($allowed_branches as $bid => $bname): ?>
                                            <option value="<?= $bid ?>"><?= $bname ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success btn-block py-2"><i class="fas fa-plus-circle mr-2"></i> إنشاء رمز QR</button>
                            </form>
                        </div>
                        <div class="col-md-7 mt-md-0 mt-4">
                            <div id="qrResult" style="display:none;" class="text-center">
                                <div class="alert alert-success mb-4 border-0 shadow-sm p-3">
                                    <h5 class="mb-2 text-success"><i class="fas fa-check-circle mr-2"></i> تم إنشاء رمز QR بنجاح</h5>
                                    <p class="mb-0 text-muted">يمكن للموظفين استخدامه لتسجيل الحضور</p>
                                </div>
                                <div class="qr-display-container mb-4">
                                    <img id="qrImage" src="" alt="QR Code" style="max-width:280px;border:3px solid #e5e7eb;border-radius:16px;padding:15px;background:#fff;box-shadow:0 4px 12px rgba(0,0,0,0.1);">
                                </div>
                                <div class="qr-details bg-light p-3 rounded mb-4">
                                    <div class="row text-right">
                                        <div class="col-6 mb-2">
                                            <small class="text-muted d-block">صالح من</small>
                                            <strong id="qrValidFrom" class="text-primary d-block"></strong>
                                        </div>
                                        <div class="col-6 mb-2">
                                            <small class="text-muted d-block">صالح حتى</small>
                                            <strong id="qrValidUntil" class="text-danger d-block"></strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">الحد الأقصى</small>
                                            <strong id="qrMaxUses" class="text-info d-block"></strong>
                                            <small class="text-muted"> استخدام</small>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">الحالة</small>
                                            <strong class="text-success d-block">نشط</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="qr-actions d-flex justify-content-center flex-wrap">
                                    <button class="btn btn-primary btn-sm mx-1 mb-2" onclick="printQR()">
                                        <i class="fas fa-print mr-1"></i> طباعة الرمز
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm mx-1 mb-2" onclick="downloadQR()">
                                        <i class="fas fa-download mr-1"></i> تحميل
                                    </button>
                                    <button class="btn btn-outline-info btn-sm mx-1 mb-2" onclick="copyQRToken()">
                                        <i class="fas fa-copy mr-1"></i> نسخ الرمز
                                    </button>
                                </div>
                            </div>
                            <!-- Active tokens -->
                            <div id="activeTokens" class="mt-5">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 text-primary"><i class="fas fa-list mr-2"></i> الرموز النشطة</h6>
                                    <button class="btn btn-outline-info btn-sm" onclick="refreshTokens()">
                                        <i class="fas fa-sync-alt mr-1"></i> تحديث
                                    </button>
                                </div>
                                <div id="activeTokensList" class="small"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Method Stats -->
    <div class="container-fluid mt-4">
        <div class="row" id="methodStatsRow">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3 id="stat_gps">0</h3>
                        <p>حضور GPS</p>
                    </div>
                    <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3 id="stat_qr">0</h3>
                        <p>حضور QR</p>
                    </div>
                    <div class="icon"><i class="fas fa-qrcode"></i></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3 id="stat_manual">0</h3>
                        <p>حضور يدوي</p>
                    </div>
                    <div class="icon"><i class="fas fa-hand-pointer"></i></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3 id="stat_import">0</h3>
                        <p>استيراد</p>
                    </div>
                    <div class="icon"><i class="fas fa-file-import"></i></div>
                </div>
            </div>
        </div>
    </div>

    <section class="content mt-4">
        <div class="container-fluid">
            <?php if (isset($_SESSION['alert']) && !empty($_SESSION['alert'])): ?>
                <div class="alert alert-success alert-dismissible fade show" id="result-alert">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="icon fas fa-check"></i>
                    <?= $_SESSION['alert'] ?>
                    <?php $_SESSION['alert'] = ''; ?>
                </div>
            <?php endif; ?>

            <!-- NEW: Attendance Correction Requests Card -->
            <div class="card mb-4">
                <div class="card-header bg-warning py-3">
                    <h6 class="m-0 font-weight-bold text-white">
                        <i class="fas fa-exclamation-triangle mr-2"></i> طلبات البصمة المنسية
                        <span class="badge badge-light ml-2" id="pendingRequestsCount">0</span>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="requests_tb" class="table dataTable table-hover dtr-inline nowrap display" width="100%">
                            <thead>
                                <tr>
                                    <th>الموظف</th>
                                    <th>تاريخ الطلب</th>
                                    <th>التاريخ المطلوب</th>
                                    <th>الوقت المطلوب</th>
                                    <th>النوع</th>
                                    <th>السبب</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- END: Attendance Correction Requests Card -->

            <div class="card filter-card mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter mr-2"></i> محرك البحث المتقدم</h6>
                </div>
                <div class="card-body">
                    <form id="filter-fm">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="date_range">الفترة (من - الى)</label>
                                <input type="text" name="date_range" class="form-control input-date-range" placeholder="من - الى" id="date_range" autocomplete="off" value="">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="branchs_list">الفرع</label>
                                <select class="selectpicker form-control" data-live-search="true" data-container="body" data-size="5" data-width="100%" title="أي" id="branchs_list" name="branchs_list" multiple>
                                    <?php
                                    foreach ($allowed_branches as $id => $name) {
                                        echo '<option value="' . $id . '">' . $name . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="filter_status">الحالة</label>
                                <select class="selectpicker show-tick form-control" data-width="100%" title="أي" id="filter_status" name="filter_status">
                                    <option value="1">حاضر </option>
                                    <option value="2">غير حاضر</option>
                                </select>
                            </div>
                        </div>

                        <div class="row filter-advance">
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="user_section">القسم</label>
                                <select class="form-control selectpicker" data-live-search="true" data-container="body" data-size="5" data-width="100%" title="حدد باي قسم" id="user_section" name="user_section" multiple></select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="user_jobtitle">المسمى الوظيفي</label>
                                <select class="form-control selectpicker" data-live-search="true" data-container="body" data-size="5" data-width="100%" title="حدد المسمى الوظيفي" id="user_jobtitle" name="user_jobtitle" multiple></select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="user_grade">الدرجة الوظيفية</label>
                                <select class="form-control selectpicker" data-live-search="true" data-container="body" data-size="5" data-width="100%" title="حدد الدرجة الوظيفية" id="user_grade" name="user_grade" multiple></select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="user_groub">المجموعة الوظيفية</label>
                                <select class="form-control selectpicker" data-live-search="true" data-container="body" data-size="5" data-width="100%" title="حدد المجموعه" id="user_groub" name="user_groub[]" multiple></select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                            <button type="button" class="btn btn-sm btn-filter-toggle show-advance">
                                <i class="fa fa-sliders-h mr-2"></i> <strong>خيارات اضافية</strong>
                            </button>
                            <div>
                                <button type="reset" class="btn btn-light reset-filter mx-2">إلغاء الفلترة</button>
                                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-search mr-2"></i> بحث</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table id="data_tb" class="table dataTable table-hover dtr-inline nowrap display" width="100%">
                    <thead>
                        <tr>
                            <th>تاريخ الحضور</th>
                            <th>اسم الموظف</th>
                            <th>الوردية</th>
                            <th>وقت الوردية</th>
                            <th>الحضور والانصراف</th>
                            <th>حالة الحضور</th>
                            <th>حالة الانصراف</th>
                            <th>ساعات العمل المطلوبة</th>
                            <th>دقائق التأخير</th>
                            <th>دقائق الانصراف المبكر</th>
                            <th>الساعات الفعلية</th>
                            <th>الاجراءات</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>

    <div class="modal fade attendance-view-modal" id="attendanceViewModal" tabindex="-1" role="dialog" aria-labelledby="attendanceViewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="attendanceViewModalLabel">تفاصيل حضور الموظف</h5>
                        <small class="text-muted" id="attendanceViewSubtitle">عرض تفاصيل اليوم المحدد</small>
                    </div>
                    <button type="button" class="close ml-0 mr-auto" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="attendance-view-summary">
                        <div class="summary-card">
                            <div class="summary-label">الموظف</div>
                            <div class="summary-value" id="attendanceViewEmployee">-</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-label">التاريخ</div>
                            <div class="summary-value" id="attendanceViewDate">-</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-label">الوردية</div>
                            <div class="summary-value" id="attendanceViewShift">-</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-label">إجمالي الساعات المحتسبة</div>
                            <div class="summary-value" id="attendanceViewCredited">00:00</div>
                        </div>
                    </div>

                    <div class="table-responsive shadow-none">
                        <table class="table table-bordered table-striped mb-0" id="attendanceDetailsTable">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>الوردية</th>
                                    <th>الحضور والانصراف</th>
                                    <th>حالة الحضور</th>
                                    <th>حالة الانصراف</th>
                                    <th>الساعات المطلوبة</th>
                                    <th>دقائق التأخير</th>
                                    <th>الانصراف المبكر</th>
                                    <th>الساعات الفعلية</th>
                                    <th>الساعات المحتسبة</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="9" class="text-left">الإجمالي</th>
                                    <th id="attendanceDetailsTotal">00:00</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="alert alert-light text-center mt-3 mb-0" id="attendanceDetailsEmpty">
                        لا توجد بيانات حضور لهذا اليوم.
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
include_once('inc/footer.php');
?>
<!-- DataTables loaded from CDN in footer.php -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
<script src="lib/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Added SweetAlert2 for better modals -->

<script>
    $(document).ready(function() {
        // Initialize Date Range Picker
        $('.input-date-range').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                applyLabel: 'Apply',
                direction: 'rtl',
                format: 'YYYY-MM-DD',
                separator: ' - ',
                daysOfWeek: ['أحد', 'اثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة', 'سبت'],
                monthNames: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
                firstDay: 6
            }
        });

        $('.input-date-range').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        });

        $('.input-date-range').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });


        $(document).on('click', '.show-advance', function() {
            $('.filter-advance').slideToggle();
            $(this).find('strong').text(function(i, text) {
                return text === "خيارات اضافية" ? "إخفاء الخيارات" : "خيارات اضافية";
            });
            $(this).find('i').toggleClass('fa-sliders-h fa-times');
        });

        // Reset Filters
        $(document).on('click', '.reset-filter', function() {
            $('#filter-fm')[0].reset();
            $('.selectpicker').val('').selectpicker('refresh');
            $('#date_range').val(''); // Clear date range input specifically
            dataTable.draw(); // Redraw DataTable with no filters
        });

        // Get filter values as an array
        function get_filter(input_name) {
            var filter = [];
            $('select[name="' + input_name + '"] option:selected').each(function() {
                filter.push($(this).val());
            });
            return filter;
        }

        // Apply Filters
        $('#filter-fm').on('submit', function(e) {
            e.preventDefault();
            dataTable.draw(); // Redraw DataTable with current filter values
        });

        // Navigation buttons
        $('#add-emp-bt').on('click', function() {
            window.location.href = "attendancet-emp";
        });

        $('#add-emp-bt-excel').on('click', function() {
            window.location.href = "import-emp-atten";
        });

        // Initialize Main Attendance DataTable
        var dataTable = $('#data_tb').DataTable({
            "processing": true,
            "serverSide": true,
            "paging": true,
            "ajax": {
                url: "hr-app/index.php?action=reveal-attendance",
                type: "POST",
                data: function(d) {
                    var dateVal = $('#date_range').val();
                    // CHANGE: Only send 'yes' if dateVal actually has a value
                    d.is_date_search = (dateVal && dateVal !== '') ? 'yes' : 'no';
                    d.date_range = dateVal;
                    
                    d.states = $('#filter_status').val();
                    d.branchs = get_filter('branchs_list');
                    d.section = get_filter('user_section');
                    d.jobtitle = get_filter('user_jobtitle');
                    d.grade = get_filter('user_grade');
                    d.groub = get_filter('user_groub');
                }
            },
            "columns": [
                { "data": "id", "name": "attendance_date", "defaultContent": "" , "orderable": true, "className": "text-nowrap" , "render": function(data, type, row){ return row.attendance_date || '';}}, // Corrected to use date directly
                { "data": "name", "name": "emp_name", "defaultContent": "" },
                { "data": "shift_name", "name": "shift_name", "defaultContent": "-" },
                { "data": "shift_time", "name": "shift_time", "defaultContent": "-" },
                { "data": "attendance_punches", "name": "attendance_punches", "defaultContent": "-" },
                { "data": "checkin_status", "name": "checkin_status", "defaultContent": "-" },
                { "data": "checkout_status", "name": "checkout_status", "defaultContent": "-" },
                { "data": "scheduled_hours", "name": "scheduled_hours", "defaultContent": "-" },
                { "data": "delay_minutes", "name": "delay_minutes", "defaultContent": "-" },
                { "data": "early_departure_minutes", "name": "early_departure_minutes", "defaultContent": "-" },
                { "data": "actual_working_hours", "name": "actual_working_hours", "defaultContent": "-" },
                {
                    "data": "actions",
                    "bSortable": false,
                    "render": function(data, type, row) {
                        var options = '<div class="btn-group">';
                        options += '<button type="button" class="btn btn-outline-info btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown" aria-expanded="false">';
                        options += '<i class="fas fa-ellipsis-h"></i></button>';
                        options += '<div class="dropdown-menu dropdown-menu-right" role="menu">';
                        <?php if ($User->isAllowedPerm(['عرض الحضور والانصراف'], $appid)) { ?>
                            options += '<button type="button" class="dropdown-item view-pos" data-emp-id="' + row.id + '" data-emp-name="' + $('<div>').text(row.name || '').html() + '" data-attendance-date="' + row.attendance_date + '" data-shift-name="' + $('<div>').text(row.shift_name || '-').html() + '"><i class="fa fa-eye mr-2"></i> عرض التفاصيل</button>';
                        <?php } ?>
                        options += '</div></div>';
                        return options;
                    }
                }
            ],
            "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 text-left'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
        });

        $('#data_tb tbody').on('click', '.view-pos', function(e) {
            e.preventDefault();
            openAttendanceDetailsModal({
                userId: $(this).data('emp-id'),
                employeeName: $(this).data('emp-name'),
                attendanceDate: $(this).data('attendance-date'),
                shiftName: $(this).data('shift-name')
            });
        });

        function escapeHtml(value) {
            return $('<div>').text(value == null ? '' : value).html();
        }

        function openAttendanceDetailsModal(details) {
            $('#attendanceViewEmployee').text(details.employeeName || '-');
            $('#attendanceViewDate').text(details.attendanceDate || '-');
            $('#attendanceViewShift').text(details.shiftName || '-');
            $('#attendanceViewCredited').text('00:00');
            $('#attendanceDetailsTotal').text('00:00');
            $('#attendanceDetailsEmpty')
                .removeClass('alert-danger')
                .addClass('alert-light')
                .hide()
                .text('لا توجد بيانات حضور لهذا اليوم.');
            $('#attendanceDetailsTable tbody').html(
                '<tr><td colspan="10" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin mr-2"></i> جاري تحميل التفاصيل...</td></tr>'
            );

            $('#attendanceViewModal').modal('show');

            $.ajax({
                url: 'hr-app/index.php?action=reveal-attendance-view',
                type: 'POST',
                dataType: 'json',
                data: {
                    user_id: details.userId,
                    date_range: details.attendanceDate + ' - ' + details.attendanceDate,
                    is_date_search: 'yes',
                    start: 0,
                    length: 25,
                    draw: 1
                },
                success: function(response) {
                    var rows = (response && response.data) ? response.data : [];
                    $('#attendanceViewCredited').text(response.total_credited_time || '00:00');
                    $('#attendanceDetailsTotal').text(response.total_credited_time || '00:00');

                    if (!rows.length) {
                        $('#attendanceDetailsTable tbody').empty();
                        $('#attendanceDetailsEmpty').show();
                        return;
                    }

                    var html = '';
                    rows.forEach(function(item) {
                        html += '<tr>';
                        html += '<td>' + escapeHtml(item.updated || '-') + '</td>';
                        html += '<td>' + escapeHtml(item.ShiftID || '-') + '</td>';
                        html += '<td>' + (item.attendance_punches || '-') + '</td>';
                        html += '<td>' + (item.checkin_status || '-') + '</td>';
                        html += '<td>' + (item.checkout_status || '-') + '</td>';
                        html += '<td>' + escapeHtml(item.scheduled_hours || '-') + '</td>';
                        html += '<td>' + escapeHtml(item.delay_minutes || '-') + '</td>';
                        html += '<td>' + escapeHtml(item.early_departure_minutes || '-') + '</td>';
                        html += '<td>' + escapeHtml(item.actual_working_hours || '-') + '</td>';
                        html += '<td>' + escapeHtml(item.credited_hours || '-') + '</td>';
                        html += '</tr>';
                    });

                    $('#attendanceDetailsTable tbody').html(html);
                },
                error: function() {
                    $('#attendanceDetailsTable tbody').html('');
                    $('#attendanceDetailsEmpty')
                        .removeClass('alert-light')
                        .addClass('alert-danger')
                        .text('تعذر تحميل تفاصيل الحضور في الوقت الحالي.')
                        .show();
                }
            });
        }


        // Fetch filter options (sections, job titles, etc.)
        get_filter_info();

        function get_filter_info() {
            $.ajax({
                url: 'hr-app/index.php?action=allUserinfo_Search',
                type: 'POST',
                data: { value: 1 },
                dataType: "json",
                beforeSend: function() {
                    $('#preloading').show();
                },
                success: function(response) {
                    populateSelect('#user_section', response.section);
                    populateSelect('#user_jobtitle', response.jobtitle);
                    populateSelect('#user_grade', response.JobGrade);
                    populateSelect('#user_groub', response.groub_list);
                    $('#preloading').hide();
                },
                error: function() {
                    toastr.error('حدث خطأ أثناء جلب البيانات');
                }
            });
        }

        function populateSelect(selectId, items) {
            var select = $(selectId);
            select.empty();
            if (items && items.length > 0) {
                $.each(items, function(index, item) {
                    select.append('<option value="' + item.data.id + '">' + item.data.name + '</option>');
                });
            }
            select.selectpicker('refresh');
        }

        // ========== Attendance Settings ==========
        function loadAttSettings() {
            $.post('hr-app/index.php?action=attendance-settings-get', {}, function(res) {
                if (res.result && res.data) {
                    var d = res.data;
                    $('#sw_gps').prop('checked', d.gps_enabled === '1');
                    $('#sw_qr').prop('checked', d.qr_enabled === '1');
                    $('#sw_manual').prop('checked', d.manual_enabled === '1');
                    $('#sw_fingerprint').prop('checked', d.fingerprint_enabled === '1');
                    $('#sw_gps_req').prop('checked', d.gps_required === '1');
                    $('#inp_radius').val(d.max_gps_radius_meters || 500);
                    $('#inp_lat').val(d.office_lat || '');
                    $('#inp_lng').val(d.office_lng || '');
                    $('#inp_fp_ip').val(d.fingerprint_device_ip || '');
                    $('#inp_fp_port').val(d.fingerprint_device_port || '4370');
                    $('#inp_fp_type').val(d.fingerprint_device_type || 'zkteco');

                    // Show/hide fingerprint extra settings
                    if (d.fingerprint_enabled === '1') {
                        $('#fingerprintExtraSettings').slideDown();
                    } else {
                        $('#fingerprintExtraSettings').slideUp();
                    }
                }
            }, 'json');
        }
        loadAttSettings();

        // Toggle fingerprint extra settings visibility
        $('#sw_fingerprint').on('change', function() {
            if ($(this).is(':checked')) {
                $('#fingerprintExtraSettings').slideDown();
            } else {
                $('#fingerprintExtraSettings').slideUp();
            }
        });

        function loadActiveTokens() {
            $.post('hr-app/index.php?action=active-qr-tokens', {}, function(res) {
                if (res.result && res.data) {
                    var html = '';
                    if (res.data.length === 0) {
                        html = '<div class="alert alert-light text-center py-3"><i class="fas fa-info-circle text-muted"></i> <p class="mb-0 text-muted">لا توجد رموز QR نشطة حالياً</p><small class="text-muted">قم بإنشاء رمز جديد لتفعيل تسجيل الحضور عبر QR</small></div>';
                    } else {
                        html = '<div class="table-responsive">';
                        html += '<table class="table table-sm table-hover table-bordered">';
                        html += '<thead class="bg-light"><tr>';
                        html += '<th><i class="fas fa-qrcode"></i> الرمز</th>';
                        html += '<th><i class="fas fa-building"></i> الفرع</th>';
                        html += '<th><i class="fas fa-clock"></i> صالح حتى</th>';
                        html += '<th><i class="fas fa-chart-bar"></i> الاستخدام</th>';
                        html += '<th><i class="fas fa-user"></i> بواسطة</th>';
                        html += '<th><i class="fas fa-cog"></i> إجراءات</th>';
                        html += '</tr></thead><tbody>';

                        res.data.forEach(function(t, index) {
                            var usagePercent = (t.used_count / t.max_uses) * 100;
                            var usageClass = usagePercent > 80 ? 'danger' : usagePercent > 50 ? 'warning' : 'success';
                            var timeLeft = getTimeLeft(t.valid_until);
                            var timeClass = timeLeft.hours <= 1 && !timeLeft.expired ? 'danger' : timeLeft.hours <= 6 && !timeLeft.expired ? 'warning' : 'success';
                            if(timeLeft.expired) timeClass = 'secondary';


                            html += '<tr>';
                            html += '<td><code class="text-primary small">' + t.token.substring(0, 8) + '...</code></td>';
                            html += '<td>' + (t.branch_name || '<span class="text-muted">الكل</span>') + '</td>';
                            html += '<td><span class="badge badge-' + timeClass + '">' + t.valid_until + '</span></td>';
                            html += '<td><div class="progress" style="height: 20px;"><div class="progress-bar bg-' + usageClass + '" style="width: ' + usagePercent + '%">' + t.used_count + '/' + t.max_uses + '</div></div></td>';
                            html += '<td><small>' + (t.FirstName || '') + ' ' + (t.LastName || '') + '</small></td>';
                            html += '<td>';
                            html += '<button class="btn btn-outline-primary btn-sm view-token-btn" data-token="' + t.token + '" data-branch="' + (t.branch_name || 'الكل') + '" data-valid-until="' + t.valid_until + '" data-used-count="' + t.used_count + '" data-max-uses="' + t.max_uses + '"><i class="fas fa-eye"></i></button> ';
                            html += '<button class="btn btn-outline-danger btn-sm revoke-token-btn" data-token="' + t.token + '"><i class="fas fa-trash"></i></button>';
                            html += '</td>';
                            html += '</tr>';
                        });
                        html += '</tbody></table>';
                        html += '</div>';
                    }
                    $('#activeTokensList').html(html);
                } else {
                    $('#activeTokensList').html('<div class="alert alert-warning text-center py-3"><i class="fas fa-exclamation-triangle"></i> <p class="mb-0">حدث خطأ في تحميل الرموز النشطة</p></div>');
                }
            }, 'json').fail(function() {
                $('#activeTokensList').html('<div class="alert alert-danger text-center py-3"><i class="fas fa-times-circle"></i> <p class="mb-0">فشل الاتصال بالخادم</p></div>');
            });
        }

        function getTimeLeft(validUntil) {
            var now = new Date();
            var until = new Date(validUntil);
            var diff = until - now;

            if (diff <= 0) {
                return { hours: 0, minutes: 0, expired: true };
            }

            var hours = Math.floor(diff / (1000 * 60 * 60));
            var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

            return { hours: hours, minutes: minutes, expired: false };
        }

        loadActiveTokens();

        // Event delegation for dynamically generated buttons
        $(document).on('click', '.view-token-btn', function(e) {
            e.preventDefault();
            var token = $(this).data('token');
            var branch = $(this).data('branch');
            var validUntil = $(this).data('valid-until');
            var usedCount = $(this).data('used-count');
            var maxUses = $(this).data('max-uses');
            viewQRToken(token, branch, validUntil, usedCount, maxUses);
        });

        $(document).on('click', '.revoke-token-btn', function(e) {
            e.preventDefault();
            var token = $(this).data('token');
            revokeToken(token);
        });

        $('#attSettingsForm').on('submit', function(e) {
            e.preventDefault();
            var data = {
                gps_enabled: $('#sw_gps').is(':checked') ? '1' : '0',
                qr_enabled: $('#sw_qr').is(':checked') ? '1' : '0',
                manual_enabled: $('#sw_manual').is(':checked') ? '1' : '0',
                fingerprint_enabled: $('#sw_fingerprint').is(':checked') ? '1' : '0',
                gps_required: $('#sw_gps_req').is(':checked') ? '1' : '0',
                max_gps_radius_meters: $('#inp_radius').val(),
                office_lat: $('#inp_lat').val(),
                office_lng: $('#inp_lng').val(),
                fingerprint_device_ip: $('#inp_fp_ip').val(),
                fingerprint_device_port: $('#inp_fp_port').val(),
                fingerprint_device_type: $('#inp_fp_type').val()
            };
            $.post('hr-app/index.php?action=attendance-settings-save', data, function(res) {
                if (res.result) { toastr.success(res.msg); } else { toastr.error(res.msg); }
            }, 'json');
        });

        // ========== Fingerprint Device Functions ==========
        function testFingerprintConnection() {
            var ip = $('#inp_fp_ip').val();
            var port = $('#inp_fp_port').val();
            var type = $('#inp_fp_type').val();

            if (!ip) {
                toastr.error('يرجى إدخال عنوان IP للجهاز');
                return;
            }

            toastr.info('جاري اختبار الاتصال بالجهاز...');

            $.post('hr-app/index.php?action=fingerprint-test-connection', {
                device_ip: ip,
                device_port: port,
                device_type: type
            }, function(res) {
                if (res.result) {
                    toastr.success(res.msg || 'تم الاتصال بالجهاز بنجاح');
                    if (res.data) {
                        var info = 'معلومات الجهاز:\n';
                        if (res.data.serial) info += 'الرقم التسلسلي: ' + res.data.serial + '\n';
                        if (res.data.users) info += 'عدد المستخدمين: ' + res.data.users + '\n';
                        if (res.data.records) info += 'عدد السجلات: ' + res.data.records;
                        Swal.fire({
                            title: 'تم الاتصال بنجاح',
                            html: '<pre class="text-right" style="font-size:14px;">' + info + '</pre>',
                            icon: 'success'
                        });
                    }
                } else {
                    toastr.error(res.msg || 'فشل الاتصال بالجهاز');
                }
            }, 'json').fail(function() {
                toastr.error('فشل الاتصال بالخادم');
            });
        }

        function syncFingerprintData() {
            var ip = $('#inp_fp_ip').val();
            var port = $('#inp_fp_port').val();
            var type = $('#inp_fp_type').val();

            if (!ip) {
                toastr.error('يرجى إدخال عنوان IP للجهاز');
                return;
            }

            Swal.fire({
                title: 'مزامنة بيانات البصمة',
                text: 'سيتم جلب سجلات الحضور من جهاز البصمة. هل تريد المتابعة؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'نعم، مزامنة',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    toastr.info('جاري مزامنة البيانات...');

                    $.post('hr-app/index.php?action=fingerprint-sync-data', {
                        device_ip: ip,
                        device_port: port,
                        device_type: type
                    }, function(res) {
                        if (res.result) {
                            Swal.fire({
                                title: 'تمت المزامنة بنجاح',
                                html: 'تم استيراد <strong>' + (res.data?.records_synced || 0) + '</strong> سجل حضور',
                                icon: 'success'
                            });
                            // Refresh the attendance table
                            dataTable.draw();
                        } else {
                            toastr.error(res.msg || 'فشل مزامنة البيانات');
                        }
                    }, 'json').fail(function() {
                        toastr.error('فشل الاتصال بالخادم');
                    });
                }
            });
        }

        // ========== Global: store current token for buttons ==========
        var _currentQRToken = '';

        // ========== QR Token Generation ==========
        $('#qrGenForm').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();

            $('#qrResult').hide();
            toastr.info('جاري إنشاء رمز QR...');

            $.post('hr-app/index.php?action=generate-qr-token', formData, function(res) {
                if (res.result && res.data) {
                    var d = res.data;
                    _currentQRToken = d.token;

                    // Generate REAL scannable QR code using QRCodeLib
                    var qrDataUrl = QRCodeLib.toDataUrl(d.token, 300);
                    $('#qrImage').attr('src', qrDataUrl);
                    $('#qrValidFrom').text(d.valid_from);
                    $('#qrValidUntil').text(d.valid_until);
                    $('#qrMaxUses').text(d.max_uses);

                    $('#qrResult').slideDown();
                    toastr.success('تم إنشاء رمز QR بنجاح');
                    loadActiveTokens();
                } else {
                    toastr.error(res.msg || 'خطأ في إنشاء رمز QR');
                }
            }, 'json').fail(function(xhr, status, error) {
                toastr.error('فشل الاتصال بالخادم: ' + error);
            });
        });

        // View QR Token details - generates REAL scannable QR
        function viewQRToken(token, branch, validUntil, usedCount, maxUses) {
            var qrDataUrl = QRCodeLib.toDataUrl(token, 256);

            var modal = '<div class="modal fade" id="qrViewModal" tabindex="-1">';
            modal += '<div class="modal-dialog">';
            modal += '<div class="modal-content">';
            modal += '<div class="modal-header bg-info text-white">';
            modal += '<h5 class="modal-title"><i class="fas fa-qrcode mr-2"></i> تفاصيل رمز QR</h5>';
            modal += '<button type="button" class="close text-white" data-dismiss="modal">&times;</button>';
            modal += '</div>';
            modal += '<div class="modal-body text-center">';
            modal += '<div class="alert alert-info text-right border-0 shadow-sm p-3 mb-4">';
            modal += '<p class="mb-1"><strong>الرمز:</strong> <code style="font-size:10px">' + token + '</code></p>';
            modal += '<p class="mb-1"><strong>الفرع:</strong> ' + branch + '</p>';
            modal += '<p class="mb-1"><strong>صالح حتى:</strong> ' + validUntil + '</p>';
            modal += '<p class="mb-0"><strong>الاستخدام:</strong> ' + usedCount + ' / ' + maxUses + '</p>';
            modal += '</div>';
            modal += '<div class="my-3"><img src="' + qrDataUrl + '" alt="QR Code" style="max-width:220px;border:2px solid #ddd;border-radius:8px;padding:10px;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.08);"></div>';
            modal += '<div class="mt-3">';
            modal += '<button class="btn btn-primary btn-sm mx-1" onclick="copyToClipboard(\'' + token + '\')"><i class="fas fa-copy mr-1"></i> نسخ الرمز</button> ';
            modal += '<button class="btn btn-outline-secondary btn-sm mx-1" onclick="downloadQRToken(\'' + token + '\')"><i class="fas fa-download mr-1"></i> تحميل</button>';
            modal += '</div>';
            modal += '</div>';
            modal += '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button></div>';
            modal += '</div></div></div>';

            $('#qrViewModal').remove();
            $('body').append(modal);
            $('#qrViewModal').modal('show');
        }

        // Copy text to clipboard
        function copyToClipboard(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    toastr.success('تم نسخ الرمز إلى الحافظة');
                }).catch(function() {
                    _fallbackCopy(text);
                });
            } else {
                _fallbackCopy(text);
            }
        }

        function _fallbackCopy(text) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.cssText = 'position:fixed;opacity:0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            toastr.success('تم نسخ الرمز إلى الحافظة');
        }

        // Download QR as PNG for a specific token
        function downloadQRToken(token) {
            var canvas = document.createElement('canvas'); // Create a canvas element
            QRCodeLib.toCanvas(canvas, token, 400, function (error) { // Use the callback
                if (error) {
                    toastr.error('فشل إنشاء رمز QR للتحميل.');
                    console.error(error);
                    return;
                }
                var link = document.createElement('a');
                link.download = 'qr-token-' + token.substring(0, 8) + '.png';
                link.href = canvas.toDataURL('image/png');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                toastr.success('تم تحميل رمز QR');
            });
        }


        // Refresh tokens list
        function refreshTokens() {
            $('#activeTokensList').html('<div class="text-center py-3"><i class="fas fa-sync-alt fa-spin text-primary"></i> <p class="text-muted mb-0 mt-2">جاري التحميل...</p></div>');
            loadActiveTokens();
        }

        // Download QR from main result area
        function downloadQR() {
            if (!_currentQRToken) { toastr.error('لا يوجد رمز QR'); return; }
            downloadQRToken(_currentQRToken);
        }

        // Copy QR Token from main result area
        function copyQRToken() {
            if (!_currentQRToken) { toastr.error('لا يوجد رمز QR'); return; }
            copyToClipboard(_currentQRToken);
        }

        // Revoke (delete) token
        function revokeToken(token) {
            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: "لن تتمكن من التراجع عن هذا الإجراء!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'نعم، احذفه!',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('hr-app/index.php?action=revoke-qr-token', { token: token }, function(res) {
                        if (res.result) {
                            Swal.fire(
                                'تم الحذف!',
                                'تم حذف الرمز بنجاح.',
                                'success'
                            );
                            loadActiveTokens();
                        } else {
                            Swal.fire(
                                'فشل!',
                                res.msg || 'فشل حذف الرمز.',
                                'error'
                            );
                        }
                    }, 'json').fail(function() {
                        Swal.fire(
                            'خطأ!',
                            'فشل الاتصال بالخادم.',
                            'error'
                        );
                    });
                }
            });
        }

        function printQR() {
            var img = document.getElementById('qrImage');
            var imgSrc = img.src;
            var validFrom = $('#qrValidFrom').text();
            var validUntil = $('#qrValidUntil').text();
            var maxUses = $('#qrMaxUses').text();

            var w = window.open('', '_blank', 'width=600,height=700');

            w.document.write('<!DOCTYPE html>');
            w.document.write('<html dir="rtl" lang="ar">');
            w.document.write('<head>');
            w.document.write('<meta charset="UTF-8">');
            w.document.write('<meta name="viewport" content="width=device-width, initial-scale=1.0">');
            w.document.write('<title>رمز QR للحضور</title>');
            w.document.write('<style>');
            w.document.write('@media print {');
            w.document.write('  body { margin: 0; padding: 20px; font-family: "Cairo", sans-serif; }');
            w.document.write('  .print-header { text-align: center; margin-bottom: 30px; }');
            w.document.write('  .print-header h1 { color: #1e3a5f; font-size: 28px; margin: 0 0 10px 0; }');
            w.document.write('  .print-header p { color: #6b7280; font-size: 16px; margin: 0; }');
            w.document.write('  .qr-container { text-align: center; margin: 30px 0; }');
            w.document.write('  .qr-image { max-width: 300px; border: 3px solid #e5e7eb; border-radius: 12px; padding: 15px; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }');
            w.document.write('  .details { background: #f8fafc; border-radius: 12px; padding: 20px; margin: 20px 0; }');
            w.document.write('  .details h3 { color: #1e3a5f; font-size: 18px; margin: 0 0 15px 0; text-align: center; }');
            w.document.write('  .detail-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }');
            w.document.write('  .detail-item:last-child { border-bottom: none; }');
            w.document.write('  .detail-label { font-weight: 600; color: #374151; font-size: 14px; }');
            w.document.write('  .detail-value { color: #1e3a5f; font-size: 14px; font-weight: 500; }');
            w.document.write('  .footer { text-align: center; margin-top: 30px; color: #6b7280; font-size: 12px; }');
            w.document.write('}');
            w.document.write('@media screen {');
            w.document.write('  body { font-family: "Cairo", sans-serif; background: #f3f4f6; margin: 0; padding: 20px; }');
            w.document.write('  .container { max-width: 500px; margin: 0 auto; background: white; border-radius: 16px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }');
            w.document.write('}');
            w.document.write('</style>');
            w.document.write('</head>');
            w.document.write('<body>');
            w.document.write('<div class="container">');
            w.document.write('<div class="print-header">');
            w.document.write('<h1>رمز QR للحضور</h1>');
            w.document.write('<p>يمكن الموظفين من تسجيل الحضور عبر مسح هذا الرمز</p>');
            w.document.write('</div>');
            w.document.write('<div class="qr-container">');
            w.document.write('<img class="qr-image" src="' + imgSrc + '" alt="QR Code" />');
            w.document.write('</div>');
            w.document.write('<div class="details">');
            w.document.write('<h3>معلومات الرمز</h3>');
            w.document.write('<div class="detail-item">');
            w.document.write('<span class="detail-label">صالح من:</span>');
            w.document.write('<span class="detail-value">' + validFrom + '</span>');
            w.document.write('</div>');
            w.document.write('<div class="detail-item">');
            w.document.write('<span class="detail-label">صالح حتى:</span>');
            w.document.write('<span class="detail-value">' + validUntil + '</span>');
            w.document.write('</div>');
            w.document.write('<div class="detail-item">');
            w.document.write('<span class="detail-label">الحد الأقصى:</span>');
            w.document.write('<span class="detail-value">' + maxUses + ' استخدام</span>');
            w.document.write('</div>');
            w.document.write('</div>');
            w.document.write('<div class="footer">');
            w.document.write('<p>نظام إدارة الموارد البشرية - Vision HR</p>');
            w.document.write('</div>');
            w.document.write('</div>');
            w.document.write('</body>');
            w.document.write('</html>');
            w.document.close();

            w.onload = function() {
                var img = w.document.querySelector('.qr-image');
                var loadCount = 0;
                var maxTries = 3;

                function tryPrint() {
                    if (img.complete && img.naturalHeight !== 0) {
                        setTimeout(function() {
                            w.print();
                            w.close();
                        }, 500);
                    } else if (loadCount < maxTries) {
                        loadCount++;
                        setTimeout(tryPrint, 1000);
                    } else {
                        w.print();
                        w.close();
                    }
                }

                setTimeout(tryPrint, 1000);
            };
        }

        function loadMethodStats() {
            $.post('hr-app/index.php?action=attendance-method-stats', {}, function(res) {
                if (res.result && res.data) {
                    var stats = { gps: 0, qr: 0, manual: 0, import: 0 };
                    res.data.forEach(function(r) { stats[r.method] = parseInt(r.cnt); });
                    $('#stat_gps').text(stats.gps);
                    $('#stat_qr').text(stats.qr);
                    $('#stat_manual').text(stats.manual);
                    $('#stat_import').text(stats['import']);
                }
            }, 'json');
        }
        loadMethodStats();

        // ===============================================
        // ATTENDANCE CORRECTION REQUESTS LOGIC
        // ===============================================

        // Initialize the new DataTable for requests
        var requestsTable = $('#requests_tb').DataTable({
            "processing": true,
            "ajax": {
                url: "hr-app/index.php?action=get-attendance-requests",
                type: "POST",
                dataSrc: 'data' // Important: specify the key for the data array
            },
            "columns": [
                { "data": null, "render": function(data, type, row) { return (row.FirstName || '') + ' ' + (row.LastName || ''); } },
                { "data": "request_date" },
                { "data": "correction_date" },
                { "data": "correction_time" },
                { "data": "correction_type", "render": function(data) {
                    return data == 1
                        ? '<span class="badge badge-success">حضور</span>'
                        : '<span class="badge badge-danger">انصراف</span>';
                }},
                { "data": "reason", "render": function(data) {
                    // Truncate long reasons
                    return '<span title="' + data + '">' + (data.length > 40 ? data.substring(0, 40) + '...' : data) + '</span>';
                }},
                {
                    "data": null,
                    "bSortable": false,
                    "render": function(data, type, row) {
                        var buttons = '<div class="btn-group">';
                        buttons += '<button type="button" class="btn btn-success btn-sm approve-request-btn" data-id="' + row.id + '"><i class="fas fa-check"></i> قبول</button>';
                        buttons += '<button type="button" class="btn btn-danger btn-sm reject-request-btn" data-id="' + row.id + '"><i class="fas fa-times"></i> رفض</button>';
                        buttons += '</div>';
                        return buttons;
                    }
                }
            ],
            "language": { url: 'dist/js/dataTables.arabic.json' },
            "initComplete": function(settings, json) {
                // Update the count badge in the header
                $('#pendingRequestsCount').text(json.data.length);
            }
        });

        // Event handler for the Approve button (using event delegation)
        $('#requests_tb tbody').on('click', '.approve-request-btn', function () {
            var requestId = $(this).data('id');
            processRequest(requestId, 'approve');
        });

        // Event handler for the Reject button
        $('#requests_tb tbody').on('click', '.reject-request-btn', function () {
            var requestId = $(this).data('id');
            processRequest(requestId, 'reject');
        });

        // Function to handle both approve and reject actions
        function processRequest(requestId, decision) {
            var title = (decision === 'approve') ? 'موافقة على الطلب' : 'رفض الطلب';
            var confirmText = (decision === 'approve')
                ? 'سيتم إضافة هذه البصمة إلى سجل الموظف. هل أنت متأكد؟'
                : 'سيتم رفض هذا الطلب ولن يتم إضافته. هل أنت متأكد؟';

            Swal.fire({
                title: title,
                text: confirmText,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: (decision === 'approve') ? '#28a745' : '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'نعم، قم بالإجراء',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'hr-app/index.php?action=process-attendance-request',
                        method: 'POST',
                        data: {
                            request_id: requestId,
                            decision: decision
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.result) {
                                Swal.fire('تم!', response.msg, 'success');
                                requestsTable.ajax.reload(function(json) {
                                    // Update the count badge after reload
                                    $('#pendingRequestsCount').text(json.data.length);
                                }); // Reload the requests table data
                                dataTable.draw(); // Also reload the main attendance table
                            } else {
                                Swal.fire('خطأ!', response.msg, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('خطأ!', 'فشل الاتصال بالخادم.', 'error');
                        }
                    });
                }
            });
        }

    });
</script>
