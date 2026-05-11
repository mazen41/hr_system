<?php
/**
 * ESS - Employee Settings Page
 * Personal settings for employees
 */
$screen = 'الخدمة الذاتية';
$page_title = 'إعدادات الموظف';
$ess_active = 'settings';
include_once('inc/header.php');

// Get current user settings
$userSettings = [];
if ($user) {
    $stmt = $connect_pdo->prepare("
        SELECT u.UserID, u.FirstName, u.LastName, u.UserEmail, u.Phone, u.Photo
        FROM tblusers u WHERE u.UserID = ?
    ");
    $stmt->execute([$user]);
    $userSettings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    
    // Get user preferences from user_settings table (if exists)
    try {
        $prefStmt = $connect_pdo->prepare("SELECT setting_key, setting_value FROM user_settings WHERE user_id = ?");
        $prefStmt->execute([$user]);
        while ($pref = $prefStmt->fetch(PDO::FETCH_ASSOC)) {
            $userSettings[$pref['setting_key']] = $pref['setting_value'];
        }
    } catch (Exception $e) {
        // Table may not exist yet, use defaults
        $userSettings['notification_email'] = 1;
        $userSettings['notification_sms'] = 0;
    }
}

// Handle form submission
$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        
        $stmt = $connect_pdo->prepare("UPDATE tblusers SET Phone = ?, UserEmail = ? WHERE UserID = ?");
        if ($stmt->execute([$phone, $email, $user])) {
            $message = 'تم تحديث البيانات بنجاح';
            $messageType = 'success';
            $userSettings['Phone'] = $phone;
            $userSettings['UserEmail'] = $email;
        } else {
            $message = 'حدث خطأ أثناء التحديث';
            $messageType = 'error';
        }
    }
    
    if ($action === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if ($newPassword !== $confirmPassword) {
            $message = 'كلمة المرور الجديدة غير متطابقة';
            $messageType = 'error';
        } elseif (strlen($newPassword) < 6) {
            $message = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
            $messageType = 'error';
        } else {
            // Verify current password
            $stmt = $connect_pdo->prepare("SELECT Password FROM tblusers WHERE UserID = ?");
            $stmt->execute([$user]);
            $storedPassword = $stmt->fetchColumn();
            
            if ($storedPassword === $currentPassword || password_verify($currentPassword, $storedPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $connect_pdo->prepare("UPDATE tblusers SET Password = ? WHERE UserID = ?");
                if ($stmt->execute([$hashedPassword, $user])) {
                    $message = 'تم تغيير كلمة المرور بنجاح';
                    $messageType = 'success';
                } else {
                    $message = 'حدث خطأ أثناء تغيير كلمة المرور';
                    $messageType = 'error';
                }
            } else {
                $message = 'كلمة المرور الحالية غير صحيحة';
                $messageType = 'error';
            }
        }
    }
    
    if ($action === 'update_notifications') {
        $notifEmail = isset($_POST['notification_email']) ? 1 : 0;
        $notifSms = isset($_POST['notification_sms']) ? 1 : 0;
        
        try {
            // Save to user_settings table
            $stmt = $connect_pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, 'notification_email', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute([$user, $notifEmail]);
            $stmt = $connect_pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, 'notification_sms', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute([$user, $notifSms]);
            
            $message = 'تم تحديث إعدادات الإشعارات';
            $messageType = 'success';
            $userSettings['notification_email'] = $notifEmail;
            $userSettings['notification_sms'] = $notifSms;
        } catch (Exception $e) {
            $message = 'حدث خطأ أثناء التحديث';
            $messageType = 'error';
        }
    }
}

// Get unread notifications count
$unreadCount = 0;
try {
    require_once 'classes/NotificationService.php';
    $notificationService = new NotificationService($connect_pdo);
    $unreadCount = $notificationService->getUnreadCount($user);
} catch (Exception $e) {}
?>

