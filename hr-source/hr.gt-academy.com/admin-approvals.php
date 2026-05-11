<?php
/**
 * Admin Approvals - Pending approval requests dashboard
 */
$screen = 'الموافقات المعلقة';
$page_title = 'الموافقات المعلقة';
$page = 'admin-approvals';
include_once('inc/header.php');

// Get pending approvals from workflow system
$pendingApprovals = [];
try {
        require_once 'classes/WorkflowManager.php';
    $workflowManager = new WorkflowManager($connect_pdo);
    
    // Check if $_SESSION['UserID'] is set before using it
    $currentUserId = $_SESSION['UserID'] ?? null;
    if ($currentUserId) {
        $pendingApprovals = $workflowManager->getPendingApprovals($currentUserId);
    } else {
        // Handle the case where UserID is not in session
        // For now, we'll just keep $pendingApprovals as an empty array
        // You might want to add a redirect to a login page or an error message here in a production environment
        error_log("Warning: UserID not found in session for admin-approvals page. User might not be logged in.");
    }
} catch (Exception $e) {
    error_log("WorkflowManager error: " . $e->getMessage()); // Log the exception message
}

// Also get direct pending items from tables (fallback/additional)
$directPending = [];

// Pending Orders (status IS NULL = pending)
$stmtOrders = $connect_pdo->query("
    SELECT 
        o.id, o.title, o.description, o.CreatedDate, o.UserID,
        u.FirstName, u.LastName, u.Photo,
        'order' as entity_type
    FROM emp_order o
    LEFT JOIN tblusers u ON u.UserID = o.UserID
    WHERE o.Status IS NULL AND o.Draft = 1
    ORDER BY o.CreatedDate DESC
");
$pendingOrders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

// Pending Leave Requests (status IS NULL = pending)
$stmtLeaves = $connect_pdo->query("
    SELECT 
        l.id, l.description as title, l.leave_start_date, l.leave_end_date, l.day_leave, l.CreatedDate, l.UserID,
        u.FirstName, u.LastName, u.Photo,
        lc.Name as leave_type,
        'leave_request' as entity_type
    FROM tblleaverequest l
    LEFT JOIN tblusers u ON u.UserID = l.UserID
    LEFT JOIN leaveclassification lc ON lc.Id = l.leavetype
    WHERE l.status IS NULL
    ORDER BY l.CreatedDate DESC
");
$pendingLeaves = $stmtLeaves->fetchAll(PDO::FETCH_ASSOC);

// Pending Advances (status IS NULL = pending)
$stmtAdvances = $connect_pdo->query("
    SELECT 
a.Id as id, a.Amount, a.description as title, a.CreatedDate, a.UserID,
        u.FirstName, u.LastName, u.Photo,
        'advance_request' as entity_type
    FROM tblempadvances a
    LEFT JOIN tblusers u ON u.UserID = a.UserID
    WHERE a.status IS NULL
    ORDER BY a.CreatedDate DESC
");
$pendingAdvances = $stmtAdvances->fetchAll(PDO::FETCH_ASSOC);

// Combine all direct pending items
$directPending = array_merge($pendingOrders, $pendingLeaves, $pendingAdvances);

// Count totals
$totalPending = count($pendingApprovals) + count($directPending);
$orderCount = count($pendingOrders);
$leaveCount = count($pendingLeaves);
$advanceCount = count($pendingAdvances);
?>

<style>
.approval-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-right: 4px solid #0d21a5;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.approval-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.12);
}
.approval-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 10px;
}
.approval-type {
    background: #e8f4fd;
    color: #0d21a5;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}
.approval-date {
    color: #6b7280;
    font-size: 13px;
}
.requester-info {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}
.requester-photo {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e5e7eb;
}
.requester-name {
    font-weight: 600;
    color: #1f2937;
}
.requester-step {
    font-size: 13px;
    color: #6b7280;
}
.approval-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.btn-approve {
    background: #10b981;
    color: #fff;
    border: none;
    padding: 8px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-approve:hover {
    background: #059669;
    color: #fff;
}
.btn-reject {
    background: #ef4444;
    color: #fff;
    border: none;
    padding: 8px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-reject:hover {
    background: #dc2626;
    color: #fff;
}
.btn-view {
    background: #f3f4f6;
    color: #374151;
    border: none;
    padding: 8px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-view:hover {
    background: #e5e7eb;
}
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}
.empty-state i {
    font-size: 60px;
    color: #d1d5db;
    margin-bottom: 20px;
}
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}
.stat-card {
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
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

@media (max-width: 768px) {
    .approval-header {
        flex-direction: column;
    }
    .approval-actions {
        width: 100%;
    }
    .approval-actions .btn {
        flex: 1;
    }
}

@media (max-width: 480px) {
    .stats-row {
        grid-template-columns: 1fr;
    }
    .approval-actions {
        flex-direction: column;
    }
    .approval-actions .btn {
        width: 100%;
        margin-bottom: 5px;
    }
}
</style>

<div class="page-nav">
        <h4 class="page-title"><i class="fas fa-tasks ml-2"></i> الموافقات المعلقة</h4>
    </div>
    
    <section class="content py-3">
        <div class="container-fluid">
            
            <!-- Stats Row -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-number"><?= $totalPending ?></div>
                    <div class="stat-label">طلبات معلقة</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $orderCount ?></div>
                    <div class="stat-label">طلبات إدارية</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $leaveCount ?></div>
                    <div class="stat-label">طلبات إجازة</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $advanceCount ?></div>
                    <div class="stat-label">طلبات سلف</div>
                </div>
            </div>
            
            <!-- Pending Approvals List -->
            <?php if ($totalPending == 0): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h5>لا توجد طلبات معلقة</h5>
                <p>جميع الطلبات تم معالجتها</p>
            </div>
            <?php else: ?>
            
            <div class="approvals-list">
                <!-- Direct Pending Orders -->
                <?php foreach ($pendingOrders as $order): ?>
                <div class="approval-card" data-type="order" data-id="<?= $order['id'] ?>">
                    <div class="approval-header">
                        <span class="approval-type" style="background:#fef3c7;color:#92400e;">
                            <i class="fas fa-file-signature"></i> طلب إداري
                        </span>
                        <span class="approval-date">
                            <i class="far fa-clock"></i>
                            <?= date('Y-m-d H:i', strtotime($order['CreatedDate'])) ?>
                        </span>
                    </div>
                    
                    <div class="requester-info">
                        <img src="<?= $order['Photo'] ?: 'dist/img/avatar-default.png' ?>" alt="" class="requester-photo">
                        <div>
                            <div class="requester-name"><?= htmlspecialchars($order['FirstName'] . ' ' . $order['LastName']) ?></div>
                            <div class="requester-step"><strong><?= htmlspecialchars($order['title']) ?></strong></div>
                        </div>
                    </div>
                    
                    <div class="approval-actions">
                        <button class="btn btn-approve" onclick="approveItem('order', <?= $order['id'] ?>)">
                            <i class="fas fa-check"></i> موافقة
                        </button>
                        <button class="btn btn-reject" onclick="rejectItem('order', <?= $order['id'] ?>)">
                            <i class="fas fa-times"></i> رفض
                        </button>
                        <button class="btn btn-view" onclick="viewItem('order', <?= $order['id'] ?>)">
                            <i class="fas fa-eye"></i> عرض
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Direct Pending Leaves -->
                <?php foreach ($pendingLeaves as $leave): ?>
                <div class="approval-card" data-type="leave" data-id="<?= $leave['id'] ?>">
                    <div class="approval-header">
                        <span class="approval-type" style="background:#dcfce7;color:#166534;">
                            <i class="fas fa-calendar-alt"></i> طلب إجازة
                        </span>
                        <span class="approval-date">
                            <i class="far fa-clock"></i>
                            <?= date('Y-m-d H:i', strtotime($leave['CreatedDate'])) ?>
                        </span>
                    </div>
                    
                    <div class="requester-info">
                        <img src="<?= $leave['Photo'] ?: 'dist/img/avatar-default.png' ?>" alt="" class="requester-photo">
                        <div>
                            <div class="requester-name"><?= htmlspecialchars($leave['FirstName'] . ' ' . $leave['LastName']) ?></div>
                            <div class="requester-step">
                                <strong><?= htmlspecialchars($leave['leave_type'] ?? 'إجازة') ?></strong> - 
                                <?= $leave['day_leave'] ?> يوم
                                (<?= $leave['leave_start_date'] ?> إلى <?= $leave['leave_end_date'] ?>)
                            </div>
                        </div>
                    </div>
                    
                    <div class="approval-actions">
                        <button class="btn btn-approve" onclick="approveItem('leave', <?= $leave['id'] ?>)">
                            <i class="fas fa-check"></i> موافقة
                        </button>
                        <button class="btn btn-reject" onclick="rejectItem('leave', <?= $leave['id'] ?>)">
                            <i class="fas fa-times"></i> رفض
                        </button>
                        <button class="btn btn-view" onclick="viewItem('leave', <?= $leave['id'] ?>)">
                            <i class="fas fa-eye"></i> عرض
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Direct Pending Advances -->
                <?php foreach ($pendingAdvances as $advance): ?>
                <div class="approval-card" data-type="advance" data-id="<?= $advance['id'] ?>">
                    <div class="approval-header">
                        <span class="approval-type" style="background:#dbeafe;color:#1e40af;">
                            <i class="fas fa-hand-holding-usd"></i> طلب سلفة
                        </span>
                        <span class="approval-date">
                            <i class="far fa-clock"></i>
                            <?= date('Y-m-d H:i', strtotime($advance['CreatedDate'])) ?>
                        </span>
                    </div>
                    
                    <div class="requester-info">
                        <img src="<?= $advance['Photo'] ?: 'dist/img/avatar-default.png' ?>" alt="" class="requester-photo">
                        <div>
                            <div class="requester-name"><?= htmlspecialchars($advance['FirstName'] . ' ' . $advance['LastName']) ?></div>
                            <div class="requester-step">
                                <strong>مبلغ: <?= number_format($advance['Amount'], 2) ?> ر.س</strong>
                                <?php if ($advance['title']): ?>
                                - <?= htmlspecialchars($advance['title']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="approval-actions">
                        <button class="btn btn-approve" onclick="approveItem('advance', <?= $advance['id'] ?>)">
                            <i class="fas fa-check"></i> موافقة
                        </button>
                        <button class="btn btn-reject" onclick="rejectItem('advance', <?= $advance['id'] ?>)">
                            <i class="fas fa-times"></i> رفض
                        </button>
                        <button class="btn btn-view" onclick="viewItem('advance', <?= $advance['id'] ?>)">
                            <i class="fas fa-eye"></i> عرض
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Workflow-based Pending Approvals -->
                <?php foreach ($pendingApprovals as $approval): ?>
                <div class="approval-card" data-instance="<?= $approval['instance_id'] ?>">
                    <div class="approval-header">
                        <span class="approval-type"><?= htmlspecialchars($approval['workflow_name']) ?></span>
                        <span class="approval-date">
                            <i class="far fa-clock"></i>
                            <?= date('Y-m-d H:i', strtotime($approval['requested_at'])) ?>
                        </span>
                    </div>
                    
                    <div class="requester-info">
                        <?php 
                        $photo = $approval['requester_photo'] ?: 'dist/img/avatar-default.png';
                        ?>
                        <img src="<?= htmlspecialchars($photo) ?>" alt="" class="requester-photo">
                        <div>
                            <div class="requester-name">
                                <?= htmlspecialchars($approval['requester_first'] . ' ' . $approval['requester_last']) ?>
                            </div>
                            <div class="requester-step">
                                <i class="fas fa-layer-group"></i>
                                <?= htmlspecialchars($approval['step_name']) ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="approval-actions">
                        <button class="btn btn-approve" onclick="processApproval(<?= $approval['instance_id'] ?>, 'approved')">
                            <i class="fas fa-check"></i> موافقة
                        </button>
                        <button class="btn btn-reject" onclick="showRejectModal(<?= $approval['instance_id'] ?>)">
                            <i class="fas fa-times"></i> رفض
                        </button>
                        <button class="btn btn-view" onclick="viewDetails('<?= $approval['entity_type'] ?>', <?= $approval['entity_id'] ?>)">
                            <i class="fas fa-eye"></i> عرض
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php endif; ?>
            
        </div>
    </section>
</div>
<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">سبب الرفض</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rejectInstanceId">
                <div class="form-group">
                    <label>سبب الرفض <span class="text-danger">*</span></label>
                    <textarea id="rejectComment" class="form-control" rows="3" placeholder="أدخل سبب الرفض..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-danger" onclick="confirmReject()">
                    <i class="fas fa-times"></i> تأكيد الرفض
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تفاصيل الطلب</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="detailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function processApproval(instanceId, action, comment = '') {
    Swal.fire({
        title: action === 'approved' ? 'تأكيد الموافقة' : 'تأكيد الرفض',
        text: action === 'approved' ? 'هل تريد الموافقة على هذا الطلب؟' : 'هل تريد رفض هذا الطلب؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'نعم',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: action === 'approved' ? '#10b981' : '#ef4444'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('hr-app/index.php?action=process-approval', {
                instance_id: instanceId,
                approval_action: action,
                comment: comment
            }, function(response) {
                if (response.result) {
                    toastr.success(response.msg);
                    // Remove the card with animation
                    $('[data-instance="' + instanceId + '"]').fadeOut(300, function() {
                        $(this).remove();
                        // Update count
                        var remaining = $('.approval-card').length;
                        $('.stat-number').first().text(remaining);
                        if (remaining === 0) {
                            location.reload();
                        }
                    });
                } else {
                    toastr.error(response.msg);
                }
            });
        }
    });
}

function showRejectModal(instanceId) {
    $('#rejectInstanceId').val(instanceId);
    $('#rejectComment').val('');
    $('#rejectModal').modal('show');
}

function confirmReject() {
    var instanceId = $('#rejectInstanceId').val();
    var comment = $('#rejectComment').val();
    
    if (!comment.trim()) {
        toastr.warning('يرجى إدخال سبب الرفض');
        return;
    }
    
    $('#rejectModal').modal('hide');
    
    $.post('hr-app/index.php?action=process-approval', {
        instance_id: instanceId,
        approval_action: 'rejected',
        comment: comment
    }, function(response) {
        if (response.result) {
            toastr.success(response.msg);
            $('[data-instance="' + instanceId + '"]').fadeOut(300, function() {
                $(this).remove();
                var remaining = $('.approval-card').length;
                $('.stat-number').first().text(remaining);
                if (remaining === 0) {
                    location.reload();
                }
            });
        } else {
            toastr.error(response.msg);
        }
    });
}

function viewDetails(entityType, entityId) {
    $('#detailsContent').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
    $('#detailsModal').modal('show');
    
    // Load details based on entity type
    var url = '';
    switch(entityType) {
        case 'leave_request':
            url = 'hr-app/index.php?action=get-leave-request&id=' + entityId;
            break;
        case 'advance_request':
            url = 'hr-app/index.php?action=get-advance-request&id=' + entityId;
            break;
        case 'promotion_request':
            url = 'hr-app/index.php?action=get-promotion-request&id=' + entityId;
            break;
        default:
            $('#detailsContent').html('<p class="text-center text-muted">لا تتوفر تفاصيل لهذا النوع</p>');
            return;
    }
    
    $.get(url, function(response) {
        if (response.result && response.data) {
            var html = '<table class="table table-bordered">';
            for (var key in response.data) {
                html += '<tr><th>' + key + '</th><td>' + (response.data[key] || '-') + '</td></tr>';
            }
            html += '</table>';
            $('#detailsContent').html(html);
        } else {
            $('#detailsContent').html('<p class="text-center text-muted">لا تتوفر تفاصيل</p>');
        }
    }).fail(function() {
        $('#detailsContent').html('<p class="text-center text-danger">حدث خطأ في تحميل البيانات</p>');
    });
}

// Direct approve/reject handlers for items not in workflow system
function approveItem(type, id) {
    Swal.fire({
        title: 'تأكيد الموافقة',
        text: 'هل تريد الموافقة على هذا الطلب؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'نعم، موافق',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#10b981'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('hr-app/index.php?action=direct-approve', {
                type: type,
                id: id,
                status: 1
            }, function(response) {
                if (response.result) {
                    toastr.success('تمت الموافقة بنجاح');
                    $('[data-type="' + type + '"][data-id="' + id + '"]').fadeOut(300, function() {
                        $(this).remove();
                        updateCounts();
                    });
                } else {
                    toastr.error(response.msg || 'حدث خطأ');
                }
            }, 'json');
        }
    });
}

