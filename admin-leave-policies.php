<?php
/**
 * Leave Policies Administration
 * Configure annual leave, accrual, carryover, and hourly leave settings
 */
$screen = 'إعدادات النظام';
$page_title = 'سياسات الإجازات';
include_once('inc/header.php');

// Check admin access
if (!$User->userIsAdmin() && !$User->userIsEmployer()) {
    header('Location: ess-dashboard');
    exit;
}

require_once 'classes/LeavePolicyManager.php';
$leaveManager = new LeavePolicyManager($connect_pdo);

// Get all policies
$policies = $leaveManager->getAllPolicies(false);

// Get leave types
$leaveTypes = $connect_pdo->query("SELECT * FROM leaveclassification ORDER BY Name")->fetchAll(PDO::FETCH_ASSOC);

// Get grades, departments, job titles for scope selection
$grades = $connect_pdo->query("SELECT * FROM tbljobgrade ORDER BY Name")->fetchAll(PDO::FETCH_ASSOC);
$departments = $connect_pdo->query("SELECT * FROM tblsection ORDER BY Name")->fetchAll(PDO::FETCH_ASSOC);
$jobTitles = $connect_pdo->query("SELECT * FROM tbljobtitle ORDER BY Name")->fetchAll(PDO::FETCH_ASSOC);
$branches = $connect_pdo->query("SELECT * FROM branches ORDER BY branch_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* Responsive Modal Styles */
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
    /*background: linear-gradient(135deg, #0d21a5 0%, #1e3a8a 100%);*/
    color: #fff;
    border-radius: 12px 12px 0 0;
    padding: 15px 20px;
}
.modal-header .close {
    /*color: #fff;*/
    margin-left: -13px;
    opacity: 0.8;
    text-shadow: none;
}
.modal-header .close:hover { opacity: 1; }
.modal-title { font-weight: 600; }
.modal-body {
    padding: 20px;
    max-height: calc(100vh - 180px);
    overflow-y: auto;
}
.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #e5e7eb;
}

@media (max-width: 768px) {
    .modal-body {
        padding: 15px;
    }
    .form-section {
        padding: 15px;
    }
    .col-md-3, .col-md-4, .col-md-6 {
        margin-bottom: 10px;
    }
    .policy-header {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    .policy-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .policy-stats {
        grid-template-columns: 1fr;
    }
    .policy-card {
        padding: 15px;
    }
    .policy-title {
        font-size: 16px;
    }
    .toggle-label {
        width: 100%;
        justify-content: space-between;
    }
    .d-flex.gap-2 {
        flex-direction: column;
    }
    .d-flex.gap-2 .btn {
        width: 100%;
    }
}

.policy-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border-right: 4px solid #0d21a5;
    transition: all 0.3s ease;
}

.policy-card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.policy-card.inactive {
    opacity: 0.6;
    border-right-color: #9ca3af;
}

.policy-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.policy-title {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
}

.policy-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.policy-badge.active {
    background: #dcfce7;
    color: #166534;
}

.policy-badge.inactive {
    background: #f3f4f6;
    color: #6b7280;
}

.policy-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.policy-stat {
    text-align: center;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
}

.policy-stat .value {
    font-size: 24px;
    font-weight: 700;
    color: #0d21a5;
}

.policy-stat .label {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.policy-features {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 15px;
}

.feature-tag {
    padding: 4px 10px;
    background: #eff6ff;
    color: #1d4ed8;
    border-radius: 6px;
    font-size: 12px;
}

.feature-tag.disabled {
    background: #f3f4f6;
    color: #9ca3af;
    text-decoration: line-through;
}

/* Form Sections */
.form-section {
    background: #f8fafc;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
}

.form-section-title {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e5e7eb;
}

.form-section-title i {
    color: #0d21a5;
    margin-left: 8px;
}

/* Toggle Switch */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e1;
    transition: 0.3s;
    border-radius: 26px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #0d21a5;
}

input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

