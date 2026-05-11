<?php
/**
 * ESS - Salary Slips
 * Employee can view their salary history
 */
$screen = 'الخدمة الذاتية';
$page_title = 'كشوف الرواتب';
$ess_active = 'salary';
include_once('inc/header.php');

// Get salary records
$salaryRecords = [];
$empSalary = 0;
if ($user) {
    $stm = $connect_pdo->prepare(
        "SELECT es.Id, es.month, es.incentive, es.benefit, es.advances, 
                es.deductions, es.absent_salary, es.net_salary, es.end_salary,
                es.id_registration, es.created_date,
                sr.year, sr.BranchID
         FROM emp_salary es
         LEFT JOIN salary_registration sr ON sr.Id = es.id_registration
         WHERE es.UserID = :uid
         ORDER BY es.Id DESC"
    );
    $stm->execute([':uid' => $user]);
    $salaryRecords = $stm->fetchAll(PDO::FETCH_ASSOC);

    // Get base salary from contract
    $stm2 = $connect_pdo->prepare(
        "SELECT r.Salary, r.Currency FROM tblremewal r 
         INNER JOIN tblusers u ON u.lastversion = r.Id 
         WHERE u.UserID = :uid LIMIT 1"
    );
    $stm2->execute([':uid' => $user]);
    $contractInfo = $stm2->fetch(PDO::FETCH_ASSOC);
    $empSalary = (float)($contractInfo['Salary'] ?? 0);

    // Get benefits, deductions, incentives for current employee
    $stmBen = $connect_pdo->prepare("SELECT SUM(CAST(Amount AS DECIMAL(10,2))) as total FROM tblbenefit WHERE UserID = :uid AND Status = 1");
    $stmBen->execute([':uid' => $user]);
    $totalBenefits = (float)($stmBen->fetchColumn() ?: 0);

    $stmDed = $connect_pdo->prepare("SELECT SUM(CAST(Amount AS DECIMAL(10,2))) as total FROM tbldeductions WHERE UserID = :uid AND Status = 1");
    $stmDed->execute([':uid' => $user]);
    $totalDeductions = (float)($stmDed->fetchColumn() ?: 0);

    $stmInc = $connect_pdo->prepare("SELECT SUM(CAST(Amount AS DECIMAL(10,2))) as total FROM tblincentives WHERE UserID = :uid AND Status = 1");
    $stmInc->execute([':uid' => $user]);
    $totalIncentives = (float)($stmInc->fetchColumn() ?: 0);

    $stmAdv = $connect_pdo->prepare("SELECT SUM(CAST(Amount AS DECIMAL(10,2))) as total FROM tblempadvances WHERE UserID = :uid AND Status = 1");
    $stmAdv->execute([':uid' => $user]);
    $totalAdvances = (float)($stmAdv->fetchColumn() ?: 0);
}

