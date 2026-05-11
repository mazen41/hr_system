<?php
/**
 * ESS - Employee Notifications
 * View all notifications for the logged-in employee
 */
$screen = 'الخدمة الذاتية';
$page_title = 'الإشعارات';
$ess_active = 'notifications';
include_once('inc/header.php');

if (!$user) {
    header('Location: login-sys');
    exit;
}
?>

<section class="content">
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bell"></i> جميع الإشعارات</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-primary" onclick="markAllAsRead()">
                            <i class="fas fa-check-double"></i> تحديد الكل كمقروء
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="notificationsList">
                        <div class="text-center py-5">
                            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                            <p class="mt-2 text-muted">جاري التحميل...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</section>

<style>
.notification-item {
    padding: 16px 20px;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.2s;
    cursor: pointer;
}
.notification-item:hover {
    background: #f9fafb;
}
.notification-item.unread {
    background: #eff6ff;
    border-left: 4px solid #3b82f6;
}
.notification-item.unread:hover {
    background: #dbeafe;
}
.notification-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.notification-icon.type-info { background: #dbeafe; color: #3b82f6; }
.notification-icon.type-success { background: #dcfce7; color: #22c55e; }
.notification-icon.type-warning { background: #fef3c7; color: #f59e0b; }
.notification-icon.type-danger { background: #fee2e2; color: #ef4444; }
.notification-content {
    flex: 1;
    min-width: 0;
    text-align: right;
    direction: rtl;
}
.notification-title {
    font-weight: 600;
    font-size: 15px;
    color: #1f2937;
    margin-bottom: 4px;
}
.notification-body {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 4px;
}
.notification-time {
    font-size: 12px;
    color: #9ca3af;
}
.notification-badge {
    background: #3b82f6;
    color: white;
    font-size: 10px;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 600;
}
.empty-state {
    text-align: center;
    padding: 60px 20px;
}
.empty-state i {
    font-size: 64px;
    color: #d1d5db;
    margin-bottom: 16px;
}
.empty-state h4 {
    color: #6b7280;
    margin-bottom: 8px;
}
.empty-state p {
    color: #9ca3af;
    font-size: 14px;
}

@media (max-width: 576px) {
    .card-header {
        flex-direction: column;
        align-items: stretch !important;
    }
    .card-title {
        margin-bottom: 10px;
    }
    .card-tools {
        width: 100%;
        display: flex;
        gap: 8px;
    }
    .card-tools .btn {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
    }
}
</style>

<?php include_once('inc/footer.php'); ?>

<script>
$(document).ready(function() {
    loadNotifications();
});

function loadNotifications() {
    $.get('hr-app/index.php?action=get-notifications&limit=100', function(res) {
        if (res.result && res.data) {
            renderNotifications(res.data);
        } else {
            $('#notificationsList').html(
                '<div class="empty-state">' +
                '<i class="fas fa-bell-slash"></i>' +
                '<h4>لا توجد إشعارات</h4>' +
                '<p>لم تتلق أي إشعارات بعد</p>' +
                '</div>'
            );
        }
    }).fail(function() {
        $('#notificationsList').html(
            '<div class="empty-state">' +
            '<i class="fas fa-exclamation-triangle text-warning"></i>' +
            '<h4>خطأ في التحميل</h4>' +
            '<p>حدث خطأ أثناء تحميل الإشعارات</p>' +
            '</div>'
        );
    });
}

function renderNotifications(notifications) {
    if (notifications.length === 0) {
        $('#notificationsList').html(
            '<div class="empty-state">' +
            '<i class="fas fa-bell-slash"></i>' +
            '<h4>لا توجد إشعارات</h4>' +
            '<p>لم تتلق أي إشعارات بعد</p>' +
            '</div>'
        );
        return;
    }

    var html = '';
    notifications.forEach(function(notif) {
        var isUnread = notif.is_read == 0;
        var iconClass = 'type-' + (notif.type || 'info');
        var icon = getNotificationIcon(notif.type);

        html += '<div class="notification-item ' + (isUnread ? 'unread' : '') + '" onclick="markAsRead(' + notif.id + ')">';
        html += '<div class="d-flex align-items-start flex-row-reverse">';
        html += '<div class="notification-icon ' + iconClass + '"><i class="' + icon + '"></i></div>';
        html += '<div class="notification-content ml-3">';
        html += '<div class="notification-title">' + escapeHtml(notif.title);
        if (isUnread) {
            html += ' <span class="notification-badge">جديد</span>';
        }
        html += '</div>';
        if (notif.body) {
            html += '<div class="notification-body">' + escapeHtml(notif.body) + '</div>';
        }
        html += '<div class="notification-time"><i class="far fa-clock"></i> ' + notif.created_at + '</div>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
    });

    $('#notificationsList').html(html);
}

function getNotificationIcon(type) {
    var icons = {
        'info': 'fas fa-info-circle',
        'success': 'fas fa-check-circle',
        'warning': 'fas fa-exclamation-triangle',
        'danger': 'fas fa-times-circle'
    };
    return icons[type] || icons['info'];
}

function markAsRead(id) {
    $.post('hr-app/index.php?action=mark-notification-read', { notification_id: id }, function(res) {
        if (res.result) {
            loadNotifications();
            if (typeof loadHeaderNotifications === 'function') {
                loadHeaderNotifications();
            }
        }
    });
}

function markAllAsRead() {
    $.post('hr-app/index.php?action=mark-all-notifications-read', function(res) {
        if (res.result) {
            toastr.success('تم تحديد جميع الإشعارات كمقروءة');
            loadNotifications();
            if (typeof loadHeaderNotifications === 'function') {
                loadHeaderNotifications();
            }
        }
    });
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
