<?php
/**
 * Admin Rewards - Rewards and bonuses management
 */
$screen = 'المكافآت';
$page_title = 'المككافآت';
$page = 'admin-rewards';
include_once('inc/header.php');

require_once 'classes/EvaluationManager.php';
$evalManager = new EvaluationManager($connect_pdo);

$allRewards = $evalManager->getPendingRewards();
$stats = $evalManager->getRewardStats();

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
    max-height: calc(100vh - 200px);
    overflow-y: auto;
}

/* Stats */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}
.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border-top: 3px solid #10b981;
}
.stat-number {
    font-size: 26px;
    font-weight: 700;
    color: #10b981;
}
.stat-label {
    font-size: 13px;
    color: #6b7280;
    margin-top: 5px;
}

/* Reward Cards */
.reward-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border-right: 4px solid #10b981;
    transition: transform 0.2s;
}
.reward-card:hover {
    transform: translateY(-2px);
}
.reward-type-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.type-bonus { background: #d1fae5; color: #065f46; }
.type-certificate { background: #dbeafe; color: #1e40af; }
.type-promotion { background: #fef3c7; color: #92400e; }
.type-gift { background: #fce7f3; color: #9d174d; }
.type-time_off { background: #e0e7ff; color: #3730a3; }

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.status-pending { background: #fef3c7; color: #92400e; }
.status-approved { background: #d1fae5; color: #065f46; }
.status-delivered { background: #dbeafe; color: #1e40af; }

.employee-info {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 15px 0;
}
.employee-photo {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e5e7eb;
}
.reward-amount {
    font-size: 20px;
    font-weight: 700;
    color: #10b981;
}

.form-row-responsive {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .stat-number {
        font-size: 22px;
    }
    .reward-card {
        padding: 15px;
    }
}
@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    .employee-info {
        flex-direction: column;
        align-items: flex-start;
        text-align: right;
    }
    .employee-photo {
        margin-bottom: 5px;
    }
    .d-flex.gap-2 .btn {
        width: 100%;
    }
}
</style>

<div class="page-nav d-flex justify-content-between align-items-center flex-wrap">
        <h4 class="page-title mb-2"><i class="fas fa-gift ml-2"></i> المكافآت</h4>
        <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#rewardModal">
            <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">مكافأة جديدة</span>
        </button>
    </div>
    
    <section class="content py-3">
        <div class="container-fluid">
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total'] ?? 0 ?></div>
                    <div class="stat-label">إجمالي المكافآت</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['delivered'] ?? 0 ?></div>
                    <div class="stat-label">تم تسليمها</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($stats['total_bonus_amount'] ?? 0) ?></div>
                    <div class="stat-label">إجمالي المبالغ (ر.س)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['unique_employees'] ?? 0 ?></div>
                    <div class="stat-label">موظفون مكافأون</div>
                </div>
            </div>
            
            <!-- All Rewards -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-gift ml-2"></i> جميع المكافآت</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($allRewards)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-check-circle fa-3x mb-3" style="color:#d1d5db;"></i>
                        <p>لا توجد مكافآت</p>
                    </div>
                    <?php else: ?>
                    <div class="row">
                        <?php foreach ($allRewards as $reward): ?>
                        <div class="col-lg-6 col-xl-4">
                            <div class="reward-card" data-id="<?= $reward['id'] ?>">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0"><?= htmlspecialchars($reward['title_ar']) ?></h6>
                                    <div class="d-flex gap-1">
                                        <span class="reward-type-badge type-<?= $reward['reward_type'] ?>">
                                            <?= $reward['reward_type'] ?>
                                        </span>
                                        <span class="status-badge status-<?= $reward['status'] ?>">
                                            <?php
                                            $statusLabels = ['pending' => 'بانتظار الاعتماد', 'approved' => 'معتمدة', 'delivered' => 'تم التسليم'];
                                            echo $statusLabels[$reward['status']] ?? $reward['status'];
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="employee-info">
                                    <img src="<?= $reward['emp_photo'] ?: 'dist/img/avatar-default.png' ?>" 
                                         alt="" class="employee-photo">
                                    <div>
                                        <div class="font-weight-bold">
                                            <?= htmlspecialchars($reward['emp_first'] . ' ' . $reward['emp_last']) ?>
                                        </div>
                                        <small class="text-muted">
                                            بواسطة: <?= htmlspecialchars($reward['awarded_by_first'] . ' ' . $reward['awarded_by_last']) ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <?php if ($reward['amount']): ?>
                                <div class="reward-amount mb-3">
                                    <?= number_format($reward['amount']) ?> <?= $reward['currency'] ?? 'ر.س' ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="d-flex gap-2">
                                    <?php if ($reward['status'] === 'pending'): ?>
                                    <button class="btn btn-success btn-sm flex-fill" onclick="approveReward(<?= $reward['id'] ?>)">
                                        <i class="fas fa-check"></i> اعتماد
                                    </button>
                                    <?php elseif ($reward['status'] === 'approved'): ?>
                                    <button class="btn btn-primary btn-sm flex-fill" onclick="deliverReward(<?= $reward['id'] ?>)">
                                        <i class="fas fa-hand-holding"></i> تسليم
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="viewReward(<?= $reward['id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </section>
</div>
<!-- Reward Modal -->
<div class="modal fade" id="rewardModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-gift ml-2"></i> إضافة مكافأة</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <!-- Added method="post" and onsubmit preventDefault as fallback -->
            <form id="rewardForm" method="post" onsubmit="event.preventDefault();">
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
                        <label class="required">نوع المكافأة</label>
                        <select name="reward_type" class="form-control" required>
                            <option value="bonus">مكافأة مالية</option>
                            <option value="certificate">شهادة تقدير</option>
                            <option value="promotion">ترقية</option>
                            <option value="gift">هدية</option>
                            <option value="time_off">إجازة إضافية</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">عنوان المكافأة</label>
                        <input type="text" name="title_ar" class="form-control" required 
                               placeholder="مثال: مكافأة الأداء المتميز">
                    </div>
                    
                    <div class="form-group">
                        <label>الوصف</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="form-row-responsive">
                        <div class="form-group">
                            <label>المبلغ (للمكافآت المالية)</label>
                            <input type="number" name="amount" class="form-control" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>العملة</label>
                            <select name="currency" class="form-control">
                                <option value="SAR">ريال سعودي</option>
                                <option value="USD">دولار</option>
                                <option value="EUR">يورو</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>تاريخ المنح</label>
                        <input type="date" name="awarded_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success">
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
console.log("admin-rewards.php script block started."); // Debug log
$(document).ready(function() {
    console.log("Document ready event fired."); // Debug log

    if ($.fn.select2) {
        console.log("Select2 is available. Initializing select2..."); // Debug log
        $('.select2').select2({
            dir: 'rtl',
            width: '100%',
            dropdownParent: $('#rewardModal')
        });
        console.log("Select2 initialization complete."); // Debug log
    } else {
        console.warn("Select2 is NOT available."); // Debug log if Select2 is missing
    }
    
    // Check if the form exists before attaching the listener
    if ($('#rewardForm').length) {
        console.log("Found #rewardForm. Attaching submit listener..."); // Debug log
        $('#rewardForm').on('submit', function(e) {
            e.preventDefault();
            console.log("rewardForm submitted via AJAX!"); // Debug log to confirm event fired
            var formData = $(this).serialize(); // Capture form data
            console.log("Form Data: ", formData); // Debug log form data

            $.post('hr-app/index.php?action=create-reward', formData, function(res) {
                console.log("AJAX response received: ", res); // Debug log raw response
                // Ensure response is parsed if it came back as text
                if(typeof res === 'string') {
                    try { res = JSON.parse(res); } catch(e) {
                        toastr.error('خطأ في تحليل استجابة الخادم.');
                        console.error("JSON Parse Error: ", e, "Response: ", res);
                        return;
                    }
                }

                if (res.result) {
                    toastr.success(res.msg || 'تم الحفظ بنجاح.');
                    $('#rewardModal').modal('hide');
                    setTimeout(function(){ location.reload(); }, 1000);
                } else {
                    toastr.error(res.msg || 'حدث خطأ أثناء الحفظ.');
                    console.error("Server Error: ", res.msg, res.data, res.debug_session); // Log full response for debugging
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                toastr.error('فشل في الاتصال بالخادم: ' + textStatus);
                console.error("AJAX Fail: ", textStatus, errorThrown, jqXHR.responseText); // Log full AJAX error
            });
        });
        console.log("Submit listener attached to #rewardForm."); // Debug log
    } else {
        console.error("Error: #rewardForm not found when trying to attach submit listener."); // Debug log if form is missing
    }


    // Approve Reward function - this is called from inline onclick, so less likely to fail event-wise
    window.approveReward = function(id) { // Define as global so onclick can find it
        Swal.fire({
            title: 'اعتماد المكافأة',
            text: 'هل تريد اعتماد هذه المكافأة؟',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'نعم، اعتماد',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#10b981'
        }).then((result) => {
            if (result.isConfirmed) {
                console.log("Approving reward ID: " + id); // Debug log
                $.post('hr-app/index.php?action=approve-reward', { reward_id: id }, function(res) {
                    console.log("Approve AJAX response: ", res); // Debug log raw response
                    if(typeof res === 'string') {
                        try { res = JSON.parse(res); } catch(e) {
                            toastr.error('خطأ في تحليل استجابة الخادم.');
                            console.error("JSON Parse Error: ", e, "Response: ", res);
                            return;
                        }
                    }
                    if (res.result) {
                        toastr.success(res.msg || 'تم الاعتماد بنجاح.');
                        setTimeout(function(){ location.reload(); }, 1000);
                    } else {
                        toastr.error(res.msg || 'فشل الاعتماد.');
                        console.error("Server Error (Approve): ", res.msg, res.data, res.debug_session);
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    toastr.error('فشل في الاتصال بالخادم: ' + textStatus);
                    console.error("AJAX Fail (Approve): ", textStatus, errorThrown, jqXHR.responseText);
                });
            }
        });
    };

    // Deliver Reward function
    window.deliverReward = function(id) {
        Swal.fire({
            title: 'تسليم المكافأة',
            text: 'هل تريد تسليم هذه المكافأة؟',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'نعم، تسليم',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#3b82f6'
        }).then((result) => {
            if (result.isConfirmed) {
                console.log("Delivering reward ID: " + id);
                $.post('hr-app/index.php?action=deliver-reward', { reward_id: id }, function(res) {
                    console.log("Deliver AJAX response: ", res);
                    if(typeof res === 'string') {
                        try { res = JSON.parse(res); } catch(e) {
                            toastr.error('خطأ في تحليل استجابة الخادم.');
                            console.error("JSON Parse Error: ", e, "Response: ", res);
                            return;
                        }
                    }
                    if (res.result) {
                        toastr.success(res.msg || 'تم التسليم بنجاح.');
                        setTimeout(function(){ location.reload(); }, 1000);
                    } else {
                        toastr.error(res.msg || 'فشل التسليم.');
                        console.error("Server Error (Deliver): ", res.msg, res.data, res.debug_session);
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    toastr.error('فشل في الاتصال بالخادم: ' + textStatus);
                    console.error("AJAX Fail (Deliver): ", textStatus, errorThrown, jqXHR.responseText);
                });
            }
        });
    };

    // View Reward function - also called from inline onclick
    window.viewReward = function(id) { // Define as global
        toastr.info('عرض تفاصيل المكافأة #' + id);
        console.log("Viewing reward ID: " + id); // Debug log
    };

}); // End of $(document).ready
</script>