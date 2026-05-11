<?php
/**
 * Vision HR - Branded Email Template
 * Use this template for all system emails
 * 
 * Variables:
 * $emailTitle - Email subject/title
 * $emailContent - Main email content (HTML allowed)
 * $actionButton - Array with 'text' and 'url' for CTA button (optional)
 * $footerText - Additional footer text (optional)
 */

$emailTitle = $emailTitle ?? 'Vision HR';
$emailContent = $emailContent ?? '';
$actionButton = $actionButton ?? null;
$footerText = $footerText ?? '';
$currentYear = date('Y');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($emailTitle) ?></title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
            background-color: #f0f2f5;
            direction: rtl;
            text-align: right;
        }
        
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #f0f2f5;
            padding: 20px;
        }
        
        .email-container {
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(13, 33, 165, 0.08);
        }
        
        .email-header {
            background: linear-gradient(135deg, #0d21a5 0%, #0a1a85 100%);
            padding: 30px 20px;
            text-align: center;
        }
        
        .email-logo {
            max-width: 180px;
            height: auto;
        }
        
        .email-body {
            padding: 40px 30px;
            color: #1a1a2e;
            line-height: 1.8;
        }
        
        .email-title {
            color: #0d21a5;
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 20px 0;
        }
        
        .email-content {
            font-size: 16px;
            color: #4b5563;
            margin-bottom: 30px;
        }
        
        .email-content p {
            margin: 0 0 15px 0;
        }
        
        .email-button {
            display: inline-block;
            background: linear-gradient(135deg, #0d21a5 0%, #0a1a85 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            box-shadow: 0 4px 12px rgba(13, 33, 165, 0.2);
            transition: all 0.3s ease;
        }
        
        .email-button:hover {
            box-shadow: 0 6px 16px rgba(13, 33, 165, 0.3);
            transform: translateY(-2px);
        }
        
        .email-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #b9c7f8, transparent);
            margin: 30px 0;
        }
        
        .email-info-box {
            background: #e9edfd;
            border-right: 4px solid #0d21a5;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .email-info-box p {
            margin: 0;
            color: #0d21a5;
            font-weight: 600;
        }
        
        .email-footer {
            background: #f8f9fa;
            padding: 30px 20px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        
        .email-footer-links {
            margin: 15px 0;
        }
        
        .email-footer-links a {
            color: #0d21a5;
            text-decoration: none;
            margin: 0 10px;
            font-weight: 600;
        }
        
        .email-footer-links a:hover {
            text-decoration: underline;
        }
        
        .email-social {
            margin: 20px 0;
        }
        
        .email-social a {
            display: inline-block;
            margin: 0 8px;
            color: #6b7280;
            text-decoration: none;
        }
        
        .email-copyright {
            color: #9ca3af;
            font-size: 12px;
            margin-top: 15px;
        }
        
        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 10px;
            }
            
            .email-body {
                padding: 30px 20px;
            }
            
            .email-title {
                font-size: 20px;
            }
            
            .email-content {
                font-size: 14px;
            }
            
            .email-button {
                display: block;
                text-align: center;
                padding: 12px 24px;
                font-size: 14px;
            }
            
            .email-logo {
                max-width: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <!-- Header with Logo -->
            <div class="email-header">
                <img src="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/dist/img/brand/logo-secondary.png" alt="Vision HR" class="email-logo">
            </div>
            
            <!-- Email Body -->
            <div class="email-body">
                <h1 class="email-title"><?= $emailTitle ?></h1>
                
                <div class="email-content">
                    <?= $emailContent ?>
                </div>
                
                <?php if ($actionButton): ?>
                <div style="text-align: center;">
                    <a href="<?= htmlspecialchars($actionButton['url']) ?>" class="email-button">
                        <?= htmlspecialchars($actionButton['text']) ?>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($footerText): ?>
                <div class="email-divider"></div>
                <div class="email-info-box">
                    <p><?= $footerText ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Footer -->
            <div class="email-footer">
                <div class="email-footer-links">
                    <a href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>">الصفحة الرئيسية</a>
                    <a href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/ess-dashboard">لوحة التحكم</a>
                    <a href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/support">الدعم الفني</a>
                </div>
                
                <div class="email-divider" style="margin: 20px auto; max-width: 200px;"></div>
                
                <p style="margin: 10px 0; color: #4b5563;">
                    نظام Vision HR لإدارة الموارد البشرية
                </p>
                
                <p class="email-copyright">
                    &copy; <?= $currentYear ?> Vision HR. جميع الحقوق محفوظة.
                </p>
            </div>
        </div>
        
        <!-- Spacer -->
        <div style="height: 20px;"></div>
    </div>
</body>
</html>