$netEstimate = $empSalary + $totalBenefits + $totalIncentives - $totalDeductions - $totalAdvances;
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
.salary-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.salary-item {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    text-align: center;
    border: 1px solid #f0f0f0;
}
.salary-item .amount {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e3a5f;
    margin-bottom: 4px;
}
.salary-item .label {
    font-size: 0.85rem;
    color: #6b7280;
}
.salary-item.positive .amount { color: #059669; }
.salary-item.negative .amount { color: #dc2626; }
.salary-item.net .amount { color: #1e3a5f; }

.salary-slip-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    transition: all 0.2s;
}
.salary-slip-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.salary-slip-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}
.salary-slip-header .period {
    font-weight: 700;
    font-size: 1.05rem;
    color: #1e3a5f;
}
.salary-slip-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px;
}
.salary-slip-details .detail-item small {
    color: #6b7280;
    display: block;
}
.salary-slip-details .detail-item strong {
    font-size: 0.95rem;
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
    .salary-summary-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        margin-bottom: 20px;
    }
    .salary-item {
        padding: 20px;
        text-align: center;
    }
    .salary-item .amount {
        font-size: 1.5rem;
        margin-bottom: 4px;
    }
    .salary-item .label {
        font-size: 0.85rem;
    }
    .salary-slip-card {
        padding: 20px;
        margin-bottom: 16px;
    }
    .salary-slip-header {
        flex-direction: column;
        gap: 8px;
        text-align: center;
    }
    .salary-slip-details {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
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
    .salary-summary-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 16px;
    }
    .salary-item {
        padding: 16px;
        text-align: center;
    }
    .salary-item .amount {
        font-size: 1.3rem;
        margin-bottom: 4px;
    }
    .salary-item .label {
        font-size: 0.8rem;
    }
    .salary-slip-card {
        padding: 16px;
        margin-bottom: 12px;
    }
    .salary-slip-header {
        flex-direction: column;
        gap: 6px;
        text-align: center;
    }
    .salary-slip-header .period {
        font-size: 1rem;
    }
    .salary-slip-details {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    .salary-slip-details .detail-item strong {
        font-size: 0.9rem;
    }
    .salary-slip-details .detail-item small {
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
    .salary-summary-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        margin-bottom: 12px;
    }
    .salary-item {
        padding: 12px;
        text-align: center;
    }
    .salary-item .amount {
        font-size: 1.1rem;
        margin-bottom: 2px;
    }
    .salary-item .label {
        font-size: 0.75rem;
    }
    .salary-slip-card {
        padding: 12px;
        margin-bottom: 8px;
    }
    .salary-slip-header {
        flex-direction: column;
        gap: 4px;
        text-align: center;
    }
    .salary-slip-header .period {
        font-size: 0.95rem;
    }
    .salary-slip-details {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    .salary-slip-details .detail-item strong {
        font-size: 0.85rem;
    }
    .salary-slip-details .detail-item small {
        font-size: 0.7rem;
    }
}

/* Touch device optimizations */
@media (hover: none) and (pointer: coarse) {
    .ess-form-card:hover {
        transform: none;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }
    .salary-item:hover {
        transform: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .salary-slip-card:hover {
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
    .salary-summary-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        margin-bottom: 8px;
    }
    .salary-item {
        padding: 8px;
    }
    .salary-item .amount {
        font-size: 1rem;
    }
    .salary-item .label {
        font-size: 0.7rem;
    }
    .salary-slip-card {
        padding: 8px;
        margin-bottom: 6px;
    }
    .salary-slip-header {
        flex-direction: row;
        justify-content: space-between;
    }
    .salary-slip-details {
        grid-template-columns: repeat(4, 1fr);
        gap: 6px;
    }
}
</style>

<div class="content-header page-nav">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <span class="page-title"><i class="fas fa-wallet"></i> كشوف الرواتب</span>
            </div>
        </div>
    </div>
</div>

<section class="content">
<div class="container-fluid">

    <!-- Salary Summary -->
    <div class="ess-form-card-enhanced">
        <div class="card-title-ess"><i class="fas fa-chart-pie"></i> ملخص الراتب الحالي</div>
        <div class="salary-summary-grid">
            <div class="salary-summary-item">
                <div class="value"><?= number_format($empSalary, 0) ?></div>
                <div class="label">الراتب الأساسي (ر.س)</div>
            </div>
            <div class="salary-summary-item positive">
                <div class="value">+<?= number_format($totalBenefits, 0) ?></div>
                <div class="label">التعويضات</div>
            </div>
            <div class="salary-summary-item positive">
                <div class="value">+<?= number_format($totalIncentives, 0) ?></div>
                <div class="label">الحوافز</div>
            </div>
            <div class="salary-summary-item negative">
                <div class="value">-<?= number_format($totalDeductions, 0) ?></div>
                <div class="label">الخصومات</div>
            </div>
            <div class="salary-summary-item negative">
                <div class="value">-<?= number_format($totalAdvances, 0) ?></div>
                <div class="label">السلف</div>
            </div>
            <div class="salary-summary-item net" style="background:#eef2ff;border:2px solid #3b82f6">
                <div class="value"><?= number_format($netEstimate, 0) ?></div>
                <div class="label">صافي الراتب التقديري</div>
            </div>
        </div>
    </div>

    <!-- Salary History -->
    <div class="ess-form-card-enhanced">
        <div class="card-title-ess"><i class="fas fa-history"></i> سجل الرواتب المصروفة</div>
        <?php if (empty($salaryRecords)): ?>
            <div class="text-center py-4">
                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                <p class="text-muted">لا توجد كشوف رواتب مصروفة حتى الآن</p>
            </div>
        <?php else: ?>
            <?php foreach ($salaryRecords as $slip): ?>
            <div class="salary-slip-card">
                <div class="salary-slip-header">
                    <span class="period">
                        <i class="fas fa-calendar-alt"></i>
                        شهر <?= htmlspecialchars($slip['month'] ?? '-') ?><?php if (!empty($slip['year'])): ?> / <?= htmlspecialchars($slip['year']) ?><?php endif; ?>
                    </span>
                    <span class="badge badge-success">مصروف</span>
                </div>
                <div class="salary-slip-details">
                    <div class="detail-item">
                        <small>الحوافز</small>
                        <strong class="text-success">+<?= number_format((float)($slip['incentive'] ?? 0), 2) ?></strong>
                    </div>
                    <div class="detail-item">
                        <small>التعويضات</small>
                        <strong class="text-success">+<?= number_format((float)($slip['benefit'] ?? 0), 2) ?></strong>
                    </div>
                    <div class="detail-item">
                        <small>الخصومات</small>
                        <strong class="text-danger">-<?= number_format((float)($slip['deductions'] ?? 0), 2) ?></strong>
                    </div>
                    <div class="detail-item">
                        <small>السلف</small>
                        <strong class="text-danger">-<?= number_format((float)($slip['advances'] ?? 0), 2) ?></strong>
                    </div>
                    <div class="detail-item">
                        <small>خصم الغياب</small>
                        <strong class="text-danger">-<?= number_format((float)($slip['absent_salary'] ?? 0), 2) ?></strong>
                    </div>
                    <div class="detail-item">
                        <small>صافي الراتب</small>
                        <strong style="color:#1e3a5f;font-size:1.1rem"><?= number_format((float)($slip['end_salary'] ?? $slip['net_salary'] ?? 0), 2) ?> ر.س</strong>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
</section>

<?php include_once('inc/footer.php'); ?>
