<?php
/**
 * Admin Mail Settings - SMTP Configuration & Email Template Editor
 * High-level security with encrypted credentials
 */

$appid = 'HR';
$page_perm = ['إعدادات النظام'];
$screen = 'إدارة الموارد البشرية';
$page_title = 'إعدادات البريد الإلكتروني';

include_once('inc/header.php');
require_once('inc/MailService.php');

// Check permission (allow system admin OR employer/HR manager)
if (!($User->userIsAdmin() || $User->userIsEmployer())) {
    echo '<script>location.replace("ess-dashboard");</script>';
    exit;
}

$mailService = new MailService($connect_pdo);
$settings = $mailService->getSettings();
// Branded default email template (used only if none saved)
$default_reset_template = '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>إعادة تعيين كلمة المرور</title></head><body style="background:#f3f4f6;margin:0;padding:24px;font-family:Tahoma, Cairo, Arial, sans-serif;color:#111827;">
  <table role="presentation" cellpadding="0" cellspacing="0" width="100%"><tr><td align="center">
    <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;background:#ffffff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;box-shadow:0 8px 24px rgba(13,33,165,.08)">
      <tr><td style="padding:0;background:linear-gradient(135deg,#0d21a5 0%, #6d7dcb 100%);height:6px"></td></tr>
      <tr><td style="padding:24px 24px 8px 24px;text-align:right"><img src="https://hr.gt-academy.com/dist/img/brand/logo-icon.png" alt="Vision HR" width="36" height="36" style="vertical-align:middle;border-radius:8px"> <span style="font-weight:800;font-size:18px;color:#0d21a5;margin-right:8px">Vision HR</span></td></tr>
      <tr><td style="padding:0 24px 16px 24px"><h2 style="margin:8px 0 0 0;font-size:20px">إعادة تعيين كلمة المرور</h2><p style="margin:6px 0 0;color:#374151">مرحباً {{USER_NAME}}، تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بك.</p></td></tr>
      <tr><td style="padding:0 24px 24px 24px"><a href="{{RESET_LINK}}" style="display:inline-block;background:#0d21a5;color:#fff;text-decoration:none;padding:12px 18px;border-radius:10px;font-weight:700">إعادة تعيين كلمة المرور</a>
        <p style="color:#6b7280;font-size:13px;margin-top:10px">رابط إعادة التعيين صالح لمدة {{EXPIRY_TIME}}. إذا لم تطلب ذلك، فتجاهل هذه الرسالة.</p>
      </td></tr>
      <tr><td style="padding:16px 24px;background:#f9fafb;border-top:1px solid #e5e7eb;color:#6b7280;font-size:12px">© {{CURRENT_YEAR}} {{COMPANY_NAME}}. جميع الحقوق محفوظة.</td></tr>
    </table>
  </td></tr></table>
</body></html>';
$default_reset_subject = MailService::getDefaultResetSubject();
$default_reset_template = MailService::getDefaultResetTemplate();
?>

<style>
/* Mail Settings Page Styles */
.mail-settings-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.settings-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    margin-bottom: 24px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
}

.card-header-custom {
    background: linear-gradient(135deg, #0d21a5 0%, #6d7dcb 100%);
    color: #fff;
    padding: 20px 24px;
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-header-custom i {
    margin-left: 10px;
}

.card-body-custom {
    padding: 24px;
}

.form-group-custom {
    margin-bottom: 20px;
}

.form-group-custom label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
    font-size: 14px;
}

.form-control-custom {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #fff;
}

.form-control-custom:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
    outline: none;
}

.encryption-badge {
    background: rgba(72, 187, 120, 0.15);
    color: #22c55e;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.info-box {
    background: #eef2ff;
    border-right: 4px solid #0d21a5;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    color: #0d21a5;
    font-size: 14px;
}

.info-box i {
    margin-left: 8px;
}

