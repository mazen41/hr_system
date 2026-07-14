<?php
/**
 * Violations Management
 * Record, track, and manage employee violations with escalation rules
 */
$screen = 'إعدادات النظام';
$page_title = 'إدارة المخالفات';
include_once('inc/header.php');

if (!$User->userIsAdmin() && !$User->userIsEmployer()) {
    header('Location: ess-dashboard');
    exit;
}

require_once 'classes/ViolationManager.php';
$violationManager = new ViolationManager($connect_pdo);

$violationTypes = $violationManager->getViolationTypes(false);
$violations = $violationManager->getAllViolations(['limit' => 50]);

// Get employees for dropdown
$employees = $connect_pdo->query("
    SELECT u.UserID, u.FirstName, u.LastName, s.Name as section_name
    FROM tblusers u
    LEFT JOIN tblremewal r ON r.Id = u.lastversion
    LEFT JOIN tblsection s ON s.Id = r.SectionID
    WHERE u.isemp = 1
    ORDER BY u.FirstName
")->fetchAll(PDO::FETCH_ASSOC);
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
    .modal-body { padding: 15px; }
}

.violation-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border-right: 4px solid;
}

.violation-card.minor { border-right-color: #fbbf24; }
.violation-card.moderate { border-right-color: #f97316; }
.violation-card.major { border-right-color: #ef4444; }
.violation-card.critical { border-right-color: #7c3aed; }

.severity-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.severity-badge.minor { background: #fef3c7; color: #92400e; }
.severity-badge.moderate { background: #ffedd5; color: #9a3412; }
.severity-badge.major { background: #fee2e2; color: #991b1b; }
.severity-badge.critical { background: #ede9fe; color: #5b21b6; }

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.reported { background: #e0e7ff; color: #3730a3; }
.status-badge.under_review { background: #fef3c7; color: #92400e; }
.status-badge.confirmed { background: #fee2e2; color: #991b1b; }
.status-badge.appealed { background: #dbeafe; color: #1e40af; }
.status-badge.dismissed { background: #d1fae5; color: #065f46; }
.status-badge.closed { background: #f3f4f6; color: #374151; }

.penalty-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    background: #f3f4f6;
    border-radius: 6px;
    font-size: 12px;
}

.penalty-tag i { color: #6b7280; }

.violation-type-card {
    background: white;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 10px;
    border: 1px solid #e5e7eb;
    transition: all 0.2s;
}

.violation-type-card:hover {
    border-color: #0d21a5;
    box-shadow: 0 2px 8px rgba(13,33,165,0.1);
}

.escalation-timeline {
    position: relative;
    padding-right: 30px;
}

.escalation-timeline::before {
    content: '';
    position: absolute;
    right: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e5e7eb;
}

.escalation-step {
    position: relative;
    padding: 10px 0;
    padding-right: 25px;
}

.escalation-step::before {
    content: '';
    position: absolute;
    right: -24px;
    top: 15px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #0d21a5;
    border: 2px solid white;
    box-shadow: 0 0 0 2px #0d21a5;
}

.escalation-step .step-num {
    font-weight: 700;
    color: #0d21a5;
}

.filter-pills {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.filter-pill {
    padding: 6px 14px;
    border-radius: 20px;
    border: 1px solid #e5e7eb;
    background: white;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-pill:hover, .filter-pill.active {
    background: #0d21a5;
    color: white;
    border-color: #0d21a5;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.stat-card .value {
    font-size: 28px;
    font-weight: 700;
}

.stat-card .label {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.stat-card.minor .value { color: #f59e0b; }
.stat-card.moderate .value { color: #f97316; }
.stat-card.major .value { color: #ef4444; }
.stat-card.critical .value { color: #7c3aed; }

@media (max-width: 767.98px) {
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    .violation-card {
        padding: 15px;
    }
    .filter-pills {
        gap: 5px;
    }
    .filter-pill {
        padding: 5px 10px;
        font-size: 12px;
    }
}

@media (max-width: 480px) {
    .stats-row {
        grid-template-columns: 1fr;
    }
    .d-flex.gap-2 {
        flex-wrap: wrap;
    }
    .d-flex.gap-2 .btn {
        flex: 1;
        width: 100%;
        margin-bottom: 5px;
    }
}
</style>

<div class="page-nav d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="mb-1 page-title"><i class="fas fa-exclamation-triangle text-warning"></i> إدارة المخالفات</h4>
        <p class="text-muted mb-0">تسجيل ومتابعة مخالفات الموظفين مع قواعد التصعيد</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" data-toggle="modal" data-target="#typesModal">
            <i class="fas fa-cog"></i> أنواع المخالفات
        </button>
        <button class="btn btn-primary" data-toggle="modal" data-target="#recordModal" onclick="resetRecordForm()">
            <i class="fas fa-plus"></i> تسجيل مخالفة
        </button>
    </div>
</div>

<section class="content py-3">
<div class="container-fluid">

    <!-- Stats -->
    <div class="stats-row">
        <?php
        $stats = ['minor' => 0, 'moderate' => 0, 'major' => 0, 'critical' => 0, 'pending' => 0];
        foreach ($violations as $v) {
            if (isset($stats[$v['severity']])) $stats[$v['severity']]++;
            if (in_array($v['status'], ['reported', 'under_review'])) $stats['pending']++;
        }
        ?>
        <div class="stat-card">
            <div class="value"><?= count($violations) ?></div>
            <div class="label">إجمالي المخالفات</div>
        </div>
        <div class="stat-card minor">
            <div class="value"><?= $stats['minor'] ?></div>
            <div class="label">بسيطة</div>
        </div>
        <div class="stat-card moderate">
            <div class="value"><?= $stats['moderate'] ?></div>
            <div class="label">متوسطة</div>
        </div>
        <div class="stat-card major">
            <div class="value"><?= $stats['major'] ?></div>
            <div class="label">جسيمة</div>
        </div>
        <div class="stat-card critical">
            <div class="value"><?= $stats['critical'] ?></div>
            <div class="label">خطيرة</div>
        </div>
        <div class="stat-card">
            <div class="value" style="color:#3b82f6;"><?= $stats['pending'] ?></div>
            <div class="label">قيد المراجعة</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-pills">
        <span class="filter-pill active" data-filter="all">الكل</span>
        <span class="filter-pill" data-filter="reported">جديدة</span>
        <span class="filter-pill" data-filter="under_review">قيد المراجعة</span>
        <span class="filter-pill" data-filter="confirmed">مؤكدة</span>
        <span class="filter-pill" data-filter="appealed">مستأنفة</span>
        <span class="filter-pill" data-filter="closed">مغلقة</span>
    </div>

    <!-- Violations List -->
    <div class="row">
        <div class="col-12">
            <?php foreach ($violations as $v): ?>
            <div class="violation-card <?= $v['severity'] ?>" data-status="<?= $v['status'] ?>">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="severity-badge <?= $v['severity'] ?>">
                                <?php
                                $severityLabels = ['minor' => 'بسيطة', 'moderate' => 'متوسطة', 'major' => 'جسيمة', 'critical' => 'خطيرة'];
                                echo $severityLabels[$v['severity']] ?? $v['severity'];
                                ?>
                            </span>
                            <span class="status-badge <?= $v['status'] ?>">
                                <?php
                                $statusLabels = ['reported' => 'جديدة', 'under_review' => 'قيد المراجعة', 'confirmed' => 'مؤكدة', 'appealed' => 'مستأنفة', 'dismissed' => 'مرفوضة', 'closed' => 'مغلقة'];
                                echo $statusLabels[$v['status']] ?? $v['status'];
                                ?>
                            </span>
                            <?php if ($v['occurrence_number'] > 1): ?>
                            <span class="badge badge-warning">المرة <?= $v['occurrence_number'] ?></span>
                            <?php endif; ?>
                        </div>
                        <h5 class="mb-1"><?= htmlspecialchars($v['violation_name']) ?></h5>
                        <p class="text-muted mb-2">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($v['FirstName'] . ' ' . $v['LastName']) ?>
                            <?php if ($v['section_name']): ?>
                            <span class="mx-2">|</span>
                            <i class="fas fa-building"></i> <?= htmlspecialchars($v['section_name']) ?>
                            <?php endif; ?>
                        </p>
                        <?php if ($v['description']): ?>
                        <p class="mb-2"><?= htmlspecialchars($v['description']) ?></p>
                        <?php endif; ?>
                        <div class="d-flex gap-3 text-muted" style="font-size:13px;">
                            <span><i class="fas fa-calendar"></i> <?= $v['violation_date'] ?></span>
                            <?php if ($v['penalty_type']): ?>
                            <span class="penalty-tag">
                                <i class="fas fa-gavel"></i>
                                <?php
                                $penaltyLabels = ['warning' => 'إنذار', 'deduction' => 'خصم', 'suspension' => 'إيقاف', 'termination' => 'فصل'];
                                echo $penaltyLabels[$v['penalty_type']] ?? $v['penalty_type'];
                                if ($v['penalty_value']) echo ' (' . $v['penalty_value'] . ')';
                                ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if ($v['status'] === 'reported'): ?>
                        <button class="btn btn-sm btn-outline-primary" onclick="updateStatus(<?= $v['id'] ?>, 'under_review')">
                            <i class="fas fa-search"></i> مراجعة
                        </button>
                        <?php endif; ?>
                        <?php if ($v['status'] === 'under_review'): ?>
                        <button class="btn btn-sm btn-success" onclick="updateStatus(<?= $v['id'] ?>, 'confirmed')">
                            <i class="fas fa-check"></i> تأكيد
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="updateStatus(<?= $v['id'] ?>, 'dismissed')">
                            <i class="fas fa-times"></i> رفض
                        </button>
                        <?php endif; ?>
                        <?php if ($v['status'] === 'confirmed'): ?>
                        <button class="btn btn-sm btn-outline-dark" onclick="updateStatus(<?= $v['id'] ?>, 'closed')">
                            <i class="fas fa-archive"></i> إغلاق
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-info" onclick="viewViolation(<?= $v['id'] ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($violations)): ?>
            <div class="text-center py-5">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h5 class="text-muted">لا توجد مخالفات مسجلة</h5>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</section>

<!-- Record Violation Modal -->
<div class="modal fade" id="recordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-circle text-warning"></i> تسجيل مخالفة</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="recordForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>الموظف <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-control select2" required>
                            <option value="">-- اختر الموظف --</option>
                            <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['UserID'] ?>">
                                <?= htmlspecialchars($emp['FirstName'] . ' ' . $emp['LastName']) ?>
                                <?php if ($emp['section_name']): ?> (<?= htmlspecialchars($emp['section_name']) ?>)<?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>نوع المخالفة <span class="text-danger">*</span></label>
                        <select name="violation_type_id" class="form-control" required onchange="showTypeInfo(this)">
                            <option value="">-- اختر نوع المخالفة --</option>
                            <?php foreach ($violationTypes as $vt): ?>
                            <option value="<?= $vt['id'] ?>" 
                                    data-severity="<?= $vt['severity'] ?>"
                                    data-penalty="<?= $vt['default_penalty_type'] ?>"
                                    data-penalty-value="<?= $vt['default_penalty_value'] ?? '' ?>"
                                    data-blocks="<?= $vt['blocks_promotion'] ?>"
                                    data-block-months="<?= $vt['promotion_block_months'] ?? '' ?>">
                                <?= htmlspecialchars($vt['name_ar']) ?> (<?= $severityLabels[$vt['severity']] ?? $vt['severity'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="typeInfo" class="mt-2" style="display:none;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>تاريخ المخالفة <span class="text-danger">*</span></label>
                        <input type="date" name="violation_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>الوصف</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="تفاصيل المخالفة..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>مرفق (دليل)</label>
                        <input type="file" name="evidence" class="form-control-file">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning">تسجيل المخالفة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Violation Types Modal -->
<div class="modal fade" id="typesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-list"></i> أنواع المخالفات</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <?php foreach ($violationTypes as $vt): ?>
                    <div class="col-md-6">
                        <div class="violation-type-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($vt['name_ar']) ?></h6>
                                    <small class="text-muted"><?= htmlspecialchars($vt['name_en'] ?? '') ?></small>
                                </div>
                                <span class="severity-badge <?= $vt['severity'] ?>">
                                    <?= $severityLabels[$vt['severity']] ?? $vt['severity'] ?>
                                </span>
                            </div>
                            <div class="mt-2 d-flex gap-2 flex-wrap" style="font-size:12px;">
                                <span class="penalty-tag">
                                    <i class="fas fa-gavel"></i>
                                    <?= $penaltyLabels[$vt['default_penalty_type']] ?? $vt['default_penalty_type'] ?>
                                </span>
                                <?php if ($vt['blocks_promotion']): ?>
                                <span class="penalty-tag" style="background:#fee2e2;color:#991b1b;">
                                    <i class="fas fa-ban"></i> يمنع الترقية (<?= $vt['promotion_block_months'] ?> شهر)
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php
                            $escalations = $violationManager->getEscalationRules($vt['id']);
                            if (!empty($escalations)):
                            ?>
                            <div class="mt-3">
                                <small class="text-muted d-block mb-2">قواعد التصعيد:</small>
                                <div class="escalation-timeline">
                                    <?php foreach ($escalations as $esc): ?>
                                    <div class="escalation-step">
                                        <span class="step-num">المرة <?= $esc['occurrence_number'] ?>:</span>
                                        <?= $penaltyLabels[$esc['penalty_type']] ?? $esc['penalty_type'] ?>
                                        <?php if ($esc['penalty_value']): ?>(<?= $esc['penalty_value'] ?>)<?php endif; ?>
                                        <?php if ($esc['notes_ar']): ?><br><small class="text-muted"><?= htmlspecialchars($esc['notes_ar']) ?></small><?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($User->userIsAdmin()): ?>
                <div class="text-center mt-3">
                    <button class="btn btn-outline-primary" onclick="$('#typesModal').modal('hide'); $('#addTypeModal').modal('show');">
                        <i class="fas fa-plus"></i> إضافة نوع مخالفة
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Violation Type Modal -->
<div class="modal fade" id="addTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> إضافة نوع مخالفة</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="typeForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>الكود <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" required placeholder="LATE_ARRIVAL">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>التصنيف <span class="text-danger">*</span></label>
                                <select name="category" class="form-control" required>
                                    <option value="attendance">الحضور</option>
                                    <option value="conduct">السلوك</option>
                                    <option value="performance">الأداء</option>
                                    <option value="safety">السلامة</option>
                                    <option value="policy">السياسات</option>
                                    <option value="other">أخرى</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>الاسم بالعربية <span class="text-danger">*</span></label>
                                <input type="text" name="name_ar" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>الاسم بالإنجليزية</label>
                                <input type="text" name="name_en" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>الخطورة <span class="text-danger">*</span></label>
                                <select name="severity" class="form-control" required>
                                    <option value="minor">بسيطة</option>
                                    <option value="moderate">متوسطة</option>
                                    <option value="major">جسيمة</option>
                                    <option value="critical">خطيرة</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>العقوبة الافتراضية</label>
                                <select name="default_penalty_type" class="form-control">
                                    <option value="warning">إنذار</option>
                                    <option value="deduction">خصم</option>
                                    <option value="suspension">إيقاف</option>
                                    <option value="termination">فصل</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="d-flex align-items-center gap-2">
                                    <input type="checkbox" name="blocks_promotion" value="1">
                                    يمنع الترقية
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>مدة منع الترقية (أشهر)</label>
                                <input type="number" name="promotion_block_months" class="form-control" value="6" min="0">
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

<!-- View Violation Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> تفاصيل المخالفة</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once('inc/footer.php'); ?>

<script>
// Filter violations
$('.filter-pill').click(function() {
    var filter = $(this).data('filter');
    $('.filter-pill').removeClass('active');
    $(this).addClass('active');
    
    if (filter === 'all') {
        $('.violation-card').show();
    } else {
        $('.violation-card').hide();
        $('.violation-card[data-status="' + filter + '"]').show();
    }
});

// Reset record form
function resetRecordForm() {
    $('#recordForm')[0].reset();
    $('#typeInfo').hide();
}

// Show type info
function showTypeInfo(select) {
    var opt = $(select).find(':selected');
    if (!opt.val()) {
        $('#typeInfo').hide();
        return;
    }
    
    var severity = opt.data('severity');
    var penalty = opt.data('penalty');
    var penaltyValue = opt.data('penalty-value');
    var blocks = opt.data('blocks');
    var blockMonths = opt.data('block-months');
    
    var html = '<div class="alert alert-info mb-0 py-2">';
    html += '<small>';
    html += '<strong>العقوبة الافتراضية:</strong> ' + getPenaltyLabel(penalty);
    if (penaltyValue) {
        html += ' (' + penaltyValue + ')';
    }
    if (blocks) {
        html += ' | <span class="text-danger"><i class="fas fa-ban"></i> يمنع الترقية';
        if (blockMonths) {
            html += ' (' + blockMonths + ' شهر)';
        }
        html += '</span>';
    }
    html += '</small></div>';
    
    $('#typeInfo').html(html).show();
}

function getPenaltyLabel(type) {
    var labels = {warning: 'إنذار', deduction: 'خصم', suspension: 'إيقاف', termination: 'فصل'};
    return labels[type] || type;
}

// Record violation
$('#recordForm').submit(function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    
    $.post('hr-app/index.php?action=record-violation', formData, function(data) {
        if (data.result) {
            toastr.success(data.msg);
            $('#recordModal').modal('hide');
            
            // Show escalation info if applicable
            if (data.data.occurrence_number > 1) {
                Swal.fire({
                    title: 'تم تسجيل المخالفة',
                    html: 'هذه المرة رقم <strong>' + data.data.occurrence_number + '</strong> لهذا النوع من المخالفات.<br>' +
                          'العقوبة المطبقة: <strong>' + getPenaltyLabel(data.data.penalty_type) + '</strong>',
                    icon: 'warning'
                });
            }
            
            location.reload();
        } else {
            toastr.error(data.msg);
        }
    });
});

// Update violation status
function updateStatus(id, status) {
    var statusLabels = {
        under_review: 'نقل للمراجعة',
        confirmed: 'تأكيد المخالفة',
        dismissed: 'رفض المخالفة',
        closed: 'إغلاق المخالفة'
    };
    
    Swal.fire({
        title: statusLabels[status] + '؟',
        text: 'هل أنت متأكد من تغيير حالة المخالفة؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'نعم',
        cancelButtonText: 'إلغاء',
        input: status === 'dismissed' ? 'textarea' : null,
        inputPlaceholder: 'سبب الرفض...'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('hr-app/index.php?action=update-violation-status', {
                id: id,
                status: status,
                notes: result.value || ''
            }, function(data) {
                if (data.result) {
                    toastr.success(data.msg);
                    location.reload();
                } else {
                    toastr.error(data.msg);
                }
            });
        }
    });
}

// View violation details
function viewViolation(id) {
    $('#viewModal').modal('show');
    $('#viewModalBody').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>');
    
    $.get('hr-app/index.php?action=get-violation&id=' + id, function(data) {
        if (data.result && data.data) {
            var v = data.data;
            var html = `
                <div class="text-center mb-3">
                    <span class="severity-badge ${v.severity}" style="font-size:14px;padding:8px 20px;">
                        ${v.violation_name}
                    </span>
                </div>
                <table class="table table-sm">
                    <tr><td class="text-muted" width="120">الموظف</td><td><strong>${v.FirstName} ${v.LastName}</strong></td></tr>
                    <tr><td class="text-muted">التاريخ</td><td>${v.violation_date}</td></tr>
                    <tr><td class="text-muted">الحالة</td><td><span class="status-badge ${v.status}">${getStatusLabel(v.status)}</span></td></tr>
                    <tr><td class="text-muted">التكرار</td><td>المرة ${v.occurrence_number}</td></tr>
                    <tr><td class="text-muted">العقوبة</td><td>${getPenaltyLabel(v.penalty_type)} ${v.penalty_value ? '('+v.penalty_value+')' : ''}</td></tr>
                    ${v.description ? '<tr><td class="text-muted">الوصف</td><td>'+v.description+'</td></tr>' : ''}
                    ${v.reporter_first ? '<tr><td class="text-muted">المُبلغ</td><td>'+v.reporter_first+' '+v.reporter_last+'</td></tr>' : ''}
                    ${v.resolution_notes ? '<tr><td class="text-muted">ملاحظات</td><td>'+v.resolution_notes+'</td></tr>' : ''}
                </table>
            `;
            $('#viewModalBody').html(html);
        } else {
            $('#viewModalBody').html('<div class="alert alert-danger">حدث خطأ في جلب البيانات</div>');
        }
    });
}

function getStatusLabel(status) {
    var labels = {reported: 'جديدة', under_review: 'قيد المراجعة', confirmed: 'مؤكدة', appealed: 'مستأنفة', dismissed: 'مرفوضة', closed: 'مغلقة'};
    return labels[status] || status;
}

// Save violation type
$('#typeForm').submit(function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    
    $.post('hr-app/index.php?action=save-violation-type', formData, function(data) {
        if (data.result) {
            toastr.success(data.msg);
            $('#addTypeModal').modal('hide');
            location.reload();
        } else {
            toastr.error(data.msg);
        }
    });
});
</script>
