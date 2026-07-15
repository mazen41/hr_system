<?php
/**
 * Promotions Management
 * Handle promotion requests with violation checks and configurable blocking modes
 */
$screen = 'إدارة الترقيات'; // More specific screen title
$page_title = 'إدارة الترقيات';
include_once('inc/header.php');

if (!$User->userIsAdmin() && !$User->userIsEmployer()) {
    header('Location: ess-dashboard');
    exit;
}

require_once 'classes/PromotionManager.php';
$promotionManager = new PromotionManager($connect_pdo);

$policies = $promotionManager->getPolicies(false);
$requests = $promotionManager->getRequests(); // This should be comprehensive with all joins needed

// Get employees, grades, job titles for forms
$employees = $connect_pdo->query("
    SELECT u.UserID, u.FirstName, u.LastName, u.Photo, u.CreatedDate,
           jg.Name as grade_name, jt.Name as job_title, s.Name as section_name, r.Salary as current_salary, r.GradeID as current_grade_id, r.jobtitleID as current_jobtitle_id
    FROM tblusers u
    LEFT JOIN tblremewal r ON r.Id = u.lastversion
    LEFT JOIN tbljobgrade jg ON jg.Id = r.GradeID
    LEFT JOIN tbljobtitle jt ON jt.Id = r.jobtitleID
    LEFT JOIN tblsection s ON s.Id = r.SectionID
    WHERE u.isemp = 1
    ORDER BY u.FirstName
")->fetchAll(PDO::FETCH_ASSOC);

$grades = $connect_pdo->query("SELECT * FROM tbljobgrade ORDER BY Name")->fetchAll(PDO::FETCH_ASSOC);
$jobTitles = $connect_pdo->query("SELECT * FROM tbljobtitle ORDER BY Name")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* General UI Enhancements */
.page-title {
    color: #0d21a5;
    font-weight: 700;
}
.page-nav {
    background-color: #f8fafc;
    padding: 15px 20px;
    border-bottom: 1px solid #e5e7eb;
    border-radius: 8px 8px 0 0;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05); /* Added shadow for depth */
}
.d-flex.gap-2 {
    gap: 10px; /* Consistent gap for button groups */
}
.btn-outline-primary {
    border-color: #0d21a5;
    color: #0d21a5;
}
.btn-outline-primary:hover {
    background-color: #0d21a5;
    color: #fff;
}
.btn-success:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}
.btn-danger:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}
.btn-outline-info:hover {
    background-color: #17a2b8;
    color: #fff;
}


/* Modal Specific Styles */
.modal-dialog {
    margin: 10px auto;
    max-width: 95%;
}
@media (min-width: 576px) {
    .modal-dialog { max-width: 500px; }
    .modal-lg { max-width: 800px; }
    .modal-xl { max-width: 1140px; }
}
.modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}
.modal-header {
    background: linear-gradient(135deg, #0d21a5 0%, #1e3a8a 100%);
    color: #fff;
    border-radius: 12px 12px 0 0;
    padding: 15px 20px;
}
.modal-header .close {
    color: #fff;
    opacity: 0.8;
    text-shadow: none;
}
.modal-header .close:hover { opacity: 1; }
.modal-title { font-weight: 600; }
.modal-body {
    padding: 20px;
    max-height: calc(100vh - 180px);
    overflow-y: auto;
    color: #333; /* Default text color */
}
.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #e5e7eb;
}
@media (max-width: 768px) {
    .modal-body { padding: 15px; }
}

/* Promotion Card UI */
.promotion-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08); /* Softer shadow */
    border-right: 6px solid #0d21a5; /* Thicker border */
    transition: all 0.3s ease-in-out; /* Smoother transition */
    display: flex; /* Use flex for layout */
    flex-direction: column;
}

.promotion-card:hover {
    transform: translateY(-3px); /* More noticeable lift */
    box-shadow: 0 8px 20px rgba(0,0,0,0.12); /* Stronger hover shadow */
}

.promotion-card.has-violations {
    border-right-color: #f59e0b; /* Warning orange */
}

.promotion-card.rejected {
    border-right-color: #ef4444; /* Error red */
    opacity: 0.85; /* Slightly less opaque */
}

.promotion-card.approved {
    border-right-color: #10b981; /* Success green */
}

.promotion-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px dashed #eee; /* Dotted separator */
}

.promotion-card-title {
    font-size: 20px; /* Larger title */
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0;
}

.promotion-card-meta {
    font-size: 14px;
    color: #6b7280;
    margin-top: 5px;
}
.promotion-card-meta i {
    margin-left: 5px;
    color: #9ca3af;
}

.status-flow {
    display: flex;
    align-items: center;
    gap: 8px; /* More gap */
    flex-wrap: wrap;
    margin-top: 15px;
}

.status-step {
    padding: 6px 14px; /* Larger padding */
    border-radius: 25px; /* More rounded */
    font-size: 12px;
    font-weight: 600;
    background: #e5e7eb; /* Lighter default */
    color: #4b5563; /* Darker default text */
    white-space: nowrap;
}

.status-step.done {
    background: #d1fae5; /* Success green light */
    color: #065f46; /* Success green dark */
}

.status-step.current {
    background: #bfdbfe; /* Primary blue light */
    color: #1d4ed8; /* Primary blue dark */
    font-weight: 700; /* Bolder current step */
    box-shadow: 0 2px 5px rgba(0,100,200,0.2); /* Shadow for current */
}

.status-step.rejected {
    background: #fee2e2;
    color: #991b1b;
}

.arrow-icon {
    color: #9ca3af; /* Softer arrow color */
    font-size: 14px; /* Larger arrow */
    margin: 0 -2px; /* Slight overlap */
}