.btn-primary-custom {
    background: linear-gradient(135deg, #0d21a5 0%, #6d7dcb 100%);
    color: #fff;
    padding: 12px 28px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(13, 33, 165, 0.35);
}

.btn-secondary-custom {
    background: #6b7280;
    color: #fff;
    padding: 12px 28px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-secondary-custom:hover {
    background: #4b5563;
}

.variable-tag {
    display: inline-block;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    margin: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: monospace;
}

.variable-tag:hover {
    background: #e5e7eb;
    border-color: #667eea;
}

.template-textarea {
    min-height: 200px;
    font-family: monospace;
    font-size: 13px;
    line-height: 1.6;
}

/* Responsive */
@media (max-width: 992px) {
    .row > [class^="col-"] {
        margin-bottom: 15px;
    }
}

@media (max-width: 768px) {
    .mail-settings-container {
        padding: 12px 10px 24px;
    }
    
    .card-header-custom {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
    
    .card-body-custom {
        padding: 16px 14px;
    }
    
    .btn-primary-custom,
    .btn-secondary-custom {
        width: 100%;
        justify-content: center;
        margin-bottom: 10px;
    }
}
</style>


<div class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h1 class="m-0">⚙️ إعدادات البريد الإلكتروني و SMTP</h1>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid mail-settings-container">
        
        <!-- SMTP Configuration -->
        <div class="settings-card">
            <div class="card-header-custom">
                <span><i class="fas fa-server"></i> إعدادات خادم SMTP</span>
                <span class="encryption-badge"><i class="fas fa-lock"></i> مشفر AES-256</span>
            </div>
            <div class="card-body-custom">
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <strong>ملاحظة أمنية:</strong> يتم تشفير كلمة مرور SMTP باستخدام AES-256-CBC. لن يتم عرض كلمة المرور المحفوظة مطلقاً.
                </div>
                
                <form id="smtpSettingsForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-custom">
                                <label>خادم SMTP <span class="text-danger">*</span></label>
                                <input type="text" name="smtp_host" class="form-control-custom" 
                                       value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>" 
                                       placeholder="smtp.gmail.com" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group-custom">
                                <label>المنفذ <span class="text-danger">*</span></label>
                                <input type="number" name="smtp_port" class="form-control-custom" 
                                       value="<?= $settings['smtp_port'] ?? 587 ?>" 
                                       placeholder="587" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group-custom">
                                <label>التشفير <span class="text-danger">*</span></label>
                                <select name="smtp_encryption" class="form-control-custom" required>
                                    <option value="tls" <?= ($settings['smtp_encryption'] ?? '') == 'tls' ? 'selected' : '' ?>>TLS</option>
                                    <option value="ssl" <?= ($settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : '' ?>>SSL</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-custom">
                                <label>اسم المستخدم <span class="text-danger">*</span></label>
                                <input type="text" name="smtp_username" class="form-control-custom" 
                                       value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>" 
                                       placeholder="your-email@gmail.com" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-custom">
                                <label>كلمة المرور <i class="fas fa-lock text-success"></i></label>
                                <input type="password" name="smtp_password" class="form-control-custom" 
                                       placeholder="اتركه فارغاً للاحتفاظ بكلمة المرور الحالية">
                                <small class="text-muted">سيتم تشفير كلمة المرور تلقائياً قبل الحفظ</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-custom">
                                <label>البريد المرسل <span class="text-danger">*</span></label>
                                <input type="email" name="smtp_from_email" class="form-control-custom" 
                                       value="<?= htmlspecialchars($settings['smtp_from_email'] ?? '') ?>" 
                                       placeholder="noreply@company.com" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-custom">
                                <label>اسم المرسل <span class="text-danger">*</span></label>
                                <input type="text" name="smtp_from_name" class="form-control-custom" 
                                       value="<?= htmlspecialchars($settings['smtp_from_name'] ?? '') ?>" 
                                       placeholder="Vision HR System" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 flex-wrap" style="gap: 10px;">
                        <button type="submit" class="btn-primary-custom">
                            <i class="fas fa-save"></i> حفظ إعدادات SMTP
                        </button>
                        <button type="button" id="testConnectionBtn" class="btn-secondary-custom">
                            <i class="fas fa-plug"></i> اختبار الاتصال
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Email Template -->
        <div class="settings-card">
            <div class="card-header-custom">
                <span><i class="fas fa-envelope-open-text"></i> قالب بريد إعادة تعيين كلمة المرور</span>
            </div>
            <div class="card-body-custom">
                <div class="info-box">
                    <i class="fas fa-lightbulb"></i>
                    <strong>المتغيرات المتاحة:</strong> انقر على أي متغير لإدراجه في القالب
                </div>
                
                <div class="mb-3">
                    <span class="variable-tag" data-var="{{USER_NAME}}">{{USER_NAME}}</span>
                    <span class="variable-tag" data-var="{{RESET_LINK}}">{{RESET_LINK}}</span>
                    <span class="variable-tag" data-var="{{EXPIRY_TIME}}">{{EXPIRY_TIME}}</span>
                    <span class="variable-tag" data-var="{{CURRENT_YEAR}}">{{CURRENT_YEAR}}</span>
                    <span class="variable-tag" data-var="{{COMPANY_NAME}}">{{COMPANY_NAME}}</span>
                </div>
                
                <form id="templateForm">
                    <div class="form-group-custom">
                        <label>عنوان البريد <span class="text-danger">*</span></label>
                        <input type="text" name="reset_email_subject" class="form-control-custom" 
                               value="<?= htmlspecialchars($settings['reset_email_subject'] ?? $default_reset_subject) ?>" required>
                    </div>
                    
                    <div class="form-group-custom">
                        <label>محتوى القالب (HTML) <span class="text-danger">*</span></label>
                        <textarea name="reset_email_template" id="emailTemplate" 
                                  class="form-control-custom template-textarea" required><?= htmlspecialchars($settings['reset_email_template'] ?? $default_reset_template) ?></textarea>
                    </div>
                    
                    <div class="d-flex gap-2 flex-wrap" style="gap: 10px;">
                        <button type="submit" class="btn-primary-custom">
                            <i class="fas fa-save"></i> حفظ القالب
                        </button>
                        <button type="button" id="previewTemplateBtn" class="btn-secondary-custom">
                            <i class="fas fa-eye"></i> معاينة القالب
                        </button>
                        <button type="button" id="sendTestEmailBtn" class="btn-secondary-custom">
                            <i class="fas fa-paper-plane"></i> إرسال بريد تجريبي
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
    </div>
</section>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">معاينة القالب</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <iframe id="previewFrame" style="width:100%;height:400px;border:1px solid #ddd;border-radius:8px;"></iframe>
            </div>
        </div>
    </div>
</div>

<?php include_once('inc/footer.php'); ?>

<script>
$(document).ready(function() {
    // Robust shim for toastr/toast in case scripts are deferred
    if (typeof window.toastr === 'undefined') {
        window.toastr = {
            success: function(m){ console.log('SUCCESS:', m); },
            error: function(m){ console.error('ERROR:', m); },
            info: function(m){ console.info('INFO:', m); },
            warning: function(m){ console.warn('WARN:', m); }
        };
    }
    if (typeof window.toast === 'undefined') {
        window.toast = function(type, msg, title){
            if (window.toastr && typeof window.toastr[type] === 'function') {
                window.toastr[type](msg || '', title || '');
            } else {
                console.log('toast', type, msg || '');
            }
        };
    }
    // Page JS (no debug)

    // Insert variable into template
    $('.variable-tag').on('click', function() {
        var varText = $(this).data('var');
        var textarea = $('#emailTemplate');
        var cursorPos = textarea[0].selectionStart;
        var textBefore = textarea.val().substring(0, cursorPos);
        var textAfter = textarea.val().substring(cursorPos);
        textarea.val(textBefore + varText + textAfter);
        textarea.focus();
    });
    
    // Save SMTP Settings
    $('#smtpSettingsForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        
        $.ajax({
            url: 'hr-app/index.php?action=save-mail-settings',
            method: 'POST',
            data: formData + '&type=smtp',
            dataType: 'json',
            success: function(res) {
                if (res.result) {
                    toastr.success(res.msg || 'تم حفظ الإعدادات بنجاح');
                } else {
                    toastr.error(res.msg || 'حدث خطأ');
                }
            },
            error: function() {
                toastr.error('حدث خطأ في الاتصال');
            }
        });
    });
    
    // Save Template
    $('#templateForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        
        $.ajax({
            url: 'hr-app/index.php?action=save-mail-settings',
            method: 'POST',
            data: formData + '&type=template',
            dataType: 'json',
            success: function(res) {
                if (res.result) {
                    toastr.success(res.msg || 'تم حفظ القالب بنجاح');
                } else {
                    toastr.error(res.msg || 'حدث خطأ');
                }
            },
            error: function() {
                toastr.error('حدث خطأ في الاتصال');
            }
        });
    });
    
    // Test Connection
    $('#testConnectionBtn').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري الاختبار...');
        
        $.ajax({
            url: 'hr-app/index.php?action=test-smtp-connection',
            method: 'POST',
            data: $('#smtpSettingsForm').serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.result) {
                    toastr.success('تم الاتصال بنجاح!');
                } else {
                    toastr.error(res.msg || 'فشل الاتصال');
                }
            },
            error: function() {
                toastr.error('حدث خطأ في الاتصال');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-plug"></i> اختبار الاتصال');
            }
        });
    });
    
    // Preview Template
    $('#previewTemplateBtn').on('click', function() {
        var template = $('#emailTemplate').val();
        template = template.replace(/\{\{USER_NAME\}\}/g, 'أحمد محمد');
        template = template.replace(/\{\{RESET_LINK\}\}/g, 'https://hr.gt-academy.com/reset-password?token=example');
        template = template.replace(/\{\{EXPIRY_TIME\}\}/g, '60 دقيقة');
        template = template.replace(/\{\{CURRENT_YEAR\}\}/g, new Date().getFullYear());
        template = template.replace(/\{\{COMPANY_NAME\}\}/g, 'Vision HR');
        
        var iframe = document.getElementById('previewFrame');
        iframe.contentWindow.document.open();
        iframe.contentWindow.document.write(template);
        iframe.contentWindow.document.close();
        
        $('#previewModal').modal('show');
    });
    
    // Send Test Email
    $('#sendTestEmailBtn').on('click', function() {
        var email = prompt('أدخل البريد الإلكتروني للاختبار:');
        if (!email) return;
        
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري الإرسال...');
        
        $.ajax({
            url: 'hr-app/index.php?action=send-test-email',
            method: 'POST',
            data: { email: email },
            dataType: 'json',
            success: function(res) {
                if (res.result) {
                    toastr.success('تم إرسال البريد التجريبي بنجاح!');
                } else {
                    toastr.error(res.msg || 'فشل إرسال البريد');
                }
            },
            error: function() {
                toastr.error('حدث خطأ في الاتصال');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> إرسال بريد تجريبي');
            }
        });
    });
});
</script>
