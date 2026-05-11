<?php
/**
 * Admin Evaluations - Performance evaluation management
 */
$screen = 'تقييم الأداء';
$page_title = 'تقييم الأداء';
$page = 'admin-evaluations';
include_once('inc/header.php');

require_once 'classes/EvaluationManager.php';
$evalManager = new EvaluationManager($connect_pdo);

$periods = $evalManager->getActivePeriods();
$criteria = $evalManager->getCriteria(false); // Pass 'false' to get inactive criteria as well
$stats = $evalManager->getEvaluationStats();
$probationEmployees = $evalManager->getEmployeesInProbation();

// Get employees for dropdown
$stmt = $connect_pdo->query("SELECT UserID, FirstName, LastName FROM tblusers WHERE isemp = 1 ORDER BY FirstName");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    max-height: calc(100vh - 200px);
    overflow-y: auto;
}
.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #e5e7eb;
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}
.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border-top: 3px solid #0d21a5;
}
.stat-number {
    font-size: 28px;
    font-weight: 700;
    color: #0d21a5;
}
.stat-label {
    font-size: 13px;
    color: #6b7280;
    margin-top: 5px;
}

/* Criteria Table */
.criteria-table {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.criteria-table th {
    background: #f8fafc;
    font-weight: 600;
    padding: 12px 15px;
    border-bottom: 2px solid #e5e7eb;
}
.criteria-table td {
    padding: 12px 15px;
    vertical-align: middle;
}
.category-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.category-performance { background: #dbeafe; color: #1e40af; }
.category-behavior { background: #fef3c7; color: #92400e; }
.category-skills { background: #d1fae5; color: #065f46; }
.category-attendance { background: #fce7f3; color: #9d174d; }
.category-teamwork { background: #e0e7ff; color: #3730a3; }

/* Period Cards */
.period-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border-right: 4px solid #0d21a5;
}
.period-type {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
    background: #e8f4fd;
    color: #0d21a5;
}

/* Probation Alert */
.probation-alert {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    border-right: 4px solid #f59e0b;
}
.probation-alert h5 {
    color: #92400e;
    margin-bottom: 10px;
}
.probation-employee {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    padding: 8px 15px;
    border-radius: 25px;
    margin: 5px;
    font-size: 14px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.probation-employee img {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    object-fit: cover;
}

/* Responsive Form */
.form-row-responsive {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .stat-number {
        font-size: 22px;
    }
    .modal-body {
        padding: 15px;
    }
    .table-responsive {
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    .probation-employee {
        width: 100%;
        justify-content: flex-start;
    }
    .period-card {
        padding: 15px;
    }
    .stat-card {
        padding: 15px;
    }
}
</style>

<div class="page-nav d-flex justify-content-between align-items-center flex-wrap">
        <h4 class="page-title mb-2"><i class="fas fa-star ml-2"></i> تقييم الأداء</h4>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#periodModal">
                <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">فترة تقييم</span>
            </button>
            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#evaluationModal">
                <i class="fas fa-clipboard-check"></i> <span class="d-none d-sm-inline">تقييم جديد</span>
            </button>
        </div>
    </div>
    
    <section class="content py-3">
        <div class="container-fluid">
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total'] ?? 0 ?></div>
                    <div class="stat-label">إجمالي التقييمات</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['draft'] ?? 0 ?></div>
                    <div class="stat-label">مسودات</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['submitted'] ?? 0 ?></div>
                    <div class="stat-label">قيد المراجعة</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['approved'] ?? 0 ?></div>
                    <div class="stat-label">معتمدة</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($stats['avg_percentage'] ?? 0, 1) ?>%</div>
                    <div class="stat-label">متوسط النسبة</div>
                </div>
            </div>
            
            <!-- Probation Employees Alert -->
            <?php if (!empty($probationEmployees)): ?>
            <div class="probation-alert">
                <h5><i class="fas fa-user-clock"></i> موظفون في فترة التجربة (<?= count($probationEmployees) ?>)</h5>
                <p class="mb-2 text-sm">هؤلاء الموظفون يحتاجون تقييم فترة التجربة:</p>
                <div class="d-flex flex-wrap">
                    <?php foreach ($probationEmployees as $emp): ?>
                    <div class="probation-employee">
                        <img src="<?= $emp['Photo'] ?: 'dist/img/avatar-default.png' ?>" alt="">
                        <span><?= htmlspecialchars($emp['FirstName'] . ' ' . $emp['LastName']) ?></span>
                        <small class="text-muted">(<?= $emp['days_employed'] ?> يوم)</small>
                        <button class="btn btn-sm btn-warning py-0 px-2" onclick="startEvaluation(<?= $emp['UserID'] ?>)">
                            <i class="fas fa-clipboard-check"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Evaluation Periods -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-calendar-alt ml-2"></i> فترات التقييم</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($periods)): ?>
                            <p class="text-muted text-center py-4">لا توجد فترات تقييم</p>
                            <?php else: ?>
                            <?php foreach ($periods as $period): ?>
                            <div class="period-card">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0"><?= htmlspecialchars($period['name_ar']) ?></h6>
                                    <span class="period-type"><?= $period['period_type'] ?></span>
                                </div>
                                <div class="text-muted small">
                                    <i class="far fa-calendar"></i>
                                    <?= $period['start_date'] ?> - <?= $period['end_date'] ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Evaluation Criteria -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-list-check ml-2"></i> معايير التقييم</h5>
                            <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#criteriaModal">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table criteria-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>المعيار</th>
                                            <th>الفئة</th>
                                            <th>الوزن</th>
                                            <th>الحد الأقصى</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($criteria as $c): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($c['name_ar']) ?></td>
                                            <td>
                                                <span class="category-badge category-<?= $c['category'] ?>">
                                                    <?= $c['category'] ?>
                                                </span>
                                            </td>
                                            <td><?= $c['weight'] ?></td>
                                            <td><?= $c['max_score'] ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </section>
</div>

<!-- Period Modal -->
<div class="modal fade" id="periodModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-plus ml-2"></i> إضافة فترة تقييم</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <!-- Added method="post" as fallback, though JS will intercept it -->
            <form id="periodForm" method="post" onsubmit="event.preventDefault();">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="required">اسم الفترة</label>
                        <input type="text" name="name_ar" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>الاسم بالإنجليزية</label>
                        <input type="text" name="name_en" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="required">نوع الفترة</label>
                        <select name="period_type" class="form-control" required>
                            <option value="annual">سنوي</option>
                            <option value="quarterly">ربع سنوي</option>
                            <option value="probation">فترة تجربة</option>
                            <option value="project">مشروع</option>
                            <option value="custom">مخصص</option>
                        </select>
                    </div>
                    <div class="form-row-responsive">
                        <div class="form-group">
                            <label class="required">تاريخ البداية</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="required">تاريخ النهاية</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Evaluation Modal -->
<div class="modal fade" id="evaluationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clipboard-check ml-2"></i> تقييم جديد</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <!-- Added method="post" and onsubmit preventDefault as fallback -->
            <form id="evaluationForm" method="post" onsubmit="event.preventDefault();">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="required">الموظف</label>
                        <select name="employee_id" class="form-control select2" required>
                            <option value="">اختر الموظف</option>
                            <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['UserID'] ?>">
                                <?= htmlspecialchars($emp['FirstName'] . ' ' . $emp['LastName']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required">فترة التقييم</label>
                        <select name="period_id" class="form-control" required>
                            <option value="">اختر الفترة</option>
                            <?php foreach ($periods as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-play"></i> بدء التقييم
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Criteria Modal -->
<div class="modal fade" id="criteriaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus ml-2"></i> إضافة معيار تقييم</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <!-- Added method="post" and onsubmit preventDefault as fallback -->
            <form id="criteriaForm" method="post" onsubmit="event.preventDefault();">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="required">اسم المعيار</label>
                        <input type="text" name="name_ar" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>الاسم بالإنجليزية</label>
                        <input type="text" name="name_en" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="required">الفئة</label>
                        <select name="category" class="form-control" required>
                            <option value="performance">الأداء</option>
                            <option value="behavior">السلوك</option>
                            <option value="skills">المهارات</option>
                            <option value="attendance">الحضور</option>
                            <option value="teamwork">العمل الجماعي</option>
                        </select>
                    </div>
                    <div class="form-row-responsive">
                        <div class="form-group">
                            <label>الوزن</label>
                            <input type="number" name="weight" class="form-control" value="1.0" step="0.1" min="0.1">
                        </div>
                        <div class="form-group">
                            <label>الحد الأقصى</label>
                            <input type="number" name="max_score" class="form-control" value="5" min="1" max="10">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>الوصف</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
// Include footer FIRST, so jQuery is loaded
include_once('inc/footer.php'); 
?>

<!-- NOW put the script AFTER the footer -->
<script>
$(document).ready(function() {
    // Initialize Select2
    if ($.fn.select2) {
        $('.select2').select2({
            dir: 'rtl',
            width: '100%',
            dropdownParent: $('#evaluationModal')
        });
    }
    
    // Period Form
    $('#periodForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.post('hr-app/index.php?action=create-evaluation-period', formData, function(res) {
            // Ensure response is parsed if it came back as text
            if(typeof res === 'string') {
                try { res = JSON.parse(res); } catch(e) {}
            }

            if (res.result) {
                toastr.success(res.msg || 'تم الحفظ بنجاح');
                $('#periodModal').modal('hide');
                setTimeout(function(){ location.reload(); }, 1000);
            } else {
                toastr.error(res.msg || 'حدث خطأ أثناء الحفظ');
            }
        });
    });
    
    // Evaluation Form
    $('#evaluationForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.post('hr-app/index.php?action=create-evaluation', formData, function(res) {
            if(typeof res === 'string') {
                try { res = JSON.parse(res); } catch(e) {}
            }

            if (res.result) {
                toastr.success(res.msg || 'تم إنشاء التقييم بنجاح');
                $('#evaluationModal').modal('hide');
                // Redirect to evaluation form
                if (res.data && res.data.id) {
                    window.location.href = 'evaluation-form?id=' + res.data.id;
                } else {
                    setTimeout(function(){ location.reload(); }, 1000);
                }
            } else {
                toastr.error(res.msg || 'حدث خطأ أثناء إنشاء التقييم');
            }
        });
    });
    
    // Criteria Form
    $('#criteriaForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.post('hr-app/index.php?action=save-evaluation-criteria', formData, function(res) {
            if(typeof res === 'string') {
                try { res = JSON.parse(res); } catch(e) {}
            }

            if (res.result) {
                toastr.success(res.msg || 'تم حفظ المعيار بنجاح');
                $('#criteriaModal').modal('hide');
                setTimeout(function(){ location.reload(); }, 1000);
            } else {
                toastr.error(res.msg || 'حدث خطأ أثناء حفظ المعيار');
            }
        });
    });
});

function startEvaluation(employeeId) {
    $('select[name="employee_id"]').val(employeeId).trigger('change');
    $('#evaluationModal').modal('show');
}
</script>