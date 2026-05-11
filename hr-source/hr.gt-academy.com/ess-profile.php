<?php
/**
 * ESS - Employee Profile & Documents
 * Employee can view and update limited personal info
 */
$screen = 'الخدمة الذاتية';
$page_title = 'الملف الشخصي';
$ess_active = 'profile';
include_once('inc/header.php');

$empData = null;
$contract = null;
$docs = null;

if ($user) {
    $stm = $connect_pdo->prepare(
        "SELECT u.UserID, u.FirstName, u.SecondName, u.LastName, u.UserEmail, u.Photo, u.Phone,
                u.ohter_phone, u.Sex, u.marital_status, u.user_address, u.HealthCondition,
                u.user_insurance, u.user_bank_name, u.user_account_bank,
                u.Id_h, u.start_date_h, u.end_date_h, u.path_h,
                u.Id_license, u.start_date_license, u.end_date_license, u.path_license,
                u.Id_passport, u.start_date_passport, u.end_date_passport, u.path_passport,
                u.Id_health, u.start_date_health, u.end_date_health, u.path_health,
                u.isemp, u.manager, u.FingerID, u.CreatedDate,
                r.Salary, r.Currency, r.SectionID, r.jobtitleID, r.GradeID, r.shiftID,
                r.TypeID, r.GroupID, r.new_s_date, r.new_e_date, r.BranchID,
                s.Name as SectionName, jt.Name as JobTitleName, jg.Name as GradeName,
                sh.ShiftName, grp.Name as GroupName,
                b.branch_name as BranchName,
                ins.Name as InsuranceName,
                mgr.FirstName as MgrFirst, mgr.LastName as MgrLast
         FROM tblusers u
         LEFT JOIN tblremewal r ON r.Id = u.lastversion
         LEFT JOIN tblsection s ON s.Id = r.SectionID
         LEFT JOIN tbljobtitle jt ON jt.Id = r.jobtitleID
         LEFT JOIN tbljobgrade jg ON jg.Id = r.GradeID
         LEFT JOIN tbshift sh ON sh.ShiftID = r.shiftID
         LEFT JOIN tblgroup grp ON grp.Id = r.GroupID
         LEFT JOIN branches b ON b.branch_id = r.BranchID
         LEFT JOIN tbinsurance ins ON ins.Id = u.user_insurance
         LEFT JOIN tblusers mgr ON mgr.UserID = u.manager
         WHERE u.UserID = :uid LIMIT 1"
    );
    $stm->execute([':uid' => $user]);
    $empData = $stm->fetch(PDO::FETCH_ASSOC);
}

$empName = $empData ? trim($empData['FirstName'] . ' ' . ($empData['SecondName'] ?? '') . ' ' . ($empData['LastName'] ?? '')) : '';
$sexMap = [1 => 'ذكر', 2 => 'أنثى'];
$maritalMap = [1 => 'متزوج', 2 => 'أعزب', 3 => 'مطلق', 4 => 'أرمل'];
?>