.violation-alert {
    background: #fffbe6; /* Lighter yellow */
    border: 1px solid #fde047; /* Yellow border */
    border-left: 4px solid #fbbf24; /* Stronger left border */
    border-radius: 8px;
    padding: 15px;
    margin-top: 20px;
}

.violation-alert .title {
    font-weight: 700; /* Bolder title */
    color: #b45309; /* Darker orange */
    margin-bottom: 8px;
    font-size: 15px;
}

.violation-item {
    font-size: 13px;
    color: #842e0b; /* Even darker orange */
    padding: 3px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.violation-item i {
    color: #f59e0b;
}


.position-change {
    display: flex;
    align-items: center;
    justify-content: center; /* Center content */
    gap: 20px; /* More space */
    padding: 15px;
    background: #f8fafc; /* Light background */
    border-radius: 10px; /* More rounded */
    margin-top: 15px;
    flex-wrap: wrap; /* Allow wrapping on small screens */
}

.position-box {
    flex: 1;
    min-width: 120px; /* Ensure minimum width */
    text-align: center;
    padding: 8px;
    border: 1px dashed #d1d5db; /* Dashed border */
    border-radius: 6px;
    background: #fff;
}

.position-box .label {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 2px;
    font-weight: 500;
}

.position-box .value {
    font-weight: 700; /* Bolder value */
    color: #1f2937;
    font-size: 16px;
}

.position-arrow {
    color: #10b981; /* Success green arrow */
    font-size: 24px; /* Larger arrow */
}

.policy-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
}

.policy-card .title {
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 10px;
    font-size: 18px;
}

.policy-mode {
    display: inline-flex;
    align-items: center;
    gap: 8px; /* More gap */
    padding: 8px 15px; /* Larger padding */
    border-radius: 25px; /* More rounded */
    font-size: 13px;
    font-weight: 600;
}

.policy-mode.block {
    background: #fee2e2;
    color: #991b1b;
}

.policy-mode.warn_allow {
    background: #fef3c7;
    color: #92400e;
}

.policy-mode.notify_only {
    background: #dbeafe;
    color: #1e40af;
}

.eligibility-result {
    padding: 18px; /* Larger padding */
    border-radius: 10px;
    margin-top: 15px;
    display: flex; /* Flex for icon and text */
    align-items: center;
    gap: 12px;
    font-size: 15px;
    font-weight: 600;
}

.eligibility-result i {
    font-size: 24px;
}

.eligibility-result.eligible {
    background: #d1fae5;
    border: 1px solid #6ee7b7;
    color: #065f46;
}

.eligibility-result.warning {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    color: #92400e;
}

.eligibility-result.blocked {
    background: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
}
.eligibility-result ul {
    margin-top: 10px;
    margin-bottom: 0;
    padding-left: 20px;
    font-weight: normal;
}
.eligibility-result ul li {
    font-size: 14px;
    color: inherit; /* Inherit color from parent */
}


.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px; /* More gap */
    margin-bottom: 30px; /* More margin */
}

.stat-box {
    background: white;
    border-radius: 12px; /* More rounded */
    padding: 20px; /* More padding */
    text-align: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.06); /* Softer shadow */
    border-top: 3px solid #0d21a5; /* Distinct top border */
}

.stat-box .num {
    font-size: 32px; /* Larger number */
    font-weight: 800; /* Bolder number */
    color: #0d21a5; /* Primary color */
}

.stat-box .txt {
    font-size: 13px;
    color: #6b7280;
    margin-top: 5px;
}

