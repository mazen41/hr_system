<?php
$today_date = date("Y-m-d");

 function siteInfo($connect){
    //$query = 'SELECT * FROM tblsite WHERE SiteUrl = :site limit 1 ';
     $query = 'SELECT * FROM tblsite order by AccountID desc limit 1 ';
    //$values = array(':site' => $subdomain);
        try
        {
            $res = $connect->prepare($query);
            $res->execute();
        }
        catch (PDOException $e)
        {
           //throw new Exception('!');
           return false;
        }
        $row = $res->fetch(PDO::FETCH_ASSOC);
        if (is_array($row))
        {
            return $row;
 
        }
        return false;
}

$instance = siteInfo($connect_pdo);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//if(isset($_POST['user_inpt'])){
    

$login = FALSE;

    try
    {
        $login = $User->login($_POST['user_inpt'], $_POST['user_password']);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
        die();
    }

    if ($login && empty($_SESSION['fatel_error']))
    {
        // Audit: successful login
        $Audit->logLogin($User->getId(), $_POST['user_inpt'], true);

        $home_page = !empty($_SESSION['user']['home_page']) ? $_SESSION['user']['home_page'] : 'employer-dashboard';
        echo '<script> location.replace("'.$home_page.'"); </script>';
        die();
    }
    else
    {
        // Audit: failed login attempt
        $Audit->logLogin(0, $_POST['user_inpt'] ?? '', false);

        $login_error = 'معلومات الدخول خاطئة';
        if(!empty($_SESSION['fatel_error'])){
            $login_error = $_SESSION['fatel_error'];
           session_destroy();
        }
    }
    
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>تسجيل الدخول - Vision HR</title>
  <meta name="theme-color" content="#0f172a">
  <link rel="manifest" href="manifest.json">
  <link rel="icon" type="image/png"  href="dist/img/brand/logo-icon.png">
  <link rel="apple-touch-icon" href="dist/img/brand/logo-icon.png">
  
  <!-- Google Font: Cairo (Arabic) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="dist/css/adminlte.css">
  <link rel="stylesheet" href="dist/css/brand.css">
  
  <!-- Modern UI/UX Styles -->
  <style>
    :root {
      --primary: #0d21a5;
      --primary-hover: #0a1a85;
      --bg-dark: #071466;
      --card-bg: rgba(30, 41, 59, 0.7);
      --card-border: rgba(255, 255, 255, 0.08);
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
      --input-bg: rgba(15, 23, 42, 0.5);
    }

    body.login-page {
        min-height: 100vh;
        background-color: var(--bg-dark);
        background-image: 
            radial-gradient(circle at 15% 50%, rgba(109, 125, 203, 0.24) 0%, transparent 50%),
            radial-gradient(circle at 85% 30%, rgba(13, 33, 165, 0.22) 0%, transparent 50%);
        font-family: 'Cairo', sans-serif;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 20px;
        position: relative;
        overflow: hidden;
    }

    /* Modern Fade-in Animation */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .login-box {
        width: 100%;
        max-width: 420px;
        z-index: 10;
        animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    /* Logo & Headings */
    .brand-logo {
        max-width: 160px;
        filter: drop-shadow(0 4px 10px rgba(0,0,0,0.3));
        margin-bottom: 15px;
        transition: transform 0.3s ease;
    }
    
    .brand-logo:hover {
        transform: scale(1.02);
    }

    .system-title {
        color: var(--text-muted);
        font-size: 1.1rem;
        font-weight: 500;
        letter-spacing: 0.5px;
        margin-bottom: 30px;
    }

    /* Glassmorphism Card */
    .card {
        background: var(--card-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid var(--card-border);
        border-radius: 24px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        overflow: hidden;
    }

    .card-header {
        background: transparent;
        border-bottom: 1px solid var(--card-border);
        padding: 25px 20px 15px;
    }

    .card-header h5 {
        color: var(--text-main);
        font-weight: 700;
        font-size: 1.4rem;
        margin: 0;
    }

    .card-body {
        padding: 35px 30px;
    }

    /* Input Fields Modernization */
    .input-group {
        border-radius: 14px;
        background: var(--input-bg);
        border: 1px solid var(--card-border);
        transition: all 0.3s ease;
        margin-bottom: 20px !important;
        overflow: hidden;
    }

    .input-group:focus-within {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        background: rgba(30, 41, 59, 0.9);
    }

    .form-control {
        background: transparent !important;
        border: none;
        height: 55px;
        padding: 0 20px;
        color: var(--text-main) !important;
        font-size: 1rem;
    }

    .form-control::placeholder {
        color: #64748b !important;
    }

    .form-control:focus {
        background: transparent;
        box-shadow: none;
    }

    /* Icon container inside input */
    .input-group-text {
        background: transparent;
        border: none;
        color: #64748b;
        padding: 0 20px;
        font-size: 1.1rem;
    }

    /* Beautiful Primary Button */
    .btn-primary {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
        border: none;
        border-radius: 14px;
        height: 55px;
        font-weight: 700;
        font-size: 1.1rem;
        color: #fff;
        box-shadow: 0 10px 20px rgba(13, 33, 165, 0.25);
        transition: all 0.3s ease;
        margin-top: 10px;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 25px rgba(13, 33, 165, 0.4);
    }

    .btn-primary:active {
        transform: translateY(0);
    }

    /* Alerts */
    .alert-danger {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #fca5a5;
        border-radius: 14px;
        font-weight: 500;
        padding: 15px;
    }

    /* Links and Footer Text */
    .small.bod {
        color: var(--text-muted);
        font-size: 0.95rem;
    }

    .forgot-link-wrap {
        margin-top: 22px;
        text-align: center;
        position: relative;
        z-index: 20;
    }

    .forgot-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 220px;
        min-height: 48px;
        padding: 0 18px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(185, 199, 248, 0.22);
        color: #dbe4ff !important;
        font-weight: 700;
        text-decoration: none !important;
        box-shadow: 0 10px 24px rgba(7, 20, 102, 0.18);
        transition: all 0.3s ease;
        pointer-events: auto;
    }

    .forgot-link:hover {
        color: #ffffff !important;
        border-color: rgba(185, 199, 248, 0.46);
        background: rgba(185, 199, 248, 0.1);
        transform: translateY(-2px);
    }

    .recover-modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(7, 20, 102, 0.74);
        backdrop-filter: blur(10px);
        z-index: 999;
    }

    .recover-modal.is-open {
        display: flex;
    }

    .recover-dialog {
        width: 100%;
        max-width: 500px;
        background: rgba(21, 32, 71, 0.97);
        border: 1px solid rgba(185, 199, 248, 0.14);
        border-radius: 28px;
        box-shadow: 0 30px 60px rgba(7, 20, 102, 0.42);
        overflow: hidden;
        color: var(--text-main);
        animation: fadeInUp 0.28s ease;
    }

    .recover-dialog__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 22px 24px 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .recover-dialog__header h6 {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 800;
        color: #f8fafc;
    }

    .recover-close {
        width: 42px;
        height: 42px;
        border: 1px solid rgba(185, 199, 248, 0.18);
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.06);
        color: #dbe4ff;
        cursor: pointer;
        transition: all 0.25s ease;
    }

    .recover-close:hover {
        background: rgba(185, 199, 248, 0.12);
        color: #fff;
    }

    .recover-dialog__body {
        padding: 24px;
    }

    .recover-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 999px;
        background: rgba(185, 199, 248, 0.09);
        color: #dbe4ff;
        font-size: 0.85rem;
        font-weight: 700;
        margin-bottom: 16px;
    }

    .recover-copy {
        margin: 0 0 18px;
        color: #cbd5e1;
        line-height: 1.9;
        font-size: 0.96rem;
    }

    .recover-message {
        display: none;
        margin-bottom: 16px;
        padding: 14px 16px;
        border-radius: 16px;
        line-height: 1.8;
        font-weight: 700;
    }

    .recover-message.is-success {
        display: block;
        background: linear-gradient(135deg, #dcfce7 0%, #d9f99d 100%);
        color: #14532d;
    }

    .recover-message.is-error {
        display: block;
        background: rgba(239, 68, 68, 0.16);
        border: 1px solid rgba(248, 113, 113, 0.28);
        color: #fee2e2;
    }

    .recover-meta {
        margin-top: 16px;
        color: var(--text-muted);
        font-size: 0.88rem;
        text-align: center;
    }

    .recover-meta a {
        color: #dbe4ff;
        text-decoration: none;
        font-weight: 700;
    }

    .small.bod a {
        color: #b9c7f8;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s;
    }

    .small.bod a:hover {
        color: #ffffff;
    }

    .footer-text {
        color: #64748b;
        font-size: 0.85rem;
        margin-top: 25px;
        z-index: 10;
    }

    .footer-text a {
        color: #94a3b8;
        font-weight: 600;
        text-decoration: none;
        transition: color 0.3s;
    }

    .footer-text a:hover {
        color: #f8fafc;
    }

    /* Expired UI Styles */
    .expired-box h4 {
        color: var(--text-main);
        font-weight: 700;
    }
    
    .btn-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: none;
        border-radius: 14px;
        height: 55px;
        font-weight: 700;
        font-size: 1.1rem;
    }
  </style>