.toggle-label {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Info Box */
.info-box {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    padding: 12px 15px;
    margin-bottom: 15px;
    font-size: 13px;
    color: #1e40af;
}

.info-box i {
    margin-left: 8px;
}
</style>

<div class="page-nav d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="mb-1 page-title"><i class="fas fa-calendar-alt text-primary"></i> سياسات الإجازات</h4>
        <p class="text-muted mb-0">إدارة سياسات الإجازات السنوية والاستحقاق والترحيل</p>
    </div>
    <button class="btn btn-primary" data-toggle="modal" data-target="#policyModal" onclick="resetForm()">
        <i class="fas fa-plus"></i> إضافة سياسة جديدة
    </button>
</div>

<section class="content py-3">
<div class="container-fluid">

    <!-- Info Box -->
    <div class="info-box">
        <i class="fas fa-info-circle"></i>
        <strong>ملاحظة:</strong> يتم احتساب الاستحقاق الشهري تلقائياً بقسمة عدد أيام الإجازة السنوية على 12 شهر. 
        مثال: 30 يوم ÷ 12 = 2.5 يوم شهرياً.
    </div>

    <!-- Policies List -->
    <div class="row">
        <?php foreach ($policies as $policy): ?>
        <div class="col-lg-6">
            <div class="policy-card <?= $policy['is_active'] ? '' : 'inactive' ?>">
                <div class="policy-header">
                    <div>
                        <div class="policy-title"><?= htmlspecialchars($policy['policy_name_ar']) ?></div>
                        <?php if ($policy['policy_name_en']): ?>
                        <small class="text-muted"><?= htmlspecialchars($policy['policy_name_en']) ?></small>
                        <?php endif; ?>
                    </div>
                    <span class="policy-badge <?= $policy['is_active'] ? 'active' : 'inactive' ?>">
                        <?= $policy['is_active'] ? 'نشط' : 'غير نشط' ?>
                    </span>
                </div>
                
                <div class="policy-stats">
                    <div class="policy-stat">
                        <div class="value"><?= number_format($policy['annual_days'], 1) ?></div>
                        <div class="label">يوم سنوياً</div>
                    </div>
                    <div class="policy-stat">
                        <div class="value"><?= number_format($policy['monthly_accrual'], 2) ?></div>
                        <div class="label">يوم شهرياً</div>
                    </div>
                    <div class="policy-stat">
                        <div class="value"><?= $policy['allow_carryover'] ? number_format($policy['max_carryover_days'], 0) : '0' ?></div>
                        <div class="label">حد الترحيل</div>
                    </div>
                    <div class="policy-stat">
                        <div class="value"><?= $policy['allow_hourly_leave'] ? number_format($policy['max_hours_per_day'], 1) : '-' ?></div>
                        <div class="label">ساعات/يوم</div>
                    </div>
                </div>
                
                <div class="policy-features">
                    <span class="feature-tag <?= $policy['allow_carryover'] ? '' : 'disabled' ?>">
                        <i class="fas fa-redo"></i> ترحيل الرصيد
                    </span>
                    <span class="feature-tag <?= $policy['allow_hourly_leave'] ? '' : 'disabled' ?>">
                        <i class="fas fa-clock"></i> إجازة بالساعة
                    </span>
                    <span class="feature-tag <?= $policy['compensate_unused'] ? '' : 'disabled' ?>">
                        <i class="fas fa-money-bill"></i> تعويض مالي
                    </span>
                    <span class="feature-tag <?= $policy['requires_approval'] ? '' : 'disabled' ?>">
                        <i class="fas fa-check-circle"></i> موافقة مطلوبة
                    </span>
                    <span class="feature-tag <?= $policy['applies_to_all'] ? '' : 'disabled' ?>">
                        <i class="fas fa-users"></i> للجميع
                    </span>
                </div>
                
                <div class="mt-3 pt-3 border-top d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary" onclick="editPolicy(<?= $policy['id'] ?>)">
                        <i class="fas fa-edit"></i> تعديل
                    </button>
                    <button class="btn btn-sm btn-outline-info" onclick="viewPolicyDetails(<?= $policy['id'] ?>)">
                        <i class="fas fa-eye"></i> التفاصيل
                    </button>
                    <?php if ($policy['is_active']): ?>
                    <button class="btn btn-sm btn-outline-warning" onclick="togglePolicy(<?= $policy['id'] ?>, 0)">
                        <i class="fas fa-pause"></i> تعطيل
                    </button>
                    <?php else: ?>
                    <button class="btn btn-sm btn-outline-success" onclick="togglePolicy(<?= $policy['id'] ?>, 1)">
                        <i class="fas fa-play"></i> تفعيل
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($policies)): ?>
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">لا توجد سياسات إجازات</h5>
                <p class="text-muted">قم بإضافة سياسة جديدة للبدء</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-bolt text-warning"></i> إجراءات سريعة</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <button class="btn btn-outline-primary btn-block" onclick="runMonthlyAccrual()">
                        <i class="fas fa-calculator"></i> تشغيل الاستحقاق الشهري
                    </button>
                    <small class="text-muted d-block mt-1">احتساب الاستحقاق لجميع الموظفين</small>
                </div>
                <div class="col-md-4 mb-3">
                    <button class="btn btn-outline-info btn-block" onclick="processYearEnd()">
                        <i class="fas fa-exchange-alt"></i> معالجة نهاية السنة
                    </button>
                    <small class="text-muted d-block mt-1">ترحيل الأرصدة للسنة الجديدة</small>
                </div>
                <div class="col-md-4 mb-3">
                    <button class="btn btn-outline-secondary btn-block" onclick="exportBalances()">
                        <i class="fas fa-file-excel"></i> تصدير الأرصدة
                    </button>
                    <small class="text-muted d-block mt-1">تصدير أرصدة جميع الموظفين</small>
                </div>
            </div>
        </div>
    </div>