<style>
.ess-form-card {
    background: #fff;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    margin-bottom: 24px;
}
.ess-form-card .card-title-ess {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e3a5f;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.profile-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    border-radius: 14px;
    padding: 30px;
    color: #fff;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 24px;
}
.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 700;
    flex-shrink: 0;
    border: 3px solid rgba(255,255,255,0.4);
}
.profile-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}
.profile-info h2 {
    margin: 0 0 4px;
    font-size: 1.4rem;
    font-weight: 700;
}
.profile-info .meta {
    opacity: 0.85;
    font-size: 0.9rem;
}
.info-table {
    width: 100%;
}
.info-table td {
    padding: 10px 8px;
    border-bottom: 1px solid #f3f4f6;
    font-size: 0.9rem;
}
.info-table td:first-child {
    color: #6b7280;
    width: 35%;
    font-weight: 500;
}
.info-table td:last-child {
    font-weight: 600;
    color: #1a1a2e;
}
.doc-card {
    background: #f8fafc;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 12px;
    border-right: 4px solid #3b82f6;
}
.doc-card.expired { border-right-color: #ef4444; }
.doc-card.expiring { border-right-color: #f59e0b; }
.doc-card .doc-title {
    font-weight: 700;
    font-size: 0.95rem;
    margin-bottom: 6px;
}
.doc-card .doc-meta {
    font-size: 0.8rem;
    color: #6b7280;
}
.doc-item .badge {
    font-size: 0.75rem;
}

/* Enhanced Responsive Design */
@media (max-width: 992px) {
    .ess-form-card {
        padding: 24px;
        margin-bottom: 20px;
    }
    .ess-form-card .card-title-ess {
        font-size: 1.2rem;
        margin-bottom: 20px;
        text-align: center;
    }
    .profile-header {
        padding: 24px;
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    .profile-avatar {
        width: 80px;
        height: 80px;
        font-size: 1.8rem;
    }
    .profile-info h2 {
        font-size: 1.4rem;
    }
    .profile-info .meta {
        font-size: 0.9rem;
    }
    .info-table td {
        padding: 10px 8px;
        font-size: 0.9rem;
    }
    .info-table td:first-child {
        width: 35%;
    }
    .doc-card {
        padding: 16px;
        margin-bottom: 12px;
    }
    .doc-card .doc-title {
        font-size: 0.95rem;
    }
    .doc-card .doc-meta {
        font-size: 0.8rem;
    }
}

@media (max-width: 768px) {
    .ess-form-card {
        padding: 20px;
        margin-bottom: 16px;
    }
    .ess-form-card .card-title-ess {
        font-size: 1.1rem;
        margin-bottom: 16px;
        text-align: center;
    }
    .profile-header {
        padding: 20px;
        flex-direction: column;
        text-align: center;
        gap: 16px;
    }
    .profile-avatar {
        width: 70px;
        height: 70px;
        font-size: 1.5rem;
    }
    .profile-info h2 {
        font-size: 1.2rem;
    }
    .profile-info .meta {
        font-size: 0.85rem;
    }
    .info-table td {
        padding: 8px 6px;
        font-size: 0.85rem;
    }
    .info-table td:first-child {
        width: 40%;
    }
    .doc-card {
        padding: 12px;
        margin-bottom: 10px;
    }
    .doc-card .doc-title {
        font-size: 0.9rem;
    }
    .doc-card .doc-meta {
        font-size: 0.75rem;
    }
}

@media (max-width: 576px) {
    .ess-form-card {
        padding: 16px;
        margin-bottom: 12px;
    }
    .ess-form-card .card-title-ess {
        font-size: 1rem;
        margin-bottom: 12px;
        text-align: center;
    }
    .profile-header {
        padding: 16px;
        gap: 12px;
    }
    .profile-avatar {
        width: 60px;
        height: 60px;
        font-size: 1.3rem;
    }
    .profile-info h2 {
        font-size: 1.1rem;
    }
    .profile-info .meta {
        font-size: 0.8rem;
    }
    .info-table td {
        padding: 6px 4px;
        font-size: 0.8rem;
    }
    .info-table td:first-child {
        width: 45%;
    }
    .doc-card {
        padding: 10px;
        margin-bottom: 8px;
    }
    .doc-card .doc-title {
        font-size: 0.85rem;
    }
    .doc-card .doc-meta {
        font-size: 0.7rem;
    }
    .doc-item .badge {
        font-size: 0.7rem;
        padding: 2px 6px;
    }
}

/* Touch device optimizations */
@media (hover: none) and (pointer: coarse) {
    .ess-form-card:hover {
        transform: none;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }
    .doc-card:hover {
        transform: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .btn:hover {
        transform: none;
        opacity: 0.9;
    }
    .btn:active {
        transform: scale(0.95);
    }
}

/* Landscape mobile optimizations */
@media (max-width: 768px) and (orientation: landscape) {
    .ess-form-card {
        padding: 12px;
        margin-bottom: 8px;
    }
    .ess-form-card .card-title-ess {
        font-size: 0.95rem;
        margin-bottom: 8px;
    }
    .profile-header {
        padding: 12px;
        gap: 8px;
    }
    .profile-avatar {
        width: 50px;
        height: 50px;
        font-size: 1.1rem;
    }
    .profile-info h2 {
        font-size: 1rem;
    }
    .profile-info .meta {
        font-size: 0.75rem;
    }
    .info-table td {
        padding: 4px 3px;
        font-size: 0.75rem;
    }
    .doc-card {
        padding: 8px;
        margin-bottom: 6px;
    }
    .doc-card .doc-title {
        font-size: 0.8rem;
    }
    .doc-card .doc-meta {
        font-size: 0.65rem;
    }
}
</style>

<div class="content-header page-nav">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <span class="page-title"><i class="fas fa-user-circle"></i> الملف الشخصي</span>
            </div>
        </div>
    </div>
</div>

<section class="content">
<div class="container-fluid">

    <?php if ($empData): ?>
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar">
            <?php if (!empty($empData['Photo'])): ?>
                <img src="uploads/basics/<?= htmlspecialchars($empData['Photo']) ?>" alt="">
            <?php else: ?>
                <?= mb_substr($empData['FirstName'], 0, 1, 'UTF-8') ?>
            <?php endif; ?>
        </div>
        <div class="profile-info">
            <h2><?= htmlspecialchars($empName) ?></h2>
            <div class="meta">
                <?= htmlspecialchars($empData['JobTitleName'] ?? '') ?>
                <?php if (!empty($empData['SectionName'])): ?> — <?= htmlspecialchars($empData['SectionName']) ?><?php endif; ?>
            </div>
            <div class="meta mt-1">
                <i class="fas fa-envelope"></i> <?= htmlspecialchars($empData['UserEmail'] ?? '-') ?>
                <?php if (!empty($empData['Phone'])): ?> &nbsp; <i class="fas fa-phone"></i> <?= htmlspecialchars($empData['Phone']) ?><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Personal Info -->
        <div class="col-lg-6 mb-4">
            <div class="ess-form-card-enhanced h-100">
                <div class="card-title-ess"><i class="fas fa-user"></i> المعلومات الشخصية</div>
                <table class="info-table">
                    <tr><td>الاسم الكامل</td><td><?= htmlspecialchars($empName) ?></td></tr>
                    <tr><td>البريد الإلكتروني</td><td><?= htmlspecialchars($empData['UserEmail'] ?? '-') ?></td></tr>
                    <tr><td>الهاتف</td><td><?= htmlspecialchars($empData['Phone'] ?? '-') ?></td></tr>
                    <tr><td>هاتف آخر</td><td><?= htmlspecialchars($empData['ohter_phone'] ?? '-') ?></td></tr>
                    <tr><td>الجنس</td><td><?= $sexMap[$empData['Sex'] ?? 0] ?? '-' ?></td></tr>
                    <tr><td>الحالة الاجتماعية</td><td><?= $maritalMap[$empData['marital_status'] ?? 0] ?? '-' ?></td></tr>
                    <tr><td>العنوان</td><td><?= htmlspecialchars($empData['user_address'] ?? '-') ?></td></tr>
                    <tr><td>الحالة الصحية</td><td><?= htmlspecialchars($empData['HealthCondition'] ?? '-') ?></td></tr>
                    <tr><td>تاريخ الالتحاق</td><td><?= htmlspecialchars($empData['CreatedDate'] ?? '-') ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Employment Info -->
        <div class="col-lg-6 mb-4">
            <div class="ess-form-card-enhanced h-100">
                <div class="card-title-ess"><i class="fas fa-briefcase"></i> المعلومات الوظيفية</div>
                <table class="info-table">
                    <tr><td>الفرع</td><td><?= htmlspecialchars($empData['BranchName'] ?? '-') ?></td></tr>
                    <tr><td>القسم</td><td><?= htmlspecialchars($empData['SectionName'] ?? '-') ?></td></tr>
                    <tr><td>المسمى الوظيفي</td><td><?= htmlspecialchars($empData['JobTitleName'] ?? '-') ?></td></tr>
                    <tr><td>الدرجة الوظيفية</td><td><?= htmlspecialchars($empData['GradeName'] ?? '-') ?></td></tr>
                    <tr><td>المجموعة</td><td><?= htmlspecialchars($empData['GroupName'] ?? '-') ?></td></tr>
                    <tr><td>نمط العمل</td><td><?= htmlspecialchars($empData['ShiftName'] ?? '-') ?></td></tr>
                    <tr><td>المدير المباشر</td><td><?= $empData['MgrFirst'] ? htmlspecialchars(trim($empData['MgrFirst'] . ' ' . ($empData['MgrLast'] ?? ''))) : '-' ?></td></tr>
                    <tr><td>الراتب</td><td><?= number_format((float)($empData['Salary'] ?? 0), 2) ?> <?= $empData['Currency'] ?? 'SAR' ?></td></tr>
                    <tr><td>بداية العقد</td><td><?= htmlspecialchars($empData['new_s_date'] ?? '-') ?></td></tr>
                    <tr><td>نهاية العقد</td><td><?= htmlspecialchars($empData['new_e_date'] ?? '-') ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Banking Info -->
        <div class="col-lg-6 mb-4">
            <div class="ess-form-card-enhanced h-100">
                <div class="card-title-ess"><i class="fas fa-university"></i> المعلومات البنكية والتأمين</div>
                <table class="info-table">
                    <tr><td>شركة التأمين</td><td><?= htmlspecialchars($empData['InsuranceName'] ?? '-') ?></td></tr>
                    <tr><td>اسم البنك</td><td><?= htmlspecialchars($empData['user_bank_name'] ?? '-') ?></td></tr>
                    <tr><td>رقم الحساب</td><td><?= htmlspecialchars($empData['user_account_bank'] ?? '-') ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Documents -->
        <div class="col-lg-6 mb-4">
            <div class="ess-form-card-enhanced h-100">
                <div class="card-title-ess"><i class="fas fa-file-alt"></i> الوثائق والمستندات</div>
                <?php
                $documents = [
                    ['الهوية الوطنية', $empData['Id_h'], $empData['start_date_h'], $empData['end_date_h'], 'fas fa-id-card'],
                    ['رخصة القيادة', $empData['Id_license'], $empData['start_date_license'], $empData['end_date_license'], 'fas fa-car'],
                    ['جواز السفر', $empData['Id_passport'], $empData['start_date_passport'], $empData['end_date_passport'], 'fas fa-passport'],
                    ['التأمين الصحي', $empData['Id_health'], $empData['start_date_health'], $empData['end_date_health'], 'fas fa-heartbeat'],
                ];
                foreach ($documents as [$label, $number, $start, $end, $icon]):
                    $daysLeft = $end ? (int)((strtotime($end) - time()) / 86400) : null;
                    $statusClass = '';
                    if ($daysLeft !== null && $daysLeft < 0) $statusClass = 'expired';
                    elseif ($daysLeft !== null && $daysLeft <= 30) $statusClass = 'expiring';
                ?>
                <div class="doc-card <?= $statusClass ?>">
                    <div class="doc-title"><i class="<?= $icon ?>"></i> <?= $label ?></div>
                    <div class="doc-meta">
                        <?php if ($number): ?>
                            <span>رقم: <strong><?= htmlspecialchars($number) ?></strong></span>
                            <?php if ($start): ?> &nbsp;|&nbsp; إصدار: <?= $start ?><?php endif; ?>
                            <?php if ($end): ?> &nbsp;|&nbsp; انتهاء: <?= $end ?>
                                <?php if ($daysLeft !== null): ?>
                                    &nbsp;
                                    <?php if ($daysLeft < 0): ?>
                                        <span class="badge badge-danger">منتهي</span>
                                    <?php elseif ($daysLeft <= 30): ?>
                                        <span class="badge badge-warning"><?= $daysLeft ?> يوم</span>
                                    <?php else: ?>
                                        <span class="badge badge-success"><?= $daysLeft ?> يوم</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">غير مسجل</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="clearfix"></div>

    <?php else: ?>
    <div class="text-center py-5">
        <i class="fas fa-user-slash fa-3x text-muted mb-3 d-block"></i>
        <p class="text-muted">لم يتم العثور على بيانات الموظف</p>
    </div>
    <?php endif; ?>

</div>
</section>

<?php include_once('inc/footer.php'); ?>