.form-row-responsive {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .promotion-card {
        padding: 15px;
    }
    .position-change {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    .position-arrow {
        transform: rotate(90deg);
        align-self: center;
    }
    .promotion-card-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .promotion-card-title {
        font-size: 18px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    .form-row-responsive {
        grid-template-columns: 1fr;
    }
    .status-flow {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    .arrow-icon {
        transform: rotate(90deg);
        margin: 0 auto;
        display: none; /* Hide arrows on very small screens for vertical list */
    }
    .status-step {
        width: 100%;
        text-align: center;
    }
    .page-nav {
        flex-direction: column;
        align-items: flex-start;
    }
    .page-nav .d-flex.gap-2 {
        width: 100%;
        flex-direction: column;
    }
    .page-nav .d-flex.gap-2 .btn {
        width: 100%;
    }
}

/* Modal for Promotion Details */
.modal-details-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px dotted #e0e0e0;
}
.modal-details-item:last-child {
    border-bottom: none;
}
.modal-details-label {
    font-weight: 600;
    color: #4a5568;
    flex-basis: 40%;
}
.modal-details-value {
    color: #2d3748;
    flex-basis: 60%;
    text-align: right;
}
.modal-details-value .badge {
    vertical-align: middle;
}
.modal-details-section-title {
    font-size: 1.1em;
    font-weight: 700;
    color: #0d21a5;
    margin-top: 20px;
    margin-bottom: 10px;
    padding-bottom: 5px;
    border-bottom: 2px solid #0d21a5;
}
.modal-user-info {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
    padding: 10px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}
.modal-user-photo {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
    box-shadow: 0 0 5px rgba(0,0,0,0.1);
}
.modal-user-name {
    font-size: 1.2em;
    font-weight: 700;
    color: #1a202c;
}
.modal-user-position {
    font-size: 0.9em;
    color: #6b7280;
}
</style>

<div class="page-nav d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="mb-1 page-title"><i class="fas fa-arrow-up text-success"></i> إدارة الترقيات</h4>
        <p class="text-muted mb-0">طلبات الترقية مع فحص المخالفات والأهلية</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" data-toggle="modal" data-target="#policiesModal">
            <i class="fas fa-cog"></i> سياسات الترقيات
        </button>
        <button class="btn btn-primary" data-toggle="modal" data-target="#newRequestModal" onclick="resetRequestForm()">
            <i class="fas fa-plus"></i> طلب ترقية جديد
        </button>
    </div>
</div>

<section class="content py-3">
<div class="container-fluid">

    <!-- Stats -->
    <?php
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'with_violations' => 0];
    foreach ($requests as $r) {
        $stats['total']++;
        if (in_array($r['status'], ['pending', 'manager_approved', 'hr_approved'])) $stats['pending']++;
        if ($r['status'] === 'approved') $stats['approved']++;
        if ($r['status'] === 'rejected') $stats['rejected']++;
        if ($r['has_violations']) $stats['with_violations']++;
    }
    ?>
    <div class="stats-grid">
        <div class="stat-box">
            <div class="num" style="color:#0d21a5;"><?= $stats['total'] ?></div>
            <div class="txt">إجمالي الطلبات</div>
        </div>
        <div class="stat-box">
            <div class="num" style="color:#3b82f6;"><?= $stats['pending'] ?></div>
            <div class="txt">قيد الانتظار</div>
        </div>
        <div class="stat-box">
            <div class="num" style="color:#10b981;"><?= $stats['approved'] ?></div>
            <div class="txt">تمت الموافقة</div>
        </div>
        <div class="stat-box">
            <div class="num" style="color:#ef4444;"><?= $stats['rejected'] ?></div>
            <div class="txt">مرفوضة</div>
        </div>
        <div class="stat-box">
            <div class="num" style="color:#f59e0b;"><?= $stats['with_violations'] ?></div>
            <div class="txt">بها مخالفات</div>
        </div>
    </div>

    <!-- Requests List -->
    <div class="row">
        <div class="col-12">
            <?php if (empty($requests)): ?>
            <div class="text-center py-5">
                <i class="fas fa-arrow-up fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">لا توجد طلبات ترقية</h5>
                <p class="text-muted">قم بإنشاء طلب ترقية جديد للبدء</p>
            </div>
            <?php endif; ?>

            <?php foreach ($requests as $r): 
                $cardClass = '';
                if (($r['status'] ?? '') === 'approved') $cardClass = 'approved';
                elseif (($r['status'] ?? '') === 'rejected') $cardClass = 'rejected';
                elseif (($r['has_violations'] ?? false)) $cardClass = 'has-violations';
            ?>
            <div class="promotion-card <?= $cardClass ?>">
                <div class="promotion-card-header">
                    <div>
                        <div class="promotion-card-title"><?= htmlspecialchars($r['emp_first'] ?? '' . ' ' . $r['emp_last'] ?? '') ?></div>
                        <div class="promotion-card-meta">
                            <i class="fas fa-id-badge"></i> طلب ترقية رقم <?= $r['id'] ?? '' ?> |
                            <i class="fas fa-calendar-alt"></i> تاريخ الطلب: <?= date('Y-m-d', strtotime($r['created_at'] ?? 'now')) ?>
                        </div>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <?php if (($r['has_violations'] ?? false)): ?>
                        <span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> مخالفات</span>
                        <?php endif; ?>
                        <?php if (($r['violation_override'] ?? false)): ?>
                        <span class="badge badge-info"><i class="fas fa-check"></i> تم التجاوز</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <p class="text-muted mb-2">
                    <i class="fas fa-building"></i> <?= htmlspecialchars($r['section_name'] ?? '-') ?>
                </p>
                
                <!-- Position Change -->
                <div class="position-change">
                    <div class="position-box">
                        <div class="label">الدرجة الحالية</div>
                        <div class="value"><?= htmlspecialchars($r['current_grade_name'] ?? '-') ?></div>
                    </div>
                    <i class="fas fa-arrow-left position-arrow"></i>
                    <div class="position-box">
                        <div class="label">الدرجة المقترحة</div>
                        <div class="value"><?= htmlspecialchars($r['proposed_grade_name'] ?? '-') ?></div>
                    </div>
                    <?php if (($r['current_salary'] ?? 0) && ($r['proposed_salary'] ?? 0)): ?>
                    <i class="fas fa-dollar-sign position-arrow"></i>
                    <div class="position-box">
                        <div class="label">الراتب الحالي</div>
                        <div class="value"><?= number_format($r['current_salary']) ?></div>
                    </div>
                    <i class="fas fa-arrow-left position-arrow"></i>
                    <div class="position-box">
                        <div class="label">الراتب المقترح</div>
                        <div class="value"><?= number_format($r['proposed_salary']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Status Flow -->
                <div class="status-flow mt-3">
                    <span class="status-step <?= (in_array(($r['status'] ?? ''), ['pending', 'manager_approved', 'hr_approved', 'approved', 'rejected'])) ? 'done' : 'current' ?>">تقديم</span>
                    <i class="fas fa-chevron-left arrow-icon"></i>
                    <span class="status-step <?= (in_array(($r['status'] ?? ''), ['manager_approved', 'hr_approved', 'approved'])) ? 'done' : ((($r['status'] ?? '') === 'pending') ? 'current' : '') ?>">موافقة المدير</span>
                    <i class="fas fa-chevron-left arrow-icon"></i>
                    <span class="status-step <?= (in_array(($r['status'] ?? ''), ['hr_approved', 'approved'])) ? 'done' : ((($r['status'] ?? '') === 'manager_approved') ? 'current' : '') ?>">موافقة HR</span>
                    <i class="fas fa-chevron-left arrow-icon"></i>
                    <span class="status-step <?= (($r['status'] ?? '') === 'approved') ? 'done' : '' ?> <?= (($r['status'] ?? '') === 'rejected') ? 'rejected' : ((($r['status'] ?? '') === 'hr_approved') ? 'current' : '') ?>">
                        <?= (($r['status'] ?? '') === 'rejected') ? 'مرفوض' : 'معتمد' ?>
                    </span>
                </div>
                
                <?php if (($r['has_violations'] ?? false) && ($r['violation_summary'] ?? null)): 
                    $violations = json_decode($r['violation_summary'], true);
                    if (!empty($violations)):
                ?>
                <div class="violation-alert">
                    <div class="title"><i class="fas fa-exclamation-triangle"></i> مخالفات مسجلة (<?= $r['violation_count'] ?? count($violations) ?>)</div>
                    <?php foreach (array_slice($violations, 0, 3) as $v): ?>
                    <div class="violation-item">
                        <i class="fas fa-circle" style="font-size:8px;"></i> <?= htmlspecialchars($v['name_ar'] ?? $v['violation_name'] ?? 'مخالفة') ?> - <?= $v['violation_date'] ?? '' ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($violations) > 3): ?>
                    <div class="violation-item text-muted">... والمزيد من المخالفات</div>
                    <?php endif; ?>
                </div>
                <?php endif; endif; ?>
                
                <div class="mt-3 pt-3 border-top d-flex gap-2 justify-content-end"> <!-- Align buttons to the end -->
                    <?php if (in_array(($r['status'] ?? ''), ['pending', 'manager_approved', 'hr_approved'])): ?>
                    <button type="button" class="btn btn-sm btn-success" onclick="approvePromotion(<?= $r['id'] ?>, <?= (($r['has_violations'] ?? false)) ? 'true' : 'false' ?>)">
                        <i class="fas fa-check"></i> موافقة
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="rejectPromotion(<?= $r['id'] ?>)"> <!-- Changed to btn-danger -->
                        <i class="fas fa-times"></i> رفض
                    </button>
                    <?php endif; ?>
                    <?php if ($User->userIsAdmin() && in_array(($r['status'] ?? ''), ['pending'])): ?>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="editPromotion(<?= $r['id'] ?>)">
                        <i class="fas fa-edit"></i> تعديل
                    </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="viewRequest(<?= $r['id'] ?>)">
                        <i class="fas fa-eye"></i> التفاصيل
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            
        </div>
    </div>

</div>
</section>

<!-- New Request Modal -->
<div class="modal fade" id="newRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-arrow-up text-success"></i> طلب ترقية جديد</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="requestForm" onsubmit="event.preventDefault();">
                <div class="modal-body">
                    <input type="hidden" name="id" id="promotionId">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>الموظف <span class="text-danger">*</span></label>
                                <select name="user_id" id="empSelect" class="form-control select2-modal-request" required onchange="checkEligibility()">
                                    <option value="">-- اختر الموظف --</option>
                                    <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['UserID'] ?>"
                                            data-grade-id="<?= htmlspecialchars($emp['current_grade_id'] ?? '') ?>"
                                            data-grade-name="<?= htmlspecialchars($emp['grade_name'] ?? '') ?>"
                                            data-jobtitle-id="<?= htmlspecialchars($emp['current_jobtitle_id'] ?? '') ?>"
                                            data-jobtitle-name="<?= htmlspecialchars($emp['job_title'] ?? '') ?>"
                                            data-salary="<?= htmlspecialchars($emp['current_salary'] ?? '') ?>"
                                            data-photo="<?= htmlspecialchars($emp['Photo'] ?? 'dist/img/avatar-default.png') ?>">
                                        <?= htmlspecialchars($emp['FirstName'] . ' ' . $emp['LastName']) ?>
                                        (<?= htmlspecialchars($emp['section_name'] ?? '') ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Eligibility Check Result -->
                    <div id="eligibilityResult" style="display:none;"></div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>الدرجة الوظيفية المقترحة</label>
                                <select name="proposed_grade_id" id="proposedGradeSelect" class="form-control select2-modal-request">
                                    <option value="">-- اختر الدرجة --</option>
                                    <?php foreach ($grades as $g): ?>
                                    <option value="<?= $g['Id'] ?>"><?= htmlspecialchars($g['Name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>المسمى الوظيفي المقترح</label>
                                <select name="proposed_job_title_id" id="proposedJobTitleSelect" class="form-control select2-modal-request">
                                    <option value="">-- اختر المسمى --</option>
                                    <?php foreach ($jobTitles as $jt): ?>
                                    <option value="<?= $jt['Id'] ?>"><?= htmlspecialchars($jt['Name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>الراتب المقترح</label>
                                <input type="number" name="proposed_salary" class="form-control" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>تاريخ السريان</label>
                                <input type="date" name="effective_date" class="form-control" value="<?= date('Y-m-d', strtotime('+1 month')) ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>المبررات</label>
                        <textarea name="justification" class="form-control" rows="3" placeholder="أسباب الترقية..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>ملاحظات الأداء</label>
                        <textarea name="performance_notes" class="form-control" rows="2" placeholder="ملاحظات حول أداء الموظف..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success" id="submitBtn">إنشاء الطلب</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Promotion Details Modal -->
<div class="modal fade" id="promotionDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> تفاصيل طلب الترقية</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="promotionDetailsContent">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="text-muted mt-2">جاري تحميل التفاصيل...</p>
                </div>
            </div>
            <div class="modal-footer d-flex gap-2 justify-content-start" id="promotionDetailsFooter">
                <!-- Action buttons will be loaded here dynamically -->
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<!-- Policies Modal -->
<div class="modal fade" id="policiesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-cog"></i> سياسات الترقيات</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <?php foreach ($policies as $p): ?>
                <div class="policy-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="title"><?= htmlspecialchars($p['policy_name_ar'] ?? '') ?></div>
                            <?php if (!empty($p['policy_name_en'])): ?>
                            <small class="text-muted"><?= htmlspecialchars($p['policy_name_en']) ?></small>
                            <?php endif; ?>
                        </div>
                        <span class="policy-mode <?= htmlspecialchars($p['violation_handling'] ?? '') ?>">
                            <?php
                            $modeLabels = [
                                'block' => '<i class="fas fa-ban"></i> منع تام',
                                'warn_allow' => '<i class="fas fa-exclamation-triangle"></i> تحذير مع السماح',
                                'notify_only' => '<i class="fas fa-info-circle"></i> إشعار فقط'
                            ];
                            echo $modeLabels[$p['violation_handling'] ?? ''] ?? htmlspecialchars($p['violation_handling'] ?? '');
                            ?>
                        </span>
                    </div>
                    <div class="mt-3 d-flex gap-3 flex-wrap" style="font-size:13px;">
                        <span><i class="fas fa-clock text-muted"></i> الحد الأدنى للخدمة: <?= $p['min_service_months'] ?? 0 ?> شهر</span>
                        <span><i class="fas fa-history text-muted"></i> فترة فحص المخالفات: <?= $p['violation_lookback_months'] ?? 0 ?> شهر</span>
                        <?php if (($p['requires_manager_approval'] ?? false)): ?>
                        <span><i class="fas fa-user-tie text-muted"></i> موافقة المدير</span>
                        <?php endif; ?>
                        <?php if (($p['requires_hr_approval'] ?? false)): ?>
                        <span><i class="fas fa-users-cog text-muted"></i> موافقة HR</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($policies)): ?>
                <div class="text-center py-4">
                    <p class="text-muted">لا توجد سياسات ترقيات</p>
                </div>
                <?php endif; ?>
                
                <?php if ($User->userIsAdmin()): ?>
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-outline-primary" onclick="$('#policiesModal').modal('hide'); $('#addPolicyModal').modal('show');">
                        <i class="fas fa-plus"></i> إضافة سياسة
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Policy Modal -->
<div class="modal fade" id="addPolicyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> إضافة سياسة ترقيات</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="policyForm" onsubmit="event.preventDefault();">
                <div class="modal-body">
                    <div class="form-group">
                        <label>اسم السياسة بالعربية <span class="text-danger">*</span></label>
                        <input type="text" name="policy_name_ar" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>طريقة التعامل مع المخالفات <span class="text-danger">*</span></label>
                        <select name="violation_handling" class="form-control" required>
                            <option value="block">منع تام - لا يمكن الترقية مع وجود مخالفات</option>
                            <option value="warn_allow" selected>تحذير مع السماح - عرض تحذير والسماح بالتجاوز</option>
                            <option value="notify_only">إشعار فقط - عرض المخالفات كمعلومات</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>الحد الأدنى للخدمة (أشهر)</label>
                                <input type="number" name="min_service_months" class="form-control" value="12" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>فترة فحص المخالفات (أشهر)</label>
                                <input type="number" name="violation_lookback_months" class="form-control" value="12" min="1">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>درجات الخطورة المانعة</label>
                        <select name="blocking_violation_severities[]" class="form-control select2-modal-policy" multiple>
                            <option value="minor">بسيطة</option>
                            <option value="moderate">متوسطة</option>
                            <option value="major" selected>جسيمة</option>
                            <option value="critical" selected>خطيرة</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="d-flex align-items-center gap-2">
                                    <input type="checkbox" name="requires_manager_approval" value="1" checked>
                                    يتطلب موافقة المدير
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="d-flex align-items-center gap-2">
                                    <input type="checkbox" name="requires_hr_approval" value="1" checked>
                                    يتطلب موافقة HR
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once('inc/footer.php'); ?>

<script>
console.log("admin-promotions.php script block started."); // Debug log

$(document).ready(function() {
    console.log("Document ready event fired."); // Debug log

    // Initialize Select2 specifically for New Request Modal
    if ($.fn.select2) {
        console.log("Select2 is available. Initializing select2 for New Request Modal...");
        $('#newRequestModal .select2-modal-request').select2({
            dir: 'rtl',
            width: '100%',
            dropdownParent: $('#newRequestModal')
        });
        console.log("Select2 initialization for New Request Modal complete.");

        // Initialize Select2 specifically for Add Policy Modal
        console.log("Initializing select2 for Add Policy Modal...");
        $('#addPolicyModal .select2-modal-policy').select2({
            dir: 'rtl',
            width: '100%',
            dropdownParent: $('#addPolicyModal')
        });
        console.log("Select2 initialization for Add Policy Modal complete.");
    } else {
        console.warn("Select2 is NOT available.");
    }

    // Reset Request Form (for new promotion request)
    window.resetRequestForm = function() {
        console.log("resetRequestForm called.");
        $('#requestForm')[0].reset();
        $('#empSelect').val('').trigger('change'); // Clear Select2 dropdown
        $('#proposedGradeSelect').val('').trigger('change');
        $('#proposedJobTitleSelect').val('').trigger('change');
        $('#eligibilityResult').hide().empty();
        $('#submitBtn').prop('disabled', false); // Enable submit button by default
    }

    // Check Eligibility when employee selected
    window.checkEligibility = function() {
        var userId = $('#empSelect').val();
        console.log("checkEligibility called for userId:", userId);

        if (!userId) {
            $('#eligibilityResult').hide().empty();
            $('#submitBtn').prop('disabled', false); // Re-enable if no employee selected
            return;
        }
        
        $('#eligibilityResult').html('<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> جاري فحص الأهلية...</div>').show();
        $('#submitBtn').prop('disabled', true); // Disable while checking eligibility
        
        $.get('hr-app/index.php?action=check-promotion-eligibility&user_id=' + userId, function(res) {
            console.log("Eligibility response received: ", res);
            if(typeof res === 'string') {
                try { res = JSON.parse(res); } catch(e) {
                    toastr.error('خطأ في تحليل استجابة الخادم عند فحص الأهلية.');
                    console.error("JSON Parse Error: ", e, "Response: ", res);
                    $('#eligibilityResult').html('<div class="eligibility-result blocked"><i class="fas fa-times-circle text-danger"></i> <strong>خطأ في تحليل الاستجابة.</strong></div>');
                    return;
                }
            }

            if (res.result && res.data) {
                var e = res.data;
                var html = '';
                
                if (e.eligible) {
                    html = '<div class="eligibility-result eligible"><i class="fas fa-check-circle"></i> <strong>' + e.message + '</strong>';
                    html += '<div class="mt-2 text-muted"><small>مدة الخدمة: ' + e.service_months + ' شهر</small></div>';
                    html += '</div>';
                    $('#submitBtn').prop('disabled', false);
                } else if (e.can_override) {
                    html = '<div class="eligibility-result warning"><i class="fas fa-exclamation-triangle"></i> <strong>' + e.message + '</strong>';
                    if (e.blocking_violations && e.blocking_violations.length > 0) {
                        html += '<div class="mt-2 text-muted">';
                        e.blocking_violations.forEach(function(v) {
                            html += '<div class="violation-item">• ' + (v.name_ar || v.violation_name || 'مخالفة') + ' - ' + (v.violation_date || '') + '</div>';
                        });
                        html += '</div>';
                    }
                    html += '<div class="mt-2 text-muted"><small>يمكن المتابعة مع التأكيد عند الموافقة.</small></div>';
                    html += '</div>';
                    $('#submitBtn').prop('disabled', false);
                } else {
                    html = '<div class="eligibility-result blocked"><i class="fas fa-ban"></i> <strong>' + e.message + '</strong>';
                    if (e.issues && e.issues.length > 0) {
                        html += '<ul class="mt-2 mb-0">';
                        e.issues.forEach(function(issue) {
                            html += '<li>' + (issue.message || 'مشكلة غير محددة') + '</li>';
                        });
                        html += '</ul>';
                    }
                    html += '</div>';
                    $('#submitBtn').prop('disabled', true); // Keep disabled if blocked
                }
                
                $('#eligibilityResult').html(html);
            } else {
                toastr.error(res.msg || 'فشل فحص الأهلية.');
                console.error("Server Error (checkEligibility): ", res.msg, res.data, res.debug_session);
                $('#eligibilityResult').html('<div class="eligibility-result blocked"><i class="fas fa-times-circle text-danger"></i> <strong>' + (res.msg || 'فشل فحص الأهلية.') + '</strong></div>');
                $('#submitBtn').prop('disabled', true);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            toastr.error('فشل في الاتصال بالخادم عند فحص الأهلية: ' + textStatus);
            console.error("AJAX Fail (checkEligibility): ", textStatus, errorThrown, jqXHR.responseText);
            $('#eligibilityResult').html('<div class="eligibility-result blocked"><i class="fas fa-times-circle text-danger"></i> <strong>فشل في الاتصال بالخادم.</strong></div>');
            $('#submitBtn').prop('disabled', true);
        });
    }

    // Submit new promotion request
    $('#requestForm').on('submit', function(e) {
        e.preventDefault();
        console.log("requestForm submitted via AJAX!");
        var formData = $(this).serialize();
        console.log("Form Data: ", formData);
        
        $.post('hr-app/index.php?action=create-promotion-request', formData, function(res) {
            console.log("create-promotion-request response received: ", res);
            if(typeof res === 'string') {
                try { res = JSON.parse(res); } catch(e) {
                    toastr.error('خطأ في تحليل استجابة الخادم.');
                    console.error("JSON Parse Error: ", e, "Response: ", res);
                    return;
                }
            }
            if (res.result) {
                toastr.success(res.msg || 'تم إنشاء طلب الترقية بنجاح.');
                $('#newRequestModal').modal('hide');
                setTimeout(function(){ location.reload(); }, 1000);
            } else {
                toastr.error(res.msg || 'حدث خطأ أثناء إنشاء الطلب.');
                console.error("Server Error (create-promotion-request): ", res.msg, res.data, res.debug_session);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            toastr.error('فشل في الاتصال بالخادم: ' + textStatus);
            console.error("AJAX Fail (create-promotion-request): ", textStatus, errorThrown, jqXHR.responseText);
        });
    });

    // Approve promotion
    window.approvePromotion = function(id, hasViolations) {
        console.log("approvePromotion called for ID:", id, "Has violations:", hasViolations);
        if (hasViolations) {
            Swal.fire({
                title: 'تأكيد الموافقة',
                html: '<div class="text-right"><p>هذا الموظف لديه مخالفات مسجلة.</p><p>هل تريد تجاوز المخالفات والموافقة على الترقية؟</p></div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، موافقة مع التجاوز',
                cancelButtonText: 'إلغاء',
                input: 'textarea',
                inputPlaceholder: 'سبب التجاوز (مطلوب)...',
                inputValidator: (value) => {
                    if (!value) return 'يجب إدخال سبب التجاوز';
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    submitApproval(id, true, result.value);
                }
            });
        } else {
            Swal.fire({
                title: 'تأكيد الموافقة',
                text: 'هل أنت متأكد من الموافقة على هذه الترقية؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'نعم، موافقة',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitApproval(id, false, '');
                }
            });
        }
    }

    function submitApproval(id, override, reason) {
        console.log("submitApproval called for ID:", id, "Override:", override, "Reason:", reason);
        $.post('hr-app/index.php?action=approve-promotion', {
            id: id,
            override_violations: override ? 1 : 0,
            override_reason: reason
        }, function(res) {
            console.log("approve-promotion response received: ", res);
            if(typeof res === 'string') { try { res = JSON.parse(res); } catch(e) { console.error("JSON Parse Error: ", e); return; } }
            if (res.result) {
                toastr.success(res.msg || 'تمت الموافقة بنجاح.');
                setTimeout(function(){ location.reload(); }, 1000);
            } else {
                if (res.data && res.data.requires_override) {
                    toastr.warning(res.msg || 'يتطلب تجاوز المخالفات');
                    approvePromotion(id, true);
                } else {
                    toastr.error(res.msg || 'حدث خطأ أثناء الموافقة.');
                }
                console.error("Server Error (approve-promotion): ", res.msg, res.data, res.debug_session);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            toastr.error('فشل في الاتصال بالخادم: ' + textStatus);
            console.error("AJAX Fail (approve-promotion): ", textStatus, errorThrown, jqXHR.responseText);
        });
    }

    // Reject promotion
    window.rejectPromotion = function(id) {
        console.log("rejectPromotion called for ID:", id);
        Swal.fire({
            title: 'رفض الترقية',
            text: 'يرجى إدخال سبب الرفض',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'رفض',
            cancelButtonText: 'إلغاء',
            input: 'textarea',
            inputPlaceholder: 'سبب الرفض...',
            inputValidator: (value) => {
                if (!value) return 'يجب إدخال سبب الرفض';
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('hr-app/index.php?action=reject-promotion', {
                    id: id,
                    reason: result.value
                }, function(res) {
                    console.log("reject-promotion response received: ", res);
                    if(typeof res === 'string') { try { res = JSON.parse(res); } catch(e) { console.error("JSON Parse Error: ", e); return; } }
                    if (res.result) {
                        toastr.success(res.msg || 'تم رفض الطلب بنجاح.');
                        setTimeout(function(){ location.reload(); }, 1000);
                    } else {
                        toastr.error(res.msg || 'حدث خطأ أثناء الرفض.');
                        console.error("Server Error (reject-promotion): ", res.msg, res.data, res.debug_session);
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    toastr.error('فشل في الاتصال بالخادم: ' + textStatus);
                    console.error("AJAX Fail (reject-promotion): ", textStatus, errorThrown, jqXHR.responseText);
                });
            }
        });
    }

    // View request details (now opens a modal)
    window.viewRequest = function(id) {
        console.log("viewRequest called for ID:", id);
        $('#promotionDetailsModal').modal('show');
        $('#promotionDetailsContent').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="text-muted mt-2">جاري تحميل التفاصيل...</p></div>');
        $('#promotionDetailsFooter').html('<button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>'); // Reset footer buttons

        $.get('hr-app/index.php?action=get-promotion-details&id=' + id, function(res) {
            console.log("get-promotion-details response received: ", res);
            if(typeof res === 'string') {
                try { res = JSON.parse(res); } catch(e) {
                    $('#promotionDetailsContent').html('<div class="alert alert-danger">خطأ في تحليل استجابة الخادم.</div>');
                    console.error("JSON Parse Error: ", e, "Response: ", res);
                    return;
                }
            }

            if (res.result && res.data) {
                var p = res.data;
                // Safely access properties with null coalescing for display
                var empName = p.emp_first + ' ' + p.emp_last ?? '-'; // Corrected to use emp_first, emp_last
                var currentJobTitle = p.current_job_title_name ?? '-';
                var currentGrade = p.current_grade_name ?? '-';
                var requesterName = p.requester_first + ' ' + p.requester_last ?? '-'; // Corrected
                var promotionId = p.id ?? '-';
                var createdAt = p.created_at ? p.created_at.substring(0, 10) : '-';
                var effectiveDate = p.effective_date ?? '-';
                var proposedGrade = p.proposed_grade_name ?? '-';
                var proposedJobTitle = p.proposed_job_title_name ?? '-';
                var currentSalary = p.current_salary ? parseFloat(p.current_salary).toLocaleString() : '0';
                var proposedSalary = p.proposed_salary ? parseFloat(p.proposed_salary).toLocaleString() : '0';
                // Removed currentCurrency as it's no longer in the data
                var justification = p.justification ?? '-';
                var performanceNotes = p.performance_notes ?? '-';
                var overrideReason = p.override_reason ?? '-';
                var rejectionReason = p.rejection_reason ?? '-';

                var contentHtml = `
                    <div class="modal-user-info">
                        <img src="${p.emp_photo || 'dist/img/avatar-default.png'}" alt="${empName}" class="modal-user-photo">
                        <div>
                            <div class="modal-user-name">${empName}</div>
                            <div class="modal-user-position">${currentJobTitle} (${currentGrade})</div>
                        </div>
                    </div>

                    <div class="modal-details-section-title">معلومات الطلب</div>
                    <div class="modal-details-item"><span class="modal-details-label">رقم الطلب</span><span class="modal-details-value">${promotionId}</span></div>
                    <div class="modal-details-item"><span class="modal-details-label">تاريخ الطلب</span><span class="modal-details-value">${createdAt}</span></div>
                    <div class="modal-details-item"><span class="modal-details-label">حالة الطلب</span><span class="modal-details-value">${p.status_badge || '-'}</span></div>
                    <div class="modal-details-item"><span class="modal-details-label">المقدم</span><span class="modal-details-value">${requesterName}</span></div>
                    <div class="modal-details-item"><span class="modal-details-label">تاريخ السريان</span><span class="modal-details-value">${effectiveDate}</span></div>
                    
                    <div class="modal-details-section-title">تغيير المسمى الوظيفي والراتب</div>
                    <div class="modal-details-item"><span class="modal-details-label">الدرجة الحالية</span><span class="modal-details-value">${currentGrade}</span></div>
                    <div class="modal-details-item"><span class="modal-details-label">الدرجة المقترحة</span><span class="modal-details-value">${proposedGrade}</span></div>
                    <div class="modal-details-item"><span class="modal-details-label">المسمى الوظيفي الحالي</span><span class="modal-details-value">${currentJobTitle}</span></div>
                    <div class="modal-details-item"><span class="modal-details-label">المسمى الوظيفي المقترح</span><span class="modal-details-value">${proposedJobTitle}</span></div>
                    <div class="modal-details-item"><span class="modal-details-label">الراتب الحالي</span><span class="modal-details-value">${currentSalary}</span></div>
                    <div class="modal-details-item"><span class="modal-details-label">الراتب المقترح</span><span class="modal-details-value">${proposedSalary}</span></div>
                    
                    <div class="modal-details-section-title">المبررات والملاحظات</div>
                    <div class="modal-details-item"><span class="modal-details-label">المبررات</span><span class="modal-details-value">${justification}</span></div>
                    <div class="modal-details-item"><span class="modal-details-label">ملاحظات الأداء</span><span class="modal-details-value">${performanceNotes}</span></div>
                `;

                if (p.has_violations && p.violation_summary_parsed && p.violation_summary_parsed.length > 0) {
                    contentHtml += `<div class="modal-details-section-title text-warning"><i class="fas fa-exclamation-triangle"></i> مخالفات الموظف</div>`;
                    p.violation_summary_parsed.forEach(function(v) {
                        contentHtml += `<div class="modal-details-item"><span class="modal-details-label">${v.name_ar || v.violation_name || 'مخالفة'}</span><span class="modal-details-value">${v.violation_date || '-'} (${v.severity || '-'})</span></div>`;
                    });
                    if (p.violation_override && overrideReason) {
                        contentHtml += `<div class="modal-details-item"><span class="modal-details-label text-info">سبب التجاوز</span><span class="modal-details-value">${overrideReason}</span></div>`;
                    }
                }
                if (p.rejection_reason) {
                     contentHtml += `<div class="modal-details-item"><span class="modal-details-label text-danger">سبب الرفض</span><span class="modal-details-value">${rejectionReason}</span></div>`;
                }
                
                $('#promotionDetailsContent').html(contentHtml);

                // Dynamically load footer buttons based on status
                var footerButtons = `
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                `;
                // Only show approve/reject buttons if the current user has permission and request is in a pending state
                // Assuming $User->userIsAdmin() or $User->userIsEmployer() provides sufficient check
                // and if the status allows further approval
                <?php if ($User->userIsAdmin() || $User->userIsEmployer()): ?>
                if (['pending', 'manager_approved', 'hr_approved'].includes(p.status)) {
                    footerButtons += `
                        <button type="button" class="btn btn-success" onclick="approvePromotion(${p.id}, ${p.has_violations ? 'true' : 'false'})"><i class="fas fa-check"></i> موافقة</button>
                        <button type="button" class="btn btn-danger" onclick="rejectPromotion(${p.id})"><i class="fas fa-times"></i> رفض</button>
                    `;
                }
                <?php endif; ?>
                $('#promotionDetailsFooter').html(footerButtons);

            } else {
                $('#promotionDetailsContent').html('<div class="alert alert-danger">فشل جلب التفاصيل: ' + (res.msg || 'خطأ غير معروف') + '</div>');
                console.error("Server Error (get-promotion-details): ", res.msg, res.data, res.debug_session);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            $('#promotionDetailsContent').html('<div class="alert alert-danger">فشل في الاتصال بالخادم.</div>');
            console.error("AJAX Fail (get-promotion-details): ", textStatus, errorThrown, jqXHR.responseText);
        });
    };

    // Save policy (for #addPolicyModal)
    $('#policyForm').on('submit', function(e) {
        e.preventDefault();
        console.log("policyForm submitted via AJAX!"); // Debug log
        var formData = $(this).serialize();
        console.log("Form Data: ", formData); // Debug log form data
        
        $.post('hr-app/index.php?action=save-promotion-policy', formData, function(res) {
            console.log("save-promotion-policy response received: ", res); // Debug log
            if(typeof res === 'string') {
                try { res = JSON.parse(res); } catch(e) {
                    toastr.error('خطأ في تحليل استجابة الخادم.');
                    console.error("JSON Parse Error: ", e, "Response: ", res);
                    return;
                }
            }
            if (res.result) {
                toastr.success(res.msg || 'تم حفظ السياسة بنجاح.');
                $('#addPolicyModal').modal('hide');
                setTimeout(function(){ location.reload(); }, 1000);
            } else {
                toastr.error(res.msg || 'حدث خطأ أثناء حفظ السياسة.');
                console.error("Server Error (save-promotion-policy): ", res.msg, res.data, res.debug_session);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            toastr.error('فشل في الاتصال بالخادم: ' + textStatus);
            console.error("AJAX Fail (save-promotion-policy): ", textStatus, errorThrown, jqXHR.responseText);
        });
    });

}); // End of $(document).ready
</script>