function rejectItem(type, id) {
    Swal.fire({
        title: 'سبب الرفض',
        input: 'textarea',
        inputPlaceholder: 'أدخل سبب الرفض...',
        showCancelButton: true,
        confirmButtonText: 'رفض',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#ef4444',
        inputValidator: (value) => {
            if (!value) {
                return 'يرجى إدخال سبب الرفض';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('hr-app/index.php?action=direct-approve', {
                type: type,
                id: id,
                status: 2,
                comment: result.value
            }, function(response) {
                if (response.result) {
                    toastr.success('تم الرفض بنجاح');
                    $('[data-type="' + type + '"][data-id="' + id + '"]').fadeOut(300, function() {
                        $(this).remove();
                        updateCounts();
                    });
                } else {
                    toastr.error(response.msg || 'حدث خطأ');
                }
            }, 'json');
        }
    });
}

function viewItem(type, id) {
    var url = '';
    switch(type) {
        case 'order':
            url = 'hr-app/index.php?action=get-order-details&id=' + id;
            break;
        case 'leave':
            url = 'hr-app/index.php?action=get-leave-request&id=' + id;
            break;
        case 'advance':
            url = 'hr-app/index.php?action=get-advance-request&id=' + id;
            break;
    }
    
    $('#detailsContent').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
    $('#detailsModal').modal('show');
    
    $.get(url, function(response) {
        if (response.result && response.data) {
            var html = '<table class="table table-bordered">';
            for (var key in response.data) {
                html += '<tr><th style="width:30%">' + key + '</th><td>' + (response.data[key] || '-') + '</td></tr>';
            }
            html += '</table>';
            $('#detailsContent').html(html);
        } else {
            $('#detailsContent').html('<p class="text-center text-muted">لا تتوفر تفاصيل</p>');
        }
    }, 'json').fail(function() {
        $('#detailsContent').html('<p class="text-center text-danger">حدث خطأ في تحميل البيانات</p>');
    });
}

function updateCounts() {
    var remaining = $('.approval-card').length;
    $('.stat-number').first().text(remaining);
    
    // Count by type
    var orders = $('[data-type="order"]').length;
    var leaves = $('[data-type="leave"]').length;
    var advances = $('[data-type="advance"]').length;
    
    $('.stat-card').eq(1).find('.stat-number').text(orders);
    $('.stat-card').eq(2).find('.stat-number').text(leaves);
    $('.stat-card').eq(3).find('.stat-number').text(advances);
    
    if (remaining === 0) {
        location.reload();
    }
}
</script>

<?php include_once('inc/footer.php'); ?>
