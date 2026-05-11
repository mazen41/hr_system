<?php
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>نسيت كلمة المرور - Vision HR</title>
    <link rel="icon" type="image/png" href="dist/img/brand/logo-icon.png">
    <link rel="stylesheet" href="dist/css/brand.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap">
    <style>
        :root {
            --page-bg-1: #071466;
            --page-bg-2: #0d21a5;
            --page-bg-3: #6d7dcb;
            --card-bg: rgba(255, 255, 255, 0.14);
            --card-border: rgba(255, 255, 255, 0.18);
            --input-bg: rgba(7, 20, 102, 0.28);
            --text-main: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.78);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Cairo', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(185, 199, 248, 0.32) 0%, transparent 35%),
                radial-gradient(circle at bottom right, rgba(109, 125, 203, 0.26) 0%, transparent 38%),
                linear-gradient(135deg, var(--page-bg-1) 0%, var(--page-bg-2) 52%, #142a99 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: var(--text-main);
        }

        .auth-shell {
            width: 100%;
            max-width: 520px;
        }

        .auth-brand {
            text-align: center;
            margin-bottom: 26px;
        }

        .auth-brand img {
            width: min(190px, 54vw);
            height: auto;
            filter: drop-shadow(0 18px 32px rgba(7, 20, 102, 0.4));
        }

        .auth-brand h1 {
            margin: 18px 0 8px;
            font-size: clamp(1.55rem, 2.8vw, 2rem);
            font-weight: 800;
        }

        .auth-brand p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.98rem;
        }

        .auth-card {
            background: linear-gradient(180deg, rgba(255,255,255,.12) 0%, rgba(255,255,255,.08) 100%);
            border: 1px solid var(--card-border);
            border-radius: 28px;
            backdrop-filter: blur(18px);
            box-shadow: 0 32px 60px rgba(7, 20, 102, 0.38);
            overflow: hidden;
        }

        .auth-card__top {
            padding: 28px 28px 18px;
            border-bottom: 1px solid rgba(255,255,255,0.12);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.1);
            color: #dbe4ff;
            border-radius: 999px;
            padding: 8px 14px;
            font-size: .86rem;
            font-weight: 700;
        }

        .auth-card__body {
            padding: 28px;
        }

        .auth-card h2 {
            margin: 0 0 10px;
            font-size: 1.9rem;
            font-weight: 800;
        }

        .auth-card .lead {
            margin: 0 0 22px;
            color: var(--text-muted);
            line-height: 1.8;
            font-size: 0.98rem;
        }

        .form-group { margin-bottom: 16px; }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: #eef2ff;
        }

        .form-control {
            width: 100%;
            border: 1px solid rgba(255,255,255,0.14);
            background: var(--input-bg);
            color: #fff;
            border-radius: 16px;
            padding: 15px 16px;
            font-size: 1rem;
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .form-control::placeholder { color: rgba(255,255,255,0.48); }
        .form-control:focus {
            outline: none;
            border-color: #b9c7f8;
            box-shadow: 0 0 0 4px rgba(185, 199, 248, 0.16);
            transform: translateY(-1px);
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .btn-primary,
        .btn-secondary {
            appearance: none;
            border: none;
            border-radius: 16px;
            font-weight: 800;
            font-family: inherit;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease, opacity .2s ease;
        }

        .btn-primary {
            flex: 1 1 220px;
            min-height: 56px;
            color: #fff;
            background: linear-gradient(135deg, #0d21a5 0%, #3d4fb8 55%, #6d7dcb 100%);
            box-shadow: 0 18px 30px rgba(13, 33, 165, 0.28);
        }

        .btn-secondary {
            flex: 0 0 auto;
            min-height: 56px;
            padding: 0 18px;
            color: #dbe4ff;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary:hover,
        .btn-secondary:hover { transform: translateY(-2px); }
        .btn-primary:disabled { opacity: .65; cursor: wait; }

        .message {
            display: none;
            margin-bottom: 18px;
            border-radius: 16px;
            padding: 14px 16px;
            line-height: 1.8;
            font-weight: 600;
        }

        .message.is-success {
            display: block;
            color: #06213d;
            background: linear-gradient(135deg, #d9f99d 0%, #dcfce7 100%);
        }

        .message.is-error {
            display: block;
            color: #fff;
            background: rgba(220, 53, 69, 0.18);
            border: 1px solid rgba(255, 145, 145, 0.35);
        }

        .aux-links {
            margin-top: 18px;
            text-align: center;
            color: var(--text-muted);
        }

        .aux-links a {
            color: #dbe4ff;
            font-weight: 700;
            text-decoration: none;
        }

        @media (max-width: 640px) {
            body { padding: 16px; }
            .auth-card__top,
            .auth-card__body { padding: 22px 18px; }
            .actions { flex-direction: column; }
            .btn-primary, .btn-secondary { width: 100%; }
        }
    </style>
</head>
<body>
    <main class="auth-shell">
        <div class="auth-brand">
            <img src="dist/img/brand/logo-secondary.png" alt="Vision HR">
            <h1>استعادة كلمة المرور</h1>
            <p>واجهة استعادة الحساب صممت بألوان الهوية نفسها المستخدمة في Vision HR ورسائل البريد الخاصة به.</p>
        </div>

        <section class="auth-card">
            <div class="auth-card__top">
                <span class="eyebrow">Vision HR</span>
            </div>
            <div class="auth-card__body">
                <h2>نسيت كلمة المرور؟</h2>
                <p class="lead">أدخل البريد الإلكتروني المرتبط بحسابك، وسنرسل لك رسالة إعادة تعيين من خلال إعدادات SMTP التي يحددها المدير داخل النظام.</p>

                <div id="formMessage" class="message"></div>

                <form id="forgotForm">
                    <div class="form-group">
                        <label for="email">البريد الإلكتروني</label>
                        <input id="email" name="email" type="email" class="form-control" autocomplete="email" required placeholder="name@company.com">
                    </div>

                    <div class="actions">
                        <button class="btn-primary" type="submit" id="submitBtn">إرسال رابط إعادة التعيين</button>
                        <a class="btn-secondary" href="login-sys">العودة لتسجيل الدخول</a>
                    </div>
                </form>

                <div class="aux-links">
                    بعد استلام الرسالة يمكنك فتح الرابط مباشرة لتعيين كلمة مرور جديدة.
                </div>
            </div>
        </section>
    </main>

    <script>
        (function () {
            var form = document.getElementById('forgotForm');
            var submitBtn = document.getElementById('submitBtn');
            var messageBox = document.getElementById('formMessage');

            function showMessage(type, text) {
                messageBox.className = 'message ' + (type === 'success' ? 'is-success' : 'is-error');
                messageBox.textContent = text;
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                var email = document.getElementById('email').value.trim();

                if (!email) {
                    showMessage('error', 'يرجى إدخال البريد الإلكتروني أولاً.');
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.textContent = 'جارٍ إرسال الطلب...';

                fetch('/api/v1/auth/forgot-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email: email })
                })
                .then(function (response) {
                    return response.json().catch(function () { return {}; });
                })
                .then(function (payload) {
                    if (payload.success) {
                        showMessage('success', payload.message || 'إذا كان البريد الإلكتروني مسجلاً لدينا فسيتم إرسال رابط إعادة التعيين خلال لحظات.');
                        form.reset();
                    } else {
                        showMessage('error', payload.message || 'تعذر إرسال طلب إعادة التعيين حالياً. حاول مرة أخرى.');
                    }
                })
                .catch(function () {
                    showMessage('error', 'حدث خطأ أثناء الاتصال بالخادم. حاول مرة أخرى بعد قليل.');
                })
                .finally(function () {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'إرسال رابط إعادة التعيين';
                });
            });
        })();
    </script>
</body>
</html>
