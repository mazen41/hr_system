<?php
/**
 * Admin Salary Ranges - Department salary range management
 */
$screen = 'نطاقات الرواتب';
$page_title = 'نطاقات الرواتب';
$page = 'admin-salary-ranges';
include_once('inc/header.php');

require_once 'classes/EvaluationManager.php';
$evalManager = new EvaluationManager($connect_pdo);

$salaryRanges = $evalManager->getAllSalaryRanges();

// Get sections, grades, job titles for dropdowns
$sections = $connect_pdo->query("SELECT Id, Name FROM tblsection ORDER BY Name")->fetchAll(PDO::FETCH_ASSOC);
$grades = $connect_pdo->query("SELECT Id, Name FROM tbljobgrade ORDER BY Name")->fetchAll(PDO::FETCH_ASSOC);
$jobTitles = $connect_pdo->query("SELECT Id, Name FROM tbljobtitle ORDER BY Name")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* Responsive Modal Styles */
.modal-dialog {
    margin: 10px auto;
    max-width: 95%;
}
@media (min-width: 576px) {
    .modal-dialog { max-width: 500px; }
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
}
.modal-body {
    padding: 20px;
    max-height: calc(100vh - 200px);
    overflow-y: auto;
}

/* Table Styles */
.salary-table {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.salary-table th {
    background: #f8fafc;
    font-weight: 600;
    padding: 12px 15px;
    border-bottom: 2px solid #e5e7eb;
    white-space: nowrap;
}
.salary-table td {
    padding: 12px 15px;
    vertical-align: middle;
}
.salary-range {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.salary-min, .salary-max {
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
}
.salary-min {
    background: #fef3c7;
    color: #92400e;
}
.salary-max {
    background: #d1fae5;
    color: #065f46;
}
.salary-arrow {
    color: #9ca3af;
}

.form-row-responsive {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}
.empty-state i {
    font-size: 50px;
    color: #d1d5db;
    margin-bottom: 15px;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 14px;
    }
    .salary-min, .salary-max {
        font-size: 12px;
        padding: 3px 8px;
    }
}

@media (max-width: 480px) {
    .page-nav {
        flex-direction: column;
        align-items: stretch !important;
        gap: 10px;
    }
    .page-nav .btn {
        width: 100%;
    }
    .salary-range {
        flex-direction: column;
        align-items: flex-start;
    }
    .salary-arrow {
        transform: rotate(90deg);
        align-self: flex-start;
        margin-left: 15px;
    }
}
</style>

<div class="page-nav d-flex justify-content-between align-items-center flex-wrap">
        <h4 class="page-title mb-2"><i class="fas fa-money-bill-wave ml-2"></i> نطاقات الرواتب</h4>
        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#rangeModal">
            <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">إضافة نطاق</span>
        </button>
    </div>
    
    <section class="content py-3">
        <div class="container-fluid">
            
            <div class="card">
                <div class="card-body p-0">
                    <?php if (empty($salaryRanges)): ?>
                    <div class="empty-state">
                        <i class="fas fa-coins"></i>
                        <h5>لا توجد نطاقات رواتب</h5>
                        <p>قم بإضافة نطاقات الرواتب لكل قسم</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table salary-table mb-0">
                            <thead>
                                <tr>
                                    <th>القسم</th>
                                    <th>الدرجة</th>
                                    <th>المسمى الوظيفي</th>
                                    <th>نطاق الراتب</th>
                                    <th>تاريخ السريان</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($salaryRanges as $range): ?>
                                <tr>
                                    <td><?= htmlspecialchars($range['section_name']) ?></td>
                                    <td><?= $range['grade_name'] ? htmlspecialchars($range['grade_name']) : '<span class="text-muted">الكل</span>' ?></td>
                                    <td><?= $range['job_title_name'] ? htmlspecialchars($range['job_title_name']) : '<span class="text-muted">الكل</span>' ?></td>
                                    <td>
                                        <div class="salary-range">
                                            <span class="salary-min"><?= number_format($range['min_salary']) ?></span>
                                            <span class="salary-arrow">←</span>
                                            <span class="salary-max"><?= number_format($range['max_salary']) ?></span>
                                            <small class="text-muted"><?= $range['currency'] ?></small>
                                        </div>
                                    </td>
                                    <td><?= $range['effective_date'] ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editRange(<?= htmlspecialchars(json_encode($range)) ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </section>
</div>
<!-- Range Modal -->
<div class="modal fade" id="rangeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-money-bill-wave ml-2"></i> نطاق الراتب</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="rangeForm" method="post" onsubmit="event.preventDefault();"> <!-- Added method="post" and onsubmit="event.preventDefault();" -->
                <input type="hidden" name="id" id="rangeId">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="required">القسم</label>
                        <select name="section_id" id="sectionId" class="form-control" required>
                            <option value="">اختر القسم</option>
                            <?php foreach ($sections as $s): ?>
                            <option value="<?= $s['Id'] ?>"><?= htmlspecialchars($s['Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row-responsive">
                        <div class="form-group">
                            <label>الدرجة (اختياري)</label>
                            <select name="grade_id" id="gradeId" class="form-control">
                                <option value="">جميع الدرجات</option>
                                <?php foreach ($grades as $g): ?>
                                <option value="<?= $g['Id'] ?>"><?= htmlspecialchars($g['Name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>المسمى الوظيفي (اختياري)</label>
                            <select name="job_title_id" id="jobTitleId" class="form-control">
                                <option value="">جميع المسميات</option>
                                <?php foreach ($jobTitles as $j): ?>
                                <option value="<?= $j['Id'] ?>"><?= htmlspecialchars($j['Name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row-responsive">
                        <div class="form-group">
                            <label class="required">الحد الأدنى</label>
                            <input type="number" name="min_salary" id="minSalary" class="form-control" required min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label class="required">الحد الأقصى</label>
                            <input type="number" name="max_salary" id="maxSalary" class="form-control" required min="0" step="0.01">
                        </div>
                    </div>
                    
                    <div class="form-row-responsive">
                        <div class="form-group">
                            <label>العملة</label>
                            <select name="currency" id="currency" class="form-control">
                                <option value="SAR">ريال سعودي</option>
                                <option value="USD">دولار</option>
                                <option value="EUR">يورو</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="required">تاريخ السريان</label>
                            <input type="date" name="effective_date" id="effectiveDate" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>ملاحظات</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
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
console.log("admin-salary-ranges.php script block started."); // Debug log
$(document).ready(function() {
    console.log("Document ready event fired."); // Debug log

    // Check if the form exists before attaching the listener
    if ($('#rangeForm').length) {
        console.log("Found #rangeForm. Attaching submit listener..."); // Debug log
        $('#rangeForm').on('submit', function(e) {
            e.preventDefault();
            console.log("rangeForm submitted via AJAX!"); // Debug log to confirm event fired
            var formData = $(this).serialize(); // Capture form data
            console.log("Form Data: ", formData); // Debug log form data
            
            var minSalary = parseFloat($('#minSalary').val());
            var maxSalary = parseFloat($('#maxSalary').val());
            
            if (minSalary >= maxSalary) {
                toastr.warning('الحد الأدنى يجب أن يكون أقل من الحد الأقصى');
                console.log("Validation failed: minSalary >= maxSalary"); // Debug log validation fail
                return;
            }
            
            $.post('hr-app/index.php?action=save-salary-range', formData, function(res) {
                console.log("AJAX response received: ", res); // Debug log raw response
                // Ensure response is parsed if it came back as text
                if(typeof res === 'string') {
                    try { res = JSON.parse(res); } catch(e) {
                        toastr.error('خطأ في تحليل استجابة الخادم.');
                        console.error("JSON Parse Error: ", e, "Response: ", res);
                        $('#rangeModal').modal('hide'); // Hide modal on error
                        return;
                    }
                }

                if (res.result) {
                    toastr.success(res.msg || 'تم الحفظ بنجاح.');
                    $('#rangeModal').modal('hide');
                    setTimeout(function(){ location.reload(); }, 1000);
                } else {
                    // Hide modal first so error is visible
                    $('#rangeModal').modal('hide');
                    // Show error with delay to ensure modal is hidden
                    setTimeout(function() {
                        toastr.error(res.msg || 'حدث خطأ أثناء الحفظ.');
                    }, 300);
                    console.error("Server Error: ", res.msg, res.data, res.debug_session); // Log full response for debugging
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                // Hide modal first so error is visible
                $('#rangeModal').modal('hide');
                setTimeout(function() {
                    toastr.error('فشل في الاتصال بالخادم: ' + textStatus);
                }, 300);
                console.error("AJAX Fail: ", textStatus, errorThrown, jqXHR.responseText); // Log full AJAX error
            });
        });
        console.log("Submit listener attached to #rangeForm."); // Debug log
    } else {
        console.error("Error: #rangeForm not found when trying to attach submit listener."); // Debug log if form is missing
    }
    
    // Reset form when modal closes - this is already correctly defined globally in your script
    window.editRange = function(range) { // Make it global to be accessible from inline onclick
        console.log("editRange called with: ", range); // Debug log
        $('#rangeId').val(range.id);
        $('#sectionId').val(range.section_id);
        $('#gradeId').val(range.grade_id || '');
        $('#jobTitleId').val(range.job_title_id || '');
        $('#minSalary').val(range.min_salary);
        $('#maxSalary').val(range.max_salary);
        $('#currency').val(range.currency);
        $('#effectiveDate').val(range.effective_date);
        $('#notes').val(range.notes || '');
        $('#rangeModal').modal('show');
    };

    // Reset form when modal closes - this is already correctly defined
    $('#rangeModal').on('hidden.bs.modal', function() {
        console.log("rangeModal hidden event fired, resetting form."); // Debug log
        $('#rangeForm')[0].reset();
        $('#rangeId').val('');
    });
});
</script>