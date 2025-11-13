<?php
require_once 'includes/functions.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$password = '';
$confirm_password = '';

if (empty($token)) {
    header("Location: login.php");
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // التحقق من صحة الرمز
    $stmt = $conn->prepare("SELECT * FROM password_reset_tokens WHERE token = ? AND used = FALSE AND expires_at > NOW()");
    $stmt->execute([$token]);
    $token_data = $stmt->fetch();

    if (!$token_data) {
        $error = 'هذا الرابط غير صالح أو انتهت صلاحيته. يرجى طلب رابط جديد.';
    } else {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($password) || empty($confirm_password)) {
                $error = 'يرجى ملء جميع الحقول';
            } elseif (strlen($password) < 8) {
                $error = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
            } elseif ($password !== $confirm_password) {
                $error = 'كلمة المرور غير متطابقة';
            } else {
                // تحديث كلمة المرور
                $hashed_password = hash_password($password);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");

                if ($update_stmt->execute([$hashed_password, $token_data['user_id']])) {
                    // تمييز الرمز بأنه مستخدم
                    $used_stmt = $conn->prepare("UPDATE password_reset_tokens SET used = TRUE WHERE id = ?");
                    $used_stmt->execute([$token_data['id']]);

                    $success = 'تم تحديث كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول بكلمة المرور الجديدة.';
                } else {
                    $error = 'حدث خطأ أثناء تحديث كلمة المرور. يرجى المحاولة مرة أخرى.';
                }
            }
        }
    }
} catch (Exception $e) {
    $error = 'حدث خطأ في قاعدة البيانات. يرجى المحاولة مرة أخرى.';
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور - موقع حجز المواعيد الطبية</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--medical-green) 100%);
            padding: 2rem 1rem;
        }

        .auth-card {
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            padding: 3rem;
            width: 100%;
            max-width: 450px;
        }

        .auth-title {
            text-align: center;
            color: var(--text-primary);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 2rem;
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
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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
            background-color: var(--primary-blue);
            color: white;
        }

        .btn:hover {
            background: #1d4ed8;
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
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
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">إعادة تعيين كلمة المرور</h1>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
                <div class="auth-footer">
                    <a href="login.php">العودة لصفحة تسجيل الدخول</a>
                </div>
            <?php elseif ($token_data): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="password">كلمة المرور الجديدة</label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">تأكيد كلمة المرور الجديدة</label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        حفظ كلمة المرور الجديدة
                    </button>
                </form>
            <?php else: ?>
                 <div class="auth-footer">
                    <a href="forgot-password.php">طلب رابط جديد</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