</div>
</section>

<!-- Policy Modal -->
<div class="modal fade" id="policyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-plus"></i> <span id="modalTitle">إضافة سياسة إجازات</span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="policyForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="policyId">
                    
                    <!-- Basic Info -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-info-circle"></i> المعلومات الأساسية</div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>اسم السياسة بالعربية <span class="text-danger">*</span></label>
                                    <input type="text" name="policy_name_ar" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>اسم السياسة بالإنجليزية</label>
                                    <input type="text" name="policy_name_en" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>نوع الإجازة</label>
                                    <select name="leave_type_id" class="form-control">
                                        <option value="">-- اختر نوع الإجازة --</option>
                                        <?php foreach ($leaveTypes as $lt): ?>
                                        <option value="<?= $lt['Id'] ?>"><?= htmlspecialchars($lt['Name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="toggle-label">
                                        <span>نشط</span>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="is_active" value="1" checked>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Annual Leave Configuration -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-calendar-day"></i> إعدادات الإجازة السنوية</div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>عدد أيام الإجازة السنوية <span class="text-danger">*</span></label>
                                    <input type="number" name="annual_days" class="form-control" value="30" min="1" max="365" step="0.5" required>
                                    <small class="text-muted">مثال: 30، 21، 15 يوم</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>طريقة الاستحقاق</label>
                                    <select name="accrual_method" class="form-control">
                                        <option value="monthly">شهري (تلقائي)</option>
                                        <option value="yearly">سنوي (دفعة واحدة)</option>
                                        <option value="custom">مخصص</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>الاستحقاق الشهري</label>
                                    <input type="text" class="form-control" id="monthlyAccrualDisplay" readonly>
                                    <small class="text-muted">يُحسب تلقائياً</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Carryover Configuration -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-redo"></i> إعدادات الترحيل</div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="toggle-label">
                                        <span>السماح بترحيل الرصيد</span>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="allow_carryover" value="1" checked onchange="toggleCarryoverFields()">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 carryover-field">
                                <div class="form-group">
                                    <label>الحد الأقصى للترحيل (أيام)</label>
                                    <input type="number" name="max_carryover_days" class="form-control" value="15" min="0" step="0.5">
                                </div>
                            </div>
                            <div class="col-md-4 carryover-field">
                                <div class="form-group">
                                    <label>صلاحية الترحيل (أشهر)</label>
                                    <input type="number" name="carryover_expiry_months" class="form-control" value="3" min="1" max="12">
                                    <small class="text-muted">بعد بداية السنة المالية</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="toggle-label">
                                        <span>تعويض مالي للرصيد غير المستخدم</span>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="compensate_unused" value="1">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="toggle-label">
                                        <span>إجازة إجبارية قبل انتهاء الصلاحية</span>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="force_leave_before_expiry" value="1">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hourly Leave Configuration -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-clock"></i> إعدادات الإجازة بالساعة</div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="toggle-label">
                                        <span>السماح بالإجازة بالساعة</span>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="allow_hourly_leave" value="1" checked onchange="toggleHourlyFields()">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3 hourly-field">
                                <div class="form-group">
                                    <label>الحد الأقصى (ساعات/يوم)</label>
                                    <input type="number" name="max_hours_per_day" class="form-control" value="4" min="0.5" max="8" step="0.5">
                                </div>
                            </div>
                            <div class="col-md-3 hourly-field">
                                <div class="form-group">
                                    <label>ساعات العمل اليومية</label>
                                    <input type="number" name="hours_per_day" class="form-control" value="8" min="1" max="24" step="0.5">
                                </div>
                            </div>
                            <div class="col-md-3 hourly-field">
                                <div class="form-group">
                                    <label>الحد الأدنى للطلب (ساعات)</label>
                                    <input type="number" name="min_hours_per_request" class="form-control" value="1" min="0.5" max="8" step="0.5">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Eligibility & Approval -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-user-check"></i> الأهلية والموافقات</div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>الحد الأدنى للخدمة (أشهر)</label>
                                    <input type="number" name="min_service_months" class="form-control" value="0" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>إشعار مسبق (أيام)</label>
                                    <input type="number" name="advance_notice_days" class="form-control" value="3" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>الحد الأقصى للأيام المتتالية</label>
                                    <input type="number" name="max_consecutive_days" class="form-control" value="30" min="1">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="toggle-label">
                                        <span>مؤهل خلال فترة التجربة</span>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="probation_eligible" value="1">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="toggle-label">
                                        <span>يتطلب موافقة</span>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="requires_approval" value="1" checked>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>مستويات الموافقة</label>
                                    <select name="approval_levels" class="form-control">
                                        <option value="1">مستوى واحد</option>
                                        <option value="2">مستويان</option>
                                        <option value="3">ثلاثة مستويات</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Scope -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-users-cog"></i> نطاق التطبيق</div>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="toggle-label">
                                    <span>تطبيق على جميع الموظفين</span>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="applies_to_all" value="1" checked onchange="toggleScopeFields()">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </label>
                            </div>
                            <div class="col-md-6 scope-field" style="display:none;">
                                <div class="form-group">
                                    <label>الدرجات الوظيفية</label>
                                    <select name="applies_to_grades[]" class="form-control select2" multiple>
                                        <?php foreach ($grades as $g): ?>
                                        <option value="<?= $g['Id'] ?>"><?= htmlspecialchars($g['Name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 scope-field" style="display:none;">
                                <div class="form-group">
                                    <label>الأقسام</label>
                                    <select name="applies_to_departments[]" class="form-control select2" multiple>
                                        <?php foreach ($departments as $d): ?>
                                        <option value="<?= $d['Id'] ?>"><?= htmlspecialchars($d['Name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 scope-field" style="display:none;">
                                <div class="form-group">
                                    <label>المسميات الوظيفية</label>
                                    <select name="applies_to_job_titles[]" class="form-control select2" multiple>
                                        <?php foreach ($jobTitles as $jt): ?>
                                        <option value="<?= $jt['Id'] ?>"><?= htmlspecialchars($jt['Name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 scope-field" style="display:none;">
                                <div class="form-group">
                                    <label>الفروع</label>
                                    <select name="applies_to_branches[]" class="form-control select2" multiple>
                                        <?php foreach ($branches as $b): ?>
                                        <option value="<?= $b['branch_id'] ?>"><?= htmlspecialchars($b['branch_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ السياسة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once('inc/footer.php'); ?>

<script>
console.log("admin-leave-policies.php script block started."); // Debug log

// Define globally to be accessible from inline onclicks
window.togglePolicy = function(id, active) {
    if (active == 1 && $('.policy-badge.active').length > 0) {
        toastr.warning('يمكن تفعيل سياسة إجازات واحدة فقط. يرجى تعطيل السياسة النشطة حالياً قبل تفعيل سياسة أخرى.');
        return;
    }
    console.log("togglePolicy called for ID:", id, "Active status:", active); // Debug log
    $.post('hr-app/index.php?action=toggle-leave-policy', { id: id, is_active: active }, function(data) {
        if(typeof data === 'string') {
            try { data = JSON.parse(data); } catch(e) {
                toastr.error('خطأ في تحليل استجابة الخادم.');
                console.error("JSON Parse Error: ", e, "Response: ", data);
                return;
            }
        }
        if (data.result) { // Changed data.success to data.result as per hr-app/index.php
            toastr.success(data.msg || (active ? 'تم تفعيل السياسة' : 'تم تعطيل السياسة'));
            setTimeout(function(){ location.reload(); }, 500); // Reload faster
        } else {
            toastr.error(data.msg || 'حدث خطأ');
            console.error("Server Error (togglePolicy): ", data.msg, data.debug_session);
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        toastr.error('فشل في الاتصال بالخادم: ' + textStatus);
        console.error("AJAX Fail (togglePolicy): ", textStatus, errorThrown, jqXHR.responseText);
    });
};

$(document).ready(function() {
    console.log("Document ready event fired."); // Debug log

    // Initialize Select2 for multiple selects
    if ($.fn.select2) {
        console.log("Select2 is available. Initializing select2 for scope fields..."); // Debug log
        $('.form-section select[multiple]').select2({
            dir: 'rtl',
            width: '100%',
            dropdownParent: $('#policyModal') // Important for modals
        });
        console.log("Select2 initialization complete."); // Debug log
    } else {
        console.warn("Select2 is NOT available."); // Debug log if Select2 is missing
    }


    // Calculate monthly accrual
    $('input[name="annual_days"]').on('input', function() {
        var annual = parseFloat($(this).val()) || 0;
        var monthly = (annual / 12).toFixed(2);
        $('#monthlyAccrualDisplay').val(monthly + ' يوم');
    });

    // Trigger on load for initial display
    $('input[name="annual_days"]').trigger('input');

    // Toggle carryover fields
    window.toggleCarryoverFields = function() { // Make global
        var checked = $('input[name="allow_carryover"]').is(':checked');
        $('.carryover-field').toggle(checked);
        // Ensure required attribute is toggled as well
        $('.carryover-field input').prop('required', checked);
    };
    $('input[name="allow_carryover"]').trigger('change'); // Initial call

    // Toggle hourly fields
    window.toggleHourlyFields = function() { // Make global
        var checked = $('input[name="allow_hourly_leave"]').is(':checked');
        $('.hourly-field').toggle(checked);
        // Ensure required attribute is toggled as well
        $('.hourly-field input').prop('required', checked);
    };
    $('input[name="allow_hourly_leave"]').trigger('change'); // Initial call


    // Toggle scope fields
    window.toggleScopeFields = function() { // Make global
        var checked = $('input[name="applies_to_all"]').is(':checked');
        $('.scope-field').toggle(!checked);
        // Toggle required for scope fields based on "applies_to_all"
        $('.scope-field select[multiple]').each(function() {
            $(this).prop('required', !checked);
            if (!checked) { // If applies_to_all is false, and this is a multiple select
                 // Optional: add data-placeholder="اختر..." if using select2
            }
        });
        // Refresh Select2 elements if they are shown/hidden
        $('.scope-field select.select2').select2('destroy').select2({
            dir: 'rtl',
            width: '100%',
            dropdownParent: $('#policyModal')
        });
    };
    $('input[name="applies_to_all"]').trigger('change'); // Initial call


    // Reset form
    window.resetForm = function() { // Make global
        console.log("resetForm called."); // Debug log
        $('#policyForm')[0].reset();
        $('#policyId').val('');
        $('#modalTitle').text('إضافة سياسة إجازات');
        
        // Reset checkboxes explicitly to their default states (checked or unchecked)
        $('input[name="is_active"]').prop('checked', true);
        $('input[name="allow_carryover"]').prop('checked', true);
        $('input[name="compensate_unused"]').prop('checked', false);
        $('input[name="force_leave_before_expiry"]').prop('checked', false);
        $('input[name="allow_hourly_leave"]').prop('checked', true);
        $('input[name="probation_eligible"]').prop('checked', false);
        $('input[name="requires_approval"]').prop('checked', true);
        $('input[name="applies_to_all"]').prop('checked', true);

        // Reset all Select2 fields
        $('#policyForm select').val('').trigger('change');
        // Re-trigger calculations and toggles to set initial state correctly
        $('input[name="annual_days"]').trigger('input');
        toggleCarryoverFields();
        toggleHourlyFields();
        toggleScopeFields(); // This also rebuilds select2
    };

    // Edit policy
    window.editPolicy = function(id) { // Make global
        console.log("editPolicy called for ID:", id); // Debug log
        $.get('hr-app/index.php?action=get-leave-policy&id=' + id, function(res) { // Changed data to res for consistency
            if(typeof res === 'string') { // Parse if needed
                try { res = JSON.parse(res); } catch(e) {
                    toastr.error('خطأ في تحليل استجابة الخادم عند جلب السياسة.');
                    console.error("JSON Parse Error: ", e, "Response: ", res);
                    return;
                }
            }

            if (res.result && res.data) { // Check res.result for success
                var p = res.data;
                console.log("Policy data received for editing: ", p); // Debug log policy data
                
                $('#policyId').val(p.id);
                $('#modalTitle').text('تعديل سياسة الإجازات');
                
                $('input[name="policy_name_ar"]').val(p.policy_name_ar);
                $('input[name="policy_name_en"]').val(p.policy_name_en);
                $('select[name="leave_type_id"]').val(p.leave_type_id).trigger('change'); // Trigger change for select2
                $('input[name="is_active"]').prop('checked', p.is_active == 1);
                $('input[name="annual_days"]').val(p.annual_days).trigger('input');
                $('select[name="accrual_method"]').val(p.accrual_method).trigger('change'); // Trigger change for select2
                $('input[name="allow_carryover"]').prop('checked', p.allow_carryover == 1).trigger('change');
                $('input[name="max_carryover_days"]').val(p.max_carryover_days);
                $('input[name="carryover_expiry_months"]').val(p.carryover_expiry_months);
                $('input[name="compensate_unused"]').prop('checked', p.compensate_unused == 1);
                $('input[name="force_leave_before_expiry"]').prop('checked', p.force_leave_before_expiry == 1);
                $('input[name="allow_hourly_leave"]').prop('checked', p.allow_hourly_leave == 1).trigger('change');
                $('input[name="max_hours_per_day"]').val(p.max_hours_per_day);
                $('input[name="hours_per_day"]').val(p.hours_per_day);
                $('input[name="min_hours_per_request"]').val(p.min_hours_per_request);
                $('input[name="min_service_months"]').val(p.min_service_months);
                $('input[name="advance_notice_days"]').val(p.advance_notice_days);
                $('input[name="max_consecutive_days"]').val(p.max_consecutive_days);
                $('input[name="probation_eligible"]').prop('checked', p.probation_eligible == 1);
                $('input[name="requires_approval"]').prop('checked', p.requires_approval == 1);
                $('select[name="approval_levels"]').val(p.approval_levels).trigger('change'); // Trigger change for select2
                $('input[name="applies_to_all"]').prop('checked', p.applies_to_all == 1).trigger('change');
                
                // Handle JSON arrays for multiple selects, ensure they are parsed
                if (p.applies_to_grades) {
                    try { $('select[name="applies_to_grades[]"]').val(JSON.parse(p.applies_to_grades)).trigger('change'); } catch(e) { console.error("Error parsing applies_to_grades:", e); }
                } else { $('select[name="applies_to_grades[]"]').val('').trigger('change'); }
                
                if (p.applies_to_departments) {
                    try { $('select[name="applies_to_departments[]"]').val(JSON.parse(p.applies_to_departments)).trigger('change'); } catch(e) { console.error("Error parsing applies_to_departments:", e); }
                } else { $('select[name="applies_to_departments[]"]').val('').trigger('change'); }

                if (p.applies_to_job_titles) {
                    try { $('select[name="applies_to_job_titles[]"]').val(JSON.parse(p.applies_to_job_titles)).trigger('change'); } catch(e) { console.error("Error parsing applies_to_job_titles:", e); }
                } else { $('select[name="applies_to_job_titles[]"]').val('').trigger('change'); }

                if (p.applies_to_branches) {
                    try { $('select[name="applies_to_branches[]"]').val(JSON.parse(p.applies_to_branches)).trigger('change'); } catch(e) { console.error("Error parsing applies_to_branches:", e); }
                } else { $('select[name="applies_to_branches[]"]').val('').trigger('change'); }
                
                $('#policyModal').modal('show');
            } else {
                toastr.error(res.msg || 'فشل جلب بيانات السياسة.');
                console.error("Server Error (editPolicy): ", res.msg, res.debug_session);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            toastr.error('فشل في الاتصال بالخادم عند جلب السياسة: ' + textStatus);
            console.error("AJAX Fail (editPolicy): ", textStatus, errorThrown, jqXHR.responseText);
        });
    };

    // Save policy
    $('#policyForm').on('submit', function(e) { // Changed to .on('submit')
        e.preventDefault();
        console.log("policyForm submitted via AJAX!"); // Debug log
        if ($('input[name="is_active"]').is(':checked') && $('.policy-badge.active').length > 0 && !$('#policy_id').val()) {
            toastr.warning('يمكن تفعيل سياسة إجازات واحدة فقط. يرجى تعطيل السياسة النشطة حالياً قبل تفعيل سياسة أخرى.');
            return;
        }
        var formData = $(this).serialize();
        console.log("Form Data: ", formData); // Debug log form data
        
        $.post('hr-app/index.php?action=save-leave-policy', formData, function(res) { // Changed data to res
            console.log("AJAX response received: ", res); // Debug log raw response
            if(typeof res === 'string') {
                try { res = JSON.parse(res); } catch(e) {
                    toastr.error('خطأ في تحليل استجابة الخادم.');
                    console.error("JSON Parse Error: ", e, "Response: ", res);
                    return;
                }
            }

            if (res.result) { // Check res.result for success
                toastr.success(res.msg || 'تم حفظ السياسة بنجاح.');
                $('#policyModal').modal('hide');
                setTimeout(function(){ location.reload(); }, 1000);
            } else {
                toastr.error(res.msg || 'حدث خطأ أثناء حفظ السياسة.');
                console.error("Server Error (savePolicy): ", res.msg, res.data, res.debug_session); // Log full response
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            toastr.error('فشل في الاتصال بالخادم: ' + textStatus);
            console.error("AJAX Fail (savePolicy): ", textStatus, errorThrown, jqXHR.responseText);
        });
    });

    // View policy details - simply reuse editPolicy for now
    window.viewPolicyDetails = function(id) {
        console.log("viewPolicyDetails called for ID:", id); // Debug log
        editPolicy(id);
    };
    
    // Run monthly accrual
    window.runMonthlyAccrual = function() {
        console.log("runMonthlyAccrual called."); // Debug log
        Swal.fire({
            title: 'تشغيل الاستحقاق الشهري',
            text: 'سيتم احتساب الاستحقاق الشهري لجميع الموظفين. هل تريد المتابعة؟',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'نعم، تشغيل',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('hr-app/index.php?action=run-leave-accrual', function(res) { // Changed data to res
                    if(typeof res === 'string') { try { res = JSON.parse(res); } catch(e) { console.error("JSON Parse Error: ", e); return; } }
                    if (res.result) {
                        Swal.fire('تم!', 'تم احتساب الاستحقاق لـ ' + (res.data.processed || 0) + ' موظف', 'success');
                        setTimeout(function(){ location.reload(); }, 1000); // Reload to update displayed balances
                    } else {
                        toastr.error(res.msg || 'حدث خطأ');
                        console.error("Server Error (runMonthlyAccrual): ", res.msg, res.data, res.debug_session);
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    toastr.error('فشل في الاتصال بالخادم: ' + textStatus);
                    console.error("AJAX Fail (runMonthlyAccrual): ", textStatus, errorThrown, jqXHR.responseText);
                });
            }
        });
    };

    // Process year end
    window.processYearEnd = function() {
        console.log("processYearEnd called."); // Debug log
        Swal.fire({
            title: 'معالجة نهاية السنة',
            html: `
                <div class="text-right">
                    <div class="form-group">
                        <label>من سنة</label>
                        <input type="number" id="fromYear" class="form-control" value="${new Date().getFullYear() - 1}">
                    </div>
                    <div class="form-group">
                        <label>إلى سنة</label>
                        <input type="number" id="toYear" class="form-control" value="${new Date().getFullYear()}">
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'تنفيذ',
            cancelButtonText: 'إلغاء',
            preConfirm: () => {
                // Basic validation for years
                const fromYear = $('#fromYear').val();
                const toYear = $('#toYear').val();
                if (!fromYear || !toYear || parseInt(fromYear) >= parseInt(toYear)) {
                    Swal.showValidationMessage('يرجى إدخال سنوات صالحة');
                    return false;
                }
                return { from_year: fromYear, to_year: toYear };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                var { from_year, to_year } = result.value;
                console.log("Processing year end with: ", from_year, to_year); // Debug log
                $.post('hr-app/index.php?action=process-year-end', { from_year: from_year, to_year: to_year }, function(res) { // Changed data to res
                    if(typeof res === 'string') { try { res = JSON.parse(res); } catch(e) { console.error("JSON Parse Error: ", e); return; } }
                    if (res.result) {
                        Swal.fire('تم!', `تمت المعالجة: ${res.data.processed} موظف\nترحيل: ${res.data.carryover_total} يوم\nتعويض: ${res.data.compensated_total} يوم`, 'success');
                        setTimeout(function(){ location.reload(); }, 1000); // Reload to update displayed balances
                    } else {
                        toastr.error(res.msg || 'حدث خطأ');
                        console.error("Server Error (processYearEnd): ", res.msg, res.data, res.debug_session);
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    toastr.error('فشل في الاتصال بالخادم: ' + textStatus);
                    console.error("AJAX Fail (processYearEnd): ", textStatus, errorThrown, jqXHR.responseText);
                });
            }
        });
    };

    // Export balances
    window.exportBalances = function() {
        console.log("exportBalances called."); // Debug log
        window.location.href = 'hr-app/index.php?action=export-leave-balances'; // Assuming this action exists and exports a file
    };

}); // End of $(document).ready
</script>