<section class="content">
<div class="container-fluid">

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Settings Navigation -->
        <div class="col-lg-3 col-md-4 mb-4">
            <div class="ess-stat-card">
                <div class="text-center mb-4">
                    <?php if (!empty($userSettings['Photo'])): ?>
                        <img src="<?= htmlspecialchars($userSettings['Photo']) ?>" alt="صورة" class="rounded-circle" style="width:80px;height:80px;object-fit:cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center mx-auto" style="width:80px;height:80px;font-size:2rem;color:#fff;">
                            <?= mb_substr($userSettings['FirstName'] ?? 'م', 0, 1, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>
                    <h5 class="mt-3 mb-1"><?= htmlspecialchars(($userSettings['FirstName'] ?? '') . ' ' . ($userSettings['LastName'] ?? '')) ?></h5>
                    <small class="text-muted"><?= htmlspecialchars($userSettings['UserEmail'] ?? '') ?></small>
                </div>
                
                <nav class="nav flex-column settings-nav">
                    <a class="nav-link active" href="#profile" data-toggle="tab">
                        <i class="fas fa-user"></i> البيانات الشخصية
                    </a>
                    <a class="nav-link" href="#password" data-toggle="tab">
                        <i class="fas fa-lock"></i> تغيير كلمة المرور
                    </a>
                    <a class="nav-link" href="#notifications" data-toggle="tab">
                        <i class="fas fa-bell"></i> الإشعارات
                        <?php if ($unreadCount > 0): ?>
                        <span class="badge badge-danger ml-2"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Settings Content -->
        <div class="col-lg-9 col-md-8">
            <div class="tab-content">
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile">
                    <div class="ess-stat-card">
                        <h5 class="mb-4"><i class="fas fa-user text-primary"></i> البيانات الشخصية</h5>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>البريد الإلكتروني</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($userSettings['UserEmail'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>رقم الهاتف</label>
                                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($userSettings['Phone'] ?? '') ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ التغييرات
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Password Tab -->
                <div class="tab-pane fade" id="password">
                    <div class="ess-stat-card">
                        <h5 class="mb-4"><i class="fas fa-lock text-primary"></i> تغيير كلمة المرور</h5>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_password">
                            <div class="mb-3">
                                <label>كلمة المرور الحالية</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>كلمة المرور الجديدة</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label>تأكيد كلمة المرور الجديدة</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i> تغيير كلمة المرور
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Notifications Tab -->
                <div class="tab-pane fade" id="notifications">
                    <div class="ess-stat-card">
                        <h5 class="mb-4"><i class="fas fa-bell text-primary"></i> إعدادات الإشعارات</h5>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_notifications">
                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="notifEmail" name="notification_email" <?= ($userSettings['notification_email'] ?? 1) ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="notifEmail">إشعارات البريد الإلكتروني</label>
                            </div>
                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="notifSms" name="notification_sms" <?= ($userSettings['notification_sms'] ?? 0) ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="notifSms">إشعارات الرسائل النصية</label>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ الإعدادات
                            </button>
                        </form>

                        <hr class="my-4">

                        <h6 class="mb-3">الإشعارات الأخيرة</h6>
                        <div id="notificationsList">
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-spinner fa-spin"></i> جاري التحميل...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</section>

<?php include_once('inc/footer.php'); ?>

<style>
.settings-nav .nav-link {
    padding: 12px 16px;
    border-radius: 10px;
    color: #374151;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.2s;
}
.settings-nav .nav-link:hover {
    background: #f3f4f6;
}
.settings-nav .nav-link.active {
    background: #0d21a5;
    color: #fff;
}
.settings-nav .nav-link i {
    width: 20px;
    text-align: center;
}
.notification-item {
    padding: 12px;
    border-radius: 8px;
    background: #f9fafb;
    margin-bottom: 8px;
    border-right: 3px solid #0d21a5;
}
.notification-item.read {
    opacity: 0.7;
    border-right-color: #d1d5db;
}
</style>

<script>
$(document).ready(function() {
    // Load notifications
    loadNotifications();
    
    // Handle tab from URL hash
    if (window.location.hash) {
        $('a[href="' + window.location.hash + '"]').tab('show');
    }
    
    // Update URL hash on tab change
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        window.location.hash = e.target.hash;
    });
});

function loadNotifications() {
    $.get('hr-app/index.php?action=get-notifications', function(res) {
        if (res.data && res.data.length > 0) {
            var html = '';
            res.data.forEach(function(notif) {
                html += '<div class="notification-item ' + (notif.is_read ? 'read' : '') + '" data-id="' + notif.id + '">';
                html += '<div class="d-flex justify-content-between align-items-start">';
                html += '<div>';
                html += '<strong>' + escapeHtml(notif.title) + '</strong>';
                html += '<p class="mb-1 text-muted small">' + escapeHtml(notif.body || '') + '</p>';
                html += '<small class="text-muted">' + notif.created_at + '</small>';
                html += '</div>';
                if (!notif.is_read) {
                    html += '<button class="btn btn-sm btn-outline-primary" onclick="markRead(' + notif.id + ')"><i class="fas fa-check"></i></button>';
                }
                html += '</div></div>';
            });
            $('#notificationsList').html(html);
        } else {
            $('#notificationsList').html('<div class="text-center text-muted py-3"><i class="fas fa-bell-slash"></i> لا توجد إشعارات</div>');
        }
    });
}

function markRead(id) {
    $.post('hr-app/index.php?action=mark-notification-read', { notification_id: id }, function(res) {
        if (res.result) {
            $('.notification-item[data-id="' + id + '"]').addClass('read').find('button').remove();
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
