<?php
require_once 'includes/functions.php';
require_once 'config/mail_config.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';
$email = '';
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'يرجى إدخال البريد الإلكتروني';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'البريد الإلكتروني غير صحيح';
    } else {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            // التحقق من وجود المستخدم
            $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // إنشاء رمز إعادة تعيين كلمة المرور
                $token = bin2hex(random_bytes(32));

                // حفظ الرمز في قاعدة البيانات - استخدام DATE_ADD في MySQL لتجنب مشاكل المنطقة الزمنية
                $stmt = $conn->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
                $stmt->execute([$user['id'], $token]);

                // إرسال البريد الإلكتروني باستخدام PHPMailer
                $reset_link = "http://localhost/medical-app-test/reset-password.php?token=" . $token;

                $mail = new PHPMailer(true);

                try {
                    // إعدادات الخادم
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USERNAME;
                    $mail->Password   = SMTP_PASSWORD;
                    $mail->SMTPSecure = SMTP_SECURE;
                    $mail->Port       = SMTP_PORT;
                    $mail->CharSet = 'UTF-8';

                    // المستلمون
                    $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
                    $mail->addAddress($email);

                    // المحتوى
                    $mail->isHTML(true);
                    $mail->Subject = 'إعادة تعيين كلمة المرور الخاصة بك';
                    $mail->Body    = '
                        <div style="font-family: \'Segoe UI\', \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 16px; color: #333; line-height: 1.6; background-color: #f4f7f6; padding: 30px 0;">
                            <div style="max-width: 600px; margin: 0 auto; padding: 0; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); overflow: hidden;">
                                <div style="background-color: #0EA5E9; padding: 25px 20px; text-align: center; color: #ffffff;">
                                    <h1 style="margin: 0; font-size: 28px; font-weight: 600;">إعادة تعيين كلمة المرور</h1>
                                </div>
                                <div style="padding: 30px 40px;">
                                    <p style="margin-top: 0;">مرحباً،</p>
                                    <p>لقد طلبت إعادة تعيين كلمة المرور الخاصة بك. انقر على الزر أدناه لإكمال العملية:</p>
                                    <p style="text-align: center; margin: 35px 0;">
                                        <a href="' . $reset_link . '" style="display: inline-block; padding: 15px 30px; background-color: #0EA5E9; color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 18px; font-weight: bold; transition: background-color 0.3s ease;">
                                            إعادة تعيين كلمة المرور
                                        </a>
                                    </p>
                                    <p>إذا لم تتمكن من النقر على الزر، يمكنك نسخ ولصق الرابط التالي في متصفحك:</p>
                                    <p style="word-break: break-all; font-size: 14px; background-color: #eef; padding: 10px; border-radius: 5px;"><a href="' . $reset_link . '" style="color: #0EA5E9; text-decoration: none;">' . $reset_link . '</a></p>
                                    <p>إذا لم تطلب إعادة تعيين كلمة المرور هذه، يرجى تجاهل هذا البريد الإلكتروني.</p>
                                    <p style="margin-bottom: 0;">شكراً لك،<br>فريق دعم موقع حجز المواعيد الطبية</p>
                                </div>
                                <div style="background-color: #f0f0f0; padding: 20px 40px; text-align: center; font-size: 13px; color: #777; border-top: 1px solid #e0e0e0;">
                                    <p style="margin: 0;">هذا البريد الإلكتروني مرسل تلقائيًا، يرجى عدم الرد عليه.</p>
                                </div>
                            </div>
                        </div>';
                    $mail->AltBody = "مرحباً،\n\nلقد طلبت إعادة تعيين كلمة المرور الخاصة بك. يرجى نسخ ولصق الرابط التالي في متصفحك لإكمال العملية:\n{$reset_link}\n\nإذا لم تطلب هذا، يرجى تجاهل هذا البريد الإلكتروني.";

                    $mail->send();
                    $message = 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني. يرجى التحقق من صندوق الوارد الخاص بك';
                    $message_type = 'success';

                } catch (Exception $e) {
                    $message = "لم نتمكن من إرسال البريد الإلكتروني. خطأ: {$mail->ErrorInfo}";
                    $message_type = 'error';
                }

            } else {
                $message = 'البريد الإلكتروني غير مسجل في النظام';
                $message_type = 'error';
            }

        } catch (Exception $e) {
            $message = 'حدث خطأ أثناء معالجة طلبك. يرجى المحاولة مرة أخرى.';
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نسيت كلمة المرور - موقع حجز المواعيد الطبية</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #0EA5E9;
            --medical-green: #10B981;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --radius: 8px;
            --radius-xl: 16px;
            --transition: all 0.3s ease;
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .auth-card {
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-blue), var(--medical-green));
        }

        .auth-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-logo i {
            font-size: 3rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .auth-title {
            text-align: center;
            color: var(--text-primary);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .auth-subtitle {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            z-index: 2;
        }

        .form-group input {
            width: 100%;
            padding: 1rem;
            padding-right: 3rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }

        .btn {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
            color: var(--text-secondary);
        }

        .auth-footer a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .back-home {
            position: absolute;
            top: 2rem;
            right: 2rem;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .back-home:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        @media (max-width: 768px) {
            .auth-card {
                padding: 2rem;
                margin: 1rem;
            }

            .back-home {
                position: static;
                margin-bottom: 2rem;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <a href="index.php" class="back-home">
            <i class="fas fa-arrow-right"></i>
            العودة للرئيسية
        </a>

        <div class="auth-card">
            <div class="auth-logo">
                <i class="fas fa-key"></i>
            </div>

            <h1 class="auth-title">نسيت كلمة المرور؟</h1>
            <p class="auth-subtitle">
                لا تقلق! أدخل بريدك الإلكتروني وسنرسل لك رابط لإعادة تعيين كلمة المرور.
            </p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">البريد الإلكتروني</label>
                        <div class="input-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        إرسال رابط إعادة التعيين
                    </button>
                </form>
            <?php endif; ?>

            <div class="auth-footer">
                تذكرت كلمة المرور؟ <a href="login.php">سجل دخولك</a>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus on email input
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            if (emailInput && !emailInput.value) {
                emailInput.focus();
            }
        });
    </script>
</body>
</html>