</head>
<body class="hold-transition login-page">

<div class="login-box">
  <div class="text-center">
      <img src="dist/img/brand/logo-secondary.png" alt="Vision HR" class="brand-logo">
      <h6 class="system-title">نظام إدارة الموارد البشرية</h6>
  </div>

  <?php if(!empty($login_error)):?>
      <div class="alert alert-danger alert-dismissible text-center" id="result-alert">
          <i class="fas fa-exclamation-circle ml-1"></i> <?=$login_error?>
      </div>
  <?php endif;?>
    
  <div class="card text-center">
  <?php
  if(!empty($_SESSION['msg'])){
      echo "<div class='card-body text-white'>";
      echo $_SESSION['msg'];
      if(!empty($_SESSION['ended'])){
          echo '<h5 class="mt-3 font-weight-bold">عذراً ..! إنتهت الفترة التجريبية</h5><p class="text-muted">لمتابعة العمل يمكنك التواصل معنا لتفعيل نسختك الخاصة من النظام</p>';
      }
      echo "</div>";
      unset($_SESSION['msg']);
      unset($_SESSION['ended']);
      die();
  }
  
  $_SESSION['end_demo'] = !empty($instance['SiteEndDate']) ? $instance['SiteEndDate'] : null;
  
  if(!empty($instance)){?>
    <?php if(empty($instance['SiteEndDate']) || (!empty($instance['SiteEndDate']) && $instance['SiteEndDate'] >= $today_date)){?>
    
    <div class="card-header">
      <h5>تسجيل الدخول</h5>
    </div>
    
    <div class="card-body">
      <form action="" role="form" method="post">
        
        <div class="input-group">
          <div class="input-group-prepend">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
          <!-- dir="ltr" is kept here so typing english emails is natural -->
          <input type="email" class="form-control" name="user_inpt" id="user_inpt" placeholder="البريد الإلكتروني" autocomplete="off" dir="ltr" style="text-align: left;">
        </div>

        <div class="input-group">
          <div class="input-group-prepend">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
          <!-- dir="ltr" is kept here so typing passwords is natural -->
          <input type="password" class="form-control" name="user_password" id="user_password" placeholder="كلمة المرور" autocomplete="off" dir="ltr" style="text-align: left;">
        </div>

        <button type="submit" class="btn btn-primary btn-block w-100" name="user_login">
            دخول <i class="fas fa-arrow-left mr-2" style="font-size: 0.9em; margin-right: 8px;"></i>
        </button>

      </form>
      
      <div class="small text-center bod forgot-link-wrap">
          <button type="button" class="forgot-link" id="openRecoverModal">نسيت كلمة المرور؟ إستعادة الحساب</button>
      </div>
      
    <?php }else{?>
        <div class="card-body text-center mt-4 mb-4 mx-2 expired-box">
            <i class="fas fa-clock fa-3x text-danger mb-3"></i>
            <h4>عذراً..!</h4>
            <h5 class="text-danger mt-2 mb-4">إنتهت الفترة التجريبية</h5>
            <a href="https://visionsys.net" type="button" class="btn btn-success btn-block w-100">ترقية النظام <i class="fas fa-gem ml-2"></i></a>
        </div>
    <?php } }?>

    <div class="overlay" style="display:none; background: rgba(15, 23, 42, 0.8);" id="add_holdon">
        <i class="fas fa-2x fa-sync-alt fa-spin text-white"></i>
    </div>
    
  </div>
</div>

<p class="footer-text">مدعوم بواسطة <a href="https://visionsys.net" target="_blank">شركة التطور الرقمي</a></p>

<div class="recover-modal" id="recoverModal" aria-hidden="true">
  <div class="recover-dialog" role="dialog" aria-modal="true" aria-labelledby="recoverTitle">
    <div class="recover-dialog__header">
      <h6 id="recoverTitle">استعادة كلمة المرور</h6>
      <button type="button" class="recover-close" id="closeRecoverModal" aria-label="إغلاق">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="recover-dialog__body">
      <span class="recover-kicker"><i class="fas fa-envelope-open-text"></i> Vision HR Recovery</span>
      <p class="recover-copy">أدخل البريد الإلكتروني المرتبط بحسابك، وسنرسل لك رابط إعادة تعيين كلمة المرور مباشرة عبر البريد الإلكتروني المعرّف في إعدادات النظام.</p>
      <div id="recoverMessage" class="recover-message"></div>
      <form id="recoverForm" novalidate>
        <div class="input-group">
          <div class="input-group-prepend">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
          <input type="email" class="form-control" id="recoverEmail" name="email" placeholder="name@company.com" autocomplete="email" dir="ltr" style="text-align: left;">
        </div>

        <button type="submit" class="btn btn-primary btn-block w-100" id="recoverSubmitBtn">
          إرسال رابط إعادة التعيين <i class="fas fa-paper-plane mr-2" style="font-size: 0.9em; margin-right: 8px;"></i>
        </button>
      </form>

      <div class="recover-meta">
        إن أردت الصفحة الكاملة يمكنك فتح <a href="forget-paswd">واجهة الاستعادة</a>.
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
$(document).ready(function(){
    var recoverModal = $('#recoverModal');
    var recoverForm = $('#recoverForm');
    var recoverMessage = $('#recoverMessage');
    var recoverSubmitBtn = $('#recoverSubmitBtn');
    var recoverEmail = $('#recoverEmail');

    function showRecoverMessage(type, text) {
        recoverMessage.removeClass('is-success is-error');
        recoverMessage.addClass(type === 'success' ? 'is-success' : 'is-error').text(text);
    }

    function openRecoverModal() {
        recoverMessage.removeClass('is-success is-error').text('');
        recoverForm[0].reset();
        recoverModal.addClass('is-open').attr('aria-hidden', 'false');
        $('body').css('overflow', 'hidden');
        setTimeout(function() { recoverEmail.trigger('focus'); }, 80);
    }

    function closeRecoverModal() {
        recoverModal.removeClass('is-open').attr('aria-hidden', 'true');
        $('body').css('overflow', '');
    }

    $('#openRecoverModal').on('click', function() {
        openRecoverModal();
    });

    $('#closeRecoverModal').on('click', function() {
        closeRecoverModal();
    });

    recoverModal.on('click', function(event) {
        if (event.target === this) {
            closeRecoverModal();
        }
    });

    document.addEventListener("keydown", function(event) {
        if (event.key === 'Escape' && recoverModal.hasClass('is-open')) {
            closeRecoverModal();
            return;
        }
        if (event.altKey && event.code === "KeyX") {
            window.open('test/test_user_session.php', '', 'window settings');
            return false;
        }
    });

    recoverForm.on('submit', function(event) {
        event.preventDefault();

        var email = $.trim(recoverEmail.val());
        if (!email) {
            showRecoverMessage('error', 'يرجى إدخال البريد الإلكتروني أولاً.');
            recoverEmail.trigger('focus');
            return;
        }

        recoverSubmitBtn.prop('disabled', true).text('جارٍ إرسال الطلب...');

        fetch('/api/v1/auth/forgot-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email: email })
        })
        .then(function(response) {
            return response.json().catch(function() { return {}; });
        })
        .then(function(payload) {
            if (payload.success) {
                showRecoverMessage('success', payload.message || 'إذا كان البريد الإلكتروني موجوداً فسيتم إرسال الرابط خلال لحظات.');
                recoverForm[0].reset();
            } else {
                showRecoverMessage('error', payload.message || 'تعذر إرسال الطلب حالياً. حاول مرة أخرى.');
            }
        })
        .catch(function() {
            showRecoverMessage('error', 'حدث خطأ أثناء الاتصال بالخادم. حاول مرة أخرى بعد قليل.');
        })
        .finally(function() {
            recoverSubmitBtn.prop('disabled', false).html('إرسال رابط إعادة التعيين <i class=\"fas fa-paper-plane mr-2\" style=\"font-size: 0.9em; margin-right: 8px;\"></i>');
        });
    });
});
</script>
</body>
</html>
